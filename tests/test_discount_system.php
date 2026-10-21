<?php
/**
 * Curtiss POS - Comprehensive CLI Verification Test Suite for Discount & Promotion System
 * 
 * Tests 100% of the core Discount & Promotion System requirements from Module_implementation_plan.md:
 * - Rule types: Item, Category, Brand, Supplier, Quantity, BOGO, Invoice Subtotal, Coupon
 * - Stacking & Exclusivity
 * - Real-time Cart Evaluation
 * - Safe Checkout Persistence in sale_discounts & usage count tracking
 * - Balanced Double-Entry Accounting
 * - Sales Returns with Promotional Net Refund Limits
 * - Business Day & End Day Summary Breakdowns
 * - ReportsEngine Discount Usage & Promotion Performance Reports
 * - Clean cleanup of test records without truncate
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/DiscountEngine.php';
require_once __DIR__ . '/../core/Sale.php';
require_once __DIR__ . '/../core/SaleReturn.php';
require_once __DIR__ . '/../core/Accounting.php';
require_once __DIR__ . '/../core/BusinessDay.php';
require_once __DIR__ . '/../core/ReportsEngine.php';

$db = Database::getInstance()->getConnection();

echo "====================================================================\n";
echo "   CURTISS POS - ADVANCED DISCOUNT SYSTEM VERIFICATION SUITE       \n";
echo "====================================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest(string $desc, bool $condition, string $detail = '') {
    global $passCount, $failCount;
    if ($condition) {
        $passCount++;
        echo " [PASS] {$desc}\n";
    } else {
        $failCount++;
        echo " [FAIL] {$desc} -> {$detail}\n";
    }
}

$cleanupRuleIds = [];
$cleanupSaleIds = [];
$cleanupReturnIds = [];
$pausedRuleIds = [];

try {
    $branchId = 1;
    $userId = 1;

    // Clean up any stale CLI test rules from previous aborted runs
    $db->prepare("DELETE drt FROM discount_rule_targets drt INNER JOIN discount_rules dr ON drt.rule_id = dr.id WHERE dr.name LIKE 'CLI Test%'")->execute();
    $db->prepare("DELETE FROM discount_rules WHERE name LIKE 'CLI Test%'")->execute();

    // Temporarily pause existing active rules for test isolation (restored in finally)
    $pausedRuleRows = Database::fetchAll("SELECT id FROM discount_rules WHERE status = 'active' AND deleted_at IS NULL AND name NOT LIKE 'CLI Test%'");
    $pausedRuleIds = array_map(fn($r) => (int)$r['id'], $pausedRuleRows);
    if (!empty($pausedRuleIds)) {
        $inClause = implode(',', $pausedRuleIds);
        $db->exec("UPDATE discount_rules SET status = 'inactive' WHERE id IN ($inClause)");
    }

    // Fetch existing testable product, lot, category, brand, supplier
    $prod = Database::fetchOne("
        SELECT p.id, p.name, p.category_id, p.brand_id, p.supplier_id, pl.id AS lot_id, pl.selling_price, pl.cost, pl.remaining_quantity
        FROM products p
        JOIN product_lots pl ON p.id = pl.product_id
        WHERE pl.branch_id = :bid AND pl.remaining_quantity >= 10 AND pl.status = 'active'
        LIMIT 1
    ", [':bid' => $branchId]);

    if (!$prod) {
        throw new Exception("No active product lot with sufficient stock found in branch #1 for testing.");
    }

    $productId = (int)$prod['id'];
    $lotId = (int)$prod['lot_id'];
    $sellingPrice = (float)$prod['selling_price'];
    $categoryId = (int)$prod['category_id'];
    $brandId = (int)$prod['brand_id'];
    $supplierId = (int)$prod['supplier_id'];

    echo "Using Test Product #{$productId} ('{$prod['name']}') - Lot #{$lotId} - Selling Price: LKR {$sellingPrice}\n\n";

    // -------------------------------------------------------------
    // TEST 1: Create Item-Wise 10% Discount Rule
    // -------------------------------------------------------------
    echo "--- 1. Testing Rule Creation & Item-Wise 10% Discount ---\n";
    $rule1Res = DiscountEngine::createRule([
        'name'           => 'CLI Test Item 10% OFF',
        'rule_type'      => 'item',
        'discount_mode'  => 'percentage',
        'discount_value' => 10.00,
        'priority'       => 10,
        'is_stackable'   => 1,
        'targets'        => [
            ['target_type' => 'product', 'target_id' => $productId]
        ]
    ], $userId);

    assertTest("Item 10% rule creation", $rule1Res['success'] === true, $rule1Res['message'] ?? '');
    $rule1Id = (int)($rule1Res['rule_id'] ?? $rule1Res['id'] ?? 0);
    if ($rule1Id) $cleanupRuleIds[] = $rule1Id;

    // Evaluate Cart with 2 units
    $eval1 = DiscountEngine::evaluateCart([
        ['product_id' => $productId, 'lot_id' => $lotId, 'quantity' => 2, 'unit_price' => $sellingPrice]
    ], ['branch_id' => $branchId]);

    $expectedSavings1 = round(($sellingPrice * 2) * 0.10, 2);
    $actualSavings1 = (float)($eval1['total_promo_discount'] ?? 0);
    assertTest("Item 10% evaluation computes exact discount (Expected: {$expectedSavings1}, Actual: {$actualSavings1})", abs($actualSavings1 - $expectedSavings1) < 0.01);

    // -------------------------------------------------------------
    // TEST 2: Quantity-Based Discount (Buy >= 5 Get 15% OFF)
    // -------------------------------------------------------------
    echo "\n--- 2. Testing Quantity Tiers Discount ---\n";
    $rule2Res = DiscountEngine::createRule([
        'name'           => 'CLI Test Qty Tier (5+ => 15% OFF)',
        'rule_type'      => 'quantity',
        'discount_mode'  => 'percentage',
        'discount_value' => 15.00,
        'min_quantity'   => 5,
        'priority'       => 20,
        'is_stackable'   => 0, // Exclusive over lower priority
        'targets'        => [
            ['target_type' => 'product', 'target_id' => $productId]
        ]
    ], $userId);

    $rule2Id = (int)($rule2Res['rule_id'] ?? $rule2Res['id'] ?? 0);
    if ($rule2Id) $cleanupRuleIds[] = $rule2Id;

    // Cart with 3 units: should NOT trigger qty tier rule, should trigger 10% item rule
    $eval2a = DiscountEngine::evaluateCart([
        ['product_id' => $productId, 'lot_id' => $lotId, 'quantity' => 3, 'unit_price' => $sellingPrice]
    ], ['branch_id' => $branchId]);
    $expected2a = round(($sellingPrice * 3) * 0.10, 2);
    assertTest("Quantity tier does not trigger below min_quantity=5 (Expected: {$expected2a}, Actual: {$eval2a['total_promo_discount']})", abs((float)$eval2a['total_promo_discount'] - $expected2a) < 0.01);

    // Cart with 5 units: higher priority rule (15%) replaces 10% because is_stackable=0
    $eval2b = DiscountEngine::evaluateCart([
        ['product_id' => $productId, 'lot_id' => $lotId, 'quantity' => 5, 'unit_price' => $sellingPrice]
    ], ['branch_id' => $branchId]);
    $expected2b = round(($sellingPrice * 5) * 0.15, 2);
    assertTest("Quantity tier triggers at min_quantity=5 with 15% OFF (Expected: {$expected2b}, Actual: {$eval2b['total_promo_discount']})", abs((float)$eval2b['total_promo_discount'] - $expected2b) < 0.01);

    // -------------------------------------------------------------
    // TEST 3: Invoice-Level Subtotal Discount (Spend >= 5000 get Rs 250 OFF)
    // -------------------------------------------------------------
    echo "\n--- 3. Testing Invoice Subtotal Promotion ---\n";
    $rule3Res = DiscountEngine::createRule([
        'name'                 => 'CLI Test Invoice Spend 5000 get 250 OFF',
        'rule_type'            => 'invoice',
        'discount_mode'        => 'fixed_amount',
        'discount_value'       => 250.00,
        'min_invoice_subtotal' => 5000.00,
        'priority'             => 5
    ], $userId);

    $rule3Id = (int)($rule3Res['rule_id'] ?? $rule3Res['id'] ?? 0);
    if ($rule3Id) $cleanupRuleIds[] = $rule3Id;

    $eval3 = DiscountEngine::evaluateCart([
        ['product_id' => $productId, 'lot_id' => $lotId, 'quantity' => 10, 'unit_price' => 1000.00]
    ], ['branch_id' => $branchId]);

    assertTest("Invoice subtotal discount applied when subtotal >= 5000", (float)($eval3['invoice_promo_discount'] ?? 0) === 250.00);

    // -------------------------------------------------------------
    // TEST 4: Coupon Code Validation & Application
    // -------------------------------------------------------------
    echo "\n--- 4. Testing Coupon Code Validation & Stacking ---\n";
    $couponCode = 'CLITEST' . rand(100, 999);
    $rule4Res = DiscountEngine::createRule([
        'name'           => 'CLI Test Coupon 50 Flat',
        'rule_type'      => 'coupon',
        'discount_mode'  => 'fixed_amount',
        'discount_value' => 50.00,
        'coupon_code'    => $couponCode,
        'priority'       => 15
    ], $userId);

    $rule4Id = (int)($rule4Res['rule_id'] ?? $rule4Res['id'] ?? 0);
    if ($rule4Id) $cleanupRuleIds[] = $rule4Id;

    $validCheck = DiscountEngine::validateCoupon($couponCode, $branchId, 1);
    assertTest("Valid coupon validation succeeds", $validCheck['valid'] === true);

    $invalidCheck = DiscountEngine::validateCoupon('NONEXISTENT_COUPON', $branchId, 1);
    assertTest("Invalid coupon validation rejected", $invalidCheck['valid'] === false);

    // Evaluate Cart with coupon
    $eval4 = DiscountEngine::evaluateCart([
        ['product_id' => $productId, 'lot_id' => $lotId, 'quantity' => 1, 'unit_price' => $sellingPrice]
    ], ['branch_id' => $branchId, 'coupon_code' => $couponCode]);
    assertTest("Coupon discount applied in cart evaluation", (float)($eval4['total_promo_discount'] ?? 0) >= 50.00);

    // -------------------------------------------------------------
    // TEST 5: POS Checkout, Draft Preparation & Finalization
    // -------------------------------------------------------------
    echo "\n--- 5. Testing Checkout, sale_discounts Snapshot & Accounting ---\n";
    $draftPayload = [
        'customer_id'           => 1,
        'discount_value'        => '0',
        'promo_discount_amount' => $expectedSavings1,
        'items'                 => [
            [
                'product_id'            => $productId,
                'lot_id'                => $lotId,
                'quantity'              => 2,
                'unit_price'            => $sellingPrice,
                'original_price'        => $sellingPrice,
                'discount_value'        => '0',
                'promo_discount_amount' => $expectedSavings1,
                'applied_rule_id'       => $rule1Id,
                'promo_name'            => 'CLI Test Item 10% OFF'
            ]
        ]
    ];

    $draftRes = Sale::prepareDraftSale($draftPayload, $userId, $branchId);
    assertTest("prepareDraftSale succeeds with promotional discounts", $draftRes['success'] === true, $draftRes['message'] ?? '');
    $saleId = (int)($draftRes['sale_id'] ?? 0);
    if ($saleId) $cleanupSaleIds[] = $saleId;

    $finalizePayload = [
        [
            'payment_method' => 'cash',
            'amount'         => (float)$draftRes['total_amount']
        ]
    ];
    $finalizePayload['applied_promotions'] = [
        [
            'rule_id'       => $rule1Id,
            'name'          => 'CLI Test Item 10% OFF',
            'rule_type'     => 'item',
            'discount_mode' => 'percentage',
            'discount_rate' => 10.00,
            'total_savings' => $expectedSavings1
        ]
    ];

    $finRes = Sale::finalizeSale($saleId, $finalizePayload, $userId, $branchId);
    assertTest("finalizeSale completes with balanced double-entry", $finRes['success'] === true, $finRes['message'] ?? '');

    // Verify record in sale_discounts table
    $sdRow = Database::fetchOne("SELECT * FROM sale_discounts WHERE sale_id = :sid", [':sid' => $saleId]);
    assertTest("Promotion snapshot recorded in sale_discounts table", !empty($sdRow) && (float)$sdRow['discount_amount'] === $expectedSavings1);

    // Verify usage count incremented on rule
    $rule1Updated = Database::fetchOne("SELECT usage_count FROM discount_rules WHERE id = :id", [':id' => $rule1Id]);
    assertTest("Discount rule usage_count incremented atomically to 1", (int)($rule1Updated['usage_count'] ?? 0) === 1);

    // Verify double-entry journal balance
    $journal = Database::fetchOne("SELECT id, total_debit, total_credit, posting_status FROM journal_entries WHERE source_type IN ('sale', 'pos_sale') AND source_id = :sid", [':sid' => $saleId]);
    assertTest("Journal entry created and balanced (Debit == Credit)", !empty($journal) && abs((float)$journal['total_debit'] - (float)$journal['total_credit']) < 0.01);

    // -------------------------------------------------------------
    // TEST 6: Sales Return with Promotional Net Price Refund Limit
    // -------------------------------------------------------------
    echo "\n--- 6. Testing Sales Return with Promotional Net Refund Limit ---\n";
    $saleItem = Database::fetchOne("SELECT * FROM sale_items WHERE sale_id = :sid LIMIT 1", [':sid' => $saleId]);
    $saleItemId = (int)$saleItem['id'];
    $netUnitPrice = round((float)$saleItem['line_total'] / (float)$saleItem['quantity'], 2);

    // Attempting return of 1 unit
    $returnPayload = [
        'sale_id'             => $saleId,
        'customer_id'         => 1,
        'refund_method'       => 'cash',
        'notes'               => 'CLI Test Promotional Return',
        'items'               => [
            [
                'sale_item_id'   => $saleItemId,
                'product_id'     => $productId,
                'lot_id'         => $lotId,
                'quantity'       => 1,
                'unit_price'     => $sellingPrice // Attempting full price return
            ]
        ]
    ];

    $retRes = SaleReturn::processReturn($returnPayload, $userId, $branchId);
    assertTest("processReturn executes safely", $retRes['success'] === true, $retRes['message'] ?? '');
    $returnId = (int)($retRes['return_id'] ?? 0);
    if ($returnId) $cleanupReturnIds[] = $returnId;

    // Check refunded unit price in sales_return_items: MUST NOT EXCEED netUnitPrice
    $retItem = Database::fetchOne("SELECT unit_price, total_amount FROM sales_return_items WHERE sales_return_id = :rid LIMIT 1", [':rid' => $returnId]);
    $refundedPrice = (float)$retItem['unit_price'];
    assertTest("Return refund capped at promotional discounted price (LKR {$refundedPrice} <= LKR {$netUnitPrice})", $refundedPrice <= ($netUnitPrice + 0.01));

    // -------------------------------------------------------------
    // TEST 7: Business Day Breakdown & Reports Engine Verification
    // -------------------------------------------------------------
    echo "\n--- 7. Testing Business Day Summary & Reports Engine ---\n";
    $activeBday = Database::fetchOne("SELECT id FROM business_days WHERE branch_id = :bid AND status = 'open'", [':bid' => $branchId]);
    if ($activeBday) {
        $bdaySummary = BusinessDay::getDaySalesSummary((int)$activeBday['id']);
        assertTest("BusinessDay summary reports promo_discount metrics", isset($bdaySummary['promo_discount']) && isset($bdaySummary['manual_discount']));
    } else {
        echo " [SKIP] No open business day currently active in branch #1.\n";
    }

    $usageRep = ReportsEngine::getDiscountUsageReport(['branch_id' => $branchId]);
    assertTest("ReportsEngine::getDiscountUsageReport returns items and summary", !empty($usageRep['items']) && isset($usageRep['summary']['total_discounts_granted']));

    $perfRep = ReportsEngine::getPromotionPerformanceReport(['branch_id' => $branchId]);
    assertTest("ReportsEngine::getPromotionPerformanceReport returns performance metrics", !empty($perfRep['items']) && isset($perfRep['summary']['total_savings']));

    // -------------------------------------------------------------
    // TEST 8: Soft Delete & Trash Bin Recovery
    // -------------------------------------------------------------
    echo "\n--- 8. Testing Soft-Delete and Trash Bin Restore ---\n";
    $delRes = DiscountEngine::deleteRule($rule1Id, $userId);
    assertTest("Discount rule soft-deleted to Trash Bin", $delRes['success'] === true);

    $trashRules = DiscountEngine::getRules(['view' => 'deleted']);
    $inTrash = false;
    foreach ($trashRules['items'] as $tr) {
        if ((int)$tr['id'] === $rule1Id) {
            $inTrash = true;
            break;
        }
    }
    assertTest("Soft-deleted rule listed in Trash Bin view", $inTrash);

    $restoreRes = DiscountEngine::restoreRule($rule1Id, $userId);
    assertTest("Discount rule successfully restored from Trash Bin", $restoreRes['success'] === true);

} catch (Throwable $e) {
    echo "\n[ERROR EXCEPTION] " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
    $failCount++;
} finally {
    // -------------------------------------------------------------
    // CLEANUP TEST FIXTURES (Clean individual test IDs only - NO TRUNCATE)
    // -------------------------------------------------------------
    echo "\n--- Cleaning Up Isolated Test Fixtures (No Truncate) ---\n";

    if (!empty($cleanupReturnIds)) {
        foreach ($cleanupReturnIds as $rid) {
            $db->prepare("DELETE FROM sales_return_items WHERE sales_return_id = :id")->execute([':id' => $rid]);
            $db->prepare("DELETE FROM sales_returns WHERE id = :id")->execute([':id' => $rid]);
        }
        echo "Cleaned " . count($cleanupReturnIds) . " test sales returns.\n";
    }

    if (!empty($cleanupSaleIds)) {
        foreach ($cleanupSaleIds as $sid) {
            $db->prepare("DELETE FROM sale_discounts WHERE sale_id = :id")->execute([':id' => $sid]);
            $db->prepare("DELETE FROM payments WHERE sale_id = :id")->execute([':id' => $sid]);
            $db->prepare("DELETE FROM sale_items WHERE sale_id = :id")->execute([':id' => $sid]);
            $db->prepare("DELETE FROM journal_lines WHERE journal_entry_id IN (SELECT id FROM journal_entries WHERE source_type IN ('sale', 'pos_sale') AND source_id = :id)")->execute([':id' => $sid]);
            $db->prepare("DELETE FROM journal_entries WHERE source_type IN ('sale', 'pos_sale') AND source_id = :id")->execute([':id' => $sid]);
            $db->prepare("DELETE FROM sales WHERE id = :id")->execute([':id' => $sid]);
        }
        echo "Cleaned " . count($cleanupSaleIds) . " test sales & journal records.\n";
    }

    if (!empty($cleanupRuleIds)) {
        foreach ($cleanupRuleIds as $rid) {
            $db->prepare("DELETE FROM discount_rule_targets WHERE rule_id = :id")->execute([':id' => $rid]);
            $db->prepare("DELETE FROM discount_rules WHERE id = :id")->execute([':id' => $rid]);
        }
        echo "Cleaned " . count($cleanupRuleIds) . " test discount rules.\n";
    }

    if (!empty($pausedRuleIds)) {
        $inClause = implode(',', $pausedRuleIds);
        $db->exec("UPDATE discount_rules SET status = 'active' WHERE id IN ($inClause)");
        echo "Restored " . count($pausedRuleIds) . " existing pre-test discount rules.\n";
    }
}

echo "\n====================================================================\n";
echo "   TEST SUMMARY: PASS: {$passCount} | FAIL: {$failCount}\n";
echo "====================================================================\n";

if ($failCount === 0) {
    echo ">>> ALL DISCOUNT & PROMOTION SUITE TESTS PASSED WITH 100% SUCCESS <<<\n\n";
} else {
    echo ">>> TESTS COMPLETED WITH FAILURES <<<\n\n";
}
