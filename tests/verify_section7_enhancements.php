<?php
/**
 * Curtiss POS - Section 7 Verification Suite
 * Comprehensive verification of Customer Collections & Accounts Receivable enhancements.
 * Verifies Customer Credit Collections, Cash/Card/Bank/Cheque methods, Double-Entry GL Integration,
 * Customer Cheque Lifecycle, Return Cheque Workflow, Security Controls, and Audit Trail.
 */

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Customer.php';
require_once __DIR__ . '/../core/CustomerPayment.php';
require_once __DIR__ . '/../core/Accounting.php';
require_once __DIR__ . '/../core/BusinessDay.php';
require_once __DIR__ . '/../core/Audit.php';

$passed = 0;
$failed = 0;
$testCount = 0;

function assertSec7(bool $condition, string $message): void
{
    global $passed, $failed, $testCount;
    $testCount++;
    if ($condition) {
        $passed++;
        echo "  [PASS] Assertion #{$testCount}: {$message}\n";
    } else {
        $failed++;
        echo "  [FAIL] Assertion #{$testCount}: {$message}\n";
    }
}

echo "========================================================================\n";
echo "       CURTISS POS - SECTION 7 ENHANCEMENTS VERIFICATION SUITE          \n";
echo "========================================================================\n\n";

$db = Database::getInstance()->getConnection();

// --- SETUP TEST DATA ---
echo "--> Setting up clean test environment for Section 7...\n";

// Cleanup old test data
$db->exec("DELETE FROM payments WHERE reference_no LIKE 'SEC7-%' OR payment_no LIKE 'CPAY-S7%' OR payment_no LIKE 'CHQ-RET-%' OR customer_id IN (SELECT id FROM customers WHERE name LIKE 'Sec7 Test%' OR customer_code LIKE 'CUS-S7%')");
$db->exec("DELETE FROM customer_cheques WHERE cheque_no LIKE 'S7-CHQ%' OR customer_id IN (SELECT id FROM customers WHERE name LIKE 'Sec7 Test%' OR customer_code LIKE 'CUS-S7%')");
$db->exec("DELETE FROM customer_credit_ledger WHERE notes LIKE '%Section 7%' OR reference_no LIKE 'CPAY-S7%' OR reference_no LIKE 'CHQ-RET-%' OR customer_id IN (SELECT id FROM customers WHERE name LIKE 'Sec7 Test%' OR customer_code LIKE 'CUS-S7%')");
$db->exec("DELETE FROM journal_lines WHERE journal_entry_id IN (SELECT id FROM journal_entries WHERE description LIKE '%Section 7%' OR description LIKE '%S7%')");
$db->exec("DELETE FROM journal_entries WHERE description LIKE '%Section 7%' OR description LIKE '%S7%'");
$db->exec("DELETE FROM sales WHERE invoice_no LIKE 'INV-S7-%' OR customer_id IN (SELECT id FROM customers WHERE name LIKE 'Sec7 Test%' OR customer_code LIKE 'CUS-S7%')");
$db->exec("DELETE FROM bank_accounts WHERE account_number LIKE 'S7-ACC-%'");
$db->exec("DELETE FROM customers WHERE name LIKE 'Sec7 Test%' OR customer_code LIKE 'CUS-S7%'");

// Ensure active branch and business day
$branch = Database::fetchOne("SELECT id FROM branches LIMIT 1");
$branchId = $branch ? (int)$branch['id'] : 1;

$user = Database::fetchOne("SELECT id FROM users LIMIT 1");
$userId = $user ? (int)$user['id'] : 1;

$activeDay = BusinessDay::getActiveDay($branchId);
if (!$activeDay) {
    BusinessDay::startDay($branchId, 10000.00, $userId, 'Section 7 test day opening');
    $activeDay = BusinessDay::getActiveDay($branchId);
}
$bDayId = (int)$activeDay['id'];

