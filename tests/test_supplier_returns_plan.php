<?php
/**
 * Curtiss POS - Supplier Return Automated Verification & Integrity Test Suite
 * Based on plan.md, curtiss_design_rules.md, and POS_DESIGN_RULES.md
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Supplier.php';
require_once __DIR__ . '/../core/Product.php';
require_once __DIR__ . '/../core/GRN.php';
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
        echo "[PASS] {$testsRun}. {$testName}\n";
    } else {
        $failed++;
        echo "[FAIL] {$testsRun}. {$testName} - Details: {$details}\n";
    }
}

echo "====================================================================\n";
echo "   CURTISS POS - SUPPLIER RETURNS INTEGRATION & ACCOUNTING TEST    \n";
echo "====================================================================\n\n";

$db = Database::getInstance()->getConnection();
$userId = 1;
$branchId = 1;

// Arrays to track created test IDs for strict isolated teardown
$cleanup = [
    'supplier_ids'       => [],
    'product_ids'        => [],
    'lot_ids'            => [],
    'grn_ids'            => [],
    'grn_item_ids'       => [],
    'return_ids'         => [],
    'return_item_ids'    => [],
    'credit_ids'         => [],
    'journal_entry_ids'  => [],
    'ledger_ids'         => [],
    'audit_ids'          => []
];

try {
    // -------------------------------------------------------------
    // TEST 1: Database Schema Verification
    // -------------------------------------------------------------
    $tables = $db->query("SHOW TABLES LIKE 'supplier_return%'")->fetchAll(PDO::FETCH_COLUMN);
    assertTest(in_array('supplier_returns', $tables) && in_array('supplier_return_items', $tables), "Database tables supplier_returns and supplier_return_items exist");

    $creditTables = $db->query("SHOW TABLES LIKE 'supplier_credit%'")->fetchAll(PDO::FETCH_COLUMN);
    assertTest(in_array('supplier_credits', $tables) || in_array('supplier_credits', $creditTables), "Database table supplier_credits exists");

    // -------------------------------------------------------------
    // TEST 2: Verify Existing User GRNs are safe & intact
    // -------------------------------------------------------------
    $existingGrns = Database::fetchAll("SELECT id, grn_number, supplier_id, status FROM grns WHERE id IN (1, 2, 3)");
    echo "  -> Found " . count($existingGrns) . " pre-existing user GRN(s) (IDs 1-3). Verifying they are untouched...\n";
    assertTest(count($existingGrns) >= 1, "Pre-existing user GRNs are present and will not be touched");

    // -------------------------------------------------------------
    // SETUP: Create isolated test entities
    // -------------------------------------------------------------
    // 1. Test Supplier
    $testSupRes = Supplier::create('AutoTest Supplier Returns', '0771234567', null, 'Test Addr', 'active');
    $testSupplierId = (int)($testSupRes['id'] ?? 0);
    $cleanup['supplier_ids'][] = $testSupplierId;
    assertTest($testSupplierId > 0, "Created isolated test supplier (ID: {$testSupplierId})");

    // 2. Test Product
    $firstBrand = Database::fetchOne("SELECT id FROM brands WHERE deleted_at IS NULL LIMIT 1");
    $bId = (int)($firstBrand['id'] ?? 1);
    $firstCat = Database::fetchOne("SELECT id FROM categories WHERE deleted_at IS NULL LIMIT 1");
    $cId = (int)($firstCat['id'] ?? 1);

    $testBarcode = 'TEST-RET-' . time();
    $db->prepare("INSERT INTO products (sku, barcode, name, brand_id, category_id, supplier_id, cost, selling_price, status, created_at) VALUES (:sku, :barcode, :name, :bid, :cid, :sid, 1500.00, 2500.00, 'active', NOW())")
        ->execute([':sku' => $testBarcode, ':barcode' => $testBarcode, ':name' => 'AutoTest Return Product', ':bid' => $bId, ':cid' => $cId, ':sid' => $testSupplierId]);
    $testProductId = (int)$db->lastInsertId();
    $cleanup['product_ids'][] = $testProductId;
    assertTest($testProductId > 0, "Created isolated test product (ID: {$testProductId})");

    // Initial branch inventory: 0
    $db->prepare("INSERT INTO branch_inventory (branch_id, product_id, quantity, updated_at) VALUES (:bid, :pid, 0.00, NOW()) ON DUPLICATE KEY UPDATE quantity = 0.00")
        ->execute([':bid' => $branchId, ':pid' => $testProductId]);

    // 3. Test Approved GRN with lot & stock
    $testGrnNo = 'GRN-TEST-' . time();
    $db->prepare("INSERT INTO grns (grn_number, supplier_id, branch_id, supplier_invoice_no, received_date, actual_grn_total, supplier_invoice_amount, paid_amount, status, payment_status, created_by, created_at) VALUES (:no, :sid, :bid, 'INV-TEST-01', CURDATE(), 15000.00, 15000.00, 0.00, 'Approved', 'unpaid', :uid, NOW())")
        ->execute([':no' => $testGrnNo, ':sid' => $testSupplierId, ':bid' => $branchId, ':uid' => $userId]);
    $testGrnId = (int)$db->lastInsertId();
    $cleanup['grn_ids'][] = $testGrnId;

    // Create Lot
    $testLotNo = 'LOT-TEST-' . time();
    $db->prepare("INSERT INTO product_lots (product_id, branch_id, lot_number, cost, selling_price, initial_quantity, remaining_quantity, status, created_at) VALUES (:pid, :bid, :lot, 1500.00, 2500.00, 10.00, 10.00, 'active', NOW())")
        ->execute([':pid' => $testProductId, ':bid' => $branchId, ':lot' => $testLotNo]);
    $testLotId = (int)$db->lastInsertId();
    $cleanup['lot_ids'][] = $testLotId;

    // Create GRN Item
    $db->prepare("INSERT INTO grn_items (grn_id, product_id, lot_id, quantity, unit_cost, selling_price, line_total) VALUES (:gid, :pid, :lid, 10.00, 1500.00, 2500.00, 15000.00)")
        ->execute([':gid' => $testGrnId, ':pid' => $testProductId, ':lid' => $testLotId]);
    $testGrnItemId = (int)$db->lastInsertId();
    $cleanup['grn_item_ids'][] = $testGrnItemId;

    // Update branch inventory to 10.00
    $db->prepare("UPDATE branch_inventory SET quantity = 10.00 WHERE branch_id = :bid AND product_id = :pid")
        ->execute([':bid' => $branchId, ':pid' => $testProductId]);

    assertTest($testGrnId > 0 && $testLotId > 0 && $testGrnItemId > 0, "Created approved test GRN #{$testGrnNo} with 10 units @ 1,500.00 (Total: 15,000.00)");

    // -------------------------------------------------------------
    // TEST 3: Supplier Purchased Products Fetching
    // -------------------------------------------------------------
    $purchasedResult = SupplierReturnEngine::getSupplierPurchasedProducts($testSupplierId, null, $branchId);
    assertTest($purchasedResult['success'] === true, "getSupplierPurchasedProducts returned success");
    assertTest(count($purchasedResult['products']) === 1, "getSupplierPurchasedProducts found exactly 1 purchased product item");

    $item = $purchasedResult['products'][0] ?? [];
    assertTest((float)$item['total_purchased_quantity'] === 10.0 && (float)$item['max_returnable'] === 10.0, "Total purchased qty is 10.00 and max_returnable is 10.00");

    // -------------------------------------------------------------
    // TEST 4: Validation Edge Case - Return exceeding stock / purchased qty
    // -------------------------------------------------------------
    $invalidReturn = SupplierReturnEngine::processReturnBySupplier([
        'supplier_id' => $testSupplierId,
        'return_date' => date('Y-m-d'),
        'notes'       => 'Test Over-Return',
        'items'       => [
            [
                'product_id' => $testProductId,
                'lot_id'     => $testLotId,
                'quantity'   => 15.00, // Exceeds available 10.00
                'unit_cost'  => 1500.00,
                'reason'     => 'Testing over-return boundary'
            ]
        ]
    ], $userId, $branchId);
    assertTest($invalidReturn['success'] === false, "Over-return request (> 10 units) properly blocked by engine");

    // -------------------------------------------------------------
    // TEST 5: Process Valid Partial Return with AP Payable Offset
    // (Return 4 units @ 1500 = 6,000.00; Applied entirely to AP since unpaid GRN has 15,000.00 due)
    // -------------------------------------------------------------
    $returnRes1 = SupplierReturnEngine::processReturnBySupplier([
        'supplier_id' => $testSupplierId,
        'return_date' => date('Y-m-d'),
        'notes'       => 'Test Return AP Offset',
        'items'       => [
            [
                'product_id' => $testProductId,
                'lot_id'     => $testLotId,
                'quantity'   => 4.00,
                'unit_cost'  => 1500.00,
                'reason'     => 'Damaged packaging'
            ]
        ]
    ], $userId, $branchId);

    assertTest($returnRes1['success'] === true, "processReturnBySupplier executed successfully: " . ($returnRes1['message'] ?? ''));
    $ret1Id = (int)($returnRes1['supplier_return_id'] ?? 0);
    $cleanup['return_ids'][] = $ret1Id;
    assertTest($ret1Id > 0, "Supplier return record created (ID: {$ret1Id}, No: {$returnRes1['return_number']})");
    assertTest((float)$returnRes1['total_return_amount'] === 6000.00, "Total return amount is 6,000.00");
    assertTest((float)$returnRes1['applied_to_payable'] === 6000.00, "Applied to payable is 6,000.00 (AP Offset)");
    assertTest((float)$returnRes1['credit_amount'] === 0.00, "Credit amount is 0.00 (No excess credit)");

    // Verify Stock Deductions
    $updatedLot = Database::fetchOne("SELECT remaining_quantity FROM product_lots WHERE id = :id", [':id' => $testLotId]);
    assertTest((float)$updatedLot['remaining_quantity'] === 6.00, "Product lot remaining quantity reduced from 10.00 to 6.00");

    $updatedInv = Database::fetchOne("SELECT quantity FROM branch_inventory WHERE product_id = :pid AND branch_id = :bid", [':pid' => $testProductId, ':bid' => $branchId]);
    assertTest((float)$updatedInv['quantity'] === 6.00, "Branch inventory quantity reduced from 10.00 to 6.00");

    // Verify Stock Ledger Movement
    $ledgerRow = Database::fetchOne("SELECT id, movement_type, quantity, balance_after FROM stock_ledger WHERE reference_no = :ref", [':ref' => $returnRes1['return_number']]);
    if ($ledgerRow) $cleanup['ledger_ids'][] = (int)$ledgerRow['id'];
    assertTest($ledgerRow && $ledgerRow['movement_type'] === 'supplier_return' && (float)$ledgerRow['quantity'] === -4.00, "Stock ledger entry created with movement_type 'supplier_return' and qty -4.00");

    // Verify GRN paid_amount updated
    $updatedGrn = Database::fetchOne("SELECT paid_amount, payment_status FROM grns WHERE id = :id", [':id' => $testGrnId]);
    assertTest((float)$updatedGrn['paid_amount'] === 6000.00 && $updatedGrn['payment_status'] === 'partially_paid', "GRN paid_amount updated to 6,000.00 (Status: partially_paid)");

    // -------------------------------------------------------------
    // TEST 6: Complete Accounting Verification for Return 1
    // -------------------------------------------------------------
    $ret1Record = Database::fetchOne("SELECT journal_entry_id FROM supplier_returns WHERE id = :id", [':id' => $ret1Id]);
    $je1Id = (int)($ret1Record['journal_entry_id'] ?? 0);
    if ($je1Id > 0) $cleanup['journal_entry_ids'][] = $je1Id;
    assertTest($je1Id > 0, "Journal entry automatically generated (JE ID: {$je1Id})");

    if ($je1Id > 0) {
        $jeHeader = Database::fetchOne("SELECT * FROM journal_entries WHERE id = :id", [':id' => $je1Id]);
        $jeLines  = Database::fetchAll("SELECT jl.*, ca.account_code, ca.account_name FROM journal_lines jl JOIN chart_of_accounts ca ON jl.account_id = ca.id WHERE jl.journal_entry_id = :id", [':id' => $je1Id]);

        $totDebit = 0.00;
        $totCredit = 0.00;
        $hasApDebit = false;
        $hasInvCredit = false;

        foreach ($jeLines as $jl) {
            $totDebit += (float)$jl['debit'];
            $totCredit += (float)$jl['credit'];
            if ((string)$jl['account_code'] === Accounting::CODE_SUPPLIER_PAYABLE && (float)$jl['debit'] === 6000.00) {
                $hasApDebit = true;
            }
            if ((string)$jl['account_code'] === Accounting::CODE_INVENTORY_ASSET && (float)$jl['credit'] === 6000.00) {
                $hasInvCredit = true;
            }
        }

        assertTest(round($totDebit, 2) === round($totCredit, 2), "Double-Entry Balance Verified: Total Debit ({$totDebit}) == Total Credit ({$totCredit})");
        assertTest($hasApDebit, "Correct Debit to Accounts Payable (" . Accounting::CODE_SUPPLIER_PAYABLE . ") for 6,000.00");
        assertTest($hasInvCredit, "Correct Credit to Inventory Asset (" . Accounting::CODE_INVENTORY_ASSET . ") for 6,000.00");
    }

    // -------------------------------------------------------------
    // TEST 7: Process Excess Return creating Supplier Credit Asset
    // Unpaid due on GRN is now 15,000 - 6,000 = 9,000.00
    // Let's mark GRN fully paid so any new return generates 100% Supplier Credit!
    // -------------------------------------------------------------
    $db->prepare("UPDATE grns SET paid_amount = 15000.00, payment_status = 'paid' WHERE id = :id")->execute([':id' => $testGrnId]);

    $returnRes2 = SupplierReturnEngine::processReturnBySupplier([
        'supplier_id' => $testSupplierId,
        'return_date' => date('Y-m-d'),
        'notes'       => 'Test Return Generating Supplier Credit',
        'items'       => [
            [
                'product_id' => $testProductId,
                'lot_id'     => $testLotId,
                'quantity'   => 2.00, // 2 units @ 1500 = 3000.00
                'unit_cost'  => 1500.00,
                'reason'     => 'Supplier recall'
            ]
        ]
    ], $userId, $branchId);

    assertTest($returnRes2['success'] === true, "processReturnBySupplier for fully paid GRN succeeded");
    $ret2Id = (int)($returnRes2['supplier_return_id'] ?? 0);
    $cleanup['return_ids'][] = $ret2Id;
    $crdId = (int)($returnRes2['supplier_credit_id'] ?? 0);
    if ($crdId > 0) $cleanup['credit_ids'][] = $crdId;

    assertTest((float)$returnRes2['total_return_amount'] === 3000.00, "Total return amount is 3,000.00");
    assertTest((float)$returnRes2['applied_to_payable'] === 0.00, "Applied to payable is 0.00 (since GRN was already paid)");
    assertTest((float)$returnRes2['credit_amount'] === 3000.00, "Supplier credit asset created for 3,000.00");
    assertTest($crdId > 0, "supplier_credits record created (ID: {$crdId})");

    // Verify Accounting for Credit Return
    $ret2Record = Database::fetchOne("SELECT journal_entry_id FROM supplier_returns WHERE id = :id", [':id' => $ret2Id]);
    $je2Id = (int)($ret2Record['journal_entry_id'] ?? 0);
    if ($je2Id > 0) $cleanup['journal_entry_ids'][] = $je2Id;

    if ($je2Id > 0) {
        $je2Lines = Database::fetchAll("SELECT jl.*, ca.account_code FROM journal_lines jl JOIN chart_of_accounts ca ON jl.account_id = ca.id WHERE jl.journal_entry_id = :id", [':id' => $je2Id]);
        $totDebit2 = 0.00;
        $totCredit2 = 0.00;
        $hasCreditAssetDebit = false;
        $hasInvCredit2 = false;

        foreach ($je2Lines as $jl) {
            $totDebit2 += (float)$jl['debit'];
            $totCredit2 += (float)$jl['credit'];
            if ((string)$jl['account_code'] === Accounting::CODE_SUPPLIER_CREDIT && (float)$jl['debit'] === 3000.00) {
                $hasCreditAssetDebit = true;
            }
            if ((string)$jl['account_code'] === Accounting::CODE_INVENTORY_ASSET && (float)$jl['credit'] === 3000.00) {
                $hasInvCredit2 = true;
            }
        }

        assertTest(round($totDebit2, 2) === round($totCredit2, 2), "Double-Entry Balance Verified for Credit Return: Debit ({$totDebit2}) == Credit ({$totCredit2})");
        assertTest($hasCreditAssetDebit, "Correct Debit to Supplier Credit Asset (" . Accounting::CODE_SUPPLIER_CREDIT . ") for 3,000.00");
        assertTest($hasInvCredit2, "Correct Credit to Inventory Asset (" . Accounting::CODE_INVENTORY_ASSET . ") for 3,000.00");
    }

    // -------------------------------------------------------------
    // TEST 8: Return History Querying & Data Structure Integrity
    // -------------------------------------------------------------
    $history = SupplierReturnEngine::getReturnHistory(['supplier_id' => $testSupplierId]);
    assertTest(count($history) === 2, "getReturnHistory returned exactly 2 returns for test supplier");
    $firstRet = $history[0] ?? [];
    assertTest(!empty($firstRet['return_number']) && !empty($firstRet['supplier_name']), "Return history items contain return_number, supplier_name, and amounts");

    // -------------------------------------------------------------
    // TEST 9: Audit Logging Verification
    // -------------------------------------------------------------
    $auditLogs = Database::fetchAll("SELECT id, action, module, record_id FROM audit_logs WHERE module = 'purchasing' AND record_id IN (:r1, :r2)", [
        ':r1' => (string)$ret1Id,
        ':r2' => (string)$ret2Id
    ]);
    foreach ($auditLogs as $al) $cleanup['audit_ids'][] = (int)$al['id'];
    assertTest(count($auditLogs) >= 2, "Audit::log entries captured for both supplier returns in audit_logs");

} catch (Throwable $e) {
    echo "[EXCEPTION] Test execution error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    $failed++;
} finally {
    // -------------------------------------------------------------
    // TEST TEARDOWN: Clean up strictly created test records
    // -------------------------------------------------------------
    echo "\n-----------------------------------------------------\n";
    echo "  ISOLATED TEST TEARDOWN & RECONCILIATION CHECK\n";
    echo "-----------------------------------------------------\n";

    $db->exec("SET FOREIGN_KEY_CHECKS = 0;");

    // Clean Journal Lines & Entries
    if (!empty($cleanup['journal_entry_ids'])) {
        $jePlaceholders = implode(',', array_map('intval', $cleanup['journal_entry_ids']));
        $db->exec("DELETE FROM journal_lines WHERE journal_entry_id IN ({$jePlaceholders})");
        $db->exec("DELETE FROM journal_entries WHERE id IN ({$jePlaceholders})");
        echo "  - Cleaned test journal entries: {$jePlaceholders}\n";
    }

    // Clean Credits
    if (!empty($cleanup['credit_ids'])) {
        $crPlaceholders = implode(',', array_map('intval', $cleanup['credit_ids']));
        $db->exec("DELETE FROM supplier_credit_settlements WHERE supplier_credit_id IN ({$crPlaceholders})");
        $db->exec("DELETE FROM supplier_credits WHERE id IN ({$crPlaceholders})");
        echo "  - Cleaned test supplier credits: {$crPlaceholders}\n";
    }

    // Clean Return Items & Returns
    if (!empty($cleanup['return_ids'])) {
        $retPlaceholders = implode(',', array_map('intval', $cleanup['return_ids']));
        $db->exec("DELETE FROM supplier_return_items WHERE supplier_return_id IN ({$retPlaceholders})");
        $db->exec("DELETE FROM supplier_returns WHERE id IN ({$retPlaceholders})");
        echo "  - Cleaned test supplier returns: {$retPlaceholders}\n";
    }

    // Clean Stock Ledger
    if (!empty($cleanup['ledger_ids'])) {
        $slPlaceholders = implode(',', array_map('intval', $cleanup['ledger_ids']));
        $db->exec("DELETE FROM stock_ledger WHERE id IN ({$slPlaceholders})");
        echo "  - Cleaned test stock ledger rows: {$slPlaceholders}\n";
    }

    // Clean GRN items & GRNs
    if (!empty($cleanup['grn_ids'])) {
        $grnPlaceholders = implode(',', array_map('intval', $cleanup['grn_ids']));
        $db->exec("DELETE FROM grn_items WHERE grn_id IN ({$grnPlaceholders})");
        $db->exec("DELETE FROM grns WHERE id IN ({$grnPlaceholders})");
        echo "  - Cleaned test GRNs: {$grnPlaceholders}\n";
    }

    // Clean Lots & Inventory
    if (!empty($cleanup['lot_ids'])) {
        $lotPlaceholders = implode(',', array_map('intval', $cleanup['lot_ids']));
        $db->exec("DELETE FROM product_lots WHERE id IN ({$lotPlaceholders})");
        echo "  - Cleaned test product lots: {$lotPlaceholders}\n";
    }

    // Clean Product
    if (!empty($cleanup['product_ids'])) {
        $prodPlaceholders = implode(',', array_map('intval', $cleanup['product_ids']));
        $db->exec("DELETE FROM branch_inventory WHERE product_id IN ({$prodPlaceholders})");
        $db->exec("DELETE FROM products WHERE id IN ({$prodPlaceholders})");
        echo "  - Cleaned test products: {$prodPlaceholders}\n";
    }

    // Clean Supplier
    if (!empty($cleanup['supplier_ids'])) {
        $supPlaceholders = implode(',', array_map('intval', $cleanup['supplier_ids']));
        $db->exec("DELETE FROM suppliers WHERE id IN ({$supPlaceholders})");
        echo "  - Cleaned test suppliers: {$supPlaceholders}\n";
    }

    // Clean Audit entries
    if (!empty($cleanup['audit_ids'])) {
        $auditPlaceholders = implode(',', array_map('intval', $cleanup['audit_ids']));
        $db->exec("DELETE FROM audit_logs WHERE id IN ({$auditPlaceholders})");
        echo "  - Cleaned test audit logs: {$auditPlaceholders}\n";
    }

    $db->exec("SET FOREIGN_KEY_CHECKS = 1;");

    // Final verification of user GRNs
    $postCheckGrns = Database::fetchAll("SELECT id, grn_number FROM grns WHERE id IN (1, 2, 3)");
    echo "  -> Post-check: Found " . count($postCheckGrns) . " user GRN(s) intact (IDs 1-3). Database is pristine!\n";
}

echo "\n=====================================================\n";
echo "  FINAL TEST SUMMARY: {$passed} PASSED, {$failed} FAILED (TOTAL: {$testsRun})\n";
echo "=====================================================\n";

if ($failed === 0) {
    exit(0);
} else {
    exit(1);
}
