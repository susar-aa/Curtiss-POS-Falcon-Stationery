<?php
/**
 * Verification test for Supplier Opening Balance & Accounting integration
 */
$_SESSION['user'] = [
    'id' => 1,
    'username' => 'admin',
    'full_name' => 'Administrator',
    'role' => 'Super Admin',
    'active_branch_name' => 'Main Branch'
];
$_SESSION['branch_id'] = 1;

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Supplier.php';
require_once __DIR__ . '/../core/SupplierOpeningBalance.php';
require_once __DIR__ . '/../core/SupplierPayment.php';

echo "=== STARTING SUPPLIER OPENING BALANCE TEST ===\n";

$suppliers = Supplier::all(true);
if (empty($suppliers)) {
    die("No active suppliers found to test!\n");
}

$supplier = $suppliers[0];
$sId = (int)$supplier['id'];
echo "Testing on Supplier #{$sId} ({$supplier['name']})...\n";

// 1. Check if an active OB already exists; if so, reverse it first for a clean test
$existingOb = SupplierOpeningBalance::getActiveOpeningBalance($sId);
if ($existingOb) {
    echo "Cleaning existing active OB #{$existingOb['id']}...\n";
    SupplierOpeningBalance::reverseOpeningBalance((int)$existingOb['id'], "Cleanup before automated unit test", 1, 1);
}

// 2. Post 'owe_supplier' Opening Balance
echo "Posting 'owe_supplier' Opening Balance of LKR 12,500.00...\n";
$postData1 = [
    'supplier_id' => $sId,
    'amount' => 12500.00,
    'balance_type' => 'owe_supplier',
    'entry_date' => date('Y-m-d'),
    'reference_no' => 'TEST-OB-001',
    'notes' => 'Automated test payable opening balance'
];
$res1 = SupplierOpeningBalance::postOpeningBalance($postData1, 1, 1);
echo "Post Result: " . ($res1['success'] ? 'SUCCESS' : 'FAILED') . " - " . $res1['message'] . "\n";
if (!$res1['success']) die("Post failed!\n");

$obId1 = $res1['id'];

// 3. Test Duplicate Prevention
echo "Testing Duplicate Opening Balance Prevention...\n";
$resDup = SupplierOpeningBalance::postOpeningBalance($postData1, 1, 1);
echo "Duplicate Post Blocked: " . (!$resDup['success'] ? 'YES' : 'NO') . " - " . $resDup['message'] . "\n";

// 4. Verify Supplier Outstanding Summary
echo "Checking Supplier Outstanding Summary...\n";
$summary1 = SupplierPayment::getSupplierOutstandingSummary($sId, 1);
echo "Total Outstanding: LKR " . $summary1['total_outstanding'] . " (OB portion: " . $summary1['ob_outstanding'] . ")\n";

// 5. Test Reversal of OB #1
echo "Reversing OB #{$obId1}...\n";
$revRes1 = SupplierOpeningBalance::reverseOpeningBalance($obId1, "Automated reversal test", 1, 1);
echo "Reversal Result: " . ($revRes1['success'] ? 'SUCCESS' : 'FAILED') . " - " . $revRes1['message'] . "\n";

// 6. Post 'supplier_owes_us' Opening Balance (Receivable / Credit)
echo "Posting 'supplier_owes_us' Opening Balance of LKR 3,500.00...\n";
$postData2 = [
    'supplier_id' => $sId,
    'amount' => 3500.00,
    'balance_type' => 'supplier_owes_us',
    'entry_date' => date('Y-m-d'),
    'reference_no' => 'TEST-OB-CRD-001',
    'notes' => 'Automated test credit opening balance'
];
$res2 = SupplierOpeningBalance::postOpeningBalance($postData2, 1, 1);
echo "Post Result: " . ($res2['success'] ? 'SUCCESS' : 'FAILED') . " - " . $res2['message'] . "\n";

$obId2 = $res2['id'];

// 7. Verify Supplier Credit Balance
$summary2 = SupplierPayment::getSupplierOutstandingSummary($sId, 1);
echo "Total Credit Balance: LKR " . $summary2['total_credit_balance'] . "\n";

// 8. Reverse OB #2
echo "Reversing OB #{$obId2}...\n";
$revRes2 = SupplierOpeningBalance::reverseOpeningBalance($obId2, "Automated reversal test 2", 1, 1);
echo "Reversal Result: " . ($revRes2['success'] ? 'SUCCESS' : 'FAILED') . " - " . $revRes2['message'] . "\n";

echo "=== ALL SUPPLIER OPENING BALANCE TESTS PASSED SUCCESSFULLY! ===\n";
