<?php
/**
 * Curtiss POS - 88mm Thermal Day End Business & Reconciliation Report
 * Print-ready receipt for Thermal POS Printers (88mm width).
 * Comprehensive financial report including:
 * - Gross/Net Sales, Cost of Goods Sold (COGS), Gross Profit & Margin %
 * - Sales Breakdown by Payment Method
 * - Customer Credit Collections
 * - Supplier Payments & Advances
 * - Employee Advance Payments
 * - Petty Cash Expenses / Vouchers
 * - Sales Returns & Customer Refunds
 * - Manual Cash Adjustments
 * - Card Terminal Settlement
 * - Complete Cash In/Out Reconciliation & Physical Count Variance
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/BusinessDay.php';
require_once __DIR__ . '/core/Settings.php';

Auth::requireLogin();

$bdayId = (int)($_GET['id'] ?? $_GET['bday_id'] ?? $_GET['business_day_id'] ?? 0);

if ($bdayId <= 0) {
    die("Invalid Business Day ID.");
}

$reportData = BusinessDay::getDayComprehensiveSummary($bdayId);

if (empty($reportData['success']) || empty($reportData['business_day'])) {
    die("Business Day record not found or could not be loaded: " . ($reportData['message'] ?? ''));
}

$bday               = $reportData['business_day'];
$salesSummary       = $reportData['sales_summary'] ?? [];
$custCollections    = $reportData['customer_collections'] ?? [];
$supplierPayments   = $reportData['supplier_payments'] ?? [];
$employeeAdvances   = $reportData['employee_advances'] ?? [];
$pettyCashExpenses  = $reportData['petty_cash_expenses'] ?? [];
$generalExpenses    = $reportData['general_expenses'] ?? [];
$payrollPayments    = $reportData['payroll_payments'] ?? [];
$refunds            = $reportData['refunds'] ?? [];
$manualMovements    = $reportData['manual_movements'] ?? [];
$summary            = $reportData['drawer_summary'] ?? [];

$closingTransfers = [];
try {
    $db = Database::getInstance()->getConnection();
    if (!empty($bday['split_transfers'])) {
        $rawSplits = is_array($bday['split_transfers']) ? $bday['split_transfers'] : json_decode($bday['split_transfers'], true);
        if (!empty($rawSplits) && is_array($rawSplits)) {
            $bankMap = [];
            $bankIds = [];
            foreach ($rawSplits as $rs) {
                if (!empty($rs['bank_account_id'])) {
                    $bankIds[] = (int)$rs['bank_account_id'];
                }
            }
            if (!empty($bankIds)) {
                $inClause = implode(',', array_unique($bankIds));
                $stmtBanks = $db->query("SELECT id, bank_name, account_number FROM bank_accounts WHERE id IN ($inClause)");
                while ($b = $stmtBanks->fetch(PDO::FETCH_ASSOC)) {
                    $bankMap[$b['id']] = $b['bank_name'] . ' (' . $b['account_number'] . ')';
                }
            }
            foreach ($rawSplits as $rs) {
                $closingTransfers[] = [
                    'destination_type' => $rs['type'] ?? 'safe',
                    'amount'           => (float)($rs['amount'] ?? 0),
                    'bank_name'        => !empty($rs['bank_account_id']) ? ($bankMap[$rs['bank_account_id']] ?? 'Bank') : '',
                    'notes'            => $rs['notes'] ?? ''
                ];
            }
        }
    }
} catch (Exception $e) {
    // Graceful fallback
}

$branchInfo   = Settings::getBranchInfo((int)($bday['branch_id'] ?? 1));
$businessName = $branchInfo['company_name'];
$branchName   = $branchInfo['branch_name'];
$branchCode   = $branchInfo['branch_code'];
$denomData    = !empty($bday['cash_denominations']) ? json_decode($bday['cash_denominations'], true) : null;
$cardSettlement = (float)($bday['card_settlement_amount'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Day End Business Report #<?= (int)$bday['id'] ?> - <?= htmlspecialchars($bday['business_date']) ?></title>
    <style>
        @page { size: 88mm auto; margin: 0; }
        * { box-sizing: border-box; }
        body {
            font-family: 'Courier New', Courier, monospace, sans-serif;
            width: 80mm;
            margin: 0 auto;
            padding: 10px 6px;
            font-size: 11.5px;
            color: #000;
            background: #fff;
            line-height: 1.35;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }
        .divider { border-bottom: 1px dashed #000; margin: 6px 0; }
        .double-divider { border-bottom: 3px double #000; margin: 7px 0; }
        table { width: 100%; border-collapse: collapse; margin: 3px 0; }
        th, td { padding: 2px 0; vertical-align: top; font-size: 11px; }
        .section-title {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 1px solid #000;
            padding-bottom: 2px;
            margin-top: 8px;
            margin-bottom: 4px;
        }
        .item-detail-row {
            font-size: 10px;
            color: #222;
            padding-left: 6px;
        }
        .btn-print-box { margin-bottom: 12px; text-align: center; }
        .btn-print {
            background: #0f172a;
            color: #fff;
            border: none;
            padding: 8px 18px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 12px;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        .receipt-logo {
            max-height: 48px;
            max-width: 160px;
            object-fit: contain;
            margin-bottom: 5px;
        }
        @media print { .btn-print-box { display: none !important; } }
    </style>
</head>
<body>
    <div class="btn-print-box">
        <button class="btn-print" onclick="window.print()">🖨️ Print 88mm Day End Report</button>
    </div>

    <!-- HEADER -->
    <div class="text-center">
        <?php if (!empty($branchInfo['company_logo']) && file_exists(__DIR__ . '/' . $branchInfo['company_logo'])): ?>
            <div>
                <img src="<?= htmlspecialchars($branchInfo['company_logo']) ?>" alt="Logo" class="receipt-logo">
            </div>
        <?php endif; ?>
        <div style="font-size: 16px; font-weight: bold;"><?= htmlspecialchars($businessName) ?></div>
        <div style="font-size: 12px; font-weight: bold;"><?= htmlspecialchars($branchName) ?> (<?= htmlspecialchars($branchCode) ?>)</div>
        <?php if (!empty($branchInfo['branch_address'])): ?>
            <div style="font-size: 10.5px;"><?= htmlspecialchars($branchInfo['branch_address']) ?></div>
        <?php endif; ?>
        <div style="font-size: 10.5px;">
            <?php if (!empty($branchInfo['branch_phone'])): ?>
                <span>Tel: <?= htmlspecialchars($branchInfo['branch_phone']) ?></span>
            <?php endif; ?>
            <?php if (!empty($branchInfo['company_whatsapp'])): ?>
                <span><?= !empty($branchInfo['branch_phone']) ? ' | ' : '' ?>WA: <?= htmlspecialchars($branchInfo['company_whatsapp']) ?></span>
            <?php endif; ?>
        </div>
        <?php if (!empty($branchInfo['company_website'])): ?>
            <div style="font-size: 10px; color: #444;"><?= htmlspecialchars($branchInfo['company_website']) ?></div>
        <?php endif; ?>
        <div style="font-size: 11px; font-weight: bold; margin-top: 5px; letter-spacing: 0.04em;">DAY END BUSINESS REPORT</div>
    </div>
    <div class="double-divider"></div>

    <!-- METADATA -->
    <table>
        <tr>
            <td>Business Date:</td>
            <td class="text-right fw-bold"><?= htmlspecialchars($bday['business_date']) ?></td>
        </tr>
        <tr>
            <td>Day Record ID:</td>
            <td class="text-right">#<?= (int)$bday['id'] ?></td>
        </tr>
        <tr>
            <td>Status:</td>
            <td class="text-right fw-bold"><?= strtoupper($bday['status']) ?></td>
        </tr>
        <tr>
            <td>Opened By:</td>
            <td class="text-right"><?= htmlspecialchars($bday['opener_name'] ?? 'N/A') ?></td>
        </tr>
        <?php if (!empty($bday['opened_at'])): ?>
        <tr>
            <td>Opened Time:</td>
            <td class="text-right"><?= htmlspecialchars($bday['opened_at']) ?></td>
        </tr>
        <?php endif; ?>
        <tr>
            <td>Closed By:</td>
            <td class="text-right"><?= htmlspecialchars($bday['closer_name'] ?? 'Pending') ?></td>
        </tr>
        <?php if (!empty($bday['closed_at'])): ?>
        <tr>
            <td>Closed Time:</td>
            <td class="text-right"><?= htmlspecialchars($bday['closed_at']) ?></td>
        </tr>
        <?php endif; ?>
    </table>

    <!-- 1. DAILY SALES & PROFITABILITY (COGS) -->
    <?php 
    $totalOrders     = (int)($salesSummary['total_sales_count'] ?? 0);
    $totalItemsSold  = (float)($salesSummary['total_items_sold'] ?? 0);
    $grossSales      = (float)($salesSummary['gross_sales'] ?? 0);
    $promoDisc       = (float)($salesSummary['promo_discount'] ?? 0);
    $manualDisc      = (float)($salesSummary['manual_discount'] ?? 0);
    $totalDisc       = (float)($salesSummary['total_discount'] ?? ($promoDisc + $manualDisc));
    $netSales        = (float)($salesSummary['net_sales'] ?? 0);
    $totalCogs       = (float)($salesSummary['total_cogs'] ?? 0);
    $grossProfit     = (float)($salesSummary['gross_profit'] ?? 0);
    $grossMarginPct  = (float)($salesSummary['gross_margin_pct'] ?? 0);

    $cashSales    = (float)($salesSummary['cash_sales'] ?? 0);
    $cardSales    = (float)($salesSummary['card_sales'] ?? 0);
    $creditSales  = (float)($salesSummary['credit_sales'] ?? 0);
    $bankSales    = (float)($salesSummary['bank_sales'] ?? 0);
    ?>

    <div class="divider"></div>
    <div class="section-title">DAILY SALES & PROFITABILITY</div>
    <table>
        <tr>
            <td>Completed Invoices:</td>
            <td class="text-right fw-bold"><?= number_format($totalOrders) ?></td>
        </tr>
        <?php if ($totalItemsSold > 0): ?>
        <tr>
            <td>Total Items Sold:</td>
            <td class="text-right"><?= number_format($totalItemsSold, 2) ?> units</td>
        </tr>
        <?php endif; ?>
        <?php if ($grossSales > 0): ?>
        <tr>
            <td>Gross Sales:</td>
            <td class="text-right">LKR <?= number_format($grossSales, 2) ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($totalDisc > 0): ?>
        <tr>
            <td>- Total Discounts:</td>
            <td class="text-right">LKR <?= number_format($totalDisc, 2) ?></td>
        </tr>
        <?php if ($promoDisc > 0): ?>
        <tr>
            <td style="padding-left: 8px; font-size: 10px;">• Auto / Promo Discounts:</td>
            <td class="text-right" style="font-size: 10px;">LKR <?= number_format($promoDisc, 2) ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($manualDisc > 0): ?>
        <tr>
            <td style="padding-left: 8px; font-size: 10px;">• Manual Discounts:</td>
            <td class="text-right" style="font-size: 10px;">LKR <?= number_format($manualDisc, 2) ?></td>
        </tr>
        <?php endif; ?>
        <?php endif; ?>
        <tr class="fw-bold" style="font-size: 12px; border-top: 1px dashed #000; border-bottom: 1px dashed #000;">
            <td>NET SALES:</td>
            <td class="text-right">LKR <?= number_format($netSales, 2) ?></td>
        </tr>
        <tr>
            <td>Cost of Goods Sold (COGS):</td>
            <td class="text-right fw-bold">LKR <?= number_format($totalCogs, 2) ?></td>
        </tr>
        <tr class="fw-bold" style="font-size: 12px;">
            <td>GROSS PROFIT:</td>
            <td class="text-right"><?= $grossProfit < 0 ? '-' : '' ?>LKR <?= number_format(abs($grossProfit), 2) ?></td>
        </tr>
        <tr>
            <td>Gross Profit Margin:</td>
            <td class="text-right fw-bold"><?= number_format($grossMarginPct, 2) ?>%</td>
        </tr>
    </table>

    <!-- 2. PAYMENT METHODS BREAKDOWN -->
    <?php if ($cashSales > 0 || $cardSales > 0 || $creditSales > 0 || $bankSales > 0): ?>
    <div class="divider"></div>
    <div class="section-title">SALES REVENUE BY METHOD</div>
    <table>
        <?php if ($cashSales > 0): ?>
        <tr>
            <td>Cash Sales:</td>
            <td class="text-right">LKR <?= number_format($cashSales, 2) ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($cardSales > 0): ?>
        <tr>
            <td>Card Sales:</td>
            <td class="text-right">LKR <?= number_format($cardSales, 2) ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($creditSales > 0): ?>
        <tr>
            <td>Credit Sales (On Account):</td>
            <td class="text-right">LKR <?= number_format($creditSales, 2) ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($bankSales > 0): ?>
        <tr>
            <td>Bank / Cheque Sales:</td>
            <td class="text-right">LKR <?= number_format($bankSales, 2) ?></td>
        </tr>
        <?php endif; ?>
    </table>
    <?php endif; ?>

    <!-- 3. CUSTOMER CREDIT COLLECTIONS (INCOMING PAYMENTS) -->
    <?php 
    $totCollections = (float)($custCollections['total'] ?? 0);
    $collItems      = $custCollections['items'] ?? [];
    ?>
    <?php if ($totCollections > 0 || !empty($collItems)): ?>
    <div class="divider"></div>
    <div class="section-title">CUSTOMER CREDIT COLLECTIONS</div>
    <table>
        <tr class="fw-bold">
            <td>TOTAL COLLECTIONS IN:</td>
            <td class="text-right">+ LKR <?= number_format($totCollections, 2) ?></td>
        </tr>
        <?php foreach ($collItems as $ci): ?>
        <tr class="item-detail-row">
            <td>&bull; <?= htmlspecialchars(substr($ci['customer_name'] ?? 'Customer', 0, 22)) ?> (<?= htmlspecialchars($ci['reference_no'] ?? 'PMT') ?>)</td>
            <td class="text-right">LKR <?= number_format((float)$ci['amount_paid'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>

    <!-- 4. SUPPLIER PAYMENTS & ADVANCE PAYMENTS -->
    <?php 
    $totSupp = (float)($supplierPayments['total'] ?? 0);
    $cashSupp = (float)($supplierPayments['cash_amount'] ?? 0);
    $bankSupp = (float)($supplierPayments['bank_amount'] ?? 0);
    $suppItems = $supplierPayments['items'] ?? [];
    ?>
    <?php if ($totSupp > 0 || !empty($suppItems)): ?>
    <div class="divider"></div>
    <div class="section-title">SUPPLIER PAYMENTS & ADVANCES</div>
    <table>
        <tr class="fw-bold">
            <td>TOTAL SUPPLIER PAYMENTS:</td>
            <td class="text-right">- LKR <?= number_format($totSupp, 2) ?></td>
        </tr>
        <?php if ($cashSupp > 0): ?>
        <tr>
            <td>&bull; Paid via Cash (Drawer):</td>
            <td class="text-right">LKR <?= number_format($cashSupp, 2) ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($bankSupp > 0): ?>
        <tr>
            <td>&bull; Paid via Bank / Transfer:</td>
            <td class="text-right">LKR <?= number_format($bankSupp, 2) ?></td>
        </tr>
        <?php endif; ?>
        <?php foreach ($suppItems as $si): ?>
        <tr class="item-detail-row">
            <td>&bull; <?= htmlspecialchars(substr($si['supplier_name'] ?? 'Supplier', 0, 20)) ?> (<?= htmlspecialchars($si['payment_method']) ?>)</td>
            <td class="text-right">LKR <?= number_format((float)$si['total_amount'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>

    <!-- 5. EMPLOYEE ADVANCE PAYMENTS -->
    <?php 
    $totEmpAdv = (float)($employeeAdvances['total'] ?? 0);
    $cashEmpAdv = (float)($employeeAdvances['cash_amount'] ?? 0);
    $bankEmpAdv = (float)($employeeAdvances['bank_amount'] ?? 0);
    $empItems  = $employeeAdvances['items'] ?? [];
    ?>
    <?php if ($totEmpAdv > 0 || !empty($empItems)): ?>
    <div class="divider"></div>
    <div class="section-title">EMPLOYEE ADVANCE PAYMENTS</div>
    <table>
        <tr class="fw-bold">
            <td>TOTAL EMPLOYEE ADVANCES:</td>
            <td class="text-right">- LKR <?= number_format($totEmpAdv, 2) ?></td>
        </tr>
        <?php if ($cashEmpAdv > 0): ?>
        <tr>
            <td>&bull; Disbursed in Cash:</td>
            <td class="text-right">LKR <?= number_format($cashEmpAdv, 2) ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($bankEmpAdv > 0): ?>
        <tr>
            <td>&bull; Disbursed via Bank:</td>
            <td class="text-right">LKR <?= number_format($bankEmpAdv, 2) ?></td>
        </tr>
        <?php endif; ?>
        <?php foreach ($empItems as $ei): ?>
        <tr class="item-detail-row">
            <td>&bull; <?= htmlspecialchars(substr($ei['employee_name'] ?? 'Employee', 0, 22)) ?> (<?= htmlspecialchars($ei['advance_no'] ?? 'ADV') ?> &bull; <?= htmlspecialchars($ei['payment_method']) ?>)</td>
            <td class="text-right">LKR <?= number_format((float)$ei['amount'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>

    <!-- 6. PETTY CASH VOUCHERS / EXPENSES -->
    <?php 
    $totPetty = (float)($pettyCashExpenses['total'] ?? 0);
    $pettyItems = $pettyCashExpenses['items'] ?? [];
    ?>
    <?php if ($totPetty > 0 || !empty($pettyItems)): ?>
    <div class="divider"></div>
    <div class="section-title">PETTY CASH / OPERATING EXPENSES</div>
    <table>
        <tr class="fw-bold">
            <td>TOTAL PETTY CASH EXPENSES:</td>
            <td class="text-right">- LKR <?= number_format($totPetty, 2) ?></td>
        </tr>
        <?php foreach ($pettyItems as $pi): ?>
        <tr class="item-detail-row">
            <td>&bull; <?= htmlspecialchars(substr(($pi['category_name'] . (!empty($pi['payee']) ? ': ' . $pi['payee'] : '')), 0, 24)) ?> (<?= htmlspecialchars($pi['voucher_no']) ?>)</td>
            <td class="text-right">LKR <?= number_format((float)$pi['amount'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>

    <!-- 6b. CENTRALIZED BUSINESS EXPENSES -->
    <?php 
    $totGenExp = (float)($generalExpenses['total'] ?? 0);
    $genExpItems = $generalExpenses['items'] ?? [];
    $genExpDrawer = (float)($generalExpenses['cash_drawer'] ?? 0);
    $genExpBank = (float)($generalExpenses['bank'] ?? 0);
    $genExpPetty = (float)($generalExpenses['petty_cash'] ?? 0);
    $genExpSafe = (float)($generalExpenses['cash_in_hand'] ?? 0);
    $genExpUnpaid = (float)($generalExpenses['unpaid'] ?? 0);
    $genExpCats = $generalExpenses['category_breakdown'] ?? [];
    ?>
    <?php if ($totGenExp > 0 || !empty($genExpItems)): ?>
    <div class="divider"></div>
    <div class="section-title">CENTRALIZED BUSINESS EXPENSES</div>
    <table>
        <tr class="fw-bold">
            <td>TOTAL EXPENSES RECORDED:</td>
            <td class="text-right">- LKR <?= number_format($totGenExp, 2) ?></td>
        </tr>
        <?php if ($genExpDrawer > 0): ?>
        <tr>
            <td>&bull; Paid from Cash Drawer:</td>
            <td class="text-right">LKR <?= number_format($genExpDrawer, 2) ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($genExpBank > 0): ?>
        <tr>
            <td>&bull; Paid via Bank Account:</td>
            <td class="text-right">LKR <?= number_format($genExpBank, 2) ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($genExpSafe > 0): ?>
        <tr>
            <td>&bull; Paid from Main Safe:</td>
            <td class="text-right">LKR <?= number_format($genExpSafe, 2) ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($genExpPetty > 0): ?>
        <tr>
            <td>&bull; Paid from Petty Cash:</td>
            <td class="text-right">LKR <?= number_format($genExpPetty, 2) ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($genExpUnpaid > 0): ?>
        <tr>
            <td>&bull; Accrued / Unpaid Bills:</td>
            <td class="text-right">LKR <?= number_format($genExpUnpaid, 2) ?></td>
        </tr>
        <?php endif; ?>
        <?php if (!empty($genExpCats)): ?>
        <tr class="fw-bold" style="border-top: 1px dashed #000;">
            <td colspan="2">Category Breakdown:</td>
        </tr>
        <?php foreach ($genExpCats as $cName => $cAmt): ?>
        <tr class="item-detail-row">
            <td>&nbsp;&nbsp;&bull; <?= htmlspecialchars(substr($cName, 0, 24)) ?>:</td>
            <td class="text-right">LKR <?= number_format((float)$cAmt, 2) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
    </table>
    <?php endif; ?>

    <!-- 7. PAYROLL SALARY DISBURSEMENTS -->
    <?php 
    $totPayroll = (float)($payrollPayments['total'] ?? 0);
    $cashPayroll = (float)($payrollPayments['cash_amount'] ?? 0);
    $bankPayroll = (float)($payrollPayments['bank_amount'] ?? 0);
    $payrollItems = $payrollPayments['items'] ?? [];
    ?>
    <?php if ($totPayroll > 0 || !empty($payrollItems)): ?>
    <div class="divider"></div>
    <div class="section-title">PAYROLL SALARY DISBURSEMENTS</div>
    <table>
        <tr class="fw-bold">
            <td>TOTAL PAYROLL SALARIES:</td>
            <td class="text-right">- LKR <?= number_format($totPayroll, 2) ?></td>
        </tr>
        <?php if ($cashPayroll > 0): ?>
        <tr>
            <td>&bull; Paid from Cash Drawer:</td>
            <td class="text-right">LKR <?= number_format($cashPayroll, 2) ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($bankPayroll > 0): ?>
        <tr>
            <td>&bull; Paid via Bank Transfer:</td>
            <td class="text-right">LKR <?= number_format($bankPayroll, 2) ?></td>
        </tr>
        <?php endif; ?>
        <?php foreach ($payrollItems as $ppi): ?>
        <tr class="item-detail-row">
            <td>&bull; <?= htmlspecialchars(substr($ppi['employee_name'] ?? 'Employee', 0, 18)) ?> (<?= htmlspecialchars($ppi['period_name'] ?? 'Salary') ?> &bull; <?= htmlspecialchars($ppi['payment_method'] ?? 'Cash') ?>)</td>
            <td class="text-right">LKR <?= number_format((float)$ppi['amount'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>

    <!-- 7. SALES RETURNS & CUSTOMER REFUNDS -->
    <?php 
    $totRefunds  = (float)($refunds['total'] ?? 0);
    $cashRefunds = (float)($refunds['cash_amount'] ?? 0);
    $refundItems = $refunds['items'] ?? [];
    ?>
    <?php if ($totRefunds > 0 || !empty($refundItems)): ?>
    <div class="divider"></div>
    <div class="section-title">SALES RETURNS & CUSTOMER REFUNDS</div>
    <table>
        <tr class="fw-bold">
            <td>TOTAL REFUNDS PROCESSED:</td>
            <td class="text-right">- LKR <?= number_format($totRefunds, 2) ?></td>
        </tr>
        <?php if ($cashRefunds > 0): ?>
        <tr>
            <td>&bull; Cash Refunds (from Drawer):</td>
            <td class="text-right">LKR <?= number_format($cashRefunds, 2) ?></td>
        </tr>
        <?php endif; ?>
        <?php foreach ($refundItems as $ri): ?>
        <tr class="item-detail-row">
            <td>&bull; <?= htmlspecialchars(substr($ri['customer_name'] ?? 'Refund', 0, 20)) ?> (<?= htmlspecialchars($ri['refund_method']) ?>)</td>
            <td class="text-right">LKR <?= number_format((float)$ri['amount'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>

    <!-- 8. MANUAL CASH MOVEMENTS -->
    <?php 
    $manIn = (float)($manualMovements['total_in'] ?? 0);
    $manOut = (float)($manualMovements['total_out'] ?? 0);
    $manItems = $manualMovements['items'] ?? [];
    ?>
    <?php if ($manIn > 0 || $manOut > 0 || !empty($manItems)): ?>
    <div class="divider"></div>
    <div class="section-title">MANUAL CASH ADJUSTMENTS</div>
    <table>
        <?php if ($manIn > 0): ?>
        <tr class="fw-bold">
            <td>+ Total Manual Cash In:</td>
            <td class="text-right">LKR <?= number_format($manIn, 2) ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($manOut > 0): ?>
        <tr class="fw-bold">
            <td>- Total Manual Cash Out:</td>
            <td class="text-right">LKR <?= number_format($manOut, 2) ?></td>
        </tr>
        <?php endif; ?>
        <?php foreach ($manItems as $mi): ?>
        <tr class="item-detail-row">
            <td>&bull; <?= $mi['movement_type'] === 'cash_in' ? '[+]' : '[-]' ?> <?= htmlspecialchars(substr($mi['reason'] ?: 'Manual Adjustment', 0, 20)) ?></td>
            <td class="text-right">LKR <?= number_format((float)$mi['amount'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>

    <!-- 9. CARD TERMINAL SETTLEMENT -->
    <?php if ($cardSales > 0 || $cardSettlement > 0): ?>
    <div class="divider"></div>
    <div class="section-title">CARD TERMINAL SETTLEMENT</div>
    <table>
        <?php if ($cardSales > 0): ?>
        <tr>
            <td>Expected POS Card Sales:</td>
            <td class="text-right">LKR <?= number_format($cardSales, 2) ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($cardSettlement > 0): ?>
        <tr class="fw-bold">
            <td>Terminal Settled Amount:</td>
            <td class="text-right">LKR <?= number_format($cardSettlement, 2) ?></td>
        </tr>
        <?php 
        $cardDiff = round($cardSettlement - $cardSales, 2);
        if (abs($cardDiff) > 0.001): 
        ?>
        <tr class="fw-bold">
            <td>Card Settlement Diff:</td>
            <td class="text-right"><?= $cardDiff > 0 ? '+' : '' ?>LKR <?= number_format($cardDiff, 2) ?></td>
        </tr>
        <?php endif; ?>
        <?php endif; ?>
    </table>
    <?php endif; ?>

    <!-- 10. CASH DRAWER RECONCILIATION SUMMARY -->
    <div class="divider"></div>
    <div class="section-title">CASH RECONCILIATION (ALL CASH IN/OUT)</div>
    <table>
        <?php if (($summary['opening_cash'] ?? 0) > 0): ?>
        <tr>
            <td>Initial Opening Float:</td>
            <td class="text-right">LKR <?= number_format((float)$summary['opening_cash'], 2) ?></td>
        </tr>
        <?php endif; ?>

        <?php if (($summary['pos_cash_sales'] ?? 0) > 0): ?>
        <tr>
            <td>+ POS Cash Sales:</td>
            <td class="text-right">LKR <?= number_format((float)$summary['pos_cash_sales'], 2) ?></td>
        </tr>
        <?php endif; ?>

        <?php if (($summary['customer_cash_collections'] ?? 0) > 0): ?>
        <tr>
            <td>+ Customer Credit Collections:</td>
            <td class="text-right">LKR <?= number_format((float)$summary['customer_cash_collections'], 2) ?></td>
        </tr>
        <?php endif; ?>

        <?php if (($summary['supplier_cash_refunds'] ?? 0) > 0): ?>
        <tr>
            <td>+ Supplier Cash Refunds:</td>
            <td class="text-right">LKR <?= number_format((float)$summary['supplier_cash_refunds'], 2) ?></td>
        </tr>
        <?php endif; ?>

        <?php if (($summary['manual_cash_in'] ?? 0) > 0): ?>
        <tr>
            <td>+ Manual Cash In:</td>
            <td class="text-right">LKR <?= number_format((float)$summary['manual_cash_in'], 2) ?></td>
        </tr>
        <?php endif; ?>

        <?php if (($summary['overage_adjustments'] ?? 0) > 0): ?>
        <tr>
            <td>+ Overage Adjustments:</td>
            <td class="text-right">LKR <?= number_format((float)$summary['overage_adjustments'], 2) ?></td>
        </tr>
        <?php endif; ?>

        <tr class="fw-bold" style="border-top: 1px dashed #000; border-bottom: 1px dashed #000;">
            <td>TOTAL CASH INFLOWS:</td>
            <td class="text-right">LKR <?= number_format((float)($summary['total_cash_in'] ?? 0), 2) ?></td>
        </tr>

        <?php if (($summary['supplier_cash_payments'] ?? 0) > 0): ?>
        <tr>
            <td>- Supplier Cash Payments:</td>
            <td class="text-right">LKR <?= number_format((float)$summary['supplier_cash_payments'], 2) ?></td>
        </tr>
        <?php endif; ?>

        <?php if (($summary['employee_advances'] ?? 0) > 0): ?>
        <tr>
            <td>- Employee Advance Payments:</td>
            <td class="text-right">LKR <?= number_format((float)$summary['employee_advances'], 2) ?></td>
        </tr>
        <?php endif; ?>

        <?php if (($summary['salary_payments'] ?? 0) > 0): ?>
        <tr>
            <td>- Payroll Salary Disbursements:</td>
            <td class="text-right">LKR <?= number_format((float)$summary['salary_payments'], 2) ?></td>
        </tr>
        <?php endif; ?>

        <?php if (($summary['petty_cash_reimbursements'] ?? 0) > 0): ?>
        <tr>
            <td>- Petty Cash Reimbursements:</td>
            <td class="text-right">LKR <?= number_format((float)$summary['petty_cash_reimbursements'], 2) ?></td>
        </tr>
        <?php endif; ?>

        <?php if (($summary['cash_refunds'] ?? 0) > 0): ?>
        <tr>
            <td>- Customer Cash Refunds:</td>
            <td class="text-right">LKR <?= number_format((float)$summary['cash_refunds'], 2) ?></td>
        </tr>
        <?php endif; ?>

        <?php if (($summary['drawer_expenses'] ?? 0) > 0): ?>
        <tr>
            <td>- Cash Drawer Expenses:</td>
            <td class="text-right">LKR <?= number_format((float)$summary['drawer_expenses'], 2) ?></td>
        </tr>
        <?php endif; ?>

        <?php if (($summary['manual_cash_out'] ?? 0) > 0): ?>
        <tr>
            <td>- Manual Cash Out / Sundry:</td>
            <td class="text-right">LKR <?= number_format((float)$summary['manual_cash_out'], 2) ?></td>
        </tr>
        <?php endif; ?>

        <?php if (($summary['shortage_adjustments'] ?? 0) > 0): ?>
        <tr>
            <td>- Shortage Adjustments:</td>
            <td class="text-right">LKR <?= number_format((float)$summary['shortage_adjustments'], 2) ?></td>
        </tr>
        <?php endif; ?>

        <?php if (($summary['total_cash_out'] ?? 0) > 0): ?>
        <tr class="fw-bold" style="border-bottom: 1px dashed #000;">
            <td>TOTAL CASH OUTFLOWS:</td>
            <td class="text-right">LKR <?= number_format((float)$summary['total_cash_out'], 2) ?></td>
        </tr>
        <?php endif; ?>

        <tr class="fw-bold" style="font-size: 12px;">
            <td>EXPECTED CASH IN DRAWER:</td>
            <td class="text-right">LKR <?= number_format((float)($summary['expected_cash'] ?? 0), 2) ?></td>
        </tr>

        <?php if ($bday['actual_cash'] !== null): ?>
        <tr class="fw-bold" style="font-size: 12px; border-top: 1px dashed #000;">
            <td>ACTUAL COUNTED CASH:</td>
            <td class="text-right">LKR <?= number_format((float)$bday['actual_cash'], 2) ?></td>
        </tr>
        <?php endif; ?>

        <?php 
        $shortage = (float)($bday['shortage_amount'] ?? 0);
        $overage  = (float)($bday['overage_amount'] ?? 0);
        ?>

        <?php if ($shortage > 0): ?>
        <tr class="fw-bold">
            <td>CASH SHORTAGE VARIANCE:</td>
            <td class="text-right">- LKR <?= number_format($shortage, 2) ?></td>
        </tr>
        <?php elseif ($overage > 0): ?>
        <tr class="fw-bold">
            <td>CASH OVERAGE VARIANCE:</td>
            <td class="text-right">+ LKR <?= number_format($overage, 2) ?></td>
        </tr>
        <?php elseif ($bday['actual_cash'] !== null): ?>
        <tr>
            <td>Cash Variance:</td>
            <td class="text-right">Exact (0.00)</td>
        </tr>
        <?php endif; ?>

        <?php if (($bday['retained_cash'] ?? 0) > 0): ?>
        <tr>
            <td>Retained Float Next Day:</td>
            <td class="text-right">LKR <?= number_format((float)$bday['retained_cash'], 2) ?></td>
        </tr>
        <?php endif; ?>

        <?php if (($bday['cash_to_deposit'] ?? 0) > 0): ?>
        <tr class="fw-bold" style="font-size: 12px; border-top: 1px dashed #000; border-bottom: 1px dashed #000;">
            <td>CASH TO DEPOSIT / TRANSFERS:</td>
            <td class="text-right">LKR <?= number_format((float)$bday['cash_to_deposit'], 2) ?></td>
        </tr>
        <?php if (!empty($closingTransfers)): ?>
        <?php foreach ($closingTransfers as $ct): ?>
        <tr class="item-detail-row">
            <td>&bull; <?= htmlspecialchars($ct['destination_type'] === 'safe' ? 'Main Safe (1010)' : ($ct['destination_type'] === 'bank' ? ('Direct Bank (' . ($ct['bank_name'] ?: 'Bank') . ')') : 'Petty Cash (1015)')) ?>:</td>
            <td class="text-right">LKR <?= number_format((float)$ct['amount'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
        <?php endif; ?>
    </table>

    <!-- 11. CASH DENOMINATION COUNT (Hiding 0 count lines) -->
    <?php 
    $hasDenom = false;
    if (!empty($denomData) && is_array($denomData)) {
        foreach ($denomData as $k => $v) {
            if ((float)$v > 0) { $hasDenom = true; break; }
        }
    }
    ?>

    <?php if ($hasDenom): ?>
    <div class="divider"></div>
    <div class="section-title">PHYSICAL CASH DENOMINATIONS</div>
    <table>
        <?php 
        $notesList = [5000, 2000, 1000, 500, 100, 50, 20];
        $totalNotesCount = 0;
        foreach ($notesList as $note) {
            $cnt = (int)($denomData[$note] ?? 0);
            if ($cnt > 0) {
                $totalNotesCount += $cnt;
                $amt = $note * $cnt;
                echo "<tr><td>Rs. " . number_format($note) . " x {$cnt} notes</td><td class=\"text-right\">LKR " . number_format($amt, 2) . "</td></tr>";
            }
        }
        if ($totalNotesCount > 0) {
            echo "<tr class=\"fw-bold\" style=\"border-top: 1px dashed #000;\"><td>Total Notes Count:</td><td class=\"text-right\">{$totalNotesCount} notes</td></tr>";
        }
        $coins = (float)($denomData['coins'] ?? 0);
        if ($coins > 0) {
            echo "<tr><td>Coins Total:</td><td class=\"text-right\">LKR " . number_format($coins, 2) . "</td></tr>";
        }
        ?>
    </table>
    <?php endif; ?>

    <?php if (!empty($bday['notes'])): ?>
    <div class="divider"></div>
    <div>
        <strong>Notes:</strong> <?= nl2br(htmlspecialchars($bday['notes'])) ?>
    </div>
    <?php endif; ?>

    <div class="double-divider"></div>
    <div style="margin-top: 35px; text-align: center;">
        <table style="width: 100%;">
            <tr>
                <td style="width: 48%; border-top: 1px dashed #000; text-align: center; padding-top: 4px; font-size: 10px;">Cashier Signature</td>
                <td style="width: 4%;"></td>
                <td style="width: 48%; border-top: 1px dashed #000; text-align: center; padding-top: 4px; font-size: 10px;">Manager Signature</td>
            </tr>
        </table>
    </div>

    <script>
        // Auto print trigger if requested
        if (new URLSearchParams(window.location.search).get('autoprint') === '1') {
            window.addEventListener('load', () => {
                setTimeout(() => window.print(), 300);
            });
        }
    </script>
</body>
</html>
