<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

echo "--- purchase_orders columns ---\n";
foreach (Database::fetchAll("DESCRIBE purchase_orders") as $c) {
    echo "{$c['Field']} ({$c['Type']})\n";
}

echo "\n--- purchase_orders indexes ---\n";
foreach (Database::fetchAll("SHOW INDEX FROM purchase_orders") as $idx) {
    echo "Index: {$idx['Key_name']} | Column: {$idx['Column_name']} | Non_unique: {$idx['Non_unique']}\n";
}

echo "\n--- purchase_order_items columns ---\n";
foreach (Database::fetchAll("DESCRIBE purchase_order_items") as $c) {
    echo "{$c['Field']} ({$c['Type']})\n";
}

echo "\n--- purchase_order_items indexes ---\n";
foreach (Database::fetchAll("SHOW INDEX FROM purchase_order_items") as $idx) {
    echo "Index: {$idx['Key_name']} | Column: {$idx['Column_name']} | Non_unique: {$idx['Non_unique']}\n";
}
