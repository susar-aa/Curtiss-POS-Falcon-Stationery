<?php
/**
 * Curtiss POS - Card Receivable (1030) -> Bank Settlement Core Engine (Stage 37 / Section 17)
 * Handles Card Processor masters, Unsettled POS Card Payment aggregation,
 * Card Settlement batching (gross, processing fee, net bank), double-entry GL posting,
 * payment settlement status tracking, and controlled reversal.
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Audit.php';
require_once __DIR__ . '/Accounting.php';
require_once __DIR__ . '/Banking.php';

class CardSettlement
{
    /**
     * Generate unique continuous settlement number (CS-YYYYMMDD-0001...)
     */
    public static function generateSettlementNumber(): string
    {
        try {
            $db = Database::getInstance()->getConnection();
            $prefix = 'CS-' . date('Ymd') . '-';
            $stmt = $db->prepare("SELECT MAX(id) AS max_id FROM card_settlements WHERE settlement_no LIKE :prefix");
            $stmt->execute([':prefix' => $prefix . '%']);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $nextNum = ((int)($row['max_id'] ?? 0)) + 1;
            return $prefix . sprintf('%04d', $nextNum);
        } catch (Throwable $e) {
            Logger::error("Failed to generate CS settlement number: " . $e->getMessage(), $e);
            return 'CS-' . date('Ymd') . '-' . rand(1000, 9999);
        }
    }

    /**
     * List Card Processors / Acquirers
     */
    public static function getProcessors(array $filters = []): array
    {
        try {
            $where = [];
            $params = [];

            if (!empty($filters['status'])) {
                $where[] = "cp.status = :status";
                $params[':status'] = $filters['status'];
            }

            if (!empty($filters['search'])) {
                $where[] = "(cp.processor_code LIKE :search OR cp.name LIKE :search OR cp.merchant_id LIKE :search)";
                $params[':search'] = '%' . trim($filters['search']) . '%';
            }

            $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

            return Database::fetchAll("
                SELECT cp.*, ba.account_name AS bank_account_name, ba.account_number AS bank_account_number
                FROM card_processors cp
                LEFT JOIN bank_accounts ba ON cp.default_bank_account_id = ba.id
                $whereSql
                ORDER BY cp.name ASC
            ", $params);
        } catch (Throwable $e) {
            Logger::error("Failed to list card processors: " . $e->getMessage(), $e);
            return [];
        }
    }

    /**
     * Get single Card Processor by ID
     */
    public static function getProcessor(int $id): ?array
    {
        try {
            return Database::fetchOne("
                SELECT cp.*, ba.account_name AS bank_account_name, ba.account_number AS bank_account_number
                FROM card_processors cp
                LEFT JOIN bank_accounts ba ON cp.default_bank_account_id = ba.id
                WHERE cp.id = :id
            ", [':id' => $id]);
        } catch (Throwable $e) {
            Logger::error("Failed to fetch card processor #{$id}: " . $e->getMessage(), $e);
            return null;
        }
    }

    /**
     * Save (Create or Update) Card Processor Master
     */
    public static function saveProcessor(array $data, int $userId): array
    {
        try {
            $id            = !empty($data['id']) ? (int)$data['id'] : null;
            $existingProc  = $id ? self::getProcessor($id) : null;
            $code          = strtoupper(trim($data['processor_code'] ?? ($existingProc['processor_code'] ?? '')));
            $name          = trim($data['name'] ?? ($existingProc['name'] ?? ''));
            $merchantId    = array_key_exists('merchant_id', $data) ? (!empty($data['merchant_id']) ? trim($data['merchant_id']) : null) : ($existingProc['merchant_id'] ?? null);
            $defaultBankId = array_key_exists('default_bank_account_id', $data) ? (!empty($data['default_bank_account_id']) ? (int)$data['default_bank_account_id'] : null) : ($existingProc ? (!empty($existingProc['default_bank_account_id']) ? (int)$existingProc['default_bank_account_id'] : null) : null);
            $status        = ($data['status'] ?? ($existingProc['status'] ?? 'active')) === 'inactive' ? 'inactive' : 'active';

            if (empty($code) || empty($name)) {
                return ['success' => false, 'message' => 'Processor code and processor name are required.'];
            }

            // Check duplicate processor_code
            $dupSql = "SELECT id FROM card_processors WHERE processor_code = :code";
            $dupParams = [':code' => $code];
            if ($id) {
                $dupSql .= " AND id != :id";
                $dupParams[':id'] = $id;
            }
            if (Database::fetchOne($dupSql, $dupParams)) {
                return ['success' => false, 'message' => "Processor code '{$code}' already exists."];
            }

            // Validate default bank account if supplied
            if ($defaultBankId) {
                $bank = Banking::getBankAccount($defaultBankId);
                if (!$bank) {
                    return ['success' => false, 'message' => 'Selected default bank account does not exist.'];
                }
            }

            if ($id) {
                Database::execute("
                    UPDATE card_processors SET
                        processor_code = :code, name = :name, merchant_id = :mid,
                        default_bank_account_id = :bank_id, status = :status, updated_at = NOW()
                    WHERE id = :id
                ", [
                    ':code'    => $code,
                    ':name'    => $name,
                    ':mid'     => $merchantId,
                    ':bank_id' => $defaultBankId,
                    ':status'  => $status,
                    ':id'      => $id
                ]);

                Audit::log('CARD_PROCESSOR_UPDATED', 'banking', (string)$id, null, ['code' => $code, 'name' => $name]);
                return ['success' => true, 'id' => $id, 'message' => "Card Processor '{$name}' updated successfully."];
            } else {
                Database::execute("
                    INSERT INTO card_processors (processor_code, name, merchant_id, default_bank_account_id, status)
                    VALUES (:code, :name, :mid, :bank_id, :status)
                ", [
                    ':code'    => $code,
                    ':name'    => $name,
                    ':mid'     => $merchantId,
                    ':bank_id' => $defaultBankId,
                    ':status'  => $status
                ]);

                $newId = (int)Database::getInstance()->getConnection()->lastInsertId();
                Audit::log('CARD_PROCESSOR_CREATED', 'banking', (string)$newId, null, ['code' => $code, 'name' => $name]);
                return ['success' => true, 'id' => $newId, 'message' => "Card Processor '{$name}' created successfully."];
            }
        } catch (Throwable $e) {
            Logger::error("Failed to save card processor: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Failed to save processor: ' . $e->getMessage()];
        }
    }

    /**
     * Toggle Card Processor Active Status
     */
    public static function toggleProcessorStatus(int $id, int $userId): array
    {
        try {
            $proc = self::getProcessor($id);
            if (!$proc) {
                return ['success' => false, 'message' => 'Processor not found.'];
            }

            $newStatus = ($proc['status'] === 'active') ? 'inactive' : 'active';
            Database::execute("UPDATE card_processors SET status = :status, updated_at = NOW() WHERE id = :id", [
                ':status' => $newStatus,
                ':id'     => $id
            ]);

            Audit::log('CARD_PROCESSOR_STATUS_TOGGLED', 'banking', (string)$id, ['old' => $proc['status']], ['new' => $newStatus]);
            return ['success' => true, 'message' => "Processor status changed to '{$newStatus}'."];
        } catch (Throwable $e) {
            Logger::error("Failed to toggle card processor #{$id} status: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Fetch Unsettled Card Payments available for settlement batching
     */
    public static function getUnsettledCardPayments(array $filters = []): array
    {
        try {
            $where = [
                "LOWER(p.payment_method) = 'card'",
                "p.settlement_status IN ('unsettled', 'partially_settled')",
                "s.status != 'cancelled'"
            ];
            $params = [];

            if (!empty($filters['branch_id'])) {
                $where[] = "s.branch_id = :branch_id";
                $params[':branch_id'] = (int)$filters['branch_id'];
            }

            if (!empty($filters['processor_id'])) {
                $where[] = "p.processor_id = :processor_id";
                $params[':processor_id'] = (int)$filters['processor_id'];
            }

            if (!empty($filters['start_date'])) {
                $where[] = "DATE(p.created_at) >= :start_date";
                $params[':start_date'] = $filters['start_date'];
            }

            if (!empty($filters['end_date'])) {
                $where[] = "DATE(p.created_at) <= :end_date";
                $params[':end_date'] = $filters['end_date'];
            }

            if (!empty($filters['search'])) {
                $where[] = "(s.invoice_no LIKE :search OR p.reference_no LIKE :search)";
                $params[':search'] = '%' . trim($filters['search']) . '%';
            }

            $whereSql = implode(" AND ", $where);

            return Database::fetchAll("
                SELECT 
                    p.id AS payment_id,
                    p.sale_id,
                    s.invoice_no AS invoice_number,
                    s.branch_id,
                    b.name AS branch_name,
                    p.created_at AS payment_date,
                    p.amount AS gross_payment_amount,
                    p.settled_amount,
                    COALESCE(cr.total_refund, 0.00) AS card_refund_amount,
                    ROUND(p.amount - COALESCE(cr.total_refund, 0.00) - p.settled_amount, 2) AS unsettled_amount,
                    p.processor_id,
                    cp.name AS processor_name,
                    p.reference_no,
                    NULL AS approval_code,
                    NULL AS card_type,
                    NULL AS card_last4
                FROM payments p
                JOIN sales s ON p.sale_id = s.id
                JOIN branches b ON s.branch_id = b.id
                LEFT JOIN card_processors cp ON p.processor_id = cp.id
                LEFT JOIN (
                    SELECT sr.sale_id, SUM(r.amount) AS total_refund
                    FROM refunds r
                    JOIN sales_returns sr ON r.sales_return_id = sr.id
                    WHERE LOWER(r.refund_method) = 'card' AND sr.status != 'cancelled'
                    GROUP BY sr.sale_id
                ) cr ON p.sale_id = cr.sale_id
                WHERE $whereSql
                  AND ROUND(p.amount - COALESCE(cr.total_refund, 0.00) - p.settled_amount, 2) > 0.001
                ORDER BY p.created_at ASC, p.id ASC
            ", $params);
        } catch (Throwable $e) {
            Logger::error("Failed to fetch unsettled card payments: " . $e->getMessage(), $e);
            return [];
        }
    }

    /**
     * Create Card Settlement Draft
     */
    public static function createSettlement(array $data, array $paymentItems, int $userId): array
    {
        $db = Database::getInstance()->getConnection();

        try {
            $db->beginTransaction();

            $branchId      = (int)($data['branch_id'] ?? 0);
            $processorId   = !empty($data['processor_id']) ? (int)$data['processor_id'] : null;
            $bankAccountId = (int)($data['bank_account_id'] ?? 0);
            $settleDate    = !empty($data['settlement_date']) ? trim($data['settlement_date']) : date('Y-m-d');
            $referenceNo   = !empty($data['reference_no']) ? trim($data['reference_no']) : null;
            $processingFee = round((float)($data['processing_fee'] ?? 0), 2);
            $notes         = !empty($data['notes']) ? trim($data['notes']) : null;

            if ($processingFee < 0.00) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Processing fee cannot be negative.'];
            }

            if ($branchId <= 0 || $bankAccountId <= 0) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Branch ID and Bank Account ID are required.'];
            }

            // Verify active operational bank account
            $bank = Banking::getBankAccount($bankAccountId);
            if (!$bank || $bank['status'] !== 'active') {
                $db->rollBack();
                return ['success' => false, 'message' => 'Selected bank account is invalid or inactive. Inactive bank accounts cannot be selected.'];
            }

            // Verify processor if specified
            if ($processorId) {
                $proc = self::getProcessor($processorId);
                if (!$proc || $proc['status'] !== 'active') {
                    $db->rollBack();
                    return ['success' => false, 'message' => 'Selected card processor is invalid or inactive.'];
                }
            }

            if (empty($paymentItems)) {
                $db->rollBack();
                return ['success' => false, 'message' => 'At least one unsettled card payment must be selected for settlement.'];
            }

            $grossAmount = 0.00;
            $validatedItems = [];
            $seenPaymentIds = [];

            foreach ($paymentItems as $idx => $item) {
                $paymentId = (int)($item['payment_id'] ?? 0);
                $itemGross = round((float)($item['gross_amount'] ?? 0), 2);

                if ($paymentId <= 0 || $itemGross <= 0.00) {
                    $db->rollBack();
                    return ['success' => false, 'message' => "Invalid payment ID or zero gross amount at item #" . ($idx + 1)];
                }

                if (in_array($paymentId, $seenPaymentIds, true)) {
                    $db->rollBack();
                    return ['success' => false, 'message' => "Duplicate payment ID #{$paymentId} in settlement item lines."];
                }
                $seenPaymentIds[] = $paymentId;

                // Verify payment record
                $pmt = Database::fetchOne("
                    SELECT p.*, s.branch_id
                    FROM payments p
                    JOIN sales s ON p.sale_id = s.id
                    WHERE p.id = :pid AND LOWER(p.payment_method) = 'card' FOR UPDATE
                ", [':pid' => $paymentId]);

                if (!$pmt) {
                    $db->rollBack();
                    return ['success' => false, 'message' => "Card payment #{$paymentId} not found."];
                }

                if ((int)$pmt['branch_id'] !== $branchId) {
                    $db->rollBack();
                    return ['success' => false, 'message' => "Payment #{$paymentId} does not belong to the selected settlement branch."];
                }

                if ($processorId && !empty($pmt['processor_id']) && (int)$pmt['processor_id'] !== $processorId) {
                    $db->rollBack();
                    return ['success' => false, 'message' => "Payment #{$paymentId} belongs to a different processor than the selected settlement processor."];
                }

                $cardRefundRow = Database::fetchOne("
                    SELECT COALESCE(SUM(r.amount), 0.00) AS total_refund
                    FROM refunds r
                    JOIN sales_returns sr ON r.sales_return_id = sr.id
                    WHERE sr.sale_id = :sale_id AND LOWER(r.refund_method) = 'card' AND sr.status != 'cancelled'
                ", [':sale_id' => $pmt['sale_id']]);
                $cardRefund = (float)($cardRefundRow['total_refund'] ?? 0);

                $unsettled = round((float)$pmt['amount'] - $cardRefund - (float)$pmt['settled_amount'], 2);
                if ($itemGross > ($unsettled + 0.001)) {
                    $db->rollBack();
                    return ['success' => false, 'message' => "Settlement amount (LKR {$itemGross}) exceeds unsettled balance (LKR {$unsettled}) for payment #{$paymentId}."];
                }

                $grossAmount += $itemGross;
                $validatedItems[] = [
                    'payment_id'   => $paymentId,
                    'gross_amount' => $itemGross
                ];
            }

            $grossAmount = round($grossAmount, 2);
            if ($processingFee > $grossAmount) {
                $db->rollBack();
                return ['success' => false, 'message' => "Processing fee (LKR {$processingFee}) cannot exceed gross settlement amount (LKR {$grossAmount})."];
            }

            $netBankAmount = round($grossAmount - $processingFee, 2);
            if ($netBankAmount < 0.00) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Net bank amount cannot be negative.'];
            }

            $settlementNo  = self::generateSettlementNumber();

            // Insert Card Settlement Header
            $stmtHeader = $db->prepare("
                INSERT INTO card_settlements (
                    settlement_no, branch_id, processor_id, bank_account_id, settlement_date,
                    reference_no, gross_amount, processing_fee, net_bank_amount, status, notes, created_by
                ) VALUES (
                    :no, :branch, :proc, :bank, :sdate,
                    :ref, :gross, :fee, :net, 'draft', :notes, :user
                )
            ");

            $stmtHeader->execute([
                ':no'     => $settlementNo,
                ':branch' => $branchId,
                ':proc'   => $processorId,
                ':bank'   => $bankAccountId,
                ':sdate'  => $settleDate,
                ':ref'    => $referenceNo,
                ':gross'  => $grossAmount,
                ':fee'    => $processingFee,
                ':net'    => $netBankAmount,
                ':notes'  => $notes,
                ':user'   => $userId
            ]);

            $settlementId = (int)$db->lastInsertId();

            // Insert Settlement Items
            $stmtItem = $db->prepare("
                INSERT INTO card_settlement_items (settlement_id, payment_id, gross_amount)
                VALUES (:settlement_id, :payment_id, :gross_amount)
            ");

            foreach ($validatedItems as $vi) {
                $stmtItem->execute([
                    ':settlement_id' => $settlementId,
                    ':payment_id'    => $vi['payment_id'],
                    ':gross_amount'  => $vi['gross_amount']
                ]);
            }

            $db->commit();

            Audit::log('CARD_SETTLEMENT_CREATED', 'banking', (string)$settlementId, null, [
                'settlement_no'   => $settlementNo,
                'gross_amount'    => $grossAmount,
                'net_bank_amount' => $netBankAmount
            ]);

            return [
                'success'         => true,
                'id'              => $settlementId,
                'settlement_no'   => $settlementNo,
                'gross_amount'    => $grossAmount,
                'net_bank_amount' => $netBankAmount,
                'message'         => "Card Settlement Draft {$settlementNo} created successfully."
            ];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to create card settlement: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Update Draft Card Settlement
     */
    public static function updateSettlement(int $settlementId, array $data, array $paymentItems, int $userId): array
    {
        $db = Database::getInstance()->getConnection();

        try {
            $db->beginTransaction();

            $cs = Database::fetchOne("SELECT * FROM card_settlements WHERE id = :id FOR UPDATE", [':id' => $settlementId]);
            if (!$cs) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Card settlement record not found.'];
            }

            if ($cs['status'] !== 'draft') {
                $db->rollBack();
                return ['success' => false, 'message' => "Only draft settlements can be updated. Current status: {$cs['status']}."];
            }

            $branchId      = (int)($data['branch_id'] ?? $cs['branch_id']);
            $processorId   = !empty($data['processor_id']) ? (int)$data['processor_id'] : null;
            $bankAccountId = (int)($data['bank_account_id'] ?? $cs['bank_account_id']);
            $settleDate    = !empty($data['settlement_date']) ? trim($data['settlement_date']) : $cs['settlement_date'];
            $referenceNo   = isset($data['reference_no']) ? trim($data['reference_no']) : $cs['reference_no'];
            $processingFee = round((float)($data['processing_fee'] ?? $cs['processing_fee']), 2);
            $notes         = isset($data['notes']) ? trim($data['notes']) : $cs['notes'];

            if ($processingFee < 0.00) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Processing fee cannot be negative.'];
            }

            // Verify active operational bank account
            $bank = Banking::getBankAccount($bankAccountId);
            if (!$bank || $bank['status'] !== 'active') {
                $db->rollBack();
                return ['success' => false, 'message' => 'Selected bank account is invalid or inactive.'];
            }

            if ($processorId) {
                $proc = self::getProcessor($processorId);
                if (!$proc || $proc['status'] !== 'active') {
                    $db->rollBack();
                    return ['success' => false, 'message' => 'Selected card processor is invalid or inactive.'];
                }
            }

            if (empty($paymentItems)) {
                $db->rollBack();
                return ['success' => false, 'message' => 'At least one unsettled card payment must be selected for settlement.'];
            }

            $grossAmount = 0.00;
            $validatedItems = [];
            $seenPaymentIds = [];

            foreach ($paymentItems as $idx => $item) {
                $paymentId = (int)($item['payment_id'] ?? 0);
                $itemGross = round((float)($item['gross_amount'] ?? 0), 2);

                if ($paymentId <= 0 || $itemGross <= 0.00) {
                    $db->rollBack();
                    return ['success' => false, 'message' => "Invalid payment ID or zero gross amount at item #" . ($idx + 1)];
                }

                if (in_array($paymentId, $seenPaymentIds, true)) {
                    $db->rollBack();
                    return ['success' => false, 'message' => "Duplicate payment ID #{$paymentId} in settlement item lines."];
                }
                $seenPaymentIds[] = $paymentId;

                $pmt = Database::fetchOne("
                    SELECT p.*, s.branch_id
                    FROM payments p
                    JOIN sales s ON p.sale_id = s.id
                    WHERE p.id = :pid AND LOWER(p.payment_method) = 'card' FOR UPDATE
                ", [':pid' => $paymentId]);

                if (!$pmt) {
                    $db->rollBack();
                    return ['success' => false, 'message' => "Card payment #{$paymentId} not found."];
                }

                if ((int)$pmt['branch_id'] !== $branchId) {
                    $db->rollBack();
                    return ['success' => false, 'message' => "Payment #{$paymentId} does not belong to the selected branch."];
                }

                if ($processorId && !empty($pmt['processor_id']) && (int)$pmt['processor_id'] !== $processorId) {
                    $db->rollBack();
                    return ['success' => false, 'message' => "Payment #{$paymentId} belongs to a different processor than the selected settlement processor."];
                }

                $cardRefundRow = Database::fetchOne("
                    SELECT COALESCE(SUM(r.amount), 0.00) AS total_refund
                    FROM refunds r
                    JOIN sales_returns sr ON r.sales_return_id = sr.id
                    WHERE sr.sale_id = :sale_id AND LOWER(r.refund_method) = 'card' AND sr.status != 'cancelled'
                ", [':sale_id' => $pmt['sale_id']]);
                $cardRefund = (float)($cardRefundRow['total_refund'] ?? 0);

                $unsettled = round((float)$pmt['amount'] - $cardRefund - (float)$pmt['settled_amount'], 2);
                if ($itemGross > ($unsettled + 0.001)) {
                    $db->rollBack();
                    return ['success' => false, 'message' => "Settlement amount (LKR {$itemGross}) exceeds unsettled balance (LKR {$unsettled}) for payment #{$paymentId}."];
                }

                $grossAmount += $itemGross;
                $validatedItems[] = [
                    'payment_id'   => $paymentId,
                    'gross_amount' => $itemGross
                ];
            }

            $grossAmount = round($grossAmount, 2);
            if ($processingFee > $grossAmount) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Processing fee cannot exceed gross settlement amount.'];
            }

            $netBankAmount = round($grossAmount - $processingFee, 2);
            if ($netBankAmount < 0.00) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Net bank amount cannot be negative.'];
            }

            // Update Header
            $db->prepare("
                UPDATE card_settlements SET
                    branch_id = :branch, processor_id = :proc, bank_account_id = :bank,
                    settlement_date = :sdate, reference_no = :ref, gross_amount = :gross,
                    processing_fee = :fee, net_bank_amount = :net, notes = :notes, updated_at = NOW()
                WHERE id = :id
            ")->execute([
                ':branch' => $branchId,
                ':proc'   => $processorId,
                ':bank'   => $bankAccountId,
                ':sdate'  => $settleDate,
                ':ref'    => $referenceNo,
                ':gross'  => $grossAmount,
                ':fee'    => $processingFee,
                ':net'    => $netBankAmount,
                ':notes'  => $notes,
                ':id'     => $settlementId
            ]);

            // Re-create Items
            $db->prepare("DELETE FROM card_settlement_items WHERE settlement_id = :id")->execute([':id' => $settlementId]);
            $stmtItem = $db->prepare("
                INSERT INTO card_settlement_items (settlement_id, payment_id, gross_amount)
                VALUES (:settlement_id, :payment_id, :gross_amount)
            ");

            foreach ($validatedItems as $vi) {
                $stmtItem->execute([
                    ':settlement_id' => $settlementId,
                    ':payment_id'    => $vi['payment_id'],
                    ':gross_amount'  => $vi['gross_amount']
                ]);
            }

            $db->commit();

            Audit::log('CARD_SETTLEMENT_UPDATED', 'banking', (string)$settlementId, null, [
                'gross_amount'    => $grossAmount,
                'net_bank_amount' => $netBankAmount
            ]);

            return ['success' => true, 'id' => $settlementId, 'message' => "Card Settlement {$cs['settlement_no']} updated successfully."];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to update card settlement #{$settlementId}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Delete Draft Card Settlement
     */
    public static function deleteSettlement(int $settlementId, int $userId): array
    {
        try {
            $cs = Database::fetchOne("SELECT * FROM card_settlements WHERE id = :id", [':id' => $settlementId]);
            if (!$cs) {
                return ['success' => false, 'message' => 'Card settlement record not found.'];
            }

            if ($cs['status'] !== 'draft') {
                return ['success' => false, 'message' => "Only draft card settlements can be deleted. Current status: {$cs['status']}."];
            }

            Database::execute("DELETE FROM card_settlement_items WHERE settlement_id = :id", [':id' => $settlementId]);
            Database::execute("DELETE FROM card_settlements WHERE id = :id", [':id' => $settlementId]);

            Audit::log('CARD_SETTLEMENT_DELETED', 'banking', (string)$settlementId, null, ['settlement_no' => $cs['settlement_no']]);
            return ['success' => true, 'message' => "Draft Card Settlement {$cs['settlement_no']} deleted successfully."];

        } catch (Throwable $e) {
            Logger::error("Failed to delete card settlement #{$settlementId}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Post Card Settlement to GL, Bank Register, and Payment Trackers
     */
    public static function postSettlement(int $settlementId, int $userId): array
    {
        $db = Database::getInstance()->getConnection();

        try {
            $db->beginTransaction();

            $cs = Database::fetchOne("SELECT * FROM card_settlements WHERE id = :id FOR UPDATE", [':id' => $settlementId]);
            if (!$cs) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Card settlement record not found.'];
            }

            if ($cs['status'] !== 'draft') {
                $db->rollBack();
                return ['success' => false, 'message' => "Only draft settlements can be posted. Current status: {$cs['status']}."];
            }

            // Verify active operational bank account
            $bank = Banking::getBankAccount((int)$cs['bank_account_id']);
            if (!$bank || $bank['status'] !== 'active') {
                $db->rollBack();
                return ['success' => false, 'message' => 'Selected bank account is invalid or inactive. Inactive bank accounts cannot be selected.'];
            }

            // Lock and validate items
            $items = Database::fetchAll("SELECT * FROM card_settlement_items WHERE settlement_id = :id", [':id' => $settlementId]);
            if (empty($items)) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Settlement contains no payment items.'];
            }

            foreach ($items as $item) {
                $pmt = Database::fetchOne("
                    SELECT p.*, s.branch_id
                    FROM payments p
                    JOIN sales s ON p.sale_id = s.id
                    WHERE p.id = :pid AND LOWER(p.payment_method) = 'card' FOR UPDATE
                ", [':pid' => $item['payment_id']]);

                if (!$pmt) {
                    $db->rollBack();
                    return ['success' => false, 'message' => "Payment #{$item['payment_id']} not found."];
                }

                if ((int)$pmt['branch_id'] !== (int)$cs['branch_id']) {
                    $db->rollBack();
                    return ['success' => false, 'message' => "Payment #{$item['payment_id']} does not belong to the settlement branch."];
                }

                $cardRefundRow = Database::fetchOne("
                    SELECT COALESCE(SUM(r.amount), 0.00) AS total_refund
                    FROM refunds r
                    JOIN sales_returns sr ON r.sales_return_id = sr.id
                    WHERE sr.sale_id = :sale_id AND LOWER(r.refund_method) = 'card' AND sr.status != 'cancelled'
                ", [':sale_id' => $pmt['sale_id']]);
                $cardRefund = (float)($cardRefundRow['total_refund'] ?? 0);

                $unsettled = round((float)$pmt['amount'] - $cardRefund - (float)$pmt['settled_amount'], 2);
                $grossItem = round((float)$item['gross_amount'], 2);

                if ($grossItem > ($unsettled + 0.001)) {
                    $db->rollBack();
                    return ['success' => false, 'message' => "Payment #{$pmt['id']} has already been partially/fully settled concurrently. Remaining: LKR {$unsettled}, Requested: LKR {$grossItem}."];
                }
            }

            // 1. Post Double-Entry Accounting Journal
            $accRes = Accounting::postCardSettlement($settlementId);
            if (!$accRes['success']) {
                $db->rollBack();
                return $accRes;
            }

            $journalId = (int)($accRes['journal_id'] ?? 0);

            // 2. Update Payment Settlement Statuses & Amounts
            $stmtUpdatePmt = $db->prepare("
                UPDATE payments 
                SET settled_amount = :settled_amount,
                    settlement_status = :settlement_status
                WHERE id = :id
            ");

            foreach ($items as $item) {
                $pmt = Database::fetchOne("SELECT * FROM payments WHERE id = :pid", [':pid' => $item['payment_id']]);

                $cardRefundRow = Database::fetchOne("
                    SELECT COALESCE(SUM(r.amount), 0.00) AS total_refund
                    FROM refunds r
                    JOIN sales_returns sr ON r.sales_return_id = sr.id
                    WHERE sr.sale_id = :sale_id AND LOWER(r.refund_method) = 'card' AND sr.status != 'cancelled'
                ", [':sale_id' => $pmt['sale_id']]);
                $cardRefund = (float)($cardRefundRow['total_refund'] ?? 0);

                $oldSettled = (float)$pmt['settled_amount'];
                $newSettled = round($oldSettled + (float)$item['gross_amount'], 2);
                $pmtNetReceivable = max(0.00, round((float)$pmt['amount'] - $cardRefund, 2));

                $newStatus = 'partially_settled';
                if (abs($newSettled - $pmtNetReceivable) < 0.005 || $newSettled >= $pmtNetReceivable) {
                    $newStatus = 'settled';
                }

                $stmtUpdatePmt->execute([
                    ':settled_amount'    => $newSettled,
                    ':settlement_status' => $newStatus,
                    ':id'                => $item['payment_id']
                ]);
            }

            // 3. Update Card Settlement Record to Posted
            $db->prepare("
                UPDATE card_settlements 
                SET status = 'posted', journal_entry_id = :je_id, updated_at = NOW()
                WHERE id = :id
            ")->execute([
                ':je_id' => $journalId,
                ':id'    => $settlementId
            ]);

            $db->commit();

            Audit::log('CARD_SETTLEMENT_POSTED', 'banking', (string)$settlementId, null, [
                'settlement_no'    => $cs['settlement_no'],
                'journal_entry_id' => $journalId,
                'net_bank_amount'  => $cs['net_bank_amount']
            ]);

            return [
                'success'          => true,
                'id'               => $settlementId,
                'settlement_no'    => $cs['settlement_no'],
                'journal_entry_id' => $journalId,
                'message'          => "Card Settlement {$cs['settlement_no']} posted successfully to Bank & General Ledger."
            ];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to post card settlement #{$settlementId}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Reverse Posted Card Settlement
     */
    public static function reverseSettlement(int $settlementId, string $reason, int $userId): array
    {
        $db = Database::getInstance()->getConnection();

        try {
            $db->beginTransaction();

            $cs = Database::fetchOne("SELECT * FROM card_settlements WHERE id = :id FOR UPDATE", [':id' => $settlementId]);
            if (!$cs) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Card settlement record not found.'];
            }

            if ($cs['status'] !== 'posted') {
                $db->rollBack();
                return ['success' => false, 'message' => "Only posted settlements can be reversed. Current status: {$cs['status']}."];
            }

            $reason = trim($reason);
            if (empty($reason)) {
                $db->rollBack();
                return ['success' => false, 'message' => 'A valid reversal reason must be provided.'];
            }

            // 1. Post Accounting Reversal Journal
            $accRes = Accounting::reverseCardSettlement($settlementId, $reason, $userId);
            if (!$accRes['success']) {
                $db->rollBack();
                return $accRes;
            }

            // 2. Rollback Payment Settlement Tracking
            $items = Database::fetchAll("SELECT * FROM card_settlement_items WHERE settlement_id = :id", [':id' => $settlementId]);
            $stmtUpdatePmt = $db->prepare("
                UPDATE payments 
                SET settled_amount = :settled_amount,
                    settlement_status = :settlement_status
                WHERE id = :id
            ");

            foreach ($items as $item) {
                $pmt = Database::fetchOne("SELECT * FROM payments WHERE id = :pid FOR UPDATE", [':pid' => $item['payment_id']]);
                if ($pmt) {
                    $cardRefundRow = Database::fetchOne("
                        SELECT COALESCE(SUM(r.amount), 0.00) AS total_refund
                        FROM refunds r
                        JOIN sales_returns sr ON r.sales_return_id = sr.id
                        WHERE sr.sale_id = :sale_id AND LOWER(r.refund_method) = 'card' AND sr.status != 'cancelled'
                    ", [':sale_id' => $pmt['sale_id']]);
                    $cardRefund = (float)($cardRefundRow['total_refund'] ?? 0);

                    $oldSettled = (float)$pmt['settled_amount'];
                    $grossItem  = (float)$item['gross_amount'];
                    $newSettled = max(0.00, round($oldSettled - $grossItem, 2));
                    $pmtNetReceivable = max(0.00, round((float)$pmt['amount'] - $cardRefund, 2));

                    if ($newSettled <= 0.001) {
                        $newStatus = ($pmtNetReceivable <= 0.001) ? 'settled' : 'unsettled';
                        $newSettled = 0.00;
                    } elseif (abs($newSettled - $pmtNetReceivable) < 0.005 || $newSettled >= $pmtNetReceivable) {
                        $newStatus = 'settled';
                    } else {
                        $newStatus = 'partially_settled';
                    }

                    $stmtUpdatePmt->execute([
                        ':settled_amount'    => $newSettled,
                        ':settlement_status' => $newStatus,
                        ':id'                => $item['payment_id']
                    ]);
                }
            }

            // 3. Mark Card Settlement as Reversed
            $db->prepare("
                UPDATE card_settlements 
                SET status = 'reversed', notes = CONCAT(IFNULL(notes, ''), '\n[REVERSED]: ', :reason), updated_at = NOW()
                WHERE id = :id
            ")->execute([
                ':reason' => $reason,
                ':id'     => $settlementId
            ]);

            $db->commit();

            Audit::log('CARD_SETTLEMENT_REVERSED', 'banking', (string)$settlementId, null, [
                'settlement_no' => $cs['settlement_no'],
                'reason'        => $reason
            ]);

            return [
                'success'       => true,
                'id'            => $settlementId,
                'settlement_no' => $cs['settlement_no'],
                'message'       => "Card Settlement {$cs['settlement_no']} reversed successfully."
            ];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to reverse card settlement #{$settlementId}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get single Card Settlement details with items
     */
    public static function getSettlement(int $id): ?array
    {
        try {
            $header = Database::fetchOne("
                SELECT 
                    cs.*,
                    b.name AS branch_name,
                    cp.processor_code,
                    cp.name AS processor_name,
                    cp.merchant_id,
                    ba.bank_name,
                    ba.account_name AS bank_account_name,
                    ba.account_number AS bank_account_number,
                    u.username AS creator_name,
                    je.entry_number AS journal_entry_number
                FROM card_settlements cs
                JOIN branches b ON cs.branch_id = b.id
                LEFT JOIN card_processors cp ON cs.processor_id = cp.id
                JOIN bank_accounts ba ON cs.bank_account_id = ba.id
                JOIN users u ON cs.created_by = u.id
                LEFT JOIN journal_entries je ON cs.journal_entry_id = je.id
                WHERE cs.id = :id
            ", [':id' => $id]);

            if (!$header) return null;

            $items = Database::fetchAll("
                SELECT 
                    csi.*,
                    p.created_at AS payment_date,
                    p.amount AS original_payment_amount,
                    p.settled_amount AS payment_total_settled,
                    p.settlement_status AS current_payment_status,
                    p.reference_no AS card_reference_no,
                    NULL AS approval_code,
                    NULL AS card_type,
                    NULL AS card_last4,
                    s.invoice_no AS invoice_number
                FROM card_settlement_items csi
                JOIN payments p ON csi.payment_id = p.id
                JOIN sales s ON p.sale_id = s.id
                WHERE csi.settlement_id = :id
                ORDER BY csi.id ASC
            ", [':id' => $id]);

            $header['items'] = $items;
            return $header;

        } catch (Throwable $e) {
            Logger::error("Failed to fetch card settlement #{$id} details: " . $e->getMessage(), $e);
            return null;
        }
    }

    /**
     * Get Card Settlements List with Pagination & Filtering
     */
    public static function getSettlements(array $filters = [], int $page = 1, int $limit = 20): array
    {
        try {
            $where = [];
            $params = [];

            if (!empty($filters['branch_id'])) {
                $where[] = "cs.branch_id = :branch_id";
                $params[':branch_id'] = (int)$filters['branch_id'];
            }

            if (!empty($filters['processor_id'])) {
                $where[] = "cs.processor_id = :processor_id";
                $params[':processor_id'] = (int)$filters['processor_id'];
            }

            if (!empty($filters['bank_account_id'])) {
                $where[] = "cs.bank_account_id = :bank_account_id";
                $params[':bank_account_id'] = (int)$filters['bank_account_id'];
            }

            if (!empty($filters['status'])) {
                $where[] = "cs.status = :status";
                $params[':status'] = $filters['status'];
            }

            if (!empty($filters['start_date'])) {
                $where[] = "cs.settlement_date >= :start_date";
                $params[':start_date'] = $filters['start_date'];
            }

            if (!empty($filters['end_date'])) {
                $where[] = "cs.settlement_date <= :end_date";
                $params[':end_date'] = $filters['end_date'];
            }

            if (!empty($filters['search'])) {
                $where[] = "(cs.settlement_no LIKE :search OR cs.reference_no LIKE :search OR cs.notes LIKE :search)";
                $params[':search'] = '%' . trim($filters['search']) . '%';
            }

            $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

            $countRow = Database::fetchOne("
                SELECT COUNT(*) AS total FROM card_settlements cs $whereSql
            ", $params);

            $totalItems = (int)($countRow['total'] ?? 0);
            $offset = ($page - 1) * $limit;

            $items = Database::fetchAll("
                SELECT 
                    cs.*,
                    b.name AS branch_name,
                    cp.name AS processor_name,
                    ba.bank_name,
                    ba.account_name AS bank_account_name,
                    u.username AS creator_name
                FROM card_settlements cs
                JOIN branches b ON cs.branch_id = b.id
                LEFT JOIN card_processors cp ON cs.processor_id = cp.id
                JOIN bank_accounts ba ON cs.bank_account_id = ba.id
                JOIN users u ON cs.created_by = u.id
                $whereSql
                ORDER BY cs.settlement_date DESC, cs.id DESC
                LIMIT $limit OFFSET $offset
            ", $params);

            return [
                'items'      => $items,
                'totalItems' => $totalItems,
                'page'       => $page,
                'limit'      => $limit,
                'totalPages' => max(1, ceil($totalItems / $limit))
            ];

        } catch (Throwable $e) {
            Logger::error("Failed to fetch card settlements list: " . $e->getMessage(), $e);
            return ['items' => [], 'totalItems' => 0, 'page' => 1, 'limit' => $limit, 'totalPages' => 1];
        }
    }
}
