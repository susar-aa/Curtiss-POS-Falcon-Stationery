<?php
/**
 * Curtiss POS - Product Edit Controller
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Product.php';
require_once __DIR__ . '/core/Brand.php';
require_once __DIR__ . '/core/Category.php';
require_once __DIR__ . '/core/Supplier.php';

Auth::requireAuth();

if (!Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)) {
    View::renderBlank('errors/403');
    exit();
}

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$product = Product::find($id);

if (!$product) {
    header('Location: products');
    exit();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'sku'                 => trim($_POST['sku'] ?? ''),
        'barcode'             => trim($_POST['barcode'] ?? ''),
        'brand_id'            => (int)($_POST['brand_id'] ?? 0),
        'category_id'         => (int)($_POST['category_id'] ?? 0),
        'name'                => trim($_POST['name'] ?? ''),
        'supplier_id'         => (int)($_POST['supplier_id'] ?? 0),
        'cost'                => (float)($_POST['cost'] ?? 0),
        'selling_price'       => (float)($_POST['selling_price'] ?? 0),
        'opening_stock'       => (float)($_POST['opening_stock'] ?? 0),
        'low_stock_threshold' => (float)($_POST['low_stock_threshold'] ?? 10.00),
        'status'              => ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active',
        'has_expiry'          => !empty($_POST['has_expiry']) ? 1 : 0
    ];

    $res = Product::update($id, $data);
    if ($res['success']) {
        header('Location: products?updated=1');
        exit();
    } else {
        $errors = $res['errors'] ?? [$res['message']];
        $product = array_merge($product, $data);
    }
}

$brands = Brand::all(false);
$categories = Category::all(false);
$suppliers = Supplier::all(false);

View::render('products/form', [
    'isEdit'     => true,
    'product'    => $product,
    'errors'     => $errors,
    'brands'     => $brands,
    'categories' => $categories,
    'suppliers'  => $suppliers
], 'Edit Product - Curtiss POS');
