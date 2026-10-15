<?php
/**
 * Curtiss POS - Sales & POS Billing Core Component (Stage 6)
 * Handles continuous invoice numbering, lot selection, discount parsing, and draft/hold workflows.
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Audit.php';

class Sale
{
    /**
     * Generate continuous integer invoice number (0001, 0002 ... 9999, 10000...)
     * Continuous across branches and time; never resets.
     */
    public static function generateInvoiceNumber(): string
    {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT MAX(CAST(invoice_no AS UNSIGNED)) AS max_inv FROM sales");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $nextNum = ((int)($row['max_inv'] ?? 0)) + 1;
            return sprintf('%04d', $nextNum);
        } catch (Throwable $e) {
            Logger::error("Failed to generate invoice number: " . $e->getMessage(), $e);
            return sprintf('%04d', rand(1000, 9999));
        }
    }

    /**
     * Parse discount input strings (e.g. '10', '10%', '10%+0.75')
     */
    public static function parseDiscount(?string $discountStr, float $baseAmount): array
    {
        $discountStr = trim((string)$discountStr);
        if ($discountStr === '' || $baseAmount <= 0) {
            return [
                'type'                 => 'none',
                'raw_value'            => '',
                'discount_amount'      => 0.00,
                'roundup_amount'       => 0.00,
                'total_discount'       => 0.00,
                'customer_facing_text' => ''
            ];
        }

        // Check round-up / added amount format e.g. "10%+25" or "10+25"
        if (strpos($discountStr, '+') !== false) {
            $parts = explode('+', $discountStr, 2);
            $basePart = trim($parts[0]);
            $addedPart = (float)trim($parts[1]);

            $baseParsed = self::parseDiscount($basePart, $baseAmount);
            // 10%+25 means 10% discount and ADD 25 rupees to the bill/item.
            // 25 is not the discount; it adds a rupee value to the bill.
            // Effective net discount deduction = discount_amount - addedPart.
            $netDiscount = round($baseParsed['discount_amount'] - $addedPart, 2);

            return [
                'type'                 => 'roundup',
                'raw_value'            => $discountStr,
                'discount_amount'      => max(0.00, $netDiscount),
                'roundup_amount'       => $addedPart,
                'total_discount'       => $netDiscount,
                'customer_facing_text' => $baseParsed['customer_facing_text']
            ];
        }

        // Percentage discount e.g. "10%"
        if (substr($discountStr, -1) === '%') {
            $pctVal = (float)rtrim($discountStr, '%');
            $discAmount = round($baseAmount * ($pctVal / 100), 2);
            $formattedPct = (floor($pctVal) == $pctVal) ? (string)(int)$pctVal : (string)$pctVal;
            return [
                'type'                 => 'percentage',
                'raw_value'            => $discountStr,
                'discount_amount'      => $discAmount,
                'roundup_amount'       => 0.00,
                'total_discount'       => $discAmount,
                'customer_facing_text' => $formattedPct . '%'
            ];
        }

        // Fixed numeric discount e.g. "10" or "50.00"
        $fixedVal = (float)$discountStr;
        $discAmount = round(min($fixedVal, $baseAmount), 2);
        return [
            'type'                 => 'fixed',
            'raw_value'            => $discountStr,
            'discount_amount'      => $discAmount,
            'roundup_amount'       => 0.00,
            'total_discount'       => $discAmount,
            'customer_facing_text' => sprintf('%.2f', $fixedVal)
        ];
    }

    /**
     * Calculate round-up suggestion for next sensible 10-value amount
     */
    public static function calculateRoundupSuggestion(float $payableAmount): ?array
    {
        if ($payableAmount <= 0) {
            return null;
        }

        $nextTen = ceil($payableAmount / 10.00) * 10.00;
        $diff = round($nextTen - $payableAmount, 2);

        if ($diff > 0.00 && $diff < 10.00) {
            return [
                'suggested_roundup' => $diff,
                'suggested_total'   => $nextTen
            ];
        }

        return null;
    }

    /**
     * Fetch active lots for product in branch where remaining_quantity > 0
     */
    public static function getAvailableLotsForProduct(int $productId, int $branchId): array
    {
        try {
            return Database::fetchAll("
                SELECT 
                    id AS lot_id,
                    lot_number,
                    product_id,
                    branch_id,
                    cost,
                    selling_price,
                    remaining_quantity,
                    expiry_date,
                    batch_number
                FROM product_lots
                WHERE product_id = :product_id 
                  AND branch_id = :branch_id 
                  AND remaining_quantity > 0 
                  AND status = 'active'
                  AND (expiry_date IS NULL OR expiry_date >= CURRENT_DATE())
                ORDER BY 
                  CASE WHEN expiry_date IS NOT NULL THEN 0 ELSE 1 END ASC,
                  expiry_date ASC,
                  id ASC
            ", [':product_id' => $productId, ':branch_id' => $branchId]);
        } catch (Throwable $e) {
            Logger::error("Failed to fetch available lots for product #{$productId}: " . $e->getMessage(), $e);
            return [];
        }
    }

    /**
     * Search products for POS cashier interface with lot auto-selection capability
     */
    public static function searchProducts(int $branchId, string $query): array
    {
        try {
            $query = trim($query);
            if ($query === '') {
                return [];
            }

            $params = [':branch_id' => $branchId];
            $where = ["p.deleted_at IS NULL AND p.status = 'active'"];

            // 1. Check for exact barcode or SKU match first (regardless of length or format)
            $exactProduct = Database::fetchOne("
                SELECT p.id 
                FROM products p 
                WHERE p.deleted_at IS NULL AND p.status = 'active'
                  AND (LOWER(p.barcode) = LOWER(:bc) OR LOWER(p.sku) = LOWER(:sku))
                LIMIT 1
            ", [':bc' => $query, ':sku' => $query]);

            if ($exactProduct) {
                $where[] = "p.id = :exact_id";
                $params[':exact_id'] = (int)$exactProduct['id'];
            } else {
                $where[] = "(p.barcode LIKE :q1 OR p.sku LIKE :q2 OR p.name LIKE :q3 OR b.name LIKE :q4 OR c.name LIKE :q5)";
                $term = '%' . $query . '%';
                $params[':q1'] = $term;
                $params[':q2'] = $term;
                $params[':q3'] = $term;
                $params[':q4'] = $term;
                $params[':q5'] = $term;
            }

            $whereSql = implode(' AND ', $where);

            $products = Database::fetchAll("
                SELECT 
                    p.id AS product_id,
                    p.sku,
                    p.barcode,
                    p.name AS product_name,
                    p.cost AS default_cost,
                    p.selling_price AS default_price,
                    p.has_expiry,
                    b.name AS brand_name,
                    c.name AS category_name,
                    COALESCE(bi.quantity, 0.00) AS total_branch_stock
                FROM products p
                JOIN brands b ON p.brand_id = b.id
                JOIN categories c ON p.category_id = c.id
                LEFT JOIN branch_inventory bi ON p.id = bi.product_id AND bi.branch_id = :branch_id
                WHERE $whereSql
                ORDER BY p.name ASC
                LIMIT 20
            ", $params);

            foreach ($products as &$prod) {
                $lots = self::getAvailableLotsForProduct((int)$prod['product_id'], $branchId);
                $prod['lots'] = $lots;
                $prod['active_lots_count'] = count($lots);
                $prod['auto_selected_lot'] = (count($lots) === 1) ? $lots[0] : null;

                // Accurately sum total available stock across all active non-expired lots
                if (!empty($lots)) {
                    $totalLotStock = 0.0;
                    foreach ($lots as $lot) {
                        $totalLotStock += (float)($lot['remaining_quantity'] ?? 0);
                    }
                    $prod['total_branch_stock'] = $totalLotStock;
                }
            }

            return $products;
        } catch (Throwable $e) {
            Logger::error("POS product search error: " . $e->getMessage(), $e);
            return [];
        }
    }

    /**
     * Hold/Park an invoice transaction (Stock is NOT deducted during hold)
     */
    public static function holdInvoice(array $data, int $userId, int $branchId): array
    {
        $db = Database::getInstance()->getConnection();
        try {
            $db->beginTransaction();

            $saleId = !empty($data['sale_id']) ? (int)$data['sale_id'] : null;
            $customerId = !empty($data['customer_id']) ? (int)$data['customer_id'] : 1; // Default Walk-in
            $salesRepId = !empty($data['sales_rep_id']) ? (int)$data['sales_rep_id'] : null;
            $heldNotes = !empty($data['held_notes']) ? trim($data['held_notes']) : 'POS Cashier Parked Invoice';
            $items = $data['items'] ?? [];

            if (empty($items)) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Cannot hold an empty cart.'];
            }

            // Calculate totals
            $subtotal = 0.00;
            $totalCost = 0.00;
            $processedItems = [];

            foreach ($items as $item) {
                $productId = (int)$item['product_id'];
                $lotId = (int)$item['lot_id'];
                $quantity = (float)$item['quantity'];
                $unitPrice = (float)$item['unit_price'];
                $originalPrice = (float)($item['original_price'] ?? $unitPrice);
                $discountStr = $item['discount_value'] ?? '';

                if ($quantity <= 0) {
                    $db->rollBack();
                    return ['success' => false, 'message' => "Invalid quantity for item ID {$productId}."];
                }

                // Verify lot stock availability
                $lot = Database::fetchOne("
                    SELECT cost, remaining_quantity, selling_price 
                    FROM product_lots 
                    WHERE id = :lot_id AND branch_id = :branch_id AND status = 'active'
                ", [':lot_id' => $lotId, ':branch_id' => $branchId]);

                if (!$lot || (float)$lot['remaining_quantity'] < $quantity) {
                    $available = $lot ? (float)$lot['remaining_quantity'] : 0;
                    $db->rollBack();
                    return [
                        'success' => false, 
                        'message' => "Insufficient branch lot stock for lot #{$lotId}. Requested: {$quantity}, Available: {$available}."
                    ];
                }

                $unitCost = (float)$lot['cost'];
                $lineSubtotal = round($unitPrice * $quantity, 2);
                $itemDiscount = self::parseDiscount($discountStr, $lineSubtotal);

                $lineTotal = round($lineSubtotal - $itemDiscount['total_discount'], 2);
                $lineCost = round($unitCost * $quantity, 2);

                $subtotal += $lineTotal;
                $totalCost += $lineCost;

                $processedItems[] = [
                    'product_id'     => $productId,
                    'lot_id'         => $lotId,
                    'quantity'       => $quantity,
                    'original_price' => $originalPrice,
                    'unit_price'     => $unitPrice,
                    'unit_cost'      => $unitCost,
                    'discount_type'  => $itemDiscount['type'],
                    'discount_value' => $discountStr,
                    'discount_amount'=> $itemDiscount['total_discount'],
                    'line_total'     => $lineTotal,
                    'line_cost'      => $lineCost
                ];

                // Audit manual price override if price changed
                if (abs($unitPrice - $originalPrice) > 0.001) {
                    Audit::log('SALES_PRICE_OVERRIDE', 'pos', (string)$productId, null, [
                        'original_price' => $originalPrice,
                        'new_price'      => $unitPrice,
                        'lot_id'         => $lotId
                    ]);
                }
            }

            // Invoice level discount
            $invoiceDiscountStr = $data['discount_value'] ?? '';
            $invDiscount = self::parseDiscount($invoiceDiscountStr, $subtotal);
            $totalAmount = round($subtotal - $invDiscount['total_discount'], 2);

            if ($saleId) {
                // Update existing held invoice
                $sale = Database::fetchOne("SELECT invoice_no FROM sales WHERE id = :id AND branch_id = :branch_id", [':id' => $saleId, ':branch_id' => $branchId]);
                if (!$sale) {
                    $db->rollBack();
                    return ['success' => false, 'message' => 'Invoice record not found.'];
                }
                $invoiceNo = $sale['invoice_no'];

                $stmt = $db->prepare("
                    UPDATE sales SET
                        customer_id = :customer_id,
                        sales_rep_id = :sales_rep_id,
                        user_id = :user_id,
                        subtotal = :subtotal,
                        discount_type = :discount_type,
                        discount_value = :discount_value,
                        discount_amount = :discount_amount,
                        roundup_amount = :roundup_amount,
                        total_amount = :total_amount,
                        total_cost = :total_cost,
                        status = 'held',
                        held_at = CURRENT_TIMESTAMP,
                        held_notes = :held_notes
                    WHERE id = :id
                ");

                $stmt->execute([
                    ':customer_id'    => $customerId,
                    ':sales_rep_id'   => $salesRepId,
                    ':user_id'        => $userId,
                    ':subtotal'       => $subtotal,
                    ':discount_type'  => $invDiscount['type'],
                    ':discount_value' => $invoiceDiscountStr,
                    ':discount_amount'=> $invDiscount['discount_amount'],
                    ':roundup_amount' => $invDiscount['roundup_amount'],
                    ':total_amount'   => $totalAmount,
                    ':total_cost'     => $totalCost,
                    ':held_notes'     => $heldNotes,
                    ':id'             => $saleId
                ]);

                // Clear previous items
                $db->prepare("DELETE FROM sale_items WHERE sale_id = :sale_id")->execute([':sale_id' => $saleId]);
            } else {
                // Create new held invoice
                $invoiceNo = self::generateInvoiceNumber();
                $stmt = $db->prepare("
                    INSERT INTO sales (
                        invoice_no, branch_id, customer_id, sales_rep_id, user_id, subtotal, 
                        discount_type, discount_value, discount_amount, roundup_amount, 
                        total_amount, total_cost, status, held_at, held_notes
                    ) VALUES (
                        :invoice_no, :branch_id, :customer_id, :sales_rep_id, :user_id, :subtotal,
                        :discount_type, :discount_value, :discount_amount, :roundup_amount,
                        :total_amount, :total_cost, 'held', CURRENT_TIMESTAMP, :held_notes
                    )
                ");

                $stmt->execute([
                    ':invoice_no'     => $invoiceNo,
                    ':branch_id'      => $branchId,
                    ':customer_id'    => $customerId,
                    ':sales_rep_id'   => $salesRepId,
                    ':user_id'        => $userId,
                    ':subtotal'       => $subtotal,
                    ':discount_type'  => $invDiscount['type'],
                    ':discount_value' => $invoiceDiscountStr,
                    ':discount_amount'=> $invDiscount['discount_amount'],
                    ':roundup_amount' => $invDiscount['roundup_amount'],
                    ':total_amount'   => $totalAmount,
                    ':total_cost'     => $totalCost,
                    ':held_notes'     => $heldNotes
                ]);

                $saleId = (int)$db->lastInsertId();
            }

            // Insert sale items
            $itemStmt = $db->prepare("
                INSERT INTO sale_items (
                    sale_id, product_id, lot_id, quantity, original_price, unit_price, 
                    unit_cost, discount_type, discount_value, discount_amount, line_total, line_cost
                ) VALUES (
                    :sale_id, :product_id, :lot_id, :quantity, :original_price, :unit_price,
                    :unit_cost, :discount_type, :discount_value, :discount_amount, :line_total, :line_cost
                )
            ");

            foreach ($processedItems as $pi) {
                $pi['sale_id'] = $saleId;
                $itemStmt->execute($pi);
            }

            $db->commit();

            Audit::log('SALES_HOLD_INVOICE', 'pos', (string)$saleId, null, [
                'invoice_no'   => $invoiceNo,
                'total_amount' => $totalAmount,
                'items_count'  => count($processedItems)
            ]);

            return [
                'success'    => true,
                'message'    => "Invoice {$invoiceNo} placed on hold successfully.",
                'sale_id'    => $saleId,
                'invoice_no' => $invoiceNo
            ];
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to hold invoice: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Database error while holding invoice: ' . $e->getMessage()];
        }
    }

    /**
     * Fetch all held invoices for branch
     */
    public static function getHeldInvoices(int $branchId): array
    {
        try {
            return Database::fetchAll("
                SELECT 
                    s.id AS sale_id,
                    s.invoice_no,
                    s.subtotal,
                    s.discount_amount,
                    s.total_amount,
                    s.held_at,
                    s.held_notes,
                    s.sales_rep_id,
                    rep.full_name AS sales_rep_name,
                    rep.employee_code AS sales_rep_code,
                    c.name AS customer_name,
                    u.full_name AS cashier_name,
                    (SELECT COUNT(*) FROM sale_items si WHERE si.sale_id = s.id) AS item_count
                FROM sales s
                JOIN customers c ON s.customer_id = c.id
                JOIN users u ON s.user_id = u.id
                LEFT JOIN employees rep ON s.sales_rep_id = rep.id
                WHERE s.branch_id = :branch_id AND s.status = 'held'
                ORDER BY s.held_at DESC
            ", [':branch_id' => $branchId]);
        } catch (Throwable $e) {
            Logger::error("Failed to fetch held invoices: " . $e->getMessage(), $e);
            return [];
        }
    }

    /**
     * Fetch sale details with line items for billing screen / resume
     */
    public static function getSaleWithItems(int $saleId, int $branchId): ?array
    {
        try {
            $sale = Database::fetchOne("
                SELECT 
                    s.*,
                    c.name AS customer_name,
                    c.phone AS customer_phone,
                    c.card_barcode AS customer_card_barcode,
                    c.credit_balance AS customer_credit_balance,
                    u.full_name AS cashier_name,
                    b.name AS branch_name,
                    rep.full_name AS sales_rep_name,
                    rep.employee_code AS sales_rep_code
                FROM sales s
                JOIN customers c ON s.customer_id = c.id
                JOIN users u ON s.user_id = u.id
                JOIN branches b ON s.branch_id = b.id
                LEFT JOIN employees rep ON s.sales_rep_id = rep.id
                WHERE s.id = :id AND s.branch_id = :branch_id
            ", [':id' => $saleId, ':branch_id' => $branchId]);

            if (!$sale) {
                return null;
            }

            $items = Database::fetchAll("
                SELECT 
                    si.*,
                    p.name AS product_name,
                    p.barcode,
                    pl.lot_number,
                    pl.remaining_quantity AS lot_remaining_stock
                FROM sale_items si
                JOIN products p ON si.product_id = p.id
                JOIN product_lots pl ON si.lot_id = pl.id
                WHERE si.sale_id = :sale_id
                ORDER BY si.id ASC
            ", [':sale_id' => $saleId]);

            $sale['items'] = $items;
            return $sale;
        } catch (Throwable $e) {
            Logger::error("Failed to fetch sale with items #{$saleId}: " . $e->getMessage(), $e);
            return null;
        }
    }

    /**
     * Cancel an active or held sale
     */
    public static function cancelInvoice(int $saleId, int $userId, int $branchId, string $reason = 'Cancelled by Cashier'): array
    {
        try {
            $sale = Database::fetchOne("
                SELECT invoice_no, status 
                FROM sales 
                WHERE id = :id AND branch_id = :branch_id
            ", [':id' => $saleId, ':branch_id' => $branchId]);

            if (!$sale) {
                return ['success' => false, 'message' => 'Invoice not found.'];
            }

            if ($sale['status'] === 'completed') {
                return ['success' => false, 'message' => 'Completed sales cannot be cancelled directly from billing screen.'];
            }

            Database::execute("
                UPDATE sales SET status = 'cancelled', updated_at = CURRENT_TIMESTAMP 
                WHERE id = :id
            ", [':id' => $saleId]);

            Audit::log('SALES_CANCEL_INVOICE', 'pos', (string)$saleId, null, [
                'invoice_no' => $sale['invoice_no'],
                'reason'     => $reason
            ]);

            return ['success' => true, 'message' => "Invoice {$sale['invoice_no']} has been cancelled."];
        } catch (Throwable $e) {
            Logger::error("Failed to cancel invoice #{$saleId}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Error cancelling invoice: ' . $e->getMessage()];
        }
    }

    /**
     * Prepare POS Sale Transaction Data (Draft status for Stage 7 handoff)
     */
    public static function prepareDraftSale(array $data, int $userId, int $branchId): array
    {
        $db = Database::getInstance()->getConnection();
        try {
            $db->beginTransaction();

            $saleId = !empty($data['sale_id']) ? (int)$data['sale_id'] : null;
            $customerId = !empty($data['customer_id']) ? (int)$data['customer_id'] : 1;
            $salesRepId = !empty($data['sales_rep_id']) ? (int)$data['sales_rep_id'] : null;
            $items = $data['items'] ?? [];

            if ($customerId > 1) {
                $cust = Database::fetchOne("SELECT name, status FROM customers WHERE id = :id AND deleted_at IS NULL", [':id' => $customerId]);
                if (!$cust || $cust['status'] === 'inactive') {
                    $db->rollBack();
                    return ['success' => false, 'message' => "Customer '" . ($cust['name'] ?? 'Selected') . "' is inactive. Cannot create sales for inactive customers."];
                }
            }

            if (empty($items)) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Cart is empty.'];
            }

            $subtotal = 0.00;
            $totalCost = 0.00;
            $processedItems = [];

            foreach ($items as $item) {
                $productId = (int)$item['product_id'];
                $lotId = (int)$item['lot_id'];
                $quantity = (float)$item['quantity'];
                $unitPrice = (float)$item['unit_price'];
                $originalPrice = (float)($item['original_price'] ?? $unitPrice);
                $discountStr = $item['discount_value'] ?? '';

                if ($quantity <= 0) {
                    $db->rollBack();
                    return ['success' => false, 'message' => 'Quantity must be greater than zero.'];
                }

                $lot = Database::fetchOne("
                    SELECT cost, remaining_quantity, expiry_date 
                    FROM product_lots 
                    WHERE id = :lot_id AND branch_id = :branch_id AND status = 'active'
                ", [':lot_id' => $lotId, ':branch_id' => $branchId]);

                if (!$lot || (float)$lot['remaining_quantity'] < $quantity) {
                    $available = $lot ? (float)$lot['remaining_quantity'] : 0;
                    $db->rollBack();
                    return [
                        'success' => false, 
                        'message' => "Stock lot limit exceeded for item ID {$productId}. Requested: {$quantity}, Available: {$available}."
                    ];
                }

                if (!empty($lot['expiry_date']) && $lot['expiry_date'] < date('Y-m-d')) {
                    $db->rollBack();
                    return [
                        'success' => false,
                        'message' => "Selected lot for product ID {$productId} expired on {$lot['expiry_date']} and cannot be sold."
                    ];
                }

                $unitCost = (float)$lot['cost'];
                $lineSubtotal = round($unitPrice * $quantity, 2);
                $itemDiscount = self::parseDiscount($discountStr, $lineSubtotal);

                $lineTotal = round($lineSubtotal - $itemDiscount['total_discount'], 2);
                $lineCost = round($unitCost * $quantity, 2);

                $subtotal += $lineTotal;
                $totalCost += $lineCost;

                $processedItems[] = [
                    'product_id'     => $productId,
                    'lot_id'         => $lotId,
                    'quantity'       => $quantity,
                    'original_price' => $originalPrice,
                    'unit_price'     => $unitPrice,
                    'unit_cost'      => $unitCost,
                    'discount_type'  => $itemDiscount['type'],
                    'discount_value' => $discountStr,
                    'discount_amount'=> $itemDiscount['total_discount'],
                    'line_total'     => $lineTotal,
                    'line_cost'      => $lineCost
                ];
            }

            $invoiceDiscountStr = $data['discount_value'] ?? '';
            $invDiscount = self::parseDiscount($invoiceDiscountStr, $subtotal);
            $totalAmount = round($subtotal - $invDiscount['total_discount'], 2);

            if ($saleId) {
                $sale = Database::fetchOne("SELECT invoice_no FROM sales WHERE id = :id AND branch_id = :branch_id", [':id' => $saleId, ':branch_id' => $branchId]);
                if (!$sale) {
                    $db->rollBack();
                    return ['success' => false, 'message' => 'Sale record not found.'];
                }
                $invoiceNo = $sale['invoice_no'];

                $stmt = $db->prepare("
                    UPDATE sales SET
                        customer_id = :customer_id,
                        sales_rep_id = :sales_rep_id,
                        user_id = :user_id,
                        subtotal = :subtotal,
                        discount_type = :discount_type,
                        discount_value = :discount_value,
                        discount_amount = :discount_amount,
                        roundup_amount = :roundup_amount,
                        total_amount = :total_amount,
                        total_cost = :total_cost,
                        status = 'draft'
                    WHERE id = :id
                ");

                $stmt->execute([
                    ':customer_id'    => $customerId,
                    ':sales_rep_id'   => $salesRepId,
                    ':user_id'        => $userId,
                    ':subtotal'       => $subtotal,
                    ':discount_type'  => $invDiscount['type'],
                    ':discount_value' => $invoiceDiscountStr,
                    ':discount_amount'=> $invDiscount['discount_amount'],
                    ':roundup_amount' => $invDiscount['roundup_amount'],
                    ':total_amount'   => $totalAmount,
                    ':total_cost'     => $totalCost,
                    ':id'             => $saleId
                ]);

                $db->prepare("DELETE FROM sale_items WHERE sale_id = :sale_id")->execute([':sale_id' => $saleId]);
            } else {
                $invoiceNo = self::generateInvoiceNumber();
                $stmt = $db->prepare("
                    INSERT INTO sales (
                        invoice_no, branch_id, customer_id, sales_rep_id, user_id, subtotal, 
                        discount_type, discount_value, discount_amount, roundup_amount, 
                        total_amount, total_cost, status
                    ) VALUES (
                        :invoice_no, :branch_id, :customer_id, :sales_rep_id, :user_id, :subtotal,
                        :discount_type, :discount_value, :discount_amount, :roundup_amount,
                        :total_amount, :total_cost, 'draft'
                    )
                ");

                $stmt->execute([
                    ':invoice_no'     => $invoiceNo,
                    ':branch_id'      => $branchId,
                    ':customer_id'    => $customerId,
                    ':sales_rep_id'   => $salesRepId,
                    ':user_id'        => $userId,
                    ':subtotal'       => $subtotal,
                    ':discount_type'  => $invDiscount['type'],
                    ':discount_value' => $invoiceDiscountStr,
                    ':discount_amount'=> $invDiscount['discount_amount'],
                    ':roundup_amount' => $invDiscount['roundup_amount'],
                    ':total_amount'   => $totalAmount,
                    ':total_cost'     => $totalCost
                ]);

                $saleId = (int)$db->lastInsertId();
            }

            $itemStmt = $db->prepare("
                INSERT INTO sale_items (
                    sale_id, product_id, lot_id, quantity, original_price, unit_price, 
                    unit_cost, discount_type, discount_value, discount_amount, line_total, line_cost
                ) VALUES (
                    :sale_id, :product_id, :lot_id, :quantity, :original_price, :unit_price,
                    :unit_cost, :discount_type, :discount_value, :discount_amount, :line_total, :line_cost
                )
            ");

            foreach ($processedItems as $pi) {
                $pi['sale_id'] = $saleId;
                $itemStmt->execute($pi);
            }

            $db->commit();

            return [
                'success'      => true,
                'message'      => 'Sale draft prepared successfully.',
                'sale_id'      => $saleId,
                'invoice_no'   => $invoiceNo,
                'subtotal'     => $subtotal,
                'total_amount' => $totalAmount,
                'total_cost'   => $totalCost
            ];
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to prepare draft sale: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Error preparing draft sale: ' . $e->getMessage()];
        }
    }

    /**
     * Atomic Sale Finalization (Stage 7)
     * Revalidates stock, posts stock ledger, processes customer credit, redeems gift vouchers, creates payment records.
     * All enclosed in ONE strict database transaction with automatic rollback on failure.
     */
    public static function finalizeSale(int $saleId, array $payments, int $userId, int $branchId): array
    {
        $db = Database::getInstance()->getConnection();
        try {
            if (!$db->inTransaction()) {
                $db->beginTransaction();
            }

            // 1. Fetch & Lock Sale Record
            $sale = Database::fetchOne("
                SELECT s.*, c.name AS customer_name, c.is_walkin, c.status AS customer_status 
                FROM sales s 
                JOIN customers c ON s.customer_id = c.id
                WHERE s.id = :id AND s.branch_id = :branch_id FOR UPDATE
            ", [':id' => $saleId, ':branch_id' => $branchId]);

            if (!$sale) {
                if ($db->inTransaction()) $db->rollBack();
                return ['success' => false, 'message' => 'Sale transaction record not found.'];
            }

            if ($sale['customer_id'] > 1 && $sale['customer_status'] === 'inactive') {
                if ($db->inTransaction()) $db->rollBack();
                return ['success' => false, 'message' => "Customer '{$sale['customer_name']}' is inactive. Cannot complete sales for inactive customers."];
            }

            if ($sale['status'] === 'completed') {
                if ($db->inTransaction()) $db->rollBack();
                return ['success' => false, 'message' => 'Double-finalization prevented: Sale is already completed.'];
            }

            if ($sale['status'] === 'cancelled') {
                if ($db->inTransaction()) $db->rollBack();
                return ['success' => false, 'message' => 'Cannot finalize a cancelled sale invoice.'];
            }

            require_once __DIR__ . '/BusinessDay.php';
            $activeDay = BusinessDay::getOrCreateActiveDay($branchId, $userId);

            $totalPayable = (float)$sale['total_amount'];
            $customerId = (int)$sale['customer_id'];
            $isWalkin = (int)$sale['is_walkin'] === 1 || $customerId === 1;

            if (empty($payments)) {
                if ($db->inTransaction()) $db->rollBack();
                return ['success' => false, 'message' => 'No payment allocation provided.'];
            }

            // 2. Validate Payment Allocations & Gift Vouchers
            $validMethods = ['Cash', 'Card', 'Bank', 'Credit', 'Voucher', 'Koko'];
            $allocatedTotal = 0.00;
            $cashAllocated = 0.00;
            $creditAllocated = 0.00;
            $nonCashTotal = 0.00;
            $processedPayments = [];
            $usedVoucherCodes = [];

            foreach ($payments as $p) {
                $rawMethod = trim($p['payment_method'] ?? '');
                $methodMap = [
                    'cash'          => 'Cash',
                    'card'          => 'Card',
                    'bank'          => 'Bank',
                    'bank_transfer' => 'Bank',
                    'cheque'        => 'Bank',
                    'credit'        => 'Credit',
                    'on_account'    => 'Credit',
                    'voucher'       => 'Voucher',
                    'koko'          => 'Koko',
                    'kokopay'       => 'Koko',
                    'koko_pay'      => 'Koko'
                ];
                $methodKey = strtolower($rawMethod);
                $method = $methodMap[$methodKey] ?? ucfirst($methodKey);

                if (!in_array($method, $validMethods, true)) {
                    if ($db->inTransaction()) $db->rollBack();
                    return ['success' => false, 'message' => "Invalid payment method '{$rawMethod}'. Allowed methods: Cash, Card, Bank, Credit, Voucher, Koko."];
                }

                $amount = (float)($p['amount'] ?? 0);
                $refNo = trim($p['reference_no'] ?? '');
                $bankAccountId = !empty($p['bank_account_id']) ? (int)$p['bank_account_id'] : null;

                if ($amount <= 0) {
                    if ($db->inTransaction()) $db->rollBack();
                    return ['success' => false, 'message' => "Invalid payment amount for method {$method}."];
                }

                // Credit Payment Rule: Requires registered customer & within available credit limit
                if ($method === 'Credit') {
                    if ($isWalkin) {
                        if ($db->inTransaction()) $db->rollBack();
                        return ['success' => false, 'message' => 'Credit payment requires a registered customer. Walk-in Customer cannot receive credit.'];
                    }

                    $custRow = Database::fetchOne("
                        SELECT id, name, credit_limit, credit_balance, status 
                        FROM customers 
                        WHERE id = :id FOR UPDATE
                    ", [':id' => $customerId]);

                    if (!$custRow) {
                        if ($db->inTransaction()) $db->rollBack();
                        return ['success' => false, 'message' => 'Customer record not found for credit allocation.'];
                    }

                    $cLimit = (float)$custRow['credit_limit'];
                    $cBalance = (float)$custRow['credit_balance'];
                    $cAvailable = max(0.00, round($cLimit - $cBalance, 2));

                    if ($cLimit <= 0.00) {
                        if ($db->inTransaction()) $db->rollBack();
                        return [
                            'success' => false, 
                            'message' => "Customer '{$custRow['name']}' has no credit facility (Credit Limit: LKR 0.00). Credit billing is not permitted."
                        ];
                    }

                    if (round($creditAllocated + $amount, 2) > $cAvailable) {
                        if ($db->inTransaction()) $db->rollBack();
                        $formattedAvail = number_format($cAvailable, 2);
                        $formattedLimit = number_format($cLimit, 2);
                        $formattedBal = number_format($cBalance, 2);
                        $formattedReq = number_format($creditAllocated + $amount, 2);
                        return [
                            'success' => false, 
                            'message' => "Credit limit exceeded for customer '{$custRow['name']}'. Available Credit: LKR {$formattedAvail} (Limit: LKR {$formattedLimit}, Outstanding: LKR {$formattedBal}). Cannot allocate LKR {$formattedReq}."
                        ];
                    }

                    $creditAllocated += $amount;
                    $nonCashTotal += $amount;
                } elseif ($method === 'Card' || $method === 'Koko') {
                    $nonCashTotal += $amount;
                } elseif ($method === 'Bank') {
                    if (empty($bankAccountId)) {
                        if ($db->inTransaction()) $db->rollBack();
                        return ['success' => false, 'message' => 'Bank payment requires selecting an active bank account.'];
                    }

                    $bankAcc = Database::fetchOne("
                        SELECT id FROM bank_accounts 
                        WHERE id = :id AND status = 'active' AND (branch_id IS NULL OR branch_id = :branch_id)
                    ", [':id' => $bankAccountId, ':branch_id' => $branchId]);

                    if (!$bankAcc) {
                        if ($db->inTransaction()) $db->rollBack();
                        return ['success' => false, 'message' => 'Selected bank account is invalid, inactive, or not accessible for this branch.'];
                    }

                    $nonCashTotal += $amount;
                }

                $voucherId = null;
                // Gift Voucher Rule: Validate voucher code, expiration, status, duplicate code & remaining payable
                if ($method === 'Voucher') {
                    $codeKey = strtolower($refNo);
                    if (empty($refNo)) {
                        if ($db->inTransaction()) $db->rollBack();
                        return ['success' => false, 'message' => 'Gift voucher code is required.'];
                    }

                    if (in_array($codeKey, $usedVoucherCodes, true)) {
                        if ($db->inTransaction()) $db->rollBack();
                        return ['success' => false, 'message' => "Duplicate gift voucher '{$refNo}' specified in the same transaction."];
                    }

                    require_once __DIR__ . '/GiftVoucher.php';
                    $valRes = GiftVoucher::validateVoucher($refNo, 0.00);
                    if (!$valRes['valid']) {
                        if ($db->inTransaction()) $db->rollBack();
                        return ['success' => false, 'message' => "Gift Voucher Error: " . $valRes['message']];
                    }

                    $voucherId = (int)$valRes['voucher']['id'];
                    $voucherAmount = (float)$valRes['voucher']['amount'];

                    if (round($voucherAmount, 2) !== round($amount, 2)) {
                        if ($db->inTransaction()) $db->rollBack();
                        return ['success' => false, 'message' => "Gift Voucher amount mismatch for '{$refNo}'. Voucher fixed value is LKR " . number_format($voucherAmount, 2) . "."];
                    }

                    $remainingPayableBeforeVoucher = round($totalPayable - $nonCashTotal, 2);
                    if ($voucherAmount > $remainingPayableBeforeVoucher) {
                        if ($db->inTransaction()) $db->rollBack();
                        return [
                            'success' => false, 
                            'message' => "Voucher value (LKR " . number_format($voucherAmount, 2) . ") exceeds remaining invoice payable (LKR " . number_format($remainingPayableBeforeVoucher, 2) . "). Fixed-value vouchers cannot be partially consumed or converted to cash change."
                        ];
                    }

                    $usedVoucherCodes[] = $codeKey;
                    $nonCashTotal += $amount;

                    // Mark voucher redeemed inside this transaction
                    $vStmt = $db->prepare("
                        UPDATE gift_vouchers SET 
                            status = 'redeemed', 
                            redeemed_sale_id = :sale_id, 
                            redeemed_at = CURRENT_TIMESTAMP 
                        WHERE id = :vid AND status = 'active'
                    ");
                    $vStmt->execute([':sale_id' => $saleId, ':vid' => $voucherId]);

                    if ($vStmt->rowCount() === 0) {
                        if ($db->inTransaction()) $db->rollBack();
                        return ['success' => false, 'message' => "Gift Voucher '{$refNo}' could not be redeemed. It may already be redeemed or inactive."];
                    }
                }

                if ($method === 'Cash') {
                    $cashAllocated += $amount;
                }

                $allocatedTotal += $amount;

                $processedPayments[] = [
                    'payment_method'  => $method,
                    'amount'          => $amount,
                    'reference_no'    => $refNo,
                    'bank_account_id' => $bankAccountId,
                    'voucher_id'      => $voucherId,
                    'processor_id'    => !empty($p['processor_id']) ? (int)$p['processor_id'] : null
                ];
            }

            // Check non-cash overpayment rule (Card, Bank, Credit, Voucher overpayment is rejected)
            if (round($nonCashTotal, 2) > round($totalPayable, 2)) {
                if ($db->inTransaction()) $db->rollBack();
                return ['success' => false, 'message' => "Non-cash payment total (LKR " . number_format($nonCashTotal, 2) . ") exceeds total invoice payable (LKR " . number_format($totalPayable, 2) . "). Overpayment is only permitted for Cash."];
            }

            if ($cashAllocated > 0 && !$activeDay) {
                if ($db->inTransaction()) $db->rollBack();
                return ['success' => false, 'message' => 'Cannot complete cash sale. No active business day is currently open for this branch.'];
            }

            if (round($allocatedTotal, 2) < round($totalPayable, 2)) {
                if ($db->inTransaction()) $db->rollBack();
                $diff = $totalPayable - $allocatedTotal;
                return ['success' => false, 'message' => "Insufficient payment. Remaining due: LKR " . number_format($diff, 2)];
            }

            $changeAmount = max(0.00, round($allocatedTotal - $totalPayable, 2));

            // 3. Fetch Line Items and Revalidate Stock Lots
            $items = Database::fetchAll("
                SELECT * FROM sale_items WHERE sale_id = :sale_id FOR UPDATE
            ", [':sale_id' => $saleId]);

            if (empty($items)) {
                if ($db->inTransaction()) $db->rollBack();
                return ['success' => false, 'message' => 'Sale invoice has no line items.'];
            }

            foreach ($items as $item) {
                $lotId = (int)$item['lot_id'];
                $productId = (int)$item['product_id'];
                $qty = (float)$item['quantity'];

                // Revalidate product lot stock
                $lot = Database::fetchOne("
                    SELECT remaining_quantity, expiry_date 
                    FROM product_lots 
                    WHERE id = :lot_id AND branch_id = :branch_id FOR UPDATE
                ", [':lot_id' => $lotId, ':branch_id' => $branchId]);

                if (!$lot || (float)$lot['remaining_quantity'] < $qty) {
                    $avail = $lot ? (float)$lot['remaining_quantity'] : 0;
                    if ($db->inTransaction()) $db->rollBack();
                    return ['success' => false, 'message' => "Insufficient lot stock for product #{$productId}. Required: {$qty}, Available: {$avail}."];
                }

                if (!empty($lot['expiry_date']) && $lot['expiry_date'] < date('Y-m-d')) {
                    if ($db->inTransaction()) $db->rollBack();
                    return ['success' => false, 'message' => "Stock lot #{$lotId} for product #{$productId} expired on {$lot['expiry_date']}. Expired stock cannot be sold."];
                }

                // Deduct Product Lot Stock
                $newLotQty = (float)$lot['remaining_quantity'] - $qty;
                $lotStatus = $newLotQty <= 0 ? 'depleted' : 'active';
                $db->prepare("
                    UPDATE product_lots SET remaining_quantity = :rem, status = :status WHERE id = :id
                ")->execute([':rem' => $newLotQty, ':status' => $lotStatus, ':id' => $lotId]);

                // Deduct Branch Inventory Stock
                $bi = Database::fetchOne("
                    SELECT quantity FROM branch_inventory WHERE branch_id = :branch_id AND product_id = :product_id FOR UPDATE
                ", [':branch_id' => $branchId, ':product_id' => $productId]);

                $currentBiQty = $bi ? (float)$bi['quantity'] : 0.00;
                $newBiQty = max(0.00, $currentBiQty - $qty);

                $db->prepare("
                    UPDATE branch_inventory SET quantity = :qty WHERE branch_id = :branch_id AND product_id = :product_id
                ")->execute([':qty' => $newBiQty, ':branch_id' => $branchId, ':product_id' => $productId]);

                // Auto-deactivate product if total stock drops to 0 upon billing
                $stockRow = Database::fetchOne("
                    SELECT COALESCE(SUM(quantity), 0) AS total_stock 
                    FROM branch_inventory 
                    WHERE product_id = :product_id
                ", [':product_id' => $productId]);

                $totalStock = (float)($stockRow['total_stock'] ?? 0);
                if ($totalStock <= 0) {
                    $db->prepare("
                        UPDATE products 
                        SET status = 'inactive', updated_at = CURRENT_TIMESTAMP 
                        WHERE id = :product_id AND status = 'active'
                    ")->execute([':product_id' => $productId]);

                    Audit::log('PRODUCT_AUTO_DEACTIVATED', 'products', (string)$productId, [
                        'status' => 'active'
                    ], [
                        'status' => 'inactive',
                        'reason' => "Stock depleted to 0 upon POS billing (Invoice #{$sale['invoice_no']})"
                    ]);
                }

                // Record Stock Movement Ledger Entry (Append-only)
                $db->prepare("
                    INSERT INTO stock_ledger (branch_id, product_id, lot_id, movement_type, quantity, balance_after, reference_no, notes, user_id)
                    VALUES (:branch_id, :product_id, :lot_id, 'sale', :quantity, :balance_after, :reference_no, :notes, :user_id)
                ")->execute([
                    ':branch_id'    => $branchId,
                    ':product_id'   => $productId,
                    ':lot_id'       => $lotId,
                    ':quantity'     => -$qty,
                    ':balance_after'=> $newBiQty,
                    ':reference_no' => $sale['invoice_no'],
                    ':notes'        => "POS Sale Finalization Invoice #{$sale['invoice_no']}",
                    ':user_id'      => $userId
                ]);
            }

            // 4. Record Customer Outstanding for Credit Portion
            if ($creditAllocated > 0) {
                $cust = Database::fetchOne("
                    SELECT credit_balance FROM customers WHERE id = :id FOR UPDATE
                ", [':id' => $customerId]);

                $currentBal = $cust ? (float)$cust['credit_balance'] : 0.00;
                $newBal = $currentBal + $creditAllocated;

                $db->prepare("
                    UPDATE customers SET credit_balance = :bal WHERE id = :id
                ")->execute([':bal' => $newBal, ':id' => $customerId]);

                $nonCreditPaid = $totalPayable - $creditAllocated;

                $db->prepare("
                    INSERT INTO customer_credit_ledger (
                        customer_id, transaction_type, reference_no, invoice_total, 
                        amount_paid, amount_due, balance_after, notes, branch_id, user_id
                    ) VALUES (
                        :customer_id, 'credit_sale', :reference_no, :invoice_total,
                        :amount_paid, :amount_due, :balance_after, :notes, :branch_id, :user_id
                    )
                ")->execute([
                    ':customer_id'  => $customerId,
                    ':reference_no' => $sale['invoice_no'],
                    ':invoice_total'=> $totalPayable,
                    ':amount_paid'  => $nonCreditPaid,
                    ':amount_due'   => $creditAllocated,
                    ':balance_after'=> $newBal,
                    ':notes'        => "POS Credit Sale Invoice #{$sale['invoice_no']}",
                    ':branch_id'    => $branchId,
                    ':user_id'      => $userId
                ]);
            }

            // 5. Create Payment Records
            $payStmt = $db->prepare("
                INSERT INTO payments (payment_no, sale_id, branch_id, bank_account_id, processor_id, customer_id, payment_method, amount, reference_no, voucher_id, user_id)
                VALUES (:payment_no, :sale_id, :branch_id, :bank_account_id, :processor_id, :customer_id, :payment_method, :amount, :reference_no, :voucher_id, :user_id)
            ");

            foreach ($processedPayments as $pp) {
                $paymentNo = 'PAY-' . date('Ymd') . '-' . sprintf('%04d', rand(1000, 9999)) . substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4);
                $payStmt->execute([
                    ':payment_no'     => $paymentNo,
                    ':sale_id'        => $saleId,
                    ':branch_id'      => $branchId,
                    ':bank_account_id'=> $pp['bank_account_id'],
                    ':processor_id'   => $pp['processor_id'],
                    ':customer_id'    => $customerId,
                    ':payment_method' => $pp['payment_method'],
                    ':amount'         => $pp['amount'],
                    ':reference_no'   => $pp['reference_no'],
                    ':voucher_id'     => $pp['voucher_id'],
                    ':user_id'        => $userId
                ]);
            }


            // 5.5 Record Cash Drawer Movement for Cash Portion (net retained cash)
            $netCash = round(max(0.00, $cashAllocated - $changeAmount), 2);
            if ($netCash > 0 && $activeDay) {
                BusinessDay::recordDrawerMovement([
                    'business_day_id' => (int)$activeDay['id'],
                    'branch_id'       => $branchId,
                    'movement_type'   => 'pos_sale',
                    'source_type'     => 'sales',
                    'source_id'       => $saleId,
                    'amount'          => $netCash,
                    'direction'       => 'in',
                    'reason'          => "POS Cash Sale Invoice #{$sale['invoice_no']}",
                    'user_id'         => $userId
                ]);
            }

            // 6. Mark Invoice Completed (preserve or update sales_rep_id if passed)
            $finalSalesRepId = !empty($payments['sales_rep_id']) ? (int)$payments['sales_rep_id'] : (!empty($sale['sales_rep_id']) ? (int)$sale['sales_rep_id'] : null);
            $db->prepare("
                UPDATE sales SET 
                    status = 'completed', 
                    sales_rep_id = :sales_rep_id,
                    updated_at = CURRENT_TIMESTAMP 
                WHERE id = :id
            ")->execute([':sales_rep_id' => $finalSalesRepId, ':id' => $saleId]);

            // 7. Atomic Accounting Posting inside transaction before commit
            require_once __DIR__ . '/Accounting.php';
            $accRes = Accounting::postPOSSale($saleId);

            if (empty($accRes['success'])) {
                if ($db->inTransaction()) $db->rollBack();
                return ['success' => false, 'message' => 'Accounting Journal Error: ' . ($accRes['message'] ?? 'Failed to post accounting journal.')];
            }

            $db->commit();

            Audit::log('SALES_FINALIZED', 'pos', (string)$saleId, null, [
                'invoice_no'    => $sale['invoice_no'],
                'total_amount'  => $totalPayable,
                'allocated'     => $allocatedTotal,
                'change_amount' => $changeAmount,
                'credit_amount' => $creditAllocated
            ]);

            return [
                'success'       => true,
                'message'       => "Sale invoice {$sale['invoice_no']} finalized successfully.",
                'sale_id'       => $saleId,
                'invoice_no'    => $sale['invoice_no'],
                'total_amount'  => $totalPayable,
                'change_amount' => $changeAmount
            ];
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to finalize sale #{$saleId}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Transaction Rollback Error: ' . $e->getMessage()];
        }
    }

    /**
     * Get 88mm Xprinter-friendly receipt view data
     */
    public static function getReceiptData(int $saleId): ?array
    {
        try {
            $sale = Database::fetchOne("
                SELECT 
                    s.*,
                    c.name AS customer_name,
                    c.phone AS customer_phone,
                    c.card_barcode AS customer_card_barcode,
                    u.full_name AS cashier_name,
                    b.name AS branch_name,
                    b.address AS branch_address,
                    b.phone AS branch_phone,
                    rep.full_name AS sales_rep_name,
                    rep.employee_code AS sales_rep_code
                FROM sales s
                JOIN customers c ON s.customer_id = c.id
                JOIN users u ON s.user_id = u.id
                JOIN branches b ON s.branch_id = b.id
                LEFT JOIN employees rep ON s.sales_rep_id = rep.id
                WHERE s.id = :id
            ", [':id' => $saleId]);

            if (!$sale) {
                return null;
            }

            $items = Database::fetchAll("
                SELECT 
                    si.*,
                    p.name AS product_name,
                    p.barcode
                FROM sale_items si
                JOIN products p ON si.product_id = p.id
                WHERE si.sale_id = :sale_id
                ORDER BY si.id ASC
            ", [':sale_id' => $saleId]);

            $payments = Database::fetchAll("
                SELECT * FROM payments WHERE sale_id = :sale_id ORDER BY id ASC
            ", [':sale_id' => $saleId]);

            $totalAllocated = 0.00;
            foreach ($payments as $p) {
                $totalAllocated += (float)$p['amount'];
            }

            $changeAmount = max(0.00, $totalAllocated - (float)$sale['total_amount']);

            // Format customer facing discount text (preserves percentage part only)
            $invDiscountParsed = self::parseDiscount($sale['discount_value'], (float)$sale['subtotal']);

            return [
                'sale'                    => $sale,
                'items'                   => $items,
                'payments'                => $payments,
                'customer_facing_discount'=> $invDiscountParsed['customer_facing_text'],
                'total_allocated'         => $totalAllocated,
                'change_amount'           => $changeAmount
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to fetch receipt data for sale #{$saleId}: " . $e->getMessage(), $e);
            return null;
        }
    }

    /**
     * Stage 18 Offline POS Sale Atomic Synchronization
     * Idempotent, re-validates server lot stock, handles conflicts cleanly, generates final invoice number.
     */
    public static function syncOfflineSale(array $payload, int $userId, int $branchId): array
    {
        $db = Database::getInstance()->getConnection();
        try {
            $userRoleRow = Database::fetchOne("
                SELECT r.code AS role_code 
                FROM users u 
                JOIN roles r ON u.role_id = r.id 
                WHERE u.id = :uid
            ", [':uid' => $userId]);

            if (($userRoleRow['role_code'] ?? '') === ROLE_SPECIAL_USER || Auth::isSpecialUser()) {
                return ['success' => false, 'message' => 'Special User is view-only and restricted from performing offline sale sync.'];
            }

            $offlineUuid = trim((string)($payload['offline_uuid'] ?? ''));
            if (empty($offlineUuid)) {
                return ['success' => false, 'message' => 'Missing required offline_uuid parameter.'];
            }

            // 1. Idempotency Check (Prevent duplicate sales/stock deductions/accounting)
            $existing = Database::fetchOne("
                SELECT id, invoice_no, total_amount, status 
                FROM sales 
                WHERE offline_uuid = :uuid LIMIT 1
            ", [':uuid' => $offlineUuid]);

            if ($existing) {
                return [
                    'success'        => true,
                    'already_synced' => true,
                    'sale_id'        => (int)$existing['id'],
                    'invoice_no'     => $existing['invoice_no'],
                    'status'         => 'Synced',
                    'message'        => "Offline sale already synchronized (Invoice #{$existing['invoice_no']})."
                ];
            }

            // 2. Extract Payments (Supporting both structured payments array and top-level payment_method/amount_paid)
            $payments = $payload['payments'] ?? [];
            if (empty($payments) && (!empty($payload['payment_method']) || !empty($payload['payment_type']))) {
                $payments = [
                    [
                        'payment_method' => $payload['payment_method'] ?? $payload['payment_type'] ?? 'Cash',
                        'amount'         => (float)($payload['amount_paid'] ?? $payload['amount'] ?? 0),
                        'reference_no'   => $payload['reference_no'] ?? $payload['offline_uuid'] ?? ''
                    ]
                ];
            }

            foreach ($payments as $p) {
                $method = strtolower(trim($p['payment_method'] ?? ''));
                if ($method === 'voucher') {
                    return [
                        'success'  => false,
                        'conflict' => true,
                        'status'   => 'Conflict',
                        'message'  => 'Gift vouchers require an online connection and cannot be redeemed offline.'
                    ];
                }
            }

            // 2.5 Business Day Integrity Validation (Closed-Day Immutability Protection)
            $bdayId = !empty($payload['business_day_id']) ? (int)$payload['business_day_id'] : null;
            $createdAt = !empty($payload['created_at']) ? $payload['created_at'] : (!empty($payload['local_created_at']) ? $payload['local_created_at'] : date('Y-m-d H:i:s'));
            $transDate = date('Y-m-d', strtotime($createdAt));

            require_once __DIR__ . '/BusinessDay.php';
            $activeDay = BusinessDay::getOrCreateActiveDay($branchId, $userId);

            if ($bdayId !== null && $bdayId > 0) {
                $targetBday = Database::fetchOne("
                    SELECT id, branch_id, business_date, status FROM business_days WHERE id = :id
                ", [':id' => $bdayId]);

                if (!$targetBday || (int)$targetBday['branch_id'] !== $branchId) {
                    return [
                        'success'  => false,
                        'conflict' => true,
                        'status'   => 'Conflict',
                        'reason'   => 'BUSINESS_DAY_MISMATCH',
                        'message'  => "Original business day #{$bdayId} is invalid or belongs to another branch."
                    ];
                }

                if ($targetBday['status'] === 'closed') {
                    return [
                        'success'          => false,
                        'conflict'         => true,
                        'status'           => 'Conflict',
                        'reason'           => 'BUSINESS_DAY_CLOSED',
                        'message'          => "Offline sale belongs to Business Day #{$bdayId} which is already closed. Closed business days are immutable and late automatic synchronization into open business days is forbidden.",
                        'conflict_details' => [
                            'offline_uuid'     => $offlineUuid,
                            'temp_ref'         => $payload['temp_ref'] ?? '',
                            'business_day_id'  => $bdayId,
                            'branch_id'        => $branchId,
                            'local_created_at' => $createdAt,
                            'reason'           => 'BUSINESS_DAY_CLOSED'
                        ]
                    ];
                }
            } else {
                // Legacy or timestamp-only offline payload:
                if ($activeDay && ($activeDay['business_date'] === $transDate || empty($payload['created_at']))) {
                    $bdayId = (int)$activeDay['id'];
                } else {
                    // Check if creation date belongs to a closed business day
                    $closedBday = Database::fetchOne("
                        SELECT id, status FROM business_days 
                        WHERE branch_id = :bid AND business_date = :bdate AND status = 'closed' LIMIT 1
                    ", [':bid' => $branchId, ':bdate' => $transDate]);

                    if ($closedBday) {
                        return [
                            'success'          => false,
                            'conflict'         => true,
                            'status'           => 'Conflict',
                            'reason'           => 'BUSINESS_DAY_CLOSED',
                            'message'          => "Offline sale created for date {$transDate} belongs to closed Business Day #{$closedBday['id']}. Late sync into closed business days is forbidden.",
                            'conflict_details' => [
                                'offline_uuid'     => $offlineUuid,
                                'temp_ref'         => $payload['temp_ref'] ?? '',
                                'business_day_id'  => (int)$closedBday['id'],
                                'branch_id'        => $branchId,
                                'local_created_at' => $createdAt,
                                'reason'           => 'BUSINESS_DAY_CLOSED'
                            ]
                        ];
                    }

                    if ($activeDay) {
                        $bdayId = (int)$activeDay['id'];
                    }
                }
            }

            $items = $payload['items'] ?? [];
            if (empty($items)) {
                return ['success' => false, 'message' => 'Offline sale payload contains no line items.'];
            }

            // 3. Revalidate Server Lot Stock (Conflict Strategy)
            foreach ($items as $it) {
                $lotId  = (int)($it['lot_id'] ?? 0);
                $prodId = (int)($it['product_id'] ?? 0);
                $reqQty = (float)($it['quantity'] ?? 0);

                $lot = Database::fetchOne("
                    SELECT id, product_id, remaining_quantity, status 
                    FROM product_lots 
                    WHERE id = :lot_id AND branch_id = :branch_id AND status = 'active'
                ", [':lot_id' => $lotId, ':branch_id' => $branchId]);

                $prodRow = Database::fetchOne("SELECT name FROM products WHERE id = :id", [':id' => $prodId]);
                $prodName = $prodRow ? $prodRow['name'] : "Product #{$prodId}";

                if (!$lot || (float)$lot['remaining_quantity'] < $reqQty) {
                    $availQty = $lot ? (float)$lot['remaining_quantity'] : 0.00;
                    return [
                        'success'  => false,
                        'conflict' => true,
                        'status'   => 'Conflict',
                        'message'  => "Insufficient stock for lot #{$lotId} of '{$prodName}'. Server has {$availQty} units available, but offline sale requested {$reqQty} units.",
                        'conflict_details' => [
                            'product_id'        => $prodId,
                            'product_name'      => $prodName,
                            'lot_id'            => $lotId,
                            'requested_quantity' => $reqQty,
                            'available_quantity' => $availQty
                        ]
                    ];
                }
            }

            // 4. Atomic DB Transaction
            if (!$db->inTransaction()) {
                $db->beginTransaction();
            }

            $customerId = (int)($payload['customer_id'] ?? 1);
            $finalInvoiceNo = self::generateInvoiceNumber();

            $subtotal = 0.00;
            $totalCost = 0.00;
            $itemsToInsert = [];

            foreach ($items as $it) {
                $pId   = (int)$it['product_id'];
                $lId   = (int)$it['lot_id'];
                $q     = (float)$it['quantity'];
                $price = (float)($it['unit_price'] ?? $it['selling_price'] ?? 0);
                
                // Server ALWAYS obtains exact selected lot cost from authoritative product_lots table (Client-supplied cost ignored for COGS security)
                $lRow = Database::fetchOne("SELECT cost FROM product_lots WHERE id = :id", [':id' => $lId]);
                $cost = $lRow ? (float)$lRow['cost'] : 0.00;

                $discAmt = (float)($it['discount_amount'] ?? 0);
                $itemSubtotal = round(($price * $q) - $discAmt, 2);

                $subtotal += $itemSubtotal;
                $totalCost += round($cost * $q, 2);

                $itemsToInsert[] = [
                    'product_id'      => $pId,
                    'lot_id'          => $lId,
                    'quantity'        => $q,
                    'unit_price'      => $price,
                    'unit_cost'       => $cost,
                    'discount_amount' => $discAmt,
                    'subtotal'        => $itemSubtotal
                ];
            }

            $discountStr = trim((string)($payload['discount_str'] ?? ''));
            $parsedDisc = self::parseDiscount($discountStr, $subtotal);
            $totalAmount = round($subtotal - $parsedDisc['total_discount'], 2);

            // Pre-Validate Payment Allocations & Business Rules
            $validMethods = ['Cash', 'Card', 'Bank', 'Credit'];
            $allocatedTotal = 0.00;
            $cashAllocated = 0.00;
            $creditAllocated = 0.00;
            $nonCashTotal = 0.00;

            foreach ($payments as $pm) {
                $pmName = ucfirst(strtolower(trim($pm['payment_method'] ?? 'Cash')));
                $pmAmt  = (float)($pm['amount'] ?? 0);

                if (!in_array($pmName, $validMethods, true)) {
                    return ['success' => false, 'message' => "Invalid payment method '{$pmName}' specified for offline sale."];
                }

                if ($pmName === 'Credit') {
                    if ($customerId === 1) {
                        return ['success' => false, 'message' => 'Credit payment not allowed for Walk-in customer.'];
                    }
                    $custRow = Database::fetchOne("SELECT id, name, credit_limit, credit_balance FROM customers WHERE id = :id", [':id' => $customerId]);
                    if (!$custRow) {
                        return ['success' => false, 'message' => 'Customer record not found for offline credit allocation.'];
                    }
                    $cLimit = (float)$custRow['credit_limit'];
                    $cBalance = (float)$custRow['credit_balance'];
                    $cAvailable = max(0.00, round($cLimit - $cBalance, 2));
                    if ($cLimit <= 0.00 || round($creditAllocated + $pmAmt, 2) > $cAvailable) {
                        $formattedAvail = number_format($cAvailable, 2);
                        return ['success' => false, 'message' => "Credit limit exceeded for offline sync on customer '{$custRow['name']}'. Available Credit: LKR {$formattedAvail}."];
                    }
                    $creditAllocated += $pmAmt;
                    $nonCashTotal += $pmAmt;
                } elseif ($pmName === 'Card' || $pmName === 'Bank') {
                    $nonCashTotal += $pmAmt;
                } elseif ($pmName === 'Cash') {
                    $cashAllocated += $pmAmt;
                }

                $allocatedTotal += $pmAmt;
            }

            if (round($nonCashTotal, 2) > round($totalAmount, 2)) {
                return ['success' => false, 'message' => "Non-cash payment total (LKR " . number_format($nonCashTotal, 2) . ") exceeds total invoice payable (LKR " . number_format($totalAmount, 2) . "). Overpayment is only permitted for Cash."];
            }

            if (round($allocatedTotal, 2) < round($totalAmount, 2)) {
                $diff = round($totalAmount - $allocatedTotal, 2);
                return ['success' => false, 'message' => "Insufficient payment allocation for offline sale. Remaining due: LKR " . number_format($diff, 2)];
            }

            // 4. Atomic DB Transaction
            if (!$db->inTransaction()) {
                $db->beginTransaction();
            }

            $finalInvoiceNo = self::generateInvoiceNumber();

            $stmtSale = $db->prepare("
                INSERT INTO sales (
                    invoice_no, offline_uuid, branch_id, customer_id, sales_rep_id, user_id, 
                    subtotal, discount_type, discount_value, discount_amount, 
                    roundup_amount, total_amount, total_cost, status, created_at
                ) VALUES (
                    :invoice_no, :offline_uuid, :branch_id, :customer_id, :sales_rep_id, :user_id,
                    :subtotal, :discount_type, :discount_value, :discount_amount,
                    :roundup_amount, :total_amount, :total_cost, 'completed', :created_at
                )
            ");

            $createdAt = !empty($payload['created_at']) ? $payload['created_at'] : date('Y-m-d H:i:s');

            $stmtSale->execute([
                ':invoice_no'      => $finalInvoiceNo,
                ':offline_uuid'    => $offlineUuid,
                ':branch_id'       => $branchId,
                ':customer_id'     => $customerId,
                ':sales_rep_id'    => !empty($payload['sales_rep_id']) ? (int)$payload['sales_rep_id'] : null,
                ':user_id'         => $userId,
                ':subtotal'        => $subtotal,
                ':discount_type'   => $parsedDisc['type'],
                ':discount_value'  => $parsedDisc['raw_value'],
                ':discount_amount' => $parsedDisc['discount_amount'],
                ':roundup_amount'  => $parsedDisc['roundup_amount'],
                ':total_amount'    => $totalAmount,
                ':total_cost'      => $totalCost,
                ':created_at'      => $createdAt
            ]);

            $newSaleId = (int)$db->lastInsertId();

            $stmtItem = $db->prepare("
                INSERT INTO sale_items (sale_id, product_id, lot_id, quantity, original_price, unit_price, unit_cost, discount_amount, line_total, line_cost)
                VALUES (:sale_id, :product_id, :lot_id, :quantity, :original_price, :unit_price, :unit_cost, :discount_amount, :line_total, :line_cost)
            ");

            foreach ($itemsToInsert as $iti) {
                $lineTotal = round($iti['subtotal'], 2);
                $lineCost  = round($iti['quantity'] * $iti['unit_cost'], 2);

                $stmtItem->execute([
                    ':sale_id'        => $newSaleId,
                    ':product_id'     => $iti['product_id'],
                    ':lot_id'         => $iti['lot_id'],
                    ':quantity'       => $iti['quantity'],
                    ':original_price' => $iti['unit_price'],
                    ':unit_price'     => $iti['unit_price'],
                    ':unit_cost'      => $iti['unit_cost'],
                    ':discount_amount'=> $iti['discount_amount'],
                    ':line_total'     => $lineTotal,
                    ':line_cost'      => $lineCost
                ]);

                // Deduct Lot Stock
                $db->prepare("
                    UPDATE product_lots 
                    SET remaining_quantity = remaining_quantity - :qty,
                        status = IF(remaining_quantity <= 0, 'depleted', 'active')
                    WHERE id = :id
                ")->execute([':qty' => $iti['quantity'], ':id' => $iti['lot_id']]);

                // Deduct Branch Inventory Stock
                $biRow = Database::fetchOne("SELECT quantity FROM branch_inventory WHERE branch_id = :b AND product_id = :p", [':b' => $branchId, ':p' => $iti['product_id']]);
                $curBi = $biRow ? (float)$biRow['quantity'] : 0.0;
                $newBi = max(0.00, $curBi - $iti['quantity']);

                $db->prepare("UPDATE branch_inventory SET quantity = :q WHERE branch_id = :b AND product_id = :p")
                   ->execute([':q' => $newBi, ':b' => $branchId, ':p' => $iti['product_id']]);

                // Auto-deactivate product if total stock drops to 0 upon billing
                $stockRow = Database::fetchOne("
                    SELECT COALESCE(SUM(quantity), 0) AS total_stock 
                    FROM branch_inventory 
                    WHERE product_id = :product_id
                ", [':product_id' => $iti['product_id']]);

                $totalStock = (float)($stockRow['total_stock'] ?? 0);
                if ($totalStock <= 0) {
                    $db->prepare("
                        UPDATE products 
                        SET status = 'inactive', updated_at = CURRENT_TIMESTAMP 
                        WHERE id = :product_id AND status = 'active'
                    ")->execute([':product_id' => $iti['product_id']]);

                    Audit::log('PRODUCT_AUTO_DEACTIVATED', 'products', (string)$iti['product_id'], [
                        'status' => 'active'
                    ], [
                        'status' => 'inactive',
                        'reason' => "Stock depleted to 0 upon offline POS billing sync (Invoice #{$finalInvoiceNo})"
                    ]);
                }

                // Stock Ledger Entry
                $db->prepare("
                    INSERT INTO stock_ledger (branch_id, product_id, lot_id, movement_type, quantity, balance_after, reference_no, notes, user_id)
                    VALUES (:b, :p, :l, 'sale', :q, :bal, :ref, :notes, :u)
                ")->execute([
                    ':b'     => $branchId,
                    ':p'     => $iti['product_id'],
                    ':l'     => $iti['lot_id'],
                    ':q'     => -$iti['quantity'],
                    ':bal'   => $newBi,
                    ':ref'   => $finalInvoiceNo,
                    ':notes' => "Offline POS Sale Sync Invoice #{$finalInvoiceNo}",
                    ':u'     => $userId
                ]);
            }

            // Process Payments
            $payStmt = $db->prepare("
                INSERT INTO payments (payment_no, sale_id, branch_id, customer_id, payment_method, amount, reference_no, user_id)
                VALUES (:pno, :sid, :bid, :cid, :pmethod, :amt, :ref, :uid)
            ");

            foreach ($payments as $pm) {
                $pmName = ucfirst(strtolower(trim($pm['payment_method'] ?? 'Cash')));
                $pmAmt  = (float)($pm['amount'] ?? 0);
                $pmRef  = trim($pm['reference_no'] ?? '');

                $pNo = 'PAY-OFF-' . date('Ymd') . '-' . sprintf('%04d', rand(1000, 9999)) . substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4);
                $payStmt->execute([
                    ':pno'     => $pNo,
                    ':sid'     => $newSaleId,
                    ':bid'     => $branchId,
                    ':cid'     => $customerId,
                    ':pmethod' => $pmName,
                    ':amt'     => $pmAmt,
                    ':ref'     => $pmRef ?: $offlineUuid,
                    ':uid'     => $userId
                ]);
            }

            if ($creditAllocated > 0) {
                $custRow = Database::fetchOne("SELECT credit_balance FROM customers WHERE id = :id FOR UPDATE", [':id' => $customerId]);
                $curBal = $custRow ? (float)$custRow['credit_balance'] : 0.00;
                $newBal = $curBal + $creditAllocated;

                $db->prepare("UPDATE customers SET credit_balance = :bal WHERE id = :id")->execute([':bal' => $newBal, ':id' => $customerId]);

                $db->prepare("
                    INSERT INTO customer_credit_ledger (customer_id, transaction_type, reference_no, invoice_total, amount_paid, amount_due, balance_after, notes, branch_id, user_id)
                    VALUES (:cid, 'credit_sale', :ref, :tot, :paid, :due, :bal, :notes, :bid, :uid)
                ")->execute([
                    ':cid'  => $customerId,
                    ':ref'  => $finalInvoiceNo,
                    ':tot'  => $totalAmount,
                    ':paid' => max(0.00, $totalAmount - $creditAllocated),
                    ':due'  => $creditAllocated,
                    ':bal'  => $newBal,
                    ':notes'=> "Offline Credit Sale Sync Invoice #{$finalInvoiceNo}",
                    ':bid'  => $branchId,
                    ':uid'  => $userId
                ]);
            }

            require_once __DIR__ . '/BusinessDay.php';
            $activeDay = BusinessDay::getOrCreateActiveDay($branchId, $userId);
            if ($cashAllocated > 0 && $activeDay) {
                BusinessDay::recordDrawerMovement([
                    'business_day_id' => (int)$activeDay['id'],
                    'branch_id'       => $branchId,
                    'movement_type'   => 'pos_sale',
                    'source_type'     => 'sales',
                    'source_id'       => $newSaleId,
                    'amount'          => $cashAllocated,
                    'direction'       => 'in',
                    'reason'          => "Offline POS Cash Sale Sync Invoice #{$finalInvoiceNo}",
                    'user_id'         => $userId
                ]);
            }

            $db->commit();

            // Post Stage 10 Double Entry GL Journal
            require_once __DIR__ . '/Accounting.php';
            Accounting::postPOSSale($newSaleId);

            Audit::log('OFFLINE_SALE_SYNCED', 'pos', (string)$newSaleId, null, [
                'offline_uuid' => $offlineUuid,
                'invoice_no'   => $finalInvoiceNo,
                'total_amount' => $totalAmount
            ]);

            return [
                'success'        => true,
                'already_synced' => false,
                'sale_id'        => $newSaleId,
                'invoice_no'     => $finalInvoiceNo,
                'status'         => 'Synced',
                'message'        => "Offline sale synchronized successfully as Invoice #{$finalInvoiceNo}."
            ];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Offline sale sync failed for UUID {$payload['offline_uuid']}: " . $e->getMessage(), $e);
            return [
                'success' => false,
                'status'  => 'Failed',
                'message' => 'Sync Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get paginated sales list for Sales History view
     */
    public static function getList(int $branchId, array $filters = [], int $page = 1, int $limit = 25): array
    {
        try {
            $where = [];
            $params = [];

            // Branch filter
            if ($branchId > 0 && empty($filters['all_branches'])) {
                $where[] = "s.branch_id = :branch_id";
                $params[':branch_id'] = $branchId;
            }

            // Status filter
            if (!empty($filters['status'])) {
                $where[] = "s.status = :status";
                $params[':status'] = $filters['status'];
            }

            // Date Range
            if (!empty($filters['date_from'])) {
                $where[] = "s.created_at >= :date_from";
                $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
            }
            if (!empty($filters['date_to'])) {
                $where[] = "s.created_at <= :date_to";
                $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
            }

            // Customer filter
            if (!empty($filters['customer_id'])) {
                $where[] = "s.customer_id = :customer_id";
                $params[':customer_id'] = (int)$filters['customer_id'];
            }

            // Multi-column search filter
            if (!empty($filters['search'])) {
                $search = trim($filters['search']);
                $cleanSearch = ltrim($search, '#');
                $where[] = "(
                    s.invoice_no LIKE :s1 OR
                    c.name LIKE :s2 OR
                    c.phone LIKE :s3 OR
                    u.username LIKE :s4 OR
                    u.full_name LIKE :s5 OR
                    CAST(s.total_amount AS CHAR) LIKE :s6
                )";
                $params[':s1'] = $cleanSearch . '%';
                $params[':s2'] = $search . '%';
                $params[':s3'] = $search . '%';
                $params[':s4'] = $search . '%';
                $params[':s5'] = $search . '%';
                $params[':s6'] = $search . '%';
            }

            $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

            // Count total items
            $countSql = "
                SELECT COUNT(s.id) AS total
                FROM sales s
                LEFT JOIN customers c ON s.customer_id = c.id
                LEFT JOIN users u ON s.user_id = u.id
                $whereSql
            ";
            $totalRow = Database::fetchOne($countSql, $params);
            $totalItems = (int)($totalRow['total'] ?? 0);

            $page = max(1, $page);
            $limit = in_array($limit, [25, 50, 75, 100, 500], true) ? $limit : 25;
            $totalPages = max(1, (int)ceil($totalItems / $limit));
            if ($page > $totalPages && $totalPages > 0) {
                $page = $totalPages;
            }
            $offset = ($page - 1) * $limit;

            // Fetch paginated sales
            $dataSql = "
                SELECT 
                    s.id,
                    s.invoice_no,
                    s.branch_id,
                    s.customer_id,
                    s.user_id,
                    s.subtotal,
                    s.discount_amount,
                    s.roundup_amount,
                    s.total_amount,
                    s.total_cost,
                    s.status,
                    s.created_at,
                    b.name AS branch_name,
                    b.code AS branch_code,
                    COALESCE(c.name, 'Walk-in Customer') AS customer_name,
                    c.phone AS customer_phone,
                    COALESCE(NULLIF(u.full_name, ''), u.username, 'System') AS cashier_name
                FROM sales s
                INNER JOIN branches b ON s.branch_id = b.id
                LEFT JOIN customers c ON s.customer_id = c.id
                LEFT JOIN users u ON s.user_id = u.id
                $whereSql
                ORDER BY s.id DESC
                LIMIT :limit OFFSET :offset
            ";

            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare($dataSql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Relational batch loading for total items and payment methods (avoids N+1)
            if (!empty($items)) {
                $saleIds = array_column($items, 'id');
                $placeholders = implode(',', array_fill(0, count($saleIds), '?'));

                // Item quantities batch
                $itemStmt = $db->prepare("
                    SELECT sale_id, SUM(quantity) AS total_items
                    FROM sale_items
                    WHERE sale_id IN ($placeholders)
                    GROUP BY sale_id
                ");
                $itemStmt->execute($saleIds);
                $itemCounts = [];
                while ($r = $itemStmt->fetch(PDO::FETCH_ASSOC)) {
                    $itemCounts[$r['sale_id']] = (float)$r['total_items'];
                }

                // Payment methods batch
                $pmtStmt = $db->prepare("
                    SELECT sale_id, GROUP_CONCAT(DISTINCT payment_method SEPARATOR ', ') AS payment_methods
                    FROM payments
                    WHERE sale_id IN ($placeholders)
                    GROUP BY sale_id
                ");
                $pmtStmt->execute($saleIds);
                $pmtMethods = [];
                while ($r = $pmtStmt->fetch(PDO::FETCH_ASSOC)) {
                    $pmtMethods[$r['sale_id']] = $r['payment_methods'];
                }

                $isSpecial = MaskingHelper::isSpecialUser();
                foreach ($items as &$it) {
                    $sid = $it['id'];
                    $it['total_items'] = $itemCounts[$sid] ?? 0;
                    $it['payment_methods'] = !empty($pmtMethods[$sid]) ? $pmtMethods[$sid] : 'Cash in Hand';

                    if ($isSpecial) {
                        $it['subtotal']        = MaskingHelper::maskValue((float)$it['subtotal'], true);
                        $it['discount_amount'] = MaskingHelper::maskValue((float)$it['discount_amount'], true);
                        $it['total_amount']    = MaskingHelper::maskValue((float)$it['total_amount'], true);
                        $it['total_cost']      = MaskingHelper::maskValue((float)$it['total_cost'], true);
                    }
                }
            }

            return [
                'items'       => $items,
                'totalItems'  => $totalItems,
                'page'        => $page,
                'limit'       => $limit,
                'totalPages'  => $totalPages
            ];

        } catch (Throwable $e) {
            Logger::error("Sale::getList failed: " . $e->getMessage(), $e);
            return [
                'items'       => [],
                'totalItems'  => 0,
                'page'        => 1,
                'limit'       => $limit,
                'totalPages'  => 1
            ];
        }
    }

    /**
     * Get complete sale details for Drawer/Modal view
     */
    public static function getSaleDetails(int $saleId): ?array
    {
        try {
            $sale = Database::fetchOne("
                SELECT 
                    s.id,
                    s.invoice_no,
                    s.branch_id,
                    s.customer_id,
                    s.user_id,
                    s.sales_rep_id,
                    s.subtotal,
                    s.discount_type,
                    s.discount_value,
                    s.discount_amount,
                    s.roundup_amount,
                    s.total_amount,
                    s.total_cost,
                    s.status,
                    s.held_notes,
                    s.created_at,
                    s.updated_at,
                    b.name AS branch_name,
                    b.address AS branch_address,
                    b.phone AS branch_phone,
                    COALESCE(c.name, 'Walk-in Customer') AS customer_name,
                    c.phone AS customer_phone,
                    c.customer_code,
                    c.address AS customer_address,
                    COALESCE(NULLIF(u.full_name, ''), u.username, 'System') AS cashier_name,
                    rep.full_name AS sales_rep_name
                FROM sales s
                INNER JOIN branches b ON s.branch_id = b.id
                LEFT JOIN customers c ON s.customer_id = c.id
                LEFT JOIN users u ON s.user_id = u.id
                LEFT JOIN employees rep ON s.sales_rep_id = rep.id
                WHERE s.id = :id
            ", [':id' => $saleId]);

            if (!$sale) {
                return null;
            }

            // Items
            $items = Database::fetchAll("
                SELECT 
                    si.id,
                    si.sale_id,
                    si.product_id,
                    si.lot_id,
                    si.quantity,
                    si.original_price,
                    si.unit_price,
                    si.discount_amount,
                    si.line_total,
                    COALESCE(p.name, CONCAT('Product #', si.product_id)) AS product_name,
                    COALESCE(p.sku, '—') AS sku,
                    COALESCE(p.barcode, '—') AS barcode,
                    l.lot_number,
                    l.expiry_date
                FROM sale_items si
                LEFT JOIN products p ON si.product_id = p.id
                LEFT JOIN product_lots l ON si.lot_id = l.id
                WHERE si.sale_id = :sale_id
                ORDER BY si.id ASC
            ", [':sale_id' => $saleId]);

            // Payments
            $payments = Database::fetchAll("
                SELECT 
                    p.id,
                    p.payment_no,
                    p.payment_method,
                    p.amount,
                    p.reference_no,
                    p.created_at
                FROM payments p
                WHERE p.sale_id = :sale_id
                ORDER BY p.id ASC
            ", [':sale_id' => $saleId]);

            $isSpecial = MaskingHelper::isSpecialUser();
            if ($isSpecial) {
                $sale['subtotal']        = MaskingHelper::maskValue((float)$sale['subtotal'], true);
                $sale['discount_amount'] = MaskingHelper::maskValue((float)$sale['discount_amount'], true);
                $sale['total_amount']    = MaskingHelper::maskValue((float)$sale['total_amount'], true);
                foreach ($items as &$it) {
                    $it['unit_price'] = MaskingHelper::maskValue((float)$it['unit_price'], true);
                    $it['line_total'] = MaskingHelper::maskValue((float)$it['line_total'], true);
                }
                foreach ($payments as &$pm) {
                    $pm['amount'] = MaskingHelper::maskValue((float)$pm['amount'], true);
                }
            }

            return [
                'sale'     => $sale,
                'items'    => $items,
                'payments' => $payments
            ];

        } catch (Throwable $e) {
            Logger::error("Sale::getSaleDetails failed: " . $e->getMessage(), $e);
            return null;
        }
    }

    /**
     * Export Sales CSV
     */
    public static function exportList(array $filters = [], int $branchId = 0): void
    {
        $res = self::getList($branchId, $filters, 1, 500);
        $sales = $res['items'];

        $filename = 'Sales_History_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        fputcsv($output, [
            'Invoice No',
            'Date & Time',
            'Branch',
            'Customer',
            'Phone',
            'Cashier',
            'Items Qty',
            'Payment Method',
            'Subtotal (LKR)',
            'Discount (LKR)',
            'Total Amount (LKR)',
            'Status'
        ]);

        foreach ($sales as $s) {
            fputcsv($output, [
                $s['invoice_no'],
                $s['created_at'],
                $s['branch_name'],
                $s['customer_name'],
                $s['customer_phone'] ?? '',
                $s['cashier_name'],
                $s['total_items'],
                $s['payment_methods'],
                number_format((float)$s['subtotal'], 2, '.', ''),
                number_format((float)$s['discount_amount'], 2, '.', ''),
                number_format((float)$s['total_amount'], 2, '.', ''),
                strtoupper($s['status'])
            ]);
        }

        fclose($output);
        exit();
    }
}

