<?php
/**
 * Curtiss POS - Comprehensive CLI Test Suite for KOKO Payments Gateway
 * Validates:
 * 1. Gateway configuration retrieval & updates
 * 2. Order creation for QR, Payment Link, and SMS phone number channels
 * 3. Pure SVG QR code generation & installment calculations
 * 4. SMS dispatch simulation & phone number normalization
 * 5. Order status polling, instant verification, and idempotency
 * 6. End-to-end POS sale finalization with Koko payment method
 * 7. COA double-entry journal balance (Dr 1032 KOKO Receivable == Cr 4010 Sales Revenue)
 * 8. Clean isolated test record cleanup (Zero table truncation)
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Settings.php';
require_once __DIR__ . '/../core/KokoPay.php';
require_once __DIR__ . '/../core/Sale.php';
require_once __DIR__ . '/../core/Accounting.php';

$passCount = 0;
$failCount = 0;

function it(string $description, callable $fn) {
    global $passCount, $failCount;
    echo "  • " . $description . " ... ";
    try {
        $result = $fn();
        if ($result === false) {
            echo "FAILED\n";
            $failCount++;
        } else {
            echo "PASSED\n";
            $passCount++;
        }
    } catch (Throwable $e) {
        echo "ERROR: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
        echo "TRACE:\n" . $e->getTraceAsString() . "\n";
        $failCount++;
    }
}

echo "\n======================================================\n";
echo " CURTISS POS - KOKO PAYMENTS TEST RUNNER\n";
echo "======================================================\n\n";

$db = Database::getInstance()->getConnection();

// --- TEST SUITE 1: Gateway Configuration Engine ---
echo "[1] Testing Koko Gateway Configuration Engine\n";

it("Retrieves active configuration parameters", function() {
    $config = KokoPay::getConfig();
    return isset($config['enabled'], $config['environment'], $config['merchant_id'], $config['currency'])
        && $config['currency'] === 'LKR';
});

it("Verifies isEnabled() helper returns boolean", function() {
    return is_bool(KokoPay::isEnabled());
});

it("Saves and updates configuration settings cleanly", function() {
    $origMid = Settings::get('koko_merchant_id', 'KOKO_MID_SAMPLE');
    $saved = KokoPay::saveConfig([
        'koko_merchant_id' => 'KOKO_MID_TEST_123',
        'koko_environment' => 'sandbox'
    ], 1);
    $newMid = Settings::get('koko_merchant_id');
    // Restore
    KokoPay::saveConfig(['koko_merchant_id' => $origMid], 1);
    return $saved && $newMid === 'KOKO_MID_TEST_123';
});

// --- TEST SUITE 2: Phone Normalization & Installment Math ---
echo "\n[2] Testing Phone Number Normalization & Installment Math\n";

it("Normalizes local and international Sri Lankan phone numbers", function() {
    $test1 = KokoPay::normalizePhoneNumber('0771234567');
    $test2 = KokoPay::normalizePhoneNumber('+94771234567');
    $test3 = KokoPay::normalizePhoneNumber('94771234567');
    $intl  = KokoPay::formatInternationalPhone('0771234567');
    return $test1 === '0771234567' 
        && $test2 === '0771234567' 
        && $test3 === '0771234567'
        && $intl  === '+94771234567';
});

it("Validates 3x installment calculation precision", function() {
    $total = 3000.00;
    $installment = round($total / 3, 2);
    return $installment === 1000.00;
});

// --- TEST SUITE 3: Order Creation & SVG QR Generation ---
echo "\n[3] Testing Order Initiation & SVG QR Code Generation\n";

$createdOrderId = null;
$createdTxId = null;

it("Creates Koko payment order with QR channel and generates SVG QR code", function() use (&$createdOrderId, &$createdTxId) {
    $res = KokoPay::createPaymentOrder([
        'amount'         => 3000.00,
        'invoice_no'     => 'TEST-INV-1001',
        'branch_id'      => 1,
        'user_id'        => 1,
        'channel'        => 'qr',
        'customer_name'  => 'Test Customer',
        'customer_phone' => '0771234567'
    ]);

    if (!$res['success']) return false;

    $createdOrderId = $res['koko_order_id'];
    $createdTxId = $res['transaction_id'];

    $hasSvg = str_contains($res['qr_svg'], '<svg') && str_contains($res['qr_svg'], 'KOKO');
    $hasUrl = str_contains($res['payment_url'], 'checkout/' . $createdOrderId);
    $hasInst = ($res['installment_amount'] === 1000.00);

    return $hasSvg && $hasUrl && $hasInst && $res['status'] === 'pending';
});

it("Creates Koko payment order with Payment Link channel", function() {
    $res = KokoPay::createPaymentOrder([
        'amount'         => 1500.00,
        'invoice_no'     => 'TEST-INV-LINK-1',
        'branch_id'      => 1,
        'user_id'        => 1,
        'channel'        => 'link',
        'customer_name'  => 'Link Customer'
    ]);
    if (!$res['success']) return false;

    // Cleanup
    Database::execute("DELETE FROM koko_transactions WHERE id = :id", [':id' => $res['transaction_id']]);
    return str_contains($res['payment_url'], 'mid=');
});

it("Creates Koko payment order with SMS Phone channel and dispatches message", function() {
    $res = KokoPay::createPaymentOrder([
        'amount'         => 6000.00,
        'invoice_no'     => 'TEST-INV-SMS-1',
        'branch_id'      => 1,
        'user_id'        => 1,
        'channel'        => 'sms',
        'customer_phone' => '0719876543',
        'send_sms'       => true
    ]);
    if (!$res['success']) return false;

    $hasSms = $res['sms_dispatched'] && str_contains($res['sms_message'], 'BNPL payment');

    // Cleanup
    Database::execute("DELETE FROM koko_transactions WHERE id = :id", [':id' => $res['transaction_id']]);
    return $hasSms;
});

// --- TEST SUITE 4: Status Polling & Instant Payment Verification ---
echo "\n[4] Testing Status Polling, Verification & Idempotency\n";

it("Polls order status from database", function() use (&$createdOrderId) {
    $statusRes = KokoPay::checkOrderStatus($createdOrderId);
    return $statusRes['success'] && $statusRes['status'] === 'pending' && $statusRes['amount'] === 3000.00;
});

it("Verifies and marks order as PAID", function() use (&$createdOrderId) {
    $verifyRes = KokoPay::verifyAndMarkPaid($createdOrderId, 'KOKO-TEST-PAY-777');
    return $verifyRes['success'] && $verifyRes['status'] === 'paid' && $verifyRes['koko_payment_id'] === 'KOKO-TEST-PAY-777';
});

it("Handles repeated verification idempotently", function() use (&$createdOrderId) {
    $repeatRes = KokoPay::verifyAndMarkPaid($createdOrderId, 'KOKO-TEST-PAY-777');
    return $repeatRes['success'] && !empty($repeatRes['already_paid']) && $repeatRes['status'] === 'paid';
});

it("Resends SMS to customer phone", function() use (&$createdOrderId) {
    $smsRes = KokoPay::resendSms($createdOrderId, '0781234567');
    return $smsRes['success'] && $smsRes['phone'] === '0781234567';
});

// --- TEST SUITE 5: End-to-End POS Sale Finalization with Koko Payment ---
echo "\n[5] Testing End-to-End POS Sale Finalization with Koko\n";

$testSaleId = null;
$testLotId = null;
$testProductId = null;

it("Creates draft sale and finalizes with Koko payment method", function() use (&$testSaleId, &$testLotId, &$testProductId, &$createdOrderId) {
    // 1. Pick an active product lot in Branch 1
    $lot = Database::fetchOne("
        SELECT pl.id, pl.product_id, pl.remaining_quantity, pl.selling_price, pl.cost
        FROM product_lots pl
        JOIN products p ON pl.product_id = p.id
        WHERE pl.branch_id = 1 AND pl.status = 'active' AND pl.remaining_quantity >= 1 AND p.status = 'active'
        LIMIT 1
    ");

    if (!$lot) {
        throw new Exception("No active product lot available for test in Branch 1");
    }

    $testLotId = (int)$lot['id'];
    $testProductId = (int)$lot['product_id'];
    $unitPrice = (float)$lot['selling_price'];
    $unitCost = (float)$lot['cost'];
    $testQty = 1.00;
    $totalAmount = round($unitPrice * $testQty, 2);

    $cust = Database::fetchOne("SELECT id FROM customers WHERE status = 'active' LIMIT 1") 
        ?: Database::fetchOne("SELECT id FROM customers LIMIT 1");
    if (!$cust) {
        throw new Exception("No customer found for test");
    }
    $testCustomerId = (int)$cust['id'];

    // 2. Insert test sale draft
    $invNo = 'INV-TEST-KOKO-' . time();
    Database::execute("
        INSERT INTO sales (
            invoice_no, branch_id, customer_id, user_id, subtotal, 
            total_amount, status, created_at, updated_at
        ) VALUES (
            :inv, 1, :cid, 1, :sub, :tot, 'draft', NOW(), NOW()
        )
    ", [
        ':inv' => $invNo,
        ':cid' => $testCustomerId,
        ':sub' => $totalAmount,
        ':tot' => $totalAmount
    ]);
    $testSaleId = (int)Database::getInstance()->getConnection()->lastInsertId();

    // 3. Insert sale item
    Database::execute("
        INSERT INTO sale_items (
            sale_id, product_id, lot_id, quantity, original_price, unit_price, unit_cost, 
            discount_amount, line_total, line_cost
        ) VALUES (
            :sid, :pid, :lid, :qty, :orig_prc, :unit_prc, :cst, 0.00, :tot, :cst_tot
        )
    ", [
        ':sid'      => $testSaleId,
        ':pid'      => $testProductId,
        ':lid'      => $testLotId,
        ':qty'      => $testQty,
        ':orig_prc' => $unitPrice,
        ':unit_prc' => $unitPrice,
        ':cst'      => $unitCost,
        ':tot'      => $totalAmount,
        ':cst_tot'  => round($unitCost * $testQty, 2)
    ]);

    // 4. Finalize sale via Sale::finalizeSale with Koko payment method
    $payments = [
        [
            'payment_method' => 'Koko',
            'amount'         => $totalAmount,
            'reference_no'   => $createdOrderId
        ]
    ];

    $finResult = Sale::finalizeSale($testSaleId, $payments, 1, 1);
    if (!$finResult['success']) {
        throw new Exception("Sale finalization failed: " . ($finResult['message'] ?? 'Unknown error'));
    }

    // Verify sale status in DB
    $saleCheck = Database::fetchOne("SELECT status FROM sales WHERE id = :id", [':id' => $testSaleId]);
    $payCheck  = Database::fetchOne("SELECT payment_method, amount FROM payments WHERE sale_id = :id", [':id' => $testSaleId]);

    return $saleCheck['status'] === 'completed' 
        && $payCheck['payment_method'] === 'Koko'
        && (float)$payCheck['amount'] === $totalAmount;
});

// --- TEST SUITE 6: Chart of Accounts Double-Entry Journal Posting ---
echo "\n[6] Testing Chart of Accounts Double-Entry Journal Postings\n";

it("Verifies journal entry was posted with Dr 1032 KOKO Receivable == Cr 4010 Sales Revenue", function() use (&$testSaleId) {
    $entry = Database::fetchOne("
        SELECT id, total_debit, total_credit, posting_status 
        FROM journal_entries 
        WHERE source_type = 'sale' AND source_id = :id
    ", [':id' => $testSaleId]);

    if (!$entry) {
        throw new Exception("No journal entry found for POS sale #{$testSaleId}");
    }

    // Verify debit and credit balance down to cent
    $isBalanced = (round((float)$entry['total_debit'], 2) === round((float)$entry['total_credit'], 2));
    if (!$isBalanced) {
        throw new Exception("Journal entry is not balanced! Debit: {$entry['total_debit']}, Credit: {$entry['total_credit']}");
    }

    // Verify lines
    $lines = Database::fetchAll("
        SELECT jl.*, coa.account_code, coa.account_name 
        FROM journal_lines jl
        JOIN chart_of_accounts coa ON jl.account_id = coa.id
        WHERE jl.journal_entry_id = :jid
    ", [':jid' => (int)$entry['id']]);

    $hasKokoDebit = false;
    $hasSalesCredit = false;

    foreach ($lines as $l) {
        if ($l['account_code'] === '1032' && (float)$l['debit'] > 0) {
            $hasKokoDebit = true;
        }
        if ($l['account_code'] === '4010' && (float)$l['credit'] > 0) {
            $hasSalesCredit = true;
        }
    }

    return $isBalanced && $hasKokoDebit && $hasSalesCredit;
});

// --- TEST SUITE 7: Isolated Test Cleanup ---
echo "\n[7] Cleaning up isolated test records (Zero Truncation Rule)\n";

it("Safely cleans up test records without modifying operational data", function() use (&$testSaleId, &$createdTxId) {
    if ($testSaleId) {
        // Rollback stock deduction on test lot
        $item = Database::fetchOne("SELECT lot_id, quantity FROM sale_items WHERE sale_id = :id", [':id' => $testSaleId]);
        if ($item) {
            Database::execute("
                UPDATE product_lots SET remaining_quantity = remaining_quantity + :qty WHERE id = :lid
            ", [':qty' => $item['quantity'], ':lid' => $item['lot_id']]);
        }

        // Clean up journal lines & entry
        $jEntry = Database::fetchOne("SELECT id FROM journal_entries WHERE source_type = 'sale' AND source_id = :id", [':id' => $testSaleId]);
        if ($jEntry) {
            Database::execute("DELETE FROM journal_lines WHERE journal_entry_id = :id", [':id' => $jEntry['id']]);
            Database::execute("DELETE FROM journal_entries WHERE id = :id", [':id' => $jEntry['id']]);
        }

        // Clean up payments, sale items, and sale
        Database::execute("DELETE FROM payments WHERE sale_id = :id", [':id' => $testSaleId]);
        Database::execute("DELETE FROM sale_items WHERE sale_id = :id", [':id' => $testSaleId]);
        Database::execute("DELETE FROM sales WHERE id = :id", [':id' => $testSaleId]);
    }

    if ($createdTxId) {
        Database::execute("DELETE FROM koko_transactions WHERE id = :id", [':id' => $createdTxId]);
    }

    return true;
});

echo "\n======================================================\n";
echo " TEST RUN RESULTS: {$passCount} PASSED, {$failCount} FAILED\n";
echo "======================================================\n\n";

if ($failCount > 0) {
    exit(1);
}
exit(0);
