<?php
/**
 * Curtiss POS - Physical Stock Audit Controller (Redesign Theme & Design Rules Enforced)
 * Queries active & completed audit sessions and delegates to inventory/audits view.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/View.php';

Auth::requireAuth();

if (!Auth::hasPermission('manage_stock_audits') && !Auth::hasPermission('inventory.view') && !Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)) {
    View::renderBlank('errors/403');
    exit();
}

$user = Auth::user();
$userId = (int)$user['id'];
$branchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? $user['active_branch_id'] ?? 1);
if (!empty($_GET['branch_id'])) {
    $branchId = (int)$_GET['branch_id'];
}

$statusFilter = trim($_GET['status'] ?? '');
$search       = trim($_GET['search'] ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));
$limit        = (int)($_GET['limit'] ?? 25);
if (!in_array($limit, [25, 50, 75, 100, 500])) {
    $limit = 25;
}

// 1. Fetch active audit session if any
$activeAudit = Database::fetchOne("
    SELECT * FROM stock_audits
    WHERE branch_id = :b AND status IN ('draft', 'counting', 'review')
    ORDER BY id DESC LIMIT 1
", [':b' => $branchId]);

$auditItems = [];
if ($activeAudit) {
    $auditItems = Database::fetchAll("
        SELECT sai.*, p.name AS product_name, p.sku, p.barcode, pl.lot_number, pl.cost AS lot_cost
        FROM stock_audit_items sai
        JOIN products p ON sai.product_id = p.id
        JOIN product_lots pl ON sai.lot_id = pl.id
        WHERE sai.audit_id = :aud_id
        ORDER BY p.name ASC, pl.id ASC
    ", [':aud_id' => $activeAudit['id']]);
}

// 2. Build Query & Pagination for Completed / Posted Audit History
$where = ["sa.branch_id = :branch_id"];
$params = [':branch_id' => $branchId];

if ($statusFilter !== '') {
    $where[] = "sa.status = :status";
    $params[':status'] = $statusFilter;
} else {
    $where[] = "sa.status = 'posted'";
}

if ($search !== '') {
    $searchTerm = '%' . $search . '%';
    $where[] = "(
        sa.audit_number LIKE :s1 OR
        CAST(sa.audit_date AS CHAR) LIKE :s2 OR
        sa.notes LIKE :s3 OR
        u.username LIKE :s4 OR
        pu.username LIKE :s5 OR
        CAST(sa.posted_at AS CHAR) LIKE :s6
    )";
    for ($i = 1; $i <= 6; $i++) {
        $params[":s{$i}"] = $searchTerm;
    }
}

$whereSql = implode(' AND ', $where);

$countRow = Database::fetchOne("
    SELECT COUNT(*) AS total
    FROM stock_audits sa
    JOIN users u ON sa.created_by = u.id
    LEFT JOIN users pu ON sa.posted_by = pu.id
    WHERE {$whereSql}
", $params);

$totalRecords = (int)($countRow['total'] ?? 0);
$totalPages = max(1, (int)ceil($totalRecords / $limit));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $limit;

$completedAudits = Database::fetchAll("
    SELECT sa.*, u.username AS creator_name, pu.username AS poster_name
    FROM stock_audits sa
    JOIN users u ON sa.created_by = u.id
    LEFT JOIN users pu ON sa.posted_by = pu.id
    WHERE {$whereSql}
    ORDER BY sa.id DESC
    LIMIT {$limit} OFFSET {$offset}
", $params);

// Fetch branch name
$branchInfo = Database::fetchOne("SELECT name FROM branches WHERE id = :id", [':id' => $branchId]);
$activeBranchName = $branchInfo['name'] ?? ($user['active_branch_name'] ?? 'Current Branch');

View::render('inventory/audits', [
    'activeAudit'      => $activeAudit,
    'auditItems'       => $auditItems,
    'completedAudits'  => $completedAudits,
    'branchId'         => $branchId,
    'statusFilter'     => $statusFilter,
    'search'           => $search,
    'page'             => $page,
    'limit'            => $limit,
    'totalRecords'     => $totalRecords,
    'totalPages'       => $totalPages,
    'activeBranchName' => $activeBranchName
], 'Physical Stock Audit - Curtiss POS');
