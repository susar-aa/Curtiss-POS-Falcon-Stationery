<?php
/**
 * Curtiss POS - Petty Cash & Expense Management Controller (Stage 12 Redesign & Optimization)
 * Clean MVC controller delegating view rendering to views/petty_cash/index.php.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/PettyCash.php';
require_once __DIR__ . '/core/Accounting.php';
require_once __DIR__ . '/core/Banking.php';
require_once __DIR__ . '/core/MaskingHelper.php';

Auth::requireLogin();
if (!Auth::hasPermission('view_petty_cash')) {
    header('Location: dashboard');
    exit;
}

$user = Auth::user();
$userId = (int)$user['id'];
$branchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? 1);

$summary = PettyCash::getDashboardSummary($branchId);
$categories = PettyCash::getCategories(true);
$allCategories = PettyCash::getCategories(false);
$deletedCategories = PettyCash::getDeletedCategories();
$vouchersRes = PettyCash::getVouchersList($branchId, [], 1, 50);
$reimbursements = PettyCash::getReimbursementsList($branchId, 50);
$adjustments = PettyCash::getAdjustmentsList($branchId, 50);
$coaExpenseAccounts = Accounting::getCOA(['type' => 'expense', 'status' => 'active']);
$bankAccounts = Banking::getBankAccounts(null, true);
$cashAccounts = Accounting::getCashAccounts();

$branches = Database::fetchAll("SELECT id, name, code FROM branches WHERE status = 'active' ORDER BY id ASC");
$accountTransactions = PettyCash::getAccountTransactions($branchId);
$pcAccount = Database::fetchOne("SELECT id, account_code, account_name, account_type FROM chart_of_accounts WHERE account_code = :code", [':code' => Accounting::CODE_PETTY_CASH]);

if (MaskingHelper::isSpecialUser()) {
    $summary['current_balance']      = MaskingHelper::maskValue($summary['current_balance']);
    $summary['total_expenses']       = MaskingHelper::maskValue($summary['total_expenses']);
    $summary['total_reimbursements'] = MaskingHelper::maskValue($summary['total_reimbursements']);
    $summary['net_adjustments']      = MaskingHelper::maskValue($summary['net_adjustments']);

    if (!empty($accountTransactions['transactions'])) {
        foreach ($accountTransactions['transactions'] as &$tx) {
            $tx['debit']           = MaskingHelper::maskValue((float)$tx['debit']);
            $tx['credit']          = MaskingHelper::maskValue((float)$tx['credit']);
            $tx['running_balance'] = MaskingHelper::maskValue((float)$tx['running_balance']);
        }
        unset($tx);
    }
    if (!empty($accountTransactions['summary'])) {
        $accountTransactions['summary']['current_balance'] = MaskingHelper::maskValue((float)$accountTransactions['summary']['current_balance']);
        $accountTransactions['summary']['total_inflow']    = MaskingHelper::maskValue((float)$accountTransactions['summary']['total_inflow']);
        $accountTransactions['summary']['total_outflow']   = MaskingHelper::maskValue((float)$accountTransactions['summary']['total_outflow']);
    }
    if (!empty($vouchersRes['items'])) {
        foreach ($vouchersRes['items'] as &$v) {
            $v['amount'] = MaskingHelper::maskValue((float)$v['amount']);
        }
        unset($v);
    }
    if (!empty($reimbursements)) {
        foreach ($reimbursements as &$r) {
            $r['amount'] = MaskingHelper::maskValue((float)$r['amount']);
        }
        unset($r);
    }
    if (!empty($adjustments)) {
        foreach ($adjustments as &$a) {
            $a['amount'] = MaskingHelper::maskValue((float)$a['amount']);
        }
        unset($a);
    }
}

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
    'deletedCategories'   => $deletedCategories,
    'vouchersRes'         => $vouchersRes,
    'reimbursements'      => $reimbursements,
    'adjustments'         => $adjustments,
    'coaExpenseAccounts'  => $coaExpenseAccounts,
    'bankAccounts'        => $bankAccounts,
    'cashAccounts'        => $cashAccounts
], 'Petty Cash & Expense Management - Curtiss POS');
