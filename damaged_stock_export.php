<?php
/**
 * Curtiss POS - Damaged & Wastage Stock CSV Export Handler
 * Exports damaged stock records matching active filters with UTF-8 BOM and Excel string escaping.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Database.php';

Auth::requireAuth();

if (!Auth::hasPermission('manage_damaged_stock') && !Auth::hasPermission('inventory.view') && !Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)) {
    http_response_code(403);
    die('Forbidden: Insufficient permissions.');
}

$user = Auth::user();
$activeBranchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? $user['active_branch_id'] ?? 1);
$branchId = (int)($_GET['branch_id'] ?? $activeBranchId);

$statusFilter = trim($_GET['status'] ?? '');
$search = trim($_GET['search'] ?? '');

$where = ["ds.branch_id = :b"];
$params = [':b' => $branchId];

if (!empty($statusFilter)) {
    $where[] = "ds.status = :st";
    $params[':st'] = $statusFilter;
}

if (!empty($search)) {
    $searchTerm = '%' . $search . '%';
    $cleanNum = preg_replace('/[^0-9.]/', '', $search);

    $searchConditions = [
        "CONCAT('DMG-', ds.id) LIKE :s1",
        "p.name LIKE :s2",
        "p.barcode LIKE :s3",
        "COALESCE(p.sku, '') LIKE :s4",
        "COALESCE(pl.lot_number, '') LIKE :s5",
        "ds.stock_type LIKE :s6",
        "COALESCE(ds.reason, '') LIKE :s7",
        "ds.status LIKE :s8",
        "u.username LIKE :s9",
        "COALESCE(sr.return_no, '') LIKE :s10",
        "CAST(ds.created_at AS CHAR) LIKE :s11"
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
    $params[':s11'] = $searchTerm;

    if ($cleanNum !== '' && is_numeric($cleanNum)) {
        $searchConditions[] = "CAST(ds.quantity AS CHAR) LIKE :s_num1";
        $searchConditions[] = "CAST(ds.cost AS CHAR) LIKE :s_num2";
        $numTerm = '%' . $cleanNum . '%';
        $params[':s_num1'] = $numTerm;
        $params[':s_num2'] = $numTerm;
    }

    $where[] = "(" . implode(" OR ", $searchConditions) . ")";
}

$whereSql = "WHERE " . implode(" AND ", $where);

$rows = Database::fetchAll("
    SELECT ds.*, p.name AS product_name, p.barcode, p.sku, pl.lot_number, pl.cost AS lot_cost,
           u.username AS logger_name, u2.username AS disposer_name, sr.return_no
    FROM damaged_stock ds
    JOIN products p ON ds.product_id = p.id
    LEFT JOIN product_lots pl ON ds.lot_id = pl.id
    JOIN users u ON ds.logged_by = u.id
    LEFT JOIN users u2 ON ds.disposed_by = u2.id
    LEFT JOIN sales_returns sr ON ds.sales_return_id = sr.id
    {$whereSql}
    ORDER BY ds.id DESC
", $params);

$filename = 'damaged_stock_' . date('Y-m-d_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');

// UTF-8 BOM for Microsoft Excel
fwrite($out, "\xEF\xBB\xBF");

// Header row
fputcsv($out, [
    'ID #',
    'Date & Time',
    'Product Name',
    'Barcode',
    'Lot #',
    'Stock Type',
    'Quantity',
    'Unit Cost (LKR)',
    'Total Loss (LKR)',
    'Source / Return #',
    'Reason',
    'Status',
    'Logged By',
    'Disposed At',
    'Disposed By',
    'Disposal Notes'
]);

foreach ($rows as $r) {
    $qty = (float)$r['quantity'];
    $cost = (float)($r['cost'] ?? $r['lot_cost'] ?? 0);
    $totalVal = round($qty * $cost, 2);
    $sourceStr = !empty($r['return_no']) ? ('Return #' . $r['return_no']) : 'Internal Move';

    fputcsv($out, [
        '="DMG-' . $r['id'] . '"',
        '="' . ($r['created_at'] ?? '') . '"',
        $r['product_name'] ?? '',
        '="' . ($r['barcode'] ?? '') . '"',
        '="' . ($r['lot_number'] ?? '') . '"',
        ucfirst($r['stock_type'] ?? 'damaged'),
        number_format($qty, 2, '.', ''),
        number_format($cost, 2, '.', ''),
        number_format($totalVal, 2, '.', ''),
        $sourceStr,
        $r['reason'] ?? '',
        ucfirst($r['status'] ?? 'active'),
        $r['logger_name'] ?? '',
        '="' . ($r['disposed_at'] ?? '') . '"',
        $r['disposer_name'] ?? '',
        $r['disposal_notes'] ?? ''
    ]);
}

fclose($out);
exit();
