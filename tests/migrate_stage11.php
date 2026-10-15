<?php
/**
 * Curtiss POS - Stage 11 Database Migration Script
 * Creates business_days, cash_drawer_movements tables,
 * and seeds Stage 11 permissions.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

echo "Executing Stage 11 Migrations...\n";

try {
    $pdo = Database::getInstance()->getConnection();

    // 1. Business Days Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `business_days` (
          `id` BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `branch_id` INT(10) UNSIGNED NOT NULL,
          `business_date` DATE NOT NULL,
          `opening_cash` DECIMAL(12,2) NOT NULL DEFAULT 20000.00,
          `expected_cash` DECIMAL(12,2) DEFAULT NULL,
          `actual_cash` DECIMAL(12,2) DEFAULT NULL,
          `shortage_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          `overage_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          `retained_cash` DECIMAL(12,2) NOT NULL DEFAULT 20000.00,
          `cash_to_deposit` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          `status` ENUM('open', 'closed') NOT NULL DEFAULT 'open',
          `opened_by` INT(10) UNSIGNED NOT NULL,
          `opened_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `closed_by` INT(10) UNSIGNED DEFAULT NULL,
          `closed_at` DATETIME DEFAULT NULL,
          `notes` TEXT DEFAULT NULL,
          FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`),
          FOREIGN KEY (`opened_by`) REFERENCES `users`(`id`),
          FOREIGN KEY (`closed_by`) REFERENCES `users`(`id`),
          INDEX `idx_bd_branch_status` (`branch_id`, `status`),
          INDEX `idx_bd_date` (`business_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "[OK] Table business_days created or exists.\n";

    // 2. Cash Drawer Movements Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `cash_drawer_movements` (
          `id` BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `business_day_id` BIGINT(20) UNSIGNED NOT NULL,
          `branch_id` INT(10) UNSIGNED NOT NULL,
          `movement_type` ENUM('opening_cash', 'pos_sale', 'customer_collection', 'cash_refund', 'supplier_payment', 'cash_in', 'cash_out', 'shortage_adjustment', 'overage_adjustment') NOT NULL,
          `source_type` VARCHAR(50) DEFAULT NULL,
          `source_id` BIGINT(20) UNSIGNED DEFAULT NULL,
          `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          `direction` ENUM('in', 'out') NOT NULL,
          `reason` VARCHAR(255) DEFAULT NULL,
          `notes` TEXT DEFAULT NULL,
          `user_id` INT(10) UNSIGNED NOT NULL,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (`business_day_id`) REFERENCES `business_days`(`id`) ON DELETE CASCADE,
          FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`),
          FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
          INDEX `idx_cdm_bday` (`business_day_id`),
          INDEX `idx_cdm_type` (`movement_type`),
          INDEX `idx_cdm_source` (`source_type`, `source_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "[OK] Table cash_drawer_movements created or exists.\n";

    // 3. Seed Stage 11 Permissions
    $pdo->exec("
        INSERT IGNORE INTO `permissions` (`id`, `module`, `permission_code`, `description`) VALUES
        (48, 'cash_drawer', 'day.open', 'Start/Open new business day'),
        (49, 'cash_drawer', 'day.close', 'Perform day closing and count reconciliation'),
        (50, 'cash_drawer', 'cash_drawer.view', 'View cash drawer status, movements, and closing history'),
        (51, 'cash_drawer', 'cash_drawer.move', 'Perform manual Cash In and Cash Out drawer movements');
    ");
    echo "[OK] Stage 11 permissions inserted.\n";

    // Assign permissions to Super Admin (1), Admin (2), Manager (3), Accountant (5), Cashier (4)
    $pdo->exec("
        INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
        SELECT 1, id FROM `permissions` WHERE id BETWEEN 48 AND 51;

        INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
        SELECT 2, id FROM `permissions` WHERE id BETWEEN 48 AND 51;

        INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
        SELECT 3, id FROM `permissions` WHERE id BETWEEN 48 AND 51;

        INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
        SELECT 5, id FROM `permissions` WHERE id BETWEEN 48 AND 51;

        INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
        SELECT 4, id FROM `permissions` WHERE id IN (48, 50, 51);
    ");
    echo "[OK] Permissions assigned to roles.\n";

    echo "Stage 11 Migrations completed successfully!\n";

} catch (Throwable $e) {
    echo "MIGRATION ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
