-- ============================================================================
-- CURTISS POS - DATABASE SCHEMA (STAGE 1 + STAGE 2 + STAGE 3 + STAGE 4 PURCHASING & GRN + STAGE 5 CUSTOMERS + STAGE 6 POS BILLING + STAGE 7 PAYMENTS & VOUCHERS)
-- Brand: Curtiss POS
-- Target: MySQL / MariaDB (XAMPP & VPS aaPanel Compatible)
-- Last Updated: 2026-09-24
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `curtiss_pos` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `curtiss_pos`;

SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- 1. Table: roles
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `role_permissions`;
DROP TABLE IF EXISTS `permissions`;
DROP TABLE IF EXISTS `user_branches`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `roles`;

CREATE TABLE `roles` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `code` VARCHAR(30) NOT NULL UNIQUE,
    `description` VARCHAR(255) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 2. Table: permissions
-- ----------------------------------------------------------------------------
CREATE TABLE `permissions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `permission_code` VARCHAR(80) NOT NULL UNIQUE,
    `module` VARCHAR(50) NOT NULL,
    `description` VARCHAR(255) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 3. Table: role_permissions
-- ----------------------------------------------------------------------------
CREATE TABLE `role_permissions` (
    `role_id` INT UNSIGNED NOT NULL,
    `permission_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`role_id`, `permission_id`),
    CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 4. Table: branches
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `branches`;

CREATE TABLE `branches` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `code` VARCHAR(20) NOT NULL UNIQUE,
    `address` TEXT NULL,
    `phone` VARCHAR(30) NULL,
    `email` VARCHAR(100) NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME NULL DEFAULT NULL,
    INDEX `idx_branches_status` (`status`),
    INDEX `idx_branches_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 5. Table: users
-- ----------------------------------------------------------------------------
CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(30) NULL,
    `role_id` INT UNSIGNED NOT NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `last_login_at` DATETIME NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME NULL DEFAULT NULL,
    CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`),
    INDEX `idx_users_role` (`role_id`),
    INDEX `idx_users_status` (`status`),
    INDEX `idx_users_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 6. Table: user_branches
-- ----------------------------------------------------------------------------
CREATE TABLE `user_branches` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `branch_id` INT UNSIGNED NOT NULL,
    `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
    `assigned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_user_branch` (`user_id`, `branch_id`),
    CONSTRAINT `fk_ub_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ub_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 7. Table: system_settings
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `system_settings`;

CREATE TABLE `system_settings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(80) NOT NULL UNIQUE,
    `setting_value` TEXT NULL,
    `setting_group` VARCHAR(50) NOT NULL DEFAULT 'general',
    `description` VARCHAR(255) NULL,
    `updated_by` INT UNSIGNED NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_settings_group` (`setting_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 8. Table: audit_logs
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `audit_logs`;

CREATE TABLE `audit_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NULL,
    `branch_id` INT UNSIGNED NULL,
    `action` VARCHAR(100) NOT NULL,
    `module` VARCHAR(50) NOT NULL,
    `record_id` VARCHAR(50) NULL,
    `old_values` LONGTEXT NULL,
    `new_values` LONGTEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(255) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_audit_user` (`user_id`),
    INDEX `idx_audit_branch` (`branch_id`),
    INDEX `idx_audit_module` (`module`),
    INDEX `idx_audit_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 9. Table: error_logs
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `error_logs`;

CREATE TABLE `error_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NULL,
    `error_level` VARCHAR(20) NOT NULL,
    `message` TEXT NOT NULL,
    `file` VARCHAR(255) NULL,
    `line` INT UNSIGNED NULL,
    `trace` LONGTEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_error_level` (`error_level`),
    INDEX `idx_error_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- STAGE 2 & 3 TABLES: BRANDS, CATEGORIES, SUPPLIERS, PRODUCTS, INVENTORY, LOTS, LEDGER
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `gift_vouchers`;
DROP TABLE IF EXISTS `sale_items`;
DROP TABLE IF EXISTS `sales`;
DROP TABLE IF EXISTS `customer_credit_ledger`;
DROP TABLE IF EXISTS `customers`;
DROP TABLE IF EXISTS `grn_items`;
DROP TABLE IF EXISTS `grns`;
DROP TABLE IF EXISTS `purchase_order_items`;
DROP TABLE IF EXISTS `purchase_orders`;
DROP TABLE IF EXISTS `stock_ledger`;
DROP TABLE IF EXISTS `product_lots`;
DROP TABLE IF EXISTS `branch_inventory`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `brands`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `suppliers`;

