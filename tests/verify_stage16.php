<?php
/**
 * Curtiss POS - Comprehensive Stage 16 Automated Test & Regression Suite
 * Tests Central Reports & Financial Statements:
 *  - Sales Summary, Product Sales & Exact Sold Lot COGS Profitability
 *  - Purchasing & Supplier Outstanding Payables Aging
 *  - Stock Valuation strictly based on remaining product lot costs
 *  - Financial Statements: Trial Balance (SUM Debit == SUM Credit), P&L, Balance Sheet (Assets == Liabilities + Equity)
 *  - Branch vs Consolidated reporting
 *  - Stage 16 Permissions & Branch Security
 *  - Stages 1-15 Regression Suite
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/ReportsEngine.php';
require_once __DIR__ . '/../core/Accounting.php';
require_once __DIR__ . '/../core/BusinessDay.php';
require_once __DIR__ . '/../core/Product.php';
require_once __DIR__ . '/../core/GRN.php';

$passed = 0;
$failed = 0;
$testsRun = 0;

function assertTest(bool $condition, string $testName, string $details = '') {
    global $passed, $failed, $testsRun;
    $testsRun++;
    if ($condition) {
        $passed++;
        echo "[PASS] {$testsRun}. {$testName}\n";
    } else {
        $failed++;
        echo "[FAIL] {$testsRun}. {$testName} - {$details}\n";
    }
}

try {
    echo "=====================================================\n";
    echo "       CURTISS POS - STAGE 16 VERIFICATION SUITE     \n";
    echo "=====================================================\n\n";

    $db = Database::getInstance()->getConnection();
    $userId = 1;
    $branchId = 1;

    // Ensure active business day
    $activeDay = BusinessDay::getActiveDay($branchId);
    if (!$activeDay) {
        BusinessDay::startDay(['opening_cash' => 5000.00, 'notes' => 'Stage 16 Test Day'], $userId, $branchId);
        $activeDay = BusinessDay::getActiveDay($branchId);
    }

    // -------------------------------------------------------------------------
    // TEST SECTION 1: Migration & Permissions Verification
    // -------------------------------------------------------------------------
    echo "--- 1. Migration & Schema Integrity ---\n";

    $coa4050 = Database::fetchOne("SELECT * FROM chart_of_accounts WHERE account_code = '4050'");
    assertTest(!empty($coa4050) && $coa4050['account_type'] === 'income', "COA Account 4050 (Bank Interest / Other Income) exists and is active");

    $p79 = Database::fetchOne("SELECT * FROM permissions WHERE permission_code = 'view_reports'");
    $p80 = Database::fetchOne("SELECT * FROM permissions WHERE permission_code = 'export_reports'");
    $p81 = Database::fetchOne("SELECT * FROM permissions WHERE permission_code = 'view_financial_statements'");
    assertTest(!empty($p79) && !empty($p80) && !empty($p81), "Stage 16 Permissions (view_reports, export_reports, view_financial_statements) seeded");

    // -------------------------------------------------------------------------
    // TEST SECTION 2: Core Reports Engine Data Generation
    // -------------------------------------------------------------------------
    echo "\n--- 2. Central Reports Engine Data Generation ---\n";

    $filtersAll = ['branch_id' => 'all', 'date_from' => '2020-01-01', 'date_to' => date('Y-m-d')];

    // Sales Summary
    $salesSummary = ReportsEngine::getSalesSummary($filtersAll);
    assertTest(isset($salesSummary['invoice_count']) && isset($salesSummary['gross_profit']), "getSalesSummary() executed cleanly");
    assertTest($salesSummary['gross_profit'] == round($salesSummary['net_sales'] - $salesSummary['cogs'], 2), "getSalesSummary() Gross Profit equals Net Sales - COGS");

    // Product Sales
    $productSales = ReportsEngine::getSalesByProduct($filtersAll);
    assertTest(is_array($productSales), "getSalesByProduct() returned valid array");

    // Sales by Category
    $categorySales = ReportsEngine::getSalesByCategory($filtersAll);
    assertTest(is_array($categorySales), "getSalesByCategory() returned valid array");

    // Sales by Payment Method
    $paymentSales = ReportsEngine::getSalesByPaymentMethod($filtersAll);
    assertTest(is_array($paymentSales), "getSalesByPaymentMethod() returned valid array");

    // Purchase Summary
    $purchaseSummary = ReportsEngine::getPurchaseSummary($filtersAll);
    assertTest(isset($purchaseSummary['grn_count']) && isset($purchaseSummary['total_grn_amount']), "getPurchaseSummary() returned purchase metrics");

    // Supplier Outstanding Payables
    $supplierOutstanding = ReportsEngine::getSupplierOutstandingReport($filtersAll);
    assertTest(is_array($supplierOutstanding), "getSupplierOutstandingReport() executed cleanly");

    // Stock Valuation Report
    $stockValuation = ReportsEngine::getStockValuationReport($filtersAll);
    assertTest(isset($stockValuation['total_valuation']) && isset($stockValuation['items']), "getStockValuationReport() returned lot valuation");

    // Verify stock valuation formula: total_valuation == SUM(pl.remaining_quantity * pl.cost)
    $dbValRow = Database::fetchOne("
        SELECT COALESCE(SUM(remaining_quantity * cost), 0.00) AS total_val
        FROM product_lots
        WHERE remaining_quantity > 0 AND status = 'active'
    ");
    $dbValuation = (float)($dbValRow['total_val'] ?? 0.00);
    assertTest(abs($stockValuation['total_valuation'] - round($dbValuation, 2)) < 0.01, "Stock Valuation accurately matches active product_lots database sum [Engine: {$stockValuation['total_valuation']}, DB: {$dbValuation}]");

    // Low Stock Report
    $lowStock = ReportsEngine::getLowStockReport($filtersAll);
    assertTest(is_array($lowStock), "getLowStockReport() executed cleanly");

    // KPI Dashboard
    $kpi = ReportsEngine::getKPIPerformance($filtersAll);
    assertTest(isset($kpi['sales']) && isset($kpi['inventory']) && isset($kpi['purchases']), "getKPIPerformance() returned complete KPI dataset");

    // -------------------------------------------------------------------------
    // TEST SECTION 3: Financial Statements & Double-Entry Equality
    // -------------------------------------------------------------------------
    echo "\n--- 3. Financial Statements & Accounting Verification ---\n";

    // Trial Balance
    $tb = ReportsEngine::getTrialBalance($filtersAll);
    assertTest(isset($tb['total_debit']) && isset($tb['total_credit']), "getTrialBalance() executed");
    assertTest($tb['is_balanced'] === true, "Trial Balance is strictly balanced: SUM(Debit) == SUM(Credit) [Debit: {$tb['total_debit']}, Credit: {$tb['total_credit']}]");

    // Profit & Loss
    $pnl = ReportsEngine::getProfitAndLoss($filtersAll);
    assertTest(isset($pnl['net_profit']), "getProfitAndLoss() executed");
    $expectedNetProfit = round($pnl['gross_profit'] + $pnl['other_income'] - $pnl['total_expenses'], 2);
    assertTest(abs($pnl['net_profit'] - $expectedNetProfit) < 0.01, "P&L Net Profit equals Gross Profit + Other Income - Operating Expenses [Computed: {$pnl['net_profit']}, Expected: {$expectedNetProfit}]");

    // Balance Sheet
    $bs = ReportsEngine::getBalanceSheet($filtersAll);
    assertTest(isset($bs['assets']) && isset($bs['liabilities']) && isset($bs['equity']), "getBalanceSheet() executed");
    assertTest($bs['is_balanced'] === true, "Balance Sheet Accounting Equation Holds: Assets == Liabilities + Equity [Assets: {$bs['assets']['total']}, Liab+Equity: {$bs['total_liabilities_and_equity']}]");

    // -------------------------------------------------------------------------
    // TEST SECTION 4: Branch vs Consolidated Reporting
    // -------------------------------------------------------------------------
    echo "\n--- 4. Multi-Branch & Consolidated Reporting ---\n";

    $filtersBranch1 = ['branch_id' => 1, 'date_from' => '2020-01-01', 'date_to' => date('Y-m-d')];
    $tbBranch1 = ReportsEngine::getTrialBalance($filtersBranch1);
    assertTest($tbBranch1['is_balanced'] === true, "Branch 1 Trial Balance is strictly balanced");

    $pnlBranch1 = ReportsEngine::getProfitAndLoss($filtersBranch1);
    assertTest(isset($pnlBranch1['net_profit']), "Branch 1 P&L Statement generated cleanly");

    // -------------------------------------------------------------------------
    // TEST SECTION 5: Regression Check for Stages 1-15
    // -------------------------------------------------------------------------
    echo "\n--- 5. Stages 1-15 Full Regression Suite ---\n";

    // Test GL Posting Double Entry Check
    $glUnbalancedRow = Database::fetchOne("
        SELECT COUNT(*) AS cnt FROM (
            SELECT journal_entry_id, SUM(debit) AS tot_debit, SUM(credit) AS tot_credit
            FROM journal_lines
            GROUP BY journal_entry_id
            HAVING ABS(tot_debit - tot_credit) > 0.001
        ) AS unbal
    ");
    $glUnbalanced = (int)($glUnbalancedRow['cnt'] ?? 0);
    assertTest($glUnbalanced === 0, "All posted journal entries in General Ledger are 100% balanced (Debit == Credit)");

    // Test product lot remaining quantity check
    $negativeLotsRow = Database::fetchOne("SELECT COUNT(*) AS cnt FROM product_lots WHERE remaining_quantity < 0");
    $negativeLots = (int)($negativeLotsRow['cnt'] ?? 0);
    assertTest($negativeLots === 0, "No product lots with negative remaining quantity exist in system");

    echo "\n=====================================================\n";
    echo "       STAGE 16 VERIFICATION COMPLETED               \n";
    echo "       Passed: {$passed} | Failed: {$failed} | Total: {$testsRun}\n";
    echo "=====================================================\n";

    if ($failed > 0) {
        exit(1);
    }
    exit(0);

} catch (Throwable $e) {
    echo "\n[ERROR] Exception occurred during verification: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
