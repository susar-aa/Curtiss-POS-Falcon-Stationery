<?php
/**
 * Curtiss POS - KOKO Payments Gateway API Controller
 * Provides REST endpoints for POS terminal:
 * - initiate / create_order
 * - check_status
 * - verify_payment
 * - cancel_order
 * - resend_sms
 * - get_config
 * - save_config
 * - webhook
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/KokoPay.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Support JSON body payloads
$rawBody = file_get_contents('php://input');
$jsonBody = [];
if (!empty($rawBody)) {
    $parsed = json_decode($rawBody, true);
    if (is_array($parsed)) {
        $jsonBody = $parsed;
        if (empty($action) && isset($jsonBody['action'])) {
            $action = $jsonBody['action'];
        }
    }
}

// 1. Webhook endpoint (Public callback from Koko server)
if ($action === 'webhook') {
    try {
        $orderId = $jsonBody['order_id'] ?? $jsonBody['koko_order_id'] ?? $_POST['order_id'] ?? $_GET['order_id'] ?? '';
        $paymentId = $jsonBody['payment_id'] ?? $jsonBody['transaction_id'] ?? $_POST['payment_id'] ?? null;
        $status = strtolower($jsonBody['status'] ?? $_POST['status'] ?? '');

        if (empty($orderId)) {
            echo json_encode(['success' => false, 'message' => 'Missing order_id in webhook']);
            exit();
        }

        if (in_array($status, ['success', 'paid', 'approved', 'completed'], true) || empty($status)) {
            $res = KokoPay::verifyAndMarkPaid($orderId, $paymentId);
            echo json_encode($res);
        } else {
            echo json_encode(['success' => true, 'message' => 'Webhook received with status: ' . $status]);
        }
        exit();
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => 'Webhook error: ' . $e->getMessage()]);
        exit();
    }
}

// Require authenticated session for terminal & admin actions
Auth::requireAuth();
$user = Auth::user();
$userId = (int)($user['id'] ?? 1);
$branchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? 1);

try {
    switch ($action) {
        case 'get_config':
            $config = KokoPay::getConfig();
            // Mask secret in public config response
            if (!empty($config['api_secret']) && !Auth::hasRole(ROLE_SUPER_ADMIN)) {
                $config['api_secret'] = '••••••••' . substr($config['api_secret'], -4);
            }
            echo json_encode(['success' => true, 'config' => $config]);
            break;

        case 'save_config':
            if (!Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN)) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                exit();
            }
            $configData = !empty($jsonBody['settings']) ? $jsonBody['settings'] : (!empty($_POST['settings']) ? $_POST['settings'] : $_POST);
            $saved = KokoPay::saveConfig($configData, $userId);
            echo json_encode(['success' => $saved, 'message' => $saved ? 'Configuration saved successfully.' : 'Failed to save configuration.']);
            break;

        case 'create_order':
        case 'initiate':
            $amount = (float)($jsonBody['amount'] ?? $_POST['amount'] ?? 0);
            $invoiceNo = trim($jsonBody['invoice_no'] ?? $_POST['invoice_no'] ?? '');
            $channel = trim($jsonBody['channel'] ?? $_POST['channel'] ?? 'qr');
            $customerPhone = trim($jsonBody['customer_phone'] ?? $_POST['customer_phone'] ?? '');
            $customerName = trim($jsonBody['customer_name'] ?? $_POST['customer_name'] ?? '');
            $saleId = !empty($jsonBody['sale_id']) ? (int)$jsonBody['sale_id'] : (!empty($_POST['sale_id']) ? (int)$_POST['sale_id'] : null);
            $reqBranchId = !empty($jsonBody['branch_id']) ? (int)$jsonBody['branch_id'] : (!empty($_POST['branch_id']) ? (int)$_POST['branch_id'] : $branchId);

            $result = KokoPay::createPaymentOrder([
                'amount'         => $amount,
                'invoice_no'     => $invoiceNo,
                'channel'        => $channel,
                'customer_phone' => $customerPhone,
                'customer_name'  => $customerName,
                'sale_id'        => $saleId,
                'branch_id'      => $reqBranchId,
                'user_id'        => $userId
            ]);

            echo json_encode($result);
            break;

        case 'check_status':
            $orderId = trim($jsonBody['order_id'] ?? $_POST['order_id'] ?? $_GET['order_id'] ?? '');
            if (empty($orderId)) {
                echo json_encode(['success' => false, 'message' => 'Missing order_id']);
                exit();
            }
            $result = KokoPay::checkOrderStatus($orderId);
            echo json_encode($result);
            break;

        case 'verify_payment':
            $orderId = trim($jsonBody['order_id'] ?? $_POST['order_id'] ?? '');
            $paymentId = trim($jsonBody['payment_id'] ?? $_POST['payment_id'] ?? '');
            $saleId = !empty($jsonBody['sale_id']) ? (int)$jsonBody['sale_id'] : null;

            if (empty($orderId)) {
                echo json_encode(['success' => false, 'message' => 'Missing order_id']);
                exit();
            }
            $result = KokoPay::verifyAndMarkPaid($orderId, $paymentId ?: null, $saleId);
            echo json_encode($result);
            break;

        case 'cancel_order':
            $orderId = trim($jsonBody['order_id'] ?? $_POST['order_id'] ?? '');
            if (empty($orderId)) {
                echo json_encode(['success' => false, 'message' => 'Missing order_id']);
                exit();
            }
            $result = KokoPay::cancelOrder($orderId);
            echo json_encode($result);
            break;

        case 'resend_sms':
            $orderId = trim($jsonBody['order_id'] ?? $_POST['order_id'] ?? '');
            $phone = trim($jsonBody['phone'] ?? $_POST['phone'] ?? '');

            if (empty($orderId) || empty($phone)) {
                echo json_encode(['success' => false, 'message' => 'order_id and phone are required']);
                exit();
            }
            $result = KokoPay::resendSms($orderId, $phone);
            echo json_encode($result);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid or unknown action.']);
            break;
    }
} catch (Throwable $e) {
    Logger::error("koko_api.php error: " . $e->getMessage(), $e);
    echo json_encode(['success' => false, 'message' => 'API Server Exception: ' . $e->getMessage()]);
}
