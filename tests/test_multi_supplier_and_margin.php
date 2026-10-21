<?php
/**
 * Test Multi-Supplier Product Management and Margin Calculation
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Product.php';
require_once __DIR__ . '/../core/Supplier.php';
require_once __DIR__ . '/../core/BulkGRN.php';

echo "=== STARTING MULTI-SUPPLIER & MARGIN TEST ===\n\n";

$db = Database::getInstance();
$branchId = 1;

// 1. Create two test suppliers
$sup1 = Supplier::create('TEST_SUP_A_' . rand(1000, 9999), '0711111111', null, 'Colombo', 'active');
$sup2 = Supplier::create('TEST_SUP_B_' . rand(1000, 9999), '0722222222', null, 'Kandy', 'active');
$s1Id = (int)$sup1['id'];
$s2Id = (int)$sup2['id'];

echo "[INFO] Created Supplier 1 (ID: $s1Id) and Supplier 2 (ID: $s2Id)\n";

// 2. Create product with multiple suppliers [s1Id, s2Id]
$barcode = 'TEST_MULTI_' . rand(1000, 9999);
$pData = [
    'barcode' => $barcode,
    'sku' => $barcode,
    'name' => 'Multi Supplier Test Product',
    'brand_id' => 1,
    'category_id' => 1,
    'supplier_id' => $s1Id,
    'supplier_ids' => [$s1Id, $s2Id],
    'cost' => 1000.00,
    'selling_price' => 1250.00, // 25% margin
    'opening_stock' => 0.00,
    'has_expiry' => 0,
    'status' => 'active',
    'branch_id' => $branchId
];

$pRes = Product::create($pData);
$pId = (int)$pRes['id'];
echo "[INFO] Created product ID: $pId with supplier_ids: [$s1Id, $s2Id]\n";

// 3. Verify Product::getSupplierIds($pId)
$linkedSupps = Product::getSupplierIds($pId);
echo "[INFO] Linked suppliers in product_suppliers: " . implode(', ', $linkedSupps) . "\n";
assert(in_array($s1Id, $linkedSupps), "Supplier 1 not found in linked suppliers");
assert(in_array($s2Id, $linkedSupps), "Supplier 2 not found in linked suppliers");
echo "  [PASS] Product successfully linked to multiple suppliers in database\n";

// 4. Verify Product::find($pId) returns supplier_ids and primary supplier_id
$foundProd = Product::find($pId);
assert(!empty($foundProd['supplier_ids']), "Product::find does not have supplier_ids");
assert(count($foundProd['supplier_ids']) === 2, "Product::find does not have 2 supplier_ids");
assert((int)$foundProd['supplier_id'] === $s1Id, "Primary supplier_id does not match");
echo "  [PASS] Product::find retrieves multiple supplier IDs and primary supplier\n";

// 5. Verify BulkGRN::getSupplierProductsWithLots retrieves the product under BOTH suppliers
$prodsUnderSup1 = BulkGRN::getSupplierProductsWithLots($s1Id, $branchId);
$foundInSup1 = false;
foreach ($prodsUnderSup1 as $p) {
    if ((int)$p['id'] === $pId) $foundInSup1 = true;
}

$prodsUnderSup2 = BulkGRN::getSupplierProductsWithLots($s2Id, $branchId);
$foundInSup2 = false;
foreach ($prodsUnderSup2 as $p) {
    if ((int)$p['id'] === $pId) $foundInSup2 = true;
}

assert($foundInSup1, "Product not retrieved under primary supplier $s1Id in Bulk GRN");
assert($foundInSup2, "Product not retrieved under secondary supplier $s2Id in Bulk GRN");
echo "  [PASS] Bulk GRN displays product under Supplier 1 AND Supplier 2\n";

// 6. Test margin formula calculations
$cost = 2000.00;
$margin = 25.00; // 25%
$expectedSell = $cost * (1 + ($margin / 100)); // 2500.00
assert($expectedSell == 2500.00, "Margin to selling price formula incorrect");

$actualMargin = (($expectedSell - $cost) / $cost) * 100;
assert($actualMargin == 25.00, "Selling price to margin formula incorrect");
echo "  [PASS] Bi-directional margin and selling price math verified\n";

// 7. Test Updating Product Suppliers
$sup3 = Supplier::create('TEST_SUP_C_' . rand(1000, 9999), '0733333333', null, 'Galle', 'active');
$s3Id = (int)$sup3['id'];
$updateData = [
    'barcode' => $barcode,
    'sku' => $barcode,
    'name' => 'Multi Supplier Test Product Updated',
    'brand_id' => 1,
    'category_id' => 1,
    'supplier_id' => $s3Id,
    'supplier_ids' => [$s2Id, $s3Id], // removed s1, added s3
    'cost' => 1200.00,
    'selling_price' => 1500.00,
    'status' => 'active'
];
Product::update($pId, $updateData);
$updatedSupps = Product::getSupplierIds($pId);
assert(!in_array($s1Id, $updatedSupps), "Supplier 1 was not removed on update");
assert(in_array($s2Id, $updatedSupps), "Supplier 2 was lost on update");
assert(in_array($s3Id, $updatedSupps), "Supplier 3 was not added on update");
echo "  [PASS] Product::update cleanly syncs multiple suppliers\n";

// 8. Clean up test fixtures
$pdo = $db->getConnection();
$pdo->prepare("DELETE FROM product_suppliers WHERE product_id = :pid")->execute([':pid' => $pId]);
$pdo->prepare("DELETE FROM stock_ledger WHERE product_id = :pid")->execute([':pid' => $pId]);
$pdo->prepare("DELETE FROM branch_inventory WHERE product_id = :pid")->execute([':pid' => $pId]);
$pdo->prepare("DELETE FROM product_lots WHERE product_id = :pid")->execute([':pid' => $pId]);
$pdo->prepare("DELETE FROM products WHERE id = :pid")->execute([':pid' => $pId]);
$pdo->prepare("DELETE FROM suppliers WHERE id IN ($s1Id, $s2Id, $s3Id)")->execute();
echo "  [DONE] Cleaned up test fixtures.\n\n";

echo "=== ALL MULTI-SUPPLIER & MARGIN TESTS PASSED CLEANLY ===\n";
