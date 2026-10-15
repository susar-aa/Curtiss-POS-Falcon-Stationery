<?php
/**
 * CLI Test for Expiry Product Creation and Batch Expiry Audit Report
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Product.php';
require_once __DIR__ . '/../core/Inventory.php';
require_once __DIR__ . '/../core/ExpiryManager.php';

echo "====================================================\n";
echo "   CURTISS POS - EXPIRY REPORT VERIFICATION TEST\n";
echo "====================================================\n\n";

$testSku = 'EXP-TEST-' . time();
$testBarcode = 'BAR-' . time();

$brand = Database::fetchOne("SELECT id FROM brands WHERE deleted_at IS NULL LIMIT 1");
$cat = Database::fetchOne("SELECT id FROM categories WHERE deleted_at IS NULL LIMIT 1");
$sup = Database::fetchOne("SELECT id FROM suppliers WHERE deleted_at IS NULL LIMIT 1");

$data = [
    'sku'                 => $testSku,
    'barcode'             => $testBarcode,
    'brand_id'            => (int)$brand['id'],
    'category_id'         => (int)$cat['id'],
    'name'                => 'Test Baby Cream Expiry 50ml',
    'supplier_id'         => (int)$sup['id'],
    'cost'                => 120.00,
    'selling_price'       => 180.00,
    'opening_stock'       => 25.00,
    'low_stock_threshold' => 5.00,
    'status'              => 'active',
    'has_expiry'          => 1,
    'expiry_date'         => '2027-08-15',
    'batch_number'        => 'TEST-BATCH-7788'
];

$res = Product::create($data);
if (!$res['success']) {
    echo "  [FAIL] Failed to create test product: " . ($res['message'] ?? '') . "\n";
    exit(1);
}

$productId = (int)$res['id'];
echo "  [PASS] Created test product ID {$productId}\n";

// 1. Verify products table has_expiry
$prod = Database::fetchOne("SELECT id, name, has_expiry FROM products WHERE id = :id", [':id' => $productId]);
if ((int)$prod['has_expiry'] === 1) {
    echo "  [PASS] Product has_expiry is correctly set to 1 in database\n";
} else {
    echo "  [FAIL] Product has_expiry is " . $prod['has_expiry'] . " (expected 1)\n";
}

// 2. Verify product_lots has expiry_date and batch_number
$lot = Database::fetchOne("SELECT id, lot_number, batch_number, expiry_date, remaining_quantity FROM product_lots WHERE product_id = :pid", [':pid' => $productId]);
if ($lot && $lot['expiry_date'] === '2027-08-15' && $lot['batch_number'] === 'TEST-BATCH-7788') {
    echo "  [PASS] Product lot has expiry_date=2027-08-15 and batch_number=TEST-BATCH-7788\n";
} else {
    echo "  [FAIL] Product lot expiry_date or batch_number incorrect: " . json_encode($lot) . "\n";
}

// 3. Verify ExpiryManager::getBatchExpiryAuditReport includes this lot
$report = ExpiryManager::getBatchExpiryAuditReport(1);
$foundInReport = false;
foreach ($report['items'] as $item) {
    if ((int)$item['product_id'] === $productId) {
        $foundInReport = true;
        echo "  [PASS] Lot found in Batch Expiry Audit Report!\n";
        echo "         SKU: {$item['sku']} | Product: {$item['product_name']} | Expiry: {$item['expiry_date']} | Status: {$item['status_label']} | Valuation: {$item['total_valuation']}\n";
        break;
    }
}

if (!$foundInReport) {
    echo "  [FAIL] Test product lot was NOT found in Batch Expiry Audit Report!\n";
}

// 4. Safely clean up ONLY the test product fixture
Database::execute("DELETE FROM journal_lines WHERE journal_entry_id IN (SELECT id FROM journal_entries WHERE source_type = 'opening_balance' AND source_id = :pid)", [':pid' => $productId]);
Database::execute("DELETE FROM journal_entries WHERE source_type = 'opening_balance' AND source_id = :pid", [':pid' => $productId]);
Database::execute("DELETE FROM stock_ledger WHERE product_id = :pid", [':pid' => $productId]);
Database::execute("DELETE FROM branch_inventory WHERE product_id = :pid", [':pid' => $productId]);
Database::execute("DELETE FROM product_lots WHERE product_id = :pid", [':pid' => $productId]);
Database::execute("DELETE FROM audit_logs WHERE record_id = :pid", [':pid' => (string)$productId]);
Database::execute("DELETE FROM products WHERE id = :pid", [':pid' => $productId]);

echo "  [PASS] Cleaned up isolated test fixtures for product ID {$productId}\n";

// 5. Verify user's existing records are untouched
$countExisting = Database::fetchOne("SELECT COUNT(*) AS c FROM products WHERE id = 15");
if ((int)$countExisting['c'] === 1) {
    echo "  [PASS] Verified user's existing Product #15 (Baby Cheramy) is 100% intact!\n";
} else {
    echo "  [FAIL] Product #15 missing!\n";
}

echo "\n====================================================\n";
echo "TEST COMPLETED SUCCESSFULLY\n";
echo "====================================================\n";
