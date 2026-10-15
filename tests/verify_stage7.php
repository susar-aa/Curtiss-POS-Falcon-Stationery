<?php
/**
 * Automated Verification Script for Stage 7: Payments, Sale Finalization & Gift Vouchers
 */

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Product.php';
require_once __DIR__ . '/../core/Inventory.php';
require_once __DIR__ . '/../core/Customer.php';
require_once __DIR__ . '/../core/Sale.php';
require_once __DIR__ . '/../core/GiftVoucher.php';
require_once __DIR__ . '/../core/Audit.php';

echo "========================================================\n";
echo "CURTISS POS - STAGE 7 PAYMENTS & FINALIZATION TEST SUITE\n";
echo "========================================================\n\n";

$testsPassed = 0;
$totalTests = 0;

function assertTest(bool $condition, string $testName) {
    global $testsPassed, $totalTests;
    $totalTests++;
    if ($condition) {
        echo "[PASS] {$testName}\n";
        $testsPassed++;
    } else {
        echo "[FAIL] {$testName}\n";
    }
}

try {
    $db = Database::getInstance()->getConnection();

    // Setup Test Session (Cashier User ID 4, Branch ID 1)
    $_SESSION['user_id'] = 4;
    $_SESSION['role_id'] = 4;
    $_SESSION['branch_id'] = 1;
    $_SESSION['username'] = 'cashier1';

    // 1. Gift Voucher Creation & Validation
    $vRes = GiftVoucher::createVoucher(500.00, date('Y-m-d', strtotime('+30 days')), 'CLI Stage 7 Test Voucher', 1);
    assertTest($vRes['success'] === true, "Gift voucher issued successfully: {$vRes['voucher_code']}");
    $vCode = $vRes['voucher_code'];

    $vVal = GiftVoucher::validateVoucher($vCode, 1000.00);
    assertTest($vVal['valid'] === true && $vVal['voucher']['amount'] == 500.00, "Active gift voucher validated successfully for payable total 1000");

    // Test voucher overpayment prevention (Voucher 500 on invoice payable 300)
    $vValOver = GiftVoucher::validateVoucher($vCode, 300.00);
    assertTest($vValOver['valid'] === false, "Voucher overpayment / partial consumption prevented when voucher > invoice total");

    // 2. Setup Test Customer for Credit Payments
    $cRes = Customer::create([
        'name'         => 'Stage7 Test Customer',
        'phone'        => '0779998877',
        'credit_limit' => 50000.00
    ]);
    $testCustId = $cRes['id'];
    assertTest($testCustId > 0, "Registered customer created for credit payment tests");

    // Fetch Product & Lot for Sale Finalization
    $stmtLot1 = Database::getInstance()->getConnection()->query("SELECT * FROM product_lots WHERE branch_id = 1 AND remaining_quantity >= 5 AND status = 'active' ORDER BY id DESC LIMIT 1");
    $lot1 = $stmtLot1->fetch(PDO::FETCH_ASSOC);
    $testProdId = (int)$lot1['product_id'];
    $testLotId = (int)$lot1['id'];
    $testPrice = (float)$lot1['selling_price'];
    
    // Capture pre-sale stock levels
    $preLotRow = Database::fetchOne("SELECT remaining_quantity FROM product_lots WHERE id = :id", [':id' => $testLotId]);
    $preLotStock = (float)$preLotRow['remaining_quantity'];

    $preBiRow = Database::fetchOne("SELECT quantity FROM branch_inventory WHERE branch_id = 1 AND product_id = :pid", [':pid' => $testProdId]);
    $preBiStock = (float)$preBiRow['quantity'];

    $totalSale1 = $testPrice * 2.00;
    // 3. Prepare Draft Sale #1
    $draft1Payload = [
        'customer_id' => $testCustId,
        'items'       => [
            [
                'product_id'     => $testProdId,
                'lot_id'         => $testLotId,
                'quantity'       => 2.00,
                'original_price' => $testPrice,
                'unit_price'     => $testPrice
            ]
        ]
    ];
    $draft1 = Sale::prepareDraftSale($draft1Payload, 4, 1);
    assertTest(($draft1['success'] ?? false) === true, "Sale draft #1 prepared (Total: LKR " . ($draft1['total_amount'] ?? '0') . ")");
    $saleId1 = (int)($draft1['sale_id'] ?? 0);

    // 4. Test Walk-in Credit Rejection
    $walkinCreditPayload = [
        ['payment_method' => 'Credit', 'amount' => $totalSale1]
    ];
    // Temporarily point draft1 to walk-in customer ID 1
    Database::execute("UPDATE sales SET customer_id = 1 WHERE id = :id", [':id' => $saleId1]);
    $walkinCreditRes = Sale::finalizeSale($saleId1, $walkinCreditPayload, 4, 1);
    assertTest($walkinCreditRes['success'] === false, "Walk-in customer credit payment strictly rejected");

    // Restore registered customer ID for draft #1
    Database::execute("UPDATE sales SET customer_id = :cid WHERE id = :id", [':cid' => $testCustId, ':id' => $saleId1]);

    // 5. Test Split Payment Finalization
    $creditPart = round($totalSale1 * 0.33, 2);
    $cashPart   = round($totalSale1 - $creditPart, 2);
    $splitPayments = [
        ['payment_method' => 'Cash', 'amount' => $cashPart],
        ['payment_method' => 'Credit', 'amount' => $creditPart]
    ];

    $preCustRow = Database::fetchOne("SELECT credit_balance FROM customers WHERE id = :id", [':id' => $testCustId]);
    $preCreditBal = (float)$preCustRow['credit_balance'];

    $finalRes1 = Sale::finalizeSale($saleId1, $splitPayments, 4, 1);
    assertTest($finalRes1['success'] === true, "Split payment (Cash + Credit) finalized successfully");

    // 6. Verify Atomic Stock Deduction & Ledger Posting
    $postLotRow = Database::fetchOne("SELECT remaining_quantity FROM product_lots WHERE id = :id", [':id' => $testLotId]);
    $postLotStock = (float)$postLotRow['remaining_quantity'];
    assertTest($postLotStock === ($preLotStock - 2.00), "Atomic Lot Stock deducted by exactly 2.00 items");

    $postBiRow = Database::fetchOne("SELECT quantity FROM branch_inventory WHERE branch_id = 1 AND product_id = :pid", [':pid' => $testProdId]);
    $postBiStock = (float)$postBiRow['quantity'];
    assertTest($postBiStock === ($preBiStock - 2.00), "Branch inventory stock updated by -2.00");

    $ledgerRow = Database::fetchOne("
        SELECT * FROM stock_ledger WHERE reference_no = :ref AND movement_type = 'sale' ORDER BY id DESC LIMIT 1
    ", [':ref' => $finalRes1['invoice_no']]);
    assertTest($ledgerRow !== null && (float)$ledgerRow['quantity'] == -2.00, "Stock movement ledger recorded 'sale' entry (-2.00)");

    // 7. Verify Customer Credit Ledger & Balance Increase
    $postCustRow = Database::fetchOne("SELECT credit_balance FROM customers WHERE id = :id", [':id' => $testCustId]);
    $postCreditBal = (float)$postCustRow['credit_balance'];
    assertTest(abs($postCreditBal - ($preCreditBal + $creditPart)) < 0.01, "Customer outstanding credit balance increased by credit portion (+LKR {$creditPart})");

    $cclRow = Database::fetchOne("
        SELECT * FROM customer_credit_ledger WHERE customer_id = :cid ORDER BY id DESC LIMIT 1
    ", [':cid' => $testCustId]);
    assertTest($cclRow !== null && abs((float)$cclRow['amount_due'] - $creditPart) < 0.01, "Customer credit ledger recorded credit sale entry");

    // 8. Test Double-Finalization Prevention
    $doubleRes = Sale::finalizeSale($saleId1, $splitPayments, 4, 1);
    assertTest($doubleRes['success'] === false, "Double-finalization / double stock deduction strictly prevented");

    // 9. Prepare Draft Sale #2 for Gift Voucher & Change Calculation Test
    $stmtLotBook = Database::getInstance()->getConnection()->query("SELECT * FROM product_lots WHERE branch_id = 1 AND remaining_quantity >= 1 AND status = 'active' ORDER BY id ASC LIMIT 1");
    $lotBook = $stmtLotBook->fetch(PDO::FETCH_ASSOC);
    $bookProdId = (int)$lotBook['product_id'];
    $bookLotId = (int)$lotBook['id'];
    $bookPrice = (float)$lotBook['selling_price'];

    $draft2Payload = [
        'customer_id' => $testCustId,
        'items'       => [
            [
                'product_id'     => $bookProdId,
                'lot_id'         => $bookLotId,
                'quantity'       => 1.00,
                'original_price' => $bookPrice,
                'unit_price'     => $bookPrice
            ]
        ]
    ];
    $draft2 = Sale::prepareDraftSale($draft2Payload, 4, 1);
    $saleId2 = $draft2['sale_id'];

    // 10. Issue Voucher & Finalize Sale #2 with Gift Voucher
    $vRes2 = GiftVoucher::createVoucher($bookPrice, date('Y-m-d', strtotime('+30 days')), 'Voucher Test');
    $vCode2 = $vRes2['voucher_code'];

    $voucherPayments = [
        ['payment_method' => 'Voucher', 'amount' => $bookPrice, 'reference_no' => $vCode2]
    ];
    $finalRes2 = Sale::finalizeSale($saleId2, $voucherPayments, 4, 1);
    assertTest($finalRes2['success'] === true, "Sale #2 finalized with 100% Gift Voucher redemption");

    // Verify Voucher status updated to 'redeemed'
    $vRedeemedRow = Database::fetchOne("SELECT status, redeemed_sale_id FROM gift_vouchers WHERE voucher_code = :code", [':code' => $vCode2]);
    assertTest($vRedeemedRow['status'] === 'redeemed' && $vRedeemedRow['redeemed_sale_id'] == $saleId2, "Gift Voucher status updated to 'redeemed'");

    // Verify redeemed voucher cannot be used again
    $vValRedeemed = GiftVoucher::validateVoucher($vCode2, 100.00);
    assertTest($vValRedeemed['valid'] === false, "Redeemed Gift Voucher cannot be reused");

    // 11. Test Receipt Data Generation & Reprinting
    $receiptData = Sale::getReceiptData($saleId2);
    assertTest($receiptData !== null && count($receiptData['items']) === 1 && count($receiptData['payments']) === 1, "88mm Thermal receipt data retrieved successfully");

    // 12. Regression Check Stages 1-6
    assertTest(Auth::isLoggedIn() === true, "Stage 1 Auth session functional");
    assertTest(count(Product::getList()['items']) > 0, "Stage 2 Product master intact");
    assertTest(count(Inventory::getInventoryList(1)['items']) > 0, "Stage 3 Inventory lots intact");
    assertTest((int)Database::fetchOne("SELECT COUNT(*) AS total FROM grns")['total'] > 0, "Stage 4 GRN records intact");
    assertTest((int)Database::fetchOne("SELECT COUNT(*) AS total FROM customers")['total'] > 0, "Stage 5 Customer accounts intact");
    assertTest((int)Database::fetchOne("SELECT COUNT(*) AS total FROM sales")['total'] > 0, "Stage 6 Sales core intact");

    echo "\n--------------------------------------------------------\n";
    echo "TEST SUMMARY: {$testsPassed} / {$totalTests} TESTS PASSED\n";
    echo "--------------------------------------------------------\n";

    if ($testsPassed === $totalTests) {
        echo "STAGE 7 PAYMENTS, SALE FINALIZATION & GIFT VOUCHERS VERIFICATION SUCCESSFUL!\n";
        exit(0);
    } else {
        echo "STAGE 7 VERIFICATION FAILED!\n";
        exit(1);
    }
} catch (Throwable $e) {
    echo "\nCRITICAL TEST ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
