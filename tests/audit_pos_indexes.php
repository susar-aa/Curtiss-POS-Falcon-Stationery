<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

$tables = ['sales', 'sale_items', 'payments', 'product_lots', 'branch_inventory', 'stock_ledger', 'products', 'customers', 'discount_rules'];

foreach ($tables as $tbl) {
    echo "--- TABLE: {$tbl} ---\n";
    $indexes = Database::fetchAll("SHOW INDEX FROM `{$tbl}`");
    $byKey = [];
    foreach ($indexes as $idx) {
        $key = $idx['Key_name'];
        $byKey[$key][] = $idx['Column_name'];
    }
    foreach ($byKey as $name => $cols) {
        echo "  - {$name} (" . implode(', ', $cols) . ")\n";
    }
}
