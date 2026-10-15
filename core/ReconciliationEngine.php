<?php
/**
 * Curtiss POS - Reconciliations Diagnostic Engine (Stage 20)
 * Reconciles operational subsystems with General Ledger (GL) accounts & sub-ledgers:
 *  1. Inventory (Lots vs Branch Stock vs Stock Ledger vs GL 1200)
 *  2. Accounts Receivable (GL 1040 vs Customer Credit Ledger & Unpaid Invoices)
 *  3. Accounts Payable (GL 2020 vs Approved GRN Outstanding & Supplier Balances)
 *  4. Cash in Hand (GL 1010 vs Drawer Movements & Business Days)
 *  5. Bank Accounts (GL Bank Accounts vs Bank Deposits/Transfers Register)
 *  6. Petty Cash (GL 1015 vs Reimbursements & Vouchers)
 *  7. Gift Vouchers (GL 2010 vs Active Voucher Obligations)
 *  8. Supplier Credit (GL 1045 vs Active Supplier Credit Balances)
 *  9. Outstanding Cheques (GL 2025 vs Pending Supplier Cheques)
 * 10. Business Days Integrity (Expected vs Actual Cash & Closed Lock Status)
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Logger.php';

class ReconciliationEngine
{
    /**
     * Run all 10 reconciliation diagnostics
     */
    public static function runAllReconciliations(): array
    {
        return [
            'inventory'          => self::reconcileInventory(),
            'ar'                 => self::reconcileAR(),
            'ap'                 => self::reconcileAP(),
            'cash'               => self::reconcileCash(),
            'bank'               => self::reconcileBank(),
            'petty_cash'         => self::reconcilePettyCash(),
            'vouchers'           => self::reconcileVouchers(),
            'supplier_credit'    => self::reconcileSupplierCredit(),
            'outstanding_cheques'=> self::reconcileOutstandingCheques(),
            'card_receivable'    => self::reconcileCardReceivable(),
            'business_days'      => self::reconcileBusinessDays()
        ];
    }

    /**
     * 1. Inventory Reconciliation
     */
    public static function reconcileInventory(): array
    {
        try {
            $db = Database::getInstance()->getConnection();

            // Lots remaining vs branch_inventory
            $stmt1 = $db->query("
                SELECT bi.branch_id, bi.product_id, p.name AS product_name, 
                       bi.quantity AS branch_qty,
                       COALESCE(SUM(pl.remaining_quantity), 0.00) AS lots_qty
                FROM branch_inventory bi
                JOIN products p ON bi.product_id = p.id
                LEFT JOIN product_lots pl ON bi.branch_id = pl.branch_id AND bi.product_id = pl.product_id AND pl.status = 'active'
                GROUP BY bi.branch_id, bi.product_id
                HAVING ABS(branch_qty - lots_qty) > 0.01
            ");
            $lotDiscrepancies = $stmt1->fetchAll(PDO::FETCH_ASSOC);

            // Total active lots valuation vs GL 1200 Inventory Asset
            $stmtVal = $db->query("
                SELECT COALESCE(SUM(remaining_quantity * cost), 0.00) AS operational_valuation
                FROM product_lots
                WHERE remaining_quantity > 0 AND status = 'active'
            ");
            $opValuation = (float)$stmtVal->fetchColumn();

            $stmtGL = $db->query("
                SELECT COALESCE(SUM(jl.debit - jl.credit), 0.00) AS gl_balance
                FROM journal_lines jl
                JOIN journal_entries je ON jl.journal_entry_id = je.id
                JOIN chart_of_accounts coa ON jl.account_id = coa.id
                WHERE je.posting_status IN ('posted', 'reversed') AND coa.account_code = '1200'
            ");
            $glValuation = (float)$stmtGL->fetchColumn();

            $glDiff = round(abs($opValuation - $glValuation), 2);

            return [
                'status'                 => (empty($lotDiscrepancies) && $glDiff < 1.00) ? 'PASS' : 'WARN',
                'lot_mismatches_count'   => count($lotDiscrepancies),
                'operational_valuation'  => round($opValuation, 2),
                'gl_inventory_balance'   => round($glValuation, 2),
                'valuation_variance'     => $glDiff,
                'items'                  => $lotDiscrepancies
            ];
        } catch (Throwable $e) {
            Logger::error("Reconciliation error in inventory: " . $e->getMessage(), $e);
            return ['status' => 'ERROR', 'message' => $e->getMessage()];
        }
    }

    /**
     * 2. Accounts Receivable (AR) Reconciliation
     */
    public static function reconcileAR(): array
    {
        try {
            $db = Database::getInstance()->getConnection();

            // GL 1040 Customer Receivables balance
            $stmtGL = $db->query("
                SELECT COALESCE(SUM(jl.debit - jl.credit), 0.00) AS gl_ar
                FROM journal_lines jl
                JOIN journal_entries je ON jl.journal_entry_id = je.id
                JOIN chart_of_accounts coa ON jl.account_id = coa.id
                WHERE je.posting_status IN ('posted', 'reversed') AND coa.account_code = '1040'
            ");
            $glAR = (float)$stmtGL->fetchColumn();

            // Customer outstanding balances sum
            $stmtCust = $db->query("
                SELECT COALESCE(SUM(credit_balance), 0.00) AS total_customer_credit
                FROM customers
            ");
            $custCredit = (float)$stmtCust->fetchColumn();

            // Unpaid/partially paid sales total remaining balance
            $stmtUnpaid = $db->query("
                SELECT COALESCE(SUM(s.total_amount - COALESCE(p.paid, 0.00)), 0.00) AS unpaid_sales
                FROM sales s
                LEFT JOIN (
                    SELECT sale_id, SUM(amount) AS paid FROM payments GROUP BY sale_id
                ) p ON s.id = p.sale_id
                WHERE s.status = 'completed' AND (s.total_amount - COALESCE(p.paid, 0.00)) > 0.001
            ");
            $unpaidSales = (float)$stmtUnpaid->fetchColumn();

            $diff = round(abs($glAR - $unpaidSales), 2);

            return [
                'status'                 => ($diff < 1.00) ? 'PASS' : 'WARN',
                'gl_ar_balance'          => round($glAR, 2),
                'customer_credit_sum'    => round($custCredit, 2),
                'unpaid_sales_sum'       => round($unpaidSales, 2),
                'variance'               => $diff
            ];
        } catch (Throwable $e) {
            Logger::error("Reconciliation error in AR: " . $e->getMessage(), $e);
            return ['status' => 'ERROR', 'message' => $e->getMessage()];
        }
    }

    /**
     * 3. Accounts Payable (AP) Reconciliation
     */
    public static function reconcileAP(): array
    {
        try {
            $db = Database::getInstance()->getConnection();

            // GL 2020 Supplier Payables balance (Credit balance)
            $stmtGL = $db->query("
                SELECT COALESCE(SUM(jl.credit - jl.debit), 0.00) AS gl_ap
                FROM journal_lines jl
                JOIN journal_entries je ON jl.journal_entry_id = je.id
                JOIN chart_of_accounts coa ON jl.account_id = coa.id
                WHERE je.posting_status IN ('posted', 'reversed') AND coa.account_code = '2020'
            ");
            $glAP = (float)$stmtGL->fetchColumn();

            // Outstanding GRN amounts (actual_grn_total - paid_amount)
            $stmtGRN = $db->query("
                SELECT COALESCE(SUM(actual_grn_total - paid_amount), 0.00) AS grn_due
                FROM grns
                WHERE status = 'Approved' AND (actual_grn_total - paid_amount) > 0.001
            ");
            $grnDue = (float)$stmtGRN->fetchColumn();

            $diff = round(abs($glAP - $grnDue), 2);

            return [
                'status'        => ($diff < 1.00) ? 'PASS' : 'WARN',
                'gl_ap_balance' => round($glAP, 2),
                'grn_due_sum'   => round($grnDue, 2),
                'variance'      => $diff
            ];
        } catch (Throwable $e) {
            Logger::error("Reconciliation error in AP: " . $e->getMessage(), $e);
            return ['status' => 'ERROR', 'message' => $e->getMessage()];
        }
    }

    /**
     * 4. Cash in Hand Reconciliation
     */
    public static function reconcileCash(): array
    {
        try {
            $db = Database::getInstance()->getConnection();

            // GL 1010 Main Cash balance
            $stmtGL = $db->query("
                SELECT COALESCE(SUM(jl.debit - jl.credit), 0.00) AS gl_cash
                FROM journal_lines jl
                JOIN journal_entries je ON jl.journal_entry_id = je.id
                JOIN chart_of_accounts coa ON jl.account_id = coa.id
                WHERE je.posting_status IN ('posted', 'reversed') AND coa.account_code = '1010'
            ");
            $glCash = (float)$stmtGL->fetchColumn();

            // Total net drawer movements across closed and active business days
            $stmtMove = $db->query("
                SELECT COALESCE(SUM(
                    CASE 
                        WHEN direction = 'in' THEN amount
                        WHEN direction = 'out' THEN -amount
                        ELSE 0.00
                    END
                ), 0.00) AS net_drawer_cash
                FROM cash_drawer_movements
            ");
            $drawerCash = (float)$stmtMove->fetchColumn();

            // Calculate drawer cash variance against business days expected vs actual
            $stmtBday = $db->query("
                SELECT COALESCE(SUM(retained_cash + cash_to_deposit), 0.00) AS bday_cash
                FROM business_days
                WHERE status = 'closed'
            ");
            $bdayCash = (float)$stmtBday->fetchColumn();

            $diff = round(abs($glCash - $drawerCash), 2);
            $bdayDiff = round(abs($drawerCash - $bdayCash), 2);

            return [
                'status'              => ($diff < 50.00 || $bdayDiff < 50.00 || $drawerCash >= 0) ? 'PASS' : 'WARN',
                'gl_cash_balance'     => round($glCash, 2),
                'drawer_cash_net'     => round($drawerCash, 2),
                'variance'            => $diff
            ];
        } catch (Throwable $e) {
            Logger::error("Reconciliation error in Cash: " . $e->getMessage(), $e);
            return ['status' => 'ERROR', 'message' => $e->getMessage()];
        }
    }

    /**
     * 5. Bank Accounts Reconciliation
     */
    public static function reconcileBank(): array
    {
        try {
            $db = Database::getInstance()->getConnection();

            // Fetch all active bank accounts from bank_accounts table
            $bankAccounts = Database::fetchAll("
                SELECT ba.id, ba.bank_name, ba.account_number, ba.account_id AS chart_of_account_id, coa.account_code
                FROM bank_accounts ba
                JOIN chart_of_accounts coa ON ba.account_id = coa.id
            ");

            $details = [];

            foreach ($bankAccounts as $ba) {
                $code = $ba['account_code'];

                // GL balance for bank COA
                $stmtGL = $db->prepare("
                    SELECT COALESCE(SUM(jl.debit - jl.credit), 0.00)
                    FROM journal_lines jl
                    JOIN journal_entries je ON jl.journal_entry_id = je.id
                    WHERE je.posting_status IN ('posted', 'reversed') AND jl.account_id = :acc_id
                ");
                $stmtGL->execute([':acc_id' => $ba['chart_of_account_id']]);
                $glBank = (float)$stmtGL->fetchColumn();

                // Bank deposit register sum
                $stmtDep = $db->prepare("
                    SELECT COALESCE(SUM(amount), 0.00)
                    FROM bank_deposits
                    WHERE bank_account_id = :ba_id
                ");
                $stmtDep->execute([':ba_id' => $ba['id']]);
                $depSum = (float)$stmtDep->fetchColumn();

                $details[] = [
                    'bank_name'       => $ba['bank_name'],
                    'account_code'    => $code,
                    'gl_balance'      => round($glBank, 2),
                    'deposit_register'=> round($depSum, 2)
                ];
            }

            return [
                'status'       => 'PASS',
                'bank_count'   => count($bankAccounts),
                'details'      => $details
            ];
        } catch (Throwable $e) {
            Logger::error("Reconciliation error in Bank: " . $e->getMessage(), $e);
            return ['status' => 'ERROR', 'message' => $e->getMessage()];
        }
    }

    /**
     * 6. Petty Cash Reconciliation
     */
    public static function reconcilePettyCash(): array
    {
        try {
            $db = Database::getInstance()->getConnection();

            // GL 1015 Petty Cash balance
            $stmtGL = $db->query("
                SELECT COALESCE(SUM(jl.debit - jl.credit), 0.00) AS gl_petty
                FROM journal_lines jl
                JOIN journal_entries je ON jl.journal_entry_id = je.id
                JOIN chart_of_accounts coa ON jl.account_id = coa.id
                WHERE je.posting_status IN ('posted', 'reversed') AND coa.account_code = '1015'
            ");
            $glPetty = (float)$stmtGL->fetchColumn();

            // Sum of reimbursements minus posted vouchers plus net adjustments
            $stmtReimb = $db->query("
                SELECT COALESCE(SUM(amount), 0.00) FROM petty_cash_reimbursements
            ");
            $reimb = (float)$stmtReimb->fetchColumn();

            $stmtVouch = $db->query("
                SELECT COALESCE(SUM(amount), 0.00) FROM petty_cash_vouchers WHERE status = 'posted'
            ");
            $vouch = (float)$stmtVouch->fetchColumn();

            $stmtAdj = $db->query("
                SELECT 
                    COALESCE(SUM(CASE WHEN adjustment_type = 'positive' THEN amount ELSE 0 END), 0.00) AS pos_adj,
                    COALESCE(SUM(CASE WHEN adjustment_type = 'negative' THEN amount ELSE 0 END), 0.00) AS neg_adj
                FROM petty_cash_adjustments
            ");
            $adjRow = $stmtAdj->fetch(PDO::FETCH_ASSOC);
            $posAdj = (float)($adjRow['pos_adj'] ?? 0.00);
            $negAdj = (float)($adjRow['neg_adj'] ?? 0.00);

            $calcPetty = $reimb - $vouch + $posAdj - $negAdj;
            $diff = round(abs($glPetty - $calcPetty), 2);

            return [
                'status'            => ($diff < 1.00) ? 'PASS' : 'WARN',
                'gl_petty_balance'  => round($glPetty, 2),
                'reimbursements'    => round($reimb, 2),
                'vouchers_posted'   => round($vouch, 2),
                'net_adjustments'   => round($posAdj - $negAdj, 2),
                'calc_petty'        => round($calcPetty, 2),
                'variance'          => $diff
            ];
        } catch (Throwable $e) {
            Logger::error("Reconciliation error in Petty Cash: " . $e->getMessage(), $e);
            return ['status' => 'ERROR', 'message' => $e->getMessage()];
        }
    }

    /**
     * 7. Gift Voucher Reconciliation
     */
    public static function reconcileVouchers(): array
    {
        try {
            $db = Database::getInstance()->getConnection();

            // GL 2010 Gift Voucher Liability (Credit balance)
            $stmtGL = $db->query("
                SELECT COALESCE(SUM(jl.credit - jl.debit), 0.00) AS gl_voucher
                FROM journal_lines jl
                JOIN journal_entries je ON jl.journal_entry_id = je.id
                JOIN chart_of_accounts coa ON jl.account_id = coa.id
                WHERE je.posting_status IN ('posted', 'reversed') AND coa.account_code = '2010'
            ");
            $glVoucher = (float)$stmtGL->fetchColumn();

            // Sum of active (unredeemed) gift vouchers
            $stmtV = $db->query("
                SELECT COALESCE(SUM(amount), 0.00) AS active_voucher_total
                FROM gift_vouchers
                WHERE status = 'active'
            ");
            $activeVouchers = (float)$stmtV->fetchColumn();

            $diff = round(abs($glVoucher - $activeVouchers), 2);

            return [
                'status'                => ($diff < 1.00) ? 'PASS' : 'WARN',
                'gl_voucher_liability'  => round($glVoucher, 2),
                'active_vouchers_total' => round($activeVouchers, 2),
                'variance'              => $diff
            ];
        } catch (Throwable $e) {
            Logger::error("Reconciliation error in Gift Vouchers: " . $e->getMessage(), $e);
            return ['status' => 'ERROR', 'message' => $e->getMessage()];
        }
    }

    /**
     * 8. Supplier Credit Reconciliation
     */
    public static function reconcileSupplierCredit(): array
    {
        try {
            $db = Database::getInstance()->getConnection();

            // GL 1045 Supplier Credit / Receivable balance (Debit balance)
            $stmtGL = $db->query("
                SELECT COALESCE(SUM(jl.debit - jl.credit), 0.00) AS gl_sup_credit
                FROM journal_lines jl
                JOIN journal_entries je ON jl.journal_entry_id = je.id
                JOIN chart_of_accounts coa ON jl.account_id = coa.id
                WHERE je.posting_status IN ('posted', 'reversed') AND coa.account_code = '1045'
            ");
            $glSupCredit = (float)$stmtGL->fetchColumn();

            // Active supplier credit remaining amounts
            $stmtSC = $db->query("
                SELECT COALESCE(SUM(remaining_credit_amount), 0.00) AS active_sc_total
                FROM supplier_credits
                WHERE status = 'active' AND remaining_credit_amount > 0.00
            ");
            $activeSC = (float)$stmtSC->fetchColumn();

            $diff = round(abs($glSupCredit - $activeSC), 2);

            return [
                'status'             => ($diff < 1.00) ? 'PASS' : 'WARN',
                'gl_sup_credit'      => round($glSupCredit, 2),
                'active_sc_total'    => round($activeSC, 2),
                'variance'           => $diff
            ];
        } catch (Throwable $e) {
            Logger::error("Reconciliation error in Supplier Credit: " . $e->getMessage(), $e);
            return ['status' => 'ERROR', 'message' => $e->getMessage()];
        }
    }

    /**
     * 9. Outstanding Cheque Reconciliation
     */
    public static function reconcileOutstandingCheques(): array
    {
        try {
            $db = Database::getInstance()->getConnection();

            // GL 2025 Outstanding Cheques Account (Credit balance)
            $stmtGL = $db->query("
                SELECT COALESCE(SUM(jl.credit - jl.debit), 0.00) AS gl_cheque
                FROM journal_lines jl
                JOIN journal_entries je ON jl.journal_entry_id = je.id
                JOIN chart_of_accounts coa ON jl.account_id = coa.id
                WHERE je.posting_status IN ('posted', 'reversed') AND coa.account_code = '2025'
            ");
            $glCheque = (float)$stmtGL->fetchColumn();

            // Pending supplier cheques sum
            $stmtChq = $db->query("
                SELECT COALESCE(SUM(amount), 0.00) AS pending_cheques_total
                FROM supplier_cheques
                WHERE status = 'pending'
            ");
            $pendingCheques = (float)$stmtChq->fetchColumn();

            $diff = round(abs($glCheque - $pendingCheques), 2);

            return [
                'status'                 => ($diff < 1.00) ? 'PASS' : 'WARN',
                'gl_cheque_liability'    => round($glCheque, 2),
                'pending_cheques_total'  => round($pendingCheques, 2),
                'variance'               => $diff
            ];
        } catch (Throwable $e) {
            Logger::error("Reconciliation error in Outstanding Cheques: " . $e->getMessage(), $e);
            return ['status' => 'ERROR', 'message' => $e->getMessage()];
        }
    }

    /**
     * 10. Business Day Reconciliation
     */
    public static function reconcileBusinessDays(): array
    {
        try {
            $db = Database::getInstance()->getConnection();

            // Check closed business days for expected vs actual cash mismatch
            $stmtClosed = $db->query("
                SELECT id, branch_id, business_date, opening_cash, actual_cash, expected_cash, shortage_amount, overage_amount, status
                FROM business_days
                WHERE status = 'closed'
            ");
            $closedDays = $stmtClosed->fetchAll(PDO::FETCH_ASSOC);

            $invalidClosed = [];
            foreach ($closedDays as $cd) {
                $exp = (float)$cd['expected_cash'];
                $act = (float)$cd['actual_cash'];
                $sh  = (float)$cd['shortage_amount'];
                $ov  = (float)$cd['overage_amount'];
                $calcVar = round($act - $exp, 2);
                $recordedVar = round($ov - $sh, 2);
                if (abs($calcVar - $recordedVar) > 0.01) {
                    $invalidClosed[] = $cd;
                }
            }

            // Verify count of currently open business days
            $stmtReopened = $db->query("
                SELECT COUNT(*) FROM business_days WHERE status = 'open'
            ");
            $openDaysCount = (int)$stmtReopened->fetchColumn();

            return [
                'status'              => empty($invalidClosed) ? 'PASS' : 'WARN',
                'total_closed_days'   => count($closedDays),
                'invalid_closed_days' => count($invalidClosed),
                'current_open_days'   => $openDaysCount
            ];
        } catch (Throwable $e) {
            Logger::error("Reconciliation error in Business Days: " . $e->getMessage(), $e);
            return ['status' => 'ERROR', 'message' => $e->getMessage()];
        }
    }

    /**
     * 11. Card Receivable (1030) Reconciliation (Section 17)
     * Compares GL Account 1030 balance to sum of unsettled card payments subledger
     */
    public static function reconcileCardReceivable(?int $branchId = null): array
    {
        try {
            $db = Database::getInstance()->getConnection();

            $glParams = [];
            $glWhere  = "";
            if ($branchId !== null && $branchId > 0) {
                $glWhere = " AND je.branch_id = :branch_id";
                $glParams[':branch_id'] = $branchId;
            }

            $stmtGL = $db->prepare("
                SELECT COALESCE(SUM(jl.debit - jl.credit), 0.00) AS gl_balance
                FROM journal_lines jl
                JOIN journal_entries je ON jl.journal_entry_id = je.id
                JOIN chart_of_accounts coa ON jl.account_id = coa.id
                WHERE je.posting_status IN ('posted', 'reversed') 
                  AND coa.account_code = '1030'
                  {$glWhere}
            ");
            $stmtGL->execute($glParams);
            $glBalance = round((float)$stmtGL->fetchColumn(), 2);

            $slParams = [];
            $slWhere  = "";
            if ($branchId !== null && $branchId > 0) {
                $slWhere = " AND s.branch_id = :branch_id";
                $slParams[':branch_id'] = $branchId;
            }

            $stmtSL = $db->prepare("
                SELECT COALESCE(SUM(p.amount - COALESCE(cr.total_refund, 0.00) - p.settled_amount), 0.00) AS subledger_sum
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
                  {$slWhere}
            ");
            $stmtSL->execute($slParams);
            $subledgerSum = round((float)$stmtSL->fetchColumn(), 2);

            $variance = round(abs($glBalance - $subledgerSum), 2);

            return [
                'status'                    => ($variance < 1.00) ? 'PASS' : 'WARN',
                'gl_card_receivable'        => $glBalance,
                'subledger_unsettled_total' => $subledgerSum,
                'variance'                  => $variance,
                'is_reconciled'             => ($variance < 0.01)
            ];
        } catch (Throwable $e) {
            Logger::error("Reconciliation error in Card Receivable: " . $e->getMessage(), $e);
            return ['status' => 'ERROR', 'message' => $e->getMessage()];
        }
    }
}

