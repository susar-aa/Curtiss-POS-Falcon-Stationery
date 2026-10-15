<?php
/**
 * CLI Test Script for Period-Level Payroll Salary Payment
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Payroll.php';
require_once __DIR__ . '/../core/Accounting.php';

$_SESSION['user_id'] = 1;
$_SESSION['role_code'] = ROLE_SUPER_ADMIN;
$_SESSION['user_name'] = 'Test Superadmin';
$_SESSION['branch_id'] = 1;
$_SESSION['permissions'] = ['payroll.view', 'payroll.pay', 'payroll.create', 'payroll.approve'];

echo "=== TESTING PERIOD-LEVEL SALARY PAYMENT DISBURSEMENT ===\n";

// 1. Create a fresh test period
$res = Payroll::createPeriod([
    'branch_id'   => 1,
    'period_code' => 'TEST-PAY-' . time(),
    'period_name' => 'CLI Period Payment Test',
    'start_date'  => date('Y-m-01'),
    'end_date'    => date('Y-m-t')
], 1);

if (empty($res['success'])) {
    die("[FAIL] Failed to create period: " . ($res['message'] ?? '') . "\n");
}
$periodId = (int)$res['period_id'];
echo "[PASS] Created test payroll period ID {$periodId}\n";

// 2. Approve the period
$appRes = Payroll::approvePeriod($periodId, 1);
if (empty($appRes['success'])) {
    die("[FAIL] Failed to approve period: " . ($appRes['message'] ?? '') . "\n");
}
echo "[PASS] Approved period ID {$periodId}\n";

$period = Database::fetchOne("SELECT * FROM payroll_periods WHERE id = :id", [':id' => $periodId]);
$totalNet = (float)$period['total_net_salary'];
echo "[INFO] Total Net Salaries for period: LKR {$totalNet}\n";

// 3. Disburse full period payment using 2 splits
$half1 = round($totalNet / 2, 2);
$half2 = round($totalNet - $half1, 2);

$bankAcc = Database::fetchOne("SELECT id, account_id FROM bank_accounts WHERE status = 'active' LIMIT 1");
$bankId = $bankAcc ? (int)$bankAcc['id'] : null;

$payPayload = [
    'payroll_period_id' => $periodId,
    'amount'            => $totalNet,
    'payment_date'      => date('Y-m-d'),
    'notes'             => 'Full Period Salary Disbursement Test',
    'splits'            => json_encode([
        [
            'amount'          => $half1,
            'payment_method'  => 'Cash in Hand',
            'account_id'      => null
        ],
        [
            'amount'          => $half2,
            'payment_method'  => 'Bank Transfer',
            'bank_account_id' => $bankId
        ]
    ])
];

$payRes = Payroll::recordPayment($payPayload, 1);
if (empty($payRes['success'])) {
    die("[FAIL] Failed to record period payment: " . ($payRes['message'] ?? '') . "\n");
}
echo "[PASS] recordPayment disbursed period total successfully: " . $payRes['message'] . "\n";

// 4. Verify entries are all marked 'paid'
$entries = Database::fetchAll("SELECT * FROM payroll_entries WHERE payroll_period_id = :pid", [':pid' => $periodId]);
$allPaid = true;
foreach ($entries as $e) {
    if ($e['status'] !== 'paid' || (float)$e['paid_amount'] < (float)$e['net_salary']) {
        $allPaid = false;
        echo "[FAIL] Entry ID {$e['id']} not fully paid (Net: {$e['net_salary']}, Paid: {$e['paid_amount']}, Status: {$e['status']})\n";
    }
}
if ($allPaid) {
    echo "[PASS] All " . count($entries) . " employee entries in period marked status='paid' with full paid_amount!\n";
}

// 5. Verify period status
$updatedPeriod = Database::fetchOne("SELECT * FROM payroll_periods WHERE id = :id", [':id' => $periodId]);
if ((float)$updatedPeriod['total_paid'] === $totalNet && $updatedPeriod['status'] === 'completed') {
    echo "[PASS] Period updated to status='completed' with total_paid == total_net_salary!\n";
} else {
    echo "[FAIL] Period totals or status mismatch (Paid: {$updatedPeriod['total_paid']}, Status: {$updatedPeriod['status']})\n";
}

// Clean up test period
// Clean up test records
$cleanPid = (int)$periodId;
Database::execute("DELETE FROM journal_lines WHERE journal_entry_id IN (SELECT id FROM journal_entries WHERE description LIKE '%CLI Period Payment Test%')");
Database::execute("DELETE FROM journal_entries WHERE description LIKE '%CLI Period Payment Test%'");
Database::execute("DELETE FROM payroll_payments WHERE payroll_entry_id IN (SELECT id FROM payroll_entries WHERE payroll_period_id = {$cleanPid})");
Database::execute("DELETE FROM payroll_entries WHERE payroll_period_id = {$cleanPid}");
Database::execute("DELETE FROM payroll_periods WHERE id = {$cleanPid}");
echo "[CLEANUP] Deleted test period records, payments, and journals.\n";

echo "=== PERIOD PAYMENT VERIFICATION COMPLETED SUCCESSFULLY ===\n";
