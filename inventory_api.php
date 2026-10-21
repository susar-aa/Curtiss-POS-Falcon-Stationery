<?php
/**
 * Curtiss POS - Advanced Inventory Operations API Endpoint (Stage 14)
 * Handles Branch Transfers, Stock Adjustments, Damaged/Wastage Management,
 * Physical Stock Audits, and Comprehensive Stock Ledger queries.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Logger.php';
require_once __DIR__ . '/core/AdvancedInventory.php';

Auth::requireLogin();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if (Auth::isSpecialUser() && !in_array($action, ['get_overview', 'get_ledger', 'get_product_lots'])) {
    echo json_encode(['success' => false, 'message' => 'Permission denied: Special User is view-only.']);
    exit;
}
$user = Auth::user();
$userId = (int)$user['id'];
$branchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? 1);

try {
    switch ($action) {

        case 'get_product_lots':
            $productId = (int)($_GET['product_id'] ?? 0);
            $targetBranchId = !empty($_GET['branch_id']) ? (int)$_GET['branch_id'] : $branchId;
            $lots = Inventory::getLots($productId, $targetBranchId);
            if (Auth::isSpecialUser()) {
                require_once __DIR__ . '/core/MaskingHelper.php';
                foreach ($lots as &$lot) {
                    $lot['cost'] = MaskingHelper::maskValue((float)$lot['cost']);
                    $lot['remaining_quantity'] = MaskingHelper::maskQuantity((float)$lot['remaining_quantity']);
                    $lot['initial_quantity'] = MaskingHelper::maskQuantity((float)$lot['initial_quantity']);
                }
                unset($lot);
            }
            echo json_encode(['success' => true, 'lots' => $lots]);
            break;

        case 'get_overview':
            if (!Auth::hasPermission('inventory.view')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to view inventory.']);
                exit;
            }
            $targetBranchId = !empty($_GET['branch_id']) ? (int)$_GET['branch_id'] : $branchId;
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
            $filters = [
                'search'      => $_GET['search'] ?? null,
                'brand_id'    => $_GET['brand_id'] ?? null,
                'category_id' => $_GET['category_id'] ?? null
            ];
            $overview = AdvancedInventory::getInventoryOverview($targetBranchId, $filters, $page, $limit);
            echo json_encode(['success' => true, 'data' => $overview]);
            break;

        case 'get_ledger':
            if (!Auth::hasPermission('view_stock_ledger') && !Auth::hasPermission('inventory.view')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to view stock ledger.']);
                exit;
            }
            $targetBranchId = !empty($_GET['branch_id']) ? (int)$_GET['branch_id'] : null;
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
            $filters = [
                'product_id'    => $_GET['product_id'] ?? null,
                'movement_type' => $_GET['movement_type'] ?? null,
                'search'        => $_GET['search'] ?? null
            ];
            $ledger = Inventory::getLedgerList($targetBranchId, $filters, $page, $limit);
            echo json_encode(['success' => true, 'data' => $ledger]);
            break;

        case 'get_transfer_details':
            $transferId = (int)($_GET['transfer_id'] ?? 0);
            if ($transferId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid transfer ID']);
                exit;
            }
            $header = Database::fetchOne("
                SELECT st.*, fb.name AS from_branch_name, tb.name AS to_branch_name, u.username AS creator_name
                FROM stock_transfers st
                JOIN branches fb ON st.from_branch_id = fb.id
                JOIN branches tb ON st.to_branch_id = tb.id
                JOIN users u ON st.created_by = u.id
                WHERE st.id = :id
            ", [':id' => $transferId]);
            if (!$header) {
                echo json_encode(['success' => false, 'message' => 'Transfer not found']);
                exit;
            }
            $items = Database::fetchAll("
                SELECT sti.*, p.name AS product_name, p.sku, p.barcode, pl.lot_number
                FROM stock_transfer_items sti
                JOIN products p ON sti.product_id = p.id
                LEFT JOIN product_lots pl ON sti.source_lot_id = pl.id
                WHERE sti.transfer_id = :id
            ", [':id' => $transferId]);
            echo json_encode(['success' => true, 'transfer' => $header, 'items' => $items]);
            break;

        case 'create_transfer':
            if (!Auth::hasPermission('create_stock_transfer') && !Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to create stock transfers.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $res = AdvancedInventory::createTransfer($input, $userId);
            echo json_encode($res);
            break;

        case 'update_transfer':
            if (!Auth::hasPermission('create_stock_transfer') && !Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to edit stock transfers.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $transferId = (int)($input['transfer_id'] ?? 0);
            $res = AdvancedInventory::updateTransfer($transferId, $input, $userId);
            echo json_encode($res);
            break;

        case 'send_transfer':
            if (!Auth::hasPermission('create_stock_transfer') && !Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to send stock transfers.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $transferId = (int)($input['transfer_id'] ?? 0);
            $res = AdvancedInventory::sendTransfer($transferId, $userId);
            echo json_encode($res);
            break;

        case 'receive_transfer':
            if (!Auth::hasPermission('receive_stock_transfer') && !Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to receive stock transfers.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $transferId = (int)($input['transfer_id'] ?? 0);

            $trf = Database::fetchOne("SELECT to_branch_id FROM stock_transfers WHERE id = :id", [':id' => $transferId]);
            if (!$trf) {
                echo json_encode(['success' => false, 'message' => 'Stock transfer not found.']);
                exit;
            }

            $user = Auth::user();
            $activeBranchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? $user['active_branch_id'] ?? 1);
            if ((int)$trf['to_branch_id'] !== $activeBranchId) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Stock transfers can only be confirmed by the destination receiving branch. Please switch to the destination branch to confirm receipt.'
                ]);
                exit;
            }

            $res = AdvancedInventory::receiveTransfer($transferId, $userId);
            echo json_encode($res);
            break;

        case 'cancel_transfer':
            if (!Auth::hasPermission('create_stock_transfer') && !Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to cancel stock transfers.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $transferId = (int)($input['transfer_id'] ?? 0);
            $res = AdvancedInventory::cancelTransfer($transferId, $userId);
            echo json_encode($res);
            break;

        case 'create_adjustment':
            if (!Auth::hasPermission('manage_stock_adjustments')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to post stock adjustments.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $res = AdvancedInventory::createAdjustment($input, $userId);
            echo json_encode($res);
            break;

        case 'get_adjustment_details':
            $adjId = (int)($_GET['adjustment_id'] ?? 0);
            if ($adjId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid adjustment ID']);
                exit;
            }
            $adj = Database::fetchOne("
                SELECT sa.*, p.name AS product_name, p.sku, p.barcode, pl.lot_number, pl.cost AS lot_cost, pl.selling_price AS lot_selling_price, u.username AS creator_name
                FROM stock_adjustments sa
                JOIN products p ON sa.product_id = p.id
                JOIN product_lots pl ON sa.lot_id = pl.id
                JOIN users u ON sa.created_by = u.id
                WHERE sa.id = :id
            ", [':id' => $adjId]);
            if (!$adj) {
                echo json_encode(['success' => false, 'message' => 'Adjustment record not found']);
                exit;
            }
            echo json_encode(['success' => true, 'adjustment' => $adj]);
            break;

        case 'record_damaged':
            if (!Auth::hasPermission('manage_damaged_stock') && !Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to record damaged stock.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $res = AdvancedInventory::recordDamagedStock($input, $userId);
            echo json_encode($res);
            break;

        case 'dispose_damaged':
            if (!Auth::hasPermission('manage_damaged_stock') && !Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to dispose damaged stock.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $id = (int)($input['damaged_stock_id'] ?? 0);
            $res = AdvancedInventory::disposeDamagedStock($id, $input, $userId);
            echo json_encode($res);
            break;

        case 'get_damaged_details':
            $dmgId = (int)($_GET['damaged_id'] ?? 0);
            if ($dmgId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid damaged record ID']);
                exit;
            }
            $dmg = Database::fetchOne("
                SELECT ds.*, p.name AS product_name, p.sku, p.barcode, pl.lot_number, pl.cost AS lot_cost, pl.selling_price AS lot_selling_price, u.username AS logger_name,
                       sr.return_no
                FROM damaged_stock ds
                JOIN products p ON ds.product_id = p.id
                LEFT JOIN product_lots pl ON ds.lot_id = pl.id
                JOIN users u ON ds.logged_by = u.id
                LEFT JOIN sales_returns sr ON ds.sales_return_id = sr.id
                WHERE ds.id = :id
            ", [':id' => $dmgId]);
            if (!$dmg) {
                echo json_encode(['success' => false, 'message' => 'Damaged stock record not found']);
                exit;
            }
            echo json_encode(['success' => true, 'damaged' => $dmg]);
            break;

        case 'get_audit_details':
            if (!Auth::hasPermission('manage_stock_audits') && !Auth::hasPermission('inventory.view') && !Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to view audit details.']);
                exit;
            }
            $auditId = (int)($_GET['audit_id'] ?? 0);
            if ($auditId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid audit ID']);
                exit;
            }
            $header = Database::fetchOne("
                SELECT sa.*, u.username AS creator_name, pu.username AS poster_name
                FROM stock_audits sa
                JOIN users u ON sa.created_by = u.id
                LEFT JOIN users pu ON sa.posted_by = pu.id
                WHERE sa.id = :id
            ", [':id' => $auditId]);
            if (!$header) {
                echo json_encode(['success' => false, 'message' => 'Audit session not found']);
                exit;
            }
            $items = Database::fetchAll("
                SELECT sai.*, p.name AS product_name, p.sku, p.barcode, pl.lot_number, pl.cost AS lot_cost
                FROM stock_audit_items sai
                JOIN products p ON sai.product_id = p.id
                JOIN product_lots pl ON sai.lot_id = pl.id
                WHERE sai.audit_id = :id
                ORDER BY p.name ASC, pl.id ASC
            ", [':id' => $auditId]);
            echo json_encode(['success' => true, 'audit' => $header, 'items' => $items]);
            break;

        case 'start_audit':
            if (!Auth::hasPermission('manage_stock_audits') && !Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to start stock audits.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $targetBranchId = (int)($input['branch_id'] ?? $branchId);
            $notes = $input['notes'] ?? null;
            $res = AdvancedInventory::startStockAudit($targetBranchId, $notes, $userId);
            echo json_encode($res);
            break;

        case 'save_audit_counts':
            if (!Auth::hasPermission('manage_stock_audits') && !Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to manage stock audits.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $auditId = (int)($input['audit_id'] ?? 0);
            $items = $input['items'] ?? [];
            $res = AdvancedInventory::saveAuditCounts($auditId, $items, $userId);
            echo json_encode($res);
            break;

        case 'post_audit':
            if (!Auth::hasPermission('manage_stock_audits') && !Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to post stock audits.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $auditId = (int)($input['audit_id'] ?? 0);
            $res = AdvancedInventory::postStockAudit($auditId, $userId);
            echo json_encode($res);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid API action.']);
            break;
    }
} catch (Throwable $e) {
    Logger::error("API error in inventory_api.php [action={$action}]: " . $e->getMessage(), $e);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
