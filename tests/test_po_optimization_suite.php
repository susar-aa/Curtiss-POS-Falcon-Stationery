<?php
/**
 * Automated CLI Test Suite for Purchase Orders & PO Creation Panel Optimization
 * Strictly follows CURTISS_MASTER_RULES:
 * - Isolated test fixtures
 * - Clean cleanup without TRUNCATE
 * - Multi-column and monetary search verification
 * - Role & branch resolution
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/PurchaseOrder.php';
require_once __DIR__ . '/../core/Supplier.php';
require_once __DIR__ . '/../core/Product.php';

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

echo "=== STARTING PURCHASE ORDERS AUTOMATION TEST SUITE ===" . PHP_EOL . PHP_EOL;

// 1. Verify Database Composite Indexes
$indexes = Database::fetchAll("SHOW INDEX FROM purchase_orders");
$indexNames = array_unique(array_column($indexes, 'Key_name'));
assertTest("Composite Index 'idx_po_br_del_id' exists on purchase_orders", in_array('idx_po_br_del_id', $indexNames));
assertTest("Composite Index 'idx_po_br_del_status' exists on purchase_orders", in_array('idx_po_br_del_status', $indexNames));
assertTest("Composite Index 'idx_po_br_del_supp' exists on purchase_orders", in_array('idx_po_br_del_supp', $indexNames));

// 2. Setup isolated test data
$supplier = Database::fetchOne("SELECT id, name FROM suppliers WHERE status = 'active' AND deleted_at IS NULL LIMIT 1");
if (!$supplier) {
    // create a temporary supplier
    Database::execute("INSERT INTO suppliers (name, contact_person, phone, email, status, created_at) VALUES ('Test PO Vendor', 'John Tester', '0771234567', 'vendor@test.com', 'active', NOW())");
    $suppId = (int)Database::lastInsertId();
} else {
    $suppId = (int)$supplier['id'];
}

$product = Database::fetchOne("SELECT id, name, cost, selling_price FROM products WHERE status = 'active' AND deleted_at IS NULL LIMIT 1");
if (!$product) {
    // create a temporary product
    Database::execute("INSERT INTO products (name, sku, barcode, cost, selling_price, status, created_at) VALUES ('Test PO Widget', 'PO-WIDGET-01', '8991234567', 1500.00, 2200.00, 'active', NOW())");
    $prodId = (int)Database::lastInsertId();
} else {
    $prodId = (int)$product['id'];
}

$user = Database::fetchOne("SELECT id, full_name FROM users LIMIT 1");
$userId = (int)($user['id'] ?? 1);
$branchId = 1;

// 3. Test PO Creation Validation
$failHeader = [
    'supplier_id' => 0, // Invalid
    'branch_id' => $branchId,
    'order_date' => date('Y-m-d'),
    'user_id' => $userId
];
$resEmptySupp = PurchaseOrder::create($failHeader, [['product_id' => $prodId, 'quantity' => 5, 'unit_cost' => 1250.00]]);
assertTest("PO creation rejects empty supplier_id", $resEmptySupp['success'] === false);

$failItems = [
    'supplier_id' => $suppId,
    'branch_id' => $branchId,
    'order_date' => date('Y-m-d'),
    'user_id' => $userId
];
$resEmptyItems = PurchaseOrder::create($failItems, []);
assertTest("PO creation rejects empty items list", $resEmptyItems['success'] === false);

// 4. Test Successful PO Creation
$validHeader = [
    'supplier_id' => $suppId,
    'branch_id' => $branchId,
    'order_date' => '2026-10-15',
    'expected_date' => '2026-10-22',
    'discount_amount' => 500.00,
    'notes' => 'CLI automated testing order with unique keywords XYZ99',
    'user_id' => $userId
];
$validItems = [
    [
        'product_id' => $prodId,
        'quantity' => 10,
        'unit_cost' => 1250.00, // 10 * 1250 = 12,500.00
        'selling_price' => 1800.00
    ]
];

$resCreate = PurchaseOrder::create($validHeader, $validItems);
assertTest("PO created successfully with valid inputs", $resCreate['success'] === true && !empty($resCreate['po_id']));
$testPoId = (int)($resCreate['po_id'] ?? 0);
$testPoNumber = $resCreate['po_number'] ?? '';

// 5. Test Calculation Integrity
$createdPo = PurchaseOrder::find($testPoId);
assertTest("PO retrieved by find()", $createdPo !== false);
assertTest("PO subtotal calculated accurately (12,500.00)", (float)$createdPo['total_amount'] === 12500.00);
assertTest("PO discount recorded (500.00)", (float)$createdPo['discount_amount'] === 500.00);
assertTest("PO net amount calculated accurately (12,000.00)", (float)$createdPo['net_amount'] === 12000.00);
assertTest("PO items count is 1", count($createdPo['items']) === 1);
assertTest("PO initial status is 'Draft'", $createdPo['status'] === 'Draft');

// 6. Test Multi-Column and Monetary Search Capabilities
// A. Search by PO Number
$sPoNum = PurchaseOrder::getList($branchId, ['search' => $testPoNumber]);
assertTest("Search finds PO by exact PO Number ({$testPoNumber})", $sPoNum['totalItems'] >= 1 && in_array($testPoId, array_column($sPoNum['items'], 'id')));

// B. Search by Raw Numeric Net Amount (12000)
$sRawAmt = PurchaseOrder::getList($branchId, ['search' => '12000']);
assertTest("Search finds PO by raw numeric Net Amount (12000)", in_array($testPoId, array_column($sRawAmt['items'], 'id')));

// C. Search by Formatted Net Amount (12,000.00)
$sFmtAmt = PurchaseOrder::getList($branchId, ['search' => '12,000.00']);
assertTest("Search finds PO by comma-formatted Net Amount (12,000.00)", in_array($testPoId, array_column($sFmtAmt['items'], 'id')));

// D. Search by Currency Prefix 'LKR 12,000.00'
$sLkrAmt = PurchaseOrder::getList($branchId, ['search' => 'LKR 12,000.00']);
assertTest("Search finds PO by currency prefix 'LKR 12,000.00'", in_array($testPoId, array_column($sLkrAmt['items'], 'id')));

// E. Search by Currency Prefix 'Rs. 12,000.00'
$sRsAmt = PurchaseOrder::getList($branchId, ['search' => 'Rs. 12,000.00']);
assertTest("Search finds PO by currency prefix 'Rs. 12,000.00'", in_array($testPoId, array_column($sRsAmt['items'], 'id')));

// F. Search by Order Date (2026-10-15)
$sDate = PurchaseOrder::getList($branchId, ['search' => '2026-10-15']);
assertTest("Search finds PO by Order Date (2026-10-15)", in_array($testPoId, array_column($sDate['items'], 'id')));

// G. Search by Notes Keyword ('XYZ99')
$sNotes = PurchaseOrder::getList($branchId, ['search' => 'XYZ99']);
assertTest("Search finds PO by Notes keyword ('XYZ99')", in_array($testPoId, array_column($sNotes['items'], 'id')));

// 7. Test Status Workflow Transitions
// A. Approve Draft PO
$resApprove = PurchaseOrder::approve($testPoId, $userId);
assertTest("PurchaseOrder::approve() succeeds on Draft PO", $resApprove['success'] === true);
$poApproved = PurchaseOrder::find($testPoId);
assertTest("PO status transitioned to 'Approved'", $poApproved['status'] === 'Approved');

// B. Reject Re-Approval
$resReApprove = PurchaseOrder::approve($testPoId, $userId);
assertTest("PurchaseOrder::approve() rejects already approved PO", $resReApprove['success'] === false);

// 8. Test Soft Delete & Trash Bin Recovery
// Soft Delete
$resDelete = PurchaseOrder::delete($testPoId, $userId);
assertTest("PurchaseOrder::delete() soft-deletes the PO", $resDelete['success'] === true);

// Verify PO is hidden from Active List
$activeList = PurchaseOrder::getList($branchId, ['view' => 'active']);
assertTest("Deleted PO is hidden from active list", !in_array($testPoId, array_column($activeList['items'], 'id')));

// Verify PO appears in Trash Bin List
$trashList = PurchaseOrder::getList($branchId, ['view' => 'deleted']);
assertTest("Deleted PO appears in Trash Bin list (view=deleted)", in_array($testPoId, array_column($trashList['items'], 'id')));

// Restore from Trash Bin
$resRestore = PurchaseOrder::restore($testPoId, $userId);
assertTest("PurchaseOrder::restore() recovers PO from trash", $resRestore['success'] === true);

// Verify PO is back in Active List
$recoveredList = PurchaseOrder::getList($branchId, ['view' => 'active']);
assertTest("Restored PO is back in active list", in_array($testPoId, array_column($recoveredList['items'], 'id')));

// 9. Test Branch Resolution & Isolation
$_SESSION['active_branch_id'] = 9999; // Non-existent branch
$otherBranchList = PurchaseOrder::getList(9999, []);
assertTest("Branch isolation: other branch list does not contain branch 1 PO", !in_array($testPoId, array_column($otherBranchList['items'], 'id')));
unset($_SESSION['active_branch_id']);

// 10. Clean Cleanup of Isolated Test Data (NO TRUNCATE)
Database::execute("DELETE FROM purchase_order_items WHERE po_id = :id", [':id' => $testPoId]);
Database::execute("DELETE FROM purchase_orders WHERE id = :id", [':id' => $testPoId]);
echo "Isolated test records cleaned up safely." . PHP_EOL . PHP_EOL;

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
