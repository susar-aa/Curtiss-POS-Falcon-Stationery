<?php
/**
 * Curtiss POS - Supplier Return & Credit Settlement API Endpoint (Stage 15)
 */

header('Content-Type: application/json');

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Logger.php';
require_once __DIR__ . '/core/SupplierReturnEngine.php';

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

        case 'get_returnable_items':
            if (!Auth::hasPermission('create_supplier_return')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to create supplier returns.']);
                exit;
            }
            $grnId = (int)($_GET['grn_id'] ?? 0);
            $res = SupplierReturnEngine::getReturnableGrnItems($grnId);
            echo json_encode($res);
            break;

        case 'get_supplier_products':
            if (!Auth::hasPermission('create_supplier_return')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to create supplier returns.']);
                exit;
            }
            $supplierId = (int)($_GET['supplier_id'] ?? 0);
            $search     = $_GET['search'] ?? null;
            $res = SupplierReturnEngine::getSupplierPurchasedProducts($supplierId, $search, $branchId);
            echo json_encode($res);
            break;

        case 'process_return':
            if (!Auth::hasPermission('create_supplier_return')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to create supplier returns.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $res = SupplierReturnEngine::processReturn($input, $userId, $branchId);
            echo json_encode($res);
            break;

        case 'get_credits':
            if (!Auth::hasPermission('manage_supplier_credits') && !Auth::hasPermission('view_supplier_returns')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to view supplier credits.']);
                exit;
            }
            $filters = [
                'supplier_id' => $_GET['supplier_id'] ?? null,
                'status'      => $_GET['status'] ?? null,
                'branch_id'   => $_GET['branch_id'] ?? null
            ];
            $credits = SupplierReturnEngine::getSupplierCredits($filters);
            echo json_encode(['success' => true, 'data' => $credits]);
            break;

        case 'process_settlement':
            if (!Auth::hasPermission('manage_supplier_credits')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to settle supplier credits.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $res = SupplierReturnEngine::processCreditSettlement($input, $userId, $branchId);
            echo json_encode($res);
            break;

        case 'get_history':
            if (!Auth::hasPermission('view_supplier_returns') && !Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to view supplier return history.']);
                exit;
            }
            $filters = [
                'supplier_id' => !empty($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : null,
                'grn_id'      => !empty($_GET['grn_id']) ? (int)$_GET['grn_id'] : null,
                'branch_id'   => !empty($_GET['branch_id']) ? (int)$_GET['branch_id'] : $branchId,
                'search'      => !empty($_GET['search']) ? trim($_GET['search']) : null,
                'limit'       => !empty($_GET['limit']) ? (int)$_GET['limit'] : null,
                'offset'      => !empty($_GET['offset']) ? (int)$_GET['offset'] : 0,
            ];
            $history = SupplierReturnEngine::getReturnHistory($filters);
            $totalCount = SupplierReturnEngine::getReturnHistoryCount($filters);
            echo json_encode(['success' => true, 'data' => $history, 'total' => $totalCount]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => "Unknown action '{$action}'."]);
            break;
    }
} catch (Throwable $e) {
    Logger::error("supplier_return_api error [action={$action}]: " . $e->getMessage(), $e);
    echo json_encode(['success' => false, 'message' => 'API Error: ' . $e->getMessage()]);
}
