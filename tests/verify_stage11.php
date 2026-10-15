<?php
/**
 * Curtiss POS - Stage 11 Automated CLI Verification & Regression Test Suite
 * Tests Business Day opening/closing, Cash Drawer append-only movement history,
 * manual cash in/out, shortage/overage accounting, transaction blocking, duplicate prevention, and Stage 1-10 regression.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Logger.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Product.php';
require_once __DIR__ . '/../core/Sale.php';
require_once __DIR__ . '/../core/Customer.php';
require_once __DIR__ . '/../core/CustomerPayment.php';
require_once __DIR__ . '/../core/Supplier.php';
require_once __DIR__ . '/../core/PurchaseOrder.php';
require_once __DIR__ . '/../core/GRN.php';
require_once __DIR__ . '/../core/SupplierPayment.php';
require_once __DIR__ . '/../core/SaleReturn.php';
require_once __DIR__ . '/../core/Accounting.php';
require_once __DIR__ . '/../core/BusinessDay.php';

$testPassed = 0;
$testFailed = 0;

function assertTest(bool $condition, string $testName, string $failureDetails = '') {
    global $testPassed, $testFailed;
    if ($condition) {
        $testPassed++;
        echo "  [PASS] {$testName}\n";
    } else {
        $testFailed++;
        echo "  [FAIL] {$testName}\n";
        if ($failureDetails) {
            echo "         Detail: {$failureDetails}\n";
        }
    }
}

echo "===============================================================\n";
echo "       CURTISS POS - STAGE 11 VERIFICATION TEST SUITE           \n";
echo "===============================================================\n\n";

try {
    $db = Database::getInstance()->getConnection();
    $branchId = 1;
    $userId = 1;

    // Clean active business day for branch 1 if any exists from previous runs
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
    $db->exec("DELETE FROM cash_drawer_movements WHERE branch_id = 1");
    $db->exec("DELETE FROM business_days WHERE branch_id = 1");
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");

    // -------------------------------------------------------------
    // TEST 1: Business Day Opening & Single Open Day Constraint
    // -------------------------------------------------------------
    echo "[GROUP 1] Business Day Opening & Constraints\n";

    $startRes = BusinessDay::startDay(1, 20000.00, $userId, 'Stage 11 Test Opening');
    assertTest($startRes['success'] === true, 'Start business day with default LKR 20,000 opening cash', $startRes['message'] ?? '');

    $bDayId = $startRes['business_day_id'] ?? 0;
    assertTest($bDayId > 0, 'Business day ID generated', "ID: {$bDayId}");

    $duplicateStart = BusinessDay::startDay(1, 15000.00, $userId, 'Duplicate Start Attempt');
    assertTest($duplicateStart['success'] === false, 'Prevent duplicate open business day for same branch', $duplicateStart['message'] ?? '');

    $activeDay = BusinessDay::getActiveDay(1);
    assertTest($activeDay !== null && (int)$activeDay['id'] === $bDayId, 'getActiveDay returns current open business day header');

    // Verify initial drawer movement for opening cash
    $openingMovement = Database::fetchOne("
        SELECT * FROM cash_drawer_movements WHERE business_day_id = :bday AND movement_type = 'opening_cash'
    ", [':bday' => $bDayId]);
    assertTest($openingMovement && (float)$openingMovement['amount'] == 20000.00, 'Initial opening cash drawer movement recorded (LKR 20,000.00)');

    echo "\n";

    // -------------------------------------------------------------
    // TEST 2: POS Cash Sale Drawer Movement Integration
    // -------------------------------------------------------------
    echo "[GROUP 2] POS Cash Sale Drawer Integration\n";

    // Create a product lot for sale test
    $prodRes = Product::create([
        'barcode' => 'S11-' . time() . rand(100, 999),
        'name' => 'Stage 11 Test Item',
        'category_id' => 1,
        'brand_id' => 1,
        'supplier_id' => 1,
        'unit' => 'pcs',
        'cost' => 500.00,
        'selling_price' => 1000.00,
        'opening_stock' => 0.00
    ], $userId);
    $productId = (int)($prodRes['id'] ?? 0);

    // Add lot stock
    $lotStmt = $db->prepare("
        INSERT INTO product_lots (lot_number, product_id, branch_id, cost, selling_price, initial_quantity, remaining_quantity, status)
        VALUES ('LOT-S11-001', :pid, 1, 500.00, 1000.00, 100, 100, 'active')
    ");
    $lotStmt->execute([':pid' => $productId]);
    $lotId = (int)$db->lastInsertId();

    $db->prepare("
        INSERT INTO branch_inventory (branch_id, product_id, quantity, low_stock_threshold)
        VALUES (1, :pid, 100, 10)
        ON DUPLICATE KEY UPDATE quantity = 100
    ")->execute([':pid' => $productId]);

    // Prepare & finalize cash sale
    $draftRes = Sale::prepareDraftSale([
        'customer_id' => 1,
        'items' => [
            [
                'product_id' => $productId,
                'lot_id' => $lotId,
                'quantity' => 2,
                'unit_price' => 1000.00,
                'original_price' => 1000.00
            ]
        ]
    ], $userId, 1);
    $saleId = (int)$draftRes['sale_id'];

    $finRes = Sale::finalizeSale($saleId, [
        ['payment_method' => 'Cash', 'amount' => 2500.00] // Payable 2000, 500 change
    ], $userId, 1);

    assertTest($finRes['success'] === true, 'Finalize POS cash sale (LKR 2,000 payable, LKR 2,500 cash, LKR 500 change)', $finRes['message'] ?? '');

    // Check cash drawer movement for POS sale (net cash = LKR 2,000.00)
    $saleMovement = Database::fetchOne("
        SELECT * FROM cash_drawer_movements WHERE business_day_id = :bday AND movement_type = 'pos_sale' AND source_id = :sid
    ", [':bday' => $bDayId, ':sid' => $saleId]);

    assertTest($saleMovement && (float)$saleMovement['amount'] == 2000.00 && $saleMovement['direction'] === 'in', 'POS sale net cash (+2,000.00) recorded in cash drawer movements');

    echo "\n";

    // -------------------------------------------------------------
    // TEST 3: Customer Cash Collection Drawer Movement Integration
    // -------------------------------------------------------------
    echo "[GROUP 3] Customer Cash Collection Drawer Integration\n";

    // Create credit customer & credit sale
    $custRes = Customer::create([
        'name' => 'Stage 11 Credit Customer',
        'phone' => '0779998877',
        'credit_limit' => 50000.00
    ], $userId);
    $customerId = (int)$custRes['id'];

    $draftRes2 = Sale::prepareDraftSale([
        'customer_id' => $customerId,
        'items' => [
            [
                'product_id' => $productId,
                'lot_id' => $lotId,
                'quantity' => 5,
                'unit_price' => 1000.00,
                'original_price' => 1000.00
            ]
        ]
    ], $userId, 1);
    $creditSaleId = (int)$draftRes2['sale_id'];

    Sale::finalizeSale($creditSaleId, [
        ['payment_method' => 'Credit', 'amount' => 5000.00]
    ], $userId, 1);

    // Process customer cash collection (LKR 3,000.00)
    $collRes = CustomerPayment::processPayment([
        'customer_id' => $customerId,
        'payment_method' => 'Cash',
        'amount' => 3000.00,
        'notes' => 'S11 Collection Test'
    ], $userId, 1);

    assertTest($collRes['success'] === true, 'Process customer cash collection of LKR 3,000.00', $collRes['message'] ?? '');

    $collMovement = Database::fetchOne("
        SELECT * FROM cash_drawer_movements WHERE business_day_id = :bday AND movement_type = 'customer_collection'
    ", [':bday' => $bDayId]);

    assertTest($collMovement && (float)$collMovement['amount'] == 3000.00 && $collMovement['direction'] === 'in', 'Customer cash collection (+3,000.00) recorded in cash drawer movements');

    echo "\n";

    // -------------------------------------------------------------
    // TEST 4: Cash Refund & Supplier Cash Payment Integration
    // -------------------------------------------------------------
    echo "[GROUP 4] Cash Refund & Supplier Cash Payment Integration\n";

    // Process Sales Return with Cash Refund (LKR 1,000.00)
    $retRes = SaleReturn::processReturn([
        'sale_id' => $saleId,
        'customer_id' => 1,
        'return_type' => 'return_only',
        'items' => [
            [
                'sale_item_id' => Database::fetchOne("SELECT id FROM sale_items WHERE sale_id = :sid LIMIT 1", [':sid' => $saleId])['id'],
                'product_id' => $productId,
                'lot_id' => $lotId,
                'quantity' => 1,
                'unit_price' => 1000.00,
                'cost_price' => 500.00,
                'item_condition' => 'good',
                'reason' => 'S11 Refund Test'
            ]
        ],
        'refunds' => [
            ['refund_method' => 'cash', 'amount' => 1000.00]
        ]
    ], $userId, 1);

    assertTest($retRes['success'] === true, 'Process sales return with cash refund (LKR 1,000.00)', $retRes['message'] ?? '');

    $refundMovement = Database::fetchOne("
        SELECT * FROM cash_drawer_movements WHERE business_day_id = :bday AND movement_type = 'cash_refund'
    ", [':bday' => $bDayId]);

    assertTest($refundMovement && (float)$refundMovement['amount'] == 1000.00 && $refundMovement['direction'] === 'out', 'Cash refund (-1,000.00) recorded in cash drawer movements');

    // Create Supplier, PO, GRN and process Supplier Cash Payment (LKR 2,000.00)
    $supRes = Supplier::create('Stage 11 Test Supplier', '0112233445');
    $supplierId = (int)$supRes['id'];

    $poRes = PurchaseOrder::create([
        'supplier_id' => $supplierId,
        'branch_id'   => 1,
        'user_id'     => $userId
    ], [
        ['product_id' => $productId, 'quantity' => 10, 'unit_cost' => 500.00, 'selling_price' => 1000.00]
    ]);
    $poId = (int)$poRes['id'];

    $grnRes = GRN::create([
        'po_id'                 => $poId,
        'supplier_id'           => $supplierId,
        'supplier_invoice_no'   => 'INV-S11-' . time(),
        'supplier_invoice_date' => date('Y-m-d'),
        'received_date'         => date('Y-m-d'),
        'branch_id'             => 1,
        'user_id'               => $userId
    ], [
        ['product_id' => $productId, 'quantity' => 10, 'unit_cost' => 500.00, 'selling_price' => 1000.00]
    ]);
    $grnId = (int)($grnRes['grn_id'] ?? 0);
    GRN::approve($grnId, $userId);

    $spayRes = SupplierPayment::processPayment([
        'supplier_id' => $supplierId,
        'payment_method' => 'Cash',
        'amount' => 2000.00,
        'notes' => 'S11 Supplier Payment Test'
    ], $userId, 1);

    assertTest($spayRes['success'] === true, 'Process supplier cash payment of LKR 2,000.00', $spayRes['message'] ?? '');

    $spayMovement = Database::fetchOne("
        SELECT * FROM cash_drawer_movements WHERE business_day_id = :bday AND movement_type = 'supplier_payment'
    ", [':bday' => $bDayId]);

    assertTest($spayMovement && (float)$spayMovement['amount'] == 2000.00 && $spayMovement['direction'] === 'out', 'Supplier cash payment (-2,000.00) recorded in cash drawer movements');

    echo "\n";

    // -------------------------------------------------------------
    // TEST 5: Manual Cash In / Cash Out Movements
    // -------------------------------------------------------------
    echo "[GROUP 5] Manual Cash In / Cash Out Movements\n";

    $manualIn = BusinessDay::manualCashMovement(1, 'cash_in', 500.00, 'Morning Bank Deposit Float', 'Ref #IN-001', $userId);
    assertTest($manualIn['success'] === true, 'Manual Cash In of LKR 500.00 recorded', $manualIn['message'] ?? '');

    $manualOut = BusinessDay::manualCashMovement(1, 'cash_out', 300.00, 'Emergency Cleaning Supplies', 'Ref #OUT-001', $userId);
    assertTest($manualOut['success'] === true, 'Manual Cash Out of LKR 300.00 recorded', $manualOut['message'] ?? '');

    echo "\n";

    // -------------------------------------------------------------
    // TEST 6: Cash Drawer Reconciliation Summary Calculation
    // -------------------------------------------------------------
    echo "[GROUP 6] Cash Drawer Reconciliation Breakdown\n";

    $summaryRes = BusinessDay::getDrawerSummary($bDayId);
    assertTest($summaryRes['success'] === true, 'Fetch drawer summary');

    $s = $summaryRes['summary'];
    // Expected calculation:
    // Opening Cash: 20,000.00
    // POS Cash Sales: 2,000.00
    // Customer Collections: 3,000.00
    // Manual Cash In: 500.00
    // Total Cash In: 25,500.00
    // Cash Refunds: 1,000.00
    // Supplier Cash Payments: 2,000.00
    // Manual Cash Out: 300.00
    // Total Cash Out: 3,300.00
    // Expected Cash: 25,500 - 3,300 = 22,200.00

    assertTest($s['opening_cash'] == 20000.00, 'Opening Cash = LKR 20,000.00', "Got: {$s['opening_cash']}");
    assertTest($s['pos_cash_sales'] == 2000.00, 'POS Cash Sales = LKR 2,000.00', "Got: {$s['pos_cash_sales']}");
    assertTest($s['customer_cash_collections'] == 3000.00, 'Customer Collections = LKR 3,000.00', "Got: {$s['customer_cash_collections']}");
    assertTest($s['manual_cash_in'] == 500.00, 'Manual Cash In = LKR 500.00', "Got: {$s['manual_cash_in']}");
    assertTest($s['total_cash_in'] == 25500.00, 'Total Cash In = LKR 25,500.00', "Got: {$s['total_cash_in']}");
    assertTest($s['cash_refunds'] == 1000.00, 'Cash Refunds = LKR 1,000.00', "Got: {$s['cash_refunds']}");
    assertTest($s['supplier_cash_payments'] == 2000.00, 'Supplier Payments = LKR 2,000.00', "Got: {$s['supplier_cash_payments']}");
    assertTest($s['manual_cash_out'] == 300.00, 'Manual Cash Out = LKR 300.00', "Got: {$s['manual_cash_out']}");
    assertTest($s['total_cash_out'] == 3300.00, 'Total Cash Out = LKR 3,300.00', "Got: {$s['total_cash_out']}");
    assertTest($s['expected_cash'] == 22200.00, 'Expected Cash = LKR 22,200.00', "Got: {$s['expected_cash']}");

    echo "\n";

    // -------------------------------------------------------------
    // TEST 7: Day Closing, Reconciliation, Shortage/Overage Accounting
    // -------------------------------------------------------------
    echo "[GROUP 7] Day Closing, Reconciliation & Shortage/Overage Accounting\n";

    // Suppose Actual Counted Cash = 22,000.00 (Shortage of LKR 200.00)
    // Retained Cash = 20,000.00 -> Cash to Deposit = 2,000.00
    $closeRes = BusinessDay::closeDay($bDayId, 22000.00, 20000.00, $userId, 'Closing with LKR 200 shortage');
    assertTest($closeRes['success'] === true, 'Close Business Day with LKR 22,000 counted cash', $closeRes['message'] ?? '');
    assertTest($closeRes['shortage_amount'] == 200.00, 'Shortage calculated as LKR 200.00', "Shortage: {$closeRes['shortage_amount']}");
    assertTest($closeRes['cash_to_deposit'] == 2000.00, 'Cash to Deposit calculated as LKR 2,000.00', "Deposit: {$closeRes['cash_to_deposit']}");

    // Verify Business Day status is closed
    $closedRow = Database::fetchOne("SELECT * FROM business_days WHERE id = :id", [':id' => $bDayId]);
    assertTest($closedRow['status'] === 'closed', 'Business Day status updated to closed');

    // Prevent double closing
    $doubleClose = BusinessDay::closeDay($bDayId, 22000.00, 20000.00, $userId);
    assertTest($doubleClose['success'] === false, 'Prevent double closing of already closed day', $doubleClose['message'] ?? '');

    // Verify Shortage Journal Entry created (Dr 5040 Cash Shortage / Overage, Cr 1010 Cash in Hand)
    $shortageJe = Database::fetchOne("
        SELECT * FROM journal_entries 
        WHERE description LIKE '%Day Closing Cash Shortage%' AND source_id = :sid
    ", [':sid' => $bDayId]);
    assertTest($shortageJe !== false, 'Shortage journal entry posted to Accounting GL');

    echo "\n";

    // -------------------------------------------------------------
    // TEST 8: Transaction Blocking when Business Day is Closed
    // -------------------------------------------------------------
    echo "[GROUP 8] Transaction Blocking when No Active Day Open\n";

    // Active day for branch 1 is now closed
    $noActive = BusinessDay::getActiveDay(1);
    assertTest($noActive === null, 'No active business day exists after closing');

    // Attempt POS Cash Sale
    $blockedDraft = Sale::prepareDraftSale([
        'customer_id' => 1,
        'items' => [['product_id' => $productId, 'lot_id' => $lotId, 'quantity' => 1, 'unit_price' => 1000.00]]
    ], $userId, 1);
    $blockedSaleId = (int)$blockedDraft['sale_id'];

    $blockedFin = Sale::finalizeSale($blockedSaleId, [
        ['payment_method' => 'Cash', 'amount' => 1000.00]
    ], $userId, 1);

    assertTest($blockedFin['success'] === false, 'Block POS Cash sale when business day is closed', $blockedFin['message'] ?? '');

    // Attempt Customer Cash Collection
    $blockedColl = CustomerPayment::processPayment([
        'customer_id' => $customerId,
        'payment_method' => 'Cash',
        'amount' => 500.00
    ], $userId, 1);

    assertTest($blockedColl['success'] === false, 'Block Customer Cash collection when business day is closed', $blockedColl['message'] ?? '');

    // Attempt Supplier Cash Payment
    $blockedSpay = SupplierPayment::processPayment([
        'supplier_id' => $supplierId,
        'payment_method' => 'Cash',
        'amount' => 500.00
    ], $userId, 1);

    assertTest($blockedSpay['success'] === false, 'Block Supplier Cash payment when business day is closed', $blockedSpay['message'] ?? '');

    echo "\n";

    // -------------------------------------------------------------
    // TEST 9: Duplicate Drawer Movement Prevention & Branch Isolation
    // -------------------------------------------------------------
    echo "[GROUP 9] Duplicate Movement Prevention & Branch Isolation\n";

    // Start Day for Branch 2
    $db->exec("DELETE FROM cash_drawer_movements WHERE branch_id = 2");
    $db->exec("DELETE FROM business_days WHERE branch_id = 2");

    $b2Start = BusinessDay::startDay(2, 20000.00, $userId, 'Branch 2 Opening');
    assertTest($b2Start['success'] === true, 'Branch 2 starts its own independent business day', $b2Start['message'] ?? '');

    $b2Active = BusinessDay::getActiveDay(2);
    assertTest($b2Active !== null && (int)$b2Active['branch_id'] === 2, 'Branch 2 active day isolated from Branch 1');

    // Test duplicate drawer movement prevention
    $dupCheck = BusinessDay::recordDrawerMovement([
        'business_day_id' => (int)$b2Active['id'],
        'branch_id' => 2,
        'movement_type' => 'opening_cash',
        'source_type' => 'business_days',
        'source_id' => (int)$b2Active['id'],
        'amount' => 20000.00,
        'direction' => 'in',
        'reason' => 'Duplicate Opening Check',
        'user_id' => $userId
    ]);
    assertTest(isset($dupCheck['already_recorded']) && $dupCheck['already_recorded'] === true, 'Duplicate cash drawer movement detected & skipped gracefully');

    echo "\n";

    // -------------------------------------------------------------
    // TEST 10: Double-Entry Accounting Trial Balance Health
    // -------------------------------------------------------------
    echo "[GROUP 10] Accounting Engine Health & Trial Balance\n";

    $trialRow = Database::fetchOne("
        SELECT SUM(debit) AS total_debit, SUM(credit) AS total_credit
        FROM journal_lines
    ");
    $totDr = round((float)($trialRow['total_debit'] ?? 0), 2);
    $totCr = round((float)($trialRow['total_credit'] ?? 0), 2);

    assertTest(abs($totDr - $totCr) < 0.001, "General Ledger Double-Entry Trial Balance is 100% Balanced (Total Dr: LKR {$totDr}, Total Cr: LKR {$totCr})");

    echo "\n";

} catch (Throwable $e) {
    echo "\n[UNHANDLED EXCEPTION] " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    $testFailed++;
}

echo "===============================================================\n";
echo "SUMMARY: " . ($testPassed + $testFailed) . " Tests Executed\n";
echo "PASSED: {$testPassed}\n";
echo "FAILED: {$testFailed}\n";
echo "===============================================================\n";

if ($testFailed === 0) {
    echo "🎉 STAGE 11 BUSINESS DAY & CASH DRAWER ENGINE VERIFIED 100% SUCCESSFUL!\n";
    exit(0);
} else {
    echo "❌ STAGE 11 VERIFICATION FAILED WITH {$testFailed} ERRORS.\n";
    exit(1);
}
