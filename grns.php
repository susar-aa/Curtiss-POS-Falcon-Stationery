<?php
/**
 * Curtiss POS - Goods Received Notes (GRN) Controller (Stage 4)
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/GRN.php';
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

if (isset($_GET['action']) && $_GET['action'] === 'get_grn') {
    header('Content-Type: application/json');
    $grnId = (int)($_GET['id'] ?? 0);
    $grn = GRN::find($grnId);
    if ($grn) {
        echo json_encode(['success' => true, 'data' => $grn, 'grn' => $grn, 'items' => $grn['items'] ?? []]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Goods Received Note not found.']);
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'approve') {
        if (!Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)) {
            $errorMsg = 'Unauthorized: Admin or Manager approval required to post GRN to inventory.';
        } else {
            $res = GRN::approve($id, $user['id']);
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
            $res = GRN::cancel($id, (int)$user['id']);
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
    'status'      => trim($_GET['status'] ?? '')
];

$allowedLimits = [25, 50, 75, 100, 500];
$page = max(1, (int)($_GET['page'] ?? 1));
$requestedLimit = (int)($_GET['limit'] ?? 25);
$limit = in_array($requestedLimit, $allowedLimits, true) ? $requestedLimit : 25;

$result = GRN::getList($activeBranchId, $filters, $page, $limit);
$suppliers = Supplier::allUnique(false);

View::render('purchasing/grn_list', [
    'grns'             => $result['items'],
    'totalItems'       => $result['totalItems'],
    'page'             => $result['page'],
    'totalPages'       => $result['totalPages'],
    'limit'            => $limit,
    'allowedLimits'    => $allowedLimits,
    'filters'          => $filters,
    'suppliers'        => $suppliers,
    'activeBranchName' => $user['active_branch_name'],
    'successMsg'       => $successMsg,
    'errorMsg'         => $errorMsg
], 'Goods Received Notes (GRN) - Curtiss POS');
