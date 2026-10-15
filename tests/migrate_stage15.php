<?php
/**
 * Curtiss POS - Stage 15 Database Migration Script
 * Creates supplier_returns, supplier_return_items, supplier_credits, supplier_credit_settlements tables.
 * Alters supplier_cheques to add bank_account_id, timestamp & user tracking, and journal linkage.
 * Seeds COA accounts (1045 Supplier Credit, 2025 Cheques Issued) and Stage 15 permissions.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "Starting Stage 15 Migration...\n";

    // 1. Create supplier_returns Table
    $db->exec("
        CREATE TABLE IF NOT EXISTS `supplier_returns` (
          `id` BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `return_number` VARCHAR(50) NOT NULL UNIQUE,
          `supplier_id` INT(10) UNSIGNED NOT NULL,
          `grn_id` INT(10) UNSIGNED NOT NULL,
          `branch_id` INT(10) UNSIGNED NOT NULL,
          `return_date` DATE NOT NULL,
          `total_return_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          `applied_to_payable` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          `credit_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          `notes` TEXT DEFAULT NULL,
          `journal_entry_id` BIGINT(20) UNSIGNED DEFAULT NULL,
          `created_by` INT(10) UNSIGNED NOT NULL,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`),
          FOREIGN KEY (`grn_id`) REFERENCES `grns`(`id`),
          FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`),
          FOREIGN KEY (`created_by`) REFERENCES `users`(`id`),
          INDEX `idx_srtn_supplier` (`supplier_id`),
          INDEX `idx_srtn_grn` (`grn_id`),
          INDEX `idx_srtn_branch` (`branch_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  - Table 'supplier_returns' ready.\n";

    // 2. Create supplier_return_items Table
    $db->exec("
        CREATE TABLE IF NOT EXISTS `supplier_return_items` (
          `id` BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `supplier_return_id` BIGINT(20) UNSIGNED NOT NULL,
          `grn_item_id` BIGINT(20) UNSIGNED DEFAULT NULL,
          `product_id` INT(10) UNSIGNED NOT NULL,
          `lot_id` INT(10) UNSIGNED NOT NULL,
          `quantity` DECIMAL(12,2) NOT NULL,
          `unit_cost` DECIMAL(12,2) NOT NULL,
          `total_amount` DECIMAL(12,2) NOT NULL,
          `reason` VARCHAR(255) DEFAULT NULL,
          FOREIGN KEY (`supplier_return_id`) REFERENCES `supplier_returns`(`id`) ON DELETE CASCADE,
          FOREIGN KEY (`product_id`) REFERENCES `products`(`id`),
          FOREIGN KEY (`lot_id`) REFERENCES `product_lots`(`id`),
          INDEX `idx_srti_return` (`supplier_return_id`),
          INDEX `idx_srti_product` (`product_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  - Table 'supplier_return_items' ready.\n";

    // 3. Create supplier_credits Table
    $db->exec("
        CREATE TABLE IF NOT EXISTS `supplier_credits` (
          `id` BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `credit_number` VARCHAR(50) NOT NULL UNIQUE,
          `supplier_id` INT(10) UNSIGNED NOT NULL,
          `supplier_return_id` BIGINT(20) UNSIGNED DEFAULT NULL,
          `branch_id` INT(10) UNSIGNED NOT NULL,
          `credit_date` DATE NOT NULL,
          `total_credit_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          `remaining_credit_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          `status` ENUM('active', 'fully_settled', 'void') NOT NULL DEFAULT 'active',
          `notes` TEXT DEFAULT NULL,
          `created_by` INT(10) UNSIGNED NOT NULL,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`),
          FOREIGN KEY (`supplier_return_id`) REFERENCES `supplier_returns`(`id`) ON DELETE SET NULL,
          FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`),
          FOREIGN KEY (`created_by`) REFERENCES `users`(`id`),
          INDEX `idx_scrd_supplier` (`supplier_id`),
          INDEX `idx_scrd_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  - Table 'supplier_credits' ready.\n";

    // 4. Create supplier_credit_settlements Table
    $db->exec("
        CREATE TABLE IF NOT EXISTS `supplier_credit_settlements` (
          `id` BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `settlement_number` VARCHAR(50) NOT NULL UNIQUE,
          `supplier_credit_id` BIGINT(20) UNSIGNED NOT NULL,
          `supplier_id` INT(10) UNSIGNED NOT NULL,
          `branch_id` INT(10) UNSIGNED NOT NULL,
          `settlement_date` DATE NOT NULL,
          `settlement_type` ENUM('bank_refund', 'cash_refund', 'future_grn_offset') NOT NULL,
          `amount` DECIMAL(12,2) NOT NULL,
          `bank_account_id` INT(10) UNSIGNED DEFAULT NULL,
          `target_grn_id` INT(10) UNSIGNED DEFAULT NULL,
          `reference_no` VARCHAR(100) DEFAULT NULL,
          `notes` TEXT DEFAULT NULL,
          `journal_entry_id` BIGINT(20) UNSIGNED DEFAULT NULL,
          `created_by` INT(10) UNSIGNED NOT NULL,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (`supplier_credit_id`) REFERENCES `supplier_credits`(`id`),
          FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`),
          FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`),
          FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts`(`id`),
          FOREIGN KEY (`target_grn_id`) REFERENCES `grns`(`id`),
          FOREIGN KEY (`created_by`) REFERENCES `users`(`id`),
          INDEX `idx_scs_credit` (`supplier_credit_id`),
          INDEX `idx_scs_supplier` (`supplier_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  - Table 'supplier_credit_settlements' ready.\n";

    // 5. Enhance supplier_cheques Table
    $cols = $db->query("SHOW COLUMNS FROM `supplier_cheques`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('bank_account_id', $cols)) {
        $db->exec("ALTER TABLE `supplier_cheques` ADD COLUMN `bank_account_id` INT(10) UNSIGNED DEFAULT NULL AFTER `bank_name`, ADD FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts`(`id`);");
    }
    if (!in_array('cleared_at', $cols)) {
        $db->exec("ALTER TABLE `supplier_cheques` ADD COLUMN `cleared_at` DATETIME DEFAULT NULL;");
    }
    if (!in_array('cleared_by', $cols)) {
        $db->exec("ALTER TABLE `supplier_cheques` ADD COLUMN `cleared_by` INT(10) UNSIGNED DEFAULT NULL;");
    }
    if (!in_array('bounced_at', $cols)) {
        $db->exec("ALTER TABLE `supplier_cheques` ADD COLUMN `bounced_at` DATETIME DEFAULT NULL;");
    }
    if (!in_array('bounced_by', $cols)) {
        $db->exec("ALTER TABLE `supplier_cheques` ADD COLUMN `bounced_by` INT(10) UNSIGNED DEFAULT NULL;");
    }
    if (!in_array('bounced_reason', $cols)) {
        $db->exec("ALTER TABLE `supplier_cheques` ADD COLUMN `bounced_reason` VARCHAR(255) DEFAULT NULL;");
    }
    if (!in_array('cancelled_at', $cols)) {
        $db->exec("ALTER TABLE `supplier_cheques` ADD COLUMN `cancelled_at` DATETIME DEFAULT NULL;");
    }
    if (!in_array('cancelled_by', $cols)) {
        $db->exec("ALTER TABLE `supplier_cheques` ADD COLUMN `cancelled_by` INT(10) UNSIGNED DEFAULT NULL;");
    }
    if (!in_array('cancellation_reason', $cols)) {
        $db->exec("ALTER TABLE `supplier_cheques` ADD COLUMN `cancellation_reason` VARCHAR(255) DEFAULT NULL;");
    }
    if (!in_array('journal_entry_id', $cols)) {
        $db->exec("ALTER TABLE `supplier_cheques` ADD COLUMN `journal_entry_id` BIGINT(20) UNSIGNED DEFAULT NULL;");
    }
    echo "  - Table 'supplier_cheques' enhanced with lifecycle tracking columns.\n";

    // 6. Seed System COA accounts
    $db->exec("
        INSERT IGNORE INTO `chart_of_accounts` (`account_code`, `account_name`, `account_type`, `parent_id`, `branch_id`, `is_system`, `status`) VALUES
        ('1045', 'Supplier Credit / Receivable Account', 'asset', 3, NULL, 1, 'active'),
        ('2025', 'Outstanding Cheques / Cheques Issued Account', 'liability', 7, NULL, 1, 'active');
    ");
    echo "  - COA accounts 1045 (Supplier Credit Asset) & 2025 (Cheques Issued Liability) seeded.\n";

    // 7. Seed Stage 15 Permissions
    $db->exec("
        INSERT IGNORE INTO `permissions` (`id`, `module`, `permission_code`, `description`) VALUES
        (72, 'supplier_returns', 'view_supplier_returns', 'View supplier purchase returns and credit history'),
        (73, 'supplier_returns', 'create_supplier_return', 'Create supplier purchase returns against GRN'),
        (74, 'supplier_returns', 'manage_supplier_credits', 'Settle supplier credit via refund or future GRN offset'),
        (75, 'cheques', 'view_cheques', 'View supplier cheques management register'),
        (76, 'cheques', 'clear_cheque', 'Confirm supplier cheque clearing to bank'),
        (77, 'cheques', 'bounce_cheque', 'Mark supplier cheque as bounced/returned and restore AP'),
        (78, 'cheques', 'cancel_cheque', 'Cancel pending supplier cheque and reverse allocation');
    ");
    echo "  - Stage 15 permissions seeded.\n";

    // 8. Assign Permissions to Roles
    $db->exec("INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) SELECT 1, id FROM `permissions` WHERE id BETWEEN 72 AND 78;"); // Super Admin
    $db->exec("INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) SELECT 2, id FROM `permissions` WHERE id BETWEEN 72 AND 78;"); // Admin
    $db->exec("INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) SELECT 3, id FROM `permissions` WHERE id BETWEEN 72 AND 78;"); // Manager
    $db->exec("INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) SELECT 5, id FROM `permissions` WHERE id BETWEEN 72 AND 78;"); // Accountant

    echo "Stage 15 Database Migration Completed Successfully!\n";

} catch (Throwable $e) {
    echo "Migration Error: " . $e->getMessage() . "\n";
    exit(1);
}
