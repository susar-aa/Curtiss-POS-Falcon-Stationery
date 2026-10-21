<?php
/**
 * Migration: Chart of Accounts Optimization & Performance Indexes
 * Adds composite and covering indexes for fast filtering and searching.
 */

require_once __DIR__ . '/../config/config.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "Starting Chart of Accounts migrations...\n";

    $existingIndexes = [];
    $stmt = $db->query("SHOW INDEX FROM chart_of_accounts");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existingIndexes[$row['Key_name']] = true;
    }

    // 1. Index on status and account_type
    if (!isset($existingIndexes['idx_coa_stat_type'])) {
        $db->exec("ALTER TABLE chart_of_accounts ADD INDEX idx_coa_stat_type (status, account_type)");
        echo "Added index idx_coa_stat_type on chart_of_accounts\n";
    } else {
        echo "Index idx_coa_stat_type already exists\n";
    }

    // 2. Index on branch_id and status
    if (!isset($existingIndexes['idx_coa_branch_status'])) {
        $db->exec("ALTER TABLE chart_of_accounts ADD INDEX idx_coa_branch_status (branch_id, status)");
        echo "Added index idx_coa_branch_status on chart_of_accounts\n";
    } else {
        echo "Index idx_coa_branch_status already exists\n";
    }

    // 3. Index on account_name
    if (!isset($existingIndexes['idx_coa_name'])) {
        $db->exec("ALTER TABLE chart_of_accounts ADD INDEX idx_coa_name (account_name)");
        echo "Added index idx_coa_name on chart_of_accounts\n";
    } else {
        echo "Index idx_coa_name already exists\n";
    }

    echo "Chart of Accounts migration completed successfully.\n";
} catch (Throwable $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
