<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

$tables = ['suppliers', 'purchase_orders', 'purchase_order_items', 'grns', 'grn_items', 'supplier_payables'];
foreach ($tables as $t) {
    echo "=== Table: {$t} ===\n";
    try {
        $cols = Database::fetchAll("DESCRIBE {$t}");
        foreach ($cols as $c) {
            echo " - {$c['Field']} ({$c['Type']}) " . ($c['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . "\n";
        }
    } catch (Exception $e) {
        echo " Error: " . $e->getMessage() . "\n";
    }
    echo "\n";
}
