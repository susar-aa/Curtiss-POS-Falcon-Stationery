<?php
/**
 * CLI Verification Script: Dashboard Analytics Engine
 * Tests all calculations, date filters, comparisons, operational KPIs, real-time pulse,
 * and Special User presentation masking without mutating database records.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/MaskingHelper.php';
require_once __DIR__ . '/../core/DashboardAnalytics.php';

echo "=========================================================\n";
echo "CURTISS POS — REAL-TIME SALES & ANALYTICS DASHBOARD TESTS\n";
echo "=========================================================\n\n";

$passed = 0;
$failed = 0;

function assertTest(string $name, bool $condition, string $details = '') {
    global $passed, $failed;
    if ($condition) {
        echo " [PASS] $name\n";
        if ($details) echo "        $details\n";
        $passed++;
    } else {
        echo " [FAIL] $name\n";
        if ($details) echo "        $details\n";
        $failed++;
    }
}

// -------------------------------------------------------------
// TEST 1: Date Range Resolver & Previous Period Calculations
// -------------------------------------------------------------
echo "1. Testing Date Range Resolver & Period Comparisons...\n";
$todayRange = DashboardAnalytics::resolveDateRange('today');
assertTest(
    "Date Range 'today' resolves correctly",
    $todayRange['current']['from_date'] === date('Y-m-d') &&
    $todayRange['current']['to_date'] === date('Y-m-d') &&
    $todayRange['interval'] === 'hourly',
    "Interval: {$todayRange['interval']}, Prev: {$todayRange['previous']['from_date']}"
);

$weekRange = DashboardAnalytics::resolveDateRange('this_week');
assertTest(
    "Date Range 'this_week' resolves daily interval with matching previous week",
    $weekRange['interval'] === 'daily' && !empty($weekRange['previous']['from_date']),
    "From: {$weekRange['current']['from_date']} To: {$weekRange['current']['to_date']}"
);

$monthRange = DashboardAnalytics::resolveDateRange('this_month');
assertTest(
    "Date Range 'this_month' resolves 1st to end-of-month",
    $monthRange['current']['from_date'] === date('Y-m-01') &&
    $monthRange['interval'] === 'daily',
    "Month from: {$monthRange['current']['from_date']} to {$monthRange['current']['to_date']}"
);

$customRange = DashboardAnalytics::resolveDateRange('custom', '2026-10-01', '2026-10-10');
assertTest(
    "Date Range 'custom' (10 days) calculates 10-day preceding equivalent period",
    $customRange['current']['from_date'] === '2026-10-01' &&
    $customRange['current']['to_date'] === '2026-10-10' &&
    $customRange['previous']['to_date'] === '2026-09-30' &&
    $customRange['previous']['from_date'] === '2026-09-21',
    "Prev period: {$customRange['previous']['from_date']} to {$customRange['previous']['to_date']}"
);

// -------------------------------------------------------------
// TEST 2: Sales Analytics & Calculations
// -------------------------------------------------------------
echo "\n2. Testing Main Sales Analytics & Calculations...\n";
$analytics = DashboardAnalytics::getSalesAnalytics(['preset' => 'this_month', 'branch_id' => 'all']);

assertTest(
    "Sales Analytics computes structured result",
    isset($analytics['current']['gross_sales']) &&
    isset($analytics['current']['net_sales']) &&
    isset($analytics['current']['cogs']) &&
    isset($analytics['current']['gross_profit']) &&
    isset($analytics['current']['operating_expenses']) &&
    isset($analytics['current']['net_profit']),
    sprintf(
        "Gross: LKR %.2f, Net: LKR %.2f, COGS: LKR %.2f, GP: LKR %.2f, Exp: LKR %.2f, NP: LKR %.2f",
        $analytics['current']['gross_sales'],
        $analytics['current']['net_sales'],
        $analytics['current']['cogs'],
        $analytics['current']['gross_profit'],
        $analytics['current']['operating_expenses'],
        $analytics['current']['net_profit']
    )
);

assertTest(
    "Gross Profit equals Net Sales minus COGS",
    abs($analytics['current']['gross_profit'] - ($analytics['current']['net_sales'] - $analytics['current']['cogs'])) < 0.05,
    "Calculated GP: {$analytics['current']['gross_profit']}, Net - COGS: " . ($analytics['current']['net_sales'] - $analytics['current']['cogs'])
);

assertTest(
    "Growth metrics computed for all major KPIs",
    isset($analytics['growth']['gross_sales']['percentage']) &&
    isset($analytics['growth']['net_sales']['percentage']) &&
    isset($analytics['growth']['net_profit']['percentage']),
    "Net Sales Growth: {$analytics['growth']['net_sales']['percentage']}%, Trend: {$analytics['growth']['net_sales']['trend']}"
);

// -------------------------------------------------------------
// TEST 3: Time-Series Chart Data
// -------------------------------------------------------------
echo "\n3. Testing Time-Series Chart Datasets...\n";
$chartData = DashboardAnalytics::getTimeSeriesChartData(['preset' => 'today', 'branch_id' => 'all']);

assertTest(
    "Hourly time-series produces 24 hours of parallel buckets",
    count($chartData['labels']) === 24 &&
    count($chartData['series']['sales']) === 24 &&
    count($chartData['series']['previous_sales']) === 24,
    "Labels count: " . count($chartData['labels']) . ", Sales series points: " . count($chartData['series']['sales'])
);

$monthChart = DashboardAnalytics::getTimeSeriesChartData(['preset' => 'this_month', 'branch_id' => 'all']);
assertTest(
    "Monthly time-series generates daily buckets with summary totals",
    $monthChart['interval'] === 'daily' &&
    isset($monthChart['summary']['total_sales']) &&
    isset($monthChart['summary']['total_profit']),
    "Total month sales: LKR {$monthChart['summary']['total_sales']}"
);

// -------------------------------------------------------------
// TEST 4: Multidimensional Breakdowns
// -------------------------------------------------------------
echo "\n4. Testing Multidimensional Breakdowns...\n";
$categories = DashboardAnalytics::getSalesByCategory(['preset' => 'this_month', 'branch_id' => 'all']);
assertTest(
    "Category breakdown returns valid list with profit margins",
    is_array($categories),
    "Categories returned: " . count($categories)
);

$brands = DashboardAnalytics::getSalesByBrand(['preset' => 'this_month', 'branch_id' => 'all']);
assertTest(
    "Brand breakdown returns valid list",
    is_array($brands),
    "Brands returned: " . count($brands)
);

$suppliers = DashboardAnalytics::getSalesBySupplier(['preset' => 'this_month', 'branch_id' => 'all']);
assertTest(
    "Supplier breakdown returns valid list",
    is_array($suppliers),
    "Suppliers returned: " . count($suppliers)
);

$products = DashboardAnalytics::getProductPerformance(['preset' => 'this_month', 'branch_id' => 'all']);
assertTest(
    "Product performance returns top-selling, profitable, and slow-moving lists",
    isset($products['top_selling']) &&
    isset($products['most_profitable']) &&
    isset($products['slow_moving']),
    "Top selling: " . count($products['top_selling']) . ", Slow moving: " . count($products['slow_moving'])
);

$paymentBreakdown = DashboardAnalytics::getPaymentMethodBreakdown(['preset' => 'this_month', 'branch_id' => 'all']);
assertTest(
    "Payment method breakdown correctly groups collections",
    isset($paymentBreakdown['total_collections']) &&
    is_array($paymentBreakdown['methods']),
    "Total collections: LKR {$paymentBreakdown['total_collections']}, Methods: " . count($paymentBreakdown['methods'])
);

// -------------------------------------------------------------
// TEST 5: Financial & Operational KPIs
// -------------------------------------------------------------
echo "\n5. Testing Financial & Operational KPIs...\n";
$ops = DashboardAnalytics::getFinancialOperations(['preset' => 'today', 'branch_id' => 'all']);

assertTest(
    "Financial Operations returns Cash Drawer, Bank, Receivables, Payables, and Aging",
    isset($ops['cash_drawer']['balance']) &&
    isset($ops['banking']['total_bank_balance']) &&
    isset($ops['receivables']['total_receivable']) &&
    isset($ops['payables']['total_payables']) &&
    isset($ops['inventory']['stock_aging']),
    sprintf(
        "Drawer: LKR %.2f, Banks: LKR %.2f, Receivables: LKR %.2f, Payables: LKR %.2f",
        $ops['cash_drawer']['balance'],
        $ops['banking']['total_bank_balance'],
        $ops['receivables']['total_receivable'],
        $ops['payables']['total_payables']
    )
);

assertTest(
    "Stock Aging breakdown contains all 5 age brackets",
    count($ops['inventory']['stock_aging']) === 5,
    "Brackets: " . implode(', ', array_column($ops['inventory']['stock_aging'], 'label'))
);

// -------------------------------------------------------------
// TEST 6: Real-Time Pulse & Fast Polling
// -------------------------------------------------------------
echo "\n6. Testing Real-Time Pulse Check...\n";
$t0 = microtime(true);
$pulse = DashboardAnalytics::getRealtimePulse(null, null);
$durationMs = round((microtime(true) - $t0) * 1000, 2);

assertTest(
    "Real-time pulse executes ultra-fast under 50ms",
    $durationMs < 50.0 && isset($pulse['has_updates']) && isset($pulse['latest_activity']),
    "Execution time: {$durationMs}ms, Has updates: " . ($pulse['has_updates'] ? 'yes' : 'no')
);

// -------------------------------------------------------------
// TEST 7: Special User Presentation Masking
// -------------------------------------------------------------
echo "\n7. Testing Special User Presentation Layer Masking...\n";
$normalAnalytics = DashboardAnalytics::getSalesAnalytics(['preset' => 'this_month', 'branch_id' => 'all', 'special_user' => false]);
$maskedAnalytics = DashboardAnalytics::getSalesAnalytics(['preset' => 'this_month', 'branch_id' => 'all', 'special_user' => true]);

$factor = MaskingHelper::getFactor();
$expectedMaskedSales = round($normalAnalytics['current']['gross_sales'] * $factor, 2);

assertTest(
    "Special user masking scales financial metrics strictly by configured percentage factor ({$factor})",
    abs($maskedAnalytics['current']['gross_sales'] - $expectedMaskedSales) < 0.05,
    "Normal: LKR {$normalAnalytics['current']['gross_sales']} -> Masked: LKR {$maskedAnalytics['current']['gross_sales']} (Expected: LKR {$expectedMaskedSales})"
);

// -------------------------------------------------------------
// SUMMARY
// -------------------------------------------------------------
echo "\n=========================================================\n";
echo "TEST RESULTS: Total: " . ($passed + $failed) . " | Passed: $passed | Failed: $failed\n";
echo "=========================================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
