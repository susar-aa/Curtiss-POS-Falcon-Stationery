<?php
/**
 * Migration: Add Centralized Expense Management System
 * 
 * - Creates 'expenses' table for centralized business expense tracking
 * - Creates 'expense_payments' table for tracking payments against unpaid/partially paid bills
 * - Creates 'expense_attachments' table for receipt/invoice file attachments
 * - Adds composite indexes for query performance and universal search
 * - Verifies permissions and COA Account 2030 (Accrued Expenses & Operating Payables)
 */

require_once __DIR__ . '/../config/config.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "========================================================\n";
    echo "Starting Expense Management System Database Migration...\n";
    echo "========================================================\n";

    // 1. Verify / Create COA Account 2030: Accrued Expenses & Operating Payables
    $accCheck = $db->query("SELECT id FROM chart_of_accounts WHERE account_code = '2030'")->fetch(PDO::FETCH_ASSOC);
    if (!$accCheck) {
        echo "Creating COA Account 2030 (Accrued Expenses & Operating Payables)..." . PHP_EOL;
        $db->exec("
            INSERT INTO chart_of_accounts (account_code, account_name, account_type, description, is_system, status)
            VALUES ('2030', 'Accrued Expenses & Operating Payables', 'liability', 'System liability account for unpaid and accrued operating expenses', 1, 'active')
        ");
    } else {
        echo "COA Account 2030 already exists." . PHP_EOL;
    }

    // 2. Create 'expenses' table
    $tblExpensesCheck = $db->query("SHOW TABLES LIKE 'expenses'")->fetch(PDO::FETCH_ASSOC);
    if (!$tblExpensesCheck) {
        echo "Creating 'expenses' table..." . PHP_EOL;
        $db->exec("
            CREATE TABLE `expenses` (
                `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                `expense_no` VARCHAR(50) NOT NULL,
                `branch_id` INT(10) UNSIGNED NOT NULL,
                `expense_date` DATE NOT NULL,
                `category_id` INT(10) UNSIGNED NOT NULL,
                `account_id` INT(10) UNSIGNED NOT NULL,
                `amount` DECIMAL(12,2) NOT NULL,
                `paid_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                `payment_status` ENUM('paid', 'unpaid', 'partially_paid') NOT NULL DEFAULT 'paid',
                `payment_method` VARCHAR(50) NOT NULL DEFAULT 'cash_drawer',
                `payment_account_id` INT(10) UNSIGNED NULL DEFAULT NULL,
                `bank_account_id` INT(10) UNSIGNED NULL DEFAULT NULL,
                `payee` VARCHAR(150) NOT NULL,
                `description` TEXT NOT NULL,
                `reference_no` VARCHAR(100) NULL DEFAULT NULL,
                `payment_reference` VARCHAR(100) NULL DEFAULT NULL,
                `notes` TEXT NULL DEFAULT NULL,
                `due_date` DATE NULL DEFAULT NULL,
                `business_day_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
                `status` ENUM('draft', 'posted', 'reversed') NOT NULL DEFAULT 'posted',
                `journal_entry_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
                `reversal_journal_entry_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
                `reversed_at` DATETIME NULL DEFAULT NULL,
                `reversed_by` INT(10) UNSIGNED NULL DEFAULT NULL,
                `reversal_reason` TEXT NULL DEFAULT NULL,
                `created_by` INT(10) UNSIGNED NOT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                `deleted_at` DATETIME NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq_expense_no` (`expense_no`),
                KEY `idx_exp_branch_date_status` (`branch_id`, `expense_date`, `status`),
                KEY `idx_exp_status_date` (`status`, `expense_date`),
                KEY `idx_exp_account_id` (`account_id`),
                KEY `idx_exp_payment_account` (`payment_account_id`),
                KEY `idx_exp_category_id` (`category_id`),
                KEY `idx_exp_bank_account` (`bank_account_id`),
                KEY `idx_exp_business_day` (`business_day_id`),
                KEY `idx_exp_del_status` (`deleted_at`, `status`),
                KEY `idx_exp_payee` (`payee`),
                KEY `idx_exp_journal_entry` (`journal_entry_id`),
                KEY `idx_exp_created_by` (`created_by`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        echo "Table 'expenses' created successfully." . PHP_EOL;
    } else {
        echo "Table 'expenses' already exists." . PHP_EOL;
    }

    // 3. Create 'expense_payments' table
    $tblExpPayCheck = $db->query("SHOW TABLES LIKE 'expense_payments'")->fetch(PDO::FETCH_ASSOC);
    if (!$tblExpPayCheck) {
        echo "Creating 'expense_payments' table..." . PHP_EOL;
        $db->exec("
            CREATE TABLE `expense_payments` (
                `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                `expense_id` BIGINT(20) UNSIGNED NOT NULL,
                `payment_no` VARCHAR(50) NOT NULL,
                `payment_date` DATE NOT NULL,
                `amount` DECIMAL(12,2) NOT NULL,
                `payment_method` VARCHAR(50) NOT NULL,
                `payment_account_id` INT(10) UNSIGNED NOT NULL,
                `bank_account_id` INT(10) UNSIGNED NULL DEFAULT NULL,
                `business_day_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
                `reference_no` VARCHAR(100) NULL DEFAULT NULL,
                `notes` TEXT NULL DEFAULT NULL,
                `status` ENUM('posted', 'reversed') NOT NULL DEFAULT 'posted',
                `journal_entry_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
                `reversal_journal_entry_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
                `reversed_at` DATETIME NULL DEFAULT NULL,
                `reversed_by` INT(10) UNSIGNED NULL DEFAULT NULL,
                `reversal_reason` TEXT NULL DEFAULT NULL,
                `created_by` INT(10) UNSIGNED NOT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq_exp_payment_no` (`payment_no`),
                KEY `idx_exp_pay_expense` (`expense_id`),
                KEY `idx_exp_pay_date_status` (`payment_date`, `status`),
                KEY `idx_exp_pay_account` (`payment_account_id`),
                KEY `idx_exp_pay_bday` (`business_day_id`),
                KEY `idx_exp_pay_journal` (`journal_entry_id`),
                CONSTRAINT `fk_exp_payments_expense` FOREIGN KEY (`expense_id`) REFERENCES `expenses` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        echo "Table 'expense_payments' created successfully." . PHP_EOL;
    } else {
        echo "Table 'expense_payments' already exists." . PHP_EOL;
    }

    // 4. Create 'expense_attachments' table
    $tblAttCheck = $db->query("SHOW TABLES LIKE 'expense_attachments'")->fetch(PDO::FETCH_ASSOC);
    if (!$tblAttCheck) {
        echo "Creating 'expense_attachments' table..." . PHP_EOL;
        $db->exec("
            CREATE TABLE `expense_attachments` (
                `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                `expense_id` BIGINT(20) UNSIGNED NOT NULL,
                `file_name` VARCHAR(255) NOT NULL,
                `file_path` VARCHAR(500) NOT NULL,
                `file_type` VARCHAR(100) NOT NULL,
                `file_size` INT(10) UNSIGNED NOT NULL,
                `uploaded_by` INT(10) UNSIGNED NOT NULL,
                `uploaded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_exp_att_expense` (`expense_id`),
                CONSTRAINT `fk_exp_attachments_expense` FOREIGN KEY (`expense_id`) REFERENCES `expenses` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        echo "Table 'expense_attachments' created successfully." . PHP_EOL;
    } else {
        echo "Table 'expense_attachments' already exists." . PHP_EOL;
    }

    // 5. Ensure all Expense Permissions are registered and assigned to Admin/Super Admin
    $expectedPerms = [
        ['expenses.view', 'expenses', 'View expense management module and listings'],
        ['expenses.create', 'expenses', 'Create new operating expenses and drafts'],
        ['expenses.edit_draft', 'expenses', 'Edit unposted draft expense vouchers'],
        ['expenses.post', 'expenses', 'Post and approve draft expenses to general ledger'],
        ['expenses.reverse', 'expenses', 'Void and reverse posted expense transactions'],
        ['expenses.export', 'expenses', 'Export expense records and registers'],
        ['expenses.view_reports', 'expenses', 'Access dedicated expense reports and analytics'],
        ['expenses.manage_categories', 'expenses', 'Configure expense categories and COA mappings']
    ];

    foreach ($expectedPerms as $ep) {
        $chkP = $db->prepare("SELECT id FROM permissions WHERE permission_code = ?");
        $chkP->execute([$ep[0]]);
        $permId = $chkP->fetchColumn();
        if (!$permId) {
            $insP = $db->prepare("INSERT INTO permissions (permission_code, module, description) VALUES (?, ?, ?)");
            $insP->execute([$ep[0], $ep[1], $ep[2]]);
            $permId = $db->lastInsertId();
            echo "Added permission: {$ep[0]}" . PHP_EOL;
        }

        // Assign to Super Admin (role_id = 1) and Admin (role_id = 2) if not already assigned
        $rolesToAssign = [1, 2];
        foreach ($rolesToAssign as $rId) {
            $chkRP = $db->prepare("SELECT COUNT(*) FROM role_permissions WHERE role_id = ? AND permission_id = ?");
            $chkRP->execute([$rId, $permId]);
            if ((int)$chkRP->fetchColumn() === 0) {
                $insRP = $db->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
                $insRP->execute([$rId, $permId]);
                echo "Assigned {$ep[0]} to Role ID {$rId}" . PHP_EOL;
            }
        }
    }

    echo "========================================================\n";
    echo "Expense Management Migration COMPLETED SUCCESSFULLY.\n";
    echo "========================================================\n";

} catch (Exception $e) {
    echo "MIGRATION FAILED: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
