<?php
/**
 * Curtiss POS - Stock Movement Ledger CSV Exporter
 * Preserves SKU, Barcode, and Lot Number leading zeros for seamless Excel spreadsheet viewing.
 * Respects active branch isolation and filters (movement_type, search, product_id).
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Inventory.php';
require_once __DIR__ . '/core/MaskingHelper.php';

Auth::requireAuth();

if (!Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER, ROLE_ACCOUNTANT, ROLE_CASHIER) || Auth::isSpecialUser()) {
    View::renderBlank('errors/403');
    exit();
}

$user = Auth::user();
$activeBranchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? $user['active_branch_id'] ?? 1);
$branchId = (int)($_GET['branch_id'] ?? $activeBranchId);

$filters = [
    'search'        => trim($_GET['search'] ?? ''),
    'product_id'    => (int)($_GET['product_id'] ?? 0),
    'movement_type' => trim($_GET['movement_type'] ?? '')
];

$res = Inventory::getLedgerList($branchId, $filters, 1, 10000);
$items = $res['items'];

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=curtiss_stock_ledger_branch_' . $branchId . '_' . date('Y-m-d_H-i-s') . '.csv');

// Print UTF-8 BOM so Excel opens with proper encoding
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

fputcsv($output, [
    'Timestamp',
    'Movement Type',
    'Barcode',
    'Product Name',
    'Lot Number',
    'Quantity Changed',
    'Balance After',
    'Unit Cost (LKR)',
    'Reference No',
    'User',
    'Notes'
]);

foreach ($items as $row) {
    $barcode = (string)$row['barcode'];
    $lotNumber = (string)($row['lot_number'] ?? '');
    $refNo = (string)($row['reference_no'] ?? '');

    $mvtLabel = str_replace('_', ' ', strtoupper($row['movement_type']));
    $qty = (float)$row['quantity'];
    $bal = (float)$row['balance_after'];
    $cost = isset($row['unit_cost']) ? (float)$row['unit_cost'] : 0.0;

    fputcsv($output, [
        $row['created_at'],
        $mvtLabel,
        '="' . $barcode . '"',
        $row['product_name'],
        $lotNumber !== '' ? '="' . $lotNumber . '"' : '-',
        number_format($qty, 2, '.', ''),
        number_format($bal, 2, '.', ''),
        $cost > 0 ? number_format($cost, 2, '.', '') : '0.00',
        $refNo !== '' ? '="' . $refNo . '"' : '-',
        $row['username'] ?? 'System',
        $row['notes'] ?? ''
    ]);
}

fclose($output);
exit();
