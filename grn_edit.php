<?php
/**
 * Curtiss POS - Edit Goods Received Note (GRN) Controller (Stage 4)
 * Allows modifying GRN header and items ONLY while in Draft status.
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

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) {
    header('Location: grns');
    exit();
}

$grnData = GRN::find($id);
if (!$grnData) {
    header('Location: grns?error=' . urlencode('Goods Received Note not found.'));
    exit();
}

// Strict business constraint: Only Draft GRNs can be edited
if ($grnData['status'] !== 'Draft') {
    header('Location: grns?error=' . urlencode("GRN {$grnData['grn_number']} is in '{$grnData['status']}' status and cannot be edited. Only Draft GRNs can be modified."));
    exit();
}

$errorMsg = null;
$poData = null;
if (!empty($grnData['po_id'])) {
    $poData = PurchaseOrder::find((int)$grnData['po_id']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $header = [
        'po_id'                   => !empty($_POST['po_id']) ? (int)$_POST['po_id'] : $grnData['po_id'],
        'supplier_id'             => (int)($_POST['supplier_id'] ?? $grnData['supplier_id']),
        'branch_id'               => (int)$grnData['branch_id'],
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

    $res = GRN::update($id, $header, $items);
    if ($res['success']) {
        header('Location: grns?updated=1');
        exit();
    } else {
        $errorMsg = $res['message'];
        // Keep entered data in grnData for re-display
        $grnData['supplier_id'] = $header['supplier_id'];
        $grnData['supplier_invoice_no'] = $header['supplier_invoice_no'];
        $grnData['supplier_invoice_date'] = $header['supplier_invoice_date'];
        $grnData['payment_due_date'] = $header['payment_due_date'];
        $grnData['received_date'] = $header['received_date'];
        $grnData['supplier_invoice_amount'] = $header['supplier_invoice_amount'];
        $grnData['difference_reason'] = $header['difference_reason'];
        $grnData['notes'] = $header['notes'];
    }
}

$suppliers = Supplier::all(true);
$products = Product::getList(['status' => 'active'], 1, 5000)['items'];

View::render('purchasing/grn_form', [
    'suppliers'        => $suppliers,
    'products'         => $products,
    'poData'           => $poData,
    'grnData'          => $grnData,
    'isEdit'           => true,
    'activeBranchName' => $grnData['branch_name'] ?? $activeBranchName,
    'errorMsg'         => $errorMsg
], "Edit GRN {$grnData['grn_number']} - Curtiss POS");
