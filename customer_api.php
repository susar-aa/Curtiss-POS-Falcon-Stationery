<?php
/**
 * Curtiss POS - Customer Management API Endpoint
 * Handles customer creation, details lookup, credit history, and status toggles.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Logger.php';
require_once __DIR__ . '/core/Customer.php';
require_once __DIR__ . '/core/MaskingHelper.php';

Auth::requireLogin();
$user = Auth::user();
$userId = (int)$user['id'];

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {

        case 'get_customer_details':
            if (!Auth::hasPermission('customers.view')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied.']);
                exit;
            }
            $customerId = (int)($_GET['customer_id'] ?? 0);
            $customer = Customer::find($customerId);
            if (!$customer) {
                echo json_encode(['success' => false, 'message' => 'Customer record not found.']);
                exit;
            }

            if (Auth::isSpecialUser() && isset($customer['credit_balance'])) {
                $customer['credit_balance'] = MaskingHelper::maskValue((float)$customer['credit_balance']);
            }

            $creditHistory = Customer::getCreditHistory($customerId);
            $purchaseHistory = Customer::getPurchaseHistory($customerId);
            $activityTimeline = Customer::getActivityTimeline($customerId);

            echo json_encode([
                'success'           => true,
                'customer'          => $customer,
                'credit_history'     => $creditHistory,
                'purchase_history'   => $purchaseHistory,
                'activity_timeline' => $activityTimeline
            ]);
            break;

        case 'create_customer':
            if (!Auth::hasPermission('customers.manage') || Auth::isSpecialUser()) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to create customer.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $res = Customer::create($input);
            echo json_encode($res);
            break;

        case 'toggle_status':
            if (!Auth::hasPermission('customers.status') || Auth::isSpecialUser()) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to modify customer status.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $id = (int)($input['id'] ?? 0);
            $res = Customer::toggleStatus($id);
            echo json_encode($res);
            break;

        case 'delete_customer':
            if (!Auth::hasPermission('customers.delete') || Auth::isSpecialUser()) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to delete customer.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $id = (int)($input['id'] ?? 0);
            $res = Customer::delete($id);
            echo json_encode($res);
            break;

        case 'restore_customer':
            if (!Auth::hasPermission('customers.delete') || Auth::isSpecialUser()) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to restore customer.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $id = (int)($input['id'] ?? 0);
            $res = Customer::restore($id);
            echo json_encode($res);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid API action.']);
            break;
    }
} catch (Throwable $e) {
    Logger::error("Customer API Exception ({$action}): " . $e->getMessage(), $e);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
