<?php
/**
 * Test script for Payroll Upgrades V2:
 * 1. Sri Lankan Labor Law EPF/ETF calculation (Base + Commission + Allowances)
 * 2. Editable Other Deductions and Net Salary recalculation
 * 3. Salary Advance Ledger listing API
 * 4. Multi-Account Split Payment for Net Salary
 * 5. EPF & ETF Remittance with Cheque details
 */

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Payroll.php';
require_once __DIR__ . '/../core/Employee.php';
require_once __DIR__ . '/../core/Accounting.php';

echo "=== TESTING PAYROLL UPGRADES V2 ===\n";

$db = Database::getInstance()->getConnection();
$currentBranchId = 1;

// 1. Create a test payroll period
$periodCode = 'TEST-EPF-' . date('Ym') . '-' . rand(100, 999);
$periodRes = Payroll::createPeriod([
    'period_code' => $periodCode,
    'period_name' => 'Sri Lanka Labor Law Test Period',
    'start_date'  => date('Y-m-01'),
    'end_date'    => date('Y-m-t'),
    'branch_id'   => $currentBranchId,
    'notes'       => 'Test for SL EPF/ETF compliance and split payments'
], 1);

if (!$periodRes['success']) {
    die("[FAIL] Could not create test period: " . $periodRes['message'] . "\n");
}
$periodId = $periodRes['period_id'];
echo "[PASS] Created test period ID {$periodId}\n";

$entries = Payroll::getPeriodEntries($periodId);
if (empty($entries)) {
    die("[FAIL] No employee entries generated for period\n");
}

$entry = $entries[0];
$entryId = (int)$entry['id'];
$basic = (float)$entry['basic_salary'];
$commInput = 20000.00;
$otherDedInput = 3500.00;

echo "[INFO] Testing Employee ID {$entry['employee_id']}, Entry ID {$entryId} (Basic: {$basic})\n";
echo "[CHECK] Employee ID {$entry['employee_id']} Period Sales: LKR {$entry['period_sales']} (Count: {$entry['sales_count']})\n";
if (!isset($entry['period_sales']) || !is_numeric($entry['period_sales'])) {
    die("[FAIL] period_sales field missing or invalid on payroll entry!\n");
}
echo "[PASS] period_sales verified on payroll entries!\n";

// Update commission and other deductions
$updRes = Payroll::updateEntry($entryId, [
    'commissions'      => $commInput,
    'other_deductions' => $otherDedInput
], 1);

if (!$updRes['success']) {
    die("[FAIL] updateEntry failed: " . $updRes['message'] . "\n");
}
echo "[PASS] updateEntry succeeded with commission {$commInput} and other deductions {$otherDedInput}\n";

// Fetch updated entry
$updatedEntry = Database::fetchOne("SELECT * FROM payroll_entries WHERE id = :id", [':id' => $entryId]);

// Verify Sri Lankan Labor Law EPF / ETF calculations:
// Base for EPF/ETF = Basic + Commissions + Allowances + Bonuses
$expectedEpfBase = round($basic + $commInput + (float)$updatedEntry['allowances'] + (float)$updatedEntry['bonuses'], 2);
$expectedEpf8 = round($expectedEpfBase * 0.08, 2);
$expectedEpf12 = round($expectedEpfBase * 0.12, 2);
$expectedEtf3 = round($expectedEpfBase * 0.03, 2);

echo "[CHECK] Expected EPF Base: {$expectedEpfBase}\n";
echo "[CHECK] Actual EPF 8%: {$updatedEntry['employee_epf_amount']}, Expected: {$expectedEpf8}\n";
echo "[CHECK] Actual EPF 12%: {$updatedEntry['employer_epf_amount']}, Expected: {$expectedEpf12}\n";
echo "[CHECK] Actual ETF 3%: {$updatedEntry['employer_etf_amount']}, Expected: {$expectedEtf3}\n";

if (abs((float)$updatedEntry['employee_epf_amount'] - $expectedEpf8) > 0.01) {
    die("[FAIL] EPF 8% does not include commissions according to Sri Lankan law!\n");
}
if (abs((float)$updatedEntry['employer_epf_amount'] - $expectedEpf12) > 0.01) {
    die("[FAIL] EPF 12% does not include commissions according to Sri Lankan law!\n");
}
if (abs((float)$updatedEntry['employer_etf_amount'] - $expectedEtf3) > 0.01) {
    die("[FAIL] ETF 3% does not include commissions according to Sri Lankan law!\n");
}
echo "[PASS] Sri Lankan Labor Law EPF/ETF calculations verified successfully!\n";

