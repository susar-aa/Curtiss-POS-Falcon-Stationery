<?php
/**
 * Curtiss POS - Real Bank Accounts, Deposits & Bank Reconciliation Core Engine (Stage 13 & Section 11 Enhancements)
 * Handles real company bank accounts, dedicated COA mapping, cash deposits (drawer vs closed day),
 * bank activity register & running balance, bank charges, interest income (4050), direct adjustments,
 * bank reconciliation sessions & zero-difference completion enforcement.
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Audit.php';
require_once __DIR__ . '/Accounting.php';
require_once __DIR__ . '/BusinessDay.php';

class Banking
{
    /**
     * Generate continuous, collision-proof transaction reference numbers (DEP-0001, BREC-0001)
     */
    public static function generateNumber(string $prefix, string $table): string
    {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT MAX(id) AS max_id FROM {$table}");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $nextNum = ((int)($row['max_id'] ?? 0)) + 1;

            $field = ($table === 'bank_deposits') ? 'deposit_no' :
                     (($table === 'bank_reconciliations') ? 'reconciliation_no' : 'id');

            while (true) {
                $candidate = $prefix . '-' . sprintf('%04d', $nextNum);
                if ($table === 'bank_deposits' || $table === 'bank_reconciliations') {
                    $chk = Database::fetchOne("SELECT id FROM {$table} WHERE {$field} = :no", [':no' => $candidate]);
                    if (!$chk) {
                        return $candidate;
                    }
                } else {
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
     * Get list of real Bank Accounts
     */
    public static function getBankAccounts(?int $branchId = null, bool $activeOnly = true): array
    {
        try {
            $where = [];
            $params = [];

            if ($activeOnly) {
                $where[] = "ba.status = 'active'";
            }

            if ($branchId !== null) {
                $where[] = "(ba.branch_id IS NULL OR ba.branch_id = :branch_id)";
                $params[':branch_id'] = $branchId;
            }

            $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

            $accounts = Database::fetchAll("
                SELECT 
                    ba.id, ba.bank_name, ba.account_name, ba.account_number, ba.branch_id,
                    ba.bank_branch_name, ba.opening_balance, ba.status, ba.account_id,
                    ba.created_at, ba.updated_at,
                    b.name AS branch_name, 
                    coa.account_code, 
                    coa.account_name AS coa_account_name
                FROM bank_accounts ba
                LEFT JOIN branches b ON ba.branch_id = b.id
                JOIN chart_of_accounts coa ON ba.account_id = coa.id
                $whereSql
                ORDER BY ba.id ASC
            ", $params);

            foreach ($accounts as &$acc) {
                $acc['current_balance'] = self::getBankBalance((int)$acc['id']);
            }

            return $accounts;
        } catch (Throwable $e) {
            Logger::error("Failed to fetch bank accounts: " . $e->getMessage(), $e);
            return [];
        }
    }

    /**
     * Find single Bank Account details
     */
    public static function getBankAccount(int $id): ?array
    {
        try {
            $acc = Database::fetchOne("
                SELECT 
                    ba.id, ba.bank_name, ba.account_name, ba.account_number, ba.branch_id,
                    ba.bank_branch_name, ba.opening_balance, ba.status, ba.account_id,
                    ba.created_at, ba.updated_at,
                    b.name AS branch_name, 
                    coa.account_code, 
                    coa.account_name AS coa_account_name
                FROM bank_accounts ba
                LEFT JOIN branches b ON ba.branch_id = b.id
                JOIN chart_of_accounts coa ON ba.account_id = coa.id
                WHERE ba.id = :id
            ", [':id' => $id]);

            if ($acc) {
                $acc['current_balance'] = self::getBankBalance($id);
            }
            return $acc;
        } catch (Throwable $e) {
            Logger::error("Failed to fetch bank account #{$id}: " . $e->getMessage(), $e);
            return null;
        }
    }

    /**
     * Get dynamic GL running balance for a specific Bank Account ID
     */
    public static function getBankBalance(int $bankAccountId): float
    {
        try {
            $acc = Database::fetchOne("SELECT account_id, opening_balance FROM bank_accounts WHERE id = :id", [':id' => $bankAccountId]);
            if (!$acc) return 0.00;

            $coaAccountId = (int)$acc['account_id'];

            // Check if opening balance was posted in GL for this bank account
            $hasObInGl = Database::fetchOne("
                SELECT id 
                FROM journal_entries 
                WHERE source_type = 'opening_balance' 
                  AND source_id = :bank_id 
                  AND posting_status = 'posted'
            ", [':bank_id' => $bankAccountId]);

            $glRow = Database::fetchOne("
                SELECT SUM(jl.debit) - SUM(jl.credit) AS net_gl
                FROM journal_lines jl
                JOIN journal_entries je ON jl.journal_entry_id = je.id
                WHERE jl.account_id = :acc_id AND je.posting_status = 'posted'
            ", [':acc_id' => $coaAccountId]);

            $netGl = (float)($glRow['net_gl'] ?? 0.00);

            if ($hasObInGl) {
                // Opening balance is already captured in net_gl
                return round($netGl, 2);
            } else {
                // Fallback for legacy accounts without GL opening balance entry
                $opening = (float)$acc['opening_balance'];
                return round($opening + $netGl, 2);
            }
        } catch (Throwable $e) {
            Logger::error("Failed to fetch bank balance for #{$bankAccountId}: " . $e->getMessage(), $e);
            return 0.00;
        }
    }

    /**
     * Save / Update Bank Account & Auto-Link Dedicated COA Asset Account
     */
    public static function saveBankAccount(array $data, int $userId): array
    {
        try {
            $id            = !empty($data['id']) ? (int)$data['id'] : null;
            $bankName      = trim($data['bank_name'] ?? '');
            $accountName   = trim($data['account_name'] ?? '');
            $accountNumber = trim($data['account_number'] ?? '');
            $branchId      = !empty($data['branch_id']) ? (int)$data['branch_id'] : null;
            $bankBranch    = !empty($data['bank_branch_name']) ? trim($data['bank_branch_name']) : null;
            $openingBal    = round((float)($data['opening_balance'] ?? 0), 2);
            $status        = ($data['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
            $accountId     = !empty($data['account_id']) ? (int)$data['account_id'] : null;

            if (empty($bankName) || empty($accountName) || empty($accountNumber)) {
                return ['success' => false, 'message' => 'Bank name, account name, and account number are required.'];
            }

            // Check duplicate account number
            $dupSql = "SELECT id FROM bank_accounts WHERE account_number = :num";
            $dupParams = [':num' => $accountNumber];
            if ($id) {
                $dupSql .= " AND id != :id";
                $dupParams[':id'] = $id;
            }
            if (Database::fetchOne($dupSql, $dupParams)) {
                return ['success' => false, 'message' => "Bank account number '{$accountNumber}' is already registered."];
            }

            // If editing an existing bank account, ensure $accountId is retrieved if not passed in $data
            if ($id && empty($accountId)) {
                $existingBank = Database::fetchOne("SELECT account_id FROM bank_accounts WHERE id = :id", [':id' => $id]);
                if ($existingBank && !empty($existingBank['account_id'])) {
                    $accountId = (int)$existingBank['account_id'];
                }
            }

            // Sanitize accountName to remove redundant bank name prefix and account number suffix
            $cleanAccountName = preg_replace('/^' . preg_quote($bankName, '/') . '\s*[-–]\s*/iu', '', $accountName);
            $cleanAccountName = preg_replace('/\s*\(' . preg_quote($accountNumber, '/') . '\)$/iu', '', $cleanAccountName);
            $cleanAccountName = trim($cleanAccountName);
            if (!empty($cleanAccountName)) {
                $accountName = $cleanAccountName;
            }
            $coaAccountName = "{$bankName} - {$accountName} ({$accountNumber})";

            // Verify or Auto-create dedicated COA Asset Account
            if ($accountId) {
                $chkCoa = Database::fetchOne("
                    SELECT id, account_type, status 
                    FROM chart_of_accounts 
                    WHERE id = :id
                ", [':id' => $accountId]);

                if (!$chkCoa || strtolower((string)$chkCoa['account_type']) !== 'asset') {
                    return ['success' => false, 'message' => 'Linked COA account does not exist or is not an Asset type account.'];
                }

                // Synchronize existing COA account name, branch and status
                Database::execute("
                    UPDATE chart_of_accounts SET
                        account_name = :account_name,
                        branch_id    = :branch_id,
                        status       = :status
                    WHERE id = :id
                ", [
                    ':account_name' => $coaAccountName,
                    ':branch_id'    => $branchId,
                    ':status'       => $status,
                    ':id'           => $accountId
                ]);
            } else {
                $parentBankCoaId = Accounting::getAccountIdByCode(Accounting::CODE_BANK); // 1020 Bank Account

                // Generate next sub-account code e.g., 1021, 1022...
                $nextCode = '1021';
                $maxCodeRow = Database::fetchOne("
                    SELECT MAX(CAST(account_code AS UNSIGNED)) AS max_c
                    FROM chart_of_accounts
                    WHERE account_code LIKE '102%'
                ");
                if ($maxCodeRow && (int)$maxCodeRow['max_c'] >= 1020) {
                    $nextCode = (string)((int)$maxCodeRow['max_c'] + 1);
                }

                // Ensure no code collision with existing accounts
                while (Database::fetchOne("SELECT id FROM chart_of_accounts WHERE account_code = :code", [':code' => $nextCode])) {
                    $nextCode = (string)((int)$nextCode + 1);
                }

                $coaRes = Accounting::createAccount([
                    'account_code' => $nextCode,
                    'account_name' => $coaAccountName,
                    'account_type' => 'asset',
                    'parent_id'    => $parentBankCoaId,
                    'branch_id'    => $branchId,
                    'is_system'    => 0,
                    'status'       => 'active'
                ], $userId);

                if (!$coaRes['success']) {
                    return ['success' => false, 'message' => "Failed to create dedicated COA account: " . $coaRes['message']];
                }
                $accountId = (int)($coaRes['id'] ?? 0);
            }

            if ($id) {
                Database::execute("
                    UPDATE bank_accounts SET
                        bank_name = :bank_name, account_name = :account_name, account_number = :account_number,
                        branch_id = :branch_id, bank_branch_name = :bank_branch_name, opening_balance = :opening_balance,
                        status = :status, account_id = :account_id
                    WHERE id = :id
                ", [
                    ':bank_name'        => $bankName,
                    ':account_name'     => $accountName,
                    ':account_number'   => $accountNumber,
                    ':branch_id'        => $branchId,
                    ':bank_branch_name' => $bankBranch,
                    ':opening_balance'  => $openingBal,
                    ':status'           => $status,
                    ':account_id'       => $accountId,
                    ':id'               => $id
                ]);

                // Synchronize double-entry opening balance journal entry
                self::syncOpeningBalanceJournal((int)$id, $accountId, $openingBal, $bankName, $accountName, $accountNumber, $branchId, $userId);

                Audit::log('BANK_ACCOUNT_UPDATED', 'banking', (string)$id, null, [
                    'bank_name' => $bankName, 'account_number' => $accountNumber
                ]);

                return ['success' => true, 'id' => $id, 'message' => "Bank Account '{$accountName}' updated successfully."];
            } else {
                Database::execute("
                    INSERT INTO bank_accounts (
                        bank_name, account_name, account_number, branch_id, bank_branch_name, opening_balance, status, account_id
                    ) VALUES (
                        :bank_name, :account_name, :account_number, :branch_id, :bank_branch_name, :opening_balance, :status, :account_id
                    )
                ", [
                    ':bank_name'        => $bankName,
                    ':account_name'     => $accountName,
                    ':account_number'   => $accountNumber,
                    ':branch_id'        => $branchId,
                    ':bank_branch_name' => $bankBranch,
                    ':opening_balance'  => $openingBal,
                    ':status'           => $status,
                    ':account_id'       => $accountId
                ]);

                $newId = (int)Database::lastInsertId();

                // Post initial double-entry opening balance journal entry
                self::syncOpeningBalanceJournal($newId, $accountId, $openingBal, $bankName, $accountName, $accountNumber, $branchId, $userId);

                Audit::log('BANK_ACCOUNT_CREATED', 'banking', (string)$newId, null, [
                    'bank_name' => $bankName, 'account_number' => $accountNumber, 'opening_balance' => $openingBal
                ]);

                return ['success' => true, 'id' => $newId, 'message' => "Bank Account '{$accountName}' created successfully."];
            }

        } catch (Throwable $e) {
            Logger::error("Failed to save bank account: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Post or synchronize opening balance double-entry journal for a bank account
     */
    public static function syncOpeningBalanceJournal(
        int $bankAccountId,
        int $coaAccountId,
        float $openingBal,
        string $bankName,
        string $accountName,
        string $accountNumber,
        ?int $branchId,
        int $userId
    ): void {
        try {
            $existingJe = Database::fetchOne("
                SELECT id, total_debit, posting_status 
                FROM journal_entries 
                WHERE source_type = 'opening_balance' 
                  AND source_id = :bank_id 
                  AND posting_status = 'posted'
                LIMIT 1
            ", [':bank_id' => $bankAccountId]);

            $equityAccId = Accounting::getAccountIdByCode(Accounting::CODE_EQUITY); // 3010 Opening Balance Equity
            if (!$equityAccId) {
                Logger::error("Opening Balance Equity account (3010) not found.");
                return;
            }

            if ($existingJe) {
                $curJeAmount = (float)$existingJe['total_debit'];
                if (abs($curJeAmount - $openingBal) > 0.001) {
                    if ($openingBal <= 0) {
                        Database::execute("UPDATE journal_entries SET posting_status = 'reversed' WHERE id = :id", [':id' => $existingJe['id']]);
                    } else {
                        Database::execute("
                            UPDATE journal_entries 
                            SET total_debit = :amt, total_credit = :amt, branch_id = :branch_id 
                            WHERE id = :id
                        ", [
                            ':amt'       => $openingBal,
                            ':branch_id' => $branchId ?: 1,
                            ':id'        => $existingJe['id']
                        ]);

                        Database::execute("
                            UPDATE journal_lines 
                            SET debit = :amt 
                            WHERE journal_entry_id = :je_id AND account_id = :acc_id
                        ", [':amt' => $openingBal, ':je_id' => $existingJe['id'], ':acc_id' => $coaAccountId]);

                        Database::execute("
                            UPDATE journal_lines 
                            SET credit = :amt 
                            WHERE journal_entry_id = :je_id AND account_id = :eq_id
                        ", [':amt' => $openingBal, ':je_id' => $existingJe['id'], ':eq_id' => $equityAccId]);
                    }
                }
            } elseif ($openingBal > 0) {
                $header = [
                    'branch_id'   => $branchId ?: 1,
                    'entry_date'  => date('Y-m-d'),
                    'source_type' => 'opening_balance',
                    'source_id'   => $bankAccountId,
                    'description' => "Opening Balance for Bank Account: {$bankName} - {$accountName} ({$accountNumber})",
                    'created_by'  => $userId
                ];

                $lines = [
                    [
                        'account_id' => $coaAccountId,
                        'debit'      => $openingBal,
                        'credit'     => 0.00,
                        'memo'       => "Opening Balance for {$bankName} - {$accountName}"
                    ],
                    [
                        'account_id' => $equityAccId,
                        'debit'      => 0.00,
                        'credit'     => $openingBal,
                        'memo'       => "Opening Balance Equity for {$bankName} - {$accountName}"
                    ]
                ];

                Accounting::postJournalEntry($header, $lines);
            }
        } catch (Throwable $e) {
            Logger::error("Failed to sync bank opening balance journal: " . $e->getMessage(), $e);
        }
    }

    /**
     * Delete Bank Account safely (blocked if transaction history exists)
     */
    public static function deleteBankAccount(int $id): array
    {
        try {
            $bank = self::getBankAccount($id);
            if (!$bank) {
                return ['success' => false, 'message' => 'Bank account not found.'];
            }

            $depCount = Database::fetchOne("SELECT COUNT(*) AS total FROM bank_deposits WHERE bank_account_id = :id", [':id' => $id]);
            $recCount = Database::fetchOne("SELECT COUNT(*) AS total FROM bank_reconciliations WHERE bank_account_id = :id", [':id' => $id]);
            $spCount  = Database::fetchOne("SELECT COUNT(*) AS total FROM supplier_payments WHERE bank_account_id = :id", [':id' => $id]);
            $cpCount  = Database::fetchOne("SELECT COUNT(*) AS total FROM customer_payments WHERE bank_account_id = :id", [':id' => $id]);
            $jlCount  = Database::fetchOne("SELECT COUNT(*) AS total FROM journal_lines WHERE account_id = :acc_id", [':acc_id' => $bank['account_id']]);

            $totalRefs = (int)($depCount['total'] ?? 0) + (int)($recCount['total'] ?? 0) + (int)($spCount['total'] ?? 0) + (int)($cpCount['total'] ?? 0) + (int)($jlCount['total'] ?? 0);

            if ($totalRefs > 0) {
                return [
                    'success' => false,
                    'message' => 'Cannot delete bank account with existing transaction history. Deactivate it instead.'
                ];
            }

            Database::execute("DELETE FROM bank_accounts WHERE id = :id", [':id' => $id]);
            Audit::log('BANK_ACCOUNT_DELETED', 'banking', (string)$id, null, ['bank_name' => $bank['bank_name']]);

            return ['success' => true, 'message' => "Bank account '{$bank['account_name']}' deleted successfully."];

        } catch (Throwable $e) {
            Logger::error("Failed to delete bank account #{$id}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Create Cash Deposit into Bank Account
     */
    public static function createDeposit(array $data, int $userId, int $branchId): array
    {
        $db = Database::getInstance()->getConnection();

        try {
            $db->beginTransaction();

            $bankAccountId = (int)($data['bank_account_id'] ?? 0);
            $amount        = round((float)($data['amount'] ?? 0), 2);
            $depositDate   = !empty($data['deposit_date']) ? trim($data['deposit_date']) : date('Y-m-d');
            $sourceType    = strtolower(trim($data['source_type'] ?? 'active_drawer')); // 'active_drawer', 'closed_day', 'other_cash'
            $bDayId        = !empty($data['business_day_id']) ? (int)$data['business_day_id'] : null;
            $referenceNo   = !empty($data['reference_no']) ? trim($data['reference_no']) : null;
            $notes         = !empty($data['notes']) ? trim($data['notes']) : null;

            if ($bankAccountId <= 0 || $amount <= 0.00) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Valid bank account and positive amount (> 0) are required.'];
            }

            $bank = self::getBankAccount($bankAccountId);
            if (!$bank || $bank['status'] !== 'active') {
                $db->rollBack();
                return ['success' => false, 'message' => 'Selected bank account is invalid or inactive. Inactive bank accounts cannot be selected for new deposits.'];
            }

            $depositNo = self::generateNumber('DEP', 'bank_deposits');
            $activeDay = null;

            // Handle Drawer Integration & Closed Day Linkage based on Source Type
            if ($sourceType === 'active_drawer') {
                // Cash removed from ACTIVE POS Drawer -> Requires active open business day & records Cash Out drawer movement
                $activeDay = BusinessDay::getOrCreateActiveDay($branchId, $userId);
                if (!$activeDay) {
                    $db->rollBack();
                    return [
                        'success' => false,
                        'message' => 'Cannot deposit from Active Drawer. No active business day is currently open for this branch.'
                    ];
                }

                $bDayId = (int)$activeDay['id'];

                // Verify active drawer cash availability
                $expectedDrawerCash = BusinessDay::getExpectedDrawerCash($branchId);
                if ($amount > ($expectedDrawerCash + 0.001)) {
                    $db->rollBack();
                    return [
                        'success' => false,
                        'message' => "Insufficient Active Drawer cash balance for deposit. Available in drawer: LKR " . number_format($expectedDrawerCash, 2) . ", Requested: LKR " . number_format($amount, 2) . "."
                    ];
                }

            } elseif ($sourceType === 'closed_day') {
                // Depositing already-removed cash from a CLOSED day -> Do NOT reduce drawer a second time!
                if ($bDayId <= 0) {
                    $db->rollBack();
                    return ['success' => false, 'message' => 'Closed business day ID is required when source is closed_day.'];
                }

                $closedDay = Database::fetchOne("SELECT * FROM business_days WHERE id = :id AND status = 'closed'", [':id' => $bDayId]);
                if (!$closedDay) {
                    $db->rollBack();
                    return ['success' => false, 'message' => 'Specified closed business day not found or not closed.'];
                }

                // Check cumulative deposits linked to this closed business day
                $cumRow = Database::fetchOne("
                    SELECT SUM(amount) AS total_deposited 
                    FROM bank_deposits 
                    WHERE business_day_id = :bday_id AND source_type = 'closed_day'
                ", [':bday_id' => $bDayId]);

                $cumDeposited = round((float)($cumRow['total_deposited'] ?? 0.00), 2);
                $maxAllowed   = round((float)($closedDay['cash_to_deposit'] ?? $closedDay['cash_to_remove'] ?? $closedDay['actual_amount'] ?? 0.00), 2);

                if (($cumDeposited + $amount) > ($maxAllowed + 0.001)) {
                    $db->rollBack();
                    return [
                        'success' => false,
                        'message' => "Cumulative deposits (LKR " . number_format($cumDeposited + $amount, 2) . ") exceed available cash to remove (LKR " . number_format($maxAllowed, 2) . ") for this closed business day."
                    ];
                }
            }

            // 1. Insert Deposit Header Record
            $stmtD = $db->prepare("
                INSERT INTO bank_deposits (
                    deposit_no, branch_id, bank_account_id, deposit_date, amount,
                    source_type, business_day_id, reference_no, notes, created_by
                ) VALUES (
                    :no, :branch, :bank_acc, :ddate, :amount,
                    :stype, :bday, :ref, :notes, :user
                )
            ");

            $stmtD->execute([
                ':no'       => $depositNo,
                ':branch'   => $branchId,
                ':bank_acc' => $bankAccountId,
                ':ddate'    => $depositDate,
                ':amount'   => $amount,
                ':stype'    => $sourceType,
                ':bday'     => $bDayId,
                ':ref'      => $referenceNo,
                ':notes'    => $notes,
                ':user'     => $userId
            ]);

            $depositId = (int)$db->lastInsertId();

            // 2. Record Drawer Movement ONLY if source_type === 'active_drawer'
            if ($sourceType === 'active_drawer' && $activeDay) {
                $mvRes = BusinessDay::recordDrawerMovement([
                    'business_day_id' => (int)$activeDay['id'],
                    'branch_id'       => $branchId,
                    'movement_type'   => 'cash_out',
                    'source_type'     => 'bank_deposit',
                    'source_id'       => $depositId,
                    'amount'          => $amount,
                    'direction'       => 'out',
                    'reason'          => "Bank Cash Deposit ({$depositNo} - Slip: {$referenceNo})",
                    'notes'           => $notes,
                    'user_id'         => $userId
                ]);

                if (!$mvRes['success']) {
                    $db->rollBack();
                    return $mvRes;
                }
            }

            // 3. Post Accounting Double-Entry (Dr Selected Bank COA, Cr Cash in Hand 1010)
            $bankCoaId = (int)$bank['account_id'];
            $cashCoaId = Accounting::getAccountIdByCode(Accounting::CODE_CASH);

            $lines = [
                ['account_id' => $bankCoaId, 'debit' => $amount, 'credit' => 0.00, 'memo' => "Cash Deposit to {$bank['account_name']}"],
                ['account_id' => $cashCoaId, 'debit' => 0.00, 'credit' => $amount, 'memo' => "Cash Deposit Slip #{$referenceNo}"]
            ];

            $header = [
                'branch_id'   => $branchId,
                'entry_date'  => $depositDate,
                'source_type' => 'manual',
                'source_id'   => $depositId,
                'description' => "Bank Cash Deposit #{$depositNo} into {$bank['bank_name']} ({$bank['account_number']})",
                'created_by'  => $userId
            ];

            $jeRes = Accounting::postJournalEntry($header, $lines);
            if (!$jeRes['success']) {
                $db->rollBack();
                return ['success' => false, 'message' => "Accounting posting failed: " . $jeRes['message']];
            }

            $jeId = (int)($jeRes['journal_id'] ?? $jeRes['id'] ?? $jeRes['journal_entry_id'] ?? 0);

            $db->prepare("UPDATE bank_deposits SET journal_entry_id = :je_id WHERE id = :id")
               ->execute([':je_id' => $jeId, ':id' => $depositId]);

            $db->commit();

            Audit::log('BANK_DEPOSIT_CREATED', 'banking', (string)$depositId, null, [
                'deposit_no' => $depositNo, 'bank_name' => $bank['bank_name'], 'amount' => $amount, 'source_type' => $sourceType
            ]);

            return [
                'success'    => true,
                'deposit_id' => $depositId,
                'deposit_no' => $depositNo,
                'amount'     => $amount,
                'message'    => "Cash deposit of LKR " . number_format($amount, 2) . " into {$bank['bank_name']} ({$bank['account_number']}) processed successfully."
            ];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to process bank deposit: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get Bank Activity Register with Running Balance for a specific Bank Account
     */
    public static function getBankRegister(int $bankAccountId, array $filters = [], int $page = 1, int $limit = 20): array
    {
        try {
            $bank = self::getBankAccount($bankAccountId);
            if (!$bank) {
                return ['items' => [], 'totalItems' => 0, 'page' => 1, 'limit' => $limit, 'totalPages' => 1];
            }

            $coaAccountId = (int)$bank['account_id'];

            $where = ["jl.account_id = :acc_id AND je.posting_status = 'posted'"];
            $params = [':acc_id' => $coaAccountId];

            if (!empty($filters['start_date'])) {
                $where[] = "je.entry_date >= :start_date";
                $params[':start_date'] = $filters['start_date'];
            }

            if (!empty($filters['end_date'])) {
                $where[] = "je.entry_date <= :end_date";
                $params[':end_date'] = $filters['end_date'];
            }

            if (!empty($filters['search'])) {
                $where[] = "(je.entry_number LIKE :q OR je.description LIKE :q OR jl.memo LIKE :q)";
                $params[':q'] = '%' . trim($filters['search']) . '%';
            }

            $whereSql = implode(" AND ", $where);

            $countRow = Database::fetchOne("
                SELECT COUNT(*) AS total
                FROM journal_lines jl
                JOIN journal_entries je ON jl.journal_entry_id = je.id
                WHERE $whereSql
            ", $params);

            $totalItems = (int)($countRow['total'] ?? 0);
            $offset = ($page - 1) * $limit;

            $items = Database::fetchAll("
                SELECT 
                    jl.id AS journal_line_id,
                    je.id AS journal_entry_id,
                    je.entry_number,
                    je.entry_date,
                    je.source_type,
                    je.source_id,
                    je.description,
                    jl.debit AS deposit_in,
                    jl.credit AS payment_out,
                    jl.memo,
                    u.username AS creator_name
                FROM journal_lines jl
                JOIN journal_entries je ON jl.journal_entry_id = je.id
                JOIN users u ON je.created_by = u.id
                WHERE $whereSql
                ORDER BY je.entry_date ASC, jl.id ASC
                LIMIT $limit OFFSET $offset
            ", $params);

            // Compute running balance
            $running = (float)$bank['opening_balance'];
            foreach ($items as &$it) {
                $in = (float)$it['deposit_in'];
                $out = (float)$it['payment_out'];
                $running += ($in - $out);
                $it['running_balance'] = round($running, 2);
            }

            return [
                'bank_account' => $bank,
                'items'        => $items,
                'totalItems'   => $totalItems,
                'page'         => $page,
                'limit'        => $limit,
                'totalPages'   => max(1, ceil($totalItems / $limit))
            ];

        } catch (Throwable $e) {
            Logger::error("Failed to fetch bank register for #{$bankAccountId}: " . $e->getMessage(), $e);
            return ['items' => [], 'totalItems' => 0, 'page' => 1, 'limit' => $limit, 'totalPages' => 1];
        }
    }

    /**
     * Start Bank Reconciliation Session
     */
    public static function startReconciliationSession(array $data, int $userId): array
    {
        $db = Database::getInstance()->getConnection();

        try {
            $db->beginTransaction();

            $bankAccountId = (int)($data['bank_account_id'] ?? 0);
            $startDate     = trim($data['statement_start_date'] ?? date('Y-m-01'));
            $endDate       = trim($data['statement_end_date'] ?? date('Y-m-t'));
            $openingBal    = round((float)($data['statement_opening_balance'] ?? 0), 2);
            $closingBal    = round((float)($data['statement_closing_balance'] ?? 0), 2);
            $notes         = !empty($data['notes']) ? trim($data['notes']) : null;

            if ($bankAccountId <= 0) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Valid bank account is required.'];
            }

            $bank = self::getBankAccount($bankAccountId);
            if (!$bank) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Bank account not found.'];
            }

            // Check if active draft reconciliation session already exists for this bank
            $existingDraft = Database::fetchOne("
                SELECT id, reconciliation_no FROM bank_reconciliations WHERE bank_account_id = :id AND status = 'draft'
            ", [':id' => $bankAccountId]);

            if ($existingDraft) {
                $db->rollBack();
                return [
                    'success' => false,
                    'message' => "An active draft reconciliation session (#{$existingDraft['reconciliation_no']}) already exists for this bank account. Complete or cancel it first."
                ];
            }

            $recNo = self::generateNumber('BREC', 'bank_reconciliations');
            $coaAccId = (int)$bank['account_id'];

            // Fetch book balance as of statement end date
            $bookBalRow = Database::fetchOne("
                SELECT (SUM(jl.debit) - SUM(jl.credit)) AS net
                FROM journal_lines jl
                JOIN journal_entries je ON jl.journal_entry_id = je.id
                WHERE jl.account_id = :acc_id AND je.posting_status = 'posted' AND je.entry_date <= :edate
            ", [':acc_id' => $coaAccId, ':edate' => $endDate]);

            $bookBalance = round((float)$bank['opening_balance'] + (float)($bookBalRow['net'] ?? 0), 2);

            // Fetch all journal lines up to statement end date, EXCLUDING lines ALREADY cleared in COMPLETED reconciliations
            $lines = Database::fetchAll("
                SELECT jl.id AS journal_line_id
                FROM journal_lines jl
                JOIN journal_entries je ON jl.journal_entry_id = je.id
                WHERE jl.account_id = :acc_id 
                  AND je.posting_status = 'posted' 
                  AND je.entry_date <= :edate
                  AND jl.id NOT IN (
                      SELECT bri.journal_line_id 
                      FROM bank_reconciliation_items bri 
                      JOIN bank_reconciliations br ON bri.reconciliation_id = br.id 
                      WHERE bri.is_cleared = 1 AND br.status = 'completed'
                  )
                ORDER BY je.entry_date ASC, jl.id ASC
            ", [':acc_id' => $coaAccId, ':edate' => $endDate]);

            // Create Header
            $stmtH = $db->prepare("
                INSERT INTO bank_reconciliations (
                    reconciliation_no, bank_account_id, branch_id, statement_start_date, statement_end_date,
                    statement_opening_balance, statement_closing_balance, book_balance, cleared_balance,
                    outstanding_deposits, outstanding_payments, reconciliation_difference, status, notes, created_by
                ) VALUES (
                    :no, :bank_acc, :branch, :sstart, :send,
                    :sopen, :sclose, :book_bal, :scleared,
                    0.00, 0.00, :diff, 'draft', :notes, :user
                )
            ");

            $initialDiff = round($openingBal - $closingBal, 2);

            $stmtH->execute([
                ':no'       => $recNo,
                ':bank_acc' => $bankAccountId,
                ':branch'   => $bank['branch_id'],
                ':sstart'   => $startDate,
                ':send'     => $endDate,
                ':sopen'    => $openingBal,
                ':sclose'   => $closingBal,
                ':book_bal' => $bookBalance,
                ':scleared' => $openingBal,
                ':diff'     => $initialDiff,
                ':notes'    => $notes,
                ':user'     => $userId
            ]);

            $recId = (int)$db->lastInsertId();

            // Populate Item Details
            $stmtItem = $db->prepare("
                INSERT INTO bank_reconciliation_items (reconciliation_id, journal_line_id, is_cleared)
                VALUES (:rec_id, :jl_id, 0)
            ");

            foreach ($lines as $l) {
                $stmtItem->execute([':rec_id' => $recId, ':jl_id' => $l['journal_line_id']]);
            }

            $db->commit();

            Audit::log('BANK_RECONCILIATION_STARTED', 'banking', (string)$recId, null, [
                'reconciliation_no' => $recNo, 'bank_account_id' => $bankAccountId, 'statement_end_date' => $endDate
            ]);

            return [
                'success'           => true,
                'reconciliation_id' => $recId,
                'reconciliation_no' => $recNo,
                'book_balance'      => $bookBalance,
                'message'           => "Bank Reconciliation session {$recNo} started."
            ];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to start bank reconciliation: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Update Cleared Items & Recalculate Reconciliation Session
     */
    public static function updateClearedItems(int $reconciliationId, array $clearedLineIds, int $userId): array
    {
        try {
            $rec = Database::fetchOne("SELECT * FROM bank_reconciliations WHERE id = :id", [':id' => $reconciliationId]);
            if (!$rec) {
                return ['success' => false, 'message' => 'Reconciliation session not found.'];
            }

            if ($rec['status'] === 'completed') {
                return ['success' => false, 'message' => 'Completed reconciliation session is locked and cannot be modified.'];
            }

            $db = Database::getInstance()->getConnection();
            $db->beginTransaction();

            // Reset all items to uncleared first
            $db->prepare("UPDATE bank_reconciliation_items SET is_cleared = 0, cleared_date = NULL WHERE reconciliation_id = :id")
               ->execute([':id' => $reconciliationId]);

            // Set cleared items
            if (!empty($clearedLineIds)) {
                $stmtC = $db->prepare("
                    UPDATE bank_reconciliation_items SET is_cleared = 1, cleared_date = CURRENT_DATE
                    WHERE reconciliation_id = :rec_id AND journal_line_id = :jl_id
                ");
                foreach ($clearedLineIds as $jlId) {
                    $stmtC->execute([':rec_id' => $reconciliationId, ':jl_id' => (int)$jlId]);
                }
            }

            // Calculate Cleared Balance, Outstanding Deposits & Payments
            $items = Database::fetchAll("
                SELECT bri.is_cleared, jl.debit, jl.credit
                FROM bank_reconciliation_items bri
                JOIN journal_lines jl ON bri.journal_line_id = jl.id
                WHERE bri.reconciliation_id = :id
            ", [':id' => $reconciliationId]);

            $clearedBalance = (float)$rec['statement_opening_balance'];
            $outDeposits = 0.00;
            $outPayments = 0.00;

            foreach ($items as $it) {
                $in = (float)$it['debit'];
                $out = (float)$it['credit'];

                if ((int)$it['is_cleared'] === 1) {
                    $clearedBalance += ($in - $out);
                } else {
                    $outDeposits += $in;
                    $outPayments += $out;
                }
            }

            $clearedBalance = round($clearedBalance, 2);
            $outDeposits    = round($outDeposits, 2);
            $outPayments    = round($outPayments, 2);
            $diff           = round($clearedBalance - (float)$rec['statement_closing_balance'], 2);

            $db->prepare("
                UPDATE bank_reconciliations SET
                    cleared_balance = :cleared,
                    outstanding_deposits = :out_dep,
                    outstanding_payments = :out_pay,
                    reconciliation_difference = :diff
                WHERE id = :id
            ")->execute([
                ':cleared' => $clearedBalance,
                ':out_dep' => $outDeposits,
                ':out_pay' => $outPayments,
                ':diff'    => $diff,
                ':id'      => $reconciliationId
            ]);

            $db->commit();

            return [
                'success'                   => true,
                'cleared_balance'           => $clearedBalance,
                'outstanding_deposits'      => $outDeposits,
                'outstanding_payments'      => $outPayments,
                'reconciliation_difference' => $diff,
                'message'                   => 'Cleared items updated.'
            ];

        } catch (Throwable $e) {
            Logger::error("Failed to update cleared items: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Post Direct Bank Adjustment / Bank Charge / Interest (credits 4050 Bank Interest / Other Income)
     */
    public static function postBankAdjustment(int $bankAccountId, array $data, int $userId): array
    {
        $db = Database::getInstance()->getConnection();

        try {
            $db->beginTransaction();

            $bank = self::getBankAccount($bankAccountId);
            if (!$bank) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Bank account not found.'];
            }

            $type        = strtolower(trim($data['type'] ?? 'bank_charge')); // 'bank_charge', 'bank_interest', 'adjustment'
            $amount      = round((float)($data['amount'] ?? 0), 2);
            $otherCoaId  = (int)($data['account_id'] ?? 0);
            $description = trim($data['description'] ?? '');

            if ($amount <= 0.00 || empty($description)) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Positive amount (> 0) and description are required.'];
            }

            $bankCoaId = (int)$bank['account_id'];

            if ($type === 'bank_charge') {
                $otherCoaId = $otherCoaId ?: Accounting::getAccountIdByCode(Accounting::CODE_EXPENSES);
                // Dr Expense (5100), Cr Bank Dedicated COA
                $lines = [
                    ['account_id' => $otherCoaId, 'debit' => $amount, 'credit' => 0.00, 'memo' => "Bank Charge: {$description}"],
                    ['account_id' => $bankCoaId, 'debit' => 0.00, 'credit' => $amount, 'memo' => "Bank Charge Deduction"]
                ];
            } elseif ($type === 'bank_interest') {
                // Must credit 4050 Bank Interest / Other Income (Accounting::CODE_OTHER_INCOME)
                $otherCoaId = $otherCoaId ?: Accounting::getAccountIdByCode(Accounting::CODE_OTHER_INCOME);
                // Dr Bank Dedicated COA, Cr Income (4050)
                $lines = [
                    ['account_id' => $bankCoaId, 'debit' => $amount, 'credit' => 0.00, 'memo' => "Bank Interest Credit"],
                    ['account_id' => $otherCoaId, 'debit' => 0.00, 'credit' => $amount, 'memo' => "Bank Interest Income: {$description}"]
                ];
            } else { // direct adjustment
                if ($otherCoaId <= 0) {
                    $otherCoaId = Accounting::getAccountIdByCode(Accounting::CODE_CASH_SHORTAGE_OVERAGE);
                }
                $dir = strtolower(trim($data['direction'] ?? 'in'));
                if ($dir === 'in') {
                    $lines = [
                        ['account_id' => $bankCoaId, 'debit' => $amount, 'credit' => 0.00, 'memo' => "Bank Adjustment In: {$description}"],
                        ['account_id' => $otherCoaId, 'debit' => 0.00, 'credit' => $amount, 'memo' => "Bank Adjustment Offset"]
                    ];
                } else {
                    $lines = [
                        ['account_id' => $otherCoaId, 'debit' => $amount, 'credit' => 0.00, 'memo' => "Bank Adjustment Out: {$description}"],
                        ['account_id' => $bankCoaId, 'debit' => 0.00, 'credit' => $amount, 'memo' => "Bank Adjustment Deduction"]
                    ];
                }
            }

            $header = [
                'branch_id'   => (int)($bank['branch_id'] ?? 1),
                'entry_date'  => date('Y-m-d'),
                'source_type' => 'manual',
                'description' => "Bank Adjustment ({$bank['bank_name']}): {$description}",
                'created_by'  => $userId
            ];

            $jeRes = Accounting::postJournalEntry($header, $lines);
            if (!$jeRes['success']) {
                $db->rollBack();
                return ['success' => false, 'message' => "Accounting posting failed: " . $jeRes['message']];
            }

            $db->commit();

            Audit::log('BANK_ADJUSTMENT_POSTED', 'banking', (string)$bankAccountId, null, [
                'type' => $type, 'amount' => $amount, 'description' => $description
            ]);

            return ['success' => true, 'message' => "Bank adjustment of LKR " . number_format($amount, 2) . " posted successfully."];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to post bank adjustment: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Complete & Lock Bank Reconciliation Session (Enforces Difference = 0 Constraint)
     */
    public static function completeReconciliation(int $reconciliationId, int $userId): array
    {
        try {
            $rec = Database::fetchOne("SELECT * FROM bank_reconciliations WHERE id = :id", [':id' => $reconciliationId]);
            if (!$rec) {
                return ['success' => false, 'message' => 'Reconciliation session not found.'];
            }

            if ($rec['status'] === 'completed') {
                return ['success' => false, 'message' => 'This reconciliation session is already completed and locked.'];
            }

            $diff = round((float)$rec['reconciliation_difference'], 2);

            // ENFORCE ZERO-DIFFERENCE COMPLETION CONSTRAINT
            if (abs($diff) > 0.001) {
                return [
                    'success' => false,
                    'message' => "Cannot complete reconciliation. Reconciliation difference (LKR " . number_format($diff, 2) . ") must be zero. Clear matching transactions or post an adjustment."
                ];
            }

            Database::execute("
                UPDATE bank_reconciliations SET
                    status = 'completed',
                    reconciled_at = CURRENT_TIMESTAMP,
                    reconciled_by = :userId
                WHERE id = :id
            ", [':userId' => $userId, ':id' => $reconciliationId]);

            Audit::log('BANK_RECONCILIATION_COMPLETED', 'banking', (string)$reconciliationId, null, [
                'reconciliation_no' => $rec['reconciliation_no'],
                'bank_account_id'   => $rec['bank_account_id'],
                'cleared_balance'   => $rec['cleared_balance']
            ]);

            return [
                'success' => true,
                'message' => "Bank Reconciliation {$rec['reconciliation_no']} completed successfully. Session is now locked."
            ];

        } catch (Throwable $e) {
            Logger::error("Failed to complete bank reconciliation #{$reconciliationId}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get Cheque Register linked to bank accounts
     */
    public static function getChequesRegister(int $bankAccountId): array
    {
        try {
            return Database::fetchAll("
                SELECT sc.*, sp.payment_no, s.name AS supplier_name, b.name AS branch_name
                FROM supplier_cheques sc
                JOIN supplier_payments sp ON sc.supplier_payment_id = sp.id
                JOIN suppliers s ON sc.supplier_id = s.id
                JOIN branches b ON sc.branch_id = b.id
                WHERE sp.bank_account_id = :bank_acc OR sc.bank_name LIKE :bname
                ORDER BY sc.cheque_date DESC
            ", [
                ':bank_acc' => $bankAccountId,
                ':bname'    => '%' . (self::getBankAccount($bankAccountId)['bank_name'] ?? '') . '%'
            ]);
        } catch (Throwable $e) {
            Logger::error("Failed to fetch cheques register: " . $e->getMessage(), $e);
            return [];
        }
    }
}
