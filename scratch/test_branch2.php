<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Sale.php';

$res = Sale::searchProducts(2, 'atlas');
echo "Branch 2 (Colombo) Product Search for 'atlas': " . count($res) . " results\n";
print_r($res);
