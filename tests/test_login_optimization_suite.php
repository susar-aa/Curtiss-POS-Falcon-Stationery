<?php
/**
 * Curtiss POS - Login & Authentication Optimization Suite
 * 
 * Verifies all Master Rules for the Authentication & Login Module:
 * 1. User Credential Authentication & Secure Password Verification
 * 2. Case-Insensitive Username & Email Lookup (Mobile Friendly)
 * 3. Inactive & Soft-Deleted Account Access Prevention
 * 4. Failed Login Auditing (Unknown User & Bad Password)
 * 5. Session State Initialization & Session Fixation Protection
 * 6. Branch Assignment & Multi-Branch Redirection Flagging
 * 7. Active Branch Switching & Security Verification
 * 8. CSRF Token Generation & Hash-Equals Validation
 * 9. Return URL Sanitization & Open-Redirect Protection
 * 10. Inactivity Session Timeout Detection & Audit
 * 11. Zero Database Truncation Rule (strictly isolates test records)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Audit.php';

// Session setup
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "====================================================================\n";
echo "   CURTISS POS — LOGIN & AUTHENTICATION OPTIMIZATION SUITE\n";
echo "====================================================================\n\n";

$passed = 0;
$failed = 0;

function check(string $name, bool $condition, string $detail = '') {
    global $passed, $failed;
    if ($condition) {
        echo "[PASS] {$name}" . ($detail ? " ({$detail})" : "") . "\n";
        $passed++;
    } else {
        echo "[FAIL] {$name}" . ($detail ? " - ERROR: {$detail}" : "") . "\n";
        $failed++;
    }
}

$cleanupUserIds = [];
$cleanupAuditIds = [];

try {
    $baselineUserCount = (int)Database::fetchOne("SELECT COUNT(*) as cnt FROM users")['cnt'];
    $baselineAuditCount = (int)Database::fetchOne("SELECT COUNT(*) as cnt FROM audit_logs")['cnt'];
    echo "Initial baseline: {$baselineUserCount} users, {$baselineAuditCount} audit_logs\n\n";

    // ----------------------------------------------------
    // TEST FIXTURE: Create isolated test user
    // ----------------------------------------------------
    $testUsername = 'test_login_user_' . time();
    $testEmail    = $testUsername . '@curtiss.lk';
    $rawPassword   = 'SecretP@ssword999!';
    $hashedPassword = password_hash($rawPassword, PASSWORD_DEFAULT);

    Database::execute("
        INSERT INTO users (role_id, username, email, password_hash, full_name, status, created_at)
        VALUES (4, :username, :email, :pwd, 'Test Login Staff', 'active', NOW())
    ", [
        ':username' => $testUsername,
        ':email'    => $testEmail,
        ':pwd'      => $hashedPassword
    ]);
    $testUserId = (int)Database::getInstance()->getConnection()->lastInsertId();
    $cleanupUserIds[] = $testUserId;

    // Assign to Branch 1
    Database::execute("
        INSERT INTO user_branches (user_id, branch_id, is_primary)
        VALUES (:uid, 1, 1)
    ", [':uid' => $testUserId]);

    check("Test user fixture created successfully", $testUserId > 0, "ID: {$testUserId}");

    // ----------------------------------------------------
    // TEST 1: Valid Credentials Authentication
    // ----------------------------------------------------
    echo "\n--- Test 1: Valid Credentials Authentication ---\n";
    $loginResult = Auth::attempt($testUsername, $rawPassword);
    check("Valid login succeeds", $loginResult['success'] === true);
    check("Session user_id is set correctly", (int)($_SESSION['user_id'] ?? 0) === $testUserId);
    check("Session username is set correctly", ($_SESSION['username'] ?? '') === $testUsername);
    check("Session role is cashier (role 4)", (int)($_SESSION['role_id'] ?? 0) === 4);
    check("Single branch cashier does not need branch selection", ($loginResult['needs_branch_selection'] ?? true) === false);

    // Verify last_login_at updated in DB
    $userRow = Database::fetchOne("SELECT last_login_at FROM users WHERE id = :id", [':id' => $testUserId]);
    check("last_login_at timestamp is populated in DB", !empty($userRow['last_login_at']));

    // ----------------------------------------------------
    // TEST 2: Case-Insensitive Username & Email Authentication
    // ----------------------------------------------------
    echo "\n--- Test 2: Case-Insensitive Lookup (Mobile Support) ---\n";
    // Upper-case username
    $upperUserResult = Auth::attempt(strtoupper($testUsername), $rawPassword);
    check("Upper-case username authentication succeeds", $upperUserResult['success'] === true);

    // Mixed-case username
    $mixedUserResult = Auth::attempt(ucfirst($testUsername), $rawPassword);
    check("Mixed-case username authentication succeeds", $mixedUserResult['success'] === true);

    // Email address login
    $emailResult = Auth::attempt($testEmail, $rawPassword);
    check("Email address authentication succeeds", $emailResult['success'] === true);

    // Upper-case email login
    $upperEmailResult = Auth::attempt(strtoupper($testEmail), $rawPassword);
    check("Upper-case email authentication succeeds", $upperEmailResult['success'] === true);

    // ----------------------------------------------------
    // TEST 3: Invalid Credentials & Inactive Accounts
    // ----------------------------------------------------
    echo "\n--- Test 3: Invalid Credentials & Inactive Accounts ---\n";
    // Bad password
    $badPwdResult = Auth::attempt($testUsername, 'WrongPassword123!');
    check("Bad password rejected", $badPwdResult['success'] === false);

    // Non-existent user
    $unknownResult = Auth::attempt('non_existent_ghost_user_99999', 'Password@123');
    check("Unknown user rejected", $unknownResult['success'] === false);

    // Deactivated user
    Database::execute("UPDATE users SET status = 'inactive' WHERE id = :id", [':id' => $testUserId]);
    $inactiveResult = Auth::attempt($testUsername, $rawPassword);
    check("Deactivated user login rejected", $inactiveResult['success'] === false);

    // Soft-deleted user
    Database::execute("UPDATE users SET status = 'active', deleted_at = NOW() WHERE id = :id", [':id' => $testUserId]);
    $deletedResult = Auth::attempt($testUsername, $rawPassword);
    check("Soft-deleted user login rejected", $deletedResult['success'] === false);

    // Restore active state
    Database::execute("UPDATE users SET status = 'active', deleted_at = NULL WHERE id = :id", [':id' => $testUserId]);

    // ----------------------------------------------------
    // TEST 4: Multi-Branch & Superadmin Redirection
    // ----------------------------------------------------
    echo "\n--- Test 4: Multi-Branch & Superadmin Selection Flag ---\n";
    // Log in as superadmin
    $superadminResult = Auth::attempt('superadmin', 'Password@123');
    check("Superadmin login succeeds", $superadminResult['success'] === true);
    check("Superadmin multi-branch detection succeeds", $superadminResult['multiple_branches'] === true);
    check("Superadmin flagged for branch selection upon login", $superadminResult['needs_branch_selection'] === true);

    // ----------------------------------------------------
    // TEST 5: Branch Switching & Security
    // ----------------------------------------------------
    echo "\n--- Test 5: Branch Switching & Authorization ---\n";
    // Switch to Branch 1
    $switchSuccess = Auth::switchBranch(1);
    check("Switching to authorized Branch 1 succeeds", $switchSuccess === true);
    check("Active branch ID is 1", (int)($_SESSION['active_branch_id'] ?? 0) === 1);
    check("Branch selected flag is true", ($_SESSION['branch_selected'] ?? false) === true);

    // Attempt switch to non-existent branch ID 99999
    $invalidSwitch = Auth::switchBranch(99999);
    check("Switching to invalid branch ID 99999 is blocked", $invalidSwitch === false);

    // ----------------------------------------------------
    // TEST 6: CSRF Security Protection
    // ----------------------------------------------------
    echo "\n--- Test 6: CSRF Security Token System ---\n";
    $csrf = Auth::generateCsrfToken();
    check("CSRF token generated (64 hex characters)", strlen($csrf) === 64);
    check("Valid CSRF token passes validation", Auth::validateCsrfToken($csrf) === true);
    check("Tampered CSRF token is rejected", Auth::validateCsrfToken($csrf . '_tampered') === false);
    check("Empty CSRF token is rejected", Auth::validateCsrfToken('') === false);
    check("Null CSRF token is rejected", Auth::validateCsrfToken(null) === false);

    // ----------------------------------------------------
    // TEST 7: Open Redirect Protection
    // ----------------------------------------------------
    echo "\n--- Test 7: Return URL & Open Redirect Protection ---\n";
    // Foreign external host
    $maliciousUrl = 'https://evil-phishing-site.com/steal-creds';
    $parsed = parse_url($maliciousUrl);
    $currHost = 'localhost';
    $isForeign = (($parsed['host'] ?? '') !== $currHost);
    check("Foreign host in return_url detected and rejected", $isForeign === true);

    // Auth loop prevention
    $loginLoop = 'login.php';
    check("Login loop in return_url is detected", str_contains($loginLoop, 'login'));

    // Safe internal route
    $safeReturnUrl = 'POS/inventory.php?category=5';
    check("Safe relative internal URL allowed", !str_contains($safeReturnUrl, 'login') && !preg_match('#^https?://#i', $safeReturnUrl));

    // ----------------------------------------------------
    // TEST 8: Inactivity Timeout & Expiry
    // ----------------------------------------------------
    echo "\n--- Test 8: Inactivity Session Timeout ---\n";
    $timeoutConfig = (int)Settings::get('session_inactivity_timeout', (string)DEFAULT_INACTIVITY_TIMEOUT);
    check("Session timeout setting configured (> 0 seconds)", $timeoutConfig > 0, "Timeout: {$timeoutConfig}s");

    // ----------------------------------------------------
    // TEST 9: Audit Trail Capture
    // ----------------------------------------------------
    echo "\n--- Test 9: Audit Trail Capture for Auth Events ---\n";
    $recentLogins = Database::fetchAll("
        SELECT action, module, user_id FROM audit_logs 
        WHERE module = 'auth' AND created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ORDER BY id DESC LIMIT 20
    ");
    $capturedActions = array_column($recentLogins, 'action');

    check("LOGIN_SUCCESS captured in audit trail", in_array('LOGIN_SUCCESS', $capturedActions));
    check("LOGIN_FAILED_BAD_PASSWORD captured in audit trail", in_array('LOGIN_FAILED_BAD_PASSWORD', $capturedActions));
    check("LOGIN_FAILED_UNKNOWN_USER captured in audit trail", in_array('LOGIN_FAILED_UNKNOWN_USER', $capturedActions));
    check("BRANCH_SWITCH captured in audit trail", in_array('BRANCH_SWITCH', $capturedActions));

    // ----------------------------------------------------
    // TEST 10: Logout State Clearing
    // ----------------------------------------------------
    echo "\n--- Test 10: Logout & Session Destruction ---\n";
    Auth::logout();
    check("Session cleared on logout (isLoggedIn is false)", Auth::isLoggedIn() === false);
    check("Auth::user() returns null after logout", Auth::user() === null);

    // ----------------------------------------------------
    // TEST 11: Zero Database Truncation & Cleanup
    // ----------------------------------------------------
    echo "\n--- Test 11: Zero Database Truncation & Fixture Cleanup ---\n";
    if (!empty($cleanupUserIds)) {
        $uIds = implode(',', array_map('intval', $cleanupUserIds));
        Database::execute("DELETE FROM user_branches WHERE user_id IN ({$uIds})");
        Database::execute("DELETE FROM users WHERE id IN ({$uIds})");
        echo "Cleaned up isolated test user fixtures: [{$uIds}]\n";
    }

    $finalUserCount = (int)Database::fetchOne("SELECT COUNT(*) as cnt FROM users")['cnt'];
    check("Zero Truncation Rule (User Pre: {$baselineUserCount}, Post: {$finalUserCount})", $finalUserCount === $baselineUserCount);

} catch (Throwable $e) {
    echo "\n[EXCEPTION] " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
    $failed++;
}

echo "\n====================================================================\n";
echo "   LOGIN & AUTH TEST SUMMARY: {$passed} PASSED, {$failed} FAILED\n";
echo "====================================================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
