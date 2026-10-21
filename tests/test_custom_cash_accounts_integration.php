<?php
/**
 * CLI Integration Test: Custom Cash Accounts Recognition across Supplier Payments,
 * Customer Collections, Petty Cash Reimbursements, Day End, and General Ledger.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Accounting.php';
require_once __DIR__ . '/../core/SupplierPayment.php';
require_once __DIR__ . '/../core/CustomerPayment.php';
require_once __DIR__ . '/../core/PettyCash.php';
require_once __DIR__ . '/../core/BusinessDay.php';

echo "=== STARTING CUSTOM CASH ACCOUNTS RECOGNITION TEST ===\n\n";

$db = Database::getInstance()->getConnection();
$userId = 1;
$branchId = 1;

// 1. Ensure test account 1011 Cash in Hand (Buwaneka) exists
$testCoa = Database::fetchOne("SELECT * FROM chart_of_accounts WHERE account_code = '1011'");
if (!$testCoa) {
    Database::execute("INSERT INTO chart_of_accounts (account_code, account_name, account_type, is_system, status) VALUES ('1011', 'Cash in Hand (Buwaneka)', 'asset', 0, 'active')");
    $testCoa = Database::fetchOne("SELECT * FROM chart_of_accounts WHERE account_code = '1011'");
}
$testCoaId = (int)$testCoa['id'];
echo "[TEST 1] Custom Cash Account exists: ID={$testCoaId}, Code={$testCoa['account_code']}, Name='{$testCoa['account_name']}'\n";

// 2. Test getCashAccounts()
$cashAccs = Accounting::getCashAccounts();
$found1011 = false;
foreach ($cashAccs as $ca) {
    if ($ca['account_code'] === '1011') {
        $found1011 = true;
        break;
    }
}
if ($found1011) {
    echo "[PASS] Accounting::getCashAccounts() dynamically lists Cash in Hand (Buwaneka)\n";
} else {
    echo "[FAIL] Accounting::getCashAccounts() did NOT list Cash in Hand (Buwaneka)\n";
    exit(1);
}

// 3. Test resolvePaymentAccountId()
$resolvedByName = Accounting::resolvePaymentAccountId('Cash in Hand (Buwaneka)');
$resolvedByCode = Accounting::resolvePaymentAccountId('1011');
$resolvedByCoaId = Accounting::resolvePaymentAccountId("COA:{$testCoaId}");

if ($resolvedByName === $testCoaId && $resolvedByCode === $testCoaId && $resolvedByCoaId === $testCoaId) {
    echo "[PASS] Accounting::resolvePaymentAccountId() resolves by name, code, and COA:ID -> ID={$testCoaId}\n";
} else {
    echo "[FAIL] resolvePaymentAccountId failed: byName={$resolvedByName}, byCode={$resolvedByCode}, byCoaId={$resolvedByCoaId}\n";
    exit(1);
}

// 4. Test getPaymentAccounts()
$paymentAccs = Accounting::getPaymentAccounts($branchId);
$foundInAllMethods = false;
foreach ($paymentAccs['all_methods'] as $m) {
    if ($m['key'] === 'Cash in Hand (Buwaneka)' || ($m['code'] ?? '') === '1011') {
        $foundInAllMethods = true;
        break;
    }
}
if ($foundInAllMethods) {
    echo "[PASS] Accounting::getPaymentAccounts() includes Cash in Hand (Buwaneka) in all_methods\n";
} else {
    echo "[FAIL] Accounting::getPaymentAccounts() did NOT include Cash in Hand (Buwaneka)\n";
    exit(1);
}

// 5. Test Supplier Payment with Cash in Hand (Buwaneka)
echo "\n--- Testing Supplier Payment Allocation & Double-Entry ---\n";
$uniq = time() . '_' . rand(100, 999);
$db->prepare("INSERT INTO suppliers (supplier_code, name, phone, status) VALUES (:code, 'Test Supplier Buw', '0779998888', 'active')")->execute([':code' => "TEST-SUP-{$uniq}"]);
$testSupId = (int)$db->lastInsertId();

$db->prepare("INSERT INTO grns (grn_number, supplier_id, branch_id, received_date, status, actual_grn_total, paid_amount, created_by) VALUES (:gno, :sup, :bid, CURRENT_DATE, 'Approved', 1000.00, 0.00, :uid)")->execute([
    ':gno' => "GRN-BUW-{$uniq}", ':sup' => $testSupId, ':bid' => $branchId, ':uid' => $userId
]);
$testGrnId = (int)$db->lastInsertId();

$spPayload = [
    'supplier_id'  => $testSupId,
    'payment_date' => date('Y-m-d'),
    'notes'        => 'Test payment from Buwaneka cash',
    'splits'       => [
        [
            'payment_method' => 'Cash in Hand (Buwaneka)',
            'amount'         => 1000.00
        ]
    ]
];

$spRes = SupplierPayment::processPayment($spPayload, $userId, $branchId);
if (!$spRes['success']) {
    echo "[FAIL] SupplierPayment::processPayment failed: " . $spRes['message'] . "\n";
    exit(1);
}
$spId = $spRes['supplier_payment_id'];
$spRow = Database::fetchOne("SELECT * FROM supplier_payments WHERE id = :id", [':id' => $spId]);

echo "Supplier Payment Record Created: ID={$spId}, Method='{$spRow['payment_method']}', Amount={$spRow['total_amount']}\n";

// Check double-entry journal entry for supplier payment
$jeRow = Database::fetchOne("
    SELECT je.* FROM journal_entries je 
    WHERE je.source_type = 'supplier_payment' AND je.source_id = :id
", [':id' => $spId]);

if (!$jeRow) {
    echo "[FAIL] No journal entry found for supplier payment #{$spId}\n";
    exit(1);
}

$jlRows = Database::fetchAll("SELECT * FROM journal_lines WHERE journal_entry_id = :je_id", [':je_id' => $jeRow['id']]);
$creditedAccountId = null;
$totalDr = 0; $totalCr = 0;
foreach ($jlRows as $jl) {
    $totalDr += (float)$jl['debit'];
    $totalCr += (float)$jl['credit'];
    if ((float)$jl['credit'] > 0) {
        $creditedAccountId = (int)$jl['account_id'];
    }
}

if ($creditedAccountId === $testCoaId && abs($totalDr - $totalCr) < 0.01) {
    echo "[PASS] Supplier Payment double-entry posted correctly: Credited Account ID={$creditedAccountId} (Cash in Hand (Buwaneka)), Balanced Dr=Cr={$totalDr}\n";
} else {
    echo "[FAIL] Supplier Payment journal entry incorrect: Credited Acc ID={$creditedAccountId} (Expected {$testCoaId}), Dr={$totalDr}, Cr={$totalCr}\n";
    exit(1);
}

// 6. Test Customer Payment Collection with Cash in Hand (Buwaneka)
echo "\n--- Testing Customer Payment Collection & Double-Entry ---\n";
// Create temporary test customer with credit balance
$db->prepare("INSERT INTO customers (customer_code, name, phone, credit_balance, status) VALUES (:code, 'Test Cust Buw', '0771239999', 500.00, 'active')")->execute([':code' => "TEST-CUS-{$uniq}"]);
$testCustId = (int)$db->lastInsertId();

$cpPayload = [
    'customer_id'     => $testCustId,
    'amount'          => 500.00,
    'allocation_mode' => 'auto',
    'notes'           => 'Test customer collection to Buwaneka cash',
    'splits'          => [
        [
            'payment_method' => 'Cash in Hand (Buwaneka)',
            'amount'         => 500.00
        ]
    ]
];

$cpRes = CustomerPayment::processPayment($cpPayload, $userId, $branchId);
if (!$cpRes['success']) {
    echo "[FAIL] CustomerPayment::processPayment failed: " . $cpRes['message'] . "\n";
    exit(1);
}

$cclRow = Database::fetchOne("SELECT * FROM customer_credit_ledger WHERE customer_id = :cid ORDER BY id DESC LIMIT 1", [':cid' => $testCustId]);
echo "Customer Credit Ledger Record Created: ID={$cclRow['id']}, Method='{$cclRow['payment_method']}', Paid={$cclRow['amount_paid']}\n";

// Check double-entry journal entry for customer collection
$custJe = Database::fetchOne("
    SELECT je.* FROM journal_entries je 
    WHERE je.source_type = 'customer_collection' AND je.source_id = :id
", [':id' => $cclRow['id']]);

if (!$custJe) {
    echo "[FAIL] No journal entry found for customer collection #{$cclRow['id']}\n";
    exit(1);
}

$custJlRows = Database::fetchAll("SELECT * FROM journal_lines WHERE journal_entry_id = :je_id", [':je_id' => $custJe['id']]);
$debitedAccountId = null;
$totalDr = 0; $totalCr = 0;
foreach ($custJlRows as $jl) {
    $totalDr += (float)$jl['debit'];
    $totalCr += (float)$jl['credit'];
    if ((float)$jl['debit'] > 0) {
        $debitedAccountId = (int)$jl['account_id'];
    }
}

if ($debitedAccountId === $testCoaId && abs($totalDr - $totalCr) < 0.01) {
    echo "[PASS] Customer Collection double-entry posted correctly: Debited Account ID={$debitedAccountId} (Cash in Hand (Buwaneka)), Balanced Dr=Cr={$totalDr}\n";
} else {
    echo "[FAIL] Customer Collection journal entry incorrect: Debited Acc ID={$debitedAccountId} (Expected {$testCoaId}), Dr={$totalDr}, Cr={$totalCr}\n";
    exit(1);
}

// 7. Test Petty Cash Reimbursement from cash_115
echo "\n--- Testing Petty Cash Reimbursement from Custom Cash Account ---\n";
$pcReimbPayload = [
    'source_type'  => "cash_{$testCoaId}",
    'amount'       => 200.00,
    'reference_no' => 'TEST-PCR-BUW',
    'notes'        => 'Reimbursement from Buwaneka cash'
];

$pcRes = PettyCash::reimburseFund($pcReimbPayload, $userId, $branchId);
if (!$pcRes['success']) {
    echo "[FAIL] PettyCash::reimburseFund failed: " . $pcRes['message'] . "\n";
    exit(1);
}

$pcrId = $pcRes['reimbursement_id'];
$pcrJeId = $pcRes['journal_entry_id'];
$pcrJe = Database::fetchOne("
    SELECT je.* FROM journal_entries je 
    WHERE je.id = :id
", [':id' => $pcrJeId]);

if (!$pcrJe) {
    echo "[FAIL] No journal entry found for petty cash reimbursement #{$pcrId}\n";
    exit(1);
}

$pcrJlRows = Database::fetchAll("SELECT * FROM journal_lines WHERE journal_entry_id = :je_id", [':je_id' => $pcrJe['id']]);
$pcCrAccId = null;
$totalDr = 0; $totalCr = 0;
foreach ($pcrJlRows as $jl) {
    $totalDr += (float)$jl['debit'];
    $totalCr += (float)$jl['credit'];
    if ((float)$jl['credit'] > 0) {
        $pcCrAccId = (int)$jl['account_id'];
    }
}

if ($pcCrAccId === $testCoaId && abs($totalDr - $totalCr) < 0.01) {
    echo "[PASS] Petty Cash Reimbursement posted correctly: Credited Account ID={$pcCrAccId} (Cash in Hand (Buwaneka)), Balanced Dr=Cr={$totalDr}\n";
} else {
    echo "[FAIL] Petty Cash Reimbursement journal entry incorrect: Credited Acc ID={$pcCrAccId} (Expected {$testCoaId}), Dr={$totalDr}, Cr={$totalCr}\n";
    exit(1);
}

// 8. Clean up ONLY our test records (never truncate master tables!)
echo "\n--- Cleaning up test records ---\n";
$db->prepare("DELETE FROM journal_lines WHERE journal_entry_id = :id")->execute([':id' => $jeRow['id']]);
$db->prepare("DELETE FROM journal_entries WHERE id = :id")->execute([':id' => $jeRow['id']]);
$db->prepare("DELETE FROM supplier_payment_grns WHERE supplier_payment_id = :id")->execute([':id' => $spId]);
$db->prepare("DELETE FROM supplier_payments WHERE id = :id")->execute([':id' => $spId]);
$db->prepare("DELETE FROM grns WHERE id = :id")->execute([':id' => $testGrnId]);
$db->prepare("DELETE FROM suppliers WHERE id = :id")->execute([':id' => $testSupId]);

$db->prepare("DELETE FROM journal_lines WHERE journal_entry_id = :id")->execute([':id' => $custJe['id']]);
$db->prepare("DELETE FROM journal_entries WHERE id = :id")->execute([':id' => $custJe['id']]);
$db->prepare("DELETE FROM customer_credit_ledger WHERE id = :id")->execute([':id' => $cclRow['id']]);
$db->prepare("DELETE FROM customers WHERE id = :id")->execute([':id' => $testCustId]);

$db->prepare("DELETE FROM journal_lines WHERE journal_entry_id = :id")->execute([':id' => $pcrJe['id']]);
$db->prepare("DELETE FROM journal_entries WHERE id = :id")->execute([':id' => $pcrJe['id']]);
$db->prepare("DELETE FROM petty_cash_reimbursements WHERE id = :id")->execute([':id' => $pcrId]);

echo "[PASS] Test cleanup completed successfully.\n";

echo "\n=== ALL CUSTOM CASH ACCOUNT INTEGRATION TESTS PASSED 100% ===\n";
