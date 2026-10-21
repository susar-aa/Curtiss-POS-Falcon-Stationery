<?php
require_once 'c:/xampp/htdocs/CURTISS/FalconStationery/POS/config/config.php';
$pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

$tables = ['brands', 'categories', 'suppliers', 'products', 'product_suppliers', 'branch_inventory', 'product_lots', 'stock_ledger'];

$sqlOutput = "-- ==========================================================\n";
$sqlOutput .= "-- Falcon POS - Live Product Catalog Import Deployment Script\n";
$sqlOutput .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
$sqlOutput .= "-- Total Products: 509\n";
$sqlOutput .= "-- ==========================================================\n\n";
$sqlOutput .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

foreach ($tables as $t) {
    $rows = $pdo->query("SELECT * FROM `$t`")->fetchAll();
    if (empty($rows)) continue;

    $sqlOutput .= "-- Table: $t (" . count($rows) . " rows)\n";
    $cols = array_keys($rows[0]);
    $colsList = implode('`, `', $cols);

    $sqlOutput .= "INSERT INTO `$t` (`$colsList`) VALUES\n";
    $valLines = [];
    foreach ($rows as $r) {
        $vals = [];
        foreach ($r as $colVal) {
            if ($colVal === null) {
                $vals[] = "NULL";
            } else {
                $vals[] = $pdo->quote($colVal);
            }
        }
        $valLines[] = "(" . implode(', ', $vals) . ")";
    }
    $sqlOutput .= implode(",\n", $valLines) . ";\n\n";
}

$sqlOutput .= "SET FOREIGN_KEY_CHECKS = 1;\n";

$targetSqlPath = 'c:/xampp/htdocs/CURTISS/FalconStationery/POS/database/products_import_live.sql';
file_put_contents($targetSqlPath, $sqlOutput);
echo "Generated $targetSqlPath (" . number_format(filesize($targetSqlPath) / 1024, 2) . " KB)\n";
