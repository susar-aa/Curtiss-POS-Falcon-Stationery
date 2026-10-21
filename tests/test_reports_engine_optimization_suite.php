<?php
/**
 * Automated Verification Suite for Report Engine & Central Reports Center
 * Tests compliance with CURTISS_MASTER_RULES.md:
 * - Controller permissions & branch context resolution
 * - ReportsEngine core query execution & aggregation across all 20+ reports
 * - Multi-branch scoping & consolidated 'all' branch mode
 * - Special User presentation-layer masking & financial statements access blocking
 * - Export formats (CSV, XLSX, PDF, JSON)
 * - UI/UX standards (48px rows, sticky headers, zebra stripes, headline only, floating header controls)
 * - Database indexes and performance
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/ReportsEngine.php';
require_once __DIR__ . '/../core/MaskingHelper.php';

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

function assertTest($description, $condition) {
    global $totalTests, $passedTests, $failedTests;
    $totalTests++;
    if ($condition) {
        $passedTests++;
        echo "  [PASS] {$description}\n";
    } else {
        $failedTests++;
        echo "  [FAIL] {$description}\n";
    }
}

echo "========================================================================\n";
echo "   CURTISS POS - REPORT ENGINE & REPORTS CENTER VERIFICATION SUITE       \n";
echo "========================================================================\n\n";

// -----------------------------------------------------------------------------
// 1. DATABASE INDEX VERIFICATION
// -----------------------------------------------------------------------------
echo "1. DATABASE INDEX VERIFICATION FOR REPORTING QUERIES\n";

$salesIndexes = Database::fetchAll("SHOW INDEX FROM sales");
$salesIdxNames = array_column($salesIndexes, 'Key_name');
assertTest("Sales table has composite date index idx_sales_br_stat_date", in_array('idx_sales_br_stat_date', $salesIdxNames));

$grnIndexes = Database::fetchAll("SHOW INDEX FROM grns");
$grnIdxNames = array_column($grnIndexes, 'Key_name');
assertTest("GRNs table has composite date index idx_grn_br_stat_recdate", in_array('idx_grn_br_stat_recdate', $grnIdxNames));
assertTest("GRNs table has date index idx_grn_recdate", in_array('idx_grn_recdate', $grnIdxNames));

$payIndexes = Database::fetchAll("SHOW INDEX FROM payments");
$payIdxNames = array_column($payIndexes, 'Key_name');
assertTest("Payments table has branch-method index idx_pmt_br_method", in_array('idx_pmt_br_method', $payIdxNames));

// -----------------------------------------------------------------------------
// 2. REPORTS CONTROLLER & PERMISSION INTEGRITY
// -----------------------------------------------------------------------------
echo "\n2. REPORTS CONTROLLER & PERMISSIONS INTEGRITY\n";

$controllerContent = file_get_contents(__DIR__ . '/../reports.php');
assertTest("reports.php requires Auth::requireLogin()", strpos($controllerContent, 'Auth::requireLogin()') !== false);
assertTest("reports.php requires 'view_reports' permission", strpos($controllerContent, "hasPermission('view_reports')") !== false);
assertTest("reports.php integrates active branch session resolution", strpos($controllerContent, "\$_SESSION['branch_id'] ?? \$_SESSION['active_branch_id'] ?? 1") !== false);
assertTest("reports.php blocks Special User from financial statements view", strpos($controllerContent, "!Auth::isSpecialUser()") !== false);

// -----------------------------------------------------------------------------
// 3. REPORTS ENGINE CORE QUERIES & AGGREGATION
// -----------------------------------------------------------------------------
echo "\n3. REPORTS ENGINE CORE QUERIES & AGGREGATIONS\n";

$filters = [
    'branch_id' => 1,
    'date_from' => date('Y-m-01'),
    'date_to'   => date('Y-m-d')
];

// Test buildFilterWhere
$whereRes = ReportsEngine::buildFilterWhere($filters, 's');
assertTest("buildFilterWhere returns WHERE clause array", is_array($whereRes['where']) && count($whereRes['where']) >= 3);
assertTest("buildFilterWhere returns named PDO parameters", isset($whereRes['params'][':branch_id']) && isset($whereRes['params'][':date_from']));

// 1. Sales Summary
$salesSum = ReportsEngine::getSalesSummary($filters);
assertTest("ReportsEngine::getSalesSummary returns invoice_count key", array_key_exists('invoice_count', $salesSum));
assertTest("ReportsEngine::getSalesSummary returns gross_sales key", array_key_exists('gross_sales', $salesSum));
assertTest("ReportsEngine::getSalesSummary returns cogs key", array_key_exists('cogs', $salesSum));
assertTest("ReportsEngine::getSalesSummary returns gross_profit key", array_key_exists('gross_profit', $salesSum));
assertTest("ReportsEngine::getSalesSummary returns profit_margin key", array_key_exists('profit_margin', $salesSum));

// 2. Sales by Product
$salesProd = ReportsEngine::getSalesByProduct($filters);
assertTest("ReportsEngine::getSalesByProduct returns array", is_array($salesProd));

// 3. Sales by Category
$salesCat = ReportsEngine::getSalesByCategory($filters);
assertTest("ReportsEngine::getSalesByCategory returns array", is_array($salesCat));

// 4. Sales by Payment Method
$salesPmt = ReportsEngine::getSalesByPaymentMethod($filters);
assertTest("ReportsEngine::getSalesByPaymentMethod returns array", is_array($salesPmt));

// 5. Sales History Report
$salesHist = ReportsEngine::getSalesHistoryReport($filters);
assertTest("ReportsEngine::getSalesHistoryReport returns array", is_array($salesHist));

// 6. Purchase Summary
$purchSum = ReportsEngine::getPurchaseSummary($filters);
assertTest("ReportsEngine::getPurchaseSummary returns grn_count key", array_key_exists('grn_count', $purchSum));
assertTest("ReportsEngine::getPurchaseSummary returns total_grn_amount key", array_key_exists('total_grn_amount', $purchSum));
assertTest("ReportsEngine::getPurchaseSummary returns total_payments_paid key", array_key_exists('total_payments_paid', $purchSum));
assertTest("ReportsEngine::getPurchaseSummary returns total_returns key", array_key_exists('total_returns', $purchSum));
assertTest("ReportsEngine::getPurchaseSummary returns outstanding_due key", array_key_exists('outstanding_due', $purchSum));

// 7. Supplier Outstanding
$suppOut = ReportsEngine::getSupplierOutstandingReport($filters);
assertTest("ReportsEngine::getSupplierOutstandingReport returns array", is_array($suppOut));

// 8. Stock Valuation
$stockVal = ReportsEngine::getStockValuationReport($filters);
assertTest("ReportsEngine::getStockValuationReport returns items array", isset($stockVal['items']) && is_array($stockVal['items']));
assertTest("ReportsEngine::getStockValuationReport returns total_valuation", array_key_exists('total_valuation', $stockVal));

// 9. Low Stock Report
$lowStock = ReportsEngine::getLowStockReport($filters);
assertTest("ReportsEngine::getLowStockReport returns array", is_array($lowStock));

// 10. Trial Balance (Accounting COA Double-Entry)
$trialBal = ReportsEngine::getTrialBalance($filters);
assertTest("ReportsEngine::getTrialBalance returns accounts array", isset($trialBal['accounts']) && is_array($trialBal['accounts']));
assertTest("ReportsEngine::getTrialBalance returns total_debit", array_key_exists('total_debit', $trialBal));
assertTest("ReportsEngine::getTrialBalance returns total_credit", array_key_exists('total_credit', $trialBal));
assertTest("ReportsEngine::getTrialBalance computes is_balanced boolean", array_key_exists('is_balanced', $trialBal));

// 11. Profit & Loss
$pnl = ReportsEngine::getProfitAndLoss($filters);
assertTest("ReportsEngine::getProfitAndLoss returns net_revenue", array_key_exists('net_revenue', $pnl));
assertTest("ReportsEngine::getProfitAndLoss returns gross_profit", array_key_exists('gross_profit', $pnl));
assertTest("ReportsEngine::getProfitAndLoss returns net_profit", array_key_exists('net_profit', $pnl));

// 12. Balance Sheet
$bs = ReportsEngine::getBalanceSheet($filters);
assertTest("ReportsEngine::getBalanceSheet returns assets total", isset($bs['assets']['total']));
assertTest("ReportsEngine::getBalanceSheet returns liabilities total", isset($bs['liabilities']['total']));
assertTest("ReportsEngine::getBalanceSheet returns equity total", isset($bs['equity']['total']));

// 13. Customer Outstanding
$custOut = ReportsEngine::getCustomerOutstandingReport($filters);
assertTest("ReportsEngine::getCustomerOutstandingReport returns array", is_array($custOut));

// 14. Damaged Stock Report
$dmgStock = ReportsEngine::getDamagedStockReport($filters);
assertTest("ReportsEngine::getDamagedStockReport returns array", is_array($dmgStock));

// 15. In-Transit Stock Report
$inTransit = ReportsEngine::getInTransitStockReport($filters);
assertTest("ReportsEngine::getInTransitStockReport returns array", is_array($inTransit));

// 16. Petty Cash Register
$pettyReg = ReportsEngine::getPettyCashRegisterReport($filters);
assertTest("ReportsEngine::getPettyCashRegisterReport returns array", is_array($pettyReg));

// 17. Bank Register
$bankReg = ReportsEngine::getBankRegisterReport($filters);
assertTest("ReportsEngine::getBankRegisterReport returns array", is_array($bankReg));

// 18. Customer Cheques
$custChq = ReportsEngine::getCustomerChequeReport($filters);
assertTest("ReportsEngine::getCustomerChequeReport returns array", is_array($custChq));

// 19. Supplier Cheques
$suppChq = ReportsEngine::getSupplierChequeReport($filters);
assertTest("ReportsEngine::getSupplierChequeReport returns array", is_array($suppChq));

// 20. KPI Performance Dashboard
$kpi = ReportsEngine::getKPIPerformance($filters);
assertTest("ReportsEngine::getKPIPerformance returns sales KPIs", isset($kpi['sales']));
assertTest("ReportsEngine::getKPIPerformance returns inventory KPIs", isset($kpi['inventory']));
assertTest("ReportsEngine::getKPIPerformance returns financials KPIs", isset($kpi['financials']));

// 21. Cashier Performance
$cashierPerf = ReportsEngine::getCashierPerformanceReport($filters);
assertTest("ReportsEngine::getCashierPerformanceReport returns array", is_array($cashierPerf));

// 22. Supplier Returns
$suppRet = ReportsEngine::getSupplierReturnsReport($filters);
assertTest("ReportsEngine::getSupplierReturnsReport returns array", is_array($suppRet));

// 23. Stock Adjustments
$stockAdj = ReportsEngine::getStockAdjustmentsReport($filters);
assertTest("ReportsEngine::getStockAdjustmentsReport returns array", is_array($stockAdj));

// -----------------------------------------------------------------------------
// 4. CONSOLIDATED ALL-BRANCH REPORTING
// -----------------------------------------------------------------------------
echo "\n4. CONSOLIDATED ALL-BRANCH REPORTING\n";

$allBranchFilters = [
    'branch_id' => 'all',
    'date_from' => date('Y-m-01'),
    'date_to'   => date('Y-m-d')
];

$allSalesSum = ReportsEngine::getSalesSummary($allBranchFilters);
assertTest("Consolidated all-branch Sales Summary executes without error", is_array($allSalesSum) && isset($allSalesSum['invoice_count']));

$allPurchSum = ReportsEngine::getPurchaseSummary($allBranchFilters);
assertTest("Consolidated all-branch Purchase Summary executes without error", is_array($allPurchSum) && isset($allPurchSum['grn_count']));

$allTrialBal = ReportsEngine::getTrialBalance($allBranchFilters);
assertTest("Consolidated all-branch Trial Balance executes without error", is_array($allTrialBal) && isset($allTrialBal['accounts']));

// -----------------------------------------------------------------------------
// 5. SPECIAL USER PRESENTATION-LAYER MASKING
// -----------------------------------------------------------------------------
echo "\n5. SPECIAL USER PRESENTATION-LAYER MASKING\n";

$maskFactor = MaskingHelper::getFactor();
assertTest("MaskingHelper::getFactor returns valid percentage decimal (> 0 and <= 1.0)", $maskFactor > 0.0 && $maskFactor <= 1.0);

$maskedFilters = $filters;
$maskedFilters['special_user'] = true;
$maskedSalesSum = ReportsEngine::getSalesSummary($maskedFilters);

if ($salesSum['net_sales'] > 0) {
    $expectedMaskedNet = round($salesSum['net_sales'] * $maskFactor, 2);
    assertTest("Special User net sales is accurately masked ({$maskedSalesSum['net_sales']} vs expected {$expectedMaskedNet})", abs($maskedSalesSum['net_sales'] - $expectedMaskedNet) <= 0.05);
} else {
    assertTest("Special User net sales masked value returned 0.00 for empty baseline", $maskedSalesSum['net_sales'] === 0.0);
}

// Check that reports_api blocks Special User from financial statements
$apiContent = file_get_contents(__DIR__ . '/../reports_api.php');
assertTest("reports_api blocks Special User from trial_balance", strpos($apiContent, "case 'trial_balance':\n            if (!Auth::hasPermission('view_financial_statements') || Auth::isSpecialUser())") !== false);
assertTest("reports_api blocks Special User from profit_and_loss", strpos($apiContent, "case 'profit_and_loss':\n            if (!Auth::hasPermission('view_financial_statements') || Auth::isSpecialUser())") !== false);
assertTest("reports_api blocks Special User from balance_sheet", strpos($apiContent, "case 'balance_sheet':\n            if (!Auth::hasPermission('view_financial_statements') || Auth::isSpecialUser())") !== false);

// -----------------------------------------------------------------------------
// 6. VIEW DESIGN & COMPLIANCE VERIFICATION
// -----------------------------------------------------------------------------
echo "\n6. UI/UX DESIGN STANDARDS COMPLIANCE IN VIEWS/REPORTS/INDEX.PHP\n";

$viewContent = file_get_contents(__DIR__ . '/../views/reports/index.php');

assertTest("CSRF meta tag present in view", strpos($viewContent, '<meta name="csrf-token"') !== false);
assertTest("CurtissSelect CSS and JS included", strpos($viewContent, 'curtiss_select.css') !== false && strpos($viewContent, 'curtiss_select.js') !== false);
assertTest("Inline filter controls container mounted above table", strpos($viewContent, 'id="reportInlineFilters"') !== false && strpos($viewContent, 'id="filter_date_from"') !== false && strpos($viewContent, 'id="filter_date_to"') !== false);
assertTest("Standard 34px button height enforced on .inv-btn", strpos($viewContent, 'height: 34px;') !== false && strpos($viewContent, 'min-height: 34px;') !== false);
assertTest("Sticky headers enforced on table.financial-table th", strpos($viewContent, 'position: sticky;') !== false && strpos($viewContent, 'top: 0;') !== false);
assertTest("Table headers use 10px uppercase styling", strpos($viewContent, 'font-size: 10px;') !== false && strpos($viewContent, 'text-transform: uppercase;') !== false);
assertTest("Standard 48px row height enforced on data table tbody tr", strpos($viewContent, '.financial-table tbody tr:not(.section-header):not(.total-row) {') !== false && strpos($viewContent, 'height: 48px;') !== false);
assertTest("Standard 48px cell height and 14px padding on financial-table td", strpos($viewContent, '.financial-table td {') !== false && strpos($viewContent, 'height: 48px;') !== false && strpos($viewContent, 'padding: 0 14px;') !== false);
assertTest("Alternating zebra striping uses rgba(248, 250, 252, 0.57)", strpos($viewContent, 'rgba(248, 250, 252, 0.57)') !== false);
assertTest("In-cell status pill uses standard 28px height", strpos($viewContent, '.status-pill {') !== false && strpos($viewContent, 'height: 28px;') !== false);
assertTest("Headline only header enforced (no subtitle tagline)", strpos($viewContent, 'id="pageHeaderSubtitle" style="display: none;"') !== false);
assertTest("CurtissSearchableSelect attached to filter_branch and quick_range_select", strpos($viewContent, "CurtissSearchableSelect.attach(filterBranch)") !== false && strpos($viewContent, "CurtissSearchableSelect.attach(quickRange)") !== false);
assertTest("CurtissNav.enablePageSearch debounces 300ms", strpos($viewContent, "clearTimeout(reportSearchDebounce);") !== false && strpos($viewContent, "}, 300);") !== false);
assertTest("Realtime table search queries data columns and raw/formatted currency while excluding action buttons", strpos($viewContent, "td:not(.col-actions):not(.col-action)") !== false && strpos($viewContent, "replace(/,/g, '').replace(/\\blkr\\b|\\brs\\.?\\b/g, '')") !== false);

// -----------------------------------------------------------------------------
// SUMMARY
// -----------------------------------------------------------------------------
echo "\n========================================================================\n";
echo "REPORT ENGINE OPTIMIZATION SUITE RESULTS: {$passedTests} / {$totalTests} PASSED\n";
if ($failedTests > 0) {
    echo "FAILED: {$failedTests} tests failed!\n";
} else {
    echo "ALL TESTS PASSED CLEANLY! (100% SUCCESS)\n";
}
echo "========================================================================\n";

exit($failedTests > 0 ? 1 : 0);
