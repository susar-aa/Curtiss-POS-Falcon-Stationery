<?php
/**
 * CLI Test Script: Enhanced Product Import Engine & Separate Issue Preview Verification
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/ProductImportEngine.php';
require_once __DIR__ . '/../core/Product.php';
require_once __DIR__ . '/../core/Brand.php';
require_once __DIR__ . '/../core/Category.php';
require_once __DIR__ . '/../core/Supplier.php';

echo "=== STARTING PRODUCT IMPORT ENHANCEMENTS CLI TESTS ===\n\n";

$db = Database::getInstance()->getConnection();

$uniqueSuffix = rand(10000, 99999);
$testBarcodeDup = "89399999" . $uniqueSuffix;
$testBarcodeValid1 = "89388888" . $uniqueSuffix;
$testBarcodeOverride = "89377777" . $uniqueSuffix;

// Mock parsed CSV data with intentional issues
$testHeaders = ProductImportEngine::getStandardTemplateHeaders();
$mapping = ProductImportEngine::autoDetectMapping($testHeaders);

$testRows = [
    // Row 0 (Line 2): Valid clean product
    ['SKU_V1_' . $uniqueSuffix, $testBarcodeValid1, 'Atlas', 'Stationery', 'Valid Pen ' . $uniqueSuffix, 'Main Supplier', '50.00', '75.00', '10.00', '5.00', 'active'],
    
    // Row 1 (Line 3): Issue - Duplicate Barcode in CSV file
    ['SKU_D1_' . $uniqueSuffix, $testBarcodeDup, 'Atlas', 'Stationery', 'Pen Duplicate 1 ' . $uniqueSuffix, 'Main Supplier', '50.00', '75.00', '0.00', '5.00', 'active'],
    
    // Row 2 (Line 4): Issue - Same Duplicate Barcode
    ['SKU_D2_' . $uniqueSuffix, $testBarcodeDup, 'Atlas', 'Stationery', 'Pen Duplicate 2 ' . $uniqueSuffix, 'Main Supplier', '50.00', '75.00', '0.00', '5.00', 'active'],
    
    // Row 3 (Line 5): Issue - Empty Barcode & SKU
    ['', '', 'Atlas', 'Stationery', 'No Barcode Pen ' . $uniqueSuffix, 'Main Supplier', '50.00', '75.00', '0.00', '5.00', 'active'],
    
    // Row 4 (Line 6): Issue - Empty Product Name
    ['SKU_NONAME_' . $uniqueSuffix, '89366666' . $uniqueSuffix, 'Atlas', 'Stationery', '', 'Main Supplier', '50.00', '75.00', '0.00', '5.00', 'active'],
    
    // Row 5 (Line 7): Issue - Selling Price < Cost warning / invalid cost
    ['SKU_LOSS_' . $uniqueSuffix, '89355555' . $uniqueSuffix, 'Atlas', 'Stationery', 'Loss Item ' . $uniqueSuffix, 'Main Supplier', '150.00', '80.00', '0.00', '5.00', 'active']
];

$parsedData = [
    'headers' => $testHeaders,
    'rows'    => $testRows
];

// TEST 1: PREVIEW VALIDATION & ISSUE SEPARATION
echo "1. Testing ProductImportEngine::preview()...\n";
$preview = ProductImportEngine::preview($parsedData, $mapping, 'create_and_update', ['auto_create_masters' => true]);

assert($preview['success'] === true, "Preview should succeed");
echo " - Summary Total: " . $preview['summary']['total_rows'] . "\n";
echo " - Valid Count: " . $preview['summary']['valid_count'] . "\n";
echo " - Issue Count: " . $preview['summary']['issue_count'] . "\n";

assert($preview['summary']['valid_count'] === 1, "Only Row 0 should be valid without fixes");
assert($preview['summary']['issue_count'] === 5, "5 rows should be flagged with issues");
assert(count($preview['issue_items']) === 5, "issue_items array must have 5 entries");
assert(count($preview['valid_items']) === 1, "valid_items array must have 1 entry");

// Verify issue types
$duplicateIssues = array_filter($preview['issue_items'], function($it) {
    return !empty($it['issues']) && array_filter($it['issues'], fn($iss) => $iss['type'] === 'duplicate');
});
assert(count($duplicateIssues) === 2, "Rows 1 and 2 must have duplicate issue flagged");
echo " [PASS] Duplicate barcode correctly flagged across rows\n";

$emptyBarcodeIssue = array_filter($preview['issue_items'], function($it) {
    return $it['row_idx'] === 3;
});
assert(!empty($emptyBarcodeIssue), "Row 3 must be in issue items");
echo " [PASS] Missing barcode and SKU flagged\n";

assert(!empty($preview['available_masters']['brands']), "available_masters.brands must be populated");
echo " [PASS] available_masters populated for UI autocomplete\n\n";

// TEST 2: COMMIT WITH ROW OVERRIDES AND EXCLUSIONS
echo "2. Testing ProductImportEngine::commitImport() with Overrides and Exclusions...\n";

// We will exclude rows 2, 3, 4, 5 and fix Row 1 with a new unique barcode!
$rowOverrides = [
    1 => [
        'sku'           => 'SKU_D1_FIXED_' . $uniqueSuffix,
        'barcode'       => $testBarcodeOverride,
        'brand'         => 'Atlas',
        'category'      => 'Stationery',
        'name'          => 'Fixed Pen ' . $uniqueSuffix,
        'cost'          => '55.00',
        'selling_price' => '85.00',
        'supplier'      => 'Atlas Supplier',
        'status'        => 'active'
    ]
];

$excludedIndices = [2, 3, 4, 5];

$commitRes = ProductImportEngine::commitImport($parsedData, $mapping, 'create_and_update', [
    'file_name'        => 'cli_test.csv',
    'row_overrides'    => $rowOverrides,
    'excluded_indices' => $excludedIndices
], 1);

assert($commitRes['success'] === true, "Commit should succeed: " . ($commitRes['message'] ?? ''));
echo " - Created: " . $commitRes['summary']['created_count'] . "\n";
echo " - Skipped (Excluded): " . $commitRes['summary']['skipped_count'] . "\n";

assert($commitRes['summary']['created_count'] === 2, "Expected 2 created products (Row 0 and overridden Row 1)");
assert($commitRes['summary']['skipped_count'] === 4, "Expected 4 skipped products from excludedIndices");

// Verify products exist in DB
$p1 = Product::findByBarcode($testBarcodeValid1);
assert($p1 !== null, "Valid row 0 product should be created in DB");
echo " [PASS] Valid row 0 saved in database: ID #" . $p1['id'] . "\n";

$p2 = Product::findByBarcode($testBarcodeOverride);
assert($p2 !== null, "Overridden row 1 product should be created in DB");
assert($p2['sku'] === 'SKU_D1_FIXED_' . $uniqueSuffix, "Overridden SKU should match");
echo " [PASS] Fixed row 1 saved with overridden values: ID #" . $p2['id'] . " (SKU: " . $p2['sku'] . ")\n";

// CLEANUP: Clean up test records only (no truncate!)
echo "\n3. Cleaning up test fixture records...\n";
$testIds = array_filter([(int)($p1['id'] ?? 0), (int)($p2['id'] ?? 0)]);
foreach ($testIds as $tId) {
    $db->prepare("DELETE FROM stock_ledger WHERE product_id = :id")->execute([':id' => $tId]);
    $db->prepare("DELETE FROM branch_inventory WHERE product_id = :id")->execute([':id' => $tId]);
    $db->prepare("DELETE FROM product_suppliers WHERE product_id = :id")->execute([':id' => $tId]);
    $db->prepare("DELETE FROM products WHERE id = :id")->execute([':id' => $tId]);
}
if (!empty($commitRes['batch_no'])) {
    $db->prepare("DELETE FROM product_import_batches WHERE batch_no = :batch")->execute([':batch' => $commitRes['batch_no']]);
}

echo " [PASS] Test records cleaned up cleanly.\n\n";
echo "=== ALL PRODUCT IMPORT ENHANCEMENTS CLI TESTS PASSED SUCCESSFULLY! ===\n";
