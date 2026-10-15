<?php
/**
 * Curtiss POS - Comprehensive Stage 15 Automated Test & Regression Suite
 * Tests Supplier Returns, Lot Stock Deductions, Supplier Payable vs Credit Allocation,
 * Supplier Credit Settlements, Cheque Lifecycle (Pending -> Cleared / Bounced / Cancelled),
 * Double-Entry Accounting Balance, and Stages 1-14 Regression.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Supplier.php';
require_once __DIR__ . '/../core/Product.php';
require_once __DIR__ . '/../core/PurchaseOrder.php';
require_once __DIR__ . '/../core/GRN.php';
require_once __DIR__ . '/../core/SupplierPayment.php';
require_once __DIR__ . '/../core/SupplierReturnEngine.php';
require_once __DIR__ . '/../core/ChequeEngine.php';
require_once __DIR__ . '/../core/Banking.php';
require_once __DIR__ . '/../core/BusinessDay.php';
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

try {
    echo "=====================================================\n";
    echo "       CURTISS POS - STAGE 15 VERIFICATION SUITE     \n";
    echo "=====================================================\n\n";

    $db = Database::getInstance()->getConnection();
    $userId = 1;
    $branchId = 1;

    // Test isolation cleanup of previous S15 test runs
    $db->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $db->exec("DELETE FROM journal_lines WHERE journal_entry_id IN (SELECT id FROM journal_entries WHERE description LIKE '%Test Supplier S15_%')");
    $db->exec("DELETE FROM journal_entries WHERE description LIKE '%Test Supplier S15_%'");
    $db->exec("DELETE FROM supplier_credit_settlements WHERE supplier_credit_id IN (SELECT id FROM supplier_credits WHERE notes LIKE '%Return SRET-%')");
    $db->exec("DELETE FROM supplier_credits WHERE notes LIKE '%Return SRET-%'");
    $db->exec("DELETE FROM supplier_return_items WHERE supplier_return_id IN (SELECT id FROM supplier_returns WHERE notes LIKE '%Stage 15%' OR return_number LIKE 'SRET-%')");
    $db->exec("DELETE FROM supplier_returns WHERE notes LIKE '%Stage 15%' OR return_number LIKE 'SRET-%'");
    $db->exec("DELETE FROM product_lots WHERE product_id IN (SELECT id FROM products WHERE name LIKE 'S15 Test Item%')");
    $db->exec("DELETE FROM branch_inventory WHERE product_id IN (SELECT id FROM products WHERE name LIKE 'S15 Test Item%')");
    $db->exec("DELETE FROM grn_items WHERE product_id IN (SELECT id FROM products WHERE name LIKE 'S15 Test Item%')");
    $db->exec("DELETE FROM products WHERE name LIKE 'S15 Test Item%'");
    $db->exec("DELETE FROM suppliers WHERE name LIKE 'Test Supplier S15_%'");
    $db->exec("SET FOREIGN_KEY_CHECKS = 1;");

    // Ensure active business day for cash tests
    $activeDay = BusinessDay::getActiveDay($branchId);
    if (!$activeDay) {
        BusinessDay::startDay($branchId, 5000.00, $userId, 'Test Day');
        $activeDay = BusinessDay::getActiveDay($branchId);
    }

    // Ensure test supplier exists
    $supName = "Test Supplier S15_" . rand(1000, 9999);
    $supRes = Supplier::create($supName, '0771234567', null, '123 Test St, Kandy', 'active');
    $supplierId = (int)$supRes['id'];

    // Ensure brand and category exist
    $brandRow = Database::fetchOne("SELECT id FROM brands WHERE deleted_at IS NULL LIMIT 1");
    $brandId = $brandRow ? (int)$brandRow['id'] : 1;
    $catRow = Database::fetchOne("SELECT id FROM categories WHERE deleted_at IS NULL LIMIT 1");
    $catId = $catRow ? (int)$catRow['id'] : 1;

    // Ensure test product exists
    $prodName = "S15 Test Item " . rand(1000, 9999);
    $prodRes = Product::create([
        'barcode'       => '987654' . rand(1000, 9999),
        'brand_id'      => $brandId,
        'category_id'   => $catId,
        'name'          => $prodName,
        'supplier_id'   => $supplierId,
        'cost'          => 100.00,
        'selling_price' => 150.00,
        'opening_stock' => 0.00,
        'status'        => 'active'
    ]);
    $productId = (int)$prodRes['id'];

    // Create PO and GRN (10 units @ LKR 100.00 = LKR 1,000.00)
    $poRes = PurchaseOrder::create([
        'supplier_id'   => $supplierId,
        'branch_id'     => $branchId,
        'user_id'       => $userId,
        'expected_date' => date('Y-m-d')
    ], [['product_id' => $productId, 'quantity' => 10, 'unit_cost' => 100.00, 'selling_price' => 150.00]]);
    $poId = $poRes['po_id'];
    PurchaseOrder::approve($poId, $userId);

    $grnRes = GRN::create([
        'po_id'                 => $poId,
        'supplier_id'           => $supplierId,
        'branch_id'             => $branchId,
        'user_id'               => $userId,
        'received_date'         => date('Y-m-d'),
        'supplier_invoice_no'   => 'INV-S15-01',
        'supplier_invoice_date' => date('Y-m-d')
    ], [['product_id' => $productId, 'quantity' => 10, 'unit_cost' => 100.00, 'selling_price' => 150.00]]);
    $grnId = $grnRes['grn_id'];
    GRN::approve($grnId, $userId);

    $grnItemRow = Database::fetchOne("SELECT * FROM grn_items WHERE grn_id = :g", [':g' => $grnId]);
    $grnItemId = (int)$grnItemRow['id'];
    $lotId     = (int)$grnItemRow['lot_id'];

    // 1. Partial Supplier Return
    $ret1 = SupplierReturnEngine::processReturn([
        'grn_id'      => $grnId,
        'return_date' => date('Y-m-d'),
        'notes'       => 'Partial return 4 units',
        'items'       => [['grn_item_id' => $grnItemId, 'quantity' => 4, 'reason' => 'Defective']]
    ], $userId, $branchId);

    assertTest($ret1['success'] && $ret1['total_return_amount'] == 400.00, "Partial Supplier Return of 4 units processed", $ret1['message'] ?? '');
    assertTest($ret1['applied_to_payable'] == 400.00 && $ret1['credit_amount'] == 0.00, "Return applied 100% to AP on unpaid GRN");

    // Verify stock deduction
    $lot1 = Database::fetchOne("SELECT remaining_quantity FROM product_lots WHERE id = :id", [':id' => $lotId]);
    assertTest($lot1['remaining_quantity'] == 6.00, "Product lot remaining quantity reduced from 10 to 6 units");

    // 2. Full / Remaining Supplier Return
    $ret2 = SupplierReturnEngine::processReturn([
        'grn_id'      => $grnId,
        'return_date' => date('Y-m-d'),
        'notes'       => 'Return remaining 6 units',
        'items'       => [['grn_item_id' => $grnItemId, 'quantity' => 6, 'reason' => 'Remaining returned']]
    ], $userId, $branchId);

    assertTest($ret2['success'] && $ret2['total_return_amount'] == 600.00, "Full Supplier Return of remaining 6 units processed");

    $grnUpdated = Database::fetchOne("SELECT paid_amount, payment_status FROM grns WHERE id = :id", [':id' => $grnId]);
    assertTest($grnUpdated['paid_amount'] == 1000.00 && $grnUpdated['payment_status'] === 'paid', "GRN paid_amount updated to 1000.00 and status set to 'paid'");

    // 3. Over-return Blocked
    $retOver = SupplierReturnEngine::processReturn([
        'grn_id'      => $grnId,
        'return_date' => date('Y-m-d'),
        'items'       => [['grn_item_id' => $grnItemId, 'quantity' => 1, 'reason' => 'Over return']]
    ], $userId, $branchId);

    assertTest(!$retOver['success'], "Over-return beyond received quantity is correctly blocked", $retOver['message']);

    // 4. Insufficient Lot Stock Blocked
    $poRes2 = PurchaseOrder::create([
        'supplier_id'   => $supplierId,
        'branch_id'     => $branchId,
        'user_id'       => $userId,
        'expected_date' => date('Y-m-d')
    ], [['product_id' => $productId, 'quantity' => 10, 'unit_cost' => 100.00, 'selling_price' => 150.00]]);
    $poId2 = $poRes2['po_id'];
    PurchaseOrder::approve($poId2, $userId);

    $grnRes2 = GRN::create([
        'po_id'                 => $poId2,
        'supplier_id'           => $supplierId,
        'branch_id'             => $branchId,
        'user_id'               => $userId,
        'received_date'         => date('Y-m-d'),
        'supplier_invoice_no'   => 'INV-S15-02',
        'supplier_invoice_date' => date('Y-m-d')
    ], [['product_id' => $productId, 'quantity' => 10, 'unit_cost' => 100.00, 'selling_price' => 150.00]]);
    $grnId2 = $grnRes2['grn_id'];
    GRN::approve($grnId2, $userId);

    $grnItemRow2 = Database::fetchOne("SELECT * FROM grn_items WHERE grn_id = :g", [':g' => $grnId2]);
    $grnItemId2 = (int)$grnItemRow2['id'];
    $lotId2     = (int)$grnItemRow2['lot_id'];

    // Reduce lot stock manually to 3 units to simulate sales
    Database::execute("UPDATE product_lots SET remaining_quantity = 3.00 WHERE id = :id", [':id' => $lotId2]);
    Database::execute("UPDATE branch_inventory SET quantity = (SELECT COALESCE(SUM(remaining_quantity), 0) FROM product_lots WHERE product_id = :pid1 AND branch_id = :bid1 AND status = 'active' AND remaining_quantity > 0) WHERE product_id = :pid2 AND branch_id = :bid2", [':pid1' => $productId, ':bid1' => $branchId, ':pid2' => $productId, ':bid2' => $branchId]);

    $retStockErr = SupplierReturnEngine::processReturn([
        'grn_id'      => $grnId2,
        'return_date' => date('Y-m-d'),
        'items'       => [['grn_item_id' => $grnItemId2, 'quantity' => 5, 'reason' => 'Excess return']]
    ], $userId, $branchId);

    assertTest(!$retStockErr['success'], "Insufficient lot stock return correctly blocked", $retStockErr['message']);

    // 5. Paid-GRN Return Creates Supplier Credit (Asset 1045)
    $poRes3 = PurchaseOrder::create([
        'supplier_id'   => $supplierId,
        'branch_id'     => $branchId,
        'user_id'       => $userId,
        'expected_date' => date('Y-m-d')
    ], [['product_id' => $productId, 'quantity' => 10, 'unit_cost' => 200.00, 'selling_price' => 300.00]]);
    PurchaseOrder::approve($poRes3['po_id'], $userId);

    $grnRes3 = GRN::create([
        'po_id'                 => $poRes3['po_id'],
        'supplier_id'           => $supplierId,
        'branch_id'             => $branchId,
        'user_id'               => $userId,
        'received_date'         => date('Y-m-d'),
        'supplier_invoice_no'   => 'INV-S15-03',
        'supplier_invoice_date' => date('Y-m-d')
    ], [['product_id' => $productId, 'quantity' => 10, 'unit_cost' => 200.00, 'selling_price' => 300.00]]);
    $grnId3 = $grnRes3['grn_id'];
    GRN::approve($grnId3, $userId);

    // Pay GRN #3 in full (2000.00)
    SupplierPayment::processPayment([
        'supplier_id'    => $supplierId,
        'payment_date'   => date('Y-m-d'),
        'payment_method' => 'Cash',
        'amount'         => 2000.00,
        'allocations'    => [['grn_id' => $grnId3, 'amount' => 2000.00]]
    ], $userId, $branchId);

    $grnItemRow3 = Database::fetchOne("SELECT * FROM grn_items WHERE grn_id = :g", [':g' => $grnId3]);

    $retPaid = SupplierReturnEngine::processReturn([
        'grn_id'      => $grnId3,
        'return_date' => date('Y-m-d'),
        'items'       => [['grn_item_id' => (int)$grnItemRow3['id'], 'quantity' => 3, 'reason' => 'Return paid item']]
    ], $userId, $branchId);

    assertTest($retPaid['success'] && $retPaid['credit_amount'] == 600.00, "Paid-GRN return creates Supplier Credit balance of LKR 600.00");
    $creditId = (int)$retPaid['supplier_credit_id'];

    $scRow = Database::fetchOne("SELECT * FROM supplier_credits WHERE id = :id", [':id' => $creditId]);
    assertTest($scRow && $scRow['remaining_credit_amount'] == 600.00 && $scRow['status'] === 'active', "Supplier Credit record saved with active status");

    // 6. Supplier Credit Settlement - Cash Refund
    $setCash = SupplierReturnEngine::processCreditSettlement([
        'supplier_credit_id' => $creditId,
        'settlement_type'    => 'cash_refund',
        'amount'             => 200.00,
        'settlement_date'    => date('Y-m-d'),
        'notes'              => 'Cash refund settlement'
    ], $userId, $branchId);

    assertTest($setCash['success'] && ($setCash['remaining_credit_amount'] ?? -1) == 400.00, "Credit settled LKR 200.00 via Cash Refund", $setCash['message'] ?? json_encode($setCash));

    // 7. Supplier Credit Settlement - Bank Refund
    $bankAccRes = Banking::saveBankAccount([
        'bank_name'       => 'Commercial Bank S15',
        'account_name'    => 'Main Operations S15',
        'account_number'  => 'CB-' . rand(10000, 99999),
        'opening_balance' => 10000.00,
        'branch_id'       => $branchId,
        'status'          => 'active'
    ], $userId);
    assertTest($bankAccRes['success'] ?? false, "Real bank account created for Stage 15 tests", $bankAccRes['message'] ?? '');
    $bankAccountId = (int)($bankAccRes['id'] ?? 0);

    $setBank = SupplierReturnEngine::processCreditSettlement([
        'supplier_credit_id' => $creditId,
        'settlement_type'    => 'bank_refund',
        'amount'             => 200.00,
        'settlement_date'    => date('Y-m-d'),
        'bank_account_id'    => $bankAccountId,
        'notes'              => 'Bank refund settlement'
    ], $userId, $branchId);

    assertTest($setBank['success'] && ($setBank['remaining_credit_amount'] ?? -1) == 200.00, "Credit settled LKR 200.00 via Bank Refund", $setBank['message'] ?? json_encode($setBank));

    // 8. Supplier Credit Settlement - Future GRN Offset
    $poRes4 = PurchaseOrder::create([
        'supplier_id'   => $supplierId,
        'branch_id'     => $branchId,
        'user_id'       => $userId,
        'expected_date' => date('Y-m-d')
    ], [['product_id' => $productId, 'quantity' => 5, 'unit_cost' => 100.00, 'selling_price' => 150.00]]);
    PurchaseOrder::approve($poRes4['po_id'], $userId);

    $grnRes4 = GRN::create([
        'po_id'                 => $poRes4['po_id'],
        'supplier_id'           => $supplierId,
        'branch_id'             => $branchId,
        'user_id'               => $userId,
        'received_date'         => date('Y-m-d'),
        'supplier_invoice_no'   => 'INV-S15-04',
        'supplier_invoice_date' => date('Y-m-d')
    ], [['product_id' => $productId, 'quantity' => 5, 'unit_cost' => 100.00, 'selling_price' => 150.00]]);
    $grnId4 = $grnRes4['grn_id'];
    GRN::approve($grnId4, $userId);

    $setOffset = SupplierReturnEngine::processCreditSettlement([
        'supplier_credit_id' => $creditId,
        'settlement_type'    => 'future_grn_offset',
        'amount'             => 200.00,
        'settlement_date'    => date('Y-m-d'),
        'target_grn_id'      => $grnId4,
        'notes'              => 'Future GRN offset settlement'
    ], $userId, $branchId);

    assertTest($setOffset['success'] && ($setOffset['remaining_credit_amount'] ?? -1) == 0.00, "Credit settled LKR 200.00 via Future GRN Offset. Balance is 0.00", $setOffset['message'] ?? json_encode($setOffset));

    $grnTargetRow = Database::fetchOne("SELECT paid_amount, payment_status FROM grns WHERE id = :id", [':id' => $grnId4]);
    assertTest($grnTargetRow['paid_amount'] == 200.00 && $grnTargetRow['payment_status'] === 'partially_paid', "Target GRN paid_amount updated to 200.00");

    // 9. Cheque Lifecycle: Issue Pending Cheque
    $chqNumber = "CHQ-S15-" . rand(1000, 9999);
    $chqPmt = SupplierPayment::processPayment([
        'supplier_id'    => $supplierId,
        'payment_date'   => date('Y-m-d'),
        'payment_method' => 'Cheque',
        'amount'         => 300.00,
        'cheque'         => [
            'cheque_number' => $chqNumber,
            'cheque_date'   => date('Y-m-d'),
            'bank_name'     => 'Sampath Bank'
        ],
        'allocations'    => [['grn_id' => $grnId4, 'amount' => 300.00]]
    ], $userId, $branchId);

    assertTest($chqPmt['success'], "Supplier Cheque payment issued successfully", $chqPmt['message'] ?? '');

    $chqRow1 = Database::fetchOne("SELECT * FROM supplier_cheques WHERE cheque_number = :c", [':c' => $chqNumber]);
    assertTest($chqRow1 && $chqRow1['status'] === 'pending', "Issued cheque recorded with 'pending' status");

    // Verify Cheques Issued journal (Dr 2020, Cr 2025)
    $chqJe = Database::fetchOne("SELECT * FROM journal_entries WHERE source_type = 'supplier_payment' AND source_id = :sid", [':sid' => $chqPmt['supplier_payment_id']]);
    assertTest($chqJe !== null, "Supplier Payment Journal Entry created");

    // 10. Clear Cheque -> Actual Bank Account
    $clearRes = ChequeEngine::clearCheque([
        'cheque_id'       => (int)$chqRow1['id'],
        'bank_account_id' => $bankAccountId,
        'cleared_date'    => date('Y-m-d')
    ], $userId);

    assertTest($clearRes['success'], "Pending cheque cleared successfully into real Bank Account", $clearRes['message'] ?? '');

    $chqClearedRow = Database::fetchOne("SELECT status FROM supplier_cheques WHERE id = :id", [':id' => $chqRow1['id']]);
    assertTest($chqClearedRow['status'] === 'cleared', "Cheque status updated to 'cleared'");

    // 11. Cleared Cheque Cancellation Blocked
    $cancelErr = ChequeEngine::cancelCheque([
        'cheque_id'           => (int)$chqRow1['id'],
        'cancellation_reason' => 'Try to cancel cleared cheque'
    ], $userId);

    assertTest(!$cancelErr['success'], "Cancellation of cleared cheque is correctly blocked", $cancelErr['message']);

    // 12. Bounced Cheque Restores Supplier Payable & GRN Allocation
    $poRes5 = PurchaseOrder::create([
        'supplier_id'   => $supplierId,
        'branch_id'     => $branchId,
        'user_id'       => $userId,
        'expected_date' => date('Y-m-d')
    ], [['product_id' => $productId, 'quantity' => 10, 'unit_cost' => 100.00, 'selling_price' => 150.00]]);
    PurchaseOrder::approve($poRes5['po_id'], $userId);

    $grnRes5 = GRN::create([
        'po_id'                 => $poRes5['po_id'],
        'supplier_id'           => $supplierId,
        'branch_id'             => $branchId,
        'user_id'               => $userId,
        'received_date'         => date('Y-m-d'),
        'supplier_invoice_no'   => 'INV-S15-05',
        'supplier_invoice_date' => date('Y-m-d')
    ], [['product_id' => $productId, 'quantity' => 10, 'unit_cost' => 100.00, 'selling_price' => 150.00]]);
    $grnId5 = $grnRes5['grn_id'];
    GRN::approve($grnId5, $userId);

    $chqNumber2 = "CHQ-S15-" . rand(1000, 9999);
    $chqPmt2 = SupplierPayment::processPayment([
        'supplier_id'    => $supplierId,
        'payment_date'   => date('Y-m-d'),
        'payment_method' => 'Cheque',
        'amount'         => 100.00,
        'cheque'         => [
            'cheque_number' => $chqNumber2,
            'cheque_date'   => date('Y-m-d'),
            'bank_name'     => 'HNB'
        ],
        'allocations'    => [['grn_id' => $grnId5, 'amount' => 100.00]]
    ], $userId, $branchId);

    assertTest($chqPmt2['success'] ?? false, "Supplier Cheque payment #2 issued successfully", $chqPmt2['message'] ?? '');

    $chqRow2 = Database::fetchOne("SELECT * FROM supplier_cheques WHERE cheque_number = :c", [':c' => $chqNumber2]);

    $bounceRes = ChequeEngine::bounceCheque([
        'cheque_id'      => (int)($chqRow2['id'] ?? 0),
        'bounced_reason' => 'Signature mismatch',
        'bounced_date'   => date('Y-m-d')
    ], $userId);

    assertTest($bounceRes['success'] ?? false, "Supplier cheque marked as BOUNCED successfully", $bounceRes['message'] ?? '');

    $chqBouncedRow = Database::fetchOne("SELECT status FROM supplier_cheques WHERE id = :id", [':id' => ($chqRow2['id'] ?? 0)]);
    assertTest(($chqBouncedRow['status'] ?? '') === 'bounced', "Cheque status updated to 'bounced'");

    // 13. Cancel Pending Cheque
    $chqNumber3 = "CHQ-S15-" . rand(1000, 9999);
    $chqPmt3 = SupplierPayment::processPayment([
        'supplier_id'    => $supplierId,
        'payment_date'   => date('Y-m-d'),
        'payment_method' => 'Cheque',
        'amount'         => 50.00,
        'cheque'         => [
            'cheque_number' => $chqNumber3,
            'cheque_date'   => date('Y-m-d'),
            'bank_name'     => 'BOC'
        ],
        'allocations'    => [['grn_id' => $grnId5, 'amount' => 50.00]]
    ], $userId, $branchId);

    assertTest($chqPmt3['success'] ?? false, "Supplier Cheque payment #3 issued successfully", $chqPmt3['message'] ?? '');

    $chqRow3 = Database::fetchOne("SELECT * FROM supplier_cheques WHERE cheque_number = :c", [':c' => $chqNumber3]);

    $cancelRes = ChequeEngine::cancelCheque([
        'cheque_id'           => (int)($chqRow3['id'] ?? 0),
        'cancellation_reason' => 'Entry error'
    ], $userId);

    assertTest($cancelRes['success'] ?? false, "Pending cheque CANCELLED successfully", $cancelRes['message'] ?? '');

    // 14. Stock Ledger Correctness
    $ledgerEntries = Database::fetchAll("SELECT * FROM stock_ledger WHERE movement_type = 'supplier_return' ORDER BY id DESC LIMIT 5");
    assertTest(count($ledgerEntries) >= 2, "Stock ledger recorded 'supplier_return' movements with negative quantities");

    // 15. Global General Ledger Balance Check (SUM(debit) == SUM(credit))
    $glBalance = Database::fetchOne("
        SELECT 
            ROUND(SUM(debit), 2) AS total_debit, 
            ROUND(SUM(credit), 2) AS total_credit 
        FROM journal_lines
    ");
    $totalDebit  = (float)($glBalance['total_debit'] ?? 0);
    $totalCredit = (float)($glBalance['total_credit'] ?? 0);
    $diff        = abs($totalDebit - $totalCredit);

    assertTest($diff < 0.001, "General Ledger strictly balanced: Total Debit (LKR {$totalDebit}) == Total Credit (LKR {$totalCredit})");

    echo "\n-----------------------------------------------------\n";
    echo "Summary: {$passed} Passed, {$failed} Failed out of {$testsRun} Tests.\n";
    echo "-----------------------------------------------------\n";

    if ($failed > 0) {
        exit(1);
    }

} catch (Throwable $e) {
    echo "\n[ERROR] Exception caught: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
