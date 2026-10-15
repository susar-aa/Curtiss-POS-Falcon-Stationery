<?php
/**
 * Curtiss POS - Comprehensive CLI Test Suite for Product Expiry Date & Batch Management System
 * 
 * Verifies:
 * 1. Product Master Expiry Toggle (has_expiry = 1 / 0)
 * 2. Opening Stock Multi-Batch Support & COA Double-Entry (Dr 1200, Cr 3010)
 * 3. GRN Receiving with Expiry Dates & Batch References
 * 4. FEFO (First Expiring, First Out) Lot Ordering & Expired Lot Exclusion
 * 5. POS Sale Prevention for Expired Batches
 * 6. ExpiryManager & Expired Stock Write-Off to Damaged Stock (Dr 1250, Cr 1200)
 * 7. Reports Center: 5 Expiry & Batch Reports Queries
 * 8. Cleanup of isolated test records (No Table Truncation)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Audit.php';
require_once __DIR__ . '/../core/Product.php';
require_once __DIR__ . '/../core/Inventory.php';
require_once __DIR__ . '/../core/GRN.php';
require_once __DIR__ . '/../core/Sale.php';
require_once __DIR__ . '/../core/AdvancedInventory.php';
require_once __DIR__ . '/../core/ExpiryManager.php';
require_once __DIR__ . '/../core/Accounting.php';

function assertTest(bool $condition, string $message): void {
    if (!$condition) {
        echo "[-] FAIL: $message\n";
        exit(1);
    }
    echo "[+] PASS: $message\n";
}

echo "=== 1. Setting up Test Session Environment ===\n";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['full_name'] = 'System Administrator';
$_SESSION['role_id'] = 1;
$_SESSION['role_code'] = ROLE_SUPER_ADMIN;
$_SESSION['role_name'] = 'Super Administrator';
$_SESSION['branch_id'] = 1;
$_SESSION['active_branch_id'] = 1;
$_SESSION['active_branch_name'] = 'Main Branch - Kandy';

$branchId = (int)($_SESSION['branch_id'] ?? 1);
assertTest($branchId === 1, "Active test branch resolved to 1");

// Track test record IDs for safe targeted cleanup
$cleanup = [
    'product_ids' => [],
    'lot_ids' => [],
    'grn_ids' => [],
    'damaged_ids' => [],
    'journal_ids' => []
];

// Helper to get active category, brand, supplier
$testCat = Database::fetchOne("SELECT id FROM categories WHERE deleted_at IS NULL LIMIT 1");
$testBrand = Database::fetchOne("SELECT id FROM brands WHERE deleted_at IS NULL LIMIT 1");
$testSupplier = Database::fetchOne("SELECT id FROM suppliers WHERE deleted_at IS NULL LIMIT 1");

$catId = $testCat ? (int)$testCat['id'] : 1;
$brandId = $testBrand ? (int)$testBrand['id'] : 1;
$supplierId = $testSupplier ? (int)$testSupplier['id'] : 1;

try {
    echo "\n=== 2. Testing Product Master Creation with Expiry (Single Batch Opening Stock) ===\n";
    $testSku1 = 'TEST-EXP-01-' . time();
    $futureExpiry1 = date('Y-m-d', strtotime('+90 days'));
    $prodData1 = [
        'name' => 'CLI Test Milk 1L',
        'sku' => $testSku1,
        'barcode' => 'BC-' . time() . '1',
        'category_id' => $catId,
        'brand_id' => $brandId,
        'supplier_id' => $supplierId,
        'cost' => 250.00,
        'selling_price' => 320.00,
        'has_expiry' => 1,
        'opening_stock' => 10,
        'expiry_date' => $futureExpiry1,
        'batch_number' => 'BATCH-M01',
        'branch_id' => $branchId
    ];

    $prod1Res = Product::create($prodData1);
    assertTest(!empty($prod1Res['success']) && !empty($prod1Res['id']), "Product created with ID: " . ($prod1Res['id'] ?? 0));
    $prod1Id = (int)$prod1Res['id'];
    $cleanup['product_ids'][] = $prod1Id;

    // Verify product row has has_expiry = 1
    $dbProd1 = Database::fetchOne("SELECT * FROM products WHERE id = :id", [':id' => $prod1Id]);
    assertTest((int)$dbProd1['has_expiry'] === 1, "products.has_expiry is 1 for expiry-tracked product");

    // Verify product_lots has expiry_date and batch_number
    $lot1 = Database::fetchOne("SELECT * FROM product_lots WHERE product_id = :pid AND branch_id = :bid", [
        ':pid' => $prod1Id,
        ':bid' => $branchId
    ]);
    assertTest(!empty($lot1), "Stock lot created in product_lots");
    $cleanup['lot_ids'][] = (int)$lot1['id'];
    assertTest($lot1['expiry_date'] === $futureExpiry1, "product_lots.expiry_date correctly saved as $futureExpiry1");
    assertTest($lot1['batch_number'] === 'BATCH-M01', "product_lots.batch_number correctly saved as BATCH-M01");
    assertTest($lot1['source_type'] === 'opening_stock', "product_lots.source_type is 'opening_stock'");

    // Verify double-entry GL journal entry (Dr 1200 Inventory, Cr 3010 Equity)
    $journal1 = Database::fetchOne("
        SELECT j.* FROM journal_entries j
        WHERE j.source_type = 'opening_balance' AND j.source_id = :pid
        ORDER BY j.id DESC LIMIT 1
    ", [':pid' => $prod1Id]);
    assertTest(!empty($journal1), "Double-entry journal entry found for opening stock (Entry #" . ($journal1['entry_number'] ?? '') . ")");
    if ($journal1) {
        $cleanup['journal_ids'][] = (int)$journal1['id'];
        assertTest((float)$journal1['total_debit'] === (float)$journal1['total_credit'], "Opening stock journal entry is balanced (Debit == Credit)");
        assertTest((float)$journal1['total_debit'] === 2500.00, "Opening stock journal amount equals 10 * 250 = 2500.00 LKR");
    }

    echo "\n=== 3. Testing Product Creation with Multi-Batch Opening Stock ===\n";
    $testSku2 = 'TEST-EXP-02-' . time();
    $futureExpiry2A = date('Y-m-d', strtotime('+15 days'));
    $futureExpiry2B = date('Y-m-d', strtotime('+60 days'));

    $prodData2 = [
        'name' => 'CLI Test Yogurt Multi-Batch',
        'sku' => $testSku2,
        'barcode' => 'BC-' . time() . '2',
        'category_id' => $catId,
        'brand_id' => $brandId,
        'supplier_id' => $supplierId,
        'cost' => 80.00,
        'selling_price' => 110.00,
        'has_expiry' => 1,
        'branch_id' => $branchId,
        'opening_batches' => [
            [
                'quantity' => 15,
                'cost' => 80.00,
                'selling_price' => 110.00,
                'expiry_date' => $futureExpiry2A,
                'batch_number' => 'BATCH-YOG-A'
            ],
            [
                'quantity' => 25,
                'cost' => 82.00,
                'selling_price' => 115.00,
                'expiry_date' => $futureExpiry2B,
                'batch_number' => 'BATCH-YOG-B'
            ]
        ]
    ];

    $prod2Res = Product::create($prodData2);
    assertTest(!empty($prod2Res['success']) && !empty($prod2Res['id']), "Product 2 created with multi-batch opening stock");
    $prod2Id = (int)$prod2Res['id'];
    $cleanup['product_ids'][] = $prod2Id;

    $lots2 = Database::fetchAll("SELECT * FROM product_lots WHERE product_id = :pid AND branch_id = :bid ORDER BY id ASC", [
        ':pid' => $prod2Id,
        ':bid' => $branchId
    ]);
    assertTest(count($lots2) === 2, "2 distinct stock lots created without lot merging");
    foreach ($lots2 as $l) {
        $cleanup['lot_ids'][] = (int)$l['id'];
    }
    assertTest($lots2[0]['expiry_date'] === $futureExpiry2A && $lots2[0]['batch_number'] === 'BATCH-YOG-A', "Batch A expiry & batch correctly stored");
    assertTest($lots2[1]['expiry_date'] === $futureExpiry2B && $lots2[1]['batch_number'] === 'BATCH-YOG-B', "Batch B expiry & batch correctly stored");

    // Total branch inventory quantity check
    $inv2 = Database::fetchOne("SELECT quantity FROM branch_inventory WHERE product_id = :pid AND branch_id = :bid", [
        ':pid' => $prod2Id,
        ':bid' => $branchId
    ]);
    assertTest((float)$inv2['quantity'] === 40.0, "Total branch inventory quantity is 15 + 25 = 40 units");

    echo "\n=== 4. Testing Standard Non-Expiry Product Creation (Backward Compatibility) ===\n";
    $testSku3 = 'TEST-STD-03-' . time();
    $prodData3 = [
        'name' => 'CLI Test Notebook (Standard)',
        'sku' => $testSku3,
        'barcode' => 'BC-' . time() . '3',
        'category_id' => $catId,
        'brand_id' => $brandId,
        'supplier_id' => $supplierId,
        'cost' => 120.00,
        'selling_price' => 180.00,
        'has_expiry' => 0,
        'opening_stock' => 5,
        'branch_id' => $branchId
    ];

    $prod3Res = Product::create($prodData3);
    assertTest(!empty($prod3Res['success']) && !empty($prod3Res['id']), "Standard product created");
    $prod3Id = (int)$prod3Res['id'];
    $cleanup['product_ids'][] = $prod3Id;

    $dbProd3 = Database::fetchOne("SELECT has_expiry FROM products WHERE id = :id", [':id' => $prod3Id]);
    assertTest((int)$dbProd3['has_expiry'] === 0, "products.has_expiry is 0 for standard item");

    $lot3 = Database::fetchOne("SELECT * FROM product_lots WHERE product_id = :pid AND branch_id = :bid", [
        ':pid' => $prod3Id,
        ':bid' => $branchId
    ]);
    if ($lot3) $cleanup['lot_ids'][] = (int)$lot3['id'];
    assertTest($lot3['expiry_date'] === null, "product_lots.expiry_date is NULL for standard item");

    echo "\n=== 5. Testing GRN Receiving with Expiry & Batch Details ===\n";
    $grnExpiry = date('Y-m-d', strtotime('+120 days'));
    $grnHeader = [
        'branch_id' => $branchId,
        'supplier_id' => $supplierId,
        'po_id' => null,
        'supplier_invoice_no' => 'INV-EXP-' . time(),
        'supplier_invoice_date' => date('Y-m-d'),
        'received_date' => date('Y-m-d'),
        'supplier_invoice_amount' => 5100.00,
        'difference_reason' => 'None',
        'notes' => 'CLI Expiry GRN Test'
    ];
    $grnItems = [
        [
            'product_id' => $prod1Id,
            'quantity' => 20,
            'unit_cost' => 255.00,
            'selling_price' => 330.00,
            'expiry_date' => $grnExpiry,
            'batch_number' => 'BATCH-GRN-101'
        ]
    ];

    $grnRes = GRN::create($grnHeader, $grnItems);
    $grnId = (int)($grnRes['grn_id'] ?? $grnRes['id'] ?? 0);
    assertTest(!empty($grnRes['success']) && $grnId > 0, "GRN created with ID: " . $grnId . " (Message: " . ($grnRes['message'] ?? '') . ")");
    $cleanup['grn_ids'][] = $grnId;

    // Verify grn_items has expiry_date and batch_number
    $grnItem = Database::fetchOne("SELECT * FROM grn_items WHERE grn_id = :gid", [':gid' => $grnId]);
    assertTest($grnItem['expiry_date'] === $grnExpiry, "grn_items.expiry_date saved as $grnExpiry");
    assertTest($grnItem['batch_number'] === 'BATCH-GRN-101', "grn_items.batch_number saved as BATCH-GRN-101");

    // Approve GRN and verify stock lot created
    $approveRes = GRN::approve($grnId, 1);
    assertTest(!empty($approveRes['success']), "GRN approved successfully: " . ($approveRes['message'] ?? ''));

    $grnLot = Database::fetchOne("
        SELECT * FROM product_lots 
        WHERE product_id = :pid AND source_type = 'grn' AND source_id = :gid
    ", [
        ':pid' => $prod1Id,
        ':gid' => $grnId
    ]);
    assertTest(!empty($grnLot), "GRN stock lot generated in product_lots");
    if ($grnLot) {
        $cleanup['lot_ids'][] = (int)$grnLot['id'];
        assertTest($grnLot['expiry_date'] === $grnExpiry, "GRN lot expiry date correctly matches: $grnExpiry");
        assertTest($grnLot['batch_number'] === 'BATCH-GRN-101', "GRN lot batch number correctly matches: BATCH-GRN-101");
        assertTest((float)$grnLot['remaining_quantity'] === 20.0, "GRN lot quantity is 20 units");
    }

    $grnJournal = Database::fetchOne("SELECT id FROM journal_entries WHERE source_type = 'grn' AND source_id = :gid", [':gid' => $grnId]);
    if ($grnJournal) {
        $cleanup['journal_ids'][] = (int)$grnJournal['id'];
    }

    echo "\n=== 6. Testing FEFO Lot Ordering in POS (Sale::getAvailableLotsForProduct) ===\n";
    // We have prod1 with:
    // Lot 1: Expiry in +90 days (from opening stock)
    // Lot 2: Expiry in +120 days (from GRN)
    // Add Lot 3: Expiry in +10 days
    $lotNearRes = Inventory::addStockLot(
        $branchId,
        $prod1Id,
        240.00,
        310.00,
        5.0,
        null,
        'ADJ-NEAR',
        'Near expiry test',
        1,
        'adjustment',
        date('Y-m-d', strtotime('+10 days')),
        'BATCH-SOON',
        'adjustment'
    );
    $lotNearId = (int)$lotNearRes['lot_id'];
    $cleanup['lot_ids'][] = $lotNearId;

    // Create an EXPIRED Lot 4: Expiry 2021-01-01
    $lotExpRes = Inventory::addStockLot(
        $branchId,
        $prod1Id,
        200.00,
        300.00,
        3.0,
        null,
        'ADJ-EXP',
        'Expired test lot',
        1,
        'adjustment',
        '2021-01-01',
        'BATCH-EXPIRED-TEST',
        'adjustment'
    );
    $lotExpiredId = (int)$lotExpRes['lot_id'];
    $cleanup['lot_ids'][] = $lotExpiredId;

    $availableLots = Sale::getAvailableLotsForProduct($prod1Id, $branchId);
    assertTest(count($availableLots) > 0, "Available lots retrieved for POS");

    // Verify FEFO: First lot must be BATCH-SOON (+10 days), before +90 days and +120 days
    $firstLot = $availableLots[0];
    assertTest($firstLot['batch_number'] === 'BATCH-SOON', "FEFO Check: Earliest expiring batch (BATCH-SOON) is listed first! Actual: " . $firstLot['batch_number']);

    // Verify EXPIRED Lot is COMPLETELY EXCLUDED from Sale::getAvailableLotsForProduct
    $foundExpiredInPOS = false;
    foreach ($availableLots as $al) {
        if ((int)$al['id'] === $lotExpiredId) {
            $foundExpiredInPOS = true;
            break;
        }
    }
    assertTest($foundExpiredInPOS === false, "POS Safety Check: Expired batch (ID $lotExpiredId) is strictly EXCLUDED from available lots!");

    echo "\n=== 7. Testing POS Sale Blocking for Expired Batches ===\n";
    // Attempt to prepare draft sale explicitly specifying the expired lot
    $draftPayload = [
        'branch_id' => $branchId,
        'customer_id' => 1,
        'items' => [
            [
                'product_id' => $prod1Id,
                'lot_id' => $lotExpiredId,
                'quantity' => 1,
                'unit_price' => 300.00
            ]
        ],
        'discount_value' => ''
    ];
    $draftRes = Sale::prepareDraftSale($draftPayload, 1, $branchId);
    $blocked = (!$draftRes['success'] && stripos($draftRes['message'] ?? '', 'expired') !== false);
    assertTest($blocked === true, "POS prepareDraftSale strictly blocked expired lot sale! Message: " . ($draftRes['message'] ?? ''));

    echo "\n=== 8. Testing ExpiryManager & Expired Stock Write-Off (COA Dr 1250 / Cr 1200) ===\n";
    $summaryBefore = ExpiryManager::getExpirySummary($branchId);
    assertTest($summaryBefore['expired_count'] >= 1, "ExpiryManager detected expired lot count: " . $summaryBefore['expired_count']);
    assertTest($summaryBefore['expired_quantity'] >= 3.0, "ExpiryManager detected expired units: " . $summaryBefore['expired_quantity']);

    // Execute Write-Off for the expired lot (3.0 units)
    $writeOffResult = ExpiryManager::writeOffLot(
        $lotExpiredId,
        $branchId,
        3.0,
        'CLI Audit Write-Off Test',
        1 // admin user
    );
    assertTest(!empty($writeOffResult['success']), "ExpiryManager::writeOffLot succeeded");
    $damagedId = (int)($writeOffResult['id'] ?? $writeOffResult['damaged_id'] ?? 0);
    assertTest($damagedId > 0, "Damaged stock ID generated: $damagedId");
    $cleanup['damaged_ids'][] = $damagedId;

    // Verify lot is now depleted
    $writtenLot = Database::fetchOne("SELECT * FROM product_lots WHERE id = :id", [':id' => $lotExpiredId]);
    assertTest((float)$writtenLot['remaining_quantity'] === 0.0, "Written-off lot remaining quantity is 0");
    assertTest($writtenLot['status'] === 'depleted', "Written-off lot status is 'depleted'");

    // Verify damaged_stock entry created with stock_type = 'expired'
    $dmgRecord = Database::fetchOne("SELECT * FROM damaged_stock WHERE id = :id", [':id' => $damagedId]);
    assertTest(!empty($dmgRecord), "damaged_stock entry created");
    assertTest($dmgRecord['stock_type'] === 'expired', "damaged_stock.stock_type is 'expired'");
    assertTest((float)$dmgRecord['quantity'] === 3.0, "damaged_stock quantity is 3.0 units");
    assertTest((float)$dmgRecord['cost'] === 200.00, "damaged_stock cost is 200.00");
    assertTest((float)($dmgRecord['quantity'] * $dmgRecord['cost']) === 600.00, "damaged_stock total loss is 600.00 LKR");

    // Verify COA double-entry: Dr 1250 (Damaged/Loss), Cr 1200 (Inventory Asset)
    $journalWriteOff = Database::fetchOne("
        SELECT j.* FROM journal_entries j
        WHERE j.id = :jid
    ", [':jid' => $dmgRecord['journal_entry_id']]);
    assertTest(!empty($journalWriteOff), "COA Journal Entry created for write-off (Entry #" . ($journalWriteOff['entry_number'] ?? '') . ")");
    if ($journalWriteOff) {
        $cleanup['journal_ids'][] = (int)$journalWriteOff['id'];
        assertTest((float)$journalWriteOff['total_debit'] === 600.00, "Journal debit is 600.00 LKR");
        assertTest((float)$journalWriteOff['total_credit'] === 600.00, "Journal credit is 600.00 LKR");

        // Verify lines
        $lines = Database::fetchAll("SELECT * FROM journal_lines WHERE journal_entry_id = :jid ORDER BY id ASC", [
            ':jid' => $journalWriteOff['id']
        ]);
        assertTest(count($lines) === 2, "2 balanced journal lines found");
        $drLine = $lines[0]['debit'] > 0 ? $lines[0] : $lines[1];
        $crLine = $lines[0]['credit'] > 0 ? $lines[0] : $lines[1];

        // Dr Account 1250 (Damaged Stock Loss), Cr Account 1200 (Inventory Asset)
        $drAccount = Database::fetchOne("SELECT account_code FROM chart_of_accounts WHERE id = :id", [':id' => $drLine['account_id']]);
        $crAccount = Database::fetchOne("SELECT account_code FROM chart_of_accounts WHERE id = :id", [':id' => $crLine['account_id']]);
        assertTest($drAccount['account_code'] === '1250', "COA Debit is Account 1250 (Damaged Stock Loss)");
        assertTest($crAccount['account_code'] === '1200', "COA Credit is Account 1200 (Inventory Asset)");
    }

    echo "\n=== 9. Testing Expiry Reports Engine Queries ===\n";
    // 1. Expired Stock Report
    $expRep = ExpiryManager::getExpiredStockReport($branchId, [], 1, 100);
    assertTest(isset($expRep['items']) && isset($expRep['summary']), "getExpiredStockReport returns items and summary");

    // 2. Near Expiry Report
    $nearRep = ExpiryManager::getNearExpiryReport($branchId, 30, [], 1, 100);
    assertTest(isset($nearRep['items']) && isset($nearRep['summary']), "getNearExpiryReport returns items and summary");
    assertTest($nearRep['summary']['window_days'] === 30, "Near expiry report window days is 30");

    // 3. Batch Expiry Audit Report
    $auditRep = ExpiryManager::getBatchExpiryAuditReport($branchId, [], 1, 100);
    assertTest(isset($auditRep['items']) && isset($auditRep['summary']), "getBatchExpiryAuditReport returns items and summary");
    assertTest($auditRep['summary']['total_lots'] > 0, "Audit report returned active lots");

    // 4. Expired Write-Off History Report
    $histRep = ExpiryManager::getExpiredWriteOffHistoryReport($branchId, [], 1, 100);
    assertTest(isset($histRep['items']) && isset($histRep['summary']), "getExpiredWriteOffHistoryReport returns items and summary");
    assertTest($histRep['summary']['total_records'] >= 1, "Write-off history contains our written-off record");

    // 5. Supplier Expiry Performance Report
    $suppRep = ExpiryManager::getSupplierExpiryPerformanceReport($branchId);
    assertTest(is_array($suppRep), "getSupplierExpiryPerformanceReport returns supplier performance list");

    echo "\n=== 10. Clean Up Isolated Test Records ===\n";
} finally {
    // Clean up journal lines and entries
    if (!empty($cleanup['journal_ids'])) {
        foreach ($cleanup['journal_ids'] as $jid) {
            Database::execute("DELETE FROM journal_lines WHERE journal_entry_id = :jid", [':jid' => $jid]);
            Database::execute("DELETE FROM journal_entries WHERE id = :jid", [':jid' => $jid]);
        }
        echo "[+] Cleaned up " . count($cleanup['journal_ids']) . " test journal entries.\n";
    }

    // Clean up damaged_stock
    if (!empty($cleanup['damaged_ids'])) {
        foreach ($cleanup['damaged_ids'] as $did) {
            Database::execute("DELETE FROM damaged_stock WHERE id = :did", [':did' => $did]);
        }
        echo "[+] Cleaned up " . count($cleanup['damaged_ids']) . " test damaged_stock records.\n";
    }

    // Clean up GRN
    if (!empty($cleanup['grn_ids'])) {
        foreach ($cleanup['grn_ids'] as $gid) {
            Database::execute("DELETE FROM grn_items WHERE grn_id = :gid", [':gid' => $gid]);
            Database::execute("DELETE FROM grns WHERE id = :gid", [':gid' => $gid]);
        }
        echo "[+] Cleaned up " . count($cleanup['grn_ids']) . " test GRN records.\n";
    }

    // Clean up stock lots & ledger & branch_inventory for test products
    if (!empty($cleanup['product_ids'])) {
        foreach ($cleanup['product_ids'] as $pid) {
            Database::execute("DELETE FROM stock_ledger WHERE product_id = :pid", [':pid' => $pid]);
            Database::execute("DELETE FROM product_lots WHERE product_id = :pid", [':pid' => $pid]);
            Database::execute("DELETE FROM branch_inventory WHERE product_id = :pid", [':pid' => $pid]);
            Database::execute("DELETE FROM products WHERE id = :pid", [':pid' => $pid]);
        }
        echo "[+] Cleaned up " . count($cleanup['product_ids']) . " test products and associated stock records.\n";
    }
}

echo "\n======================================================\n";
echo ">>> ALL CLI TESTS COMPLETED WITH 100% PASS RATE! <<<\n";
echo "======================================================\n";
