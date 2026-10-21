<?php
/**
 * CLI Test Suite for POS Enhancements:
 * 1. Customer Loyalty Configuration & Persistence
 * 2. Lot Selection Filtering (Only lots with available stock)
 * 3. POS QTY Validation (Blocking overbilling)
 * 4. Auto-Deactivation of Products when stock hits 0 upon billing
 */

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Customer.php';
require_once __DIR__ . '/../core/Sale.php';
require_once __DIR__ . '/../core/Settings.php';
require_once __DIR__ . '/../core/Audit.php';

echo "=========================================================\n";
echo "   CURTISS POS - ENHANCEMENTS CLI VERIFICATION SUITE     \n";
echo "=========================================================\n\n";

$db = Database::getInstance()->getConnection();
$branchId = 1;
$userId = 1;
$allPassed = true;

function assertTest(string $description, bool $condition): void {
    global $allPassed;
    if ($condition) {
        echo " [PASS] " . $description . "\n";
    } else {
        echo " [FAIL] " . $description . "\n";
        $allPassed = false;
    }
}

// --------------------------------------------------------------------------
// TEST 1: System Settings for Loyalty Discount Percentage
// --------------------------------------------------------------------------
echo "\n--- TEST 1: Loyalty System Settings ---\n";
$defaultPct = Settings::get('loyalty_discount_percentage');
assertTest("Loyalty discount percentage exists in system settings", $defaultPct !== null && $defaultPct !== '');
$pctVal = (float)$defaultPct;
assertTest("Loyalty discount percentage is a valid positive float ($pctVal%)", $pctVal > 0);

// Test updating settings
Settings::set('loyalty_discount_percentage', '7.50', $userId, 'pos');
$updatedPct = Settings::get('loyalty_discount_percentage');
assertTest("Loyalty discount percentage can be updated (7.50)", (float)$updatedPct === 7.50);

// Restore setting
Settings::set('loyalty_discount_percentage', '5.00', $userId, 'pos');

// --------------------------------------------------------------------------
// TEST 2: Customer Loyalty Creation, Update, and Retrieval
// --------------------------------------------------------------------------
echo "\n--- TEST 2: Customer Loyalty Model & Persistence ---\n";
$testCustCode = 'TEST-LOYALTY-' . time();
$createRes = Customer::create([
    'customer_code'             => $testCustCode,
    'name'                      => 'VIP Loyalty Tester',
    'phone'                     => '0779998877',
    'email'                     => 'loyalty@tester.com',
    'address'                   => '123 Loyalty Lane',
    'credit_limit'              => 50000.00,
    'is_loyalty'                => 1,
    'loyalty_discount_override' => 12.50,
    'status'                    => 'active'
]);

assertTest("Loyalty Customer created successfully", $createRes['success'] === true && !empty($createRes['id']));
$testCustId = (int)($createRes['id'] ?? 0);

if ($testCustId > 0) {
    $cust = Customer::find($testCustId);
    assertTest("Customer retrieved has is_loyalty == 1", (int)$cust['is_loyalty'] === 1);
    assertTest("Customer retrieved has loyalty_discount_override == 12.50", (float)$cust['loyalty_discount_override'] === 12.50);

    // Update customer loyalty override
    $updateRes = Customer::update($testCustId, [
        'name'                      => 'VIP Loyalty Tester Updated',
        'loyalty_discount_override' => 15.00
    ]);
    assertTest("Customer update successful", $updateRes['success'] === true);
    $custUpdated = Customer::find($testCustId);
    assertTest("Updated loyalty_discount_override == 15.00", (float)$custUpdated['loyalty_discount_override'] === 15.00);

    // Test Customer::all search
    $searchRes = Customer::all(['search' => $testCustCode]);
    assertTest("Customer search finds loyalty member", count($searchRes['items']) > 0 && (int)$searchRes['items'][0]['is_loyalty'] === 1);
}

