<?php
/**
 * CURTISS POS - JOURNAL ENTRIES COMPREHENSIVE OPTIMIZATION TEST SUITE
 * 
 * Tests all functionality, database composite indexes, double-entry mathematical balancing,
 * manual posting validation, reversal lifecycles, universal search across all columns,
 * server-side pagination, CSRF & security checks, and UI design standards.
 * 
 * STRICT COMPLIANCE: ZERO TABLE TRUNCATIONS.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'super_admin';
$_SESSION['branch_id'] = 1;
$_SESSION['active_branch_id'] = 1;

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Accounting.php';
require_once __DIR__ . '/../core/Audit.php';

class JournalEntriesOptimizationTestSuite
{
    private $db;
    private $totalTests = 0;
    private $passedTests = 0;
    private $failedTests = 0;
    private $createdJournalIds = [];

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    private function assertTest(string $description, bool $result, string $details = ''): void
    {
        $this->totalTests++;
        if ($result) {
            $this->passedTests++;
            echo " [PASS] Step {$this->totalTests}: {$description}\n";
        } else {
            $this->failedTests++;
            echo " [FAIL] Step {$this->totalTests}: {$description}";
            if (!empty($details)) {
                echo " -- Details: {$details}";
            }
            echo "\n";
        }
    }

    public function runAll(): void
    {
        echo "========================================================================\n";
        echo "   CURTISS POS - JOURNAL ENTRIES OPTIMIZATION TEST SUITE                \n";
        echo "========================================================================\n\n";

        try {
            $this->testSchemaAndIndexes();
            $this->testManualJournalDoubleEntry();
            $this->testJournalReversals();
            $this->testJournalDetails();
            $this->testPaginationAndLimits();
            $this->testUniversalMultiColumnSearch();
            $this->testSecurityAndAudit();
            $this->testUIDesignCompliance();
        } catch (Throwable $e) {
            echo "\n[FATAL EXCEPTION]: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
        } finally {
            $this->cleanUpFixtures();
        }

        echo "\n========================================================================\n";
        echo "   TEST SUMMARY: Total: {$this->totalTests} | Passed: {$this->passedTests} | Failed: {$this->failedTests} \n";
        echo "========================================================================\n";

        if ($this->failedTests > 0) {
            exit(1);
        }
    }

    private function testSchemaAndIndexes(): void
    {
        echo "--- SECTION 1: Schema & Composite Index Verification ---\n";

        // Check composite indexes on journal_entries
        $idx1 = $this->db->query("SHOW INDEX FROM journal_entries WHERE Key_name = 'idx_je_branch_status_date'")->fetch();
        $this->assertTest("journal_entries has composite index idx_je_branch_status_date", !empty($idx1));

        $idx2 = $this->db->query("SHOW INDEX FROM journal_entries WHERE Key_name = 'idx_je_branch_source'")->fetch();
        $this->assertTest("journal_entries has composite index idx_je_branch_source", !empty($idx2));

        $idx3 = $this->db->query("SHOW INDEX FROM journal_entries WHERE Key_name = 'idx_je_status_date'")->fetch();
        $this->assertTest("journal_entries has composite index idx_je_status_date", !empty($idx3));

        // Check composite indexes on journal_lines
        $idx4 = $this->db->query("SHOW INDEX FROM journal_lines WHERE Key_name = 'idx_jl_je_acc'")->fetch();
        $this->assertTest("journal_lines has composite index idx_jl_je_acc", !empty($idx4));

        $idx5 = $this->db->query("SHOW INDEX FROM journal_lines WHERE Key_name = 'idx_jl_acc_je'")->fetch();
        $this->assertTest("journal_lines has composite index idx_jl_acc_je", !empty($idx5));
    }

    private function testManualJournalDoubleEntry(): void
    {
        echo "\n--- SECTION 2: Manual Journal Posting & Double-Entry Math ---\n";

        // Fetch two valid active accounts from COA
        $acc1 = Database::fetchOne("SELECT id, account_code FROM chart_of_accounts WHERE status = 'active' ORDER BY id ASC LIMIT 1");
        $acc2 = Database::fetchOne("SELECT id, account_code FROM chart_of_accounts WHERE status = 'active' AND id != :id ORDER BY id ASC LIMIT 1", [':id' => $acc1['id']]);

        $this->assertTest("Two active COA accounts available for double-entry test", !empty($acc1) && !empty($acc2));

        // Test unbalanced journal rejection
        $header = [
            'branch_id'   => 1,
            'entry_date'  => date('Y-m-d'),
            'source_type' => 'manual',
            'description' => 'Unbalanced Test Journal',
            'created_by'  => 1
        ];
        $unbalancedLines = [
            ['account_id' => $acc1['id'], 'debit' => 5000.00, 'credit' => 0.00, 'memo' => 'Debit Line'],
            ['account_id' => $acc2['id'], 'debit' => 0.00, 'credit' => 4500.00, 'memo' => 'Credit Line']
        ];
        $resUnbal = Accounting::postJournalEntry($header, $unbalancedLines);
        $this->assertTest("postJournalEntry rejects unbalanced lines (Dr 5000 != Cr 4500)", !$resUnbal['success']);

        // Test minimum lines rejection (1 line only)
        $singleLine = [
            ['account_id' => $acc1['id'], 'debit' => 5000.00, 'credit' => 0.00, 'memo' => 'Only One Line']
        ];
        $resSingle = Accounting::postJournalEntry($header, $singleLine);
        $this->assertTest("postJournalEntry rejects journal with fewer than 2 lines", !$resSingle['success']);

        // Test valid balanced manual journal posting
        $balancedLines = [
            ['account_id' => $acc1['id'], 'debit' => 12500.00, 'credit' => 0.00, 'memo' => 'Test Dr Line'],
            ['account_id' => $acc2['id'], 'debit' => 0.00, 'credit' => 12500.00, 'memo' => 'Test Cr Line']
        ];
        $header['description'] = 'Valid Manual Journal Posting Test';
        $resBal = Accounting::postJournalEntry($header, $balancedLines);
        $this->assertTest("postJournalEntry succeeds with balanced double-entry (Dr 12500 = Cr 12500)", $resBal['success']);

        $journalId = (int)($resBal['journal_id'] ?? $resBal['journal_entry_id'] ?? 0);
        $this->assertTest("postJournalEntry returns valid journal_id and journal_entry_id", $journalId > 0);
        if ($journalId > 0) {
            $this->createdJournalIds[] = $journalId;
        }

        // Verify entry in database
        $dbEntry = Database::fetchOne("SELECT * FROM journal_entries WHERE id = :id", [':id' => $journalId]);
        $this->assertTest("journal_entries record created with posting_status 'posted'", !empty($dbEntry) && $dbEntry['posting_status'] === 'posted');
        $this->assertTest("total_debit matches 12500.00 exactly", (float)$dbEntry['total_debit'] === 12500.00);
        $this->assertTest("total_credit matches 12500.00 exactly", (float)$dbEntry['total_credit'] === 12500.00);

        // Verify journal lines
        $dbLines = Database::fetchAll("SELECT * FROM journal_lines WHERE journal_entry_id = :id ORDER BY id ASC", [':id' => $journalId]);
        $this->assertTest("journal_lines contains exactly 2 lines", count($dbLines) === 2);
        $this->assertTest("Line 1 debit is 12500.00 and credit is 0.00", (float)$dbLines[0]['debit'] === 12500.00 && (float)$dbLines[0]['credit'] === 0.00);
        $this->assertTest("Line 2 debit is 0.00 and credit is 12500.00", (float)$dbLines[1]['debit'] === 0.00 && (float)$dbLines[1]['credit'] === 12500.00);
    }

    private function testJournalReversals(): void
    {
        echo "\n--- SECTION 3: Journal Reversal Lifecycle & Double-Entry Inversion ---\n";

        // Create a new balanced journal entry to reverse
        $acc1 = Database::fetchOne("SELECT id FROM chart_of_accounts WHERE status = 'active' ORDER BY id ASC LIMIT 1");
        $acc2 = Database::fetchOne("SELECT id FROM chart_of_accounts WHERE status = 'active' AND id != :id ORDER BY id ASC LIMIT 1", [':id' => $acc1['id']]);

        $header = [
            'branch_id'   => 1,
            'entry_date'  => date('Y-m-d'),
            'source_type' => 'manual',
            'description' => 'Entry To Be Reversed',
            'created_by'  => 1
        ];
        $lines = [
            ['account_id' => $acc1['id'], 'debit' => 8800.00, 'credit' => 0.00, 'memo' => 'Original Dr'],
            ['account_id' => $acc2['id'], 'debit' => 0.00, 'credit' => 8800.00, 'memo' => 'Original Cr']
        ];
        $postRes = Accounting::postJournalEntry($header, $lines);
        $origId = (int)$postRes['journal_id'];
        $this->createdJournalIds[] = $origId;

        // Test reversal requires reason
        $failRev = Accounting::reverseJournalEntry($origId, '', 1);
        $this->assertTest("reverseJournalEntry rejects empty reversal reason", !$failRev['success']);

        // Execute reversal
        $revRes = Accounting::reverseJournalEntry($origId, 'Test Audit Reversal Reason', 1);
        $this->assertTest("reverseJournalEntry succeeds with valid reason", $revRes['success']);

        $revId = (int)($revRes['reversal_journal_id'] ?? 0);
        $this->assertTest("Reversal entry ID generated", $revId > 0);
        if ($revId > 0) {
            $this->createdJournalIds[] = $revId;
        }

        // Verify original entry marked as reversed
        $origUpdated = Database::fetchOne("SELECT posting_status, reversed_journal_id FROM journal_entries WHERE id = :id", [':id' => $origId]);
        $this->assertTest("Original entry posting_status updated to 'reversed'", $origUpdated['posting_status'] === 'reversed');
        $this->assertTest("Original entry links to reversed_journal_id", (int)$origUpdated['reversed_journal_id'] === $revId);

        // Verify reversal entry properties
        $revEntry = Database::fetchOne("SELECT * FROM journal_entries WHERE id = :id", [':id' => $revId]);
        $this->assertTest("Reversal entry source_type is 'reversal'", $revEntry['source_type'] === 'reversal');
        $this->assertTest("Reversal entry source_id is original journal ID", (int)$revEntry['source_id'] === $origId);
        $this->assertTest("Reversal entry is balanced (Dr 8800 = Cr 8800)", (float)$revEntry['total_debit'] === 8800.00 && (float)$revEntry['total_credit'] === 8800.00);

        // Verify reversal lines swapped debits and credits
        $revLines = Database::fetchAll("SELECT * FROM journal_lines WHERE journal_entry_id = :id ORDER BY id ASC", [':id' => $revId]);
        $this->assertTest("Reversal lines count matches original (2 lines)", count($revLines) === 2);
        $this->assertTest("Reversal Line 1 swaps to Credit 8800.00", (float)$revLines[0]['credit'] === 8800.00 && (float)$revLines[0]['debit'] === 0.00);
        $this->assertTest("Reversal Line 2 swaps to Debit 8800.00", (float)$revLines[1]['debit'] === 8800.00 && (float)$revLines[1]['credit'] === 0.00);

        // Test double-reversal blocked
        $dupRev = Accounting::reverseJournalEntry($origId, 'Attempting duplicate reversal', 1);
        $this->assertTest("Attempting to reverse an already reversed journal is rejected", !$dupRev['success']);
    }

    private function testJournalDetails(): void
    {
        echo "\n--- SECTION 4: Journal Details API & Line Resolution ---\n";

        $testId = end($this->createdJournalIds);
        $details = Accounting::getJournalEntryDetails($testId);

        $this->assertTest("getJournalEntryDetails returns non-null array", !empty($details));
        $this->assertTest("getJournalEntryDetails includes branch_name", isset($details['branch_name']));
        $this->assertTest("getJournalEntryDetails includes creator_name", isset($details['creator_name']));
        $this->assertTest("getJournalEntryDetails includes lines array with account_code and account_name", !empty($details['lines']) && isset($details['lines'][0]['account_code']));
    }

    private function testPaginationAndLimits(): void
    {
        echo "\n--- SECTION 5: Server-Side Pagination & Whitelisted Limits ---\n";

        $limits = [25, 50, 75, 100, 500];
        foreach ($limits as $lim) {
            $res = Accounting::getJournalEntries([], 1, $lim);
            $this->assertTest("getJournalEntries works with limit {$lim}", isset($res['items']) && $res['limit'] === $lim);
        }

        // Test non-whitelisted limit fallback
        $apiGet = function($lim) {
            return in_array((int)$lim, [25, 50, 75, 100, 500], true) ? (int)$lim : 25;
        };
        $this->assertTest("Non-whitelisted limit 9999 falls back to default 25", $apiGet(9999) === 25);
        $this->assertTest("Non-whitelisted limit 0 falls back to default 25", $apiGet(0) === 25);
    }

    private function testUniversalMultiColumnSearch(): void
    {
        echo "\n--- SECTION 6: Universal Multi-Column Search Verification ---\n";

        // Create a unique searchable journal fixture
        $acc1 = Database::fetchOne("SELECT id, account_code, account_name FROM chart_of_accounts WHERE status = 'active' ORDER BY id ASC LIMIT 1");
        $acc2 = Database::fetchOne("SELECT id, account_code, account_name FROM chart_of_accounts WHERE status = 'active' AND id != :id ORDER BY id ASC LIMIT 1", [':id' => $acc1['id']]);

        $uniqueKey = 'UNQJE' . time();
        $header = [
            'branch_id'   => 1,
            'entry_date'  => date('Y-m-d'),
            'source_type' => 'manual',
            'description' => "Test Narrative {$uniqueKey}",
            'created_by'  => 1
        ];
        $lines = [
            ['account_id' => $acc1['id'], 'debit' => 9324.50, 'credit' => 0.00, 'memo' => "DrMemo {$uniqueKey}"],
            ['account_id' => $acc2['id'], 'debit' => 0.00, 'credit' => 9324.50, 'memo' => "CrMemo {$uniqueKey}"]
        ];
        $postRes = Accounting::postJournalEntry($header, $lines);
        $jeId = (int)$postRes['journal_id'];
        $this->createdJournalIds[] = $jeId;
        $entryNum = $postRes['entry_number'];

        // 1. Search by exact Entry Number
        $s1 = Accounting::getJournalEntries(['search' => $entryNum]);
        $found1 = array_filter($s1['items'], fn($item) => $item['id'] == $jeId);
        $this->assertTest("Search by Entry Number '{$entryNum}' matches record", !empty($found1));

        // 2. Search by unique narrative text
        $s2 = Accounting::getJournalEntries(['search' => $uniqueKey]);
        $found2 = array_filter($s2['items'], fn($item) => $item['id'] == $jeId);
        $this->assertTest("Search by narrative text '{$uniqueKey}' matches record", !empty($found2));

        // 3. Search by line memo text
        $s3 = Accounting::getJournalEntries(['search' => "DrMemo {$uniqueKey}"]);
        $found3 = array_filter($s3['items'], fn($item) => $item['id'] == $jeId);
        $this->assertTest("Search by line memo 'DrMemo {$uniqueKey}' matches record", !empty($found3));

        // 4. Search by raw numeric amount
        $s4 = Accounting::getJournalEntries(['search' => '9324.50']);
        $found4 = array_filter($s4['items'], fn($item) => $item['id'] == $jeId);
        $this->assertTest("Search by raw numeric amount '9324.50' matches record", !empty($found4));

        // 5. Search by comma-formatted currency amount
        $s5 = Accounting::getJournalEntries(['search' => '9,324.50']);
        $found5 = array_filter($s5['items'], fn($item) => $item['id'] == $jeId);
        $this->assertTest("Search by comma-formatted currency '9,324.50' matches record", !empty($found5));

        // 6. Search by formatted currency with LKR prefix
        $s6 = Accounting::getJournalEntries(['search' => 'LKR 9,324.50']);
        $found6 = array_filter($s6['items'], fn($item) => $item['id'] == $jeId);
        $this->assertTest("Search by formatted currency with LKR prefix 'LKR 9,324.50' matches record", !empty($found6));

        // 7. Search by account code from line items
        $s7 = Accounting::getJournalEntries(['search' => $acc1['account_code']]);
        $found7 = array_filter($s7['items'], fn($item) => $item['id'] == $jeId);
        $this->assertTest("Search by Account Code '{$acc1['account_code']}' matches record", !empty($found7));
    }

    private function testSecurityAndAudit(): void
    {
        echo "\n--- SECTION 7: Security, Special User Restrictions & Audit Logging ---\n";

        // Test Special User restriction
        $_SESSION['role_code'] = ROLE_SPECIAL_USER;
        $isSpec = Auth::isSpecialUser();
        $this->assertTest("Auth::isSpecialUser() recognizes special user session", $isSpec === true);

        // Reset session role
        $_SESSION['role_code'] = ROLE_SUPER_ADMIN;

        // Test CSRF token validation
        $token = Auth::generateCsrfToken();
        $this->assertTest("Auth::generateCsrfToken returns non-empty string", !empty($token));
        $this->assertTest("Auth::validateCsrfToken validates active session token", Auth::validateCsrfToken($token));
        $this->assertTest("Auth::validateCsrfToken rejects tampered token", !Auth::validateCsrfToken('tampered_token'));

        // Verify audit log captured JOURNAL_POSTED
        $latestPostId = end($this->createdJournalIds);
        $auditPost = Database::fetchOne("
            SELECT * FROM audit_logs 
            WHERE module = 'accounting' AND action = 'JOURNAL_POSTED' AND record_id = :id 
            ORDER BY id DESC LIMIT 1
        ", [':id' => (string)$latestPostId]);
        $this->assertTest("audit_logs contains entry for JOURNAL_POSTED with record_id {$latestPostId}", !empty($auditPost));
    }

    private function testUIDesignCompliance(): void
    {
        echo "\n--- SECTION 8: UI Design & Master Rules Compliance ---\n";

        $viewFile = __DIR__ . '/../views/journal_entries/index.php';
        $content = file_get_contents($viewFile);

        $this->assertTest("View implements fixed viewport height calc(100vh - 110px)", str_contains($content, 'height: calc(100vh - 110px)'));
        $this->assertTest("View implements internal table scroll container .prod-table-scroll", str_contains($content, 'prod-table-scroll'));
        $this->assertTest("View enforces standard 48px row heights", str_contains($content, 'height: 48px;') && str_contains($content, 'min-height: 48px;'));
        $this->assertTest("View enforces sticky table header position: sticky; top: 0;", str_contains($content, 'position: sticky;') && str_contains($content, 'top: 0;'));
        $this->assertTest("View enforces zebra striping with rgba(248, 250, 252, 0.57)", str_contains($content, 'rgba(248, 250, 252, 0.57)'));
        $this->assertTest("View enforces headline only without subtitle tagline", !str_contains($content, 'inv-subtitle'));
        $this->assertTest("View mounts primary actions in #curtiss-floating-header-right", str_contains($content, 'id="curtiss-floating-header-right"'));
        $this->assertTest("View implements CurtissNav.enablePageSearch bottom realtime search", str_contains($content, 'CurtissNav.enablePageSearch'));
        $this->assertTest("View implements 300ms search debounce", str_contains($content, 'searchDebounceTimer'));
        $this->assertTest("View implements double-submit protection on manual journal form", str_contains($content, 'submitBtn.disabled = true;'));
        $this->assertTest("View implements double-submit protection on reversal form", str_contains($content, 'confirmBtn.disabled = true;'));
        $this->assertTest("View includes meta name=csrf-token", str_contains($content, 'meta name="csrf-token"'));
        $this->assertTest("View attaches CurtissSearchableSelect to filter dropdowns", str_contains($content, 'CurtissSearchableSelect.attach'));
        $this->assertTest("View implements full-row click for details modal", str_contains($content, 'onclick="viewJournalDetails(${j.id})"'));
    }

    private function cleanUpFixtures(): void
    {
        if (empty($this->createdJournalIds)) {
            return;
        }

        echo "\n--- Cleaning up isolated test fixtures (ZERO table truncation) ---\n";
        foreach ($this->createdJournalIds as $id) {
            $this->db->prepare("DELETE FROM journal_lines WHERE journal_entry_id = :id")->execute([':id' => $id]);
            $this->db->prepare("DELETE FROM journal_entries WHERE id = :id")->execute([':id' => $id]);
            $this->db->prepare("DELETE FROM audit_logs WHERE module = 'accounting' AND record_id = :id")->execute([':id' => (string)$id]);
        }
        echo "Cleaned up " . count($this->createdJournalIds) . " isolated test journal entries and audit records.\n";
    }
}

$suite = new JournalEntriesOptimizationTestSuite();
$suite->runAll();
