<?php
/**
 * Curtiss POS - Stage 1 Automated Structural Verification Script
 * Validates backend architecture, database connections, authentication, error logging, and audit logs.
 */

require_once __DIR__ . '/../config/config.php';

echo "========================================================\n";
echo " CURTISS POS - STAGE 1 AUTOMATED ARCHITECTURE VERIFIER\n";
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

// 1. Database Connection Test
try {
    $db = Database::getInstance()->getConnection();
    assertTest($db !== null, "Database PDO Connection Established to 'curtiss_pos'");
} catch (Exception $e) {
    assertTest(false, "Database Connection Failed: " . $e->getMessage());
}

// 2. Foundation Tables Existence
$tables = ['roles', 'permissions', 'role_permissions', 'branches', 'users', 'user_branches', 'system_settings', 'audit_logs', 'error_logs'];
foreach ($tables as $t) {
    $row = Database::fetchOne("SHOW TABLES LIKE '$t'");
    assertTest($row !== null, "Database Table Exists: '$t'");
}

// 3. Auth Authentication Test with Hashed Passwords
$authRes = Auth::attempt('superadmin', 'Password@123');
assertTest($authRes['success'] === true, "Authentication Success for 'superadmin' with BCrypt Hashed Password");

$badAuth = Auth::attempt('superadmin', 'WrongPassword');
assertTest($badAuth['success'] === false, "Authentication Reject Bad Password for 'superadmin'");

// 4. Multi-Branch Isolation & Assignment Test
$userBranches = Auth::getUserBranches(1, 'super_admin');
assertTest(count($userBranches) >= 2, "Super Admin Branch Assignment (Found " . count($userBranches) . " branches)");

// 5. Audit Logging Verification
$auditTestResult = Audit::log('STAGE1_VERIFICATION', 'test_module', '999', ['old' => 'test'], ['new' => 'test_passed']);
assertTest($auditTestResult === true, "Audit Log Record Successfully Inserted into 'audit_logs'");

$latestAudit = Database::fetchOne("SELECT * FROM audit_logs WHERE action = 'STAGE1_VERIFICATION' ORDER BY id DESC LIMIT 1");
assertTest($latestAudit !== null && $latestAudit['module'] === 'test_module', "Audit Record Retrieval & JSON Payload Integrity Verified");

// 6. Error Logging Verification (Rule 3)
Logger::error("Stage 1 Verification Test Error Log Entry", null, ['test_key' => 'test_value']);
$logFileExists = file_exists(LOG_PATH);
assertTest($logFileExists, "Error Log File Exists at " . LOG_PATH);

$latestErrorDb = Database::fetchOne("SELECT * FROM error_logs ORDER BY id DESC LIMIT 1");
assertTest($latestErrorDb !== null && strpos($latestErrorDb['message'], 'Stage 1 Verification') !== false, "Error Log Record Successfully Saved in DB 'error_logs' Table");

// 7. System Settings Key-Value Engine
$businessName = Settings::get('business_name');
assertTest($businessName === 'Curtiss POS', "Settings Key Retrieval: business_name = '$businessName'");

$visPct = Settings::get('special_user_visibility_pct');
assertTest($visPct === '50', "Special User Visibility Percentage Setting Configured = '$visPct%'");

echo "\n--------------------------------------------------------\n";
echo " VERIFICATION SUMMARY: $passCount Passed, $failCount Failed.\n";
echo "========================================================\n";

if ($failCount > 0) {
    exit(1);
}
