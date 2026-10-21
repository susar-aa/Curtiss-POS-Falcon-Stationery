<?php
require_once 'c:/xampp/htdocs/CURTISS/FalconStationery/POS/config/config.php';
$pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);

$tables = ['brands', 'categories', 'suppliers', 'products', 'product_lots', 'branch_inventory', 'product_suppliers', 'stock_ledger'];
foreach ($tables as $t) {
    $c = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
    echo str_pad($t, 25) . ": $c rows\n";
}
