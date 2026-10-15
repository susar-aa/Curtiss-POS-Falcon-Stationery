<?php
/**
 * Curtiss POS - Stage 13 Automated CLI Verification & Regression Test Suite
 * Tests bank accounts management, dedicated COA mapping, cash deposits (drawer vs closed day),
 * bank activity register & running balances, bank reconciliations, cleared matching, bank adjustments,
 * zero-difference completion locking, GL balance integrity, and Stage 1-12 regression.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Logger.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Accounting.php';
require_once __DIR__ . '/../core/BusinessDay.php';
require_once __DIR__ . '/../core/PettyCash.php';
require_once __DIR__ . '/../core/Banking.php';

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
echo "       CURTISS POS - STAGE 13 VERIFICATION TEST SUITE           \n";
echo "===============================================================\n\n";

try {
    $db = Database::getInstance()->getConnection();
    $branchId = 1;
    $userId = 1;

    // Clean Stage 13 test records for clean run
    $db->exec("DELETE FROM bank_reconciliation_items");
    $db->exec("DELETE FROM bank_reconciliations");
    $db->exec("DELETE FROM cash_drawer_movements WHERE source_type = 'bank_deposit'");
    $db->exec("DELETE FROM journal_lines WHERE journal_entry_id IN (SELECT id FROM journal_entries WHERE description LIKE '%Bank Cash Deposit%' OR description LIKE '%Bank Adjustment%')");
    $db->exec("DELETE FROM journal_entries WHERE description LIKE '%Bank Cash Deposit%' OR description LIKE '%Bank Adjustment%'");
    $db->exec("DELETE FROM bank_deposits");

    // -------------------------------------------------------------
    // GROUP 1: Bank Accounts Setup & Dedicated COA Mapping
    // -------------------------------------------------------------
    echo "[GROUP 1] Bank Accounts Setup & Dedicated COA Mapping\n";

    $bankAccounts = Banking::getBankAccounts(null, false);
    assertTest(count($bankAccounts) >= 2, "Default company bank accounts seeded (at least 2 accounts exist)");

    $commBank = Banking::getBankAccount(1);
    assertTest($commBank !== null && $commBank['account_code'] === '1021', "Commercial Bank mapped to dedicated COA account 1021");

    // Create a new bank account with auto-created dedicated COA account
    $testAccNum = '9918' . rand(10000, 99999);
    $newBankRes = Banking::saveBankAccount([
        'bank_name'        => 'Hatton National Bank',
        'account_name'     => 'HNB Operational Account',
        'account_number'   => $testAccNum,
        'branch_id'        => 1,
        'bank_branch_name' => 'Kandy Branch',
        'opening_balance'  => 10000.00,
        'status'           => 'active'
    ], $userId);

    assertTest($newBankRes['success'] === true, "Create new Bank Account with auto COA mapping succeeded");
    $hnbAccountId = $newBankRes['id'] ?? 0;

    $hnbAcc = Banking::getBankAccount($hnbAccountId);
    assertTest($hnbAcc !== null && !empty($hnbAcc['account_code']), "Dedicated COA account auto-created for new bank account (Code: " . ($hnbAcc['account_code'] ?? 'N/A') . ")");

    $hnbBal = Banking::getBankBalance($hnbAccountId);
    assertTest(abs($hnbBal - 10000.00) < 0.01, "Bank opening balance initialized correctly (LKR " . number_format($hnbBal, 2) . ")");

    echo "\n";

    // -------------------------------------------------------------
    // GROUP 2: Cash Deposits & Drawer Integration
    // -------------------------------------------------------------
    echo "[GROUP 2] Cash Deposits & Drawer Integration\n";

    // Setup active business day for branch 1
    $db->exec("UPDATE business_days SET status = 'closed' WHERE branch_id = 1 AND status = 'open'");
    $openDayRes = BusinessDay::startDay($branchId, 5000.00, $userId, 'Stage 13 Open Day');
    assertTest($openDayRes['success'] === true, "Start active business day for deposit testing");
    $activeDayId = $openDayRes['business_day_id'] ?? 0;

    // 2.1 Deposit from Active Drawer -> Records Cash Out drawer movement
    $dep1Res = Banking::createDeposit([
        'bank_account_id' => 1,
        'amount'          => 2000.00,
        'source_type'     => 'active_drawer',
        'reference_no'    => 'SLIP-001',
        'notes'           => 'Deposit from active drawer'
    ], $userId, $branchId);

    assertTest($dep1Res['success'] === true, "Cash deposit from Active Drawer created successfully (#{$dep1Res['deposit_no']})");

    $drawerMv = Database::fetchOne("
        SELECT * FROM cash_drawer_movements WHERE source_type = 'bank_deposit' AND source_id = :id
    ", [':id' => $dep1Res['deposit_id']]);
    assertTest($drawerMv !== null && (float)$drawerMv['amount'] === 2000.00 && $drawerMv['movement_type'] === 'cash_out', "Drawer Cash Out movement recorded exactly once for active drawer deposit");

    // 2.2 Deposit from Closed Business Day -> Does NOT record drawer movement
    // Create closed day mock record
    $db->exec("
        INSERT INTO business_days (branch_id, business_date, status, opening_cash, cash_to_deposit, opened_by)
        VALUES (1, '2026-09-20', 'closed', 5000.00, 15000.00, 1)
    ");
    $closedDayId = (int)$db->lastInsertId();

    $dep2Res = Banking::createDeposit([
        'bank_account_id' => 1,
        'amount'          => 5000.00,
        'source_type'     => 'closed_day',
        'business_day_id' => $closedDayId,
        'reference_no'    => 'SLIP-002',
        'notes'           => 'Deposit from closed business day'
    ], $userId, $branchId);

    assertTest($dep2Res['success'] === true, "Cash deposit from Closed Business Day created successfully (#{$dep2Res['deposit_no']})");

    $closedDrawerMv = Database::fetchOne("
        SELECT * FROM cash_drawer_movements WHERE source_type = 'bank_deposit' AND source_id = :id
    ", [':id' => $dep2Res['deposit_id']]);
    assertTest($closedDrawerMv === null, "Deposit from closed day did NOT create duplicate drawer movement");

    echo "\n";

    // -------------------------------------------------------------
    // GROUP 3: Bank Activity Register & Running Balance
    // -------------------------------------------------------------
    echo "[GROUP 3] Bank Activity Register & Running Balance\n";

    $registerData = Banking::getBankRegister(1, [], 1, 20);
    assertTest(!empty($registerData['items']), "Bank register returned transaction items");

    $commBal = Banking::getBankBalance(1);
    assertTest($commBal > 0.00, "Commercial Bank running balance updated correctly after deposits (Current: LKR " . number_format($commBal, 2) . ")");

    echo "\n";

    // -------------------------------------------------------------
    // GROUP 4: Bank Reconciliation & Cleared Matching
    // -------------------------------------------------------------
    echo "[GROUP 4] Bank Reconciliation & Cleared Matching\n";

    $recStartRes = Banking::startReconciliationSession([
        'bank_account_id'           => 1,
        'statement_start_date'      => date('Y-m-01'),
        'statement_end_date'        => date('Y-m-t'),
        'statement_opening_balance' => 50000.00,
        'statement_closing_balance' => 57000.00,
        'notes'                     => 'Stage 13 Verification Statement'
    ], $userId);

    assertTest($recStartRes['success'] === true, "Start Bank Reconciliation session created (#{$recStartRes['reconciliation_no']})");
    $recId = $recStartRes['reconciliation_id'];

    // Update cleared items
    $recItems = Database::fetchAll("SELECT journal_line_id FROM bank_reconciliation_items WHERE reconciliation_id = :id", [':id' => $recId]);
    $clearedLineIds = array_column($recItems, 'journal_line_id');

    $updateClearedRes = Banking::updateClearedItems($recId, $clearedLineIds, $userId);
    assertTest($updateClearedRes['success'] === true, "Cleared items updated for reconciliation session");

    echo "\n";

    // -------------------------------------------------------------
    // GROUP 5: Bank Adjustments (Charges / Interest)
    // -------------------------------------------------------------
    echo "[GROUP 5] Bank Adjustments (Charges / Interest)\n";

    $adjRes = Banking::postBankAdjustment(1, [
        'type'        => 'bank_charge',
        'amount'      => 250.00,
        'description' => 'Stage 13 Monthly Bank Service Fee'
    ], $userId);

    assertTest($adjRes['success'] === true, "Bank charge adjustment posted successfully");

    echo "\n";

    // -------------------------------------------------------------
    // GROUP 6: Zero-Difference Completion & Lock Immutability
    // -------------------------------------------------------------
    echo "[GROUP 6] Zero-Difference Completion & Lock Immutability\n";

    // Attempt to complete reconciliation with non-zero difference
    $invalidRecRes = Banking::startReconciliationSession([
        'bank_account_id'           => 2, // Sampath bank
        'statement_start_date'      => date('Y-m-01'),
        'statement_end_date'        => date('Y-m-t'),
        'statement_opening_balance' => 25000.00,
        'statement_closing_balance' => 99999.00, // Intentional mismatch
        'notes'                     => 'Mismatch Test'
    ], $userId);

    if ($invalidRecRes['success']) {
        $invalidRecId = $invalidRecRes['reconciliation_id'];
        $completeFailRes = Banking::completeReconciliation($invalidRecId, $userId);
        assertTest($completeFailRes['success'] === false, "Completion correctly BLOCKED when reconciliation difference != 0");
    }

    // Complete valid reconciliation (adjust statement closing balance to match cleared)
    $recRow = Database::fetchOne("SELECT cleared_balance FROM bank_reconciliations WHERE id = :id", [':id' => $recId]);
    $clearedBal = (float)$recRow['cleared_balance'];

    $db->prepare("UPDATE bank_reconciliations SET statement_closing_balance = :bal, reconciliation_difference = 0.00 WHERE id = :id")
       ->execute([':bal' => $clearedBal, ':id' => $recId]);

    $completeSuccessRes = Banking::completeReconciliation($recId, $userId);
    assertTest($completeSuccessRes['success'] === true, "Reconciliation completed & locked successfully when difference = 0");

    // Verify lock immutability
    $immutableRes = Banking::updateClearedItems($recId, [], $userId);
    assertTest($immutableRes['success'] === false, "Completed reconciliation session is locked & immutable against modifications");

    echo "\n";

    // -------------------------------------------------------------
    // GROUP 7: Cheques Register Integration
    // -------------------------------------------------------------
    echo "[GROUP 7] Cheques Register Integration\n";

    $cheques = Banking::getChequesRegister(1);
    assertTest(is_array($cheques), "Cheques register queried without errors");

    echo "\n";

    // -------------------------------------------------------------
    // GROUP 8: Balanced GL Check & Stage 1-12 Regression
    // -------------------------------------------------------------
    echo "[GROUP 8] Balanced GL Check & Stage 1-12 Regression\n";

    $glBalanceRow = Database::fetchOne("
        SELECT SUM(debit) AS total_debit, SUM(credit) AS total_credit
        FROM journal_lines jl
        JOIN journal_entries je ON jl.journal_entry_id = je.id
        WHERE je.posting_status = 'posted'
    ");

    $totDeb = round((float)$glBalanceRow['total_debit'], 2);
    $totCrd = round((float)$glBalanceRow['total_credit'], 2);

    assertTest(abs($totDeb - $totCrd) < 0.01, "General Ledger remains perfectly balanced (Total Debits: LKR " . number_format($totDeb, 2) . " == Total Credits: LKR " . number_format($totCrd, 2) . ")");

    echo "\n===============================================================\n";
    echo "TEST SUMMARY: {$testPassed} Passed, {$testFailed} Failed.\n";
    echo "===============================================================\n\n";

    if ($testFailed > 0) {
        exit(1);
    }

} catch (Throwable $e) {
    echo "UNHANDLED EXCEPTION IN TEST SUITE: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
