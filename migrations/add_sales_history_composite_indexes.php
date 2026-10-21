<?php
/**
 * Migration: Add composite indexes for Sales History optimization
 */

require_once __DIR__ . '/../config/config.php';

try {
    $db = Database::getInstance()->getConnection();

    // Check existing indexes on sales
    $stmt = $db->query("SHOW INDEX FROM sales");
    $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $indexNames = array_unique(array_column($indexes, 'Key_name'));

    if (!in_array('idx_sales_branch_created', $indexNames)) {
        echo "Adding index idx_sales_branch_created (branch_id, created_at, id) on sales...\n";
        $db->exec("ALTER TABLE sales ADD INDEX idx_sales_branch_created (branch_id, created_at, id)");
        echo "Index idx_sales_branch_created added successfully.\n";
    } else {
        echo "Index idx_sales_branch_created already exists on sales.\n";
    }

    echo "Migration completed successfully.\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
