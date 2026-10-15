<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Inventory.php';

$branchId = 2;
$productId = 1;
$cost = 50.00;
$sellingPrice = 100.00;
$quantity = 10.00;
$lotNumber = 'LOT-GRN1010-1-123';
$grnNumber = 'GRN-202609-1010';
$userId = 1;

$db = Database::getInstance()->getConnection();
$db->beginTransaction();

$res = Inventory::addStockLot(
    $branchId,
    $productId,
    $cost,
    $sellingPrice,
    $quantity,
    $lotNumber,
    $grnNumber,
    "GRN Purchase test",
    $userId,
    'purchase_grn'
);

$db->rollBack();

echo "Inventory::addStockLot result: " . json_encode($res, JSON_PRETTY_PRINT) . "\n";
