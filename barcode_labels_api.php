<?php
/**
 * Curtiss POS - Barcode Label Printing API Endpoint
 * Handles product lookups, lot inspection, printer/label settings, and read-only audit logging.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Logger.php';
require_once __DIR__ . '/core/Audit.php';
require_once __DIR__ . '/core/Settings.php';
require_once __DIR__ . '/core/Sale.php';
require_once __DIR__ . '/core/Inventory.php';

Auth::requireLogin();

if (!Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER, ROLE_ACCOUNTANT, ROLE_CASHIER) || Auth::isSpecialUser()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Permission denied: Insufficient privileges for barcode label printing.']);
    exit;
}

$user = Auth::user();
$userId = (int)$user['id'];
$branchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? 1);
$action = trim($_GET['action'] ?? $_POST['action'] ?? '');

try {
    switch ($action) {

        case 'search_products':
            $q = trim($_GET['q'] ?? '');
            if ($q === '') {
                echo json_encode(['success' => true, 'products' => []]);
                exit;
            }

            $products = Sale::searchProducts($branchId, $q);

            // Enhance each product with active lot details and fallback lot if none exists
            $results = [];
            foreach ($products as $p) {
                $lots = Sale::getAvailableLotsForProduct((int)$p['product_id'], $branchId);
                
                // If product has no specific lots recorded, create a virtual default lot from master
                if (empty($lots)) {
                    $lots = [
                        [
                            'lot_id'             => 0,
                            'lot_number'         => 'DEFAULT-MASTER',
                            'product_id'         => (int)$p['product_id'],
                            'branch_id'          => $branchId,
                            'cost'               => (float)($p['default_cost'] ?? 0),
                            'selling_price'      => (float)($p['default_price'] ?? 0),
                            'remaining_quantity' => (float)($p['total_branch_stock'] ?? 0),
                            'expiry_date'        => null,
                            'batch_number'       => 'MASTER'
                        ]
                    ];
                }

                $results[] = [
                    'id'                 => (int)$p['product_id'],
                    'sku'                => $p['sku'] ?? '',
                    'barcode'            => $p['barcode'] ?? '',
                    'name'               => $p['product_name'] ?? '',
                    'brand_name'         => $p['brand_name'] ?? '',
                    'category_name'      => $p['category_name'] ?? '',
                    'default_price'      => (float)($p['default_price'] ?? 0),
                    'default_cost'       => (float)($p['default_cost'] ?? 0),
                    'total_branch_stock' => (float)($p['total_branch_stock'] ?? 0),
                    'lots'               => $lots
                ];
            }

            echo json_encode(['success' => true, 'products' => $results]);
            break;

        case 'get_product_lots':
            $productId = (int)($_GET['product_id'] ?? 0);
            if ($productId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid product ID.']);
                exit;
            }

            $product = Database::fetchOne("
                SELECT id, sku, barcode, name, cost, selling_price 
                FROM products 
                WHERE id = :id AND deleted_at IS NULL
            ", [':id' => $productId]);

            if (!$product) {
                echo json_encode(['success' => false, 'message' => 'Product not found.']);
                exit;
            }

            $lots = Sale::getAvailableLotsForProduct($productId, $branchId);
            if (empty($lots)) {
                $stockRow = Database::fetchOne("
                    SELECT quantity FROM branch_inventory WHERE product_id = :pid AND branch_id = :bid
                ", [':pid' => $productId, ':bid' => $branchId]);

                $lots = [
                    [
                        'lot_id'             => 0,
                        'lot_number'         => 'DEFAULT-MASTER',
                        'product_id'         => $productId,
                        'branch_id'          => $branchId,
                        'cost'               => (float)($product['cost'] ?? 0),
                        'selling_price'      => (float)($product['selling_price'] ?? 0),
                        'remaining_quantity' => (float)($stockRow['quantity'] ?? 0),
                        'expiry_date'        => null,
                        'batch_number'       => 'MASTER'
                    ]
                ];
            }

            echo json_encode(['success' => true, 'product' => $product, 'lots' => $lots]);
            break;

        case 'get_settings':
            $shopName = Settings::get('business_name', 'Kandy Kids');
            $currency = Settings::get('currency_symbol', 'Rs.');
            if ($currency === 'LKR') {
                $currency = 'Rs.';
            }

            $settings = [
                'printer_model'     => Settings::get('label_printer_model', 'TSC TE244'),
                'label_width_mm'    => (float)Settings::get('label_width_mm', '25'),
                'label_height_mm'   => (float)Settings::get('label_height_mm', '40'),
                'labels_per_row'    => (int)Settings::get('labels_per_row', '2'),
                'column_gap_mm'     => (float)Settings::get('label_column_gap_mm', '2'),
                'row_gap_mm'        => (float)Settings::get('label_row_gap_mm', '2'),
                'margin_top_mm'     => (float)Settings::get('label_margin_top_mm', '0'),
                'margin_left_mm'    => (float)Settings::get('label_margin_left_mm', '0'),
                'rotation_deg'      => (int)Settings::get('label_rotation_deg', '0'),
                'shop_name'         => Settings::get('label_shop_name_custom', $shopName),
                'price_prefix'      => Settings::get('label_price_prefix', $currency),
                'show_shop_name'    => (int)Settings::get('label_show_shop_name', '1'),
                'show_product_code' => (int)Settings::get('label_show_product_code', '1'),
                'show_price'        => (int)Settings::get('label_show_price', '1'),
                'barcode_height_mm' => (float)Settings::get('label_barcode_height_mm', '12'),
                'font_size_name_pt' => (float)Settings::get('label_font_size_name_pt', '8'),
                'font_size_price_pt'=> (float)Settings::get('label_font_size_price_pt', '11')
            ];

            echo json_encode(['success' => true, 'settings' => $settings]);
            break;

        case 'save_settings':
            if (!Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN)) {
                echo json_encode(['success' => false, 'message' => 'Permission denied: Admin rights required to save label printer configuration.']);
                exit;
            }

            $allowedKeys = [
                'label_printer_model'       => trim($_POST['printer_model'] ?? 'TSC TE244'),
                'label_width_mm'            => (string)max(10, (float)($_POST['label_width_mm'] ?? 25)),
                'label_height_mm'           => (string)max(10, (float)($_POST['label_height_mm'] ?? 40)),
                'labels_per_row'            => (string)max(1, min(4, (int)($_POST['labels_per_row'] ?? 2))),
                'label_column_gap_mm'       => (string)max(0, (float)($_POST['column_gap_mm'] ?? 2)),
                'label_row_gap_mm'          => (string)max(0, (float)($_POST['row_gap_mm'] ?? 2)),
                'label_margin_top_mm'       => (string)max(0, (float)($_POST['margin_top_mm'] ?? 0)),
                'label_margin_left_mm'      => (string)max(0, (float)($_POST['margin_left_mm'] ?? 0)),
                'label_rotation_deg'        => (string)(in_array((int)($_POST['rotation_deg'] ?? 0), [0, 90, 180, 270]) ? (int)$_POST['rotation_deg'] : 0),
                'label_shop_name_custom'    => trim($_POST['shop_name'] ?? 'Kandy Kids'),
                'label_price_prefix'        => trim($_POST['price_prefix'] ?? 'Rs.'),
                'label_show_shop_name'      => !empty($_POST['show_shop_name']) ? '1' : '0',
                'label_show_product_code'   => !empty($_POST['show_product_code']) ? '1' : '0',
                'label_show_price'          => !empty($_POST['show_price']) ? '1' : '0',
                'label_barcode_height_mm'   => (string)max(6, min(30, (float)($_POST['barcode_height_mm'] ?? 12))),
                'label_font_size_name_pt'   => (string)max(6, min(14, (float)($_POST['font_size_name_pt'] ?? 8))),
                'label_font_size_price_pt'  => (string)max(8, min(20, (float)($_POST['font_size_price_pt'] ?? 11)))
            ];

            foreach ($allowedKeys as $k => $v) {
                Settings::set($k, $v);
            }

            Audit::log('update_label_settings', 'inventory', 0, [], $allowedKeys);
            echo json_encode(['success' => true, 'message' => 'Barcode label printer settings saved successfully.']);
            break;

        case 'log_print':
            $count = (int)($_POST['total_labels'] ?? 0);
            $itemsJson = $_POST['items_json'] ?? '[]';
            $items = json_decode($itemsJson, true) ?: [];

            Audit::log('print_barcode_labels', 'inventory', 0, [], [
                'total_labels' => $count,
                'items_count'  => count($items),
                'branch_id'    => $branchId,
                'timestamp'    => date('Y-m-d H:i:s')
            ]);

            echo json_encode(['success' => true, 'message' => "Audit logged for {$count} printed labels."]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => "Unknown action '{$action}'."]);
            break;
    }
} catch (Throwable $e) {
    Logger::error("barcode_labels_api.php error: " . $e->getMessage(), $e);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
