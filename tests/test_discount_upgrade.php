<?php
/**
 * Curtiss POS - Discount System Upgrade Test Suite
 * Validates that items with inline item-wise discounts (or gift cards)
 * are excluded from receiving the total bill (invoice-level) discount.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Sale.php';
require_once __DIR__ . '/../core/DiscountEngine.php';
require_once __DIR__ . '/../core/Accounting.php';

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

echo "=== STARTING DISCOUNT SYSTEM UPGRADE TESTS ===\n\n";

// TEST 1: DiscountEngine Standard Cart - 1 Item with inline discount, 1 Item without (using non-promo products 3 and 8)
$cart1 = [
    [
        'product_id'     => 3,
        'quantity'       => 1,
        'unit_price'     => 1000.00,
        'original_price' => 1000.00,
        'discount_value' => '' // No inline discount
    ],
    [
        'product_id'     => 8,
        'quantity'       => 1,
        'unit_price'     => 2000.00,
        'original_price' => 2000.00,
        'discount_value' => '10%' // 10% inline discount -> LKR 200 disc, net 1800
    ]
];
$context1 = [
    'branch_id' => 1,
    'manual_invoice_discount' => '10%' // 10% total bill discount
];
$res1 = DiscountEngine::evaluateCart($cart1, $context1);

// Eligible subtotal is Item 1 only (1,000.00). 10% of 1,000.00 = 100.00 bill discount.
// Item 2 net: 1800.00. Item 1 net before bill disc: 1000.00.
// Final payable: 1000 - 100 + 1800 = 2700.00.
assertTest(
    abs($res1['final_payable'] - 2700.00) < 0.01,
    "DiscountEngine: Total bill discount excluded from item with inline discount",
    "Expected final payable 2700.00, got " . $res1['final_payable']
);
assertTest(
    abs($res1['total_manual_discount'] - 300.00) < 0.01,
    "DiscountEngine: Total savings is 200 (inline) + 100 (bill) = 300",
    "Expected total savings 300.00, got " . $res1['total_manual_discount']
);

// TEST 2: All items in cart have inline discount -> Bill discount must be 0
$cart2 = [
    [
        'product_id'     => 3,
        'quantity'       => 1,
        'unit_price'     => 1000.00,
        'original_price' => 1000.00,
        'discount_value' => '50' // Rs 50 inline discount -> net 950
    ],
    [
        'product_id'     => 8,
        'quantity'       => 1,
        'unit_price'     => 2000.00,
        'original_price' => 2000.00,
        'discount_value' => '10%' // 10% inline discount -> net 1800
    ]
];
$context2 = [
    'branch_id' => 1,
    'manual_invoice_discount' => '10%' // 10% total bill discount
];
$res2 = DiscountEngine::evaluateCart($cart2, $context2);
// Eligible subtotal is 0.00. Bill discount must be 0.00.
// Final payable: 950 + 1800 = 2750.00. Total savings: 50 + 200 = 250.00.
assertTest(
    abs($res2['final_payable'] - 2750.00) < 0.01,
    "DiscountEngine: Zero bill discount when all items have inline discounts",
    "Expected final payable 2750.00, got " . $res2['final_payable']
);
assertTest(
    abs($res2['total_manual_discount'] - 250.00) < 0.01,
    "DiscountEngine: Total savings reflects only inline discounts when no items are eligible",
    "Expected total savings 250.00, got " . $res2['total_manual_discount']
);

// TEST 3: Gift card item + normal item without discount
$cart3 = [
    [
        'product_id'     => 3,
        'quantity'       => 1,
        'unit_price'     => 1000.00,
        'original_price' => 1000.00,
        'discount_value' => '',
        'is_gift_card'   => 0
    ],
    [
        'product_id'      => 0,
        'quantity'        => 1,
        'unit_price'      => 5000.00,
        'original_price'  => 5000.00,
        'discount_value'  => '',
        'is_gift_card'    => 1,
        'is_gift_voucher' => true
    ]
];
$context3 = [
    'branch_id' => 1,
    'manual_invoice_discount' => '10%'
];
$res3 = DiscountEngine::evaluateCart($cart3, $context3);
// Eligible subtotal is 1,000.00. Bill discount = 100.00.
// Final payable = (1000 - 100) + 5000 = 5900.00.
assertTest(
    abs($res3['final_payable'] - 5900.00) < 0.01,
    "DiscountEngine: Gift card excluded from invoice discount",
    "Expected final payable 5900.00, got " . $res3['final_payable']
);

// TEST 4: Fixed amount total bill discount on mixed cart
$cart4 = [
    [
        'product_id'     => 3,
        'quantity'       => 1,
        'unit_price'     => 1000.00,
        'original_price' => 1000.00,
        'discount_value' => '' // Eligible for bill discount
    ],
    [
        'product_id'     => 8,
        'quantity'       => 1,
        'unit_price'     => 2000.00,
        'original_price' => 2000.00,
        'discount_value' => '200' // Inline discount 200 -> net 1800
    ]
];
$context4 = [
    'branch_id' => 1,
    'manual_invoice_discount' => '250' // Rs 250 fixed bill discount
];
$res4 = DiscountEngine::evaluateCart($cart4, $context4);
// Eligible subtotal is 1000.00. Fixed bill discount of 250 is applied to eligible subtotal.
// Final payable: (1000 - 250) + 1800 = 2550.00.
assertTest(
    abs($res4['final_payable'] - 2550.00) < 0.01,
    "DiscountEngine: Fixed amount bill discount applied correctly on eligible items",
    "Expected final payable 2550.00, got " . $res4['final_payable']
);

// TEST 4B: Cart containing ONLY a Gift Card must NEVER trigger or receive promotional discounts
$gcSystemProd = Sale::getOrCreateGiftCardSystemProduct(1);
$cartGCAlone = [
    [
        'product_id'      => (int)$gcSystemProd['product_id'],
        'lot_id'          => (int)$gcSystemProd['lot_id'],
        'quantity'        => 1,
        'unit_price'      => 1000.00,
        'original_price'  => 1000.00,
        'discount_value'  => '',
        'is_gift_card'    => 1,
        'is_gift_voucher' => true
    ]
];
$resGCAlone = DiscountEngine::evaluateCart($cartGCAlone, ['branch_id' => 1]);
assertTest(
    empty($resGCAlone['applied_promotions']) && (float)$resGCAlone['item_promo_discount'] === 0.00,
    "DiscountEngine: Gift card alone in cart never triggers or receives promotional discounts",
    "Got applied promos: " . json_encode($resGCAlone['applied_promotions'])
);
assertTest(
    abs($resGCAlone['final_payable'] - 1000.00) < 0.01,
    "DiscountEngine: Gift card alone final payable equals face value without discount",
    "Expected 1000.00, got " . $resGCAlone['final_payable']
);

// TEST 5: Sale::prepareDraftSale with mixed items
// Find active product and lots for testing
$db = Database::getInstance()->getConnection();
$activeLot = Database::fetchOne("
    SELECT pl.id AS lot_id, pl.product_id, pl.selling_price, pl.cost, pl.remaining_quantity
    FROM product_lots pl
    JOIN products p ON pl.product_id = p.id
    WHERE pl.branch_id = 1 AND pl.status = 'active' AND pl.remaining_quantity >= 5 AND p.status = 'active' AND p.deleted_at IS NULL
    LIMIT 1
");

if ($activeLot) {
    $pId = (int)$activeLot['product_id'];
    $lId = (int)$activeLot['lot_id'];
    $unitP = (float)$activeLot['selling_price'];

    // Create 2 line items using this lot: Line 1 with inline discount, Line 2 without
    $saleData = [
        'customer_id'    => 1,
        'branch_id'      => 1,
        'user_id'        => 1,
        'discount_value' => '10%', // 10% bill discount
        'items'          => [
            [
                'product_id'     => $pId,
                'lot_id'         => $lId,
                'quantity'       => 1,
                'unit_price'     => $unitP,
                'discount_value' => '10%' // Inline discount
            ],
            [
                'product_id'     => $pId,
                'lot_id'         => $lId,
                'quantity'       => 1,
                'unit_price'     => $unitP,
                'discount_value' => '' // No inline discount, eligible for bill discount
            ]
        ]
    ];

    $draftRes = Sale::prepareDraftSale($saleData, 1, 1);
    assertTest(
        !empty($draftRes['success']),
        "Sale::prepareDraftSale: Successfully prepared draft sale",
        $draftRes['message'] ?? ''
    );

    if (!empty($draftRes['success'])) {
        $testSaleId = (int)$draftRes['sale_id'];
        $createdSale = Database::fetchOne("SELECT subtotal, discount_amount, total_amount FROM sales WHERE id = :id", [':id' => $testSaleId]);

        // Line 1: unitP * 1 - 10% = 0.9 * unitP
        // Line 2: unitP * 1 - 0 = unitP
        // subtotal = 1.9 * unitP
        // Eligible subtotal = Line 2 only = unitP
        // Bill discount = 10% of unitP = 0.1 * unitP
        // Total amount = subtotal - bill discount = 1.8 * unitP
        $expectedLine1 = round($unitP * 0.9, 2);
        $expectedLine2 = round($unitP, 2);
        $expectedSubtotal = round($expectedLine1 + $expectedLine2, 2);
        $expectedBillDisc = round($unitP * 0.1, 2);
        $expectedTotal = round($expectedSubtotal - $expectedBillDisc, 2);

        assertTest(
            abs((float)$createdSale['discount_amount'] - $expectedBillDisc) < 0.02,
            "Sale::prepareDraftSale: Stored discount_amount matches bill discount on eligible item only",
            "Expected {$expectedBillDisc}, got {$createdSale['discount_amount']}"
        );
        assertTest(
            abs((float)$createdSale['total_amount'] - $expectedTotal) < 0.02,
            "Sale::prepareDraftSale: Stored total_amount matches net sum",
            "Expected {$expectedTotal}, got {$createdSale['total_amount']}"
        );

        // Clean up test draft sale
        $db->exec("DELETE FROM sale_items WHERE sale_id = {$testSaleId}");
        $db->exec("DELETE FROM sales WHERE id = {$testSaleId}");
    }
} else {
    echo "[SKIP] Skipped Sale::prepareDraftSale DB test - no active test lot found\n";
}

// Summary
echo "\n=========================================\n";
echo "TEST RESULTS: Total: {$testsRun} | Passed: {$passed} | Failed: {$failed}\n";
echo "=========================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
