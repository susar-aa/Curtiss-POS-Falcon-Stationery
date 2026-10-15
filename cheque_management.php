<?php
/**
 * Curtiss POS - Central Cheque Management Controller
 * Handles authentication, permissions, server-side paginated data fetching, and renders the view.
 * Defaults to pending cheques view with summary totals text & history mode switch.
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
$branchId = (int)($_SESSION['branch_id'] ?? 1);

// Default status filter to 'pending' if not explicitly provided
$statusFilter = isset($_GET['status']) && $_GET['status'] !== '' ? trim($_GET['status']) : 'pending';
$supplierFilter = $_GET['supplier_id'] ?? '';
$searchFilter = $_GET['search'] ?? '';

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = max(5, (int)($_GET['limit'] ?? 15));

// Build query parameters for main table
$queryParams = [
    'branch_id'   => $branchId,
    'supplier_id' => $supplierFilter,
    'search'      => $searchFilter
];
if ($statusFilter !== 'all' && $statusFilter !== 'history') {
    $queryParams['status'] = $statusFilter;
}

$chequeData = ChequeEngine::getPaginatedCheques($queryParams, $page, $limit);
$cheques = $chequeData['items'];
$totalItems = $chequeData['totalItems'];
$totalPages = $chequeData['totalPages'];

// Fetch unique active suppliers grouped by name to avoid duplicate dropdown entries
$suppliers = Supplier::allUnique(true);
$bankAccounts = Banking::getBankAccounts($branchId, true);

// Calculate pending summary totals specifically for top header text
$pendingCheques = ChequeEngine::getCheques([
    'branch_id'   => $branchId,
    'status'      => 'pending',
    'supplier_id' => $supplierFilter
]);
$totPending = 0.00;
$countPending = count($pendingCheques);
foreach ($pendingCheques as $ch) {
    $totPending += (float)$ch['amount'];
}

View::render('purchasing/cheque_management', [
    'cheques'        => $cheques,
    'totalItems'     => $totalItems,
    'totalPages'     => $totalPages,
    'page'           => $page,
    'limit'          => $limit,
    'suppliers'      => $suppliers,
    'bankAccounts'   => $bankAccounts,
    'statusFilter'   => $statusFilter,
    'supplierFilter' => $supplierFilter,
    'searchFilter'   => $searchFilter,
    'totPending'     => $totPending,
    'countPending'   => $countPending
], 'Cheque Management - Curtiss POS');
