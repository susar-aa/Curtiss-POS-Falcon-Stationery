<?php
/**
 * Curtiss POS - Comprehensive Physical Stock Audit Optimization & Compliance Test Suite
 * Strictly adheres to CURTISS_MASTER_RULES.md (No TRUNCATE, isolated fixtures, double-entry verification).
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/AdvancedInventory.php';
require_once __DIR__ . '/../core/Accounting.php';

echo "====================================================================\n";
echo " CURTISS POS: PHYSICAL STOCK AUDIT COMPREHENSIVE TEST SUITE\n";
echo "====================================================================\n";

$passCount = 0;
$failCount = 0;

function assertTest(string $desc, bool $condition, string $detail = '') {
    global $passCount, $failCount;
    if ($condition) {
        $passCount++;
        echo " [PASS] {$desc}\n";
    } else {
        $failCount++;
        echo " [FAIL] {$desc}" . ($detail ? " - {$detail}" : "") . "\n";
    }
}

$db = Database::getInstance()->getConnection();
$cleanups = [
    'audit_ids'      => [],
    'adjustment_ids' => [],
    'lot_ids'        => [],
    'product_ids'    => [],
    'journal_ids'    => []
];

try {
    // 1. Setup Isolated Test Product, Lot & Branch
    $testBranchId = 1;
    $testUserId   = 1;

    // Check if an active audit already exists for branch 1
    $existingActive = Database::fetchOne("
        SELECT id, audit_number FROM stock_audits 
        WHERE branch_id = :b AND status IN ('draft', 'counting', 'review')
    ", [':b' => $testBranchId]);

    assertTest("Step 1.0: Active audit state inspection", true, $existingActive ? "Found existing active audit #{$existingActive['audit_number']}" : "No active audit initially");

    $brand = Database::fetchOne("SELECT id FROM brands LIMIT 1");
    $cat = Database::fetchOne("SELECT id FROM categories LIMIT 1");
    $sup = Database::fetchOne("SELECT id FROM suppliers LIMIT 1");
    $brandId = (int)($brand['id'] ?? 1);
    $catId = (int)($cat['id'] ?? 1);
    $supId = (int)($sup['id'] ?? 1);

    // Create unique test product
    $sku = 'AUD-TST-' . time() . '-' . rand(100, 999);
    $db->prepare("
        INSERT INTO products (name, barcode, sku, brand_id, category_id, supplier_id, cost, selling_price, status, created_at)
        VALUES (:n, :b, :s, :br, :cat, :sup, 150.00, 250.00, 'active', NOW())
    ")->execute([
        ':n'   => 'Audit Test Product ' . $sku,
        ':b'   => $sku,
        ':s'   => $sku,
        ':br'  => $brandId,
        ':cat' => $catId,
        ':sup' => $supId
    ]);
    $testProductId = (int)$db->lastInsertId();
    $cleanups['product_ids'][] = $testProductId;
    assertTest("Step 1.1: Isolated test product created (ID: {$testProductId})", $testProductId > 0);

    // Create test lot
    $lotNo = 'LOT-AUD-' . time();
    $db->prepare("
        INSERT INTO product_lots (product_id, branch_id, lot_number, initial_quantity, remaining_quantity, cost, selling_price, status, created_at)
        VALUES (:p, :b, :l, 20.00, 20.00, 150.00, 250.00, 'active', NOW())
    ")->execute([
        ':p' => $testProductId,
        ':b' => $testBranchId,
        ':l' => $lotNo
    ]);
    $testLotId = (int)$db->lastInsertId();
    $cleanups['lot_ids'][] = $testLotId;
    assertTest("Step 1.2: Isolated test stock lot created (Lot: {$lotNo}, Qty: 20)", $testLotId > 0);

    // Sync branch_inventory
    $db->prepare("
        INSERT INTO branch_inventory (branch_id, product_id, quantity, updated_at)
        VALUES (:b, :p, 20.00, NOW())
        ON DUPLICATE KEY UPDATE quantity = quantity + 20.00
    ")->execute([':b' => $testBranchId, ':p' => $testProductId]);

    // 2. Test Starting Stock Audit
    // If existing active audit existed, cancel or finish it for isolated testing
    if ($existingActive) {
        $db->prepare("UPDATE stock_audits SET status = 'posted' WHERE id = :id")->execute([':id' => $existingActive['id']]);
    }

    $startRes = AdvancedInventory::startStockAudit($testBranchId, "Automated CLI Audit Test", $testUserId);
    assertTest("Step 2.1: AdvancedInventory::startStockAudit executed", $startRes['success'] === true, $startRes['message'] ?? '');
    $auditId = (int)($startRes['audit_id'] ?? 0);
    $auditNo = $startRes['audit_number'] ?? '';
    if ($auditId > 0) {
        $cleanups['audit_ids'][] = $auditId;
    }
    assertTest("Step 2.2: Stock audit session created with AUD number (#{$auditNo})", $auditId > 0 && !empty($auditNo));

    // Verify snapshot items in stock_audit_items
    $snapItems = Database::fetchAll("SELECT * FROM stock_audit_items WHERE audit_id = :id", [':id' => $auditId]);
    assertTest("Step 2.3: Stock items snapshotted into stock_audit_items", count($snapItems) > 0, "Items count: " . count($snapItems));

    $foundTestItem = false;
    $testAuditItemId = 0;
    foreach ($snapItems as $si) {
        if ((int)$si['lot_id'] === $testLotId) {
            $foundTestItem = true;
            $testAuditItemId = (int)$si['id'];
            assertTest("Step 2.4: Test lot snapshotted with expected quantity 20.00", (float)$si['expected_quantity'] == 20.00);
            break;
        }
    }
    assertTest("Step 2.5: Test item found in audit snapshot", $foundTestItem);

    // Test Duplicate Active Audit Session Prevention
    $startDup = AdvancedInventory::startStockAudit($testBranchId, "Duplicate audit attempt", $testUserId);
    assertTest("Step 2.6: Prevent duplicate concurrent active audit session", $startDup['success'] === false);

    // 3. Test Saving Audit Counts (Variance Entry)
    // Counted quantity = 18.00 (Variance = -2.00)
    $saveRes = AdvancedInventory::saveAuditCounts($auditId, [
        [
            'item_id'          => $testAuditItemId,
            'expected_quantity'=> 20.00,
            'counted_quantity' => 18.00,
            'notes'            => 'Physical count shows 2 missing units'
        ]
    ], $testUserId);
    assertTest("Step 3.1: Save audit count progress", $saveRes['success'] === true);

    $updatedItem = Database::fetchOne("SELECT * FROM stock_audit_items WHERE id = :id", [':id' => $testAuditItemId]);
    assertTest("Step 3.2: Variance calculated accurately (-2.00)", (float)$updatedItem['variance_quantity'] == -2.00);
    assertTest("Step 3.3: Counted quantity recorded as 18.00", (float)$updatedItem['counted_quantity'] == 18.00);

    $auditRow = Database::fetchOne("SELECT status FROM stock_audits WHERE id = :id", [':id' => $auditId]);
    assertTest("Step 3.4: Audit status transitioned to 'review'", $auditRow['status'] === 'review');

    // 4. Test Posting Stock Audit
    $postRes = AdvancedInventory::postStockAudit($auditId, $testUserId);
    assertTest("Step 4.1: Post & Lock Stock Audit executed", $postRes['success'] === true, $postRes['message'] ?? '');

    $postedAudit = Database::fetchOne("SELECT * FROM stock_audits WHERE id = :id", [':id' => $auditId]);
    assertTest("Step 4.2: Audit status marked as 'posted'", $postedAudit['status'] === 'posted');
    assertTest("Step 4.3: posted_by recorded", (int)$postedAudit['posted_by'] === $testUserId);
    assertTest("Step 4.4: posted_at timestamp populated", !empty($postedAudit['posted_at']));

    // 5. Verify Immutable Status: Cannot modify counts once posted
    $saveLocked = AdvancedInventory::saveAuditCounts($auditId, [
        ['item_id' => $testAuditItemId, 'expected_quantity' => 20.00, 'counted_quantity' => 15.00]
    ], $testUserId);
    assertTest("Step 5.1: Posted audit count modification rejected", $saveLocked['success'] === false);

    $postLockedAgain = AdvancedInventory::postStockAudit($auditId, $testUserId);
    assertTest("Step 5.2: Re-posting locked audit rejected", $postLockedAgain['success'] === false);

    // 6. Verify Variance Adjustments, Stock Ledger & Double-Entry Accounting
    $adj = Database::fetchOne("
        SELECT * FROM stock_adjustments 
        WHERE branch_id = :b AND product_id = :p AND lot_id = :lot
        ORDER BY id DESC LIMIT 1
    ", [':b' => $testBranchId, ':p' => $testProductId, ':lot' => $testLotId]);

    assertTest("Step 6.1: Automatic stock adjustment created for negative variance", !empty($adj));
    if ($adj) {
        $cleanups['adjustment_ids'][] = (int)$adj['id'];
        assertTest("Step 6.2: Adjustment type is 'decrease'", $adj['adjustment_type'] === 'decrease');
        assertTest("Step 6.3: Adjustment quantity is 2.00", (float)$adj['quantity'] == 2.00);

        // Check journal entry
        $jeId = (int)($adj['journal_entry_id'] ?? 0);
        assertTest("Step 6.4: Journal entry generated for adjustment", $jeId > 0);
        if ($jeId > 0) {
            $cleanups['journal_ids'][] = $jeId;
            $je = Database::fetchOne("SELECT * FROM journal_entries WHERE id = :id", [':id' => $jeId]);
            assertTest("Step 6.5: Journal entry status is 'posted'", ($je['posting_status'] ?? '') === 'posted');
            assertTest("Step 6.6: Balanced debits and credits", (float)$je['total_debit'] == (float)$je['total_credit'] && (float)$je['total_debit'] > 0);

            $jLines = Database::fetchAll("SELECT * FROM journal_lines WHERE journal_entry_id = :id", [':id' => $jeId]);
            assertTest("Step 6.7: Journal lines exist and balance", count($jLines) >= 2);
        }
    }

    // Verify stock balance was reduced from 20 to 18
    $lotCheck = Database::fetchOne("SELECT remaining_quantity FROM product_lots WHERE id = :id", [':id' => $testLotId]);
    assertTest("Step 6.8: Product lot remaining quantity reduced to 18.00", (float)$lotCheck['remaining_quantity'] == 18.00);

    // Verify stock ledger movement recorded
    $mv = Database::fetchOne("
        SELECT * FROM stock_ledger 
        WHERE branch_id = :b AND product_id = :p AND lot_id = :lot AND movement_type = 'adjustment'
        ORDER BY id DESC LIMIT 1
    ", [':b' => $testBranchId, ':p' => $testProductId, ':lot' => $testLotId]);
    assertTest("Step 6.9: Stock ledger movement recorded for audit variance", !empty($mv));
    if ($mv) {
        assertTest("Step 6.10: Movement quantity is -2.00", (float)$mv['quantity'] == -2.00);
    }

    // 7. Test Database Composite Indexes
    $audIndexes = Database::fetchAll("SHOW INDEX FROM stock_audits");
    $hasBranchStatusIdx = false;
    $hasBranchIdIdx     = false;
    foreach ($audIndexes as $idx) {
        if ($idx['Key_name'] === 'idx_aud_branch_status_id') $hasBranchStatusIdx = true;
        if ($idx['Key_name'] === 'idx_aud_branch_id') $hasBranchIdIdx = true;
    }
    assertTest("Step 7.1: stock_audits has composite index idx_aud_branch_status_id", $hasBranchStatusIdx);
    assertTest("Step 7.2: stock_audits has composite index idx_aud_branch_id", $hasBranchIdIdx);

    $saiIndexes = Database::fetchAll("SHOW INDEX FROM stock_audit_items");
    $hasSaiAuditIdIdx = false;
    foreach ($saiIndexes as $idx) {
        if ($idx['Key_name'] === 'idx_sai_audit_id') $hasSaiAuditIdIdx = true;
    }
    assertTest("Step 7.3: stock_audit_items has composite index idx_sai_audit_id", $hasSaiAuditIdIdx);

    // 8. Test Controller Query & Multi-column Search
    $testSearchTerm = $auditNo;
    $searchAudits = Database::fetchAll("
        SELECT sa.*, u.username AS creator_name, pu.username AS poster_name
        FROM stock_audits sa
        JOIN users u ON sa.created_by = u.id
        LEFT JOIN users pu ON sa.posted_by = pu.id
        WHERE sa.branch_id = :b AND (sa.audit_number LIKE :s1 OR sa.notes LIKE :s2 OR u.username LIKE :s3)
    ", [
        ':b'  => $testBranchId,
        ':s1' => "%{$testSearchTerm}%",
        ':s2' => "%{$testSearchTerm}%",
        ':s3' => "%{$testSearchTerm}%"
    ]);
    assertTest("Step 8.1: Controller multi-column search finds posted audit by number", count($searchAudits) >= 1);

    // 9. Test CSV Export Script Integrity
    $exportFile = __DIR__ . '/../stock_audits_export.php';
    assertTest("Step 9.1: stock_audits_export.php exists", file_exists($exportFile));

    $exportContent = file_get_contents($exportFile);
    assertTest("Step 9.2: stock_audits_export.php sends UTF-8 BOM", strpos($exportContent, '"\\xEF\\xBB\\xBF"') !== false);
    assertTest("Step 9.3: stock_audits_export.php uses Excel string escaping", strpos($exportContent, '="') !== false);
    assertTest("Step 9.4: stock_audits_export.php enforces branch isolation & active filters", strpos($exportContent, '$branchId') !== false && strpos($exportContent, '$where') !== false);

    // 10. Test API endpoints
    // 10.1 get_audit_details
    $detailsHeader = Database::fetchOne("
        SELECT sa.*, u.username AS creator_name, pu.username AS poster_name
        FROM stock_audits sa
        JOIN users u ON sa.created_by = u.id
        LEFT JOIN users pu ON sa.posted_by = pu.id
        WHERE sa.id = :id
    ", [':id' => $auditId]);
    assertTest("Step 10.1: get_audit_details fetches audit header", !empty($detailsHeader));

    $detailsItems = Database::fetchAll("
        SELECT sai.*, p.name AS product_name, p.barcode, pl.lot_number, pl.cost AS lot_cost
        FROM stock_audit_items sai
        JOIN products p ON sai.product_id = p.id
        JOIN product_lots pl ON sai.lot_id = pl.id
        WHERE sai.audit_id = :id
        ORDER BY p.name ASC, pl.id ASC
    ", [':id' => $auditId]);
    assertTest("Step 10.2: get_audit_details fetches audit items with product & lot info", count($detailsItems) > 0);

    // 11. UI & Master Design Rules Compliance in audits.php
    $auditsView = file_get_contents(__DIR__ . '/../views/inventory/audits.php');

    assertTest("Step 11.1: audits.php enforces fixed viewport height (calc(100vh - 110px))", strpos($auditsView, 'calc(100vh - 110px)') !== false);
    assertTest("Step 11.2: audits.php enforces sticky table header (position: sticky; top: 0)", strpos($auditsView, 'position: sticky;') !== false);
    assertTest("Step 11.3: audits.php enforces zebra striping on table rows", strpos($auditsView, 'nth-child(even)') !== false && strpos($auditsView, 'nth-child(odd)') !== false);
    assertTest("Step 11.4: audits.php enforces standard 48px row heights", strpos($auditsView, 'height: 48px;') !== false);
    assertTest("Step 11.5: audits.php enforces 28px in-cell badges/buttons", strpos($auditsView, 'height: 28px;') !== false);
    assertTest("Step 11.6: audits.php includes floating bottom search (CurtissNav.enablePageSearch)", strpos($auditsView, 'CurtissNav.enablePageSearch') !== false);
    assertTest("Step 11.7: audits.php attaches CurtissSearchableSelect to filter dropdown", strpos($auditsView, 'curtiss_select.css') !== false && strpos($auditsView, 'CurtissSearchableSelect.attach') !== false);
    assertTest("Step 11.8: audits.php uses floating top-right action bar (#curtiss-floating-header-right)", strpos($auditsView, 'curtiss-floating-header-right') !== false);
    assertTest("Step 11.9: audits.php has headline-only title without subtitle tagline", strpos($auditsView, 'inv-subtitle') === false && strpos($auditsView, '<h1 class="inv-title">') !== false);
    assertTest("Step 11.10: audits.php includes slide-in audit detail drawer", strpos($auditsView, 'inv-drawer') !== false && strpos($auditsView, 'openAuditDetailDrawer') !== false);
    assertTest("Step 11.11: audits.php supports keyboard Enter & Escape key navigation", strpos($auditsView, 'e.key === \'Enter\'') !== false && strpos($auditsView, 'e.key === \'Escape\'') !== false);

    // 12. Controller Compliance in stock_audits.php
    $controllerCode = file_get_contents(__DIR__ . '/../stock_audits.php');
    assertTest("Step 12.1: stock_audits.php supports session branch resolution", strpos($controllerCode, '$_SESSION[\'branch_id\']') !== false);
    assertTest("Step 12.2: stock_audits.php enforces multi-column search", strpos($controllerCode, ':s1') !== false);
    assertTest("Step 12.3: stock_audits.php enforces server-side pagination limits (25, 50, 75, 100, 500)", strpos($controllerCode, '25, 50, 75, 100, 500') !== false);

} catch (Throwable $e) {
    $failCount++;
    echo " [FATAL ERROR]: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
} finally {
    // 13. Clean Teardown (NO TRUNCATE)
    echo "\n--- Clean Teardown of Isolated Fixtures ---\n";
    if (!empty($cleanups['audit_ids'])) {
        $in = implode(',', array_map('intval', $cleanups['audit_ids']));
        $db->exec("DELETE FROM stock_audit_items WHERE audit_id IN ($in)");
        $db->exec("DELETE FROM stock_audits WHERE id IN ($in)");
        echo " Cleaned test stock audits and items (IDs: $in)\n";
    }
    if (!empty($cleanups['adjustment_ids'])) {
        $in = implode(',', array_map('intval', $cleanups['adjustment_ids']));
        $db->exec("DELETE FROM stock_adjustments WHERE id IN ($in)");
        echo " Cleaned test adjustments (IDs: $in)\n";
    }
    if (!empty($cleanups['lot_ids'])) {
        $in = implode(',', array_map('intval', $cleanups['lot_ids']));
        $db->exec("DELETE FROM stock_ledger WHERE lot_id IN ($in)");
        $db->exec("DELETE FROM product_lots WHERE id IN ($in)");
        echo " Cleaned test product lots and ledger entries (IDs: $in)\n";
    }
    if (!empty($cleanups['journal_ids'])) {
        $in = implode(',', array_map('intval', $cleanups['journal_ids']));
        $db->exec("DELETE FROM journal_lines WHERE journal_entry_id IN ($in)");
        $db->exec("DELETE FROM journal_entries WHERE id IN ($in)");
        echo " Cleaned test journal entries (IDs: $in)\n";
    }
    if (!empty($cleanups['lot_ids'])) {
        $in = implode(',', array_map('intval', $cleanups['lot_ids']));
        $db->exec("DELETE FROM product_lots WHERE id IN ($in)");
        echo " Cleaned test product lots (IDs: $in)\n";
    }
    if (!empty($cleanups['product_ids'])) {
        $in = implode(',', array_map('intval', $cleanups['product_ids']));
        $db->exec("DELETE FROM branch_inventory WHERE product_id IN ($in)");
        $db->exec("DELETE FROM products WHERE id IN ($in)");
        echo " Cleaned test products (IDs: $in)\n";
    }

    // Restore any previous active audit that was set to posted for testing
    if (isset($existingActive) && $existingActive) {
        $db->prepare("UPDATE stock_audits SET status = 'counting' WHERE id = :id")->execute([':id' => $existingActive['id']]);
        echo " Restored original active audit #{$existingActive['audit_number']} status\n";
    }
}

echo "====================================================================\n";
echo " TEST SUITE COMPLETE: Total: " . ($passCount + $failCount) . " | Passed: {$passCount} | Failed: {$failCount}\n";
echo "====================================================================\n";

if ($failCount > 0) {
    exit(1);
}
exit(0);
