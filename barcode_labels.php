<?php
/**
 * Curtiss POS - Barcode Label Printing Controller
 * Dedicated thermal label printing page optimized for TSC TE244 (25mm x 40mm, 2 labels per row)
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/View.php';
require_once __DIR__ . '/core/Settings.php';
require_once __DIR__ . '/core/Database.php';

Auth::requireAuth();

if (!Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER, ROLE_ACCOUNTANT, ROLE_CASHIER) || Auth::isSpecialUser()) {
    View::renderBlank('errors/403');
    exit();
}

$user = Auth::user();
$branchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? 1);

// Optional initial product selection via URL query e.g. barcode_labels.php?product_id=123
$initialProductId = (int)($_GET['product_id'] ?? 0);
$initialProduct = null;
if ($initialProductId > 0) {
    $initialProduct = Database::fetchOne("
        SELECT id, sku, barcode, name, cost, selling_price 
        FROM products 
        WHERE id = :id AND deleted_at IS NULL
    ", [':id' => $initialProductId]);
}

$branches = Database::fetchAll("SELECT id, name FROM branches WHERE status = 'active' ORDER BY name ASC");
$shopName = Settings::get('business_name', 'Kandy Kids');
$currency = Settings::get('currency_symbol', 'Rs.');
if ($currency === 'LKR') {
    $currency = 'Rs.';
}

View::render('barcode_labels/index', [
    'user'             => $user,
    'branchId'         => $branchId,
    'branches'         => $branches,
    'shopName'         => $shopName,
    'currency'         => $currency,
    'initialProduct'   => $initialProduct,
    'initialProductId' => $initialProductId
], 'Barcode Label Printing - Curtiss POS');
