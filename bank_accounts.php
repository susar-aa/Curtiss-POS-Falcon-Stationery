<?php
/**
 * Curtiss POS - Bank Accounts & Cash Deposits Controller (Stage 13)
 * Clean controller delegating view rendering to views/bank_accounts/index.php.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Banking.php';
require_once __DIR__ . '/core/BusinessDay.php';

Auth::requireLogin();
if (!Auth::hasPermission('view_bank_accounts')) {
    header('Location: dashboard');
    exit;
}

$user = Auth::user();
$userId = (int)$user['id'];
$branchId = (int)($_SESSION['branch_id'] ?? 1);

$bankAccounts = Banking::getBankAccounts(null, false);
$activeDay = BusinessDay::getOrCreateActiveDay($branchId, $userId);
$closedDays = Database::fetchAll("SELECT id, business_date, cash_to_deposit, closed_at FROM business_days WHERE branch_id = :b AND status = 'closed' ORDER BY id DESC LIMIT 30", [':b' => $branchId]);
$branches = Database::fetchAll("SELECT id, name FROM branches WHERE status = 'active' ORDER BY name ASC");

$totalBankFunds = 0.00;
$activeAccountCount = 0;
foreach ($bankAccounts as $ba) {
    if ($ba['status'] === 'active') {
        $totalBankFunds += (float)$ba['current_balance'];
        $activeAccountCount++;
    }
}

$registerAccountId = (int)($_GET['register_id'] ?? $_GET['account_id'] ?? 0);
$canExport = Auth::hasPermission('export_reports');

View::render('bank_accounts/index', [
    'user'               => $user,
    'userId'             => $userId,
    'branchId'           => $branchId,
    'bankAccounts'       => $bankAccounts,
    'activeDay'          => $activeDay,
    'closedDays'         => $closedDays,
    'branches'           => $branches,
    'totalBankFunds'     => $totalBankFunds,
    'activeAccountCount' => $activeAccountCount,
    'registerAccountId'  => $registerAccountId,
    'canExport'          => $canExport
], 'Bank Accounts & Cash Deposits - Curtiss POS');
