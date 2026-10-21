<?php
/**
 * Curtiss POS - Stock Transfers Controller (Redesign Theme & Design Rules Enforced)
 * Features Active Branch Session Resolution, Universal Search, and Server-Side Pagination.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/View.php';

Auth::requireAuth();

if (!Auth::hasPermission('view_stock_transfers') && !Auth::hasPermission('inventory.view')) {
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

$branches = Database::fetchAll("SELECT id, name FROM branches WHERE status = 'active' ORDER BY name ASC");

$where = ["(st.from_branch_id = :from_b OR st.to_branch_id = :to_b)"];
$params = [':from_b' => $branchId, ':to_b' => $branchId];

if (!empty($statusFilter)) {
    $where[] = "st.status = :status";
    $params[':status'] = $statusFilter;
}

if (!empty($search)) {
    $searchTerm = '%' . $search . '%';
    $where[] = "(
        st.transfer_number LIKE :s1 OR 
        fb.name LIKE :s2 OR 
        tb.name LIKE :s3 OR 
        u.username LIKE :s4 OR 
        COALESCE(st.notes, '') LIKE :s5 OR 
        st.status LIKE :s6 OR 
        CAST(st.transfer_date AS CHAR) LIKE :s7
    )";
    for ($i = 1; $i <= 7; $i++) {
        $params[":s{$i}"] = $searchTerm;
    }
}

$whereSql = "WHERE " . implode(" AND ", $where);

// Count total matching records for pagination
$countRow = Database::fetchOne("
    SELECT COUNT(*) AS total
    FROM stock_transfers st
    JOIN branches fb ON st.from_branch_id = fb.id
    JOIN branches tb ON st.to_branch_id = tb.id
    JOIN users u ON st.created_by = u.id
    {$whereSql}
", $params);

$totalItems = (int)($countRow['total'] ?? 0);

// Pagination limits: 25, 50, 75, 100, 500
$rawLimit = (int)($_GET['limit'] ?? 25);
$limit = in_array($rawLimit, [25, 50, 75, 100, 500], true) ? $rawLimit : 25;
$page = max(1, (int)($_GET['page'] ?? 1));
$totalPages = max(1, (int)ceil($totalItems / $limit));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = (int)(($page - 1) * $limit);

$transfers = Database::fetchAll("
    SELECT st.*, fb.name AS from_branch_name, tb.name AS to_branch_name, u.username AS creator_name
    FROM stock_transfers st
    JOIN branches fb ON st.from_branch_id = fb.id
    JOIN branches tb ON st.to_branch_id = tb.id
    JOIN users u ON st.created_by = u.id
    {$whereSql}
    ORDER BY st.id DESC 
    LIMIT {$limit} OFFSET {$offset}
", $params);

View::render('inventory/transfers', [
    'transfers'        => $transfers,
    'totalItems'       => $totalItems,
    'page'             => $page,
    'limit'            => $limit,
    'totalPages'       => $totalPages,
    'branches'         => $branches,
    'branchId'         => $branchId,
    'statusFilter'     => $statusFilter,
    'search'           => $search,
    'activeBranchName' => $activeBranchName
], 'Branch Stock Transfers - Curtiss POS');
