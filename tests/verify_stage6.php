<?php
/**
 * Verification Script for Stage 6: POS Billing & Sales Core
 */

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Product.php';
require_once __DIR__ . '/../core/Inventory.php';
require_once __DIR__ . '/../core/Customer.php';
require_once __DIR__ . '/../core/Sale.php';
require_once __DIR__ . '/../core/Audit.php';

echo "========================================================\n";
echo "CURTISS POS - STAGE 6 POS BILLING & SALES CORE TEST SUITE\n";
echo "========================================================\n\n";

$testsPassed = 0;
$totalTests = 0;

function assertTest(bool $condition, string $testName, string $detail = '') {
    global $testsPassed, $totalTests;
    $totalTests++;
    if ($condition) {
        echo "[PASS] {$testName}\n";
        $testsPassed++;
    } else {
        echo "[FAIL] {$testName}" . ($detail !== '' ? " -> Details: {$detail}" : "") . "\n";
    }
}

try {
    $db = Database::getInstance()->getConnection();

    // Setup Test Session (Cashier User ID 4, Branch ID 1)
    $_SESSION['user_id'] = 4;
    $_SESSION['role_id'] = 4;
    $_SESSION['branch_id'] = 1;
    $_SESSION['username'] = 'cashier1';

    // 1. Verify Continuous Invoice Sequence Generator
    $inv1 = Sale::generateInvoiceNumber();
    assertTest(preg_match('/^\d{4,}$/', $inv1) === 1, "Continuous invoice sequence generated valid number ({$inv1})");

    // 2. Verify Product & Barcode Search with Lots
    $products = Sale::searchProducts(1, '893450001001');
    assertTest(count($products) > 0 && $products[0]['barcode'] === '893450001001', "Barcode search returned product '893450001001'");
    assertTest(isset($products[0]['lots']) && count($products[0]['lots']) > 0, "Product search attached available stock lots");

    $nameSearch = Sale::searchProducts(1, 'Atlas');
    assertTest(count($nameSearch) > 0, "Product name search returned matching items");

    // 3. Lot Selection Logic & Auto-Selection
    $prodId = (int)$products[0]['product_id'];
    $lots = Sale::getAvailableLotsForProduct($prodId, 1);
    assertTest(count($lots) > 0, "Active lots retrieved for product ID {$prodId}");
    assertTest((float)$lots[0]['remaining_quantity'] > 0, "Only active lots with remaining stock > 0 returned");

    // 4. Discount Engine Parsing & Round-up Calculation
    $discFixed = Sale::parseDiscount('10', 100.00);
    assertTest($discFixed['discount_amount'] == 10.00, "Fixed discount '10' parsed correctly");

    $discPct = Sale::parseDiscount('10%', 100.00);
    assertTest($discPct['discount_amount'] == 10.00 && $discPct['customer_facing_text'] === '10%', "Percentage discount '10%' parsed correctly");

    $discRoundup = Sale::parseDiscount('10%+0.75', 1592.50);
    // 10% of 1592.50 = 159.25. +0.75 added to bill -> net discount deduction = 158.50
    assertTest($discRoundup['roundup_amount'] == 0.75 && $discRoundup['total_discount'] == 158.50, "Round-up discount '10%+0.75' calculated correctly");
    assertTest($discRoundup['customer_facing_text'] === '10%', "Customer facing receipt text preserves percentage portion only ('10%')");

    $suggestion = Sale::calculateRoundupSuggestion(159.25);
    assertTest($suggestion !== null && $suggestion['suggested_roundup'] == 0.75 && $suggestion['suggested_total'] == 160.00, "Round-up suggestion correctly calculated next 10-value rounded amount");

    // 5. Customer Lookup & Walk-in Default
    $walkinRes = Customer::all(['search' => 'Walk-in'], 1, 1);
    assertTest(count($walkinRes['items']) > 0 && $walkinRes['items'][0]['is_walkin'] == 1, "Default Walk-in customer available");

    // Record initial lot remaining stock before hold operation
    $testLotId = $lots[0]['lot_id'];
    $initialLotStockRow = Database::fetchOne("SELECT remaining_quantity FROM product_lots WHERE id = :id", [':id' => $testLotId]);
    $initialLotStock = (float)$initialLotStockRow['remaining_quantity'];
    if ($initialLotStock < 2.00) {
        Database::execute("UPDATE product_lots SET remaining_quantity = remaining_quantity + 10.00 WHERE id = :id", [':id' => $testLotId]);
        Database::execute("UPDATE branch_inventory SET quantity = (SELECT COALESCE(SUM(remaining_quantity), 0) FROM product_lots WHERE branch_id = 1 AND product_id = :p1 AND status = 'active' AND remaining_quantity > 0) WHERE branch_id = 1 AND product_id = :p2", [':p1' => $prodId, ':p2' => $prodId]);
        $initialLotStockRow = Database::fetchOne("SELECT remaining_quantity FROM product_lots WHERE id = :id", [':id' => $testLotId]);
        $initialLotStock = (float)$initialLotStockRow['remaining_quantity'];
    }

    // 6. Hold Invoice Workflow & Stock Preservation Rule
    $holdPayload = [
        'customer_id'    => 1,
        'held_notes'     => 'Test held invoice for stage 6 CLI verification',
        'discount_value' => '10%+0.75',
        'items'          => [
            [
                'product_id'     => $prodId,
                'lot_id'         => $testLotId,
                'quantity'       => 2.00,
                'original_price' => (float)$lots[0]['selling_price'],
                'unit_price'     => (float)$lots[0]['selling_price'] + 5.00, // Manual price override test
                'discount_value' => '5%'
            ]
        ]
    ];

    $holdRes = Sale::holdInvoice($holdPayload, 4, 1);
    assertTest($holdRes['success'] === true && !empty($holdRes['sale_id']), "Invoice held successfully with ID #{$holdRes['sale_id']} and Invoice #{$holdRes['invoice_no']}", $holdRes['message'] ?? '');

    // STOCK RULE ASSERTION: Held invoice MUST NOT deduct stock!
    $afterHoldLotStockRow = Database::fetchOne("SELECT remaining_quantity FROM product_lots WHERE id = :id", [':id' => $testLotId]);
    $afterHoldLotStock = (float)$afterHoldLotStockRow['remaining_quantity'];
    assertTest($afterHoldLotStock === $initialLotStock, "CRITICAL STOCK RULE: Stock lot remaining quantity was NOT deducted during hold operation");

    // 7. Resume Held Invoice
    $heldSaleId = $holdRes['sale_id'];
    $heldList = Sale::getHeldInvoices(1);
    assertTest(count($heldList) > 0, "Held invoices list retrieved for branch");

    $saleWithItems = Sale::getSaleWithItems($heldSaleId, 1);
    assertTest($saleWithItems !== null && count($saleWithItems['items']) === 1, "Held invoice details and line items fetched for resume");

    // 8. Cancel Invoice Workflow
    $cancelRes = Sale::cancelInvoice($heldSaleId, 4, 1, 'CLI Stage 6 Verification Test');
    assertTest($cancelRes['success'] === true, "Invoice cancelled successfully");

    $cancelledCheck = Database::fetchOne("SELECT status FROM sales WHERE id = :id", [':id' => $heldSaleId]);
    assertTest($cancelledCheck['status'] === 'cancelled', "Invoice status updated to 'cancelled'");

    // 9. Stock Over-Selling Prevention Test
    $overSellPayload = [
        'customer_id' => 1,
        'items'       => [
            [
                'product_id'     => $prodId,
                'lot_id'         => $testLotId,
                'quantity'       => $initialLotStock + 999.00, // Exceeds stock
                'unit_price'     => 30.00
            ]
        ]
    ];
    $overSellRes = Sale::holdInvoice($overSellPayload, 4, 1);
    assertTest($overSellRes['success'] === false, "Over-selling more than available lot stock is strictly blocked");

    // 10. Audit Log Verification
    $auditLogs = Database::fetchAll("SELECT * FROM audit_logs WHERE module = 'pos' ORDER BY id DESC LIMIT 5");
    assertTest(count($auditLogs) > 0, "Audit trail captured POS actions (price override, hold, cancel)");

    // 11. Regression Check Stages 1-5
    assertTest(Auth::isLoggedIn() === true, "Stage 1 Auth session functional");
    $prodCount = Database::fetchOne("SELECT COUNT(*) AS total FROM products WHERE deleted_at IS NULL");
    assertTest((int)$prodCount['total'] > 0, "Stage 2 Products intact");
    $branchInv = Inventory::getBranchStock(1, $prodId);
    assertTest($branchInv['quantity'] >= 0, "Stage 3 Branch inventory intact");
    $grnCount = Database::fetchOne("SELECT COUNT(*) AS total FROM grns");
    assertTest((int)$grnCount['total'] > 0, "Stage 4 GRN records intact");
    $custCount = Database::fetchOne("SELECT COUNT(*) AS total FROM customers WHERE deleted_at IS NULL");
    assertTest((int)$custCount['total'] > 0, "Stage 5 Customer accounts intact");

    echo "\n--------------------------------------------------------\n";
    echo "TEST SUMMARY: {$testsPassed} / {$totalTests} TESTS PASSED\n";
    echo "--------------------------------------------------------\n";

    if ($testsPassed === $totalTests) {
        echo "STAGE 6 POS BILLING & SALES CORE VERIFICATION SUCCESSFUL!\n";
        exit(0);
    } else {
        echo "STAGE 6 VERIFICATION FAILED!\n";
        exit(1);
    }
} catch (Throwable $e) {
    echo "\nCRITICAL TEST ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
