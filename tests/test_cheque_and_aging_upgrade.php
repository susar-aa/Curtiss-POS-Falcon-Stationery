<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Audit.php';
require_once __DIR__ . '/../core/SupplierPayment.php';
require_once __DIR__ . '/../core/CustomerPayment.php';
require_once __DIR__ . '/../core/ChequeEngine.php';
require_once __DIR__ . '/../core/Accounting.php';

echo "=== CURTISS POS: CHEQUE UPGRADE & AGING CLI TEST SUITE ===\n\n";

$pdo = Database::getInstance()->getConnection();
$branchId = 1;
$userId = 1;

// Track created IDs for clean teardown without TRUNCATE
$createdSupplierId = null;
$createdCustomerId = null;
$createdBankAccountId = null;
$createdGrnId = null;
$createdSupPaymentId = null;
$createdCustPaymentId = null;
$createdSupplierChequeId = null;
$createdCustomerChequeId = null;

try {
    // 1. Setup Test Bank Account
    $coaId = (int)$pdo->query("SELECT id FROM chart_of_accounts WHERE account_code = '1020' LIMIT 1")->fetchColumn();
    if (!$coaId) {
        $coaId = (int)$pdo->query("SELECT id FROM chart_of_accounts LIMIT 1")->fetchColumn();
    }
    $testAccNo = 'CHQ-TEST-' . time();
    $stmt = $pdo->prepare("INSERT INTO bank_accounts (branch_id, bank_name, account_name, account_number, account_id, status, created_at)
        VALUES (:b, 'Test Commercial Bank', 'Test Main Operating', :acc, :coa, 'active', NOW())");
    $stmt->execute([':b' => $branchId, ':acc' => $testAccNo, ':coa' => $coaId]);
    $createdBankAccountId = (int)$pdo->lastInsertId();
    echo "[PASS] Test Bank Account created (ID: {$createdBankAccountId})\n";

    // 2. Setup Test Supplier
    $supCode = 'SUP-CHQ-' . time();
    $stmt = $pdo->prepare("INSERT INTO suppliers (supplier_code, name, phone, status, created_at)
        VALUES (:code, 'Test Cheque Supplier Ltd', '0771234567', 'active', NOW())");
    $stmt->execute([':code' => $supCode]);
    $createdSupplierId = (int)$pdo->lastInsertId();
    echo "[PASS] Test Supplier created (ID: {$createdSupplierId})\n";

    // 3. Setup Test Customer
    $custCode = 'CUST-CHQ-' . time();
    $stmt = $pdo->prepare("INSERT INTO customers (customer_code, name, phone, credit_limit, credit_balance, status, created_at)
        VALUES (:code, 'Test Cheque Customer', '0719876543', 200000.00, 15000.00, 'active', NOW())");
    $stmt->execute([':code' => $custCode]);
    $createdCustomerId = (int)$pdo->lastInsertId();
    echo "[PASS] Test Customer created (ID: {$createdCustomerId}) with credit balance LKR 15,000.00\n";

    // 4. Setup Test GRN for Supplier
    $grnNumber = 'GRN-CHQ-' . time();
    $stmt = $pdo->prepare("INSERT INTO grns (grn_number, supplier_id, branch_id, received_date, actual_grn_total, paid_amount, payment_status, status, created_by, created_at)
        VALUES (:num, :sup, :b, CURDATE(), 25000.00, 0.00, 'unpaid', 'Approved', :u, NOW())");
    $stmt->execute([':num' => $grnNumber, ':sup' => $createdSupplierId, ':b' => $branchId, ':u' => $userId]);
    $createdGrnId = (int)$pdo->lastInsertId();
    echo "[PASS] Test GRN created (ID: {$createdGrnId}) for LKR 25,000.00\n";

    // 5. Test Supplier Payment with Issued Cheque
    $chqNumber = 'CHQ-SUP-9901';
    $supPaymentData = [
        'supplier_id' => $createdSupplierId,
        'branch_id' => $branchId,
        'payment_date' => date('Y-m-d'),
        'total_amount' => 10000.00,
        'splits' => [
            [
                'method' => 'Cheque:' . $createdBankAccountId,
                'amount' => 10000.00,
                'bank_account_id' => $createdBankAccountId,
                'cheque_no' => $chqNumber,
                'cheque_date' => date('Y-m-d', strtotime('+7 days')),
                'notes' => 'Post-dated test supplier cheque'
            ]
        ],
        'allocations' => [
            [
                'grn_id' => $createdGrnId,
                'amount' => 10000.00
            ]
        ]
    ];

    $supResult = SupplierPayment::processPayment($supPaymentData, $userId, $branchId);
    if (empty($supResult['success'])) {
        throw new Exception("Supplier payment failed: " . ($supResult['message'] ?? 'Unknown error'));
    }
    $createdSupPaymentId = (int)($supResult['supplier_payment_id'] ?? $supResult['payment_ids'][0] ?? 0);
    echo "[PASS] Supplier Cheque Payment processed successfully (Payment ID: {$createdSupPaymentId})\n";

    // Verify supplier_cheques record created with status pending
    $stmt = $pdo->prepare("SELECT * FROM supplier_cheques WHERE supplier_payment_id = :pid");
    $stmt->execute([':pid' => $createdSupPaymentId]);
    $supCheque = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$supCheque || $supCheque['cheque_number'] !== $chqNumber || $supCheque['status'] !== 'pending') {
        throw new Exception("Supplier cheque record not found or invalid status");
    }
    $createdSupplierChequeId = (int)$supCheque['id'];
    echo "[PASS] Supplier Cheque registered in 'pending' status (Cheque ID: {$createdSupplierChequeId}, Number: {$chqNumber})\n";

    // 6. Test Duplicate Cheque Number Rejection on Same Bank Account
    $dupPaymentData = [
        'supplier_id' => $createdSupplierId,
        'branch_id' => $branchId,
        'payment_date' => date('Y-m-d'),
        'total_amount' => 5000.00,
        'splits' => [
            [
                'method' => 'Cheque:' . $createdBankAccountId,
                'amount' => 5000.00,
                'bank_account_id' => $createdBankAccountId,
                'cheque_no' => $chqNumber, // Duplicate!
                'cheque_date' => date('Y-m-d', strtotime('+10 days')),
                'notes' => 'Duplicate cheque test'
            ]
        ],
        'allocations' => []
    ];
    $dupResult = SupplierPayment::processPayment($dupPaymentData, $userId, $branchId);
    if ($dupResult['success']) {
        throw new Exception("Duplicate cheque number was NOT blocked as expected!");
    }
    echo "[PASS] Duplicate Cheque Number blocked successfully: '{$dupResult['message']}'\n";

    // 7. Test Issued Cheque Clearance (ChequeEngine)
    $clearResult = ChequeEngine::clearCheque([
        'cheque_id' => $createdSupplierChequeId,
        'cleared_date' => date('Y-m-d'),
        'bank_ref' => 'REF-BNK-CLR-7788',
        'notes' => 'Supplier cheque presented and cleared'
    ], $userId);
    if (!$clearResult['success']) {
        throw new Exception("ChequeEngine::clearCheque failed: " . ($clearResult['message'] ?? 'Unknown error'));
    }
    echo "[PASS] Supplier Cheque cleared successfully via ChequeEngine\n";

    // Verify cheque status and clearance details in DB
    $stmt = $pdo->prepare("SELECT status, cleared_date, bank_ref FROM supplier_cheques WHERE id = :id");
    $stmt->execute([':id' => $createdSupplierChequeId]);
    $clearedCheque = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($clearedCheque['status'] !== 'cleared' || empty($clearedCheque['cleared_date']) || $clearedCheque['bank_ref'] !== 'REF-BNK-CLR-7788') {
        throw new Exception("Supplier cheque cleared state verification failed");
    }
    echo "[PASS] Supplier Cheque database state verified: Status 'cleared', Date '{$clearedCheque['cleared_date']}', Ref '{$clearedCheque['bank_ref']}'\n";

    // 8. Test Customer Payment with Received Cheque (in_hand)
    $custChqNo = 'CUST-CHQ-1002';
    $custPaymentData = [
        'customer_id' => $createdCustomerId,
        'branch_id' => $branchId,
        'payment_date' => date('Y-m-d'),
        'total_amount' => 8000.00,
        'splits' => [
            [
                'method' => 'Cheque',
                'amount' => 8000.00,
                'bank_name' => 'Bank of Ceylon',
                'cheque_no' => $custChqNo,
                'cheque_date' => date('Y-m-d', strtotime('+3 days')),
                'notes' => 'Customer settlement cheque'
            ]
        ]
    ];

    $custResult = CustomerPayment::processPayment($custPaymentData, $userId, $branchId);
    if (empty($custResult['success'])) {
        throw new Exception("Customer payment processing failed: " . ($custResult['message'] ?? 'Unknown error'));
    }
    $createdCustomerChequeId = (int)($custResult['cheque_id'] ?? 0);
    echo "[PASS] Customer Cheque Payment processed successfully (Cheque ID: {$createdCustomerChequeId})\n";

    // Verify customer_cheques row created with status in_hand
    $stmt = $pdo->prepare("SELECT * FROM customer_cheques WHERE id = :id");
    $stmt->execute([':id' => $createdCustomerChequeId]);
    $custCheque = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$custCheque || $custCheque['cheque_no'] !== $custChqNo || $custCheque['status'] !== 'in_hand') {
        throw new Exception("Customer cheque row creation failed or status is not 'in_hand'");
    }
    echo "[PASS] Customer Cheque created in 'in_hand' status (ID: {$createdCustomerChequeId}, Bank: '{$custCheque['bank_name']}', Amount: {$custCheque['amount']})\n";

    // Verify Customer Credit Balance reduced by 8000.00 (from 15000 to 7000)
    $stmt = $pdo->prepare("SELECT credit_balance FROM customers WHERE id = :id");
    $stmt->execute([':id' => $createdCustomerId]);
    $newCustBal = (float)$stmt->fetchColumn();
    if (abs($newCustBal - 7000.00) > 0.01) {
        throw new Exception("Customer credit balance was not correctly reduced (Expected 7000.00, got {$newCustBal})");
    }
    echo "[PASS] Customer Credit Balance reduced correctly to LKR " . number_format($newCustBal, 2) . "\n";

    // 9. Test Customer Cheque Deposit (in_hand -> deposited)
    $depResult = ChequeEngine::depositCustomerCheque([
        'cheque_id' => $createdCustomerChequeId,
        'bank_account_id' => $createdBankAccountId,
        'deposit_date' => date('Y-m-d'),
        'deposit_ref' => 'SLIP-DEP-4455'
    ], $userId);
    if (!$depResult['success']) {
        throw new Exception("Deposit customer cheque failed: " . ($depResult['message'] ?? 'Unknown error'));
    }
    echo "[PASS] Customer Cheque deposited to bank account (Status: 'deposited')\n";

    // 10. Test Customer Cheque Return / Bounce (deposited -> returned) with AR & Customer Balance Restoration
    $retResult = ChequeEngine::returnCustomerCheque([
        'cheque_id' => $createdCustomerChequeId,
        'return_reason' => 'Insufficient funds in customer drawer'
    ], $userId, $branchId);
    if (!$retResult['success']) {
        throw new Exception("Return customer cheque failed: " . ($retResult['message'] ?? 'Unknown error'));
    }
    echo "[PASS] Customer Cheque marked as returned (Status: 'returned')\n";

    // Verify Customer Credit Balance restored back to 15,000.00
    $stmt = $pdo->prepare("SELECT credit_balance FROM customers WHERE id = :id");
    $stmt->execute([':id' => $createdCustomerId]);
    $restoredCustBal = (float)$stmt->fetchColumn();
    if (abs($restoredCustBal - 15000.00) > 0.01) {
        throw new Exception("Customer credit balance was not restored upon cheque return (Expected 15000.00, got {$restoredCustBal})");
    }
    echo "[PASS] Customer Credit Balance restored back to LKR " . number_format($restoredCustBal, 2) . "\n";

    // Verify customer_credit_ledger has cheque_return entry
    $stmt = $pdo->prepare("SELECT * FROM customer_credit_ledger WHERE customer_id = :cid ORDER BY id DESC LIMIT 1");
    $stmt->execute([':cid' => $createdCustomerId]);
    $ledgerRow = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$ledgerRow || $ledgerRow['transaction_type'] !== 'cheque_return') {
        throw new Exception("Customer credit ledger entry 'cheque_return' was not created");
    }
    echo "[PASS] Customer Credit Ledger entry verified: Type '{$ledgerRow['transaction_type']}', Amount: {$ledgerRow['amount']}\n";

    // 11. Test ChequeEngine Tab Queries and KPI Summaries
    $issuedList = ChequeEngine::getPaginatedCheques(['branch_id' => $branchId], 1, 25);
    if (!isset($issuedList['items']) || !isset($issuedList['totalItems'])) {
        throw new Exception("ChequeEngine::getPaginatedCheques response format invalid");
    }
    echo "[PASS] Issued Cheques Tab Query verified ({$issuedList['totalItems']} total cheques)\n";

    $receivedList = ChequeEngine::getPaginatedCustomerCheques(['branch_id' => $branchId], 1, 25);
    if (!isset($receivedList['items']) || !isset($receivedList['totalItems'])) {
        throw new Exception("ChequeEngine::getPaginatedCustomerCheques response format invalid");
    }
    echo "[PASS] Received Cheques Tab Query verified ({$receivedList['totalItems']} total cheques)\n";

    $supSummary = ChequeEngine::getPendingSummary($branchId);
    if (!isset($supSummary['count_pending']) || !isset($supSummary['total_pending'])) {
        throw new Exception("ChequeEngine::getPendingSummary response format invalid");
    }
    echo "[PASS] Issued Cheque KPI Summary verified (Pending: {$supSummary['count_pending']}, LKR " . number_format($supSummary['total_pending'], 2) . ")\n";

    $custSummary = ChequeEngine::getCustomerPendingSummary($branchId);
    if (!isset($custSummary['count_in_hand']) || !isset($custSummary['count_deposited'])) {
        throw new Exception("ChequeEngine::getCustomerPendingSummary response format invalid");
    }
    echo "[PASS] Received Cheque KPI Summary verified (In-Hand: {$custSummary['count_in_hand']}, Deposited: {$custSummary['count_deposited']})\n";

    // 12. Verify Double-Entry Accounting Journal Equality
    $stmt = $pdo->prepare("SELECT SUM(debit) AS tot_debit, SUM(credit) AS tot_credit FROM journal_lines");
    $stmt->execute();
    $jeBal = $stmt->fetch(PDO::FETCH_ASSOC);
    $totDebit = (float)($jeBal['tot_debit'] ?? 0);
    $totCredit = (float)($jeBal['tot_credit'] ?? 0);
    $diff = abs($totDebit - $totCredit);
    if ($diff > 0.01) {
        throw new Exception("Double-entry imbalance detected in journal lines: Debit " . number_format($totDebit, 2) . " vs Credit " . number_format($totCredit, 2));
    }
    echo "[PASS] Global Chart of Accounts Double-Entry Balanced: Total Debit = LKR " . number_format($totDebit, 2) . " == Total Credit = LKR " . number_format($totCredit, 2) . "\n";

    echo "\n=== ALL 12 TESTS PASSED WITH 100% SUCCESS ===\n";

} catch (Throwable $e) {
    echo "\n[FAIL] Test aborted with error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
} finally {
    // Isolated Cleanup: Delete ONLY created test records (NEVER TRUNCATE)
    echo "\n--- Cleaning up test fixtures ---\n";
    if ($createdCustomerChequeId) {
        $pdo->exec("DELETE FROM customer_cheques WHERE id = {$createdCustomerChequeId}");
    }
    if ($createdCustomerId) {
        $pdo->exec("DELETE FROM customer_credit_ledger WHERE customer_id = {$createdCustomerId}");
        $pdo->exec("DELETE FROM customers WHERE id = {$createdCustomerId}");
    }
    if ($createdSupplierChequeId) {
        $pdo->exec("DELETE FROM supplier_cheques WHERE id = {$createdSupplierChequeId}");
    }
    if ($createdSupPaymentId) {
        $pdo->exec("DELETE FROM supplier_payment_grns WHERE supplier_payment_id = {$createdSupPaymentId}");
        $pdo->exec("DELETE FROM supplier_payments WHERE id = {$createdSupPaymentId}");
    }
    if ($createdGrnId) {
        $pdo->exec("DELETE FROM supplier_payment_grns WHERE grn_id = {$createdGrnId}");
        $pdo->exec("DELETE FROM grns WHERE id = {$createdGrnId}");
    }
    if ($createdSupplierId) {
        $pdo->exec("DELETE FROM suppliers WHERE id = {$createdSupplierId}");
    }
    if ($createdBankAccountId) {
        $pdo->exec("DELETE FROM bank_accounts WHERE id = {$createdBankAccountId}");
    }
    echo "[DONE] Test fixtures safely cleaned up. Database remains intact.\n";
}
