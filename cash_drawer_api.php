<?php
/**
 * Curtiss POS - Cash Drawer & Business Day API Endpoint (Stage 11)
 * Handles Business Day opening/closing, Cash Drawer Movements, Manual Cash In/Out, and Reconciliation Queries.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/BusinessDay.php';

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

            if ($bDayId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Valid business day ID is required.']);
                exit;
            }

            $res = BusinessDay::closeDay($bDayId, $actualCountedCash, $retainedCash, $userId, $notes, [], $cashDenominations, $cardSettlementAmount);
            echo json_encode($res);
            break;

        case 'get_closed_days':
            if (!Auth::hasPermission('view_closed_days')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to view closed days history.']);
                exit;
            }
            $page  = max(1, (int)($_GET['page'] ?? 1));
            $limit = max(1, min(100, (int)($_GET['limit'] ?? 15)));
            $res = BusinessDay::getClosedDaysList($branchId, $page, $limit);
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
