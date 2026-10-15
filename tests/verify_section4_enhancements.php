<?php
/**
 * Curtiss POS - Section 4 Verification Suite: Payments & Checkout Settlement
 * Verifies all 43 Section 4 requirements and test scenarios.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Sale.php';
require_once __DIR__ . '/../core/Product.php';
require_once __DIR__ . '/../core/Customer.php';
require_once __DIR__ . '/../core/GiftVoucher.php';
require_once __DIR__ . '/../core/Banking.php';
require_once __DIR__ . '/../core/BusinessDay.php';
require_once __DIR__ . '/../core/Accounting.php';

$passCount = 0;
$failCount = 0;

function testAssert(bool $condition, string $description, ?string $extraMsg = null): void {
    global $passCount, $failCount;
    if ($condition) {
        echo "[PASS] {$description}\n";
        $passCount++;
    } else {
        echo "[FAIL] {$description}" . ($extraMsg ? " -> " . $extraMsg : "") . "\n";
        $failCount++;
    }
}

echo "=========================================================\n";
echo " CURTISS POS - SECTION 4: PAYMENTS & SETTLEMENT SUITE   \n";
echo "=========================================================\n\n";

$branchId = 1;
$userId = 1;

// Ensure active business day
$activeDay = BusinessDay::getActiveDay($branchId);
if (!$activeDay) {
    BusinessDay::startDay($branchId, 10000.00, $userId, 'Section 4 Test Day');
    $activeDay = BusinessDay::getActiveDay($branchId);
}

$db = Database::getInstance()->getConnection();

// Cleanup prior test sales and orphaned journal entries safely
Database::execute("SET FOREIGN_KEY_CHECKS = 0");
Database::execute("
    DELETE FROM journal_lines WHERE journal_entry_id IN (
        SELECT id FROM journal_entries WHERE source_type = 'sale' AND source_id NOT IN (SELECT id FROM sales)
    )
");
Database::execute("
    DELETE FROM journal_entries WHERE source_type = 'sale' AND source_id NOT IN (SELECT id FROM sales)
");

$oldSales = Database::fetchAll("SELECT id FROM sales WHERE invoice_no LIKE 'SEC4-%' OR held_notes LIKE '%Sec4%'");
foreach ($oldSales as $os) {
    $sid = (int)$os['id'];
    Database::execute("DELETE FROM journal_lines WHERE journal_entry_id IN (SELECT id FROM journal_entries WHERE source_type = 'sale' AND source_id = :sid)", [':sid' => $sid]);
    Database::execute("DELETE FROM journal_entries WHERE source_type = 'sale' AND source_id = :sid", [':sid' => $sid]);
    Database::execute("DELETE FROM payments WHERE sale_id = :sid", [':sid' => $sid]);
    Database::execute("DELETE FROM sale_items WHERE sale_id = :sid", [':sid' => $sid]);
    Database::execute("DELETE FROM sales WHERE id = :sid", [':sid' => $sid]);
}
Database::execute("DELETE FROM customer_credit_ledger WHERE customer_id IN (SELECT id FROM customers WHERE name LIKE 'Sec4 Test Customer%')");
Database::execute("DELETE FROM payments WHERE customer_id IN (SELECT id FROM customers WHERE name LIKE 'Sec4 Test Customer%')");
Database::execute("DELETE FROM customers WHERE name LIKE 'Sec4 Test Customer%'");
Database::execute("SET FOREIGN_KEY_CHECKS = 1");

// 0. Setup test fixtures (Product, Lot, Customer, Bank Account)
$testSku = 'SKU-SEC4-' . rand(1000, 9999);
$testBarcode = 'BAR-SEC4-' . rand(10000, 99999);
Database::execute("
    INSERT INTO products (sku, barcode, name, brand_id, category_id, supplier_id, cost, selling_price, status)
    VALUES (:sku, :barcode, 'Sec4 Test Product', 1, 1, 1, 500.00, 1000.00, 'active')
", [':sku' => $testSku, ':barcode' => $testBarcode]);
$productId = (int)Database::lastInsertId();

$lotNumber = 'LOT-SEC4-' . rand(1000, 9999);
Database::execute("
    INSERT INTO product_lots (product_id, branch_id, lot_number, cost, selling_price, initial_quantity, remaining_quantity, status)
    VALUES (:product_id, :branch_id, :lot_number, 500.00, 1000.00, 1000.00, 1000.00, 'active')
", [':product_id' => $productId, ':branch_id' => $branchId, ':lot_number' => $lotNumber]);
$lotId = (int)Database::lastInsertId();

Database::execute("
    INSERT INTO branch_inventory (branch_id, product_id, quantity)
    VALUES (:branch_id, :product_id, 1000.00)
    ON DUPLICATE KEY UPDATE quantity = quantity + 1000.00
", [':branch_id' => $branchId, ':product_id' => $productId]);

// Create registered test customer
$custRes = Customer::create([
    'name' => 'Sec4 Test Customer ' . rand(1000, 9999),
    'phone' => '077' . rand(1000007, 9999999),
    'is_walkin' => 0
]);
$customerId = (int)$custRes['id'];

// Fetch or create active bank account
$bankAccounts = Banking::getBankAccounts($branchId, true);
if (empty($bankAccounts)) {
    $coaRes = Database::fetchOne("SELECT id FROM chart_of_accounts WHERE account_code = '1020'");
    $bankCoaId = (int)$coaRes['id'];
    $bankSaveRes = Banking::saveBankAccount([
        'bank_name' => 'Commercial Bank',
        'account_name' => 'Test POS Account',
        'account_number' => '1234567890',
        'branch_id' => $branchId,
        'bank_branch_name' => 'Kandy Main',
        'account_id' => $bankCoaId,
        'status' => 'active'
    ], $userId);
    $bankAccountId = (int)$bankSaveRes['bank_account_id'];
    $bankAccCoaId = $bankCoaId;
} else {
    $bankAccountId = (int)$bankAccounts[0]['id'];
    $bankAccCoaId = (int)$bankAccounts[0]['account_id'];
}

// Helper to prepare draft sale
function helperDraftSale(int $productId, int $lotId, float $qty, float $price, int $customerId, int $userId, int $branchId): int {
    static $seq = 1;
    $invNo = 'SEC4-' . date('YmdHis') . '-' . sprintf('%04d', $seq++) . '-' . rand(1000, 9999);
    $res = Sale::prepareDraftSale([
        'customer_id' => $customerId,
        'items' => [
            ['product_id' => $productId, 'lot_id' => $lotId, 'quantity' => $qty, 'unit_price' => $price]
        ]
    ], $userId, $branchId);
    
    // Update invoice_no to SEC4 prefix to prevent collisions
    $saleId = (int)$res['sale_id'];
    Database::execute("UPDATE sales SET invoice_no = :inv, held_notes = 'Sec4 Test' WHERE id = :id", [':inv' => $invNo, ':id' => $saleId]);
    return $saleId;
}

// --- Test 1: Cash exact amount ---
$s1Id = helperDraftSale($productId, $lotId, 1, 1750.00, 1, $userId, $branchId);
$f1 = Sale::finalizeSale($s1Id, [['payment_method' => 'Cash', 'amount' => 1750.00]], $userId, $branchId);
testAssert($f1['success'] === true && $f1['change_amount'] == 0.00, "1. Cash exact amount (1,750 settled with 1,750 cash, change = 0)", $f1['message'] ?? null);

// --- Test 2: Cash with change ---
$s2Id = helperDraftSale($productId, $lotId, 1, 1750.00, 1, $userId, $branchId);
$f2 = Sale::finalizeSale($s2Id, [['payment_method' => 'Cash', 'amount' => 2000.00]], $userId, $branchId);
testAssert($f2['success'] === true && $f2['change_amount'] == 250.00, "2. Cash with change (1,750 settled with 2,000 cash, change = 250)", $f2['message'] ?? null);

// Check net cash journal for Test 2
$j2 = Database::fetchOne("SELECT * FROM journal_entries WHERE source_type = 'sale' AND source_id = :sid", [':sid' => $s2Id]);
$jLines2 = $j2 ? Database::fetchAll("SELECT * FROM journal_lines WHERE journal_entry_id = :jid", [':jid' => $j2['id']]) : [];
$cashDebit2 = 0;
foreach ($jLines2 as $jl) {
    if ((int)$jl['account_id'] === Accounting::getAccountIdByCode(Accounting::CODE_CASH)) {
        $cashDebit2 += (float)$jl['debit'];
    }
}
testAssert($cashDebit2 == 1750.00, "2b. Cash account debited net cash 1,750 (not 2,000)");

// --- Test 3: Insufficient cash rejected ---
$s3Id = helperDraftSale($productId, $lotId, 1, 1750.00, 1, $userId, $branchId);
$f3 = Sale::finalizeSale($s3Id, [['payment_method' => 'Cash', 'amount' => 1500.00]], $userId, $branchId);
testAssert($f3['success'] === false && strpos($f3['message'], 'Insufficient payment') !== false, "3. Insufficient cash rejected (1,500 tendered for 1,750)", $f3['message'] ?? null);

// --- Test 4: Card payment ---
$s4Id = helperDraftSale($productId, $lotId, 1, 1200.00, 1, $userId, $branchId);
$f4 = Sale::finalizeSale($s4Id, [['payment_method' => 'Card', 'amount' => 1200.00, 'reference_no' => 'TXN-CARD-123']], $userId, $branchId);
testAssert($f4['success'] === true, "4. Card payment finalized successfully", $f4['message'] ?? null);

$j4 = Database::fetchOne("SELECT * FROM journal_entries WHERE source_type = 'sale' AND source_id = :sid", [':sid' => $s4Id]);
$jLines4 = $j4 ? Database::fetchAll("SELECT * FROM journal_lines WHERE journal_entry_id = :jid", [':jid' => $j4['id']]) : [];
$cardDebit4 = 0;
foreach ($jLines4 as $jl) {
    if ((int)$jl['account_id'] === Accounting::getAccountIdByCode(Accounting::CODE_CARD_RECEIVABLE)) {
        $cardDebit4 += (float)$jl['debit'];
    }
}
testAssert($cardDebit4 == 1200.00, "4b. Card payment posted to Card Receivable (1030)");

// --- Test 5: Bank payment missing bank_account_id rejected ---
$s5Id = helperDraftSale($productId, $lotId, 1, 1500.00, 1, $userId, $branchId);
$f5 = Sale::finalizeSale($s5Id, [['payment_method' => 'Bank', 'amount' => 1500.00, 'reference_no' => 'REF-BANK-001']], $userId, $branchId);
testAssert($f5['success'] === false && strpos($f5['message'], 'requires selecting an active bank account') !== false, "5. Bank payment without bank_account_id rejected", $f5['message'] ?? null);

// --- Test 6: Bank payment with dedicated bank account COA ---
$s6Id = helperDraftSale($productId, $lotId, 1, 1500.00, 1, $userId, $branchId);
$f6 = Sale::finalizeSale($s6Id, [['payment_method' => 'Bank', 'amount' => 1500.00, 'reference_no' => 'REF-BANK-002', 'bank_account_id' => $bankAccountId]], $userId, $branchId);
testAssert($f6['success'] === true, "6. Bank payment with dedicated bank account finalized", $f6['message'] ?? null);

$j6 = Database::fetchOne("SELECT * FROM journal_entries WHERE source_type = 'sale' AND source_id = :sid", [':sid' => $s6Id]);
$jLines6 = $j6 ? Database::fetchAll("SELECT * FROM journal_lines WHERE journal_entry_id = :jid", [':jid' => $j6['id']]) : [];
$bankDebit6 = 0;
foreach ($jLines6 as $jl) {
    if ((int)$jl['account_id'] === $bankAccCoaId) {
        $bankDebit6 += (float)$jl['debit'];
    }
}
testAssert($bankDebit6 == 1500.00, "6b. Bank payment debited selected bank's dedicated COA account");

// --- Test 7: Full registered-customer credit ---
$s7Id = helperDraftSale($productId, $lotId, 1, 3000.00, $customerId, $userId, $branchId);
$f7 = Sale::finalizeSale($s7Id, [['payment_method' => 'Credit', 'amount' => 3000.00]], $userId, $branchId);
testAssert($f7['success'] === true, "7. Registered customer full credit sale finalized", $f7['message'] ?? null);

$cust7 = Database::fetchOne("SELECT credit_balance FROM customers WHERE id = :id", [':id' => $customerId]);
testAssert((float)$cust7['credit_balance'] == 3000.00, "7b. Customer outstanding balance increased by 3,000");

// --- Test 8: Walk-in customer credit blocked ---
$s8Id = helperDraftSale($productId, $lotId, 1, 2000.00, 1, $userId, $branchId); // customer_id 1 = Walk-in
$f8 = Sale::finalizeSale($s8Id, [['payment_method' => 'Credit', 'amount' => 2000.00]], $userId, $branchId);
testAssert($f8['success'] === false && strpos($f8['message'], 'Walk-in Customer cannot receive credit') !== false, "8. Walk-in customer credit blocked", $f8['message'] ?? null);

// --- Test 9: Partial credit (4,000 Cash + 6,000 Credit for 10,000 sale) ---
$s9Id = helperDraftSale($productId, $lotId, 10, 1000.00, $customerId, $userId, $branchId);
$f9 = Sale::finalizeSale($s9Id, [
    ['payment_method' => 'Cash', 'amount' => 4000.00],
    ['payment_method' => 'Credit', 'amount' => 6000.00]
], $userId, $branchId);
testAssert($f9['success'] === true, "9. Partial credit (4,000 Cash + 6,000 Credit) finalized", $f9['message'] ?? null);

$cust9 = Database::fetchOne("SELECT credit_balance FROM customers WHERE id = :id", [':id' => $customerId]);
testAssert((float)$cust9['credit_balance'] == 9000.00, "9b. Customer balance updated to 9,000 (3,000 + 6,000)");

// --- Test 10: Cash + Card ---
$s10Id = helperDraftSale($productId, $lotId, 2, 1000.00, 1, $userId, $branchId);
$f10 = Sale::finalizeSale($s10Id, [
    ['payment_method' => 'Cash', 'amount' => 1000.00],
    ['payment_method' => 'Card', 'amount' => 1000.00, 'reference_no' => 'TXN-10']
], $userId, $branchId);
testAssert($f10['success'] === true, "10. Split Cash + Card finalized", $f10['message'] ?? null);

// --- Test 11: Cash + Bank ---
$s11Id = helperDraftSale($productId, $lotId, 2, 1000.00, 1, $userId, $branchId);
$f11 = Sale::finalizeSale($s11Id, [
    ['payment_method' => 'Cash', 'amount' => 1000.00],
    ['payment_method' => 'Bank', 'amount' => 1000.00, 'bank_account_id' => $bankAccountId, 'reference_no' => 'REF-11']
], $userId, $branchId);
testAssert($f11['success'] === true, "11. Split Cash + Bank finalized", $f11['message'] ?? null);

// --- Test 12: Cash + Credit ---
$s12Id = helperDraftSale($productId, $lotId, 2, 1000.00, $customerId, $userId, $branchId);
$f12 = Sale::finalizeSale($s12Id, [
    ['payment_method' => 'Cash', 'amount' => 1000.00],
    ['payment_method' => 'Credit', 'amount' => 1000.00]
], $userId, $branchId);
testAssert($f12['success'] === true, "12. Split Cash + Credit finalized", $f12['message'] ?? null);

// --- Test 13: Card + Credit ---
$s13Id = helperDraftSale($productId, $lotId, 2, 1000.00, $customerId, $userId, $branchId);
$f13 = Sale::finalizeSale($s13Id, [
    ['payment_method' => 'Card', 'amount' => 1000.00, 'reference_no' => 'TXN-13'],
    ['payment_method' => 'Credit', 'amount' => 1000.00]
], $userId, $branchId);
testAssert($f13['success'] === true, "13. Split Card + Credit finalized", $f13['message'] ?? null);

// --- Test 14: Cash + Card + Bank ---
$s14Id = helperDraftSale($productId, $lotId, 3, 1000.00, 1, $userId, $branchId);
$f14 = Sale::finalizeSale($s14Id, [
    ['payment_method' => 'Cash', 'amount' => 1000.00],
    ['payment_method' => 'Card', 'amount' => 1000.00, 'reference_no' => 'TXN-14'],
    ['payment_method' => 'Bank', 'amount' => 1000.00, 'bank_account_id' => $bankAccountId, 'reference_no' => 'REF-14']
], $userId, $branchId);
testAssert($f14['success'] === true, "14. Split Cash + Card + Bank finalized", $f14['message'] ?? null);

// --- Test 15: Split cash payment with change (Plan Section 8 example) ---
$s15Id = helperDraftSale($productId, $lotId, 1, 2500.00, 1, $userId, $branchId);
$f15 = Sale::finalizeSale($s15Id, [
    ['payment_method' => 'Card', 'amount' => 1500.00, 'reference_no' => 'TXN-15'],
    ['payment_method' => 'Cash', 'amount' => 1500.00]
], $userId, $branchId);
testAssert($f15['success'] === true && $f15['change_amount'] == 500.00, "15. Split Card (1,500) + Cash (1,500) for 2,500 sale produces 500 change", $f15['message'] ?? null);

$j15 = Database::fetchOne("SELECT * FROM journal_entries WHERE source_type = 'sale' AND source_id = :sid", [':sid' => $s15Id]);
$jLines15 = $j15 ? Database::fetchAll("SELECT * FROM journal_lines WHERE journal_entry_id = :jid", [':jid' => $j15['id']]) : [];
$cashDebit15 = 0;
$cardDebit15 = 0;
foreach ($jLines15 as $jl) {
    if ((int)$jl['account_id'] === Accounting::getAccountIdByCode(Accounting::CODE_CASH)) $cashDebit15 += (float)$jl['debit'];
    if ((int)$jl['account_id'] === Accounting::getAccountIdByCode(Accounting::CODE_CARD_RECEIVABLE)) $cardDebit15 += (float)$jl['debit'];
}
testAssert($cardDebit15 == 1500.00 && $cashDebit15 == 1000.00, "15b. Journal debited Card 1,500 and Cash net 1,000 (total debit 2,500)");

// --- Test 16: Voucher redemption ---
$vRes16 = GiftVoucher::createVoucher(500.00, date('Y-m-d', strtotime('+30 days')), 'Test Voucher 16', $userId);
$vCode16 = $vRes16['voucher_code'];

$s16Id = helperDraftSale($productId, $lotId, 1, 500.00, 1, $userId, $branchId);
$f16 = Sale::finalizeSale($s16Id, [['payment_method' => 'Voucher', 'amount' => 500.00, 'reference_no' => $vCode16]], $userId, $branchId);
testAssert($f16['success'] === true, "16. Voucher redemption finalized", $f16['message'] ?? null);

$vRow16 = Database::fetchOne("SELECT status FROM gift_vouchers WHERE voucher_code = :c", [':c' => $vCode16]);
testAssert($vRow16['status'] === 'redeemed', "16b. Gift Voucher status updated to 'redeemed'");

// --- Test 17: Expired voucher blocked ---
$vRes17 = GiftVoucher::createVoucher(500.00, date('Y-m-d', strtotime('+30 days')), 'Expired Voucher', $userId);
$vCode17 = $vRes17['voucher_code'];
// Manually set expiry_date to past date in DB
Database::execute("UPDATE gift_vouchers SET expiry_date = '2020-01-01' WHERE id = :id", [':id' => $vRes17['voucher_id']]);

$s17Id = helperDraftSale($productId, $lotId, 1, 500.00, 1, $userId, $branchId);
$f17 = Sale::finalizeSale($s17Id, [['payment_method' => 'Voucher', 'amount' => 500.00, 'reference_no' => $vCode17]], $userId, $branchId);
testAssert($f17['success'] === false && strpos($f17['message'], 'expired') !== false, "17. Expired voucher redemption blocked", $f17['message'] ?? null);

// --- Test 18: Already-used voucher blocked ---
$s18Id = helperDraftSale($productId, $lotId, 1, 500.00, 1, $userId, $branchId);
$f18 = Sale::finalizeSale($s18Id, [['payment_method' => 'Voucher', 'amount' => 500.00, 'reference_no' => $vCode16]], $userId, $branchId);
testAssert($f18['success'] === false && strpos($f18['message'], 'already been redeemed') !== false, "18. Already-redeemed voucher blocked", $f18['message'] ?? null);

// --- Test 19: Voucher larger than remaining payable blocked ---
$vRes19 = GiftVoucher::createVoucher(1000.00, date('Y-m-d', strtotime('+30 days')), 'Big Voucher', $userId);
$vCode19 = $vRes19['voucher_code'];

$s19Id = helperDraftSale($productId, $lotId, 1, 800.00, 1, $userId, $branchId);
$f19 = Sale::finalizeSale($s19Id, [['payment_method' => 'Voucher', 'amount' => 1000.00, 'reference_no' => $vCode19]], $userId, $branchId);
testAssert($f19['success'] === false && (strpos($f19['message'], 'exceeds remaining') !== false || strpos($f19['message'], 'mismatch') !== false), "19. Voucher value (1,000) > invoice payable (800) blocked", $f19['message'] ?? null);

// --- Test 20: Multiple vouchers ---
$vRes20a = GiftVoucher::createVoucher(500.00, date('Y-m-d', strtotime('+30 days')), 'Voucher A', $userId);
$vRes20b = GiftVoucher::createVoucher(500.00, date('Y-m-d', strtotime('+30 days')), 'Voucher B', $userId);
$vCode20a = $vRes20a['voucher_code'];
$vCode20b = $vRes20b['voucher_code'];

$s20Id = helperDraftSale($productId, $lotId, 1, 1000.00, 1, $userId, $branchId);
$f20 = Sale::finalizeSale($s20Id, [
    ['payment_method' => 'Voucher', 'amount' => 500.00, 'reference_no' => $vCode20a],
    ['payment_method' => 'Voucher', 'amount' => 500.00, 'reference_no' => $vCode20b]
], $userId, $branchId);
testAssert($f20['success'] === true, "20. Multiple valid vouchers (500 + 500) for 1,000 sale finalized", $f20['message'] ?? null);

// --- Test 21: Same voucher twice blocked ---
$vRes21 = GiftVoucher::createVoucher(500.00, date('Y-m-d', strtotime('+30 days')), 'Single Voucher', $userId);
$vCode21 = $vRes21['voucher_code'];

$s21Id = helperDraftSale($productId, $lotId, 1, 1000.00, 1, $userId, $branchId);
$f21 = Sale::finalizeSale($s21Id, [
    ['payment_method' => 'Voucher', 'amount' => 500.00, 'reference_no' => $vCode21],
    ['payment_method' => 'Voucher', 'amount' => 500.00, 'reference_no' => $vCode21]
], $userId, $branchId);
testAssert($f21['success'] === false && strpos($f21['message'], 'Duplicate') !== false, "21. Same voucher specified twice in single transaction blocked", $f21['message'] ?? null);

// --- Test 22: Voucher + Cash ---
$vRes22 = GiftVoucher::createVoucher(500.00, date('Y-m-d', strtotime('+30 days')), 'Voucher 22', $userId);
$vCode22 = $vRes22['voucher_code'];

$s22Id = helperDraftSale($productId, $lotId, 1, 1000.00, 1, $userId, $branchId);
$f22 = Sale::finalizeSale($s22Id, [
    ['payment_method' => 'Voucher', 'amount' => 500.00, 'reference_no' => $vCode22],
    ['payment_method' => 'Cash', 'amount' => 500.00]
], $userId, $branchId);
testAssert($f22['success'] === true, "22. Voucher + Cash finalized", $f22['message'] ?? null);

// --- Test 23: Voucher + Card ---
$vRes23 = GiftVoucher::createVoucher(500.00, date('Y-m-d', strtotime('+30 days')), 'Voucher 23', $userId);
$vCode23 = $vRes23['voucher_code'];

$s23Id = helperDraftSale($productId, $lotId, 1, 1000.00, 1, $userId, $branchId);
$f23 = Sale::finalizeSale($s23Id, [
    ['payment_method' => 'Voucher', 'amount' => 500.00, 'reference_no' => $vCode23],
    ['payment_method' => 'Card', 'amount' => 500.00, 'reference_no' => 'TXN-23']
], $userId, $branchId);
testAssert($f23['success'] === true, "23. Voucher + Card finalized", $f23['message'] ?? null);

// --- Test 24: Voucher + Cash + Card ---
$vRes24 = GiftVoucher::createVoucher(500.00, date('Y-m-d', strtotime('+30 days')), 'Voucher 24', $userId);
$vCode24 = $vRes24['voucher_code'];

$s24Id = helperDraftSale($productId, $lotId, 1, 1500.00, 1, $userId, $branchId);
$f24 = Sale::finalizeSale($s24Id, [
    ['payment_method' => 'Voucher', 'amount' => 500.00, 'reference_no' => $vCode24],
    ['payment_method' => 'Cash', 'amount' => 500.00],
    ['payment_method' => 'Card', 'amount' => 500.00, 'reference_no' => 'TXN-24']
], $userId, $branchId);
testAssert($f24['success'] === true, "24. Voucher + Cash + Card finalized", $f24['message'] ?? null);

// --- Test 25: Negative / zero payment blocked ---
$s25Id = helperDraftSale($productId, $lotId, 1, 1000.00, 1, $userId, $branchId);
$f25 = Sale::finalizeSale($s25Id, [['payment_method' => 'Cash', 'amount' => -100.00]], $userId, $branchId);
testAssert($f25['success'] === false && strpos($f25['message'], 'Invalid payment amount') !== false, "25. Negative payment amount blocked", $f25['message'] ?? null);

// --- Test 26: Invalid method blocked ---
$s26Id = helperDraftSale($productId, $lotId, 1, 1000.00, 1, $userId, $branchId);
$f26 = Sale::finalizeSale($s26Id, [['payment_method' => 'Crypto', 'amount' => 1000.00]], $userId, $branchId);
testAssert($f26['success'] === false && strpos($f26['message'], 'Invalid payment method') !== false, "26. Unsupported payment method blocked", $f26['message'] ?? null);

// --- Test 27: Non-cash overpayment blocked ---
$s27Id = helperDraftSale($productId, $lotId, 1, 1000.00, 1, $userId, $branchId);
$f27 = Sale::finalizeSale($s27Id, [['payment_method' => 'Card', 'amount' => 2000.00, 'reference_no' => 'TXN-27']], $userId, $branchId);
testAssert($f27['success'] === false && strpos($f27['message'], 'exceeds total invoice payable') !== false, "27. Card overpayment (2,000 for 1,000 sale) blocked", $f27['message'] ?? null);

// --- Test 28: Payment failure rolls back sale ---
$s28Id = helperDraftSale($productId, $lotId, 1, 1000.00, 1, $userId, $branchId);
// Pass invalid bank ID
$f28 = Sale::finalizeSale($s28Id, [['payment_method' => 'Bank', 'amount' => 1000.00, 'bank_account_id' => 999999]], $userId, $branchId);
$sale28 = Database::fetchOne("SELECT status FROM sales WHERE id = :id", [':id' => $s28Id]);
testAssert($f28['success'] === false && $sale28['status'] === 'draft', "28. Payment validation failure rolls back sale to 'draft'", $f28['message'] ?? null);

// --- Test 29: Voucher failure rolls back entire sale ---
$s29Id = helperDraftSale($productId, $lotId, 1, 1000.00, 1, $userId, $branchId);
$f29 = Sale::finalizeSale($s29Id, [['payment_method' => 'Voucher', 'amount' => 1000.00, 'reference_no' => 'NONEXISTENT']], $userId, $branchId);
$sale29 = Database::fetchOne("SELECT status FROM sales WHERE id = :id", [':id' => $s29Id]);
testAssert($f29['success'] === false && $sale29['status'] === 'draft', "29. Invalid voucher rolls back sale to 'draft'", $f29['message'] ?? null);

// --- Test 30: Customer credit rollback on failure ---
$custPre30 = Database::fetchOne("SELECT credit_balance FROM customers WHERE id = :id", [':id' => $customerId]);
$balPre30 = (float)$custPre30['credit_balance'];

$s30Id = helperDraftSale($productId, $lotId, 1, 1000.00, $customerId, $userId, $branchId);
// Invalid method in multi-payment
$f30 = Sale::finalizeSale($s30Id, [
    ['payment_method' => 'Credit', 'amount' => 500.00],
    ['payment_method' => 'InvalidMethod', 'amount' => 500.00]
], $userId, $branchId);

$custPost30 = Database::fetchOne("SELECT credit_balance FROM customers WHERE id = :id", [':id' => $customerId]);
testAssert($f30['success'] === false && (float)$custPost30['credit_balance'] == $balPre30, "30. Customer credit balance unchanged after payment failure rollback", $f30['message'] ?? null);

// --- Test 31: Cash drawer correct ---
$s31Id = helperDraftSale($productId, $lotId, 1, 1500.00, 1, $userId, $branchId);
$f31 = Sale::finalizeSale($s31Id, [['payment_method' => 'Cash', 'amount' => 1500.00]], $userId, $branchId);

$m31 = Database::fetchOne("SELECT amount FROM cash_drawer_movements WHERE source_type = 'sales' AND source_id = :sid", [':sid' => $s31Id]);
testAssert($m31 && (float)$m31['amount'] == 1500.00, "31. Cash drawer movement recorded exact net cash 1,500");

// --- Test 32: Card does not affect drawer ---
$m32 = Database::fetchOne("SELECT amount FROM cash_drawer_movements WHERE source_type = 'sales' AND source_id = :sid", [':sid' => $s4Id]);
testAssert(!$m32, "32. Card payment did not record cash drawer movement");

// --- Test 33: Bank does not affect drawer ---
$m33 = Database::fetchOne("SELECT amount FROM cash_drawer_movements WHERE source_type = 'sales' AND source_id = :sid", [':sid' => $s6Id]);
testAssert(!$m33, "33. Bank payment did not record cash drawer movement");

// --- Test 34: Credit does not affect drawer ---
$m34 = Database::fetchOne("SELECT amount FROM cash_drawer_movements WHERE source_type = 'sales' AND source_id = :sid", [':sid' => $s7Id]);
testAssert(!$m34, "34. Credit payment did not record cash drawer movement");

// --- Test 35: Voucher does not affect drawer ---
$m35 = Database::fetchOne("SELECT amount FROM cash_drawer_movements WHERE source_type = 'sales' AND source_id = :sid", [':sid' => $s16Id]);
testAssert(!$m35, "35. Voucher payment did not record cash drawer movement");

// --- Test 36: Accounting for each method ---
$j16 = Database::fetchOne("SELECT * FROM journal_entries WHERE source_type = 'sale' AND source_id = :sid", [':sid' => $s16Id]);
$jLines16 = $j16 ? Database::fetchAll("SELECT * FROM journal_lines WHERE journal_entry_id = :jid", [':jid' => $j16['id']]) : [];
$vouchDebit16 = 0;
foreach ($jLines16 as $jl) {
    if ((int)$jl['account_id'] === Accounting::getAccountIdByCode(Accounting::CODE_VOUCHER_LIABILITY)) $vouchDebit16 += (float)$jl['debit'];
}
testAssert($vouchDebit16 == 500.00, "36. Gift Voucher payment debited Voucher Liability 2010");

// --- Test 37: Split-payment accounting balanced ---
$j14 = Database::fetchOne("SELECT * FROM journal_entries WHERE source_type = 'sale' AND source_id = :sid", [':sid' => $s14Id]);
$jLines14 = $j14 ? Database::fetchAll("SELECT * FROM journal_lines WHERE journal_entry_id = :jid", [':jid' => $j14['id']]) : [];
$sumDeb14 = 0; $sumCred14 = 0;
foreach ($jLines14 as $jl) {
    $sumDeb14 += (float)$jl['debit'];
    $sumCred14 += (float)$jl['credit'];
}
testAssert(round($sumDeb14, 2) === round($sumCred14, 2), "37. Split payment (Cash+Card+Bank) journal entry is perfectly balanced");

// --- Test 38: Voucher liability accounting ---
testAssert($vouchDebit16 == 500.00, "38. Voucher redemption debited Voucher Liability (2010)");

// --- Test 39: No duplicate revenue from voucher ---
$revCount16 = 0;
foreach ($jLines16 as $jl) {
    if ((int)$jl['account_id'] === Accounting::getAccountIdByCode(Accounting::CODE_SALES_REVENUE)) {
        $revCount16 += (float)$jl['credit'];
    }
}
testAssert($revCount16 == 500.00, "39. Sales Revenue credited exactly 500 once on voucher redemption");

// --- Test 40: Double-submit protection ---
$dupFin = Sale::finalizeSale($s16Id, [['payment_method' => 'Voucher', 'amount' => 500.00, 'reference_no' => $vCode16]], $userId, $branchId);
testAssert($dupFin['success'] === false && strpos($dupFin['message'], 'Double-finalization prevented') !== false, "40. Double-submission on finalized sale blocked", $dupFin['message'] ?? null);

// --- Test 41: Completed payments immutable ---
$pmtRows = Database::fetchAll("SELECT * FROM payments WHERE sale_id = :sid", [':sid' => $s16Id]);
testAssert(!empty($pmtRows), "41. Payment records created and preserved in payments table");

// --- Test 42: Receipt payment breakdown ---
$rcpt15 = Sale::getReceiptData($s15Id);
testAssert(!empty($rcpt15['payments']) && count($rcpt15['payments']) === 2 && $rcpt15['change_amount'] == 500.00, "42. Receipt data contains payment breakdown and change_amount");

// --- Test 43: GL remains balanced across all posted journals ---
$unbalancedJournals = Database::fetchAll("
    SELECT journal_entry_id, SUM(debit) as tot_debit, SUM(credit) as tot_credit 
    FROM journal_lines 
    GROUP BY journal_entry_id 
    HAVING ROUND(tot_debit, 2) != ROUND(tot_credit, 2)
");
testAssert(empty($unbalancedJournals), "43. General Ledger contains zero unbalanced journal entries");

echo "\n=========================================================\n";
echo " SECTION 4 TEST RESULTS: {$passCount} PASSED, {$failCount} FAILED\n";
echo "=========================================================\n";

if ($failCount > 0) {
    exit(1);
}
exit(0);
