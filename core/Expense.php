<?php
/**
 * Curtiss POS - Centralized Expense Management Core Engine
 * 
 * Provides end-to-end expense management:
 * - Multi-account payment routing: Cash Drawer (1012), Cash in Hand (1010), Petty Cash (1015), Bank Accounts (1020-1034), Unpaid Accruals (2030)
 * - Strict double-entry general ledger posting & audit trails
 * - Cash drawer movements integration & Business Day reconciliation
 * - Bank statement & reconciliation integration
 * - Sequential document numbering (EXP-00001, EXPPAY-00001)
 * - Draft, approval, posting, partial payment, and reversal workflows
 * - Master soft-delete trash bin and restore
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Accounting.php';
require_once __DIR__ . '/BusinessDay.php';
require_once __DIR__ . '/Banking.php';
require_once __DIR__ . '/Audit.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/MaskingHelper.php';

class Expense
{
    const STATUS_DRAFT          = 'draft';
    const STATUS_POSTED         = 'posted';
    const STATUS_PARTIALLY_PAID = 'partially_paid';
    const STATUS_PAID           = 'paid';
    const STATUS_REVERSED       = 'reversed';

    const METHOD_CASH_DRAWER = 'cash_drawer';
    const METHOD_CASH_IN_HAND= 'cash_in_hand';
    const METHOD_PETTY_CASH  = 'petty_cash';
    const METHOD_BANK        = 'bank';
    const METHOD_UNPAID      = 'unpaid';

    /**
     * Generate sequential document number with atomic row-lock concurrency protection
     */
    public static function generateExpenseNumber(): string
    {
        $db = Database::getInstance()->getConnection();
        $row = $db->query("SELECT MAX(id) AS max_id FROM expenses FOR UPDATE")->fetch(PDO::FETCH_ASSOC);
        $nextId = (int)($row['max_id'] ?? 0) + 1;
        return sprintf("EXP-%05d", $nextId);
    }

    /**
     * Generate sequential payment document number
     */
    public static function generatePaymentNumber(): string
    {
        $db = Database::getInstance()->getConnection();
        $row = $db->query("SELECT MAX(id) AS max_id FROM expense_payments FOR UPDATE")->fetch(PDO::FETCH_ASSOC);
        $nextId = (int)($row['max_id'] ?? 0) + 1;
        return sprintf("EXPPAY-%05d", $nextId);
    }

    /**
     * Normalize payment source input into method string and bank account ID
     */
    public static function normalizePaymentSource(string $rawMethod, ?int $bankId = null): array
    {
        $rawMethod = trim($rawMethod);
        if (stripos($rawMethod, 'Bank:') === 0 || stripos($rawMethod, 'bank_') === 0) {
            $parts = preg_split('/[:_]/', $rawMethod);
            return [self::METHOD_BANK, (int)end($parts)];
        }
        $lower = strtolower($rawMethod);
        if ($lower === 'cash in hand' || $lower === 'cash_in_hand' || $lower === 'safe') {
            return [self::METHOD_CASH_IN_HAND, null];
        }
        if ($lower === 'cash drawer' || $lower === 'cash_drawer' || $lower === 'drawer') {
            return [self::METHOD_CASH_DRAWER, null];
        }
        if ($lower === 'petty cash' || $lower === 'petty_cash') {
            return [self::METHOD_PETTY_CASH, null];
        }
        if ($lower === 'unpaid' || $lower === 'unpaid bill' || $lower === 'accrued') {
            return [self::METHOD_UNPAID, null];
        }
        if ($lower === 'bank') {
            return [self::METHOD_BANK, $bankId];
        }
        return [$rawMethod ?: self::METHOD_CASH_IN_HAND, $bankId];
    }

    /**
     * Fetch list of expenses with server-side pagination and multi-column universal search
     */
    public static function getExpenses(array $filters = [], int $page = 1, int $limit = 25): array
    {
        try {
            $page  = max(1, $page);
            $limit = in_array($limit, [25, 50, 75, 100, 500]) ? $limit : 25;
            $offset = ($page - 1) * $limit;

            $where = [];
            $params = [];

            // Trash bin vs active records
            $view = $filters['view'] ?? 'active';
            if ($view === 'deleted') {
                $where[] = "e.deleted_at IS NOT NULL";
            } else {
                $where[] = "e.deleted_at IS NULL";
            }

            // Branch isolation
            if (!empty($filters['branch_id'])) {
                $where[] = "e.branch_id = :branch_id";
                $params[':branch_id'] = (int)$filters['branch_id'];
            }

            // Category filter
            if (!empty($filters['category_id'])) {
                $where[] = "e.category_id = :category_id";
                $params[':category_id'] = (int)$filters['category_id'];
            }

            // Status filter
            if (!empty($filters['status'])) {
                $where[] = "e.status = :status";
                $params[':status'] = $filters['status'];
            }

            // Payment method filter
            if (!empty($filters['payment_method'])) {
                $where[] = "e.payment_method = :payment_method";
                $params[':payment_method'] = $filters['payment_method'];
            }

            // Date Range
            if (!empty($filters['date_from'])) {
                $where[] = "e.expense_date >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }
            if (!empty($filters['date_to'])) {
                $where[] = "e.expense_date <= :date_to";
                $params[':date_to'] = $filters['date_to'];
            }

            // Universal Search covering text, categories, accounts, and amounts
            if (!empty($filters['search'])) {
                $search = '%' . trim($filters['search']) . '%';
                $where[] = "(
                    e.expense_no LIKE :s1 OR
                    e.payee LIKE :s2 OR
                    e.description LIKE :s3 OR
                    e.vendor_supplier LIKE :s4 OR
                    e.invoice_ref LIKE :s5 OR
                    e.reference_no LIKE :s6 OR
                    ec.name LIKE :s7 OR
                    coa.account_name LIKE :s8 OR
                    coa.account_code LIKE :s9 OR
                    e.payment_method LIKE :s10 OR
                    ba.bank_name LIKE :s11 OR
                    u.full_name LIKE :s12 OR
                    CAST(e.amount AS CHAR) LIKE :s13 OR
                    CONCAT('LKR ', FORMAT(e.amount, 2)) LIKE :s14
                )";
                for ($i = 1; $i <= 14; $i++) {
                    $params[":s{$i}"] = $search;
                }
            }

            $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

            // Total count query
            $countSql = "
                SELECT COUNT(*) AS total
                FROM expenses e
                LEFT JOIN expense_categories ec ON e.category_id = ec.id
                LEFT JOIN chart_of_accounts coa ON e.account_id = coa.id
                LEFT JOIN bank_accounts ba ON e.bank_account_id = ba.id
                LEFT JOIN users u ON e.created_by = u.id
                $whereSql
            ";
            $totalCount = (int)Database::fetchValue($countSql, $params);

            // Fetch paginated data with explicit column selection
            $dataSql = "
                SELECT 
                    e.id,
                    e.expense_no,
                    e.branch_id,
                    e.expense_date,
                    e.category_id,
                    e.account_id,
                    e.payment_method,
                    e.payment_account_id,
                    e.bank_account_id,
                    e.amount,
                    e.paid_amount,
                    e.payee,
                    e.description,
                    e.vendor_supplier,
                    e.invoice_ref,
                    e.reference_no,
                    e.notes,
                    e.status,
                    e.business_day_id,
                    e.journal_entry_id,
                    e.reversal_journal_entry_id,
                    e.reversed_at,
                    e.reversed_by,
                    e.reversal_reason,
                    e.created_by,
                    e.created_at,
                    e.updated_at,
                    e.deleted_at,
                    ec.name AS category_name,
                    ec.code AS category_code,
                    coa.account_code AS expense_account_code,
                    coa.account_name AS expense_account_name,
                    pay_coa.account_code AS payment_account_code,
                    pay_coa.account_name AS payment_account_name,
                    ba.bank_name,
                    ba.account_number AS bank_account_number,
                    b.name AS branch_name,
                    u.full_name AS created_by_name,
                    rev_u.full_name AS reversed_by_name,
                    je.entry_number AS journal_entry_number
                FROM expenses e
                LEFT JOIN expense_categories ec ON e.category_id = ec.id
                LEFT JOIN chart_of_accounts coa ON e.account_id = coa.id
                LEFT JOIN chart_of_accounts pay_coa ON e.payment_account_id = pay_coa.id
                LEFT JOIN bank_accounts ba ON e.bank_account_id = ba.id
                LEFT JOIN branches b ON e.branch_id = b.id
                LEFT JOIN users u ON e.created_by = u.id
                LEFT JOIN users rev_u ON e.reversed_by = rev_u.id
                LEFT JOIN journal_entries je ON e.journal_entry_id = je.id
                $whereSql
                ORDER BY e.expense_date DESC, e.id DESC
                LIMIT :limit OFFSET :offset
            ";

            $stmt = Database::getInstance()->getConnection()->prepare($dataSql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Special user masking
            if (MaskingHelper::isSpecialUser()) {
                foreach ($items as &$it) {
                    $it['amount']      = MaskingHelper::maskValue((float)$it['amount']);
                    $it['paid_amount'] = MaskingHelper::maskValue((float)$it['paid_amount']);
                }
                unset($it);
            }

            return [
                'success'      => true,
                'items'        => $items,
                'total'        => $totalCount,
                'page'         => $page,
                'limit'        => $limit,
                'total_pages'  => ceil($totalCount / $limit) ?: 1
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to fetch expenses: " . $e->getMessage(), $e);
            return [
                'success' => false,
                'message' => "Error fetching expenses: " . $e->getMessage(),
                'items'   => [],
                'total'   => 0
            ];
        }
    }

    /**
     * Get single expense details including payments and attachments
     */
    public static function getExpense(int $id): ?array
    {
        try {
            $sql = "
                SELECT 
                    e.*,
                    ec.name AS category_name,
                    ec.code AS category_code,
                    coa.account_code AS expense_account_code,
                    coa.account_name AS expense_account_name,
                    pay_coa.account_code AS payment_account_code,
                    pay_coa.account_name AS payment_account_name,
                    ba.bank_name,
                    ba.account_number AS bank_account_number,
                    b.name AS branch_name,
                    u.full_name AS created_by_name,
                    rev_u.full_name AS reversed_by_name,
                    je.entry_number AS journal_entry_number,
                    rev_je.entry_number AS reversal_journal_entry_number
                FROM expenses e
                LEFT JOIN expense_categories ec ON e.category_id = ec.id
                LEFT JOIN chart_of_accounts coa ON e.account_id = coa.id
                LEFT JOIN chart_of_accounts pay_coa ON e.payment_account_id = pay_coa.id
                LEFT JOIN bank_accounts ba ON e.bank_account_id = ba.id
                LEFT JOIN branches b ON e.branch_id = b.id
                LEFT JOIN users u ON e.created_by = u.id
                LEFT JOIN users rev_u ON e.reversed_by = rev_u.id
                LEFT JOIN journal_entries je ON e.journal_entry_id = je.id
                LEFT JOIN journal_entries rev_je ON e.reversal_journal_entry_id = rev_je.id
                WHERE e.id = :id
            ";
            $expense = Database::fetchOne($sql, [':id' => $id]);
            if (!$expense) {
                return null;
            }

            // Payments history
            $expense['payments'] = Database::fetchAll("
                SELECT 
                    ep.*,
                    pay_coa.account_name AS payment_account_name,
                    ba.bank_name,
                    u.full_name AS created_by_name,
                    je.entry_number AS journal_entry_number
                FROM expense_payments ep
                LEFT JOIN chart_of_accounts pay_coa ON ep.payment_account_id = pay_coa.id
                LEFT JOIN bank_accounts ba ON ep.bank_account_id = ba.id
                LEFT JOIN users u ON ep.created_by = u.id
                LEFT JOIN journal_entries je ON ep.journal_entry_id = je.id
                WHERE ep.expense_id = :id
                ORDER BY ep.payment_date ASC, ep.id ASC
            ", [':id' => $id]);

            // Attachments
            $expense['attachments'] = Database::fetchAll("
                SELECT ea.*, u.full_name AS uploaded_by_name
                FROM expense_attachments ea
                LEFT JOIN users u ON ea.uploaded_by = u.id
                WHERE ea.expense_id = :id
                ORDER BY ea.id ASC
            ", [':id' => $id]);

            // Masking if applicable
            if (MaskingHelper::isSpecialUser()) {
                $expense['amount']      = MaskingHelper::maskValue((float)$expense['amount']);
                $expense['paid_amount'] = MaskingHelper::maskValue((float)$expense['paid_amount']);
                foreach ($expense['payments'] as &$p) {
                    $p['amount'] = MaskingHelper::maskValue((float)$p['amount']);
                }
                unset($p);
            }

            return $expense;
        } catch (Throwable $e) {
            Logger::error("Failed to fetch expense details #{$id}: " . $e->getMessage(), $e);
            return null;
        }
    }

    /**
     * Create a new expense record (draft or direct post)
     */
    public static function createExpense(array $data, int $userId): array
    {
        $db = Database::getInstance()->getConnection();
        $inTransaction = $db->inTransaction();

        if (!$inTransaction) {
            $db->beginTransaction();
        }

        try {
            $branchId       = (int)($data['branch_id'] ?? 1);
            $expenseDate    = trim($data['expense_date'] ?? date('Y-m-d'));
            $categoryId     = (int)($data['category_id'] ?? 0);
            $amount         = round((float)($data['amount'] ?? 0), 2);
            $payee          = trim($data['payee'] ?? '');
            $description    = trim($data['description'] ?? '');
            
            $rawMethod      = trim($data['payment_method'] ?? $data['payment_account_key'] ?? self::METHOD_CASH_IN_HAND);
            $rawBankId      = !empty($data['bank_account_id']) ? (int)$data['bank_account_id'] : null;
            [$paymentMethod, $bankAccountId] = self::normalizePaymentSource($rawMethod, $rawBankId);

            $vendorSupplier = trim($data['vendor_supplier'] ?? '') ?: null;
            $invoiceRef     = trim($data['invoice_ref'] ?? '') ?: null;
            $referenceNo    = trim($data['reference_no'] ?? '') ?: null;
            $notes          = trim($data['notes'] ?? '') ?: null;
            $targetStatus   = ($data['status'] ?? self::STATUS_POSTED) === self::STATUS_DRAFT ? self::STATUS_DRAFT : self::STATUS_POSTED;

            if ($branchId <= 0) {
                throw new Exception("Valid branch is required.");
            }
            if ($amount <= 0) {
                throw new Exception("Expense amount must be greater than zero.");
            }
            if (empty($expenseDate)) {
                throw new Exception("Expense date is required.");
            }
            if (empty($payee)) {
                throw new Exception("Payee name is required.");
            }
            if (empty($description)) {
                throw new Exception("Expense description is required.");
            }
            if ($categoryId <= 0) {
                throw new Exception("Expense category is required.");
            }

            // Verify category and resolve expense account
            $category = Database::fetchOne("
                SELECT ec.*, coa.account_code, coa.account_name, coa.account_type 
                FROM expense_categories ec
                JOIN chart_of_accounts coa ON ec.account_id = coa.id
                WHERE ec.id = :id AND ec.deleted_at IS NULL
            ", [':id' => $categoryId]);

            if (!$category) {
                throw new Exception("Selected expense category is invalid or deleted.");
            }

            // Custom COA account selection or default from category
            $expenseAccountId = !empty($data['account_id']) ? (int)$data['account_id'] : (int)$category['account_id'];
            $expAccount = Database::fetchOne("SELECT id, account_code, account_name, account_type FROM chart_of_accounts WHERE id = :id", [':id' => $expenseAccountId]);
            if (!$expAccount || $expAccount['account_type'] !== 'expense') {
                throw new Exception("Invalid expense account. Account must be an active Expense account (5xxx).");
            }

            // Resolve payment account and bank account
            $paymentAccountId = null;
            $businessDayId    = null;

            if ($paymentMethod === self::METHOD_CASH_DRAWER) {
                $paymentAccountId = (int)Accounting::getAccountIdByCode(Accounting::CODE_CASH_DRAWER);
                $activeBday = BusinessDay::getActiveBusinessDay($branchId);
                $businessDayId = $activeBday ? (int)$activeBday['id'] : null;
            } elseif ($paymentMethod === self::METHOD_CASH_IN_HAND) {
                $paymentAccountId = (int)Accounting::getAccountIdByCode(Accounting::CODE_CASH_IN_HAND);
            } elseif ($paymentMethod === self::METHOD_PETTY_CASH) {
                $paymentAccountId = (int)Accounting::getAccountIdByCode(Accounting::CODE_PETTY_CASH);
            } elseif ($paymentMethod === self::METHOD_BANK) {
                $bankAccountId = $bankAccountId ?: (int)($data['bank_account_id'] ?? 0);
                if ($bankAccountId <= 0) {
                    throw new Exception("Bank account selection is required for bank payment method.");
                }
                $bankAcc = Database::fetchOne("SELECT id, account_id, bank_name, account_number, status FROM bank_accounts WHERE id = :id AND deleted_at IS NULL", [':id' => $bankAccountId]);
                if (!$bankAcc || $bankAcc['status'] !== 'active') {
                    throw new Exception("Selected bank account is inactive or not found.");
                }
                $paymentAccountId = (int)$bankAcc['account_id'];
            } elseif ($paymentMethod === self::METHOD_UNPAID) {
                // Liability account 2030 (Accrued Expenses & Operating Payables)
                $paymentAccountId = (int)Accounting::getAccountIdByCode('2030');
                if (!$paymentAccountId) {
                    // Fallback create or search
                    $pRow = Database::fetchOne("SELECT id FROM chart_of_accounts WHERE account_code = '2030'");
                    $paymentAccountId = $pRow ? (int)$pRow['id'] : null;
                }
                if (!$paymentAccountId) {
                    throw new Exception("Accrued Expenses Account (2030) not found in Chart of Accounts.");
                }
            } else {
                $resAccId = Accounting::resolvePaymentAccountId($paymentMethod, $bankAccountId);
                if ($resAccId > 0 && $resAccId !== (int)Accounting::getAccountIdByCode(Accounting::CODE_CASH_IN_HAND)) {
                    $paymentAccountId = $resAccId;
                } else {
                    $coaAcc = Database::fetchOne("SELECT id FROM chart_of_accounts WHERE (LOWER(account_name) = LOWER(:n) OR account_code = :c) AND status = 'active'", [':n' => $paymentMethod, ':c' => $paymentMethod]);
                    if ($coaAcc) {
                        $paymentAccountId = (int)$coaAcc['id'];
                    } else {
                        throw new Exception("Unsupported payment method: {$paymentMethod}");
                    }
                }
            }

            // Generate sequential document number
            $expenseNo = self::generateExpenseNumber();

            // Set paid amount
            $paidAmount = ($targetStatus === self::STATUS_POSTED && $paymentMethod !== self::METHOD_UNPAID) ? $amount : 0.00;

            // Insert into expenses
            $stmt = $db->prepare("
                INSERT INTO expenses (
                    expense_no, branch_id, expense_date, category_id, account_id,
                    payment_method, payment_account_id, bank_account_id, amount, paid_amount,
                    payee, description, vendor_supplier, invoice_ref, reference_no, notes,
                    status, business_day_id, created_by, created_at
                ) VALUES (
                    :expense_no, :branch_id, :expense_date, :category_id, :account_id,
                    :payment_method, :payment_account_id, :bank_account_id, :amount, :paid_amount,
                    :payee, :description, :vendor_supplier, :invoice_ref, :reference_no, :notes,
                    :status, :business_day_id, :created_by, CURRENT_TIMESTAMP
                )
            ");

            $stmt->execute([
                ':expense_no'          => $expenseNo,
                ':branch_id'           => $branchId,
                ':expense_date'        => $expenseDate,
                ':category_id'         => $categoryId,
                ':account_id'          => $expenseAccountId,
                ':payment_method'      => $paymentMethod,
                ':payment_account_id'  => $paymentAccountId,
                ':bank_account_id'     => $bankAccountId,
                ':amount'              => $amount,
                ':paid_amount'         => $paidAmount,
                ':payee'               => $payee,
                ':description'         => $description,
                ':vendor_supplier'     => $vendorSupplier,
                ':invoice_ref'         => $invoiceRef,
                ':reference_no'        => $referenceNo,
                ':notes'               => $notes,
                ':status'              => $targetStatus,
                ':business_day_id'     => $businessDayId,
                ':created_by'          => $userId
            ]);

            $expenseId = (int)$db->lastInsertId();

            // If draft, commit and return immediately without financial posting
            if ($targetStatus === self::STATUS_DRAFT) {
                if (!$inTransaction) $db->commit();

                Audit::log('EXPENSE_DRAFT_CREATED', 'expenses', (string)$expenseId, null, [
                    'expense_no' => $expenseNo,
                    'amount'     => $amount,
                    'payee'      => $payee,
                    'category'   => $category['name']
                ]);

                return [
                    'success'    => true,
                    'expense_id' => $expenseId,
                    'expense_no' => $expenseNo,
                    'status'     => self::STATUS_DRAFT,
                    'message'    => "Draft expense {$expenseNo} saved successfully."
                ];
            }

            // POSTED: Execute double-entry accounting posting
            $journalRes = self::postAccountingEntry($expenseId, $userId);
            if (!$journalRes['success']) {
                throw new Exception("Double-entry accounting failed: " . $journalRes['message']);
            }

            if (!$inTransaction) {
                $db->commit();
            }

            Audit::log('EXPENSE_CREATED', 'expenses', (string)$expenseId, null, [
                'expense_no'     => $expenseNo,
                'amount'         => $amount,
                'payee'          => $payee,
                'category'       => $category['name'],
                'payment_method' => $paymentMethod,
                'journal_id'     => $journalRes['journal_id'] ?? null
            ]);

            return [
                'success'    => true,
                'expense_id' => $expenseId,
                'expense_no' => $expenseNo,
                'status'     => self::STATUS_POSTED,
                'journal_id' => $journalRes['journal_id'] ?? null,
                'message'    => "Expense {$expenseNo} recorded and posted successfully."
            ];

        } catch (Throwable $e) {
            if (!$inTransaction && $db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to create expense: " . $e->getMessage(), $e);
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Update an unposted draft expense
     */
    public static function updateDraft(int $id, array $data, int $userId): array
    {
        $db = Database::getInstance()->getConnection();
        try {
            $expense = Database::fetchOne("SELECT * FROM expenses WHERE id = :id AND deleted_at IS NULL", [':id' => $id]);
            if (!$expense) {
                return ['success' => false, 'message' => 'Expense not found.'];
            }
            if ($expense['status'] !== self::STATUS_DRAFT) {
                return ['success' => false, 'message' => 'Only draft expenses can be edited. Posted transactions must be reversed.'];
            }

            $branchId       = (int)($data['branch_id'] ?? $expense['branch_id']);
            $expenseDate    = trim($data['expense_date'] ?? $expense['expense_date']);
            $categoryId     = (int)($data['category_id'] ?? $expense['category_id']);
            $amount         = round((float)($data['amount'] ?? $expense['amount']), 2);
            $payee          = trim($data['payee'] ?? $expense['payee']);
            $description    = trim($data['description'] ?? $expense['description']);

            $rawMethod      = trim($data['payment_method'] ?? $data['payment_account_key'] ?? $expense['payment_method']);
            $rawBankId      = !empty($data['bank_account_id']) ? (int)$data['bank_account_id'] : (int)($expense['bank_account_id'] ?? 0);
            [$paymentMethod, $bankAccountId] = self::normalizePaymentSource($rawMethod, $rawBankId);

            $vendorSupplier = trim($data['vendor_supplier'] ?? '') ?: null;
            $invoiceRef     = trim($data['invoice_ref'] ?? '') ?: null;
            $referenceNo    = trim($data['reference_no'] ?? '') ?: null;
            $notes          = trim($data['notes'] ?? '') ?: null;

            if ($amount <= 0 || empty($payee) || empty($description)) {
                return ['success' => false, 'message' => 'Amount, payee, and description are required.'];
            }

            // Resolve category and expense account
            $category = Database::fetchOne("SELECT * FROM expense_categories WHERE id = :id AND deleted_at IS NULL", [':id' => $categoryId]);
            if (!$category) {
                return ['success' => false, 'message' => 'Selected category is invalid.'];
            }
            $expenseAccountId = !empty($data['account_id']) ? (int)$data['account_id'] : (int)$category['account_id'];

            // Payment account
            $paymentAccountId = null;
            if ($paymentMethod === self::METHOD_CASH_DRAWER) {
                $paymentAccountId = (int)Accounting::getAccountIdByCode(Accounting::CODE_CASH_DRAWER);
            } elseif ($paymentMethod === self::METHOD_CASH_IN_HAND) {
                $paymentAccountId = (int)Accounting::getAccountIdByCode(Accounting::CODE_CASH_IN_HAND);
            } elseif ($paymentMethod === self::METHOD_PETTY_CASH) {
                $paymentAccountId = (int)Accounting::getAccountIdByCode(Accounting::CODE_PETTY_CASH);
            } elseif ($paymentMethod === self::METHOD_BANK) {
                $bankAccountId = $bankAccountId ?: (int)($data['bank_account_id'] ?? 0);
                $bankAcc = Database::fetchOne("SELECT account_id FROM bank_accounts WHERE id = :id AND status = 'active'", [':id' => $bankAccountId]);
                if (!$bankAcc) return ['success' => false, 'message' => 'Valid active bank account is required.'];
                $paymentAccountId = (int)$bankAcc['account_id'];
            } elseif ($paymentMethod === self::METHOD_UNPAID) {
                $paymentAccountId = (int)Accounting::getAccountIdByCode('2030');
            } else {
                $paymentAccountId = Accounting::resolvePaymentAccountId($paymentMethod, $bankAccountId);
            }

            $stmt = $db->prepare("
                UPDATE expenses SET
                    branch_id = :branch_id,
                    expense_date = :expense_date,
                    category_id = :category_id,
                    account_id = :account_id,
                    payment_method = :payment_method,
                    payment_account_id = :payment_account_id,
                    bank_account_id = :bank_account_id,
                    amount = :amount,
                    payee = :payee,
                    description = :description,
                    vendor_supplier = :vendor_supplier,
                    invoice_ref = :invoice_ref,
                    reference_no = :reference_no,
                    notes = :notes,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");

            $stmt->execute([
                ':branch_id'          => $branchId,
                ':expense_date'       => $expenseDate,
                ':category_id'        => $categoryId,
                ':account_id'         => $expenseAccountId,
                ':payment_method'     => $paymentMethod,
                ':payment_account_id' => $paymentAccountId,
                ':bank_account_id'    => $bankAccountId,
                ':amount'             => $amount,
                ':payee'              => $payee,
                ':description'        => $description,
                ':vendor_supplier'    => $vendorSupplier,
                ':invoice_ref'        => $invoiceRef,
                ':reference_no'       => $referenceNo,
                ':notes'              => $notes,
                ':id'                 => $id
            ]);

            Audit::log('EXPENSE_DRAFT_UPDATED', 'expenses', (string)$id, $expense, [
                'expense_no' => $expense['expense_no'],
                'amount'     => $amount,
                'payee'      => $payee
            ]);

            return ['success' => true, 'message' => "Draft expense {$expense['expense_no']} updated successfully."];

        } catch (Throwable $e) {
            Logger::error("Failed to update draft expense #{$id}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Post a draft expense to the general ledger
     */
    public static function postExpense(int $id, int $userId): array
    {
        $db = Database::getInstance()->getConnection();
        $inTransaction = $db->inTransaction();

        if (!$inTransaction) {
            $db->beginTransaction();
        }

        try {
            $expense = Database::fetchOne("SELECT * FROM expenses WHERE id = :id AND deleted_at IS NULL FOR UPDATE", [':id' => $id]);
            if (!$expense) {
                throw new Exception("Expense record not found.");
            }
            if ($expense['status'] !== self::STATUS_DRAFT) {
                throw new Exception("Only draft expenses can be posted. Current status: {$expense['status']}");
            }

            // Post accounting journal
            $journalRes = self::postAccountingEntry($id, $userId);
            if (!$journalRes['success']) {
                throw new Exception("Accounting posting failed: " . $journalRes['message']);
            }

            $paidAmount = ($expense['payment_method'] !== self::METHOD_UNPAID) ? (float)$expense['amount'] : 0.00;

            // If cash drawer, update business_day_id to active shift if not set
            $bdayId = $expense['business_day_id'];
            if ($expense['payment_method'] === self::METHOD_CASH_DRAWER && !$bdayId) {
                $activeBday = BusinessDay::getActiveBusinessDay((int)$expense['branch_id']);
                $bdayId = $activeBday ? (int)$activeBday['id'] : null;
            }

            $db->prepare("
                UPDATE expenses SET
                    status = :status,
                    paid_amount = :paid_amount,
                    business_day_id = :bday_id,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ")->execute([
                ':status'      => self::STATUS_POSTED,
                ':paid_amount' => $paidAmount,
                ':bday_id'     => $bdayId,
                ':id'          => $id
            ]);

            if (!$inTransaction) {
                $db->commit();
            }

            Audit::log('EXPENSE_POSTED', 'expenses', (string)$id, $expense, [
                'status'     => self::STATUS_POSTED,
                'journal_id' => $journalRes['journal_id'] ?? null
            ]);

            return [
                'success'    => true,
                'expense_id' => $id,
                'journal_id' => $journalRes['journal_id'] ?? null,
                'message'    => "Expense {$expense['expense_no']} posted to General Ledger successfully."
            ];

        } catch (Throwable $e) {
            if (!$inTransaction && $db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to post expense #{$id}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Post double-entry general ledger journal entry for an expense
     */
    private static function postAccountingEntry(int $expenseId, int $userId): array
    {
        $db = Database::getInstance()->getConnection();
        $expense = Database::fetchOne("
            SELECT e.*, ec.name AS category_name
            FROM expenses e
            JOIN expense_categories ec ON e.category_id = ec.id
            WHERE e.id = :id
        ", [':id' => $expenseId]);

        if (!$expense) {
            return ['success' => false, 'message' => 'Expense not found.'];
        }

        $amount            = (float)$expense['amount'];
        $expAccountId      = (int)$expense['account_id'];
        $paymentAccountId  = (int)$expense['payment_account_id'];
        $branchId          = (int)$expense['branch_id'];
        $expenseDate       = $expense['expense_date'];
        $expenseNo         = $expense['expense_no'];
        $payee             = $expense['payee'];
        $categoryName      = $expense['category_name'];

        // Balanced double-entry journal:
        // Debit: Expense Account (5xxx)
        // Credit: Payment Source Account (1012 Cash Drawer, 1010 Cash in Hand, 1015 Petty Cash, Bank COA, or 2030 Accrued Payables)
        $lines = [
            [
                'account_id' => $expAccountId,
                'debit'      => $amount,
                'credit'     => 0.00,
                'memo'       => "Expense: {$categoryName} ({$payee})"
            ],
            [
                'account_id' => $paymentAccountId,
                'debit'      => 0.00,
                'credit'     => $amount,
                'memo'       => "Expense Voucher #{$expenseNo}"
            ]
        ];

        $header = [
            'branch_id'   => $branchId,
            'entry_date'  => $expenseDate,
            'source_type' => 'expense',
            'source_id'   => $expenseId,
            'description' => "Expense Voucher #{$expenseNo} - {$payee} ({$categoryName})",
            'created_by'  => $userId
        ];

        $jeRes = Accounting::postJournalEntry($header, $lines);
        if (!$jeRes['success']) {
            return ['success' => false, 'message' => $jeRes['message']];
        }

        $jeId = (int)$jeRes['journal_id'];

        // Link journal entry to expense record
        $db->prepare("UPDATE expenses SET journal_entry_id = :je_id WHERE id = :id")
           ->execute([':je_id' => $jeId, ':id' => $expenseId]);

        // Cash Drawer Integration: Record drawer movement if paid from Cash Drawer
        if ($expense['payment_method'] === self::METHOD_CASH_DRAWER) {
            $bdayId = (int)($expense['business_day_id'] ?? 0);
            if ($bdayId <= 0) {
                $activeBday = BusinessDay::getActiveBusinessDay($branchId);
                $bdayId = $activeBday ? (int)$activeBday['id'] : 0;
            }

            if ($bdayId > 0) {
                BusinessDay::recordDrawerMovement([
                    'business_day_id' => $bdayId,
                    'branch_id'       => $branchId,
                    'movement_type'   => 'expense_payout',
                    'source_type'     => 'expense',
                    'source_id'       => $expenseId,
                    'amount'          => $amount,
                    'direction'       => 'out',
                    'reason'          => "Expense #{$expenseNo}: {$categoryName} - {$payee}",
                    'notes'           => $expense['description'],
                    'user_id'         => $userId
                ]);
            }
        }

        return ['success' => true, 'journal_id' => $jeId];
    }

    /**
     * Void / Reverse a posted expense transaction
     */
    public static function reverseExpense(int $id, string $reason, int $userId): array
    {
        $db = Database::getInstance()->getConnection();
        $inTransaction = $db->inTransaction();

        if (!$inTransaction) {
            $db->beginTransaction();
        }

        try {
            $reason = trim($reason);
            if (empty($reason)) {
                return ['success' => false, 'message' => 'A valid reversal reason is required.'];
            }

            $expense = Database::fetchOne("
                SELECT e.*, ec.name AS category_name
                FROM expenses e
                JOIN expense_categories ec ON e.category_id = ec.id
                WHERE e.id = :id FOR UPDATE
            ", [':id' => $id]);

            if (!$expense) {
                return ['success' => false, 'message' => 'Expense not found.'];
            }
            if ($expense['status'] === self::STATUS_REVERSED) {
                return ['success' => false, 'message' => 'This expense transaction has already been reversed.'];
            }
            if ($expense['status'] === self::STATUS_DRAFT) {
                return ['success' => false, 'message' => 'Draft expenses cannot be reversed; they can be deleted directly.'];
            }

            $amount           = (float)$expense['amount'];
            $expAccountId     = (int)$expense['account_id'];
            $paymentAccountId = (int)$expense['payment_account_id'];
            $branchId         = (int)$expense['branch_id'];
            $expenseNo        = $expense['expense_no'];

            // 1. Create balanced reversal journal entry:
            // Debit: Payment Account (restore cash/bank/liability)
            // Credit: Expense Account (reduce expense)
            $lines = [
                [
                    'account_id' => $paymentAccountId,
                    'debit'      => $amount,
                    'credit'     => 0.00,
                    'memo'       => "Reversal of Expense #{$expenseNo}: {$reason}"
                ],
                [
                    'account_id' => $expAccountId,
                    'debit'      => 0.00,
                    'credit'     => $amount,
                    'memo'       => "Reversal of Expense #{$expenseNo}: {$reason}"
                ]
            ];

            $header = [
                'branch_id'   => $branchId,
                'entry_date'  => date('Y-m-d'),
                'source_type' => 'reversal',
                'source_id'   => $id,
                'description' => "REVERSAL: Expense Voucher #{$expenseNo} - {$reason}",
                'created_by'  => $userId
            ];

            $jeRes = Accounting::postJournalEntry($header, $lines);
            if (!$jeRes['success']) {
                throw new Exception("Reversal journal posting failed: " . $jeRes['message']);
            }

            $revJeId = (int)$jeRes['journal_id'];

            // 2. If original paid from cash drawer, record offset cash in movement
            if ($expense['payment_method'] === self::METHOD_CASH_DRAWER) {
                $activeBday = BusinessDay::getActiveBusinessDay($branchId);
                $bdayId = $activeBday ? (int)$activeBday['id'] : (int)($expense['business_day_id'] ?? 0);
                if ($bdayId > 0) {
                    BusinessDay::recordDrawerMovement([
                        'business_day_id' => $bdayId,
                        'branch_id'       => $branchId,
                        'movement_type'   => 'expense_reversal',
                        'source_type'     => 'expense_reversal',
                        'source_id'       => $id,
                        'amount'          => $amount,
                        'direction'       => 'in',
                        'reason'          => "Reversal: Expense #{$expenseNo} - {$reason}",
                        'notes'           => "Original expense #{$expenseNo} reversed by User #{$userId}",
                        'user_id'         => $userId
                    ]);
                }
            }

            // 3. Reverse any payments associated with this expense
            $payments = Database::fetchAll("SELECT * FROM expense_payments WHERE expense_id = :id AND status = 'posted'", [':id' => $id]);
            foreach ($payments as $p) {
                self::reversePayment((int)$p['id'], "Parent expense #{$expenseNo} reversed: {$reason}", $userId);
            }

            // 4. Update expense record
            $db->prepare("
                UPDATE expenses SET
                    status = :status,
                    reversal_journal_entry_id = :rev_je_id,
                    reversed_at = CURRENT_TIMESTAMP,
                    reversed_by = :reversed_by,
                    reversal_reason = :reversal_reason,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ")->execute([
                ':status'          => self::STATUS_REVERSED,
                ':rev_je_id'       => $revJeId,
                ':reversed_by'     => $userId,
                ':reversal_reason' => $reason,
                ':id'              => $id
            ]);

            if (!$inTransaction) {
                $db->commit();
            }

            Audit::log('EXPENSE_REVERSED', 'expenses', (string)$id, $expense, [
                'reversal_reason'     => $reason,
                'reversal_journal_id' => $revJeId
            ]);

            return [
                'success'             => true,
                'reversal_journal_id' => $revJeId,
                'message'             => "Expense #{$expenseNo} has been reversed successfully."
            ];

        } catch (Throwable $e) {
            if (!$inTransaction && $db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to reverse expense #{$id}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Record a payment against an unpaid or partially paid expense bill
     */
    public static function recordPayment(int $expenseId, array $data, int $userId): array
    {
        $db = Database::getInstance()->getConnection();
        $inTransaction = $db->inTransaction();

        if (!$inTransaction) {
            $db->beginTransaction();
        }

        try {
            $expense = Database::fetchOne("SELECT * FROM expenses WHERE id = :id AND deleted_at IS NULL FOR UPDATE", [':id' => $expenseId]);
            if (!$expense) {
                return ['success' => false, 'message' => 'Expense not found.'];
            }
            if ($expense['status'] === self::STATUS_REVERSED || $expense['status'] === self::STATUS_DRAFT) {
                return ['success' => false, 'message' => "Cannot pay expense with status '{$expense['status']}'."];
            }

            $totalAmount = (float)$expense['amount'];
            $paidAmount  = (float)$expense['paid_amount'];
            $remaining   = round($totalAmount - $paidAmount, 2);

            if ($remaining <= 0) {
                return ['success' => false, 'message' => 'This expense bill is already fully paid.'];
            }

            $amount = round((float)($data['amount'] ?? 0), 2);
            if ($amount <= 0 || $amount > $remaining) {
                return ['success' => false, 'message' => "Payment amount must be between 0.01 and remaining balance of LKR " . number_format($remaining, 2)];
            }

            $rawMethod     = trim($data['payment_method'] ?? $data['payment_account_key'] ?? self::METHOD_CASH_IN_HAND);
            $rawBankId     = !empty($data['bank_account_id']) ? (int)$data['bank_account_id'] : null;
            [$paymentMethod, $bankAccountId] = self::normalizePaymentSource($rawMethod, $rawBankId);

            $paymentDate   = $data['payment_date'] ?? date('Y-m-d');
            $referenceNo   = trim($data['reference_no'] ?? '') ?: null;
            $notes         = trim($data['notes'] ?? '') ?: null;
            $branchId      = (int)$expense['branch_id'];

            // Resolve payment account
            $paymentAccountId = null;
            $businessDayId    = null;

            if ($paymentMethod === self::METHOD_CASH_DRAWER) {
                $paymentAccountId = (int)Accounting::getAccountIdByCode(Accounting::CODE_CASH_DRAWER);
                $activeBday = BusinessDay::getActiveBusinessDay($branchId);
                $businessDayId = $activeBday ? (int)$activeBday['id'] : null;
            } elseif ($paymentMethod === self::METHOD_CASH_IN_HAND) {
                $paymentAccountId = (int)Accounting::getAccountIdByCode(Accounting::CODE_CASH_IN_HAND);
            } elseif ($paymentMethod === self::METHOD_PETTY_CASH) {
                $paymentAccountId = (int)Accounting::getAccountIdByCode(Accounting::CODE_PETTY_CASH);
            } elseif ($paymentMethod === self::METHOD_BANK) {
                $bankAccountId = $bankAccountId ?: (int)($data['bank_account_id'] ?? 0);
                $bankAcc = Database::fetchOne("SELECT account_id FROM bank_accounts WHERE id = :id AND status = 'active'", [':id' => $bankAccountId]);
                if (!$bankAcc) return ['success' => false, 'message' => 'Active bank account required.'];
            } else {
                $resAccId = Accounting::resolvePaymentAccountId($paymentMethod, $bankAccountId);
                if ($resAccId > 0 && $resAccId !== (int)Accounting::getAccountIdByCode(Accounting::CODE_CASH_IN_HAND)) {
                    $paymentAccountId = $resAccId;
                } else {
                    $coaAcc = Database::fetchOne("SELECT id FROM chart_of_accounts WHERE (LOWER(account_name) = LOWER(:n) OR account_code = :c) AND status = 'active'", [':n' => $paymentMethod, ':c' => $paymentMethod]);
                    if ($coaAcc) {
                        $paymentAccountId = (int)$coaAcc['id'];
                    } else {
                        return ['success' => false, 'message' => "Unsupported payment method: {$paymentMethod}"];
                    }
                }
            }

            $paymentNo = self::generatePaymentNumber();

            // Double-entry accounting for payment of accrued expense bill:
            // Debit: Accrued Expenses & Operating Payables (2030)
            // Credit: Payment Source (1012 Cash Drawer, 1010 Cash in Hand, 1015 Petty Cash, or Bank COA)
            $accruedAccountId = (int)Accounting::getAccountIdByCode('2030');
            $lines = [
                [
                    'account_id' => $accruedAccountId,
                    'debit'      => $amount,
                    'credit'     => 0.00,
                    'memo'       => "Payment for Expense #{$expense['expense_no']}"
                ],
                [
                    'account_id' => $paymentAccountId,
                    'debit'      => 0.00,
                    'credit'     => $amount,
                    'memo'       => "Expense Payment Voucher #{$paymentNo}"
                ]
            ];

            $header = [
                'branch_id'   => $branchId,
                'entry_date'  => $paymentDate,
                'source_type' => 'expense_payment',
                'source_id'   => $expenseId,
                'description' => "Expense Bill Payment #{$paymentNo} for {$expense['expense_no']} ({$expense['payee']})",
                'created_by'  => $userId
            ];

            $jeRes = Accounting::postJournalEntry($header, $lines);
            if (!$jeRes['success']) {
                throw new Exception("Payment journal posting failed: " . $jeRes['message']);
            }

            $jeId = (int)$jeRes['journal_id'];

            // Insert into expense_payments
            $stmt = $db->prepare("
                INSERT INTO expense_payments (
                    payment_no, expense_id, branch_id, payment_date, payment_method,
                    payment_account_id, bank_account_id, amount, reference_no, notes,
                    business_day_id, journal_entry_id, status, created_by, created_at
                ) VALUES (
                    :payment_no, :expense_id, :branch_id, :payment_date, :payment_method,
                    :payment_account_id, :bank_account_id, :amount, :reference_no, :notes,
                    :business_day_id, :journal_entry_id, 'posted', :created_by, CURRENT_TIMESTAMP
                )
            ");

            $stmt->execute([
                ':payment_no'          => $paymentNo,
                ':expense_id'          => $expenseId,
                ':branch_id'           => $branchId,
                ':payment_date'        => $paymentDate,
                ':payment_method'      => $paymentMethod,
                ':payment_account_id'  => $paymentAccountId,
                ':bank_account_id'     => $bankAccountId,
                ':amount'              => $amount,
                ':reference_no'        => $referenceNo,
                ':notes'               => $notes,
                ':business_day_id'     => $businessDayId,
                ':journal_entry_id'    => $jeId,
                ':created_by'          => $userId
            ]);

            $paymentId = (int)$db->lastInsertId();

            // Cash Drawer Movement if from Cash Drawer
            if ($paymentMethod === self::METHOD_CASH_DRAWER && $businessDayId > 0) {
                BusinessDay::recordDrawerMovement([
                    'business_day_id' => $businessDayId,
                    'branch_id'       => $branchId,
                    'movement_type'   => 'expense_payout',
                    'source_type'     => 'expense_payment',
                    'source_id'       => $paymentId,
                    'amount'          => $amount,
                    'direction'       => 'out',
                    'reason'          => "Bill Payment #{$paymentNo} for Expense #{$expense['expense_no']}",
                    'notes'           => $notes,
                    'user_id'         => $userId
                ]);
            }

            // Update parent expense paid_amount & status
            $newPaidAmount = round($paidAmount + $amount, 2);
            $newStatus = ($newPaidAmount >= $totalAmount) ? self::STATUS_PAID : self::STATUS_PARTIALLY_PAID;

            $db->prepare("
                UPDATE expenses SET
                    paid_amount = :paid_amount,
                    status = :status,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ")->execute([
                ':paid_amount' => $newPaidAmount,
                ':status'      => $newStatus,
                ':id'          => $expenseId
            ]);

            if (!$inTransaction) {
                $db->commit();
            }

            Audit::log('EXPENSE_PAYMENT_RECORDED', 'expenses', (string)$paymentId, null, [
                'expense_id' => $expenseId,
                'payment_no' => $paymentNo,
                'amount'     => $amount,
                'method'     => $paymentMethod
            ]);

            return [
                'success'    => true,
                'payment_id' => $paymentId,
                'payment_no' => $paymentNo,
                'journal_id' => $jeId,
                'message'    => "Payment of LKR " . number_format($amount, 2) . " recorded successfully."
            ];

        } catch (Throwable $e) {
            if (!$inTransaction && $db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to record expense payment: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Reverse a specific expense payment
     */
    public static function reversePayment(int $paymentId, string $reason, int $userId): array
    {
        $db = Database::getInstance()->getConnection();
        $inTransaction = $db->inTransaction();

        if (!$inTransaction) {
            $db->beginTransaction();
        }

        try {
            $payment = Database::fetchOne("SELECT * FROM expense_payments WHERE id = :id FOR UPDATE", [':id' => $paymentId]);
            if (!$payment || $payment['status'] === 'reversed') {
                return ['success' => false, 'message' => 'Payment not found or already reversed.'];
            }

            $amount           = (float)$payment['amount'];
            $paymentAccountId = (int)$payment['payment_account_id'];
            $expenseId        = (int)$payment['expense_id'];
            $branchId         = (int)$payment['branch_id'];
            $accruedAccountId = (int)Accounting::getAccountIdByCode('2030');

            // Reversal double-entry:
            // Debit: Payment Account
            // Credit: Accrued Expenses Account
            $lines = [
                [
                    'account_id' => $paymentAccountId,
                    'debit'      => $amount,
                    'credit'     => 0.00,
                    'memo'       => "Reversal of Expense Payment #{$payment['payment_no']}"
                ],
                [
                    'account_id' => $accruedAccountId,
                    'debit'      => 0.00,
                    'credit'     => $amount,
                    'memo'       => "Reversal of Expense Payment #{$payment['payment_no']}"
                ]
            ];

            $header = [
                'branch_id'   => $branchId,
                'entry_date'  => date('Y-m-d'),
                'source_type' => 'reversal',
                'source_id'   => $paymentId,
                'description' => "REVERSAL: Expense Payment #{$payment['payment_no']} - {$reason}",
                'created_by'  => $userId
            ];

            $jeRes = Accounting::postJournalEntry($header, $lines);
            if (!$jeRes['success']) {
                throw new Exception("Reversal journal failed: " . $jeRes['message']);
            }

            $revJeId = (int)$jeRes['journal_id'];

            // Cash Drawer offset if drawer payment
            if ($payment['payment_method'] === self::METHOD_CASH_DRAWER) {
                $activeBday = BusinessDay::getActiveBusinessDay($branchId);
                $bdayId = $activeBday ? (int)$activeBday['id'] : (int)($payment['business_day_id'] ?? 0);
                if ($bdayId > 0) {
                    BusinessDay::recordDrawerMovement([
                        'business_day_id' => $bdayId,
                        'branch_id'       => $branchId,
                        'movement_type'   => 'expense_reversal',
                        'source_type'     => 'expense_payment_reversal',
                        'source_id'       => $paymentId,
                        'amount'          => $amount,
                        'direction'       => 'in',
                        'reason'          => "Reversal: Payment #{$payment['payment_no']}",
                        'notes'           => $reason,
                        'user_id'         => $userId
                    ]);
                }
            }

            // Update payment record
            $db->prepare("
                UPDATE expense_payments SET
                    status = 'reversed',
                    reversal_journal_entry_id = :rev_je,
                    reversed_at = CURRENT_TIMESTAMP,
                    reversed_by = :rev_by,
                    reversal_reason = :reason
                WHERE id = :id
            ")->execute([
                ':rev_je'  => $revJeId,
                ':rev_by'  => $userId,
                ':reason'  => $reason,
                ':id'      => $paymentId
            ]);

            // Recompute parent expense paid amount and status
            $exp = Database::fetchOne("SELECT amount, paid_amount FROM expenses WHERE id = :id FOR UPDATE", [':id' => $expenseId]);
            if ($exp) {
                $newPaid = max(0.00, round((float)$exp['paid_amount'] - $amount, 2));
                $newStat = ($newPaid <= 0.00) ? self::STATUS_POSTED : self::STATUS_PARTIALLY_PAID;
                $db->prepare("UPDATE expenses SET paid_amount = :paid, status = :status WHERE id = :id")
                   ->execute([':paid' => $newPaid, ':status' => $newStat, ':id' => $expenseId]);
            }

            if (!$inTransaction) {
                $db->commit();
            }

            Audit::log('EXPENSE_PAYMENT_REVERSED', 'expenses', (string)$paymentId, $payment, [
                'reversal_reason' => $reason
            ]);

            return ['success' => true, 'message' => "Payment #{$payment['payment_no']} reversed successfully."];

        } catch (Throwable $e) {
            if (!$inTransaction && $db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to reverse expense payment #{$paymentId}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Soft-delete an unposted draft expense
     */
    public static function softDeleteDraft(int $id, int $userId): array
    {
        try {
            $expense = Database::fetchOne("SELECT * FROM expenses WHERE id = :id AND deleted_at IS NULL", [':id' => $id]);
            if (!$expense) {
                return ['success' => false, 'message' => 'Expense not found.'];
            }
            if ($expense['status'] !== self::STATUS_DRAFT) {
                return ['success' => false, 'message' => 'Only draft expenses can be deleted. Posted transactions must be reversed.'];
            }

            Database::execute("UPDATE expenses SET deleted_at = CURRENT_TIMESTAMP WHERE id = :id", [':id' => $id]);

            Audit::log('EXPENSE_DRAFT_DELETED', 'expenses', (string)$id, $expense, [
                'expense_no' => $expense['expense_no']
            ]);

            return ['success' => true, 'message' => "Draft expense {$expense['expense_no']} moved to trash bin."];
        } catch (Throwable $e) {
            Logger::error("Failed to delete draft expense #{$id}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Restore a soft-deleted draft expense from Trash Bin
     */
    public static function restoreDraft(int $id, int $userId): array
    {
        try {
            $expense = Database::fetchOne("SELECT * FROM expenses WHERE id = :id AND deleted_at IS NOT NULL", [':id' => $id]);
            if (!$expense) {
                return ['success' => false, 'message' => 'Trashed draft expense not found.'];
            }

            Database::execute("UPDATE expenses SET deleted_at = NULL WHERE id = :id", [':id' => $id]);

            Audit::log('EXPENSE_DRAFT_RESTORED', 'expenses', (string)$id, null, [
                'expense_no' => $expense['expense_no']
            ]);

            return ['success' => true, 'message' => "Draft expense {$expense['expense_no']} restored successfully."];
        } catch (Throwable $e) {
            Logger::error("Failed to restore draft expense #{$id}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Upload and attach supporting receipt/document to an expense
     */
    public static function addAttachment(int $expenseId, array $file, int $userId): array
    {
        try {
            $expense = Database::fetchOne("SELECT id, expense_no FROM expenses WHERE id = :id AND deleted_at IS NULL", [':id' => $expenseId]);
            if (!$expense) {
                return ['success' => false, 'message' => 'Expense not found.'];
            }

            $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'webp'];
            $maxBytes = 5 * 1024 * 1024; // 5MB

            $fileName = basename($file['name']);
            $fileSize = (int)$file['size'];
            $fileTmp  = $file['tmp_name'];
            $ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if (!in_array($ext, $allowedExtensions)) {
                return ['success' => false, 'message' => 'Invalid file format. Allowed formats: JPG, PNG, PDF, WEBP.'];
            }
            if ($fileSize > $maxBytes) {
                return ['success' => false, 'message' => 'File exceeds maximum upload size of 5MB.'];
            }

            $uploadDir = APP_ROOT . '/storage/uploads/expenses';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $uniqueName = 'exp_' . $expenseId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $destPath = $uploadDir . '/' . $uniqueName;

            if (!move_uploaded_file($fileTmp, $destPath)) {
                return ['success' => false, 'message' => 'Failed to save uploaded file.'];
            }

            $relPath = 'storage/uploads/expenses/' . $uniqueName;

            Database::execute("
                INSERT INTO expense_attachments (expense_id, file_name, file_path, file_type, file_size, uploaded_by, created_at)
                VALUES (:exp_id, :fname, :fpath, :ftype, :fsize, :uid, CURRENT_TIMESTAMP)
            ", [
                ':exp_id' => $expenseId,
                ':fname'  => $fileName,
                ':fpath'  => $relPath,
                ':ftype'  => $file['type'] ?? 'application/octet-stream',
                ':fsize'  => $fileSize,
                ':uid'    => $userId
            ]);

            Audit::log('EXPENSE_ATTACHMENT_UPLOADED', 'expenses', (string)$expenseId, null, [
                'file_name' => $fileName,
                'file_size' => $fileSize
            ]);

            return ['success' => true, 'message' => "Attachment '{$fileName}' uploaded successfully."];

        } catch (Throwable $e) {
            Logger::error("Failed to upload expense attachment: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get KPI summary metrics for Expense Dashboard
     */
    public static function getDashboardSummary(int $branchId = 0, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        try {
            $where = ["e.deleted_at IS NULL", "e.status != 'reversed'"];
            $params = [];

            if ($branchId > 0) {
                $where[] = "e.branch_id = :branch_id";
                $params[':branch_id'] = $branchId;
            }
            if (!empty($dateFrom)) {
                $where[] = "e.expense_date >= :date_from";
                $params[':date_from'] = $dateFrom;
            }
            if (!empty($dateTo)) {
                $where[] = "e.expense_date <= :date_to";
                $params[':date_to'] = $dateTo;
            }

            $whereSql = implode(" AND ", $where);

            $row = Database::fetchOne("
                SELECT 
                    COUNT(*) AS total_count,
                    COALESCE(SUM(e.amount), 0.00) AS total_amount,
                    COALESCE(SUM(CASE WHEN e.payment_method = 'cash_drawer' THEN e.amount ELSE 0.00 END), 0.00) AS cash_drawer_amount,
                    COALESCE(SUM(CASE WHEN e.payment_method = 'cash_in_hand' THEN e.amount ELSE 0.00 END), 0.00) AS cash_in_hand_amount,
                    COALESCE(SUM(CASE WHEN e.payment_method = 'petty_cash' THEN e.amount ELSE 0.00 END), 0.00) AS petty_cash_amount,
                    COALESCE(SUM(CASE WHEN e.payment_method = 'bank' THEN e.amount ELSE 0.00 END), 0.00) AS bank_amount,
                    COALESCE(SUM(CASE WHEN e.payment_method = 'unpaid' THEN (e.amount - e.paid_amount) ELSE 0.00 END), 0.00) AS unpaid_amount,
                    COALESCE(SUM(CASE WHEN e.status = 'draft' THEN 1 ELSE 0 END), 0) AS draft_count
                FROM expenses e
                WHERE $whereSql
            ", $params);

            $summary = [
                'total_count'         => (int)($row['total_count'] ?? 0),
                'total_amount'        => round((float)($row['total_amount'] ?? 0), 2),
                'cash_drawer_amount'  => round((float)($row['cash_drawer_amount'] ?? 0), 2),
                'cash_in_hand_amount' => round((float)($row['cash_in_hand_amount'] ?? 0), 2),
                'petty_cash_amount'   => round((float)($row['petty_cash_amount'] ?? 0), 2),
                'bank_amount'         => round((float)($row['bank_amount'] ?? 0), 2),
                'unpaid_amount'       => round((float)($row['unpaid_amount'] ?? 0), 2),
                'draft_count'         => (int)($row['draft_count'] ?? 0)
            ];

            if (MaskingHelper::isSpecialUser()) {
                foreach (['total_amount', 'cash_drawer_amount', 'cash_in_hand_amount', 'petty_cash_amount', 'bank_amount', 'unpaid_amount'] as $k) {
                    $summary[$k] = MaskingHelper::maskValue($summary[$k]);
                }
            }

            return $summary;
        } catch (Throwable $e) {
            Logger::error("Failed to fetch expense dashboard summary: " . $e->getMessage(), $e);
            return [
                'total_count' => 0, 'total_amount' => 0.00, 'cash_drawer_amount' => 0.00,
                'cash_in_hand_amount' => 0.00, 'petty_cash_amount' => 0.00, 'bank_amount' => 0.00,
                'unpaid_amount' => 0.00, 'draft_count' => 0
            ];
        }
    }

    /**
     * Get active expense categories with mapped COA details
     */
    public static function getCategories(bool $activeOnly = true): array
    {
        try {
            $where = $activeOnly ? "WHERE ec.deleted_at IS NULL AND ec.status = 'active'" : "WHERE ec.deleted_at IS NULL";
            return Database::fetchAll("
                SELECT ec.id, ec.code, ec.name, ec.account_id, ec.description, ec.status,
                       coa.account_code, coa.account_name
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
     * Get eligible payment accounts for a branch (never includes expense accounts)
     */
    public static function getEligiblePaymentAccounts(int $branchId = 1): array
    {
        try {
            $accounts = [
                'cash_drawer' => [
                    'code' => Accounting::CODE_CASH_DRAWER,
                    'name' => 'Cash Drawer (POS Register)',
                    'id'   => (int)Accounting::getAccountIdByCode(Accounting::CODE_CASH_DRAWER)
                ],
                'cash_in_hand' => [
                    'code' => Accounting::CODE_CASH_IN_HAND,
                    'name' => 'Cash in Hand (Main Safe)',
                    'id'   => (int)Accounting::getAccountIdByCode(Accounting::CODE_CASH_IN_HAND)
                ],
                'petty_cash' => [
                    'code' => Accounting::CODE_PETTY_CASH,
                    'name' => 'Petty Cash Account',
                    'id'   => (int)Accounting::getAccountIdByCode(Accounting::CODE_PETTY_CASH)
                ],
                'unpaid' => [
                    'code' => '2030',
                    'name' => 'Accrued Expenses & Operating Payables (Unpaid Bill)',
                    'id'   => (int)Accounting::getAccountIdByCode('2030')
                ]
            ];

            // Add active operational bank accounts
            $bankAccounts = Database::fetchAll("
                SELECT ba.id, ba.bank_name, ba.account_name, ba.account_number, ba.account_id
                FROM bank_accounts ba
                WHERE ba.status = 'active' AND ba.deleted_at IS NULL
                  AND (ba.branch_id = :bid OR ba.branch_id IS NULL)
                ORDER BY ba.bank_name ASC
            ", [':bid' => $branchId]);

            $accounts['bank_accounts'] = $bankAccounts;
            $accounts['cash_accounts'] = Accounting::getCashAccounts();

            return $accounts;
        } catch (Throwable $e) {
            Logger::error("Failed to fetch eligible payment accounts: " . $e->getMessage(), $e);
            return [];
        }
    }
}
