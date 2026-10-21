<?php
/**
 * Curtiss POS - Customer & Customer Credit Optimization Test Suite
 * CLI Verification Script for CURTISS_MASTER_RULES compliance.
 * Local XAMPP Only - NO TRUNCATE operations allowed.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Customer.php';
require_once __DIR__ . '/../core/CustomerPayment.php';
require_once __DIR__ . '/../core/Accounting.php';
require_once __DIR__ . '/../core/MaskingHelper.php';

class CustomerOptimizationSuite
{
    private PDO $db;
    private int $passCount = 0;
    private int $failCount = 0;
    private array $testCustomerIds = [];
    private array $testJournalIds = [];

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function runAll(): void
    {
        echo "\n======================================================\n";
        echo "  CURTISS POS - CUSTOMER & CREDIT OPTIMIZATION SUITE  \n";
        echo "======================================================\n";

        try {
            $this->testCompositeIndexes();
            $this->testCustomerCrudAndLifecycle();
            $this->testTrashBinAndRestore();
            $this->testPaginationWhitelisting();
            $this->testUniversalMultiColumnSearch();
            $this->testSpecialUserMasking();
            $this->testDoubleEntryAccountingCollection();
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

    private function testCompositeIndexes(): void
    {
        echo "\n--- 1. Database Composite Index Verification ---\n";
        
        $indexes = Database::fetchAll("SHOW INDEX FROM customers");
        $idxNames = array_column($indexes, 'Key_name');
        $this->assert("Composite index 'idx_cust_del_stat_id' exists on customers table", in_array('idx_cust_del_stat_id', $idxNames, true));

        $ledgerIndexes = Database::fetchAll("SHOW INDEX FROM customer_credit_ledger");
        $lIdxNames = array_column($ledgerIndexes, 'Key_name');
        $this->assert("Composite index 'idx_ccl_cust_id' exists on customer_credit_ledger table", in_array('idx_ccl_cust_id', $lIdxNames, true));
    }

    private function testCustomerCrudAndLifecycle(): void
    {
        echo "\n--- 2. Customer CRUD & Status Toggle ---\n";

        $testCode = 'TCUST-' . substr(uniqid(), -6);
        $res = Customer::create([
            'name'          => 'Test Customer CLI',
            'customer_code' => $testCode,
            'phone'         => '0771999888',
            'whatsapp'      => '0771999888',
            'card_barcode'  => 'CARD-' . substr(uniqid(), -5),
            'credit_limit'  => 15000.00,
            'address'       => '123 Test Street, Kandy'
        ]);

        $this->assert("Customer creation succeeded", $res['success'] && !empty($res['id']));
        $custId = (int)($res['id'] ?? 0);
        if ($custId > 0) {
            $this->testCustomerIds[] = $custId;
        }

        $customer = Customer::find($custId);
        $this->assert("Customer fetched successfully with active status", $customer && $customer['status'] === 'active');
        $this->assert("Customer credit limit stored correctly as 15000.00", (float)($customer['credit_limit'] ?? 0) === 15000.00);

        // Toggle status
        $toggleRes = Customer::toggleStatus($custId);
        $this->assert("Status toggled to inactive", $toggleRes['success'] && $toggleRes['new_status'] === 'inactive');

        $toggledCust = Customer::find($custId);
        $this->assert("Database reflects inactive status", $toggledCust['status'] === 'inactive');

        // Toggle back to active
        $toggleBack = Customer::toggleStatus($custId);
        $this->assert("Status toggled back to active", $toggleBack['success'] && $toggleBack['new_status'] === 'active');
    }

    private function testTrashBinAndRestore(): void
    {
        echo "\n--- 3. Soft Delete Trash Bin & Restore System ---\n";

        $testCode = 'TTRASH-' . substr(uniqid(), -6);
        $res = Customer::create([
            'name'          => 'Trash Test Customer',
            'customer_code' => $testCode,
            'phone'         => '0772000111',
            'credit_limit'  => 5000.00
        ]);
        $custId = (int)($res['id'] ?? 0);
        $this->testCustomerIds[] = $custId;

        // Soft delete
        $delRes = Customer::delete($custId);
        $this->assert("Customer soft deletion succeeded", $delRes['success']);

        $row = Database::fetchOne("SELECT status, deleted_at FROM customers WHERE id = :id", [':id' => $custId]);
        $this->assert("deleted_at timestamp is populated in DB", !empty($row['deleted_at']));
        $this->assert("Status transitioned to inactive upon soft delete", $row['status'] === 'inactive');

        // Active list check
        $activeList = Customer::all(['search' => $testCode]);
        $this->assert("Soft-deleted customer is NOT present in active list", empty($activeList['items']));

        // Trash Bin list check
        $trashList = Customer::all(['search' => $testCode, 'view' => 'deleted']);
        $this->assert("Soft-deleted customer is present in Trash Bin view (view=deleted)", !empty($trashList['items']) && (int)$trashList['items'][0]['id'] === $custId);

        // Restore
        $restoreRes = Customer::restore($custId);
        $this->assert("Customer restore succeeded", $restoreRes['success']);

        $restoredRow = Database::fetchOne("SELECT status, deleted_at FROM customers WHERE id = :id", [':id' => $custId]);
        $this->assert("deleted_at timestamp is reset to NULL", $restoredRow['deleted_at'] === null);
        $this->assert("Status is restored to active", $restoredRow['status'] === 'active');

        // Active list check post-restore
        $activeListPost = Customer::all(['search' => $testCode]);
        $this->assert("Restored customer is back in active list", !empty($activeListPost['items']));
    }

    private function testPaginationWhitelisting(): void
    {
        echo "\n--- 4. Server-Side Pagination & Whitelisted Limits ---\n";

        $limits = [25, 50, 75, 100, 500];
        foreach ($limits as $l) {
            $data = Customer::all([], 1, $l);
            $this->assert("Pagination works with whitelisted limit {$l}", $data['limit'] === $l);
        }

        // Invalid limit fallback to default 25
        $fallbackData = Customer::all([], 1, 9999);
        $this->assert("Invalid limit (9999) correctly falls back to default 25", $fallbackData['limit'] === 25);
    }

    private function testUniversalMultiColumnSearch(): void
    {
        echo "\n--- 5. Universal Floating Search Across All Columns ---\n";

        $code = 'TSEARCH-' . substr(uniqid(), -5);
        $name = 'Searchable Customer Alpha';
        $phone = '0779911223';
        $barcode = 'BAR-' . substr(uniqid(), -6);
        $limitAmt = 12345.67;

        $res = Customer::create([
            'name'          => $name,
            'customer_code' => $code,
            'phone'         => $phone,
            'card_barcode'  => $barcode,
            'credit_limit'  => $limitAmt
        ]);
        $custId = (int)($res['id'] ?? 0);
        $this->testCustomerIds[] = $custId;

        // Search by Code
        $s1 = Customer::all(['search' => $code]);
        $this->assert("Search by Customer Code ({$code}) returns match", !empty($s1['items']) && (int)$s1['items'][0]['id'] === $custId);

        // Search by Name
        $s2 = Customer::all(['search' => 'Customer Alpha']);
        $this->assert("Search by Name ('Customer Alpha') returns match", !empty($s2['items']) && (int)$s2['items'][0]['id'] === $custId);

        // Search by Phone
        $s3 = Customer::all(['search' => $phone]);
        $this->assert("Search by Phone ({$phone}) returns match", !empty($s3['items']) && (int)$s3['items'][0]['id'] === $custId);

        // Search by Barcode
        $s4 = Customer::all(['search' => $barcode]);
        $this->assert("Search by Card Barcode ({$barcode}) returns match", !empty($s4['items']) && (int)$s4['items'][0]['id'] === $custId);

        // Search by Raw Credit Limit
        $s5 = Customer::all(['search' => '12345.67']);
        $this->assert("Search by raw Credit Limit (12345.67) returns match", !empty($s5['items']) && (int)$s5['items'][0]['id'] === $custId);

        // Search by Formatted Currency String
        $s6 = Customer::all(['search' => '12,345.67']);
        $this->assert("Search by formatted currency (12,345.67) returns match", !empty($s6['items']) && (int)$s6['items'][0]['id'] === $custId);

        // Search by ID with '#' prefix
        $s7 = Customer::all(['search' => '#' . $custId]);
        $this->assert("Search by ID with '#' prefix returns match", !empty($s7['items']) && (int)$s7['items'][0]['id'] === $custId);
    }

    private function testSpecialUserMasking(): void
    {
        echo "\n--- 6. Special User Presentation-Layer Masking ---\n";

        $testCode = 'TMSK-' . substr(uniqid(), -5);
        $res = Customer::create([
            'name'          => 'Mask Test Customer',
            'customer_code' => $testCode,
            'phone'         => '0778881111',
            'credit_limit'  => 20000.00
        ]);
        $custId = (int)($res['id'] ?? 0);
        $this->testCustomerIds[] = $custId;

        // Set customer credit balance to 10000.00
        $this->db->prepare("UPDATE customers SET credit_balance = 10000.00 WHERE id = :id")->execute([':id' => $custId]);

        // Save session state
        $origUserId = $_SESSION['user_id'] ?? null;
        $origRoleCode = $_SESSION['role_code'] ?? null;

        $_SESSION['user_id'] = 9999;
        $_SESSION['role_code'] = defined('ROLE_SPECIAL_USER') ? ROLE_SPECIAL_USER : 'special_user';

        $data = Customer::all(['search' => $testCode], 1, 25);
        $firstItem = $data['items'][0] ?? null;

        $this->assert("Special User detected via Auth::isSpecialUser()", Auth::isSpecialUser());
        $expectedMasked = round(10000.00 * MaskingHelper::getFactor(), 2);
        $this->assert("Credit balance is masked by configured percentage without mutating DB", $firstItem && (float)$firstItem['credit_balance'] === $expectedMasked, "Masked: " . ($firstItem['credit_balance'] ?? 'null') . ", Expected: {$expectedMasked}");

        $dbCheck = Database::fetchOne("SELECT credit_balance FROM customers WHERE id = :id", [':id' => $custId]);
        $this->assert("Underlying DB balance is completely untouched (10000.00)", (float)($dbCheck['credit_balance'] ?? 0) === 10000.00);

        // Restore session
        $_SESSION['user_id'] = $origUserId;
        $_SESSION['role_code'] = $origRoleCode;
    }

    private function testDoubleEntryAccountingCollection(): void
    {
        echo "\n--- 7. Double-Entry Accounting Verification for Collections ---\n";

        $testCode = 'TCOA-' . substr(uniqid(), -5);
        $res = Customer::create([
            'name'          => 'COA Test Customer',
            'customer_code' => $testCode,
            'phone'         => '0773334444',
            'credit_limit'  => 20000.00
        ]);
        $custId = (int)($res['id'] ?? 0);
        $this->testCustomerIds[] = $custId;

        // Set customer credit balance to 4500.00
        $this->db->prepare("UPDATE customers SET credit_balance = 4500.00 WHERE id = :id")->execute([':id' => $custId]);

        // Insert test ledger record
        $this->db->prepare("
            INSERT INTO customer_credit_ledger (
                customer_id, branch_id, user_id, payment_method, amount_paid, amount_due, balance_after, transaction_type, reference_no, notes
            ) VALUES (
                :cid, 1, 1, 'Cash Drawer', 4500.00, -4500.00, 0.00, 'payment', 'TEST-CPAY-001', 'Test collection'
            )
        ")->execute([':cid' => $custId]);
        $cclId = (int)$this->db->lastInsertId();

        // Post to COA
        $journalRes = Accounting::postCustomerCollection($cclId);
        $this->assert("Customer collection journal posted successfully", $journalRes['success']);

        if (!empty($journalRes['journal_id'])) {
            $jId = (int)$journalRes['journal_id'];
            $this->testJournalIds[] = $jId;

            $journal = Database::fetchOne("SELECT * FROM journal_entries WHERE id = :id", [':id' => $jId]);
            $this->assert("Journal entry exists and posting_status is 'posted'", $journal && $journal['posting_status'] === 'posted');
            $this->assert("Journal total_debit equals total_credit (balanced)", (float)$journal['total_debit'] === (float)$journal['total_credit']);
            $this->assert("Journal total matches collection amount (4500.00)", (float)$journal['total_debit'] === 4500.00);

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
            $this->assert("Credit line posts to Accounts Receivable (Account 1040)", in_array('1040', $creditAccCodes, true));
        }
    }

    private function testUiDesignCompliance(): void
    {
        echo "\n--- 8. UI/UX Design Compliance Check ---\n";

        $viewFile = __DIR__ . '/../views/customers/customer_list.php';
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
        $this->assert("Clickable theme-matched status pills are used", str_contains($content, 'prod-badge-active') && str_contains($content, 'toggleCustomerStatus'));
        $this->assert("Floating bottom search hook (CurtissNav.enablePageSearch) is present", str_contains($content, 'window.CurtissNav.enablePageSearch'));
        $this->assert("Realtime search excludes action button column", str_contains($content, 'td:not(.col-right:last-child)'));
        $this->assert("Double-entry split payment modal with balanced badge is present", str_contains($content, 'customerSplitDifferenceBadge') && str_contains($content, 'Balanced (100%)'));
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

        if (!empty($this->testCustomerIds)) {
            $cIdsStr = implode(',', array_map('intval', $this->testCustomerIds));
            $this->db->exec("DELETE FROM customer_credit_ledger WHERE customer_id IN ({$cIdsStr})");
            $this->db->exec("DELETE FROM payments WHERE customer_id IN ({$cIdsStr})");
            $this->db->exec("DELETE FROM customers WHERE id IN ({$cIdsStr})");
            echo " [OK] Removed " . count($this->testCustomerIds) . " test customer records\n";
        }
    }
}

$suite = new CustomerOptimizationSuite();
$suite->runAll();
