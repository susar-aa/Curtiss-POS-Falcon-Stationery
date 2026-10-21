<?php
/**
 * Migration: Add composite indexes for Sales Returns optimization
 */

require_once __DIR__ . '/../config/config.php';

try {
    $db = Database::getInstance()->getConnection();

    $stmt = $db->query("SHOW INDEX FROM sales_returns");
    $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $indexNames = array_unique(array_column($indexes, 'Key_name'));

    if (!in_array('idx_returns_branch_id', $indexNames)) {
        echo "Adding index idx_returns_branch_id (branch_id, id) on sales_returns...\n";
        $db->exec("ALTER TABLE sales_returns ADD INDEX idx_returns_branch_id (branch_id, id)");
        echo "Index idx_returns_branch_id added successfully.\n";
    } else {
        echo "Index idx_returns_branch_id already exists on sales_returns.\n";
    }

    if (!in_array('idx_returns_branch_created', $indexNames)) {
        echo "Adding index idx_returns_branch_created (branch_id, created_at, id) on sales_returns...\n";
        $db->exec("ALTER TABLE sales_returns ADD INDEX idx_returns_branch_created (branch_id, created_at, id)");
        echo "Index idx_returns_branch_created added successfully.\n";
    } else {
        echo "Index idx_returns_branch_created already exists on sales_returns.\n";
    }

    echo "Migration completed successfully.\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
