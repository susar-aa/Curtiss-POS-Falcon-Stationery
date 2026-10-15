<?php
/**
 * Curtiss POS - Stage 10 Database Migration Script
 * Creates Chart of Accounts, Journal Entries, Journal Lines tables,
 * seeds default hierarchical COA accounts, and seeds Stage 10 permissions.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

echo "Executing Stage 10 Migrations...\n";

try {
    $pdo = Database::getInstance()->getConnection();

    // 1. Chart of Accounts Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `chart_of_accounts` (
          `id` INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `account_code` VARCHAR(20) NOT NULL UNIQUE,
          `account_name` VARCHAR(100) NOT NULL,
          `account_type` ENUM('asset', 'liability', 'equity', 'income', 'expense') NOT NULL,
          `parent_id` INT(10) UNSIGNED DEFAULT NULL,
          `branch_id` INT(10) UNSIGNED DEFAULT NULL,
          `is_system` TINYINT(1) NOT NULL DEFAULT 0,
          `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          FOREIGN KEY (`parent_id`) REFERENCES `chart_of_accounts`(`id`) ON DELETE SET NULL,
          FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON DELETE SET NULL,
          INDEX `idx_coa_code` (`account_code`),
          INDEX `idx_coa_type` (`account_type`),
          INDEX `idx_coa_branch` (`branch_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "[OK] Table chart_of_accounts created or exists.\n";

    // 2. Journal Entries Header Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `journal_entries` (
          `id` BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `entry_number` VARCHAR(50) NOT NULL UNIQUE,
          `branch_id` INT(10) UNSIGNED NOT NULL,
          `entry_date` DATE NOT NULL,
          `source_type` ENUM('grn', 'supplier_payment', 'sale', 'customer_collection', 'sales_return', 'manual', 'reversal', 'opening_balance') NOT NULL,
          `source_id` BIGINT(20) UNSIGNED DEFAULT NULL,
          `description` TEXT NOT NULL,
          `total_debit` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          `total_credit` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          `posting_status` ENUM('draft', 'posted', 'reversed') NOT NULL DEFAULT 'posted',
          `reversed_journal_id` BIGINT(20) UNSIGNED DEFAULT NULL,
          `created_by` INT(10) UNSIGNED NOT NULL,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`),
          FOREIGN KEY (`created_by`) REFERENCES `users`(`id`),
          INDEX `idx_je_number` (`entry_number`),
          INDEX `idx_je_branch` (`branch_id`),
          INDEX `idx_je_source` (`source_type`, `source_id`),
          INDEX `idx_je_date` (`entry_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "[OK] Table journal_entries created or exists.\n";

    // 3. Journal Lines Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `journal_lines` (
          `id` BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `journal_entry_id` BIGINT(20) UNSIGNED NOT NULL,
          `account_id` INT(10) UNSIGNED NOT NULL,
          `debit` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          `credit` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          `memo` VARCHAR(255) DEFAULT NULL,
          FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries`(`id`) ON DELETE CASCADE,
          FOREIGN KEY (`account_id`) REFERENCES `chart_of_accounts`(`id`),
          INDEX `idx_jl_entry` (`journal_entry_id`),
          INDEX `idx_jl_account` (`account_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "[OK] Table journal_lines created or exists.\n";

    // 4. Seed Default Hierarchical Chart of Accounts
    $pdo->exec("
        INSERT IGNORE INTO `chart_of_accounts` (`id`, `account_code`, `account_name`, `account_type`, `parent_id`, `branch_id`, `is_system`, `status`) VALUES
        (1, '1010', 'Cash in Hand', 'asset', NULL, NULL, 1, 'active'),
        (2, '1015', 'Petty Cash', 'asset', NULL, NULL, 1, 'active'),
        (3, '1020', 'Bank Account', 'asset', NULL, NULL, 1, 'active'),
        (4, '1030', 'Card Receivable', 'asset', NULL, NULL, 1, 'active'),
        (5, '1040', 'Customer Receivables (AR)', 'asset', NULL, NULL, 1, 'active'),
        (6, '1200', 'Inventory Asset', 'asset', NULL, NULL, 1, 'active'),
        (7, '1250', 'Damaged Inventory Loss', 'expense', NULL, NULL, 1, 'active'),
        (8, '2010', 'Gift Voucher Liability', 'liability', NULL, NULL, 1, 'active'),
        (9, '2020', 'Supplier Payables (AP)', 'liability', NULL, NULL, 1, 'active'),
        (10, '3010', 'Opening Balance Equity', 'equity', NULL, NULL, 1, 'active'),
        (11, '4010', 'Sales Revenue', 'income', NULL, NULL, 1, 'active'),
        (12, '4020', 'Sales Returns & Allowances', 'income', NULL, NULL, 1, 'active'),
        (13, '5010', 'Cost of Goods Sold (COGS)', 'expense', NULL, NULL, 1, 'active'),
        (14, '5020', 'Inventory Adjustments', 'expense', NULL, NULL, 1, 'active'),
        (15, '5030', 'Sales Discounts', 'expense', NULL, NULL, 1, 'active'),
        (16, '5040', 'Cash Shortage / Overage', 'expense', NULL, NULL, 1, 'active'),
        (17, '5100', 'General Operating Expenses', 'expense', NULL, NULL, 1, 'active');
    ");
    echo "[OK] Default Chart of Accounts seeded.\n";

    // 5. Seed Stage 10 Permissions
    $pdo->exec("
        INSERT IGNORE INTO `permissions` (`id`, `module`, `permission_code`, `description`) VALUES
        (43, 'accounting', 'accounting.view', 'View Chart of Accounts, Journal Entries, and General Ledger'),
        (44, 'accounting', 'accounts.manage', 'Create and manage Chart of Accounts'),
        (45, 'accounting', 'accounting.post_manual', 'Create manual double-entry journal postings'),
        (46, 'accounting', 'accounting.reverse', 'Post journal reversal entries'),
        (47, 'accounting', 'accounting.backfill', 'Execute historical accounting backfill migration');
    ");
    echo "[OK] Stage 10 permissions inserted.\n";

    // Assign permissions to Super Admin (1), Admin (2), Manager (3), Accountant (5)
    $pdo->exec("
        INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
        SELECT 1, id FROM `permissions` WHERE id BETWEEN 43 AND 47;

        INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
        SELECT 2, id FROM `permissions` WHERE id BETWEEN 43 AND 47;

        INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
        SELECT 3, id FROM `permissions` WHERE id BETWEEN 43 AND 47;

        INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
        SELECT 5, id FROM `permissions` WHERE id BETWEEN 43 AND 47;
    ");
    echo "[OK] Permissions assigned to roles.\n";

    echo "Stage 10 Migrations completed successfully!\n";

} catch (Throwable $e) {
    echo "MIGRATION ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
