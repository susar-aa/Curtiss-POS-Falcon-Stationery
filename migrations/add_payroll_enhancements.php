<?php
/**
 * Migration: Add Performance Indexes for Payroll Management
 * Workspace: CURTISS POS
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

echo "Running Payroll Indexes Migration...\n";

$indexes = [
    [
        'table' => 'payroll_periods',
        'name'  => 'idx_pp_br_stat_date',
        'cols'  => '(`branch_id`, `status`, `start_date`)'
    ],
    [
        'table' => 'payroll_entries',
        'name'  => 'idx_pe_period_status',
        'cols'  => '(`payroll_period_id`, `status`)'
    ],
    [
        'table' => 'employee_advances',
        'name'  => 'idx_emp_adv_br_stat_date',
        'cols'  => '(`branch_id`, `status`, `disbursement_date`)'
    ],
    [
        'table' => 'epf_etf_remittances',
        'name'  => 'idx_rem_br_date',
        'cols'  => '(`branch_id`, `payment_date`)'
    ],
    [
        'table' => 'payroll_payments',
        'name'  => 'idx_pay_br_date',
        'cols'  => '(`branch_id`, `payment_date`)'
    ]
];

foreach ($indexes as $idx) {
    $table = $idx['table'];
    $name  = $idx['name'];
    $cols  = $idx['cols'];

    try {
        $existing = Database::fetchAll("SHOW INDEX FROM `{$table}` WHERE Key_name = :k", [':k' => $name]);
        if (!empty($existing)) {
            echo "Index {$name} on {$table} already exists. Skipping.\n";
            continue;
        }

        Database::execute("ALTER TABLE `{$table}` ADD INDEX `{$name}` {$cols}");
        echo "Created index {$name} on {$table}.\n";
    } catch (Throwable $e) {
        echo "Error on {$table}.{$name}: " . $e->getMessage() . "\n";
    }
}

echo "Migration finished.\n";
