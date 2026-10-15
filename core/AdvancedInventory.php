<?php
/**
 * Curtiss POS - Advanced Inventory Operations Engine (Stage 14)
 * Handles Branch Stock Transfers (Draft -> Sent -> Received), Stock Adjustments,
 * Damaged/Wastage Management & Disposal, Physical Stock Audits, and Comprehensive Stock Ledger.
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Audit.php';
require_once __DIR__ . '/Accounting.php';
require_once __DIR__ . '/Inventory.php';

class AdvancedInventory
{
    /**
     * Generate unique continuous transaction number (TRF-0001, ADJ-0001, AUD-0001)
     */
    public static function generateNumber(string $prefix, string $table): string
    {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT MAX(id) AS max_id FROM {$table}");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $nextNum = ((int)($row['max_id'] ?? 0)) + 1;
            return $prefix . '-' . sprintf('%04d', $nextNum);
        } catch (Throwable $e) {
            Logger::error("Failed to generate {$prefix} number: " . $e->getMessage(), $e);
            return $prefix . '-' . sprintf('%04d', rand(1000, 9999));
        }
    }

    // =========================================================================
    // 1. BRANCH STOCK TRANSFERS
    // =========================================================================

    /**
     * Create Draft or Sent Stock Transfer
     */
    public static function createTransfer(array $data, int $userId): array
    {
        $db = Database::getInstance()->getConnection();

        try {
            $db->beginTransaction();

            $fromBranchId = (int)($data['from_branch_id'] ?? 0);
            $toBranchId   = (int)($data['to_branch_id'] ?? 0);
            $transferDate = !empty($data['transfer_date']) ? trim($data['transfer_date']) : date('Y-m-d');
            $notes        = !empty($data['notes']) ? trim($data['notes']) : null;
            $items        = $data['items'] ?? [];
            $sendImmediately = !empty($data['send_now']);

            if ($fromBranchId <= 0 || $toBranchId <= 0) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Valid source and destination branches are required.'];
            }

            if ($fromBranchId === $toBranchId) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Source and destination branches cannot be the same.'];
            }

            if (empty($items)) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Transfer must contain at least one product item.'];
            }

            $transferNo = self::generateNumber('TRF', 'stock_transfers');

            $stmtH = $db->prepare("
                INSERT INTO stock_transfers (
                    transfer_number, from_branch_id, to_branch_id, transfer_date, status, notes, created_by
                ) VALUES (
                    :no, :from_b, :to_b, :tdate, 'draft', :notes, :user
                )
            ");

            $stmtH->execute([
                ':no'     => $transferNo,
                ':from_b' => $fromBranchId,
                ':to_b'   => $toBranchId,
                ':tdate'  => $transferDate,
                ':notes'  => $notes,
                ':user'   => $userId
            ]);

            $transferId = (int)$db->lastInsertId();

            $stmtItem = $db->prepare("
                INSERT INTO stock_transfer_items (
                    transfer_id, product_id, source_lot_id, quantity, cost, selling_price
                ) VALUES (
                    :trf_id, :prod_id, :lot_id, :qty, :cost, :price
                )
            ");

            foreach ($items as $item) {
                $productId = (int)($item['product_id'] ?? 0);
                $lotId     = (int)($item['lot_id'] ?? 0);
                $qty       = round((float)($item['quantity'] ?? 0), 2);

                if ($productId <= 0 || $lotId <= 0 || $qty <= 0.00) {
                    $db->rollBack();
                    return ['success' => false, 'message' => 'Invalid product, lot, or non-positive quantity in transfer item.'];
                }

                // Verify source lot
                $lot = Database::fetchOne("
                    SELECT * FROM product_lots WHERE id = :id AND branch_id = :b_id AND product_id = :p_id
                ", [':id' => $lotId, ':b_id' => $fromBranchId, ':p_id' => $productId]);

                if (!$lot) {
                    $db->rollBack();
                    return ['success' => false, 'message' => "Source lot #{$lotId} not found for product #{$productId} at branch #{$fromBranchId}."];
                }

                if ((float)$lot['remaining_quantity'] < $qty) {
                    $db->rollBack();
                    return [
                        'success' => false,
                        'message' => "Insufficient stock in Lot #{$lot['lot_number']}. Available: {$lot['remaining_quantity']}, Requested: {$qty}."
                    ];
                }

                $stmtItem->execute([
                    ':trf_id'  => $transferId,
                    ':prod_id' => $productId,
                    ':lot_id'  => $lotId,
                    ':qty'     => $qty,
                    ':cost'    => (float)$lot['cost'],
                    ':price'   => (float)$lot['selling_price']
                ]);
            }

            $db->commit();

            Audit::log('STOCK_TRANSFER_CREATED', 'inventory', (string)$transferId, null, [
                'transfer_number' => $transferNo, 'from_branch_id' => $fromBranchId, 'to_branch_id' => $toBranchId
            ]);

            if ($sendImmediately) {
                return self::sendTransfer($transferId, $userId);
            }

            return [
                'success'         => true,
                'transfer_id'     => $transferId,
                'transfer_number' => $transferNo,
                'message'         => "Stock transfer {$transferNo} created as Draft."
            ];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to create stock transfer: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Update Draft Stock Transfer
     */
    public static function updateTransfer(int $transferId, array $data, int $userId): array
    {
        $db = Database::getInstance()->getConnection();

        try {
            $db->beginTransaction();

            $trf = Database::fetchOne("SELECT * FROM stock_transfers WHERE id = :id FOR UPDATE", [':id' => $transferId]);
            if (!$trf) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Stock transfer not found.'];
            }

            if ($trf['status'] !== 'draft') {
                $db->rollBack();
                return ['success' => false, 'message' => "Only draft stock transfers can be modified. Current status: '{$trf['status']}'."];
            }

            $fromBranchId = (int)$trf['from_branch_id'];
            $toBranchId   = (int)($data['to_branch_id'] ?? $trf['to_branch_id']);
            $transferDate = !empty($data['transfer_date']) ? trim($data['transfer_date']) : $trf['transfer_date'];
            $notes        = !empty($data['notes']) ? trim($data['notes']) : null;
            $items        = $data['items'] ?? [];
            $sendImmediately = !empty($data['send_now']);

            if ($toBranchId <= 0 || $fromBranchId === $toBranchId) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Valid destination branch different from source branch is required.'];
            }

            if (empty($items)) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Transfer must contain at least one product item.'];
            }

            $stmtH = $db->prepare("
                UPDATE stock_transfers
                SET to_branch_id = :to_b, transfer_date = :tdate, notes = :notes
                WHERE id = :id
            ");
            $stmtH->execute([
                ':to_b'   => $toBranchId,
                ':tdate'  => $transferDate,
                ':notes'  => $notes,
                ':id'     => $transferId
            ]);

            // Clear previous items
            $db->prepare("DELETE FROM stock_transfer_items WHERE transfer_id = :id")->execute([':id' => $transferId]);

            $stmtItem = $db->prepare("
                INSERT INTO stock_transfer_items (
                    transfer_id, product_id, source_lot_id, quantity, cost, selling_price
                ) VALUES (
                    :trf_id, :prod_id, :lot_id, :qty, :cost, :price
                )
            ");

            foreach ($items as $item) {
                $productId = (int)($item['product_id'] ?? 0);
                $lotId     = (int)($item['lot_id'] ?? 0);
                $qty       = round((float)($item['quantity'] ?? 0), 2);

                if ($productId <= 0 || $lotId <= 0 || $qty <= 0.00) {
                    $db->rollBack();
                    return ['success' => false, 'message' => 'Invalid product, lot, or non-positive quantity in transfer item.'];
                }

                $lot = Database::fetchOne("
                    SELECT * FROM product_lots WHERE id = :id AND branch_id = :b_id AND product_id = :p_id
                ", [':id' => $lotId, ':b_id' => $fromBranchId, ':p_id' => $productId]);

                if (!$lot) {
                    $db->rollBack();
                    return ['success' => false, 'message' => "Source lot #{$lotId} not found for product #{$productId}."];
                }

                if ((float)$lot['remaining_quantity'] < $qty) {
                    $db->rollBack();
                    return [
                        'success' => false,
                        'message' => "Insufficient stock in Lot #{$lot['lot_number']}. Available: {$lot['remaining_quantity']}, Requested: {$qty}."
                    ];
                }

                $stmtItem->execute([
                    ':trf_id'  => $transferId,
                    ':prod_id' => $productId,
                    ':lot_id'  => $lotId,
                    ':qty'     => $qty,
                    ':cost'    => (float)$lot['cost'],
                    ':price'   => (float)$lot['selling_price']
                ]);
            }

            $db->commit();

            Audit::log('STOCK_TRANSFER_UPDATED', 'inventory', (string)$transferId, null, [
                'transfer_number' => $trf['transfer_number'], 'from_branch_id' => $fromBranchId, 'to_branch_id' => $toBranchId
            ]);

            if ($sendImmediately) {
                return self::sendTransfer($transferId, $userId);
            }

            return [
                'success'         => true,
                'transfer_id'     => $transferId,
                'transfer_number' => $trf['transfer_number'],
                'message'         => "Stock transfer {$trf['transfer_number']} updated successfully."
            ];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to update stock transfer: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Send Stock Transfer (Deducts Source Stock & Marks In-Transit)
     */
    public static function sendTransfer(int $transferId, int $userId): array
    {
        $db = Database::getInstance()->getConnection();

        try {
            $db->beginTransaction();

            $trf = Database::fetchOne("SELECT * FROM stock_transfers WHERE id = :id", [':id' => $transferId]);
            if (!$trf) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Stock transfer not found.'];
            }

            if ($trf['status'] !== 'draft') {
                $db->rollBack();
                return ['success' => false, 'message' => "Stock transfer cannot be sent because it is in '{$trf['status']}' status."];
            }

            $items = Database::fetchAll("SELECT * FROM stock_transfer_items WHERE transfer_id = :id", [':id' => $transferId]);
            if (empty($items)) {
                $db->rollBack();
                return ['success' => false, 'message' => 'No items found in stock transfer.'];
            }

            $fromBranchId = (int)$trf['from_branch_id'];
            $toBranchId   = (int)$trf['to_branch_id'];

            foreach ($items as $item) {
                $lotId     = (int)$item['source_lot_id'];
                $productId = (int)$item['product_id'];
                $qty       = (float)$item['quantity'];

                // 1. Verify & Deduct source lot
                $lot = Database::fetchOne("SELECT * FROM product_lots WHERE id = :id FOR UPDATE", [':id' => $lotId]);
                if (!$lot || (float)$lot['remaining_quantity'] < $qty) {
                    $db->rollBack();
                    return ['success' => false, 'message' => "Insufficient stock in source lot #{$lotId} during send."];
                }

                $newLotQty = round((float)$lot['remaining_quantity'] - $qty, 2);
                $lotStatus = $newLotQty > 0 ? 'active' : 'depleted';

                $db->prepare("
                    UPDATE product_lots SET remaining_quantity = :rem_q, status = :st WHERE id = :id
                ")->execute([':rem_q' => $newLotQty, ':st' => $lotStatus, ':id' => $lotId]);

                // 2. Deduct source branch sellable inventory
                $srcInv = Database::fetchOne("
                    SELECT quantity FROM branch_inventory WHERE branch_id = :b AND product_id = :p FOR UPDATE
                ", [':b' => $fromBranchId, ':p' => $productId]);

                $newSrcInvQty = round(((float)($srcInv['quantity'] ?? 0)) - $qty, 2);
                if ($newSrcInvQty < 0) {
                    $db->rollBack();
                    return ['success' => false, 'message' => "Transfer results in negative stock for product #{$productId} at source branch."];
                }

                $db->prepare("
                    UPDATE branch_inventory SET quantity = :q WHERE branch_id = :b AND product_id = :p
                ")->execute([':q' => $newSrcInvQty, ':b' => $fromBranchId, ':p' => $productId]);

                // 3. Increase In-Transit quantity at Destination Branch
                $destInv = Database::fetchOne("
                    SELECT in_transit_quantity FROM branch_inventory WHERE branch_id = :b AND product_id = :p FOR UPDATE
                ", [':b' => $toBranchId, ':p' => $productId]);

                if ($destInv) {
                    $newInTransit = round((float)$destInv['in_transit_quantity'] + $qty, 2);
                    $db->prepare("
                        UPDATE branch_inventory SET in_transit_quantity = :itq WHERE branch_id = :b AND product_id = :p
                    ")->execute([':itq' => $newInTransit, ':b' => $toBranchId, ':p' => $productId]);
                } else {
                    $db->prepare("
                        INSERT INTO branch_inventory (branch_id, product_id, quantity, in_transit_quantity, low_stock_threshold)
                        VALUES (:b, :p, 0.00, :itq, 10.00)
                    ")->execute([':b' => $toBranchId, ':p' => $productId, ':itq' => $qty]);
                }

                // 4. Append Stock Ledger entry for Transfer Out
                $db->prepare("
                    INSERT INTO stock_ledger (
                        branch_id, product_id, lot_id, movement_type, quantity, balance_after, reference_no, notes, user_id
                    ) VALUES (
                        :b, :p, :lot, 'transfer_out', :q, :bal, :ref, :notes, :u
                    )
                ")->execute([
                    ':b'     => $fromBranchId,
                    ':p'     => $productId,
                    ':lot'   => $lotId,
                    ':q'     => -$qty,
                    ':bal'   => $newSrcInvQty,
                    ':ref'   => $trf['transfer_number'],
                    ':notes' => "Stock Transfer Sent to Branch #{$toBranchId}",
                    ':u'     => $userId
                ]);
            }

            // Update Transfer Status
            $db->prepare("
                UPDATE stock_transfers SET status = 'sent', sent_by = :u, sent_at = CURRENT_TIMESTAMP WHERE id = :id
            ")->execute([':u' => $userId, ':id' => $transferId]);

            $db->commit();

            Audit::log('STOCK_TRANSFER_SENT', 'inventory', (string)$transferId, null, [
                'transfer_number' => $trf['transfer_number'], 'from_branch_id' => $fromBranchId, 'to_branch_id' => $toBranchId
            ]);

            return [
                'success' => true,
                'message' => "Stock transfer {$trf['transfer_number']} sent successfully. Stock is now In-Transit."
            ];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to send stock transfer #{$transferId}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Cancel Stock Transfer (Draft or Sent)
     */
    public static function cancelTransfer(int $transferId, int $userId): array
    {
        $db = Database::getInstance()->getConnection();

        try {
            $db->beginTransaction();

            $trf = Database::fetchOne("SELECT * FROM stock_transfers WHERE id = :id FOR UPDATE", [':id' => $transferId]);
            if (!$trf) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Stock transfer not found.'];
            }

            if ($trf['status'] === 'received') {
                $db->rollBack();
                return ['success' => false, 'message' => 'Cannot cancel a received stock transfer.'];
            }

            if ($trf['status'] === 'cancelled') {
                $db->rollBack();
                return ['success' => false, 'message' => 'Stock transfer is already cancelled.'];
            }

            if ($trf['status'] === 'sent') {
                $fromBranchId = (int)$trf['from_branch_id'];
                $toBranchId   = (int)$trf['to_branch_id'];
                $items = Database::fetchAll("SELECT * FROM stock_transfer_items WHERE transfer_id = :id", [':id' => $transferId]);

                foreach ($items as $item) {
                    $productId = (int)$item['product_id'];
                    $srcLotId  = (int)$item['source_lot_id'];
                    $qty       = (float)$item['quantity'];

                    // 1. Return quantity back to source lot
                    $lot = Database::fetchOne("SELECT * FROM product_lots WHERE id = :id FOR UPDATE", [':id' => $srcLotId]);
                    if ($lot) {
                        $newLotQty = round((float)$lot['remaining_quantity'] + $qty, 2);
                        $db->prepare("UPDATE product_lots SET remaining_quantity = :rem, status = 'active' WHERE id = :id")
                           ->execute([':rem' => $newLotQty, ':id' => $srcLotId]);
                    }

                    // 2. Return quantity to source branch sellable inventory
                    $srcInv = Database::fetchOne("SELECT quantity FROM branch_inventory WHERE branch_id = :b AND product_id = :p FOR UPDATE", [':b' => $fromBranchId, ':p' => $productId]);
                    $newSrcQty = round(((float)($srcInv['quantity'] ?? 0)) + $qty, 2);
                    $db->prepare("UPDATE branch_inventory SET quantity = :q WHERE branch_id = :b AND product_id = :p")
                       ->execute([':q' => $newSrcQty, ':b' => $fromBranchId, ':p' => $productId]);

                    // 3. Clear in_transit_quantity from destination branch
                    $destInv = Database::fetchOne("SELECT in_transit_quantity FROM branch_inventory WHERE branch_id = :b AND product_id = :p FOR UPDATE", [':b' => $toBranchId, ':p' => $productId]);
                    $newInTransit = round(max(0.00, ((float)($destInv['in_transit_quantity'] ?? 0)) - $qty), 2);
                    $db->prepare("UPDATE branch_inventory SET in_transit_quantity = :itq WHERE branch_id = :b AND product_id = :p")
                       ->execute([':itq' => $newInTransit, ':b' => $toBranchId, ':p' => $productId]);

                    // 4. Ledger entry for transfer cancel
                    $db->prepare("
                        INSERT INTO stock_ledger (branch_id, product_id, lot_id, movement_type, quantity, balance_after, reference_no, notes, user_id)
                        VALUES (:b, :p, :lot, 'transfer_cancel', :q, :bal, :ref, :notes, :u)
                    ")->execute([
                        ':b'     => $fromBranchId,
                        ':p'     => $productId,
                        ':lot'   => $srcLotId,
                        ':q'     => $qty,
                        ':bal'   => $newSrcQty,
                        ':ref'   => $trf['transfer_number'],
                        ':notes' => "Stock Transfer Cancelled - Stock Returned to Source",
                        ':u'     => $userId
                    ]);
                }
            }

            // Update status to cancelled
            $db->prepare("UPDATE stock_transfers SET status = 'cancelled' WHERE id = :id")->execute([':id' => $transferId]);

            $db->commit();

            Audit::log('STOCK_TRANSFER_CANCELLED', 'inventory', (string)$transferId, null, [
                'transfer_number' => $trf['transfer_number']
            ]);

            return [
                'success' => true,
                'message' => "Stock transfer {$trf['transfer_number']} cancelled successfully."
            ];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to cancel stock transfer #{$transferId}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Receive Stock Transfer (Adds Destination Stock & Clears In-Transit)
     */
    public static function receiveTransfer(int $transferId, int $userId): array
    {
        $db = Database::getInstance()->getConnection();

        try {
            $db->beginTransaction();

            $trf = Database::fetchOne("SELECT * FROM stock_transfers WHERE id = :id", [':id' => $transferId]);
            if (!$trf) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Stock transfer not found.'];
            }

            if ($trf['status'] === 'received') {
                $db->rollBack();
                return ['success' => false, 'message' => 'Double receipt blocked! This stock transfer has already been received.'];
            }

            if ($trf['status'] !== 'sent') {
                $db->rollBack();
                return ['success' => false, 'message' => "Only transfers in 'sent' status can be received (Current status: {$trf['status']})."];
            }

            $items = Database::fetchAll("SELECT * FROM stock_transfer_items WHERE transfer_id = :id", [':id' => $transferId]);
            $toBranchId = (int)$trf['to_branch_id'];

            foreach ($items as $item) {
                $productId = (int)$item['product_id'];
                $srcLotId  = (int)$item['source_lot_id'];
                $qty       = (float)$item['quantity'];
                $cost      = (float)$item['cost'];
                $price     = (float)$item['selling_price'];

                // 1. Fetch Source Lot to preserve lot number identity, expiry date & batch number
                $srcLot = Database::fetchOne("SELECT lot_number, expiry_date, batch_number FROM product_lots WHERE id = :id", [':id' => $srcLotId]);
                $lotNum = ($srcLot['lot_number'] ?? 'LOT') . '-TRF' . $toBranchId;
                $expiryDate = !empty($srcLot['expiry_date']) ? $srcLot['expiry_date'] : null;
                $batchNumber = !empty($srcLot['batch_number']) ? $srcLot['batch_number'] : null;

                // 2. Create or find Destination Lot preserving exact cost, price, expiry date and batch
                $destLotId = null;
                $lotCheckSql = "
                    SELECT id, remaining_quantity FROM product_lots
                    WHERE branch_id = :b AND product_id = :p AND cost = :cost AND selling_price = :price AND status = 'active'
                ";
                $lotCheckParams = [':b' => $toBranchId, ':p' => $productId, ':cost' => $cost, ':price' => $price];
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
                $lotCheckSql .= " ORDER BY id DESC LIMIT 1";

                $existingDestLot = Database::fetchOne($lotCheckSql, $lotCheckParams);

                if ($existingDestLot) {
                    $destLotId = (int)$existingDestLot['id'];
                    $newRem = round((float)$existingDestLot['remaining_quantity'] + $qty, 2);
                    $db->prepare("UPDATE product_lots SET remaining_quantity = :rem WHERE id = :id")
                       ->execute([':rem' => $newRem, ':id' => $destLotId]);
                } else {
                    $stmtL = $db->prepare("
                        INSERT INTO product_lots (lot_number, product_id, branch_id, cost, selling_price, initial_quantity, remaining_quantity, status, expiry_date, batch_number, source_type, source_id)
                        VALUES (:lot_no, :p, :b, :cost, :price, :init_q, :rem_q, 'active', :exp, :batch, 'transfer', :src_id)
                    ");
                    $stmtL->execute([
                        ':lot_no' => $lotNum,
                        ':p'      => $productId,
                        ':b'      => $toBranchId,
                        ':cost'   => $cost,
                        ':price'  => $price,
                        ':init_q' => $qty,
                        ':rem_q'  => $qty,
                        ':exp'    => $expiryDate,
                        ':batch'  => $batchNumber,
                        ':src_id' => $transferId
                    ]);
                    $destLotId = (int)$db->lastInsertId();
                }

                // Update item with dest_lot_id
                $db->prepare("UPDATE stock_transfer_items SET dest_lot_id = :d_lot WHERE id = :id")
                   ->execute([':d_lot' => $destLotId, ':id' => $item['id']]);

                // 3. Update Destination Branch Inventory (Increase sellable, Decrease In-Transit)
                $destInv = Database::fetchOne("
                    SELECT quantity, in_transit_quantity FROM branch_inventory WHERE branch_id = :b AND product_id = :p FOR UPDATE
                ", [':b' => $toBranchId, ':p' => $productId]);

                $curSellable  = (float)($destInv['quantity'] ?? 0.00);
                $curInTransit = (float)($destInv['in_transit_quantity'] ?? 0.00);

                $newSellable  = round($curSellable + $qty, 2);
                $newInTransit = round(max(0.00, $curInTransit - $qty), 2);

                if ($destInv) {
                    $db->prepare("
                        UPDATE branch_inventory SET quantity = :q, in_transit_quantity = :itq WHERE branch_id = :b AND product_id = :p
                    ")->execute([':q' => $newSellable, ':itq' => $newInTransit, ':b' => $toBranchId, ':p' => $productId]);
                } else {
                    $db->prepare("
                        INSERT INTO branch_inventory (branch_id, product_id, quantity, in_transit_quantity, low_stock_threshold)
                        VALUES (:b, :p, :q, 0.00, 10.00)
                    ")->execute([':b' => $toBranchId, ':p' => $productId, ':q' => $newSellable]);
                }

                // 4. Append Stock Ledger entry for Transfer In
                $db->prepare("
                    INSERT INTO stock_ledger (
                        branch_id, product_id, lot_id, movement_type, quantity, balance_after, reference_no, notes, user_id
                    ) VALUES (
                        :b, :p, :lot, 'transfer_in', :q, :bal, :ref, :notes, :u
                    )
                ")->execute([
                    ':b'     => $toBranchId,
                    ':p'     => $productId,
                    ':lot'   => $destLotId,
                    ':q'     => $qty,
                    ':bal'   => $newSellable,
                    ':ref'   => $trf['transfer_number'],
                    ':notes' => "Stock Transfer Received from Branch #{$trf['from_branch_id']}",
                    ':u'     => $userId
                ]);
            }

            // Update Transfer Header Status
            $db->prepare("
                UPDATE stock_transfers SET status = 'received', received_by = :u, received_at = CURRENT_TIMESTAMP WHERE id = :id
            ")->execute([':u' => $userId, ':id' => $transferId]);

            $db->commit();

            Audit::log('STOCK_TRANSFER_RECEIVED', 'inventory', (string)$transferId, null, [
                'transfer_number' => $trf['transfer_number'], 'to_branch_id' => $toBranchId
            ]);

            return [
                'success' => true,
                'message' => "Stock transfer {$trf['transfer_number']} received successfully into Branch stock."
            ];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to receive stock transfer #{$transferId}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // =========================================================================
    // 2. STOCK ADJUSTMENTS (INCREASE / DECREASE WITH ACCOUNTING)
    // =========================================================================

    /**
     * Create Authorized Stock Adjustment
     */
    public static function createAdjustment(array $data, int $userId): array
    {
        $db = Database::getInstance()->getConnection();
        $isNested = $db->inTransaction();

        try {
            if (!$isNested) {
                $db->beginTransaction();
            }

            $branchId = (int)($data['branch_id'] ?? 0);
            $productId = (int)($data['product_id'] ?? 0);
            $lotId     = (int)($data['lot_id'] ?? 0);
            $type      = strtolower(trim($data['adjustment_type'] ?? '')); // 'increase' or 'decrease'
            $qty       = round((float)($data['quantity'] ?? 0), 2);
            $reason    = trim($data['reason'] ?? '');
            $notes     = !empty($data['notes']) ? trim($data['notes']) : null;

            if ($branchId <= 0 || $productId <= 0 || $lotId <= 0 || $qty <= 0.00 || !in_array($type, ['increase', 'decrease']) || empty($reason)) {
                if (!$isNested && $db->inTransaction()) {
                    $db->rollBack();
                }
                return ['success' => false, 'message' => 'Valid branch, product, lot, adjustment type, quantity > 0 and reason are required.'];
            }

            // Verify Lot
            $lot = Database::fetchOne("
                SELECT * FROM product_lots WHERE id = :id AND branch_id = :b AND product_id = :p FOR UPDATE
            ", [':id' => $lotId, ':b' => $branchId, ':p' => $productId]);

            if (!$lot) {
                if (!$isNested && $db->inTransaction()) {
                    $db->rollBack();
                }
                return ['success' => false, 'message' => 'Specified stock lot not found for this branch and product.'];
            }

            $lotCost = (float)$lot['cost'];
            $totalCost = round($qty * $lotCost, 2);

            $inv = Database::fetchOne("
                SELECT quantity FROM branch_inventory WHERE branch_id = :b AND product_id = :p FOR UPDATE
            ", [':b' => $branchId, ':p' => $productId]);

            $curInvQty = (float)($inv['quantity'] ?? 0.00);

            if ($type === 'decrease') {
                if ((float)$lot['remaining_quantity'] < $qty || $curInvQty < $qty) {
                    if (!$isNested && $db->inTransaction()) {
                        $db->rollBack();
                    }
                    return [
                        'success' => false,
                        'message' => "Stock adjustment decrease cannot exceed available sellable stock (Lot available: {$lot['remaining_quantity']}, Branch total: {$curInvQty})."
                    ];
                }

                $newLotQty = round((float)$lot['remaining_quantity'] - $qty, 2);
                $newInvQty = round($curInvQty - $qty, 2);
                $lotStatus = $newLotQty > 0 ? 'active' : 'depleted';
                $mvType    = 'adjustment_decrease';
                $ledgerQty = -$qty;
            } else {
                $newLotQty = round((float)$lot['remaining_quantity'] + $qty, 2);
                $newInvQty = round($curInvQty + $qty, 2);
                $lotStatus = 'active';
                $mvType    = 'adjustment_increase';
                $ledgerQty = $qty;
            }

            // Update Lot & Branch Inventory
            $db->prepare("UPDATE product_lots SET remaining_quantity = :rem, status = :st WHERE id = :id")
               ->execute([':rem' => $newLotQty, ':st' => $lotStatus, ':id' => $lotId]);

            $db->prepare("UPDATE branch_inventory SET quantity = :q WHERE branch_id = :b AND product_id = :p")
               ->execute([':q' => $newInvQty, ':b' => $branchId, ':p' => $productId]);

            $adjNo = self::generateNumber('ADJ', 'stock_adjustments');

            // Insert Adjustment Header
            $stmtAdj = $db->prepare("
                INSERT INTO stock_adjustments (
                    adjustment_number, branch_id, product_id, lot_id, adjustment_type, quantity, cost, reason, notes, created_by
                ) VALUES (
                    :no, :b, :p, :lot, :type, :q, :cost, :reason, :notes, :u
                )
            ");

            $stmtAdj->execute([
                ':no'     => $adjNo,
                ':b'      => $branchId,
                ':p'      => $productId,
                ':lot'    => $lotId,
                ':type'   => $type,
                ':q'      => $qty,
                ':cost'   => $lotCost,
                ':reason' => $reason,
                ':notes'  => $notes,
                ':u'      => $userId
            ]);

            $adjId = (int)$db->lastInsertId();

            // Append Stock Ledger Entry
            $db->prepare("
                INSERT INTO stock_ledger (
                    branch_id, product_id, lot_id, movement_type, quantity, balance_after, reference_no, notes, user_id
                ) VALUES (
                    :b, :p, :lot, :mv, :q, :bal, :ref, :notes, :u
                )
            ")->execute([
                ':b'     => $branchId,
                ':p'     => $productId,
                ':lot'   => $lotId,
                ':mv'    => $mvType,
                ':q'     => $ledgerQty,
                ':bal'   => $newInvQty,
                ':ref'   => $adjNo,
                ':notes' => "Stock Adjustment ({$type}): {$reason}",
                ':u'     => $userId
            ]);

            // Double-Entry Accounting Entry
            $invAssetCoaId = Accounting::getAccountIdByCode(Accounting::CODE_INVENTORY_ASSET); // 1200
            $adjCoaId      = Accounting::getAccountIdByCode(Accounting::CODE_INVENTORY_ADJUSTMENTS); // 5020 or 5300

            if ($type === 'increase') {
                // Dr Inventory Asset, Cr Inventory Adjustment
                $lines = [
                    ['account_id' => $invAssetCoaId, 'debit' => $totalCost, 'credit' => 0.00, 'memo' => "Stock Adjustment Increase #{$adjNo}"],
                    ['account_id' => $adjCoaId, 'debit' => 0.00, 'credit' => $totalCost, 'memo' => "Adjustment Offset: {$reason}"]
                ];
            } else {
                // Dr Inventory Adjustment, Cr Inventory Asset
                $lines = [
                    ['account_id' => $adjCoaId, 'debit' => $totalCost, 'credit' => 0.00, 'memo' => "Adjustment Expense: {$reason}"],
                    ['account_id' => $invAssetCoaId, 'debit' => 0.00, 'credit' => $totalCost, 'memo' => "Stock Adjustment Decrease #{$adjNo}"]
                ];
            }

            $jeHeader = [
                'branch_id'   => $branchId,
                'entry_date'  => date('Y-m-d'),
                'source_type' => 'manual',
                'source_id'   => $adjId,
                'description' => "Stock Adjustment #{$adjNo} ({$type}): {$reason}",
                'created_by'  => $userId
            ];

            $jeRes = Accounting::postJournalEntry($jeHeader, $lines);
            if ($jeRes['success']) {
                $db->prepare("UPDATE stock_adjustments SET journal_entry_id = :je WHERE id = :id")
                   ->execute([':je' => $jeRes['journal_entry_id'], ':id' => $adjId]);
            }

            if (!$isNested) {
                $db->commit();
            }

            Audit::log('STOCK_ADJUSTMENT_POSTED', 'inventory', (string)$adjId, null, [
                'adjustment_number' => $adjNo, 'type' => $type, 'quantity' => $qty, 'cost' => $totalCost
            ]);

            return [
                'success'           => true,
                'adjustment_id'     => $adjId,
                'adjustment_number' => $adjNo,
                'message'           => "Stock adjustment {$adjNo} ({$type} of {$qty} units) posted successfully."
            ];

        } catch (Throwable $e) {
            if (!$isNested && $db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to create stock adjustment: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // =========================================================================
    // 3. DAMAGED / WASTAGE STOCK MANAGEMENT & DISPOSAL
    // =========================================================================

    /**
     * Move Sellable Stock to Damaged or Wastage
     */
    public static function recordDamagedStock(array $data, int $userId): array
    {
        $db = Database::getInstance()->getConnection();

        try {
            $db->beginTransaction();

            $branchId  = (int)($data['branch_id'] ?? 0);
            $productId = (int)($data['product_id'] ?? 0);
            $lotId     = (int)($data['lot_id'] ?? 0);
            $qty       = round((float)($data['quantity'] ?? 0), 2);
            $stockType = strtolower(trim($data['stock_type'] ?? 'damaged')); // 'damaged' or 'wastage'
            $reason    = trim($data['reason'] ?? '');

            if ($branchId <= 0 || $productId <= 0 || $lotId <= 0 || $qty <= 0.00 || empty($reason)) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Valid branch, product, lot, quantity > 0 and reason are required.'];
            }

            // Verify Lot
            $lot = Database::fetchOne("
                SELECT * FROM product_lots WHERE id = :id AND branch_id = :b AND product_id = :p FOR UPDATE
            ", [':id' => $lotId, ':b' => $branchId, ':p' => $productId]);

            if (!$lot || (float)$lot['remaining_quantity'] < $qty) {
                $db->rollBack();
                return ['success' => false, 'message' => "Insufficient stock available in lot #{$lotId} to move to {$stockType}."];
            }

            $inv = Database::fetchOne("
                SELECT quantity, damaged_quantity FROM branch_inventory WHERE branch_id = :b AND product_id = :p FOR UPDATE
            ", [':b' => $branchId, ':p' => $productId]);

            $curSellable = (float)($inv['quantity'] ?? 0.00);
            if ($curSellable < $qty) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Insufficient sellable branch stock.'];
            }

            $newLotQty     = round((float)$lot['remaining_quantity'] - $qty, 2);
            $newSellable   = round($curSellable - $qty, 2);
            $newDamagedQty = round(((float)($inv['damaged_quantity'] ?? 0.00)) + $qty, 2);
            $lotStatus     = $newLotQty > 0 ? 'active' : 'depleted';
            $lotCost       = (float)$lot['cost'];
            $totalCost     = round($qty * $lotCost, 2);

            // Deduct sellable, increase damaged
            $db->prepare("UPDATE product_lots SET remaining_quantity = :rem, status = :st WHERE id = :id")
               ->execute([':rem' => $newLotQty, ':st' => $lotStatus, ':id' => $lotId]);

            $db->prepare("UPDATE branch_inventory SET quantity = :q, damaged_quantity = :dq WHERE branch_id = :b AND product_id = :p")
               ->execute([':q' => $newSellable, ':dq' => $newDamagedQty, ':b' => $branchId, ':p' => $productId]);

            // Insert into damaged_stock log
            $stmtD = $db->prepare("
                INSERT INTO damaged_stock (
                    branch_id, product_id, lot_id, quantity, stock_type, cost, reason, logged_by, status
                ) VALUES (
                    :b, :p, :lot, :q, :stype, :cost, :reason, :u, 'active'
                )
            ");
            $stmtD->execute([
                ':b'     => $branchId,
                ':p'     => $productId,
                ':lot'   => $lotId,
                ':q'     => $qty,
                ':stype' => $stockType,
                ':cost'  => $lotCost,
                ':reason'=> $reason,
                ':u'     => $userId
            ]);

            $dmgId = (int)$db->lastInsertId();

            // Append Stock Ledger entry
            $db->prepare("
                INSERT INTO stock_ledger (
                    branch_id, product_id, lot_id, movement_type, quantity, balance_after, reference_no, notes, user_id
                ) VALUES (
                    :b, :p, :lot, :mv, :q, :bal, :ref, :notes, :u
                )
            ")->execute([
                ':b'     => $branchId,
                ':p'     => $productId,
                ':lot'   => $lotId,
                ':mv'    => in_array($stockType, ['damaged', 'wastage', 'expired']) ? 'damage_wastage' : 'adjustment',
                ':q'     => -$qty,
                ':bal'   => $newSellable,
                ':ref'   => "DMG-{$dmgId}",
                ':notes' => ($stockType === 'expired' ? "Expired Stock Write-Off: {$reason}" : "Moved to {$stockType}: {$reason}"),
                ':u'     => $userId
            ]);

            // Accounting Entry (Dr Damage/Wastage/Expired Loss 5310/1250, Cr Inventory Asset 1200)
            $invAssetCoaId = Accounting::getAccountIdByCode(Accounting::CODE_INVENTORY_ASSET);
            $damageCoaId   = Accounting::getAccountIdByCode(Accounting::CODE_DAMAGED_LOSS); // 1250 / 5310

            $lines = [
                ['account_id' => $damageCoaId, 'debit' => $totalCost, 'credit' => 0.00, 'memo' => "{$stockType} Expense: {$reason}"],
                ['account_id' => $invAssetCoaId, 'debit' => 0.00, 'credit' => $totalCost, 'memo' => "Stock Removal to {$stockType}"]
            ];

            $descType = ($stockType === 'expired') ? 'Expired Stock Write-Off' : "Damaged/Wastage Stock Log";
            $jeHeader = [
                'branch_id'   => $branchId,
                'entry_date'  => date('Y-m-d'),
                'source_type' => 'manual',
                'source_id'   => $dmgId,
                'description' => "{$descType} #DMG-{$dmgId}: {$reason}",
                'created_by'  => $userId
            ];

            $jeRes = Accounting::postJournalEntry($jeHeader, $lines);
            $newJeId = $jeRes['journal_id'] ?? $jeRes['journal_entry_id'] ?? null;
            if ($newJeId) {
                $db->prepare("UPDATE damaged_stock SET journal_entry_id = :je WHERE id = :id")
                   ->execute([':je' => $newJeId, ':id' => $dmgId]);
            }

            $db->commit();

            Audit::log('DAMAGED_STOCK_RECORDED', 'inventory', (string)$dmgId, null, [
                'stock_type' => $stockType, 'quantity' => $qty, 'cost' => $totalCost
            ]);

            return [
                'success' => true,
                'id'      => $dmgId,
                'message' => "{$qty} units moved to {$stockType} stock successfully."
            ];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to record damaged stock: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Dispose / Write-Off Damaged Stock (Does NOT reduce sellable stock again)
     */
    public static function disposeDamagedStock(int $damagedStockId, array $data, int $userId): array
    {
        $db = Database::getInstance()->getConnection();

        try {
            $db->beginTransaction();

            $dmg = Database::fetchOne("SELECT * FROM damaged_stock WHERE id = :id FOR UPDATE", [':id' => $damagedStockId]);
            if (!$dmg) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Damaged stock log entry not found.'];
            }

            if ($dmg['status'] === 'disposed') {
                $db->rollBack();
                return ['success' => false, 'message' => 'This damaged stock record has already been disposed.'];
            }

            $branchId  = (int)$dmg['branch_id'];
            $productId = (int)$dmg['product_id'];
            $qty       = (float)$dmg['quantity'];
            $notes     = !empty($data['notes']) ? trim($data['notes']) : 'Physical disposal/scrap write-off';

            // Reduce damaged_quantity in branch_inventory (DOES NOT TOUCH SELLABLE QUANTITY!)
            $inv = Database::fetchOne("
                SELECT damaged_quantity FROM branch_inventory WHERE branch_id = :b AND product_id = :p FOR UPDATE
            ", [':b' => $branchId, ':p' => $productId]);

            $newDamagedQty = round(max(0.00, ((float)($inv['damaged_quantity'] ?? 0.00)) - $qty), 2);

            $db->prepare("
                UPDATE branch_inventory SET damaged_quantity = :dq WHERE branch_id = :b AND product_id = :p
            ")->execute([':dq' => $newDamagedQty, ':b' => $branchId, ':p' => $productId]);

            // Update Damaged Stock record status
            $db->prepare("
                UPDATE damaged_stock SET status = 'disposed', disposed_at = CURRENT_TIMESTAMP, disposed_by = :u, disposal_notes = :notes WHERE id = :id
            ")->execute([':u' => $userId, ':notes' => $notes, ':id' => $damagedStockId]);

            $db->commit();

            Audit::log('DAMAGED_STOCK_DISPOSED', 'inventory', (string)$damagedStockId, null, [
                'quantity' => $qty, 'notes' => $notes
            ]);

            return [
                'success' => true,
                'message' => "Damaged stock log #{$damagedStockId} marked as disposed. Sellable stock was preserved."
            ];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to dispose damaged stock: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // =========================================================================
    // 4. PHYSICAL STOCK AUDIT WORKFLOW
    // =========================================================================

    /**
     * Start Physical Stock Audit (Snapshots Expected Stock)
     */
    public static function startStockAudit(int $branchId, ?string $notes, int $userId): array
    {
        $db = Database::getInstance()->getConnection();

        try {
            $db->beginTransaction();

            // Check existing active audit session for branch
            $stmtAct = $db->prepare("
                SELECT id, audit_number FROM stock_audits WHERE branch_id = :b AND status IN ('draft', 'counting', 'review')
            ");
            $stmtAct->execute([':b' => $branchId]);
            $activeAudit = $stmtAct->fetch(PDO::FETCH_ASSOC);

            if ($activeAudit) {
                $db->rollBack();
                return [
                    'success' => false,
                    'message' => "An active stock audit (#{$activeAudit['audit_number']}) is already in progress for this branch."
                ];
            }

            $auditNo = self::generateNumber('AUD', 'stock_audits');

            $db->prepare("
                INSERT INTO stock_audits (audit_number, branch_id, audit_date, status, notes, created_by)
                VALUES (:no, :b, :adate, 'draft', :notes, :u)
            ")->execute([
                ':no'    => $auditNo,
                ':b'     => $branchId,
                ':adate' => date('Y-m-d'),
                ':notes' => $notes,
                ':u'     => $userId
            ]);

            $auditId = (int)$db->lastInsertId();

            // Snapshot all active lots in this branch into audit items
            $stmtLots = $db->prepare("
                SELECT pl.id AS lot_id, pl.product_id, pl.remaining_quantity, pl.cost
                FROM product_lots pl
                WHERE pl.branch_id = :b AND pl.status = 'active' AND pl.remaining_quantity > 0
            ");
            $stmtLots->execute([':b' => $branchId]);
            $lots = $stmtLots->fetchAll(PDO::FETCH_ASSOC);

            $stmtItem = $db->prepare("
                INSERT INTO stock_audit_items (audit_id, product_id, lot_id, expected_quantity, counted_quantity, variance_quantity, cost)
                VALUES (:aud_id, :p, :lot, :exp, :cnt, 0.00, :cost)
            ");

            foreach ($lots as $l) {
                $stmtItem->execute([
                    ':aud_id' => $auditId,
                    ':p'      => $l['product_id'],
                    ':lot'    => $l['lot_id'],
                    ':exp'    => (float)$l['remaining_quantity'],
                    ':cnt'    => (float)$l['remaining_quantity'],
                    ':cost'   => (float)$l['cost']
                ]);
            }

            $db->commit();

            Audit::log('STOCK_AUDIT_STARTED', 'inventory', (string)$auditId, null, [
                'audit_number' => $auditNo, 'branch_id' => $branchId, 'items_snapshotted' => count($lots)
            ]);

            return [
                'success'      => true,
                'audit_id'     => $auditId,
                'audit_number' => $auditNo,
                'message'      => "Physical Stock Audit {$auditNo} started. Expected stock snapshotted."
            ];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to start stock audit: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Save Counted Quantities for Stock Audit
     */
    public static function saveAuditCounts(int $auditId, array $items, int $userId): array
    {
        try {
            $audit = Database::fetchOne("SELECT * FROM stock_audits WHERE id = :id", [':id' => $auditId]);
            if (!$audit) {
                return ['success' => false, 'message' => 'Stock audit session not found.'];
            }

            if ($audit['status'] === 'posted') {
                return ['success' => false, 'message' => 'Posted stock audit is locked and immutable.'];
            }

            $db = Database::getInstance()->getConnection();
            $db->beginTransaction();

            $stmtUpdate = $db->prepare("
                UPDATE stock_audit_items SET
                    counted_quantity = :cnt,
                    variance_quantity = :var,
                    notes = :notes
                WHERE audit_id = :aud_id AND id = :item_id
            ");

            foreach ($items as $it) {
                $itemId   = (int)($it['item_id'] ?? 0);
                $counted  = round((float)($it['counted_quantity'] ?? 0), 2);
                $expected = (float)($it['expected_quantity'] ?? 0);
                $variance = round($counted - $expected, 2);
                $notes    = !empty($it['notes']) ? trim($it['notes']) : null;

                $stmtUpdate->execute([
                    ':cnt'    => $counted,
                    ':var'    => $variance,
                    ':notes'  => $notes,
                    ':aud_id' => $auditId,
                    ':item_id'=> $itemId
                ]);
            }

            $db->prepare("UPDATE stock_audits SET status = 'review' WHERE id = :id")->execute([':id' => $auditId]);

            $db->commit();

            return ['success' => true, 'message' => 'Stock audit count saved successfully.'];

        } catch (Throwable $e) {
            Logger::error("Failed to save audit counts: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Post Stock Audit (Applies Variance Adjustments, Stock Ledger & Accounting)
     */
    public static function postStockAudit(int $auditId, int $userId): array
    {
        $db = Database::getInstance()->getConnection();

        try {
            $db->beginTransaction();

            $audit = Database::fetchOne("SELECT * FROM stock_audits WHERE id = :id FOR UPDATE", [':id' => $auditId]);
            if (!$audit) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Stock audit session not found.'];
            }

            if ($audit['status'] === 'posted') {
                $db->rollBack();
                return ['success' => false, 'message' => 'This stock audit has already been posted and locked.'];
            }

            $items = Database::fetchAll("SELECT * FROM stock_audit_items WHERE audit_id = :id", [':id' => $auditId]);
            $branchId = (int)$audit['branch_id'];

            foreach ($items as $it) {
                $varQty = round((float)$it['variance_quantity'], 2);
                if (abs($varQty) < 0.001) {
                    continue; // No variance to adjust
                }

                $productId = (int)$it['product_id'];
                $lotId     = (int)$it['lot_id'];
                $cost      = (float)$it['cost'];

                // Post adjustment for variance
                $adjType = $varQty > 0 ? 'increase' : 'decrease';
                $absQty  = abs($varQty);

                $adjRes = self::createAdjustment([
                    'branch_id'       => $branchId,
                    'product_id'      => $productId,
                    'lot_id'          => $lotId,
                    'adjustment_type' => $adjType,
                    'quantity'        => $absQty,
                    'reason'          => "Stock Audit Variance (#{$audit['audit_number']})",
                    'notes'           => $it['notes']
                ], $userId);

                if (!$adjRes['success']) {
                    $db->rollBack();
                    return ['success' => false, 'message' => "Audit posting failed on product #{$productId}: " . $adjRes['message']];
                }
            }

            // Mark audit as Posted
            $db->prepare("
                UPDATE stock_audits SET status = 'posted', posted_by = :u, posted_at = CURRENT_TIMESTAMP WHERE id = :id
            ")->execute([':u' => $userId, ':id' => $auditId]);

            $db->commit();

            Audit::log('STOCK_AUDIT_POSTED', 'inventory', (string)$auditId, null, [
                'audit_number' => $audit['audit_number'], 'branch_id' => $branchId
            ]);

            return [
                'success' => true,
                'message' => "Stock Audit {$audit['audit_number']} posted successfully. Variance adjustments updated stock & GL."
            ];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to post stock audit #{$auditId}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // =========================================================================
    // 5. INVENTORY OVERVIEW & ENHANCED STOCK LEDGER
    // =========================================================================

    /**
     * Get Comprehensive Branch Inventory Overview
     */
    public static function getInventoryOverview(int $branchId, array $filters = [], int $page = 1, int $limit = 20): array
    {
        try {
            $where = ["p.deleted_at IS NULL"];
            $params = [':branch_id' => $branchId];

            if (!empty($filters['search'])) {
                $where[] = "(p.barcode LIKE :search OR p.name LIKE :search)";
                $params[':search'] = '%' . trim($filters['search']) . '%';
            }

            if (!empty($filters['brand_id'])) {
                $where[] = "p.brand_id = :brand_id";
                $params[':brand_id'] = (int)$filters['brand_id'];
            }

            if (!empty($filters['category_id'])) {
                $where[] = "p.category_id = :category_id";
                $params[':category_id'] = (int)$filters['category_id'];
            }

            $whereSql = implode(" AND ", $where);

            $countRow = Database::fetchOne("SELECT COUNT(*) AS total FROM products p WHERE $whereSql", array_diff_key($params, [':branch_id' => 0]));
            $totalItems = (int)($countRow['total'] ?? 0);

            $offset = ($page - 1) * $limit;

            $items = Database::fetchAll("
                SELECT 
                    p.id AS product_id,
                    p.barcode,
                    p.name AS product_name,
                    b.name AS brand_name,
                    c.name AS category_name,
                    p.cost AS default_cost,
                    p.selling_price AS default_price,
                    COALESCE(bi.quantity, 0.00) AS sellable_quantity,
                    COALESCE(bi.in_transit_quantity, 0.00) AS in_transit_quantity,
                    COALESCE(bi.damaged_quantity, 0.00) AS damaged_quantity,
                    COALESCE(bi.low_stock_threshold, p.low_stock_threshold) AS low_stock_threshold
                FROM products p
                JOIN brands b ON p.brand_id = b.id
                JOIN categories c ON p.category_id = c.id
                LEFT JOIN branch_inventory bi ON p.id = bi.product_id AND bi.branch_id = :branch_id
                WHERE $whereSql
                ORDER BY p.name ASC
                LIMIT $limit OFFSET $offset
            ", $params);

            foreach ($items as &$it) {
                $it['is_low_stock'] = (float)$it['sellable_quantity'] <= (float)$it['low_stock_threshold'];
                $it['lots'] = Database::fetchAll("
                    SELECT id, lot_number, cost, selling_price, remaining_quantity, status
                    FROM product_lots
                    WHERE branch_id = :b AND product_id = :p AND status = 'active' AND remaining_quantity > 0
                    ORDER BY id ASC
                ", [':b' => $branchId, ':p' => $it['product_id']]);
            }

            return [
                'items'      => $items,
                'totalItems' => $totalItems,
                'page'       => $page,
                'limit'      => $limit,
                'totalPages' => max(1, ceil($totalItems / $limit))
            ];

        } catch (Throwable $e) {
            Logger::error("Failed to fetch inventory overview: " . $e->getMessage(), $e);
            return ['items' => [], 'totalItems' => 0, 'page' => 1, 'limit' => $limit, 'totalPages' => 1];
        }
    }
}
