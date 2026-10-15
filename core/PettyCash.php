<?php
/**
 * Curtiss POS - Petty Cash & Expense Core Engine (Stage 12 & Section 10 Enhancements)
 * Manages branch-specific petty cash balances, expense categories & COA mapping,
 * sequential expense vouchers (Draft & Approved/Posted), bank / cash-in-hand reimbursements, drawer movement integration,
 * manual adjustments, voucher reversal, sub-ledger register, and GL double-entry accounting.
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Audit.php';
require_once __DIR__ . '/Accounting.php';
require_once __DIR__ . '/BusinessDay.php';
require_once __DIR__ . '/Banking.php';

class PettyCash
{
    /**
     * Generate continuous, collision-proof voucher reference numbers (PCV-0001, PCR-0001, PCA-0001)
     */
    public static function generateNumber(string $prefix, string $table): string
    {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT MAX(id) AS max_id FROM {$table}");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $nextNum = ((int)($row['max_id'] ?? 0)) + 1;

            $field = ($table === 'petty_cash_vouchers') ? 'voucher_no' :
                     (($table === 'petty_cash_reimbursements') ? 'reimbursement_no' : 'adjustment_no');

            while (true) {
                $candidate = $prefix . '-' . sprintf('%04d', $nextNum);
                $chk = Database::fetchOne("SELECT id FROM {$table} WHERE {$field} = :no", [':no' => $candidate]);
                if (!$chk) {
                    return $candidate;
                }
                $nextNum++;
            }
        } catch (Throwable $e) {
            Logger::error("Failed to generate {$prefix} number: " . $e->getMessage(), $e);
            return $prefix . '-' . sprintf('%04d', rand(1000, 9999));
        }
    }

    /**
     * Get active Petty Cash GL Balance for a specific branch (Account 1015)
     */
    public static function getPettyCashBalance(int $branchId): float
    {
        try {
            $pcAccId = Accounting::getAccountIdByCode(Accounting::CODE_PETTY_CASH);
            $row = Database::fetchOne("
                SELECT SUM(jl.debit) - SUM(jl.credit) AS balance
                FROM journal_lines jl
                JOIN journal_entries je ON jl.journal_entry_id = je.id
                WHERE jl.account_id = :acc_id 
                  AND je.branch_id = :branch_id 
                  AND je.posting_status = 'posted'
            ", [':acc_id' => $pcAccId, ':branch_id' => $branchId]);

            return round((float)($row['balance'] ?? 0.00), 2);
        } catch (Throwable $e) {
            Logger::error("Failed to fetch petty cash balance for branch #{$branchId}: " . $e->getMessage(), $e);
            return 0.00;
        }
    }

    /**
     * Get Petty Cash Dashboard Summary Statistics for a branch
     */
    public static function getDashboardSummary(int $branchId): array
    {
        try {
            $balance = self::getPettyCashBalance($branchId);

            $expRow = Database::fetchOne("
                SELECT SUM(amount) AS total_expenses, COUNT(*) AS count
                FROM petty_cash_vouchers
                WHERE branch_id = :branch_id AND status = 'posted'
            ", [':branch_id' => $branchId]);

            $reimbRow = Database::fetchOne("
                SELECT SUM(amount) AS total_reimbursements
                FROM petty_cash_reimbursements
                WHERE branch_id = :branch_id
            ", [':branch_id' => $branchId]);

            $adjRow = Database::fetchOne("
                SELECT 
                    SUM(CASE WHEN adjustment_type = 'positive' THEN amount ELSE 0 END) AS pos_adj,
                    SUM(CASE WHEN adjustment_type = 'negative' THEN amount ELSE 0 END) AS neg_adj
                FROM petty_cash_adjustments
                WHERE branch_id = :branch_id
            ", [':branch_id' => $branchId]);

            return [
                'current_balance'      => $balance,
                'total_expenses'       => round((float)($expRow['total_expenses'] ?? 0.00), 2),
                'total_vouchers_count' => (int)($expRow['count'] ?? 0),
                'total_reimbursements' => round((float)($reimbRow['total_reimbursements'] ?? 0.00), 2),
                'net_adjustments'      => round((float)($adjRow['pos_adj'] ?? 0) - (float)($adjRow['neg_adj'] ?? 0), 2)
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to fetch petty cash summary: " . $e->getMessage(), $e);
            return [
                'current_balance' => 0.00, 'total_expenses' => 0.00,
                'total_vouchers_count' => 0, 'total_reimbursements' => 0.00, 'net_adjustments' => 0.00
            ];
        }
    }

    /**
     * Fetch all Expense Categories (active or all)
     */
    public static function getCategories(bool $activeOnly = true): array
    {
        try {
            $where = $activeOnly ? "WHERE ec.status = 'active'" : "";
            return Database::fetchAll("
                SELECT ec.*, coa.account_code, coa.account_name
                FROM expense_categories ec
                JOIN chart_of_accounts coa ON ec.account_id = coa.id
                $where
                ORDER BY ec.name ASC
            ");
        } catch (Throwable $e) {
            Logger::error("Failed to fetch expense categories: " . $e->getMessage(), $e);
            return [];
        }
    }

    /**
     * Save / Update Expense Category & COA Mapping
     */
    public static function saveCategory(array $data, int $userId): array
    {
        try {
            $id = !empty($data['id']) ? (int)$data['id'] : null;
            $name = trim($data['name'] ?? '');
            $code = strtoupper(trim($data['code'] ?? ''));
            $accountId = (int)($data['account_id'] ?? 0);
            $description = trim($data['description'] ?? '') ?: null;
            $status = ($data['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

            if (empty($name) || empty($code) || $accountId <= 0) {
                return ['success' => false, 'message' => 'Category name, code, and valid COA account are required.'];
            }

            // Verify account exists and is an expense account (5xxx)
            $acc = Database::fetchOne("
                SELECT id, account_code, account_type, account_name 
                FROM chart_of_accounts 
                WHERE id = :id AND status = 'active'
            ", [':id' => $accountId]);

            if (!$acc) {
                return ['success' => false, 'message' => 'Selected COA account does not exist or is inactive.'];
            }

            $accCode = (string)($acc['account_code'] ?? '');
            $accType = strtolower((string)($acc['account_type'] ?? ''));

            if (strpos($accCode, '5') !== 0 && strpos($accType, 'expense') === false) {
                return ['success' => false, 'message' => "Category must map to a valid expense GL account (5xxx). Account '{$accCode}' is not an expense account."];
            }

            // Code uniqueness
            $dupSql = "SELECT id FROM expense_categories WHERE code = :code";
            $dupParams = [':code' => $code];
            if ($id) {
                $dupSql .= " AND id != :id";
                $dupParams[':id'] = $id;
            }
            if (Database::fetchOne($dupSql, $dupParams)) {
                return ['success' => false, 'message' => "Category code '{$code}' is already assigned."];
            }

            if ($id) {
                Database::execute("
                    UPDATE expense_categories SET
                        name = :name, code = :code, account_id = :account_id,
                        description = :description, status = :status
                    WHERE id = :id
                ", [
                    ':name'        => $name,
                    ':code'        => $code,
                    ':account_id'  => $accountId,
                    ':description' => $description,
                    ':status'      => $status,
                    ':id'          => $id
                ]);

                Audit::log('EXPENSE_CATEGORY_UPDATED', 'petty_cash', (string)$id, null, [
                    'name' => $name, 'code' => $code, 'account_id' => $accountId
                ]);

                return ['success' => true, 'message' => "Expense category '{$name}' updated successfully."];
            } else {
                Database::execute("
                    INSERT INTO expense_categories (name, code, account_id, description, status)
                    VALUES (:name, :code, :account_id, :description, :status)
                ", [
                    ':name'        => $name,
                    ':code'        => $code,
                    ':account_id'  => $accountId,
                    ':description' => $description,
                    ':status'      => $status
                ]);

                $newId = (int)Database::lastInsertId();

                Audit::log('EXPENSE_CATEGORY_CREATED', 'petty_cash', (string)$newId, null, [
                    'name' => $name, 'code' => $code, 'account_id' => $accountId
                ]);

                return ['success' => true, 'id' => $newId, 'message' => "Expense category '{$name}' created successfully."];
            }

        } catch (Throwable $e) {
            Logger::error("Failed to save expense category: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Delete Expense Category safely (blocked if associated vouchers exist)
     */
    public static function deleteCategory(int $id): array
    {
        try {
            $cat = Database::fetchOne("SELECT * FROM expense_categories WHERE id = :id", [':id' => $id]);
            if (!$cat) {
                return ['success' => false, 'message' => 'Expense category not found.'];
            }

            $usage = Database::fetchOne("SELECT COUNT(*) AS total FROM petty_cash_vouchers WHERE category_id = :id", [':id' => $id]);
            if (!empty($usage['total'])) {
                return [
                    'success' => false,
                    'message' => 'Cannot delete expense category that has associated vouchers. Deactivate it instead.'
                ];
            }

            Database::execute("DELETE FROM expense_categories WHERE id = :id", [':id' => $id]);

            Audit::log('EXPENSE_CATEGORY_DELETED', 'petty_cash', (string)$id, null, ['name' => $cat['name']]);

            return ['success' => true, 'message' => "Expense category '{$cat['name']}' deleted successfully."];
        } catch (Throwable $e) {
            Logger::error("Failed to delete category #{$id}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Create Petty Cash Expense Voucher (Draft or Posted) inside atomic DB transaction
     */
    public static function createVoucher(array $data, int $userId, int $branchId): array
    {
        $db = Database::getInstance()->getConnection();

        try {
            $db->beginTransaction();

            $categoryId   = (int)($data['category_id'] ?? 0);
            $amount       = round((float)($data['amount'] ?? 0), 2);
            $payee        = trim($data['payee'] ?? '');
            $description  = trim($data['description'] ?? '');
            $referenceNo  = !empty($data['reference_no']) ? trim($data['reference_no']) : null;
            $receiptRef   = !empty($data['receipt_ref']) ? trim($data['receipt_ref']) : null;
            $voucherDate  = !empty($data['voucher_date']) ? trim($data['voucher_date']) : date('Y-m-d');
            $targetStatus = strtolower(trim($data['status'] ?? 'posted'));

            if (!in_array($targetStatus, ['draft', 'posted'])) {
                $targetStatus = 'posted';
            }

            if ($categoryId <= 0 || $amount <= 0.00 || empty($payee) || empty($description)) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Category, valid positive amount (> 0), payee, and description are required.'];
            }

            // Verify Category & mapped COA account
            $cat = Database::fetchOne("SELECT * FROM expense_categories WHERE id = :id AND status = 'active'", [':id' => $categoryId]);
            if (!$cat) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Expense category not found or inactive.'];
            }

            $expAccountId = (int)$cat['account_id'];
            $pcAccountId  = Accounting::getAccountIdByCode(Accounting::CODE_PETTY_CASH);

            // If status is 'posted', verify Petty Cash Balance constraint: Balance must NEVER fall below zero
            if ($targetStatus === 'posted') {
                $currentBal = self::getPettyCashBalance($branchId);
                if ($amount > ($currentBal + 0.001)) {
                    $db->rollBack();
                    return [
                        'success' => false,
                        'message' => "Insufficient Petty Cash balance. Available: LKR " . number_format($currentBal, 2) . ", Requested: LKR " . number_format($amount, 2) . "."
                    ];
                }
            }

            $voucherNo = self::generateNumber('PCV', 'petty_cash_vouchers');

            // 1. Insert Voucher Header
            $stmtV = $db->prepare("
                INSERT INTO petty_cash_vouchers (
                    voucher_no, branch_id, voucher_date, category_id, account_id,
                    amount, payee, description, reference_no, receipt_ref, status, created_by
                ) VALUES (
                    :voucher_no, :branch_id, :voucher_date, :category_id, :account_id,
                    :amount, :payee, :description, :reference_no, :receipt_ref, :status, :created_by
                )
            ");

            $stmtV->execute([
                ':voucher_no'   => $voucherNo,
                ':branch_id'    => $branchId,
                ':voucher_date' => $voucherDate,
                ':category_id'  => $categoryId,
                ':account_id'   => $expAccountId,
                ':amount'       => $amount,
                ':payee'        => $payee,
                ':description'  => $description,
                ':reference_no' => $referenceNo,
                ':receipt_ref'  => $receiptRef,
                ':status'       => $targetStatus,
                ':created_by'   => $userId
            ]);

            $voucherId = (int)$db->lastInsertId();

            if ($targetStatus === 'draft') {
                // Draft voucher: No accounting GL entry, no petty cash balance reduction
                $db->commit();
                Audit::log('PETTY_CASH_EXPENSE_DRAFT_CREATED', 'petty_cash', (string)$voucherId, null, [
                    'voucher_no' => $voucherNo, 'amount' => $amount, 'payee' => $payee
                ]);
                return [
                    'success'    => true,
                    'voucher_id' => $voucherId,
                    'voucher_no' => $voucherNo,
                    'amount'     => $amount,
                    'status'     => 'draft',
                    'message'    => "Draft Petty Cash Voucher {$voucherNo} created successfully."
                ];
            }

            // 2. Post Accounting Double-Entry (Dr Expense Account, Cr Petty Cash 1015)
            $lines = [
                ['account_id' => $expAccountId, 'debit' => $amount, 'credit' => 0.00, 'memo' => "Petty Cash Expense: {$cat['name']}"],
                ['account_id' => $pcAccountId, 'debit' => 0.00, 'credit' => $amount, 'memo' => "Petty Cash Voucher #{$voucherNo}"]
            ];

            $header = [
                'branch_id'   => $branchId,
                'entry_date'  => $voucherDate,
                'source_type' => 'manual',
                'source_id'   => $voucherId,
                'description' => "Petty Cash Expense Voucher #{$voucherNo} ({$payee} - {$cat['name']})",
                'created_by'  => $userId
            ];

            $jeRes = Accounting::postJournalEntry($header, $lines);
            if (!$jeRes['success']) {
                $db->rollBack();
                return ['success' => false, 'message' => "Accounting posting failed: " . $jeRes['message']];
            }

            $jeId = (int)$jeRes['journal_entry_id'];

            // Update voucher record with journal_entry_id
            $db->prepare("UPDATE petty_cash_vouchers SET journal_entry_id = :je_id WHERE id = :id")
               ->execute([':je_id' => $jeId, ':id' => $voucherId]);

            $db->commit();

            Audit::log('PETTY_CASH_EXPENSE_CREATED', 'petty_cash', (string)$voucherId, null, [
                'voucher_no' => $voucherNo, 'amount' => $amount, 'payee' => $payee, 'category' => $cat['name']
            ]);

            return [
                'success'    => true,
                'voucher_id' => $voucherId,
                'voucher_no' => $voucherNo,
                'amount'     => $amount,
                'status'     => 'posted',
                'message'    => "Petty Cash Voucher {$voucherNo} for LKR " . number_format($amount, 2) . " created and posted successfully."
            ];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to create petty cash voucher: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Approve and Post a Draft Petty Cash Voucher
     */
    public static function approveVoucher(int $voucherId, int $userId, int $branchId): array
    {
        $db = Database::getInstance()->getConnection();

        try {
            $db->beginTransaction();

            $voucher = Database::fetchOne("
                SELECT * FROM petty_cash_vouchers WHERE id = :id AND branch_id = :branch_id FOR UPDATE
            ", [':id' => $voucherId, ':branch_id' => $branchId]);

            if (!$voucher) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Petty cash voucher not found.'];
            }

            if ($voucher['status'] !== 'draft') {
                $db->rollBack();
                return ['success' => false, 'message' => "Voucher is not in Draft status (current status: {$voucher['status']})."];
            }

            $amount = (float)$voucher['amount'];

            // Verify Petty Cash balance constraint
            $currentBal = self::getPettyCashBalance($branchId);
            if ($amount > ($currentBal + 0.001)) {
                $db->rollBack();
                return [
                    'success' => false,
                    'message' => "Insufficient Petty Cash balance to approve voucher. Available: LKR " . number_format($currentBal, 2) . ", Requested: LKR " . number_format($amount, 2) . "."
                ];
            }

            $expAccountId = (int)$voucher['account_id'];
            $pcAccountId  = Accounting::getAccountIdByCode(Accounting::CODE_PETTY_CASH);

            // Post Accounting Double-Entry
            $lines = [
                ['account_id' => $expAccountId, 'debit' => $amount, 'credit' => 0.00, 'memo' => "Petty Cash Expense Approved"],
                ['account_id' => $pcAccountId, 'debit' => 0.00, 'credit' => $amount, 'memo' => "Petty Cash Voucher #{$voucher['voucher_no']} Approved"]
            ];

            $header = [
                'branch_id'   => $branchId,
                'entry_date'  => date('Y-m-d'),
                'source_type' => 'manual',
                'source_id'   => $voucherId,
                'description' => "Approved Petty Cash Expense Voucher #{$voucher['voucher_no']}",
                'created_by'  => $userId
            ];

            $jeRes = Accounting::postJournalEntry($header, $lines);
            if (!$jeRes['success']) {
                $db->rollBack();
                return ['success' => false, 'message' => "Accounting posting failed: " . $jeRes['message']];
            }

            $jeId = (int)$jeRes['journal_entry_id'];

            $db->prepare("UPDATE petty_cash_vouchers SET status = 'posted', journal_entry_id = :je_id WHERE id = :id")
               ->execute([':je_id' => $jeId, ':id' => $voucherId]);

            $db->commit();

            Audit::log('PETTY_CASH_VOUCHER_APPROVED', 'petty_cash', (string)$voucherId, null, [
                'voucher_no' => $voucher['voucher_no'], 'amount' => $amount
            ]);

            return [
                'success' => true,
                'message' => "Draft Voucher {$voucher['voucher_no']} approved and posted successfully."
            ];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to approve voucher #{$voucherId}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Cancel / Reverse a Petty Cash Expense Voucher
     */
    public static function reverseVoucher(int $voucherId, string $reason, int $userId, int $branchId): array
    {
        $db = Database::getInstance()->getConnection();

        try {
            $db->beginTransaction();

            $voucher = Database::fetchOne("
                SELECT * FROM petty_cash_vouchers WHERE id = :id AND branch_id = :branch_id FOR UPDATE
            ", [':id' => $voucherId, ':branch_id' => $branchId]);

            if (!$voucher) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Petty cash voucher not found.'];
            }

            if ($voucher['status'] === 'reversed') {
                $db->rollBack();
                return ['success' => false, 'message' => 'Double-reversal prevented: Voucher is already reversed.'];
            }

            $reason = trim($reason);
            if (empty($reason)) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Reversal reason is required.'];
            }

            $amount       = (float)$voucher['amount'];
            $expAccountId = (int)$voucher['account_id'];
            $pcAccountId  = Accounting::getAccountIdByCode(Accounting::CODE_PETTY_CASH);

            // Post Reversal Journal Entry: Dr Petty Cash (1015), Cr Expense Account
            $lines = [
                ['account_id' => $pcAccountId, 'debit' => $amount, 'credit' => 0.00, 'memo' => "Reversal of Voucher #{$voucher['voucher_no']}"],
                ['account_id' => $expAccountId, 'debit' => 0.00, 'credit' => $amount, 'memo' => "Expense Voucher Cancellation Offset"]
            ];

            $header = [
                'branch_id'   => $branchId,
                'entry_date'  => date('Y-m-d'),
                'source_type' => 'reversal',
                'source_id'   => $voucherId,
                'description' => "Reversal of Petty Cash Voucher #{$voucher['voucher_no']}: {$reason}",
                'created_by'  => $userId
            ];

            $jeRes = Accounting::postJournalEntry($header, $lines);
            if (!$jeRes['success']) {
                $db->rollBack();
                return ['success' => false, 'message' => "Reversal accounting posting failed: " . $jeRes['message']];
            }

            $reversalJeId = (int)$jeRes['journal_entry_id'];

            // Update voucher record status to reversed
            $db->prepare("
                UPDATE petty_cash_vouchers SET
                    status = 'reversed',
                    reversed_at = CURRENT_TIMESTAMP,
                    reversed_by = :userId,
                    reversal_reason = :reason,
                    reversal_journal_entry_id = :rJeId
                WHERE id = :id
            ")->execute([
                ':userId' => $userId,
                ':reason' => $reason,
                ':rJeId'  => $reversalJeId,
                ':id'     => $voucherId
            ]);

            $db->commit();

            Audit::log('PETTY_CASH_VOUCHER_REVERSED', 'petty_cash', (string)$voucherId, null, [
                'voucher_no' => $voucher['voucher_no'], 'amount' => $amount, 'reason' => $reason
            ]);

            return [
                'success' => true,
                'message' => "Petty Cash Voucher {$voucher['voucher_no']} reversed successfully. Balance restored."
            ];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to reverse petty cash voucher #{$voucherId}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Reimburse / Top-Up Petty Cash Fund (from Bank or Cash in Hand)
     */
    public static function reimburseFund(array $data, int $userId, int $branchId): array
    {
        $db = Database::getInstance()->getConnection();

        try {
            $db->beginTransaction();

            $sourceType    = strtolower(trim($data['source_type'] ?? 'bank'));
            $amount        = round((float)($data['amount'] ?? 0), 2);
            $referenceNo   = !empty($data['reference_no']) ? trim($data['reference_no']) : null;
            $notes         = !empty($data['notes']) ? trim($data['notes']) : null;
            $reimbDate     = !empty($data['reimbursement_date']) ? trim($data['reimbursement_date']) : date('Y-m-d');
            $bankAccountId = !empty($data['bank_account_id']) ? (int)$data['bank_account_id'] : null;

            if (!in_array($sourceType, ['bank', 'cash_in_hand']) || $amount <= 0.00) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Valid funding source (bank or cash_in_hand) and positive amount (> 0) are required.'];
            }

            $reimbNo     = self::generateNumber('PCR', 'petty_cash_reimbursements');
            $pcAccountId = Accounting::getAccountIdByCode(Accounting::CODE_PETTY_CASH);

            if ($sourceType === 'cash_in_hand') {
                // Cash in Hand reimbursement is taken from physical POS Cash Drawer!
                // Must verify active open business day for branch
                $activeDay = BusinessDay::getOrCreateActiveDay($branchId, $userId);
                if (!$activeDay) {
                    $db->rollBack();
                    return [
                        'success' => false,
                        'message' => 'Cannot reimburse from Cash in Hand. No active business day is currently open for this branch.'
                    ];
                }

                $creditAccountId = Accounting::getAccountIdByCode(Accounting::CODE_CASH);
                $sourceLabel = 'Cash in Hand Drawer';
                $bankAccountId = null; // No bank account for cash in hand

            } else { // bank
                // Bank-funded reimbursement does NOT affect POS Cash Drawer
                if ($bankAccountId) {
                    $bankAcc = Banking::getBankAccount($bankAccountId);
                    if (!$bankAcc || $bankAcc['status'] !== 'active') {
                        $db->rollBack();
                        return ['success' => false, 'message' => 'Selected bank account does not exist or is inactive.'];
                    }
                    $creditAccountId = (int)$bankAcc['account_id']; // Dedicated Bank COA
                    $sourceLabel = "Bank Account ({$bankAcc['bank_name']} - {$bankAcc['account_number']})";
                } else {
                    // Try to find first active bank account for branch or global
                    $allBankAccs = Banking::getBankAccounts($branchId, true);
                    if (!empty($allBankAccs)) {
                        $bankAcc = $allBankAccs[0];
                        $bankAccountId = (int)$bankAcc['id'];
                        $creditAccountId = (int)$bankAcc['account_id'];
                        $sourceLabel = "Bank Account ({$bankAcc['bank_name']})";
                    } else {
                        $creditAccountId = Accounting::getAccountIdByCode(Accounting::CODE_BANK);
                        $sourceLabel = 'Bank Account';
                        $bankAccountId = null;
                    }
                }
            }

            // 1. Create Reimbursement Header Record
            $stmtR = $db->prepare("
                INSERT INTO petty_cash_reimbursements (
                    reimbursement_no, branch_id, bank_account_id, reimbursement_date, source_type,
                    amount, reference_no, notes, created_by
                ) VALUES (
                    :no, :branch, :bank_acc, :rdate, :stype,
                    :amount, :ref, :notes, :user
                )
            ");

            $stmtR->execute([
                ':no'       => $reimbNo,
                ':branch'   => $branchId,
                ':bank_acc' => $bankAccountId,
                ':rdate'    => $reimbDate,
                ':stype'    => $sourceType,
                ':amount'   => $amount,
                ':ref'      => $referenceNo,
                ':notes'    => $notes,
                ':user'     => $userId
            ]);

            $reimbursementId = (int)$db->lastInsertId();

            // 2. Drawer Movement Integration (ONLY if source_type === 'cash_in_hand')
            if ($sourceType === 'cash_in_hand' && isset($activeDay)) {
                $mvRes = BusinessDay::recordDrawerMovement([
                    'business_day_id' => (int)$activeDay['id'],
                    'branch_id'       => $branchId,
                    'movement_type'   => 'cash_out',
                    'source_type'     => 'petty_cash_reimbursement',
                    'source_id'       => $reimbursementId,
                    'amount'          => $amount,
                    'direction'       => 'out',
                    'reason'          => "Petty Cash Fund Reimbursement ({$reimbNo})",
                    'notes'           => $notes,
                    'user_id'         => $userId
                ]);

                if (!$mvRes['success']) {
                    $db->rollBack();
                    return $mvRes;
                }
            }

            // 3. Post Accounting Journal Entry (Dr Petty Cash 1015, Cr Bank COA / Cash 1010)
            $lines = [
                ['account_id' => $pcAccountId, 'debit' => $amount, 'credit' => 0.00, 'memo' => "Petty Cash Replenishment from {$sourceLabel}"],
                ['account_id' => $creditAccountId, 'debit' => 0.00, 'credit' => $amount, 'memo' => "Petty Cash Reimbursement #{$reimbNo}"]
            ];

            $header = [
                'branch_id'   => $branchId,
                'entry_date'  => $reimbDate,
                'source_type' => 'manual',
                'source_id'   => $reimbursementId,
                'description' => "Petty Cash Fund Reimbursement #{$reimbNo} from {$sourceLabel}",
                'created_by'  => $userId
            ];

            $jeRes = Accounting::postJournalEntry($header, $lines);
            if (!$jeRes['success']) {
                $db->rollBack();
                return ['success' => false, 'message' => "Accounting posting failed: " . $jeRes['message']];
            }

            $jeId = (int)$jeRes['journal_entry_id'];

            $db->prepare("UPDATE petty_cash_reimbursements SET journal_entry_id = :je_id WHERE id = :id")
               ->execute([':je_id' => $jeId, ':id' => $reimbursementId]);

            $db->commit();

            Audit::log('PETTY_CASH_REIMBURSED', 'petty_cash', (string)$reimbursementId, null, [
                'reimbursement_no' => $reimbNo, 'source_type' => $sourceType, 'amount' => $amount
            ]);

            return [
                'success'          => true,
                'reimbursement_id' => $reimbursementId,
                'reimbursement_no' => $reimbNo,
                'amount'           => $amount,
                'message'          => "Petty Cash fund replenished by LKR " . number_format($amount, 2) . " from {$sourceLabel}."
            ];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to reimburse petty cash: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Manual Petty Cash Adjustment (Positive / Negative) with Accounting
     */
    public static function adjustFund(array $data, int $userId, int $branchId): array
    {
        $db = Database::getInstance()->getConnection();

        try {
            $db->beginTransaction();

            $adjType = strtolower(trim($data['adjustment_type'] ?? 'positive'));
            $amount  = round((float)($data['amount'] ?? 0), 2);
            $reason  = trim($data['reason'] ?? '');

            if (!in_array($adjType, ['positive', 'negative']) || $amount <= 0.00 || empty($reason)) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Adjustment type, positive amount (> 0), and mandatory reason are required.'];
            }

            // If negative adjustment, verify available balance
            if ($adjType === 'negative') {
                $currentBal = self::getPettyCashBalance($branchId);
                if ($amount > ($currentBal + 0.001)) {
                    $db->rollBack();
                    return [
                        'success' => false,
                        'message' => "Insufficient Petty Cash balance for negative adjustment. Available: LKR " . number_format($currentBal, 2) . ", Requested: LKR " . number_format($amount, 2) . "."
                    ];
                }
            }

            $adjNo       = self::generateNumber('PCA', 'petty_cash_adjustments');
            $pcAccountId = Accounting::getAccountIdByCode(Accounting::CODE_PETTY_CASH);
            $expAccountId = Accounting::getAccountIdByCode(Accounting::CODE_CASH_SHORTAGE_OVERAGE); // 5040

            // 1. Create Adjustment Record
            $stmtA = $db->prepare("
                INSERT INTO petty_cash_adjustments (
                    adjustment_no, branch_id, adjustment_type, amount, reason, created_by
                ) VALUES (
                    :no, :branch, :atype, :amount, :reason, :user
                )
            ");

            $stmtA->execute([
                ':no'     => $adjNo,
                ':branch' => $branchId,
                ':atype'  => $adjType,
                ':amount' => $amount,
                ':reason' => $reason,
                ':user'   => $userId
            ]);

            $adjustmentId = (int)$db->lastInsertId();

            // 2. Post Accounting Journal Entry
            if ($adjType === 'positive') {
                // Dr Petty Cash (1015), Cr Cash Shortage / Overage (5040)
                $lines = [
                    ['account_id' => $pcAccountId, 'debit' => $amount, 'credit' => 0.00, 'memo' => "Positive Petty Cash Adjustment: {$reason}"],
                    ['account_id' => $expAccountId, 'debit' => 0.00, 'credit' => $amount, 'memo' => "Petty Cash Adjustment Offset"]
                ];
            } else { // negative
                // Dr Cash Shortage / Overage (5040), Cr Petty Cash (1015)
                $lines = [
                    ['account_id' => $expAccountId, 'debit' => $amount, 'credit' => 0.00, 'memo' => "Negative Petty Cash Adjustment: {$reason}"],
                    ['account_id' => $pcAccountId, 'debit' => 0.00, 'credit' => $amount, 'memo' => "Petty Cash Reduction"]
                ];
            }

            $header = [
                'branch_id'   => $branchId,
                'entry_date'  => date('Y-m-d'),
                'source_type' => 'manual',
                'source_id'   => $adjustmentId,
                'description' => "Petty Cash " . ucfirst($adjType) . " Adjustment #{$adjNo}: {$reason}",
                'created_by'  => $userId
            ];

            $jeRes = Accounting::postJournalEntry($header, $lines);
            if (!$jeRes['success']) {
                $db->rollBack();
                return ['success' => false, 'message' => "Accounting posting failed: " . $jeRes['message']];
            }

            $jeId = (int)$jeRes['journal_entry_id'];

            $db->prepare("UPDATE petty_cash_adjustments SET journal_entry_id = :je_id WHERE id = :id")
               ->execute([':je_id' => $jeId, ':id' => $adjustmentId]);

            $db->commit();

            Audit::log('PETTY_CASH_ADJUSTED', 'petty_cash', (string)$adjustmentId, null, [
                'adjustment_no' => $adjNo, 'adjustment_type' => $adjType, 'amount' => $amount, 'reason' => $reason
            ]);

            return [
                'success'       => true,
                'adjustment_id' => $adjustmentId,
                'adjustment_no' => $adjNo,
                'amount'        => $amount,
                'message'       => "Petty Cash " . ucfirst($adjType) . " Adjustment of LKR " . number_format($amount, 2) . " processed successfully."
            ];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to adjust petty cash: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get Petty Cash Sub-Ledger Register Feed with Running Balance
     */
    public static function getRegisterList(int $branchId): array
    {
        try {
            $entries = [];

            // 1. Reimbursements
            $reimbs = Database::fetchAll("
                SELECT reimbursement_no AS ref_no, reimbursement_date AS tx_date,
                       'reimbursement' AS tx_type, amount, notes AS description,
                       created_at
                FROM petty_cash_reimbursements
                WHERE branch_id = :branch_id
            ", [':branch_id' => $branchId]);

            foreach ($reimbs as $r) {
                $entries[] = [
                    'ref_no'      => $r['ref_no'],
                    'date'        => $r['tx_date'],
                    'type'        => 'reimbursement',
                    'description' => "Reimbursement ({$r['description']})",
                    'debit'       => (float)$r['amount'],
                    'credit'      => 0.00,
                    'created_at'  => $r['created_at']
                ];
            }

            // 2. Vouchers (posted and reversed)
            $vouchers = Database::fetchAll("
                SELECT voucher_no AS ref_no, voucher_date AS tx_date, status, amount,
                       payee, description, created_at, reversed_at
                FROM petty_cash_vouchers
                WHERE branch_id = :branch_id AND status IN ('posted', 'reversed')
            ", [':branch_id' => $branchId]);

            foreach ($vouchers as $v) {
                // Posted voucher decreases balance (credit)
                $entries[] = [
                    'ref_no'      => $v['ref_no'],
                    'date'        => $v['tx_date'],
                    'type'        => 'voucher',
                    'description' => "Voucher to {$v['payee']}: {$v['description']}",
                    'debit'       => 0.00,
                    'credit'      => (float)$v['amount'],
                    'created_at'  => $v['created_at']
                ];

                // If reversed, reversal increases balance back (debit)
                if ($v['status'] === 'reversed') {
                    $entries[] = [
                        'ref_no'      => $v['ref_no'] . '-REV',
                        'date'        => substr($v['reversed_at'] ?? $v['tx_date'], 0, 10),
                        'type'        => 'reversal',
                        'description' => "Reversal of Voucher #{$v['ref_no']}",
                        'debit'       => (float)$v['amount'],
                        'credit'      => 0.00,
                        'created_at'  => $v['reversed_at'] ?? $v['created_at']
                    ];
                }
            }

            // 3. Adjustments
            $adjs = Database::fetchAll("
                SELECT adjustment_no AS ref_no, created_at, adjustment_type, amount, reason
                FROM petty_cash_adjustments
                WHERE branch_id = :branch_id
            ", [':branch_id' => $branchId]);

            foreach ($adjs as $a) {
                $isPos = ($a['adjustment_type'] === 'positive');
                $entries[] = [
                    'ref_no'      => $a['ref_no'],
                    'date'        => substr($a['created_at'], 0, 10),
                    'type'        => $a['adjustment_type'] . '_adjustment',
                    'description' => "Adjustment: {$a['reason']}",
                    'debit'       => $isPos ? (float)$a['amount'] : 0.00,
                    'credit'      => $isPos ? 0.00 : (float)$a['amount'],
                    'created_at'  => $a['created_at']
                ];
            }

            // 4. Supplier Payments via Petty Cash
            $spPayments = Database::fetchAll("
                SELECT sp.payment_no AS ref_no, sp.payment_date AS tx_date,
                       sp.total_amount AS amount, sp.notes, s.name AS supplier_name,
                       sp.created_at
                FROM supplier_payments sp
                JOIN suppliers s ON sp.supplier_id = s.id
                WHERE sp.branch_id = :branch_id AND sp.payment_method = 'Petty Cash'
            ", [':branch_id' => $branchId]);

            foreach ($spPayments as $sp) {
                $entries[] = [
                    'ref_no'      => $sp['ref_no'],
                    'date'        => $sp['tx_date'],
                    'type'        => 'supplier_payment',
                    'description' => "Supplier Payment to {$sp['supplier_name']}" . (!empty($sp['notes']) ? ": {$sp['notes']}" : ''),
                    'debit'       => 0.00,
                    'credit'      => (float)$sp['amount'],
                    'created_at'  => $sp['created_at']
                ];
            }

            // Sort entries chronologically by created_at
            usort($entries, function($a, $b) {
                return strcmp($a['created_at'], $b['created_at']);
            });

            // Calculate running balance
            $runningBalance = 0.00;
            foreach ($entries as &$e) {
                $runningBalance += ($e['debit'] - $e['credit']);
                $e['running_balance'] = round($runningBalance, 2);
            }

            return [
                'entries' => $entries,
                'balance' => round($runningBalance, 2)
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to fetch petty cash register list: " . $e->getMessage(), $e);
            return ['entries' => [], 'balance' => 0.00];
        }
    }

    /**
     * Get Paginated Petty Cash Vouchers List with Filters
     */
    public static function getVouchersList(int $branchId, array $filters = [], int $page = 1, int $limit = 15): array
    {
        try {
            $where = ["pcv.branch_id = :branch_id"];
            $params = [':branch_id' => $branchId];

            if (!empty($filters['category_id'])) {
                $where[] = "pcv.category_id = :category_id";
                $params[':category_id'] = (int)$filters['category_id'];
            }

            if (!empty($filters['status'])) {
                $where[] = "pcv.status = :status";
                $params[':status'] = $filters['status'];
            }

            if (!empty($filters['start_date'])) {
                $where[] = "pcv.voucher_date >= :start_date";
                $params[':start_date'] = $filters['start_date'];
            }

            if (!empty($filters['end_date'])) {
                $where[] = "pcv.voucher_date <= :end_date";
                $params[':end_date'] = $filters['end_date'];
            }

            if (!empty($filters['search'])) {
                $where[] = "(pcv.voucher_no LIKE :q OR pcv.payee LIKE :q OR pcv.description LIKE :q)";
                $params[':q'] = '%' . trim($filters['search']) . '%';
            }

            $whereSql = implode(" AND ", $where);

            $countRow = Database::fetchOne("SELECT COUNT(*) AS total FROM petty_cash_vouchers pcv WHERE $whereSql", $params);
            $totalItems = (int)($countRow['total'] ?? 0);
            $offset = ($page - 1) * $limit;

            $items = Database::fetchAll("
                SELECT pcv.*, ec.name AS category_name, ec.code AS category_code, u.username AS creator_name, u2.username AS reverser_name
                FROM petty_cash_vouchers pcv
                JOIN expense_categories ec ON pcv.category_id = ec.id
                JOIN users u ON pcv.created_by = u.id
                LEFT JOIN users u2 ON pcv.reversed_by = u2.id
                WHERE $whereSql
                ORDER BY pcv.id DESC
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
            Logger::error("Failed to fetch vouchers list: " . $e->getMessage(), $e);
            return ['items' => [], 'totalItems' => 0, 'page' => 1, 'limit' => $limit, 'totalPages' => 1];
        }
    }

    /**
     * Get Reimbursements List
     */
    public static function getReimbursementsList(int $branchId, int $limit = 20): array
    {
        try {
            return Database::fetchAll("
                SELECT pcr.*, u.username AS creator_name
                FROM petty_cash_reimbursements pcr
                JOIN users u ON pcr.created_by = u.id
                WHERE pcr.branch_id = :branch_id
                ORDER BY pcr.id DESC
                LIMIT $limit
            ", [':branch_id' => $branchId]);
        } catch (Throwable $e) {
            Logger::error("Failed to fetch reimbursements list: " . $e->getMessage(), $e);
            return [];
        }
    }

    /**
     * Get Adjustments List
     */
    public static function getAdjustmentsList(int $branchId, int $limit = 20): array
    {
        try {
            return Database::fetchAll("
                SELECT pca.*, u.username AS creator_name
                FROM petty_cash_adjustments pca
                JOIN users u ON pca.created_by = u.id
                WHERE pca.branch_id = :branch_id
                ORDER BY pca.id DESC
                LIMIT $limit
            ", [':branch_id' => $branchId]);
        } catch (Throwable $e) {
            Logger::error("Failed to fetch adjustments list: " . $e->getMessage(), $e);
            return [];
        }
    }

    /**
     * Get real COA 1015 (Petty Cash) Transactions with Running Balance, Sources, and Memos
     */
    public static function getAccountTransactions($branchFilter = 'all', string $search = ''): array
    {
        try {
            $pcAccount = Database::fetchOne("SELECT id, account_code, account_name, account_type FROM chart_of_accounts WHERE account_code = :code", [':code' => Accounting::CODE_PETTY_CASH]);
            if (!$pcAccount) {
                return ['account' => null, 'transactions' => [], 'summary' => ['current_balance' => 0.00, 'total_inflow' => 0.00, 'total_outflow' => 0.00, 'total_count' => 0]];
            }

            $accId = (int)$pcAccount['id'];
            $where = [
                "jl.account_id = :acc_id",
                "je.posting_status IN ('posted', 'reversed')"
            ];
            $params = [':acc_id' => $accId];

            if ($branchFilter !== 'all' && $branchFilter !== '' && is_numeric($branchFilter)) {
                $where[] = "je.branch_id = :branch_id";
                $params[':branch_id'] = (int)$branchFilter;
            }

            if (!empty($search)) {
                $searchTerm = '%' . trim($search) . '%';
                $where[] = "(je.entry_number LIKE :s1 OR je.description LIKE :s2 OR jl.memo LIKE :s3 OR b.name LIKE :s4 OR je.source_type LIKE :s5)";
                $params[':s1'] = $searchTerm;
                $params[':s2'] = $searchTerm;
                $params[':s3'] = $searchTerm;
                $params[':s4'] = $searchTerm;
                $params[':s5'] = $searchTerm;
            }

            $whereSql = implode(' AND ', $where);

            // Fetch transactions chronologically to calculate accurate running balance
            $rows = Database::fetchAll("
                SELECT 
                    jl.id AS line_id,
                    je.id AS journal_id,
                    je.entry_number,
                    je.entry_date,
                    je.source_type,
                    je.source_id,
                    je.description,
                    jl.memo,
                    jl.debit,
                    jl.credit,
                    je.posting_status,
                    b.name AS branch_name,
                    u.full_name AS created_by_name,
                    je.created_at
                FROM journal_lines jl
                JOIN journal_entries je ON jl.journal_entry_id = je.id
                LEFT JOIN branches b ON je.branch_id = b.id
                LEFT JOIN users u ON je.created_by = u.id
                WHERE $whereSql
                ORDER BY je.entry_date ASC, je.id ASC, jl.id ASC
            ", $params);

            $runningBal = 0.00;
            $totalInflow = 0.00;
            $totalOutflow = 0.00;

            foreach ($rows as &$r) {
                $debit = (float)$r['debit'];
                $credit = (float)$r['credit'];
                $totalInflow += $debit;
                $totalOutflow += $credit;
                $runningBal += ($debit - $credit);
                $r['running_balance'] = round($runningBal, 2);
            }

            // Return in reverse chronological order (newest on top) for standard ledger display
            $displayRows = array_reverse($rows);

            return [
                'account'      => $pcAccount,
                'transactions' => $displayRows,
                'summary'      => [
                    'current_balance' => round($runningBal, 2),
                    'total_inflow'    => round($totalInflow, 2),
                    'total_outflow'   => round($totalOutflow, 2),
                    'total_count'     => count($displayRows)
                ]
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to fetch petty cash account transactions: " . $e->getMessage(), $e);
            return ['account' => null, 'transactions' => [], 'summary' => ['current_balance' => 0.00, 'total_inflow' => 0.00, 'total_outflow' => 0.00, 'total_count' => 0]];
        }
    }
}

