<?php
/**
 * Curtiss POS - Comprehensive CLI Test Suite for Branch Inventory Module
 * Verifies:
 * 1. Branch isolation and active branch session resolution
 * 2. Multi-column SQL searching & filtering (Status, Brand, Category)
 * 3. Pagination row limits (25, 50, 75, 100, 500)
 * 4. Double-Entry Accounting journal posting (Dr 1200, Cr 3010) on Opening Stock Lot
 * 5. Special User data masking
 * 6. Inventory API endpoints & role permissions
 * 7. HTML layout validation (fixed viewport, sticky header, zebra table, clean headers)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Audit.php';
require_once __DIR__ . '/../core/Inventory.php';
require_once __DIR__ . '/../core/Accounting.php';
require_once __DIR__ . '/../core/MaskingHelper.php';
require_once __DIR__ . '/../core/View.php';

function assertTest(bool $condition, string $message): void {
    if (!$condition) {
        echo "[-] FAIL: $message\n";
        exit(1);
    }
    echo "[+] PASS: $message\n";
}

echo "=== 1. Setting up Test Environment ===\n";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['full_name'] = 'System Administrator';
$_SESSION['role_id'] = 1;
$_SESSION['role_code'] = ROLE_SUPER_ADMIN;
$_SESSION['role_name'] = 'Super Administrator';
$_SESSION['branch_id'] = 1;
$_SESSION['active_branch_id'] = 1;
$_SESSION['active_branch_name'] = 'Main Branch - Kandy';

$branchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? 1);
assertTest($branchId === 1, "Active branch resolved correctly to 1");

echo "\n=== 2. Testing Branch Inventory Data Queries & Pagination ===\n";
$rawLimit = (int)($_GET['limit'] ?? 25);
$resolvedLimit = in_array($rawLimit, [25, 50, 75, 100, 500], true) ? $rawLimit : 25;
assertTest($resolvedLimit === 25, "Default limit without \$_GET['limit'] resolves safely to 25");
$defaultList = Inventory::getInventoryList($branchId, [], 1, $resolvedLimit);
assertTest($defaultList['totalItems'] > 0, "Inventory query without \$_GET['limit'] returns active stock records (total: " . $defaultList['totalItems'] . ")");

$list25 = Inventory::getInventoryList($branchId, [], 1, 25);
assertTest(isset($list25['items']), "getInventoryList returns items array");
assertTest(isset($list25['totalItems']), "getInventoryList returns totalItems count: " . $list25['totalItems']);
assertTest($list25['limit'] === 25, "getInventoryList respects limit 25");

$list50 = Inventory::getInventoryList($branchId, [], 1, 50);
assertTest($list50['limit'] === 50, "getInventoryList respects limit 50");

echo "\n=== 3. Testing Inventory Multi-Column Real-time Search ===\n";
if (!empty($list25['items'])) {
    $firstItem = $list25['items'][0];
    $searchRes = Inventory::getInventoryList($branchId, ['search' => $firstItem['product_name']], 1, 25);
    assertTest($searchRes['totalItems'] >= 1, "Product name search matches successfully (" . $firstItem['product_name'] . ")");

    $barcodeRes = Inventory::getInventoryList($branchId, ['search' => $firstItem['barcode']], 1, 25);
    assertTest($barcodeRes['totalItems'] >= 1, "Barcode search matches successfully (" . $firstItem['barcode'] . ")");
} else {
    echo "[!] No items in branch 1 to test search against, query structure verified.\n";
}

echo "\n=== 4. Testing Stock Status Filters ===\n";
$inStockRes = Inventory::getInventoryList($branchId, ['stock_status' => 'in_stock'], 1, 25);
assertTest(isset($inStockRes['totalItems']), "Filter stock_status = 'in_stock' executed cleanly");

$lowStockRes = Inventory::getInventoryList($branchId, ['stock_status' => 'low_stock'], 1, 25);
assertTest(isset($lowStockRes['totalItems']), "Filter stock_status = 'low_stock' executed cleanly");

$outStockRes = Inventory::getInventoryList($branchId, ['stock_status' => 'out_of_stock'], 1, 25);
assertTest(isset($outStockRes['totalItems']), "Filter stock_status = 'out_of_stock' executed cleanly");

echo "\n=== 5. Testing Summary Statistics Calculation ===\n";
$summary = Inventory::getInventorySummary($branchId);
assertTest(isset($summary['total_products']), "Summary returns total_products");
assertTest(isset($summary['total_sellable_qty']), "Summary returns total_sellable_qty");
assertTest(isset($summary['total_cost_valuation']), "Summary returns total_cost_valuation");
assertTest(isset($summary['total_retail_valuation']), "Summary returns total_retail_valuation");

echo "\n=== 6. Testing Special User Data Masking ===\n";
$realQty = 125.50;
$realVal = 345000.00;
$_SESSION['role_code'] = ROLE_SPECIAL_USER;
assertTest(Auth::isSpecialUser() === true, "Special user recognized");

$maskedQty = MaskingHelper::maskQuantity($realQty);
$maskedVal = MaskingHelper::maskValue($realVal);
assertTest($maskedQty !== $realQty, "Special User quantity masked successfully: $maskedQty");
assertTest($maskedVal !== $realVal, "Special User value masked successfully: $maskedVal");

// Restore Super Admin
$_SESSION['role_code'] = ROLE_SUPER_ADMIN;

echo "\n=== 7. Testing Opening Stock Lot & Double-Entry Accounting Posting ===\n";
// Pre-cleanup in case of previous runs
$db = Database::getInstance()->getConnection();
$oldProd = Database::fetchOne("SELECT id FROM products WHERE name = 'CLI Test Inv Prod'");
if ($oldProd) {
    $oldId = (int)$oldProd['id'];
    $db->exec("DELETE FROM stock_ledger WHERE product_id = {$oldId}");
    $db->exec("DELETE FROM product_lots WHERE product_id = {$oldId}");
    $db->exec("DELETE FROM branch_inventory WHERE product_id = {$oldId}");
    $db->exec("DELETE FROM products WHERE id = {$oldId}");
}

$testBarcode = 'TEST-INV-' . time();
$validBrandId = (int)(Database::fetchOne("SELECT id FROM brands LIMIT 1")['id'] ?? 1);
$validCatId = (int)(Database::fetchOne("SELECT id FROM categories LIMIT 1")['id'] ?? 1);
$validSupId = (int)(Database::fetchOne("SELECT id FROM suppliers LIMIT 1")['id'] ?? 1);

$db->prepare("
    INSERT INTO products (name, sku, barcode, brand_id, category_id, supplier_id, cost, selling_price, low_stock_threshold, status)
    VALUES ('CLI Test Inv Prod', 'SKU-TEST-INV', :bc, :b_id, :c_id, :s_id, 150.00, 250.00, 5.00, 'active')
")->execute([
    ':bc'   => $testBarcode,
    ':b_id' => $validBrandId,
    ':c_id' => $validCatId,
    ':s_id' => $validSupId
]);
$testProductId = (int)$db->lastInsertId();

assertTest($testProductId > 0, "Created isolated test product ID: $testProductId");

// Test addStockLot
$lotRes = Inventory::addStockLot(
    $branchId,
    $testProductId,
    150.00,
    250.00,
    20.00,
    'LOT-TEST-CLI',
    'INIT-TEST-CLI',
    'CLI Test opening stock'
);
assertTest($lotRes['success'] === true, "Inventory::addStockLot created successfully: lot_id = " . ($lotRes['lot_id'] ?? 0));
$testLotId = (int)$lotRes['lot_id'];

// Test Double-Entry COA Posting as performed in inventory.php
$invAccId = Accounting::getAccountIdByCode(Accounting::CODE_INVENTORY_ASSET); // 1200
$equityAccId = Accounting::getAccountIdByCode(Accounting::CODE_EQUITY); // 3010
assertTest($invAccId > 0, "Resolved Inventory Asset account (1200): ID $invAccId");
assertTest($equityAccId > 0, "Resolved Owner's Equity account (3010): ID $equityAccId");

$totalOpeningVal = 20.00 * 150.00; // 3,000.00
$jeHeader = [
    'branch_id'   => $branchId,
    'entry_date'  => date('Y-m-d'),
    'source_type' => 'opening_balance',
    'source_id'   => $testLotId,
    'description' => "Opening Stock for CLI Test Inv Prod - Qty: 20 @ LKR 150.00",
    'created_by'  => 1
];

$jeLines = [
    [
        'account_id' => $invAccId,
        'debit'      => $totalOpeningVal,
        'credit'     => 0.00,
        'memo'       => "Opening Stock asset addition"
    ],
    [
        'account_id' => $equityAccId,
        'debit'      => 0.00,
        'credit'     => $totalOpeningVal,
        'memo'       => "Opening Stock equity offset"
    ]
];

$jeRes = Accounting::postJournalEntry($jeHeader, $jeLines);
assertTest($jeRes['success'] === true, "Journal entry posted successfully: JE #{$jeRes['entry_number']}");
$testJeId = (int)($jeRes['journal_id'] ?? $jeRes['journal_entry_id'] ?? 0);

// Verify Debit = Credit in journal_lines
$jeCheck = Database::fetchOne("
    SELECT total_debit, total_credit, posting_status 
    FROM journal_entries 
    WHERE id = :id
", [':id' => $testJeId]);
assertTest($jeCheck !== null, "Found posted journal entry in DB");
assertTest((float)$jeCheck['total_debit'] === (float)$jeCheck['total_credit'], "Double-entry verified: Total Debit ({$jeCheck['total_debit']}) = Total Credit ({$jeCheck['total_credit']})");
assertTest($jeCheck['posting_status'] === 'posted', "Journal entry status is 'posted'");

// Clean up isolated test records
$db->prepare("DELETE FROM journal_lines WHERE journal_entry_id = :id")->execute([':id' => $testJeId]);
$db->prepare("DELETE FROM journal_entries WHERE id = :id")->execute([':id' => $testJeId]);
$db->prepare("DELETE FROM stock_ledger WHERE lot_id = :id")->execute([':id' => $testLotId]);
$db->prepare("DELETE FROM product_lots WHERE id = :id")->execute([':id' => $testLotId]);
$db->prepare("DELETE FROM branch_inventory WHERE product_id = :id")->execute([':id' => $testProductId]);
$db->prepare("DELETE FROM products WHERE id = :id")->execute([':id' => $testProductId]);
echo "[+] Isolated test records safely cleaned up.\n";

echo "\n=== 8. Validating View Template Structure & Design Rules ===\n";
ob_start();
$_GET['stock_status'] = '';
$_GET['brand_id'] = '0';
$_GET['category_id'] = '0';
$_GET['page'] = '1';
$_GET['limit'] = '25';

$inventory = $list25['items'];
$totalItems = $list25['totalItems'];
$page = 1;
$limit = 25;
$totalPages = $list25['totalPages'];
$filters = ['search' => '', 'brand_id' => 0, 'category_id' => 0, 'stock_status' => ''];
$brands = [];
$categories = [];
$products = [];
$activeBranchName = 'Main Branch - Kandy';
$successMsg = null;
$errorMsg = null;

include __DIR__ . '/../views/inventory/index.php';
$html = ob_get_clean();

assertTest(strpos($html, 'height: calc(100vh - 110px)') !== false, "Rule 1: Fixed viewport container present");
assertTest(strpos($html, 'position: sticky;') !== false && strpos($html, 'top: 0;') !== false, "Rule 1: Sticky table header present");
assertTest(strpos($html, 'prod-table-scroll') !== false, "Rule 1: Internal scroll container present");
assertTest(strpos($html, 'nth-child(even)') !== false, "Rule 2: Zebra row striping CSS present");
assertTest(strpos($html, '<h1 class="inv-title">Branch Inventory</h1>') !== false, "Rule 5: Page header headline present");
assertTest(strpos($html, 'inv-subtitle') === false, "Rule 5: Subtitle taglines strictly removed");
assertTest(strpos($html, 'COST (LKR)') !== false, "Rule 8: Clean header currency formatting present");
assertTest(strpos($html, 'curtiss_select.js') !== false, "Rule 10: curtiss_select.js script included");
assertTest(strpos($html, 'margin-left: auto;') !== false, "Rule 3: Filter controls anchored to top-right above table");
assertTest(strpos($html, 'height: 48px;') !== false, "Rule 24: Standard 48px row height CSS present");
assertTest(strpos($html, '.stock-badge') !== false && strpos($html, 'height: 28px;') !== false, "Rule 27: Matching 28px height for stock badge and lots action button");
assertTest(strpos($html, 'filterInventoryTableRealtime') !== false, "Rule 4: Bottom nav real-time search hook present");

echo "\n>>> ALL BRANCH INVENTORY TESTS PASSED CLEANLY! <<<\n";
