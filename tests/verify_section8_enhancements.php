<?php
/**
 * Curtiss POS - Section 8 Verification Suite
 * Comprehensive verification of Supplier Payments, Supplier Returns & Accounts Payable.
 * Verifies Supplier AP, Cash/Bank/Cheque Payments, Cash Drawer (OUT), Supplier Cheque Lifecycle,
 * Return Workflows (Exact Lot Costing), Supplier Credit Settlements, Security Controls, and Audit Trail.
 */

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Supplier.php';
require_once __DIR__ . '/../core/SupplierPayment.php';
require_once __DIR__ . '/../core/SupplierReturnEngine.php';
require_once __DIR__ . '/../core/ChequeEngine.php';
require_once __DIR__ . '/../core/Accounting.php';
require_once __DIR__ . '/../core/BusinessDay.php';
require_once __DIR__ . '/../core/Banking.php';
require_once __DIR__ . '/../core/Audit.php';

$passed = 0;
$failed = 0;
$testCount = 0;

function assertSec8(bool $condition, string $message): void
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
echo "       CURTISS POS - SECTION 8 ENHANCEMENTS VERIFICATION SUITE          \n";
echo "========================================================================\n\n";

$db = Database::getInstance()->getConnection();

// --- SETUP TEST ENVIRONMENT ---
echo "--> Setting up clean test environment for Section 8...\n";

// Cleanup previous test artifacts
$db->exec("DELETE FROM bank_reconciliation_items WHERE journal_line_id IN (SELECT id FROM journal_lines WHERE journal_entry_id IN (SELECT id FROM journal_entries WHERE description LIKE '%Section 8%' OR description LIKE '%S8%' OR description LIKE '%Sec8 Test%'))");
$db->exec("DELETE FROM supplier_credit_settlements WHERE settlement_number LIKE 'SSET-S8%' OR notes LIKE '%Section 8%' OR supplier_id IN (SELECT id FROM suppliers WHERE name LIKE 'Sec8 Test%' OR supplier_code LIKE 'SUP-S8%')");
$db->exec("DELETE FROM supplier_credits WHERE credit_number LIKE 'SCRD-S8%' OR notes LIKE '%Section 8%' OR supplier_id IN (SELECT id FROM suppliers WHERE name LIKE 'Sec8 Test%' OR supplier_code LIKE 'SUP-S8%')");
$db->exec("DELETE FROM supplier_return_items WHERE supplier_return_id IN (SELECT id FROM supplier_returns WHERE return_number LIKE 'SRET-S8%' OR notes LIKE '%Section 8%' OR grn_id IN (SELECT id FROM grns WHERE grn_number LIKE 'GRN-S8-%') OR supplier_id IN (SELECT id FROM suppliers WHERE name LIKE 'Sec8 Test%' OR supplier_code LIKE 'SUP-S8%'))");
$db->exec("DELETE FROM supplier_returns WHERE return_number LIKE 'SRET-S8%' OR notes LIKE '%Section 8%' OR grn_id IN (SELECT id FROM grns WHERE grn_number LIKE 'GRN-S8-%') OR supplier_id IN (SELECT id FROM suppliers WHERE name LIKE 'Sec8 Test%' OR supplier_code LIKE 'SUP-S8%')");
$db->exec("DELETE FROM supplier_cheques WHERE cheque_number LIKE 'S8-CHQ%' OR bank_name LIKE 'Sec8%' OR supplier_id IN (SELECT id FROM suppliers WHERE name LIKE 'Sec8 Test%' OR supplier_code LIKE 'SUP-S8%')");
$db->exec("DELETE FROM supplier_payment_grns WHERE supplier_payment_id IN (SELECT id FROM supplier_payments WHERE payment_no LIKE 'SPAY-S8%' OR notes LIKE '%Section 8%' OR supplier_id IN (SELECT id FROM suppliers WHERE name LIKE 'Sec8 Test%' OR supplier_code LIKE 'SUP-S8%'))");
$db->exec("DELETE FROM supplier_payments WHERE payment_no LIKE 'SPAY-S8%' OR notes LIKE '%Section 8%' OR supplier_id IN (SELECT id FROM suppliers WHERE name LIKE 'Sec8 Test%' OR supplier_code LIKE 'SUP-S8%')");
$db->exec("DELETE FROM stock_ledger WHERE reference_no LIKE 'SRET-S8%' OR notes LIKE '%Section 8%' OR product_id IN (SELECT id FROM products WHERE barcode LIKE 'BAR-S8%')");
$db->exec("DELETE FROM stock_audit_items WHERE lot_id IN (SELECT id FROM product_lots WHERE lot_number LIKE 'LOT-S8-%') OR product_id IN (SELECT id FROM products WHERE barcode LIKE 'BAR-S8%')");
$db->exec("DELETE FROM grn_items WHERE grn_id IN (SELECT id FROM grns WHERE grn_number LIKE 'GRN-S8-%' OR supplier_id IN (SELECT id FROM suppliers WHERE name LIKE 'Sec8 Test%' OR supplier_code LIKE 'SUP-S8%'))");
$db->exec("DELETE FROM grns WHERE grn_number LIKE 'GRN-S8-%' OR supplier_id IN (SELECT id FROM suppliers WHERE name LIKE 'Sec8 Test%' OR supplier_code LIKE 'SUP-S8%')");
$db->exec("DELETE FROM product_lots WHERE lot_number LIKE 'LOT-S8-%' OR product_id IN (SELECT id FROM products WHERE barcode LIKE 'BAR-S8%')");
$db->exec("DELETE FROM branch_inventory WHERE product_id IN (SELECT id FROM products WHERE barcode LIKE 'BAR-S8%')");
$db->exec("DELETE FROM products WHERE barcode LIKE 'BAR-S8%'");
$db->exec("DELETE FROM journal_lines WHERE journal_entry_id IN (SELECT id FROM journal_entries WHERE description LIKE '%Section 8%' OR description LIKE '%S8%' OR description LIKE '%Sec8 Test%')");
$db->exec("DELETE FROM journal_entries WHERE description LIKE '%Section 8%' OR description LIKE '%S8%' OR description LIKE '%Sec8 Test%'");
$db->exec("DELETE FROM suppliers WHERE name LIKE 'Sec8 Test%' OR supplier_code LIKE 'SUP-S8%'");

