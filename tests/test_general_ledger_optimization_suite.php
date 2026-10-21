<?php
/**
 * Test Suite: General Ledger Optimization & Compliance Suite
 * Tests all functionality, database indexes, multi-column search, pagination,
 * double-entry accounting integrity, security permissions, and UI design rules for the General Ledger module.
 * ZERO DATABASE TRUNCATIONS - Isolated fixtures only.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Accounting.php';

$passed = 0;
$failed = 0;
$testJournalsCreated = [];

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
echo "   CURTISS POS - GENERAL LEDGER OPTIMIZATION TEST SUITE\n";
echo "====================================================================\n\n";

try {
    $db = Database::getInstance()->getConnection();

    // ---------------------------------------------------------
    // 1. DATABASE SCHEMA & PERFORMANCE INDEXES
    // ---------------------------------------------------------
    echo "--- 1. Database Schema & Index Verification ---\n";
    $jeStmt = $db->query("SHOW INDEX FROM journal_entries");
    $jeIndexes = [];
    while ($r = $jeStmt->fetch(PDO::FETCH_ASSOC)) {
        $jeIndexes[$r['Key_name']] = true;
    }

    assertTest("Index 'idx_je_date_id' exists on journal_entries", isset($jeIndexes['idx_je_date_id']));
    assertTest("Index 'idx_je_stat_date_id' exists on journal_entries", isset($jeIndexes['idx_je_stat_date_id']));
    assertTest("Index 'idx_je_status_date' exists on journal_entries", isset($jeIndexes['idx_je_status_date']));
    assertTest("Index 'idx_je_branch_status_date' exists on journal_entries", isset($jeIndexes['idx_je_branch_status_date']));

    $jlStmt = $db->query("SHOW INDEX FROM journal_lines");
    $jlIndexes = [];
    while ($r = $jlStmt->fetch(PDO::FETCH_ASSOC)) {
        $jlIndexes[$r['Key_name']] = true;
    }

    assertTest("Index 'idx_jl_acc_debit_credit' exists on journal_lines", isset($jlIndexes['idx_jl_acc_debit_credit']));
    assertTest("Index 'idx_jl_acc_je' exists on journal_lines", isset($jlIndexes['idx_jl_acc_je']));
    assertTest("Index 'idx_jl_je_acc' exists on journal_lines", isset($jlIndexes['idx_jl_je_acc']));

    // ---------------------------------------------------------
    // 2. ISOLATED TEST FIXTURE CREATION (DOUBLE-ENTRY JOURNAL)
    // ---------------------------------------------------------
    echo "\n--- 2. Isolated Test Fixture Creation ---\n";
    $cashAcc = Database::fetchOne("SELECT id FROM chart_of_accounts WHERE account_code = '1012'");
    $cogsAcc = Database::fetchOne("SELECT id FROM chart_of_accounts WHERE account_code = '5010'");

    $uniqueMemo = 'GL-TEST-MEMO-' . uniqid();
    $uniqueDesc = 'General Ledger Automated Test ' . uniqid();
    $testAmount = 7450.50;

    $postRes = Accounting::postJournalEntry([
        'branch_id'   => 1,
        'entry_date'  => date('Y-m-d'),
        'source_type' => 'manual',
        'description' => $uniqueDesc,
        'created_by'  => 1
    ], [
        [
            'account_id' => (int)$cogsAcc['id'],
            'debit'      => $testAmount,
            'credit'     => 0,
            'memo'       => $uniqueMemo . ' Dr'
        ],
        [
            'account_id' => (int)$cashAcc['id'],
            'debit'      => 0,
            'credit'     => $testAmount,
            'memo'       => $uniqueMemo . ' Cr'
        ]
    ]);

    assertTest("Isolated test journal posted successfully", $postRes['success']);
    if ($postRes['success'] && !empty($postRes['journal_entry_id'])) {
        $testJournalsCreated[] = (int)$postRes['journal_entry_id'];
    }

    // ---------------------------------------------------------
    // 3. GENERAL LEDGER BASIC RETRIEVAL & STRUCTURE
    // ---------------------------------------------------------
    echo "\n--- 3. General Ledger Basic Retrieval ---\n";
    $glAll = Accounting::getGeneralLedger(['branch_id' => 'all'], 1, 25);
    assertTest("getGeneralLedger returns array structure", is_array($glAll));
    assertTest("getGeneralLedger returns 'items' array", isset($glAll['items']) && is_array($glAll['items']));
    assertTest("getGeneralLedger returns 'totalItems' >= 1", isset($glAll['totalItems']) && $glAll['totalItems'] >= 1);
    assertTest("getGeneralLedger returns 'page' = 1", ($glAll['page'] ?? 0) === 1);
    assertTest("getGeneralLedger returns 'limit' = 25", ($glAll['limit'] ?? 0) === 25);
    assertTest("getGeneralLedger returns 'totalPages' >= 1", ($glAll['totalPages'] ?? 0) >= 1);

    if (!empty($glAll['items'])) {
        $first = $glAll['items'][0];
        assertTest("Item has line_id", isset($first['line_id']));
        assertTest("Item has debit", isset($first['debit']));
        assertTest("Item has credit", isset($first['credit']));
        assertTest("Item has journal_id", isset($first['journal_id']));
        assertTest("Item has entry_number", isset($first['entry_number']));
        assertTest("Item has entry_date", isset($first['entry_date']));
        assertTest("Item has source_type", isset($first['source_type']));
        assertTest("Item has description", isset($first['description']));
        assertTest("Item has account_code", isset($first['account_code']));
        assertTest("Item has account_name", isset($first['account_name']));
        assertTest("Item has branch_name", isset($first['branch_name']));
    }

    // ---------------------------------------------------------
    // 4. FILTERING INTEGRITY
    // ---------------------------------------------------------
    echo "\n--- 4. Filtering Integrity Verification ---\n";
    // 4a. Filter by Account
    $glAcc = Accounting::getGeneralLedger(['account_id' => (int)$cashAcc['id'], 'branch_id' => 'all'], 1, 25);
    assertTest("Filter by account_id returns records", $glAcc['totalItems'] > 0);
    $allMatchAcc = true;
    foreach ($glAcc['items'] as $it) {
        if ($it['account_code'] !== '1012') {
            $allMatchAcc = false;
            break;
        }
    }
    assertTest("All items in account-filtered list match account '1012'", $allMatchAcc);

    // 4b. Filter by Branch
    $glBranch1 = Accounting::getGeneralLedger(['branch_id' => 1], 1, 25);
    assertTest("Filter by branch_id = 1 returns records", $glBranch1['totalItems'] > 0);

    // 4c. Filter by Source Type
    $glSource = Accounting::getGeneralLedger(['source_type' => 'manual', 'branch_id' => 'all'], 1, 25);
    assertTest("Filter by source_type = 'manual' returns records", $glSource['totalItems'] > 0);
    $allMatchSrc = true;
    foreach ($glSource['items'] as $it) {
        if ($it['source_type'] !== 'manual') {
            $allMatchSrc = false;
            break;
        }
    }
    assertTest("All items in source-filtered list match 'manual'", $allMatchSrc);

    // 4d. Filter by Date Range
    $today = date('Y-m-d');
    $glDate = Accounting::getGeneralLedger(['start_date' => $today, 'end_date' => $today, 'branch_id' => 'all'], 1, 25);
    assertTest("Filter by today's date range returns records", $glDate['totalItems'] > 0);

    // ---------------------------------------------------------
    // 5. SERVER-SIDE PAGINATION & WHITELISTED LIMITS
    // ---------------------------------------------------------
    echo "\n--- 5. Server-Side Pagination & Limits ---\n";
    $limits = [25, 50, 75, 100, 500];
    foreach ($limits as $l) {
        $pRes = Accounting::getGeneralLedger(['branch_id' => 'all'], 1, $l);
        assertTest("Pagination limit {$l} is respected", $pRes['limit'] === $l);
    }
    // Test invalid limit falls back to default 25
    $fallbackRes = Accounting::getGeneralLedger(['branch_id' => 'all'], 1, 999);
    assertTest("Non-whitelisted limit (999) falls back to default 25", $fallbackRes['limit'] === 25);

    // ---------------------------------------------------------
    // 6. UNIVERSAL MULTI-COLUMN FLOATING SEARCH
    // ---------------------------------------------------------
    echo "\n--- 6. Universal Multi-Column Search Verification ---\n";
    // 6a. Search by unique memo
    $searchMemo = Accounting::getGeneralLedger(['search' => $uniqueMemo, 'branch_id' => 'all']);
    assertTest("Search by line memo matches test fixture lines", $searchMemo['totalItems'] >= 2);

    // 6b. Search by unique description
    $searchDesc = Accounting::getGeneralLedger(['search' => $uniqueDesc, 'branch_id' => 'all']);
    assertTest("Search by description matches test fixture", $searchDesc['totalItems'] >= 2);

    // 6c. Search by raw numeric amount
    $searchRawAmt = Accounting::getGeneralLedger(['search' => '7450', 'branch_id' => 'all']);
    assertTest("Search by raw integer '7450' matches test fixture", $searchRawAmt['totalItems'] >= 2);

    // 6d. Search by formatted currency amount
    $searchFormattedAmt = Accounting::getGeneralLedger(['search' => '7,450.50', 'branch_id' => 'all']);
    assertTest("Search by formatted amount '7,450.50' matches test fixture", $searchFormattedAmt['totalItems'] >= 2);

    // 6e. Search by currency prefix string
    $searchCurr = Accounting::getGeneralLedger(['search' => 'LKR 7,450.50', 'branch_id' => 'all']);
    assertTest("Search by currency prefix 'LKR 7,450.50' matches test fixture", $searchCurr['totalItems'] >= 2);

    // 6f. Search by Account Code
    $searchCode = Accounting::getGeneralLedger(['search' => '1012', 'branch_id' => 'all']);
    assertTest("Search by account code '1012' returns matching lines", $searchCode['totalItems'] > 0);

    // 6g. Search by Account Name
    $searchName = Accounting::getGeneralLedger(['search' => 'Cash Drawer', 'branch_id' => 'all']);
    assertTest("Search by account name 'Cash Drawer' returns matching lines", $searchName['totalItems'] > 0);

    // 6h. Search by Source Type
    $searchSrc = Accounting::getGeneralLedger(['search' => 'manual', 'branch_id' => 'all']);
    assertTest("Search by source_type 'manual' returns matching lines", $searchSrc['totalItems'] > 0);

    // ---------------------------------------------------------
    // 7. JOURNAL ENTRY DETAILS & BALANCED DOUBLE-ENTRY
    // ---------------------------------------------------------
    echo "\n--- 7. Journal Entry Details & Double-Entry Integrity ---\n";
    if (!empty($testJournalsCreated)) {
        $jId = $testJournalsCreated[0];
        $details = Accounting::getJournalEntryDetails($jId);
        assertTest("getJournalEntryDetails returns details array", !empty($details));
        assertTest("Journal details has lines array with 2 lines", isset($details['lines']) && count($details['lines']) === 2);
        assertTest("Journal total_debit equals total_credit (balanced)", (float)$details['total_debit'] === (float)$details['total_credit']);
        assertTest("Journal debit amount matches test amount", (float)$details['total_debit'] === $testAmount);
    }

    // ---------------------------------------------------------
    // 8. SECURITY & PERMISSIONS VERIFICATION
    // ---------------------------------------------------------
    echo "\n--- 8. Security & Permissions Verification ---\n";
    $_SESSION['user_id'] = 1;
    $_SESSION['role_code'] = ROLE_SPECIAL_USER;
    assertTest("Auth::isSpecialUser() recognizes special user role", Auth::isSpecialUser());
    unset($_SESSION['role_code'], $_SESSION['user_id']);

    $token = Auth::generateCsrfToken();
    assertTest("CSRF token generated successfully", !empty($token));
    assertTest("Valid CSRF token validates true", Auth::validateCsrfToken($token));

    // ---------------------------------------------------------
    // 9. UI VIEW & DESIGN COMPLIANCE AUDIT
    // ---------------------------------------------------------
    echo "\n--- 9. UI View & Design Compliance Audit ---\n";
    $viewFile = file_get_contents(__DIR__ . '/../views/general_ledger/index.php');

    assertTest("View includes CSRF meta tag", strpos($viewFile, 'meta name="csrf-token"') !== false);
    assertTest("View includes curtiss_select.css and curtiss_select.js", strpos($viewFile, 'curtiss_select.css') !== false && strpos($viewFile, 'curtiss_select.js') !== false);
    assertTest("View enforces fixed viewport height 'calc(100vh - 110px)'", strpos($viewFile, 'height: calc(100vh - 110px)') !== false);
    assertTest("View data table headers are sticky at top", strpos($viewFile, 'position: sticky') !== false && strpos($viewFile, 'top: 0') !== false);
    assertTest("View enforces standard 48px row heights on tbody tr", strpos($viewFile, 'height: 48px;') !== false);
    assertTest("View enforces zebra striping with 0.57 opacity", strpos($viewFile, '248, 250, 252, 0.57') !== false);
    assertTest("View enforces 28px standard pill badges (.prod-badge)", strpos($viewFile, 'height: 28px;') !== false && strpos($viewFile, 'min-height: 28px;') !== false);
    assertTest("View excludes subtitle tagline (.inv-subtitle removed)", strpos($viewFile, 'class="inv-subtitle"') === false);
    assertTest("View mounts action button in #curtiss-floating-header-right", strpos($viewFile, 'id="curtiss-floating-header-right"') !== false);
    assertTest("View pagination options include 25, 50, 75, 100, 500", strpos($viewFile, 'value="25"') !== false && strpos($viewFile, 'value="500"') !== false);
    assertTest("View implements full-row click for journal details modal", strpos($viewFile, 'viewJournalDetails(') !== false);
    assertTest("View attaches CurtissSearchableSelect to filter dropdowns", strpos($viewFile, 'CurtissSearchableSelect.attach') !== false);
    assertTest("View hooks CurtissNav.enablePageSearch with 300ms debounce", strpos($viewFile, 'CurtissNav.enablePageSearch') !== false && strpos($viewFile, '300') !== false);

} catch (Throwable $e) {
    echo " [FATAL ERROR] " . $e->getMessage() . "\n";
    $failed++;
} finally {
    // ---------------------------------------------------------
    // CLEANUP TEST FIXTURES (ZERO TABLE TRUNCATION)
    // ---------------------------------------------------------
    if (!empty($testJournalsCreated)) {
        $db = Database::getInstance()->getConnection();
        $placeholders = implode(',', array_fill(0, count($testJournalsCreated), '?'));
        $db->prepare("DELETE FROM journal_lines WHERE journal_entry_id IN ($placeholders)")->execute($testJournalsCreated);
        $db->prepare("DELETE FROM journal_entries WHERE id IN ($placeholders)")->execute($testJournalsCreated);
        echo "\nCleaned up " . count($testJournalsCreated) . " test journal entry fixtures.\n";
    }
}

echo "\n====================================================================\n";
echo " TEST SUMMARY: $passed PASSED | $failed FAILED\n";
echo "====================================================================\n";

exit($failed > 0 ? 1 : 0);
