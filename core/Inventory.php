<?php
/**
 * Curtiss POS - Inventory & Price-Lot Core Component (Stage 3 + Stage 4 Purchasing Support)
 * Manages branch-isolated stock, price/cost lots, and append-only stock ledger movements.
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Audit.php';

class Inventory
{
    /**
     * Get stock level for a product in a specific branch
     */
    public static function getBranchStock(int $branchId, int $productId): array
    {
        try {
            $row = Database::fetchOne("
                SELECT bi.quantity, bi.low_stock_threshold 
                FROM branch_inventory bi
                JOIN products p ON bi.product_id = p.id
                WHERE bi.branch_id = :branch_id AND bi.product_id = :product_id AND p.deleted_at IS NULL
            ", [':branch_id' => $branchId, ':product_id' => $productId]);

            if ($row) {
                return [
                    'quantity'            => (float)$row['quantity'],
                    'low_stock_threshold' => (float)$row['low_stock_threshold'],
                    'is_low_stock'        => (float)$row['quantity'] <= (float)$row['low_stock_threshold']
                ];
            }

            return ['quantity' => 0.00, 'low_stock_threshold' => 10.00, 'is_low_stock' => true];
        } catch (Throwable $e) {
            Logger::error("Failed to fetch branch stock: " . $e->getMessage(), $e);
            return ['quantity' => 0.00, 'low_stock_threshold' => 10.00, 'is_low_stock' => true];
        }
    }

    /**
     * Fetch branch inventory list with filtering and pagination
     */
    public static function getInventoryList(int $branchId, array $filters = [], ?int $page = 1, ?int $limit = 15): array
    {
        $limit = max(1, (int)($limit ?? 15));
        $page = max(1, (int)($page ?? 1));
        try {
            $where = ["p.deleted_at IS NULL"];
            $params = [
                ':branch_id1' => $branchId,
                ':branch_id2' => $branchId
            ];

            if (!empty($filters['search'])) {
                $rawTerm = trim($filters['search']);
                $searchTerm = '%' . $rawTerm . '%';
                $cleanNumeric = str_replace(',', '', $rawTerm);
                $cleanNumericTerm = '%' . $cleanNumeric . '%';

                $where[] = "(
                    p.sku LIKE :s1 OR 
                    p.barcode LIKE :s2 OR 
                    p.name LIKE :s3 OR 
                    COALESCE(b.name, '') LIKE :s4 OR 
                    COALESCE(c.name, '') LIKE :s5 OR 
                    CAST(p.cost AS CHAR) LIKE :s6 OR 
                    FORMAT(p.cost, 2) LIKE :s7 OR 
                    CONCAT('LKR ', FORMAT(p.cost, 2)) LIKE :s8 OR 
                    CONCAT('Rs. ', FORMAT(p.cost, 2)) LIKE :s9 OR 
                    CAST(p.selling_price AS CHAR) LIKE :s10 OR 
                    FORMAT(p.selling_price, 2) LIKE :s11 OR 
                    CONCAT('LKR ', FORMAT(p.selling_price, 2)) LIKE :s12 OR 
                    CONCAT('Rs. ', FORMAT(p.selling_price, 2)) LIKE :s13 OR 
                    CAST(COALESCE(bi.quantity, 0) AS CHAR) LIKE :s14 OR 
                    FORMAT(COALESCE(bi.quantity, 0), 2) LIKE :s15
                )";
                $params[':s1']  = $searchTerm;
                $params[':s2']  = $searchTerm;
                $params[':s3']  = $searchTerm;
                $params[':s4']  = $searchTerm;
                $params[':s5']  = $searchTerm;
                $params[':s6']  = $cleanNumericTerm;
                $params[':s7']  = $searchTerm;
                $params[':s8']  = $searchTerm;
                $params[':s9']  = $searchTerm;
                $params[':s10'] = $cleanNumericTerm;
                $params[':s11'] = $searchTerm;
                $params[':s12'] = $searchTerm;
                $params[':s13'] = $searchTerm;
                $params[':s14'] = $cleanNumericTerm;
                $params[':s15'] = $searchTerm;
            }

            if (!empty($filters['brand_id'])) {
                $where[] = "p.brand_id = :brand_id";
                $params[':brand_id'] = (int)$filters['brand_id'];
            }

            if (!empty($filters['category_id'])) {
                $where[] = "p.category_id = :category_id";
                $params[':category_id'] = (int)$filters['category_id'];
            }

            if (!empty($filters['status'])) {
                $where[] = "p.status = :status";
                $params[':status'] = $filters['status'];
            }

            if (!empty($filters['stock_status'])) {
                if ($filters['stock_status'] === 'out_of_stock') {
                    $where[] = "COALESCE(bi.quantity, 0.00) <= 0";
                } elseif ($filters['stock_status'] === 'low_stock') {
                    $where[] = "COALESCE(bi.quantity, 0.00) > 0 AND COALESCE(bi.quantity, 0.00) <= COALESCE(bi.low_stock_threshold, p.low_stock_threshold)";
                } elseif ($filters['stock_status'] === 'in_stock') {
                    $where[] = "COALESCE(bi.quantity, 0.00) > COALESCE(bi.low_stock_threshold, p.low_stock_threshold)";
                }
            }

            $whereSql = implode(" AND ", $where);

            // Count total (only needs :branch_id1)
            $countParams = array_diff_key($params, [':branch_id2' => 0]);
            $countSql = "
                SELECT COUNT(*) AS total 
                FROM products p
                LEFT JOIN brands b ON p.brand_id = b.id
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN branch_inventory bi ON p.id = bi.product_id AND bi.branch_id = :branch_id1
                WHERE $whereSql
            ";
            $countRow = Database::fetchOne($countSql, $countParams);
            $totalItems = (int)($countRow['total'] ?? 0);

            $offset = ($page - 1) * $limit;

            $sql = "
                SELECT 
                    p.id AS product_id,
                    p.sku,
                    p.barcode,
                    p.name AS product_name,
                    p.status AS product_status,
                    COALESCE(b.name, 'Unassigned') AS brand_name,
                    COALESCE(c.name, 'Uncategorized') AS category_name,
                    p.cost AS default_cost,
                    p.selling_price AS default_price,
                    COALESCE(bi.quantity, 0.00) AS available_quantity,
                    COALESCE(bi.low_stock_threshold, p.low_stock_threshold) AS low_stock_threshold,
                    (SELECT COUNT(*) FROM product_lots pl WHERE pl.product_id = p.id AND pl.branch_id = :branch_id2 AND pl.remaining_quantity > 0 AND pl.status = 'active') AS active_lots_count
                FROM products p
                LEFT JOIN brands b ON p.brand_id = b.id
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN branch_inventory bi ON p.id = bi.product_id AND bi.branch_id = :branch_id1
                WHERE $whereSql
                ORDER BY p.name ASC
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
            Logger::error("Failed to fetch branch inventory list: " . $e->getMessage(), $e);
            return ['items' => [], 'totalItems' => 0, 'page' => 1, 'limit' => $limit, 'totalPages' => 1];
        }
    }

    /**
     * Get branch inventory summary stats (total valuation, sellable stock, low stock, out of stock)
     */
    public static function getInventorySummary(int $branchId): array
    {
        try {
            $sql = "
                SELECT 
                    COUNT(p.id) AS total_products,
                    COALESCE(SUM(bi.quantity), 0.00) AS total_sellable_qty,
                    COALESCE(SUM(bi.quantity * p.cost), 0.00) AS total_cost_valuation,
                    COALESCE(SUM(bi.quantity * p.selling_price), 0.00) AS total_retail_valuation,
                    COALESCE(SUM(CASE WHEN COALESCE(bi.quantity, 0) > 0 AND COALESCE(bi.quantity, 0) <= COALESCE(bi.low_stock_threshold, p.low_stock_threshold) THEN 1 ELSE 0 END), 0) AS low_stock_count,
                    COALESCE(SUM(CASE WHEN COALESCE(bi.quantity, 0) <= 0 THEN 1 ELSE 0 END), 0) AS out_of_stock_count
                FROM products p
                LEFT JOIN branch_inventory bi ON p.id = bi.product_id AND bi.branch_id = :branch_id
                WHERE p.deleted_at IS NULL AND p.status = 'active'
            ";
            $row = Database::fetchOne($sql, [':branch_id' => $branchId]);

            return [
                'total_products'        => (int)($row['total_products'] ?? 0),
                'total_sellable_qty'    => (float)($row['total_sellable_qty'] ?? 0),
                'total_cost_valuation'  => (float)($row['total_cost_valuation'] ?? 0),
                'total_retail_valuation'=> (float)($row['total_retail_valuation'] ?? 0),
                'low_stock_count'       => (int)($row['low_stock_count'] ?? 0),
                'out_of_stock_count'    => (int)($row['out_of_stock_count'] ?? 0)
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to get inventory summary: " . $e->getMessage(), $e);
            return [
                'total_products'        => 0,
                'total_sellable_qty'    => 0,
                'total_cost_valuation'  => 0,
                'total_retail_valuation'=> 0,
                'low_stock_count'       => 0,
                'out_of_stock_count'    => 0
            ];
        }
    }

    /**
     * Add a stock lot and record inventory movement (supports nested transactions)
     */
    public static function addStockLot(
        int $branchId,
        int $productId,
        float $cost,
        float $sellingPrice,
        float $quantity,
        ?string $lotNumber = null,
        ?string $referenceNo = null,
        ?string $notes = null,
        ?int $userId = null,
        string $movementType = 'opening_stock',
        ?string $expiryDate = null,
        ?string $batchNumber = null,
        string $sourceType = 'opening_stock',
        ?int $sourceId = null
    ): array {
        if ($quantity < 0) {
            return ['success' => false, 'message' => 'Stock quantity cannot be negative.'];
        }
        if ($cost < 0 || $sellingPrice < 0) {
            return ['success' => false, 'message' => 'Cost and selling price cannot be negative.'];
        }

        $db = Database::getInstance()->getConnection();
        $isNested = $db->inTransaction();

        try {
            if (!$isNested) {
                $db->beginTransaction();
            }

            $userId = $userId ?? ($_SESSION['user_id'] ?? null);

            // Normalize empty strings to null for batch & expiry
            $expiryDate = !empty($expiryDate) ? trim($expiryDate) : null;
            $batchNumber = !empty($batchNumber) ? trim($batchNumber) : null;

            // 1. Check for existing active lot with identical cost, selling price, and matching expiry date & batch
            $lotCheckSql = "
                SELECT id, lot_number, initial_quantity, remaining_quantity 
                FROM product_lots 
                WHERE branch_id = :branch_id 
                  AND product_id = :product_id 
                  AND ABS(cost - :cost) < 0.001 
                  AND ABS(selling_price - :selling_price) < 0.001 
                  AND status = 'active'
            ";
            $lotCheckParams = [
                ':branch_id'     => $branchId,
                ':product_id'    => $productId,
                ':cost'          => $cost,
                ':selling_price' => $sellingPrice
            ];

            if ($expiryDate !== null) {
                $lotCheckSql .= " AND expiry_date = :expiry_date";
                $lotCheckParams[':expiry_date'] = $expiryDate;
            } else {
                $lotCheckSql .= " AND expiry_date IS NULL";
            }

            if ($batchNumber !== null) {
                $lotCheckSql .= " AND batch_number = :batch_number";
                $lotCheckParams[':batch_number'] = $batchNumber;
            } else {
                $lotCheckSql .= " AND (batch_number IS NULL OR batch_number = '')";
            }

            $lotCheckSql .= " LIMIT 1";

            $existingLot = Database::fetchOne($lotCheckSql, $lotCheckParams);

            if ($existingLot) {
                // Reuse existing lot: increment initial & remaining quantities
                $lotId = (int)$existingLot['id'];
                $stmt = $db->prepare("
                    UPDATE product_lots 
                    SET initial_quantity = initial_quantity + :qty1, 
                        remaining_quantity = remaining_quantity + :qty2, 
                        status = 'active',
                        updated_at = NOW() 
                    WHERE id = :id
                ");
                $stmt->execute([':qty1' => $quantity, ':qty2' => $quantity, ':id' => $lotId]);
            } else {
                // Create new lot for distinct price/cost/expiry/batch combination
                $lotNumber = $lotNumber ?: 'LOT-' . date('Ymd') . '-' . rand(1000, 9999);
                $status = $quantity > 0 ? 'active' : 'depleted';

                $stmt = $db->prepare("
                    INSERT INTO product_lots (
                        lot_number, product_id, branch_id, cost, selling_price, 
                        initial_quantity, remaining_quantity, status, 
                        expiry_date, batch_number, source_type, source_id
                    ) VALUES (
                        :lot_number, :product_id, :branch_id, :cost, :selling_price, 
                        :initial_quantity, :remaining_quantity, :status, 
                        :expiry_date, :batch_number, :source_type, :source_id
                    )
                ");

                $stmt->execute([
                    ':lot_number'         => $lotNumber,
                    ':product_id'         => $productId,
                    ':branch_id'          => $branchId,
                    ':cost'               => $cost,
                    ':selling_price'      => $sellingPrice,
                    ':initial_quantity'   => $quantity,
                    ':remaining_quantity' => $quantity,
                    ':status'             => $status,
                    ':expiry_date'        => $expiryDate,
                    ':batch_number'       => $batchNumber,
                    ':source_type'        => $sourceType,
                    ':source_id'          => $sourceId
                ]);

                $lotId = (int)$db->lastInsertId();
            }

            // 2. Update or Insert Branch Inventory
            $invRow = Database::fetchOne("
                SELECT quantity, low_stock_threshold FROM branch_inventory 
                WHERE branch_id = :branch_id AND product_id = :product_id
            ", [':branch_id' => $branchId, ':product_id' => $productId]);

            $currentQty = $invRow ? (float)$invRow['quantity'] : 0.00;
            $newBalance = $currentQty + $quantity;

            if ($invRow) {
                $stmt = $db->prepare("
                    UPDATE branch_inventory 
                    SET quantity = :quantity, updated_at = NOW() 
                    WHERE branch_id = :branch_id AND product_id = :product_id
                ");
                $stmt->execute([':quantity' => $newBalance, ':branch_id' => $branchId, ':product_id' => $productId]);
            } else {
                $stmt = $db->prepare("
                    INSERT INTO branch_inventory (branch_id, product_id, quantity, low_stock_threshold)
                    VALUES (:branch_id, :product_id, :quantity, 10.00)
                ");
                $stmt->execute([':branch_id' => $branchId, ':product_id' => $productId, ':quantity' => $newBalance]);
            }

            // 3. Append to Stock Ledger (Append-Only)
            $stmt = $db->prepare("
                INSERT INTO stock_ledger (branch_id, product_id, lot_id, movement_type, quantity, balance_after, reference_no, notes, user_id)
                VALUES (:branch_id, :product_id, :lot_id, :movement_type, :quantity, :balance_after, :reference_no, :notes, :user_id)
            ");

            $stmt->execute([
                ':branch_id'     => $branchId,
                ':product_id'    => $productId,
                ':lot_id'        => $lotId,
                ':movement_type' => $movementType,
                ':quantity'      => $quantity,
                ':balance_after' => $newBalance,
                ':reference_no'  => $referenceNo ?: 'INIT-STOCK-BR' . $branchId,
                ':notes'         => $notes ?: 'Stock lot created',
                ':user_id'       => $userId
            ]);

            if (!$isNested) {
                $db->commit();
            }

            Audit::log('INVENTORY_LOT_CREATED', 'inventory', (string)$lotId, null, [
                'branch_id' => $branchId, 'product_id' => $productId, 'lot_number' => $lotNumber, 'quantity' => $quantity, 'movement_type' => $movementType
            ]);

            return ['success' => true, 'lot_id' => $lotId, 'message' => 'Stock lot and ledger movement created successfully.'];

        } catch (Throwable $e) {
            if (!$isNested && $db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to add stock lot: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Database error while creating stock lot.'];
        }
    }

    /**
     * Fetch price & cost lots for a product
     */
    public static function getLots(?int $productId = null, ?int $branchId = null, bool $activeOnly = false): array
    {
        try {
            if ($productId && $branchId) {
                Database::execute("UPDATE product_lots SET status = 'depleted' WHERE product_id = :p AND branch_id = :b AND remaining_quantity <= 0 AND status = 'active'", [':p' => $productId, ':b' => $branchId]);
            } elseif ($productId) {
                Database::execute("UPDATE product_lots SET status = 'depleted' WHERE product_id = :p AND remaining_quantity <= 0 AND status = 'active'", [':p' => $productId]);
            } elseif ($branchId) {
                Database::execute("UPDATE product_lots SET status = 'depleted' WHERE branch_id = :b AND remaining_quantity <= 0 AND status = 'active'", [':b' => $branchId]);
            } else {
                Database::execute("UPDATE product_lots SET status = 'depleted' WHERE remaining_quantity <= 0 AND status = 'active'");
            }

            $where = ["p.deleted_at IS NULL"];
            $params = [];

            if ($productId) {
                $where[] = "pl.product_id = :product_id";
                $params[':product_id'] = $productId;
            }

            if ($branchId) {
                $where[] = "pl.branch_id = :branch_id";
                $params[':branch_id'] = $branchId;
            }

            if ($activeOnly) {
                $where[] = "pl.status = 'active' AND pl.remaining_quantity > 0";
            }

            $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

            return Database::fetchAll("
                SELECT 
                    pl.*, 
                    p.name AS product_name, 
                    p.barcode, 
                    COALESCE(p.sku, p.barcode) AS sku,
                    br.name AS brand_name,
                    cat.name AS category_name,
                    b.name AS branch_name
                FROM product_lots pl
                JOIN products p ON pl.product_id = p.id
                LEFT JOIN brands br ON p.brand_id = br.id
                LEFT JOIN categories cat ON p.category_id = cat.id
                JOIN branches b ON pl.branch_id = b.id
                $whereSql
                ORDER BY pl.id DESC
            ", $params);
        } catch (Throwable $e) {
            Logger::error("Failed to fetch product lots: " . $e->getMessage(), $e);
            return [];
        }
    }

    /**
     * Fetch Stock Movement Ledger History (Append-Only)
     */
    public static function getLedgerList(?int $branchId = null, array $filters = [], int $page = 1, int $limit = 15): array
    {
        try {
            $where = ["p.deleted_at IS NULL"];
            $params = [];

            if ($branchId) {
                $where[] = "sl.branch_id = :branch_id";
                $params[':branch_id'] = $branchId;
            }

            if (!empty($filters['product_id'])) {
                $where[] = "sl.product_id = :product_id";
                $params[':product_id'] = (int)$filters['product_id'];
            }

            if (!empty($filters['movement_type'])) {
                $where[] = "sl.movement_type = :movement_type";
                $params[':movement_type'] = $filters['movement_type'];
            }

            if (!empty($filters['search'])) {
                $rawTerm = trim($filters['search']);
                $searchTerm = '%' . $rawTerm . '%';
                $cleanNum = preg_replace('/[^0-9.]/', '', $rawTerm);

                $searchParts = [
                    "p.name LIKE :s1",
                    "p.barcode LIKE :s2",
                    "COALESCE(p.sku, '') LIKE :s3",
                    "COALESCE(sl.reference_no, '') LIKE :s4",
                    "sl.movement_type LIKE :s5",
                    "REPLACE(sl.movement_type, '_', ' ') LIKE :s6",
                    "COALESCE(pl.lot_number, '') LIKE :s7",
                    "COALESCE(u.username, '') LIKE :s8",
                    "COALESCE(sl.notes, '') LIKE :s9",
                    "CAST(sl.quantity AS CHAR) LIKE :s10",
                    "CAST(sl.balance_after AS CHAR) LIKE :s11"
                ];

                for ($i = 1; $i <= 11; $i++) {
                    $params[":s{$i}"] = $searchTerm;
                }

                if ($cleanNum !== '' && is_numeric($cleanNum)) {
                    $searchParts[] = "ABS(sl.quantity - :sNum1) < 0.01";
                    $searchParts[] = "ABS(sl.balance_after - :sNum2) < 0.01";
                    $searchParts[] = "ABS(COALESCE(pl.cost, 0) - :sNum3) < 0.01";
                    $params[':sNum1'] = (float)$cleanNum;
                    $params[':sNum2'] = (float)$cleanNum;
                    $params[':sNum3'] = (float)$cleanNum;
                }

                $where[] = "(" . implode(" OR ", $searchParts) . ")";
            }

            $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

            $countRow = Database::fetchOne("
                SELECT COUNT(*) AS total 
                FROM stock_ledger sl 
                JOIN products p ON sl.product_id = p.id 
                LEFT JOIN product_lots pl ON sl.lot_id = pl.id
                LEFT JOIN users u ON sl.user_id = u.id
                $whereSql
            ", $params);

            $totalItems = (int)($countRow['total'] ?? 0);
            $offset = (int)(($page - 1) * $limit);

            $sql = "
                SELECT 
                    sl.*,
                    p.name AS product_name,
                    p.barcode,
                    COALESCE(p.sku, p.barcode) AS sku,
                    b.name AS branch_name,
                    pl.lot_number,
                    pl.cost AS unit_cost,
                    pl.selling_price,
                    u.username AS username
                FROM stock_ledger sl
                JOIN products p ON sl.product_id = p.id
                JOIN branches b ON sl.branch_id = b.id
                LEFT JOIN product_lots pl ON sl.lot_id = pl.id
                LEFT JOIN users u ON sl.user_id = u.id
                $whereSql
                ORDER BY sl.id DESC
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset . "
            ";

            $items = Database::fetchAll($sql, $params);

            return [
                'items'      => $items,
                'totalItems' => $totalItems,
                'page'       => $page,
                'limit'      => $limit,
                'totalPages' => max(1, (int)ceil($totalItems / $limit))
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to fetch stock ledger list: " . $e->getMessage(), $e);
            return ['items' => [], 'totalItems' => 0, 'page' => 1, 'limit' => $limit, 'totalPages' => 1];
        }
    }
}
