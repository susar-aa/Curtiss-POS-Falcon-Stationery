<?php
/**
 * Curtiss POS - Bank Accounts & Cash Deposits Enhancements Migration
 * Adds soft-delete column deleted_at and composite indexes to bank_accounts and bank_deposits tables.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "Starting Bank Accounts & Cash Deposits schema enhancements migration...\n";

    // 1. Check & Add deleted_at column to bank_accounts
    $colDeleted = Database::fetchOne("SHOW COLUMNS FROM bank_accounts LIKE 'deleted_at'");
    if (!$colDeleted) {
        $db->exec("ALTER TABLE bank_accounts ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL AFTER updated_at");
        echo "[OK] Added 'deleted_at' column to bank_accounts table.\n";
    } else {
        echo "[INFO] Column 'deleted_at' already exists on bank_accounts table.\n";
    }

    // 2. Composite indexes on bank_accounts
    $baIndexes = Database::fetchAll("SHOW INDEX FROM bank_accounts");
    $baIndexNames = array_column($baIndexes, 'Key_name');

    if (!in_array('idx_ba_del_status_id', $baIndexNames, true)) {
        $db->exec("ALTER TABLE bank_accounts ADD INDEX idx_ba_del_status_id (deleted_at, status, id)");
        echo "[OK] Added composite index 'idx_ba_del_status_id' to bank_accounts.\n";
    } else {
        echo "[INFO] Composite index 'idx_ba_del_status_id' already exists.\n";
    }

    if (!in_array('idx_ba_branch_del', $baIndexNames, true)) {
        $db->exec("ALTER TABLE bank_accounts ADD INDEX idx_ba_branch_del (branch_id, deleted_at)");
        echo "[OK] Added composite index 'idx_ba_branch_del' to bank_accounts.\n";
    } else {
        echo "[INFO] Composite index 'idx_ba_branch_del' already exists.\n";
    }

    if (!in_array('idx_ba_account_del', $baIndexNames, true)) {
        $db->exec("ALTER TABLE bank_accounts ADD INDEX idx_ba_account_del (account_id, deleted_at)");
        echo "[OK] Added composite index 'idx_ba_account_del' to bank_accounts.\n";
    } else {
        echo "[INFO] Composite index 'idx_ba_account_del' already exists.\n";
    }

    // 3. Composite indexes on bank_deposits
    $bdIndexes = Database::fetchAll("SHOW INDEX FROM bank_deposits");
    $bdIndexNames = array_column($bdIndexes, 'Key_name');

    if (!in_array('idx_bd_ba_date', $bdIndexNames, true)) {
        $db->exec("ALTER TABLE bank_deposits ADD INDEX idx_bd_ba_date (bank_account_id, deposit_date)");
        echo "[OK] Added composite index 'idx_bd_ba_date' to bank_deposits.\n";
    } else {
        echo "[INFO] Composite index 'idx_bd_ba_date' already exists.\n";
    }

    if (!in_array('idx_bd_source_bday', $bdIndexNames, true)) {
        $db->exec("ALTER TABLE bank_deposits ADD INDEX idx_bd_source_bday (source_type, business_day_id)");
        echo "[OK] Added composite index 'idx_bd_source_bday' to bank_deposits.\n";
    } else {
        echo "[INFO] Composite index 'idx_bd_source_bday' already exists.\n";
    }

    // 4. Composite index on journal_lines & journal_entries for bank register queries
    $jlIndexes = Database::fetchAll("SHOW INDEX FROM journal_lines");
    $jlIndexNames = array_column($jlIndexes, 'Key_name');
    if (!in_array('idx_jl_acc_je', $jlIndexNames, true)) {
        $db->exec("ALTER TABLE journal_lines ADD INDEX idx_jl_acc_je (account_id, journal_entry_id)");
        echo "[OK] Added composite index 'idx_jl_acc_je' to journal_lines.\n";
    } else {
        echo "[INFO] Composite index 'idx_jl_acc_je' already exists.\n";
    }

    $jeIndexes = Database::fetchAll("SHOW INDEX FROM journal_entries");
    $jeIndexNames = array_column($jeIndexes, 'Key_name');
    if (!in_array('idx_je_status_date', $jeIndexNames, true)) {
        $db->exec("ALTER TABLE journal_entries ADD INDEX idx_je_status_date (posting_status, entry_date)");
        echo "[OK] Added composite index 'idx_je_status_date' to journal_entries.\n";
    } else {
        echo "[INFO] Composite index 'idx_je_status_date' already exists.\n";
    }

    echo "Migration completed successfully!\n";

} catch (Throwable $e) {
    echo "[-] Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
