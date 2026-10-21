<?php
/**
 * Curtiss POS - POS Billing Core AJAX API (Stage 6)
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Sale.php';
require_once __DIR__ . '/core/Customer.php';
require_once __DIR__ . '/core/DiscountEngine.php';
require_once __DIR__ . '/core/Logger.php';

try {
    if (!Auth::isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized session.']);
        exit;
    }

    if ((!Auth::hasPermission('pos.access') && !Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER, ROLE_CASHIER)) || Auth::isSpecialUser()) {
        echo json_encode(['success' => false, 'message' => 'Access denied: Special User is strictly view-only or unauthorized.']);
        exit;
    }

    $branchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? Auth::getCurrentBranchId() ?? 1);
    if (!empty($_GET['branch_id'])) {
        $branchId = (int)$_GET['branch_id'];
    }
    $userId = Auth::getUserId();

    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    // Support raw JSON body for complex payloads
    $inputData = [];
    $rawInput = file_get_contents('php://input');
    if (!empty($rawInput)) {
        $parsed = json_decode($rawInput, true);
        if (is_array($parsed)) {
            $inputData = $parsed;
        }
    }
    if (empty($action) && !empty($inputData['action'])) {
        $action = $inputData['action'];
    }

    switch ($action) {
        case 'search_products':
            $q = trim($_GET['q'] ?? $_POST['q'] ?? $inputData['q'] ?? '');
            $products = Sale::searchProducts($branchId, $q, false);
            echo json_encode(['success' => true, 'data' => $products]);
            break;

        case 'get_product_lots':
            $productId = (int)($_GET['product_id'] ?? $_POST['product_id'] ?? $inputData['product_id'] ?? 0);
            if ($productId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid product ID.']);
                exit;
            }
            $lots = Sale::getAvailableLotsForProduct($productId, $branchId);
            echo json_encode(['success' => true, 'data' => $lots]);
            break;

        case 'get_walkin_customer':
            echo json_encode([
                'success' => true,
                'data' => [
                    'id' => 1,
                    'name' => 'Walk-in Customer',
                    'customer_code' => 'CUST-WALKIN',
                    'is_walkin' => 1,
                    'status' => 'active',
                    'credit_limit' => 0.00,
                    'credit_balance' => 0.00
                ]
            ]);
            break;

        case 'parse_discount':
            $discountStr = $_GET['discount'] ?? $_POST['discount'] ?? $inputData['discount'] ?? '';
            $subtotal = (float)($_GET['subtotal'] ?? $_POST['subtotal'] ?? $inputData['subtotal'] ?? 0);
            $eligibleSubtotal = isset($_GET['eligible_subtotal']) ? (float)$_GET['eligible_subtotal'] : (isset($_POST['eligible_subtotal']) ? (float)$_POST['eligible_subtotal'] : (isset($inputData['eligible_subtotal']) ? (float)$inputData['eligible_subtotal'] : $subtotal));
            $parsed = Sale::parseDiscount($discountStr, $eligibleSubtotal);
            $suggestion = Sale::calculateRoundupSuggestion(max(0, $subtotal - $parsed['total_discount']));
            echo json_encode([
                'success'    => true, 
                'parsed'     => $parsed,
                'suggestion' => $suggestion
            ]);
            break;

        case 'evaluate_discounts':
            $items = $inputData['items'] ?? (isset($_POST['items']) ? (is_array($_POST['items']) ? $_POST['items'] : json_decode($_POST['items'], true)) : []);
            if (!is_array($items)) {
                $items = [];
            }
            $context = [
                'branch_id'               => (int)($inputData['branch_id'] ?? $_POST['branch_id'] ?? $branchId),
                'customer_id'             => !empty($inputData['customer_id'] ?? $_POST['customer_id']) ? (int)($inputData['customer_id'] ?? $_POST['customer_id']) : null,
                'coupon_code'             => trim($inputData['coupon_code'] ?? $_POST['coupon_code'] ?? ''),
                'payment_method'          => trim($inputData['payment_method'] ?? $_POST['payment_method'] ?? ''),
                'manual_invoice_discount' => trim($inputData['manual_invoice_discount'] ?? $_POST['manual_invoice_discount'] ?? ''),
            ];
            $result = DiscountEngine::evaluateCart($items, $context);
            echo json_encode([
                'success' => true,
                'data'    => $result
            ]);
            break;

        case 'validate_coupon':
            $couponCode = trim($inputData['coupon_code'] ?? $_POST['coupon_code'] ?? $_GET['coupon_code'] ?? '');
            $customerId = !empty($inputData['customer_id'] ?? $_POST['customer_id']) ? (int)($inputData['customer_id'] ?? $_POST['customer_id']) : null;
            $res = DiscountEngine::validateCoupon($couponCode, $branchId, $customerId);
            echo json_encode($res);
            break;

        case 'hold_invoice':
            if (!Auth::hasPermission('sales.hold') && !Auth::hasPermission('sales.create')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to hold invoices.']);
                exit;
            }
            $payload = !empty($inputData['items']) ? $inputData : $_POST;
            $res = Sale::holdInvoice($payload, $userId, $branchId);
            echo json_encode($res);
            break;

        case 'get_held_invoices':
            if (!Auth::hasPermission('sales.hold')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to view held invoices.']);
                exit;
            }
            $heldList = Sale::getHeldInvoices($branchId);
            echo json_encode(['success' => true, 'data' => $heldList]);
            break;

        case 'get_sales_history':
            $page = max(1, (int)($_GET['page'] ?? $inputData['page'] ?? 1));
            $limit = max(10, min(100, (int)($_GET['limit'] ?? $inputData['limit'] ?? 25)));
            $preset = trim($_GET['date_preset'] ?? $inputData['date_preset'] ?? 'today');
            $search = trim($_GET['search'] ?? $inputData['search'] ?? '');
            
            $filters = [];
            if (!empty($search)) {
                $filters['search'] = $search;
            }
            if (!empty($preset) && in_array($preset, ['today', 'yesterday', 'week', 'month'], true)) {
                $filters['date_preset'] = $preset;
            } elseif ($preset === 'all') {
                // No date restriction
            } else {
                $filters['date_preset'] = 'today';
            }
            if (!empty($_GET['status']) || !empty($inputData['status'])) {
                $filters['status'] = trim($_GET['status'] ?? $inputData['status']);
            }

            $res = Sale::getList($branchId, $filters, $page, $limit);
            echo json_encode([
                'success' => true,
                'data' => $res['items'],
                'pagination' => [
                    'page' => $res['page'],
                    'limit' => $res['limit'],
                    'totalItems' => $res['totalItems'],
                    'totalPages' => $res['totalPages']
                ]
            ]);
            break;

        case 'get_sale_details':
            $saleId = (int)($_GET['sale_id'] ?? $inputData['sale_id'] ?? 0);
            if ($saleId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid sale ID.']);
                exit;
            }
            $sale = Sale::getSaleDetails($saleId);
            if (!$sale) {
                echo json_encode(['success' => false, 'message' => 'Sale not found.']);
                exit;
            }
            echo json_encode([
                'success'  => true,
                'sale'     => $sale['sale'],
                'items'    => $sale['items'],
                'payments' => $sale['payments']
            ]);
            break;

        case 'resume_invoice':
            if (!Auth::hasPermission('sales.hold')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to resume held invoices.']);
                exit;
            }
            $saleId = (int)($_GET['sale_id'] ?? $_POST['sale_id'] ?? $inputData['sale_id'] ?? 0);
            $saleData = Sale::getSaleWithItems($saleId, $branchId);
            if (!$saleData) {
                echo json_encode(['success' => false, 'message' => 'Held invoice not found.']);
                exit;
            }
            echo json_encode(['success' => true, 'data' => $saleData]);
            break;

        case 'cancel_invoice':
            if (!Auth::hasPermission('sales.create')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to cancel sale.']);
                exit;
            }
            $saleId = (int)($_GET['sale_id'] ?? $_POST['sale_id'] ?? $inputData['sale_id'] ?? 0);
            $reason = trim($_GET['reason'] ?? $_POST['reason'] ?? $inputData['reason'] ?? 'Cancelled by Cashier');
            $res = Sale::cancelInvoice($saleId, $userId, $branchId, $reason);
            echo json_encode($res);
            break;

        case 'prepare_draft':
            if (!Auth::hasPermission('sales.create')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to create sales.']);
                exit;
            }
            $payload = !empty($inputData['items']) ? $inputData : $_POST;
            $payload['customer_id'] = 1;
            $payload['sales_rep_id'] = null;
            $res = Sale::prepareDraftSale($payload, $userId, $branchId);
            echo json_encode($res);
            break;

        case 'finalize_sale':
            if (!Auth::hasPermission('sales.create')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to finalize sales.']);
                exit;
            }
            $saleId = (int)($inputData['sale_id'] ?? $_POST['sale_id'] ?? 0);
            $payments = $inputData['payments'] ?? $_POST['payments'] ?? [];
            if (!empty($inputData['applied_promotions']) && is_array($inputData['applied_promotions'])) {
                $payments['applied_promotions'] = $inputData['applied_promotions'];
            }
            $payments['sales_rep_id'] = null;
            $res = Sale::finalizeSale($saleId, $payments, $userId, $branchId);
            echo json_encode($res);
            break;

        case 'get_bank_accounts':
            $bankAccounts = Database::fetchAll("
                SELECT id, bank_name, account_name, account_number 
                FROM bank_accounts 
                WHERE status = 'active' AND (branch_id IS NULL OR branch_id = :b)
                ORDER BY bank_name ASC, account_name ASC
            ", [':b' => $branchId]);
            echo json_encode(['success' => true, 'data' => $bankAccounts]);
            break;

        case 'get_billing_cache':
            $products = Database::fetchAll("
                SELECT p.id, p.name, p.sku, p.barcode, p.selling_price, p.cost, p.low_stock_threshold, p.category_id, p.brand_id
                FROM products p
                WHERE p.status = 'active' AND p.deleted_at IS NULL
            ");

            $lots = Database::fetchAll("
                SELECT pl.id, pl.product_id, pl.lot_number, pl.cost, pl.selling_price, pl.remaining_quantity
                FROM product_lots pl
                WHERE pl.branch_id = :b AND pl.status = 'active'
            ", [':b' => $branchId]);

            $bankAccounts = Database::fetchAll("
                SELECT id, bank_name, account_name, account_number 
                FROM bank_accounts 
                WHERE status = 'active' AND (branch_id IS NULL OR branch_id = :b)
                ORDER BY bank_name ASC, account_name ASC
            ", [':b' => $branchId]);

            $branchInfo = Database::fetchOne("SELECT id, name, code FROM branches WHERE id = :id", [':id' => $branchId]);

            echo json_encode([
                'success'       => true,
                'timestamp'     => time(),
                'branch'        => $branchInfo,
                'products'      => $products,
                'lots'          => $lots,
                'customers'     => [],
                'sales_reps'    => [],
                'bank_accounts' => $bankAccounts
            ]);
            break;

        case 'sync_offline_sale':
            if (!Auth::hasPermission('sales.create')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied to sync offline sales.']);
                exit;
            }
            $payload = !empty($inputData['offline_uuid']) ? $inputData : $_POST;
            $res = Sale::syncOfflineSale($payload, $userId, $branchId);
            echo json_encode($res);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid POS action requested.']);
            break;
    }
} catch (Throwable $e) {
    Logger::error("POS API Exception: " . $e->getMessage(), $e);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
