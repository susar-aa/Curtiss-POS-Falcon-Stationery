<?php
/**
 * Curtiss POS - Damaged & Wastage Controller (Redesign Theme & Design Rules Enforced)
 * Queries damaged stock records and active lots and delegates to inventory/damaged view.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Database.php';

Auth::requireAuth();

if (!Auth::hasPermission('manage_damaged_stock') && !Auth::hasPermission('inventory.view')) {
    View::renderBlank('errors/403');
    exit();
}

$user = Auth::user();
$userId = (int)$user['id'];
$branchId = (int)($_GET['branch_id'] ?? $user['active_branch_id'] ?? 1);
$statusFilter = trim($_GET['status'] ?? '');

$params = [':b' => $branchId];
$whereStatus = "";
if (!empty($statusFilter)) {
    $whereStatus = " AND ds.status = :st ";
    $params[':st'] = $statusFilter;
}

$damagedList = Database::fetchAll("
    SELECT ds.*, p.name AS product_name, p.barcode, pl.lot_number, pl.cost AS lot_cost, u.username AS logger_name,
           sr.return_no
    FROM damaged_stock ds
    JOIN products p ON ds.product_id = p.id
    LEFT JOIN product_lots pl ON ds.lot_id = pl.id
    JOIN users u ON ds.logged_by = u.id
    LEFT JOIN sales_returns sr ON ds.sales_return_id = sr.id
    WHERE ds.branch_id = :b {$whereStatus}
    ORDER BY ds.id DESC LIMIT 100
", $params);

$productsWithLots = Database::fetchAll("
    SELECT p.id AS product_id, p.name AS product_name, p.barcode, pl.id AS lot_id, pl.lot_number, pl.cost, pl.selling_price, pl.remaining_quantity
    FROM products p
    JOIN product_lots pl ON p.id = pl.product_id
    WHERE pl.branch_id = :b AND pl.status = 'active' AND pl.remaining_quantity > 0 AND p.deleted_at IS NULL
    ORDER BY p.name ASC, pl.id ASC
", [':b' => $branchId]);

$uniqueProductsMap = [];
foreach ($productsWithLots as $row) {
    $pid = (int)$row['product_id'];
    if (!isset($uniqueProductsMap[$pid])) {
        $uniqueProductsMap[$pid] = [
            'id'      => $pid,
            'name'    => $row['product_name'],
            'barcode' => $row['barcode']
        ];
    }
}
$uniqueProducts = array_values($uniqueProductsMap);

$totalActiveDamagedQty = 0.00;
foreach ($damagedList as $d) {
    if ($d['status'] === 'active') {
        $totalActiveDamagedQty += (float)$d['quantity'];
    }
}

View::render('inventory/damaged', [
    'damagedList'           => $damagedList,
    'productsWithLots'      => $productsWithLots,
    'uniqueProducts'        => $uniqueProducts,
    'totalActiveDamagedQty' => $totalActiveDamagedQty,
    'branchId'              => $branchId,
    'statusFilter'          => $statusFilter,
    'activeBranchName'      => $user['active_branch_name'] ?? 'Current Branch'
], 'Damaged & Wastage Stock - Curtiss POS');
