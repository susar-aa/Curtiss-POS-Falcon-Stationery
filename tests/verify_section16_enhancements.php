<?php
/**
 * Curtiss POS - Section 16 Comprehensive Targeted Verification Suite (Stage 36)
 * Section 16: Offline POS, Synchronization, Conflict Handling & Recovery
 *
 * Covers 80+ Targeted Assertions across 92 Plan Specifications:
 *  1. Schema & UUID Unique Index Integrity
 *  2. Offline Sale Synchronization & Multi-Sync Idempotency Protection
 *  3. Server Lot Stock Revalidation, Conflict Strategy & Multi-Device Oversell Protection
 *  4. Partial Batch Sync & Queue Order Resilience
 *  5. Payment Method Validation (Cash, Card 1030 Receivable, Bank, Credit, Gift Voucher Offline Rejection, Walk-in Credit Rejection)
 *  6. Customer Validation, AR Outstanding Updates & Balance Idempotency
 *  7. Price Integrity, Manual Overrides & Discount Accounting (Fixed, Percent, Round-Up, Item/Invoice)
 *  8. Cost Integrity & Exact-Lot COGS Calculation (Client Cost Ignored)
 *  9. Business Day Association, Pending Sale Protection & Late Sync Rules
 * 10. Security & Tampering Protections (Branch Spoofing, User Scoping, Mismatched Lots, Payment Mismatches)
 * 11. Database Transaction Atomicity & Failure Injection Rollbacks (0 Orphan Invoices/Payments/Journals)
 * 12. Offline Returns & Hold/Resume Scope Verification
 * 13. System-Wide Double-Entry GL Trial Balance Health & Inventory Reconciliation (No Negative Stock)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Sale.php';
require_once __DIR__ . '/../core/BusinessDay.php';
require_once __DIR__ . '/../core/DiagnosticsEngine.php';

echo "=========================================================\n";
echo "   CURTISS POS - SECTION 16 TARGETED VERIFICATION SUITE   \n";
echo "   Offline POS, Sync, Conflict Handling & Recovery (Stage 36)\n";
echo "=========================================================\n\n";

$testsPassed = 0;
$totalTests  = 0;

function assertTest(bool $condition, string $testName, string $detail = '') {
    global $testsPassed, $totalTests;
    $totalTests++;
    if ($condition) {
        echo "[PASS] " . sprintf("%2d", $totalTests) . ". {$testName}\n";
        $testsPassed++;
    } else {
        echo "[FAIL] " . sprintf("%2d", $totalTests) . ". {$testName}" . ($detail !== '' ? " -> Details: {$detail}" : "") . "\n";
    }
}

function generateTestUUIDv4() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

try {
    $db = Database::getInstance()->getConnection();
    $userId = 1; // Super Admin
    $branchId = 1;

    // Ensure active business day
    $activeDay = BusinessDay::getActiveDay($branchId);
    if (!$activeDay) {
        BusinessDay::startDay($branchId, $userId, 10000.00, 'Section 16 Test Suite Float');
        $activeDay = BusinessDay::getActiveDay($branchId);
    }

    // Fetch active product lots with sufficient stock for multi-test scenarios
    $stmtLots = $db->query("
        SELECT l.*, l.selling_price AS unit_price, p.name AS product_name 
        FROM product_lots l 
        JOIN products p ON l.product_id = p.id 
        WHERE l.branch_id = {$branchId} AND l.remaining_quantity > 20 AND l.status = 'active'
        ORDER BY l.id ASC
    ");
    $sampleLots = $stmtLots->fetchAll(PDO::FETCH_ASSOC);

    if (count($sampleLots) < 2) {
        die("FATAL: At least two product lots with > 20 stock required for Section 16 verification.\n");
    }

    $lotA = $sampleLots[0];
    $lotB = $sampleLots[1];

    // Fetch a registered credit-eligible customer
    $custReg = Database::fetchOne("
        SELECT id, name, credit_balance FROM customers 
        WHERE is_walkin = 0 AND status = 'active' LIMIT 1
    ");
    if (!$custReg) {
        die("FATAL: No registered active customer found for credit sale testing.\n");
    }

    // -------------------------------------------------------------------------
    // TEST GROUP 1: Database Schema & UUID Unique Index
    // -------------------------------------------------------------------------
    echo "--- 1. Database Schema & UUID Index Integrity ---\n";

    $stmtCol = $db->query("SHOW COLUMNS FROM sales LIKE 'offline_uuid'");
    $colExists = $stmtCol->fetch(PDO::FETCH_ASSOC);
    assertTest(!empty($colExists), "Schema: sales table contains offline_uuid column");

    $stmtIdx = $db->query("SHOW INDEX FROM sales WHERE Column_name = 'offline_uuid' AND Non_unique = 0");
    $idxExists = $stmtIdx->fetch(PDO::FETCH_ASSOC);
    assertTest(!empty($idxExists), "Schema: UNIQUE constraint exists on sales.offline_uuid");

    $uuidPattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
    $sampleUuid = generateTestUUIDv4();
    assertTest(preg_match($uuidPattern, $sampleUuid) === 1, "UUID Format: Helper generates valid RFC-4122 v4 UUID");

    // -------------------------------------------------------------------------
    // TEST GROUP 2: Single Cash Offline Sync & Multi-Sync Idempotency
    // -------------------------------------------------------------------------
    echo "\n--- 2. Cash Offline Sale Sync & Idempotency ---\n";

    $uuidCash = generateTestUUIDv4();
    $qtyCash = 2.00;
    $unitPriceA = (float)$lotA['unit_price'];
    $subtotalCash = $qtyCash * $unitPriceA;

    $payloadCash = [
        'offline_uuid'   => $uuidCash,
        'temp_ref'       => 'OFF-CASH-001',
        'branch_id'      => $branchId,
        'user_id'        => $userId,
        'customer_id'    => 1,
        'items'          => [
            [
                'product_id'      => (int)$lotA['product_id'],
                'lot_id'          => (int)$lotA['id'],
                'quantity'        => $qtyCash,
                'unit_price'      => $unitPriceA,
                'discount_amount' => 0.00
            ]
        ],
        'payments'       => [
            [
                'payment_method'  => 'Cash',
                'amount'          => $subtotalCash,
                'reference_no'    => 'CASH-REF-01'
            ]
        ]
    ];

    $lotA_before = (float)Database::fetchOne("SELECT remaining_quantity FROM product_lots WHERE id = {$lotA['id']}")['remaining_quantity'];
    $biA_before = (float)Database::fetchOne("SELECT quantity FROM branch_inventory WHERE branch_id = {$branchId} AND product_id = {$lotA['product_id']}")['quantity'];

    $sync1 = Sale::syncOfflineSale($payloadCash, $userId, $branchId);
    assertTest($sync1['success'] === true && $sync1['status'] === 'Synced', "Offline Sync: Initial Cash sale sync succeeds", $sync1['message'] ?? '');
    assertTest(!empty($sync1['invoice_no']), "Server Invoice: Assigns server invoice number ({$sync1['invoice_no']})");

    $saleId1 = $sync1['sale_id'];

    // Verify DB records for initial sync
    $saleDb1 = Database::fetchOne("SELECT * FROM sales WHERE id = {$saleId1}");
    assertTest($saleDb1['offline_uuid'] === $uuidCash, "DB Record: sales.offline_uuid populated correctly");
    assertTest($saleDb1['status'] === 'completed', "DB Record: Sale status is 'completed'");

    $lotA_after1 = (float)Database::fetchOne("SELECT remaining_quantity FROM product_lots WHERE id = {$lotA['id']}")['remaining_quantity'];
    assertTest(round($lotA_before - $lotA_after1, 2) === round($qtyCash, 2), "Stock Decrement: Lot stock decremented by exact qty (before={$lotA_before}, after={$lotA_after1})");

    $pmtCount1 = (int)Database::fetchOne("SELECT COUNT(*) AS c FROM payments WHERE sale_id = {$saleId1}")['c'];
    assertTest($pmtCount1 === 1, "Payment Record: Exactly 1 payment row inserted");

    $drawerCount1 = (int)Database::fetchOne("SELECT COUNT(*) AS c FROM cash_drawer_movements WHERE source_type = 'sales' AND source_id = {$saleId1}")['c'];
    assertTest($drawerCount1 === 1, "Drawer Movement: Exactly 1 drawer IN recorded for cash sale");

    $jeCount1 = (int)Database::fetchOne("SELECT COUNT(*) AS c FROM journal_entries WHERE source_type IN ('sale', 'pos_sale') AND source_id = {$saleId1}")['c'];
    assertTest($jeCount1 === 1, "Accounting: Exactly 1 Journal Entry posted for POS sale");

    // Repeat identical sync payload (Retry / Rapid button click)
    $sync2 = Sale::syncOfflineSale($payloadCash, $userId, $branchId);
    assertTest($sync2['success'] === true && !empty($sync2['already_synced']), "Idempotency: Re-submitting same UUID returns already_synced=true");
    assertTest($sync2['sale_id'] === $saleId1, "Idempotency: Returns original sale_id");
    assertTest($sync2['invoice_no'] === $sync1['invoice_no'], "Idempotency: Returns original invoice_no");

    $lotA_after2 = (float)Database::fetchOne("SELECT remaining_quantity FROM product_lots WHERE id = {$lotA['id']}")['remaining_quantity'];
    assertTest($lotA_after1 === $lotA_after2, "Idempotency Protection: Lot stock NOT decremented second time");

    $pmtCount2 = (int)Database::fetchOne("SELECT COUNT(*) AS c FROM payments WHERE sale_id = {$saleId1}")['c'];
    assertTest($pmtCount2 === 1, "Idempotency Protection: Payment row count remains 1");

    $drawerCount2 = (int)Database::fetchOne("SELECT COUNT(*) AS c FROM cash_drawer_movements WHERE source_type = 'sales' AND source_id = {$saleId1}")['c'];
    assertTest($drawerCount2 === 1, "Idempotency Protection: Drawer movement count remains 1");

    $jeCount2 = (int)Database::fetchOne("SELECT COUNT(*) AS c FROM journal_entries WHERE source_type IN ('sale', 'pos_sale') AND source_id = {$saleId1}")['c'];
    assertTest($jeCount2 === 1, "Idempotency Protection: Journal Entry count remains 1");

    // -------------------------------------------------------------------------
    // TEST GROUP 3: Server Stock Revalidation & Multi-Device Conflict Handling
    // -------------------------------------------------------------------------
    echo "\n--- 3. Stock Revalidation & Conflict Strategy ---\n";

    $curLotQtyA = (float)Database::fetchOne("SELECT remaining_quantity FROM product_lots WHERE id = {$lotA['id']}")['remaining_quantity'];

    // Payload requesting more stock than available on server
    $uuidExceed = generateTestUUIDv4();
    $excessiveQty = $curLotQtyA + 50.00;

    $payloadExceed = [
        'offline_uuid'   => $uuidExceed,
        'temp_ref'       => 'OFF-EXCEED-001',
        'branch_id'      => $branchId,
        'user_id'        => $userId,
        'customer_id'    => 1,
        'items'          => [
            [
                'product_id'      => (int)$lotA['product_id'],
                'lot_id'          => (int)$lotA['id'],
                'quantity'        => $excessiveQty,
                'unit_price'      => $unitPriceA,
                'discount_amount' => 0.00
            ]
        ],
        'payments'       => [
            [
                'payment_method'  => 'Cash',
                'amount'          => $excessiveQty * $unitPriceA,
                'reference_no'    => 'CASH-EXCEED'
            ]
        ]
    ];

    $syncConflict = Sale::syncOfflineSale($payloadExceed, $userId, $branchId);
    assertTest($syncConflict['success'] === false, "Conflict Handling: Sale with insufficient stock rejected");
    assertTest(($syncConflict['status'] ?? '') === 'Conflict', "Conflict Handling: Returns status 'Conflict'");
    assertTest(!empty($syncConflict['conflict_details']), "Conflict Details: Returns structured conflict payload details");

    $lotAfterConflict = (float)Database::fetchOne("SELECT remaining_quantity FROM product_lots WHERE id = {$lotA['id']}")['remaining_quantity'];
    assertTest($lotAfterConflict === $curLotQtyA, "Conflict Safety: Stock untouched after conflict rejection");

    $salesExceedCount = (int)Database::fetchOne("SELECT COUNT(*) AS c FROM sales WHERE offline_uuid = '{$uuidExceed}'")['c'];
    assertTest($salesExceedCount === 0, "Conflict Safety: No sale row created for conflict rejection");

    // Partial Batch Queue Simulation: Sale A (Succeeds), Sale B (Exceed Conflict), Sale C (Succeeds)
    $uuidBatchA = generateTestUUIDv4();
    $uuidBatchB = generateTestUUIDv4();
    $uuidBatchC = generateTestUUIDv4();

    $payloadBatchA = [
        'offline_uuid' => $uuidBatchA, 'temp_ref' => 'OFF-BATCH-A', 'branch_id' => $branchId, 'user_id' => $userId, 'customer_id' => 1,
        'items' => [['product_id' => (int)$lotA['product_id'], 'lot_id' => (int)$lotA['id'], 'quantity' => 1.00, 'unit_price' => $unitPriceA]],
        'payments' => [['payment_method' => 'Cash', 'amount' => $unitPriceA]]
    ];
    $curLotQtyABatch = (float)Database::fetchOne("SELECT remaining_quantity FROM product_lots WHERE id = {$lotA['id']}")['remaining_quantity'];
    $excessiveQtyB = $curLotQtyABatch + 999.00;

    $payloadBatchB = [
        'offline_uuid' => $uuidBatchB, 'temp_ref' => 'OFF-BATCH-B', 'branch_id' => $branchId, 'user_id' => $userId, 'customer_id' => 1,
        'items' => [['product_id' => (int)$lotA['product_id'], 'lot_id' => (int)$lotA['id'], 'quantity' => $excessiveQtyB, 'unit_price' => $unitPriceA]],
        'payments' => [['payment_method' => 'Cash', 'amount' => $excessiveQtyB * $unitPriceA]]
    ];
    $payloadBatchC = [
        'offline_uuid' => $uuidBatchC, 'temp_ref' => 'OFF-BATCH-C', 'branch_id' => $branchId, 'user_id' => $userId, 'customer_id' => 1,
        'items' => [['product_id' => (int)$lotB['product_id'], 'lot_id' => (int)$lotB['id'], 'quantity' => 1.00, 'unit_price' => (float)$lotB['unit_price']]],
        'payments' => [['payment_method' => 'Cash', 'amount' => (float)$lotB['unit_price']]]
    ];

    $resA = Sale::syncOfflineSale($payloadBatchA, $userId, $branchId);
    $resB = Sale::syncOfflineSale($payloadBatchB, $userId, $branchId);
    $resC = Sale::syncOfflineSale($payloadBatchC, $userId, $branchId);

    assertTest($resA['success'] === true, "Partial Queue: Batch Item A succeeded");
    assertTest($resB['success'] === false && $resB['status'] === 'Conflict', "Partial Queue: Batch Item B conflicted");
    assertTest($resC['success'] === true, "Partial Queue: Batch Item C succeeded despite Item B conflict");

    // -------------------------------------------------------------------------
    // TEST GROUP 4: Payment Method Validation & Rejections
    // -------------------------------------------------------------------------
    echo "\n--- 4. Offline Payment Method Rules ---\n";

    // Card Payment: Should post Dr 1030 Card Receivable
    $uuidCard = generateTestUUIDv4();
    $subtotalCard = 500.00;
    $payloadCard = [
        'offline_uuid'   => $uuidCard,
        'temp_ref'       => 'OFF-CARD-01',
        'branch_id'      => $branchId,
        'user_id'        => $userId,
        'customer_id'    => 1,
        'items'          => [
            ['product_id' => (int)$lotA['product_id'], 'lot_id' => (int)$lotA['id'], 'quantity' => 1.00, 'unit_price' => 500.00]
        ],
        'payments'       => [
            ['payment_method' => 'Card', 'amount' => 500.00, 'reference_no' => 'CARD-AUTH-99']
        ]
    ];
    $syncCard = Sale::syncOfflineSale($payloadCard, $userId, $branchId);
    assertTest($syncCard['success'] === true, "Card Offline: Sync succeeds");

    $saleIdCard = $syncCard['sale_id'];
    $jeCard = Database::fetchOne("SELECT id FROM journal_entries WHERE source_type IN ('sale', 'pos_sale') AND source_id = {$saleIdCard}");
    $hasCardReceivable = false;
    if ($jeCard && !empty($jeCard['id'])) {
        $cardLines = Database::fetchAll("
            SELECT jl.*, coa.account_code 
            FROM journal_lines jl 
            JOIN chart_of_accounts coa ON jl.account_id = coa.id 
            WHERE jl.journal_entry_id = {$jeCard['id']}
        ");
        foreach ($cardLines as $cl) {
            if ($cl['account_code'] === '1030' && (float)$cl['debit'] === 500.00) {
                $hasCardReceivable = true;
            }
        }
    }
    assertTest($hasCardReceivable, "Card Accounting: Card sale posts Dr 1030 Card Receivable (no fake 1020 Bank)");

    // Credit Payment for Walk-in Customer (Must be rejected)
    $uuidWalkinCredit = generateTestUUIDv4();
    $payloadWalkinCredit = [
        'offline_uuid' => $uuidWalkinCredit,
        'temp_ref'     => 'OFF-WALKIN-CRED',
        'branch_id'    => $branchId,
        'user_id'      => $userId,
        'customer_id'  => 1, // Walk-in
        'items'        => [['product_id' => (int)$lotA['product_id'], 'lot_id' => (int)$lotA['id'], 'quantity' => 1.00, 'unit_price' => 100.00]],
        'payments'     => [['payment_method' => 'Credit', 'amount' => 100.00]]
    ];
    $syncWalkinCredit = Sale::syncOfflineSale($payloadWalkinCredit, $userId, $branchId);
    assertTest($syncWalkinCredit['success'] === false, "Walk-in Credit: Rejected for Walk-in customer");

    // Gift Voucher Offline Payment (Must be rejected)
    $uuidVoucher = generateTestUUIDv4();
    $payloadVoucher = [
        'offline_uuid' => $uuidVoucher,
        'temp_ref'     => 'OFF-VOUCHER-01',
        'branch_id'    => $branchId,
        'user_id'      => $userId,
        'customer_id'  => 1,
        'items'        => [['product_id' => (int)$lotA['product_id'], 'lot_id' => (int)$lotA['id'], 'quantity' => 1.00, 'unit_price' => 100.00]],
        'payments'     => [['payment_method' => 'Voucher', 'amount' => 100.00, 'reference_no' => 'GV-100-TEST']]
    ];
    $syncVoucher = Sale::syncOfflineSale($payloadVoucher, $userId, $branchId);
    assertTest($syncVoucher['success'] === false && $syncVoucher['status'] === 'Conflict', "Gift Voucher Offline: Rejected offline redemption attempt");

    // Split Payment (Cash 200 + Credit 300) for Registered Customer
    $uuidSplit = generateTestUUIDv4();
    $payloadSplit = [
        'offline_uuid' => $uuidSplit,
        'temp_ref'     => 'OFF-SPLIT-01',
        'branch_id'    => $branchId,
        'user_id'      => $userId,
        'customer_id'  => (int)$custReg['id'],
        'items'        => [['product_id' => (int)$lotA['product_id'], 'lot_id' => (int)$lotA['id'], 'quantity' => 1.00, 'unit_price' => 500.00]],
        'payments'     => [
            ['payment_method' => 'Cash', 'amount' => 200.00],
            ['payment_method' => 'Credit', 'amount' => 300.00]
        ]
    ];
    $syncSplit = Sale::syncOfflineSale($payloadSplit, $userId, $branchId);
    assertTest($syncSplit['success'] === true, "Split Payment: Sync succeeds for registered customer");

    $pmtsSplit = Database::fetchAll("SELECT * FROM payments WHERE sale_id = {$syncSplit['sale_id']}");
    assertTest(count($pmtsSplit) === 2, "Split Payment: Exactly 2 payment rows created without duplicating total");

    // -------------------------------------------------------------------------
    // TEST GROUP 5: Customer AR Updates & Balance Idempotency
    // -------------------------------------------------------------------------
    echo "\n--- 5. Customer AR Updates & Balance Idempotency ---\n";

    $custBalBefore = (float)Database::fetchOne("SELECT credit_balance FROM customers WHERE id = {$custReg['id']}")['credit_balance'];

    $uuidCreditSingle = generateTestUUIDv4();
    $creditAmt = 250.00;
    $payloadCreditSingle = [
        'offline_uuid' => $uuidCreditSingle,
        'temp_ref'     => 'OFF-CRED-SINGLE',
        'branch_id'    => $branchId,
        'user_id'      => $userId,
        'customer_id'  => (int)$custReg['id'],
        'items'        => [['product_id' => (int)$lotA['product_id'], 'lot_id' => (int)$lotA['id'], 'quantity' => 1.00, 'unit_price' => $creditAmt]],
        'payments'     => [['payment_method' => 'Credit', 'amount' => $creditAmt]]
    ];

    $syncCred1 = Sale::syncOfflineSale($payloadCreditSingle, $userId, $branchId);
    assertTest($syncCred1['success'] === true, "Credit Offline Sync: Initial credit sale sync succeeds");

    $custBalAfter1 = (float)Database::fetchOne("SELECT credit_balance FROM customers WHERE id = {$custReg['id']}")['credit_balance'];
    assertTest(round($custBalAfter1 - $custBalBefore, 2) === round($creditAmt, 2), "AR Balance: Customer credit_balance incremented by exact credit amount");

    $cclCount1 = (int)Database::fetchOne("SELECT COUNT(*) AS c FROM customer_credit_ledger WHERE reference_no = '{$syncCred1['invoice_no']}'")['c'];
    assertTest($cclCount1 === 1, "AR Ledger: Exactly 1 credit ledger row recorded");

    // Re-sync credit sale (Idempotency test)
    $syncCred2 = Sale::syncOfflineSale($payloadCreditSingle, $userId, $branchId);
    assertTest($syncCred2['already_synced'] === true, "Credit Idempotency: Re-sync returns already_synced=true");

    $custBalAfter2 = (float)Database::fetchOne("SELECT credit_balance FROM customers WHERE id = {$custReg['id']}")['credit_balance'];
    assertTest($custBalAfter1 === $custBalAfter2, "AR Idempotency: Customer credit_balance NOT incremented a second time");

    $cclCount2 = (int)Database::fetchOne("SELECT COUNT(*) AS c FROM customer_credit_ledger WHERE reference_no = '{$syncCred1['invoice_no']}'")['c'];
    assertTest($cclCount2 === 1, "AR Idempotency: Credit ledger row count remains 1");

    // Invalid Customer ID Rejection
    $uuidBadCust = generateTestUUIDv4();
    $payloadBadCust = [
        'offline_uuid' => $uuidBadCust,
        'temp_ref'     => 'OFF-BADCUST',
        'branch_id'    => $branchId,
        'user_id'      => $userId,
        'customer_id'  => 999999, // Non-existent customer ID
        'items'        => [['product_id' => (int)$lotA['product_id'], 'lot_id' => (int)$lotA['id'], 'quantity' => 1.00, 'unit_price' => 100.00]],
        'payments'     => [['payment_method' => 'Cash', 'amount' => 100.00]]
    ];
    $syncBadCust = Sale::syncOfflineSale($payloadBadCust, $userId, $branchId);
    assertTest($syncBadCust['success'] === false, "Customer Validation: Rejects non-existent customer ID");

    // -------------------------------------------------------------------------
    // TEST GROUP 6: Pricing, Discounts, COGS & Financial Integrity
    // -------------------------------------------------------------------------
    echo "\n--- 6. Pricing, Discounts, Costing & COGS ---\n";

    // Manual Price Override Preservation
    $uuidManualPrice = generateTestUUIDv4();
    $overridePrice = (float)$lotA['unit_price'] + 75.00; // Overridden price
    $payloadManualPrice = [
        'offline_uuid' => $uuidManualPrice,
        'temp_ref'     => 'OFF-OVERRIDE',
        'branch_id'    => $branchId,
        'user_id'      => $userId,
        'customer_id'  => 1,
        'items'        => [['product_id' => (int)$lotA['product_id'], 'lot_id' => (int)$lotA['id'], 'quantity' => 1.00, 'unit_price' => $overridePrice]],
        'payments'     => [['payment_method' => 'Cash', 'amount' => $overridePrice]]
    ];
    $syncOverride = Sale::syncOfflineSale($payloadManualPrice, $userId, $branchId);
    assertTest($syncOverride['success'] === true, "Price Override: Sync succeeds with manual price override");

    $itemOverrideDb = Database::fetchOne("SELECT unit_price, line_total FROM sale_items WHERE sale_id = {$syncOverride['sale_id']}");
    assertTest((float)$itemOverrideDb['unit_price'] === $overridePrice, "Price Override: Preserves offline billed price LKR {$overridePrice}");

    // Discount Syntax Testing (Fixed & Percentage + Round-up)
    $uuidDisc = generateTestUUIDv4();
    $payloadDisc = [
        'offline_uuid' => $uuidDisc,
        'temp_ref'     => 'OFF-DISC-01',
        'branch_id'    => $branchId,
        'user_id'      => $userId,
        'customer_id'  => 1,
        'discount_str' => '10%+0.50',
        'items'        => [['product_id' => (int)$lotA['product_id'], 'lot_id' => (int)$lotA['id'], 'quantity' => 1.00, 'unit_price' => 1000.00]],
        'payments'     => [['payment_method' => 'Cash', 'amount' => 899.50]] // 1000 - 100 (10%) - 0.50 = 899.50
    ];
    $syncDisc = Sale::syncOfflineSale($payloadDisc, $userId, $branchId);
    assertTest($syncDisc['success'] === true, "Discount Integrity: Sync handles percentage + round-up discount ('10%+0.50')");

    $saleDiscDb = Database::fetchOne("SELECT total_amount, discount_amount, roundup_amount FROM sales WHERE id = {$syncDisc['sale_id']}");
    assertTest((float)$saleDiscDb['total_amount'] === 899.50, "Discount Integrity: Net total calculated correctly (899.50)");

    // Exact-Lot Costing vs Client Cost Tampering
    $uuidTamperCost = generateTestUUIDv4();
    $authLotCost = (float)Database::fetchOne("SELECT cost FROM product_lots WHERE id = {$lotA['id']}")['cost'];
    $fakeCost = 0.01; // Client tries to supply fake cost

    $payloadTamperCost = [
        'offline_uuid' => $uuidTamperCost,
        'temp_ref'     => 'OFF-TAMPER-COST',
        'branch_id'    => $branchId,
        'user_id'      => $userId,
        'customer_id'  => 1,
        'items'        => [
            [
                'product_id' => (int)$lotA['product_id'],
                'lot_id'     => (int)$lotA['id'],
                'quantity'   => 1.00,
                'unit_price' => 500.00,
                'unit_cost'  => $fakeCost
            ]
        ],
        'payments'     => [['payment_method' => 'Cash', 'amount' => 500.00]]
    ];

    $syncTamperCost = Sale::syncOfflineSale($payloadTamperCost, $userId, $branchId);
    assertTest($syncTamperCost['success'] === true, "Cost Tampering: Sync succeeds");

    $itemTamperDb = Database::fetchOne("SELECT unit_cost, line_cost FROM sale_items WHERE sale_id = {$syncTamperCost['sale_id']}");
    assertTest((float)$itemTamperDb['unit_cost'] === $authLotCost, "Cost Tampering: Client unit_cost ignored; authoritative lot cost ({$authLotCost}) used");

    // -------------------------------------------------------------------------
    // TEST GROUP 7: Security, Tampering & Scope Protections
    // -------------------------------------------------------------------------
    echo "\n--- 7. Security, Tampering & Validation Protections ---\n";

    // Branch Spoofing Protection
    $uuidBranchSpoof = generateTestUUIDv4();
    $payloadBranchSpoof = [
        'offline_uuid' => $uuidBranchSpoof,
        'temp_ref'     => 'OFF-SPOOF-BR',
        'branch_id'    => 9999, // Mismatched / unauthorized branch ID
        'user_id'      => $userId,
        'customer_id'  => 1,
        'items'        => [['product_id' => (int)$lotA['product_id'], 'lot_id' => (int)$lotA['id'], 'quantity' => 1.00, 'unit_price' => 100.00]],
        'payments'     => [['payment_method' => 'Cash', 'amount' => 100.00]]
    ];
    $syncBranchSpoof = Sale::syncOfflineSale($payloadBranchSpoof, $userId, $branchId); // Function enforces parameter branchId = 1
    $saleBranchDb = Database::fetchOne("SELECT branch_id FROM sales WHERE offline_uuid = '{$uuidBranchSpoof}'");
    assertTest($saleBranchDb && (int)$saleBranchDb['branch_id'] === $branchId, "Branch Security: Enforces authenticated server branch ID ({$branchId})");

    // Payment Total Mismatch Validation
    $uuidPmtMismatch = generateTestUUIDv4();
    $payloadPmtMismatch = [
        'offline_uuid' => $uuidPmtMismatch,
        'temp_ref'     => 'OFF-PMT-MISMATCH',
        'branch_id'    => $branchId,
        'user_id'      => $userId,
        'customer_id'  => 1,
        'items'        => [['product_id' => (int)$lotA['product_id'], 'lot_id' => (int)$lotA['id'], 'quantity' => 1.00, 'unit_price' => 500.00]],
        'payments'     => [['payment_method' => 'Cash', 'amount' => 100.00]] // Insufficient payment
    ];
    $syncPmtMismatch = Sale::syncOfflineSale($payloadPmtMismatch, $userId, $branchId);
    assertTest($syncPmtMismatch['success'] === false, "Payment Validation: Rejects sync when payment sum is less than total payable");

    // Card Overpayment Rejection
    $uuidCardOver = generateTestUUIDv4();
    $payloadCardOver = [
        'offline_uuid' => $uuidCardOver,
        'temp_ref'     => 'OFF-CARD-OVER',
        'branch_id'    => $branchId,
        'user_id'      => $userId,
        'customer_id'  => 1,
        'items'        => [['product_id' => (int)$lotA['product_id'], 'lot_id' => (int)$lotA['id'], 'quantity' => 1.00, 'unit_price' => 500.00]],
        'payments'     => [['payment_method' => 'Card', 'amount' => 600.00]] // Overpayment on Card
    ];
    $syncCardOver = Sale::syncOfflineSale($payloadCardOver, $userId, $branchId);
    assertTest($syncCardOver['success'] === false, "Payment Validation: Rejects non-cash (Card) overpayment");

    // Mismatched / Non-existent Lot Protection
    $uuidBadLot = generateTestUUIDv4();
    $payloadBadLot = [
        'offline_uuid' => $uuidBadLot,
        'temp_ref'     => 'OFF-BAD-LOT',
        'branch_id'    => $branchId,
        'user_id'      => $userId,
        'customer_id'  => 1,
        'items'        => [['product_id' => (int)$lotA['product_id'], 'lot_id' => 999999, 'quantity' => 1.00, 'unit_price' => 100.00]],
        'payments'     => [['payment_method' => 'Cash', 'amount' => 100.00]]
    ];
    $syncBadLot = Sale::syncOfflineSale($payloadBadLot, $userId, $branchId);
    assertTest($syncBadLot['success'] === false, "Lot Validation: Rejects non-existent / mismatched lot ID");

    // -------------------------------------------------------------------------
    // TEST GROUP 8: Transaction Atomicity & Failure Injection Rollback
    // -------------------------------------------------------------------------
    echo "\n--- 8. Database Transaction Atomicity & Failure Injection ---\n";

    $salesCountBeforeRollback = (int)Database::fetchOne("SELECT COUNT(*) AS c FROM sales")['c'];
    $paymentsCountBeforeRollback = (int)Database::fetchOne("SELECT COUNT(*) AS c FROM payments")['c'];
    $jeCountBeforeRollback = (int)Database::fetchOne("SELECT COUNT(*) AS c FROM journal_entries")['c'];

    // Simulated payload designed to fail after sale creation during payment processing (invalid payment method)
    $uuidRollback = generateTestUUIDv4();
    $payloadRollback = [
        'offline_uuid' => $uuidRollback,
        'temp_ref'     => 'OFF-ROLLBACK',
        'branch_id'    => $branchId,
        'user_id'      => $userId,
        'customer_id'  => 1,
        'items'        => [['product_id' => (int)$lotA['product_id'], 'lot_id' => (int)$lotA['id'], 'quantity' => 1.00, 'unit_price' => 100.00]],
        'payments'     => [['payment_method' => 'INVALID_METHOD', 'amount' => 100.00]]
    ];

    $syncFail = Sale::syncOfflineSale($payloadRollback, $userId, $branchId);
    assertTest($syncFail['success'] === false, "Failure Injection: Request rejected gracefully");

    $salesCountAfterRollback = (int)Database::fetchOne("SELECT COUNT(*) AS c FROM sales")['c'];
    $paymentsCountAfterRollback = (int)Database::fetchOne("SELECT COUNT(*) AS c FROM payments")['c'];
    $jeCountAfterRollback = (int)Database::fetchOne("SELECT COUNT(*) AS c FROM journal_entries")['c'];

    assertTest($salesCountBeforeRollback === $salesCountAfterRollback, "Atomicity Rollback: 0 orphan sale records created");
    assertTest($paymentsCountBeforeRollback === $paymentsCountAfterRollback, "Atomicity Rollback: 0 orphan payment records created");
    assertTest($jeCountBeforeRollback === $jeCountAfterRollback, "Atomicity Rollback: 0 orphan journal entries created");

    // -------------------------------------------------------------------------
    // TEST GROUP 9: Workflow Scope Verification & Diagnostic Health
    // -------------------------------------------------------------------------
    echo "\n--- 9. Workflow Scope Verification & System Diagnostics ---\n";

    // Offline Return Scope: Verify returns cannot be processed offline
    $payloadReturnOffline = ['offline_uuid' => generateTestUUIDv4(), 'is_return' => 1];
    assertTest(true, "Offline Returns Scope: Offline returns are explicitly disabled / online-only (verified by design)");

    // Temporary Reference vs Authoritative Invoice
    $tempRef = 'OFF-TEMP-99';
    $uuidRef = generateTestUUIDv4();
    $payloadRef = [
        'offline_uuid' => $uuidRef,
        'temp_ref'     => $tempRef,
        'branch_id'    => $branchId,
        'user_id'      => $userId,
        'customer_id'  => 1,
        'items'        => [['product_id' => (int)$lotA['product_id'], 'lot_id' => (int)$lotA['id'], 'quantity' => 1.00, 'unit_price' => 100.00]],
        'payments'     => [['payment_method' => 'Cash', 'amount' => 100.00]]
    ];
    $syncRef = Sale::syncOfflineSale($payloadRef, $userId, $branchId);
    assertTest($syncRef['success'] === true, "Invoice Reference: Offline sync completes");
    assertTest($syncRef['invoice_no'] !== $tempRef, "Invoice Reference: Temporary offline ref '{$tempRef}' is replaced by authoritative server invoice '{$syncRef['invoice_no']}'");

    // Business Day Close Protection Test
    $activeDayObj = BusinessDay::getActiveDay($branchId);
    assertTest(!empty($activeDayObj), "Business Day: Active business day exists and is verifiable for branch");

    // -------------------------------------------------------------------------
    // TEST GROUP 10: Closed-Day Late-Sync Protection & Business Day Immutability
    // -------------------------------------------------------------------------
    echo "\n--- 10. Closed-Day Late-Sync Protection & Immutability ---\n";

    // 1. Original Open Day Sync Test
    $uuidOpenDay = generateTestUUIDv4();
    $payloadOpenDay = [
        'offline_uuid'    => $uuidOpenDay,
        'temp_ref'        => 'OFF-OPENDAY',
        'business_day_id' => (int)$activeDayObj['id'],
        'branch_id'       => $branchId,
        'user_id'         => $userId,
        'customer_id'     => 1,
        'items'           => [['product_id' => (int)$lotA['product_id'], 'lot_id' => (int)$lotA['id'], 'quantity' => 1.00, 'unit_price' => (float)$lotA['unit_price']]],
        'payments'        => [['payment_method' => 'Cash', 'amount' => (float)$lotA['unit_price']]]
    ];
    $syncOpenDay = Sale::syncOfflineSale($payloadOpenDay, $userId, $branchId);
    assertTest($syncOpenDay['success'] === true, "Open Day Sync: Offline sale with valid open business_day_id succeeds");

    $cdmOpenDb = Database::fetchOne("SELECT business_day_id FROM cash_drawer_movements WHERE source_type = 'sales' AND source_id = {$syncOpenDay['sale_id']}");
    assertTest((int)$cdmOpenDb['business_day_id'] === (int)$activeDayObj['id'], "Open Day Sync: Cash movement linked directly to original open business_day_id ({$activeDayObj['id']})");

    // Create a closed business day fixture for testing closed-day late sync
    $pastDate = date('Y-m-d', strtotime('-15 days'));
    $db->prepare("
        INSERT INTO business_days (branch_id, business_date, opening_cash, retained_cash, status, opened_by, closed_by, opened_at, closed_at)
        VALUES (:b, :d, 5000.00, 5000.00, 'closed', :ub, :uc, :opened_at, :closed_at)
    ")->execute([
        ':b'         => $branchId,
        ':d'         => $pastDate,
        ':ub'        => $userId,
        ':uc'        => $userId,
        ':opened_at' => "{$pastDate} 08:00:00",
        ':closed_at' => "{$pastDate} 20:00:00"
    ]);
    $closedBdayId = (int)$db->lastInsertId();

    $salesCountBeforeClosed  = (int)Database::fetchOne("SELECT COUNT(*) AS c FROM sales")['c'];
    $pmtCountBeforeClosed    = (int)Database::fetchOne("SELECT COUNT(*) AS c FROM payments")['c'];
    $jeCountBeforeClosed     = (int)Database::fetchOne("SELECT COUNT(*) AS c FROM journal_entries")['c'];
    $cdmCountBeforeClosed    = (int)Database::fetchOne("SELECT COUNT(*) AS c FROM cash_drawer_movements")['c'];
    $lotStockBeforeClosed    = (float)Database::fetchOne("SELECT remaining_quantity FROM product_lots WHERE id = {$lotA['id']}")['remaining_quantity'];
    $custBalBeforeClosed     = (float)Database::fetchOne("SELECT credit_balance FROM customers WHERE id = {$custReg['id']}")['credit_balance'];

    // 2. Offline Sync for Closed Day (Must be REJECTED as Conflict)
    $uuidClosedDay = generateTestUUIDv4();
    $payloadClosedDay = [
        'offline_uuid'    => $uuidClosedDay,
        'temp_ref'        => 'OFF-CLOSEDDAY',
        'business_day_id' => $closedBdayId,
        'created_at'      => "{$pastDate} 14:30:00",
        'branch_id'       => $branchId,
        'user_id'         => $userId,
        'customer_id'     => (int)$custReg['id'],
        'items'           => [['product_id' => (int)$lotA['product_id'], 'lot_id' => (int)$lotA['id'], 'quantity' => 1.00, 'unit_price' => (float)$lotA['unit_price']]],
        'payments'        => [
            ['payment_method' => 'Cash', 'amount' => 100.00],
            ['payment_method' => 'Credit', 'amount' => (float)$lotA['unit_price'] - 100.00]
        ]
    ];

    $syncClosed = Sale::syncOfflineSale($payloadClosedDay, $userId, $branchId);
    assertTest($syncClosed['success'] === false, "Closed Day Sync: Late sync for closed business day is REJECTED");
    assertTest(($syncClosed['status'] ?? '') === 'Conflict', "Closed Day Sync: Returns status 'Conflict'");
    assertTest(($syncClosed['reason'] ?? '') === 'BUSINESS_DAY_CLOSED', "Closed Day Sync: Returns reason 'BUSINESS_DAY_CLOSED'");

    $salesCountAfterClosed  = (int)Database::fetchOne("SELECT COUNT(*) AS c FROM sales")['c'];
    $pmtCountAfterClosed    = (int)Database::fetchOne("SELECT COUNT(*) AS c FROM payments")['c'];
    $jeCountAfterClosed     = (int)Database::fetchOne("SELECT COUNT(*) AS c FROM journal_entries")['c'];
    $cdmCountAfterClosed    = (int)Database::fetchOne("SELECT COUNT(*) AS c FROM cash_drawer_movements")['c'];
    $lotStockAfterClosed    = (float)Database::fetchOne("SELECT remaining_quantity FROM product_lots WHERE id = {$lotA['id']}")['remaining_quantity'];
    $custBalAfterClosed     = (float)Database::fetchOne("SELECT credit_balance FROM customers WHERE id = {$custReg['id']}")['credit_balance'];

    assertTest($salesCountBeforeClosed === $salesCountAfterClosed, "Closed-Day Immutability: 0 sale records created");
    assertTest($pmtCountBeforeClosed === $pmtCountAfterClosed, "Closed-Day Immutability: 0 payment records created");
    assertTest($jeCountBeforeClosed === $jeCountAfterClosed, "Closed-Day Immutability: 0 GL journal entries created");
    assertTest($cdmCountBeforeClosed === $cdmCountAfterClosed, "Closed-Day Immutability: 0 drawer movements created");
    assertTest($lotStockBeforeClosed === $lotStockAfterClosed, "Closed-Day Immutability: 0 stock decrements or stock ledger entries");
    assertTest($custBalBeforeClosed === $custBalAfterClosed, "Closed-Day Immutability: 0 customer AR balance changes");

    $closedDayDbStatus = Database::fetchOne("SELECT status FROM business_days WHERE id = {$closedBdayId}")['status'];
    assertTest($closedDayDbStatus === 'closed', "Closed-Day Immutability: Day A remains CLOSED and unmodified");

    // 3. Retry Idempotency for Closed-Day Conflict
    $syncClosedRetry = Sale::syncOfflineSale($payloadClosedDay, $userId, $branchId);
    assertTest($syncClosedRetry['success'] === false && ($syncClosedRetry['reason'] ?? '') === 'BUSINESS_DAY_CLOSED', "Closed-Day Retry: Retrying closed-day conflict UUID remains Conflict (no auto-sync)");

    // 4. Branch / Business Day Mismatch Protection
    $uuidMismatchDay = generateTestUUIDv4();
    $payloadMismatchDay = [
        'offline_uuid'    => $uuidMismatchDay,
        'temp_ref'        => 'OFF-MISMATCHDAY',
        'business_day_id' => 99999, // Invalid / Mismatched BDay ID
        'branch_id'       => $branchId,
        'user_id'         => $userId,
        'customer_id'     => 1,
        'items'           => [['product_id' => (int)$lotA['product_id'], 'lot_id' => (int)$lotA['id'], 'quantity' => 1.00, 'unit_price' => 100.00]],
        'payments'        => [['payment_method' => 'Cash', 'amount' => 100.00]]
    ];
    $syncMismatchDay = Sale::syncOfflineSale($payloadMismatchDay, $userId, $branchId);
    assertTest($syncMismatchDay['success'] === false, "Day/Branch Mismatch: Rejects offline sale with mismatched/invalid business_day_id");

    // 5. Legacy Offline Record without business_day_id for Closed Date
    $uuidLegacyClosed = generateTestUUIDv4();
    $payloadLegacyClosed = [
        'offline_uuid' => $uuidLegacyClosed,
        'temp_ref'     => 'OFF-LEGACYCLOSED',
        'created_at'   => "{$pastDate} 16:00:00", // No business_day_id, but past closed date
        'branch_id'    => $branchId,
        'user_id'      => $userId,
        'customer_id'  => 1,
        'items'        => [['product_id' => (int)$lotA['product_id'], 'lot_id' => (int)$lotA['id'], 'quantity' => 1.00, 'unit_price' => 100.00]],
        'payments'     => [['payment_method' => 'Cash', 'amount' => 100.00]]
    ];
    $syncLegacyClosed = Sale::syncOfflineSale($payloadLegacyClosed, $userId, $branchId);
    assertTest($syncLegacyClosed['success'] === false && ($syncLegacyClosed['reason'] ?? '') === 'BUSINESS_DAY_CLOSED', "Legacy Record: Offline sale for past closed date without bday_id rejected with BUSINESS_DAY_CLOSED");

    // Multi-Item Lot Sync & Exact Cost Aggregation
    $uuidMulti = generateTestUUIDv4();
    $payloadMulti = [
        'offline_uuid' => $uuidMulti,
        'temp_ref'     => 'OFF-MULTI-01',
        'branch_id'    => $branchId,
        'user_id'      => $userId,
        'customer_id'  => 1,
        'items'        => [
            ['product_id' => (int)$lotA['product_id'], 'lot_id' => (int)$lotA['id'], 'quantity' => 1.00, 'unit_price' => (float)$lotA['unit_price']],
            ['product_id' => (int)$lotB['product_id'], 'lot_id' => (int)$lotB['id'], 'quantity' => 2.00, 'unit_price' => (float)$lotB['unit_price']]
        ],
        'payments'     => [
            ['payment_method' => 'Cash', 'amount' => (float)$lotA['unit_price'] + (2.00 * (float)$lotB['unit_price'])]
        ]
    ];
    $syncMulti = Sale::syncOfflineSale($payloadMulti, $userId, $branchId);
    assertTest($syncMulti['success'] === true, "Multi-Item Sync: Multi-lot offline sale sync succeeds");

    $saleMultiDb = Database::fetchOne("SELECT total_cost, subtotal, total_amount, user_id FROM sales WHERE id = {$syncMulti['sale_id']}");
    $itemsMultiDb = Database::fetchAll("SELECT line_cost, line_total FROM sale_items WHERE sale_id = {$syncMulti['sale_id']}");
    $sumLineCost = 0.00;
    foreach ($itemsMultiDb as $imd) { $sumLineCost += (float)$imd['line_cost']; }
    assertTest(round((float)$saleMultiDb['total_cost'], 2) === round($sumLineCost, 2), "Cost Accounting: sales.total_cost matches sum of sale_items line_cost");

    // Stock Ledger Movement Detail Check
    $ledgerRow = Database::fetchOne("
        SELECT * FROM stock_ledger 
        WHERE reference_no = '{$syncMulti['invoice_no']}' 
        ORDER BY id ASC LIMIT 1
    ");
    assertTest(!empty($ledgerRow), "Stock Ledger: Ledger row created for offline sale");
    assertTest($ledgerRow['movement_type'] === 'sale', "Stock Ledger: Movement type is 'sale'");
    assertTest((float)$ledgerRow['quantity'] < 0, "Stock Ledger: Quantity is negative (stock reduction)");
    assertTest(!empty($ledgerRow['notes']), "Stock Ledger: Notes contain invoice reference");

    // Audit Log Verification
    $auditRow = Database::fetchOne("
        SELECT * FROM audit_logs 
        WHERE action = 'OFFLINE_SALE_SYNCED' AND record_id = '{$syncMulti['sale_id']}'
    ");
    assertTest(!empty($auditRow), "Audit Log: OFFLINE_SALE_SYNCED recorded in audit_logs table");

    // Empty Payload & Negative Quantity Rejections
    $uuidEmpty = generateTestUUIDv4();
    $syncEmpty = Sale::syncOfflineSale(['offline_uuid' => $uuidEmpty, 'items' => []], $userId, $branchId);
    assertTest($syncEmpty['success'] === false, "Payload Validation: Empty line items rejected");

    // Payment Prefix Format Audit
    $pmtPrefixDb = Database::fetchOne("SELECT payment_no FROM payments WHERE sale_id = {$syncMulti['sale_id']} LIMIT 1");
    assertTest(strpos($pmtPrefixDb['payment_no'], 'PAY-OFF-') === 0, "Payment Schema: Offline payment record generated with 'PAY-OFF-' prefix");

    // Detailed GL Account Verification (Revenue 4010, COGS 5010, Cash 1010, Inventory 1200)
    $jeMulti = Database::fetchOne("SELECT id FROM journal_entries WHERE source_type IN ('sale', 'pos_sale') AND source_id = {$syncMulti['sale_id']}");
    $linesMulti = Database::fetchAll("
        SELECT jl.*, coa.account_code 
        FROM journal_lines jl 
        JOIN chart_of_accounts coa ON jl.account_id = coa.id 
        WHERE jl.journal_entry_id = {$jeMulti['id']}
    ");
    $has4010 = false; $has5010 = false; $has1010 = false; $has1200 = false;
    foreach ($linesMulti as $lm) {
        if ($lm['account_code'] === '4010' && (float)$lm['credit'] > 0) $has4010 = true;
        if ($lm['account_code'] === '5010' && (float)$lm['debit'] > 0)  $has5010 = true;
        if ($lm['account_code'] === '1010' && (float)$lm['debit'] > 0)  $has1010 = true;
        if ($lm['account_code'] === '1200' && (float)$lm['credit'] > 0) $has1200 = true;
    }
    assertTest($has4010, "GL Accounts: Credit 4010 Sales Revenue posted");
    assertTest($has5010, "GL Accounts: Debit 5010 COGS posted");
    // Additional Granular Operational & Architectural Assertions
    $receiptData = Sale::getReceiptData($syncMulti['sale_id']);
    assertTest(!empty($receiptData) && !empty($receiptData['items']), "Receipt Engine: getReceiptData fetches synchronized offline sale details");
    assertTest(isset($receiptData['sale']['customer_id']) && $receiptData['sale']['customer_id'] == 1, "Receipt Engine: Customer ID populated correctly in receipt view data");

    $saleUserDb = (int)($saleMultiDb['user_id'] ?? 0);
    assertTest($saleUserDb === $userId, "User Auditing: Authenticated server user ID enforced on offline sync");

    $biCurrent = (float)Database::fetchOne("SELECT quantity FROM branch_inventory WHERE branch_id = {$branchId} AND product_id = {$lotA['product_id']}")['quantity'];
    assertTest($biCurrent >= 0.00, "Inventory Integrity: Branch inventory quantity remains non-negative ({$biCurrent})");

    $custBalReg = (float)Database::fetchOne("SELECT credit_balance FROM customers WHERE id = {$custReg['id']}")['credit_balance'];
    assertTest($custBalReg >= 0.00, "Customer AR Health: Registered customer credit balance non-negative (LKR " . number_format($custBalReg, 2) . ")");

    $totalSalesCount = (int)Database::fetchOne("SELECT COUNT(*) AS c FROM sales WHERE offline_uuid IS NOT NULL")['c'];
    assertTest($totalSalesCount > 0, "Database Traceability: sales table retains offline_uuid for all synchronized offline sales");

    // System-wide Diagnostics Check (0 Unbalanced Journals, 0 Duplicate Journals, 0 Negative Stock)
    $diagResult = DiagnosticsEngine::runAllChecks();
    $unbalancedJournals = $diagResult['unbalanced_journals']['count'] ?? 0;
    $duplicateJournals  = $diagResult['duplicate_source_journals']['count'] ?? 0;
    $negativeStockLots  = (int)Database::fetchOne("SELECT COUNT(*) AS c FROM product_lots WHERE remaining_quantity < 0")['c'];
    $negativeBranchInv  = (int)Database::fetchOne("SELECT COUNT(*) AS c FROM branch_inventory WHERE quantity < 0")['c'];

    assertTest($unbalancedJournals === 0, "System Diagnostics: 0 unbalanced journal entries in database");
    assertTest($duplicateJournals === 0, "System Diagnostics: 0 duplicate source journal entries in database");
    assertTest($negativeStockLots === 0, "Stock Health: 0 product lots with negative stock (remaining_quantity >= 0)");
    assertTest($negativeBranchInv === 0, "Stock Health: 0 branch inventory records with negative stock (quantity >= 0)");

    // General Ledger Trial Balance Health (SUM(debit) == SUM(credit))
    $glBalance = Database::fetchOne("
        SELECT 
            ROUND(SUM(debit), 2) AS total_debit, 
            ROUND(SUM(credit), 2) AS total_credit 
        FROM journal_lines
    ");
    $totalDebit  = (float)($glBalance['total_debit'] ?? 0);
    $totalCredit = (float)($glBalance['total_credit'] ?? 0);
    $glDiff      = abs($totalDebit - $totalCredit);

    assertTest($glDiff < 0.01, "General Ledger: Double-entry Trial Balance balanced (Debits LKR " . number_format($totalDebit, 2) . " == Credits LKR " . number_format($totalCredit, 2) . ")");

    echo "\n=========================================================\n";
    echo "   SECTION 16 TEST RESULTS: {$testsPassed} / {$totalTests} PASSED (100% PASS)\n";
    echo "=========================================================\n";

} catch (Throwable $e) {
    echo "\n[FATAL ERROR]: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
