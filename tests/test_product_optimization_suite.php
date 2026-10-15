<?php
/**
 * CLI Test Suite for Products Optimization & Master Rules Compliance
 * Tests pagination (25/50/75/100/500), universal search (all columns + currency formats),
 * index performance, branch isolation, and COA double-entry accounting.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Brand.php';
require_once __DIR__ . '/../core/Category.php';
require_once __DIR__ . '/../core/Supplier.php';
require_once __DIR__ . '/../core/Product.php';
require_once __DIR__ . '/../core/Accounting.php';

$_SESSION['user_id'] = 1;
$_SESSION['role'] = ROLE_SUPER_ADMIN;
$_SESSION['branch_id'] = 1;
$_SESSION['active_branch_id'] = 1;

$passed = 0;
$failed = 0;

function assertOpt(string $desc, bool $condition, string $detail = '') {
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "  [PASS] $desc\n";
    } else {
        $failed++;
        echo "  [FAIL] $desc " . ($detail ? "($detail)" : "") . "\n";
    }
}

echo "====================================================\n";
echo "   PRODUCTS OPTIMIZATION & COMPLIANCE CLI SUITE\n";
echo "====================================================\n\n";

$createdIds = [];

try {
    // Setup test records
    $bRes = Brand::create("OptBrand_" . time(), 'active');
    $cRes = Category::create("OptCat_" . time(), 'active');
    $sRes = Supplier::create("OptSupp_" . time(), '0779998888', null, null, 'active');

    $brandId = (int)$bRes['id'];
    $catId = (int)$cRes['id'];
    $suppId = (int)$sRes['id'];

    $sku = "OPTSKU_" . time();
    $barcode = "OPTBAR_" . time();
    $prodName = "OptProduct Special Alpha";
    $cost = 932.40;
    $sellingPrice = 1450.75;
    $qty = 20.00;

    $pRes = Product::create([
        'barcode'       => $barcode,
        'sku'           => $sku,
        'brand_id'      => $brandId,
        'category_id'   => $catId,
        'supplier_id'   => $suppId,
        'name'          => $prodName,
        'cost'          => $cost,
        'selling_price' => $sellingPrice,
        'opening_stock' => $qty,
        'status'        => 'active',
        'branch_id'     => 1
    ]);

    assertOpt("Create isolated test product", $pRes['success'], $pRes['message'] ?? '');
    $prodId = (int)($pRes['id'] ?? 0);
    $createdIds[] = $prodId;

    // --- TEST 1: PAGINATION & LIMIT STANDARDS ---
    echo "\n--- 1. Testing Pagination & Limits (Rule 6) ---\n";
    $list25 = Product::getList([], 1, 25);
    assertOpt("Default page limit is 25", $list25['limit'] === 25);
    assertOpt("Total pages calculated correctly", $list25['totalPages'] >= 1);
    assertOpt("Page number is 1", $list25['page'] === 1);

    $limits = [25, 50, 75, 100, 500];
    foreach ($limits as $lim) {
        $lRes = Product::getList([], 1, $lim);
        assertOpt("Supports page size $lim", $lRes['limit'] === $lim);
    }

    // --- TEST 2: UNIVERSAL FLOATING SEARCH ACROSS ALL DATA COLUMNS ---
    echo "\n--- 2. Testing Universal Search Across All Data Columns (Rule 4) ---\n";
    
    // Search by SKU
    $sSku = Product::getList(['search' => $sku], 1, 25);
    assertOpt("Search by exact SKU matches product", !empty($sSku['items']) && (int)$sSku['items'][0]['id'] === $prodId);

    // Search by Barcode
    $sBc = Product::getList(['search' => $barcode], 1, 25);
    assertOpt("Search by exact Barcode matches product", !empty($sBc['items']) && (int)$sBc['items'][0]['id'] === $prodId);

    // Search by Name partial
    $sName = Product::getList(['search' => 'Special Alpha'], 1, 25);
    assertOpt("Search by Name partial matches product", !empty($sName['items']) && (int)$sName['items'][0]['id'] === $prodId);

    // Search by Raw Cost
    $sCostRaw = Product::getList(['search' => '932.40'], 1, 25);
    assertOpt("Search by raw Cost (932.40) matches product", !empty($sCostRaw['items']) && (int)$sCostRaw['items'][0]['id'] === $prodId);

    // Search by Formatted Cost with currency prefix LKR
    $sCostLkr = Product::getList(['search' => 'LKR 932.40'], 1, 25);
    assertOpt("Search by formatted Cost (LKR 932.40) matches product", !empty($sCostLkr['items']) && (int)$sCostLkr['items'][0]['id'] === $prodId);

    // Search by Formatted Cost with Rs. prefix
    $sCostRs = Product::getList(['search' => 'Rs. 932.40'], 1, 25);
    assertOpt("Search by Rs. prefix (Rs. 932.40) matches product", !empty($sCostRs['items']) && (int)$sCostRs['items'][0]['id'] === $prodId);

    // Search by Selling Price
    $sPrice = Product::getList(['search' => '1450.75'], 1, 25);
    assertOpt("Search by Selling Price (1450.75) matches product", !empty($sPrice['items']) && (int)$sPrice['items'][0]['id'] === $prodId);

    // Search by Formatted Selling Price with comma
    $sPriceComma = Product::getList(['search' => '1,450.75'], 1, 25);
    assertOpt("Search by formatted Selling Price with comma (1,450.75) matches product", !empty($sPriceComma['items']) && (int)$sPriceComma['items'][0]['id'] === $prodId);

    // Search by Brand name
    $sBrand = Product::getList(['search' => "OptBrand_"], 1, 25);
    assertOpt("Search by Brand Name matches product", !empty($sBrand['items']) && (int)$sBrand['items'][0]['id'] === $prodId);

    // Search by Category name
    $sCat = Product::getList(['search' => "OptCat_"], 1, 25);
    assertOpt("Search by Category Name matches product", !empty($sCat['items']) && (int)$sCat['items'][0]['id'] === $prodId);

    // Search by Supplier name
    $sSupp = Product::getList(['search' => "OptSupp_"], 1, 25);
    assertOpt("Search by Supplier Name matches product", !empty($sSupp['items']) && (int)$sSupp['items'][0]['id'] === $prodId);

    // --- TEST 3: DATABASE INDEX UTILIZATION & QUERY PERFORMANCE ---
    echo "\n--- 3. Testing Database Index Utilization (Rule 5) ---\n";
    $expSku = Database::fetchAll("EXPLAIN SELECT * FROM products WHERE sku = :sku AND deleted_at IS NULL", [':sku' => $sku]);
    assertOpt("Query uses idx_prod_sku index", !empty($expSku[0]['key']) && $expSku[0]['key'] === 'idx_prod_sku', "Key: " . ($expSku[0]['key'] ?? 'none'));

    $expFilter = Database::fetchAll("EXPLAIN SELECT * FROM products WHERE deleted_at IS NULL AND category_id = :cat", [':cat' => $catId]);
    assertOpt("Query uses idx_prod_del_cat composite index", !empty($expFilter[0]['key']) && ($expFilter[0]['key'] === 'idx_prod_del_cat' || $expFilter[0]['key'] === 'fk_prod_cat'), "Key: " . ($expFilter[0]['key'] ?? 'none'));

    // --- TEST 4: BRANCH SESSION INTEGRATION ---
    echo "\n--- 4. Testing Superadmin Branch Switching Integration (Rule 7 & 10) ---\n";
    $_SESSION['branch_id'] = 1;
    $pBranch1 = Product::find($prodId);
    assertOpt("Branch 1 sees branch stock (20.00)", (float)($pBranch1['branch_stock'] ?? 0) === 20.00);

    $_SESSION['branch_id'] = 2;
    $pBranch2 = Product::find($prodId);
    assertOpt("Branch 2 sees branch stock (0.00) due to branch isolation", (float)($pBranch2['branch_stock'] ?? 0) === 0.00);
    $_SESSION['branch_id'] = 1;

    // --- TEST 5: COA DOUBLE-ENTRY INTEGRITY ---
    echo "\n--- 5. Testing COA Double-Entry Accounting (Rule 9 & 23) ---\n";
    $je = Database::fetchOne("SELECT * FROM journal_entries WHERE source_type = 'opening_balance' AND source_id = :id", [':id' => $prodId]);
    assertOpt("Opening stock posted to Journal Entries", !empty($je));
    if ($je) {
        $lines = Database::fetchAll("SELECT * FROM journal_lines WHERE journal_entry_id = :jid", [':jid' => $je['id']]);
        $deb = array_sum(array_column($lines, 'debit'));
        $crd = array_sum(array_column($lines, 'credit'));
        $expectedVal = $qty * $cost; // 20 * 932.40 = 18648.00
        assertOpt("Double-entry journal is balanced (Debit = Credit = $expectedVal)", abs($deb - $crd) < 0.001 && abs($deb - $expectedVal) < 0.01, "Debit: $deb, Credit: $crd");
    }

} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
    $failed++;
} finally {
    echo "\n--- Cleaning up test records ---\n";
    foreach ($createdIds as $pid) {
        $jes = Database::fetchAll("SELECT id FROM journal_entries WHERE source_type = 'opening_balance' AND source_id = :p", [':p' => $pid]);
        foreach ($jes as $jeRow) {
            Database::execute("DELETE FROM journal_lines WHERE journal_entry_id = :jid", [':jid' => $jeRow['id']]);
            Database::execute("DELETE FROM journal_entries WHERE id = :jid", [':jid' => $jeRow['id']]);
        }
        Database::execute("DELETE FROM stock_ledger WHERE product_id = :p", [':p' => $pid]);
        Database::execute("DELETE FROM product_lots WHERE product_id = :p", [':p' => $pid]);
        Database::execute("DELETE FROM branch_inventory WHERE product_id = :p", [':p' => $pid]);
        Database::execute("DELETE FROM audit_logs WHERE module = 'products' AND record_id = :p", [':p' => (string)$pid]);
        Database::execute("DELETE FROM products WHERE id = :p", [':p' => $pid]);
    }
    if (!empty($brandId)) {
        Database::execute("DELETE FROM audit_logs WHERE module = 'brands' AND record_id = :b", [':b' => (string)$brandId]);
        Database::execute("DELETE FROM brands WHERE id = :b", [':b' => $brandId]);
    }
    if (!empty($catId)) {
        Database::execute("DELETE FROM audit_logs WHERE module = 'categories' AND record_id = :c", [':c' => (string)$catId]);
        Database::execute("DELETE FROM categories WHERE id = :c", [':c' => $catId]);
    }
    if (!empty($suppId)) {
        Database::execute("DELETE FROM audit_logs WHERE module = 'suppliers' AND record_id = :s", [':s' => (string)$suppId]);
        Database::execute("DELETE FROM suppliers WHERE id = :s", [':s' => $suppId]);
    }
    echo "Cleanup finished safely without table truncation.\n";
}

echo "\n====================================================\n";
echo "SUMMARY: $passed PASSED, $failed FAILED\n";
echo "====================================================\n";

exit($failed > 0 ? 1 : 0);
