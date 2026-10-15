<?php
/**
 * Curtiss POS - Sales History Controller
 * Provides searchable, paginated sales transaction logs, receipt drawer, and export.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Sale.php';
require_once __DIR__ . '/core/Customer.php';

Auth::requireAuth();

if (!Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER, ROLE_ACCOUNTANT, ROLE_CASHIER, ROLE_SPECIAL_USER)) {
    View::renderBlank('errors/403');
    exit();
}

$user = Auth::user();
$activeBranchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? $user['active_branch_id'] ?? 1);

// AJAX: Get Sale Details for Drawer/Modal
if (isset($_GET['action']) && $_GET['action'] === 'get_sale_details') {
    header('Content-Type: application/json');
    $saleId = (int)($_GET['id'] ?? 0);
    $details = Sale::getSaleDetails($saleId);
    if ($details) {
        echo json_encode(['success' => true, 'data' => $details]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Sales record not found.']);
    }
    exit();
}

// Date preset handling
$preset = trim($_GET['date_preset'] ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');

if (!empty($preset)) {
    $today = date('Y-m-d');
    if ($preset === 'today') {
        $dateFrom = $today;
        $dateTo = $today;
    } elseif ($preset === 'yesterday') {
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $dateFrom = $yesterday;
        $dateTo = $yesterday;
    } elseif ($preset === 'week') {
        $dateFrom = date('Y-m-d', strtotime('-7 days'));
        $dateTo = $today;
    } elseif ($preset === 'month') {
        $dateFrom = date('Y-m-01');
        $dateTo = $today;
    } elseif ($preset === 'all') {
        $dateFrom = '';
        $dateTo = '';
    }
}

$filters = [
    'search'      => trim($_GET['search'] ?? ''),
    'status'      => trim($_GET['status'] ?? ''),
    'date_from'   => $dateFrom,
    'date_to'     => $dateTo,
    'date_preset' => $preset,
    'customer_id' => (int)($_GET['customer_id'] ?? 0),
];

$page = max(1, (int)($_GET['page'] ?? 1));
$requestedLimit = (int)($_GET['limit'] ?? 25);
$limit = in_array($requestedLimit, [25, 50, 75, 100, 500], true) ? $requestedLimit : 25;

$result = Sale::getList($activeBranchId, $filters, $page, $limit);

View::render('sales/sales_history', [
    'sales'            => $result['items'],
    'totalItems'       => $result['totalItems'],
    'page'             => $result['page'],
    'totalPages'       => $result['totalPages'],
    'limit'            => $result['limit'],
    'filters'          => $filters,
    'activeBranchId'   => $activeBranchId
]);
