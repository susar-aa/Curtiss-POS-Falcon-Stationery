<?php
/**
 * Curtiss POS - Stage 12 Migration Script
 * Creates expense_categories, petty_cash_vouchers, petty_cash_reimbursements, petty_cash_adjustments tables
 * Seeds default expense categories and Stage 12 permissions.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "Starting Stage 12 Migration...\n";

    // 1. Create expense_categories Table
    $db->exec("
        CREATE TABLE IF NOT EXISTS `expense_categories` (
          `id` INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `name` VARCHAR(100) NOT NULL,
          `code` VARCHAR(50) NOT NULL UNIQUE,
          `account_id` INT(10) UNSIGNED NOT NULL,
          `description` TEXT DEFAULT NULL,
          `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
          FOREIGN KEY (`account_id`) REFERENCES `chart_of_accounts`(`id`),
          INDEX `idx_expcat_status` (`status`),
          INDEX `idx_expcat_account` (`account_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  - Table 'expense_categories' ready.\n";

    // 2. Create petty_cash_vouchers Table
    $db->exec("
        CREATE TABLE IF NOT EXISTS `petty_cash_vouchers` (
          `id` BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `voucher_no` VARCHAR(50) NOT NULL UNIQUE,
          `branch_id` INT(10) UNSIGNED NOT NULL,
          `voucher_date` DATE NOT NULL,
          `category_id` INT(10) UNSIGNED NOT NULL,
          `account_id` INT(10) UNSIGNED NOT NULL,
          `amount` DECIMAL(12,2) NOT NULL,
          `payee` VARCHAR(150) NOT NULL,
          `description` TEXT NOT NULL,
          `reference_no` VARCHAR(100) DEFAULT NULL,
          `receipt_ref` VARCHAR(100) DEFAULT NULL,
          `status` ENUM('posted', 'reversed') NOT NULL DEFAULT 'posted',
          `reversed_at` DATETIME DEFAULT NULL,
          `reversed_by` INT(10) UNSIGNED DEFAULT NULL,
          `reversal_reason` TEXT DEFAULT NULL,
          `journal_entry_id` BIGINT(20) UNSIGNED DEFAULT NULL,
          `reversal_journal_entry_id` BIGINT(20) UNSIGNED DEFAULT NULL,
          `created_by` INT(10) UNSIGNED NOT NULL,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`),
          FOREIGN KEY (`category_id`) REFERENCES `expense_categories`(`id`),
          FOREIGN KEY (`account_id`) REFERENCES `chart_of_accounts`(`id`),
          FOREIGN KEY (`created_by`) REFERENCES `users`(`id`),
          FOREIGN KEY (`reversed_by`) REFERENCES `users`(`id`),
          INDEX `idx_pcv_branch_date` (`branch_id`, `voucher_date`),
          INDEX `idx_pcv_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  - Table 'petty_cash_vouchers' ready.\n";

    // 3. Create petty_cash_reimbursements Table
    $db->exec("
        CREATE TABLE IF NOT EXISTS `petty_cash_reimbursements` (
          `id` BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `reimbursement_no` VARCHAR(50) NOT NULL UNIQUE,
          `branch_id` INT(10) UNSIGNED NOT NULL,
          `reimbursement_date` DATE NOT NULL,
          `source_type` ENUM('bank', 'cash_in_hand') NOT NULL,
          `amount` DECIMAL(12,2) NOT NULL,
          `reference_no` VARCHAR(100) DEFAULT NULL,
          `notes` TEXT DEFAULT NULL,
          `journal_entry_id` BIGINT(20) UNSIGNED DEFAULT NULL,
          `created_by` INT(10) UNSIGNED NOT NULL,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`),
          FOREIGN KEY (`created_by`) REFERENCES `users`(`id`),
          INDEX `idx_pcr_branch_date` (`branch_id`, `reimbursement_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  - Table 'petty_cash_reimbursements' ready.\n";

    // 4. Create petty_cash_adjustments Table
    $db->exec("
        CREATE TABLE IF NOT EXISTS `petty_cash_adjustments` (
          `id` BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `adjustment_no` VARCHAR(50) NOT NULL UNIQUE,
          `branch_id` INT(10) UNSIGNED NOT NULL,
          `adjustment_type` ENUM('positive', 'negative') NOT NULL,
          `amount` DECIMAL(12,2) NOT NULL,
          `reason` TEXT NOT NULL,
          `journal_entry_id` BIGINT(20) UNSIGNED DEFAULT NULL,
          `created_by` INT(10) UNSIGNED NOT NULL,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`),
          FOREIGN KEY (`created_by`) REFERENCES `users`(`id`),
          INDEX `idx_pca_branch` (`branch_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  - Table 'petty_cash_adjustments' ready.\n";

    // 5. Seed Default Expense Categories (linked to COA 5100 General Operating Expenses)
    $stmtExp = $db->prepare("
        INSERT IGNORE INTO `expense_categories` (`id`, `name`, `code`, `account_id`, `description`, `status`) VALUES
        (1, 'Fuel & Travelling', 'EXP-FUEL', 17, 'Vehicle fuel, travelling allowance and transport costs', 'active'),
        (2, 'Meals & Refreshments', 'EXP-MEALS', 17, 'Staff tea, meals and client entertainment', 'active'),
        (3, 'Stationery & Printing', 'EXP-STAT', 17, 'Paper, pens, receipt rolls and office supplies', 'active'),
        (4, 'Cleaning & Sanitation', 'EXP-CLEAN', 17, 'Cleaning products, detergents and waste management', 'active'),
        (5, 'Repairs & Maintenance', 'EXP-REPAIR', 17, 'Minor store maintenance, bulb replacement and repairs', 'active'),
        (6, 'Utilities & Postage', 'EXP-UTIL', 17, 'Minor courier fees, electricity/water bills, postage', 'active'),
        (7, 'Miscellaneous Expenses', 'EXP-MISC', 17, 'General incidental petty expense items', 'active');
    ");
    $stmtExp->execute();
    echo "  - Default expense categories seeded.\n";

    // 6. Seed Stage 12 Permissions
    $stmtPerm = $db->prepare("
        INSERT IGNORE INTO `permissions` (`id`, `module`, `permission_code`, `description`) VALUES
        (53, 'petty_cash', 'view_petty_cash', 'View petty cash dashboard, balance and transactions'),
        (54, 'petty_cash', 'create_expense', 'Create petty cash expense vouchers'),
        (55, 'petty_cash', 'manage_expense_categories', 'Create and edit expense categories & COA mapping'),
        (56, 'petty_cash', 'reimburse_petty_cash', 'Reimburse / top-up petty cash fund from bank or drawer'),
        (57, 'petty_cash', 'adjust_petty_cash', 'Perform manual petty cash adjustments with accounting'),
        (58, 'petty_cash', 'reverse_petty_cash_voucher', 'Cancel / reverse petty cash expense vouchers');
    ");
    $stmtPerm->execute();
    echo "  - Stage 12 permissions seeded.\n";

    // 7. Assign Permissions to Roles
    $db->exec("INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) SELECT 1, id FROM `permissions` WHERE id BETWEEN 53 AND 58;"); // Super Admin
    $db->exec("INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) SELECT 2, id FROM `permissions` WHERE id BETWEEN 53 AND 58;"); // Admin
    $db->exec("INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) SELECT 3, id FROM `permissions` WHERE id BETWEEN 53 AND 58;"); // Manager
    $db->exec("INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) SELECT 5, id FROM `permissions` WHERE id BETWEEN 53 AND 58;"); // Accountant

    // Cashier (4): View & Create Expense
    $db->exec("INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES (4, 53), (4, 54);");
    echo "  - Role permissions assigned.\n";

    echo "Stage 12 Database Migration Completed Successfully!\n";

} catch (Throwable $e) {
    echo "Migration Error: " . $e->getMessage() . "\n";
    exit(1);
}
