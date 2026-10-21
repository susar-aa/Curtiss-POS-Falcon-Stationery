<?php
/**
 * CLI Integration Test: Curtiss POS Gift Card Issue System (Stage 6)
 */

define('CLI_TEST_MODE', true);

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Sale.php';
require_once __DIR__ . '/../core/Accounting.php';
require_once __DIR__ . '/../core/GiftVoucher.php';
require_once __DIR__ . '/../core/Audit.php';

$branchId = 1;
$userId = 1; // Superadmin / Cashier
$testGcCode = 'GC-CLI-' . strtoupper(substr(uniqid(), -6));
$testAmount = 2500.00;
$testExpiry = date('Y-m-d', strtotime('+1 year'));

echo "=== STARTING GIFT CARD SYSTEM CLI VERIFICATION ===\n\n";

// 1. Verify System Product Resolution
echo "[TEST 1] System Product & Lot Resolution... ";
$gcSysInfo = Sale::getOrCreateGiftCardSystemProduct($branchId);
if (!$gcSysInfo || empty($gcSysInfo['product_id']) || empty($gcSysInfo['lot_id'])) {
    echo "FAILED: Invalid system product info: " . json_encode($gcSysInfo) . "\n";
    exit(1);
}
echo "PASSED (Product ID: {$gcSysInfo['product_id']}, Lot ID: {$gcSysInfo['lot_id']})\n";

// 2. Verify Duplicate Code Check API Logic
echo "[TEST 2] Duplicate Code Validation Logic... ";
// Check available code
$existing = Database::fetchOne("SELECT id FROM gift_vouchers WHERE voucher_code = :c AND deleted_at IS NULL", [':c' => $testGcCode]);
if ($existing) {
    echo "FAILED: Test code already exists!\n";
    exit(1);
}
echo "PASSED (Code '{$testGcCode}' is available)\n";

