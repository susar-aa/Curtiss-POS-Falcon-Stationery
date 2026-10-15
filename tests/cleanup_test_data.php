<?php
/**
 * Cleanup script to remove all automated test data and keep strictly the user's authentic data.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

$db = Database::getInstance()->getConnection();

echo "=========================================================\n";
echo "   CLEANING UP AUTOMATED TEST DATA (PRESERVING USER DATA)\n";
echo "=========================================================\n\n";

// User preserved IDs:
// Products: 12, 13, 18
// Suppliers: 9, 12
// Customers: 1
// GRNs: 7, 8, 9

$cutoff = '2026-10-12 17:00:00';
$db->exec("SET FOREIGN_KEY_CHECKS = 0");

// Clean up test supplier payments & customer payments created during test runner
$db->exec("DELETE FROM supplier_payment_grns WHERE supplier_payment_id IN (SELECT id FROM supplier_payments WHERE created_at >= '$cutoff') OR grn_id >= 10");
$db->exec("DELETE FROM supplier_payments WHERE created_at >= '$cutoff'");
$db->exec("DELETE FROM payments WHERE created_at >= '$cutoff'");
$db->exec("DELETE FROM supplier_returns WHERE created_at >= '$cutoff'");
$db->exec("DELETE FROM stock_transfer_items WHERE transfer_id IN (SELECT id FROM stock_transfers WHERE created_at >= '$cutoff')");
$db->exec("DELETE FROM stock_transfers WHERE created_at >= '$cutoff'");
$db->exec("DELETE FROM stock_adjustments WHERE created_at >= '$cutoff'");
$db->exec("DELETE FROM stock_audit_items WHERE audit_id IN (SELECT id FROM stock_audits WHERE created_at >= '$cutoff')");
$db->exec("DELETE FROM stock_audits WHERE created_at >= '$cutoff'");
$db->exec("DELETE FROM card_settlement_items WHERE settlement_id IN (SELECT id FROM card_settlements WHERE created_at >= '$cutoff')");
$db->exec("DELETE FROM card_settlements WHERE created_at >= '$cutoff'");

// 1. Delete test GRNs (ID >= 10)
$testGrns = Database::fetchAll("SELECT id FROM grns WHERE id >= 10 OR created_at >= :c", [':c' => $cutoff]);
$testGrnIds = array_column($testGrns, 'id');
if (!empty($testGrnIds)) {
    $inGrns = implode(',', array_map('intval', $testGrnIds));
    $db->exec("DELETE FROM grn_items WHERE grn_id IN ($inGrns)");
    $db->exec("DELETE FROM grns WHERE id IN ($inGrns)");
    echo "[+] Cleaned up " . count($testGrnIds) . " test GRNs\n";
}

// 2. Delete test Purchase Orders (ID >= 3)
$testPos = Database::fetchAll("SELECT id FROM purchase_orders WHERE id >= 3 OR created_at >= :c", [':c' => $cutoff]);
$testPoIds = array_column($testPos, 'id');
if (!empty($testPoIds)) {
    $inPos = implode(',', array_map('intval', $testPoIds));
    $db->exec("DELETE FROM purchase_order_items WHERE po_id IN ($inPos)");
    $db->exec("DELETE FROM purchase_orders WHERE id IN ($inPos)");
    echo "[+] Cleaned up " . count($testPoIds) . " test Purchase Orders\n";
}

// 3. Delete test products (not in 12, 13, 18)
$testProds = Database::fetchAll("SELECT id FROM products WHERE id NOT IN (12, 13, 18)");
$testProdIds = array_column($testProds, 'id');
if (!empty($testProdIds)) {
    $inProds = implode(',', array_map('intval', $testProdIds));
    $db->exec("DELETE FROM product_lots WHERE product_id IN ($inProds)");
    $db->exec("DELETE FROM branch_inventory WHERE product_id IN ($inProds)");
    $db->exec("DELETE FROM stock_ledger WHERE product_id IN ($inProds)");
    $db->exec("DELETE FROM products WHERE id IN ($inProds)");
    echo "[+] Cleaned up " . count($testProdIds) . " test products\n";
}

// 4. Delete test suppliers (not in 9, 12)
$testSupps = Database::fetchAll("SELECT id FROM suppliers WHERE id NOT IN (9, 12)");
$testSuppIds = array_column($testSupps, 'id');
if (!empty($testSuppIds)) {
    $inSupps = implode(',', array_map('intval', $testSuppIds));
    $db->exec("DELETE FROM suppliers WHERE id IN ($inSupps)");
    echo "[+] Cleaned up " . count($testSuppIds) . " test suppliers\n";
}

// 5. Delete test customers (not in 1)
$testCusts = Database::fetchAll("SELECT id FROM customers WHERE id NOT IN (1)");
$testCustIds = array_column($testCusts, 'id');
if (!empty($testCustIds)) {
    $inCusts = implode(',', array_map('intval', $testCustIds));
    $db->exec("DELETE FROM customers WHERE id IN ($inCusts)");
    echo "[+] Cleaned up " . count($testCustIds) . " test customers\n";
}

// 6. Delete test sales created during test runner (created_at >= cutoff)
$testSales = Database::fetchAll("SELECT id FROM sales WHERE created_at >= :c", [':c' => $cutoff]);
$testSaleIds = array_column($testSales, 'id');
if (!empty($testSaleIds)) {
    $inSales = implode(',', array_map('intval', $testSaleIds));
    $db->exec("DELETE FROM sale_items WHERE sale_id IN ($inSales)");
    $db->exec("DELETE FROM payments WHERE sale_id IN ($inSales)");
    $db->exec("DELETE FROM sales WHERE id IN ($inSales)");
    echo "[+] Cleaned up " . count($testSaleIds) . " test sales\n";
}

// 7. Delete test journal entries created during test runner (created_at >= cutoff)
$testJournals = Database::fetchAll("SELECT id FROM journal_entries WHERE created_at >= :c", [':c' => $cutoff]);
$testJournalIds = array_column($testJournals, 'id');
if (!empty($testJournalIds)) {
    $inJournals = implode(',', array_map('intval', $testJournalIds));
    $db->exec("DELETE FROM journal_lines WHERE journal_entry_id IN ($inJournals)");
    $db->exec("DELETE FROM journal_entries WHERE id IN ($inJournals)");
    echo "[+] Cleaned up " . count($testJournalIds) . " test journal entries\n";
}

// 8. Delete test audit logs created during test runner
$db->exec("DELETE FROM audit_logs WHERE created_at >= '$cutoff'");
echo "[+] Cleaned up test audit logs created after $cutoff\n";

// 9. Reset Auto-Increment for clean IDs where applicable
$maxGrn = (int)(Database::fetchOne("SELECT MAX(id) AS m FROM grns")['m'] ?? 0);
$nextGrn = $maxGrn + 1;
$db->exec("ALTER TABLE grns AUTO_INCREMENT = $nextGrn");

$maxPo = (int)(Database::fetchOne("SELECT MAX(id) AS m FROM purchase_orders")['m'] ?? 0);
$nextPo = $maxPo + 1;
$db->exec("ALTER TABLE purchase_orders AUTO_INCREMENT = $nextPo");

$maxProd = (int)(Database::fetchOne("SELECT MAX(id) AS m FROM products")['m'] ?? 0);
$nextProd = $maxProd + 1;
$db->exec("ALTER TABLE products AUTO_INCREMENT = $nextProd");

$maxSupp = (int)(Database::fetchOne("SELECT MAX(id) AS m FROM suppliers")['m'] ?? 0);
$nextSupp = $maxSupp + 1;
$db->exec("ALTER TABLE suppliers AUTO_INCREMENT = $nextSupp");

$maxCust = (int)(Database::fetchOne("SELECT MAX(id) AS m FROM customers")['m'] ?? 0);
$nextCust = $maxCust + 1;
$db->exec("SET FOREIGN_KEY_CHECKS = 1");

echo "\n--- VERIFICATION OF REMAINING USER DATA ---\n";
$prods = Database::fetchAll("SELECT id, name, sku FROM products");
echo "Products (" . count($prods) . "):\n";
foreach ($prods as $p) echo "  ID: {$p['id']} | {$p['name']} | SKU: {$p['sku']}\n";

$supps = Database::fetchAll("SELECT id, name FROM suppliers");
echo "Suppliers (" . count($supps) . "):\n";
foreach ($supps as $s) echo "  ID: {$s['id']} | {$s['name']}\n";

$custs = Database::fetchAll("SELECT id, name FROM customers");
echo "Customers (" . count($custs) . "):\n";
foreach ($custs as $c) echo "  ID: {$c['id']} | {$c['name']}\n";

$grns = Database::fetchAll("SELECT id, grn_number, supplier_invoice_no FROM grns");
echo "GRNs (" . count($grns) . "):\n";
foreach ($grns as $g) echo "  ID: {$g['id']} | {$g['grn_number']} | Inv: {$g['supplier_invoice_no']}\n";

echo "\n[+] Complete. Only user data remains in the database.\n";
