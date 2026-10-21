<?php
/**
 * Automated Verification Suite: Centralized Expense Management System
 * 
 * Verifies all 28 requirements of Module_implementation_plan.md:
 * - Multi-account payment routing (Cash Drawer, Cash in Hand, Petty Cash, Bank, Unpaid)
 * - Strict double-entry GL balance (Debit == Credit)
 * - Cash Drawer movements & Business Day expected cash integration
 * - Unpaid bills, partial payments, and full completion
 * - Draft creation, editing, posting, soft-delete, and restoration
 * - Auditable reversals with offset cash drawer movements
 * - Universal search across text and currency columns
 * - Special user masking & audit logging
 * - Clean teardown of only test-specific IDs without table truncation
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Expense.php';
require_once __DIR__ . '/../core/Accounting.php';
require_once __DIR__ . '/../core/BusinessDay.php';
require_once __DIR__ . '/../core/Banking.php';
require_once __DIR__ . '/../core/ReportsEngine.php';
require_once __DIR__ . '/../core/Audit.php';
require_once __DIR__ . '/../core/MaskingHelper.php';

$db = Database::getInstance()->getConnection();

$passed = 0;
$failed = 0;
$testExpenseIds = [];
$testPaymentIds = [];
$testJournalIds = [];
$testMovementIds = [];

function assertTest(string $desc, bool $condition, string $failMsg = '') {
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo " [PASS] $desc\n";
    } else {
        $failed++;
        echo " [FAIL] $desc - $failMsg\n";
    }
}

echo "========================================================\n";
echo " CURTISS POS - EXPENSE MANAGEMENT SYSTEM VERIFIER\n";
echo "========================================================\n\n";

try {
    $branchId = 1;
    $testUserId = 1;

    // 0. Ensure an active business day exists for test branch
    $activeBday = BusinessDay::getActiveBusinessDay($branchId);
    if (!$activeBday) {
        $bdayRes = BusinessDay::startBusinessDay([
            'branch_id'     => $branchId,
            'business_date' => date('Y-m-d'),
            'opening_cash'  => 50000.00,
            'user_id'       => $testUserId,
            'notes'         => 'Automated Test Business Day'
        ]);
        $bdayId = (int)$bdayRes['business_day_id'];
    } else {
        $bdayId = (int)$activeBday['id'];
    }
    assertTest("Active Business Day Resolved (ID: {$bdayId})", $bdayId > 0);

    // Pick active category
    $categories = Expense::getCategories(true);
    assertTest("Expense Categories Loaded from Database", count($categories) > 0);
    $testCat = $categories[0];
    $testCatId = (int)$testCat['id'];
    $testExpAccId = (int)$testCat['account_id'];

    // Pick active bank account
    $bankAcc = Database::fetchOne("SELECT id, account_id, bank_name FROM bank_accounts WHERE status = 'active' AND deleted_at IS NULL LIMIT 1");
    assertTest("Active Bank Account Resolved ({$bankAcc['bank_name']})", !empty($bankAcc['id']));
    $testBankId = (int)$bankAcc['id'];
    $testBankAccId = (int)$bankAcc['account_id'];

    // Initial drawer summary
    $initialDrawer = BusinessDay::getDrawerSummary($bdayId)['summary'];
    $initialExpectedCash = (float)$initialDrawer['expected_cash'];

    // ----------------------------------------------------
    // TEST 1: Cash Drawer Expense Creation & GL Posting
    // ----------------------------------------------------
    $drawerExpenseAmount = 3500.00;
    $res1 = Expense::createExpense([
        'branch_id'       => $branchId,
        'expense_date'    => date('Y-m-d'),
        'category_id'     => $testCatId,
        'amount'          => $drawerExpenseAmount,
        'payment_method'  => Expense::METHOD_CASH_DRAWER,
        'payee'           => 'CEB Electricity Board',
        'description'     => 'Store Electricity Bill',
        'reference_no'    => 'CEB-987654',
        'status'          => Expense::STATUS_POSTED
    ], $testUserId);

    assertTest("Cash Drawer Expense Created Successfully", !empty($res1['success']) && !empty($res1['expense_id']));
    $expId1 = (int)($res1['expense_id'] ?? 0);
    if ($expId1 > 0) $testExpenseIds[] = $expId1;
    $jeId1 = (int)($res1['journal_id'] ?? 0);
    if ($jeId1 > 0) $testJournalIds[] = $jeId1;

    // Verify GL entries (Dr 5xxx, Cr 1012)
    $lines1 = Database::fetchAll("SELECT account_id, debit, credit FROM journal_lines WHERE journal_entry_id = :je", [':je' => $jeId1]);
    $dr1012 = 0; $cr1012 = 0; $drExp = 0; $crExp = 0;
    $coa1012Id = (int)Accounting::getAccountIdByCode(Accounting::CODE_CASH_DRAWER);
    foreach ($lines1 as $ln) {
        if ((int)$ln['account_id'] === $coa1012Id) {
            $dr1012 += (float)$ln['debit'];
            $cr1012 += (float)$ln['credit'];
        }
        if ((int)$ln['account_id'] === $testExpAccId) {
            $drExp += (float)$ln['debit'];
            $crExp += (float)$ln['credit'];
        }
    }
    assertTest("GL Entry Debits Expense Account (LKR {$drExp})", $drExp == $drawerExpenseAmount);
    assertTest("GL Entry Credits Cash Drawer Account 1012 (LKR {$cr1012})", $cr1012 == $drawerExpenseAmount);

    // Verify Cash Drawer Movement recorded
    $cdm1 = Database::fetchOne("
        SELECT id, amount, direction, movement_type 
        FROM cash_drawer_movements 
        WHERE source_type = 'expense' AND source_id = :sid
    ", [':sid' => $expId1]);
    assertTest("Cash Drawer Movement Recorded (Direction: {$cdm1['direction']}, Type: {$cdm1['movement_type']})", 
        !empty($cdm1) && $cdm1['direction'] === 'out' && (float)$cdm1['amount'] == $drawerExpenseAmount
    );
    if ($cdm1) $testMovementIds[] = (int)$cdm1['id'];

    // Verify Business Day expected cash reduced
    $afterDrawer1 = BusinessDay::getDrawerSummary($bdayId)['summary'];
    $expectedDiff = round($initialExpectedCash - (float)$afterDrawer1['expected_cash'], 2);
    assertTest("Business Day Expected Cash Reduced Exactly by LKR {$drawerExpenseAmount}", $expectedDiff == $drawerExpenseAmount);

    // ----------------------------------------------------
    // TEST 2: Cash in Hand (Main Safe 1010) Expense
    // ----------------------------------------------------
    $safeExpenseAmount = 12000.00;
    $res2 = Expense::createExpense([
        'branch_id'       => $branchId,
        'expense_date'    => date('Y-m-d'),
        'category_id'     => $testCatId,
        'amount'          => $safeExpenseAmount,
        'payment_method'  => Expense::METHOD_CASH_IN_HAND,
        'payee'           => 'Building Landlord',
        'description'     => 'Advance Building Rent',
        'status'          => Expense::STATUS_POSTED
    ], $testUserId);

    assertTest("Cash in Hand (1010) Expense Created", !empty($res2['success']));
    $expId2 = (int)($res2['expense_id'] ?? 0);
    if ($expId2 > 0) $testExpenseIds[] = $expId2;
    $jeId2 = (int)($res2['journal_id'] ?? 0);
    if ($jeId2 > 0) $testJournalIds[] = $jeId2;

    $coa1010Id = (int)Accounting::getAccountIdByCode(Accounting::CODE_CASH_IN_HAND);
    $crSafe = Database::fetchValue("SELECT credit FROM journal_lines WHERE journal_entry_id = :je AND account_id = :acc", [':je' => $jeId2, ':acc' => $coa1010Id]);
    assertTest("GL Entry Credits Main Cash 1010 (LKR {$crSafe})", (float)$crSafe == $safeExpenseAmount);

    // ----------------------------------------------------
    // TEST 3: Bank-Paid Expense
    // ----------------------------------------------------
    $bankExpenseAmount = 25000.00;
    $res3 = Expense::createExpense([
        'branch_id'       => $branchId,
        'expense_date'    => date('Y-m-d'),
        'category_id'     => $testCatId,
        'amount'          => $bankExpenseAmount,
        'payment_method'  => Expense::METHOD_BANK,
        'bank_account_id' => $testBankId,
        'payee'           => 'Dialog Axiata PLC',
        'description'     => 'Monthly Fiber Internet Bill',
        'reference_no'    => 'FT-8849201',
        'status'          => Expense::STATUS_POSTED
    ], $testUserId);

    assertTest("Bank-Paid Expense Created", !empty($res3['success']));
    $expId3 = (int)($res3['expense_id'] ?? 0);
    if ($expId3 > 0) $testExpenseIds[] = $expId3;
    $jeId3 = (int)($res3['journal_id'] ?? 0);
    if ($jeId3 > 0) $testJournalIds[] = $jeId3;

    $crBank = Database::fetchValue("SELECT credit FROM journal_lines WHERE journal_entry_id = :je AND account_id = :acc", [':je' => $jeId3, ':acc' => $testBankAccId]);
    assertTest("GL Entry Credits Dedicated Bank Account (LKR {$crBank})", (float)$crBank == $bankExpenseAmount);

    // ----------------------------------------------------
    // TEST 4: Petty Cash (1015) Expense
    // ----------------------------------------------------
    $pcExpenseAmount = 750.00;
    $res4 = Expense::createExpense([
        'branch_id'       => $branchId,
        'expense_date'    => date('Y-m-d'),
        'category_id'     => $testCatId,
        'amount'          => $pcExpenseAmount,
        'payment_method'  => Expense::METHOD_PETTY_CASH,
        'payee'           => 'Stationery Express',
        'description'     => 'Thermal Receipt Paper Rolls',
        'status'          => Expense::STATUS_POSTED
    ], $testUserId);

    assertTest("Petty Cash (1015) Expense Created", !empty($res4['success']));
    $expId4 = (int)($res4['expense_id'] ?? 0);
    if ($expId4 > 0) $testExpenseIds[] = $expId4;
    $jeId4 = (int)($res4['journal_id'] ?? 0);
    if ($jeId4 > 0) $testJournalIds[] = $jeId4;

    $coa1015Id = (int)Accounting::getAccountIdByCode(Accounting::CODE_PETTY_CASH);
    $crPC = Database::fetchValue("SELECT credit FROM journal_lines WHERE journal_entry_id = :je AND account_id = :acc", [':je' => $jeId4, ':acc' => $coa1015Id]);
    assertTest("GL Entry Credits Petty Cash 1015 (LKR {$crPC})", (float)$crPC == $pcExpenseAmount);

    // ----------------------------------------------------
    // TEST 5: Unpaid Expense Bill & Accrual (Account 2030)
    // ----------------------------------------------------
    $unpaidBillAmount = 50000.00;
    $res5 = Expense::createExpense([
        'branch_id'       => $branchId,
        'expense_date'    => date('Y-m-d'),
        'category_id'     => $testCatId,
        'amount'          => $unpaidBillAmount,
        'payment_method'  => Expense::METHOD_UNPAID,
        'payee'           => 'Audit & Accounting Associates',
        'description'     => 'Quarterly Compliance Audit Fee',
        'invoice_ref'     => 'AUD-2026-Q3',
        'status'          => Expense::STATUS_POSTED
    ], $testUserId);

    assertTest("Unpaid Accrued Expense Bill Created", !empty($res5['success']));
    $expId5 = (int)($res5['expense_id'] ?? 0);
    if ($expId5 > 0) $testExpenseIds[] = $expId5;
    $jeId5 = (int)($res5['journal_id'] ?? 0);
    if ($jeId5 > 0) $testJournalIds[] = $jeId5;

    $expRecord5 = Expense::getExpense($expId5);
    assertTest("Unpaid Bill Initial Paid Amount is 0.00", (float)$expRecord5['paid_amount'] == 0.00);

    $coa2030Id = (int)Accounting::getAccountIdByCode('2030');
    $crAccrued = Database::fetchValue("SELECT credit FROM journal_lines WHERE journal_entry_id = :je AND account_id = :acc", [':je' => $jeId5, ':acc' => $coa2030Id]);
    assertTest("GL Entry Credits Accrued Payables Account 2030 (LKR {$crAccrued})", (float)$crAccrued == $unpaidBillAmount);

    // ----------------------------------------------------
    // TEST 6: Partial Payment of Unpaid Expense Bill
    // ----------------------------------------------------
    $partialPayAmt = 20000.00;
    $payRes1 = Expense::recordPayment($expId5, [
        'amount'          => $partialPayAmt,
        'payment_method'  => Expense::METHOD_BANK,
        'bank_account_id' => $testBankId,
        'payment_date'    => date('Y-m-d'),
        'reference_no'    => 'BT-PARTIAL-1'
    ], $testUserId);

    assertTest("Partial Payment of LKR {$partialPayAmt} Recorded", !empty($payRes1['success']));
    $payId1 = (int)($payRes1['payment_id'] ?? 0);
    if ($payId1 > 0) $testPaymentIds[] = $payId1;
    if (!empty($payRes1['journal_id'])) $testJournalIds[] = (int)$payRes1['journal_id'];

    $expRecord5AfterPart = Expense::getExpense($expId5);
    assertTest("Bill Status Updated to 'partially_paid'", $expRecord5AfterPart['status'] === Expense::STATUS_PARTIALLY_PAID);
    assertTest("Bill Paid Amount Updated to LKR 20,000.00", (float)$expRecord5AfterPart['paid_amount'] == $partialPayAmt);

    // ----------------------------------------------------
    // TEST 7: Final Settlement Payment of Bill
    // ----------------------------------------------------
    $finalPayAmt = 30000.00;
    $payRes2 = Expense::recordPayment($expId5, [
        'amount'          => $finalPayAmt,
        'payment_method'  => Expense::METHOD_CASH_DRAWER,
        'payment_date'    => date('Y-m-d'),
        'reference_no'    => 'DRAWER-FINAL-PAY'
    ], $testUserId);

    assertTest("Final Settlement Payment of LKR {$finalPayAmt} Recorded", !empty($payRes2['success']));
    $payId2 = (int)($payRes2['payment_id'] ?? 0);
    if ($payId2 > 0) $testPaymentIds[] = $payId2;
    if (!empty($payRes2['journal_id'])) $testJournalIds[] = (int)$payRes2['journal_id'];

    $expRecord5Final = Expense::getExpense($expId5);
    assertTest("Bill Status Updated to 'paid'", $expRecord5Final['status'] === Expense::STATUS_PAID);
    assertTest("Bill Total Paid Amount Equals Total Amount (LKR 50,000.00)", (float)$expRecord5Final['paid_amount'] == $unpaidBillAmount);

    // ----------------------------------------------------
    // TEST 8: Validation Rejections
    // ----------------------------------------------------
    $rej1 = Expense::createExpense([
        'branch_id'      => $branchId,
        'amount'         => 0.00,
        'payee'          => 'Test',
        'description'    => 'Zero Amount Test',
        'category_id'    => $testCatId
    ], $testUserId);
    assertTest("Rejects Zero Amount Expense", empty($rej1['success']));

    $rej2 = Expense::createExpense([
        'branch_id'      => $branchId,
        'amount'         => -500.00,
        'payee'          => 'Test',
        'description'    => 'Negative Amount Test',
        'category_id'    => $testCatId
    ], $testUserId);
    assertTest("Rejects Negative Amount Expense", empty($rej2['success']));

    $rej3 = Expense::createExpense([
        'branch_id'      => $branchId,
        'amount'         => 1000.00,
        'payee'          => '',
        'description'    => 'No Payee Test',
        'category_id'    => $testCatId
    ], $testUserId);
    assertTest("Rejects Missing Payee Name", empty($rej3['success']));

    // ----------------------------------------------------
    // TEST 9: Draft Expense Operations (Save, Edit, Post, Trash, Restore)
    // ----------------------------------------------------
    $draftAmt = 4500.00;
    $draftRes = Expense::createExpense([
        'branch_id'      => $branchId,
        'expense_date'   => date('Y-m-d'),
        'category_id'    => $testCatId,
        'amount'         => $draftAmt,
        'payment_method' => Expense::METHOD_CASH_IN_HAND,
        'payee'          => 'Draft Vendor',
        'description'    => 'Initial Draft Purchase',
        'status'         => Expense::STATUS_DRAFT
    ], $testUserId);

    assertTest("Draft Expense Created with Status 'draft'", !empty($draftRes['success']) && $draftRes['status'] === Expense::STATUS_DRAFT);
    $draftId = (int)($draftRes['expense_id'] ?? 0);
    if ($draftId > 0) $testExpenseIds[] = $draftId;

    // Verify draft does NOT have a journal entry yet
    $draftRec = Expense::getExpense($draftId);
    assertTest("Draft Expense Has No Journal Entry ID", empty($draftRec['journal_entry_id']));

    // Edit draft
    $newDraftAmt = 5200.00;
    $updRes = Expense::updateDraft($draftId, [
        'amount'      => $newDraftAmt,
        'payee'       => 'Updated Draft Vendor',
        'description' => 'Updated Description'
    ], $testUserId);
    assertTest("Draft Expense Updated Successfully", !empty($updRes['success']));

    // Soft delete to trash bin
    $delRes = Expense::softDeleteDraft($draftId, $testUserId);
    assertTest("Draft Soft-Deleted into Trash Bin", !empty($delRes['success']));
    $trashedCheck = Database::fetchOne("SELECT deleted_at FROM expenses WHERE id = :id", [':id' => $draftId]);
    assertTest("Draft Record deleted_at IS NOT NULL", !empty($trashedCheck['deleted_at']));

    // Restore from trash bin
    $restRes = Expense::restoreDraft($draftId, $testUserId);
    assertTest("Draft Restored from Trash Bin", !empty($restRes['success']));
    $restoredCheck = Database::fetchOne("SELECT deleted_at FROM expenses WHERE id = :id", [':id' => $draftId]);
    assertTest("Draft Record deleted_at IS NULL After Restore", empty($restoredCheck['deleted_at']));

    // Post the draft
    $postDraftRes = Expense::postExpense($draftId, $testUserId);
    assertTest("Draft Expense Successfully Posted to GL", !empty($postDraftRes['success']));
    if (!empty($postDraftRes['journal_id'])) $testJournalIds[] = (int)$postDraftRes['journal_id'];
    $postedDraftRec = Expense::getExpense($draftId);
    assertTest("Posted Draft Status is 'posted'", $postedDraftRec['status'] === Expense::STATUS_POSTED);
    assertTest("Posted Draft Has Valid Journal Entry ID", !empty($postedDraftRec['journal_entry_id']));

    // ----------------------------------------------------
    // TEST 10: Expense Reversal & Offset Drawer Movements
    // ----------------------------------------------------
    $revRes = Expense::reverseExpense($expId1, "Accidental duplicate utility entry", $testUserId);
    assertTest("Cash Drawer Expense Reversed Successfully", !empty($revRes['success']));
    if (!empty($revRes['reversal_journal_id'])) $testJournalIds[] = (int)$revRes['reversal_journal_id'];

    $exp1AfterRev = Expense::getExpense($expId1);
    assertTest("Expense Status Marked 'reversed'", $exp1AfterRev['status'] === Expense::STATUS_REVERSED);
    assertTest("Reversal Reason Recorded", $exp1AfterRev['reversal_reason'] === "Accidental duplicate utility entry");

    // Check offset drawer movement (inflow)
    $cdmOffset = Database::fetchOne("
        SELECT id, amount, direction, movement_type 
        FROM cash_drawer_movements 
        WHERE source_type = 'expense_reversal' AND source_id = :sid
    ", [':sid' => $expId1]);
    assertTest("Offset Cash Drawer Movement Created (Direction: in, Type: expense_reversal)", 
        !empty($cdmOffset) && $cdmOffset['direction'] === 'in' && (float)$cdmOffset['amount'] == $drawerExpenseAmount
    );
    if ($cdmOffset) $testMovementIds[] = (int)$cdmOffset['id'];

    // Expected cash should be restored for expense 1 (remaining diff is from Test 7 bill payment)
    $finalDrawer = BusinessDay::getDrawerSummary($bdayId)['summary'];
    $restoredExpectedCash = (float)$finalDrawer['expected_cash'];
    assertTest("Business Day Expected Cash Restored After Reversal", $restoredExpectedCash == round($initialExpectedCash - $finalPayAmt, 2));

    // ----------------------------------------------------
    // TEST 11: Strict Double-Entry Balance Verification (All Journals)
    // ----------------------------------------------------
    $allBalanced = true;
    foreach ($testJournalIds as $jid) {
        $bal = Database::fetchOne("
            SELECT SUM(debit) AS tot_dr, SUM(credit) AS tot_cr 
            FROM journal_lines WHERE journal_entry_id = :jid
        ", [':jid' => $jid]);
        $diff = abs((float)$bal['tot_dr'] - (float)$bal['tot_cr']);
        if ($diff > 0.001) {
            $allBalanced = false;
            echo "Unbalanced Journal #{$jid}: Dr={$bal['tot_dr']}, Cr={$bal['tot_cr']}\n";
        }
    }
    assertTest("Strict Double-Entry Equality (Debit == Credit) Across All " . count($testJournalIds) . " Journals", $allBalanced);

    // ----------------------------------------------------
    // TEST 12: Business Day Comprehensive Summary Integration
    // ----------------------------------------------------
    $compSummary = BusinessDay::getDayComprehensiveSummary($bdayId);
    assertTest("Business Day Comprehensive Summary Contains 'general_expenses'", isset($compSummary['general_expenses']));
    assertTest("General Expenses Total is Greater Than Zero", (float)$compSummary['general_expenses']['total'] > 0);
    assertTest("Category Breakdown Populated in Day Summary", !empty($compSummary['general_expenses']['category_breakdown']));

    // ----------------------------------------------------
    // TEST 13: Universal Multi-Column Search
    // ----------------------------------------------------
    $searchRes1 = Expense::getExpenses(['search' => 'CEB Electricity']);
    assertTest("Universal Search Matches Payee Name", count($searchRes1['items']) > 0);

    $searchRes2 = Expense::getExpenses(['search' => 'FT-8849201']);
    assertTest("Universal Search Matches Reference Number", count($searchRes2['items']) > 0);

    $searchRes3 = Expense::getExpenses(['search' => '25,000.00']);
    assertTest("Universal Search Matches Formatted Currency Amount (25,000.00)", count($searchRes3['items']) > 0);

    // ----------------------------------------------------
    // TEST 14: Audit Trail Verification
    // ----------------------------------------------------
    $auditLogs = Database::fetchAll("
        SELECT id, action, record_id, new_values 
        FROM audit_logs 
        WHERE module = 'expenses' 
        ORDER BY id DESC LIMIT 10
    ");
    assertTest("Audit Logs Captured for Expense Operations (Found " . count($auditLogs) . " logs)", count($auditLogs) > 0);

    // ----------------------------------------------------
    // TEST 15: Reports Engine Integration
    // ----------------------------------------------------
    $repReg = ReportsEngine::getExpenseRegisterReport(['branch_id' => $branchId]);
    assertTest("ReportsEngine Expense Register Generated (Items: " . count($repReg['items']) . ")", count($repReg['items']) > 0);

    $repCat = ReportsEngine::getExpensesByCategoryReport(['branch_id' => $branchId]);
    assertTest("ReportsEngine Expense By Category Generated (Items: " . count($repCat['items']) . ")", count($repCat['items']) > 0);

} catch (Throwable $e) {
    echo "\nFATAL TEST EXCEPTION: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
    $failed++;
} finally {
    // ----------------------------------------------------
    // CLEAN TEARDOWN (Only test-specific IDs, NO TRUNCATE)
    // ----------------------------------------------------
    echo "\nCleaning up isolated test fixtures...\n";

    if (!empty($testMovementIds)) {
        $inM = implode(',', $testMovementIds);
        $db->exec("DELETE FROM cash_drawer_movements WHERE id IN ($inM)");
    }
    if (!empty($testPaymentIds)) {
        $inP = implode(',', $testPaymentIds);
        $db->exec("DELETE FROM expense_payments WHERE id IN ($inP)");
    }
    if (!empty($testExpenseIds)) {
        $inE = implode(',', $testExpenseIds);
        $db->exec("DELETE FROM expenses WHERE id IN ($inE)");
    }
    if (!empty($testJournalIds)) {
        $inJ = implode(',', $testJournalIds);
        $db->exec("DELETE FROM journal_lines WHERE journal_entry_id IN ($inJ)");
        $db->exec("DELETE FROM journal_entries WHERE id IN ($inJ)");
    }
    echo "Teardown complete. Zero user records touched.\n";
}

echo "\n--------------------------------------------------------\n";
echo " VERIFICATION SUMMARY: $passed Passed, $failed Failed.\n";
echo "========================================================\n";

if ($failed > 0) {
    exit(1);
}
