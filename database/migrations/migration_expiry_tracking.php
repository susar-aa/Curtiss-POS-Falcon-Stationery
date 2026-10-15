<?php
/**
 * Curtiss POS - Expiry Date & Batch Management Migration
 * Alters:
 * - products: adds has_expiry
 * - product_lots: adds expiry_date, batch_number, source_type, source_id
 * - grn_items: adds expiry_date, batch_number
 * - damaged_stock: modifies stock_type enum to include 'expired'
 * Seeds default system settings for expiry alert thresholds.
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Database.php';

echo "Running Migration: Product Expiry Date & Batch Management System...\n";

$db = Database::getInstance()->getConnection();

// 1. Update products table: add has_expiry
$hasExpiryCol = Database::fetchOne("SHOW COLUMNS FROM products LIKE 'has_expiry'");
if (!$hasExpiryCol) {
    $db->exec("
        ALTER TABLE `products`
        ADD COLUMN `has_expiry` TINYINT(1) NOT NULL DEFAULT 0 AFTER `low_stock_threshold`,
        ADD INDEX `idx_prod_has_expiry` (`has_expiry`);
    ");
    echo "Added has_expiry to products table.\n";
} else {
    echo "Column has_expiry already exists in products table.\n";
}

// 2. Update product_lots table: add expiry_date, batch_number, source_type, source_id
$lotsCols = Database::fetchAll("SHOW COLUMNS FROM product_lots");
$colNames = array_column($lotsCols, 'Field');

if (!in_array('expiry_date', $colNames, true)) {
    $db->exec("
        ALTER TABLE `product_lots`
        ADD COLUMN `expiry_date` DATE NULL AFTER `remaining_quantity`,
        ADD INDEX `idx_lots_expiry` (`expiry_date`);
    ");
    echo "Added expiry_date to product_lots table.\n";
} else {
    echo "Column expiry_date already exists in product_lots.\n";
}

if (!in_array('batch_number', $colNames, true)) {
    $db->exec("
        ALTER TABLE `product_lots`
        ADD COLUMN `batch_number` VARCHAR(100) NULL AFTER `lot_number`,
        ADD INDEX `idx_lots_batch` (`batch_number`);
    ");
    echo "Added batch_number to product_lots table.\n";
} else {
    echo "Column batch_number already exists in product_lots.\n";
}

if (!in_array('source_type', $colNames, true)) {
    $db->exec("
        ALTER TABLE `product_lots`
        ADD COLUMN `source_type` ENUM('opening_stock', 'grn', 'transfer', 'adjustment') NOT NULL DEFAULT 'grn' AFTER `expiry_date`,
        ADD COLUMN `source_id` INT UNSIGNED NULL AFTER `source_type`,
        ADD INDEX `idx_lots_source` (`source_type`, `source_id`);
    ");
    echo "Added source_type and source_id to product_lots table.\n";
} else {
    echo "Column source_type already exists in product_lots.\n";
}

// 3. Update grn_items table: add expiry_date, batch_number
$grnCols = Database::fetchAll("SHOW COLUMNS FROM grn_items");
$grnColNames = array_column($grnCols, 'Field');

if (!in_array('expiry_date', $grnColNames, true)) {
    $db->exec("
        ALTER TABLE `grn_items`
        ADD COLUMN `expiry_date` DATE NULL AFTER `selling_price`,
        ADD INDEX `idx_grn_items_expiry` (`expiry_date`);
    ");
    echo "Added expiry_date to grn_items table.\n";
} else {
    echo "Column expiry_date already exists in grn_items.\n";
}

if (!in_array('batch_number', $grnColNames, true)) {
    $db->exec("
        ALTER TABLE `grn_items`
        ADD COLUMN `batch_number` VARCHAR(100) NULL AFTER `expiry_date`,
        ADD INDEX `idx_grn_items_batch` (`batch_number`);
    ");
    echo "Added batch_number to grn_items table.\n";
} else {
    echo "Column batch_number already exists in grn_items.\n";
}

// 4. Update damaged_stock table: extend stock_type enum to include 'expired'
try {
    $db->exec("
        ALTER TABLE `damaged_stock`
        MODIFY COLUMN `stock_type` ENUM('damaged', 'wastage', 'expired') NOT NULL DEFAULT 'damaged';
    ");
    echo "Updated damaged_stock stock_type enum to include 'expired'.\n";
} catch (Throwable $e) {
    echo "Note on damaged_stock: " . $e->getMessage() . "\n";
}

// 5. Seed default system settings for Expiry Tracking
$defaultSettings = [
    'expiry_alert_days_soon' => '30',
    'expiry_block_sale_days' => '0',
    'expiry_fefo_enabled'    => '1'
];

foreach ($defaultSettings as $key => $val) {
    $existingSetting = Database::fetchOne("SELECT id FROM system_settings WHERE setting_key = :k", [':k' => $key]);
    if (!$existingSetting) {
        Database::execute("
            INSERT INTO system_settings (setting_key, setting_value, setting_group, description, updated_at)
            VALUES (:k, :v, 'inventory', 'Expiry Tracking System Parameter', NOW())
        ", [':k' => $key, ':v' => $val]);
        echo "Created setting: {$key} = {$val}\n";
    } else {
        echo "Setting {$key} already exists.\n";
    }
}

echo "Migration migration_expiry_tracking.php completed successfully!\n";
