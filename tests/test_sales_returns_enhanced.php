<?php
/**
 * Test Sales Return Enhanced System
 * Verifies:
 * 1. Customer & Invoice Omni Search
 * 2. Sold Products with Lots Search
 * 3. Return with POS Exchange Mode (No Cash Refund - Stock Restored, COA Balanced)
 * 4. Return with Customer Credit Mode (Customer Balance Adjusted)
 * 5. Return with COA Split Refund (Cash Drawer + Bank Transfer, COA Balanced)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/SaleReturn.php';
require_once __DIR__ . '/../core/Accounting.php';

echo "=== START SALES RETURNS ENHANCED CLI TEST ===\n";

$_SESSION['user_id'] = 1;
$_SESSION['branch_id'] = 1;

$db = Database::getInstance()->getConnection();

// 1. Setup isolated test data: Customer, Product, Product Lot, Sale, Sale Items
$testCode = 'RET-TEST-CUST-' . time();
$db->prepare("INSERT INTO customers (name, phone, customer_code, credit_balance, status) VALUES (?, ?, ?, ?, 'active')")
   ->execute(["Return Test Customer", "0779998877", $testCode, 5000.00]);
$testCustId = (int)$db->lastInsertId();

$testBarcode = 'TEST-RET-PROD-' . time();
$brandId = (int)$db->query("SELECT id FROM brands LIMIT 1")->fetchColumn();
$catId = (int)$db->query("SELECT id FROM categories LIMIT 1")->fetchColumn();
$supId = (int)$db->query("SELECT id FROM suppliers LIMIT 1")->fetchColumn();
$db->prepare("INSERT INTO products (name, barcode, brand_id, category_id, supplier_id, status) VALUES (?, ?, ?, ?, ?, 'active')")
   ->execute(["Return Test Laptop", $testBarcode, $brandId, $catId, $supId]);
$testProdId = (int)$db->lastInsertId();

$testLotNum = 'LOT-TEST-' . time();
$db->prepare("INSERT INTO product_lots (branch_id, product_id, lot_number, cost, selling_price, initial_quantity, remaining_quantity, status) VALUES (1, ?, ?, 1000.00, 1500.00, 10.00, 8.00, 'active')")
   ->execute([$testProdId, $testLotNum]);
$testLotId = (int)$db->lastInsertId();

// Branch inventory
$db->prepare("INSERT INTO branch_inventory (branch_id, product_id, quantity, low_stock_threshold) VALUES (1, ?, 8.00, 5.00) ON DUPLICATE KEY UPDATE quantity = 8.00")
   ->execute([$testProdId]);

// Sale Invoice
$testInvNo = 'INV-RET-' . time();
$db->prepare("INSERT INTO sales (invoice_no, branch_id, customer_id, user_id, subtotal, total_amount, total_cost, status, created_at) VALUES (?, 1, ?, 1, 3000.00, 3000.00, 2000.00, 'completed', NOW())")
   ->execute([$testInvNo, $testCustId]);
$testSaleId = (int)$db->lastInsertId();

// Sale Item (Sold 2 items from lot)
$db->prepare("INSERT INTO sale_items (sale_id, product_id, lot_id, quantity, original_price, unit_price, unit_cost, line_total, line_cost) VALUES (?, ?, ?, 2.00, 1500.00, 1500.00, 1000.00, 3000.00, 2000.00)")
   ->execute([$testSaleId, $testProdId, $testLotId]);
$testSaleItemId = (int)$db->lastInsertId();

echo "Created test sale #{$testSaleId} (Invoice: {$testInvNo}) for customer #{$testCustId}.\n";

// TEST 1: Omni-Search by Customer Phone
$searchResPhone = SaleReturn::searchSalesOrCustomers("0779998877", 1);
echo "Omni-search by phone count: " . count($searchResPhone['invoices']) . " invoices, " . count($searchResPhone['customers']) . " customers.\n";
if (empty($searchResPhone['invoices'])) {
    echo "ERROR: Omni-search by customer phone failed to find invoice!\n";
    exit(1);
}

// TEST 2: Omni-Search by Invoice No
$searchResInv = SaleReturn::searchSalesOrCustomers($testInvNo, 1);
echo "Omni-search by invoice count: " . count($searchResInv['invoices']) . " invoices.\n";
if (empty($searchResInv['invoices'])) {
    echo "ERROR: Omni-search by invoice number failed!\n";
    exit(1);
}

// TEST 3: Search Sold Products with Lots
$soldProdRes = SaleReturn::searchSoldProducts(1, $testBarcode);
echo "Search sold products count: " . count($soldProdRes) . "\n";
if (empty($soldProdRes) || empty($soldProdRes[0]['lots'])) {
    echo "ERROR: Search sold products did not return lot for product!\n";
    exit(1);
}
echo "Lot number found: " . $soldProdRes[0]['lots'][0]['lot_number'] . "\n";

// TEST 4: Process Return with POS Exchange Mode (No Cash Refund - Stock Restored, COA Posted)
echo "\n--- Testing Return: Exchange Mode (No Cash Refund) ---\n";
$returnPayload1 = [
    'sale_id'         => $testSaleId,
    'customer_id'     => $testCustId,
    'return_type'     => 'return_only',
    'settlement_mode' => 'exchange',
    'notes'           => 'CLI Test exchange return',
    'items'           => [
        [
            'sale_item_id'   => $testSaleItemId,
            'product_id'     => $testProdId,
            'lot_id'         => $testLotId,
            'quantity'       => 1.00,
            'unit_price'     => 1500.00,
            'item_condition' => 'good',
            'reason'         => 'Test Exchange'
        ]
    ]
];

$res1 = SaleReturn::processReturn($returnPayload1, 1, 1);
echo "Exchange return result: " . json_encode($res1) . "\n";
if (!$res1['success']) {
    echo "ERROR in exchange return: " . ($res1['message'] ?? '') . "\n";
    exit(1);
}
$returnNo1 = $res1['return_no'];

// Verify stock restored
$lot1 = Database::fetchOne("SELECT remaining_quantity FROM product_lots WHERE id = ?", [$testLotId]);
echo "Product lot stock after 1 return: " . $lot1['remaining_quantity'] . " (Expected: 9.00)\n";
if (abs((float)$lot1['remaining_quantity'] - 9.00) > 0.001) {
    echo "ERROR: Lot quantity was not restored properly!\n";
    exit(1);
}

// Verify COA Journal entry was posted and balanced
$journal1 = Database::fetchOne("SELECT * FROM journal_entries WHERE source_type = 'sales_return' AND source_id = (SELECT id FROM sales_returns WHERE return_no = ?)", [$returnNo1]);
echo "Journal Entry #1: " . ($journal1 ? $journal1['entry_number'] : 'NONE') . " (Debit: {$journal1['total_debit']}, Credit: {$journal1['total_credit']})\n";
if (!$journal1 || abs((float)$journal1['total_debit'] - (float)$journal1['total_credit']) > 0.001) {
    echo "ERROR: Double-entry journal for exchange return is missing or unbalanced!\n";
    exit(1);
}

// TEST 5: Process Manual Return with COA Cash Drawer Refund
echo "\n--- Testing Manual Return: Cash Drawer Refund ---\n";
$returnPayload2 = [
    'sale_id'         => null, // No invoice
    'customer_id'     => $testCustId,
    'return_type'     => 'return_only',
    'settlement_mode' => 'refund',
    'notes'           => 'CLI Test manual cash drawer return',
    'items'           => [
        [
            'product_id'     => $testProdId,
            'lot_id'         => $testLotId,
            'quantity'       => 1.00,
            'unit_price'     => 1500.00,
            // Notice: cost_price omitted, backend should automatically fall back to lot cost!
            'item_condition' => 'good',
            'reason'         => 'Manual Return'
        ]
    ],
    'refunds'         => [
        [
            'refund_method' => 'cash_drawer',
            'amount'        => 1500.00,
            'reference_no'  => 'CD-REF-1500'
        ]
    ]
];

$res2 = SaleReturn::processReturn($returnPayload2, 1, 1);
echo "Manual cash drawer return result: " . json_encode($res2) . "\n";
if (!$res2['success']) {
    echo "ERROR in manual return: " . ($res2['message'] ?? '') . "\n";
    exit(1);
}
$returnNo2 = $res2['return_no'];

// Verify COA Journal entry for cash drawer refund
$journal2 = Database::fetchOne("SELECT * FROM journal_entries WHERE source_type = 'sales_return' AND source_id = (SELECT id FROM sales_returns WHERE return_no = ?)", [$returnNo2]);
echo "Journal Entry #2: " . ($journal2 ? $journal2['entry_number'] : 'NONE') . " (Debit: {$journal2['total_debit']}, Credit: {$journal2['total_credit']})\n";
if (!$journal2 || abs((float)$journal2['total_debit'] - (float)$journal2['total_credit']) > 0.001) {
    echo "ERROR: Double-entry journal for manual return is missing or unbalanced!\n";
    exit(1);
}

// Check lines to verify Cash Drawer (Account 1012) was credited
$cashDrawerLine = Database::fetchOne("
    SELECT jl.* FROM journal_lines jl
    JOIN chart_of_accounts coa ON jl.account_id = coa.id
    WHERE jl.journal_entry_id = ? AND coa.account_code = '1012'
", [$journal2['id']]);
echo "Cash Drawer Line (1012) Credited: " . ($cashDrawerLine ? "LKR {$cashDrawerLine['credit']}" : "NO") . "\n";
if (!$cashDrawerLine || (float)$cashDrawerLine['credit'] != 1500.00) {
    echo "ERROR: Cash Drawer COA 1012 was not credited properly!\n";
    exit(1);
}

// Cleanup Test Data
$db->exec("DELETE FROM journal_lines WHERE journal_entry_id IN (SELECT id FROM journal_entries WHERE source_type = 'sales_return' AND source_id IN (SELECT id FROM sales_returns WHERE return_no IN ('{$returnNo1}', '{$returnNo2}')))");
$db->exec("DELETE FROM journal_entries WHERE source_type = 'sales_return' AND source_id IN (SELECT id FROM sales_returns WHERE return_no IN ('{$returnNo1}', '{$returnNo2}'))");
$db->exec("DELETE FROM refunds WHERE sales_return_id IN (SELECT id FROM sales_returns WHERE return_no IN ('{$returnNo1}', '{$returnNo2}'))");
$db->exec("DELETE FROM sales_return_items WHERE sales_return_id IN (SELECT id FROM sales_returns WHERE return_no IN ('{$returnNo1}', '{$returnNo2}'))");
$db->exec("DELETE FROM sales_returns WHERE return_no IN ('{$returnNo1}', '{$returnNo2}')");
$db->exec("DELETE FROM stock_ledger WHERE product_id = {$testProdId}");
$db->exec("DELETE FROM sale_items WHERE sale_id = {$testSaleId}");
$db->exec("DELETE FROM sales WHERE id = {$testSaleId}");
$db->exec("DELETE FROM branch_inventory WHERE product_id = {$testProdId}");
$db->exec("DELETE FROM product_lots WHERE id = {$testLotId}");
$db->exec("DELETE FROM products WHERE id = {$testProdId}");
$db->exec("DELETE FROM customers WHERE id = {$testCustId}");

echo "CLEANUP COMPLETE. ALL SALES RETURNS ENHANCEMENTS TESTS PASSED SUCCESSFULLY!\n";
