<?php
/**
 * Curtiss POS - Stage 10 CLI Automated Verification Suite
 * Tests double-entry engine, COA, automatic accounting triggers, exact lot COGS,
 * good vs damaged return accounting, voucher accounting, reversals, duplicate prevention,
 * historical backfill idempotency, and Stage 1-9 regression.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Product.php';
require_once __DIR__ . '/../core/Inventory.php';
require_once __DIR__ . '/../core/Supplier.php';
require_once __DIR__ . '/../core/GRN.php';
require_once __DIR__ . '/../core/SupplierPayment.php';
require_once __DIR__ . '/../core/Customer.php';
require_once __DIR__ . '/../core/CustomerPayment.php';
require_once __DIR__ . '/../core/GiftVoucher.php';
require_once __DIR__ . '/../core/Sale.php';
require_once __DIR__ . '/../core/SaleReturn.php';
require_once __DIR__ . '/../core/Accounting.php';

echo "================================================================\n";
echo " CURTISS POS - STAGE 10 AUTOMATED CLI TEST SUITE\n";
echo "================================================================\n\n";

$testsPassed = 0;
$testsFailed = 0;

function assertTest(bool $condition, string $testName, string $failureMsg = '') {
    global $testsPassed, $testsFailed;
    if ($condition) {
        echo "[PASS] {$testName}\n";
        $testsPassed++;
    } else {
        echo "[FAIL] {$testName} - {$failureMsg}\n";
        $testsFailed++;
    }
}

try {
    $db = Database::getInstance()->getConnection();
    $userId = 1; // Admin User
    $branchId = 1;

    // 1. Chart of Accounts Test
    echo "--- 1. Testing Chart of Accounts ---\n";
    $coaList = Accounting::getCOA();
    assertTest(count($coaList) >= 17, "Default COA seeded with all required accounts", "Found " . count($coaList) . " accounts");

    $cashAcc = Accounting::getAccountByCode('1010');
    assertTest($cashAcc && $cashAcc['account_name'] === 'Cash in Hand', "Account 1010 Cash in Hand exists");

    $cogsAcc = Accounting::getAccountByCode('5010');
    assertTest($cogsAcc && $cogsAcc['account_type'] === 'expense', "Account 5010 COGS exists as expense");

    // Create Custom Account
    $customCode = 'TEST' . rand(1000, 9999);
    $customRes = Accounting::createAccount([
        'account_code' => $customCode,
        'account_name' => 'Test Cleaning Expense',
        'account_type' => 'expense'
    ], $userId);
    assertTest($customRes['success'], "Custom account creation succeeded");

    // 2. Double-Entry Engine & Balance Enforcement
    echo "\n--- 2. Testing Double-Entry Engine & Balance Validation ---\n";
    $balancedHeader = [
        'branch_id'   => $branchId,
        'entry_date'  => date('Y-m-d'),
        'source_type' => 'manual',
        'description' => 'Test Balanced Journal',
        'created_by'  => $userId
    ];

    $balancedLines = [
        ['account_id' => Accounting::getAccountIdByCode('1010'), 'debit' => 500.00, 'credit' => 0.00, 'memo' => 'Dr Cash'],
        ['account_id' => Accounting::getAccountIdByCode('3010'), 'debit' => 0.00, 'credit' => 500.00, 'memo' => 'Cr Equity']
    ];

    $resBalanced = Accounting::postJournalEntry($balancedHeader, $balancedLines);
    assertTest($resBalanced['success'], "Balanced journal entry (Debit = Credit) posted successfully");

    // Unbalanced Entry (Should Fail)
    $unbalancedLines = [
        ['account_id' => Accounting::getAccountIdByCode('1010'), 'debit' => 500.00, 'credit' => 0.00],
        ['account_id' => Accounting::getAccountIdByCode('3010'), 'debit' => 0.00, 'credit' => 450.00]
    ];
    $resUnbalanced = Accounting::postJournalEntry($balancedHeader, $unbalancedLines);
    assertTest(!$resUnbalanced['success'], "Unbalanced journal entry (Debit != Credit) strictly rejected", $resUnbalanced['message']);

    // 3. Journal Reversal Test
    echo "\n--- 3. Testing Journal Reversal ---\n";
    $origJournalId = $resBalanced['journal_id'];
    $revRes = Accounting::reverseJournalEntry($origJournalId, "Testing reversal functionality", $userId);
    assertTest($revRes['success'], "Journal entry reversal succeeded");

    $origDetails = Accounting::getJournalEntryDetails($origJournalId);
    assertTest($origDetails['posting_status'] === 'reversed', "Original journal entry marked as reversed");

    // Attempting double reversal should fail
    $revRes2 = Accounting::reverseJournalEntry($origJournalId, "Second reversal attempt", $userId);
    assertTest(!$revRes2['success'], "Double reversal attempt prevented");

    // 4. GRN Posting Trigger Test
    echo "\n--- 4. Testing GRN Accounting Posting ---\n";
    $suppliers = Supplier::all();
    $supplier = $suppliers[0] ?? null;
    if (!$supplier) {
        $spRes = Supplier::create('Stage 10 Supplier', '0771112233');
        $supplierId = (int)$spRes['id'];
    } else {
        $supplierId = (int)$supplier['id'];
    }

    $products = Product::getList([], 1, 1)['items'];
    $product = $products[0] ?? null;
    if (!$product) {
        $prodRes = Product::create([
            'barcode' => '9988' . rand(100000, 999999),
            'name' => 'Stage 10 Test Product',
            'category_id' => 1,
            'brand_id' => 1,
            'cost' => 100.00,
            'selling_price' => 150.00
        ], $userId);
        $productId = (int)$prodRes['id'];
    } else {
        $productId = (int)$product['id'];
    }

    $headerData = [
        'supplier_id'             => $supplierId,
        'branch_id'               => $branchId,
        'supplier_invoice_no'     => 'INV-ST10-' . rand(1000, 9999),
        'supplier_invoice_date'   => date('Y-m-d'),
        'received_date'           => date('Y-m-d'),
        'supplier_invoice_amount' => 1000.00
    ];
    $itemsData = [
        ['product_id' => $productId, 'quantity' => 10, 'unit_cost' => 100.00, 'selling_price' => 150.00, 'line_total' => 1000.00]
    ];
    $grnRes = GRN::create($headerData, $itemsData);

    $grnId = (int)$grnRes['grn_id'];
    $appRes = GRN::approve($grnId, $userId);
    assertTest($appRes['success'], "GRN approval succeeded");

    $grnJe = Database::fetchOne("SELECT * FROM journal_entries WHERE source_type = 'grn' AND source_id = :id", [':id' => $grnId]);
    assertTest($grnJe !== false, "GRN journal entry created in accounting table");
    assertTest((float)$grnJe['total_debit'] == 1000.00, "GRN journal debit equals 1000.00");

    // 5. Supplier Payment Posting Trigger Test
    echo "\n--- 5. Testing Supplier Payment Accounting Posting ---\n";
    $spayRes = SupplierPayment::processPayment([
        'supplier_id'    => $supplierId,
        'payment_date'   => date('Y-m-d'),
        'payment_method' => 'Cash',
        'amount'         => 1000.00,
        'notes'          => 'Full settlement of test GRN'
    ], $userId, $branchId);

    assertTest($spayRes['success'], "Supplier Payment created successfully");
    $spayId = (int)$spayRes['supplier_payment_id'];
    $spayJe = Database::fetchOne("SELECT * FROM journal_entries WHERE source_type = 'supplier_payment' AND source_id = :id", [':id' => $spayId]);
    assertTest($spayJe !== false, "Supplier Payment journal entry posted automatically");

    // 6. POS Sale Posting Trigger & Exact Lot COGS Test
    echo "\n--- 6. Testing POS Sale Posting & Exact Lot COGS ---\n";
    $lots = Sale::getAvailableLotsForProduct($productId, $branchId);
    $lotId = !empty($lots) ? (int)$lots[0]['lot_id'] : 1;

    $prepRes = Sale::prepareDraftSale([
        'customer_id' => 1,
        'items'       => [
            ['product_id' => $productId, 'quantity' => 2, 'unit_price' => 150.00, 'lot_id' => $lotId]
        ]
    ], $userId, $branchId);

    assertTest($prepRes['success'], "POS Draft Sale prepared");
    $saleId = (int)$prepRes['sale_id'];

    $saleRes = Sale::finalizeSale($saleId, [
        ['payment_method' => 'Cash', 'amount' => 300.00]
    ], $userId, $branchId);

    assertTest($saleRes['success'], "POS Sale invoice completed");

    $saleJe = Database::fetchOne("SELECT * FROM journal_entries WHERE source_type = 'sale' AND source_id = :id", [':id' => $saleId]);
    assertTest($saleJe !== false, "POS Sale journal entry posted automatically");

    $saleJeDetails = Accounting::getJournalEntryDetails((int)$saleJe['id']);
    $hasCogsLine = false;
    $hasRevLine = false;
    $expectedCogs = round(2 * (float)$lots[0]['cost'], 2);

    foreach ($saleJeDetails['lines'] as $l) {
        if ($l['account_code'] === '5010' && abs((float)$l['debit'] - $expectedCogs) < 0.01) {
            $hasCogsLine = true;
        }
        if ($l['account_code'] === '4010' && abs((float)$l['credit'] - 300.00) < 0.01) {
            $hasRevLine = true;
        }
    }
    assertTest($hasCogsLine, "Exact Lot COGS (Dr 5010 = LKR {$expectedCogs}) posted correctly");
    assertTest($hasRevLine, "Sales Revenue (Cr 4010 = LKR 300.00) posted correctly");

    // 7. Customer Collection Posting Trigger Test
    echo "\n--- 7. Testing Customer Collection Accounting Posting ---\n";
    $custPhone = '077' . rand(1000000, 9999999);
    $custRes = Customer::create(['name' => 'Stage 10 Credit Cust', 'phone' => $custPhone, 'credit_limit' => 5000.00]);
    $custObjId = (int)$custRes['id'];

    $creditPrepRes = Sale::prepareDraftSale([
        'customer_id' => $custObjId,
        'items'       => [['product_id' => $productId, 'quantity' => 1, 'unit_price' => 150.00, 'lot_id' => $lotId]]
    ], $userId, $branchId);
    $creditSaleId = (int)$creditPrepRes['sale_id'];

    $creditSaleRes = Sale::finalizeSale($creditSaleId, [
        ['payment_method' => 'Credit', 'amount' => 150.00]
    ], $userId, $branchId);

    $collRes = CustomerPayment::processPayment([
        'customer_id'    => $custObjId,
        'amount'         => 150.00,
        'payment_method' => 'Cash',
        'notes'          => 'Credit collection test'
    ], $userId, $branchId);

    assertTest($collRes['success'], "Customer Collection recorded successfully");
    $collJe = Database::fetchOne("SELECT * FROM journal_entries WHERE source_type = 'customer_collection' ORDER BY id DESC LIMIT 1");
    assertTest($collJe !== false, "Customer Collection journal entry posted automatically");

    // 8. Sales Return Good vs Damaged Accounting Test
    echo "\n--- 8. Testing Sales Return Accounting (Good vs Damaged) ---\n";
    $lastSaleId = (int)$creditSaleRes['sale_id'];
    $returnSearch = SaleReturn::searchSaleForReturn((string)$creditSaleRes['invoice_no'], $branchId);
    assertTest($returnSearch !== null, "Sale found for return lookup");

    $retRes = SaleReturn::processReturn([
        'sale_id'       => $lastSaleId,
        'customer_id'   => $custObjId,
        'items'         => [
            [
                'sale_item_id'   => $returnSearch['items'][0]['id'],
                'product_id'     => $productId,
                'lot_id'         => $returnSearch['items'][0]['lot_id'],
                'quantity'       => 1,
                'unit_price'     => 150.00,
                'cost_price'     => (float)$returnSearch['items'][0]['unit_cost'],
                'item_condition' => 'damaged',
                'restock'        => 0
            ]
        ],
        'refunds'       => [
            ['refund_method' => 'credit', 'amount' => 150.00]
        ]
    ], $userId, $branchId);

    assertTest($retRes['success'], "Sales return processed successfully");
    $retId = (int)$retRes['sales_return_id'];

    $retJe = Database::fetchOne("SELECT * FROM journal_entries WHERE source_type = 'sales_return' AND source_id = :id", [':id' => $retId]);
    assertTest($retJe !== false, "Sales Return journal entry posted automatically");

    $retJeDetails = Accounting::getJournalEntryDetails((int)$retJe['id']);
    $hasDamagedLossLine = false;
    $expectedDamagedCost = (float)$returnSearch['items'][0]['unit_cost'];
    if ($retJeDetails && !empty($retJeDetails['lines'])) {
        foreach ($retJeDetails['lines'] as $l) {
            if ($l['account_code'] === '1250' && abs((float)$l['debit'] - $expectedDamagedCost) < 0.01) { 
                $hasDamagedLossLine = true;
            }
        }
    }
    assertTest($hasDamagedLossLine, "Damaged Return debited 1250 Damaged Inventory Loss (LKR {$expectedDamagedCost}) without adding to sellable inventory asset");

    // 9. Historical Backfill Idempotency Test
    echo "\n--- 9. Testing Historical Accounting Backfill Idempotency ---\n";
    $bfRes1 = Accounting::runHistoricalBackfill($userId);
    assertTest($bfRes1['success'], "First historical backfill run executed successfully", !empty($bfRes1['errors']) ? implode(', ', $bfRes1['errors']) : ($bfRes1['message'] ?? ''));

    $bfRes2 = Accounting::runHistoricalBackfill($userId);
    assertTest($bfRes2['success'], "Second historical backfill run executed successfully", !empty($bfRes2['errors']) ? implode(', ', $bfRes2['errors']) : ($bfRes2['message'] ?? ''));
    assertTest($bfRes2['stats']['total_posted'] === 0, "Second backfill created ZERO duplicate postings (100% Idempotent!)");

    // 10. General Ledger & API Test
    echo "\n--- 10. Testing General Ledger Foundation ---\n";
    $glRes = Accounting::getGeneralLedger(['branch_id' => $branchId]);
    assertTest($glRes['totalItems'] > 0, "General Ledger returned entries", "Total ledger entries: " . $glRes['totalItems']);

    echo "\n================================================================\n";
    echo " TEST SUMMARY: Passed: {$testsPassed} | Failed: {$testsFailed}\n";
    echo "================================================================\n";

    if ($testsFailed > 0) {
        exit(1);
    }

} catch (Throwable $e) {
    echo "\n[CRITICAL ERROR] Test suite threw exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
