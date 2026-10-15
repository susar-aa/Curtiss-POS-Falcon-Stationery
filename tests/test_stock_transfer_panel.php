<?php
/**
 * Test Suite: Stock Transfer Creation Panel & AdvancedInventory Workflow
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/AdvancedInventory.php';

echo "=== STARTING STOCK TRANSFER TEST SUITE ===" . PHP_EOL;

$db = Database::getInstance()->getConnection();

// 1. Locate source lots at Branch 1
$lot = Database::fetchOne("
    SELECT pl.id AS lot_id, pl.product_id, pl.remaining_quantity, pl.cost, pl.selling_price
    FROM product_lots pl
    WHERE pl.branch_id = 1 AND pl.status = 'active' AND pl.remaining_quantity >= 2
    LIMIT 1
");

if (!$lot) {
    echo "SKIP: No available test lots found at branch 1 with qty >= 2" . PHP_EOL;
    exit(0);
}

$productId = (int)$lot['product_id'];
$lotId = (int)$lot['lot_id'];
$testUserId = 1;

echo "Selected Test Lot #{$lotId} for Product #{$productId} (Avail: {$lot['remaining_quantity']})" . PHP_EOL;

// 2. Test createTransfer as Draft
$transferData = [
    'from_branch_id' => 1,
    'to_branch_id'   => 2,
    'transfer_date'  => date('Y-m-d'),
    'notes'          => 'CLI Test Stock Transfer Draft',
    'send_now'       => 0,
    'items'          => [
        [
            'product_id' => $productId,
            'lot_id'     => $lotId,
            'quantity'   => 1.00
        ]
    ]
];

$createRes = AdvancedInventory::createTransfer($transferData, $testUserId);
if (!$createRes['success']) {
    echo "FAIL: createTransfer failed: " . $createRes['message'] . PHP_EOL;
    exit(1);
}

$transferId = (int)$createRes['transfer_id'];
$transferNo = $createRes['transfer_number'];
echo "PASS: createTransfer succeeded -> ID: {$transferId}, No: {$transferNo}" . PHP_EOL;

// 3. Verify transfer record in DB
$trf = Database::fetchOne("SELECT * FROM stock_transfers WHERE id = :id", [':id' => $transferId]);
if (!$trf || $trf['status'] !== 'draft') {
    echo "FAIL: transfer status is not draft in DB" . PHP_EOL;
    exit(1);
}
echo "PASS: Transfer status is 'draft'" . PHP_EOL;

$items = Database::fetchAll("SELECT * FROM stock_transfer_items WHERE transfer_id = :id", [':id' => $transferId]);
if (count($items) !== 1 || (float)$items[0]['quantity'] !== 1.00) {
    echo "FAIL: transfer items mismatch in DB" . PHP_EOL;
    exit(1);
}
echo "PASS: Transfer item verified (Qty: 1.00)" . PHP_EOL;

// 4. Test updateTransfer
$updateData = [
    'from_branch_id' => 1,
    'to_branch_id'   => 2,
    'transfer_date'  => date('Y-m-d'),
    'notes'          => 'CLI Test Stock Transfer Updated',
    'send_now'       => 0,
    'items'          => [
        [
            'product_id' => $productId,
            'lot_id'     => $lotId,
            'quantity'   => 1.50
        ]
    ]
];

$updateRes = AdvancedInventory::updateTransfer($transferId, $updateData, $testUserId);
if (!$updateRes['success']) {
    echo "FAIL: updateTransfer failed: " . $updateRes['message'] . PHP_EOL;
    exit(1);
}
echo "PASS: updateTransfer succeeded" . PHP_EOL;

$itemsUpdated = Database::fetchAll("SELECT * FROM stock_transfer_items WHERE transfer_id = :id", [':id' => $transferId]);
if (count($itemsUpdated) !== 1 || (float)$itemsUpdated[0]['quantity'] !== 1.50) {
    echo "FAIL: updated quantity mismatch (expected 1.50, got " . $itemsUpdated[0]['quantity'] . ")" . PHP_EOL;
    exit(1);
}
echo "PASS: Updated quantity verified in DB (1.50)" . PHP_EOL;

// 5. Test sendTransfer
$sendRes = AdvancedInventory::sendTransfer($transferId, $testUserId);
if (!$sendRes['success']) {
    echo "FAIL: sendTransfer failed: " . $sendRes['message'] . PHP_EOL;
    exit(1);
}
echo "PASS: sendTransfer succeeded (status: sent)" . PHP_EOL;

// 6. Test receiveTransfer
$recRes = AdvancedInventory::receiveTransfer($transferId, $testUserId);
if (!$recRes['success']) {
    echo "FAIL: receiveTransfer failed: " . $recRes['message'] . PHP_EOL;
    exit(1);
}
echo "PASS: receiveTransfer succeeded (status: received)" . PHP_EOL;

// Clean up isolated test records
echo "Cleaning up test transfer records..." . PHP_EOL;
$destItems = Database::fetchAll("SELECT dest_lot_id FROM stock_transfer_items WHERE transfer_id = :id", [':id' => $transferId]);
$db->prepare("DELETE FROM stock_transfer_items WHERE transfer_id = :id")->execute([':id' => $transferId]);
$db->prepare("DELETE FROM stock_transfers WHERE id = :id")->execute([':id' => $transferId]);
$db->prepare("DELETE FROM stock_ledger WHERE reference_no = :ref")->execute([':ref' => $transferNo]);

foreach ($destItems as $di) {
    if (!empty($di['dest_lot_id'])) {
        $db->prepare("DELETE FROM stock_ledger WHERE lot_id = :lid")->execute([':lid' => $di['dest_lot_id']]);
        $db->prepare("DELETE FROM product_lots WHERE id = :id")->execute([':id' => $di['dest_lot_id']]);
    }
}

// Restore original test lot quantity
$db->prepare("UPDATE product_lots SET remaining_quantity = :q WHERE id = :id")
   ->execute([':q' => $lot['remaining_quantity'], ':id' => $lotId]);

echo "=== ALL STOCK TRANSFER TESTS PASSED CLEANLY ===" . PHP_EOL;
