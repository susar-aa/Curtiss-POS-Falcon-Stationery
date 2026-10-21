<?php
/**
 * Curtiss POS - Petty Cash & Expense Management API Endpoint (Stage 12 Redesign & Optimization)
 * Handles Petty Cash balance queries, expense vouchers creation, category management,
 * soft-delete & trash restore, reimbursements, manual adjustments, voucher reversals, and draft cancellations.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/PettyCash.php';
require_once __DIR__ . '/core/MaskingHelper.php';

Auth::requireLogin();
$user = Auth::user();
$userId = (int)$user['id'];
$branchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? 1);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Block mutations for view-only Special Users
$mutatingActions = ['save_category', 'delete_category', 'restore_category', 'create_voucher', 'reverse_voucher', 'cancel_voucher', 'reimburse_fund', 'adjust_fund'];
if (in_array($action, $mutatingActions, true) && Auth::isSpecialUser()) {
    echo json_encode(['success' => false, 'message' => 'Permission denied: Special User is view-only.']);
    exit;
}

try {
    switch ($action) {

        case 'get_summary':
            if (!Auth::hasPermission('view_petty_cash')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to view petty cash.']);
                exit;
            }
            $reqBranch = !empty($_GET['branch_id']) && is_numeric($_GET['branch_id']) ? (int)$_GET['branch_id'] : $branchId;
            $summary = PettyCash::getDashboardSummary($reqBranch);
            if (MaskingHelper::isSpecialUser()) {
                $summary['current_balance']      = MaskingHelper::maskValue($summary['current_balance']);
                $summary['total_expenses']       = MaskingHelper::maskValue($summary['total_expenses']);
                $summary['total_reimbursements'] = MaskingHelper::maskValue($summary['total_reimbursements']);
                $summary['net_adjustments']      = MaskingHelper::maskValue($summary['net_adjustments']);
            }
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
            if (MaskingHelper::isSpecialUser() && !empty($res['transactions'])) {
                foreach ($res['transactions'] as &$tx) {
                    $tx['debit']           = MaskingHelper::maskValue((float)$tx['debit']);
                    $tx['credit']          = MaskingHelper::maskValue((float)$tx['credit']);
                    $tx['running_balance'] = MaskingHelper::maskValue((float)$tx['running_balance']);
                }
                unset($tx);
                if (!empty($res['summary'])) {
                    $res['summary']['current_balance'] = MaskingHelper::maskValue((float)$res['summary']['current_balance']);
                    $res['summary']['total_inflow']    = MaskingHelper::maskValue((float)$res['summary']['total_inflow']);
                    $res['summary']['total_outflow']   = MaskingHelper::maskValue((float)$res['summary']['total_outflow']);
                }
            }
            echo json_encode(['success' => true, 'data' => $res]);
            break;

        case 'get_categories':
            $activeOnly = isset($_GET['all']) && $_GET['all'] === '1' ? false : true;
            $cats = PettyCash::getCategories($activeOnly);
            echo json_encode(['success' => true, 'data' => $cats]);
            break;

        case 'get_deleted_categories':
            if (!Auth::hasPermission('manage_expense_categories')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied.']);
                exit;
            }
            $cats = PettyCash::getDeletedCategories();
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

        case 'delete_category':
            if (!Auth::hasPermission('manage_expense_categories')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to delete expense categories.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $catId = (int)($input['id'] ?? 0);
            if ($catId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Valid category ID is required.']);
                exit;
            }
            $res = PettyCash::deleteCategory($catId, $userId);
            echo json_encode($res);
            break;

        case 'restore_category':
            if (!Auth::hasPermission('manage_expense_categories')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to restore expense categories.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $catId = (int)($input['id'] ?? 0);
            if ($catId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Valid category ID is required.']);
                exit;
            }
            $res = PettyCash::restoreCategory($catId, $userId);
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

        case 'cancel_voucher':
            if (!Auth::hasPermission('create_expense') && !Auth::hasPermission('reverse_petty_cash_voucher')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to cancel expense voucher.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $voucherId = (int)($input['voucher_id'] ?? 0);
            if ($voucherId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Valid voucher ID is required.']);
                exit;
            }
            $res = PettyCash::cancelVoucher($voucherId, $userId, $branchId);
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
            $limit = max(1, min(500, (int)($_GET['limit'] ?? 25)));
            $filters = [
                'category_id' => $_GET['category_id'] ?? '',
                'status'      => $_GET['status'] ?? '',
                'start_date'  => $_GET['start_date'] ?? '',
                'end_date'    => $_GET['end_date'] ?? '',
                'search'      => $_GET['search'] ?? ''
            ];
            $reqBranch = !empty($_GET['branch_id']) && is_numeric($_GET['branch_id']) ? (int)$_GET['branch_id'] : $branchId;
            $res = PettyCash::getVouchersList($reqBranch, $filters, $page, $limit);
            if (MaskingHelper::isSpecialUser() && !empty($res['items'])) {
                foreach ($res['items'] as &$v) {
                    $v['amount'] = MaskingHelper::maskValue((float)$v['amount']);
                }
                unset($v);
            }
            echo json_encode(['success' => true, 'data' => $res]);
            break;

        case 'get_reimbursements':
            if (!Auth::hasPermission('view_petty_cash')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied.']);
                exit;
            }
            $reqBranch = !empty($_GET['branch_id']) && is_numeric($_GET['branch_id']) ? (int)$_GET['branch_id'] : $branchId;
            $res = PettyCash::getReimbursementsList($reqBranch, 100);
            if (MaskingHelper::isSpecialUser() && !empty($res)) {
                foreach ($res as &$r) {
                    $r['amount'] = MaskingHelper::maskValue((float)$r['amount']);
                }
                unset($r);
            }
            echo json_encode(['success' => true, 'data' => $res]);
            break;

        case 'get_adjustments':
            if (!Auth::hasPermission('view_petty_cash')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied.']);
                exit;
            }
            $reqBranch = !empty($_GET['branch_id']) && is_numeric($_GET['branch_id']) ? (int)$_GET['branch_id'] : $branchId;
            $res = PettyCash::getAdjustmentsList($reqBranch, 100);
            if (MaskingHelper::isSpecialUser() && !empty($res)) {
                foreach ($res as &$a) {
                    $a['amount'] = MaskingHelper::maskValue((float)$a['amount']);
                }
                unset($a);
            }
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
