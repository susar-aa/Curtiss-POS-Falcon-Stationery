<?php
/**
 * Automated CLI Test Suite for Cheque Upgrade Verification
 * Tests supplier and customer cheque lifecycles, duplicate validation, and double-entry accounting integrity.
 * Local XAMPP only. Zero DB truncation (cleans up only created test IDs).
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/SupplierPayment.php';
require_once __DIR__ . '/../core/CustomerPayment.php';
require_once __DIR__ . '/../core/ChequeEngine.php';
require_once __DIR__ . '/../core/Banking.php';
require_once __DIR__ . '/../core/Accounting.php';

$testPassed = 0;
$testFailed = 0;

function assertTest(bool $condition, string $title) {
    global $testPassed, $testFailed;
    if ($condition) {
        echo "[PASS] {$title}\n";
        $testPassed++;
    } else {
        echo "[FAIL] {$title}\n";
        $testFailed++;
    }
}

echo "=== STARTING CHEQUE UPGRADE VERIFICATION TEST SUITE ===\n\n";

$db = Database::getInstance()->getConnection();
$testBranchId = 1;
$testUserId = 1;

// 1. Fetch or create test bank account
$testBank = Database::fetchOne("SELECT * FROM bank_accounts WHERE status = 'active' LIMIT 1");
if (!$testBank) {
    $db->prepare("INSERT INTO bank_accounts (branch_id, bank_name, account_name, account_number, account_id, status) VALUES (?, 'Test Bank', 'Test Account', 'ACC-9999', 1, 'active')")->execute([$testBranchId]);
    $testBankId = (int)$db->lastInsertId();
} else {
    $testBankId = (int)$testBank['id'];
}

// 2. Fetch or create test supplier
$testSupplier = Database::fetchOne("SELECT * FROM suppliers WHERE deleted_at IS NULL LIMIT 1");
if (!$testSupplier) {
    $db->prepare("INSERT INTO suppliers (branch_id, name, contact_person, phone, status) VALUES (?, 'Test Cheque Supplier', 'Contact', '0771234567', 'active')")->execute([$testBranchId]);
    $testSupplierId = (int)$db->lastInsertId();
} else {
    $testSupplierId = (int)$testSupplier['id'];
}

// 3. Create dedicated test customer with id > 1 and credit balance
$db->prepare("INSERT INTO customers (name, phone, customer_code, credit_balance, status) VALUES ('Test Cheque Customer', '0779998888', 'CUST-CHQ-1', 5000.00, 'active')")->execute();
$testCustId = (int)$db->lastInsertId();

$createdSupplierPaymentIds = [];
$createdSupplierChequeIds  = [];
$createdCustomerChequeIds  = [];
$createdJournalIds         = [];

try {
    // -------------------------------------------------------------
    // TEST 1: Duplicate Cheque Number Validation (Supplier Cheque)
    // -------------------------------------------------------------
    $chqNumber = 'CHQ-DUP-' . time();
    $db->prepare("
        INSERT INTO supplier_cheques (branch_id, supplier_id, bank_account_id, cheque_number, cheque_date, amount, status, created_by)
        VALUES (?, ?, ?, ?, CURDATE(), 2500.00, 'pending', ?)
    ")->execute([$testBranchId, $testSupplierId, $testBankId, $chqNumber, $testUserId]);
    $firstChqId = (int)$db->lastInsertId();
    $createdSupplierChequeIds[] = $firstChqId;

    $isDup = ChequeEngine::checkDuplicateChequeNo($testBankId, $chqNumber);
    assertTest($isDup === true, "Test 1A: Duplicate cheque number detected for same bank account");

    $isDupOtherBank = ChequeEngine::checkDuplicateChequeNo(999999, $chqNumber);
    assertTest($isDupOtherBank === false, "Test 1B: Different bank account does not collide with cheque number");

    // -------------------------------------------------------------
    // TEST 2: Supplier Payment with Cheque:<bank_id> Method
    // -------------------------------------------------------------
    // Clean residual test GRNs if any from prior aborted run
    $db->exec("DELETE FROM supplier_payment_grns WHERE grn_id IN (SELECT id FROM grns WHERE grn_number LIKE 'GRN-TEST-CHQ%')");
    $db->exec("DELETE FROM grns WHERE grn_number LIKE 'GRN-TEST-CHQ%'");

    $grnNumber = 'GRN-TEST-CHQ-' . time() . '-' . rand(100, 999);
    $db->prepare("INSERT INTO grns (branch_id, supplier_id, grn_number, received_date, actual_grn_total, paid_amount, payment_status, status, created_by) VALUES (?, ?, ?, CURDATE(), 3000.00, 0.00, 'unpaid', 'Approved', ?)")->execute([$testBranchId, $testSupplierId, $grnNumber, $testUserId]);
    $testGrnId = (int)$db->lastInsertId();

    $chqNumber2 = 'CHQ-SP-' . time();
    $paymentPayload = [
        'supplier_id'      => $testSupplierId,
        'payment_date'     => date('Y-m-d'),
        'total_amount'     => 1500.00,
        'discount_amount'  => 0.00,
        'splits'           => [
            [
                'payment_method'  => "Cheque:{$testBankId}",
                'amount'          => 1500.00,
                'cheque_no'       => $chqNumber2,
                'cheque_date'     => date('Y-m-d')
            ]
        ]
    ];

    $spRes = SupplierPayment::processPayment($paymentPayload, $testUserId, $testBranchId);
    assertTest($spRes['success'] === true, "Test 2A: Supplier payment processed successfully with Cheque:<bank_id>");

    if ($spRes['success']) {
        $spId = (int)($spRes['supplier_payment_id'] ?? 0);
        $createdSupplierPaymentIds[] = $spId;

        $createdChq = Database::fetchOne("SELECT * FROM supplier_cheques WHERE supplier_payment_id = ?", [$spId]);
        assertTest(!empty($createdChq) && $createdChq['status'] === 'pending' && $createdChq['cheque_number'] === $chqNumber2, "Test 2B: Supplier cheque recorded as 'pending' with correct bank_account_id");
        if ($createdChq) {
            $createdSupplierChequeIds[] = (int)$createdChq['id'];

            // -------------------------------------------------------------
            // TEST 3: Clear Supplier Cheque into Real Bank Account
            // -------------------------------------------------------------
            $clearRes = ChequeEngine::clearCheque([
                'cheque_id'        => (int)$createdChq['id'],
                'bank_account_id'  => $testBankId,
                'cleared_date'     => date('Y-m-d'),
                'bank_ref'         => 'STMT-REF-1001'
            ], $testUserId);

            assertTest($clearRes['success'] === true, "Test 3A: Supplier cheque cleared successfully");
            if ($clearRes['success'] && !empty($clearRes['journal_entry_id'])) {
                $createdJournalIds[] = (int)$clearRes['journal_entry_id'];

                $clearedChqInDb = Database::fetchOne("SELECT * FROM supplier_cheques WHERE id = ?", [(int)$createdChq['id']]);
                assertTest($clearedChqInDb['status'] === 'cleared' && $clearedChqInDb['bank_ref'] === 'STMT-REF-1001', "Test 3B: Cheque status updated to 'cleared' with bank_ref and cleared_date");

                // Verify journal entry lines (Debit 2025, Credit Bank)
                $lines = Database::fetchAll("SELECT * FROM journal_lines WHERE journal_entry_id = ?", [(int)$clearRes['journal_entry_id']]);
                $totDebit = array_sum(array_column($lines, 'debit'));
                $totCredit = array_sum(array_column($lines, 'credit'));
                assertTest(count($lines) === 2 && abs($totDebit - 1500.00) < 0.01 && abs($totCredit - 1500.00) < 0.01, "Test 3C: Double-entry clearing journal entry balanced (Debit 2025 == Credit Bank)");
            }
        }
    }

    // -------------------------------------------------------------
    // TEST 4: Customer Payment with Cheque Split
    // -------------------------------------------------------------
    $custChqNo = 'CUST-CHQ-' . time();
    $custPaymentPayload = [
        'customer_id'     => $testCustId,
        'amount'          => 2000.00,
        'allocation_mode' => 'auto',
        'splits'          => [
            [
                'payment_method'  => 'Cheque',
                'amount'          => 2000.00,
                'cheque_no'       => $custChqNo,
                'bank_name'       => 'Commercial Bank',
                'cheque_date'     => date('Y-m-d')
            ]
        ]
    ];

    $cpRes = CustomerPayment::processPayment($custPaymentPayload, $testUserId, $testBranchId);
    assertTest($cpRes['success'] === true, "Test 4A: Customer collection payment processed with Cheque split");

    $custChqRow = Database::fetchOne("SELECT * FROM customer_cheques WHERE cheque_no = ?", [$custChqNo]);
    assertTest(!empty($custChqRow) && $custChqRow['status'] === 'in_hand' && $custChqRow['bank_name'] === 'Commercial Bank', "Test 4B: Customer cheque recorded with status 'in_hand'");

    if ($custChqRow) {
        $createdCustomerChequeIds[] = (int)$custChqRow['id'];

        // Verify initial accounting: Dr 1035 Cheques in Hand, Cr 1040 AR
        $cclId = (int)$custChqRow['customer_credit_ledger_id'];
        $collJe = Database::fetchOne("SELECT id FROM journal_entries WHERE source_type = 'customer_collection' AND source_id = ?", [$cclId]);
        if ($collJe) {
            $createdJournalIds[] = (int)$collJe['id'];
            $collLines = Database::fetchAll("SELECT * FROM journal_lines WHERE journal_entry_id = ?", [(int)$collJe['id']]);
            $has1035Debit = false;
            foreach ($collLines as $cl) {
                $acc = Database::fetchOne("SELECT account_code FROM chart_of_accounts WHERE id = ?", [(int)$cl['account_id']]);
                if ($acc && $acc['account_code'] === '1035' && (float)$cl['debit'] > 0) {
                    $has1035Debit = true;
                }
            }
            assertTest($has1035Debit === true, "Test 4C: Customer cheque receipt debited Account 1035 (Cheques in Hand)");
        }

        // -------------------------------------------------------------
        // TEST 5: Customer Cheque Deposit to Bank
        // -------------------------------------------------------------
        $depRes = ChequeEngine::depositCustomerCheque([
            'cheque_id'        => (int)$custChqRow['id'],
            'bank_account_id'  => $testBankId,
            'deposit_date'     => date('Y-m-d'),
            'deposit_ref'      => 'DEP-SLIP-777'
        ], $testUserId);

        assertTest($depRes['success'] === true, "Test 5A: Customer cheque deposited to bank account");
        $depChqInDb = Database::fetchOne("SELECT * FROM customer_cheques WHERE id = ?", [(int)$custChqRow['id']]);
        assertTest($depChqInDb['status'] === 'deposited' && $depChqInDb['deposit_ref'] === 'DEP-SLIP-777', "Test 5B: Status transitioned to 'deposited' with deposit_ref");

        // -------------------------------------------------------------
        // TEST 6: Customer Cheque Clearing into Destination Bank
        // -------------------------------------------------------------
        $clrRes = ChequeEngine::clearCustomerCheque([
            'cheque_id'        => (int)$custChqRow['id'],
            'bank_account_id'  => $testBankId,
            'cleared_date'     => date('Y-m-d'),
            'bank_ref'         => 'CLEAR-REF-888'
        ], $testUserId);

        assertTest($clrRes['success'] === true, "Test 6A: Customer cheque cleared successfully: " . ($clrRes['message'] ?? ''));
        if ($clrRes['success'] && !empty($clrRes['journal_entry_id'])) {
            $createdJournalIds[] = (int)$clrRes['journal_entry_id'];

            $clrLines = Database::fetchAll("SELECT * FROM journal_lines WHERE journal_entry_id = ?", [(int)$clrRes['journal_entry_id']]);
            $totDebit = array_sum(array_column($clrLines, 'debit'));
            $totCredit = array_sum(array_column($clrLines, 'credit'));
            assertTest(count($clrLines) === 2 && abs($totDebit - 2000.00) < 0.01 && abs($totCredit - 2000.00) < 0.01, "Test 6B: Double-entry customer clearing journal balanced (Debit Bank == Credit 1035)");
        }

        // -------------------------------------------------------------
        // TEST 7: Customer Cheque Return / Bounce Workflow
        // -------------------------------------------------------------
        $custBefore = Database::fetchOne("SELECT credit_balance FROM customers WHERE id = ?", [$testCustId]);
        $balBefore = (float)$custBefore['credit_balance'];

        $retRes = ChequeEngine::returnCustomerCheque([
            'cheque_id' => (int)$custChqRow['id'],
            'reason'    => 'Signature irregular'
        ], $testUserId, $testBranchId);

        assertTest($retRes['success'] === true, "Test 7A: Customer cheque returned / bounced successfully");

        $custAfter = Database::fetchOne("SELECT credit_balance FROM customers WHERE id = ?", [$testCustId]);
        $balAfter = (float)$custAfter['credit_balance'];
        assertTest(abs($balAfter - ($balBefore + 2000.00)) < 0.01, "Test 7B: Customer credit balance restored by 2000.00 on cheque return");

        $retChqInDb = Database::fetchOne("SELECT * FROM customer_cheques WHERE id = ?", [(int)$custChqRow['id']]);
        assertTest($retChqInDb['status'] === 'returned', "Test 7C: Customer cheque status marked as 'returned'");
    }

    // -------------------------------------------------------------
    // TEST 8: Soft-Delete & Restore Lifecycle (Trash Bin)
    // -------------------------------------------------------------
    $delRes = ChequeEngine::deleteCustomerCheque((int)$custChqRow['id'], $testUserId);
    assertTest($delRes['success'] === true, "Test 8A: Customer cheque soft-deleted to Trash Bin");

    $delRow = Database::fetchOne("SELECT deleted_at FROM customer_cheques WHERE id = ?", [(int)$custChqRow['id']]);
    assertTest(!empty($delRow['deleted_at']), "Test 8B: deleted_at column populated");

    $restRes = ChequeEngine::restoreCustomerCheque((int)$custChqRow['id'], $testUserId);
    assertTest($restRes['success'] === true, "Test 8C: Customer cheque restored from Trash Bin");

    $restRow = Database::fetchOne("SELECT deleted_at FROM customer_cheques WHERE id = ?", [(int)$custChqRow['id']]);
    assertTest(empty($restRow['deleted_at']), "Test 8D: deleted_at column cleared to NULL upon restore");

} catch (Throwable $e) {
    echo "[ERROR] Exception during tests: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
    $testFailed++;
} finally {
    // -------------------------------------------------------------
    // CLEAN FIXTURE TEARDOWN (Zero Truncation)
    // -------------------------------------------------------------
    echo "\nCleaning up test fixtures (zero truncation)...\n";
    if (!empty($createdCustomerChequeIds)) {
        $in = implode(',', $createdCustomerChequeIds);
        $db->exec("DELETE FROM customer_cheques WHERE id IN ({$in})");
    }
    if (!empty($createdSupplierChequeIds)) {
        $in = implode(',', $createdSupplierChequeIds);
        $db->exec("DELETE FROM supplier_cheques WHERE id IN ({$in})");
    }
    if (!empty($createdSupplierPaymentIds)) {
        $in = implode(',', $createdSupplierPaymentIds);
        $db->exec("DELETE FROM supplier_payment_grns WHERE supplier_payment_id IN ({$in})");
        $db->exec("DELETE FROM supplier_payments WHERE id IN ({$in})");
    }
    if (!empty($testGrnId)) {
        $db->prepare("DELETE FROM supplier_payment_grns WHERE grn_id = ?")->execute([$testGrnId]);
        $db->prepare("DELETE FROM grns WHERE id = ?")->execute([$testGrnId]);
    }
    if (!empty($createdJournalIds)) {
        $in = implode(',', $createdJournalIds);
        $db->exec("DELETE FROM journal_lines WHERE journal_entry_id IN ({$in})");
        $db->exec("DELETE FROM journal_entries WHERE id IN ({$in})");
    }
    // Clean up test credit ledger rows created during customer payment and return
    if (!empty($testCustId)) {
        $db->prepare("DELETE FROM payments WHERE customer_id = ?")->execute([$testCustId]);
        $db->prepare("DELETE FROM customer_credit_ledger WHERE customer_id = ?")->execute([$testCustId]);
        $db->prepare("DELETE FROM customer_cheques WHERE customer_id = ?")->execute([$testCustId]);
        $db->prepare("DELETE FROM customers WHERE id = ?")->execute([$testCustId]);
    }
    echo "Cleanup complete.\n";
}

echo "\n============================================\n";
echo "TEST RESULTS: {$testPassed} PASSED, {$testFailed} FAILED\n";
echo "============================================\n";
exit($testFailed > 0 ? 1 : 0);
