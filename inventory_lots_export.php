<?php
/**
 * Curtiss POS - Product Price & Cost Lots CSV Exporter
 * Preserves SKU & Barcode leading zeros for seamless Excel spreadsheet viewing.
 * Scoped to active branch and respects active filters (active_only, search).
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
$activeOnly = isset($_GET['active_only']) && (string)$_GET['active_only'] === '1';
$search = trim($_GET['search'] ?? '');

$rawLots = Inventory::getLots(null, $branchId ?: null, $activeOnly);

// Group lots by Product ID
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

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=curtiss_price_lots_branch_' . $branchId . '_' . date('Y-m-d_H-i-s') . '.csv');

// Print UTF-8 BOM so Excel opens with proper encoding
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

fputcsv($output, [
    'Barcode',
    'Product Name',
    'Brand',
    'Category',
    'Active Lots Count',
    'Total Lot Stock',
    'Default Cost (LKR)',
    'Default Selling Price (LKR)'
]);

foreach ($groupedProducts as $p) {
    $barcode = (string)$p['barcode'];

    fputcsv($output, [
        '="' . $barcode . '"',
        $p['product_name'],
        $p['brand_name'],
        $p['category_name'],
        (int)$p['active_lots_count'],
        number_format((float)$p['total_lot_stock'], 2, '.', ''),
        number_format((float)$p['default_cost'], 2, '.', ''),
        number_format((float)$p['default_price'], 2, '.', '')
    ]);
}

fclose($output);
exit();
