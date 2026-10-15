<?php
/**
 * CLI Integration Test: GRN Expiry & Payment Due Date Verification
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/GRN.php';
require_once __DIR__ . '/../core/Product.php';
require_once __DIR__ . '/../core/Supplier.php';

echo "=========================================================\n";
echo "   CLI TEST: GRN EXPIRY & PAYMENT DUE DATE VERIFICATION \n";
echo "=========================================================\n\n";

$db = Database::getInstance()->getConnection();

// 1. Fetch active supplier and expiry product
$supplier = Database::fetchOne("SELECT id, name FROM suppliers WHERE status = 'active' LIMIT 1");
if (!$supplier) {
    echo "FAILED: No active supplier found.\n";
    exit(1);
}
echo "[1] Using Supplier: ID={$supplier['id']} - {$supplier['name']}\n";

$expiryProd = Database::fetchOne("SELECT id, name, sku, has_expiry, cost, selling_price FROM products WHERE has_expiry = 1 AND status = 'active' LIMIT 1");
if (!$expiryProd) {
    echo "FAILED: No active expiry-tracked product found.\n";
    exit(1);
}
echo "[2] Using Expiry Product: ID={$expiryProd['id']} - {$expiryProd['name']} (has_expiry={$expiryProd['has_expiry']})\n";

// 2. Test rejection when expiry-tracked product has no expiry_date
$testHeaderNoExp = [
    'supplier_id'             => (int)$supplier['id'],
    'branch_id'               => 1,
    'supplier_invoice_no'     => 'TEST-INV-NO-EXP-' . time(),
    'supplier_invoice_date'   => date('Y-m-d'),
    'payment_due_date'        => date('Y-m-d', strtotime('+30 days')),
    'received_date'           => date('Y-m-d'),
    'supplier_invoice_amount' => 100.00,
    'user_id'                 => 1
];
$itemsNoExp = [
    [
        'product_id'    => (int)$expiryProd['id'],
        'quantity'      => 2,
        'unit_cost'     => (float)$expiryProd['cost'],
        'selling_price' => (float)$expiryProd['selling_price'],
        'expiry_date'   => '', // Missing!
        'batch_number'  => 'BATCH-TEST'
    ]
];

$resNoExp = GRN::create($testHeaderNoExp, $itemsNoExp);
if ($resNoExp['success']) {
    echo "[-] FAILED: Expected GRN creation without expiry date to fail, but it succeeded!\n";
    exit(1);
} else {
    echo "[+] PASSED: GRN creation without expiry date properly rejected: '{$resNoExp['message']}'\n";
}

// 3. Test successful GRN creation with expiry_date and payment_due_date
$dueDate = date('Y-m-d', strtotime('+30 days'));
$expDate = date('Y-m-d', strtotime('+365 days'));
$invNo   = 'TEST-INV-' . time();

$testHeader = [
    'supplier_id'             => (int)$supplier['id'],
    'branch_id'               => 1,
    'supplier_invoice_no'     => $invNo,
    'supplier_invoice_date'   => date('Y-m-d'),
    'payment_due_date'        => $dueDate,
    'received_date'           => date('Y-m-d'),
    'supplier_invoice_amount' => round(5 * (float)$expiryProd['cost'], 2),
    'difference_reason'       => 'None',
    'notes'                   => 'CLI Test with payment due date & expiry',
    'user_id'                 => 1
];
$testItems = [
    [
        'product_id'    => (int)$expiryProd['id'],
        'quantity'      => 5,
        'unit_cost'     => (float)$expiryProd['cost'],
        'selling_price' => (float)$expiryProd['selling_price'],
        'expiry_date'   => $expDate,
        'batch_number'  => 'LOT-TEST-999'
    ]
];

$resCreate = GRN::create($testHeader, $testItems);
if (!$resCreate['success']) {
    echo "[-] FAILED to create GRN: {$resCreate['message']}\n";
    exit(1);
}
$createdGrnId = (int)$resCreate['grn_id'];
echo "[+] PASSED: GRN created successfully with ID: {$createdGrnId} ({$resCreate['grn_number']})\n";

// 4. Verify persisted GRN record in database
$savedGrn = GRN::find($createdGrnId);
if (!$savedGrn) {
    echo "[-] FAILED: Could not find created GRN #{$createdGrnId}\n";
    exit(1);
}

if ($savedGrn['payment_due_date'] !== $dueDate) {
    echo "[-] FAILED: payment_due_date mismatch: expected '{$dueDate}', got '{$savedGrn['payment_due_date']}'\n";
    exit(1);
}
echo "[+] PASSED: payment_due_date verified in DB: '{$savedGrn['payment_due_date']}'\n";

if (empty($savedGrn['items']) || count($savedGrn['items']) !== 1) {
    echo "[-] FAILED: items count mismatch\n";
    exit(1);
}
$savedItem = $savedGrn['items'][0];
if ($savedItem['expiry_date'] !== $expDate) {
    echo "[-] FAILED: item expiry_date mismatch: expected '{$expDate}', got '{$savedItem['expiry_date']}'\n";
    exit(1);
}
echo "[+] PASSED: item expiry_date verified in DB: '{$savedItem['expiry_date']}' (Batch: '{$savedItem['batch_number']}')\n";

// 5. Test updating GRN payment_due_date
$newDueDate = date('Y-m-d', strtotime('+45 days'));
$newExpDate = date('Y-m-d', strtotime('+400 days'));
$updateHeader = [
    'supplier_id'             => (int)$supplier['id'],
    'branch_id'               => 1,
    'supplier_invoice_no'     => $invNo . '-UPDATED',
    'supplier_invoice_date'   => date('Y-m-d'),
    'payment_due_date'        => $newDueDate,
    'received_date'           => date('Y-m-d'),
    'supplier_invoice_amount' => round(6 * (float)$expiryProd['cost'], 2),
    'difference_reason'       => 'None',
    'notes'                   => 'Updated GRN via CLI test',
    'user_id'                 => 1
];
$updateItems = [
    [
        'product_id'    => (int)$expiryProd['id'],
        'quantity'      => 6,
        'unit_cost'     => (float)$expiryProd['cost'],
        'selling_price' => (float)$expiryProd['selling_price'],
        'expiry_date'   => $newExpDate,
        'batch_number'  => 'LOT-UPDATED-888'
    ]
];

$resUpdate = GRN::update($createdGrnId, $updateHeader, $updateItems);
if (!$resUpdate['success']) {
    echo "[-] FAILED to update GRN: {$resUpdate['message']}\n";
    exit(1);
}
echo "[+] PASSED: GRN updated successfully\n";

$recheckGrn = GRN::find($createdGrnId);
if ($recheckGrn['payment_due_date'] !== $newDueDate) {
    echo "[-] FAILED: updated payment_due_date mismatch: expected '{$newDueDate}', got '{$recheckGrn['payment_due_date']}'\n";
    exit(1);
}
echo "[+] PASSED: updated payment_due_date verified: '{$recheckGrn['payment_due_date']}'\n";

if ($recheckGrn['items'][0]['expiry_date'] !== $newExpDate) {
    echo "[-] FAILED: updated item expiry_date mismatch: expected '{$newExpDate}', got '{$recheckGrn['items'][0]['expiry_date']}'\n";
    exit(1);
}
echo "[+] PASSED: updated item expiry_date verified: '{$recheckGrn['items'][0]['expiry_date']}'\n";

// 6. Test search by payment_due_date in GRN::getList
$searchList = GRN::getList(1, ['search' => $newDueDate]);
$foundInSearch = false;
foreach ($searchList['items'] as $item) {
    if ((int)$item['id'] === $createdGrnId) {
        $foundInSearch = true;
        break;
    }
}
if (!$foundInSearch) {
    echo "[-] FAILED: GRN not found when searching by payment_due_date\n";
    exit(1);
}
echo "[+] PASSED: GRN found via search by payment_due_date ('{$newDueDate}')\n";

// 7. Clean up test records (only the created test GRN)
$db->prepare("DELETE FROM grn_items WHERE grn_id = :id")->execute([':id' => $createdGrnId]);
$db->prepare("DELETE FROM grns WHERE id = :id")->execute([':id' => $createdGrnId]);
$db->prepare("DELETE FROM audit_logs WHERE module = 'purchasing' AND record_id = :id")->execute([':id' => (string)$createdGrnId]);
echo "[+] Cleaned up test GRN #{$createdGrnId} safely without modifying any business records.\n\n";

echo "=========================================================\n";
echo "   ALL GRN EXPIRY & PAYMENT DUE TESTS PASSED (100%)\n";
echo "=========================================================\n";
exit(0);
