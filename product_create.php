<?php
/**
 * Curtiss POS - Product Create Controller
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

$errors = [];
$data = [
    'sku'                 => '',
    'barcode'             => '',
    'brand_id'            => '',
    'category_id'         => '',
    'description'         => '',
    'name'                => '',
    'supplier_id'         => '',
    'cost'                => '',
    'selling_price'       => '',
    'opening_stock'       => '',
    'low_stock_threshold' => '10.00',
    'status'              => 'active'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $barcode = trim($_POST['barcode'] ?? '');
    $sku = trim($_POST['sku'] ?? '');
    if ($sku === '') {
        $sku = $barcode;
    }
    $data = [
        'barcode'             => $barcode,
        'sku'                 => $sku,
        'brand_id'            => (int)($_POST['brand_id'] ?? 0),
        'category_id'         => (int)($_POST['category_id'] ?? 0),
        'description'         => trim($_POST['description'] ?? ''),
        'name'                => trim($_POST['name'] ?? ''),
        'supplier_id'         => (int)($_POST['supplier_id'] ?? 0),
        'supplier_ids'        => $_POST['supplier_ids'] ?? [],
        'cost'                => (float)($_POST['cost'] ?? 0),
        'selling_price'       => (float)($_POST['selling_price'] ?? 0),
        'opening_stock'       => (float)($_POST['opening_stock'] ?? 0),
        'low_stock_threshold' => (float)($_POST['low_stock_threshold'] ?? 10.00),
        'status'              => ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active',
        'has_expiry'          => !empty($_POST['has_expiry']) ? 1 : 0,
        'expiry_date'         => !empty($_POST['expiry_date']) ? trim($_POST['expiry_date']) : null,
        'batch_number'        => !empty($_POST['batch_number']) ? trim($_POST['batch_number']) : null
    ];

    $res = Product::create($data);
    if ($res['success']) {
        header('Location: products?created=1');
        exit();
    } else {
        $errors = $res['errors'] ?? [$res['message']];
    }
}

$brands = Brand::all(true);
$categories = Category::all(true);
$suppliers = Supplier::all(true);

View::render('products/form', [
    'isEdit'     => false,
    'product'    => $data,
    'errors'     => $errors,
    'brands'     => $brands,
    'categories' => $categories,
    'suppliers'  => $suppliers
], 'Add New Product - Curtiss POS');
