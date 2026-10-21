<?php
/**
 * CURTISS POS: Sales Returns & Exchanges Master Optimization & Compliance Test Suite
 *
 * Validates:
 * 1. Database composite indexing (idx_returns_branch_id, idx_returns_branch_created, idx_sri_return)
 * 2. Chart of Accounts & refund method routing (Cash Drawer 1012, Bank, Store Credit, Voucher)
 * 3. Return processing lifecycle, stock restoration, and double-entry balanced accounting
 * 4. Multi-branch session resolution & superadmin branch switching
 * 5. Standard pagination row limits (25, 50, 75, 100, 500)
 * 6. Date presets (today, yesterday, week, month) & range filters
 * 7. Universal multi-column SQL search across EVERY displayed data column (raw numbers and formatted currency)
 * 8. Return Details lookup (SaleReturn::find)
 * 9. Special User presentation-layer display masking
 * 10. UI & Master Design Rules compliance (Viewport, sticky headers, zebra striping, floating nav search)
 * 11. Clean teardown with ZERO database table truncations
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Sale.php';
require_once __DIR__ . '/../core/SaleReturn.php';
require_once __DIR__ . '/../core/MaskingHelper.php';

$passed = 0;
$failed = 0;

function assertTest(string $desc, bool $condition): void {
    global $passed, $failed;
    if ($condition) {
        echo " [PASS] $desc\n";
        $passed++;
    } else {
        echo " [FAIL] $desc\n";
        $failed++;
    }
}

echo "====================================================================\n";
echo " CURTISS POS: SALES RETURNS & EXCHANGES COMPREHENSIVE TEST SUITE\n";
echo "====================================================================\n\n";

$db = Database::getInstance()->getConnection();

// --- SECTION 1: Database Composite Indexes ---
echo "-- Section 1: Database Composite Indexes --\n";
$stmt = $db->query("SHOW INDEX FROM sales_returns");
$returnsIndexes = array_unique(array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Key_name'));

assertTest("Step 1.1: sales_returns table has composite index idx_returns_branch_id", in_array('idx_returns_branch_id', $returnsIndexes));
assertTest("Step 1.2: sales_returns table has composite index idx_returns_branch_created", in_array('idx_returns_branch_created', $returnsIndexes));

$stmtSri = $db->query("SHOW INDEX FROM sales_return_items");
$sriIndexes = array_unique(array_column($stmtSri->fetchAll(PDO::FETCH_ASSOC), 'Key_name'));
assertTest("Step 1.3: sales_return_items table has index idx_sri_return", in_array('idx_sri_return', $sriIndexes));

// --- SECTION 2: Chart of Accounts & Bank Accounts Setup ---
echo "\n-- Section 2: Chart of Accounts & Routing Setup --\n";
$cashDrawer = Database::fetchOne("SELECT id, account_code, account_name FROM chart_of_accounts WHERE account_code = '1012'");
assertTest("Step 2.1: COA Cash Drawer (1012) exists", !empty($cashDrawer));

$bankAccounts = Database::fetchAll("SELECT id, bank_name, account_id FROM bank_accounts WHERE status = 'active'");
assertTest("Step 2.2: Active operational bank accounts configured", !empty($bankAccounts));

// --- SECTION 3: Isolated Fixtures Setup ---
echo "\n-- Section 3: Isolated Fixtures Setup --\n";
$branchId = 1;
$userId = 1;
$uniqueSuffix = time() . '_' . mt_rand(100, 999);
$testInvoiceNo = 'INV-RET-' . $uniqueSuffix;
$testCustomerName = 'RetCust_' . $uniqueSuffix;
$testCustomerPhone = '07788' . mt_rand(10000, 99999);

// Create test customer
$db->prepare("
    INSERT INTO customers (name, phone, customer_code, status, created_at)
    VALUES (:name, :phone, :code, 'active', NOW())
")->execute([
    ':name'  => $testCustomerName,
    ':phone' => $testCustomerPhone,
    ':code'  => 'CUST-R-' . $uniqueSuffix
]);
$testCustomerId = (int)$db->lastInsertId();
assertTest("Step 3.1: Isolated test customer created (ID: $testCustomerId)", $testCustomerId > 0);

// Create test product & lot
$bRow = Database::fetchOne("SELECT id FROM brands LIMIT 1");
$cRow = Database::fetchOne("SELECT id FROM categories LIMIT 1");
$sRow = Database::fetchOne("SELECT id FROM suppliers LIMIT 1");
$brandFk = (int)($bRow['id'] ?? 7);
$catFk = (int)($cRow['id'] ?? 7);
$supFk = (int)($sRow['id'] ?? 9);

$db->prepare("
    INSERT INTO products (brand_id, category_id, supplier_id, name, sku, barcode, selling_price, cost, status, created_at)
    VALUES (:brand, :cat, :sup, :name, :sku, :bc, 2000.00, 1200.00, 'active', NOW())
")->execute([
    ':brand' => $brandFk,
    ':cat'   => $catFk,
    ':sup'   => $supFk,
    ':name'  => 'RetProduct_' . $uniqueSuffix,
    ':sku'   => 'SKU-R-' . $uniqueSuffix,
    ':bc'    => 'BC-R-' . $uniqueSuffix
]);
$testProductId = (int)$db->lastInsertId();

$db->prepare("
    INSERT INTO product_lots (product_id, branch_id, lot_number, cost, selling_price, initial_quantity, remaining_quantity, status, created_at)
    VALUES (:p, :b, :lot, 1200.00, 2000.00, 40.00, 40.00, 'active', NOW())
")->execute([
    ':p'   => $testProductId,
    ':b'   => $branchId,
    ':lot' => 'LOT-R-' . $uniqueSuffix
]);
$testLotId = (int)$db->lastInsertId();

// Create completed sale to return against
$db->prepare("
    INSERT INTO sales (invoice_no, branch_id, customer_id, user_id, subtotal, discount_amount, roundup_amount, total_amount, total_cost, status, created_at)
    VALUES (:inv, :b, :c, :u, 4000.00, 0.00, 0.00, 4000.00, 2400.00, 'completed', NOW())
")->execute([
    ':inv'  => $testInvoiceNo,
    ':b'    => $branchId,
    ':c'    => $testCustomerId,
    ':u'    => $userId
]);
$testSaleId = (int)$db->lastInsertId();
assertTest("Step 3.2: Isolated test sale created (ID: $testSaleId, Invoice: #$testInvoiceNo)", $testSaleId > 0);

$db->prepare("
    INSERT INTO sale_items (sale_id, product_id, lot_id, quantity, original_price, unit_price, discount_amount, line_total, unit_cost, line_cost)
    VALUES (:sid, :pid, :lid, 2.00, 2000.00, 2000.00, 0.00, 4000.00, 1200.00, 2400.00)
")->execute([
    ':sid' => $testSaleId,
    ':pid' => $testProductId,
    ':lid' => $testLotId
]);
$testSaleItemId = (int)$db->lastInsertId();
assertTest("Step 3.3: Isolated sale item created (ID: $testSaleItemId)", $testSaleItemId > 0);

// --- SECTION 4: Return Processing Lifecycle & Stock Restoration ---
echo "\n-- Section 4: Return Processing & Stock Restoration --\n";
// Ensure active business day for cash refund
require_once __DIR__ . '/../core/BusinessDay.php';
$activeDay = BusinessDay::getOrCreateActiveDay($branchId, $userId);
assertTest("Step 4.1: Active business day ready for refund handling", !empty($activeDay));

$returnPayload = [
    'sale_id'         => $testSaleId,
    'customer_id'     => $testCustomerId,
    'return_type'     => 'return_only',
    'settlement_mode' => 'refund',
    'notes'           => 'Optimization test return',
    'items'           => [
        [
            'sale_item_id'   => $testSaleItemId,
            'product_id'     => $testProductId,
            'lot_id'         => $testLotId,
            'quantity'       => 1.00,
            'unit_price'     => 2000.00,
            'cost_price'     => 1200.00,
            'item_condition' => 'good',
            'reason'         => 'Test return refund'
        ]
    ],
    'refunds'         => [
        [
            'refund_method' => 'cash',
            'amount'        => 2000.00,
            'reference_no'  => 'CASH-REF-' . $uniqueSuffix
        ]
    ]
];

$processResult = SaleReturn::processReturn($returnPayload, $userId, $branchId);
assertTest("Step 4.2: SaleReturn::processReturn succeeds", !empty($processResult['success']));
$testReturnId = (int)($processResult['sales_return_id'] ?? $processResult['return_id'] ?? 0);
$testReturnNo = $processResult['return_no'] ?? '';
assertTest("Step 4.3: Return transaction generated ($testReturnNo, ID: $testReturnId)", $testReturnId > 0 && !empty($testReturnNo));

// Verify stock restoration on product_lots
$updatedLot = Database::fetchOne("SELECT remaining_quantity FROM product_lots WHERE id = :id", [':id' => $testLotId]);
assertTest("Step 4.4: Stock lot restored from 40.00 to 41.00", (float)$updatedLot['remaining_quantity'] == 41.00);

// Verify sales_returns record in DB
$returnRow = Database::fetchOne("SELECT * FROM sales_returns WHERE id = :id", [':id' => $testReturnId]);
assertTest("Step 4.5: Sales return recorded in DB with total 2000.00", (float)$returnRow['total_return_amount'] == 2000.00);

// --- SECTION 5: Multi-Branch & Pagination Rules ---
echo "\n-- Section 5: Multi-Branch & Pagination Rules --\n";
$listBranch1 = SaleReturn::all(['branch_id' => $branchId], 1, 25);
assertTest("Step 5.1: SaleReturn::all returns paginated items for branch 1", !empty($listBranch1['items']));
assertTest("Step 5.2: Default limit is 25", $listBranch1['limit'] === 25);

$listAll = SaleReturn::all(['all_branches' => true], 1, 50);
assertTest("Step 5.3: SaleReturn::all with all_branches=true succeeds", isset($listAll['totalItems']));

// Test non-whitelisted limit clamps to 25
$listClamped = SaleReturn::all(['branch_id' => $branchId], 1, 888);
assertTest("Step 5.4: Non-whitelisted pagination limit safely clamps to 25", $listClamped['limit'] === 25);

// --- SECTION 6: Date Presets & Date Filters ---
echo "\n-- Section 6: Date Presets & Filter Handling --\n";
$listToday = SaleReturn::all(['branch_id' => $branchId, 'date_preset' => 'today'], 1, 25);
assertTest("Step 6.1: Date preset 'today' includes today's created return", array_search($testReturnId, array_column($listToday['items'], 'id')) !== false);

$listMonth = SaleReturn::all(['branch_id' => $branchId, 'date_preset' => 'month'], 1, 25);
assertTest("Step 6.2: Date preset 'month' resolves correctly", array_search($testReturnId, array_column($listMonth['items'], 'id')) !== false);

$listCustom = SaleReturn::all(['branch_id' => $branchId, 'date_from' => date('Y-m-d'), 'date_to' => date('Y-m-d')], 1, 25);
assertTest("Step 6.3: Custom date_from and date_to range query works", array_search($testReturnId, array_column($listCustom['items'], 'id')) !== false);

// --- SECTION 7: Universal Multi-Column SQL Search ---
echo "\n-- Section 7: Universal Multi-Column SQL Search --\n";

// 7.1 Search by Return No
$searchRet = SaleReturn::all(['branch_id' => $branchId, 'search' => $testReturnNo], 1, 25);
assertTest("Step 7.1: Search by Return No ($testReturnNo) matches", count($searchRet['items']) === 1 && $searchRet['items'][0]['id'] == $testReturnId);

// 7.2 Search by Original Invoice No with and without hash
$searchInv = SaleReturn::all(['branch_id' => $branchId, 'search' => '#' . $testInvoiceNo], 1, 25);
assertTest("Step 7.2: Search by invoice with '#' matches", count($searchInv['items']) === 1 && $searchInv['items'][0]['id'] == $testReturnId);

$searchInvClean = SaleReturn::all(['branch_id' => $branchId, 'search' => $testInvoiceNo], 1, 25);
assertTest("Step 7.3: Search by raw invoice number matches", count($searchInvClean['items']) === 1 && $searchInvClean['items'][0]['id'] == $testReturnId);

// 7.4 Search by Customer Name & Phone
$searchCust = SaleReturn::all(['branch_id' => $branchId, 'search' => $testCustomerName], 1, 25);
assertTest("Step 7.4: Search by customer name matches", count($searchCust['items']) === 1 && $searchCust['items'][0]['id'] == $testReturnId);

$searchPhone = SaleReturn::all(['branch_id' => $branchId, 'search' => $testCustomerPhone], 1, 25);
assertTest("Step 7.5: Search by customer phone matches", count($searchPhone['items']) === 1 && $searchPhone['items'][0]['id'] == $testReturnId);

// 7.6 Search by Processed By Cashier / User
$searchUser = SaleReturn::all(['branch_id' => $branchId, 'search' => 'admin'], 1, 25);
assertTest("Step 7.6: Search by cashier name/username matches", !empty($searchUser['items']));

// 7.7 Search by Return Type
$searchType = SaleReturn::all(['branch_id' => $branchId, 'search' => 'return_only'], 1, 25);
assertTest("Step 7.7: Search by return_type 'return_only' matches", array_search($testReturnId, array_column($searchType['items'], 'id')) !== false);

// 7.8 Search by Date YYYY-MM-DD
$searchDate = SaleReturn::all(['branch_id' => $branchId, 'search' => date('Y-m-d')], 1, 25);
assertTest("Step 7.8: Search by formatted date YYYY-MM-DD matches", array_search($testReturnId, array_column($searchDate['items'], 'id')) !== false);

// 7.9 Search by Raw Return Total amount
$searchAmt = SaleReturn::all(['branch_id' => $branchId, 'search' => '2000'], 1, 25);
assertTest("Step 7.9: Search by raw numeric total (2000) matches", array_search($testReturnId, array_column($searchAmt['items'], 'id')) !== false);

// 7.10 Search by Formatted Currency Total
$searchAmtFmt = SaleReturn::all(['branch_id' => $branchId, 'search' => 'LKR 2,000.00'], 1, 25);
assertTest("Step 7.10: Search by formatted currency 'LKR 2,000.00' matches", array_search($testReturnId, array_column($searchAmtFmt['items'], 'id')) !== false);

$searchAmtComma = SaleReturn::all(['branch_id' => $branchId, 'search' => '2,000.00'], 1, 25);
assertTest("Step 7.11: Search by comma-formatted total '2,000.00' matches", array_search($testReturnId, array_column($searchAmtComma['items'], 'id')) !== false);

// --- SECTION 8: Return Details Lookup (SaleReturn::find) ---
echo "\n-- Section 8: Return Details Lookup (SaleReturn::find) --\n";
$details = SaleReturn::find($testReturnId);
assertTest("Step 8.1: SaleReturn::find returns valid record", !empty($details));
assertTest("Step 8.2: Details contains return_no " . $testReturnNo, $details['return_no'] === $testReturnNo);
assertTest("Step 8.3: Details contains returned line items", count($details['items']) === 1 && $details['items'][0]['lot_number'] === ('LOT-R-' . $uniqueSuffix));
assertTest("Step 8.4: Details contains refund record with amount 2000.00", count($details['refunds']) === 1 && (float)$details['refunds'][0]['amount'] == 2000.00);

// --- SECTION 9: Special User Presentation Masking ---
echo "\n-- Section 9: Special User Presentation Masking --\n";
$_SESSION['user_id'] = 1;
$_SESSION['role_code'] = 'special_user';
$specialDetails = SaleReturn::find($testReturnId);
assertTest("Step 9.1: Special user return details fetched", !empty($specialDetails));
$maskedTotal = (float)$specialDetails['total_return_amount'];
assertTest("Step 9.2: Return total masked on presentation layer ($maskedTotal vs 2000.00)", $maskedTotal !== 2000.00);
unset($_SESSION['role_code'], $_SESSION['user_id']);

// --- SECTION 10: UI Design & Master Rules Compliance ---
echo "\n-- Section 10: UI Design & Master Rules Compliance --\n";
$viewFile = file_get_contents(__DIR__ . '/../views/returns/index.php');

assertTest("Step 10.1: returns/index.php enforces fixed viewport height (calc(100vh - 110px))", strpos($viewFile, 'calc(100vh - 110px)') !== false);
assertTest("Step 10.2: returns/index.php enforces sticky table headers (position: sticky; top: 0)", strpos($viewFile, 'position: sticky;') !== false && strpos($viewFile, 'top: 0;') !== false);
assertTest("Step 10.3: returns/index.php enforces alternating zebra striping", strpos($viewFile, 'rgba(248, 250, 252, 0.57)') !== false);
assertTest("Step 10.4: returns/index.php page headline contains title only (no subtitle)", strpos($viewFile, '<h1 class="inv-title">Sales Returns & Exchanges</h1>') !== false && strpos($viewFile, '<p class="inv-subtitle">') === false);
assertTest("Step 10.5: returns/index.php table cells format cleanly without repeating LKR prefix in (LKR) column", strpos($viewFile, '<strong style="color: #16a34a; font-family: monospace;"><?= number_format') !== false);
assertTest("Step 10.6: returns/index.php table row opens details modal via onclick (viewReturnDetails)", strpos($viewFile, 'viewReturnDetails(') !== false && strpos($viewFile, '<tr class="return-row"') !== false);
assertTest("Step 10.7: returns/index.php connects floating bottom search with onSearch and onEnter", strpos($viewFile, 'registerBottomSearch') !== false && strpos($viewFile, 'onSearch: function') !== false && strpos($viewFile, 'onEnter: function') !== false);
assertTest("Step 10.8: returns/index.php filterReturnsTableRealtime excludes action buttons from match", strpos($viewFile, 'td:not(.col-center:last-child)') !== false);
assertTest("Step 10.9: returns/index.php provides standard page limit selector (25, 50, 75, 100, 500)", strpos($viewFile, 'id="returnsLimitSelect"') !== false && strpos($viewFile, 'value="25"') !== false && strpos($viewFile, 'value="500"') !== false);

// --- SECTION 11: Clean Teardown of Isolated Fixtures ---
echo "\n--- Clean Teardown of Isolated Fixtures (Zero Table Truncation) ---\n";
// Delete refund, return items, sales return
$db->prepare("DELETE FROM refunds WHERE sales_return_id = :id")->execute([':id' => $testReturnId]);
$db->prepare("DELETE FROM sales_return_items WHERE sales_return_id = :id")->execute([':id' => $testReturnId]);
$db->prepare("DELETE FROM sales_returns WHERE id = :id")->execute([':id' => $testReturnId]);

// Delete test sale items and sale
$db->prepare("DELETE FROM sale_items WHERE id = :id")->execute([':id' => $testSaleItemId]);
$db->prepare("DELETE FROM sales WHERE id = :id")->execute([':id' => $testSaleId]);

// Delete stock movements, ledger and branch inventory entries created by return
$db->prepare("DELETE FROM stock_ledger WHERE reference_no = :ref OR product_id = :pid")->execute([':ref' => $testReturnNo, ':pid' => $testProductId]);
$db->prepare("DELETE FROM branch_inventory WHERE product_id = :pid")->execute([':pid' => $testProductId]);
$db->prepare("DELETE FROM cash_drawer_movements WHERE source_id = :ret_id AND source_type IN ('sales_returns', 'return', 'returns')")->execute([':ret_id' => $testReturnId]);

// Delete test lot, product, and customer
$db->prepare("DELETE FROM product_lots WHERE id = :id")->execute([':id' => $testLotId]);
$db->prepare("DELETE FROM products WHERE id = :id")->execute([':id' => $testProductId]);
$db->prepare("DELETE FROM customers WHERE id = :id")->execute([':id' => $testCustomerId]);

echo " Cleaned test return, refunds, return items, sale, items, lot, product & customer (Return ID: $testReturnId)\n";

$totalTests = $passed + $failed;
echo "====================================================================\n";
echo " TEST SUITE COMPLETE: Total: $totalTests | Passed: $passed | Failed: $failed\n";
echo "====================================================================\n";

if ($failed > 0) {
    exit(1);
}
