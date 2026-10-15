<?php
/**
 * Migration Script for Stage 7: Payments, Sale Finalization & Gift Vouchers
 */

require_once __DIR__ . '/../core/Database.php';

try {
    $db = Database::getInstance()->getConnection();

    // 1. Table: gift_vouchers
    $db->exec("
        CREATE TABLE IF NOT EXISTS `gift_vouchers` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `voucher_code` VARCHAR(100) NOT NULL UNIQUE,
            `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `issue_date` DATE NOT NULL,
            `expiry_date` DATE NOT NULL,
            `status` ENUM('active', 'redeemed', 'expired', 'cancelled') NOT NULL DEFAULT 'active',
            `created_by` INT UNSIGNED NOT NULL,
            `redeemed_sale_id` BIGINT UNSIGNED NULL,
            `redeemed_at` DATETIME NULL,
            `notes` TEXT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT `fk_gv_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
            CONSTRAINT `fk_gv_redeemed_sale` FOREIGN KEY (`redeemed_sale_id`) REFERENCES `sales` (`id`) ON DELETE SET NULL,
            INDEX `idx_gv_code` (`voucher_code`),
            INDEX `idx_gv_status` (`status`),
            INDEX `idx_gv_expiry` (`expiry_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 2. Table: payments
    $db->exec("
        CREATE TABLE IF NOT EXISTS `payments` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `payment_no` VARCHAR(50) NOT NULL UNIQUE,
            `sale_id` BIGINT UNSIGNED NOT NULL,
            `branch_id` INT UNSIGNED NOT NULL,
            `customer_id` INT UNSIGNED NOT NULL,
            `payment_method` ENUM('Cash', 'Card', 'Bank', 'Credit', 'Voucher') NOT NULL,
            `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `reference_no` VARCHAR(100) NULL,
            `voucher_id` INT UNSIGNED NULL,
            `user_id` INT UNSIGNED NOT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT `fk_pay_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_pay_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
            CONSTRAINT `fk_pay_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
            CONSTRAINT `fk_pay_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `gift_vouchers` (`id`) ON DELETE SET NULL,
            CONSTRAINT `fk_pay_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
            INDEX `idx_pay_sale` (`sale_id`),
            INDEX `idx_pay_branch` (`branch_id`),
            INDEX `idx_pay_customer` (`customer_id`),
            INDEX `idx_pay_method` (`payment_method`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 3. Stage 7 Permissions
    $db->exec("
        INSERT IGNORE INTO `permissions` (`id`, `permission_code`, `module`, `description`) VALUES
        (33, 'vouchers.view', 'vouchers', 'View gift vouchers list and redemption status'),
        (34, 'vouchers.manage', 'vouchers', 'Create and manage gift vouchers'),
        (35, 'vouchers.redeem', 'vouchers', 'Redeem gift vouchers during POS payment settlement'),
        (36, 'sales.reprint_receipt', 'pos', 'Reprint customer sales receipt');
    ");

    $db->exec("
        INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES
        (1, 33), (1, 34), (1, 35), (1, 36),
        (2, 33), (2, 34), (2, 35), (2, 36),
        (3, 33), (3, 34), (3, 35), (3, 36),
        (4, 33), (4, 35), (4, 36),
        (5, 33), (5, 36);
    ");

    echo "Stage 7 database migration completed successfully.\n";
} catch (Throwable $e) {
    echo "Stage 7 Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
