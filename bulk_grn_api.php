<?php
/**
 * Curtiss POS - Bulk GRN API Endpoint
 * Handles asynchronous supplier product loading, price lots retrieval, barcode validation, and AJAX GRN submission.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/BulkGRN.php';
require_once __DIR__ . '/core/Supplier.php';
require_once __DIR__ . '/core/Product.php';
require_once __DIR__ . '/core/Brand.php';
require_once __DIR__ . '/core/Category.php';

Auth::requireAuth();

if (!Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$user = Auth::user();
$activeBranchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? $user['active_branch_id'] ?? 1);

$rawInput = file_get_contents('php://input');
$jsonPayload = !empty($rawInput) ? json_decode($rawInput, true) : null;
$action = $_GET['action'] ?? $_POST['action'] ?? ($jsonPayload['action'] ?? '');

// Action 1: Get all active products for a supplier with lots & stock
if ($action === 'get_supplier_products') {
    $supplierId = (int)($_GET['supplier_id'] ?? 0);
    if ($supplierId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid supplier selected.']);
        exit();
    }

    $supplier = Supplier::find($supplierId);
    if (!$supplier) {
        echo json_encode(['success' => false, 'message' => 'Supplier not found.']);
        exit();
    }

    $products = BulkGRN::getSupplierProductsWithLots($supplierId, $activeBranchId);

    echo json_encode([
        'success'  => true,
        'supplier' => [
            'id'                  => (int)$supplier['id'],
            'name'                => $supplier['name'],
            'code'                => $supplier['supplier_code'] ?? '',
            'phone'               => $supplier['phone'] ?? '',
            'whatsapp'            => $supplier['whatsapp'] ?? '',
            'address'             => $supplier['address'] ?? '',
            'outstanding_payable' => (float)($supplier['outstanding_payable'] ?? 0)
        ],
        'count'    => count($products),
        'products' => $products
    ]);
    exit();
}

// Action 2: Generate unique barcode
if ($action === 'generate_barcode') {
    $prefix = 'B' . date('ymd');
    $barcode = $prefix . rand(1000, 9999);
    while (Database::fetchOne("SELECT id FROM products WHERE barcode = :b", [':b' => $barcode])) {
        $barcode = $prefix . rand(1000, 9999);
    }

    echo json_encode(['success' => true, 'barcode' => $barcode]);
    exit();
}

// Action 3: Check barcode uniqueness
if ($action === 'check_barcode') {
    $barcode = trim($_GET['barcode'] ?? '');
    if (empty($barcode)) {
        echo json_encode(['success' => true, 'available' => false, 'message' => 'Barcode cannot be empty.']);
        exit();
    }

    $exists = Database::fetchOne("SELECT id, name FROM products WHERE barcode = :b AND deleted_at IS NULL", [':b' => $barcode]);
    if ($exists) {
        echo json_encode([
            'success'   => true,
            'available' => false,
            'product'   => ['id' => $exists['id'], 'name' => $exists['name']],
            'message'   => "Barcode already in use by product '{$exists['name']}'."
        ]);
    } else {
        echo json_encode(['success' => true, 'available' => true, 'message' => 'Barcode is available.']);
    }
    exit();
}

// Action 4: Submit Bulk GRN via AJAX
if ($action === 'save_bulk_grn') {
    $payload = $jsonPayload ?? $_POST;

    $header = [
        'supplier_id'             => (int)($payload['supplier_id'] ?? 0),
        'branch_id'               => $activeBranchId,
        'supplier_invoice_no'     => trim($payload['supplier_invoice_no'] ?? ''),
        'supplier_invoice_date'   => trim($payload['supplier_invoice_date'] ?? ''),
        'payment_due_date'        => !empty($payload['payment_due_date']) ? trim($payload['payment_due_date']) : null,
        'received_date'           => trim($payload['received_date'] ?? date('Y-m-d')),
        'supplier_invoice_amount' => (float)($payload['supplier_invoice_amount'] ?? 0),
        'difference_reason'       => trim($payload['difference_reason'] ?? 'None'),
        'notes'                   => trim($payload['notes'] ?? ''),
        'user_id'                 => (int)$user['id']
    ];

    $existingItems = $payload['existing_items'] ?? [];
    $newProducts = $payload['new_products'] ?? [];
    $approveImmediately = !empty($payload['approve_immediately']) && Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER);

    $result = BulkGRN::process($header, $existingItems, $newProducts, $approveImmediately);
    echo json_encode($result);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid action requested.']);
