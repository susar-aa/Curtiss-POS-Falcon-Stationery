<?php
/**
 * Curtiss POS - Stock Adjustments Controller (Redesign Theme & Design Rules Enforced)
 * Features Active Branch Session Resolution, Universal Search, and Server-Side Pagination.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/View.php';

Auth::requireAuth();

if (!Auth::hasPermission('manage_stock_adjustments') && !Auth::hasPermission('inventory.view') && !Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)) {
    View::renderBlank('errors/403');
    exit();
}

$user = Auth::user();
$userId = (int)$user['id'];
$activeBranchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? $user['active_branch_id'] ?? 1);
$branchId = (int)($_GET['branch_id'] ?? $activeBranchId);
$activeBranchName = $user['active_branch_name'] ?? ('Branch #' . $branchId);

// If superadmin switched branch, resolve branch name accurately
if ($branchId !== (int)($user['active_branch_id'] ?? 1)) {
    $branchRow = Database::fetchOne("SELECT name FROM branches WHERE id = :id", [':id' => $branchId]);
    if ($branchRow) {
        $activeBranchName = $branchRow['name'];
    }
}

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
        $searchConditions[] = "CONCAT('LKR ', FORMAT(sa.cost, 2)) LIKE :s_num3";
        $searchConditions[] = "CONCAT('Rs. ', FORMAT(sa.cost, 2)) LIKE :s_num4";
        $searchConditions[] = "CONCAT(FORMAT(sa.cost, 2)) LIKE :s_num5";
        $searchConditions[] = "CAST(ROUND(sa.quantity * sa.cost, 2) AS CHAR) LIKE :s_num6";
        $searchConditions[] = "CONCAT('LKR ', FORMAT(sa.quantity * sa.cost, 2)) LIKE :s_num7";

        $numTerm = '%' . $cleanNum . '%';
        $params[':s_num1'] = $numTerm;
        $params[':s_num2'] = $numTerm;
        $params[':s_num3'] = $searchTerm;
        $params[':s_num4'] = $searchTerm;
        $params[':s_num5'] = $searchTerm;
        $params[':s_num6'] = $numTerm;
        $params[':s_num7'] = $searchTerm;
    }

    $where[] = "(" . implode(" OR ", $searchConditions) . ")";
}

$whereSql = "WHERE " . implode(" AND ", $where);

// Count matching records for pagination
$countRow = Database::fetchOne("
    SELECT COUNT(*) AS total
    FROM stock_adjustments sa
    JOIN products p ON sa.product_id = p.id
    JOIN product_lots pl ON sa.lot_id = pl.id
    JOIN users u ON sa.created_by = u.id
    {$whereSql}
", $params);

$totalItems = (int)($countRow['total'] ?? 0);

// Pagination limits: 25, 50, 75, 100, 500 (Default: 25)
$rawLimit = (int)($_GET['limit'] ?? 25);
$limit = in_array($rawLimit, [25, 50, 75, 100, 500], true) ? $rawLimit : 25;
$page = max(1, (int)($_GET['page'] ?? 1));
$totalPages = max(1, (int)ceil($totalItems / $limit));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = (int)(($page - 1) * $limit);

$adjustments = Database::fetchAll("
    SELECT sa.*, p.name AS product_name, p.barcode, p.sku, pl.lot_number, pl.selling_price AS lot_selling_price, u.username AS creator_name
    FROM stock_adjustments sa
    JOIN products p ON sa.product_id = p.id
    JOIN product_lots pl ON sa.lot_id = pl.id
    JOIN users u ON sa.created_by = u.id
    {$whereSql}
    ORDER BY sa.id DESC 
    LIMIT {$limit} OFFSET {$offset}
", $params);

// Fetch active product lots for the modal dropdown
$productsWithLots = Database::fetchAll("
    SELECT p.id AS product_id, p.name AS product_name, p.barcode, p.sku, 
           pl.id AS lot_id, pl.lot_number, pl.cost, pl.selling_price, pl.remaining_quantity
    FROM products p
    JOIN product_lots pl ON p.id = pl.product_id
    WHERE pl.branch_id = :b AND pl.status = 'active' AND p.deleted_at IS NULL
    ORDER BY p.name ASC, pl.id ASC
", [':b' => $branchId]);

$uniqueProductsMap = [];
foreach ($productsWithLots as $row) {
    $pid = (int)$row['product_id'];
    if (!isset($uniqueProductsMap[$pid])) {
        $uniqueProductsMap[$pid] = [
            'id'      => $pid,
            'name'    => $row['product_name'],
            'barcode' => $row['barcode'] ?? '',
            'sku'     => $row['sku'] ?? ''
        ];
    }
}
$uniqueProducts = array_values($uniqueProductsMap);

View::render('inventory/adjustments', [
    'adjustments'      => $adjustments,
    'totalItems'       => $totalItems,
    'page'             => $page,
    'limit'            => $limit,
    'totalPages'       => $totalPages,
    'productsWithLots' => $productsWithLots,
    'uniqueProducts'   => $uniqueProducts,
    'branchId'         => $branchId,
    'typeFilter'       => $typeFilter,
    'search'           => $search,
    'activeBranchName' => $activeBranchName
], 'Stock Adjustments - Curtiss POS');
