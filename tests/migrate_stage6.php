<?php
/**
 * Migration Script for Stage 6: POS Billing & Sales Core
 */

require_once __DIR__ . '/../core/Database.php';

try {
    $db = Database::getInstance()->getConnection();

    $db->exec("
        CREATE TABLE IF NOT EXISTS `sales` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `invoice_no` VARCHAR(50) NOT NULL UNIQUE,
            `branch_id` INT UNSIGNED NOT NULL,
            `customer_id` INT UNSIGNED NOT NULL,
            `user_id` INT UNSIGNED NOT NULL,
            `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `discount_type` VARCHAR(20) NULL,
            `discount_value` VARCHAR(50) NULL,
            `discount_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `roundup_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `total_cost` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `status` ENUM('draft', 'held', 'completed', 'cancelled') NOT NULL DEFAULT 'draft',
            `held_at` DATETIME NULL,
            `held_notes` VARCHAR(255) NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT `fk_sales_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
            CONSTRAINT `fk_sales_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
            CONSTRAINT `fk_sales_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
            INDEX `idx_sales_invoice` (`invoice_no`),
            INDEX `idx_sales_branch` (`branch_id`),
            INDEX `idx_sales_customer` (`customer_id`),
            INDEX `idx_sales_status` (`status`),
            INDEX `idx_sales_created` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS `sale_items` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `sale_id` BIGINT UNSIGNED NOT NULL,
            `product_id` INT UNSIGNED NOT NULL,
            `lot_id` INT UNSIGNED NOT NULL,
            `quantity` DECIMAL(12,2) NOT NULL DEFAULT 1.00,
            `original_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `unit_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `unit_cost` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `discount_type` VARCHAR(20) NULL,
            `discount_value` VARCHAR(50) NULL,
            `discount_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `line_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `line_cost` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            CONSTRAINT `fk_si_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_si_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
            CONSTRAINT `fk_si_lot` FOREIGN KEY (`lot_id`) REFERENCES `product_lots` (`id`),
            INDEX `idx_si_sale` (`sale_id`),
            INDEX `idx_si_product` (`product_id`),
            INDEX `idx_si_lot` (`lot_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    $db->exec("
        INSERT IGNORE INTO `permissions` (`id`, `permission_code`, `module`, `description`) VALUES
        (28, 'pos.access', 'pos', 'Access POS billing screen'),
        (29, 'sales.create', 'pos', 'Create POS billing transactions'),
        (30, 'sales.hold', 'pos', 'Hold and resume POS invoices'),
        (31, 'sales.override_price', 'pos', 'Override product selling price manually'),
        (32, 'sales.discount', 'pos', 'Apply item and invoice level discounts');
    ");

    $db->exec("
        INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES
        (1, 28), (1, 29), (1, 30), (1, 31), (1, 32),
        (2, 28), (2, 29), (2, 30), (2, 31), (2, 32),
        (3, 28), (3, 29), (3, 30), (3, 31), (3, 32),
        (4, 28), (4, 29), (4, 30), (4, 31), (4, 32),
        (5, 28), (5, 29), (5, 30);
    ");

    $db->exec("
        INSERT IGNORE INTO `system_settings` (`setting_key`, `setting_value`, `setting_group`, `description`, `updated_by`) VALUES
        ('special_user_visibility_pct', '50', 'general', 'Default visibility percentage for Special User role', 1);
    ");

    echo "Stage 6 database migration completed successfully.\n";
} catch (Throwable $e) {
    echo "Stage 6 Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
