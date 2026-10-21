<?php
/**
 * Curtiss POS - Stock Adjustments CSV Export Handler
 * Exports stock adjustments matching active filters with UTF-8 BOM and Excel string escaping.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Database.php';

Auth::requireAuth();

if (!Auth::hasPermission('manage_stock_adjustments') && !Auth::hasPermission('inventory.view') && !Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)) {
    http_response_code(403);
    die('Forbidden: Insufficient permissions.');
}

$user = Auth::user();
$activeBranchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? $user['active_branch_id'] ?? 1);
$branchId = (int)($_GET['branch_id'] ?? $activeBranchId);

$typeFilter = trim($_GET['adjustment_type'] ?? '');
$search = trim($_GET['search'] ?? '');

$where = ["sa.branch_id = :b"];
$params = [':b' => $branchId];

if (!empty($typeFilter)) {
    $where[] = "sa.adjustment_type = :atype";
    $params[':atype'] = $typeFilter;
}

if (!empty($search)) {
    $searchTerm = '%' . $search . '%';
    $cleanNum = preg_replace('/[^0-9.]/', '', $search);

    $searchConditions = [
        "sa.adjustment_number LIKE :s1",
        "p.name LIKE :s2",
        "p.barcode LIKE :s3",
        "COALESCE(p.sku, '') LIKE :s4",
        "pl.lot_number LIKE :s5",
        "sa.adjustment_type LIKE :s6",
        "sa.reason LIKE :s7",
        "COALESCE(sa.notes, '') LIKE :s8",
        "u.username LIKE :s9",
        "CAST(sa.created_at AS CHAR) LIKE :s10"
    ];

    $params[':s1']  = $searchTerm;
    $params[':s2']  = $searchTerm;
    $params[':s3']  = $searchTerm;
    $params[':s4']  = $searchTerm;
    $params[':s5']  = $searchTerm;
    $params[':s6']  = $searchTerm;
    $params[':s7']  = $searchTerm;
    $params[':s8']  = $searchTerm;
    $params[':s9']  = $searchTerm;
    $params[':s10'] = $searchTerm;

    if ($cleanNum !== '' && is_numeric($cleanNum)) {
        $searchConditions[] = "CAST(sa.quantity AS CHAR) LIKE :s_num1";
        $searchConditions[] = "CAST(sa.cost AS CHAR) LIKE :s_num2";
        $numTerm = '%' . $cleanNum . '%';
        $params[':s_num1'] = $numTerm;
        $params[':s_num2'] = $numTerm;
    }

    $where[] = "(" . implode(" OR ", $searchConditions) . ")";
}

$whereSql = "WHERE " . implode(" AND ", $where);

$rows = Database::fetchAll("
    SELECT sa.*, p.name AS product_name, p.barcode, p.sku, pl.lot_number, u.username AS creator_name
    FROM stock_adjustments sa
    JOIN products p ON sa.product_id = p.id
    JOIN product_lots pl ON sa.lot_id = pl.id
    JOIN users u ON sa.created_by = u.id
    {$whereSql}
    ORDER BY sa.id DESC
", $params);

$filename = 'stock_adjustments_' . date('Y-m-d_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');

// UTF-8 BOM for Microsoft Excel
fwrite($out, "\xEF\xBB\xBF");

// Header row
fputcsv($out, [
    'Adjustment #',
    'Date & Time',
    'Product Name',
    'Barcode',
    'Lot #',
    'Adjustment Type',
    'Quantity',
    'Unit Cost (LKR)',
    'Total Cost Value (LKR)',
    'Reason',
    'Notes',
    'Posted By'
]);

foreach ($rows as $r) {
    $type = strtolower($r['adjustment_type']);
    $isIncrease = ($type === 'increase');
    $qty = (float)$r['quantity'];
    $signedQty = ($isIncrease ? '+' : '-') . number_format($qty, 2, '.', '');
    $cost = (float)$r['cost'];
    $totalVal = round($qty * $cost, 2);

    fputcsv($out, [
        '="' . ($r['adjustment_number'] ?? '') . '"',
        '="' . ($r['created_at'] ?? '') . '"',
        $r['product_name'] ?? '',
        '="' . ($r['barcode'] ?? '') . '"',
        '="' . ($r['lot_number'] ?? '') . '"',
        $isIncrease ? 'Increase (+)' : 'Decrease (-)',
        $signedQty,
        number_format($cost, 2, '.', ''),
        number_format($totalVal, 2, '.', ''),
        $r['reason'] ?? '',
        $r['notes'] ?? '',
        $r['creator_name'] ?? ''
    ]);
}

fclose($out);
exit();
