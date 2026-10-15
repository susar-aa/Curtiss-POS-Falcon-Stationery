<?php
/**
 * Curtiss POS - Section 12 Verification Suite
 * Comprehensive verification of Advanced Inventory, Branch Transfers, Adjustments,
 * Damage/Wastage Management & Physical Stock Audit Workflow.
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
$testCount = 0;

function assertSec12(bool $condition, string $testName, string $failureDetails = '') {
    global $testPassed, $testFailed, $testCount;
    $testCount++;
    if ($condition) {
        $testPassed++;
        echo "  [PASS] Assertion #{$testCount}: {$testName}\n";
    } else {
        $testFailed++;
        echo "  [FAIL] Assertion #{$testCount}: {$testName}\n";
        if ($failureDetails) {
            echo "         Detail: {$failureDetails}\n";
        }
    }
}

echo "===============================================================\n";
echo "    CURTISS POS - SECTION 12 ENHANCEMENT VERIFICATION SUITE    \n";
echo "===============================================================\n\n";

try {
    $db = Database::getInstance()->getConnection();
    $branch1Id = 1;
    $branch2Id = 2;
    $userId = 1;

    // --- SETUP TEST DATA & CLEANUP ---
    echo "--> Setting up clean test environment for Section 12...\n";

    $db->exec("DELETE FROM stock_audit_items WHERE audit_id IN (SELECT id FROM stock_audits WHERE notes LIKE '%Section 12%' OR audit_number LIKE 'AUD-S12%')");
    $db->exec("DELETE FROM stock_audits WHERE notes LIKE '%Section 12%' OR audit_number LIKE 'AUD-S12%'");
    $db->exec("DELETE FROM damaged_stock WHERE reason LIKE '%Section 12%' OR reason LIKE '%S12%'");
    $db->exec("DELETE FROM journal_lines WHERE journal_entry_id IN (SELECT id FROM journal_entries WHERE description LIKE '%Section 12%' OR description LIKE '%S12%')");
    $db->exec("DELETE FROM journal_entries WHERE description LIKE '%Section 12%' OR description LIKE '%S12%'");
    $db->exec("DELETE FROM stock_adjustments WHERE reason LIKE '%Section 12%' OR adjustment_number LIKE 'ADJ-S12%'");
    $db->exec("DELETE FROM stock_transfer_items WHERE transfer_id IN (SELECT id FROM stock_transfers WHERE notes LIKE '%Section 12%' OR transfer_number LIKE 'TRF-S12%')");
    $db->exec("DELETE FROM stock_transfers WHERE notes LIKE '%Section 12%' OR transfer_number LIKE 'TRF-S12%'");
    $db->exec("DELETE FROM stock_ledger WHERE notes LIKE '%Section 12%' OR reference_no LIKE '%S12%'");

    // Setup Test Product #1 (Product Global, Stock Branch-Isolated)
    $prod1 = Database::fetchOne("SELECT id FROM products WHERE barcode = 'BAR-S12-001' AND deleted_at IS NULL");
    if (!$prod1) {
        $db->exec("INSERT INTO products (name, barcode, sku, brand_id, category_id, supplier_id, cost, selling_price, status) VALUES ('Sec12 Test Product 1', 'BAR-S12-001', 'SKU-S12-001', 1, 1, 1, 100.00, 150.00, 'active')");
        $prod1Id = (int)$db->lastInsertId();
    } else {
        $prod1Id = (int)$prod1['id'];
    }

    // Reset inventory and lots for test product
    $db->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $db->exec("DELETE FROM stock_ledger WHERE product_id = {$prod1Id}");
    $db->exec("DELETE FROM product_lots WHERE product_id = {$prod1Id}");
    $db->exec("UPDATE branch_inventory SET quantity = 0.00, in_transit_quantity = 0.00, damaged_quantity = 0.00 WHERE product_id = {$prod1Id}");
    $db->exec("SET FOREIGN_KEY_CHECKS = 1;");

    // -------------------------------------------------------------
    // GROUP 1: Core Inventory & Exact-Lot Isolation
    // -------------------------------------------------------------
    echo "\n[GROUP 1] Core Inventory Architecture & Exact-Lot Isolation\n";

    // 1.1 Create Lot A at Branch 1 (Cost: 100.00, Price: 150.00, Qty: 30)
    $lotARes = Inventory::addStockLot($branch1Id, $prod1Id, 100.00, 150.00, 30.00, 'LOT-S12-A', 'INIT-S12-A', 'S12 Lot A', $userId, 'opening_stock');
    assertSec12($lotARes['success'] === true, "Create Lot A at Branch 1 (30 units @ Cost 100.00)");
    $lotAId = (int)$lotARes['lot_id'];

    // 1.2 Create Lot B at Branch 1 (Cost: 120.00, Price: 150.00, Qty: 20) -> Same Price, Different Cost
    $lotBRes = Inventory::addStockLot($branch1Id, $prod1Id, 120.00, 150.00, 20.00, 'LOT-S12-B', 'INIT-S12-B', 'S12 Lot B', $userId, 'opening_stock');
    assertSec12($lotBRes['success'] === true, "Create Lot B at Branch 1 (20 units @ Cost 120.00, Same Price 150.00)");
    $lotBId = (int)$lotBRes['lot_id'];

    // 1.3 Verify Exact Lots remain distinct
    assertSec12($lotAId !== $lotBId, "Lots A and B remain separate distinct lot records despite same selling price");

    // 1.4 Verify Branch Inventory equals sum of lots (30 + 20 = 50)
    $b1Inv = Inventory::getBranchStock($branch1Id, $prod1Id);
    assertSec12((float)$b1Inv['quantity'] === 50.00, "Branch 1 sellable inventory equals sum of lots (50.00 units)");

    // 1.5 Deplete Lot B to 0 (Zero-stock lot handling)
    $db->exec("UPDATE product_lots SET remaining_quantity = 0.00, status = 'depleted' WHERE id = {$lotBId}");
    $db->exec("UPDATE branch_inventory SET quantity = 30.00 WHERE branch_id = {$branch1Id} AND product_id = {$prod1Id}");

    // Verify zero-stock lot is retained historically but excluded from active POS selection
    $activeLots = Database::fetchAll("
        SELECT id FROM product_lots WHERE branch_id = {$branch1Id} AND product_id = {$prod1Id} AND status = 'active' AND remaining_quantity > 0
    ");
    assertSec12(count($activeLots) === 1 && (int)$activeLots[0]['id'] === $lotAId, "Zero-stock Lot B is hidden from active POS selection while retained historically");

    // Restore Lot B remaining quantity to 20 for transfer testing
    $db->exec("UPDATE product_lots SET remaining_quantity = 20.00, status = 'active' WHERE id = {$lotBId}");
    $db->exec("UPDATE branch_inventory SET quantity = 50.00 WHERE branch_id = {$branch1Id} AND product_id = {$prod1Id}");

    // -------------------------------------------------------------
    // GROUP 2: Branch Stock Transfers & In-Transit Tracking
    // -------------------------------------------------------------
    echo "\n[GROUP 2] Branch Stock Transfers & In-Transit Lifecycle\n";

    // 2.1 Block Transfer with Same Source and Destination
    $sameBranchTrf = AdvancedInventory::createTransfer([
        'from_branch_id' => $branch1Id,
        'to_branch_id'   => $branch1Id,
        'items'          => [['product_id' => $prod1Id, 'lot_id' => $lotAId, 'quantity' => 5.00]]
    ], $userId);
    assertSec12($sameBranchTrf['success'] === false, "Block transfer when source and destination branches are identical");

    // 2.2 Block Transfer with Excess Quantity (> Lot Stock)
    $excessTrf = AdvancedInventory::createTransfer([
        'from_branch_id' => $branch1Id,
        'to_branch_id'   => $branch2Id,
        'items'          => [['product_id' => $prod1Id, 'lot_id' => $lotAId, 'quantity' => 100.00]]
    ], $userId);
    assertSec12($excessTrf['success'] === false, "Block transfer quantity exceeding available lot stock");

    // 2.3 Create Valid Draft Transfer (10 units from Lot A)
    $draftTrf = AdvancedInventory::createTransfer([
        'from_branch_id' => $branch1Id,
        'to_branch_id'   => $branch2Id,
        'notes'          => 'Section 12 Draft Transfer',
        'items'          => [['product_id' => $prod1Id, 'lot_id' => $lotAId, 'quantity' => 10.00]]
    ], $userId);
    assertSec12($draftTrf['success'] === true, "Create Draft Stock Transfer (10 units from Lot A)");
    $trf1Id = (int)$draftTrf['transfer_id'];

    // Verify Draft state does NOT alter stock
    $postDraftInv = Inventory::getBranchStock($branch1Id, $prod1Id);
    assertSec12((float)$postDraftInv['quantity'] === 50.00, "Draft transfer produces NO stock movement (Branch 1 stock remains 50.00)");

    // 2.4 Send Transfer
    $sendTrfRes = AdvancedInventory::sendTransfer($trf1Id, $userId);
    assertSec12($sendTrfRes['success'] === true, "Send Stock Transfer (Status changes to 'sent')");

    // Verify Source Stock Decreased & Destination In-Transit Increased
    $postSendSrcInv = Inventory::getBranchStock($branch1Id, $prod1Id);
    $postSendDestInv = Database::fetchOne("SELECT quantity, in_transit_quantity FROM branch_inventory WHERE branch_id = {$branch2Id} AND product_id = {$prod1Id}");
    assertSec12((float)$postSendSrcInv['quantity'] === 40.00, "Source Branch 1 stock reduced by 10.00 (from 50.00 to 40.00)");
    assertSec12((float)$postSendDestInv['in_transit_quantity'] === 10.00, "Destination Branch 2 in_transit_quantity increased to 10.00");
    assertSec12((float)$postSendDestInv['quantity'] === 0.00, "Destination Branch 2 sellable quantity unchanged (0.00) while in transit");

    // 2.5 Block Duplicate Send
    $dupSendRes = AdvancedInventory::sendTransfer($trf1Id, $userId);
    assertSec12($dupSendRes['success'] === false, "Block duplicate send on already sent transfer");

    // 2.6 Receive Transfer at Destination Branch 2
    $recTrfRes = AdvancedInventory::receiveTransfer($trf1Id, $userId);
    assertSec12($recTrfRes['success'] === true, "Receive Stock Transfer at Branch 2");

    // Verify Destination Stock Increased, In-Transit Cleared, Exact Lot Cost Preserved
    $postRecDestInv = Database::fetchOne("SELECT quantity, in_transit_quantity FROM branch_inventory WHERE branch_id = {$branch2Id} AND product_id = {$prod1Id}");
    $destLot = Database::fetchOne("SELECT * FROM product_lots WHERE branch_id = {$branch2Id} AND product_id = {$prod1Id}");
    assertSec12((float)$postRecDestInv['quantity'] === 10.00, "Destination Branch 2 sellable quantity increased to 10.00 upon receipt");
    assertSec12((float)$postRecDestInv['in_transit_quantity'] === 0.00, "Destination Branch 2 in_transit_quantity cleared back to 0.00");
    assertSec12($destLot !== false && (float)$destLot['cost'] === 100.00, "Transferred destination lot preserves exact transferred cost (LKR 100.00)");

    // 2.7 Block Duplicate Receipt
    $dupRecRes = AdvancedInventory::receiveTransfer($trf1Id, $userId);
    assertSec12($dupRecRes['success'] === false, "Block duplicate receipt on already received transfer");

    // 2.8 Transfer Cancellation Test (Draft & Sent)
    $cancelDraftTrf = AdvancedInventory::createTransfer([
        'from_branch_id' => $branch1Id,
        'to_branch_id'   => $branch2Id,
        'notes'          => 'Section 12 Cancel Test',
        'items'          => [['product_id' => $prod1Id, 'lot_id' => $lotAId, 'quantity' => 5.00]]
    ], $userId);
    $cTrfId = (int)$cancelDraftTrf['transfer_id'];

    // Send transfer then cancel sent transfer
    AdvancedInventory::sendTransfer($cTrfId, $userId);
    $cancelSentRes = AdvancedInventory::cancelTransfer($cTrfId, $userId);
    assertSec12($cancelSentRes['success'] === true, "Cancel sent stock transfer returns stock to source and clears in-transit");

    $postCancelSrcInv = Inventory::getBranchStock($branch1Id, $prod1Id);
    assertSec12((float)$postCancelSrcInv['quantity'] === 40.00, "Source Branch 1 stock restored back to 40.00 after transfer cancellation");

    // -------------------------------------------------------------
    // GROUP 3: Stock Adjustments (Increase / Decrease & Accounting)
    // -------------------------------------------------------------
    echo "\n[GROUP 3] Stock Adjustments (Increase / Decrease & Accounting)\n";

    // 3.1 Adjustment Increase (+15 units to Lot A at Branch 1)
    $adjIncRes = AdvancedInventory::createAdjustment([
        'branch_id'       => $branch1Id,
        'product_id'      => $prod1Id,
        'lot_id'          => $lotAId,
        'adjustment_type' => 'increase',
        'quantity'        => 15.00,
        'reason'          => 'Section 12 Stock Count Surplus',
        'notes'           => 'S12 Test Adjustment Increase'
    ], $userId);

    assertSec12($adjIncRes['success'] === true, "Post Stock Adjustment Increase (+15 units)");
    $adjIncId = (int)$adjIncRes['adjustment_id'];

    // Verify GL Posting for Adjustment Increase (Dr 1200 Inventory Asset, Cr 5020 Inventory Adjustments)
    $adjIncJe = Database::fetchOne("
        SELECT je.*, jl.debit, jl.credit, coa.account_code
        FROM journal_entries je
        JOIN journal_lines jl ON jl.journal_entry_id = je.id
        JOIN chart_of_accounts coa ON jl.account_id = coa.id
        WHERE je.source_id = :id AND je.description LIKE '%Section 12 Stock Count Surplus%' AND jl.debit > 0
    ", [':id' => $adjIncId]);

    assertSec12($adjIncJe !== false && $adjIncJe['account_code'] === '1200', "Adjustment Increase debits Inventory Asset (1200)");

    // 3.2 Adjustment Decrease (-5 units from Lot A at Branch 1)
    $adjDecRes = AdvancedInventory::createAdjustment([
        'branch_id'       => $branch1Id,
        'product_id'      => $prod1Id,
        'lot_id'          => $lotAId,
        'adjustment_type' => 'decrease',
        'quantity'        => 5.00,
        'reason'          => 'Section 12 Shrinkage',
        'notes'           => 'S12 Test Adjustment Decrease'
    ], $userId);

    assertSec12($adjDecRes['success'] === true, "Post Stock Adjustment Decrease (-5 units)");

    // 3.3 Block Adjustment Decrease Exceeding Available Stock
    $overDecRes = AdvancedInventory::createAdjustment([
        'branch_id'       => $branch1Id,
        'product_id'      => $prod1Id,
        'lot_id'          => $lotAId,
        'adjustment_type' => 'decrease',
        'quantity'        => 999.00,
        'reason'          => 'Section 12 Excess Decrease'
    ], $userId);

    assertSec12($overDecRes['success'] === false, "Block stock adjustment decrease exceeding available stock");

    // -------------------------------------------------------------
    // GROUP 4: Damaged / Wastage Stock Management & Disposal
    // -------------------------------------------------------------
    echo "\n[GROUP 4] Damaged / Wastage Stock Management & Disposal\n";

    // 4.1 Record Damaged Stock (3 units from Lot A)
    $dmgRes = AdvancedInventory::recordDamagedStock([
        'branch_id'  => $branch1Id,
        'product_id' => $prod1Id,
        'lot_id'     => $lotAId,
        'quantity'   => 3.00,
        'stock_type' => 'damaged',
        'reason'     => 'Section 12 Water Damage'
    ], $userId);

    assertSec12($dmgRes['success'] === true, "Record Damaged Stock (3 units moved from sellable to damaged)");
    $dmgId = (int)$dmgRes['id'];

    // Verify sellable stock reduced, damaged_quantity increased
    $postDmgInv = Database::fetchOne("SELECT quantity, damaged_quantity FROM branch_inventory WHERE branch_id = {$branch1Id} AND product_id = {$prod1Id}");
    assertSec12((float)$postDmgInv['damaged_quantity'] === 3.00, "Branch inventory damaged_quantity increased to 3.00");

    // 4.2 Dispose / Write-Off Damaged Stock
    $dispRes = AdvancedInventory::disposeDamagedStock($dmgId, ['notes' => 'S12 Disposal'], $userId);
    assertSec12($dispRes['success'] === true, "Dispose damaged stock (clears damaged_quantity without touching sellable stock)");

    $postDispInv = Database::fetchOne("SELECT quantity, damaged_quantity FROM branch_inventory WHERE branch_id = {$branch1Id} AND product_id = {$prod1Id}");
    assertSec12((float)$postDispInv['damaged_quantity'] === 0.00, "Damaged stock disposed: damaged_quantity cleared back to 0.00");

    // 4.3 Block Duplicate Disposal
    $dupDispRes = AdvancedInventory::disposeDamagedStock($dmgId, [], $userId);
    assertSec12($dupDispRes['success'] === false, "Block duplicate disposal of already disposed damaged stock record");

    // -------------------------------------------------------------
    // GROUP 5: Physical Stock Audit Workflow & Live Snapshot Concurrency
    // -------------------------------------------------------------
    echo "\n[GROUP 5] Physical Stock Audit Workflow & Live Snapshot Concurrency\n";

    // 5.1 Start Stock Audit Session for Branch 1
    $startAudRes = AdvancedInventory::startStockAudit($branch1Id, 'Section 12 Physical Audit', $userId);
    assertSec12($startAudRes['success'] === true, "Start Physical Stock Audit for Branch 1");
    $auditId = (int)$startAudRes['audit_id'];

    // 5.2 Block Starting Duplicate Active Audit Session
    $dupAudRes = AdvancedInventory::startStockAudit($branch1Id, 'Duplicate Session', $userId);
    assertSec12($dupAudRes['success'] === false, "Block starting duplicate active stock audit for same branch");

    // 5.3 Save Counted Quantities
    $auditItems = Database::fetchAll("SELECT * FROM stock_audit_items WHERE audit_id = {$auditId}");
    $countData = [];
    foreach ($auditItems as $item) {
        $countData[] = [
            'item_id'          => $item['id'],
            'expected_quantity'=> $item['expected_quantity'],
            'counted_quantity' => (float)$item['expected_quantity'] - 1.00, // 1 unit shortage variance
            'notes'            => 'S12 Audit Count Entry'
        ];
    }

    $saveCountRes = AdvancedInventory::saveAuditCounts($auditId, $countData, $userId);
    assertSec12($saveCountRes['success'] === true, "Save physical audit counts with 1-unit shortage variance");

    // 5.4 Post Stock Audit Session
    $postAudRes = AdvancedInventory::postStockAudit($auditId, $userId);
    assertSec12($postAudRes['success'] === true, "Post Stock Audit session (Applies variance adjustments & locks session)");

    // 5.5 Block Modifying Posted Audit
    $modPostAud = AdvancedInventory::saveAuditCounts($auditId, [], $userId);
    assertSec12($modPostAud['success'] === false, "Block modifying counts on posted/locked stock audit session");

    // -------------------------------------------------------------
    // GROUP 6: Inventory Reconciliations & Diagnostic Checks
    // -------------------------------------------------------------
    echo "\n[GROUP 6] Inventory Reconciliations & Diagnostic Checks\n";

    $reconciles = ReconciliationEngine::runAllReconciliations();

    assertSec12($reconciles['inventory']['lot_mismatches_count'] === 0, "Branch Sellable Inventory reconciles with exact lots (0 lot mismatches)");

    $inTransitSum = (float)Database::fetchOne("SELECT COALESCE(SUM(in_transit_quantity), 0.00) AS total FROM branch_inventory")['total'];
    $sentUnrecSum = (float)Database::fetchOne("
        SELECT COALESCE(SUM(sti.quantity), 0.00) AS total
        FROM stock_transfer_items sti
        JOIN stock_transfers st ON sti.transfer_id = st.id
        WHERE st.status = 'sent'
    ")['total'];

    assertSec12(round(abs($inTransitSum - $sentUnrecSum), 2) === 0.00, "In-Transit stock reconciles with Sent/Unreceived transfer quantities (In-Transit: {$inTransitSum})");

    // Check System Diagnostic Anomalies
    $diag = DiagnosticsEngine::runAllChecks();
    assertSec12($diag['unbalanced_journals']['count'] === 0, "Zero unbalanced GL journal entries exist in system");
    assertSec12($diag['negative_stock']['count'] === 0, "Zero negative stock quantities exist across lots and branch inventory");

    // --- TEARDOWN TEST ARTIFACTS ---
    echo "\n[TEARDOWN] Cleaning Section 12 Test Artifacts...\n";

    $db->exec("DELETE FROM stock_audit_items WHERE audit_id IN (SELECT id FROM stock_audits WHERE notes LIKE '%Section 12%' OR audit_number LIKE 'AUD-S12%')");
    $db->exec("DELETE FROM stock_audits WHERE notes LIKE '%Section 12%' OR audit_number LIKE 'AUD-S12%'");
    $db->exec("DELETE FROM damaged_stock WHERE reason LIKE '%Section 12%' OR reason LIKE '%S12%'");
    $db->exec("DELETE FROM journal_lines WHERE journal_entry_id IN (SELECT id FROM journal_entries WHERE description LIKE '%Section 12%' OR description LIKE '%S12%')");
    $db->exec("DELETE FROM journal_entries WHERE description LIKE '%Section 12%' OR description LIKE '%S12%'");
    $db->exec("DELETE FROM stock_adjustments WHERE reason LIKE '%Section 12%' OR adjustment_number LIKE 'ADJ-S12%'");
    $db->exec("DELETE FROM stock_transfer_items WHERE transfer_id IN (SELECT id FROM stock_transfers WHERE notes LIKE '%Section 12%' OR transfer_number LIKE 'TRF-S12%')");
    $db->exec("DELETE FROM stock_transfers WHERE notes LIKE '%Section 12%' OR transfer_number LIKE 'TRF-S12%'");
    $db->exec("DELETE FROM stock_ledger WHERE notes LIKE '%Section 12%' OR reference_no LIKE '%S12%'");

    echo "Teardown complete.\n\n";

    echo "===============================================================\n";
    echo "  SECTION 12 TEST SUMMARY: Passed: {$testPassed} | Failed: {$testFailed}\n";
    echo "===============================================================\n";

    if ($testFailed > 0) {
        exit(1);
    } else {
        exit(0);
    }

} catch (Throwable $e) {
    echo "\n[FATAL ERROR] " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
