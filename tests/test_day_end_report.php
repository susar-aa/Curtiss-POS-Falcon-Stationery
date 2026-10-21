<?php
/**
 * CLI Test Suite for Day End Business & Reconciliation Report
 * Tests:
 * 1. Cost of Goods Sold (COGS) & Gross Profit calculation
 * 2. Sales and payment methods breakdown
 * 3. Customer credit collections tracking
 * 4. Supplier payments & advance payments tracking
 * 5. Employee advance payments tracking
 * 6. Petty cash vouchers & operating expenses tracking
 * 7. Sales returns & customer refunds tracking
 * 8. Receipt template rendering verification
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/BusinessDay.php';
require_once __DIR__ . '/../core/Settings.php';

echo "=========================================================\n";
echo "   CURTISS POS - DAY END REPORT CLI VERIFICATION SUITE   \n";
echo "=========================================================\n\n";

$allPassed = true;

function assertTest(string $description, bool $condition): void {
    global $allPassed;
    if ($condition) {
        echo " [PASS] " . $description . "\n";
    } else {
        echo " [FAIL] " . $description . "\n";
        $allPassed = false;
    }
}

$db = Database::getInstance()->getConnection();
$branchId = 1;

// Get or create active business day
$bday = BusinessDay::getOrCreateActiveDay($branchId, 1);
assertTest("Active business day is accessible (ID: {$bday['id']})", !empty($bday) && (int)$bday['id'] > 0);
$bdayId = (int)$bday['id'];

// --------------------------------------------------------------------------
// TEST 1: COGS & Sales Profitability Calculation
// --------------------------------------------------------------------------
echo "\n--- TEST 1: Sales & Cost of Goods Sold (COGS) ---\n";

$salesSummary = BusinessDay::getDaySalesSummary($bdayId);
assertTest("getDaySalesSummary returns total_cogs key", array_key_exists('total_cogs', $salesSummary));
assertTest("getDaySalesSummary returns gross_profit key", array_key_exists('gross_profit', $salesSummary));
assertTest("getDaySalesSummary returns gross_margin_pct key", array_key_exists('gross_margin_pct', $salesSummary));
assertTest("getDaySalesSummary returns total_items_sold key", array_key_exists('total_items_sold', $salesSummary));

$netSales = (float)$salesSummary['net_sales'];
$cogs     = (float)$salesSummary['total_cogs'];
$profit   = (float)$salesSummary['gross_profit'];
echo "   -> Net Sales: LKR " . number_format($netSales, 2) . "\n";
echo "   -> Total COGS: LKR " . number_format($cogs, 2) . "\n";
echo "   -> Gross Profit: LKR " . number_format($profit, 2) . " (" . $salesSummary['gross_margin_pct'] . "%)\n";

assertTest("Gross Profit equals Net Sales minus COGS", abs($profit - ($netSales - $cogs)) < 0.01);

// --------------------------------------------------------------------------
// TEST 2: Comprehensive Day End Summary Structure
// --------------------------------------------------------------------------
echo "\n--- TEST 2: Comprehensive Summary Engine ---\n";

$compSummary = BusinessDay::getDayComprehensiveSummary($bdayId);
assertTest("getDayComprehensiveSummary succeeds", !empty($compSummary['success']));
assertTest("Summary contains sales_summary", isset($compSummary['sales_summary']));
assertTest("Summary contains customer_collections", isset($compSummary['customer_collections']));
assertTest("Summary contains supplier_payments", isset($compSummary['supplier_payments']));
assertTest("Summary contains employee_advances", isset($compSummary['employee_advances']));
assertTest("Summary contains petty_cash_expenses", isset($compSummary['petty_cash_expenses']));
assertTest("Summary contains refunds", isset($compSummary['refunds']));
assertTest("Summary contains manual_movements", isset($compSummary['manual_movements']));
assertTest("Summary contains drawer_summary", isset($compSummary['drawer_summary']));

// --------------------------------------------------------------------------
// TEST 3: Transaction Categories Insertion & Retrieval Verification
// --------------------------------------------------------------------------
echo "\n--- TEST 3: Transaction Totals Tracking ---\n";

$todayDate = $bday['business_date'];

// Fetch valid customer and supplier
$custRow = Database::fetchOne("SELECT id FROM customers LIMIT 1");
$suppRow = Database::fetchOne("SELECT id FROM suppliers LIMIT 1");

$validCustId = (int)($custRow['id'] ?? 2);
$validSuppId = (int)($suppRow['id'] ?? 1);

// Ensure test employee exists
$empRow = Database::fetchOne("SELECT id FROM employees LIMIT 1");
$testEmpCreated = false;
$validEmpId = 0;
if ($empRow) {
    $validEmpId = (int)$empRow['id'];
} else {
    $db->prepare("
        INSERT INTO employees (branch_id, employee_code, full_name, job_role, basic_salary, status, created_at)
        VALUES (:b, 'EMP-TEST-01', 'Test Day End Cashier', 'Cashier', 45000.00, 'active', NOW())
    ")->execute([':b' => $branchId]);
    $validEmpId = (int)$db->lastInsertId();
    $testEmpCreated = true;
}

$testSuffix = time() . '_' . mt_rand(100, 999);

// Pre-cleanup any lingering test records
$db->query("DELETE FROM supplier_payments WHERE payment_no LIKE 'SP-TEST-%'");
$db->query("DELETE FROM employee_advances WHERE advance_no LIKE 'ADV-TEST-%'");
$db->query("DELETE FROM petty_cash_vouchers WHERE voucher_no LIKE 'PCV-TEST-%'");
$db->query("DELETE FROM customer_credit_ledger WHERE reference_no LIKE 'TEST-REC-%'");
$db->query("DELETE FROM refunds WHERE reference_no LIKE 'REF-TEST-%'");
$db->query("DELETE FROM sales_returns WHERE return_no LIKE 'RET-TEST-%'");

// 1. Insert test Customer Credit Collection
$testDateTime = date('Y-m-d H:i:s');

$db->prepare("
    INSERT INTO customer_credit_ledger (customer_id, transaction_type, reference_no, invoice_total, amount_paid, amount_due, balance_after, notes, branch_id, user_id, created_at)
    VALUES (:c, 'payment', :ref, 0.00, 4500.00, 0.00, 500.00, 'Test Day End Collection', :b, 1, :dt)
")->execute([':c' => $validCustId, ':ref' => 'TEST-REC-' . $testSuffix, ':b' => $branchId, ':dt' => $testDateTime]);
$testCclId = (int)$db->lastInsertId();

// 2. Insert test Supplier Payment
$db->prepare("
    INSERT INTO supplier_payments (payment_no, supplier_id, branch_id, payment_date, payment_method, total_amount, reference_no, notes, created_by, created_at)
    VALUES (:pno, :s, :b, :d, 'Cash', 3200.00, 'CHQ-9988', 'Test Day End Supplier Payment', 1, :dt)
")->execute([':pno' => 'SP-TEST-' . $testSuffix, ':s' => $validSuppId, ':b' => $branchId, ':d' => $todayDate, ':dt' => $testDateTime]);
$testSpId = (int)$db->lastInsertId();

// 3. Insert test Employee Advance
$db->prepare("
    INSERT INTO employee_advances (advance_no, employee_id, branch_id, amount, recovered_amount, remaining_amount, disbursement_date, payment_method, status, notes, created_by, created_at)
    VALUES (:ano, :e, :b, 2500.00, 0.00, 2500.00, :d, 'Cash', 'active', 'Test Day End Salary Advance', 1, :dt)
")->execute([':ano' => 'ADV-TEST-' . $testSuffix, ':e' => $validEmpId, ':b' => $branchId, ':d' => $todayDate, ':dt' => $testDateTime]);
$testEaId = (int)$db->lastInsertId();

// 4. Insert test Petty Cash Expense Voucher
$db->prepare("
    INSERT INTO petty_cash_vouchers (voucher_no, branch_id, voucher_date, category_id, account_id, amount, payee, description, reference_no, status, created_by, created_at)
    VALUES (:vno, :b, :d, 1, 1, 850.00, 'Office Depot', 'Printer Paper & Pens', 'REC-441', 'posted', 1, :dt)
")->execute([':vno' => 'PCV-TEST-' . $testSuffix, ':b' => $branchId, ':d' => $todayDate, ':dt' => $testDateTime]);
$testPcvId = (int)$db->lastInsertId();

// 5. Insert test Sales Return & Refund
$saleRow = Database::fetchOne("SELECT id FROM sales LIMIT 1");
$validSaleId = (int)($saleRow['id'] ?? 1);

$db->prepare("
    INSERT INTO sales_returns (return_no, sale_id, branch_id, customer_id, return_type, total_return_amount, notes, created_by, created_at)
    VALUES (:rno, :s, :b, :c, 'refund', 650.00, 'Test Day End Refund', 1, :dt)
")->execute([':rno' => 'RET-TEST-' . $testSuffix, ':s' => $validSaleId, ':b' => $branchId, ':c' => $validCustId, ':dt' => $testDateTime]);
$testSrId = (int)$db->lastInsertId();

$db->prepare("
    INSERT INTO refunds (sales_return_id, branch_id, customer_id, refund_method, amount, reference_no, created_by, created_at)
    VALUES (:sr, :b, :c, 'cash', 650.00, :ref, 1, :dt)
")->execute([':sr' => $testSrId, ':b' => $branchId, ':c' => $validCustId, ':ref' => 'REF-TEST-' . $testSuffix, ':dt' => $testDateTime]);
$testRefId = (int)$db->lastInsertId();

// Re-query comprehensive summary
$updatedSummary = BusinessDay::getDayComprehensiveSummary($bdayId);

// Check customer collections
$colls = $updatedSummary['customer_collections'];
assertTest("Customer collections total reflects test record (>= 4500)", (float)$colls['total'] >= 4500.00);

// Check supplier payments
$supp = $updatedSummary['supplier_payments'];
assertTest("Supplier payments total reflects test record (>= 3200)", (float)$supp['total'] >= 3200.00);
assertTest("Supplier cash payments tracked separately (>= 3200)", (float)$supp['cash_amount'] >= 3200.00);

// Check employee advances
$adv = $updatedSummary['employee_advances'];
assertTest("Employee advances total reflects test record (>= 2500)", (float)$adv['total'] >= 2500.00);
assertTest("Employee cash advances tracked separately (>= 2500)", (float)$adv['cash_amount'] >= 2500.00);

// Check petty cash expenses
$petty = $updatedSummary['petty_cash_expenses'];
assertTest("Petty cash expenses total reflects test voucher (>= 850)", (float)$petty['total'] >= 850.00);

// Check refunds
$ref = $updatedSummary['refunds'];
assertTest("Refunds total reflects test record (>= 650)", (float)$ref['total'] >= 650.00);

// --------------------------------------------------------------------------
// TEST 4: Receipt Template Buffer Execution
// --------------------------------------------------------------------------
echo "\n--- TEST 4: Receipt Template Rendering ---\n";

$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'superadmin';
$_SESSION['full_name'] = 'Super Administrator';
$_SESSION['role_name'] = 'Super Admin';
$_SESSION['role_code'] = 'super_admin';
$_GET['id'] = $bdayId;

ob_start();
include __DIR__ . '/../day_closing_receipt.php';
$receiptHtml = ob_get_clean();

assertTest("Receipt HTML rendered without fatal errors", !empty($receiptHtml));
assertTest("Receipt contains DAY END BUSINESS REPORT headline", strpos($receiptHtml, 'DAY END BUSINESS REPORT') !== false);
assertTest("Receipt contains Cost of Goods Sold (COGS)", strpos($receiptHtml, 'Cost of Goods Sold (COGS)') !== false);
assertTest("Receipt contains GROSS PROFIT", strpos($receiptHtml, 'GROSS PROFIT') !== false);
assertTest("Receipt contains CUSTOMER CREDIT COLLECTIONS", strpos($receiptHtml, 'CUSTOMER CREDIT COLLECTIONS') !== false);
assertTest("Receipt contains SUPPLIER PAYMENTS & ADVANCES", strpos($receiptHtml, 'SUPPLIER PAYMENTS & ADVANCES') !== false);
assertTest("Receipt contains EMPLOYEE ADVANCE PAYMENTS", strpos($receiptHtml, 'EMPLOYEE ADVANCE PAYMENTS') !== false);
assertTest("Receipt contains PETTY CASH / OPERATING EXPENSES", strpos($receiptHtml, 'PETTY CASH / OPERATING EXPENSES') !== false);
assertTest("Receipt contains SALES RETURNS & CUSTOMER REFUNDS", strpos($receiptHtml, 'SALES RETURNS & CUSTOMER REFUNDS') !== false);
assertTest("Receipt contains CASH RECONCILIATION section", strpos($receiptHtml, 'CASH RECONCILIATION') !== false);

// --------------------------------------------------------------------------
// CLEANUP: Isolated Test Data Only
// --------------------------------------------------------------------------
echo "\n--- Cleaning Up Isolated Test Records ---\n";
try {
    $db->prepare("DELETE FROM customer_credit_ledger WHERE id = :id")->execute([':id' => $testCclId]);
    $db->prepare("DELETE FROM supplier_payments WHERE id = :id")->execute([':id' => $testSpId]);
    $db->prepare("DELETE FROM employee_advances WHERE id = :id")->execute([':id' => $testEaId]);
    $db->prepare("DELETE FROM petty_cash_vouchers WHERE id = :id")->execute([':id' => $testPcvId]);
    $db->prepare("DELETE FROM refunds WHERE id = :id")->execute([':id' => $testRefId]);
    if (!empty($testSrId)) {
        $db->prepare("DELETE FROM sales_returns WHERE id = :id")->execute([':id' => $testSrId]);
    }
    if ($testEmpCreated && !empty($validEmpId)) {
        $db->prepare("DELETE FROM employees WHERE id = :id")->execute([':id' => $validEmpId]);
    }
    echo " [CLEANUP] Isolated test records successfully removed.\n";
} catch (Throwable $e) {
    echo " [CLEANUP ERROR] " . $e->getMessage() . "\n";
}

echo "\n=========================================================\n";
if ($allPassed) {
    echo "   >>> ALL CLI TESTS PASSED SUCCESSFULLY! (100%) <<<\n";
} else {
    echo "   >>> SOME CLI TESTS FAILED! CHECK OUTPUT ABOVE. <<<\n";
}
echo "=========================================================\n";

exit($allPassed ? 0 : 1);
