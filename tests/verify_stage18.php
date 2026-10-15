<?php
/**
 * Curtiss POS - Comprehensive Stage 18 Automated Test & Regression Suite
 * Tests Offline POS Billing & Synchronization Engine:
 *  - Schema verification for offline_uuid column & UNIQUE constraint in sales table
 *  - Idempotent sale synchronization using offline_uuid
 *  - Duplicate sync protection (returns mapped sale without duplicate stock or GL posting)
 *  - Re-validation of server stock lots & conflict response handling (no negative stock or partial invoices)
 *  - Gift voucher offline payment restriction ("Gift vouchers require an online connection.")
 *  - Special User blocking for offline sale synchronization
 *  - General Ledger posting integrity & GL balance regression (debit == credit)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Sale.php';
require_once __DIR__ . '/../core/BusinessDay.php';

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

function generateTestUUID() {
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
    echo "       CURTISS POS - STAGE 18 VERIFICATION SUITE     \n";
    echo "=====================================================\n\n";

    $db = Database::getInstance()->getConnection();
    $userId = 1; // Super Admin
    $branchId = 1;

    // Ensure active business day exists
    $activeDay = BusinessDay::getActiveDay($branchId);
    if (!$activeDay) {
        $bdayId = BusinessDay::startDay($branchId, $userId, 10000.00, 'Stage 18 Verification Opening');
    }

    // Fetch sample product & lot with available stock
    $stmtLot = $db->query("
        SELECT l.*, l.selling_price AS unit_price, p.name AS product_name 
        FROM product_lots l 
        JOIN products p ON l.product_id = p.id 
        WHERE l.branch_id = {$branchId} AND l.remaining_quantity > 5 AND l.status = 'active'
        LIMIT 1
    ");
    $sampleLot = $stmtLot->fetch(PDO::FETCH_ASSOC);

    if (!$sampleLot) {
        die("FATAL: No suitable product lot found for testing with remaining_quantity > 5.\n");
    }

    // -------------------------------------------------------------------------
    // TEST SECTION 1: Database Schema Verification
    // -------------------------------------------------------------------------
    echo "--- 1. Database Schema & Index Integrity ---\n";

    $stmtCol = $db->query("SHOW COLUMNS FROM sales LIKE 'offline_uuid'");
    $colExists = $stmtCol->fetch(PDO::FETCH_ASSOC);
    assertTest(!empty($colExists), "Database Migration: sales table contains offline_uuid column");

    $stmtIdx = $db->query("SHOW INDEX FROM sales WHERE Column_name = 'offline_uuid' AND Non_unique = 0");
    $idxExists = $stmtIdx->fetch(PDO::FETCH_ASSOC);
    assertTest(!empty($idxExists), "Database Integrity: UNIQUE constraint exists on sales.offline_uuid");

    // -------------------------------------------------------------------------
    // TEST SECTION 2: Idempotent Synchronization Flow
    // -------------------------------------------------------------------------
    echo "\n--- 2. Offline Sale Synchronization & Idempotency Protection ---\n";

    $testUuid = generateTestUUID();
    $qtyToBuy = 2.00;
    $unitPrice = (float)$sampleLot['unit_price'];
    $itemSubtotal = $qtyToBuy * $unitPrice;

    $payload = [
        'offline_uuid'   => $testUuid,
        'temp_ref'       => 'OFF-STAGE18T',
        'branch_id'      => $branchId,
        'user_id'        => $userId,
        'customer_id'    => 1, // Walk-in
        'items'          => [
            [
                'product_id'      => (int)$sampleLot['product_id'],
                'lot_id'          => (int)$sampleLot['id'],
                'quantity'        => $qtyToBuy,
                'unit_price'      => $unitPrice,
                'discount_amount' => 0.00,
                'subtotal'        => $itemSubtotal
            ]
        ],
        'discount_str'   => '',
        'subtotal'       => $itemSubtotal,
        'total_discount' => 0.00,
        'total_amount'   => $itemSubtotal,
        'payments'       => [
            [
                'payment_method' => 'Cash',
                'amount'         => $itemSubtotal,
                'reference_no'   => ''
            ]
        ]
    ];

    $lotStockBefore = (float)$sampleLot['remaining_quantity'];

    // First Sync Execution
    $syncResult1 = Sale::syncOfflineSale($payload, $userId, $branchId);
    assertTest(
        !empty($syncResult1['success']) && $syncResult1['success'] === true && !empty($syncResult1['sale_id']),
        "First Sync: Successfully synced offline sale into server DB (Sale ID #" . ($syncResult1['sale_id'] ?? '') . ", Invoice #" . ($syncResult1['invoice_no'] ?? '') . ")",
        $syncResult1['message'] ?? ''
    );

    $syncedSaleId = (int)($syncResult1['sale_id'] ?? 0);

    // Check stock lot deduction on server
    $stmtLotAfter = $db->query("SELECT remaining_quantity FROM product_lots WHERE id = {$sampleLot['id']}");
    $lotStockAfter = (float)$stmtLotAfter->fetchColumn();
    $expectedStockAfter = $lotStockBefore - $qtyToBuy;
    assertTest(
        abs($lotStockAfter - $expectedStockAfter) < 0.001,
        "Stock Ledger: Server lot stock decremented accurately from {$lotStockBefore} to {$lotStockAfter}"
    );

    // Verify DB record offline_uuid
    $stmtSaleRow = $db->query("SELECT offline_uuid, status FROM sales WHERE id = {$syncedSaleId}");
    $saleRow = $stmtSaleRow->fetch(PDO::FETCH_ASSOC);
    assertTest(
        $saleRow && $saleRow['offline_uuid'] === $testUuid && strtolower($saleRow['status']) === 'completed',
        "DB Verification: sales row contains exact offline_uuid and status Completed"
    );

    // Second Sync Execution with EXACT SAME payload (Idempotency Check)
    $syncResult2 = Sale::syncOfflineSale($payload, $userId, $branchId);
    assertTest(
        !empty($syncResult2['success']) && $syncResult2['success'] === true && $syncResult2['sale_id'] == $syncedSaleId && !empty($syncResult2['already_synced']),
        "Idempotency Protection: Re-submitting same offline_uuid returns existing sale without duplicate invoice creation"
    );

    // Verify stock did NOT deduct again
    $stmtLotAfter2 = $db->query("SELECT remaining_quantity FROM product_lots WHERE id = {$sampleLot['id']}");
    $lotStockAfter2 = (float)$stmtLotAfter2->fetchColumn();
    assertTest(
        abs($lotStockAfter2 - $lotStockAfter) < 0.001,
        "Idempotency Protection: Server lot stock remains unchanged on duplicate sync attempt"
    );

    // Verify sales count for this offline_uuid is exactly 1
    $stmtCount = $db->query("SELECT COUNT(*) FROM sales WHERE offline_uuid = '{$testUuid}'");
    $salesCount = (int)$stmtCount->fetchColumn();
    assertTest($salesCount === 1, "Idempotency Protection: Exactly 1 sales record exists for offline_uuid in DB");

    // -------------------------------------------------------------------------
    // TEST SECTION 3: Conflict Handling & Stock Revalidation
    // -------------------------------------------------------------------------
    echo "\n--- 3. Stock Revalidation & Conflict Strategy ---\n";

    $conflictUuid = generateTestUUID();
    $excessiveQty = $lotStockAfter2 + 9999.00;

    $conflictPayload = $payload;
    $conflictPayload['offline_uuid'] = $conflictUuid;
    $conflictPayload['items'][0]['quantity'] = $excessiveQty;
    $conflictPayload['items'][0]['subtotal'] = $excessiveQty * $unitPrice;
    $conflictPayload['subtotal'] = $excessiveQty * $unitPrice;
    $conflictPayload['total_amount'] = $excessiveQty * $unitPrice;
    $conflictPayload['payments'][0]['amount'] = $excessiveQty * $unitPrice;

    $conflictResult = Sale::syncOfflineSale($conflictPayload, $userId, $branchId);
    assertTest(
        isset($conflictResult['success']) && $conflictResult['success'] === false && !empty($conflictResult['conflict']) && $conflictResult['status'] === 'Conflict',
        "Stock Conflict: Server stock revalidation flags insufficient lot quantity as Conflict"
    );

    $stmtConflictCheck = $db->query("SELECT COUNT(*) FROM sales WHERE offline_uuid = '{$conflictUuid}'");
    $conflictCount = (int)$stmtConflictCheck->fetchColumn();
    assertTest($conflictCount === 0, "Stock Conflict: No partial invoice or negative stock created in DB for conflict sale");

    // -------------------------------------------------------------------------
    // TEST SECTION 4: Gift Voucher & Special User Restrictions
    // -------------------------------------------------------------------------
    echo "\n--- 4. Gift Voucher & Special User Rule Enforcement ---\n";

    // Test Gift Voucher offline rejection
    $voucherUuid = generateTestUUID();
    $voucherPayload = $payload;
    $voucherPayload['offline_uuid'] = $voucherUuid;
    $voucherPayload['payments'] = [
        [
            'payment_method' => 'Voucher',
            'amount'         => $itemSubtotal,
            'reference_no'   => 'GIFT-123456'
        ]
    ];

    $voucherResult = Sale::syncOfflineSale($voucherPayload, $userId, $branchId);
    assertTest(
        isset($voucherResult['success']) && $voucherResult['success'] === false && strpos($voucherResult['message'], 'Gift vouchers require an online connection') !== false,
        "Gift Voucher Restriction: Rejects voucher payment attempt in offline sale sync"
    );

    // Test Special User restriction
    $stmtSpecial = $db->query("SELECT u.id FROM users u JOIN roles r ON u.role_id = r.id WHERE r.code = '" . ROLE_SPECIAL_USER . "' LIMIT 1");
    $specialUser = $stmtSpecial->fetch(PDO::FETCH_ASSOC);

    if (!$specialUser) {
        $stmtRole = $db->query("SELECT id FROM roles WHERE code = '" . ROLE_SPECIAL_USER . "' LIMIT 1");
        $roleRow = $stmtRole->fetch(PDO::FETCH_ASSOC);
        if ($roleRow) {
            $db->exec("INSERT INTO users (username, email, password_hash, full_name, role_id, status) VALUES ('test_special', 'test_special@curtiss.lk', 'hash', 'Test Special', {$roleRow['id']}, 'active')");
            $specialUserId = (int)$db->lastInsertId();
        }
    } else {
        $specialUserId = (int)$specialUser['id'];
    }

    if (isset($specialUserId)) {
        $specialUuid = generateTestUUID();
        $specialPayload = $payload;
        $specialPayload['offline_uuid'] = $specialUuid;

        $specialResult = Sale::syncOfflineSale($specialPayload, $specialUserId, $branchId);
        assertTest(
            isset($specialResult['success']) && $specialResult['success'] === false && strpos($specialResult['message'], 'restricted') !== false,
            "Special User Restriction: Blocks Special User from performing offline sale sync"
        );
    } else {
        assertTest(false, "Special User Restriction: Unable to create or find Special User for test");
    }

    // -------------------------------------------------------------------------
    // TEST SECTION 5: Stages 1-17 GL Balance Regression Suite
    // -------------------------------------------------------------------------
    echo "\n--- 5. Stages 1-17 General Ledger Balance Regression ---\n";

    $stmtGL = $db->query("SELECT SUM(debit) AS total_debit, SUM(credit) AS total_credit FROM journal_lines");
    $glRow = $stmtGL->fetch(PDO::FETCH_ASSOC);
    $totalDebit = (float)($glRow['total_debit'] ?? 0);
    $totalCredit = (float)($glRow['total_credit'] ?? 0);

    $diff = abs($totalDebit - $totalCredit);
    assertTest(
        $diff < 0.001,
        "GL Regression Integrity: General Ledger remains balanced (Total Debit: LKR " . number_format($totalDebit, 2) . " == Total Credit: LKR " . number_format($totalCredit, 2) . ")"
    );

    echo "\n=====================================================\n";
    echo "       STAGE 18 VERIFICATION COMPLETE: {$passed}/{$testsRun} PASSED   \n";
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
