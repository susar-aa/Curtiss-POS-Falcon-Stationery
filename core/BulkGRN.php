<?php
/**
 * Curtiss POS - Bulk Goods Received Note (Bulk GRN) Processor
 * Handles supplier-based bulk GRN entry, on-the-fly new product creation,
 * price-lot reuse/creation, stock ledger updates, and accounting integration.
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Audit.php';
require_once __DIR__ . '/Inventory.php';
require_once __DIR__ . '/Supplier.php';
require_once __DIR__ . '/Product.php';
require_once __DIR__ . '/GRN.php';
require_once __DIR__ . '/Accounting.php';

class BulkGRN
{
    /**
     * Fetch all active products assigned to a supplier with current branch stock and available price lots
     */
    public static function getSupplierProductsWithLots(int $supplierId, int $branchId): array
    {
        try {
            $sql = "
                SELECT DISTINCT
                    p.id,
                    p.barcode,
                    p.sku,
                    p.name,
                    p.brand_id,
                    COALESCE(b.name, 'Unassigned') AS brand_name,
                    p.category_id,
                    COALESCE(c.name, 'Uncategorized') AS category_name,
                    p.supplier_id,
                    COALESCE(s.name, 'Unassigned') AS supplier_name,
                    p.cost AS default_cost,
                    p.selling_price AS default_selling_price,
                    p.has_expiry,
                    COALESCE(bi.quantity, 0.00) AS current_stock
                FROM products p
                LEFT JOIN product_suppliers ps ON p.id = ps.product_id
                LEFT JOIN brands b ON p.brand_id = b.id
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN suppliers s ON p.supplier_id = s.id
                LEFT JOIN branch_inventory bi ON p.id = bi.product_id AND bi.branch_id = :branch_id1
                WHERE (p.supplier_id = :supplier_id1 OR ps.supplier_id = :supplier_id2)
                  AND p.deleted_at IS NULL
                  AND p.status = 'active'
                ORDER BY p.name ASC
            ";

            $products = Database::fetchAll($sql, [
                ':supplier_id1' => $supplierId,
                ':supplier_id2' => $supplierId,
                ':branch_id1'   => $branchId
            ]);

            if (empty($products)) {
                return [];
            }

            $productIds = array_column($products, 'id');
            $inSql = implode(',', array_map('intval', $productIds));

            // Fetch active price lots for these products in the active branch
            $lotsSql = "
                SELECT 
                    id,
                    product_id,
                    lot_number,
                    cost,
                    selling_price,
                    remaining_quantity,
                    expiry_date,
                    batch_number
                FROM product_lots
                WHERE branch_id = :branch_id
                  AND product_id IN ($inSql)
                  AND status = 'active'
                ORDER BY id DESC
            ";

            $lots = Database::fetchAll($lotsSql, [':branch_id' => $branchId]);

            // Group lots by product_id
            $lotsByProduct = [];
            foreach ($lots as $lot) {
                $pid = (int)$lot['product_id'];
                if (!isset($lotsByProduct[$pid])) {
                    $lotsByProduct[$pid] = [];
                }
                $lotsByProduct[$pid][] = [
                    'id'                 => (int)$lot['id'],
                    'cost'               => (float)$lot['cost'],
                    'selling_price'      => (float)$lot['selling_price'],
                    'remaining_quantity' => (float)$lot['remaining_quantity'],
                    'expiry_date'        => $lot['expiry_date'] ?? null,
                    'batch_number'       => $lot['batch_number'] ?? null,
                    'display'            => 'Cost Rs. ' . number_format((float)$lot['cost'], 2) . ' / Selling Rs. ' . number_format((float)$lot['selling_price'], 2) . ' (Stock: ' . number_format((float)$lot['remaining_quantity'], 0) . ')'
                ];
            }

            // Merge into product array
            foreach ($products as &$p) {
                $pid = (int)$p['id'];
                $prodLots = $lotsByProduct[$pid] ?? [];
                $p['lots'] = $prodLots;

                // Requirement: Show the cost and selling price from the latest price lot by default.
                // If lots exist, default to the latest lot's cost and selling price; otherwise product default.
                if (!empty($prodLots)) {
                    $latestLot = $prodLots[0];
                    $p['cost'] = (float)$latestLot['cost'];
                    $p['selling_price'] = (float)$latestLot['selling_price'];
                    $p['latest_expiry_date'] = $latestLot['expiry_date'];
                    $p['latest_batch_number'] = $latestLot['batch_number'];
                } else {
                    $p['cost'] = (float)$p['default_cost'];
                    $p['selling_price'] = (float)$p['default_selling_price'];
                    $p['latest_expiry_date'] = null;
                    $p['latest_batch_number'] = null;
                }
            }
            unset($p);

            return $products;

        } catch (Throwable $e) {
            Logger::error("Failed to fetch supplier products with lots: " . $e->getMessage(), $e);
            return [];
        }
    }

    /**
     * Process and save Bulk GRN atomically.
     * Creates new products, validates existing items, creates GRN records,
     * and optionally approves & posts directly to inventory.
     */
    public static function process(array $header, array $existingItems, array $newProducts = [], bool $approveImmediately = false): array
    {
        $supplierId = (int)($header['supplier_id'] ?? 0);
        $branchId = (int)($header['branch_id'] ?? ($_SESSION['active_branch_id'] ?? 1));
        $supplierInvoiceNo = trim($header['supplier_invoice_no'] ?? '');
        $supplierInvoiceDate = trim($header['supplier_invoice_date'] ?? '') ?: null;
        $paymentDueDate = trim($header['payment_due_date'] ?? '') ?: null;
        $receivedDate = trim($header['received_date'] ?? date('Y-m-d'));
        $supplierInvoiceAmount = (float)($header['supplier_invoice_amount'] ?? 0);
        $differenceReason = trim($header['difference_reason'] ?? 'None');
        $notes = trim($header['notes'] ?? '');
        $userId = (int)($header['user_id'] ?? ($_SESSION['user_id'] ?? 1));

        if ($supplierId <= 0) {
            return ['success' => false, 'message' => 'Please select a valid supplier.'];
        }

        if (empty($supplierInvoiceNo)) {
            return ['success' => false, 'message' => 'Supplier Invoice Number is required.'];
        }

        $supplier = Supplier::find($supplierId);
        if (!$supplier || $supplier['status'] !== 'active') {
            return ['success' => false, 'message' => 'Selected supplier is inactive or invalid.'];
        }

        $db = Database::getInstance()->getConnection();

        try {
            $db->beginTransaction();

            $grnItemsToInsert = [];
            $actualGrnTotal = 0.00;

            // ── STEP 1: Process and Create New Products On-the-Fly ──
            foreach ($newProducts as $np) {
                $name = trim($np['name'] ?? '');
                $qty = (float)($np['quantity'] ?? 0);
                $cost = (float)($np['unit_cost'] ?? 0);
                $sellingPrice = (float)($np['selling_price'] ?? 0);
                $brandId = (int)($np['brand_id'] ?? 0);
                $categoryId = (int)($np['category_id'] ?? 0);
                $barcode = trim($np['barcode'] ?? '');
                $hasExpiry = !empty($np['has_expiry']) ? 1 : 0;
                $expiryDate = !empty($np['expiry_date']) ? trim($np['expiry_date']) : null;
                $batchNumber = !empty($np['batch_number']) ? trim($np['batch_number']) : null;

                // Skip blank or 0-quantity rows
                if (empty($name) && $qty <= 0) {
                    continue;
                }

                if (empty($name)) {
                    throw new Exception("Product Name is required for newly added product rows.");
                }

                if ($qty <= 0) {
                    throw new Exception("Received Quantity must be greater than 0 for new product '$name'.");
                }

                if ($cost < 0 || $sellingPrice < 0) {
                    throw new Exception("Cost and Selling Price cannot be negative for new product '$name'.");
                }

                if ($hasExpiry && empty($expiryDate)) {
                    throw new Exception("Expiry Date is required for expiry-enabled new product '$name'.");
                }

                // Auto-generate barcode if blank
                if (empty($barcode)) {
                    $barcode = 'B' . date('ymd') . rand(1000, 9999);
                    while (Database::fetchOne("SELECT id FROM products WHERE barcode = :b", [':b' => $barcode])) {
                        $barcode = 'B' . date('ymd') . rand(1000, 9999);
                    }
                } else {
                    $existingBarcode = Database::fetchOne("SELECT id, name FROM products WHERE barcode = :b AND deleted_at IS NULL", [':b' => $barcode]);
                    if ($existingBarcode) {
                        throw new Exception("Barcode '$barcode' is already in use by product '{$existingBarcode['name']}'.");
                    }
                }

                // Fallback brand / category if missing
                if ($brandId <= 0) {
                    $defaultBrand = Database::fetchOne("SELECT id FROM brands WHERE deleted_at IS NULL AND status = 'active' ORDER BY id ASC LIMIT 1");
                    $brandId = (int)($defaultBrand['id'] ?? 1);
                }
                if ($categoryId <= 0) {
                    $defaultCat = Database::fetchOne("SELECT id FROM categories WHERE deleted_at IS NULL AND status = 'active' ORDER BY id ASC LIMIT 1");
                    $categoryId = (int)($defaultCat['id'] ?? 1);
                }

                $sku = $barcode;

                // Insert into products table
                $stmtNewProd = $db->prepare("
                    INSERT INTO products (sku, barcode, brand_id, category_id, name, supplier_id, cost, selling_price, opening_stock, low_stock_threshold, status, has_expiry)
                    VALUES (:sku, :barcode, :brand_id, :category_id, :name, :supplier_id, :cost, :selling_price, 0.00, 10.00, 'active', :has_expiry)
                ");
                $stmtNewProd->execute([
                    ':sku'          => $sku,
                    ':barcode'      => $barcode,
                    ':brand_id'     => $brandId,
                    ':category_id'  => $categoryId,
                    ':name'         => $name,
                    ':supplier_id'  => $supplierId,
                    ':cost'         => $cost,
                    ':selling_price'=> $sellingPrice,
                    ':has_expiry'   => $hasExpiry
                ]);

                $newProdId = (int)$db->lastInsertId();

                // Link supplier to product_suppliers table
                $stmtPS = $db->prepare("
                    INSERT INTO product_suppliers (product_id, supplier_id, is_primary)
                    VALUES (:pid, :sid, 1)
                    ON DUPLICATE KEY UPDATE is_primary = 1
                ");
                $stmtPS->execute([':pid' => $newProdId, ':sid' => $supplierId]);

                Audit::log('PRODUCT_CREATED_BULK_GRN', 'products', (string)$newProdId, null, [
                    'name' => $name, 'barcode' => $barcode, 'supplier_id' => $supplierId
                ]);

                $lineTotal = $qty * $cost;
                $actualGrnTotal += $lineTotal;

                $grnItemsToInsert[] = [
                    'product_id'    => $newProdId,
                    'quantity'      => $qty,
                    'unit_cost'     => $cost,
                    'selling_price' => $sellingPrice,
                    'line_total'    => $lineTotal,
                    'expiry_date'   => $expiryDate,
                    'batch_number'  => $batchNumber
                ];
            }

            // ── STEP 2: Process Existing Products with Entered Quantities ──
            foreach ($existingItems as $item) {
                $productId = (int)($item['product_id'] ?? 0);

                // Ensure supplier is linked in product_suppliers table
                $stmtLink = $db->prepare("
                    INSERT IGNORE INTO product_suppliers (product_id, supplier_id, is_primary)
                    VALUES (:pid, :sid, 0)
                ");
                $stmtLink->execute([':pid' => $productId, ':sid' => $supplierId]);
                $qty = (float)($item['quantity'] ?? 0);
                $cost = (float)($item['unit_cost'] ?? 0);
                $sellingPrice = (float)($item['selling_price'] ?? 0);
                $expiryDate = !empty($item['expiry_date']) ? trim($item['expiry_date']) : null;
                $batchNumber = !empty($item['batch_number']) ? trim($item['batch_number']) : null;
                $isExcluded = !empty($item['is_hidden']) || !empty($item['is_excluded']);

                // Requirement: Only products with a valid quantity greater than zero should be included in the GRN.
                // Empty or 0 quantity means the product is excluded, regardless of changes made to other fields.
                if ($isExcluded || $productId <= 0 || $qty <= 0) {
                    continue;
                }

                if ($cost < 0 || $sellingPrice < 0) {
                    throw new Exception("Cost and Selling Price cannot be negative for product #$productId.");
                }

                // Check product validity
                $prod = Database::fetchOne("SELECT id, name, status, has_expiry FROM products WHERE id = :id", [':id' => $productId]);
                if (!$prod || $prod['status'] !== 'active') {
                    $pName = $prod['name'] ?? "#$productId";
                    throw new Exception("Product '$pName' is inactive or does not exist.");
                }

                if (!empty($prod['has_expiry']) && empty($expiryDate)) {
                    throw new Exception("Expiry Date is required for expiry-tracked product '{$prod['name']}'.");
                }

                $lineTotal = $qty * $cost;
                $actualGrnTotal += $lineTotal;

                $grnItemsToInsert[] = [
                    'product_id'    => $productId,
                    'quantity'      => $qty,
                    'unit_cost'     => $cost,
                    'selling_price' => $sellingPrice,
                    'line_total'    => $lineTotal,
                    'expiry_date'   => $expiryDate,
                    'batch_number'  => $batchNumber
                ];
            }

            if (empty($grnItemsToInsert)) {
                $db->rollBack();
                return ['success' => false, 'message' => 'No products with valid received quantities entered. Please enter a quantity > 0 for at least one product.'];
            }

            // ── STEP 3: Generate GRN Number ──
            $maxRow = Database::fetchOne("SELECT MAX(id) AS max_id FROM grns");
            $nextId = ((int)($maxRow['max_id'] ?? 0)) + 1;
            $grnNumber = 'GRN-' . date('Ym') . '-' . sprintf('%04d', $nextId);
            while (Database::fetchOne("SELECT id FROM grns WHERE grn_number = :g", [':g' => $grnNumber])) {
                $nextId++;
                $grnNumber = 'GRN-' . date('Ym') . '-' . sprintf('%04d', $nextId);
            }

            $differenceAmount = $supplierInvoiceAmount - $actualGrnTotal;

            // ── STEP 4: Insert GRN Header ──
            $stmtGrn = $db->prepare("
                INSERT INTO grns (
                    grn_number, po_id, supplier_id, branch_id, supplier_invoice_no, 
                    supplier_invoice_date, payment_due_date, received_date, actual_grn_total, 
                    supplier_invoice_amount, difference_amount, difference_reason, notes, 
                    status, created_by
                ) VALUES (
                    :grn_number, NULL, :supplier_id, :branch_id, :supplier_invoice_no, 
                    :supplier_invoice_date, :payment_due_date, :received_date, :actual_grn_total, 
                    :supplier_invoice_amount, :difference_amount, :difference_reason, :notes, 
                    'Draft', :created_by
                )
            ");

            $stmtGrn->execute([
                ':grn_number'              => $grnNumber,
                ':supplier_id'             => $supplierId,
                ':branch_id'               => $branchId,
                ':supplier_invoice_no'     => $supplierInvoiceNo,
                ':supplier_invoice_date'   => $supplierInvoiceDate,
                ':payment_due_date'        => $paymentDueDate,
                ':received_date'           => $receivedDate,
                ':actual_grn_total'        => $actualGrnTotal,
                ':supplier_invoice_amount' => $supplierInvoiceAmount,
                ':difference_amount'       => $differenceAmount,
                ':difference_reason'       => $differenceReason,
                ':notes'                   => $notes,
                ':created_by'              => $userId
            ]);

            $grnId = (int)$db->lastInsertId();

            // ── STEP 5: Insert GRN Items ──
            $stmtItem = $db->prepare("
                INSERT INTO grn_items (
                    grn_id, product_id, quantity, unit_cost, selling_price, 
                    line_total, expiry_date, batch_number
                ) VALUES (
                    :grn_id, :product_id, :quantity, :unit_cost, :selling_price, 
                    :line_total, :expiry_date, :batch_number
                )
            ");

            foreach ($grnItemsToInsert as $gi) {
                $stmtItem->execute([
                    ':grn_id'        => $grnId,
                    ':product_id'    => $gi['product_id'],
                    ':quantity'      => $gi['quantity'],
                    ':unit_cost'     => $gi['unit_cost'],
                    ':selling_price' => $gi['selling_price'],
                    ':line_total'    => $gi['line_total'],
                    ':expiry_date'   => $gi['expiry_date'],
                    ':batch_number'  => $gi['batch_number']
                ]);
            }

            Audit::log('BULK_GRN_CREATED', 'purchasing', (string)$grnId, null, [
                'grn_number'              => $grnNumber,
                'supplier_invoice_no'     => $supplierInvoiceNo,
                'item_count'              => count($grnItemsToInsert),
                'actual_grn_total'        => $actualGrnTotal,
                'supplier_invoice_amount' => $supplierInvoiceAmount
            ]);

            $db->commit();

            // ── STEP 6: Optional Immediate Approval / Posting ──
            if ($approveImmediately) {
                $appRes = GRN::approve($grnId, $userId);
                if (!$appRes['success']) {
                    return [
                        'success'    => true,
                        'grn_id'     => $grnId,
                        'grn_number' => $grnNumber,
                        'warning'    => "GRN $grnNumber saved as Draft, but automatic posting failed: " . $appRes['message']
                    ];
                }
                return [
                    'success'    => true,
                    'grn_id'     => $grnId,
                    'grn_number' => $grnNumber,
                    'status'     => 'Approved',
                    'message'    => "Bulk Goods Received Note $grnNumber saved and posted to stock successfully."
                ];
            }

            return [
                'success'    => true,
                'grn_id'     => $grnId,
                'grn_number' => $grnNumber,
                'status'     => 'Draft',
                'message'    => "Bulk Goods Received Note $grnNumber saved successfully (Draft)."
            ];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to process Bulk GRN: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Error processing Bulk GRN: ' . $e->getMessage()];
        }
    }
}
