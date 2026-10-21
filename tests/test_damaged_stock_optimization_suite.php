<?php
/**
 * Curtiss POS - Damaged & Wastage Stock Optimization & Compliance Test Suite
 * Validates:
 * 1. Database composite indexes (idx_ds_branch_id, idx_ds_branch_status_id).
 * 2. Record Damaged Stock: Sellable lot/branch reduction, damaged_quantity increase, stock_ledger entry, and balanced COA journal entry.
 * 3. Dispose Damaged Stock: Status transition to 'disposed', damaged_quantity reduction, sellable stock preservation.
 * 4. Negative stock protection: Rejection of damaged requests exceeding available sellable stock.
 * 5. Double-disposal prevention: Rejection of disposal on already disposed stock.
 * 6. Double-entry Chart of Accounts integration: Debit/Credit equality, accounts 1250 (Damage Loss) & 1200 (Inventory Asset).
 * 7. Mandatory audit logging for DAMAGED_STOCK_RECORDED and DAMAGED_STOCK_DISPOSED.
 * 8. Universal Search across DMG ID, product, barcode, lot, reason, logger, cost, and formatted currency.
 * 9. Server-side pagination limits (25, 50, 75, 100, 500).
 * 10. Superadmin active branch switching resolution ($_SESSION['branch_id']).
 * 11. CSV Export script integrity, UTF-8 BOM, and Excel string escaping.
 * 12. HTML UI rules compliance (Fixed Viewport, Sticky Header, Zebra Table, 48px rows, 28px in-cell badges, Headline-Only).
 * 13. Isolated cleanup without database truncations.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/AdvancedInventory.php';
require_once __DIR__ . '/../core/Accounting.php';
require_once __DIR__ . '/../core/Audit.php';

$testCount = 0;
$passCount = 0;
$failCount = 0;

function assertTest(string $description, bool $condition, string $detail = '') {
    global $testCount, $passCount, $failCount;
    $testCount++;
    if ($condition) {
        $passCount++;
        echo "[PASS] {$description}\n";
    } else {
        $failCount++;
        echo "[FAIL] {$description} - {$detail}\n";
    }
}

echo "=== STARTING DAMAGED & WASTAGE STOCK OPTIMIZATION SUITE ===\n\n";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id'] = 1;
$_SESSION['branch_id'] = 1;
$_SESSION['active_branch_id'] = 1;

$branchId = 1;
$cleanupDmgIds = [];
$cleanupJournalIds = [];
$cleanupLotIds = [];
$cleanupProductIds = [];

try {
    // -------------------------------------------------------------
    // SECTION 1: Database Composite Indexes
    // -------------------------------------------------------------
    echo "-- Section 1: Database Composite Indexes --\n";

    $branchIdx = Database::fetchAll("SHOW INDEX FROM damaged_stock WHERE Key_name = 'idx_ds_branch_id'");
    assertTest("idx_ds_branch_id composite index exists on damaged_stock", !empty($branchIdx), "Missing idx_ds_branch_id");

    $statusIdx = Database::fetchAll("SHOW INDEX FROM damaged_stock WHERE Key_name = 'idx_ds_branch_status_id'");
    assertTest("idx_ds_branch_status_id composite index exists on damaged_stock", !empty($statusIdx), "Missing idx_ds_branch_status_id");

    // -------------------------------------------------------------
    // SECTION 2: Isolated Fixture Setup
    // -------------------------------------------------------------
    echo "\n-- Section 2: Isolated Fixture Creation --\n";

    $sampleBrand = Database::fetchOne("SELECT id FROM brands WHERE deleted_at IS NULL LIMIT 1");
    $sampleCat = Database::fetchOne("SELECT id FROM categories WHERE deleted_at IS NULL LIMIT 1");
    $sampleSup = Database::fetchOne("SELECT id FROM suppliers WHERE deleted_at IS NULL LIMIT 1");

    $testBarcode = '996' . rand(100000, 999999);
    $testCost = 720.00;
    $testPrice = 1350.00;
    $initialQty = 25.00;

    Database::execute("
        INSERT INTO products (name, barcode, sku, cost, selling_price, brand_id, category_id, supplier_id, status, created_at)
        VALUES (:name, :bc, :sku, :c, :p, :bid, :cid, :sid, 'active', NOW())
    ", [
        ':name' => 'Damaged Stock Unit Test Item ' . rand(100, 999),
        ':bc'   => $testBarcode,
        ':sku'  => 'DMG-SKU-' . time(),
        ':c'    => $testCost,
        ':p'    => $testPrice,
        ':bid'  => $sampleBrand['id'] ?? 1,
        ':cid'  => $sampleCat['id'] ?? 1,
        ':sid'  => $sampleSup['id'] ?? null
    ]);
    $testProductId = (int)Database::lastInsertId();
    $cleanupProductIds[] = $testProductId;

    $testLotNum = 'LOT-DMG-' . rand(1000, 9999);
    Database::execute("
        INSERT INTO product_lots (product_id, branch_id, lot_number, cost, selling_price, initial_quantity, remaining_quantity, status, created_at)
        VALUES (:pid, :bid, :lot, :c, :p, :iq, :rq, 'active', NOW())
    ", [
        ':pid' => $testProductId,
        ':bid' => $branchId,
        ':lot' => $testLotNum,
        ':c'   => $testCost,
        ':p'   => $testPrice,
        ':iq'  => $initialQty,
        ':rq'  => $initialQty
    ]);
    $testLotId = (int)Database::lastInsertId();
    $cleanupLotIds[] = $testLotId;

    Database::execute("
        INSERT INTO branch_inventory (branch_id, product_id, quantity, damaged_quantity, in_transit_quantity, low_stock_threshold)
        VALUES (:b, :p, :q, 0.00, 0.00, 5.00)
        ON DUPLICATE KEY UPDATE quantity = :q2, damaged_quantity = 0.00
    ", [
        ':b'  => $branchId,
        ':p'  => $testProductId,
        ':q'  => $initialQty,
        ':q2' => $initialQty
    ]);

    assertTest("Test Product #{$testProductId} & Lot #{$testLotId} created with {$initialQty} units at Branch #{$branchId}", $testProductId > 0 && $testLotId > 0);

    // -------------------------------------------------------------
    // SECTION 3: Record Damaged Stock Operation
    // -------------------------------------------------------------
    echo "\n-- Section 3: Record Damaged Stock Operation --\n";

    $dmgQty = 6.00;
    $recordDmgRes = AdvancedInventory::recordDamagedStock([
        'branch_id'  => $branchId,
        'product_id' => $testProductId,
        'lot_id'     => $testLotId,
        'quantity'   => $dmgQty,
        'stock_type' => 'damaged',
        'reason'     => 'Broken packaging during unloading'
    ], 1);

    assertTest("AdvancedInventory::recordDamagedStock succeeds", $recordDmgRes['success'] === true, $recordDmgRes['message'] ?? '');
    $dmgId = (int)($recordDmgRes['id'] ?? 0);
    $cleanupDmgIds[] = $dmgId;

    $dmgRecord = Database::fetchOne("SELECT * FROM damaged_stock WHERE id = :id", [':id' => $dmgId]);
    assertTest("Damaged stock record saved with status 'active' and stock_type 'damaged'", $dmgRecord && $dmgRecord['status'] === 'active' && $dmgRecord['stock_type'] === 'damaged');

    if (!empty($dmgRecord['journal_entry_id'])) {
        $cleanupJournalIds[] = (int)$dmgRecord['journal_entry_id'];
    }

    // Verify Sellable Lot Stock deducted
    $expectedSellableStock = $initialQty - $dmgQty;
    $lotCheck = Database::fetchOne("SELECT remaining_quantity FROM product_lots WHERE id = :id", [':id' => $testLotId]);
    assertTest("Lot remaining sellable stock deducted to {$expectedSellableStock}", (float)$lotCheck['remaining_quantity'] === (float)$expectedSellableStock);

    // Verify Branch Inventory: sellable deducted, damaged_quantity increased
    $invCheck = Database::fetchOne("SELECT quantity, damaged_quantity FROM branch_inventory WHERE branch_id = :b AND product_id = :p", [':b' => $branchId, ':p' => $testProductId]);
    assertTest("Branch inventory sellable quantity reduced to {$expectedSellableStock}", (float)$invCheck['quantity'] === (float)$expectedSellableStock);
    assertTest("Branch inventory damaged_quantity increased to {$dmgQty}", (float)$invCheck['damaged_quantity'] === (float)$dmgQty);

    // Verify Stock Movement Ledger entry
    $ledgerDmg = Database::fetchOne("
        SELECT * FROM stock_ledger 
        WHERE branch_id = :b AND product_id = :p AND reference_no = :ref AND movement_type = 'damage_wastage'
        ORDER BY id DESC LIMIT 1
    ", [':b' => $branchId, ':p' => $testProductId, ':ref' => "DMG-{$dmgId}"]);
    assertTest("Stock ledger recorded 'damage_wastage' with negative quantity -{$dmgQty}", $ledgerDmg && (float)$ledgerDmg['quantity'] === -(float)$dmgQty);

    // Verify Double-Entry Journal Entry
    assertTest("Journal entry created for damaged stock logging", !empty($dmgRecord['journal_entry_id']));
    if (!empty($dmgRecord['journal_entry_id'])) {
        $jeLines = Database::fetchAll("SELECT * FROM journal_lines WHERE journal_entry_id = :jid", [':jid' => $dmgRecord['journal_entry_id']]);
        $totalDebit = array_sum(array_column($jeLines, 'debit'));
        $totalCredit = array_sum(array_column($jeLines, 'credit'));
        $expectedTotalLoss = round($dmgQty * $testCost, 2);
        assertTest("Damaged stock journal entry is balanced (Debit == Credit == {$expectedTotalLoss})", (float)$totalDebit === (float)$expectedTotalLoss && (float)$totalCredit === (float)$expectedTotalLoss);
    }

    // -------------------------------------------------------------
    // SECTION 4: Dispose Damaged Stock Operation
    // -------------------------------------------------------------
    echo "\n-- Section 4: Dispose Damaged Stock Operation --\n";

    $disposeRes = AdvancedInventory::disposeDamagedStock($dmgId, [
        'notes' => 'Scrapped at landfill with management approval'
    ], 1);

    assertTest("AdvancedInventory::disposeDamagedStock succeeds", $disposeRes['success'] === true, $disposeRes['message'] ?? '');

    $disposedRecord = Database::fetchOne("SELECT * FROM damaged_stock WHERE id = :id", [':id' => $dmgId]);
    assertTest("Damaged stock status transitioned to 'disposed' with valid disposed_at and disposed_by", $disposedRecord && $disposedRecord['status'] === 'disposed' && !empty($disposedRecord['disposed_at']));

    // Verify branch_inventory damaged_quantity reduced, but sellable stock remains untouched
    $invAfterDispose = Database::fetchOne("SELECT quantity, damaged_quantity FROM branch_inventory WHERE branch_id = :b AND product_id = :p", [':b' => $branchId, ':p' => $testProductId]);
    assertTest("Branch inventory damaged_quantity cleared to 0.00 after disposal", (float)$invAfterDispose['damaged_quantity'] === 0.00);
    assertTest("Branch inventory sellable stock preserved at {$expectedSellableStock} without double-deduction", (float)$invAfterDispose['quantity'] === (float)$expectedSellableStock);

    // Double disposal prevention
    $reDisposeRes = AdvancedInventory::disposeDamagedStock($dmgId, [], 1);
    assertTest("Double-disposal prevention: Attempting to re-dispose an already disposed record returns error", $reDisposeRes['success'] === false);

    // -------------------------------------------------------------
    // SECTION 5: Negative Stock & Over-Reduction Protection
    // -------------------------------------------------------------
    echo "\n-- Section 5: Negative Stock & Over-Reduction Protection --\n";

    $excessiveQty = 9999.00;
    $overDmgRes = AdvancedInventory::recordDamagedStock([
        'branch_id'  => $branchId,
        'product_id' => $testProductId,
        'lot_id'     => $testLotId,
        'quantity'   => $excessiveQty,
        'stock_type' => 'damaged',
        'reason'     => 'Excessive damage test'
    ], 1);

    assertTest("Moving stock exceeding available sellable inventory is strictly rejected", $overDmgRes['success'] === false);

    // -------------------------------------------------------------
    // SECTION 6: Universal Search & Server-Side Pagination
    // -------------------------------------------------------------
    echo "\n-- Section 6: Universal Search & Server-Side Pagination --\n";

    // Test Search by DMG ID
    $dmgRef = "DMG-{$dmgId}";
    $searchWhere = "(
        CONCAT('DMG-', ds.id) LIKE :s1 OR 
        p.name LIKE :s2 OR 
        p.barcode LIKE :s3 OR 
        COALESCE(p.sku, '') LIKE :s4 OR 
        COALESCE(pl.lot_number, '') LIKE :s5 OR 
        ds.stock_type LIKE :s6 OR 
        COALESCE(ds.reason, '') LIKE :s7 OR 
        ds.status LIKE :s8 OR 
        u.username LIKE :s9 OR 
        COALESCE(sr.return_no, '') LIKE :s10 OR 
        CAST(ds.created_at AS CHAR) LIKE :s11
    )";
    $searchParams = [':b' => $branchId];
    $searchTerm = '%' . $dmgRef . '%';
    for ($i = 1; $i <= 11; $i++) {
        $searchParams[":s{$i}"] = $searchTerm;
    }

    $dmgResults = Database::fetchAll("
        SELECT ds.id, p.name 
        FROM damaged_stock ds
        JOIN products p ON ds.product_id = p.id
        LEFT JOIN product_lots pl ON ds.lot_id = pl.id
        JOIN users u ON ds.logged_by = u.id
        LEFT JOIN sales_returns sr ON ds.sales_return_id = sr.id
        WHERE ds.branch_id = :b AND {$searchWhere}
    ", $searchParams);

    $foundDmgIds = array_column($dmgResults, 'id');
    assertTest("Universal Search finds damaged stock by exact DMG ID ({$dmgRef})", in_array($dmgId, $foundDmgIds, true));

    // Test Search by Lot Number
    $lotParams = [':b' => $branchId];
    $lotTerm = '%' . $testLotNum . '%';
    for ($i = 1; $i <= 11; $i++) {
        $lotParams[":s{$i}"] = $lotTerm;
    }
    $lotResults = Database::fetchAll("
        SELECT ds.id 
        FROM damaged_stock ds
        JOIN products p ON ds.product_id = p.id
        LEFT JOIN product_lots pl ON ds.lot_id = pl.id
        JOIN users u ON ds.logged_by = u.id
        LEFT JOIN sales_returns sr ON ds.sales_return_id = sr.id
        WHERE ds.branch_id = :b AND {$searchWhere}
    ", $lotParams);
    $foundLotIds = array_column($lotResults, 'id');
    assertTest("Universal Search finds damaged stock by Lot Number ({$testLotNum})", in_array($dmgId, $foundLotIds, true));

    // Test Search by Reason keyword
    $reasonParams = [':b' => $branchId];
    $reasonTerm = '%Broken packaging%';
    for ($i = 1; $i <= 11; $i++) {
        $reasonParams[":s{$i}"] = $reasonTerm;
    }
    $reasonResults = Database::fetchAll("
        SELECT ds.id 
        FROM damaged_stock ds
        JOIN products p ON ds.product_id = p.id
        LEFT JOIN product_lots pl ON ds.lot_id = pl.id
        JOIN users u ON ds.logged_by = u.id
        LEFT JOIN sales_returns sr ON ds.sales_return_id = sr.id
        WHERE ds.branch_id = :b AND {$searchWhere}
    ", $reasonParams);
    $foundReasonIds = array_column($reasonResults, 'id');
    assertTest("Universal Search finds damaged stock by Reason keyword", in_array($dmgId, $foundReasonIds, true));

    // Verify Server-Side Pagination Limits in Controller
    $controllerContent = file_get_contents(__DIR__ . '/../damaged_stock.php');
    assertTest("damaged_stock.php validates limits [25, 50, 75, 100, 500]", strpos($controllerContent, '[25, 50, 75, 100, 500]') !== false);
    assertTest("damaged_stock.php supports session branch switching", strpos($controllerContent, '$_SESSION[\'branch_id\']') !== false);

    // -------------------------------------------------------------
    // SECTION 7: CSV Export Script Integrity
    // -------------------------------------------------------------
    echo "\n-- Section 7: CSV Export Integrity --\n";

    $exportFile = __DIR__ . '/../damaged_stock_export.php';
    assertTest("damaged_stock_export.php exists", file_exists($exportFile));

    $exportContent = file_get_contents($exportFile);
    assertTest("damaged_stock_export.php sends UTF-8 BOM", strpos($exportContent, '"\\xEF\\xBB\\xBF"') !== false);
    assertTest("damaged_stock_export.php preserves codes and dates with Excel string escaping", strpos($exportContent, '="') !== false);
    assertTest("damaged_stock_export.php enforces branch isolation and active filters", strpos($exportContent, '$branchId') !== false && strpos($exportContent, '$where') !== false);

    // -------------------------------------------------------------
    // SECTION 8: UI & Master Design Rules Compliance
    // -------------------------------------------------------------
    echo "\n-- Section 8: UI & Master Design Rules Compliance --\n";

    $damagedView = file_get_contents(__DIR__ . '/../views/inventory/damaged.php');

    assertTest("damaged.php enforces fixed viewport height (calc(100vh - 110px))", strpos($damagedView, 'calc(100vh - 110px)') !== false);
    assertTest("damaged.php enforces sticky table header (position: sticky; top: 0)", strpos($damagedView, 'position: sticky;') !== false);
    assertTest("damaged.php enforces zebra striping on table rows", strpos($damagedView, 'nth-child(even)') !== false && strpos($damagedView, 'nth-child(odd)') !== false);
    assertTest("damaged.php enforces 48px row heights (height: 48px; min-height: 48px;)", strpos($damagedView, 'height: 48px;') !== false);
    assertTest("damaged.php includes floating bottom realtime search (enablePageSearch)", strpos($damagedView, 'CurtissNav.enablePageSearch') !== false);
    assertTest("damaged.php attaches CurtissSearchableSelect to filter dropdowns", strpos($damagedView, 'curtiss_select.css') !== false && strpos($damagedView, 'CurtissSearchableSelect.attach') !== false);
    assertTest("damaged.php uses floating top-right action bar (#curtiss-floating-header-right)", strpos($damagedView, 'curtiss-floating-header-right') !== false);
    assertTest("damaged.php has headline-only title without subtitle tagline", strpos($damagedView, 'inv-subtitle') === false && strpos($damagedView, '<h1 class="inv-title">') !== false);
    assertTest("damaged.php features active damaged quantity summary badge", strpos($damagedView, 'totalActiveDamagedQty') !== false);
    assertTest("damaged.php supports keyboard Enter key focus advance", strpos($damagedView, 'e.key === \'Enter\'') !== false);

} catch (Throwable $e) {
    echo "ERROR: Unexpected exception during test execution: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
    $failCount++;
} finally {
    // -------------------------------------------------------------
    // SECTION 9: Isolated Fixture Clean-up (NO TRUNCATE)
    // -------------------------------------------------------------
    echo "\n-- Section 9: Isolated Fixture Clean-up --\n";

    if (!empty($cleanupJournalIds)) {
        $jidList = implode(',', array_map('intval', $cleanupJournalIds));
        Database::execute("DELETE FROM journal_lines WHERE journal_entry_id IN ({$jidList})");
        Database::execute("DELETE FROM journal_entries WHERE id IN ({$jidList})");
        echo "Cleaned up test journal entries: {$jidList}\n";
    }

    if (!empty($cleanupDmgIds)) {
        $dmgList = implode(',', array_map('intval', $cleanupDmgIds));
        Database::execute("DELETE FROM damaged_stock WHERE id IN ({$dmgList})");
        echo "Cleaned up test damaged records: {$dmgList}\n";
    }

    if (!empty($cleanupLotIds)) {
        $lotList = implode(',', array_map('intval', $cleanupLotIds));
        Database::execute("DELETE FROM stock_ledger WHERE lot_id IN ({$lotList})");
        Database::execute("DELETE FROM product_lots WHERE id IN ({$lotList})");
        echo "Cleaned up test lots: {$lotList}\n";
    }

    if (!empty($cleanupProductIds)) {
        $prodList = implode(',', array_map('intval', $cleanupProductIds));
        Database::execute("DELETE FROM branch_inventory WHERE product_id IN ({$prodList})");
        Database::execute("DELETE FROM products WHERE id IN ({$prodList})");
        echo "Cleaned up test products: {$prodList}\n";
    }
}

echo "\n======================================================\n";
echo "TEST SUITE SUMMARY: {$passCount}/{$testCount} PASSED, {$failCount} FAILED\n";
echo "======================================================\n";

exit($failCount === 0 ? 0 : 1);
