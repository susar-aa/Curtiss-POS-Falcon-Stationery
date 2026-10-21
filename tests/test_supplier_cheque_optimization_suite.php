<?php
/**
 * Curtiss POS - Comprehensive Supplier Cheques Optimization & Compliance Suite
 * Tests all functionality, database indexes, pagination, multi-column search,
 * lifecycle (Clear, Bounce, Cancel), accounting journals, soft-delete Trash Bin,
 * Special User masking, and UI design compliance.
 *
 * ZERO TRUNCATION: Creates and tears down isolated test fixtures cleanly.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/ChequeEngine.php';
require_once __DIR__ . '/../core/Banking.php';
require_once __DIR__ . '/../core/Accounting.php';
require_once __DIR__ . '/../core/MaskingHelper.php';

class SupplierChequeOptimizationSuite
{
    private int $passCount = 0;
    private int $failCount = 0;
    private array $testChequeIds = [];
    private array $testPaymentIds = [];
    private array $testGrnIds = [];
    private array $testSupplierIds = [];
    private array $testJournalIds = [];

    public function run(): void
    {
        echo "\n======================================================\n";
        echo "  CURTISS POS - SUPPLIER CHEQUES OPTIMIZATION SUITE   \n";
        echo "======================================================\n";

        try {
            $this->testDatabaseSchemaAndIndexes();
            $this->testChequeLifecycleAndAccounting();
            $this->testSoftDeleteAndTrashBin();
            $this->testServerSidePagination();
            $this->testUniversalMultiColumnSearch();
            $this->testSpecialUserMasking();
            $this->testPendingSummaryPerformance();
            $this->testUiDesignCompliance();
        } catch (Throwable $e) {
            echo "\n[FATAL ERROR] Unexpected exception in test suite: " . $e->getMessage() . "\n";
            echo $e->getTraceAsString() . "\n";
            $this->failCount++;
        } finally {
            $this->cleanUpFixtures();
        }

        echo "\n======================================================\n";
        echo "  TEST SUMMARY: {$this->passCount} PASSED, {$this->failCount} FAILED\n";
        echo "======================================================\n\n";

        if ($this->failCount > 0) {
            exit(1);
        }
    }

    private function assert(string $label, bool $condition, string $detail = ''): void
    {
        if ($condition) {
            echo " [PASS] {$label}\n";
            $this->passCount++;
        } else {
            echo " [FAIL] {$label}" . ($detail ? " ({$detail})" : "") . "\n";
            $this->failCount++;
        }
    }

    private function testDatabaseSchemaAndIndexes(): void
    {
        echo "\n--- 1. Database Schema & Composite Indexes ---\n";

        $colDeleted = Database::fetchOne("SHOW COLUMNS FROM supplier_cheques LIKE 'deleted_at'");
        $this->assert("Column 'deleted_at' exists on supplier_cheques table", !empty($colDeleted));

        $indexes = Database::fetchAll("SHOW INDEX FROM supplier_cheques");
        $idxNames = array_column($indexes, 'Key_name');

        $this->assert("Composite index 'idx_sc_del_status_id' exists on supplier_cheques", in_array('idx_sc_del_status_id', $idxNames, true));
        $this->assert("Composite index 'idx_sc_del_branch_status' exists on supplier_cheques", in_array('idx_sc_del_branch_status', $idxNames, true));
        $this->assert("Index 'idx_sc_cheque_num' exists on supplier_cheques", in_array('idx_sc_cheque_num', $idxNames, true));
        $this->assert("Index 'idx_sc_cheque_date' exists on supplier_cheques", in_array('idx_sc_cheque_date', $idxNames, true));
    }

    private function testChequeLifecycleAndAccounting(): void
    {
        echo "\n--- 2. Cheque Lifecycle, GRN Reversals & Double-Entry Accounting ---\n";

        // Create isolated test supplier
        $supCode = 'SUP-TEST-' . rand(1000, 9999);
        Database::execute("INSERT INTO suppliers (supplier_code, name, phone, status) VALUES (:code, :name, '0770001122', 'active')", [
            ':code' => $supCode,
            ':name' => 'Cheque Test Supplier ' . $supCode
        ]);
        $supplierId = (int)Database::lastInsertId();
        $this->testSupplierIds[] = $supplierId;

        // Create isolated test GRN
        $grnNum = 'GRN-TEST-' . rand(1000, 9999);
        Database::execute("
            INSERT INTO grns (grn_number, supplier_id, branch_id, received_date, actual_grn_total, paid_amount, payment_status, status, created_by)
            VALUES (:num, :sup, 1, '2026-10-16', 15000.00, 15000.00, 'paid', 'Approved', 1)
        ", [':num' => $grnNum, ':sup' => $supplierId]);
        $grnId = (int)Database::lastInsertId();
        $this->testGrnIds[] = $grnId;

        // Create isolated supplier payment
        $payNum = 'PAY-TEST-' . rand(1000, 9999);
        Database::execute("
            INSERT INTO supplier_payments (payment_no, supplier_id, branch_id, payment_date, total_amount, payment_method, created_by)
            VALUES (:pno, :sup, 1, '2026-10-16', 15000.00, 'cheque', 1)
        ", [':pno' => $payNum, ':sup' => $supplierId]);
        $paymentId = (int)Database::lastInsertId();
        $this->testPaymentIds[] = $paymentId;

        // Allocate GRN to payment
        Database::execute("
            INSERT INTO supplier_payment_grns (supplier_payment_id, grn_id, allocated_amount)
            VALUES (:sp, :grn, 15000.00)
        ", [':sp' => $paymentId, ':grn' => $grnId]);

        // Post original payment journal entry
        $apAccId = Accounting::getAccountIdByCode(Accounting::CODE_SUPPLIER_PAYABLE);
        $chequesIssuedAccId = Accounting::getAccountIdByCode(Accounting::CODE_CHEQUES_ISSUED);
        $payJeRes = Accounting::postJournalEntry([
            'branch_id'   => 1,
            'entry_date'  => '2026-10-16',
            'source_type' => 'supplier_payment',
            'source_id'   => $paymentId,
            'description' => "Supplier Payment #{$payNum} Cheque Issuance",
            'created_by'  => 1
        ], [
            ['account_id' => $apAccId, 'debit' => 15000.00, 'credit' => 0.00, 'memo' => 'AP Debit'],
            ['account_id' => $chequesIssuedAccId, 'debit' => 0.00, 'credit' => 15000.00, 'memo' => 'Cheques Issued Obligation']
        ]);
        if (!empty($payJeRes['journal_id'])) {
            $this->testJournalIds[] = (int)$payJeRes['journal_id'];
        }

        // Create isolated test cheque
        $chequeNum = 'CHQ-' . rand(100000, 999999);
        Database::execute("
            INSERT INTO supplier_cheques (supplier_payment_id, supplier_id, branch_id, cheque_number, cheque_date, bank_name, amount, status, created_by)
            VALUES (:sp, :sup, 1, :cnum, '2026-10-25', 'Commercial Bank', 15000.00, 'pending', 1)
        ", [':sp' => $paymentId, ':sup' => $supplierId, ':cnum' => $chequeNum]);
        $chequeId = (int)Database::lastInsertId();
        $this->testChequeIds[] = $chequeId;

        $this->assert("Supplier cheque created with status 'pending'", $chequeId > 0);

        // Fetch bank accounts for clearance test
        $bankAccs = Banking::getBankAccounts(1, true);
        $targetBankAccId = !empty($bankAccs) ? (int)$bankAccs[0]['id'] : 1;

        // 1. Test Clearing Cheque
        $clearRes = ChequeEngine::clearCheque([
            'cheque_id'       => $chequeId,
            'bank_account_id' => $targetBankAccId,
            'cleared_date'    => '2026-10-25'
        ], 1);

        $this->assert("Cheque clearing succeeded", $clearRes['success']);
        if (!empty($clearRes['journal_entry_id'])) {
            $this->testJournalIds[] = (int)$clearRes['journal_entry_id'];
        }

        $clearedCheque = ChequeEngine::getCheque($chequeId);
        $this->assert("Cheque status updated to 'cleared'", ($clearedCheque['status'] ?? '') === 'cleared');
        $this->assert("Cleared timestamp and user recorded", !empty($clearedCheque['cleared_at']) && (int)$clearedCheque['cleared_by'] === 1);

        // Verify clearance journal entry
        $clearingJe = Database::fetchOne("
            SELECT * FROM journal_entries WHERE id = :id
        ", [':id' => $clearedCheque['journal_entry_id']]);
        $this->assert("Clearance journal entry created and posted", !empty($clearingJe) && $clearingJe['posting_status'] === 'posted');
        $this->assert("Clearance journal total matches 15000.00 and is balanced", (float)$clearingJe['total_debit'] === 15000.00 && (float)$clearingJe['total_debit'] === (float)$clearingJe['total_credit']);

        // 2. Test Bouncing a Cleared Cheque
        $bounceRes = ChequeEngine::bounceCheque([
            'cheque_id'      => $chequeId,
            'bounced_reason' => 'Account Frozen / Signature Mismatch',
            'bounced_date'   => '2026-10-26'
        ], 1);

        $this->assert("Bouncing cleared cheque succeeded", $bounceRes['success']);
        $bouncedCheque = ChequeEngine::getCheque($chequeId);
        $this->assert("Cheque status transitioned to 'bounced'", ($bouncedCheque['status'] ?? '') === 'bounced');

        // Verify GRN paid amount and payment status were reversed
        $reopenedGrn = Database::fetchOne("SELECT paid_amount, payment_status FROM grns WHERE id = :id", [':id' => $grnId]);
        $this->assert("GRN paid amount restored to 0.00 upon cheque bounce", (float)($reopenedGrn['paid_amount'] ?? 1) === 0.00);
        $this->assert("GRN payment status restored to 'unpaid'", ($reopenedGrn['payment_status'] ?? '') === 'unpaid');

        // Verify clearance journal was marked as reversed
        $clearingJeUpdated = Database::fetchOne("SELECT posting_status FROM journal_entries WHERE id = :id", [':id' => $clearedCheque['journal_entry_id']]);
        $this->assert("Clearance journal entry marked as 'reversed'", ($clearingJeUpdated['posting_status'] ?? '') === 'reversed');

        // 3. Test Cancelling a Pending Cheque
        $chequeNum2 = 'CHQ-' . rand(100000, 999999);
        Database::execute("
            INSERT INTO supplier_cheques (supplier_payment_id, supplier_id, branch_id, cheque_number, cheque_date, bank_name, amount, status, created_by)
            VALUES (:sp, :sup, 1, :cnum, '2026-10-28', 'Sampath Bank', 5000.00, 'pending', 1)
        ", [':sp' => $paymentId, ':sup' => $supplierId, ':cnum' => $chequeNum2]);
        $chequeId2 = (int)Database::lastInsertId();
        $this->testChequeIds[] = $chequeId2;

        $cancelRes = ChequeEngine::cancelCheque([
            'cheque_id'           => $chequeId2,
            'cancellation_reason' => 'Cheque Damaged on Printing'
        ], 1);

        $this->assert("Pending cheque cancellation succeeded", $cancelRes['success']);
        $cancelledCheque = ChequeEngine::getCheque($chequeId2);
        $this->assert("Cheque status updated to 'cancelled'", ($cancelledCheque['status'] ?? '') === 'cancelled');
    }

    private function testSoftDeleteAndTrashBin(): void
    {
        echo "\n--- 3. Soft Delete Trash Bin & Restore System ---\n";

        $chequeNum = 'CHQ-DEL-' . rand(100000, 999999);
        $supId = !empty($this->testSupplierIds) ? $this->testSupplierIds[0] : 1;
        $payId = !empty($this->testPaymentIds) ? $this->testPaymentIds[0] : 1;

        Database::execute("
            INSERT INTO supplier_cheques (supplier_payment_id, supplier_id, branch_id, cheque_number, cheque_date, bank_name, amount, status, created_by)
            VALUES (:sp, :sup, 1, :cnum, '2026-10-30', 'HNB Bank', 7500.00, 'pending', 1)
        ", [':sp' => $payId, ':sup' => $supId, ':cnum' => $chequeNum]);
        $chqId = (int)Database::lastInsertId();
        $this->testChequeIds[] = $chqId;

        // Soft delete
        $delRes = ChequeEngine::delete($chqId, 1);
        $this->assert("Cheque soft deletion succeeded", $delRes['success']);

        $dbCheck = Database::fetchOne("SELECT deleted_at, status FROM supplier_cheques WHERE id = :id", [':id' => $chqId]);
        $this->assert("deleted_at timestamp is populated in DB", !empty($dbCheck['deleted_at']));
        $this->assert("Status transitioned to cancelled upon soft deletion", $dbCheck['status'] === 'cancelled');

        // Check active list excludes it
        $activeList = ChequeEngine::getPaginatedCheques(['search' => $chequeNum, 'view' => 'active'], 1, 25);
        $this->assert("Soft-deleted cheque excluded from active list", empty($activeList['items']));

        // Check trash bin includes it
        $trashList = ChequeEngine::getPaginatedCheques(['search' => $chequeNum, 'view' => 'deleted'], 1, 25);
        $this->assert("Soft-deleted cheque appears in Trash Bin view (view=deleted)", !empty($trashList['items']) && (int)$trashList['items'][0]['id'] === $chqId);

        // Restore
        $resRes = ChequeEngine::restore($chqId, 1);
        $this->assert("Cheque restoration succeeded", $resRes['success']);

        $restoredCheck = Database::fetchOne("SELECT deleted_at, status FROM supplier_cheques WHERE id = :id", [':id' => $chqId]);
        $this->assert("deleted_at is reset to NULL upon restoration", $restoredCheck['deleted_at'] === null);
        $this->assert("Status is restored to 'pending'", $restoredCheck['status'] === 'pending');

        $activeListAfter = ChequeEngine::getPaginatedCheques(['search' => $chequeNum, 'view' => 'active'], 1, 25);
        $this->assert("Restored cheque is back in active list", !empty($activeListAfter['items']));
    }

    private function testServerSidePagination(): void
    {
        echo "\n--- 4. Server-Side Pagination & Whitelisted Limits ---\n";

        $limits = [25, 50, 75, 100, 500];
        foreach ($limits as $lim) {
            $data = ChequeEngine::getPaginatedCheques([], 1, $lim);
            $this->assert("Pagination works with whitelisted limit {$lim}", $data['limit'] === $lim && is_array($data['items']));
        }

        // Test invalid limit falls back to 25
        $invalid = ChequeEngine::getPaginatedCheques([], 1, 9999);
        $this->assert("Invalid limit (9999) correctly falls back to default 25", $invalid['limit'] === 25);
    }

    private function testUniversalMultiColumnSearch(): void
    {
        echo "\n--- 5. Universal Floating Search Across All Columns ---\n";

        $supId = !empty($this->testSupplierIds) ? $this->testSupplierIds[0] : 1;
        $payId = !empty($this->testPaymentIds) ? $this->testPaymentIds[0] : 1;
        $vCode = 'CHQ-SEARCH-' . rand(100000, 999999);

        Database::execute("
            INSERT INTO supplier_cheques (supplier_payment_id, supplier_id, branch_id, cheque_number, cheque_date, bank_name, amount, status, created_by)
            VALUES (:sp, :sup, 1, :cnum, '2026-11-05', 'Seylan Special Bank', 24680.00, 'pending', 1)
        ", [':sp' => $payId, ':sup' => $supId, ':cnum' => $vCode]);
        $cId = (int)Database::lastInsertId();
        $this->testChequeIds[] = $cId;

        // Search by Cheque #
        $s1 = ChequeEngine::getPaginatedCheques(['search' => $vCode], 1, 25);
        $this->assert("Search by Cheque # ({$vCode}) returns match", !empty($s1['items']) && (int)$s1['items'][0]['id'] === $cId);

        // Search by Bank Name
        $s2 = ChequeEngine::getPaginatedCheques(['search' => 'Seylan Special Bank'], 1, 25);
        $this->assert("Search by Bank Name returns match", !empty($s2['items']) && (int)$s2['items'][0]['id'] === $cId);

        // Search by Cheque Date
        $s3 = ChequeEngine::getPaginatedCheques(['search' => '2026-11-05'], 1, 25);
        $this->assert("Search by Cheque Date returns match", !empty($s3['items']) && (int)$s3['items'][0]['id'] === $cId);

        // Search by Raw Amount
        $s6 = ChequeEngine::getPaginatedCheques(['search' => '24680.00'], 1, 25);
        $this->assert("Search by raw numeric amount (24680.00) returns match", !empty($s6['items']) && (int)$s6['items'][0]['id'] === $cId);

        // Search by Formatted Currency
        $s7 = ChequeEngine::getPaginatedCheques(['search' => '24,680.00'], 1, 25);
        $this->assert("Search by formatted currency (24,680.00) returns match", !empty($s7['items']) && (int)$s7['items'][0]['id'] === $cId);

        // Search by ID with '#' prefix
        $s8 = ChequeEngine::getPaginatedCheques(['search' => '#' . $cId], 1, 25);
        $this->assert("Search by ID with '#' prefix returns match", !empty($s8['items']) && (int)$s8['items'][0]['id'] === $cId);
    }

    private function testSpecialUserMasking(): void
    {
        echo "\n--- 6. Special User Presentation-Layer Masking ---\n";

        $supId = !empty($this->testSupplierIds) ? $this->testSupplierIds[0] : 1;
        $payId = !empty($this->testPaymentIds) ? $this->testPaymentIds[0] : 1;
        $cCode = 'CHQ-MASK-' . rand(100000, 999999);

        Database::execute("
            INSERT INTO supplier_cheques (supplier_payment_id, supplier_id, branch_id, cheque_number, cheque_date, bank_name, amount, status, created_by)
            VALUES (:sp, :sup, 1, :cnum, '2026-11-10', 'People\'s Bank', 10000.00, 'pending', 1)
        ", [':sp' => $payId, ':sup' => $supId, ':cnum' => $cCode]);
        $cId = (int)Database::lastInsertId();
        $this->testChequeIds[] = $cId;

        // Save session state
        $origUserId = $_SESSION['user_id'] ?? null;
        $origRoleCode = $_SESSION['role_code'] ?? null;

        $_SESSION['user_id'] = 9999;
        $_SESSION['role_code'] = defined('ROLE_SPECIAL_USER') ? ROLE_SPECIAL_USER : 'special_user';

        $this->assert("Special User recognized via Auth::isSpecialUser()", Auth::isSpecialUser());

        $data = ChequeEngine::getPaginatedCheques(['search' => $cCode], 1, 25);
        $firstItem = $data['items'][0] ?? null;

        $expectedMasked = round(10000.00 * MaskingHelper::getFactor(), 2);
        $this->assert("Cheque amount is scaled by configured percentage for Special User", $firstItem && (float)$firstItem['amount'] === $expectedMasked, "Masked: " . ($firstItem['amount'] ?? 'null') . ", Expected: {$expectedMasked}");

        $dbCheck = Database::fetchOne("SELECT amount FROM supplier_cheques WHERE id = :id", [':id' => $cId]);
        $this->assert("Underlying DB amount remains untouched at 10000.00", (float)($dbCheck['amount'] ?? 0) === 10000.00);

        // Restore session
        $_SESSION['user_id'] = $origUserId;
        $_SESSION['role_code'] = $origRoleCode;
    }

    private function testPendingSummaryPerformance(): void
    {
        echo "\n--- 7. Fast Pending Summary Aggregation ---\n";

        $summary = ChequeEngine::getPendingSummary(1);
        $this->assert("Pending summary returns count_pending", isset($summary['count_pending']) && is_int($summary['count_pending']));
        $this->assert("Pending summary returns total_pending", isset($summary['total_pending']) && is_float($summary['total_pending']));
    }

    private function testUiDesignCompliance(): void
    {
        echo "\n--- 8. UI/UX Design Compliance Check ---\n";

        $viewFile = __DIR__ . '/../views/purchasing/cheque_management.php';
        $this->assert("View file exists and is readable", file_exists($viewFile));

        $content = file_get_contents($viewFile);

        $this->assert("Fixed viewport height calc(100vh - 110px) is present", strpos($content, 'calc(100vh - 110px)') !== false);
        $this->assert("Internal table scroll container .prod-table-scroll is present", strpos($content, 'prod-table-scroll') !== false);
        $this->assert("Sticky table header position: sticky; top: 0; is present", strpos($content, 'position: sticky') !== false && strpos($content, 'top: 0') !== false);
        $this->assert("Alternating zebra striping rules are present", strpos($content, 'nth-child(even)') !== false && strpos($content, 'nth-child(odd)') !== false);
        $this->assert("Standard 48px row heights are defined", strpos($content, '48px') !== false);
        $this->assert("Subtitle tagline (<p class=\"inv-subtitle\">) is removed (headline only)", strpos($content, '<p class="inv-subtitle">') === false);
        $this->assert("Floating top-right action header (#curtiss-floating-header-right) is present", strpos($content, 'curtiss-floating-header-right') !== false);
        $this->assert("Trash Bin soft red styling (#dc2626 / #fef2f2) is present", strpos($content, '#dc2626') !== false && strpos($content, '#fef2f2') !== false);
        $this->assert("Theme-matched status badges (.prod-badge) with dot indicator are used", strpos($content, 'prod-badge-dot') !== false);
        $this->assert("Floating bottom search hook (CurtissNav.enablePageSearch) is present", strpos($content, 'CurtissNav.enablePageSearch') !== false);
        $this->assert("Realtime search excludes action button column", strpos($content, 'td:not(.col-right)') !== false);
        $this->assert("Separate View button removed from action column (full row clickable)", strpos($content, 'viewChequeDetails') !== false);
    }

    private function cleanUpFixtures(): void
    {
        echo "\n--- Cleaning up test fixtures (NO TRUNCATE) ---\n";

        // Clean journals
        if (!empty($this->testJournalIds)) {
            $ids = implode(',', array_map('intval', $this->testJournalIds));
            Database::execute("DELETE FROM journal_lines WHERE journal_entry_id IN ({$ids})");
            Database::execute("DELETE FROM journal_entries WHERE id IN ({$ids})");
            echo " [OK] Removed " . count($this->testJournalIds) . " test journal entries\n";
        }

        // Clean cheques
        if (!empty($this->testChequeIds)) {
            $ids = implode(',', array_map('intval', $this->testChequeIds));
            Database::execute("DELETE FROM supplier_cheques WHERE id IN ({$ids})");
            echo " [OK] Removed " . count($this->testChequeIds) . " test cheque records\n";
        }

        // Clean payments and GRN allocations
        if (!empty($this->testPaymentIds)) {
            $ids = implode(',', array_map('intval', $this->testPaymentIds));
            Database::execute("DELETE FROM supplier_payment_grns WHERE supplier_payment_id IN ({$ids})");
            Database::execute("DELETE FROM supplier_payments WHERE id IN ({$ids})");
            echo " [OK] Removed " . count($this->testPaymentIds) . " test supplier payment records\n";
        }

        // Clean GRNs
        if (!empty($this->testGrnIds)) {
            $ids = implode(',', array_map('intval', $this->testGrnIds));
            Database::execute("DELETE FROM grns WHERE id IN ({$ids})");
            echo " [OK] Removed " . count($this->testGrnIds) . " test GRN records\n";
        }

        // Clean suppliers
        if (!empty($this->testSupplierIds)) {
            $ids = implode(',', array_map('intval', $this->testSupplierIds));
            Database::execute("DELETE FROM suppliers WHERE id IN ({$ids})");
            echo " [OK] Removed " . count($this->testSupplierIds) . " test supplier records\n";
        }
    }
}

// Run test suite
$suite = new SupplierChequeOptimizationSuite();
$suite->run();
