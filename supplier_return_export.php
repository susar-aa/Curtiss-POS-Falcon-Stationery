<?php
/**
 * Curtiss POS - Supplier Return CSV Export Handler
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/SupplierReturnEngine.php';

Auth::requireAuth();

if (!Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER) && !Auth::hasPermission('view_supplier_returns')) {
    http_response_code(403);
    echo "Unauthorized";
    exit();
}

$branchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? 1);

$filters = [
    'branch_id' => $branchId
];

if (!empty($_GET['search'])) {
    $filters['search'] = trim($_GET['search']);
}
if (!empty($_GET['supplier_id'])) {
    $filters['supplier_id'] = (int)$_GET['supplier_id'];
}

$history = SupplierReturnEngine::getReturnHistory($filters);

$filename = 'supplier_returns_export_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// Header row
fputcsv($output, [
    'Return Number',
    'Return Date',
    'Supplier Name',
    'GRN Reference',
    'Total Return (LKR)',
    'AP Offset (LKR)',
    'Supplier Credit (LKR)',
    'Logged By',
    'Notes'
]);

foreach ($history as $r) {
    fputcsv($output, [
        $r['return_number'] ?? '',
        $r['return_date'] ?? '',
        $r['supplier_name'] ?? '',
        $r['grn_number'] ?? 'Direct Return',
        number_format((float)($r['total_return_amount'] ?? 0), 2, '.', ''),
        number_format((float)($r['applied_to_payable'] ?? 0), 2, '.', ''),
        number_format((float)($r['credit_amount'] ?? 0), 2, '.', ''),
        $r['created_by_name'] ?? 'System',
        $r['notes'] ?? ''
    ]);
}

fclose($output);
exit();
