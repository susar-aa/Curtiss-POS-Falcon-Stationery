<?php
/**
 * Curtiss POS - Product Master & Catalog Core Engine (Enhanced Section 1)
 * Field Sequence: 1. Barcode, 2. Brand, 3. Category, 4. Name, 5. Supplier, 6. Cost, 7. Selling Price, 8. Opening Stock
 * SKU: Unique, stable string/text identifier preserving leading zeros (e.g. '001', '00001', 'ABC001').
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Audit.php';
require_once __DIR__ . '/Brand.php';
require_once __DIR__ . '/Category.php';
require_once __DIR__ . '/Supplier.php';
require_once __DIR__ . '/Inventory.php';

class Product
{
    /**
     * Normalize SKU string without losing leading zeros
     */
    public static function normalizeSku(?string $sku): string
    {
        return trim((string)$sku);
    }

    /**
     * Find product by exact normalized SKU
     */
    public static function findBySku(string $sku): ?array
    {
        $cleanSku = self::normalizeSku($sku);
        if ($cleanSku === '') {
            return null;
        }

        try {
            return Database::fetchOne("
                SELECT p.*, b.name AS brand_name, c.name AS category_name, s.name AS supplier_name
                FROM products p
                LEFT JOIN brands b ON p.brand_id = b.id
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN suppliers s ON p.supplier_id = s.id
                WHERE p.sku = :sku AND p.deleted_at IS NULL
                LIMIT 1
            ", [':sku' => $cleanSku]);
        } catch (Throwable $e) {
            Logger::error("Failed to find product by SKU '$sku': " . $e->getMessage(), $e);
            return null;
        }
    }

    /**
     * Find product by exact Barcode
     */
    public static function findByBarcode(string $barcode): ?array
    {
        $cleanBarcode = trim($barcode);
        if ($cleanBarcode === '') {
            return null;
        }

        try {
            return Database::fetchOne("
                SELECT p.*, b.name AS brand_name, c.name AS category_name, s.name AS supplier_name
                FROM products p
                LEFT JOIN brands b ON p.brand_id = b.id
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN suppliers s ON p.supplier_id = s.id
                WHERE p.barcode = :barcode AND p.deleted_at IS NULL
                LIMIT 1
            ", [':barcode' => $cleanBarcode]);
        } catch (Throwable $e) {
            Logger::error("Failed to find product by barcode '$barcode': " . $e->getMessage(), $e);
            return null;
        }
    }

    /**
     * Fetch products list with filtering, search, sorting, and pagination
     */
    public static function getList(array $filters = [], int $page = 1, int $limit = 15): array
    {
        try {
            $where = ["p.deleted_at IS NULL"];
            $params = [];

            $activeBranchId = (int)($filters['branch_id'] ?? ($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? 1));
            $params[':branch_id'] = $activeBranchId;

            if (!empty($filters['search'])) {
                $rawSearch = trim($filters['search']);
                $searchTerm = '%' . $rawSearch . '%';
                $stripped = preg_replace('/^(rs\.?|lkr)\s*/i', '', $rawSearch);
                $stripped = str_replace([',', ' '], '', $stripped);
                $cleanSearch = '%' . $stripped . '%';
                $where[] = "(
                    p.sku LIKE :s1 OR p.barcode LIKE :s2 OR p.name LIKE :s3 OR
                    b.name LIKE :s4 OR c.name LIKE :s5 OR s.name LIKE :s6 OR
                    p.status LIKE :s7 OR
                    CAST(p.cost AS CHAR) LIKE :s8 OR FORMAT(p.cost, 2) LIKE :s9 OR REPLACE(FORMAT(p.cost, 2), ',', '') LIKE :s10 OR CONCAT('LKR ', FORMAT(p.cost, 2)) LIKE :s11 OR CONCAT('Rs. ', FORMAT(p.cost, 2)) LIKE :s12 OR
                    CAST(p.selling_price AS CHAR) LIKE :s13 OR FORMAT(p.selling_price, 2) LIKE :s14 OR REPLACE(FORMAT(p.selling_price, 2), ',', '') LIKE :s15 OR CONCAT('LKR ', FORMAT(p.selling_price, 2)) LIKE :s16 OR CONCAT('Rs. ', FORMAT(p.selling_price, 2)) LIKE :s17 OR
                    CAST(COALESCE(bi.quantity, 0.00) AS CHAR) LIKE :s18 OR FORMAT(COALESCE(bi.quantity, 0.00), 2) LIKE :s19
                )";
                for ($i = 1; $i <= 7; $i++) {
                    $params[":s{$i}"] = $searchTerm;
                }
                $params[':s8']  = $searchTerm;
                $params[':s9']  = $searchTerm;
                $params[':s10'] = $cleanSearch;
                $params[':s11'] = $searchTerm;
                $params[':s12'] = $searchTerm;
                $params[':s13'] = $searchTerm;
                $params[':s14'] = $searchTerm;
                $params[':s15'] = $cleanSearch;
                $params[':s16'] = $searchTerm;
                $params[':s17'] = $searchTerm;
                $params[':s18'] = $searchTerm;
                $params[':s19'] = $cleanSearch;
            }

            if (!empty($filters['sku'])) {
                $where[] = "p.sku = :exact_sku";
                $params[':exact_sku'] = self::normalizeSku($filters['sku']);
            }

            if (!empty($filters['barcode'])) {
                $where[] = "p.barcode = :exact_barcode";
                $params[':exact_barcode'] = trim($filters['barcode']);
            }

            if (!empty($filters['brand_id']) && $filters['brand_id'] !== 'all') {
                $where[] = "p.brand_id = :brand_id";
                $params[':brand_id'] = (int)$filters['brand_id'];
            }

            if (!empty($filters['category_id']) && $filters['category_id'] !== 'all') {
                $where[] = "p.category_id = :category_id";
                $params[':category_id'] = (int)$filters['category_id'];
            }

            if (!empty($filters['supplier_id']) && $filters['supplier_id'] !== 'all') {
                $where[] = "p.supplier_id = :supplier_id";
                $params[':supplier_id'] = (int)$filters['supplier_id'];
            }

            if (!empty($filters['status']) && $filters['status'] !== 'all') {
                $where[] = "p.status = :status";
                $params[':status'] = $filters['status'];
            }

            $whereSql = implode(" AND ", $where);

            // Count total
            $countRow = Database::fetchOne("
                SELECT COUNT(*) AS total 
                FROM products p 
                LEFT JOIN brands b ON p.brand_id = b.id
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN suppliers s ON p.supplier_id = s.id
                LEFT JOIN branch_inventory bi ON p.id = bi.product_id AND bi.branch_id = :branch_id
                WHERE $whereSql
            ", $params);
            $totalItems = (int)($countRow['total'] ?? 0);

            $limit = max(1, min(10000, $limit)); // Cap limit at 10000 for responsive UI and bulk fetching
            $offset = ($page - 1) * $limit;

            // Sort column validation
            $allowedSorts = [
                'id' => 'p.id',
                'sku' => 'p.sku',
                'barcode' => 'p.barcode',
                'name' => 'p.name',
                'cost' => 'p.cost',
                'selling_price' => 'p.selling_price',
                'status' => 'p.status'
            ];
            $sortCol = $allowedSorts[$filters['sort_by'] ?? 'id'] ?? 'p.id';
            $sortDir = (strtoupper($filters['sort_dir'] ?? 'DESC') === 'ASC') ? 'ASC' : 'DESC';

            $sql = "
                SELECT 
                    p.id,
                    COALESCE(p.sku, p.barcode) AS sku,
                    p.barcode,
                    p.brand_id,
                    COALESCE(b.name, 'Unassigned') AS brand_name,
                    p.category_id,
                    COALESCE(c.name, 'Uncategorized') AS category_name,
                    p.name,
                    p.supplier_id,
                    COALESCE(s.name, 'No Supplier') AS supplier_name,
                    p.cost,
                    p.selling_price,
                    COALESCE(bi.quantity, 0.00) AS opening_stock,
                    COALESCE(bi.low_stock_threshold, p.low_stock_threshold) AS low_stock_threshold,
                    p.has_expiry,
                    p.status,
                    p.created_at,
                    p.updated_at
                FROM products p
                LEFT JOIN brands b ON p.brand_id = b.id
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN suppliers s ON p.supplier_id = s.id
                LEFT JOIN branch_inventory bi ON p.id = bi.product_id AND bi.branch_id = :branch_id
                WHERE $whereSql
                ORDER BY $sortCol $sortDir
                LIMIT $limit OFFSET $offset
            ";

            $items = Database::fetchAll($sql, $params);

            return [
                'items'       => $items,
                'totalItems'  => $totalItems,
                'page'        => $page,
                'limit'       => $limit,
                'totalPages' => max(1, ceil($totalItems / $limit))
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to fetch products list: " . $e->getMessage(), $e);
            return ['items' => [], 'totalItems' => 0, 'page' => 1, 'limit' => $limit, 'totalPages' => 1];
        }
    }

    public static function find(int $id, ?int $branchId = null): ?array
    {
        try {
            $activeBranchId = $branchId ?: (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? 1);
            return Database::fetchOne("
                SELECT p.*, COALESCE(p.sku, p.barcode) AS sku,
                       b.name AS brand_name, c.name AS category_name, s.name AS supplier_name,
                       COALESCE(bi.quantity, 0.00) AS branch_stock
                FROM products p
                LEFT JOIN brands b ON p.brand_id = b.id
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN suppliers s ON p.supplier_id = s.id
                LEFT JOIN branch_inventory bi ON p.id = bi.product_id AND bi.branch_id = :branch_id
                WHERE p.id = :id AND p.deleted_at IS NULL
            ", [':id' => $id, ':branch_id' => $activeBranchId]);
        } catch (Throwable $e) {
            Logger::error("Failed to find product #$id: " . $e->getMessage(), $e);
            return null;
        }
    }

    /**
     * Validate product input fields
     */
    public static function validate(array $data, ?int $ignoreId = null): array
    {
        $errors = [];

        $barcode = trim($data['barcode'] ?? '');
        $sku = self::normalizeSku($data['sku'] ?? ($data['barcode'] ?? ''));
        $brandId = (int)($data['brand_id'] ?? 0);
        $categoryId = (int)($data['category_id'] ?? 0);
        $name = trim($data['name'] ?? '');
        $supplierId = (int)($data['supplier_id'] ?? 0);
        $cost = (float)($data['cost'] ?? 0);
        $sellingPrice = (float)($data['selling_price'] ?? 0);
        $openingStock = (float)($data['opening_stock'] ?? 0);

        if (empty($barcode)) {
            $errors[] = 'Barcode is required.';
        } else {
            $sql = "SELECT id FROM products WHERE barcode = :barcode AND deleted_at IS NULL";
            $params = [':barcode' => $barcode];
            if ($ignoreId) {
                $sql .= " AND id != :ignore_id";
                $params[':ignore_id'] = $ignoreId;
            }
            $existing = Database::fetchOne($sql, $params);
            if ($existing) {
                $errors[] = "Barcode '$barcode' is already assigned to another product.";
            }
        }

        if (!empty($sku)) {
            $sqlSku = "SELECT id FROM products WHERE sku = :sku AND deleted_at IS NULL";
            $paramsSku = [':sku' => $sku];
            if ($ignoreId) {
                $sqlSku .= " AND id != :ignore_id";
                $paramsSku[':ignore_id'] = $ignoreId;
            }
            $existingSku = Database::fetchOne($sqlSku, $paramsSku);
            if ($existingSku) {
                $errors[] = "SKU '$sku' is already assigned to another product.";
            }
        }

        if ($brandId <= 0 || !Brand::find($brandId)) {
            $errors[] = 'Please select a valid Brand.';
        }

        if ($categoryId <= 0 || !Category::find($categoryId)) {
            $errors[] = 'Please select a valid Category.';
        }

        if (empty($name)) {
            $errors[] = 'Product Name is required.';
        }

        if ($supplierId <= 0 || !Supplier::find($supplierId)) {
            $errors[] = 'Please select a valid Supplier.';
        }

        if ($cost < 0) {
            $errors[] = 'Cost cannot be negative.';
        }

        if ($sellingPrice < 0) {
            $errors[] = 'Selling Price cannot be negative.';
        }

        if ($openingStock < 0) {
            $errors[] = 'Opening Stock cannot be negative.';
        }

        $hasExpiry = !empty($data['has_expiry']) ? 1 : 0;
        if ($hasExpiry) {
            if (!empty($data['opening_batches']) && is_array($data['opening_batches'])) {
                foreach ($data['opening_batches'] as $idx => $b) {
                    $bQty = (float)($b['quantity'] ?? 0);
                    $bCost = (float)($b['cost'] ?? 0);
                    $bPrice = (float)($b['selling_price'] ?? 0);
                    $bExp = trim($b['expiry_date'] ?? '');
                    if ($bQty < 0) {
                        $errors[] = "Batch #" . ($idx + 1) . " quantity cannot be negative.";
                    }
                    if ($bCost < 0 || $bPrice < 0) {
                        $errors[] = "Batch #" . ($idx + 1) . " cost and price cannot be negative.";
                    }
                    if ($bQty > 0 && empty($bExp)) {
                        $errors[] = "Batch #" . ($idx + 1) . " requires an Expiry Date.";
                    }
                }
            } elseif ($openingStock > 0 && empty($data['expiry_date'])) {
                $errors[] = 'Expiry Date is required for opening stock when expiry tracking is enabled.';
            }
        }

        return $errors;
    }

    public static function create(array $data): array
    {
        $errors = self::validate($data);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors, 'message' => implode(' ', $errors)];
        }

        try {
            $barcode = trim($data['barcode']);
            $sku = self::normalizeSku($data['sku'] ?? $barcode);
            if (empty($sku)) {
                $sku = $barcode;
            }
            $brandId = (int)$data['brand_id'];
            $categoryId = (int)$data['category_id'];
            $name = trim($data['name']);
            $supplierId = (int)$data['supplier_id'];
            $cost = (float)$data['cost'];
            $sellingPrice = (float)$data['selling_price'];
            $openingStock = (float)($data['opening_stock'] ?? 0);
            $lowStockThreshold = isset($data['low_stock_threshold']) ? (float)$data['low_stock_threshold'] : 10.00;
            $status = ($data['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
            $branchId = (int)($data['branch_id'] ?? ($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? 1));
            $hasExpiry = !empty($data['has_expiry']) ? 1 : 0;
            $openingBatches = (!empty($data['opening_batches']) && is_array($data['opening_batches'])) ? $data['opening_batches'] : null;

            if ($hasExpiry && $openingBatches) {
                $openingStock = array_sum(array_map(fn($b) => max(0, (float)($b['quantity'] ?? 0)), $openingBatches));
            }

            $sql = "
                INSERT INTO products (sku, barcode, brand_id, category_id, name, supplier_id, cost, selling_price, opening_stock, low_stock_threshold, status, has_expiry)
                VALUES (:sku, :barcode, :brand_id, :category_id, :name, :supplier_id, :cost, :selling_price, :opening_stock, :low_stock_threshold, :status, :has_expiry)
            ";

            Database::execute($sql, [
                ':sku'                 => $sku,
                ':barcode'             => $barcode,
                ':brand_id'            => $brandId,
                ':category_id'         => $categoryId,
                ':name'                => $name,
                ':supplier_id'         => $supplierId,
                ':cost'                => $cost,
                ':selling_price'       => $sellingPrice,
                ':opening_stock'       => $openingStock,
                ':low_stock_threshold' => $lowStockThreshold,
                ':status'              => $status,
                ':has_expiry'          => $hasExpiry
            ]);

            $newId = (int)Database::lastInsertId();

            // Create initial Stock Lot and Ledger Movement for active branch
            $totalOpeningVal = 0.00;
            if ($hasExpiry && !empty($openingBatches)) {
                foreach ($openingBatches as $idx => $b) {
                    $bQty = (float)($b['quantity'] ?? 0);
                    if ($bQty <= 0) continue;
                    $bCost = isset($b['cost']) ? (float)$b['cost'] : $cost;
                    $bPrice = isset($b['selling_price']) ? (float)$b['selling_price'] : $sellingPrice;
                    $bExp = !empty($b['expiry_date']) ? trim($b['expiry_date']) : null;
                    $bBatch = !empty($b['batch_number']) ? trim($b['batch_number']) : null;
                    $bLotNo = 'LOT-INIT-' . $newId . '-' . ($idx + 1) . '-' . rand(100, 999);

                    Inventory::addStockLot(
                        $branchId,
                        $newId,
                        $bCost,
                        $bPrice,
                        $bQty,
                        $bLotNo,
                        'INIT-STOCK-BR' . $branchId,
                        'Initial opening stock lot' . ($bBatch ? " (Batch: {$bBatch})" : ''),
                        null,
                        'opening_stock',
                        $bExp,
                        $bBatch,
                        'opening_stock',
                        $newId
                    );
                    $totalOpeningVal += ($bQty * $bCost);
                }
            } elseif ($openingStock >= 0) {
                $expDate = (!empty($data['expiry_date'])) ? trim($data['expiry_date']) : null;
                $batchNo = (!empty($data['batch_number'])) ? trim($data['batch_number']) : null;

                Inventory::addStockLot(
                    $branchId,
                    $newId,
                    $cost,
                    $sellingPrice,
                    $openingStock,
                    null,
                    'INIT-STOCK-BR' . $branchId,
                    'Initial opening stock lot' . ($batchNo ? " (Batch: {$batchNo})" : ''),
                    null,
                    'opening_stock',
                    $expDate,
                    $batchNo,
                    'opening_stock',
                    $newId
                );
                $totalOpeningVal = $openingStock * $cost;
            }

            // Chart of Accounts Double-Entry Journal Posting for Opening Stock
            if ($openingStock > 0 && $totalOpeningVal > 0) {
                try {
                    require_once __DIR__ . '/Accounting.php';
                    $invAccId = Accounting::getAccountIdByCode(Accounting::CODE_INVENTORY_ASSET); // 1200
                    $equityAccId = Accounting::getAccountIdByCode(Accounting::CODE_EQUITY); // 3010

                    $jeHeader = [
                        'branch_id'   => $branchId,
                        'entry_date'  => date('Y-m-d'),
                        'source_type' => 'opening_balance',
                        'source_id'   => $newId,
                        'description' => "Opening Stock for Product: {$name} ({$sku}) - Qty: {$openingStock} @ Val: LKR " . number_format($totalOpeningVal, 2),
                        'created_by'  => (int)($_SESSION['user_id'] ?? 1)
                    ];

                    $jeLines = [
                        [
                            'account_id' => $invAccId,
                            'debit'      => $totalOpeningVal,
                            'credit'     => 0.00,
                            'memo'       => "Opening Stock asset for {$name}"
                        ],
                        [
                            'account_id' => $equityAccId,
                            'debit'      => 0.00,
                            'credit'     => $totalOpeningVal,
                            'memo'       => "Opening Stock equity balance for {$name}"
                        ]
                    ];

                    Accounting::postJournalEntry($jeHeader, $jeLines);
                } catch (Throwable $jeEx) {
                    Logger::error("Failed to post opening stock journal entry for product #{$newId}: " . $jeEx->getMessage(), $jeEx);
                }
            }

            Audit::log('PRODUCT_CREATED', 'products', (string)$newId, null, [
                'sku' => $sku, 'barcode' => $barcode, 'name' => $name, 'cost' => $cost, 'selling_price' => $sellingPrice, 'opening_stock' => $openingStock, 'has_expiry' => $hasExpiry, 'branch_id' => $branchId
            ]);

            return ['success' => true, 'id' => $newId, 'sku' => $sku, 'message' => 'Product created successfully.'];
        } catch (Throwable $e) {
            Logger::error("Failed to create product: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Database error while creating product: ' . $e->getMessage()];
        }
    }

    public static function update(int $id, array $data): array
    {
        $product = self::find($id);
        if (!$product) {
            return ['success' => false, 'message' => 'Product not found.'];
        }

        $errors = self::validate($data, $id);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors, 'message' => implode(' ', $errors)];
        }

        try {
            $barcode = trim($data['barcode']);
            $sku = self::normalizeSku($data['sku'] ?? ($product['sku'] ?: $barcode));
            $brandId = (int)$data['brand_id'];
            $categoryId = (int)$data['category_id'];
            $name = trim($data['name']);
            $supplierId = (int)$data['supplier_id'];
            $cost = (float)$data['cost'];
            $sellingPrice = (float)$data['selling_price'];
            $openingStock = (float)($data['opening_stock'] ?? $product['opening_stock']);
            $lowStockThreshold = isset($data['low_stock_threshold']) ? (float)$data['low_stock_threshold'] : (float)$product['low_stock_threshold'];
            $status = ($data['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
            $hasExpiry = isset($data['has_expiry']) ? (!empty($data['has_expiry']) ? 1 : 0) : (int)($product['has_expiry'] ?? 0);

            $sql = "
                UPDATE products
                SET sku = :sku, barcode = :barcode, brand_id = :brand_id, category_id = :category_id, name = :name,
                    supplier_id = :supplier_id, cost = :cost, selling_price = :selling_price, opening_stock = :opening_stock,
                    low_stock_threshold = :low_stock_threshold, status = :status, has_expiry = :has_expiry, updated_at = NOW()
                WHERE id = :id
            ";

            Database::execute($sql, [
                ':sku'                 => $sku,
                ':barcode'             => $barcode,
                ':brand_id'            => $brandId,
                ':category_id'         => $categoryId,
                ':name'                => $name,
                ':supplier_id'         => $supplierId,
                ':cost'                => $cost,
                ':selling_price'       => $sellingPrice,
                ':opening_stock'       => $openingStock,
                ':low_stock_threshold' => $lowStockThreshold,
                ':status'              => $status,
                ':has_expiry'          => $hasExpiry,
                ':id'                  => $id
            ]);

            Audit::log('PRODUCT_UPDATED', 'products', (string)$id, $product, [
                'sku' => $sku, 'barcode' => $barcode, 'name' => $name, 'cost' => $cost, 'selling_price' => $sellingPrice, 'has_expiry' => $hasExpiry
            ]);

            return ['success' => true, 'message' => 'Product updated successfully.'];
        } catch (Throwable $e) {
            Logger::error("Failed to update product #$id: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Database error while updating product: ' . $e->getMessage()];
        }
    }

    public static function toggleStatus(int $id): array
    {
        $product = self::find($id);
        if (!$product) {
            return ['success' => false, 'message' => 'Product not found.'];
        }

        $newStatus = ($product['status'] === 'active') ? 'inactive' : 'active';
        try {
            Database::execute("UPDATE products SET status = :status WHERE id = :id", [
                ':status' => $newStatus,
                ':id'     => $id
            ]);

            Audit::log('PRODUCT_STATUS_TOGGLED', 'products', (string)$id, ['status' => $product['status']], ['status' => $newStatus]);
            return ['success' => true, 'message' => "Product status updated to $newStatus."];
        } catch (Throwable $e) {
            Logger::error("Failed to toggle product status #$id: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Database error.'];
        }
    }

    /**
     * Check if product can be hard deleted (only permitted if no historical transactions exist)
     */
    public static function canDelete(int $id): array
    {
        try {
            $db = Database::getInstance()->getConnection();

            // Check sales
            $stmt1 = $db->prepare("SELECT COUNT(*) FROM sale_items WHERE product_id = :id");
            $stmt1->execute([':id' => $id]);
            if ((int)$stmt1->fetchColumn() > 0) {
                return ['can_delete' => false, 'reason' => 'Product has historical sales transactions. Deactivate product instead.'];
            }

            // Check GRNs
            $stmt2 = $db->prepare("SELECT COUNT(*) FROM grn_items WHERE product_id = :id");
            $stmt2->execute([':id' => $id]);
            if ((int)$stmt2->fetchColumn() > 0) {
                return ['can_delete' => false, 'reason' => 'Product has historical Goods Received Notes (GRN). Deactivate product instead.'];
            }

            // Check Stock Ledger
            $stmt3 = $db->prepare("SELECT COUNT(*) FROM stock_ledger WHERE product_id = :id");
            $stmt3->execute([':id' => $id]);
            if ((int)$stmt3->fetchColumn() > 0) {
                return ['can_delete' => false, 'reason' => 'Product has stock movement ledger history. Deactivate product instead.'];
            }

            // Check Active Stock Lots
            $stmt4 = $db->prepare("SELECT COUNT(*) FROM product_lots WHERE product_id = :id AND remaining_quantity > 0");
            $stmt4->execute([':id' => $id]);
            if ((int)$stmt4->fetchColumn() > 0) {
                return ['can_delete' => false, 'reason' => 'Product currently has active remaining stock in inventory. Deactivate product instead.'];
            }

            return ['can_delete' => true, 'reason' => 'Product is unused and safe to delete.'];
        } catch (Throwable $e) {
            Logger::error("Failed deletion safety check for product #$id: " . $e->getMessage(), $e);
            return ['can_delete' => false, 'reason' => 'Database check error.'];
        }
    }

    /**
     * Fetch soft-deleted products in Trash Bin
     */
    public static function deleted(): array
    {
        try {
            $activeBranchId = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? 1);
            return Database::fetchAll("
                SELECT p.*, COALESCE(p.sku, p.barcode) AS sku,
                       COALESCE(b.name, 'Unassigned') AS brand_name,
                       COALESCE(c.name, 'Uncategorized') AS category_name,
                       COALESCE(s.name, 'No Supplier') AS supplier_name,
                       COALESCE(bi.quantity, 0.00) AS opening_stock
                FROM products p
                LEFT JOIN brands b ON p.brand_id = b.id
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN suppliers s ON p.supplier_id = s.id
                LEFT JOIN branch_inventory bi ON p.id = bi.product_id AND bi.branch_id = :branch_id
                WHERE p.deleted_at IS NOT NULL
                ORDER BY p.deleted_at DESC
            ", [':branch_id' => $activeBranchId]);
        } catch (Throwable $e) {
            Logger::error("Failed to fetch deleted products: " . $e->getMessage(), $e);
            return [];
        }
    }

    /**
     * Move product to Trash Bin (Soft Delete preserving all transactions, stock ledger & audit logs)
     */
    public static function delete(int $id): array
    {
        $product = self::find($id);
        if (!$product) {
            return ['success' => false, 'message' => 'Product not found.'];
        }

        try {
            Database::execute("UPDATE products SET deleted_at = NOW() WHERE id = :id", [':id' => $id]);

            // Deactivate active stock lots for this product so they are excluded from active stock
            Database::execute("UPDATE product_lots SET status = 'inactive' WHERE product_id = :id AND status = 'active'", [':id' => $id]);

            // Reverse opening stock journal entry if posted so financial statements reflect deleted product
            $openingJe = Database::fetchOne("
                SELECT id FROM journal_entries 
                WHERE source_type = 'opening_balance' AND source_id = :id AND posting_status = 'posted'
            ", [':id' => $id]);
            if ($openingJe) {
                require_once __DIR__ . '/Accounting.php';
                Accounting::reverseJournalEntry((int)$openingJe['id'], "Product #{$id} ({$product['name']}) moved to Trash Bin", (int)($_SESSION['user_id'] ?? 1));
            }

            Audit::log('PRODUCT_DELETED', 'products', (string)$id, $product, ['deleted_at' => date('Y-m-d H:i:s')]);
            return ['success' => true, 'message' => 'Product moved to Trash Bin successfully.'];
        } catch (Throwable $e) {
            Logger::error("Failed to delete product #$id: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Database error while deleting product.'];
        }
    }

    /**
     * Restore soft-deleted product from Trash Bin
     */
    public static function restore(int $id): array
    {
        try {
            $product = Database::fetchOne("SELECT * FROM products WHERE id = :id AND deleted_at IS NOT NULL", [':id' => $id]);
            if (!$product) {
                return ['success' => false, 'message' => 'Deleted product record not found.'];
            }

            if (!empty($product['barcode'])) {
                $existingBc = Database::fetchOne("SELECT id FROM products WHERE barcode = :bc AND deleted_at IS NULL AND id != :id", [':bc' => $product['barcode'], ':id' => $id]);
                if ($existingBc) {
                    return ['success' => false, 'message' => 'Cannot restore: an active product with barcode "' . $product['barcode'] . '" already exists.'];
                }
            }

            Database::execute("UPDATE products SET deleted_at = NULL WHERE id = :id", [':id' => $id]);

            // Reactivate stock lots with remaining quantity
            Database::execute("UPDATE product_lots SET status = 'active' WHERE product_id = :id AND status = 'inactive' AND remaining_quantity > 0", [':id' => $id]);

            // Restore reversed opening stock journal entry if applicable
            $reversedJe = Database::fetchOne("
                SELECT id, reversed_journal_id FROM journal_entries 
                WHERE source_type = 'opening_balance' AND source_id = :id AND posting_status = 'reversed'
            ", [':id' => $id]);
            if ($reversedJe && !empty($reversedJe['reversed_journal_id'])) {
                require_once __DIR__ . '/Accounting.php';
                Accounting::reverseJournalEntry((int)$reversedJe['reversed_journal_id'], "Product #{$id} restored from Trash Bin", (int)($_SESSION['user_id'] ?? 1));
            }

            Audit::log('PRODUCT_RESTORED', 'products', (string)$id, ['deleted_at' => $product['deleted_at']], ['restored_at' => date('Y-m-d H:i:s')]);
            return ['success' => true, 'message' => 'Product restored successfully from Trash Bin.'];
        } catch (Throwable $e) {
            Logger::error("Failed to restore product #$id: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Database error while restoring product.'];
        }
    }

    /**
     * Execute bulk operations on selected products
     */
    public static function bulkAction(array $productIds, string $action, array $extraData = []): array
    {
        $productIds = array_filter(array_map('intval', $productIds));
        if (empty($productIds)) {
            return ['success' => false, 'message' => 'No products selected.'];
        }

        $count = count($productIds);
        $inSql = implode(',', $productIds);

        try {
            switch ($action) {
                case 'activate':
                    Database::execute("UPDATE products SET status = 'active', updated_at = NOW() WHERE id IN ($inSql)");
                    Audit::log('BULK_PRODUCT_ACTIVATED', 'products', null, null, ['count' => $count, 'ids' => $productIds]);
                    return ['success' => true, 'message' => "Successfully activated $count products."];

                case 'deactivate':
                    Database::execute("UPDATE products SET status = 'inactive', updated_at = NOW() WHERE id IN ($inSql)");
                    Audit::log('BULK_PRODUCT_DEACTIVATED', 'products', null, null, ['count' => $count, 'ids' => $productIds]);
                    return ['success' => true, 'message' => "Successfully deactivated $count products."];

                case 'update_category':
                    $catId = (int)($extraData['category_id'] ?? 0);
                    if ($catId <= 0 || !Category::find($catId)) {
                        return ['success' => false, 'message' => 'Invalid category selected.'];
                    }
                    Database::execute("UPDATE products SET category_id = :cat_id, updated_at = NOW() WHERE id IN ($inSql)", [':cat_id' => $catId]);
                    Audit::log('BULK_PRODUCT_CATEGORY_UPDATED', 'products', null, null, ['count' => $count, 'category_id' => $catId]);
                    return ['success' => true, 'message' => "Successfully updated category for $count products."];

                case 'update_brand':
                    $brandId = (int)($extraData['brand_id'] ?? 0);
                    if ($brandId <= 0 || !Brand::find($brandId)) {
                        return ['success' => false, 'message' => 'Invalid brand selected.'];
                    }
                    Database::execute("UPDATE products SET brand_id = :brand_id, updated_at = NOW() WHERE id IN ($inSql)", [':brand_id' => $brandId]);
                    Audit::log('BULK_PRODUCT_BRAND_UPDATED', 'products', null, null, ['count' => $count, 'brand_id' => $brandId]);
                    return ['success' => true, 'message' => "Successfully updated brand for $count products."];

                case 'update_supplier':
                    $supId = (int)($extraData['supplier_id'] ?? 0);
                    if ($supId <= 0 || !Supplier::find($supId)) {
                        return ['success' => false, 'message' => 'Invalid supplier selected.'];
                    }
                    Database::execute("UPDATE products SET supplier_id = :sup_id, updated_at = NOW() WHERE id IN ($inSql)", [':sup_id' => $supId]);
                    Audit::log('BULK_PRODUCT_SUPPLIER_UPDATED', 'products', null, null, ['count' => $count, 'supplier_id' => $supId]);
                    return ['success' => true, 'message' => "Successfully updated supplier for $count products."];

                case 'update_low_stock':
                    $thresh = (float)($extraData['low_stock_threshold'] ?? 10.00);
                    if ($thresh < 0) {
                        return ['success' => false, 'message' => 'Threshold cannot be negative.'];
                    }
                    Database::execute("UPDATE products SET low_stock_threshold = :thresh, updated_at = NOW() WHERE id IN ($inSql)", [':thresh' => $thresh]);
                    Audit::log('BULK_PRODUCT_LOW_STOCK_UPDATED', 'products', null, null, ['count' => $count, 'threshold' => $thresh]);
                    return ['success' => true, 'message' => "Successfully updated low-stock threshold for $count products."];

                default:
                    return ['success' => false, 'message' => 'Unsupported bulk action.'];
            }
        } catch (Throwable $e) {
            Logger::error("Bulk product action '$action' failed: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Database error during bulk action.'];
        }
    }
}
