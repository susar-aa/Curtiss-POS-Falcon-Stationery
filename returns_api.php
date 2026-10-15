<?php
/**
 * Curtiss POS - Sales Returns & Exchanges API Endpoint (Stage 8)
 * Provides JSON API handlers for invoice lookups, item validation, and return finalization.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/SaleReturn.php';
require_once __DIR__ . '/core/Sale.php';
require_once __DIR__ . '/core/Customer.php';

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
        case 'search_sales_or_customers':
            if (!Auth::hasPermission('returns.view') && !Auth::hasPermission('returns.create')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied.']);
                exit;
            }

            $query = trim($_GET['query'] ?? $_POST['query'] ?? '');
            if (empty($query)) {
                echo json_encode(['success' => true, 'invoices' => [], 'customers' => []]);
                exit;
            }

            $results = SaleReturn::searchSalesOrCustomers($query, $branchId);
            echo json_encode([
                'success'   => true, 
                'invoices'  => $results['invoices'], 
                'customers' => $results['customers']
            ]);
            break;

        case 'get_customer_invoices':
            if (!Auth::hasPermission('returns.view') && !Auth::hasPermission('returns.create')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied.']);
                exit;
            }

            $customerId = (int)($_GET['customer_id'] ?? $_POST['customer_id'] ?? 0);
            if ($customerId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Valid customer ID required.']);
                exit;
            }

            $invoices = SaleReturn::getCustomerCompletedInvoices($customerId, $branchId);
            echo json_encode(['success' => true, 'invoices' => $invoices]);
            break;

        case 'search_invoice':
            if (!Auth::hasPermission('returns.view') && !Auth::hasPermission('returns.create')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied.']);
                exit;
            }

            $invoiceNo = trim($_GET['invoice_no'] ?? $_POST['invoice_no'] ?? '');
            if (empty($invoiceNo)) {
                echo json_encode(['success' => false, 'message' => 'Please enter an invoice number.']);
                exit;
            }

            $saleData = SaleReturn::searchSaleForReturn($invoiceNo, $branchId);
            if (!$saleData) {
                echo json_encode(['success' => false, 'message' => "Completed invoice '{$invoiceNo}' not found in this branch."]);
                exit;
            }

            echo json_encode(['success' => true, 'sale' => $saleData]);
            break;

        case 'search_sold_products':
            if (!Auth::hasPermission('returns.create')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied.']);
                exit;
            }

            $query = trim($_GET['query'] ?? $_POST['query'] ?? '');
            if (empty($query)) {
                echo json_encode(['success' => true, 'products' => []]);
                exit;
            }

            $products = SaleReturn::searchSoldProducts($branchId, $query);
            echo json_encode(['success' => true, 'products' => $products]);
            break;

        case 'search_products':
            if (!Auth::hasPermission('returns.create')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied.']);
                exit;
            }

            $query = trim($_GET['query'] ?? $_POST['query'] ?? '');
            if (empty($query)) {
                echo json_encode(['success' => true, 'products' => []]);
                exit;
            }

            $products = Sale::searchProducts($branchId, $query);
            echo json_encode(['success' => true, 'products' => $products]);
            break;

        case 'process_return':
            if (!Auth::hasPermission('returns.create')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to process returns.']);
                exit;
            }

            $rawInput = file_get_contents('php://input');
            $data = json_decode($rawInput, true);

            if (!$data) {
                $data = $_POST;
            }

            $saleId = !empty($data['sale_id']) ? (int)$data['sale_id'] : null;
            if ($saleId === null && !Auth::hasPermission('returns.no_invoice')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to process returns without original invoice.']);
                exit;
            }

            $result = SaleReturn::processReturn($data, $userId, $branchId);
            echo json_encode($result);
            break;

        case 'get_return_details':
            if (!Auth::hasPermission('returns.view')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied.']);
                exit;
            }

            $returnId = (int)($_GET['id'] ?? 0);
            $returnData = SaleReturn::find($returnId);

            if (!$returnData) {
                echo json_encode(['success' => false, 'message' => 'Return record not found.']);
                exit;
            }

            echo json_encode(['success' => true, 'return' => $returnData]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid API action.']);
            break;
    }
} catch (Throwable $e) {
    Logger::error("Returns API Error: " . $e->getMessage(), $e);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
