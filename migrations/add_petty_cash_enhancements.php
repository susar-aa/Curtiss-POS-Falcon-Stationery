<?php
/**
 * Migration: Add Petty Cash & Expense Enhancements & Composite Indexes
 * 
 * - Adds deleted_at to expense_categories for master data soft-deletion and trash recovery.
 * - Updates petty_cash_vouchers status ENUM to support ('draft', 'posted', 'reversed').
 * - Adds deleted_at to petty_cash_vouchers for draft discard/cancellation.
 * - Adds composite performance indexes for high-frequency ledger queries and reports.
 */

require_once __DIR__ . '/../config/config.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "Starting Petty Cash & Expense Enhancements Migration..." . PHP_EOL;

    // 1. expense_categories: deleted_at column
    $colCheck = $db->query("SHOW COLUMNS FROM expense_categories LIKE 'deleted_at'")->fetch(PDO::FETCH_ASSOC);
    if (!$colCheck) {
        echo "Adding deleted_at column to expense_categories..." . PHP_EOL;
        $db->exec("ALTER TABLE expense_categories ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL AFTER status");
    } else {
        echo "Column deleted_at already exists in expense_categories." . PHP_EOL;
    }

    // 2. expense_categories: composite index
    $idxCheck = $db->query("SHOW INDEX FROM expense_categories WHERE Key_name = 'idx_expcat_del_status'")->fetch(PDO::FETCH_ASSOC);
    if (!$idxCheck) {
        echo "Adding index idx_expcat_del_status to expense_categories..." . PHP_EOL;
        $db->exec("ALTER TABLE expense_categories ADD INDEX idx_expcat_del_status (deleted_at, status, name)");
    } else {
        echo "Index idx_expcat_del_status already exists on expense_categories." . PHP_EOL;
    }

    // 3. petty_cash_vouchers: modify status enum to include 'draft'
    echo "Updating status column ENUM in petty_cash_vouchers..." . PHP_EOL;
    $db->exec("ALTER TABLE petty_cash_vouchers MODIFY COLUMN status ENUM('draft', 'posted', 'reversed') NOT NULL DEFAULT 'posted'");

    // 4. petty_cash_vouchers: deleted_at column
    $colCheck2 = $db->query("SHOW COLUMNS FROM petty_cash_vouchers LIKE 'deleted_at'")->fetch(PDO::FETCH_ASSOC);
    if (!$colCheck2) {
        echo "Adding deleted_at column to petty_cash_vouchers..." . PHP_EOL;
        $db->exec("ALTER TABLE petty_cash_vouchers ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL AFTER created_at");
    } else {
        echo "Column deleted_at already exists in petty_cash_vouchers." . PHP_EOL;
    }

    // 5. petty_cash_vouchers: composite indexes
    $vIndexes = [
        'idx_pcv_branch_date_status' => "ALTER TABLE petty_cash_vouchers ADD INDEX idx_pcv_branch_date_status (branch_id, voucher_date, status)",
        'idx_pcv_branch_status'      => "ALTER TABLE petty_cash_vouchers ADD INDEX idx_pcv_branch_status (branch_id, status)",
        'idx_pcv_del_status'         => "ALTER TABLE petty_cash_vouchers ADD INDEX idx_pcv_del_status (deleted_at, status)"
    ];
    foreach ($vIndexes as $idxName => $sql) {
        $chk = $db->query("SHOW INDEX FROM petty_cash_vouchers WHERE Key_name = '{$idxName}'")->fetch(PDO::FETCH_ASSOC);
        if (!$chk) {
            echo "Adding index {$idxName} to petty_cash_vouchers..." . PHP_EOL;
            $db->exec($sql);
        } else {
            echo "Index {$idxName} already exists on petty_cash_vouchers." . PHP_EOL;
        }
    }

    // 6. petty_cash_reimbursements: composite index
    $chkR = $db->query("SHOW INDEX FROM petty_cash_reimbursements WHERE Key_name = 'idx_pcr_branch_date_source'")->fetch(PDO::FETCH_ASSOC);
    if (!$chkR) {
        echo "Adding index idx_pcr_branch_date_source to petty_cash_reimbursements..." . PHP_EOL;
        $db->exec("ALTER TABLE petty_cash_reimbursements ADD INDEX idx_pcr_branch_date_source (branch_id, reimbursement_date, source_type)");
    } else {
        echo "Index idx_pcr_branch_date_source already exists on petty_cash_reimbursements." . PHP_EOL;
    }

    // 7. petty_cash_adjustments: composite index
    $chkA = $db->query("SHOW INDEX FROM petty_cash_adjustments WHERE Key_name = 'idx_pca_branch_type_created'")->fetch(PDO::FETCH_ASSOC);
    if (!$chkA) {
        echo "Adding index idx_pca_branch_type_created to petty_cash_adjustments..." . PHP_EOL;
        $db->exec("ALTER TABLE petty_cash_adjustments ADD INDEX idx_pca_branch_type_created (branch_id, adjustment_type, created_at)");
    } else {
        echo "Index idx_pca_branch_type_created already exists on petty_cash_adjustments." . PHP_EOL;
    }

    echo "Petty Cash & Expense Enhancements Migration completed successfully!" . PHP_EOL;

} catch (Throwable $e) {
    echo "Migration failed: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
