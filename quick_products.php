<?php
/**
 * Falcon POS - Quick Product & Stock Editor Controller
 * Fast, inline catalog editing and real-time inventory adjustments.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Audit.php';
require_once __DIR__ . '/core/View.php';

Auth::requireAuth();

if (!Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER, ROLE_ACCOUNTANT)) {
    View::renderBlank('errors/403');
    exit();
}

$branchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? 1);
$userId = (int)($_SESSION['user_id'] ?? 1);

// ─── AJAX Request Handling ───────────────────────────────────────────────────
if (isset($_REQUEST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_REQUEST['action'];

    // 1. Live Search & Filter API
    if ($action === 'search') {
        $q = trim($_GET['q'] ?? '');
        $params = [':bid' => $branchId];
        $whereSql = "p.deleted_at IS NULL";

        if (!empty($q)) {
            $whereSql .= " AND (p.name LIKE :q1 OR p.barcode LIKE :q2 OR p.sku LIKE :q3)";
            $params[':q1'] = '%' . $q . '%';
            $params[':q2'] = '%' . $q . '%';
            $params[':q3'] = '%' . $q . '%';
        }

        $sql = "
            SELECT 
                p.id,
                p.sku,
                p.barcode,
                p.name,
                p.cost,
                p.selling_price,
                p.status,
                COALESCE(bi.quantity, 0.00) AS stock,
                COALESCE(b.name, 'Unassigned') AS brand_name,
                COALESCE(c.name, 'General') AS category_name
            FROM products p
            LEFT JOIN branch_inventory bi ON p.id = bi.product_id AND bi.branch_id = :bid
            LEFT JOIN brands b ON p.brand_id = b.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE $whereSql
            ORDER BY p.id DESC
            LIMIT 500
        ";

        try {
            $results = Database::fetchAll($sql, $params);
            echo json_encode(['success' => true, 'count' => count($results), 'products' => $results]);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // 2. Single Product & Stock Update API
    if ($action === 'update_product') {
        $productId    = (int)($_POST['id'] ?? 0);
        $name         = trim($_POST['name'] ?? '');
        $sku          = trim($_POST['sku'] ?? '');
        $barcode      = trim($_POST['barcode'] ?? '');
        $cost         = (float)($_POST['cost'] ?? 0);
        $sellingPrice = (float)($_POST['selling_price'] ?? 0);
        $newStock     = (float)($_POST['stock'] ?? 0);

        if ($productId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid Product ID.']);
            exit();
        }

        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Product Name cannot be empty.']);
            exit();
        }

        if (empty($barcode)) {
            echo json_encode(['success' => false, 'message' => 'Barcode cannot be empty.']);
            exit();
        }

        // If SKU is empty, default to barcode
        if ($sku === '') {
            $sku = $barcode;
        }

        // Check if barcode is already used by a different product
        $existing = Database::fetchOne("
            SELECT id FROM products 
            WHERE barcode = :b AND id != :id AND deleted_at IS NULL 
            LIMIT 1
        ", [':b' => $barcode, ':id' => $productId]);

        if ($existing) {
            echo json_encode(['success' => false, 'message' => "Barcode '$barcode' is already assigned to another product (ID #{$existing['id']})."]);
            exit();
        }

        // Check if SKU is already used by a different product
        if (!empty($sku)) {
            $existingSku = Database::fetchOne("
                SELECT id FROM products 
                WHERE sku = :s AND id != :id AND deleted_at IS NULL 
                LIMIT 1
            ", [':s' => $sku, ':id' => $productId]);

            if ($existingSku) {
                echo json_encode(['success' => false, 'message' => "SKU '$sku' is already assigned to another product (ID #{$existingSku['id']})."]);
                exit();
            }
        }

        try {
            // Fetch current product & inventory details for audit & stock ledger
            $currentProduct = Database::fetchOne("SELECT * FROM products WHERE id = :id AND deleted_at IS NULL", [':id' => $productId]);
            if (!$currentProduct) {
                echo json_encode(['success' => false, 'message' => 'Product not found.']);
                exit();
            }

            $currentInv = Database::fetchOne("SELECT quantity FROM branch_inventory WHERE product_id = :pid AND branch_id = :bid", [
                ':pid' => $productId,
                ':bid' => $branchId
            ]);
            $oldStock = (float)($currentInv['quantity'] ?? 0.00);

            // 1. Update product master details
            Database::execute("
                UPDATE products 
                SET name = :name, sku = :sku, barcode = :barcode, cost = :cost, selling_price = :selling_price, updated_at = NOW()
                WHERE id = :id
            ", [
                ':name'          => $name,
                ':sku'           => $sku,
                ':barcode'       => $barcode,
                ':cost'          => $cost,
                ':selling_price' => $sellingPrice,
                ':id'            => $productId
            ]);

            // 2. Direct Stock Update on branch_inventory
            Database::execute("
                INSERT INTO branch_inventory (branch_id, product_id, quantity, updated_at)
                VALUES (:bid, :pid, :qty, NOW())
                ON DUPLICATE KEY UPDATE quantity = VALUES(quantity), updated_at = NOW()
            ", [
                ':bid' => $branchId,
                ':pid' => $productId,
                ':qty' => $newStock
            ]);

            // 3. Record in stock_ledger if stock has changed
            $stockDiff = round($newStock - $oldStock, 4);
            if (abs($stockDiff) > 0.0001) {
                Database::execute("
                    INSERT INTO stock_ledger (branch_id, product_id, movement_type, quantity, balance_after, reference_no, notes, user_id, created_at)
                    VALUES (:bid, :pid, 'adjustment', :qty, :bal, :ref, :notes, :uid, NOW())
                ", [
                    ':bid'   => $branchId,
                    ':pid'   => $productId,
                    ':qty'   => $stockDiff,
                    ':bal'   => $newStock,
                    ':ref'   => 'QUICK_EDIT_' . $productId,
                    ':notes' => "Quick Product Editor stock change from {$oldStock} to {$newStock}",
                    ':uid'   => $userId
                ]);
            }

            // 4. Audit Log
            Audit::log('PRODUCT_QUICK_UPDATE', 'products', (string)$productId, [
                'name'          => $currentProduct['name'],
                'sku'           => $currentProduct['sku'] ?? '',
                'barcode'       => $currentProduct['barcode'],
                'cost'          => $currentProduct['cost'],
                'selling_price' => $currentProduct['selling_price'],
                'stock'         => $oldStock
            ], [
                'name'          => $name,
                'sku'           => $sku,
                'barcode'       => $barcode,
                'cost'          => $cost,
                'selling_price' => $sellingPrice,
                'stock'         => $newStock
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Product and inventory updated successfully!',
                'data'    => [
                    'id'            => $productId,
                    'name'          => $name,
                    'sku'           => $sku,
                    'barcode'       => $barcode,
                    'cost'          => $cost,
                    'selling_price' => $sellingPrice,
                    'stock'         => $newStock,
                    'stock_changed' => abs($stockDiff) > 0.0001
                ]
            ]);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Database update error: ' . $e->getMessage()]);
        }
        exit();
    }

    // 3. Quick Add Product API
    if ($action === 'add_product') {
        $name         = trim($_POST['name'] ?? '');
        $sku          = trim($_POST['sku'] ?? '');
        $barcode      = trim($_POST['barcode'] ?? '');
        $cost         = max(0.00, (float)($_POST['cost'] ?? 0));
        $sellingPrice = max(0.00, (float)($_POST['selling_price'] ?? 0));
        $stock        = max(0.00, (float)($_POST['stock'] ?? 0));

        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Product Name cannot be empty.']);
            exit();
        }

        if (empty($barcode)) {
            echo json_encode(['success' => false, 'message' => 'Barcode cannot be empty.']);
            exit();
        }

        // If SKU is empty, default to barcode
        if (empty($sku)) {
            $sku = $barcode;
        }

        // Check if barcode already exists on active products
        $existing = Database::fetchOne("
            SELECT id FROM products 
            WHERE barcode = :b AND deleted_at IS NULL 
            LIMIT 1
        ", [':b' => $barcode]);

        if ($existing) {
            echo json_encode(['success' => false, 'message' => "Barcode '$barcode' is already assigned to another product (ID #{$existing['id']})."]);
            exit();
        }

        // Check if SKU already exists on active products
        if (!empty($sku)) {
            $existingSku = Database::fetchOne("
                SELECT id FROM products 
                WHERE sku = :s AND deleted_at IS NULL 
                LIMIT 1
            ", [':s' => $sku]);

            if ($existingSku) {
                echo json_encode(['success' => false, 'message' => "SKU '$sku' is already assigned to another product (ID #{$existingSku['id']})."]);
                exit();
            }
        }

        try {
            // Ensure default brand, category, supplier exist without user having to configure them
            $brandId = (int)(Database::fetchOne("SELECT id FROM brands WHERE deleted_at IS NULL ORDER BY id ASC LIMIT 1")['id'] ?? 0);
            if ($brandId <= 0) {
                Database::execute("INSERT INTO brands (name, created_at) VALUES ('General', NOW())");
                $brandId = (int)Database::lastInsertId();
            }

            $categoryId = (int)(Database::fetchOne("SELECT id FROM categories WHERE deleted_at IS NULL ORDER BY id ASC LIMIT 1")['id'] ?? 0);
            if ($categoryId <= 0) {
                Database::execute("INSERT INTO categories (name, created_at) VALUES ('General', NOW())");
                $categoryId = (int)Database::lastInsertId();
            }

            $supplierId = (int)(Database::fetchOne("SELECT id FROM suppliers WHERE deleted_at IS NULL ORDER BY id ASC LIMIT 1")['id'] ?? 0);
            if ($supplierId <= 0) {
                Database::execute("INSERT INTO suppliers (name, created_at) VALUES ('General Supplier', NOW())");
                $supplierId = (int)Database::lastInsertId();
            }

            Database::execute("
                INSERT INTO products (sku, barcode, brand_id, category_id, supplier_id, name, cost, selling_price, opening_stock, low_stock_threshold, status, has_expiry, created_at, updated_at)
                VALUES (:sku, :barcode, :brand_id, :category_id, :supplier_id, :name, :cost, :selling_price, :opening_stock, 5, 'active', 0, NOW(), NOW())
            ", [
                ':sku'           => $sku,
                ':barcode'       => $barcode,
                ':brand_id'      => $brandId,
                ':category_id'   => $categoryId,
                ':supplier_id'   => $supplierId,
                ':name'          => $name,
                ':cost'          => $cost,
                ':selling_price' => $sellingPrice,
                ':opening_stock' => $stock
            ]);

            $newId = (int)Database::lastInsertId();

            // Set live branch inventory
            Database::execute("
                INSERT INTO branch_inventory (branch_id, product_id, quantity, updated_at)
                VALUES (:bid, :pid, :qty, NOW())
                ON DUPLICATE KEY UPDATE quantity = VALUES(quantity), updated_at = NOW()
            ", [
                ':bid' => $branchId,
                ':pid' => $newId,
                ':qty' => $stock
            ]);

            // Record initial movement in stock_ledger if initial stock > 0
            if ($stock > 0) {
                Database::execute("
                    INSERT INTO stock_ledger (branch_id, product_id, movement_type, quantity, balance_after, reference_no, notes, user_id, created_at)
                    VALUES (:bid, :pid, 'opening_stock', :qty, :bal, :ref, :notes, :uid, NOW())
                ", [
                    ':bid'   => $branchId,
                    ':pid'   => $newId,
                    ':qty'   => $stock,
                    ':bal'   => $stock,
                    ':ref'   => 'QUICK_ADD_' . $newId,
                    ':notes' => "Opening stock added via Quick Product Editor",
                    ':uid'   => $userId
                ]);
            }

            // Audit log
            Audit::log('PRODUCT_QUICK_CREATE', 'products', (string)$newId, null, [
                'name'          => $name,
                'barcode'       => $barcode,
                'sku'           => $sku,
                'cost'          => $cost,
                'selling_price' => $sellingPrice,
                'stock'         => $stock,
                'branch_id'     => $branchId
            ]);

            echo json_encode([
                'success' => true,
                'message' => "Product '{$name}' created successfully!",
                'product' => [
                    'id'            => $newId,
                    'name'          => $name,
                    'barcode'       => $barcode,
                    'sku'           => $sku,
                    'cost'          => $cost,
                    'selling_price' => $sellingPrice,
                    'stock'         => $stock
                ]
            ]);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Error adding product: ' . $e->getMessage()]);
        }
        exit();
    }

    // 4. Delete Product API (Move to Trash Bin)
    if ($action === 'delete_product') {
        $productId = (int)($_POST['id'] ?? 0);
        if ($productId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid Product ID.']);
            exit();
        }

        require_once __DIR__ . '/core/Product.php';
        $res = Product::delete($productId);
        echo json_encode($res);
        exit();
    }

    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    exit();
}

// ─── Initial Page Load Query (Optimized Server-Side Query) ───────────────────
$sql = "
    SELECT 
        p.id,
        p.sku,
        p.barcode,
        p.name,
        p.cost,
        p.selling_price,
        p.status,
        COALESCE(bi.quantity, 0.00) AS stock,
        COALESCE(b.name, 'Unassigned') AS brand_name,
        COALESCE(c.name, 'General') AS category_name
    FROM products p
    LEFT JOIN branch_inventory bi ON p.id = bi.product_id AND bi.branch_id = :bid
    LEFT JOIN brands b ON p.brand_id = b.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.deleted_at IS NULL
    ORDER BY p.id DESC
    LIMIT 200
";

$totalCount = (int)(Database::fetchOne("SELECT COUNT(*) AS total FROM products WHERE deleted_at IS NULL")['total'] ?? 0);
$products = Database::fetchAll($sql, [':bid' => $branchId]);

$activeBranchName = $_SESSION['active_branch_name'] ?? 'Main Branch';

View::render('products/quick_edit', [
    'products'         => $products,
    'totalCount'       => $totalCount,
    'branchId'         => $branchId,
    'activeBranchName' => $activeBranchName
], 'Quick Product & Inventory Editor - Falcon POS');
