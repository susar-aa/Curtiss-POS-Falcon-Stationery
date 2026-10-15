<?php
/**
 * Automated CLI Test Suite for Goods Received Notes (GRN) & GRN Creation Panel
 * Strictly follows CURTISS_MASTER_RULES:
 * - Isolated test fixtures
 * - Clean cleanup without TRUNCATE
 * - Multi-column and monetary search verification
 * - COA Double-entry and inventory lot posting verification
 * - Role & branch resolution
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/GRN.php';
require_once __DIR__ . '/../core/PurchaseOrder.php';
require_once __DIR__ . '/../core/Supplier.php';
require_once __DIR__ . '/../core/Product.php';
require_once __DIR__ . '/../core/Inventory.php';
require_once __DIR__ . '/../core/Accounting.php';

$passCount = 0;
$failCount = 0;

function assertTest($description, $condition) {
    global $passCount, $failCount;
    if ($condition) {
        echo "[PASS] " . $description . PHP_EOL;
        $passCount++;
    } else {
        echo "[FAIL] " . $description . PHP_EOL;
        $failCount++;
    }
}

echo "=== STARTING GRN & GRN CREATION AUTOMATION TEST SUITE ===" . PHP_EOL . PHP_EOL;

// 1. Verify Database Composite Indexes
$indexes = Database::fetchAll("SHOW INDEX FROM grns");
$indexNames = array_unique(array_column($indexes, 'Key_name'));
assertTest("Composite Index 'idx_grn_br_id' exists on grns", in_array('idx_grn_br_id', $indexNames));
assertTest("Composite Index 'idx_grn_br_status' exists on grns", in_array('idx_grn_br_status', $indexNames));
assertTest("Composite Index 'idx_grn_br_supp' exists on grns", in_array('idx_grn_br_supp', $indexNames));

// 2. Setup isolated test data
$user = Database::fetchOne("SELECT id, full_name FROM users LIMIT 1");
$userId = (int)($user['id'] ?? 1);
$branchId = 1;

$supplier = Database::fetchOne("SELECT id, name FROM suppliers WHERE status = 'active' AND deleted_at IS NULL LIMIT 1");
if (!$supplier) {
    Database::execute("INSERT INTO suppliers (name, contact_person, phone, email, status, created_at) VALUES ('GRN Test Supplier', 'Bob Smith', '0772223344', 'grn_supp@test.com', 'active', NOW())");
    $suppId = (int)Database::lastInsertId();
} else {
    $suppId = (int)$supplier['id'];
}

$product = Database::fetchOne("SELECT id, name, cost, selling_price, has_expiry FROM products WHERE status = 'active' AND deleted_at IS NULL LIMIT 1");
if (!$product) {
    Database::execute("INSERT INTO products (name, sku, barcode, cost, selling_price, status, created_at) VALUES ('GRN Test Item', 'GRN-SKU-01', '8999990001', 500.00, 750.00, 'active', NOW())");
    $prodId = (int)Database::lastInsertId();
    $prodCost = 500.00;
    $prodSell = 750.00;
} else {
    $prodId = (int)$product['id'];
    $prodCost = (float)$product['cost'];
    $prodSell = (float)$product['selling_price'];
}

// 3. Test Validation Rules
$resNoSupp = GRN::create([
    'supplier_id' => 0,
    'branch_id' => $branchId,
    'supplier_invoice_no' => 'INV-TEST-001'
], [['product_id' => $prodId, 'quantity' => 5, 'unit_cost' => 500.00, 'selling_price' => 750.00]]);
assertTest("GRN creation rejects missing supplier_id", $resNoSupp['success'] === false);

$resNoInv = GRN::create([
    'supplier_id' => $suppId,
    'branch_id' => $branchId,
    'supplier_invoice_no' => ''
], [['product_id' => $prodId, 'quantity' => 5, 'unit_cost' => 500.00, 'selling_price' => 750.00]]);
assertTest("GRN creation rejects empty supplier_invoice_no", $resNoInv['success'] === false);

$resNoItems = GRN::create([
    'supplier_id' => $suppId,
    'branch_id' => $branchId,
    'supplier_invoice_no' => 'INV-TEST-001'
], []);
assertTest("GRN creation rejects empty items list", $resNoItems['success'] === false);

// 4. Test Successful GRN Creation (Draft status with Variance Calculation)
$grnQty = 20.00;
$grnUnitCost = 750.00; // 20 * 750 = 15,000.00
$statedInvoiceAmt = 16200.00; // 1,200.00 variance
$varianceExpected = $statedInvoiceAmt - (20 * 750.00); // +1,200.00

$grnHeader = [
    'supplier_id'             => $suppId,
    'branch_id'               => $branchId,
    'supplier_invoice_no'     => 'INV-GRN-UNIQUE-' . time(),
    'supplier_invoice_date'   => '2026-10-15',
    'payment_due_date'        => '2026-11-15',
    'received_date'           => '2026-10-15',
    'supplier_invoice_amount' => $statedInvoiceAmt,
    'difference_reason'       => 'Price Variance',
    'notes'                   => 'CLI automated GRN test record GRNKEY99',
    'user_id'                 => $userId
];
$grnItems = [
    [
        'product_id'    => $prodId,
        'quantity'      => $grnQty,
        'unit_cost'     => $grnUnitCost,
        'selling_price' => 1100.00,
        'expiry_date'   => '2027-10-15',
        'batch_number'  => 'BATCH-GRN-99'
    ]
];

// Record initial branch stock before GRN
$initialStockRow = Database::fetchOne("SELECT quantity FROM branch_inventory WHERE branch_id = :branch_id AND product_id = :product_id", [
    ':branch_id' => $branchId,
    ':product_id' => $prodId
]);
$initialStock = (float)($initialStockRow['quantity'] ?? 0);

$resCreate = GRN::create($grnHeader, $grnItems);
assertTest("GRN created successfully in Draft status", $resCreate['success'] === true && !empty($resCreate['grn_id']));
$testGrnId = (int)($resCreate['grn_id'] ?? 0);
$testGrnNumber = $resCreate['grn_number'] ?? '';

// Verify calculations in DB
$createdGrn = GRN::find($testGrnId);
assertTest("GRN retrieved by find()", $createdGrn !== null);
assertTest("GRN initial status is 'Draft'", $createdGrn['status'] === 'Draft');
assertTest("Actual GRN Total accurately calculated (15,000.00)", (float)$createdGrn['actual_grn_total'] === 15000.00);
assertTest("Supplier Invoice Amount recorded (16,200.00)", (float)$createdGrn['supplier_invoice_amount'] === 16200.00);
assertTest("Variance Amount accurately calculated (1,200.00)", (float)$createdGrn['difference_amount'] === 1200.00);
assertTest("Variance Reason recorded ('Price Variance')", $createdGrn['difference_reason'] === 'Price Variance');

// 5. Test Premature Stock Posting Prevention (Rule 8)
$stockDuringDraft = (float)(Database::fetchOne("SELECT quantity FROM branch_inventory WHERE branch_id = :branch_id AND product_id = :product_id", [
    ':branch_id' => $branchId,
    ':product_id' => $prodId
])['quantity'] ?? 0);
assertTest("Draft GRN does NOT prematurely alter branch inventory stock", $stockDuringDraft === $initialStock);

// 6. Test Multi-Column and Monetary Search Verification
// A. Search by GRN Number
$sGrnNum = GRN::getList($branchId, ['search' => $testGrnNumber]);
assertTest("Search finds GRN by exact GRN Number ({$testGrnNumber})", in_array($testGrnId, array_column($sGrnNum['items'], 'id')));

// B. Search by Supplier Invoice No
$sInvNo = GRN::getList($branchId, ['search' => $grnHeader['supplier_invoice_no']]);
assertTest("Search finds GRN by Supplier Invoice No", in_array($testGrnId, array_column($sInvNo['items'], 'id')));

// C. Search by Raw Actual GRN Total (15000)
$sRawAmt = GRN::getList($branchId, ['search' => '15000']);
assertTest("Search finds GRN by raw actual total (15000)", in_array($testGrnId, array_column($sRawAmt['items'], 'id')));

// D. Search by Formatted Actual GRN Total (15,000.00)
$sFmtAmt = GRN::getList($branchId, ['search' => '15,000.00']);
assertTest("Search finds GRN by comma-formatted total (15,000.00)", in_array($testGrnId, array_column($sFmtAmt['items'], 'id')));

// E. Search by Currency Prefix 'LKR 15,000.00'
$sLkrAmt = GRN::getList($branchId, ['search' => 'LKR 15,000.00']);
assertTest("Search finds GRN by currency prefix 'LKR 15,000.00'", in_array($testGrnId, array_column($sLkrAmt['items'], 'id')));

// F. Search by Currency Prefix 'Rs. 15,000.00'
$sRsAmt = GRN::getList($branchId, ['search' => 'Rs. 15,000.00']);
assertTest("Search finds GRN by currency prefix 'Rs. 15,000.00'", in_array($testGrnId, array_column($sRsAmt['items'], 'id')));

// G. Search by Variance Reason ('Price Variance')
$sReason = GRN::getList($branchId, ['search' => 'Price Variance']);
assertTest("Search finds GRN by Variance Reason ('Price Variance')", in_array($testGrnId, array_column($sReason['items'], 'id')));

// H. Search by Received Date ('2026-10-15')
$sDate = GRN::getList($branchId, ['search' => '2026-10-15']);
assertTest("Search finds GRN by Received Date ('2026-10-15')", in_array($testGrnId, array_column($sDate['items'], 'id')));

// 7. Test GRN Approval & Stock Posting Workflow
$resApprove = GRN::approve($testGrnId, $userId);
assertTest("GRN::approve() succeeds on Draft GRN", $resApprove['success'] === true);

// Verify Status Transition
$approvedGrn = GRN::find($testGrnId);
assertTest("GRN status transitioned to 'Approved'", $approvedGrn['status'] === 'Approved');

// Verify Branch Inventory Stock Increased
$stockAfterApproval = (float)(Database::fetchOne("SELECT quantity FROM branch_inventory WHERE branch_id = :branch_id AND product_id = :product_id", [
    ':branch_id' => $branchId,
    ':product_id' => $prodId
])['quantity'] ?? 0);
assertTest("Branch stock increased by exactly received quantity (+{$grnQty})", $stockAfterApproval === ($initialStock + $grnQty));

// Verify Creation of Inventory Lot via grn_items.lot_id
$grnItem = Database::fetchOne("SELECT lot_id FROM grn_items WHERE grn_id = :grn_id AND product_id = :product_id", [
    ':grn_id' => $testGrnId,
    ':product_id' => $prodId
]);
$lotId = (int)($grnItem['lot_id'] ?? 0);
$createdLot = $lotId ? Database::fetchOne("SELECT * FROM product_lots WHERE id = :id", [':id' => $lotId]) : null;
assertTest("Product Lot created with unit cost 750.00 and selling price 1100.00", $createdLot && (float)$createdLot['cost'] === 750.00 && (float)$createdLot['selling_price'] === 1100.00);

// Verify Stock Movement Ledger Record in stock_ledger
$movement = Database::fetchOne("SELECT * FROM stock_ledger WHERE movement_type = 'purchase_grn' AND reference_no = :ref_no", [
    ':ref_no' => $testGrnNumber
]);
assertTest("Append-only stock ledger logged for GRN with quantity {$grnQty}", $movement && (float)$movement['quantity'] === $grnQty);

// Verify COA Double-Entry Accounting Entry
$journal = Database::fetchOne("SELECT * FROM journal_entries WHERE source_type = 'grn' AND source_id = :grn_id", [
    ':grn_id' => $testGrnId
]);
if ($journal) {
    $lines = Database::fetchAll("SELECT * FROM journal_lines WHERE journal_entry_id = :jid", [':jid' => $journal['id']]);
    $totDebit = array_sum(array_column($lines, 'debit'));
    $totCredit = array_sum(array_column($lines, 'credit'));
    assertTest("COA Double-entry journal created and balanced (Debit = Credit = {$totDebit})", $totDebit > 0 && abs($totDebit - $totCredit) < 0.001);
} else {
    assertTest("COA Double-entry journal created for GRN", false);
}

// 8. Test Branch Isolation
$otherBranchList = GRN::getList(9999, []);
assertTest("Branch isolation: branch 9999 cannot view branch 1 GRNs", !in_array($testGrnId, array_column($otherBranchList['items'], 'id')));

// 9. Clean Cleanup of Isolated Test Data (NO TRUNCATE)
// Roll back stock increment
Database::execute("UPDATE branch_inventory SET quantity = quantity - :qty WHERE branch_id = :branch_id AND product_id = :product_id", [
    ':qty' => $grnQty,
    ':branch_id' => $branchId,
    ':product_id' => $prodId
]);
if ($journal) {
    Database::execute("DELETE FROM journal_lines WHERE journal_entry_id = :jid", [':jid' => $journal['id']]);
    Database::execute("DELETE FROM journal_entries WHERE id = :jid", [':jid' => $journal['id']]);
}
if ($createdLot) {
    Database::execute("DELETE FROM product_lots WHERE id = :lid", [':lid' => $createdLot['id']]);
}
Database::execute("DELETE FROM supplier_credit_settlements WHERE target_grn_id = :id", [':id' => $testGrnId]);
Database::execute("DELETE FROM stock_ledger WHERE movement_type = 'purchase_grn' AND reference_no = :ref_no", [':ref_no' => $testGrnNumber]);
Database::execute("DELETE FROM grn_items WHERE grn_id = :grn_id", [':grn_id' => $testGrnId]);
Database::execute("DELETE FROM grns WHERE id = :id", [':id' => $testGrnId]);
echo "Isolated GRN test records cleaned up safely." . PHP_EOL . PHP_EOL;

echo "=== TEST RESULTS SUMMARY ===" . PHP_EOL;
echo "Total Passed: {$passCount}" . PHP_EOL;
echo "Total Failed: {$failCount}" . PHP_EOL;

if ($failCount === 0) {
    echo "STATUS: ALL TESTS PASSED SUCCESSFULLY!" . PHP_EOL;
    exit(0);
} else {
    echo "STATUS: SOME TESTS FAILED!" . PHP_EOL;
    exit(1);
}
