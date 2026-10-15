<?php
/**
 * Migration: Customer Loyalty System & Default Discount Setting
 */

require_once __DIR__ . '/../../core/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "Running migration: Customer Loyalty System...\n";

    // 1. Add is_loyalty and loyalty_discount_override to customers table
    $stmt = $db->query("SHOW COLUMNS FROM customers LIKE 'is_loyalty'");
    if ($stmt->rowCount() === 0) {
        $db->exec("
            ALTER TABLE customers 
            ADD COLUMN is_loyalty TINYINT(1) NOT NULL DEFAULT 0 AFTER is_walkin,
            ADD COLUMN loyalty_discount_override DECIMAL(5,2) NULL DEFAULT NULL AFTER is_loyalty,
            ADD INDEX idx_customers_loyalty (is_loyalty)
        ");
        echo "Added 'is_loyalty' and 'loyalty_discount_override' columns to 'customers'.\n";
    } else {
        echo "Columns 'is_loyalty' already exist in 'customers'.\n";
    }

    // 2. Add default loyalty discount setting to system_settings if not present
    $stmtSetting = $db->prepare("SELECT id FROM system_settings WHERE setting_key = 'loyalty_discount_percentage'");
    $stmtSetting->execute();
    if ($stmtSetting->rowCount() === 0) {
        $db->exec("
            INSERT INTO system_settings (setting_key, setting_value, setting_group, description, updated_at)
            VALUES ('loyalty_discount_percentage', '5.00', 'pos', 'Default bill discount percentage for Loyalty Customers', NOW())
        ");
        echo "Inserted 'loyalty_discount_percentage' (5.00%) into 'system_settings'.\n";
    } else {
        echo "Setting 'loyalty_discount_percentage' already exists in 'system_settings'.\n";
    }

    echo "Migration completed successfully!\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
