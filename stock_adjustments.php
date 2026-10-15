<?php
/**
 * Curtiss POS - Stock Adjustments Controller (Redesign Theme & Design Rules Enforced)
 * Queries adjustments and available product lots and delegates to inventory/adjustments view.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Database.php';

Auth::requireAuth();

if (!Auth::hasPermission('manage_stock_adjustments') && !Auth::hasPermission('inventory.view')) {
    View::renderBlank('errors/403');
    exit();
}

$user = Auth::user();
$userId = (int)$user['id'];
$branchId = (int)($_GET['branch_id'] ?? $user['active_branch_id'] ?? 1);
$typeFilter = trim($_GET['adjustment_type'] ?? '');

$params = [':b' => $branchId];
$whereType = "";
if (!empty($typeFilter)) {
    $whereType = " AND sa.adjustment_type = :atype ";
    $params[':atype'] = $typeFilter;
}

$adjustments = Database::fetchAll("
    SELECT sa.*, p.name AS product_name, p.barcode, pl.lot_number, u.username AS creator_name
    FROM stock_adjustments sa
    JOIN products p ON sa.product_id = p.id
    JOIN product_lots pl ON sa.lot_id = pl.id
    JOIN users u ON sa.created_by = u.id
    WHERE sa.branch_id = :b {$whereType}
    ORDER BY sa.id DESC LIMIT 100
", $params);

$productsWithLots = Database::fetchAll("
    SELECT p.id AS product_id, p.name AS product_name, p.barcode, pl.id AS lot_id, pl.lot_number, pl.cost, pl.selling_price, pl.remaining_quantity
    FROM products p
    JOIN product_lots pl ON p.id = pl.product_id
    WHERE pl.branch_id = :b AND pl.status = 'active' AND p.deleted_at IS NULL
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

View::render('inventory/adjustments', [
    'adjustments'      => $adjustments,
    'productsWithLots' => $productsWithLots,
    'uniqueProducts'   => $uniqueProducts,
    'branchId'         => $branchId,
    'typeFilter'       => $typeFilter,
    'activeBranchName' => $user['active_branch_name'] ?? 'Current Branch'
], 'Stock Adjustments - Curtiss POS');
