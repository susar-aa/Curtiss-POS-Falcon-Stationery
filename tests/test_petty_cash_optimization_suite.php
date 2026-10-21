<?php
/**
 * Automated CLI Test Suite for Petty Cash & Expense Management Module
 * Comprehensive verification against .agents/rules/CURTISS_MASTER_RULES.md and AGENTS.md
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/PettyCash.php';
require_once __DIR__ . '/../core/Accounting.php';
require_once __DIR__ . '/../core/Banking.php';
require_once __DIR__ . '/../core/BusinessDay.php';
require_once __DIR__ . '/../core/MaskingHelper.php';
require_once __DIR__ . '/../core/Audit.php';

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

function assertTest(string $description, bool $condition, string $failureDetails = ''): void {
    global $totalTests, $passedTests, $failedTests;
    $totalTests++;
    if ($condition) {
        $passedTests++;
        echo " [PASS] {$description}\n";
    } else {
        $failedTests++;
        echo " [FAIL] {$description}\n";
        if ($failureDetails) {
            echo "        Details: {$failureDetails}\n";
        }
    }
}

echo "========================================================================\n";
echo "   CURTISS POS - PETTY CASH & EXPENSE OPTIMIZATION TEST SUITE           \n";
echo "========================================================================\n";

$createdCatId = 0;
$testVoucherIds = [];

try {
    $db = Database::getInstance()->getConnection();

    // -------------------------------------------------------------------------
    // TEST SECTION 1: Schema & Composite Index Verification
    // -------------------------------------------------------------------------
    echo "\n--- SECTION 1: Schema & Composite Index Verification ---\n";

    $catDelCol = Database::fetchOne("SHOW COLUMNS FROM expense_categories LIKE 'deleted_at'");
    assertTest("Step 1.1: expense_categories has deleted_at column", !empty($catDelCol));

    $catIdx = Database::fetchOne("SHOW INDEX FROM expense_categories WHERE Key_name = 'idx_expcat_del_status'");
    assertTest("Step 1.2: expense_categories has composite index idx_expcat_del_status", !empty($catIdx));

    $vDelCol = Database::fetchOne("SHOW COLUMNS FROM petty_cash_vouchers LIKE 'deleted_at'");
    assertTest("Step 1.3: petty_cash_vouchers has deleted_at column", !empty($vDelCol));

    $vStatusCol = Database::fetchOne("SHOW COLUMNS FROM petty_cash_vouchers LIKE 'status'");
    assertTest("Step 1.4: petty_cash_vouchers status ENUM includes draft", strpos($vStatusCol['Type'] ?? '', "'draft'") !== false);

    $vIdx1 = Database::fetchOne("SHOW INDEX FROM petty_cash_vouchers WHERE Key_name = 'idx_pcv_branch_date_status'");
    assertTest("Step 1.5: petty_cash_vouchers has idx_pcv_branch_date_status", !empty($vIdx1));

    $vIdx2 = Database::fetchOne("SHOW INDEX FROM petty_cash_vouchers WHERE Key_name = 'idx_pcv_del_status'");
    assertTest("Step 1.6: petty_cash_vouchers has idx_pcv_del_status", !empty($vIdx2));

    $rIdx = Database::fetchOne("SHOW INDEX FROM petty_cash_reimbursements WHERE Key_name = 'idx_pcr_branch_date_source'");
    assertTest("Step 1.7: petty_cash_reimbursements has idx_pcr_branch_date_source", !empty($rIdx));

    $aIdx = Database::fetchOne("SHOW INDEX FROM petty_cash_adjustments WHERE Key_name = 'idx_pca_branch_type_created'");
    assertTest("Step 1.8: petty_cash_adjustments has idx_pca_branch_type_created", !empty($aIdx));

    // -------------------------------------------------------------------------
    // TEST SECTION 2: Expense Category Lifecycle & Soft-Delete Trash Bin
    // -------------------------------------------------------------------------
    echo "\n--- SECTION 2: Expense Category Lifecycle & Soft-Delete Trash Bin ---\n";

    $testUser = Database::fetchOne("SELECT id FROM users LIMIT 1");
    $testUserId = (int)($testUser['id'] ?? 1);
    $testBranch = Database::fetchOne("SELECT id FROM branches LIMIT 1");
    $testBranchId = (int)($testBranch['id'] ?? 1);

    // Get an expense account (5xxx)
    $expAcc = Database::fetchOne("SELECT id, account_code, account_name FROM chart_of_accounts WHERE account_code LIKE '5%' AND status = 'active' LIMIT 1");
    $nonExpAcc = Database::fetchOne("SELECT id, account_code, account_name FROM chart_of_accounts WHERE account_code = '1010' LIMIT 1");

    assertTest("Step 2.1: Expense GL Account (5xxx) available for testing", !empty($expAcc));

    // Reject non-expense account
    if ($nonExpAcc) {
        $rejRes = PettyCash::saveCategory([
            'name' => 'Invalid Non-Expense Cat',
            'code' => 'TEST-NONEXP',
            'account_id' => (int)$nonExpAcc['id']
        ], $testUserId);
        assertTest("Step 2.2: saveCategory rejects non-expense COA account mapping", !$rejRes['success']);
    }

    // Create Category
    $uniqueCode = 'TEST-EXP-' . rand(1000, 9999);
    $catRes = PettyCash::saveCategory([
        'name' => "Test Category {$uniqueCode}",
        'code' => $uniqueCode,
        'account_id' => (int)$expAcc['id'],
        'description' => 'Test expense category for CLI suite'
    ], $testUserId);
    assertTest("Step 2.3: saveCategory successfully creates new expense category", $catRes['success'] && !empty($catRes['id']));
    $createdCatId = (int)($catRes['id'] ?? 0);

    // Case-insensitive duplicate check
    $dupRes = PettyCash::saveCategory([
        'name' => "test category {$uniqueCode}",
        'code' => strtolower($uniqueCode),
        'account_id' => (int)$expAcc['id']
    ], $testUserId);
    assertTest("Step 2.4: saveCategory enforces case-insensitive code/name uniqueness", !$dupRes['success']);

    // Soft-delete category
    $delRes = PettyCash::deleteCategory($createdCatId, $testUserId);
    assertTest("Step 2.5: deleteCategory soft-deletes category to Trash Bin", $delRes['success']);

    $chkDeleted = Database::fetchOne("SELECT deleted_at FROM expense_categories WHERE id = :id", [':id' => $createdCatId]);
    assertTest("Step 2.6: Category record retained in DB with deleted_at timestamp", !empty($chkDeleted['deleted_at']));

    $activeCats = PettyCash::getCategories(true);
    $foundInActive = array_filter($activeCats, fn($c) => (int)$c['id'] === $createdCatId);
    assertTest("Step 2.7: Soft-deleted category excluded from active getCategories()", empty($foundInActive));

    $trashedCats = PettyCash::getDeletedCategories();
    $foundInTrash = array_filter($trashedCats, fn($c) => (int)$c['id'] === $createdCatId);
    assertTest("Step 2.8: Soft-deleted category included in getDeletedCategories()", !empty($foundInTrash));

    // Restore category
    $resRes = PettyCash::restoreCategory($createdCatId, $testUserId);
    assertTest("Step 2.9: restoreCategory safely restores category from Trash Bin", $resRes['success']);

    $chkRestored = Database::fetchOne("SELECT deleted_at FROM expense_categories WHERE id = :id", [':id' => $createdCatId]);
    assertTest("Step 2.10: Category deleted_at cleared back to NULL", $chkRestored['deleted_at'] === null);

    // -------------------------------------------------------------------------
    // TEST SECTION 3: Petty Cash Balance & Fund Reimbursement Double-Entry
    // -------------------------------------------------------------------------
    echo "\n--- SECTION 3: Petty Cash Balance & Fund Reimbursement Double-Entry ---\n";

    $initialBalance = PettyCash::getPettyCashBalance($testBranchId);
    assertTest("Step 3.1: getPettyCashBalance returns numeric GL balance for branch", is_numeric($initialBalance));

    // Ensure branch has positive balance
    $bankAcc = Database::fetchOne("SELECT id, account_id, bank_name FROM bank_accounts WHERE status = 'active' AND deleted_at IS NULL LIMIT 1");
    if (!$bankAcc) {
        $bankAccRes = Banking::createBankAccount([
            'bank_name' => 'Commercial Bank',
            'account_name' => 'Petty Cash Funding Account',
            'account_number' => 'ACC-' . rand(1000, 9999),
            'branch_id' => $testBranchId,
            'opening_balance' => 500000.00
        ], $testUserId);
        $bankAcc = Banking::getBankAccount((int)$bankAccRes['bank_account_id']);
    }

    $topUpAmount = 15000.00;
    $reimbRes = PettyCash::reimburseFund([
        'source_type' => 'bank',
        'bank_account_id' => (int)$bankAcc['id'],
        'amount' => $topUpAmount,
        'reference_no' => 'REF-TEST-REIMB',
        'notes' => 'CLI automated top-up test'
    ], $testUserId, $testBranchId);

    assertTest("Step 3.2: reimburseFund from bank succeeds", $reimbRes['success']);
    $reimbId = (int)($reimbRes['reimbursement_id'] ?? 0);

    $reimbRow = Database::fetchOne("SELECT * FROM petty_cash_reimbursements WHERE id = :id", [':id' => $reimbId]);
    assertTest("Step 3.3: Reimbursement header record created with journal_entry_id", !empty($reimbRow['journal_entry_id']));

    // Check double entry
    $jeId = (int)$reimbRow['journal_entry_id'];
    $je = Database::fetchOne("SELECT * FROM journal_entries WHERE id = :id", [':id' => $jeId]);
    $jLines = Database::fetchAll("SELECT * FROM journal_lines WHERE journal_entry_id = :id", [':id' => $jeId]);

    $totalDr = array_sum(array_column($jLines, 'debit'));
    $totalCr = array_sum(array_column($jLines, 'credit'));
    assertTest("Step 3.4: Reimbursement GL journal double-entry is perfectly balanced (Dr = Cr)", abs($totalDr - $totalCr) < 0.001);
    assertTest("Step 3.5: Journal debit matches reimbursement amount (LKR {$topUpAmount})", abs($totalDr - $topUpAmount) < 0.001);

    $newBalance = PettyCash::getPettyCashBalance($testBranchId);
    assertTest("Step 3.6: Petty Cash GL balance increased by exactly LKR {$topUpAmount}", abs(($newBalance - $initialBalance) - $topUpAmount) < 0.001);

    // -------------------------------------------------------------------------
    // TEST SECTION 4: Expense Voucher Lifecycle & Balance Constraints
    // -------------------------------------------------------------------------
    echo "\n--- SECTION 4: Expense Voucher Lifecycle & Balance Constraints ---\n";

    // Negative balance prevention: Try to create voucher for more than available balance
    $excessiveAmount = $newBalance + 999999.00;
    $excessRes = PettyCash::createVoucher([
        'category_id' => $createdCatId,
        'amount' => $excessiveAmount,
        'payee' => 'Overdraft Test Payee',
        'description' => 'Should fail due to insufficient petty cash balance',
        'status' => 'posted'
    ], $testUserId, $testBranchId);
    assertTest("Step 4.1: createVoucher blocks expense exceeding petty cash balance (prevents negative balance)", !$excessRes['success']);

    // Create Draft Voucher
    $draftAmount = 2500.00;
    $draftRes = PettyCash::createVoucher([
        'category_id' => $createdCatId,
        'amount' => $draftAmount,
        'payee' => 'Draft Vendor',
        'description' => 'Draft voucher test',
        'status' => 'draft'
    ], $testUserId, $testBranchId);
    assertTest("Step 4.2: createVoucher creates draft voucher without posting GL entry", $draftRes['success'] && $draftRes['status'] === 'draft');
    $draftVoucherId = (int)($draftRes['voucher_id'] ?? 0);
    $testVoucherIds[] = $draftVoucherId;

    $draftRow = Database::fetchOne("SELECT * FROM petty_cash_vouchers WHERE id = :id", [':id' => $draftVoucherId]);
    assertTest("Step 4.3: Draft voucher has status 'draft' and NULL journal_entry_id", $draftRow['status'] === 'draft' && empty($draftRow['journal_entry_id']));

    // Discard/Cancel Draft Voucher
    $cancelRes = PettyCash::cancelVoucher($draftVoucherId, $testUserId, $testBranchId);
    assertTest("Step 4.4: cancelVoucher discards draft voucher via soft-delete", $cancelRes['success']);

    $cancelledRow = Database::fetchOne("SELECT deleted_at FROM petty_cash_vouchers WHERE id = :id", [':id' => $draftVoucherId]);
    assertTest("Step 4.5: Cancelled draft voucher has deleted_at populated", !empty($cancelledRow['deleted_at']));

    // Create Posted Voucher
    $voucherAmount = 3500.00;
    $vouchRes = PettyCash::createVoucher([
        'category_id' => $createdCatId,
        'amount' => $voucherAmount,
        'payee' => 'Test Vendor Lanka',
        'description' => 'Office tea and sugar supplies',
        'status' => 'posted'
    ], $testUserId, $testBranchId);
    assertTest("Step 4.6: createVoucher successfully posts valid expense voucher", $vouchRes['success'] && $vouchRes['status'] === 'posted');
    $voucherId = (int)($vouchRes['voucher_id'] ?? 0);
    $testVoucherIds[] = $voucherId;

    $vouchRow = Database::fetchOne("SELECT * FROM petty_cash_vouchers WHERE id = :id", [':id' => $voucherId]);
    assertTest("Step 4.7: Posted voucher record has valid journal_entry_id assigned", !empty($vouchRow['journal_entry_id']));

    $vJeId = (int)$vouchRow['journal_entry_id'];
    $vLines = Database::fetchAll("SELECT * FROM journal_lines WHERE journal_entry_id = :id", [':id' => $vJeId]);
    $vDr = array_sum(array_column($vLines, 'debit'));
    $vCr = array_sum(array_column($vLines, 'credit'));
    assertTest("Step 4.8: Voucher journal entry is balanced (Dr = Cr)", abs($vDr - $vCr) < 0.001);
    assertTest("Step 4.9: Voucher journal amount matches LKR {$voucherAmount}", abs($vDr - $voucherAmount) < 0.001);

    $balAfterVoucher = PettyCash::getPettyCashBalance($testBranchId);
    assertTest("Step 4.10: Petty Cash GL balance reduced by exactly voucher amount", abs(($newBalance - $balAfterVoucher) - $voucherAmount) < 0.001);

    // -------------------------------------------------------------------------
    // TEST SECTION 5: Voucher Reversal Workflow
    // -------------------------------------------------------------------------
    echo "\n--- SECTION 5: Voucher Reversal Workflow ---\n";

    // Attempt reversal without reason (should fail)
    $emptyReasonRes = PettyCash::reverseVoucher($voucherId, '', $testUserId, $testBranchId);
    assertTest("Step 5.1: reverseVoucher rejects empty reversal reason", !$emptyReasonRes['success']);

    // Reverse voucher with valid reason
    $revRes = PettyCash::reverseVoucher($voucherId, 'Incorrect receipt attached during audit', $testUserId, $testBranchId);
    assertTest("Step 5.2: reverseVoucher successfully reverses posted voucher", $revRes['success']);

    $revVouchRow = Database::fetchOne("SELECT * FROM petty_cash_vouchers WHERE id = :id", [':id' => $voucherId]);
    assertTest("Step 5.3: Voucher status updated to 'reversed'", $revVouchRow['status'] === 'reversed');
    assertTest("Step 5.4: Reversal journal entry ID linked", !empty($revVouchRow['reversal_journal_entry_id']));

    $revJeId = (int)$revVouchRow['reversal_journal_entry_id'];
    $revLines = Database::fetchAll("SELECT * FROM journal_lines WHERE journal_entry_id = :id", [':id' => $revJeId]);
    $revDr = array_sum(array_column($revLines, 'debit'));
    $revCr = array_sum(array_column($revLines, 'credit'));
    assertTest("Step 5.5: Reversal journal entry is balanced (Dr = Cr)", abs($revDr - $revCr) < 0.001);

    $balAfterReversal = PettyCash::getPettyCashBalance($testBranchId);
    assertTest("Step 5.6: Petty Cash GL balance restored to pre-voucher level", abs($balAfterReversal - $newBalance) < 0.001);

    // Double-reversal protection
    $doubleRevRes = PettyCash::reverseVoucher($voucherId, 'Second reversal attempt', $testUserId, $testBranchId);
    assertTest("Step 5.7: Double-reversal attempt strictly prevented", !$doubleRevRes['success']);

    // -------------------------------------------------------------------------
    // TEST SECTION 6: Manual Petty Cash Adjustment Workflow
    // -------------------------------------------------------------------------
    echo "\n--- SECTION 6: Manual Petty Cash Adjustment Workflow ---\n";

    $adjAmount = 1200.00;
    // Positive Adjustment
    $posAdjRes = PettyCash::adjustFund([
        'adjustment_type' => 'positive',
        'amount' => $adjAmount,
        'reason' => 'Physical count surplus correction'
    ], $testUserId, $testBranchId);
    assertTest("Step 6.1: adjustFund processes positive adjustment", $posAdjRes['success']);
    $posAdjId = (int)($posAdjRes['adjustment_id'] ?? 0);

    $posAdjRow = Database::fetchOne("SELECT * FROM petty_cash_adjustments WHERE id = :id", [':id' => $posAdjId]);
    assertTest("Step 6.2: Adjustment journal entry created and linked", !empty($posAdjRow['journal_entry_id']));

    $balAfterPosAdj = PettyCash::getPettyCashBalance($testBranchId);
    assertTest("Step 6.3: Balance increased by positive adjustment amount", abs(($balAfterPosAdj - $balAfterReversal) - $adjAmount) < 0.001);

    // Negative Adjustment
    $negAdjRes = PettyCash::adjustFund([
        'adjustment_type' => 'negative',
        'amount' => $adjAmount,
        'reason' => 'Audit count reconciliation offset'
    ], $testUserId, $testBranchId);
    assertTest("Step 6.4: adjustFund processes negative adjustment", $negAdjRes['success']);
    $negAdjId = (int)($negAdjRes['adjustment_id'] ?? 0);

    $balAfterNegAdj = PettyCash::getPettyCashBalance($testBranchId);
    assertTest("Step 6.5: Balance returned to original amount after negative adjustment", abs($balAfterNegAdj - $balAfterReversal) < 0.001);

    // -------------------------------------------------------------------------
    // TEST SECTION 7: Multi-Column Universal Search & SQL Pagination
    // -------------------------------------------------------------------------
    echo "\n--- SECTION 7: Multi-Column Universal Search & SQL Pagination ---\n";

    // Test search by voucher number
    $searchVRes = PettyCash::getVouchersList($testBranchId, ['search' => $vouchRow['voucher_no']]);
    assertTest("Step 7.1: getVouchersList matches voucher by voucher_no", !empty($searchVRes['items']));

    // Test search by payee
    $searchPRes = PettyCash::getVouchersList($testBranchId, ['search' => 'Vendor Lanka']);
    assertTest("Step 7.2: getVouchersList matches voucher by payee name", !empty($searchPRes['items']));

    // Test search by raw amount (3500)
    $searchAmtRes = PettyCash::getVouchersList($testBranchId, ['search' => '3500']);
    assertTest("Step 7.3: getVouchersList matches voucher by raw numeric amount", !empty($searchAmtRes['items']));

    // Test search by formatted amount (3,500.00)
    $searchFmtRes = PettyCash::getVouchersList($testBranchId, ['search' => '3,500.00']);
    assertTest("Step 7.4: getVouchersList matches voucher by formatted amount string", !empty($searchFmtRes['items']));

    // Test Account Transactions search by amount
    $searchTxRes = PettyCash::getAccountTransactions($testBranchId, '15,000.00');
    assertTest("Step 7.5: getAccountTransactions matches GL lines by formatted monetary amount", !empty($searchTxRes['transactions']));

    // Test pagination
    $pagRes = PettyCash::getVouchersList($testBranchId, [], 1, 25);
    assertTest("Step 7.6: getVouchersList pagination returns limit of 25", $pagRes['limit'] === 25);

    // -------------------------------------------------------------------------
    // TEST SECTION 8: Special User Presentation Masking
    // -------------------------------------------------------------------------
    echo "\n--- SECTION 8: Special User Presentation Masking ---\n";

    $factor = MaskingHelper::getFactor();
    $rawVal = 10000.00;
    $maskedVal = MaskingHelper::maskValue($rawVal, true);
    $expectedMasked = round($rawVal * $factor, 2);
    assertTest("Step 8.1: MaskingHelper masks monetary values according to configured factor", abs($maskedVal - $expectedMasked) < 0.01);

    // -------------------------------------------------------------------------
    // TEST SECTION 9: UI Design & View Compliance Checks
    // -------------------------------------------------------------------------
    echo "\n--- SECTION 9: UI Design & View Compliance Checks ---\n";

    $viewContent = file_get_contents(__DIR__ . '/../views/petty_cash/index.php');

    assertTest("Step 9.1: Fixed viewport calc(100vh - 110px) implemented in root container", strpos($viewContent, 'calc(100vh - 110px)') !== false);
    assertTest("Step 9.2: Internal scroll container prod-table-scroll implemented", strpos($viewContent, 'prod-table-scroll') !== false);
    assertTest("Step 9.3: Standard 48px row height specified in CSS", strpos($viewContent, 'height: 48px') !== false);
    assertTest("Step 9.4: Sticky headers position: sticky; and top: 0; specified", strpos($viewContent, 'position: sticky;') !== false && strpos($viewContent, 'top: 0;') !== false);
    assertTest("Step 9.5: Alternating zebra striping pattern implemented", strpos($viewContent, 'nth-child(even)') !== false && strpos($viewContent, 'rgba(248, 250, 252, 0.57)') !== false);
    assertTest("Step 9.6: Action controls mounted in curtiss-floating-header-right", strpos($viewContent, 'curtiss-floating-header-right') !== false);
    assertTest("Step 9.7: Headline only title with no subtitle tagline", strpos($viewContent, '<h1 class="inv-title">') !== false && strpos($viewContent, '<p class="inv-subtitle">') === false);
    assertTest("Step 9.8: CurtissNav.enablePageSearch bottom floating search registered", strpos($viewContent, 'CurtissNav.enablePageSearch') !== false);
    assertTest("Step 9.9: Action column cells excluded from realtime text search", strpos($viewContent, 'td:not(.col-action)') !== false);
    assertTest("Step 9.10: Double-submit protection setFormSubmitting implemented on all modal forms", strpos($viewContent, 'setFormSubmitting') !== false);
    assertTest("Step 9.11: Category Trash Bin and Restore button implemented", strpos($viewContent, 'btnCatViewTrash') !== false && strpos($viewContent, 'handleRestoreCategory') !== false);
    assertTest("Step 9.12: Standard pagination options (25, 50, 75, 100, 500) implemented", strpos($viewContent, '<option value="25" selected>25</option>') !== false && strpos($viewContent, '<option value="500">500</option>') !== false);

    // -------------------------------------------------------------------------
    // CLEANUP TEST FIXTURES
    // -------------------------------------------------------------------------
    if (!empty($testVoucherIds)) {
        foreach ($testVoucherIds as $vid) {
            $vRow = Database::fetchOne("SELECT journal_entry_id, reversal_journal_entry_id FROM petty_cash_vouchers WHERE id = :id", [':id' => $vid]);
            if (!empty($vRow['journal_entry_id'])) {
                Database::execute("DELETE FROM journal_lines WHERE journal_entry_id = :id", [':id' => $vRow['journal_entry_id']]);
                Database::execute("DELETE FROM journal_entries WHERE id = :id", [':id' => $vRow['journal_entry_id']]);
            }
            if (!empty($vRow['reversal_journal_entry_id'])) {
                Database::execute("DELETE FROM journal_lines WHERE journal_entry_id = :id", [':id' => $vRow['reversal_journal_entry_id']]);
                Database::execute("DELETE FROM journal_entries WHERE id = :id", [':id' => $vRow['reversal_journal_entry_id']]);
            }
        }
        $inIds = implode(',', array_map('intval', $testVoucherIds));
        Database::execute("DELETE FROM petty_cash_vouchers WHERE id IN ({$inIds})");
    }
    if (!empty($reimbId)) {
        $rRow = Database::fetchOne("SELECT journal_entry_id FROM petty_cash_reimbursements WHERE id = :id", [':id' => $reimbId]);
        if (!empty($rRow['journal_entry_id'])) {
            Database::execute("DELETE FROM journal_lines WHERE journal_entry_id = :id", [':id' => $rRow['journal_entry_id']]);
            Database::execute("DELETE FROM journal_entries WHERE id = :id", [':id' => $rRow['journal_entry_id']]);
        }
        Database::execute("DELETE FROM petty_cash_reimbursements WHERE id = :id", [':id' => $reimbId]);
    }
    if (!empty($posAdjId)) {
        $pRow = Database::fetchOne("SELECT journal_entry_id FROM petty_cash_adjustments WHERE id = :id", [':id' => $posAdjId]);
        if (!empty($pRow['journal_entry_id'])) {
            Database::execute("DELETE FROM journal_lines WHERE journal_entry_id = :id", [':id' => $pRow['journal_entry_id']]);
            Database::execute("DELETE FROM journal_entries WHERE id = :id", [':id' => $pRow['journal_entry_id']]);
        }
        Database::execute("DELETE FROM petty_cash_adjustments WHERE id = :id", [':id' => $posAdjId]);
    }
    if (!empty($negAdjId)) {
        $nRow = Database::fetchOne("SELECT journal_entry_id FROM petty_cash_adjustments WHERE id = :id", [':id' => $negAdjId]);
        if (!empty($nRow['journal_entry_id'])) {
            Database::execute("DELETE FROM journal_lines WHERE journal_entry_id = :id", [':id' => $nRow['journal_entry_id']]);
            Database::execute("DELETE FROM journal_entries WHERE id = :id", [':id' => $nRow['journal_entry_id']]);
        }
        Database::execute("DELETE FROM petty_cash_adjustments WHERE id = :id", [':id' => $negAdjId]);
    }
    if ($createdCatId > 0) {
        Database::execute("DELETE FROM expense_categories WHERE id = :id", [':id' => $createdCatId]);
    }

} catch (Throwable $e) {
    echo "\n[ERROR] Exception encountered during test execution: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n========================================================================\n";
echo "   TEST SUMMARY: Total: {$totalTests} | Passed: {$passedTests} | Failed: {$failedTests} \n";
echo "========================================================================\n";

if ($failedTests > 0) {
    exit(1);
}
exit(0);
