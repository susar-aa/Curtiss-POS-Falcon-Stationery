<?php
/**
 * Curtiss POS - Product Price & Cost Lots Controller (Stage 3 + Stage 17 Redesign)
 * Groups lots by product so users can inspect price-lots per product in a slide-in side pane.
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

$productId = (int)($_GET['product_id'] ?? 0);
$activeOnly = isset($_GET['active_only']) && (string)$_GET['active_only'] === '1';
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

// Filter products by universal search if query specified (covers all displayed data columns and monetary formats)
if ($search !== '') {
    $searchLower = strtolower($search);
    $cleanNum = preg_replace('/[^0-9.]/', '', $search);
    $groupedProducts = array_filter($groupedProducts, function($p) use ($searchLower, $cleanNum) {
        $cost = (float)$p['default_cost'];
        $price = (float)$p['default_price'];
        $stock = (float)$p['total_lot_stock'];
        $lotsCount = (int)$p['active_lots_count'];

        $textMatch = str_contains(strtolower($p['product_name']), $searchLower)
            || str_contains(strtolower($p['barcode']), $searchLower)
            || str_contains(strtolower($p['sku']), $searchLower)
            || str_contains(strtolower($p['brand_name']), $searchLower)
            || str_contains(strtolower($p['category_name']), $searchLower)
            || str_contains(strtolower($lotsCount . ' active lots'), $searchLower);

        if ($textMatch) return true;

        if ($cleanNum !== '' && is_numeric($cleanNum)) {
            $numVal = (float)$cleanNum;
            if (abs($cost - $numVal) < 0.01 || abs($price - $numVal) < 0.01 || abs($stock - $numVal) < 0.01) {
                return true;
            }
        }

        $costFormatted = number_format($cost, 2);
        $priceFormatted = number_format($price, 2);
        $stockFormatted = number_format($stock, 2);

        return str_contains(strtolower("lkr {$costFormatted}"), $searchLower)
            || str_contains(strtolower("rs. {$costFormatted}"), $searchLower)
            || str_contains(strtolower("rs {$costFormatted}"), $searchLower)
            || str_contains(strtolower("lkr {$priceFormatted}"), $searchLower)
            || str_contains(strtolower("rs. {$priceFormatted}"), $searchLower)
            || str_contains(strtolower("rs {$priceFormatted}"), $searchLower)
            || str_contains(strtolower("{$costFormatted}"), $searchLower)
            || str_contains(strtolower("{$priceFormatted}"), $searchLower)
            || str_contains(strtolower("{$stockFormatted}"), $searchLower);
    });
}

// Server-side Pagination (Limits: 25, 50, 75, 100, 500)
$totalItems = count($groupedProducts);
$rawLimit = (int)($_GET['limit'] ?? 25);
$limit = in_array($rawLimit, [25, 50, 75, 100, 500], true) ? $rawLimit : 25;
$page = max(1, (int)($_GET['page'] ?? 1));
$totalPages = max(1, (int)ceil($totalItems / $limit));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $limit;
$pagedProducts = array_slice(array_values($groupedProducts), $offset, $limit);

View::render('inventory/lots', [
    'productsWithLots' => $pagedProducts,
    'totalItems'       => $totalItems,
    'page'             => $page,
    'limit'            => $limit,
    'totalPages'       => $totalPages,
    'productId'        => $productId,
    'branchId'         => $branchId,
    'activeOnly'       => $activeOnly,
    'search'           => $search,
    'activeBranchName' => $activeBranchName
], 'Price & Cost Lots - Curtiss POS');
