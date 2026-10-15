<?php
/**
 * Curtiss POS - Stage 13 Migration Script
 * Creates bank_accounts, bank_deposits, bank_reconciliations, bank_reconciliation_items tables.
 * Adds bank_account_id column to payments, supplier_payments, refunds, petty_cash_reimbursements.
 * Seeds Stage 13 permissions and default company bank accounts.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "Starting Stage 13 Migration...\n";

    // 1. Create bank_accounts Table
    $db->exec("
        CREATE TABLE IF NOT EXISTS `bank_accounts` (
          `id` INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `bank_name` VARCHAR(100) NOT NULL,
          `account_name` VARCHAR(100) NOT NULL,
          `account_number` VARCHAR(50) NOT NULL UNIQUE,
          `branch_id` INT(10) UNSIGNED DEFAULT NULL,
          `bank_branch_name` VARCHAR(100) DEFAULT NULL,
          `opening_balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
          `account_id` INT(10) UNSIGNED NOT NULL,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
          FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`),
          FOREIGN KEY (`account_id`) REFERENCES `chart_of_accounts`(`id`),
          INDEX `idx_bankacc_branch` (`branch_id`),
          INDEX `idx_bankacc_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  - Table 'bank_accounts' ready.\n";

    // 2. Add bank_account_id column to payments, supplier_payments, refunds, petty_cash_reimbursements if not exists
    $tablesToAlter = ['payments', 'supplier_payments', 'refunds', 'petty_cash_reimbursements'];
    foreach ($tablesToAlter as $tbl) {
        $colCheck = $db->query("SHOW COLUMNS FROM `{$tbl}` LIKE 'bank_account_id'")->fetch();
        if (!$colCheck) {
            $db->exec("ALTER TABLE `{$tbl}` ADD COLUMN `bank_account_id` INT(10) UNSIGNED DEFAULT NULL AFTER `branch_id`, ADD FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts`(`id`);");
            echo "  - Added 'bank_account_id' column to '{$tbl}'.\n";
        }
    }

    // 3. Create bank_deposits Table
    $db->exec("
        CREATE TABLE IF NOT EXISTS `bank_deposits` (
          `id` BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `deposit_no` VARCHAR(50) NOT NULL UNIQUE,
          `branch_id` INT(10) UNSIGNED NOT NULL,
          `bank_account_id` INT(10) UNSIGNED NOT NULL,
          `deposit_date` DATE NOT NULL,
          `amount` DECIMAL(12,2) NOT NULL,
          `source_type` ENUM('active_drawer', 'closed_day', 'other_cash') NOT NULL DEFAULT 'active_drawer',
          `business_day_id` BIGINT(20) UNSIGNED DEFAULT NULL,
          `reference_no` VARCHAR(100) DEFAULT NULL,
          `notes` TEXT DEFAULT NULL,
          `journal_entry_id` BIGINT(20) UNSIGNED DEFAULT NULL,
          `created_by` INT(10) UNSIGNED NOT NULL,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`),
          FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts`(`id`),
          FOREIGN KEY (`business_day_id`) REFERENCES `business_days`(`id`),
          FOREIGN KEY (`created_by`) REFERENCES `users`(`id`),
          INDEX `idx_dep_bank` (`bank_account_id`),
          INDEX `idx_dep_branch` (`branch_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  - Table 'bank_deposits' ready.\n";

    // 4. Create bank_reconciliations Table
    $db->exec("
        CREATE TABLE IF NOT EXISTS `bank_reconciliations` (
          `id` BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `reconciliation_no` VARCHAR(50) NOT NULL UNIQUE,
          `bank_account_id` INT(10) UNSIGNED NOT NULL,
          `branch_id` INT(10) UNSIGNED DEFAULT NULL,
          `statement_start_date` DATE NOT NULL,
          `statement_end_date` DATE NOT NULL,
          `statement_opening_balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          `statement_closing_balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          `book_balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          `cleared_balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          `outstanding_deposits` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          `outstanding_payments` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          `reconciliation_difference` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          `status` ENUM('draft', 'completed') NOT NULL DEFAULT 'draft',
          `reconciled_at` DATETIME DEFAULT NULL,
          `reconciled_by` INT(10) UNSIGNED DEFAULT NULL,
          `notes` TEXT DEFAULT NULL,
          `created_by` INT(10) UNSIGNED NOT NULL,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts`(`id`),
          FOREIGN KEY (`created_by`) REFERENCES `users`(`id`),
          FOREIGN KEY (`reconciled_by`) REFERENCES `users`(`id`),
          INDEX `idx_brec_bank_status` (`bank_account_id`, `status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  - Table 'bank_reconciliations' ready.\n";

    // 5. Create bank_reconciliation_items Table
    $db->exec("
        CREATE TABLE IF NOT EXISTS `bank_reconciliation_items` (
          `id` BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `reconciliation_id` BIGINT(20) UNSIGNED NOT NULL,
          `journal_line_id` BIGINT(20) UNSIGNED NOT NULL,
          `is_cleared` TINYINT(1) NOT NULL DEFAULT 0,
          `cleared_date` DATE DEFAULT NULL,
          FOREIGN KEY (`reconciliation_id`) REFERENCES `bank_reconciliations`(`id`) ON DELETE CASCADE,
          FOREIGN KEY (`journal_line_id`) REFERENCES `journal_lines`(`id`),
          INDEX `idx_bri_rec` (`reconciliation_id`),
          INDEX `idx_bri_line` (`journal_line_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  - Table 'bank_reconciliation_items' ready.\n";

    // 6. Create Dedicated COA Bank Accounts & Seed Default Real Bank Accounts
    // Ensure COA accounts for Commercial Bank & Sampath Bank exist
    $bankAcc1Code = '1021';
    $bankAcc2Code = '1022';

    $db->exec("
        INSERT IGNORE INTO `chart_of_accounts` (`account_code`, `account_name`, `account_type`, `parent_id`, `branch_id`, `is_system`, `status`) VALUES
        ('1021', 'Commercial Bank - Main A/C 800991', 'asset', 3, NULL, 1, 'active'),
        ('1022', 'Sampath Bank - Store A/C 001920', 'asset', 3, NULL, 1, 'active');
    ");

    $coa1 = Database::fetchOne("SELECT id FROM chart_of_accounts WHERE account_code = '1021'");
    $coa2 = Database::fetchOne("SELECT id FROM chart_of_accounts WHERE account_code = '1022'");

    if ($coa1 && $coa2) {
        $db->exec("
            INSERT IGNORE INTO `bank_accounts` (`id`, `bank_name`, `account_name`, `account_number`, `branch_id`, `bank_branch_name`, `opening_balance`, `status`, `account_id`) VALUES
            (1, 'Commercial Bank', 'Curtiss Kandy Main Account', '8009910022', NULL, 'Kandy Main Branch', 50000.00, 'active', {$coa1['id']}),
            (2, 'Sampath Bank', 'Curtiss Kandy POS Collection Account', '0019203341', 1, 'Kandy City Center', 25000.00, 'active', {$coa2['id']});
        ");
        echo "  - Dedicated COA accounts and default bank accounts seeded.\n";
    }

    // 7. Seed Stage 13 Permissions
    $stmtPerm = $db->prepare("
        INSERT IGNORE INTO `permissions` (`id`, `module`, `permission_code`, `description`) VALUES
        (59, 'banking', 'view_bank_accounts', 'View company bank accounts and balances'),
        (60, 'banking', 'manage_bank_accounts', 'Create, edit and manage company bank accounts and COA mapping'),
        (61, 'banking', 'make_bank_deposit', 'Deposit cash into company bank account'),
        (62, 'banking', 'view_bank_register', 'View bank transaction activity register and running balances'),
        (63, 'banking', 'manage_bank_reconciliation', 'Create, edit and complete bank reconciliation sessions'),
        (64, 'banking', 'post_bank_adjustment', 'Post bank charges, interest and direct adjustments');
    ");
    $stmtPerm->execute();
    echo "  - Stage 13 permissions seeded.\n";

    // 8. Assign Permissions to Roles
    $db->exec("INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) SELECT 1, id FROM `permissions` WHERE id BETWEEN 59 AND 64;"); // Super Admin
    $db->exec("INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) SELECT 2, id FROM `permissions` WHERE id BETWEEN 59 AND 64;"); // Admin
    $db->exec("INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) SELECT 3, id FROM `permissions` WHERE id BETWEEN 59 AND 64;"); // Manager
    $db->exec("INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) SELECT 5, id FROM `permissions` WHERE id BETWEEN 59 AND 64;"); // Accountant

    // Cashier (4): Make Deposit, View Register
    $db->exec("INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES (4, 59), (4, 61);");
    echo "  - Role permissions assigned.\n";

    echo "Stage 13 Database Migration Completed Successfully!\n";

} catch (Throwable $e) {
    echo "Migration Error: " . $e->getMessage() . "\n";
    exit(1);
}
