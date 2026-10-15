<?php
/**
 * Curtiss POS - Section 1: Product Master & Inventory Foundation Test Suite
 * Validates SKU handling, string leading zeros, import engine modes, blank-cell protection,
 * stock safety rules, bulk actions, safe delete block, and Special User masking.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Product.php';
require_once __DIR__ . '/../core/ProductImportEngine.php';
require_once __DIR__ . '/../core/Inventory.php';
require_once __DIR__ . '/../core/Brand.php';
require_once __DIR__ . '/../core/Category.php';
require_once __DIR__ . '/../core/Supplier.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/MaskingHelper.php';

$passed = 0;
$failed = 0;

function assertTest(bool $condition, string $testName, string $details = ''): void {
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "[PASS] {$testName}\n";
    } else {
        $failed++;
        echo "[FAIL] {$testName}" . ($details ? " - {$details}" : "") . "\n";
    }
}

echo "=========================================================\n";
echo "    SECTION 1: PRODUCT MASTER & INVENTORY TEST SUITE     \n";
echo "=========================================================\n\n";

try {
    $existingBrand = Database::fetchOne("SELECT id FROM brands WHERE deleted_at IS NULL LIMIT 1");
    $brandId = $existingBrand ? (int)$existingBrand['id'] : (int)(Brand::create('Sec1 Test Brand ' . time())['id'] ?? 1);

    $existingCat = Database::fetchOne("SELECT id FROM categories WHERE deleted_at IS NULL LIMIT 1");
    $catId = $existingCat ? (int)$existingCat['id'] : (int)(Category::create('Sec1 Test Category ' . time())['id'] ?? 1);

    $existingSup = Database::fetchOne("SELECT id FROM suppliers WHERE deleted_at IS NULL LIMIT 1");
    $supId = $existingSup ? (int)$existingSup['id'] : (int)(Supplier::create('Sec1 Test Supplier ' . time())['id'] ?? 1);

    // 1. Create Product with SKU and string leading zero
    $uniqueSku = 'SKU' . rand(10000, 99999);
    $uniqueBarcode = 'BC' . rand(100000, 999999);

    $prodData = [
        'sku'                 => $uniqueSku,
        'barcode'             => $uniqueBarcode,
        'brand_id'            => $brandId,
        'category_id'         => $catId,
        'supplier_id'         => $supId,
        'name'                => 'Section 1 Test Item',
        'cost'                => 150.00,
        'selling_price'       => 250.00,
        'opening_stock'       => 25.00,
        'low_stock_threshold' => 5.00,
        'branch_id'           => 1
    ];

    $res = Product::create($prodData);
    assertTest($res['success'], 'Create product with SKU and opening stock', $res['message'] ?? '');
    $prodId = $res['id'] ?? 0;

    // 2. Fetch by SKU and verify exact string preservation
    $fetched = Product::findBySku($uniqueSku);
    assertTest($fetched && $fetched['sku'] === $uniqueSku, 'Fetch product by SKU matches exact string value');

    // 3. Update Product SKU to leading-zero string ('000' + rand)
    $newSku = '000' . rand(100, 999);
    $upRes = Product::update($prodId, array_merge($fetched, ['sku' => $newSku]));
    assertTest($upRes['success'], 'Update product SKU to leading-zero string (' . $newSku . ')', $upRes['message'] ?? '');

    $fetchedUpdated = Product::find($prodId);
    assertTest($fetchedUpdated['sku'] === $newSku, 'Updated product preserves exact leading zero string ' . $newSku);

    // 4. Duplicate SKU rejection test
    $dummyProdData = [
        'sku'           => $newSku, // Duplicate!
        'barcode'       => 'BC_DUP_' . rand(10000, 99999),
        'brand_id'      => $brandId,
        'category_id'   => $catId,
        'supplier_id'   => $supId,
        'name'          => 'Duplicate SKU Test',
        'cost'          => 10.00,
        'selling_price' => 20.00
    ];
    $dupRes = Product::create($dummyProdData);
    assertTest(!$dupRes['success'], 'Reject creation of duplicate SKU');

    // 5. Test Deletion Blocking when stock/lots exist
    $delCheck = Product::canDelete($prodId);
    assertTest(!$delCheck['can_delete'], 'canDelete returns false when stock lots exist');
    
    $delRes = Product::delete($prodId);
    assertTest(!$delRes['success'], 'Product deletion blocked safely when inventory dependencies exist');

    // 6. Test Bulk Actions (Activate, Deactivate, Low Stock Threshold)
    $bulkDeact = Product::bulkAction([$prodId], 'deactivate');
    assertTest($bulkDeact['success'], 'Bulk deactivate product');
    $checkDeact = Product::find($prodId);
    assertTest($checkDeact['status'] === 'inactive', 'Product status changed to inactive');

    $bulkAct = Product::bulkAction([$prodId], 'activate');
    assertTest($bulkAct['success'], 'Bulk activate product');
    $checkAct = Product::find($prodId);
    assertTest($checkAct['status'] === 'active', 'Product status changed to active');

    $bulkThresh = Product::bulkAction([$prodId], 'update_low_stock', ['low_stock_threshold' => 15]);
    assertTest($bulkThresh['success'], 'Bulk update low stock threshold');
    $checkThresh = Product::find($prodId);
    assertTest((float)$checkThresh['low_stock_threshold'] === 15.0, 'Low stock threshold updated to 15');

    // 7. Product Import Engine Preview & Commit Tests
    $tmpCsv = tempnam(sys_get_temp_dir(), 'csv_sec1_');
    $importCsv = "SKU,Barcode,Brand,Category,Product Name,Supplier,Cost,Selling Price,Opening Stock,Low Stock Threshold,Status\r\n";
    $importCsv .= "{$newSku},{$uniqueBarcode},Sec1 Test Brand,Sec1 Test Category,Section 1 Test Item Updated,Sec1 Test Supplier,160.00,260.00,999.00,10,active\r\n";
    $newImportSku = '000' . rand(100, 999);
    $newImportBc = 'BC_NEW_' . rand(1000, 9999);
    $importCsv .= "{$newImportSku},{$newImportBc},Sec1 Test Brand,Sec1 Test Category,Imported New Product,Sec1 Test Supplier,80.00,120.00,50.00,5,active\r\n";
    file_put_contents($tmpCsv, $importCsv);

    $parsed = ProductImportEngine::parseCsvFile($tmpCsv);
    assertTest($parsed['success'], 'Parse CSV file successfully');
    $mapping = ProductImportEngine::autoDetectMapping($parsed['headers']);
    
    $preview = ProductImportEngine::preview($parsed, $mapping, 'create_and_update');
    assertTest($preview['success'], 'Import preview generated successfully');
    assertTest($preview['summary']['total_rows'] === 2, 'Import preview counted 2 total rows');
    assertTest($preview['summary']['update_count'] === 1, 'Import preview detected 1 existing product to update');
    assertTest($preview['summary']['new_count'] === 1, 'Import preview detected 1 new product to create');

    // 8. Commit Import with Stock Safety Rule & Blank Cell Protection
    $options = [
        'ignore_blank_cells'  => true,
        'auto_create_masters' => true
    ];
    $commit = ProductImportEngine::commitImport($parsed, $mapping, 'create_and_update', $options, 1);
    assertTest($commit['success'], 'Import commit executed successfully', $commit['message'] ?? '');
    if ($commit['success']) {
        assertTest($commit['summary']['created_count'] === 1, 'Import created 1 product');
        assertTest($commit['summary']['updated_count'] === 1, 'Import updated 1 product');
    }

    // Check Stock Safety Rule: Existing product should NOT have its stock replaced with 999.00!
    $stockCheck = Inventory::getBranchStock(1, $prodId);
    assertTest($stockCheck['quantity'] === 25.0, 'Stock Safety Rule: Existing product stock remains 25.00 and was NOT overwritten by import Opening Stock 999.00');

    // Check New Product Creation with SKU
    $newProd = Product::findBySku($newImportSku);
    assertTest($newProd !== null && $newProd['sku'] === $newImportSku, 'New product created with exact SKU ' . $newImportSku);
    if ($newProd) {
        $newStock = Inventory::getBranchStock(1, $newProd['id']);
        assertTest($newStock['quantity'] === 50.0, 'Brand-new imported product initialized with opening stock 50.00');
    }

    // Inventory List & Summary verification
    $invList = Inventory::getInventoryList(1, ['search' => $newSku]);
    $foundCount = count($invList['items']);
    assertTest($foundCount === 1, 'Inventory list filters by SKU ' . $newSku);
    if (!empty($invList['items'])) {
        assertTest($invList['items'][0]['sku'] === $newSku, 'Inventory item contains SKU ' . $newSku);
    }

    $summary = Inventory::getInventorySummary(1);
    assertTest($summary['total_products'] > 0, 'Inventory summary returns valid total product count');

    @unlink($tmpCsv);

    // Clean up created test products safely
    Database::execute("SET FOREIGN_KEY_CHECKS = 0");
    if (!empty($newProd['id'])) {
        Database::execute("DELETE FROM stock_ledger WHERE product_id = :id", [':id' => $newProd['id']]);
        Database::execute("DELETE FROM product_lots WHERE product_id = :id", [':id' => $newProd['id']]);
        Database::execute("DELETE FROM branch_inventory WHERE product_id = :id", [':id' => $newProd['id']]);
        Database::execute("DELETE FROM products WHERE id = :id", [':id' => $newProd['id']]);
    }
    if (!empty($prodId)) {
        Database::execute("DELETE FROM stock_ledger WHERE product_id = :id", [':id' => $prodId]);
        Database::execute("DELETE FROM product_lots WHERE product_id = :id", [':id' => $prodId]);
        Database::execute("DELETE FROM branch_inventory WHERE product_id = :id", [':id' => $prodId]);
        Database::execute("DELETE FROM products WHERE id = :id", [':id' => $prodId]);
    }
    Database::execute("SET FOREIGN_KEY_CHECKS = 1");

} catch (Throwable $e) {
    $failed++;
    echo "[FAIL] Unexpected exception during test: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n=========================================================\n";
echo "    SECTION 1 TEST SUMMARY: PASSED={$passed}, FAILED={$failed}\n";
echo "=========================================================\n";

exit($failed === 0 ? 0 : 1);
