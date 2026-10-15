<?php
/**
 * Curtiss POS - Stage 8 Database Migration Script
 * Creates tables for sales returns, return items, damaged stock, and refunds,
 * and seeds Stage 8 permissions.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

echo "Executing Stage 8 Migrations...\n";

try {
    $pdo = Database::getInstance()->getConnection();

    // 1. Sales Returns Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `sales_returns` (
          `id` BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `return_no` VARCHAR(50) NOT NULL UNIQUE,
          `sale_id` BIGINT(20) UNSIGNED DEFAULT NULL,
          `branch_id` INT(10) UNSIGNED NOT NULL,
          `customer_id` INT(10) UNSIGNED DEFAULT NULL,
          `return_type` ENUM('return_only', 'exchange') NOT NULL DEFAULT 'return_only',
          `total_return_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          `total_exchange_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          `net_difference` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          `exchange_sale_id` BIGINT(20) UNSIGNED DEFAULT NULL,
          `notes` TEXT DEFAULT NULL,
          `created_by` INT(10) UNSIGNED NOT NULL,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (`sale_id`) REFERENCES `sales`(`id`) ON DELETE SET NULL,
          FOREIGN KEY (`exchange_sale_id`) REFERENCES `sales`(`id`) ON DELETE SET NULL,
          FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`),
          FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`),
          FOREIGN KEY (`created_by`) REFERENCES `users`(`id`),
          INDEX `idx_return_no` (`return_no`),
          INDEX `idx_return_sale` (`sale_id`),
          INDEX `idx_return_branch` (`branch_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "[OK] Table sales_returns created or exists.\n";

    // 2. Sales Return Items Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `sales_return_items` (
          `id` BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `sales_return_id` BIGINT(20) UNSIGNED NOT NULL,
          `sale_item_id` BIGINT(20) UNSIGNED DEFAULT NULL,
          `product_id` INT(10) UNSIGNED NOT NULL,
          `lot_id` INT(10) UNSIGNED NOT NULL,
          `quantity` DECIMAL(10,2) NOT NULL,
          `unit_price` DECIMAL(12,2) NOT NULL,
          `cost_price` DECIMAL(12,2) NOT NULL,
          `total_amount` DECIMAL(12,2) NOT NULL,
          `item_condition` ENUM('good', 'damaged') NOT NULL DEFAULT 'good',
          `reason` VARCHAR(255) DEFAULT NULL,
          FOREIGN KEY (`sales_return_id`) REFERENCES `sales_returns`(`id`) ON DELETE CASCADE,
          FOREIGN KEY (`sale_item_id`) REFERENCES `sale_items`(`id`) ON DELETE SET NULL,
          FOREIGN KEY (`product_id`) REFERENCES `products`(`id`),
          FOREIGN KEY (`lot_id`) REFERENCES `product_lots`(`id`),
          INDEX `idx_sri_return` (`sales_return_id`),
          INDEX `idx_sri_sale_item` (`sale_item_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "[OK] Table sales_return_items created or exists.\n";

    // 3. Damaged Stock Log Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `damaged_stock` (
          `id` BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `branch_id` INT(10) UNSIGNED NOT NULL,
          `product_id` INT(10) UNSIGNED NOT NULL,
          `lot_id` INT(10) UNSIGNED DEFAULT NULL,
          `quantity` DECIMAL(10,2) NOT NULL,
          `sales_return_id` BIGINT(20) UNSIGNED DEFAULT NULL,
          `reason` VARCHAR(255) DEFAULT NULL,
          `logged_by` INT(10) UNSIGNED NOT NULL,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`),
          FOREIGN KEY (`product_id`) REFERENCES `products`(`id`),
          FOREIGN KEY (`sales_return_id`) REFERENCES `sales_returns`(`id`) ON DELETE SET NULL,
          FOREIGN KEY (`logged_by`) REFERENCES `users`(`id`),
          INDEX `idx_damaged_branch` (`branch_id`),
          INDEX `idx_damaged_product` (`product_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "[OK] Table damaged_stock created or exists.\n";

    // 4. Refunds Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `refunds` (
          `id` BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `sales_return_id` BIGINT(20) UNSIGNED NOT NULL,
          `branch_id` INT(10) UNSIGNED NOT NULL,
          `customer_id` INT(10) UNSIGNED DEFAULT NULL,
          `refund_method` ENUM('cash', 'card', 'bank', 'credit', 'voucher') NOT NULL,
          `amount` DECIMAL(12,2) NOT NULL,
          `reference_no` VARCHAR(100) DEFAULT NULL,
          `replacement_voucher_id` INT(10) DEFAULT NULL,
          `created_by` INT(10) UNSIGNED NOT NULL,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (`sales_return_id`) REFERENCES `sales_returns`(`id`) ON DELETE CASCADE,
          FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`),
          FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`),
          FOREIGN KEY (`created_by`) REFERENCES `users`(`id`),
          INDEX `idx_refund_return` (`sales_return_id`),
          INDEX `idx_refund_method` (`refund_method`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "[OK] Table refunds created or exists.\n";

    // 5. Stage 8 Permissions
    $pdo->exec("
        INSERT IGNORE INTO `permissions` (`id`, `module`, `permission_code`, `description`) VALUES
        (37, 'returns', 'returns.view', 'View sales returns and history'),
        (38, 'returns', 'returns.create', 'Process sales returns, refunds and exchanges'),
        (39, 'returns', 'returns.no_invoice', 'Process returns without original invoice');
    ");
    echo "[OK] Permissions inserted.\n";

    // Assign permissions to Super Admin (1), Admin (2), Manager (3), Cashier (4)
    $pdo->exec("
        INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
        SELECT 1, id FROM `permissions` WHERE id BETWEEN 37 AND 39;

        INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
        SELECT 2, id FROM `permissions` WHERE id BETWEEN 37 AND 39;

        INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
        SELECT 3, id FROM `permissions` WHERE id BETWEEN 37 AND 39;

        INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
        SELECT 4, id FROM `permissions` WHERE id IN (37, 38, 39);
    ");
    echo "[OK] Permissions assigned to roles.\n";

    echo "Stage 8 Migrations completed successfully!\n";

} catch (Throwable $e) {
    echo "MIGRATION ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
