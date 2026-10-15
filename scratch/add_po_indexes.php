<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

$db = Database::getInstance()->getConnection();

$indexesToAdd = [
    'idx_po_br_del_id'     => 'ALTER TABLE purchase_orders ADD INDEX idx_po_br_del_id (branch_id, deleted_at, id)',
    'idx_po_br_del_status' => 'ALTER TABLE purchase_orders ADD INDEX idx_po_br_del_status (branch_id, deleted_at, status)',
    'idx_po_br_del_supp'   => 'ALTER TABLE purchase_orders ADD INDEX idx_po_br_del_supp (branch_id, deleted_at, supplier_id)'
];

$existing = array_column(Database::fetchAll("SHOW INDEX FROM purchase_orders"), 'Key_name');

foreach ($indexesToAdd as $name => $sql) {
    if (!in_array($name, $existing, true)) {
        $db->exec($sql);
        echo "Created index $name\n";
    } else {
        echo "Index $name already exists\n";
    }
}
echo "Done.\n";
