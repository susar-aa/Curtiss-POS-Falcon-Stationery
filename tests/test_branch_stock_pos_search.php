<?php
/**
 * Test Suite: Multi-Branch Out-of-Stock Isolation in POS Search
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Sale.php';
require_once __DIR__ . '/../core/Product.php';
require_once __DIR__ . '/../core/Inventory.php';

$passed = 0;
$failed = 0;
$testsRun = 0;

function assertTest(bool $condition, string $testName, string $details = '') {
    global $passed, $failed, $testsRun;
    $testsRun++;
    if ($condition) {
        $passed++;
        echo "[PASS] {$testsRun}. {$testName}\n";
    } else {
        $failed++;
        echo "[FAIL] {$testsRun}. {$testName} - {$details}\n";
    }
}

echo "=== TESTING MULTI-BRANCH OUT-OF-STOCK ISOLATION ===\n\n";

$db = Database::getInstance()->getConnection();

// Setup session context
$_SESSION['user_id'] = 1;
$_SESSION['role_id'] = 1;
$_SESSION['role_code'] = 'super_admin';
$_SESSION['branch_id'] = 1;

// 1. Identify two distinct branches
$branches = Database::fetchAll("SELECT id, name FROM branches WHERE status = 'active' ORDER BY id ASC LIMIT 2");
if (count($branches) < 2) {
    echo "[WARN] Need at least 2 branches for multi-branch test. Creating temporary branch...\n";
    $db->prepare("INSERT INTO branches (name, code, status) VALUES ('Branch B Test', 'BR-B-TEST', 'active')")->execute();
    $b2Id = (int)$db->lastInsertId();
    $b1Id = (int)$branches[0]['id'];
    $tempBranchCreated = true;
} else {
    $b1Id = (int)$branches[0]['id'];
    $b2Id = (int)$branches[1]['id'];
    $tempBranchCreated = false;
}

echo "Testing with Branch 1 (ID: {$b1Id}) and Branch 2 (ID: {$b2Id})\n";

// 2. Create isolated test product
$testBarcode = 'TEST-BALL-' . time();
$testSku = 'SKU-BALL-' . time();
$testName = 'Test Super Ball ' . rand(1000, 9999);

$brand = Database::fetchOne("SELECT id FROM brands WHERE deleted_at IS NULL LIMIT 1");
$category = Database::fetchOne("SELECT id FROM categories WHERE deleted_at IS NULL LIMIT 1");
$supplier = Database::fetchOne("SELECT id FROM suppliers WHERE deleted_at IS NULL LIMIT 1");

$db->prepare("
    INSERT INTO products (sku, barcode, name, brand_id, category_id, supplier_id, cost, selling_price, status, created_at, updated_at)
    VALUES (:sku, :bc, :name, :bid, :cid, :sid, 50.00, 100.00, 'active', NOW(), NOW())
")->execute([
    ':sku'  => $testSku,
    ':bc'   => $testBarcode,
    ':name' => $testName,
    ':bid'  => (int)$brand['id'],
    ':cid'  => (int)$category['id'],
    ':sid'  => (int)$supplier['id']
]);
$testProductId = (int)$db->lastInsertId();

assertTest($testProductId > 0, "Created isolated test product '{$testName}' (ID: {$testProductId})");

// 3. Add 10 stock to Branch 1
Inventory::addStockLot(
    $b1Id,
    $testProductId,
    50.00,
    100.00,
    10.00,
    'LOT-B1-' . time(),
    'INIT-BR1',
    'Branch 1 stock lot',
    null,
    'purchase',
    null,
    null,
    'test',
    1
);

// 4. Add 2 stock to Branch 2
Inventory::addStockLot(
    $b2Id,
    $testProductId,
    50.00,
    100.00,
    2.00,
    'LOT-B2-' . time(),
    'INIT-BR2',
    'Branch 2 stock lot',
    null,
    'purchase',
    null,
    null,
    'test',
    1
);

// 5. Test search in Branch 1 (should find with stock = 10)
$searchB1 = Sale::searchProducts($b1Id, $testBarcode);
assertTest(
    !empty($searchB1) && (int)$searchB1[0]['product_id'] === $testProductId,
    "Branch 1 search finds product by barcode"
);
assertTest(
    !empty($searchB1) && (float)$searchB1[0]['total_branch_stock'] === 10.00,
    "Branch 1 search shows exactly 10 units available"
);

// 6. Test search in Branch 2 (should find with stock = 2)
$searchB2 = Sale::searchProducts($b2Id, $testBarcode);
assertTest(
    !empty($searchB2) && (int)$searchB2[0]['product_id'] === $testProductId,
    "Branch 2 search finds product by barcode"
);
assertTest(
    !empty($searchB2) && (float)$searchB2[0]['total_branch_stock'] === 2.00,
    "Branch 2 search shows exactly 2 units available"
);

// 7. Bill 2 units from Branch 2 (depleting Branch 2 stock to 0)
$lotB2 = Database::fetchOne("
    SELECT id FROM product_lots WHERE product_id = :p AND branch_id = :b AND remaining_quantity > 0 AND status = 'active' LIMIT 1
", [':p' => $testProductId, ':b' => $b2Id]);

assertTest(!empty($lotB2), "Found active lot for Branch 2 before billing");

$draftSale = Sale::prepareDraftSale([
    'customer_id' => 1,
    'branch_id'   => $b2Id,
    'user_id'     => 1,
    'items'       => [
        [
            'product_id' => $testProductId,
            'lot_id'     => (int)$lotB2['id'],
            'qty'        => 2.00,
            'quantity'   => 2.00,
            'unit_price' => 100.00
        ]
    ]
], 1, $b2Id);
assertTest($draftSale['success'] === true, "Prepared draft sale for 2 units in Branch 2");

$saleId = (int)$draftSale['sale_id'];
$finalizeRes = Sale::finalizeSale($saleId, [['payment_method' => 'cash', 'amount' => 200.00]], 1, $b2Id);
assertTest($finalizeRes['success'] === true, "Finalized sale in Branch 2 successfully: " . ($finalizeRes['message'] ?? ''));

// 8. Verify Branch 2 inventory is 0
$biB2 = Database::fetchOne("SELECT quantity FROM branch_inventory WHERE product_id = :p AND branch_id = :b", [':p' => $testProductId, ':b' => $b2Id]);
assertTest((float)($biB2['quantity'] ?? -1) === 0.00, "Branch 2 inventory quantity dropped to 0.00");

// 9. Verify product status REMAINS 'active' (DOES NOT globally deactivate)
$prodAfter = Database::fetchOne("SELECT status FROM products WHERE id = :p", [':p' => $testProductId]);
assertTest(
    $prodAfter['status'] === 'active',
    "Product master status REMAINS 'active' after Branch 2 stock reached 0"
);

// 10. Verify Branch 2 search now DOES NOT show product (Out of stock in Branch 2)
$searchB2After = Sale::searchProducts($b2Id, $testBarcode);
assertTest(
    empty($searchB2After),
    "Branch 2 search DOES NOT show product when out of stock in Branch 2"
);

// 11. Verify Branch 1 search STILL shows product with 10 units!
$searchB1After = Sale::searchProducts($b1Id, $testBarcode);
assertTest(
    !empty($searchB1After) && (int)$searchB1After[0]['product_id'] === $testProductId,
    "Branch 1 search STILL finds product because Branch 1 has stock"
);
assertTest(
    !empty($searchB1After) && (float)$searchB1After[0]['total_branch_stock'] === 10.00,
    "Branch 1 search still shows 10 units available for sale"
);

// CLEANUP: Isolated Test Data Only
echo "\n--- Cleaning up test records ---\n";
try {
    $db->prepare("DELETE FROM payments WHERE sale_id = :s")->execute([':s' => $saleId]);
    $db->prepare("DELETE FROM stock_ledger WHERE product_id = :p")->execute([':p' => $testProductId]);
    $db->prepare("DELETE FROM sale_items WHERE sale_id = :s")->execute([':s' => $saleId]);
    $db->prepare("DELETE FROM sales WHERE id = :s")->execute([':s' => $saleId]);
    $db->prepare("DELETE FROM branch_inventory WHERE product_id = :p")->execute([':p' => $testProductId]);
    $db->prepare("DELETE FROM product_lots WHERE product_id = :p")->execute([':p' => $testProductId]);
    $db->prepare("DELETE FROM products WHERE id = :p")->execute([':p' => $testProductId]);
    if (!empty($tempBranchCreated) && !empty($b2Id)) {
        $db->prepare("DELETE FROM branches WHERE id = :b")->execute([':b' => $b2Id]);
    }
} catch (Throwable $e) {
    echo "Cleanup error: " . $e->getMessage() . "\n";
}

echo "\n=========================================\n";
echo "TEST RESULTS: Total: {$testsRun} | Passed: {$passed} | Failed: {$failed}\n";
echo "=========================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
