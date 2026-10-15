<?php
/**
 * Curtiss POS - Stage 18 Database Migration Script
 * Adds offline_uuid UNIQUE column to sales table for Stage 18 Offline POS Billing & Synchronization.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "Starting Stage 18 Migration...\n";

    // 1. Add offline_uuid column to sales table if not exists
    $columnCheck = Database::fetchOne("
        SELECT COLUMN_NAME 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
          AND TABLE_NAME = 'sales' 
          AND COLUMN_NAME = 'offline_uuid'
    ");

    if (!$columnCheck) {
        $db->exec("ALTER TABLE `sales` ADD COLUMN `offline_uuid` VARCHAR(64) NULL UNIQUE AFTER `invoice_no`;");
        echo "  - Column 'offline_uuid' added to 'sales' table with UNIQUE constraint.\n";
    } else {
        echo "  - Column 'offline_uuid' already exists on 'sales' table.\n";
    }

    echo "Stage 18 Database Migration Completed Successfully!\n";

} catch (Throwable $e) {
    echo "Migration Error: " . $e->getMessage() . "\n";
    exit(1);
}
