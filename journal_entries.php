<?php
/**
 * Curtiss POS - Journal Entries Controller (Stage 10 Redesign)
 * Delegates view rendering to views/journal_entries/index.php.
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
$coaAccounts = Accounting::getCOA(['status' => 'active']);
$branches = Database::fetchAll("SELECT id, name, code FROM branches WHERE status = 'active' ORDER BY id ASC");

View::render('journal_entries/index', [
    'user'        => $user,
    'userId'      => $userId,
    'branchId'    => $branchId,
    'coaAccounts' => $coaAccounts,
    'branches'    => $branches
], 'Journal Entries - Curtiss POS');
