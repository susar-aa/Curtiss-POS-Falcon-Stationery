<?php
/**
 * Migration Script: Cheque Management Complete Upgrade
 * Adds 1035 (Cheques in Hand) to chart_of_accounts.
 * Extends customer_cheques with deposit, clearing, cancellation, and soft-delete columns.
 * Extends supplier_cheques with cleared_date and bank_ref.
 */

require_once __DIR__ . '/../core/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "=== RUNNING CHEQUE UPGRADE MIGRATION ===\n";

    // 1. Chart of Accounts: 1035 Cheques in Hand
    $existing1035 = Database::fetchOne("SELECT id FROM chart_of_accounts WHERE account_code = '1035'");
    if (!$existing1035) {
        Database::execute("
            INSERT INTO chart_of_accounts (account_code, account_name, account_type, parent_id, is_system, status)
            VALUES ('1035', 'Cheques in Hand', 'asset', NULL, 1, 'active')
        ");
        echo "[OK] Inserted Account 1035 (Cheques in Hand) into chart_of_accounts.\n";
    } else {
        echo "[SKIP] Account 1035 already exists in chart_of_accounts.\n";
    }

    // 2. Extend customer_cheques
    $ccCols = array_column(Database::fetchAll("DESCRIBE customer_cheques"), 'Field');

    $alterCc = [];
    if (!in_array('deposit_date', $ccCols, true)) {
        $alterCc[] = "ADD COLUMN deposit_date DATE NULL AFTER bank_account_id";
    }
    if (!in_array('deposit_ref', $ccCols, true)) {
        $alterCc[] = "ADD COLUMN deposit_ref VARCHAR(100) NULL AFTER deposit_date";
    }
    if (!in_array('deposited_at', $ccCols, true)) {
        $alterCc[] = "ADD COLUMN deposited_at DATETIME NULL AFTER deposit_ref";
    }
    if (!in_array('deposited_by', $ccCols, true)) {
        $alterCc[] = "ADD COLUMN deposited_by INT UNSIGNED NULL AFTER deposited_at";
    }
    if (!in_array('cleared_date', $ccCols, true)) {
        $alterCc[] = "ADD COLUMN cleared_date DATE NULL AFTER deposited_by";
    }
    if (!in_array('bank_ref', $ccCols, true)) {
        $alterCc[] = "ADD COLUMN bank_ref VARCHAR(100) NULL AFTER cleared_date";
    }
    if (!in_array('cleared_at', $ccCols, true)) {
        $alterCc[] = "ADD COLUMN cleared_at DATETIME NULL AFTER bank_ref";
    }
    if (!in_array('cleared_by', $ccCols, true)) {
        $alterCc[] = "ADD COLUMN cleared_by INT UNSIGNED NULL AFTER cleared_at";
    }
    if (!in_array('journal_entry_id', $ccCols, true)) {
        $alterCc[] = "ADD COLUMN journal_entry_id BIGINT UNSIGNED NULL AFTER cleared_by";
    }
    if (!in_array('cancellation_reason', $ccCols, true)) {
        $alterCc[] = "ADD COLUMN cancellation_reason VARCHAR(255) NULL AFTER return_reason";
    }
    if (!in_array('cancelled_at', $ccCols, true)) {
        $alterCc[] = "ADD COLUMN cancelled_at DATETIME NULL AFTER cancellation_reason";
    }
    if (!in_array('cancelled_by', $ccCols, true)) {
        $alterCc[] = "ADD COLUMN cancelled_by INT UNSIGNED NULL AFTER cancelled_at";
    }
    if (!in_array('deleted_at', $ccCols, true)) {
        $alterCc[] = "ADD COLUMN deleted_at DATETIME NULL AFTER updated_at";
    }

    if (!empty($alterCc)) {
        $sql = "ALTER TABLE customer_cheques " . implode(", ", $alterCc);
        Database::execute($sql);
        echo "[OK] Updated customer_cheques schema with deposit & clearing lifecycle columns.\n";
    } else {
        echo "[SKIP] customer_cheques lifecycle columns already present.\n";
    }

    // Add indexes to customer_cheques if missing
    $ccIndexes = array_column(Database::fetchAll("SHOW INDEX FROM customer_cheques"), 'Key_name');
    if (!in_array('idx_cc_status_del', $ccIndexes, true)) {
        Database::execute("ALTER TABLE customer_cheques ADD INDEX idx_cc_status_del (status, deleted_at)");
        echo "[OK] Added index idx_cc_status_del on customer_cheques.\n";
    }
    if (!in_array('idx_cc_branch_status', $ccIndexes, true)) {
        Database::execute("ALTER TABLE customer_cheques ADD INDEX idx_cc_branch_status (branch_id, status)");
        echo "[OK] Added index idx_cc_branch_status on customer_cheques.\n";
    }

    // 3. Extend supplier_cheques
    $scCols = array_column(Database::fetchAll("DESCRIBE supplier_cheques"), 'Field');
    $alterSc = [];
    if (!in_array('cleared_date', $scCols, true)) {
        $alterSc[] = "ADD COLUMN cleared_date DATE NULL AFTER cleared_by";
    }
    if (!in_array('bank_ref', $scCols, true)) {
        $alterSc[] = "ADD COLUMN bank_ref VARCHAR(100) NULL AFTER cleared_date";
    }

    if (!empty($alterSc)) {
        $sql = "ALTER TABLE supplier_cheques " . implode(", ", $alterSc);
        Database::execute($sql);
        echo "[OK] Updated supplier_cheques schema with cleared_date and bank_ref.\n";
    } else {
        echo "[SKIP] supplier_cheques columns already present.\n";
    }

    $scIndexes = array_column(Database::fetchAll("SHOW INDEX FROM supplier_cheques"), 'Key_name');
    if (!in_array('idx_sc_bank_chq', $scIndexes, true)) {
        Database::execute("ALTER TABLE supplier_cheques ADD INDEX idx_sc_bank_chq (bank_account_id, cheque_number)");
        echo "[OK] Added index idx_sc_bank_chq on supplier_cheques.\n";
    }

    echo "\n=== CHEQUE MIGRATION COMPLETED SUCCESSFULLY ===\n";

} catch (Throwable $e) {
    echo "[ERROR] Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
