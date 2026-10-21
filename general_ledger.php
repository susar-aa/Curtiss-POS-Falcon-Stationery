<?php
/**
 * Curtiss POS - General Ledger Controller (Stage 10 Redesign)
 * Delegates view rendering to views/general_ledger/index.php.
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
$branchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? 1);
$coaAccounts = Accounting::getCOA(['status' => 'active']);
$branches = Database::fetchAll("SELECT id, name, code FROM branches WHERE status = 'active' ORDER BY id ASC");

View::render('general_ledger/index', [
    'user'        => $user,
    'userId'      => $userId,
    'branchId'    => $branchId,
    'coaAccounts' => $coaAccounts,
    'branches'    => $branches
], 'General Ledger - Curtiss POS');
