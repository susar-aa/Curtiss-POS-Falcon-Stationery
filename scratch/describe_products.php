<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

$indexes = Database::fetchAll("SHOW INDEX FROM branch_inventory");
foreach ($indexes as $idx) {
    echo "Index: {$idx['Key_name']} | Column: {$idx['Column_name']} | Non_unique: {$idx['Non_unique']}\n";
}
