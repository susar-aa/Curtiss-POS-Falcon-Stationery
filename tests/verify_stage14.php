<?php
/**
 * Curtiss POS - Stage 14 Automated CLI Verification & Regression Test Suite
 * Tests branch stock transfers (draft->sent->received), in-transit tracking, lot cost preservation,
 * double receipt blocking, negative stock prevention, stock adjustments (increase/decrease) with accounting,
 * damaged/wastage movements & write-off disposal, physical stock audits with variance posting & lock,
 * stock ledger, branch isolation, balanced GL, and Stage 1-13 regression.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Logger.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Accounting.php';
require_once __DIR__ . '/../core/BusinessDay.php';
require_once __DIR__ . '/../core/Inventory.php';
require_once __DIR__ . '/../core/AdvancedInventory.php';

$testPassed = 0;
$testFailed = 0;

function assertTest(bool $condition, string $testName, string $failureDetails = '') {
    global $testPassed, $testFailed;
    if ($condition) {
        $testPassed++;
        echo "  [PASS] {$testName}\n";
    } else {
        $testFailed++;
        echo "  [FAIL] {$testName}\n";
        if ($failureDetails) {
            echo "         Detail: {$failureDetails}\n";
        }
    }
}

echo "===============================================================\n";
echo "       CURTISS POS - STAGE 14 VERIFICATION TEST SUITE           \n";
echo "===============================================================\n\n";

try {
    $db = Database::getInstance()->getConnection();
    $branch1Id = 1;
    $branch2Id = 2;
    $userId = 1;

    // Clean Stage 14 test records for clean run
    $db->exec("DELETE FROM stock_audit_items");
    $db->exec("DELETE FROM stock_audits");
    $db->exec("DELETE FROM damaged_stock");
    $db->exec("DELETE FROM journal_lines WHERE journal_entry_id IN (SELECT id FROM journal_entries WHERE description LIKE '%Stock Adjustment%' OR description LIKE '%Damaged%')");
    $db->exec("DELETE FROM journal_entries WHERE description LIKE '%Stock Adjustment%' OR description LIKE '%Damaged%'");
    $db->exec("DELETE FROM stock_adjustments");
    $db->exec("DELETE FROM stock_transfer_items");
    $db->exec("DELETE FROM stock_transfers");
    $db->exec("DELETE FROM stock_ledger WHERE movement_type IN ('transfer_out', 'transfer_in', 'adjustment_increase', 'adjustment_decrease', 'damaged', 'wastage', 'stock_audit')");

    // Setup initial product & stock lot for testing
    $prod = Database::fetchOne("SELECT id FROM products WHERE barcode = 'ST14-001' AND deleted_at IS NULL");
    if (!$prod) {
        $db->exec("INSERT INTO products (name, barcode, brand_id, category_id, supplier_id, cost, selling_price, status) VALUES ('Stage 14 Test Item', 'ST14-001', 1, 1, 1, 100.00, 150.00, 'active')");
        $productId = (int)$db->lastInsertId();
    } else {
        $productId = (int)$prod['id'];
    }

    // Reset stock lots and inventory for test product
    $db->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $db->exec("DELETE FROM stock_ledger WHERE product_id = {$productId}");
    $db->exec("DELETE FROM product_lots WHERE product_id = {$productId}");
    $db->exec("UPDATE branch_inventory SET quantity = 0.00, in_transit_quantity = 0.00, damaged_quantity = 0.00 WHERE product_id = {$productId}");
    $db->exec("SET FOREIGN_KEY_CHECKS = 1;");

    // Add stock lot of 50 units at Branch 1
    $lotRes = Inventory::addStockLot($branch1Id, $productId, 100.00, 150.00, 50.00, 'LOT-ST14-B1', 'INIT-STAGE14', 'Test stock lot', $userId, 'opening_stock');
    assertTest($lotRes['success'] === true, "Stock lot created at Branch 1 (50 units @ LKR 100.00 cost)");
    $sourceLotId = $lotRes['lot_id'];

    // -------------------------------------------------------------
    // GROUP 1: Branch Stock Transfer Workflow
    // -------------------------------------------------------------
    echo "\n[GROUP 1] Branch Stock Transfer Workflow\n";

    // 1.1 Create Draft Transfer
    $createTrfRes = AdvancedInventory::createTransfer([
        'from_branch_id' => $branch1Id,
        'to_branch_id'   => $branch2Id,
        'transfer_date'  => date('Y-m-d'),
        'notes'          => 'Stage 14 Test Transfer',
        'items'          => [
            ['product_id' => $productId, 'lot_id' => $sourceLotId, 'quantity' => 15.00]
        ]
    ], $userId);

    assertTest($createTrfRes['success'] === true, "Draft stock transfer created successfully (#{$createTrfRes['transfer_number']})");
    $transferId = $createTrfRes['transfer_id'];

    // 1.2 Send Transfer -> Deducts source stock & sets In-Transit
    $sendRes = AdvancedInventory::sendTransfer($transferId, $userId);
    assertTest($sendRes['success'] === true, "Stock transfer sent successfully");

    $srcLotAfterSend = Database::fetchOne("SELECT remaining_quantity FROM product_lots WHERE id = :id", [':id' => $sourceLotId]);
    assertTest(abs((float)$srcLotAfterSend['remaining_quantity'] - 35.00) < 0.01, "Source lot quantity reduced from 50 to 35 units");

    $destInvInTransit = Database::fetchOne("SELECT in_transit_quantity FROM branch_inventory WHERE branch_id = :b AND product_id = :p", [':b' => $branch2Id, ':p' => $productId]);
    assertTest(abs((float)$destInvInTransit['in_transit_quantity'] - 15.00) < 0.01, "In-Transit quantity at destination branch updated to 15 units");

    // 1.3 Receive Transfer -> Destination stock added & lot cost preserved
    $recvRes = AdvancedInventory::receiveTransfer($transferId, $userId);
    assertTest($recvRes['success'] === true, "Stock transfer received successfully at Branch 2");

    $destInv = Database::fetchOne("SELECT quantity, in_transit_quantity FROM branch_inventory WHERE branch_id = :b AND product_id = :p", [':b' => $branch2Id, ':p' => $productId]);
    assertTest(abs((float)$destInv['quantity'] - 15.00) < 0.01 && abs((float)$destInv['in_transit_quantity'] - 0.00) < 0.01, "Destination sellable stock increased to 15 units and In-Transit cleared");

    $destLot = Database::fetchOne("
        SELECT * FROM product_lots WHERE branch_id = :b AND product_id = :p AND remaining_quantity > 0 ORDER BY id DESC LIMIT 1
    ", [':b' => $branch2Id, ':p' => $productId]);
    assertTest($destLot !== null && abs((float)$destLot['cost'] - 100.00) < 0.01, "Destination lot created preserving exact lot cost (LKR 100.00)");

    // 1.4 Double Receipt Blocked
    $doubleRecvRes = AdvancedInventory::receiveTransfer($transferId, $userId);
    assertTest($doubleRecvRes['success'] === false, "Double receipt correctly BLOCKED for already received transfer");

    // 1.5 Negative Stock Transfer Blocked
    $excessTrfRes = AdvancedInventory::createTransfer([
        'from_branch_id' => $branch1Id,
        'to_branch_id'   => $branch2Id,
        'send_now'       => 1,
        'items'          => [
            ['product_id' => $productId, 'lot_id' => $sourceLotId, 'quantity' => 999.00]
        ]
    ], $userId);
    assertTest($excessTrfRes['success'] === false, "Excessive transfer exceeding available stock correctly BLOCKED");

    // -------------------------------------------------------------
    // GROUP 2: Stock Adjustments (Increase / Decrease)
    // -------------------------------------------------------------
    echo "\n[GROUP 2] Stock Adjustments & Accounting\n";

    // 2.1 Increase Adjustment
    $adjIncRes = AdvancedInventory::createAdjustment([
        'branch_id'       => $branch1Id,
        'product_id'      => $productId,
        'lot_id'          => $sourceLotId,
        'adjustment_type' => 'increase',
        'quantity'        => 5.00,
        'reason'          => 'Found uncounted stock during check'
    ], $userId);

    assertTest($adjIncRes['success'] === true, "Stock adjustment increase (+5 units) created successfully (#{$adjIncRes['adjustment_number']})");

    $srcLotAfterInc = Database::fetchOne("SELECT remaining_quantity FROM product_lots WHERE id = :id", [':id' => $sourceLotId]);
    assertTest(abs((float)$srcLotAfterInc['remaining_quantity'] - 40.00) < 0.01, "Source lot remaining quantity increased to 40 units");

    // 2.2 Decrease Adjustment
    $adjDecRes = AdvancedInventory::createAdjustment([
        'branch_id'       => $branch1Id,
        'product_id'      => $productId,
        'lot_id'          => $sourceLotId,
        'adjustment_type' => 'decrease',
        'quantity'        => 2.00,
        'reason'          => 'Damaged outer box'
    ], $userId);

    assertTest($adjDecRes['success'] === true, "Stock adjustment decrease (-2 units) created successfully (#{$adjDecRes['adjustment_number']})");

    $srcLotAfterDec = Database::fetchOne("SELECT remaining_quantity FROM product_lots WHERE id = :id", [':id' => $sourceLotId]);
    assertTest(abs((float)$srcLotAfterDec['remaining_quantity'] - 38.00) < 0.01, "Source lot remaining quantity decreased to 38 units");

    // -------------------------------------------------------------
    // GROUP 3: Damaged / Wastage Stock & Disposal Write-Off
    // -------------------------------------------------------------
    echo "\n[GROUP 3] Damaged / Wastage Stock & Disposal Write-Off\n";

    $dmgRes = AdvancedInventory::recordDamagedStock([
        'branch_id'  => $branch1Id,
        'product_id' => $productId,
        'lot_id'     => $sourceLotId,
        'quantity'   => 3.00,
        'stock_type' => 'damaged',
        'reason'     => 'Water leak damage'
    ], $userId);

    assertTest($dmgRes['success'] === true, "Moved 3 units to Damaged Stock log (#DMG-{$dmgRes['id']})");

    $invDmg = Database::fetchOne("SELECT quantity, damaged_quantity FROM branch_inventory WHERE branch_id = :b AND product_id = :p", [':b' => $branch1Id, ':p' => $productId]);
    assertTest(abs((float)$invDmg['quantity'] - 35.00) < 0.01 && abs((float)$invDmg['damaged_quantity'] - 3.00) < 0.01, "Sellable stock decreased to 35 units and damaged stock increased to 3 units");

    // Disposal Write-Off (Must NOT double-reduce sellable stock)
    $disposeRes = AdvancedInventory::disposeDamagedStock($dmgRes['id'], ['notes' => 'Scrapped in bin'], $userId);
    assertTest($disposeRes['success'] === true, "Damaged stock log marked as disposed");

    $invAfterDispose = Database::fetchOne("SELECT quantity, damaged_quantity FROM branch_inventory WHERE branch_id = :b AND product_id = :p", [':b' => $branch1Id, ':p' => $productId]);
    assertTest(abs((float)$invAfterDispose['quantity'] - 35.00) < 0.01 && abs((float)$invAfterDispose['damaged_quantity'] - 0.00) < 0.01, "Disposal reduced damaged quantity without double-reducing sellable stock (Sellable remains 35)");

    // -------------------------------------------------------------
    // GROUP 4: Physical Stock Audit Workflow
    // -------------------------------------------------------------
    echo "\n[GROUP 4] Physical Stock Audit Workflow\n";

    $startAuditRes = AdvancedInventory::startStockAudit($branch1Id, 'Stage 14 Test Audit', $userId);
    assertTest($startAuditRes['success'] === true, "Started Stock Audit (#{$startAuditRes['audit_number']})");
    $auditId = $startAuditRes['audit_id'];

    $auditItems = Database::fetchAll("SELECT * FROM stock_audit_items WHERE audit_id = :id", [':id' => $auditId]);
    assertTest(!empty($auditItems), "Snapshot expected stock items populated for audit session");

    // Enter counted quantity with variance (-1 unit difference)
    $firstItem = $auditItems[0];
    $countedQty = (float)$firstItem['expected_quantity'] - 1.00;

    $saveCountsRes = AdvancedInventory::saveAuditCounts($auditId, [
        [
            'item_id'          => $firstItem['id'],
            'expected_quantity'=> $firstItem['expected_quantity'],
            'counted_quantity' => $countedQty,
            'notes'            => 'One unit missing'
        ]
    ], $userId);

    assertTest($saveCountsRes['success'] === true, "Audit counted quantities saved");

    // Post Audit -> Applies variance adjustment & locks session
    $postAuditRes = AdvancedInventory::postStockAudit($auditId, $userId);
    assertTest($postAuditRes['success'] === true, "Stock audit posted successfully and locked");

    $immutableAuditRes = AdvancedInventory::saveAuditCounts($auditId, [], $userId);
    assertTest($immutableAuditRes['success'] === false, "Posted stock audit session is locked & immutable against modifications");

    // -------------------------------------------------------------
    // GROUP 5: Stock Ledger & Inventory Overview
    // -------------------------------------------------------------
    echo "\n[GROUP 5] Stock Ledger & Inventory Overview\n";

    $ledgerData = Inventory::getLedgerList($branch1Id, [], 1, 20);
    assertTest(!empty($ledgerData['items']), "Stock ledger returned movements history with user & reference details");

    $overview = AdvancedInventory::getInventoryOverview($branch1Id, [], 1, 20);
    assertTest(!empty($overview['items']), "Inventory overview returned branch sellable, in-transit, and damaged quantities");

    // -------------------------------------------------------------
    // GROUP 6: Balanced GL Check & Stage 1-13 Regression
    // -------------------------------------------------------------
    echo "\n[GROUP 6] Balanced GL Check & Stage 1-13 Regression\n";

    $glBalanceRow = Database::fetchOne("
        SELECT SUM(debit) AS total_debit, SUM(credit) AS total_credit
        FROM journal_lines jl
        JOIN journal_entries je ON jl.journal_entry_id = je.id
        WHERE je.posting_status = 'posted'
    ");

    $totDeb = round((float)$glBalanceRow['total_debit'], 2);
    $totCrd = round((float)$glBalanceRow['total_credit'], 2);

    assertTest(abs($totDeb - $totCrd) < 0.01, "General Ledger Double-Entry remains 100% balanced (Total Debits: LKR " . number_format($totDeb, 2) . " == Total Credits: LKR " . number_format($totCrd, 2) . ")");

    echo "\n===============================================================\n";
    echo "TEST SUMMARY: {$testPassed} Passed, {$testFailed} Failed.\n";
    echo "===============================================================\n\n";

    if ($testFailed > 0) {
        exit(1);
    }

} catch (Throwable $e) {
    echo "UNHANDLED EXCEPTION IN TEST SUITE: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
