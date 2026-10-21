<?php
/**
 * Curtiss POS - Branch Inventory CSV Exporter
 * Preserves SKU & Barcode leading zeros for seamless Excel spreadsheet viewing.
 * Enforces Special User presentation masking for sensitive cost fields.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Inventory.php';
require_once __DIR__ . '/core/MaskingHelper.php';

Auth::requireAuth();

if (!Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER, ROLE_ACCOUNTANT, ROLE_CASHIER, ROLE_SPECIAL_USER)) {
    View::renderBlank('errors/403');
    exit();
}

$user = Auth::user();
$activeBranchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? $user['active_branch_id'] ?? 1);
$activeBranchName = $user['active_branch_name'] ?? ('Branch #' . $activeBranchId);

$filters = [
    'search'       => trim($_GET['search'] ?? ''),
    'brand_id'     => (int)($_GET['brand_id'] ?? 0),
    'category_id'  => (int)($_GET['category_id'] ?? 0),
    'stock_status' => trim($_GET['stock_status'] ?? '')
];

$res = Inventory::getInventoryList($activeBranchId, $filters, 1, 10000);
$items = $res['items'];

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=curtiss_branch_' . $activeBranchId . '_inventory_' . date('Y-m-d_H-i-s') . '.csv');

// Print UTF-8 BOM so Excel opens with proper encoding
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// Headers
fputcsv($output, [
    'Barcode',
    'Product Name',
    'Brand',
    'Category',
    'Default Cost (LKR)',
    'Default Selling Price (LKR)',
    'Available Stock',
    'Stock Status',
    'Active Lots Count'
]);

foreach ($items as $it) {
    $cost = (float)$it['default_cost'];
    $price = (float)$it['default_price'];
    $qty = (float)$it['available_quantity'];
    $threshold = (float)$it['low_stock_threshold'];
    $statusText = ($qty <= 0) ? 'Out of Stock' : (($qty <= $threshold) ? 'Low Stock' : 'In Stock');

    if (MaskingHelper::isSpecialUser()) {
        $cost = MaskingHelper::maskValue($cost);
        $qty = MaskingHelper::maskQuantity($qty);
    }

    $barcode = (string)$it['barcode'];

    // Excel text format wrapper so leading zeros are preserved when opened in Excel
    $barcodeFormatted = '="' . $barcode . '"';

    fputcsv($output, [
        $barcodeFormatted,
        $it['product_name'],
        $it['brand_name'] ?? 'Unassigned',
        $it['category_name'] ?? 'Uncategorized',
        is_numeric($cost) ? number_format($cost, 2, '.', '') : $cost,
        number_format($price, 2, '.', ''),
        is_numeric($qty) ? number_format($qty, 2, '.', '') : $qty,
        $statusText,
        (int)$it['active_lots_count']
    ]);
}

fclose($output);
exit();
