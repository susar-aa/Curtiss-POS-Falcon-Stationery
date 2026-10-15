<?php
/**
 * Curtiss POS - Supplier Return Engine (Stage 15)
 * Manages purchase returns against approved GRNs, stock lot deduction,
 * supplier payable adjustment vs supplier credit asset allocation,
 * and credit settlement workflows (cash refund, bank refund, future GRN offset).
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Audit.php';
require_once __DIR__ . '/Accounting.php';
require_once __DIR__ . '/Banking.php';

class SupplierReturnEngine
{
    public static function generateReturnNumber(): string
    {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT MAX(id) AS max_id FROM supplier_returns");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $nextNum = ((int)($row['max_id'] ?? 0)) + 1;
            return 'SRET-' . sprintf('%04d', $nextNum);
        } catch (Throwable $e) {
            Logger::error("Failed to generate supplier return number: " . $e->getMessage(), $e);
            return 'SRET-' . sprintf('%04d', rand(1000, 9999));
        }
    }

    public static function generateCreditNumber(): string
    {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT MAX(id) AS max_id FROM supplier_credits");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $nextNum = ((int)($row['max_id'] ?? 0)) + 1;
            return 'SCRD-' . sprintf('%04d', $nextNum);
        } catch (Throwable $e) {
            Logger::error("Failed to generate supplier credit number: " . $e->getMessage(), $e);
            return 'SCRD-' . sprintf('%04d', rand(1000, 9999));
        }
    }

    public static function generateSettlementNumber(): string
    {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT MAX(id) AS max_id FROM supplier_credit_settlements");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $nextNum = ((int)($row['max_id'] ?? 0)) + 1;
            return 'SSET-' . sprintf('%04d', $nextNum);
        } catch (Throwable $e) {
            Logger::error("Failed to generate settlement number: " . $e->getMessage(), $e);
            return 'SSET-' . sprintf('%04d', rand(1000, 9999));
        }
    }

    /**
     * Get Products and Totals purchased from a specific supplier (across approved GRNs)
     */
    public static function getSupplierPurchasedProducts(int $supplierId, ?string $search = null, int $branchId = 1): array
    {
        try {
            $params = [
                ':supplier_id' => $supplierId,
                ':branch_id'   => $branchId
            ];
            $searchWhere = "";
            if (!empty($search)) {
                $searchWhere = " AND (p.name LIKE :search1 OR p.barcode LIKE :search2 OR pl.lot_number LIKE :search3)";
                $term = '%' . trim($search) . '%';
                $params[':search1'] = $term;
                $params[':search2'] = $term;
                $params[':search3'] = $term;
            }

            $sql = "
                SELECT 
                    gi.product_id,
                    p.name AS product_name,
                    p.barcode,
                    gi.lot_id,
                    pl.lot_number,
                    gi.unit_cost,
                    pl.remaining_quantity AS lot_remaining_quantity,
                    COALESCE(bi.quantity, 0.00) AS branch_stock_quantity,
                    SUM(gi.quantity) AS total_purchased_quantity,
                    COALESCE(SUM(sri.total_returned), 0.00) AS total_returned_quantity
                FROM grn_items gi
                JOIN grns g ON gi.grn_id = g.id
                JOIN products p ON gi.product_id = p.id
                LEFT JOIN product_lots pl ON gi.lot_id = pl.id
                LEFT JOIN branch_inventory bi ON (bi.product_id = gi.product_id AND bi.branch_id = :branch_id)
                LEFT JOIN (
                    SELECT grn_item_id, SUM(quantity) AS total_returned
                    FROM supplier_return_items
                    GROUP BY grn_item_id
                ) sri ON sri.grn_item_id = gi.id
                WHERE g.supplier_id = :supplier_id 
                  AND g.status = 'Approved'
                  {$searchWhere}
                GROUP BY gi.product_id, gi.lot_id, gi.unit_cost
                HAVING (total_purchased_quantity - total_returned_quantity) > 0.001
                ORDER BY p.name ASC
            ";

            $rows = Database::fetchAll($sql, $params);

            foreach ($rows as &$r) {
                $purchased = (float)$r['total_purchased_quantity'];
                $returned  = (float)$r['total_returned_quantity'];
                $netRemFromSupplier = max(0.00, round($purchased - $returned, 2));
                $lotStock = (float)($r['lot_remaining_quantity'] ?? 0.00);
                $branchStock = (float)($r['branch_stock_quantity'] ?? 0.00);

                $r['total_purchased_quantity'] = $purchased;
                $r['total_returned_quantity']  = $returned;
                $r['net_supplier_quantity']    = $netRemFromSupplier;
                $r['unit_cost']                = (float)$r['unit_cost'];
                $r['lot_remaining_quantity']   = $lotStock;
                $r['branch_stock_quantity']    = $branchStock;
                $r['max_returnable']          = max(0.00, round(min($netRemFromSupplier, $lotStock, $branchStock), 2));
            }

            return [
                'success' => true,
                'products' => $rows
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to fetch purchased products for supplier #{$supplierId}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Error loading supplier products: ' . $e->getMessage(), 'products' => []];
        }
    }
    public static function getReturnableGrnItems(int $grnId): array
    {
        try {
            $grn = Database::fetchOne("
                SELECT g.*, s.name AS supplier_name, b.name AS branch_name
                FROM grns g
                JOIN suppliers s ON g.supplier_id = s.id
                JOIN branches b ON g.branch_id = b.id
                WHERE g.id = :id AND g.status = 'Approved'
            ", [':id' => $grnId]);

            if (!$grn) {
                return ['success' => false, 'message' => 'Approved GRN not found.'];
            }

            $items = Database::fetchAll("
                SELECT 
                    gi.id AS grn_item_id,
                    gi.grn_id,
                    gi.product_id,
                    gi.lot_id,
                    gi.quantity AS received_quantity,
                    gi.unit_cost,
                    gi.selling_price,
                    gi.line_total,
                    p.name AS product_name,
                    p.barcode,
                    pl.lot_number,
                    pl.remaining_quantity AS lot_remaining_quantity,
                    COALESCE(bi.quantity, 0.00) AS branch_stock_quantity,
                    COALESCE(sri.total_returned, 0.00) AS previously_returned_quantity
                FROM grn_items gi
                JOIN products p ON gi.product_id = p.id
                LEFT JOIN product_lots pl ON gi.lot_id = pl.id
                LEFT JOIN branch_inventory bi ON (bi.product_id = gi.product_id AND bi.branch_id = :branch_id)
                LEFT JOIN (
                    SELECT grn_item_id, SUM(quantity) AS total_returned
                    FROM supplier_return_items
                    GROUP BY grn_item_id
                ) sri ON sri.grn_item_id = gi.id
                WHERE gi.grn_id = :grn_id
                ORDER BY gi.id ASC
            ", [':grn_id' => $grnId, ':branch_id' => $grn['branch_id']]);

            foreach ($items as &$item) {
                $received = (float)$item['received_quantity'];
                $prevReturned = (float)$item['previously_returned_quantity'];
                $lotRemaining = (float)($item['lot_remaining_quantity'] ?? 0.00);
                $branchStock  = (float)($item['branch_stock_quantity'] ?? 0.00);

                $maxReturnableFromGrn = max(0.00, round($received - $prevReturned, 2));
                $effectiveMaxReturn = min($maxReturnableFromGrn, $lotRemaining, $branchStock);

                $item['received_quantity']         = $received;
                $item['previously_returned_quantity'] = $prevReturned;
                $item['max_returnable_from_grn']   = $maxReturnableFromGrn;
                $item['lot_remaining_quantity']    = $lotRemaining;
                $item['branch_stock_quantity']     = $branchStock;
                $item['effective_max_return']      = max(0.00, round($effectiveMaxReturn, 2));
                $item['unit_cost']                 = (float)$item['unit_cost'];
            }

            return [
                'success' => true,
                'grn'     => $grn,
                'items'   => $items
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to fetch returnable GRN items for GRN #{$grnId}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Error loading GRN items: ' . $e->getMessage()];
        }
    }

    /**
     * Process Supplier Return against approved GRN or by Supplier ID directly
     */
    public static function processReturn(array $data, int $userId, int $branchId): array
    {
        $grnId = (int)($data['grn_id'] ?? 0);
        $supplierId = (int)($data['supplier_id'] ?? 0);

        if ($grnId <= 0 && $supplierId > 0) {
            return self::processReturnBySupplier($data, $userId, $branchId);
        }

        $db = Database::getInstance()->getConnection();

        try {
            $db->beginTransaction();

            $grnId = (int)($data['grn_id'] ?? 0);
            $returnDate = !empty($data['return_date']) ? trim($data['return_date']) : date('Y-m-d');
            $notes = !empty($data['notes']) ? trim($data['notes']) : 'Supplier Return';
            $items = $data['items'] ?? [];

            if ($grnId <= 0) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Valid GRN selection is required.'];
            }

            if (empty($items) || !is_array($items)) {
                $db->rollBack();
                return ['success' => false, 'message' => 'At least one item must be selected for return.'];
            }

            $grn = Database::fetchOne("
                SELECT g.*, s.name AS supplier_name 
                FROM grns g 
                JOIN suppliers s ON g.supplier_id = s.id 
                WHERE g.id = :id FOR UPDATE
            ", [':id' => $grnId]);

            if (!$grn || $grn['status'] !== 'Approved') {
                $db->rollBack();
                return ['success' => false, 'message' => 'GRN not found or is not Approved.'];
            }

            $supplierId = (int)$grn['supplier_id'];
            $grnBranchId = (int)$grn['branch_id'];

            $totalReturnAmount = 0.00;
            $processedItems = [];

            foreach ($items as $reqItem) {
                $grnItemId = (int)($reqItem['grn_item_id'] ?? 0);
                $returnQty = round(max(0.00, (float)($reqItem['quantity'] ?? 0)), 2);
                $reason    = !empty($reqItem['reason']) ? trim($reqItem['reason']) : 'Supplier return';

                if ($grnItemId <= 0 || $returnQty <= 0.00) {
                    continue; // Skip zero items
                }

                // Fetch GRN item & lot details FOR UPDATE
                $gi = Database::fetchOne("
                    SELECT 
                        gi.*,
                        p.name AS product_name,
                        pl.lot_number,
                        pl.remaining_quantity AS lot_remaining_quantity
                    FROM grn_items gi
                    JOIN products p ON gi.product_id = p.id
                    LEFT JOIN product_lots pl ON gi.lot_id = pl.id
                    WHERE gi.id = :gi_id AND gi.grn_id = :grn_id
                    FOR UPDATE
                ", [':gi_id' => $grnItemId, ':grn_id' => $grnId]);

                if (!$gi) {
                    $db->rollBack();
                    return ['success' => false, 'message' => "GRN Item #{$grnItemId} not found for this GRN."];
                }

                $productId = (int)$gi['product_id'];
                $lotId     = !empty($gi['lot_id']) ? (int)$gi['lot_id'] : null;
                $unitCost  = (float)$gi['unit_cost'];
                $origQty   = (float)$gi['quantity'];

                // 1. Calculate previously returned quantity for this GRN item
                $prevRetRow = Database::fetchOne("
                    SELECT SUM(quantity) AS total_ret FROM supplier_return_items WHERE grn_item_id = :gi_id
                ", [':gi_id' => $grnItemId]);
                $prevRetQty = round((float)($prevRetRow['total_ret'] ?? 0.00), 2);

                $maxReturnableFromGrn = round(max(0.00, $origQty - $prevRetQty), 2);

                if ($returnQty > ($maxReturnableFromGrn + 0.001)) {
                    $db->rollBack();
                    return [
                        'success' => false,
                        'message' => "Over-return blocked for product '{$gi['product_name']}': Requested return quantity ({$returnQty}) exceeds original GRN received qty ({$origQty}) minus previous returns ({$prevRetQty}) [Max allowed: {$maxReturnableFromGrn}]."
                    ];
                }

                // 2. Check remaining lot stock
                if ($lotId) {
                    $lotRemaining = round((float)$gi['lot_remaining_quantity'], 2);
                    if ($returnQty > ($lotRemaining + 0.001)) {
                        $db->rollBack();
                        return [
                            'success' => false,
                            'message' => "Insufficient sellable lot stock blocked for product '{$gi['product_name']}': Remaining lot quantity is {$lotRemaining} units (Lot #: {$gi['lot_number']}), but return requested is {$returnQty} units."
                        ];
                    }
                }

                // 3. Check branch inventory stock
                $invRow = Database::fetchOne("
                    SELECT quantity FROM branch_inventory 
                    WHERE product_id = :pid AND branch_id = :bid FOR UPDATE
                ", [':pid' => $productId, ':bid' => $grnBranchId]);

                $branchStock = round((float)($invRow['quantity'] ?? 0.00), 2);
                if ($returnQty > ($branchStock + 0.001)) {
                    $db->rollBack();
                    return [
                        'success' => false,
                        'message' => "Insufficient branch inventory stock for product '{$gi['product_name']}': Available branch stock is {$branchStock} units, but return requested is {$returnQty} units."
                    ];
                }

                $lineTotal = round($returnQty * $unitCost, 2);
                $totalReturnAmount += $lineTotal;

                $processedItems[] = [
                    'grn_item_id' => $grnItemId,
                    'product_id'  => $productId,
                    'lot_id'      => $lotId,
                    'quantity'    => $returnQty,
                    'unit_cost'   => $unitCost,
                    'line_total'  => $lineTotal,
                    'reason'      => $reason
                ];
            }

            if (empty($processedItems) || $totalReturnAmount <= 0.00) {
                $db->rollBack();
                return ['success' => false, 'message' => 'No valid return items or quantity specified.'];
            }

            $totalReturnAmount = round($totalReturnAmount, 2);

            // 4. Calculate GRN Payable vs Supplier Credit allocation
            $actualGrnTotal = (float)$grn['actual_grn_total'];
            $currentPaid    = (float)$grn['paid_amount'];
            $grnRemainingDue = round(max(0.00, $actualGrnTotal - $currentPaid), 2);

            $appliedToPayable = min($totalReturnAmount, $grnRemainingDue);
            $creditAmount     = round(max(0.00, $totalReturnAmount - $appliedToPayable), 2);

            $returnNo = self::generateReturnNumber();

            // 5. Insert supplier_returns header
            $stmtHeader = $db->prepare("
                INSERT INTO supplier_returns (
                    return_number, supplier_id, grn_id, branch_id, return_date,
                    total_return_amount, applied_to_payable, credit_amount, notes, created_by
                ) VALUES (
                    :no, :sid, :grnid, :bid, :rdate,
                    :tot, :app, :crd, :notes, :user
                )
            ");

            $stmtHeader->execute([
                ':no'     => $returnNo,
                ':sid'    => $supplierId,
                ':grnid'  => $grnId,
                ':bid'    => $grnBranchId,
                ':rdate'  => $returnDate,
                ':tot'    => $totalReturnAmount,
                ':app'    => $appliedToPayable,
                ':crd'    => $creditAmount,
                ':notes'  => $notes,
                ':user'   => $userId
            ]);

            $supplierReturnId = (int)$db->lastInsertId();

            // 6. Process items: insert supplier_return_items, reduce lot stock, reduce branch inventory, append stock_ledger
            $stmtItem = $db->prepare("
                INSERT INTO supplier_return_items (
                    supplier_return_id, grn_item_id, product_id, lot_id, quantity, unit_cost, total_amount, reason
                ) VALUES (
                    :sret_id, :gi_id, :pid, :lot_id, :qty, :cost, :tot, :reason
                )
            ");

            foreach ($processedItems as $pItem) {
                $stmtItem->execute([
                    ':sret_id' => $supplierReturnId,
                    ':gi_id'   => $pItem['grn_item_id'],
                    ':pid'     => $pItem['product_id'],
                    ':lot_id'  => $pItem['lot_id'],
                    ':qty'     => $pItem['quantity'],
                    ':cost'    => $pItem['unit_cost'],
                    ':tot'     => $pItem['line_total'],
                    ':reason'  => $pItem['reason']
                ]);

                // Reduce Product Lot
                if (!empty($pItem['lot_id'])) {
                    $db->prepare("
                        UPDATE product_lots 
                        SET remaining_quantity = remaining_quantity - :qty, updated_at = NOW()
                        WHERE id = :lot_id
                    ")->execute([':qty' => $pItem['quantity'], ':lot_id' => $pItem['lot_id']]);
                }

                // Reduce Branch Inventory
                $db->prepare("
                    UPDATE branch_inventory 
                    SET quantity = quantity - :qty, updated_at = NOW()
                    WHERE product_id = :pid AND branch_id = :bid
                ")->execute([':qty' => $pItem['quantity'], ':pid' => $pItem['product_id'], ':bid' => $grnBranchId]);

                // Fetch new branch stock for stock ledger balance_after
                $newStockRow = Database::fetchOne("
                    SELECT quantity FROM branch_inventory WHERE product_id = :pid AND branch_id = :bid
                ", [':pid' => $pItem['product_id'], ':bid' => $grnBranchId]);
                $newStock = (float)($newStockRow['quantity'] ?? 0.00);

                // Append stock_ledger movement
                $db->prepare("
                    INSERT INTO stock_ledger (
                        branch_id, product_id, lot_id, movement_type, quantity, balance_after, reference_no, notes, user_id
                    ) VALUES (
                        :bid, :pid, :lot_id, 'supplier_return', :qty, :bal, :ref, :notes, :user
                    )
                ")->execute([
                    ':bid'     => $grnBranchId,
                    ':pid'     => $pItem['product_id'],
                    ':lot_id'  => $pItem['lot_id'],
                    ':qty'     => -1 * $pItem['quantity'], // negative movement
                    ':bal'     => $newStock,
                    ':ref'     => $returnNo,
                    ':notes'   => "Supplier Return {$returnNo} for GRN #{$grn['grn_number']}",
                    ':user'    => $userId
                ]);
            }

            // 7. Update GRN paid_amount & payment_status if appliedToPayable > 0
            if ($appliedToPayable > 0.00) {
                $newPaid = round($currentPaid + $appliedToPayable, 2);
                $newStatus = ($newPaid >= ($actualGrnTotal - 0.001)) ? 'paid' : 'partially_paid';

                $db->prepare("
                    UPDATE grns SET paid_amount = :paid, payment_status = :status WHERE id = :id
                ")->execute([':paid' => $newPaid, ':status' => $newStatus, ':id' => $grnId]);
            }

            // 8. If creditAmount > 0, create supplier_credits record
            $supplierCreditId = null;
            if ($creditAmount > 0.00) {
                $creditNo = self::generateCreditNumber();
                $stmtCred = $db->prepare("
                    INSERT INTO supplier_credits (
                        credit_number, supplier_id, supplier_return_id, branch_id, credit_date,
                        total_credit_amount, remaining_credit_amount, status, notes, created_by
                    ) VALUES (
                        :no, :sid, :sret_id, :bid, :cdate,
                        :tot, :rem, 'active', :notes, :user
                    )
                ");

                $stmtCred->execute([
                    ':no'      => $creditNo,
                    ':sid'     => $supplierId,
                    ':sret_id' => $supplierReturnId,
                    ':bid'     => $grnBranchId,
                    ':cdate'   => $returnDate,
                    ':tot'     => $creditAmount,
                    ':rem'     => $creditAmount,
                    ':notes'   => "Over-return credit from Return {$returnNo}",
                    ':user'    => $userId
                ]);

                $supplierCreditId = (int)$db->lastInsertId();
            }

            // 9. Post Double-Entry Journal Entry
            $apAccId        = Accounting::getAccountIdByCode(Accounting::CODE_SUPPLIER_PAYABLE);
            $invAccId       = Accounting::getAccountIdByCode(Accounting::CODE_INVENTORY_ASSET);
            $creditAssetId  = Accounting::getAccountIdByCode(Accounting::CODE_SUPPLIER_CREDIT);

            $journalLines = [];

            if ($appliedToPayable > 0.00) {
                $journalLines[] = [
                    'account_id' => $apAccId,
                    'debit'      => $appliedToPayable,
                    'credit'     => 0.00,
                    'memo'       => "AP Reduction via Supplier Return {$returnNo}"
                ];
            }

            if ($creditAmount > 0.00) {
                $journalLines[] = [
                    'account_id' => $creditAssetId,
                    'debit'      => $creditAmount,
                    'credit'     => 0.00,
                    'memo'       => "Supplier Receivable Credit via Return {$returnNo}"
                ];
            }

            $journalLines[] = [
                'account_id' => $invAccId,
                'debit'      => 0.00,
                'credit'     => $totalReturnAmount,
                'memo'       => "Inventory Asset reduction for returned stock"
            ];

            $header = [
                'branch_id'   => $grnBranchId,
                'entry_date'  => $returnDate,
                'source_type' => 'supplier_return',
                'source_id'   => $supplierReturnId,
                'description' => "Supplier Return {$returnNo} to {$grn['supplier_name']} for GRN #{$grn['grn_number']}",
                'created_by'  => $userId
            ];

            $jeRes = Accounting::postJournalEntry($header, $journalLines);
            if ($jeRes['success']) {
                $journalEntryId = $jeRes['journal_id'];
                $db->prepare("
                    UPDATE supplier_returns SET journal_entry_id = :je_id WHERE id = :id
                ")->execute([':je_id' => $journalEntryId, ':id' => $supplierReturnId]);
            }

            $db->commit();

            Audit::log('SUPPLIER_RETURN_PROCESSED', 'purchasing', (string)$supplierReturnId, null, [
                'return_number'       => $returnNo,
                'supplier_name'        => $grn['supplier_name'],
                'grn_number'          => $grn['grn_number'],
                'total_return_amount' => $totalReturnAmount,
                'applied_to_payable'  => $appliedToPayable,
                'credit_amount'       => $creditAmount
            ]);

            return [
                'success'             => true,
                'message'             => "Supplier Return {$returnNo} of LKR " . number_format($totalReturnAmount, 2) . " processed successfully.",
                'supplier_return_id'  => $supplierReturnId,
                'return_number'       => $returnNo,
                'total_return_amount' => $totalReturnAmount,
                'applied_to_payable'  => $appliedToPayable,
                'credit_amount'       => $creditAmount,
                'supplier_credit_id'  => $supplierCreditId
            ];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to process supplier return: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => "Supplier Return Error: " . $e->getMessage()];
        }
    }

    /**
     * Process Supplier Return directly by Supplier ID (Automatic FIFO GRN & Lot Allocation)
     */
    public static function processReturnBySupplier(array $data, int $userId, int $branchId): array
    {
        $db = Database::getInstance()->getConnection();

        try {
            $db->beginTransaction();

            $supplierId = (int)($data['supplier_id'] ?? 0);
            $returnDate = !empty($data['return_date']) ? trim($data['return_date']) : date('Y-m-d');
            $notes      = !empty($data['notes']) ? trim($data['notes']) : 'Supplier Return';
            $items      = $data['items'] ?? [];

            if ($supplierId <= 0) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Supplier selection is required.'];
            }

            if (empty($items) || !is_array($items)) {
                $db->rollBack();
                return ['success' => false, 'message' => 'At least one product item must be selected for return.'];
            }

            $supplier = Database::fetchOne("SELECT * FROM suppliers WHERE id = :id", [':id' => $supplierId]);
            if (!$supplier) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Selected supplier not found.'];
            }

            $totalReturnAmount = 0.00;
            $processedItems    = [];
            $primaryGrnId      = null;

            foreach ($items as $reqItem) {
                $productId = (int)($reqItem['product_id'] ?? 0);
                $lotId     = !empty($reqItem['lot_id']) ? (int)$reqItem['lot_id'] : null;
                $reqQty    = round(max(0.00, (float)($reqItem['quantity'] ?? 0)), 2);
                $reason    = !empty($reqItem['reason']) ? trim($reqItem['reason']) : 'Supplier return';

                if ($productId <= 0 || $reqQty <= 0.00) {
                    continue;
                }

                // Check branch inventory stock
                $invRow = Database::fetchOne("
                    SELECT quantity FROM branch_inventory 
                    WHERE product_id = :pid AND branch_id = :bid FOR UPDATE
                ", [':pid' => $productId, ':bid' => $branchId]);

                $branchStock = round((float)($invRow['quantity'] ?? 0.00), 2);
                if ($reqQty > ($branchStock + 0.001)) {
                    $db->rollBack();
                    $pName = Database::fetchOne("SELECT name FROM products WHERE id = :pid", [':pid' => $productId])['name'] ?? "Product #$productId";
                    return [
                        'success' => false,
                        'message' => "Insufficient branch inventory stock for product '{$pName}': Available branch stock is {$branchStock} units, but return requested is {$reqQty} units."
                    ];
                }

                // Query supplier's approved GRN items chronologically (FIFO)
                $lotWhere = $lotId ? "AND gi.lot_id = :lot_id" : "";
                $params = [':sid' => $supplierId, ':pid' => $productId];
                if ($lotId) $params[':lot_id'] = $lotId;

                $grnItems = Database::fetchAll("
                    SELECT 
                        gi.id AS grn_item_id, gi.grn_id, gi.quantity AS orig_qty, gi.unit_cost,
                        g.grn_number, g.actual_grn_total, g.paid_amount,
                        COALESCE(sri.total_ret, 0.00) AS prev_returned,
                        p.name AS product_name
                    FROM grn_items gi
                    JOIN grns g ON gi.grn_id = g.id
                    JOIN products p ON gi.product_id = p.id
                    LEFT JOIN (
                        SELECT grn_item_id, SUM(quantity) AS total_ret
                        FROM supplier_return_items
                        GROUP BY grn_item_id
                    ) sri ON sri.grn_item_id = gi.id
                    WHERE g.supplier_id = :sid AND g.status = 'Approved' AND gi.product_id = :pid
                    {$lotWhere}
                    ORDER BY g.received_date ASC, gi.id ASC
                    FOR UPDATE
                ", $params);

                if (empty($grnItems)) {
                    $db->rollBack();
                    return ['success' => false, 'message' => "No approved purchase records found for product ID #{$productId} from supplier '{$supplier['name']}'."];
                }

                $qtyNeeded = $reqQty;

                foreach ($grnItems as $gi) {
                    if ($qtyNeeded <= 0.001) break;

                    $origQty = (float)$gi['orig_qty'];
                    $prevRet = (float)$gi['prev_returned'];
                    $availInGi = round(max(0.00, $origQty - $prevRet), 2);

                    if ($availInGi <= 0.001) continue;

                    $allocQty = min($qtyNeeded, $availInGi);
                    $unitCost = (float)$gi['unit_cost'];
                    $lineTotal = round($allocQty * $unitCost, 2);

                    $totalReturnAmount += $lineTotal;
                    if (!$primaryGrnId) $primaryGrnId = (int)$gi['grn_id'];

                    $processedItems[] = [
                        'grn_id'      => (int)$gi['grn_id'],
                        'grn_item_id' => (int)$gi['grn_item_id'],
                        'product_id'  => $productId,
                        'lot_id'      => $lotId,
                        'quantity'    => $allocQty,
                        'unit_cost'   => $unitCost,
                        'line_total'  => $lineTotal,
                        'reason'      => $reason
                    ];

                    $qtyNeeded = round($qtyNeeded - $allocQty, 2);
                }

                if ($qtyNeeded > 0.001) {
                    $db->rollBack();
                    $pName = $grnItems[0]['product_name'] ?? "Product #$productId";
                    return [
                        'success' => false,
                        'message' => "Return quantity ({$reqQty}) exceeds total returnable purchased quantity for product '{$pName}' from supplier '{$supplier['name']}'."
                    ];
                }
            }

            if (empty($processedItems) || $totalReturnAmount <= 0.00) {
                $db->rollBack();
                return ['success' => false, 'message' => 'No valid return items or quantity specified.'];
            }

            $totalReturnAmount = round($totalReturnAmount, 2);
            $returnNo = self::generateReturnNumber();

            // Calculate overall AP Offset vs Supplier Credit across affected GRNs
            $affectedGrnIds = array_unique(array_column($processedItems, 'grn_id'));
            $totalApDue = 0.00;
            foreach ($affectedGrnIds as $gId) {
                $gRow = Database::fetchOne("SELECT actual_grn_total, paid_amount FROM grns WHERE id = :id", [':id' => $gId]);
                if ($gRow) {
                    $totalApDue += round(max(0.00, (float)$gRow['actual_grn_total'] - (float)$gRow['paid_amount']), 2);
                }
            }

            $appliedToPayable = min($totalReturnAmount, $totalApDue);
            $creditAmount     = round(max(0.00, $totalReturnAmount - $appliedToPayable), 2);

            // Insert supplier_returns header
            $stmtHeader = $db->prepare("
                INSERT INTO supplier_returns (
                    return_number, supplier_id, grn_id, branch_id, return_date,
                    total_return_amount, applied_to_payable, credit_amount, notes, created_by
                ) VALUES (
                    :no, :sid, :grnid, :bid, :rdate,
                    :tot, :app, :crd, :notes, :user
                )
            ");

            $stmtHeader->execute([
                ':no'     => $returnNo,
                ':sid'    => $supplierId,
                ':grnid'  => $primaryGrnId ?: 0,
                ':bid'    => $branchId,
                ':rdate'  => $returnDate,
                ':tot'    => $totalReturnAmount,
                ':app'    => $appliedToPayable,
                ':crd'    => $creditAmount,
                ':notes'  => $notes,
                ':user'   => $userId
            ]);

            $supplierReturnId = (int)$db->lastInsertId();

            // Insert items, update lots, update branch inventory, log stock ledger
            $stmtItem = $db->prepare("
                INSERT INTO supplier_return_items (
                    supplier_return_id, grn_item_id, product_id, lot_id, quantity, unit_cost, total_amount, reason
                ) VALUES (
                    :sret_id, :gi_id, :pid, :lot_id, :qty, :cost, :tot, :reason
                )
            ");

            foreach ($processedItems as $pItem) {
                $stmtItem->execute([
                    ':sret_id' => $supplierReturnId,
                    ':gi_id'   => $pItem['grn_item_id'],
                    ':pid'     => $pItem['product_id'],
                    ':lot_id'  => $pItem['lot_id'],
                    ':qty'     => $pItem['quantity'],
                    ':cost'    => $pItem['unit_cost'],
                    ':tot'     => $pItem['line_total'],
                    ':reason'  => $pItem['reason']
                ]);

                // Reduce Product Lot
                if (!empty($pItem['lot_id'])) {
                    $db->prepare("
                        UPDATE product_lots 
                        SET remaining_quantity = remaining_quantity - :qty, updated_at = NOW()
                        WHERE id = :lot_id
                    ")->execute([':qty' => $pItem['quantity'], ':lot_id' => $pItem['lot_id']]);
                }

                // Reduce Branch Inventory
                $db->prepare("
                    UPDATE branch_inventory 
                    SET quantity = quantity - :qty, updated_at = NOW()
                    WHERE product_id = :pid AND branch_id = :bid
                ")->execute([':qty' => $pItem['quantity'], ':pid' => $pItem['product_id'], ':bid' => $branchId]);

                $newStockRow = Database::fetchOne("
                    SELECT quantity FROM branch_inventory WHERE product_id = :pid AND branch_id = :bid
                ", [':pid' => $pItem['product_id'], ':bid' => $branchId]);
                $newStock = (float)($newStockRow['quantity'] ?? 0.00);

                // Stock Ledger Movement
                $db->prepare("
                    INSERT INTO stock_ledger (
                        branch_id, product_id, lot_id, movement_type, quantity, balance_after, reference_no, notes, user_id
                    ) VALUES (
                        :bid, :pid, :lot_id, 'supplier_return', :qty, :bal, :ref, :notes, :user
                    )
                ")->execute([
                    ':bid'     => $branchId,
                    ':pid'     => $pItem['product_id'],
                    ':lot_id'  => $pItem['lot_id'],
                    ':qty'     => -1 * $pItem['quantity'],
                    ':bal'     => $newStock,
                    ':ref'     => $returnNo,
                    ':notes'   => "Supplier Return {$returnNo} for supplier {$supplier['name']}",
                    ':user'    => $userId
                ]);
            }

            // Update affected GRNs' paid_amount & payment_status
            $remainingApToOffset = $appliedToPayable;
            foreach ($affectedGrnIds as $gId) {
                if ($remainingApToOffset <= 0.001) break;

                $gRow = Database::fetchOne("SELECT actual_grn_total, paid_amount FROM grns WHERE id = :id FOR UPDATE", [':id' => $gId]);
                if (!$gRow) continue;

                $tot = (float)$gRow['actual_grn_total'];
                $paid = (float)$gRow['paid_amount'];
                $due = round(max(0.00, $tot - $paid), 2);

                if ($due <= 0.001) continue;

                $offset = min($remainingApToOffset, $due);
                $newPaid = round($paid + $offset, 2);
                $newStatus = ($newPaid >= ($tot - 0.001)) ? 'paid' : 'partially_paid';

                $db->prepare("
                    UPDATE grns SET paid_amount = :paid, payment_status = :status WHERE id = :id
                ")->execute([':paid' => $newPaid, ':status' => $newStatus, ':id' => $gId]);

                $remainingApToOffset = round($remainingApToOffset - $offset, 2);
            }

            // If creditAmount > 0, create supplier_credits record
            $supplierCreditId = null;
            if ($creditAmount > 0.00) {
                $creditNo = self::generateCreditNumber();
                $stmtCred = $db->prepare("
                    INSERT INTO supplier_credits (
                        credit_number, supplier_id, supplier_return_id, branch_id, credit_date,
                        total_credit_amount, remaining_credit_amount, status, notes, created_by
                    ) VALUES (
                        :no, :sid, :sret_id, :bid, :cdate,
                        :tot, :rem, 'active', :notes, :user
                    )
                ");

                $stmtCred->execute([
                    ':no'      => $creditNo,
                    ':sid'     => $supplierId,
                    ':sret_id' => $supplierReturnId,
                    ':bid'     => $branchId,
                    ':cdate'   => $returnDate,
                    ':tot'     => $creditAmount,
                    ':rem'     => $creditAmount,
                    ':notes'   => "Supplier Return credit from Return {$returnNo}",
                    ':user'    => $userId
                ]);

                $supplierCreditId = (int)$db->lastInsertId();
            }

            // Double-Entry Journal Entry
            $apAccId       = Accounting::getAccountIdByCode(Accounting::CODE_SUPPLIER_PAYABLE);
            $invAccId      = Accounting::getAccountIdByCode(Accounting::CODE_INVENTORY_ASSET);
            $creditAssetId = Accounting::getAccountIdByCode(Accounting::CODE_SUPPLIER_CREDIT);

            $journalLines = [];
            if ($appliedToPayable > 0.00) {
                $journalLines[] = [
                    'account_id' => $apAccId,
                    'debit'      => $appliedToPayable,
                    'credit'     => 0.00,
                    'memo'       => "AP Reduction via Supplier Return {$returnNo}"
                ];
            }
            if ($creditAmount > 0.00) {
                $journalLines[] = [
                    'account_id' => $creditAssetId,
                    'debit'      => $creditAmount,
                    'credit'     => 0.00,
                    'memo'       => "Supplier Receivable Credit via Return {$returnNo}"
                ];
            }
            $journalLines[] = [
                'account_id' => $invAccId,
                'debit'      => 0.00,
                'credit'     => $totalReturnAmount,
                'memo'       => "Inventory Asset reduction for returned stock"
            ];

            $header = [
                'branch_id'   => $branchId,
                'entry_date'  => $returnDate,
                'source_type' => 'supplier_return',
                'source_id'   => $supplierReturnId,
                'description' => "Supplier Return {$returnNo} for {$supplier['name']}",
                'created_by'  => $userId
            ];

            $jeRes = Accounting::postJournalEntry($header, $journalLines);
            if ($jeRes['success']) {
                $journalEntryId = $jeRes['journal_id'];
                $db->prepare("
                    UPDATE supplier_returns SET journal_entry_id = :je_id WHERE id = :id
                ")->execute([':je_id' => $journalEntryId, ':id' => $supplierReturnId]);
            }

            $db->commit();

            Audit::log('SUPPLIER_RETURN_PROCESSED', 'purchasing', (string)$supplierReturnId, null, [
                'return_number'       => $returnNo,
                'supplier_name'       => $supplier['name'],
                'total_return_amount' => $totalReturnAmount,
                'applied_to_payable'  => $appliedToPayable,
                'credit_amount'       => $creditAmount
            ]);

            return [
                'success'             => true,
                'message'             => "Supplier Return {$returnNo} of LKR " . number_format($totalReturnAmount, 2) . " processed successfully.",
                'supplier_return_id'  => $supplierReturnId,
                'return_number'       => $returnNo,
                'total_return_amount' => $totalReturnAmount,
                'applied_to_payable'  => $appliedToPayable,
                'credit_amount'       => $creditAmount,
                'supplier_credit_id'  => $supplierCreditId
            ];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to process supplier return by supplier: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => "Supplier Return Error: " . $e->getMessage()];
        }
    }

    /**
     * Get active/history Supplier Credits
     */
    public static function getSupplierCredits(array $filters = []): array
    {
        try {
            $where = [];
            $params = [];

            if (!empty($filters['supplier_id'])) {
                $where[] = "sc.supplier_id = :supplier_id";
                $params[':supplier_id'] = (int)$filters['supplier_id'];
            }

            if (!empty($filters['status'])) {
                $where[] = "sc.status = :status";
                $params[':status'] = $filters['status'];
            }

            if (!empty($filters['branch_id'])) {
                $where[] = "sc.branch_id = :branch_id";
                $params[':branch_id'] = (int)$filters['branch_id'];
            }

            $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

            return Database::fetchAll("
                SELECT sc.*, s.name AS supplier_name, b.name AS branch_name, sr.return_number, u.full_name AS created_by_name
                FROM supplier_credits sc
                JOIN suppliers s ON sc.supplier_id = s.id
                JOIN branches b ON sc.branch_id = b.id
                LEFT JOIN supplier_returns sr ON sc.supplier_return_id = sr.id
                LEFT JOIN users u ON sc.created_by = u.id
                $whereSql
                ORDER BY sc.id DESC
            ", $params);
        } catch (Throwable $e) {
            Logger::error("Failed to fetch supplier credits: " . $e->getMessage(), $e);
            return [];
        }
    }

    /**
     * Settle Supplier Credit balance via bank_refund, cash_refund, or future_grn_offset
     */
    public static function processCreditSettlement(array $data, int $userId, int $branchId): array
    {
        $db = Database::getInstance()->getConnection();

        try {
            $db->beginTransaction();

            $creditId       = (int)($data['supplier_credit_id'] ?? 0);
            $settlementType = strtolower(trim($data['settlement_type'] ?? ''));
            $amount         = round(max(0.00, (float)($data['amount'] ?? 0)), 2);
            $settlementDate = !empty($data['settlement_date']) ? trim($data['settlement_date']) : date('Y-m-d');
            $bankAccountId  = !empty($data['bank_account_id']) ? (int)$data['bank_account_id'] : null;
            $targetGrnId    = !empty($data['target_grn_id']) ? (int)$data['target_grn_id'] : null;
            $referenceNo    = !empty($data['reference_no']) ? trim($data['reference_no']) : null;
            $notes          = !empty($data['notes']) ? trim($data['notes']) : 'Credit settlement';

            if (!in_array($settlementType, ['bank_refund', 'cash_refund', 'future_grn_offset'])) {
                $db->rollBack();
                return ['success' => false, 'message' => "Invalid settlement type '{$settlementType}'."];
            }

            if ($creditId <= 0 || $amount <= 0.00) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Valid credit selection and amount > 0 are required.'];
            }

            $sc = Database::fetchOne("
                SELECT sc.*, s.name AS supplier_name FROM supplier_credits sc 
                JOIN suppliers s ON sc.supplier_id = s.id 
                WHERE sc.id = :id FOR UPDATE
            ", [':id' => $creditId]);

            if (!$sc || $sc['status'] !== 'active') {
                $db->rollBack();
                return ['success' => false, 'message' => 'Supplier credit not found or is already fully settled/void.'];
            }

            $remCredit = (float)$sc['remaining_credit_amount'];
            if ($amount > ($remCredit + 0.001)) {
                $db->rollBack();
                return [
                    'success' => false,
                    'message' => "Settlement amount (LKR " . number_format($amount, 2) . ") exceeds remaining credit balance (LKR " . number_format($remCredit, 2) . ")."
                ];
            }

            $supplierId  = (int)$sc['supplier_id'];
            $creditBranchId = (int)$sc['branch_id'];

            // Validate settlement type specifics
            if ($settlementType === 'bank_refund') {
                if (!$bankAccountId) {
                    $db->rollBack();
                    return ['success' => false, 'message' => 'Bank account selection is required for bank refund.'];
                }
                $bankAcc = Banking::getBankAccount($bankAccountId);
                if (!$bankAcc || $bankAcc['status'] !== 'active') {
                    $db->rollBack();
                    return ['success' => false, 'message' => 'Selected bank account is invalid or inactive.'];
                }
            } elseif ($settlementType === 'cash_refund') {
                require_once __DIR__ . '/BusinessDay.php';
                $activeDay = BusinessDay::getOrCreateActiveDay($branchId, $userId);
                if (!$activeDay) {
                    $db->rollBack();
                    return ['success' => false, 'message' => 'Cannot process cash refund settlement. No active business day is currently open.'];
                }
            } elseif ($settlementType === 'future_grn_offset') {
                if (!$targetGrnId) {
                    $db->rollBack();
                    return ['success' => false, 'message' => 'Target GRN selection is required for future GRN offset.'];
                }

                $tGrn = Database::fetchOne("
                    SELECT id, grn_number, supplier_id, actual_grn_total, paid_amount, status 
                    FROM grns WHERE id = :id FOR UPDATE
                ", [':id' => $targetGrnId]);

                if (!$tGrn || $tGrn['status'] !== 'Approved') {
                    $db->rollBack();
                    return ['success' => false, 'message' => 'Target GRN is invalid or not Approved.'];
                }

                if ((int)$tGrn['supplier_id'] !== $supplierId) {
                    $db->rollBack();
                    return ['success' => false, 'message' => 'Target GRN does not belong to the credit supplier.'];
                }

                $tDue = round((float)$tGrn['actual_grn_total'] - (float)$tGrn['paid_amount'], 2);
                if ($amount > ($tDue + 0.001)) {
                    $db->rollBack();
                    return [
                        'success' => false,
                        'message' => "Settlement amount (LKR " . number_format($amount, 2) . ") exceeds target GRN #{$tGrn['grn_number']} remaining due (LKR " . number_format($tDue, 2) . ")."
                    ];
                }
            }

            $settlementNo = self::generateSettlementNumber();

            // 1. Insert supplier_credit_settlements
            $stmtS = $db->prepare("
                INSERT INTO supplier_credit_settlements (
                    settlement_number, supplier_credit_id, supplier_id, branch_id, settlement_date,
                    settlement_type, amount, bank_account_id, target_grn_id, reference_no, notes, created_by
                ) VALUES (
                    :no, :sc_id, :sid, :bid, :sdate,
                    :stype, :amount, :bank_acc, :tgrn, :ref, :notes, :user
                )
            ");

            $stmtS->execute([
                ':no'       => $settlementNo,
                ':sc_id'    => $creditId,
                ':sid'      => $supplierId,
                ':bid'      => $creditBranchId,
                ':sdate'    => $settlementDate,
                ':stype'    => $settlementType,
                ':amount'   => $amount,
                ':bank_acc' => $bankAccountId,
                ':tgrn'     => $targetGrnId,
                ':ref'      => $referenceNo,
                ':notes'    => $notes,
                ':user'     => $userId
            ]);

            $settlementId = (int)$db->lastInsertId();

            // 2. Update supplier_credits remaining_credit_amount & status
            $newRemCredit = round($remCredit - $amount, 2);
            $newStatus    = ($newRemCredit <= 0.001) ? 'fully_settled' : 'active';

            $db->prepare("
                UPDATE supplier_credits 
                SET remaining_credit_amount = :rem, status = :status
                WHERE id = :id
            ")->execute([':rem' => $newRemCredit, ':status' => $newStatus, ':id' => $creditId]);

            // 3. Handle cash drawer or target GRN updates
            if ($settlementType === 'cash_refund' && isset($activeDay) && $activeDay) {
                BusinessDay::recordDrawerMovement([
                    'business_day_id' => (int)$activeDay['id'],
                    'branch_id'       => $branchId,
                    'movement_type'   => 'cash_in',
                    'source_type'     => 'supplier_credit_settlements',
                    'source_id'       => $settlementId,
                    'amount'          => $amount,
                    'direction'       => 'in',
                    'reason'          => "Supplier Credit Cash Refund ({$settlementNo})",
                    'user_id'         => $userId
                ]);
            } elseif ($settlementType === 'future_grn_offset' && isset($tGrn)) {
                $newTargetPaid   = round((float)$tGrn['paid_amount'] + $amount, 2);
                $newTargetStatus = ($newTargetPaid >= ((float)$tGrn['actual_grn_total'] - 0.001)) ? 'paid' : 'partially_paid';

                $db->prepare("
                    UPDATE grns SET paid_amount = :paid, payment_status = :status WHERE id = :id
                ")->execute([':paid' => $newTargetPaid, ':status' => $newTargetStatus, ':id' => $targetGrnId]);
            }

            // 4. Accounting Journal Entry
            $creditAssetId = Accounting::getAccountIdByCode(Accounting::CODE_SUPPLIER_CREDIT); // 1045

            if ($settlementType === 'cash_refund') {
                $debitAccCode = (isset($activeDay) && $activeDay) ? Accounting::CODE_CASH_DRAWER : Accounting::CODE_CASH;
                $debitAccId = Accounting::getAccountIdByCode($debitAccCode); // 1012 or 1010
                $memoText = "Cash refund received for supplier credit {$sc['credit_number']}";
            } elseif ($settlementType === 'bank_refund') {
                $bankAccount = Banking::getBankAccount($bankAccountId);
                $debitAccId = (int)($bankAccount['account_id'] ?? Accounting::getAccountIdByCode(Accounting::CODE_BANK));
                $memoText = "Bank refund received into {$bankAccount['bank_name']} for credit {$sc['credit_number']}";
            } else { // future_grn_offset
                $debitAccId = Accounting::getAccountIdByCode(Accounting::CODE_SUPPLIER_PAYABLE); // 2020
                $memoText = "Supplier credit {$sc['credit_number']} applied to GRN #{$tGrn['grn_number']}";
            }

            $header = [
                'branch_id'   => $creditBranchId,
                'entry_date'  => $settlementDate,
                'source_type' => 'supplier_credit_settlement',
                'source_id'   => $settlementId,
                'description' => "Supplier Credit Settlement {$settlementNo} ({$settlementType}) for {$sc['supplier_name']}",
                'created_by'  => $userId
            ];

            $journalLines = [
                [
                    'account_id' => $debitAccId,
                    'debit'      => $amount,
                    'credit'     => 0.00,
                    'memo'       => $memoText
                ],
                [
                    'account_id' => $creditAssetId,
                    'debit'      => 0.00,
                    'credit'     => $amount,
                    'memo'       => "Supplier Credit Asset settlement"
                ]
            ];

            $jeRes = Accounting::postJournalEntry($header, $journalLines);
            if ($jeRes['success']) {
                $journalEntryId = $jeRes['journal_id'];
                $db->prepare("
                    UPDATE supplier_credit_settlements SET journal_entry_id = :je_id WHERE id = :id
                ")->execute([':je_id' => $journalEntryId, ':id' => $settlementId]);
            }

            $db->commit();

            Audit::log('SUPPLIER_CREDIT_SETTLED', 'purchasing', (string)$settlementId, null, [
                'settlement_number' => $settlementNo,
                'credit_number'     => $sc['credit_number'],
                'settlement_type'   => $settlementType,
                'amount'            => $amount
            ]);

            return [
                'success'                 => true,
                'message'                 => "Supplier Credit Settlement {$settlementNo} of LKR " . number_format($amount, 2) . " processed successfully.",
                'settlement_id'           => $settlementId,
                'settlement_number'       => $settlementNo,
                'remaining_credit_amount' => $newRemCredit
            ];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to process credit settlement: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => "Settlement Error: " . $e->getMessage()];
        }
    }

    /**
     * Get return history with multi-column search, pagination and batch item loading (N+1 eliminated)
     */
    public static function getReturnHistory(array $filters = []): array
    {
        try {
            $where = [];
            $params = [];

            if (!empty($filters['supplier_id'])) {
                $where[] = "sr.supplier_id = :supplier_id";
                $params[':supplier_id'] = (int)$filters['supplier_id'];
            }

            if (!empty($filters['grn_id'])) {
                $where[] = "sr.grn_id = :grn_id";
                $params[':grn_id'] = (int)$filters['grn_id'];
            }

            if (!empty($filters['branch_id'])) {
                $where[] = "sr.branch_id = :branch_id";
                $params[':branch_id'] = (int)$filters['branch_id'];
            }

            if (!empty($filters['search'])) {
                $rawTerm = trim($filters['search']);
                $term = '%' . $rawTerm . '%';
                $cleanNumeric = str_replace(',', '', $rawTerm);
                $cleanNumericTerm = '%' . $cleanNumeric . '%';

                $where[] = "(
                    sr.return_number LIKE :s1 OR
                    s.name LIKE :s2 OR
                    g.grn_number LIKE :s3 OR
                    sr.notes LIKE :s4 OR
                    u.full_name LIKE :s5 OR
                    CAST(sr.total_return_amount AS CHAR) LIKE :s6 OR
                    CONCAT('LKR ', FORMAT(sr.total_return_amount, 2)) LIKE :s7 OR
                    CONCAT('Rs. ', FORMAT(sr.total_return_amount, 2)) LIKE :s8 OR
                    CAST(sr.applied_to_payable AS CHAR) LIKE :s9 OR
                    CONCAT('LKR ', FORMAT(sr.applied_to_payable, 2)) LIKE :s10 OR
                    CONCAT('Rs. ', FORMAT(sr.applied_to_payable, 2)) LIKE :s11 OR
                    CAST(sr.credit_amount AS CHAR) LIKE :s12 OR
                    CONCAT('LKR ', FORMAT(sr.credit_amount, 2)) LIKE :s13 OR
                    CONCAT('Rs. ', FORMAT(sr.credit_amount, 2)) LIKE :s14 OR
                    sr.return_date LIKE :s15
                )";
                $params[':s1']  = $term;
                $params[':s2']  = $term;
                $params[':s3']  = $term;
                $params[':s4']  = $term;
                $params[':s5']  = $term;
                $params[':s6']  = $cleanNumericTerm;
                $params[':s7']  = $term;
                $params[':s8']  = $term;
                $params[':s9']  = $cleanNumericTerm;
                $params[':s10'] = $term;
                $params[':s11'] = $term;
                $params[':s12'] = $cleanNumericTerm;
                $params[':s13'] = $term;
                $params[':s14'] = $term;
                $params[':s15'] = $term;
            }

            $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

            $limitSql = "";
            if (!empty($filters['limit'])) {
                $limit = (int)$filters['limit'];
                $offset = (int)($filters['offset'] ?? 0);
                $limitSql = " LIMIT $limit OFFSET $offset";
            }

            $returns = Database::fetchAll("
                SELECT sr.*, s.name AS supplier_name, COALESCE(g.grn_number, 'Direct Return') AS grn_number, b.name AS branch_name, u.full_name AS created_by_name
                FROM supplier_returns sr
                JOIN suppliers s ON sr.supplier_id = s.id
                LEFT JOIN grns g ON sr.grn_id = g.id
                JOIN branches b ON sr.branch_id = b.id
                LEFT JOIN users u ON sr.created_by = u.id
                $whereSql
                ORDER BY sr.id DESC
                $limitSql
            ", $params);

            if (empty($returns)) {
                return [];
            }

            // Batch fetch items to eliminate N+1 queries completely
            $returnIds = array_column($returns, 'id');
            $placeholders = implode(',', array_fill(0, count($returnIds), '?'));
            $allItems = Database::fetchAll("
                SELECT sri.*, p.name AS product_name, p.barcode, p.sku, p.sku AS item_code, pl.lot_number
                FROM supplier_return_items sri
                JOIN products p ON sri.product_id = p.id
                LEFT JOIN product_lots pl ON sri.lot_id = pl.id
                WHERE sri.supplier_return_id IN ($placeholders)
                ORDER BY sri.id ASC
            ", $returnIds);

            $itemsByReturn = [];
            foreach ($allItems as $it) {
                $itemsByReturn[$it['supplier_return_id']][] = $it;
            }

            foreach ($returns as &$ret) {
                $ret['items'] = $itemsByReturn[$ret['id']] ?? [];
            }
            unset($ret);

            return $returns;
        } catch (Throwable $e) {
            Logger::error("Failed to fetch supplier return history: " . $e->getMessage(), $e);
            return [];
        }
    }

    /**
     * Get total count of return history matching filters
     */
    public static function getReturnHistoryCount(array $filters = []): int
    {
        try {
            $where = [];
            $params = [];

            if (!empty($filters['supplier_id'])) {
                $where[] = "sr.supplier_id = :supplier_id";
                $params[':supplier_id'] = (int)$filters['supplier_id'];
            }

            if (!empty($filters['grn_id'])) {
                $where[] = "sr.grn_id = :grn_id";
                $params[':grn_id'] = (int)$filters['grn_id'];
            }

            if (!empty($filters['branch_id'])) {
                $where[] = "sr.branch_id = :branch_id";
                $params[':branch_id'] = (int)$filters['branch_id'];
            }

            if (!empty($filters['search'])) {
                $rawTerm = trim($filters['search']);
                $term = '%' . $rawTerm . '%';
                $cleanNumeric = str_replace(',', '', $rawTerm);
                $cleanNumericTerm = '%' . $cleanNumeric . '%';

                $where[] = "(
                    sr.return_number LIKE :s1 OR
                    s.name LIKE :s2 OR
                    g.grn_number LIKE :s3 OR
                    sr.notes LIKE :s4 OR
                    u.full_name LIKE :s5 OR
                    CAST(sr.total_return_amount AS CHAR) LIKE :s6 OR
                    CONCAT('LKR ', FORMAT(sr.total_return_amount, 2)) LIKE :s7 OR
                    CONCAT('Rs. ', FORMAT(sr.total_return_amount, 2)) LIKE :s8 OR
                    CAST(sr.applied_to_payable AS CHAR) LIKE :s9 OR
                    CONCAT('LKR ', FORMAT(sr.applied_to_payable, 2)) LIKE :s10 OR
                    CONCAT('Rs. ', FORMAT(sr.applied_to_payable, 2)) LIKE :s11 OR
                    CAST(sr.credit_amount AS CHAR) LIKE :s12 OR
                    CONCAT('LKR ', FORMAT(sr.credit_amount, 2)) LIKE :s13 OR
                    CONCAT('Rs. ', FORMAT(sr.credit_amount, 2)) LIKE :s14 OR
                    sr.return_date LIKE :s15
                )";
                $params[':s1']  = $term;
                $params[':s2']  = $term;
                $params[':s3']  = $term;
                $params[':s4']  = $term;
                $params[':s5']  = $term;
                $params[':s6']  = $cleanNumericTerm;
                $params[':s7']  = $term;
                $params[':s8']  = $term;
                $params[':s9']  = $cleanNumericTerm;
                $params[':s10'] = $term;
                $params[':s11'] = $term;
                $params[':s12'] = $cleanNumericTerm;
                $params[':s13'] = $term;
                $params[':s14'] = $term;
                $params[':s15'] = $term;
            }

            $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

            $row = Database::fetchOne("
                SELECT COUNT(*) AS total
                FROM supplier_returns sr
                JOIN suppliers s ON sr.supplier_id = s.id
                LEFT JOIN grns g ON sr.grn_id = g.id
                JOIN branches b ON sr.branch_id = b.id
                LEFT JOIN users u ON sr.created_by = u.id
                $whereSql
            ", $params);

            return (int)($row['total'] ?? 0);
        } catch (Throwable $e) {
            Logger::error("Failed to fetch supplier return history count: " . $e->getMessage(), $e);
            return 0;
        }
    }
}
