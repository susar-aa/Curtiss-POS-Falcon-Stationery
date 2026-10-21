<?php
/**
 * Curtiss POS - Supplier Payments & Cheques Core Component (Stage 9 & Section 8 Enhancements)
 * Manages supplier outstanding calculation based ONLY on actual GRN totals,
 * payment allocations to approved GRNs, bank account dedicated COA support,
 * cash drawer integration (direction OUT), and cheque foundation tracking.
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Audit.php';
require_once __DIR__ . '/Supplier.php';
require_once __DIR__ . '/GRN.php';

class SupplierPayment
{
    /**
     * Generate continuous supplier payment voucher reference number (SPAY-0001, SPAY-0002...)
     */
    public static function generatePaymentNumber(): string
    {
        try {
            $db = Database::getInstance()->getConnection();
            $nextNum = 1;
            
            $stmt = $db->query("SELECT payment_no FROM supplier_payments WHERE payment_no LIKE 'SPAY-%' ORDER BY id DESC LIMIT 1");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && preg_match('/SPAY-(\d+)/', $row['payment_no'], $m)) {
                $nextNum = max($nextNum, ((int)$m[1]) + 1);
            }

            return 'SPAY-' . sprintf('%04d', $nextNum);
        } catch (Throwable $e) {
            Logger::error("Failed to generate supplier payment number: " . $e->getMessage(), $e);
            return 'SPAY-' . sprintf('%04d', rand(1000, 9999));
        }
    }

    /**
     * Search Suppliers by Code, Name, Company, Phone, WhatsApp
     */
    public static function searchSuppliers(string $query): array
    {
        try {
            $q = '%' . trim($query) . '%';
            return Database::fetchAll("
                SELECT id, supplier_code, name, phone, whatsapp, status
                FROM suppliers
                WHERE (name LIKE :q1 OR supplier_code LIKE :q2 OR phone LIKE :q3 OR whatsapp LIKE :q4)
                  AND deleted_at IS NULL
                ORDER BY name ASC
                LIMIT 20
            ", [
                ':q1' => $q,
                ':q2' => $q,
                ':q3' => $q,
                ':q4' => $q
            ]);
        } catch (Throwable $e) {
            Logger::error("Failed to search suppliers: " . $e->getMessage(), $e);
            return [];
        }
    }

    /**
     * Get Supplier Outstanding Summary & Approved GRNs List (Calculated ONLY from actual GRN totals)
     */
    public static function getSupplierOutstandingSummary(int $supplierId, ?int $branchId = null): array
    {
        try {
            $supplier = Supplier::find($supplierId);
            if (!$supplier) {
                return ['success' => false, 'message' => 'Supplier not found.'];
            }

            $where = ["g.supplier_id = :supplier_id AND g.status = 'Approved'"];
            $params = [':supplier_id' => $supplierId];

            if ($branchId) {
                $where[] = "g.branch_id = :branch_id";
                $params[':branch_id'] = $branchId;
            }

            $whereSql = implode(' AND ', $where);

            $grns = Database::fetchAll("
                SELECT 
                    g.id AS grn_id,
                    g.grn_number,
                    g.supplier_invoice_no,
                    g.received_date,
                    g.actual_grn_total,
                    g.paid_amount,
                    g.payment_status,
                    g.branch_id,
                    b.name AS branch_name,
                    po.po_number
                FROM grns g
                JOIN branches b ON g.branch_id = b.id
                LEFT JOIN purchase_orders po ON g.po_id = po.id
                WHERE $whereSql
                ORDER BY g.id ASC
            ", $params);

            $totalOutstanding = 0.00;
            $grnList = [];

            foreach ($grns as $grn) {
                $actualTotal = (float)$grn['actual_grn_total'];
                $paidAmt = (float)$grn['paid_amount'];
                $dueAmt = round(max(0.00, $actualTotal - $paidAmt), 2);

                $grn['actual_grn_total'] = $actualTotal;
                $grn['paid_amount'] = $paidAmt;
                $grn['due_amount'] = $dueAmt;

                if ($dueAmt > 0.001) {
                    $totalOutstanding += $dueAmt;
                }

                $grnList[] = $grn;
            }

            require_once __DIR__ . '/SupplierOpeningBalance.php';
            $activeOb = SupplierOpeningBalance::getActiveOpeningBalance($supplierId, $branchId);
            $obOweAmount = 0.00;
            if ($activeOb && $activeOb['balance_type'] === 'owe_supplier') {
                $obOweAmount = max(0.00, round((float)$activeOb['amount'] - (float)($activeOb['paid_amount'] ?? 0), 2));
            }

            $totalOutstanding = round($totalOutstanding + $obOweAmount, 2);

            // Fetch supplier payment history (filtered by branch if specified)
            $pmtWhere = ["sp.supplier_id = :supplier_id"];
            $pmtParams = [':supplier_id' => $supplierId];
            if ($branchId !== null && $branchId > 0) {
                $pmtWhere[] = "sp.branch_id = :branch_id";
                $pmtParams[':branch_id'] = $branchId;
            }
            $pmtWhereSql = implode(" AND ", $pmtWhere);
            $payments = Database::fetchAll("
                SELECT sp.*, b.name AS branch_name, u.full_name AS user_name, sc.cheque_number, sc.cheque_date, sc.status AS cheque_status
                FROM supplier_payments sp
                JOIN branches b ON sp.branch_id = b.id
                JOIN users u ON sp.created_by = u.id
                LEFT JOIN supplier_cheques sc ON sc.supplier_payment_id = sp.id
                WHERE {$pmtWhereSql}
                ORDER BY sp.id DESC
                LIMIT 50
            ", $pmtParams);

            // Fetch active supplier credits (filtered by branch if specified)
            $crWhere = ["supplier_id = :supplier_id AND status = 'active'"];
            $crParams = [':supplier_id' => $supplierId];
            if ($branchId !== null && $branchId > 0) {
                $crWhere[] = "branch_id = :branch_id";
                $crParams[':branch_id'] = $branchId;
            }
            $crWhereSql = implode(" AND ", $crWhere);
            $credits = Database::fetchAll("
                SELECT * FROM supplier_credits WHERE {$crWhereSql} ORDER BY id DESC
            ", $crParams);

            $totalCreditBalance = 0.00;
            foreach ($credits as $c) {
                $totalCreditBalance += (float)($c['remaining_credit_amount'] ?? $c['remaining_amount'] ?? 0);
            }
            $totalCreditBalance = round($totalCreditBalance, 2);

            return [
                'success'                => true,
                'supplier'               => $supplier,
                'total_outstanding'      => $totalOutstanding,
                'grn_outstanding'        => round($totalOutstanding - $obOweAmount, 2),
                'ob_outstanding'         => $obOweAmount,
                'total_credit_balance'   => $totalCreditBalance,
                'active_opening_balance' => $activeOb,
                'approved_grns'          => $grnList,
                'payment_history'        => $payments,
                'active_credits'         => $credits
            ];

        } catch (Throwable $e) {
            Logger::error("Failed to fetch supplier outstanding summary for #{$supplierId}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Error loading supplier summary: ' . $e->getMessage()];
        }
    }

    /**
     * Batch Fetch Supplier Outstanding Summaries to eliminate N+1 queries
     */
    public static function getBatchSupplierSummaries(array $supplierIds, ?int $branchId = null): array
    {
        if (empty($supplierIds)) return [];

        try {
            $inList = implode(',', array_map('intval', $supplierIds));

            // 1. GRN totals and counts (filtered by branch if specified)
            $grnWhere = "supplier_id IN ($inList) AND status = 'Approved'";
            $grnParams = [];
            if ($branchId) {
                $grnWhere .= " AND branch_id = :branch_id";
                $grnParams[':branch_id'] = $branchId;
            }
            $grns = Database::fetchAll("
                SELECT 
                    supplier_id,
                    COUNT(*) AS approved_grns_count,
                    COALESCE(SUM(actual_grn_total), 0) AS total_grn_amount,
                    COALESCE(SUM(paid_amount), 0) AS total_grn_paid,
                    COALESCE(SUM(GREATEST(0, actual_grn_total - paid_amount)), 0) AS grn_outstanding
                FROM grns
                WHERE {$grnWhere}
                GROUP BY supplier_id
            ", $grnParams);

            $grnSummaryMap = [];
            foreach ($grns as $row) {
                $grnSummaryMap[(int)$row['supplier_id']] = $row;
            }

            // 2. Active Opening Balances (filtered by branch if specified)
            $obWhere = "ob.supplier_id IN ($inList) AND ob.status = 'posted'";
            $obParams = [];
            if ($branchId) {
                $obWhere .= " AND ob.branch_id = :ob_branch_id";
                $obParams[':ob_branch_id'] = $branchId;
            }
            $obs = Database::fetchAll("
                SELECT 
                    ob.*, 
                    u.full_name AS created_by_name, 
                    je.entry_number AS journal_number
                FROM supplier_opening_balances ob
                JOIN users u ON ob.created_by = u.id
                LEFT JOIN journal_entries je ON ob.journal_entry_id = je.id
                WHERE {$obWhere}
                ORDER BY ob.id DESC
            ", $obParams);
            $obMap = [];
            foreach ($obs as $ob) {
                $sId = (int)$ob['supplier_id'];
                if (!isset($obMap[$sId])) {
                    $obMap[$sId] = $ob;
                }
            }

            // 3. Active Supplier Credits (filtered by branch if specified)
            $crWhere = "supplier_id IN ($inList) AND status = 'active'";
            $crParams = [];
            if ($branchId) {
                $crWhere .= " AND branch_id = :cr_branch_id";
                $crParams[':cr_branch_id'] = $branchId;
            }
            $credits = Database::fetchAll("
                SELECT 
                    supplier_id,
                    COALESCE(SUM(remaining_credit_amount), 0) AS total_credit_balance
                FROM supplier_credits
                WHERE {$crWhere}
                GROUP BY supplier_id
            ", $crParams);
            $creditMap = [];
            foreach ($credits as $cr) {
                $creditMap[(int)$cr['supplier_id']] = (float)$cr['total_credit_balance'];
            }

            // 4. Supplier Payments Count (filtered by branch if specified)
            $pmtWhere = "supplier_id IN ($inList)";
            $pmtParams = [];
            if ($branchId) {
                $pmtWhere .= " AND branch_id = :pmt_branch_id";
                $pmtParams[':pmt_branch_id'] = $branchId;
            }
            $payments = Database::fetchAll("
                SELECT 
                    supplier_id,
                    COUNT(*) AS payment_count
                FROM supplier_payments
                WHERE {$pmtWhere}
                GROUP BY supplier_id
            ", $pmtParams);
            $paymentMap = [];
            foreach ($payments as $pmt) {
                $paymentMap[(int)$pmt['supplier_id']] = (int)$pmt['payment_count'];
            }

            $result = [];
            foreach ($supplierIds as $id) {
                $grnRow = $grnSummaryMap[$id] ?? null;
                $grnOut = (float)($grnRow['grn_outstanding'] ?? 0.00);
                $approvedGrnsCount = (int)($grnRow['approved_grns_count'] ?? 0);

                $activeOb = $obMap[$id] ?? null;
                $obOweAmount = 0.00;
                if ($activeOb && $activeOb['balance_type'] === 'owe_supplier') {
                    $obOweAmount = max(0.00, round((float)$activeOb['amount'] - (float)($activeOb['paid_amount'] ?? 0), 2));
                }

                $totalOutstanding = round($grnOut + $obOweAmount, 2);
                $totalCredit = round($creditMap[$id] ?? 0.00, 2);
                $paymentCount = (int)($paymentMap[$id] ?? 0);

                $result[$id] = [
                    'total_outstanding'      => $totalOutstanding,
                    'total_credit_balance'   => $totalCredit,
                    'active_opening_balance' => $activeOb,
                    'approved_grns_count'    => $approvedGrnsCount,
                    'payment_history_count'  => $paymentCount
                ];
            }

            return $result;
        } catch (Throwable $e) {
            Logger::error("Failed to batch fetch supplier summaries: " . $e->getMessage(), $e);
            return [];
        }
    }

    /**
     * Process Supplier Payment and GRN allocations inside a single database transaction (Supports Multi-Account Splits)
     */
    public static function processPayment(array $data, int $userId, int $branchId): array
    {
        $db = Database::getInstance()->getConnection();

        try {
            $db->beginTransaction();

            $supplierId = (int)($data['supplier_id'] ?? 0);
            $paymentDate = !empty($data['payment_date']) ? trim($data['payment_date']) : date('Y-m-d');
            $notes = !empty($data['notes']) ? trim($data['notes']) : 'Supplier payment';

            if ($supplierId <= 0) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Valid supplier is required.'];
            }

            // Lock supplier record (allow payment even if supplier status is inactive)
            $supplier = Database::fetchOne("SELECT * FROM suppliers WHERE id = :id FOR UPDATE", [':id' => $supplierId]);
            if (!$supplier) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Supplier not found.'];
            }

            // Calculate current total outstanding from approved GRNs FOR UPDATE
            $approvedGrns = Database::fetchAll("
                SELECT id, actual_grn_total, paid_amount 
                FROM grns 
                WHERE supplier_id = :supplier_id AND status = 'Approved' 
                ORDER BY received_date ASC, id ASC
                FOR UPDATE
            ", [':supplier_id' => $supplierId]);

            $grnOutstanding = 0.00;
            foreach ($approvedGrns as $ag) {
                $due = round((float)$ag['actual_grn_total'] - (float)$ag['paid_amount'], 2);
                if ($due > 0.001) {
                    $grnOutstanding += $due;
                }
            }
            $grnOutstanding = round($grnOutstanding, 2);

            // Calculate active opening balance (We Owe Supplier) FOR UPDATE
            require_once __DIR__ . '/SupplierOpeningBalance.php';
            $activeOb = Database::fetchOne("
                SELECT id, amount, paid_amount 
                FROM supplier_opening_balances 
                WHERE supplier_id = :supplier_id AND status = 'posted' AND balance_type = 'owe_supplier' 
                FOR UPDATE
            ", [':supplier_id' => $supplierId]);

            $obDue = 0.00;
            if ($activeOb) {
                $obDue = max(0.00, round((float)$activeOb['amount'] - (float)($activeOb['paid_amount'] ?? 0), 2));
            }

            $totalOutstanding = round($grnOutstanding + $obDue, 2);

            if ($totalOutstanding <= 0.00) {
                $db->rollBack();
                return ['success' => false, 'message' => "Supplier '{$supplier['name']}' has no outstanding balance."];
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
                $splits = [[
                    'payment_method'  => $data['payment_method'] ?? 'Cash in Hand',
                    'bank_account_id' => $data['bank_account_id'] ?? null,
                    'amount'          => $data['amount'] ?? 0,
                    'reference_no'    => $data['reference_no'] ?? null,
                    'bank_name'       => $data['bank_name'] ?? null,
                    'cheque_no'       => $data['cheque']['cheque_number'] ?? null,
                    'cheque_date'     => $data['cheque']['cheque_date'] ?? null,
                    'cheque_bank'     => $data['cheque']['bank_name'] ?? null,
                ]];
            }

            // Calculate total payment amount
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
                return ['success' => false, 'message' => 'Payment amount must be greater than zero.'];
            }

            if ($totalAmount > ($totalOutstanding + 0.01)) {
                $db->rollBack();
                return [
                    'success' => false,
                    'message' => "Overpayment prevented: Total payment (LKR " . number_format($totalAmount, 2) . ") exceeds supplier total outstanding (LKR " . number_format($totalOutstanding, 2) . ")."
                ];
            }

            // Pre-validate all splits before executing mutations
            $validatedSplits = [];
            foreach ($splits as $sp) {
                $rawMethod = trim($sp['payment_method'] ?? $sp['method'] ?? 'Cash in Hand');
                $bankAccountId = !empty($sp['bank_account_id']) ? (int)$sp['bank_account_id'] : null;

                if (strpos($rawMethod, 'Bank:') === 0) {
                    $parts = explode(':', $rawMethod);
                    $paymentMethod = 'Bank';
                    $bankAccountId = (int)($parts[1] ?? 0);
                } elseif (strpos($rawMethod, 'Cheque:') === 0) {
                    $parts = explode(':', $rawMethod);
                    $paymentMethod = 'Cheque';
                    $bankAccountId = (int)($parts[1] ?? 0);
                } elseif (strcasecmp($rawMethod, 'Cash Drawer') === 0 || strcasecmp($rawMethod, 'cash_drawer') === 0) {
                    $paymentMethod = 'Cash Drawer';
                } elseif (strcasecmp($rawMethod, 'Cash in Hand') === 0 || strcasecmp($rawMethod, 'cash_in_hand') === 0 || strcasecmp($rawMethod, 'Cash') === 0) {
                    $paymentMethod = 'Cash in Hand';
                } elseif (strcasecmp($rawMethod, 'Petty Cash') === 0 || strcasecmp($rawMethod, 'petty_cash') === 0) {
                    $paymentMethod = 'Petty Cash';
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

                $validMethods = ['Cash Drawer', 'Cash in Hand', 'Petty Cash', 'Bank', 'Cheque'];
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

                $bankName = trim($sp['bank_name'] ?? '');
                if ($paymentMethod === 'Bank') {
                    if ($bankAccountId <= 0) {
                        $defaultBank = Database::fetchOne("SELECT id FROM bank_accounts WHERE status = 'active' ORDER BY id ASC LIMIT 1");
                        if ($defaultBank) {
                            $bankAccountId = (int)$defaultBank['id'];
                        } else {
                            $db->rollBack();
                            return ['success' => false, 'message' => 'Bank account selection is required for bank transfer split.'];
                        }
                    }
                    $bankRow = Database::fetchOne("SELECT * FROM bank_accounts WHERE id = :id AND status = 'active'", [':id' => $bankAccountId]);
                    if (!$bankRow) {
                        $db->rollBack();
                        return ['success' => false, 'message' => 'Selected bank account is invalid or inactive.'];
                    }
                    if (empty($bankName)) {
                        $bankName = $bankRow['bank_name'] . ' - ' . $bankRow['account_name'];
                    }
                }

                $chequeNo = null;
                $chequeDate = null;
                if ($paymentMethod === 'Cheque') {
                    $chequeNo = trim($sp['cheque_no'] ?? $sp['reference_no'] ?? '');
                    $chequeDate = trim($sp['cheque_date'] ?? $paymentDate);

                    if (empty($chequeNo)) {
                        $db->rollBack();
                        return ['success' => false, 'message' => 'Cheque number is required for issued cheque.'];
                    }

                    if ($bankAccountId <= 0) {
                        $defaultBank = Database::fetchOne("SELECT id FROM bank_accounts WHERE status = 'active' ORDER BY id ASC LIMIT 1");
                        if ($defaultBank) {
                            $bankAccountId = (int)$defaultBank['id'];
                        } else {
                            $db->rollBack();
                            return ['success' => false, 'message' => 'Bank account selection is required for issued cheque.'];
                        }
                    }

                    $bankRow = Database::fetchOne("SELECT * FROM bank_accounts WHERE id = :id AND status = 'active'", [':id' => $bankAccountId]);
                    if (!$bankRow) {
                        $db->rollBack();
                        return ['success' => false, 'message' => 'Selected bank account is invalid or inactive.'];
                    }
                    $bankName = $bankRow['bank_name'] . ' - ' . $bankRow['account_name'];

                    // Duplicate cheque validation on bank account
                    $dupCheque = Database::fetchOne("
                        SELECT id, cheque_number, status FROM supplier_cheques 
                        WHERE bank_account_id = :bid AND cheque_number = :cno AND deleted_at IS NULL AND status IN ('pending', 'cleared')
                    ", [':bid' => $bankAccountId, ':cno' => $chequeNo]);

                    if ($dupCheque) {
                        $db->rollBack();
                        return [
                            'success' => false, 
                            'message' => "Duplicate Cheque Error: Cheque #{$chequeNo} has already been issued from {$bankRow['bank_name']} ({$bankRow['account_number']}) [Status: {$dupCheque['status']}]."
                        ];
                    }

                    if (empty($chequeDate)) {
                        $chequeDate = date('Y-m-d');
                    }
                }

                if ($paymentMethod === 'Cash Drawer' || $paymentMethod === 'Cash in Hand' || $isCustomCoa) {
                    require_once __DIR__ . '/BusinessDay.php';
                    $activeDay = BusinessDay::getOrCreateActiveDay($branchId, $userId);
                    if (!$activeDay) {
                        $db->rollBack();
                        return ['success' => false, 'message' => 'Cannot process cash payment. No active business day is currently open for this branch.'];
                    }
                }

                $validatedSplits[] = [
                    'payment_method'  => $paymentMethod,
                    'bank_account_id' => $bankAccountId,
                    'amount'          => round((float)($sp['amount'] ?? 0), 2),
                    'reference_no'    => !empty($sp['reference_no']) ? trim($sp['reference_no']) : null,
                    'bank_name'       => $bankName ?: null,
                    'cheque_no'       => $chequeNo,
                    'cheque_date'     => $chequeDate
                ];
            }

            $basePaymentNo = self::generatePaymentNumber();
            $createdPaymentIds = [];

            // Statements for payment header, cheques, and allocations
            $stmtHeader = $db->prepare("
                INSERT INTO supplier_payments (
                    payment_no, supplier_id, branch_id, bank_account_id, payment_date, payment_method,
                    total_amount, reference_no, bank_name, notes, created_by
                ) VALUES (
                    :payment_no, :supplier_id, :branch_id, :bank_account_id, :payment_date, :payment_method,
                    :total_amount, :reference_no, :bank_name, :notes, :created_by
                )
            ");

            $stmtCheque = $db->prepare("
                INSERT INTO supplier_cheques (
                    supplier_payment_id, supplier_id, branch_id, bank_account_id, cheque_number, cheque_date,
                    bank_name, amount, status, created_by
                ) VALUES (
                    :supplier_payment_id, :supplier_id, :branch_id, :bank_account_id, :cheque_number, :cheque_date,
                    :bank_name, :amount, 'pending', :created_by
                )
            ");

            $stmtAlloc = $db->prepare("
                INSERT INTO supplier_payment_grns (supplier_payment_id, grn_id, allocated_amount)
                VALUES (:supplier_payment_id, :grn_id, :allocated_amount)
            ");

            // Build obligations queue (FIFO)
            // 1. Opening Balance (oldest liability) if outstanding
            $obligations = [];
            if ($activeOb && $obDue > 0.001) {
                $obligations[] = [
                    'type'     => 'ob',
                    'id'       => (int)$activeOb['id'],
                    'due'      => $obDue,
                    'paid'     => (float)($activeOb['paid_amount'] ?? 0)
                ];
            }

            // 2. Approved unpaid GRNs FIFO
            foreach ($approvedGrns as $ag) {
                $actualTot = (float)$ag['actual_grn_total'];
                $paidAmt = (float)$ag['paid_amount'];
                $due = round($actualTot - $paidAmt, 2);
                if ($due > 0.001) {
                    $obligations[] = [
                        'type'       => 'grn',
                        'id'         => (int)$ag['id'],
                        'actual_tot' => $actualTot,
                        'due'        => $due,
                        'paid'       => $paidAmt
                    ];
                }
            }

            $obIndex = 0;

            foreach ($validatedSplits as $idx => $vSp) {
                $spAmt = $vSp['amount'];
                $spMethod = $vSp['payment_method'];
                $spBankId = $vSp['bank_account_id'];
                $linePmtNo = (count($validatedSplits) === 1) ? $basePaymentNo : ($basePaymentNo . '-' . ($idx + 1));
                $spRef = $vSp['reference_no'] ?: $linePmtNo;
                if ($spMethod === 'Cheque' && !empty($vSp['cheque_no'])) {
                    $spRef = $vSp['cheque_no'];
                }

                $splitNote = "Supplier payment via {$spMethod}";
                if (!empty($notes)) {
                    $splitNote .= " - {$notes}";
                }

                $stmtHeader->execute([
                    ':payment_no'      => $linePmtNo,
                    ':supplier_id'     => $supplierId,
                    ':branch_id'       => $branchId,
                    ':bank_account_id' => $spBankId,
                    ':payment_date'    => $paymentDate,
                    ':payment_method'  => $spMethod,
                    ':total_amount'    => $spAmt,
                    ':reference_no'    => $spRef,
                    ':bank_name'       => $vSp['bank_name'],
                    ':notes'           => $splitNote,
                    ':created_by'      => $userId
                ]);

                $supplierPaymentId = (int)$db->lastInsertId();
                $createdPaymentIds[] = $supplierPaymentId;

                if ($spMethod === 'Cash Drawer') {
                    require_once __DIR__ . '/BusinessDay.php';
                    $activeDay = BusinessDay::getOrCreateActiveDay($branchId, $userId);
                    if ($activeDay) {
                        BusinessDay::recordDrawerMovement([
                            'business_day_id' => (int)$activeDay['id'],
                            'branch_id'       => $branchId,
                            'movement_type'   => 'supplier_payment',
                            'source_type'     => 'supplier_payments',
                            'source_id'       => $supplierPaymentId,
                            'amount'          => $spAmt,
                            'direction'       => 'out',
                            'reason'          => "Supplier Cash Drawer Payment ({$linePmtNo})",
                            'user_id'         => $userId
                        ]);
                    }
                }

                if ($spMethod === 'Cheque') {
                    $stmtCheque->execute([
                        ':supplier_payment_id' => $supplierPaymentId,
                        ':supplier_id'         => $supplierId,
                        ':branch_id'           => $branchId,
                        ':bank_account_id'     => $spBankId,
                        ':cheque_number'       => $vSp['cheque_no'],
                        ':cheque_date'         => $vSp['cheque_date'],
                        ':bank_name'           => $vSp['bank_name'] ?: 'Primary Bank',
                        ':amount'              => $spAmt,
                        ':created_by'          => $userId
                    ]);
                    $chequeId = (int)$db->lastInsertId();
                    Audit::log('SUPPLIER_CHEQUE_RECORDED', 'purchasing', (string)$chequeId, null, [
                        'cheque_number' => $vSp['cheque_no'],
                        'cheque_date'   => $vSp['cheque_date'],
                        'bank_name'     => $vSp['bank_name'],
                        'amount'        => $spAmt
                    ]);
                }

                // Allocate this split's amount across obligations FIFO
                $remSplitAmt = $spAmt;
                while ($remSplitAmt > 0.001 && $obIndex < count($obligations)) {
                    $currentOb = &$obligations[$obIndex];
                    $alloc = min($remSplitAmt, $currentOb['due']);

                    if ($currentOb['type'] === 'ob') {
                        $currentOb['paid'] = round($currentOb['paid'] + $alloc, 2);
                        $currentOb['due'] = round($currentOb['due'] - $alloc, 2);
                        $db->prepare("
                            UPDATE supplier_opening_balances SET paid_amount = :paid WHERE id = :id
                        ")->execute([':paid' => $currentOb['paid'], ':id' => $currentOb['id']]);
                    } elseif ($currentOb['type'] === 'grn') {
                        $currentOb['paid'] = round($currentOb['paid'] + $alloc, 2);
                        $currentOb['due'] = round($currentOb['due'] - $alloc, 2);
                        $pStatus = ($currentOb['paid'] >= $currentOb['actual_tot'] - 0.001) ? 'paid' : 'partially_paid';

                        $db->prepare("
                            UPDATE grns SET paid_amount = :paid, payment_status = :pstatus WHERE id = :id
                        ")->execute([':paid' => $currentOb['paid'], ':pstatus' => $pStatus, ':id' => $currentOb['id']]);

                        $stmtAlloc->execute([
                            ':supplier_payment_id' => $supplierPaymentId,
                            ':grn_id'              => $currentOb['id'],
                            ':allocated_amount'    => $alloc
                        ]);
                    }

                    $remSplitAmt = round($remSplitAmt - $alloc, 2);
                    if ($currentOb['due'] <= 0.001) {
                        $obIndex++;
                    }
                }
            }

            $db->commit();

            // Post double-entry journals for each split
            require_once __DIR__ . '/Accounting.php';
            foreach ($createdPaymentIds as $spId) {
                Accounting::postSupplierPayment($spId);
            }

            Audit::log('SUPPLIER_PAYMENT_PROCESSED', 'purchasing', (string)$createdPaymentIds[0], null, [
                'payment_no'     => $basePaymentNo,
                'supplier_name'  => $supplier['name'],
                'total_amount'   => $totalAmount,
                'splits_count'   => count($validatedSplits)
            ]);

            return [
                'success'             => true,
                'message'             => "Supplier Payment {$basePaymentNo} of LKR " . number_format($totalAmount, 2) . " processed successfully.",
                'supplier_payment_id' => $createdPaymentIds[0],
                'payment_ids'         => $createdPaymentIds,
                'payment_no'          => $basePaymentNo,
                'total_amount'        => $totalAmount
            ];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to process supplier payment: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => "Supplier Payment Error: " . $e->getMessage()];
        }
    }
}
