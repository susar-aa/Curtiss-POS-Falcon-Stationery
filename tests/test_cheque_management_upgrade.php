<?php
/**
 * CLI Automated Test Suite: Cheque Management Upgrade
 * Tests supplier cheque issuance, duplicate validation, clearance,
 * customer cheque collection (in_hand -> deposited -> cleared -> returned),
 * Chart of Accounts double-entry journal postings, and balance restorations.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/ChequeEngine.php';
require_once __DIR__ . '/../core/CustomerPayment.php';
require_once __DIR__ . '/../core/SupplierPayment.php';
require_once __DIR__ . '/../core/Banking.php';
require_once __DIR__ . '/../core/Accounting.php';

echo "========================================================\n";
echo "  CLI TEST SUITE: CHEQUE ISSUING, RECEIVING & MANAGEMENT\n";
echo "========================================================\n\n";

$db = Database::getInstance()->getConnection();
$testRunId = time();
$createdIds = [
    'customer_ids'       => [],
    'supplier_ids'       => [],
    'customer_cheque_ids'=> [],
    'supplier_cheque_ids'=> [],
    'journal_entry_ids'  => [],
    'ccl_ids'            => []
];

$passCount = 0;
$failCount = 0;

function assertTest(bool $condition, string $testName) {
    global $passCount, $failCount;
    if ($condition) {
        echo "  [PASS] {$testName}\n";
        $passCount++;
    } else {
        echo "  [FAIL] {$testName}\n";
        $failCount++;
    }
}

try {
    // 0. Setup test branch, user, bank account
    $branchId = 1;
    $userId = 1;
    
    // Ensure active bank account
    $bankAccount = Database::fetchOne("SELECT * FROM bank_accounts WHERE status = 'active' ORDER BY id ASC LIMIT 1");
    if (!$bankAccount) {
        throw new Exception("No active bank account found for testing.");
    }
    $bankAccountId = (int)$bankAccount['id'];
    echo "Using Bank Account: #{$bankAccountId} ({$bankAccount['bank_name']} - {$bankAccount['account_number']})\n\n";

    // -------------------------------------------------------------------------
    // TEST 1: Chart of Accounts Verification
    // -------------------------------------------------------------------------
    echo "--- 1. Testing Chart of Accounts Seed --- \n";
    $acc1035 = Database::fetchOne("SELECT * FROM chart_of_accounts WHERE account_code = '1035'");
    assertTest(!empty($acc1035), "Account 1035 (Cheques in Hand) exists in chart_of_accounts");
    assertTest(strtolower($acc1035['account_type'] ?? '') === 'asset', "Account 1035 type is 'asset'");

    $acc2025 = Database::fetchOne("SELECT * FROM chart_of_accounts WHERE account_code = '2025'");
    assertTest(!empty($acc2025), "Account 2025 (Cheques Issued Outstanding) exists in chart_of_accounts");

    // -------------------------------------------------------------------------
    // TEST 2: Supplier Cheque Duplicate Validation & Clearance
    // -------------------------------------------------------------------------
    echo "\n--- 2. Testing Supplier Cheque & Duplicate Validation --- \n";
    
    // Create test supplier
    $supName = "Test Supplier {$testRunId}";
    $db->prepare("INSERT INTO suppliers (name, supplier_code, phone, status, created_at) VALUES (:n, :code, '0771234567', 'active', NOW())")
       ->execute([':n' => $supName, ':code' => "SUP-{$testRunId}"]);
    $supplierId = (int)$db->lastInsertId();
    $createdIds['supplier_ids'][] = $supplierId;

    $chqNumber1 = "CHQ-SUP-{$testRunId}";
    
    // Check duplicate check when empty
    $isDupBefore = ChequeEngine::checkDuplicateChequeNo($bankAccountId, $chqNumber1);
    assertTest(!$isDupBefore, "Duplicate cheque check returns FALSE for unused cheque number");

    // Insert pending supplier cheque
    $db->prepare("
        INSERT INTO supplier_cheques (
            supplier_id, branch_id, bank_account_id, cheque_number, cheque_date, amount, status, bank_name, created_by, created_at
        ) VALUES (
            :sid, :bid, :baid, :cno, :cdate, :amt, 'pending', :bname, :uid, NOW()
        )
    ")->execute([
        ':sid'   => $supplierId,
        ':bid'   => $branchId,
        ':baid'  => $bankAccountId,
        ':cno'   => $chqNumber1,
        ':cdate' => date('Y-m-d'),
        ':amt'   => 25000.00,
        ':bname' => $bankAccount['bank_name'],
        ':uid'   => $userId
    ]);
    $suppChequeId = (int)$db->lastInsertId();
    $createdIds['supplier_cheque_ids'][] = $suppChequeId;

    $isDupAfter = ChequeEngine::checkDuplicateChequeNo($bankAccountId, $chqNumber1);
    assertTest($isDupAfter, "Duplicate cheque check returns TRUE for existing pending cheque number");

    $isDupExclude = ChequeEngine::checkDuplicateChequeNo($bankAccountId, $chqNumber1, $suppChequeId);
    assertTest(!$isDupExclude, "Duplicate cheque check excludes the current cheque ID when updating");

    // Clear the supplier cheque
    $clrRes = ChequeEngine::clearCheque([
        'cheque_id'       => $suppChequeId,
        'bank_account_id' => $bankAccountId,
        'cleared_date'    => date('Y-m-d'),
        'bank_ref'        => "CLR-REF-{$testRunId}"
    ], $userId);

    assertTest($clrRes['success'] === true, "Supplier cheque cleared successfully: " . ($clrRes['message'] ?? ''));

    // Verify status updated in DB
    $updatedSuppChq = Database::fetchOne("SELECT * FROM supplier_cheques WHERE id = :id", [':id' => $suppChequeId]);
    assertTest($updatedSuppChq['status'] === 'cleared', "Supplier cheque status updated to 'cleared'");
    assertTest($updatedSuppChq['cleared_date'] === date('Y-m-d'), "Supplier cheque cleared_date correctly saved");
    assertTest($updatedSuppChq['bank_ref'] === "CLR-REF-{$testRunId}", "Supplier cheque bank_ref correctly saved");
    assertTest(!empty($updatedSuppChq['journal_entry_id']), "Journal entry ID linked to supplier cheque");

    if (!empty($updatedSuppChq['journal_entry_id'])) {
        $createdIds['journal_entry_ids'][] = (int)$updatedSuppChq['journal_entry_id'];
        $je = Database::fetchOne("SELECT * FROM journal_entries WHERE id = :id", [':id' => $updatedSuppChq['journal_entry_id']]);
        assertTest((float)$je['total_debit'] == 25000.00 && (float)$je['total_credit'] == 25000.00, "Clearing journal entry is balanced (Debit = Credit = 25000.00)");
        
        $lines = Database::fetchAll("SELECT * FROM journal_lines WHERE journal_entry_id = :jid ORDER BY id ASC", [':jid' => $je['id']]);
        $chqIssuedAccId = Accounting::getAccountIdByCode(Accounting::CODE_CHEQUES_ISSUED);
        assertTest((int)$lines[0]['account_id'] === $chqIssuedAccId && (float)$lines[0]['debit'] == 25000.00, "Journal Line 1 Debits Account 2025 (Cheques Issued)");
        assertTest((float)$lines[1]['credit'] == 25000.00, "Journal Line 2 Credits Bank Account COA");
    }

    // -------------------------------------------------------------------------
    // TEST 3: Customer Cheque Collection (In-Hand -> Deposited -> Cleared)
    // -------------------------------------------------------------------------
    echo "\n--- 3. Testing Customer Cheque Collection Lifecycle --- \n";

    // Create test customer with debt
    $custName = "Test Cheque Customer {$testRunId}";
    $db->prepare("
        INSERT INTO customers (name, customer_code, phone, credit_balance, status, created_at)
        VALUES (:n, :code, '0719876543', 50000.00, 'active', NOW())
    ")->execute([
        ':n'    => $custName,
        ':code' => "CUS-{$testRunId}"
    ]);
    $customerId = (int)$db->lastInsertId();
    $createdIds['customer_ids'][] = $customerId;

    $custChqNo1 = "CHQ-CUS-A-{$testRunId}";

    // Receive Customer Cheque
    $payRes1 = CustomerPayment::processPayment([
        'customer_id'     => $customerId,
        'amount'          => 15000.00,
        'notes'           => 'Test customer cheque collection',
        'splits'          => [
            [
                'payment_method'  => 'Cheque',
                'amount'          => 15000.00,
                'cheque_no'       => $custChqNo1,
                'bank_name'       => 'Commercial Bank',
                'cheque_date'     => date('Y-m-d')
            ]
        ]
    ], $userId, $branchId);

    assertTest($payRes1['success'] === true, "Customer cheque payment processed: " . ($payRes1['message'] ?? ''));

    // Verify customer balance reduced
    $updatedCust1 = Database::fetchOne("SELECT credit_balance FROM customers WHERE id = :id", [':id' => $customerId]);
    assertTest((float)$updatedCust1['credit_balance'] == 35000.00, "Customer credit balance decreased by 15000 to 35000.00");

    // Verify customer_cheques record created in_hand
    $custChq1 = Database::fetchOne("SELECT * FROM customer_cheques WHERE cheque_no = :cno", [':cno' => $custChqNo1]);
    assertTest(!empty($custChq1), "Customer cheque record created in customer_cheques table");
    assertTest(($custChq1['status'] ?? '') === 'in_hand', "Customer cheque status is 'in_hand'");
    assertTest((float)$custChq1['amount'] == 15000.00, "Customer cheque amount is 15000.00");
    assertTest(($custChq1['bank_name'] ?? '') === 'Commercial Bank', "Cheque bank is recorded as 'Commercial Bank'");
    $custChqId1 = (int)$custChq1['id'];
    $createdIds['customer_cheque_ids'][] = $custChqId1;
    if (!empty($custChq1['customer_credit_ledger_id'])) {
        $createdIds['ccl_ids'][] = (int)$custChq1['customer_credit_ledger_id'];
    }

    // Check collection journal entry (Dr 1035 Cheques in Hand, Cr 1040 AR)
    $ccl1 = Database::fetchOne("SELECT * FROM customer_credit_ledger WHERE id = :id", [':id' => $custChq1['customer_credit_ledger_id']]);
    $je1 = Database::fetchOne("SELECT * FROM journal_entries WHERE source_type = 'customer_collection' AND source_id = :sid", [':sid' => $ccl1['id']]);
    if ($je1) {
        $createdIds['journal_entry_ids'][] = (int)$je1['id'];
        $jLines = Database::fetchAll("SELECT * FROM journal_lines WHERE journal_entry_id = :jid ORDER BY id ASC", [':jid' => $je1['id']]);
        $chqInHandAccId = Accounting::getAccountIdByCode(Accounting::CODE_CHEQUES_HAND);
        $arAccId = Accounting::getAccountIdByCode(Accounting::CODE_CUSTOMER_RECEIVABLE);
        assertTest((int)$jLines[0]['account_id'] === $chqInHandAccId && (float)$jLines[0]['debit'] == 15000.00, "Collection Journal Debits Account 1035 (Cheques in Hand) for 15000.00");
        assertTest((int)$jLines[1]['account_id'] === $arAccId && (float)$jLines[1]['credit'] == 15000.00, "Collection Journal Credits Account 1040 (Customer Receivables) for 15000.00");
    }

    // Deposit the customer cheque
    $depRes = ChequeEngine::depositCustomerCheque([
        'cheque_id'       => $custChqId1,
        'bank_account_id' => $bankAccountId,
        'deposit_date'    => date('Y-m-d'),
        'deposit_ref'     => "DEP-SLIP-{$testRunId}"
    ], $userId);

    assertTest($depRes['success'] === true, "Customer cheque deposited: " . ($depRes['message'] ?? ''));
    $depositedChq = Database::fetchOne("SELECT * FROM customer_cheques WHERE id = :id", [':id' => $custChqId1]);
    assertTest($depositedChq['status'] === 'deposited', "Customer cheque status updated to 'deposited'");
    assertTest((int)$depositedChq['bank_account_id'] === $bankAccountId, "Customer cheque destination bank_account_id saved");
    assertTest($depositedChq['deposit_ref'] === "DEP-SLIP-{$testRunId}", "Customer cheque deposit_ref saved");

    // Clear the customer cheque
    $clearCustRes = ChequeEngine::clearCustomerCheque([
        'cheque_id'    => $custChqId1,
        'cleared_date' => date('Y-m-d'),
        'bank_ref'     => "BNK-CLR-{$testRunId}"
    ], $userId);

    assertTest($clearCustRes['success'] === true, "Customer cheque cleared: " . ($clearCustRes['message'] ?? ''));
    $clearedCustChq = Database::fetchOne("SELECT * FROM customer_cheques WHERE id = :id", [':id' => $custChqId1]);
    assertTest($clearedCustChq['status'] === 'cleared', "Customer cheque status updated to 'cleared'");
    assertTest(!empty($clearedCustChq['journal_entry_id']), "Clearing journal entry ID recorded on customer cheque");

    if (!empty($clearedCustChq['journal_entry_id'])) {
        $createdIds['journal_entry_ids'][] = (int)$clearedCustChq['journal_entry_id'];
        $jeClear = Database::fetchOne("SELECT * FROM journal_entries WHERE id = :id", [':id' => $clearedCustChq['journal_entry_id']]);
        assertTest((float)$jeClear['total_debit'] == 15000.00 && (float)$jeClear['total_credit'] == 15000.00, "Customer Clearing Journal is balanced (15000.00)");
        
        $linesClear = Database::fetchAll("SELECT * FROM journal_lines WHERE journal_entry_id = :jid ORDER BY id ASC", [':jid' => $jeClear['id']]);
        $bankAccCoaId = (int)$bankAccount['account_id'];
        $chqInHandAccId = Accounting::getAccountIdByCode(Accounting::CODE_CHEQUES_HAND);
        assertTest((int)$linesClear[0]['account_id'] === $bankAccCoaId && (float)$linesClear[0]['debit'] == 15000.00, "Clearing Journal Debits Destination Bank Account COA for 15000.00");
        assertTest((int)$linesClear[1]['account_id'] === $chqInHandAccId && (float)$linesClear[1]['credit'] == 15000.00, "Clearing Journal Credits Account 1035 (Cheques in Hand) for 15000.00");
    }

    // -------------------------------------------------------------------------
    // TEST 4: Customer Cheque Return / Bounce & AR Restoration
    // -------------------------------------------------------------------------
    echo "\n--- 4. Testing Customer Cheque Return & Balance Restoration --- \n";

    $custChqNo2 = "CHQ-CUS-B-{$testRunId}";
    $payRes2 = CustomerPayment::processPayment([
        'customer_id'     => $customerId,
        'amount'          => 10000.00,
        'notes'           => 'Test customer cheque for return test',
        'splits'          => [
            [
                'payment_method'  => 'Cheque',
                'amount'          => 10000.00,
                'cheque_no'       => $custChqNo2,
                'bank_name'       => 'Bank of Ceylon',
                'cheque_date'     => date('Y-m-d')
            ]
        ]
    ], $userId, $branchId);

    assertTest($payRes2['success'] === true, "Second customer cheque payment processed: 10000.00");
    $custChq2 = Database::fetchOne("SELECT * FROM customer_cheques WHERE cheque_no = :cno", [':cno' => $custChqNo2]);
    $custChqId2 = (int)$custChq2['id'];
    $createdIds['customer_cheque_ids'][] = $custChqId2;
    if (!empty($custChq2['customer_credit_ledger_id'])) {
        $createdIds['ccl_ids'][] = (int)$custChq2['customer_credit_ledger_id'];
    }

    // Verify balance was reduced to 25000
    $custBeforeReturn = Database::fetchOne("SELECT credit_balance FROM customers WHERE id = :id", [':id' => $customerId]);
    assertTest((float)$custBeforeReturn['credit_balance'] == 25000.00, "Customer balance reduced to 25000.00 before return");

    // Now Return / Bounce the cheque
    $retRes = ChequeEngine::returnCustomerCheque([
        'cheque_id' => $custChqId2,
        'reason'    => 'Insufficient funds in customer account'
    ], $userId, $branchId);

    assertTest($retRes['success'] === true, "Cheque successfully returned: " . ($retRes['message'] ?? ''));

    // Verify customer balance restored back to 35000
    $custAfterReturn = Database::fetchOne("SELECT credit_balance FROM customers WHERE id = :id", [':id' => $customerId]);
    assertTest((float)$custAfterReturn['credit_balance'] == 35000.00, "Customer credit balance restored back to 35000.00 (+10000)");

    // Verify cheque status is returned
    $returnedChq = Database::fetchOne("SELECT * FROM customer_cheques WHERE id = :id", [':id' => $custChqId2]);
    assertTest($returnedChq['status'] === 'returned', "Cheque status updated to 'returned'");
    assertTest($returnedChq['return_reason'] === 'Insufficient funds in customer account', "Return reason recorded");

    // Verify customer_credit_ledger entry of type cheque_return
    $retCcl = Database::fetchOne("SELECT * FROM customer_credit_ledger WHERE customer_id = :cid AND transaction_type = 'cheque_return' ORDER BY id DESC LIMIT 1", [':cid' => $customerId]);
    assertTest(!empty($retCcl), "Cheque return ledger entry created in customer_credit_ledger");
    assertTest((float)$retCcl['amount_due'] == 10000.00, "Ledger amount_due is 10000.00");
    if ($retCcl) {
        $createdIds['ccl_ids'][] = (int)$retCcl['id'];
        $jeRet = Database::fetchOne("SELECT * FROM journal_entries WHERE source_type = 'customer_cheque_return' AND source_id = :sid", [':sid' => $custChqId2]);
        if ($jeRet) {
            $createdIds['journal_entry_ids'][] = (int)$jeRet['id'];
            assertTest((float)$jeRet['total_debit'] == 10000.00 && (float)$jeRet['total_credit'] == 10000.00, "Reversal journal entry balanced (10000.00)");
        }
    }

    // -------------------------------------------------------------------------
    // TEST 5: Soft-Delete Trash Bin & Restore
    // -------------------------------------------------------------------------
    echo "\n--- 5. Testing Soft-Delete Trash Bin & Restore --- \n";
    
    // Create an in-hand customer cheque to delete & restore
    $custChqNo3 = "CHQ-CUS-DEL-{$testRunId}";
    $db->prepare("
        INSERT INTO customer_cheques (
            cheque_no, customer_id, branch_id, bank_name, cheque_date, received_date, amount, status, created_by, created_at
        ) VALUES (
            :cno, :cid, :bid, 'HNB', :cdate, :rdate, 5000.00, 'in_hand', :uid, NOW()
        )
    ")->execute([
        ':cno'   => $custChqNo3,
        ':cid'   => $customerId,
        ':bid'   => $branchId,
        ':cdate' => date('Y-m-d'),
        ':rdate' => date('Y-m-d'),
        ':uid'   => $userId
    ]);
    $delChqId = (int)$db->lastInsertId();
    $createdIds['customer_cheque_ids'][] = $delChqId;

    // Delete
    $delRes = ChequeEngine::deleteCustomerCheque($delChqId, $userId);
    assertTest($delRes['success'] === true, "Customer cheque moved to trash bin (soft-deleted)");
    $checkDeleted = Database::fetchOne("SELECT deleted_at FROM customer_cheques WHERE id = :id", [':id' => $delChqId]);
    assertTest(!empty($checkDeleted['deleted_at']), "deleted_at is NOT NULL on soft-deleted cheque");

    // Restore
    $restRes = ChequeEngine::restoreCustomerCheque($delChqId, $userId);
    assertTest($restRes['success'] === true, "Customer cheque restored from trash bin");
    $checkRestored = Database::fetchOne("SELECT deleted_at FROM customer_cheques WHERE id = :id", [':id' => $delChqId]);
    assertTest($checkRestored['deleted_at'] === null, "deleted_at is NULL after restoration");

    // -------------------------------------------------------------------------
    // TEST 6: Paginated Multi-Column Queries
    // -------------------------------------------------------------------------
    echo "\n--- 6. Testing Paginated Queries & Search Filters --- \n";
    $pagRes = ChequeEngine::getPaginatedCustomerCheques([
        'branch_id' => $branchId,
        'search'    => 'Commercial Bank',
        'view'      => 'active'
    ], 1, 25);
    assertTest(!empty($pagRes['items']), "Customer cheques search by bank name found matching rows");

    $pagAmtRes = ChequeEngine::getPaginatedCustomerCheques([
        'branch_id' => $branchId,
        'search'    => '15,000.00',
        'view'      => 'active'
    ], 1, 25);
    assertTest(!empty($pagAmtRes['items']), "Customer cheques search by formatted amount '15,000.00' found matching rows");

} catch (Throwable $e) {
    echo "\n[ERROR] Exception encountered during test execution: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    $failCount++;
} finally {
    // -------------------------------------------------------------------------
    // CLEAN TEARDOWN (Zero Truncation - Remove ONLY created test records)
    // -------------------------------------------------------------------------
    echo "\n--- 7. Clean Fixture Teardown (Zero Truncation) --- \n";

    if (!empty($createdIds['journal_entry_ids'])) {
        $jidList = implode(',', array_map('intval', $createdIds['journal_entry_ids']));
        $db->exec("DELETE FROM journal_lines WHERE journal_entry_id IN ({$jidList})");
        $db->exec("DELETE FROM journal_entries WHERE id IN ({$jidList})");
        echo "Cleaned up " . count($createdIds['journal_entry_ids']) . " test journal entries.\n";
    }

    if (!empty($createdIds['supplier_cheque_ids'])) {
        $scidList = implode(',', array_map('intval', $createdIds['supplier_cheque_ids']));
        $db->exec("DELETE FROM supplier_cheques WHERE id IN ({$scidList})");
        echo "Cleaned up " . count($createdIds['supplier_cheque_ids']) . " test supplier cheques.\n";
    }

    if (!empty($createdIds['customer_cheque_ids'])) {
        $ccidList = implode(',', array_map('intval', $createdIds['customer_cheque_ids']));
        $db->exec("DELETE FROM customer_cheques WHERE id IN ({$ccidList})");
        echo "Cleaned up " . count($createdIds['customer_cheque_ids']) . " test customer cheques.\n";
    }

    if (!empty($createdIds['ccl_ids'])) {
        $cclidList = implode(',', array_map('intval', $createdIds['ccl_ids']));
        $db->exec("DELETE FROM customer_credit_ledger WHERE id IN ({$cclidList})");
        echo "Cleaned up " . count($createdIds['ccl_ids']) . " test customer credit ledger rows.\n";
    }

    if (!empty($createdIds['customer_ids'])) {
        $cidList = implode(',', array_map('intval', $createdIds['customer_ids']));
        $db->exec("DELETE FROM customers WHERE id IN ({$cidList})");
        echo "Cleaned up " . count($createdIds['customer_ids']) . " test customers.\n";
    }

    if (!empty($createdIds['supplier_ids'])) {
        $sidList = implode(',', array_map('intval', $createdIds['supplier_ids']));
        $db->exec("DELETE FROM suppliers WHERE id IN ({$sidList})");
        echo "Cleaned up " . count($createdIds['supplier_ids']) . " test suppliers.\n";
    }

    echo "\n========================================================\n";
    echo "  TEST SUMMARY: {$passCount} PASSED | {$failCount} FAILED\n";
    echo "========================================================\n";

    if ($failCount > 0) {
        exit(1);
    }
}
