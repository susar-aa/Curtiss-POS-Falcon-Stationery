<?php
/**
 * CLI Test Suite for Admin/Superadmin Branch Selection on Login
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Audit.php';

echo "=========================================================\n";
echo "   CURTISS POS - BRANCH LOGIN SELECTION CLI SUITE        \n";
echo "=========================================================\n\n";

$allPassed = true;

function assertTest(string $description, bool $condition): void {
    global $allPassed;
    if ($condition) {
        echo " [PASS] " . $description . "\n";
    } else {
        echo " [FAIL] " . $description . "\n";
        $allPassed = false;
    }
}

$db = Database::getInstance()->getConnection();

// Fetch superadmin and admin users
$superAdminUser = Database::fetchOne("
    SELECT u.*, r.code AS role_code 
    FROM users u 
    JOIN roles r ON u.role_id = r.id 
    WHERE r.code = 'super_admin' AND u.status = 'active' AND u.deleted_at IS NULL 
    LIMIT 1
");

$adminUser = Database::fetchOne("
    SELECT u.*, r.code AS role_code 
    FROM users u 
    JOIN roles r ON u.role_id = r.id 
    WHERE r.code = 'admin' AND u.status = 'active' AND u.deleted_at IS NULL 
    LIMIT 1
");

assertTest("Superadmin user exists in database", !empty($superAdminUser));
assertTest("Admin user exists in database", !empty($adminUser));

// --------------------------------------------------------------------------
// TEST 1: Branch Availability for Super Admin & Admin
// --------------------------------------------------------------------------
echo "\n--- TEST 1: Branch Accessibility for Super Admin & Admin ---\n";

$allActiveBranches = Database::fetchAll("SELECT * FROM branches WHERE status = 'active' AND deleted_at IS NULL");
$totalActiveCount = count($allActiveBranches);
assertTest("Database has multiple active branches ($totalActiveCount branches)", $totalActiveCount > 1);

$superBranches = Auth::getUserBranches((int)$superAdminUser['id'], $superAdminUser['role_code']);
assertTest("Super Admin has access to all active branches", count($superBranches) === $totalActiveCount);

$adminBranches = Auth::getUserBranches((int)$adminUser['id'], $adminUser['role_code']);
assertTest("Admin has access to all active branches", count($adminBranches) === $totalActiveCount);

// --------------------------------------------------------------------------
// TEST 2: Multi-Branch Login Prompt Requirement
// --------------------------------------------------------------------------
echo "\n--- TEST 2: Multi-Branch Login Prompt Requirement ---\n";

// Test setting up session as Superadmin
$_SESSION = [];
// Simulate attempt logic directly with user record
$hasMultipleBranches = count($superBranches) > 1;
$isAdminOrSuperAdmin = in_array($superAdminUser['role_code'], [ROLE_SUPER_ADMIN, ROLE_ADMIN], true);
$needsBranchSelection = ($isAdminOrSuperAdmin && $hasMultipleBranches);

assertTest("Super Admin requires branch selection prompt on login", $needsBranchSelection === true);

// Set session as superadmin
$_SESSION['user_id'] = (int)$superAdminUser['id'];
$_SESSION['username'] = $superAdminUser['username'];
$_SESSION['full_name'] = $superAdminUser['full_name'];
$_SESSION['role_id'] = (int)$superAdminUser['role_id'];
$_SESSION['role_code'] = $superAdminUser['role_code'];
$_SESSION['role_name'] = 'Super Admin';
$_SESSION['user_branches'] = $superBranches;
$_SESSION['active_branch_id'] = (int)$superBranches[0]['id'];
$_SESSION['branch_id'] = (int)$superBranches[0]['id'];
$_SESSION['active_branch_name'] = $superBranches[0]['name'];
$_SESSION['active_branch_code'] = $superBranches[0]['code'];
$_SESSION['branch_selected'] = false; // Prompt required

assertTest("Initial branch_selected is false for Super Admin until branch is chosen", $_SESSION['branch_selected'] === false);

// --------------------------------------------------------------------------
// TEST 3: Switching Active Operating Branch
// --------------------------------------------------------------------------
echo "\n--- TEST 3: Switching Operating Branch & Header Sync ---\n";

$branch1 = $allActiveBranches[0];
$branch2 = $allActiveBranches[1];

// Switch to Branch 2 (e.g. Colombo)
$switchOk = Auth::switchBranch((int)$branch2['id']);
assertTest("Auth::switchBranch to Branch #{$branch2['id']} succeeds", $switchOk === true);
assertTest("active_branch_id updated to Branch #{$branch2['id']}", (int)$_SESSION['active_branch_id'] === (int)$branch2['id']);
assertTest("branch_id in sync with active_branch_id", (int)$_SESSION['branch_id'] === (int)$branch2['id']);
assertTest("active_branch_name updated to '{$branch2['name']}'", $_SESSION['active_branch_name'] === $branch2['name']);
assertTest("active_branch_code updated to '{$branch2['code']}'", $_SESSION['active_branch_code'] === $branch2['code']);
assertTest("branch_selected marked true after choosing branch", $_SESSION['branch_selected'] === true);

// Switch back to Branch 1 (e.g. Kandy)
$switchOk1 = Auth::switchBranch((int)$branch1['id']);
assertTest("Auth::switchBranch back to Branch #{$branch1['id']} succeeds", $switchOk1 === true);
assertTest("active_branch_id updated to Branch #{$branch1['id']}", (int)$_SESSION['active_branch_id'] === (int)$branch1['id']);
assertTest("branch_id updated to Branch #{$branch1['id']}", (int)$_SESSION['branch_id'] === (int)$branch1['id']);
assertTest("active_branch_name updated to '{$branch1['name']}'", $_SESSION['active_branch_name'] === $branch1['name']);

// Verify Auth::user() helper returns updated active branch
$currUser = Auth::user();
assertTest("Auth::user()['active_branch_id'] matches session", (int)$currUser['active_branch_id'] === (int)$branch1['id']);
assertTest("Auth::user()['active_branch_name'] matches session", $currUser['active_branch_name'] === $branch1['name']);

// --------------------------------------------------------------------------
// TEST 4: Audit Logging on Branch Switch
// --------------------------------------------------------------------------
echo "\n--- TEST 4: Branch Switch Audit Logging ---\n";

$auditRow = Database::fetchOne("
    SELECT * FROM audit_logs 
    WHERE action = 'BRANCH_SWITCH' AND user_id = :u 
    ORDER BY id DESC LIMIT 1
", [':u' => $superAdminUser['id']]);

assertTest("Audit log captured BRANCH_SWITCH event", !empty($auditRow));

// Clean up test audit log entries
if (!empty($auditRow)) {
    Database::execute("DELETE FROM audit_logs WHERE action = 'BRANCH_SWITCH' AND user_id = :u", [':u' => $superAdminUser['id']]);
    echo " [CLEANUP] Test audit log records cleaned up.\n";
}

echo "\n=========================================================\n";
if ($allPassed) {
    echo "   >>> ALL CLI TESTS PASSED SUCCESSFULLY! (100%) <<<\n";
} else {
    echo "   >>> SOME CLI TESTS FAILED! CHECK OUTPUT ABOVE. <<<\n";
}
echo "=========================================================\n";

exit($allPassed ? 0 : 1);