CREATE TABLE `brands` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME NULL DEFAULT NULL,
    INDEX `idx_brands_name` (`name`),
    INDEX `idx_brands_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `categories` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME NULL DEFAULT NULL,
    INDEX `idx_categories_name` (`name`),
    INDEX `idx_categories_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `suppliers` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(30) NULL,
    `whatsapp` VARCHAR(30) NULL,
    `address` TEXT NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME NULL DEFAULT NULL,
    INDEX `idx_suppliers_name` (`name`),
    INDEX `idx_suppliers_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `products` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `barcode` VARCHAR(50) NOT NULL,
    `sku` VARCHAR(100) NULL,
    `brand_id` INT UNSIGNED NOT NULL,
    `category_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(200) NOT NULL,
    `supplier_id` INT UNSIGNED NOT NULL,
    `cost` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `selling_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `opening_stock` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `low_stock_threshold` DECIMAL(10,2) NOT NULL DEFAULT 10.00,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME NULL DEFAULT NULL,
    CONSTRAINT `fk_prod_brand` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`),
    CONSTRAINT `fk_prod_cat` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
    CONSTRAINT `fk_prod_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
    INDEX `idx_prod_barcode` (`barcode`),
    INDEX `idx_prod_name` (`name`),
    INDEX `idx_prod_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `branch_inventory` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `branch_id` INT UNSIGNED NOT NULL,
    `product_id` INT UNSIGNED NOT NULL,
    `quantity` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `low_stock_threshold` DECIMAL(12,2) NOT NULL DEFAULT 10.00,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_branch_product` (`branch_id`, `product_id`),
    CONSTRAINT `fk_bi_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_bi_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_lots` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `lot_number` VARCHAR(50) NOT NULL,
    `product_id` INT UNSIGNED NOT NULL,
    `branch_id` INT UNSIGNED NOT NULL,
    `cost` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `selling_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `initial_quantity` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `remaining_quantity` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `status` ENUM('active', 'depleted', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_lot_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_lot_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
    INDEX `idx_lot_product` (`product_id`),
    INDEX `idx_lot_branch` (`branch_id`),
    INDEX `idx_lot_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `stock_ledger` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `branch_id` INT UNSIGNED NOT NULL,
    `product_id` INT UNSIGNED NOT NULL,
    `lot_id` INT UNSIGNED NULL,
    `movement_type` ENUM('opening_stock', 'purchase_grn', 'sale', 'customer_return', 'supplier_return', 'transfer_in', 'transfer_out', 'adjustment', 'damage_wastage') NOT NULL,
    `quantity` DECIMAL(12,2) NOT NULL,
    `balance_after` DECIMAL(12,2) NOT NULL,
    `reference_no` VARCHAR(100) NULL,
    `notes` TEXT NULL,
    `user_id` INT UNSIGNED NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_sl_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
    CONSTRAINT `fk_sl_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
    CONSTRAINT `fk_sl_lot` FOREIGN KEY (`lot_id`) REFERENCES `product_lots` (`id`) ON DELETE SET NULL,
    INDEX `idx_sl_branch` (`branch_id`),
    INDEX `idx_sl_product` (`product_id`),
    INDEX `idx_sl_movement` (`movement_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- STAGE 4 TABLES: PURCHASE ORDERS & GRN
-- ----------------------------------------------------------------------------
CREATE TABLE `purchase_orders` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `po_number` VARCHAR(50) NOT NULL UNIQUE,
    `supplier_id` INT UNSIGNED NOT NULL,
    `branch_id` INT UNSIGNED NOT NULL,
    `order_date` DATE NOT NULL,
    `expected_date` DATE NULL,
    `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `discount_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `net_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `notes` TEXT NULL,
    `status` ENUM('Draft', 'Approved', 'Cancelled', 'Completed') NOT NULL DEFAULT 'Draft',
    `created_by` INT UNSIGNED NOT NULL,
    `approved_by` INT UNSIGNED NULL,
    `approved_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_po_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
    CONSTRAINT `fk_po_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
    CONSTRAINT `fk_po_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
    CONSTRAINT `fk_po_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
    INDEX `idx_po_supplier` (`supplier_id`),
    INDEX `idx_po_branch` (`branch_id`),
    INDEX `idx_po_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `purchase_order_items` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `po_id` INT UNSIGNED NOT NULL,
    `product_id` INT UNSIGNED NOT NULL,
    `quantity` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `received_quantity` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `unit_cost` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `selling_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `line_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    CONSTRAINT `fk_poi_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_poi_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
    INDEX `idx_poi_po` (`po_id`),
    INDEX `idx_poi_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `grns` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `grn_number` VARCHAR(50) NOT NULL UNIQUE,
    `po_id` INT UNSIGNED NULL,
    `supplier_id` INT UNSIGNED NOT NULL,
    `branch_id` INT UNSIGNED NOT NULL,
    `supplier_invoice_no` VARCHAR(100) NOT NULL,
    `supplier_invoice_date` DATE NULL,
    `received_date` DATE NOT NULL,
    `payment_due_date` DATE NULL,
    `actual_grn_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `supplier_invoice_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `difference_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `difference_reason` ENUM('None', 'Missing items', 'Damaged items', 'Quantity difference', 'Price difference', 'Other') NOT NULL DEFAULT 'None',
    `notes` TEXT NULL,
    `status` ENUM('Draft', 'Approved', 'Cancelled') NOT NULL DEFAULT 'Draft',
    `created_by` INT UNSIGNED NOT NULL,
    `approved_by` INT UNSIGNED NULL,
    `approved_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_grn_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_grn_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
    CONSTRAINT `fk_grn_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
    CONSTRAINT `fk_grn_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
    CONSTRAINT `fk_grn_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
    INDEX `idx_grn_supplier` (`supplier_id`),
    INDEX `idx_grn_branch` (`branch_id`),
    INDEX `idx_grn_invoice` (`supplier_invoice_no`),
    INDEX `idx_grn_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `grn_items` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `grn_id` INT UNSIGNED NOT NULL,
    `product_id` INT UNSIGNED NOT NULL,
    `quantity` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `unit_cost` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `selling_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `line_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `lot_id` INT UNSIGNED NULL,
    CONSTRAINT `fk_gi_grn` FOREIGN KEY (`grn_id`) REFERENCES `grns` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_gi_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
    CONSTRAINT `fk_gi_lot` FOREIGN KEY (`lot_id`) REFERENCES `product_lots` (`id`) ON DELETE SET NULL,
    INDEX `idx_gi_grn` (`grn_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- STAGE 5 TABLES: CUSTOMERS & CUSTOMER CREDIT FOUNDATION
-- ----------------------------------------------------------------------------
CREATE TABLE `customers` (
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

CREATE TABLE `customer_credit_ledger` (
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
    INDEX `idx_ccl_type` (`transaction_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- STAGE 6 TABLES: POS BILLING & SALES CORE
-- ----------------------------------------------------------------------------
CREATE TABLE `sales` (
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

CREATE TABLE `sale_items` (
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

-- ----------------------------------------------------------------------------
-- STAGE 7 TABLES: PAYMENTS, SALE FINALIZATION & GIFT VOUCHERS
-- ----------------------------------------------------------------------------
CREATE TABLE `gift_vouchers` (
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

CREATE TABLE `payments` (
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

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- SEED DATA SETUP
-- ============================================================================

-- Seed Roles
INSERT INTO `roles` (`id`, `name`, `code`, `description`) VALUES
(1, 'Super Admin', 'super_admin', 'System owner & developer account with unrestricted global access'),
(2, 'Admin', 'admin', 'Shop owner with multi-branch management capabilities'),
(3, 'Manager', 'manager', 'Branch manager with branch-level operational control'),
(4, 'Cashier', 'cashier', 'POS billing operator with terminal transaction access'),
(5, 'Accountant', 'accountant', 'Financial and ledger record manager'),
(6, 'Special User', 'special_user', 'Restricted view user with configurable visibility percentages');

-- Seed Permissions (Stage 1 to Stage 7)
INSERT INTO `permissions` (`id`, `permission_code`, `module`, `description`) VALUES
(1, 'dashboard.view', 'dashboard', 'View main dashboard'),
(2, 'branches.view', 'branches', 'View branch list and details'),
(3, 'branches.manage', 'branches', 'Create and edit branches'),
(4, 'users.view', 'users', 'View user accounts'),
(5, 'users.manage', 'users', 'Create and manage user accounts and branch assignments'),
(6, 'settings.view', 'settings', 'View system settings'),
(7, 'settings.manage', 'settings', 'Update system configuration'),
(8, 'audit.view', 'audit', 'View system audit logs'),
(9, 'products.view', 'products', 'View products list and details'),
(10, 'products.create', 'products', 'Create new products'),
(11, 'products.edit', 'products', 'Edit existing product details'),
(12, 'products.status', 'products', 'Activate or deactivate products'),
(13, 'brands.manage', 'brands', 'Manage brands list and status'),
(14, 'categories.manage', 'categories', 'Manage categories list and status'),
(15, 'suppliers.manage', 'suppliers', 'Manage suppliers list and status'),
(16, 'inventory.view', 'inventory', 'View branch inventory levels, lots and ledger history'),
(17, 'inventory.manage', 'inventory', 'Manage opening stock and stock adjustments'),
(18, 'inventory.lots', 'inventory', 'View product price and cost lots'),
(19, 'po.view', 'purchasing', 'View Purchase Orders'),
(20, 'po.create', 'purchasing', 'Create Purchase Orders'),
(21, 'po.approve', 'purchasing', 'Approve and cancel Purchase Orders'),
(22, 'grn.view', 'purchasing', 'View Goods Received Notes'),
(23, 'grn.create', 'purchasing', 'Create Goods Received Notes'),
(24, 'grn.approve', 'purchasing', 'Approve Goods Received Notes'),
(25, 'customers.view', 'customers', 'View customer list, profile and credit history'),
(26, 'customers.manage', 'customers', 'Create and edit customer accounts and barcode cards'),
(27, 'customers.status', 'customers', 'Activate or deactivate customer accounts'),
(28, 'pos.access', 'pos', 'Access POS billing screen'),
(29, 'sales.create', 'pos', 'Create POS billing transactions'),
(30, 'sales.hold', 'pos', 'Hold and resume POS invoices'),
(31, 'sales.override_price', 'pos', 'Override product selling price manually'),
(32, 'sales.discount', 'pos', 'Apply item and invoice level discounts'),
(33, 'vouchers.view', 'vouchers', 'View gift vouchers list and redemption status'),
(34, 'vouchers.manage', 'vouchers', 'Create and manage gift vouchers'),
(35, 'vouchers.redeem', 'vouchers', 'Redeem gift vouchers during POS payment settlement'),
(36, 'sales.reprint_receipt', 'pos', 'Reprint customer sales receipt');

-- Grant permissions to Roles
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 6), (1, 7), (1, 8), (1, 9), (1, 10), (1, 11), (1, 12), (1, 13), (1, 14), (1, 15), (1, 16), (1, 17), (1, 18), (1, 19), (1, 20), (1, 21), (1, 22), (1, 23), (1, 24), (1, 25), (1, 26), (1, 27), (1, 28), (1, 29), (1, 30), (1, 31), (1, 32), (1, 33), (1, 34), (1, 35), (1, 36),
(2, 1), (2, 2), (2, 3), (2, 4), (2, 5), (2, 6), (2, 7), (2, 8), (2, 9), (2, 10), (2, 11), (2, 12), (2, 13), (2, 14), (2, 15), (2, 16), (2, 17), (2, 18), (2, 19), (2, 20), (2, 21), (2, 22), (2, 23), (2, 24), (2, 25), (2, 26), (2, 27), (2, 28), (2, 29), (2, 30), (2, 31), (2, 32), (2, 33), (2, 34), (2, 35), (2, 36),
(3, 1), (3, 2), (3, 4), (3, 6), (3, 9), (3, 10), (3, 11), (3, 12), (3, 13), (3, 14), (3, 15), (3, 16), (3, 17), (3, 18), (3, 19), (3, 20), (3, 21), (3, 22), (3, 23), (3, 24), (3, 25), (3, 26), (3, 27), (3, 28), (3, 29), (3, 30), (3, 31), (3, 32), (3, 33), (3, 34), (3, 35), (3, 36),
(4, 1), (4, 9), (4, 16), (4, 25), (4, 26), (4, 28), (4, 29), (4, 30), (4, 31), (4, 32), (4, 33), (4, 35), (4, 36),
(5, 1), (5, 6), (5, 8), (5, 9), (5, 16), (5, 18), (5, 19), (5, 22), (5, 25), (5, 26), (5, 28), (5, 29), (5, 30), (5, 33), (5, 36),
(6, 1), (6, 9), (6, 16), (6, 19), (6, 22), (6, 25);

-- Seed Initial Branches
INSERT INTO `branches` (`id`, `name`, `code`, `address`, `phone`, `email`, `status`) VALUES
(1, 'Main Branch - Kandy', 'BR-KDY-01', 'No 123, Peradeniya Road, Kandy', '+94 81 222 3344', 'kandy@curtisspos.com', 'active'),
(2, 'Colombo Branch', 'BR-CMB-02', 'No 45, Galle Road, Colombo 03', '+94 11 255 6677', 'colombo@curtisspos.com', 'active');

-- Seed Initial Users
INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `full_name`, `phone`, `role_id`, `status`) VALUES
(1, 'superadmin', 'superadmin@curtisspos.com', '$2y$10$zhgicxbe8El0OBpuZzOUtumUoDdURyr6MtNGcZYGmntYTkdUsoKl6', 'Super Administrator', '+94 77 000 0000', 1, 'active'),
(2, 'admin', 'admin@curtisspos.com', '$2y$10$zhgicxbe8El0OBpuZzOUtumUoDdURyr6MtNGcZYGmntYTkdUsoKl6', 'Shop Owner Admin', '+94 77 111 2222', 2, 'active'),
(3, 'manager1', 'manager1@curtisspos.com', '$2y$10$zhgicxbe8El0OBpuZzOUtumUoDdURyr6MtNGcZYGmntYTkdUsoKl6', 'Kandy Manager', '+94 77 222 3333', 3, 'active'),
(4, 'cashier1', 'cashier1@curtisspos.com', '$2y$10$zhgicxbe8El0OBpuZzOUtumUoDdURyr6MtNGcZYGmntYTkdUsoKl6', 'Kandy Cashier 01', '+94 77 333 4444', 4, 'active'),
(5, 'accountant', 'accountant@curtisspos.com', '$2y$10$zhgicxbe8El0OBpuZzOUtumUoDdURyr6MtNGcZYGmntYTkdUsoKl6', 'Lead Accountant', '+94 77 444 5555', 5, 'active'),
(6, 'specialuser', 'specialuser@curtisspos.com', '$2y$10$zhgicxbe8El0OBpuZzOUtumUoDdURyr6MtNGcZYGmntYTkdUsoKl6', 'Auditor Special User', '+94 77 555 6666', 6, 'active');

-- User Branch Assignments
INSERT INTO `user_branches` (`user_id`, `branch_id`, `is_primary`) VALUES
(1, 1, 1), (1, 2, 0),
(2, 1, 1), (2, 2, 0),
(3, 1, 1),
(4, 1, 1),
(5, 1, 1), (5, 2, 0),
(6, 1, 1);

-- Seed System Settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_group`, `description`, `updated_by`) VALUES
('business_name', 'Curtiss POS', 'general', 'Business brand name displayed across system UI and receipts', 1),
('business_phone', '+94 81 222 3344', 'general', 'Main business contact hotline', 1),
('business_email', 'info@curtisspos.com', 'general', 'Main business email address', 1),
('currency_symbol', 'LKR', 'general', 'Currency symbol used for monetary displays', 1),
('session_inactivity_timeout', '3600', 'security', 'Session inactivity timeout in seconds (1 hour / 3600 seconds)', 1),
('default_opening_cash', '20000.00', 'pos', 'Default register opening cash float (LKR 20,000)', 1),
('low_stock_default', '10', 'inventory', 'Global default threshold for low stock warnings (Default = 10)', 1),
('receipt_printer_width', '88mm', 'pos', 'Default receipt width format', 1),
('special_user_visibility_pct', '50', 'general', 'Default visibility percentage for Special User role', 1);

-- Seed Brands, Categories, Suppliers
INSERT INTO `brands` (`id`, `name`, `status`) VALUES
(1, 'Atlas', 'active'),
(2, 'Richard', 'active'),
(3, 'Elephant House', 'active'),
(4, 'Munchee', 'active');

INSERT INTO `categories` (`id`, `name`, `status`) VALUES
(1, 'Ball Pen', 'active'),
(2, 'Exercise Books', 'active'),
(3, 'Beverages', 'active'),
(4, 'Biscuits', 'active');

INSERT INTO `suppliers` (`id`, `name`, `phone`, `whatsapp`, `address`, `status`) VALUES
(1, 'Atlas Axillia Co Ltd', '+94 11 269 1111', '+94 77 123 4567', 'No 96, Parakrama Road, Peliyagoda', 'active'),
(2, 'CBL Munchee Distributors', '+94 11 285 5555', '+94 77 234 5678', 'High Level Road, Makumbura, Pannipitiya', 'active'),
(3, 'Ceylon Cold Stores (Elephant House)', '+94 11 231 6000', '+94 77 345 6789', 'No 148, Vauxhall Street, Colombo 02', 'active');

-- Seed Default Walk-in Customer
INSERT INTO `customers` (`id`, `customer_code`, `name`, `phone`, `whatsapp`, `address`, `card_barcode`, `credit_limit`, `credit_balance`, `is_walkin`, `status`) VALUES
(1, 'CUST-WALKIN', 'Walk-in Customer', NULL, NULL, 'Default POS General Customer', NULL, 0.00, 0.00, 1, 'active');

-- Seed Demo Products
INSERT INTO `products` (`id`, `barcode`, `brand_id`, `category_id`, `name`, `supplier_id`, `cost`, `selling_price`, `opening_stock`, `low_stock_threshold`, `status`) VALUES
(1, '893450001001', 1, 1, 'Atlas Ball Pen Blue 0.5mm', 1, 20.00, 30.00, 100.00, 10.00, 'active'),
(2, '893450001002', 1, 1, 'Atlas Ball Pen Black 0.5mm', 1, 20.00, 30.00, 85.00, 10.00, 'active'),
(3, '893450002001', 1, 2, 'Atlas Exercise Books Single Line 80 Pages', 1, 75.00, 100.00, 150.00, 10.00, 'active'),
(4, '893450003001', 4, 4, 'Munchee Biscuits Chocolate Cream 100g', 2, 90.00, 120.00, 60.00, 10.00, 'active');

-- Seed Branch Inventory
INSERT INTO `branch_inventory` (`branch_id`, `product_id`, `quantity`, `low_stock_threshold`) VALUES
(1, 1, 100.00, 10.00),
(1, 2, 85.00, 10.00),
(1, 3, 150.00, 10.00),
(1, 4, 60.00, 10.00),
(2, 1, 40.00, 10.00),
(2, 3, 80.00, 10.00);

-- Seed Product Price/Cost Lots
INSERT INTO `product_lots` (`id`, `lot_number`, `product_id`, `branch_id`, `cost`, `selling_price`, `initial_quantity`, `remaining_quantity`, `status`) VALUES
(1, 'LOT-KDY-001', 1, 1, 20.00, 30.00, 100.00, 100.00, 'active'),
(2, 'LOT-KDY-002', 2, 1, 20.00, 30.00, 85.00, 85.00, 'active'),
(3, 'LOT-KDY-003', 3, 1, 75.00, 100.00, 150.00, 150.00, 'active'),
(4, 'LOT-KDY-004', 4, 1, 90.00, 120.00, 60.00, 60.00, 'active'),
(5, 'LOT-CMB-001', 1, 2, 20.00, 30.00, 40.00, 40.00, 'active'),
(6, 'LOT-CMB-002', 3, 2, 75.00, 100.00, 80.00, 80.00, 'active');

-- Seed Stock Ledger Entries
INSERT INTO `stock_ledger` (`branch_id`, `product_id`, `lot_id`, `movement_type`, `quantity`, `balance_after`, `reference_no`, `notes`, `user_id`) VALUES
(1, 1, 1, 'opening_stock', 100.00, 100.00, 'INIT-STOCK-KDY', 'Initial opening stock setup', 1),
(1, 2, 2, 'opening_stock', 85.00, 85.00, 'INIT-STOCK-KDY', 'Initial opening stock setup', 1),
(1, 3, 3, 'opening_stock', 150.00, 150.00, 'INIT-STOCK-KDY', 'Initial opening stock setup', 1),
(1, 4, 4, 'opening_stock', 60.00, 60.00, 'INIT-STOCK-KDY', 'Initial opening stock setup', 1),
(2, 1, 5, 'opening_stock', 40.00, 40.00, 'INIT-STOCK-CMB', 'Initial opening stock setup', 1),
(2, 3, 6, 'opening_stock', 80.00, 80.00, 'INIT-STOCK-CMB', 'Initial opening stock setup', 1);

-- Seed Purchase Order Demo
INSERT INTO `purchase_orders` (`id`, `po_number`, `supplier_id`, `branch_id`, `order_date`, `expected_date`, `total_amount`, `discount_amount`, `net_amount`, `notes`, `status`, `created_by`, `approved_by`, `approved_at`) VALUES
(1, 'PO-202609-0001', 1, 1, '2026-09-20', '2026-09-25', 5000.00, 0.00, 5000.00, 'Regular stationery restock', 'Approved', 1, 1, '2026-09-20 14:00:00');

INSERT INTO `purchase_order_items` (`id`, `po_id`, `product_id`, `quantity`, `received_quantity`, `unit_cost`, `selling_price`, `line_total`) VALUES
(1, 1, 1, 100.00, 100.00, 20.00, 30.00, 2000.00),
(2, 1, 2, 100.00, 0.00, 30.00, 45.00, 3000.00);

-- Seed GRN Demo
INSERT INTO `grns` (`id`, `grn_number`, `po_id`, `supplier_id`, `branch_id`, `supplier_invoice_no`, `supplier_invoice_date`, `received_date`, `actual_grn_total`, `supplier_invoice_amount`, `difference_amount`, `difference_reason`, `notes`, `status`, `created_by`, `approved_by`, `approved_at`) VALUES
(1, 'GRN-202609-0001', 1, 1, 1, 'INV-ATL-9944', '2026-09-21', '2026-09-22', 2000.00, 2000.00, 0.00, 'None', 'First batch received from PO-202609-0001', 'Approved', 1, 1, '2026-09-22 10:30:00');

INSERT INTO `grn_items` (`id`, `grn_id`, `product_id`, `quantity`, `unit_cost`, `selling_price`, `line_total`, `lot_id`) VALUES
(1, 1, 1, 100.00, 20.00, 30.00, 2000.00, 1);

-- ----------------------------------------------------------------------------
-- STAGE 10 TABLES: CHART OF ACCOUNTS & DOUBLE-ENTRY JOURNAL ENGINE
-- ----------------------------------------------------------------------------
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

-- Seed Default Hierarchical Chart of Accounts
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

-- Seed Stage 10 Permissions
INSERT IGNORE INTO `permissions` (`id`, `module`, `permission_code`, `description`) VALUES
(43, 'accounting', 'accounting.view', 'View Chart of Accounts, Journal Entries, and General Ledger'),
(44, 'accounting', 'accounts.manage', 'Create and manage Chart of Accounts'),
(45, 'accounting', 'accounting.post_manual', 'Create manual double-entry journal postings'),
(46, 'accounting', 'accounting.reverse', 'Post journal reversal entries'),
(47, 'accounting', 'accounting.backfill', 'Execute historical accounting backfill migration');

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) SELECT 1, id FROM `permissions` WHERE id BETWEEN 43 AND 47;
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) SELECT 2, id FROM `permissions` WHERE id BETWEEN 43 AND 47;
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) SELECT 3, id FROM `permissions` WHERE id BETWEEN 43 AND 47;
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) SELECT 5, id FROM `permissions` WHERE id BETWEEN 43 AND 47;

