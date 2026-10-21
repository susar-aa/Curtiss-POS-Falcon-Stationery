<?php
/**
 * Curtiss POS - Business Day & Cash Drawer Core Engine (Stage 11)
 * Manages daily opening/closing, append-only cash drawer movements,
 * manual cash in/out, shortage/overage accounting reconciliation, and active day locking.
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Audit.php';
require_once __DIR__ . '/Accounting.php';
require_once __DIR__ . '/MaskingHelper.php';

class BusinessDay
{
    const DEFAULT_RETAINED_CASH = 20000.00;

    /**
     * Fetch active open business day for a branch
     */
    public static function getActiveDay(int $branchId): ?array
    {
        try {
            return Database::fetchOne("
                SELECT bd.*, b.name AS branch_name, u.full_name AS opener_name
                FROM business_days bd
                JOIN branches b ON bd.branch_id = b.id
                JOIN users u ON bd.opened_by = u.id
                WHERE bd.branch_id = :branch_id AND bd.status = 'open'
                LIMIT 1
            ", [':branch_id' => $branchId]);
        } catch (Throwable $e) {
            Logger::error("Failed to fetch active business day for branch #{$branchId}: " . $e->getMessage(), $e);
            return null;
        }
    }

    /**
     * Alias for getActiveDay
     */
    public static function getActiveBusinessDay(int $branchId): ?array
    {
        return self::getActiveDay($branchId);
    }

    /**
     * Fetch active open business day for a branch, or auto-start a new day with retained cash float (default LKR 20,000)
     */
    public static function getOrCreateActiveDay(int $branchId, int $userId = 1): ?array
    {
        $activeDay = self::getActiveDay($branchId);
        if ($activeDay) {
            return $activeDay;
        }

        $retainedCash = self::DEFAULT_RETAINED_CASH;
        try {
            $lastClosed = Database::fetchOne("
                SELECT retained_cash FROM business_days
                WHERE branch_id = :branch_id AND status = 'closed'
                ORDER BY id DESC LIMIT 1
            ", [':branch_id' => $branchId]);

            if ($lastClosed && isset($lastClosed['retained_cash']) && (float)$lastClosed['retained_cash'] >= 0) {
                $retainedCash = (float)$lastClosed['retained_cash'];
            }
        } catch (Throwable $e) {
            Logger::error("Failed to query last closed retained cash float: " . $e->getMessage(), $e);
        }

        $res = self::startDay($branchId, $retainedCash, $userId, 'Auto-started business day with retained cash float');
        if (!empty($res['success'])) {
            return self::getActiveDay($branchId);
        }

        return null;
    }

    /**
     * Get expected cash balance in active drawer for a branch
     */
    public static function getExpectedDrawerCash(int $branchId): float
    {
        try {
            $activeDay = self::getActiveDay($branchId);
            if (!$activeDay) return 0.00;

            $bdayId = (int)$activeDay['id'];
            $openingCash = (float)$activeDay['opening_amount'];

            $mRow = Database::fetchOne("
                SELECT 
                    COALESCE(SUM(CASE WHEN direction = 'in' THEN amount ELSE 0 END), 0.00) AS cash_in,
                    COALESCE(SUM(CASE WHEN direction = 'out' THEN amount ELSE 0 END), 0.00) AS cash_out
                FROM cash_drawer_movements
                WHERE business_day_id = :bday_id
            ", [':bday_id' => $bdayId]);

            $cashIn  = (float)($mRow['cash_in'] ?? 0.00);
            $cashOut = (float)($mRow['cash_out'] ?? 0.00);

            return round($openingCash + $cashIn - $cashOut, 2);
        } catch (Throwable $e) {
            Logger::error("Failed to fetch expected drawer cash for branch #{$branchId}: " . $e->getMessage(), $e);
            return 0.00;
        }
    }

    /**
     * Start / Open a new Business Day for a branch
     */
    public static function startDay(int $branchId, float $openingCash = self::DEFAULT_RETAINED_CASH, int $userId = 1, ?string $notes = null): array
    {
        $db = Database::getInstance()->getConnection();
        $inTransaction = $db->inTransaction();
        try {
            if (!$inTransaction) {
                $db->beginTransaction();
            }

            if ($openingCash < 0.00) {
                if (!$inTransaction && $db->inTransaction()) $db->rollBack();
                return ['success' => false, 'message' => 'Opening cash cannot be negative.'];
            }

            // 1. Verify no open business day exists for this branch (with lock)
            $stmtLock = $db->prepare("
                SELECT id, business_date FROM business_days 
                WHERE branch_id = :branch_id AND status = 'open' FOR UPDATE
            ");
            $stmtLock->execute([':branch_id' => $branchId]);
            $active = $stmtLock->fetch(PDO::FETCH_ASSOC);

            if ($active) {
                if (!$inTransaction && $db->inTransaction()) $db->rollBack();
                return [
                    'success' => false,
                    'message' => "An active business day (Date: {$active['business_date']}) is already open for this branch. Close it before starting a new day."
                ];
            }

            $openingCash = round(max(0.00, $openingCash), 2);
            $businessDate = date('Y-m-d');

            // 2. Create business_days header record
            $stmtBd = $db->prepare("
                INSERT INTO business_days (branch_id, business_date, opening_cash, retained_cash, status, opened_by, notes)
                VALUES (:branch_id, :business_date, :opening_cash, :retained_cash, 'open', :opened_by, :notes)
            ");

            $stmtBd->execute([
                ':branch_id'     => $branchId,
                ':business_date' => $businessDate,
                ':opening_cash'  => $openingCash,
                ':retained_cash' => $openingCash,
                ':opened_by'     => $userId,
                ':notes'         => trim($notes ?? '') ?: null
            ]);

            $businessDayId = (int)$db->lastInsertId();

            // 3. Record Initial Opening Cash Drawer Movement
            $stmtCdm = $db->prepare("
                INSERT INTO cash_drawer_movements (
                    business_day_id, branch_id, movement_type, source_type, source_id,
                    amount, direction, reason, notes, user_id
                ) VALUES (
                    :business_day_id, :branch_id, 'opening_cash', 'business_days', :source_id,
                    :amount, 'in', 'Initial Opening Cash Drawer', :notes, :user_id
                )
            ");

            $stmtCdm->execute([
                ':business_day_id' => $businessDayId,
                ':branch_id'       => $branchId,
                ':source_id'       => $businessDayId,
                ':amount'          => $openingCash,
                ':notes'           => $notes,
                ':user_id'         => $userId
            ]);

            if (!$inTransaction) {
                $db->commit();
            }

            Audit::log('BUSINESS_DAY_STARTED', 'cash_drawer', (string)$businessDayId, null, [
                'branch_id'     => $branchId,
                'business_date' => $businessDate,
                'opening_cash'  => $openingCash
            ]);

            return [
                'success'         => true,
                'business_day_id' => $businessDayId,
                'business_date'   => $businessDate,
                'opening_cash'    => $openingCash,
                'message'         => "Business Day started successfully for {$businessDate} with LKR " . number_format($openingCash, 2) . " opening cash."
            ];

        } catch (Throwable $e) {
            if (!$inTransaction && $db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to start business day: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Failed to start business day: ' . $e->getMessage()];
        }
    }

    /**
     * Record an append-only cash drawer movement
     */
    public static function recordDrawerMovement(array $data): array
    {
        try {
            $db = Database::getInstance()->getConnection();
            $bDayId   = (int)($data['business_day_id'] ?? 0);
            $branchId = (int)($data['branch_id'] ?? 0);
            $type     = $data['movement_type'] ?? '';
            $stype    = !empty($data['source_type']) ? trim($data['source_type']) : null;
            $sid      = !empty($data['source_id']) ? (int)$data['source_id'] : null;
            $amount   = round((float)($data['amount'] ?? 0), 2);
            $dir      = strtolower($data['direction'] ?? 'in') === 'out' ? 'out' : 'in';
            $reason   = trim($data['reason'] ?? 'Cash Movement');
            $notes    = trim($data['notes'] ?? '') ?: null;
            $userId   = (int)($data['user_id'] ?? 1);

            if ($bDayId <= 0 || $branchId <= 0 || $amount <= 0 || empty($type)) {
                return ['success' => false, 'message' => 'Invalid parameters for cash drawer movement.'];
            }

            // Check duplicate movement for source transaction
            if ($sid !== null && $stype !== null) {
                $existing = Database::fetchOne("
                    SELECT id FROM cash_drawer_movements
                    WHERE business_day_id = :bday AND source_type = :stype AND source_id = :sid AND movement_type = :mtype
                ", [
                    ':bday'  => $bDayId,
                    ':stype' => $stype,
                    ':sid'   => $sid,
                    ':mtype' => $type
                ]);

                if ($existing) {
                    return ['success' => true, 'already_recorded' => true, 'id' => (int)$existing['id']];
                }
            }

            $stmt = $db->prepare("
                INSERT INTO cash_drawer_movements (
                    business_day_id, branch_id, movement_type, source_type, source_id,
                    amount, direction, reason, notes, user_id
                ) VALUES (
                    :bday, :branch, :type, :stype, :sid,
                    :amount, :dir, :reason, :notes, :user
                )
            ");

            $stmt->execute([
                ':bday'   => $bDayId,
                ':branch' => $branchId,
                ':type'   => $type,
                ':stype'  => $stype,
                ':sid'    => $sid,
                ':amount' => $amount,
                ':dir'    => $dir,
                ':reason' => $reason,
                ':notes'  => $notes,
                ':user'   => $userId
            ]);

            $id = (int)$db->lastInsertId();
            return ['success' => true, 'id' => $id];

        } catch (Throwable $e) {
            Logger::error("Failed to record cash drawer movement: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Manual Cash In / Cash Out Movement by Authorized User
     */
    public static function manualCashMovement(int $branchId, string $type, float $amount, string $reason, ?string $notes, int $userId): array
    {
        $activeDay = self::getActiveDay($branchId);
        if (!$activeDay) {
            return ['success' => false, 'message' => 'Cannot perform cash movement. No active business day is currently open for this branch.'];
        }

        $type = strtolower(trim($type));
        if (!in_array($type, ['cash_in', 'cash_out'])) {
            return ['success' => false, 'message' => "Invalid movement type '{$type}'. Must be 'cash_in' or 'cash_out'."];
        }

        $amount = round((float)$amount, 2);
        if ($amount <= 0.00) {
            return ['success' => false, 'message' => 'Movement amount must be greater than zero.'];
        }

        if (empty(trim($reason))) {
            return ['success' => false, 'message' => 'Reason for manual cash movement is required.'];
        }

        $db = Database::getInstance()->getConnection();

        try {
            $db->beginTransaction();

            $bDayId = (int)$activeDay['id'];
            $dir = ($type === 'cash_in') ? 'in' : 'out';

            $mvRes = self::recordDrawerMovement([
                'business_day_id' => $bDayId,
                'branch_id'       => $branchId,
                'movement_type'   => $type,
                'source_type'     => 'manual',
                'amount'          => $amount,
                'direction'       => $dir,
                'reason'          => $reason,
                'notes'           => $notes,
                'user_id'         => $userId
            ]);

            if (!$mvRes['success']) {
                $db->rollBack();
                return $mvRes;
            }

            // Post Accounting Double-Entry for Manual Cash In / Out
            $cashAccId = Accounting::getAccountIdByCode(Accounting::CODE_CASH);
            $expAccId  = Accounting::getAccountIdByCode(Accounting::CODE_EXPENSES);
            $shortageAccId = Accounting::getAccountIdByCode(Accounting::CODE_CASH_SHORTAGE_OVERAGE);

            if ($type === 'cash_in') {
                $lines = [
                    ['account_id' => $cashAccId, 'debit' => $amount, 'credit' => 0.00, 'memo' => "Manual Cash In: {$reason}"],
                    ['account_id' => $shortageAccId, 'debit' => 0.00, 'credit' => $amount, 'memo' => "Cash In Offset"]
                ];
            } else { // cash_out
                $lines = [
                    ['account_id' => $expAccId, 'debit' => $amount, 'credit' => 0.00, 'memo' => "Manual Cash Out: {$reason}"],
                    ['account_id' => $cashAccId, 'debit' => 0.00, 'credit' => $amount, 'memo' => "Cash Out from Drawer"]
                ];
            }

            $header = [
                'branch_id'   => $branchId,
                'entry_date'  => date('Y-m-d'),
                'source_type' => 'manual',
                'source_id'   => $mvRes['id'],
                'description' => "Manual Drawer " . ($type === 'cash_in' ? 'Cash In' : 'Cash Out') . ": {$reason}",
                'created_by'  => $userId
            ];

            $jeRes = Accounting::postJournalEntry($header, $lines);
            if (!$jeRes['success']) {
                $db->rollBack();
                return ['success' => false, 'message' => "Accounting posting failed: " . $jeRes['message']];
            }

            $db->commit();

            Audit::log('MANUAL_CASH_MOVEMENT', 'cash_drawer', (string)$mvRes['id'], null, [
                'type' => $type, 'amount' => $amount, 'reason' => $reason
            ]);

            return [
                'success' => true,
                'message' => "Manual " . ($type === 'cash_in' ? 'Cash In' : 'Cash Out') . " of LKR " . number_format($amount, 2) . " recorded successfully."
            ];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Manual cash movement failed: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get Cash Drawer Summary & Reconciliation Breakdown for a Business Day
     */
    public static function getDrawerSummary(int $businessDayId): array
    {
        try {
            $bday = Database::fetchOne("
                SELECT bd.*, b.name AS branch_name, u.full_name AS opener_name, u2.full_name AS closer_name
                FROM business_days bd
                JOIN branches b ON bd.branch_id = b.id
                JOIN users u ON bd.opened_by = u.id
                LEFT JOIN users u2 ON bd.closed_by = u2.id
                WHERE bd.id = :id
            ", [':id' => $businessDayId]);

            if (!$bday) {
                return ['success' => false, 'message' => 'Business day record not found.'];
            }

            $movements = Database::fetchAll("
                SELECT movement_type, direction, SUM(amount) AS total_amount
                FROM cash_drawer_movements
                WHERE business_day_id = :bday_id
                GROUP BY movement_type, direction
            ", [':bday_id' => $businessDayId]);

            $summary = [
                'opening_cash'              => 0.00,
                'pos_cash_sales'            => 0.00,
                'customer_cash_collections' => 0.00,
                'supplier_cash_refunds'     => 0.00,
                'manual_cash_in'            => 0.00,
                'overage_adjustments'       => 0.00,
                'cash_refunds'              => 0.00,
                'supplier_cash_payments'    => 0.00,
                'employee_advances'         => 0.00,
                'salary_payments'           => 0.00,
                'drawer_expenses'           => 0.00,
                'manual_cash_out'           => 0.00,
                'petty_cash_reimbursements' => 0.00,
                'shortage_adjustments'      => 0.00,
                'total_cash_in'             => 0.00,
                'total_cash_out'            => 0.00,
                'expected_cash'             => 0.00
            ];

            foreach ($movements as $m) {
                $type = $m['movement_type'];
                $amt  = (float)$m['total_amount'];
                $dir  = strtolower($m['direction']);

                if ($type === 'opening_cash') {
                    $summary['opening_cash'] += $amt;
                } elseif ($type === 'pos_sale') {
                    $summary['pos_cash_sales'] += $amt;
                } elseif ($type === 'customer_collection') {
                    $summary['customer_cash_collections'] += $amt;
                } elseif ($type === 'supplier_refund' || $type === 'supplier_cash_refund') {
                    $summary['supplier_cash_refunds'] += $amt;
                } elseif ($type === 'cash_in') {
                    $summary['manual_cash_in'] += $amt;
                } elseif ($type === 'overage_adjustment') {
                    $summary['overage_adjustments'] += $amt;
                } elseif ($type === 'cash_refund') {
                    $summary['cash_refunds'] += $amt;
                } elseif ($type === 'supplier_payment') {
                    $summary['supplier_cash_payments'] += $amt;
                } elseif ($type === 'employee_advance') {
                    $summary['employee_advances'] += $amt;
                } elseif ($type === 'salary_payment' || $type === 'payroll_payment') {
                    $summary['salary_payments'] += $amt;
                } elseif ($type === 'expense_payout' || $type === 'expense') {
                    $summary['drawer_expenses'] += $amt;
                } elseif ($type === 'expense_reversal') {
                    $summary['drawer_expenses'] -= $amt;
                } elseif ($type === 'cash_out') {
                    $summary['manual_cash_out'] += $amt;
                } elseif ($type === 'petty_cash_reimbursement' || $type === 'petty_cash_expense') {
                    $summary['petty_cash_reimbursements'] += $amt;
                } elseif ($type === 'shortage_adjustment') {
                    $summary['shortage_adjustments'] += $amt;
                }

                if ($dir === 'in') {
                    $summary['total_cash_in'] += $amt;
                } else {
                    $summary['total_cash_out'] += $amt;
                }
            }

            $summary['opening_cash']              = round($summary['opening_cash'], 2);
            $summary['pos_cash_sales']            = round($summary['pos_cash_sales'], 2);
            $summary['customer_cash_collections'] = round($summary['customer_cash_collections'], 2);
            $summary['supplier_cash_refunds']     = round($summary['supplier_cash_refunds'], 2);
            $summary['manual_cash_in']            = round($summary['manual_cash_in'], 2);
            $summary['overage_adjustments']       = round($summary['overage_adjustments'], 2);
            $summary['cash_refunds']              = round($summary['cash_refunds'], 2);
            $summary['supplier_cash_payments']    = round($summary['supplier_cash_payments'], 2);
            $summary['employee_advances']         = round($summary['employee_advances'], 2);
            $summary['salary_payments']           = round($summary['salary_payments'], 2);
            $summary['drawer_expenses']           = round($summary['drawer_expenses'], 2);
            $summary['manual_cash_out']           = round($summary['manual_cash_out'], 2);
            $summary['petty_cash_reimbursements'] = round($summary['petty_cash_reimbursements'], 2);
            $summary['shortage_adjustments']      = round($summary['shortage_adjustments'], 2);

            $summary['total_cash_in']  = round($summary['total_cash_in'], 2);
            $summary['total_cash_out'] = round($summary['total_cash_out'], 2);
            $summary['expected_cash']  = round($summary['total_cash_in'] - $summary['total_cash_out'], 2);

            $salesSum = self::getDaySalesSummary($businessDayId);
            $summary['expected_card_sales'] = round((float)($salesSum['card_sales'] ?? 0.00), 2);

            if (MaskingHelper::isSpecialUser()) {
                foreach ($summary as $k => $val) {
                    if (is_numeric($val)) {
                        $summary[$k] = MaskingHelper::maskValue((float)$val);
                    }
                }
            }

            return [
                'success'      => true,
                'business_day' => $bday,
                'summary'      => $summary
            ];

        } catch (Throwable $e) {
            Logger::error("Failed to get drawer summary for BDay #{$businessDayId}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Manager Day Closing & Cash Reconciliation
     */
    public static function closeDay(int $businessDayId, float $actualCountedCash, float $retainedCash = self::DEFAULT_RETAINED_CASH, int $userId = 1, ?string $notes = null, array $options = [], $cashDenominations = null, float $cardSettlementAmount = 0.00): array
    {
        $db = Database::getInstance()->getConnection();

        try {
            $db->beginTransaction();

            if (!empty($options['has_pending_offline_sales']) || !empty($options['pending_offline_sales_count'])) {
                $db->rollBack();
                return [
                    'success' => false,
                    'message' => 'Cannot close business day: There are pending offline sales that must be synchronized first.'
                ];
            }

            $bday = Database::fetchOne("
                SELECT * FROM business_days WHERE id = :id FOR UPDATE
            ", [':id' => $businessDayId]);

            if (!$bday) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Business day record not found.'];
            }

            if ($bday['status'] === 'closed') {
                $db->rollBack();
                return ['success' => false, 'message' => 'Double closing prevented: This business day is already closed.'];
            }

            $actualCountedCash    = round(max(0.00, $actualCountedCash), 2);
            $cardSettlementAmount = round(max(0.00, $cardSettlementAmount), 2);
            $retainedCash         = round(max(0.00, min($retainedCash, $actualCountedCash)), 2);

            // Get live expected cash
            $sumRes = self::getDrawerSummary($businessDayId);
            if (!$sumRes['success']) {
                $db->rollBack();
                return $sumRes;
            }

            $expectedCash = $sumRes['summary']['expected_cash'];
            $diff = round($actualCountedCash - $expectedCash, 2);

            if (abs($diff) > 0.001 && empty(trim($notes ?? ''))) {
                $db->rollBack();
                return [
                    'success' => false,
                    'message' => 'Reason for cash variance (Shortage/Overage) is required before closing.'
                ];
            }

            $shortageAmount = 0.00;
            $overageAmount  = 0.00;

            if ($diff < -0.001) {
                $shortageAmount = abs($diff);
            } elseif ($diff > 0.001) {
                $overageAmount = $diff;
            }

            $cashToDeposit = round(max(0.00, $actualCountedCash - $retainedCash), 2);

            $denomJson = null;
            if ($cashDenominations !== null) {
                $denomJson = is_array($cashDenominations) ? json_encode($cashDenominations) : (string)$cashDenominations;
            }

            $splitTransfers = $options['split_transfers'] ?? [];
            $splitJson = !empty($splitTransfers) ? (is_string($splitTransfers) ? $splitTransfers : json_encode($splitTransfers)) : null;

            // 1. Update business_days record status to closed
            $stmtClose = $db->prepare("
                UPDATE business_days SET
                    expected_cash = :expected_cash,
                    actual_cash = :actual_cash,
                    card_settlement_amount = :card_settlement_amount,
                    shortage_amount = :shortage_amount,
                    overage_amount = :overage_amount,
                    retained_cash = :retained_cash,
                    cash_to_deposit = :cash_to_deposit,
                    status = 'closed',
                    closed_by = :closed_by,
                    closed_at = CURRENT_TIMESTAMP,
                    notes = :notes,
                    cash_denominations = :cash_denominations,
                    split_transfers = :split_transfers
                WHERE id = :id
            ");

            $stmtClose->execute([
                ':expected_cash'          => $expectedCash,
                ':actual_cash'            => $actualCountedCash,
                ':card_settlement_amount' => $cardSettlementAmount,
                ':shortage_amount'        => $shortageAmount,
                ':overage_amount'         => $overageAmount,
                ':retained_cash'          => $retainedCash,
                ':cash_to_deposit'        => $cashToDeposit,
                ':closed_by'              => $userId,
                ':notes'                  => trim($notes ?? '') ?: null,
                ':cash_denominations'     => $denomJson,
                ':split_transfers'        => $splitJson,
                ':id'                     => $businessDayId
            ]);

            // 2. Record Shortage / Overage movement if any
            if ($shortageAmount > 0.00) {
                self::recordDrawerMovement([
                    'business_day_id' => $businessDayId,
                    'branch_id'       => (int)$bday['branch_id'],
                    'movement_type'   => 'shortage_adjustment',
                    'source_type'     => 'business_days',
                    'source_id'       => $businessDayId,
                    'amount'          => $shortageAmount,
                    'direction'       => 'out',
                    'reason'          => 'Day Closing Cash Shortage Adjustment',
                    'user_id'         => $userId
                ]);

                // Accounting: Dr Cash Shortage / Overage (5040), Cr Cash in Hand (1010)
                $shortageAccId = Accounting::getAccountIdByCode(Accounting::CODE_CASH_SHORTAGE_OVERAGE);
                $cashAccId     = Accounting::getAccountIdByCode(Accounting::CODE_CASH);

                $lines = [
                    ['account_id' => $shortageAccId, 'debit' => $shortageAmount, 'credit' => 0.00, 'memo' => "Day Closing Cash Shortage"],
                    ['account_id' => $cashAccId, 'debit' => 0.00, 'credit' => $shortageAmount, 'memo' => "Cash Drawer Shortage Deduction"]
                ];

                Accounting::postJournalEntry([
                    'branch_id'   => (int)$bday['branch_id'],
                    'entry_date'  => date('Y-m-d'),
                    'source_type' => 'manual',
                    'source_id'   => $businessDayId,
                    'description' => "Day Closing Cash Shortage Adjustment (Date: {$bday['business_date']})",
                    'created_by'  => $userId
                ], $lines);
            }

            if ($overageAmount > 0.00) {
                self::recordDrawerMovement([
                    'business_day_id' => $businessDayId,
                    'branch_id'       => (int)$bday['branch_id'],
                    'movement_type'   => 'overage_adjustment',
                    'source_type'     => 'business_days',
                    'source_id'       => $businessDayId,
                    'amount'          => $overageAmount,
                    'direction'       => 'in',
                    'reason'          => 'Day Closing Cash Overage Adjustment',
                    'user_id'         => $userId
                ]);

                // Accounting: Dr Cash in Hand (1010), Cr Cash Shortage / Overage (5040)
                $cashAccId     = Accounting::getAccountIdByCode(Accounting::CODE_CASH);
                $shortageAccId = Accounting::getAccountIdByCode(Accounting::CODE_CASH_SHORTAGE_OVERAGE);

                $lines = [
                    ['account_id' => $cashAccId, 'debit' => $overageAmount, 'credit' => 0.00, 'memo' => "Day Closing Cash Overage"],
                    ['account_id' => $shortageAccId, 'debit' => 0.00, 'credit' => $overageAmount, 'memo' => "Cash Drawer Overage Addition"]
                ];

                Accounting::postJournalEntry([
                    'branch_id'   => (int)$bday['branch_id'],
                    'entry_date'  => date('Y-m-d'),
                    'source_type' => 'manual',
                    'source_id'   => $businessDayId,
                    'description' => "Day Closing Cash Overage Adjustment (Date: {$bday['business_date']})",
                    'created_by'  => $userId
                ], $lines);
            }

            // Accounting: Cash Transfer from Drawer (1012) to Selected Split Accounts or Main Safe (1010)
            if ($cashToDeposit > 0.00) {
                $mainSafeAccId = Accounting::getAccountIdByCode(Accounting::CODE_CASH_IN_HAND);
                $drawerAccId   = Accounting::getAccountIdByCode(Accounting::CODE_CASH_DRAWER) ?: $mainSafeAccId;
                
                $parsedSplits = [];
                if (!empty($splitTransfers)) {
                    $parsedSplits = is_string($splitTransfers) ? json_decode($splitTransfers, true) : $splitTransfers;
                }

                if (!empty($parsedSplits) && is_array($parsedSplits)) {
                    $journalLines = [];
                    $totalSplitAllocated = 0.00;

                    // Cr Cash Drawer (1012) for the full surplus cash
                    $journalLines[] = [
                        'account_id' => $drawerAccId,
                        'debit'      => 0.00,
                        'credit'     => $cashToDeposit,
                        'memo'       => "Day End Cash Drawer Transfer (Split Deposit)"
                    ];

                    require_once __DIR__ . '/Banking.php';

                    foreach ($parsedSplits as $st) {
                        $stAmount = round((float)($st['amount'] ?? 0), 2);
                        if ($stAmount <= 0) continue;
                        $totalSplitAllocated += $stAmount;
                        $stType = $st['type'] ?? 'safe';
                        $destAccId = null;
                        $memo = trim($st['notes'] ?? '');

                        if ($stType === 'safe' || $stType === 'cash') {
                            if (!empty($st['account_id'])) {
                                $destAccId = (int)$st['account_id'];
                                $targetAcc = Database::fetchOne("SELECT account_code, account_name FROM chart_of_accounts WHERE id = :id", [':id' => $destAccId]);
                                $accLabel = $targetAcc ? "{$targetAcc['account_name']} ({$targetAcc['account_code']})" : "Safe Account";
                                $memo = $memo ?: "Day End Transfer to {$accLabel}";
                            } else {
                                $destAccId = Accounting::getAccountIdByCode(Accounting::CODE_CASH_IN_HAND);
                                $memo = $memo ?: "Day End Transfer to Main Safe (1010)";
                            }
                        } elseif ($stType === 'petty_cash') {
                            $destAccId = Accounting::getAccountIdByCode(Accounting::CODE_PETTY_CASH);
                            $memo = $memo ?: "Day End Transfer to Petty Cash Account (1015)";
                        } elseif ($stType === 'bank') {
                            $bankAccId = (int)($st['bank_account_id'] ?? 0);
                            $bankAcc = Database::fetchOne("SELECT * FROM bank_accounts WHERE id = :id AND deleted_at IS NULL", [':id' => $bankAccId]);
                            if ($bankAcc) {
                                $destAccId = !empty($bankAcc['chart_account_id']) ? (int)$bankAcc['chart_account_id'] : Accounting::getAccountIdByCode(Accounting::CODE_BANK);
                                $memo = $memo ?: "Day End Deposit to {$bankAcc['bank_name']} ({$bankAcc['account_number']})";

                                // Insert into bank_deposits for bank reconciliation & register
                                $depNo = Banking::generateNumber('DEP', 'bank_deposits');
                                $stmtBDep = $db->prepare("
                                    INSERT INTO bank_deposits (
                                        deposit_no, branch_id, bank_account_id, deposit_date, amount,
                                        source_type, business_day_id, reference_no, notes, created_by
                                    ) VALUES (
                                        :dep_no, :branch_id, :bank_id, CURRENT_DATE, :amount,
                                        'closed_day', :bday_id, :ref_no, :notes, :user_id
                                    )
                                ");
                                $stmtBDep->execute([
                                    ':dep_no'    => $depNo,
                                    ':branch_id' => (int)$bday['branch_id'],
                                    ':bank_id'   => $bankAccId,
                                    ':amount'    => $stAmount,
                                    ':bday_id'   => $businessDayId,
                                    ':ref_no'    => $depNo,
                                    ':notes'     => $memo,
                                    ':user_id'   => $userId
                                ]);
                            } else {
                                $destAccId = Accounting::getAccountIdByCode(Accounting::CODE_BANK);
                                $memo = $memo ?: "Day End Deposit to Bank Account";
                            }
                        }

                        if ($destAccId) {
                            $journalLines[] = [
                                'account_id' => $destAccId,
                                'debit'      => $stAmount,
                                'credit'     => 0.00,
                                'memo'       => $memo
                            ];
                        }
                    }

                    if (abs($totalSplitAllocated - $cashToDeposit) > 0.01) {
                        $db->rollBack();
                        return [
                            'success' => false,
                            'message' => "Split transfer allocation total (LKR " . number_format($totalSplitAllocated, 2) . ") does not equal surplus cash to transfer (LKR " . number_format($cashToDeposit, 2) . ")."
                        ];
                    }

                    Accounting::postJournalEntry([
                        'branch_id'   => (int)$bday['branch_id'],
                        'entry_date'  => date('Y-m-d'),
                        'source_type' => 'manual',
                        'source_id'   => $businessDayId,
                        'description' => "Day Closing Cash Drawer Split Transfer (Date: {$bday['business_date']})",
                        'created_by'  => $userId
                    ], $journalLines);

                } else {
                    // Fallback to single account (Main Safe 1010)
                    if ($mainSafeAccId && $drawerAccId && $mainSafeAccId !== $drawerAccId) {
                        $lines = [
                            ['account_id' => $mainSafeAccId, 'debit' => $cashToDeposit, 'credit' => 0.00, 'memo' => "Day End Safe Deposit from Cash Drawer"],
                            ['account_id' => $drawerAccId,   'debit' => 0.00, 'credit' => $cashToDeposit, 'memo' => "Day End Cash Transfer to Main Safe"]
                        ];
                        Accounting::postJournalEntry([
                            'branch_id'   => (int)$bday['branch_id'],
                            'entry_date'  => date('Y-m-d'),
                            'source_type' => 'manual',
                            'source_id'   => $businessDayId,
                            'description' => "Day Closing Cash Drawer Deposit to Main Safe (Date: {$bday['business_date']})",
                            'created_by'  => $userId
                        ], $lines);
                    }
                }
            }

            $db->commit();

            Audit::log('BUSINESS_DAY_CLOSED', 'cash_drawer', (string)$businessDayId, null, [
                'branch_id'        => $bday['branch_id'],
                'expected_cash'    => $expectedCash,
                'actual_cash'      => $actualCountedCash,
                'shortage_amount'  => $shortageAmount,
                'overage_amount'   => $overageAmount,
                'retained_cash'    => $retainedCash,
                'cash_to_deposit'  => $cashToDeposit
            ]);

            return [
                'success'         => true,
                'business_day_id' => $businessDayId,
                'expected_cash'   => $expectedCash,
                'actual_cash'     => $actualCountedCash,
                'shortage_amount' => $shortageAmount,
                'overage_amount'  => $overageAmount,
                'cash_to_deposit' => $cashToDeposit,
                'message'         => "Business Day (Date: {$bday['business_date']}) closed successfully. " . 
                                     ($shortageAmount > 0 ? "Shortage: LKR " . number_format($shortageAmount, 2) . ". " : "") .
                                     ($overageAmount > 0 ? "Overage: LKR " . number_format($overageAmount, 2) . ". " : "") .
                                     "Main Cash Account Transfer: LKR " . number_format($cashToDeposit, 2) . "."
            ];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to close business day #{$businessDayId}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Failed to close business day: ' . $e->getMessage()];
        }
    }

    /**
     * Get Closed Business Days Log with Pagination, Multi-Column Search & Presentation Masking
     */
    public static function getClosedDaysList(int $branchId, int $page = 1, int $limit = 25, array $filters = []): array
    {
        try {
            $allowedLimits = [25, 50, 75, 100, 500];
            if (!in_array($limit, $allowedLimits, true)) {
                $limit = 25;
            }
            $page = max(1, $page);

            $where = ["bd.status = 'closed'"];
            $params = [];

            if (empty($filters['all_branches'])) {
                $where[] = "bd.branch_id = :branch_id";
                $params[':branch_id'] = $branchId;
            }

            if (!empty($filters['date_from'])) {
                $where[] = "bd.business_date >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }
            if (!empty($filters['date_to'])) {
                $where[] = "bd.business_date <= :date_to";
                $params[':date_to'] = $filters['date_to'];
            }

            if (!empty($filters['search'])) {
                $search = trim($filters['search']);
                $cleanNum = preg_replace('/[^0-9.]/', '', $search);
                $cleanInv = ltrim($search, '#');
                $like = '%' . $search . '%';
                $likeInv = '%' . $cleanInv . '%';
                $likeNum = !empty($cleanNum) ? '%' . $cleanNum . '%' : '';

                $where[] = "(
                    bd.id LIKE :s1
                    OR bd.id LIKE :s2
                    OR bd.business_date LIKE :s3
                    OR u1.username LIKE :s4
                    OR u1.full_name LIKE :s5
                    OR u2.username LIKE :s6
                    OR u2.full_name LIKE :s7
                    OR bd.notes LIKE :s8
                    OR CAST(bd.opening_cash AS CHAR) LIKE :s9
                    OR CONCAT('LKR ', FORMAT(bd.opening_cash, 2)) LIKE :s10
                    OR FORMAT(bd.opening_cash, 2) LIKE :s11
                    OR CAST(bd.expected_cash AS CHAR) LIKE :s12
                    OR FORMAT(bd.expected_cash, 2) LIKE :s13
                    OR CAST(bd.actual_cash AS CHAR) LIKE :s14
                    OR FORMAT(bd.actual_cash, 2) LIKE :s15
                    OR CAST(bd.cash_to_deposit AS CHAR) LIKE :s16
                    OR FORMAT(bd.cash_to_deposit, 2) LIKE :s17
                    OR CAST(bd.shortage_amount AS CHAR) LIKE :s18
                    OR CAST(bd.overage_amount AS CHAR) LIKE :s19
                )";
                $params[':s1']  = $like;
                $params[':s2']  = $likeInv;
                $params[':s3']  = $like;
                $params[':s4']  = $like;
                $params[':s5']  = $like;
                $params[':s6']  = $like;
                $params[':s7']  = $like;
                $params[':s8']  = $like;
                $params[':s9']  = $likeNum ?: $like;
                $params[':s10'] = $like;
                $params[':s11'] = $like;
                $params[':s12'] = $likeNum ?: $like;
                $params[':s13'] = $like;
                $params[':s14'] = $likeNum ?: $like;
                $params[':s15'] = $like;
                $params[':s16'] = $likeNum ?: $like;
                $params[':s17'] = $like;
                $params[':s18'] = $likeNum ?: $like;
                $params[':s19'] = $likeNum ?: $like;
            }

            $whereSql = implode(' AND ', $where);

            $countSql = "
                SELECT COUNT(*) AS total
                FROM business_days bd
                JOIN users u1 ON bd.opened_by = u1.id
                LEFT JOIN users u2 ON bd.closed_by = u2.id
                WHERE {$whereSql}
            ";
            $countRow = Database::fetchOne($countSql, $params);
            $totalItems = (int)($countRow['total'] ?? 0);
            $offset = ($page - 1) * $limit;

            $itemsSql = "
                SELECT 
                    bd.*,
                    u1.username AS opener_name,
                    u1.full_name AS opener_full_name,
                    u2.username AS closer_name,
                    u2.full_name AS closer_full_name
                FROM business_days bd
                JOIN users u1 ON bd.opened_by = u1.id
                LEFT JOIN users u2 ON bd.closed_by = u2.id
                WHERE {$whereSql}
                ORDER BY bd.id DESC
                LIMIT {$limit} OFFSET {$offset}
            ";
            $items = Database::fetchAll($itemsSql, $params);

            if (MaskingHelper::isSpecialUser()) {
                foreach ($items as &$item) {
                    foreach (['opening_cash', 'expected_cash', 'actual_cash', 'retained_cash', 'cash_to_deposit', 'shortage_amount', 'overage_amount'] as $field) {
                        if (isset($item[$field])) {
                            $item[$field] = MaskingHelper::maskValue((float)$item[$field]);
                        }
                    }
                }
                unset($item);
            }

            return [
                'items'      => $items,
                'totalItems' => $totalItems,
                'page'       => $page,
                'limit'      => $limit,
                'totalPages' => max(1, (int)ceil($totalItems / $limit))
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to fetch closed days list: " . $e->getMessage(), $e);
            return ['items' => [], 'totalItems' => 0, 'page' => 1, 'limit' => $limit, 'totalPages' => 1];
        }
    }


    /**
     * Get detailed Movement History for a Business Day
     */
    public static function getDayMovements(int $businessDayId): array
    {
        try {
            return Database::fetchAll("
                SELECT cdm.*, u.username AS user_name
                FROM cash_drawer_movements cdm
                JOIN users u ON cdm.user_id = u.id
                WHERE cdm.business_day_id = :id
                ORDER BY cdm.id ASC
            ", [':id' => $businessDayId]);
        } catch (Throwable $e) {
            Logger::error("Failed to fetch day movements for #{$businessDayId}: " . $e->getMessage(), $e);
            return [];
        }
    }

    /**
     * Get Daily POS Sales & Payment Breakdown Summary for a Business Day
     */
    public static function getDaySalesSummary(int $businessDayId): array
    {
        try {
            $bday = Database::fetchOne("
                SELECT * FROM business_days WHERE id = :id
            ", [':id' => $businessDayId]);

            if (!$bday) {
                return [
                    'total_sales_count' => 0,
                    'gross_sales'       => 0.00,
                    'total_discount'    => 0.00,
                    'net_sales'         => 0.00,
                    'cash_sales'        => 0.00,
                    'card_sales'        => 0.00,
                    'credit_sales'      => 0.00,
                    'bank_sales'        => 0.00
                ];
            }

            $branchId = (int)$bday['branch_id'];
            $openedAt = $bday['opened_at'];
            $closedAt = !empty($bday['closed_at']) ? $bday['closed_at'] : date('Y-m-d H:i:s');

            // Query sales totals
            $salesRow = Database::fetchOne("
                SELECT 
                    COUNT(*) AS sales_count,
                    COALESCE(SUM(subtotal), 0.00) AS gross_sales,
                    COALESCE(SUM(promo_discount_amount), 0.00) AS promo_discount,
                    COALESCE(SUM(discount_amount), 0.00) AS manual_discount,
                    (COALESCE(SUM(promo_discount_amount), 0.00) + COALESCE(SUM(discount_amount), 0.00)) AS total_discount,
                    COALESCE(SUM(total_amount), 0.00) AS net_sales
                FROM sales
                WHERE branch_id = :branch_id 
                  AND status = 'completed'
                  AND (created_at >= :opened_at AND created_at <= :closed_at)
            ", [
                ':branch_id' => $branchId,
                ':opened_at' => $openedAt,
                ':closed_at' => $closedAt
            ]);

            // Query payment methods breakdown
            $pmRows = Database::fetchAll("
                SELECT p.payment_method, COALESCE(SUM(p.amount), 0.00) AS total_amt
                FROM payments p
                JOIN sales s ON p.sale_id = s.id
                WHERE s.branch_id = :branch_id 
                  AND s.status = 'completed'
                  AND (s.created_at >= :opened_at AND s.created_at <= :closed_at)
                GROUP BY p.payment_method
            ", [
                ':branch_id' => $branchId,
                ':opened_at' => $openedAt,
                ':closed_at' => $closedAt
            ]);

            $cashSales   = 0.00;
            $cardSales   = 0.00;
            $creditSales = 0.00;
            $bankSales   = 0.00;

            foreach ($pmRows as $pm) {
                $method = strtolower($pm['payment_method']);
                $amt    = (float)$pm['total_amt'];

                if ($method === 'cash') {
                    $cashSales += $amt;
                } elseif ($method === 'card') {
                    $cardSales += $amt;
                } elseif ($method === 'credit') {
                    $creditSales += $amt;
                } elseif ($method === 'bank' || $method === 'cheque') {
                    $bankSales += $amt;
                }
            }

            // Query COGS & items sold totals
            $cogsRow = Database::fetchOne("
                SELECT 
                    COALESCE(SUM(COALESCE(si.line_cost, si.quantity * si.unit_cost, 0.00)), 0.00) AS total_cogs,
                    COALESCE(SUM(si.quantity), 0.00) AS total_items_sold
                FROM sale_items si
                INNER JOIN sales s ON si.sale_id = s.id
                WHERE s.branch_id = :branch_id 
                  AND s.status = 'completed'
                  AND (s.created_at >= :opened_at AND s.created_at <= :closed_at)
            ", [
                ':branch_id' => $branchId,
                ':opened_at' => $openedAt,
                ':closed_at' => $closedAt
            ]);

            $netSales = round((float)($salesRow['net_sales'] ?? 0), 2);
            $totalCogs = round((float)($cogsRow['total_cogs'] ?? 0), 2);
            $grossProfit = round($netSales - $totalCogs, 2);
            $grossMarginPct = $netSales > 0 ? round(($grossProfit / $netSales) * 100, 2) : 0.00;

            return [
                'total_sales_count' => (int)($salesRow['sales_count'] ?? 0),
                'total_items_sold'  => (float)($cogsRow['total_items_sold'] ?? 0),
                'gross_sales'       => round((float)($salesRow['gross_sales'] ?? 0), 2),
                'promo_discount'    => round((float)($salesRow['promo_discount'] ?? 0), 2),
                'manual_discount'   => round((float)($salesRow['manual_discount'] ?? 0), 2),
                'total_discount'    => round((float)($salesRow['total_discount'] ?? 0), 2),
                'net_sales'         => $netSales,
                'total_cogs'        => $totalCogs,
                'gross_profit'      => $grossProfit,
                'gross_margin_pct'  => $grossMarginPct,
                'cash_sales'        => round($cashSales, 2),
                'card_sales'        => round($cardSales, 2),
                'credit_sales'      => round($creditSales, 2),
                'bank_sales'        => round($bankSales, 2)
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to fetch day sales summary for BDay #{$businessDayId}: " . $e->getMessage(), $e);
            return [
                'total_sales_count' => 0,
                'total_items_sold'  => 0.00,
                'gross_sales'       => 0.00,
                'total_discount'    => 0.00,
                'net_sales'         => 0.00,
                'total_cogs'        => 0.00,
                'gross_profit'      => 0.00,
                'gross_margin_pct'  => 0.00,
                'cash_sales'        => 0.00,
                'card_sales'        => 0.00,
                'credit_sales'      => 0.00,
                'bank_sales'        => 0.00
            ];
        }
    }

    /**
     * Get Complete Day End Financial & Transaction Summary
     * Captures sales, COGS, gross profit, payments, advance payments, collections, expenses, and drawer reconciliation.
     */
    public static function getDayComprehensiveSummary(int $businessDayId): array
    {
        try {
            $bday = Database::fetchOne("
                SELECT bd.*, b.name AS branch_name, b.code AS branch_code,
                       u1.full_name AS opener_name, u2.full_name AS closer_name
                FROM business_days bd
                JOIN branches b ON bd.branch_id = b.id
                JOIN users u1 ON bd.opened_by = u1.id
                LEFT JOIN users u2 ON bd.closed_by = u2.id
                WHERE bd.id = :id
            ", [':id' => $businessDayId]);

            if (!$bday) {
                return ['success' => false, 'message' => 'Business day record not found.'];
            }

            $branchId = (int)$bday['branch_id'];
            $openedAt = $bday['opened_at'];
            $closedAt = !empty($bday['closed_at']) ? $bday['closed_at'] : date('Y-m-d H:i:s');

            // 1. Sales & COGS & Gross Profit
            $salesSummary = self::getDaySalesSummary($businessDayId);

            // 2. Customer Credit Collections
            $custCollections = Database::fetchAll("
                SELECT 
                    ccl.id, ccl.customer_id, ccl.reference_no, ccl.amount_paid, ccl.notes, ccl.created_at,
                    c.name AS customer_name, c.customer_code
                FROM customer_credit_ledger ccl
                INNER JOIN customers c ON ccl.customer_id = c.id
                WHERE ccl.branch_id = :branch_id
                  AND ccl.transaction_type = 'payment'
                  AND ccl.amount_paid > 0
                  AND (
                      (ccl.created_at >= :opened_at AND ccl.created_at <= :closed_at)
                      OR ccl.id IN (
                          SELECT source_id FROM cash_drawer_movements 
                          WHERE business_day_id = :bday_id_cc AND movement_type = 'customer_collection' AND source_type = 'customer_credit_ledger'
                      )
                  )
                ORDER BY ccl.id ASC
            ", [
                ':branch_id'   => $branchId,
                ':opened_at'   => $openedAt,
                ':closed_at'   => $closedAt,
                ':bday_id_cc'  => $businessDayId
            ]);

            $totalCustomerCollections = 0.00;
            foreach ($custCollections as $cc) {
                $totalCustomerCollections += (float)$cc['amount_paid'];
            }

            // 3. Supplier Payments & Advances
            $supplierPayments = Database::fetchAll("
                SELECT 
                    sp.id, sp.payment_no, sp.supplier_id, sp.payment_date, sp.payment_method, sp.total_amount, sp.reference_no, sp.notes, sp.created_at,
                    sup.name AS supplier_name
                FROM supplier_payments sp
                INNER JOIN suppliers sup ON sp.supplier_id = sup.id
                WHERE sp.branch_id = :branch_id
                  AND (
                      (sp.created_at >= :opened_at AND sp.created_at <= :closed_at)
                      OR sp.id IN (
                          SELECT source_id FROM cash_drawer_movements 
                          WHERE business_day_id = :bday_id_sp AND movement_type = 'supplier_payment'
                      )
                  )
                ORDER BY sp.id ASC
            ", [
                ':branch_id'  => $branchId,
                ':opened_at'  => $openedAt,
                ':closed_at'  => $closedAt,
                ':bday_id_sp' => $businessDayId
            ]);

            $totalSupplierPayments = 0.00;
            $cashSupplierPayments  = 0.00;
            $bankSupplierPayments  = 0.00;
            foreach ($supplierPayments as $sp) {
                $amt = (float)$sp['total_amount'];
                $totalSupplierPayments += $amt;
                if (strtolower($sp['payment_method']) === 'cash') {
                    $cashSupplierPayments += $amt;
                } else {
                    $bankSupplierPayments += $amt;
                }
            }

            // 4. Employee Advance Payments
            $employeeAdvances = Database::fetchAll("
                SELECT 
                    ea.id, ea.advance_no, ea.employee_id, ea.amount, ea.disbursement_date, ea.payment_method, ea.notes, ea.created_at,
                    e.full_name AS employee_name, e.employee_code
                FROM employee_advances ea
                INNER JOIN employees e ON ea.employee_id = e.id
                WHERE ea.branch_id = :branch_id
                  AND ea.status != 'cancelled'
                  AND (
                      (ea.created_at >= :opened_at AND ea.created_at <= :closed_at)
                      OR ea.id IN (
                          SELECT source_id FROM cash_drawer_movements 
                          WHERE business_day_id = :bday_id_ea AND movement_type = 'employee_advance'
                      )
                  )
                ORDER BY ea.id ASC
            ", [
                ':branch_id'  => $branchId,
                ':opened_at'  => $openedAt,
                ':closed_at'  => $closedAt,
                ':bday_id_ea' => $businessDayId
            ]);

            $totalEmployeeAdvances = 0.00;
            $cashEmployeeAdvances  = 0.00;
            $bankEmployeeAdvances  = 0.00;
            foreach ($employeeAdvances as $ea) {
                $amt = (float)$ea['amount'];
                $totalEmployeeAdvances += $amt;
                $mLow = strtolower($ea['payment_method'] ?? '');
                if (in_array($mLow, ['cash', 'cash drawer', 'cash in hand', 'petty cash'], true) || strpos($mLow, 'cash') !== false || strpos($mLow, 'drawer') !== false) {
                    $cashEmployeeAdvances += $amt;
                } else {
                    $bankEmployeeAdvances += $amt;
                }
            }

            // 5. Petty Cash Vouchers / Daily Operating Expenses
            $pettyCashVouchers = Database::fetchAll("
                SELECT 
                    pcv.id, pcv.voucher_no, pcv.amount, pcv.voucher_date, pcv.payee, pcv.description, pcv.reference_no, pcv.created_at,
                    COALESCE(ec.name, 'Operating Expense') AS category_name
                FROM petty_cash_vouchers pcv
                LEFT JOIN expense_categories ec ON pcv.category_id = ec.id
                WHERE pcv.branch_id = :branch_id
                  AND pcv.status = 'posted'
                  AND (
                      (pcv.created_at >= :opened_at AND pcv.created_at <= :closed_at)
                      OR pcv.id IN (
                          SELECT source_id FROM cash_drawer_movements 
                          WHERE business_day_id = :bday_id_pc AND movement_type IN ('petty_cash_reimbursement', 'petty_cash_expense')
                      )
                  )
                ORDER BY pcv.id ASC
            ", [
                ':branch_id'  => $branchId,
                ':opened_at'  => $openedAt,
                ':closed_at'  => $closedAt,
                ':bday_id_pc' => $businessDayId
            ]);

            $totalPettyCashExpenses = 0.00;
            foreach ($pettyCashVouchers as $pcv) {
                $totalPettyCashExpenses += (float)$pcv['amount'];
            }

            // 6. Payroll Salary Disbursements
            $payrollPayments = Database::fetchAll("
                SELECT 
                    pp.id, pp.payment_no, pp.employee_id, pp.amount, pp.payment_method, pp.payment_date, pp.notes, pp.created_at,
                    e.full_name AS employee_name, e.employee_code,
                    COALESCE(p.period_name, pp.payment_no) AS period_name
                FROM payroll_payments pp
                INNER JOIN employees e ON pp.employee_id = e.id
                LEFT JOIN payroll_entries pe ON pp.payroll_entry_id = pe.id
                LEFT JOIN payroll_periods p ON pe.payroll_period_id = p.id
                WHERE pp.branch_id = :branch_id
                  AND (
                      (pp.created_at >= :opened_at AND pp.created_at <= :closed_at)
                      OR pp.id IN (
                          SELECT source_id FROM cash_drawer_movements 
                          WHERE business_day_id = :bday_id_pp AND movement_type = 'salary_payment'
                      )
                  )
                ORDER BY pp.id ASC
            ", [
                ':branch_id'  => $branchId,
                ':opened_at'  => $openedAt,
                ':closed_at'  => $closedAt,
                ':bday_id_pp' => $businessDayId
            ]);

            $totalPayrollPayments = 0.00;
            $cashPayrollPayments  = 0.00;
            $bankPayrollPayments  = 0.00;
            foreach ($payrollPayments as $pp) {
                $amt = (float)$pp['amount'];
                $totalPayrollPayments += $amt;
                $mLow = strtolower($pp['payment_method'] ?? '');
                if (in_array($mLow, ['cash', 'cash drawer', 'cash in hand'], true) || strpos($mLow, 'cash') !== false || strpos($mLow, 'drawer') !== false) {
                    $cashPayrollPayments += $amt;
                } else {
                    $bankPayrollPayments += $amt;
                }
            }

            // 7. Customer Refunds / Sales Returns
            $refunds = Database::fetchAll("
                SELECT 
                    r.id, r.refund_method, r.amount, r.reference_no, r.created_at,
                    COALESCE(c.name, 'Customer Refund') AS customer_name
                FROM refunds r
                LEFT JOIN customers c ON r.customer_id = c.id
                WHERE r.branch_id = :branch_id
                  AND (
                      (r.created_at >= :opened_at AND r.created_at <= :closed_at)
                      OR r.id IN (
                          SELECT source_id FROM cash_drawer_movements 
                          WHERE business_day_id = :bday_id_rf AND movement_type = 'cash_refund'
                      )
                  )
                ORDER BY r.id ASC
            ", [
                ':branch_id'  => $branchId,
                ':opened_at'  => $openedAt,
                ':closed_at'  => $closedAt,
                ':bday_id_rf' => $businessDayId
            ]);

            $totalRefunds = 0.00;
            $cashRefunds  = 0.00;
            foreach ($refunds as $rf) {
                $amt = (float)$rf['amount'];
                $totalRefunds += $amt;
                if (strtolower($rf['refund_method']) === 'cash') {
                    $cashRefunds += $amt;
                }
            }

            // 8. Manual Cash In & Out Movements
            $manualMovements = Database::fetchAll("
                SELECT id, movement_type, amount, direction, reason, notes, created_at
                FROM cash_drawer_movements
                WHERE business_day_id = :bday_id
                  AND movement_type IN ('cash_in', 'cash_out')
                ORDER BY id ASC
            ", [':bday_id' => $businessDayId]);

            $totalManualCashIn  = 0.00;
            $totalManualCashOut = 0.00;
            foreach ($manualMovements as $mm) {
                $amt = (float)$mm['amount'];
                if ($mm['movement_type'] === 'cash_in' || strtolower($mm['direction']) === 'in') {
                    $totalManualCashIn += $amt;
                } else {
                    $totalManualCashOut += $amt;
                }
            }

            // 8b. Centralized Business Expenses
            $businessExpenses = Database::fetchAll("
                SELECT 
                    e.id, e.expense_no, e.amount, e.paid_amount, e.expense_date, e.payment_method, e.payee, e.description,
                    e.reference_no, e.invoice_ref, e.created_at, e.status,
                    COALESCE(ec.name, 'General Expense') AS category_name,
                    coa.account_code, coa.account_name
                FROM expenses e
                LEFT JOIN expense_categories ec ON e.category_id = ec.id
                LEFT JOIN chart_of_accounts coa ON e.account_id = coa.id
                WHERE e.branch_id = :branch_id
                  AND e.status != 'reversed'
                  AND e.deleted_at IS NULL
                  AND (
                      (e.created_at >= :opened_at AND e.created_at <= :closed_at)
                      OR e.business_day_id = :bday_id_exp
                      OR e.id IN (
                          SELECT source_id FROM cash_drawer_movements
                          WHERE business_day_id = :bday_id_exp_cdm AND source_type = 'expense'
                      )
                  )
                ORDER BY e.id ASC
            ", [
                ':branch_id'       => $branchId,
                ':opened_at'       => $openedAt,
                ':closed_at'       => $closedAt,
                ':bday_id_exp'     => $businessDayId,
                ':bday_id_exp_cdm' => $businessDayId
            ]);

            $totalBusinessExpenses = 0.00;
            $cashDrawerExpenses    = 0.00;
            $cashInHandExpenses    = 0.00;
            $pettyCashModExpenses  = 0.00;
            $bankExpenses          = 0.00;
            $unpaidExpenses        = 0.00;
            $categoryBreakdown     = [];

            foreach ($businessExpenses as $be) {
                $bAmt = (float)$be['amount'];
                $totalBusinessExpenses += $bAmt;
                $pm = $be['payment_method'];
                if ($pm === 'cash_drawer') {
                    $cashDrawerExpenses += $bAmt;
                } elseif ($pm === 'cash_in_hand') {
                    $cashInHandExpenses += $bAmt;
                } elseif ($pm === 'petty_cash') {
                    $pettyCashModExpenses += $bAmt;
                } elseif ($pm === 'bank') {
                    $bankExpenses += $bAmt;
                } elseif ($pm === 'unpaid') {
                    $unpaidExpenses += ($bAmt - (float)$be['paid_amount']);
                }

                $cName = $be['category_name'];
                $categoryBreakdown[$cName] = ($categoryBreakdown[$cName] ?? 0.00) + $bAmt;
            }

            // 9. Drawer summary for reconciliation
            $drawerSum = self::getDrawerSummary($businessDayId);
            $summaryData = $drawerSum['summary'] ?? [];

            $res = [
                'success'               => true,
                'business_day'          => $bday,
                'sales_summary'         => $salesSummary,
                'customer_collections'  => [
                    'total' => round($totalCustomerCollections, 2),
                    'items' => $custCollections
                ],
                'supplier_payments'     => [
                    'total'       => round($totalSupplierPayments, 2),
                    'cash_amount' => round($cashSupplierPayments, 2),
                    'bank_amount' => round($bankSupplierPayments, 2),
                    'items'       => $supplierPayments
                ],
                'employee_advances'     => [
                    'total'       => round($totalEmployeeAdvances, 2),
                    'cash_amount' => round($cashEmployeeAdvances, 2),
                    'bank_amount' => round($bankEmployeeAdvances, 2),
                    'items'       => $employeeAdvances
                ],
                'petty_cash_expenses'   => [
                    'total' => round($totalPettyCashExpenses, 2),
                    'items' => $pettyCashVouchers
                ],
                'general_expenses'      => [
                    'total'              => round($totalBusinessExpenses, 2),
                    'cash_drawer'        => round($cashDrawerExpenses, 2),
                    'cash_in_hand'       => round($cashInHandExpenses, 2),
                    'petty_cash'         => round($pettyCashModExpenses, 2),
                    'bank'               => round($bankExpenses, 2),
                    'unpaid'             => round($unpaidExpenses, 2),
                    'category_breakdown' => $categoryBreakdown,
                    'items'              => $businessExpenses
                ],
                'payroll_payments'      => [
                    'total'       => round($totalPayrollPayments, 2),
                    'cash_amount' => round($cashPayrollPayments, 2),
                    'bank_amount' => round($bankPayrollPayments, 2),
                    'items'       => $payrollPayments
                ],
                'refunds'               => [
                    'total'       => round($totalRefunds, 2),
                    'cash_amount' => round($cashRefunds, 2),
                    'items'       => $refunds
                ],
                'manual_movements'      => [
                    'total_in'  => round($totalManualCashIn, 2),
                    'total_out' => round($totalManualCashOut, 2),
                    'items'     => $manualMovements
                ],
                'drawer_summary'        => $summaryData
            ];

            if (MaskingHelper::isSpecialUser()) {
                if (isset($res['sales_summary']) && is_array($res['sales_summary'])) {
                    foreach ($res['sales_summary'] as $sk => $sval) {
                        if (is_numeric($sval)) {
                            $res['sales_summary'][$sk] = MaskingHelper::maskValue((float)$sval);
                        }
                    }
                }
                foreach (['customer_collections', 'supplier_payments', 'employee_advances', 'petty_cash_expenses', 'general_expenses', 'payroll_payments', 'refunds', 'manual_movements'] as $sec) {
                    if (isset($res[$sec]) && is_array($res[$sec])) {
                        foreach ($res[$sec] as $rk => $rval) {
                            if (is_numeric($rval)) {
                                $res[$sec][$rk] = MaskingHelper::maskValue((float)$rval);
                            }
                        }
                    }
                }
            }

            return $res;
        } catch (Throwable $e) {
            Logger::error("Failed to get comprehensive summary for BDay #{$businessDayId}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
