<?php
/**
 * Curtiss POS - Customer Credit Collections API Endpoint (Stage 9)
 * Provides JSON API handlers for fetching customer credit outstanding summaries and processing collection payments.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/CustomerPayment.php';

Auth::requireLogin();
if (Auth::isSpecialUser()) {
    echo json_encode(['success' => false, 'message' => 'Permission denied: Special User is view-only.']);
    exit;
}

$user = Auth::user();
$branchId = (int)($_SESSION['branch_id'] ?? 1);
$userId = (int)($user['id'] ?? 1);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_customer_outstanding':
            if (!Auth::hasPermission('customers.view') && !Auth::hasPermission('customers.pay')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied.']);
                exit;
            }

            $customerId = (int)($_GET['customer_id'] ?? $_POST['customer_id'] ?? 0);
            if ($customerId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Valid customer ID is required.']);
                exit;
            }

            $summary = CustomerPayment::getCustomerOutstandingSummary($customerId);
            echo json_encode($summary);
            break;

        case 'process_customer_payment':
            if (!Auth::hasPermission('customers.pay')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to process customer credit payments.']);
                exit;
            }

            $rawInput = file_get_contents('php://input');
            $data = json_decode($rawInput, true);

            if (!$data) {
                $data = $_POST;
            }

            if (isset($data['splits']) && is_string($data['splits'])) {
                $data['splits'] = json_decode($data['splits'], true) ?? [];
            }

            $result = CustomerPayment::processPayment($data, $userId, $branchId);
            echo json_encode($result);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid API action.']);
            break;
    }
} catch (Throwable $e) {
    Logger::error("Customer Payments API Error: " . $e->getMessage(), $e);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