-- =========================================================
-- STAGE 11: BUSINESS DAY, CASH DRAWER & DAY CLOSING
-- =========================================================

CREATE TABLE IF NOT EXISTS `business_days` (
  `id` BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `branch_id` INT(10) UNSIGNED NOT NULL,
  `business_date` DATE NOT NULL,
  `opening_cash` DECIMAL(12,2) NOT NULL DEFAULT 20000.00,
  `expected_cash` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `actual_cash` DECIMAL(12,2) DEFAULT NULL,
  `shortage_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `overage_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `retained_cash` DECIMAL(12,2) NOT NULL DEFAULT 20000.00,
  `cash_to_deposit` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('open', 'closed') NOT NULL DEFAULT 'open',
  `opened_by` INT(10) UNSIGNED NOT NULL,
  `closed_by` INT(10) UNSIGNED DEFAULT NULL,
  `opened_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `closed_at` DATETIME DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`),
  FOREIGN KEY (`opened_by`) REFERENCES `users`(`id`),
  FOREIGN KEY (`closed_by`) REFERENCES `users`(`id`),
  INDEX `idx_bday_branch_status` (`branch_id`, `status`),
  INDEX `idx_bday_date` (`business_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cash_drawer_movements` (
  `id` BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `business_day_id` BIGINT(20) UNSIGNED NOT NULL,
  `branch_id` INT(10) UNSIGNED NOT NULL,
  `movement_type` ENUM('opening_cash', 'pos_sale', 'customer_collection', 'cash_in', 'cash_refund', 'supplier_payment', 'cash_out', 'shortage_adjustment', 'overage_adjustment') NOT NULL,
  `source_type` VARCHAR(50) DEFAULT NULL,
  `source_id` BIGINT(20) UNSIGNED DEFAULT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `direction` ENUM('in', 'out') NOT NULL,
  `reason` VARCHAR(255) NOT NULL,
  `notes` TEXT DEFAULT NULL,
  `user_id` INT(10) UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`business_day_id`) REFERENCES `business_days`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
  INDEX `idx_cdm_bday` (`business_day_id`),
  INDEX `idx_cdm_branch` (`branch_id`),
  INDEX `idx_cdm_type` (`movement_type`),
  INDEX `idx_cdm_source` (`source_type`, `source_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Stage 11 Permissions
INSERT IGNORE INTO `permissions` (`id`, `module`, `permission_code`, `description`) VALUES
(48, 'cash_drawer', 'start_business_day', 'Start / Open new business day for branch'),
(49, 'cash_drawer', 'manual_cash_movement', 'Perform manual drawer cash in and cash out'),
(50, 'cash_drawer', 'close_business_day', 'Perform day closing and cash reconciliation'),
(51, 'cash_drawer', 'view_closed_days', 'View closed business days log history'),
(52, 'cash_drawer', 'view_cash_drawer', 'View active cash drawer status and movement history');

-- Assign Stage 11 Permissions to Roles
-- Super Admin (1): All permissions
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) SELECT 1, id FROM `permissions` WHERE id BETWEEN 48 AND 52;

-- Admin (2): All permissions
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) SELECT 2, id FROM `permissions` WHERE id BETWEEN 48 AND 52;

-- Manager (3): All permissions
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) SELECT 3, id FROM `permissions` WHERE id BETWEEN 48 AND 52;

-- Accountant (5): View drawer, view closed days, close day
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES (5, 50), (5, 51), (5, 52);

-- Cashier (4): Start day, manual cash movement, view drawer
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES (4, 48), (4, 49), (4, 52);

-- =========================================================
-- STAGE 12: PETTY CASH & EXPENSE MANAGEMENT
-- =========================================================

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

-- Seed Default Expense Categories
INSERT IGNORE INTO `expense_categories` (`id`, `name`, `code`, `account_id`, `description`, `status`) VALUES
(1, 'Fuel & Travelling', 'EXP-FUEL', 17, 'Vehicle fuel, travelling allowance and transport costs', 'active'),
(2, 'Meals & Refreshments', 'EXP-MEALS', 17, 'Staff tea, meals and client entertainment', 'active'),
(3, 'Stationery & Printing', 'EXP-STAT', 17, 'Paper, pens, receipt rolls and office supplies', 'active'),
(4, 'Cleaning & Sanitation', 'EXP-CLEAN', 17, 'Cleaning products, detergents and waste management', 'active'),
(5, 'Repairs & Maintenance', 'EXP-REPAIR', 17, 'Minor store maintenance, bulb replacement and repairs', 'active'),
(6, 'Utilities & Postage', 'EXP-UTIL', 17, 'Minor courier fees, electricity/water bills, postage', 'active'),
(7, 'Miscellaneous Expenses', 'EXP-MISC', 17, 'General incidental petty expense items', 'active');

-- Seed Stage 12 Permissions
INSERT IGNORE INTO `permissions` (`id`, `module`, `permission_code`, `description`) VALUES
(53, 'petty_cash', 'view_petty_cash', 'View petty cash dashboard, balance and transactions'),
(54, 'petty_cash', 'create_expense', 'Create petty cash expense vouchers'),
(55, 'petty_cash', 'manage_expense_categories', 'Create and edit expense categories & COA mapping'),
(56, 'petty_cash', 'reimburse_petty_cash', 'Reimburse / top-up petty cash fund from bank or drawer'),
(57, 'petty_cash', 'adjust_petty_cash', 'Perform manual petty cash adjustments with accounting'),
(58, 'petty_cash', 'reverse_petty_cash_voucher', 'Cancel / reverse petty cash expense vouchers');

-- Assign Stage 12 Permissions to Roles
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) SELECT 1, id FROM `permissions` WHERE id BETWEEN 53 AND 58;
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) SELECT 2, id FROM `permissions` WHERE id BETWEEN 53 AND 58;
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) SELECT 3, id FROM `permissions` WHERE id BETWEEN 53 AND 58;

-- =============================================================================
-- STAGE 13: REAL BANK ACCOUNTS, DEPOSITS & BANK RECONCILIATION
-- =============================================================================

CREATE TABLE IF NOT EXISTS `bank_accounts` (
  `id` INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `bank_name` VARCHAR(100) NOT NULL,
  `account_name` VARCHAR(100) NOT NULL,
  `account_number` VARCHAR(50) NOT NULL UNIQUE,
  `branch_id` INT(10) UNSIGNED DEFAULT NULL,
  `bank_branch_name` VARCHAR(100) DEFAULT NULL,
  `opening_balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `account_id` INT(10) UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`),
  FOREIGN KEY (`account_id`) REFERENCES `chart_of_accounts`(`id`),
  INDEX `idx_bankacc_branch` (`branch_id`),
  INDEX `idx_bankacc_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Note: bank_account_id is added to payments, supplier_payments, refunds, petty_cash_reimbursements via migrate_stage13.php
ALTER TABLE `payments` ADD COLUMN IF NOT EXISTS `bank_account_id` INT(10) UNSIGNED DEFAULT NULL AFTER `branch_id`;

CREATE TABLE IF NOT EXISTS `bank_deposits` (
  `id` BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `deposit_no` VARCHAR(50) NOT NULL UNIQUE,
  `branch_id` INT(10) UNSIGNED NOT NULL,
  `bank_account_id` INT(10) UNSIGNED NOT NULL,
  `deposit_date` DATE NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `source_type` ENUM('active_drawer', 'closed_day', 'other_cash') NOT NULL DEFAULT 'active_drawer',
  `business_day_id` BIGINT(20) UNSIGNED DEFAULT NULL,
  `reference_no` VARCHAR(100) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `journal_entry_id` BIGINT(20) UNSIGNED DEFAULT NULL,
  `created_by` INT(10) UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`),
  FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts`(`id`),
  FOREIGN KEY (`business_day_id`) REFERENCES `business_days`(`id`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`),
  INDEX `idx_dep_bank` (`bank_account_id`),
  INDEX `idx_dep_branch` (`branch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bank_reconciliations` (
  `id` BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `reconciliation_no` VARCHAR(50) NOT NULL UNIQUE,
  `bank_account_id` INT(10) UNSIGNED NOT NULL,
  `branch_id` INT(10) UNSIGNED DEFAULT NULL,
  `statement_start_date` DATE NOT NULL,
  `statement_end_date` DATE NOT NULL,
  `statement_opening_balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `statement_closing_balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `book_balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `cleared_balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `outstanding_deposits` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `outstanding_payments` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `reconciliation_difference` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('draft', 'completed') NOT NULL DEFAULT 'draft',
  `reconciled_at` DATETIME DEFAULT NULL,
  `reconciled_by` INT(10) UNSIGNED DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_by` INT(10) UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts`(`id`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`),
  FOREIGN KEY (`reconciled_by`) REFERENCES `users`(`id`),
  INDEX `idx_brec_bank_status` (`bank_account_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bank_reconciliation_items` (
  `id` BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `reconciliation_id` BIGINT(20) UNSIGNED NOT NULL,
  `journal_line_id` BIGINT(20) UNSIGNED NOT NULL,
  `is_cleared` TINYINT(1) NOT NULL DEFAULT 0,
  `cleared_date` DATE DEFAULT NULL,
  FOREIGN KEY (`reconciliation_id`) REFERENCES `bank_reconciliations`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`journal_line_id`) REFERENCES `journal_lines`(`id`),
  INDEX `idx_bri_rec` (`reconciliation_id`),
  INDEX `idx_bri_line` (`journal_line_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Dedicated COA Bank Sub-Accounts
INSERT IGNORE INTO `chart_of_accounts` (`account_code`, `account_name`, `account_type`, `parent_id`, `branch_id`, `is_system`, `status`) VALUES
('1021', 'Commercial Bank - Main A/C 800991', 'asset', 3, NULL, 1, 'active'),
('1022', 'Sampath Bank - Store A/C 001920', 'asset', 3, NULL, 1, 'active');

-- Seed Default Company Bank Accounts
INSERT IGNORE INTO `bank_accounts` (`id`, `bank_name`, `account_name`, `account_number`, `branch_id`, `bank_branch_name`, `opening_balance`, `status`, `account_id`) VALUES
(1, 'Commercial Bank', 'Curtiss Kandy Main Account', '8009910022', NULL, 'Kandy Main Branch', 50000.00, 'active', (SELECT id FROM chart_of_accounts WHERE account_code = '1021')),
(2, 'Sampath Bank', 'Curtiss Kandy POS Collection Account', '0019203341', 1, 'Kandy City Center', 25000.00, 'active', (SELECT id FROM chart_of_accounts WHERE account_code = '1022'));

-- Seed Stage 13 Permissions
INSERT IGNORE INTO `permissions` (`id`, `module`, `permission_code`, `description`) VALUES
(59, 'banking', 'view_bank_accounts', 'View company bank accounts and balances'),
(60, 'banking', 'manage_bank_accounts', 'Create, edit and manage company bank accounts and COA mapping'),
(61, 'banking', 'make_bank_deposit', 'Deposit cash into company bank account'),
(62, 'banking', 'view_bank_register', 'View bank transaction activity register and running balances'),
(63, 'banking', 'manage_bank_reconciliation', 'Create, edit and complete bank reconciliation sessions'),
(64, 'banking', 'post_bank_adjustment', 'Post bank charges, interest and direct adjustments');


-- =============================================================================
-- STAGE 14: ADVANCED INVENTORY OPERATIONS
-- =============================================================================

ALTER TABLE `branch_inventory` ADD COLUMN IF NOT EXISTS `in_transit_quantity` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `quantity`;
ALTER TABLE `branch_inventory` ADD COLUMN IF NOT EXISTS `damaged_quantity` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `in_transit_quantity`;

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

INSERT IGNORE INTO `chart_of_accounts` (`account_code`, `account_name`, `account_type`, `parent_id`, `branch_id`, `is_system`, `status`) VALUES
('5300', 'Inventory Adjustment Account', 'expense', 16, NULL, 1, 'active'),
('5310', 'Damage & Wastage Expense Account', 'expense', 16, NULL, 1, 'active');

INSERT IGNORE INTO `permissions` (`id`, `module`, `permission_code`, `description`) VALUES
(65, 'transfers', 'view_stock_transfers', 'View branch stock transfer requests and history'),
(66, 'transfers', 'create_stock_transfer', 'Create and send branch stock transfers'),
(67, 'transfers', 'receive_stock_transfer', 'Receive and confirm branch stock transfers'),
(68, 'inventory', 'manage_stock_adjustments', 'Post manual stock adjustments (increase and decrease)'),
(69, 'inventory', 'manage_damaged_stock', 'Move stock to damaged or wastage and record disposal'),
(70, 'inventory', 'manage_stock_audits', 'Create, count, review and post physical stock audits'),
(71, 'inventory', 'view_stock_ledger', 'View full append-only stock movement ledger history');

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) SELECT 1, id FROM `permissions` WHERE id BETWEEN 65 AND 71;
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) SELECT 2, id FROM `permissions` WHERE id BETWEEN 65 AND 71;
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) SELECT 3, id FROM `permissions` WHERE id BETWEEN 65 AND 71;
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) SELECT 5, id FROM `permissions` WHERE id BETWEEN 65 AND 71;
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES (4, 65), (4, 67), (4, 71);

-- ============================================================================
-- STAGE 15: SUPPLIER RETURNS & CHEQUE MANAGEMENT
-- ============================================================================

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

INSERT IGNORE INTO `chart_of_accounts` (`account_code`, `account_name`, `account_type`, `parent_id`, `branch_id`, `is_system`, `status`) VALUES
('1045', 'Supplier Credit / Receivable Account', 'asset', 3, NULL, 1, 'active'),
('2025', 'Outstanding Cheques / Cheques Issued Account', 'liability', 7, NULL, 1, 'active');

INSERT IGNORE INTO `permissions` (`id`, `module`, `permission_code`, `description`) VALUES
(72, 'supplier_returns', 'view_supplier_returns', 'View supplier purchase returns and credit history'),
(73, 'supplier_returns', 'create_supplier_return', 'Create supplier purchase returns against GRN'),
(74, 'supplier_returns', 'manage_supplier_credits', 'Settle supplier credit via refund or future GRN offset'),
(75, 'cheques', 'view_cheques', 'View supplier cheques management register'),
(76, 'cheques', 'clear_cheque', 'Confirm supplier cheque clearing to bank'),
(77, 'cheques', 'bounce_cheque', 'Mark supplier cheque as bounced/returned and restore AP'),
(78, 'cheques', 'cancel_cheque', 'Cancel pending supplier cheque and reverse allocation');

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) SELECT 1, id FROM `permissions` WHERE id BETWEEN 72 AND 78;
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) SELECT 2, id FROM `permissions` WHERE id BETWEEN 72 AND 78;
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) SELECT 3, id FROM `permissions` WHERE id BETWEEN 72 AND 78;
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) SELECT 5, id FROM `permissions` WHERE id BETWEEN 72 AND 78;

-- Stage 16: Central Reports & Financial Statements
INSERT IGNORE INTO `chart_of_accounts` (`account_code`, `account_name`, `account_type`, `parent_id`, `branch_id`, `is_system`, `status`) VALUES
('4050', 'Bank Interest / Other Income Account', 'income', NULL, NULL, 1, 'active');

INSERT IGNORE INTO `permissions` (`id`, `module`, `permission_code`, `description`) VALUES
(79, 'reports', 'view_reports', 'View central reports and analytics viewer'),
(80, 'reports', 'export_reports', 'Export reports to Excel/CSV, PDF, and print format'),
(81, 'reports', 'view_financial_statements', 'View financial statements (Trial Balance, P&L, Balance Sheet)');

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) SELECT 1, id FROM `permissions` WHERE id BETWEEN 79 AND 81;
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) SELECT 2, id FROM `permissions` WHERE id BETWEEN 79 AND 81;
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) SELECT 3, id FROM `permissions` WHERE id BETWEEN 79 AND 81;
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) SELECT 5, id FROM `permissions` WHERE id BETWEEN 79 AND 81;

-- Stage 17: Special User Masked View System
INSERT IGNORE INTO `system_settings` (`setting_key`, `setting_value`, `setting_group`, `description`, `updated_by`) VALUES
('special_user_display_percentage', '60.00', 'security', 'Configured percentage for presentation-layer masking of sensitive financial & inventory values for Special User', 1),
('special_user_visibility_pct', '60.00', 'security', 'Legacy alias for special user visibility percentage', 1);

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES (6, 79), (6, 81);

-- Stage 18: Offline POS Billing & Synchronization
ALTER TABLE `sales` ADD COLUMN IF NOT EXISTS `offline_uuid` VARCHAR(64) NULL UNIQUE AFTER `invoice_no`;