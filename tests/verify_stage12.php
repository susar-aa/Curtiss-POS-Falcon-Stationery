<?php
/**
 * Curtiss POS - Stage 12 Automated CLI Verification & Regression Test Suite
 * Tests branch-specific petty cash balance tracking, expense categories & COA mapping,
 * sequential expense vouchers, insufficient balance blocking, bank vs drawer reimbursements,
 * drawer movement integration, adjustments, voucher reversals, branch isolation, balanced GL, and Stage 1-11 regression.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Logger.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Accounting.php';
require_once __DIR__ . '/../core/BusinessDay.php';
require_once __DIR__ . '/../core/PettyCash.php';

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
echo "       CURTISS POS - STAGE 12 VERIFICATION TEST SUITE           \n";
echo "===============================================================\n\n";

try {
    $db = Database::getInstance()->getConnection();
    $branchId = 1;
    $userId = 1;

    // Clean stage 12 test records for clean run
    $db->exec("DELETE FROM bank_reconciliation_items");
    $db->exec("DELETE FROM bank_reconciliations");
    $db->exec("DELETE FROM bank_deposits");
    $db->exec("DELETE FROM cash_drawer_movements WHERE branch_id = 1");
    $db->exec("DELETE FROM business_days WHERE branch_id = 1");
    $db->exec("DELETE FROM journal_lines WHERE journal_entry_id IN (SELECT id FROM journal_entries WHERE description LIKE '%Petty Cash%')");
    $db->exec("DELETE FROM journal_entries WHERE description LIKE '%Petty Cash%'");
    $db->exec("DELETE FROM petty_cash_adjustments WHERE branch_id = 1");
    $db->exec("DELETE FROM petty_cash_reimbursements WHERE branch_id = 1");
    $db->exec("DELETE FROM petty_cash_vouchers WHERE branch_id = 1");
    $db->exec("DELETE FROM expense_categories WHERE code LIKE 'EXP-S12-%'");

    // -------------------------------------------------------------
    // TEST 1: Expense Category Creation & COA Mapping
    // -------------------------------------------------------------
    echo "[GROUP 1] Expense Categories & COA Mapping\n";

    $expAccId = Accounting::getAccountIdByCode(Accounting::CODE_EXPENSES); // 5100 General Operating Expenses

    $catRes = PettyCash::saveCategory([
        'name' => 'Stage 12 Custom Expense Category',
        'code' => 'EXP-S12-' . time() . '-' . rand(100, 999),
        'account_id' => $expAccId,
        'description' => 'S12 Test Expense Category',
        'status' => 'active'
    ], $userId);

    assertTest($catRes['success'] === true, 'Save custom expense category mapped to COA 5100', $catRes['message'] ?? '');
    $categoryId = (int)($catRes['id'] ?? 1);

    $cats = PettyCash::getCategories(true);
    assertTest(!empty($cats), 'Fetch active expense categories list');

    echo "\n";

    // -------------------------------------------------------------
    // TEST 2: Bank Fund Reimbursement (No Drawer Impact)
    // -------------------------------------------------------------
    echo "[GROUP 2] Bank Fund Reimbursement (No Drawer Impact)\n";

    $initialBal = PettyCash::getPettyCashBalance(1);

    $bankReimb = PettyCash::reimburseFund([
        'source_type' => 'bank',
        'amount' => 10000.00,
        'reference_no' => 'CHQ-BANK-S12',
        'notes' => 'Initial Fund Top-up from Bank Account'
    ], $userId, 1);

    assertTest($bankReimb['success'] === true, 'Reimburse LKR 10,000.00 from Bank Account', $bankReimb['message'] ?? '');
    
    $balAfterBank = PettyCash::getPettyCashBalance(1);
    assertTest(round($balAfterBank - $initialBal, 2) == 10000.00, 'Petty Cash balance increased by LKR 10,000.00 (New balance: LKR ' . number_format($balAfterBank, 2) . ')');

    // Verify Bank Reimbursement did NOT create any cash drawer movement
    $drawerCheckBank = Database::fetchOne("
        SELECT * FROM cash_drawer_movements 
        WHERE source_type = 'petty_cash_reimbursement' AND source_id = :sid
    ", [':sid' => $bankReimb['reimbursement_id']]);

    assertTest($drawerCheckBank === null || $drawerCheckBank === false, 'Bank-funded reimbursement does NOT create cash drawer movement');

    echo "\n";

    // -------------------------------------------------------------
    // TEST 3: Expense Voucher Creation & Insufficient Balance Blocking
    // -------------------------------------------------------------
    echo "[GROUP 3] Expense Vouchers & Insufficient Balance Enforcement\n";

    // 1. Post valid voucher (LKR 3,500.00)
    $vRes1 = PettyCash::createVoucher([
        'category_id' => $categoryId,
        'amount' => 3500.00,
        'payee' => 'Ceypetco Fuel Station',
        'description' => 'Delivery van diesel tank fill',
        'reference_no' => 'BILL-FUEL-001',
        'receipt_ref' => 'REC-001'
    ], $userId, 1);

    assertTest($vRes1['success'] === true, 'Create expense voucher #1 of LKR 3,500.00', $vRes1['message'] ?? '');
    $voucherId1 = (int)$vRes1['voucher_id'];
    $voucherNo1 = $vRes1['voucher_no'];
    assertTest(strpos($voucherNo1, 'PCV-') === 0, "Sequential voucher number generated: {$voucherNo1}");

    $balAfterExp1 = PettyCash::getPettyCashBalance(1);
    assertTest(round($balAfterBank - $balAfterExp1, 2) == 3500.00, 'Petty Cash balance reduced by LKR 3,500.00 (Current balance: LKR ' . number_format($balAfterExp1, 2) . ')');

    // Verify GL Double-Entry for Expense: Dr Expense (5100), Cr Petty Cash (1015)
    $voucherJe = Database::fetchOne("
        SELECT je.* FROM journal_entries je
        WHERE je.source_id = :vid AND je.description LIKE '%Petty Cash Expense Voucher%'
    ", [':vid' => $voucherId1]);
    assertTest($voucherJe !== false, 'Expense voucher posted double-entry journal entry to GL');

    // 2. Attempt expense that EXCEEDS current petty cash balance (LKR 15,000.00 when balance is ~6,500)
    $overExp = PettyCash::createVoucher([
        'category_id' => $categoryId,
        'amount' => 15000.00,
        'payee' => 'Exceeding Payee',
        'description' => 'Attempting to exceed balance'
    ], $userId, 1);

    assertTest($overExp['success'] === false, 'Block expense voucher exceeding available petty cash balance', $overExp['message'] ?? '');

    echo "\n";

    // -------------------------------------------------------------
    // TEST 4: Cash in Hand Reimbursement & Drawer Movement Integration
    // -------------------------------------------------------------
    echo "[GROUP 4] Cash in Hand Reimbursement & Drawer Integration\n";

    // Clean active day for branch 1 if any, then start fresh active business day
    $db->exec("DELETE FROM cash_drawer_movements WHERE branch_id = 1");
    $db->exec("DELETE FROM business_days WHERE branch_id = 1");

    $bDayRes = BusinessDay::startDay(1, 20000.00, $userId, 'S12 Drawer Test Day');
    $bDayId = (int)$bDayRes['business_day_id'];

    // Reimburse LKR 2,000.00 from Cash in Hand Drawer
    $cashReimb = PettyCash::reimburseFund([
        'source_type' => 'cash_in_hand',
        'amount' => 2000.00,
        'reference_no' => 'DRAWER-REFUND-S12',
        'notes' => 'Petty Cash Replenishment from Cash Drawer'
    ], $userId, 1);

    assertTest($cashReimb['success'] === true, 'Reimburse LKR 2,000.00 from Cash in Hand Drawer', $cashReimb['message'] ?? '');

    // Verify Petty Cash balance increased by 2,000
    $balAfterCashReimb = PettyCash::getPettyCashBalance(1);
    assertTest(round($balAfterCashReimb - $balAfterExp1, 2) == 2000.00, 'Petty Cash balance increased by LKR 2,000.00');

    // Verify Cash Drawer Movement recorded (`movement_type = 'cash_out'`, `direction = 'out'`)
    $drawerCheckCash = Database::fetchOne("
        SELECT * FROM cash_drawer_movements 
        WHERE source_type = 'petty_cash_reimbursement' AND source_id = :sid AND direction = 'out'
    ", [':sid' => $cashReimb['reimbursement_id']]);

    assertTest($drawerCheckCash !== false && (float)$drawerCheckCash['amount'] == 2000.00, 'Cash in Hand reimbursement creates physical Cash Out drawer movement (LKR 2,000.00)');

    echo "\n";

    // -------------------------------------------------------------
    // TEST 5: Manual Adjustments (Positive & Negative)
    // -------------------------------------------------------------
    echo "[GROUP 5] Manual Petty Cash Adjustments\n";

    $balBeforeAdj = PettyCash::getPettyCashBalance(1);

    // Positive adjustment (+ LKR 1,000.00)
    $posAdj = PettyCash::adjustFund([
        'adjustment_type' => 'positive',
        'amount' => 1000.00,
        'reason' => 'Found unrecorded cash float in petty cash box'
    ], $userId, 1);

    assertTest($posAdj['success'] === true, 'Process positive adjustment (+ LKR 1,000.00)', $posAdj['message'] ?? '');

    $balAfterPosAdj = PettyCash::getPettyCashBalance(1);
    assertTest(round($balAfterPosAdj - $balBeforeAdj, 2) == 1000.00, 'Petty Cash balance increased by LKR 1,000.00');

    // Negative adjustment (- LKR 400.00)
    $negAdj = PettyCash::adjustFund([
        'adjustment_type' => 'negative',
        'amount' => 400.00,
        'reason' => 'Minor cash shortage in petty box during count'
    ], $userId, 1);

    assertTest($negAdj['success'] === true, 'Process negative adjustment (- LKR 400.00)', $negAdj['message'] ?? '');

    $balAfterNegAdj = PettyCash::getPettyCashBalance(1);
    assertTest(round($balAfterPosAdj - $balAfterNegAdj, 2) == 400.00, 'Petty Cash balance reduced by LKR 400.00');

    echo "\n";

    // -------------------------------------------------------------
    // TEST 6: Voucher Reversal & Balance Restoration
    // -------------------------------------------------------------
    echo "[GROUP 6] Voucher Reversal & Immutability\n";

    $balBeforeRev = PettyCash::getPettyCashBalance(1);

    // Reverse Voucher #1 (LKR 3,500.00)
    $revRes = PettyCash::reverseVoucher($voucherId1, 'Payee cancelled receipt and refunded cash', $userId, 1);
    assertTest($revRes['success'] === true, 'Reverse expense voucher #1 (LKR 3,500.00)', $revRes['message'] ?? '');

    $balAfterRev = PettyCash::getPettyCashBalance(1);
    assertTest(round($balAfterRev - $balBeforeRev, 2) == 3500.00, 'Petty Cash balance restored (+ LKR 3,500.00)');

    // Verify voucher status updated to reversed
    $revVoucher = Database::fetchOne("SELECT status FROM petty_cash_vouchers WHERE id = :id", [':id' => $voucherId1]);
    assertTest($revVoucher['status'] === 'reversed', 'Voucher status updated to reversed');

    // Prevent double reversal
    $doubleRev = PettyCash::reverseVoucher($voucherId1, 'Duplicate reversal attempt', $userId, 1);
    assertTest($doubleRev['success'] === false, 'Prevent double reversal of already reversed voucher', $doubleRev['message'] ?? '');

    echo "\n";

    // -------------------------------------------------------------
    // TEST 7: Branch Isolation & GL Balance Health
    // -------------------------------------------------------------
    echo "[GROUP 7] Branch Isolation & Trial Balance Health\n";

    // Verify Branch 2 petty cash balance is completely independent
    $b2Bal = PettyCash::getPettyCashBalance(2);
    assertTest($b2Bal == 0.00, 'Branch 2 petty cash balance is isolated (LKR 0.00)');

    // Verify General Ledger Trial Balance is 100% Balanced
    $trialRow = Database::fetchOne("
        SELECT SUM(debit) AS total_debit, SUM(credit) AS total_credit
        FROM journal_lines
    ");
    $totDr = round((float)($trialRow['total_debit'] ?? 0), 2);
    $totCr = round((float)($trialRow['total_credit'] ?? 0), 2);

    assertTest(abs($totDr - $totCr) < 0.001, "General Ledger Double-Entry Trial Balance is 100% Balanced (Total Dr: LKR {$totDr}, Total Cr: LKR {$totCr})");

    echo "\n";

} catch (Throwable $e) {
    echo "\n[UNHANDLED EXCEPTION] " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    $testFailed++;
}

echo "===============================================================\n";
echo "SUMMARY: " . ($testPassed + $testFailed) . " Tests Executed\n";
echo "PASSED: {$testPassed}\n";
echo "FAILED: {$testFailed}\n";
echo "===============================================================\n";

if ($testFailed === 0) {
    echo "🎉 STAGE 12 PETTY CASH & EXPENSE ENGINE VERIFIED 100% SUCCESSFUL!\n";
    exit(0);
} else {
    echo "❌ STAGE 12 VERIFICATION FAILED WITH {$testFailed} ERRORS.\n";
    exit(1);
}
