<?php
/**
 * Curtiss POS - Sales Returns & Exchanges Controller (Stage 8)
 * Clean controller passing data to views/returns/index.php via standard View::render system.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/SaleReturn.php';
require_once __DIR__ . '/core/Customer.php';

Auth::requireLogin();
if (!Auth::hasPermission('returns.view') && !Auth::hasPermission('returns.create')) {
    header('Location: dashboard');
    exit;
}

$user = Auth::user();
$branchId = (int)($_SESSION['branch_id'] ?? 1);

$page = (int)($_GET['page'] ?? 1);
$search = trim($_GET['search'] ?? '');
$typeFilter = trim($_GET['return_type'] ?? '');

$returnsData = SaleReturn::all(['branch_id' => $branchId, 'search' => $search, 'return_type' => $typeFilter], $page, 15);
$returnsList = $returnsData['items'];
$totalPages = $returnsData['totalPages'];

$customers = Customer::all(['status' => 'active'], 1, 100)['items'];
$bankAccounts = Database::fetchAll("SELECT id, bank_name, account_name, account_number FROM bank_accounts WHERE status = 'active' ORDER BY bank_name ASC");

View::render('returns/index', [
    'user'         => $user,
    'branchId'     => $branchId,
    'page'         => $page,
    'search'       => $search,
    'typeFilter'   => $typeFilter,
    'returnsList'  => $returnsList,
    'totalPages'   => $totalPages,
    'customers'    => $customers,
    'bankAccounts' => $bankAccounts
], 'Sales Returns & Exchanges - Curtiss POS');
