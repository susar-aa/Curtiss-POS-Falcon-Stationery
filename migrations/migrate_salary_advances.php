<?php
/**
 * Migration Script: Employee Salary Advance Enhancements
 */
require_once __DIR__ . '/../core/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "Running Salary Advances Migration...\n";

    // 1. Check & Alter employee_advances.payment_method
    $db->exec("ALTER TABLE employee_advances MODIFY COLUMN payment_method VARCHAR(50) NOT NULL DEFAULT 'Cash in Hand'");
    echo "✔ employee_advances.payment_method altered to VARCHAR(50)\n";

    // 2. Add columns if not exist in employee_advances
    $cols = Database::fetchAll("SHOW COLUMNS FROM employee_advances");
    $colNames = array_column($cols, 'Field');

    if (!in_array('source_account_id', $colNames)) {
        $db->exec("ALTER TABLE employee_advances ADD COLUMN source_account_id INT(10) UNSIGNED NULL AFTER bank_account_id");
        echo "✔ Added source_account_id to employee_advances\n";
    }

    if (!in_array('reference_no', $colNames)) {
        $db->exec("ALTER TABLE employee_advances ADD COLUMN reference_no VARCHAR(100) NULL AFTER source_account_id");
        echo "✔ Added reference_no to employee_advances\n";
    }

    if (!in_array('recovery_period_id', $colNames)) {
        $db->exec("ALTER TABLE employee_advances ADD COLUMN recovery_period_id INT(10) UNSIGNED NULL AFTER reference_no");
        echo "✔ Added recovery_period_id to employee_advances\n";
    }

    if (!in_array('cancelled_at', $colNames)) {
        $db->exec("ALTER TABLE employee_advances ADD COLUMN cancelled_at DATETIME NULL AFTER notes");
        echo "✔ Added cancelled_at to employee_advances\n";
    }

    if (!in_array('cancelled_by', $colNames)) {
        $db->exec("ALTER TABLE employee_advances ADD COLUMN cancelled_by INT(10) UNSIGNED NULL AFTER cancelled_at");
        echo "✔ Added cancelled_by to employee_advances\n";
    }

    if (!in_array('cancel_reason', $colNames)) {
        $db->exec("ALTER TABLE employee_advances ADD COLUMN cancel_reason TEXT NULL AFTER cancelled_by");
        echo "✔ Added cancel_reason to employee_advances\n";
    }

    // Add indexes if missing
    $indexes = Database::fetchAll("SHOW INDEX FROM employee_advances");
    $indexNames = array_column($indexes, 'Key_name');

    if (!in_array('idx_emp_adv_branch_status', $indexNames)) {
        $db->exec("ALTER TABLE employee_advances ADD INDEX idx_emp_adv_branch_status (branch_id, status)");
        echo "✔ Added index idx_emp_adv_branch_status\n";
    }

    if (!in_array('idx_emp_adv_emp_status', $indexNames)) {
        $db->exec("ALTER TABLE employee_advances ADD INDEX idx_emp_adv_emp_status (employee_id, status)");
        echo "✔ Added index idx_emp_adv_emp_status\n";
    }

    // 3. Alter cash_drawer_movements.movement_type to include employee_advance
    $db->exec("ALTER TABLE cash_drawer_movements MODIFY COLUMN movement_type ENUM('opening_cash','pos_sale','customer_collection','cash_in','cash_refund','supplier_payment','employee_advance','cash_out','shortage_adjustment','overage_adjustment') NOT NULL");
    echo "✔ cash_drawer_movements.movement_type updated with 'employee_advance'\n";

    // 4. Alter journal_entries.source_type to include employee_advance, payroll, payroll_payment, epf_etf_remittance
    $db->exec("ALTER TABLE journal_entries MODIFY COLUMN source_type ENUM('grn','supplier_payment','sale','customer_collection','sales_return','manual','reversal','opening_balance','customer_cheque_return','cheque_return','supplier_return','supplier_credit_settlement','cheque_clearing','cheque_bounce','cheque_cancellation','card_settlement','card_settlement_reversal','employee_advance','payroll','payroll_payment','epf_etf_remittance') NOT NULL DEFAULT 'manual'");
    echo "✔ journal_entries.source_type updated with 'employee_advance', 'payroll', 'payroll_payment', 'epf_etf_remittance'\n";

    echo "Migration completed successfully!\n";
} catch (Throwable $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
