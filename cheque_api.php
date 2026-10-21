<?php
/**
 * Curtiss POS - Supplier Cheque Management API Endpoint (Stage 15)
 */

header('Content-Type: application/json');

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Logger.php';
require_once __DIR__ . '/core/ChequeEngine.php';

Auth::requireLogin();
if (Auth::isSpecialUser()) {
    echo json_encode(['success' => false, 'message' => 'Permission denied: Special User is view-only.']);
    exit;
}
$user = Auth::user();
$userId = (int)$user['id'];
$branchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? 1);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {

        case 'get_cheques':
            if (!Auth::hasPermission('view_cheques')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to view cheques.']);
                exit;
            }
            $filters = [
                'status'          => $_GET['status'] ?? null,
                'supplier_id'     => $_GET['supplier_id'] ?? null,
                'branch_id'       => $_GET['branch_id'] ?? $branchId,
                'bank_account_id' => $_GET['bank_account_id'] ?? null,
                'search'          => $_GET['search'] ?? null,
                'date_from'       => $_GET['date_from'] ?? null,
                'date_to'         => $_GET['date_to'] ?? null,
                'view'            => $_GET['view'] ?? 'active'
            ];
            $cheques = ChequeEngine::getCheques($filters);
            echo json_encode(['success' => true, 'data' => $cheques]);
            break;

        case 'get_cheque_details':
            if (!Auth::hasPermission('view_cheques')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to view cheques.']);
                exit;
            }
            $chequeId = (int)($_GET['cheque_id'] ?? 0);
            $cheque = ChequeEngine::getCheque($chequeId);
            if (!$cheque) {
                echo json_encode(['success' => false, 'message' => 'Cheque not found.']);
            } else {
                echo json_encode(['success' => true, 'data' => $cheque]);
            }
            break;

        case 'clear_cheque':
            if (!Auth::hasPermission('clear_cheque')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to clear cheques.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $res = ChequeEngine::clearCheque($input, $userId);
            echo json_encode($res);
            break;

        case 'bounce_cheque':
            if (!Auth::hasPermission('bounce_cheque')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to bounce cheques.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $res = ChequeEngine::bounceCheque($input, $userId);
            echo json_encode($res);
            break;

        case 'cancel_cheque':
            if (!Auth::hasPermission('cancel_cheque')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to cancel cheques.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $res = ChequeEngine::cancelCheque($input, $userId);
            echo json_encode($res);
            break;

        case 'delete_cheque':
            if (!Auth::hasPermission('cancel_cheque')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to delete cheques.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $chequeId = (int)($input['cheque_id'] ?? $_GET['cheque_id'] ?? 0);
            $res = ChequeEngine::delete($chequeId, $userId);
            echo json_encode($res);
            break;

        case 'restore_cheque':
            if (!Auth::hasPermission('cancel_cheque')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to restore cheques.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $chequeId = (int)($input['cheque_id'] ?? $_GET['cheque_id'] ?? 0);
            $res = ChequeEngine::restore($chequeId, $userId);
            echo json_encode($res);
            break;

        case 'check_duplicate_cheque_no':
            $bankAccountId = (int)($_GET['bank_account_id'] ?? $_POST['bank_account_id'] ?? 0);
            $chequeNo = trim($_GET['cheque_no'] ?? $_POST['cheque_no'] ?? '');
            $excludeId = !empty($_GET['exclude_id']) ? (int)$_GET['exclude_id'] : null;
            if ($bankAccountId <= 0 || empty($chequeNo)) {
                echo json_encode(['success' => true, 'duplicate' => false]);
                exit;
            }
            $isDup = ChequeEngine::checkDuplicateChequeNo($bankAccountId, $chequeNo, $excludeId);
            echo json_encode(['success' => true, 'duplicate' => $isDup]);
            break;

        // Customer Cheques Handlers
        case 'get_customer_cheques':
            if (!Auth::hasPermission('view_cheques') && !Auth::hasPermission('customers.view')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to view customer cheques.']);
                exit;
            }
            $filters = [
                'status'          => $_GET['status'] ?? null,
                'customer_id'     => $_GET['customer_id'] ?? null,
                'branch_id'       => $_GET['branch_id'] ?? $branchId,
                'bank_account_id' => $_GET['bank_account_id'] ?? null,
                'search'          => $_GET['search'] ?? null,
                'date_from'       => $_GET['date_from'] ?? null,
                'date_to'         => $_GET['date_to'] ?? null,
                'view'            => $_GET['view'] ?? 'active'
            ];
            $cheques = ChequeEngine::getCustomerCheques($filters);
            echo json_encode(['success' => true, 'data' => $cheques]);
            break;

        case 'get_customer_cheque_details':
            if (!Auth::hasPermission('view_cheques') && !Auth::hasPermission('customers.view')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to view customer cheques.']);
                exit;
            }
            $chequeId = (int)($_GET['cheque_id'] ?? 0);
            $cheque = ChequeEngine::getCustomerCheque($chequeId);
            if (!$cheque) {
                echo json_encode(['success' => false, 'message' => 'Customer cheque not found.']);
            } else {
                echo json_encode(['success' => true, 'data' => $cheque]);
            }
            break;

        case 'deposit_customer_cheque':
            if (!Auth::hasPermission('clear_cheque') && !Auth::hasPermission('customers.pay')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to deposit customer cheques.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $res = ChequeEngine::depositCustomerCheque($input, $userId);
            echo json_encode($res);
            break;

        case 'clear_customer_cheque':
            if (!Auth::hasPermission('clear_cheque') && !Auth::hasPermission('customers.pay')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to clear customer cheques.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $res = ChequeEngine::clearCustomerCheque($input, $userId);
            echo json_encode($res);
            break;

        case 'return_customer_cheque':
            if (!Auth::hasPermission('bounce_cheque') && !Auth::hasPermission('customers.pay')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to return customer cheques.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $res = ChequeEngine::returnCustomerCheque($input, $userId, $branchId);
            echo json_encode($res);
            break;

        case 'cancel_customer_cheque':
            if (!Auth::hasPermission('cancel_cheque') && !Auth::hasPermission('customers.pay')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to cancel customer cheques.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $res = ChequeEngine::cancelCustomerCheque($input, $userId, $branchId);
            echo json_encode($res);
            break;

        case 'delete_customer_cheque':
            if (!Auth::hasPermission('cancel_cheque')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to delete customer cheques.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $chequeId = (int)($input['cheque_id'] ?? $_GET['cheque_id'] ?? 0);
            $res = ChequeEngine::deleteCustomerCheque($chequeId, $userId);
            echo json_encode($res);
            break;

        case 'restore_customer_cheque':
            if (!Auth::hasPermission('cancel_cheque')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to restore customer cheques.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $chequeId = (int)($input['cheque_id'] ?? $_GET['cheque_id'] ?? 0);
            $res = ChequeEngine::restoreCustomerCheque($chequeId, $userId);
            echo json_encode($res);
            break;

        default:
            echo json_encode(['success' => false, 'message' => "Unknown action '{$action}'."]);
            break;
    }
} catch (Throwable $e) {
    Logger::error("cheque_api error [action={$action}]: " . $e->getMessage(), $e);
    echo json_encode(['success' => false, 'message' => 'API Error: ' . $e->getMessage()]);
}
