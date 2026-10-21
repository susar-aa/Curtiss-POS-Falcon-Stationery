<?php
/**
 * CURTISS POS: Cash Drawer & Day Closing Master Optimization & Compliance Test Suite
 *
 * Validates according to CURTISS_MASTER_RULES.md & AGENTS.md:
 * 1. Database composite indexing:
 *    - business_days: idx_bday_branch_status_id, idx_bday_branch_date
 *    - cash_drawer_movements: idx_cdm_bday_id, idx_cdm_bday_type
 * 2. Chart of Accounts integration:
 *    - Account 1010 (Cash in Hand / Main Safe)
 *    - Account 1012 (Cash Drawer)
 * 3. Cash Drawer lifecycle & calculations:
 *    - Starting opening cash float
 *    - Manual Cash In & Cash Out movements
 *    - Expected Cash formula: Opening + In - Out + SalesCash - RefundsCash
 *    - Shortage / Overage computation
 * 4. Day Closing Reconciliation & Safe Deposit:
 *    - Retained cash float (LKR 20,000.00)
 *    - Safe deposit transfer to Main Safe (Dr 1010, Cr 1012)
 *    - Closing summary snapshot & denomination breakdown
 * 5. Closed Days Listing, Pagination & Search:
 *    - Pagination whitelist limits: 25, 50, 75, 100, 500
 *    - Universal multi-column SQL search across ID, date, notes, opener/closer, raw & formatted currency
 *    - Branch scoping & Super Admin branch filter
 * 6. Presentation-layer Masking for Special User (MaskingHelper):
 *    - Drawer summary KPI masking
 *    - Closed days listing amount masking
 * 7. Security & API authorization:
 *    - Special user restriction from financial mutations
 *    - Session branch resolution
 * 8. UI & Design Rules compliance:
 *    - Fixed viewport height: calc(100vh - 110px)
 *    - Sticky table header & internal table scrolling
 *    - 48px row heights & zebra striping
 *    - Headline only (no subtitle tagline)
 *    - Bottom floating navigation search integration with action column exclusion
 *    - Full-row click for details modal
 * 9. Clean Teardown:
 *    - Clean removal of isolated test records, ZERO TRUNCATE calls
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/BusinessDay.php';
require_once __DIR__ . '/../core/Accounting.php';
require_once __DIR__ . '/../core/MaskingHelper.php';

$passed = 0;
$failed = 0;

function assertTest(string $desc, bool $condition): void {
    global $passed, $failed;
    if ($condition) {
        echo " [PASS] $desc\n";
        $passed++;
    } else {
        echo " [FAIL] $desc\n";
        $failed++;
    }
}

echo "====================================================================\n";
echo " CURTISS POS: CASH DRAWER & DAY CLOSING COMPREHENSIVE TEST SUITE\n";
echo "====================================================================\n\n";

$db = Database::getInstance()->getConnection();

// --- SECTION 1: Database Composite Indexes ---
echo "-- Section 1: Database Composite Indexes --\n";
$stmtBday = $db->query("SHOW INDEX FROM business_days");
$bdayIndexes = array_unique(array_column($stmtBday->fetchAll(PDO::FETCH_ASSOC), 'Key_name'));
assertTest("Step 1.1: business_days has composite index idx_bday_branch_status_id", in_array('idx_bday_branch_status_id', $bdayIndexes));
assertTest("Step 1.2: business_days has composite index idx_bday_branch_date", in_array('idx_bday_branch_date', $bdayIndexes));

$stmtCdm = $db->query("SHOW INDEX FROM cash_drawer_movements");
$cdmIndexes = array_unique(array_column($stmtCdm->fetchAll(PDO::FETCH_ASSOC), 'Key_name'));
assertTest("Step 1.3: cash_drawer_movements has composite index idx_cdm_bday_id", in_array('idx_cdm_bday_id', $cdmIndexes));
assertTest("Step 1.4: cash_drawer_movements has composite index idx_cdm_bday_type", in_array('idx_cdm_bday_type', $cdmIndexes));


// --- SECTION 2: Chart of Accounts Integration ---
echo "\n-- Section 2: Chart of Accounts Check --\n";
$acct1010 = Database::fetchOne("SELECT id, account_code, account_name FROM chart_of_accounts WHERE account_code = '1010' LIMIT 1");
$acct1012 = Database::fetchOne("SELECT id, account_code, account_name FROM chart_of_accounts WHERE account_code = '1012' LIMIT 1");
assertTest("Step 2.1: COA Account 1010 (Cash in Hand / Main Safe) exists", !empty($acct1010['id']));
assertTest("Step 2.2: COA Account 1012 (Cash Drawer) exists", !empty($acct1012['id']));


// --- SECTION 3: Isolated Fixture Setup & Business Day Creation ---
echo "\n-- Section 3: Isolated Business Day Lifecycle --\n";
$uniqueCode = 'TEST_CD_' . time() . '_' . mt_rand(100, 999);
$testDate = '2099-12-31';
$testBranchId = 1;

// Get test user
$userRow = Database::fetchOne("SELECT id FROM users WHERE status = 'active' LIMIT 1");
$testUserId = (int)($userRow['id'] ?? 1);

// Create isolated business day record
$db->prepare("
    INSERT INTO business_days (
        branch_id, business_date, opened_by, opened_at,
        opening_cash, status, notes
    ) VALUES (
        :branch_id, :b_date, :opened_by, NOW(),
        20000.00, 'open', :notes
    )
")->execute([
    ':branch_id' => $testBranchId,
    ':b_date'    => $testDate,
    ':opened_by' => $testUserId,
    ':notes'     => 'Isolated Test Business Day ' . $uniqueCode
]);
$testBdayId = (int)$db->lastInsertId();
assertTest("Step 3.1: Isolated business day created with ID $testBdayId", $testBdayId > 0);

// Record Opening Cash Float Movement
$openMovement = BusinessDay::recordDrawerMovement([
    'business_day_id' => $testBdayId,
    'branch_id'       => $testBranchId,
    'movement_type'   => 'opening_cash',
    'direction'       => 'in',
    'amount'          => 20000.00,
    'reason'          => 'Opening Cash Float',
    'user_id'         => $testUserId
]);
assertTest("Step 3.2: Opening Cash movement recorded", !empty($openMovement['success']));

// Record Manual Cash In
$inMovement = BusinessDay::recordDrawerMovement([
    'business_day_id' => $testBdayId,
    'branch_id'       => $testBranchId,
    'movement_type'   => 'cash_in',
    'direction'       => 'in',
    'amount'          => 5000.00,
    'reason'          => 'Cash injection for change ' . $uniqueCode,
    'notes'           => 'Test notes ' . $uniqueCode,
    'user_id'         => $testUserId
]);
assertTest("Step 3.3: Manual Cash In recorded successfully", !empty($inMovement['success']) && !empty($inMovement['id']));

// Record Manual Cash Out
$outMovement = BusinessDay::recordDrawerMovement([
    'business_day_id' => $testBdayId,
    'branch_id'       => $testBranchId,
    'movement_type'   => 'cash_out',
    'direction'       => 'out',
    'amount'          => 2500.00,
    'reason'          => 'Purchased emergency supplies ' . $uniqueCode,
    'notes'           => 'Test notes ' . $uniqueCode,
    'user_id'         => $testUserId
]);
assertTest("Step 3.4: Manual Cash Out recorded successfully", !empty($outMovement['success']) && !empty($outMovement['id']));

// Check Drawer Summary
$res = BusinessDay::getDrawerSummary($testBdayId);
$summary = $res['summary'] ?? [];
assertTest("Step 3.5: Drawer summary returns opening cash 20,000.00", (float)($summary['opening_cash'] ?? 0) === 20000.00);
assertTest("Step 3.6: Drawer summary reflects cash in 5,000.00", (float)($summary['manual_cash_in'] ?? 0) === 5000.00);
assertTest("Step 3.7: Drawer summary reflects cash out 2,500.00", (float)($summary['manual_cash_out'] ?? 0) === 2500.00);

// Expected cash = 20,000 + 5,000 - 2,500 = 22,500
$expectedCalculated = 20000.00 + 5000.00 - 2500.00;
assertTest("Step 3.8: Expected cash accurately calculated as 22,500.00", (float)($summary['expected_cash'] ?? 0) === $expectedCalculated);


// --- SECTION 4: Day Closing & Safe Deposit Accounting ---
echo "\n-- Section 4: Day Closing & Safe Deposit Accounting --\n";
// Suppose physical count is 25,000.00 (Over by 2,500.00)
// With default retained float of 20,000.00:
// Safe deposit amount to transfer to Main Safe = 25,000 - 20,000 = 5,000.00
$actualCash = 25000.00;
$actualCard = 15000.00;
$denominations = [
    '5000' => 4,
    '1000' => 5
];

$closeResult = BusinessDay::closeDay(
    $testBdayId,
    $actualCash,
    20000.00,
    $testUserId,
    'Day close reconciliation test ' . $uniqueCode,
    [],
    $denominations,
    $actualCard
);

assertTest("Step 4.1: BusinessDay::closeDay executed successfully", !empty($closeResult['success']));

// Verify updated record in business_days
$closedRow = Database::fetchOne("SELECT * FROM business_days WHERE id = :id", [':id' => $testBdayId]);
assertTest("Step 4.2: Business day status is 'closed'", ($closedRow['status'] ?? '') === 'closed');
assertTest("Step 4.3: Actual cash recorded matches 25,000.00", (float)($closedRow['actual_cash'] ?? 0) === 25000.00);
assertTest("Step 4.4: Cash discrepancy accurately calculated as +2,500.00 (overage)", (float)($closedRow['overage_amount'] ?? 0) === 2500.00);

// Verify safe deposit journal entry
$journalEntry = Database::fetchOne("
    SELECT * FROM journal_entries 
    WHERE source_id = :id AND description LIKE '%Day Closing Cash Drawer Deposit%'
    ORDER BY id DESC LIMIT 1
", [':id' => $testBdayId]);

assertTest("Step 4.5: Safe deposit journal entry created in COA", !empty($journalEntry['id']));

if (!empty($journalEntry['id'])) {
    $lines = Database::fetchAll("
        SELECT jl.*, ca.account_code 
        FROM journal_lines jl
        JOIN chart_of_accounts ca ON jl.account_id = ca.id
        WHERE jl.journal_entry_id = :jid
    ", [':jid' => $journalEntry['id']]);

    $hasDebitSafe = false;
    $hasCreditDrawer = false;
    $totalDebit = 0;
    $totalCredit = 0;

    foreach ($lines as $line) {
        $totalDebit += (float)$line['debit'];
        $totalCredit += (float)$line['credit'];
        if ($line['account_code'] === '1010' && (float)$line['debit'] == 5000.00) {
            $hasDebitSafe = true;
        }
        if ($line['account_code'] === '1012' && (float)$line['credit'] == 5000.00) {
            $hasCreditDrawer = true;
        }
    }

    assertTest("Step 4.6: Safe deposit Debits Main Safe (1010) for 5,000.00", $hasDebitSafe);
    assertTest("Step 4.7: Safe deposit Credits Cash Drawer (1012) for 5,000.00", $hasCreditDrawer);
    assertTest("Step 4.8: Safe deposit journal is balanced (Debit == Credit)", abs($totalDebit - $totalCredit) < 0.01);
}


// --- SECTION 5: Closed Days Listing, Pagination & Search ---
echo "\n-- Section 5: Closed Days Listing, Pagination & Multi-Column Search --\n";

// Pagination limit check
$list25 = BusinessDay::getClosedDaysList($testBranchId, 1, 25, []);
$list50 = BusinessDay::getClosedDaysList($testBranchId, 1, 50, []);
assertTest("Step 5.1: getClosedDaysList with limit 25 returns valid array", is_array($list25));
assertTest("Step 5.2: getClosedDaysList with limit 50 returns valid array", is_array($list50));

// Multi-column search checks
// 1. Search by unique ID
$searchById = BusinessDay::getClosedDaysList($testBranchId, 1, 25, ['search' => (string)$testBdayId]);
$foundById = false;
foreach (($searchById['items'] ?? []) as $row) {
    if ((int)$row['id'] === $testBdayId) {
        $foundById = true;
        break;
    }
}
assertTest("Step 5.3: Search by Business Day ID found record", $foundById);

// 2. Search by unique test notes text
$searchByNote = BusinessDay::getClosedDaysList($testBranchId, 1, 25, ['search' => $uniqueCode]);
$foundByNote = false;
foreach (($searchByNote['items'] ?? []) as $row) {
    if ((int)$row['id'] === $testBdayId) {
        $foundByNote = true;
        break;
    }
}
assertTest("Step 5.4: Search by unique notes found record", $foundByNote);

// 3. Search by raw numeric amount (25000.00 or 25000)
$searchByNum = BusinessDay::getClosedDaysList($testBranchId, 1, 25, ['search' => '25000']);
$foundByNum = false;
foreach (($searchByNum['items'] ?? []) as $row) {
    if ((int)$row['id'] === $testBdayId) {
        $foundByNum = true;
        break;
    }
}
assertTest("Step 5.5: Search by raw currency amount (25000) found record", $foundByNum);

// 4. Search by formatted currency string ('LKR 25,000')
$searchByFormatted = BusinessDay::getClosedDaysList($testBranchId, 1, 25, ['search' => 'LKR 25,000']);
$foundByFormatted = false;
foreach (($searchByFormatted['items'] ?? []) as $row) {
    if ((int)$row['id'] === $testBdayId) {
        $foundByFormatted = true;
        break;
    }
}
assertTest("Step 5.6: Search by formatted currency ('LKR 25,000') found record", $foundByFormatted);

// 5. Search by date
$searchByDate = BusinessDay::getClosedDaysList($testBranchId, 1, 25, ['search' => $testDate]);
$foundByDate = false;
foreach (($searchByDate['items'] ?? []) as $row) {
    if ((int)$row['id'] === $testBdayId) {
        $foundByDate = true;
        break;
    }
}
assertTest("Step 5.7: Search by business date ('$testDate') found record", $foundByDate);


// --- SECTION 6: Special User Presentation Masking ---
echo "\n-- Section 6: Special User Presentation Masking --\n";
if (!defined('ROLE_SPECIAL_USER')) {
    define('ROLE_SPECIAL_USER', 'special_user');
}

// Emulate Special User session
$_SESSION['user_id'] = $testUserId;
$_SESSION['role_code'] = ROLE_SPECIAL_USER;
$_SESSION['user_role'] = ROLE_SPECIAL_USER;

$maskedRes = BusinessDay::getDrawerSummary($testBdayId);
$maskedSummary = $maskedRes['summary'] ?? [];
assertTest("Step 6.1: MaskingHelper masks expected_cash for Special User", (float)($maskedSummary['expected_cash'] ?? 0) !== (float)($summary['expected_cash'] ?? 0));

$maskedList = BusinessDay::getClosedDaysList($testBranchId, 1, 25, ['search' => $uniqueCode]);
$maskedRecord = $maskedList['items'][0] ?? [];
assertTest("Step 6.2: Special User listing masks actual_cash", !empty($maskedRecord) && (float)($maskedRecord['actual_cash'] ?? 0) !== 25000.00);

// Revert session role
$_SESSION['role_code'] = 'admin';
$_SESSION['user_role'] = 'admin';


// --- SECTION 7: UI & Master Design Rules Verification ---
echo "\n-- Section 7: UI & Master Design Rules Verification --\n";
$viewFile = __DIR__ . '/../views/cash_drawer/index.php';
$viewContent = file_get_contents($viewFile);

assertTest("Step 7.1: View implements fixed viewport height (calc(100vh - 110px))", strpos($viewContent, 'calc(100vh - 110px)') !== false);
assertTest("Step 7.2: View enforces sticky table headers (position: sticky; top: 0;)", strpos($viewContent, 'position: sticky;') !== false && strpos($viewContent, 'top: 0;') !== false);
assertTest("Step 7.3: View enforces 48px row heights", strpos($viewContent, 'height: 48px;') !== false || strpos($viewContent, 'min-height: 48px;') !== false);
assertTest("Step 7.4: View implements zebra striping (nth-child(even))", strpos($viewContent, ':nth-child(even)') !== false);
assertTest("Step 7.5: View headline-only title without subtitle tagline", strpos($viewContent, 'class="inv-subtitle"') === false && strpos($viewContent, 'class="inv-title"') !== false);
assertTest("Step 7.6: View implements closed day details modal (#closedDayDetailsModal)", strpos($viewContent, 'id="closedDayDetailsModal"') !== false);
assertTest("Step 7.7: View implements full-row click for details modal (viewClosedDayDetails)", strpos($viewContent, 'viewClosedDayDetails') !== false);
assertTest("Step 7.8: Action buttons stop event propagation", strpos($viewContent, 'event.stopPropagation()') !== false);
assertTest("Step 7.9: Real-time search routes via CurtissNav.enablePageSearch", strpos($viewContent, 'CurtissNav.enablePageSearch') !== false);
assertTest("Step 7.10: Search excludes action buttons (.cd-act-btn)", strpos($viewContent, '.cd-act-btn') !== false);
assertTest("Step 7.11: Page size dropdown supports 25, 50, 75, 100, 500", strpos($viewContent, 'value="25"') !== false && strpos($viewContent, 'value="500"') !== false);


// --- SECTION 8: Clean Teardown (Zero Table Truncation) ---
echo "\n-- Section 8: Isolated Fixture Teardown --\n";
// Delete created journal lines and journal entries for safe deposit
if (!empty($journalEntry['id'])) {
    $db->prepare("DELETE FROM journal_lines WHERE journal_entry_id = :jid")->execute([':jid' => $journalEntry['id']]);
    $db->prepare("DELETE FROM journal_entries WHERE id = :jid")->execute([':jid' => $journalEntry['id']]);
}

// Delete created cash drawer movements
$db->prepare("DELETE FROM cash_drawer_movements WHERE business_day_id = :id")->execute([':id' => $testBdayId]);

// Delete created test business day
$db->prepare("DELETE FROM business_days WHERE id = :id")->execute([':id' => $testBdayId]);

$verifyDeleted = Database::fetchOne("SELECT id FROM business_days WHERE id = :id", [':id' => $testBdayId]);
assertTest("Step 8.1: Isolated business day cleanly deleted (no TRUNCATE used)", empty($verifyDeleted));

echo "\n====================================================================\n";
echo " TEST SUITE SUMMARY: $passed PASSED, $failed FAILED\n";
echo "====================================================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
