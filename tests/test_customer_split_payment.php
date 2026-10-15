<?php
/**
 * Test Customer Split Payment Processing
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/CustomerPayment.php';
require_once __DIR__ . '/../core/Accounting.php';

echo "=== START CUSTOMER SPLIT PAYMENT CLI TEST ===\n";

// Set session user
$_SESSION['user_id'] = 1;
$_SESSION['branch_id'] = 1;

$db = Database::getInstance()->getConnection();

// Ensure customer_cheques table exists
$db->exec("CREATE TABLE IF NOT EXISTS customer_cheques (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cheque_no VARCHAR(100) NOT NULL,
    customer_id INT UNSIGNED NOT NULL,
    branch_id INT UNSIGNED NOT NULL,
    bank_name VARCHAR(100) NULL,
    cheque_date DATE NOT NULL,
    received_date DATE NOT NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    payment_no VARCHAR(100) NULL,
    customer_credit_ledger_id BIGINT UNSIGNED NULL,
    bank_account_id INT UNSIGNED NULL,
    status ENUM('in_hand','deposited','cleared','returned','cancelled') NOT NULL DEFAULT 'in_hand',
    return_reason VARCHAR(255) NULL,
    returned_at DATETIME NULL,
    returned_by INT UNSIGNED NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cust_chq_cust (customer_id),
    INDEX idx_cust_chq_branch (branch_id),
    INDEX idx_cust_chq_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Pre-cleanup any previous test records
$db->exec("DELETE FROM payments WHERE customer_id IN (SELECT id FROM customers WHERE customer_code LIKE 'CUST-TEST-%')");
$db->exec("DELETE FROM customer_credit_ledger WHERE customer_id IN (SELECT id FROM customers WHERE customer_code LIKE 'CUST-TEST-%')");
$db->exec("DELETE FROM customer_cheques WHERE customer_id IN (SELECT id FROM customers WHERE customer_code LIKE 'CUST-TEST-%')");
$db->exec("DELETE FROM sales WHERE customer_id IN (SELECT id FROM customers WHERE customer_code LIKE 'CUST-TEST-%')");
$db->exec("DELETE FROM customers WHERE customer_code LIKE 'CUST-TEST-%'");

// 1. Get an active bank account
$bank = Database::fetchOne("SELECT id FROM bank_accounts WHERE status = 'active' LIMIT 1");
$bankId = $bank ? (int)$bank['id'] : null;

// 2. Create isolated test customer
$testCode = 'CUST-TEST-' . time();
$db->prepare("INSERT INTO customers (name, phone, customer_code, credit_balance, credit_limit, status) VALUES (?, ?, ?, ?, ?, 'active')")
   ->execute(["Split Test Customer", "0770000000", $testCode, 3000.00, 10000.00]);
$testCustId = (int)$db->lastInsertId();

// 3. Create isolated test credit invoice
$testInvNo = 'INV-TEST-' . time();
$db->prepare("INSERT INTO sales (invoice_no, branch_id, customer_id, user_id, total_amount, status, created_at) VALUES (?, 1, ?, 1, 3000.00, 'completed', NOW())")
   ->execute([$testInvNo, $testCustId]);
$testSaleId = (int)$db->lastInsertId();

$db->prepare("INSERT INTO payments (payment_no, sale_id, branch_id, customer_id, payment_method, amount, user_id) VALUES (?, ?, 1, ?, 'Credit', 3000.00, 1)")
   ->execute(['PMT-TEST-CREDIT-' . time(), $testSaleId, $testCustId]);

echo "Created test customer #{$testCustId} and credit invoice #{$testSaleId} with LKR 3,000.00 due.\n";

// 4. Test Split Payment Process
$payload = [
    'customer_id'     => $testCustId,
    'amount'          => 3000.00,
    'allocation_mode' => 'auto',
    'notes'           => 'CLI Unit test split payment',
    'splits'          => [
        [
            'payment_method' => 'Cash in Hand',
            'amount'         => 1500.00,
            'reference_no'   => 'CASH-REF'
        ],
        [
            'payment_method'  => 'Bank Transfer',
            'bank_account_id' => $bankId,
            'amount'          => 1000.00,
            'reference_no'    => 'BANK-TX-999'
        ],
        [
            'payment_method' => 'Cheque',
            'amount'         => 500.00,
            'cheque_no'      => 'CHQ-TEST-888',
            'bank_name'      => 'Sampath Bank',
            'cheque_date'    => date('Y-m-d')
        ]
    ]
];

$res = CustomerPayment::processPayment($payload, 1, 1);

echo "Process result: " . json_encode($res) . "\n";

if (!$res['success']) {
    echo "FAILED: " . ($res['message'] ?? 'Unknown error') . "\n";
    // Cleanup
    $db->prepare("DELETE FROM sales WHERE id = ?")->execute([$testSaleId]);
    $db->prepare("DELETE FROM customers WHERE id = ?")->execute([$testCustId]);
    exit(1);
}

// 5. Verify customer balance
$cust = Database::fetchOne("SELECT credit_balance FROM customers WHERE id = ?", [$testCustId]);
echo "Customer balance after payment: LKR " . number_format((float)$cust['credit_balance'], 2) . "\n";
if (abs((float)$cust['credit_balance']) > 0.001) {
    echo "ERROR: Customer balance should be 0.00!\n";
    exit(1);
}

// 6. Verify sale payment allocation
$paidNonCreditRow = Database::fetchOne("SELECT SUM(amount) AS total_paid FROM payments WHERE sale_id = ? AND payment_method != 'Credit'", [$testSaleId]);
$totalPaid = (float)($paidNonCreditRow['total_paid'] ?? 0);
echo "Total non-credit payments allocated to sale: LKR " . number_format($totalPaid, 2) . "\n";
if (abs($totalPaid - 3000.00) > 0.01) {
    echo "ERROR: Sale should have LKR 3,000.00 allocated!\n";
    exit(1);
}

// 7. Verify ledger records count
$ledgerCountRow = Database::fetchOne("SELECT COUNT(*) AS cnt FROM customer_credit_ledger WHERE customer_id = ?", [$testCustId]);
$ledgerCount = $ledgerCountRow['cnt'] ?? 0;
echo "Customer credit ledger entries: {$ledgerCount} (expected 3)\n";
if ((int)$ledgerCount !== 3) {
    echo "ERROR: Expected 3 ledger entries for 3 splits!\n";
    exit(1);
}

// 8. Verify cheque record created
$cheque = Database::fetchOne("SELECT * FROM customer_cheques WHERE customer_id = ? AND cheque_no = 'CHQ-TEST-888'", [$testCustId]);
echo "Cheque created: " . ($cheque ? "YES (Amount: {$cheque['amount']}, Status: {$cheque['status']})" : "NO") . "\n";
if (!$cheque || (float)$cheque['amount'] != 500.00) {
    echo "ERROR: Cheque record not created properly!\n";
    exit(1);
}

// 9. Clean up test records
$db->prepare("DELETE FROM customer_cheques WHERE customer_id = ?")->execute([$testCustId]);
$db->prepare("DELETE FROM payments WHERE customer_id = ?")->execute([$testCustId]);
$db->prepare("DELETE FROM customer_credit_ledger WHERE customer_id = ?")->execute([$testCustId]);
$db->prepare("DELETE FROM sales WHERE id = ?")->execute([$testSaleId]);
$db->prepare("DELETE FROM customers WHERE id = ?")->execute([$testCustId]);

echo "FULL SETTLEMENT TEST PASSED.\n";

// 10. TEST PARTIAL PAYMENT COLLECTION (Customer owes 5,000, pays 2,000, keeps 3,000 as credit)
echo "\n=== TESTING PARTIAL PAYMENT COLLECTION ===\n";
$partialTestCode = 'CUST-TEST-PARTIAL-' . time();
$db->prepare("INSERT INTO customers (name, phone, customer_code, credit_balance, credit_limit, status) VALUES (?, ?, ?, ?, ?, 'active')")
   ->execute(["Partial Test Customer", "0771112222", $partialTestCode, 5000.00, 10000.00]);
$partialCustId = (int)$db->lastInsertId();

$partialSaleInv = 'INV-TEST-P-' . time();
$db->prepare("INSERT INTO sales (invoice_no, branch_id, customer_id, user_id, total_amount, status, created_at) VALUES (?, 1, ?, 1, 5000.00, 'completed', NOW())")
   ->execute([$partialSaleInv, $partialCustId]);
$partialSaleId = (int)$db->lastInsertId();

$db->prepare("INSERT INTO payments (payment_no, sale_id, branch_id, customer_id, payment_method, amount, user_id) VALUES (?, ?, 1, ?, 'Credit', 5000.00, 1)")
   ->execute(['PMT-TEST-CRED-P-' . time(), $partialSaleId, $partialCustId]);

echo "Created partial customer #{$partialCustId} with LKR 5,000.00 outstanding.\n";

$partialPayload = [
    'customer_id'     => $partialCustId,
    'amount'          => 2000.00,
    'allocation_mode' => 'auto',
    'notes'           => 'Partial payment test (Paying 2,000 out of 5,000)',
    'splits'          => [
        [
            'payment_method' => 'Cash in Hand',
            'amount'         => 1200.00,
            'reference_no'   => 'CASH-PARTIAL-1200'
        ],
        [
            'payment_method'  => 'Bank Transfer',
            'bank_account_id' => $bankId,
            'amount'          => 800.00,
            'reference_no'    => 'BANK-PARTIAL-800'
        ]
    ]
];

$partialRes = CustomerPayment::processPayment($partialPayload, 1, 1);
echo "Partial payment result: " . json_encode($partialRes) . "\n";

if (!$partialRes['success']) {
    echo "FAILED PARTIAL: " . ($partialRes['message'] ?? 'Unknown error') . "\n";
    $db->prepare("DELETE FROM sales WHERE id = ?")->execute([$partialSaleId]);
    $db->prepare("DELETE FROM customers WHERE id = ?")->execute([$partialCustId]);
    exit(1);
}

// Verify remaining customer credit debt
$partialCust = Database::fetchOne("SELECT credit_balance FROM customers WHERE id = ?", [$partialCustId]);
$remBal = (float)$partialCust['credit_balance'];
echo "Customer remaining balance after partial payment: LKR " . number_format($remBal, 2) . " (Expected: 3,000.00)\n";
if (abs($remBal - 3000.00) > 0.01) {
    echo "ERROR: Remaining customer balance should be 3,000.00! Got: {$remBal}\n";
    exit(1);
}

// Verify ledger running balance
$latestLedger = Database::fetchOne("SELECT balance_after FROM customer_credit_ledger WHERE customer_id = ? ORDER BY id DESC LIMIT 1", [$partialCustId]);
$ledgerBal = (float)($latestLedger['balance_after'] ?? 0);
echo "Latest ledger running balance: LKR " . number_format($ledgerBal, 2) . " (Expected: 3,000.00)\n";
if (abs($ledgerBal - 3000.00) > 0.01) {
    echo "ERROR: Ledger running balance should be 3,000.00!\n";
    exit(1);
}

// Clean up partial test records
$db->prepare("DELETE FROM customer_cheques WHERE customer_id = ?")->execute([$partialCustId]);
$db->prepare("DELETE FROM payments WHERE customer_id = ?")->execute([$partialCustId]);
$db->prepare("DELETE FROM customer_credit_ledger WHERE customer_id = ?")->execute([$partialCustId]);
$db->prepare("DELETE FROM sales WHERE id = ?")->execute([$partialSaleId]);
$db->prepare("DELETE FROM customers WHERE id = ?")->execute([$partialCustId]);

echo "CLEANUP COMPLETE. ALL TESTS (FULL & PARTIAL PAYMENTS) PASSED!\n";

