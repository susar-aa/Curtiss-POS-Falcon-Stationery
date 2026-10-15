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
$branchId = (int)($_SESSION['branch_id'] ?? 1);

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
                'branch_id'       => $_GET['branch_id'] ?? null,
                'bank_account_id' => $_GET['bank_account_id'] ?? null,
                'search'          => $_GET['search'] ?? null,
                'date_from'       => $_GET['date_from'] ?? null,
                'date_to'         => $_GET['date_to'] ?? null
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

        default:
            echo json_encode(['success' => false, 'message' => "Unknown action '{$action}'."]);
            break;
    }
} catch (Throwable $e) {
    Logger::error("cheque_api error [action={$action}]: " . $e->getMessage(), $e);
    echo json_encode(['success' => false, 'message' => 'API Error: ' . $e->getMessage()]);
}
