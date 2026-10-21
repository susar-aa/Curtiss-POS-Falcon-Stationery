<?php
/**
 * Curtiss POS - Supplier Cheques Enhancements Migration
 * Adds soft-delete column deleted_at and composite indexes to supplier_cheques table.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "Starting Supplier Cheques schema enhancements migration...\n";

    // 1. Check & Add deleted_at column
    $colDeleted = Database::fetchOne("SHOW COLUMNS FROM supplier_cheques LIKE 'deleted_at'");
    if (!$colDeleted) {
        $db->exec("ALTER TABLE supplier_cheques ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL AFTER cancellation_reason");
        echo "[OK] Added 'deleted_at' column to supplier_cheques table.\n";
    } else {
        echo "[INFO] Column 'deleted_at' already exists on supplier_cheques table.\n";
    }

    // 2. Composite indexes
    $indexes = Database::fetchAll("SHOW INDEX FROM supplier_cheques");
    $indexNames = array_column($indexes, 'Key_name');

    if (!in_array('idx_sc_del_status_id', $indexNames, true)) {
        $db->exec("ALTER TABLE supplier_cheques ADD INDEX idx_sc_del_status_id (deleted_at, status, id)");
        echo "[OK] Added composite index 'idx_sc_del_status_id' to supplier_cheques.\n";
    } else {
        echo "[INFO] Composite index 'idx_sc_del_status_id' already exists.\n";
    }

    if (!in_array('idx_sc_del_branch_status', $indexNames, true)) {
        $db->exec("ALTER TABLE supplier_cheques ADD INDEX idx_sc_del_branch_status (deleted_at, branch_id, status)");
        echo "[OK] Added composite index 'idx_sc_del_branch_status' to supplier_cheques.\n";
    } else {
        echo "[INFO] Composite index 'idx_sc_del_branch_status' already exists.\n";
    }

    if (!in_array('idx_sc_cheque_num', $indexNames, true)) {
        $db->exec("ALTER TABLE supplier_cheques ADD INDEX idx_sc_cheque_num (cheque_number)");
        echo "[OK] Added index 'idx_sc_cheque_num' to supplier_cheques.\n";
    } else {
        echo "[INFO] Index 'idx_sc_cheque_num' already exists.\n";
    }

    if (!in_array('idx_sc_cheque_date', $indexNames, true)) {
        $db->exec("ALTER TABLE supplier_cheques ADD INDEX idx_sc_cheque_date (cheque_date)");
        echo "[OK] Added index 'idx_sc_cheque_date' to supplier_cheques.\n";
    } else {
        echo "[INFO] Index 'idx_sc_cheque_date' already exists.\n";
    }

    echo "Migration completed successfully!\n";

} catch (Throwable $e) {
    echo "[-] Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
