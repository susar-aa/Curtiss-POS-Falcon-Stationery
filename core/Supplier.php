<?php
/**
 * Curtiss POS - Supplier Core Component
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Audit.php';

class Supplier
{
    public static function all($activeOnly = false, ?string $search = null): array
    {
        try {
            $where = ["s.deleted_at IS NULL"];
            $params = [];

            if (is_array($activeOnly)) {
                if (!empty($activeOnly['status']) && $activeOnly['status'] === 'active') {
                    $where[] = "s.status = 'active'";
                } elseif (!empty($activeOnly['activeOnly'])) {
                    $where[] = "s.status = 'active'";
                }
                if (!empty($activeOnly['search'])) {
                    $search = (string)$activeOnly['search'];
                }
            } elseif ($activeOnly) {
                $where[] = "s.status = 'active'";
            }

            if (!empty($search)) {
                $searchTerm = '%' . trim($search) . '%';
                $cleanNum = preg_replace('/[^0-9.]/', '', $search);
                $numTerm = !empty($cleanNum) ? '%' . $cleanNum . '%' : '';

                for ($i = 1; $i <= 10; $i++) {
                    $params[":s{$i}"] = $searchTerm;
                }

                $havingSql = "";
                if (!empty($numTerm)) {
                    $params[':h1'] = $numTerm;
                    $params[':h2'] = $searchTerm;
                    $params[':h3'] = $searchTerm;
                    $havingSql = " OR (
                        CAST(total_outstanding AS CHAR) LIKE :h1 OR
                        CONCAT('LKR ', FORMAT(total_outstanding, 2)) LIKE :h2 OR
                        CONCAT('Rs. ', FORMAT(total_outstanding, 2)) LIKE :h3
                    )";
                }

                $whereSql = implode(" AND ", $where);
                $sql = "
                    SELECT 
                        s.*,
                        COALESCE(SUM(GREATEST(0, g.actual_grn_total - g.paid_amount)), 0) + 
                        COALESCE(MAX(CASE WHEN ob.balance_type = 'owe_supplier' THEN GREATEST(0, ob.amount - ob.paid_amount) ELSE 0 END), 0) AS total_outstanding
                    FROM suppliers s
                    LEFT JOIN grns g ON g.supplier_id = s.id AND g.status = 'Approved'
                    LEFT JOIN supplier_opening_balances ob ON ob.supplier_id = s.id AND ob.status = 'posted'
                    WHERE {$whereSql}
                    GROUP BY s.id
                    HAVING (
                        s.supplier_code LIKE :s1 OR 
                        s.name LIKE :s2 OR 
                        s.phone LIKE :s3 OR 
                        s.whatsapp LIKE :s4 OR 
                        s.address LIKE :s5 OR 
                        s.bank_name LIKE :s6 OR 
                        s.bank_account_name LIKE :s7 OR 
                        s.bank_account_no LIKE :s8 OR 
                        s.bank_branch LIKE :s9 OR 
                        s.status LIKE :s10
                        {$havingSql}
                    )
                    ORDER BY s.name ASC
                ";
                return Database::fetchAll($sql, $params);
            }

            $whereSql = implode(" AND ", $where);
            $sql = "SELECT s.* FROM suppliers s WHERE {$whereSql} ORDER BY s.name ASC";
            return Database::fetchAll($sql, $params);
        } catch (Throwable $e) {
            Logger::error("Failed to fetch suppliers: " . $e->getMessage(), $e);
            return [];
        }
    }

    public static function allUnique(bool $activeOnly = false): array
    {
        try {
            $where = ["deleted_at IS NULL"];
            if ($activeOnly) {
                $where[] = "status = 'active'";
            }
            $whereSql = implode(" AND ", $where);
            return Database::fetchAll("SELECT MIN(id) AS id, name, MIN(supplier_code) AS supplier_code FROM suppliers WHERE {$whereSql} GROUP BY name ORDER BY name ASC");
        } catch (Throwable $e) {
            Logger::error("Failed to fetch unique suppliers: " . $e->getMessage(), $e);
            return [];
        }
    }

    public static function find(int $id): ?array
    {
        try {
            return Database::fetchOne("SELECT * FROM suppliers WHERE id = :id AND deleted_at IS NULL", [':id' => $id]);
        } catch (Throwable $e) {
            Logger::error("Failed to find supplier #$id: " . $e->getMessage(), $e);
            return null;
        }
    }

    public static function findByCode(string $code): ?array
    {
        $code = trim($code);
        if (empty($code)) return null;

        try {
            return Database::fetchOne("SELECT * FROM suppliers WHERE supplier_code = :code AND deleted_at IS NULL", [':code' => $code]);
        } catch (Throwable $e) {
            Logger::error("Failed to find supplier by code '$code': " . $e->getMessage(), $e);
            return null;
        }
    }

    public static function generateSupplierCode(): string
    {
        try {
            $maxRow = Database::fetchOne("SELECT MAX(id) AS max_id FROM suppliers");
            $nextId = ((int)($maxRow['max_id'] ?? 0)) + 1;
            $code = 'SUP-' . sprintf('%04d', $nextId);

            // Ensure uniqueness
            $check = Database::fetchOne("SELECT id FROM suppliers WHERE supplier_code = :code", [':code' => $code]);
            while ($check) {
                $nextId++;
                $code = 'SUP-' . sprintf('%04d', $nextId);
                $check = Database::fetchOne("SELECT id FROM suppliers WHERE supplier_code = :code", [':code' => $code]);
            }
            return $code;
        } catch (Throwable $e) {
            Logger::error("Failed to generate supplier code: " . $e->getMessage(), $e);
            return 'SUP-' . rand(1000, 9999);
        }
    }

    public static function create(
        string $name, 
        ?string $phone = null, 
        ?string $whatsapp = null, 
        ?string $address = null, 
        string $status = 'active', 
        ?string $supplierCode = null,
        ?string $bankName = null,
        ?string $bankAccountName = null,
        ?string $bankAccountNo = null,
        ?string $bankBranch = null
    ): array {
        $name = trim($name);
        if (empty($name)) {
            return ['success' => false, 'message' => 'Supplier name is required.'];
        }

        $supplierCode = trim($supplierCode ?? '');
        if (empty($supplierCode)) {
            $supplierCode = self::generateSupplierCode();
        } else {
            $existing = self::findByCode($supplierCode);
            if ($existing) {
                return ['success' => false, 'message' => "Supplier Code '$supplierCode' is already in use by another supplier."];
            }
        }

        try {
            Database::execute("
                INSERT INTO suppliers (
                    supplier_code, name, phone, whatsapp, address, 
                    bank_name, bank_account_name, bank_account_no, bank_branch, status
                ) VALUES (
                    :supplier_code, :name, :phone, :whatsapp, :address, 
                    :bank_name, :bank_account_name, :bank_account_no, :bank_branch, :status
                )
            ", [
                ':supplier_code'     => $supplierCode,
                ':name'              => $name,
                ':phone'             => trim($phone ?? '') ?: null,
                ':whatsapp'          => trim($whatsapp ?? '') ?: null,
                ':address'           => trim($address ?? '') ?: null,
                ':bank_name'         => trim($bankName ?? '') ?: null,
                ':bank_account_name' => trim($bankAccountName ?? '') ?: null,
                ':bank_account_no'   => trim($bankAccountNo ?? '') ?: null,
                ':bank_branch'       => trim($bankBranch ?? '') ?: null,
                ':status'            => $status === 'inactive' ? 'inactive' : 'active'
            ]);

            $newId = (int)Database::lastInsertId();
            Audit::log('SUPPLIER_CREATED', 'suppliers', (string)$newId, null, [
                'supplier_code'     => $supplierCode, 
                'name'              => $name, 
                'phone'             => $phone, 
                'whatsapp'          => $whatsapp, 
                'address'           => $address, 
                'bank_name'         => $bankName,
                'bank_account_name' => $bankAccountName,
                'bank_account_no'   => $bankAccountNo,
                'bank_branch'       => $bankBranch,
                'status'            => $status
            ]);
            return ['success' => true, 'id' => $newId, 'supplier_code' => $supplierCode, 'message' => "Supplier '{$name}' ({$supplierCode}) created successfully."];
        } catch (Throwable $e) {
            Logger::error("Failed to create supplier: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Database error while creating supplier: ' . $e->getMessage()];
        }
    }

    public static function update(
        int $id, 
        string $name, 
        ?string $phone, 
        ?string $whatsapp, 
        ?string $address, 
        string $status, 
        ?string $supplierCode = null,
        ?string $bankName = null,
        ?string $bankAccountName = null,
        ?string $bankAccountNo = null,
        ?string $bankBranch = null
    ): array {
        $name = trim($name);
        if (empty($name)) {
            return ['success' => false, 'message' => 'Supplier name is required.'];
        }

        $supplier = self::find($id);
        if (!$supplier) {
            return ['success' => false, 'message' => 'Supplier not found.'];
        }

        $supplierCode = trim($supplierCode ?? '');
        if (empty($supplierCode)) {
            $supplierCode = $supplier['supplier_code'] ?: self::generateSupplierCode();
        } elseif ($supplierCode !== $supplier['supplier_code']) {
            $existing = self::findByCode($supplierCode);
            if ($existing && (int)$existing['id'] !== $id) {
                return ['success' => false, 'message' => "Supplier Code '$supplierCode' is already assigned to another supplier."];
            }
        }

        try {
            Database::execute("
                UPDATE suppliers 
                SET supplier_code = :supplier_code, 
                    name = :name, 
                    phone = :phone, 
                    whatsapp = :whatsapp, 
                    address = :address, 
                    bank_name = :bank_name,
                    bank_account_name = :bank_account_name,
                    bank_account_no = :bank_account_no,
                    bank_branch = :bank_branch,
                    status = :status, 
                    updated_at = NOW()
                WHERE id = :id
            ", [
                ':supplier_code'     => $supplierCode,
                ':name'              => $name,
                ':phone'             => trim($phone ?? '') ?: null,
                ':whatsapp'          => trim($whatsapp ?? '') ?: null,
                ':address'           => trim($address ?? '') ?: null,
                ':bank_name'         => trim($bankName ?? '') ?: null,
                ':bank_account_name' => trim($bankAccountName ?? '') ?: null,
                ':bank_account_no'   => trim($bankAccountNo ?? '') ?: null,
                ':bank_branch'       => trim($bankBranch ?? '') ?: null,
                ':status'            => $status === 'inactive' ? 'inactive' : 'active',
                ':id'                => $id
            ]);

            Audit::log('SUPPLIER_UPDATED', 'suppliers', (string)$id, $supplier, [
                'supplier_code'     => $supplierCode, 
                'name'              => $name, 
                'phone'             => $phone, 
                'whatsapp'          => $whatsapp, 
                'address'           => $address, 
                'bank_name'         => $bankName,
                'bank_account_name' => $bankAccountName,
                'bank_account_no'   => $bankAccountNo,
                'bank_branch'       => $bankBranch,
                'status'            => $status
            ]);
            return ['success' => true, 'message' => "Supplier '{$name}' updated successfully."];
        } catch (Throwable $e) {
            Logger::error("Failed to update supplier #$id: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Database error while updating supplier.'];
        }
    }

    public static function toggleStatus(int $id): array
    {
        $supplier = self::find($id);
        if (!$supplier) {
            return ['success' => false, 'message' => 'Supplier not found.'];
        }

        $newStatus = ($supplier['status'] === 'active') ? 'inactive' : 'active';
        try {
            Database::execute("UPDATE suppliers SET status = :status WHERE id = :id", [
                ':status' => $newStatus,
                ':id'     => $id
            ]);

            Audit::log('SUPPLIER_STATUS_TOGGLED', 'suppliers', (string)$id, ['status' => $supplier['status']], ['status' => $newStatus]);
            return ['success' => true, 'message' => "Supplier status updated to $newStatus."];
        } catch (Throwable $e) {
            Logger::error("Failed to toggle supplier status #$id: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Database error.'];
        }
    }

    public static function canDelete(int $id): array
    {
        $reasons = [];

        try {
            $poCount = (int)(Database::fetchOne("SELECT COUNT(*) AS c FROM purchase_orders WHERE supplier_id = :id", [':id' => $id])['c'] ?? 0);
            if ($poCount > 0) $reasons[] = "$poCount Purchase Order(s)";

            $grnCount = (int)(Database::fetchOne("SELECT COUNT(*) AS c FROM grns WHERE supplier_id = :id", [':id' => $id])['c'] ?? 0);
            if ($grnCount > 0) $reasons[] = "$grnCount Goods Received Note(s)";

            $pmtCount = (int)(Database::fetchOne("SELECT COUNT(*) AS c FROM supplier_payments WHERE supplier_id = :id", [':id' => $id])['c'] ?? 0);
            if ($pmtCount > 0) $reasons[] = "$pmtCount Supplier Payment(s)";

            $retCount = (int)(Database::fetchOne("SELECT COUNT(*) AS c FROM supplier_returns WHERE supplier_id = :id", [':id' => $id])['c'] ?? 0);
            if ($retCount > 0) $reasons[] = "$retCount Supplier Return(s)";

            $chqCount = (int)(Database::fetchOne("SELECT COUNT(*) AS c FROM supplier_cheques WHERE supplier_id = :id", [':id' => $id])['c'] ?? 0);
            if ($chqCount > 0) $reasons[] = "$chqCount Supplier Cheque(s)";

            $crdCount = (int)(Database::fetchOne("SELECT COUNT(*) AS c FROM supplier_credits WHERE supplier_id = :id", [':id' => $id])['c'] ?? 0);
            if ($crdCount > 0) $reasons[] = "$crdCount Supplier Credit Note(s)";

            return [
                'can_delete' => empty($reasons),
                'reasons'    => $reasons
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to check supplier delete safety for #$id: " . $e->getMessage(), $e);
            return ['can_delete' => false, 'reasons' => ['Database error during safety check']];
        }
    }

    public static function delete(int $id): array
    {
        $supplier = self::find($id);
        if (!$supplier) {
            return ['success' => false, 'message' => 'Supplier not found.'];
        }

        $safety = self::canDelete($id);
        if (!$safety['can_delete']) {
            $reasonStr = implode(', ', $safety['reasons']);
            return [
                'success' => false,
                'message' => "Cannot delete supplier '{$supplier['name']}' because history exists ({$reasonStr}). You may deactivate this supplier instead."
            ];
        }

        try {
            Database::execute("UPDATE suppliers SET deleted_at = NOW(), status = 'inactive' WHERE id = :id", [':id' => $id]);

            Audit::log('SUPPLIER_DELETED', 'suppliers', (string)$id, $supplier, ['deleted_at' => date('Y-m-d H:i:s')]);
            return ['success' => true, 'message' => "Supplier '{$supplier['name']}' moved to Trash Bin."];
        } catch (Throwable $e) {
            Logger::error("Failed to delete supplier #$id: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Database error while deleting supplier.'];
        }
    }

    /**
     * Fetch soft-deleted suppliers in Trash Bin
     */
    public static function deleted(): array
    {
        try {
            return Database::fetchAll("SELECT * FROM suppliers WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC");
        } catch (Throwable $e) {
            Logger::error("Failed to fetch deleted suppliers: " . $e->getMessage(), $e);
            return [];
        }
    }

    /**
     * Restore soft-deleted supplier from Trash Bin
     */
    public static function restore(int $id): array
    {
        try {
            $supplier = Database::fetchOne("SELECT * FROM suppliers WHERE id = :id AND deleted_at IS NOT NULL", [':id' => $id]);
            if (!$supplier) {
                return ['success' => false, 'message' => 'Deleted supplier record not found.'];
            }

            Database::execute("UPDATE suppliers SET deleted_at = NULL WHERE id = :id", [':id' => $id]);
            Audit::log('SUPPLIER_RESTORED', 'suppliers', (string)$id, ['deleted_at' => $supplier['deleted_at']], ['restored_at' => date('Y-m-d H:i:s')]);
            return ['success' => true, 'message' => "Supplier '{$supplier['name']}' restored successfully from Trash Bin."];
        } catch (Throwable $e) {
            Logger::error("Failed to restore supplier #$id: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Database error while restoring supplier.'];
        }
    }

    /**
     * Purchasing & Outstanding Foundation Summary for Supplier
     */
    public static function getPurchaseSummary(int $supplierId): array
    {
        try {
            $poCount = Database::fetchOne("SELECT COUNT(*) AS total FROM purchase_orders WHERE supplier_id = :id", [':id' => $supplierId])['total'] ?? 0;
            $grnRow = Database::fetchOne("
                SELECT COUNT(*) AS count, COALESCE(SUM(actual_grn_total), 0) AS total_purchased 
                FROM grns 
                WHERE supplier_id = :id AND status = 'Approved'
            ", [':id' => $supplierId]);

            $invoices = Database::fetchAll("
                SELECT DISTINCT supplier_invoice_no, supplier_invoice_date, actual_grn_total, grn_number
                FROM grns 
                WHERE supplier_id = :id AND status = 'Approved'
                ORDER BY id DESC LIMIT 10
            ", [':id' => $supplierId]);

            return [
                'total_pos'               => (int)$poCount,
                'total_grns'              => (int)($grnRow['count'] ?? 0),
                'total_purchased_amount'  => (float)($grnRow['total_purchased'] ?? 0.00),
                'recent_invoice_records'  => $invoices
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to fetch supplier purchase summary: " . $e->getMessage(), $e);
            return ['total_pos' => 0, 'total_grns' => 0, 'total_purchased_amount' => 0.00, 'recent_invoice_records' => []];
        }
    }

    /**
     * Get Complete Chronological Activity Ledger for a Supplier with Debit, Credit & Running Balance
     */
    /**
     * Get Complete Chronological Activity Ledger for a Supplier with Debit, Credit & Running Balance
     * Includes: GRNs & Invoices, Supplier Payments, Opening Balances, Supplier Returns, Credit Settlements, POs
     */
    public static function getActivityLedger(int $supplierId, ?int $branchId = null): array
    {
        $activities = [];

        try {
            $baseParams = [':id' => $supplierId];
            if ($branchId !== null && $branchId > 0) {
                $baseParams[':branch_id'] = $branchId;
            }

            // 1. Purchase Orders (Non-financial commitment, running balance impact = 0)
            $poBranchSql = ($branchId !== null && $branchId > 0) ? " AND po.branch_id = :branch_id" : "";
            $pos = Database::fetchAll("
                SELECT 
                    po.id, 
                    po.po_number AS ref_no, 
                    po.order_date AS activity_date, 
                    po.total_amount AS amount, 
                    po.net_amount,
                    po.status, 
                    po.notes, 
                    po.created_at,
                    b.name AS branch_name
                FROM purchase_orders po
                LEFT JOIN branches b ON b.id = po.branch_id
                WHERE po.supplier_id = :id {$poBranchSql}
                ORDER BY po.order_date ASC, po.id ASC
            ", $baseParams);

            foreach ($pos as $po) {
                $poAmt = (float)(!empty($po['net_amount']) && (float)$po['net_amount'] > 0 ? $po['net_amount'] : $po['amount']);
                $activities[] = [
                    'id'             => (int)$po['id'],
                    'type'           => 'PO',
                    'title'          => 'Purchase Order',
                    'badge_class'    => 'act-badge-po',
                    'ref_no'         => $po['ref_no'],
                    'invoice_no'     => null,
                    'invoice_date'   => null,
                    'invoice_amount' => 0.00,
                    'date'           => $po['activity_date'],
                    'date_fmt'       => date('M d, Y', strtotime($po['activity_date'])),
                    'amount'         => $poAmt,
                    'debit'          => 0.00,
                    'credit'         => 0.00,
                    'status'         => $po['status'],
                    'payment_status' => null,
                    'paid_amount'    => 0.00,
                    'due_amount'     => 0.00,
                    'details'        => '#' . $po['ref_no'] . ($po['notes'] ? " - {$po['notes']}" : ''),
                    'branch_name'    => $po['branch_name'] ?? null,
                    'action_url'     => 'po_print.php?id=' . $po['id'],
                    'created_at'     => $po['created_at']
                ];
            }

            // 2. Goods Received Notes (GRN / Supplier Invoices - Approved increases payable liability)
            $grnBranchSql = ($branchId !== null && $branchId > 0) ? " AND g.branch_id = :branch_id" : "";
            $grns = Database::fetchAll("
                SELECT 
                    g.id, 
                    g.grn_number AS ref_no, 
                    g.po_id,
                    g.supplier_invoice_no, 
                    g.supplier_invoice_date, 
                    g.received_date AS activity_date, 
                    g.actual_grn_total AS amount, 
                    g.paid_amount, 
                    g.payment_status, 
                    g.status, 
                    g.supplier_invoice_amount,
                    g.difference_amount,
                    g.difference_reason,
                    g.notes,
                    g.created_at,
                    po.po_number,
                    b.name AS branch_name,
                    COUNT(gi.id) AS item_count
                FROM grns g
                LEFT JOIN purchase_orders po ON po.id = g.po_id
                LEFT JOIN branches b ON b.id = g.branch_id
                LEFT JOIN grn_items gi ON gi.grn_id = g.id
                WHERE g.supplier_id = :id {$grnBranchSql}
                GROUP BY g.id
                ORDER BY g.received_date ASC, g.id ASC
            ", $baseParams);

            foreach ($grns as $g) {
                $actualTotal = (float)$g['amount'];
                $paidAmt = (float)$g['paid_amount'];
                $dueAmt = max(0.00, round($actualTotal - $paidAmt, 2));
                $isApproved = ($g['status'] === 'Approved');

                $detailParts = [];
                if (!empty($g['supplier_invoice_no'])) {
                    $detailParts[] = "Invoice: #{$g['supplier_invoice_no']}";
                }
                if (!empty($g['po_number'])) {
                    $detailParts[] = "PO: {$g['po_number']}";
                }
                if (!empty($g['item_count'])) {
                    $detailParts[] = "{$g['item_count']} item(s)";
                }
                if (!empty($g['notes'])) {
                    $detailParts[] = $g['notes'];
                }

                $activities[] = [
                    'id'             => (int)$g['id'],
                    'type'           => 'GRN',
                    'title'          => !empty($g['supplier_invoice_no']) ? 'GRN / Invoice' : 'GRN',
                    'badge_class'    => 'act-badge-grn',
                    'ref_no'         => $g['ref_no'],
                    'invoice_no'     => $g['supplier_invoice_no'] ?? null,
                    'invoice_date'   => $g['supplier_invoice_date'] ?? null,
                    'invoice_amount' => (float)($g['supplier_invoice_amount'] ?? 0.00),
                    'date'           => $g['activity_date'],
                    'date_fmt'       => date('M d, Y', strtotime($g['activity_date'])),
                    'amount'         => $actualTotal,
                    'debit'          => $isApproved ? $actualTotal : 0.00,
                    'credit'         => 0.00,
                    'status'         => $g['status'],
                    'payment_status' => $g['payment_status'] ?? 'unpaid',
                    'paid_amount'    => $paidAmt,
                    'due_amount'     => $dueAmt,
                    'details'        => implode(' • ', $detailParts),
                    'branch_name'    => $g['branch_name'] ?? null,
                    'action_url'     => 'grn_print.php?id=' . $g['id'],
                    'created_at'     => $g['created_at']
                ];
            }

            // 3. Supplier Payments (Payment - Credit reduces payable liability)
            $spBranchSql = ($branchId !== null && $branchId > 0) ? " AND sp.branch_id = :branch_id" : "";
            $payments = Database::fetchAll("
                SELECT 
                    sp.id, 
                    sp.payment_no AS ref_no, 
                    sp.payment_date AS activity_date, 
                    sp.payment_method, 
                    sp.total_amount AS amount, 
                    sp.bank_name, 
                    sp.reference_no, 
                    sp.notes, 
                    sp.created_at, 
                    sc.cheque_number,
                    sc.cheque_date,
                    sc.status AS cheque_status,
                    ba.bank_name AS account_bank,
                    ba.account_name,
                    ba.account_number,
                    b.name AS branch_name,
                    GROUP_CONCAT(DISTINCT CONCAT(g.grn_number, ' (Rs: ', FORMAT(spg.allocated_amount, 2), ')') SEPARATOR ', ') AS allocated_grns
                FROM supplier_payments sp
                LEFT JOIN branches b ON b.id = sp.branch_id
                LEFT JOIN supplier_cheques sc ON sc.supplier_payment_id = sp.id
                LEFT JOIN bank_accounts ba ON ba.id = sp.bank_account_id
                LEFT JOIN supplier_payment_grns spg ON spg.supplier_payment_id = sp.id
                LEFT JOIN grns g ON g.id = spg.grn_id
                WHERE sp.supplier_id = :id {$spBranchSql}
                GROUP BY sp.id
                ORDER BY sp.payment_date ASC, sp.id ASC
            ", $baseParams);

            foreach ($payments as $p) {
                $refDetail = $p['cheque_number'] 
                    ? "Cheque #{$p['cheque_number']}" 
                    : ($p['reference_no'] ? "Ref #{$p['reference_no']}" : '');
                
                $methodStr = ucfirst($p['payment_method']);
                if ($refDetail) {
                    $methodStr .= " ({$refDetail})";
                }
                if ($p['account_bank']) {
                    $methodStr .= " • {$p['account_bank']}";
                }
                if ($p['allocated_grns']) {
                    $methodStr .= " • Settled: {$p['allocated_grns']}";
                }
                if ($p['notes']) {
                    $methodStr .= " - {$p['notes']}";
                }

                $activities[] = [
                    'id'             => (int)$p['id'],
                    'type'           => 'Payment',
                    'title'          => 'Payment',
                    'badge_class'    => 'act-badge-payment',
                    'ref_no'         => $p['ref_no'],
                    'invoice_no'     => null,
                    'invoice_date'   => null,
                    'invoice_amount' => 0.00,
                    'date'           => $p['activity_date'],
                    'date_fmt'       => date('M d, Y', strtotime($p['activity_date'])),
                    'amount'         => (float)$p['amount'],
                    'debit'          => 0.00,
                    'credit'         => (float)$p['amount'],
                    'status'         => 'Completed',
                    'payment_status' => 'paid',
                    'paid_amount'    => (float)$p['amount'],
                    'due_amount'     => 0.00,
                    'details'        => $methodStr,
                    'branch_name'    => $p['branch_name'] ?? null,
                    'action_url'     => null,
                    'created_at'     => $p['created_at']
                ];
            }

            // 4. Supplier Opening Balances (Opening Bal / Migration)
            $obBranchSql = ($branchId !== null && $branchId > 0) ? " AND ob.branch_id = :branch_id" : "";
            $obs = Database::fetchAll("
                SELECT 
                    ob.id, 
                    ob.entry_date AS activity_date, 
                    ob.balance_type, 
                    ob.amount, 
                    ob.paid_amount, 
                    ob.reference_no, 
                    ob.notes, 
                    ob.status, 
                    ob.created_at,
                    b.name AS branch_name,
                    je.entry_number AS journal_number
                FROM supplier_opening_balances ob
                LEFT JOIN branches b ON b.id = ob.branch_id
                LEFT JOIN journal_entries je ON ob.journal_entry_id = je.id
                WHERE ob.supplier_id = :id {$obBranchSql}
                ORDER BY ob.entry_date ASC, ob.id ASC
            ", $baseParams);

            foreach ($obs as $ob) {
                $isPayable = ($ob['balance_type'] === 'owe_supplier');
                $isPosted  = ($ob['status'] === 'posted');
                $obAmt     = (float)$ob['amount'];

                $activities[] = [
                    'id'             => (int)$ob['id'],
                    'type'           => 'Opening Bal',
                    'title'          => 'Opening Balance',
                    'badge_class'    => 'act-badge-opening_balance',
                    'ref_no'         => $ob['journal_number'] ?: ('OB-#' . $ob['id']),
                    'invoice_no'     => $ob['reference_no'] ?? null,
                    'invoice_date'   => null,
                    'invoice_amount' => 0.00,
                    'date'           => $ob['activity_date'],
                    'date_fmt'       => date('M d, Y', strtotime($ob['activity_date'])),
                    'amount'         => $obAmt,
                    'debit'          => ($isPosted && $isPayable) ? $obAmt : 0.00,
                    'credit'         => ($isPosted && !$isPayable) ? $obAmt : 0.00,
                    'status'         => $ob['status'],
                    'payment_status' => null,
                    'paid_amount'    => (float)($ob['paid_amount'] ?? 0.00),
                    'due_amount'     => max(0.00, round($obAmt - (float)($ob['paid_amount'] ?? 0), 2)),
                    'details'        => ($isPayable ? 'We Owe Supplier (Payable)' : 'Supplier Advance / Credit') . ($ob['notes'] ? " - {$ob['notes']}" : ''),
                    'branch_name'    => $ob['branch_name'] ?? null,
                    'action_url'     => null,
                    'created_at'     => $ob['created_at']
                ];
            }

            // 5. Supplier Returns (Credit reduces payable liability)
            $retBranchSql = ($branchId !== null && $branchId > 0) ? " AND r.branch_id = :branch_id" : "";
            $returns = Database::fetchAll("
                SELECT 
                    r.id, 
                    r.return_number AS ref_no, 
                    r.return_date AS activity_date, 
                    r.total_return_amount AS amount, 
                    r.applied_to_payable, 
                    r.credit_amount, 
                    r.notes, 
                    r.created_at,
                    g.grn_number,
                    b.name AS branch_name
                FROM supplier_returns r
                LEFT JOIN branches b ON b.id = r.branch_id
                LEFT JOIN grns g ON g.id = r.grn_id
                WHERE r.supplier_id = :id {$retBranchSql}
                ORDER BY r.return_date ASC, r.id ASC
            ", $baseParams);

            foreach ($returns as $r) {
                $retAmt = (float)$r['amount'];
                $activities[] = [
                    'id'             => (int)$r['id'],
                    'type'           => 'Return',
                    'title'          => 'Supplier Return',
                    'badge_class'    => 'act-badge-return',
                    'ref_no'         => $r['ref_no'],
                    'invoice_no'     => null,
                    'invoice_date'   => null,
                    'invoice_amount' => 0.00,
                    'date'           => $r['activity_date'],
                    'date_fmt'       => date('M d, Y', strtotime($r['activity_date'])),
                    'amount'         => $retAmt,
                    'debit'          => 0.00,
                    'credit'         => $retAmt,
                    'status'         => 'Completed',
                    'payment_status' => null,
                    'paid_amount'    => 0.00,
                    'due_amount'     => 0.00,
                    'details'        => ($r['grn_number'] ? "Return against {$r['grn_number']}" : "Supplier Return") . ($r['notes'] ? " - {$r['notes']}" : ''),
                    'branch_name'    => $r['branch_name'] ?? null,
                    'action_url'     => 'supplier_returns.php',
                    'created_at'     => $r['created_at']
                ];
            }

            // 6. Supplier Credit Settlements
            $csBranchSql = ($branchId !== null && $branchId > 0) ? " AND cs.branch_id = :branch_id" : "";
            $settlements = Database::fetchAll("
                SELECT 
                    cs.id, 
                    cs.settlement_number AS ref_no, 
                    cs.settlement_date AS activity_date, 
                    cs.settlement_type, 
                    cs.amount, 
                    cs.reference_no, 
                    cs.notes, 
                    cs.created_at,
                    c.credit_number, 
                    g.grn_number AS target_grn_number,
                    b.name AS branch_name
                FROM supplier_credit_settlements cs
                LEFT JOIN branches b ON b.id = cs.branch_id
                LEFT JOIN supplier_credits c ON c.id = cs.supplier_credit_id
                LEFT JOIN grns g ON g.id = cs.target_grn_id
                WHERE cs.supplier_id = :id {$csBranchSql}
                ORDER BY cs.settlement_date ASC, cs.id ASC
            ", $baseParams);

            foreach ($settlements as $cs) {
                $csAmt = (float)$cs['amount'];
                $activities[] = [
                    'id'             => (int)$cs['id'],
                    'type'           => 'Settlement',
                    'title'          => 'Credit Settlement',
                    'badge_class'    => 'act-badge-payment',
                    'ref_no'         => $cs['ref_no'],
                    'invoice_no'     => $cs['reference_no'] ?? null,
                    'invoice_date'   => null,
                    'invoice_amount' => 0.00,
                    'date'           => $cs['activity_date'],
                    'date_fmt'       => date('M d, Y', strtotime($cs['activity_date'])),
                    'amount'         => $csAmt,
                    'debit'          => 0.00,
                    'credit'         => 0.00,
                    'status'         => 'Completed',
                    'payment_status' => null,
                    'paid_amount'    => 0.00,
                    'due_amount'     => 0.00,
                    'details'        => ucwords(str_replace('_', ' ', $cs['settlement_type'])) . ($cs['target_grn_number'] ? " (Offset GRN: {$cs['target_grn_number']})" : '') . ($cs['credit_number'] ? " [Credit: {$cs['credit_number']}]" : ''),
                    'branch_name'    => $cs['branch_name'] ?? null,
                    'action_url'     => null,
                    'created_at'     => $cs['created_at']
                ];
            }

            // Step 1: Sort chronologically ASCENDING to calculate running balance accurately
            usort($activities, function ($a, $b) {
                $cmp = strcmp($a['date'], $b['date']);
                if ($cmp === 0) {
                    if ($a['type'] === 'Opening Bal') return -1;
                    if ($b['type'] === 'Opening Bal') return 1;
                    return strcmp($a['created_at'], $b['created_at']);
                }
                return $cmp;
            });

            // Step 2: Calculate cumulative Running Balance
            $runningBalance = 0.00;
            foreach ($activities as &$act) {
                $runningBalance += ($act['debit'] - $act['credit']);
                $act['running_balance'] = round($runningBalance, 2);
            }
            unset($act);

            // Step 3: Sort DESCENDING (newest first) for UI display
            usort($activities, function ($a, $b) {
                $cmp = strcmp($b['date'], $a['date']);
                if ($cmp === 0) {
                    if ($a['type'] === 'Opening Bal') return 1;
                    if ($b['type'] === 'Opening Bal') return -1;
                    return strcmp($b['created_at'], $a['created_at']);
                }
                return $cmp;
            });

        } catch (Throwable $e) {
            Logger::error("Failed to fetch supplier activity ledger for #{$supplierId}: " . $e->getMessage(), $e);
        }

        return $activities;
    }
}
