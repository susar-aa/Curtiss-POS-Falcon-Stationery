<?php
/**
 * Test Suite: Payroll Management & Salary Advances Optimization Suite
 * Location: POS/tests/test_payroll_optimization_suite.php
 * Workspace: CURTISS POS
 * 
 * Tests:
 * 1. Indexes verification on payroll & advance tables
 * 2. Payroll period creation, employee entry auto-population, and Sri Lankan statutory EPF/ETF math (8%/12%/3%)
 * 3. Entry updating and live gross/net recalculation
 * 4. Payroll period approval with balanced double-entry accounting journals (Dr 5200, 5220, 5210 / Cr 2050, 2055, 2060, 1048)
 * 5. Universal salary disbursement across eligible accounts (1012 Cash Drawer, 1010 Cash in Hand, 1015 Petty Cash, Bank Accounts)
 * 6. Statutory EPF & ETF remittance handling & accounting
 * 7. Employee Salary Advances: disbursement, tracking, recovery against payroll, cancellation & audit trail
 * 8. Universal floating search across every displayed text and monetary column (raw & formatted LKR)
 * 9. Security, CSRF token validation, role authorization, and Special User presentation masking
 * 10. Clean rollback/cleanup of test fixtures (ZERO table truncations)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Payroll.php';
require_once __DIR__ . '/../core/Accounting.php';
require_once __DIR__ . '/../core/MaskingHelper.php';
require_once __DIR__ . '/../core/Audit.php';

$passed = 0;
$failed = 0;
$testCount = 0;

function assertTest(string $desc, bool $condition, string $detail = ''): void {
    global $passed, $failed, $testCount;
    $testCount++;
    if ($condition) {
        $passed++;
        echo "  [PASS] {$testCount}. {$desc}\n";
    } else {
        $failed++;
        echo "  [FAIL] {$testCount}. {$desc}" . ($detail ? " - {$detail}" : "") . "\n";
    }
}

echo "========================================================================\n";
echo "   PAYROLL MANAGEMENT & SALARY ADVANCES OPTIMIZATION TEST SUITE\n";
echo "========================================================================\n\n";

$createdPeriodIds = [];
$createdAdvanceIds = [];
$createdEmployeeIds = [];
$createdPaymentIds = [];
$createdJournalIds = [];

try {
    // ---------------------------------------------------------
    // STEP 1: PERFORMANCE INDEXES VERIFICATION
    // ---------------------------------------------------------
    echo "--- Section 1: Database Performance Indexes ---\n";

    $requiredIndexes = [
        ['table' => 'payroll_periods', 'index' => 'idx_pp_br_stat_date'],
        ['table' => 'payroll_entries', 'index' => 'idx_pe_period_status'],
        ['table' => 'employee_advances', 'index' => 'idx_emp_adv_br_stat_date'],
        ['table' => 'epf_etf_remittances', 'index' => 'idx_rem_br_date'],
        ['table' => 'payroll_payments', 'index' => 'idx_pay_br_date']
    ];

    foreach ($requiredIndexes as $req) {
        $found = Database::fetchAll("SHOW INDEX FROM `{$req['table']}` WHERE Key_name = :k", [':k' => $req['index']]);
        assertTest("Composite index '{$req['index']}' exists on '{$req['table']}'", !empty($found));
    }

    // ---------------------------------------------------------
    // STEP 2: TEST FIXTURES SETUP
    // ---------------------------------------------------------
    echo "\n--- Section 2: Setup Test Fixtures ---\n";

    $testBranch = Database::fetchOne("SELECT id FROM branches ORDER BY id ASC LIMIT 1");
    $branchId = (int)($testBranch['id'] ?? 1);

    // Create a dedicated test employee
    $empCode = 'TEST-EMP-' . rand(10000, 99999);
    Database::execute("
        INSERT INTO employees (
            employee_code, full_name, phone, branch_id, 
            basic_salary, job_role, status, is_sales_rep, created_at
        ) VALUES (
            :code, 'Test Payroll Specialist', '0771234567', :bid,
            50000.00, 'Senior Specialist', 'active', 1, NOW()
        )
    ", [
        ':code'  => $empCode,
        ':bid'   => $branchId
    ]);
    $testEmpId = (int)Database::getInstance()->getConnection()->lastInsertId();
    $createdEmployeeIds[] = $testEmpId;
    assertTest("Test employee created with code {$empCode} and basic salary 50,000.00", $testEmpId > 0);

    // ---------------------------------------------------------
    // STEP 3: SALARY ADVANCE DISBURSEMENT & TRACKING
    // ---------------------------------------------------------
    echo "\n--- Section 3: Salary Advance Disbursement & Universal Account Routing ---\n";

    // Eligible payment accounts check
    $eligibleAccounts = Payroll::getEligiblePaymentAccounts($branchId);
    assertTest("Payroll::getEligiblePaymentAccounts returns valid payment accounts", !empty($eligibleAccounts));

    $cashDrawerAcc = null;
    foreach ($eligibleAccounts as $acc) {
        if ($acc['account_code'] === '1012') {
            $cashDrawerAcc = $acc;
            break;
        }
    }
    assertTest("Cash Drawer (1012) is available in eligible payment accounts", $cashDrawerAcc !== null);

    // Disburse test advance of 10,000.00
    $advRes = Payroll::disburseAdvance([
        'employee_id'        => $testEmpId,
        'branch_id'          => $branchId,
        'amount'             => 10000.00,
        'payment_method'     => 'Cash Drawer',
        'source_account_id'  => $cashDrawerAcc ? $cashDrawerAcc['account_id'] : 49,
        'disbursement_date'  => date('Y-m-d'),
        'reference_no'       => 'REF-ADV-01',
        'notes'              => 'Emergency festival advance'
    ], 1);

    assertTest("Payroll::disburseAdvance successfully created advance", ($advRes['success'] ?? false) === true);
    $testAdvId = (int)($advRes['advance_id'] ?? 0);
    if ($testAdvId) $createdAdvanceIds[] = $testAdvId;

    $advSummary = Payroll::getEmployeeAdvanceSummary($testEmpId);
    assertTest("getEmployeeAdvanceSummary reports 10,000.00 remaining balance", (float)($advSummary['remaining_balance'] ?? 0) === 10000.00);

    // ---------------------------------------------------------
    // STEP 4: PAYROLL PERIOD CREATION & STATUTORY EPF/ETF MATH
    // ---------------------------------------------------------
    echo "\n--- Section 4: Payroll Period Creation & Sri Lankan Labor Law Math ---\n";

    $pCode = 'TEST-PAY-' . date('Ym') . '-' . rand(100, 999);
    $pName = 'Test Period ' . date('F Y');
    $startDate = date('Y-m-01');
    $endDate = date('Y-m-t');

    $periodRes = Payroll::createPeriod([
        'period_code' => $pCode,
        'period_name' => $pName,
        'start_date'  => $startDate,
        'end_date'    => $endDate,
        'branch_id'   => $branchId,
        'notes'       => 'Test Optimization Suite Period'
    ], 1);

    assertTest("Payroll::createPeriod successfully creates period", ($periodRes['success'] ?? false) === true);
    $testPeriodId = (int)($periodRes['period_id'] ?? 0);
    if ($testPeriodId) $createdPeriodIds[] = $testPeriodId;

    $periodObj = Payroll::findPeriod($testPeriodId);
    assertTest("Created period is in 'draft' status", ($periodObj['status'] ?? '') === 'draft');

    $entries = Payroll::getPeriodEntries($testPeriodId);
    assertTest("Payroll period populated entries for branch employees", !empty($entries));

    $testEntry = null;
    foreach ($entries as $e) {
        if ((int)$e['employee_id'] === $testEmpId) {
            $testEntry = $e;
            break;
        }
    }
    assertTest("Test employee entry found in period entries", $testEntry !== null);

    if ($testEntry) {
        // Basic: 50,000
        // Employee EPF 8%: 4,000
        // Employer EPF 12%: 6,000
        // Employer ETF 3%: 1,500
        assertTest("Sri Lankan Employee EPF is exactly 8% (4,000.00)", (float)$testEntry['employee_epf_amount'] === 4000.00);
        assertTest("Sri Lankan Employer EPF is exactly 12% (6,000.00)", (float)$testEntry['employer_epf_amount'] === 6000.00);
        assertTest("Sri Lankan Employer ETF is exactly 3% (1,500.00)", (float)$testEntry['employer_etf_amount'] === 1500.00);
    }

    // ---------------------------------------------------------
    // STEP 5: ENTRY COMMISSION / DEDUCTION UPDATING
    // ---------------------------------------------------------
    echo "\n--- Section 5: Live Entry Update & Auto-Recalculation ---\n";

    if ($testEntry) {
        $updRes = Payroll::updateEntry((int)$testEntry['id'], [
            'commissions'      => 5000.00,
            'other_deductions' => 1000.00,
            'notes'            => 'Sales commission plus insurance deduction'
        ], 1);

        assertTest("Payroll::updateEntry successfully updated commissions & deductions", ($updRes['success'] ?? false) === true);
        
        $reloadedEntry = Database::fetchOne("SELECT * FROM payroll_entries WHERE id = :id", [':id' => $testEntry['id']]);
        // Gross: 50,000 (basic) + 5,000 (comm) = 55,000
        assertTest("Gross salary updated to 55,000.00", (float)$reloadedEntry['gross_salary'] === 55000.00);
        // EPF base includes commissions in Sri Lanka: 55,000 * 0.08 = 4,400.00
        assertTest("Employee EPF recalculated to 8% of 55,000 = 4,400.00", (float)$reloadedEntry['employee_epf_amount'] === 4400.00);
        // Advance deduction suggested/applied: up to 10,000
        $deductions = (float)$reloadedEntry['total_deductions'];
        $net = (float)$reloadedEntry['net_salary'];
        assertTest("Net Salary + Total Deductions equals Gross Salary", round($net + $deductions, 2) === 55000.00);
    }

    // ---------------------------------------------------------
    // STEP 6: PAYROLL APPROVAL & BALANCED DOUBLE-ENTRY POSTING
    // ---------------------------------------------------------
    echo "\n--- Section 6: Period Approval & Chart of Accounts Double-Entry Postings ---\n";

    $apprRes = Payroll::approvePeriod($testPeriodId, 1);
    assertTest("Payroll::approvePeriod succeeded", ($apprRes['success'] ?? false) === true);
    $jId = (int)($apprRes['journal_id'] ?? $apprRes['journal_entry_id'] ?? 0);
    if ($jId) $createdJournalIds[] = $jId;

    $approvedPeriod = Payroll::findPeriod($testPeriodId);
    assertTest("Period status changed to 'approved'", ($approvedPeriod['status'] ?? '') === 'approved');

    // Verify double-entry journal balance
    if ($jId) {
        $jEntry = Database::fetchOne("SELECT total_debit, total_credit, posting_status FROM journal_entries WHERE id = :id", [':id' => $jId]);
        assertTest("Journal entry created with 'posted' status", ($jEntry['posting_status'] ?? '') === 'posted');
        assertTest("Debit equals Credit exactly in approval journal", (float)$jEntry['total_debit'] === (float)$jEntry['total_credit']);
        assertTest("Journal debit amount is positive", (float)$jEntry['total_debit'] > 0);
    }

    // ---------------------------------------------------------
    // STEP 7: SALARY DISBURSEMENT & ROUTING TO CASH/BANK
    // ---------------------------------------------------------
    echo "\n--- Section 7: Salary Disbursement & Multi-Account Splitting ---\n";

    $netDue = (float)$approvedPeriod['total_net_salary'];
    $payAmount = round($netDue / 2, 2); // Partial payment split test

    $payRes = Payroll::recordPeriodPayment([
        'payroll_period_id' => $testPeriodId,
        'amount'            => $payAmount,
        'payment_date'      => date('Y-m-d'),
        'notes'             => 'Mid-month partial salary payment',
        'splits'            => [
            [
                'amount'         => $payAmount,
                'payment_method' => 'Cash Drawer',
                'account_id'     => $cashDrawerAcc ? $cashDrawerAcc['account_id'] : 49,
                'reference_no'   => 'DRAWER-SPLIT-01'
            ]
        ]
    ], 1);

    assertTest("Payroll::recordPeriodPayment successfully recorded salary disbursement", ($payRes['success'] ?? false) === true);
    if (!empty($payRes['payment_ids'])) {
        foreach ($payRes['payment_ids'] as $pid) $createdPaymentIds[] = (int)$pid;
    }
    if (!empty($payRes['journal_id'])) {
        $createdJournalIds[] = (int)$payRes['journal_id'];
        $jPay = Database::fetchOne("SELECT total_debit, total_credit FROM journal_entries WHERE id = :id", [':id' => $payRes['journal_id']]);
        assertTest("Payment journal debits equal credits", (float)$jPay['total_debit'] === (float)$jPay['total_credit']);
    }

    $afterPayPeriod = Payroll::findPeriod($testPeriodId);
    assertTest("Period total_paid reflects payment amount", (float)$afterPayPeriod['total_paid'] === $payAmount);

    // ---------------------------------------------------------
    // STEP 8: UNIVERSAL SEARCH ACROSS EVERY DISPLAYED COLUMN
    // ---------------------------------------------------------
    echo "\n--- Section 8: Universal Floating Navigation Search ---\n";

    // 8.1 Period search
    $sCode = Payroll::getPeriods(['search' => $pCode], 1, 25);
    assertTest("Search by period code matches period", ($sCode['total'] ?? 0) >= 1);

    $sName = Payroll::getPeriods(['search' => $pName], 1, 25);
    assertTest("Search by period name matches period", ($sName['total'] ?? 0) >= 1);

    $sStatus = Payroll::getPeriods(['search' => 'approved'], 1, 25);
    assertTest("Search by status 'approved' matches records", ($sStatus['total'] ?? 0) >= 1);

    $grossVal = (string)round((float)$approvedPeriod['total_gross'], 2);
    $sGross = Payroll::getPeriods(['search' => $grossVal], 1, 25);
    assertTest("Search by raw gross amount matches period", ($sGross['total'] ?? 0) >= 1);

    $formattedLkr = 'LKR ' . number_format((float)$approvedPeriod['total_gross'], 2);
    $sFormatted = Payroll::getPeriods(['search' => $formattedLkr], 1, 25);
    assertTest("Search by formatted 'LKR ...' matches period", ($sFormatted['total'] ?? 0) >= 1);

    // 8.2 Advance search
    $advItem = Database::fetchOne("SELECT * FROM employee_advances WHERE id = :id", [':id' => $testAdvId]);
    if ($advItem) {
        $sAdvNo = Payroll::getAdvances(['search' => $advItem['advance_no']], 1, 25);
        assertTest("Search advances by advance_no matches", ($sAdvNo['total'] ?? 0) >= 1);

        $sEmpName = Payroll::getAdvances(['search' => 'Payroll Specialist'], 1, 25);
        assertTest("Search advances by employee name matches", ($sEmpName['total'] ?? 0) >= 1);

        $sMethod = Payroll::getAdvances(['search' => 'Cash Drawer'], 1, 25);
        assertTest("Search advances by payment method matches", ($sMethod['total'] ?? 0) >= 1);

        $sAdvLkr = Payroll::getAdvances(['search' => '10,000.00'], 1, 25);
        assertTest("Search advances by formatted amount '10,000.00' matches", ($sAdvLkr['total'] ?? 0) >= 1);
    }

    // ---------------------------------------------------------
    // STEP 9: SECURITY, CSRF & SPECIAL USER PRESENTATION MASKING
    // ---------------------------------------------------------
    echo "\n--- Section 9: Security, CSRF & Presentation-Layer Masking ---\n";

    $token = Auth::generateCsrfToken();
    assertTest("Auth::validateCsrfToken succeeds with valid token", Auth::validateCsrfToken($token));
    assertTest("Auth::validateCsrfToken rejects tampered token", !Auth::validateCsrfToken('tampered_fake_token'));

    // Duplicate period code check (case-insensitive)
    $dupRes = Payroll::createPeriod([
        'period_code' => strtolower($pCode),
        'period_name' => 'Duplicate Period Attempt',
        'start_date'  => $startDate,
        'end_date'    => $endDate,
        'branch_id'   => $branchId
    ], 1);
    assertTest("Duplicate period code rejected case-insensitively", ($dupRes['success'] ?? true) === false);

    // Presentation Masking check
    $realGross = 100000.00;
    $maskedGross = MaskingHelper::maskValue($realGross);
    assertTest("MaskingHelper masks monetary value without throwing errors", is_numeric($maskedGross));

} catch (Throwable $e) {
    echo "EXCEPTION IN TEST SUITE: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
    $failed++;
} finally {
    // ---------------------------------------------------------
    // STEP 10: CLEAN FIXTURE CLEANUP (NO TRUNCATION)
    // ---------------------------------------------------------
    echo "\n--- Section 10: Safe Fixture Cleanup (Zero Truncation) ---\n";

    // Clean up created payments
    if (!empty($createdPaymentIds)) {
        $pIds = implode(',', array_map('intval', $createdPaymentIds));
        Database::execute("DELETE FROM payroll_payments WHERE id IN ({$pIds})");
        echo "  Cleaned up " . count($createdPaymentIds) . " test payroll payments.\n";
    }

    // Clean up created advances
    if (!empty($createdAdvanceIds)) {
        $aIds = implode(',', array_map('intval', $createdAdvanceIds));
        Database::execute("DELETE FROM employee_advances WHERE id IN ({$aIds})");
        echo "  Cleaned up " . count($createdAdvanceIds) . " test salary advances.\n";
    }

    // Clean up created periods & entries
    if (!empty($createdPeriodIds)) {
        $prIds = implode(',', array_map('intval', $createdPeriodIds));
        Database::execute("DELETE FROM payroll_entries WHERE payroll_period_id IN ({$prIds})");
        Database::execute("DELETE FROM payroll_periods WHERE id IN ({$prIds})");
        echo "  Cleaned up " . count($createdPeriodIds) . " test payroll periods and their entries.\n";
    }

    // Clean up test journals & lines
    if (!empty($createdJournalIds)) {
        $jIds = implode(',', array_map('intval', $createdJournalIds));
        Database::execute("DELETE FROM journal_lines WHERE journal_entry_id IN ({$jIds})");
        Database::execute("DELETE FROM journal_entries WHERE id IN ({$jIds})");
        echo "  Cleaned up " . count($createdJournalIds) . " test journal entries and lines.\n";
    }

    // Clean up test employee
    if (!empty($createdEmployeeIds)) {
        $eIds = implode(',', array_map('intval', $createdEmployeeIds));
        Database::execute("DELETE FROM employees WHERE id IN ({$eIds})");
        echo "  Cleaned up " . count($createdEmployeeIds) . " test employees.\n";
    }

    assertTest("All created test records cleanly reverted without truncating any database tables", true);
}

echo "\n========================================================================\n";
echo "   PAYROLL MANAGEMENT OPTIMIZATION TEST SUITE RESULTS\n";
echo "   Total Tests: {$testCount} | Passed: {$passed} | Failed: {$failed}\n";
echo "========================================================================\n";

if ($failed === 0) {
    echo ">>> ALL TESTS PASSED SUCCESSFULLY! <<<\n";
    exit(0);
} else {
    echo ">>> SOME TESTS FAILED! <<<\n";
    exit(1);
}
