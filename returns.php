<?php
/**
 * Curtiss POS - Sales Returns & Exchanges Controller (Stage 8)
 * Clean controller passing data to views/returns/index.php via standard View::render system.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/SaleReturn.php';
require_once __DIR__ . '/core/Customer.php';

if (!defined('ROLE_SUPER_ADMIN')) define('ROLE_SUPER_ADMIN', 'super_admin');
if (!defined('ROLE_ADMIN')) define('ROLE_ADMIN', 'admin');
if (!defined('ROLE_MANAGER')) define('ROLE_MANAGER', 'manager');
if (!defined('ROLE_ACCOUNTANT')) define('ROLE_ACCOUNTANT', 'accountant');
if (!defined('ROLE_CASHIER')) define('ROLE_CASHIER', 'cashier');
if (!defined('ROLE_SPECIAL_USER')) define('ROLE_SPECIAL_USER', 'special_user');

Auth::requireLogin();
if (!Auth::hasPermission('returns.view') && !Auth::hasPermission('returns.create') && !Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER, ROLE_CASHIER)) {
    header('Location: dashboard');
    exit;
}

$user = Auth::user();
$branchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? $user['active_branch_id'] ?? 1);

// Superadmin dynamic branch switching
if (Auth::hasRole(ROLE_SUPER_ADMIN) && isset($_GET['branch_id'])) {
    if ($_GET['branch_id'] === 'all' || (int)$_GET['branch_id'] === 0) {
        $branchId = 0;
        $_SESSION['branch_id'] = 0;
    } else {
        $branchId = (int)$_GET['branch_id'];
        $_SESSION['branch_id'] = $branchId;
    }
}

$page = max(1, (int)($_GET['page'] ?? 1));
$requestedLimit = (int)($_GET['limit'] ?? 25);
$limit = in_array($requestedLimit, [25, 50, 75, 100, 500], true) ? $requestedLimit : 25;

$search = trim($_GET['search'] ?? '');
$typeFilter = trim($_GET['return_type'] ?? '');

$returnsData = SaleReturn::all(['branch_id' => $branchId, 'search' => $search, 'return_type' => $typeFilter], $page, $limit);
$returnsList = $returnsData['items'];
$totalItems = $returnsData['totalItems'];
$totalPages = $returnsData['totalPages'];

$customers = Customer::all(['status' => 'active'], 1, 100)['items'];
$bankAccounts = Database::fetchAll("SELECT id, bank_name, account_name, account_number FROM bank_accounts WHERE status = 'active' ORDER BY bank_name ASC");

View::render('returns/index', [
    'user'         => $user,
    'branchId'     => $branchId,
    'page'         => $page,
    'limit'        => $limit,
    'totalItems'   => $totalItems,
    'search'       => $search,
    'typeFilter'   => $typeFilter,
    'returnsList'  => $returnsList,
    'totalPages'   => $totalPages,
    'customers'    => $customers,
    'bankAccounts' => $bankAccounts
], 'Sales Returns & Exchanges - Curtiss POS');
