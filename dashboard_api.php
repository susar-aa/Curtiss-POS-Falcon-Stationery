<?php
/**
 * Curtiss POS - Dashboard Real-Time API Endpoint
 * Handles asynchronous AJAX queries, incremental KPI refreshes,
 * multidimensional chart payloads, lightweight polling, and SSE real-time streaming.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/MaskingHelper.php';
require_once __DIR__ . '/core/DashboardAnalytics.php';

// Authenticate session
if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized session. Please log in.']);
    exit;
}

$user = Auth::user();
$action = trim($_GET['action'] ?? 'bundle');

// Resolve branch context and role constraints
$sessionBranchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? ($user['active_branch_id'] ?? 1));
$isSuperAdmin = Auth::hasRole('super_admin') || Auth::hasRole('admin');

if ($isSuperAdmin && isset($_GET['branch_id'])) {
    $requestedBranch = trim($_GET['branch_id']);
    $branchFilter = ($requestedBranch === 'all' || $requestedBranch === '') ? 'all' : (int)$requestedBranch;
} else {
    $branchFilter = $sessionBranchId;
}

// Build standard filter array from GET request
$filters = [
    'branch_id'    => $branchFilter,
    'preset'       => trim($_GET['preset'] ?? 'today'),
    'custom_from'  => !empty($_GET['from']) ? trim($_GET['from']) : null,
    'custom_to'    => !empty($_GET['to']) ? trim($_GET['to']) : null,
    'category_id'  => !empty($_GET['category_id']) ? (int)$_GET['category_id'] : null,
    'brand_id'     => !empty($_GET['brand_id']) ? (int)$_GET['brand_id'] : null,
    'supplier_id'  => !empty($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : null,
    'product_id'   => !empty($_GET['product_id']) ? (int)$_GET['product_id'] : null,
    'user_id'      => !empty($_GET['user_id']) ? (int)$_GET['user_id'] : null,
    'special_user' => MaskingHelper::isSpecialUser()
];

// =========================================================================
// SERVER-SENT EVENTS (SSE) STREAMING
// =========================================================================
if ($action === 'stream') {
    // Crucial for long-lived PHP requests on Apache/XAMPP:
    // Close the session lock immediately so other AJAX requests & page navigations aren't blocked!
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    // Set headers for SSE stream
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Connection: keep-alive');
    header('X-Accel-Buffering: no'); // Nginx / reverse proxy disable buffer

    // Prevent script timeout
    @set_time_limit(0);
    @ini_set('max_execution_time', '0');

    $pulseBranch = ($branchFilter === 'all') ? null : (int)$branchFilter;
    $lastTimestamp = !empty($_GET['last_check']) ? trim($_GET['last_check']) : date('Y-m-d H:i:s');
    $maxLoops = 30; // 30 iterations * 2s = 60s max per connection, client will reconnect automatically
    $loop = 0;

    echo "event: connected\n";
    echo "data: " . json_encode(['connected' => true, 'timestamp' => date('Y-m-d H:i:s')]) . "\n\n";
    @ob_flush();
    @flush();

    while ($loop < $maxLoops && !connection_aborted()) {
        $pulse = DashboardAnalytics::getRealtimePulse($pulseBranch, $lastTimestamp);

        if ($pulse['has_updates'] || $loop === 0) {
            $lastTimestamp = $pulse['latest_activity'];
            echo "event: pulse\n";
            echo "data: " . json_encode([
                'success'         => true,
                'has_updates'     => $pulse['has_updates'],
                'latest_activity' => $pulse['latest_activity'],
                'live_counters'   => $pulse['live_counters'],
                'timestamp'       => date('Y-m-d H:i:s')
            ]) . "\n\n";
            @ob_flush();
            @flush();
        } else {
            // Heartbeat ping
            echo "event: ping\n";
            echo "data: " . json_encode(['time' => time()]) . "\n\n";
            @ob_flush();
            @flush();
        }

        sleep(2);
        $loop++;
    }
    exit;
}

// =========================================================================
// STANDARD JSON RESPONSES
// =========================================================================
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

try {
    switch ($action) {
        // Ultra-fast lightweight poll
        case 'pulse':
            $pulseBranch = ($branchFilter === 'all') ? null : (int)$branchFilter;
            $lastCheck = !empty($_GET['last_check']) ? trim($_GET['last_check']) : null;
            $lastSig   = !empty($_GET['last_sig']) ? trim($_GET['last_sig']) : null;
            $pulse = DashboardAnalytics::getRealtimePulse($pulseBranch, $lastCheck, $lastSig);
            echo json_encode(['success' => true, 'data' => $pulse]);
            break;

        // Main sales analytics (Gross/Net sales, Profit, COGS, Growth %, Targets)
        case 'analytics':
        case 'kpis':
            $data = DashboardAnalytics::getSalesAnalytics($filters);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        // Time series for charts
        case 'time_series':
        case 'charts':
            $data = DashboardAnalytics::getTimeSeriesChartData($filters);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        // Multidimensional breakdowns
        case 'categories':
            $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
            $data = DashboardAnalytics::getSalesByCategory($filters, $limit);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'brands':
            $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
            $data = DashboardAnalytics::getSalesByBrand($filters, $limit);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'suppliers':
            $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
            $data = DashboardAnalytics::getSalesBySupplier($filters, $limit);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'products':
            $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
            $data = DashboardAnalytics::getProductPerformance($filters, $limit);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'cashiers':
            $data = DashboardAnalytics::getCashierPerformance($filters);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'branches':
            $data = DashboardAnalytics::getBranchPerformance($filters);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'payments':
            $data = DashboardAnalytics::getPaymentMethodBreakdown($filters);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'discounts':
            $data = DashboardAnalytics::getDiscountAndPromotionAnalytics($filters);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'customers':
            $data = DashboardAnalytics::getCustomerPurchaseAnalytics($filters);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'returns':
            $data = DashboardAnalytics::getReturnsAndRefundTrends($filters);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        // Financial & operational KPIs (Cash drawer, banks, receivables, payables, stock aging)
        case 'operations':
            $data = DashboardAnalytics::getFinancialOperations($filters);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        // Recent activity feed
        case 'activities':
            $limit = max(1, min(50, (int)($_GET['limit'] ?? 10)));
            $data = DashboardAnalytics::getRecentActivities($filters, $limit);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        // Complete dashboard bundle
        case 'bundle':
        default:
            $bundle = DashboardAnalytics::getDashboardBundle($filters);
            echo json_encode(['success' => true, 'data' => $bundle]);
            break;
    }
} catch (Throwable $e) {
    Logger::error("Dashboard API Error [action=$action]: " . $e->getMessage(), $e);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while computing dashboard analytics.',
        'error'   => $e->getMessage()
    ]);
}
