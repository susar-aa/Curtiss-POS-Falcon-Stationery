<?php
require_once 'c:/xampp/htdocs/CURTISS/FalconStationery/POS/config/config.php';
$pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
$pdo->exec("TRUNCATE TABLE sales");
$pdo->exec("TRUNCATE TABLE sale_items");
$pdo->exec("TRUNCATE TABLE payments");
$pdo->exec("TRUNCATE TABLE cash_drawer_movements");
$pdo->exec("TRUNCATE TABLE business_days");
$pdo->exec("TRUNCATE TABLE journal_lines");
$pdo->exec("TRUNCATE TABLE journal_entries");
$pdo->exec("TRUNCATE TABLE audit_logs");
// Restore product 1 stock
$pdo->exec("UPDATE product_lots SET remaining_quantity = 4, status = 'active' WHERE product_id = 1");
$pdo->exec("UPDATE branch_inventory SET quantity = 4 WHERE product_id = 1");
// Clean stock ledger
$pdo->exec("TRUNCATE TABLE stock_ledger");
// Re-insert initial opening stock ledger
$lots = $pdo->query("SELECT * FROM product_lots")->fetchAll(PDO::FETCH_ASSOC);
foreach ($lots as $l) {
    $pdo->prepare("INSERT INTO stock_ledger (branch_id, product_id, lot_id, movement_type, quantity, balance_after, reference_no, notes, user_id) VALUES (1, :pid, :lid, 'opening_stock', :qty, :qty, 'INIT-STOCK-BR1', 'Initial opening stock lot', 1)")
        ->execute([':pid' => $l['product_id'], ':lid' => $l['id'], ':qty' => $l['remaining_quantity']]);
}
$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
echo "Local database cleaned and restored after test.\n";
