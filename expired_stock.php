<?php
/**
 * Curtiss POS - Expired Stock & Batch Management Controller
 * Displays expired and near-expiry stock batches with instant write-off actions.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/ExpiryManager.php';
require_once __DIR__ . '/core/Brand.php';
require_once __DIR__ . '/core/Category.php';
require_once __DIR__ . '/core/Supplier.php';

Auth::requireAuth();

if (!Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER, ROLE_ACCOUNTANT, ROLE_SPECIAL_USER)) {
    View::renderBlank('errors/403');
    exit();
}

$user = Auth::user();
$activeBranchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? $user['active_branch_id'] ?? 1);

$successMsg = null;
$errorMsg = null;

// POST Action: Write-off Lot
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (Auth::isSpecialUser()) {
        $errorMsg = 'Unauthorized action. Special User is strictly view-only.';
    } else {
        $action = $_POST['action'];
        $userId = (int)($_SESSION['user_id'] ?? $user['id'] ?? 1);

        if ($action === 'write_off_single') {
            $lotId = (int)($_POST['lot_id'] ?? 0);
            $qty = (float)($_POST['quantity'] ?? 0);
            $reason = trim($_POST['reason'] ?? 'Expired batch write-off');

            $res = ExpiryManager::writeOffLot($lotId, $activeBranchId, $qty, $reason, $userId);
            if ($res['success']) {
                $successMsg = $res['message'];
            } else {
                $errorMsg = $res['message'];
            }
        } elseif ($action === 'write_off_batch') {
            $lotIds = $_POST['lot_ids'] ?? [];
            $reason = trim($_POST['batch_reason'] ?? 'Bulk expired batches write-off');

            if (empty($lotIds)) {
                $errorMsg = 'Please select at least one expired batch to write off.';
            } else {
                $res = ExpiryManager::batchWriteOff($activeBranchId, $lotIds, $reason, $userId);
                if ($res['success']) {
                    $successMsg = $res['message'];
                } else {
                    $errorMsg = $res['message'];
                }
            }
        }
    }
}

$filters = [
    'filter_type' => trim($_GET['filter_type'] ?? 'all'),
    'search'      => trim($_GET['search'] ?? ''),
    'brand_id'    => (int)($_GET['brand_id'] ?? 0),
    'category_id' => (int)($_GET['category_id'] ?? 0),
    'supplier_id' => (int)($_GET['supplier_id'] ?? 0)
];

$page = max(1, (int)($_GET['page'] ?? 1));
$rawLimit = (int)($_GET['limit'] ?? 25);
$limit = in_array($rawLimit, [25, 50, 75, 100, 500], true) ? $rawLimit : 25;

$result = ExpiryManager::getExpiringLots($activeBranchId, $filters, $page, $limit);
$summary = ExpiryManager::getExpirySummary($activeBranchId);

$brands = Brand::all(false);
$categories = Category::all(false);
$suppliers = Supplier::all();

View::render('inventory/expired_stock', [
    'items'            => $result['items'],
    'summary'          => $summary,
    'totalItems'       => $result['totalItems'],
    'page'             => $result['page'],
    'limit'            => $limit,
    'totalPages'       => $result['totalPages'],
    'filters'          => $filters,
    'brands'           => $brands,
    'categories'       => $categories,
    'suppliers'        => $suppliers,
    'activeBranchName' => $user['active_branch_name'] ?? 'Main Branch',
    'successMsg'       => $successMsg,
    'errorMsg'         => $errorMsg
], 'Expired Stock & Batch Management - Curtiss POS');
