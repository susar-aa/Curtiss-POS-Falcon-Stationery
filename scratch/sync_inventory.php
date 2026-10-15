<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

$pdo = Database::getInstance()->getConnection();

// Synchronize branch_inventory.quantity with total active product_lots.remaining_quantity
$pdo->exec("
    UPDATE branch_inventory bi
    JOIN (
        SELECT product_id, branch_id, SUM(remaining_quantity) AS total_remaining
        FROM product_lots
        WHERE status = 'active'
        GROUP BY product_id, branch_id
    ) pl_sum ON bi.product_id = pl_sum.product_id AND bi.branch_id = pl_sum.branch_id
    SET bi.quantity = pl_sum.total_remaining
");

echo "SYNCHRONIZED branch_inventory with active product_lots remaining quantity.\n";