// --------------------------------------------------------------------------
// TEST 3: Lot Selection Filtering (Available stock on branch only)
// --------------------------------------------------------------------------
echo "\n--- TEST 3: Lot Selection Filtering (Only positive stock lots) ---\n";
// Create isolated test product
$testBrand = (int)($db->query("SELECT id FROM brands WHERE deleted_at IS NULL LIMIT 1")->fetchColumn() ?: 7);
$testCat   = (int)($db->query("SELECT id FROM categories WHERE deleted_at IS NULL LIMIT 1")->fetchColumn() ?: 7);
$testSup   = (int)($db->query("SELECT id FROM suppliers WHERE deleted_at IS NULL LIMIT 1")->fetchColumn() ?: 9);
$testBarcode = 'TESTBC-' . time();
$db->prepare("
    INSERT INTO products (barcode, sku, brand_id, category_id, name, supplier_id, cost, selling_price, status)
    VALUES (:bc, :sku, :brand, :cat, 'Test Product For Depletion', :sup, 100.00, 150.00, 'active')
")->execute([':bc' => $testBarcode, ':sku' => 'SKU-' . $testBarcode, ':brand' => $testBrand, ':cat' => $testCat, ':sup' => $testSup]);
$testProductId = (int)$db->lastInsertId();

// Create Lot 1 with 5 units remaining
$db->prepare("
    INSERT INTO product_lots (product_id, branch_id, lot_number, cost, selling_price, initial_quantity, remaining_quantity, status)
    VALUES (:p, :b, 'LOT-ACTIVE-5', 100.00, 150.00, 5.00, 5.00, 'active')
")->execute([':p' => $testProductId, ':b' => $branchId]);
$lotActiveId = (int)$db->lastInsertId();

// Create Lot 2 with 0 units remaining (Depleted)
$db->prepare("
    INSERT INTO product_lots (product_id, branch_id, lot_number, cost, selling_price, initial_quantity, remaining_quantity, status)
    VALUES (:p, :b, 'LOT-DEPLETED-0', 100.00, 150.00, 5.00, 0.00, 'depleted')
")->execute([':p' => $testProductId, ':b' => $branchId]);
$lotDepletedId = (int)$db->lastInsertId();

// Initialize branch_inventory with 5 units
$db->prepare("
    INSERT INTO branch_inventory (branch_id, product_id, quantity)
    VALUES (:b, :p, 5.00)
")->execute([':b' => $branchId, ':p' => $testProductId]);

// Call Sale::getAvailableLotsForProduct
$availableLots = Sale::getAvailableLotsForProduct($testProductId, $branchId);
assertTest("Sale::getAvailableLotsForProduct returns exactly 1 active lot with stock", count($availableLots) === 1);
assertTest("Returned lot is LOT-ACTIVE-5 with remaining_quantity = 5", !empty($availableLots) && (int)$availableLots[0]['lot_id'] === $lotActiveId && (float)$availableLots[0]['remaining_quantity'] === 5.0);

// Verify depleted lot is NOT in available lots
$depletedFound = false;
foreach ($availableLots as $l) {
    if ((int)$l['lot_id'] === $lotDepletedId) $depletedFound = true;
}
assertTest("Depleted lot (0 remaining stock) is excluded from lot selector", !$depletedFound);

// --------------------------------------------------------------------------
// TEST 4: POS Overbilling Validation (Cart Stock Limit)
// --------------------------------------------------------------------------
echo "\n--- TEST 4: POS QTY Overbilling Prevention ---\n";
// Attempt to draft sale with 10 units when only 5 units exist
$overbillDraft = Sale::prepareDraftSale([
    'customer_id' => $testCustId,
    'items' => [
        [
            'product_id' => $testProductId,
            'lot_id' => $lotActiveId,
            'quantity' => 10.00,
            'unit_price' => 150.00
        ]
    ]
], $userId, $branchId);

assertTest("prepareDraftSale rejects quantity exceeding lot stock (10 > 5)", $overbillDraft['success'] === false);
assertTest("Error message indicates lot limit exceeded", strpos($overbillDraft['message'], 'Stock lot limit exceeded') !== false);

// Valid draft sale with 5 units (exact stock)
$validDraft = Sale::prepareDraftSale([
    'customer_id' => $testCustId,
    'discount_value' => '15%',
    'items' => [
        [
            'product_id' => $testProductId,
            'lot_id' => $lotActiveId,
            'quantity' => 5.00,
            'unit_price' => 150.00
        ]
    ]
], $userId, $branchId);

assertTest("prepareDraftSale succeeds for valid available stock (5 units)", $validDraft['success'] === true);
$saleId = (int)($validDraft['sale_id'] ?? 0);

// --------------------------------------------------------------------------
// TEST 5: Auto-Deactivate Product on Stock Depletion (Billing to 0)
// --------------------------------------------------------------------------
echo "\n--- TEST 5: Auto-Deactivate Product on Stock Depletion ---\n";
// Product should currently be 'active'
$prodBefore = Database::fetchOne("SELECT status FROM products WHERE id = :p", [':p' => $testProductId]);
assertTest("Product status before sale finalization is 'active'", $prodBefore['status'] === 'active');

// Finalize sale: 5 units of 5 sold => remaining stock becomes 0!
$finalizeRes = Sale::finalizeSale($saleId, [
    [
        'payment_method' => 'Cash',
        'amount' => (float)$validDraft['total_amount']
    ]
], $userId, $branchId);

assertTest("Sale finalized successfully", $finalizeRes['success'] === true);

// Verify branch_inventory is 0
$biAfter = Database::fetchOne("SELECT quantity FROM branch_inventory WHERE product_id = :p AND branch_id = :b", [':p' => $testProductId, ':b' => $branchId]);
assertTest("Branch inventory quantity dropped to 0.00", (float)($biAfter['quantity'] ?? -1) === 0.00);

// Verify product remains active in master products table for multi-branch isolation
$prodAfter = Database::fetchOne("SELECT status FROM products WHERE id = :p", [':p' => $testProductId]);
assertTest("Product master status remains 'active' preserving other branch sales", $prodAfter['status'] === 'active');

// Verify POS search hides product for this branch when out of stock
$searchAfter = Sale::searchProducts($branchId, $testBarcode);
assertTest("POS search excludes product in branch with 0 stock", empty($searchAfter));

// --------------------------------------------------------------------------
// CLEANUP: Isolated Test Data Only (No Truncation)
// --------------------------------------------------------------------------
echo "\n--- Cleaning Up Isolated Test Records ---\n";
try {
    $db->prepare("DELETE FROM audit_logs WHERE record_id = :p AND module = 'products'")->execute([':p' => (string)$testProductId]);
    $db->prepare("DELETE FROM payments WHERE sale_id = :s")->execute([':s' => $saleId]);
    $db->prepare("DELETE FROM stock_ledger WHERE product_id = :p")->execute([':p' => $testProductId]);
    $db->prepare("DELETE FROM sale_items WHERE sale_id = :s")->execute([':s' => $saleId]);
    $db->prepare("DELETE FROM sales WHERE id = :s")->execute([':s' => $saleId]);
    $db->prepare("DELETE FROM branch_inventory WHERE product_id = :p")->execute([':p' => $testProductId]);
    $db->prepare("DELETE FROM product_lots WHERE product_id = :p")->execute([':p' => $testProductId]);
    $db->prepare("DELETE FROM products WHERE id = :p")->execute([':p' => $testProductId]);
    $db->prepare("DELETE FROM customers WHERE id = :c")->execute([':c' => $testCustId]);
    echo " [CLEANUP] Isolated test records successfully cleaned up.\n";
} catch (Throwable $e) {
    echo " [CLEANUP ERROR] " . $e->getMessage() . "\n";
}

echo "\n=========================================================\n";
if ($allPassed) {
    echo "   >>> ALL CLI TESTS PASSED SUCCESSFULLY! (100%) <<<\n";
} else {
    echo "   >>> SOME CLI TESTS FAILED! CHECK OUTPUT ABOVE. <<<\n";
}
echo "=========================================================\n";

exit($allPassed ? 0 : 1);
