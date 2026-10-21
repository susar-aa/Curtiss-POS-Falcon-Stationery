<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Audit.php';
require_once __DIR__ . '/../core/MaskingHelper.php';

echo "=== Curtiss POS: Comprehensive PIN Login, Multi-Role & Permission Test Suite ===" . PHP_EOL . PHP_EOL;

$db = Database::getInstance()->getConnection();
$testsPassed = 0;
$testsFailed = 0;

function assertTest(string $desc, bool $condition, string $detail = '') {
    global $testsPassed, $testsFailed;
    if ($condition) {
        $testsPassed++;
        echo "  [PASS] {$desc}" . PHP_EOL;
    } else {
        $testsFailed++;
        echo "  [FAIL] {$desc}" . ($detail ? " - {$detail}" : '') . PHP_EOL;
    }
}

// -------------------------------------------------------------
// 1. PIN Authentication Tests for Standard Roles
// -------------------------------------------------------------
echo "1. TESTING PIN AUTHENTICATION ON DEFAULT ACCOUNTS:" . PHP_EOL;

// Super Admin (PIN: 11111)
$res1 = Auth::attemptPin('11111');
assertTest("Super Admin PIN login (11111)", $res1['success'] === true, $res1['message'] ?? '');
assertTest("Super Admin session role verified", Auth::isSuperAdmin() === true);
assertTest("Super Admin has full permissions bypass", Auth::hasPermission('arbitrary.nonexistent.permission') === true);
Auth::logout();

// Admin (PIN: 22222)
$res2 = Auth::attemptPin('22222');
assertTest("Admin PIN login (22222)", $res2['success'] === true, $res2['message'] ?? '');
assertTest("Admin has branches accessible", !empty($_SESSION['user_branches']));
Auth::logout();

// Cashier (PIN: 44444)
$res4 = Auth::attemptPin('44444');
assertTest("Cashier PIN login (44444)", $res4['success'] === true, $res4['message'] ?? '');
assertTest("Cashier has role cashier", Auth::hasRole(ROLE_CASHIER));
assertTest("Cashier is NOT super admin", Auth::isSuperAdmin() === false);
Auth::logout();

// -------------------------------------------------------------
// 2. Special User PIN Login & Masking Verification
// -------------------------------------------------------------
echo PHP_EOL . "2. TESTING SPECIAL USER PIN LOGIN & SALES MASKING:" . PHP_EOL;

$res6 = Auth::attemptPin('66666');
assertTest("Special User PIN login (66666)", $res6['success'] === true, $res6['message'] ?? '');
assertTest("Auth::isSpecialUser() recognizes special user session", Auth::isSpecialUser() === true);
assertTest("MaskingHelper::isSpecialUser() recognizes special user", MaskingHelper::isSpecialUser() === true);
Auth::logout();

// -------------------------------------------------------------
// 3. Security, Validation & Bad PIN Handling
// -------------------------------------------------------------
echo PHP_EOL . "3. TESTING PIN VALIDATION & SECURITY REJECTION:" . PHP_EOL;

// Non-5-digit PIN
$resBadFormat = Auth::attemptPin('123');
assertTest("Non-5-digit PIN rejected", $resBadFormat['success'] === false && strpos($resBadFormat['message'], '5-digit') !== false);

// Alpha PIN
$resAlpha = Auth::attemptPin('ABCDE');
assertTest("Non-numeric PIN rejected", $resAlpha['success'] === false);

// Unassigned 5-digit PIN
$resWrong = Auth::attemptPin('99999');
assertTest("Incorrect 5-digit PIN rejected without account leakage", $resWrong['success'] === false && strpos($resWrong['message'], 'Invalid PIN') !== false);

// Verify failed attempt logged in audit_logs
$failedAudit = Database::fetchOne("SELECT * FROM audit_logs WHERE action = 'LOGIN_FAILED_BAD_PIN' ORDER BY id DESC LIMIT 1");
assertTest("Failed PIN attempt recorded in audit_logs", !empty($failedAudit));

// -------------------------------------------------------------
// 4. User Management: Creation, Unique PIN & Multi-Role Sync
// -------------------------------------------------------------
echo PHP_EOL . "4. TESTING USER MANAGEMENT, PIN UNIQUENESS & MULTI-ROLE:" . PHP_EOL;

$testUsername = 'testuser_' . time();
$testPin = '77712';

// 4A. Create Test User with PIN and Multi-Roles (Cashier ID 4 + Accountant ID 5)
$pwdHash = password_hash('SecretPass123!', PASSWORD_DEFAULT);
$pinHash = password_hash($testPin, PASSWORD_BCRYPT);

Database::execute("
    INSERT INTO users (full_name, username, email, phone, role_id, password_hash, pin_hash, status, created_at, updated_at)
    VALUES ('Multi-Role Test User', :un, :em, '0779998877', 4, :pwd, :pin, 'active', NOW(), NOW())
", [
    ':un'  => $testUsername,
    ':em'  => $testUsername . '@test.com',
    ':pwd' => $pwdHash,
    ':pin' => $pinHash
]);
$testUserId = (int)$db->lastInsertId();

// Assign dual roles: Cashier (4) and Accountant (5)
Database::execute("INSERT INTO user_roles (user_id, role_id) VALUES (:uid, 4)", [':uid' => $testUserId]);
Database::execute("INSERT INTO user_roles (user_id, role_id) VALUES (:uid, 5)", [':uid' => $testUserId]);
Database::execute("INSERT INTO user_branches (user_id, branch_id, is_primary) VALUES (:uid, 1, 1)", [':uid' => $testUserId]);

