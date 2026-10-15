<?php
/**
 * CURTISS POS - Purchasing System & GRN Master CLI Test Suite
 * Strictly adheres to plan.md and AGENTS.md rules.
 * Uses isolated test fixtures and safely cleans up without touching user data.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Supplier.php';
require_once __DIR__ . '/../core/Product.php';
require_once __DIR__ . '/../core/PurchaseOrder.php';
require_once __DIR__ . '/../core/GRN.php';
require_once __DIR__ . '/../core/Inventory.php';
require_once __DIR__ . '/../core/Accounting.php';
require_once __DIR__ . '/../core/Audit.php';

echo "====================================================\n";
echo "   CURTISS POS - PURCHASING & GRN CLI TEST SUITE\n";
echo "====================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest(bool $condition, string $title, string $details = '') {
    global $passCount, $failCount;
    if ($condition) {
        $passCount++;
        echo "  [PASS] {$title}\n";
    } else {
        $failCount++;
        echo "  [FAIL] {$title}" . ($details ? " - {$details}" : "") . "\n";
    }
}

// Ensure active branch and user session for tests
$_SESSION['user_id'] = 1;
$_SESSION['role_id'] = ROLE_SUPER_ADMIN;
$_SESSION['active_branch_id'] = 1;
$_SESSION['branch_id'] = 1;

$testSuffix = time() . '_' . rand(100, 999);
$createdProductIds = [];
$createdGrnIds = [];
$createdPoIds = [];

try {
    // Fetch valid brand and category for test products
    $brand = Database::fetchOne("SELECT id FROM brands WHERE deleted_at IS NULL LIMIT 1");
    $cat = Database::fetchOne("SELECT id FROM categories WHERE deleted_at IS NULL LIMIT 1");
    $sup = Database::fetchOne("SELECT id FROM suppliers WHERE deleted_at IS NULL LIMIT 1");

    if (!$brand || !$cat || !$sup) {
        throw new Exception("Prerequisite brand, category, or supplier missing in database.");
    }

    $brandId = (int)$brand['id'];
    $catId = (int)$cat['id'];
    $supplierId = (int)$sup['id'];
    $branchId = 1;

    echo "--- 1. Setting up Isolated Test Fixtures ---\n";

    // Create Test Product A (Standard)
    $prodA = Product::create([
        'sku'                 => 'TEST-SKU-A-' . $testSuffix,
        'barcode'             => 'BAR-A-' . $testSuffix,
        'brand_id'            => $brandId,
        'category_id'         => $catId,
        'name'                => 'GRN Test Standard Product ' . $testSuffix,
        'supplier_id'         => $supplierId,
        'cost'                => 100.00,
        'selling_price'       => 150.00,
        'opening_stock'       => 0,
        'low_stock_threshold' => 5.00,
        'status'              => 'active'
    ]);
    assertTest($prodA['success'], "Create Test Product A (Standard, 0 stock)");
    $prodAId = (int)$prodA['id'];
    $createdProductIds[] = $prodAId;

    // Create Test Product B (Expiry Tracked)
    $prodB = Product::create([
        'sku'                 => 'TEST-SKU-B-' . $testSuffix,
        'barcode'             => 'BAR-B-' . $testSuffix,
        'brand_id'            => $brandId,
        'category_id'         => $catId,
        'name'                => 'GRN Test Expiry Product ' . $testSuffix,
        'supplier_id'         => $supplierId,
        'cost'                => 200.00,
        'selling_price'       => 300.00,
        'opening_stock'       => 0,
        'low_stock_threshold' => 5.00,
        'status'              => 'active',
        'has_expiry'          => 1
    ]);
    assertTest($prodB['success'], "Create Test Product B (Expiry Tracked, 0 stock)");
    $prodBId = (int)$prodB['id'];
    $createdProductIds[] = $prodBId;

    echo "\n--- 2. Testing Direct GRN Creation (Draft Status) ---\n";

    $grnHeader = [
        'po_id'                   => null,
        'supplier_id'             => $supplierId,
        'branch_id'               => $branchId,
        'supplier_invoice_no'     => 'INV-TEST-' . $testSuffix,
        'supplier_invoice_date'   => date('Y-m-d'),
        'received_date'           => date('Y-m-d'),
        'supplier_invoice_amount' => 5000.00,
        'difference_reason'       => 'None',
        'notes'                   => 'Direct test GRN creation',
        'user_id'                 => 1
    ];

    $grnItems = [
        [
            'product_id'    => $prodAId,
            'quantity'      => 10.00,
            'unit_cost'     => 100.00,
            'selling_price' => 160.00,
            'expiry_date'   => null,
            'batch_number'  => null
        ],
        [
            'product_id'    => $prodBId,
            'quantity'      => 20.00,
            'unit_cost'     => 200.00,
            'selling_price' => 320.00,
            'expiry_date'   => date('Y-m-d', strtotime('+365 days')),
            'batch_number'  => 'BATCH-GRN-' . $testSuffix
        ]
    ];

    $grnRes = GRN::create($grnHeader, $grnItems);
    assertTest($grnRes['success'], "Create Direct GRN in Draft Status");
    $grnId = (int)$grnRes['grn_id'];
    $createdGrnIds[] = $grnId;

    // Verify GRN details
    $grn = GRN::find($grnId);
    assertTest($grn !== null, "Find created GRN by ID");
    assertTest($grn['status'] === 'Draft', "GRN Status is Draft upon creation");
    assertTest((float)$grn['actual_grn_total'] === 5000.00, "Actual GRN Total matches line totals (10*100 + 20*200 = 5000)");
    assertTest(count($grn['items']) === 2, "GRN has precisely 2 line items");

    // Verify items have expiry_date and batch_number preserved
    $itemB = null;
    foreach ($grn['items'] as $it) {
        if ((int)$it['product_id'] === $prodBId) $itemB = $it;
    }
    assertTest($itemB && $itemB['batch_number'] === ('BATCH-GRN-' . $testSuffix), "GRN item preserves batch number");
    assertTest($itemB && !empty($itemB['expiry_date']), "GRN item preserves expiry date");

    // Verify inventory is NOT yet increased while in Draft status
    $invA = Database::fetchOne("SELECT quantity FROM branch_inventory WHERE product_id = :p AND branch_id = :b", [':p' => $prodAId, ':b' => $branchId]);
    assertTest(!$invA || (float)$invA['quantity'] === 0.0, "Draft GRN does NOT increment sellable branch inventory");

    $lotsBefore = Database::fetchAll("SELECT * FROM product_lots WHERE product_id IN (:p1, :p2) AND source_type = 'grn'", [':p1' => $prodAId, ':p2' => $prodBId]);
    assertTest(empty($lotsBefore), "Draft GRN does NOT create stock lots before approval");

    echo "\n--- 3. Testing Draft GRN Modification ---\n";

    $updateHeader = $grnHeader;
    $updateHeader['supplier_invoice_amount'] = 5500.00;
    $updateHeader['difference_reason'] = 'Price Variance';

    $updateItems = $grnItems;
    $updateItems[0]['unit_cost'] = 150.00; // 10 * 150 = 1500, + 20 * 200 = 4000 => 5500

    $updateRes = GRN::update($grnId, $updateHeader, $updateItems);
    assertTest($updateRes['success'], "Update Draft GRN with modified costs and totals");

    $grnUpdated = GRN::find($grnId);
    assertTest((float)$grnUpdated['actual_grn_total'] === 5500.00, "Updated Actual GRN Total is LKR 5500.00");
    assertTest($grnUpdated['difference_reason'] === 'Price Variance', "Updated difference reason is preserved");

    echo "\n--- 4. Testing GRN Approval & Atomic Inventory Posting ---\n";

    $appRes = GRN::approve($grnId, 1);
    assertTest($appRes['success'], "Approve Draft GRN");

    $grnApproved = GRN::find($grnId);
    assertTest($grnApproved['status'] === 'Approved', "GRN Status updated to 'Approved'");
    assertTest((int)$grnApproved['approved_by'] === 1, "GRN Approved By user recorded");
    assertTest(!empty($grnApproved['approved_at']), "GRN Approved At timestamp recorded");

    // Verify stock lots created
    $lotA = Database::fetchOne("SELECT * FROM product_lots WHERE product_id = :p AND branch_id = :b AND source_type = 'grn'", [':p' => $prodAId, ':b' => $branchId]);
    assertTest($lotA && (float)$lotA['remaining_quantity'] === 10.00, "Product A stock lot created with QTY=10.00");
    assertTest($lotA && (float)$lotA['cost'] === 150.00, "Product A stock lot cost is 150.00");

    $lotB = Database::fetchOne("SELECT * FROM product_lots WHERE product_id = :p AND branch_id = :b AND source_type = 'grn'", [':p' => $prodBId, ':b' => $branchId]);
    assertTest($lotB && (float)$lotB['remaining_quantity'] === 20.00, "Product B stock lot created with QTY=20.00");
    assertTest($lotB && $lotB['batch_number'] === ('BATCH-GRN-' . $testSuffix), "Product B stock lot has correct batch number");
    assertTest($lotB && !empty($lotB['expiry_date']), "Product B stock lot has correct expiry date");

    // Verify sellable branch inventory updated
    $invAAfter = Database::fetchOne("SELECT quantity FROM branch_inventory WHERE product_id = :p AND branch_id = :b", [':p' => $prodAId, ':b' => $branchId]);
    assertTest($invAAfter && (float)$invAAfter['quantity'] === 10.00, "Product A branch inventory increased to 10.00");

    $invBAfter = Database::fetchOne("SELECT quantity FROM branch_inventory WHERE product_id = :p AND branch_id = :b", [':p' => $prodBId, ':b' => $branchId]);
    assertTest($invBAfter && (float)$invBAfter['quantity'] === 20.00, "Product B branch inventory increased to 20.00");

    // Verify stock ledger movement recorded
    $ledgerA = Database::fetchOne("SELECT * FROM stock_ledger WHERE product_id = :p AND movement_type = 'purchase_grn'", [':p' => $prodAId]);
    assertTest($ledgerA && (float)$ledgerA['quantity'] === 10.00, "Stock ledger entry created for Product A");

    // Verify master product cost and selling price updated
    $prodAMaster = Product::find($prodAId);
    assertTest((float)$prodAMaster['cost'] === 150.00, "Master Product A cost updated to latest GRN cost (150.00)");
    assertTest((float)$prodAMaster['selling_price'] === 160.00, "Master Product A selling price updated to GRN selling price (160.00)");

    echo "\n--- 5. Testing Chart of Accounts (COA) Double-Entry Verification ---\n";

    $je = Database::fetchOne("SELECT * FROM journal_entries WHERE source_type = 'grn' AND source_id = :gid", [':gid' => $grnId]);
    assertTest($je !== null, "Journal Entry created for approved GRN");
    assertTest($je && $je['posting_status'] === 'posted', "Journal Entry posting status is 'posted'");

    $jeLines = Database::fetchAll("
        SELECT jl.*, coa.account_code, coa.account_name 
        FROM journal_lines jl
        JOIN chart_of_accounts coa ON jl.account_id = coa.id
        WHERE jl.journal_entry_id = :jid
    ", [':jid' => $je['id']]);

    assertTest(count($jeLines) === 2, "Journal Entry has 2 balanced lines");

    $totalDebit = 0.0;
    $totalCredit = 0.0;
    $hasInvDebit = false;
    $hasApCredit = false;

    foreach ($jeLines as $line) {
        $deb = (float)$line['debit'];
        $crd = (float)$line['credit'];
        $totalDebit += $deb;
        $totalCredit += $crd;

        if ($line['account_code'] === Accounting::CODE_INVENTORY_ASSET && $deb === 5500.00) {
            $hasInvDebit = true;
        }
        if ($line['account_code'] === Accounting::CODE_SUPPLIER_PAYABLE && $crd === 5500.00) {
            $hasApCredit = true;
        }
    }

    assertTest($totalDebit === 5500.00 && $totalCredit === 5500.00, "Journal Entry is 100% Balanced (Total Debit == Total Credit == LKR 5,500.00)");
    assertTest($hasInvDebit, "Debit: Inventory Asset (1200) for LKR 5,500.00");
    assertTest($hasApCredit, "Credit: Supplier Payable (2010) for LKR 5,500.00");

    echo "\n--- 6. Testing Immutability & Re-Approval Protections ---\n";

    // Attempt to edit approved GRN
    $editBlockedRes = GRN::update($grnId, $updateHeader, $updateItems);
    assertTest(!$editBlockedRes['success'], "Blocked editing of Approved GRN (Immutability enforced)");

    // Attempt duplicate approval
    $dupAppRes = GRN::approve($grnId, 1);
    assertTest(!$dupAppRes['success'], "Blocked duplicate approval of already Approved GRN");

    // Attempt to cancel approved GRN
    $cancelBlockedRes = GRN::cancel($grnId, 1);
    assertTest(!$cancelBlockedRes['success'], "Blocked cancellation of Approved GRN");

    echo "\n--- 7. Testing Linked Purchase Order (PO) to GRN Flow ---\n";

    // Create a Purchase Order
    $poData = [
        'supplier_id'            => $supplierId,
        'branch_id'              => $branchId,
        'order_date'             => date('Y-m-d'),
        'expected_delivery_date' => date('Y-m-d', strtotime('+7 days')),
        'notes'                  => 'Test PO for GRN receiving'
    ];
    $poItems = [
        [
            'product_id' => $prodAId,
            'quantity'   => 15.00,
            'unit_cost'  => 140.00
        ]
    ];

    $poRes = PurchaseOrder::create($poData, $poItems);
    assertTest($poRes['success'], "Create Purchase Order for 15 units");
    $poId = (int)($poRes['po_id'] ?? $poRes['id'] ?? 0);
    $createdPoIds[] = $poId;

    // Approve the PO
    $poAppRes = PurchaseOrder::approve($poId, 1);
    assertTest($poAppRes['success'], "Approve Purchase Order");

    // Receive via linked GRN
    $linkedGrnHeader = [
        'po_id'                   => $poId,
        'supplier_id'             => $supplierId,
        'branch_id'               => $branchId,
        'supplier_invoice_no'     => 'INV-PO-' . $testSuffix,
        'supplier_invoice_date'   => date('Y-m-d'),
        'received_date'           => date('Y-m-d'),
        'supplier_invoice_amount' => 2100.00,
        'difference_reason'       => 'None',
        'notes'                   => 'Receiving linked PO',
        'user_id'                 => 1
    ];
    $linkedGrnItems = [
        [
            'product_id'    => $prodAId,
            'quantity'      => 15.00,
            'unit_cost'     => 140.00,
            'selling_price' => 160.00,
            'expiry_date'   => null,
            'batch_number'  => null
        ]
    ];

    $linkedGrnRes = GRN::create($linkedGrnHeader, $linkedGrnItems);
    assertTest($linkedGrnRes['success'], "Create GRN linked to Purchase Order");
    $linkedGrnId = (int)$linkedGrnRes['grn_id'];
    $createdGrnIds[] = $linkedGrnId;

    // Approve linked GRN
    $linkedAppRes = GRN::approve($linkedGrnId, 1);
    assertTest($linkedAppRes['success'], "Approve linked GRN");

    // Verify PO received quantity and completion status
    $poItemRow = Database::fetchOne("SELECT received_quantity FROM purchase_order_items WHERE po_id = :p AND product_id = :prod", [':p' => $poId, ':prod' => $prodAId]);
    assertTest((float)$poItemRow['received_quantity'] === 15.00, "PO item received_quantity updated to 15.00");

    $poFinal = PurchaseOrder::find($poId);
    assertTest($poFinal['status'] === 'Completed', "PO status transitioned to 'Completed' upon full receipt");

    echo "\n--- 8. Testing Draft GRN Cancellation ---\n";

    $grnCancelHeader = $grnHeader;
    $grnCancelHeader['supplier_invoice_no'] = 'INV-CANCEL-' . $testSuffix;

    $grnCancelRes = GRN::create($grnCancelHeader, [
        ['product_id' => $prodAId, 'quantity' => 5, 'unit_cost' => 100, 'selling_price' => 150, 'expiry_date' => null, 'batch_number' => null]
    ]);
    assertTest($grnCancelRes['success'], "Create second draft GRN for cancellation");
    $cancelGrnId = (int)$grnCancelRes['grn_id'];
    $createdGrnIds[] = $cancelGrnId;

    $cancelRes = GRN::cancel($cancelGrnId, 1);
    assertTest($cancelRes['success'], "Cancel Draft GRN");

    $cancelledGrn = GRN::find($cancelGrnId);
    assertTest($cancelledGrn['status'] === 'Cancelled', "GRN Status updated to 'Cancelled'");

    // Verify no journal entries created for cancelled GRN
    $cancelJe = Database::fetchOne("SELECT id FROM journal_entries WHERE source_type = 'grn' AND source_id = :gid", [':gid' => $cancelGrnId]);
    assertTest($cancelJe === null, "No Journal Entry created for Cancelled GRN");

    echo "\n--- 9. Testing List Pagination, Multi-column Search & Audit Trail ---\n";

    // Test List retrieval with branch filter
    $listRes = GRN::getList($branchId, ['search' => 'INV-TEST-' . $testSuffix], 1, 25);
    assertTest(count($listRes['items']) >= 1, "GRN Search matches by supplier invoice number");

    // Test audit log recording
    $audits = Database::fetchAll("SELECT action FROM audit_logs WHERE module = 'purchasing' AND record_id IN (" . implode(',', $createdGrnIds) . ")");
    $actions = array_column($audits, 'action');
    assertTest(in_array('GRN_CREATED', $actions), "Audit log captured GRN_CREATED");
    assertTest(in_array('GRN_APPROVED', $actions), "Audit log captured GRN_APPROVED");
    assertTest(in_array('GRN_CANCELLED', $actions), "Audit log captured GRN_CANCELLED");

} catch (Throwable $t) {
    echo "\n  [EXCEPTION] Unexpected test error: " . $t->getMessage() . "\n" . $t->getTraceAsString() . "\n";
    $failCount++;
} finally {
    echo "\n--- 10. Cleaning up Isolated Test Fixtures ---\n";
    // Safely delete only the test fixtures created during this run
    if (!empty($createdGrnIds)) {
        $grnIdList = implode(',', $createdGrnIds);
        Database::execute("DELETE FROM journal_lines WHERE journal_entry_id IN (SELECT id FROM journal_entries WHERE source_type = 'grn' AND source_id IN ($grnIdList))");
        Database::execute("DELETE FROM journal_entries WHERE source_type = 'grn' AND source_id IN ($grnIdList)");
        Database::execute("DELETE FROM stock_ledger WHERE movement_type = 'purchase_grn' AND reference_no IN (SELECT grn_number FROM grns WHERE id IN ($grnIdList))");
        Database::execute("DELETE FROM product_lots WHERE source_type = 'grn' AND source_id IN ($grnIdList)");
        Database::execute("DELETE FROM grn_items WHERE grn_id IN ($grnIdList)");
        Database::execute("DELETE FROM audit_logs WHERE module = 'purchasing' AND record_id IN ($grnIdList)");
        Database::execute("DELETE FROM supplier_credit_settlements WHERE target_grn_id IN ($grnIdList)");
        Database::execute("DELETE FROM grns WHERE id IN ($grnIdList)");
    }
    if (!empty($createdPoIds)) {
        $poIdList = implode(',', $createdPoIds);
        Database::execute("DELETE FROM purchase_order_items WHERE po_id IN ($poIdList)");
        Database::execute("DELETE FROM purchase_orders WHERE id IN ($poIdList)");
    }
    if (!empty($createdProductIds)) {
        $prodIdList = implode(',', $createdProductIds);
        Database::execute("DELETE FROM purchase_order_items WHERE product_id IN ($prodIdList)");
        Database::execute("DELETE FROM journal_lines WHERE journal_entry_id IN (SELECT id FROM journal_entries WHERE source_type = 'opening_balance' AND source_id IN ($prodIdList))");
        Database::execute("DELETE FROM journal_entries WHERE source_type = 'opening_balance' AND source_id IN ($prodIdList)");
        Database::execute("DELETE FROM stock_ledger WHERE product_id IN ($prodIdList)");
        Database::execute("DELETE FROM product_lots WHERE product_id IN ($prodIdList)");
        Database::execute("DELETE FROM branch_inventory WHERE product_id IN ($prodIdList)");
        Database::execute("DELETE FROM audit_logs WHERE module = 'products' AND record_id IN ($prodIdList)");
        Database::execute("DELETE FROM supplier_return_items WHERE product_id IN ($prodIdList)");
        Database::execute("DELETE FROM products WHERE id IN ($prodIdList)");
    }

    echo "  Cleaned up test IDs safely without modifying any business data.\n";
}

echo "\n====================================================\n";
echo "TEST RESULTS: {$passCount} PASSED, {$failCount} FAILED\n";
echo "====================================================\n";

if ($failCount > 0) exit(1);
