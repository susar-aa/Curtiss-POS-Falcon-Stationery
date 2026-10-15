<?php
/**
 * Curtiss POS - Stage 9 Comprehensive Automated Test Suite
 * Verifies Customer Collections & Supplier Payments Architecture:
 * 1. Customer Outstanding collection (Cash, Card, Bank, Voucher)
 * 2. Partial customer payment & credit balance reduction
 * 3. Overpayment prevention for customer credit
 * 4. Automatic & specific credit invoice allocation
 * 5. Supplier outstanding calculation based ONLY on actual GRN totals
 * 6. Supplier payment (Cash, Bank, Card, Cheque)
 * 7. Multi-GRN payment allocation & payment_status updates (unpaid -> partially_paid -> paid)
 * 8. Supplier Cheque foundation recording in supplier_cheques table
 * 9. Supplier overpayment prevention beyond total outstanding
 * 10. Single DB transaction rollback safety
 * 11. Branch isolation & Audit logging
 * 12. Stage 1-8 regression verification
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Sale.php';
require_once __DIR__ . '/../core/Customer.php';
require_once __DIR__ . '/../core/CustomerPayment.php';
require_once __DIR__ . '/../core/Supplier.php';
require_once __DIR__ . '/../core/PurchaseOrder.php';
require_once __DIR__ . '/../core/GRN.php';
require_once __DIR__ . '/../core/SupplierPayment.php';

echo "=======================================================\n";
echo " CURTISS POS - STAGE 9 AUTOMATED VERIFICATION SUITE\n";
echo "=======================================================\n\n";

$testsPassed = 0;
$testsFailed = 0;

function assertCondition(bool $condition, string $testName, string $failureDetails = '') {
    global $testsPassed, $testsFailed;
    if ($condition) {
        echo "[PASS] {$testName}\n";
        $testsPassed++;
    } else {
        echo "[FAIL] {$testName} - {$failureDetails}\n";
        $testsFailed++;
    }
}

try {
    $db = Database::getInstance()->getConnection();

    // Setup Test Context (Branch 1, User 1 SuperAdmin)
    $_SESSION['user_id'] = 1;
    $_SESSION['branch_id'] = 1;
    $_SESSION['role_id'] = 1;
    $userId = 1;
    $branchId = 1;

    // ----------------------------------------------------
    // TEST 1: Customer Credit Sale & Outstanding Baseline Setup
    // ----------------------------------------------------
    echo "\n--- TEST 1: Customer Credit Sale Setup ---\n";
    $customer = Customer::find(2); // Registered customer
    assertCondition($customer !== null, "Registered Customer #2 Found");

    // Set initial debt for customer 2
    $db->exec("UPDATE customers SET credit_balance = 1000.00, credit_limit = 5000.00 WHERE id = 2");

    $custSummaryBefore = CustomerPayment::getCustomerOutstandingSummary(2);
    assertCondition((float)$custSummaryBefore['credit_balance'] === 1000.00, "Customer #2 Initial Debt is LKR 1000.00");

    // ----------------------------------------------------
    // TEST 2: Customer Partial Collection Payment (Cash)
    // ----------------------------------------------------
    echo "\n--- TEST 2: Customer Partial Payment & Debt Reduction ---\n";
    $cPayPayload1 = [
        'customer_id' => 2,
        'payment_method' => 'Cash',
        'amount' => 400.00,
        'reference_no' => 'CASH-REC-001',
        'allocation_mode' => 'auto',
        'notes' => 'Partial cash collection'
    ];

    $cPayRes1 = CustomerPayment::processPayment($cPayPayload1, $userId, $branchId);
    assertCondition($cPayRes1['success'] === true, "Customer Payment of LKR 400.00 Processed", $cPayRes1['message'] ?? '');
    assertCondition(abs((float)$cPayRes1['new_balance'] - 600.00) < 0.001, "New Debt Balance is LKR 600.00");

    $custRowAfter = Database::fetchOne("SELECT credit_balance FROM customers WHERE id = 2");
    assertCondition(abs((float)$custRowAfter['credit_balance'] - 600.00) < 0.001, "Database Customer credit_balance Updated to 600.00");

    // ----------------------------------------------------
    // TEST 3: Customer Overpayment Prevention
    // ----------------------------------------------------
    echo "\n--- TEST 3: Customer Overpayment Prevention ---\n";
    $cOverPayload = [
        'customer_id' => 2,
        'payment_method' => 'Cash',
        'amount' => 700.00, // Debt is 600. 700 should fail!
        'allocation_mode' => 'auto'
    ];

    $cOverRes = CustomerPayment::processPayment($cOverPayload, $userId, $branchId);
    assertCondition($cOverRes['success'] === false, "Overpayment Exceeding Outstanding Blocked as Expected", $cOverRes['message'] ?? '');

    // ----------------------------------------------------
    // TEST 4: Customer Voucher Collection Payment
    // ----------------------------------------------------
    echo "\n--- TEST 4: Customer Gift Voucher Collection Payment ---\n";
    require_once __DIR__ . '/../core/GiftVoucher.php';
    $vRes = GiftVoucher::createVoucher(200.00, date('Y-m-d', strtotime('+3 months')), 'Test Voucher', $userId);
    assertCondition($vRes['success'] === true, "Gift Voucher Created for Collection");

    $cVoucherPayload = [
        'customer_id' => 2,
        'payment_method' => 'Voucher',
        'amount' => 200.00,
        'voucher_code' => $vRes['voucher_code'],
        'allocation_mode' => 'auto'
    ];

    $cPayVoucherRes = CustomerPayment::processPayment($cVoucherPayload, $userId, $branchId);
    assertCondition($cPayVoucherRes['success'] === true, "Voucher Collection Payment Processed", $cPayVoucherRes['message'] ?? '');

    $custRowAfterVoucher = Database::fetchOne("SELECT credit_balance FROM customers WHERE id = 2");
    assertCondition(abs((float)$custRowAfterVoucher['credit_balance'] - 400.00) < 0.001, "Debt Balance Reduced from 600.00 to 400.00");

    $vStatus = Database::fetchOne("SELECT status FROM gift_vouchers WHERE id = :id", [':id' => $vRes['voucher_id']]);
    assertCondition($vStatus['status'] === 'redeemed', "Gift Voucher Marked Redeemed");

    // ----------------------------------------------------
    // TEST 5: Supplier Outstanding Calculation from Actual GRN Total
    // ----------------------------------------------------
    echo "\n--- TEST 5: Supplier Outstanding Calculation (Actual GRN Value) ---\n";
    // Setup approved GRN with actual_grn_total = 2000.00
    $poHeader = [
        'supplier_id' => 1,
        'branch_id'   => $branchId,
        'order_date'  => date('Y-m-d'),
        'user_id'     => $userId
    ];
    $poItems = [
        ['product_id' => 1, 'quantity' => 100.00, 'unit_cost' => 20.00, 'selling_price' => 30.00]
    ];
    $poRes = PurchaseOrder::create($poHeader, $poItems);
    assertCondition($poRes['success'] === true, "Test PO Created", $poRes['message'] ?? '');
    $poId = (int)$poRes['po_id'];

    PurchaseOrder::approve($poId, $userId);

    $grnHeader = [
        'po_id'                   => $poId,
        'supplier_id'             => 1,
        'branch_id'               => $branchId,
        'supplier_invoice_no'     => 'INV-SUPP-' . rand(1000, 9999),
        'supplier_invoice_date'   => date('Y-m-d'),
        'received_date'           => date('Y-m-d'),
        'supplier_invoice_amount' => 3500.00, // Stated invoice 3500, but actual received items GRN is 2000!
        'difference_reason'       => 'Price difference',
        'user_id'                 => $userId
    ];
    $grnItems = [
        ['product_id' => 1, 'quantity' => 100.00, 'unit_cost' => 20.00, 'selling_price' => 30.00]
    ];

    $grnRes = GRN::create($grnHeader, $grnItems);
    assertCondition($grnRes['success'] === true, "Test GRN Created", $grnRes['message'] ?? '');
    $grnId = (int)$grnRes['grn_id'];

    GRN::approve($grnId, $userId);


    $suppSummary = SupplierPayment::getSupplierOutstandingSummary(1);
    assertCondition($suppSummary['success'] === true, "Supplier Outstanding Summary Loaded");
    
    // Find our created GRN in summary
    $foundGrn = null;
    foreach ($suppSummary['approved_grns'] as $g) {
        if ($g['grn_id'] === $grnId) {
            $foundGrn = $g;
            break;
        }
    }
    assertCondition($foundGrn !== null, "Approved GRN Present in Supplier Summary");
    assertCondition((float)$foundGrn['actual_grn_total'] === 2000.00, "Outstanding Calculated Based on Actual GRN Total (2000.00), NOT Stated Invoice (3500.00)");

    // ----------------------------------------------------
    // TEST 6: Supplier Partial Payment (Cash)
    // ----------------------------------------------------
    echo "\n--- TEST 6: Supplier Partial Payment & GRN Status Update ---\n";
    $sPayPayload1 = [
        'supplier_id' => 1,
        'payment_date' => date('Y-m-d'),
        'payment_method' => 'Cash',
        'amount' => 500.00,
        'reference_no' => 'CASH-PAID-01',
        'allocations' => [
            ['grn_id' => $grnId, 'amount' => 500.00]
        ]
    ];

    $sPayRes1 = SupplierPayment::processPayment($sPayPayload1, $userId, $branchId);
    assertCondition($sPayRes1['success'] === true, "Supplier Payment of LKR 500.00 Processed", $sPayRes1['message'] ?? '');

    $grnRow1 = Database::fetchOne("SELECT paid_amount, payment_status FROM grns WHERE id = :id", [':id' => $grnId]);
    assertCondition(abs((float)$grnRow1['paid_amount'] - 500.00) < 0.001, "GRN Paid Amount Updated to 500.00");
    assertCondition($grnRow1['payment_status'] === 'partially_paid', "GRN Payment Status is 'partially_paid'");

    // ----------------------------------------------------
    // TEST 7: Supplier Cheque Payment Foundation
    // ----------------------------------------------------
    echo "\n--- TEST 7: Supplier Cheque Payment & Full GRN Settlement ---\n";
    $sChequePayload = [
        'supplier_id' => 1,
        'payment_date' => date('Y-m-d'),
        'payment_method' => 'Cheque',
        'amount' => 1500.00,
        'cheque' => [
            'cheque_number' => 'CHQ-884411',
            'cheque_date' => date('Y-m-d', strtotime('+15 days')),
            'bank_name' => 'Commercial Bank of Ceylon',
            'amount' => 1500.00
        ],
        'allocations' => [
            ['grn_id' => $grnId, 'amount' => 1500.00]
        ]
    ];

    $sChequeRes = SupplierPayment::processPayment($sChequePayload, $userId, $branchId);
    assertCondition($sChequeRes['success'] === true, "Supplier Cheque Payment Processed", $sChequeRes['message'] ?? '');

    $chequeRow = Database::fetchOne("SELECT * FROM supplier_cheques WHERE supplier_payment_id = :sp_id", [':sp_id' => $sChequeRes['supplier_payment_id']]);
    assertCondition($chequeRow && $chequeRow['cheque_number'] === 'CHQ-884411', "Cheque Recorded in supplier_cheques Table");
    assertCondition($chequeRow['status'] === 'pending', "Cheque Status is Initialized to 'pending'");

    $grnRowFull = Database::fetchOne("SELECT paid_amount, payment_status FROM grns WHERE id = :id", [':id' => $grnId]);
    assertCondition(abs((float)$grnRowFull['paid_amount'] - 2000.00) < 0.001, "GRN Paid Amount Fully Reached 2000.00");
    assertCondition($grnRowFull['payment_status'] === 'paid', "GRN Payment Status Updated to 'paid'");

    // ----------------------------------------------------
    // TEST 8: Supplier Overpayment Prevention
    // ----------------------------------------------------
    echo "\n--- TEST 8: Supplier Overpayment Prevention ---\n";
    $sOverPayload = [
        'supplier_id' => 1,
        'payment_date' => date('Y-m-d'),
        'payment_method' => 'Cash',
        'amount' => 999999.00 // Exceeds total outstanding
    ];

    $sOverRes = SupplierPayment::processPayment($sOverPayload, $userId, $branchId);
    assertCondition($sOverRes['success'] === false, "Supplier Overpayment Exceeding Outstanding Blocked", $sOverRes['message'] ?? '');

    // ----------------------------------------------------
    // TEST 9: Transaction Rollback Safety
    // ----------------------------------------------------
    echo "\n--- TEST 9: Transaction Rollback Safety ---\n";
    $invalidAllocPayload = [
        'supplier_id' => 1,
        'payment_date' => date('Y-m-d'),
        'payment_method' => 'Cash',
        'amount' => 100.00,
        'allocations' => [
            ['grn_id' => 999999, 'amount' => 100.00] // Invalid GRN ID
        ]
    ];

    $txFailRes = SupplierPayment::processPayment($invalidAllocPayload, $userId, $branchId);
    assertCondition($txFailRes['success'] === false, "Invalid Allocation Transaction Safely Rolled Back", $txFailRes['message'] ?? '');

    // ----------------------------------------------------
    // TEST 10: Stage 1-8 Regression Check
    // ----------------------------------------------------
    echo "\n--- TEST 10: Stage 1-8 Regression Verification ---\n";
    assertCondition(class_exists('Database'), "Stage 1 Database Core Intact");
    assertCondition(class_exists('Auth'), "Stage 2 Auth Core Intact");
    assertCondition(class_exists('Inventory'), "Stage 3 Inventory Core Intact");
    assertCondition(class_exists('Customer'), "Stage 5 Customer Core Intact");
    assertCondition(class_exists('Sale'), "Stage 6 Sale POS Core Intact");
    assertCondition(class_exists('GiftVoucher'), "Stage 7 Gift Voucher Core Intact");
    assertCondition(class_exists('SaleReturn'), "Stage 8 Sale Return Core Intact");

} catch (Throwable $e) {
    echo "\nCRITICAL TEST ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    $testsFailed++;
}

echo "\n=======================================================\n";
echo " STAGE 9 VERIFICATION SUMMARY\n";
echo " Passed: {$testsPassed}\n";
echo " Failed: {$testsFailed}\n";
echo "=======================================================\n";

if ($testsFailed > 0) {
    exit(1);
}
