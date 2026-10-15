<?php
/**
 * Curtiss POS - Accounting Core & Double-Entry Engine (Stage 10)
 * Chart of Accounts, Journal Entries, General Ledger, and Automatic Transaction Integration.
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Audit.php';

class Accounting
{
    /**
     * Standard system account codes
     */
    const CODE_CASH_IN_HAND = '1010';
    const CODE_CASH = '1010'; // Alias for backwards compatibility
    const CODE_CASH_DRAWER = '1012';
    const CODE_PETTY_CASH = '1015';
    const CODE_BANK = '1020';
    const CODE_BANK_ACCOUNT = '1020';
    const CODE_CARD_RECEIVABLE = '1030';
    const CODE_KOKO_RECEIVABLE = '1032';
    const CODE_CHEQUES_HAND = '1035';
    const CODE_CUSTOMER_RECEIVABLE = '1040';
    const CODE_SUPPLIER_CREDIT = '1045';
    const CODE_EMPLOYEE_ADVANCES = '1048';
    const CODE_INVENTORY_ASSET = '1200';
    const CODE_DAMAGED_LOSS = '1250';
    const CODE_VOUCHER_LIABILITY = '2010';
    const CODE_SUPPLIER_PAYABLE = '2020';
    const CODE_CHEQUES_ISSUED = '2025';
    const CODE_SALARY_PAYABLE = '2050';
    const CODE_EPF_PAYABLE = '2055';
    const CODE_ETF_PAYABLE = '2060';
    const CODE_EQUITY = '3010';
    const CODE_SALES_REVENUE = '4010';
    const CODE_SALES_RETURNS = '4020';
    const CODE_OTHER_INCOME = '4050';
    const CODE_BANK_INTEREST = '4050';
    const CODE_COGS = '5010';
    const CODE_INVENTORY_ADJUSTMENTS = '5020';
    const CODE_DISCOUNTS = '5030';
    const CODE_CASH_SHORTAGE_OVERAGE = '5040';
    const CODE_EXPENSES = '5100';
    const CODE_CARD_PROCESSING_FEES = '5170';
    const CODE_SALARY_EXPENSE = '5200';
    const CODE_EMPLOYER_EPF_ETF_EXPENSE = '5210';
    const CODE_COMMISSION_ALLOWANCE_EXPENSE = '5220';


    /**
     * Get account by code
     */
    public static function getAccountByCode(string $code): ?array
    {
        try {
            return Database::fetchOne("
                SELECT * FROM chart_of_accounts WHERE account_code = :code AND status = 'active'
            ", [':code' => trim($code)]);
        } catch (Throwable $e) {
            Logger::error("Failed to fetch COA account code {$code}: " . $e->getMessage(), $e);
            return null;
        }
    }

    /**
     * Get account ID by code
     */
    public static function getAccountIdByCode(string $code): int
    {
        $acc = self::getAccountByCode($code);
        if (!$acc) {
            throw new Exception("System COA Account code '{$code}' not found or inactive.");
        }
        return (int)$acc['id'];
    }

    /**
     * List Chart of Accounts
     */
    public static function getCOA(array $filters = []): array
    {
        try {
            $where = [];
            $params = [];

            if (!empty($filters['type'])) {
                $where[] = "coa.account_type = :type";
                $params[':type'] = $filters['type'];
            }

            if (!empty($filters['status'])) {
                $where[] = "coa.status = :status";
                $params[':status'] = $filters['status'];
            }

            if (!empty($filters['search'])) {
                $searchVal = '%' . trim($filters['search']) . '%';
                $where[] = "(coa.account_code LIKE :cs1 OR coa.account_name LIKE :cs2 OR coa.account_type LIKE :cs3 OR p.account_name LIKE :cs4)";
                $params[':cs1'] = $searchVal;
                $params[':cs2'] = $searchVal;
                $params[':cs3'] = $searchVal;
                $params[':cs4'] = $searchVal;
            }

            if (isset($filters['branch_id']) && $filters['branch_id'] !== '') {
                $where[] = "(coa.branch_id IS NULL OR coa.branch_id = :branch_id)";
                $params[':branch_id'] = (int)$filters['branch_id'];
            }

            $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

            return Database::fetchAll("
                SELECT 
                    coa.*, 
                    b.name AS branch_name, 
                    p.account_name AS parent_name,
                    COALESCE(
                        SUM(
                            CASE 
                                WHEN coa.account_type IN ('asset', 'expense') THEN jl.debit - jl.credit
                                ELSE jl.credit - jl.debit
                            END
                        ), 0.00
                    ) AS current_balance
                FROM chart_of_accounts coa
                LEFT JOIN branches b ON coa.branch_id = b.id
                LEFT JOIN chart_of_accounts p ON coa.parent_id = p.id
                LEFT JOIN journal_lines jl ON coa.id = jl.account_id
                LEFT JOIN journal_entries je ON jl.journal_entry_id = je.id AND je.posting_status = 'posted'
                $whereSql
                GROUP BY coa.id
                ORDER BY coa.account_code ASC
            ", $params);
        } catch (Throwable $e) {
            Logger::error("Failed to list COA: " . $e->getMessage(), $e);
            return [];
        }
    }

    /**
     * Fetch complete transaction history & running balance for an account
     */
    public static function getAccountHistory(int $accountId, ?int $branchId = null): array
    {
        try {
            $account = Database::fetchOne("
                SELECT coa.*, b.name AS branch_name
                FROM chart_of_accounts coa
                LEFT JOIN branches b ON coa.branch_id = b.id
                WHERE coa.id = :id
            ", [':id' => $accountId]);

            if (!$account) {
                return ['success' => false, 'message' => 'Account not found.'];
            }

            $where = ["jl.account_id = :account_id", "je.posting_status = 'posted'"];
            $params = [':account_id' => $accountId];

            if ($branchId !== null && $branchId > 0) {
                $where[] = "(je.branch_id = :branch_id OR coa.branch_id IS NULL)";
                $params[':branch_id'] = (int)$branchId;
            }

            $whereSql = implode(" AND ", $where);

            $rows = Database::fetchAll("
                SELECT 
                    jl.id AS line_id,
                    jl.debit,
                    jl.credit,
                    jl.memo,
                    je.id AS journal_id,
                    je.entry_number,
                    je.entry_date,
                    je.source_type,
                    je.source_id,
                    je.description,
                    b.name AS branch_name,
                    u.full_name AS created_by_name
                FROM journal_lines jl
                JOIN journal_entries je ON jl.journal_entry_id = je.id
                JOIN chart_of_accounts coa ON jl.account_id = coa.id
                LEFT JOIN branches b ON je.branch_id = b.id
                LEFT JOIN users u ON je.created_by = u.id
                WHERE $whereSql
                ORDER BY je.entry_date ASC, je.id ASC, jl.id ASC
            ", $params);

            $runningBalance = 0.00;
            $isAssetOrExpense = in_array($account['account_type'], ['asset', 'expense']);

            foreach ($rows as &$row) {
                $debit = (float)$row['debit'];
                $credit = (float)$row['credit'];

                if ($isAssetOrExpense) {
                    $runningBalance += ($debit - $credit);
                } else {
                    $runningBalance += ($credit - $debit);
                }
                $row['running_balance'] = $runningBalance;
            }

            $account['current_balance'] = $runningBalance;

            return [
                'success'      => true,
                'account'      => $account,
                'transactions' => array_reverse($rows)
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to fetch account history: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Create custom COA account
     */
    public static function createAccount(array $data, int $userId): array
    {
        try {
            $code = trim($data['account_code'] ?? '');
            $name = trim($data['account_name'] ?? '');
            $type = $data['account_type'] ?? '';
            $parentId = !empty($data['parent_id']) ? (int)$data['parent_id'] : null;
            $branchId = !empty($data['branch_id']) ? (int)$data['branch_id'] : null;

            if (empty($code) || empty($name) || !in_array($type, ['asset', 'liability', 'equity', 'income', 'expense'])) {
                return ['success' => false, 'message' => 'Valid code, name, and account type are required.'];
            }

            $existing = Database::fetchOne("SELECT id FROM chart_of_accounts WHERE account_code = :code", [':code' => $code]);
            if ($existing) {
                return ['success' => false, 'message' => "Account code '{$code}' already exists."];
            }

            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                INSERT INTO chart_of_accounts (account_code, account_name, account_type, parent_id, branch_id, is_system, status)
                VALUES (:code, :name, :type, :parent_id, :branch_id, 0, 'active')
            ");

            $stmt->execute([
                ':code'      => $code,
                ':name'      => $name,
                ':type'      => $type,
                ':parent_id' => $parentId,
                ':branch_id' => $branchId
            ]);

            $id = (int)$db->lastInsertId();

            Audit::log('COA_ACCOUNT_CREATED', 'accounting', (string)$id, null, [
                'code' => $code, 'name' => $name, 'type' => $type
            ]);

            return ['success' => true, 'id' => $id, 'message' => "Account '{$name}' ({$code}) created successfully."];
        } catch (Throwable $e) {
            Logger::error("Failed to create COA account: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Failed to create account: ' . $e->getMessage()];
        }
    }

    /**
     * Update COA account (System accounts protected)
     */
    public static function updateAccount(int $id, array $data, int $userId): array
    {
        try {
            $acc = Database::fetchOne("SELECT * FROM chart_of_accounts WHERE id = :id", [':id' => $id]);
            if (!$acc) {
                return ['success' => false, 'message' => 'Account not found.'];
            }

            if ($acc['is_system'] && isset($data['account_code']) && $data['account_code'] !== $acc['account_code']) {
                return ['success' => false, 'message' => 'System account codes cannot be modified.'];
            }

            $name = trim($data['account_name'] ?? $acc['account_name']);
            $status = $data['status'] ?? $acc['status'];

            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                UPDATE chart_of_accounts 
                SET account_name = :name, status = :status, updated_at = NOW()
                WHERE id = :id
            ");

            $stmt->execute([
                ':name'   => $name,
                ':status' => $status,
                ':id'     => $id
            ]);

            Audit::log('COA_ACCOUNT_UPDATED', 'accounting', (string)$id, [
                'name' => $acc['account_name'], 'status' => $acc['status']
            ], [
                'name' => $name, 'status' => $status
            ]);

            return ['success' => true, 'message' => 'Account updated successfully.'];
        } catch (Throwable $e) {
            Logger::error("Failed to update COA account #{$id}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Failed to update account.'];
        }
    }

    /**
     * Delete COA Account (Protected against System accounts & Accounts with Journal/Sub-ledger History)
     */
    public static function deleteAccount(int $id, int $userId): array
    {
        try {
            $acc = Database::fetchOne("SELECT * FROM chart_of_accounts WHERE id = :id", [':id' => $id]);
            if (!$acc) {
                return ['success' => false, 'message' => 'Account not found.'];
            }

            // System account protection
            $systemCodes = [
                self::CODE_CASH, self::CODE_PETTY_CASH, self::CODE_BANK, self::CODE_CARD_RECEIVABLE,
                self::CODE_CHEQUES_HAND, self::CODE_CUSTOMER_RECEIVABLE, self::CODE_SUPPLIER_CREDIT,
                self::CODE_INVENTORY_ASSET, self::CODE_DAMAGED_LOSS, self::CODE_VOUCHER_LIABILITY,
                self::CODE_SUPPLIER_PAYABLE, self::CODE_CHEQUES_ISSUED, self::CODE_EQUITY,
                self::CODE_SALES_REVENUE, self::CODE_SALES_RETURNS, self::CODE_OTHER_INCOME,
                self::CODE_COGS, self::CODE_INVENTORY_ADJUSTMENTS, self::CODE_DISCOUNTS,
                self::CODE_CASH_SHORTAGE_OVERAGE, self::CODE_EXPENSES, self::CODE_CARD_PROCESSING_FEES
            ];

            if ($acc['is_system'] || in_array($acc['account_code'], $systemCodes)) {
                return ['success' => false, 'message' => "System protected account '{$acc['account_name']}' ({$acc['account_code']}) cannot be deleted."];
            }

            // Check journal lines history
            $hasJournals = (int)Database::fetchOne("
                SELECT COUNT(*) AS cnt FROM journal_lines WHERE account_id = :id
            ", [':id' => $id])['cnt'];

            if ($hasJournals > 0) {
                return [
                    'success' => false,
                    'message' => "Cannot delete account '{$acc['account_name']}' because it has existing journal transaction history. Deactivate the account instead."
                ];
            }

            // Check bank account linkage
            $hasBank = (int)Database::fetchOne("
                SELECT COUNT(*) AS cnt FROM bank_accounts WHERE account_id = :id
            ", [':id' => $id])['cnt'];

            if ($hasBank > 0) {
                return [
                    'success' => false,
                    'message' => "Cannot delete account '{$acc['account_name']}' because it is linked to a registered bank account."
                ];
            }

            $db = Database::getInstance()->getConnection();
            $db->prepare("DELETE FROM chart_of_accounts WHERE id = :id")->execute([':id' => $id]);

            Audit::log('COA_ACCOUNT_DELETED', 'accounting', (string)$id, [
                'code' => $acc['account_code'], 'name' => $acc['account_name']
            ]);

            return ['success' => true, 'message' => "Account '{$acc['account_name']}' ({$acc['account_code']}) deleted successfully."];

        } catch (Throwable $e) {
            Logger::error("Failed to delete COA account #{$id}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Failed to delete account: ' . $e->getMessage()];
        }
    }

    /**
     * Generate unique continuous journal entry number (JE-YYYYMMDD-0001...)
     */
    public static function generateEntryNumber(): string
    {
        try {
            $db = Database::getInstance()->getConnection();
            $prefix = 'JE-' . date('Ymd') . '-';
            $stmt = $db->prepare("SELECT MAX(id) AS max_id FROM journal_entries WHERE entry_number LIKE :prefix");
            $stmt->execute([':prefix' => $prefix . '%']);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $nextNum = ((int)($row['max_id'] ?? 0)) + 1;
            return $prefix . sprintf('%04d', $nextNum);
        } catch (Throwable $e) {
            Logger::error("Failed to generate JE entry number: " . $e->getMessage(), $e);
            return 'JE-' . date('Ymd') . '-' . rand(1000, 9999);
        }
    }

    /**
     * Post a Double-Entry Journal (Header + Lines)
     * Enforces STRICT Debit = Credit equality!
     */
    public static function postJournalEntry(array $header, array $lines): array
    {
        $db = Database::getInstance()->getConnection();
        $inTransaction = $db->inTransaction();

        if (!$inTransaction) {
            $db->beginTransaction();
        }

        try {
            $branchId   = (int)($header['branch_id'] ?? 0);
            $entryDate  = $header['entry_date'] ?? date('Y-m-d');
            $sourceType = $header['source_type'] ?? 'manual';
            $sourceId   = !empty($header['source_id']) ? (int)$header['source_id'] : null;
            $description= trim($header['description'] ?? 'Journal Entry');
            $createdBy  = (int)($header['created_by'] ?? 1);

            if ($branchId <= 0) {
                throw new Exception("Valid branch ID is required for journal entry.");
            }

            // Check duplicate posting if source_type & source_id are specified (except manual/reversal)
            if ($sourceId !== null && !in_array($sourceType, ['manual', 'reversal'])) {
                $existing = Database::fetchOne("
                    SELECT id, entry_number FROM journal_entries 
                    WHERE source_type = :stype AND source_id = :sid AND posting_status = 'posted' FOR UPDATE
                ", [':stype' => $sourceType, ':sid' => $sourceId]);

                if ($existing) {
                    if (!$inTransaction) $db->rollBack();
                    return [
                        'success'        => true,
                        'already_posted' => true,
                        'journal_id'     => (int)$existing['id'],
                        'entry_number'   => $existing['entry_number'],
                        'message'        => "Transaction already posted (Journal #{$existing['entry_number']})."
                    ];
                }
            }

            if (empty($lines) || count($lines) < 2) {
                throw new Exception("Journal entry must contain at least 2 lines (Debit and Credit).");
            }

            $totalDebit  = 0.00;
            $totalCredit = 0.00;
            $validatedLines = [];

            foreach ($lines as $idx => $line) {
                $accountId = (int)($line['account_id'] ?? 0);
                $debit     = round(max(0.00, (float)($line['debit'] ?? 0)), 2);
                $credit    = round(max(0.00, (float)($line['credit'] ?? 0)), 2);
                $memo      = !empty($line['memo']) ? trim($line['memo']) : null;

                if ($accountId <= 0) {
                    throw new Exception("Invalid account ID at line #" . ($idx + 1));
                }

                if ($debit == 0.00 && $credit == 0.00) {
                    continue; // skip zero lines
                }

                if ($debit > 0.00 && $credit > 0.00) {
                    throw new Exception("Line #" . ($idx + 1) . " cannot have both debit and credit amounts.");
                }

                $totalDebit += $debit;
                $totalCredit += $credit;

                $validatedLines[] = [
                    'account_id' => $accountId,
                    'debit'      => $debit,
                    'credit'     => $credit,
                    'memo'       => $memo
                ];
            }

            $totalDebit  = round($totalDebit, 2);
            $totalCredit = round($totalCredit, 2);

            if (count($validatedLines) < 2) {
                throw new Exception("Journal entry must have at least 2 non-zero lines.");
            }

            // DOUBLE-ENTRY BALANCE VALIDATION
            if (abs($totalDebit - $totalCredit) > 0.001) {
                throw new Exception("Unbalanced Journal Entry! Total Debit (LKR {$totalDebit}) != Total Credit (LKR {$totalCredit}).");
            }

            if ($totalDebit <= 0.00) {
                throw new Exception("Journal entry total must be greater than zero.");
            }

            $entryNumber = self::generateEntryNumber();

            // Insert Header
            $stmtHeader = $db->prepare("
                INSERT INTO journal_entries (
                    entry_number, branch_id, entry_date, source_type, source_id,
                    description, total_debit, total_credit, posting_status, created_by
                ) VALUES (
                    :entry_number, :branch_id, :entry_date, :source_type, :source_id,
                    :description, :total_debit, :total_credit, 'posted', :created_by
                )
            ");

            $stmtHeader->execute([
                ':entry_number' => $entryNumber,
                ':branch_id'    => $branchId,
                ':entry_date'   => $entryDate,
                ':source_type'  => $sourceType,
                ':source_id'    => $sourceId,
                ':description'  => $description,
                ':total_debit'  => $totalDebit,
                ':total_credit' => $totalCredit,
                ':created_by'   => $createdBy
            ]);

            $journalId = (int)$db->lastInsertId();

            // Insert Lines
            $stmtLine = $db->prepare("
                INSERT INTO journal_lines (journal_entry_id, account_id, debit, credit, memo)
                VALUES (:journal_entry_id, :account_id, :debit, :credit, :memo)
            ");

            foreach ($validatedLines as $vl) {
                $stmtLine->execute([
                    ':journal_entry_id' => $journalId,
                    ':account_id'       => $vl['account_id'],
                    ':debit'            => $vl['debit'],
                    ':credit'           => $vl['credit'],
                    ':memo'             => $vl['memo']
                ]);
            }

            if (!$inTransaction) {
                $db->commit();
            }

            Audit::log('JOURNAL_POSTED', 'accounting', (string)$journalId, null, [
                'entry_number' => $entryNumber,
                'source_type'  => $sourceType,
                'source_id'    => $sourceId,
                'total_amount' => $totalDebit
            ]);

            return [
                'success'      => true,
                'journal_id'   => $journalId,
                'entry_number' => $entryNumber,
                'total_debit'  => $totalDebit,
                'total_credit' => $totalCredit,
                'message'      => "Journal Entry {$entryNumber} posted successfully."
            ];

        } catch (Throwable $e) {
            if (!$inTransaction && $db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to post journal entry: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Reverse a posted Journal Entry (Creates reversal entry and marks original as reversed)
     */
    public static function reverseJournalEntry(int $journalId, string $reason, int $userId): array
    {
        $db = Database::getInstance()->getConnection();
        $inTransaction = $db->inTransaction();

        if (!$inTransaction) {
            $db->beginTransaction();
        }

        try {
            $original = Database::fetchOne("
                SELECT * FROM journal_entries WHERE id = :id FOR UPDATE
            ", [':id' => $journalId]);

            if (!$original) {
                if (!$inTransaction) $db->rollBack();
                return ['success' => false, 'message' => 'Journal entry not found.'];
            }

            if ($original['posting_status'] === 'reversed') {
                if (!$inTransaction) $db->rollBack();
                return ['success' => false, 'message' => 'Journal entry has already been reversed.'];
            }

            $origLines = Database::fetchAll("
                SELECT * FROM journal_lines WHERE journal_entry_id = :id
            ", [':id' => $journalId]);

            $reversalLines = [];
            foreach ($origLines as $line) {
                $reversalLines[] = [
                    'account_id' => (int)$line['account_id'],
                    'debit'      => (float)$line['credit'], // Swap credit to debit
                    'credit'     => (float)$line['debit'],  // Swap debit to credit
                    'memo'       => 'Reversal: ' . ($line['memo'] ?? '')
                ];
            }

            $header = [
                'branch_id'   => (int)$original['branch_id'],
                'entry_date'  => date('Y-m-d'),
                'source_type' => 'reversal',
                'source_id'   => $journalId,
                'description' => "Reversal of {$original['entry_number']}. Reason: " . trim($reason),
                'created_by'  => $userId
            ];

            $res = self::postJournalEntry($header, $reversalLines);
            if (!$res['success']) {
                if (!$inTransaction) $db->rollBack();
                return $res;
            }

            $reversalJournalId = $res['journal_id'];

            // Update original entry status
            $db->prepare("
                UPDATE journal_entries 
                SET posting_status = 'reversed', reversed_journal_id = :rev_id
                WHERE id = :id
            ")->execute([':rev_id' => $reversalJournalId, ':id' => $journalId]);

            if (!$inTransaction) {
                $db->commit();
            }

            Audit::log('JOURNAL_REVERSED', 'accounting', (string)$journalId, [
                'entry_number' => $original['entry_number']
            ], [
                'reversal_journal_id' => $reversalJournalId,
                'reason' => $reason
            ]);

            return [
                'success' => true,
                'reversal_journal_id' => $reversalJournalId,
                'message' => "Journal Entry {$original['entry_number']} reversed successfully."
            ];

        } catch (Throwable $e) {
            if (!$inTransaction && $db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to reverse journal entry #{$journalId}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Failed to reverse journal entry: ' . $e->getMessage()];
        }
    }

    /**
     * Automatic Accounting Trigger: GRN Approval Posting
     * Dr Inventory Asset (1200)
     * Cr Supplier Payables (2020)
     */
    public static function postGRN(int $grnId): array
    {
        try {
            $grn = Database::fetchOne("
                SELECT g.*, s.name AS supplier_name 
                FROM grns g 
                JOIN suppliers s ON g.supplier_id = s.id 
                WHERE g.id = :id
            ", [':id' => $grnId]);

            if (!$grn || $grn['status'] !== 'Approved') {
                return ['success' => false, 'message' => 'Approved GRN not found.'];
            }

            $amount = round((float)$grn['actual_grn_total'], 2);
            if ($amount <= 0) {
                return ['success' => false, 'message' => 'GRN amount must be greater than zero.'];
            }

            $inventoryAccId = self::getAccountIdByCode(self::CODE_INVENTORY_ASSET);
            $apAccId        = self::getAccountIdByCode(self::CODE_SUPPLIER_PAYABLE);

            $header = [
                'branch_id'   => (int)$grn['branch_id'],
                'entry_date'  => $grn['received_date'] ?? date('Y-m-d'),
                'source_type' => 'grn',
                'source_id'   => $grnId,
                'description' => "Approved GRN #{$grn['grn_number']} from {$grn['supplier_name']}",
                'created_by'  => (int)($grn['approved_by'] ?? $grn['created_by'] ?? 1)
            ];

            $lines = [
                [
                    'account_id' => $inventoryAccId,
                    'debit'      => $amount,
                    'credit'     => 0.00,
                    'memo'       => "Stock received via GRN {$grn['grn_number']}"
                ],
                [
                    'account_id' => $apAccId,
                    'debit'      => 0.00,
                    'credit'     => $amount,
                    'memo'       => "Supplier Payable for GRN {$grn['grn_number']}"
                ]
            ];

            return self::postJournalEntry($header, $lines);

        } catch (Throwable $e) {
            Logger::error("Accounting::postGRN failed for GRN #{$grnId}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Automatic Accounting Trigger: Supplier Payment Posting
     * Dr Supplier Payables (2020)
     * Cr Cash (1010) / Bank (1020) / Card (1030)
     */
    public static function postSupplierPayment(int $paymentId): array
    {
        try {
            $pmt = Database::fetchOne("
                SELECT sp.*, s.name AS supplier_name 
                FROM supplier_payments sp 
                JOIN suppliers s ON sp.supplier_id = s.id 
                WHERE sp.id = :id
            ", [':id' => $paymentId]);

            if (!$pmt) {
                return ['success' => false, 'message' => 'Supplier payment not found.'];
            }

            $amount = round((float)$pmt['total_amount'], 2);
            if ($amount <= 0) {
                return ['success' => false, 'message' => 'Payment amount must be greater than zero.'];
            }

            $apAccId = self::getAccountIdByCode(self::CODE_SUPPLIER_PAYABLE);

            // Determine payment account
            $method = strtolower($pmt['payment_method']);
            if ($method === 'cash drawer' || $method === 'cash_drawer') {
                $creditAccId = self::getAccountIdByCode(self::CODE_CASH_DRAWER);
            } elseif ($method === 'cash in hand' || $method === 'cash_in_hand' || $method === 'cash') {
                $creditAccId = self::getAccountIdByCode(self::CODE_CASH_IN_HAND);
            } elseif ($method === 'petty cash' || $method === 'petty_cash') {
                $creditAccId = self::getAccountIdByCode(self::CODE_PETTY_CASH);
            } elseif ($method === 'cheque') {
                $creditAccId = self::getAccountIdByCode(self::CODE_CHEQUES_ISSUED);
            } elseif (in_array($method, ['bank', 'bank_transfer'])) {
                if (!empty($pmt['bank_account_id'])) {
                    $bankAcc = Database::fetchOne("SELECT account_id FROM bank_accounts WHERE id = :bid", [':bid' => $pmt['bank_account_id']]);
                    if ($bankAcc && !empty($bankAcc['account_id'])) {
                        $creditAccId = (int)$bankAcc['account_id'];
                    } else {
                        $creditAccId = self::getAccountIdByCode(self::CODE_BANK);
                    }
                } else {
                    $creditAccId = self::getAccountIdByCode(self::CODE_BANK);
                }
            } elseif ($method === 'card') {
                $creditAccId = self::getAccountIdByCode(self::CODE_CARD_RECEIVABLE);
            } else {
                $creditAccId = self::getAccountIdByCode(self::CODE_CASH_IN_HAND);
            }

            $header = [
                'branch_id'   => (int)$pmt['branch_id'],
                'entry_date'  => $pmt['payment_date'],
                'source_type' => 'supplier_payment',
                'source_id'   => $paymentId,
                'description' => "Supplier Payment #{$pmt['payment_no']} to {$pmt['supplier_name']}",
                'created_by'  => (int)$pmt['created_by']
            ];

            $lines = [
                [
                    'account_id' => $apAccId,
                    'debit'      => $amount,
                    'credit'     => 0.00,
                    'memo'       => "AP Settlement #{$pmt['payment_no']}"
                ],
                [
                    'account_id' => $creditAccId,
                    'debit'      => 0.00,
                    'credit'     => $amount,
                    'memo'       => "Payment via {$pmt['payment_method']}"
                ]
            ];

            return self::postJournalEntry($header, $lines);

        } catch (Throwable $e) {
            Logger::error("Accounting::postSupplierPayment failed for Payment #{$paymentId}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Automatic Accounting Trigger: POS Sale Posting
     * Revenue & Payments:
     *   Dr Cash (1010) / Card (1030) / Bank (1020) / Voucher (2010) / AR (1040)
     *   Dr Sales Discounts (5030) (if applicable)
     *   Cr Sales Revenue (4010)
     * Exact Lot COGS:
     *   Dr COGS (5010)
     *   Cr Inventory Asset (1200)
     */
    public static function postPOSSale(int $saleId): array
    {
        try {
            $sale = Database::fetchOne("
                SELECT * FROM sales WHERE id = :id AND status = 'completed'
            ", [':id' => $saleId]);

            if (!$sale) {
                return ['success' => false, 'message' => 'Completed sale invoice not found.'];
            }

            // Fetch sale items
            $items = Database::fetchAll("
                SELECT * FROM sale_items WHERE sale_id = :sale_id
            ", [':sale_id' => $saleId]);

            // Fetch payments
            $pmts = Database::fetchAll("
                SELECT * FROM payments WHERE sale_id = :sale_id
            ", [':sale_id' => $saleId]);

            if (empty($pmts)) {
                $pmts = Database::fetchAll("
                    SELECT * FROM sale_payments WHERE sale_id = :sale_id
                ", [':sale_id' => $saleId]);
            }

            $cashAccId       = self::getAccountIdByCode(self::CODE_CASH_DRAWER); // 1012 Cash Drawer for POS sales
            $cashInHandAccId = self::getAccountIdByCode(self::CODE_CASH_IN_HAND);
            $cardAccId       = self::getAccountIdByCode(self::CODE_CARD_RECEIVABLE);
            $kokoAccId       = self::getAccountIdByCode(self::CODE_KOKO_RECEIVABLE);
            $bankAccId       = self::getAccountIdByCode(self::CODE_BANK);
            $voucherAccId    = self::getAccountIdByCode(self::CODE_VOUCHER_LIABILITY);
            $arAccId         = self::getAccountIdByCode(self::CODE_CUSTOMER_RECEIVABLE);
            $revAccId        = self::getAccountIdByCode(self::CODE_SALES_REVENUE);
            $cogsAccId       = self::getAccountIdByCode(self::CODE_COGS);
            $invAccId        = self::getAccountIdByCode(self::CODE_INVENTORY_ASSET);
            $discAccId       = self::getAccountIdByCode(self::CODE_DISCOUNTS);

            // Calculate Gross Sales, Total Item Cost, Total Discounts
            $grossSales = 0.00;
            $totalCost  = 0.00;
            $itemDiscounts = 0.00;

            foreach ($items as $item) {
                $qty = (float)$item['quantity'];
                $unitPrice = (float)$item['unit_price'];
                $unitCost = (float)$item['unit_cost'];
                $lineDisc = (float)($item['discount_amount'] ?? 0);

                $grossSales += ($unitPrice * $qty);
                $totalCost += ($unitCost * $qty);
                $itemDiscounts += $lineDisc;
            }

            $grossSales = round($grossSales, 2);
            $totalCost = round($totalCost, 2);
            $itemDiscounts = round($itemDiscounts, 2);

            $headerDiscount = round((float)($sale['discount_amount'] ?? 0), 2);
            $totalDiscount  = round($itemDiscounts + $headerDiscount, 2);
            $grandTotal     = round((float)($sale['total_amount'] ?? $sale['grand_total'] ?? 0), 2);

            $journalLines = [];

            // 1. Process payment accounts
            $totalPaymentAllocated = 0.00;
            foreach ($pmts as $p) {
                $totalPaymentAllocated += round((float)$p['amount'], 2);
            }
            $changeAmount = round(max(0.00, $totalPaymentAllocated - $grandTotal), 2);

            $paidTotal = 0.00;
            foreach ($pmts as $p) {
                $pAmount = round((float)$p['amount'], 2);
                if ($pAmount <= 0) continue;

                $pMethod = strtolower($p['payment_method']);

                if ($pMethod === 'cash' || $pMethod === 'cash drawer' || $pMethod === 'cash_drawer') {
                    $netCashDebit = round(max(0.00, $pAmount - $changeAmount), 2);
                    $paidTotal += $netCashDebit;
                    if ($netCashDebit > 0) {
                        $journalLines[] = ['account_id' => $cashAccId, 'debit' => $netCashDebit, 'credit' => 0.00, 'memo' => "Cash terminal payment to Cash Drawer (net of change)"];
                    }
                } elseif ($pMethod === 'card') {
                    $paidTotal += $pAmount;
                    $journalLines[] = ['account_id' => $cardAccId, 'debit' => $pAmount, 'credit' => 0.00, 'memo' => "Card payment"];
                } elseif ($pMethod === 'koko' || $pMethod === 'kokopay' || $pMethod === 'koko_pay') {
                    $paidTotal += $pAmount;
                    $targetKokoAccId = $kokoAccId ?: $cardAccId;
                    $refStr = !empty($p['reference_no']) ? " (Ref: {$p['reference_no']})" : "";
                    $journalLines[] = ['account_id' => $targetKokoAccId, 'debit' => $pAmount, 'credit' => 0.00, 'memo' => "KOKO BNPL payment{$refStr}"];
                } elseif (in_array($pMethod, ['bank', 'bank_transfer', 'cheque'], true)) {
                    $paidTotal += $pAmount;
                    $targetBankCoaId = $bankAccId;
                    if (!empty($p['bank_account_id'])) {
                        $bankAcc = Database::fetchOne("
                            SELECT account_id FROM bank_accounts WHERE id = :id
                        ", [':id' => (int)$p['bank_account_id']]);
                        if ($bankAcc && !empty($bankAcc['account_id'])) {
                            $targetBankCoaId = (int)$bankAcc['account_id'];
                        }
                    }
                    $journalLines[] = ['account_id' => $targetBankCoaId, 'debit' => $pAmount, 'credit' => 0.00, 'memo' => "Bank payment"];
                } elseif ($pMethod === 'voucher') {
                    $paidTotal += $pAmount;
                    $journalLines[] = ['account_id' => $voucherAccId, 'debit' => $pAmount, 'credit' => 0.00, 'memo' => "Voucher redemption"];
                } elseif ($pMethod === 'on_account' || $pMethod === 'credit') {
                    $paidTotal += $pAmount;
                    $journalLines[] = ['account_id' => $arAccId, 'debit' => $pAmount, 'credit' => 0.00, 'memo' => "Credit sale"];
                } else {
                    $netCashDebit = round(max(0.00, $pAmount - $changeAmount), 2);
                    $paidTotal += $netCashDebit;
                    if ($netCashDebit > 0) {
                        $journalLines[] = ['account_id' => $cashAccId, 'debit' => $netCashDebit, 'credit' => 0.00, 'memo' => "Payment ({$pMethod})"];
                    }
                }
            }

            // Customer Credit / Unpaid balance (On Account)
            $unpaidBalance = round(max(0.00, $grandTotal - $paidTotal), 2);
            if ($unpaidBalance > 0.00) {
                $journalLines[] = ['account_id' => $arAccId, 'debit' => $unpaidBalance, 'credit' => 0.00, 'memo' => "Customer Receivable (Credit)"];
            }

            // Discount line (if any)
            if ($totalDiscount > 0.00) {
                $journalLines[] = ['account_id' => $discAccId, 'debit' => $totalDiscount, 'credit' => 0.00, 'memo' => "Sales Discount"];
            }

            // Gross Sales Revenue line
            $journalLines[] = ['account_id' => $revAccId, 'debit' => 0.00, 'credit' => $grossSales, 'memo' => "Gross Sales Revenue"];

            // Exact Lot COGS & Inventory lines
            if ($totalCost > 0.00) {
                $journalLines[] = ['account_id' => $cogsAccId, 'debit' => $totalCost, 'credit' => 0.00, 'memo' => "COGS for invoice #{$sale['invoice_no']}"];
                $journalLines[] = ['account_id' => $invAccId, 'debit' => 0.00, 'credit' => $totalCost, 'memo' => "Inventory reduction"];
            }

            $entryDate = date('Y-m-d', strtotime($sale['created_at']));
            $header = [
                'branch_id'   => (int)$sale['branch_id'],
                'entry_date'  => $entryDate,
                'source_type' => 'sale',
                'source_id'   => $saleId,
                'description' => "POS Sale Invoice #{$sale['invoice_no']}",
                'created_by'  => (int)$sale['user_id']
            ];

            return self::postJournalEntry($header, $journalLines);

        } catch (Throwable $e) {
            Logger::error("Accounting::postPOSSale failed for Sale #{$saleId}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Automatic Accounting Trigger: Customer Collection Posting
     * Dr Cash (1010) / Bank (1020 or Bank COA) / Card (1030) / Cheques in Hand (1035) / Voucher (2010)
     * Cr Customer Receivables (1040)
     */
    public static function postCustomerCollection(int $paymentId): array
    {
        try {
            // First check customer_credit_ledger
            $pmt = Database::fetchOne("
                SELECT ccl.*, c.name AS customer_name 
                FROM customer_credit_ledger ccl 
                JOIN customers c ON ccl.customer_id = c.id 
                WHERE ccl.id = :id
            ", [':id' => $paymentId]);

            if (!$pmt) {
                // Fallback check customer_payments table if legacy
                $pmt = Database::fetchOne("
                    SELECT cp.*, c.name AS customer_name 
                    FROM customer_payments cp 
                    JOIN customers c ON cp.customer_id = c.id 
                    WHERE cp.id = :id
                ", [':id' => $paymentId]);
            }

            if (!$pmt) {
                return ['success' => false, 'message' => 'Customer payment receipt not found.'];
            }

            $amount = round(abs((float)($pmt['amount_paid'] ?? $pmt['amount'] ?? 0)), 2);
            if ($amount <= 0) {
                return ['success' => false, 'message' => 'Payment amount must be greater than zero.'];
            }

            $arAccId = self::getAccountIdByCode(self::CODE_CUSTOMER_RECEIVABLE);

            $method = strtolower($pmt['payment_method'] ?? 'cash');
            if ($method === 'cash drawer' || $method === 'cash_drawer') {
                $debitAccId = self::getAccountIdByCode(self::CODE_CASH_DRAWER);
            } elseif ($method === 'cash in hand' || $method === 'cash_in_hand' || $method === 'cash') {
                $debitAccId = self::getAccountIdByCode(self::CODE_CASH_IN_HAND);
            } elseif ($method === 'petty cash' || $method === 'petty_cash') {
                $debitAccId = self::getAccountIdByCode(self::CODE_PETTY_CASH);
            } elseif ($method === 'card') {
                $debitAccId = self::getAccountIdByCode(self::CODE_CARD_RECEIVABLE);
            } elseif ($method === 'cheque') {
                $debitAccId = self::getAccountIdByCode(self::CODE_CHEQUES_HAND);
            } elseif (in_array($method, ['bank', 'bank_transfer'])) {
                if (!empty($pmt['bank_account_id'])) {
                    $bankAcc = Database::fetchOne("SELECT account_id FROM bank_accounts WHERE id = :bid", [':bid' => $pmt['bank_account_id']]);
                    if ($bankAcc && !empty($bankAcc['account_id'])) {
                        $debitAccId = (int)$bankAcc['account_id'];
                    } else {
                        $debitAccId = self::getAccountIdByCode(self::CODE_BANK);
                    }
                } else {
                    $debitAccId = self::getAccountIdByCode(self::CODE_BANK);
                }
            } elseif ($method === 'voucher') {
                $debitAccId = self::getAccountIdByCode(self::CODE_VOUCHER_LIABILITY);
            } else {
                $debitAccId = self::getAccountIdByCode(self::CODE_CASH_IN_HAND);
            }

            $refNo = $pmt['reference_no'] ?? ($pmt['receipt_number'] ?? $paymentId);

            $header = [
                'branch_id'   => (int)($pmt['branch_id'] ?? 1),
                'entry_date'  => $pmt['created_at'] ?? $pmt['payment_date'] ?? date('Y-m-d H:i:s'),
                'source_type' => 'customer_collection',
                'source_id'   => $paymentId,
                'description' => "Customer Collection Receipt #{$refNo} from {$pmt['customer_name']}",
                'created_by'  => (int)($pmt['user_id'] ?? $pmt['created_by'] ?? 1)
            ];

            $lines = [
                [
                    'account_id' => $debitAccId,
                    'debit'      => $amount,
                    'credit'     => 0.00,
                    'memo'       => "Collection via " . ucfirst($method)
                ],
                [
                    'account_id' => $arAccId,
                    'debit'      => 0.00,
                    'credit'     => $amount,
                    'memo'       => "AR Collection for {$pmt['customer_name']}"
                ]
            ];

            return self::postJournalEntry($header, $lines);

        } catch (Throwable $e) {
            Logger::error("Accounting::postCustomerCollection failed for Receipt #{$paymentId}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Automatic Accounting Trigger: Customer Cheque Return Posting
     * Dr Customer Receivables (1040)
     * Cr Cheques in Hand (1035) OR Bank COA (if deposited/cleared)
     */
    public static function postCustomerChequeReturn(int $chequeId, int $cclId, ?string $previousStatus = null): array
    {
        try {
            $chq = Database::fetchOne("
                SELECT cq.*, c.name AS customer_name 
                FROM customer_cheques cq 
                JOIN customers c ON cq.customer_id = c.id 
                WHERE cq.id = :id
            ", [':id' => $chequeId]);

            if (!$chq) {
                return ['success' => false, 'message' => 'Customer cheque not found.'];
            }

            $amount = round((float)$chq['amount'], 2);
            if ($amount <= 0) {
                return ['success' => false, 'message' => 'Cheque amount must be greater than zero.'];
            }

            $arAccId = self::getAccountIdByCode(self::CODE_CUSTOMER_RECEIVABLE);

            // Determine credit account based on cheque state at time of return
            $statusToCheck = !empty($previousStatus) ? $previousStatus : $chq['status'];
            if (in_array($statusToCheck, ['deposited', 'cleared']) && !empty($chq['bank_account_id'])) {
                $bankAcc = Database::fetchOne("SELECT account_id FROM bank_accounts WHERE id = :bid", [':bid' => $chq['bank_account_id']]);
                if ($bankAcc && !empty($bankAcc['account_id'])) {
                    $creditAccId = (int)$bankAcc['account_id'];
                } else {
                    $creditAccId = self::getAccountIdByCode(self::CODE_BANK);
                }
            } else {
                $creditAccId = self::getAccountIdByCode(self::CODE_CHEQUES_HAND);
            }

            $header = [
                'branch_id'   => (int)($chq['branch_id'] ?? 1),
                'entry_date'  => $chq['returned_at'] ?? date('Y-m-d H:i:s'),
                'source_type' => 'customer_cheque_return',
                'source_id'   => $chequeId,
                'description' => "Customer Cheque Return #{$chq['cheque_no']} from {$chq['customer_name']}",
                'created_by'  => (int)($chq['returned_by'] ?? 1)
            ];

            $lines = [
                [
                    'account_id' => $arAccId,
                    'debit'      => $amount,
                    'credit'     => 0.00,
                    'memo'       => "Restored AR for returned cheque #{$chq['cheque_no']}"
                ],
                [
                    'account_id' => $creditAccId,
                    'debit'      => 0.00,
                    'credit'     => $amount,
                    'memo'       => "Cheque return reversal for #{$chq['cheque_no']}"
                ]
            ];

            return self::postJournalEntry($header, $lines);

        } catch (Throwable $e) {
            Logger::error("Accounting::postCustomerChequeReturn failed for Cheque #{$chequeId}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Automatic Accounting Trigger: Sales Return Posting
     * Revenue Reversal:
     *   Dr Sales Returns & Allowances (4020)
     *   Cr Cash / Card / Bank / AR / Voucher Liability
     * Stock Reversal:
     *   Good stock: Dr Inventory Asset (1200), Cr COGS (5010)
     *   Damaged stock: Dr Damaged Inventory Loss (1250), Cr COGS (5010)
     */
    public static function postSalesReturn(int $returnId): array
    {
        try {
            $ret = Database::fetchOne("
                SELECT * FROM sales_returns WHERE id = :id
            ", [':id' => $returnId]);

            if (!$ret) {
                return ['success' => false, 'message' => 'Completed sales return transaction not found.'];
            }

            $returnItems = Database::fetchAll("
                SELECT * FROM sales_return_items WHERE sales_return_id = :id
            ", [':id' => $returnId]);

            $returnPmts = Database::fetchAll("
                SELECT * FROM refunds WHERE sales_return_id = :id
            ", [':id' => $returnId]);

            $cashAccId       = self::getAccountIdByCode(self::CODE_CASH);
            $cardAccId       = self::getAccountIdByCode(self::CODE_CARD_RECEIVABLE);
            $bankAccId       = self::getAccountIdByCode(self::CODE_BANK);
            $voucherAccId    = self::getAccountIdByCode(self::CODE_VOUCHER_LIABILITY);
            $arAccId         = self::getAccountIdByCode(self::CODE_CUSTOMER_RECEIVABLE);
            $returnsAccId    = self::getAccountIdByCode(self::CODE_SALES_RETURNS);
            $cogsAccId       = self::getAccountIdByCode(self::CODE_COGS);
            $invAccId        = self::getAccountIdByCode(self::CODE_INVENTORY_ASSET);
            $damagedLossAccId= self::getAccountIdByCode(self::CODE_DAMAGED_LOSS);

            $refundTotal = round((float)($ret['total_return_amount'] ?? $ret['total_refund_amount'] ?? 0), 2);
            $returnNo = $ret['return_no'] ?? $ret['return_number'] ?? ('RET-' . $returnId);
            $journalLines = [];

            // 1. Dr Sales Returns & Allowances (4020)
            if ($refundTotal > 0.00) {
                $journalLines[] = [
                    'account_id' => $returnsAccId,
                    'debit'      => $refundTotal,
                    'credit'     => 0.00,
                    'memo'       => "Sales Return #{$returnNo}"
                ];

                // Cr Refund payment accounts
                $paidOutTotal = 0.00;
                foreach ($returnPmts as $rp) {
                    $rpAmount = round((float)$rp['amount'], 2);
                    if ($rpAmount <= 0) continue;
                    $paidOutTotal += $rpAmount;
                    $method = strtolower($rp['refund_method'] ?? $rp['payment_method'] ?? 'cash');

                    if ($method === 'cash_drawer') {
                        $cashDrawerAccId = self::getAccountIdByCode(self::CODE_CASH_DRAWER);
                        $journalLines[] = ['account_id' => $cashDrawerAccId, 'debit' => 0.00, 'credit' => $rpAmount, 'memo' => "Cash Drawer refund payout"];
                    } elseif ($method === 'petty_cash') {
                        $pettyAccId = self::getAccountIdByCode(self::CODE_PETTY_CASH);
                        $journalLines[] = ['account_id' => $pettyAccId, 'debit' => 0.00, 'credit' => $rpAmount, 'memo' => "Petty Cash refund payout"];
                    } elseif ($method === 'cash' || $method === 'cash_in_hand') {
                        $journalLines[] = ['account_id' => $cashAccId, 'debit' => 0.00, 'credit' => $rpAmount, 'memo' => "Cash in Hand refund payout"];
                    } elseif ($method === 'card') {
                        $saleId = (int)($ret['sale_id'] ?? 0);
                        $remaining1030 = 0.00;
                        if ($saleId > 0) {
                            $pmtStats = Database::fetchOne("
                                SELECT 
                                    COALESCE(SUM(p.amount), 0.00) AS total_pmt,
                                    COALESCE(SUM(p.settled_amount), 0.00) AS total_settled
                                FROM payments p
                                WHERE p.sale_id = :sale_id AND LOWER(p.payment_method) = 'card'
                            ", [':sale_id' => $saleId]);
                            $totalPmt = (float)($pmtStats['total_pmt'] ?? 0);
                            $totalSettled = (float)($pmtStats['total_settled'] ?? 0);

                            $priorRefundsRow = Database::fetchOne("
                                SELECT COALESCE(SUM(r.amount), 0.00) AS prior_refunds
                                FROM refunds r
                                JOIN sales_returns sr ON r.sales_return_id = sr.id
                                WHERE sr.sale_id = :sale_id AND LOWER(r.refund_method) = 'card'
                                  AND sr.status != 'cancelled' AND sr.id != :ret_id
                            ", [':sale_id' => $saleId, ':ret_id' => $returnId]);
                            $priorRefunds = (float)($priorRefundsRow['prior_refunds'] ?? 0);

                            $remaining1030 = max(0.00, round($totalPmt - $totalSettled - $priorRefunds, 2));
                        } else {
                            // Unlinked return (sale_id is null / 0): credit 1030 up to available branch unsettled balance, excess to bank
                            $unsettled1030Row = Database::fetchOne("
                                SELECT COALESCE(SUM(p.amount - COALESCE(cr.total_refund, 0.00) - p.settled_amount), 0.00) AS total_unsettled
                                FROM payments p
                                JOIN sales s ON p.sale_id = s.id
                                LEFT JOIN (
                                    SELECT sr.sale_id, SUM(r.amount) AS total_refund
                                    FROM refunds r
                                    JOIN sales_returns sr ON r.sales_return_id = sr.id
                                    WHERE LOWER(r.refund_method) = 'card' AND sr.status != 'cancelled'
                                    GROUP BY sr.sale_id
                                ) cr ON p.sale_id = cr.sale_id
                                WHERE LOWER(p.payment_method) = 'card'
                                  AND p.settlement_status IN ('unsettled', 'partially_settled')
                                  AND s.status != 'cancelled'
                                  AND s.branch_id = :branch_id
                            ", [':branch_id' => (int)$ret['branch_id']]);
                            $remaining1030 = max(0.00, round((float)($unsettled1030Row['total_unsettled'] ?? 0), 2));
                        }

                        $portion1030 = min($rpAmount, $remaining1030);
                        $portionBank = round($rpAmount - $portion1030, 2);

                        if ($portion1030 > 0.00) {
                            $journalLines[] = ['account_id' => $cardAccId, 'debit' => 0.00, 'credit' => $portion1030, 'memo' => "Card refund (1030 clearance)"];
                        }
                        if ($portionBank > 0.00) {
                            $targetBankCoaId = $bankAccId;
                            if (!empty($rp['bank_account_id'])) {
                                $bankAcc = Database::fetchOne("SELECT account_id FROM bank_accounts WHERE id = :id", [':id' => (int)$rp['bank_account_id']]);
                                if ($bankAcc && !empty($bankAcc['account_id'])) {
                                    $targetBankCoaId = (int)$bankAcc['account_id'];
                                }
                            } else if ($saleId > 0) {
                                $procBank = Database::fetchOne("
                                    SELECT ba.account_id
                                    FROM payments p
                                    JOIN card_processors cp ON p.processor_id = cp.id
                                    JOIN bank_accounts ba ON cp.default_bank_account_id = ba.id
                                    WHERE p.sale_id = :sale_id AND LOWER(p.payment_method) = 'card' AND cp.default_bank_account_id IS NOT NULL
                                    LIMIT 1
                                ", [':sale_id' => $saleId]);
                                if ($procBank && !empty($procBank['account_id'])) {
                                    $targetBankCoaId = (int)$procBank['account_id'];
                                }
                            }
                            $journalLines[] = ['account_id' => $targetBankCoaId, 'debit' => 0.00, 'credit' => $portionBank, 'memo' => "Card refund payout (bank outflow post-settlement)"];
                        }
                    } elseif (in_array($method, ['bank', 'bank_transfer', 'cheque'])) {
                        $targetBankCoaId = ($method === 'cheque') ? self::getAccountIdByCode(self::CODE_CHEQUES_HAND) : $bankAccId;
                        if ($method !== 'cheque' && !empty($rp['bank_account_id'])) {
                            $bankAcc = Database::fetchOne("SELECT account_id FROM bank_accounts WHERE id = :id", [':id' => (int)$rp['bank_account_id']]);
                            if ($bankAcc && !empty($bankAcc['account_id'])) {
                                $targetBankCoaId = (int)$bankAcc['account_id'];
                            }
                        }
                        $journalLines[] = ['account_id' => $targetBankCoaId, 'debit' => 0.00, 'credit' => $rpAmount, 'memo' => ucfirst($method) . " refund"];
                    } elseif ($method === 'voucher' || $method === 'gift_voucher') {
                        $journalLines[] = ['account_id' => $voucherAccId, 'debit' => 0.00, 'credit' => $rpAmount, 'memo' => "Gift Voucher issued"];
                    } elseif ($method === 'customer_credit' || $method === 'credit' || $method === 'exchange' || $method === 'store_credit') {
                        $journalLines[] = ['account_id' => $arAccId, 'debit' => 0.00, 'credit' => $rpAmount, 'memo' => "Customer credit adjustment"];
                    } else {
                        $journalLines[] = ['account_id' => $cashAccId, 'debit' => 0.00, 'credit' => $rpAmount, 'memo' => "Refund ({$method})"];
                    }
                }

                $unallocatedRefund = round(max(0.00, $refundTotal - $paidOutTotal), 2);
                if ($unallocatedRefund > 0.00) {
                    // If no financial payout was made (POS Exchange / Store Credit), credit Customer Receivables
                    $journalLines[] = ['account_id' => $arAccId, 'debit' => 0.00, 'credit' => $unallocatedRefund, 'memo' => "Return exchange credit (No cash payout)"];
                }
            }

            // 2. Stock Cost Reversal Lines
            $goodStockCost    = 0.00;
            $damagedStockCost = 0.00;

            foreach ($returnItems as $ri) {
                $qty = (float)$ri['quantity'];
                $unitCost = (float)($ri['cost_price'] ?? $ri['unit_cost'] ?? 0);
                $lineCost = round($qty * $unitCost, 2);

                $cond = strtolower($ri['item_condition'] ?? 'good');

                if ($cond === 'damaged') {
                    $damagedStockCost += $lineCost;
                } else {
                    $goodStockCost += $lineCost;
                }
            }

            $goodStockCost = round($goodStockCost, 2);
            $damagedStockCost = round($damagedStockCost, 2);

            if ($goodStockCost > 0.00) {
                $journalLines[] = ['account_id' => $invAccId, 'debit' => $goodStockCost, 'credit' => 0.00, 'memo' => "Restocked returned goods"];
                $journalLines[] = ['account_id' => $cogsAccId, 'debit' => 0.00, 'credit' => $goodStockCost, 'memo' => "COGS reduction for good returns"];
            }

            if ($damagedStockCost > 0.00) {
                $journalLines[] = ['account_id' => $damagedLossAccId, 'debit' => $damagedStockCost, 'credit' => 0.00, 'memo' => "Damaged goods return loss"];
                $journalLines[] = ['account_id' => $cogsAccId, 'debit' => 0.00, 'credit' => $damagedStockCost, 'memo' => "COGS reduction for damaged returns"];
            }

            $entryDate = date('Y-m-d', strtotime($ret['created_at']));
            $header = [
                'branch_id'   => (int)$ret['branch_id'],
                'entry_date'  => $entryDate,
                'source_type' => 'sales_return',
                'source_id'   => $returnId,
                'description' => "Sales Return #{$returnNo}",
                'created_by'  => (int)$ret['created_by']
            ];

            if (!empty($ret['sale_id'])) {
                $saleIdVal = (int)$ret['sale_id'];
                $saleCardPmts = Database::fetchAll("SELECT * FROM payments WHERE sale_id = :sale_id AND LOWER(payment_method) = 'card'", [':sale_id' => $saleIdVal]);
                foreach ($saleCardPmts as $scPmt) {
                    $cardRefundsRow = Database::fetchOne("
                        SELECT COALESCE(SUM(r.amount), 0.00) AS total_refund
                        FROM refunds r
                        JOIN sales_returns sr ON r.sales_return_id = sr.id
                        WHERE sr.sale_id = :sale_id AND LOWER(r.refund_method) = 'card' AND sr.status != 'cancelled'
                    ", [':sale_id' => $saleIdVal]);
                    $cardRefund = (float)($cardRefundsRow['total_refund'] ?? 0);
                    $unsettled = round((float)$scPmt['amount'] - $cardRefund - (float)$scPmt['settled_amount'], 2);

                    if ($unsettled <= 0.001) {
                        Database::execute("UPDATE payments SET settlement_status = 'settled' WHERE id = :id", [':id' => $scPmt['id']]);
                    } elseif ((float)$scPmt['settled_amount'] > 0.001) {
                        Database::execute("UPDATE payments SET settlement_status = 'partially_settled' WHERE id = :id", [':id' => $scPmt['id']]);
                    } else {
                        Database::execute("UPDATE payments SET settlement_status = 'unsettled' WHERE id = :id", [':id' => $scPmt['id']]);
                    }
                }
            }

            return self::postJournalEntry($header, $journalLines);

        } catch (Throwable $e) {
            Logger::error("Accounting::postSalesReturn failed for Return #{$returnId}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Controlled Historical Accounting Backfill / Migration Engine
     * Idempotently posts all eligible Stage 1-9 historical transactions without duplication.
     */
    public static function runHistoricalBackfill(int $userId): array
    {
        $stats = [
            'grn_posted'                  => 0,
            'supplier_payments_posted'     => 0,
            'sales_posted'                => 0,
            'customer_collections_posted' => 0,
            'sales_returns_posted'        => 0,
            'total_posted'                => 0,
            'skipped'                     => 0,
            'errors'                      => []
        ];

        try {
            // 1. Approved GRNs
            $grns = Database::fetchAll("SELECT id FROM grns WHERE status = 'Approved' ORDER BY id ASC");
            foreach ($grns as $g) {
                $res = self::postGRN((int)$g['id']);
                if ($res['success']) {
                    if (!empty($res['already_posted'])) {
                        $stats['skipped']++;
                    } else {
                        $stats['grn_posted']++;
                        $stats['total_posted']++;
                    }
                } else {
                    $stats['errors'][] = "GRN #{$g['id']}: " . $res['message'];
                }
            }

            // 2. Supplier Payments
            $supplierPmts = Database::fetchAll("SELECT id FROM supplier_payments ORDER BY id ASC");
            foreach ($supplierPmts as $sp) {
                $res = self::postSupplierPayment((int)$sp['id']);
                if ($res['success']) {
                    if (!empty($res['already_posted'])) {
                        $stats['skipped']++;
                    } else {
                        $stats['supplier_payments_posted']++;
                        $stats['total_posted']++;
                    }
                } else {
                    $stats['errors'][] = "Supplier Payment #{$sp['id']}: " . $res['message'];
                }
            }

            // 3. Completed POS Sales
            $sales = Database::fetchAll("SELECT id FROM sales WHERE status = 'completed' ORDER BY id ASC");
            foreach ($sales as $s) {
                $res = self::postPOSSale((int)$s['id']);
                if ($res['success']) {
                    if (!empty($res['already_posted'])) {
                        $stats['skipped']++;
                    } else {
                        $stats['sales_posted']++;
                        $stats['total_posted']++;
                    }
                } else {
                    $stats['errors'][] = "Sale #{$s['id']}: " . $res['message'];
                }
            }

            // 4. Customer Collections
            $custPmts = Database::fetchAll("SELECT id FROM customer_credit_ledger WHERE transaction_type = 'payment' ORDER BY id ASC");
            foreach ($custPmts as $cp) {
                $res = self::postCustomerCollection((int)$cp['id']);
                if ($res['success']) {
                    if (!empty($res['already_posted'])) {
                        $stats['skipped']++;
                    } else {
                        $stats['customer_collections_posted']++;
                        $stats['total_posted']++;
                    }
                } else {
                    $stats['errors'][] = "Customer Collection #{$cp['id']}: " . $res['message'];
                }
            }

            // 5. Completed Sales Returns
            $returns = Database::fetchAll("SELECT id FROM sales_returns ORDER BY id ASC");
            foreach ($returns as $r) {
                $res = self::postSalesReturn((int)$r['id']);
                if ($res['success']) {
                    if (!empty($res['already_posted'])) {
                        $stats['skipped']++;
                    } else {
                        $stats['sales_returns_posted']++;
                        $stats['total_posted']++;
                    }
                } else {
                    $stats['errors'][] = "Sales Return #{$r['id']}: " . $res['message'];
                }
            }

            Audit::log('ACCOUNTING_BACKFILL_EXECUTED', 'accounting', null, null, $stats);

            return ['success' => true, 'stats' => $stats];

        } catch (Throwable $e) {
            Logger::error("Historical backfill failed: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Historical backfill failed: ' . $e->getMessage(), 'stats' => $stats];
        }
    }

    /**
     * Automatic Accounting Trigger: Card Settlement Posting (Section 17)
     * Dr Dedicated Bank Account (Net Bank Amount = Gross - Fee)
     * Dr Card Processing Fees (5170) (Processing Fee)
     * Cr Card Receivable (1030) (Gross Amount)
     */
    public static function postCardSettlement(int $settlementId): array
    {
        try {
            $cs = Database::fetchOne("
                SELECT cs.*, cp.name AS processor_name, ba.account_id AS bank_coa_id, ba.account_name AS bank_account_name
                FROM card_settlements cs
                LEFT JOIN card_processors cp ON cs.processor_id = cp.id
                LEFT JOIN bank_accounts ba ON cs.bank_account_id = ba.id
                WHERE cs.id = :id
            ", [':id' => $settlementId]);

            if (!$cs) {
                return ['success' => false, 'message' => 'Card settlement record not found.'];
            }

            if ($cs['status'] !== 'draft') {
                return ['success' => false, 'message' => "Card settlement #{$cs['settlement_no']} is already {$cs['status']}."];
            }

            $grossAmount   = round((float)$cs['gross_amount'], 2);
            $feeAmount     = round((float)$cs['processing_fee'], 2);
            $netBankAmount = round((float)$cs['net_bank_amount'], 2);

            if ($grossAmount <= 0) {
                return ['success' => false, 'message' => 'Gross settlement amount must be greater than zero.'];
            }

            if (abs(($grossAmount - $feeAmount) - $netBankAmount) > 0.001) {
                return ['success' => false, 'message' => 'Net Bank Amount does not match Gross Amount minus Processing Fee.'];
            }

            // Determine Bank COA Account
            if (!empty($cs['bank_coa_id'])) {
                $bankCoaId = (int)$cs['bank_coa_id'];
            } else {
                $bankCoaId = self::getAccountIdByCode(self::CODE_BANK);
            }

            $cardReceivableCoaId = self::getAccountIdByCode(self::CODE_CARD_RECEIVABLE);
            $feeCoaId            = self::getAccountIdByCode(self::CODE_CARD_PROCESSING_FEES);

            $procLabel = !empty($cs['processor_name']) ? $cs['processor_name'] : 'Card Processor';

            $header = [
                'branch_id'   => (int)$cs['branch_id'],
                'entry_date'  => $cs['settlement_date'],
                'source_type' => 'card_settlement',
                'source_id'   => $settlementId,
                'description' => "Card Settlement {$cs['settlement_no']} ({$procLabel})",
                'created_by'  => (int)$cs['created_by']
            ];

            $lines = [];

            // 1. Dr Dedicated Bank Account (Net Bank Amount)
            $lines[] = [
                'account_id' => $bankCoaId,
                'debit'      => $netBankAmount,
                'credit'     => 0.00,
                'memo'       => "Net Settlement Received - {$procLabel}"
            ];

            // 2. Dr Card Processing Fees (5170) (if fee > 0)
            if ($feeAmount > 0.00) {
                $lines[] = [
                    'account_id' => $feeCoaId,
                    'debit'      => $feeAmount,
                    'credit'     => 0.00,
                    'memo'       => "Processing Fee - {$procLabel}"
                ];
            }

            // 3. Cr Card Receivable (1030) (Gross Amount)
            $lines[] = [
                'account_id' => $cardReceivableCoaId,
                'debit'      => 0.00,
                'credit'     => $grossAmount,
                'memo'       => "Clear Card Receivable - {$cs['settlement_no']}"
            ];

            return self::postJournalEntry($header, $lines);

        } catch (Throwable $e) {
            Logger::error("Accounting::postCardSettlement failed for Settlement #{$settlementId}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Automatic Accounting Trigger: Reverse Card Settlement (Section 17)
     * Dr Card Receivable (1030) (Gross Amount)
     * Cr Dedicated Bank Account (Net Bank Amount)
     * Cr Card Processing Fees (5170) (Processing Fee if > 0)
     */
    public static function reverseCardSettlement(int $settlementId, string $reason, int $userId): array
    {
        try {
            $cs = Database::fetchOne("
                SELECT cs.*, cp.name AS processor_name, ba.account_id AS bank_coa_id
                FROM card_settlements cs
                LEFT JOIN card_processors cp ON cs.processor_id = cp.id
                LEFT JOIN bank_accounts ba ON cs.bank_account_id = ba.id
                WHERE cs.id = :id
            ", [':id' => $settlementId]);

            if (!$cs) {
                return ['success' => false, 'message' => 'Card settlement record not found.'];
            }

            if ($cs['status'] !== 'posted') {
                return ['success' => false, 'message' => "Only posted settlements can be reversed. Current status: {$cs['status']}."];
            }

            if (!empty($cs['journal_entry_id'])) {
                $revRes = self::reverseJournalEntry((int)$cs['journal_entry_id'], $reason, $userId);
                if (!$revRes['success']) {
                    return $revRes;
                }
                return [
                    'success'            => true,
                    'reversal_journal_id'=> $revRes['reversal_journal_id'] ?? null,
                    'message'            => "Card Settlement #{$cs['settlement_no']} reversed successfully."
                ];
            }

            // Fallback manual reversal journal if original journal_entry_id was missing
            $grossAmount   = round((float)$cs['gross_amount'], 2);
            $feeAmount     = round((float)$cs['processing_fee'], 2);
            $netBankAmount = round((float)$cs['net_bank_amount'], 2);

            if (!empty($cs['bank_coa_id'])) {
                $bankCoaId = (int)$cs['bank_coa_id'];
            } else {
                $bankCoaId = self::getAccountIdByCode(self::CODE_BANK);
            }

            $cardReceivableCoaId = self::getAccountIdByCode(self::CODE_CARD_RECEIVABLE);
            $feeCoaId            = self::getAccountIdByCode(self::CODE_CARD_PROCESSING_FEES);

            $procLabel = !empty($cs['processor_name']) ? $cs['processor_name'] : 'Card Processor';

            $header = [
                'branch_id'   => (int)$cs['branch_id'],
                'entry_date'  => date('Y-m-d'),
                'source_type' => 'card_settlement_reversal',
                'source_id'   => $settlementId,
                'description' => "Reversal: Card Settlement {$cs['settlement_no']} ({$reason})",
                'created_by'  => $userId
            ];

            $lines = [
                [
                    'account_id' => $cardReceivableCoaId,
                    'debit'      => $grossAmount,
                    'credit'     => 0.00,
                    'memo'       => "Re-instate Card Receivable - Reversal of {$cs['settlement_no']}"
                ],
                [
                    'account_id' => $bankCoaId,
                    'debit'      => 0.00,
                    'credit'     => $netBankAmount,
                    'memo'       => "Reverse Net Settlement - {$procLabel}"
                ]
            ];

            if ($feeAmount > 0.00) {
                $lines[] = [
                    'account_id' => $feeCoaId,
                    'debit'      => 0.00,
                    'credit'     => $feeAmount,
                    'memo'       => "Reverse Processing Fee - {$procLabel}"
                ];
            }

            return self::postJournalEntry($header, $lines);

        } catch (Throwable $e) {
            Logger::error("Accounting::reverseCardSettlement failed for Settlement #{$settlementId}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get Journal Entries List with Pagination & Filtering
     */
    public static function getJournalEntries(array $filters = [], int $page = 1, int $limit = 15): array
    {
        try {
            $where = [];
            $params = [];

            if (!empty($filters['branch_id']) && $filters['branch_id'] !== 'all') {
                $where[] = "je.branch_id = :branch_id";
                $params[':branch_id'] = (int)$filters['branch_id'];
            }

            if (!empty($filters['source_type'])) {
                $where[] = "je.source_type = :source_type";
                $params[':source_type'] = $filters['source_type'];
            }

            if (!empty($filters['posting_status'])) {
                $where[] = "je.posting_status = :posting_status";
                $params[':posting_status'] = $filters['posting_status'];
            }

            if (!empty($filters['start_date'])) {
                $where[] = "je.entry_date >= :start_date";
                $params[':start_date'] = $filters['start_date'];
            }

            if (!empty($filters['end_date'])) {
                $where[] = "je.entry_date <= :end_date";
                $params[':end_date'] = $filters['end_date'];
            }

            if (!empty($filters['search'])) {
                $searchRaw = trim($filters['search']);
                $searchVal = '%' . $searchRaw . '%';
                $searchCleanNum = preg_replace('/[^0-9.]/', '', $searchRaw);

                $conds = [
                    "je.entry_number LIKE :s1",
                    "je.entry_date LIKE :s2",
                    "je.source_type LIKE :s3",
                    "je.description LIKE :s4",
                    "je.posting_status LIKE :s5",
                    "b.name LIKE :s6",
                    "u.username LIKE :s7",
                    "u.full_name LIKE :s8",
                    "CAST(je.total_debit AS CHAR) LIKE :s9",
                    "CAST(je.total_credit AS CHAR) LIKE :s10",
                    "FORMAT(je.total_debit, 2) LIKE :s11",
                    "FORMAT(je.total_credit, 2) LIKE :s12",
                    "EXISTS (
                        SELECT 1 FROM journal_lines jl_s 
                        JOIN chart_of_accounts coa_s ON jl_s.account_id = coa_s.id 
                        WHERE jl_s.journal_entry_id = je.id 
                        AND (
                            coa_s.account_code LIKE :s13 
                            OR coa_s.account_name LIKE :s14 
                            OR jl_s.memo LIKE :s15
                            OR CAST(jl_s.debit AS CHAR) LIKE :s16
                            OR CAST(jl_s.credit AS CHAR) LIKE :s17
                            OR FORMAT(jl_s.debit, 2) LIKE :s18
                            OR FORMAT(jl_s.credit, 2) LIKE :s19
                        )
                    )"
                ];

                if ($searchCleanNum !== '' && is_numeric($searchCleanNum)) {
                    $conds[] = "CAST(je.total_debit AS CHAR) LIKE :s20";
                    $conds[] = "CAST(je.total_credit AS CHAR) LIKE :s21";
                    $conds[] = "FORMAT(je.total_debit, 2) LIKE :s22";
                    $conds[] = "FORMAT(je.total_credit, 2) LIKE :s23";
                }

                $where[] = "(" . implode(" OR ", $conds) . ")";
                
                $params[':s1']  = $searchVal;
                $params[':s2']  = $searchVal;
                $params[':s3']  = $searchVal;
                $params[':s4']  = $searchVal;
                $params[':s5']  = $searchVal;
                $params[':s6']  = $searchVal;
                $params[':s7']  = $searchVal;
                $params[':s8']  = $searchVal;
                $params[':s9']  = $searchVal;
                $params[':s10'] = $searchVal;
                $params[':s11'] = $searchVal;
                $params[':s12'] = $searchVal;
                $params[':s13'] = $searchVal;
                $params[':s14'] = $searchVal;
                $params[':s15'] = $searchVal;
                $params[':s16'] = $searchVal;
                $params[':s17'] = $searchVal;
                $params[':s18'] = $searchVal;
                $params[':s19'] = $searchVal;
                if ($searchCleanNum !== '' && is_numeric($searchCleanNum)) {
                    $numVal = '%' . $searchCleanNum . '%';
                    $params[':s20'] = $numVal;
                    $params[':s21'] = $numVal;
                    $params[':s22'] = $numVal;
                    $params[':s23'] = $numVal;
                }
            }

            $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

            $countRow = Database::fetchOne("
                SELECT COUNT(DISTINCT je.id) AS total 
                FROM journal_entries je 
                LEFT JOIN branches b ON je.branch_id = b.id 
                LEFT JOIN users u ON je.created_by = u.id 
                $whereSql
            ", $params);

            $totalItems = (int)($countRow['total'] ?? 0);
            $offset = ($page - 1) * $limit;

            $items = Database::fetchAll("
                SELECT 
                    je.*,
                    b.name AS branch_name,
                    u.username AS creator_name
                FROM journal_entries je
                LEFT JOIN branches b ON je.branch_id = b.id
                LEFT JOIN users u ON je.created_by = u.id
                $whereSql
                ORDER BY je.id DESC
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
            Logger::error("Failed to fetch journal entries list: " . $e->getMessage(), $e);
            return ['items' => [], 'totalItems' => 0, 'page' => 1, 'limit' => $limit, 'totalPages' => 1];
        }
    }

    /**
     * Get single Journal Entry with lines
     */
    public static function getJournalEntryDetails(int $id): ?array
    {
        try {
            $header = Database::fetchOne("
                SELECT 
                    je.*,
                    b.name AS branch_name,
                    u.full_name AS creator_name
                FROM journal_entries je
                JOIN branches b ON je.branch_id = b.id
                JOIN users u ON je.created_by = u.id
                WHERE je.id = :id
            ", [':id' => $id]);

            if (!$header) return null;

            $lines = Database::fetchAll("
                SELECT 
                    jl.*,
                    coa.account_code,
                    coa.account_name,
                    coa.account_type
                FROM journal_lines jl
                JOIN chart_of_accounts coa ON jl.account_id = coa.id
                WHERE jl.journal_entry_id = :id
                ORDER BY jl.id ASC
            ", [':id' => $id]);

            $header['lines'] = $lines;
            return $header;

        } catch (Throwable $e) {
            Logger::error("Failed to fetch journal entry #{$id} details: " . $e->getMessage(), $e);
            return null;
        }
    }

    /**
     * Get General Ledger Foundation (Ledger report by account, date range, branch)
     */
    public static function getGeneralLedger(array $filters = [], int $page = 1, int $limit = 20): array
    {
        try {
            $where = ["je.posting_status IN ('posted', 'reversed')"];
            $params = [];

            if (!empty($filters['account_id'])) {
                $where[] = "jl.account_id = :account_id";
                $params[':account_id'] = (int)$filters['account_id'];
            }

            if (!empty($filters['branch_id']) && $filters['branch_id'] !== 'all') {
                $where[] = "je.branch_id = :branch_id";
                $params[':branch_id'] = (int)$filters['branch_id'];
            }

            if (!empty($filters['start_date'])) {
                $where[] = "je.entry_date >= :start_date";
                $params[':start_date'] = $filters['start_date'];
            }

            if (!empty($filters['end_date'])) {
                $where[] = "je.entry_date <= :end_date";
                $params[':end_date'] = $filters['end_date'];
            }

            if (!empty($filters['source_type'])) {
                $where[] = "je.source_type = :source_type";
                $params[':source_type'] = $filters['source_type'];
            }

            if (!empty($filters['search'])) {
                $searchRaw = trim($filters['search']);
                $searchVal = '%' . $searchRaw . '%';
                $searchCleanNum = preg_replace('/[^0-9.]/', '', $searchRaw);

                $conds = [
                    "je.entry_date LIKE :gs1",
                    "je.entry_number LIKE :gs2",
                    "coa.account_code LIKE :gs3",
                    "coa.account_name LIKE :gs4",
                    "CONCAT(coa.account_code, ' - ', coa.account_name) LIKE :gs5",
                    "je.description LIKE :gs6",
                    "jl.memo LIKE :gs7",
                    "b.name LIKE :gs8",
                    "CAST(jl.debit AS CHAR) LIKE :gs9",
                    "CAST(jl.credit AS CHAR) LIKE :gs10",
                    "FORMAT(jl.debit, 2) LIKE :gs11",
                    "FORMAT(jl.credit, 2) LIKE :gs12"
                ];

                if ($searchCleanNum !== '' && is_numeric($searchCleanNum)) {
                    $conds[] = "CAST(jl.debit AS CHAR) LIKE :gs13";
                    $conds[] = "CAST(jl.credit AS CHAR) LIKE :gs14";
                    $conds[] = "FORMAT(jl.debit, 2) LIKE :gs15";
                    $conds[] = "FORMAT(jl.credit, 2) LIKE :gs16";
                }

                $where[] = "(" . implode(" OR ", $conds) . ")";
                
                $params[':gs1']  = $searchVal;
                $params[':gs2']  = $searchVal;
                $params[':gs3']  = $searchVal;
                $params[':gs4']  = $searchVal;
                $params[':gs5']  = $searchVal;
                $params[':gs6']  = $searchVal;
                $params[':gs7']  = $searchVal;
                $params[':gs8']  = $searchVal;
                $params[':gs9']  = $searchVal;
                $params[':gs10'] = $searchVal;
                $params[':gs11'] = $searchVal;
                $params[':gs12'] = $searchVal;
                if ($searchCleanNum !== '' && is_numeric($searchCleanNum)) {
                    $numVal = '%' . $searchCleanNum . '%';
                    $params[':gs13'] = $numVal;
                    $params[':gs14'] = $numVal;
                    $params[':gs15'] = $numVal;
                    $params[':gs16'] = $numVal;
                }
            }

            $whereSql = implode(" AND ", $where);

            $countRow = Database::fetchOne("
                SELECT COUNT(*) AS total 
                FROM journal_lines jl
                JOIN journal_entries je ON jl.journal_entry_id = je.id
                JOIN chart_of_accounts coa ON jl.account_id = coa.id
                LEFT JOIN branches b ON je.branch_id = b.id
                WHERE $whereSql
            ", $params);

            $totalItems = (int)($countRow['total'] ?? 0);
            $offset = ($page - 1) * $limit;

            $items = Database::fetchAll("
                SELECT 
                    jl.id AS line_id,
                    jl.debit,
                    jl.credit,
                    jl.memo,
                    je.id AS journal_id,
                    je.entry_number,
                    je.entry_date,
                    je.source_type,
                    je.source_id,
                    je.description,
                    coa.account_code,
                    coa.account_name,
                    coa.account_type,
                    b.name AS branch_name
                FROM journal_lines jl
                JOIN journal_entries je ON jl.journal_entry_id = je.id
                JOIN chart_of_accounts coa ON jl.account_id = coa.id
                LEFT JOIN branches b ON je.branch_id = b.id
                WHERE $whereSql
                ORDER BY je.entry_date DESC, je.id DESC, jl.id ASC
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
            Logger::error("Failed to fetch General Ledger: " . $e->getMessage(), $e);
            return ['items' => [], 'totalItems' => 0, 'page' => 1, 'limit' => $limit, 'totalPages' => 1];
        }
    }

    /**
     * Fetch Trial Balance summary
     */
    public static function getTrialBalance(array $filters = []): array
    {
        require_once __DIR__ . '/ReportsEngine.php';
        return ReportsEngine::getTrialBalance($filters);
    }
}
