<?php
/**
 * Curtiss POS - Supplier Payments API Endpoint (Stage 9)
 * Provides JSON API handlers for fetching supplier outstanding summaries (based on approved GRNs) and processing payments.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/SupplierPayment.php';

Auth::requireLogin();
if (Auth::isSpecialUser()) {
    echo json_encode(['success' => false, 'message' => 'Permission denied: Special User is view-only.']);
    exit;
}

$user = Auth::user();
$branchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? 1);
$userId = (int)($user['id'] ?? 1);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_supplier_outstanding':
            if (!Auth::hasPermission('suppliers.view_payments') && !Auth::hasPermission('suppliers.pay')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied.']);
                exit;
            }

            $supplierId = (int)($_GET['supplier_id'] ?? $_POST['supplier_id'] ?? 0);
            if ($supplierId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Valid supplier ID is required.']);
                exit;
            }

            $targetBranchId = !empty($_GET['branch_id']) ? (int)$_GET['branch_id'] : $branchId;
            $summary = SupplierPayment::getSupplierOutstandingSummary($supplierId, $targetBranchId);
            echo json_encode($summary);
            break;

        case 'get_supplier_activity_ledger':
            require_once __DIR__ . '/core/Supplier.php';
            $supplierId = (int)($_GET['supplier_id'] ?? $_POST['supplier_id'] ?? 0);
            if ($supplierId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Valid supplier ID is required.']);
                exit;
            }
            $targetBranchId = !empty($_GET['branch_id']) ? (int)$_GET['branch_id'] : $branchId;
            $ledger = Supplier::getActivityLedger($supplierId, $targetBranchId);
            echo json_encode(['success' => true, 'activity_ledger' => $ledger]);
            break;

        case 'process_supplier_payment':
            if (!Auth::hasPermission('suppliers.pay')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to process supplier payments.']);
                exit;
            }

            $rawInput = file_get_contents('php://input');
            $data = json_decode($rawInput, true);

            if (!$data) {
                $data = $_POST;
            }

            $result = SupplierPayment::processPayment($data, $userId, $branchId);
            echo json_encode($result);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid API action.']);
            break;
    }
} catch (Throwable $e) {
    Logger::error("Supplier Payments API Error: " . $e->getMessage(), $e);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
