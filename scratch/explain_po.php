<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

$sql = "
    EXPLAIN SELECT 
        po.*,
        s.name AS supplier_name,
        s.supplier_code,
        b.name AS branch_name,
        u.username AS creator_name,
        u2.username AS approver_name
    FROM purchase_orders po
    JOIN suppliers s ON po.supplier_id = s.id
    JOIN branches b ON po.branch_id = b.id
    JOIN users u ON po.created_by = u.id
    LEFT JOIN users u2 ON po.approved_by = u2.id
    WHERE po.branch_id = :branch_id AND po.deleted_at IS NULL
    ORDER BY po.id DESC
    LIMIT 25 OFFSET 0
";

$explain = Database::fetchAll($sql, [':branch_id' => 1]);
foreach ($explain as $row) {
    echo "table: {$row['table']} | type: {$row['type']} | possible_keys: {$row['possible_keys']} | key: {$row['key']} | rows: {$row['rows']} | Extra: {$row['Extra']}\n";
}
