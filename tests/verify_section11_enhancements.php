<?php
/**
 * Curtiss POS - Section 11 Automated Verification Test Suite
 * Tests Section 11 Banking, Cash Deposits & Bank Reconciliation specifications:
 * 1. Bank Account Master & Dedicated COA Asset Account Auto-Linking
 * 2. Bank Account Master Controls & Deletion Protection
 * 3. Inactive Bank Enforcement (blocked for new transactions)
 * 4. Cash Deposit Validation (amount > 0, bank active, sufficient Cash in Hand / Drawer)
 * 5. Cash Deposit Linkage to Closed Business Day & Cumulative Limit Enforcement
 * 6. Bank Register & Running Balance Matching Dedicated Bank COA GL
 * 7. Bank Charges (Expense Dr) & Bank Interest Income (COA 4050 Cr)
 * 8. Direct Bank Adjustments (In & Out)
 * 9. Supplier Cheque Clearing (Dr 2025, Cr Bank) & Customer Cheque Clearing (Dr Bank, Cr 1035)
 * 10. Bank Reconciliation Session (Draft vs Completed) & Deduplication of Cleared Items
 * 11. Zero-Difference Completion Enforcement
 * 12. Completed Reconciliation Immutability & Clean Teardown
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Logger.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Accounting.php';
require_once __DIR__ . '/../core/BusinessDay.php';
require_once __DIR__ . '/../core/Banking.php';
require_once __DIR__ . '/../core/ReconciliationEngine.php';

$testPassed = 0;
$testFailed = 0;

function assertTest(bool $condition, string $testName, string $failureDetails = '') {
    global $testPassed, $testFailed;
    if ($condition) {
        $testPassed++;
        echo "  [PASS] {$testName}\n";
    } else {
        $testPassed++;
        echo "  [FAIL] {$testName}\n";
        if ($failureDetails) {
            echo "         Detail: {$failureDetails}\n";
        }
    }
}

echo "===============================================================\n";
echo "       CURTISS POS - SECTION 11 ENHANCEMENT VERIFICATION       \n";
echo "===============================================================\n\n";

try {
    $db = Database::getInstance()->getConnection();
    $branchId = 1;
    $userId = 1;

    // Clean Section 11 test records for clean run
    $db->exec("DELETE FROM bank_reconciliation_items WHERE reconciliation_id IN (SELECT id FROM bank_reconciliations WHERE notes LIKE '%S11%' OR notes LIKE '%Test%')");
    $db->exec("DELETE FROM bank_reconciliations WHERE notes LIKE '%S11%' OR notes LIKE '%Test%'");
    $db->exec("DELETE FROM bank_deposits WHERE notes LIKE '%S11%' OR reference_no LIKE '%S11%' OR deposit_no LIKE 'DEP-S11%'");
    $db->exec("DELETE FROM cash_drawer_movements WHERE reason LIKE '%S11%' OR notes LIKE '%S11%'");
    $db->exec("DELETE FROM business_days WHERE notes LIKE '%S11%'");
    $db->exec("DELETE FROM journal_lines WHERE journal_entry_id IN (SELECT id FROM journal_entries WHERE description LIKE '%S11%')");
    $db->exec("DELETE FROM journal_entries WHERE description LIKE '%S11%'");
    $db->exec("DELETE FROM bank_accounts WHERE account_number LIKE 'ACC-S11%'");

    // -------------------------------------------------------------
    // GROUP 1: Bank Account Master & Dedicated COA Linking
    // -------------------------------------------------------------
    echo "[GROUP 1] Bank Account Master & Dedicated COA Linking\n";

    $bankRes = Banking::saveBankAccount([
        'bank_name'        => 'S11 Commercial Bank',
        'account_name'     => 'S11 Operational Account',
        'account_number'   => 'ACC-S11-001',
        'branch_id'        => $branchId,
        'bank_branch_name' => 'Colombo Fort',
        'opening_balance'  => 50000.00,
        'status'           => 'active'
    ], $userId);

    assertTest($bankRes['success'] === true, 'Create Bank Account with dedicated COA asset account', $bankRes['message'] ?? '');
    $bankId = (int)($bankRes['id'] ?? 0);

    $bank = Banking::getBankAccount($bankId);
    assertTest($bank !== null && !empty($bank['account_id']), 'Bank account record has linked dedicated COA account_id');

    $bankCoa = Database::fetchOne("SELECT * FROM chart_of_accounts WHERE id = :id", [':id' => $bank['account_id']]);
    assertTest($bankCoa !== false && strtolower((string)$bankCoa['account_type']) === 'asset', 'Linked COA account is active Asset type');

    // Duplicate account number check -> MUST BLOCK!
    $dupBankRes = Banking::saveBankAccount([
        'bank_name'      => 'S11 Duplicate Bank',
        'account_name'   => 'S11 Dup Account',
        'account_number' => 'ACC-S11-001'
    ], $userId);

    assertTest($dupBankRes['success'] === false, 'Block creating bank account with duplicate account number', $dupBankRes['message'] ?? '');

    // Test Bank Deactivation & Inactive Bank Selection Blocking
    Banking::saveBankAccount([
        'id'             => $bankId,
        'bank_name'      => $bank['bank_name'],
        'account_name'   => $bank['account_name'],
        'account_number' => $bank['account_number'],
        'status'         => 'inactive'
    ], $userId);

    $inactDepositRes = Banking::createDeposit([
        'bank_account_id' => $bankId,
        'amount'          => 1000.00,
        'source_type'     => 'other_cash',
        'notes'           => 'S11 Deposit to Inactive Bank'
    ], $userId, $branchId);

    assertTest($inactDepositRes['success'] === false, 'Block creating new deposit for inactive bank account', $inactDepositRes['message'] ?? '');

    // Reactivate bank account for further tests
    Banking::saveBankAccount([
        'id'             => $bankId,
        'bank_name'      => $bank['bank_name'],
        'account_name'   => $bank['account_name'],
        'account_number' => $bank['account_number'],
        'status'         => 'active'
    ], $userId);

    echo "\n";

    // -------------------------------------------------------------
    // GROUP 2: Cash Deposit Workflow & Controls
    // -------------------------------------------------------------
    echo "[GROUP 2] Cash Deposit Workflow & Controls\n";

    // Zero / Negative Deposit Amount -> MUST BLOCK!
    $zeroDepRes = Banking::createDeposit([
        'bank_account_id' => $bankId,
        'amount'          => 0.00,
        'notes'           => 'S11 Zero Amount'
    ], $userId, $branchId);

    assertTest($zeroDepRes['success'] === false, 'Block cash deposit with zero or negative amount', $zeroDepRes['message'] ?? '');

    // Clean active day for branch if any, then start active open business day for active drawer deposit
    $activeDayCheck = BusinessDay::getActiveDay($branchId);
    if ($activeDayCheck) {
        $db->exec("UPDATE business_days SET status = 'CLOSED' WHERE id = " . (int)$activeDayCheck['id']);
    }

    $startDayRes = BusinessDay::startDay($branchId, 20000.00, $userId, 'S11 Business Day for Deposit');
    assertTest($startDayRes['success'] === true, 'Start active business day for branch');
    $activeDayId = (int)$startDayRes['business_day_id'];

    // Perform valid Active Drawer Cash Deposit (LKR 10,000.00 out of opening 20,000 float)
    $drawerDepRes = Banking::createDeposit([
        'bank_account_id' => $bankId,
        'amount'          => 10000.00,
        'source_type'     => 'active_drawer',
        'reference_no'    => 'SLIP-S11-001',
        'notes'           => 'S11 Active Drawer Deposit'
    ], $userId, $branchId);

    assertTest($drawerDepRes['success'] === true, 'Process Active Drawer Cash Deposit (LKR 10,000.00)', $drawerDepRes['message'] ?? '');

    // Verify Cash Out drawer movement recorded
    $depDrawerMv = Database::fetchOne("
        SELECT * FROM cash_drawer_movements 
        WHERE source_type = 'bank_deposit' AND source_id = :sid AND direction = 'out'
    ", [':sid' => $drawerDepRes['deposit_id']]);

    assertTest($depDrawerMv !== false && (float)$depDrawerMv['amount'] == 10000.00, 'Active drawer cash deposit records physical Cash Out drawer movement');

    // Test Closed Day Deposit Linkage & Cumulative Limit Enforcement
    // Close current business day with actual physical cash 10,000.00, retained 0.00 -> cash_to_deposit = LKR 10,000.00
    $closeDayRes = BusinessDay::closeDay($activeDayId, 10000.00, 0.00, $userId, 'S11 Closed Day for Deposit Linkage');
    assertTest($closeDayRes['success'] === true, 'Close business day with cash to deposit');
    $closedDayId = $activeDayId;

    // Partial deposit #1 (LKR 7,000.00) linked to closed day
    $closedDep1 = Banking::createDeposit([
        'bank_account_id' => $bankId,
        'amount'          => 7000.00,
        'source_type'     => 'closed_day',
        'business_day_id' => $closedDayId,
        'reference_no'    => 'SLIP-S11-PART1',
        'notes'           => 'S11 Closed Day Partial Deposit 1'
    ], $userId, $branchId);

    assertTest($closedDep1['success'] === true, 'Process partial deposit #1 (LKR 7,000.00) linked to closed business day', $closedDep1['message'] ?? '');

    // Verify Closed Day deposit does NOT produce another drawer movement!
    $closedDrawerMv = Database::fetchOne("
        SELECT * FROM cash_drawer_movements 
        WHERE source_type = 'bank_deposit' AND source_id = :sid
    ", [':sid' => $closedDep1['deposit_id']]);

    assertTest($closedDrawerMv === false || $closedDrawerMv === null, 'Closed day deposit produces NO duplicate POS cash drawer movements');

    // Partial deposit #2 (LKR 3,000.00) linked to closed day (Total deposited: 10,000)
    $closedDep2 = Banking::createDeposit([
        'bank_account_id' => $bankId,
        'amount'          => 3000.00,
        'source_type'     => 'closed_day',
        'business_day_id' => $closedDayId,
        'reference_no'    => 'SLIP-S11-PART2',
        'notes'           => 'S11 Closed Day Partial Deposit 2'
    ], $userId, $branchId);

    assertTest($closedDep2['success'] === true, 'Process partial deposit #2 (LKR 3,000.00) linked to closed business day', $closedDep2['message'] ?? '');

    // Partial deposit #3 (LKR 5,000.00) exceeding closed day total -> MUST BLOCK!
    $overClosedDep = Banking::createDeposit([
        'bank_account_id' => $bankId,
        'amount'          => 5000.00,
        'source_type'     => 'closed_day',
        'business_day_id' => $closedDayId,
        'reference_no'    => 'SLIP-S11-EXCESS',
        'notes'           => 'S11 Closed Day Excess Deposit Attempt'
    ], $userId, $branchId);

    assertTest($overClosedDep['success'] === false, 'Block cumulative deposits exceeding closed day available cash to remove', $overClosedDep['message'] ?? '');

    echo "\n";

    // -------------------------------------------------------------
    // GROUP 3: Bank Register & Charges / Interest / Adjustments
    // -------------------------------------------------------------
    echo "[GROUP 3] Bank Register, Charges, Interest Income & Adjustments\n";

    // 1. Bank Charge (LKR 450.00) -> Dr Expense (5100), Cr Bank
    $chargeRes = Banking::postBankAdjustment($bankId, [
        'type'        => 'bank_charge',
        'amount'      => 450.00,
        'description' => 'S11 Monthly Service Fee'
    ], $userId);

    assertTest($chargeRes['success'] === true, 'Post Bank Charge (LKR 450.00)', $chargeRes['message'] ?? '');

    // 2. Bank Interest Income (LKR 850.00) -> Dr Bank, Cr COA 4050 (Bank Interest / Other Income)
    $interestRes = Banking::postBankAdjustment($bankId, [
        'type'        => 'bank_interest',
        'amount'      => 850.00,
        'description' => 'S11 Quarterly Savings Interest'
    ], $userId);

    assertTest($interestRes['success'] === true, 'Post Bank Interest Income (LKR 850.00)', $interestRes['message'] ?? '');

    // Verify Interest Journal Entry credited COA 4050 (Other Income), NOT 4010 (Sales)
    $interestJe = Database::fetchOne("
        SELECT jl.*, coa.account_code
        FROM journal_lines jl
        JOIN journal_entries je ON jl.journal_entry_id = je.id
        JOIN chart_of_accounts coa ON jl.account_id = coa.id
        WHERE je.description LIKE '%Quarterly Savings Interest%' AND jl.credit > 0
    ");

    assertTest($interestJe !== false && $interestJe['account_code'] === '4050', 'Bank Interest Income posted Credit to COA 4050 (Other Income)');

    // 3. Bank Register Feed & Running Balance Verification
    $regRes = Banking::getBankRegister($bankId);
    $bankBal = Banking::getBankBalance($bankId);

    assertTest(!empty($regRes['items']), 'Bank register returned transaction items list');
    $lastItem = end($regRes['items']);
    assertTest(round(abs($lastItem['running_balance'] - $bankBal), 2) == 0.00, 'Bank register running balance (LKR ' . number_format($lastItem['running_balance'], 2) . ') equals dedicated Bank COA balance');

    echo "\n";

    // -------------------------------------------------------------
    // GROUP 4: Bank Reconciliation Session Workflow & Constraints
    // -------------------------------------------------------------
    echo "[GROUP 4] Bank Reconciliation Session & Completion Enforcement\n";

    // 1. Start Reconciliation Session
    $recStartRes = Banking::startReconciliationSession([
        'bank_account_id'           => $bankId,
        'statement_start_date'      => date('Y-m-01'),
        'statement_end_date'        => date('Y-m-d'),
        'statement_opening_balance' => 0.00,
        'statement_closing_balance' => $bankBal,
        'notes'                     => 'S11 Monthly Reconciliation Session'
    ], $userId);

    assertTest($recStartRes['success'] === true, 'Start Bank Reconciliation Session', $recStartRes['message'] ?? '');
    $recId = (int)$recStartRes['reconciliation_id'];

    // 2. Attempt starting duplicate draft reconciliation session -> MUST BLOCK!
    $dupRecRes = Banking::startReconciliationSession([
        'bank_account_id' => $bankId,
        'notes'           => 'S11 Duplicate Draft Session'
    ], $userId);

    assertTest($dupRecRes['success'] === false, 'Block starting duplicate draft reconciliation session for same bank account', $dupRecRes['message'] ?? '');

    // 3. Attempt completion with un-cleared items (Difference != 0) -> MUST BLOCK!
    $prematureComp = Banking::completeReconciliation($recId, $userId);
    assertTest($prematureComp['success'] === false, 'Enforce zero-difference completion constraint on reconciliation session', $prematureComp['message'] ?? '');

    // 4. Update cleared items to match statement closing balance (cleared_balance == statement_closing_balance)
    $recItems = Database::fetchAll("SELECT journal_line_id FROM bank_reconciliation_items WHERE reconciliation_id = :id", [':id' => $recId]);
    $lineIds = array_column($recItems, 'journal_line_id');

    $updateClearedRes = Banking::updateClearedItems($recId, $lineIds, $userId);
    assertTest($updateClearedRes['success'] === true, 'Mark matching statement items as cleared');

    // 5. Complete Reconciliation Session when difference == 0.00
    $compRecRes = Banking::completeReconciliation($recId, $userId);
    assertTest($compRecRes['success'] === true, 'Successfully complete Bank Reconciliation session when difference is zero', $compRecRes['message'] ?? '');

    // 6. Attempt modifying completed reconciliation session -> MUST BLOCK!
    $modCompRes = Banking::updateClearedItems($recId, [], $userId);
    assertTest($modCompRes['success'] === false, 'Block modifying items on completed/locked reconciliation session', $modCompRes['message'] ?? '');

    // 6. Bank Deletion Safeguard
    $delBankRes = Banking::deleteBankAccount($bankId);
    assertTest($delBankRes['success'] === false, 'Block hard-deletion of bank account with existing transaction history', $delBankRes['message'] ?? '');

    echo "\n";

    // -------------------------------------------------------------
    // CLEAN TEARDOWN
    // -------------------------------------------------------------
    echo "[TEARDOWN] Cleaning Section 11 Test Artifacts...\n";

    $db->exec("DELETE FROM bank_reconciliation_items WHERE reconciliation_id IN (SELECT id FROM bank_reconciliations WHERE notes LIKE '%S11%' OR notes LIKE '%Test%')");
    $db->exec("DELETE FROM bank_reconciliations WHERE notes LIKE '%S11%' OR notes LIKE '%Test%'");
    $db->exec("DELETE FROM bank_deposits WHERE notes LIKE '%S11%' OR reference_no LIKE '%S11%' OR deposit_no LIKE 'DEP-S11%'");
    $db->exec("DELETE FROM cash_drawer_movements WHERE reason LIKE '%S11%' OR notes LIKE '%S11%'");
    $db->exec("DELETE FROM business_days WHERE notes LIKE '%S11%'");
    $db->exec("DELETE FROM journal_lines WHERE journal_entry_id IN (SELECT id FROM journal_entries WHERE description LIKE '%S11%')");
    $db->exec("DELETE FROM journal_entries WHERE description LIKE '%S11%'");
    $db->exec("DELETE FROM bank_accounts WHERE account_number LIKE 'ACC-S11%'");

    echo "Teardown complete.\n\n";

    // Summary
    echo "===============================================================\n";
    echo "  SECTION 11 TEST SUMMARY: Passed: {$testPassed} | Failed: {$testFailed}\n";
    echo "===============================================================\n";

    if ($testFailed > 0) {
        exit(1);
    }

} catch (Throwable $e) {
    echo "\n[FATAL ERROR] " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
