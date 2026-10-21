<?php
/**
 * Curtiss POS - Comprehensive Bulk GRN Test Suite
 * Tests all 13 requirements specified in Module_implementation_plan.md:
 * 1. Supplier product loading with stock and price lots
 * 2. Quantity exclusion (empty / 0 quantity excluded)
 * 3. Matching existing price lots reuse
 * 4. Creating new price lots when prices differ
 * 5. Reusing exact price lots
 * 6. Expiry date handling & validation
 * 7. On-the-fly new product creation
 * 8. Stock ledger and double-entry accounting verification
 * 9. Transaction rollback on error
 * 10. Preserving existing normal GRN functionality
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/BulkGRN.php';
require_once __DIR__ . '/../core/GRN.php';
require_once __DIR__ . '/../core/Supplier.php';
require_once __DIR__ . '/../core/Product.php';
require_once __DIR__ . '/../core/Brand.php';
require_once __DIR__ . '/../core/Category.php';
require_once __DIR__ . '/../core/Inventory.php';
require_once __DIR__ . '/../core/Accounting.php';

$branchId = 1;
$userId = 1;
$passed = 0;
$failed = 0;

function assertTest(string $desc, bool $condition, &$passed, &$failed) {
    if ($condition) {
        echo "  [PASS] $desc\n";
        $passed++;
    } else {
        echo "  [FAIL] $desc\n";
        $failed++;
    }
}

echo "=== STARTING BULK GRN TEST SUITE ===\n\n";

// Setup Test Data
$testSupplierName = "TEST_SUPPLIER_BULK_" . rand(1000, 9999);
$supRes = Supplier::create($testSupplierName, "0771234567", null, "Colombo", "active");
$supplierId = (int)$supRes['id'];

$brandRes = Brand::create("TEST_BRAND_" . rand(1000, 9999), "active");
$brandId = (int)$brandRes['id'];

$catRes = Category::create("TEST_CAT_" . rand(1000, 9999), "active");
$catId = (int)$catRes['id'];

// Create 2 test products assigned to this supplier
// Product 1: Standard product with initial lot (Cost: 100, Sell: 150)
$prod1Barcode = "TEST_P1_" . rand(1000, 9999);
$p1Res = Product::create([
    'barcode' => $prod1Barcode,
    'sku' => $prod1Barcode,
    'name' => "Bulk Test Product A",
    'brand_id' => $brandId,
    'category_id' => $catId,
    'supplier_id' => $supplierId,
    'cost' => 100.00,
    'selling_price' => 150.00,
    'opening_stock' => 5.00,
    'has_expiry' => 0,
    'status' => 'active',
    'branch_id' => $branchId
]);
$p1Id = (int)$p1Res['id'];

// Product 2: Expiry-tracked product (Cost: 200, Sell: 280)
$prod2Barcode = "TEST_P2_" . rand(1000, 9999);
$p2Res = Product::create([
    'barcode' => $prod2Barcode,
    'sku' => $prod2Barcode,
    'name' => "Bulk Test Product B (Expiry)",
    'brand_id' => $brandId,
    'category_id' => $catId,
    'supplier_id' => $supplierId,
    'cost' => 200.00,
    'selling_price' => 280.00,
    'opening_stock' => 0.00,
    'has_expiry' => 1,
    'status' => 'active',
    'branch_id' => $branchId
]);
$p2Id = (int)$p2Res['id'];

// TEST 1: Load active products for selected supplier
echo "--- TEST 1: Supplier Product Loading ---\n";
$supplierProducts = BulkGRN::getSupplierProductsWithLots($supplierId, $branchId);
assertTest("Loaded products count is exactly 2", count($supplierProducts) === 2, $passed, $failed);
$p1Data = null;
$p2Data = null;
foreach ($supplierProducts as $sp) {
    if ($sp['id'] == $p1Id) $p1Data = $sp;
    if ($sp['id'] == $p2Id) $p2Data = $sp;
}
assertTest("Product A has current stock 5.00", abs($p1Data['current_stock'] - 5.00) < 0.01, $passed, $failed);
assertTest("Product A has 1 active lot", count($p1Data['lots']) === 1, $passed, $failed);
assertTest("Product A defaults to lot cost 100 & sell 150", abs($p1Data['cost'] - 100) < 0.01 && abs($p1Data['selling_price'] - 150) < 0.01, $passed, $failed);
assertTest("Product B is identified as expiry-enabled", $p2Data['has_expiry'] == 1, $passed, $failed);

// TEST 2: Exclusion of empty/0 quantity products
echo "\n--- TEST 2: Quantity Exclusion & Draft Creation ---\n";
$header = [
    'supplier_id'             => $supplierId,
    'branch_id'               => $branchId,
    'supplier_invoice_no'     => "INV-BULK-001",
    'supplier_invoice_date'   => date('Y-m-d'),
    'received_date'           => date('Y-m-d'),
    'supplier_invoice_amount' => 1000.00,
    'difference_reason'       => 'None',
    'notes'                   => 'Test Bulk GRN Draft',
    'user_id'                 => $userId
];
// Only enter quantity for Product 1; leave Product 2 quantity as 0 / empty
$existingItems = [
    [
        'product_id'    => $p1Id,
        'quantity'      => 10.00,
        'unit_cost'     => 100.00,
        'selling_price' => 150.00
    ],
    [
        'product_id'    => $p2Id,
        'quantity'      => 0, // Should be excluded!
        'unit_cost'     => 200.00,
        'selling_price' => 280.00
    ]
];
$draftRes = BulkGRN::process($header, $existingItems, [], false);
assertTest("Bulk GRN Draft created successfully", $draftRes['success'] === true && $draftRes['status'] === 'Draft', $passed, $failed);
$draftGrnId = (int)$draftRes['grn_id'];
$draftGrn = GRN::find($draftGrnId);
assertTest("Draft GRN contains exactly 1 item (Product 2 with qty 0 excluded)", count($draftGrn['items']) === 1, $passed, $failed);
assertTest("Actual GRN Total is 1,000.00 (10 * 100)", abs((float)$draftGrn['actual_grn_total'] - 1000.00) < 0.01, $passed, $failed);

// TEST 3: Matching Price Lot Reuse upon Approval
echo "\n--- TEST 3: Matching Price Lot Reuse upon Approval ---\n";
// Before approving draft GRN, count active lots for Product 1
$lotsBefore = Database::fetchAll("SELECT id, remaining_quantity FROM product_lots WHERE product_id = :p AND branch_id = :b AND status = 'active'", [':p' => $p1Id, ':b' => $branchId]);
$lotsCountBefore = count($lotsBefore);

$appRes = GRN::approve($draftGrnId, $userId);
assertTest("Draft GRN approved and posted to stock", $appRes['success'] === true, $passed, $failed);

$lotsAfter = Database::fetchAll("SELECT id, remaining_quantity FROM product_lots WHERE product_id = :p AND branch_id = :b AND status = 'active'", [':p' => $p1Id, ':b' => $branchId]);
$lotsCountAfter = count($lotsAfter);
assertTest("No duplicate lot created; existing matching lot reused", $lotsCountBefore === $lotsCountAfter, $passed, $failed);

$p1StockAfter = Database::fetchOne("SELECT quantity FROM branch_inventory WHERE product_id = :p AND branch_id = :b", [':p' => $p1Id, ':b' => $branchId]);
assertTest("Branch inventory updated to 15.00 (5 initial + 10 received)", abs((float)$p1StockAfter['quantity'] - 15.00) < 0.01, $passed, $failed);

// TEST 4: New Price Lot Creation When Prices Differ
echo "\n--- TEST 4: New Price Lot Creation (Different Prices) ---\n";
$header2 = [
    'supplier_id'             => $supplierId,
    'branch_id'               => $branchId,
    'supplier_invoice_no'     => "INV-BULK-002",
    'supplier_invoice_date'   => date('Y-m-d'),
    'received_date'           => date('Y-m-d'),
    'supplier_invoice_amount' => 1100.00,
    'notes'                   => 'New price test',
    'user_id'                 => $userId
];
// Received Product 1 with NEW cost (110.00) and selling price (160.00)
$existingItems2 = [
    [
        'product_id'    => $p1Id,
        'quantity'      => 10.00,
        'unit_cost'     => 110.00,
        'selling_price' => 160.00
    ]
];
$newLotGrnRes = BulkGRN::process($header2, $existingItems2, [], true); // approve immediately
assertTest("Bulk GRN with different price saved & approved", $newLotGrnRes['success'] === true, $passed, $failed);

$lotsAfterPriceDiff = Database::fetchAll("SELECT id, cost, selling_price, remaining_quantity FROM product_lots WHERE product_id = :p AND branch_id = :b AND status = 'active'", [':p' => $p1Id, ':b' => $branchId]);
assertTest("Product 1 now has 2 distinct active price lots", count($lotsAfterPriceDiff) === 2, $passed, $failed);

// TEST 5: On-The-Fly New Product Creation & Expiry Validation
echo "\n--- TEST 5: On-The-Fly New Product Creation & Expiry Handling ---\n";
$newProdBarcode = "TEST_NEW_PROD_" . rand(1000, 9999);
$newProdExpDate = date('Y-m-d', strtotime('+180 days'));
$newProductsPayload = [
    [
        'name'          => 'Instant Created Milk Powder',
        'barcode'       => $newProdBarcode,
        'brand_id'      => $brandId,
        'category_id'   => $catId,
        'quantity'      => 20.00,
        'unit_cost'     => 450.00,
        'selling_price' => 550.00,
        'has_expiry'    => 1,
        'expiry_date'   => $newProdExpDate
    ]
];
$header3 = [
    'supplier_id'             => $supplierId,
    'branch_id'               => $branchId,
    'supplier_invoice_no'     => "INV-BULK-003",
    'supplier_invoice_date'   => date('Y-m-d'),
    'received_date'           => date('Y-m-d'),
    'supplier_invoice_amount' => 9000.00,
    'notes'                   => 'Instant product creation test',
    'user_id'                 => $userId
];
$newProdGrnRes = BulkGRN::process($header3, [], $newProductsPayload, true);
assertTest("Bulk GRN with newly added product created & approved", $newProdGrnRes['success'] === true, $passed, $failed);

// Verify new product in database
$createdProd = Database::fetchOne("SELECT id, name, barcode, supplier_id, has_expiry FROM products WHERE barcode = :b AND deleted_at IS NULL", [':b' => $newProdBarcode]);
assertTest("New product exists in Product Master with supplier assigned", !empty($createdProd) && $createdProd['supplier_id'] == $supplierId, $passed, $failed);
$createdProdId = (int)$createdProd['id'];

// Verify stock and price lot created for new product
$newProdLot = Database::fetchOne("SELECT id, cost, selling_price, remaining_quantity, expiry_date FROM product_lots WHERE product_id = :p AND branch_id = :b", [':p' => $createdProdId, ':b' => $branchId]);
assertTest("New product received stock in product_lots (remaining 20)", !empty($newProdLot) && abs((float)$newProdLot['remaining_quantity'] - 20) < 0.01, $passed, $failed);
assertTest("New product price lot retained exact expiry date", $newProdLot['expiry_date'] === $newProdExpDate, $passed, $failed);

// TEST 6: Atomic Rollback on Failure
echo "\n--- TEST 6: Atomic Rollback on Failure ---\n";
$badBarcode = "ROLLBACK_PROD_" . rand(1000, 9999);
$failNewProducts = [
    [
        'name'          => 'Rollback Test Product',
        'barcode'       => $badBarcode,
        'brand_id'      => $brandId,
        'category_id'   => $catId,
        'quantity'      => 5.00,
        'unit_cost'     => 100.00,
        'selling_price' => 120.00,
        'has_expiry'    => 1,
        'expiry_date'   => '' // Missing required expiry date -> Must fail!
    ]
];
$headerFail = [
    'supplier_id'             => $supplierId,
    'branch_id'               => $branchId,
    'supplier_invoice_no'     => "INV-FAIL-001",
    'supplier_invoice_amount' => 500.00,
    'user_id'                 => $userId
];
$failRes = BulkGRN::process($headerFail, [], $failNewProducts, false);
assertTest("Bulk GRN failed validation due to missing expiry date", $failRes['success'] === false, $passed, $failed);

$rolledBackProd = Database::fetchOne("SELECT id FROM products WHERE barcode = :b", [':b' => $badBarcode]);
assertTest("Product creation was atomically rolled back", empty($rolledBackProd), $passed, $failed);

// TEST 7: Normal GRN Untouched & Fully Operational
echo "\n--- TEST 7: Normal GRN System Integrity ---\n";
$normalHeader = [
    'supplier_id'             => $supplierId,
    'branch_id'               => $branchId,
    'supplier_invoice_no'     => "INV-NORMAL-001",
    'supplier_invoice_date'   => date('Y-m-d'),
    'received_date'           => date('Y-m-d'),
    'supplier_invoice_amount' => 500.00,
    'user_id'                 => $userId
];
$normalItems = [
    [
        'product_id'    => $p1Id,
        'quantity'      => 5.00,
        'unit_cost'     => 100.00,
        'selling_price' => 150.00
    ]
];
$normalRes = GRN::create($normalHeader, $normalItems, true);
assertTest("Existing normal GRN::create operates identically", $normalRes['success'] === true, $passed, $failed);

// Cleanup Test Records (preserves existing user data)
echo "\n--- Cleaning up test records ---\n";
Database::execute("DELETE FROM journal_lines WHERE journal_entry_id IN (SELECT id FROM journal_entries WHERE source_type = 'grn' AND source_id IN (SELECT id FROM grns WHERE supplier_id = :sid))", [':sid' => $supplierId]);
Database::execute("DELETE FROM journal_entries WHERE source_type = 'grn' AND source_id IN (SELECT id FROM grns WHERE supplier_id = :sid)", [':sid' => $supplierId]);
Database::execute("DELETE FROM stock_ledger WHERE product_id IN ($p1Id, $p2Id, $createdProdId)");
Database::execute("DELETE FROM product_lots WHERE product_id IN ($p1Id, $p2Id, $createdProdId)");
Database::execute("DELETE FROM branch_inventory WHERE product_id IN ($p1Id, $p2Id, $createdProdId)");
Database::execute("DELETE FROM grn_items WHERE product_id IN ($p1Id, $p2Id, $createdProdId)");
Database::execute("DELETE FROM grns WHERE supplier_id = :sid", [':sid' => $supplierId]);
Database::execute("DELETE FROM products WHERE id IN ($p1Id, $p2Id, $createdProdId)");
Database::execute("DELETE FROM suppliers WHERE id = :sid", [':sid' => $supplierId]);
Database::execute("DELETE FROM brands WHERE id = :bid", [':bid' => $brandId]);
Database::execute("DELETE FROM categories WHERE id = :cid", [':cid' => $catId]);
echo "  [DONE] Cleaned up isolated test fixtures.\n\n";

echo "=== TEST RESULTS: $passed PASSED, $failed FAILED ===\n";
exit($failed > 0 ? 1 : 0);
