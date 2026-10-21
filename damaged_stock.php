<?php
/**
 * Curtiss POS - Damaged & Wastage Stock Controller (Redesign Theme & Design Rules Enforced)
 * Features Active Branch Session Resolution, Universal Search, and Server-Side Pagination.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/View.php';

Auth::requireAuth();

if (!Auth::hasPermission('manage_damaged_stock') && !Auth::hasPermission('inventory.view') && !Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)) {
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
        $searchConditions[] = "CONCAT('LKR ', FORMAT(ds.cost, 2)) LIKE :s_num3";
        $searchConditions[] = "CONCAT('Rs. ', FORMAT(ds.cost, 2)) LIKE :s_num4";
        $searchConditions[] = "CAST(ROUND(ds.quantity * ds.cost, 2) AS CHAR) LIKE :s_num5";
        $searchConditions[] = "CONCAT('LKR ', FORMAT(ds.quantity * ds.cost, 2)) LIKE :s_num6";

        $numTerm = '%' . $cleanNum . '%';
        $params[':s_num1'] = $numTerm;
        $params[':s_num2'] = $numTerm;
        $params[':s_num3'] = $searchTerm;
        $params[':s_num4'] = $searchTerm;
        $params[':s_num5'] = $numTerm;
        $params[':s_num6'] = $searchTerm;
    }

    $where[] = "(" . implode(" OR ", $searchConditions) . ")";
}

$whereSql = "WHERE " . implode(" AND ", $where);

// Count matching records for pagination
$countRow = Database::fetchOne("
    SELECT COUNT(*) AS total
    FROM damaged_stock ds
    JOIN products p ON ds.product_id = p.id
    LEFT JOIN product_lots pl ON ds.lot_id = pl.id
    JOIN users u ON ds.logged_by = u.id
    LEFT JOIN sales_returns sr ON ds.sales_return_id = sr.id
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

$damagedList = Database::fetchAll("
    SELECT ds.*, p.name AS product_name, p.barcode, p.sku, pl.lot_number, pl.cost AS lot_cost, pl.selling_price AS lot_selling_price, 
           u.username AS logger_name, u2.username AS disposer_name, sr.return_no
    FROM damaged_stock ds
    JOIN products p ON ds.product_id = p.id
    LEFT JOIN product_lots pl ON ds.lot_id = pl.id
    JOIN users u ON ds.logged_by = u.id
    LEFT JOIN users u2 ON ds.disposed_by = u2.id
    LEFT JOIN sales_returns sr ON ds.sales_return_id = sr.id
    {$whereSql}
    ORDER BY ds.id DESC 
    LIMIT {$limit} OFFSET {$offset}
", $params);

// Calculate total active damaged stock for branch summary
$activeDmgRow = Database::fetchOne("
    SELECT COALESCE(SUM(quantity), 0.00) AS total_active_damaged
    FROM damaged_stock
    WHERE branch_id = :b AND status = 'active'
", [':b' => $branchId]);
$totalActiveDamagedQty = (float)($activeDmgRow['total_active_damaged'] ?? 0.00);

// Fetch active sellable lots for the Move to Damaged modal dropdown
$productsWithLots = Database::fetchAll("
    SELECT p.id AS product_id, p.name AS product_name, p.barcode, p.sku, 
           pl.id AS lot_id, pl.lot_number, pl.cost, pl.selling_price, pl.remaining_quantity
    FROM products p
    JOIN product_lots pl ON p.id = pl.product_id
    WHERE pl.branch_id = :b AND pl.status = 'active' AND pl.remaining_quantity > 0 AND p.deleted_at IS NULL
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

View::render('inventory/damaged', [
    'damagedList'           => $damagedList,
    'totalItems'            => $totalItems,
    'page'                  => $page,
    'limit'                 => $limit,
    'totalPages'            => $totalPages,
    'productsWithLots'      => $productsWithLots,
    'uniqueProducts'        => $uniqueProducts,
    'totalActiveDamagedQty' => $totalActiveDamagedQty,
    'branchId'              => $branchId,
    'statusFilter'          => $statusFilter,
    'search'                => $search,
    'activeBranchName'      => $activeBranchName
], 'Damaged & Wastage Stock - Curtiss POS');
