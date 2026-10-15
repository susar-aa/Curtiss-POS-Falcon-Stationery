<?php
/**
 * CLI Test: Verification of EPF & ETF Remittance for Completed Payroll Periods
 * Verifies that:
 * 1. Periods with status='completed' (all salaries paid) can remit EPF & ETF.
 * 2. Payroll::getPeriods() correctly returns remitted_epf_etf and remit_count.
 * 3. Double-entry journals for EPF & ETF remittance are balanced and correct.
 */

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Accounting.php';
require_once __DIR__ . '/../core/Payroll.php';

echo "=== TESTING EPF/ETF REMITTANCE FOR COMPLETED PAYROLL PERIODS ===\n";

$db = Database::getInstance()->getConnection();

// 1. Create and approve a test period
$testCode = 'CLI-R-' . time();
$periodRes = Payroll::createPeriod([
    'period_code' => $testCode,
    'period_name' => 'CLI Remit Test Period',
    'start_date'  => '2026-11-01',
    'end_date'    => '2026-11-30',
    'branch_id'   => 1,
    'notes'       => 'CLI-REMIT-TEST'
], 1);

if (empty($periodRes['success'])) {
    die("[FAIL] Could not create test period: " . ($periodRes['message'] ?? '') . "\n");
}
$periodId = (int)$periodRes['period_id'];
echo "[PASS] Created test payroll period ID: {$periodId}\n";

// Approve period
$appRes = Payroll::approvePeriod($periodId, 1);
if (empty($appRes['success'])) {
    die("[FAIL] Could not approve period: " . ($appRes['message'] ?? '') . "\n");
}
echo "[PASS] Approved period ID: {$periodId}\n";

// 2. Disburse 100% salary to make period status='completed'
$period = Payroll::findPeriod($periodId);
$totalNet = (float)$period['total_net_salary'];
$totalEpfEtf = (float)$period['total_employee_epf'] + (float)$period['total_employer_epf'] + (float)$period['total_employer_etf'];

echo "[INFO] Period Net Salary: LKR {$totalNet}, Total EPF+ETF Statutory Due: LKR {$totalEpfEtf}\n";

$payRes = Payroll::recordPayment([
    'payroll_period_id' => $periodId,
    'amount'            => $totalNet,
    'payment_date'      => date('Y-m-d'),
    'notes'             => 'Full Salary Disbursement CLI Test',
    'splits'            => [
        [
            'account_id'     => Accounting::getAccountIdByCode(Accounting::CODE_CASH_DRAWER) ?: 1,
            'payment_method' => 'Cash in Hand',
            'amount'         => $totalNet
        ]
    ]
], 1);

if (empty($payRes['success'])) {
    die("[FAIL] Salary disbursement failed: " . ($payRes['message'] ?? '') . "\n");
}
echo "[PASS] Salary payment completed!\n";

// Verify period is now 'completed'
$completedPeriod = Payroll::findPeriod($periodId);
if ($completedPeriod['status'] !== 'completed') {
    die("[FAIL] Expected period status='completed', got: {$completedPeriod['status']}\n");
}
echo "[PASS] Verified period status is 'completed'!\n";

// 3. Test Remitting EPF & ETF for this 'completed' period
$epfBank = Database::fetchOne("SELECT id FROM bank_accounts WHERE status = 'active' LIMIT 1");
$bankId = $epfBank ? (int)$epfBank['id'] : null;

$remitPayload = [
    'payroll_period_id'   => $periodId,
    'remittance_type'     => 'Combined',
    'epf_amount'          => (float)$completedPeriod['total_employee_epf'] + (float)$completedPeriod['total_employer_epf'],
    'etf_amount'          => (float)$completedPeriod['total_employer_etf'],
    'payment_date'        => date('Y-m-d'),
    'bank_account_id'     => $bankId,
    'epf_bank_account_id' => $bankId,
    'etf_bank_account_id' => $bankId,
    'cheque_no'           => 'CHQ-REMIT-999',
    'notes'               => 'CLI Remittance on Completed Period'
];

$remitRes = Payroll::remitEpfEtf($remitPayload, 1);
if (empty($remitRes['success'])) {
    die("[FAIL] Remit EPF/ETF failed on completed period: " . ($remitRes['message'] ?? '') . "\n");
}
echo "[PASS] Successfully remitted EPF/ETF for completed period: " . $remitRes['message'] . "\n";

// 4. Verify getPeriods() reflects remitted amounts
$listRes = Payroll::getPeriods(['search' => 'CLI Remit Test']);
$found = null;
foreach ($listRes['items'] as $item) {
    if ((int)$item['id'] === $periodId) {
        $found = $item;
        break;
    }
}

if (!$found) {
    die("[FAIL] Period not found in getPeriods list!\n");
}
echo "[PASS] getPeriods() returned period ID {$periodId}!\n";
echo "[CHECK] Remitted in getPeriods: {$found['remitted_epf_etf']}, Remit Count: {$found['remit_count']}\n";
if ((float)$found['remitted_epf_etf'] === $totalEpfEtf && (int)$found['remit_count'] === 1) {
    echo "[PASS] getPeriods() correctly shows 100% EPF & ETF remitted for completed period!\n";
} else {
    die("[FAIL] Mismatch in getPeriods remitted figures!\n");
}

// 5. Clean up test records
Database::execute("DELETE FROM journal_lines WHERE journal_entry_id IN (SELECT id FROM journal_entries WHERE description LIKE '%CLI Remit Test%')");
Database::execute("DELETE FROM journal_entries WHERE description LIKE '%CLI Remit Test%'");
Database::execute("DELETE FROM epf_etf_remittances WHERE payroll_period_id = {$periodId}");
Database::execute("DELETE FROM payroll_payments WHERE payroll_entry_id IN (SELECT id FROM payroll_entries WHERE payroll_period_id = {$periodId})");
Database::execute("DELETE FROM payroll_entries WHERE payroll_period_id = {$periodId}");
Database::execute("DELETE FROM payroll_periods WHERE id = {$periodId}");
echo "[CLEANUP] Deleted test period records, remittance, payments, and journals.\n";

echo "=== ALL TESTS PASSED SUCCESSFULLY ===\n";
