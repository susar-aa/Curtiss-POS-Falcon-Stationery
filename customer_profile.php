<?php
/**
 * Curtiss POS - Customer Profile & Credit Foundation Controller (Stage 5)
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Customer.php';

Auth::requireAuth();

if (!Auth::hasPermission('customers.view')) {
    View::renderBlank('errors/403');
    exit();
}

$id = (int)($_GET['id'] ?? 0);
$customer = Customer::find($id);

if (!$customer) {
    $_SESSION['flash_error'] = 'Customer record not found.';
    header("Location: customers");
    exit();
}

$creditData = Customer::getCreditHistory($id);
$purchaseData = Customer::getPurchaseHistory($id);

View::render('customers/customer_profile', [
    'customer'     => $customer,
    'creditData'   => $creditData,
    'purchaseData' => $purchaseData
], "Customer Profile: {$customer['name']} - Curtiss POS");