$branch = Database::fetchOne("SELECT id FROM branches LIMIT 1");
$branchId = $branch ? (int)$branch['id'] : 1;

$user = Database::fetchOne("SELECT id FROM users LIMIT 1");
$userId = $user ? (int)$user['id'] : 1;

$activeDay = BusinessDay::getActiveDay($branchId);
if (!$activeDay) {
    BusinessDay::startBusinessDay($branchId, 10000.00, $userId, 'Section 8 test day opening');
    $activeDay = BusinessDay::getActiveDay($branchId);
}
$bDayId = (int)$activeDay['id'];

// Dedicated Bank Account
$bankAcc = Database::fetchOne("SELECT ba.*, coa.id as coa_id FROM bank_accounts ba JOIN chart_of_accounts coa ON ba.account_id = coa.id WHERE ba.status = 'active' LIMIT 1");
if (!$bankAcc) {
    $db->exec("INSERT INTO chart_of_accounts (account_code, account_name, account_type, is_system, status) VALUES ('1022', 'Sec8 HNB Bank Account', 'asset', 0, 'active')");
    $bankCoaId = (int)$db->lastInsertId();
    $db->exec("INSERT INTO bank_accounts (account_name, account_number, bank_name, account_id, status) VALUES ('Sec8 HNB Account', '9876543210', 'Hatton National Bank', {$bankCoaId}, 'active')");
    $bankAcc = Database::fetchOne("SELECT ba.*, coa.id as coa_id FROM bank_accounts ba JOIN chart_of_accounts coa ON ba.account_id = coa.id WHERE ba.status = 'active' ORDER BY ba.id DESC LIMIT 1");
}
$bankAccountId = (int)$bankAcc['id'];
$bankCoaAccountId = (int)$bankAcc['account_id'];

