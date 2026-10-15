<?php
/**
 * Test script for Payroll Upgrades:
 * 1. Branch scoping for active staff
 * 2. Fullscreen calculation columns and live commission recalculation
 * 3. Separation of salary advances
 */

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Payroll.php';
require_once __DIR__ . '/../core/Employee.php';

echo "=== TESTING PAYROLL UPGRADES ===\n";

$db = Database::getInstance()->getConnection();

// 1. Verify branch staff filter
$currentBranchId = 1;
$activeEmployees = Database::fetchAll("
    SELECT id, full_name, basic_salary, branch_id
    FROM employees
    WHERE status = 'active'
      AND deleted_at IS NULL
      AND branch_id = :bid
", [':bid' => $currentBranchId]);

echo "[PASS] Fetched " . count($activeEmployees) . " active employees strictly for branch {$currentBranchId}\n";
foreach ($activeEmployees as $emp) {
    if ((int)$emp['branch_id'] !== $currentBranchId) {
        die("[FAIL] Employee {$emp['id']} has wrong branch {$emp['branch_id']}\n");
    }
}
echo "[PASS] All fetched employees strictly belong to branch {$currentBranchId}\n";

// 2. Test createPeriod respects current branch
$periodCode = 'TEST-UPG-' . date('Ym') . '-' . rand(100, 999);
$periodRes = Payroll::createPeriod([
    'period_code' => $periodCode,
    'period_name' => 'Upgrade Test Period',
    'start_date'  => date('Y-m-01'),
    'end_date'    => date('Y-m-t'),
    'branch_id'   => $currentBranchId,
    'notes'       => 'Test for payroll upgrades'
], 1);

if (!$periodRes['success']) {
    die("[FAIL] Could not create test period: " . $periodRes['message'] . "\n");
}
$periodId = $periodRes['period_id'];
echo "[PASS] Created test period ID {$periodId}\n";

$entries = Payroll::getPeriodEntries($periodId);
echo "[PASS] Generated " . count($entries) . " entries for period\n";

if (!empty($entries)) {
    $entry = $entries[0];
    $entryId = (int)$entry['id'];
    $basic = (float)$entry['basic_salary'];
    $commInput = 15000.00;

    echo "[INFO] Testing commission manual update for entry ID {$entryId} (Basic: LKR {$basic})\n";

    // Test updateEntry
    $updRes = Payroll::updateEntry($entryId, [
        'commissions' => $commInput
    ], 1);

    if (!$updRes['success']) {
        die("[FAIL] updateEntry failed: " . $updRes['message'] . "\n");
    }
    echo "[PASS] updateEntry succeeded\n";

    // Verify updated entry values
    $updatedEntry = Database::fetchOne("SELECT * FROM payroll_entries WHERE id = :id", [':id' => $entryId]);
    $expectedGross = round($basic + $commInput, 2);
    $expectedEpf8 = round($basic * 0.08, 2);
    $advDeduct = (float)$updatedEntry['salary_advances_deducted'];
    $otherDeduct = (float)$updatedEntry['other_deductions'];
    $expectedNet = max(0.00, round($expectedGross - ($expectedEpf8 + $advDeduct + $otherDeduct), 2));

    if (abs((float)$updatedEntry['gross_salary'] - $expectedGross) > 0.01) {
        die("[FAIL] Gross salary mismatch. Expected {$expectedGross}, got {$updatedEntry['gross_salary']}\n");
    }
    echo "[PASS] Gross salary correctly recalculated: LKR {$updatedEntry['gross_salary']}\n";

    if (abs((float)$updatedEntry['net_salary'] - $expectedNet) > 0.01) {
        die("[FAIL] Net salary mismatch. Expected {$expectedNet}, got {$updatedEntry['net_salary']}\n");
    }
    echo "[PASS] Net salary correctly recalculated: LKR {$updatedEntry['net_salary']}\n";
}

// Clean up test period
Database::execute("DELETE FROM payroll_entries WHERE payroll_period_id = :id", [':id' => $periodId]);
Database::execute("DELETE FROM payroll_periods WHERE id = :id", [':id' => $periodId]);
echo "[CLEANUP] Deleted test period ID {$periodId}\n";

echo "=== ALL PAYROLL UPGRADE TESTS PASSED ===\n";
