<?php
/**
 * Curtiss POS - Comprehensive CLI Test Suite for Company Settings, Logo & Branch Management Branding
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Settings.php';
require_once __DIR__ . '/../core/Audit.php';
require_once __DIR__ . '/../core/Sale.php';
require_once __DIR__ . '/../core/BusinessDay.php';
require_once __DIR__ . '/../core/PurchaseOrder.php';
require_once __DIR__ . '/../core/GRN.php';

echo "=== STARTING COMPANY SETTINGS & BRANCH BRANDING VERIFICATION ===\n\n";

$passed = 0;
$failed = 0;

function assertCondition(bool $cond, string $message) {
    global $passed, $failed;
    if ($cond) {
        echo "[PASS] " . $message . "\n";
        $passed++;
    } else {
        echo "[FAIL] " . $message . "\n";
        $failed++;
    }
}

// 1. Test Settings::getCompanyInfo()
$companyInfo = Settings::getCompanyInfo();
assertCondition(!empty($companyInfo['name']), "Settings::getCompanyInfo returns non-empty company name: '{$companyInfo['name']}'");
assertCondition(isset($companyInfo['phone']), "Settings::getCompanyInfo returns phone key: '{$companyInfo['phone']}'");
assertCondition(isset($companyInfo['whatsapp']), "Settings::getCompanyInfo returns whatsapp key: '{$companyInfo['whatsapp']}'");
assertCondition(isset($companyInfo['email']), "Settings::getCompanyInfo returns email key: '{$companyInfo['email']}'");
assertCondition(isset($companyInfo['website']), "Settings::getCompanyInfo returns website key: '{$companyInfo['website']}'");
assertCondition(isset($companyInfo['address']), "Settings::getCompanyInfo returns address key: '{$companyInfo['address']}'");
assertCondition(isset($companyInfo['reg_no']), "Settings::getCompanyInfo returns reg_no key");
assertCondition(isset($companyInfo['receipt_footer']), "Settings::getCompanyInfo returns receipt_footer key: '{$companyInfo['receipt_footer']}'");
assertCondition(isset($companyInfo['logo']), "Settings::getCompanyInfo returns logo key");
assertCondition(!empty($companyInfo['currency']), "Settings::getCompanyInfo returns currency: '{$companyInfo['currency']}'");

// 2. Test Settings::getBranchInfo()
$branch1 = Settings::getBranchInfo(1);
assertCondition(!empty($branch1['branch_name']), "Settings::getBranchInfo(1) returns branch name: '{$branch1['branch_name']}'");
assertCondition(!empty($branch1['branch_phone']), "Settings::getBranchInfo(1) returns branch phone: '{$branch1['branch_phone']}'");
assertCondition(!empty($branch1['branch_address']), "Settings::getBranchInfo(1) returns branch address: '{$branch1['branch_address']}'");
assertCondition($branch1['company_name'] === $companyInfo['name'], "Settings::getBranchInfo(1) retains company name");
assertCondition($branch1['company_whatsapp'] === $companyInfo['whatsapp'], "Settings::getBranchInfo(1) retains company whatsapp");
assertCondition($branch1['company_website'] === $companyInfo['website'], "Settings::getBranchInfo(1) retains company website");

$branch2 = Settings::getBranchInfo(2);
assertCondition(!empty($branch2['branch_name']), "Settings::getBranchInfo(2) returns branch name: '{$branch2['branch_name']}'");
assertCondition($branch2['branch_phone'] === '+94 11 255 6677', "Settings::getBranchInfo(2) returns Colombo Branch phone: '{$branch2['branch_phone']}'");

// 3. Test Settings::getAllActiveBranches()
$allActive = Settings::getAllActiveBranches();
assertCondition(is_array($allActive) && count($allActive) >= 2, "Settings::getAllActiveBranches returns active branches count: " . count($allActive));

// 4. Test Branch Save & Unique Code Validation (Isolated test branch)
$testCode = 'BR-TEST-' . time();
Database::execute("
    INSERT INTO branches (name, code, phone, email, address, status, created_at, updated_at)
    VALUES ('Test Branch', :code, '+94 77 999 8888', 'test@branch.lk', 'Test Address', 'active', NOW(), NOW())
", [':code' => $testCode]);
$testBranchId = (int)Database::lastInsertId();
assertCondition($testBranchId > 0, "Created isolated test branch ID #{$testBranchId}");

$testBranchInfo = Settings::getBranchInfo($testBranchId);
assertCondition($testBranchInfo['branch_phone'] === '+94 77 999 8888', "Settings::getBranchInfo correctly pulls custom branch phone: '{$testBranchInfo['branch_phone']}'");

// Clean up isolated test branch
Database::execute("DELETE FROM branches WHERE id = :id", [':id' => $testBranchId]);
assertCondition(true, "Cleaned up test branch ID #{$testBranchId}");

// 5. Test Mock Logo Setting
$dummyLogoPath = 'uploads/test_logo_mock.png';
$originalLogo = Settings::get('company_logo', '');
Settings::set('company_logo', $dummyLogoPath, 1);
$updatedCompanyInfo = Settings::getCompanyInfo();
assertCondition($updatedCompanyInfo['logo'] === $dummyLogoPath, "Settings::getCompanyInfo reflects updated logo path: '{$updatedCompanyInfo['logo']}'");
// Restore original logo
Settings::set('company_logo', $originalLogo, 1);

// 6. Test Receipt Data Branch Phone Integration
$lastSale = Database::fetchOne("SELECT id FROM sales ORDER BY id DESC LIMIT 1");
if ($lastSale) {
    $receiptData = Sale::getReceiptData((int)$lastSale['id']);
    assertCondition(!empty($receiptData['sale']['branch_name']), "Sale::getReceiptData contains branch_name: '{$receiptData['sale']['branch_name']}'");
    assertCondition(isset($receiptData['sale']['branch_phone']), "Sale::getReceiptData contains branch_phone: '{$receiptData['sale']['branch_phone']}'");
    assertCondition(isset($receiptData['sale']['branch_address']), "Sale::getReceiptData contains branch_address");
} else {
    echo "[INFO] No sales found to test getReceiptData\n";
}

echo "\n======================================================\n";
echo "SUMMARY: Passed: {$passed}, Failed: {$failed}\n";
echo "======================================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
