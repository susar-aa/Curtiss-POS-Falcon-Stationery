<?php
/**
 * Curtiss POS - Stock Movement Ledger Controller (Stage 3)
 * Adheres strictly to Curtiss Master Rules: Branch Switching, Universal Search & Pagination.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Inventory.php';

Auth::requireAuth();

if (!Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER, ROLE_ACCOUNTANT, ROLE_CASHIER) || Auth::isSpecialUser()) {
    View::renderBlank('errors/403');
    exit();
}

$user = Auth::user();
$activeBranchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? $user['active_branch_id'] ?? 1);
$branchId = (int)($_GET['branch_id'] ?? $activeBranchId);
$activeBranchName = $user['active_branch_name'] ?? ('Branch #' . $branchId);

// If superadmin switched branch, resolve branch name accurately
if ($branchId !== (int)($user['active_branch_id'] ?? 1)) {
    $branchRow = Database::fetchOne("SELECT name FROM branches WHERE id = :id", [':id' => $branchId]);
    if ($branchRow) {
        $activeBranchName = $branchRow['name'];
    }
}

$filters = [
    'search'        => trim($_GET['search'] ?? ''),
    'product_id'    => (int)($_GET['product_id'] ?? 0),
    'movement_type' => trim($_GET['movement_type'] ?? '')
];

$page = max(1, (int)($_GET['page'] ?? 1));
$rawLimit = (int)($_GET['limit'] ?? 25);
$limit = in_array($rawLimit, [25, 50, 75, 100, 500], true) ? $rawLimit : 25;

$result = Inventory::getLedgerList($branchId, $filters, $page, $limit);

View::render('inventory/ledger', [
    'ledger'           => $result['items'],
    'totalItems'       => $result['totalItems'],
    'page'             => $result['page'],
    'limit'            => $limit,
    'totalPages'       => $result['totalPages'],
    'filters'          => $filters,
    'branchId'         => $branchId,
    'activeBranchName' => $activeBranchName
], 'Stock Movement Ledger - Curtiss POS');
