<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

$whereSql = "p.deleted_at IS NULL";
$params = [':branch_id' => 1];

$sql = "
    SELECT 
        p.id,
        COALESCE(p.sku, p.barcode) AS sku,
        p.barcode,
        p.brand_id,
        COALESCE(b.name, 'Unassigned') AS brand_name,
        p.category_id,
        COALESCE(c.name, 'Uncategorized') AS category_name,
        p.name,
        p.supplier_id,
        COALESCE(s.name, 'No Supplier') AS supplier_name,
        p.cost,
        p.selling_price,
        COALESCE(bi.quantity, 0.00) AS opening_stock,
        COALESCE(bi.low_stock_threshold, p.low_stock_threshold) AS low_stock_threshold,
        p.status,
        p.created_at,
        p.updated_at
    FROM products p
    LEFT JOIN brands b ON p.brand_id = b.id
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN suppliers s ON p.supplier_id = s.id
    LEFT JOIN branch_inventory bi ON p.id = bi.product_id AND bi.branch_id = :branch_id
    WHERE $whereSql
    ORDER BY p.id DESC
    LIMIT 15 OFFSET 0
";

try {
    $items = Database::fetchAll($sql, $params);
    echo "ITEMS FOUND: " . count($items) . "\n";
} catch (Throwable $e) {
    echo "QUERY EXCEPTION: " . $e->getMessage() . "\n";
}
