<?php
/**
 * Curtiss POS - Populate Multiple Stock Lots Script
 * Adds 2-4 distinct price/stock lots per product for branch 1 and branch 2
 * to test automatic lot selection modal popup in POS billing.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

try {
    $pdo = Database::getInstance()->getConnection();
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    echo "=========================================================\n";
    echo "  ADDING MULTIPLE STOCK LOTS FOR ALL PRODUCTS IN DB      \n";
    echo "=========================================================\n\n";

    // Fetch all active products
    $products = $pdo->query("SELECT id, name, cost, selling_price FROM products WHERE deleted_at IS NULL")->fetchAll(PDO::FETCH_ASSOC);

    $lotCounter = 100;

    foreach ($products as $p) {
        $prodId = (int)$p['id'];
        $baseCost = (float)$p['cost'];
        $basePrice = (float)$p['selling_price'];

        // Create 3 active lots per product for Branch 1
        $lotsData = [
            [
                'lot_number' => 'LOT-2026-A' . ++$lotCounter,
                'cost'       => max(10, $baseCost),
                'price'      => max(15, $basePrice),
                'qty'        => 50.00
            ],
            [
                'lot_number' => 'LOT-2026-B' . ++$lotCounter,
                'cost'       => max(10, round($baseCost * 1.05, 2)),
                'price'      => max(15, round($basePrice * 1.08, 2)),
                'qty'        => 35.00
            ],
            [
                'lot_number' => 'LOT-2026-C' . ++$lotCounter,
                'cost'       => max(10, round($baseCost * 1.10, 2)),
                'price'      => max(15, round($basePrice * 1.15, 2)),
                'qty'        => 20.00
            ]
        ];

        $totalStockB1 = 0;

        foreach ($lotsData as $lot) {
            $stmt = $pdo->prepare("
                INSERT INTO product_lots (lot_number, product_id, branch_id, cost, selling_price, initial_quantity, remaining_quantity, status)
                VALUES (:num, :pid, 1, :cost, :price, :init_qty, :rem_qty, 'active')
                ON DUPLICATE KEY UPDATE remaining_quantity = VALUES(remaining_quantity), selling_price = VALUES(selling_price)
            ");
            $stmt->execute([
                ':num'      => $lot['lot_number'],
                ':pid'      => $prodId,
                ':cost'     => $lot['cost'],
                ':price'    => $lot['price'],
                ':init_qty' => $lot['qty'],
                ':rem_qty'  => $lot['qty']
            ]);
            $totalStockB1 += $lot['qty'];
        }

        // Also add Branch 2 lots & branch_inventory
        foreach ($lotsData as $lot) {
            $b2LotNum = $lot['lot_number'] . '-BR2';
            $stmt = $pdo->prepare("
                INSERT INTO product_lots (lot_number, product_id, branch_id, cost, selling_price, initial_quantity, remaining_quantity, status)
                VALUES (:num, :pid, 2, :cost, :price, :init_qty, :rem_qty, 'active')
                ON DUPLICATE KEY UPDATE remaining_quantity = VALUES(remaining_quantity), selling_price = VALUES(selling_price)
            ");
            $stmt->execute([
                ':num'      => $b2LotNum,
                ':pid'      => $prodId,
                ':cost'     => $lot['cost'],
                ':price'    => $lot['price'],
                ':init_qty' => $lot['qty'],
                ':rem_qty'  => $lot['qty']
            ]);
        }

        // Update branch_inventory summary totals for Branch 1 and Branch 2
        $pdo->exec("
            INSERT INTO branch_inventory (branch_id, product_id, quantity, low_stock_threshold)
            VALUES (1, {$prodId}, {$totalStockB1}, 10.00)
            ON DUPLICATE KEY UPDATE quantity = {$totalStockB1}
        ");
        $pdo->exec("
            INSERT INTO branch_inventory (branch_id, product_id, quantity, low_stock_threshold)
            VALUES (2, {$prodId}, {$totalStockB1}, 10.00)
            ON DUPLICATE KEY UPDATE quantity = {$totalStockB1}
        ");
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "SUCCESS: Created multiple active stock/price lots (3 per product per branch) across " . count($products) . " products.\n";

} catch (Throwable $e) {
    echo "ERROR POPULATING LOTS: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
