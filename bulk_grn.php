<?php
/**
 * Curtiss POS - Bulk Goods Received Note (Bulk GRN) Controller
 * Fast, supplier-based bulk GRN entry where all supplier products appear automatically,
 * users enter quantities only for received products, reuse or create price lots,
 * and add new products directly.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/BulkGRN.php';
require_once __DIR__ . '/core/GRN.php';
require_once __DIR__ . '/core/Supplier.php';
require_once __DIR__ . '/core/Product.php';
require_once __DIR__ . '/core/Brand.php';
require_once __DIR__ . '/core/Category.php';

Auth::requireAuth();

if (!Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)) {
    View::renderBlank('errors/403');
    exit();
}

$user = Auth::user();
$activeBranchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? $user['active_branch_id'] ?? 1);
$activeBranchName = $user['active_branch_name'] ?? 'Main Branch';

$errorMsg = null;
$successMsg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $header = [
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

    $existingItems = $_POST['items'] ?? [];
    $newProducts = $_POST['new_products'] ?? [];
    $approveImmediately = !empty($_POST['approve_immediately']);

    $res = BulkGRN::process($header, $existingItems, $newProducts, $approveImmediately);
    if ($res['success']) {
        header('Location: grns?created=1');
        exit();
    } else {
        $errorMsg = $res['message'];
    }
}

$suppliers = Supplier::all(true);
$brands = Brand::all(true);
$categories = Category::all(true);

$maxRow = Database::fetchOne("SELECT MAX(id) AS max_id FROM grns");
$nextId = ((int)($maxRow['max_id'] ?? 0)) + 1;
$nextGrnNumber = 'GRN-' . date('Ym') . '-' . sprintf('%04d', $nextId);

View::render('purchasing/bulk_grn_form', [
    'suppliers'        => $suppliers,
    'brands'           => $brands,
    'categories'       => $categories,
    'nextGrnNumber'    => $nextGrnNumber,
    'activeBranchId'   => $activeBranchId,
    'activeBranchName' => $activeBranchName,
    'errorMsg'         => $errorMsg,
    'successMsg'       => $successMsg
], 'Bulk Goods Received Note (Bulk GRN) - Curtiss POS');
