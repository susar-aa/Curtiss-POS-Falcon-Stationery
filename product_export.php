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
            SELECT p.id, p.sku, p.barcode, b.name AS brand_name, c.name AS category_name,
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
header('Content-Disposition: attachment; filename=falcon_products_master_' . date('Y-m-d_H-i-s') . '.csv');

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

// Batch-fetch active price lots for all exported products (Rule 5: No N+1 queries)
$branchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? 1);
$productIds = array_column($products, 'id');
$lotsByProduct = [];

if (!empty($productIds)) {
    $inClause = implode(',', array_map('intval', $productIds));
    $lots = Database::fetchAll("
        SELECT pl.product_id, pl.cost, pl.selling_price, pl.remaining_quantity
        FROM product_lots pl
        WHERE pl.product_id IN ($inClause)
          AND pl.branch_id = :branch_id
          AND pl.status = 'active'
          AND pl.remaining_quantity > 0
        ORDER BY pl.id ASC
    ", [':branch_id' => $branchId]);

    foreach ($lots as $l) {
        $lotsByProduct[(int)$l['product_id']][] = $l;
    }
}

foreach ($products as $p) {
    $pId = (int)$p['id'];
    $barcode = (string)$p['barcode'];
    $sku = (string)(!empty($p['sku']) ? $p['sku'] : $barcode);
    $skuFormatted = '="' . $sku . '"';
    $barcodeFormatted = '="' . $barcode . '"';

    if (isset($lotsByProduct[$pId]) && count($lotsByProduct[$pId]) > 1) {
        // Multiple active price lots exist: export each price lot as a distinct row
        foreach ($lotsByProduct[$pId] as $lot) {
            $lotCost = (float)$lot['cost'];
            if (MaskingHelper::isSpecialUser()) {
                $lotCost = MaskingHelper::maskValue($lotCost);
            }
            $lotPrice = (float)$lot['selling_price'];
            $lotStock = (float)$lot['remaining_quantity'];

            fputcsv($output, [
                $skuFormatted,
                $barcodeFormatted,
                $p['brand_name'] ?? 'Unassigned',
                $p['category_name'] ?? 'Uncategorized',
                $p['name'],
                $p['supplier_name'] ?? 'No Supplier',
                number_format($lotCost, 2, '.', ''),
                number_format($lotPrice, 2, '.', ''),
                number_format($lotStock, 2, '.', ''),
                number_format((float)($p['low_stock_threshold'] ?? 10), 2, '.', ''),
                ucfirst($p['status'])
            ]);
        }
    } else {
        // Standard single product row
        $cost = (float)$p['cost'];
        if (MaskingHelper::isSpecialUser()) {
            $cost = MaskingHelper::maskValue($cost);
        }
        $stock = isset($lotsByProduct[$pId][0]) ? (float)$lotsByProduct[$pId][0]['remaining_quantity'] : (float)($p['opening_stock'] ?? 0);

        fputcsv($output, [
            $skuFormatted,
            $barcodeFormatted,
            $p['brand_name'] ?? 'Unassigned',
            $p['category_name'] ?? 'Uncategorized',
            $p['name'],
            $p['supplier_name'] ?? 'No Supplier',
            number_format($cost, 2, '.', ''),
            number_format((float)$p['selling_price'], 2, '.', ''),
            number_format($stock, 2, '.', ''),
            number_format((float)($p['low_stock_threshold'] ?? 10), 2, '.', ''),
            ucfirst($p['status'])
        ]);
    }
}

fclose($output);
exit();
