<?php
/**
 * Curtiss POS - Purchase Orders Controller (Stage 4)
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/PurchaseOrder.php';
require_once __DIR__ . '/core/Supplier.php';

Auth::requireAuth();

if (!Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER, ROLE_ACCOUNTANT, ROLE_CASHIER, ROLE_SPECIAL_USER)) {
    View::renderBlank('errors/403');
    exit();
}

$user = Auth::user();
$activeBranchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? $user['active_branch_id'] ?? 1);

$successMsg = null;
$errorMsg = null;

if (isset($_GET['action']) && $_GET['action'] === 'get_po') {
    header('Content-Type: application/json');
    $poId = (int)($_GET['id'] ?? 0);
    $po = PurchaseOrder::find($poId);
    if ($po) {
        echo json_encode(['success' => true, 'data' => $po, 'po' => $po, 'items' => $po['items'] ?? []]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Purchase order not found.']);
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'approve') {
        if (!Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)) {
            $errorMsg = 'Unauthorized: Admin or Manager approval required.';
        } else {
            $res = PurchaseOrder::approve($id, $user['id']);
            if ($res['success']) {
                $successMsg = $res['message'];
            } else {
                $errorMsg = $res['message'];
            }
        }
    } elseif ($action === 'cancel') {
        if (!Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)) {
            $errorMsg = 'Unauthorized action.';
        } else {
            $res = PurchaseOrder::cancel($id, $user['id']);
            if ($res['success']) {
                $successMsg = $res['message'];
            } else {
                $errorMsg = $res['message'];
            }
        }
    } elseif ($action === 'delete') {
        if (!Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)) {
            $errorMsg = 'Unauthorized action.';
        } else {
            $res = PurchaseOrder::delete($id, $user['id']);
            if ($res['success']) {
                $successMsg = $res['message'];
            } else {
                $errorMsg = $res['message'];
            }
        }
    } elseif ($action === 'restore') {
        if (!Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)) {
            $errorMsg = 'Unauthorized action.';
        } else {
            $res = PurchaseOrder::restore($id, $user['id']);
            if ($res['success']) {
                $successMsg = $res['message'];
            } else {
                $errorMsg = $res['message'];
            }
        }
    }
}

$filters = [
    'search'      => trim($_GET['search'] ?? ''),
    'supplier_id' => (int)($_GET['supplier_id'] ?? 0),
    'status'      => trim($_GET['status'] ?? ''),
    'view'        => trim($_GET['view'] ?? 'active')
];

$allowedLimits = [25, 50, 75, 100, 500];
$limit = isset($_GET['limit']) && in_array((int)$_GET['limit'], $allowedLimits, true) ? (int)$_GET['limit'] : 25;
$page = max(1, (int)($_GET['page'] ?? 1));

$result = PurchaseOrder::getList($activeBranchId, $filters, $page, $limit);
$suppliers = Supplier::allUnique(false);

$deletedCountRow = Database::fetchOne("
    SELECT COUNT(*) AS total 
    FROM purchase_orders 
    WHERE branch_id = :branch_id AND deleted_at IS NOT NULL
", [':branch_id' => $activeBranchId]);
$deletedCount = (int)($deletedCountRow['total'] ?? 0);

View::render('purchasing/po_list', [
    'purchaseOrders'   => $result['items'],
    'totalItems'       => $result['totalItems'],
    'page'             => $result['page'],
    'totalPages'       => $result['totalPages'],
    'limit'            => $limit,
    'allowedLimits'    => $allowedLimits,
    'filters'          => $filters,
    'suppliers'        => $suppliers,
    'activeBranchName' => $user['active_branch_name'],
    'successMsg'       => $successMsg,
    'errorMsg'         => $errorMsg,
    'deletedCount'     => $deletedCount,
    'currentView'      => $filters['view']
], 'Purchase Orders - Curtiss POS');

