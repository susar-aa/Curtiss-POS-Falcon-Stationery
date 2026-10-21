<?php
/**
 * Curtiss POS - Customer Credit Collections Core Component (Stage 9 & Section 7 Enhancements)
 * Handles customer outstanding lookup, payment collections (Cash, Card, Bank, Cheque, Gift Voucher),
 * customer cheque returns workflow, auto/invoice-specific allocation, and credit balance ledger updating 
 * within strict atomic DB transactions and double-entry GL integration.
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Audit.php';
require_once __DIR__ . '/Customer.php';

class CustomerPayment
{
    /**
     * Generate continuous customer payment collection reference number (CPAY-0001, CPAY-0002...)
     */
    public static function generatePaymentNumber(): string
    {
        try {
            $db = Database::getInstance()->getConnection();
            $nextNum = 1;
            
            $stmt1 = $db->query("SELECT reference_no FROM customer_credit_ledger WHERE reference_no LIKE 'CPAY-%'");
            while ($row1 = $stmt1->fetch(PDO::FETCH_ASSOC)) {
                if (preg_match('/CPAY-(\d+)/', $row1['reference_no'], $m1)) {
                    $nextNum = max($nextNum, ((int)$m1[1]) + 1);
                }
            }

            $stmt2 = $db->query("SELECT payment_no FROM payments WHERE payment_no LIKE 'CPAY-%'");
            while ($row2 = $stmt2->fetch(PDO::FETCH_ASSOC)) {
                if (preg_match('/CPAY-(\d+)/', $row2['payment_no'], $m2)) {
                    $nextNum = max($nextNum, ((int)$m2[1]) + 1);
                }
            }

            $paymentNo = 'CPAY-' . sprintf('%04d', $nextNum);
            while (Database::fetchOne("SELECT id FROM payments WHERE payment_no = :p", [':p' => $paymentNo])) {
                $nextNum++;
                $paymentNo = 'CPAY-' . sprintf('%04d', $nextNum);
            }

            return $paymentNo;
        } catch (Throwable $e) {
            Logger::error("Failed to generate customer payment number: " . $e->getMessage(), $e);
            return 'CPAY-' . sprintf('%04d', rand(10000, 99999));
        }
    }

    /**
     * Search Customers by Code, Name, Phone, WhatsApp, Card Barcode
     */
    public static function searchCustomers(string $query): array
    {
        try {
            $q = '%' . trim($query) . '%';
            $rawQ = trim($query);
            return Database::fetchAll("
                SELECT id, customer_code, name, phone, whatsapp, card_barcode, credit_limit, credit_balance, status
                FROM customers
                WHERE (name LIKE :q1 OR customer_code LIKE :q2 OR phone LIKE :q3 OR whatsapp LIKE :q4 OR card_barcode = :rawq)
                  AND id > 1
                  AND deleted_at IS NULL
                ORDER BY name ASC
                LIMIT 20
            ", [
                ':q1'   => $q,
                ':q2'   => $q,
                ':q3'   => $q,
                ':q4'   => $q,
                ':rawq' => $rawQ
            ]);
        } catch (Throwable $e) {
            Logger::error("Failed to search customers: " . $e->getMessage(), $e);
            return [];
        }
    }

    /**
     * Get Customer Outstanding Summary & Unpaid Credit Invoices List
     */
    public static function getCustomerOutstandingSummary(int $customerId): array
    {
        try {
            $customer = Customer::find($customerId);
            if (!$customer) {
                return ['success' => false, 'message' => 'Customer not found.'];
            }

            $limit = (float)$customer['credit_limit'];
            $balance = (float)$customer['credit_balance'];
            $available = max(0.00, round($limit - $balance, 2));

            // Fetch unpaid / partially paid credit invoices for this customer
            $sales = Database::fetchAll("
                SELECT 
                    s.id AS sale_id,
                    s.invoice_no,
                    s.branch_id,
                    s.total_amount,
                    s.created_at,
                    b.name AS branch_name,
                    COALESCE((
                        SELECT SUM(p.amount)
                        FROM payments p
                        WHERE p.sale_id = s.id AND p.payment_method != 'Credit'
                    ), 0.00) AS total_non_credit_paid
                FROM sales s
                JOIN branches b ON s.branch_id = b.id
                WHERE s.customer_id = :customer_id AND s.status = 'completed'
                ORDER BY s.id ASC
            ", [':customer_id' => $customerId]);

            $unpaidInvoices = [];
            foreach ($sales as $sale) {
                $totalAmount = (float)$sale['total_amount'];
                $paidNonCredit = (float)$sale['total_non_credit_paid'];
                $dueAmount = round($totalAmount - $paidNonCredit, 2);

                if ($dueAmount > 0.001) {
                    $sale['due_amount'] = $dueAmount;
                    $sale['paid_amount'] = $paidNonCredit;
                    $unpaidInvoices[] = $sale;
                }
            }

            // Fetch payment collection history from credit ledger
            $ledger = Database::fetchAll("
                SELECT ccl.*, b.name AS branch_name, u.full_name AS user_name
                FROM customer_credit_ledger ccl
                LEFT JOIN branches b ON ccl.branch_id = b.id
                LEFT JOIN users u ON ccl.user_id = u.id
                WHERE ccl.customer_id = :customer_id
                ORDER BY ccl.id DESC
                LIMIT 50
            ", [':customer_id' => $customerId]);

            $cheques = self::getCheques(['customer_id' => $customerId]);

            return [
                'success'           => true,
                'customer'          => $customer,
                'credit_limit'      => $limit,
                'credit_balance'    => $balance,
                'available_credit'  => $available,
                'unpaid_invoices'   => $unpaidInvoices,
                'collection_ledger' => $ledger,
                'cheques'           => $cheques
            ];

        } catch (Throwable $e) {
            Logger::error("Failed to fetch customer outstanding summary for #{$customerId}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Error loading customer summary: ' . $e->getMessage()];
        }
    }

    /**
     * Process Customer Credit Collection Payment inside an atomic database transaction
     */
    public static function processPayment(array $data, int $userId, int $branchId): array
    {
        $db = Database::getInstance()->getConnection();

        try {
            $db->beginTransaction();

            $customerId = (int)($data['customer_id'] ?? 0);
            if ($customerId <= 1) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Credit collection payment is not allowed for Walk-in Customer account.'];
            }

            // Fetch & lock customer
            $custRow = Database::fetchOne("
                SELECT * FROM customers WHERE id = :id AND deleted_at IS NULL FOR UPDATE
            ", [':id' => $customerId]);

            if (!$custRow) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Customer account not found.'];
            }

            $currentDebt = (float)$custRow['credit_balance'];
            if ($currentDebt <= 0.00) {
                $db->rollBack();
                return ['success' => false, 'message' => "Customer '{$custRow['name']}' has no outstanding balance."];
            }

            // Parse splits
            $rawSplits = $data['splits'] ?? null;
            if (is_string($rawSplits)) {
                $rawSplits = json_decode($rawSplits, true);
            }

            $splits = [];
            if (!empty($rawSplits) && is_array($rawSplits)) {
                $splits = $rawSplits;
            } else {
                $singleMethod = trim($data['payment_method'] ?? 'Cash in Hand');
                $singleAmount = round((float)($data['amount'] ?? 0), 2);
                $singleBankId = !empty($data['bank_account_id']) ? (int)$data['bank_account_id'] : null;
                $splits = [[
                    'payment_method'  => $singleMethod,
                    'bank_account_id' => $singleBankId,
                    'amount'          => $singleAmount,
                    'reference_no'    => $data['reference_no'] ?? null,
                    'cheque_no'       => $data['cheque_no'] ?? null,
                    'bank_name'       => $data['bank_name'] ?? null,
                    'cheque_date'     => $data['cheque_date'] ?? null,
                    'received_date'   => $data['received_date'] ?? date('Y-m-d')
                ]];
            }

            // Calculate total collection amount
            $totalAmount = 0.00;
            foreach ($splits as $sp) {
                $spAmt = round((float)($sp['amount'] ?? 0), 2);
                if ($spAmt <= 0.00) {
                    $db->rollBack();
                    return ['success' => false, 'message' => 'Each payment split amount must be greater than zero.'];
                }
                $totalAmount = round($totalAmount + $spAmt, 2);
            }

            if ($totalAmount <= 0.00) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Payment collection amount must be greater than zero.'];
            }

            // Overpayment prevention
            if ($totalAmount > ($currentDebt + 0.01)) {
                $db->rollBack();
                return [
                    'success' => false,
                    'message' => "Overpayment prevented: Total payment (LKR " . number_format($totalAmount, 2) . ") exceeds customer outstanding balance (LKR " . number_format($currentDebt, 2) . ")."
                ];
            }

            $notes = !empty($data['notes']) ? trim($data['notes']) : 'Customer credit collection payment';

            // Pre-validate all splits before executing mutations
            $validatedSplits = [];
            foreach ($splits as $sp) {
                $rawMethod = trim($sp['payment_method'] ?? $sp['method'] ?? 'Cash in Hand');
                $bankAccountId = !empty($sp['bank_account_id']) ? (int)$sp['bank_account_id'] : null;

                if (strcasecmp($rawMethod, 'Credit') === 0) {
                    $db->rollBack();
                    return ['success' => false, 'message' => "Credit cannot be used as a collection payment method."];
                }

                if (strpos($rawMethod, 'Bank:') === 0) {
                    $parts = explode(':', $rawMethod);
                    $paymentMethod = 'Bank';
                    $bankAccountId = (int)($parts[1] ?? 0);
                } elseif (strcasecmp($rawMethod, 'Cash Drawer') === 0 || strcasecmp($rawMethod, 'cash_drawer') === 0) {
                    $paymentMethod = 'Cash Drawer';
                } elseif (strcasecmp($rawMethod, 'Cash in Hand') === 0 || strcasecmp($rawMethod, 'cash_in_hand') === 0 || strcasecmp($rawMethod, 'Cash') === 0) {
                    $paymentMethod = 'Cash in Hand';
                } elseif (strcasecmp($rawMethod, 'Petty Cash') === 0 || strcasecmp($rawMethod, 'petty_cash') === 0) {
                    $paymentMethod = 'Petty Cash';
                } elseif (strcasecmp($rawMethod, 'Card') === 0 || strcasecmp($rawMethod, 'card') === 0) {
                    $paymentMethod = 'Card';
                } elseif (strcasecmp($rawMethod, 'Cheque') === 0) {
                    $paymentMethod = 'Cheque';
                } elseif (strcasecmp($rawMethod, 'Bank') === 0 || strcasecmp($rawMethod, 'Bank Transfer') === 0) {
                    $paymentMethod = 'Bank';
                } else {
                    // Check if it matches an active COA account (e.g. 'Cash in Hand (Buwaneka)', '1011', 'COA:115')
                    $coaAcc = null;
                    if (stripos($rawMethod, 'COA:') === 0 || stripos($rawMethod, 'coa_') === 0) {
                        $val = trim(substr($rawMethod, 4));
                        $coaAcc = Database::fetchOne("SELECT id, account_code, account_name FROM chart_of_accounts WHERE (id = :id OR account_code = :code) AND status = 'active'", [':id' => (int)$val, ':code' => $val]);
                    } else {
                        $coaAcc = Database::fetchOne("
                            SELECT id, account_code, account_name 
                            FROM chart_of_accounts 
                            WHERE (LOWER(account_name) = LOWER(:m1) OR account_code = :m2) AND status = 'active'
                            LIMIT 1
                        ", [':m1' => $rawMethod, ':m2' => $rawMethod]);
                    }

                    if ($coaAcc) {
                        $paymentMethod = $coaAcc['account_name'];
                    } else {
                        $paymentMethod = 'Cash in Hand';
                    }
                }

                $validMethods = ['Cash Drawer', 'Cash in Hand', 'Petty Cash', 'Card', 'Bank', 'Cheque'];
                $isCustomCoa = false;
                if (!in_array($paymentMethod, $validMethods, true)) {
                    $coaCheck = Database::fetchOne("
                        SELECT id FROM chart_of_accounts 
                        WHERE LOWER(account_name) = LOWER(:name) AND status = 'active'
                    ", [':name' => $paymentMethod]);
                    if ($coaCheck) {
                        $isCustomCoa = true;
                    }
                }

                if (!in_array($paymentMethod, $validMethods, true) && !$isCustomCoa) {
                    $db->rollBack();
                    return ['success' => false, 'message' => "Unsupported payment method '{$paymentMethod}' in payment split."];
                }

                if ($paymentMethod === 'Bank') {
                    if ($bankAccountId <= 0) {
                        $db->rollBack();
                        return ['success' => false, 'message' => 'Bank account selection is required for bank transfer split.'];
                    }
                    $bankRow = Database::fetchOne("SELECT * FROM bank_accounts WHERE id = :id AND status = 'active'", [':id' => $bankAccountId]);
                    if (!$bankRow) {
                        $db->rollBack();
                        return ['success' => false, 'message' => 'Selected bank account is invalid or inactive.'];
                    }
                }

                $chequeNo = null;
                $bankName = null;
                $chequeDate = null;
                $receivedDate = null;
                if ($paymentMethod === 'Cheque') {
                    $chequeNo = trim($sp['cheque_no'] ?? $sp['reference_no'] ?? '');
                    $bankName = trim($sp['bank_name'] ?? '');
                    $chequeDate = trim($sp['cheque_date'] ?? '');
                    $receivedDate = !empty($sp['received_date']) ? trim($sp['received_date']) : date('Y-m-d');

                    if (empty($chequeNo)) {
                        $db->rollBack();
                        return ['success' => false, 'message' => 'Cheque number is required for cheque split.'];
                    }
                    if (empty($bankName)) {
                        $db->rollBack();
                        return ['success' => false, 'message' => 'Bank name is required for cheque collection.'];
                    }
                    if (empty($chequeDate)) {
                        $chequeDate = date('Y-m-d');
                    }
                }

                if (in_array($paymentMethod, ['Cash Drawer', 'Cash in Hand'], true) || $isCustomCoa) {
                    require_once __DIR__ . '/BusinessDay.php';
                    $activeDay = BusinessDay::getOrCreateActiveDay($branchId, $userId);
                    if (!$activeDay) {
                        $db->rollBack();
                        return ['success' => false, 'message' => 'Cannot process cash collection. No active business day is currently open for this branch.'];
                    }
                }

                $validatedSplits[] = [
                    'payment_method'  => $paymentMethod,
                    'bank_account_id' => $bankAccountId,
                    'amount'          => round((float)($sp['amount'] ?? 0), 2),
                    'reference_no'    => !empty($sp['reference_no']) ? trim($sp['reference_no']) : null,
                    'cheque_no'       => $chequeNo,
                    'bank_name'       => $bankName,
                    'cheque_date'     => $chequeDate,
                    'received_date'   => $receivedDate
                ];
            }

            // 1. Update customer credit balance
            $newDebt = max(0.00, round($currentDebt - $totalAmount, 2));
            $db->prepare("
                UPDATE customers SET credit_balance = :new_bal, updated_at = NOW() WHERE id = :id
            ")->execute([':new_bal' => $newDebt, ':id' => $customerId]);

            $basePaymentNo = self::generatePaymentNumber();

            // 2. Prepare Ledger, Cheque, and Drawer statements
            $stmtCcl = $db->prepare("
                INSERT INTO customer_credit_ledger (
                    customer_id, branch_id, user_id, payment_method, bank_account_id, amount_paid, amount_due, balance_after, transaction_type, reference_no, notes
                ) VALUES (
                    :customer_id, :branch_id, :user_id, :payment_method, :bank_account_id, :amount_paid, :amount_due, :balance_after, 'payment', :reference_no, :notes
                )
            ");

            $stmtChq = $db->prepare("
                INSERT INTO customer_cheques (
                    cheque_no, customer_id, branch_id, bank_name, cheque_date, received_date, amount, payment_no, customer_credit_ledger_id, bank_account_id, status, created_by
                ) VALUES (
                    :cheque_no, :customer_id, :branch_id, :bank_name, :cheque_date, :received_date, :amount, :payment_no, :customer_credit_ledger_id, :bank_account_id, 'in_hand', :created_by
                )
            ");

            $createdCclIds = [];
            $createdChequeId = null;
            $accumulatedPaid = 0.00;

            foreach ($validatedSplits as $idx => $vSp) {
                $spAmt = $vSp['amount'];
                $spMethod = $vSp['payment_method'];
                $spBankId = $vSp['bank_account_id'];
                $accumulatedPaid = round($accumulatedPaid + $spAmt, 2);
                $runningBal = max(0.00, round($currentDebt - $accumulatedPaid, 2));

                $linePmtNo = (count($validatedSplits) === 1) ? $basePaymentNo : ($basePaymentNo . '-' . ($idx + 1));
                $spRef = $vSp['reference_no'] ?: $linePmtNo;

                $splitNote = "Collection via {$spMethod}";
                if (!empty($notes)) {
                    $splitNote .= " - {$notes}";
                }

                $stmtCcl->execute([
                    ':customer_id'     => $customerId,
                    ':branch_id'       => $branchId,
                    ':user_id'         => $userId,
                    ':payment_method'  => $spMethod,
                    ':bank_account_id' => $spBankId,
                    ':amount_paid'     => $spAmt,
                    ':amount_due'      => -$spAmt,
                    ':balance_after'   => $runningBal,
                    ':reference_no'    => $linePmtNo,
                    ':notes'           => $splitNote
                ]);
                $cclId = (int)$db->lastInsertId();
                $createdCclIds[] = $cclId;

                if ($spMethod === 'Cheque') {
                    $stmtChq->execute([
                        ':cheque_no'                 => $vSp['cheque_no'],
                        ':customer_id'               => $customerId,
                        ':branch_id'                 => $branchId,
                        ':bank_name'                 => $vSp['bank_name'],
                        ':cheque_date'               => $vSp['cheque_date'],
                        ':received_date'             => $vSp['received_date'],
                        ':amount'                    => $spAmt,
                        ':payment_no'                => $linePmtNo,
                        ':customer_credit_ledger_id' => $cclId,
                        ':bank_account_id'           => $spBankId,
                        ':created_by'                => $userId
                    ]);
                    $createdChequeId = (int)$db->lastInsertId();
                }

                if (in_array($spMethod, ['Cash Drawer', 'Cash in Hand'], true)) {
                    require_once __DIR__ . '/BusinessDay.php';
                    $activeDay = BusinessDay::getOrCreateActiveDay($branchId, $userId);
                    if ($activeDay) {
                        BusinessDay::recordDrawerMovement([
                            'business_day_id' => (int)$activeDay['id'],
                            'branch_id'       => $branchId,
                            'movement_type'   => 'customer_collection',
                            'source_type'     => 'customer_credit_ledger',
                            'source_id'       => $cclId,
                            'amount'          => $spAmt,
                            'direction'       => 'in',
                            'reason'          => "Customer Collection ({$linePmtNo} - {$spMethod})",
                            'user_id'         => $userId
                        ]);
                    }
                }
            }

            // 3. Invoice Allocation across unpaid credit sales
            $unpaidInvoices = Database::fetchAll("
                SELECT 
                    s.id AS sale_id,
                    s.total_amount,
                    COALESCE((
                        SELECT SUM(p.amount) FROM payments p WHERE p.sale_id = s.id AND p.payment_method != 'Credit'
                    ), 0.00) AS paid_non_credit
                FROM sales s
                WHERE s.customer_id = :customer_id AND s.status = 'completed'
                ORDER BY s.id ASC
            ", [':customer_id' => $customerId]);

            $stmtPay = $db->prepare("
                INSERT INTO payments (payment_no, sale_id, branch_id, bank_account_id, customer_id, payment_method, amount, reference_no, user_id)
                VALUES (:payment_no, :sale_id, :branch_id, :bank_account_id, :customer_id, :payment_method, :amount, :reference_no, :user_id)
            ");

            $invIndex = 0;
            $currentInvPaid = 0.00;

            foreach ($validatedSplits as $sIdx => $vSp) {
                $remSplitAmt = $vSp['amount'];
                $spMethod = $vSp['payment_method'];
                $linePmtNo = (count($validatedSplits) === 1) ? $basePaymentNo : ($basePaymentNo . '-' . ($sIdx + 1));
                $spRef = $vSp['reference_no'] ?: $linePmtNo;

                while ($remSplitAmt > 0.001 && $invIndex < count($unpaidInvoices)) {
                    $inv = $unpaidInvoices[$invIndex];
                    $invTotal = (float)$inv['total_amount'];
                    $invAlreadyPaid = (float)$inv['paid_non_credit'] + $currentInvPaid;
                    $invDue = round($invTotal - $invAlreadyPaid, 2);

                    if ($invDue <= 0.001) {
                        $invIndex++;
                        $currentInvPaid = 0.00;
                        continue;
                    }

                    $alloc = min($remSplitAmt, $invDue);
                    $stmtPay->execute([
                        ':payment_no'       => $linePmtNo,
                        ':sale_id'          => $inv['sale_id'],
                        ':branch_id'        => $branchId,
                        ':bank_account_id'  => $vSp['bank_account_id'],
                        ':customer_id'      => $customerId,
                        ':payment_method'   => $spMethod,
                        ':amount'           => $alloc,
                        ':reference_no'     => $spRef,
                        ':user_id'          => $userId
                    ]);

                    $currentInvPaid = round($currentInvPaid + $alloc, 2);
                    $remSplitAmt = round($remSplitAmt - $alloc, 2);

                    if ($currentInvPaid >= ($invTotal - (float)$inv['paid_non_credit'] - 0.001)) {
                        $invIndex++;
                        $currentInvPaid = 0.00;
                    }
                }

                if ($remSplitAmt > 0.001 && !empty($unpaidInvoices)) {
                    $stmtPay->execute([
                        ':payment_no'     => $linePmtNo . '-REM',
                        ':sale_id'        => $unpaidInvoices[0]['sale_id'],
                        ':branch_id'      => $branchId,
                        ':customer_id'    => $customerId,
                        ':payment_method' => $spMethod,
                        ':amount'         => $remSplitAmt,
                        ':reference_no'   => $spRef,
                        ':user_id'        => $userId
                    ]);
                }
            }

            $db->commit();

            // 4. Post each split to Chart of Accounts (COA)
            require_once __DIR__ . '/Accounting.php';
            foreach ($createdCclIds as $cclId) {
                Accounting::postCustomerCollection($cclId);
            }

            Audit::log('CUSTOMER_PAYMENT_PROCESSED', 'customers', (string)$customerId, null, [
                'payment_no'        => $basePaymentNo,
                'customer_name'     => $custRow['name'],
                'previous_balance'  => $currentDebt,
                'new_balance'       => $newDebt,
                'amount_paid'       => $totalAmount,
                'splits_count'      => count($validatedSplits)
            ]);

            return [
                'success'          => true,
                'message'          => "Collection payment {$basePaymentNo} of LKR " . number_format($totalAmount, 2) . " processed successfully across " . count($validatedSplits) . " payment split(s).",
                'payment_no'       => $basePaymentNo,
                'cheque_id'        => $createdChequeId ?? null,
                'previous_balance' => $currentDebt,
                'new_balance'      => $newDebt,
                'amount_paid'      => $totalAmount,
                'splits_count'     => count($validatedSplits)
            ];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to process customer payment: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => "Customer Payment Error: " . $e->getMessage()];
        }
    }

    /**
     * Process Customer Cheque Return / Bounce Workflow
     */
    public static function returnCustomerCheque(array $data, int $userId, int $branchId): array
    {
        $db = Database::getInstance()->getConnection();

        try {
            $db->beginTransaction();

            $chequeId = (int)($data['cheque_id'] ?? 0);
            $reason = trim($data['reason'] ?? 'Customer cheque returned / bounced');

            if ($chequeId <= 0) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Valid Cheque ID is required.'];
            }

            // Lock & fetch cheque
            $chq = Database::fetchOne("
                SELECT * FROM customer_cheques WHERE id = :id FOR UPDATE
            ", [':id' => $chequeId]);

            if (!$chq) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Cheque record not found.'];
            }

            if ($chq['status'] === 'returned') {
                $db->rollBack();
                return ['success' => false, 'message' => 'Cheque has already been returned. Duplicate return prevented.'];
            }

            if ($chq['status'] === 'cancelled') {
                $db->rollBack();
                return ['success' => false, 'message' => 'Cannot return a cancelled cheque.'];
            }

            $customerId = (int)$chq['customer_id'];
            $amount = round((float)$chq['amount'], 2);

            // Lock customer record
            $custRow = Database::fetchOne("
                SELECT * FROM customers WHERE id = :id FOR UPDATE
            ", [':id' => $customerId]);

            if (!$custRow) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Customer record not found.'];
            }

            $currentDebt = (float)$custRow['credit_balance'];
            $newDebt = round($currentDebt + $amount, 2);

            // Restore customer credit balance
            $db->prepare("
                UPDATE customers SET credit_balance = :new_bal, updated_at = NOW() WHERE id = :id
            ")->execute([':new_bal' => $newDebt, ':id' => $customerId]);

            $previousStatus = $chq['status'];

            // Update cheque status
            $db->prepare("
                UPDATE customer_cheques 
                SET status = 'returned', return_reason = :reason, returned_at = NOW(), returned_by = :uid 
                WHERE id = :id
            ")->execute([
                ':reason' => $reason,
                ':uid'    => $userId,
                ':id'     => $chequeId
            ]);

            // Create cheque_return ledger entry
            $retRef = "CHQ-RET-" . $chq['cheque_no'];
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
                ':bank_account_id' => $chq['bank_account_id'],
                ':amount_due'      => $amount,
                ':balance_after'   => $newDebt,
                ':reference_no'    => $retRef,
                ':notes'           => "Cheque #{$chq['cheque_no']} returned: {$reason}"
            ]);
            $cclId = (int)$db->lastInsertId();

            // Reverse invoice allocations in payments table
            if (!empty($chq['payment_no'])) {
                $pmts = Database::fetchAll("
                    SELECT * FROM payments WHERE payment_no = :pno AND amount > 0
                ", [':pno' => $chq['payment_no']]);

                $stmtRevPay = $db->prepare("
                    INSERT INTO payments (payment_no, sale_id, branch_id, customer_id, payment_method, amount, reference_no, user_id)
                    VALUES (:payment_no, :sale_id, :branch_id, :customer_id, 'Cheque Return', :amount, :reference_no, :user_id)
                ");

                foreach ($pmts as $p) {
                    $stmtRevPay->execute([
                        ':payment_no'     => $retRef,
                        ':sale_id'        => $p['sale_id'],
                        ':branch_id'      => $branchId,
                        ':customer_id'    => $customerId,
                        ':amount'         => -abs((float)$p['amount']),
                        ':reference_no'   => "Reversal for {$chq['cheque_no']}",
                        ':user_id'        => $userId
                    ]);
                }
            }

            $db->commit();

            // Accounting trigger
            require_once __DIR__ . '/Accounting.php';
            Accounting::postCustomerChequeReturn($chequeId, $cclId, $previousStatus);

            Audit::log('CUSTOMER_CHEQUE_RETURNED', 'customer_cheques', (string)$chequeId, null, [
                'cheque_no'        => $chq['cheque_no'],
                'customer_name'    => $custRow['name'],
                'amount'           => $amount,
                'previous_balance' => $currentDebt,
                'new_balance'      => $newDebt,
                'reason'           => $reason
            ]);

            return [
                'success'          => true,
                'message'          => "Cheque #{$chq['cheque_no']} returned successfully. Outstanding balance restored to LKR " . number_format($newDebt, 2) . ".",
                'cheque_id'        => $chequeId,
                'cheque_no'        => $chq['cheque_no'],
                'previous_balance' => $currentDebt,
                'new_balance'      => $newDebt,
                'restored_amount'  => $amount
            ];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to process customer cheque return for Cheque #{$data['cheque_id']}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => "Customer Cheque Return Error: " . $e->getMessage()];
        }
    }

    /**
     * Get List of Customer Cheques with Filters
     */
    public static function getCheques(array $filters = []): array
    {
        try {
            $sql = "
                SELECT cq.*, c.name AS customer_name, c.customer_code, b.name AS branch_name, u.full_name AS created_by_name
                FROM customer_cheques cq
                JOIN customers c ON cq.customer_id = c.id
                LEFT JOIN branches b ON cq.branch_id = b.id
                LEFT JOIN users u ON cq.created_by = u.id
                WHERE 1=1
            ";
            $params = [];

            if (!empty($filters['customer_id'])) {
                $sql .= " AND cq.customer_id = :customer_id";
                $params[':customer_id'] = (int)$filters['customer_id'];
            }

            if (!empty($filters['status'])) {
                $sql .= " AND cq.status = :status";
                $params[':status'] = trim($filters['status']);
            }

            if (!empty($filters['branch_id'])) {
                $sql .= " AND cq.branch_id = :branch_id";
                $params[':branch_id'] = (int)$filters['branch_id'];
            }

            $sql .= " ORDER BY cq.id DESC";

            return Database::fetchAll($sql, $params);
        } catch (Throwable $e) {
            Logger::error("Failed to fetch customer cheques: " . $e->getMessage(), $e);
            return [];
        }
    }

    /**
     * Get Details of a Single Customer Cheque
     */
    public static function getChequeDetails(int $chequeId): ?array
    {
        try {
            return Database::fetchOne("
                SELECT cq.*, c.name AS customer_name, c.customer_code, c.phone AS customer_phone, b.name AS branch_name, u.full_name AS created_by_name
                FROM customer_cheques cq
                JOIN customers c ON cq.customer_id = c.id
                LEFT JOIN branches b ON cq.branch_id = b.id
                LEFT JOIN users u ON cq.created_by = u.id
                WHERE cq.id = :id
            ", [':id' => $chequeId]);
        } catch (Throwable $e) {
            Logger::error("Failed to fetch cheque details for #{$chequeId}: " . $e->getMessage(), $e);
            return null;
        }
    }
}
