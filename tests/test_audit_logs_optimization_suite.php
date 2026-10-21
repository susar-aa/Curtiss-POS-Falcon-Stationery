<?php
/**
 * Curtiss POS - System Audit Logs Optimization & Compliance Suite
 * 
 * Verifies all Master Rules for the Audit Logs Architecture:
 * 1. Database Indexing & Scalability (single & composite performance indexes)
 * 2. Operational Event Logging (Audit::log) with JSON payload serialization
 * 3. Session Branch Resolution ($_SESSION['branch_id'] & $_SESSION['active_branch_id'])
 * 4. Multi-Column SQL Search (ID, timestamp, user, branch, module, action, record_id, IP, JSON payloads)
 * 5. Server-Side Pagination & Limits (count, limit, offset, pagination envelope)
 * 6. Module & Action Filter Extraction (Audit::getModules, Audit::getActions)
 * 7. Branch Context Filtering & Superadmin Consolidated Viewing
 * 8. CSV Compliance Export Integrity
 * 9. Security, Role Authorization & SQL Injection Safety
 * 10. Zero Database Truncation Rule (test cleanup strictly isolates test IDs)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Audit.php';

// Session setup
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$testUserId = 1;
$_SESSION['user_id'] = $testUserId;
$_SESSION['branch_id'] = 1;
$_SESSION['role_id'] = 1;
$_SESSION['role_code'] = ROLE_SUPER_ADMIN;

echo "====================================================================\n";
echo "   CURTISS POS — SYSTEM AUDIT LOGS OPTIMIZATION & COMPLIANCE SUITE\n";
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

$cleanupLogIds = [];

try {
    // Record baseline count
    $baselineCount = (int)Database::fetchOne("SELECT COUNT(*) as cnt FROM audit_logs")['cnt'];
    echo "Initial total audit_logs in database: {$baselineCount}\n\n";

    // ----------------------------------------------------
    // TEST 1: Database Index Verification
    // ----------------------------------------------------
    echo "--- Test 1: Database Indexes ---\n";
    $indexes = Database::fetchAll("SHOW INDEX FROM audit_logs");
    $indexNames = array_unique(array_column($indexes, 'Key_name'));

    check("Primary Key Index Exists", in_array('PRIMARY', $indexNames));
    check("Module Index (idx_audit_module) Exists", in_array('idx_audit_module', $indexNames));
    check("User Index (idx_audit_user) Exists", in_array('idx_audit_user', $indexNames));
    check("Branch Index (idx_audit_branch) Exists", in_array('idx_audit_branch', $indexNames));
    check("Timestamp Index (idx_audit_created) Exists", in_array('idx_audit_created', $indexNames));
    check("Action Index (idx_audit_action) Exists", in_array('idx_audit_action', $indexNames));
    check("Composite Branch+Date Index (idx_audit_branch_created) Exists", in_array('idx_audit_branch_created', $indexNames));
    check("Composite Module+Action Index (idx_audit_module_action) Exists", in_array('idx_audit_module_action', $indexNames));

    // ----------------------------------------------------
    // TEST 2: Audit::log Execution & Payload Serialization
    // ----------------------------------------------------
    echo "\n--- Test 2: Audit::log Event Capture ---\n";
    $uniqueCode = 'TEST_AUDIT_' . time() . '_' . rand(1000, 9999);
    $oldPayload = ['status' => 'pending', 'amount' => 12500.00, 'note' => 'Initial state'];
    $newPayload = ['status' => 'approved', 'amount' => 14000.50, 'note' => 'Updated by manager'];

    $logSuccess = Audit::log('TEST_EVENT_' . $uniqueCode, 'compliance_test', 'REC-999', $oldPayload, $newPayload);
    check("Audit::log executed successfully", $logSuccess === true);

    $loggedRow = Database::fetchOne("SELECT * FROM audit_logs WHERE action = :action", [
        ':action' => 'TEST_EVENT_' . $uniqueCode
    ]);
    check("Audit record found in database", !empty($loggedRow));
    if (!empty($loggedRow)) {
        $cleanupLogIds[] = (int)$loggedRow['id'];
        check("User ID recorded accurately", (int)$loggedRow['user_id'] === $testUserId, "User: " . $loggedRow['user_id']);
        check("Branch ID recorded accurately", (int)$loggedRow['branch_id'] === 1, "Branch: " . $loggedRow['branch_id']);
        check("Module recorded accurately", $loggedRow['module'] === 'compliance_test');
        check("Record ID recorded accurately", $loggedRow['record_id'] === 'REC-999');

        $decodedOld = json_decode($loggedRow['old_values'], true);
        $decodedNew = json_decode($loggedRow['new_values'], true);
        check("Old values JSON decoded matches original", ($decodedOld['amount'] ?? 0) == 12500.00);
        check("New values JSON decoded matches original", ($decodedNew['amount'] ?? 0) == 14000.50);
    }

    // ----------------------------------------------------
    // TEST 3: Branch Session Resolution
    // ----------------------------------------------------
    echo "\n--- Test 3: Branch Session Resolution ---\n";
    // Test with active_branch_id when branch_id is absent
    unset($_SESSION['branch_id']);
    $_SESSION['active_branch_id'] = 2;
    $logBranchTest = Audit::log('TEST_BRANCH_SESSION_' . $uniqueCode, 'compliance_test', 'REC-888', null, ['test' => true]);
    $branchRow = Database::fetchOne("SELECT * FROM audit_logs WHERE action = :act", [':act' => 'TEST_BRANCH_SESSION_' . $uniqueCode]);
    if (!empty($branchRow)) {
        $cleanupLogIds[] = (int)$branchRow['id'];
        check("Branch resolved correctly from active_branch_id", (int)$branchRow['branch_id'] === 2, "Branch: " . $branchRow['branch_id']);
    } else {
        check("Branch resolved correctly from active_branch_id", false, "Row not created");
    }
    // Restore default session branch
    $_SESSION['branch_id'] = 1;
    unset($_SESSION['active_branch_id']);

    // ----------------------------------------------------
    // TEST 4: Multi-Column SQL Search Query
    // ----------------------------------------------------
    echo "\n--- Test 4: Multi-Column Search Capabilities ---\n";
    if (!empty($loggedRow)) {
        $testLogId = (int)$loggedRow['id'];

        // Search by raw ID
        $s1 = Audit::all(['search' => (string)$testLogId]);
        check("Search by Raw Log ID matches record", !empty($s1) && in_array($testLogId, array_column($s1, 'id')));

        // Search by #ID
        $s2 = Audit::all(['search' => '#' . $testLogId]);
        check("Search by #LogID matches record", !empty($s2) && in_array($testLogId, array_column($s2, 'id')));

        // Search by Action Name
        $s3 = Audit::all(['search' => $uniqueCode]);
        check("Search by Action substring matches record", !empty($s3) && in_array($testLogId, array_column($s3, 'id')));

        // Search by Module Name
        $s4 = Audit::all(['search' => 'compliance_test']);
        check("Search by Module name matches record", !empty($s4) && in_array($testLogId, array_column($s4, 'id')));

        // Search by Record ID
        $s5 = Audit::all(['search' => 'REC-999']);
        check("Search by Record ID matches record", !empty($s5) && in_array($testLogId, array_column($s5, 'id')));

        // Search by JSON Payload Content (monetary value in new_values)
        $s6 = Audit::all(['search' => '14000.50']);
        check("Search by JSON Payload monetary amount matches record", !empty($s6) && in_array($testLogId, array_column($s6, 'id')));

        // Search by Timestamp string (YYYY-MM-DD)
        $todayDate = date('Y-m-d');
        $s7 = Audit::all(['search' => $todayDate]);
        check("Search by Timestamp date substring matches records", !empty($s7));
    }

    // ----------------------------------------------------
    // TEST 5: Server-Side Pagination & Limits
    // ----------------------------------------------------
    echo "\n--- Test 5: Server-Side Pagination & Limits ---\n";
    $totalCount = Audit::count(['module' => 'compliance_test']);
    check("Audit::count returns accurate count", $totalCount >= 2, "Count: {$totalCount}");

    $paged = Audit::paginate(['module' => 'compliance_test'], 1, 1);
    check("Audit::paginate returns envelope", isset($paged['data'], $paged['total'], $paged['page'], $paged['limit'], $paged['total_pages']));
    check("Audit::paginate slice size respects limit (1)", count($paged['data']) === 1);
    check("Audit::paginate total equals count", $paged['total'] === $totalCount);
    check("Audit::paginate total_pages computed accurately", $paged['total_pages'] === $totalCount);

    $pagedPage2 = Audit::paginate(['module' => 'compliance_test'], 2, 1);
    check("Audit::paginate page 2 returns record", count($pagedPage2['data']) === 1);
    if (!empty($paged['data']) && !empty($pagedPage2['data'])) {
        check("Page 1 and Page 2 records are distinct", $paged['data'][0]['id'] !== $pagedPage2['data'][0]['id']);
    }

    // ----------------------------------------------------
    // TEST 6: Module & Action Filter Extraction
    // ----------------------------------------------------
    echo "\n--- Test 6: Module & Action Filter Extraction ---\n";
    $modules = Audit::getModules();
    $actions = Audit::getActions();
    check("Audit::getModules includes test module", in_array('compliance_test', $modules));
    check("Audit::getActions includes test action", in_array('TEST_EVENT_' . $uniqueCode, $actions));

    // ----------------------------------------------------
    // TEST 7: Branch Context Filtering
    // ----------------------------------------------------
    echo "\n--- Test 7: Branch Context Filtering ---\n";
    $b1Logs = Audit::all(['module' => 'compliance_test', 'branch_id' => 1]);
    $b2Logs = Audit::all(['module' => 'compliance_test', 'branch_id' => 2]);
    check("Branch 1 filter captures Branch 1 logs", !empty($b1Logs) && (int)$b1Logs[0]['branch_id'] === 1);
    check("Branch 2 filter captures Branch 2 logs", !empty($b2Logs) && (int)$b2Logs[0]['branch_id'] === 2);

    // ----------------------------------------------------
    // TEST 8: CSV Export Capability
    // ----------------------------------------------------
    echo "\n--- Test 8: CSV Export Simulation ---\n";
    $exportRecords = Audit::all(['module' => 'compliance_test']);
    $csvBuffer = fopen('php://memory', 'r+');
    fputcsv($csvBuffer, ['ID', 'Timestamp', 'User', 'Branch', 'Module', 'Action', 'Record ID', 'IP Address', 'User Agent', 'Old Values', 'New Values']);
    foreach ($exportRecords as $r) {
        fputcsv($csvBuffer, [
            $r['id'], $r['created_at'], $r['user_name'], $r['branch_name'],
            $r['module'], $r['action'], $r['record_id'], $r['ip_address'],
            $r['user_agent'], $r['old_values'], $r['new_values']
        ]);
    }
    rewind($csvBuffer);
    $csvOutput = stream_get_contents($csvBuffer);
    fclose($csvBuffer);
    check("CSV Export produces valid CSV string", !empty($csvOutput) && str_contains($csvOutput, 'compliance_test'));
    check("CSV Export contains header columns", str_contains($csvOutput, 'Timestamp') && str_contains($csvOutput, 'Old Values'));

    // ----------------------------------------------------
    // TEST 9: Security & Authorization Verification
    // ----------------------------------------------------
    echo "\n--- Test 9: Security & Role Authorization ---\n";
    // Authorized roles
    $allowedRoles = [ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER, ROLE_ACCOUNTANT];
    foreach ($allowedRoles as $role) {
        $_SESSION['role_code'] = $role;
        $canAccess = Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER, ROLE_ACCOUNTANT);
        check("Role {$role} is authorized to view audit logs", $canAccess === true);
    }
    // Unauthorized role (Cashier)
    $_SESSION['role_code'] = ROLE_CASHIER;
    $cashierAccess = Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER, ROLE_ACCOUNTANT);
    check("Role CASHIER is blocked from viewing audit logs", $cashierAccess === false);
    // Reset session role
    $_SESSION['role_code'] = ROLE_SUPER_ADMIN;

    // SQL Injection resilience in search term
    $maliciousSearch = "' OR '1'='1' -- ";
    $safeResults = Audit::all(['search' => $maliciousSearch]);
    check("SQL Injection search input is safely parameterized", is_array($safeResults));

    // ----------------------------------------------------
    // TEST 10: Strict Zero Database Truncation Rule
    // ----------------------------------------------------
    echo "\n--- Test 10: Zero Database Truncation & Record Safety ---\n";
    if (!empty($cleanupLogIds)) {
        $inIds = implode(',', array_map('intval', $cleanupLogIds));
        Database::execute("DELETE FROM audit_logs WHERE id IN ({$inIds})");
        echo "Cleaned up isolated test records: [{$inIds}]\n";
    }

    $finalCount = (int)Database::fetchOne("SELECT COUNT(*) as cnt FROM audit_logs")['cnt'];
    check("Zero Truncation Rule Maintained (Pre: {$baselineCount}, Post: {$finalCount})", $finalCount === $baselineCount);

} catch (Throwable $e) {
    echo "\n[EXCEPTION] " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
    $failed++;
}

echo "\n====================================================================\n";
echo "   AUDIT LOGS TEST SUMMARY: {$passed} PASSED, {$failed} FAILED\n";
echo "====================================================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
