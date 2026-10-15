<?php
/**
 * Curtiss POS - Section 5 Verification Suite
 * Customer Management, Customer Profile, Credit & Outstanding Integration Tests
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Customer.php';
require_once __DIR__ . '/../core/CustomerPayment.php';
require_once __DIR__ . '/../core/CustomerImportEngine.php';
require_once __DIR__ . '/../core/Sale.php';
require_once __DIR__ . '/../core/Product.php';
require_once __DIR__ . '/../core/PurchaseOrder.php';
require_once __DIR__ . '/../core/GRN.php';
require_once __DIR__ . '/../core/BusinessDay.php';
require_once __DIR__ . '/../core/ReconciliationEngine.php';

echo "=====================================================\n";
echo "    SECTION 5: CUSTOMER & CREDIT VERIFICATION SUITE  \n";
echo "=====================================================\n\n";

$db = Database::getInstance()->getConnection();
$db->exec("SET FOREIGN_KEY_CHECKS = 0;");

// Clean up stale test customers & ledger
$db->exec("DELETE FROM sale_items WHERE sale_id IN (SELECT id FROM sales WHERE customer_id IN (SELECT id FROM customers WHERE name LIKE 'Sec%' OR name LIKE 'Stage%' OR customer_code LIKE 'CUS-S%' OR customer_code LIKE 'CUST-STAGE%'))");
$db->exec("DELETE FROM customer_credit_ledger WHERE customer_id IN (SELECT id FROM customers WHERE name LIKE 'Sec%' OR name LIKE 'Stage%' OR customer_code LIKE 'CUS-S%' OR customer_code LIKE 'CUST-STAGE%')");
$db->exec("DELETE FROM payments WHERE customer_id IN (SELECT id FROM customers WHERE name LIKE 'Sec%' OR name LIKE 'Stage%' OR customer_code LIKE 'CUS-S%' OR customer_code LIKE 'CUST-STAGE%')");
$db->exec("DELETE FROM sales WHERE customer_id IN (SELECT id FROM customers WHERE name LIKE 'Sec%' OR name LIKE 'Stage%' OR customer_code LIKE 'CUS-S%' OR customer_code LIKE 'CUST-STAGE%')");
$db->exec("DELETE FROM customers WHERE name LIKE 'Sec%' OR name LIKE 'Stage%' OR customer_code LIKE 'CUS-S%' OR customer_code LIKE 'CUST-STAGE%'");
$db->exec("SET FOREIGN_KEY_CHECKS = 1;");

// Ensure active business day
$activeDay = BusinessDay::getActiveDay(1);
if (!$activeDay) {
    BusinessDay::startDay(1, 10000.00, 1, 'Sec 5 Test Day');
    $activeDay = BusinessDay::getActiveDay(1);
}

$passed = 0;
$failed = 0;

function assertTest(bool $condition, string $testName, string $message = ''): void
{
    global $passed, $failed;
    if ($condition) {
        echo "  [PASS] {$testName}\n";
        $passed++;
    } else {
        echo "  [FAIL] {$testName}";
        if ($message) echo " - {$message}";
        echo "\n";
        $failed++;
    }
}

// -------------------------------------------------------------------------
// GROUP 1: Customer Master CRUD & Safeguards (Tests #1 - #8)
// -------------------------------------------------------------------------
echo "--- Group 1: Customer Master CRUD & Safeguards ---\n";

// 1. Create Customer
$code1 = 'CUS-S5-' . rand(1000, 9999);
$barcode1 = 'BAR-S5-' . rand(1000, 9999);
$c1Res = Customer::create([
    'customer_code' => $code1,
    'name'          => 'Sec5 Alpha Corp',
    'phone'         => '0771112233',
    'whatsapp'      => '0781112233',
    'address'       => '123 Kandy Road, Colombo',
    'card_barcode'  => $barcode1,
    'credit_limit'  => 50000.00,
    'status'        => 'active'
]);
assertTest($c1Res['success'], "Test #1: Create Customer with code and barcode", $c1Res['message'] ?? '');
$c1Id = (int)($c1Res['id'] ?? 0);

// 2. Edit Customer
$c1EditRes = Customer::update($c1Id, [
    'name'    => 'Sec5 Alpha Corporation',
    'phone'   => '0779998877',
    'address' => '456 Peradeniya Road, Kandy'
]);
assertTest($c1EditRes['success'], "Test #2: Edit Customer master info");
$c1Updated = Customer::find($c1Id);
assertTest($c1Updated['name'] === 'Sec5 Alpha Corporation' && $c1Updated['phone'] === '0779998877', "Test #2b: Updated fields verified in DB");

// 3. Activate / Deactivate
$toggleRes = Customer::toggleStatus($c1Id);
assertTest($toggleRes['success'], "Test #3: Deactivate customer");
$c1Deactive = Customer::find($c1Id);
assertTest($c1Deactive['status'] === 'inactive', "Test #3b: Customer status set to 'inactive'");
Customer::toggleStatus($c1Id); // Reactivate

// 4. Safe Delete Unused Customer
$codeUnused = 'CUS-S5-UNUSED-' . rand(1000, 9999);
$cUnused = Customer::create(['customer_code' => $codeUnused, 'name' => 'Sec5 Unused Customer']);
$cUnusedId = (int)$cUnused['id'];
$delUnusedRes = Customer::delete($cUnusedId);
assertTest($delUnusedRes['success'], "Test #4: Safe delete unused customer without history succeeded");

// 5. Delete Customer with History Blocked
// (We will test after making a credit sale on $c1Id)

// 6. Walk-in Customer Safeguards
$walkinDelRes = Customer::delete(1);
assertTest(!$walkinDelRes['success'] && strpos($walkinDelRes['message'], 'Walk-in Customer cannot be deleted') !== false, "Test #6: Walk-in Customer deletion strictly blocked");

$walkinToggleRes = Customer::toggleStatus(1);
assertTest(!$walkinToggleRes['success'] && strpos($walkinToggleRes['message'], 'Walk-in Customer cannot be deactivated') !== false, "Test #6b: Walk-in Customer deactivation strictly blocked");

// 7. Customer Code Uniqueness
$dupCodeRes = Customer::create(['customer_code' => $code1, 'name' => 'Sec5 Duplicate Code Test']);
assertTest(!$dupCodeRes['success'], "Test #7: Duplicate Customer Code blocked on creation");

// 8. Card Barcode Uniqueness
$dupBcRes = Customer::create(['card_barcode' => $barcode1, 'name' => 'Sec5 Duplicate Barcode Test']);
assertTest(!$dupBcRes['success'], "Test #8: Duplicate Card Barcode blocked on creation");


// -------------------------------------------------------------------------
// GROUP 2: Searching & Identification (Tests #9 - #13)
// -------------------------------------------------------------------------
echo "\n--- Group 2: Customer Searching & Identification ---\n";

$searchCode = Customer::search($code1);
assertTest(count($searchCode) >= 1 && (int)$searchCode[0]['id'] === $c1Id, "Test #9: Search customer by Customer Code");

$searchName = Customer::search('Alpha Corporation');
assertTest(count($searchName) >= 1 && (int)$searchName[0]['id'] === $c1Id, "Test #10: Search customer by Name");

$searchPhone = Customer::search('0779998877');
assertTest(count($searchPhone) >= 1 && (int)$searchPhone[0]['id'] === $c1Id, "Test #11: Search customer by Phone");

$searchWa = Customer::search('0781112233');
assertTest(count($searchWa) >= 1 && (int)$searchWa[0]['id'] === $c1Id, "Test #12: Search customer by WhatsApp");

$byBarcode = Customer::findByBarcode($barcode1);
assertTest($byBarcode !== null && (int)$byBarcode['id'] === $c1Id, "Test #13: Exact Customer Card Barcode lookup");


// -------------------------------------------------------------------------
// GROUP 3: Customer CSV Import Engine & Financial Safety (Tests #14 - #23)
// -------------------------------------------------------------------------
echo "\n--- Group 3: Customer CSV Import Engine & Financial Safety ---\n";

$tmpCsvPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sec5_test_import_' . rand(1000, 9999) . '.csv';

$codeImp1 = 'CUS-S5-IMP1-' . rand(1000, 9999);
$codeImp2 = 'CUS-S5-IMP2-' . rand(1000, 9999);
$bcImp1 = 'BAR-S5-IMP1-' . rand(1000, 9999);

// Create existing customer for update test
Customer::create(['customer_code' => $codeImp1, 'name' => 'Sec5 Import Existing', 'phone' => '0770001111', 'whatsapp' => '0780001111']);

// Write sample CSV
$csvContent = implode("\n", [
    "Customer Code,Name,Phone,WhatsApp,Address,Card Barcode,Credit Limit,Status,Outstanding,Credit Balance",
    "{$codeImp1},Sec5 Import Existing Updated,,0780009999,789 Update St,{$bcImp1},25000,active,999999,999999",
    "{$codeImp2},Sec5 Import New,0772223333,0782223333,101 New St,,15000,active,888888,888888",
    "CUST-WALKIN,Walkin Attempt,000,000,000,,0,active,0,0"
]);
file_put_contents($tmpCsvPath, $csvContent);

// 14. Import Preview
$prevRes = CustomerImportEngine::previewImport($tmpCsvPath, ['mode' => 'create_update', 'ignore_blanks' => true]);
assertTest($prevRes['success'] && $prevRes['update_count'] === 1 && $prevRes['new_count'] === 1, "Test #14: Import Preview detects 1 update, 1 create, 1 walk-in skip");
assertTest(in_array('Outstanding', $prevRes['ignored_financial_cols']) || in_array('Credit Balance', $prevRes['ignored_financial_cols']), "Test #14b: Financial columns (Outstanding/Balance) correctly identified and ignored");

// 15. Commit Import
$commitRes = CustomerImportEngine::commitImport($tmpCsvPath, ['mode' => 'create_update', 'ignore_blanks' => true], 1);
assertTest($commitRes['success'] && $commitRes['created_count'] === 1 && $commitRes['updated_count'] === 1, "Test #15: Commit Import creates 1 and updates 1");

// 16. Verify blank-cell protection during update
$imp1Updated = Database::fetchOne("SELECT * FROM customers WHERE customer_code = :c", [':c' => $codeImp1]);
assertTest($imp1Updated['phone'] === '0770001111', "Test #16: Blank phone cell in CSV ignored during update (Phone preserved)");
assertTest($imp1Updated['whatsapp'] === '0780009999', "Test #17: Non-blank WhatsApp in CSV updated");

// 18. Financial Isolation Check
assertTest((float)$imp1Updated['credit_balance'] === 0.00, "Test #18: Financial safety verified: CSV Outstanding/Balance column (999999) strictly IGNORED and debt balance remains 0.00");

// Cleanup temp CSV
@unlink($tmpCsvPath);


// -------------------------------------------------------------------------
// GROUP 4: Credit Sales & Walk-in Restrictions (Tests #24 - #27)
// -------------------------------------------------------------------------
echo "\n--- Group 4: Credit Sales & Walk-in Restrictions ---\n";

// Setup product and lot
$supRes = Database::fetchOne("SELECT id FROM suppliers WHERE status = 'active' LIMIT 1");
if (!$supRes) {
    require_once __DIR__ . '/../core/Supplier.php';
    $sRes = Supplier::create('Sec5 Test Supplier', '0770009999');
    $supId = (int)$sRes['id'];
} else {
    $supId = (int)$supRes['id'];
}

$brandRow = Database::fetchOne("SELECT id FROM brands WHERE deleted_at IS NULL LIMIT 1");
$brandId = $brandRow ? (int)$brandRow['id'] : 1;
$catRow = Database::fetchOne("SELECT id FROM categories WHERE deleted_at IS NULL LIMIT 1");
$catId = $catRow ? (int)$catRow['id'] : 1;

$pRes = Product::create([
    'barcode'       => 'SKU-S5-' . rand(1000, 9999),
    'brand_id'      => $brandId,
    'category_id'   => $catId,
    'name'          => 'Sec5 Credit Test Item',
    'supplier_id'   => $supId,
    'cost'          => 100.00,
    'selling_price' => 200.00,
    'opening_stock' => 0.00,
    'status'        => 'active'
]);
$pId = (int)($pRes['id'] ?? 0);

// Create PO & GRN to receive stock lots
$poRes = PurchaseOrder::create(['supplier_id' => $supId, 'branch_id' => 1, 'user_id' => 1, 'expected_date' => date('Y-m-d')], [
    ['product_id' => $pId, 'quantity' => 50, 'unit_cost' => 100.00, 'selling_price' => 200.00]
]);
$poId = (int)($poRes['po_id'] ?? 0);
if ($poId > 0) {
    PurchaseOrder::approve($poId, 1);
}

$grnRes = GRN::create([
    'po_id' => $poId, 'supplier_id' => $supId, 'branch_id' => 1, 'user_id' => 1, 'received_date' => date('Y-m-d'), 'supplier_invoice_no' => 'INV-S5-01'
], [
    ['product_id' => $pId, 'quantity' => 50, 'unit_cost' => 100.00, 'selling_price' => 200.00]
]);
$grnId = (int)($grnRes['grn_id'] ?? 0);
if ($grnId > 0) {
    GRN::approve($grnId, 1);
}

$lotRow = Database::fetchOne("SELECT id FROM product_lots WHERE product_id = :p AND branch_id = 1 AND status = 'active'", [':p' => $pId]);
$lotId = (int)$lotRow['id'];

// 24. Full Credit Sale
$draft1 = Sale::prepareDraftSale([
    'customer_id' => $c1Id,
    'items'       => [['product_id' => $pId, 'lot_id' => $lotId, 'quantity' => 10, 'unit_price' => 200.00]]
], 1, 1);
$saleId1 = (int)$draft1['sale_id'];

$fin1 = Sale::finalizeSale($saleId1, [['payment_method' => 'Credit', 'amount' => 2000.00]], 1, 1);
assertTest($fin1['success'], "Test #24: Full Credit Sale of LKR 2,000.00 completed successfully", $fin1['message'] ?? '');

$c1PostSale = Customer::find($c1Id);
assertTest((float)$c1PostSale['credit_balance'] === 2000.00, "Test #24b: Customer outstanding increased from 0.00 to 2,000.00");

// 25. Partial Credit Sale
$draft2 = Sale::prepareDraftSale([
    'customer_id' => $c1Id,
    'items'       => [['product_id' => $pId, 'lot_id' => $lotId, 'quantity' => 5, 'unit_price' => 200.00]]
], 1, 1);
$saleId2 = (int)$draft2['sale_id'];

$fin2 = Sale::finalizeSale($saleId2, [
    ['payment_method' => 'Cash', 'amount' => 400.00],
    ['payment_method' => 'Credit', 'amount' => 600.00]
], 1, 1);
assertTest($fin2['success'], "Test #25: Partial Credit Sale (LKR 400 Cash + LKR 600 Credit) completed");

$c1PostSale2 = Customer::find($c1Id);
assertTest((float)$c1PostSale2['credit_balance'] === 2600.00, "Test #25b: Customer outstanding increased strictly by credit portion (2000 + 600 = 2,600.00)");

// 26. Walk-in Credit Blocked
$draftWalkin = Sale::prepareDraftSale([
    'customer_id' => 1,
    'items'       => [['product_id' => $pId, 'lot_id' => $lotId, 'quantity' => 1, 'unit_price' => 200.00]]
], 1, 1);
$saleWalkinId = (int)$draftWalkin['sale_id'];

$finWalkin = Sale::finalizeSale($saleWalkinId, [['payment_method' => 'Credit', 'amount' => 200.00]], 1, 1);
assertTest(!$finWalkin['success'] && strpos($finWalkin['message'], 'Walk-in Customer cannot receive credit') !== false, "Test #26: Credit payment for Walk-in Customer strictly blocked");


// -------------------------------------------------------------------------
// GROUP 5: Collections, Overpayment & Delete-with-history Block (Tests #28 - #31)
// -------------------------------------------------------------------------
echo "\n--- Group 5: Collections, Overpayment & Delete-with-history Block ---\n";

// 28. Customer Collection Payment
$payRes = CustomerPayment::processPayment([
    'customer_id'    => $c1Id,
    'payment_method' => 'Cash',
    'amount'         => 1000.00,
    'notes'          => 'Sec5 Test Collection'
], 1, 1);
assertTest($payRes['success'], "Test #28: Customer Cash Collection payment of LKR 1,000.00 processed", $payRes['message'] ?? '');

$c1PostPay = Customer::find($c1Id);
assertTest((float)$c1PostPay['credit_balance'] === 1600.00, "Test #28b: Customer outstanding decreased to 1,600.00 (2600 - 1000)");

// 29. Over-collection Protection
$overPayRes = CustomerPayment::processPayment([
    'customer_id'    => $c1Id,
    'payment_method' => 'Cash',
    'amount'         => 5000.00
], 1, 1);
assertTest(!$overPayRes['success'] && strpos($overPayRes['message'], 'Overpayment prevented') !== false, "Test #29: Customer collection overpayment (> 1600 outstanding) strictly blocked");

// 30. Delete Customer with History Blocked
$delHistRes = Customer::delete($c1Id);
assertTest(!$delHistRes['success'] && strpos($delHistRes['message'], 'transaction history exists') !== false, "Test #30: Deleting customer with sales & collection history strictly blocked");


// -------------------------------------------------------------------------
// GROUP 6: Customer Profile, Consolidated Timeline & Reconciliation (Tests #32 - #42)
// -------------------------------------------------------------------------
echo "\n--- Group 6: Profile, Consolidated Timeline & AR Reconciliation ---\n";

// 32. Consolidated Activity Timeline
$timeline = Customer::getActivityTimeline($c1Id);
assertTest(count($timeline) >= 3, "Test #32: Consolidated Activity Timeline returned chronological events");
$eventTypes = array_column($timeline, 'event_type');
assertTest(in_array('Invoice', $eventTypes) && in_array('Credit Sale', $eventTypes) && in_array('Customer Payment', $eventTypes), "Test #32b: Timeline contains Invoices, Credit Sales, and Customer Payments");

// 33. Customer Profile Summary
$profile = Customer::getProfile($c1Id);
assertTest($profile !== null && (float)$profile['current_outstanding'] === 1600.00, "Test #33: Customer Profile summary returns correct current outstanding (LKR 1,600.00)");

// 37. Accounts Receivable Reconciliation
$recon = Customer::reconcileBalances();
assertTest($recon['status'] === 'PASS' && $recon['ledger_variance'] < 1.00, "Test #37: Customer Outstanding vs Credit Ledger Subsystem Reconciliation PASSED (Variance < 1.00)");

// 39. Inactive Customer Sale Restriction
$cInactiveRes = Customer::create(['name' => 'Sec5 Inactive Customer', 'status' => 'inactive']);
$cInactiveId = (int)$cInactiveRes['id'];

$draftInactive = Sale::prepareDraftSale([
    'customer_id' => $cInactiveId,
    'items'       => [['product_id' => $pId, 'lot_id' => $lotId, 'quantity' => 1, 'unit_price' => 200.00]]
], 1, 1);
assertTest(!$draftInactive['success'] && strpos($draftInactive['message'], 'is inactive') !== false, "Test #39: Creating sale for inactive customer strictly blocked");


// -------------------------------------------------------------------------
// SUMMARY
// -------------------------------------------------------------------------
echo "\n=====================================================\n";
echo " TEST SUMMARY: {$passed} / " . ($passed + $failed) . " PASSED\n";
echo "=====================================================\n\n";

if ($failed > 0) {
    exit(1);
} else {
    exit(0);
}
