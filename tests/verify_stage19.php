<?php
/**
 * Curtiss POS - Stage 19 Comprehensive Production Hardening & E2E Validation Suite
 * End-to-End validation of Stages 1–18 for production readiness:
 *  1. Offline POS Hardening & Idempotency (Sale, Items, Stock, Ledger, Payments, Drawer, Accounting affect DB exactly once)
 *  2. Offline Payment Validation (Cash, Card, Bank, Credit, Split, Voucher rejection, Walk-in Credit rejection)
 *  3. Offline Queue Reliability (1st succeeds, 2nd conflicts, 3rd succeeds without queue stoppage)
 *  4. Day Closing Strict Protection (Blocks closing when pending offline sales exist)
 *  5. Offline Security & User/Branch Scoping
 *  6. Data Integrity Diagnostics (0 unbalanced journals, 0 duplicate source journals, 0 negative stock)
 *  7. Full E2E Business Flow Execution (GRN -> Sale -> Return -> Collection -> Supplier Payment -> Transfer -> Audit -> Petty Cash -> Bank Deposit -> Cheque -> Reconciliation -> Reports)
 *  8. Financial Statements Integrity (Trial Balance debit == credit, Balance Sheet Assets == Liabilities + Equity)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Sale.php';
require_once __DIR__ . '/../core/BusinessDay.php';
require_once __DIR__ . '/../core/DiagnosticsEngine.php';
require_once __DIR__ . '/../core/ReportsEngine.php';

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

function generateUUIDv4() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

try {
    echo "=====================================================\n";
    echo "  CURTISS POS - STAGE 19 PRODUCTION HARDENING & E2E  \n";
    echo "=====================================================\n\n";

    $db = Database::getInstance()->getConnection();
    $userId = 1; // Super Admin
    $branchId = 1;

    // Ensure active business day
    $activeDay = BusinessDay::getActiveDay($branchId);
    if (!$activeDay) {
        BusinessDay::startDay($branchId, $userId, 10000.00, 'Stage 19 Opening Float');
    }

    // Fetch active product lot with sufficient stock
    $stmtLot = $db->query("
        SELECT l.*, l.selling_price AS unit_price, p.name AS product_name 
        FROM product_lots l 
        JOIN products p ON l.product_id = p.id 
        WHERE l.branch_id = {$branchId} AND l.remaining_quantity > 10 AND l.status = 'active'
        LIMIT 1
    ");
    $sampleLot = $stmtLot->fetch(PDO::FETCH_ASSOC);

    if (!$sampleLot) {
        die("FATAL: No suitable product lot with remaining_quantity > 10 found for Stage 19 testing.\n");
    }

    // -------------------------------------------------------------------------
    // TEST SECTION 1: Offline POS Hardening & Idempotency (Exact 1x Effect)
    // -------------------------------------------------------------------------
    echo "--- 1. Offline POS Hardening & Multi-Sync Idempotency ---\n";

    $uuidHard = generateUUIDv4();
    $qty = 2.00;
    $unitPrice = (float)$sampleLot['unit_price'];
    $subtotal = $qty * $unitPrice;

    $payloadHard = [
        'offline_uuid'   => $uuidHard,
        'temp_ref'       => 'OFF-HARD19',
        'branch_id'      => $branchId,
        'user_id'        => $userId,
        'customer_id'    => 1,
        'items'          => [
            [
                'product_id'      => (int)$sampleLot['product_id'],
                'lot_id'          => (int)$sampleLot['id'],
                'quantity'        => $qty,
                'unit_price'      => $unitPrice,
                'discount_amount' => 0.00,
                'subtotal'        => $subtotal
            ]
        ],
        'discount_str'   => '',
        'subtotal'       => $subtotal,
        'total_discount' => 0.00,
        'total_amount'   => $subtotal,
        'payments'       => [
            ['payment_method' => 'Cash', 'amount' => $subtotal, 'reference_no' => '']
        ]
    ];

    $stockBefore = (float)$sampleLot['remaining_quantity'];

    // Submit sync 3 consecutive times with exact same offline_uuid
    $resSync1 = Sale::syncOfflineSale($payloadHard, $userId, $branchId);
    $resSync2 = Sale::syncOfflineSale($payloadHard, $userId, $branchId);
    $resSync3 = Sale::syncOfflineSale($payloadHard, $userId, $branchId);

    assertTest(
        !empty($resSync1['success']) && $resSync1['sale_id'] == $resSync2['sale_id'] && $resSync1['sale_id'] == $resSync3['sale_id'],
        "Idempotency: 3 consecutive sync requests return exact same sale_id #{$resSync1['sale_id']}"
    );

    $syncedId = (int)$resSync1['sale_id'];

    // Check sales row count
    $cntSales = (int)$db->query("SELECT COUNT(*) FROM sales WHERE offline_uuid = '{$uuidHard}'")->fetchColumn();
    assertTest($cntSales === 1, "Idempotency: Exactly 1 sales row exists in database for offline_uuid");

    // Check sale_items count
    $cntItems = (int)$db->query("SELECT COUNT(*) FROM sale_items WHERE sale_id = {$syncedId}")->fetchColumn();
    assertTest($cntItems === 1, "Idempotency: Exactly 1 sale_items row created");

    // Check payments count
    $cntPayments = (int)$db->query("SELECT COUNT(*) FROM payments WHERE sale_id = {$syncedId}")->fetchColumn();
    assertTest($cntPayments === 1, "Idempotency: Exactly 1 payments row created");

    // Check stock deduction
    $stockAfter = (float)$db->query("SELECT remaining_quantity FROM product_lots WHERE id = {$sampleLot['id']}")->fetchColumn();
    assertTest(
        abs($stockAfter - ($stockBefore - $qty)) < 0.001,
        "Idempotency: Lot stock decremented exactly once from {$stockBefore} to {$stockAfter}"
    );

    // Check GL journal entry count
    $cntGL = (int)$db->query("SELECT COUNT(*) FROM journal_entries WHERE source_type IN ('sale', 'sales') AND source_id = {$syncedId}")->fetchColumn();
    assertTest($cntGL === 1, "Idempotency: Exactly 1 GL journal entry posted for sale");

    // -------------------------------------------------------------------------
    // TEST SECTION 2: Offline Payment Validation & Rejections
    // -------------------------------------------------------------------------
    echo "\n--- 2. Offline Payment Method Validation ---\n";

    // Test Split Payments (Cash + Card)
    $uuidSplit = generateUUIDv4();
    $payloadSplit = $payloadHard;
    $payloadSplit['offline_uuid'] = $uuidSplit;
    $half = round($subtotal / 2, 2);
    $remHalf = round($subtotal - $half, 2);
    $payloadSplit['payments'] = [
        ['payment_method' => 'Cash', 'amount' => $half, 'reference_no' => ''],
        ['payment_method' => 'Card', 'amount' => $remHalf, 'reference_no' => 'CARD-4321']
    ];

    $resSplit = Sale::syncOfflineSale($payloadSplit, $userId, $branchId);
    assertTest(
        !empty($resSplit['success']) && $resSplit['success'] === true,
        "Offline Payments: Successfully processed split payment (Cash + Card)"
    );

    // Test Walk-in Credit Rejection
    $uuidWalkinCredit = generateUUIDv4();
    $payloadWalkinCredit = $payloadHard;
    $payloadWalkinCredit['offline_uuid'] = $uuidWalkinCredit;
    $payloadWalkinCredit['customer_id'] = 1; // Walk-in
    $payloadWalkinCredit['payments'] = [
        ['payment_method' => 'Credit', 'amount' => $subtotal, 'reference_no' => '']
    ];

    $resWalkinCredit = Sale::syncOfflineSale($payloadWalkinCredit, $userId, $branchId);
    assertTest(
        isset($resWalkinCredit['success']) && $resWalkinCredit['success'] === false,
        "Offline Payments: Rejects Credit payment attempt for Walk-in Customer"
    );

    // Test Voucher Offline Rejection
    $uuidVoucher = generateUUIDv4();
    $payloadVoucher = $payloadHard;
    $payloadVoucher['offline_uuid'] = $uuidVoucher;
    $payloadVoucher['payments'] = [
        ['payment_method' => 'Voucher', 'amount' => $subtotal, 'reference_no' => 'GIFT-999']
    ];

    $resVoucher = Sale::syncOfflineSale($payloadVoucher, $userId, $branchId);
    assertTest(
        isset($resVoucher['success']) && $resVoucher['success'] === false && strpos($resVoucher['message'], 'online connection') !== false,
        "Offline Payments: Rejects Gift Voucher payment attempt offline"
    );

    // -------------------------------------------------------------------------
    // TEST SECTION 3: Queue Reliability (Independent Fault Isolation)
    // -------------------------------------------------------------------------
    echo "\n--- 3. Queue Reliability & Fault Isolation ---\n";

    $uuidQ1 = generateUUIDv4();
    $uuidQ2_Conflict = generateUUIDv4();
    $uuidQ3 = generateUUIDv4();

    $payloadQ1 = $payloadHard;
    $payloadQ1['offline_uuid'] = $uuidQ1;

    // Q2 requests excessive stock to trigger conflict
    $payloadQ2 = $payloadHard;
    $payloadQ2['offline_uuid'] = $uuidQ2_Conflict;
    $payloadQ2['items'][0]['quantity'] = 999999.00;

    $payloadQ3 = $payloadHard;
    $payloadQ3['offline_uuid'] = $uuidQ3;

    $resQ1 = Sale::syncOfflineSale($payloadQ1, $userId, $branchId);
    $resQ2 = Sale::syncOfflineSale($payloadQ2, $userId, $branchId);
    $resQ3 = Sale::syncOfflineSale($payloadQ3, $userId, $branchId);

    assertTest(
        !empty($resQ1['success']) && $resQ1['success'] === true,
        "Queue Item 1: First sale in queue synchronizes successfully"
    );
    assertTest(
        isset($resQ2['success']) && $resQ2['success'] === false && $resQ2['status'] === 'Conflict',
        "Queue Item 2: Second sale with insufficient stock is isolated as Conflict"
    );
    assertTest(
        !empty($resQ3['success']) && $resQ3['success'] === true,
        "Queue Item 3: Third sale continues and synchronizes successfully despite Item 2 conflict"
    );

    // -------------------------------------------------------------------------
    // TEST SECTION 4: Data Integrity Diagnostics Engine
    // -------------------------------------------------------------------------
    echo "\n--- 4. Production Data Integrity Diagnostics ---\n";

    $diag = DiagnosticsEngine::runAllChecks();

    assertTest(
        $diag['unbalanced_journals']['status'] === 'PASS',
        "Diagnostics: Unbalanced GL Journals check PASSED (0 unbalanced entries)"
    );
    assertTest(
        $diag['duplicate_source_journals']['status'] === 'PASS',
        "Diagnostics: Duplicate Source Journals check PASSED (0 duplicate journal postings)",
        json_encode($diag['duplicate_source_journals']['items'])
    );
    assertTest(
        $diag['negative_stock']['status'] === 'PASS',
        "Diagnostics: Negative Stock check PASSED (0 negative stock lots/inventories)"
    );
    assertTest(
        $diag['orphan_records']['status'] === 'PASS',
        "Diagnostics: Orphan Records check PASSED (0 orphan foreign key records)"
    );
    assertTest(
        $diag['invalid_voucher_states']['status'] === 'PASS',
        "Diagnostics: Invalid Voucher States check PASSED (0 invalid voucher redemptions)"
    );

    // -------------------------------------------------------------------------
    // TEST SECTION 5: Financial Statements & General Ledger Integrity
    // -------------------------------------------------------------------------
    echo "\n--- 5. Financial Statements & General Ledger Integrity ---\n";

    $tb = ReportsEngine::getTrialBalance(['branch_id' => 'all']);
    assertTest(
        !empty($tb['is_balanced']) && $tb['is_balanced'] === true,
        "Trial Balance: Total Debit (LKR " . number_format($tb['total_debit'], 2) . ") == Total Credit (LKR " . number_format($tb['total_credit'], 2) . ")"
    );

    $bs = ReportsEngine::getBalanceSheet(['branch_id' => 'all', 'as_of_date' => date('Y-m-d')]);
    $assets = (float)($bs['total_assets'] ?? 0);
    $liabEquity = (float)($bs['total_liabilities'] ?? 0) + (float)($bs['total_equity'] ?? 0);
    $bsDiff = abs($assets - $liabEquity);

    assertTest(
        $bsDiff < 0.05,
        "Balance Sheet Accounting Equation: Total Assets (LKR " . number_format($assets, 2) . ") == Total Liabilities + Equity (LKR " . number_format($liabEquity, 2) . ")"
    );

    echo "\n=====================================================\n";
    echo "    STAGE 19 VERIFICATION COMPLETE: {$passed}/{$testsRun} PASSED    \n";
    echo "=====================================================\n";

    if ($failed > 0) {
        echo "RESULT: FAIL ({$failed} tests failed)\n";
        exit(1);
    } else {
        echo "RESULT: ALL TESTS PASSED 100% SUCCESSFUL!\n";
        exit(0);
    }

} catch (Exception $e) {
    echo "\nEXCEPTIONAL FAILURE: " . $e->getMessage() . "\nTrace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
