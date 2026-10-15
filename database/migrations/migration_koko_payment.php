<?php
/**
 * Curtiss POS - KOKO Payments Gateway & Settlement Migration
 * Creates koko_transactions table, seeds COA 1032 (KOKO Payments Receivable),
 * and configures KOKO system settings.
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Database.php';

echo "Running Migration: KOKO Payments Gateway System...\n";

$db = Database::getInstance()->getConnection();

// 1. Create koko_transactions Table
$db->exec("
CREATE TABLE IF NOT EXISTS `koko_transactions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `sale_id` BIGINT UNSIGNED NULL,
    `invoice_no` VARCHAR(50) NOT NULL,
    `branch_id` INT UNSIGNED NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `currency` VARCHAR(10) NOT NULL DEFAULT 'LKR',
    `customer_phone` VARCHAR(30) NULL,
    `customer_name` VARCHAR(150) NULL,
    `koko_order_id` VARCHAR(100) NOT NULL UNIQUE,
    `koko_payment_id` VARCHAR(100) NULL,
    `payment_url` TEXT NULL,
    `qr_data` TEXT NULL,
    `channel` ENUM('qr', 'link', 'sms', 'direct') NOT NULL DEFAULT 'qr',
    `status` ENUM('initiated', 'pending', 'paid', 'failed', 'cancelled', 'expired') NOT NULL DEFAULT 'pending',
    `response_payload` LONGTEXT NULL,
    `paid_at` DATETIME NULL,
    `created_by` INT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_koko_invoice` (`invoice_no`),
    INDEX `idx_koko_order` (`koko_order_id`),
    INDEX `idx_koko_status` (`status`),
    INDEX `idx_koko_branch` (`branch_id`),
    INDEX `idx_koko_sale` (`sale_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "Created/verified koko_transactions table.\n";

// 2. Seed COA Account 1032: KOKO Payments Receivable
$existingCoa = Database::fetchOne("SELECT id FROM chart_of_accounts WHERE account_code = '1032'");
if (!$existingCoa) {
    Database::execute("
        INSERT INTO chart_of_accounts (account_code, account_name, account_type, is_system, status)
        VALUES ('1032', 'KOKO Payments Receivable', 'asset', 1, 'active')
    ");
    echo "Seeded COA Account 1032: KOKO Payments Receivable.\n";
} else {
    echo "COA Account 1032 already exists.\n";
}

// 3. Seed Default System Settings for KOKO
$defaultSettings = [
    'koko_enabled'          => '1',
    'koko_environment'      => 'sandbox',
    'koko_merchant_id'      => 'KOKO_MID_SAMPLE',
    'koko_api_key'          => 'koko_live_or_sandbox_key',
    'koko_api_secret'       => 'koko_live_or_sandbox_secret',
    'koko_sms_enabled'      => '1',
    'koko_merchant_name'    => 'Kandy Kids',
    'koko_currency'         => 'LKR'
];

foreach ($defaultSettings as $key => $val) {
    $existingSetting = Database::fetchOne("SELECT id FROM system_settings WHERE setting_key = :k", [':k' => $key]);
    if (!$existingSetting) {
        Database::execute("
            INSERT INTO system_settings (setting_key, setting_value, setting_group, updated_at)
            VALUES (:k, :v, 'payment_gateways', NOW())
        ", [':k' => $key, ':v' => $val]);
        echo "Created setting: {$key} = {$val}\n";
    } else {
        echo "Setting {$key} already exists.\n";
    }
}

echo "Migration migration_koko_payment.php successfully completed!\n";
