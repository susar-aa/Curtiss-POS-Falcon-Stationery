<?php
/**
 * CLI Test Suite for 88mm Thermal Payslip System
 * Tests:
 * 1. Payroll::getPayslip($entryId) includes sales, advance balance, and EPF/ETF details.
 * 2. Payroll::getPeriodPayslips($periodId) batch-fetches all employee payslips.
 * 3. POS/payslip_receipt.php renders valid 88mm HTML for both single employee and batch mode.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Payroll.php';
require_once __DIR__ . '/../core/Settings.php';

$_SESSION['user_id'] = 1;
$_SESSION['role_code'] = ROLE_SUPER_ADMIN;
$_SESSION['user_name'] = 'Test Superadmin';
$_SESSION['branch_id'] = 1;
$_SESSION['permissions'] = ['payroll.view', 'payroll.pay', 'payroll.create'];

$testPassed = 0;
$testFailed = 0;

function assertCondition(string $desc, bool $condition, string $detail = ''): void {
    global $testPassed, $testFailed;
    if ($condition) {
        echo "  [PASS] {$desc}\n";
        $testPassed++;
    } else {
        echo "  [FAIL] {$desc} " . ($detail ? "({$detail})" : "") . "\n";
        $testFailed++;
    }
}

echo "=========================================================\n";
echo "  CURTISS POS - 88mm Thermal Payslip CLI Verification\n";
echo "=========================================================\n\n";

// 1. Fetch an existing payroll period and entry
$period = Database::fetchOne("SELECT * FROM payroll_periods ORDER BY id DESC LIMIT 1");
if (!$period) {
    echo "Creating a temporary test period for verification...\n";
    $createRes = Payroll::createPeriod([
        'branch_id'   => 1,
        'period_name' => 'CLI Verification Period',
        'start_date'  => date('Y-m-01'),
        'end_date'    => date('Y-m-t')
    ], 1);
    $period = Database::fetchOne("SELECT * FROM payroll_periods WHERE id = :id", [':id' => $createRes['period_id']]);
}

assertCondition("Payroll Period Available", !empty($period['id']), "Period ID: " . ($period['id'] ?? 'none'));

$targetPeriodId = (int)$period['id'];
$entries = Payroll::getPeriodEntries($targetPeriodId);
assertCondition("Period Entries Retrieved", !empty($entries), "Count: " . count($entries));

$firstEntry = $entries[0] ?? null;
if ($firstEntry) {
    $entryId = (int)$firstEntry['id'];

    // Test 1: Single Payslip Fetch
    echo "\n--- Test 1: Payroll::getPayslip --- \n";
    $ps = Payroll::getPayslip($entryId);
    assertCondition("Payslip Data Fetched", !empty($ps), "Entry ID: {$entryId}");
    assertCondition("Employee Code & Name Present", !empty($ps['employee_code']) && !empty($ps['full_name']), "{$ps['employee_code']} - {$ps['full_name']}");
    assertCondition("Basic Salary Exists", isset($ps['basic_salary']) && is_numeric($ps['basic_salary']), "Basic: {$ps['basic_salary']}");
    assertCondition("EPF 8% and ETF 3% Present", isset($ps['employee_epf_amount']) && isset($ps['employer_etf_amount']), "EPF8: {$ps['employee_epf_amount']}, ETF3: {$ps['employer_etf_amount']}");
    assertCondition("Sales Coverage Fields Attached", array_key_exists('period_sales', $ps) && array_key_exists('sales_count', $ps), "Sales: {$ps['period_sales']}, Count: {$ps['sales_count']}");
    assertCondition("Advance Balance Attached", array_key_exists('remaining_advance_balance', $ps), "Advance Bal: {$ps['remaining_advance_balance']}");

    // Test 2: Batch Payslips Fetch
    echo "\n--- Test 2: Payroll::getPeriodPayslips (Batch) --- \n";
    $batchPayslips = Payroll::getPeriodPayslips($targetPeriodId);
    assertCondition("Batch Payslips Fetched", count($batchPayslips) === count($entries), "Fetched: " . count($batchPayslips) . ", Expected: " . count($entries));
    if (!empty($batchPayslips)) {
        $firstBatch = $batchPayslips[0];
        assertCondition("Batch Item Has Complete Schema", isset($firstBatch['gross_salary']) && isset($firstBatch['net_salary']), "Net: {$firstBatch['net_salary']}");
    }

    // Test 3: Rendering payslip_receipt.php for Single Employee
    echo "\n--- Test 3: POS/payslip_receipt.php (Single Employee) --- \n";
    $_GET = ['entry_id' => $entryId];
    ob_start();
    include __DIR__ . '/../payslip_receipt.php';
    $outputSingle = ob_get_clean();

    assertCondition("Receipt HTML Generated", strlen($outputSingle) > 500, "Length: " . strlen($outputSingle));
    assertCondition("88mm Thermal Page CSS Present", strpos($outputSingle, '88mm') !== false, "Matches 88mm printer geometry");
    assertCondition("Employee Name Rendered", strpos($outputSingle, htmlspecialchars($ps['full_name'])) !== false);
    assertCondition("Earnings & Deductions Rendered", strpos($outputSingle, 'Earnings Breakdown') !== false && strpos($outputSingle, 'Deductions Breakdown') !== false);
    assertCondition("Statutory Contributions Rendered", strpos($outputSingle, 'Employer Contributions') !== false);

    // Test 4: Rendering payslip_receipt.php for Batch All
    echo "\n--- Test 4: POS/payslip_receipt.php (Batch All Mode) --- \n";
    $_GET = ['period_id' => $targetPeriodId, 'all' => '1'];
    ob_start();
    include __DIR__ . '/../payslip_receipt.php';
    $outputBatch = ob_get_clean();

    assertCondition("Batch Receipt HTML Generated", strlen($outputBatch) > 1000, "Length: " . strlen($outputBatch));
    assertCondition("Page Break CSS Present for Batch", strpos($outputBatch, 'page-break-after: always') !== false);
    assertCondition("Batch Ticket Count Matches", substr_count($outputBatch, 'class="payslip-ticket"') === count($entries), "Tickets: " . substr_count($outputBatch, 'class="payslip-ticket"'));
}

echo "\n=========================================================\n";
echo "  TEST SUMMARY: {$testPassed} Passed, {$testFailed} Failed\n";
echo "=========================================================\n";

if ($testFailed > 0) {
    exit(1);
}
exit(0);
