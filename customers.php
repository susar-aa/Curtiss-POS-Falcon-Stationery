<?php
/**
 * Curtiss POS - Customers Main Controller (Stage 5 & Stage 17 Special User Integration)
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Customer.php';
require_once __DIR__ . '/core/Accounting.php';
require_once __DIR__ . '/core/MaskingHelper.php';

Auth::requireAuth();

if (!Auth::hasPermission('customers.view')) {
    View::renderBlank('errors/403');
    exit();
}

$errorMsg = $_SESSION['flash_error'] ?? null;
$successMsg = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_error'], $_SESSION['flash_success']);

// Status toggle handler (Restricted for Special User)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle') {
    if (!Auth::hasPermission('customers.status') || Auth::isSpecialUser()) {
        $errorMsg = 'You do not have permission to modify customer status.';
    } else {
        $id = (int)($_POST['id'] ?? 0);
        $res = Customer::toggleStatus($id);
        if ($res['success']) {
            $successMsg = $res['message'];
        } else {
            $errorMsg = $res['message'];
        }
    }
}

// Delete action handler (Restricted for Special User)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (!Auth::hasPermission('customers.delete') || Auth::isSpecialUser()) {
        $errorMsg = 'You do not have permission to delete customers.';
    } else {
        $id = (int)($_POST['id'] ?? 0);
        $res = Customer::delete($id);
        if ($res['success']) {
            $successMsg = $res['message'];
        } else {
            $errorMsg = $res['message'];
        }
    }
}

// Restore action handler (Restricted for Special User)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'restore') {
    if (!Auth::hasPermission('customers.delete') || Auth::isSpecialUser()) {
        $errorMsg = 'You do not have permission to restore customers.';
    } else {
        $id = (int)($_POST['id'] ?? 0);
        $res = Customer::restore($id);
        if ($res['success']) {
            $successMsg = $res['message'];
        } else {
            $errorMsg = $res['message'];
        }
    }
}

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$view = trim($_GET['view'] ?? '');
$allowedLimits = [25, 50, 75, 100, 500];
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 25;
if (!in_array($limit, $allowedLimits, true)) {
    $limit = 25;
}
$page = max(1, (int)($_GET['page'] ?? 1));

$customerData = Customer::all([
    'search' => $search,
    'status' => $status,
    'view'   => $view
], $page, $limit);

$stats = Customer::getSummaryStats();

if (Auth::isSpecialUser()) {
    foreach ($customerData['items'] as &$c) {
        if (isset($c['credit_balance'])) {
            $c['credit_balance'] = MaskingHelper::maskValue((float)$c['credit_balance']);
        }
    }
    if (isset($stats['total_outstanding'])) {
        $stats['total_outstanding'] = MaskingHelper::maskValue((float)$stats['total_outstanding']);
    }
    if (isset($stats['total_credit_limit'])) {
        $stats['total_credit_limit'] = MaskingHelper::maskValue((float)$stats['total_credit_limit']);
    }
}

$canPay = Auth::hasPermission('customers.pay') && !Auth::isSpecialUser();

// Fetch active bank accounts and all cash accounts for customer collection payment options
$bankAccounts = Database::fetchAll("SELECT * FROM bank_accounts WHERE status = 'active' ORDER BY bank_name ASC");
$cashAccounts = Accounting::getCashAccounts();

View::render('customers/customer_list', [
    'customers'    => $customerData['items'],
    'totalItems'   => $customerData['totalItems'],
    'page'         => $customerData['page'],
    'limit'        => $limit,
    'totalPages'   => $customerData['totalPages'],
    'search'       => $search,
    'status'       => $status,
    'view'         => $view,
    'stats'        => $stats,
    'canPay'       => $canPay,
    'bankAccounts' => $bankAccounts,
    'cashAccounts' => $cashAccounts,
    'errorMsg'     => $errorMsg,
    'successMsg'   => $successMsg
], 'Customers & Credit - Curtiss POS');