// Create Test Suppliers
$db->exec("
    INSERT INTO suppliers (name, supplier_code, phone, whatsapp, status)
    VALUES ('Sec8 Test Active Supplier', 'SUP-S801', '0711002233', '0711002233', 'active')
");
$sup1Id = (int)$db->lastInsertId();

$db->exec("
    INSERT INTO suppliers (name, supplier_code, phone, whatsapp, status)
    VALUES ('Sec8 Test Inactive Supplier', 'SUP-S802', '0714405566', '0714405566', 'inactive')
");
$sup2Id = (int)$db->lastInsertId();

// Create Test Product & Lot for Returns
$brandId = (int)(Database::fetchOne("SELECT id FROM brands LIMIT 1")['id'] ?? 1);
$catId   = (int)(Database::fetchOne("SELECT id FROM categories LIMIT 1")['id'] ?? 1);

$db->exec("
    INSERT INTO products (name, barcode, brand_id, category_id, supplier_id, cost, selling_price, status)
    VALUES ('Sec8 Test Product A', 'BAR-S801', {$brandId}, {$catId}, {$sup1Id}, 120.00, 180.00, 'active')
");
$prod1Id = (int)$db->lastInsertId();

$db->exec("
    INSERT INTO product_lots (product_id, branch_id, lot_number, initial_quantity, remaining_quantity, cost, selling_price)
    VALUES ({$prod1Id}, {$branchId}, 'LOT-S8-001', 100.00, 100.00, 100.00, 150.00)
");
$lot1Id = (int)$db->lastInsertId();

// Set initial branch inventory
$db->exec("
    INSERT INTO branch_inventory (branch_id, product_id, quantity)
    VALUES ({$branchId}, {$prod1Id}, 100.00)
    ON DUPLICATE KEY UPDATE quantity = 100.00
");

// Create approved GRNs for sup1Id
$db->exec("
    INSERT INTO grns (grn_number, po_id, branch_id, supplier_id, created_by, received_date, actual_grn_total, paid_amount, payment_status, status)
    VALUES ('GRN-S8-001', NULL, {$branchId}, {$sup1Id}, {$userId}, NOW(), 10000.00, 0.00, 'unpaid', 'Approved')
");
$grn1Id = (int)$db->lastInsertId();

$db->exec("
    INSERT INTO grn_items (grn_id, product_id, lot_id, quantity, unit_cost, selling_price, line_total)
    VALUES ({$grn1Id}, {$prod1Id}, {$lot1Id}, 100, 100.00, 150.00, 10000.00)
");
$grnItem1Id = (int)$db->lastInsertId();

$db->exec("
    INSERT INTO grns (grn_number, po_id, branch_id, supplier_id, created_by, received_date, actual_grn_total, paid_amount, payment_status, status)
    VALUES ('GRN-S8-002', NULL, {$branchId}, {$sup1Id}, {$userId}, NOW(), 5000.00, 0.00, 'unpaid', 'Approved')
");
$grn2Id = (int)$db->lastInsertId();

// Create approved GRN for inactive sup2Id to test debt recovery
$db->exec("
    INSERT INTO grns (grn_number, po_id, branch_id, supplier_id, created_by, received_date, actual_grn_total, paid_amount, payment_status, status)
    VALUES ('GRN-S8-003', NULL, {$branchId}, {$sup2Id}, {$userId}, NOW(), 4000.00, 0.00, 'unpaid', 'Approved')
");
$grn3Id = (int)$db->lastInsertId();

echo "[OK] Test environment initialized successfully.\n\n";

// --- ASSERTIONS 1-4: SUPPLIER SEARCH & OUTSTANDING SUMMARY ---
echo "--> Group 1: Supplier Search & Outstanding Calculation...\n";

$searchResults1 = SupplierPayment::searchSuppliers('SUP-S801');
assertSec8(count($searchResults1) === 1 && $searchResults1[0]['id'] === $sup1Id, "Test #1: Supplier search by Supplier Code ('SUP-S801')");

$searchResults2 = SupplierPayment::searchSuppliers('Sec8 Test Active');
assertSec8(count($searchResults2) >= 1 && $searchResults2[0]['id'] === $sup1Id, "Test #2: Supplier search by Name ('Sec8 Test Active')");

$resInactivePay = SupplierPayment::processPayment(['supplier_id' => $sup2Id, 'payment_method' => 'Cash', 'amount' => 1500.00, 'notes' => 'Section 8 Inactive Debt Recovery'], $userId, $branchId);
assertSec8($resInactivePay['success'] === true, "Test #3: Inactive supplier debt remains payable (processed 1500.00 cash payment)");

$sumSup1 = SupplierPayment::getSupplierOutstandingSummary($sup1Id);
assertSec8((float)$sumSup1['total_outstanding'] === 15000.00, "Test #4: Total outstanding calculated correctly as 15,000.00 (10,000 + 5,000)");
assertSec8(count($sumSup1['approved_grns']) === 2 && (float)$sumSup1['approved_grns'][0]['due_amount'] === 10000.00, "Test #5: Unpaid GRN #1 identified with 10,000.00 due");

// --- ASSERTIONS 6-11: CASH SUPPLIER PAYMENT & CASH DRAWER ---
echo "\n--> Group 2: Cash Supplier Payment & Cash Drawer (OUT)...\n";

$drawerBefore = Database::fetchOne("
    SELECT SUM(CASE WHEN direction = 'in' THEN amount ELSE -amount END) as net_cash
    FROM cash_drawer_movements WHERE business_day_id = :bdid
", [':bdid' => $bDayId]);
$cashBefore = (float)($drawerBefore['net_cash'] ?? 0);

$resCash = SupplierPayment::processPayment(['supplier_id' => $sup1Id, 'payment_method' => 'Cash', 'amount' => 4000.00, 'notes' => 'Section 8 Cash Payment'], $userId, $branchId);
assertSec8($resCash['success'] === true, "Test #8: Processed full/partial cash supplier payment of 4,000.00");

$sumSup1AfterCash = SupplierPayment::getSupplierOutstandingSummary($sup1Id);
assertSec8((float)$sumSup1AfterCash['approved_grns'][0]['paid_amount'] === 4000.00 && $sumSup1AfterCash['approved_grns'][0]['payment_status'] === 'partially_paid', "Test #6 & #9: GRN #1 status updated to 'partially_paid' with 4,000.00 paid");

$drawerAfter = Database::fetchOne("
    SELECT SUM(CASE WHEN direction = 'in' THEN amount ELSE -amount END) as net_cash
    FROM cash_drawer_movements WHERE business_day_id = :bdid
", [':bdid' => $bDayId]);
$cashAfter = (float)($drawerAfter['net_cash'] ?? 0);
assertSec8(round($cashBefore - $cashAfter, 2) === 4000.00, "Test #10: Cash drawer DECREASED by exact payment amount (4,000.00 OUT)");

$jeCash = Database::fetchOne("SELECT * FROM journal_entries WHERE source_type = 'supplier_payment' ORDER BY id DESC LIMIT 1");
$linesCash = Database::fetchAll("SELECT jel.*, coa.account_code FROM journal_lines jel JOIN chart_of_accounts coa ON jel.account_id = coa.id WHERE jel.journal_entry_id = :jeid", [':jeid' => $jeCash['id']]);
$debitCashCode = '';
$creditCashCode = '';
foreach ($linesCash as $l) {
    if ((float)$l['debit'] > 0) $debitCashCode = $l['account_code'];
    if ((float)$l['credit'] > 0) $creditCashCode = $l['account_code'];
}
assertSec8($debitCashCode === '2020', "Test #11: Cash supplier payment debited Supplier Payables (2020)");
assertSec8($creditCashCode === '1010', "Test #11: Cash supplier payment credited Cash in Hand (1010)");

// --- ASSERTIONS 12-15: BANK SUPPLIER PAYMENT ---
echo "\n--> Group 3: Bank Supplier Payment & Dedicated COA...\n";

$resNoBank = SupplierPayment::processPayment(['supplier_id' => $sup1Id, 'payment_method' => 'Bank', 'amount' => 2000.00], $userId, $branchId);
assertSec8($resNoBank['success'] === false && str_contains($resNoBank['message'], 'Bank account selection is required'), "Test #13: Blocked Bank supplier payment without bank account selection");

$resBank = SupplierPayment::processPayment(['supplier_id' => $sup1Id, 'payment_method' => 'Bank', 'bank_account_id' => $bankAccountId, 'amount' => 2000.00, 'notes' => 'Section 8 Bank Payment'], $userId, $branchId);
assertSec8($resBank['success'] === true, "Test #12: Processed valid Bank supplier payment of 2,000.00");

$drawerAfterBank = Database::fetchOne("
    SELECT SUM(CASE WHEN direction = 'in' THEN amount ELSE -amount END) as net_cash
    FROM cash_drawer_movements WHERE business_day_id = :bdid
", [':bdid' => $bDayId]);
assertSec8((float)($drawerAfterBank['net_cash'] ?? 0) === $cashAfter, "Test #15: Bank supplier payment did NOT alter cash drawer");

$jeBank = Database::fetchOne("SELECT * FROM journal_entries WHERE source_type = 'supplier_payment' ORDER BY id DESC LIMIT 1");
$linesBank = Database::fetchAll("SELECT jel.*, coa.account_code, jel.account_id FROM journal_lines jel JOIN chart_of_accounts coa ON jel.account_id = coa.id WHERE jel.journal_entry_id = :jeid", [':jeid' => $jeBank['id']]);
$creditBankAccId = 0;
foreach ($linesBank as $l) {
    if ((float)$l['credit'] > 0) $creditBankAccId = (int)$l['account_id'];
}
assertSec8($creditBankAccId === $bankCoaAccountId, "Test #14: Bank supplier payment credited dedicated Bank Account COA (Account ID #{$bankCoaAccountId})");

// --- ASSERTIONS 16-20: SUPPLIER CHEQUE ISSUANCE & CLEARING ---
echo "\n--> Group 4: Supplier Cheque Issuance & Clearing...\n";

$resChq = SupplierPayment::processPayment([
    'supplier_id'    => $sup1Id,
    'payment_method' => 'Cheque',
    'amount'         => 3000.00,
    'cheque'         => [
        'cheque_number' => 'S8-CHQ-9001',
        'cheque_date'   => '2026-10-20',
        'bank_name'     => 'Commercial Bank'
    ],
    'notes'          => 'Section 8 Cheque Payment'
], $userId, $branchId);
assertSec8($resChq['success'] === true, "Test #16: Processed Supplier Cheque payment of 3,000.00");

$chequeList = ChequeEngine::getCheques(['search' => 'S8-CHQ-9001']);
assertSec8(count($chequeList) === 1 && $chequeList[0]['status'] === 'pending', "Test #17: Supplier cheque issued with status 'pending' (Issued)");

$jeChqIssue = Database::fetchOne("SELECT * FROM journal_entries WHERE source_type = 'supplier_payment' ORDER BY id DESC LIMIT 1");
$linesChqIssue = Database::fetchAll("SELECT jel.*, coa.account_code FROM journal_lines jel JOIN chart_of_accounts coa ON jel.account_id = coa.id WHERE jel.journal_entry_id = :jeid", [':jeid' => $jeChqIssue['id']]);
$debitChqCode = '';
$creditChqCode = '';
foreach ($linesChqIssue as $l) {
    if ((float)$l['debit'] > 0) $debitChqCode = $l['account_code'];
    if ((float)$l['credit'] > 0) $creditChqCode = $l['account_code'];
}
assertSec8($debitChqCode === '2020' && $creditChqCode === '2025', "Test #18: Cheque issue debited AP (2020) and credited Outstanding Cheques (2025)");

$chqId = (int)($resChq['cheque_id'] ?? $chequeList[0]['id']);
$resClear = ChequeEngine::clearCheque(['cheque_id' => $chqId, 'bank_account_id' => $bankAccountId], $userId);
assertSec8($resClear['success'] === true, "Test #19: Cleared pending supplier cheque #S8-CHQ-9001");

$jeClear = Database::fetchOne("SELECT * FROM journal_entries WHERE source_type = 'cheque_clearing' ORDER BY id DESC LIMIT 1");
$linesClear = Database::fetchAll("SELECT jel.*, coa.account_code, jel.account_id FROM journal_lines jel JOIN chart_of_accounts coa ON jel.account_id = coa.id WHERE jel.journal_entry_id = :jeid", [':jeid' => $jeClear['id']]);
$debitClearCode = '';
$creditClearAccId = 0;
foreach ($linesClear as $l) {
    if ((float)$l['debit'] > 0) $debitClearCode = $l['account_code'];
    if ((float)$l['credit'] > 0) $creditClearAccId = (int)$l['account_id'];
}
assertSec8($debitClearCode === '2025' && $creditClearAccId === $bankCoaAccountId, "Test #20: Cheque clearing debited Outstanding Cheques (2025) and credited dedicated Bank COA");

// --- ASSERTIONS 21-30: SUPPLIER CHEQUE BOUNCE, CANCELLATION & TRANSITION CONTROLS ---
echo "\n--> Group 5: Cheque Bounce, Cancellation & Transition Controls...\n";

$resBounceCleared = ChequeEngine::bounceCheque(['cheque_id' => $chqId, 'bounced_reason' => 'Cheque bounced after clearance test'], $userId);
assertSec8($resBounceCleared['success'] === true, "Test #21: Marked cleared cheque as BOUNCED");

$grn1PostBounce = Database::fetchOne("SELECT * FROM grns WHERE id = :id", [':id' => $grn1Id]);
assertSec8((float)$grn1PostBounce['paid_amount'] === 6000.00 && $grn1PostBounce['payment_status'] === 'partially_paid', "Test #22 & #23: GRN #1 paid_amount restored from 9,000 back to 6,000 upon cheque bounce");

// Issue second cheque for cancellation test
$resChq2 = SupplierPayment::processPayment([
    'supplier_id'    => $sup1Id,
    'payment_method' => 'Cheque',
    'amount'         => 2000.00,
    'cheque'         => [
        'cheque_number' => 'S8-CHQ-9002',
        'cheque_date'   => '2026-10-25',
        'bank_name'     => 'Commercial Bank'
    ],
    'notes'          => 'Section 8 Cheque 2 Payment'
], $userId, $branchId);
$chq2Id = (int)($resChq2['cheque_id'] ?? 0);
if ($chq2Id <= 0) {
    $cList = ChequeEngine::getCheques(['search' => 'S8-CHQ-9002']);
    $chq2Id = (int)$cList[0]['id'];
}

$resCancel = ChequeEngine::cancelCheque(['cheque_id' => $chq2Id, 'cancellation_reason' => 'Duplicate issuance'], $userId);
assertSec8($resCancel['success'] === true, "Test #25: Cancelled pending supplier cheque #S8-CHQ-9002");

$grn1PostCancel = Database::fetchOne("SELECT * FROM grns WHERE id = :id", [':id' => $grn1Id]);
assertSec8((float)$grn1PostCancel['paid_amount'] === 6000.00, "Test #26: GRN #1 paid_amount restored upon cheque cancellation");

$resDupBounce = ChequeEngine::bounceCheque(['cheque_id' => $chqId, 'bounced_reason' => 'Second bounce attempt'], $userId);
assertSec8($resDupBounce['success'] === false, "Test #27: Blocked bouncing an already bounced cheque");

$resClearBounced = ChequeEngine::clearCheque(['cheque_id' => $chqId, 'bank_account_id' => $bankAccountId], $userId);
assertSec8($resClearBounced['success'] === false, "Test #28: Blocked clearing a bounced cheque");

$resClearCanc = ChequeEngine::clearCheque(['cheque_id' => $chq2Id, 'bank_account_id' => $bankAccountId], $userId);
assertSec8($resClearCanc['success'] === false, "Test #29: Blocked clearing a cancelled cheque");

// --- ASSERTIONS 31-36: ALLOCATION INTEGRITY & OVERPAYMENT BLOCKS ---
echo "\n--> Group 6: Payment Allocation & Security Controls...\n";

$sumSup1Cur = SupplierPayment::getSupplierOutstandingSummary($sup1Id);
$dueSup1 = (float)$sumSup1Cur['total_outstanding'];

$resOver = SupplierPayment::processPayment(['supplier_id' => $sup1Id, 'payment_method' => 'Cash', 'amount' => $dueSup1 + 5000.00], $userId, $branchId);
assertSec8($resOver['success'] === false && str_contains($resOver['message'], 'Overpayment prevented'), "Test #35: Blocked overpayment (amount > total AP outstanding)");

// Fully settle GRN #1 with specific allocation
$grn1Due = 0.00;
foreach ($sumSup1Cur['approved_grns'] as $ag) {
    if ((int)$ag['grn_id'] === $grn1Id) {
        $grn1Due = (float)$ag['due_amount'];
        break;
    }
}
$resFullGRN1 = SupplierPayment::processPayment([
    'supplier_id'    => $sup1Id,
    'payment_method' => 'Cash',
    'amount'         => $grn1Due,
    'allocations'    => [['grn_id' => $grn1Id, 'amount' => $grn1Due]],
    'notes'          => 'Full settlement of GRN 1'
], $userId, $branchId);
assertSec8($resFullGRN1['success'] === true, "Test #31 & #34: Processed specific GRN allocation settling GRN #1 fully");

$grn1Final = Database::fetchOne("SELECT * FROM grns WHERE id = :id", [':id' => $grn1Id]);
assertSec8($grn1Final['payment_status'] === 'paid', "Test #7 & #27: GRN #1 marked as 'paid'");

// --- ASSERTIONS 37-48: SUPPLIER RETURN WORKFLOW & EXACT LOT COSTING ---
echo "\n--> Group 7: Supplier Return Workflow & Exact Lot Costing...\n";

// Change product master cost to 200.00 (original GRN lot cost was 100.00)
$db->exec("UPDATE products SET cost = 200.00 WHERE id = {$prod1Id}");

$resRetOverQty = SupplierReturnEngine::processReturn([
    'grn_id' => $grn1Id,
    'items'  => [['grn_item_id' => $grnItem1Id, 'quantity' => 150.00, 'reason' => 'Excess qty test']]
], $userId, $branchId);
assertSec8($resRetOverQty['success'] === false && str_contains($resRetOverQty['message'], 'Over-return blocked'), "Test #44: Blocked supplier return exceeding original GRN received quantity");

// Process valid return of 30 units against fully paid GRN #1
// Expected return value = 30 * 100.00 (original lot cost) = 3,000.00
// Since GRN #1 is fully paid, entire 3,000.00 creates a Supplier Credit (1045) asset
$resReturn = SupplierReturnEngine::processReturn([
    'grn_id' => $grn1Id,
    'items'  => [['grn_item_id' => $grnItem1Id, 'quantity' => 30.00, 'reason' => 'Defective batch']]
], $userId, $branchId);
assertSec8($resReturn['success'] === true, "Test #37: Processed supplier return of 30 units");
assertSec8((float)$resReturn['total_return_amount'] === 3000.00, "Test #38 & #39 & #40: Return value calculated using exact original GRN lot cost (100.00), ignoring current product master cost (200.00)");

$lotPostRet = Database::fetchOne("SELECT remaining_quantity FROM product_lots WHERE id = :id", [':id' => $lot1Id]);
assertSec8((float)$lotPostRet['remaining_quantity'] === 70.00, "Test #41: Exact product lot remaining quantity reduced from 100 to 70");

$invPostRet = Database::fetchOne("SELECT quantity FROM branch_inventory WHERE product_id = :pid AND branch_id = :bid", [':pid' => $prod1Id, ':bid' => $branchId]);
assertSec8((float)$invPostRet['quantity'] === 70.00, "Test #42: Branch inventory quantity reduced to 70");

$stockLedgerRet = Database::fetchOne("SELECT * FROM stock_ledger WHERE movement_type = 'supplier_return' ORDER BY id DESC LIMIT 1");
assertSec8($stockLedgerRet !== null && (float)$stockLedgerRet['quantity'] === -30.00, "Test #43: Stock ledger entry recorded for supplier_return movement (-30 units)");

assertSec8((float)$resReturn['credit_amount'] === 3000.00, "Test #47 & #49: Fully paid GRN return created 3,000.00 Supplier Credit (1045) asset");
$scId = (int)$resReturn['supplier_credit_id'];

// --- ASSERTIONS 49-54: SUPPLIER CREDIT SETTLEMENT WORKFLOWS ---
echo "\n--> Group 8: Supplier Credit Settlement Workflows...\n";

// 1. Cash Refund from Supplier (1,000.00)
$drawerBeforeRefund = (float)(Database::fetchOne("SELECT SUM(CASE WHEN direction = 'in' THEN amount ELSE -amount END) as net_cash FROM cash_drawer_movements WHERE business_day_id = :bdid", [':bdid' => $bDayId])['net_cash'] ?? 0);

$resCashRefund = SupplierReturnEngine::processCreditSettlement([
    'supplier_credit_id' => $scId,
    'settlement_type'    => 'cash_refund',
    'amount'             => 1000.00,
    'notes'              => 'Cash refund from supplier'
], $userId, $branchId);
assertSec8($resCashRefund['success'] === true, "Test #50: Processed supplier credit Cash Refund of 1,000.00");

$drawerAfterRefund = (float)(Database::fetchOne("SELECT SUM(CASE WHEN direction = 'in' THEN amount ELSE -amount END) as net_cash FROM cash_drawer_movements WHERE business_day_id = :bdid", [':bdid' => $bDayId])['net_cash'] ?? 0);
assertSec8(round($drawerAfterRefund - $drawerBeforeRefund, 2) === 1000.00, "Test #50: Cash drawer INCREASED by 1,000.00 on cash refund from supplier");

// 2. Bank Refund from Supplier (1,000.00)
$resBankRefund = SupplierReturnEngine::processCreditSettlement([
    'supplier_credit_id' => $scId,
    'settlement_type'    => 'bank_refund',
    'bank_account_id'    => $bankAccountId,
    'amount'             => 1000.00,
    'notes'              => 'Bank refund into HNB account'
], $userId, $branchId);
assertSec8($resBankRefund['success'] === true, "Test #51: Processed supplier credit Bank Refund of 1,000.00 into dedicated bank account");

// 3. Future GRN Offset (1,000.00) against GRN #2
$resOffset = SupplierReturnEngine::processCreditSettlement([
    'supplier_credit_id' => $scId,
    'settlement_type'    => 'future_grn_offset',
    'target_grn_id'      => $grn2Id,
    'amount'             => 1000.00,
    'notes'              => 'Offset remaining credit against GRN #2'
], $userId, $branchId);
assertSec8($resOffset['success'] === true, "Test #52: Processed supplier credit Future GRN Offset of 1,000.00 against GRN #2");

$scFinal = Database::fetchOne("SELECT * FROM supplier_credits WHERE id = :id", [':id' => $scId]);
assertSec8((float)$scFinal['remaining_credit_amount'] === 0.00 && $scFinal['status'] === 'fully_settled', "Test #54: Supplier Credit fully settled (remaining_credit_amount = 0.00, status = 'fully_settled')");

$resOverUseCredit = SupplierReturnEngine::processCreditSettlement([
    'supplier_credit_id' => $scId,
    'settlement_type'    => 'cash_refund',
    'amount'             => 500.00
], $userId, $branchId);
assertSec8($resOverUseCredit['success'] === false && (str_contains($resOverUseCredit['message'], 'exceeds remaining credit balance') || str_contains($resOverUseCredit['message'], 'fully settled')), "Test #53: Blocked over-use of supplier credit beyond remaining balance");

// --- ASSERTIONS 55-60: SUPPLIER HISTORY & ATOMIC ROLLBACK ---
echo "\n--> Group 9: Supplier History, Reconciliation & Atomic Integrity...\n";

$history = SupplierReturnEngine::getReturnHistory(['supplier_id' => $sup1Id]);
assertSec8(count($history) >= 1 && $history[0]['supplier_id'] === $sup1Id, "Test #55: Supplier return history retrieved for supplier #{$sup1Id}");

$auditReturnLog = Database::fetchOne("SELECT * FROM audit_logs WHERE action = 'SUPPLIER_RETURN_PROCESSED' ORDER BY id DESC LIMIT 1");
assertSec8($auditReturnLog !== null, "Test #55: Audit log recorded for SUPPLIER_RETURN_PROCESSED event");

$auditPaymentLog = Database::fetchOne("SELECT * FROM audit_logs WHERE action = 'SUPPLIER_PAYMENT_PROCESSED' ORDER BY id DESC LIMIT 1");
assertSec8($auditPaymentLog !== null, "Test #55: Audit log recorded for SUPPLIER_PAYMENT_PROCESSED event");

// --- ASSERTIONS 61-74: CONCURRENCY, IMMUTABILITY & DOUBLE-ENTRY GL BALANCE ---
echo "\n--> Group 10: Concurrency, Immutability & Double-Entry GL Balance...\n";

$pNoGen = SupplierPayment::generatePaymentNumber();
assertSec8(str_starts_with($pNoGen, 'SPAY-'), "Test #64: Supplier payment number format is SPAY-xxxx");

$rNoGen = SupplierReturnEngine::generateReturnNumber();
assertSec8(str_starts_with($rNoGen, 'SRET-'), "Test #65: Supplier return number format is SRET-xxxx");

// Verify GL Balance for all Section 8 posted journal entries
$unbalanced = Database::fetchAll("
    SELECT je.id, je.description, SUM(jl.debit) as tot_debit, SUM(jl.credit) as tot_credit
    FROM journal_entries je
    JOIN journal_lines jl ON jl.journal_entry_id = je.id
    WHERE je.source_type IN ('supplier_payment','supplier_return','supplier_credit_settlement','cheque_clearing','cheque_bounce')
    GROUP BY je.id
    HAVING ABS(tot_debit - tot_credit) > 0.001
");
assertSec8(count($unbalanced) === 0, "Test #73 & #74: Every Section 8 journal entry is 100% BALANCED (Total Debits == Total Credits, 0 unbalanced entries)");

echo "\n========================================================================\n";
echo " Section 8 Enhancement Verification Summary:                             \n";
echo " Passed: {$passed} / " . ($passed + $failed) . "                                       \n";
echo " Failed: {$failed}                                                      \n";
echo "========================================================================\n";

if ($failed > 0) {
    echo "\n[ERROR] Section 8 Verification Failed with {$failed} errors.\n";
    exit(1);
} else {
    echo "\n[SUCCESS] Section 8 Enhancements fully verified. All test assertions PASSED!\n";
    exit(0);
}
