<?php
/**
 * Curtiss POS - Central Cheque Management Controller
 * Handles authentication, permissions, server-side paginated data fetching, and renders the view.
 * Supports tabs: 'received' (Customer Cheques) and 'issued' (Supplier Cheques).
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/ChequeEngine.php';
require_once __DIR__ . '/core/Banking.php';
require_once __DIR__ . '/core/Supplier.php';

Auth::requireLogin();
if (!Auth::hasPermission('view_cheques')) {
    View::renderBlank('errors/403');
    exit;
}

$user = Auth::user();
$userId = (int)$user['id'];
$branchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? 1);

// Active Tab: 'received' (Customer Cheques) or 'issued' (Supplier Cheques)
$tab = isset($_GET['tab']) && in_array($_GET['tab'], ['received', 'issued'], true) ? $_GET['tab'] : 'received';

$view = $_GET['view'] ?? 'active';
$searchFilter = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = (int)($_GET['limit'] ?? 25);
$allowedLimits = [25, 50, 75, 100, 500];
if (!in_array($limit, $allowedLimits, true)) {
    $limit = 25;
}

$bankAccounts = Banking::getBankAccounts($branchId, true);

if ($tab === 'received') {
    // Default status to 'all_pending' (in_hand & deposited)
    $statusFilter = isset($_GET['status']) && $_GET['status'] !== '' ? trim($_GET['status']) : 'all_pending';
    $customerFilter = $_GET['customer_id'] ?? '';

    $queryParams = [
        'branch_id'   => $branchId,
        'customer_id' => $customerFilter,
        'search'      => $searchFilter,
        'view'        => $view,
        'status'      => $statusFilter
    ];

    $chequeData = ChequeEngine::getPaginatedCustomerCheques($queryParams, $page, $limit);
    $cheques = $chequeData['items'];
    $totalItems = $chequeData['totalItems'];
    $totalPages = $chequeData['totalPages'];

    // Fast customer pending summary
    $summary = ChequeEngine::getCustomerPendingSummary($branchId, !empty($customerFilter) ? (int)$customerFilter : null);

    // Fetch active customers
    $customers = Database::fetchAll("SELECT id, name, customer_code, phone FROM customers WHERE deleted_at IS NULL ORDER BY name ASC");
    $suppliers = [];
    $supplierFilter = '';
} else {
    // Issued Cheques (Supplier)
    $statusFilter = isset($_GET['status']) && $_GET['status'] !== '' ? trim($_GET['status']) : 'pending';
    $supplierFilter = $_GET['supplier_id'] ?? '';

    $queryParams = [
        'branch_id'   => $branchId,
        'supplier_id' => $supplierFilter,
        'search'      => $searchFilter,
        'view'        => $view
    ];
    if ($statusFilter !== 'all' && $statusFilter !== 'history') {
        $queryParams['status'] = $statusFilter;
    }

    $chequeData = ChequeEngine::getPaginatedCheques($queryParams, $page, $limit);
    $cheques = $chequeData['items'];
    $totalItems = $chequeData['totalItems'];
    $totalPages = $chequeData['totalPages'];

    $summary = ChequeEngine::getPendingSummary($branchId, !empty($supplierFilter) ? (int)$supplierFilter : null);

    $suppliers = Supplier::allUnique(true);
    $customers = [];
    $customerFilter = '';
}

View::render('purchasing/cheque_management', [
    'tab'            => $tab,
    'cheques'        => $cheques,
    'totalItems'     => $totalItems,
    'totalPages'     => $totalPages,
    'page'           => $page,
    'limit'          => $limit,
    'suppliers'      => $suppliers,
    'customers'      => $customers,
    'bankAccounts'   => $bankAccounts,
    'statusFilter'   => $statusFilter,
    'supplierFilter' => $supplierFilter,
    'customerFilter' => $customerFilter ?? '',
    'searchFilter'   => $searchFilter,
    'summary'        => $summary,
    'totPending'     => $summary['total_pending'] ?? ($summary['total_in_hand'] ?? 0),
    'countPending'   => $summary['count_pending'] ?? ($summary['count_in_hand'] ?? 0),
    'view'           => $view
], 'Cheque Management - Curtiss POS');
