<?php
/**
 * Curtiss POS - POS Billing Core AJAX API (Stage 6)
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Sale.php';
require_once __DIR__ . '/core/Customer.php';
require_once __DIR__ . '/core/Logger.php';

try {
    if (!Auth::isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized session.']);
        exit;
    }

    if (!Auth::hasPermission('pos.access') || Auth::isSpecialUser()) {
        echo json_encode(['success' => false, 'message' => 'Access denied: Special User is strictly view-only.']);
        exit;
    }

    $branchId = Auth::getCurrentBranchId();
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
            $products = Sale::searchProducts($branchId, $q);
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

        case 'search_customers':
            $q = trim($_GET['q'] ?? $_POST['q'] ?? $inputData['q'] ?? '');
            $res = Customer::all(['search' => $q, 'status' => 'active'], 1, 10);
            echo json_encode(['success' => true, 'data' => $res['items']]);
            break;

        case 'quick_create_customer':
            $name = trim($inputData['name'] ?? $_POST['name'] ?? '');
            $phone = trim($inputData['phone'] ?? $_POST['phone'] ?? '');
            $whatsapp = trim($inputData['whatsapp'] ?? $_POST['whatsapp'] ?? '');

            if (empty($name)) {
                echo json_encode(['success' => false, 'message' => 'Customer name is required.']);
                exit;
            }

            // Limit phone and whatsapp to max 10 numeric digits
            $cleanPhone = !empty($phone) ? substr(preg_replace('/[^0-9]/', '', $phone), 0, 10) : null;
            $cleanWhatsapp = !empty($whatsapp) ? substr(preg_replace('/[^0-9]/', '', $whatsapp), 0, 10) : null;

            $createRes = Customer::create([
                'name'         => $name,
                'phone'        => $cleanPhone,
                'whatsapp'     => $cleanWhatsapp,
                'credit_limit' => 0.00,
                'status'       => 'active'
            ]);

            if ($createRes['success']) {
                $newCust = Customer::find((int)$createRes['id']);
                echo json_encode([
                    'success'  => true,
                    'id'       => $createRes['id'],
                    'customer' => $newCust,
                    'message'  => "Customer '{$name}' created successfully."
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => $createRes['message']]);
            }
            break;

        case 'get_walkin_customer':
            $walkin = Database::fetchOne("SELECT * FROM customers WHERE is_walkin = 1 AND status = 'active' LIMIT 1");
            if (!$walkin) {
                $walkin = Database::fetchOne("SELECT * FROM customers WHERE customer_code = 'CUST-WALKIN' LIMIT 1");
            }
            if (!$walkin) {
                // Self-heal default Walk-in record if missing
                Database::execute("
                    INSERT INTO customers (customer_code, name, address, is_walkin, status, credit_limit, credit_balance)
                    VALUES ('CUST-WALKIN', 'Walk-in Customer', 'Default POS General Customer', 1, 'active', 0.00, 0.00)
                ");
                $walkin = Database::fetchOne("SELECT * FROM customers WHERE customer_code = 'CUST-WALKIN' LIMIT 1");
            }
            echo json_encode(['success' => true, 'data' => $walkin]);
            break;

        case 'parse_discount':
            $discountStr = $_GET['discount'] ?? $_POST['discount'] ?? $inputData['discount'] ?? '';
            $subtotal = (float)($_GET['subtotal'] ?? $_POST['subtotal'] ?? $inputData['subtotal'] ?? 0);
            $parsed = Sale::parseDiscount($discountStr, $subtotal);
            $suggestion = Sale::calculateRoundupSuggestion(max(0, $subtotal - $parsed['total_discount']));
            echo json_encode([
                'success'    => true, 
                'parsed'     => $parsed,
                'suggestion' => $suggestion
            ]);
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

        case 'validate_voucher':
            require_once __DIR__ . '/core/GiftVoucher.php';
            $code = trim($_GET['code'] ?? $_POST['code'] ?? $inputData['code'] ?? '');
            $payable = (float)($_GET['payable'] ?? $_POST['payable'] ?? $inputData['payable'] ?? 0);
            $valRes = GiftVoucher::validateVoucher($code, $payable);
            echo json_encode($valRes);
            break;

        case 'search_sales_reps':
            require_once __DIR__ . '/core/Employee.php';
            $q = trim($_GET['q'] ?? $_POST['q'] ?? $inputData['q'] ?? '');
            $reps = Employee::getSalesReps($branchId);
            if (!empty($q)) {
                $qLower = strtolower($q);
                $reps = array_values(array_filter($reps, function($r) use ($qLower) {
                    return strpos(strtolower($r['full_name']), $qLower) !== false ||
                           strpos(strtolower($r['employee_code']), $qLower) !== false;
                }));
            }
            echo json_encode(['success' => true, 'data' => $reps]);
            break;

        case 'get_billing_cache':
            $products = Database::fetchAll("
                SELECT p.id, p.name, p.barcode, p.selling_price, p.cost, p.low_stock_threshold, p.category_id, p.brand_id
                FROM products p
                WHERE p.status = 'active' AND p.deleted_at IS NULL
            ");

            $lots = Database::fetchAll("
                SELECT pl.id, pl.product_id, pl.lot_number, pl.cost, pl.selling_price, pl.remaining_quantity
                FROM product_lots pl
                WHERE pl.branch_id = :b AND pl.remaining_quantity > 0 AND pl.status = 'active'
            ", [':b' => $branchId]);

            $customers = Database::fetchAll("
                SELECT id, name, phone, customer_code, is_walkin, credit_limit, credit_balance
                FROM customers
                WHERE status = 'active'
            ");

            $salesReps = Database::fetchAll("
                SELECT id, employee_code, full_name, phone, job_role
                FROM employees
                WHERE status = 'active' AND deleted_at IS NULL AND is_sales_rep = 1 AND branch_id = :b
                ORDER BY full_name ASC
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
                'customers'     => $customers,
                'sales_reps'    => $salesReps,
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
