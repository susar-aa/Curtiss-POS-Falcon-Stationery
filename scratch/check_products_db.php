<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

$allProds = Database::fetchAll("SELECT p.id, p.name, p.barcode, p.sku, p.brand_id, p.category_id, p.status, p.deleted_at FROM products WHERE p.deleted_at IS NULL");
echo "Total Active Products (not deleted): " . count($allProds) . "\n\n";

foreach ($allProds as $p) {
    echo "ID: {$p['id']} | Name: {$p['name']} | BC: {$p['barcode']} | SKU: {$p['sku']} | BrandID: " . var_export($p['brand_id'], true) . " | CatID: " . var_export($p['category_id'], true) . " | Status: {$p['status']}\n";
}
