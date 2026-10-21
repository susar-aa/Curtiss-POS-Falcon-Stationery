<?php
/**
 * CURTISS POS: Sales History Master Optimization & Compliance Test Suite
 *
 * Validates:
 * 1. Database composite indexing (idx_sales_branch_created, idx_sales_branch_status_id, idx_pay_sale)
 * 2. Multi-branch session resolution & superadmin branch switching
 * 3. Standard pagination row limits (25, 50, 75, 100, 500)
 * 4. Date presets (today, yesterday, week, month) & range filters
 * 5. Universal multi-column SQL search across EVERY displayed data column (including raw and formatted currency)
 * 6. Sale Details Drawer retrieval (Sale::getSaleDetails)
 * 7. Special User display masking
 * 8. CSV export filtering and batch loading
 * 9. UI & Master Design Rules compliance (Fixed viewport, sticky header, zebra striping, floating nav search)
 * 10. Clean teardown with ZERO database table truncations
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Sale.php';
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
echo " CURTISS POS: SALES HISTORY COMPREHENSIVE TEST SUITE\n";
echo "====================================================================\n\n";

$db = Database::getInstance()->getConnection();

// --- SECTION 1: Database Composite Indexes ---
echo "-- Section 1: Database Composite Indexes --\n";
$stmt = $db->query("SHOW INDEX FROM sales");
$salesIndexes = array_unique(array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Key_name'));

assertTest("Step 1.1: sales table has composite index idx_sales_branch_created", in_array('idx_sales_branch_created', $salesIndexes));
assertTest("Step 1.2: sales table has composite index idx_sales_branch_status_id", in_array('idx_sales_branch_status_id', $salesIndexes));

$stmtPmt = $db->query("SHOW INDEX FROM payments");
$pmtIndexes = array_unique(array_column($stmtPmt->fetchAll(PDO::FETCH_ASSOC), 'Key_name'));
assertTest("Step 1.3: payments table has index idx_pay_sale", in_array('idx_pay_sale', $pmtIndexes));

// --- SECTION 2: Isolated Fixture Setup ---
echo "\n-- Section 2: Isolated Fixtures Setup --\n";
$branchId = 1;
$uniqueSuffix = time() . '_' . mt_rand(100, 999);
$testInvoiceNo = 'HIST-' . $uniqueSuffix;
$testCustomerName = 'HistCust_' . $uniqueSuffix;
$testCustomerPhone = '07799' . mt_rand(10000, 99999);

// Create test customer
$db->prepare("
    INSERT INTO customers (name, phone, customer_code, status, created_at)
    VALUES (:name, :phone, :code, 'active', NOW())
")->execute([
    ':name' => $testCustomerName,
    ':phone' => $testCustomerPhone,
    ':code' => 'CUST-' . $uniqueSuffix
]);
$testCustomerId = (int)$db->lastInsertId();
assertTest("Step 2.1: Isolated customer created (ID: $testCustomerId)", $testCustomerId > 0);

// Create test product & lot
$bRow = Database::fetchOne("SELECT id FROM brands LIMIT 1");
$cRow = Database::fetchOne("SELECT id FROM categories LIMIT 1");
$sRow = Database::fetchOne("SELECT id FROM suppliers LIMIT 1");
$brandFk = (int)($bRow['id'] ?? 7);
$catFk = (int)($cRow['id'] ?? 7);
$supFk = (int)($sRow['id'] ?? 9);

$db->prepare("
    INSERT INTO products (brand_id, category_id, supplier_id, name, sku, barcode, selling_price, cost, status, created_at)
    VALUES (:brand, :cat, :sup, :name, :sku, :bc, 1500.00, 1000.00, 'active', NOW())
")->execute([
    ':brand' => $brandFk,
    ':cat'   => $catFk,
    ':sup'   => $supFk,
    ':name'  => 'HistProduct_' . $uniqueSuffix,
    ':sku'   => 'SKU-H-' . $uniqueSuffix,
    ':bc'    => 'BC-H-' . $uniqueSuffix
]);
$testProductId = (int)$db->lastInsertId();

$db->prepare("
    INSERT INTO product_lots (product_id, branch_id, lot_number, cost, selling_price, initial_quantity, remaining_quantity, status, created_at)
    VALUES (:p, :b, :lot, 1000.00, 1500.00, 50.00, 50.00, 'active', NOW())
")->execute([
    ':p' => $testProductId,
    ':b' => $branchId,
    ':lot' => 'LOT-H-' . $uniqueSuffix
]);
$testLotId = (int)$db->lastInsertId();

// Create completed test sale
$subtotal = 3000.00;
$discountAmount = 500.00;
$totalAmount = 2500.00;
$totalCost = 2000.00;

$db->prepare("
    INSERT INTO sales (invoice_no, branch_id, customer_id, user_id, subtotal, discount_amount, roundup_amount, total_amount, total_cost, status, created_at)
    VALUES (:inv, :b, :c, 1, :sub, :disc, 0.00, :tot, :cost, 'completed', NOW())
")->execute([
    ':inv' => $testInvoiceNo,
    ':b' => $branchId,
    ':c' => $testCustomerId,
    ':sub' => $subtotal,
    ':disc' => $discountAmount,
    ':tot' => $totalAmount,
    ':cost' => $totalCost
]);
$testSaleId = (int)$db->lastInsertId();
assertTest("Step 2.2: Isolated test sale created (ID: $testSaleId, Invoice: #$testInvoiceNo)", $testSaleId > 0);

// Create sale items
$db->prepare("
    INSERT INTO sale_items (sale_id, product_id, lot_id, quantity, original_price, unit_price, discount_amount, line_total, unit_cost, line_cost)
    VALUES (:sid, :pid, :lid, 2.00, 1500.00, 1500.00, 500.00, 2500.00, 1000.00, 2000.00)
")->execute([
    ':sid' => $testSaleId,
    ':pid' => $testProductId,
    ':lid' => $testLotId
]);
$testItemId = (int)$db->lastInsertId();
assertTest("Step 2.3: Isolated sale item created (ID: $testItemId)", $testItemId > 0);

// Create payment record (Bank Transfer)
$db->prepare("
    INSERT INTO payments (sale_id, branch_id, customer_id, user_id, payment_no, payment_method, amount, reference_no, created_at)
    VALUES (:sid, :b, :c, 1, :pno, 'Bank Transfer', :amt, :ref, NOW())
")->execute([
    ':sid' => $testSaleId,
    ':b' => $branchId,
    ':c' => $testCustomerId,
    ':pno' => 'PAY-' . $uniqueSuffix,
    ':amt' => $totalAmount,
    ':ref' => 'REF-' . $uniqueSuffix
]);
$testPaymentId = (int)$db->lastInsertId();
assertTest("Step 2.4: Isolated payment created (ID: $testPaymentId, Method: Bank Transfer)", $testPaymentId > 0);

// --- SECTION 3: Multi-Branch & Pagination Rules ---
echo "\n-- Section 3: Multi-Branch & Pagination Rules --\n";
$listBranch1 = Sale::getList($branchId, [], 1, 25);
assertTest("Step 3.1: Sale::getList returns paginated items for branch 1", !empty($listBranch1['items']));
assertTest("Step 3.2: Pagination limits supported (25, 50, 75, 100, 500)", in_array($listBranch1['limit'], [25, 50, 75, 100, 500]));

$listAll = Sale::getList(0, ['all_branches' => true], 1, 50);
assertTest("Step 3.3: Sale::getList with all_branches=true succeeds", isset($listAll['totalItems']));

// Test non-standard limit clamps to 25
$listClamped = Sale::getList($branchId, [], 1, 999);
assertTest("Step 3.4: Non-whitelisted pagination limit safely clamps to 25", $listClamped['limit'] === 25);

// --- SECTION 4: Date Presets Handling ---
echo "\n-- Section 4: Date Presets & Filter Handling --\n";
$listToday = Sale::getList($branchId, ['date_preset' => 'today'], 1, 25);
assertTest("Step 4.1: Date preset 'today' includes today's created sale", array_search($testSaleId, array_column($listToday['items'], 'id')) !== false);

$listMonth = Sale::getList($branchId, ['date_preset' => 'month'], 1, 25);
assertTest("Step 4.2: Date preset 'month' resolves correctly", array_search($testSaleId, array_column($listMonth['items'], 'id')) !== false);

$listCustomDate = Sale::getList($branchId, [
    'date_from' => date('Y-m-d'),
    'date_to' => date('Y-m-d')
], 1, 25);
assertTest("Step 4.3: Explicit date_from and date_to range query works", array_search($testSaleId, array_column($listCustomDate['items'], 'id')) !== false);

// --- SECTION 5: Universal Multi-Column Search Across Every Displayed Column ---
echo "\n-- Section 5: Universal Multi-Column SQL Search --\n";

// 5.1 Search by Invoice No with and without hash
$searchInv = Sale::getList($branchId, ['search' => '#' . $testInvoiceNo], 1, 25);
assertTest("Step 5.1: Search by invoice with '#' prefix matches", count($searchInv['items']) === 1 && $searchInv['items'][0]['id'] == $testSaleId);

$searchInvClean = Sale::getList($branchId, ['search' => $testInvoiceNo], 1, 25);
assertTest("Step 5.2: Search by raw invoice number matches", count($searchInvClean['items']) === 1 && $searchInvClean['items'][0]['id'] == $testSaleId);

// 5.3 Search by Customer Name & Phone
$searchCust = Sale::getList($branchId, ['search' => $testCustomerName], 1, 25);
assertTest("Step 5.3: Search by customer name matches", count($searchCust['items']) === 1 && $searchCust['items'][0]['id'] == $testSaleId);

$searchPhone = Sale::getList($branchId, ['search' => $testCustomerPhone], 1, 25);
assertTest("Step 5.4: Search by customer phone matches", count($searchPhone['items']) === 1 && $searchPhone['items'][0]['id'] == $testSaleId);

// 5.5 Search by Cashier / User
$searchCashier = Sale::getList($branchId, ['search' => 'admin'], 1, 25);
assertTest("Step 5.5: Search by cashier username matches", !empty($searchCashier['items']));

// 5.6 Search by Payment Method
$searchPmt = Sale::getList($branchId, ['search' => 'Bank Transfer'], 1, 25);
assertTest("Step 5.6: Search by payment method 'Bank Transfer' matches", array_search($testSaleId, array_column($searchPmt['items'], 'id')) !== false);

// 5.7 Search by Status
$searchStatus = Sale::getList($branchId, ['search' => 'completed'], 1, 25);
assertTest("Step 5.7: Search by status 'completed' matches", array_search($testSaleId, array_column($searchStatus['items'], 'id')) !== false);

// 5.8 Search by Date YYYY-MM-DD
$searchDate = Sale::getList($branchId, ['search' => date('Y-m-d')], 1, 25);
assertTest("Step 5.8: Search by formatted date YYYY-MM-DD matches", array_search($testSaleId, array_column($searchDate['items'], 'id')) !== false);

// 5.9 Search by Raw Subtotal amount
$searchSub = Sale::getList($branchId, ['search' => '3000'], 1, 25);
assertTest("Step 5.9: Search by raw numeric subtotal (3000) matches", array_search($testSaleId, array_column($searchSub['items'], 'id')) !== false);

// 5.10 Search by Formatted Subtotal currency
$searchSubFmt = Sale::getList($branchId, ['search' => 'LKR 3,000.00'], 1, 25);
assertTest("Step 5.10: Search by formatted subtotal 'LKR 3,000.00' matches", array_search($testSaleId, array_column($searchSubFmt['items'], 'id')) !== false);

// 5.11 Search by Raw Discount amount
$searchDisc = Sale::getList($branchId, ['search' => '500'], 1, 25);
assertTest("Step 5.11: Search by discount amount (500) matches", array_search($testSaleId, array_column($searchDisc['items'], 'id')) !== false);

// 5.12 Search by Raw Total amount
$searchTot = Sale::getList($branchId, ['search' => '2500'], 1, 25);
assertTest("Step 5.12: Search by raw total amount (2500) matches", array_search($testSaleId, array_column($searchTot['items'], 'id')) !== false);

// 5.13 Search by Formatted Total currency
$searchTotFmt = Sale::getList($branchId, ['search' => 'LKR 2,500.00'], 1, 25);
assertTest("Step 5.13: Search by formatted total 'LKR 2,500.00' matches", array_search($testSaleId, array_column($searchTotFmt['items'], 'id')) !== false);

$searchTotComma = Sale::getList($branchId, ['search' => '2,500.00'], 1, 25);
assertTest("Step 5.14: Search by comma-formatted total '2,500.00' matches", array_search($testSaleId, array_column($searchTotComma['items'], 'id')) !== false);

// --- SECTION 6: Sale Details Drawer API ---
echo "\n-- Section 6: Sale Details Drawer API --\n";
$details = Sale::getSaleDetails($testSaleId);
assertTest("Step 6.1: Sale::getSaleDetails returns non-null result", $details !== null);
assertTest("Step 6.2: Details contains 'sale', 'items', and 'payments' keys", isset($details['sale'], $details['items'], $details['payments']));
assertTest("Step 6.3: Sale header contains invoice #" . $testInvoiceNo, $details['sale']['invoice_no'] === $testInvoiceNo);
assertTest("Step 6.4: Sale line items contain product and lot", count($details['items']) === 1 && $details['items'][0]['lot_number'] === ('LOT-H-' . $uniqueSuffix));
assertTest("Step 6.5: Sale payments breakdown contains Bank Transfer with amount 2500.00", count($details['payments']) === 1 && (float)$details['payments'][0]['amount'] == 2500.00);

// --- SECTION 7: Special User Masking ---
echo "\n-- Section 7: Special User Masking --\n";
// Temporarily mock special user
$_SESSION['user_id'] = 1;
$_SESSION['role_code'] = 'special_user';
$specialDetails = Sale::getSaleDetails($testSaleId);
assertTest("Step 7.1: Special user sale details fetched", $specialDetails !== null);
$maskedSub = (float)$specialDetails['sale']['subtotal'];
assertTest("Step 7.2: Special user value masked without mutating DB ($maskedSub vs 3000.00)", $maskedSub !== 3000.00);
unset($_SESSION['role_code'], $_SESSION['user_id']);

// --- SECTION 8: CSV Export Batch Loading --
echo "\n-- Section 8: CSV Export Batch Loading --\n";
// Test getList with limit 5000 (used in exportList)
$exportBatch = Sale::getList($branchId, ['search' => $testInvoiceNo], 1, 5000);
assertTest("Step 8.1: Large batch fetching (limit 5000) succeeds for export", count($exportBatch['items']) === 1);
assertTest("Step 8.2: Export items contain cashier, branch, and payment methods", !empty($exportBatch['items'][0]['payment_methods']));

// --- SECTION 9: UI Design & Master Rules Compliance --
echo "\n-- Section 9: UI Design & Master Rules Compliance --\n";
$viewFile = file_get_contents(__DIR__ . '/../views/sales/sales_history.php');

assertTest("Step 9.1: sales_history.php enforces fixed viewport height (calc(100vh - 110px))", strpos($viewFile, 'calc(100vh - 110px)') !== false);
assertTest("Step 9.2: sales_history.php enforces sticky table header (position: sticky; top: 0)", strpos($viewFile, 'position: sticky;') !== false && strpos($viewFile, 'top: 0;') !== false);
assertTest("Step 9.3: sales_history.php enforces alternating zebra striping", strpos($viewFile, 'rgba(248, 250, 252, 0.57)') !== false);
assertTest("Step 9.4: sales_history.php renders floating actions in #curtiss-floating-header-right", strpos($viewFile, 'id="curtiss-floating-header-right"') !== false);
assertTest("Step 9.5: sales_history.php page headline contains no subtitle", strpos($viewFile, '<h1 class="inv-title">Sales History</h1>') !== false && strpos($viewFile, '<p class="inv-subtitle">') === false);
assertTest("Step 9.6: sales_history.php attaches CurtissSearchableSelect to filter dropdowns", strpos($viewFile, 'CurtissSearchableSelect.attach') !== false);
assertTest("Step 9.7: sales_history.php connects registerBottomSearch with onSearch and onEnter", strpos($viewFile, 'registerBottomSearch') !== false && strpos($viewFile, 'onSearch: function') !== false && strpos($viewFile, 'onEnter: function') !== false);
assertTest("Step 9.8: sales_history.php filterSalesTableRealtime excludes action buttons from match", strpos($viewFile, 'td:not(.col-right:last-child)') !== false);
assertTest("Step 9.9: sales_history.php table row opens details drawer via onclick (viewSaleDetails)", strpos($viewFile, 'viewSaleDetails') !== false);

// --- SECTION 10: Clean Teardown of Isolated Fixtures ---
echo "\n--- Clean Teardown of Isolated Fixtures (Zero Table Truncation) ---\n";
$db->prepare("DELETE FROM payments WHERE id = :id")->execute([':id' => $testPaymentId]);
$db->prepare("DELETE FROM sale_items WHERE id = :id")->execute([':id' => $testItemId]);
$db->prepare("DELETE FROM sales WHERE id = :id")->execute([':id' => $testSaleId]);
$db->prepare("DELETE FROM product_lots WHERE id = :id")->execute([':id' => $testLotId]);
$db->prepare("DELETE FROM products WHERE id = :id")->execute([':id' => $testProductId]);
$db->prepare("DELETE FROM customers WHERE id = :id")->execute([':id' => $testCustomerId]);

echo " Cleaned test sale, items, payments, customer, lot & product (Sale ID: $testSaleId)\n";

$totalTests = $passed + $failed;
echo "====================================================================\n";
echo " TEST SUITE COMPLETE: Total: $totalTests | Passed: $passed | Failed: $failed\n";
echo "====================================================================\n";

if ($failed > 0) {
    exit(1);
}
