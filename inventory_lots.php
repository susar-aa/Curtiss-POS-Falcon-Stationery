<?php
/**
 * Curtiss POS - Product Price & Cost Lots Controller (Stage 3 + Stage 17 Redesign)
 * Groups lots by product so users can inspect price-lots per product in a slide-in side pane.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Inventory.php';

Auth::requireAuth();

if (!Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER, ROLE_ACCOUNTANT, ROLE_CASHIER) || Auth::isSpecialUser()) {
    View::renderBlank('errors/403');
    exit();
}

$user = Auth::user();

$productId = (int)($_GET['product_id'] ?? 0);
$branchId = (int)($_GET['branch_id'] ?? $user['active_branch_id']);
$activeOnly = isset($_GET['active_only']) && $_GET['active_only'] === '1';
$search = trim($_GET['search'] ?? '');

$rawLots = Inventory::getLots($productId ?: null, $branchId ?: null, $activeOnly);

// Group lots by Product ID so only products with price lots are displayed
$groupedProducts = [];
foreach ($rawLots as $lot) {
    $pId = (int)$lot['product_id'];
    if (!isset($groupedProducts[$pId])) {
        $groupedProducts[$pId] = [
            'product_id'        => $pId,
            'product_name'      => $lot['product_name'],
            'barcode'           => $lot['barcode'] ?? '',
            'sku'               => $lot['sku'] ?? 'N/A',
            'brand_name'        => $lot['brand_name'] ?? 'Unassigned',
            'category_name'     => $lot['category_name'] ?? 'Uncategorized',
            'default_cost'      => (float)($lot['cost'] ?? 0),
            'default_price'     => (float)($lot['selling_price'] ?? 0),
            'total_lot_stock'   => 0.00,
            'active_lots_count' => 0,
            'lots'              => []
        ];
    }

    $remQty = (float)$lot['remaining_quantity'];
    $groupedProducts[$pId]['total_lot_stock'] += $remQty;
    if ($remQty > 0 && $lot['status'] === 'active') {
        $groupedProducts[$pId]['active_lots_count']++;
    }
    $groupedProducts[$pId]['lots'][] = $lot;
}

// Filter products by search if query specified
if ($search !== '') {
    $searchLower = strtolower($search);
    $groupedProducts = array_filter($groupedProducts, function($p) use ($searchLower) {
        return str_contains(strtolower($p['product_name']), $searchLower)
            || str_contains(strtolower($p['barcode']), $searchLower)
            || str_contains(strtolower($p['sku']), $searchLower);
    });
}

View::render('inventory/lots', [
    'productsWithLots' => array_values($groupedProducts),
    'productId'        => $productId,
    'branchId'         => $branchId,
    'activeOnly'       => $activeOnly,
    'search'           => $search,
    'activeBranchName' => $user['active_branch_name']
], 'Price & Cost Lots - Curtiss POS');
