<?php
/**
 * Curtiss POS - Stock Transfer Create & Edit Controller
 * Professional Full-Screen Procurement Panel Architecture
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/View.php';
require_once __DIR__ . '/core/AdvancedInventory.php';

Auth::requireAuth();

if (!Auth::hasPermission('create_stock_transfer') && !Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)) {
    View::renderBlank('errors/403');
    exit();
}

$user = Auth::user();
$userId = (int)$user['id'];
$activeBranchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? $user['active_branch_id'] ?? 1);

$currentBranch = Database::fetchOne("
    SELECT id, name, code, address, phone 
    FROM branches 
    WHERE id = :id
", [':id' => $activeBranchId]) ?: [
    'id' => $activeBranchId,
    'name' => 'Main Branch',
    'code' => 'BR-01',
    'address' => '',
    'phone' => ''
];

$errorMsg = null;
$successMsg = null;

$editId = (int)($_GET['id'] ?? 0);
$transferData = null;
$existingItems = [];

if ($editId > 0) {
    $transferData = Database::fetchOne("
        SELECT st.*, fb.name AS from_branch_name, tb.name AS to_branch_name
        FROM stock_transfers st
        JOIN branches fb ON st.from_branch_id = fb.id
        JOIN branches tb ON st.to_branch_id = tb.id
        WHERE st.id = :id
    ", [':id' => $editId]);

    if (!$transferData) {
        header("Location: stock_transfers");
        exit();
    }

    if ($transferData['status'] !== 'draft') {
        header("Location: stock_transfers?err=" . urlencode("Only draft stock transfers can be edited."));
        exit();
    }

    $existingItems = Database::fetchAll("
        SELECT sti.*, p.name AS product_name, p.barcode, p.sku, pl.lot_number, pl.remaining_quantity AS lot_avail_qty
        FROM stock_transfer_items sti
        JOIN products p ON sti.product_id = p.id
        LEFT JOIN product_lots pl ON sti.source_lot_id = pl.id
        WHERE sti.transfer_id = :id
    ", [':id' => $editId]);
}

// Handle Form Submission (Both standard POST and JSON fetch)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isJson = (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false);
    $input = $isJson ? (json_decode(file_get_contents('php://input'), true) ?? []) : $_POST;

    $isEdit = !empty($input['id']);
    $transferId = (int)($input['id'] ?? 0);

    $headerData = [
        'from_branch_id' => $activeBranchId,
        'to_branch_id'   => (int)($input['to_branch_id'] ?? 0),
        'transfer_date'  => trim($input['transfer_date'] ?? date('Y-m-d')),
        'notes'          => trim($input['notes'] ?? ''),
        'send_now'       => !empty($input['send_now']) ? 1 : 0
    ];

    $rawItems = $input['items'] ?? [];
    $items = [];

    foreach ($rawItems as $ri) {
        $pId = (int)($ri['product_id'] ?? 0);
        $lId = (int)($ri['lot_id'] ?? $ri['source_lot_id'] ?? 0);
        $qty = round((float)($ri['quantity'] ?? 0), 2);

        if ($pId > 0 && $lId > 0 && $qty > 0) {
            $items[] = [
                'product_id' => $pId,
                'lot_id'     => $lId,
                'quantity'   => $qty
            ];
        }
    }

    $headerData['items'] = $items;

    if ($isEdit && $transferId > 0) {
        $res = AdvancedInventory::updateTransfer($transferId, $headerData, $userId);
    } else {
        $res = AdvancedInventory::createTransfer($headerData, $userId);
    }

    if ($isJson) {
        header('Content-Type: application/json');
        echo json_encode($res);
        exit();
    }

    if ($res['success']) {
        header("Location: stock_transfers?created=1");
        exit();
    } else {
        $errorMsg = $res['message'];
    }
}

// Destination Branches (Excluding current branch)
$branches = Database::fetchAll("
    SELECT id, name, code, address, phone 
    FROM branches 
    WHERE status = 'active' AND id != :b 
    ORDER BY name ASC
", [':b' => $activeBranchId]);

// Load all active lots at the current source branch with product details
$lotsRaw = Database::fetchAll("
    SELECT p.id AS product_id, p.name AS product_name, p.barcode, p.sku, 
           c.name AS category_name,
           pl.id AS lot_id, pl.lot_number, pl.cost, pl.selling_price, 
           pl.remaining_quantity, pl.expiry_date
    FROM products p
    JOIN product_lots pl ON p.id = pl.product_id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE pl.branch_id = :b 
      AND pl.status = 'active' 
      AND pl.remaining_quantity > 0 
      AND p.deleted_at IS NULL
    ORDER BY p.name ASC, pl.expiry_date ASC, pl.id ASC
", [':b' => $activeBranchId]);

// Organize into product-level structure with nested lot arrays for catalog search & fast selection
$catalogMap = [];
foreach ($lotsRaw as $row) {
    $pid = (int)$row['product_id'];
    if (!isset($catalogMap[$pid])) {
        $catalogMap[$pid] = [
            'product_id'    => $pid,
            'product_name'  => $row['product_name'],
            'barcode'       => $row['barcode'] ?? '',
            'sku'           => $row['sku'] ?? '',
            'category_name' => $row['category_name'] ?? 'General',
            'total_stock'   => 0.00,
            'lots'          => []
        ];
    }

    $qty = (float)$row['remaining_quantity'];
    $catalogMap[$pid]['total_stock'] += $qty;
    $catalogMap[$pid]['lots'][] = [
        'lot_id'             => (int)$row['lot_id'],
        'lot_number'         => $row['lot_number'],
        'cost'               => (float)$row['cost'],
        'selling_price'      => (float)$row['selling_price'],
        'remaining_quantity' => $qty,
        'expiry_date'        => $row['expiry_date'] ?? null
    ];
}
$catalogProducts = array_values($catalogMap);

$nextTransferNumber = AdvancedInventory::generateNumber('TRF', 'stock_transfers');

View::render('inventory/transfer_form', [
    'currentBranch'      => $currentBranch,
    'branches'           => $branches,
    'catalogProducts'    => $catalogProducts,
    'nextTransferNumber' => $nextTransferNumber,
    'transferData'       => $transferData,
    'existingItems'      => $existingItems,
    'errorMsg'           => $errorMsg
], ($editId > 0 ? 'Edit Stock Transfer' : 'Create Stock Transfer') . ' - Curtiss POS');
