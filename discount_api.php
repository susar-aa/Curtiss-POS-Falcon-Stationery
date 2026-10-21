<?php
/**
 * Curtiss POS - Discount & Promotion Management API
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/DiscountEngine.php';
require_once __DIR__ . '/core/Database.php';

Auth::requireLogin();
$user     = Auth::user();
$userId   = (int)$user['id'];
$branchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? 1);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Block mutations for view-only Special Users
$mutatingActions = ['create', 'update', 'toggle_status', 'delete', 'restore'];
if (in_array($action, $mutatingActions, true) && Auth::isSpecialUser()) {
    echo json_encode(['success' => false, 'message' => 'Permission denied: Special User is view-only.']);
    exit;
}

try {
    switch ($action) {

        case 'list':
            $page  = max(1, (int)($_GET['page'] ?? 1));
            $limit = in_array((int)($_GET['limit'] ?? 25), [25, 50, 75, 100, 500]) ? (int)$_GET['limit'] : 25;

            $filters = [
                'branch_id' => !empty($_GET['branch_id']) ? (int)$_GET['branch_id'] : $branchId,
                'rule_type' => !empty($_GET['rule_type']) ? trim($_GET['rule_type']) : null,
                'status'    => !empty($_GET['status']) ? trim($_GET['status']) : null,
                'search'    => !empty($_GET['search']) ? trim($_GET['search']) : null,
                'view'      => (!empty($_GET['view']) && $_GET['view'] === 'deleted') ? 'deleted' : 'active'
            ];

            if (isset($_GET['branch_id']) && $_GET['branch_id'] === 'all') {
                $filters['branch_id'] = null;
            }

            $res = DiscountEngine::getRules($filters, $page, $limit);
            echo json_encode($res);
            break;

        case 'get':
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid rule ID.']);
                exit;
            }

            $rule = DiscountEngine::getRule($id);
            if (!$rule) {
                echo json_encode(['success' => false, 'message' => 'Rule not found.']);
                exit;
            }

            echo json_encode(['success' => true, 'data' => $rule]);
            break;

        case 'create':
            $payload = [
                'name'                   => trim($_POST['name'] ?? ''),
                'description'            => trim($_POST['description'] ?? ''),
                'rule_type'              => trim($_POST['rule_type'] ?? DiscountEngine::TYPE_ITEM),
                'discount_mode'          => trim($_POST['discount_mode'] ?? DiscountEngine::MODE_PERCENTAGE),
                'discount_value'         => round((float)($_POST['discount_value'] ?? 0), 2),
                'priority'               => (int)($_POST['priority'] ?? 10),
                'is_exclusive'           => !empty($_POST['is_exclusive']) ? 1 : 0,
                'is_stackable'           => isset($_POST['is_stackable']) ? (int)$_POST['is_stackable'] : 1,
                'allow_manual_discount'  => isset($_POST['allow_manual_discount']) ? (int)$_POST['allow_manual_discount'] : 1,
                'max_discount_amount'    => !empty($_POST['max_discount_amount']) ? (float)$_POST['max_discount_amount'] : null,
                'min_invoice_subtotal'   => !empty($_POST['min_invoice_subtotal']) ? (float)$_POST['min_invoice_subtotal'] : 0.00,
                'min_quantity'           => !empty($_POST['min_quantity']) ? (float)$_POST['min_quantity'] : 1.00,
                'get_quantity'           => !empty($_POST['get_quantity']) ? (float)$_POST['get_quantity'] : 0.00,
                'get_discount_mode'      => !empty($_POST['get_discount_mode']) ? trim($_POST['get_discount_mode']) : null,
                'get_discount_value'     => !empty($_POST['get_discount_value']) ? (float)$_POST['get_discount_value'] : null,
                'max_usage_total'        => !empty($_POST['max_usage_total']) ? (int)$_POST['max_usage_total'] : null,
                'max_usage_per_customer' => !empty($_POST['max_usage_per_customer']) ? (int)$_POST['max_usage_per_customer'] : null,
                'start_date'             => !empty($_POST['start_date']) ? trim($_POST['start_date']) : null,
                'end_date'               => !empty($_POST['end_date']) ? trim($_POST['end_date']) : null,
                'start_time'             => !empty($_POST['start_time']) ? trim($_POST['start_time']) : null,
                'end_time'               => !empty($_POST['end_time']) ? trim($_POST['end_time']) : null,
                'days_of_week'           => !empty($_POST['days_of_week']) ? (is_array($_POST['days_of_week']) ? implode(',', $_POST['days_of_week']) : $_POST['days_of_week']) : null,
                'branch_id'              => !empty($_POST['branch_id']) ? (int)$_POST['branch_id'] : null,
                'customer_id'            => !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : null,
                'payment_method'         => !empty($_POST['payment_method']) ? trim($_POST['payment_method']) : null,
                'coupon_code'            => !empty($_POST['coupon_code']) ? strtoupper(trim($_POST['coupon_code'])) : null,
                'status'                 => !empty($_POST['status']) ? trim($_POST['status']) : DiscountEngine::STATUS_ACTIVE,
                'targets'                => []
            ];

            // Parse primary targets if sent
            if (!empty($_POST['target_ids']) && is_array($_POST['target_ids'])) {
                $targetType = trim($_POST['target_type'] ?? 'product');
                foreach ($_POST['target_ids'] as $tid) {
                    if ((int)$tid > 0) {
                        $payload['targets'][] = ['target_type' => $targetType, 'target_id' => (int)$tid];
                    }
                }
            }

            // Parse secondary / reward targets for BOGO
            if (!empty($_POST['get_target_ids']) && is_array($_POST['get_target_ids'])) {
                foreach ($_POST['get_target_ids'] as $gtid) {
                    if ((int)$gtid > 0) {
                        $payload['targets'][] = ['target_type' => 'get_product', 'target_id' => (int)$gtid];
                    }
                }
            }

            $res = DiscountEngine::createRule($payload, $userId);
            echo json_encode($res);
            break;

        case 'update':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid rule ID.']);
                exit;
            }

            $payload = [
                'name'                   => trim($_POST['name'] ?? ''),
                'description'            => trim($_POST['description'] ?? ''),
                'rule_type'              => trim($_POST['rule_type'] ?? DiscountEngine::TYPE_ITEM),
                'discount_mode'          => trim($_POST['discount_mode'] ?? DiscountEngine::MODE_PERCENTAGE),
                'discount_value'         => round((float)($_POST['discount_value'] ?? 0), 2),
                'priority'               => (int)($_POST['priority'] ?? 10),
                'is_exclusive'           => !empty($_POST['is_exclusive']) ? 1 : 0,
                'is_stackable'           => isset($_POST['is_stackable']) ? (int)$_POST['is_stackable'] : 1,
                'allow_manual_discount'  => isset($_POST['allow_manual_discount']) ? (int)$_POST['allow_manual_discount'] : 1,
                'max_discount_amount'    => !empty($_POST['max_discount_amount']) ? (float)$_POST['max_discount_amount'] : null,
                'min_invoice_subtotal'   => !empty($_POST['min_invoice_subtotal']) ? (float)$_POST['min_invoice_subtotal'] : 0.00,
                'min_quantity'           => !empty($_POST['min_quantity']) ? (float)$_POST['min_quantity'] : 1.00,
                'get_quantity'           => !empty($_POST['get_quantity']) ? (float)$_POST['get_quantity'] : 0.00,
                'get_discount_mode'      => !empty($_POST['get_discount_mode']) ? trim($_POST['get_discount_mode']) : null,
                'get_discount_value'     => !empty($_POST['get_discount_value']) ? (float)$_POST['get_discount_value'] : null,
                'max_usage_total'        => !empty($_POST['max_usage_total']) ? (int)$_POST['max_usage_total'] : null,
                'max_usage_per_customer' => !empty($_POST['max_usage_per_customer']) ? (int)$_POST['max_usage_per_customer'] : null,
                'start_date'             => !empty($_POST['start_date']) ? trim($_POST['start_date']) : null,
                'end_date'               => !empty($_POST['end_date']) ? trim($_POST['end_date']) : null,
                'start_time'             => !empty($_POST['start_time']) ? trim($_POST['start_time']) : null,
                'end_time'               => !empty($_POST['end_time']) ? trim($_POST['end_time']) : null,
                'days_of_week'           => !empty($_POST['days_of_week']) ? (is_array($_POST['days_of_week']) ? implode(',', $_POST['days_of_week']) : $_POST['days_of_week']) : null,
                'branch_id'              => !empty($_POST['branch_id']) ? (int)$_POST['branch_id'] : null,
                'customer_id'            => !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : null,
                'payment_method'         => !empty($_POST['payment_method']) ? trim($_POST['payment_method']) : null,
                'coupon_code'            => !empty($_POST['coupon_code']) ? strtoupper(trim($_POST['coupon_code'])) : null,
                'status'                 => !empty($_POST['status']) ? trim($_POST['status']) : DiscountEngine::STATUS_ACTIVE
            ];

            if (isset($_POST['target_ids']) && is_array($_POST['target_ids'])) {
                $payload['targets'] = [];
                $targetType = trim($_POST['target_type'] ?? 'product');
                foreach ($_POST['target_ids'] as $tid) {
                    if ((int)$tid > 0) {
                        $payload['targets'][] = ['target_type' => $targetType, 'target_id' => (int)$tid];
                    }
                }
                if (!empty($_POST['get_target_ids']) && is_array($_POST['get_target_ids'])) {
                    foreach ($_POST['get_target_ids'] as $gtid) {
                        if ((int)$gtid > 0) {
                            $payload['targets'][] = ['target_type' => 'get_product', 'target_id' => (int)$gtid];
                        }
                    }
                }
            }

            $res = DiscountEngine::updateRule($id, $payload, $userId);
            echo json_encode($res);
            break;

        case 'toggle_status':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid rule ID.']);
                exit;
            }
            $res = DiscountEngine::toggleStatus($id, $userId);
            echo json_encode($res);
            break;

        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid rule ID.']);
                exit;
            }
            $res = DiscountEngine::deleteRule($id, $userId);
            echo json_encode($res);
            break;

        case 'restore':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid rule ID.']);
                exit;
            }
            $res = DiscountEngine::restoreRule($id, $userId);
            echo json_encode($res);
            break;

        case 'validate_coupon':
            $code = strtoupper(trim($_POST['coupon_code'] ?? ''));
            if ($code === '') {
                echo json_encode(['success' => false, 'message' => 'Coupon code is required.']);
                exit;
            }

            $rule = Database::fetchOne("
                SELECT * FROM discount_rules
                WHERE coupon_code = :cc AND status = 'active' AND deleted_at IS NULL
                  AND (branch_id IS NULL OR branch_id = :bid)
                  AND (start_date IS NULL OR start_date <= CURRENT_DATE())
                  AND (end_date IS NULL OR end_date >= CURRENT_DATE())
                  AND (max_usage_total IS NULL OR usage_count < max_usage_total)
                LIMIT 1
            ", [':cc' => $code, ':bid' => $branchId]);

            if (!$rule) {
                echo json_encode(['success' => false, 'message' => 'Invalid or expired coupon code.']);
                exit;
            }

            echo json_encode([
                'success' => true,
                'data' => [
                    'rule_id'        => (int)$rule['id'],
                    'rule_code'      => $rule['rule_code'],
                    'name'           => $rule['name'],
                    'coupon_code'    => $rule['coupon_code'],
                    'discount_mode'  => $rule['discount_mode'],
                    'discount_value' => (float)$rule['discount_value']
                ],
                'message' => "Coupon '{$rule['coupon_code']}' applied successfully!"
            ]);
            break;

        case 'entities':
            // Fast lookup for categories, brands, suppliers, products, customers for select comboboxes
            $categories = Database::fetchAll("SELECT id, name FROM categories WHERE status = 'active' AND deleted_at IS NULL ORDER BY name ASC");
            $brands     = Database::fetchAll("SELECT id, name FROM brands WHERE status = 'active' AND deleted_at IS NULL ORDER BY name ASC");
            $suppliers  = Database::fetchAll("SELECT id, name FROM suppliers WHERE status = 'active' AND deleted_at IS NULL ORDER BY name ASC");
            $products   = Database::fetchAll("SELECT id, name, COALESCE(sku, barcode, '') AS sku, barcode, selling_price FROM products WHERE status = 'active' AND deleted_at IS NULL ORDER BY name ASC LIMIT 300");
            $customers  = Database::fetchAll("SELECT id, name, phone FROM customers WHERE status = 'active' AND deleted_at IS NULL ORDER BY name ASC LIMIT 200");
            $branches   = Database::fetchAll("SELECT id, name FROM branches WHERE status = 'active' ORDER BY id ASC");

            echo json_encode([
                'success'    => true,
                'categories' => $categories,
                'brands'     => $brands,
                'suppliers'  => $suppliers,
                'products'   => $products,
                'customers'  => $customers,
                'branches'   => $branches
            ]);
            break;

        case 'search_products':
            $q = trim($_GET['q'] ?? $_POST['q'] ?? '');
            $params = [];
            $where = ["p.deleted_at IS NULL", "p.status = 'active'"];
            if ($q !== '') {
                $where[] = "(p.name LIKE :q1 OR p.sku LIKE :q2 OR p.barcode LIKE :q3)";
                $term = '%' . $q . '%';
                $params[':q1'] = $term;
                $params[':q2'] = $term;
                $params[':q3'] = $term;
            }
            $whereSql = implode(' AND ', $where);
            $prods = Database::fetchAll("
                SELECT p.id, p.name, COALESCE(p.sku, p.barcode, '') AS sku, p.barcode, p.selling_price
                FROM products p
                WHERE {$whereSql}
                ORDER BY p.name ASC
                LIMIT 50
            ", $params);

            echo json_encode(['success' => true, 'products' => $prods]);
            break;

        case 'export_csv':
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=discount_rules_' . date('Y-m-d') . '.csv');
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Rule ID', 'Rule Code', 'Name', 'Type', 'Mode', 'Value', 'Priority', 'Usage Count', 'Status', 'Start Date', 'End Date']);

            $rules = Database::fetchAll("
                SELECT dr.id, dr.rule_code, dr.name, dr.rule_type, dr.discount_mode, dr.discount_value, dr.priority, dr.usage_count, dr.status, dr.start_date, dr.end_date
                FROM discount_rules dr
                WHERE dr.deleted_at IS NULL
                ORDER BY dr.priority DESC, dr.id DESC
            ");

            foreach ($rules as $r) {
                fputcsv($out, [
                    $r['id'],
                    $r['rule_code'],
                    $r['name'],
                    $r['rule_type'],
                    $r['discount_mode'],
                    $r['discount_value'],
                    $r['priority'],
                    $r['usage_count'],
                    $r['status'],
                    $r['start_date'] ?: 'N/A',
                    $r['end_date'] ?: 'N/A'
                ]);
            }
            fclose($out);
            exit;

        default:
            echo json_encode(['success' => false, 'message' => "Unknown action: {$action}"]);
            break;
    }
} catch (Throwable $e) {
    Logger::error("Discount API error: " . $e->getMessage(), $e);
    echo json_encode(['success' => false, 'message' => 'Internal server error: ' . $e->getMessage()]);
}
