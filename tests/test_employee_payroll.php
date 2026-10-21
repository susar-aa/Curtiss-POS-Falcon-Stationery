<?php
/**
 * Curtiss POS - Comprehensive Employee Directory, Sales Rep & Payroll CLI Verification Suite
 * 
 * Verifies:
 * 1. COA Integration (1048, 2050, 2055, 2060, 5200, 5210, 5220)
 * 2. Employee Directory Model (CRUD, continuous EMP-XXXX code, status toggle, soft-delete & restore)
 * 3. POS Sales Rep Integration (listing, attribution, performance stats)
 * 4. Employee Salary Advance Disbursement & Double-Entry Journal (Dr 1048, Cr 1010/Bank)
 * 5. Monthly Payroll Period Auto-Loading & Sri Lankan Statutory EPF/ETF Calculations
 * 6. Period Approval & Complete Multi-Line Balanced Journal Entry Posting (Dr 5200, 5210, 5220 | Cr 2050, 2055, 2060, 1048)
 * 7. Net Salary Payment Disbursal & Journal Posting (Dr 2050, Cr 1010)
 * 8. EPF & ETF Statutory Remittances & Journal Posting (Dr 2055/2060, Cr Bank COA)
 * 9. Printable Payslip Generation
 * 10. Isolated Test Cleanup
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Audit.php';
require_once __DIR__ . '/../core/Accounting.php';
require_once __DIR__ . '/../core/Employee.php';
require_once __DIR__ . '/../core/Payroll.php';
require_once __DIR__ . '/../core/Sale.php';

$branchId = 1;
$userId = 1;

// Emulate admin session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id'] = $userId;
$_SESSION['branch_id'] = $branchId;
$_SESSION['role_id'] = 1;
$_SESSION['role_code'] = ROLE_SUPER_ADMIN;

echo "====================================================================\n";
echo "   CURTISS POS — EMPLOYEE DIRECTORY & PAYROLL COMPREHENSIVE CLI TEST\n";
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

try {
    // ----------------------------------------------------
    // TEST 1: COA Accounts & Permissions Verification
    // ----------------------------------------------------
    echo "--- TEST 1: COA Payroll Accounts & Permissions Verification ---\n";

    $requiredAccounts = [
        Accounting::CODE_EMPLOYEE_ADVANCES        => '1048',
        Accounting::CODE_SALARY_PAYABLE           => '2050',
        Accounting::CODE_EPF_PAYABLE              => '2055',
        Accounting::CODE_ETF_PAYABLE              => '2060',
        Accounting::CODE_SALARY_EXPENSE           => '5200',
        Accounting::CODE_EMPLOYER_EPF_ETF_EXPENSE => '5210',
        Accounting::CODE_COMMISSION_ALLOWANCE_EXPENSE => '5220',
    ];

    foreach ($requiredAccounts as $constVal => $code) {
        $coa = Accounting::getAccountByCode($code);
        assertTest("COA Account {$code} exists", !empty($coa), "ID: " . ($coa['id'] ?? 'none') . " Name: " . ($coa['account_name'] ?? 'none'));
    }

    $requiredPerms = ['employees.view', 'employees.manage', 'payroll.view', 'payroll.create', 'payroll.approve', 'payroll.pay', 'payroll.remit'];
    foreach ($requiredPerms as $p) {
        $perm = Database::fetchOne("SELECT id FROM permissions WHERE permission_code = :c", [':c' => $p]);
        assertTest("Permission {$p} exists", !empty($perm), "ID: " . ($perm['id'] ?? 'none'));
    }

    // ----------------------------------------------------
    // TEST 2: Employee Creation & Model Lifecycle
    // ----------------------------------------------------
    echo "\n--- TEST 2: Employee Creation & Model Lifecycle ---\n";

    // Clean up any stale test employee from previous test runs
    $oldEmp = Database::fetchOne("SELECT id FROM employees WHERE nic = '951234567V'");
    if ($oldEmp) {
        Database::execute("DELETE FROM epf_etf_remittances WHERE branch_id = :b", [':b' => $branchId]);
        Database::execute("DELETE FROM payroll_payments WHERE employee_id = :id", [':id' => $oldEmp['id']]);
        Database::execute("DELETE FROM employee_advances WHERE employee_id = :id", [':id' => $oldEmp['id']]);
        Database::execute("DELETE FROM payroll_entries WHERE employee_id = :id", [':id' => $oldEmp['id']]);
        Database::execute("DELETE FROM employees WHERE id = :id", [':id' => $oldEmp['id']]);
    }

    $empData = [
        'full_name'         => 'Kamal Perera',
        'nic'               => '951234567V',
        'phone'             => '0771234567',
        'whatsapp'          => '0771234567',
        'address'           => '123 Kandy Road, Kandy',
        'job_role'          => 'Sales Rep',
        'is_sales_rep'      => 1,
        'branch_id'         => $branchId,
        'basic_salary'      => 50000.00,
        'bank_name'         => 'Commercial Bank',
        'bank_account_no'   => '1234567890',
        'joining_date'      => date('Y-m-d')
    ];

    $createResult = Employee::create($empData);
    assertTest("Create Employee", $createResult['success'] === true, "ID: " . ($createResult['employee_id'] ?? 'none') . ", Code: " . ($createResult['employee_code'] ?? 'none'));

    $empId = (int)$createResult['employee_id'];
    $empCode = $createResult['employee_code'];

    // Verify code format EMP-XXXX
    assertTest("Employee Code Continuous Format", (bool)preg_match('/^EMP-\d{4}$/', $empCode), "Code is {$empCode}");

    // Fetch employee
    $emp = Employee::getById($empId);
    assertTest("Fetch Employee by ID", !empty($emp) && $emp['full_name'] === 'Kamal Perera', "Name: {$emp['full_name']}");

    // Status toggle
    $toggleDeactive = Employee::toggleStatus($empId);
    $empAfterDeactive = Employee::getById($empId);
    assertTest("Toggle Employee Status to Deactive", $toggleDeactive['success'] && $empAfterDeactive['status'] === 'inactive', "Status: {$empAfterDeactive['status']}");

    $toggleActive = Employee::toggleStatus($empId);
    $empAfterActive = Employee::getById($empId);
    assertTest("Toggle Employee Status back to Active", $toggleActive['success'] && $empAfterActive['status'] === 'active', "Status: {$empAfterActive['status']}");

    // ----------------------------------------------------
    // TEST 3: Sales Rep Listing & POS Attribution
    // ----------------------------------------------------
    echo "\n--- TEST 3: Sales Rep Listing & Performance Integration ---\n";

    $salesReps = Employee::getSalesReps($branchId);
    $foundInReps = false;
    foreach ($salesReps as $rep) {
        if ((int)$rep['id'] === $empId) {
            $foundInReps = true;
            break;
        }
    }
    assertTest("Employee listed as Active Sales Rep", $foundInReps, "Found in branch {$branchId} sales reps list");

    $salesPerf = Employee::getSalesPerformance($empId);
    assertTest("Sales Performance query executes cleanly", isset($salesPerf['total_invoices']), "Invoices: {$salesPerf['total_invoices']}, Volume: {$salesPerf['total_sales_volume']}");

    // Test POS Hold Invoice with Sales Rep Attribution
    $availLot = Database::fetchOne("SELECT id, product_id, selling_price FROM product_lots WHERE branch_id = :b AND remaining_quantity > 0 AND status = 'active' LIMIT 1", [':b' => $branchId]);
    if ($availLot) {
        $holdRes = Sale::holdInvoice([
            'sales_rep_id' => $empId,
            'customer_id'  => 1,
            'items'        => [[
                'product_id'     => $availLot['product_id'],
                'lot_id'         => $availLot['id'],
                'quantity'       => 1,
                'unit_price'     => $availLot['selling_price'],
                'original_price' => $availLot['selling_price']
            ]],
            'held_notes'   => 'CLI Test Held Sale'
        ], $userId, $branchId);

        assertTest("POS Invoice Hold with Sales Rep", $holdRes['success'] === true, "Invoice No: " . ($holdRes['invoice_no'] ?? 'none'));
        $heldList = Sale::getHeldInvoices($branchId);
        $foundHeld = false;
        foreach ($heldList as $hl) {
            if ((int)($hl['sales_rep_id'] ?? 0) === $empId && ($hl['sales_rep_name'] ?? '') === 'Kamal Perera') {
                $foundHeld = true;
                break;
            }
        }
        assertTest("Held Invoice returns Sales Rep Name & Code", $foundHeld);
        // Clean up held test invoice
        if (!empty($holdRes['sale_id'])) {
            Database::execute("DELETE FROM sale_items WHERE sale_id = :id", [':id' => $holdRes['sale_id']]);
            Database::execute("DELETE FROM sales WHERE id = :id", [':id' => $holdRes['sale_id']]);
        }
    }

    // ----------------------------------------------------
    // TEST 4: Employee Salary Advance & Double-Entry Journal
    // ----------------------------------------------------
    echo "\n--- TEST 4: Salary Advance Disbursement & Double-Entry ---\n";

    $advanceAmount = 5000.00;
    $advResult = Payroll::disburseAdvance([
        'employee_id'       => $empId,
        'branch_id'         => $branchId,
        'amount'            => $advanceAmount,
        'disbursement_date' => date('Y-m-d'),
        'payment_method'    => 'Cash',
        'notes'             => 'Mid-month emergency advance'
    ], $userId);

    assertTest("Disburse Salary Advance", $advResult['success'] === true, "Advance No: " . ($advResult['advance_no'] ?? 'none'));
    $advId = (int)$advResult['advance_id'];

    // Verify Active Advance Balance
    $activeAdvances = Payroll::getActiveAdvances($empId);
    assertTest("Active Advances for Employee", count($activeAdvances) === 1 && (float)$activeAdvances[0]['remaining_amount'] === 5000.00, "Remaining: " . $activeAdvances[0]['remaining_amount']);

    // Verify Advance Journal Entry
    $advJournal = Database::fetchOne("
        SELECT id, entry_number, total_debit, total_credit, posting_status
        FROM journal_entries
        WHERE source_type = 'employee_advance' AND source_id = :id
    ", [':id' => $advId]);

    $isAdvBalanced = !empty($advJournal) && (float)$advJournal['total_debit'] === (float)$advJournal['total_credit'] && $advJournal['posting_status'] === 'posted';
    assertTest("Advance Journal Entry exists & balanced", $isAdvBalanced && (float)$advJournal['total_debit'] === 5000.00, "JE #: " . ($advJournal['entry_number'] ?? 'none') . " Dr: " . ($advJournal['total_debit'] ?? 0));

    // ----------------------------------------------------
    // TEST 5: Payroll Period Creation & Automated Auto-Loading
    // ----------------------------------------------------
    echo "\n--- TEST 5: Payroll Period Creation & Auto-Loading ---\n";

    $testYear = (int)date('Y');
    $testMonth = (int)date('m');
    $periodCode = sprintf('%04d-%02d', $testYear, $testMonth);
    $periodName = date('F Y');

    // Clean any pre-existing test period for this month in branch 1
    $existingPeriod = Database::fetchOne("SELECT id FROM payroll_periods WHERE branch_id = :b AND period_code = :pc", [
        ':b' => $branchId,
        ':pc' => $periodCode
    ]);
    if ($existingPeriod) {
        Database::execute("DELETE FROM payroll_periods WHERE id = :id", [':id' => $existingPeriod['id']]);
    }

    $periodResult = Payroll::createPeriod([
        'period_code'   => $periodCode,
        'period_name'   => $periodName,
        'start_date'    => date('Y-m-01'),
        'end_date'      => date('Y-m-t'),
        'branch_id'     => $branchId,
        'notes'         => 'Automated CLI Test Payroll Period'
    ], $userId);

    assertTest("Create Payroll Period", $periodResult['success'] === true, "Period ID: " . ($periodResult['period_id'] ?? 'none') . ", Period No: " . ($periodResult['period_code'] ?? 'none'));
    $periodId = (int)$periodResult['period_id'];

    // Check that our employee was auto-loaded
    $periodEntries = Payroll::getPeriodEntries($periodId);
    $empEntry = null;
    foreach ($periodEntries as $entry) {
        if ((int)$entry['employee_id'] === $empId) {
            $empEntry = $entry;
            break;
        }
    }
    assertTest("Employee auto-loaded into Period Entries", !empty($empEntry), "Entry ID: " . ($empEntry['id'] ?? 'none'));
    assertTest("Auto-loaded Basic Salary & Advance Deduction", (float)$empEntry['basic_salary'] === 50000.00 && (float)$empEntry['salary_advances_deducted'] === 5000.00, "Basic: {$empEntry['basic_salary']}, Advance: {$empEntry['salary_advances_deducted']}");

    $entryId = (int)$empEntry['id'];

    // ----------------------------------------------------
    // TEST 6: Sri Lankan Statutory EPF & ETF Formulas
    // ----------------------------------------------------
    echo "\n--- TEST 6: Sri Lankan Statutory EPF/ETF Calculations ---\n";

    // Update entry with overtime and bonus
    $updateEntryResult = Payroll::updateEntry($entryId, [
        'allowances'                => 3000.00,
        'bonuses'                   => 2000.00,
        'overtime_amount'           => 1000.00,
        'salary_advances_deducted'  => 5000.00,
        'other_deductions'          => 0.00,
        'notes'                     => 'Performance bonus + 10h overtime'
    ], $userId);

    assertTest("Update Entry with Allowances & Overtime", $updateEntryResult['success'] === true);

    $updatedEntry = Database::fetchOne("SELECT * FROM payroll_entries WHERE id = :id", [':id' => $entryId]);

    // Statutory verification:
    // Basic = 50,000, Allowances = 3,000, Bonuses = 2,000, Overtime = 1,000
    // Gross = 56,000
    // Employee EPF (8% of 50,000) = 4,000.00
    // Employer EPF (12% of 50,000) = 6,000.00
    // Employer ETF (3% of 50,000) = 1,500.00
    // Total Deductions = 4,000 + 5,000 (Advance) = 9,000.00
    // Net Salary = 56,000 - 9,000 = 47,000.00
    assertTest("Gross Salary = 56,000.00", (float)$updatedEntry['gross_salary'] === 56000.00, "Actual: {$updatedEntry['gross_salary']}");
    assertTest("Employee EPF (8%) = 4,000.00", (float)$updatedEntry['employee_epf_amount'] === 4000.00, "Actual: {$updatedEntry['employee_epf_amount']}");
    assertTest("Employer EPF (12%) = 6,000.00", (float)$updatedEntry['employer_epf_amount'] === 6000.00, "Actual: {$updatedEntry['employer_epf_amount']}");
    assertTest("Employer ETF (3%) = 1,500.00", (float)$updatedEntry['employer_etf_amount'] === 1500.00, "Actual: {$updatedEntry['employer_etf_amount']}");
    assertTest("Total Deductions = 9,000.00", (float)$updatedEntry['total_deductions'] === 9000.00, "Actual: {$updatedEntry['total_deductions']}");
    assertTest("Net Salary = 47,000.00", (float)$updatedEntry['net_salary'] === 47000.00, "Actual: {$updatedEntry['net_salary']}");

    // ----------------------------------------------------
    // TEST 7: Period Approval & Double-Entry Journal Check
    // ----------------------------------------------------
    echo "\n--- TEST 7: Payroll Period Approval & Double-Entry Equality ---\n";

    $approveResult = Payroll::approvePeriod($periodId, $userId);
    assertTest("Approve Payroll Period", $approveResult['success'] === true, ($approveResult['message'] ?? 'none') . " JE ID: " . ($approveResult['journal_entry_id'] ?? 'none'));

    $periodAfterApproval = Payroll::getPeriodById($periodId);
    assertTest("Period Status is 'approved'", $periodAfterApproval['status'] === 'approved');

    // Verify posted journal entry
    $jeId = (int)$approveResult['journal_entry_id'];
    $journal = Database::fetchOne("SELECT * FROM journal_entries WHERE id = :id", [':id' => $jeId]);
    $isJeBalanced = !empty($journal) && (float)$journal['total_debit'] === (float)$journal['total_credit'] && $journal['posting_status'] === 'posted';
    assertTest("Journal Entry is Balanced", $isJeBalanced, "Debit: {$journal['total_debit']} Credit: {$journal['total_credit']}");

    // Check exact Double Entry math:
    // Debits:
    // Dr 5200 (Salaries Expense): 50,000.00
    // Dr 5220 (Allowances/Commissions): 6,000.00 (3000 + 2000 + 1000)
    // Dr 5210 (Employer EPF/ETF): 7,500.00 (6000 + 1500)
    // Total Debits = 63,500.00
    // Credits:
    // Cr 2050 (Salaries Payable): 47,000.00
    // Cr 2055 (EPF Payable): 10,000.00 (4000 + 6000)
    // Cr 2060 (ETF Payable): 1,500.00
    // Cr 1048 (Advances Recovered): 5,000.00
    // Total Credits = 63,500.00
    $expectedTotal = 63500.00;
    assertTest("Debits Equal Credits Exactly (63,500.00)", (float)$journal['total_debit'] === $expectedTotal && (float)$journal['total_credit'] === $expectedTotal, "Total Debit: {$journal['total_debit']}, Total Credit: {$journal['total_credit']}");

    $lines = Database::fetchAll("
        SELECT jl.*, ca.account_code, ca.account_name
        FROM journal_lines jl
        INNER JOIN chart_of_accounts ca ON jl.account_id = ca.id
        WHERE jl.journal_entry_id = :je
        ORDER BY jl.debit DESC, jl.credit DESC
    ", [':je' => $jeId]);

    echo "   Posting lines verified:\n";
    foreach ($lines as $ln) {
        $type = (float)$ln['debit'] > 0 ? 'Dr' : 'Cr';
        $amt = (float)$ln['debit'] > 0 ? $ln['debit'] : $ln['credit'];
        echo "   -> {$type} [{$ln['account_code']}] {$ln['account_name']}: " . number_format($amt, 2) . "\n";
    }

    // ----------------------------------------------------
    // TEST 8: Net Salary Payment Disbursal
    // ----------------------------------------------------
    echo "\n--- TEST 8: Net Salary Payment Disbursal & Journal Posting ---\n";

    $netPay = 47000.00;
    $payResult = Payroll::recordPayment([
        'payroll_entry_id'  => $entryId,
        'amount'            => $netPay,
        'payment_method'    => 'Cash',
        'payment_date'      => date('Y-m-d'),
        'reference_no'      => 'VOUCHER-001',
        'notes'             => 'October 2026 Salary Payment via Cash'
    ], $userId);

    assertTest("Record Net Salary Payment", $payResult['success'] === true, "Payment No: " . ($payResult['payment_no'] ?? 'none'));
    $payId = (int)$payResult['payment_id'];

    // Check entry status is 'paid'
    $entryAfterPay = Database::fetchOne("SELECT * FROM payroll_entries WHERE id = :id", [':id' => $entryId]);
    assertTest("Payroll Entry Status is 'paid'", $entryAfterPay['status'] === 'paid', "Paid Amount: {$entryAfterPay['paid_amount']}");

    // Check Payment Journal Entry: Dr 2050 (47,000) / Cr 1010 (47,000)
    $payJournal = Database::fetchOne("
        SELECT * FROM journal_entries WHERE source_type = 'payroll_payment' AND source_id = :id
    ", [':id' => $payId]);
    $isPayBalanced = !empty($payJournal) && (float)$payJournal['total_debit'] === (float)$payJournal['total_credit'] && $payJournal['posting_status'] === 'posted';
    assertTest("Salary Payment Journal exists & balanced", $isPayBalanced && (float)$payJournal['total_debit'] === $netPay, "Dr: " . ($payJournal['total_debit'] ?? 0) . " Cr: " . ($payJournal['total_credit'] ?? 0));

    // ----------------------------------------------------
    // TEST 9: Statutory EPF & ETF Remittances
    // ----------------------------------------------------
    echo "\n--- TEST 9: Statutory EPF & ETF Remittance Journals ---\n";

    // Find active bank account for remittance
    $bankAcc = Database::fetchOne("SELECT id FROM bank_accounts WHERE status = 'active' LIMIT 1");
    $bankAccId = $bankAcc ? (int)$bankAcc['id'] : null;

    // Remit EPF: 10,000.00
    $epfRemit = Payroll::remitContributions([
        'payroll_period_id' => $periodId,
        'remittance_type'   => 'EPF',
        'epf_amount'        => 10000.00,
        'etf_amount'        => 0.00,
        'payment_date'      => date('Y-m-d'),
        'bank_account_id'   => $bankAccId,
        'reference_no'      => 'CBSL-EPF-TXN-001',
        'notes'             => 'CBSL EPF Remittance for Oct 2026'
    ], $userId);

    assertTest("Remit EPF Contributions", $epfRemit['success'] === true, "Remittance No: " . ($epfRemit['remittance_no'] ?? 'none'));

    // Remit ETF: 1,500.00
    $etfRemit = Payroll::remitContributions([
        'payroll_period_id' => $periodId,
        'remittance_type'   => 'ETF',
        'epf_amount'        => 0.00,
        'etf_amount'        => 1500.00,
        'payment_date'      => date('Y-m-d'),
        'bank_account_id'   => $bankAccId,
        'reference_no'      => 'ETFB-REMIT-001',
        'notes'             => 'ETF Board Remittance for Oct 2026'
    ], $userId);

    assertTest("Remit ETF Contributions", $etfRemit['success'] === true, "Remittance No: " . ($etfRemit['remittance_no'] ?? 'none'));

    // Check Remittance Journals
    $epfJournal = Database::fetchOne("SELECT * FROM journal_entries WHERE source_type = 'epf_etf_remittance' AND source_id = :id", [':id' => $epfRemit['remittance_id']]);
    $isEpfBalanced = !empty($epfJournal) && (float)$epfJournal['total_debit'] === (float)$epfJournal['total_credit'] && $epfJournal['posting_status'] === 'posted';
    assertTest("EPF Remittance Journal Balanced (10,000.00)", $isEpfBalanced && (float)$epfJournal['total_debit'] === 10000.00);

    $etfJournal = Database::fetchOne("SELECT * FROM journal_entries WHERE source_type = 'epf_etf_remittance' AND source_id = :id", [':id' => $etfRemit['remittance_id']]);
    $isEtfBalanced = !empty($etfJournal) && (float)$etfJournal['total_debit'] === (float)$etfJournal['total_credit'] && $etfJournal['posting_status'] === 'posted';
    assertTest("ETF Remittance Journal Balanced (1,500.00)", $isEtfBalanced && (float)$etfJournal['total_debit'] === 1500.00);

    // ----------------------------------------------------
    // TEST 10: Payslip Generation
    // ----------------------------------------------------
    echo "\n--- TEST 10: Printable Payslip Generation ---\n";

    $payslip = Payroll::generatePayslip($entryId);
    assertTest("Payslip Generated Successfully", !empty($payslip['full_name']) && (float)$payslip['net_salary'] === 47000.00, "Employee: {$payslip['full_name']}, Net: {$payslip['net_salary']}");

    // ----------------------------------------------------
    // TEST 11: Soft-Delete & Restore Lifecycle
    // ----------------------------------------------------
    echo "\n--- TEST 11: Employee Soft-Delete & Restore ---\n";

    // Test soft delete on employee
    $delResult = Employee::delete($empId, $userId);
    assertTest("Soft-Delete Employee", $delResult['success'] === true);

    $empDeleted = Database::fetchOne("SELECT deleted_at FROM employees WHERE id = :id", [':id' => $empId]);
    assertTest("Employee has deleted_at timestamp set", !empty($empDeleted['deleted_at']));

    $restoreResult = Employee::restore($empId, $userId);
    assertTest("Restore Employee from Trash", $restoreResult['success'] === true);

    $empRestored = Database::fetchOne("SELECT deleted_at FROM employees WHERE id = :id", [':id' => $empId]);
    assertTest("Employee deleted_at is NULL after restore", is_null($empRestored['deleted_at']));

    // ----------------------------------------------------
    // CLEANUP: Isolated Test Data Cleanup
    // ----------------------------------------------------
    echo "\n--- CLEANUP: Removing isolated test records ---\n";

    // Delete created remittances
    Database::execute("DELETE FROM epf_etf_remittances WHERE id IN (:e1, :e2)", [':e1' => $epfRemit['remittance_id'], ':e2' => $etfRemit['remittance_id']]);
    // Delete payments
    Database::execute("DELETE FROM payroll_payments WHERE id = :id", [':id' => $payId]);
    // Delete advance
    Database::execute("DELETE FROM employee_advances WHERE id = :id", [':id' => $advId]);
    // Delete period & entries (cascaded)
    Database::execute("DELETE FROM payroll_periods WHERE id = :id", [':id' => $periodId]);
    // Delete employee
    Database::execute("DELETE FROM employees WHERE id = :id", [':id' => $empId]);

    // Delete isolated journal lines and entries
    if (!empty($periodResult['journal_entry_id'])) {
        Database::execute("DELETE FROM journal_lines WHERE journal_entry_id = :je_id", [':je_id' => $periodResult['journal_entry_id']]);
        Database::execute("DELETE FROM journal_entries WHERE id = :je_id", [':je_id' => $periodResult['journal_entry_id']]);
    }
    Database::execute("DELETE jl FROM journal_lines jl JOIN journal_entries je ON jl.journal_entry_id = je.id WHERE je.source_type IN ('employee_advance', 'payroll', 'payroll_period', 'payroll_payment', 'epf_etf_remittance') AND je.branch_id = :b", [
        ':b' => $branchId
    ]);
    Database::execute("DELETE FROM journal_entries WHERE source_type IN ('employee_advance', 'payroll', 'payroll_period', 'payroll_payment', 'epf_etf_remittance') AND branch_id = :b", [
        ':b' => $branchId
    ]);

    echo "Cleanup complete: Isolated test artifacts removed safely.\n";

} catch (Throwable $e) {
    echo "\n[EXCEPTION] " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    $testsFailed++;
}

echo "\n====================================================================\n";
echo "   CLI TEST SUMMARY: {$testsPassed} PASSED, {$testsFailed} FAILED\n";
echo "====================================================================\n";

exit($testsFailed === 0 ? 0 : 1);
