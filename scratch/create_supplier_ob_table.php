<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

try {
    $sql = "
    CREATE TABLE IF NOT EXISTS `supplier_opening_balances` (
      `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
      `supplier_id` INT(10) UNSIGNED NOT NULL,
      `branch_id` INT(10) UNSIGNED NOT NULL DEFAULT 1,
      `entry_date` DATE NOT NULL,
      `balance_type` ENUM('owe_supplier', 'supplier_owes_us') NOT NULL,
      `amount` DECIMAL(12,2) NOT NULL,
      `reference_no` VARCHAR(100) DEFAULT NULL,
      `notes` TEXT DEFAULT NULL,
      `journal_entry_id` BIGINT(20) UNSIGNED DEFAULT NULL,
      `status` ENUM('posted', 'reversed') NOT NULL DEFAULT 'posted',
      `reversed_by` INT(10) UNSIGNED DEFAULT NULL,
      `reversed_at` DATETIME DEFAULT NULL,
      `reversal_notes` TEXT DEFAULT NULL,
      `reversal_journal_id` BIGINT(20) UNSIGNED DEFAULT NULL,
      `created_by` INT(10) UNSIGNED NOT NULL,
      `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `supplier_id` (`supplier_id`),
      KEY `branch_id` (`branch_id`),
      KEY `status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    Database::execute($sql);
    echo "Table supplier_opening_balances created or verified successfully!\n";
} catch (Throwable $e) {
    echo "Error creating table: " . $e->getMessage() . "\n";
}
