<?php
/**
 * Curtiss POS - Bank Reconciliation Controller (Stage 13 Redesign)
 * Delegates view rendering to views/bank_reconciliation/index.php.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Banking.php';
require_once __DIR__ . '/core/Accounting.php';

Auth::requireLogin();
if (!Auth::hasPermission('manage_bank_reconciliation')) {
    header('Location: dashboard');
    exit;
}

$user = Auth::user();
$userId = (int)$user['id'];
$branchId = (int)($_SESSION['branch_id'] ?? 1);

$bankAccounts = Banking::getBankAccounts(null, true);
$selectedBankId = (int)($_GET['bank_account_id'] ?? ($bankAccounts[0]['id'] ?? 0));

$selectedBankAccount = null;
foreach ($bankAccounts as $ba) {
    if ((int)$ba['id'] === $selectedBankId) {
        $selectedBankAccount = $ba;
        break;
    }
}
if (!$selectedBankAccount && !empty($bankAccounts)) {
    $selectedBankAccount = $bankAccounts[0];
    $selectedBankId = (int)$selectedBankAccount['id'];
}

$activeSession = null;
$sessionItems = [];
if ($selectedBankId > 0) {
    $activeSession = Database::fetchOne("
        SELECT br.*, ba.bank_name, ba.account_name, ba.account_number
        FROM bank_reconciliations br
        JOIN bank_accounts ba ON br.bank_account_id = ba.id
        WHERE br.bank_account_id = :bank_acc AND br.status = 'draft'
        ORDER BY br.id DESC LIMIT 1
    ", [':bank_acc' => $selectedBankId]);

    if ($activeSession) {
        $sessionItems = Database::fetchAll("
            SELECT bri.id AS item_id, bri.is_cleared, bri.cleared_date,
                   jl.id AS journal_line_id, jl.debit, jl.credit, jl.memo,
                   je.entry_number, je.entry_date, je.description, je.source_type
            FROM bank_reconciliation_items bri
            JOIN journal_lines jl ON bri.journal_line_id = jl.id
            JOIN journal_entries je ON jl.journal_entry_id = je.id
            WHERE bri.reconciliation_id = :rec_id
            ORDER BY je.entry_date ASC, jl.id ASC
        ", [':rec_id' => $activeSession['id']]);
    }
}

// Fetch historical completed reconciliations for this bank
$completedSessions = Database::fetchAll("
    SELECT br.*, u.username AS reconciler_name
    FROM bank_reconciliations br
    LEFT JOIN users u ON br.reconciled_by = u.id
    WHERE br.bank_account_id = :bank_acc AND br.status = 'completed'
    ORDER BY br.id DESC LIMIT 20
", [':bank_acc' => $selectedBankId]);

$expenseAccounts = Accounting::getCOA(['type' => 'expense', 'status' => 'active']);
$incomeAccounts = Accounting::getCOA(['type' => 'revenue', 'status' => 'active']);

View::render('bank_reconciliation/index', [
    'user'                => $user,
    'userId'              => $userId,
    'branchId'            => $branchId,
    'bankAccounts'        => $bankAccounts,
    'selectedBankId'      => $selectedBankId,
    'selectedBankAccount' => $selectedBankAccount,
    'activeSession'       => $activeSession,
    'sessionItems'        => $sessionItems,
    'completedSessions'   => $completedSessions,
    'expenseAccounts'     => $expenseAccounts,
    'incomeAccounts'      => $incomeAccounts
], 'Bank Reconciliation - Curtiss POS');
