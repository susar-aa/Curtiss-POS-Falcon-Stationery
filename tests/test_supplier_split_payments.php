<?php
/**
 * Isolated Verification Test for Supplier Split Payments
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/SupplierPayment.php';
require_once __DIR__ . '/../core/Accounting.php';

echo "=== TEST: Supplier Split Payments System ===\n";

$db = Database::getInstance()->getConnection();

// Create isolated test supplier
$db->exec("
    INSERT INTO suppliers (name, supplier_code, phone, address, status, created_at)
    VALUES ('TEST_SPLIT_SUPPLIER_XYZ', 'SUP-SPLIT-999', '0779998877', 'Colombo', 'active', NOW())
");
$testSupplierId = (int)$db->lastInsertId();
echo "[1] Created test supplier ID: {$testSupplierId}\n";

// Create an approved GRN for this supplier with actual_grn_total = 10000.00
$db->exec("
    INSERT INTO grns (grn_number, supplier_id, branch_id, po_id, supplier_invoice_no, received_date, actual_grn_total, paid_amount, payment_status, status, created_by, created_at)
    VALUES ('GRN-TEST-SPLIT-01', {$testSupplierId}, 1, NULL, 'INV-SPLIT-01', CURDATE(), 10000.00, 0.00, 'unpaid', 'Approved', 1, NOW())
");
$testGrnId = (int)$db->lastInsertId();
echo "[2] Created test approved GRN ID: {$testGrnId} with total LKR 10,000.00\n";

// Verify getSupplierOutstandingSummary
$summary = SupplierPayment::getSupplierOutstandingSummary($testSupplierId);
if (!$summary['success'] || (float)$summary['total_outstanding'] !== 10000.00) {
    throw new Exception("Outstanding summary mismatch: expected 10000.00, got " . ($summary['total_outstanding'] ?? 'null'));
}
echo "[3] Outstanding summary verified: LKR " . number_format($summary['total_outstanding'], 2) . "\n";

// Fetch an active bank account
$bank = Database::fetchOne("SELECT id, bank_name, account_name FROM bank_accounts WHERE status = 'active' LIMIT 1");
$bankId = $bank ? (int)$bank['id'] : null;

// Test split payment: LKR 3,000 via Cash in Hand + LKR 4,000 via Cheque
$splits = [
    [
        'payment_method'  => 'Cash in Hand',
        'bank_account_id' => null,
        'amount'          => 3000.00,
        'reference_no'    => 'TRX-CASH-01',
        'cheque_no'       => '',
        'cheque_date'     => date('Y-m-d')
    ],
    [
        'payment_method'  => 'Cheque',
        'bank_account_id' => $bankId,
        'amount'          => 4000.00,
        'reference_no'    => 'CHQ-SPLIT-7788',
        'cheque_no'       => 'CHQ-SPLIT-7788',
        'cheque_date'     => date('Y-m-d'),
        'bank_name'       => 'Commercial Bank'
    ]
];

$payload = [
    'supplier_id'  => $testSupplierId,
    'payment_date' => date('Y-m-d'),
    'amount'       => 7000.00,
    'splits'       => $splits,
    'notes'        => 'Test Split Payment verification'
];

$res = SupplierPayment::processPayment($payload, 1, 1);
echo "[4] Process Payment result: " . ($res['success'] ? 'SUCCESS' : 'FAILED') . "\n";
if (!$res['success']) {
    throw new Exception("Payment processing failed: " . ($res['message'] ?? 'Unknown error'));
}

$paymentIds = $res['payment_ids'] ?? [$res['supplier_payment_id']];
echo "    Created Payment IDs: " . implode(', ', $paymentIds) . "\n";

// Verify GRN paid_amount updated to 7000.00
$updatedGrn = Database::fetchOne("SELECT actual_grn_total, paid_amount, payment_status FROM grns WHERE id = :id", [':id' => $testGrnId]);
echo "[5] GRN paid_amount: LKR " . number_format($updatedGrn['paid_amount'], 2) . ", Status: " . $updatedGrn['payment_status'] . "\n";
if ((float)$updatedGrn['paid_amount'] !== 7000.00 || $updatedGrn['payment_status'] !== 'partially_paid') {
    throw new Exception("GRN payment status incorrect!");
}

// Verify supplier_payment_grns allocations count
$allocs = Database::fetchAll("SELECT * FROM supplier_payment_grns WHERE grn_id = :gid", [':gid' => $testGrnId]);
echo "[6] Allocations count: " . count($allocs) . "\n";
$sumAlloc = array_sum(array_column($allocs, 'allocated_amount'));
echo "    Total allocated: LKR " . number_format($sumAlloc, 2) . "\n";
if ((float)$sumAlloc !== 7000.00) {
    throw new Exception("Allocation sum mismatch: expected 7000.00, got " . $sumAlloc);
}

// Verify supplier_cheques record
$cheques = Database::fetchAll("SELECT * FROM supplier_cheques WHERE supplier_payment_id IN (" . implode(',', $paymentIds) . ")");
echo "[7] Cheques recorded: " . count($cheques) . "\n";
if (count($cheques) !== 1 || $cheques[0]['cheque_number'] !== 'CHQ-SPLIT-7788') {
    throw new Exception("Cheque record missing or incorrect!");
}

// Verify Journal Entries and double-entry balance for each payment
foreach ($paymentIds as $spId) {
    $je = Database::fetchOne("SELECT * FROM journal_entries WHERE source_type = 'supplier_payment' AND source_id = :id", [':id' => $spId]);
    if (!$je) {
        throw new Exception("Journal entry missing for supplier payment ID #{$spId}");
    }
    echo "[8] Journal Entry #{$je['entry_number']}: Total Debit LKR {$je['total_debit']}, Total Credit LKR {$je['total_credit']}\n";
    if ((float)$je['total_debit'] !== (float)$je['total_credit']) {
        throw new Exception("Journal entry #{$je['entry_number']} is UNBALANCED!");
    }
}

// Test overpayment prevention
$overpaySplits = [
    [
        'payment_method'  => 'Cash in Hand',
        'amount'          => 5000.00 // Outstanding is now 3000, 5000 is overpaying
    ]
];
$overpayRes = SupplierPayment::processPayment([
    'supplier_id' => $testSupplierId,
    'amount'      => 5000.00,
    'splits'      => $overpaySplits
], 1, 1);
echo "[9] Overpayment test prevented: " . (!$overpayRes['success'] ? 'YES (' . $overpayRes['message'] . ')' : 'NO (ERROR: Allowed)') . "\n";
if ($overpayRes['success']) {
    throw new Exception("Overpayment was not prevented!");
}

// Clean up test data cleanly
foreach ($paymentIds as $spId) {
    // Delete journal lines & entry
    $je = Database::fetchOne("SELECT id FROM journal_entries WHERE source_type = 'supplier_payment' AND source_id = :id", [':id' => $spId]);
    if ($je) {
        Database::execute("DELETE FROM journal_lines WHERE journal_entry_id = :jid", [':jid' => $je['id']]);
        Database::execute("DELETE FROM journal_entries WHERE id = :jid", [':jid' => $je['id']]);
    }
    Database::execute("DELETE FROM supplier_cheques WHERE supplier_payment_id = :id", [':id' => $spId]);
    Database::execute("DELETE FROM supplier_payment_grns WHERE supplier_payment_id = :id", [':id' => $spId]);
    Database::execute("DELETE FROM supplier_payments WHERE id = :id", [':id' => $spId]);
}
Database::execute("DELETE FROM grns WHERE id = :id", [':id' => $testGrnId]);
Database::execute("DELETE FROM suppliers WHERE id = :id", [':id' => $testSupplierId]);
echo "[10] Isolated test data cleaned up 100% cleanly.\n";
echo "=== ALL SUPPLIER SPLIT PAYMENT TESTS PASSED SUCCESSFULLY! ===\n";
