<?php
/**
 * Curtiss POS - Physical Stock Audits CSV Export Handler
 * Exports completed and posted audit records with UTF-8 BOM and Excel string escaping.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Database.php';

Auth::requireAuth();

if (!Auth::hasPermission('manage_stock_audits') && !Auth::hasPermission('inventory.view') && !Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)) {
    http_response_code(403);
    exit('Unauthorized access.');
}

$user = Auth::user();
$branchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? $user['active_branch_id'] ?? 1);
if (!empty($_GET['branch_id'])) {
    $branchId = (int)$_GET['branch_id'];
}

$statusFilter = trim($_GET['status'] ?? '');
$search       = trim($_GET['search'] ?? '');

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

$audits = Database::fetchAll("
    SELECT sa.*, u.username AS creator_name, pu.username AS poster_name, b.name AS branch_name
    FROM stock_audits sa
    JOIN users u ON sa.created_by = u.id
    LEFT JOIN users pu ON sa.posted_by = pu.id
    LEFT JOIN branches b ON sa.branch_id = b.id
    WHERE {$whereSql}
    ORDER BY sa.id DESC
", $params);

$filename = 'stock_audits_export_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');
// Output UTF-8 BOM
fwrite($out, "\xEF\xBB\xBF");

// Header row
fputcsv($out, [
    'Audit #',
    'Branch',
    'Audit Date',
    'Status',
    'Created By',
    'Posted By',
    'Posted At',
    'Notes'
]);

foreach ($audits as $row) {
    fputcsv($out, [
        '="' . str_replace('"', '""', $row['audit_number']) . '"',
        $row['branch_name'] ?? 'Branch #' . $row['branch_id'],
        $row['audit_date'] ?? '',
        strtoupper($row['status'] ?? ''),
        $row['creator_name'] ?? '',
        $row['poster_name'] ?? 'System',
        $row['posted_at'] ?? '',
        $row['notes'] ?? ''
    ]);
}

fclose($out);
exit;
