<?php
/**
 * Curtiss POS - Petty Cash & Expense Management Controller (Stage 12 Redesign)
 * Clean MVC controller delegating view rendering to views/petty_cash/index.php.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/PettyCash.php';
require_once __DIR__ . '/core/Accounting.php';

Auth::requireLogin();
if (!Auth::hasPermission('view_petty_cash')) {
    header('Location: dashboard');
    exit;
}

$user = Auth::user();
$userId = (int)$user['id'];
$branchId = (int)($_SESSION['branch_id'] ?? 1);

$summary = PettyCash::getDashboardSummary($branchId);
$categories = PettyCash::getCategories(true);
$allCategories = PettyCash::getCategories(false);
$vouchersRes = PettyCash::getVouchersList($branchId, [], 1, 50);
$reimbursements = PettyCash::getReimbursementsList($branchId, 50);
$adjustments = PettyCash::getAdjustmentsList($branchId, 50);
$coaExpenseAccounts = Accounting::getCOA(['type' => 'expense', 'status' => 'active']);

$branches = Database::fetchAll("SELECT id, name, code FROM branches WHERE status = 'active' ORDER BY id ASC");
$accountTransactions = PettyCash::getAccountTransactions($branchId);
$pcAccount = Database::fetchOne("SELECT id, account_code, account_name, account_type FROM chart_of_accounts WHERE account_code = :code", [':code' => Accounting::CODE_PETTY_CASH]);

View::render('petty_cash/index', [
    'user'                => $user,
    'userId'              => $userId,
    'branchId'            => $branchId,
    'branches'            => $branches,
    'summary'             => $summary,
    'accountTransactions' => $accountTransactions,
    'pcAccount'           => $pcAccount,
    'categories'          => $categories,
    'allCategories'       => $allCategories,
    'vouchersRes'         => $vouchersRes,
    'reimbursements'      => $reimbursements,
    'adjustments'         => $adjustments,
    'coaExpenseAccounts'  => $coaExpenseAccounts
], 'Petty Cash & Expense Management - Curtiss POS');
