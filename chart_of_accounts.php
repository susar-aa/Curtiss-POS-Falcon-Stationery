<?php
/**
 * Curtiss POS - Chart of Accounts Controller (Stage 10 Redesign)
 * Delegates view rendering to views/chart_of_accounts/index.php.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Accounting.php';

Auth::requireLogin();
if (!Auth::hasPermission('accounting.view') || Auth::isSpecialUser()) {
    header('Location: dashboard');
    exit;
}

$user = Auth::user();
$userId = (int)$user['id'];
$branchId = (int)($_SESSION['branch_id'] ?? 1);
$coaAccounts = Accounting::getCOA();

View::render('chart_of_accounts/index', [
    'user'        => $user,
    'userId'      => $userId,
    'branchId'    => $branchId,
    'coaAccounts' => $coaAccounts
], 'Chart of Accounts - Curtiss POS');
