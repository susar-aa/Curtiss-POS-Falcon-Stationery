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
            $activeOb = SupplierOpeningBalance::getActiveOpeningBalance($supplierId);
            $obOweAmount = 0.00;
            if ($activeOb && $activeOb['balance_type'] === 'owe_supplier') {
                $obOweAmount = max(0.00, round((float)$activeOb['amount'] - (float)($activeOb['paid_amount'] ?? 0), 2));
            }

            $totalOutstanding = round($totalOutstanding + $obOweAmount, 2);

            // Fetch supplier payment history
            $payments = Database::fetchAll("
                SELECT sp.*, b.name AS branch_name, u.full_name AS user_name, sc.cheque_number, sc.cheque_date, sc.status AS cheque_status
                FROM supplier_payments sp
                JOIN branches b ON sp.branch_id = b.id
                JOIN users u ON sp.created_by = u.id
                LEFT JOIN supplier_cheques sc ON sc.supplier_payment_id = sp.id
                WHERE sp.supplier_id = :supplier_id
                ORDER BY sp.id DESC
                LIMIT 50
            ", [':supplier_id' => $supplierId]);

            // Fetch active supplier credits
            $credits = Database::fetchAll("
                SELECT * FROM supplier_credits WHERE supplier_id = :supplier_id AND status = 'active' ORDER BY id DESC
            ", [':supplier_id' => $supplierId]);

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

            // 2. Active Opening Balances
            $obs = Database::fetchAll("
                SELECT 
                    ob.*, 
                    u.full_name AS created_by_name, 
                    je.entry_number AS journal_number
                FROM supplier_opening_balances ob
                JOIN users u ON ob.created_by = u.id
                LEFT JOIN journal_entries je ON ob.journal_entry_id = je.id
                WHERE ob.supplier_id IN ($inList) AND ob.status = 'posted'
                ORDER BY ob.id DESC
            ");
            $obMap = [];
            foreach ($obs as $ob) {
                $sId = (int)$ob['supplier_id'];
                if (!isset($obMap[$sId])) {
                    $obMap[$sId] = $ob;
                }
            }

            // 3. Active Supplier Credits
            $credits = Database::fetchAll("
                SELECT 
                    supplier_id,
                    COALESCE(SUM(remaining_credit_amount), 0) AS total_credit_balance
                FROM supplier_credits
                WHERE supplier_id IN ($inList) AND status = 'active'
                GROUP BY supplier_id
            ");
            $creditMap = [];
            foreach ($credits as $cr) {
                $creditMap[(int)$cr['supplier_id']] = (float)$cr['total_credit_balance'];
            }

            // 4. Supplier Payments Count
            $payments = Database::fetchAll("
                SELECT 
                    supplier_id,
                    COUNT(*) AS payment_count
                FROM supplier_payments
                WHERE supplier_id IN ($inList)
                GROUP BY supplier_id
            ");
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
     * Process Supplier Payment and GRN allocations inside a single database transaction
     */
    public static function processPayment(array $data, int $userId, int $branchId): array
    {
        $db = Database::getInstance()->getConnection();

        try {
            $db->beginTransaction();

            $supplierId = (int)($data['supplier_id'] ?? 0);
            $paymentDate = !empty($data['payment_date']) ? trim($data['payment_date']) : date('Y-m-d');
            
            $rawMethod = trim($data['payment_method'] ?? 'Cash in Hand');
            $bankAccountId = !empty($data['bank_account_id']) ? (int)$data['bank_account_id'] : null;

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
            } elseif (strcasecmp($rawMethod, 'Cheque') === 0) {
                $paymentMethod = 'Cheque';
            } elseif (strcasecmp($rawMethod, 'Bank') === 0) {
                $paymentMethod = 'Bank';
            } else {
                $paymentMethod = 'Cash in Hand';
            }

            $totalAmount = round((float)($data['amount'] ?? 0), 2);
            $referenceNo = !empty($data['reference_no']) ? trim($data['reference_no']) : null;
            $bankName = !empty($data['bank_name']) ? trim($data['bank_name']) : null;
            $notes = !empty($data['notes']) ? trim($data['notes']) : 'Supplier payment';
            $allocations = $data['allocations'] ?? [];
            $chequeData = $data['cheque'] ?? null;

            if (!in_array($paymentMethod, ['Cash Drawer', 'Cash in Hand', 'Cash', 'Petty Cash', 'Bank', 'Cheque'], true)) {
                $db->rollBack();
                return ['success' => false, 'message' => "Unsupported supplier payment method '{$paymentMethod}'."];
            }

            if ($supplierId <= 0) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Valid supplier is required.'];
            }

            if ($paymentMethod === 'Bank') {
                if ($bankAccountId <= 0) {
                    $defaultBank = Database::fetchOne("SELECT id FROM bank_accounts WHERE status = 'active' ORDER BY id ASC LIMIT 1");
                    if ($defaultBank) {
                        $bankAccountId = (int)$defaultBank['id'];
                    } else {
                        $db->rollBack();
                        return ['success' => false, 'message' => 'Bank account selection is required for bank supplier payment.'];
                    }
                }
                $bankRow = Database::fetchOne("SELECT * FROM bank_accounts WHERE id = :id AND status = 'active'", [':id' => $bankAccountId]);
                if (!$bankRow) {
                    $db->rollBack();
                    return ['success' => false, 'message' => 'Selected bank account is invalid or inactive.'];
                }
                $bankName = $bankRow['bank_name'] . ' - ' . $bankRow['account_name'];
            }

            if ($paymentMethod === 'Cash Drawer' || $paymentMethod === 'Cash in Hand' || $paymentMethod === 'Cash') {
                require_once __DIR__ . '/BusinessDay.php';
                $activeDay = BusinessDay::getOrCreateActiveDay($branchId, $userId);
                if (!$activeDay) {
                    $db->rollBack();
                    return ['success' => false, 'message' => 'Cannot process supplier cash payment. No active business day is currently open for this branch.'];
                }
            }

            if ($totalAmount <= 0.00) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Payment amount must be greater than zero.'];
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
                WHERE supplier_id = :supplier_id AND status = 'Approved' FOR UPDATE
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

            if ($totalAmount > ($totalOutstanding + 0.01)) {
                $db->rollBack();
                return [
                    'success' => false,
                    'message' => "Overpayment prevented: Payment amount (LKR " . number_format($totalAmount, 2) . ") exceeds supplier total outstanding (LKR " . number_format($totalOutstanding, 2) . ")."
                ];
            }

            // Cheque details validation if method is Cheque
            if ($paymentMethod === 'Cheque') {
                if (empty($chequeData['cheque_number']) || empty($chequeData['cheque_date']) || empty($chequeData['bank_name'])) {
                    $db->rollBack();
                    return ['success' => false, 'message' => 'Cheque number, cheque date, and bank name are mandatory for cheque payments.'];
                }
                $bankName = trim($chequeData['bank_name']);
                $referenceNo = trim($chequeData['cheque_number']);
                $bankAccountId = !empty($chequeData['bank_account_id']) ? (int)$chequeData['bank_account_id'] : $bankAccountId;
            }

            $paymentNo = self::generatePaymentNumber();

            // Insert supplier_payments Header
            $stmtHeader = $db->prepare("
                INSERT INTO supplier_payments (
                    payment_no, supplier_id, branch_id, bank_account_id, payment_date, payment_method,
                    total_amount, reference_no, bank_name, notes, created_by
                ) VALUES (
                    :payment_no, :supplier_id, :branch_id, :bank_account_id, :payment_date, :payment_method,
                    :total_amount, :reference_no, :bank_name, :notes, :created_by
                )
            ");

            $stmtHeader->execute([
                ':payment_no'      => $paymentNo,
                ':supplier_id'     => $supplierId,
                ':branch_id'       => $branchId,
                ':bank_account_id' => $bankAccountId,
                ':payment_date'    => $paymentDate,
                ':payment_method'  => $paymentMethod,
                ':total_amount'    => $totalAmount,
                ':reference_no'    => $referenceNo,
                ':bank_name'       => $bankName,
                ':notes'           => $notes,
                ':created_by'      => $userId
            ]);

            $supplierPaymentId = (int)$db->lastInsertId();

            if ($paymentMethod === 'Cash' && isset($activeDay) && $activeDay) {
                BusinessDay::recordDrawerMovement([
                    'business_day_id' => (int)$activeDay['id'],
                    'branch_id'       => $branchId,
                    'movement_type'   => 'supplier_payment',
                    'source_type'     => 'supplier_payments',
                    'source_id'       => $supplierPaymentId,
                    'amount'          => $totalAmount,
                    'direction'       => 'out',
                    'reason'          => "Supplier Cash Payment ({$paymentNo})",
                    'user_id'         => $userId
                ]);
            }

            // Record Cheque Foundation if method is Cheque
            if ($paymentMethod === 'Cheque') {
                $stmtCheque = $db->prepare("
                    INSERT INTO supplier_cheques (
                        supplier_payment_id, supplier_id, branch_id, bank_account_id, cheque_number, cheque_date,
                        bank_name, amount, status, created_by
                    ) VALUES (
                        :supplier_payment_id, :supplier_id, :branch_id, :bank_account_id, :cheque_number, :cheque_date,
                        :bank_name, :amount, 'pending', :created_by
                    )
                ");

                $stmtCheque->execute([
                    ':supplier_payment_id' => $supplierPaymentId,
                    ':supplier_id'         => $supplierId,
                    ':branch_id'           => $branchId,
                    ':bank_account_id'     => $bankAccountId,
                    ':cheque_number'       => trim($chequeData['cheque_number']),
                    ':cheque_date'         => trim($chequeData['cheque_date']),
                    ':bank_name'           => $bankName,
                    ':amount'              => $totalAmount,
                    ':created_by'          => $userId
                ]);

                $chequeId = (int)$db->lastInsertId();
                Audit::log('SUPPLIER_CHEQUE_RECORDED', 'purchasing', (string)$chequeId, null, [
                    'cheque_number' => trim($chequeData['cheque_number']),
                    'cheque_date'   => trim($chequeData['cheque_date']),
                    'bank_name'     => $bankName,
                    'amount'        => $totalAmount
                ]);
            }

            // Allocate Payment to Approved GRNs
            $stmtAlloc = $db->prepare("
                INSERT INTO supplier_payment_grns (supplier_payment_id, grn_id, allocated_amount)
                VALUES (:supplier_payment_id, :grn_id, :allocated_amount)
            ");

            $remPay = $totalAmount;

            if (!empty($allocations)) {
                $sumAlloc = 0.00;
                foreach ($allocations as $allocItem) {
                    $grnId = (int)($allocItem['grn_id'] ?? 0);
                    $aAmount = round((float)($allocItem['amount'] ?? 0), 2);

                    if ($grnId > 0 && $aAmount > 0) {
                        $grnRow = Database::fetchOne("
                            SELECT actual_grn_total, paid_amount, status FROM grns WHERE id = :id AND supplier_id = :sid FOR UPDATE
                        ", [':id' => $grnId, ':sid' => $supplierId]);

                        if (!$grnRow || $grnRow['status'] !== 'Approved') {
                            $db->rollBack();
                            return ['success' => false, 'message' => "GRN #{$grnId} is not approved or does not belong to supplier."];
                        }

                        $due = round((float)$grnRow['actual_grn_total'] - (float)$grnRow['paid_amount'], 2);
                        if ($aAmount > ($due + 0.01)) {
                            $db->rollBack();
                            return ['success' => false, 'message' => "Allocation (LKR " . number_format($aAmount, 2) . ") exceeds GRN #{$grnId} remaining due (LKR " . number_format($due, 2) . ")." ];
                        }

                        $newPaid = round((float)$grnRow['paid_amount'] + $aAmount, 2);
                        $pStatus = ($newPaid >= (float)$grnRow['actual_grn_total'] - 0.001) ? 'paid' : 'partially_paid';

                        $db->prepare("
                            UPDATE grns SET paid_amount = :paid, payment_status = :pstatus WHERE id = :id
                        ")->execute([':paid' => $newPaid, ':pstatus' => $pStatus, ':id' => $grnId]);

                        $stmtAlloc->execute([
                            ':supplier_payment_id' => $supplierPaymentId,
                            ':grn_id'              => $grnId,
                            ':allocated_amount'    => $aAmount
                        ]);

                        $sumAlloc += $aAmount;
                    }
                }

                $remPay = round($totalAmount - $sumAlloc, 2);
                if ($remPay > 0.001 && $activeOb && $obDue > 0.001) {
                    $obAlloc = min($remPay, $obDue);
                    $newObPaid = round((float)($activeOb['paid_amount'] ?? 0) + $obAlloc, 2);
                    $db->prepare("
                        UPDATE supplier_opening_balances SET paid_amount = :paid WHERE id = :id
                    ")->execute([':paid' => $newObPaid, ':id' => $activeOb['id']]);
                    $remPay = round($remPay - $obAlloc, 2);
                }

                if (abs($remPay) > 0.01) {
                    $db->rollBack();
                    return ['success' => false, 'message' => "Allocation sum does not match total payment amount (LKR " . number_format($totalAmount, 2) . ")." ];
                }
            } else {
                // Auto Allocation:
                // 1. First allocate to active Opening Balance (oldest liability) if outstanding
                if ($activeOb && $obDue > 0.001) {
                    $obAlloc = min($remPay, $obDue);
                    $newObPaid = round((float)($activeOb['paid_amount'] ?? 0) + $obAlloc, 2);
                    $db->prepare("
                        UPDATE supplier_opening_balances SET paid_amount = :paid WHERE id = :id
                    ")->execute([':paid' => $newObPaid, ':id' => $activeOb['id']]);
                    $remPay = round($remPay - $obAlloc, 2);
                }

                // 2. Next allocate remaining payment to oldest approved unpaid GRNs
                if ($remPay > 0.001) {
                    foreach ($approvedGrns as $ag) {
                        if ($remPay <= 0.001) break;
                        $actualTot = (float)$ag['actual_grn_total'];
                        $paidAmt = (float)$ag['paid_amount'];
                        $due = round($actualTot - $paidAmt, 2);

                        if ($due > 0.001) {
                            $aAmount = min($remPay, $due);
                            $newPaid = round($paidAmt + $aAmount, 2);
                            $pStatus = ($newPaid >= $actualTot - 0.001) ? 'paid' : 'partially_paid';

                            $db->prepare("
                                UPDATE grns SET paid_amount = :paid, payment_status = :pstatus WHERE id = :id
                            ")->execute([':paid' => $newPaid, ':pstatus' => $pStatus, ':id' => $ag['id']]);

                            $stmtAlloc->execute([
                                ':supplier_payment_id' => $supplierPaymentId,
                                ':grn_id'              => $ag['id'],
                                ':allocated_amount'    => $aAmount
                            ]);

                            $remPay = round($remPay - $aAmount, 2);
                        }
                    }
                }
            }

            $db->commit();

            require_once __DIR__ . '/Accounting.php';
            Accounting::postSupplierPayment($supplierPaymentId);

            Audit::log('SUPPLIER_PAYMENT_PROCESSED', 'purchasing', (string)$supplierPaymentId, null, [
                'payment_no'     => $paymentNo,
                'supplier_name'  => $supplier['name'],
                'total_amount'   => $totalAmount,
                'payment_method' => $paymentMethod
            ]);

            return [
                'success'             => true,
                'message'             => "Supplier Payment {$paymentNo} of LKR " . number_format($totalAmount, 2) . " processed successfully.",
                'supplier_payment_id' => $supplierPaymentId,
                'payment_no'          => $paymentNo,
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
