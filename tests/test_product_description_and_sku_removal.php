<?php
/**
 * Test Product Description and SKU Removal Upgrades
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Product.php';
require_once __DIR__ . '/../core/Brand.php';
require_once __DIR__ . '/../core/Category.php';
require_once __DIR__ . '/../core/Supplier.php';

echo "=== STARTING PRODUCT DESCRIPTION & SKU REMOVAL TEST ===\n\n";

$db = Database::getInstance();
$branchId = 1;

// 1. Create fixtures: Brand, Category, Supplier
$brand = Brand::create('TEST_BRAND_DESC_' . rand(1000, 9999), 'active');
$cat = Category::create('TEST_CAT_DESC_' . rand(1000, 9999), 'active');
$sup = Supplier::create('TEST_SUP_DESC_' . rand(1000, 9999), '0714444444', null, 'Colombo', 'active');

$brandId = (int)$brand['id'];
$catId = (int)$cat['id'];
$supId = (int)$sup['id'];

$brandName = "TestBrand";
$catName = "GamingLaptop";
$descText = "ROG Zephyrus G14 16GB";

// Expected auto-composed name
$expectedName = "$brandName $catName $descText";

// 2. Test Product Creation with description and no SKU provided
$barcode = "TEST_BC_" . rand(100000, 999999);
$createData = [
    'barcode'             => $barcode,
    // Note: 'sku' is NOT provided (completely removed from form)
    'brand_id'            => $brandId,
    'category_id'         => $catId,
    'description'         => $descText,
    'name'                => $expectedName,
    'supplier_id'         => $supId,
    'supplier_ids'        => [$supId],
    'cost'                => 1500.00,
    'selling_price'       => 2000.00,
    'opening_stock'       => 0.00,
    'low_stock_threshold' => 5.00,
    'status'              => 'active',
    'branch_id'           => $branchId
];

$res = Product::create($createData);
assert($res['success'] === true, "Product::create failed: " . ($res['message'] ?? ''));
$productId = (int)$res['id'];
echo "  [PASS] Product created successfully with ID: $productId without manual SKU\n";

// 3. Verify Product in Database has description and sku = barcode
$found = Product::find($productId, $branchId);
assert(!empty($found), "Product not found via Product::find()");
assert($found['description'] === $descText, "Saved description does not match expected description");
assert($found['sku'] === $barcode, "SKU was not automatically set to Barcode ($barcode vs {$found['sku']})");
assert($found['name'] === $expectedName, "Product name does not match expected name");
echo "  [PASS] Product::find verified: description is '$descText' and SKU defaulted to '$barcode'\n";

// 4. Test Product Update with modified description and auto-composed name
$newDesc = "ROG Strix SCAR 18";
$newExpectedName = "$brandName $catName $newDesc";

$updateData = [
    'barcode'             => $barcode,
    'brand_id'            => $brandId,
    'category_id'         => $catId,
    'description'         => $newDesc,
    'name'                => $newExpectedName,
    'supplier_id'         => $supId,
    'supplier_ids'        => [$supId],
    'cost'                => 1600.00,
    'selling_price'       => 2200.00,
    'opening_stock'       => 0.00,
    'low_stock_threshold' => 5.00,
    'status'              => 'active'
];

$updateRes = Product::update($productId, $updateData);
assert($updateRes['success'] === true, "Product::update failed: " . ($updateRes['message'] ?? ''));

$updated = Product::find($productId, $branchId);
assert($updated['description'] === $newDesc, "Updated description does not match");
assert($updated['name'] === $newExpectedName, "Updated name does not match");
echo "  [PASS] Product::update verified: description updated to '$newDesc' and name updated\n";

// 5. Clean up fixtures
$pdo = $db->getConnection();
$pdo->prepare("DELETE FROM product_suppliers WHERE product_id = :pid")->execute([':pid' => $productId]);
$pdo->prepare("DELETE FROM stock_ledger WHERE product_id = :pid")->execute([':pid' => $productId]);
$pdo->prepare("DELETE FROM branch_inventory WHERE product_id = :pid")->execute([':pid' => $productId]);
$pdo->prepare("DELETE FROM product_lots WHERE product_id = :pid")->execute([':pid' => $productId]);
$pdo->prepare("DELETE FROM products WHERE id = :pid")->execute([':pid' => $productId]);
$pdo->prepare("DELETE FROM brands WHERE id = :id")->execute([':id' => $brandId]);
$pdo->prepare("DELETE FROM categories WHERE id = :id")->execute([':id' => $catId]);
$pdo->prepare("DELETE FROM suppliers WHERE id = :id")->execute([':id' => $supId]);

echo "  [DONE] Cleaned up isolated test records.\n\n";
echo "=== ALL PRODUCT DESCRIPTION & SKU REMOVAL TESTS PASSED CLEANLY ===\n";
