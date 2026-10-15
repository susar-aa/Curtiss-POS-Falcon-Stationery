<?php
/**
 * Curtiss POS - Purchase Order Core Component
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Audit.php';
require_once __DIR__ . '/Supplier.php';
require_once __DIR__ . '/Product.php';

class PurchaseOrder
{
    public static function getList(?int $branchId = null, array $filters = [], int $page = 1, int $limit = 15): array
    {
        try {
            $where = [];
            $params = [];

            if ($branchId) {
                $where[] = "po.branch_id = :branch_id";
                $params[':branch_id'] = $branchId;
            }

            if (!empty($filters['supplier_id'])) {
                $where[] = "po.supplier_id = :supplier_id";
                $params[':supplier_id'] = (int)$filters['supplier_id'];
            }

            if (!empty($filters['status'])) {
                $where[] = "po.status = :status";
                $params[':status'] = $filters['status'];
            }

            if (!empty($filters['view']) && $filters['view'] === 'deleted') {
                $where[] = "po.deleted_at IS NOT NULL";
            } else {
                $where[] = "po.deleted_at IS NULL";
            }

            if (!empty($filters['search'])) {
                $rawSearch = trim($filters['search']);
                $searchTerm = '%' . $rawSearch . '%';
                $stripped = preg_replace('/^(rs\.?|lkr)\s*/i', '', $rawSearch);
                $stripped = str_replace([',', ' '], '', $stripped);
                $cleanSearch = '%' . $stripped . '%';

                $where[] = "(
                    po.po_number LIKE :s1 OR s.name LIKE :s2 OR s.supplier_code LIKE :s3 OR
                    b.name LIKE :s4 OR po.status LIKE :s5 OR u.username LIKE :s6 OR u.full_name LIKE :s7 OR
                    CAST(po.order_date AS CHAR) LIKE :s8 OR CAST(po.expected_date AS CHAR) LIKE :s9 OR
                    po.notes LIKE :s10 OR
                    CAST(po.net_amount AS CHAR) LIKE :s11 OR FORMAT(po.net_amount, 2) LIKE :s12 OR REPLACE(FORMAT(po.net_amount, 2), ',', '') LIKE :s13 OR CONCAT('LKR ', FORMAT(po.net_amount, 2)) LIKE :s14 OR CONCAT('Rs. ', FORMAT(po.net_amount, 2)) LIKE :s15 OR
                    CAST(po.total_amount AS CHAR) LIKE :s16 OR FORMAT(po.total_amount, 2) LIKE :s17 OR REPLACE(FORMAT(po.total_amount, 2), ',', '') LIKE :s18
                )";
                for ($i = 1; $i <= 10; $i++) {
                    $params[":s{$i}"] = $searchTerm;
                }
                $params[':s11'] = $searchTerm;
                $params[':s12'] = $searchTerm;
                $params[':s13'] = $cleanSearch;
                $params[':s14'] = $searchTerm;
                $params[':s15'] = $searchTerm;
                $params[':s16'] = $searchTerm;
                $params[':s17'] = $searchTerm;
                $params[':s18'] = $cleanSearch;
            }

            if (!empty($filters['date_from'])) {
                $where[] = "po.order_date >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }

            if (!empty($filters['date_to'])) {
                $where[] = "po.order_date <= :date_to";
                $params[':date_to'] = $filters['date_to'];
            }

            $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

            $countRow = Database::fetchOne("
                SELECT COUNT(*) AS total 
                FROM purchase_orders po 
                JOIN suppliers s ON po.supplier_id = s.id
                JOIN branches b ON po.branch_id = b.id
                JOIN users u ON po.created_by = u.id
                $whereSql
            ", $params);

            $totalItems = (int)($countRow['total'] ?? 0);
            $offset = ($page - 1) * $limit;

            $sql = "
                SELECT 
                    po.*,
                    s.name AS supplier_name,
                    s.supplier_code,
                    b.name AS branch_name,
                    u.username AS creator_name,
                    u2.username AS approver_name
                FROM purchase_orders po
                JOIN suppliers s ON po.supplier_id = s.id
                JOIN branches b ON po.branch_id = b.id
                JOIN users u ON po.created_by = u.id
                LEFT JOIN users u2 ON po.approved_by = u2.id
                $whereSql
                ORDER BY po.id DESC
                LIMIT $limit OFFSET $offset
            ";

            $items = Database::fetchAll($sql, $params);

            return [
                'items'      => $items,
                'totalItems' => $totalItems,
                'page'       => $page,
                'limit'      => $limit,
                'totalPages' => max(1, ceil($totalItems / $limit))
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to fetch PO list: " . $e->getMessage(), $e);
            return ['items' => [], 'totalItems' => 0, 'page' => 1, 'limit' => $limit, 'totalPages' => 1];
        }
    }

    public static function find(int $id): ?array
    {
        try {
            $po = Database::fetchOne("
                SELECT po.*, 
                       s.name AS supplier_name, s.supplier_code, s.status AS supplier_status, s.phone AS supplier_phone, s.whatsapp AS supplier_whatsapp, s.address AS supplier_address,
                       b.name AS branch_name, b.code AS branch_code, b.address AS branch_address, b.phone AS branch_phone, b.email AS branch_email,
                       u.full_name AS creator_name,
                       u2.full_name AS approver_name
                FROM purchase_orders po
                JOIN suppliers s ON po.supplier_id = s.id
                JOIN branches b ON po.branch_id = b.id
                JOIN users u ON po.created_by = u.id
                LEFT JOIN users u2 ON po.approved_by = u2.id
                WHERE po.id = :id
            ", [':id' => $id]);

            if (!$po) return null;

            $items = Database::fetchAll("
                SELECT poi.*, p.name AS product_name, p.sku, p.barcode, p.has_expiry, p.status AS product_status
                FROM purchase_order_items poi
                JOIN products p ON poi.product_id = p.id
                WHERE poi.po_id = :po_id
            ", [':po_id' => $id]);

            $po['items'] = $items;
            return $po;
        } catch (Throwable $e) {
            Logger::error("Failed to find PO #$id: " . $e->getMessage(), $e);
            return null;
        }
    }

    public static function searchProducts(string $query, bool $activeOnly = true): array
    {
        $query = trim($query);
        if (empty($query)) return [];

        try {
            $where = "(p.name LIKE :q OR p.sku LIKE :q OR p.barcode LIKE :q)";
            $params = [':q' => '%' . $query . '%'];

            if ($activeOnly) {
                $where .= " AND p.status = 'active'";
            }

            return Database::fetchAll("
                SELECT p.id, p.sku, p.barcode, p.name, p.cost, p.selling_price, p.status
                FROM products p
                WHERE $where
                ORDER BY p.name ASC
                LIMIT 20
            ", $params);
        } catch (Throwable $e) {
            Logger::error("Failed to search products for PO: " . $e->getMessage(), $e);
            return [];
        }
    }

    public static function create(array $header, array $items): array
    {
        $supplierId = (int)($header['supplier_id'] ?? 0);
        $branchId = (int)($header['branch_id'] ?? ($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? 1));
        $orderDate = trim($header['order_date'] ?? date('Y-m-d'));
        $expectedDate = trim($header['expected_date'] ?? '') ?: null;
        $discountAmount = (float)($header['discount_amount'] ?? 0);
        $notes = trim($header['notes'] ?? '');
        $userId = (int)($header['user_id'] ?? ($_SESSION['user_id'] ?? 1));

        if ($supplierId <= 0 || empty($items)) {
            return ['success' => false, 'message' => 'Valid supplier and at least one item are required.'];
        }

        // Validate supplier active status
        $supplier = Supplier::find($supplierId);
        if (!$supplier) {
            return ['success' => false, 'message' => 'Selected supplier does not exist.'];
        }
        if ($supplier['status'] !== 'active') {
            return ['success' => false, 'message' => "Supplier '{$supplier['name']}' is inactive. Cannot create PO for inactive supplier."];
        }

        $db = Database::getInstance()->getConnection();

        try {
            $db->beginTransaction();

            $maxRow = Database::fetchOne("SELECT MAX(id) AS max_id FROM purchase_orders");
            $nextId = ((int)($maxRow['max_id'] ?? 0)) + 1;
            $poNumber = 'PO-' . date('Ym') . '-' . sprintf('%04d', $nextId);
            while (Database::fetchOne("SELECT id FROM purchase_orders WHERE po_number = :po", [':po' => $poNumber])) {
                $nextId++;
                $poNumber = 'PO-' . date('Ym') . '-' . sprintf('%04d', $nextId);
            }

            $totalAmount = 0.00;
            $parsedItems = [];

            foreach ($items as $item) {
                $productId = (int)($item['product_id'] ?? 0);
                $qty = (float)($item['quantity'] ?? 0);
                $unitCost = (float)($item['unit_cost'] ?? 0);
                $sellingPrice = (float)($item['selling_price'] ?? 0);

                if ($productId <= 0 || $qty <= 0 || $unitCost < 0) {
                    continue;
                }

                // Check product status
                $prod = Product::find($productId);
                if (!$prod || $prod['status'] !== 'active') {
                    $prodName = $prod ? $prod['name'] : "#$productId";
                    throw new Exception("Product '$prodName' is inactive or invalid. Only active products can be added to PO.");
                }

                $lineTotal = $qty * $unitCost;
                $totalAmount += $lineTotal;

                $parsedItems[] = [
                    'product_id'    => $productId,
                    'quantity'      => $qty,
                    'unit_cost'     => $unitCost,
                    'selling_price' => $sellingPrice,
                    'line_total'    => $lineTotal
                ];
            }

            if (empty($parsedItems)) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Please provide valid active product quantities and unit costs.'];
            }

            $netAmount = max(0, $totalAmount - $discountAmount);

            $stmt = $db->prepare("
                INSERT INTO purchase_orders (po_number, supplier_id, branch_id, order_date, expected_date, total_amount, discount_amount, net_amount, notes, status, created_by)
                VALUES (:po_number, :supplier_id, :branch_id, :order_date, :expected_date, :total_amount, :discount_amount, :net_amount, :notes, 'Draft', :created_by)
            ");

            $stmt->execute([
                ':po_number'       => $poNumber,
                ':supplier_id'     => $supplierId,
                ':branch_id'       => $branchId,
                ':order_date'      => $orderDate,
                ':expected_date'   => $expectedDate,
                ':total_amount'    => $totalAmount,
                ':discount_amount' => $discountAmount,
                ':net_amount'      => $netAmount,
                ':notes'           => $notes,
                ':created_by'      => $userId
            ]);

            $poId = (int)$db->lastInsertId();

            $stmtItem = $db->prepare("
                INSERT INTO purchase_order_items (po_id, product_id, quantity, received_quantity, unit_cost, selling_price, line_total)
                VALUES (:po_id, :product_id, :quantity, 0.00, :unit_cost, :selling_price, :line_total)
            ");

            foreach ($parsedItems as $pi) {
                $stmtItem->execute([
                    ':po_id'         => $poId,
                    ':product_id'    => $pi['product_id'],
                    ':quantity'      => $pi['quantity'],
                    ':unit_cost'     => $pi['unit_cost'],
                    ':selling_price' => $pi['selling_price'],
                    ':line_total'    => $pi['line_total']
                ]);
            }

            $db->commit();

            Audit::log('PO_CREATED', 'purchasing', (string)$poId, null, [
                'po_number' => $poNumber, 'supplier_id' => $supplierId, 'net_amount' => $netAmount
            ]);

            return ['success' => true, 'po_id' => $poId, 'po_number' => $poNumber, 'message' => "Purchase Order $poNumber created successfully."];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to create PO: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Error while creating Purchase Order: ' . $e->getMessage()];
        }
    }

    public static function update(int $id, array $header, array $items): array
    {
        $po = self::find($id);
        if (!$po) {
            return ['success' => false, 'message' => 'Purchase Order not found.'];
        }

        if (in_array($po['status'], ['Cancelled', 'Completed'])) {
            return ['success' => false, 'message' => "Purchase Order {$po['po_number']} is {$po['status']} and cannot be edited."];
        }

        $supplierId = (int)($header['supplier_id'] ?? $po['supplier_id']);
        $orderDate = trim($header['order_date'] ?? $po['order_date']);
        $expectedDate = trim($header['expected_date'] ?? '') ?: null;
        $discountAmount = (float)($header['discount_amount'] ?? $po['discount_amount']);
        $notes = trim($header['notes'] ?? $po['notes']);

        // Calculate received quantity across items
        $totalReceived = 0.00;
        $receivedByProduct = [];
        foreach ($po['items'] as $origItem) {
            $recQty = (float)$origItem['received_quantity'];
            $totalReceived += $recQty;
            $receivedByProduct[$origItem['product_id']] = $recQty;
        }

        // Rule: If receiving has already started, cannot change supplier
        if ($totalReceived > 0 && $supplierId !== (int)$po['supplier_id']) {
            return ['success' => false, 'message' => 'Cannot change supplier after receiving has already started on this PO.'];
        }

        // Validate supplier active status
        $supplier = Supplier::find($supplierId);
        if (!$supplier || $supplier['status'] !== 'active') {
            return ['success' => false, 'message' => 'Selected supplier is inactive or invalid.'];
        }

        $db = Database::getInstance()->getConnection();

        try {
            $db->beginTransaction();

            $totalAmount = 0.00;
            $parsedItems = [];

            foreach ($items as $item) {
                $productId = (int)($item['product_id'] ?? 0);
                $qty = (float)($item['quantity'] ?? 0);
                $unitCost = (float)($item['unit_cost'] ?? 0);
                $sellingPrice = (float)($item['selling_price'] ?? 0);

                if ($productId <= 0 || $qty <= 0 || $unitCost < 0) {
                    continue;
                }

                $alreadyReceived = $receivedByProduct[$productId] ?? 0.00;
                if ($qty < $alreadyReceived) {
                    throw new Exception("Ordered quantity for product #$productId cannot be less than already received quantity ($alreadyReceived).");
                }

                // Check product active status
                $prod = Product::find($productId);
                if (!$prod || $prod['status'] !== 'active') {
                    $prodName = $prod ? $prod['name'] : "#$productId";
                    throw new Exception("Product '$prodName' is inactive. Cannot add to PO.");
                }

                $lineTotal = $qty * $unitCost;
                $totalAmount += $lineTotal;

                $parsedItems[] = [
                    'product_id'        => $productId,
                    'quantity'          => $qty,
                    'received_quantity' => $alreadyReceived,
                    'unit_cost'         => $unitCost,
                    'selling_price'     => $sellingPrice,
                    'line_total'        => $lineTotal
                ];
            }

            // Ensure items with received_quantity > 0 were not deleted
            foreach ($receivedByProduct as $prodId => $recQty) {
                if ($recQty > 0) {
                    $found = false;
                    foreach ($parsedItems as $pi) {
                        if ($pi['product_id'] === $prodId) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        throw new Exception("Cannot remove product #$prodId from PO as quantity ($recQty) has already been received.");
                    }
                }
            }

            if (empty($parsedItems)) {
                $db->rollBack();
                return ['success' => false, 'message' => 'PO must contain at least one valid item.'];
            }

            $netAmount = max(0, $totalAmount - $discountAmount);

            $stmt = $db->prepare("
                UPDATE purchase_orders 
                SET supplier_id = :supplier_id, order_date = :order_date, expected_date = :expected_date, 
                    total_amount = :total_amount, discount_amount = :discount_amount, net_amount = :net_amount, 
                    notes = :notes, updated_at = NOW()
                WHERE id = :id
            ");

            $stmt->execute([
                ':supplier_id'     => $supplierId,
                ':order_date'      => $orderDate,
                ':expected_date'   => $expectedDate,
                ':total_amount'    => $totalAmount,
                ':discount_amount' => $discountAmount,
                ':net_amount'      => $netAmount,
                ':notes'           => $notes,
                ':id'              => $id
            ]);

            // Replace items
            $db->prepare("DELETE FROM purchase_order_items WHERE po_id = :po_id")->execute([':po_id' => $id]);

            $stmtItem = $db->prepare("
                INSERT INTO purchase_order_items (po_id, product_id, quantity, received_quantity, unit_cost, selling_price, line_total)
                VALUES (:po_id, :product_id, :quantity, :received_quantity, :unit_cost, :selling_price, :line_total)
            ");

            foreach ($parsedItems as $pi) {
                $stmtItem->execute([
                    ':po_id'             => $id,
                    ':product_id'        => $pi['product_id'],
                    ':quantity'          => $pi['quantity'],
                    ':received_quantity' => $pi['received_quantity'],
                    ':unit_cost'         => $pi['unit_cost'],
                    ':selling_price'     => $pi['selling_price'],
                    ':line_total'        => $pi['line_total']
                ]);
            }

            $db->commit();

            Audit::log('PO_UPDATED', 'purchasing', (string)$id, $po, [
                'supplier_id' => $supplierId, 'net_amount' => $netAmount
            ]);

            return ['success' => true, 'message' => "Purchase Order {$po['po_number']} updated successfully."];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to update PO #$id: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Error while updating Purchase Order: ' . $e->getMessage()];
        }
    }

    public static function approve(int $id, int $userId): array
    {
        $po = self::find($id);
        if (!$po) {
            return ['success' => false, 'message' => 'Purchase Order not found.'];
        }

        if ($po['status'] !== 'Draft') {
            return ['success' => false, 'message' => 'Only Draft Purchase Orders can be approved.'];
        }

        try {
            Database::execute("
                UPDATE purchase_orders 
                SET status = 'Approved', approved_by = :approved_by, approved_at = NOW(), updated_at = NOW()
                WHERE id = :id
            ", [':approved_by' => $userId, ':id' => $id]);

            Audit::log('PO_APPROVED', 'purchasing', (string)$id, ['status' => 'Draft'], ['status' => 'Approved']);
            return ['success' => true, 'message' => "Purchase Order {$po['po_number']} approved successfully."];
        } catch (Throwable $e) {
            Logger::error("Failed to approve PO #$id: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Database error while approving PO.'];
        }
    }

    public static function cancel(int $id, int $userId): array
    {
        $po = self::find($id);
        if (!$po) {
            return ['success' => false, 'message' => 'Purchase Order not found.'];
        }

        if ($po['status'] === 'Completed') {
            return ['success' => false, 'message' => 'Completed Purchase Orders cannot be cancelled.'];
        }

        // Check if receiving has already started
        $totalReceived = 0.00;
        foreach ($po['items'] as $item) {
            $totalReceived += (float)$item['received_quantity'];
        }

        if ($totalReceived > 0) {
            return ['success' => false, 'message' => "Cannot cancel PO {$po['po_number']} because receiving has already started ($totalReceived items received)."];
        }

        try {
            Database::execute("
                UPDATE purchase_orders 
                SET status = 'Cancelled', updated_at = NOW()
                WHERE id = :id
            ", [':id' => $id]);

            Audit::log('PO_CANCELLED', 'purchasing', (string)$id, ['status' => $po['status']], ['status' => 'Cancelled']);
            return ['success' => true, 'message' => "Purchase Order {$po['po_number']} cancelled."];
        } catch (Throwable $e) {
            Logger::error("Failed to cancel PO #$id: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Database error.'];
        }
    }

    public static function exportList(array $filters = []): void
    {
        $res = self::getList(null, $filters, 1, 10000);
        $items = $res['items'];

        $filename = 'purchase_orders_export_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['PO Number', 'Supplier Code', 'Supplier Name', 'Branch', 'Order Date', 'Expected Date', 'Total Amount', 'Discount', 'Net Amount', 'Status', 'Created By']);

        foreach ($items as $p) {
            fputcsv($output, [
                $p['po_number'],
                $p['supplier_code'] ?? '',
                $p['supplier_name'],
                $p['branch_name'],
                $p['order_date'],
                $p['expected_date'] ?? '',
                number_format($p['total_amount'], 2, '.', ''),
                number_format($p['discount_amount'], 2, '.', ''),
                number_format($p['net_amount'], 2, '.', ''),
                $p['status'],
                $p['creator_name']
            ]);
        }

        fclose($output);
        exit();
    }

    public static function delete(int $id, int $userId): array
    {
        $po = self::find($id);
        if (!$po) {
            return ['success' => false, 'message' => 'Purchase Order not found.'];
        }

        if ($po['status'] === 'Completed') {
            return ['success' => false, 'message' => 'Completed Purchase Orders cannot be moved to trash.'];
        }

        try {
            Database::execute("
                UPDATE purchase_orders 
                SET deleted_at = NOW(), updated_at = NOW() 
                WHERE id = :id
            ", [':id' => $id]);

            Audit::log('PO_DELETED', 'purchasing', (string)$id, $po, ['deleted_at' => date('Y-m-d H:i:s')]);
            return ['success' => true, 'message' => "Purchase Order {$po['po_number']} moved to Trash Bin."];
        } catch (Throwable $e) {
            Logger::error("Failed to soft-delete PO #$id: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Database error while deleting PO.'];
        }
    }

    public static function restore(int $id, int $userId): array
    {
        $po = Database::fetchOne("SELECT * FROM purchase_orders WHERE id = :id", [':id' => $id]);
        if (!$po) {
            return ['success' => false, 'message' => 'Purchase Order not found.'];
        }

        try {
            Database::execute("
                UPDATE purchase_orders 
                SET deleted_at = NULL, updated_at = NOW() 
                WHERE id = :id
            ", [':id' => $id]);

            Audit::log('PO_RESTORED', 'purchasing', (string)$id, $po, ['deleted_at' => null]);
            return ['success' => true, 'message' => "Purchase Order {$po['po_number']} restored successfully."];
        } catch (Throwable $e) {
            Logger::error("Failed to restore PO #$id: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Database error while restoring PO.'];
        }
    }
}
