<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Sale.php';
require_once __DIR__ . '/../core/Customer.php';

$res = Sale::searchProducts(1, 'atlas');
echo "Product Search for 'atlas': " . count($res) . " results\n";
print_r($res);

$res2 = Sale::searchProducts(1, 'a');
echo "Product Search for 'a': " . count($res2) . " results\n";

$cust = Customer::all(['search' => 'te', 'status' => 'active'], 1, 10);
echo "Customer Search for 'te': " . count($cust['items']) . " results\n";
print_r($cust['items']);
