<?php
/**
 * Curtiss POS - Customer Master CSV Export (Section 5)
 * Exports customer master list to CSV file download.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Customer.php';

Auth::requireLogin();

if (!Auth::hasPermission('view_customers') && !Auth::hasPermission('export_customers')) {
    http_response_code(403);
    die('Unauthorized: You do not have permission to export customers.');
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="customers_export_' . date('Y-m-d_His') . '.csv"');

$output = fopen('php://output', 'w');

// Header row
fputcsv($output, [
    'Customer Code',
    'Name',
    'Phone',
    'WhatsApp',
    'Address',
    'Card Barcode',
    'Credit Limit',
    'Status',
    'Current Outstanding'
]);

$customers = Database::fetchAll("
    SELECT * FROM customers 
    WHERE deleted_at IS NULL 
    ORDER BY is_walkin DESC, id ASC
");

foreach ($customers as $c) {
    fputcsv($output, [
        $c['customer_code'],
        $c['name'],
        $c['phone'] ?? '',
        $c['whatsapp'] ?? '',
        $c['address'] ?? '',
        $c['card_barcode'] ?? '',
        sprintf('%.2f', (float)$c['credit_limit']),
        $c['status'],
        sprintf('%.2f', (float)$c['credit_balance'])
    ]);
}

fclose($output);
exit;
