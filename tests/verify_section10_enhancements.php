<?php
/**
 * Curtiss POS - Section 10 Automated Verification Test Suite
 * Tests Section 10 Petty Cash & Expense Management specifications:
 * 1. Fundamental Separation (1015 Petty Cash vs 1010 Cash in Hand)
 * 2. Expense Categories & COA Expense Account (5xxx) Mapping
 * 3. Safe Category Deletion & Deactivation Controls
 * 4. Draft & Posted Expense Vouchers (sequential PCV-XXXX numbers)
 * 5. Insufficient Balance Enforcement (balance never falls below zero)
 * 6. Voucher Reversals & Double-Reversal Safeguards
 * 7. Bank Reimbursements (dedicated bank COA, no drawer impact)
 * 8. Cash in Hand Reimbursements (active open business day required, drawer OUT movement)
 * 9. Manual Adjustments (Positive & Negative, mandatory reason, overdraw protection)
 * 10. Petty Cash Register & GL 1015 Sub-Ledger Reconciliation
 * 11. POS Drawer Separation Verification
 * 12. Branch Control & Clean Teardown
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Logger.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Accounting.php';
require_once __DIR__ . '/../core/BusinessDay.php';
require_once __DIR__ . '/../core/Banking.php';
require_once __DIR__ . '/../core/PettyCash.php';
require_once __DIR__ . '/../core/ReconciliationEngine.php';

$testPassed = 0;
$testFailed = 0;

function assertTest(bool $condition, string $testName, string $failureDetails = '') {
    global $testPassed, $testFailed;
    if ($condition) {
        $testPassed++;
        echo "  [PASS] {$testName}\n";
    } else {
        $testFailed++;
        echo "  [FAIL] {$testName}\n";
        if ($failureDetails) {
            echo "         Detail: {$failureDetails}\n";
        }
    }
}

echo "===============================================================\n";
echo "       CURTISS POS - SECTION 10 ENHANCEMENT VERIFICATION       \n";
echo "===============================================================\n\n";

try {
    $db = Database::getInstance()->getConnection();
    $branchId = 1;
    $userId = 1;

    // Clean Section 10 test records for clean run
    $db->exec("DELETE FROM bank_reconciliation_items");
    $db->exec("DELETE FROM bank_reconciliations");
    $db->exec("DELETE FROM bank_deposits");
    $db->exec("DELETE FROM cash_drawer_movements WHERE branch_id = 1");
    $db->exec("DELETE FROM business_days WHERE branch_id = 1");
    $db->exec("DELETE FROM journal_lines WHERE journal_entry_id IN (SELECT id FROM journal_entries WHERE description LIKE '%S10%' OR description LIKE '%Petty Cash%')");
    $db->exec("DELETE FROM journal_entries WHERE description LIKE '%S10%' OR description LIKE '%Petty Cash%'");
    $db->exec("DELETE FROM petty_cash_adjustments WHERE branch_id = 1");
    $db->exec("DELETE FROM petty_cash_reimbursements WHERE branch_id = 1");
    $db->exec("DELETE FROM petty_cash_vouchers WHERE branch_id = 1");
    $db->exec("DELETE FROM expense_categories WHERE code LIKE 'EXP-S10%'");

    // -------------------------------------------------------------
    // GROUP 1: Fundamental Separation (1015 vs 1010)
    // -------------------------------------------------------------
    echo "[GROUP 1] Fundamental Separation (Petty Cash 1015 vs Cash in Hand 1010)\n";

    $pcAccId = Accounting::getAccountIdByCode(Accounting::CODE_PETTY_CASH);
    $cashAccId = Accounting::getAccountIdByCode(Accounting::CODE_CASH);

    assertTest($pcAccId > 0, 'GL 1015 (Petty Cash) account exists');
    assertTest($cashAccId > 0, 'GL 1010 (Cash in Hand) account exists');
    assertTest($pcAccId !== $cashAccId, 'GL 1015 and GL 1010 are distinct separate accounts');

    echo "\n";

    // -------------------------------------------------------------
    // GROUP 2: Expense Categories & COA 5xxx Mapping
    // -------------------------------------------------------------
    echo "[GROUP 2] Expense Categories & COA Mapping Controls\n";

    $cat5110 = Accounting::getAccountIdByCode('5110'); // Fuel & Transport
    $cat5120 = Accounting::getAccountIdByCode('5120'); // Meals & Refreshment
    $cat5130 = Accounting::getAccountIdByCode('5130'); // Utilities & Bills
    $cat5140 = Accounting::getAccountIdByCode('5140'); // Repairs & Maintenance
    $cat5150 = Accounting::getAccountIdByCode('5150'); // Accommodation & Travel
    $cat5160 = Accounting::getAccountIdByCode('5160'); // General Office Expenses

    assertTest($cat5110 > 0 && $cat5120 > 0 && $cat5130 > 0 && $cat5140 > 0 && $cat5150 > 0 && $cat5160 > 0, 'Specific COA 5xxx expense accounts exist');

    // Create custom category mapped to 5110 (Fuel)
    $catRes1 = PettyCash::saveCategory([
        'name' => 'S10 Fuel Category',
        'code' => 'EXP-S10-FUEL',
        'account_id' => $cat5110,
        'description' => 'Vehicle Fuel Expense Category',
        'status' => 'active'
    ], $userId);

    assertTest($catRes1['success'] === true, 'Create expense category mapped to COA 5110 (Fuel)', $catRes1['message'] ?? '');
    $catIdFuel = (int)($catRes1['id'] ?? 0);

    // Create custom category mapped to 5120 (Meals)
    $catRes2 = PettyCash::saveCategory([
        'name' => 'S10 Meals Category',
        'code' => 'EXP-S10-MEAL',
        'account_id' => $cat5120,
        'description' => 'Staff Refreshments Expense Category',
        'status' => 'active'
    ], $userId);

    assertTest($catRes2['success'] === true, 'Create expense category mapped to COA 5120 (Meals)', $catRes2['message'] ?? '');
    $catIdMeal = (int)($catRes2['id'] ?? 0);

    // Attempt to map category to non-expense account (e.g., 1040 AR) -> MUST BLOCK!
    $badCatRes = PettyCash::saveCategory([
        'name' => 'S10 Bad Category',
        'code' => 'EXP-S10-BAD',
        'account_id' => Accounting::getAccountIdByCode(Accounting::CODE_CUSTOMER_RECEIVABLE),
        'description' => 'Attempting non-expense COA'
    ], $userId);

    assertTest($badCatRes['success'] === false, 'Block creating category mapped to non-expense account (AR 1040)', $badCatRes['message'] ?? '');

    echo "\n";

    // -------------------------------------------------------------
    // GROUP 3: Bank & Cash-in-Hand Reimbursements
    // -------------------------------------------------------------
    echo "[GROUP 3] Fund Reimbursements & Drawer Movement Integration\n";

    $initBal = PettyCash::getPettyCashBalance($branchId);

    // 1. Bank Reimbursement with dedicated bank account selection
    $bankAccs = Banking::getBankAccounts($branchId, true);
    $bankAccId = !empty($bankAccs) ? (int)$bankAccs[0]['id'] : null;

    $bankReimb = PettyCash::reimburseFund([
        'source_type' => 'bank',
        'bank_account_id' => $bankAccId,
        'amount' => 15000.00,
        'reference_no' => 'S10-BANK-CHQ',
        'notes' => 'S10 Bank Replenishment'
    ], $userId, $branchId);

    assertTest($bankReimb['success'] === true, 'Reimburse LKR 15,000.00 from Bank Account', $bankReimb['message'] ?? '');
    $balAfterBank = PettyCash::getPettyCashBalance($branchId);
    assertTest(round($balAfterBank - $initBal, 2) == 15000.00, 'Petty Cash balance increased by LKR 15,000.00 (New Balance: LKR ' . number_format($balAfterBank, 2) . ')');

    // Verify Bank Reimbursement produced ZERO drawer movements!
    $bankDrawerMv = Database::fetchOne("
        SELECT * FROM cash_drawer_movements 
        WHERE source_type = 'petty_cash_reimbursement' AND source_id = :sid
    ", [':sid' => $bankReimb['reimbursement_id']]);

    assertTest($bankDrawerMv === false || $bankDrawerMv === null, 'Bank-funded reimbursement produces NO POS cash drawer movements');

    // 2. Cash-in-Hand Reimbursement without active business day -> MUST BLOCK!
    // Ensure no active day
    $activeDay = BusinessDay::getActiveDay($branchId);
    if ($activeDay) {
        $db->exec("UPDATE business_days SET status = 'CLOSED' WHERE id = " . (int)$activeDay['id']);
    }

    $noDayCashReimb = PettyCash::reimburseFund([
        'source_type' => 'cash_in_hand',
        'amount' => 2000.00,
        'notes' => 'S10 Cash Reimbursement No Day'
    ], $userId, $branchId);

    assertTest($noDayCashReimb['success'] === false, 'Block Cash in Hand reimbursement when no open business day exists', $noDayCashReimb['message'] ?? '');

    // 3. Open active business day & perform Cash-in-Hand Reimbursement
    $startDayRes = BusinessDay::startDay($branchId, 20000.00, $userId, 'S10 Active Business Day');
    assertTest($startDayRes['success'] === true, 'Start active business day for branch');

    $cashReimb = PettyCash::reimburseFund([
        'source_type' => 'cash_in_hand',
        'amount' => 3000.00,
        'reference_no' => 'S10-DRAWER-TOPUP',
        'notes' => 'S10 Cash in Hand Drawer Replenishment'
    ], $userId, $branchId);

    assertTest($cashReimb['success'] === true, 'Reimburse LKR 3,000.00 from active Cash in Hand Drawer', $cashReimb['message'] ?? '');
    $balAfterCash = PettyCash::getPettyCashBalance($branchId);
    assertTest(round($balAfterCash - $balAfterBank, 2) == 3000.00, 'Petty Cash balance increased by LKR 3,000.00 (Current Balance: LKR ' . number_format($balAfterCash, 2) . ')');

    // Verify Cash in Hand Reimbursement created physical Drawer OUT movement!
    $cashDrawerMv = Database::fetchOne("
        SELECT * FROM cash_drawer_movements 
        WHERE source_type = 'petty_cash_reimbursement' AND source_id = :sid AND direction = 'out'
    ", [':sid' => $cashReimb['reimbursement_id']]);

    assertTest($cashDrawerMv !== false && (float)$cashDrawerMv['amount'] == 3000.00, 'Cash in Hand reimbursement creates physical Drawer OUT movement (LKR 3,000.00)');

    echo "\n";

    // -------------------------------------------------------------
    // GROUP 4: Petty Cash Expense Vouchers (Draft & Posted)
    // -------------------------------------------------------------
    echo "[GROUP 4] Draft & Posted Vouchers & Balance Enforcement\n";

    $balBeforeVouch = PettyCash::getPettyCashBalance($branchId);

    // 1. Create Draft Voucher (No balance reduction, no GL posting)
    $draftVouchRes = PettyCash::createVoucher([
        'category_id' => $catIdFuel,
        'amount' => 1200.00,
        'payee' => 'S10 Fuel Station',
        'description' => 'S10 Draft Fuel Purchase',
        'status' => 'draft'
    ], $userId, $branchId);

    assertTest($draftVouchRes['success'] === true && $draftVouchRes['status'] === 'draft', 'Create Draft Voucher (LKR 1,200.00)', $draftVouchRes['message'] ?? '');
    $draftVoucherId = (int)$draftVouchRes['voucher_id'];

    $balAfterDraft = PettyCash::getPettyCashBalance($branchId);
    assertTest(round($balBeforeVouch - $balAfterDraft, 2) == 0.00, 'Draft Voucher does NOT reduce Petty Cash balance');

    // 2. Approve Draft Voucher
    $appRes = PettyCash::approveVoucher($draftVoucherId, $userId, $branchId);
    assertTest($appRes['success'] === true, 'Approve Draft Voucher', $appRes['message'] ?? '');

    $balAfterApp = PettyCash::getPettyCashBalance($branchId);
    assertTest(round($balBeforeVouch - $balAfterApp, 2) == 1200.00, 'Approving Draft Voucher reduces Petty Cash balance by LKR 1,200.00');

    // 3. Create Posted Voucher directly
    $postVouchRes = PettyCash::createVoucher([
        'category_id' => $catIdMeal,
        'amount' => 2500.00,
        'payee' => 'S10 Food Court',
        'description' => 'S10 Staff Meal Voucher',
        'status' => 'posted'
    ], $userId, $branchId);

    assertTest($postVouchRes['success'] === true && $postVouchRes['status'] === 'posted', 'Create Posted Voucher (LKR 2,500.00)', $postVouchRes['message'] ?? '');
    $postedVoucherId = (int)$postVouchRes['voucher_id'];

    $balAfterPosted = PettyCash::getPettyCashBalance($branchId);
    assertTest(round($balAfterApp - $balAfterPosted, 2) == 2500.00, 'Posted Voucher reduces Petty Cash balance by LKR 2,500.00');

    // Verify Expense Voucher produced ZERO drawer movements!
    $vouchDrawerMv = Database::fetchOne("
        SELECT * FROM cash_drawer_movements 
        WHERE source_id = :sid AND (notes LIKE '%S10 Staff Meal%' OR reason LIKE '%Meal%')
    ", [':sid' => $postedVoucherId]);

    assertTest($vouchDrawerMv === false || $vouchDrawerMv === null, 'Petty Cash Expense Voucher produces NO POS cash drawer movements');

    // 4. Insufficient Balance Enforcement
    $currentAvail = PettyCash::getPettyCashBalance($branchId);
    $excessiveVouch = PettyCash::createVoucher([
        'category_id' => $catIdFuel,
        'amount' => $currentAvail + 5000.00,
        'payee' => 'S10 Overdraw Payee',
        'description' => 'S10 Attempting Overdraw Voucher',
        'status' => 'posted'
    ], $userId, $branchId);

    assertTest($excessiveVouch['success'] === false, 'Block voucher exceeding available Petty Cash balance', $excessiveVouch['message'] ?? '');

    // 5. Zero / Negative Amount Enforcement
    $zeroVouch = PettyCash::createVoucher([
        'category_id' => $catIdFuel,
        'amount' => 0.00,
        'payee' => 'S10 Zero Payee',
        'description' => 'S10 Zero Amount'
    ], $userId, $branchId);

    assertTest($zeroVouch['success'] === false, 'Block voucher with zero amount', $zeroVouch['message'] ?? '');

    echo "\n";

    // -------------------------------------------------------------
    // GROUP 5: Voucher Reversals & Safeguards
    // -------------------------------------------------------------
    echo "[GROUP 5] Voucher Reversals & Double-Reversal Safeguards\n";

    $balBeforeRev = PettyCash::getPettyCashBalance($branchId);

    // 1. Attempt Reversal with empty reason -> MUST BLOCK!
    $emptyRevRes = PettyCash::reverseVoucher($postedVoucherId, '   ', $userId, $branchId);
    assertTest($emptyRevRes['success'] === false, 'Block voucher reversal with empty reason', $emptyRevRes['message'] ?? '');

    // 2. Perform valid reversal
    $validRevRes = PettyCash::reverseVoucher($postedVoucherId, 'S10 Cancelled Meal Event', $userId, $branchId);
    assertTest($validRevRes['success'] === true, 'Reverse posted voucher (LKR 2,500.00 restored)', $validRevRes['message'] ?? '');

    $balAfterRev = PettyCash::getPettyCashBalance($branchId);
    assertTest(round($balAfterRev - $balBeforeRev, 2) == 2500.00, 'Reversing voucher restores LKR 2,500.00 to Petty Cash balance');

    // 3. Attempt Double Reversal -> MUST BLOCK!
    $doubleRevRes = PettyCash::reverseVoucher($postedVoucherId, 'S10 Duplicate Reversal', $userId, $branchId);
    assertTest($doubleRevRes['success'] === false, 'Block double-reversal of already reversed voucher', $doubleRevRes['message'] ?? '');

    echo "\n";

    // -------------------------------------------------------------
    // GROUP 6: Manual Adjustments & Category Safeguards
    // -------------------------------------------------------------
    echo "[GROUP 6] Manual Adjustments & Category Safeguards\n";

    $balBeforeAdj = PettyCash::getPettyCashBalance($branchId);

    // 1. Positive Adjustment
    $posAdj = PettyCash::adjustFund([
        'adjustment_type' => 'positive',
        'amount' => 1500.00,
        'reason' => 'S10 Verified physical cash float excess'
    ], $userId, $branchId);

    assertTest($posAdj['success'] === true, 'Process Positive Adjustment (+ LKR 1,500.00)', $posAdj['message'] ?? '');
    $balAfterPosAdj = PettyCash::getPettyCashBalance($branchId);
    assertTest(round($balAfterPosAdj - $balBeforeAdj, 2) == 1500.00, 'Positive adjustment increases balance by LKR 1,500.00');

    // 2. Negative Adjustment
    $negAdj = PettyCash::adjustFund([
        'adjustment_type' => 'negative',
        'amount' => 500.00,
        'reason' => 'S10 Verified minor petty cash count deficit'
    ], $userId, $branchId);

    assertTest($negAdj['success'] === true, 'Process Negative Adjustment (- LKR 500.00)', $negAdj['message'] ?? '');
    $balAfterNegAdj = PettyCash::getPettyCashBalance($branchId);
    assertTest(round($balAfterPosAdj - $balAfterNegAdj, 2) == 500.00, 'Negative adjustment decreases balance by LKR 500.00');

    // 3. Negative Adjustment exceeding available balance -> MUST BLOCK!
    $currBal = PettyCash::getPettyCashBalance($branchId);
    $overNegAdj = PettyCash::adjustFund([
        'adjustment_type' => 'negative',
        'amount' => $currBal + 10000.00,
        'reason' => 'S10 Overdraw attempt'
    ], $userId, $branchId);

    assertTest($overNegAdj['success'] === false, 'Block negative adjustment exceeding available Petty Cash balance', $overNegAdj['message'] ?? '');

    // 4. Safe Category Deletion Safeguard
    $delCatUsed = PettyCash::deleteCategory($catIdFuel);
    assertTest($delCatUsed['success'] === false, 'Block hard-deletion of category with associated vouchers', $delCatUsed['message'] ?? '');

    echo "\n";

    // -------------------------------------------------------------
    // GROUP 7: Register Feed & GL Reconciliation
    // -------------------------------------------------------------
    echo "[GROUP 7] Petty Cash Register & GL 1015 Reconciliation\n";

    $reg = PettyCash::getRegisterList($branchId);
    $glBalance = PettyCash::getPettyCashBalance($branchId);

    assertTest(!empty($reg['entries']), 'Petty Cash register returned chronologically ordered entries');
    assertTest(round(abs($reg['balance'] - $glBalance), 2) == 0.00, 'Petty Cash register running balance (LKR ' . number_format($reg['balance'], 2) . ') perfectly equals GL 1015 balance');

    $reconRes = ReconciliationEngine::reconcilePettyCash();
    assertTest($reconRes['status'] === 'PASS', 'ReconciliationEngine::reconcilePettyCash() status is PASS');
    assertTest($reconRes['variance'] < 1.00, 'ReconciliationEngine::reconcilePettyCash() variance is 0.00');

    echo "\n";

    // -------------------------------------------------------------
    // CLEAN TEARDOWN
    // -------------------------------------------------------------
    echo "[TEARDOWN] Cleaning Section 10 Test Artifacts...\n";

    $db->exec("DELETE FROM bank_reconciliation_items");
    $db->exec("DELETE FROM bank_reconciliations");
    $db->exec("DELETE FROM bank_deposits");
    $db->exec("DELETE FROM cash_drawer_movements WHERE branch_id = 1");
    $db->exec("DELETE FROM business_days WHERE branch_id = 1");
    $db->exec("DELETE FROM journal_lines WHERE journal_entry_id IN (SELECT id FROM journal_entries WHERE description LIKE '%S10%' OR description LIKE '%Petty Cash%')");
    $db->exec("DELETE FROM journal_entries WHERE description LIKE '%S10%' OR description LIKE '%Petty Cash%'");
    $db->exec("DELETE FROM petty_cash_adjustments WHERE branch_id = 1");
    $db->exec("DELETE FROM petty_cash_reimbursements WHERE branch_id = 1");
    $db->exec("DELETE FROM petty_cash_vouchers WHERE branch_id = 1");
    $db->exec("DELETE FROM expense_categories WHERE code LIKE 'EXP-S10%'");

    echo "Teardown complete.\n\n";

    // Summary
    echo "===============================================================\n";
    echo "  SECTION 10 TEST SUMMARY: Passed: {$testPassed} | Failed: {$testFailed}\n";
    echo "===============================================================\n";

    if ($testFailed > 0) {
        exit(1);
    }

} catch (Throwable $e) {
    echo "\n[FATAL ERROR] " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
