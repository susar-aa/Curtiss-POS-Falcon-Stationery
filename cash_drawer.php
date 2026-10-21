<?php
/**
 * Curtiss POS - Cash Drawer & Day Closing Controller
 * Clean MVC controller invoking View::render('cash_drawer/index', ...)
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/BusinessDay.php';

defined('ROLE_SUPER_ADMIN') or define('ROLE_SUPER_ADMIN', 1);
defined('ROLE_ADMIN') or define('ROLE_ADMIN', 2);
defined('ROLE_MANAGER') or define('ROLE_MANAGER', 3);
defined('ROLE_CASHIER') or define('ROLE_CASHIER', 4);
defined('ROLE_ACCOUNTANT') or define('ROLE_ACCOUNTANT', 5);
defined('ROLE_SPECIAL_USER') or define('ROLE_SPECIAL_USER', 6);

Auth::requireAuth();
if (!Auth::hasPermission('view_cash_drawer') && !Auth::hasPermission('start_business_day')) {
    View::renderBlank('errors/403');
    exit;
}

$user = Auth::user();
$userId = (int)($user['id'] ?? 1);
$isSuperAdmin = Auth::hasRole('superadmin') || Auth::isSuperAdmin();
$branchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? $user['active_branch_id'] ?? 1);
if ($isSuperAdmin && !empty($_GET['branch_id'])) {
    $branchId = (int)$_GET['branch_id'];
}

$allowedLimits = [25, 50, 75, 100, 500];
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 25;
if (!in_array($limit, $allowedLimits, true)) {
    $limit = 25;
}
$page = max(1, (int)($_GET['page'] ?? 1));

$filters = [
    'search'    => trim($_GET['search'] ?? ''),
    'date_from' => trim($_GET['date_from'] ?? ''),
    'date_to'   => trim($_GET['date_to'] ?? '')
];
if ($isSuperAdmin && !empty($_GET['all_branches'])) {
    $filters['all_branches'] = true;
}

$activeDay = BusinessDay::getOrCreateActiveDay($branchId, $userId);
$activeSummary = $activeDay ? BusinessDay::getDrawerSummary((int)$activeDay['id']) : null;
$comprehensiveSummary = $activeDay ? BusinessDay::getDayComprehensiveSummary((int)$activeDay['id']) : null;
$movements = $activeDay ? BusinessDay::getDayMovements((int)$activeDay['id']) : [];
require_once __DIR__ . '/core/Accounting.php';
$cashAccounts = Accounting::getCashAccounts();
$bankAccounts = Database::fetchAll("SELECT id, bank_name, account_name, account_number FROM bank_accounts WHERE deleted_at IS NULL ORDER BY bank_name ASC");

View::render('cash_drawer/index', [
    'activeDay'            => $activeDay,
    'activeSummary'        => $activeSummary,
    'comprehensiveSummary' => $comprehensiveSummary,
    'movements'            => $movements,
    'closedDays'           => $closedDays,
    'bankAccounts'         => $bankAccounts,
    'cashAccounts'         => $cashAccounts,
    'branchId'             => $branchId,
    'limit'                => $limit,
    'page'                 => $page,
    'totalClosed'          => (int)($closedDays['totalItems'] ?? 0)
], 'Cash Drawer & Day Closing - Curtiss POS');