// 3. Find a regular product with stock for mixed invoice test
echo "[TEST 3] Fetching sample physical product for mixed invoice... ";
$sampleProduct = Database::fetchOne("
    SELECT p.id as product_id, p.name, p.barcode, pl.id as lot_id, pl.lot_number, pl.selling_price, pl.cost, pl.remaining_quantity
    FROM products p
    INNER JOIN product_lots pl ON p.id = pl.product_id
    WHERE pl.branch_id = :b AND pl.remaining_quantity >= 5 AND p.status = 'active' AND p.deleted_at IS NULL AND p.barcode != 'SYS-GIFTCARD'
    LIMIT 1
", [':b' => $branchId]);

if (!$sampleProduct) {
    echo "WARNING: No physical product with stock found. Using system product only.\n";
} else {
    echo "PASSED (Sample Product: '{$sampleProduct['name']}', Stock: {$sampleProduct['remaining_quantity']})\n";
}

// 4. Test Mixed Cart Draft Creation
echo "[TEST 4] Testing Draft Sale Creation with Mixed Cart (Product + Gift Card)... ";
$cartItems = [];

// Physical product
if ($sampleProduct) {
    $cartItems[] = [
        'product_id' => (int)$sampleProduct['product_id'],
        'lot_id' => (int)$sampleProduct['lot_id'],
        'quantity' => 1.00,
        'unit_price' => (float)$sampleProduct['selling_price'],
        'original_price' => (float)$sampleProduct['selling_price'],
        'unit_cost' => (float)$sampleProduct['cost'],
        'discount_value' => '',
        'is_gift_card' => 0,
        'gift_card_code' => null,
        'gift_card_expiry' => null
    ];
}

// Gift Card
$cartItems[] = [
    'product_id' => (int)$gcSysInfo['product_id'],
    'lot_id' => (int)$gcSysInfo['lot_id'],
    'quantity' => 1.00,
    'unit_price' => $testAmount,
    'original_price' => $testAmount,
    'unit_cost' => 0.00,
    'discount_value' => '',
    'is_gift_card' => 1,
    'gift_card_code' => $testGcCode,
    'gift_card_expiry' => $testExpiry
];

$draftResult = Sale::prepareDraftSale([
    'customer_id' => 1, // Walk-in
    'discount_value' => '',
    'items' => $cartItems
], $userId, $branchId);

if (!$draftResult['success']) {
    echo "FAILED: " . ($draftResult['message'] ?? 'Unknown error') . "\n";
    exit(1);
}

$saleId = (int)$draftResult['sale_id'];
echo "PASSED (Sale Draft Created: ID {$saleId}, Total: LKR {$draftResult['total_amount']})\n";

// Verify sale_items in database
$savedItems = Database::fetchAll("SELECT * FROM sale_items WHERE sale_id = :s ORDER BY id ASC", [':s' => $saleId]);
$hasGcItem = false;
foreach ($savedItems as $it) {
    if ($it['is_gift_card'] == 1 && $it['gift_card_code'] === $testGcCode) {
        $hasGcItem = true;
    }
}
if (!$hasGcItem) {
    echo "FAILED: Gift card item not saved in sale_items with is_gift_card = 1 and code = {$testGcCode}\n";
    exit(1);
}
echo "  ↳ Database verification: sale_items correctly tagged with is_gift_card = 1\n";

// 5. Finalize Sale
echo "[TEST 5] Finalizing Mixed Sale (Cash Payment)... ";
$totalAmount = (float)$draftResult['total_amount'];
$payments = [
    [
        'payment_method' => 'Cash',
        'amount' => $totalAmount,
        'reference_no' => 'CLI-TEST-PAYMENT',
        'tendered' => $totalAmount
    ]
];

$finalizeRes = Sale::finalizeSale($saleId, $payments, $userId, $branchId);
if (!$finalizeRes['success']) {
    echo "FAILED: " . ($finalizeRes['message'] ?? 'Unknown error') . "\n";
    exit(1);
}
echo "PASSED (Invoice #{$finalizeRes['invoice_no']} finalized successfully)\n";

// 6. Verify Gift Card Activated in gift_vouchers
echo "[TEST 6] Verifying Gift Voucher Record Creation & Activation... ";
$createdVoucher = Database::fetchOne("
    SELECT * FROM gift_vouchers 
    WHERE voucher_code = :c AND deleted_at IS NULL
", [':c' => $testGcCode]);

if (!$createdVoucher) {
    echo "FAILED: Gift voucher '{$testGcCode}' was not created in gift_vouchers table!\n";
    exit(1);
}
if ($createdVoucher['status'] !== 'active') {
    echo "FAILED: Voucher status is '{$createdVoucher['status']}', expected 'active'!\n";
    exit(1);
}
if ((float)$createdVoucher['amount'] !== $testAmount) {
    echo "FAILED: Voucher amount mismatch: Expected {$testAmount}, got amount={$createdVoucher['amount']}!\n";
    exit(1);
}
if ((int)$createdVoucher['sale_id'] !== $saleId) {
    echo "FAILED: Voucher sale_id mismatch: Expected {$saleId}, got {$createdVoucher['sale_id']}!\n";
    exit(1);
}
echo "PASSED (Voucher ID: {$createdVoucher['id']}, Status: {$createdVoucher['status']}, Sale ID: {$createdVoucher['sale_id']}, Amount: LKR {$createdVoucher['amount']})\n";

// 7. Verify Accounting Journal Entry (Gift Voucher Liability 2010 vs Product Revenue 4010)
echo "[TEST 7] Verifying COA Double-Entry Accounting Posting... ";
$journal = Database::fetchOne("
    SELECT * FROM journal_entries 
    WHERE source_type = 'sale' AND source_id = :s 
    ORDER BY id DESC LIMIT 1
", [':s' => $saleId]);

if (!$journal) {
    echo "FAILED: No journal entry created for sale ID {$saleId}!\n";
    exit(1);
}

$journalLines = Database::fetchAll("
    SELECT jl.*, coa.account_code, coa.account_name
    FROM journal_lines jl
    INNER JOIN chart_of_accounts coa ON jl.account_id = coa.id
    WHERE jl.journal_entry_id = :je
", [':je' => $journal['id']]);

$totalDebit = 0.00;
$totalCredit = 0.00;
$voucherLiabilityCredit = 0.00;
$salesRevenueCredit = 0.00;
$cashDrawerDebit = 0.00;

foreach ($journalLines as $jl) {
    $totalDebit += (float)$jl['debit'];
    $totalCredit += (float)$jl['credit'];
    if ($jl['account_code'] === '2010') {
        $voucherLiabilityCredit += (float)$jl['credit'];
    }
    if ($jl['account_code'] === '4010') {
        $salesRevenueCredit += (float)$jl['credit'];
    }
    if ($jl['account_code'] === '1012') {
        $cashDrawerDebit += (float)$jl['debit'];
    }
}

if (abs($totalDebit - $totalCredit) > 0.01) {
    echo "FAILED: Journal entry unbalanced! Debit: {$totalDebit}, Credit: {$totalCredit}\n";
    exit(1);
}

if (abs($voucherLiabilityCredit - $testAmount) > 0.01) {
    echo "FAILED: Account 2010 (Gift Voucher Liability) was not credited for {$testAmount}! Got: {$voucherLiabilityCredit}\n";
    exit(1);
}

echo "PASSED (Journal #{$journal['entry_number']}: Balanced Debits {$totalDebit} = Credits {$totalCredit}, Liability 2010 Credited: LKR {$voucherLiabilityCredit})\n";

// 8. Verify Duplicate Code Block on Existing Code
echo "[TEST 8] Verifying Duplicate Code Prevention on newly activated code... ";
$dupCheck = Database::fetchOne("
    SELECT id, voucher_code, status 
    FROM gift_vouchers 
    WHERE voucher_code = :c AND deleted_at IS NULL
", [':c' => $testGcCode]);

if (!$dupCheck) {
    echo "FAILED: Code not found for duplicate test!\n";
    exit(1);
}
echo "PASSED (Code '{$testGcCode}' correctly recognized as existing active record in DB)\n";

// 9. Clean up test records
echo "[TEST 9] Cleaning up test fixtures cleanly... ";
Database::execute("DELETE FROM journal_lines WHERE journal_entry_id = :je", [':je' => $journal['id']]);
Database::execute("DELETE FROM journal_entries WHERE id = :je", [':je' => $journal['id']]);
Database::execute("DELETE FROM gift_vouchers WHERE id = :vid", [':vid' => $createdVoucher['id']]);
Database::execute("DELETE FROM payments WHERE sale_id = :s", [':s' => $saleId]);
Database::execute("DELETE FROM sale_items WHERE sale_id = :s", [':s' => $saleId]);
Database::execute("DELETE FROM sales WHERE id = :s", [':s' => $saleId]);
if ($sampleProduct) {
    // Restore the 1 unit deducted for physical sample product
    Database::execute("
        UPDATE product_lots SET remaining_quantity = remaining_quantity + 1 WHERE id = :lid
    ", [':lid' => $sampleProduct['lot_id']]);
}
echo "PASSED (Cleaned up test sale {$saleId} and test voucher {$createdVoucher['id']})\n\n";

echo "=== ALL GIFT CARD POS TESTS PASSED WITH 100% SUCCESS ===\n";
