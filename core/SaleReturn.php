<?php
/**
 * Curtiss POS - Sales Returns, Refunds & Exchanges Core Component (Stage 8)
 * Handles invoice and no-invoice returns, stock restoration, damaged stock logging,
 * split refund methods (Cash, Card, Bank, Customer Credit, Gift Voucher), and atomic exchanges.
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Audit.php';
require_once __DIR__ . '/Inventory.php';
require_once __DIR__ . '/Customer.php';
require_once __DIR__ . '/GiftVoucher.php';
require_once __DIR__ . '/Sale.php';

class SaleReturn
{
    /**
     * Generate continuous return transaction number (RET-0001, RET-0002...)
     */
    public static function generateReturnNumber(): string
    {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT MAX(id) AS max_id FROM sales_returns");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $nextNum = ((int)($row['max_id'] ?? 0)) + 1;
            return 'RET-' . sprintf('%04d', $nextNum);
        } catch (Throwable $e) {
            Logger::error("Failed to generate return number: " . $e->getMessage(), $e);
            return 'RET-' . sprintf('%04d', rand(1000, 9999));
        }
    }

    /**
     * Search original sale for return lookup by invoice number
     */
    public static function searchSaleForReturn(string $invoiceNo, int $branchId): ?array
    {
        $invoiceNo = trim($invoiceNo);
        if (empty($invoiceNo)) {
            return null;
        }

        try {
            $invPadded = is_numeric($invoiceNo) ? sprintf('%04d', (int)$invoiceNo) : $invoiceNo;
            $sale = Database::fetchOne("
                SELECT 
                    s.*,
                    c.name AS customer_name,
                    c.phone AS customer_phone,
                    c.credit_balance AS customer_credit_balance,
                    b.name AS branch_name,
                    u.full_name AS cashier_name
                FROM sales s
                LEFT JOIN customers c ON s.customer_id = c.id
                LEFT JOIN branches b ON s.branch_id = b.id
                LEFT JOIN users u ON s.user_id = u.id
                WHERE (s.invoice_no = :invoice_no OR s.invoice_no = :inv_pad)
                  AND s.branch_id = :branch_id 
                  AND s.status = 'completed'
            ", [
                ':invoice_no' => $invoiceNo,
                ':inv_pad'    => $invPadded,
                ':branch_id'  => $branchId
            ]);

            if (!$sale) {
                return null;
            }


            // Fetch sale items with lot details and previously returned quantities
            $items = Database::fetchAll("
                SELECT 
                    si.*,
                    p.name AS product_name,
                    p.barcode,
                    pl.lot_number,
                    COALESCE((
                        SELECT SUM(sri.quantity)
                        FROM sales_return_items sri
                        JOIN sales_returns sr ON sri.sales_return_id = sr.id
                        WHERE sr.sale_id = :sub_sale_id AND sri.sale_item_id = si.id
                    ), 0.00) AS previously_returned_qty
                FROM sale_items si
                JOIN products p ON si.product_id = p.id
                JOIN product_lots pl ON si.lot_id = pl.id
                WHERE si.sale_id = :sale_id
                ORDER BY si.id ASC
            ", [':sale_id' => $sale['id'], ':sub_sale_id' => $sale['id']]);

            foreach ($items as &$item) {
                $soldQty = (float)$item['quantity'];
                $prevReturned = (float)$item['previously_returned_qty'];
                $item['quantity'] = $soldQty;
                $item['previously_returned_qty'] = $prevReturned;
                $item['available_return_qty'] = max(0.00, round($soldQty - $prevReturned, 2));
            }

            $sale['items'] = $items;
            return $sale;

        } catch (Throwable $e) {
            Logger::error("Failed to search sale for return (Invoice: {$invoiceNo}): " . $e->getMessage(), $e);
            return null;
        }
    }

    /**
     * Search completed sales and customers by customer name, phone, code, or invoice number
     */
    public static function searchSalesOrCustomers(string $query, int $branchId): array
    {
        $query = trim($query);
        if (empty($query)) {
            return ['invoices' => [], 'customers' => []];
        }

        try {
            $likeQuery = '%' . $query . '%';
            $invPadded = is_numeric($query) ? sprintf('%04d', (int)$query) : $query;
            $likePadded = '%' . $invPadded . '%';

            // 1. Invoices Matching
            $invoices = Database::fetchAll("
                SELECT 
                    s.id,
                    s.invoice_no,
                    s.created_at,
                    s.total_amount,
                    s.customer_id,
                    COALESCE(c.name, 'Walk-in Customer') AS customer_name,
                    COALESCE(c.phone, '') AS customer_phone,
                    COALESCE(c.customer_code, '') AS customer_code,
                    (SELECT COUNT(*) FROM sale_items si WHERE si.sale_id = s.id) AS item_count
                FROM sales s
                LEFT JOIN customers c ON s.customer_id = c.id
                WHERE s.branch_id = :branch_id
                  AND s.status = 'completed'
                  AND (
                      s.invoice_no LIKE :q1 
                      OR s.invoice_no LIKE :q_pad
                      OR c.name LIKE :q2 
                      OR c.phone LIKE :q3 
                      OR c.customer_code LIKE :q4
                  )
                ORDER BY s.id DESC
                LIMIT 20
            ", [
                ':branch_id' => $branchId,
                ':q1'        => $likeQuery,
                ':q_pad'     => $likePadded,
                ':q2'        => $likeQuery,
                ':q3'        => $likeQuery,
                ':q4'        => $likeQuery
            ]);

            // 2. Customers Matching
            $customers = Database::fetchAll("
                SELECT 
                    c.id,
                    c.name,
                    c.phone,
                    c.customer_code,
                    c.credit_balance,
                    (SELECT COUNT(*) FROM sales s WHERE s.customer_id = c.id AND s.status = 'completed' AND s.branch_id = :branch_id) AS total_invoices
                FROM customers c
                WHERE c.deleted_at IS NULL
                  AND (c.name LIKE :q1 OR c.phone LIKE :q2 OR c.customer_code LIKE :q3)
                ORDER BY c.name ASC
                LIMIT 6
            ", [
                ':branch_id' => $branchId,
                ':q1'        => $likeQuery,
                ':q2'        => $likeQuery,
                ':q3'        => $likeQuery
            ]);

            return [
                'invoices'  => $invoices,
                'customers' => $customers
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to search sales or customers for return: " . $e->getMessage(), $e);
            return ['invoices' => [], 'customers' => []];
        }
    }

    /**
     * Get completed sales invoices for a specific customer
     */
    public static function getCustomerCompletedInvoices(int $customerId, int $branchId): array
    {
        try {
            return Database::fetchAll("
                SELECT 
                    s.id,
                    s.invoice_no,
                    s.created_at,
                    s.total_amount,
                    s.customer_id,
                    (SELECT COUNT(*) FROM sale_items si WHERE si.sale_id = s.id) AS item_count
                FROM sales s
                WHERE s.customer_id = :customer_id
                  AND s.branch_id = :branch_id
                  AND s.status = 'completed'
                ORDER BY s.id DESC
                LIMIT 25
            ", [
                ':customer_id' => $customerId,
                ':branch_id'   => $branchId
            ]);
        } catch (Throwable $e) {
            Logger::error("Failed to fetch customer invoices for return: " . $e->getMessage(), $e);
            return [];
        }
    }

    /**
     * Search products that have lots in the specified branch for return selection
     */
    public static function searchSoldProducts(int $branchId, string $query): array
    {
        $query = trim($query);
        if (empty($query)) {
            return [];
        }

        try {
            $likeQuery = '%' . $query . '%';
            $rows = Database::fetchAll("
                SELECT DISTINCT
                    p.id AS product_id,
                    p.name AS product_name,
                    p.barcode,
                    p.sku,
                    pl.id AS lot_id,
                    pl.lot_number,
                    pl.selling_price,
                    pl.cost,
                    pl.remaining_quantity
                FROM product_lots pl
                INNER JOIN products p ON pl.product_id = p.id
                WHERE pl.branch_id = :branch_id
                  AND p.deleted_at IS NULL
                  AND (p.name LIKE :q1 OR p.barcode LIKE :q2 OR pl.lot_number LIKE :q3 OR p.sku LIKE :q4)
                ORDER BY p.name ASC, pl.id DESC
                LIMIT 50
            ", [
                ':branch_id' => $branchId,
                ':q1'        => $likeQuery,
                ':q2'        => $likeQuery,
                ':q3'        => $likeQuery,
                ':q4'        => $likeQuery
            ]);

            $grouped = [];
            foreach ($rows as $row) {
                $pid = (int)$row['product_id'];
                if (!isset($grouped[$pid])) {
                    $grouped[$pid] = [
                        'product_id'   => $pid,
                        'product_name' => $row['product_name'],
                        'barcode'      => $row['barcode'] ?: ($row['sku'] ?: ('#' . $pid)),
                        'sku'          => $row['sku'],
                        'lots'         => []
                    ];
                }
                $grouped[$pid]['lots'][] = [
                    'lot_id'             => (int)$row['lot_id'],
                    'lot_number'         => $row['lot_number'],
                    'selling_price'      => (float)$row['selling_price'],
                    'cost'               => (float)$row['cost'],
                    'remaining_quantity' => (float)$row['remaining_quantity']
                ];
            }

            return array_values($grouped);
        } catch (Throwable $e) {
            Logger::error("Failed to search sold products: " . $e->getMessage(), $e);
            return [];
        }
    }


    /**
     * Get summary map of previously returned quantities per sale item ID
     */
    public static function getPreviouslyReturnedQuantities(int $saleId): array
    {
        try {
            $rows = Database::fetchAll("
                SELECT sri.sale_item_id, SUM(sri.quantity) AS total_returned
                FROM sales_return_items sri
                JOIN sales_returns sr ON sri.sales_return_id = sr.id
                WHERE sr.sale_id = :sale_id AND sri.sale_item_id IS NOT NULL
                GROUP BY sri.sale_item_id
            ", [':sale_id' => $saleId]);

            $map = [];
            foreach ($rows as $row) {
                $map[(int)$row['sale_item_id']] = (float)$row['total_returned'];
            }
            return $map;
        } catch (Throwable $e) {
            Logger::error("Failed to get previously returned quantities for sale #{$saleId}: " . $e->getMessage(), $e);
            return [];
        }
    }

    /**
     * Process Sales Return, Refund and/or Exchange inside a single database transaction
     */
    public static function processReturn(array $data, int $userId, int $branchId): array
    {
        $db = Database::getInstance()->getConnection();

        try {
            $db->beginTransaction();

            $saleId = !empty($data['sale_id']) ? (int)$data['sale_id'] : null;
            $customerId = !empty($data['customer_id']) ? (int)$data['customer_id'] : 1;
            $returnType = isset($data['return_type']) && in_array($data['return_type'], ['return_only', 'exchange']) 
                ? $data['return_type'] : 'return_only';
            $notes = !empty($data['notes']) ? trim($data['notes']) : null;
            $items = $data['items'] ?? [];
            $refundsData = $data['refunds'] ?? [];
            $exchangeData = $data['exchange'] ?? null;

            if (empty($items)) {
                $db->rollBack();
                return ['success' => false, 'message' => 'No return items specified.'];
            }

            require_once __DIR__ . '/BusinessDay.php';

            $hasCashRefund = false;
            if (!empty($refundsData)) {
                foreach ($refundsData as $r) {
                    if (strtolower(trim($r['refund_method'] ?? '')) === 'cash' && (float)($r['amount'] ?? 0) > 0) {
                        $hasCashRefund = true;
                        break;
                    }
                }
            }

            $hasExchangeCash = false;
            if ($returnType === 'exchange' && !empty($exchangeData['payments'])) {
                foreach ($exchangeData['payments'] as $ep) {
                    if (strtolower(trim($ep['payment_method'] ?? '')) === 'cash' && (float)($ep['amount'] ?? 0) > 0) {
                        $hasExchangeCash = true;
                        break;
                    }
                }
            }

            $activeDay = null;
            if ($hasCashRefund || $hasExchangeCash) {
                $activeDay = BusinessDay::getOrCreateActiveDay($branchId, $userId);
                if (!$activeDay) {
                    $db->rollBack();
                    return ['success' => false, 'message' => 'Cannot process cash refund or exchange. No active business day is currently open for this branch.'];
                }
            }

            // Original sale verification
            $originalSale = null;
            $prevReturnedMap = [];
            if ($saleId !== null) {
                $originalSale = Database::fetchOne("
                    SELECT * FROM sales WHERE id = :id AND branch_id = :branch_id AND status = 'completed'
                ", [':id' => $saleId, ':branch_id' => $branchId]);

                if (!$originalSale) {
                    $db->rollBack();
                    return ['success' => false, 'message' => "Original invoice not found or not completed in this branch."];
                }

                $prevReturnedMap = self::getPreviouslyReturnedQuantities($saleId);
            }

            $totalReturnAmount = 0.00;
            $processedReturnItems = [];

            // Process returned items
            foreach ($items as $idx => $item) {
                $saleItemId = !empty($item['sale_item_id']) ? (int)$item['sale_item_id'] : null;
                $productId = (int)($item['product_id'] ?? 0);
                $lotId = (int)($item['lot_id'] ?? 0);
                $quantity = round((float)($item['quantity'] ?? 0), 2);
                $unitPrice = round((float)($item['unit_price'] ?? 0), 2);
                $costPrice = round((float)($item['cost_price'] ?? 0), 2);
                $condition = isset($item['item_condition']) && in_array($item['item_condition'], ['good', 'damaged']) 
                    ? $item['item_condition'] : 'good';
                $reason = !empty($item['reason']) ? trim($item['reason']) : 'Customer Return';

                // If invoice return, load exact original sale item values
                if ($saleId !== null && $saleItemId !== null) {
                    $origItem = Database::fetchOne("
                        SELECT * FROM sale_items WHERE id = :id AND sale_id = :sale_id
                    ", [':id' => $saleItemId, ':sale_id' => $saleId]);

                    if (!$origItem) {
                        $db->rollBack();
                        return ['success' => false, 'message' => "Sale item #{$saleItemId} does not belong to original invoice #{$originalSale['invoice_no']}."];
                    }

                    // Always enforce exact sold product and lot from the original invoice
                    $productId = (int)$origItem['product_id'];
                    $lotId = (int)$origItem['lot_id'];

                    $soldQty = (float)$origItem['quantity'];
                    $alreadyReturned = $prevReturnedMap[$saleItemId] ?? 0.00;
                    $maxAllowable = round($soldQty - $alreadyReturned, 2);

                    if ($quantity > ($maxAllowable + 0.001)) {
                        $db->rollBack();
                        return [
                            'success' => false,
                            'message' => "Cannot return quantity {$quantity} for item #{$productId}. Max allowable return is {$maxAllowable} (Sold: {$soldQty}, Previously Returned: {$alreadyReturned})."
                        ];
                    }

                    // Default price/cost from exact invoice sold values
                    if ($unitPrice <= 0) {
                        $itemPrice = (float)$origItem['unit_price'];
                        if (!empty($originalSale['subtotal']) && (float)$originalSale['subtotal'] > 0 && !empty($originalSale['discount_amount']) && (float)$originalSale['discount_amount'] > 0) {
                            $discRatio = (float)$originalSale['discount_amount'] / (float)$originalSale['subtotal'];
                            $itemPrice = round($itemPrice * (1.0 - $discRatio), 2);
                        }
                        $unitPrice = $itemPrice;
                    }
                    if ($costPrice <= 0) {
                        $costPrice = (float)$origItem['unit_cost'];
                    }
                }

                if ($productId <= 0 || $lotId <= 0 || $quantity <= 0.00) {
                    $db->rollBack();
                    return ['success' => false, 'message' => "Invalid product, lot, or quantity at item row #" . ($idx + 1)];
                }

                // Verify product and lot exist
                $lotRow = Database::fetchOne("
                    SELECT * FROM product_lots WHERE id = :lot_id AND product_id = :product_id AND branch_id = :branch_id
                ", [':lot_id' => $lotId, ':product_id' => $productId, ':branch_id' => $branchId]);

                if (!$lotRow) {
                    $db->rollBack();
                    return ['success' => false, 'message' => "Lot #{$lotId} for product #{$productId} not found in this branch."];
                }

                if ($saleId === null || $saleItemId === null) {
                    // No-invoice return price/cost fallback from lot/product master
                    if ($unitPrice <= 0) {
                        $unitPrice = (float)($lotRow['selling_price'] ?? 0.00);
                    }
                    if ($costPrice <= 0) {
                        $costPrice = (float)($lotRow['cost'] ?? 0.00);
                    }
                }

                $lineTotal = round($quantity * $unitPrice, 2);
                $totalReturnAmount += $lineTotal;

                $processedReturnItems[] = [
                    'sale_item_id'   => $saleItemId,
                    'product_id'     => $productId,
                    'lot_id'         => $lotId,
                    'quantity'       => $quantity,
                    'unit_price'     => $unitPrice,
                    'cost_price'     => $costPrice,
                    'line_total'     => $lineTotal,
                    'item_condition' => $condition,
                    'reason'         => $reason
                ];
            }

            $totalReturnAmount = round($totalReturnAmount, 2);

            // Cap cumulative invoice refunds to not exceed original sale net total
            if ($saleId !== null && $originalSale) {
                $prevRow = Database::fetchOne("
                    SELECT COALESCE(SUM(total_return_amount), 0.00) AS total
                    FROM sales_returns 
                    WHERE sale_id = :sid
                ", [':sid' => $saleId]);
                $prevInvoiceRefunded = (float)($prevRow['total'] ?? 0.00);

                $maxRefundableInvoiceTotal = round((float)$originalSale['total_amount'] - $prevInvoiceRefunded, 2);
                if ($totalReturnAmount > ($maxRefundableInvoiceTotal + 0.01)) {
                    $db->rollBack();
                    return [
                        'success' => false,
                        'message' => "Total return amount (LKR " . number_format($totalReturnAmount, 2) . ") exceeds remaining refundable amount on original invoice (LKR " . number_format($maxRefundableInvoiceTotal, 2) . ")."
                    ];
                }
            }

            // Handle Exchange Items
            $totalExchangeAmount = 0.00;
            $exchangeSaleId = null;
            $processedExchangeItems = [];

            if ($returnType === 'exchange' && !empty($exchangeData['items'])) {
                foreach ($exchangeData['items'] as $eIdx => $eItem) {
                    $eProductId = (int)($eItem['product_id'] ?? 0);
                    $eLotId = (int)($eItem['lot_id'] ?? 0);
                    $eQuantity = round((float)($eItem['quantity'] ?? 0), 2);
                    $eUnitPrice = round((float)($eItem['unit_price'] ?? 0), 2);
                    $eOriginalPrice = round((float)($eItem['original_price'] ?? $eUnitPrice), 2);
                    $eDiscountStr = $eItem['discount_value'] ?? '';

                    if ($eProductId <= 0 || $eLotId <= 0 || $eQuantity <= 0) {
                        $db->rollBack();
                        return ['success' => false, 'message' => "Invalid replacement product, lot, or quantity at exchange row #" . ($eIdx + 1)];
                    }

                    // Check lot stock
                    $eLot = Database::fetchOne("
                        SELECT cost, remaining_quantity, selling_price 
                        FROM product_lots 
                        WHERE id = :lot_id AND branch_id = :branch_id AND status = 'active'
                    ", [':lot_id' => $eLotId, ':branch_id' => $branchId]);

                    if (!$eLot || (float)$eLot['remaining_quantity'] < $eQuantity) {
                        $avail = $eLot ? (float)$eLot['remaining_quantity'] : 0.00;
                        $db->rollBack();
                        return ['success' => false, 'message' => "Insufficient stock for replacement lot #{$eLotId}. Requested: {$eQuantity}, Available: {$avail}."];
                    }

                    $eUnitCost = (float)$eLot['cost'];
                    $eLineSubtotal = round($eUnitPrice * $eQuantity, 2);
                    $eDiscount = Sale::parseDiscount($eDiscountStr, $eLineSubtotal);
                    $eLineTotal = round($eLineSubtotal - $eDiscount['total_discount'], 2);

                    $totalExchangeAmount += $eLineTotal;

                    $processedExchangeItems[] = [
                        'product_id'     => $eProductId,
                        'lot_id'         => $eLotId,
                        'quantity'       => $eQuantity,
                        'original_price' => $eOriginalPrice,
                        'unit_price'     => $eUnitPrice,
                        'unit_cost'      => $eUnitCost,
                        'discount_type'  => $eDiscount['type'],
                        'discount_value' => $eDiscountStr,
                        'discount_amount'=> $eDiscount['total_discount'],
                        'line_total'     => $eLineTotal,
                        'line_cost'      => round($eUnitCost * $eQuantity, 2)
                    ];
                }
            }

            $totalExchangeAmount = round($totalExchangeAmount, 2);
            $netDifference = round($totalExchangeAmount - $totalReturnAmount, 2);

            // Create Sales Return Record Header
            $returnNo = self::generateReturnNumber();

            $stmtReturn = $db->prepare("
                INSERT INTO sales_returns (
                    return_no, sale_id, branch_id, customer_id, return_type,
                    total_return_amount, total_exchange_amount, net_difference,
                    exchange_sale_id, notes, created_by
                ) VALUES (
                    :return_no, :sale_id, :branch_id, :customer_id, :return_type,
                    :total_return_amount, :total_exchange_amount, :net_difference,
                    NULL, :notes, :created_by
                )
            ");

            $stmtReturn->execute([
                ':return_no'             => $returnNo,
                ':sale_id'               => $saleId,
                ':branch_id'             => $branchId,
                ':customer_id'           => $customerId,
                ':return_type'           => $returnType,
                ':total_return_amount'   => $totalReturnAmount,
                ':total_exchange_amount' => $totalExchangeAmount,
                ':net_difference'        => $netDifference,
                ':notes'                 => $notes,
                ':created_by'            => $userId
            ]);

            $salesReturnId = (int)$db->lastInsertId();

            // Insert Sales Return Items & Handle Stock Restoration / Damaged Logging
            $stmtItem = $db->prepare("
                INSERT INTO sales_return_items (
                    sales_return_id, sale_item_id, product_id, lot_id, quantity,
                    unit_price, cost_price, total_amount, item_condition, reason
                ) VALUES (
                    :sales_return_id, :sale_item_id, :product_id, :lot_id, :quantity,
                    :unit_price, :cost_price, :total_amount, :item_condition, :reason
                )
            ");

            foreach ($processedReturnItems as $pItem) {
                $stmtItem->execute([
                    ':sales_return_id' => $salesReturnId,
                    ':sale_item_id'    => $pItem['sale_item_id'],
                    ':product_id'      => $pItem['product_id'],
                    ':lot_id'          => $pItem['lot_id'],
                    ':quantity'        => $pItem['quantity'],
                    ':unit_price'      => $pItem['unit_price'],
                    ':cost_price'      => $pItem['cost_price'],
                    ':total_amount'    => $pItem['line_total'],
                    ':item_condition'  => $pItem['item_condition'],
                    ':reason'          => $pItem['reason']
                ]);

                if ($pItem['item_condition'] === 'good') {
                    // Restore sellable lot stock
                    $db->prepare("
                        UPDATE product_lots 
                        SET remaining_quantity = remaining_quantity + :qty, status = 'active'
                        WHERE id = :lot_id
                    ")->execute([':qty' => $pItem['quantity'], ':lot_id' => $pItem['lot_id']]);

                    // Update branch inventory
                    $db->prepare("
                        INSERT INTO branch_inventory (branch_id, product_id, quantity, low_stock_threshold)
                        VALUES (:branch_id, :product_id, :qty, 10.00)
                        ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
                    ")->execute([
                        ':branch_id' => $branchId,
                        ':product_id' => $pItem['product_id'],
                        ':qty' => $pItem['quantity']
                    ]);

                    // Get updated branch stock balance
                    $currStockRow = Database::fetchOne("
                        SELECT quantity FROM branch_inventory WHERE branch_id = :branch_id AND product_id = :product_id
                    ", [':branch_id' => $branchId, ':product_id' => $pItem['product_id']]);
                    $balAfter = (float)($currStockRow['quantity'] ?? 0.00);

                    // Insert stock ledger entry using valid movement_type = 'customer_return'
                    $db->prepare("
                        INSERT INTO stock_ledger (
                            branch_id, product_id, lot_id, movement_type, quantity, balance_after, reference_no, notes, user_id
                        ) VALUES (
                            :branch_id, :product_id, :lot_id, 'customer_return', :qty, :bal_after, :ref_no, :notes, :user_id
                        )
                    ")->execute([
                        ':branch_id'  => $branchId,
                        ':product_id' => $pItem['product_id'],
                        ':lot_id'     => $pItem['lot_id'],
                        ':qty'        => $pItem['quantity'],
                        ':bal_after'  => $balAfter,
                        ':ref_no'     => $returnNo,
                        ':notes'      => 'Good condition sales return stock restoration',
                        ':user_id'    => $userId
                    ]);

                } else {
                    // Damaged Return - Log in damaged stock, do NOT add to sellable inventory
                    $db->prepare("
                        INSERT INTO damaged_stock (
                            branch_id, product_id, lot_id, quantity, sales_return_id, reason, logged_by
                        ) VALUES (
                            :branch_id, :product_id, :lot_id, :qty, :sales_return_id, :reason, :logged_by
                        )
                    ")->execute([
                        ':branch_id'       => $branchId,
                        ':product_id'      => $pItem['product_id'],
                        ':lot_id'          => $pItem['lot_id'],
                        ':qty'             => $pItem['quantity'],
                        ':sales_return_id' => $salesReturnId,
                        ':reason'          => $pItem['reason'],
                        ':logged_by'       => $userId
                    ]);

                    $currStockRow = Database::fetchOne("
                        SELECT quantity FROM branch_inventory WHERE branch_id = :branch_id AND product_id = :product_id
                    ", [':branch_id' => $branchId, ':product_id' => $pItem['product_id']]);
                    $balAfter = (float)($currStockRow['quantity'] ?? 0.00);

                    // Stock ledger entry using movement_type = 'damage_wastage'
                    $db->prepare("
                        INSERT INTO stock_ledger (
                            branch_id, product_id, lot_id, movement_type, quantity, balance_after, reference_no, notes, user_id
                        ) VALUES (
                            :branch_id, :product_id, :lot_id, 'damage_wastage', 0.00, :bal_after, :ref_no, :notes, :user_id
                        )
                    ")->execute([
                        ':branch_id'  => $branchId,
                        ':product_id' => $pItem['product_id'],
                        ':lot_id'     => $pItem['lot_id'],
                        ':bal_after'  => $balAfter,
                        ':ref_no'     => $returnNo,
                        ':notes'      => 'Damaged item return logged to damaged stock log (Qty: ' . $pItem['quantity'] . ')',
                        ':user_id'    => $userId
                    ]);

                    Audit::log('DAMAGED_STOCK_LOGGED', 'returns', (string)$salesReturnId, null, [
                        'return_no'  => $returnNo,
                        'product_id' => $pItem['product_id'],
                        'quantity'   => $pItem['quantity'],
                        'reason'     => $pItem['reason']
                    ]);
                }
            }

            // Create Exchange Sale if replacement products selected
            if ($returnType === 'exchange' && !empty($processedExchangeItems)) {
                $exSubtotal = $totalExchangeAmount;
                $exTotalAmount = $totalExchangeAmount;
                $exTotalCost = 0.00;
                foreach ($processedExchangeItems as $exItem) {
                    $exTotalCost += $exItem['line_cost'];
                }

                $exInvoiceNo = Sale::generateInvoiceNumber();
                $stmtExSale = $db->prepare("
                    INSERT INTO sales (
                        invoice_no, branch_id, customer_id, user_id, subtotal,
                        discount_type, discount_value, discount_amount, roundup_amount,
                        total_amount, total_cost, status, held_notes
                    ) VALUES (
                        :invoice_no, :branch_id, :customer_id, :user_id, :subtotal,
                        'none', '', 0.00, 0.00,
                        :total_amount, :total_cost, 'completed', :held_notes
                    )
                ");

                $stmtExSale->execute([
                    ':invoice_no'   => $exInvoiceNo,
                    ':branch_id'    => $branchId,
                    ':customer_id'  => $customerId,
                    ':user_id'      => $userId,
                    ':subtotal'     => $exSubtotal,
                    ':total_amount' => $exTotalAmount,
                    ':total_cost'   => $exTotalCost,
                    ':held_notes'   => "Exchange replacement sale for Return #{$returnNo}"
                ]);

                $exchangeSaleId = (int)$db->lastInsertId();

                // Deduct stock for exchange replacement items
                $stmtExItem = $db->prepare("
                    INSERT INTO sale_items (
                        sale_id, product_id, lot_id, quantity, original_price, unit_price, unit_cost,
                        discount_type, discount_value, discount_amount, line_total, line_cost
                    ) VALUES (
                        :sale_id, :product_id, :lot_id, :quantity, :original_price, :unit_price, :unit_cost,
                        :discount_type, :discount_value, :discount_amount, :line_total, :line_cost
                    )
                ");

                foreach ($processedExchangeItems as $exItem) {
                    $stmtExItem->execute([
                        ':sale_id'        => $exchangeSaleId,
                        ':product_id'     => $exItem['product_id'],
                        ':lot_id'         => $exItem['lot_id'],
                        ':quantity'       => $exItem['quantity'],
                        ':original_price' => $exItem['original_price'],
                        ':unit_price'     => $exItem['unit_price'],
                        ':unit_cost'      => $exItem['unit_cost'],
                        ':discount_type'  => $exItem['discount_type'],
                        ':discount_value' => $exItem['discount_value'],
                        ':discount_amount'=> $exItem['discount_amount'],
                        ':line_total'     => $exItem['line_total'],
                        ':line_cost'      => $exItem['line_cost']
                    ]);

                    // Deduct lot stock
                    $db->prepare("
                        UPDATE product_lots 
                        SET remaining_quantity = remaining_quantity - :qty,
                            status = IF(remaining_quantity <= 0, 'depleted', status)
                        WHERE id = :lot_id
                    ")->execute([':qty' => $exItem['quantity'], ':lot_id' => $exItem['lot_id']]);

                    // Deduct branch inventory
                    $db->prepare("
                        UPDATE branch_inventory 
                        SET quantity = quantity - :qty 
                        WHERE branch_id = :branch_id AND product_id = :product_id
                    ")->execute([':qty' => $exItem['quantity'], ':branch_id' => $branchId, ':product_id' => $exItem['product_id']]);

                    $currStockRow = Database::fetchOne("
                        SELECT quantity FROM branch_inventory WHERE branch_id = :branch_id AND product_id = :product_id
                    ", [':branch_id' => $branchId, ':product_id' => $exItem['product_id']]);
                    $balAfter = (float)($currStockRow['quantity'] ?? 0.00);

                    // Record stock ledger movement
                    $db->prepare("
                        INSERT INTO stock_ledger (
                            branch_id, product_id, lot_id, movement_type, quantity, balance_after, reference_no, notes, user_id
                        ) VALUES (
                            :branch_id, :product_id, :lot_id, 'sale', :qty, :bal_after, :ref_no, :notes, :user_id
                        )
                    ")->execute([
                        ':branch_id'  => $branchId,
                        ':product_id' => $exItem['product_id'],
                        ':lot_id'     => $exItem['lot_id'],
                        ':qty'        => -$exItem['quantity'],
                        ':bal_after'  => $balAfter,
                        ':ref_no'     => $exInvoiceNo,
                        ':notes'      => 'Exchange replacement item sale',
                        ':user_id'    => $userId
                    ]);
                }

                // Update sales_returns record with exchange_sale_id
                $db->prepare("UPDATE sales_returns SET exchange_sale_id = :ex_id WHERE id = :ret_id")
                   ->execute([':ex_id' => $exchangeSaleId, ':ret_id' => $salesReturnId]);
            }

            // Determine Net Refund / Customer Additional Payment Requirements
            $settlementMode = $data['settlement_mode'] ?? (!empty($data['no_refund']) ? 'exchange' : (!empty($refundsData) ? 'refund' : 'exchange'));
            $requiredRefundAmount = 0.00;
            if ($netDifference < 0) {
                // Store owes refund to customer
                $requiredRefundAmount = abs($netDifference);
            } elseif ($returnType === 'return_only') {
                if ($settlementMode === 'refund' || (!empty($refundsData) && $settlementMode !== 'exchange' && $settlementMode !== 'credit')) {
                    $requiredRefundAmount = $totalReturnAmount;
                } else {
                    $requiredRefundAmount = 0.00;
                }
            }

            // Handle Customer Additional Payment for Exchange (if netDifference > 0)
            if ($netDifference > 0 && $exchangeSaleId !== null) {
                $exPayments = $exchangeData['payments'] ?? [];
                $totalPaidForEx = 0.00;
                $stmtExPay = $db->prepare("
                    INSERT INTO payments (payment_no, sale_id, branch_id, customer_id, payment_method, amount, reference_no, voucher_id, user_id)
                    VALUES (:payment_no, :sale_id, :branch_id, :customer_id, :payment_method, :amount, :reference_no, :voucher_id, :user_id)
                ");

                foreach ($exPayments as $exPay) {
                    $pMethod = ucfirst(strtolower(trim($exPay['payment_method'] ?? 'Cash')));
                    if (!in_array($pMethod, ['Cash', 'Card', 'Bank', 'Credit', 'Voucher'])) {
                        $pMethod = 'Cash';
                    }
                    $pAmount = round((float)($exPay['amount'] ?? 0), 2);
                    $pRef = $exPay['reference_no'] ?? null;

                    if ($pAmount > 0) {
                        $totalPaidForEx += $pAmount;
                        $paymentNo = 'PAY-' . date('Ymd') . '-' . sprintf('%04d', rand(1000, 9999)) . substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4);
                        $stmtExPay->execute([
                            ':payment_no'     => $paymentNo,
                            ':sale_id'        => $exchangeSaleId,
                            ':branch_id'      => $branchId,
                            ':customer_id'    => $customerId,
                            ':payment_method' => $pMethod,
                            ':amount'         => $pAmount,
                            ':reference_no'   => $pRef,
                            ':voucher_id'     => null,
                            ':user_id'        => $userId
                        ]);
                        if ($pMethod === 'Cash' && isset($activeDay) && $activeDay) {
                            BusinessDay::recordDrawerMovement([
                                'business_day_id' => (int)$activeDay['id'],
                                'branch_id'       => $branchId,
                                'movement_type'   => 'pos_sale',
                                'source_type'     => 'sales',
                                'source_id'       => $exchangeSaleId,
                                'amount'          => $pAmount,
                                'direction'       => 'in',
                                'reason'          => "Exchange Cash Payment (Return #{$returnNo})",
                                'user_id'         => $userId
                            ]);
                        }
                    }
                }

                // Apply return credit as payment on exchange sale
                if ($totalReturnAmount > 0) {
                    $paymentNo = 'PAY-' . date('Ymd') . '-' . sprintf('%04d', rand(1000, 9999)) . substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4);
                    $stmtExPay->execute([
                        ':payment_no'     => $paymentNo,
                        ':sale_id'        => $exchangeSaleId,
                        ':branch_id'      => $branchId,
                        ':customer_id'    => $customerId,
                        ':payment_method' => 'Credit',
                        ':amount'         => $totalReturnAmount,
                        ':reference_no'   => "Applied Credit from Return #{$returnNo}",
                        ':voucher_id'     => null,
                        ':user_id'        => $userId
                    ]);
                    $totalPaidForEx += $totalReturnAmount;
                }

                if ($totalPaidForEx < ($totalExchangeAmount - 0.01)) {
                    $db->rollBack();
                    return [
                        'success' => false,
                        'message' => "Exchange payment total is insufficient. Total Exchange: {$totalExchangeAmount}, Return Credit: {$totalReturnAmount}, Additional Paid: " . ($totalPaidForEx - $totalReturnAmount)
                    ];
                }
            }

            // Handle Customer Store Credit Adjustment (if settlement_mode === 'credit')
            if ($settlementMode === 'credit' && $totalReturnAmount > 0.00) {
                if ($customerId <= 1) {
                    $db->rollBack();
                    return ['success' => false, 'message' => 'Store Credit adjustment is only available for registered customers. Please select or register a customer account.'];
                }

                $custRow = Database::fetchOne("SELECT * FROM customers WHERE id = :id AND deleted_at IS NULL FOR UPDATE", [':id' => $customerId]);
                if (!$custRow) {
                    $db->rollBack();
                    return ['success' => false, 'message' => 'Customer account not found for credit adjustment.'];
                }

                $currDebt = (float)$custRow['credit_balance'];
                $newDebt = max(0.00, round($currDebt - $totalReturnAmount, 2));

                $db->prepare("UPDATE customers SET credit_balance = :new_bal WHERE id = :id")
                   ->execute([':new_bal' => $newDebt, ':id' => $customerId]);

                $db->prepare("
                    INSERT INTO customer_credit_ledger (
                        customer_id, branch_id, user_id, amount_paid, amount_due, balance_after, transaction_type, reference_no, notes
                    ) VALUES (
                        :customer_id, :branch_id, :user_id, :amount_paid, :amount_due, :balance_after, 'adjustment', :reference_no, :notes
                    )
                ")->execute([
                    ':customer_id'   => $customerId,
                    ':branch_id'     => $branchId,
                    ':user_id'       => $userId,
                    ':amount_paid'   => $totalReturnAmount,
                    ':amount_due'    => -$totalReturnAmount,
                    ':balance_after' => $newDebt,
                    ':reference_no'  => $returnNo,
                    ':notes'         => "Return #{$returnNo} store credit balance adjustment"
                ]);

                $db->prepare("
                    INSERT INTO refunds (
                        sales_return_id, branch_id, bank_account_id, customer_id, refund_method, amount, reference_no, replacement_voucher_id, created_by
                    ) VALUES (
                        :sales_return_id, :branch_id, NULL, :customer_id, 'credit', :amount, :reference_no, NULL, :created_by
                    )
                ")->execute([
                    ':sales_return_id' => $salesReturnId,
                    ':branch_id'       => $branchId,
                    ':customer_id'     => $customerId,
                    ':amount'          => $totalReturnAmount,
                    ':reference_no'    => $returnNo,
                    ':created_by'      => $userId
                ]);

                Audit::log('CUSTOMER_CREDIT_ADJUSTED', 'customers', (string)$customerId, null, [
                    'previous_balance' => $currDebt,
                    'new_balance'      => $newDebt,
                    'refund_amount'    => $totalReturnAmount,
                    'return_no'        => $returnNo
                ]);
            }

            // Handle Refund Methods (if refund payout is required)
            $totalRefunded = 0.00;
            if ($requiredRefundAmount > 0.00 && $settlementMode === 'refund') {
                if (empty($refundsData)) {
                    $db->rollBack();
                    return ['success' => false, 'message' => "Refund method and amount required. Net Refund Due: " . sprintf('%.2f', $requiredRefundAmount)];
                }

                $stmtRefund = $db->prepare("
                    INSERT INTO refunds (
                        sales_return_id, branch_id, bank_account_id, customer_id, refund_method, amount, reference_no, replacement_voucher_id, created_by
                    ) VALUES (
                        :sales_return_id, :branch_id, :bank_account_id, :customer_id, :refund_method, :amount, :reference_no, :replacement_voucher_id, :created_by
                    )
                ");

                foreach ($refundsData as $rData) {
                    $rawMethod = strtolower(trim($rData['refund_method'] ?? 'cash'));
                    $rBankId = !empty($rData['bank_account_id']) ? (int)$rData['bank_account_id'] : null;

                    if ($rawMethod === 'cash drawer' || $rawMethod === 'cash_drawer') {
                        $rMethod = 'cash_drawer';
                    } elseif ($rawMethod === 'cash in hand' || $rawMethod === 'cash_in_hand') {
                        $rMethod = 'cash';
                    } elseif ($rawMethod === 'petty cash' || $rawMethod === 'petty_cash') {
                        $rMethod = 'petty_cash';
                    } elseif (strpos($rawMethod, 'bank:') === 0) {
                        $parts = explode(':', $rawMethod);
                        $rMethod = 'bank';
                        $rBankId = (int)($parts[1] ?? 0);
                    } elseif ($rawMethod === 'bank transfer' || $rawMethod === 'bank') {
                        $rMethod = 'bank';
                    } else {
                        $rMethod = $rawMethod;
                    }

                    $rAmount = round((float)($rData['amount'] ?? 0), 2);
                    $rRef = !empty($rData['reference_no']) ? trim($rData['reference_no']) : null;

                    if (!in_array($rMethod, ['cash', 'cash_drawer', 'petty_cash', 'card', 'bank', 'credit', 'voucher', 'cheque'])) {
                        $db->rollBack();
                        return ['success' => false, 'message' => "Unsupported refund method '{$rMethod}'."];
                    }

                    if ($rAmount <= 0) {
                        continue;
                    }

                    $totalRefunded += $rAmount;
                    $replacementVoucherId = null;

                    if ($rMethod === 'bank' && $rBankId <= 0) {
                        $db->rollBack();
                        return ['success' => false, 'message' => 'Bank account selection is required for bank transfer refund.'];
                    }

                    if ($rMethod === 'credit') {
                        // Customer credit balance adjustment
                        if ($customerId <= 1) {
                            $db->rollBack();
                            return ['success' => false, 'message' => 'Customer Credit Adjustment refund is not allowed for Walk-in Customer. Select a registered customer.'];
                        }

                        $custRow = Database::fetchOne("SELECT * FROM customers WHERE id = :id AND deleted_at IS NULL", [':id' => $customerId]);
                        if (!$custRow) {
                            $db->rollBack();
                            return ['success' => false, 'message' => 'Customer account not found for credit adjustment.'];
                        }

                        $currDebt = (float)$custRow['credit_balance'];
                        $newDebt = max(0.00, round($currDebt - $rAmount, 2));

                        $db->prepare("UPDATE customers SET credit_balance = :new_bal WHERE id = :id")
                           ->execute([':new_bal' => $newDebt, ':id' => $customerId]);

                        // Record in customer credit ledger matching schema columns: amount_paid, amount_due, balance_after
                        $db->prepare("
                            INSERT INTO customer_credit_ledger (
                                customer_id, branch_id, user_id, amount_paid, amount_due, balance_after, transaction_type, reference_no, notes
                            ) VALUES (
                                :customer_id, :branch_id, :user_id, :amount_paid, :amount_due, :balance_after, 'adjustment', :reference_no, :notes
                            )
                        ")->execute([
                            ':customer_id'   => $customerId,
                            ':branch_id'     => $branchId,
                            ':user_id'       => $userId,
                            ':amount_paid'   => $rAmount,
                            ':amount_due'    => -$rAmount,
                            ':balance_after' => $newDebt,
                            ':reference_no'  => $returnNo,
                            ':notes'         => "Return #{$returnNo} credit balance adjustment refund"
                        ]);

                        Audit::log('CUSTOMER_CREDIT_ADJUSTED', 'customers', (string)$customerId, null, [
                            'previous_balance' => $currDebt,
                            'new_balance'      => $newDebt,
                            'refund_amount'    => $rAmount,
                            'return_no'        => $returnNo
                        ]);

                    } elseif ($rMethod === 'voucher') {
                        // Issue NEW replacement Gift Voucher
                        $expiryDate = date('Y-m-d', strtotime('+6 months'));
                        $vRes = GiftVoucher::createVoucher($rAmount, $expiryDate, "Replacement voucher issued for Return #{$returnNo}", $userId);
                        
                        if (!$vRes['success']) {
                            $db->rollBack();
                            return ['success' => false, 'message' => "Failed to issue replacement gift voucher: " . $vRes['message']];
                        }

                        $replacementVoucherId = (int)$vRes['voucher_id'];

                        Audit::log('REPLACEMENT_VOUCHER_ISSUED', 'vouchers', (string)$replacementVoucherId, null, [
                            'voucher_code' => $vRes['voucher_code'],
                            'amount'       => $rAmount,
                            'return_no'    => $returnNo
                        ]);
                    }

                    $stmtRefund->execute([
                        ':sales_return_id'        => $salesReturnId,
                        ':branch_id'              => $branchId,
                        ':bank_account_id'        => $rBankId,
                        ':customer_id'            => $customerId,
                        ':refund_method'          => $rMethod,
                        ':amount'                 => $rAmount,
                        ':reference_no'           => $rRef,
                        ':replacement_voucher_id' => $replacementVoucherId,
                        ':created_by'             => $userId
                    ]);

                    $refundId = (int)$db->lastInsertId();

                    if ($rMethod === 'card' && $saleId !== null && $saleId > 0) {
                        $db->prepare("
                            UPDATE payments 
                            SET refunded_amount = refunded_amount + :ramt,
                                settlement_status = IF(ROUND(amount - refunded_amount - settled_amount, 2) <= 0.00, 'settled', settlement_status)
                            WHERE sale_id = :sid AND LOWER(payment_method) = 'card'
                        ")->execute([':ramt' => $rAmount, ':sid' => $saleId]);
                    }

                    if (($rMethod === 'cash' || $rMethod === 'cash_drawer') && isset($activeDay) && $activeDay) {
                        BusinessDay::recordDrawerMovement([
                            'business_day_id' => (int)$activeDay['id'],
                            'branch_id'       => $branchId,
                            'movement_type'   => 'cash_refund',
                            'source_type'     => 'refunds',
                            'source_id'       => $refundId,
                            'amount'          => $rAmount,
                            'direction'       => 'out',
                            'reason'          => "Cash Refund for Return #{$returnNo}",
                            'user_id'         => $userId
                        ]);
                    }

                    Audit::log('REFUND_ISSUED', 'returns', (string)$salesReturnId, null, [
                        'return_no'     => $returnNo,
                        'refund_method' => $rMethod,
                        'amount'        => $rAmount
                    ]);
                }

                if (abs($totalRefunded - $requiredRefundAmount) > 0.01) {
                    $db->rollBack();
                    return [
                        'success' => false,
                        'message' => "Total refund amount (" . sprintf('%.2f', $totalRefunded) . ") does not match required refund amount (" . sprintf('%.2f', $requiredRefundAmount) . ")."
                    ];
                }
            }

            $db->commit();

            require_once __DIR__ . '/Accounting.php';
            Accounting::postSalesReturn($salesReturnId);

            // Audit Logs for Return Creation
            Audit::log('RETURN_CREATED', 'returns', (string)$salesReturnId, null, [
                'return_no'             => $returnNo,
                'sale_id'               => $saleId,
                'return_type'           => $returnType,
                'total_return_amount'   => $totalReturnAmount,
                'total_exchange_amount' => $totalExchangeAmount,
                'net_difference'        => $netDifference
            ]);

            if ($saleId === null) {
                Audit::log('RETURN_NO_INVOICE', 'returns', (string)$salesReturnId, null, [
                    'return_no'           => $returnNo,
                    'total_return_amount' => $totalReturnAmount
                ]);
            }

            if ($returnType === 'exchange') {
                Audit::log('EXCHANGE_PROCESSED', 'returns', (string)$salesReturnId, null, [
                    'return_no'        => $returnNo,
                    'exchange_sale_id' => $exchangeSaleId,
                    'net_difference'   => $netDifference
                ]);
            }

            return [
                'success'               => true,
                'message'               => "Sales Return {$returnNo} processed successfully.",
                'sales_return_id'       => $salesReturnId,
                'return_no'             => $returnNo,
                'total_return_amount'   => $totalReturnAmount,
                'total_exchange_amount' => $totalExchangeAmount,
                'net_difference'        => $netDifference,
                'exchange_sale_id'      => $exchangeSaleId
            ];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to process sales return: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => "Sales Return Error: " . $e->getMessage()];
        }
    }

    /**
     * Create Draft Sales Return (No inventory, refund, or accounting impact)
     */
    public static function createDraftReturn(array $data, int $userId, int $branchId): array
    {
        $db = Database::getInstance()->getConnection();
        try {
            $db->beginTransaction();

            $saleId = !empty($data['sale_id']) ? (int)$data['sale_id'] : null;
            $customerId = !empty($data['customer_id']) ? (int)$data['customer_id'] : 1;
            $returnType = isset($data['return_type']) && in_array($data['return_type'], ['return_only', 'exchange']) ? $data['return_type'] : 'return_only';
            $notes = !empty($data['notes']) ? trim($data['notes']) : 'Draft Return';
            $items = $data['items'] ?? [];

            if (empty($items)) {
                $db->rollBack();
                return ['success' => false, 'message' => 'No return items specified for draft.'];
            }

            $totalReturnAmount = 0.00;
            foreach ($items as $item) {
                $qty = round((float)($item['quantity'] ?? 0), 2);
                $price = round((float)($item['unit_price'] ?? 0), 2);
                $totalReturnAmount += ($qty * $price);
            }

            $returnNo = self::generateReturnNumber();
            $netDifference = -$totalReturnAmount;
            $stmt = $db->prepare("
                INSERT INTO sales_returns (
                    return_no, sale_id, branch_id, customer_id, return_type,
                    total_return_amount, total_exchange_amount, net_difference,
                    status, notes, created_by
                ) VALUES (
                    :return_no, :sale_id, :branch_id, :customer_id, :return_type,
                    :total_return_amount, 0.00, :net_difference,
                    'draft', :notes, :created_by
                )
            ");
            $stmt->execute([
                ':return_no'           => $returnNo,
                ':sale_id'             => $saleId,
                ':branch_id'           => $branchId,
                ':customer_id'         => $customerId,
                ':return_type'         => $returnType,
                ':total_return_amount' => $totalReturnAmount,
                ':net_difference'      => $netDifference,
                ':notes'               => $notes,
                ':created_by'          => $userId
            ]);
            $draftId = (int)$db->lastInsertId();

            $stmtItem = $db->prepare("
                INSERT INTO sales_return_items (
                    sales_return_id, sale_item_id, product_id, lot_id, quantity,
                    unit_price, cost_price, total_amount, item_condition, reason
                ) VALUES (
                    :sales_return_id, :sale_item_id, :product_id, :lot_id, :quantity,
                    :unit_price, :cost_price, :total_amount, :item_condition, :reason
                )
            ");
            foreach ($items as $item) {
                $stmtItem->execute([
                    ':sales_return_id' => $draftId,
                    ':sale_item_id'    => $item['sale_item_id'] ?? null,
                    ':product_id'      => (int)$item['product_id'],
                    ':lot_id'          => (int)$item['lot_id'],
                    ':quantity'        => round((float)$item['quantity'], 2),
                    ':unit_price'      => round((float)($item['unit_price'] ?? 0), 2),
                    ':cost_price'      => round((float)($item['cost_price'] ?? 0), 2),
                    ':total_amount'    => round((float)$item['quantity'] * (float)($item['unit_price'] ?? 0), 2),
                    ':item_condition'  => $item['item_condition'] ?? 'good',
                    ':reason'          => $item['reason'] ?? 'Draft Return'
                ]);
            }

            $db->commit();
            Audit::log('RETURN_DRAFT_CREATED', 'returns', (string)$draftId, null, ['return_no' => $returnNo]);

            return [
                'success'         => true,
                'message'         => "Draft return {$returnNo} created successfully.",
                'sales_return_id' => $draftId,
                'return_no'       => $returnNo,
                'status'          => 'draft'
            ];
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            Logger::error("Failed to create draft return: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => "Draft Return Error: " . $e->getMessage()];
        }
    }

    /**
     * Cancel Draft Sales Return
     */
    public static function cancelDraftReturn(int $returnId, int $userId): array
    {
        try {
            $ret = Database::fetchOne("SELECT * FROM sales_returns WHERE id = :id", [':id' => $returnId]);
            if (!$ret) {
                return ['success' => false, 'message' => 'Return record not found.'];
            }
            if ($ret['status'] !== 'draft') {
                return ['success' => false, 'message' => "Cannot cancel return #{$ret['return_no']}. Status is '{$ret['status']}' (only Draft returns can be cancelled)."];
            }

            Database::execute("UPDATE sales_returns SET status = 'cancelled' WHERE id = :id", [':id' => $returnId]);
            Audit::log('RETURN_CANCELLED', 'returns', (string)$returnId, null, ['return_no' => $ret['return_no']]);
            return ['success' => true, 'message' => "Draft return #{$ret['return_no']} cancelled successfully."];
        } catch (Throwable $e) {
            Logger::error("Failed to cancel draft return #{$returnId}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => "Cancel Draft Error: " . $e->getMessage()];
        }
    }

    /**
     * Get Paginated Sales Returns List
     */
    public static function all(array $filters = [], int $page = 1, int $limit = 15): array
    {
        try {
            $where = [];
            $params = [];

            if (!empty($filters['branch_id'])) {
                $where[] = "sr.branch_id = :branch_id";
                $params[':branch_id'] = (int)$filters['branch_id'];
            }

            if (!empty($filters['search'])) {
                $search = '%' . trim($filters['search']) . '%';
                $where[] = "(sr.return_no LIKE :s1 OR s.invoice_no LIKE :s2 OR c.name LIKE :s3)";
                $params[':s1'] = $search;
                $params[':s2'] = $search;
                $params[':s3'] = $search;
            }

            if (!empty($filters['return_type']) && in_array($filters['return_type'], ['return_only', 'exchange'])) {
                $where[] = "sr.return_type = :return_type";
                $params[':return_type'] = $filters['return_type'];
            }

            $whereSql = !empty($where) ? "WHERE " . implode(' AND ', $where) : "";

            $countRow = Database::fetchOne("
                SELECT COUNT(*) AS total 
                FROM sales_returns sr 
                LEFT JOIN sales s ON sr.sale_id = s.id 
                LEFT JOIN customers c ON sr.customer_id = c.id 
                $whereSql
            ", $params);

            $totalItems = (int)($countRow['total'] ?? 0);
            $offset = ($page - 1) * $limit;

            $items = Database::fetchAll("
                SELECT 
                    sr.*,
                    s.invoice_no AS original_invoice_no,
                    c.name AS customer_name,
                    b.name AS branch_name,
                    u.full_name AS created_by_name
                FROM sales_returns sr
                LEFT JOIN sales s ON sr.sale_id = s.id
                LEFT JOIN customers c ON sr.customer_id = c.id
                JOIN branches b ON sr.branch_id = b.id
                JOIN users u ON sr.created_by = u.id
                $whereSql
                ORDER BY sr.id DESC
                LIMIT $limit OFFSET $offset
            ", $params);

            return [
                'items'      => $items,
                'totalItems' => $totalItems,
                'page'       => $page,
                'limit'      => $limit,
                'totalPages' => max(1, ceil($totalItems / $limit))
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to fetch sales returns list: " . $e->getMessage(), $e);
            return ['items' => [], 'totalItems' => 0, 'page' => 1, 'limit' => $limit, 'totalPages' => 1];
        }
    }

    /**
     * Find single Sales Return by ID with items, refunds, damaged stock, and exchange details
     */
    public static function find(int $returnId): ?array
    {
        try {
            $return = Database::fetchOne("
                SELECT 
                    sr.*,
                    s.invoice_no AS original_invoice_no,
                    c.name AS customer_name,
                    c.phone AS customer_phone,
                    b.name AS branch_name,
                    u.full_name AS created_by_name
                FROM sales_returns sr
                LEFT JOIN sales s ON sr.sale_id = s.id
                LEFT JOIN customers c ON sr.customer_id = c.id
                JOIN branches b ON sr.branch_id = b.id
                JOIN users u ON sr.created_by = u.id
                WHERE sr.id = :id
            ", [':id' => $returnId]);

            if (!$return) {
                return null;
            }

            // Return items
            $items = Database::fetchAll("
                SELECT sri.*, p.name AS product_name, p.barcode, pl.lot_number
                FROM sales_return_items sri
                JOIN products p ON sri.product_id = p.id
                JOIN product_lots pl ON sri.lot_id = pl.id
                WHERE sri.sales_return_id = :id
                ORDER BY sri.id ASC
            ", [':id' => $returnId]);

            // Refunds
            $refunds = Database::fetchAll("
                SELECT r.*, gv.voucher_code AS replacement_voucher_code
                FROM refunds r
                LEFT JOIN gift_vouchers gv ON r.replacement_voucher_id = gv.id
                WHERE r.sales_return_id = :id
                ORDER BY r.id ASC
            ", [':id' => $returnId]);

            // Damaged stock logs
            $damaged = Database::fetchAll("
                SELECT ds.*, p.name AS product_name, p.barcode, pl.lot_number
                FROM damaged_stock ds
                JOIN products p ON ds.product_id = p.id
                LEFT JOIN product_lots pl ON ds.lot_id = pl.id
                WHERE ds.sales_return_id = :id
                ORDER BY ds.id ASC
            ", [':id' => $returnId]);

            // Exchange sale details if present
            $exchangeSale = null;
            if (!empty($return['exchange_sale_id'])) {
                $exchangeSale = Database::fetchOne("
                    SELECT s.*, u.full_name AS cashier_name
                    FROM sales s
                    LEFT JOIN users u ON s.user_id = u.id
                    WHERE s.id = :id
                ", [':id' => $return['exchange_sale_id']]);

                if ($exchangeSale) {
                    $exchangeSale['items'] = Database::fetchAll("
                        SELECT si.*, p.name AS product_name, p.barcode, pl.lot_number
                        FROM sale_items si
                        JOIN products p ON si.product_id = p.id
                        JOIN product_lots pl ON si.lot_id = pl.id
                        WHERE si.sale_id = :id
                    ", [':id' => $return['exchange_sale_id']]);
                }
            }

            $return['items'] = $items;
            $return['refunds'] = $refunds;
            $return['damaged_stock'] = $damaged;
            $return['exchange_sale'] = $exchangeSale;

            return $return;

        } catch (Throwable $e) {
            Logger::error("Failed to find sales return #{$returnId}: " . $e->getMessage(), $e);
            return null;
        }
    }
}
