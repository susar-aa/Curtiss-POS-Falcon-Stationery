<?php
/**
 * Curtiss POS - Stage 5 Automated Test Suite
 * Validates Customers & Customer Credit Foundation, Barcode Scanner identification,
 * Walk-in customer default, Duplicate barcode prevention, Permissions, Audit, and Stage 1-4 Regression.
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Customer.php';
require_once __DIR__ . '/../core/PurchaseOrder.php';
require_once __DIR__ . '/../core/GRN.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

echo "========================================================\n";
echo " CURTISS POS - STAGE 5 AUTOMATED TEST SUITE\n";
echo "========================================================\n\n";

try {
    // ----------------------------------------------------------------------------
    // 1. Stage 1 - 4 Regression Tests
    // ----------------------------------------------------------------------------
    $db = Database::getInstance()->getConnection();

    $timeoutSetting = Database::fetchOne("SELECT setting_value FROM system_settings WHERE setting_key = 'session_inactivity_timeout'");
    assertTest(($timeoutSetting['setting_value'] ?? '') === '3600', "Regression: Inactivity Timeout = 3600 seconds");

    $cashSetting = Database::fetchOne("SELECT setting_value FROM system_settings WHERE setting_key = 'default_opening_cash'");
    assertTest((float)($cashSetting['setting_value'] ?? 0) === 20000.00, "Regression: Default Opening Cash = LKR 20,000");

    $authRes = Auth::attempt('superadmin', 'Password@123');
    assertTest($authRes['success'] === true, "Auth Regression Test: Super Admin Login Success");

    // ----------------------------------------------------------------------------
    // 2. Walk-in Default Customer Verification
    // ----------------------------------------------------------------------------
    $walkIn = Customer::getWalkInCustomer();
    assertTest(!empty($walkIn) && (int)$walkIn['id'] === 1, "Walk-in Customer Exists Default with ID #1");
    assertTest(($walkIn['customer_code'] ?? '') === 'CUST-WALKIN', "Walk-in Customer Code = 'CUST-WALKIN'");
    assertTest((int)$walkIn['is_walkin'] === 1, "Walk-in Customer Flag is_walkin = 1");

    // Walk-in customer deactivation prevention
    $walkInToggle = Customer::toggleStatus(1);
    assertTest($walkInToggle['success'] === false, "Walk-in Customer Deactivation Prevention: Cannot deactivate default Walk-in");

    // ----------------------------------------------------------------------------
    // 3. Customer Creation & Barcode Uniqueness
    // ----------------------------------------------------------------------------
    $randNum = rand(10000, 99999);
    $cardBarcode1 = 'CARD-POS-' . $randNum;
    $phone1 = '+94 77 ' . sprintf('%03d', rand(100, 999)) . ' ' . sprintf('%04d', rand(1000, 9999));
    $name1 = 'Saman Perera ' . $randNum;

    $create1 = Customer::create([
        'name'         => $name1,
        'phone'        => $phone1,
        'whatsapp'     => $phone1,
        'address'      => 'No 45, Kandy Road, Peradeniya',
        'card_barcode' => $cardBarcode1,
        'credit_limit' => 50000.00,
        'status'       => 'active'
    ]);

    assertTest($create1['success'] === true, "Customer Creation Success: {$create1['customer_code']}");
    $cId1 = (int)$create1['id'];

    // Verify duplicate barcode prevention
    $createDuplicate = Customer::create([
        'name'         => 'Nimal Fernando',
        'phone'        => '+94 77 999 8877',
        'card_barcode' => $cardBarcode1, // Same barcode
        'credit_limit' => 10000.00
    ]);
    assertTest($createDuplicate['success'] === false, "Duplicate Barcode Prevention: Disallows creating customer with existing card barcode");

    // ----------------------------------------------------------------------------
    // 4. Customer Lookup & Search Identification (Barcode, Phone, Name, ID)
    // ----------------------------------------------------------------------------
    $foundByBarcode = Customer::findByBarcode($cardBarcode1);
    assertTest($foundByBarcode !== null && (int)$foundByBarcode['id'] === $cId1, "Customer Identification: Exact Card Barcode Lookup Success");

    $searchByPhone = Customer::search($phone1);
    assertTest(!empty($searchByPhone) && (int)$searchByPhone[0]['id'] === $cId1, "Customer Identification: Phone Search Success");

    $searchByName = Customer::search($name1);
    assertTest(!empty($searchByName) && (int)$searchByName[0]['id'] === $cId1, "Customer Identification: Name Partial Search Success");

    $searchById = Customer::search((string)$cId1);
    assertTest(!empty($searchById) && (int)$searchById[0]['id'] === $cId1, "Customer Identification: ID Exact Search Success");

    // ----------------------------------------------------------------------------
    // 5. Customer Update & Status Toggle (Soft Deactivate / Reactivate)
    // ----------------------------------------------------------------------------
    $updateRes = Customer::update($cId1, [
        'name'         => 'Saman Perera Updated',
        'phone'        => '+94 77 111 9999',
        'card_barcode' => $cardBarcode1,
        'credit_limit' => 75000.00
    ]);
    assertTest($updateRes['success'] === true, "Customer Update Details Success");

    $updatedCust = Customer::find($cId1);
    assertTest($updatedCust['name'] === 'Saman Perera Updated' && (float)$updatedCust['credit_limit'] === 75000.00, "Updated Name & Credit Limit Verified");

    // Soft Deactivate
    $toggleOff = Customer::toggleStatus($cId1);
    assertTest($toggleOff['success'] === true, "Customer Status Toggle Off Success");
    $deactivatedCust = Customer::find($cId1);
    assertTest($deactivatedCust['status'] === 'inactive', "Customer Status Updated to 'inactive'");

    // Reactivate
    $toggleOn = Customer::toggleStatus($cId1);
    assertTest($toggleOn['success'] === true, "Customer Status Toggle On Success");
    $reactivatedCust = Customer::find($cId1);
    assertTest($reactivatedCust['status'] === 'active', "Customer Status Restored to 'active'");

    // ----------------------------------------------------------------------------
    // 6. Customer Credit & Outstanding Balance Foundation
    // ----------------------------------------------------------------------------
    // Add credit ledger entry foundation
    $db->prepare("
        INSERT INTO customer_credit_ledger (customer_id, transaction_type, reference_no, invoice_total, amount_paid, amount_due, balance_after, notes, branch_id, user_id)
        VALUES (:cid, 'credit_sale', 'INV-DEMO-1001', 15000.00, 5000.00, 10000.00, 10000.00, 'Initial credit sale transaction demo', 1, 1)
    ")->execute([':cid' => $cId1]);

    $db->prepare("UPDATE customers SET credit_balance = 10000.00 WHERE id = :id")->execute([':id' => $cId1]);

    $creditHistory = Customer::getCreditHistory($cId1);
    assertTest((float)$creditHistory['credit_limit'] === 75000.00, "Credit Limit Foundation = LKR 75,000.00");
    assertTest((float)$creditHistory['credit_balance'] === 10000.00, "Outstanding Balance Foundation = LKR 10,000.00");
    assertTest((float)$creditHistory['available_credit'] === 65000.00, "Available Credit = LKR 65,000.00 (75k - 10k)");
    assertTest(!empty($creditHistory['ledger']), "Credit Ledger History Retreived");

    $purchaseHistory = Customer::getPurchaseHistory($cId1);
    assertTest(array_key_exists('total_invoices', $purchaseHistory) && array_key_exists('invoices', $purchaseHistory), "Purchase History Placeholder Structure Intact");

    // ----------------------------------------------------------------------------
    // 7. Permissions & Audit Trail Logging
    // ----------------------------------------------------------------------------
    $permView = Database::fetchOne("SELECT id FROM permissions WHERE permission_code = 'customers.view'");
    $permManage = Database::fetchOne("SELECT id FROM permissions WHERE permission_code = 'customers.manage'");
    $permStatus = Database::fetchOne("SELECT id FROM permissions WHERE permission_code = 'customers.status'");

    assertTest($permView && $permManage && $permStatus, "Stage 5 Customer Permissions Registered in Database");

    $auditCreated = Database::fetchOne("SELECT * FROM audit_logs WHERE action = 'CUSTOMER_CREATED' AND record_id = :id", [':id' => $cId1]);
    $auditUpdated = Database::fetchOne("SELECT * FROM audit_logs WHERE action = 'CUSTOMER_UPDATED' AND record_id = :id", [':id' => $cId1]);
    $auditToggled = Database::fetchOne("SELECT * FROM audit_logs WHERE action = 'CUSTOMER_STATUS_TOGGLED' AND record_id = :id", [':id' => $cId1]);

    assertTest($auditCreated !== null, "Audit Trail Verification: CUSTOMER_CREATED event recorded");
    assertTest($auditUpdated !== null, "Audit Trail Verification: CUSTOMER_UPDATED event recorded");
    assertTest($auditToggled !== null, "Audit Trail Verification: CUSTOMER_STATUS_TOGGLED event recorded");

} catch (Throwable $e) {
    echo "Exception during Stage 5 verification: " . $e->getMessage() . "\n";
    $failCount++;
}

echo "\n--------------------------------------------------------\n";
echo " VERIFICATION SUMMARY: $passCount Passed, $failCount Failed.\n";
echo "========================================================\n";

if ($failCount > 0) {
    exit(1);
}
