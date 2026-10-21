<?php
/**
 * Curtiss POS - Discount & Promotion Management Controller
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/DiscountEngine.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/View.php';

Auth::requireLogin();

$user     = Auth::user();
$userId   = (int)$user['id'];
$branchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? 1);

// Master reference lists for quick selection
$categories = Database::fetchAll("SELECT id, name FROM categories WHERE status = 'active' AND deleted_at IS NULL ORDER BY name ASC");
$brands     = Database::fetchAll("SELECT id, name FROM brands WHERE status = 'active' AND deleted_at IS NULL ORDER BY name ASC");
$suppliers  = Database::fetchAll("SELECT id, name FROM suppliers WHERE status = 'active' AND deleted_at IS NULL ORDER BY name ASC");
$branches   = Database::fetchAll("SELECT id, name FROM branches WHERE status = 'active' ORDER BY id ASC");
$products   = Database::fetchAll("SELECT id, name, COALESCE(sku, barcode, '') AS sku, barcode, selling_price FROM products WHERE status = 'active' AND deleted_at IS NULL ORDER BY name ASC LIMIT 300");
$customers  = Database::fetchAll("SELECT id, name, phone FROM customers WHERE status = 'active' AND deleted_at IS NULL ORDER BY name ASC LIMIT 200");

View::render('discounts/index', [
    'user'       => $user,
    'userId'     => $userId,
    'branchId'   => $branchId,
    'categories' => $categories,
    'brands'     => $brands,
    'suppliers'  => $suppliers,
    'branches'   => $branches,
    'products'   => $products,
    'customers'  => $customers
], 'Discount & Promotions - Curtiss POS');

