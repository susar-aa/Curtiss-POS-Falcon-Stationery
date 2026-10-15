<?php
/**
 * Migration: Employee Directory, Sales Rep & Payroll System
 * Creates tables, adds COA accounts, permissions, and indexes.
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Database.php';

$db = Database::getInstance()->getConnection();

echo "Running Migration: Employee Directory, Sales Rep & Payroll...\n";

// 1. Employees Table
$db->exec("
CREATE TABLE IF NOT EXISTS `employees` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `employee_code` VARCHAR(30) NOT NULL UNIQUE,
    `full_name` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(30) NULL,
    `whatsapp` VARCHAR(30) NULL,
    `address` TEXT NULL,
    `nic` VARCHAR(30) NULL,
    `job_role` VARCHAR(50) NOT NULL DEFAULT 'Sales Rep',
    `is_sales_rep` TINYINT(1) NOT NULL DEFAULT 1,
    `branch_id` INT UNSIGNED NOT NULL,
    `joining_date` DATE NULL,
    `basic_salary` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `bank_name` VARCHAR(100) NULL,
    `bank_account_name` VARCHAR(150) NULL,
    `bank_account_no` VARCHAR(50) NULL,
    `notes` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME NULL DEFAULT NULL,
    INDEX `idx_emp_branch` (`branch_id`),
    INDEX `idx_emp_status` (`status`),
    INDEX `idx_emp_sales_rep` (`is_sales_rep`),
    INDEX `idx_emp_deleted` (`deleted_at`),
    CONSTRAINT `fk_emp_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "Created/verified employees table.\n";

// 2. Add sales_rep_id to sales table if not exists
$colCheck = Database::fetchOne("
    SELECT COLUMN_NAME 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sales' AND COLUMN_NAME = 'sales_rep_id'
");
if (!$colCheck) {
    $db->exec("
        ALTER TABLE `sales` 
        ADD COLUMN `sales_rep_id` INT UNSIGNED NULL AFTER `customer_id`,
        ADD INDEX `idx_sales_rep_id` (`sales_rep_id`),
        ADD CONSTRAINT `fk_sales_rep` FOREIGN KEY (`sales_rep_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL;
    ");
    echo "Added sales_rep_id to sales table.\n";
} else {
    echo "sales_rep_id already exists on sales table.\n";
}

// 3. Payroll Periods Table
$db->exec("
CREATE TABLE IF NOT EXISTS `payroll_periods` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `period_code` VARCHAR(20) NOT NULL, -- e.g. '2026-10'
    `period_name` VARCHAR(100) NOT NULL, -- e.g. 'October 2026'
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `branch_id` INT UNSIGNED NULL, -- NULL indicates all branches
    `status` ENUM('draft', 'approved', 'completed', 'cancelled') NOT NULL DEFAULT 'draft',
    `total_basic` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `total_allowances` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `total_commissions` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `total_gross` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `total_employee_epf` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `total_employer_epf` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `total_employer_etf` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `total_deductions` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `total_net_salary` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `total_paid` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `created_by` INT UNSIGNED NOT NULL,
    `approved_by` INT UNSIGNED NULL,
    `approved_at` DATETIME NULL,
    `notes` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_pp_period_code` (`period_code`),
    INDEX `idx_pp_status` (`status`),
    INDEX `idx_pp_branch` (`branch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "Created/verified payroll_periods table.\n";

// 4. Payroll Entries Table
$db->exec("
CREATE TABLE IF NOT EXISTS `payroll_entries` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `payroll_period_id` INT UNSIGNED NOT NULL,
    `employee_id` INT UNSIGNED NOT NULL,
    `branch_id` INT UNSIGNED NOT NULL,
    `basic_salary` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `allowances` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `bonuses` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `commissions` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `overtime_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `gross_salary` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `employee_epf_rate` DECIMAL(5,2) NOT NULL DEFAULT 8.00,
    `employee_epf_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `salary_advances_deducted` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `other_deductions` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `total_deductions` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `net_salary` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `employer_epf_rate` DECIMAL(5,2) NOT NULL DEFAULT 12.00,
    `employer_epf_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `employer_etf_rate` DECIMAL(5,2) NOT NULL DEFAULT 3.00,
    `employer_etf_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `paid_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `status` ENUM('draft', 'approved', 'partially_paid', 'paid', 'cancelled') NOT NULL DEFAULT 'draft',
    `notes` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_pp_emp` (`payroll_period_id`, `employee_id`),
    INDEX `idx_pe_emp` (`employee_id`),
    INDEX `idx_pe_branch` (`branch_id`),
    INDEX `idx_pe_status` (`status`),
    CONSTRAINT `fk_pe_period` FOREIGN KEY (`payroll_period_id`) REFERENCES `payroll_periods` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pe_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "Created/verified payroll_entries table.\n";

// 5. Payroll Payments Table
$db->exec("
CREATE TABLE IF NOT EXISTS `payroll_payments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `payment_no` VARCHAR(50) NOT NULL UNIQUE,
    `payroll_entry_id` INT UNSIGNED NOT NULL,
    `employee_id` INT UNSIGNED NOT NULL,
    `branch_id` INT UNSIGNED NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `payment_method` ENUM('Cash', 'Bank') NOT NULL DEFAULT 'Cash',
    `payment_date` DATE NOT NULL,
    `bank_account_id` INT UNSIGNED NULL,
    `cheque_number` VARCHAR(50) NULL,
    `reference_no` VARCHAR(100) NULL,
    `notes` TEXT NULL,
    `created_by` INT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_pay_pe` (`payroll_entry_id`),
    INDEX `idx_pay_emp` (`employee_id`),
    INDEX `idx_pay_method` (`payment_method`),
    CONSTRAINT `fk_pay_pe` FOREIGN KEY (`payroll_entry_id`) REFERENCES `payroll_entries` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_pay_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "Created/verified payroll_payments table.\n";

// 6. Employee Salary Advances Table
$db->exec("
CREATE TABLE IF NOT EXISTS `employee_advances` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `advance_no` VARCHAR(50) NOT NULL UNIQUE,
    `employee_id` INT UNSIGNED NOT NULL,
    `branch_id` INT UNSIGNED NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `recovered_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `remaining_amount` DECIMAL(12,2) NOT NULL,
    `disbursement_date` DATE NOT NULL,
    `payment_method` ENUM('Cash', 'Bank') NOT NULL DEFAULT 'Cash',
    `bank_account_id` INT UNSIGNED NULL,
    `status` ENUM('active', 'fully_recovered', 'cancelled') NOT NULL DEFAULT 'active',
    `notes` TEXT NULL,
    `created_by` INT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_adv_emp` (`employee_id`),
    INDEX `idx_adv_branch` (`branch_id`),
    INDEX `idx_adv_status` (`status`),
    CONSTRAINT `fk_adv_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "Created/verified employee_advances table.\n";

// 7. EPF / ETF Remittances Table
$db->exec("
CREATE TABLE IF NOT EXISTS `epf_etf_remittances` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `remittance_no` VARCHAR(50) NOT NULL UNIQUE,
    `payroll_period_id` INT UNSIGNED NOT NULL,
    `branch_id` INT UNSIGNED NOT NULL,
    `remittance_type` ENUM('EPF', 'ETF', 'Combined') NOT NULL DEFAULT 'Combined',
    `total_employee_epf` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `total_employer_epf` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `total_employer_etf` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `total_amount` DECIMAL(12,2) NOT NULL,
    `payment_date` DATE NOT NULL,
    `bank_account_id` INT UNSIGNED NULL,
    `reference_no` VARCHAR(100) NULL,
    `cheque_no` VARCHAR(50) NULL,
    `notes` TEXT NULL,
    `created_by` INT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_rem_period` (`payroll_period_id`),
    INDEX `idx_rem_type` (`remittance_type`),
    CONSTRAINT `fk_rem_period` FOREIGN KEY (`payroll_period_id`) REFERENCES `payroll_periods` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "Created/verified epf_etf_remittances table.\n";

// 8. Add Required Chart of Accounts for Payroll
$requiredCoas = [
    [
        'code' => '1048',
        'name' => 'Employee Salary Advances',
        'type' => 'asset',
        'desc' => 'Short term salary advances and employee receivables'
    ],
    [
        'code' => '2050',
        'name' => 'Salaries & Wages Payable',
        'type' => 'liability',
        'desc' => 'Accrued net salary obligations payable to employees'
    ],
    [
        'code' => '2055',
        'name' => 'EPF Payable',
        'type' => 'liability',
        'desc' => 'Employee 8% and Employer 12% EPF liabilities payable to Central Bank'
    ],
    [
        'code' => '2060',
        'name' => 'ETF Payable',
        'type' => 'liability',
        'desc' => 'Employer 3% ETF liability payable to ETF Board'
    ],
    [
        'code' => '5200',
        'name' => 'Salaries & Wages Expense',
        'type' => 'expense',
        'desc' => 'Monthly basic salary and wages cost'
    ],
    [
        'code' => '5210',
        'name' => 'Employer EPF & ETF Contribution Expense',
        'type' => 'expense',
        'desc' => 'Statutory employer contributions (EPF 12% + ETF 3%)'
    ],
    [
        'code' => '5220',
        'name' => 'Sales Commissions & Employee Allowances Expense',
        'type' => 'expense',
        'desc' => 'Sales representative commissions, overtime, and allowances'
    ]
];

foreach ($requiredCoas as $rc) {
    $existing = Database::fetchOne("SELECT id FROM chart_of_accounts WHERE account_code = :c", [':c' => $rc['code']]);
    if (!$existing) {
        Database::execute("
            INSERT INTO chart_of_accounts (account_code, account_name, account_type, is_system, status)
            VALUES (:code, :name, :type, 1, 'active')
        ", [
            ':code' => $rc['code'],
            ':name' => $rc['name'],
            ':type' => $rc['type']
        ]);
        echo "Added COA Account: {$rc['code']} - {$rc['name']}\n";
    } else {
        echo "COA Account {$rc['code']} already exists.\n";
    }
}

// 9. Add Required Permissions
$permissions = [
    ['code' => 'employees.view',   'module' => 'employees', 'desc' => 'View Employees Directory'],
    ['code' => 'employees.manage', 'module' => 'employees', 'desc' => 'Create & Edit Employees'],
    ['code' => 'payroll.view',     'module' => 'payroll',   'desc' => 'View Payroll Ledgers & Periods'],
    ['code' => 'payroll.create',   'module' => 'payroll',   'desc' => 'Create & Draft Payroll Periods'],
    ['code' => 'payroll.approve',  'module' => 'payroll',   'desc' => 'Approve & Post Payroll Journals'],
    ['code' => 'payroll.pay',      'module' => 'payroll',   'desc' => 'Disburse Salaries & Advances'],
    ['code' => 'payroll.remit',    'module' => 'payroll',   'desc' => 'Remit Statutory EPF & ETF']
];

foreach ($permissions as $p) {
    $existing = Database::fetchOne("SELECT id FROM permissions WHERE permission_code = :c", [':c' => $p['code']]);
    $pId = null;
    if (!$existing) {
        Database::execute("
            INSERT INTO permissions (permission_code, module, description, created_at)
            VALUES (:code, :module, :desc, NOW())
        ", [
            ':code'   => $p['code'],
            ':module' => $p['module'],
            ':desc'   => $p['desc']
        ]);
        $pId = (int)Database::lastInsertId();
        echo "Created permission: {$p['code']}\n";
    } else {
        $pId = (int)$existing['id'];
    }

    // Assign to Super Admin (1), Admin (2), Manager (3), Accountant (4)
    $targetRoles = [1, 2, 3, 4];
    foreach ($targetRoles as $rid) {
        $hasRolePerm = Database::fetchOne("SELECT 1 FROM role_permissions WHERE role_id = :r AND permission_id = :p", [':r' => $rid, ':p' => $pId]);
        if (!$hasRolePerm) {
            Database::execute("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (:r, :p)", [':r' => $rid, ':p' => $pId]);
        }
    }
}

// 10. Extend journal_entries source_type ENUM for payroll sources
$db->exec("
    ALTER TABLE `journal_entries` MODIFY COLUMN `source_type` ENUM(
        'grn','supplier_payment','sale','customer_collection','sales_return','manual','reversal',
        'opening_balance','customer_cheque_return','cheque_return','supplier_return',
        'supplier_credit_settlement','cheque_clearing','cheque_bounce','cheque_cancellation',
        'card_settlement','card_settlement_reversal',
        'employee_advance','payroll','payroll_period','payroll_payment','epf_etf_remittance'
    ) NOT NULL;
");
echo "Updated journal_entries source_type ENUM.\n";

echo "Permissions verified and assigned to administrative roles.\n";
echo "Migration successfully completed!\n";
