<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

$products = Database::fetchAll("SELECT id, sku, barcode, brand_id, category_id, name, status, deleted_at FROM products LIMIT 30");
echo "Fetched " . count($products) . " products:\n";
foreach ($products as $p) {
    echo "ID: {$p['id']} | Name: {$p['name']} | BC: {$p['barcode']} | SKU: {$p['sku']} | Status: {$p['status']} | BrandID: " . var_export($p['brand_id'], true) . " | CatID: " . var_export($p['category_id'], true) . "\n";
}
