<?php
/**
 * Curtiss POS - Products Controller (Section 1 Product Master & Catalog Management)
 * Supports search, filters, pagination, bulk operations, status toggle, and safe deletion.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Product.php';
require_once __DIR__ . '/core/Brand.php';
require_once __DIR__ . '/core/Category.php';
require_once __DIR__ . '/core/Supplier.php';

Auth::requireAuth();

if (!Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER, ROLE_ACCOUNTANT, ROLE_CASHIER, ROLE_SPECIAL_USER)) {
    View::renderBlank('errors/403');
    exit();
}

$message = '';
$error = '';

// Build absolute base URL for redirects (prevents path-resolution issues in sub-directory setups)
$_appBaseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
    . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
    . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';

// Handle POST actions (Toggle Status, Single Delete, Bulk Actions)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)) {
        $action = $_POST['action'];

        if ($action === 'toggle') {
            $id = (int)($_POST['id'] ?? 0);
            $res = Product::toggleStatus($id);
            if ($res['success']) {
                header('Location: ' . $_appBaseUrl . 'products?toggled=1');
                exit();
            } else {
                $error = $res['message'];
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $res = Product::delete($id);
            if ($res['success']) {
                header('Location: ' . $_appBaseUrl . 'products?deleted=1');
                exit();
            } else {
                $error = $res['message'];
            }
        } elseif ($action === 'restore') {
            $id = (int)($_POST['id'] ?? 0);
            $res = Product::restore($id);
            if ($res['success']) {
                header('Location: ' . $_appBaseUrl . 'products?restored=1');
                exit();
            } else {
                $error = $res['message'];
            }
        } elseif ($action === 'bulk_action') {
            $bulkType = $_POST['bulk_type'] ?? '';
            $ids = $_POST['product_ids'] ?? [];
            if (!is_array($ids)) {
                $ids = explode(',', (string)$ids);
            }

            $extraData = [
                'category_id'         => (int)($_POST['bulk_category_id'] ?? 0),
                'brand_id'            => (int)($_POST['bulk_brand_id'] ?? 0),
                'supplier_id'         => (int)($_POST['bulk_supplier_id'] ?? 0),
                'low_stock_threshold' => (float)($_POST['bulk_low_stock_threshold'] ?? 10.00)
            ];

            $res = Product::bulkAction($ids, $bulkType, $extraData);
            if ($res['success']) {
                $message = $res['message'];
            } else {
                $error = $res['message'];
            }
        }
    }
}

$filters = [
    'search'      => trim($_GET['search'] ?? ''),
    'sku'         => trim($_GET['sku'] ?? ''),
    'barcode'     => trim($_GET['barcode'] ?? ''),
    'brand_id'    => (int)($_GET['brand_id'] ?? 0),
    'category_id' => (int)($_GET['category_id'] ?? 0),
    'supplier_id' => (int)($_GET['supplier_id'] ?? 0),
    'status'      => trim($_GET['status'] ?? ''),
    'sort_by'     => trim($_GET['sort_by'] ?? 'id'),
    'sort_dir'    => trim($_GET['sort_dir'] ?? 'DESC'),
    'branch_id'   => (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? 1)
];

$allowedLimits = [25, 50, 75, 100, 500];
$limit = isset($_GET['limit']) && in_array((int)$_GET['limit'], $allowedLimits, true) ? (int)$_GET['limit'] : 25;
$page = max(1, (int)($_GET['page'] ?? 1));

$result = Product::getList($filters, $page, $limit);

$brands = Brand::all(false);
$categories = Category::all(false);
$suppliers = Supplier::allUnique(false);

$viewMode = $_GET['view'] ?? 'active';
$deletedProducts = Product::deleted();

if ($viewMode === 'deleted') {
    $displayProducts = $deletedProducts;
    $totalItems = count($deletedProducts);
    $totalPages = 1;
} else {
    $displayProducts = $result['items'];
    $totalItems = $result['totalItems'];
    $totalPages = $result['totalPages'];
}

View::render('products/index', [
    'products'        => $displayProducts,
    'deletedProducts' => $deletedProducts,
    'viewMode'        => $viewMode,
    'totalItems'      => $totalItems,
    'page'            => $result['page'],
    'totalPages'      => $totalPages,
    'limit'           => $limit,
    'allowedLimits'   => $allowedLimits,
    'filters'         => $filters,
    'brands'          => $brands,
    'categories'      => $categories,
    'suppliers'       => $suppliers,
    'message'         => $message,
    'error'           => $error
], 'Products - Curtiss POS');
