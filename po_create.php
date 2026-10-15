<?php
/**
 * Curtiss POS - Purchase Order Create Controller (Stage 4)
 */

require_once __DIR__ . '/config/config.php';
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

$errorMsg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $header = [
        'supplier_id'     => (int)($_POST['supplier_id'] ?? 0),
        'branch_id'       => $activeBranchId,
        'order_date'      => trim($_POST['order_date'] ?? date('Y-m-d')),
        'expected_date'   => trim($_POST['expected_date'] ?? ''),
        'discount_amount' => (float)($_POST['discount_amount'] ?? 0),
        'notes'           => trim($_POST['notes'] ?? ''),
        'user_id'         => $user['id']
    ];

    $rawItems = $_POST['items'] ?? [];
    $items = [];

    foreach ($rawItems as $ri) {
        $items[] = [
            'product_id'    => (int)($ri['product_id'] ?? 0),
            'quantity'      => (float)($ri['quantity'] ?? 0),
            'unit_cost'     => (float)($ri['unit_cost'] ?? 0),
            'selling_price' => (float)($ri['selling_price'] ?? 0)
        ];
    }

    $res = PurchaseOrder::create($header, $items);
    if ($res['success']) {
        header('Location: purchase_orders?created=1');
        exit();
    } else {
        $errorMsg = $res['message'];
    }
}

// Generate Next PO Number sequence preview
$maxRow = Database::fetchOne("SELECT MAX(id) AS max_id FROM purchase_orders");
$nextId = ((int)($maxRow['max_id'] ?? 0)) + 1;
$nextPoNumber = 'PO-' . date('Ym') . '-' . sprintf('%04d', $nextId);

$suppliers = Supplier::all(true);

// Compute Outstanding Payable per supplier from approved GRNs (Batch query)
$outstandingRows = Database::fetchAll("
    SELECT supplier_id, SUM(actual_grn_total - paid_amount) AS total_outstanding
    FROM grns
    WHERE status = 'Approved'
    GROUP BY supplier_id
");
$outstandingMap = [];
foreach ($outstandingRows as $or) {
    $outstandingMap[(int)$or['supplier_id']] = max(0, (float)$or['total_outstanding']);
}

// Compute Linked Products count per supplier (Batch query)
$linkedRows = Database::fetchAll("
    SELECT supplier_id, COUNT(*) AS total_linked
    FROM products
    WHERE supplier_id IS NOT NULL AND status = 'active' AND deleted_at IS NULL
    GROUP BY supplier_id
");
$linkedMap = [];
foreach ($linkedRows as $lr) {
    $linkedMap[(int)$lr['supplier_id']] = (int)$lr['total_linked'];
}

foreach ($suppliers as &$s) {
    $sId = (int)$s['id'];
    $s['outstanding_payable'] = $outstandingMap[$sId] ?? 0.00;
    $s['linked_products'] = $linkedMap[$sId] ?? 0;
}
unset($s);

$products = Database::fetchAll("
    SELECT id, name, COALESCE(sku, barcode) AS sku, barcode, cost, selling_price 
    FROM products 
    WHERE status = 'active' AND deleted_at IS NULL 
    ORDER BY name ASC
");

View::render('purchasing/po_form', [
    'suppliers'        => $suppliers,
    'products'         => $products,
    'nextPoNumber'     => $nextPoNumber,
    'activeBranchName' => $user['active_branch_name'],
    'errorMsg'         => $errorMsg
], 'Create Purchase Order - Curtiss POS');
