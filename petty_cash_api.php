<?php
/**
 * Curtiss POS - Petty Cash & Expense Management API Endpoint (Stage 12)
 * Handles Petty Cash balance queries, expense vouchers creation, category management,
 * reimbursements, manual adjustments, and voucher reversals.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/PettyCash.php';

Auth::requireLogin();
if (Auth::isSpecialUser()) {
    echo json_encode(['success' => false, 'message' => 'Permission denied: Special User is view-only.']);
    exit;
}
$user = Auth::user();
$userId = (int)$user['id'];
$branchId = (int)($_SESSION['branch_id'] ?? 1);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {

        case 'get_summary':
            if (!Auth::hasPermission('view_petty_cash')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to view petty cash.']);
                exit;
            }
            $summary = PettyCash::getDashboardSummary($branchId);
            echo json_encode(['success' => true, 'data' => $summary]);
            break;

        case 'get_account_transactions':
            if (!Auth::hasPermission('view_petty_cash')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to view petty cash.']);
                exit;
            }
            $reqBranch = $_GET['branch_id'] ?? $branchId;
            $search = trim($_GET['search'] ?? '');
            $res = PettyCash::getAccountTransactions($reqBranch, $search);
            echo json_encode(['success' => true, 'data' => $res]);
            break;

        case 'get_categories':
            $activeOnly = isset($_GET['all']) && $_GET['all'] === '1' ? false : true;
            $cats = PettyCash::getCategories($activeOnly);
            echo json_encode(['success' => true, 'data' => $cats]);
            break;

        case 'save_category':
            if (!Auth::hasPermission('manage_expense_categories')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to manage expense categories.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $res = PettyCash::saveCategory($input, $userId);
            echo json_encode($res);
            break;

        case 'create_voucher':
            if (!Auth::hasPermission('create_expense')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to create expense vouchers.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $res = PettyCash::createVoucher($input, $userId, $branchId);
            echo json_encode($res);
            break;

        case 'reverse_voucher':
            if (!Auth::hasPermission('reverse_petty_cash_voucher')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to reverse expense vouchers.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $voucherId = (int)($input['voucher_id'] ?? 0);
            $reason = trim($input['reason'] ?? '');
            if ($voucherId <= 0 || empty($reason)) {
                echo json_encode(['success' => false, 'message' => 'Voucher ID and reversal reason are required.']);
                exit;
            }
            $res = PettyCash::reverseVoucher($voucherId, $reason, $userId, $branchId);
            echo json_encode($res);
            break;

        case 'reimburse_fund':
            if (!Auth::hasPermission('reimburse_petty_cash')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to reimburse petty cash fund.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $res = PettyCash::reimburseFund($input, $userId, $branchId);
            echo json_encode($res);
            break;

        case 'adjust_fund':
            if (!Auth::hasPermission('adjust_petty_cash')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to adjust petty cash fund.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $res = PettyCash::adjustFund($input, $userId, $branchId);
            echo json_encode($res);
            break;

        case 'get_vouchers':
            if (!Auth::hasPermission('view_petty_cash')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied.']);
                exit;
            }
            $page  = max(1, (int)($_GET['page'] ?? 1));
            $limit = max(1, min(100, (int)($_GET['limit'] ?? 15)));
            $filters = [
                'category_id' => $_GET['category_id'] ?? '',
                'status'      => $_GET['status'] ?? '',
                'start_date'  => $_GET['start_date'] ?? '',
                'end_date'    => $_GET['end_date'] ?? '',
                'search'      => $_GET['search'] ?? ''
            ];
            $res = PettyCash::getVouchersList($branchId, $filters, $page, $limit);
            echo json_encode(['success' => true, 'data' => $res]);
            break;

        case 'get_reimbursements':
            if (!Auth::hasPermission('view_petty_cash')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied.']);
                exit;
            }
            $res = PettyCash::getReimbursementsList($branchId);
            echo json_encode(['success' => true, 'data' => $res]);
            break;

        case 'get_adjustments':
            if (!Auth::hasPermission('view_petty_cash')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied.']);
                exit;
            }
            $res = PettyCash::getAdjustmentsList($branchId);
            echo json_encode(['success' => true, 'data' => $res]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action parameter.']);
            break;
    }
} catch (Throwable $e) {
    Logger::error("Petty Cash API Exception: " . $e->getMessage(), $e);
    echo json_encode(['success' => false, 'message' => 'API Error: ' . $e->getMessage()]);
}
