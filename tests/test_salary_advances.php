<?php
/**
 * Curtiss POS - Comprehensive Employee Salary Advance System CLI Verification Suite
 * 
 * Verifies:
 * 1. Chart of Accounts Setup (1048, 1012, 1010, 1015, 2050)
 * 2. Eligible Payment Accounts Lookup (Balances & Account Mapping)
 * 3. Advance Disbursement via Cash Drawer (1012):
 *    - GL Posting (Dr 1048 / Cr 1012)
 *    - Cash Drawer Movement Out (type: employee_advance)
 *    - Business Day Expected Cash Reduction
 * 4. Advance Disbursement via Cash in Hand (1010):
 *    - GL Posting (Dr 1048 / Cr 1010)
 *    - Verified NO Cash Drawer Movement
 * 5. Advance Disbursement via Bank Transfer:
 *    - GL Posting (Dr 1048 / Cr Bank COA)
 * 6. Duplicate Payment Prevention (Idempotency)
 * 7. Employee Advance Summary & Outstanding Tracking
 * 8. Payroll Integration & Automatic Deduction Recovery:
 *    - Statutory EPF/ETF calculated on full basic
 *    - Net pay reduced by advance deduction
 *    - Period Approval posts Dr 2050 / Cr 1048
 *    - Advance status updated to fully_recovered / active partial
 * 9. Advance Cancellation & Reversal:
 *    - Reverses double-entry GL journal
 *    - Restores cash drawer if paid from 1012
 *    - Blocks cancellation if already recovered through payroll
 * 10. Payroll Period Cancellation & Un-recovery:
 *    - Restores recovered advance balances
 *    - Reverses payroll GL journal
 * 11. Advance Report & Aging Analysis:
 *    - KPIs, branch-wise, employee-wise, drawer cash-outs, aging brackets
 * 12. Isolated Test Cleanup (No table truncation)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Audit.php';
require_once __DIR__ . '/../core/Accounting.php';
require_once __DIR__ . '/../core/Employee.php';
require_once __DIR__ . '/../core/Payroll.php';
require_once __DIR__ . '/../core/BusinessDay.php';
require_once __DIR__ . '/../core/Banking.php';
require_once __DIR__ . '/../core/PettyCash.php';

$branchId = 1;
$userId = 1;

// Emulate session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id'] = $userId;
$_SESSION['branch_id'] = $branchId;
$_SESSION['role_id'] = 1;
$_SESSION['role_code'] = ROLE_SUPER_ADMIN;

echo "====================================================================\n";
echo "   CURTISS POS — SALARY ADVANCE SYSTEM COMPREHENSIVE CLI TEST\n";
echo "====================================================================\n\n";

$testsPassed = 0;
$testsFailed = 0;

function assertTest(string $name, bool $condition, string $detail = '') {
    global $testsPassed, $testsFailed;
    if ($condition) {
        echo "[PASS] {$name}" . ($detail ? " ({$detail})" : "") . "\n";
        $testsPassed++;
    } else {
        echo "[FAIL] {$name}" . ($detail ? " - ERROR: {$detail}" : "") . "\n";
        $testsFailed++;
    }
}

$cleanupAdvanceIds = [];
$cleanupJournalIds = [];
$cleanupPeriodIds  = [];
$cleanupMovementIds= [];
$testEmployeeId    = null;

try {
    // ----------------------------------------------------
    // TEST 1: COA Accounts Setup
    // ----------------------------------------------------
    echo "--- 1. CHART OF ACCOUNTS VERIFICATION ---\n";
    $accEmpAdvances = Accounting::getAccountIdByCode(Accounting::CODE_EMPLOYEE_ADVANCES); // 1048
    $accCashDrawer  = Accounting::getAccountIdByCode(Accounting::CODE_CASH_DRAWER);       // 1012
    $accCashInHand  = Accounting::getAccountIdByCode(Accounting::CODE_CASH_IN_HAND);       // 1010
    $accPettyCash   = Accounting::getAccountIdByCode(Accounting::CODE_PETTY_CASH);        // 1015
    $accSalPayable  = Accounting::getAccountIdByCode(Accounting::CODE_SALARY_PAYABLE);     // 2050

    assertTest("COA 1048 (Employee Advances) exists", !empty($accEmpAdvances), "ID: {$accEmpAdvances}");
    assertTest("COA 1012 (Cash Drawer) exists", !empty($accCashDrawer), "ID: {$accCashDrawer}");
    assertTest("COA 1010 (Cash in Hand) exists", !empty($accCashInHand), "ID: {$accCashInHand}");
    assertTest("COA 1015 (Petty Cash) exists", !empty($accPettyCash), "ID: {$accPettyCash}");
    assertTest("COA 2050 (Salaries & Wages Payable) exists", !empty($accSalPayable), "ID: {$accSalPayable}");

    // ----------------------------------------------------
    // TEST 2: Eligible Payment Accounts
    // ----------------------------------------------------
    echo "\n--- 2. ELIGIBLE PAYMENT ACCOUNTS ---\n";
    $eligibleAccounts = Payroll::getEligiblePaymentAccounts($branchId);
    assertTest("getEligiblePaymentAccounts returns non-empty list", !empty($eligibleAccounts));

    $hasDrawer = false;
    $hasCash   = false;
    $hasPetty  = false;
    foreach ($eligibleAccounts as $ea) {
        if ($ea['account_code'] === '1012') $hasDrawer = true;
        if ($ea['account_code'] === '1010') $hasCash = true;
        if ($ea['account_code'] === '1015') $hasPetty = true;
    }
    assertTest("Includes Cash Drawer (1012)", $hasDrawer);
    assertTest("Includes Cash in Hand (1010)", $hasCash);
    assertTest("Includes Petty Cash (1015)", $hasPetty);

    // ----------------------------------------------------
    // TEST 3: Create Test Employee
    // ----------------------------------------------------
    echo "\n--- 3. TEST EMPLOYEE CREATION ---\n";
    $empCode = 'TEST-ADV-' . substr(time(), -4);
    $empNic  = '99' . rand(1000000, 9999999) . 'V';
    $empRes = Employee::create([
        'employee_code'     => $empCode,
        'full_name'         => 'Test Advance Employee ' . rand(100, 999),
        'nic'               => $empNic,
        'phone'             => '077' . rand(1000000, 9999999),
        'email'             => 'testadv' . rand(100, 999) . '@curtiss.lk',
        'job_role'          => 'Cashier',
        'branch_id'         => $branchId,
        'joining_date'      => date('Y-m-d'),
        'basic_salary'      => 60000.00,
        'is_sales_rep'      => 0,
        'epf_etf_eligible'  => 1,
        'status'            => 'active'
    ]);

    assertTest("Test Employee created successfully", !empty($empRes['success']), "ID: " . ($empRes['employee_id'] ?? '') . " | " . ($empRes['message'] ?? ''));
    $testEmployeeId = (int)($empRes['employee_id'] ?? 0);

    // ----------------------------------------------------
    // TEST 4: Ensure Active Business Day
    // ----------------------------------------------------
    echo "\n--- 4. BUSINESS DAY & CASH DRAWER CHECK ---\n";
    $activeDay = BusinessDay::getActiveDay($branchId);
    $createdTestDay = false;
    if (!$activeDay) {
        $openRes = BusinessDay::openDay([
            'branch_id'    => $branchId,
            'opening_cash' => 50000.00,
            'notes'        => 'Test Business Day for Advance Testing'
        ], $userId);
        assertTest("Opened test business day", !empty($openRes['success']));
        $activeDay = BusinessDay::getActiveDay($branchId);
        $createdTestDay = true;
    } else {
        echo "[INFO] Existing active Business Day #{$activeDay['id']} found.\n";
    }

    $initialExpectedCash = BusinessDay::getExpectedDrawerCash($branchId);
    echo "[INFO] Initial Expected Cash in Drawer: LKR {$initialExpectedCash}\n";

    // ----------------------------------------------------
    // TEST 5: Advance Disbursement via Cash Drawer (1012)
    // ----------------------------------------------------
    echo "\n--- 5. DISBURSE ADVANCE VIA CASH DRAWER (1012) ---\n";
    $advDrawerAmount = 5000.00;
    $advRes1 = Payroll::disburseAdvance([
        'employee_id'       => $testEmployeeId,
        'amount'            => $advDrawerAmount,
        'disbursement_date' => date('Y-m-d'),
        'payment_method'    => 'Cash Drawer',
        'source_account_id' => $accCashDrawer,
        'reference_no'      => 'REF-DRAWER-01',
        'notes'             => 'Emergency medical advance'
    ], $userId);

    assertTest("Disburse advance via Cash Drawer succeeds", !empty($advRes1['success']), $advRes1['message'] ?? '');
    $adv1Id = (int)($advRes1['advance_id'] ?? 0);
    if ($adv1Id) $cleanupAdvanceIds[] = $adv1Id;

    // Verify DB Record
    $adv1 = Database::fetchOne("SELECT * FROM employee_advances WHERE id = :id", [':id' => $adv1Id]);
    assertTest("Advance record status is active", $adv1 && $adv1['status'] === 'active');
    assertTest("Advance amount is correct", (float)$adv1['amount'] === $advDrawerAmount);
    assertTest("Remaining amount equals amount", (float)$adv1['remaining_amount'] === $advDrawerAmount);
    assertTest("Payment method stored as Cash Drawer", $adv1['payment_method'] === 'Cash Drawer');

    // Verify Double-Entry Accounting Journal
    $je1 = Database::fetchOne("
        SELECT id, total_debit, total_credit, posting_status 
        FROM journal_entries 
        WHERE source_type = 'employee_advance' AND source_id = :id
    ", [':id' => $adv1Id]);

    assertTest("Journal entry posted for Advance #1", !empty($je1) && $je1['posting_status'] === 'posted', "JE ID: " . ($je1['id'] ?? ''));
    if ($je1) {
        $cleanupJournalIds[] = (int)$je1['id'];
        assertTest("Journal entry balances (Dr == Cr)", (float)$je1['total_debit'] === (float)$je1['total_credit']);
        assertTest("Journal amount equals advance amount", (float)$je1['total_debit'] === $advDrawerAmount);

        $lines1 = Database::fetchAll("SELECT * FROM journal_lines WHERE journal_entry_id = :jeid", [':jeid' => $je1['id']]);
        $hasDr1048 = false;
        $hasCr1012 = false;
        foreach ($lines1 as $l) {
            if ((int)$l['account_id'] === (int)$accEmpAdvances && (float)$l['debit'] === $advDrawerAmount) $hasDr1048 = true;
            if ((int)$l['account_id'] === (int)$accCashDrawer && (float)$l['credit'] === $advDrawerAmount) $hasCr1012 = true;
        }
        assertTest("Journal debited 1048 (Employee Advances)", $hasDr1048);
        assertTest("Journal credited 1012 (Cash Drawer)", $hasCr1012);
    }

    // Verify Cash Drawer Movement Created
    $cdm1 = Database::fetchOne("
        SELECT * FROM cash_drawer_movements 
        WHERE source_type = 'employee_advances' AND source_id = :id
    ", [':id' => $adv1Id]);

    assertTest("Cash drawer movement recorded", !empty($cdm1));
    if ($cdm1) {
        $cleanupMovementIds[] = (int)$cdm1['id'];
        assertTest("Movement type is employee_advance", $cdm1['movement_type'] === 'employee_advance');
        assertTest("Movement direction is out", $cdm1['direction'] === 'out');
        assertTest("Movement amount is 5000.00", (float)$cdm1['amount'] === $advDrawerAmount);
    }

    // Verify Expected Drawer Cash Reduced
    $newExpectedCash = BusinessDay::getExpectedDrawerCash($branchId);
    $diff = round($initialExpectedCash - $newExpectedCash, 2);
    assertTest("Expected drawer cash reduced by advance amount", $diff === $advDrawerAmount, "Initial: {$initialExpectedCash}, New: {$newExpectedCash}");

    // ----------------------------------------------------
    // TEST 6: Advance Disbursement via Cash in Hand (1010)
    // ----------------------------------------------------
    echo "\n--- 6. DISBURSE ADVANCE VIA CASH IN HAND (1010) ---\n";
    $advCashAmount = 3000.00;
    $advRes2 = Payroll::disburseAdvance([
        'employee_id'       => $testEmployeeId,
        'amount'            => $advCashAmount,
        'disbursement_date' => date('Y-m-d'),
        'payment_method'    => 'Cash in Hand',
        'source_account_id' => $accCashInHand,
        'reference_no'      => 'REF-SAFE-01',
        'notes'             => 'School fees advance'
    ], $userId);

    assertTest("Disburse advance via Cash in Hand succeeds", !empty($advRes2['success']));
    $adv2Id = (int)($advRes2['advance_id'] ?? 0);
    if ($adv2Id) $cleanupAdvanceIds[] = $adv2Id;

    // Verify GL: Dr 1048 / Cr 1010
    $je2 = Database::fetchOne("
        SELECT id, total_debit, total_credit 
        FROM journal_entries 
        WHERE source_type = 'employee_advance' AND source_id = :id
    ", [':id' => $adv2Id]);
    if ($je2) {
        $cleanupJournalIds[] = (int)$je2['id'];
        $lines2 = Database::fetchAll("SELECT * FROM journal_lines WHERE journal_entry_id = :jeid", [':jeid' => $je2['id']]);
        $hasDr1048_2 = false;
        $hasCr1010_2 = false;
        foreach ($lines2 as $l) {
            if ((int)$l['account_id'] === (int)$accEmpAdvances && (float)$l['debit'] === $advCashAmount) $hasDr1048_2 = true;
            if ((int)$l['account_id'] === (int)$accCashInHand && (float)$l['credit'] === $advCashAmount) $hasCr1010_2 = true;
        }
        assertTest("Journal 2 debited 1048 and credited 1010", $hasDr1048_2 && $hasCr1010_2);
    }

    // Verify NO Cash Drawer Movement Created for Cash in Hand
    $cdm2 = Database::fetchOne("
        SELECT * FROM cash_drawer_movements 
        WHERE source_type = 'employee_advances' AND source_id = :id
    ", [':id' => $adv2Id]);
    assertTest("NO cash drawer movement created for Cash in Hand disbursement", empty($cdm2));

    // Verify Drawer Cash Unchanged
    $drawerCashAfter2 = BusinessDay::getExpectedDrawerCash($branchId);
    assertTest("Drawer cash unaffected by Cash in Hand advance", $drawerCashAfter2 === $newExpectedCash);

    // ----------------------------------------------------
    // TEST 7: Advance Disbursement via Bank Transfer
    // ----------------------------------------------------
    echo "\n--- 7. DISBURSE ADVANCE VIA BANK TRANSFER ---\n";
    $bankRow = Database::fetchOne("SELECT id, account_id FROM bank_accounts WHERE status = 'active' LIMIT 1");
    if ($bankRow) {
        $advBankAmount = 4000.00;
        $advRes3 = Payroll::disburseAdvance([
            'employee_id'       => $testEmployeeId,
            'amount'            => $advBankAmount,
            'disbursement_date' => date('Y-m-d'),
            'payment_method'    => 'Bank Transfer',
            'bank_account_id'   => (int)$bankRow['id'],
            'source_account_id' => (int)$bankRow['account_id'],
            'reference_no'      => 'TRF-100293',
            'notes'             => 'Direct bank transfer advance'
        ], $userId);

        assertTest("Disburse advance via Bank Transfer succeeds", !empty($advRes3['success']));
        $adv3Id = (int)($advRes3['advance_id'] ?? 0);
        if ($adv3Id) $cleanupAdvanceIds[] = $adv3Id;

        $je3 = Database::fetchOne("
            SELECT id, total_debit, total_credit 
            FROM journal_entries 
            WHERE source_type = 'employee_advance' AND source_id = :id
        ", [':id' => $adv3Id]);
        if ($je3) {
            $cleanupJournalIds[] = (int)$je3['id'];
            $lines3 = Database::fetchAll("SELECT * FROM journal_lines WHERE journal_entry_id = :jeid", [':jeid' => $je3['id']]);
            $hasDr1048_3 = false;
            $hasCrBank   = false;
            foreach ($lines3 as $l) {
                if ((int)$l['account_id'] === (int)$accEmpAdvances && (float)$l['debit'] === $advBankAmount) $hasDr1048_3 = true;
                if ((int)$l['account_id'] === (int)$bankRow['account_id'] && (float)$l['credit'] === $advBankAmount) $hasCrBank = true;
            }
            assertTest("Journal 3 debited 1048 and credited Bank Account COA", $hasDr1048_3 && $hasCrBank);
        }
    } else {
        echo "[SKIP] No active bank account in DB for Bank Advance test.\n";
    }

    // ----------------------------------------------------
    // TEST 8: Duplicate Payment Prevention (Idempotency)
    // ----------------------------------------------------
    echo "\n--- 8. DUPLICATE PAYMENT PREVENTION ---\n";
    $dupRes = Payroll::disburseAdvance([
        'employee_id'       => $testEmployeeId,
        'amount'            => $advDrawerAmount, // identical amount
        'disbursement_date' => date('Y-m-d'),    // identical date
        'payment_method'    => 'Cash Drawer',
        'source_account_id' => $accCashDrawer,
        'reference_no'      => 'REF-DRAWER-DUP',
        'notes'             => 'Duplicate test attempt'
    ], $userId);

    assertTest("Duplicate advance payment prevented within 3 minutes", empty($dupRes['success']) && strpos($dupRes['message'], 'Duplicate payment prevented') !== false, $dupRes['message'] ?? '');

    // ----------------------------------------------------
    // TEST 9: Employee Advance Summary Lookup
    // ----------------------------------------------------
    echo "\n--- 9. EMPLOYEE ADVANCE SUMMARY LOOKUP ---\n";
    $empSummary = Payroll::getEmployeeAdvanceSummary($testEmployeeId);
    assertTest("getEmployeeAdvanceSummary succeeds", !empty($empSummary['success']));
    $expectedTotalAdv = $advDrawerAmount + $advCashAmount + (isset($advBankAmount) ? $advBankAmount : 0);
    assertTest("Total advanced matches expected sum", (float)$empSummary['total_advanced'] === (float)$expectedTotalAdv, "Expected: {$expectedTotalAdv}, Got: {$empSummary['total_advanced']}");
    assertTest("Total recovered is 0.00", (float)$empSummary['total_recovered'] === 0.00);
    assertTest("Remaining balance matches total advanced", (float)$empSummary['remaining_balance'] === (float)$expectedTotalAdv);
    assertTest("Active advance count is correct", (int)$empSummary['active_count'] >= 2);

    // ----------------------------------------------------
    // TEST 10: Advance Cancellation & Reversal (Before recovery)
    // ----------------------------------------------------
    echo "\n--- 10. ADVANCE CANCELLATION & REVERSAL ---\n";
    // Disburse a test advance specifically to test cancellation
    $cancelTestAmt = 1500.00;
    $advCancelRes = Payroll::disburseAdvance([
        'employee_id'       => $testEmployeeId,
        'amount'            => $cancelTestAmt,
        'disbursement_date' => date('Y-m-d'),
        'payment_method'    => 'Cash Drawer',
        'source_account_id' => $accCashDrawer,
        'reference_no'      => 'REF-TO-CANCEL',
        'notes'             => 'Accidental disbursement to be cancelled'
    ], $userId);

    assertTest("Disbursed advance to test cancellation", !empty($advCancelRes['success']));
    $cancelAdvId = (int)$advCancelRes['advance_id'];
    $cleanupAdvanceIds[] = $cancelAdvId;

    // Check drawer expected cash before cancel
    $drawerBeforeCancel = BusinessDay::getExpectedDrawerCash($branchId);

    // Now cancel it
    $cancelRes = Payroll::cancelAdvance($cancelAdvId, "Entered wrong amount by mistake", $userId);
    assertTest("cancelAdvance succeeds", !empty($cancelRes['success']), $cancelRes['message'] ?? '');

    // Check advance record status
    $advCancelledRow = Database::fetchOne("SELECT * FROM employee_advances WHERE id = :id", [':id' => $cancelAdvId]);
    assertTest("Advance record status changed to 'cancelled'", $advCancelledRow && $advCancelledRow['status'] === 'cancelled');
    assertTest("Advance remaining_amount set to 0.00", (float)$advCancelledRow['remaining_amount'] === 0.00);
    assertTest("Cancel reason recorded", $advCancelledRow['cancel_reason'] === "Entered wrong amount by mistake");

    // Check Journal Reversal
    $origJe = Database::fetchOne("
        SELECT id FROM journal_entries 
        WHERE source_type = 'employee_advance' AND source_id = :id AND posting_status = 'reversed'
    ", [':id' => $cancelAdvId]);
    assertTest("Original journal entry marked as reversed", !empty($origJe));

    $revJe = Database::fetchOne("
        SELECT id, total_debit, total_credit, posting_status 
        FROM journal_entries 
        WHERE source_type = 'reversal' AND source_id = :jeid
    ", [':jeid' => $origJe['id']]);
    assertTest("Reversal journal entry posted", !empty($revJe) && $revJe['posting_status'] === 'posted');
    if ($revJe) $cleanupJournalIds[] = (int)$revJe['id'];

    // Check Drawer Restoration
    $drawerAfterCancel = BusinessDay::getExpectedDrawerCash($branchId);
    $drawerDiff = round($drawerAfterCancel - $drawerBeforeCancel, 2);
    assertTest("Cash drawer cash restored by cancelled advance amount", $drawerDiff === $cancelTestAmt, "Before: {$drawerBeforeCancel}, After: {$drawerAfterCancel}");

    // ----------------------------------------------------
    // TEST 11: Payroll Deduction & Period Approval
    // ----------------------------------------------------
    echo "\n--- 11. PAYROLL INTEGRATION & AUTOMATIC DEDUCTION RECOVERY ---\n";
    $pCode = 'PR-TEST-' . substr(time(), -4);
    $startDate = date('Y-m-01');
    $endDate   = date('Y-m-t');

    // Create Draft Payroll Period
    $createPeriodRes = Payroll::createPeriod([
        'period_code' => $pCode,
        'period_name' => "Test Advance Recovery Period " . date('M Y'),
        'branch_id'   => $branchId,
        'start_date'  => $startDate,
        'end_date'    => $endDate,
        'notes'       => 'Payroll Period for Salary Advance Recovery Test'
    ], $userId);

    assertTest("Payroll period created", !empty($createPeriodRes['success']), "Period ID: " . ($createPeriodRes['period_id'] ?? ''));
    $testPeriodId = (int)($createPeriodRes['period_id'] ?? 0);
    if ($testPeriodId) $cleanupPeriodIds[] = $testPeriodId;

    // Period entries are auto-generated on createPeriod
    $entryCount = Database::fetchOne("SELECT COUNT(*) AS cnt FROM payroll_entries WHERE payroll_period_id = :pid", [':pid' => $testPeriodId]);
    assertTest("Entries auto-generated on period creation", (int)($entryCount['cnt'] ?? 0) > 0, "Entries: " . ($entryCount['cnt'] ?? 0));

    // Inspect the entry for our test employee
    $entryRow = Database::fetchOne("
        SELECT * FROM payroll_entries 
        WHERE payroll_period_id = :pid AND employee_id = :eid
    ", [':pid' => $testPeriodId, ':eid' => $testEmployeeId]);

    assertTest("Payroll entry found for test employee", !empty($entryRow));
    if ($entryRow) {
        $basicSalary   = (float)$entryRow['basic_salary']; // 60,000
        $grossSalary   = (float)$entryRow['gross_salary'];
        $advanceDeduct = (float)$entryRow['salary_advances_deducted'];
        $eeEpf         = (float)($entryRow['employee_epf_amount'] ?? 0); // 8% of 60,000 = 4,800
        $netSalary     = (float)$entryRow['net_salary'];

        echo "[INFO] Basic: LKR {$basicSalary}, Gross: LKR {$grossSalary}, EE EPF (8%): LKR {$eeEpf}, Advance Deducted: LKR {$advanceDeduct}, Net Salary: LKR {$netSalary}\n";

        assertTest("Statutory EPF calculated correctly (8% of basic)", $eeEpf === 4800.00);
        assertTest("Advance deductions applied to payroll entry", $advanceDeduct > 0, "Deducted: LKR {$advanceDeduct}");
        $expectedNet = round($grossSalary - $eeEpf - $advanceDeduct, 2);
        assertTest("Net salary accurately reflects advance deduction", $netSalary === $expectedNet, "Net: {$netSalary}, Expected: {$expectedNet}");
    }

    // Approve the Payroll Period
    echo "\n--- 11.1 APPROVING PAYROLL PERIOD & VERIFYING COA ADVANCE POSTING ---\n";
    $approveRes = Payroll::approvePeriod($testPeriodId, $userId);
    assertTest("Payroll period approved successfully", !empty($approveRes['success']), $approveRes['message'] ?? '');

    // Check Period Double-Entry Journal
    $periodJe = Database::fetchOne("
        SELECT id, total_debit, total_credit, posting_status 
        FROM journal_entries 
        WHERE source_type = 'payroll' AND source_id = :pid
    ", [':pid' => $testPeriodId]);

    assertTest("Payroll journal entry posted", !empty($periodJe) && $periodJe['posting_status'] === 'posted', "JE ID: " . ($periodJe['id'] ?? ''));
    if ($periodJe) {
        $cleanupJournalIds[] = (int)$periodJe['id'];
        assertTest("Payroll journal entry is balanced (Dr == Cr)", (float)$periodJe['total_debit'] === (float)$periodJe['total_credit']);

        // Verify that credit line to 1048 (Employee Advances) exists in the payroll journal
        $periodLines = Database::fetchAll("SELECT * FROM journal_lines WHERE journal_entry_id = :jeid", [':jeid' => $periodJe['id']]);
        $hasCr1048Payroll = false;
        $cr1048Amount = 0.00;
        foreach ($periodLines as $pl) {
            if ((int)$pl['account_id'] === (int)$accEmpAdvances && (float)$pl['credit'] > 0) {
                $hasCr1048Payroll = true;
                $cr1048Amount = (float)$pl['credit'];
            }
        }
        assertTest("Payroll journal credited 1048 (Employee Advances) to reduce receivable", $hasCr1048Payroll, "Cr Amount: LKR {$cr1048Amount}");
    }

    // Verify Advance Records Updated (Recovered)
    $advAfterRecovery = Database::fetchAll("
        SELECT id, amount, recovered_amount, remaining_amount, status 
        FROM employee_advances 
        WHERE employee_id = :eid AND status != 'cancelled'
    ", [':eid' => $testEmployeeId]);

    $totalRecovFromAdv = 0.00;
    foreach ($advAfterRecovery as $aar) {
        $totalRecovFromAdv += (float)$aar['recovered_amount'];
        echo "[INFO] Advance #{$aar['id']}: Amount = {$aar['amount']}, Recovered = {$aar['recovered_amount']}, Remaining = {$aar['remaining_amount']}, Status = {$aar['status']}\n";
    }
    assertTest("Advances recovered_amount updated in DB", $totalRecovFromAdv > 0, "Total Recovered: LKR {$totalRecovFromAdv}");

    // Cannot cancel an advance once recovered
    $blockedCancel = Payroll::cancelAdvance($adv1Id, "Trying to cancel recovered advance", $userId);
    assertTest("Cannot cancel advance after recovery in payroll", empty($blockedCancel['success']) && strpos($blockedCancel['message'], 'already been recovered') !== false, $blockedCancel['message'] ?? '');

    // ----------------------------------------------------
    // TEST 12: Payroll Period Cancellation & Un-Recovery
    // ----------------------------------------------------
    echo "\n--- 12. PAYROLL PERIOD CANCELLATION & UN-RECOVERY ---\n";
    $cancelPeriodRes = Payroll::cancelPeriod($testPeriodId, "Testing payroll cancellation & advance un-recovery", $userId);
    assertTest("cancelPeriod succeeds", !empty($cancelPeriodRes['success']), $cancelPeriodRes['message'] ?? '');

    // Check that recovered advances were restored
    $advAfterUnrecovery = Database::fetchAll("
        SELECT id, amount, recovered_amount, remaining_amount, status 
        FROM employee_advances 
        WHERE employee_id = :eid AND status != 'cancelled'
    ", [':eid' => $testEmployeeId]);

    $allRestored = true;
    foreach ($advAfterUnrecovery as $aaur) {
        if ((float)$aaur['recovered_amount'] > 0 || $aaur['status'] !== 'active') {
            $allRestored = false;
        }
    }
    assertTest("All advance balances restored to active with 0 recovered", $allRestored);

    // Verify Payroll Period Journal Reversed
    $revPeriodJe = Database::fetchOne("
        SELECT id, posting_status 
        FROM journal_entries 
        WHERE source_type = 'payroll' AND source_id = :pid AND posting_status = 'reversed'
    ", [':pid' => $testPeriodId]);
    assertTest("Payroll period journal was marked reversed", !empty($revPeriodJe));

    // ----------------------------------------------------
    // TEST 13: Advance Reports & Analytics
    // ----------------------------------------------------
    echo "\n--- 13. ADVANCE REPORTS & ANALYTICS ---\n";
    $report = Payroll::getAdvanceReport(['branch_id' => $branchId]);
    assertTest("getAdvanceReport returns success", !empty($report['success']));
    assertTest("Report includes KPIs", isset($report['kpis']['total_advanced']) && isset($report['kpis']['total_outstanding']));
    assertTest("Report includes branch breakdown", !empty($report['branch_breakdown']));
    assertTest("Report includes employee breakdown", !empty($report['employee_breakdown']));
    assertTest("Report includes drawer cash-out breakdown", isset($report['drawer_cashouts']));
    assertTest("Report includes aging summary brackets (0-30d, 31-60d, etc.)", isset($report['aging_summary']['days_0_30']));

    echo "[INFO] Report KPIs: Total Advanced = LKR {$report['kpis']['total_advanced']}, Outstanding = LKR {$report['kpis']['total_outstanding']}, Drawer Cashouts = LKR {$report['kpis']['total_drawer_cashout']}\n";

} catch (Throwable $e) {
    echo "\n[EXCEPTION] " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
    $testsFailed++;
} finally {
    // ----------------------------------------------------
    // CLEANUP TEST DATA (NO TABLE TRUNCATION - Rule 17)
    // ----------------------------------------------------
    echo "\n--- CLEANING UP TEST RECORDS ---\n";

    if (!empty($cleanupMovementIds)) {
        Database::execute("DELETE FROM cash_drawer_movements WHERE id IN (" . implode(',', $cleanupMovementIds) . ")");
        echo "[CLEANUP] Deleted " . count($cleanupMovementIds) . " test cash drawer movements.\n";
    }

    if (!empty($cleanupPeriodIds)) {
        Database::execute("DELETE FROM payroll_entries WHERE payroll_period_id IN (" . implode(',', $cleanupPeriodIds) . ")");
        Database::execute("DELETE FROM payroll_periods WHERE id IN (" . implode(',', $cleanupPeriodIds) . ")");
        echo "[CLEANUP] Deleted test payroll periods and entries.\n";
    }

    if (!empty($cleanupJournalIds)) {
        Database::execute("DELETE FROM journal_lines WHERE journal_entry_id IN (" . implode(',', $cleanupJournalIds) . ")");
        Database::execute("DELETE FROM journal_entries WHERE id IN (" . implode(',', $cleanupJournalIds) . ")");
        echo "[CLEANUP] Deleted " . count($cleanupJournalIds) . " test journal entries.\n";
    }

    // Clean any reverse journals tied to our test advances
    if (!empty($cleanupAdvanceIds)) {
        $revJes = Database::fetchAll("
            SELECT id FROM journal_entries 
            WHERE source_type = 'employee_advance' AND source_id IN (" . implode(',', $cleanupAdvanceIds) . ")
        ");
        foreach ($revJes as $rj) {
            Database::execute("DELETE FROM journal_lines WHERE journal_entry_id = :id", [':id' => $rj['id']]);
            Database::execute("DELETE FROM journal_entries WHERE id = :id", [':id' => $rj['id']]);
        }

        // Delete test drawer movements associated with these advances
        Database::execute("
            DELETE FROM cash_drawer_movements 
            WHERE source_type = 'employee_advances' AND source_id IN (" . implode(',', $cleanupAdvanceIds) . ")
        ");

        Database::execute("DELETE FROM employee_advances WHERE id IN (" . implode(',', $cleanupAdvanceIds) . ")");
        echo "[CLEANUP] Deleted " . count($cleanupAdvanceIds) . " test employee advances.\n";
    }

    if ($testEmployeeId) {
        Database::execute("DELETE FROM employees WHERE id = :id", [':id' => $testEmployeeId]);
        echo "[CLEANUP] Deleted test employee ID {$testEmployeeId}.\n";
    }

    if (!empty($createdTestDay) && !empty($activeDay['id'])) {
        Database::execute("DELETE FROM business_days WHERE id = :id", [':id' => $activeDay['id']]);
        echo "[CLEANUP] Cleaned up temporary test business day.\n";
    }
}

echo "\n====================================================================\n";
echo "TEST RESULTS: Passed: {$testsPassed} | Failed: {$testsFailed}\n";
echo "====================================================================\n";

if ($testsFailed > 0) {
    exit(1);
}
exit(0);
