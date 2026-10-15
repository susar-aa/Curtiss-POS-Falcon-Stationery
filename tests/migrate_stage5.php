<?php
/**
 * Migration Script for Stage 5: Customers & Customer Credit Foundation
 */

require_once __DIR__ . '/../core/Database.php';

try {
    $db = Database::getInstance()->getConnection();

    $db->exec("
        CREATE TABLE IF NOT EXISTS `customers` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `customer_code` VARCHAR(50) NOT NULL UNIQUE,
            `name` VARCHAR(150) NOT NULL,
            `phone` VARCHAR(30) NULL,
            `whatsapp` VARCHAR(30) NULL,
            `address` TEXT NULL,
            `card_barcode` VARCHAR(100) NULL UNIQUE,
            `credit_limit` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `credit_balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `is_walkin` TINYINT(1) NOT NULL DEFAULT 0,
            `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `deleted_at` DATETIME NULL DEFAULT NULL,
            INDEX `idx_cust_barcode` (`card_barcode`),
            INDEX `idx_cust_phone` (`phone`),
            INDEX `idx_cust_name` (`name`),
            INDEX `idx_cust_status` (`status`),
            INDEX `idx_cust_deleted` (`deleted_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS `customer_credit_ledger` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `customer_id` INT UNSIGNED NOT NULL,
            `transaction_type` ENUM('credit_sale', 'payment', 'opening_balance', 'adjustment') NOT NULL,
            `reference_no` VARCHAR(100) NULL,
            `invoice_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `amount_paid` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `amount_due` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `balance_after` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `notes` TEXT NULL,
            `branch_id` INT UNSIGNED NULL,
            `user_id` INT UNSIGNED NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT `fk_ccl_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_ccl_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
            CONSTRAINT `fk_ccl_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
            INDEX `idx_ccl_customer` (`customer_id`),
            INDEX `idx_ccl_type` (`transaction_type`),
            INDEX `idx_ccl_created` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    $db->exec("
        INSERT IGNORE INTO `customers` (`id`, `customer_code`, `name`, `phone`, `whatsapp`, `address`, `card_barcode`, `credit_limit`, `credit_balance`, `is_walkin`, `status`) VALUES
        (1, 'CUST-WALKIN', 'Walk-in Customer', NULL, NULL, 'Default POS General Customer', NULL, 0.00, 0.00, 1, 'active');
    ");

    $db->exec("
        INSERT IGNORE INTO `permissions` (`id`, `permission_code`, `module`, `description`) VALUES
        (25, 'customers.view', 'customers', 'View customer list, profile and credit history'),
        (26, 'customers.manage', 'customers', 'Create and edit customer accounts and barcode cards'),
        (27, 'customers.status', 'customers', 'Activate or deactivate customer accounts');
    ");

    $db->exec("
        INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES
        (1, 25), (1, 26), (1, 27),
        (2, 25), (2, 26), (2, 27),
        (3, 25), (3, 26), (3, 27),
        (4, 25), (4, 26),
        (5, 25), (5, 26),
        (6, 25);
    ");

    echo "Stage 5 database migration completed successfully.\n";
} catch (Throwable $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
