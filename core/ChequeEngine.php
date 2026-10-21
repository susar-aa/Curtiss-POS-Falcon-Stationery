<?php
/**
 * Curtiss POS - Cheque Management Engine (Stage 15)
 * Manages full cheque lifecycle (Pending -> Cleared / Bounced / Cancelled),
 * double-entry accounting (Supplier Payables -> Cheques Issued -> Real Bank Account),
 * GRN allocation reversals on bounce/cancellation, and audit tracking.
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Audit.php';
require_once __DIR__ . '/Accounting.php';
require_once __DIR__ . '/Banking.php';
require_once __DIR__ . '/MaskingHelper.php';

class ChequeEngine
{
    /**
     * Get list of supplier cheques with filtering
     */
    public static function getCheques(array $filters = []): array
    {
        try {
            $where = [];
            $params = [];

            // Soft-delete trash bin filter
            if (!empty($filters['view']) && $filters['view'] === 'deleted') {
                $where[] = "sc.deleted_at IS NOT NULL";
            } else {
                $where[] = "sc.deleted_at IS NULL";
            }

            if (!empty($filters['status']) && $filters['status'] !== 'all') {
                $where[] = "sc.status = :status";
                $params[':status'] = $filters['status'];
            }

            if (!empty($filters['supplier_id'])) {
                $where[] = "sc.supplier_id = :supplier_id";
                $params[':supplier_id'] = (int)$filters['supplier_id'];
            }

            if (!empty($filters['branch_id'])) {
                $where[] = "sc.branch_id = :branch_id";
                $params[':branch_id'] = (int)$filters['branch_id'];
            }

            if (!empty($filters['bank_account_id'])) {
                $where[] = "sc.bank_account_id = :bank_account_id";
                $params[':bank_account_id'] = (int)$filters['bank_account_id'];
            }

            if (!empty($filters['search'])) {
                $st = '%' . trim($filters['search']) . '%';
                $where[] = "(
                    sc.cheque_number LIKE :s1 OR 
                    sc.bank_name LIKE :s2 OR 
                    s.name LIKE :s3 OR 
                    sc.cheque_date LIKE :s4 OR 
                    ba.account_name LIKE :s5 OR 
                    ba.account_number LIKE :s6 OR 
                    sp.payment_no LIKE :s7 OR 
                    sc.status LIKE :s8 OR 
                    sc.bounced_reason LIKE :s9 OR 
                    sc.cancellation_reason LIKE :s10 OR 
                    CONCAT('#', sc.id) LIKE :s11 OR 
                    CAST(sc.amount AS CHAR) LIKE :s12 OR 
                    FORMAT(sc.amount, 2) LIKE :s13 OR 
                    CONCAT('LKR ', FORMAT(sc.amount, 2)) LIKE :s14
                )";
                for ($i = 1; $i <= 14; $i++) {
                    $params[":s{$i}"] = $st;
                }
            }

            if (!empty($filters['date_from'])) {
                $where[] = "sc.cheque_date >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }

            if (!empty($filters['date_to'])) {
                $where[] = "sc.cheque_date <= :date_to";
                $params[':date_to'] = $filters['date_to'];
            }

            $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

            $rows = Database::fetchAll("
                SELECT 
                    sc.*,
                    s.name AS supplier_name,
                    b.name AS branch_name,
                    sp.payment_no,
                    sp.payment_date,
                    ba.account_name AS bank_account_name,
                    ba.account_number AS bank_account_number,
                    uc.full_name AS created_by_name,
                    uclr.full_name AS cleared_by_name,
                    ubnc.full_name AS bounced_by_name,
                    ucan.full_name AS cancelled_by_name
                FROM supplier_cheques sc
                JOIN suppliers s ON sc.supplier_id = s.id
                JOIN branches b ON sc.branch_id = b.id
                LEFT JOIN supplier_payments sp ON sc.supplier_payment_id = sp.id
                LEFT JOIN bank_accounts ba ON sc.bank_account_id = ba.id
                LEFT JOIN users uc ON sc.created_by = uc.id
                LEFT JOIN users uclr ON sc.cleared_by = uclr.id
                LEFT JOIN users ubnc ON sc.bounced_by = ubnc.id
                LEFT JOIN users ucan ON sc.cancelled_by = ucan.id
                $whereSql
                ORDER BY sc.id DESC
            ", $params);

            if (Auth::isSpecialUser()) {
                foreach ($rows as &$r) {
                    $r['amount'] = MaskingHelper::maskValue((float)$r['amount']);
                }
            }

            return $rows;
        } catch (Throwable $e) {
            Logger::error("Failed to fetch supplier cheques: " . $e->getMessage(), $e);
            return [];
        }
    }

    /**
     * Get paginated list of supplier cheques
     */
    public static function getPaginatedCheques(array $filters = [], int $page = 1, int $limit = 25): array
    {
        try {
            // Whitelist page size limits
            $allowedLimits = [25, 50, 75, 100, 500];
            if (!in_array($limit, $allowedLimits, true)) {
                $limit = 25;
            }

            $where = [];
            $params = [];

            // Soft-delete trash bin filter
            if (!empty($filters['view']) && $filters['view'] === 'deleted') {
                $where[] = "sc.deleted_at IS NOT NULL";
            } else {
                $where[] = "sc.deleted_at IS NULL";
            }

            if (!empty($filters['status']) && $filters['status'] !== 'all') {
                $where[] = "sc.status = :status";
                $params[':status'] = $filters['status'];
            }

            if (!empty($filters['supplier_id'])) {
                $where[] = "sc.supplier_id = :supplier_id";
                $params[':supplier_id'] = (int)$filters['supplier_id'];
            }

            if (!empty($filters['branch_id'])) {
                $where[] = "sc.branch_id = :branch_id";
                $params[':branch_id'] = (int)$filters['branch_id'];
            }

            if (!empty($filters['bank_account_id'])) {
                $where[] = "sc.bank_account_id = :bank_account_id";
                $params[':bank_account_id'] = (int)$filters['bank_account_id'];
            }

            if (!empty($filters['search'])) {
                $st = '%' . trim($filters['search']) . '%';
                $where[] = "(
                    sc.cheque_number LIKE :s1 OR 
                    sc.bank_name LIKE :s2 OR 
                    s.name LIKE :s3 OR 
                    sc.cheque_date LIKE :s4 OR 
                    ba.account_name LIKE :s5 OR 
                    ba.account_number LIKE :s6 OR 
                    sp.payment_no LIKE :s7 OR 
                    sc.status LIKE :s8 OR 
                    sc.bounced_reason LIKE :s9 OR 
                    sc.cancellation_reason LIKE :s10 OR 
                    CONCAT('#', sc.id) LIKE :s11 OR 
                    CAST(sc.amount AS CHAR) LIKE :s12 OR 
                    FORMAT(sc.amount, 2) LIKE :s13 OR 
                    CONCAT('LKR ', FORMAT(sc.amount, 2)) LIKE :s14
                )";
                for ($i = 1; $i <= 14; $i++) {
                    $params[":s{$i}"] = $st;
                }
            }

            if (!empty($filters['date_from'])) {
                $where[] = "sc.cheque_date >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }

            if (!empty($filters['date_to'])) {
                $where[] = "sc.cheque_date <= :date_to";
                $params[':date_to'] = $filters['date_to'];
            }

            $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

            // Fetch total count for pagination with all joined tables in search conditions
            $countRow = Database::fetchOne("
                SELECT COUNT(*) AS c 
                FROM supplier_cheques sc 
                JOIN suppliers s ON sc.supplier_id = s.id 
                JOIN branches b ON sc.branch_id = b.id
                LEFT JOIN supplier_payments sp ON sc.supplier_payment_id = sp.id
                LEFT JOIN bank_accounts ba ON sc.bank_account_id = ba.id
                $whereSql
            ", $params);
            $totalItems = (int)($countRow['c'] ?? 0);
            $totalPages = max(1, (int)ceil($totalItems / $limit));
            $page = max(1, min($page, $totalPages));
            $offset = ($page - 1) * $limit;

            $items = Database::fetchAll("
                SELECT 
                    sc.id, sc.cheque_number, sc.cheque_date, sc.amount, sc.status, sc.bank_name, sc.created_at, sc.deleted_at,
                    sc.bounced_reason, sc.cancellation_reason,
                    s.name AS supplier_name,
                    b.name AS branch_name,
                    sp.payment_no,
                    sp.payment_date,
                    ba.account_name AS bank_account_name,
                    ba.account_number AS bank_account_number,
                    uc.full_name AS created_by_name,
                    uclr.full_name AS cleared_by_name,
                    ubnc.full_name AS bounced_by_name,
                    ucan.full_name AS cancelled_by_name
                FROM supplier_cheques sc
                JOIN suppliers s ON sc.supplier_id = s.id
                JOIN branches b ON sc.branch_id = b.id
                LEFT JOIN supplier_payments sp ON sc.supplier_payment_id = sp.id
                LEFT JOIN bank_accounts ba ON sc.bank_account_id = ba.id
                LEFT JOIN users uc ON sc.created_by = uc.id
                LEFT JOIN users uclr ON sc.cleared_by = uclr.id
                LEFT JOIN users ubnc ON sc.bounced_by = ubnc.id
                LEFT JOIN users ucan ON sc.cancelled_by = ucan.id
                $whereSql
                ORDER BY sc.id DESC
                LIMIT $limit OFFSET $offset
            ", $params);

            if (Auth::isSpecialUser()) {
                foreach ($items as &$item) {
                    $item['amount'] = MaskingHelper::maskValue((float)$item['amount']);
                }
            }

            return [
                'items'      => $items,
                'totalItems' => $totalItems,
                'totalPages' => $totalPages,
                'page'       => $page,
                'limit'      => $limit
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to fetch paginated supplier cheques: " . $e->getMessage(), $e);
            return [
                'items'      => [],
                'totalItems' => 0,
                'totalPages' => 1,
                'page'       => 1,
                'limit'      => $limit
            ];
        }
    }

    /**
     * Fast aggregated pending summary totals
     */
    public static function getPendingSummary(int $branchId, ?int $supplierId = null): array
    {
        try {
            $sql = "
                SELECT 
                    COUNT(*) AS count_pending,
                    COALESCE(SUM(amount), 0.00) AS total_pending
                FROM supplier_cheques
                WHERE deleted_at IS NULL AND status = 'pending' AND branch_id = :branch_id
            ";
            $params = [':branch_id' => $branchId];

            if ($supplierId !== null && $supplierId > 0) {
                $sql .= " AND supplier_id = :supplier_id";
                $params[':supplier_id'] = $supplierId;
            }

            $row = Database::fetchOne($sql, $params);
            $countPending = (int)($row['count_pending'] ?? 0);
            $totalPending = (float)($row['total_pending'] ?? 0.00);

            if (Auth::isSpecialUser()) {
                $totalPending = MaskingHelper::maskValue($totalPending);
            }

            return [
                'count_pending' => $countPending,
                'total_pending' => $totalPending
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to fetch pending cheque summary: " . $e->getMessage(), $e);
            return ['count_pending' => 0, 'total_pending' => 0.00];
        }
    }

    /**
     * Soft delete a cheque (Trash Bin)
     */
    public static function delete(int $id, int $userId): array
    {
        $db = Database::getInstance()->getConnection();
        try {
            $db->beginTransaction();

            $cheque = Database::fetchOne("
                SELECT * FROM supplier_cheques WHERE id = :id FOR UPDATE
            ", [':id' => $id]);

            if (!$cheque) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Cheque record not found.'];
            }

            if (!empty($cheque['deleted_at'])) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Cheque is already in Trash Bin.'];
            }

            $prevStatus = $cheque['status'];

            // If pending, reverse payment journal & GRN allocations before soft-deleting
            if ($cheque['status'] === 'pending') {
                $allocations = Database::fetchAll("
                    SELECT spg.*, g.actual_grn_total, g.paid_amount
                    FROM supplier_payment_grns spg
                    JOIN grns g ON spg.grn_id = g.id
                    WHERE spg.supplier_payment_id = :sp_id
                    FOR UPDATE
                ", [':sp_id' => $cheque['supplier_payment_id']]);

                foreach ($allocations as $alloc) {
                    $grnId    = (int)$alloc['grn_id'];
                    $allocAmt = (float)$alloc['allocated_amount'];
                    $curPaid  = (float)$alloc['paid_amount'];
                    $newPaid  = round(max(0.00, $curPaid - $allocAmt), 2);
                    $pStatus  = ($newPaid <= 0.001) ? 'unpaid' : 'partially_paid';

                    $db->prepare("
                        UPDATE grns SET paid_amount = :paid, payment_status = :status WHERE id = :id
                    ")->execute([':paid' => $newPaid, ':status' => $pStatus, ':id' => $grnId]);
                }

                $paymentJe = Database::fetchOne("
                    SELECT id FROM journal_entries 
                    WHERE source_type = 'supplier_payment' AND source_id = :sp_id AND posting_status = 'posted'
                ", [':sp_id' => $cheque['supplier_payment_id']]);

                if ($paymentJe) {
                    Accounting::reverseJournalEntry((int)$paymentJe['id'], "Cheque #{$cheque['cheque_number']} soft-deleted - payment reversal", $userId);
                }
            }

            $db->prepare("
                UPDATE supplier_cheques 
                SET deleted_at = NOW(), status = 'cancelled' 
                WHERE id = :id
            ")->execute([':id' => $id]);

            $db->commit();

            Audit::log('CHEQUE_DELETED', 'finance', (string)$id, ['status' => $prevStatus], [
                'cheque_number' => $cheque['cheque_number'],
                'amount'        => $cheque['amount']
            ]);

            return ['success' => true, 'message' => "Cheque #{$cheque['cheque_number']} moved to Trash Bin."];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to soft-delete cheque #{$id}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => "Soft-Delete Error: " . $e->getMessage()];
        }
    }

    /**
     * Restore a soft-deleted cheque from Trash Bin
     */
    public static function restore(int $id, int $userId): array
    {
        $db = Database::getInstance()->getConnection();
        try {
            $db->beginTransaction();

            $cheque = Database::fetchOne("
                SELECT * FROM supplier_cheques WHERE id = :id FOR UPDATE
            ", [':id' => $id]);

            if (!$cheque) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Cheque record not found.'];
            }

            if (empty($cheque['deleted_at'])) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Cheque is not in Trash Bin.'];
            }

            $db->prepare("
                UPDATE supplier_cheques 
                SET deleted_at = NULL, status = 'pending' 
                WHERE id = :id
            ")->execute([':id' => $id]);

            $db->commit();

            Audit::log('CHEQUE_RESTORED', 'finance', (string)$id, null, [
                'cheque_number' => $cheque['cheque_number'],
                'amount'        => $cheque['amount']
            ]);

            return ['success' => true, 'message' => "Cheque #{$cheque['cheque_number']} restored successfully."];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to restore cheque #{$id}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => "Restore Error: " . $e->getMessage()];
        }
    }

    /**
     * Get single cheque details by ID
     */
    public static function getCheque(int $id): ?array
    {
        try {
            $cheque = Database::fetchOne("
                SELECT 
                    sc.*,
                    s.name AS supplier_name,
                    b.name AS branch_name,
                    sp.payment_no,
                    sp.payment_date,
                    ba.account_name AS bank_account_name,
                    ba.account_number AS bank_account_number,
                    uc.full_name AS created_by_name,
                    uclr.full_name AS cleared_by_name,
                    ubnc.full_name AS bounced_by_name,
                    ucan.full_name AS cancelled_by_name
                FROM supplier_cheques sc
                JOIN suppliers s ON sc.supplier_id = s.id
                JOIN branches b ON sc.branch_id = b.id
                JOIN supplier_payments sp ON sc.supplier_payment_id = sp.id
                LEFT JOIN bank_accounts ba ON sc.bank_account_id = ba.id
                LEFT JOIN users uc ON sc.created_by = uc.id
                LEFT JOIN users uclr ON sc.cleared_by = uclr.id
                LEFT JOIN users ubnc ON sc.bounced_by = ubnc.id
                LEFT JOIN users ucan ON sc.cancelled_by = ucan.id
                WHERE sc.id = :id
            ", [':id' => $id]);

            if ($cheque) {
                if (Auth::isSpecialUser()) {
                    $cheque['amount'] = MaskingHelper::maskValue((float)$cheque['amount']);
                }

                // Fetch GRN allocations for this payment
                $allocations = Database::fetchAll("
                    SELECT spg.*, g.grn_number, g.actual_grn_total, g.paid_amount, g.payment_status
                    FROM supplier_payment_grns spg
                    JOIN grns g ON spg.grn_id = g.id
                    WHERE spg.supplier_payment_id = :sp_id
                ", [':sp_id' => $cheque['supplier_payment_id']]);

                if (Auth::isSpecialUser()) {
                    foreach ($allocations as &$al) {
                        $al['actual_grn_total'] = MaskingHelper::maskValue((float)$al['actual_grn_total']);
                        $al['allocated_amount'] = MaskingHelper::maskValue((float)$al['allocated_amount']);
                        $al['paid_amount'] = MaskingHelper::maskValue((float)$al['paid_amount']);
                    }
                }
                $cheque['allocations'] = $allocations;
            }

            return $cheque;
        } catch (Throwable $e) {
            Logger::error("Failed to fetch cheque details for #{$id}: " . $e->getMessage(), $e);
            return null;
        }
    }

    /**
     * Clear a pending cheque into a real bank account
     * Dr Cheques Issued (2025)
     * Cr Actual Bank Account (1021/1022 or account_id from bank_accounts)
     */
    public static function clearCheque(array $data, int $userId): array
    {
        $db = Database::getInstance()->getConnection();

        try {
            $db->beginTransaction();

            $chequeId      = (int)($data['cheque_id'] ?? 0);
            $bankAccountId = (int)($data['bank_account_id'] ?? 0);
            $clearedDate   = !empty($data['cleared_date']) ? trim($data['cleared_date']) : date('Y-m-d');
            $bankRef       = !empty($data['bank_ref']) ? trim($data['bank_ref']) : null;

            if ($chequeId <= 0) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Valid cheque selection is required.'];
            }

            $cheque = Database::fetchOne("
                SELECT sc.*, s.name AS supplier_name 
                FROM supplier_cheques sc 
                JOIN suppliers s ON sc.supplier_id = s.id 
                WHERE sc.id = :id FOR UPDATE
            ", [':id' => $chequeId]);

            if (!$cheque) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Cheque record not found.'];
            }

            if ($bankAccountId <= 0 && !empty($cheque['bank_account_id'])) {
                $bankAccountId = (int)$cheque['bank_account_id'];
            }

            if ($bankAccountId <= 0) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Valid real bank account is required.'];
            }

            if ($cheque['status'] !== 'pending') {
                $db->rollBack();
                return ['success' => false, 'message' => "Only pending cheques can be cleared. Current status: {$cheque['status']}."];
            }

            $bankAccount = Banking::getBankAccount($bankAccountId);
            if (!$bankAccount || $bankAccount['status'] !== 'active') {
                $db->rollBack();
                return ['success' => false, 'message' => 'Selected bank account is invalid or inactive.'];
            }

            $amount = round((float)$cheque['amount'], 2);
            $chequesIssuedAccId = Accounting::getAccountIdByCode(Accounting::CODE_CHEQUES_ISSUED); // 2025
            $bankCoaAccId       = (int)($bankAccount['account_id'] ?? Accounting::getAccountIdByCode(Accounting::CODE_BANK));

            // Post double-entry journal for clearing
            $header = [
                'branch_id'   => (int)$cheque['branch_id'],
                'entry_date'  => $clearedDate,
                'source_type' => 'cheque_clearing',
                'source_id'   => $chequeId,
                'description' => "Cheque #{$cheque['cheque_number']} cleared to {$bankAccount['bank_name']} ({$bankAccount['account_number']})",
                'created_by'  => $userId
            ];

            $lines = [
                [
                    'account_id' => $chequesIssuedAccId,
                    'debit'      => $amount,
                    'credit'     => 0.00,
                    'memo'       => "Cheque #{$cheque['cheque_number']} clearance debit"
                ],
                [
                    'account_id' => $bankCoaAccId,
                    'debit'      => 0.00,
                    'credit'     => $amount,
                    'memo'       => "Withdrawal from {$bankAccount['bank_name']} for Cheque #{$cheque['cheque_number']}"
                ]
            ];

            $jeRes = Accounting::postJournalEntry($header, $lines);
            if (!$jeRes['success']) {
                $db->rollBack();
                return ['success' => false, 'message' => "Cheque Clearing Journal Error: " . $jeRes['message']];
            }

            $clearingJournalId = $jeRes['journal_id'];

            // Update cheque status
            $db->prepare("
                UPDATE supplier_cheques 
                SET status = 'cleared', bank_account_id = :bank_acc, cleared_date = :cleared_date, bank_ref = :bank_ref, cleared_at = NOW(), cleared_by = :user, journal_entry_id = :je_id
                WHERE id = :id
            ")->execute([
                ':bank_acc'     => $bankAccountId,
                ':cleared_date' => $clearedDate,
                ':bank_ref'     => $bankRef,
                ':user'         => $userId,
                ':je_id'        => $clearingJournalId,
                ':id'           => $chequeId
            ]);

            $db->commit();

            Audit::log('CHEQUE_CLEARED', 'finance', (string)$chequeId, null, [
                'cheque_number' => $cheque['cheque_number'],
                'supplier_name' => $cheque['supplier_name'],
                'bank_account'  => $bankAccount['bank_name'],
                'amount'        => $amount
            ]);

            return [
                'success'           => true,
                'message'           => "Cheque #{$cheque['cheque_number']} of LKR " . number_format($amount, 2) . " cleared successfully.",
                'cheque_id'         => $chequeId,
                'journal_entry_id'  => $clearingJournalId
            ];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to clear cheque #{$data['cheque_id']}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => "Clear Cheque Error: " . $e->getMessage()];
        }
    }

    /**
     * Mark cheque as bounced / returned
     * Restores supplier payable & GRN allocations, and reverses accounting entries.
     */
    public static function bounceCheque(array $data, int $userId): array
    {
        $db = Database::getInstance()->getConnection();

        try {
            $db->beginTransaction();

            $chequeId      = (int)($data['cheque_id'] ?? 0);
            $bouncedReason = trim($data['bounced_reason'] ?? '');
            $bouncedDate   = !empty($data['bounced_date']) ? trim($data['bounced_date']) : date('Y-m-d');

            if ($chequeId <= 0 || empty($bouncedReason)) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Valid cheque selection and reason for bouncing are required.'];
            }

            $cheque = Database::fetchOne("
                SELECT sc.*, s.name AS supplier_name 
                FROM supplier_cheques sc 
                JOIN suppliers s ON sc.supplier_id = s.id 
                WHERE sc.id = :id FOR UPDATE
            ", [':id' => $chequeId]);

            if (!$cheque) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Cheque record not found.'];
            }

            if (in_array($cheque['status'], ['bounced', 'cancelled'])) {
                $db->rollBack();
                return ['success' => false, 'message' => "Cheque is already {$cheque['status']}."];
            }

            $prevStatus = $cheque['status'];

            // 1. Reverse GRN Allocations for related supplier payment
            $allocations = Database::fetchAll("
                SELECT spg.*, g.actual_grn_total, g.paid_amount
                FROM supplier_payment_grns spg
                JOIN grns g ON spg.grn_id = g.id
                WHERE spg.supplier_payment_id = :sp_id
                FOR UPDATE
            ", [':sp_id' => $cheque['supplier_payment_id']]);

            foreach ($allocations as $alloc) {
                $grnId      = (int)$alloc['grn_id'];
                $allocAmt   = (float)$alloc['allocated_amount'];
                $curPaid    = (float)$alloc['paid_amount'];
                $newPaid    = round(max(0.00, $curPaid - $allocAmt), 2);
                $pStatus    = ($newPaid <= 0.001) ? 'unpaid' : 'partially_paid';

                $db->prepare("
                    UPDATE grns SET paid_amount = :paid, payment_status = :status WHERE id = :id
                ")->execute([':paid' => $newPaid, ':status' => $pStatus, ':id' => $grnId]);
            }

            // 2. Reverse Accounting Entries
            // Reversal of clearing journal if it was cleared
            if ($prevStatus === 'cleared' && !empty($cheque['journal_entry_id'])) {
                Accounting::reverseJournalEntry((int)$cheque['journal_entry_id'], "Cheque #{$cheque['cheque_number']} bounced reversal", $userId);
            }

            // Reversal of original payment journal entry
            $paymentJe = Database::fetchOne("
                SELECT id FROM journal_entries 
                WHERE source_type = 'supplier_payment' AND source_id = :sp_id AND posting_status = 'posted'
            ", [':sp_id' => $cheque['supplier_payment_id']]);

            if ($paymentJe) {
                Accounting::reverseJournalEntry((int)$paymentJe['id'], "Cheque #{$cheque['cheque_number']} bounced - payment reversal", $userId);
            }

            // 3. Update Cheque record status
            $db->prepare("
                UPDATE supplier_cheques SET
                    status = 'bounced', bounced_at = NOW(), bounced_by = :user, bounced_reason = :reason
                WHERE id = :id
            ")->execute([
                ':user'   => $userId,
                ':reason' => $bouncedReason,
                ':id'     => $chequeId
            ]);

            $db->commit();

            Audit::log('CHEQUE_BOUNCED', 'finance', (string)$chequeId, ['status' => $prevStatus], [
                'cheque_number'  => $cheque['cheque_number'],
                'supplier_name'  => $cheque['supplier_name'],
                'bounced_reason' => $bouncedReason,
                'amount'         => $cheque['amount']
            ]);

            return [
                'success'   => true,
                'message'   => "Cheque #{$cheque['cheque_number']} marked as BOUNCED. Supplier payable & GRN balances restored successfully.",
                'cheque_id' => $chequeId
            ];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to bounce cheque #{$data['cheque_id']}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => "Bounce Cheque Error: " . $e->getMessage()];
        }
    }

    /**
     * Cancel a pending cheque
     * Cleared cheques CANNOT be cancelled!
     */
    public static function cancelCheque(array $data, int $userId): array
    {
        $db = Database::getInstance()->getConnection();

        try {
            $db->beginTransaction();

            $chequeId           = (int)($data['cheque_id'] ?? 0);
            $cancellationReason = trim($data['cancellation_reason'] ?? '');

            if ($chequeId <= 0 || empty($cancellationReason)) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Valid cheque selection and reason for cancellation are required.'];
            }

            $cheque = Database::fetchOne("
                SELECT sc.*, s.name AS supplier_name 
                FROM supplier_cheques sc 
                JOIN suppliers s ON sc.supplier_id = s.id 
                WHERE sc.id = :id FOR UPDATE
            ", [':id' => $chequeId]);

            if (!$cheque) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Cheque record not found.'];
            }

            if ($cheque['status'] === 'cleared') {
                $db->rollBack();
                return [
                    'success' => false,
                    'message' => "Cleared cheques cannot be cancelled. Cheque #{$cheque['cheque_number']} must be bounced or reversed via bank reversal."
                ];
            }

            if ($cheque['status'] !== 'pending') {
                $db->rollBack();
                return ['success' => false, 'message' => "Cheque is already {$cheque['status']} and cannot be cancelled."];
            }

            // 1. Reverse GRN Allocations for related supplier payment
            $allocations = Database::fetchAll("
                SELECT spg.*, g.actual_grn_total, g.paid_amount
                FROM supplier_payment_grns spg
                JOIN grns g ON spg.grn_id = g.id
                WHERE spg.supplier_payment_id = :sp_id
                FOR UPDATE
            ", [':sp_id' => $cheque['supplier_payment_id']]);

            foreach ($allocations as $alloc) {
                $grnId      = (int)$alloc['grn_id'];
                $allocAmt   = (float)$alloc['allocated_amount'];
                $curPaid    = (float)$alloc['paid_amount'];
                $newPaid    = round(max(0.00, $curPaid - $allocAmt), 2);
                $pStatus    = ($newPaid <= 0.001) ? 'unpaid' : 'partially_paid';

                $db->prepare("
                    UPDATE grns SET paid_amount = :paid, payment_status = :status WHERE id = :id
                ")->execute([':paid' => $newPaid, ':status' => $pStatus, ':id' => $grnId]);
            }

            // 2. Reverse original payment journal entry
            $paymentJe = Database::fetchOne("
                SELECT id FROM journal_entries 
                WHERE source_type = 'supplier_payment' AND source_id = :sp_id AND posting_status = 'posted'
            ", [':sp_id' => $cheque['supplier_payment_id']]);

            if ($paymentJe) {
                Accounting::reverseJournalEntry((int)$paymentJe['id'], "Cheque #{$cheque['cheque_number']} cancelled - payment reversal", $userId);
            }

            // 3. Update Cheque record status
            $db->prepare("
                UPDATE supplier_cheques SET
                    status = 'cancelled', cancelled_at = NOW(), cancelled_by = :user, cancellation_reason = :reason
                WHERE id = :id
            ")->execute([
                ':user'   => $userId,
                ':reason' => $cancellationReason,
                ':id'     => $chequeId
            ]);

            $db->commit();

            Audit::log('CHEQUE_CANCELLED', 'finance', (string)$chequeId, ['status' => 'pending'], [
                'cheque_number'       => $cheque['cheque_number'],
                'supplier_name'       => $cheque['supplier_name'],
                'cancellation_reason' => $cancellationReason,
                'amount'              => $cheque['amount']
            ]);

            return [
                'success'   => true,
                'message'   => "Pending Cheque #{$cheque['cheque_number']} CANCELLED. Supplier payable & GRN balances restored.",
                'cheque_id' => $chequeId
            ];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to cancel cheque #{$data['cheque_id']}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => "Cancel Cheque Error: " . $e->getMessage()];
        }
    }

    /**
     * Check if a cheque number already exists for a bank account in supplier_cheques
     */
    public static function checkDuplicateChequeNo(int $bankAccountId, string $chequeNo, ?int $excludeId = null): bool
    {
        try {
            $sql = "SELECT id FROM supplier_cheques WHERE bank_account_id = :ba AND cheque_number = :cn AND status IN ('pending', 'cleared') AND deleted_at IS NULL";
            $params = [':ba' => $bankAccountId, ':cn' => trim($chequeNo)];
            if ($excludeId) {
                $sql .= " AND id != :ex";
                $params[':ex'] = $excludeId;
            }
            return (bool)Database::fetchOne($sql, $params);
        } catch (Throwable $e) {
            Logger::error("Failed checking duplicate cheque no: " . $e->getMessage(), $e);
            return false;
        }
    }

    /**
     * Get list of customer cheques with filtering
     */
    public static function getCustomerCheques(array $filters = []): array
    {
        try {
            $where = [];
            $params = [];

            if (!empty($filters['view']) && $filters['view'] === 'deleted') {
                $where[] = "cq.deleted_at IS NOT NULL";
            } else {
                $where[] = "cq.deleted_at IS NULL";
            }

            if (!empty($filters['status']) && $filters['status'] !== 'all' && $filters['status'] !== 'history') {
                if ($filters['status'] === 'all_pending' || $filters['status'] === 'pending') {
                    $where[] = "cq.status IN ('in_hand', 'deposited')";
                } else {
                    $where[] = "cq.status = :status";
                    $params[':status'] = $filters['status'];
                }
            }

            if (!empty($filters['customer_id'])) {
                $where[] = "cq.customer_id = :customer_id";
                $params[':customer_id'] = (int)$filters['customer_id'];
            }

            if (!empty($filters['branch_id'])) {
                $where[] = "cq.branch_id = :branch_id";
                $params[':branch_id'] = (int)$filters['branch_id'];
            }

            if (!empty($filters['bank_account_id'])) {
                $where[] = "cq.bank_account_id = :bank_account_id";
                $params[':bank_account_id'] = (int)$filters['bank_account_id'];
            }

            if (!empty($filters['search'])) {
                $st = '%' . trim($filters['search']) . '%';
                $where[] = "(
                    cq.cheque_no LIKE :s1 OR 
                    cq.bank_name LIKE :s2 OR 
                    c.name LIKE :s3 OR 
                    c.customer_code LIKE :s4 OR 
                    cq.cheque_date LIKE :s5 OR 
                    cq.received_date LIKE :s6 OR 
                    cq.deposit_ref LIKE :s7 OR 
                    cq.bank_ref LIKE :s8 OR 
                    ba.account_name LIKE :s9 OR 
                    ba.account_number LIKE :s10 OR 
                    cq.status LIKE :s11 OR 
                    cq.return_reason LIKE :s12 OR 
                    cq.cancellation_reason LIKE :s13 OR 
                    CONCAT('#', cq.id) LIKE :s14 OR 
                    CAST(cq.amount AS CHAR) LIKE :s15 OR 
                    FORMAT(cq.amount, 2) LIKE :s16 OR 
                    CONCAT('LKR ', FORMAT(cq.amount, 2)) LIKE :s17
                )";
                for ($i = 1; $i <= 17; $i++) {
                    $params[":s{$i}"] = $st;
                }
            }

            if (!empty($filters['date_from'])) {
                $where[] = "cq.cheque_date >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }

            if (!empty($filters['date_to'])) {
                $where[] = "cq.cheque_date <= :date_to";
                $params[':date_to'] = $filters['date_to'];
            }

            $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

            $rows = Database::fetchAll("
                SELECT 
                    cq.*,
                    c.name AS customer_name,
                    c.customer_code,
                    c.phone AS customer_phone,
                    b.name AS branch_name,
                    ba.bank_name AS destination_bank_name,
                    ba.account_name AS bank_account_name,
                    ba.account_number AS bank_account_number,
                    uc.full_name AS created_by_name,
                    udep.full_name AS deposited_by_name,
                    uclr.full_name AS cleared_by_name,
                    uret.full_name AS returned_by_name,
                    ucan.full_name AS cancelled_by_name
                FROM customer_cheques cq
                JOIN customers c ON cq.customer_id = c.id
                LEFT JOIN branches b ON cq.branch_id = b.id
                LEFT JOIN bank_accounts ba ON cq.bank_account_id = ba.id
                LEFT JOIN users uc ON cq.created_by = uc.id
                LEFT JOIN users udep ON cq.deposited_by = udep.id
                LEFT JOIN users uclr ON cq.cleared_by = uclr.id
                LEFT JOIN users uret ON cq.returned_by = uret.id
                LEFT JOIN users ucan ON cq.cancelled_by = ucan.id
                $whereSql
                ORDER BY cq.id DESC
            ", $params);

            if (Auth::isSpecialUser()) {
                foreach ($rows as &$r) {
                    $r['amount'] = MaskingHelper::maskValue((float)$r['amount']);
                }
            }

            return $rows;
        } catch (Throwable $e) {
            Logger::error("Failed to fetch customer cheques: " . $e->getMessage(), $e);
            return [];
        }
    }

    /**
     * Get paginated list of customer cheques
     */
    public static function getPaginatedCustomerCheques(array $filters = [], int $page = 1, int $limit = 25): array
    {
        try {
            $allowedLimits = [25, 50, 75, 100, 500];
            if (!in_array($limit, $allowedLimits, true)) {
                $limit = 25;
            }

            $where = [];
            $params = [];

            if (!empty($filters['view']) && $filters['view'] === 'deleted') {
                $where[] = "cq.deleted_at IS NOT NULL";
            } else {
                $where[] = "cq.deleted_at IS NULL";
            }

            if (!empty($filters['status']) && $filters['status'] !== 'all' && $filters['status'] !== 'history') {
                if ($filters['status'] === 'all_pending' || $filters['status'] === 'pending') {
                    $where[] = "cq.status IN ('in_hand', 'deposited')";
                } else {
                    $where[] = "cq.status = :status";
                    $params[':status'] = $filters['status'];
                }
            }

            if (!empty($filters['customer_id'])) {
                $where[] = "cq.customer_id = :customer_id";
                $params[':customer_id'] = (int)$filters['customer_id'];
            }

            if (!empty($filters['branch_id'])) {
                $where[] = "cq.branch_id = :branch_id";
                $params[':branch_id'] = (int)$filters['branch_id'];
            }

            if (!empty($filters['bank_account_id'])) {
                $where[] = "cq.bank_account_id = :bank_account_id";
                $params[':bank_account_id'] = (int)$filters['bank_account_id'];
            }

            if (!empty($filters['search'])) {
                $st = '%' . trim($filters['search']) . '%';
                $where[] = "(
                    cq.cheque_no LIKE :s1 OR 
                    cq.bank_name LIKE :s2 OR 
                    c.name LIKE :s3 OR 
                    c.customer_code LIKE :s4 OR 
                    cq.cheque_date LIKE :s5 OR 
                    cq.received_date LIKE :s6 OR 
                    cq.deposit_ref LIKE :s7 OR 
                    cq.bank_ref LIKE :s8 OR 
                    ba.account_name LIKE :s9 OR 
                    ba.account_number LIKE :s10 OR 
                    cq.status LIKE :s11 OR 
                    cq.return_reason LIKE :s12 OR 
                    cq.cancellation_reason LIKE :s13 OR 
                    CONCAT('#', cq.id) LIKE :s14 OR 
                    CAST(cq.amount AS CHAR) LIKE :s15 OR 
                    FORMAT(cq.amount, 2) LIKE :s16 OR 
                    CONCAT('LKR ', FORMAT(cq.amount, 2)) LIKE :s17
                )";
                for ($i = 1; $i <= 17; $i++) {
                    $params[":s{$i}"] = $st;
                }
            }

            if (!empty($filters['date_from'])) {
                $where[] = "cq.cheque_date >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }

            if (!empty($filters['date_to'])) {
                $where[] = "cq.cheque_date <= :date_to";
                $params[':date_to'] = $filters['date_to'];
            }

            $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

            $countRow = Database::fetchOne("
                SELECT COUNT(*) AS total
                FROM customer_cheques cq
                JOIN customers c ON cq.customer_id = c.id
                LEFT JOIN branches b ON cq.branch_id = b.id
                LEFT JOIN bank_accounts ba ON cq.bank_account_id = ba.id
                $whereSql
            ", $params);
            $totalItems = (int)($countRow['total'] ?? 0);
            $totalPages = max(1, (int)ceil($totalItems / $limit));
            $page = max(1, min($page, $totalPages));
            $offset = ($page - 1) * $limit;

            $items = Database::fetchAll("
                SELECT 
                    cq.*,
                    c.name AS customer_name,
                    c.customer_code,
                    c.phone AS customer_phone,
                    b.name AS branch_name,
                    ba.bank_name AS destination_bank_name,
                    ba.account_name AS bank_account_name,
                    ba.account_number AS bank_account_number,
                    uc.full_name AS created_by_name,
                    udep.full_name AS deposited_by_name,
                    uclr.full_name AS cleared_by_name,
                    uret.full_name AS returned_by_name,
                    ucan.full_name AS cancelled_by_name
                FROM customer_cheques cq
                JOIN customers c ON cq.customer_id = c.id
                LEFT JOIN branches b ON cq.branch_id = b.id
                LEFT JOIN bank_accounts ba ON cq.bank_account_id = ba.id
                LEFT JOIN users uc ON cq.created_by = uc.id
                LEFT JOIN users udep ON cq.deposited_by = udep.id
                LEFT JOIN users uclr ON cq.cleared_by = uclr.id
                LEFT JOIN users uret ON cq.returned_by = uret.id
                LEFT JOIN users ucan ON cq.cancelled_by = ucan.id
                $whereSql
                ORDER BY cq.id DESC
                LIMIT $limit OFFSET $offset
            ", $params);

            if (Auth::isSpecialUser()) {
                foreach ($items as &$item) {
                    $item['amount'] = MaskingHelper::maskValue((float)$item['amount']);
                }
            }

            return [
                'items'      => $items,
                'totalItems' => $totalItems,
                'totalPages' => $totalPages,
                'page'       => $page,
                'limit'      => $limit
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to fetch paginated customer cheques: " . $e->getMessage(), $e);
            return [
                'items'      => [],
                'totalItems' => 0,
                'totalPages' => 1,
                'page'       => 1,
                'limit'      => $limit
            ];
        }
    }

    /**
     * Fast aggregated pending summary totals for customer cheques
     */
    public static function getCustomerPendingSummary(int $branchId, ?int $customerId = null): array
    {
        try {
            $sql = "
                SELECT 
                    SUM(CASE WHEN status = 'in_hand' THEN 1 ELSE 0 END) AS count_in_hand,
                    COALESCE(SUM(CASE WHEN status = 'in_hand' THEN amount ELSE 0 END), 0.00) AS total_in_hand,
                    SUM(CASE WHEN status = 'deposited' THEN 1 ELSE 0 END) AS count_deposited,
                    COALESCE(SUM(CASE WHEN status = 'deposited' THEN amount ELSE 0 END), 0.00) AS total_deposited
                FROM customer_cheques
                WHERE deleted_at IS NULL AND status IN ('in_hand', 'deposited') AND branch_id = :branch_id
            ";
            $params = [':branch_id' => $branchId];

            if ($customerId !== null && $customerId > 0) {
                $sql .= " AND customer_id = :customer_id";
                $params[':customer_id'] = $customerId;
            }

            $row = Database::fetchOne($sql, $params);
            $countInHand    = (int)($row['count_in_hand'] ?? 0);
            $totalInHand    = (float)($row['total_in_hand'] ?? 0.00);
            $countDeposited = (int)($row['count_deposited'] ?? 0);
            $totalDeposited = (float)($row['total_deposited'] ?? 0.00);

            if (Auth::isSpecialUser()) {
                $totalInHand    = MaskingHelper::maskValue($totalInHand);
                $totalDeposited = MaskingHelper::maskValue($totalDeposited);
            }

            return [
                'count_in_hand'   => $countInHand,
                'total_in_hand'   => $totalInHand,
                'count_deposited' => $countDeposited,
                'total_deposited' => $totalDeposited
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to fetch customer pending cheque summary: " . $e->getMessage(), $e);
            return [
                'count_in_hand'   => 0,
                'total_in_hand'   => 0.00,
                'count_deposited' => 0,
                'total_deposited' => 0.00
            ];
        }
    }

    /**
     * Get single customer cheque details by ID
     */
    public static function getCustomerCheque(int $id): ?array
    {
        try {
            $cheque = Database::fetchOne("
                SELECT 
                    cq.*,
                    c.name AS customer_name,
                    c.customer_code,
                    c.phone AS customer_phone,
                    b.name AS branch_name,
                    ba.bank_name AS destination_bank_name,
                    ba.account_name AS bank_account_name,
                    ba.account_number AS bank_account_number,
                    uc.full_name AS created_by_name,
                    udep.full_name AS deposited_by_name,
                    uclr.full_name AS cleared_by_name,
                    uret.full_name AS returned_by_name,
                    ucan.full_name AS cancelled_by_name
                FROM customer_cheques cq
                JOIN customers c ON cq.customer_id = c.id
                LEFT JOIN branches b ON cq.branch_id = b.id
                LEFT JOIN bank_accounts ba ON cq.bank_account_id = ba.id
                LEFT JOIN users uc ON cq.created_by = uc.id
                LEFT JOIN users udep ON cq.deposited_by = udep.id
                LEFT JOIN users uclr ON cq.cleared_by = uclr.id
                LEFT JOIN users uret ON cq.returned_by = uret.id
                LEFT JOIN users ucan ON cq.cancelled_by = ucan.id
                WHERE cq.id = :id
            ", [':id' => $id]);

            if ($cheque && Auth::isSpecialUser()) {
                $cheque['amount'] = MaskingHelper::maskValue((float)$cheque['amount']);
            }

            return $cheque;
        } catch (Throwable $e) {
            Logger::error("Failed to fetch customer cheque details for #{$id}: " . $e->getMessage(), $e);
            return null;
        }
    }

    /**
     * Deposit a customer cheque to a destination bank account
     * Updates status from 'in_hand' to 'deposited'
     */
    public static function depositCustomerCheque(array $data, int $userId): array
    {
        $db = Database::getInstance()->getConnection();
        try {
            $db->beginTransaction();

            $chequeId      = (int)($data['cheque_id'] ?? 0);
            $bankAccountId = (int)($data['bank_account_id'] ?? 0);
            $depositDate   = !empty($data['deposit_date']) ? trim($data['deposit_date']) : date('Y-m-d');
            $depositRef    = !empty($data['deposit_ref']) ? trim($data['deposit_ref']) : null;

            if ($chequeId <= 0 || $bankAccountId <= 0) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Valid cheque selection and destination bank account are required.'];
            }

            $cheque = Database::fetchOne("
                SELECT cq.*, c.name AS customer_name 
                FROM customer_cheques cq 
                JOIN customers c ON cq.customer_id = c.id 
                WHERE cq.id = :id FOR UPDATE
            ", [':id' => $chequeId]);

            if (!$cheque) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Customer cheque record not found.'];
            }

            if ($cheque['status'] !== 'in_hand') {
                $db->rollBack();
                return ['success' => false, 'message' => "Only in-hand cheques can be marked as deposited. Current status: {$cheque['status']}."];
            }

            $bankAccount = Banking::getBankAccount($bankAccountId);
            if (!$bankAccount || $bankAccount['status'] !== 'active') {
                $db->rollBack();
                return ['success' => false, 'message' => 'Selected bank account is invalid or inactive.'];
            }

            $db->prepare("
                UPDATE customer_cheques 
                SET status = 'deposited', bank_account_id = :bank_acc, deposit_date = :dep_date, deposit_ref = :dep_ref,
                    deposited_at = NOW(), deposited_by = :user
                WHERE id = :id
            ")->execute([
                ':bank_acc'  => $bankAccountId,
                ':dep_date'  => $depositDate,
                ':dep_ref'   => $depositRef,
                ':user'      => $userId,
                ':id'        => $chequeId
            ]);

            $db->commit();

            Audit::log('CUSTOMER_CHEQUE_DEPOSITED', 'customer_cheques', (string)$chequeId, ['status' => 'in_hand'], [
                'cheque_no'      => $cheque['cheque_no'],
                'customer_name'  => $cheque['customer_name'],
                'bank_account'   => $bankAccount['bank_name'] . ' (' . $bankAccount['account_number'] . ')',
                'deposit_ref'    => $depositRef,
                'amount'         => $cheque['amount']
            ]);

            return [
                'success'   => true,
                'message'   => "Cheque #{$cheque['cheque_no']} deposited to {$bankAccount['bank_name']} successfully.",
                'cheque_id' => $chequeId
            ];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to deposit customer cheque #{$data['cheque_id']}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => "Deposit Cheque Error: " . $e->getMessage()];
        }
    }

    /**
     * Clear a customer cheque
     * Dr Destination Bank Account COA (bank_accounts.account_id)
     * Cr 1035 Cheques in Hand (Accounting::CODE_CHEQUES_HAND)
     */
    public static function clearCustomerCheque(array $data, int $userId): array
    {
        $db = Database::getInstance()->getConnection();
        try {
            $db->beginTransaction();

            $chequeId      = (int)($data['cheque_id'] ?? 0);
            $clearedDate   = !empty($data['cleared_date']) ? trim($data['cleared_date']) : date('Y-m-d');
            $bankRef       = !empty($data['bank_ref']) ? trim($data['bank_ref']) : null;
            $bankAccountId = !empty($data['bank_account_id']) ? (int)$data['bank_account_id'] : 0;

            if ($chequeId <= 0) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Valid cheque selection is required.'];
            }

            $cheque = Database::fetchOne("
                SELECT cq.*, c.name AS customer_name 
                FROM customer_cheques cq 
                JOIN customers c ON cq.customer_id = c.id 
                WHERE cq.id = :id FOR UPDATE
            ", [':id' => $chequeId]);

            if (!$cheque) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Customer cheque record not found.'];
            }

            if (!in_array($cheque['status'], ['in_hand', 'deposited'], true)) {
                $db->rollBack();
                return ['success' => false, 'message' => "Only in-hand or deposited cheques can be cleared. Current status: {$cheque['status']}."];
            }

            $effectiveBankId = $bankAccountId ?: (int)$cheque['bank_account_id'];
            if ($effectiveBankId <= 0) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Destination bank account is required to clear cheque.'];
            }

            $bankAccount = Banking::getBankAccount($effectiveBankId);
            if (!$bankAccount || $bankAccount['status'] !== 'active') {
                $db->rollBack();
                return ['success' => false, 'message' => 'Destination bank account is invalid or inactive.'];
            }

            $amount = round((float)$cheque['amount'], 2);
            $chequesInHandAccId = Accounting::getAccountIdByCode(Accounting::CODE_CHEQUES_HAND); // 1035
            $bankCoaAccId       = (int)($bankAccount['account_id'] ?? Accounting::getAccountIdByCode(Accounting::CODE_BANK));

            // Post double-entry journal for customer cheque clearing
            $header = [
                'branch_id'   => (int)$cheque['branch_id'],
                'entry_date'  => $clearedDate,
                'source_type' => 'customer_cheque_clearing',
                'source_id'   => $chequeId,
                'description' => "Customer Cheque #{$cheque['cheque_no']} ({$cheque['bank_name']}) cleared to {$bankAccount['bank_name']} ({$bankAccount['account_number']})",
                'created_by'  => $userId
            ];

            $lines = [
                [
                    'account_id' => $bankCoaAccId,
                    'debit'      => $amount,
                    'credit'     => 0.00,
                    'memo'       => "Deposit clearing from Cheque #{$cheque['cheque_no']}"
                ],
                [
                    'account_id' => $chequesInHandAccId,
                    'debit'      => 0.00,
                    'credit'     => $amount,
                    'memo'       => "Credit Cheques in Hand for #{$cheque['cheque_no']}"
                ]
            ];

            $jeRes = Accounting::postJournalEntry($header, $lines);
            if (!$jeRes['success']) {
                $db->rollBack();
                return ['success' => false, 'message' => "Customer Cheque Clearing Journal Error: " . $jeRes['message']];
            }

            $clearingJournalId = $jeRes['journal_id'];

            $db->prepare("
                UPDATE customer_cheques 
                SET status = 'cleared', bank_account_id = :bank_acc, cleared_date = :clr_date, bank_ref = :bank_ref,
                    cleared_at = NOW(), cleared_by = :user, journal_entry_id = :je_id
                WHERE id = :id
            ")->execute([
                ':bank_acc'  => $effectiveBankId,
                ':clr_date'  => $clearedDate,
                ':bank_ref'  => $bankRef,
                ':user'      => $userId,
                ':je_id'     => $clearingJournalId,
                ':id'        => $chequeId
            ]);

            $db->commit();

            Audit::log('CUSTOMER_CHEQUE_CLEARED', 'customer_cheques', (string)$chequeId, null, [
                'cheque_no'     => $cheque['cheque_no'],
                'customer_name' => $cheque['customer_name'],
                'bank_account'  => $bankAccount['bank_name'] . ' (' . $bankAccount['account_number'] . ')',
                'amount'        => $amount
            ]);

            return [
                'success'          => true,
                'message'          => "Customer Cheque #{$cheque['cheque_no']} of LKR " . number_format($amount, 2) . " cleared successfully into {$bankAccount['bank_name']}.",
                'cheque_id'        => $chequeId,
                'journal_entry_id' => $clearingJournalId
            ];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to clear customer cheque #{$data['cheque_id']}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => "Clear Customer Cheque Error: " . $e->getMessage()];
        }
    }

    /**
     * Return a customer cheque (Bounced)
     * Uses CustomerPayment::returnCustomerCheque for full AR restoration and reversal journal
     */
    public static function returnCustomerCheque(array $data, int $userId, int $branchId): array
    {
        require_once __DIR__ . '/CustomerPayment.php';
        return CustomerPayment::returnCustomerCheque($data, $userId, $branchId);
    }

    /**
     * Cancel an in-hand customer cheque
     * Cleared cheques cannot be cancelled.
     */
    public static function cancelCustomerCheque(array $data, int $userId, int $branchId): array
    {
        $db = Database::getInstance()->getConnection();
        try {
            $db->beginTransaction();

            $chequeId           = (int)($data['cheque_id'] ?? 0);
            $cancellationReason = trim($data['cancellation_reason'] ?? '');

            if ($chequeId <= 0 || empty($cancellationReason)) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Valid cheque selection and reason for cancellation are required.'];
            }

            $cheque = Database::fetchOne("
                SELECT cq.*, c.name AS customer_name, c.credit_balance 
                FROM customer_cheques cq 
                JOIN customers c ON cq.customer_id = c.id 
                WHERE cq.id = :id FOR UPDATE
            ", [':id' => $chequeId]);

            if (!$cheque) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Customer cheque record not found.'];
            }

            if ($cheque['status'] === 'cleared') {
                $db->rollBack();
                return ['success' => false, 'message' => 'Cleared customer cheques cannot be cancelled. Use the return workflow instead.'];
            }

            if (in_array($cheque['status'], ['cancelled', 'returned'], true)) {
                $db->rollBack();
                return ['success' => false, 'message' => "Cheque is already {$cheque['status']}."];
            }

            $customerId = (int)$cheque['customer_id'];
            $amount = round((float)$cheque['amount'], 2);

            // Restore customer credit balance
            $currentDebt = (float)$cheque['credit_balance'];
            $newDebt = round($currentDebt + $amount, 2);
            $db->prepare("
                UPDATE customers SET credit_balance = :new_bal, updated_at = NOW() WHERE id = :id
            ")->execute([':new_bal' => $newDebt, ':id' => $customerId]);

            // Insert ledger entry
            $canRef = "CHQ-CAN-" . $cheque['cheque_no'];
            $stmtCcl = $db->prepare("
                INSERT INTO customer_credit_ledger (
                    customer_id, branch_id, user_id, bank_account_id, amount_paid, amount_due, balance_after, transaction_type, payment_method, reference_no, notes
                ) VALUES (
                    :customer_id, :branch_id, :user_id, :bank_account_id, 0.00, :amount_due, :balance_after, 'cheque_return', 'Cheque', :reference_no, :notes
                )
            ");
            $stmtCcl->execute([
                ':customer_id'     => $customerId,
                ':branch_id'       => $branchId,
                ':user_id'         => $userId,
                ':bank_account_id' => $cheque['bank_account_id'],
                ':amount_due'      => $amount,
                ':balance_after'   => $newDebt,
                ':reference_no'    => $canRef,
                ':notes'           => "Cheque #{$cheque['cheque_no']} cancelled: {$cancellationReason}"
            ]);

            // Reverse original customer collection journal if exists
            if (!empty($cheque['customer_credit_ledger_id'])) {
                $je = Database::fetchOne("
                    SELECT id FROM journal_entries 
                    WHERE source_type = 'customer_collection' AND source_id = :cid AND posting_status = 'posted'
                ", [':cid' => $cheque['customer_credit_ledger_id']]);
                if ($je) {
                    Accounting::reverseJournalEntry((int)$je['id'], "Customer Cheque #{$cheque['cheque_no']} cancelled - collection reversal", $userId);
                }
            }

            $db->prepare("
                UPDATE customer_cheques 
                SET status = 'cancelled', cancellation_reason = :reason, cancelled_at = NOW(), cancelled_by = :user
                WHERE id = :id
            ")->execute([
                ':reason' => $cancellationReason,
                ':user'   => $userId,
                ':id'     => $chequeId
            ]);

            $db->commit();

            Audit::log('CUSTOMER_CHEQUE_CANCELLED', 'customer_cheques', (string)$chequeId, ['status' => $cheque['status']], [
                'cheque_no'           => $cheque['cheque_no'],
                'customer_name'       => $cheque['customer_name'],
                'cancellation_reason' => $cancellationReason,
                'amount'              => $amount
            ]);

            return [
                'success'   => true,
                'message'   => "Customer Cheque #{$cheque['cheque_no']} CANCELLED. Outstanding balance restored to LKR " . number_format($newDebt, 2) . ".",
                'cheque_id' => $chequeId
            ];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to cancel customer cheque #{$data['cheque_id']}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => "Cancel Customer Cheque Error: " . $e->getMessage()];
        }
    }

    /**
     * Soft delete a customer cheque (Trash Bin)
     */
    public static function deleteCustomerCheque(int $id, int $userId): array
    {
        $db = Database::getInstance()->getConnection();
        try {
            $db->beginTransaction();

            $cheque = Database::fetchOne("
                SELECT * FROM customer_cheques WHERE id = :id FOR UPDATE
            ", [':id' => $id]);

            if (!$cheque) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Customer cheque record not found.'];
            }

            if (!empty($cheque['deleted_at'])) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Customer cheque is already in Trash Bin.'];
            }

            $db->prepare("
                UPDATE customer_cheques 
                SET deleted_at = NOW() 
                WHERE id = :id
            ")->execute([':id' => $id]);

            $db->commit();

            Audit::log('CUSTOMER_CHEQUE_DELETED', 'customer_cheques', (string)$id, ['status' => $cheque['status']], [
                'cheque_no' => $cheque['cheque_no'],
                'amount'    => $cheque['amount']
            ]);

            return ['success' => true, 'message' => "Customer Cheque #{$cheque['cheque_no']} moved to Trash Bin."];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to soft-delete customer cheque #{$id}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => "Soft-Delete Customer Cheque Error: " . $e->getMessage()];
        }
    }

    /**
     * Restore a soft-deleted customer cheque from Trash Bin
     */
    public static function restoreCustomerCheque(int $id, int $userId): array
    {
        $db = Database::getInstance()->getConnection();
        try {
            $db->beginTransaction();

            $cheque = Database::fetchOne("
                SELECT * FROM customer_cheques WHERE id = :id FOR UPDATE
            ", [':id' => $id]);

            if (!$cheque) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Customer cheque record not found.'];
            }

            if (empty($cheque['deleted_at'])) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Customer cheque is not in Trash Bin.'];
            }

            $db->prepare("
                UPDATE customer_cheques 
                SET deleted_at = NULL 
                WHERE id = :id
            ")->execute([':id' => $id]);

            $db->commit();

            Audit::log('CUSTOMER_CHEQUE_RESTORED', 'customer_cheques', (string)$id, null, [
                'cheque_no' => $cheque['cheque_no'],
                'amount'    => $cheque['amount']
            ]);

            return ['success' => true, 'message' => "Customer Cheque #{$cheque['cheque_no']} restored successfully."];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to restore customer cheque #{$id}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => "Restore Customer Cheque Error: " . $e->getMessage()];
        }
    }
}

