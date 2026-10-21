-- ==============================================================================
-- Falcon POS - Live Database Synchronization Script
-- Compatible with MySQL 8.0+ and MariaDB 10.3+
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Ensure Missing Columns Exist on Existing Tables
-- ------------------------------------------------------------------------------

-- products
ALTER TABLE `products` 
    ADD COLUMN IF NOT EXISTS `description` varchar(255) NULL;

-- sales
ALTER TABLE `sales` 
    ADD COLUMN IF NOT EXISTS `promo_discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00;

-- sale_items
ALTER TABLE `sale_items` 
    ADD COLUMN IF NOT EXISTS `cost_price_at_sale` decimal(10,2) NOT NULL DEFAULT 0.00,
    ADD COLUMN IF NOT EXISTS `is_free_item` tinyint(1) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `unit_discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
    ADD COLUMN IF NOT EXISTS `gross_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
    ADD COLUMN IF NOT EXISTS `applied_rule_id` int(10) unsigned NULL,
    ADD COLUMN IF NOT EXISTS `promo_discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
    ADD COLUMN IF NOT EXISTS `promo_name` varchar(150) NULL;

-- bank_accounts
ALTER TABLE `bank_accounts`
    ADD COLUMN IF NOT EXISTS `short_name` varchar(50) NULL,
    ADD COLUMN IF NOT EXISTS `created_by` int(10) unsigned NULL,
    ADD COLUMN IF NOT EXISTS `updated_by` int(10) unsigned NULL;

-- business_days
ALTER TABLE `business_days`
    ADD COLUMN IF NOT EXISTS `actual_cash_counted` decimal(12,2) NULL,
    ADD COLUMN IF NOT EXISTS `variance` decimal(12,2) NULL,
    ADD COLUMN IF NOT EXISTS `closing_notes` text NULL;

-- customer_cheques
ALTER TABLE `customer_cheques`
    ADD COLUMN IF NOT EXISTS `deposited_at` datetime NULL,
    ADD COLUMN IF NOT EXISTS `deposited_by` int(10) unsigned NULL;

-- supplier_cheques
ALTER TABLE `supplier_cheques`
    ADD COLUMN IF NOT EXISTS `cleared_at` datetime NULL,
    ADD COLUMN IF NOT EXISTS `cleared_by` int(10) unsigned NULL,
    ADD COLUMN IF NOT EXISTS `bounced_at` datetime NULL,
    ADD COLUMN IF NOT EXISTS `bounced_by` int(10) unsigned NULL;

-- expense_categories
ALTER TABLE `expense_categories`
    ADD COLUMN IF NOT EXISTS `code` varchar(50) NULL,
    ADD COLUMN IF NOT EXISTS `description` text NULL,
    ADD COLUMN IF NOT EXISTS `is_active` tinyint(1) NOT NULL DEFAULT 1;

-- petty_cash_vouchers
ALTER TABLE `petty_cash_vouchers`
    ADD COLUMN IF NOT EXISTS `payment_method` enum('cash','cheque','bank_transfer') NOT NULL DEFAULT 'cash',
    ADD COLUMN IF NOT EXISTS `bank_account_id` int(10) unsigned NULL;

-- gift_vouchers
ALTER TABLE `gift_vouchers`
    ADD COLUMN IF NOT EXISTS `recipient_name` varchar(150) NULL,
    ADD COLUMN IF NOT EXISTS `recipient_phone` varchar(30) NULL;

-- koko_transactions
ALTER TABLE `koko_transactions`
    ADD COLUMN IF NOT EXISTS `currency` varchar(10) NOT NULL DEFAULT 'LKR',
    ADD COLUMN IF NOT EXISTS `refund_id` varchar(100) NULL,
    ADD COLUMN IF NOT EXISTS `refund_status` varchar(50) NULL,
    ADD COLUMN IF NOT EXISTS `refund_amount` decimal(10,2) NULL,
    ADD COLUMN IF NOT EXISTS `refund_reason` varchar(255) NULL,
    ADD COLUMN IF NOT EXISTS `raw_refund_response` text NULL;

-- users (PIN login support)
ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `pin` varchar(64) NULL,
    ADD COLUMN IF NOT EXISTS `pin_hash` varchar(255) NULL;

