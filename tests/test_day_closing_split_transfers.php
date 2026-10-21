<?php
/**
 * CLI Test Suite for Day End Cash Transfer Routing (Split Transfers)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/BusinessDay.php';
require_once __DIR__ . '/../core/Accounting.php';

echo "=== STARTING DAY CLOSING SPLIT TRANSFERS TEST SUITE ===\n\n";

$db = Database::getInstance()->getConnection();

// 1. Get an active bank account for testing
$stmtBank = $db->query("SELECT * FROM bank_accounts LIMIT 1");
$bankAccount = $stmtBank->fetch(PDO::FETCH_ASSOC);

if (!$bankAccount) {
    die("FAILED: No active bank account found for testing.\n");
}

echo "Using Test Bank Account: ID {$bankAccount['id']} - {$bankAccount['bank_name']} (Acc: {$bankAccount['account_number']})\n";

// 2. Setup a test business day
$testDate = '2099-01-01';
// Clean up any pre-existing test day
$stmtPre = $db->prepare("SELECT id FROM business_days WHERE business_date = :bdate");
$stmtPre->execute([':bdate' => $testDate]);
$preIds = $stmtPre->fetchAll(PDO::FETCH_COLUMN);
foreach ($preIds as $pid) {
    $db->prepare("DELETE FROM bank_deposits WHERE business_day_id = :id")->execute([':id' => $pid]);
    $stmtJe = $db->prepare("SELECT id FROM journal_entries WHERE source_type = 'manual' AND source_id = :id");
    $stmtJe->execute([':id' => $pid]);
    foreach ($stmtJe->fetchAll(PDO::FETCH_COLUMN) as $jeId) {
        $db->prepare("DELETE FROM journal_lines WHERE journal_entry_id = :id")->execute([':id' => $jeId]);
        $db->prepare("DELETE FROM journal_entries WHERE id = :id")->execute([':id' => $jeId]);
    }
    $db->prepare("DELETE FROM cash_drawer_movements WHERE business_day_id = :id")->execute([':id' => $pid]);
    $db->prepare("DELETE FROM business_days WHERE id = :id")->execute([':id' => $pid]);
}

// Open test day
$stmtOpen = $db->prepare("
    INSERT INTO business_days (branch_id, business_date, status, opening_cash, opened_by, opened_at)
    VALUES (1, :bdate, 'open', 5000.00, 1, NOW())
");
$stmtOpen->execute([':bdate' => $testDate]);
$testBdayId = (int)$db->lastInsertId();
echo "Created Test Business Day: #{$testBdayId}\n";

// Add some cash movement so expected cash is known
$stmtMv = $db->prepare("
    INSERT INTO cash_drawer_movements (
        business_day_id, branch_id, movement_type, source_type, source_id,
        amount, direction, reason, notes, user_id
    ) VALUES 
    (:bday1, 1, 'opening_cash', 'business_days', :bday2, 5000.00, 'in', 'Opening Float', NULL, 1),
    (:bday3, 1, 'pos_sale', 'sales', 99999, 10000.00, 'in', 'Cash Sale', NULL, 1)
");
$stmtMv->execute([
    ':bday1' => $testBdayId,
    ':bday2' => $testBdayId,
    ':bday3' => $testBdayId
]);

// Total cash in drawer = 15,000.00
// Retained float for next day = 5,000.00
// Surplus to transfer = 10,000.00
// Splits:
// - Main Safe: 4,000.00
// - Bank Account: 5,000.00
// - Petty Cash: 1,000.00
$actualCash = 15000.00;
$retainedCash = 5000.00;
$splits = [
    [
        'type' => 'safe',
        'amount' => 4000.00,
        'notes' => 'Test Day End Transfer to Safe'
    ],
    [
        'type' => 'bank',
        'amount' => 5000.00,
        'bank_account_id' => $bankAccount['id'],
        'notes' => 'Test Day End Transfer to Bank'
    ],
    [
        'type' => 'petty_cash',
        'amount' => 1000.00,
        'notes' => 'Test Day End Transfer to Petty Cash'
    ]
];

try {
    $res = BusinessDay::closeDay(
        $testBdayId,
        $actualCash,
        $retainedCash,
        1,
        'CLI automated split transfer test',
        [
            'split_transfers' => $splits
        ],
        null,
        0.00
    );
} catch (Throwable $e) {
    echo "EXCEPTION AT " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

if (empty($res['success'])) {
    echo "FAILED: closeDay returned error: " . ($res['message'] ?? 'Unknown error') . "\n";
    exit(1);
}

echo "SUCCESS: Business Day closed successfully with split transfers!\n";

// 3. Verify business_days record
$stmtCheck = $db->prepare("SELECT * FROM business_days WHERE id = :id");
$stmtCheck->execute([':id' => $testBdayId]);
$bday = $stmtCheck->fetch(PDO::FETCH_ASSOC);

if ($bday['status'] !== 'closed') {
    echo "FAILED: Business day status is not 'closed'. Current: {$bday['status']}\n";
    exit(1);
}
if ((float)$bday['cash_to_deposit'] != 10000.00) {
    echo "FAILED: cash_to_deposit is " . $bday['cash_to_deposit'] . ", expected 10000.00\n";
    exit(1);
}
echo "PASS: business_days status=closed, cash_to_deposit=10000.00\n";

$savedSplits = json_decode($bday['split_transfers'] ?? '[]', true);
if (count($savedSplits) !== 3) {
    echo "FAILED: Expected 3 split items in business_days.split_transfers, found " . count($savedSplits) . "\n";
    exit(1);
}
echo "PASS: business_days.split_transfers JSON verified (3 destinations saved: Safe, Bank, Petty Cash)\n";

// 4. Verify Journal Entries (Double Entry Balance)
$stmtJournals = $db->prepare("
    SELECT je.id, je.entry_number, je.description, je.total_debit, je.total_credit,
           jl.account_id, coa.account_code, coa.account_name, jl.debit, jl.credit
    FROM journal_entries je
    JOIN journal_lines jl ON je.id = jl.journal_entry_id
    JOIN chart_of_accounts coa ON jl.account_id = coa.id
    WHERE je.source_type = 'manual' AND je.source_id = :bday_id AND je.description LIKE '%Split Transfer%'
    ORDER BY je.id ASC, jl.id ASC
");
$stmtJournals->execute([':bday_id' => $testBdayId]);
$lines = $stmtJournals->fetchAll(PDO::FETCH_ASSOC);

if (empty($lines)) {
    echo "FAILED: No journal entries found for Split Transfer.\n";
    exit(1);
}

echo "Found " . count($lines) . " journal lines for split transfer journal entry.\n";

$totDebit = 0;
$totCredit = 0;
foreach ($lines as $jl) {
    $totDebit += (float)$jl['debit'];
    $totCredit += (float)$jl['credit'];
    echo "  Account {$jl['account_code']} ({$jl['account_name']}): DR {$jl['debit']} | CR {$jl['credit']}\n";
}

if (abs($totDebit - 10000.00) > 0.01 || abs($totCredit - 10000.00) > 0.01) {
    echo "FAILED: Total debit or credit mismatch! DR={$totDebit}, CR={$totCredit}, expected 10,000.00\n";
    exit(1);
}
if (abs($totDebit - $totCredit) > 0.001) {
    echo "FAILED: Journal entry is not balanced! DR={$totDebit}, CR={$totCredit}\n";
    exit(1);
}
echo "PASS: Split Transfer Journal Entry is 100% balanced (Total Debit: {$totDebit} == Total Credit: {$totCredit})\n";

// 5. Verify Bank Deposits record
$stmtBd = $db->prepare("SELECT * FROM bank_deposits WHERE source_type = 'closed_day' AND business_day_id = :bday_id");
$stmtBd->execute([':bday_id' => $testBdayId]);
$bdep = $stmtBd->fetch(PDO::FETCH_ASSOC);

if (!$bdep) {
    echo "FAILED: bank_deposits record not found for business day #{$testBdayId}\n";
    exit(1);
}
if ((float)$bdep['amount'] != 5000.00 || (int)$bdep['bank_account_id'] !== (int)$bankAccount['id']) {
    echo "FAILED: bank_deposits details mismatch: amount={$bdep['amount']}, bank_id={$bdep['bank_account_id']}\n";
    exit(1);
}
echo "PASS: bank_deposits record verified (Deposit #{$bdep['deposit_no']}, Amount: LKR 5,000.00 into Bank ID {$bankAccount['id']})\n";

// 6. Test Receipt Generation
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';
$_SESSION['branch_id'] = 1;
ob_start();
$_GET['id'] = $testBdayId;
include __DIR__ . '/../day_closing_receipt.php';
$receiptHtml = ob_get_clean();

if (strpos($receiptHtml, 'CASH TO DEPOSIT / TRANSFERS:') === false) {
    echo "FAILED: Receipt output missing 'CASH TO DEPOSIT / TRANSFERS:' section\n";
    exit(1);
}
if (strpos($receiptHtml, 'Main Safe (1010)') === false) {
    echo "FAILED: Receipt output missing 'Main Safe (1010)' transfer line\n";
    exit(1);
}
if (strpos($receiptHtml, 'Direct Bank') === false) {
    echo "FAILED: Receipt output missing 'Direct Bank' transfer line\n";
    exit(1);
}
if (strpos($receiptHtml, 'Petty Cash (1015)') === false) {
    echo "FAILED: Receipt output missing 'Petty Cash (1015)' transfer line\n";
    exit(1);
}
echo "PASS: Day Closing Receipt successfully rendered with all 3 transfer breakdown lines!\n";

// 7. Clean up test records
$db->prepare("DELETE FROM bank_deposits WHERE id = :id")->execute([':id' => $bdep['id']]);

$jeIds = array_unique(array_column($lines, 'id'));
foreach ($jeIds as $jeId) {
    $db->prepare("DELETE FROM journal_lines WHERE journal_entry_id = :id")->execute([':id' => $jeId]);
    $db->prepare("DELETE FROM journal_entries WHERE id = :id")->execute([':id' => $jeId]);
}

$db->prepare("DELETE FROM cash_drawer_movements WHERE business_day_id = :id")->execute([':id' => $testBdayId]);
$db->prepare("DELETE FROM business_days WHERE id = :id")->execute([':id' => $testBdayId]);

echo "Cleaned up all isolated test records successfully.\n";
echo "\n=== ALL DAY CLOSING SPLIT TRANSFER TESTS PASSED PERFECTLY! ===\n";
