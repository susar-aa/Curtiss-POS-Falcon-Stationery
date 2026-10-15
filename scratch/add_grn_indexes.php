<?php
require_once __DIR__ . '/../config/config.php';

$indexes = Database::fetchAll("SHOW INDEX FROM grns");
$names = array_unique(array_column($indexes, 'Key_name'));

if (!in_array('idx_grn_br_id', $names)) {
    Database::execute("ALTER TABLE grns ADD INDEX idx_grn_br_id (branch_id, id)");
    echo "Added idx_grn_br_id\n";
} else {
    echo "idx_grn_br_id already exists\n";
}

if (!in_array('idx_grn_br_status', $names)) {
    Database::execute("ALTER TABLE grns ADD INDEX idx_grn_br_status (branch_id, status)");
    echo "Added idx_grn_br_status\n";
} else {
    echo "idx_grn_br_status already exists\n";
}

if (!in_array('idx_grn_br_supp', $names)) {
    Database::execute("ALTER TABLE grns ADD INDEX idx_grn_br_supp (branch_id, supplier_id)");
    echo "Added idx_grn_br_supp\n";
} else {
    echo "idx_grn_br_supp already exists\n";
}

echo "GRN indexes verified.\n";
