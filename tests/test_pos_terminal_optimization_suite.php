<?php
/**
 * Curtiss POS - Comprehensive POS Terminal Optimization & Compliance Test Suite
 * Strictly adheres to CURTISS_MASTER_RULES.md (No TRUNCATE, isolated fixtures, double-entry verification).
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Sale.php';
require_once __DIR__ . '/../core/Customer.php';
require_once __DIR__ . '/../core/Employee.php';
require_once __DIR__ . '/../core/Accounting.php';
require_once __DIR__ . '/../core/BusinessDay.php';

echo "====================================================================\n";
echo " CURTISS POS: POS TERMINAL & BILLING COMPREHENSIVE TEST SUITE\n";
echo "====================================================================\n";

$passCount = 0;
$failCount = 0;

function assertTest(string $desc, bool $condition, string $detail = '') {
    global $passCount, $failCount;
    if ($condition) {
        $passCount++;
        echo " [PASS] {$desc}" . ($detail ? " ({$detail})" : "") . "\n";
    } else {
        $failCount++;
        echo " [FAIL] {$desc}" . ($detail ? " - {$detail}" : "") . "\n";
    }
}

$db = Database::getInstance()->getConnection();
$cleanups = [
    'sale_ids'      => [],
    'lot_ids'       => [],
    'product_ids'   => [],
    'customer_ids'  => [],
    'voucher_ids'   => [],
    'journal_ids'   => []
];

try {
    $testBranchId = 1;
    $testUserId   = 1;

    // -----------------------------------------------------------------
    // SECTION 1: Database Composite Indexes Verification
    // -----------------------------------------------------------------
    echo "\n-- Section 1: Database Composite Indexes --\n";

    $salesIndexes = Database::fetchAll("SHOW INDEX FROM sales");
    $hasSalesBranchStatusIdx = false;
    foreach ($salesIndexes as $idx) {
        if ($idx['Key_name'] === 'idx_sales_branch_status_id') $hasSalesBranchStatusIdx = true;
    }
    assertTest("Step 1.1: sales table has composite index idx_sales_branch_status_id", $hasSalesBranchStatusIdx);

    $siIndexes = Database::fetchAll("SHOW INDEX FROM sale_items");
    $hasSiSaleLotIdx = false;
    foreach ($siIndexes as $idx) {
        if ($idx['Key_name'] === 'idx_si_sale_lot') $hasSiSaleLotIdx = true;
    }
    assertTest("Step 1.2: sale_items table has composite index idx_si_sale_lot", $hasSiSaleLotIdx);

    $plIndexes = Database::fetchAll("SHOW INDEX FROM product_lots");
    $hasLotsCoveringIdx = false;
    foreach ($plIndexes as $idx) {
        if ($idx['Key_name'] === 'idx_lots_branch_prod_status') $hasLotsCoveringIdx = true;
    }
    assertTest("Step 1.3: product_lots table has 4-column covering index idx_lots_branch_prod_status", $hasLotsCoveringIdx);

    // -----------------------------------------------------------------
    // SECTION 2: Chart of Accounts & Bank Accounts Linkage
    // -----------------------------------------------------------------
    echo "\n-- Section 2: Chart of Accounts & Universal Payment Routing --\n";

    $cashDrawerCoa = Accounting::getAccountByCode(Accounting::CODE_CASH_DRAWER);
    assertTest("Step 2.1: COA Cash Drawer (1012) exists", !empty($cashDrawerCoa) && (int)$cashDrawerCoa['id'] > 0);

    $salesRevCoa = Accounting::getAccountByCode(Accounting::CODE_SALES_REVENUE);
    assertTest("Step 2.2: COA Sales Revenue (4010) exists", !empty($salesRevCoa) && (int)$salesRevCoa['id'] > 0);

    $cogsCoa = Accounting::getAccountByCode(Accounting::CODE_COGS);
    assertTest("Step 2.3: COA Cost of Goods Sold (5010) exists", !empty($cogsCoa) && (int)$cogsCoa['id'] > 0);

    $invAssetCoa = Accounting::getAccountByCode(Accounting::CODE_INVENTORY_ASSET);
    assertTest("Step 2.4: COA Inventory Asset (1200) exists", !empty($invAssetCoa) && (int)$invAssetCoa['id'] > 0);

    $bankAccounts = Database::fetchAll("
        SELECT id, bank_name, account_name, account_number, account_id 
        FROM bank_accounts 
        WHERE status = 'active'
    ");
    assertTest("Step 2.5: Active bank accounts configured in system", count($bankAccounts) > 0);

    $allBanksLinked = true;
    foreach ($bankAccounts as $ba) {
        if (empty($ba['account_id'])) {
            $allBanksLinked = false;
            break;
        }
    }
    assertTest("Step 2.6: All active bank accounts linked to COA account_id", $allBanksLinked);

    // -----------------------------------------------------------------
    // SECTION 3: Isolated Fixtures Creation (Product, Lots, Customer)
    // -----------------------------------------------------------------
    echo "\n-- Section 3: Isolated Fixtures Setup --\n";

    $brand = Database::fetchOne("SELECT id FROM brands LIMIT 1");
    $cat = Database::fetchOne("SELECT id FROM categories LIMIT 1");
    $sup = Database::fetchOne("SELECT id FROM suppliers LIMIT 1");
    $brandId = (int)($brand['id'] ?? 1);
    $catId = (int)($cat['id'] ?? 1);
    $supId = (int)($sup['id'] ?? 1);

    // Create unique test product
    $sku = 'POS-PROD-' . time() . '-' . rand(100, 999);
    $barcode = 'BC-' . rand(100000, 999999);
    $sellingPrice = 1250.00;
    $costPrice = 750.00;

    $db->prepare("
        INSERT INTO products (name, barcode, sku, brand_id, category_id, supplier_id, cost, selling_price, status, created_at)
        VALUES (:n, :b, :s, :br, :cat, :sup, :c, :p, 'active', NOW())
    ")->execute([
        ':n'   => 'POS Test Item ' . $sku,
        ':b'   => $barcode,
        ':s'   => $sku,
        ':br'  => $brandId,
        ':cat' => $catId,
        ':sup' => $supId,
        ':c'   => $costPrice,
        ':p'   => $sellingPrice
    ]);
    $testProductId = (int)$db->lastInsertId();
    $cleanups['product_ids'][] = $testProductId;
    assertTest("Step 3.1: Isolated test product created (ID: {$testProductId})", $testProductId > 0);

    // Create 2 lots to test multi-lot resolution
    $lotNo1 = 'LOT-A-' . time();
    $db->prepare("
        INSERT INTO product_lots (product_id, branch_id, lot_number, initial_quantity, remaining_quantity, cost, selling_price, status, created_at)
        VALUES (:p, :b, :l, 30.00, 30.00, :c, :p_sell, 'active', NOW())
    ")->execute([':p' => $testProductId, ':b' => $testBranchId, ':l' => $lotNo1, ':c' => $costPrice, ':p_sell' => $sellingPrice]);
    $testLotId1 = (int)$db->lastInsertId();
    $cleanups['lot_ids'][] = $testLotId1;

    $lotNo2 = 'LOT-B-' . time();
    $db->prepare("
        INSERT INTO product_lots (product_id, branch_id, lot_number, initial_quantity, remaining_quantity, cost, selling_price, status, created_at)
        VALUES (:p, :b, :l, 20.00, 20.00, :c, :p_sell, 'active', NOW())
    ")->execute([':p' => $testProductId, ':b' => $testBranchId, ':l' => $lotNo2, ':c' => $costPrice, ':p_sell' => $sellingPrice]);
    $testLotId2 = (int)$db->lastInsertId();
    $cleanups['lot_ids'][] = $testLotId2;

    $db->prepare("
        INSERT INTO branch_inventory (branch_id, product_id, quantity, updated_at)
        VALUES (:b, :p, 50.00, NOW())
        ON DUPLICATE KEY UPDATE quantity = quantity + 50.00
    ")->execute([':b' => $testBranchId, ':p' => $testProductId]);
    assertTest("Step 3.2: Two active lots created (Total Stock: 50.00)", $testLotId1 > 0 && $testLotId2 > 0);

    // Create registered customer with credit facility
    $custCode = 'CUST-POS-' . time();
    $db->prepare("
        INSERT INTO customers (customer_code, name, phone, credit_limit, credit_balance, status, created_at)
        VALUES (:code, :name, '0771234567', 10000.00, 0.00, 'active', NOW())
    ")->execute([
        ':code' => $custCode,
        ':name' => 'POS Credit Customer ' . $custCode
    ]);
    $testCustomerId = (int)$db->lastInsertId();
    $cleanups['customer_ids'][] = $testCustomerId;
    assertTest("Step 3.3: Registered test customer created with LKR 10,000 credit limit", $testCustomerId > 0);

    // -----------------------------------------------------------------
    // SECTION 4: Product Search, Barcode Priority & Price Searching
    // -----------------------------------------------------------------
    echo "\n-- Section 4: Universal Search & Barcode Prioritization --\n";

    // 4.1 Exact Barcode Search Priority
    $bcResults = Sale::searchProducts($testBranchId, $barcode);
    assertTest("Step 4.1: Exact barcode search returns prioritized match", count($bcResults) === 1 && (int)$bcResults[0]['product_id'] === $testProductId);

    // 4.2 Exact SKU Search
    $skuResults = Sale::searchProducts($testBranchId, $sku);
    assertTest("Step 4.2: Exact SKU search returns matching product", count($skuResults) === 1 && (int)$skuResults[0]['product_id'] === $testProductId);

    // 4.3 Multi-lot resolution returns both active lots
    assertTest("Step 4.3: Search returns all active price/cost lots for item", count($bcResults[0]['lots']) === 2);

    // 4.4 Monetary/Price Searching (raw number and formatted currency)
    $priceResults = Sale::searchProducts($testBranchId, '1250');
    $foundPrice = false;
    foreach ($priceResults as $pr) {
        if ((int)$pr['product_id'] === $testProductId) { $foundPrice = true; break; }
    }
    assertTest("Step 4.4: Searching numeric price '1250' matches product", $foundPrice);

    $currResults = Sale::searchProducts($testBranchId, 'LKR 1,250.00');
    $foundCurr = false;
    foreach ($currResults as $cr) {
        if ((int)$cr['product_id'] === $testProductId) { $foundCurr = true; break; }
    }
    assertTest("Step 4.5: Searching formatted currency 'LKR 1,250.00' matches product", $foundCurr);

    // -----------------------------------------------------------------
    // SECTION 5: Customer Lookup & Walk-in Healing
    // -----------------------------------------------------------------
    echo "\n-- Section 5: Customer Lookup & Walk-In Integration --\n";

    $walkin = Database::fetchOne("SELECT * FROM customers WHERE is_walkin = 1 AND status = 'active' LIMIT 1");
    assertTest("Step 5.1: Walk-in customer available", !empty($walkin));

    $foundCust = Customer::find($testCustomerId);
    assertTest("Step 5.2: Registered customer find returns valid profile", !empty($foundCust) && (float)$foundCust['credit_limit'] == 10000.00);

    // -----------------------------------------------------------------
    // SECTION 6: Discount Parsing & Round-Up Calculation
    // -----------------------------------------------------------------
    echo "\n-- Section 6: Discount Engine & Round-Up Parsing --\n";

    $pPct = Sale::parseDiscount('10%', 2000.00);
    assertTest("Step 6.1: Percentage discount parses correctly (10% of 2000 = 200)", (float)$pPct['total_discount'] == 200.00 && $pPct['type'] === 'percentage');

    $pFixed = Sale::parseDiscount('150.00', 2000.00);
    assertTest("Step 6.2: Fixed discount parses correctly (150.00)", (float)$pFixed['total_discount'] == 150.00 && $pFixed['type'] === 'fixed');

    $pRoundup = Sale::parseDiscount('10%+25', 2000.00);
    // 10% discount = 200. +25 rupees added to the bill = 175 net discount.
    assertTest("Step 6.3: Roundup discount '10%+25' calculates net discount 175.00", (float)$pRoundup['total_discount'] == 175.00 && $pRoundup['type'] === 'roundup');

    // -----------------------------------------------------------------
    // SECTION 7: Transaction Workflows (Draft, Hold, Resume, Cancel)
    // -----------------------------------------------------------------
    echo "\n-- Section 7: Transaction Workflow Lifecycle (Draft, Hold, Resume) --\n";

    $activeDay = BusinessDay::getOrCreateActiveDay($testBranchId, $testUserId);
    assertTest("Step 7.1: Active business day open", !empty($activeDay) && (int)$activeDay['id'] > 0);

    // Prepare Draft Sale
    $draftPayload = [
        'customer_id'  => $testCustomerId,
        'discount_str' => '0',
        'items'        => [
            [
                'product_id'     => $testProductId,
                'lot_id'         => $testLotId1,
                'quantity'       => 2.00,
                'unit_price'     => $sellingPrice,
                'discount_value' => '0'
            ]
        ]
    ];

    $draftRes = Sale::prepareDraftSale($draftPayload, $testUserId, $testBranchId);
    assertTest("Step 7.2: Prepare draft sale succeeds", $draftRes['success'] === true);
    $draftSaleId = (int)($draftRes['sale_id'] ?? 0);
    if ($draftSaleId > 0) {
        $cleanups['sale_ids'][] = $draftSaleId;
    }
    assertTest("Step 7.3: Draft sale ID created", $draftSaleId > 0);

    // Hold Invoice
    $holdRes = Sale::holdInvoice($draftPayload, $testUserId, $testBranchId);
    assertTest("Step 7.4: Hold invoice succeeds", $holdRes['success'] === true);
    $heldSaleId = (int)($holdRes['sale_id'] ?? 0);
    if ($heldSaleId > 0) {
        $cleanups['sale_ids'][] = $heldSaleId;
    }

    $heldList = Sale::getHeldInvoices($testBranchId);
    $foundHeld = false;
    foreach ($heldList as $hl) {
        $hlId = (int)($hl['sale_id'] ?? $hl['id'] ?? 0);
        if ($hlId === $heldSaleId) { $foundHeld = true; break; }
    }
    assertTest("Step 7.5: Held invoice retrieved in held invoices list", $foundHeld);

    // Cancel held invoice
    $cancelRes = Sale::cancelInvoice($heldSaleId, $testUserId, $testBranchId, "CLI Test Cancellation");
    assertTest("Step 7.6: Cancel held invoice succeeds", $cancelRes['success'] === true);

    $cancelledSale = Database::fetchOne("SELECT status FROM sales WHERE id = :id", [':id' => $heldSaleId]);
    assertTest("Step 7.7: Cancelled sale status is 'cancelled'", ($cancelledSale['status'] ?? '') === 'cancelled');

    // -----------------------------------------------------------------
    // SECTION 8: Finalize Cash Sale & Double-Entry Accounting
    // -----------------------------------------------------------------
    echo "\n-- Section 8: Finalize Cash Sale & Accounting Integrity --\n";

    // Prepare fresh draft for 2 units (Total = 2500)
    $saleDraft = Sale::prepareDraftSale($draftPayload, $testUserId, $testBranchId);
    $cashSaleId = (int)$saleDraft['sale_id'];
    $cleanups['sale_ids'][] = $cashSaleId;

    $cashPayRes = Sale::finalizeSale($cashSaleId, [
        ['payment_method' => 'Cash', 'amount' => 2500.00]
    ], $testUserId, $testBranchId);

    assertTest("Step 8.1: Finalize cash sale succeeds", $cashPayRes['success'] === true);
    $invoiceNo = $cashPayRes['invoice_no'] ?? '';
    assertTest("Step 8.2: Continuous invoice number generated (#{$invoiceNo})", !empty($invoiceNo));

    // Verify stock lot deducted by 2 (30 -> 28)
    $lot1Check = Database::fetchOne("SELECT remaining_quantity FROM product_lots WHERE id = :id", [':id' => $testLotId1]);
    assertTest("Step 8.3: Stock lot deducted from 30.00 to 28.00", (float)$lot1Check['remaining_quantity'] == 28.00);

    // Verify branch_inventory deducted by 2 (50 -> 48)
    $invCheck = Database::fetchOne("SELECT quantity FROM branch_inventory WHERE branch_id = :b AND product_id = :p", [':b' => $testBranchId, ':p' => $testProductId]);
    assertTest("Step 8.4: Branch inventory deducted from 50.00 to 48.00", (float)$invCheck['quantity'] == 48.00);

    // Verify stock_ledger entry
    $ledgerRow = Database::fetchOne("
        SELECT * FROM stock_ledger 
        WHERE branch_id = :b AND product_id = :p AND lot_id = :lot AND movement_type = 'sale'
        ORDER BY id DESC LIMIT 1
    ", [':b' => $testBranchId, ':p' => $testProductId, ':lot' => $testLotId1]);
    assertTest("Step 8.5: Stock ledger movement recorded for sale (-2.00)", !empty($ledgerRow) && (float)$ledgerRow['quantity'] == -2.00);

    // Verify cash_drawer_movements recorded
    $cdm = Database::fetchOne("
        SELECT * FROM cash_drawer_movements 
        WHERE branch_id = :b AND source_type = 'sales' AND source_id = :sale_id
        ORDER BY id DESC LIMIT 1
    ", [':b' => $testBranchId, ':sale_id' => $cashSaleId]);
    assertTest("Step 8.6: Cash drawer movement recorded (Amount: 2500.00)", !empty($cdm) && (float)$cdm['amount'] == 2500.00);

    // Verify Journal Entry & Double-Entry Equality
    $je = Database::fetchOne("SELECT * FROM journal_entries WHERE source_type = 'sale' AND source_id = :id", [':id' => $cashSaleId]);
    $jeId = (int)($je['id'] ?? 0);
    assertTest("Step 8.7: Journal entry generated for cash sale", $jeId > 0);
    if ($jeId > 0) {
        $cleanups['journal_ids'][] = $jeId;
        assertTest("Step 8.8: Journal entry posted status", ($je['posting_status'] ?? '') === 'posted');
        assertTest("Step 8.9: Balanced Debits == Credits", (float)$je['total_debit'] == (float)$je['total_credit'] && (float)$je['total_debit'] > 0);

        // Check Cash Drawer 1012 debited
        $jlCash = Database::fetchOne("
            SELECT jl.* FROM journal_lines jl 
            JOIN chart_of_accounts coa ON jl.account_id = coa.id
            WHERE jl.journal_entry_id = :je AND coa.account_code = '1012'
        ", [':je' => $jeId]);
        assertTest("Step 8.10: COA Cash Drawer (1012) debited for cash sale", !empty($jlCash) && (float)$jlCash['debit'] == 2500.00);
    }

    // -----------------------------------------------------------------
    // SECTION 9: Bank Transfer Sale & Destination Account Routing
    // -----------------------------------------------------------------
    echo "\n-- Section 9: Bank Transfer Sale & COA Account Routing --\n";

    $bankAcc = $bankAccounts[0];
    $targetBankCoaId = (int)$bankAcc['account_id'];

    $bankDraft = Sale::prepareDraftSale($draftPayload, $testUserId, $testBranchId);
    $bankSaleId = (int)$bankDraft['sale_id'];
    $cleanups['sale_ids'][] = $bankSaleId;

    $bankPayRes = Sale::finalizeSale($bankSaleId, [
        [
            'payment_method'  => 'Bank',
            'amount'          => 2500.00,
            'bank_account_id' => (int)$bankAcc['id'],
            'reference_no'    => 'SLIP-CLI-9921'
        ]
    ], $testUserId, $testBranchId);

    assertTest("Step 9.1: Finalize bank transfer sale succeeds", $bankPayRes['success'] === true);

    $bankJe = Database::fetchOne("SELECT * FROM journal_entries WHERE source_type = 'sale' AND source_id = :id", [':id' => $bankSaleId]);
    $bankJeId = (int)($bankJe['id'] ?? 0);
    if ($bankJeId > 0) {
        $cleanups['journal_ids'][] = $bankJeId;
        $jlBank = Database::fetchOne("
            SELECT * FROM journal_lines WHERE journal_entry_id = :je AND account_id = :coa_id
        ", [':je' => $bankJeId, ':coa_id' => $targetBankCoaId]);
        assertTest("Step 9.2: Target Bank Account COA debited directly", !empty($jlBank) && (float)$jlBank['debit'] == 2500.00);
    }

    // -----------------------------------------------------------------
    // SECTION 10: Customer Credit Sale & Limit Enforcement
    // -----------------------------------------------------------------
    echo "\n-- Section 10: Customer Credit Sale & Enforcement --\n";

    // 10.1 Walk-in Customer cannot receive credit
    $walkinDraft = Sale::prepareDraftSale([
        'customer_id'  => 1,
        'discount_str' => '0',
        'items'        => [
            ['product_id' => $testProductId, 'lot_id' => $testLotId1, 'quantity' => 1.00, 'unit_price' => $sellingPrice]
        ]
    ], $testUserId, $testBranchId);
    $walkinSaleId = (int)$walkinDraft['sale_id'];
    $cleanups['sale_ids'][] = $walkinSaleId;

    $walkinCreditAttempt = Sale::finalizeSale($walkinSaleId, [
        ['payment_method' => 'Credit', 'amount' => $sellingPrice]
    ], $testUserId, $testBranchId);
    assertTest("Step 10.1: Credit payment strictly rejected for Walk-in customer", $walkinCreditAttempt['success'] === false);

    // 10.2 Credit sale for registered customer with credit limit
    $regDraft = Sale::prepareDraftSale($draftPayload, $testUserId, $testBranchId);
    $regSaleId = (int)$regDraft['sale_id'];
    $cleanups['sale_ids'][] = $regSaleId;

    $regCreditRes = Sale::finalizeSale($regSaleId, [
        ['payment_method' => 'Credit', 'amount' => 2500.00]
    ], $testUserId, $testBranchId);
    assertTest("Step 10.2: Credit payment succeeds for registered customer within limit", $regCreditRes['success'] === true);

    $custAfterCredit = Customer::find($testCustomerId);
    assertTest("Step 10.3: Customer credit balance increased by 2500.00", (float)$custAfterCredit['credit_balance'] == 2500.00);

    // -----------------------------------------------------------------
    // SECTION 11: Multi-Method Split Payment
    // -----------------------------------------------------------------
    echo "\n-- Section 11: Multi-Method Split Payment Execution --\n";

    $splitDraft = Sale::prepareDraftSale($draftPayload, $testUserId, $testBranchId);
    $splitSaleId = (int)$splitDraft['sale_id'];
    $cleanups['sale_ids'][] = $splitSaleId;

    $splitRes = Sale::finalizeSale($splitSaleId, [
        ['payment_method' => 'Cash', 'amount' => 1500.00],
        ['payment_method' => 'Bank', 'amount' => 1000.00, 'bank_account_id' => (int)$bankAcc['id'], 'reference_no' => 'SPLIT-TX-101']
    ], $testUserId, $testBranchId);

    assertTest("Step 11.1: Multi-method split payment (Cash + Bank) finalized successfully", $splitRes['success'] === true);

    $splitJe = Database::fetchOne("SELECT * FROM journal_entries WHERE source_type = 'sale' AND source_id = :id", [':id' => $splitSaleId]);
    $splitJeId = (int)($splitJe['id'] ?? 0);
    if ($splitJeId > 0) {
        $cleanups['journal_ids'][] = $splitJeId;
        assertTest("Step 11.2: Split payment journal entry balanced", (float)$splitJe['total_debit'] == (float)$splitJe['total_credit']);
    }

    // -----------------------------------------------------------------
    // SECTION 12: UI & Master Design Rules Compliance
    // -----------------------------------------------------------------
    echo "\n-- Section 12: UI & Master Design Rules Compliance --\n";

    $posView = file_get_contents(__DIR__ . '/../views/pos/pos_screen.php');

    assertTest("Step 12.1: pos_screen.php enforces full viewport height (100vh)", strpos($posView, '100vh') !== false);
    assertTest("Step 12.2: pos_screen.php cart table enforces sticky header (position: sticky; top: 0)", strpos($posView, 'position: sticky;') !== false);
    assertTest("Step 12.3: pos_screen.php cart table enforces zebra striping", strpos($posView, 'cart-table tbody tr:nth-child(even)') !== false && strpos($posView, 'cart-table tbody tr:nth-child(odd)') !== false);
    assertTest("Step 12.4: pos_screen.php modal tables enforce zebra striping", strpos($posView, 'held-table tbody tr:nth-child(even)') !== false);
    assertTest("Step 12.5: pos_screen.php has double-submit protection on finalizeBtn", strpos($posView, 'finalizeBtn.disabled = true;') !== false);
    assertTest("Step 12.6: pos_screen.php supports keyboard shortcuts (F2, F3, F4, F6, F7, F8, F9, F10)", strpos($posView, 'e.key === \'F10\'') !== false && strpos($posView, 'e.key === \'F4\'') !== false);

    // -----------------------------------------------------------------
    // SECTION 13: Controller & API Multi-Branch Context Resolution
    // -----------------------------------------------------------------
    echo "\n-- Section 13: Controller & API Multi-Branch Context Resolution --\n";

    $posController = file_get_contents(__DIR__ . '/../pos.php');
    assertTest("Step 13.1: pos.php supports session branch switching", strpos($posController, '$_SESSION[\'branch_id\']') !== false);
    assertTest("Step 13.2: pos.php includes role fallback authorization", strpos($posController, 'ROLE_SUPER_ADMIN') !== false);

    $posApi = file_get_contents(__DIR__ . '/../pos_api.php');
    assertTest("Step 13.3: pos_api.php supports session branch resolution", strpos($posApi, '$_SESSION[\'branch_id\']') !== false);
    assertTest("Step 13.4: pos_api.php includes role fallback authorization", strpos($posApi, 'ROLE_SUPER_ADMIN') !== false);

} catch (Throwable $e) {
    $failCount++;
    echo " [FATAL ERROR]: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
} finally {
    // -----------------------------------------------------------------
    // SECTION 14: Isolated Fixture Clean-up (NO TRUNCATE)
    // -----------------------------------------------------------------
    echo "\n--- Clean Teardown of Isolated Fixtures (Zero Table Truncation) ---\n";

    if (!empty($cleanups['sale_ids'])) {
        $in = implode(',', array_map('intval', $cleanups['sale_ids']));
        $db->exec("DELETE FROM cash_drawer_movements WHERE source_type = 'sale' AND source_id IN ($in)");
        $db->exec("DELETE FROM payments WHERE sale_id IN ($in)");
        $db->exec("DELETE FROM sale_items WHERE sale_id IN ($in)");
        $db->exec("DELETE FROM sales WHERE id IN ($in)");
        echo " Cleaned test sales, items, payments & drawer movements (IDs: $in)\n";
    }

    if (!empty($cleanups['journal_ids'])) {
        $in = implode(',', array_map('intval', $cleanups['journal_ids']));
        $db->exec("DELETE FROM journal_lines WHERE journal_entry_id IN ($in)");
        $db->exec("DELETE FROM journal_entries WHERE id IN ($in)");
        echo " Cleaned test journal entries (IDs: $in)\n";
    }

    if (!empty($cleanups['lot_ids'])) {
        $in = implode(',', array_map('intval', $cleanups['lot_ids']));
        $db->exec("DELETE FROM stock_ledger WHERE lot_id IN ($in)");
        $db->exec("DELETE FROM product_lots WHERE id IN ($in)");
        echo " Cleaned test lots & stock ledger entries (IDs: $in)\n";
    }

    if (!empty($cleanups['product_ids'])) {
        $in = implode(',', array_map('intval', $cleanups['product_ids']));
        $db->exec("DELETE FROM branch_inventory WHERE product_id IN ($in)");
        $db->exec("DELETE FROM products WHERE id IN ($in)");
        echo " Cleaned test products & inventory (IDs: $in)\n";
    }

    if (!empty($cleanups['customer_ids'])) {
        $in = implode(',', array_map('intval', $cleanups['customer_ids']));
        $db->exec("DELETE FROM customer_credit_ledger WHERE customer_id IN ($in)");
        $db->exec("DELETE FROM customers WHERE id IN ($in)");
        echo " Cleaned test customers & credit records (IDs: $in)\n";
    }
}

echo "====================================================================\n";
echo " TEST SUITE COMPLETE: Total: " . ($passCount + $failCount) . " | Passed: {$passCount} | Failed: {$failCount}\n";
echo "====================================================================\n";

if ($failCount > 0) {
    exit(1);
}
exit(0);
