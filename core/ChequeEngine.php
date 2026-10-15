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

            if (!empty($filters['status'])) {
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
                $where[] = "(sc.cheque_number LIKE :search1 OR sc.bank_name LIKE :search2 OR s.name LIKE :search3)";
                $params[':search1'] = '%' . trim($filters['search']) . '%';
                $params[':search2'] = '%' . trim($filters['search']) . '%';
                $params[':search3'] = '%' . trim($filters['search']) . '%';
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

            return Database::fetchAll("
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
                $whereSql
                ORDER BY sc.id DESC
            ", $params);
        } catch (Throwable $e) {
            Logger::error("Failed to fetch supplier cheques: " . $e->getMessage(), $e);
            return [];
        }
    }

    /**
     * Get paginated list of supplier cheques
     */
    public static function getPaginatedCheques(array $filters = [], int $page = 1, int $limit = 15): array
    {
        try {
            $where = [];
            $params = [];

            if (!empty($filters['status'])) {
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
                $where[] = "(sc.cheque_number LIKE :search1 OR sc.bank_name LIKE :search2 OR s.name LIKE :search3)";
                $params[':search1'] = '%' . trim($filters['search']) . '%';
                $params[':search2'] = '%' . trim($filters['search']) . '%';
                $params[':search3'] = '%' . trim($filters['search']) . '%';
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

            // Fetch total count for pagination
            $countRow = Database::fetchOne("
                SELECT COUNT(*) AS c 
                FROM supplier_cheques sc 
                JOIN suppliers s ON sc.supplier_id = s.id 
                $whereSql
            ", $params);
            $totalItems = (int)($countRow['c'] ?? 0);
            $totalPages = (int)ceil($totalItems / $limit);
            $page = max(1, min($page, max(1, $totalPages)));
            $offset = ($page - 1) * $limit;

            $items = Database::fetchAll("
                SELECT 
                    sc.id, sc.cheque_number, sc.cheque_date, sc.amount, sc.status, sc.bank_name, sc.payee_name, sc.remarks, sc.created_at,
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
                $whereSql
                ORDER BY sc.id DESC
                LIMIT $limit OFFSET $offset
            ", $params);

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
                // Fetch GRN allocations for this payment
                $cheque['allocations'] = Database::fetchAll("
                    SELECT spg.*, g.grn_number, g.actual_grn_total, g.paid_amount, g.payment_status
                    FROM supplier_payment_grns spg
                    JOIN grns g ON spg.grn_id = g.id
                    WHERE spg.supplier_payment_id = :sp_id
                ", [':sp_id' => $cheque['supplier_payment_id']]);
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

            if ($chequeId <= 0 || $bankAccountId <= 0) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Valid cheque selection and real bank account are required.'];
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
                SET status = 'cleared', bank_account_id = :bank_acc, cleared_at = NOW(), cleared_by = :user, journal_entry_id = :je_id
                WHERE id = :id
            ")->execute([
                ':bank_acc' => $bankAccountId,
                ':user'     => $userId,
                ':je_id'    => $clearingJournalId,
                ':id'       => $chequeId
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
}
