<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

$db = Database::getInstance();
$tables = ['brands', 'categories', 'suppliers', 'products', 'branch_inventory', 'product_lots', 'customers', 'gift_vouchers', 'bank_accounts'];

echo "=========================================================\n";
echo " LOCAL XAMPP DATABASE [curtiss_pos] ROW COUNT CHECK \n";
echo "=========================================================\n";

$pdo = Database::getInstance()->getConnection();
foreach ($tables as $t) {
    $count = $pdo->query("SELECT COUNT(*) FROM `{$t}`")->fetchColumn();
    echo str_pad($t, 20) . ": " . $count . " rows\n";
}
