<?php
/**
 * Curtiss POS - Stock Transfers CSV Exporter
 * Preserves Transfer Number leading zeros for seamless Excel spreadsheet viewing.
 * Respects active branch isolation and filters (status, search).
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Database.php';

Auth::requireAuth();

if (!Auth::hasPermission('view_stock_transfers') && !Auth::hasPermission('inventory.view')) {
    View::renderBlank('errors/403');
    exit();
}

$user = Auth::user();
$activeBranchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? $user['active_branch_id'] ?? 1);
$branchId = (int)($_GET['branch_id'] ?? $activeBranchId);

$statusFilter = trim($_GET['status'] ?? '');
$search = trim($_GET['search'] ?? '');

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

$transfers = Database::fetchAll("
    SELECT st.*, fb.name AS from_branch_name, tb.name AS to_branch_name, u.username AS creator_name,
           u_sent.username AS sent_by_name, u_rec.username AS received_by_name, u_canc.username AS cancelled_by_name
    FROM stock_transfers st
    JOIN branches fb ON st.from_branch_id = fb.id
    JOIN branches tb ON st.to_branch_id = tb.id
    JOIN users u ON st.created_by = u.id
    LEFT JOIN users u_sent ON st.sent_by = u_sent.id
    LEFT JOIN users u_rec ON st.received_by = u_rec.id
    LEFT JOIN users u_canc ON st.cancelled_by = u_canc.id
    {$whereSql}
    ORDER BY st.id DESC
", $params);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=curtiss_stock_transfers_branch_' . $branchId . '_' . date('Y-m-d_H-i-s') . '.csv');

// Print UTF-8 BOM so Excel opens with proper encoding
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

fputcsv($output, [
    'Transfer #',
    'Date',
    'From Branch',
    'To Branch',
    'Status',
    'Created By',
    'Sent By',
    'Received By',
    'Cancelled By',
    'Notes'
]);

foreach ($transfers as $trf) {
    $trfNum = (string)$trf['transfer_number'];

    fputcsv($output, [
        '="' . $trfNum . '"',
        $trf['transfer_date'],
        $trf['from_branch_name'],
        $trf['to_branch_name'],
        ucfirst($trf['status']),
        $trf['creator_name'],
        $trf['sent_by_name'] ?? '-',
        $trf['received_by_name'] ?? '-',
        $trf['cancelled_by_name'] ?? '-',
        $trf['notes'] ?? ''
    ]);
}

fclose($output);
exit();
