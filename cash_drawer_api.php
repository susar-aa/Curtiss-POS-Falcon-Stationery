<?php
/**
 * Curtiss POS - Cash Drawer & Business Day API Endpoint (Stage 11)
 * Handles Business Day opening/closing, Cash Drawer Movements, Manual Cash In/Out, and Reconciliation Queries.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/BusinessDay.php';

defined('ROLE_SUPER_ADMIN') or define('ROLE_SUPER_ADMIN', 1);
defined('ROLE_ADMIN') or define('ROLE_ADMIN', 2);
defined('ROLE_MANAGER') or define('ROLE_MANAGER', 3);
defined('ROLE_CASHIER') or define('ROLE_CASHIER', 4);
defined('ROLE_ACCOUNTANT') or define('ROLE_ACCOUNTANT', 5);
defined('ROLE_SPECIAL_USER') or define('ROLE_SPECIAL_USER', 6);

Auth::requireLogin();

$user = Auth::user();
$userId = (int)($user['id'] ?? 1);
$isSuperAdmin = Auth::hasRole('superadmin') || Auth::isSuperAdmin();
$branchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? $user['active_branch_id'] ?? 1);
if ($isSuperAdmin && !empty($_GET['branch_id'])) {
    $branchId = (int)$_GET['branch_id'];
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$mutationActions = ['start_day', 'close_day', 'manual_cash_movement'];

if (Auth::isSpecialUser() && in_array($action, $mutationActions, true)) {
    echo json_encode(['success' => false, 'message' => 'Permission denied: Special User is view-only.']);
    exit;
}

try {
    switch ($action) {

        case 'start_day':
            if (!Auth::hasPermission('start_business_day')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to start business day.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $openingCash = isset($input['opening_cash']) ? (float)$input['opening_cash'] : BusinessDay::DEFAULT_RETAINED_CASH;
            $notes = $input['notes'] ?? null;
            $res = BusinessDay::startDay($branchId, $openingCash, $userId, $notes);
            echo json_encode($res);
            break;

        case 'get_active_day':
            $activeDay = BusinessDay::getOrCreateActiveDay($branchId, $userId);
            if (!$activeDay) {
                echo json_encode(['success' => true, 'has_active_day' => false, 'active_day' => null]);
            } else {
                $summaryRes = BusinessDay::getDrawerSummary((int)$activeDay['id']);
                echo json_encode([
                    'success'        => true,
                    'has_active_day' => true,
                    'active_day'     => $activeDay,
                    'summary'        => $summaryRes['summary'] ?? null
                ]);
            }
            break;

        case 'manual_cash_movement':
            if (!Auth::hasPermission('manual_cash_movement')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied for manual cash movements.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $type   = $input['type'] ?? '';
            $amount = (float)($input['amount'] ?? 0);
            $reason = $input['reason'] ?? '';
            $notes  = $input['notes'] ?? null;
            $res = BusinessDay::manualCashMovement($branchId, $type, $amount, $reason, $notes, $userId);
            echo json_encode($res);
            break;

        case 'get_drawer_summary':
            $bDayId = (int)($_GET['business_day_id'] ?? $_POST['business_day_id'] ?? 0);
            if ($bDayId <= 0) {
                $active = BusinessDay::getOrCreateActiveDay($branchId, $userId);
                $bDayId = $active ? (int)$active['id'] : 0;
            }
            if ($bDayId <= 0) {
                echo json_encode(['success' => false, 'message' => 'No active business day found.']);
                exit;
            }
            $res = BusinessDay::getDrawerSummary($bDayId);
            echo json_encode($res);
            break;

        case 'get_comprehensive_summary':
            $bDayId = (int)($_GET['business_day_id'] ?? $_POST['business_day_id'] ?? 0);
            if ($bDayId <= 0) {
                $active = BusinessDay::getOrCreateActiveDay($branchId, $userId);
                $bDayId = $active ? (int)$active['id'] : 0;
            }
            if ($bDayId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Valid business day ID is required.']);
                exit;
            }
            $res = BusinessDay::getDayComprehensiveSummary($bDayId);
            echo json_encode($res);
            break;

        case 'close_day':
            if (!Auth::hasPermission('close_business_day')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to close business day.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $bDayId               = (int)($input['business_day_id'] ?? 0);
            $actualCountedCash    = (float)($input['actual_cash'] ?? 0);
            $cardSettlementAmount = (float)($input['card_settlement_amount'] ?? 0);
            $retainedCash         = isset($input['retained_cash']) ? (float)$input['retained_cash'] : BusinessDay::DEFAULT_RETAINED_CASH;
            $notes                = $input['notes'] ?? null;
            $cashDenominations    = $input['cash_denominations'] ?? null;
            $splitTransfers       = $input['split_transfers'] ?? [];

            if ($bDayId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Valid business day ID is required.']);
                exit;
            }

            $options = ['split_transfers' => $splitTransfers];
            $res = BusinessDay::closeDay($bDayId, $actualCountedCash, $retainedCash, $userId, $notes, $options, $cashDenominations, $cardSettlementAmount);
            echo json_encode($res);
            break;

        case 'get_closed_days':
            if (!Auth::hasPermission('view_closed_days')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to view closed days history.']);
                exit;
            }
            $page  = max(1, (int)($_GET['page'] ?? 1));
            $limit = (int)($_GET['limit'] ?? 25);
            $filters = [
                'search'    => trim($_GET['search'] ?? ''),
                'date_from' => trim($_GET['date_from'] ?? ''),
                'date_to'   => trim($_GET['date_to'] ?? '')
            ];
            if ($isSuperAdmin && !empty($_GET['all_branches'])) {
                $filters['all_branches'] = true;
            }
            $res = BusinessDay::getClosedDaysList($branchId, $page, $limit, $filters);
            echo json_encode(['success' => true, 'data' => $res]);
            break;

        case 'get_day_movements':
            $bDayId = (int)($_GET['business_day_id'] ?? 0);
            if ($bDayId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid business day ID.']);
                exit;
            }
            $movements = BusinessDay::getDayMovements($bDayId);
            echo json_encode(['success' => true, 'data' => $movements]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action parameter.']);
            break;
    }

} catch (Throwable $e) {
    Logger::error("Cash Drawer API Exception: " . $e->getMessage(), $e);
    echo json_encode(['success' => false, 'message' => 'API Error: ' . $e->getMessage()]);
}
