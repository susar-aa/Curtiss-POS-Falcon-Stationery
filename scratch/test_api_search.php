<?php
define('CURTISS_POS', true);
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['branch_id'] = 1;

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Sale.php';
require_once __DIR__ . '/../core/Customer.php';

echo "--- Testing Product Search 'atlas' ---\n";
$products = Sale::searchProducts(1, 'atlas');
echo "Found " . count($products) . " products:\n";
foreach ($products as $p) {
    echo "ID: {$p['product_id']} | Name: {$p['product_name']} | Price: {$p['default_price']} | Stock: {$p['total_branch_stock']} | Lots: {$p['active_lots_count']}\n";
}

echo "\n--- Testing Product Search 'a' ---\n";
$productsA = Sale::searchProducts(1, 'a');
echo "Found " . count($productsA) . " products for 'a'\n";

echo "\n--- Testing Customer Search 'te' ---\n";
$customers = Customer::all(['search' => 'te', 'status' => 'active'], 1, 10);
echo "Found " . count($customers['items']) . " customers:\n";
foreach ($customers['items'] as $c) {
    echo "ID: {$c['id']} | Name: {$c['name']} | Phone: {$c['phone']}\n";
}
