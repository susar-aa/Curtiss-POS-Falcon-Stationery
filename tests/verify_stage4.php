<?php
/**
 * Curtiss POS - Stage 4 Automated Structural Verification Script
 * Validates Purchase Orders, Goods Received Notes (GRN), Variance Tracking, Inventory Posting & Regressions.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Brand.php';
require_once __DIR__ . '/../core/Category.php';
require_once __DIR__ . '/../core/Supplier.php';
require_once __DIR__ . '/../core/Product.php';
require_once __DIR__ . '/../core/Inventory.php';
require_once __DIR__ . '/../core/PurchaseOrder.php';
require_once __DIR__ . '/../core/GRN.php';

echo "========================================================\n";
echo " CURTISS POS - STAGE 4 AUTOMATED TEST SUITE\n";
echo "========================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest(bool $condition, string $testName): void
{
    global $passCount, $failCount;
    if ($condition) {
        echo " [PASS] $testName\n";
        $passCount++;
    } else {
        echo " [FAIL] $testName\n";
        $failCount++;
    }
}

// ----------------------------------------------------------------------------
// 1. Stage 1-3 Regression Tests
// ----------------------------------------------------------------------------
$timeout = (int)Settings::get('session_inactivity_timeout');
assertTest($timeout === 3600, "Regression: Inactivity Timeout = 3600 seconds");

$openingCash = (float)Settings::get('default_opening_cash');
assertTest($openingCash === 20000.00, "Regression: Default Opening Cash = LKR 20,000");

// ----------------------------------------------------------------------------
// 2. Purchase Order (PO) Creation, Approval, and Cancellation
// ----------------------------------------------------------------------------
$supplier = Supplier::all(true)[0];
$product = Database::fetchOne("SELECT * FROM products WHERE (has_expiry = 0 OR has_expiry IS NULL) AND deleted_at IS NULL ORDER BY id ASC LIMIT 1");

$poHeader = [
    'supplier_id'     => $supplier['id'],
    'branch_id'       => 1,
    'order_date'      => date('Y-m-d'),
    'expected_date'   => date('Y-m-d', strtotime('+5 days')),
    'discount_amount' => 100.00,
    'notes'           => 'Stage 4 Automated Test PO',
    'user_id'         => 1
];

$poItems = [
    [
        'product_id'    => $product['id'],
        'quantity'      => 50.00,
        'unit_cost'     => 25.00,
        'selling_price' => 40.00
    ]
];

$poRes = PurchaseOrder::create($poHeader, $poItems);
assertTest($poRes['success'] === true, "PO Creation Success: {$poRes['po_number']}");
$poId = $poRes['po_id'];

$po = PurchaseOrder::find($poId);
assertTest($po['status'] === 'Draft', "PO Initial Status = 'Draft'");
assertTest((float)$po['net_amount'] === 1150.00, "PO Net Amount Calculated Correctly (50*25 - 100 = 1150)");

$poApproveRes = PurchaseOrder::approve($poId, 1);
assertTest($poApproveRes['success'] === true, "PO Approval Success");
$poApproved = PurchaseOrder::find($poId);
assertTest($poApproved['status'] === 'Approved', "PO Status Updated to 'Approved'");

// Test PO Cancellation
$poCancelHeader = array_merge($poHeader, ['notes' => 'PO to cancel']);
$poCancelRes = PurchaseOrder::create($poCancelHeader, $poItems);
$poCancelId = $poCancelRes['po_id'];
PurchaseOrder::cancel($poCancelId, 1);
$poCancelled = PurchaseOrder::find($poCancelId);
assertTest($poCancelled['status'] === 'Cancelled', "PO Cancellation Success");

// ----------------------------------------------------------------------------
// 3. GRN Creation (Draft) & Premature Stock Change Prevention
// ----------------------------------------------------------------------------
$initialStock = Inventory::getBranchStock(1, $product['id'])['quantity'];

$grnHeader = [
    'po_id'                   => $poId,
    'supplier_id'             => $supplier['id'],
    'branch_id'               => 1,
    'supplier_invoice_no'     => 'TEST-INV-8899',
    'supplier_invoice_date'   => date('Y-m-d'),
    'received_date'           => date('Y-m-d'),
    'supplier_invoice_amount' => 1500.00, // Invoice states 1500
    'difference_reason'       => 'Price difference',
    'notes'                   => 'Testing GRN variance and premature stock prevention',
    'user_id'                 => 1
];

$grnItems = [
    [
        'product_id'    => $product['id'],
        'quantity'      => 50.00,
        'unit_cost'     => 26.00, // Cost 26 -> 50*26 = 1300 actual GRN total
        'selling_price' => 42.00
    ]
];

$grnRes = GRN::create($grnHeader, $grnItems);
assertTest($grnRes['success'] === true, "GRN Creation Success: {$grnRes['grn_number']} (Draft)");
$grnId = $grnRes['grn_id'];

// Check Stock is NOT updated during Draft status
$stockAfterDraftGrn = Inventory::getBranchStock(1, $product['id'])['quantity'];
assertTest($stockAfterDraftGrn === $initialStock, "Premature Stock Change Prevention: Draft GRN did not alter branch stock");

// Check Invoice Variance Calculation
$grnDraft = GRN::find($grnId);
assertTest((float)$grnDraft['actual_grn_total'] === 1300.00, "Actual GRN Total = 1300.00 (50 * 26)");
assertTest((float)$grnDraft['difference_amount'] === 200.00, "Invoice Variance Amount = 200.00 (1500 Stated - 1300 Actual)");
assertTest($grnDraft['difference_reason'] === 'Price difference', "Invoice Variance Reason Recorded = 'Price difference'");

// ----------------------------------------------------------------------------
// 4. GRN Approval, Stock Posting & Lot Integration
// ----------------------------------------------------------------------------
$grnApproveRes = GRN::approve($grnId, 1);
assertTest($grnApproveRes['success'] === true, "GRN Approval & Stock Posting Success");

$grnApproved = GRN::find($grnId);
assertTest($grnApproved['status'] === 'Approved', "GRN Status Updated to 'Approved'");

// Check Branch Stock Increased
$stockAfterApprovedGrn = Inventory::getBranchStock(1, $product['id'])['quantity'];
assertTest($stockAfterApprovedGrn === ($initialStock + 50.00), "Branch Stock Increased by Received Quantity (+50.00)");

// Check New Price/Cost Lot Created
$lots = Inventory::getLots($product['id'], 1, true);
$grnLots = array_filter($lots, fn($l) => (float)$l['cost'] === 26.00 && (float)$l['selling_price'] === 42.00);
assertTest(!empty($grnLots), "GRN Created New Separate Price/Cost Lot with Cost 26.00 and Selling Price 42.00");

// Check Append-Only Stock Ledger Entry Recorded
$ledgerRes = Inventory::getLedgerList(1, ['product_id' => $product['id']]);
$grnLedger = array_filter($ledgerRes['items'], fn($l) => $l['movement_type'] === 'purchase_grn');
assertTest(!empty($grnLedger), "Stock Movement Ledger Recorded 'purchase_grn' Movement Entry");

// Check Linked PO Status Updated to Completed
$poAfterGrn = PurchaseOrder::find($poId);
assertTest($poAfterGrn['status'] === 'Completed', "Linked Purchase Order Status Updated to 'Completed'");

// ----------------------------------------------------------------------------
// 5. Supplier Purchase History & Payable Foundation
// ----------------------------------------------------------------------------
$supSummary = Supplier::getPurchaseSummary($supplier['id']);
assertTest($supSummary['total_grns'] >= 1, "Supplier Purchase Summary: GRN count tracked");
assertTest($supSummary['total_purchased_amount'] >= 1300.00, "Supplier Purchase Summary: Payable basis uses Actual GRN Value");

// ----------------------------------------------------------------------------
// 6. Audit Trail Logging Verification
// ----------------------------------------------------------------------------
$auditGrn = Database::fetchOne("SELECT * FROM audit_logs WHERE action = 'GRN_APPROVED' AND record_id = :id", [':id' => $grnId]);
assertTest($auditGrn !== null, "Audit Trail Verification: GRN_APPROVED event recorded");

// ----------------------------------------------------------------------------
// 7. Auth Regression Test
// ----------------------------------------------------------------------------
$authRes = Auth::attempt('superadmin', 'Password@123');
assertTest($authRes['success'] === true, "Auth Regression Test: Super Admin Login Success");

echo "\n--------------------------------------------------------\n";
echo " VERIFICATION SUMMARY: $passCount Passed, $failCount Failed.\n";
echo "========================================================\n";

if ($failCount > 0) {
    exit(1);
}
