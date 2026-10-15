<?php
require_once __DIR__ . '/../config/config.php';
$p = Database::fetchAll("SELECT id, name, sku, barcode, has_expiry, status FROM products");
foreach ($p as $r) {
    echo "ID: {$r['id']} | Name: {$r['name']} | SKU: {$r['sku']} | Exp: {$r['has_expiry']} | Status: {$r['status']}\n";
}