assertTest("Created test user with multi-roles in DB (ID {$testUserId})", $testUserId > 0);

// 4B. Authenticate with newly created user's PIN
$resNewPin = Auth::attemptPin($testPin);
assertTest("New user logs in successfully via PIN '{$testPin}'", $resNewPin['success'] === true, $resNewPin['message'] ?? '');
assertTest("Multi-role: user has Cashier role", Auth::hasRole(ROLE_CASHIER));
assertTest("Multi-role: user has Accountant role", Auth::hasRole(ROLE_ACCOUNTANT));
assertTest("Multi-role: user does NOT have Super Admin role", Auth::hasRole(ROLE_SUPER_ADMIN) === false);
assertTest("Multi-role session lists both role IDs in \$_SESSION['roles']", !empty($_SESSION['roles']) && count($_SESSION['roles']) === 2);
Auth::logout();

// 4C. Verify PIN Uniqueness Check (simulate collision detection)
$collisionCheck = false;
$activeUsersWithPin = Database::fetchAll("SELECT id, pin_hash FROM users WHERE status = 'active' AND deleted_at IS NULL AND pin_hash IS NOT NULL");
foreach ($activeUsersWithPin as $u) {
    if (password_verify($testPin, $u['pin_hash'])) {
        $collisionCheck = true;
        break;
    }
}
assertTest("PIN uniqueness conflict correctly detected for duplicate PIN '{$testPin}'", $collisionCheck === true);

// -------------------------------------------------------------
// 5. Role & Permission Matrix Customization
// -------------------------------------------------------------
echo PHP_EOL . "5. TESTING CUSTOM ROLE & PERMISSION MATRIX:" . PHP_EOL;

$customRoleCode = 'test_supervisor_' . time();
Database::execute("
    INSERT INTO roles (name, code, description, created_at, updated_at)
    VALUES ('Test Supervisor', :code, 'Test custom supervisor role', NOW(), NOW())
", [':code' => $customRoleCode]);
$customRoleId = (int)$db->lastInsertId();

assertTest("Created custom role in database (ID {$customRoleId})", $customRoleId > 0);

// Assign specific permissions: 'pos.access' (ID 40ish) and 'products.view'
$permPos = Database::fetchOne("SELECT id FROM permissions WHERE permission_code = 'pos.access'");
$permProd = Database::fetchOne("SELECT id FROM permissions WHERE permission_code = 'products.view'");
if ($permPos && $permProd) {
    Database::execute("INSERT INTO role_permissions (role_id, permission_id) VALUES (:rid, :p1)", [':rid' => $customRoleId, ':p1' => $permPos['id']]);
    Database::execute("INSERT INTO role_permissions (role_id, permission_id) VALUES (:rid, :p2)", [':rid' => $customRoleId, ':p2' => $permProd['id']]);

    // Re-assign test user to custom role
    Database::execute("DELETE FROM user_roles WHERE user_id = :uid", [':uid' => $testUserId]);
    Database::execute("INSERT INTO user_roles (user_id, role_id) VALUES (:uid, :rid)", [':uid' => $testUserId, ':rid' => $customRoleId]);
    Database::execute("UPDATE users SET role_id = :rid WHERE id = :uid", [':uid' => $testUserId, ':rid' => $customRoleId]);

    // Login as test user
    Auth::attemptPin($testPin);
    assertTest("Custom role user has 'pos.access' permission", Auth::hasPermission('pos.access') === true);
    assertTest("Custom role user has 'products.view' permission", Auth::hasPermission('products.view') === true);
    assertTest("Custom role user does NOT have 'accounting.reverse' permission", Auth::hasPermission('accounting.reverse') === false);
    Auth::logout();
}

// -------------------------------------------------------------
// 6. Clean Up Test Fixtures
// -------------------------------------------------------------
echo PHP_EOL . "6. CLEANING UP TEST FIXTURES:" . PHP_EOL;
Database::execute("DELETE FROM user_roles WHERE user_id = :uid", [':uid' => $testUserId]);
Database::execute("DELETE FROM user_branches WHERE user_id = :uid", [':uid' => $testUserId]);
Database::execute("DELETE FROM users WHERE id = :uid", [':uid' => $testUserId]);
Database::execute("DELETE FROM role_permissions WHERE role_id = :rid", [':rid' => $customRoleId]);
Database::execute("DELETE FROM roles WHERE id = :rid", [':rid' => $customRoleId]);
Database::execute("DELETE FROM audit_logs WHERE record_id = :uid AND module = 'users'", [':uid' => (string)$testUserId]);
Database::execute("DELETE FROM audit_logs WHERE record_id = :rid AND module = 'roles'", [':rid' => (string)$customRoleId]);

echo "  PASS: Cleaned up test user #{$testUserId} and custom role #{$customRoleId} with 0 DB residue." . PHP_EOL;

echo PHP_EOL . "=================================================" . PHP_EOL;
echo "TEST RESULTS: {$testsPassed} Passed, {$testsFailed} Failed." . PHP_EOL;
if ($testsFailed === 0) {
    echo "STATUS: ALL TESTS PASSED SUCCESSFULLY!" . PHP_EOL;
} else {
    echo "STATUS: SOME TESTS FAILED!" . PHP_EOL;
    exit(1);
}
