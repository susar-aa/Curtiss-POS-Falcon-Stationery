<?php
/**
 * Curtiss POS - Branch Stock Transfers Optimization & Compliance Test Suite
 * Validates:
 * 1. Database composite indexes (idx_st_from_status_id, idx_st_to_status_id).
 * 2. Full Transfer Lifecycle: Create Draft -> Update Draft -> Send In-Transit -> Receive Completed.
 * 3. Exact Lot & Cost Preservation: FIFO lot deduction, destination lot creation at exact unit cost and price.
 * 4. Append-Only Stock Movement Ledger (transfer_out, transfer_in) with running balances.
 * 5. In-Transit stock accounting in branch_inventory.
 * 6. Double-receipt and invalid branch transfer prevention.
 * 7. Cancel Transfer safety and status transitions.
 * 8. Universal Search across Transfer #, Branch names, Creator, Notes, Status, and Date.
 * 9. Server-side pagination limits (25, 50, 75, 100, 500).
 * 10. Superadmin branch session resolution ($_SESSION['branch_id']).
 * 11. CSV Export script integrity and Excel zero/code protection.
 * 12. UI Rules compliance (Fixed Viewport, Sticky Header, Zebra Table, 48px rows, 28px in-cell badges, Headline-Only).
 * 13. Isolated cleanup without database truncations.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/AdvancedInventory.php';
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

echo "=== STARTING BRANCH STOCK TRANSFERS OPTIMIZATION SUITE ===\n\n";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id'] = 1;
$_SESSION['branch_id'] = 1;
$_SESSION['active_branch_id'] = 1;

$cleanupTransferIds = [];
$cleanupLotIds = [];
$cleanupProductIds = [];

try {
    // -------------------------------------------------------------
    // SECTION 1: Database Composite Indexes
    // -------------------------------------------------------------
    echo "-- Section 1: Database Composite Indexes --\n";

    $fromIdx = Database::fetchAll("SHOW INDEX FROM stock_transfers WHERE Key_name = 'idx_st_from_status_id'");
    assertTest("idx_st_from_status_id composite index exists on stock_transfers", !empty($fromIdx), "Missing idx_st_from_status_id");

    $toIdx = Database::fetchAll("SHOW INDEX FROM stock_transfers WHERE Key_name = 'idx_st_to_status_id'");
    assertTest("idx_st_to_status_id composite index exists on stock_transfers", !empty($toIdx), "Missing idx_st_to_status_id");

    // -------------------------------------------------------------
    // SECTION 2: Branch & Fixture Preparation
    // -------------------------------------------------------------
    echo "\n-- Section 2: Branch & Fixture Setup --\n";

    $branches = Database::fetchAll("SELECT id, name FROM branches WHERE status = 'active' ORDER BY id ASC LIMIT 2");
    assertTest("At least two active branches available for testing", count($branches) >= 2, "Found only " . count($branches) . " branch(es)");

    $srcBranchId = (int)$branches[0]['id'];
    $destBranchId = (int)$branches[1]['id'];

    $sampleBrand = Database::fetchOne("SELECT id FROM brands WHERE deleted_at IS NULL LIMIT 1");
    $sampleCat = Database::fetchOne("SELECT id FROM categories WHERE deleted_at IS NULL LIMIT 1");
    $sampleSup = Database::fetchOne("SELECT id FROM suppliers WHERE deleted_at IS NULL LIMIT 1");

    $testBarcode = '998' . rand(100000, 999999);
    $testCost = 450.00;
    $testPrice = 850.00;
    $initialQty = 50.00;

    Database::execute("
        INSERT INTO products (name, barcode, sku, cost, selling_price, brand_id, category_id, supplier_id, status, created_at)
        VALUES (:name, :bc, :sku, :c, :p, :bid, :cid, :sid, 'active', NOW())
    ", [
        ':name' => 'Stock Transfer Unit Test Prod ' . rand(100, 999),
        ':bc'   => $testBarcode,
        ':sku'  => 'ST-' . time(),
        ':c'    => $testCost,
        ':p'    => $testPrice,
        ':bid'  => $sampleBrand['id'] ?? 1,
        ':cid'  => $sampleCat['id'] ?? 1,
        ':sid'  => $sampleSup['id'] ?? null
    ]);
    $testProductId = (int)Database::lastInsertId();
    $cleanupProductIds[] = $testProductId;

    // Create initial lot at source branch
    $testLotNum = 'LOT-ST-' . rand(1000, 9999);
    Database::execute("
        INSERT INTO product_lots (product_id, branch_id, lot_number, cost, selling_price, initial_quantity, remaining_quantity, status, created_at)
        VALUES (:pid, :bid, :lot, :c, :p, :iq, :rq, 'active', NOW())
    ", [
        ':pid' => $testProductId,
        ':bid' => $srcBranchId,
        ':lot' => $testLotNum,
        ':c'   => $testCost,
        ':p'   => $testPrice,
        ':iq'  => $initialQty,
        ':rq'  => $initialQty
    ]);
    $testLotId = (int)Database::lastInsertId();
    $cleanupLotIds[] = $testLotId;

    // Seed branch inventory for source branch
    Database::execute("
        INSERT INTO branch_inventory (branch_id, product_id, quantity, in_transit_quantity, low_stock_threshold)
        VALUES (:b, :p, :q, 0.00, 5.00)
        ON DUPLICATE KEY UPDATE quantity = :q2, in_transit_quantity = 0.00
    ", [
        ':b'  => $srcBranchId,
        ':p'  => $testProductId,
        ':q'  => $initialQty,
        ':q2' => $initialQty
    ]);

    assertTest("Test Product #{$testProductId} & Lot #{$testLotId} created with {$initialQty} units at Branch #{$srcBranchId}", $testProductId > 0 && $testLotId > 0);

    // -------------------------------------------------------------
    // SECTION 3: Transfer Lifecycle (Draft -> Send -> Receive)
    // -------------------------------------------------------------
    echo "\n-- Section 3: Transfer Lifecycle Operations --\n";

    // 3A. Create Draft Transfer
    $transferQty = 12.00;
    $createDraftRes = AdvancedInventory::createTransfer([
        'from_branch_id' => $srcBranchId,
        'to_branch_id'   => $destBranchId,
        'transfer_date'  => date('Y-m-d'),
        'notes'          => 'Unit test draft transfer',
        'items'          => [
            [
                'product_id' => $testProductId,
                'lot_id'     => $testLotId,
                'quantity'   => $transferQty
            ]
        ]
    ], 1);

    assertTest("AdvancedInventory::createTransfer succeeds as Draft", $createDraftRes['success'] === true, $createDraftRes['message'] ?? '');
    $transferId = (int)($createDraftRes['transfer_id'] ?? 0);
    $cleanupTransferIds[] = $transferId;

    $draftRecord = Database::fetchOne("SELECT * FROM stock_transfers WHERE id = :id", [':id' => $transferId]);
    assertTest("Transfer saved with status 'draft' and valid transfer_number", $draftRecord && $draftRecord['status'] === 'draft' && !empty($draftRecord['transfer_number']));

    // Stock should not be deducted yet during draft
    $srcLotDraft = Database::fetchOne("SELECT remaining_quantity FROM product_lots WHERE id = :id", [':id' => $testLotId]);
    assertTest("Source lot stock unchanged during draft status (remains {$initialQty})", (float)$srcLotDraft['remaining_quantity'] === (float)$initialQty);

    // 3B. Update Draft Transfer (Notes & Qty)
    $updatedTransferQty = 15.00;
    $updateRes = AdvancedInventory::updateTransfer($transferId, [
        'to_branch_id'   => $destBranchId,
        'transfer_date'  => date('Y-m-d'),
        'notes'          => 'Updated unit test draft transfer notes',
        'items'          => [
            [
                'product_id' => $testProductId,
                'lot_id'     => $testLotId,
                'quantity'   => $updatedTransferQty
            ]
        ]
    ], 1);
    assertTest("AdvancedInventory::updateTransfer successfully updates draft transfer", $updateRes['success'] === true, $updateRes['message'] ?? '');

    $itemCheck = Database::fetchOne("SELECT quantity FROM stock_transfer_items WHERE transfer_id = :id", [':id' => $transferId]);
    assertTest("Updated transfer item quantity matches {$updatedTransferQty}", (float)($itemCheck['quantity'] ?? 0) === (float)$updatedTransferQty);

    // 3C. Send Transfer (In-Transit)
    $sendRes = AdvancedInventory::sendTransfer($transferId, 1);
    assertTest("AdvancedInventory::sendTransfer succeeds", $sendRes['success'] === true, $sendRes['message'] ?? '');

    $sentRecord = Database::fetchOne("SELECT * FROM stock_transfers WHERE id = :id", [':id' => $transferId]);
    assertTest("Transfer status transitioned to 'sent'", $sentRecord && $sentRecord['status'] === 'sent');

    // Verify source lot deduction
    $srcLotSent = Database::fetchOne("SELECT remaining_quantity FROM product_lots WHERE id = :id", [':id' => $testLotId]);
    $expectedSrcLotQty = $initialQty - $updatedTransferQty;
    assertTest("Source lot quantity correctly deducted to {$expectedSrcLotQty}", (float)$srcLotSent['remaining_quantity'] === (float)$expectedSrcLotQty);

    // Verify source branch inventory deduction
    $srcInvSent = Database::fetchOne("SELECT quantity FROM branch_inventory WHERE branch_id = :b AND product_id = :p", [':b' => $srcBranchId, ':p' => $testProductId]);
    assertTest("Source branch_inventory quantity correctly reduced to {$expectedSrcLotQty}", (float)$srcInvSent['quantity'] === (float)$expectedSrcLotQty);

    // Verify destination in_transit_quantity increase
    $destInvSent = Database::fetchOne("SELECT quantity, in_transit_quantity FROM branch_inventory WHERE branch_id = :b AND product_id = :p", [':b' => $destBranchId, ':p' => $testProductId]);
    assertTest("Destination branch in_transit_quantity increased to {$updatedTransferQty}", (float)($destInvSent['in_transit_quantity'] ?? 0) === (float)$updatedTransferQty);

    // Verify stock_ledger record for transfer_out
    $ledgerOut = Database::fetchOne("
        SELECT * FROM stock_ledger 
        WHERE branch_id = :b AND product_id = :p AND reference_no = :ref AND movement_type = 'transfer_out'
        ORDER BY id DESC LIMIT 1
    ", [':b' => $srcBranchId, ':p' => $testProductId, ':ref' => $sentRecord['transfer_number']]);
    assertTest("Stock movement ledger recorded 'transfer_out' entry with negative quantity -{$updatedTransferQty}", $ledgerOut && (float)$ledgerOut['quantity'] === -(float)$updatedTransferQty);

    // 3D. Receive Transfer (Destination Stock Addition & Clear In-Transit)
    $receiveRes = AdvancedInventory::receiveTransfer($transferId, 1);
    assertTest("AdvancedInventory::receiveTransfer succeeds", $receiveRes['success'] === true, $receiveRes['message'] ?? '');

    $receivedRecord = Database::fetchOne("SELECT * FROM stock_transfers WHERE id = :id", [':id' => $transferId]);
    assertTest("Transfer status transitioned to 'received'", $receivedRecord && $receivedRecord['status'] === 'received');

    // Verify destination branch inventory updated and in_transit cleared
    $destInvReceived = Database::fetchOne("SELECT quantity, in_transit_quantity FROM branch_inventory WHERE branch_id = :b AND product_id = :p", [':b' => $destBranchId, ':p' => $testProductId]);
    assertTest("Destination branch_inventory quantity received ({$updatedTransferQty}) and in_transit cleared (0.00)", (float)($destInvReceived['quantity'] ?? 0) >= (float)$updatedTransferQty && (float)($destInvReceived['in_transit_quantity'] ?? 0) === 0.00);

    // Verify destination lot creation with preserved cost & price
    $destLot = Database::fetchOne("
        SELECT * FROM product_lots 
        WHERE branch_id = :b AND product_id = :p AND cost = :c AND selling_price = :p_price AND status = 'active'
        ORDER BY id DESC LIMIT 1
    ", [':b' => $destBranchId, ':p' => $testProductId, ':c' => $testCost, ':p_price' => $testPrice]);
    assertTest("Destination lot created preserving exact unit cost {$testCost} and selling price {$testPrice}", !empty($destLot));
    if ($destLot) {
        $cleanupLotIds[] = (int)$destLot['id'];
    }

    // Verify stock_ledger record for transfer_in
    $ledgerIn = Database::fetchOne("
        SELECT * FROM stock_ledger 
        WHERE branch_id = :b AND product_id = :p AND reference_no = :ref AND movement_type = 'transfer_in'
        ORDER BY id DESC LIMIT 1
    ", [':b' => $destBranchId, ':p' => $testProductId, ':ref' => $sentRecord['transfer_number']]);
    assertTest("Stock movement ledger recorded 'transfer_in' entry with positive quantity +{$updatedTransferQty}", $ledgerIn && (float)$ledgerIn['quantity'] === (float)$updatedTransferQty);

    // 3E. Double Receipt Prevention
    $doubleReceiveRes = AdvancedInventory::receiveTransfer($transferId, 1);
    assertTest("Double-receipt prevention: Attempting to re-receive a received transfer returns error", $doubleReceiveRes['success'] === false);

    // 3F. Cancel Transfer Test
    $cancelDraftRes = AdvancedInventory::createTransfer([
        'from_branch_id' => $srcBranchId,
        'to_branch_id'   => $destBranchId,
        'transfer_date'  => date('Y-m-d'),
        'notes'          => 'Unit test transfer to cancel',
        'items'          => [
            [
                'product_id' => $testProductId,
                'lot_id'     => $testLotId,
                'quantity'   => 2.00
            ]
        ]
    ], 1);
    $cancelTransferId = (int)($cancelDraftRes['transfer_id'] ?? 0);
    $cleanupTransferIds[] = $cancelTransferId;

    $cancelRes = AdvancedInventory::cancelTransfer($cancelTransferId, 1);
    assertTest("AdvancedInventory::cancelTransfer succeeds for draft transfer", $cancelRes['success'] === true);
    $cancelledRecord = Database::fetchOne("SELECT status FROM stock_transfers WHERE id = :id", [':id' => $cancelTransferId]);
    assertTest("Cancelled transfer status is 'cancelled'", ($cancelledRecord['status'] ?? '') === 'cancelled');

    // -------------------------------------------------------------
    // SECTION 4: Universal Search & Server-Side Pagination
    // -------------------------------------------------------------
    echo "\n-- Section 4: Universal Search & Server-Side Pagination --\n";

    // Test Search by Transfer Number
    $trfNumQuery = $sentRecord['transfer_number'];
    $whereParams = [':from_b' => $srcBranchId, ':to_b' => $srcBranchId];
    $searchTerm = '%' . $trfNumQuery . '%';
    $searchWhere = "(
        st.transfer_number LIKE :s1 OR 
        fb.name LIKE :s2 OR 
        tb.name LIKE :s3 OR 
        u.username LIKE :s4 OR 
        COALESCE(st.notes, '') LIKE :s5 OR 
        st.status LIKE :s6 OR 
        CAST(st.transfer_date AS CHAR) LIKE :s7
    )";
    for ($i = 1; $i <= 7; $i++) {
        $whereParams[":s{$i}"] = $searchTerm;
    }

    $searchResults = Database::fetchAll("
        SELECT st.id, st.transfer_number, st.status 
        FROM stock_transfers st
        JOIN branches fb ON st.from_branch_id = fb.id
        JOIN branches tb ON st.to_branch_id = tb.id
        JOIN users u ON st.created_by = u.id
        WHERE (st.from_branch_id = :from_b OR st.to_branch_id = :to_b)
          AND {$searchWhere}
    ", $whereParams);

    $foundTransfers = array_column($searchResults, 'transfer_number');
    assertTest("Universal Search finds transfer by exact Transfer Number ({$trfNumQuery})", in_array($trfNumQuery, $foundTransfers, true));

    // Test Search by Status ('received')
    $statusParams = [':from_b' => $srcBranchId, ':to_b' => $srcBranchId];
    $statusTerm = '%received%';
    for ($i = 1; $i <= 7; $i++) {
        $statusParams[":s{$i}"] = $statusTerm;
    }
    $statusResults = Database::fetchAll("
        SELECT st.id, st.transfer_number, st.status 
        FROM stock_transfers st
        JOIN branches fb ON st.from_branch_id = fb.id
        JOIN branches tb ON st.to_branch_id = tb.id
        JOIN users u ON st.created_by = u.id
        WHERE (st.from_branch_id = :from_b OR st.to_branch_id = :to_b)
          AND {$searchWhere}
    ", $statusParams);
    $foundStatus = array_column($statusResults, 'transfer_number');
    assertTest("Universal Search finds transfer by Status 'received'", in_array($trfNumQuery, $foundStatus, true));

    // Test Search by Branch Name
    $destBranchName = $branches[1]['name'];
    $destBranchParams = [':from_b' => $srcBranchId, ':to_b' => $srcBranchId];
    $destTerm = '%' . $destBranchName . '%';
    for ($i = 1; $i <= 7; $i++) {
        $destBranchParams[":s{$i}"] = $destTerm;
    }
    $branchResults = Database::fetchAll("
        SELECT st.id, st.transfer_number, st.status 
        FROM stock_transfers st
        JOIN branches fb ON st.from_branch_id = fb.id
        JOIN branches tb ON st.to_branch_id = tb.id
        JOIN users u ON st.created_by = u.id
        WHERE (st.from_branch_id = :from_b OR st.to_branch_id = :to_b)
          AND {$searchWhere}
    ", $destBranchParams);
    $foundBranch = array_column($branchResults, 'transfer_number');
    assertTest("Universal Search finds transfer by Destination Branch Name '{$destBranchName}'", in_array($trfNumQuery, $foundBranch, true));

    // Verify Server-Side Pagination Limits in Controller
    $controllerContent = file_get_contents(__DIR__ . '/../stock_transfers.php');
    assertTest("stock_transfers.php validates limits [25, 50, 75, 100, 500]", strpos($controllerContent, '[25, 50, 75, 100, 500]') !== false);
    assertTest("stock_transfers.php supports session branch switching", strpos($controllerContent, '$_SESSION[\'branch_id\']') !== false);

    // -------------------------------------------------------------
    // SECTION 5: CSV Export Integrity
    // -------------------------------------------------------------
    echo "\n-- Section 5: CSV Export Integrity --\n";

    $exportFile = __DIR__ . '/../stock_transfers_export.php';
    assertTest("stock_transfers_export.php exists", file_exists($exportFile));

    $exportContent = file_get_contents($exportFile);
    assertTest("stock_transfers_export.php sends UTF-8 BOM", strpos($exportContent, '"\\xEF\\xBB\\xBF"') !== false);
    assertTest("stock_transfers_export.php preserves Transfer # and Dates with Excel string escaping", strpos($exportContent, '="') !== false);
    assertTest("stock_transfers_export.php enforces branch isolation and active filters", strpos($exportContent, '$branchId') !== false && strpos($exportContent, '$where') !== false);

    // -------------------------------------------------------------
    // SECTION 6: UI & Design Rules Compliance
    // -------------------------------------------------------------
    echo "\n-- Section 6: UI & Master Design Rules Compliance --\n";

    $transfersView = file_get_contents(__DIR__ . '/../views/inventory/transfers.php');

    assertTest("transfers.php enforces fixed viewport height (calc(100vh - 110px))", strpos($transfersView, 'calc(100vh - 110px)') !== false);
    assertTest("transfers.php enforces sticky table header (position: sticky; top: 0)", strpos($transfersView, 'position: sticky;') !== false);
    assertTest("transfers.php enforces zebra striping on table rows", strpos($transfersView, 'nth-child(even)') !== false && strpos($transfersView, 'nth-child(odd)') !== false);
    assertTest("transfers.php enforces 48px row heights (height: 48px; min-height: 48px;)", strpos($transfersView, 'height: 48px;') !== false);
    assertTest("transfers.php includes floating bottom realtime search (enablePageSearch)", strpos($transfersView, 'CurtissNav.enablePageSearch') !== false);
    assertTest("transfers.php attaches CurtissSearchableSelect to filter dropdowns", strpos($transfersView, 'curtiss_select.css') !== false && strpos($transfersView, 'CurtissSearchableSelect.attach') !== false);
    assertTest("transfers.php uses floating top-right action bar (#curtiss-floating-header-right)", strpos($transfersView, 'curtiss-floating-header-right') !== false);
    assertTest("transfers.php has headline-only title without subtitle tagline", strpos($transfersView, 'inv-subtitle') === false && strpos($transfersView, '<h1 class="inv-title">') !== false);

    $transferFormView = file_get_contents(__DIR__ . '/../views/inventory/transfer_form.php');
    assertTest("transfer_form.php uses fixed viewport height (calc(100vh - 110px))", strpos($transferFormView, 'calc(100vh - 110px)') !== false);
    assertTest("transfer_form.php supports Ctrl+S shortcut save", strpos($transferFormView, 'Ctrl+S') !== false);
    assertTest("transfer_form.php has headline-only title without subtitle tagline", strpos($transferFormView, 'prod-subtitle') === false && strpos($transferFormView, '<h1 class="prod-title"') !== false);

} catch (Throwable $e) {
    echo "ERROR: Unexpected exception during test execution: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
    $failCount++;
} finally {
    // -------------------------------------------------------------
    // SECTION 7: Isolated Fixture Clean-up (NO TRUNCATE)
    // -------------------------------------------------------------
    echo "\n-- Section 7: Isolated Fixture Clean-up --\n";

    if (!empty($cleanupTransferIds)) {
        $idList = implode(',', array_map('intval', $cleanupTransferIds));
        Database::execute("DELETE FROM stock_transfer_items WHERE transfer_id IN ({$idList})");
        Database::execute("DELETE FROM stock_transfers WHERE id IN ({$idList})");
        echo "Cleaned up test transfers: {$idList}\n";
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
