<?php
/**
 * Curtiss POS - Create Goods Received Note (GRN) Controller (Stage 4)
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/GRN.php';
require_once __DIR__ . '/core/PurchaseOrder.php';
require_once __DIR__ . '/core/Supplier.php';
require_once __DIR__ . '/core/Product.php';

Auth::requireAuth();

if (!Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)) {
    View::renderBlank('errors/403');
    exit();
}

$user = Auth::user();
$activeBranchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? $user['active_branch_id'] ?? 1);
$activeBranchName = $user['active_branch_name'] ?? 'Main Branch';

$errorMsg = null;
$editId = (int)($_GET['id'] ?? 0);
if ($editId > 0) {
    header("Location: grn_edit?id=$editId");
    exit();
}

$poId = (int)($_GET['po_id'] ?? 0);
$poData = null;
if ($poId > 0) {
    $poData = PurchaseOrder::find($poId);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $header = [
        'po_id'                   => !empty($_POST['po_id']) ? (int)$_POST['po_id'] : null,
        'supplier_id'             => (int)($_POST['supplier_id'] ?? 0),
        'branch_id'               => $activeBranchId,
        'supplier_invoice_no'     => trim($_POST['supplier_invoice_no'] ?? ''),
        'supplier_invoice_date'   => trim($_POST['supplier_invoice_date'] ?? ''),
        'payment_due_date'        => !empty($_POST['payment_due_date']) ? trim($_POST['payment_due_date']) : null,
        'received_date'           => trim($_POST['received_date'] ?? date('Y-m-d')),
        'supplier_invoice_amount' => (float)($_POST['supplier_invoice_amount'] ?? 0),
        'difference_reason'       => trim($_POST['difference_reason'] ?? 'None'),
        'notes'                   => trim($_POST['notes'] ?? ''),
        'user_id'                 => $user['id']
    ];

    $rawItems = $_POST['items'] ?? [];
    $items = [];

    foreach ($rawItems as $ri) {
        $items[] = [
            'product_id'    => (int)($ri['product_id'] ?? 0),
            'quantity'      => (float)($ri['quantity'] ?? 0),
            'unit_cost'     => (float)($ri['unit_cost'] ?? 0),
            'selling_price' => (float)($ri['selling_price'] ?? 0),
            'expiry_date'   => !empty($ri['expiry_date']) ? trim($ri['expiry_date']) : null,
            'batch_number'  => !empty($ri['batch_number']) ? trim($ri['batch_number']) : null
        ];
    }

    $allowOverReceipt = true;
    $res = GRN::create($header, $items, $allowOverReceipt);
    if ($res['success']) {
        header('Location: grns?created=1');
        exit();
    } else {
        $errorMsg = $res['message'];
    }
}

$suppliers = Supplier::all(true);
$products = Product::getList(['status' => 'active'], 1, 5000)['items'];

$maxRow = Database::fetchOne("SELECT MAX(id) AS max_id FROM grns");
$nextId = ((int)($maxRow['max_id'] ?? 0)) + 1;
$nextGrnNumber = 'GRN-' . date('Ym') . '-' . sprintf('%04d', $nextId);

View::render('purchasing/grn_form', [
    'suppliers'        => $suppliers,
    'products'         => $products,
    'poData'           => $poData,
    'nextGrnNumber'    => $nextGrnNumber,
    'activeBranchName' => $activeBranchName,
    'errorMsg'         => $errorMsg
], 'Create Goods Received Note (GRN) - Curtiss POS');
