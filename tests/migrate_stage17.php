<?php
/**
 * Curtiss POS - Section 17 Database Migration Script (Stage 37)
 * Section 17: Card Receivable (1030) -> Bank Settlement & Card Reconciliation
 *
 * Migration Tasks:
 *  1. Create `card_processors` table
 *  2. Add `processor_id`, `settlement_status`, and `settled_amount` to `payments` table
 *  3. Create `card_settlements` and `card_settlement_items` tables
 *  4. Seed system account `5170 Card Processing Fees` in `chart_of_accounts`
 *  5. Seed default card acquirers/processors in `card_processors`
 *  6. Seed Section 17 permissions and role permission mappings
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "Starting Section 17 (Stage 37) Database Migration...\n";

    // 1. Create card_processors Table
    $db->exec("
        CREATE TABLE IF NOT EXISTS `card_processors` (
            `id` INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `processor_code` VARCHAR(50) NOT NULL UNIQUE,
            `name` VARCHAR(100) NOT NULL,
            `merchant_id` VARCHAR(100) DEFAULT NULL,
            `default_bank_account_id` INT(10) UNSIGNED DEFAULT NULL,
            `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_cp_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    // 1b. Update journal_entries source_type ENUM for Card Settlement
    $db->exec("
        ALTER TABLE `journal_entries` 
        MODIFY COLUMN `source_type` ENUM(
            'grn','supplier_payment','sale','customer_collection','sales_return','manual','reversal',
            'opening_balance','customer_cheque_return','cheque_return','supplier_return',
            'supplier_credit_settlement','cheque_clearing','cheque_bounce','cheque_cancellation',
            'card_settlement','card_settlement_reversal'
        ) NOT NULL
    ");
    echo "  - Table 'journal_entries' ENUM updated for card settlement.\n";


    // 2. Alter payments Table (Add processor_id, settlement_status, settled_amount)
    $stmtCol = $db->query("SHOW COLUMNS FROM payments LIKE 'processor_id'");
    if (!$stmtCol->fetch(PDO::FETCH_ASSOC)) {
        $db->exec("
            ALTER TABLE `payments` 
            ADD COLUMN `processor_id` INT(10) UNSIGNED DEFAULT NULL AFTER `bank_account_id`,
            ADD COLUMN `settlement_status` ENUM('unsettled', 'partially_settled', 'settled') NOT NULL DEFAULT 'unsettled' AFTER `voucher_id`,
            ADD COLUMN `settled_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `settlement_status`,
            ADD INDEX `idx_pmt_processor` (`processor_id`),
            ADD INDEX `idx_pmt_settle_status` (`settlement_status`);
        ");
        echo "  - Table 'payments' updated with settlement columns.\n";
    } else {
        echo "  - Table 'payments' already has settlement columns.\n";
    }

    $stmtColRef = $db->query("SHOW COLUMNS FROM payments LIKE 'refunded_amount'");
    if (!$stmtColRef->fetch(PDO::FETCH_ASSOC)) {
        $db->exec("
            ALTER TABLE `payments` 
            ADD COLUMN `refunded_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `settled_amount`;
        ");
        echo "  - Table 'payments' updated with refunded_amount column.\n";
    }

    // 3. Create card_settlements Table
    $db->exec("
        CREATE TABLE IF NOT EXISTS `card_settlements` (
            `id` BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `settlement_no` VARCHAR(50) NOT NULL UNIQUE,
            `branch_id` INT(10) UNSIGNED NOT NULL,
            `processor_id` INT(10) UNSIGNED DEFAULT NULL,
            `bank_account_id` INT(10) UNSIGNED NOT NULL,
            `settlement_date` DATE NOT NULL,
            `reference_no` VARCHAR(100) DEFAULT NULL,
            `gross_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `processing_fee` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `net_bank_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `status` ENUM('draft', 'posted', 'reversed') NOT NULL DEFAULT 'draft',
            `notes` TEXT DEFAULT NULL,
            `journal_entry_id` BIGINT(20) UNSIGNED DEFAULT NULL,
            `created_by` INT(10) UNSIGNED NOT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_cs_branch` (`branch_id`),
            INDEX `idx_cs_processor` (`processor_id`),
            INDEX `idx_cs_bank_acc` (`bank_account_id`),
            INDEX `idx_cs_status` (`status`),
            INDEX `idx_cs_date` (`settlement_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  - Table 'card_settlements' ready.\n";

    // 4. Create card_settlement_items Table
    $db->exec("
        CREATE TABLE IF NOT EXISTS `card_settlement_items` (
            `id` BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `settlement_id` BIGINT(20) UNSIGNED NOT NULL,
            `payment_id` BIGINT(20) UNSIGNED NOT NULL,
            `gross_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            INDEX `idx_csi_settlement` (`settlement_id`),
            INDEX `idx_csi_payment` (`payment_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  - Table 'card_settlement_items' ready.\n";

    // 5. Seed System Account 5170 Card Processing Fees in COA
    $stmtCoa = $db->query("SELECT id FROM chart_of_accounts WHERE account_code = '5170'");
    if (!$stmtCoa->fetch(PDO::FETCH_ASSOC)) {
        $db->exec("
            INSERT INTO chart_of_accounts (account_code, account_name, account_type, is_system, status)
            VALUES ('5170', 'Card Processing Fees', 'expense', 1, 'active')
        ");
        echo "  - COA Account 5170 'Card Processing Fees' seeded.\n";
    } else {
        echo "  - COA Account 5170 'Card Processing Fees' already exists.\n";
    }

    // 6. Default Card Processors - Schema created only (No sample processor seeds in production migration)
    echo "  - Table 'card_processors' ready for operational processor creation.\n";

    // 7. Seed Section 17 Permissions
    $perms = [
        'view_card_settlements'    => 'View Card Settlements & Unsettled Card Reports',
        'create_card_settlement'   => 'Create and Edit Card Settlement Drafts',
        'post_card_settlement'     => 'Post Card Settlements to Bank & General Ledger',
        'reverse_card_settlement'  => 'Reverse Posted Card Settlements',
        'manage_card_processors'   => 'Manage Card Processor / Acquirer Masters'
    ];

    $stmtCheckPerm = $db->prepare("SELECT id FROM permissions WHERE permission_code = :pcode");
    $stmtInsPerm = $db->prepare("INSERT INTO permissions (module, permission_code, description) VALUES ('banking', :pcode, :desc)");

    foreach ($perms as $pCode => $pDesc) {
        $stmtCheckPerm->execute([':pcode' => $pCode]);
        if (!$stmtCheckPerm->fetch(PDO::FETCH_ASSOC)) {
            $stmtInsPerm->execute([':pcode' => $pCode, ':desc' => $pDesc]);
        }
    }
    echo "  - Section 17 permissions seeded.\n";

    // Seed Role Permissions for Super Admin (1), Admin (2), Manager (3), Accountant (5)
    $db->exec("
        INSERT IGNORE INTO role_permissions (role_id, permission_id)
        SELECT r.id, p.id
        FROM roles r
        CROSS JOIN permissions p
        WHERE r.id IN (1, 2, 3, 5)
          AND p.permission_code IN ('view_card_settlements', 'create_card_settlement', 'post_card_settlement', 'reverse_card_settlement', 'manage_card_processors')
    ");
    echo "  - Section 17 role permission mappings updated.\n";

    echo "\nSection 17 Migration Completed Successfully.\n";

} catch (Throwable $e) {
    echo "\n[MIGRATION ERROR]: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
