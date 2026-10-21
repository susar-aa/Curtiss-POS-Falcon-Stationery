<?php
/**
 * Test Suite: Chart of Accounts Optimization & Compliance Suite
 * Tests all functionality, database indexes, system protections, universal search,
 * security permissions, and UI design rules for the Chart of Accounts module.
 * ZERO DATABASE TRUNCATIONS - Isolated fixtures only.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Accounting.php';
require_once __DIR__ . '/../core/Audit.php';

$passed = 0;
$failed = 0;
$testAccountsCreated = [];

function assertTest(string $desc, bool $condition, string $details = '') {
    global $passed, $failed;
    if ($condition) {
        echo " [PASS] $desc\n";
        $passed++;
    } else {
        echo " [FAIL] $desc - $details\n";
        $failed++;
    }
}

echo "====================================================================\n";
echo "   CURTISS POS - CHART OF ACCOUNTS OPTIMIZATION TEST SUITE\n";
echo "====================================================================\n\n";

try {
    $db = Database::getInstance()->getConnection();

    // ---------------------------------------------------------
    // 1. DATABASE SCHEMA & PERFORMANCE INDEXES
    // ---------------------------------------------------------
    echo "--- 1. Database Schema & Index Verification ---\n";
    $stmt = $db->query("SHOW INDEX FROM chart_of_accounts");
    $indexes = [];
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $indexes[$r['Key_name']] = true;
    }

    assertTest("Index 'idx_coa_stat_type' exists on chart_of_accounts", isset($indexes['idx_coa_stat_type']));
    assertTest("Index 'idx_coa_branch_status' exists on chart_of_accounts", isset($indexes['idx_coa_branch_status']));
    assertTest("Index 'idx_coa_name' exists on chart_of_accounts", isset($indexes['idx_coa_name']));
    assertTest("Index 'idx_coa_code' or unique code key exists", isset($indexes['idx_coa_code']) || isset($indexes['account_code']));

    // ---------------------------------------------------------
    // 2. CORE SYSTEM ACCOUNTS INTEGRITY
    // ---------------------------------------------------------
    echo "\n--- 2. Core System Accounts Integrity ---\n";
    $coreCodes = ['1010', '1012', '1015', '1020', '1040', '1200', '2010', '2020', '3010', '4010', '5010'];
    foreach ($coreCodes as $code) {
        $acc = Database::fetchOne("SELECT * FROM chart_of_accounts WHERE account_code = :c", [':c' => $code]);
        assertTest("Core system account '{$code}' exists", !empty($acc));
        if ($acc) {
            assertTest("Core system account '{$code}' has is_system = 1", (int)$acc['is_system'] === 1);
            assertTest("Core system account '{$code}' is active", $acc['status'] === 'active');
        }
    }

    // ---------------------------------------------------------
    // 3. SYSTEM ACCOUNT PROTECTION RULES
    // ---------------------------------------------------------
    echo "\n--- 3. System Account Protection Rules ---\n";
    $sysAcc = Database::fetchOne("SELECT * FROM chart_of_accounts WHERE account_code = '1012'");
    if ($sysAcc) {
        // Attempt to change code
        $resCode = Accounting::updateAccount((int)$sysAcc['id'], ['account_code' => '9999'], 1);
        assertTest("System account code change is rejected", !$resCode['success']);

        // Attempt to deactivate system account via update
        $resDeact = Accounting::updateAccount((int)$sysAcc['id'], ['status' => 'inactive'], 1);
        assertTest("System account deactivation via update is rejected", !$resDeact['success']);

        // Attempt to deactivate system account via toggle
        $resToggle = Accounting::toggleAccountStatus((int)$sysAcc['id'], 1);
        assertTest("System account toggle is rejected", !$resToggle['success']);
    }

    // ---------------------------------------------------------
    // 4. CUSTOM ACCOUNT CREATION & CASE-INSENSITIVE UNIQUENESS
    // ---------------------------------------------------------
    echo "\n--- 4. Custom Account Creation & Duplicate Prevention ---\n";
    $testCode = 'TEST' . rand(1000, 9999);
    $testName = 'Test Automated Expense ' . rand(1000, 9999);

    $createRes = Accounting::createAccount([
        'account_code' => $testCode,
        'account_name' => $testName,
        'account_type' => 'expense'
    ], 1);

    assertTest("Custom account creation succeeds", $createRes['success']);
    if ($createRes['success'] && !empty($createRes['id'])) {
        $testAccountsCreated[] = (int)$createRes['id'];

        // Test duplicate code (case-insensitive)
        $dupCodeRes = Accounting::createAccount([
            'account_code' => strtolower($testCode),
            'account_name' => 'Another Name ' . rand(1000, 9999),
            'account_type' => 'expense'
        ], 1);
        assertTest("Duplicate account code (lowercase) is rejected", !$dupCodeRes['success']);

        // Test duplicate name (case-insensitive)
        $dupNameRes = Accounting::createAccount([
            'account_code' => 'TEST' . rand(1000, 9999),
            'account_name' => strtolower($testName),
            'account_type' => 'expense'
        ], 1);
        assertTest("Duplicate account name (lowercase) is rejected", !$dupNameRes['success']);

        // Verify audit log for creation
        $auditLog = Database::fetchOne("
            SELECT * FROM audit_logs 
            WHERE action = 'COA_ACCOUNT_CREATED' AND record_id = :id
            ORDER BY id DESC LIMIT 1
        ", [':id' => (string)$createRes['id']]);
        assertTest("Audit log captured for COA account creation", !empty($auditLog));
    }

    // ---------------------------------------------------------
    // 5. ACCOUNT STATUS TOGGLING & AUDIT LOGGING
    // ---------------------------------------------------------
    echo "\n--- 5. Custom Account Status Toggling ---\n";
    if (!empty($testAccountsCreated)) {
        $testId = $testAccountsCreated[0];

        // Toggle from active to inactive
        $toggle1 = Accounting::toggleAccountStatus($testId, 1);
        assertTest("Toggle status to inactive succeeds", $toggle1['success'] && $toggle1['new_status'] === 'inactive');

        $curr1 = Database::fetchOne("SELECT status FROM chart_of_accounts WHERE id = :id", [':id' => $testId]);
        assertTest("Database reflects 'inactive' status", ($curr1['status'] ?? '') === 'inactive');

        // Toggle from inactive to active
        $toggle2 = Accounting::toggleAccountStatus($testId, 1);
        assertTest("Toggle status back to active succeeds", $toggle2['success'] && $toggle2['new_status'] === 'active');

        $curr2 = Database::fetchOne("SELECT status FROM chart_of_accounts WHERE id = :id", [':id' => $testId]);
        assertTest("Database reflects 'active' status", ($curr2['status'] ?? '') === 'active');

        // Verify audit log
        $auditToggle = Database::fetchOne("
            SELECT * FROM audit_logs 
            WHERE action = 'COA_ACCOUNT_STATUS_TOGGLED' AND record_id = :id
            ORDER BY id DESC LIMIT 1
        ", [':id' => (string)$testId]);
        assertTest("Audit log captured for COA status toggle", !empty($auditToggle));
    }

    // ---------------------------------------------------------
    // 6. UNIVERSAL MULTI-COLUMN FLOATING SEARCH
    // ---------------------------------------------------------
    echo "\n--- 6. Universal Multi-Column Search Verification ---\n";
    // 6a. Search by code
    $searchCode = Accounting::getCOA(['search' => '1012']);
    assertTest("Search by code '1012' returns Cash Drawer", count($searchCode) >= 1 && $searchCode[0]['account_code'] === '1012');

    // 6b. Search by name
    $searchName = Accounting::getCOA(['search' => 'Cash Drawer']);
    assertTest("Search by name 'Cash Drawer' returns correct record", count($searchName) >= 1 && $searchName[0]['account_name'] === 'Cash Drawer');

    // 6c. Search by account type
    $searchType = Accounting::getCOA(['search' => 'expense']);
    assertTest("Search by type 'expense' returns matching expense accounts", count($searchType) > 0);

    // 6d. Search by system keyword
    $searchSys = Accounting::getCOA(['search' => 'System']);
    assertTest("Search by 'System' matches system accounts", count($searchSys) > 0);

    // 6e. Search by status keyword
    $searchAct = Accounting::getCOA(['search' => 'active']);
    assertTest("Search by 'active' matches active accounts", count($searchAct) > 0);

    // 6f. Search by monetary balance (raw and formatted)
    $drawerAcc = Database::fetchOne("SELECT * FROM chart_of_accounts WHERE account_code = '1012'");
    if ($drawerAcc) {
        $coaList = Accounting::getCOA(['search' => '1012']);
        if (!empty($coaList)) {
            $bal = (float)$coaList[0]['current_balance'];
            if ($bal > 0) {
                $rawSearch = (string)intval($bal);
                $searchBalRaw = Accounting::getCOA(['search' => $rawSearch]);
                assertTest("Search by raw balance integer '{$rawSearch}' returns results", count($searchBalRaw) > 0);

                $formattedSearch = 'LKR ' . number_format($bal, 0);
                $searchBalCurr = Accounting::getCOA(['search' => $formattedSearch]);
                assertTest("Search by currency formatted prefix '{$formattedSearch}' finds matching account", count($searchBalCurr) > 0);
            } else {
                echo " [SKIP] Cash drawer balance is 0.00; monetary non-zero search skipped\n";
            }
        }
    }

    // ---------------------------------------------------------
    // 7. TRANSACTION HISTORY & RUNNING BALANCE VERIFICATION
    // ---------------------------------------------------------
    echo "\n--- 7. Account Transaction History & Running Balance ---\n";
    $histRes = Accounting::getAccountHistory((int)$sysAcc['id']);
    assertTest("Account history returns success", $histRes['success']);
    assertTest("Account history includes account profile", isset($histRes['account']) && $histRes['account']['account_code'] === '1012');
    assertTest("Account history includes transactions array", isset($histRes['transactions']) && is_array($histRes['transactions']));

    // ---------------------------------------------------------
    // 8. SECURITY & PERMISSIONS
    // ---------------------------------------------------------
    echo "\n--- 8. Security & Permissions Verification ---\n";
    // Check that special users are prevented from detailed accounting
    $_SESSION['user_id'] = 1;
    $_SESSION['role_code'] = ROLE_SPECIAL_USER;
    assertTest("Auth::isSpecialUser() recognizes special user role", Auth::isSpecialUser());
    unset($_SESSION['role_code'], $_SESSION['user_id']);

    // Check CSRF token generation & validation
    $token = Auth::generateCsrfToken();
    assertTest("CSRF token generated successfully", !empty($token));
    assertTest("Valid CSRF token validates true", Auth::validateCsrfToken($token));
    assertTest("Invalid CSRF token validates false", !Auth::validateCsrfToken('invalid_token_xyz'));

    // ---------------------------------------------------------
    // 9. UI DESIGN & CURTISS SYSTEM COMPLIANCE
    // ---------------------------------------------------------
    echo "\n--- 9. UI View & Design Compliance Audit ---\n";
    $viewFile = file_get_contents(__DIR__ . '/../views/chart_of_accounts/index.php');

    assertTest("View includes CSRF meta tag", strpos($viewFile, 'meta name="csrf-token"') !== false);
    assertTest("View includes curtiss_select.css and curtiss_select.js", strpos($viewFile, 'curtiss_select.css') !== false && strpos($viewFile, 'curtiss_select.js') !== false);
    assertTest("View enforces fixed viewport height 'calc(100vh - 110px)'", strpos($viewFile, 'height: calc(100vh - 110px)') !== false);
    assertTest("View data table headers are sticky at top", strpos($viewFile, 'position: sticky') !== false && strpos($viewFile, 'top: 0') !== false);
    assertTest("View enforces standard 48px row heights on tbody tr", strpos($viewFile, 'height: 48px;') !== false);
    assertTest("View enforces zebra striping with 0.57 opacity", strpos($viewFile, '248, 250, 252, 0.57') !== false);
    assertTest("View enforces 28px standard pill badges (.prod-badge)", strpos($viewFile, 'height: 28px;') !== false && strpos($viewFile, 'min-height: 28px;') !== false);
    assertTest("View has clickable status badge trigger (.prod-badge-active / .prod-badge-deactive)", strpos($viewFile, 'toggleAccountStatus(') !== false);
    assertTest("View excludes subtitle tagline (.inv-subtitle removed)", strpos($viewFile, 'class="inv-subtitle"') === false);
    assertTest("View mounts action button in #curtiss-floating-header-right", strpos($viewFile, 'id="curtiss-floating-header-right"') !== false);
    assertTest("View pagination options include 25, 50, 75, 100, 500", strpos($viewFile, 'value="25"') !== false && strpos($viewFile, 'value="500"') !== false);
    assertTest("View implements double-submit protection", strpos($viewFile, 'submitBtn.disabled = true') !== false);
    assertTest("View implements Enter key keyboard navigation", strpos($viewFile, "e.key === 'Enter'") !== false);
    assertTest("View hooks CurtissNav.enablePageSearch with 300ms debounce", strpos($viewFile, 'CurtissNav.enablePageSearch') !== false && strpos($viewFile, '300') !== false);

} catch (Throwable $e) {
    echo " [FATAL ERROR] " . $e->getMessage() . "\n";
    $failed++;
} finally {
    // ---------------------------------------------------------
    // CLEANUP TEST FIXTURES (ZERO TABLE TRUNCATION)
    // ---------------------------------------------------------
    if (!empty($testAccountsCreated)) {
        $db = Database::getInstance()->getConnection();
        $placeholders = implode(',', array_fill(0, count($testAccountsCreated), '?'));
        $stmt = $db->prepare("DELETE FROM chart_of_accounts WHERE id IN ($placeholders) AND is_system = 0");
        $stmt->execute($testAccountsCreated);
        echo "\nCleaned up " . count($testAccountsCreated) . " test account records.\n";
    }
}

echo "\n====================================================================\n";
echo " TEST SUMMARY: $passed PASSED | $failed FAILED\n";
echo "====================================================================\n";

exit($failed > 0 ? 1 : 0);
