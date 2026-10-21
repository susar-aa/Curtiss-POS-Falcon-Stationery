<?php
/**
 * Automated Verification Suite for Employee Directory
 * Tests compliance with CURTISS_MASTER_RULES.md:
 * - Database schema & composite index verification
 * - Code generation format & continuous sequence
 * - Employee CRUD & case-insensitive uniqueness validation
 * - In-cell clickable status badge toggling
 * - Soft-delete Trash Bin & Restore lifecycle (NO table truncation)
 * - Server-side pagination & whitelisted limits [25, 50, 75, 100, 500]
 * - Universal search across every displayed data column (code, name, phone, WA, NIC, role, branch, status, salary)
 * - Special User presentation masking
 * - Sales rep attribution & performance queries
 * - UI/UX standards (48px rows, sticky headers, zebra stripes, headline only, floating header controls)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Employee.php';
require_once __DIR__ . '/../core/MaskingHelper.php';
require_once __DIR__ . '/../core/Audit.php';

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

function assertTest($description, $condition) {
    global $totalTests, $passedTests, $failedTests;
    $totalTests++;
    if ($condition) {
        $passedTests++;
        echo "  [PASS] {$description}\n";
    } else {
        $failedTests++;
        echo "  [FAIL] {$description}\n";
    }
}

echo "========================================================================\n";
echo "   CURTISS POS - EMPLOYEE DIRECTORY OPTIMIZATION VERIFICATION SUITE     \n";
echo "========================================================================\n\n";

// -----------------------------------------------------------------------------
// 1. DATABASE COMPOSITE INDEX VERIFICATION
// -----------------------------------------------------------------------------
echo "1. DATABASE INDEX VERIFICATION ON EMPLOYEES TABLE\n";

$indexes = Database::fetchAll("SHOW INDEX FROM employees");
$idxNames = array_column($indexes, 'Key_name');

assertTest("Composite index idx_emp_del_branch_id exists on employees", in_array('idx_emp_del_branch_id', $idxNames));
assertTest("Composite index idx_emp_del_stat_id exists on employees", in_array('idx_emp_del_stat_id', $idxNames));
assertTest("Search index idx_emp_name exists on employees", in_array('idx_emp_name', $idxNames));
assertTest("Search index idx_emp_phone exists on employees", in_array('idx_emp_phone', $idxNames));
assertTest("Search index idx_emp_nic exists on employees", in_array('idx_emp_nic', $idxNames));

// -----------------------------------------------------------------------------
// 2. EMPLOYEE CODE GENERATION
// -----------------------------------------------------------------------------
echo "\n2. EMPLOYEE CODE GENERATION & SEQUENCING\n";

$generatedCode = Employee::generateEmployeeCode();
assertTest("generateEmployeeCode returns non-empty string", !empty($generatedCode));
assertTest("generateEmployeeCode matches 'EMP-xxxx' format", (bool)preg_match('/^EMP-\d{4,}$/', $generatedCode));

// -----------------------------------------------------------------------------
// 3. EMPLOYEE CRUD & VALIDATION
// -----------------------------------------------------------------------------
echo "\n3. EMPLOYEE CREATION, VALIDATION & PROFILE UPDATES\n";

$testBranch = Database::fetchOne("SELECT id FROM branches WHERE status = 'active' ORDER BY id ASC LIMIT 1");
$testBranchId = (int)($testBranch['id'] ?? 1);

$uniqueSuffix = time() . rand(100, 999);
$testCode = 'EMP-T' . substr($uniqueSuffix, -4);
$testNic = '99' . rand(1000000, 9999999) . 'V';
$testName = "Test Employee {$uniqueSuffix}";
$testPhone = "077" . rand(1000000, 9999999);
$testSalary = 65432.50;

// Test Creation
$createRes = Employee::create([
    'employee_code'     => $testCode,
    'full_name'         => $testName,
    'phone'             => $testPhone,
    'whatsapp'          => $testPhone,
    'nic'               => $testNic,
    'job_role'          => 'Sales Rep',
    'is_sales_rep'      => 1,
    'branch_id'         => $testBranchId,
    'joining_date'      => date('Y-m-d'),
    'basic_salary'      => $testSalary,
    'bank_name'         => 'Commercial Bank',
    'bank_account_name' => $testName,
    'bank_account_no'   => '1234567890',
    'address'           => '123 Test Street, Colombo',
    'notes'             => 'Test automated fixture'
]);

assertTest("Employee::create succeeds with valid payload", !empty($createRes['success']) && !empty($createRes['employee_id']));
$testEmpId = (int)($createRes['employee_id'] ?? 0);

// Verify record in DB
$empRecord = Employee::find($testEmpId, false);
assertTest("Created employee is found in DB", $empRecord !== null);
assertTest("Employee code matches created code", ($empRecord['employee_code'] ?? '') === $testCode);
assertTest("Employee status defaults to 'active'", ($empRecord['status'] ?? '') === 'active');
assertTest("Basic salary matches exact value", (float)($empRecord['basic_salary'] ?? 0) === $testSalary);
assertTest("is_sales_rep is set to 1", (int)($empRecord['is_sales_rep'] ?? 0) === 1);

// Test Duplicate Code Rejection
$dupCodeRes = Employee::create([
    'employee_code' => $testCode,
    'full_name'     => "Duplicate Code Emp",
    'branch_id'     => $testBranchId
]);
assertTest("Employee::create rejects duplicate employee_code", empty($dupCodeRes['success']));

// Test Duplicate NIC Rejection
$dupNicRes = Employee::create([
    'employee_code' => 'EMP-D' . rand(1000, 9999),
    'full_name'     => "Duplicate NIC Emp",
    'nic'           => $testNic,
    'branch_id'     => $testBranchId
]);
assertTest("Employee::create rejects duplicate NIC", empty($dupNicRes['success']));

// Test Profile Update
$updatedName = "Updated {$testName}";
$updatedSalary = 75000.00;
$updateRes = Employee::update($testEmpId, [
    'full_name'    => $updatedName,
    'basic_salary' => $updatedSalary,
    'job_role'     => 'Branch Manager',
    'is_sales_rep' => 0
]);
assertTest("Employee::update succeeds with valid data", !empty($updateRes['success']));

$reloadedEmp = Employee::find($testEmpId, false);
assertTest("Updated full_name persists in database", ($reloadedEmp['full_name'] ?? '') === $updatedName);
assertTest("Updated basic_salary persists in database", (float)($reloadedEmp['basic_salary'] ?? 0) === $updatedSalary);
assertTest("Updated job_role persists in database", ($reloadedEmp['job_role'] ?? '') === 'Branch Manager');

// -----------------------------------------------------------------------------
// 4. STATUS TOGGLE (ACTIVE <-> INACTIVE)
// -----------------------------------------------------------------------------
echo "\n4. INTERACTIVE THEME-MATCHED STATUS TOGGLING\n";

// Toggle to inactive
$toggleRes1 = Employee::toggleStatus($testEmpId);
assertTest("Employee::toggleStatus to inactive succeeds", !empty($toggleRes1['success']) && ($toggleRes1['new_status'] ?? '') === 'inactive');

$toggledEmp1 = Employee::find($testEmpId, false);
assertTest("Database reflects 'inactive' status", ($toggledEmp1['status'] ?? '') === 'inactive');

// Toggle back to active
$toggleRes2 = Employee::toggleStatus($testEmpId);
assertTest("Employee::toggleStatus back to active succeeds", !empty($toggleRes2['success']) && ($toggleRes2['new_status'] ?? '') === 'active');

$toggledEmp2 = Employee::find($testEmpId, false);
assertTest("Database reflects 'active' status", ($toggledEmp2['status'] ?? '') === 'active');

// -----------------------------------------------------------------------------
// 5. SOFT-DELETE TRASH BIN & RESTORE LIFECYCLE
// -----------------------------------------------------------------------------
echo "\n5. SOFT-DELETE TRASH BIN & RESTORE LIFECYCLE\n";

// Soft Delete
$delRes = Employee::delete($testEmpId);
assertTest("Employee::delete soft-deletes employee record", !empty($delRes['success']));

// Record retained in DB with deleted_at populated
$rawRecord = Database::fetchOne("SELECT id, status, deleted_at FROM employees WHERE id = :id", [':id' => $testEmpId]);
assertTest("Employee record still exists in DB (no hard-deletion)", $rawRecord !== null);
assertTest("deleted_at timestamp is populated", !empty($rawRecord['deleted_at']));
assertTest("Status is set to 'inactive' upon soft delete", ($rawRecord['status'] ?? '') === 'inactive');

// Excluded from active directory
$activeList = Employee::all(['view' => 'active', 'search' => $testCode]);
assertTest("Soft-deleted employee excluded from active directory view", empty($activeList['items']));

// Present in Trash Bin view
$trashList = Employee::all(['view' => 'deleted', 'search' => $testCode]);
assertTest("Soft-deleted employee present in Trash Bin view", !empty($trashList['items']));

// Restore
$restoreRes = Employee::restore($testEmpId);
assertTest("Employee::restore restores employee from Trash Bin", !empty($restoreRes['success']));

$restoredRecord = Database::fetchOne("SELECT id, status, deleted_at FROM employees WHERE id = :id", [':id' => $testEmpId]);
assertTest("Restored employee deleted_at is reset to NULL", empty($restoredRecord['deleted_at']));
assertTest("Restored employee status is set back to 'active'", ($restoredRecord['status'] ?? '') === 'active');

$restoredList = Employee::all(['view' => 'active', 'search' => $testCode]);
assertTest("Restored employee appears in active directory view", !empty($restoredList['items']));

// -----------------------------------------------------------------------------
// 6. SERVER-SIDE PAGINATION & LIMITS
// -----------------------------------------------------------------------------
echo "\n6. SERVER-SIDE PAGINATION & LIMIT WHITELISTING\n";

foreach ([25, 50, 75, 100, 500] as $lim) {
    $pRes = Employee::all(['view' => 'active'], 1, $lim);
    assertTest("Employee::all executes with limit {$lim}", $pRes['limit'] === $lim && is_array($pRes['items']));
}

// -----------------------------------------------------------------------------
// 7. UNIVERSAL SEARCH ACROSS EVERY DISPLAYED COLUMN
// -----------------------------------------------------------------------------
echo "\n7. UNIVERSAL REALTIME FLOATING SEARCH VERIFICATION\n";

// 1. Search by Employee Code
$s1 = Employee::all(['search' => $testCode]);
assertTest("Search by employee code ({$testCode}) matches record", !empty($s1['items']) && $s1['items'][0]['id'] == $testEmpId);

// 2. Search by Full Name
$s2 = Employee::all(['search' => $updatedName]);
assertTest("Search by full name matches record", !empty($s2['items']) && $s2['items'][0]['id'] == $testEmpId);

// 3. Search by Phone
$s3 = Employee::all(['search' => $testPhone]);
assertTest("Search by phone number matches record", !empty($s3['items']) && $s3['items'][0]['id'] == $testEmpId);

// 4. Search by NIC
$s4 = Employee::all(['search' => $testNic]);
assertTest("Search by NIC number matches record", !empty($s4['items']) && $s4['items'][0]['id'] == $testEmpId);

// 5. Search by Job Role
$s5 = Employee::all(['search' => 'Branch Manager']);
assertTest("Search by job role ('Branch Manager') returns matching records", !empty($s5['items']));

// 6. Search by Branch Name
$branchName = $empRecord['branch_name'] ?? 'Main';
$s6 = Employee::all(['search' => $branchName]);
assertTest("Search by assigned branch name ('{$branchName}') returns results", !empty($s6['items']));

// 7. Search by Status
$s7 = Employee::all(['search' => 'active']);
assertTest("Search by status ('active') returns results", !empty($s7['items']));

// 8. Search by Raw Numeric Basic Salary (75000)
$s8 = Employee::all(['search' => '75000']);
assertTest("Search by raw integer salary ('75000') matches test record", !empty($s8['items']) && in_array($testEmpId, array_column($s8['items'], 'id')));

// 9. Search by Formatted Currency String ('75,000.00')
$s9 = Employee::all(['search' => '75,000.00']);
assertTest("Search by formatted currency ('75,000.00') matches test record", !empty($s9['items']) && in_array($testEmpId, array_column($s9['items'], 'id')));

// 10. Search by LKR Currency String ('LKR 75,000.00')
$s10 = Employee::all(['search' => 'LKR 75,000.00']);
assertTest("Search by currency prefix string ('LKR 75,000.00') matches test record", !empty($s10['items']) && in_array($testEmpId, array_column($s10['items'], 'id')));

// -----------------------------------------------------------------------------
// 8. SPECIAL USER PRESENTATION-LAYER MASKING
// -----------------------------------------------------------------------------
echo "\n8. SPECIAL USER PRESENTATION-LAYER MASKING\n";

$factor = MaskingHelper::getFactor();
assertTest("MaskingHelper::getFactor returns valid percentage factor", $factor > 0.0 && $factor <= 1.0);

$expectedMaskedSalary = round($updatedSalary * $factor, 2);
$maskedSalary = MaskingHelper::maskValue($updatedSalary, true);
assertTest("MaskingHelper masks salary accurately ({$maskedSalary} vs {$expectedMaskedSalary})", abs($maskedSalary - $expectedMaskedSalary) <= 0.05);

// Verify DB record is completely unaltered
$dbCheck = Database::fetchOne("SELECT basic_salary FROM employees WHERE id = :id", [':id' => $testEmpId]);
assertTest("Underlying database salary is 100% unaltered", (float)($dbCheck['basic_salary'] ?? 0) === $updatedSalary);

// -----------------------------------------------------------------------------
// 9. SALES REP LOOKUP & PERFORMANCE
// -----------------------------------------------------------------------------
echo "\n9. SALES REP ATTRIBUTION & PERFORMANCE\n";

$reps = Employee::getSalesReps($testBranchId);
assertTest("Employee::getSalesReps returns array", is_array($reps));

$perf = Employee::getSalesPerformance($testEmpId);
assertTest("Employee::getSalesPerformance returns performance array", is_array($perf));
assertTest("Employee::getSalesPerformance has total_sales_volume key", array_key_exists('total_sales_volume', $perf));
assertTest("Employee::getSalesPerformance has total_invoices key", array_key_exists('total_invoices', $perf));

// -----------------------------------------------------------------------------
// 10. UI DESIGN & MASTER RULES COMPLIANCE IN VIEWS/EMPLOYEES/INDEX.PHP
// -----------------------------------------------------------------------------
echo "\n10. UI/UX DESIGN STANDARDS COMPLIANCE AUDIT\n";

$viewContent = file_get_contents(__DIR__ . '/../views/employees/index.php');

assertTest("CSRF meta tag present in view", strpos($viewContent, '<meta name="csrf-token"') !== false);
assertTest("CurtissSelect CSS and JS included", strpos($viewContent, 'curtiss_select.css') !== false && strpos($viewContent, 'curtiss_select.js') !== false);
assertTest("Fixed viewport height style calc(100vh - 110px) enforced", strpos($viewContent, 'calc(100vh - 110px)') !== false);
assertTest("Internal table scroll container .prod-table-scroll implemented", strpos($viewContent, 'prod-table-scroll') !== false);
assertTest("Sticky header rules on prod-table thead th", strpos($viewContent, 'position: sticky;') !== false && strpos($viewContent, 'top: 0;') !== false);
assertTest("Standard 48px table row height rule on tbody tr", strpos($viewContent, '.prod-table tbody tr {') !== false && strpos($viewContent, 'height: 48px;') !== false);
assertTest("Alternating zebra striping with 0.57 opacity", strpos($viewContent, 'rgba(248, 250, 252, 0.57)') !== false);
assertTest("Action controls mounted in #curtiss-floating-header-right", strpos($viewContent, 'id="curtiss-floating-header-right"') !== false);
assertTest("Trash Bin button features soft red styling (#dc2626 / #fef2f2)", strpos($viewContent, '#dc2626') !== false && strpos($viewContent, '#fef2f2') !== false);
assertTest("In-cell status pill badge enforced at 28px height", strpos($viewContent, '.prod-badge {') !== false && strpos($viewContent, 'height: 28px;') !== false);
assertTest("Clickable theme-matched status badge trigger implemented", strpos($viewContent, 'toggleEmployeeStatus') !== false);
assertTest("No status dropdown present in employee form (empForm)", strpos($viewContent, 'id="formStatus"') === false);
assertTest("Salary column header indicates currency with '(LKR)'", strpos($viewContent, 'Basic Salary (LKR)') !== false);
assertTest("Full-row click implemented for slide-over drawer", strpos($viewContent, 'openEmployeeDrawer') !== false);
assertTest("CurtissSearchableSelect attached to filter selects", strpos($viewContent, 'CurtissSearchableSelect.attach(branchSel)') !== false || strpos($viewContent, 'CurtissSearchableSelect.attach') !== false);
assertTest("CurtissNav.enablePageSearch debounces with 300ms timer", strpos($viewContent, 'searchDebounceTimer') !== false && strpos($viewContent, '300') !== false);

// -----------------------------------------------------------------------------
// CLEANUP TEST FIXTURES (NO TABLE TRUNCATE)
// -----------------------------------------------------------------------------
echo "\n--- Cleaning up isolated test fixtures (ZERO table truncation) ---\n";
Database::execute("DELETE FROM audit_logs WHERE module = 'employees' AND record_id = :id", [':id' => (string)$testEmpId]);
Database::execute("DELETE FROM employees WHERE id = :id", [':id' => $testEmpId]);
echo "Cleaned up isolated employee fixture ID {$testEmpId}.\n";

// -----------------------------------------------------------------------------
// SUMMARY
// -----------------------------------------------------------------------------
echo "\n========================================================================\n";
echo "EMPLOYEE DIRECTORY OPTIMIZATION SUITE RESULTS: {$passedTests} / {$totalTests} PASSED\n";
if ($failedTests > 0) {
    echo "FAILED: {$failedTests} tests failed!\n";
} else {
    echo "ALL TESTS PASSED CLEANLY! (100% SUCCESS)\n";
}
echo "========================================================================\n";

exit($failedTests > 0 ? 1 : 0);
