<?php
/**
 * Curtiss POS - Stock Movement Ledger Controller (Stage 3)
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Inventory.php';

Auth::requireAuth();

if (!Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER, ROLE_ACCOUNTANT, ROLE_CASHIER) || Auth::isSpecialUser()) {
    View::renderBlank('errors/403');
    exit();
}

$user = Auth::user();
$branchId = (int)($_GET['branch_id'] ?? $user['active_branch_id']);

$filters = [
    'search'        => trim($_GET['search'] ?? ''),
    'product_id'    => (int)($_GET['product_id'] ?? 0),
    'movement_type' => trim($_GET['movement_type'] ?? '')
];

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;

$result = Inventory::getLedgerList($branchId, $filters, $page, $limit);

View::render('inventory/ledger', [
    'ledger'           => $result['items'],
    'totalItems'       => $result['totalItems'],
    'page'             => $result['page'],
    'totalPages'       => $result['totalPages'],
    'filters'          => $filters,
    'branchId'         => $branchId,
    'activeBranchName' => $user['active_branch_name']
], 'Stock Movement Ledger - Curtiss POS');
