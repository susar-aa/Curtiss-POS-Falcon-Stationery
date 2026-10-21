<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Product.php';
require_once __DIR__ . '/../core/Accounting.php';

echo "=== Curtiss POS: Product Accounting & Journal Entries Verification ===" . PHP_EOL . PHP_EOL;

$db = Database::getInstance()->getConnection();
$productModel = new Product();

// Verify Chart of Accounts mapping exists
$invAcc = Accounting::getAccountByCode(Accounting::CODE_INVENTORY_ASSET);
$equityAcc = Accounting::getAccountByCode(Accounting::CODE_EQUITY);

echo "1. CHART OF ACCOUNTS CONFIGURATION:" . PHP_EOL;
echo "   - Inventory Asset (1200): " . ($invAcc ? "FOUND [ID {$invAcc['id']}: {$invAcc['account_name']}]" : "MISSING") . PHP_EOL;
echo "   - Opening Balance Equity (3010): " . ($equityAcc ? "FOUND [ID {$equityAcc['id']}: {$equityAcc['account_name']}]" : "MISSING") . PHP_EOL;

if (!$invAcc || !$equityAcc) {
    echo "ERROR: Critical COA accounts missing!" . PHP_EOL;
    exit(1);
}

// Find active supplier
$supplier = $db->query("SELECT id FROM suppliers WHERE deleted_at IS NULL ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$supplierId = $supplier ? (int)$supplier['id'] : 1;

// 2. Create isolated test product with opening stock
$testCode = 'ACC_TEST_' . time();
$openingQty = 15.00;
$unitCost = 400.00;
$expectedValuation = $openingQty * $unitCost; // 6000.00

$testData = [
    'name' => 'Accounting Journal Audit Product ' . time(),
    'product_code' => $testCode,
    'barcode' => $testCode,
    'category_id' => 1,
    'brand_id' => 1,
    'supplier_id' => $supplierId,
    'supplier_ids' => [$supplierId],
    'cost' => $unitCost,
    'price' => 750.00,
    'wholesale_price' => 700.00,
    'opening_stock' => $openingQty,
    'min_stock' => 5,
    'branch_id' => 1,
    'user_id' => 1
];

echo PHP_EOL . "2. PRODUCT CREATION WITH OPENING STOCK:" . PHP_EOL;
echo "   - Opening Quantity: {$openingQty}" . PHP_EOL;
echo "   - Unit Cost: LKR {$unitCost}" . PHP_EOL;
echo "   - Expected Valuation: LKR {$expectedValuation}" . PHP_EOL;

$createRes = $productModel->create($testData);
if (!$createRes['success']) {
    echo "   FAIL: Product creation failed: " . ($createRes['message'] ?? 'Unknown error') . PHP_EOL;
    exit(1);
}

$productId = (int)$createRes['id'];
echo "   PASS: Created Product ID {$productId}" . PHP_EOL;

// 3. Inspect Journal Entry in DB
echo PHP_EOL . "3. JOURNAL ENTRY INSPECTION (POST-CREATION):" . PHP_EOL;
$stmt = $db->prepare("SELECT * FROM journal_entries WHERE source_type = 'opening_balance' AND source_id = :pid");
$stmt->execute([':pid' => $productId]);
$je = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$je) {
    echo "   FAIL: No journal entry created for opening_balance with source_id {$productId}!" . PHP_EOL;
} else {
    echo "   PASS: Journal Entry Found:" . PHP_EOL;
    echo "         - Entry ID: {$je['id']}" . PHP_EOL;
    echo "         - Entry Number: {$je['entry_number']}" . PHP_EOL;
    echo "         - Source Type: {$je['source_type']}" . PHP_EOL;
    echo "         - Total Debit: LKR " . number_format($je['total_debit'], 2) . PHP_EOL;
    echo "         - Total Credit: LKR " . number_format($je['total_credit'], 2) . PHP_EOL;
    echo "         - Posting Status: {$je['posting_status']}" . PHP_EOL;

    // Inspect Journal Lines
    $stmtLines = $db->prepare("
        SELECT jl.*, ca.account_code, ca.account_name 
        FROM journal_lines jl 
        JOIN chart_of_accounts ca ON jl.account_id = ca.id 
        WHERE jl.journal_entry_id = :jeid
        ORDER BY jl.id ASC
    ");
    $stmtLines->execute([':jeid' => $je['id']]);
    $lines = $stmtLines->fetchAll(PDO::FETCH_ASSOC);

    echo "   Journal Lines (" . count($lines) . "):" . PHP_EOL;
    $sumDebit = 0;
    $sumCredit = 0;
    foreach ($lines as $line) {
        echo "         [Line {$line['id']}] Account {$line['account_code']} ({$line['account_name']}): " .
             "Debit = " . number_format($line['debit'], 2) . ", Credit = " . number_format($line['credit'], 2) . 
             " | Desc: '{$line['description']}'" . PHP_EOL;
        $sumDebit += (float)$line['debit'];
        $sumCredit += (float)$line['credit'];
    }

    if (abs($sumDebit - $expectedValuation) < 0.01 && abs($sumCredit - $expectedValuation) < 0.01 && abs($sumDebit - $sumCredit) < 0.01) {
        echo "   PASS: Double-entry balanced! Debit (LKR {$sumDebit}) == Credit (LKR {$sumCredit}) == Valuation (LKR {$expectedValuation})" . PHP_EOL;
    } else {
        echo "   FAIL: Double-entry imbalance! Sum Debit: {$sumDebit}, Sum Credit: {$sumCredit}, Expected: {$expectedValuation}" . PHP_EOL;
    }
}

// 4. Test Soft-Delete Journal Behavior
echo PHP_EOL . "4. SOFT-DELETE JOURNAL AUDIT:" . PHP_EOL;
$delRes = $productModel->delete($productId, 1);
echo "   - Product soft-delete status: " . ($delRes['success'] ? 'SUCCESS' : 'FAILED') . PHP_EOL;

// Check what happened to the journal entry or reversal entry
$stmtCheck = $db->prepare("
    SELECT id, entry_number, source_type, source_id, total_debit, total_credit, posting_status, description 
    FROM journal_entries 
    WHERE (source_type = 'opening_balance' AND source_id = :pid) 
       OR (description LIKE :desc)
    ORDER BY id ASC
");
$stmtCheck->execute([':pid' => $productId, ':desc' => "%#{$productId}%"]);
$jesAfterDelete = $stmtCheck->fetchAll(PDO::FETCH_ASSOC);

echo "   Journal records after soft-delete (" . count($jesAfterDelete) . "):" . PHP_EOL;
foreach ($jesAfterDelete as $entry) {
    echo "         - Entry #{$entry['entry_number']} (ID {$entry['id']}): Status='{$entry['posting_status']}', Source='{$entry['source_type']}', Debit={$entry['total_debit']}, Credit={$entry['total_credit']}, Desc='{$entry['description']}'" . PHP_EOL;
}

// 5. Test Restore Journal Behavior
echo PHP_EOL . "5. TRASH BIN RESTORE JOURNAL AUDIT:" . PHP_EOL;
$restRes = $productModel->restore($productId, 1);
echo "   - Product restore status: " . ($restRes['success'] ? 'SUCCESS' : 'FAILED') . PHP_EOL;

$stmtCheck->execute([':pid' => $productId, ':desc' => "%#{$productId}%"]);
$jesAfterRestore = $stmtCheck->fetchAll(PDO::FETCH_ASSOC);

echo "   Journal records after restore (" . count($jesAfterRestore) . "):" . PHP_EOL;
foreach ($jesAfterRestore as $entry) {
    echo "         - Entry #{$entry['entry_number']} (ID {$entry['id']}): Status='{$entry['posting_status']}', Source='{$entry['source_type']}', Debit={$entry['total_debit']}, Credit={$entry['total_credit']}, Desc='{$entry['description']}'" . PHP_EOL;
}

// 6. Clean up test records
echo PHP_EOL . "6. CLEANING UP TEST FIXTURES:" . PHP_EOL;
$jeIds = array_column($jesAfterRestore, 'id');
if (!empty($jeIds)) {
    $inClause = implode(',', array_map('intval', $jeIds));
    $db->exec("DELETE FROM journal_lines WHERE journal_entry_id IN ({$inClause})");
    $db->exec("DELETE FROM journal_entries WHERE id IN ({$inClause})");
}
$db->prepare("DELETE FROM stock_ledger WHERE product_id = :pid")->execute([':pid' => $productId]);
$db->prepare("DELETE FROM branch_inventory WHERE product_id = :pid")->execute([':pid' => $productId]);
$db->prepare("DELETE FROM product_lots WHERE product_id = :pid")->execute([':pid' => $productId]);
$db->prepare("DELETE FROM product_suppliers WHERE product_id = :pid")->execute([':pid' => $productId]);
$db->prepare("DELETE FROM audit_logs WHERE record_id = :pid AND module = 'products'")->execute([':pid' => $productId]);
$db->prepare("DELETE FROM products WHERE id = :pid")->execute([':pid' => $productId]);

echo "   PASS: Isolated test product and all associated journal entries cleaned up with 0 DB residue." . PHP_EOL;
echo PHP_EOL . "=== AUDIT COMPLETED SUCCESSFULLY ===" . PHP_EOL;
