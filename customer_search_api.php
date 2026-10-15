<?php
/**
 * Curtiss POS - Customer JSON Search API (Stage 5)
 * Endpoint for barcode scanners, phone/name lookup in future POS terminal
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Customer.php';

header('Content-Type: application/json');

if (!Auth::isLoggedIn() || !Auth::hasPermission('customers.view')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$q = trim($_GET['q'] ?? '');
$customers = Customer::search($q, 20);

echo json_encode([
    'success'   => true,
    'count'     => count($customers),
    'customers' => $customers
]);