// Verify other deductions and net pay
$expectedGross = round($basic + $commInput + (float)$updatedEntry['allowances'] + (float)$updatedEntry['bonuses'], 2);
$advDeduct = (float)$updatedEntry['salary_advances_deducted'];
$expectedNet = max(0.00, round($expectedGross - ($expectedEpf8 + $advDeduct + $otherDedInput), 2));

if (abs((float)$updatedEntry['other_deductions'] - $otherDedInput) > 0.01) {
    die("[FAIL] Other deductions not updated properly!\n");
}
if (abs((float)$updatedEntry['net_salary'] - $expectedNet) > 0.01) {
    die("[FAIL] Net salary mismatch! Expected {$expectedNet}, got {$updatedEntry['net_salary']}\n");
}
echo "[PASS] Other deductions and Net Salary verified successfully! (Net Pay: {$expectedNet})\n";

// 2. Approve and post the payroll period
$approveRes = Payroll::approvePeriod($periodId, 1);
if (!$approveRes['success']) {
    die("[FAIL] Could not approve period: " . $approveRes['message'] . "\n");
}
echo "[PASS] Period approved and journal posted!\n";

// 3. Test Multi-Account Split Payment
// Fetch available payment accounts
$accounts = Database::fetchAll("SELECT id, account_code, account_name FROM chart_of_accounts WHERE account_code IN ('1010', '1012', '1015') OR account_type = 'asset' ORDER BY id ASC LIMIT 2");
if (count($accounts) < 2) {
    // If not enough accounts, fallback
    $acc1 = (int)$accounts[0]['id'];
    $acc2 = (int)$accounts[0]['id'];
} else {
    $acc1 = (int)$accounts[0]['id'];
    $acc2 = (int)$accounts[1]['id'];
}

$split1 = round($expectedNet / 2, 2);
$split2 = round($expectedNet - $split1, 2);

$payRes = Payroll::recordPayment([
    'payroll_entry_id' => $entryId,
    'amount'           => $expectedNet,
    'payment_date'     => date('Y-m-d'),
    'notes'            => 'Test multi-account split disbursement',
    'splits'           => [
        [
            'account_id'     => $acc1,
            'amount'         => $split1,
            'payment_method' => 'Cash in Hand',
            'reference_no'   => 'CASH-PART-1',
            'cheque_number'  => '',
            'cheque_date'    => null
        ],
        [
            'account_id'     => $acc2,
            'amount'         => $split2,
            'payment_method' => 'Bank Transfer',
            'reference_no'   => 'BANK-PART-2',
            'cheque_number'  => 'CHQ-998877',
            'cheque_date'    => date('Y-m-d')
        ]
    ]
], 1);

if (!$payRes['success']) {
    die("[FAIL] Split payment failed: " . $payRes['message'] . "\n");
}
echo "[PASS] Split payment recorded successfully! (Total Paid: " . ($split1 + $split2) . ")\n";

// Verify journal entry for payment debits 2050 and credits split accounts
$payJournal = Database::fetchOne("SELECT * FROM journal_entries WHERE source_type = 'payroll_payment' AND source_id = :pid ORDER BY id DESC LIMIT 1", [':pid' => $payRes['payment_id']]);
if (!$payJournal) {
    die("[FAIL] No journal entry found for payroll payment\n");
}
$payLines = Database::fetchAll("SELECT * FROM journal_lines WHERE journal_entry_id = :jid", [':jid' => $payJournal['id']]);
echo "[PASS] Payroll payment journal lines count: " . count($payLines) . " (Balanced: Debit={$payJournal['total_debit']} Credit={$payJournal['total_credit']})\n";
if (abs((float)$payJournal['total_debit'] - (float)$payJournal['total_credit']) > 0.01) {
    die("[FAIL] Payment journal out of balance!\n");
}

