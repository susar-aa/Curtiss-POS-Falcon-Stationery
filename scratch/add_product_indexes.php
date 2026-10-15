<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

$db = Database::getInstance()->getConnection();

$indexesToAdd = [
    'idx_prod_sku'        => 'ALTER TABLE products ADD INDEX idx_prod_sku (sku)',
    'idx_prod_deleted_at' => 'ALTER TABLE products ADD INDEX idx_prod_deleted_at (deleted_at)',
    'idx_prod_del_status' => 'ALTER TABLE products ADD INDEX idx_prod_del_status (deleted_at, status)',
    'idx_prod_del_brand'  => 'ALTER TABLE products ADD INDEX idx_prod_del_brand (deleted_at, brand_id)',
    'idx_prod_del_cat'    => 'ALTER TABLE products ADD INDEX idx_prod_del_cat (deleted_at, category_id)',
    'idx_prod_del_supp'   => 'ALTER TABLE products ADD INDEX idx_prod_del_supp (deleted_at, supplier_id)'
];

$existing = array_column(Database::fetchAll("SHOW INDEX FROM products"), 'Key_name');

foreach ($indexesToAdd as $name => $sql) {
    if (!in_array($name, $existing, true)) {
        $db->exec($sql);
        echo "Created index $name\n";
    } else {
        echo "Index $name already exists\n";
    }
}
echo "Done.\n";
