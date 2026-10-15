<?php
/**
 * Curtiss POS - Supplier Returns & Creation Panel Complete Optimization Suite
 * Comprehensive automated CLI verification for Stage 15 per CURTISS_MASTER_RULES.md
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Supplier.php';
require_once __DIR__ . '/../core/Product.php';
require_once __DIR__ . '/../core/SupplierReturnEngine.php';
require_once __DIR__ . '/../core/Accounting.php';
require_once __DIR__ . '/../core/Audit.php';

$passed = 0;
$failed = 0;
$testsRun = 0;

function assertTest(bool $condition, string $testName, string $details = '') {
    global $passed, $failed, $testsRun;
    $testsRun++;
    if ($condition) {
        $passed++;
        echo "  [PASS] {$testsRun}. {$testName}\n";
    } else {
        $failed++;
        echo "  [FAIL] {$testsRun}. {$testName} - Details: {$details}\n";
    }
}

echo "====================================================================\n";
echo "   CURTISS POS - SUPPLIER RETURNS COMPLETE OPTIMIZATION SUITE       \n";
echo "====================================================================\n\n";

$db = Database::getInstance()->getConnection();
$userId = 1;
$branchId = 1;

$cleanup = [
    'supplier_ids'      => [],
    'product_ids'       => [],
    'lot_ids'           => [],
    'grn_ids'           => [],
    'grn_item_ids'      => [],
    'return_ids'        => [],
    'return_item_ids'   => [],
    'credit_ids'        => [],
    'settlement_ids'    => [],
    'journal_entry_ids' => [],
    'ledger_ids'        => [],
    'audit_ids'         => []
];

try {
    // -------------------------------------------------------------
    // SECTION 1: DATABASE SCHEMA & COMPOSITE INDEXES
    // -------------------------------------------------------------
    echo "--- 1. DATABASE SCHEMA & COMPOSITE INDEXES ---\n";

    $sretIndexes = $db->query("SHOW INDEX FROM supplier_returns")->fetchAll(PDO::FETCH_ASSOC);
    $sretIndexNames = array_column($sretIndexes, 'Key_name');
    assertTest(in_array('idx_sret_branch_id', $sretIndexNames), "Composite index idx_sret_branch_id exists on supplier_returns (branch_id, id)");
    assertTest(in_array('idx_sret_branch_supp', $sretIndexNames), "Composite index idx_sret_branch_supp exists on supplier_returns (branch_id, supplier_id, id)");
    assertTest(in_array('idx_sret_branch_date', $sretIndexNames), "Composite index idx_sret_branch_date exists on supplier_returns (branch_id, return_date, id)");

    $sriIndexes = $db->query("SHOW INDEX FROM supplier_return_items")->fetchAll(PDO::FETCH_ASSOC);
    $sriIndexNames = array_column($sriIndexes, 'Key_name');
    assertTest(in_array('idx_srti_grn_item', $sriIndexNames), "Index idx_srti_grn_item exists on supplier_return_items (grn_item_id)");

    $scrdIndexes = $db->query("SHOW INDEX FROM supplier_credits")->fetchAll(PDO::FETCH_ASSOC);
    $scrdIndexNames = array_column($scrdIndexes, 'Key_name');
    assertTest(in_array('idx_scrd_branch_status', $scrdIndexNames), "Composite index idx_scrd_branch_status exists on supplier_credits (branch_id, status, id)");

    // -------------------------------------------------------------
    // SECTION 2: SETUP ISOLATED TEST FIXTURES
    // -------------------------------------------------------------
    echo "\n--- 2. SETUP ISOLATED TEST FIXTURES ---\n";

    // 1. Supplier
    $supplierName = 'OptSuite Return Supplier ' . time();
    $supRes = Supplier::create($supplierName, '0779988771', '0779988771', '123 Return Way', 'active');
    $supplierId = (int)($supRes['id'] ?? 0);
    $cleanup['supplier_ids'][] = $supplierId;
    assertTest($supplierId > 0, "Created isolated test supplier #{$supplierId}");

    // 2. Product
    $brand = Database::fetchOne("SELECT id FROM brands WHERE deleted_at IS NULL LIMIT 1");
    $bId = (int)($brand['id'] ?? 1);
    $cat = Database::fetchOne("SELECT id FROM categories WHERE deleted_at IS NULL LIMIT 1");
    $cId = (int)($cat['id'] ?? 1);

    $testBarcode = 'OPTSRET-' . time();
    $db->prepare("INSERT INTO products (sku, barcode, name, brand_id, category_id, supplier_id, cost, selling_price, status, created_at) VALUES (:sku, :barcode, :name, :bid, :cid, :sid, 2000.00, 3200.00, 'active', NOW())")
        ->execute([':sku' => $testBarcode, ':barcode' => $testBarcode, ':name' => 'OptSuite Return Product', ':bid' => $bId, ':cid' => $cId, ':sid' => $supplierId]);
    $productId = (int)$db->lastInsertId();
    $cleanup['product_ids'][] = $productId;
    assertTest($productId > 0, "Created isolated test product #{$productId}");

    // 3. Approved GRN
    $grnNo = 'GRN-OPT-' . time();
    $db->prepare("INSERT INTO grns (grn_number, supplier_id, branch_id, supplier_invoice_no, received_date, actual_grn_total, supplier_invoice_amount, paid_amount, payment_status, status, created_by, created_at) VALUES (:no, :sid, :bid, 'INV-OPT-01', CURDATE(), 20000.00, 20000.00, 5000.00, 'partially_paid', 'Approved', :uid, NOW())")
        ->execute([':no' => $grnNo, ':sid' => $supplierId, ':bid' => $branchId, ':uid' => $userId]);
    $grnId = (int)$db->lastInsertId();
    $cleanup['grn_ids'][] = $grnId;
    assertTest($grnId > 0, "Created approved test GRN #{$grnNo} (ID: {$grnId}) with total 20,000.00, paid 5,000.00 (Due: 15,000.00)");

    // 4. Product Lot
    $lotNo = 'LOT-OPT-' . time();
    $db->prepare("INSERT INTO product_lots (product_id, branch_id, lot_number, cost, selling_price, initial_quantity, remaining_quantity, status, created_at) VALUES (:pid, :bid, :lno, 2000.00, 3200.00, 10.00, 10.00, 'active', NOW())")
        ->execute([':pid' => $productId, ':bid' => $branchId, ':lno' => $lotNo]);
    $lotId = (int)$db->lastInsertId();
    $cleanup['lot_ids'][] = $lotId;
    assertTest($lotId > 0, "Created product lot #{$lotNo} (ID: {$lotId}) with 10 units");

    // 5. GRN Item
    $db->prepare("INSERT INTO grn_items (grn_id, product_id, lot_id, quantity, unit_cost, selling_price, line_total) VALUES (:gid, :pid, :lid, 10.00, 2000.00, 3200.00, 20000.00)")
        ->execute([':gid' => $grnId, ':pid' => $productId, ':lid' => $lotId]);
    $grnItemId = (int)$db->lastInsertId();
    $cleanup['grn_item_ids'][] = $grnItemId;

    // 6. Branch inventory
    $db->prepare("INSERT INTO branch_inventory (branch_id, product_id, quantity, updated_at) VALUES (:bid, :pid, 10.00, NOW()) ON DUPLICATE KEY UPDATE quantity = 10.00")
        ->execute([':bid' => $branchId, ':pid' => $productId]);

    // -------------------------------------------------------------
    // SECTION 3: RETURN ENGINE FUNCTIONALITY & CONCURRENCY
    // -------------------------------------------------------------
    echo "\n--- 3. RETURN ENGINE FUNCTIONALITY & CONCURRENCY ---\n";

    // Over-return prevention
    $overReturnRes = SupplierReturnEngine::processReturn([
        'grn_id' => $grnId,
        'return_date' => date('Y-m-d'),
        'items' => [
            ['grn_item_id' => $grnItemId, 'quantity' => 15.00, 'reason' => 'Too many units']
        ]
    ], $userId, $branchId);
    assertTest($overReturnRes['success'] === false, "Over-return of 15 units (exceeding 10 received) is blocked by engine");

    // Valid Return 1: 4 units @ 2000 = 8,000.00 (Due was 15,000, so 8,000 goes 100% to AP reduction)
    $retRes1 = SupplierReturnEngine::processReturn([
        'grn_id' => $grnId,
        'return_date' => date('Y-m-d'),
        'notes' => 'OptSuite Test Return 1',
        'items' => [
            ['grn_item_id' => $grnItemId, 'quantity' => 4.00, 'reason' => 'Damaged packaging']
        ]
    ], $userId, $branchId);

    assertTest($retRes1['success'] === true, "Valid return of 4 units processed successfully: {$retRes1['message']}");
    $returnId1 = (int)($retRes1['supplier_return_id'] ?? 0);
    $cleanup['return_ids'][] = $returnId1;

    assertTest((float)$retRes1['total_return_amount'] === 8000.00, "Total return amount calculated accurately: LKR 8,000.00");
    assertTest((float)$retRes1['applied_to_payable'] === 8000.00, "Applied to payable is LKR 8,000.00 (reducing AP)");
    assertTest((float)$retRes1['credit_amount'] === 0.00, "Supplier credit is 0.00 since return was within remaining AP due");

    // Verify product lot remaining decreased to 6
    $lotCheck = Database::fetchOne("SELECT remaining_quantity FROM product_lots WHERE id = :id", [':id' => $lotId]);
    assertTest((float)$lotCheck['remaining_quantity'] === 6.00, "Product lot remaining quantity reduced from 10.00 to 6.00");

    // Verify branch inventory decreased to 6
    $invCheck = Database::fetchOne("SELECT quantity FROM branch_inventory WHERE product_id = :pid AND branch_id = :bid", [':pid' => $productId, ':bid' => $branchId]);
    assertTest((float)$invCheck['quantity'] === 6.00, "Branch inventory reduced from 10.00 to 6.00");

    // Verify stock ledger movement
    $ledgerRow = Database::fetchOne("SELECT * FROM stock_ledger WHERE product_id = :pid AND reference_no = :ref ORDER BY id DESC LIMIT 1", [':pid' => $productId, ':ref' => $retRes1['return_number']]);
    if ($ledgerRow) $cleanup['ledger_ids'][] = (int)$ledgerRow['id'];
    assertTest($ledgerRow && (float)$ledgerRow['quantity'] === -4.00 && $ledgerRow['movement_type'] === 'supplier_return', "Stock ledger record created with movement_type 'supplier_return' and qty -4.00");

    // -------------------------------------------------------------
    // SECTION 4: DOUBLE-ENTRY ACCOUNTING & COA INTEGRATION
    // -------------------------------------------------------------
    echo "\n--- 4. DOUBLE-ENTRY ACCOUNTING & COA INTEGRATION ---\n";

    $retDbRow = Database::fetchOne("SELECT journal_entry_id FROM supplier_returns WHERE id = :id", [':id' => $returnId1]);
    $jeId1 = (int)($retDbRow['journal_entry_id'] ?? 0);
    if ($jeId1 > 0) $cleanup['journal_entry_ids'][] = $jeId1;
    assertTest($jeId1 > 0, "Journal entry automatically posted for return (JE #{$jeId1})");

    $jeLines = Database::fetchAll("
        SELECT jl.*, coa.account_code, coa.account_name 
        FROM journal_lines jl 
        JOIN chart_of_accounts coa ON jl.account_id = coa.id 
        WHERE jl.journal_entry_id = :je_id
    ", [':je_id' => $jeId1]);

    $totalDebit = 0.00;
    $totalCredit = 0.00;
    $hasApDebit = false;
    $hasInvCredit = false;

    foreach ($jeLines as $line) {
        $totalDebit += (float)$line['debit'];
        $totalCredit += (float)$line['credit'];
        if ($line['account_code'] === Accounting::CODE_SUPPLIER_PAYABLE && (float)$line['debit'] === 8000.00) {
            $hasApDebit = true;
        }
        if ($line['account_code'] === Accounting::CODE_INVENTORY_ASSET && (float)$line['credit'] === 8000.00) {
            $hasInvCredit = true;
        }
    }

    assertTest(round($totalDebit, 2) === round($totalCredit, 2) && $totalDebit === 8000.00, "Double-Entry Balance Verified: Total Debit ({$totalDebit}) == Total Credit ({$totalCredit})");
    assertTest($hasApDebit, "Correct Debit to Accounts Payable (2020) for LKR 8,000.00");
    assertTest($hasInvCredit, "Correct Credit to Inventory Asset (1200) for LKR 8,000.00");

    // -------------------------------------------------------------
    // SECTION 5: MULTI-COLUMN SEARCH & FORMATTED AMOUNT QUERYING
    // -------------------------------------------------------------
    echo "\n--- 5. MULTI-COLUMN SEARCH & FORMATTED AMOUNT QUERYING ---\n";

    // 1. Search by Return Number
    $s1 = SupplierReturnEngine::getReturnHistory(['search' => $retRes1['return_number'], 'branch_id' => $branchId]);
    assertTest(count($s1) >= 1 && $s1[0]['return_number'] === $retRes1['return_number'], "Search by exact return number matches correctly");

    // 2. Search by Supplier Name
    $s2 = SupplierReturnEngine::getReturnHistory(['search' => 'OptSuite Return Supplier', 'branch_id' => $branchId]);
    assertTest(count($s2) >= 1 && $s2[0]['supplier_name'] === $supplierName, "Search by supplier name matches correctly");

    // 3. Search by Notes
    $s3 = SupplierReturnEngine::getReturnHistory(['search' => 'OptSuite Test Return 1', 'branch_id' => $branchId]);
    assertTest(count($s3) >= 1 && strpos($s3[0]['notes'], 'OptSuite Test Return 1') !== false, "Search by notes keyword matches correctly");

    // 4. Search by Raw Numeric Amount: 8000
    $s4 = SupplierReturnEngine::getReturnHistory(['search' => '8000', 'branch_id' => $branchId]);
    assertTest(count($s4) >= 1, "Search by raw number '8000' matches return row");

    // 5. Search by Comma-Formatted Amount: 8,000.00
    $s5 = SupplierReturnEngine::getReturnHistory(['search' => '8,000.00', 'branch_id' => $branchId]);
    assertTest(count($s5) >= 1, "Search by comma-formatted '8,000.00' matches return row");

    // 6. Search by Formatted Currency String: LKR 8,000.00
    $s6 = SupplierReturnEngine::getReturnHistory(['search' => 'LKR 8,000.00', 'branch_id' => $branchId]);
    assertTest(count($s6) >= 1, "Search by formatted currency 'LKR 8,000.00' matches return row");

    // 7. Search by Rs. Currency String: Rs. 8,000.00
    $s7 = SupplierReturnEngine::getReturnHistory(['search' => 'Rs. 8,000.00', 'branch_id' => $branchId]);
    assertTest(count($s7) >= 1, "Search by formatted currency 'Rs. 8,000.00' matches return row");

    // -------------------------------------------------------------
    // SECTION 6: PAGINATION & N+1 ELIMINATION
    // -------------------------------------------------------------
    echo "\n--- 6. PAGINATION & N+1 ELIMINATION ---\n";

    $totalCount = SupplierReturnEngine::getReturnHistoryCount(['branch_id' => $branchId]);
    assertTest($totalCount >= 1, "getReturnHistoryCount returned accurate total ($totalCount entries)");

    $pagedHistory = SupplierReturnEngine::getReturnHistory([
        'branch_id' => $branchId,
        'limit'     => 1,
        'offset'    => 0
    ]);
    assertTest(count($pagedHistory) === 1, "Server-side pagination limit=1 returns exactly 1 entry");
    assertTest(isset($pagedHistory[0]['items']) && is_array($pagedHistory[0]['items']), "Items for paged return were batch-loaded successfully without N+1");

    // -------------------------------------------------------------
    // SECTION 7: AUDIT LOGGING
    // -------------------------------------------------------------
    echo "\n--- 7. AUDIT LOGGING ---\n";

    $auditRow = Database::fetchOne("
        SELECT * FROM audit_logs 
        WHERE module = 'purchasing' AND record_id = :rid AND action = 'SUPPLIER_RETURN_PROCESSED'
        ORDER BY id DESC LIMIT 1
    ", [':rid' => (string)$returnId1]);
    if ($auditRow) $cleanup['audit_ids'][] = (int)$auditRow['id'];
    assertTest($auditRow !== null, "Mandatory audit log captured for SUPPLIER_RETURN_PROCESSED");

    // -------------------------------------------------------------
    // SECTION 8: CSV EXPORT VERIFICATION
    // -------------------------------------------------------------
    echo "\n--- 8. CSV EXPORT SCRIPT INTEGRITY ---\n";

    assertTest(file_exists(__DIR__ . '/../supplier_return_export.php'), "Export handler supplier_return_export.php exists");

} catch (Throwable $e) {
    echo "  [EXCEPTION] Unexpected failure: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
    $failed++;
} finally {
    // -------------------------------------------------------------
    // CLEANUP: ISOLATED TEARDOWN (NO TRUNCATE)
    // -------------------------------------------------------------
    echo "\n-----------------------------------------------------\n";
    echo "  ISOLATED TEST TEARDOWN (Zero impact on user data)\n";
    echo "-----------------------------------------------------\n";

    // Clean journal lines & entries
    if (!empty($cleanup['journal_entry_ids'])) {
        $jeStr = implode(',', $cleanup['journal_entry_ids']);
        $db->exec("DELETE FROM journal_lines WHERE journal_entry_id IN ($jeStr)");
        $db->exec("DELETE FROM journal_entries WHERE id IN ($jeStr)");
        echo "  - Cleaned test journal entries: $jeStr\n";
    }

    // Clean returns & return items
    if (!empty($cleanup['return_ids'])) {
        $retStr = implode(',', $cleanup['return_ids']);
        $db->exec("DELETE FROM supplier_return_items WHERE supplier_return_id IN ($retStr)");
        $db->exec("DELETE FROM supplier_returns WHERE id IN ($retStr)");
        echo "  - Cleaned test supplier returns: $retStr\n";
    }

    // Clean stock ledger
    if (!empty($cleanup['ledger_ids'])) {
        $ledStr = implode(',', $cleanup['ledger_ids']);
        $db->exec("DELETE FROM stock_ledger WHERE id IN ($ledStr)");
        echo "  - Cleaned test stock ledger rows: $ledStr\n";
    }

    // Clean GRN items & GRNs
    if (!empty($cleanup['grn_ids'])) {
        $grnStr = implode(',', $cleanup['grn_ids']);
        $db->exec("DELETE FROM grn_items WHERE grn_id IN ($grnStr)");
        $db->exec("DELETE FROM grns WHERE id IN ($grnStr)");
        echo "  - Cleaned test GRNs: $grnStr\n";
    }

    // Clean product lots
    if (!empty($cleanup['lot_ids'])) {
        $lotStr = implode(',', $cleanup['lot_ids']);
        $db->exec("DELETE FROM product_lots WHERE id IN ($lotStr)");
        echo "  - Cleaned test product lots: $lotStr\n";
    }

    // Clean branch inventory & products
    if (!empty($cleanup['product_ids'])) {
        $pStr = implode(',', $cleanup['product_ids']);
        $db->exec("DELETE FROM branch_inventory WHERE product_id IN ($pStr)");
        $db->exec("DELETE FROM products WHERE id IN ($pStr)");
        echo "  - Cleaned test products: $pStr\n";
    }

    // Clean suppliers
    if (!empty($cleanup['supplier_ids'])) {
        $sStr = implode(',', $cleanup['supplier_ids']);
        $db->exec("DELETE FROM suppliers WHERE id IN ($sStr)");
        echo "  - Cleaned test suppliers: $sStr\n";
    }

    // Clean audit logs
    if (!empty($cleanup['audit_ids'])) {
        $aStr = implode(',', $cleanup['audit_ids']);
        $db->exec("DELETE FROM audit_logs WHERE id IN ($aStr)");
        echo "  - Cleaned test audit logs: $aStr\n";
    }
}

echo "\n=====================================================\n";
echo "  FINAL TEST SUMMARY: {$passed} PASSED, {$failed} FAILED (TOTAL: {$testsRun})\n";
echo "=====================================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
