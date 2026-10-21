<?php
/**
 * Curtiss POS - Centralized Expense Management Controller
 * 
 * Clean MVC controller delegating view rendering to views/expenses/index.php.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Expense.php';
require_once __DIR__ . '/core/Accounting.php';
require_once __DIR__ . '/core/Banking.php';
require_once __DIR__ . '/core/MaskingHelper.php';
require_once __DIR__ . '/core/View.php';

Auth::requireLogin();
if (!Auth::hasPermission('expenses.view')) {
    header('Location: dashboard');
    exit;
}

$user     = Auth::user();
$userId   = (int)$user['id'];
$branchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? 1);

// Master data
$categories         = Expense::getCategories(true);
$eligibleAccounts   = Expense::getEligiblePaymentAccounts($branchId);
$coaExpenseAccounts = Accounting::getCOA(['type' => 'expense', 'status' => 'active']);
$branches           = Database::fetchAll("SELECT id, name, code FROM branches WHERE status = 'active' ORDER BY id ASC");
$stats              = Expense::getDashboardSummary($branchId);

View::render('expenses/index', [
    'user'               => $user,
    'userId'             => $userId,
    'branchId'           => $branchId,
    'categories'         => $categories,
    'eligibleAccounts'   => $eligibleAccounts,
    'coaExpenseAccounts' => $coaExpenseAccounts,
    'branches'           => $branches,
    'stats'              => $stats
], 'Expense Management - Curtiss POS');
