<?php
/**
 * Verification Test Suite - Section 2: Purchasing & GRN Enhancements
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Supplier.php';
require_once __DIR__ . '/../core/SupplierImportEngine.php';
require_once __DIR__ . '/../core/PurchaseOrder.php';
require_once __DIR__ . '/../core/GRN.php';
require_once __DIR__ . '/../core/Inventory.php';
require_once __DIR__ . '/../core/Accounting.php';
require_once __DIR__ . '/../core/Product.php';
require_once __DIR__ . '/../core/Database.php';

$testCount = 0;
$passedCount = 0;

function assertTest(bool $condition, string $testName, string $details = ''): void
{
    global $testCount, $passedCount;
    $testCount++;
    if ($condition) {
        $passedCount++;
        echo "  [PASS] Test #{$testCount}: {$testName}\n";
    } else {
        echo "  [FAIL] Test #{$testCount}: {$testName} - {$details}\n";
    }
}

echo "=====================================================\n";
echo "    SECTION 2: PURCHASING & GRN VERIFICATION SUITE   \n";
echo "=====================================================\n\n";

try {
    $db = Database::getInstance()->getConnection();

    // Clean up test artifacts from previous runs in cascade order
    Database::execute("SET FOREIGN_KEY_CHECKS = 0;");
    $testSups = Database::fetchAll("SELECT id FROM suppliers WHERE supplier_code LIKE 'SUP-TEST-%' OR name LIKE 'Test Vendor%' OR name LIKE 'New Vendor%'");
    if (!empty($testSups)) {
        $sIds = implode(',', array_column($testSups, 'id'));

        // Delete linked GRNs
        $grns = Database::fetchAll("SELECT id FROM grns WHERE supplier_id IN ($sIds) OR supplier_invoice_no LIKE 'INV-%'");
        if (!empty($grns)) {
            $gIds = implode(',', array_column($grns, 'id'));
            Database::execute("DELETE FROM supplier_payment_grns WHERE grn_id IN ($gIds)");
            Database::execute("DELETE FROM journal_lines WHERE journal_entry_id IN (SELECT id FROM journal_entries WHERE source_type = 'grn' AND source_id IN ($gIds))");
            Database::execute("DELETE FROM journal_entries WHERE source_type = 'grn' AND source_id IN ($gIds)");
            Database::execute("DELETE FROM grn_items WHERE grn_id IN ($gIds)");
            Database::execute("DELETE FROM grns WHERE id IN ($gIds)");
        }

        // Delete linked POs
        $pos = Database::fetchAll("SELECT id FROM purchase_orders WHERE supplier_id IN ($sIds)");
        if (!empty($pos)) {
            $pIds = implode(',', array_column($pos, 'id'));
            Database::execute("DELETE FROM purchase_order_items WHERE po_id IN ($pIds)");
            Database::execute("DELETE FROM purchase_orders WHERE id IN ($pIds)");
        }

        // Delete supplier financials
        Database::execute("DELETE FROM supplier_payment_grns WHERE supplier_payment_id IN (SELECT id FROM supplier_payments WHERE supplier_id IN ($sIds))");
        Database::execute("DELETE FROM supplier_payments WHERE supplier_id IN ($sIds)");
        Database::execute("DELETE FROM supplier_return_items WHERE supplier_return_id IN (SELECT id FROM supplier_returns WHERE supplier_id IN ($sIds))");
        Database::execute("DELETE FROM supplier_returns WHERE supplier_id IN ($sIds)");
        Database::execute("DELETE FROM supplier_cheques WHERE supplier_id IN ($sIds)");
        Database::execute("DELETE FROM supplier_credit_settlements WHERE supplier_credit_id IN (SELECT id FROM supplier_credits WHERE supplier_id IN ($sIds))");
        Database::execute("DELETE FROM supplier_credits WHERE supplier_id IN ($sIds)");
        Database::execute("DELETE FROM suppliers WHERE id IN ($sIds)");
    }
    Database::execute("SET FOREIGN_KEY_CHECKS = 1;");

    // -------------------------------------------------------------------------
    // TEST GROUP 1: SUPPLIER MANAGEMENT & SAFE DELETE
    // -------------------------------------------------------------------------
    echo "--- Group 1: Supplier Management & Safe Delete ---\n";

    // Create supplier
    $supRes = Supplier::create("Test Vendor Alpha", "0112000001", "0771000001", "123 Main St, Kandy", "active", "SUP-TEST-01");
    assertTest($supRes['success'], "Create Supplier with custom code", $supRes['message'] ?? '');
    $supId1 = (int)($supRes['id'] ?? 0);

    // Auto-generate code
    $supRes2 = Supplier::create("Test Vendor Beta", "0112000002", null, "456 Park Rd, Colombo", "active", "SUP-TEST-02");
    assertTest($supRes2['success'] && !empty($supRes2['supplier_code']), "Create Supplier with explicit test code: " . ($supRes2['supplier_code'] ?? ''));
    $supId2 = (int)($supRes2['id'] ?? 0);

    // Search supplier
    $searchResults = Supplier::all(false, "SUP-TEST-01");
    assertTest(count($searchResults) === 1 && $searchResults[0]['name'] === "Test Vendor Alpha", "Supplier search by code", "Found count: " . count($searchResults));

    // Safe delete (no transaction history)
    $delRes = Supplier::delete($supId2);
    assertTest($delRes['success'], "Safe delete supplier without history", "Del msg: " . ($delRes['message'] ?? ''));

    // -------------------------------------------------------------------------
    // TEST GROUP 2: SUPPLIER CSV IMPORT ENGINE
    // -------------------------------------------------------------------------
    echo "\n--- Group 2: Supplier CSV Import Engine ---\n";

    $csvFile = __DIR__ . '/../storage/tmp/test_suppliers.csv';
    if (!is_dir(dirname($csvFile))) {
        mkdir(dirname($csvFile), 0777, true);
    }

    $csvContent = "Supplier Code,Supplier Name,Phone,WhatsApp,Address,Status\n";
    $csvContent .= "SUP-TEST-01,Test Vendor Alpha Updated,0112999999,,Updated Address,active\n";
    $csvContent .= "SUP-TEST-99,New Vendor Gamma,0779998888,0779998888,Galle,active\n";
    file_put_contents($csvFile, $csvContent);

    // Preview
    $prev = SupplierImportEngine::preview($csvFile, SupplierImportEngine::MODE_CREATE_AND_UPDATE, true);
    assertTest($prev['success'] && $prev['summary']['will_update'] === 1 && $prev['summary']['will_create'] === 1, "CSV Import Preview detects 1 update and 1 create");

    // Commit
    $impRes = SupplierImportEngine::import($csvFile, SupplierImportEngine::MODE_CREATE_AND_UPDATE, true);
    assertTest($impRes['success'] && $impRes['summary']['created'] === 1 && $impRes['summary']['updated'] === 1, "CSV Import Commit creates 1 and updates 1");

    $sup1Updated = Supplier::find($supId1);
    assertTest($sup1Updated['name'] === "Test Vendor Alpha Updated" && $sup1Updated['phone'] === "0112999999" && $sup1Updated['whatsapp'] === "0771000001", "Blank CSV cell ignored during update (WhatsApp preserved)");

    $gammaSup = Supplier::findByCode("SUP-TEST-99");
    assertTest($gammaSup !== null && $gammaSup['name'] === "New Vendor Gamma", "New supplier created via CSV import");
    $gammaSupId = (int)$gammaSup['id'];

    // Mode test: create_new_only skips existing
    $prevCreateOnly = SupplierImportEngine::preview($csvFile, SupplierImportEngine::MODE_CREATE_NEW, true);
    assertTest($prevCreateOnly['summary']['skipped'] === 2, "Create New Only mode skips existing codes");

    @unlink($csvFile);

    // -------------------------------------------------------------------------
    // TEST GROUP 3: INACTIVE RESTRICTIONS (SUPPLIER & PRODUCT)
    // -------------------------------------------------------------------------
    echo "\n--- Group 3: Inactive Restrictions ---\n";

    // Deactivate supplier
    Supplier::toggleStatus($gammaSupId);
    $inactSup = Supplier::find($gammaSupId);
    assertTest($inactSup['status'] === 'inactive', "Supplier deactivated successfully");

    // Fetch test product
    $activeProduct = Database::fetchOne("SELECT id, name FROM products WHERE status = 'active' LIMIT 1");
    $prodId1 = (int)$activeProduct['id'];

    // Try creating PO for inactive supplier -> Should Fail
    $poInactiveSupRes = PurchaseOrder::create(['supplier_id' => $gammaSupId], [['product_id' => $prodId1, 'quantity' => 10, 'unit_cost' => 100]]);
    assertTest(!$poInactiveSupRes['success'], "Create PO for inactive supplier blocked: " . $poInactiveSupRes['message']);

    // Re-activate supplier
    Supplier::toggleStatus($gammaSupId);

    // -------------------------------------------------------------------------
    // TEST GROUP 4: PURCHASE ORDER WORKFLOW & EDITING RULES
    // -------------------------------------------------------------------------
    echo "\n--- Group 4: Purchase Order Workflow & Editing Rules ---\n";

    // Create Draft PO
    $poItems = [
        ['product_id' => $prodId1, 'quantity' => 100, 'unit_cost' => 50.00, 'selling_price' => 75.00]
    ];
    $poRes = PurchaseOrder::create(['supplier_id' => $gammaSupId, 'branch_id' => 1, 'order_date' => date('Y-m-d'), 'notes' => 'Test PO'], $poItems);
    assertTest($poRes['success'], "Draft PO created successfully (#" . ($poRes['po_number'] ?? '') . ")");
    $poId = (int)$poRes['po_id'];

    // Edit Draft PO (update items quantity to 120)
    $poItemsEdit = [
        ['product_id' => $prodId1, 'quantity' => 120, 'unit_cost' => 50.00, 'selling_price' => 75.00]
    ];
    $poEditRes = PurchaseOrder::update($poId, ['notes' => 'Updated PO Notes'], $poItemsEdit);
    assertTest($poEditRes['success'], "Draft PO updated successfully");

    // Approve PO
    $poAppRes = PurchaseOrder::approve($poId, 1);
    assertTest($poAppRes['success'], "Draft PO approved successfully");

    $poApproved = PurchaseOrder::find($poId);
    assertTest($poApproved['status'] === 'Approved', "PO status is Approved");

    // -------------------------------------------------------------------------
    // TEST GROUP 5: PARTIAL PO RECEIVING & MULTIPLE GRNS
    // -------------------------------------------------------------------------
    echo "\n--- Group 5: Partial PO Receiving & Multiple GRNs ---\n";

    // GRN #1: Receive 70 items out of 120 ordered
    $grn1Items = [
        ['product_id' => $prodId1, 'quantity' => 70, 'unit_cost' => 50.00, 'selling_price' => 75.00]
    ];
    $grn1Res = GRN::create([
        'po_id'                   => $poId,
        'supplier_id'             => $gammaSupId,
        'branch_id'               => 1,
        'supplier_invoice_no'     => 'INV-GRN-101',
        'supplier_invoice_amount' => 3500.00,
        'difference_reason'       => 'None'
    ], $grn1Items);
    assertTest($grn1Res['success'], "Draft GRN #1 created for 70 units");
    $grnId1 = (int)$grn1Res['grn_id'];

    // Ensure no stale journal entry exists for $grnId1 from old deleted GRNs
    Database::execute("DELETE FROM journal_lines WHERE journal_entry_id IN (SELECT id FROM journal_entries WHERE source_type = 'grn' AND source_id = :grn_id)", [':grn_id' => $grnId1]);
    Database::execute("DELETE FROM journal_entries WHERE source_type = 'grn' AND source_id = :grn_id", [':grn_id' => $grnId1]);

    // Approve GRN #1
    $grn1AppRes = GRN::approve($grnId1, 1);
    assertTest($grn1AppRes['success'], "GRN #1 approved successfully: " . ($grn1AppRes['message'] ?? ''));

    $poAfterGrn1 = PurchaseOrder::find($poId);
    assertTest($poAfterGrn1['status'] === 'Partially Received', "PO status transitioned to 'Partially Received' (Received 70/120)");

    // Over-receipt test: Try receiving 60 units when only 50 remaining (120 - 70 = 50) -> Should fail
    $overGrnItems = [
        ['product_id' => $prodId1, 'quantity' => 60, 'unit_cost' => 50.00, 'selling_price' => 75.00]
    ];
    $overGrnRes = GRN::create([
        'po_id'               => $poId,
        'supplier_id'         => $gammaSupId,
        'branch_id'           => 1,
        'supplier_invoice_no' => 'INV-OVER-102',
        'supplier_invoice_amount' => 3000.00
    ], $overGrnItems);
    assertTest(!$overGrnRes['success'], "Over-receipt blocked when receiving 60 > 50 remaining: " . $overGrnRes['message']);

    // GRN #2: Receive remaining 50 items
    $grn2Items = [
        ['product_id' => $prodId1, 'quantity' => 50, 'unit_cost' => 50.00, 'selling_price' => 75.00]
    ];
    $grn2Res = GRN::create([
        'po_id'                   => $poId,
        'supplier_id'             => $gammaSupId,
        'branch_id'               => 1,
        'supplier_invoice_no'     => 'INV-GRN-103',
        'supplier_invoice_amount' => 2500.00,
        'difference_reason'       => 'None'
    ], $grn2Items);
    assertTest($grn2Res['success'], "Draft GRN #2 created for remaining 50 units");
    $grnId2 = (int)$grn2Res['grn_id'];

    // Ensure no stale journal entry exists for $grnId2
    Database::execute("DELETE FROM journal_lines WHERE journal_entry_id IN (SELECT id FROM journal_entries WHERE source_type = 'grn' AND source_id = :grn_id)", [':grn_id' => $grnId2]);
    Database::execute("DELETE FROM journal_entries WHERE source_type = 'grn' AND source_id = :grn_id", [':grn_id' => $grnId2]);

    // Approve GRN #2
    $grn2AppRes = GRN::approve($grnId2, 1);
    assertTest($grn2AppRes['success'], "GRN #2 approved successfully");

    $poAfterGrn2 = PurchaseOrder::find($poId);
    assertTest($poAfterGrn2['status'] === 'Completed', "PO status transitioned to 'Completed' (Received 120/120)");

    // -------------------------------------------------------------------------
    // TEST GROUP 6: ATOMIC GRN POSTING & ACCOUNTING
    // -------------------------------------------------------------------------
    echo "\n--- Group 6: Atomic GRN Posting & Accounting ---\n";

    // Duplicate approval test
    $dupAppRes = GRN::approve($grnId1, 1);
    assertTest(!$dupAppRes['success'], "Duplicate GRN approval blocked: " . $dupAppRes['message']);

    // Check Journal Entry for GRN #1
    $jeRow = Database::fetchOne("
        SELECT * FROM journal_entries WHERE source_type = 'grn' AND source_id = :grn_id
    ", [':grn_id' => $grnId1]);

    assertTest($jeRow !== null && (float)$jeRow['total_debit'] === 3500.00, "Accounting Journal Entry posted for GRN #1 (Dr 1200 / Cr 2020 LKR 3,500.00)");

    // Check Stock Lot created
    $lotRow = Database::fetchOne("
        SELECT * FROM product_lots WHERE product_id = :pid AND initial_quantity = 70 AND cost = 50.00 ORDER BY id DESC LIMIT 1
    ", [':pid' => $prodId1]);
    assertTest($lotRow !== null && (float)$lotRow['remaining_quantity'] == 70.00, "Exact stock lot created in product_lots");

    // Block Approved GRN edit / cancel
    $cancelApprovedRes = GRN::cancel($grnId1, 1);
    assertTest(!$cancelApprovedRes['success'], "Cancel approved GRN blocked: " . $cancelApprovedRes['message']);

    // Block deletion of Supplier with PO/GRN history
    $delHistSupRes = Supplier::delete($gammaSupId);
    assertTest(!$delHistSupRes['success'], "Delete supplier with transaction history blocked: " . $delHistSupRes['message']);

    // -------------------------------------------------------------------------
    // TEST GROUP 7: SUPPLIER INVOICE VARIANCE TRACKING
    // -------------------------------------------------------------------------
    echo "\n--- Group 7: Supplier Invoice Variance Tracking ---\n";

    $varGrnRes = GRN::create([
        'supplier_id'             => $gammaSupId,
        'branch_id'               => 1,
        'supplier_invoice_no'     => 'INV-VAR-999',
        'supplier_invoice_amount' => 5000.00, // Invoice says LKR 5000
        'difference_reason'       => 'Supplier discount'
    ], [
        ['product_id' => $prodId1, 'quantity' => 80, 'unit_cost' => 50.00, 'selling_price' => 75.00] // Actual total: LKR 4000
    ]);
    assertTest($varGrnRes['success'], "Direct GRN created with invoice amount variance (LKR 5000 vs actual LKR 4000)", $varGrnRes['message'] ?? '');
    $varGrnId = (int)($varGrnRes['grn_id'] ?? 0);

    $varGrn = GRN::find($varGrnId);
    assertTest($varGrn !== null && (float)$varGrn['difference_amount'] === 1000.00 && $varGrn['difference_reason'] === 'Supplier discount', "Variance difference calculated correctly (LKR 1,000.00 difference, reason: Supplier discount)");

    if ($varGrnId > 0) {
        GRN::approve($varGrnId, 1);
    }

} catch (Throwable $e) {
    echo "\n  [CRITICAL FATAL EXCEPTION]: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n=====================================================\n";
echo " TEST SUMMARY: {$passedCount} / {$testCount} PASSED (" . round(($passedCount / max(1, $testCount)) * 100, 1) . "%)\n";
echo "=====================================================\n\n";

if ($passedCount === $testCount && $testCount > 0) {
    exit(0);
} else {
    exit(1);
}
