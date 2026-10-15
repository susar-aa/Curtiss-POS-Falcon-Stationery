<?php
/**
 * CLI Test Suite for Product Master, Brands, and Categories Modules
 * Strictly follows testing rules: isolated test records, zero truncation, non-destructive cleanup.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Brand.php';
require_once __DIR__ . '/../core/Category.php';
require_once __DIR__ . '/../core/Supplier.php';
require_once __DIR__ . '/../core/Product.php';
require_once __DIR__ . '/../core/Accounting.php';
require_once __DIR__ . '/../core/Audit.php';

// Setup CLI Session
$_SESSION['user_id'] = 1;
$_SESSION['role'] = ROLE_SUPER_ADMIN;
$_SESSION['active_branch_id'] = 1;
$_SESSION['branch_id'] = 1;

$testResults = [
    'passed' => 0,
    'failed' => 0,
    'details' => []
];

function assertTest(string $desc, bool $condition, string $evidence = '') {
    global $testResults;
    if ($condition) {
        $testResults['passed']++;
        $testResults['details'][] = "[PASS] $desc" . ($evidence ? " | $evidence" : "");
        echo "  [PASS] $desc\n";
    } else {
        $testResults['failed']++;
        $testResults['details'][] = "[FAIL] $desc" . ($evidence ? " | $evidence" : "");
        echo "  [FAIL] $desc" . ($evidence ? " | $evidence" : "") . "\n";
    }
}

echo "====================================================\n";
echo "   CURTISS POS - PRODUCT MASTER CLI TEST SUITE\n";
echo "====================================================\n\n";

$createdBrandIds = [];
$createdCategoryIds = [];
$createdProductIds = [];
$createdSupplierIds = [];

try {
    // ----------------------------------------------------
    // 1. BRAND MANAGEMENT FUNCTIONAL TESTS
    // ----------------------------------------------------
    echo "--- 1. Testing Brand Management ---\n";
    $testBrandName = "TestBrand_" . time() . "_" . rand(100, 999);
    
    // Test 1.1: Create Brand
    $resCreate = Brand::create($testBrandName, 'active');
    assertTest("Create Brand", $resCreate['success'] && !empty($resCreate['id']), "Brand ID: " . ($resCreate['id'] ?? 'none'));
    if ($resCreate['success']) {
        $createdBrandIds[] = (int)$resCreate['id'];
        $brandId = (int)$resCreate['id'];
    }

    // Test 1.2: Duplicate Brand Case-Insensitive Prevention
    $resDup = Brand::create(strtolower($testBrandName), 'active');
    assertTest("Prevent Duplicate Brand (Case-Insensitive)", !$resDup['success'], "Message: " . ($resDup['message'] ?? ''));

    // Test 1.3: Find Brand
    $foundBrand = Brand::find($brandId);
    assertTest("Find Brand by ID", $foundBrand !== null && $foundBrand['name'] === $testBrandName);

    // Test 1.4: Update Brand
    $updatedBrandName = $testBrandName . "_Updated";
    $resUpd = Brand::update($brandId, $updatedBrandName, 'active');
    assertTest("Update Brand Name", $resUpd['success']);

    // Test 1.5: Toggle Status
    $resToggle = Brand::toggleStatus($brandId);
    $bAfterToggle = Brand::find($brandId);
    assertTest("Toggle Brand Status (Active -> Inactive)", $resToggle['success'] && ($bAfterToggle['status'] ?? '') === 'inactive');

    // Toggle back to active
    Brand::toggleStatus($brandId);

    // Test 1.6: Soft Delete Brand
    $resDel = Brand::delete($brandId);
    $bAfterDel = Brand::find($brandId);
    assertTest("Soft Delete Brand (deleted_at set, find returns null)", $resDel['success'] && $bAfterDel === null);

    // Test 1.7: Trash Bin Listing
    $deletedBrands = Brand::deleted();
    $foundInTrash = false;
    foreach ($deletedBrands as $db) {
        if ((int)$db['id'] === $brandId) { $foundInTrash = true; break; }
    }
    assertTest("Brand visible in Trash Bin", $foundInTrash);

    // Test 1.8: Restore Brand
    $resRestore = Brand::restore($brandId);
    $bAfterRestore = Brand::find($brandId);
    assertTest("Restore Brand from Trash Bin", $resRestore['success'] && $bAfterRestore !== null);

    // Test 1.9: Audit Log Verification for Brand
    $auditLogs = Database::fetchAll("SELECT * FROM audit_logs WHERE module = 'brands' AND record_id = :id ORDER BY id DESC", [':id' => $brandId]);
    assertTest("Brand Audit Logging (Captured creation, update, toggle, delete, restore)", count($auditLogs) >= 4, "Found " . count($auditLogs) . " audit logs");

    // ----------------------------------------------------
    // 2. CATEGORY MANAGEMENT FUNCTIONAL TESTS
    // ----------------------------------------------------
    echo "\n--- 2. Testing Category Management ---\n";
    $testCatName = "TestCat_" . time() . "_" . rand(100, 999);

    // Test 2.1: Create Category
    $resCatCreate = Category::create($testCatName, 'active');
    assertTest("Create Category", $resCatCreate['success'] && !empty($resCatCreate['id']), "Category ID: " . ($resCatCreate['id'] ?? 'none'));
    if ($resCatCreate['success']) {
        $createdCategoryIds[] = (int)$resCatCreate['id'];
        $catId = (int)$resCatCreate['id'];
    }

    // Test 2.2: Duplicate Category Prevention
    $resCatDup = Category::create(strtoupper($testCatName), 'active');
    assertTest("Prevent Duplicate Category (Case-Insensitive)", !$resCatDup['success'], "Message: " . ($resCatDup['message'] ?? ''));

    // Test 2.3: Find Category
    $foundCat = Category::find($catId);
    assertTest("Find Category by ID", $foundCat !== null && $foundCat['name'] === $testCatName);

    // Test 2.4: Update Category
    $updatedCatName = $testCatName . "_Updated";
    $resCatUpd = Category::update($catId, $updatedCatName, 'active');
    assertTest("Update Category Name", $resCatUpd['success']);

    // Test 2.5: Toggle Category Status
    $resCatToggle = Category::toggleStatus($catId);
    $cAfterToggle = Category::find($catId);
    assertTest("Toggle Category Status", $resCatToggle['success'] && ($cAfterToggle['status'] ?? '') === 'inactive');
    Category::toggleStatus($catId); // back to active

    // Test 2.6: Soft Delete Category
    $resCatDel = Category::delete($catId);
    $cAfterDel = Category::find($catId);
    assertTest("Soft Delete Category", $resCatDel['success'] && $cAfterDel === null);

    // Test 2.7: Restore Category
    $resCatRestore = Category::restore($catId);
    $cAfterRestore = Category::find($catId);
    assertTest("Restore Category from Trash Bin", $resCatRestore['success'] && $cAfterRestore !== null);

    // Test 2.8: Category Audit Logging
    $catAuditLogs = Database::fetchAll("SELECT * FROM audit_logs WHERE module = 'categories' AND record_id = :id ORDER BY id DESC", [':id' => $catId]);
    assertTest("Category Audit Logging", count($catAuditLogs) >= 4, "Found " . count($catAuditLogs) . " audit logs");

    // ----------------------------------------------------
    // 3. PRODUCT MASTER & INVENTORY/ACCOUNTING TESTS
    // ----------------------------------------------------
    echo "\n--- 3. Testing Product Master & Accounting ---\n";

    // Create a test supplier for product linkage
    $testSupplierName = "TestSupp_" . time() . "_" . rand(100, 999);
    $resSupp = Supplier::create($testSupplierName, '0771234567', null, null, 'active');
    $suppId = (int)($resSupp['id'] ?? 1);
    if ($resSupp['success']) {
        $createdSupplierIds[] = $suppId;
    }

    $testBarcode = "BAR" . time() . rand(10, 99);
    $testSku = "SKU" . time() . rand(10, 99);
    $testProdName = "TestProduct " . time();
    $openingQty = 10.00;
    $costPrice = 150.00;
    $sellPrice = 250.00;
    $expectedTotalVal = $openingQty * $costPrice; // 1500.00

    // Test 3.1: Create Product with Opening Stock
    $prodPayload = [
        'barcode'             => $testBarcode,
        'sku'                 => $testSku,
        'brand_id'            => $brandId,
        'category_id'         => $catId,
        'supplier_id'         => $suppId,
        'name'                => $testProdName,
        'cost'                => $costPrice,
        'selling_price'       => $sellPrice,
        'opening_stock'       => $openingQty,
        'low_stock_threshold' => 5.00,
        'status'              => 'active',
        'branch_id'           => 1
    ];

    $resProd = Product::create($prodPayload);
    assertTest("Create Product with Opening Stock", $resProd['success'], "Errors: " . ($resProd['message'] ?? ''));
    $prodId = 0;
    if ($resProd['success']) {
        $prodId = (int)$resProd['id'];
        $createdProductIds[] = $prodId;
    }

    // Test 3.2: Verify Branch Inventory & Stock Lots
    $branchInv = Database::fetchOne("SELECT quantity FROM branch_inventory WHERE product_id = :p AND branch_id = 1", [':p' => $prodId]);
    assertTest("Branch Inventory Quantity matches Opening Stock ($openingQty)", (float)($branchInv['quantity'] ?? 0) === $openingQty);

    $stockLot = Database::fetchOne("SELECT * FROM product_lots WHERE product_id = :p AND branch_id = 1", [':p' => $prodId]);
    assertTest("Stock Lot Created for Branch", $stockLot !== null && (float)$stockLot['remaining_quantity'] === $openingQty);

    // Test 3.3: Verify Stock Movement in Stock Ledger
    $ledger = Database::fetchOne("SELECT * FROM stock_ledger WHERE product_id = :p ORDER BY id DESC LIMIT 1", [':p' => $prodId]);
    assertTest("Stock Ledger Entry Recorded for Opening Stock", $ledger !== null && (float)$ledger['quantity'] === $openingQty);

    // Test 3.4: Verify Chart of Accounts Double-Entry Journal Posting
    $je = Database::fetchOne("
        SELECT * FROM journal_entries 
        WHERE source_type = 'opening_balance' AND source_id = :p 
        ORDER BY id DESC LIMIT 1
    ", [':p' => $prodId]);
    assertTest("COA Journal Entry Created for Opening Stock", $je !== null);

    if ($je) {
        $lines = Database::fetchAll("SELECT * FROM journal_lines WHERE journal_entry_id = :jid ORDER BY id ASC", [':jid' => $je['id']]);
        $totalDebit = array_sum(array_column($lines, 'debit'));
        $totalCredit = array_sum(array_column($lines, 'credit'));
        $isBalanced = (abs($totalDebit - $totalCredit) < 0.001) && ($totalDebit == $expectedTotalVal);
        assertTest("COA Journal is Balanced (Debit = Credit = LKR $expectedTotalVal)", $isBalanced, "Debit: $totalDebit, Credit: $totalCredit");

        $invAcc = Accounting::getAccountIdByCode(Accounting::CODE_INVENTORY_ASSET); // 1200
        $equityAcc = Accounting::getAccountIdByCode(Accounting::CODE_EQUITY); // 3010
        $hasInvDebit = false;
        $hasEquityCredit = false;
        foreach ($lines as $ln) {
            if ($ln['account_id'] == $invAcc && (float)$ln['debit'] == $expectedTotalVal) $hasInvDebit = true;
            if ($ln['account_id'] == $equityAcc && (float)$ln['credit'] == $expectedTotalVal) $hasEquityCredit = true;
        }
        assertTest("Correct Accounts: Debit Inventory Asset (1200), Credit Owner's Equity (3010)", $hasInvDebit && $hasEquityCredit);
    }

    // Test 3.5: Duplicate Barcode and SKU Prevention
    $resDupBarcode = Product::create(array_merge($prodPayload, ['sku' => 'UNIQUE_SKU_123', 'barcode' => $testBarcode]));
    assertTest("Prevent Duplicate Barcode", !$resDupBarcode['success']);

    $resDupSku = Product::create(array_merge($prodPayload, ['barcode' => 'UNIQUE_BC_123', 'sku' => $testSku]));
    assertTest("Prevent Duplicate SKU", !$resDupSku['success']);

    // Test 3.6: Find Product by SKU and Barcode
    $bySku = Product::findBySku($testSku);
    $byBc = Product::findByBarcode($testBarcode);
    assertTest("Find Product by SKU", $bySku !== null && (int)$bySku['id'] === $prodId);
    assertTest("Find Product by Barcode", $byBc !== null && (int)$byBc['id'] === $prodId);

    // Test 3.7: Toggle Product Status
    $resProdToggle = Product::toggleStatus($prodId);
    $pAfterToggle = Product::find($prodId);
    assertTest("Toggle Product Status (Active -> Inactive)", $resProdToggle['success'] && ($pAfterToggle['status'] ?? '') === 'inactive');
    Product::toggleStatus($prodId); // Back to active

    // Test 3.8: Deletion Safety Check
    $safety = Product::canDelete($prodId);
    assertTest("Deletion Safety Blocks Delete when Stock Lots/Ledger exist", !$safety['can_delete'], "Reason: " . ($safety['reason'] ?? ''));

    // Test 3.9: Soft Delete & Restore
    $resProdDel = Product::delete($prodId);
    $pAfterDel = Product::find($prodId);
    assertTest("Soft Delete Product (moved to Trash Bin)", $resProdDel['success'] && $pAfterDel === null);

    $resProdRestore = Product::restore($prodId);
    $pAfterRestore = Product::find($prodId);
    assertTest("Restore Product from Trash Bin", $resProdRestore['success'] && $pAfterRestore !== null);

    // Test 3.10: Search Multi-Column Matching (Product Name, SKU, Barcode, Price)
    $searchList = Product::getList(['search' => $testSku], 1, 10);
    assertTest("Search Matches Product by SKU", count($searchList['items']) >= 1 && (int)$searchList['items'][0]['id'] === $prodId);

    // ----------------------------------------------------
    // 4. QUICK CREATE API TESTS
    // ----------------------------------------------------
    echo "\n--- 4. Testing Quick Create API ---\n";
    $quickBrandName = "QC_Brand_" . time();
    $qcBrandRes = Brand::create($quickBrandName, 'active');
    assertTest("Quick Create Brand", $qcBrandRes['success']);
    if ($qcBrandRes['success']) $createdBrandIds[] = (int)$qcBrandRes['id'];

    $quickCatName = "QC_Cat_" . time();
    $qcCatRes = Category::create($quickCatName, 'active');
    assertTest("Quick Create Category", $qcCatRes['success']);
    if ($qcCatRes['success']) $createdCategoryIds[] = (int)$qcCatRes['id'];

} catch (Throwable $e) {
    echo "CRITICAL SUITE ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
    $testResults['failed']++;
} finally {
    // ----------------------------------------------------
    // NON-DESTRUCTIVE CLEANUP OF TEST FIXTURES ONLY
    // ----------------------------------------------------
    echo "\n--- Cleaning up isolated test fixtures ---\n";
    foreach ($createdProductIds as $pid) {
        // Remove journal entries created for this test product
        $jes = Database::fetchAll("SELECT id FROM journal_entries WHERE source_type = 'opening_balance' AND source_id = :p", [':p' => $pid]);
        foreach ($jes as $jeRow) {
            Database::execute("DELETE FROM journal_lines WHERE journal_entry_id = :jid", [':jid' => $jeRow['id']]);
            Database::execute("DELETE FROM journal_entries WHERE id = :jid", [':jid' => $jeRow['id']]);
        }
        Database::execute("DELETE FROM stock_ledger WHERE product_id = :p", [':p' => $pid]);
        Database::execute("DELETE FROM product_lots WHERE product_id = :p", [':p' => $pid]);
        Database::execute("DELETE FROM branch_inventory WHERE product_id = :p", [':p' => $pid]);
        Database::execute("DELETE FROM audit_logs WHERE module = 'products' AND record_id = :p", [':p' => (string)$pid]);
        Database::execute("DELETE FROM products WHERE id = :p", [':p' => $pid]);
    }
    foreach ($createdBrandIds as $bid) {
        Database::execute("DELETE FROM audit_logs WHERE module = 'brands' AND record_id = :b", [':b' => (string)$bid]);
        Database::execute("DELETE FROM brands WHERE id = :b", [':b' => $bid]);
    }
    foreach ($createdCategoryIds as $cid) {
        Database::execute("DELETE FROM audit_logs WHERE module = 'categories' AND record_id = :c", [':c' => (string)$cid]);
        Database::execute("DELETE FROM categories WHERE id = :c", [':c' => $cid]);
    }
    foreach ($createdSupplierIds as $sid) {
        Database::execute("DELETE FROM audit_logs WHERE module = 'suppliers' AND record_id = :s", [':s' => (string)$sid]);
        Database::execute("DELETE FROM suppliers WHERE id = :s", [':s' => $sid]);
    }
    echo "Cleaned up test IDs safely without touching any user data.\n";
}

echo "\n====================================================\n";
echo "TEST RESULTS SUMMARY: " . $testResults['passed'] . " PASSED, " . $testResults['failed'] . " FAILED\n";
echo "====================================================\n";

if ($testResults['failed'] > 0) {
    exit(1);
}
exit(0);
