<?php
/**
 * Curtiss POS - Supplier CSV Export Handler
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Supplier.php';

Auth::requireAuth();

if (!Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)) {
    http_response_code(403);
    echo "Unauthorized";
    exit();
}

$search = $_GET['search'] ?? null;
$activeOnly = isset($_GET['active_only']) && $_GET['active_only'] === '1';

$suppliers = Supplier::all($activeOnly, $search);

$filename = 'suppliers_export_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// Header row
fputcsv($output, ['Supplier Code', 'Supplier Name', 'Phone', 'WhatsApp', 'Address', 'Status']);

foreach ($suppliers as $s) {
    fputcsv($output, [
        $s['supplier_code'] ?? '',
        $s['name'] ?? '',
        $s['phone'] ?? '',
        $s['whatsapp'] ?? '',
        $s['address'] ?? '',
        $s['status'] ?? 'active'
    ]);
}

fclose($output);
exit();
