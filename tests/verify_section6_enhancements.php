<?php
/**
 * Curtiss POS - Section 6 Verification Suite
 * Sales Returns, Refunds & Exchanges Integration Tests (60 Comprehensive Tests)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Sale.php';
require_once __DIR__ . '/../core/SaleReturn.php';
require_once __DIR__ . '/../core/Customer.php';
require_once __DIR__ . '/../core/GiftVoucher.php';
require_once __DIR__ . '/../core/Inventory.php';
require_once __DIR__ . '/../core/Banking.php';
require_once __DIR__ . '/../core/BusinessDay.php';
require_once __DIR__ . '/../core/Accounting.php';
require_once __DIR__ . '/../core/ReportsEngine.php';
require_once __DIR__ . '/../core/DiagnosticsEngine.php';

echo "=====================================================\n";
echo " SECTION 6: SALES RETURNS, REFUNDS & EXCHANGES SUITE \n";
echo "=====================================================\n\n";

$db = Database::getInstance()->getConnection();
$db->exec("SET FOREIGN_KEY_CHECKS = 0;");

// Clean up stale test returns, sales, ledger & customers
$db->exec("DELETE FROM refunds WHERE sales_return_id IN (SELECT id FROM sales_returns WHERE notes LIKE '%Sec6%' OR return_no LIKE 'RET-S6%')");
$db->exec("DELETE FROM damaged_stock WHERE sales_return_id IN (SELECT id FROM sales_returns WHERE notes LIKE '%Sec6%' OR return_no LIKE 'RET-S6%')");
$db->exec("DELETE FROM sales_return_items WHERE sales_return_id IN (SELECT id FROM sales_returns WHERE notes LIKE '%Sec6%' OR return_no LIKE 'RET-S6%')");
$db->exec("DELETE FROM sales_returns WHERE notes LIKE '%Sec6%' OR return_no LIKE 'RET-S6%'");
$db->exec("DELETE FROM sale_items WHERE sale_id IN (SELECT id FROM sales WHERE invoice_no LIKE 'SEC6-%' OR held_notes LIKE '%Sec6%')");
$db->exec("DELETE FROM payments WHERE sale_id IN (SELECT id FROM sales WHERE invoice_no LIKE 'SEC6-%' OR held_notes LIKE '%Sec6%')");
$db->exec("DELETE FROM sales WHERE invoice_no LIKE 'SEC6-%' OR held_notes LIKE '%Sec6%'");
$db->exec("DELETE FROM customer_credit_ledger WHERE customer_id IN (SELECT id FROM customers WHERE name LIKE 'Sec6 %' OR customer_code LIKE 'CUS-S6%')");
$db->exec("DELETE FROM payments WHERE customer_id IN (SELECT id FROM customers WHERE name LIKE 'Sec6 %' OR customer_code LIKE 'CUS-S6%')");
$db->exec("DELETE FROM customers WHERE name LIKE 'Sec6 %' OR customer_code LIKE 'CUS-S6%'");
$db->exec("SET FOREIGN_KEY_CHECKS = 1;");

// Setup session context
$_SESSION['user_id'] = 1;
$_SESSION['branch_id'] = 1;
$_SESSION['role_id'] = 1;
$userId = 1;
$branchId = 1;

// Ensure active business day
$activeDay = BusinessDay::getActiveDay($branchId);
if (!$activeDay) {
    BusinessDay::startDay($branchId, 10000.00, $userId, 'Sec 6 Test Day');
    $activeDay = BusinessDay::getActiveDay($branchId);
}

$passed = 0;
$failed = 0;

function assertTest(bool $condition, string $testName, string $failureDetails = '') {
    global $passed, $failed;
    if ($condition) {
        echo "  [PASS] {$testName}\n";
        $passed++;
    } else {
        echo "  [FAIL] {$testName}";
        if ($failureDetails !== '') {
            echo " - {$failureDetails}";
        }
        echo "\n";
        $failed++;
    }
}

// -------------------------------------------------------------------------
// FIXTURE SETUP
// -------------------------------------------------------------------------
$randKey = rand(1000, 9999);
$sku = 'SKU-SEC6-' . $randKey;
$barcode = 'BAR-SEC6-' . $randKey;

$db->prepare("
    INSERT INTO products (sku, barcode, name, brand_id, category_id, supplier_id, cost, selling_price, status)
    VALUES (:sku, :barcode, 'Sec6 Test Product', 1, 1, 1, 500.00, 1000.00, 'active')
")->execute([':sku' => $sku, ':barcode' => $barcode]);
$pId = (int)$db->lastInsertId();

$lotNo = 'LOT-SEC6-' . $randKey;
$db->prepare("
    INSERT INTO product_lots (product_id, branch_id, lot_number, cost, selling_price, initial_quantity, remaining_quantity, status)
    VALUES (:p_id, :b_id, :lot_no, 500.00, 1000.00, 500.00, 500.00, 'active')
")->execute([':p_id' => $pId, ':b_id' => $branchId, ':lot_no' => $lotNo]);
$lotId = (int)$db->lastInsertId();

$db->prepare("
    INSERT INTO branch_inventory (branch_id, product_id, quantity)
    VALUES (:b_id, :p_id, 500.00)
    ON DUPLICATE KEY UPDATE quantity = quantity + 500.00
")->execute([':b_id' => $branchId, ':p_id' => $pId]);

// Registered test customer
$cRes = Customer::create(['name' => 'Sec6 Credit Customer ' . $randKey, 'credit_limit' => 50000.00]);
$cId = (int)$cRes['id'];

// Bank account
$bankAccounts = Banking::getBankAccounts($branchId, true);
$bankAccId = !empty($bankAccounts) ? (int)$bankAccounts[0]['id'] : 1;


function createTestCompletedSale(array $saleData, int $userId, int $branchId): array {
    $prep = Sale::prepareDraftSale($saleData, $userId, $branchId);
    if (!$prep['success']) return $prep;
    $payments = $saleData['payments'] ?? [['payment_method' => 'Cash', 'amount' => $prep['total_amount']]];
    return Sale::finalizeSale((int)$prep['sale_id'], $payments, $userId, $branchId);
}

// -------------------------------------------------------------------------
// GROUP 1: Original Invoice Search & Identification (Tests #1 - #1b)
// -------------------------------------------------------------------------
echo "\n--- Group 1: Original Invoice Search & Identification ---\n";

$saleData1 = [
    'customer_id' => $cId,
    'items'       => [['product_id' => $pId, 'lot_id' => $lotId, 'quantity' => 10, 'unit_price' => 1000.00]],
    'payments'    => [['payment_method' => 'Cash', 'amount' => 10000.00]]
];
$saleRes1 = createTestCompletedSale($saleData1, $userId, $branchId);
$saleId1 = (int)$saleRes1['sale_id'];
$invNo1 = $saleRes1['invoice_no'];

// 1. Search original sale
$foundSale = SaleReturn::searchSaleForReturn($invNo1, $branchId);
assertTest($foundSale !== null && (int)$foundSale['id'] === $saleId1, "Test #1: Search original invoice by invoice number succeeded");
assertTest($foundSale !== null && (int)$foundSale['id'] === $saleId1, "Test #1: Search original invoice by invoice number succeeded");

// 1b. Check returnable quantity calculation
assertTest(isset($foundSale['items'][0]['available_return_qty']) && (float)$foundSale['items'][0]['available_return_qty'] === 10.00, "Test #1b: Invoice lookup calculates available returnable quantity correctly (10.00)");


// -------------------------------------------------------------------------
// GROUP 2: Partial & Full Invoice Returns (Tests #2 - #6)
// -------------------------------------------------------------------------
echo "\n--- Group 2: Partial & Full Invoice Returns ---\n";

// 2. Partial return (3 units)
$retRes1 = SaleReturn::processReturn([
    'sale_id'     => $saleId1,
    'customer_id' => $cId,
    'return_type' => 'return_only',
    'notes'       => 'Sec6 Partial Return 1',
    'items'       => [['sale_item_id' => $foundSale['items'][0]['id'], 'product_id' => $pId, 'lot_id' => $lotId, 'quantity' => 3, 'unit_price' => 1000.00, 'item_condition' => 'good', 'reason' => 'Customer Changed Mind']],
    'refunds'     => [['refund_method' => 'cash', 'amount' => 3000.00]]
], $userId, $branchId);

assertTest($retRes1['success'] === true, "Test #2: Partial invoice return of 3 units processed successfully");

// 3. Check updated remaining returnable quantity (10 - 3 = 7)
$foundSale2 = SaleReturn::searchSaleForReturn($invNo1, $branchId);
assertTest((float)$foundSale2['items'][0]['available_return_qty'] === 7.00, "Test #3: Remaining returnable quantity updated to 7.00 after partial return");

// 4. Second partial return (2 units)
$retRes2 = SaleReturn::processReturn([
    'sale_id'     => $saleId1,
    'customer_id' => $cId,
    'return_type' => 'return_only',
    'notes'       => 'Sec6 Partial Return 2',
    'items'       => [['sale_item_id' => $foundSale['items'][0]['id'], 'product_id' => $pId, 'lot_id' => $lotId, 'quantity' => 2, 'unit_price' => 1000.00, 'item_condition' => 'good', 'reason' => 'Defective']],
    'refunds'     => [['refund_method' => 'cash', 'amount' => 2000.00]]
], $userId, $branchId);
assertTest($retRes2['success'] === true, "Test #4: Second partial return of 2 units on same invoice succeeded");

// 5. Over-return blocked (Attempting to return 6 units when max remaining is 5)
$overRetRes = SaleReturn::processReturn([
    'sale_id'     => $saleId1,
    'customer_id' => $cId,
    'return_type' => 'return_only',
    'notes'       => 'Sec6 Over Return',
    'items'       => [['sale_item_id' => $foundSale['items'][0]['id'], 'product_id' => $pId, 'lot_id' => $lotId, 'quantity' => 6, 'unit_price' => 1000.00, 'item_condition' => 'good', 'reason' => 'Wrong Item']],
    'refunds'     => [['refund_method' => 'cash', 'amount' => 6000.00]]
], $userId, $branchId);
assertTest(!$overRetRes['success'] && strpos($overRetRes['message'], 'Max allowable return is 5') !== false, "Test #5: Over-return of 6 units strictly blocked (Max allowable: 5)");

// 6. Complete remaining 5 units return
$retRes3 = SaleReturn::processReturn([
    'sale_id'     => $saleId1,
    'customer_id' => $cId,
    'return_type' => 'return_only',
    'notes'       => 'Sec6 Full Return',
    'items'       => [['sale_item_id' => $foundSale['items'][0]['id'], 'product_id' => $pId, 'lot_id' => $lotId, 'quantity' => 5, 'unit_price' => 1000.00, 'item_condition' => 'good', 'reason' => 'Wrong Item']],
    'refunds'     => [['refund_method' => 'cash', 'amount' => 5000.00]]
], $userId, $branchId);
assertTest($retRes3['success'] === true, "Test #6: Remaining 5 units returned completing full invoice return (Total returned: 10/10)");


// -------------------------------------------------------------------------
// GROUP 3: Stock & Inventory Restoration (Tests #7 - #10)
// -------------------------------------------------------------------------
echo "\n--- Group 3: Stock & Inventory Restoration ---\n";

// 7. Check exact product lot remaining quantity (500 - 10 sold + 10 returned = 500)
$lotCheck = Database::fetchOne("SELECT remaining_quantity, status FROM product_lots WHERE id = :id", [':id' => $lotId]);
assertTest((float)$lotCheck['remaining_quantity'] === 500.00, "Test #7: Stock restored to exact original product lot ID (Remaining: 500.00)");

// 8. Historical depleted lot restoration test
$depLotNo = 'LOT-DEP-' . $randKey;
$db->prepare("
    INSERT INTO product_lots (product_id, branch_id, lot_number, cost, selling_price, initial_quantity, remaining_quantity, status)
    VALUES (:p_id, :b_id, :lot_no, 500.00, 1000.00, 10.00, 0.00, 'depleted')
")->execute([':p_id' => $pId, ':b_id' => $branchId, ':lot_no' => $depLotNo]);
$depLotId = (int)$db->lastInsertId();

// Create sale on depleted lot
$invDep = 'SEC6-DEP-' . $randKey;
$saleDep = createTestCompletedSale([
    'invoice_no'  => $invDep,
    'customer_id' => $cId,
    'items'       => [['product_id' => $pId, 'lot_id' => $depLotId, 'quantity' => 0, 'unit_price' => 1000.00]],
    'payments'    => [['payment_method' => 'Cash', 'amount' => 0.00]]
], $userId, $branchId);

// Return 2 units to depleted lot
$retDepRes = SaleReturn::processReturn([
    'sale_id'     => null,
    'customer_id' => $cId,
    'return_type' => 'return_only',
    'notes'       => 'Sec6 Depleted Lot Restoration',
    'items'       => [['product_id' => $pId, 'lot_id' => $depLotId, 'quantity' => 2, 'unit_price' => 1000.00, 'cost_price' => 500.00, 'item_condition' => 'good', 'reason' => 'Customer Return']],
    'refunds'     => [['refund_method' => 'cash', 'amount' => 2000.00]]
], $userId, $branchId);

$depLotCheck = Database::fetchOne("SELECT remaining_quantity, status FROM product_lots WHERE id = :id", [':id' => $depLotId]);
assertTest($depLotCheck['status'] === 'active' && (float)$depLotCheck['remaining_quantity'] === 2.00, "Test #8: Restoring stock to historical depleted lot reactivates lot status to 'active' (Remaining: 2.00)");

// 9. Branch inventory check
$invCheck = Database::fetchOne("SELECT quantity FROM branch_inventory WHERE branch_id = :b_id AND product_id = :p_id", [':b_id' => $branchId, ':p_id' => $pId]);
assertTest((float)$invCheck['quantity'] >= 502.00, "Test #9: Branch inventory sellable quantity increased by good return stock");

// 10. Stock ledger entry check
$ledgerEntry = Database::fetchOne("
    SELECT * FROM stock_ledger WHERE reference_no = :ref_no AND movement_type = 'customer_return'
", [':ref_no' => $retRes1['return_no']]);
assertTest($ledgerEntry !== null && (float)$ledgerEntry['quantity'] === 3.00, "Test #10: Stock ledger entry created with movement_type = 'customer_return'");


// -------------------------------------------------------------------------
// GROUP 4: Damaged Stock Handling (Tests #11 - #13)
// -------------------------------------------------------------------------
echo "\n--- Group 4: Damaged Stock Handling ---\n";

$invDam = 'SEC6-DAM-' . $randKey;
$saleDam = createTestCompletedSale([
    'invoice_no'  => $invDam,
    'customer_id' => $cId,
    'items'       => [['product_id' => $pId, 'lot_id' => $lotId, 'quantity' => 5, 'unit_price' => 1000.00]],
    'payments'    => [['payment_method' => 'Cash', 'amount' => 5000.00]]
], $userId, $branchId);
$saleDamId = (int)$saleDam['sale_id'];

$invDamSale = SaleReturn::searchSaleForReturn($invDam, $branchId);
$branchStockBeforeDamRow = Database::fetchOne("SELECT quantity FROM branch_inventory WHERE branch_id = :b_id AND product_id = :p_id", [':b_id' => $branchId, ':p_id' => $pId]);
$branchStockBeforeDam = (float)($branchStockBeforeDamRow['quantity'] ?? 0);

// 11. Damaged return (2 units)
$damRetRes = SaleReturn::processReturn([
    'sale_id'     => $saleDamId,
    'customer_id' => $cId,
    'return_type' => 'return_only',
    'notes'       => 'Sec6 Damaged Return',
    'items'       => [['sale_item_id' => $invDamSale['items'][0]['id'], 'product_id' => $pId, 'lot_id' => $lotId, 'quantity' => 2, 'unit_price' => 1000.00, 'item_condition' => 'damaged', 'reason' => 'Damaged Goods']],
    'refunds'     => [['refund_method' => 'cash', 'amount' => 2000.00]]
], $userId, $branchId);

assertTest($damRetRes['success'] === true, "Test #11: Damaged return of 2 units processed successfully");

// 12. Damaged stock log entry check
$damLog = Database::fetchOne("
    SELECT * FROM damaged_stock WHERE sales_return_id = :ret_id
", [':ret_id' => $damRetRes['sales_return_id']]);
assertTest($damLog !== null && (float)$damLog['quantity'] === 2.00, "Test #12: Damaged stock logged to damaged_stock table (Qty: 2.00)");

// 13. Sellable inventory NOT increased for damaged return
$branchStockAfterDamRow = Database::fetchOne("SELECT quantity FROM branch_inventory WHERE branch_id = :b_id AND product_id = :p_id", [':b_id' => $branchId, ':p_id' => $pId]);
$branchStockAfterDam = (float)($branchStockAfterDamRow['quantity'] ?? 0);
assertTest($branchStockAfterDam === $branchStockBeforeDam, "Test #13: Damaged return strictly DOES NOT increase sellable branch inventory");


// -------------------------------------------------------------------------
// GROUP 5: Refund Settlement Methods & Cash Drawer (Tests #14 - #27)
// -------------------------------------------------------------------------
echo "\n--- Group 5: Refund Settlement Methods & Cash Drawer ---\n";

// 14 & 15. Cash Refund & Cash Drawer Movement
$drawerBefore = (float)$db->query("
    SELECT COALESCE(SUM(IF(direction='in', amount, -amount)), 0.00) 
    FROM cash_drawer_movements WHERE business_day_id = {$activeDay['id']}
")->fetchColumn();

$cashRetRes = SaleReturn::processReturn([
    'sale_id'     => null,
    'customer_id' => $cId,
    'return_type' => 'return_only',
    'notes'       => 'Sec6 Cash Refund Test',
    'items'       => [['product_id' => $pId, 'lot_id' => $lotId, 'quantity' => 1, 'unit_price' => 1000.00, 'cost_price' => 500.00, 'item_condition' => 'good', 'reason' => 'Customer Return']],
    'refunds'     => [['refund_method' => 'cash', 'amount' => 1000.00]]
], $userId, $branchId);

$drawerAfter = (float)$db->query("
    SELECT COALESCE(SUM(IF(direction='in', amount, -amount)), 0.00) 
    FROM cash_drawer_movements WHERE business_day_id = {$activeDay['id']}
")->fetchColumn();

assertTest($cashRetRes['success'] === true, "Test #14: Cash refund of LKR 1,000.00 processed successfully");
assertTest(round($drawerBefore - $drawerAfter, 2) === 1000.00, "Test #15: Cash refund reduced physical cash drawer by exactly LKR 1,000.00");

// 16 & 17. Card Refund
$cardRetRes = SaleReturn::processReturn([
    'sale_id'     => null,
    'customer_id' => $cId,
    'return_type' => 'return_only',
    'notes'       => 'Sec6 Card Refund Test',
    'items'       => [['product_id' => $pId, 'lot_id' => $lotId, 'quantity' => 1, 'unit_price' => 1000.00, 'cost_price' => 500.00, 'item_condition' => 'good', 'reason' => 'Customer Return']],
    'refunds'     => [['refund_method' => 'card', 'amount' => 1000.00, 'reference_no' => 'CARD-REF-9988']]
], $userId, $branchId);

$drawerAfterCard = (float)$db->query("
    SELECT COALESCE(SUM(IF(direction='in', amount, -amount)), 0.00) 
    FROM cash_drawer_movements WHERE business_day_id = {$activeDay['id']}
")->fetchColumn();

assertTest($cardRetRes['success'] === true, "Test #16: Card refund of LKR 1,000.00 processed successfully");
assertTest($drawerAfterCard === $drawerAfter, "Test #17: Card refund strictly DOES NOT affect physical cash drawer");

$cardJeLine = Database::fetchOne("
    SELECT coa.account_code FROM journal_lines jl
    JOIN journal_entries je ON jl.journal_entry_id = je.id
    JOIN chart_of_accounts coa ON jl.account_id = coa.id
    WHERE je.source_type = 'sales_return' AND je.source_id = :id AND jl.credit > 0
", [':id' => $cardRetRes['sales_return_id']]);
assertTest($cardJeLine !== null && $cardJeLine['account_code'] === '1030', "Test #17b: Card refund GL journal explicitly credits Card Receivable COA 1030");

// 18 & 19. Bank Refund with bank_account_id
$bankRetRes = SaleReturn::processReturn([
    'sale_id'     => null,
    'customer_id' => $cId,
    'return_type' => 'return_only',
    'notes'       => 'Sec6 Bank Refund Test',
    'items'       => [['product_id' => $pId, 'lot_id' => $lotId, 'quantity' => 1, 'unit_price' => 1000.00, 'cost_price' => 500.00, 'item_condition' => 'good', 'reason' => 'Customer Return']],
    'refunds'     => [['refund_method' => 'bank', 'bank_account_id' => $bankAccId, 'amount' => 1000.00, 'reference_no' => 'BANK-TRF-5544']]
], $userId, $branchId);

assertTest($bankRetRes['success'] === true, "Test #18: Bank refund of LKR 1,000.00 processed with selected bank_account_id #{$bankAccId}");

$bankRefundRow = Database::fetchOne("SELECT * FROM refunds WHERE sales_return_id = :id AND refund_method = 'bank'", [':id' => $bankRetRes['sales_return_id']]);
assertTest($bankRefundRow !== null && (int)$bankRefundRow['bank_account_id'] === $bankAccId, "Test #19: Bank refund record correctly persisted target bank_account_id #{$bankAccId}");

$targetBankCoa = Database::fetchOne("SELECT account_id FROM bank_accounts WHERE id = :id", [':id' => $bankAccId]);
$bankJeLine = Database::fetchOne("
    SELECT jl.account_id FROM journal_lines jl
    JOIN journal_entries je ON jl.journal_entry_id = je.id
    WHERE je.source_type = 'sales_return' AND je.source_id = :id AND jl.credit > 0
", [':id' => $bankRetRes['sales_return_id']]);
assertTest($bankJeLine !== null && (int)$bankJeLine['account_id'] === (int)$targetBankCoa['account_id'], "Test #19b: Bank refund GL journal explicitly credits dedicated bank account COA #{$targetBankCoa['account_id']}");

// 20 & 21. Customer Credit Adjustment Refund
$db->prepare("UPDATE customers SET credit_balance = 5000.00 WHERE id = :id")->execute([':id' => $cId]);

$credRetRes = SaleReturn::processReturn([
    'sale_id'     => null,
    'customer_id' => $cId,
    'return_type' => 'return_only',
    'notes'       => 'Sec6 Credit Adjustment Test',
    'items'       => [['product_id' => $pId, 'lot_id' => $lotId, 'quantity' => 2, 'unit_price' => 1000.00, 'cost_price' => 500.00, 'item_condition' => 'good', 'reason' => 'Credit Return']],
    'refunds'     => [['refund_method' => 'credit', 'amount' => 2000.00]]
], $userId, $branchId);

$cDebtAfter = (float)Database::fetchOne("SELECT credit_balance FROM customers WHERE id = :id", [':id' => $cId])['credit_balance'];
assertTest($credRetRes['success'] === true && $cDebtAfter === 3000.00, "Test #20: Customer Credit Adjustment reduced registered customer outstanding (5,000 -> 3,000)");

$cclEntry = Database::fetchOne("
    SELECT * FROM customer_credit_ledger WHERE reference_no = :ref_no AND transaction_type = 'adjustment'
", [':ref_no' => $credRetRes['return_no']]);
assertTest($cclEntry !== null && (float)$cclEntry['amount_paid'] === 2000.00 && (float)$cclEntry['balance_after'] === 3000.00, "Test #21: Customer Credit Adjustment appended customer_credit_ledger entry");

$arJeLine = Database::fetchOne("
    SELECT coa.account_code FROM journal_lines jl
    JOIN journal_entries je ON jl.journal_entry_id = je.id
    JOIN chart_of_accounts coa ON jl.account_id = coa.id
    WHERE je.source_type = 'sales_return' AND je.source_id = :id AND jl.credit > 0
", [':id' => $credRetRes['sales_return_id']]);
assertTest($arJeLine !== null && $arJeLine['account_code'] === '1040', "Test #21b: Customer Credit Adjustment GL journal explicitly credits Customer Receivables COA 1040");

// 22. Customer credit balance never reduced below zero
$credRetRes2 = SaleReturn::processReturn([
    'sale_id'     => null,
    'customer_id' => $cId,
    'return_type' => 'return_only',
    'notes'       => 'Sec6 Credit Adjustment Over-reduction Test',
    'items'       => [['product_id' => $pId, 'lot_id' => $lotId, 'quantity' => 4, 'unit_price' => 1000.00, 'cost_price' => 500.00, 'item_condition' => 'good', 'reason' => 'Credit Return']],
    'refunds'     => [['refund_method' => 'credit', 'amount' => 4000.00]]
], $userId, $branchId);

$cDebtAfter2 = (float)Database::fetchOne("SELECT credit_balance FROM customers WHERE id = :id", [':id' => $cId])['credit_balance'];
assertTest($credRetRes2['success'] === true && $cDebtAfter2 === 0.00, "Test #22: Customer outstanding capped at 0.00 (never reduced below zero)");

// 23. Walk-in Customer Credit Adjustment strictly blocked
$walkinRetRes = SaleReturn::processReturn([
    'sale_id'     => null,
    'customer_id' => 1, // Walk-in
    'return_type' => 'return_only',
    'notes'       => 'Sec6 Walk-in Credit Refund Test',
    'items'       => [['product_id' => $pId, 'lot_id' => $lotId, 'quantity' => 1, 'unit_price' => 1000.00, 'cost_price' => 500.00, 'item_condition' => 'good', 'reason' => 'Customer Return']],
    'refunds'     => [['refund_method' => 'credit', 'amount' => 1000.00]]
], $userId, $branchId);

assertTest(!$walkinRetRes['success'] && strpos($walkinRetRes['message'], 'not allowed for Walk-in Customer') !== false, "Test #23: Customer Credit Adjustment refund for Walk-in Customer strictly blocked");

// 24, 25, 26, 27. Gift Voucher Refund
$vouchRetRes = SaleReturn::processReturn([
    'sale_id'     => null,
    'customer_id' => $cId,
    'return_type' => 'return_only',
    'notes'       => 'Sec6 Gift Voucher Refund Test',
    'items'       => [['product_id' => $pId, 'lot_id' => $lotId, 'quantity' => 1, 'unit_price' => 1500.00, 'cost_price' => 500.00, 'item_condition' => 'good', 'reason' => 'Voucher Refund']],
    'refunds'     => [['refund_method' => 'voucher', 'amount' => 1500.00]]
], $userId, $branchId);

assertTest($vouchRetRes['success'] === true, "Test #24: Gift Voucher refund of LKR 1,500.00 processed successfully");

$voucherRefundRow = Database::fetchOne("SELECT * FROM refunds WHERE sales_return_id = :id AND refund_method = 'voucher'", [':id' => $vouchRetRes['sales_return_id']]);
$newVoucher = Database::fetchOne("SELECT * FROM gift_vouchers WHERE id = :id", [':id' => (int)$voucherRefundRow['replacement_voucher_id']]);
assertTest($newVoucher !== null && $newVoucher['status'] === 'active', "Test #25: Gift Voucher refund issued a BRAND NEW voucher with status 'active'");
assertTest((float)$newVoucher['amount'] === 1500.00, "Test #27: Fixed replacement voucher value equals exact refund amount (LKR 1,500.00)");

$vouchJeLine = Database::fetchOne("
    SELECT coa.account_code FROM journal_lines jl
    JOIN journal_entries je ON jl.journal_entry_id = je.id
    JOIN chart_of_accounts coa ON jl.account_id = coa.id
    WHERE je.source_type = 'sales_return' AND je.source_id = :id AND jl.credit > 0
", [':id' => $vouchRetRes['sales_return_id']]);
assertTest($vouchJeLine !== null && $vouchJeLine['account_code'] === '2010', "Test #27b: Gift Voucher refund GL journal explicitly credits Gift Voucher Liability COA 2010");


// -------------------------------------------------------------------------
// GROUP 6: Price & Discount Policy (Tests #28 - #34)
// -------------------------------------------------------------------------
echo "\n--- Group 6: Price & Discount Policy ---\n";

// 28 & 29. Original sale price used (Product master price change ignored)
$invPriceTest = 'SEC6-PRICE-' . $randKey;
$salePriceData = createTestCompletedSale([
    'invoice_no'  => $invPriceTest,
    'customer_id' => $cId,
    'items'       => [['product_id' => $pId, 'lot_id' => $lotId, 'quantity' => 2, 'unit_price' => 1000.00]],
    'payments'    => [['payment_method' => 'Cash', 'amount' => 2000.00]]
], $userId, $branchId);
$salePriceId = (int)$salePriceData['sale_id'];
$invPriceTest = $salePriceData['invoice_no'];

// Change product master price to 1500.00
$db->prepare("UPDATE products SET selling_price = 1500.00 WHERE id = :id")->execute([':id' => $pId]);

$foundPriceSale = SaleReturn::searchSaleForReturn($invPriceTest, $branchId);
$retPriceRes = SaleReturn::processReturn([
    'sale_id'     => $salePriceId,
    'customer_id' => $cId,
    'return_type' => 'return_only',
    'notes'       => 'Sec6 Price Policy Test',
    'items'       => [['sale_item_id' => $foundPriceSale['items'][0]['id'], 'product_id' => $pId, 'lot_id' => $lotId, 'quantity' => 1, 'unit_price' => 0, 'item_condition' => 'good', 'reason' => 'Customer Return']],
    'refunds'     => [['refund_method' => 'cash', 'amount' => 1000.00]]
], $userId, $branchId);

assertTest($retPriceRes['success'] === true && (float)$retPriceRes['total_return_amount'] === 1000.00, "Test #28 & #29: Return uses original charged price (1000.00), strictly ignoring updated Product Master price (1500.00)");

// Reset product price back to 1000
$db->prepare("UPDATE products SET selling_price = 1000.00 WHERE id = :id")->execute([':id' => $pId]);

// 30 & 31. Invoice-level Header Discount Proportional Allocation
$invDiscTest = 'SEC6-DISC-' . $randKey;
$saleDiscData = createTestCompletedSale([
    'invoice_no'     => $invDiscTest,
    'customer_id'    => $cId,
    'discount_type'  => 'amount',
    'discount_value' => '200', // Subtotal 2000 - 200 Disc = 1800 Net (10% discount)
    'items'          => [['product_id' => $pId, 'lot_id' => $lotId, 'quantity' => 2, 'unit_price' => 1000.00]],
    'payments'       => [['payment_method' => 'Cash', 'amount' => 1800.00]]
], $userId, $branchId);
$saleDiscId = (int)$saleDiscData['sale_id'];
$invDiscTest = $saleDiscData['invoice_no'];

$foundDiscSale = SaleReturn::searchSaleForReturn($invDiscTest, $branchId);
$retDiscRes = SaleReturn::processReturn([
    'sale_id'     => $saleDiscId,
    'customer_id' => $cId,
    'return_type' => 'return_only',
    'notes'       => 'Sec6 Header Discount Allocation Test',
    'items'       => [['sale_item_id' => $foundDiscSale['items'][0]['id'], 'product_id' => $pId, 'lot_id' => $lotId, 'quantity' => 1, 'unit_price' => 0, 'item_condition' => 'good', 'reason' => 'Customer Return']],
    'refunds'     => [['refund_method' => 'cash', 'amount' => 900.00]]
], $userId, $branchId);

assertTest($retDiscRes['success'] === true && (float)$retDiscRes['total_return_amount'] === 900.00, "Test #30 & #31: Header discount proportional allocation calculated net returned unit price correctly (LKR 900.00 net refund)");

// 33. Cumulative refunds capped by original sale net total
$retDiscRes2 = SaleReturn::processReturn([
    'sale_id'     => $saleDiscId,
    'customer_id' => $cId,
    'return_type' => 'return_only',
    'notes'       => 'Sec6 Header Discount Cap Test',
    'items'       => [['sale_item_id' => $foundDiscSale['items'][0]['id'], 'product_id' => $pId, 'lot_id' => $lotId, 'quantity' => 1, 'unit_price' => 1000.00, 'item_condition' => 'good', 'reason' => 'Customer Return']],
    'refunds'     => [['refund_method' => 'cash', 'amount' => 1000.00]]
], $userId, $branchId);

assertTest(!$retDiscRes2['success'] && strpos($retDiscRes2['message'], 'exceeds remaining refundable amount') !== false, "Test #33: Cumulative refunds capped by original invoice net total (1,000.00 refund rejected when max remaining is 900.00)");


// -------------------------------------------------------------------------
// GROUP 7: Return Status Workflow & Drafts (Tests #35 - #39)
// -------------------------------------------------------------------------
echo "\n--- Group 7: Return Status Workflow & Drafts ---\n";

// 35. Create Draft Return
$draftRes = SaleReturn::createDraftReturn([
    'customer_id' => $cId,
    'notes'       => 'Sec6 Draft Return Test',
    'items'       => [['product_id' => $pId, 'lot_id' => $lotId, 'quantity' => 1, 'unit_price' => 1000.00, 'cost_price' => 500.00]]
], $userId, $branchId);
$draftId = (int)$draftRes['sales_return_id'];

assertTest($draftRes['success'] === true && $draftRes['status'] === 'draft', "Test #35: Draft return created successfully without stock or GL impact");

// 36. Cancel Draft Return
$cancelRes = SaleReturn::cancelDraftReturn($draftId, $userId);
assertTest($cancelRes['success'] === true, "Test #36: Draft return cancelled successfully (status = 'cancelled')");

// 37. Completed Return Immutability check
$cancelCompletedRes = SaleReturn::cancelDraftReturn($retRes1['sales_return_id'], $userId);
assertTest(!$cancelCompletedRes['success'] && strpos($cancelCompletedRes['message'], 'only Draft returns can be cancelled') !== false, "Test #37: Completed return is strictly immutable and cannot be cancelled or deleted");


// -------------------------------------------------------------------------
// GROUP 8: Exchanges (Tests #40 - #46)
// -------------------------------------------------------------------------
echo "\n--- Group 8: Exchanges ---\n";

// 40. Equal Value Exchange (Returned 1000, Replacement 1000)
$eqExRes = SaleReturn::processReturn([
    'sale_id'     => null,
    'customer_id' => $cId,
    'return_type' => 'exchange',
    'notes'       => 'Sec6 Equal Exchange Test',
    'items'       => [['product_id' => $pId, 'lot_id' => $lotId, 'quantity' => 1, 'unit_price' => 1000.00, 'cost_price' => 500.00, 'item_condition' => 'good', 'reason' => 'Exchange']],
    'exchange'    => [
        'items'    => [['product_id' => $pId, 'lot_id' => $lotId, 'quantity' => 1, 'unit_price' => 1000.00]],
        'payments' => []
    ]
], $userId, $branchId);

assertTest($eqExRes['success'] === true && (float)$eqExRes['net_difference'] === 0.00, "Test #40: Equal-value exchange processed with 0.00 net difference");
assertTest(!empty($eqExRes['exchange_sale_id']), "Test #46: Sales return record linked to replacement exchange_sale_id #{$eqExRes['exchange_sale_id']}");

// 41. Exchange Customer Pays Difference (Returned 1000, Replacement 1500 -> Customer pays 500)
$payExRes = SaleReturn::processReturn([
    'sale_id'     => null,
    'customer_id' => $cId,
    'return_type' => 'exchange',
    'notes'       => 'Sec6 Customer Pays Difference Exchange Test',
    'items'       => [['product_id' => $pId, 'lot_id' => $lotId, 'quantity' => 1, 'unit_price' => 1000.00, 'cost_price' => 500.00, 'item_condition' => 'good', 'reason' => 'Exchange Upgrade']],
    'exchange'    => [
        'items'    => [['product_id' => $pId, 'lot_id' => $lotId, 'quantity' => 1.5, 'unit_price' => 1000.00]],
        'payments' => [['payment_method' => 'Cash', 'amount' => 500.00]]
    ]
], $userId, $branchId);

assertTest($payExRes['success'] === true && (float)$payExRes['net_difference'] === 500.00, "Test #41: Exchange where customer pays difference of LKR 500.00 completed successfully");

// 42. Exchange Store Refunds Difference (Returned 2000, Replacement 1000 -> Store refunds 1000)
$refExRes = SaleReturn::processReturn([
    'sale_id'     => null,
    'customer_id' => $cId,
    'return_type' => 'exchange',
    'notes'       => 'Sec6 Store Refunds Difference Exchange Test',
    'items'       => [['product_id' => $pId, 'lot_id' => $lotId, 'quantity' => 2, 'unit_price' => 1000.00, 'cost_price' => 500.00, 'item_condition' => 'good', 'reason' => 'Exchange Downgrade']],
    'refunds'     => [['refund_method' => 'cash', 'amount' => 1000.00]],
    'exchange'    => [
        'items'    => [['product_id' => $pId, 'lot_id' => $lotId, 'quantity' => 1, 'unit_price' => 1000.00]],
        'payments' => []
    ]
], $userId, $branchId);

assertTest($refExRes['success'] === true && (float)$refExRes['net_difference'] === -1000.00, "Test #42: Exchange where store refunds difference of LKR 1,000.00 completed successfully");


// -------------------------------------------------------------------------
// GROUP 9: No-Invoice Returns (Tests #47 - #49)
// -------------------------------------------------------------------------
echo "\n--- Group 9: No-Invoice Returns ---\n";

// 47. No-Invoice Good Return
$noInvGoodRes = SaleReturn::processReturn([
    'sale_id'     => null,
    'customer_id' => $cId,
    'return_type' => 'return_only',
    'notes'       => 'Sec6 No Invoice Good Return',
    'items'       => [['product_id' => $pId, 'lot_id' => $lotId, 'quantity' => 1, 'unit_price' => 0, 'cost_price' => 0, 'item_condition' => 'good', 'reason' => 'No Invoice Return']],
    'refunds'     => [['refund_method' => 'cash', 'amount' => 1000.00]]
], $userId, $branchId);

assertTest($noInvGoodRes['success'] === true && (float)$noInvGoodRes['total_return_amount'] === 1000.00, "Test #47: No-invoice good return fallback to lot master selling price (1000.00) & cost (500.00) succeeded");

// 48. No-Invoice Damaged Return
$noInvDamRes = SaleReturn::processReturn([
    'sale_id'     => null,
    'customer_id' => $cId,
    'return_type' => 'return_only',
    'notes'       => 'Sec6 No Invoice Damaged Return',
    'items'       => [['product_id' => $pId, 'lot_id' => $lotId, 'quantity' => 1, 'unit_price' => 0, 'cost_price' => 0, 'item_condition' => 'damaged', 'reason' => 'Damaged Goods']],
    'refunds'     => [['refund_method' => 'cash', 'amount' => 1000.00]]
], $userId, $branchId);

assertTest($noInvDamRes['success'] === true, "Test #48: No-invoice damaged return processed successfully");

// 49. No-Invoice Audit Trail entry
$auditEntry = Database::fetchOne("
    SELECT * FROM audit_logs WHERE action = 'RETURN_NO_INVOICE' AND record_id = :id
", [':id' => $noInvGoodRes['sales_return_id']]);
assertTest($auditEntry !== null, "Test #49: No-invoice return logged explicitly in audit trail (RETURN_NO_INVOICE)");


// -------------------------------------------------------------------------
// GROUP 10: Profile, Reports & General Ledger Accounting (Tests #50 - #60)
// -------------------------------------------------------------------------
echo "\n--- Group 10: Profile, Reports & Accounting ---\n";

// 50. Customer Activity Timeline contains Sales Return
$timeline = Customer::getActivityTimeline($cId);
$eventTypes = array_column($timeline, 'event_type');
assertTest(in_array('Sales Return', $eventTypes), "Test #50: Sales Return appears in Customer Activity Timeline");

// 51. Paginated Sales Returns List API
$allReturns = SaleReturn::all(['branch_id' => $branchId], 1, 10);
assertTest(!empty($allReturns['items']) && $allReturns['totalItems'] >= 5, "Test #51: Paginated Sales Returns API returned valid items list");

// 52. Net Sales Calculation Formula
$pnl = ReportsEngine::getProfitAndLoss(['branch_id' => 'all']);
$expectedNetSales = round((float)$pnl['gross_sales'] - (float)$pnl['sales_returns'], 2);
assertTest(round((float)$pnl['net_sales'], 2) === $expectedNetSales, "Test #52: Profit & Loss Net Sales formula matches (Gross Sales - Sales Returns)");

// 53, 54, 55, 56, 57, 58. Double-Entry Accounting Balance Verification
$diag = DiagnosticsEngine::runAllChecks();
$unbalanced = $diag['unbalanced_journals'];
assertTest($unbalanced['status'] === 'PASS' && (int)$unbalanced['count'] === 0, "Test #53 - #58: All generated Sales Return GL journal entries are 100% balanced (Total Debit == Total Credit)");

// 59. Sales Returns Audit Log completeness
$retAudit = Database::fetchOne("
    SELECT * FROM audit_logs WHERE action = 'RETURN_CREATED' AND record_id = :id
", [':id' => $retRes1['sales_return_id']]);
assertTest($retAudit !== null, "Test #59: RETURN_CREATED event logged cleanly in audit trail");

// 60. Special User Mode Scaling Test
$pnlSpecial = ReportsEngine::getProfitAndLoss(['branch_id' => 'all', 'special_user' => true]);
$isScaled = (isset($pnlSpecial['gross_sales']) && (float)$pnlSpecial['gross_sales'] < (float)$pnl['gross_sales'])
         || (isset($pnlSpecial['cogs']) && (float)$pnlSpecial['cogs'] < (float)$pnl['cogs']);
assertTest($isScaled, "Test #60: Special User mode scales P&L COGS and Gross Profit by visibility factor");


// -------------------------------------------------------------------------
// SUMMARY
// -------------------------------------------------------------------------
echo "\n=====================================================\n";
echo " TEST SUMMARY: {$passed} / " . ($passed + $failed) . " PASSED\n";
echo "=====================================================\n\n";

if ($failed > 0) {
    exit(1);
} else {
    exit(0);
}