// Ensure dedicated Bank Account exists with COA
$bankAcc = Database::fetchOne("SELECT ba.*, coa.id as coa_id FROM bank_accounts ba JOIN chart_of_accounts coa ON ba.account_id = coa.id WHERE ba.status = 'active' LIMIT 1");
if (!$bankAcc) {
    // Create bank account COA & Bank record
    $db->exec("INSERT INTO chart_of_accounts (account_code, account_name, account_type, is_system, status) VALUES ('1021', 'Sec7 Commercial Bank', 'asset', 0, 'active')");
    $bankCoaId = (int)$db->lastInsertId();
    $db->exec("INSERT INTO bank_accounts (account_name, account_number, bank_name, account_id, status) VALUES ('Sec7 Commercial Bank Account', '1234567890', 'Commercial Bank', {$bankCoaId}, 'active')");
    $bankAcc = Database::fetchOne("SELECT ba.*, coa.id as coa_id FROM bank_accounts ba JOIN chart_of_accounts coa ON ba.account_id = coa.id WHERE ba.status = 'active' ORDER BY ba.id DESC LIMIT 1");
}
$bankAccountId = (int)$bankAcc['id'];
$bankCoaAccountId = (int)$bankAcc['account_id'];

// Create Test Customers
$db->exec("
    INSERT INTO customers (name, customer_code, phone, whatsapp, card_barcode, credit_limit, credit_balance, status)
    VALUES ('Sec7 Test Active Customer', 'CUS-S701', '0771112233', '0771112233', 'BAR-S701', 50000.00, 15000.00, 'active')
");
$cust1Id = (int)$db->lastInsertId();

$db->exec("
    INSERT INTO customers (name, customer_code, phone, whatsapp, card_barcode, credit_limit, credit_balance, status)
    VALUES ('Sec7 Test Inactive Customer', 'CUS-S702', '0774445566', '0774445566', 'BAR-S702', 30000.00, 8000.00, 'inactive')
");
$cust2Id = (int)$db->lastInsertId();

$db->exec("
    INSERT INTO customers (name, customer_code, phone, whatsapp, card_barcode, credit_limit, credit_balance, status)
    VALUES ('Sec7 Test Zero Balance Customer', 'CUS-S703', '0777778899', '0777778899', 'BAR-S703', 20000.00, 0.00, 'active')
");
$cust3Id = (int)$db->lastInsertId();

// Create sample sales invoices for cust1Id to test allocation
$db->exec("
    INSERT INTO sales (invoice_no, branch_id, customer_id, user_id, total_amount, status)
    VALUES ('INV-S7-001', {$branchId}, {$cust1Id}, {$userId}, 6000.00, 'completed')
");
$sale1Id = (int)$db->lastInsertId();

$db->exec("
    INSERT INTO sales (invoice_no, branch_id, customer_id, user_id, total_amount, status)
    VALUES ('INV-S7-002', {$branchId}, {$cust1Id}, {$userId}, 9000.00, 'completed')
");
$sale2Id = (int)$db->lastInsertId();

echo "[OK] Setup completed successfully.\n\n";

// --- TEST SUITE 1: CUSTOMER SEARCH & LOOKUP ---
echo "--> Test Suite 1: Customer Search & Identification...\n";

$searchResults = CustomerPayment::searchCustomers('CUS-S701');
assertSec7(count($searchResults) === 1 && $searchResults[0]['id'] === $cust1Id, "Test #1: Search customer by Customer Code (CUS-S701)");

$searchResultsName = CustomerPayment::searchCustomers('Sec7 Test Active');
assertSec7(count($searchResultsName) >= 1 && $searchResultsName[0]['id'] === $cust1Id, "Test #2: Search customer by Name ('Sec7 Test Active')");

$searchResultsPhone = CustomerPayment::searchCustomers('0771112233');
assertSec7(count($searchResultsPhone) === 1 && $searchResultsPhone[0]['id'] === $cust1Id, "Test #3: Search customer by Phone ('0771112233')");

$searchResultsCard = CustomerPayment::searchCustomers('BAR-S701');
assertSec7(count($searchResultsCard) === 1 && $searchResultsCard[0]['id'] === $cust1Id, "Test #4: Search customer by Card Barcode ('BAR-S701')");

$searchResultsWalkIn = CustomerPayment::searchCustomers('Walk-in');
assertSec7(count(array_filter($searchResultsWalkIn, fn($c) => (int)$c['id'] === 1)) === 0, "Test #5: Walk-in customer (id=1) excluded from credit customer search");

// --- TEST SUITE 2: OUTSTANDING SUMMARY ---
echo "\n--> Test Suite 2: Customer Outstanding Summary...\n";

$summary = CustomerPayment::getCustomerOutstandingSummary($cust1Id);
assertSec7($summary['success'] === true, "Test #6: getCustomerOutstandingSummary returned success");
assertSec7((float)$summary['credit_balance'] === 15000.00, "Test #7: Outstanding balance correctly identified as 15,000.00");
assertSec7((float)$summary['available_credit'] === 35000.00, "Test #8: Available credit correctly calculated as 35,000.00 (50,000 limit - 15,000 balance)");
assertSec7(count($summary['unpaid_invoices']) === 2, "Test #9: Returned 2 unpaid credit invoices for customer");
assertSec7((float)$summary['unpaid_invoices'][0]['due_amount'] === 6000.00, "Test #10: First unpaid invoice due amount is 6,000.00");
assertSec7((float)$summary['unpaid_invoices'][1]['due_amount'] === 9000.00, "Test #11: Second unpaid invoice due amount is 9,000.00");

// --- TEST SUITE 3: MANAGEMENT CONTROLS & SECURITY VALIDATION ---
echo "\n--> Test Suite 3: Management Controls & Validation Restrictions...\n";

$resWalkIn = CustomerPayment::processPayment(['customer_id' => 1, 'payment_method' => 'Cash', 'amount' => 500.00], $userId, $branchId);
assertSec7($resWalkIn['success'] === false && str_contains($resWalkIn['message'], 'Walk-in Customer'), "Test #12: Blocked credit collection for Walk-in Customer (id=1)");

$resZeroAmt = CustomerPayment::processPayment(['customer_id' => $cust1Id, 'payment_method' => 'Cash', 'amount' => 0.00], $userId, $branchId);
assertSec7($resZeroAmt['success'] === false && str_contains($resZeroAmt['message'], 'greater than zero'), "Test #13: Blocked collection with zero amount");

$resNegAmt = CustomerPayment::processPayment(['customer_id' => $cust1Id, 'payment_method' => 'Cash', 'amount' => -100.00], $userId, $branchId);
assertSec7($resNegAmt['success'] === false && str_contains($resNegAmt['message'], 'greater than zero'), "Test #14: Blocked collection with negative amount");

$resOverPay = CustomerPayment::processPayment(['customer_id' => $cust1Id, 'payment_method' => 'Cash', 'amount' => 20000.00], $userId, $branchId);
assertSec7($resOverPay['success'] === false && str_contains($resOverPay['message'], 'Overpayment prevented'), "Test #15: Blocked over-collection (amount 20,000 > balance 15,000)");

$resCreditMethod = CustomerPayment::processPayment(['customer_id' => $cust1Id, 'payment_method' => 'Credit', 'amount' => 1000.00], $userId, $branchId);
assertSec7($resCreditMethod['success'] === false && str_contains($resCreditMethod['message'], 'Credit cannot be used'), "Test #16: Blocked using 'Credit' as collection payment method");

$resNoBank = CustomerPayment::processPayment(['customer_id' => $cust1Id, 'payment_method' => 'Bank', 'amount' => 1000.00], $userId, $branchId);
assertSec7($resNoBank['success'] === false && str_contains($resNoBank['message'], 'Bank account selection is required'), "Test #17: Blocked Bank collection without bank account ID");

$resInvalidBank = CustomerPayment::processPayment(['customer_id' => $cust1Id, 'payment_method' => 'Bank', 'bank_account_id' => 99999, 'amount' => 1000.00], $userId, $branchId);
assertSec7($resInvalidBank['success'] === false && str_contains($resInvalidBank['message'], 'invalid or inactive'), "Test #18: Blocked Bank collection with invalid bank account ID");

$resNoChequeNo = CustomerPayment::processPayment(['customer_id' => $cust1Id, 'payment_method' => 'Cheque', 'bank_name' => 'BOC', 'cheque_date' => '2026-10-01', 'amount' => 1000.00], $userId, $branchId);
assertSec7($resNoChequeNo['success'] === false && str_contains($resNoChequeNo['message'], 'Cheque number is required'), "Test #19: Blocked Cheque collection without cheque number");

$resNoChequeBank = CustomerPayment::processPayment(['customer_id' => $cust1Id, 'payment_method' => 'Cheque', 'cheque_no' => 'CHQ123', 'cheque_date' => '2026-10-01', 'amount' => 1000.00], $userId, $branchId);
assertSec7($resNoChequeBank['success'] === false && str_contains($resNoChequeBank['message'], 'Bank name is required'), "Test #20: Blocked Cheque collection without bank name");

$resZeroBalCust = CustomerPayment::processPayment(['customer_id' => $cust3Id, 'payment_method' => 'Cash', 'amount' => 500.00], $userId, $branchId);
assertSec7($resZeroBalCust['success'] === false && str_contains($resZeroBalCust['message'], 'no outstanding balance'), "Test #21: Blocked collection for customer with zero balance");

// --- TEST SUITE 4: INACTIVE CUSTOMER DEBT COLLECTION ---
echo "\n--> Test Suite 4: Inactive Customer Debt Recovery...\n";

$resInactiveColl = CustomerPayment::processPayment(['customer_id' => $cust2Id, 'payment_method' => 'Cash', 'amount' => 3000.00, 'notes' => 'Section 7 Test Inactive Customer Recovery'], $userId, $branchId);
assertSec7($resInactiveColl['success'] === true, "Test #22: Allowed debt collection for inactive customer (cust2Id)");

$cust2Post = Customer::find($cust2Id);
assertSec7((float)$cust2Post['credit_balance'] === 5000.00, "Test #23: Inactive customer debt balance reduced from 8,000 to 5,000");

// --- TEST SUITE 5: CASH COLLECTION & CASH DRAWER INTEGRATION ---
echo "\n--> Test Suite 5: Cash Collection & Cash Drawer Integration...\n";

$drawerBefore = Database::fetchOne("
    SELECT SUM(CASE WHEN direction = 'in' THEN amount ELSE -amount END) as net_cash
    FROM cash_drawer_movements WHERE business_day_id = :bdid
", [':bdid' => $bDayId]);
$cashBefore = (float)($drawerBefore['net_cash'] ?? 0);

$resCash = CustomerPayment::processPayment(['customer_id' => $cust1Id, 'payment_method' => 'Cash', 'amount' => 4000.00, 'notes' => 'Section 7 Test Cash Collection'], $userId, $branchId);
assertSec7($resCash['success'] === true, "Test #24: Processed valid Cash collection of LKR 4,000.00");

$cust1PostCash = Customer::find($cust1Id);
assertSec7((float)$cust1PostCash['credit_balance'] === 11000.00, "Test #25: Customer debt balance reduced from 15,000 to 11,000");

$drawerAfter = Database::fetchOne("
    SELECT SUM(CASE WHEN direction = 'in' THEN amount ELSE -amount END) as net_cash
    FROM cash_drawer_movements WHERE business_day_id = :bdid
", [':bdid' => $bDayId]);
$cashAfter = (float)($drawerAfter['net_cash'] ?? 0);
assertSec7(round($cashAfter - $cashBefore, 2) === 4000.00, "Test #26: Physical cash drawer INCREASED by exact cash collection amount (4,000.00)");

$jeCash = Database::fetchOne("SELECT * FROM journal_entries WHERE source_type = 'customer_collection' ORDER BY id DESC LIMIT 1");
assertSec7($jeCash !== null, "Test #27: Journal entry generated for Cash collection");

$linesCash = Database::fetchAll("SELECT jel.*, coa.account_code FROM journal_lines jel JOIN chart_of_accounts coa ON jel.account_id = coa.id WHERE jel.journal_entry_id = :jeid", [':jeid' => $jeCash['id']]);
$debitCashCode = '';
$creditCashCode = '';
foreach ($linesCash as $l) {
    if ((float)$l['debit'] > 0) $debitCashCode = $l['account_code'];
    if ((float)$l['credit'] > 0) $creditCashCode = $l['account_code'];
}
assertSec7($debitCashCode === '1010', "Test #28: Cash collection debited Cash in Hand (1010)");
assertSec7($creditCashCode === '1040', "Test #29: Cash collection credited Customer Receivables (1040)");

// --- TEST SUITE 6: CARD COLLECTION & DRAWER BYPASS ---
echo "\n--> Test Suite 6: Card Collection & Cash Drawer Bypass...\n";

$resCard = CustomerPayment::processPayment(['customer_id' => $cust1Id, 'payment_method' => 'Card', 'reference_no' => 'CARD-S7-8899', 'amount' => 2000.00, 'notes' => 'Section 7 Test Card Collection'], $userId, $branchId);
assertSec7($resCard['success'] === true, "Test #30: Processed valid Card collection of LKR 2,000.00");

$cust1PostCard = Customer::find($cust1Id);
assertSec7((float)$cust1PostCard['credit_balance'] === 9000.00, "Test #31: Customer debt balance reduced from 11,000 to 9,000");

$drawerAfterCard = Database::fetchOne("
    SELECT SUM(CASE WHEN direction = 'in' THEN amount ELSE -amount END) as net_cash
    FROM cash_drawer_movements WHERE business_day_id = :bdid
", [':bdid' => $bDayId]);
assertSec7((float)($drawerAfterCard['net_cash'] ?? 0) === $cashAfter, "Test #32: Card collection did NOT alter physical cash drawer");

$jeCard = Database::fetchOne("SELECT * FROM journal_entries WHERE source_type = 'customer_collection' ORDER BY id DESC LIMIT 1");
$linesCard = Database::fetchAll("SELECT jel.*, coa.account_code FROM journal_lines jel JOIN chart_of_accounts coa ON jel.account_id = coa.id WHERE jel.journal_entry_id = :jeid", [':jeid' => $jeCard['id']]);
$debitCardCode = '';
$creditCardCode = '';
foreach ($linesCard as $l) {
    if ((float)$l['debit'] > 0) $debitCardCode = $l['account_code'];
    if ((float)$l['credit'] > 0) $creditCardCode = $l['account_code'];
}
assertSec7($debitCardCode === '1030', "Test #33: Card collection debited Card Receivable (1030)");
assertSec7($creditCardCode === '1040', "Test #34: Card collection credited Customer Receivables (1040)");

// --- TEST SUITE 7: BANK COLLECTION & DEDICATED BANK COA ---
echo "\n--> Test Suite 7: Bank Collection & Dedicated COA...\n";

$resBank = CustomerPayment::processPayment([
    'customer_id'     => $cust1Id,
    'payment_method'  => 'Bank',
    'bank_account_id' => $bankAccountId,
    'reference_no'    => 'BANK-TXN-9988',
    'amount'          => 3000.00,
    'notes'           => 'Section 7 Test Bank Collection'
], $userId, $branchId);
assertSec7($resBank['success'] === true, "Test #35: Processed valid Bank collection of LKR 3,000.00");

$cust1PostBank = Customer::find($cust1Id);
assertSec7((float)$cust1PostBank['credit_balance'] === 6000.00, "Test #36: Customer debt balance reduced from 9,000 to 6,000");

$jeBank = Database::fetchOne("SELECT * FROM journal_entries WHERE source_type = 'customer_collection' ORDER BY id DESC LIMIT 1");
$linesBank = Database::fetchAll("SELECT jel.*, coa.account_code, jel.account_id FROM journal_lines jel JOIN chart_of_accounts coa ON jel.account_id = coa.id WHERE jel.journal_entry_id = :jeid", [':jeid' => $jeBank['id']]);
$debitBankAccId = 0;
$creditBankCode = '';
foreach ($linesBank as $l) {
    if ((float)$l['debit'] > 0) $debitBankAccId = (int)$l['account_id'];
    if ((float)$l['credit'] > 0) $creditBankCode = $l['account_code'];
}
assertSec7($debitBankAccId === $bankCoaAccountId, "Test #37: Bank collection debited dedicated Bank Account COA (Account ID #{$bankCoaAccountId})");
assertSec7($creditBankCode === '1040', "Test #38: Bank collection credited Customer Receivables (1040)");

// --- TEST SUITE 8: CUSTOMER CHEQUE COLLECTION & CHEQUE LIFECYCLE ---
echo "\n--> Test Suite 8: Customer Cheque Collection & In Hand Lifecycle...\n";

$resCheque = CustomerPayment::processPayment([
    'customer_id'    => $cust1Id,
    'payment_method' => 'Cheque',
    'cheque_no'      => 'S7-CHQ-1001',
    'bank_name'      => 'Sampath Bank',
    'cheque_date'    => '2026-10-15',
    'amount'         => 4000.00,
    'notes'          => 'Section 7 Test Cheque Collection'
], $userId, $branchId);
assertSec7($resCheque['success'] === true, "Test #39: Processed valid Cheque collection of LKR 4,000.00");
$chequeId = (int)($resCheque['cheque_id'] ?? 0);
assertSec7($chequeId > 0, "Test #40: Created customer_cheques record (ID #{$chequeId})");

$chqRow = CustomerPayment::getChequeDetails($chequeId);
assertSec7($chqRow !== null && $chqRow['status'] === 'in_hand', "Test #41: Customer cheque created with status 'in_hand'");
assertSec7($chqRow['cheque_no'] === 'S7-CHQ-1001' && $chqRow['bank_name'] === 'Sampath Bank', "Test #42: Cheque details (Number S7-CHQ-1001, Bank Sampath Bank) correctly preserved");

$cust1PostCheque = Customer::find($cust1Id);
assertSec7((float)$cust1PostCheque['credit_balance'] === 2000.00, "Test #43: Customer debt balance reduced from 6,000 to 2,000 upon cheque receipt");

$jeCheque = Database::fetchOne("SELECT * FROM journal_entries WHERE source_type = 'customer_collection' ORDER BY id DESC LIMIT 1");
$linesCheque = Database::fetchAll("SELECT jel.*, coa.account_code FROM journal_lines jel JOIN chart_of_accounts coa ON jel.account_id = coa.id WHERE jel.journal_entry_id = :jeid", [':jeid' => $jeCheque['id']]);
$debitChequeCode = '';
$creditChequeCode = '';
foreach ($linesCheque as $l) {
    if ((float)$l['debit'] > 0) $debitChequeCode = $l['account_code'];
    if ((float)$l['credit'] > 0) $creditChequeCode = $l['account_code'];
}
assertSec7($debitChequeCode === '1035', "Test #44: Cheque collection debited Cheques in Hand (1035)");
assertSec7($creditChequeCode === '1040', "Test #45: Cheque collection credited Customer Receivables (1040)");

// --- TEST SUITE 9: CUSTOMER CHEQUE RETURN WORKFLOW & AR REVERSAL ---
echo "\n--> Test Suite 9: Customer Cheque Return Workflow & Accounting Reversal...\n";

$resRet = CustomerPayment::returnCustomerCheque([
    'cheque_id' => $chequeId,
    'reason'    => 'Insufficient funds in customer account'
], $userId, $branchId);
assertSec7($resRet['success'] === true, "Test #46: returnCustomerCheque returned success response");

$chqRowRet = CustomerPayment::getChequeDetails($chequeId);
assertSec7($chqRowRet['status'] === 'returned', "Test #47: Cheque status updated to 'returned'");
assertSec7($chqRowRet['return_reason'] === 'Insufficient funds in customer account', "Test #48: Return reason saved on cheque record");

$cust1PostRet = Customer::find($cust1Id);
assertSec7((float)$cust1PostRet['credit_balance'] === 6000.00, "Test #49: Customer outstanding balance RESTORED back from 2,000 to 6,000 (+4,000)");

$cclRet = Database::fetchOne("
    SELECT * FROM customer_credit_ledger 
    WHERE customer_id = :cid AND transaction_type = 'cheque_return' 
    ORDER BY id DESC LIMIT 1
", [':cid' => $cust1Id]);
assertSec7($cclRet !== null, "Test #50: Recorded customer_credit_ledger entry with transaction_type='cheque_return'");
assertSec7((float)$cclRet['balance_after'] === 6000.00, "Test #51: Ledger balance_after recorded as 6,000.00");

$jeRet = Database::fetchOne("SELECT * FROM journal_entries WHERE source_type = 'customer_cheque_return' ORDER BY id DESC LIMIT 1");
assertSec7($jeRet !== null, "Test #52: Journal entry created for customer cheque return");

$linesRet = Database::fetchAll("SELECT jel.*, coa.account_code FROM journal_lines jel JOIN chart_of_accounts coa ON jel.account_id = coa.id WHERE jel.journal_entry_id = :jeid", [':jeid' => $jeRet['id']]);
$debitRetCode = '';
$creditRetCode = '';
foreach ($linesRet as $l) {
    if ((float)$l['debit'] > 0) $debitRetCode = $l['account_code'];
    if ((float)$l['credit'] > 0) $creditRetCode = $l['account_code'];
}
assertSec7($debitRetCode === '1040', "Test #53: Cheque return debited Customer Receivables (1040) to reinstate AR");
assertSec7($creditRetCode === '1035', "Test #54: Cheque return credited Cheques in Hand (1035) to reverse in-hand cheque asset");

// --- TEST SUITE 10: CHEQUE RETURN CONTROLS & EDGE CASES ---
echo "\n--> Test Suite 10: Cheque Return Controls & Duplicate Prevention...\n";

$resDupRet = CustomerPayment::returnCustomerCheque([
    'cheque_id' => $chequeId,
    'reason'    => 'Attempting second return'
], $userId, $branchId);
assertSec7($resDupRet['success'] === false && str_contains($resDupRet['message'], 'Duplicate return prevented'), "Test #55: Blocked returning the same cheque twice");

// Test returning cancelled cheque
$db->exec("
    INSERT INTO customer_cheques (cheque_no, customer_id, branch_id, bank_name, cheque_date, received_date, amount, status, created_by)
    VALUES ('S7-CHQ-CANCELLED', {$cust1Id}, {$branchId}, 'BOC', '2026-10-01', '2026-10-01', 1000.00, 'cancelled', {$userId})
");
$cancChqId = (int)$db->lastInsertId();

$resCancRet = CustomerPayment::returnCustomerCheque([
    'cheque_id' => $cancChqId,
    'reason'    => 'Attempting return on cancelled cheque'
], $userId, $branchId);
assertSec7($resCancRet['success'] === false && str_contains($resCancRet['message'], 'Cannot return a cancelled cheque'), "Test #56: Blocked returning a cancelled cheque");

// --- TEST SUITE 11: ALLOCATION INTEGRITY & SEQUENCE HARDENING ---
echo "\n--> Test Suite 11: Allocation Integrity & Sequence Hardening...\n";

$pNo1 = CustomerPayment::generatePaymentNumber();
assertSec7(str_starts_with($pNo1, 'CPAY-'), "Test #57: Payment number generator format is CPAY-xxxx");

$auditReturn = Database::fetchOne("
    SELECT * FROM audit_logs WHERE action = 'CUSTOMER_CHEQUE_RETURNED' ORDER BY id DESC LIMIT 1
");
assertSec7($auditReturn !== null, "Test #58: Audit log recorded for CUSTOMER_CHEQUE_RETURNED event");

$auditPayment = Database::fetchOne("
    SELECT * FROM audit_logs WHERE action = 'CUSTOMER_PAYMENT_PROCESSED' ORDER BY id DESC LIMIT 1
");
assertSec7($auditPayment !== null, "Test #59: Audit log recorded for CUSTOMER_PAYMENT_PROCESSED event");

// Check historical visibility of returned cheque
$chequesList = CustomerPayment::getCheques(['customer_id' => $cust1Id, 'status' => 'returned']);
assertSec7(count($chequesList) >= 1 && $chequesList[0]['cheque_no'] === 'S7-CHQ-1001', "Test #60: Returned cheque remains visible historically in cheque register");

// --- TEST SUITE 12: CHEQUE RETURN AFTER BANK DEPOSIT & 1045 ISOLATION ASSERTIONS ---
echo "\n--> Test Suite 12: Cheque Return After Bank Deposit & COA Isolation...\n";

require_once __DIR__ . '/../core/Banking.php';

// 1. Create a Bank Account with dedicated COA for testing cleared cheque return
$saveBankRes = Banking::saveBankAccount([
    'bank_name'       => 'Sec7 Test Bank',
    'account_name'    => 'Sec7 Test Account',
    'account_number'  => 'S7-ACC-TEST-99',
    'branch_id'       => $branchId,
    'opening_balance' => 0.00,
    'status'          => 'active'
], $userId);

assertSec7($saveBankRes['success'] === true, "Test #61: Created dedicated bank account for cheque deposit testing");
$testBankId = (int)($saveBankRes['id'] ?? 0);

$bankRow = Banking::getBankAccount($testBankId);
$bankCoaCode = $bankRow['account_code'];

// 2. Receive customer cheque
$resCheque2 = CustomerPayment::processPayment([
    'customer_id'    => $cust1Id,
    'payment_method' => 'Cheque',
    'amount'         => 3000.00,
    'cheque_no'      => 'S7-CHQ-BANKED-1002',
    'bank_name'      => 'Sec7 Test Bank',
    'cheque_date'    => date('Y-m-d'),
    'notes'          => 'Section 7 Test Cheque for Bank Deposit'
], $userId, $branchId);

assertSec7($resCheque2['success'] === true, "Test #62: Processed Cheque collection of LKR 3,000.00");
$cheque2Id = (int)($resCheque2['cheque_id'] ?? 0);

// 3. Mark cheque as cleared in bank
$db->exec("UPDATE customer_cheques SET status = 'cleared', bank_account_id = {$testBankId} WHERE id = {$cheque2Id}");

// 4. Return the cleared cheque
$resRet2 = CustomerPayment::returnCustomerCheque([
    'cheque_id' => $cheque2Id,
    'reason'    => 'Cleared cheque bounced post-clearance'
], $userId, $branchId);

assertSec7($resRet2['success'] === true, "Test #63: Successfully returned a cleared customer cheque");

// 5. Verify journal entry for returned cleared cheque
$jeRet2 = Database::fetchOne("
    SELECT * FROM journal_entries 
    WHERE source_type = 'customer_cheque_return' AND source_id = :id
", [':id' => $cheque2Id]);

$linesRet2 = Database::fetchAll("
    SELECT jel.*, coa.account_code 
    FROM journal_lines jel 
    JOIN chart_of_accounts coa ON jel.account_id = coa.id 
    WHERE jel.journal_entry_id = :jeid
", [':jeid' => $jeRet2['id']]);

$debitRet2Code = '';
$creditRet2Code = '';
foreach ($linesRet2 as $l) {
    if ((float)$l['debit'] > 0) $debitRet2Code = $l['account_code'];
    if ((float)$l['credit'] > 0) $creditRet2Code = $l['account_code'];
}

assertSec7($debitRet2Code === '1040', "Test #64: Returned deposited cheque debited Customer Receivables (1040)");
assertSec7($creditRet2Code === $bankCoaCode, "Test #65: Returned deposited cheque credited dedicated Bank COA ({$bankCoaCode})");
assertSec7($creditRet2Code !== '1035', "Test #66: Returned deposited cheque did NOT credit 1035 (since cheque was already cleared)");

// 6. Assert Account 1045 Isolation across all customer cheque journals
$chq1045Count = (int)Database::fetchOne("
    SELECT COUNT(*) AS cnt
    FROM journal_lines jl
    JOIN journal_entries je ON jl.journal_entry_id = je.id
    JOIN chart_of_accounts coa ON jl.account_id = coa.id
    WHERE je.source_type IN ('customer_collection', 'customer_cheque_return')
      AND coa.account_code = '1045'
")['cnt'];

assertSec7($chq1045Count === 0, "Test #67: Isolation confirmed - 0 customer cheque journal lines hit 1045 Supplier Credit account");

// 7. Verify Canonical Mappings
assertSec7(Accounting::CODE_CHEQUES_HAND === '1035', "Test #68: Accounting::CODE_CHEQUES_HAND is 1035");
assertSec7(Accounting::CODE_CUSTOMER_RECEIVABLE === '1040', "Test #69: Accounting::CODE_CUSTOMER_RECEIVABLE is 1040");
assertSec7(Accounting::CODE_SUPPLIER_CREDIT === '1045', "Test #70: Accounting::CODE_SUPPLIER_CREDIT is 1045");
assertSec7(Accounting::CODE_CHEQUES_ISSUED === '2025', "Test #71: Accounting::CODE_CHEQUES_ISSUED is 2025");

echo "\n========================================================================\n";
echo " Section 7 Enhancement Verification Summary:                             \n";
echo " Passed: {$passed} / " . ($passed + $failed) . "                                       \n";
echo " Failed: {$failed}                                                      \n";
echo "========================================================================\n";

if ($failed > 0) {
    echo "\n[ERROR] Section 7 Verification Failed with {$failed} errors.\n";
    exit(1);
} else {
    echo "\n[SUCCESS] Section 7 Enhancements fully verified. All 60 test assertions PASSED!\n";
    exit(0);
}
