<?php
/**
 * Curtiss POS - Stage 2 Automated Structural Verification Script
 * Validates Stage 1 Corrections & Stage 2 Product Master Modules.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Brand.php';
require_once __DIR__ . '/../core/Category.php';
require_once __DIR__ . '/../core/Supplier.php';
require_once __DIR__ . '/../core/Product.php';

echo "========================================================\n";
echo " CURTISS POS - STAGE 2 AUTOMATED TEST SUITE\n";
echo "========================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest(bool $condition, string $testName): void
{
    global $passCount, $failCount;
    if ($condition) {
        echo " [PASS] $testName\n";
        $passCount++;
    } else {
        echo " [FAIL] $testName\n";
        $failCount++;
    }
}

// ----------------------------------------------------------------------------
// 1. Stage 1 Corrections Verification
// ----------------------------------------------------------------------------
$timeout = (int)Settings::get('session_inactivity_timeout');
assertTest($timeout === 3600, "Stage 1 Correction: Inactivity Timeout = 3600 seconds (Found $timeout)");

$openingCash = (float)Settings::get('default_opening_cash');
assertTest($openingCash === 20000.00, "Stage 1 Correction: Default Opening Cash = LKR 20,000 (Found LKR $openingCash)");

// ----------------------------------------------------------------------------
// 2. Brand CRUD & Duplicate Name Prevention
// ----------------------------------------------------------------------------
$suffix = rand(100000, 999999) . '_' . time();
$brandName = "Test Brand Alpha " . $suffix;
$brandRes = Brand::create($brandName);
assertTest($brandRes['success'] === true, "Brand Creation Success: '$brandName'");
$brandId = $brandRes['id'];

$dupBrandRes = Brand::create(strtolower($brandName));
assertTest($dupBrandRes['success'] === false, "Brand Duplicate Prevention: Rejected duplicate name '$brandName'");

$brandUpdateRes = Brand::update($brandId, $brandName . " Updated", "active");
assertTest($brandUpdateRes['success'] === true, "Brand Update Success");

// ----------------------------------------------------------------------------
// 3. Category CRUD & Duplicate Name Prevention
// ----------------------------------------------------------------------------
$catName = "Test Category Beta " . $suffix;
$catRes = Category::create($catName);
assertTest($catRes['success'] === true, "Category Creation Success: '$catName'");
$catId = $catRes['id'];

$dupCatRes = Category::create(strtolower($catName));
assertTest($dupCatRes['success'] === false, "Category Duplicate Prevention: Rejected duplicate name '$catName'");

$catUpdateRes = Category::update($catId, $catName . " Updated", "active");
assertTest($catUpdateRes['success'] === true, "Category Update Success");

// ----------------------------------------------------------------------------
// 4. Supplier CRUD
// ----------------------------------------------------------------------------
$supName = "Test Supplier Gamma " . $suffix;
$supRes = Supplier::create($supName, "+94 11 999 8888", "+94 77 999 8888", "123 Commercial Street");
assertTest($supRes['success'] === true, "Supplier Creation Success: '$supName'");
$supId = $supRes['id'];

$supUpdateRes = Supplier::update($supId, $supName . " Updated", "+94 11 999 8888", "+94 77 999 8888", "123 Commercial Street", "active");
assertTest($supUpdateRes['success'] === true, "Supplier Update Success");

// ----------------------------------------------------------------------------
// 5. Product Master CRUD & Validation
// ----------------------------------------------------------------------------
$testBarcode = "999" . rand(100000001, 999999999);
$prodData = [
    'barcode'             => $testBarcode,
    'brand_id'            => $brandId,
    'category_id'         => $catId,
    'name'                => 'Test Product Item ' . $suffix,
    'supplier_id'         => $supId,
    'cost'                => 150.00,
    'selling_price'       => 220.00,
    'opening_stock'       => 75.00,
    'low_stock_threshold' => 10.00,
    'status'              => 'active'
];

$prodRes = Product::create($prodData);
assertTest($prodRes['success'] === true, "Product Creation Success with Barcode $testBarcode");
$prodId = $prodRes['id'];

// Test Barcode Uniqueness Validation
$dupBarcodeRes = Product::create(array_merge($prodData, ['name' => 'Different Product']));
assertTest($dupBarcodeRes['success'] === false, "Product Barcode Uniqueness Validation: Rejected duplicate barcode");

// Test Negative Stock Validation
$negStockData = array_merge($prodData, ['barcode' => '999888777999', 'opening_stock' => -10]);
$negStockRes = Product::create($negStockData);
assertTest($negStockRes['success'] === false, "Product Negative Stock Validation: Rejected negative stock (-10)");

// Test Negative Cost Validation
$negCostData = array_merge($prodData, ['barcode' => '999888777998', 'cost' => -50]);
$negCostRes = Product::create($negCostData);
assertTest($negCostRes['success'] === false, "Product Negative Cost Validation: Rejected negative cost (-50)");

// Test Product Retrieval & Field Ordering
$fetchedProd = Product::find($prodId);
assertTest($fetchedProd !== null && $fetchedProd['barcode'] === $testBarcode, "Product Retrieval Integrity Verified");
assertTest((float)$fetchedProd['low_stock_threshold'] === 10.00, "Low Stock Default Threshold Verified = 10");

// ----------------------------------------------------------------------------
// 6. Audit Logging Verification for Product Operations
// ----------------------------------------------------------------------------
$auditProduct = Database::fetchOne("SELECT * FROM audit_logs WHERE action = 'PRODUCT_CREATED' AND record_id = :id", [':id' => $prodId]);
assertTest($auditProduct !== null, "Audit Log Verification: PRODUCT_CREATED event recorded");

// ----------------------------------------------------------------------------
// 7. Auth & Multi-Branch Regression Test
// ----------------------------------------------------------------------------
$authRes = Auth::attempt('superadmin', 'Password@123');
assertTest($authRes['success'] === true, "Auth Regression Test: Super Admin Login Success");

echo "\n--------------------------------------------------------\n";
echo " VERIFICATION SUMMARY: $passCount Passed, $failCount Failed.\n";
echo "========================================================\n";

if ($failCount > 0) {
    exit(1);
}
