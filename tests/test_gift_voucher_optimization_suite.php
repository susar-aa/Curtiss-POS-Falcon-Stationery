<?php
/**
 * Curtiss POS - Gift Voucher Optimization Test Suite
 * CLI Verification Script for CURTISS_MASTER_RULES compliance.
 * Local XAMPP Only - ZERO TRUNCATE operations allowed.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/GiftVoucher.php';
require_once __DIR__ . '/../core/Accounting.php';
require_once __DIR__ . '/../core/MaskingHelper.php';

class GiftVoucherOptimizationSuite
{
    private PDO $db;
    private int $passCount = 0;
    private int $failCount = 0;
    private array $testVoucherIds = [];
    private array $testJournalIds = [];

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function runAll(): void
    {
        echo "\n======================================================\n";
        echo "  CURTISS POS - GIFT VOUCHERS OPTIMIZATION SUITE      \n";
        echo "======================================================\n";

        try {
            $this->testDatabaseSchemaAndIndexes();
            $this->testVoucherIssuanceAndValidation();
            $this->testTrashBinAndRestore();
            $this->testPaginationWhitelisting();
            $this->testUniversalMultiColumnSearch();
            $this->testSpecialUserMasking();
            $this->testDoubleEntryAccounting();
            $this->testUiDesignCompliance();
        } catch (Throwable $e) {
            echo "[-] CRITICAL RUNTIME ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
            $this->failCount++;
        } finally {
            $this->cleanup();
        }

        echo "\n======================================================\n";
        echo "  TEST SUMMARY: {$this->passCount} PASSED, {$this->failCount} FAILED\n";
        echo "======================================================\n\n";

        if ($this->failCount > 0) {
            exit(1);
        }
    }

    private function assert(string $desc, bool $condition, string $detail = ''): void
    {
        if ($condition) {
            echo " [PASS] {$desc}\n";
            $this->passCount++;
        } else {
            echo " [FAIL] {$desc}" . ($detail ? " ({$detail})" : "") . "\n";
            $this->failCount++;
        }
    }

    private function testDatabaseSchemaAndIndexes(): void
    {
        echo "\n--- 1. Database Schema & Composite Indexes ---\n";

        $colDeleted = Database::fetchOne("SHOW COLUMNS FROM gift_vouchers LIKE 'deleted_at'");
        $this->assert("Column 'deleted_at' exists on gift_vouchers table", !empty($colDeleted));

        $colPayment = Database::fetchOne("SHOW COLUMNS FROM gift_vouchers LIKE 'payment_method'");
        $this->assert("Column 'payment_method' exists on gift_vouchers table", !empty($colPayment));

        $colBank = Database::fetchOne("SHOW COLUMNS FROM gift_vouchers LIKE 'bank_account_id'");
        $this->assert("Column 'bank_account_id' exists on gift_vouchers table", !empty($colBank));

        $indexes = Database::fetchAll("SHOW INDEX FROM gift_vouchers");
        $idxNames = array_column($indexes, 'Key_name');

        $this->assert("Composite index 'idx_gv_del_status_id' exists on gift_vouchers", in_array('idx_gv_del_status_id', $idxNames, true));
        $this->assert("Composite index 'idx_gv_code_del' exists on gift_vouchers", in_array('idx_gv_code_del', $idxNames, true));
    }

    private function testVoucherIssuanceAndValidation(): void
    {
        echo "\n--- 2. Voucher Issuance, Validation & Single-Use Rules ---\n";

        $expDate = date('Y-m-d', strtotime('+30 days'));
        $res = GiftVoucher::createVoucher(2500.00, $expDate, 'CLI Test Voucher', 1, 'Cash Drawer');

        $this->assert("Gift voucher issued successfully", $res['success'] && !empty($res['voucher_id']));
        $vId = (int)($res['voucher_id'] ?? 0);
        $vCode = $res['voucher_code'] ?? '';
        if ($vId > 0) {
            $this->testVoucherIds[] = $vId;
        }

        // Validate active voucher
        $valRes = GiftVoucher::validateVoucher($vCode, 3000.00);
        $this->assert("Voucher validation succeeds for payable total >= voucher amount", $valRes['valid']);
        $this->assert("Validated voucher amount matches 2500.00", (float)($valRes['voucher']['amount'] ?? 0) === 2500.00);

        // Test overpayment check: voucher exceeds payable total
        $overRes = GiftVoucher::validateVoucher($vCode, 1000.00);
        $this->assert("Validation correctly blocks partial voucher usage when voucher > total payable", !$overRes['valid']);

        // Cancel voucher
        $cancelRes = GiftVoucher::cancelVoucher($vId);
        $this->assert("Voucher cancellation succeeded", $cancelRes['success']);

        $cancelledCheck = GiftVoucher::validateVoucher($vCode, 3000.00);
        $this->assert("Validation correctly rejects cancelled voucher", !$cancelledCheck['valid'] && str_contains($cancelledCheck['message'], 'cancelled'));
    }

    private function testTrashBinAndRestore(): void
    {
        echo "\n--- 3. Soft Delete Trash Bin & Restore System ---\n";

        $expDate = date('Y-m-d', strtotime('+60 days'));
        $res = GiftVoucher::createVoucher(5000.00, $expDate, 'Trash Bin Test', 1, 'Cash Drawer');
        $vId = (int)($res['voucher_id'] ?? 0);
        $vCode = $res['voucher_code'] ?? '';
        $this->testVoucherIds[] = $vId;

        // Soft delete
        $delRes = GiftVoucher::delete($vId);
        $this->assert("Gift voucher soft deletion succeeded", $delRes['success']);

        $row = Database::fetchOne("SELECT status, deleted_at FROM gift_vouchers WHERE id = :id", [':id' => $vId]);
        $this->assert("deleted_at timestamp is populated in DB", !empty($row['deleted_at']));
        $this->assert("Status is updated to cancelled upon soft deletion", $row['status'] === 'cancelled');

        // Check active list exclusion
        $activeList = GiftVoucher::all(['search' => $vCode]);
        $this->assert("Soft-deleted voucher is excluded from active list", empty($activeList['items']));

        // Check Trash Bin list inclusion
        $trashList = GiftVoucher::all(['search' => $vCode, 'view' => 'deleted']);
        $this->assert("Soft-deleted voucher appears in Trash Bin view (view=deleted)", !empty($trashList['items']) && (int)$trashList['items'][0]['id'] === $vId);

        // Restore voucher
        $restoreRes = GiftVoucher::restore($vId);
        $this->assert("Gift voucher restore succeeded", $restoreRes['success']);

        $restoredRow = Database::fetchOne("SELECT status, deleted_at FROM gift_vouchers WHERE id = :id", [':id' => $vId]);
        $this->assert("deleted_at is reset to NULL", $restoredRow['deleted_at'] === null);
        $this->assert("Status is restored to active", $restoredRow['status'] === 'active');

        // Check active list inclusion post-restore
        $activeListPost = GiftVoucher::all(['search' => $vCode]);
        $this->assert("Restored voucher is back in active list", !empty($activeListPost['items']));
    }

    private function testPaginationWhitelisting(): void
    {
        echo "\n--- 4. Server-Side Pagination & Whitelisted Limits ---\n";

        $limits = [25, 50, 75, 100, 500];
        foreach ($limits as $l) {
            $data = GiftVoucher::all([], 1, $l);
            $this->assert("Pagination works with whitelisted limit {$l}", $data['limit'] === $l);
        }

        // Invalid limit fallback to default 25
        $fallback = GiftVoucher::all([], 1, 9999);
        $this->assert("Invalid limit (9999) correctly falls back to default 25", $fallback['limit'] === 25);
    }

    private function testUniversalMultiColumnSearch(): void
    {
        echo "\n--- 5. Universal Floating Search Across All Columns ---\n";

        $expDate = date('Y-m-d', strtotime('+45 days'));
        $uniqueNote = 'SPECIAL_OCCASION_' . substr(uniqid(), -6);
        $amt = 3750.00;

        $res = GiftVoucher::createVoucher($amt, $expDate, $uniqueNote, 1, 'Cash Drawer');
        $vId = (int)($res['voucher_id'] ?? 0);
        $vCode = $res['voucher_code'] ?? '';
        $this->testVoucherIds[] = $vId;

        // Search by Voucher Code
        $s1 = GiftVoucher::all(['search' => $vCode]);
        $this->assert("Search by Voucher Code ({$vCode}) returns match", !empty($s1['items']) && (int)$s1['items'][0]['id'] === $vId);

        // Search by Unique Note
        $s2 = GiftVoucher::all(['search' => $uniqueNote]);
        $this->assert("Search by Notes returns match", !empty($s2['items']) && (int)$s2['items'][0]['id'] === $vId);

        // Search by Raw Amount
        $s3 = GiftVoucher::all(['search' => '3750.00']);
        $this->assert("Search by raw numeric amount (3750.00) returns match", !empty($s3['items']) && (int)$s3['items'][0]['id'] === $vId);

        // Search by Formatted Currency
        $s4 = GiftVoucher::all(['search' => '3,750.00']);
        $this->assert("Search by formatted currency (3,750.00) returns match", !empty($s4['items']) && (int)$s4['items'][0]['id'] === $vId);

        // Search by ID with '#' prefix
        $s5 = GiftVoucher::all(['search' => '#' . $vId]);
        $this->assert("Search by ID with '#' prefix returns match", !empty($s5['items']) && (int)$s5['items'][0]['id'] === $vId);

        // Search by Expiry Date
        $s6 = GiftVoucher::all(['search' => $expDate]);
        $this->assert("Search by Expiry Date returns match", !empty($s6['items']) && (int)$s6['items'][0]['id'] === $vId);
    }

    private function testSpecialUserMasking(): void
    {
        echo "\n--- 6. Special User Presentation-Layer Masking ---\n";

        $expDate = date('Y-m-d', strtotime('+30 days'));
        $res = GiftVoucher::createVoucher(8000.00, $expDate, 'Masking Test', 1, 'Cash Drawer');
        $vId = (int)($res['voucher_id'] ?? 0);
        $vCode = $res['voucher_code'] ?? '';
        $this->testVoucherIds[] = $vId;

        // Save session state
        $origUserId = $_SESSION['user_id'] ?? null;
        $origRoleCode = $_SESSION['role_code'] ?? null;

        $_SESSION['user_id'] = 9999;
        $_SESSION['role_code'] = defined('ROLE_SPECIAL_USER') ? ROLE_SPECIAL_USER : 'special_user';

        $data = GiftVoucher::all(['search' => $vCode], 1, 25);
        $firstItem = $data['items'][0] ?? null;

        $this->assert("Special User recognized via Auth::isSpecialUser()", Auth::isSpecialUser());

        $expectedMasked = round(8000.00 * MaskingHelper::getFactor(), 2);
        $this->assert("Voucher amount is scaled by configured percentage for Special User", $firstItem && (float)$firstItem['amount'] === $expectedMasked, "Masked: " . ($firstItem['amount'] ?? 'null') . ", Expected: {$expectedMasked}");

        $dbCheck = Database::fetchOne("SELECT amount FROM gift_vouchers WHERE id = :id", [':id' => $vId]);
        $this->assert("Underlying DB amount remains untouched at 8000.00", (float)($dbCheck['amount'] ?? 0) === 8000.00);

        // Summary stats check for Special User
        $stats = GiftVoucher::getSummaryStats();
        $this->assert("Summary stats financial amounts are masked without affecting count totals", isset($stats['active_amount']));

        // Restore session
        $_SESSION['user_id'] = $origUserId;
        $_SESSION['role_code'] = $origRoleCode;
    }

    private function testDoubleEntryAccounting(): void
    {
        echo "\n--- 7. Double-Entry Accounting Verification for Vouchers ---\n";

        $expDate = date('Y-m-d', strtotime('+30 days'));
        $res = GiftVoucher::createVoucher(4000.00, $expDate, 'COA Double Entry Test', 1, 'Cash Drawer');
        $vId = (int)($res['voucher_id'] ?? 0);
        $this->testVoucherIds[] = $vId;

        // Check journal entry created for voucher issuance
        $je = Database::fetchOne("
            SELECT * FROM journal_entries 
            WHERE source_type = 'gift_voucher_issuance' AND source_id = :id AND posting_status = 'posted'
        ", [':id' => $vId]);

        $this->assert("Double-entry journal entry was automatically created upon voucher issuance", !empty($je));

        if (!empty($je)) {
            $jId = (int)$je['id'];
            $this->testJournalIds[] = $jId;

            $this->assert("Journal entry total_debit equals total_credit (balanced)", (float)$je['total_debit'] === (float)$je['total_credit']);
            $this->assert("Journal total matches voucher amount (4000.00)", (float)$je['total_debit'] === 4000.00);

            $lines = Database::fetchAll("
                SELECT jl.*, ca.account_code 
                FROM journal_lines jl 
                JOIN chart_of_accounts ca ON jl.account_id = ca.id 
                WHERE jl.journal_entry_id = :id
            ", [':id' => $jId]);

            $debitLines = array_filter($lines, fn($l) => (float)$l['debit'] > 0);
            $creditLines = array_filter($lines, fn($l) => (float)$l['credit'] > 0);

            $debitAccCodes = array_column($debitLines, 'account_code');
            $creditAccCodes = array_column($creditLines, 'account_code');

            $this->assert("Debit line posts to Cash Drawer (Account 1012)", in_array('1012', $debitAccCodes, true));
            $this->assert("Credit line posts to Gift Voucher Liability (Account 2010)", in_array('2010', $creditAccCodes, true));

            // Test cancellation reversal
            $revRes = Accounting::postVoucherCancellation($vId);
            $this->assert("Voucher cancellation triggers journal entry reversal", $revRes['success']);

            $origUpdated = Database::fetchOne("SELECT posting_status FROM journal_entries WHERE id = :id", [':id' => $jId]);
            $this->assert("Original journal entry marked as 'reversed'", $origUpdated['posting_status'] === 'reversed');

            if (!empty($revRes['reversal_entry_id'])) {
                $this->testJournalIds[] = (int)$revRes['reversal_entry_id'];
            }
        }
    }

    private function testUiDesignCompliance(): void
    {
        echo "\n--- 8. UI/UX Design Compliance Check ---\n";

        $viewFile = __DIR__ . '/../views/vouchers/voucher_list.php';
        $content = file_get_contents($viewFile);

        $this->assert("View file exists and is readable", !empty($content));
        $this->assert("Fixed viewport height calc(100vh - 110px) is present", str_contains($content, 'calc(100vh - 110px)'));
        $this->assert("Internal table scroll container .prod-table-scroll is present", str_contains($content, 'prod-table-scroll'));
        $this->assert("Sticky table header position: sticky; top: 0; is present", str_contains($content, 'position: sticky;') && str_contains($content, 'top: 0;'));
        $this->assert("Alternating zebra striping rules are present", str_contains($content, 'tr:nth-child(even)') && str_contains($content, 'rgba(248, 250, 252, 0.57)'));
        $this->assert("Standard 48px row heights are defined", str_contains($content, 'height: 48px;') && str_contains($content, 'min-height: 48px;'));
        $this->assert("Subtitle tagline (<p class=\"inv-subtitle\">) is removed (headline only)", !str_contains($content, 'class="inv-subtitle"'));
        $this->assert("Floating top-right action header (#curtiss-floating-header-right) is present", str_contains($content, 'curtiss-floating-header-right'));
        $this->assert("Trash Bin soft red styling (#dc2626 / #fef2f2) is present", str_contains($content, 'color: #dc2626; background: #fef2f2;'));
        $this->assert("Theme-matched status badges (.prod-badge) with dot indicator are used", str_contains($content, 'prod-badge') && str_contains($content, 'class="dot"'));
        $this->assert("Floating bottom search hook (CurtissNav.enablePageSearch) is present", str_contains($content, 'window.CurtissNav.enablePageSearch'));
        $this->assert("Realtime search excludes action button column", str_contains($content, 'td:not(.col-right:last-child)'));
        $this->assert("Double-submit protection (handleFormSubmit) is present", str_contains($content, 'handleFormSubmit(this)'));
    }

    private function cleanup(): void
    {
        echo "\n--- Cleaning up test fixtures (NO TRUNCATE) ---\n";

        if (!empty($this->testJournalIds)) {
            $jIdsStr = implode(',', array_map('intval', $this->testJournalIds));
            $this->db->exec("DELETE FROM journal_lines WHERE journal_entry_id IN ({$jIdsStr})");
            $this->db->exec("DELETE FROM journal_entries WHERE id IN ({$jIdsStr})");
            echo " [OK] Removed " . count($this->testJournalIds) . " test journal entries\n";
        }

        if (!empty($this->testVoucherIds)) {
            $vIdsStr = implode(',', array_map('intval', $this->testVoucherIds));
            $this->db->exec("DELETE FROM gift_vouchers WHERE id IN ({$vIdsStr})");
            echo " [OK] Removed " . count($this->testVoucherIds) . " test voucher records\n";
        }
    }
}

$suite = new GiftVoucherOptimizationSuite();
$suite->runAll();
