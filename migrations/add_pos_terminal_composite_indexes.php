<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

$pdo = Database::getInstance()->getConnection();

$queries = [
    "ALTER TABLE sales ADD INDEX idx_sales_branch_status_id (branch_id, status, id)",
    "ALTER TABLE sale_items ADD INDEX idx_si_sale_lot (sale_id, lot_id)"
];

foreach ($queries as $sql) {
    try {
        $pdo->exec($sql);
        echo "SUCCESS: " . $sql . PHP_EOL;
    } catch (Throwable $e) {
        echo "NOTICE: " . $sql . " => " . $e->getMessage() . PHP_EOL;
    }
}
