<?php
/**
 * Curtiss POS - Edit Customer Controller (Stage 5)
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Customer.php';

Auth::requireAuth();

if (!Auth::hasPermission('customers.manage')) {
    View::renderBlank('errors/403');
    exit();
}

$id = (int)($_GET['id'] ?? 0);
$customer = Customer::find($id);

if (!$customer) {
    $_SESSION['flash_error'] = 'Customer not found.';
    header("Location: customers");
    exit();
}

$errorMsg = null;
$formData = $customer;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hasCreditLimit = !empty($_POST['has_credit_limit']);
    $creditLimit = ($hasCreditLimit && isset($_POST['credit_limit']) && $_POST['credit_limit'] !== '')
        ? (float)$_POST['credit_limit']
        : null;

    $formData = array_merge($customer, [
        'name'                      => $_POST['name'] ?? '',
        'phone'                     => $_POST['phone'] ?? '',
        'whatsapp'                  => $_POST['whatsapp'] ?? '',
        'address'                   => $_POST['address'] ?? '',
        'card_barcode'              => $_POST['card_barcode'] ?? '',
        'credit_limit'              => $creditLimit,
        'has_credit_limit'          => $hasCreditLimit ? 1 : 0,
        'status'                    => $_POST['status'] ?? 'active',
        'is_loyalty'                => isset($_POST['is_loyalty']) ? 1 : 0,
        'loyalty_discount_override' => $_POST['loyalty_discount_override'] ?? ''
    ]);

    $res = Customer::update($id, $formData);

    if ($res['success']) {
        $_SESSION['flash_success'] = $res['message'];
        header("Location: customers");
        exit();
    } else {
        $errorMsg = $res['message'];
    }
}

View::render('customers/customer_form', [
    'mode'      => 'edit',
    'id'        => $id,
    'formData'  => $formData,
    'errorMsg'  => $errorMsg
], "Edit Customer: {$customer['name']} - Curtiss POS");
