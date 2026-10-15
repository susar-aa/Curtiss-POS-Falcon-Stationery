<?php
/**
 * Curtiss POS - Product Master CSV & Excel Exporter (Section 1)
 * Preserves SKU leading zeros (e.g. '001', '00001') for seamless re-import.
 * Enforces Special User presentation masking for sensitive cost fields.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Product.php';
require_once __DIR__ . '/core/MaskingHelper.php';

Auth::requirePermission('products.export');

$selectedIds = $_GET['ids'] ?? '';
$filters = [
    'search'      => trim($_GET['search'] ?? ''),
    'brand_id'    => (int)($_GET['brand_id'] ?? 0),
    'category_id' => (int)($_GET['category_id'] ?? 0),
    'supplier_id' => (int)($_GET['supplier_id'] ?? 0),
    'status'      => trim($_GET['status'] ?? '')
];

// If specific IDs selected
if (!empty($selectedIds)) {
    $idsArr = array_filter(array_map('intval', explode(',', $selectedIds)));
    if (!empty($idsArr)) {
        $inSql = implode(',', $idsArr);
        $products = Database::fetchAll("
            SELECT p.id, COALESCE(p.sku, p.barcode) AS sku, p.barcode, b.name AS brand_name, c.name AS category_name,
                   p.name, s.name AS supplier_name, p.cost, p.selling_price, p.opening_stock, p.low_stock_threshold, p.status
            FROM products p
            LEFT JOIN brands b ON p.brand_id = b.id
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN suppliers s ON p.supplier_id = s.id
            WHERE p.id IN ($inSql) AND p.deleted_at IS NULL
            ORDER BY p.id DESC
        ");
    } else {
        $res = Product::getList($filters, 1, 10000);
        $products = $res['items'];
    }
} else {
    $res = Product::getList($filters, 1, 10000);
    $products = $res['items'];
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=curtiss_products_master_' . date('Y-m-d_H-i-s') . '.csv');

// Print UTF-8 BOM so Excel opens with proper encoding
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// Headers
fputcsv($output, [
    'SKU',
    'Barcode',
    'Brand',
    'Category',
    'Product Name',
    'Supplier',
    'Cost (LKR)',
    'Selling Price (LKR)',
    'Opening Stock',
    'Low Stock Threshold',
    'Status'
]);

foreach ($products as $p) {
    $cost = (float)$p['cost'];
    if (MaskingHelper::isSpecialUser()) {
        $cost = MaskingHelper::maskValue($cost);
    }

    $sku = (string)($p['sku'] ?? $p['barcode']);
    $barcode = (string)$p['barcode'];

    // Excel text format wrapper so leading zeros are preserved when opened in Excel
    $skuFormatted = '="' . $sku . '"';
    $barcodeFormatted = '="' . $barcode . '"';

    fputcsv($output, [
        $skuFormatted,
        $barcodeFormatted,
        $p['brand_name'] ?? 'Unassigned',
        $p['category_name'] ?? 'Uncategorized',
        $p['name'],
        $p['supplier_name'] ?? 'No Supplier',
        number_format($cost, 2, '.', ''),
        number_format((float)$p['selling_price'], 2, '.', ''),
        number_format((float)$p['opening_stock'], 2, '.', ''),
        number_format((float)($p['low_stock_threshold'] ?? 10), 2, '.', ''),
        ucfirst($p['status'])
    ]);
}

fclose($output);
exit();
