<?php
/**
 * Curtiss POS - Goods Received Notes (GRN) Core Component
 * Handles GRN creation, partial PO receiving, supplier invoice variance calculation, 
 * atomic stock lot posting, and accounting integration upon approval.
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Audit.php';
require_once __DIR__ . '/Inventory.php';
require_once __DIR__ . '/Supplier.php';
require_once __DIR__ . '/Product.php';
require_once __DIR__ . '/Accounting.php';

class GRN
{
    public static function getList(?int $branchId = null, array $filters = [], int $page = 1, int $limit = 15): array
    {
        try {
            $limit = max(1, $limit);
            $page = max(1, $page);
            $where = [];
            $params = [];

            if ($branchId) {
                $where[] = "g.branch_id = :branch_id";
                $params[':branch_id'] = $branchId;
            }

            if (!empty($filters['supplier_id'])) {
                $where[] = "g.supplier_id = :supplier_id";
                $params[':supplier_id'] = (int)$filters['supplier_id'];
            }

            if (!empty($filters['status'])) {
                $where[] = "g.status = :status";
                $params[':status'] = $filters['status'];
            }

            if (!empty($filters['search'])) {
                $term = '%' . trim($filters['search']) . '%';
                $where[] = "(
                    g.grn_number LIKE :s1 OR g.supplier_invoice_no LIKE :s2 OR s.name LIKE :s3 OR
                    s.supplier_code LIKE :s4 OR po.po_number LIKE :s5 OR g.status LIKE :s6 OR
                    g.difference_reason LIKE :s7 OR CAST(g.received_date AS CHAR) LIKE :s8 OR
                    CAST(g.actual_grn_total AS CHAR) LIKE :s9 OR FORMAT(g.actual_grn_total, 2) LIKE :s10 OR
                    CONCAT('LKR ', FORMAT(g.actual_grn_total, 2)) LIKE :s11 OR CONCAT('Rs. ', FORMAT(g.actual_grn_total, 2)) LIKE :s12 OR
                    CONCAT('Rs ', FORMAT(g.actual_grn_total, 2)) LIKE :s13 OR CAST(g.supplier_invoice_amount AS CHAR) LIKE :s14 OR
                    FORMAT(g.supplier_invoice_amount, 2) LIKE :s15 OR CONCAT('LKR ', FORMAT(g.supplier_invoice_amount, 2)) LIKE :s16 OR
                    CONCAT('Rs. ', FORMAT(g.supplier_invoice_amount, 2)) LIKE :s17 OR CAST(g.payment_due_date AS CHAR) LIKE :s18
                )";
                for ($i = 1; $i <= 18; $i++) {
                    $params[":s{$i}"] = $term;
                }
            }

            if (!empty($filters['date_from'])) {
                $where[] = "g.received_date >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }

            if (!empty($filters['date_to'])) {
                $where[] = "g.received_date <= :date_to";
                $params[':date_to'] = $filters['date_to'];
            }

            $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

            $countRow = Database::fetchOne("
                SELECT COUNT(*) AS total 
                FROM grns g 
                JOIN suppliers s ON g.supplier_id = s.id
                LEFT JOIN purchase_orders po ON g.po_id = po.id
                $whereSql
            ", $params);

            $totalItems = (int)($countRow['total'] ?? 0);
            $offset = ($page - 1) * $limit;

            $sql = "
                SELECT 
                    g.*,
                    s.name AS supplier_name,
                    s.supplier_code,
                    b.name AS branch_name,
                    u.username AS creator_name,
                    u2.username AS approver_name,
                    po.po_number
                FROM grns g
                JOIN suppliers s ON g.supplier_id = s.id
                JOIN branches b ON g.branch_id = b.id
                JOIN users u ON g.created_by = u.id
                LEFT JOIN users u2 ON g.approved_by = u2.id
                LEFT JOIN purchase_orders po ON g.po_id = po.id
                $whereSql
                ORDER BY g.id DESC
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
            Logger::error("Failed to fetch GRN list: " . $e->getMessage(), $e);
            return ['items' => [], 'totalItems' => 0, 'page' => 1, 'limit' => $limit, 'totalPages' => 1];
        }
    }

    public static function find(int $id): ?array
    {
        try {
            $grn = Database::fetchOne("
                SELECT g.*, 
                       s.name AS supplier_name, s.supplier_code, s.status AS supplier_status, s.phone AS supplier_phone, s.whatsapp AS supplier_whatsapp, s.address AS supplier_address,
                       b.name AS branch_name, b.code AS branch_code, b.address AS branch_address, b.phone AS branch_phone, b.email AS branch_email,
                       u.full_name AS creator_name,
                       u2.full_name AS approver_name,
                       po.po_number
                FROM grns g
                JOIN suppliers s ON g.supplier_id = s.id
                JOIN branches b ON g.branch_id = b.id
                JOIN users u ON g.created_by = u.id
                LEFT JOIN users u2 ON g.approved_by = u2.id
                LEFT JOIN purchase_orders po ON g.po_id = po.id
                WHERE g.id = :id
            ", [':id' => $id]);

            if (!$grn) return null;

            $items = Database::fetchAll("
                SELECT gi.*, p.name AS product_name, p.sku, p.barcode, p.has_expiry
                FROM grn_items gi
                JOIN products p ON gi.product_id = p.id
                WHERE gi.grn_id = :grn_id
            ", [':grn_id' => $id]);

            $grn['items'] = $items;
            return $grn;
        } catch (Throwable $e) {
            Logger::error("Failed to find GRN #$id: " . $e->getMessage(), $e);
            return null;
        }
    }

    public static function create(array $header, array $items, bool $allowOverReceipt = true): array
    {
        $poId = !empty($header['po_id']) ? (int)$header['po_id'] : null;
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

        if ($supplierId <= 0 || empty($supplierInvoiceNo) || empty($items)) {
            return ['success' => false, 'message' => 'Supplier, Supplier Invoice No, and items are required.'];
        }

        // Validate supplier active status
        $supplier = Supplier::find($supplierId);
        if (!$supplier || $supplier['status'] !== 'active') {
            return ['success' => false, 'message' => 'Selected supplier is inactive or invalid. Cannot create GRN for inactive supplier.'];
        }

        // If PO linked, validate PO
        $poItemsMap = [];
        if ($poId) {
            $po = Database::fetchOne("SELECT * FROM purchase_orders WHERE id = :id", [':id' => $poId]);
            if (!$po) {
                return ['success' => false, 'message' => 'Linked Purchase Order not found.'];
            }
            if (in_array($po['status'], ['Cancelled', 'Completed'])) {
                return ['success' => false, 'message' => "Linked PO is {$po['status']} and cannot accept new GRNs."];
            }

            $poItemsRows = Database::fetchAll("SELECT * FROM purchase_order_items WHERE po_id = :po_id", [':po_id' => $poId]);
            foreach ($poItemsRows as $poi) {
                $poItemsMap[$poi['product_id']] = [
                    'ordered_qty'  => (float)$poi['quantity'],
                    'received_qty' => (float)$poi['received_quantity']
                ];
            }
        }

        $db = Database::getInstance()->getConnection();

        try {
            $db->beginTransaction();

            $maxRow = Database::fetchOne("SELECT MAX(id) AS max_id FROM grns");
            $nextId = ((int)($maxRow['max_id'] ?? 0)) + 1;
            $grnNumber = 'GRN-' . date('Ym') . '-' . sprintf('%04d', $nextId);
            while (Database::fetchOne("SELECT id FROM grns WHERE grn_number = :g", [':g' => $grnNumber])) {
                $nextId++;
                $grnNumber = 'GRN-' . date('Ym') . '-' . sprintf('%04d', $nextId);
            }

            $actualGrnTotal = 0.00;
            $parsedItems = [];

            foreach ($items as $item) {
                $productId = (int)($item['product_id'] ?? 0);
                $qty = (float)($item['quantity'] ?? 0);
                $unitCost = (float)($item['unit_cost'] ?? 0);
                $sellingPrice = (float)($item['selling_price'] ?? 0);
                $expiryDate = !empty($item['expiry_date']) ? trim($item['expiry_date']) : null;
                $batchNumber = !empty($item['batch_number']) ? trim($item['batch_number']) : null;

                if ($productId <= 0 || $qty <= 0 || $unitCost < 0) {
                    continue;
                }

                // Check product active status
                $prod = Product::find($productId);
                if (!$prod || $prod['status'] !== 'active') {
                    $prodName = $prod ? $prod['name'] : "#$productId";
                    throw new Exception("Product '$prodName' is inactive. Cannot receive inactive product.");
                }

                if (!empty($prod['has_expiry']) && empty($expiryDate)) {
                    $prodName = $prod['name'] ?? "#$productId";
                    throw new Exception("Expiry Date is required for expiry-tracked product '$prodName'.");
                }

                // Over-receipt permitted: allows receiving quantities larger than PO ordered quantity

                $lineTotal = $qty * $unitCost;
                $actualGrnTotal += $lineTotal;

                $parsedItems[] = [
                    'product_id'    => $productId,
                    'quantity'      => $qty,
                    'unit_cost'     => $unitCost,
                    'selling_price' => $sellingPrice,
                    'line_total'    => $lineTotal,
                    'expiry_date'   => $expiryDate,
                    'batch_number'  => $batchNumber
                ];
            }

            if (empty($parsedItems)) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Please provide valid active product quantities and unit costs.'];
            }

            $differenceAmount = $supplierInvoiceAmount - $actualGrnTotal;

            $stmt = $db->prepare("
                INSERT INTO grns (grn_number, po_id, supplier_id, branch_id, supplier_invoice_no, supplier_invoice_date, payment_due_date, received_date, actual_grn_total, supplier_invoice_amount, difference_amount, difference_reason, notes, status, created_by)
                VALUES (:grn_number, :po_id, :supplier_id, :branch_id, :supplier_invoice_no, :supplier_invoice_date, :payment_due_date, :received_date, :actual_grn_total, :supplier_invoice_amount, :difference_amount, :difference_reason, :notes, 'Draft', :created_by)
            ");

            $stmt->execute([
                ':grn_number'              => $grnNumber,
                ':po_id'                   => $poId,
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

            $stmtItem = $db->prepare("
                INSERT INTO grn_items (grn_id, product_id, quantity, unit_cost, selling_price, line_total, expiry_date, batch_number)
                VALUES (:grn_id, :product_id, :quantity, :unit_cost, :selling_price, :line_total, :expiry_date, :batch_number)
            ");

            foreach ($parsedItems as $pi) {
                $stmtItem->execute([
                    ':grn_id'        => $grnId,
                    ':product_id'    => $pi['product_id'],
                    ':quantity'      => $pi['quantity'],
                    ':unit_cost'     => $pi['unit_cost'],
                    ':selling_price' => $pi['selling_price'],
                    ':line_total'    => $pi['line_total'],
                    ':expiry_date'   => $pi['expiry_date'],
                    ':batch_number'  => $pi['batch_number']
                ]);
            }

            $db->commit();

            Audit::log('GRN_CREATED', 'purchasing', (string)$grnId, null, [
                'grn_number' => $grnNumber, 'supplier_invoice_no' => $supplierInvoiceNo, 'actual_grn_total' => $actualGrnTotal
            ]);

            return ['success' => true, 'grn_id' => $grnId, 'grn_number' => $grnNumber, 'message' => "Goods Received Note $grnNumber created successfully (Draft)."];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to create GRN: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Error while creating GRN: ' . $e->getMessage()];
        }
    }

    public static function update(int $id, array $header, array $items, bool $allowOverReceipt = false): array
    {
        $grn = self::find($id);
        if (!$grn) {
            return ['success' => false, 'message' => 'GRN not found.'];
        }

        if ($grn['status'] !== 'Draft') {
            return ['success' => false, 'message' => 'Only Draft GRNs can be edited. Approved GRNs are immutable.'];
        }

        $poId = !empty($header['po_id']) ? (int)$header['po_id'] : $grn['po_id'];
        $supplierId = (int)($header['supplier_id'] ?? $grn['supplier_id']);
        $supplierInvoiceNo = trim($header['supplier_invoice_no'] ?? $grn['supplier_invoice_no']);
        $supplierInvoiceDate = trim($header['supplier_invoice_date'] ?? '') ?: null;
        $paymentDueDate = trim($header['payment_due_date'] ?? '') ?: null;
        $receivedDate = trim($header['received_date'] ?? $grn['received_date']);
        $supplierInvoiceAmount = (float)($header['supplier_invoice_amount'] ?? $grn['supplier_invoice_amount']);
        $differenceReason = trim($header['difference_reason'] ?? $grn['difference_reason']);
        $notes = trim($header['notes'] ?? $grn['notes']);

        // Validate active supplier
        $supplier = Supplier::find($supplierId);
        if (!$supplier || $supplier['status'] !== 'active') {
            return ['success' => false, 'message' => 'Selected supplier is inactive or invalid.'];
        }

        $db = Database::getInstance()->getConnection();

        try {
            $db->beginTransaction();

            $actualGrnTotal = 0.00;
            $parsedItems = [];

            foreach ($items as $item) {
                $productId = (int)($item['product_id'] ?? 0);
                $qty = (float)($item['quantity'] ?? 0);
                $unitCost = (float)($item['unit_cost'] ?? 0);
                $sellingPrice = (float)($item['selling_price'] ?? 0);
                $expiryDate = !empty($item['expiry_date']) ? trim($item['expiry_date']) : null;
                $batchNumber = !empty($item['batch_number']) ? trim($item['batch_number']) : null;

                if ($productId <= 0 || $qty <= 0 || $unitCost < 0) {
                    continue;
                }

                $prod = Product::find($productId);
                if (!$prod || $prod['status'] !== 'active') {
                    $prodName = $prod ? $prod['name'] : "#$productId";
                    throw new Exception("Product '$prodName' is inactive.");
                }

                if (!empty($prod['has_expiry']) && empty($expiryDate)) {
                    $prodName = $prod['name'] ?? "#$productId";
                    throw new Exception("Expiry Date is required for expiry-tracked product '$prodName'.");
                }

                $lineTotal = $qty * $unitCost;
                $actualGrnTotal += $lineTotal;

                $parsedItems[] = [
                    'product_id'    => $productId,
                    'quantity'      => $qty,
                    'unit_cost'     => $unitCost,
                    'selling_price' => $sellingPrice,
                    'line_total'    => $lineTotal,
                    'expiry_date'   => $expiryDate,
                    'batch_number'  => $batchNumber
                ];
            }

            if (empty($parsedItems)) {
                $db->rollBack();
                return ['success' => false, 'message' => 'GRN must contain at least one valid item.'];
            }

            $differenceAmount = $supplierInvoiceAmount - $actualGrnTotal;

            $stmt = $db->prepare("
                UPDATE grns 
                SET po_id = :po_id, supplier_id = :supplier_id, supplier_invoice_no = :supplier_invoice_no,
                    supplier_invoice_date = :supplier_invoice_date, payment_due_date = :payment_due_date, received_date = :received_date, 
                    actual_grn_total = :actual_grn_total, supplier_invoice_amount = :supplier_invoice_amount, 
                    difference_amount = :difference_amount, difference_reason = :difference_reason, 
                    notes = :notes, updated_at = NOW()
                WHERE id = :id
            ");

            $stmt->execute([
                ':po_id'                   => $poId,
                ':supplier_id'             => $supplierId,
                ':supplier_invoice_no'     => $supplierInvoiceNo,
                ':supplier_invoice_date'   => $supplierInvoiceDate,
                ':payment_due_date'        => $paymentDueDate,
                ':received_date'           => $receivedDate,
                ':actual_grn_total'        => $actualGrnTotal,
                ':supplier_invoice_amount' => $supplierInvoiceAmount,
                ':difference_amount'       => $differenceAmount,
                ':difference_reason'       => $differenceReason,
                ':notes'                   => $notes,
                ':id'                      => $id
            ]);

            // Replace items
            $db->prepare("DELETE FROM grn_items WHERE grn_id = :grn_id")->execute([':grn_id' => $id]);

            $stmtItem = $db->prepare("
                INSERT INTO grn_items (grn_id, product_id, quantity, unit_cost, selling_price, line_total, expiry_date, batch_number)
                VALUES (:grn_id, :product_id, :quantity, :unit_cost, :selling_price, :line_total, :expiry_date, :batch_number)
            ");

            foreach ($parsedItems as $pi) {
                $stmtItem->execute([
                    ':grn_id'        => $id,
                    ':product_id'    => $pi['product_id'],
                    ':quantity'      => $pi['quantity'],
                    ':unit_cost'     => $pi['unit_cost'],
                    ':selling_price' => $pi['selling_price'],
                    ':line_total'    => $pi['line_total'],
                    ':expiry_date'   => $pi['expiry_date'],
                    ':batch_number'  => $pi['batch_number']
                ]);
            }

            $db->commit();

            Audit::log('GRN_UPDATED', 'purchasing', (string)$id, $grn, [
                'actual_grn_total' => $actualGrnTotal, 'supplier_invoice_no' => $supplierInvoiceNo
            ]);

            return ['success' => true, 'message' => "GRN {$grn['grn_number']} updated successfully."];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to update GRN #$id: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Error while updating GRN: ' . $e->getMessage()];
        }
    }

    public static function cancel(int $id, int $userId = 0): array
    {
        $grn = self::find($id);
        if (!$grn) {
            return ['success' => false, 'message' => 'GRN not found.'];
        }

        if ($grn['status'] !== 'Draft') {
            return ['success' => false, 'message' => 'Approved GRNs are immutable and cannot be cancelled or deleted. Use Supplier Return for corrections.'];
        }

        try {
            Database::execute("UPDATE grns SET status = 'Cancelled', updated_at = NOW() WHERE id = :id", [':id' => $id]);

            Audit::log('GRN_CANCELLED', 'purchasing', (string)$id, ['status' => 'Draft'], ['status' => 'Cancelled']);
            return ['success' => true, 'message' => "Draft GRN {$grn['grn_number']} cancelled."];
        } catch (Throwable $e) {
            Logger::error("Failed to cancel GRN #$id: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Database error while cancelling GRN.'];
        }
    }

    /**
     * Approve GRN: Atomic creation of stock lots, branch sellable inventory increase,
     * stock ledger posting, PO received tracking update, and Accounting Journal posting.
     * IF ANY STEP FAILS: ROLLBACK EVERYTHING ATOMICALLY.
     */
    public static function approve(int $id, int $userId): array
    {
        $grn = self::find($id);
        if (!$grn) {
            return ['success' => false, 'message' => 'GRN not found.'];
        }

        if ($grn['status'] === 'Approved') {
            return ['success' => false, 'message' => 'GRN is already approved. Duplicate approval blocked.'];
        }

        if ($grn['status'] !== 'Draft') {
            return ['success' => false, 'message' => 'Only Draft GRNs can be approved. Current status: ' . $grn['status']];
        }

        $db = Database::getInstance()->getConnection();

        try {
            $db->beginTransaction();

            $branchId = (int)$grn['branch_id'];
            $grnNumber = $grn['grn_number'];

            foreach ($grn['items'] as $item) {
                $productId = (int)$item['product_id'];
                $qty = (float)$item['quantity'];
                $cost = (float)$item['unit_cost'];
                $sellingPrice = (float)$item['selling_price'];
                $expiryDate = !empty($item['expiry_date']) ? $item['expiry_date'] : null;
                $batchNumber = !empty($item['batch_number']) ? $item['batch_number'] : null;

                $lotNumber = 'LOT-GRN' . $id . '-' . $productId . '-' . rand(100, 999);

                // Add Stock Lot & Ledger entry (increases branch sellable inventory)
                $lotRes = Inventory::addStockLot(
                    $branchId,
                    $productId,
                    $cost,
                    $sellingPrice,
                    $qty,
                    $lotNumber,
                    $grnNumber,
                    "GRN Purchase (Invoice: " . $grn['supplier_invoice_no'] . ")" . ($batchNumber ? " (Batch: {$batchNumber})" : ''),
                    $userId,
                    'purchase_grn',
                    $expiryDate,
                    $batchNumber,
                    'grn',
                    $id
                );

                if (!$lotRes['success']) {
                    throw new Exception("Stock lot posting failed for product #$productId: " . $lotRes['message']);
                }

                // Update grn_items.lot_id
                $stmtLot = $db->prepare("UPDATE grn_items SET lot_id = :lot_id WHERE id = :item_id");
                $stmtLot->execute([':lot_id' => $lotRes['lot_id'], ':item_id' => $item['id']]);

                // Update master product cost & selling price
                $stmtProd = $db->prepare("UPDATE products SET cost = :cost, selling_price = :selling_price, updated_at = NOW() WHERE id = :id");
                $stmtProd->execute([':cost' => $cost, ':selling_price' => $sellingPrice, ':id' => $productId]);
            }

            // Update GRN Status to Approved
            $stmt = $db->prepare("
                UPDATE grns 
                SET status = 'Approved', approved_by = :approved_by, approved_at = NOW(), updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute([':approved_by' => $userId, ':id' => $id]);

            // If linked to PO, update PO received quantities and status
            if (!empty($grn['po_id'])) {
                $poId = (int)$grn['po_id'];
                foreach ($grn['items'] as $item) {
                    $stmtPoItem = $db->prepare("
                        UPDATE purchase_order_items 
                        SET received_quantity = received_quantity + :qty 
                        WHERE po_id = :po_id AND product_id = :product_id
                    ");
                    $stmtPoItem->execute([
                        ':qty'        => (float)$item['quantity'],
                        ':po_id'      => $poId,
                        ':product_id' => (int)$item['product_id']
                    ]);
                }

                // Check PO completion status
                $poItemSummary = Database::fetchOne("
                    SELECT 
                        SUM(quantity) AS total_ordered,
                        SUM(received_quantity) AS total_received,
                        SUM(CASE WHEN quantity > received_quantity THEN 1 ELSE 0 END) AS unfulfilled_count
                    FROM purchase_order_items 
                    WHERE po_id = :po_id
                ", [':po_id' => $poId]);

                $unfulfilledCount = (int)($poItemSummary['unfulfilled_count'] ?? 0);
                $totalReceived = (float)($poItemSummary['total_received'] ?? 0);

                if ($unfulfilledCount === 0) {
                    Database::execute("UPDATE purchase_orders SET status = 'Completed', updated_at = NOW() WHERE id = :id", [':id' => $poId]);
                } elseif ($totalReceived > 0) {
                    Database::execute("UPDATE purchase_orders SET status = 'Partially Received', updated_at = NOW() WHERE id = :id", [':id' => $poId]);
                }
            }

            // Accounting Posting (Dr Inventory Asset / Cr Supplier Payables) INSIDE SAME TRANSACTION
            $acctRes = Accounting::postGRN($id);
            if (!$acctRes['success']) {
                throw new Exception("Accounting journal posting failed for GRN #$id: " . $acctRes['message']);
            }

            // Commit atomic transaction
            $db->commit();

            Audit::log('GRN_APPROVED', 'purchasing', (string)$id, ['status' => 'Draft'], ['status' => 'Approved', 'total' => $grn['actual_grn_total']]);

            return ['success' => true, 'message' => "GRN {$grn['grn_number']} approved, stock lots created, and posted to accounting successfully."];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to approve GRN #$id: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Atomic GRN approval failed: ' . $e->getMessage()];
        }
    }

    public static function exportList(array $filters = []): void
    {
        $res = self::getList(null, $filters, 1, 10000);
        $items = $res['items'];

        $filename = 'grns_export_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['GRN Number', 'PO Number', 'Supplier Code', 'Supplier Name', 'Supplier Invoice No', 'Supplier Invoice Date', 'Received Date', 'Actual GRN Total', 'Supplier Invoice Amount', 'Difference Amount', 'Difference Reason', 'Status', 'Created By']);

        foreach ($items as $g) {
            fputcsv($output, [
                $g['grn_number'],
                $g['po_number'] ?? '',
                $g['supplier_code'] ?? '',
                $g['supplier_name'],
                $g['supplier_invoice_no'],
                $g['supplier_invoice_date'] ?? '',
                $g['received_date'],
                number_format($g['actual_grn_total'], 2, '.', ''),
                number_format($g['supplier_invoice_amount'], 2, '.', ''),
                number_format($g['difference_amount'], 2, '.', ''),
                $g['difference_reason'],
                $g['status'],
                $g['creator_name']
            ]);
        }

        fclose($output);
        exit();
    }
}
