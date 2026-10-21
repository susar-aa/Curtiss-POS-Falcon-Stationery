<?php
/**
 * Migration: Create Discount & Promotion Management System Database Tables
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

$db = Database::getInstance()->getConnection();

echo "Running Discount System Migration...\n";

// 1. discount_rules
$db->exec("
CREATE TABLE IF NOT EXISTS `discount_rules` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `rule_code` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(150) NOT NULL,
    `description` TEXT NULL,
    `rule_type` ENUM('item', 'category', 'brand', 'supplier', 'invoice', 'quantity', 'bogo', 'bundle', 'customer', 'payment_method', 'coupon') NOT NULL DEFAULT 'item',
    `priority` INT NOT NULL DEFAULT 10,
    `is_exclusive` TINYINT(1) NOT NULL DEFAULT 0,
    `is_stackable` TINYINT(1) NOT NULL DEFAULT 1,
    `allow_manual_discount` TINYINT(1) NOT NULL DEFAULT 1,
    `discount_mode` ENUM('percentage', 'fixed_amount', 'fixed_price', 'free_qty') NOT NULL DEFAULT 'percentage',
    `discount_value` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `max_discount_amount` DECIMAL(12,2) NULL,
    `min_invoice_subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `min_quantity` DECIMAL(12,2) NOT NULL DEFAULT 1.00,
    `get_quantity` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `get_discount_mode` ENUM('free', 'percentage', 'fixed_amount') NULL,
    `get_discount_value` DECIMAL(12,2) NULL,
    `max_usage_total` INT NULL,
    `max_usage_per_customer` INT NULL,
    `max_usage_per_invoice` INT NULL,
    `usage_count` INT NOT NULL DEFAULT 0,
    `start_date` DATE NULL,
    `end_date` DATE NULL,
    `start_time` TIME NULL,
    `end_time` TIME NULL,
    `days_of_week` VARCHAR(50) NULL COMMENT 'Comma separated 1-7 (1=Mon, 7=Sun)',
    `branch_id` INT UNSIGNED NULL COMMENT 'NULL for all branches',
    `customer_id` INT UNSIGNED NULL,
    `payment_method` VARCHAR(50) NULL,
    `coupon_code` VARCHAR(50) NULL,
    `min_margin_percent` DECIMAL(5,2) NULL,
    `status` ENUM('active', 'inactive', 'scheduled', 'expired', 'archived') NOT NULL DEFAULT 'active',
    `created_by` INT UNSIGNED NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME NULL,
    INDEX `idx_disc_branch_status` (`branch_id`, `status`, `deleted_at`),
    INDEX `idx_disc_dates` (`start_date`, `end_date`),
    INDEX `idx_disc_coupon` (`coupon_code`),
    INDEX `idx_disc_priority` (`priority`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "Table `discount_rules` created or exists.\n";

// 2. discount_rule_targets
$db->exec("
CREATE TABLE IF NOT EXISTS `discount_rule_targets` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `rule_id` INT UNSIGNED NOT NULL,
    `target_type` ENUM('product', 'category', 'brand', 'supplier', 'lot', 'get_product', 'get_category') NOT NULL DEFAULT 'product',
    `target_id` INT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_target_lookup` (`rule_id`, `target_type`, `target_id`),
    INDEX `idx_target_entity` (`target_type`, `target_id`),
    FOREIGN KEY (`rule_id`) REFERENCES `discount_rules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "Table `discount_rule_targets` created or exists.\n";

// 3. sale_discounts (snapshot of applied promotions per invoice and per item)
$db->exec("
CREATE TABLE IF NOT EXISTS `sale_discounts` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `sale_id` BIGINT UNSIGNED NOT NULL,
    `sale_item_id` BIGINT UNSIGNED NULL COMMENT 'NULL for invoice-level discount',
    `rule_id` INT UNSIGNED NULL,
    `coupon_code` VARCHAR(50) NULL,
    `discount_name` VARCHAR(150) NOT NULL,
    `rule_type` VARCHAR(50) NOT NULL,
    `discount_mode` VARCHAR(50) NOT NULL,
    `discount_rate` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `discount_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_sd_sale` (`sale_id`),
    INDEX `idx_sd_item` (`sale_item_id`),
    INDEX `idx_sd_rule` (`rule_id`),
    INDEX `idx_sd_coupon` (`coupon_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "Table `sale_discounts` created or exists.\n";

// 4. Ensure sales table has promo discount breakdown column
$hasPromoCol = $db->query("
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sales' AND COLUMN_NAME = 'promo_discount_amount'
")->fetchColumn();

if (!$hasPromoCol) {
    $db->exec("ALTER TABLE `sales` ADD COLUMN `promo_discount_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `discount_amount`;");
    echo "Added column `sales.promo_discount_amount`.\n";
}

// 5. Ensure sale_items has promo discount columns
$hasItemPromoCol = $db->query("
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sale_items' AND COLUMN_NAME = 'promo_discount_amount'
")->fetchColumn();

if (!$hasItemPromoCol) {
    $db->exec("
        ALTER TABLE `sale_items` 
        ADD COLUMN `applied_rule_id` INT UNSIGNED NULL AFTER `discount_amount`,
        ADD COLUMN `promo_discount_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `applied_rule_id`,
        ADD COLUMN `promo_name` VARCHAR(150) NULL AFTER `promo_discount_amount`;
    ");
    echo "Added promo columns to `sale_items`.\n";
}

echo "Discount System Database Migration completed successfully.\n";
