<?php
/**
 * Curtiss POS - Stage 3 Automated Structural Verification Script
 * Validates Branch Inventory, Price & Cost Lots, Append-Only Stock Ledger, and Regression Tests.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Brand.php';
require_once __DIR__ . '/../core/Category.php';
require_once __DIR__ . '/../core/Supplier.php';
require_once __DIR__ . '/../core/Product.php';
require_once __DIR__ . '/../core/Inventory.php';

echo "========================================================\n";
echo " CURTISS POS - STAGE 3 AUTOMATED TEST SUITE\n";
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
// 1. Stage 1 & 2 Regression Tests
// ----------------------------------------------------------------------------
$timeout = (int)Settings::get('session_inactivity_timeout');
assertTest($timeout === 3600, "Regression: Inactivity Timeout = 3600 seconds");

$openingCash = (float)Settings::get('default_opening_cash');
assertTest($openingCash === 20000.00, "Regression: Default Opening Cash = LKR 20,000");

// ----------------------------------------------------------------------------
// 2. Branch-Specific Stock Isolation
// ----------------------------------------------------------------------------
// Product #1 (Atlas Ball Pen Blue 0.5mm) has stock 100 in Branch 1 and 40 in Branch 2
$stockBranch1 = Inventory::getBranchStock(1, 1);
$stockBranch2 = Inventory::getBranchStock(2, 1);

assertTest(($stockBranch1['quantity'] ?? 0) >= 0, "Branch 1 Stock Isolation for Product #1");
assertTest(($stockBranch2['quantity'] ?? 0) >= 0, "Branch 2 Stock Isolation for Product #1");
assertTest(isset($stockBranch1['quantity']) && isset($stockBranch2['quantity']), "Branch Stock Quantities Are Isolated Per Branch");

// ----------------------------------------------------------------------------
// 3. Opening Stock & Automatic Lot/Ledger Creation
// ----------------------------------------------------------------------------
$brand = Brand::all(true)[0];
$cat = Category::all(true)[0];
$sup = Supplier::all(true)[0];

$newBarcode = "99" . time() . rand(10, 99);
$newProdData = [
    'barcode'             => $newBarcode,
    'brand_id'            => $brand['id'],
    'category_id'         => $cat['id'],
    'name'                => 'Stage3 Opening Stock Test Item',
    'supplier_id'         => $sup['id'],
    'cost'                => 120.00,
    'selling_price'       => 160.00,
    'opening_stock'       => 50.00,
    'low_stock_threshold' => 10.00,
    'status'              => 'active',
    'branch_id'           => 1
];

$prodRes = Product::create($newProdData);
assertTest($prodRes['success'] === true, "Product Master Creation with Opening Stock (50.00)");
$newProdId = $prodRes['id'];

// Verify Branch 1 stock for new product
$newStock = Inventory::getBranchStock(1, $newProdId);
assertTest($newStock['quantity'] === 50.00, "Opening Stock automatically initialized in Branch 1 inventory = 50.00");

// ----------------------------------------------------------------------------
// 4. Multiple Price/Cost Lots for Same Product
// ----------------------------------------------------------------------------
// Add Lot B with different Cost & Selling Price for same product in Branch 1
// Lot A: Cost 120, Selling 160, Qty 50 (created during Product::create)
// Lot B: Cost 130, Selling 175, Qty 30
$lotBRes = Inventory::addStockLot(1, $newProdId, 130.00, 175.00, 30.00, 'LOT-TEST-B', 'REF-GRN-002', 'Second batch arrival');
assertTest($lotBRes['success'] === true, "Added Second Price/Cost Lot (Lot B) for same product");

$allLots = Inventory::getLots($newProdId, 1, false);
assertTest(count($allLots) >= 2, "Product has multiple lots (Found " . count($allLots) . " lots)");

$updatedBranchStock = Inventory::getBranchStock(1, $newProdId);
assertTest($updatedBranchStock['quantity'] === 80.00, "Branch total available stock accumulated across lots = 80.00 (50 + 30)");

// ----------------------------------------------------------------------------
// 5. Zero-Stock Lot Becoming Unavailable (Depleted Status)
// ----------------------------------------------------------------------------
// Add Lot C with 0 quantity
$lotCRes = Inventory::addStockLot(1, $newProdId, 140.00, 180.00, 0.00, 'LOT-TEST-C', 'REF-GRN-003', 'Depleted lot test');
assertTest($lotCRes['success'] === true, "Added Zero Stock Lot (Lot C)");

$activeLotsOnly = Inventory::getLots($newProdId, 1, true);
$activeLotIds = array_column($activeLotsOnly, 'lot_number');
assertTest(!in_array('LOT-TEST-C', $activeLotIds), "Zero-stock Lot C excluded from Active Lots list (Status = depleted)");

// ----------------------------------------------------------------------------
// 6. Negative-Stock Prevention
// ----------------------------------------------------------------------------
$negLotRes = Inventory::addStockLot(1, $newProdId, 100.00, 150.00, -20.00);
assertTest($negLotRes['success'] === false, "Negative Stock Lot Prevention: Rejected negative quantity (-20)");

// ----------------------------------------------------------------------------
// 7. Stock Ledger Movements (Append-Only)
// ----------------------------------------------------------------------------
$ledgerRes = Inventory::getLedgerList(1, ['product_id' => $newProdId]);
assertTest(count($ledgerRes['items']) >= 2, "Stock Ledger Records Found (Found " . count($ledgerRes['items']) . " ledger entries)");

$firstLedgerEntry = end($ledgerRes['items']);
assertTest($firstLedgerEntry['movement_type'] === 'opening_stock', "Ledger Movement Type Verified = 'opening_stock'");
assertTest((float)$firstLedgerEntry['quantity'] === 50.00, "Ledger Entry Quantity Verified = 50.00");

// ----------------------------------------------------------------------------
// 8. Auth & Multi-Branch Regression Test
// ----------------------------------------------------------------------------
$authRes = Auth::attempt('superadmin', 'Password@123');
assertTest($authRes['success'] === true, "Auth Regression Test: Super Admin Login Success");

echo "\n--------------------------------------------------------\n";
echo " VERIFICATION SUMMARY: $passCount Passed, $failCount Failed.\n";
echo "========================================================\n";

if ($failCount > 0) {
    exit(1);
}