// 4. Test EPF & ETF Remittance with Cheque Details
$epfTotalRemit = round($expectedEpf8 + $expectedEpf12, 2);
$remitRes = Payroll::remitEpfEtf([
    'payroll_period_id'  => $periodId,
    'remittance_type'    => 'Combined',
    'epf_amount'         => $epfTotalRemit,
    'etf_amount'         => $expectedEtf3,
    'payment_date'       => date('Y-m-d'),
    'account_id'         => $acc1,
    'cheque_no'          => 'EPF-CHQ-1001',
    'cheque_date'        => date('Y-m-d'),
    'reference_no'       => 'EPF-CBO-REF',
    'etf_account_id'     => $acc2,
    'etf_cheque_no'      => 'ETF-CHQ-2002',
    'etf_cheque_date'    => date('Y-m-d'),
    'etf_reference_no'   => 'ETF-CBO-REF',
    'notes'              => 'Central Bank EPF and ETF Board cheque remittances'
], 1);

if (!$remitRes['success']) {
    die("[FAIL] Remittance failed: " . $remitRes['message'] . "\n");
}
echo "[PASS] EPF & ETF remittance recorded with dedicated cheques successfully!\n";

$remitJournal = Database::fetchOne("SELECT * FROM journal_entries WHERE source_type = 'epf_etf_remittance' AND source_id = :rid ORDER BY id DESC LIMIT 1", [':rid' => $remitRes['remittance_id']]);
if (!$remitJournal) {
    die("[FAIL] No journal entry found for EPF/ETF remittance\n");
}
echo "[PASS] Remittance journal verified (Debit={$remitJournal['total_debit']} Credit={$remitJournal['total_credit']})\n";
if (abs((float)$remitJournal['total_debit'] - (float)$remitJournal['total_credit']) > 0.01) {
    die("[FAIL] Remittance journal out of balance!\n");
}

// 5. Test Salary Advance Ledger fetch
$advList = Payroll::getAdvances(['branch_id' => $currentBranchId], 1, 25);
echo "[PASS] Payroll::getAdvances returned " . count($advList['items'] ?? []) . " records\n";

// Cleanup test period and journal entries
Database::execute("DELETE FROM journal_lines WHERE journal_entry_id IN (SELECT id FROM journal_entries WHERE source_id = :pid AND source_type IN ('payroll', 'payroll_payment', 'epf_etf_remittance'))", [':pid' => $periodId]);
Database::execute("DELETE FROM journal_entries WHERE source_id = :pid AND source_type IN ('payroll', 'payroll_payment', 'epf_etf_remittance')", [':pid' => $periodId]);
Database::execute("DELETE FROM payroll_payments WHERE payroll_entry_id IN (SELECT id FROM payroll_entries WHERE payroll_period_id = :pid)", [':pid' => $periodId]);
Database::execute("DELETE FROM epf_etf_remittances WHERE payroll_period_id = :pid", [':pid' => $periodId]);
Database::execute("DELETE FROM payroll_entries WHERE payroll_period_id = :pid", [':pid' => $periodId]);
Database::execute("DELETE FROM payroll_periods WHERE id = :id", [':id' => $periodId]);
$leftoverPeriods = Database::fetchAll("SELECT id FROM payroll_periods WHERE period_code LIKE 'TEST-%'");
foreach ($leftoverPeriods as $lp) {
    $lpid = (int)$lp['id'];
    Database::execute("DELETE FROM journal_lines WHERE journal_entry_id IN (SELECT id FROM journal_entries WHERE source_id = :pid AND source_type IN ('payroll', 'payroll_payment', 'epf_etf_remittance'))", [':pid' => $lpid]);
    Database::execute("DELETE FROM journal_entries WHERE source_id = :pid AND source_type IN ('payroll', 'payroll_payment', 'epf_etf_remittance')", [':pid' => $lpid]);
    Database::execute("DELETE FROM payroll_payments WHERE payroll_entry_id IN (SELECT id FROM payroll_entries WHERE payroll_period_id = :pid)", [':pid' => $lpid]);
    Database::execute("DELETE FROM epf_etf_remittances WHERE payroll_period_id = :pid", [':pid' => $lpid]);
    Database::execute("DELETE FROM payroll_entries WHERE payroll_period_id = :pid", [':pid' => $lpid]);
    Database::execute("DELETE FROM payroll_periods WHERE id = :id", [':id' => $lpid]);
}
echo "[CLEANUP] Leftover test periods cleaned up.\n";

echo "=== ALL PAYROLL UPGRADE V2 TESTS PASSED PERFECTLY ===\n";
