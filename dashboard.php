<?php
/**
 * Curtiss POS - Dashboard Controller (Stage 17 Special User Integration)
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/MaskingHelper.php';
require_once __DIR__ . '/core/View.php';

Auth::requireAuth();

$user = Auth::user();
$isAllBranches = !empty($_SESSION['all_branches_view']);
$activeBranchId = $user['active_branch_id'];

// Fetch summary metrics
$branchCount = Database::fetchOne("SELECT COUNT(*) AS total FROM branches WHERE status = 'active' AND deleted_at IS NULL")['total'] ?? 0;
$userCount = Database::fetchOne("SELECT COUNT(*) AS total FROM users WHERE status = 'active' AND deleted_at IS NULL")['total'] ?? 0;
$auditCount = Auth::isSpecialUser() ? 0 : (Database::fetchOne("SELECT COUNT(*) AS total FROM audit_logs")['total'] ?? 0);

// Product Master metrics
$productCount = Database::fetchOne("SELECT COUNT(*) AS total FROM products WHERE status = 'active' AND deleted_at IS NULL")['total'] ?? 0;
$brandCount = Database::fetchOne("SELECT COUNT(*) AS total FROM brands WHERE status = 'active' AND deleted_at IS NULL")['total'] ?? 0;

// Inventory metrics (Branch-Isolated vs All Branches Consolidated)
if ($isAllBranches) {
    $branchStockTotal = (float)(Database::fetchOne("
        SELECT COALESCE(SUM(bi.quantity), 0) AS total 
        FROM branch_inventory bi
        JOIN products p ON bi.product_id = p.id
        WHERE p.deleted_at IS NULL
    ")['total'] ?? 0);

    if (MaskingHelper::isSpecialUser()) {
        $branchStockTotal = MaskingHelper::maskQuantity($branchStockTotal);
    }

    $activeLotsCount = Database::fetchOne("
        SELECT COUNT(*) AS total 
        FROM product_lots pl
        JOIN products p ON pl.product_id = p.id
        WHERE pl.remaining_quantity > 0 AND pl.status = 'active' AND p.deleted_at IS NULL
    ")['total'] ?? 0;

    $ledgerEntriesCount = Auth::isSpecialUser() ? 0 : (Database::fetchOne("
        SELECT COUNT(*) AS total 
        FROM stock_ledger sl
        JOIN products p ON sl.product_id = p.id
        WHERE p.deleted_at IS NULL
    ")['total'] ?? 0);

    $lowStockCount = Database::fetchOne("
        SELECT COUNT(*) AS total 
        FROM branch_inventory bi
        JOIN products p ON bi.product_id = p.id
        WHERE bi.quantity <= bi.low_stock_threshold AND p.deleted_at IS NULL AND p.status = 'active'
    ")['total'] ?? 0;
} else {
    $branchStockTotal = (float)(Database::fetchOne("
        SELECT COALESCE(SUM(bi.quantity), 0) AS total 
        FROM branch_inventory bi
        JOIN products p ON bi.product_id = p.id
        WHERE bi.branch_id = :branch_id AND p.deleted_at IS NULL
    ", [':branch_id' => $activeBranchId])['total'] ?? 0);

    if (MaskingHelper::isSpecialUser()) {
        $branchStockTotal = MaskingHelper::maskQuantity($branchStockTotal);
    }

    $activeLotsCount = Database::fetchOne("
        SELECT COUNT(*) AS total 
        FROM product_lots pl
        JOIN products p ON pl.product_id = p.id
        WHERE pl.branch_id = :branch_id AND pl.remaining_quantity > 0 AND pl.status = 'active' AND p.deleted_at IS NULL
    ", [':branch_id' => $activeBranchId])['total'] ?? 0;

    $ledgerEntriesCount = Auth::isSpecialUser() ? 0 : (Database::fetchOne("
        SELECT COUNT(*) AS total 
        FROM stock_ledger sl
        JOIN products p ON sl.product_id = p.id
        WHERE sl.branch_id = :branch_id AND p.deleted_at IS NULL
    ", [':branch_id' => $activeBranchId])['total'] ?? 0);

    $lowStockCount = Database::fetchOne("
        SELECT COUNT(*) AS total 
        FROM branch_inventory bi
        JOIN products p ON bi.product_id = p.id
        WHERE bi.branch_id = :branch_id AND bi.quantity <= bi.low_stock_threshold AND p.deleted_at IS NULL AND p.status = 'active'
    ", [':branch_id' => $activeBranchId])['total'] ?? 0;
}

// Fetch recent 5 audit log entries (Blocked for Special User)
$recentAudits = Auth::isSpecialUser() ? [] : Database::fetchAll("
    SELECT * FROM audit_logs 
    ORDER BY id DESC 
    LIMIT 5
");

// Expiry & Batch Management Metrics
require_once __DIR__ . '/core/ExpiryManager.php';
$expirySummary = ExpiryManager::getExpirySummary($isAllBranches ? null : (int)$activeBranchId);

// Real-Time Sales & Analytics Data Integration
require_once __DIR__ . '/core/DashboardAnalytics.php';
$dashboardFilters = [
    'branch_id'    => $isAllBranches ? 'all' : (int)$activeBranchId,
    'preset'       => 'today',
    'special_user' => MaskingHelper::isSpecialUser()
];

// Fetch full dashboard bundle
$dashboardBundle = DashboardAnalytics::getDashboardBundle($dashboardFilters);
$salesAnalytics      = $dashboardBundle['analytics'] ?? [];
$financialOperations = $dashboardBundle['operations'] ?? [];
$productPerformance  = $dashboardBundle['products'] ?? [];

// Chart of Accounts balances: 1010 (Cash in Hand / Main Safe) & 1015 (Petty Cash)
$coaBalances = ['1010' => 0.00, '1015' => 0.00];
try {
    $coaRows = Database::fetchAll("
        SELECT coa.account_code,
            COALESCE(SUM(
                CASE 
                    WHEN coa.account_type IN ('asset', 'expense') THEN jl.debit - jl.credit
                    ELSE jl.credit - jl.debit
                END
            ), 0.00) AS balance
        FROM chart_of_accounts coa
        LEFT JOIN journal_lines jl ON coa.id = jl.account_id
        LEFT JOIN journal_entries je ON jl.journal_entry_id = je.id AND je.posting_status = 'posted'
        WHERE coa.account_code IN ('1010', '1015')
        GROUP BY coa.id, coa.account_code
    ");
    foreach ($coaRows as $cr) {
        $bal = (float)$cr['balance'];
        if (MaskingHelper::isSpecialUser()) {
            $bal = MaskingHelper::maskValue($bal, true);
        }
        $coaBalances[$cr['account_code']] = $bal;
    }
} catch (Throwable $e) {
    Logger::error("Failed to query COA balances for dashboard: " . $e->getMessage(), $e);
}

// Assemble $analyticsBundle expected by dashboard/index.php
$analyticsBundle = [
    'sales_summary'       => $salesAnalytics['current'] ?? [],
    'growth'              => [
        'net_sales_growth'     => $salesAnalytics['growth']['net_sales']['percentage'] ?? 0,
        'gross_profit_growth'  => $salesAnalytics['growth']['gross_profit']['percentage'] ?? 0,
        'cogs_growth'          => $salesAnalytics['growth']['cogs']['percentage'] ?? 0,
        'expenses_growth'      => $salesAnalytics['growth']['operating_expenses']['percentage'] ?? 0,
        'invoice_count_growth' => $salesAnalytics['growth']['invoice_count']['percentage'] ?? 0,
        'raw'                  => $salesAnalytics['growth'] ?? []
    ],
    'time_series'         => $dashboardBundle['time_series'] ?? [],
    'payment_methods'     => $dashboardBundle['payments']['methods'] ?? ($dashboardBundle['payments'] ?? []),
    'top_products'        => $productPerformance['top_selling'] ?? [],
    'slow_movers'         => $productPerformance['slow_moving'] ?? [],
    'category_breakdown'  => $dashboardBundle['categories'] ?? [],
    'brand_breakdown'     => $dashboardBundle['brands'] ?? [],
    'cashier_performance' => $dashboardBundle['cashiers'] ?? [],
    'branch_performance'  => $dashboardBundle['branches'] ?? [],
    'customer_analytics'  => $dashboardBundle['customers'] ?? [],
    'returns'             => $dashboardBundle['returns'] ?? [],
    'discounts'           => $dashboardBundle['discounts'] ?? [],
    'hourly_trend'        => []
];

// Populate hourly trend if today's time-series is available
if (!empty($dashboardBundle['time_series']['labels'])) {
    foreach ($dashboardBundle['time_series']['labels'] as $idx => $lbl) {
        $analyticsBundle['hourly_trend'][] = [
            'hour'      => (int)filter_var($lbl, FILTER_SANITIZE_NUMBER_INT),
            'net_sales' => (float)($dashboardBundle['time_series']['series']['sales'][$idx] ?? 0.00)
        ];
    }
}

// Assemble $operationsBundle expected by dashboard/index.php
$invOps = $financialOperations['inventory'] ?? [];
$operationsBundle = [
    'inventory' => array_merge($invOps, [
        'total_inventory_value' => $invOps['cost_valuation'] ?? 0.00,
        'low_stock_count'       => $invOps['low_stock_count'] ?? 0,
        'out_of_stock_count'    => $invOps['out_of_stock_count'] ?? 0,
        'stock_aging'           => $invOps['stock_aging'] ?? []
    ]),
    'financial' => [
        'cash_drawer_balance'    => $financialOperations['cash_drawer']['balance'] ?? 0.00,
        'cash_in_hand'           => $coaBalances['1010'] ?? 0.00,
        'petty_cash_balance'     => $coaBalances['1015'] ?? 0.00,
        'bank_total'             => $financialOperations['banking']['total_bank_balance'] ?? 0.00,
        'bank_accounts'          => $financialOperations['banking']['accounts'] ?? [],
        'outstanding_credit'     => $financialOperations['receivables']['total_receivable'] ?? 0.00,
        'customer_collections'   => $financialOperations['receivables']['period_collections'] ?? 0.00,
        'supplier_payables'      => $financialOperations['payables']['total_payables'] ?? 0.00
    ],
    'recent_sales' => $dashboardBundle['recent_activity'] ?? []
];

View::render('dashboard/index', [
    'currentUser'         => $user,
    'isAllBranches'       => $isAllBranches,
    'activeBranchName'    => $isAllBranches ? 'All Branches' : $user['active_branch_name'],
    'activeBranchCode'    => $isAllBranches ? 'ALL' : $user['active_branch_code'],
    'branchCount'         => $branchCount,
    'userCount'           => $userCount,
    'auditCount'          => $auditCount,
    'productCount'        => $productCount,
    'brandCount'          => $brandCount,
    'branchStockTotal'    => $branchStockTotal,
    'activeLotsCount'     => $activeLotsCount,
    'ledgerEntriesCount'  => $ledgerEntriesCount,
    'lowStockCount'       => $lowStockCount,
    'expirySummary'       => $expirySummary,
    'recentAudits'        => $recentAudits,
    'analyticsBundle'     => $analyticsBundle,
    'operationsBundle'    => $operationsBundle,
    'salesAnalytics'      => $salesAnalytics,
    'financialOperations' => $financialOperations,
    'dashboardFilters'    => $dashboardFilters
], 'Dashboard - Curtiss POS');
