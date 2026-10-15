<?php
/**
 * Curtiss POS - Stage 9 Database Migration Script
 * Adds paid_amount and payment_status to GRNs table,
 * creates supplier_payments, supplier_payment_grns, supplier_cheques tables,
 * and seeds Stage 9 permissions.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

echo "Executing Stage 9 Migrations...\n";

try {
    $pdo = Database::getInstance()->getConnection();

    // 1. Add paid_amount and payment_status columns to grns table if not exists
    $cols = $pdo->query("SHOW COLUMNS FROM `grns` LIKE 'paid_amount'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("
            ALTER TABLE `grns`
            ADD COLUMN `paid_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `actual_grn_total`,
            ADD COLUMN `payment_status` ENUM('unpaid', 'partially_paid', 'paid') NOT NULL DEFAULT 'unpaid' AFTER `paid_amount`;
        ");
        echo "[OK] Added paid_amount and payment_status columns to grns table.\n";
    } else {
        echo "[OK] Columns paid_amount and payment_status already exist on grns table.\n";
    }

    // 2. Supplier Payments Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `supplier_payments` (
          `id` BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `payment_no` VARCHAR(50) NOT NULL UNIQUE,
          `supplier_id` INT(10) UNSIGNED NOT NULL,
          `branch_id` INT(10) UNSIGNED NOT NULL,
          `payment_date` DATE NOT NULL,
          `payment_method` ENUM('Cash', 'Bank', 'Card', 'Cheque') NOT NULL DEFAULT 'Cash',
          `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          `reference_no` VARCHAR(100) DEFAULT NULL,
          `bank_name` VARCHAR(100) DEFAULT NULL,
          `notes` TEXT DEFAULT NULL,
          `created_by` INT(10) UNSIGNED NOT NULL,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`),
          FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`),
          FOREIGN KEY (`created_by`) REFERENCES `users`(`id`),
          INDEX `idx_sp_supplier` (`supplier_id`),
          INDEX `idx_sp_branch` (`branch_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "[OK] Table supplier_payments created or exists.\n";

    // 3. Supplier Payment GRN Allocations Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `supplier_payment_grns` (
          `id` BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `supplier_payment_id` BIGINT(20) UNSIGNED NOT NULL,
          `grn_id` INT(10) UNSIGNED NOT NULL,
          `allocated_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          FOREIGN KEY (`supplier_payment_id`) REFERENCES `supplier_payments`(`id`) ON DELETE CASCADE,
          FOREIGN KEY (`grn_id`) REFERENCES `grns`(`id`),
          INDEX `idx_spg_payment` (`supplier_payment_id`),
          INDEX `idx_spg_grn` (`grn_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "[OK] Table supplier_payment_grns created or exists.\n";

    // 4. Supplier Cheques Foundation Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `supplier_cheques` (
          `id` BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `supplier_payment_id` BIGINT(20) UNSIGNED DEFAULT NULL,
          `supplier_id` INT(10) UNSIGNED NOT NULL,
          `branch_id` INT(10) UNSIGNED NOT NULL,
          `cheque_number` VARCHAR(100) NOT NULL,
          `cheque_date` DATE NOT NULL,
          `bank_name` VARCHAR(100) NOT NULL,
          `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          `status` ENUM('pending', 'cleared', 'bounced', 'cancelled') NOT NULL DEFAULT 'pending',
          `created_by` INT(10) UNSIGNED NOT NULL,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (`supplier_payment_id`) REFERENCES `supplier_payments`(`id`) ON DELETE SET NULL,
          FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`),
          FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`),
          FOREIGN KEY (`created_by`) REFERENCES `users`(`id`),
          INDEX `idx_cheque_supplier` (`supplier_id`),
          INDEX `idx_cheque_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "[OK] Table supplier_cheques created or exists.\n";

    // 5. Stage 9 Permissions
    $pdo->exec("
        INSERT IGNORE INTO `permissions` (`id`, `module`, `permission_code`, `description`) VALUES
        (40, 'customers', 'customers.pay', 'Process customer credit outstanding collections'),
        (41, 'purchasing', 'suppliers.view_payments', 'View supplier outstanding balances and payment history'),
        (42, 'purchasing', 'suppliers.pay', 'Process payments to suppliers against approved GRNs');
    ");
    echo "[OK] Stage 9 permissions inserted.\n";

    // Assign permissions to Super Admin (1), Admin (2), Manager (3), Cashier (4), Accountant (5)
    $pdo->exec("
        INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
        SELECT 1, id FROM `permissions` WHERE id BETWEEN 40 AND 42;

        INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
        SELECT 2, id FROM `permissions` WHERE id BETWEEN 40 AND 42;

        INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
        SELECT 3, id FROM `permissions` WHERE id BETWEEN 40 AND 42;

        INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
        SELECT 4, id FROM `permissions` WHERE id IN (40);

        INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
        SELECT 5, id FROM `permissions` WHERE id BETWEEN 40 AND 42;
    ");
    echo "[OK] Permissions assigned to roles.\n";

    echo "Stage 9 Migrations completed successfully!\n";

} catch (Throwable $e) {
    echo "MIGRATION ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
