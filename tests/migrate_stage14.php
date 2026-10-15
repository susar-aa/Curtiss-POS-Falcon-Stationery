<?php
/**
 * Curtiss POS - Stage 14 Database Migration Script
 * Creates stock_transfers, stock_transfer_items, stock_adjustments, stock_audits, stock_audit_items tables.
 * Alters branch_inventory to add in_transit_quantity & damaged_quantity.
 * Alters damaged_stock to support wastage, disposal tracking, and accounting link.
 * Seeds Stage 14 permissions & role assignments.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "Starting Stage 14 Migration...\n";

    // 1. Alter branch_inventory to add in_transit_quantity & damaged_quantity
    $cols = $db->query("SHOW COLUMNS FROM `branch_inventory`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('in_transit_quantity', $cols)) {
        $db->exec("ALTER TABLE `branch_inventory` ADD COLUMN `in_transit_quantity` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `quantity`;");
        echo "  - Added 'in_transit_quantity' to branch_inventory.\n";
    }
    if (!in_array('damaged_quantity', $cols)) {
        $db->exec("ALTER TABLE `branch_inventory` ADD COLUMN `damaged_quantity` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `in_transit_quantity`;");
        echo "  - Added 'damaged_quantity' to branch_inventory.\n";
    }

    // 2. Create stock_transfers Table
    $db->exec("
        CREATE TABLE IF NOT EXISTS `stock_transfers` (
          `id` BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `transfer_number` VARCHAR(50) NOT NULL UNIQUE,
          `from_branch_id` INT(10) UNSIGNED NOT NULL,
          `to_branch_id` INT(10) UNSIGNED NOT NULL,
          `transfer_date` DATE NOT NULL,
          `status` ENUM('draft', 'sent', 'received', 'cancelled') NOT NULL DEFAULT 'draft',
          `notes` TEXT DEFAULT NULL,
          `created_by` INT(10) UNSIGNED NOT NULL,
          `sent_by` INT(10) UNSIGNED DEFAULT NULL,
          `sent_at` DATETIME DEFAULT NULL,
          `received_by` INT(10) UNSIGNED DEFAULT NULL,
          `received_at` DATETIME DEFAULT NULL,
          `cancelled_by` INT(10) UNSIGNED DEFAULT NULL,
          `cancelled_at` DATETIME DEFAULT NULL,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (`from_branch_id`) REFERENCES `branches`(`id`),
          FOREIGN KEY (`to_branch_id`) REFERENCES `branches`(`id`),
          FOREIGN KEY (`created_by`) REFERENCES `users`(`id`),
          FOREIGN KEY (`sent_by`) REFERENCES `users`(`id`),
          FOREIGN KEY (`received_by`) REFERENCES `users`(`id`),
          FOREIGN KEY (`cancelled_by`) REFERENCES `users`(`id`),
          INDEX `idx_st_from` (`from_branch_id`),
          INDEX `idx_st_to` (`to_branch_id`),
          INDEX `idx_st_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  - Table 'stock_transfers' ready.\n";

    // 3. Create stock_transfer_items Table
    $db->exec("
        CREATE TABLE IF NOT EXISTS `stock_transfer_items` (
          `id` BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `transfer_id` BIGINT(20) UNSIGNED NOT NULL,
          `product_id` INT(10) UNSIGNED NOT NULL,
          `source_lot_id` INT(10) UNSIGNED NOT NULL,
          `dest_lot_id` INT(10) UNSIGNED DEFAULT NULL,
          `quantity` DECIMAL(12,2) NOT NULL,
          `cost` DECIMAL(12,2) NOT NULL,
          `selling_price` DECIMAL(12,2) NOT NULL,
          FOREIGN KEY (`transfer_id`) REFERENCES `stock_transfers`(`id`) ON DELETE CASCADE,
          FOREIGN KEY (`product_id`) REFERENCES `products`(`id`),
          FOREIGN KEY (`source_lot_id`) REFERENCES `product_lots`(`id`),
          FOREIGN KEY (`dest_lot_id`) REFERENCES `product_lots`(`id`),
          INDEX `idx_sti_transfer` (`transfer_id`),
          INDEX `idx_sti_product` (`product_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  - Table 'stock_transfer_items' ready.\n";

    // 4. Create stock_adjustments Table
    $db->exec("
        CREATE TABLE IF NOT EXISTS `stock_adjustments` (
          `id` BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `adjustment_number` VARCHAR(50) NOT NULL UNIQUE,
          `branch_id` INT(10) UNSIGNED NOT NULL,
          `product_id` INT(10) UNSIGNED NOT NULL,
          `lot_id` INT(10) UNSIGNED NOT NULL,
          `adjustment_type` ENUM('increase', 'decrease') NOT NULL,
          `quantity` DECIMAL(12,2) NOT NULL,
          `cost` DECIMAL(12,2) NOT NULL,
          `reason` VARCHAR(255) NOT NULL,
          `notes` TEXT DEFAULT NULL,
          `journal_entry_id` BIGINT(20) UNSIGNED DEFAULT NULL,
          `created_by` INT(10) UNSIGNED NOT NULL,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`),
          FOREIGN KEY (`product_id`) REFERENCES `products`(`id`),
          FOREIGN KEY (`lot_id`) REFERENCES `product_lots`(`id`),
          FOREIGN KEY (`created_by`) REFERENCES `users`(`id`),
          INDEX `idx_sa_branch` (`branch_id`),
          INDEX `idx_sa_product` (`product_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  - Table 'stock_adjustments' ready.\n";

    // 5. Alter damaged_stock Table to expand Stage 8
    $dsCols = $db->query("SHOW COLUMNS FROM `damaged_stock`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('stock_type', $dsCols)) {
        $db->exec("ALTER TABLE `damaged_stock` ADD COLUMN `stock_type` ENUM('damaged', 'wastage') NOT NULL DEFAULT 'damaged' AFTER `quantity`;");
    }
    if (!in_array('cost', $dsCols)) {
        $db->exec("ALTER TABLE `damaged_stock` ADD COLUMN `cost` DECIMAL(12,2) DEFAULT NULL AFTER `stock_type`;");
    }
    if (!in_array('status', $dsCols)) {
        $db->exec("ALTER TABLE `damaged_stock` ADD COLUMN `status` ENUM('active', 'disposed') NOT NULL DEFAULT 'active' AFTER `sales_return_id`;");
    }
    if (!in_array('disposed_at', $dsCols)) {
        $db->exec("ALTER TABLE `damaged_stock` ADD COLUMN `disposed_at` DATETIME DEFAULT NULL;");
    }
    if (!in_array('disposed_by', $dsCols)) {
        $db->exec("ALTER TABLE `damaged_stock` ADD COLUMN `disposed_by` INT(10) UNSIGNED DEFAULT NULL;");
    }
    if (!in_array('disposal_notes', $dsCols)) {
        $db->exec("ALTER TABLE `damaged_stock` ADD COLUMN `disposal_notes` TEXT DEFAULT NULL;");
    }
    if (!in_array('journal_entry_id', $dsCols)) {
        $db->exec("ALTER TABLE `damaged_stock` ADD COLUMN `journal_entry_id` BIGINT(20) UNSIGNED DEFAULT NULL;");
    }
    echo "  - Table 'damaged_stock' enhanced with wastage, disposal, & accounting columns.\n";

    // 6. Create stock_audits Table
    $db->exec("
        CREATE TABLE IF NOT EXISTS `stock_audits` (
          `id` BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `audit_number` VARCHAR(50) NOT NULL UNIQUE,
          `branch_id` INT(10) UNSIGNED NOT NULL,
          `audit_date` DATE NOT NULL,
          `status` ENUM('draft', 'counting', 'review', 'posted') NOT NULL DEFAULT 'draft',
          `notes` TEXT DEFAULT NULL,
          `created_by` INT(10) UNSIGNED NOT NULL,
          `posted_by` INT(10) UNSIGNED DEFAULT NULL,
          `posted_at` DATETIME DEFAULT NULL,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`),
          FOREIGN KEY (`created_by`) REFERENCES `users`(`id`),
          FOREIGN KEY (`posted_by`) REFERENCES `users`(`id`),
          INDEX `idx_aud_branch` (`branch_id`),
          INDEX `idx_aud_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  - Table 'stock_audits' ready.\n";

    // 7. Create stock_audit_items Table
    $db->exec("
        CREATE TABLE IF NOT EXISTS `stock_audit_items` (
          `id` BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `audit_id` BIGINT(20) UNSIGNED NOT NULL,
          `product_id` INT(10) UNSIGNED NOT NULL,
          `lot_id` INT(10) UNSIGNED NOT NULL,
          `expected_quantity` DECIMAL(12,2) NOT NULL,
          `counted_quantity` DECIMAL(12,2) NOT NULL,
          `variance_quantity` DECIMAL(12,2) NOT NULL,
          `cost` DECIMAL(12,2) NOT NULL,
          `notes` TEXT DEFAULT NULL,
          FOREIGN KEY (`audit_id`) REFERENCES `stock_audits`(`id`) ON DELETE CASCADE,
          FOREIGN KEY (`product_id`) REFERENCES `products`(`id`),
          FOREIGN KEY (`lot_id`) REFERENCES `product_lots`(`id`),
          INDEX `idx_sai_audit` (`audit_id`),
          INDEX `idx_sai_product` (`product_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  - Table 'stock_audit_items' ready.\n";

    // 8. Seed COA accounts for Inventory Adjustments & Damage/Wastage
    $db->exec("
        INSERT IGNORE INTO `chart_of_accounts` (`account_code`, `account_name`, `account_type`, `parent_id`, `branch_id`, `is_system`, `status`) VALUES
        ('5300', 'Inventory Adjustment Account', 'expense', 16, NULL, 1, 'active'),
        ('5310', 'Damage & Wastage Expense Account', 'expense', 16, NULL, 1, 'active');
    ");
    echo "  - COA accounts 5300 (Inventory Adjustment) & 5310 (Damage/Wastage) seeded.\n";

    // 9. Seed Stage 14 Permissions
    $db->exec("
        INSERT IGNORE INTO `permissions` (`id`, `module`, `permission_code`, `description`) VALUES
        (65, 'transfers', 'view_stock_transfers', 'View branch stock transfer requests and history'),
        (66, 'transfers', 'create_stock_transfer', 'Create and send branch stock transfers'),
        (67, 'transfers', 'receive_stock_transfer', 'Receive and confirm branch stock transfers'),
        (68, 'inventory', 'manage_stock_adjustments', 'Post manual stock adjustments (increase and decrease)'),
        (69, 'inventory', 'manage_damaged_stock', 'Move stock to damaged or wastage and record disposal'),
        (70, 'inventory', 'manage_stock_audits', 'Create, count, review and post physical stock audits'),
        (71, 'inventory', 'view_stock_ledger', 'View full append-only stock movement ledger history');
    ");
    echo "  - Stage 14 permissions seeded.\n";

    // 10. Assign Permissions to Roles
    $db->exec("INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) SELECT 1, id FROM `permissions` WHERE id BETWEEN 65 AND 71;"); // Super Admin
    $db->exec("INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) SELECT 2, id FROM `permissions` WHERE id BETWEEN 65 AND 71;"); // Admin
    $db->exec("INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) SELECT 3, id FROM `permissions` WHERE id BETWEEN 65 AND 71;"); // Manager
    $db->exec("INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) SELECT 5, id FROM `permissions` WHERE id BETWEEN 65 AND 71;"); // Accountant
    $db->exec("INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES (4, 65), (4, 67), (4, 71);"); // Cashier
    echo "  - Role permissions assigned.\n";

    echo "Stage 14 Database Migration Completed Successfully!\n";

} catch (Throwable $e) {
    echo "Migration Error: " . $e->getMessage() . "\n";
    exit(1);
}
