<?php
/**
 * Curtiss POS - Stock Transfers Controller (Redesign Theme & Design Rules Enforced)
 * Fixes SQLSTATE[HY093] parameter binding bug and delegates to inventory/transfers view.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Database.php';

Auth::requireAuth();

if (!Auth::hasPermission('view_stock_transfers') && !Auth::hasPermission('inventory.view')) {
    View::renderBlank('errors/403');
    exit();
}

$user = Auth::user();
$userId = (int)$user['id'];
$branchId = (int)($_GET['branch_id'] ?? $user['active_branch_id'] ?? 1);
$statusFilter = trim($_GET['status'] ?? '');

$branches = Database::fetchAll("SELECT id, name FROM branches WHERE status = 'active' ORDER BY name ASC");

$params = [':from_b' => $branchId, ':to_b' => $branchId];
$whereStatus = "";
if (!empty($statusFilter)) {
    $whereStatus = " AND st.status = :status ";
    $params[':status'] = $statusFilter;
}

// FIX PDO SQLSTATE[HY093] Invalid parameter number error:
// Use distinct named parameters (:from_b and :to_b) instead of reusing :b twice
$transfers = Database::fetchAll("
    SELECT st.*, fb.name AS from_branch_name, tb.name AS to_branch_name, u.username AS creator_name
    FROM stock_transfers st
    JOIN branches fb ON st.from_branch_id = fb.id
    JOIN branches tb ON st.to_branch_id = tb.id
    JOIN users u ON st.created_by = u.id
    WHERE (st.from_branch_id = :from_b OR st.to_branch_id = :to_b) {$whereStatus}
    ORDER BY st.id DESC LIMIT 100
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

View::render('inventory/transfers', [
    'transfers'        => $transfers,
    'branches'         => $branches,
    'productsWithLots' => $productsWithLots,
    'uniqueProducts'   => $uniqueProducts,
    'branchId'         => $branchId,
    'statusFilter'     => $statusFilter,
    'activeBranchName' => $user['active_branch_name'] ?? 'Current Branch'
], 'Branch Stock Transfers - Curtiss POS');
