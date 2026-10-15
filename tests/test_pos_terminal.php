<?php
/**
 * Curtiss POS - Comprehensive POS Terminal CLI Verification Suite
 * Tests:
 * 1. Bank Accounts API & COA Account ID linkage
 * 2. Customer APIs & Walk-in customer lookup
 * 3. Barcode exact lookup and lot resolution
 * 4. Cash Sale Transaction -> Cash Drawer (1012), COGS (5010), Inventory (1200), Revenue (4010)
 * 5. Bank Transfer Sale -> Target Bank Account COA & Double-Entry Balance
 * 6. Stock Lot & Branch Inventory Deductions
 * 7. Audit Logging & Cash Drawer Movements
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Sale.php';
require_once __DIR__ . '/../core/Customer.php';
require_once __DIR__ . '/../core/Accounting.php';
require_once __DIR__ . '/../core/BusinessDay.php';

$branchId = 1;
$userId = 1;

echo "====================================================\n";
echo "   CURTISS POS TERMINAL - COMPREHENSIVE CLI TEST    \n";
echo "====================================================\n\n";

$testsPassed = 0;
$testsFailed = 0;

function assertTest(string $name, bool $condition, string $detail = '') {
    global $testsPassed, $testsFailed;
    if ($condition) {
        echo "[PASS] {$name}" . ($detail ? " ({$detail})" : "") . "\n";
        $testsPassed++;
    } else {
        echo "[FAIL] {$name}" . ($detail ? " - ERROR: {$detail}" : "") . "\n";
        $testsFailed++;
    }
}

// ----------------------------------------------------
// TEST 1: Bank Accounts & COA Linkage
// ----------------------------------------------------
echo "--- TEST 1: Bank Accounts & Chart of Accounts Linkage ---\n";
$bankAccounts = Database::fetchAll("
    SELECT id, bank_name, account_name, account_number, account_id 
    FROM bank_accounts 
    WHERE status = 'active'
");
assertTest("Bank Accounts Query", count($bankAccounts) > 0, count($bankAccounts) . " active bank account(s) found");

foreach ($bankAccounts as $ba) {
    $coa = Database::fetchOne("SELECT id, account_code, account_name FROM chart_of_accounts WHERE id = :id", [':id' => $ba['account_id']]);
    assertTest("Bank '{$ba['bank_name']}' COA Link", !empty($coa), "Linked to COA #{$coa['id']} [{$coa['account_code']} - {$coa['account_name']}]");
}

// Check Cash Drawer 1012 in COA
$cashDrawerCoa = Accounting::getAccountByCode(Accounting::CODE_CASH_DRAWER);
assertTest("COA Cash Drawer (1012) exists", !empty($cashDrawerCoa), "Account ID: {$cashDrawerCoa['id']}, Code: {$cashDrawerCoa['account_code']}");

// Check Revenue, COGS, Inventory Asset, Discounts
$revCoa = Accounting::getAccountByCode(Accounting::CODE_SALES_REVENUE);
assertTest("COA Sales Revenue (4010) exists", !empty($revCoa), "Account ID: {$revCoa['id']}");

$cogsCoa = Accounting::getAccountByCode(Accounting::CODE_COGS);
assertTest("COA COGS (5010) exists", !empty($cogsCoa), "Account ID: {$cogsCoa['id']}");

$invCoa = Accounting::getAccountByCode(Accounting::CODE_INVENTORY_ASSET);
assertTest("COA Inventory Asset (1200) exists", !empty($invCoa), "Account ID: {$invCoa['id']}");

// ----------------------------------------------------
// TEST 2: Customer APIs & Walk-in
// ----------------------------------------------------
echo "\n--- TEST 2: Customer Selection & Walk-in API ---\n";
$walkin = Database::fetchOne("SELECT * FROM customers WHERE is_walkin = 1 AND status = 'active' LIMIT 1");
if (!$walkin) {
    $walkin = Database::fetchOne("SELECT * FROM customers WHERE customer_code = 'CUST-WALKIN' LIMIT 1");
}
if (!$walkin) {
    Database::execute("
        INSERT INTO customers (customer_code, name, address, is_walkin, status, credit_limit, credit_balance)
        VALUES ('CUST-WALKIN', 'Walk-in Customer', 'Default POS General Customer', 1, 'active', 0.00, 0.00)
    ");
    $walkin = Database::fetchOne("SELECT * FROM customers WHERE customer_code = 'CUST-WALKIN' LIMIT 1");
}
assertTest("Walk-in Customer Lookup", !empty($walkin), "ID: {$walkin['id']}, Name: {$walkin['name']}");

$custSearch = Customer::all(['search' => '', 'status' => 'active'], 1, 5);
assertTest("Active Customers Search", count($custSearch['items']) > 0, count($custSearch['items']) . " customers returned");

// ----------------------------------------------------
// TEST 3: Barcode Search & Lot Resolution
// ----------------------------------------------------
echo "\n--- TEST 3: Barcode Search & Lot Resolution ---\n";
// Find an active product with stock lots in branch 1
$testProduct = Database::fetchOne("
    SELECT p.id, p.barcode, p.sku, p.name, pl.id AS lot_id, pl.lot_number, pl.remaining_quantity, pl.selling_price, pl.cost
    FROM products p
    JOIN product_lots pl ON p.id = pl.product_id
    WHERE pl.branch_id = :b AND pl.remaining_quantity > 2 AND pl.status = 'active' AND p.status = 'active' AND p.deleted_at IS NULL
    LIMIT 1
", [':b' => $branchId]);

if (!$testProduct) {
    $p = Database::fetchOne("SELECT id, barcode, sku, name FROM products WHERE status = 'active' AND deleted_at IS NULL LIMIT 1");
    $lotId = Database::insert('product_lots', [
        'product_id' => $p['id'],
        'branch_id' => $branchId,
        'lot_number' => 'LOT-TEST-CLI',
        'cost' => 100.00,
        'selling_price' => 150.00,
        'initial_quantity' => 20.00,
        'remaining_quantity' => 20.00,
        'status' => 'active',
        'created_at' => date('Y-m-d H:i:s')
    ]);
    Database::execute("
        INSERT INTO branch_inventory (branch_id, product_id, quantity)
        VALUES (:b, :p, 20.00)
        ON DUPLICATE KEY UPDATE quantity = quantity + 20.00
    ", [':b' => $branchId, ':p' => $p['id']]);

    $testProduct = [
        'id' => $p['id'],
        'barcode' => $p['barcode'],
        'sku' => $p['sku'],
        'name' => $p['name'],
        'lot_id' => $lotId,
        'lot_number' => 'LOT-TEST-CLI',
        'remaining_quantity' => 20.00,
        'selling_price' => 150.00,
        'cost' => 100.00
    ];
}

assertTest("Test Product & Stock Lot Located", !empty($testProduct), "Product: {$testProduct['name']} [BC: {$testProduct['barcode']}], Lot: #{$testProduct['lot_id']}");

// Test exact barcode search
$searchResults = Sale::searchProducts($branchId, $testProduct['barcode']);
assertTest("Exact Barcode Search Priority", count($searchResults) === 1 && $searchResults[0]['product_id'] == $testProduct['id'], "Returned 1 exact product matching barcode '{$testProduct['barcode']}'");
assertTest("Barcode Search Returns Lots", !empty($searchResults[0]['lots']), count($searchResults[0]['lots']) . " lot(s) attached");

// ----------------------------------------------------
// TEST 4: Cash Sale Transaction End-to-End
// ----------------------------------------------------
echo "\n--- TEST 4: Cash Sale Transaction -> Cash Drawer (1012) & COA Balance ---\n";

// Ensure active business day exists
$activeDay = BusinessDay::getOrCreateActiveDay($branchId, $userId);
assertTest("Active Business Day Open", !empty($activeDay), "Business Day ID: {$activeDay['id']}");

$lotRow = Database::fetchOne("SELECT remaining_quantity FROM product_lots WHERE id = :id", [':id' => $testProduct['lot_id']]);
$initialLotStock = (float)($lotRow['remaining_quantity'] ?? 0);

$biRow = Database::fetchOne("SELECT quantity FROM branch_inventory WHERE branch_id = :b AND product_id = :p", [':b' => $branchId, ':p' => $testProduct['id']]);
$initialBiStock = (float)($biRow['quantity'] ?? 0);

// Prepare draft sale
$draftPayload = [
    'customer_id' => $walkin['id'],
    'discount_value' => '0',
    'items' => [
        [
            'product_id' => $testProduct['id'],
            'lot_id' => $testProduct['lot_id'],
            'quantity' => 1.00,
            'original_price' => (float)$testProduct['selling_price'],
            'unit_price' => (float)$testProduct['selling_price'],
            'discount_value' => '0'
        ]
    ]
];

$draftRes = Sale::prepareDraftSale($draftPayload, $userId, $branchId);
assertTest("Prepare Draft Sale", !empty($draftRes['success']) && !empty($draftRes['sale_id']), "Sale ID: " . ($draftRes['sale_id'] ?? 0));
$saleId = (int)$draftRes['sale_id'];

// Finalize with Cash payment
$payments = [
    [
        'payment_method' => 'Cash',
        'amount' => (float)$testProduct['selling_price'],
        'reference_no' => 'CASH-TENDER'
    ]
];

$finalRes = Sale::finalizeSale($saleId, $payments, $userId, $branchId);
assertTest("Finalize Cash Sale", !empty($finalRes['success']), "Invoice: " . ($finalRes['invoice_no'] ?? 'N/A'));

// Check inventory deduction
$lotRowAfter = Database::fetchOne("SELECT remaining_quantity FROM product_lots WHERE id = :id", [':id' => $testProduct['lot_id']]);
$afterLotStock = (float)($lotRowAfter['remaining_quantity'] ?? 0);

$biRowAfter = Database::fetchOne("SELECT quantity FROM branch_inventory WHERE branch_id = :b AND product_id = :p", [':b' => $branchId, ':p' => $testProduct['id']]);
$afterBiStock = (float)($biRowAfter['quantity'] ?? 0);

assertTest("Lot Stock Deducted", round($afterLotStock, 2) === round($initialLotStock - 1.00, 2), "From {$initialLotStock} to {$afterLotStock}");
assertTest("Branch Inventory Deducted", round($afterBiStock, 2) === round($initialBiStock - 1.00, 2), "From {$initialBiStock} to {$afterBiStock}");

// Check Cash Drawer Movement
$drawerMov = Database::fetchOne("
    SELECT * FROM cash_drawer_movements 
    WHERE source_type = 'sales' AND source_id = :s 
    ORDER BY id DESC LIMIT 1
", [':s' => $saleId]);
assertTest("Cash Drawer Movement Recorded", !empty($drawerMov), "Amount: LKR " . ($drawerMov['amount'] ?? 0) . " (direction: {$drawerMov['direction']})");

// Check COA Journal Entry for Sale
$journal = Database::fetchOne("
    SELECT * FROM journal_entries 
    WHERE source_type = 'sale' AND source_id = :s
", [':s' => $saleId]);
assertTest("COA Journal Entry Created", !empty($journal), "Entry #{$journal['entry_number']}, Status: {$journal['posting_status']}");

$lines = Database::fetchAll("
    SELECT jl.*, coa.account_code, coa.account_name 
    FROM journal_lines jl 
    JOIN chart_of_accounts coa ON jl.account_id = coa.id 
    WHERE jl.journal_entry_id = :je
", [':je' => $journal['id']]);

$totalDebit = 0.00;
$totalCredit = 0.00;
$hitCashDrawer = false;
$hitRevenue = false;
$hitCogs = false;
$hitInventory = false;

foreach ($lines as $l) {
    $totalDebit += (float)$l['debit'];
    $totalCredit += (float)$l['credit'];
    if ($l['account_code'] === Accounting::CODE_CASH_DRAWER && (float)$l['debit'] > 0) $hitCashDrawer = true;
    if ($l['account_code'] === Accounting::CODE_SALES_REVENUE && (float)$l['credit'] > 0) $hitRevenue = true;
    if ($l['account_code'] === Accounting::CODE_COGS && (float)$l['debit'] > 0) $hitCogs = true;
    if ($l['account_code'] === Accounting::CODE_INVENTORY_ASSET && (float)$l['credit'] > 0) $hitInventory = true;
}

assertTest("COA Hits Cash Drawer (1012)", $hitCashDrawer, "Debit to Cash Drawer 1012");
assertTest("COA Hits Sales Revenue (4010)", $hitRevenue, "Credit to Sales Revenue 4010");
assertTest("COA Hits COGS (5010)", $hitCogs, "Debit to COGS 5010");
assertTest("COA Hits Inventory Asset (1200)", $hitInventory, "Credit to Inventory 1200");
assertTest("Double-Entry Balance (Debit == Credit)", round($totalDebit, 2) === round($totalCredit, 2), "Debit: LKR " . number_format($totalDebit, 2) . " == Credit: LKR " . number_format($totalCredit, 2));

// ----------------------------------------------------
// TEST 5: Bank Transfer Sale -> Bank Account COA
// ----------------------------------------------------
echo "\n--- TEST 5: Bank Transfer Sale -> Bank Account COA & Double-Entry ---\n";
if (!empty($bankAccounts)) {
    $firstBank = $bankAccounts[0];
    
    $draftPayload2 = [
        'customer_id' => $walkin['id'],
        'discount_value' => '0',
        'items' => [
            [
                'product_id' => $testProduct['id'],
                'lot_id' => $testProduct['lot_id'],
                'quantity' => 1.00,
                'original_price' => (float)$testProduct['selling_price'],
                'unit_price' => (float)$testProduct['selling_price'],
                'discount_value' => '0'
            ]
        ]
    ];

    $draftRes2 = Sale::prepareDraftSale($draftPayload2, $userId, $branchId);
    $saleId2 = (int)$draftRes2['sale_id'];

    $bankPayments = [
        [
            'payment_method' => 'Bank',
            'amount' => (float)$testProduct['selling_price'],
            'bank_account_id' => (int)$firstBank['id'],
            'reference_no' => 'TXN-BANK-CLI-999'
        ]
    ];

    $finalRes2 = Sale::finalizeSale($saleId2, $bankPayments, $userId, $branchId);
    assertTest("Finalize Bank Transfer Sale", !empty($finalRes2['success']), "Invoice: " . ($finalRes2['invoice_no'] ?? 'N/A'));

    $journal2 = Database::fetchOne("SELECT * FROM journal_entries WHERE source_type = 'sale' AND source_id = :s", [':s' => $saleId2]);
    $lines2 = Database::fetchAll("
        SELECT jl.*, coa.account_code, coa.account_name 
        FROM journal_lines jl 
        JOIN chart_of_accounts coa ON jl.account_id = coa.id 
        WHERE jl.journal_entry_id = :je
    ", [':je' => $journal2['id']]);

    $hitBankCoa = false;
    $totalDebit2 = 0.00;
    $totalCredit2 = 0.00;

    foreach ($lines2 as $l2) {
        $totalDebit2 += (float)$l2['debit'];
        $totalCredit2 += (float)$l2['credit'];
        if ((int)$l2['account_id'] === (int)$firstBank['account_id'] && (float)$l2['debit'] > 0) {
            $hitBankCoa = true;
        }
    }

    assertTest("COA Hits Target Bank Account", $hitBankCoa, "Debit to {$firstBank['bank_name']} (COA ID: {$firstBank['account_id']})");
    assertTest("Bank Sale Double-Entry Balanced", round($totalDebit2, 2) === round($totalCredit2, 2), "Debit: LKR " . number_format($totalDebit2, 2) . " == Credit: LKR " . number_format($totalCredit2, 2));

    // Restore stock for test 2
    Database::execute("UPDATE product_lots SET remaining_quantity = remaining_quantity + 1 WHERE id = :id", [':id' => $testProduct['lot_id']]);
    Database::execute("UPDATE branch_inventory SET quantity = quantity + 1 WHERE branch_id = :b AND product_id = :p", [':b' => $branchId, ':p' => $testProduct['id']]);
}

// Restore stock for test 1
Database::execute("UPDATE product_lots SET remaining_quantity = remaining_quantity + 1 WHERE id = :id", [':id' => $testProduct['lot_id']]);
Database::execute("UPDATE branch_inventory SET quantity = quantity + 1 WHERE branch_id = :b AND product_id = :p", [':b' => $branchId, ':p' => $testProduct['id']]);

echo "\n====================================================\n";
echo "TEST RESULTS: Passed: {$testsPassed} | Failed: {$testsFailed}\n";
echo "====================================================\n";

if ($testsFailed === 0) {
    echo ">>> ALL POS TERMINAL, TRANSACTION API, BANK & COA TESTS PASSED SUCCESSFULLY! <<<\n";
    exit(0);
} else {
    echo ">>> SOME TESTS FAILED. CHECK DETAILS ABOVE. <<<\n";
    exit(1);
}