-- 2. Create Missing Tables (If Not Existing)
-- ------------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `discount_rules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text,
  `type` enum('percentage','fixed_amount','bogo','bundle','happy_hour') NOT NULL,
  `value` decimal(10,2) NOT NULL DEFAULT '0.00',
  `min_spend` decimal(10,2) DEFAULT '0.00',
  `applies_to` enum('entire_order','specific_products','specific_categories','customer_group') NOT NULL DEFAULT 'entire_order',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `days_of_week` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_active_dates` (`is_active`,`start_date`,`end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `discount_rule_targets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `rule_id` int NOT NULL,
  `target_type` enum('product','category','customer') NOT NULL,
  `target_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `rule_id` (`rule_id`),
  KEY `idx_target` (`target_type`,`target_id`),
  CONSTRAINT `discount_rule_targets_ibfk_1` FOREIGN KEY (`rule_id`) REFERENCES `discount_rules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expenses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `expense_number` varchar(50) NOT NULL,
  `branch_id` int NOT NULL,
  `category_id` int NOT NULL,
  `supplier_id` int DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `expense_date` date NOT NULL,
  `payment_method` enum('cash','bank_transfer','cheque','petty_cash') NOT NULL DEFAULT 'cash',
  `bank_account_id` int DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `status` enum('draft','pending','approved','paid','voided') NOT NULL DEFAULT 'paid',
  `description` text,
  `created_by` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `expense_number` (`expense_number`),
  KEY `branch_id` (`branch_id`),
  KEY `category_id` (`category_id`),
  KEY `expense_date` (`expense_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expense_attachments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `expense_id` int NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int DEFAULT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `expense_id` (`expense_id`),
  CONSTRAINT `expense_attachments_ibfk_1` FOREIGN KEY (`expense_id`) REFERENCES `expenses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expense_payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `expense_id` int NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` enum('cash','bank_transfer','cheque','petty_cash') NOT NULL,
  `bank_account_id` int DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `notes` text,
  `created_by` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `expense_id` (`expense_id`),
  CONSTRAINT `expense_payments_ibfk_1` FOREIGN KEY (`expense_id`) REFERENCES `expenses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nav_wallpaper_cache` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `image_url` text NOT NULL,
  `cached_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_suppliers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `supplier_id` int(10) unsigned NOT NULL,
  `supplier_cost_price` decimal(10,2) DEFAULT NULL,
  `supplier_sku` varchar(100) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_product_supplier` (`product_id`,`supplier_id`),
  KEY `supplier_id` (`supplier_id`),
  CONSTRAINT `product_suppliers_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_suppliers_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sale_discounts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sale_id` bigint(20) unsigned NOT NULL,
  `rule_id` int DEFAULT NULL,
  `discount_name` varchar(150) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `sale_id` (`sale_id`),
  KEY `rule_id` (`rule_id`),
  CONSTRAINT `sale_discounts_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `permissions` json DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Upsert Falcon Admin User and PIN
-- ------------------------------------------------------------------------------
INSERT INTO `users` (`username`, `email`, `password_hash`, `pin`, `pin_hash`, `full_name`, `phone`, `role_id`, `status`)
VALUES (
    'falcon', 
    'admin@falconstationery.lk', 
    '$2y$10$wz8J1G0Bq7gq7V1fJc4xle9N8F7lIknv13x8lZ2Yp4GzY3H6xL7z6', 
    '12369', 
    '$2y$10$3n57m/2M6XfB1Q5wE9zS6.vH2qT8gN7e0Z8mC3yB4jE9xV5l0Q6t2', 
    'Falcon Admin', 
    '0771234567', 
    1, 
    'active'
)
ON DUPLICATE KEY UPDATE
    `password_hash` = VALUES(`password_hash`),
    `pin` = VALUES(`pin`),
    `pin_hash` = VALUES(`pin_hash`),
    `status` = 'active';

-- Assign Falcon user to default branch 1 if not already assigned
INSERT IGNORE INTO `user_branches` (`user_id`, `branch_id`, `is_primary`)
SELECT u.id, 1, 1 
FROM `users` u 
WHERE u.username = 'falcon';

-- 4. Update Business Settings in system_settings
-- ------------------------------------------------------------------------------
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_group`, `description`, `updated_at`)
VALUES 
    ('business_name', 'Falcon Stationery', 'general', 'Company business name', NOW()),
    ('business_email', 'info@falconstationery.lk', 'general', 'Company email', NOW()),
    ('business_website', 'www.falconstationery.lk', 'general', 'Company website', NOW()),
    ('business_address', 'Falcon Stationery, Main Street', 'general', 'Company address', NOW()),
    ('app_name', 'Falcon POS', 'general', 'Application branding', NOW())
ON DUPLICATE KEY UPDATE 
    `setting_value` = VALUES(`setting_value`),
    `updated_at` = NOW();

SET FOREIGN_KEY_CHECKS = 1;
