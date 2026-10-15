<?php
/**
 * Curtiss POS - Customer Core Component (Stage 5)
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Audit.php';

class Customer
{
    /**
     * Get Paginated Customer List with Search & Filters
     */
    public static function all(array $filters = [], int $page = 1, int $limit = 15): array
    {
        try {
            $where = ["deleted_at IS NULL"];
            $params = [];

            if (!empty($filters['search'])) {
                $search = '%' . trim($filters['search']) . '%';
                $where[] = "(name LIKE :s1 OR phone LIKE :s2 OR whatsapp LIKE :s3 OR card_barcode LIKE :s4 OR customer_code LIKE :s5 OR id = :exact_id)";
                $params[':s1'] = $search;
                $params[':s2'] = $search;
                $params[':s3'] = $search;
                $params[':s4'] = $search;
                $params[':s5'] = $search;
                $params[':exact_id'] = is_numeric($filters['search']) ? (int)$filters['search'] : 0;
            }

            if (!empty($filters['status']) && in_array($filters['status'], ['active', 'inactive'])) {
                $where[] = "status = :status";
                $params[':status'] = $filters['status'];
            }

            $whereSql = implode(' AND ', $where);

            $countRow = Database::fetchOne("SELECT COUNT(*) AS total FROM customers WHERE $whereSql", $params);
            $totalItems = (int)($countRow['total'] ?? 0);
            $offset = ($page - 1) * $limit;

            $items = Database::fetchAll("
                SELECT * FROM customers 
                WHERE $whereSql 
                ORDER BY is_walkin DESC, id DESC 
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
            Logger::error("Failed to fetch customer list: " . $e->getMessage(), $e);
            return ['items' => [], 'totalItems' => 0, 'page' => 1, 'limit' => $limit, 'totalPages' => 1];
        }
    }

    /**
     * Find Customer by ID
     */
    public static function find(int $id): ?array
    {
        try {
            return Database::fetchOne("SELECT * FROM customers WHERE id = :id AND deleted_at IS NULL", [':id' => $id]);
        } catch (Throwable $e) {
            Logger::error("Failed to find customer #$id: " . $e->getMessage(), $e);
            return null;
        }
    }

    /**
     * Find Customer by Card Barcode
     */
    public static function findByBarcode(string $barcode): ?array
    {
        $barcode = trim($barcode);
        if (empty($barcode)) return null;

        try {
            return Database::fetchOne("SELECT * FROM customers WHERE card_barcode = :barcode AND deleted_at IS NULL", [':barcode' => $barcode]);
        } catch (Throwable $e) {
            Logger::error("Failed to find customer by barcode '$barcode': " . $e->getMessage(), $e);
            return null;
        }
    }

    /**
     * Quick Search Customers (for POS scanner, card reader, phone/name auto-complete)
     */
    public static function search(string $query, int $limit = 20): array
    {
        $query = trim($query);
        if (empty($query)) {
            return Database::fetchAll("SELECT * FROM customers WHERE deleted_at IS NULL AND status = 'active' ORDER BY is_walkin DESC, name ASC LIMIT $limit");
        }

        try {
            $isNum = is_numeric($query);
            $exactId = $isNum ? (int)$query : 0;
            $searchPattern = '%' . $query . '%';

            return Database::fetchAll("
                SELECT * FROM customers 
                WHERE deleted_at IS NULL 
                  AND status = 'active'
                  AND (
                        card_barcode = :eq1
                     OR phone = :eq2
                     OR whatsapp = :eq3
                     OR customer_code = :eq4
                     OR id = :exact_id
                     OR name LIKE :sp1
                     OR phone LIKE :sp2
                  )
                ORDER BY (id = :exact_id2) DESC, (card_barcode = :eq5) DESC, is_walkin DESC, name ASC
                LIMIT $limit
            ", [
                ':eq1'       => $query,
                ':eq2'       => $query,
                ':eq3'       => $query,
                ':eq4'       => $query,
                ':eq5'       => $query,
                ':exact_id'  => $exactId,
                ':exact_id2' => $exactId,
                ':sp1'       => $searchPattern,
                ':sp2'       => $searchPattern
            ]);
        } catch (Throwable $e) {
            Logger::error("Failed customer search for '$query': " . $e->getMessage(), $e);
            return [];
        }
    }

    /**
     * Get or Ensure Default Walk-in Customer
     */
    public static function getWalkInCustomer(): array
    {
        try {
            $customer = Database::fetchOne("SELECT * FROM customers WHERE is_walkin = 1 AND deleted_at IS NULL LIMIT 1");
            if ($customer) {
                return $customer;
            }

            // Create if missing
            Database::execute("
                INSERT INTO customers (id, customer_code, name, address, is_walkin, status)
                VALUES (1, 'CUST-WALKIN', 'Walk-in Customer', 'Default POS General Customer', 1, 'active')
            ");
            return Database::fetchOne("SELECT * FROM customers WHERE id = 1");
        } catch (Throwable $e) {
            Logger::error("Failed to fetch default Walk-in Customer: " . $e->getMessage(), $e);
            return [
                'id' => 1, 'customer_code' => 'CUST-WALKIN', 'name' => 'Walk-in Customer',
                'phone' => null, 'whatsapp' => null, 'address' => 'Default POS General Customer',
                'card_barcode' => null, 'credit_limit' => 0.00, 'credit_balance' => 0.00,
                'is_walkin' => 1, 'status' => 'active'
            ];
        }
    }

    /**
     * Create Customer Record
     */
    /**
     * Auto-generate unique sequential Customer Code (CUS-00001, CUS-00002...)
     */
    public static function generateCustomerCode(): string
    {
        try {
            $row = Database::fetchOne("SELECT MAX(id) AS max_id FROM customers");
            $nextId = ((int)($row['max_id'] ?? 0)) + 1;
            return 'CUS-' . sprintf('%05d', $nextId);
        } catch (Throwable $e) {
            Logger::error("Failed to generate customer code: " . $e->getMessage(), $e);
            return 'CUS-' . sprintf('%05d', rand(1000, 99999));
        }
    }

    /**
     * Create Customer Record
     */
    public static function create(array $data): array
    {
        $name = trim($data['name'] ?? '');
        $phone = trim($data['phone'] ?? '') ?: null;
        $whatsapp = trim($data['whatsapp'] ?? '') ?: null;
        $address = trim($data['address'] ?? '') ?: null;
        $cardBarcode = trim($data['card_barcode'] ?? '') ?: null;
        $creditLimit = (float)($data['credit_limit'] ?? 0.00);
        $status = ($data['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
        $customerCode = !empty($data['customer_code']) ? trim($data['customer_code']) : self::generateCustomerCode();
        $isLoyalty = !empty($data['is_loyalty']) ? 1 : 0;
        $loyaltyDiscountOverride = (isset($data['loyalty_discount_override']) && $data['loyalty_discount_override'] !== '') ? (float)$data['loyalty_discount_override'] : null;

        if (empty($name)) {
            return ['success' => false, 'message' => 'Customer name is required.'];
        }

        if ($creditLimit < 0) {
            return ['success' => false, 'message' => 'Credit limit cannot be negative.'];
        }

        // Validate unique customer code
        if (!empty($customerCode)) {
            $existingCode = Database::fetchOne("SELECT id FROM customers WHERE customer_code = :code AND deleted_at IS NULL", [':code' => $customerCode]);
            if ($existingCode) {
                return ['success' => false, 'message' => "Customer code '$customerCode' is already assigned to another customer."];
            }
        }

        // Validate unique card barcode if provided
        if ($cardBarcode !== null) {
            $existingBarcode = Database::fetchOne("SELECT id FROM customers WHERE card_barcode = :barcode AND deleted_at IS NULL", [':barcode' => $cardBarcode]);
            if ($existingBarcode) {
                return ['success' => false, 'message' => "Customer card barcode '$cardBarcode' is already in use by another customer."];
            }
        }

        try {
            Database::execute("
                INSERT INTO customers (customer_code, name, phone, whatsapp, address, card_barcode, credit_limit, credit_balance, is_walkin, status, is_loyalty, loyalty_discount_override)
                VALUES (:code, :name, :phone, :whatsapp, :address, :card_barcode, :credit_limit, 0.00, 0, :status, :is_loyalty, :loyalty_discount_override)
            ", [
                ':code'                      => $customerCode,
                ':name'                      => $name,
                ':phone'                     => $phone,
                ':whatsapp'                  => $whatsapp,
                ':address'                   => $address,
                ':card_barcode'              => $cardBarcode,
                ':credit_limit'              => $creditLimit,
                ':status'                    => $status,
                ':is_loyalty'                => $isLoyalty,
                ':loyalty_discount_override' => $loyaltyDiscountOverride
            ]);

            $id = Database::lastInsertId();
            Audit::log('CUSTOMER_CREATED', 'customers', (string)$id, null, [
                'customer_code' => $customerCode, 'name' => $name, 'phone' => $phone, 'card_barcode' => $cardBarcode, 'credit_limit' => $creditLimit, 'is_loyalty' => $isLoyalty, 'loyalty_discount_override' => $loyaltyDiscountOverride
            ]);

            return ['success' => true, 'id' => $id, 'customer_code' => $customerCode, 'message' => "Customer '$name' created successfully."];
        } catch (Throwable $e) {
            Logger::error("Failed to create customer: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Database error while creating customer: ' . $e->getMessage()];
        }
    }

    /**
     * Update Customer Record
     */
    public static function update(int $id, array $data): array
    {
        $customer = self::find($id);
        if (!$customer) {
            return ['success' => false, 'message' => 'Customer not found.'];
        }

        if (!empty($customer['is_walkin']) || $id === 1) {
            if (isset($data['status']) && $data['status'] === 'inactive') {
                return ['success' => false, 'message' => 'Walk-in Customer cannot be deactivated.'];
            }
        }

        $name = trim($data['name'] ?? $customer['name']);
        $phone = isset($data['phone']) ? (trim($data['phone']) ?: null) : $customer['phone'];
        $whatsapp = isset($data['whatsapp']) ? (trim($data['whatsapp']) ?: null) : $customer['whatsapp'];
        $address = isset($data['address']) ? (trim($data['address']) ?: null) : $customer['address'];
        $cardBarcode = isset($data['card_barcode']) ? (trim($data['card_barcode']) ?: null) : $customer['card_barcode'];
        $creditLimit = isset($data['credit_limit']) ? (float)$data['credit_limit'] : (float)$customer['credit_limit'];
        $status = ($data['status'] ?? $customer['status']) === 'inactive' ? 'inactive' : 'active';
        $customerCode = !empty($data['customer_code']) ? trim($data['customer_code']) : $customer['customer_code'];
        $isLoyalty = isset($data['is_loyalty']) ? (!empty($data['is_loyalty']) ? 1 : 0) : (int)($customer['is_loyalty'] ?? 0);
        $loyaltyDiscountOverride = array_key_exists('loyalty_discount_override', $data) ? ($data['loyalty_discount_override'] !== '' && $data['loyalty_discount_override'] !== null ? (float)$data['loyalty_discount_override'] : null) : ($customer['loyalty_discount_override'] ?? null);

        if (empty($name)) {
            return ['success' => false, 'message' => 'Customer name is required.'];
        }

        if ($creditLimit < 0) {
            return ['success' => false, 'message' => 'Credit limit cannot be negative.'];
        }

        // Validate unique customer code if modified
        if ($customerCode !== $customer['customer_code']) {
            $existingCode = Database::fetchOne("
                SELECT id FROM customers WHERE customer_code = :code AND id != :id AND deleted_at IS NULL
            ", [':code' => $customerCode, ':id' => $id]);
            if ($existingCode) {
                return ['success' => false, 'message' => "Customer code '$customerCode' is already assigned to another customer."];
            }
        }

        // Validate unique card barcode if provided
        if ($cardBarcode !== null) {
            $existingBarcode = Database::fetchOne("
                SELECT id FROM customers 
                WHERE card_barcode = :barcode AND id != :id AND deleted_at IS NULL
            ", [':barcode' => $cardBarcode, ':id' => $id]);

            if ($existingBarcode) {
                return ['success' => false, 'message' => "Customer card barcode '$cardBarcode' is already assigned to another customer."];
            }
        }

        try {
            Database::execute("
                UPDATE customers 
                SET customer_code = :code, name = :name, phone = :phone, whatsapp = :whatsapp, address = :address, 
                    card_barcode = :card_barcode, credit_limit = :credit_limit, status = :status,
                    is_loyalty = :is_loyalty, loyalty_discount_override = :loyalty_discount_override,
                    updated_at = NOW()
                WHERE id = :id
            ", [
                ':code'                      => $customerCode,
                ':name'                      => $name,
                ':phone'                     => $phone,
                ':whatsapp'                  => $whatsapp,
                ':address'                   => $address,
                ':card_barcode'              => $cardBarcode,
                ':credit_limit'              => $creditLimit,
                ':status'                    => $status,
                ':is_loyalty'                => $isLoyalty,
                ':loyalty_discount_override' => $loyaltyDiscountOverride,
                ':id'                        => $id
            ]);

            Audit::log('CUSTOMER_UPDATED', 'customers', (string)$id, $customer, [
                'customer_code' => $customerCode, 'name' => $name, 'phone' => $phone, 'card_barcode' => $cardBarcode, 'credit_limit' => $creditLimit, 'status' => $status, 'is_loyalty' => $isLoyalty, 'loyalty_discount_override' => $loyaltyDiscountOverride
            ]);

            return ['success' => true, 'message' => "Customer '$name' updated successfully."];
        } catch (Throwable $e) {
            Logger::error("Failed to update customer #$id: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Database error while updating customer.'];
        }
    }

    /**
     * Safe Delete Customer Record (Hard Delete if 0 history, otherwise blocked)
     */
    public static function delete(int $id): array
    {
        $customer = self::find($id);
        if (!$customer) {
            return ['success' => false, 'message' => 'Customer not found.'];
        }

        if (!empty($customer['is_walkin']) || $id === 1 || $customer['customer_code'] === 'CUST-WALKIN') {
            return ['success' => false, 'message' => 'Walk-in Customer cannot be deleted.'];
        }

        // Check transaction history across subsystems
        $salesCount = (int)(Database::fetchOne("SELECT COUNT(*) AS total FROM sales WHERE customer_id = :id", [':id' => $id])['total'] ?? 0);
        $creditCount = (int)(Database::fetchOne("SELECT COUNT(*) AS total FROM customer_credit_ledger WHERE customer_id = :id", [':id' => $id])['total'] ?? 0);
        $payCount = (int)(Database::fetchOne("SELECT COUNT(*) AS total FROM payments WHERE customer_id = :id", [':id' => $id])['total'] ?? 0);
        $returnCount = (int)(Database::fetchOne("SELECT COUNT(*) AS total FROM sales_returns WHERE customer_id = :id", [':id' => $id])['total'] ?? 0);

        $totalHistory = $salesCount + $creditCount + $payCount + $returnCount;
        if ($totalHistory > 0) {
            return [
                'success' => false,
                'message' => "Cannot delete customer '{$customer['name']}' because transaction history exists ({$salesCount} Sales, {$creditCount} Credit Entries, {$payCount} Payments, {$returnCount} Returns). You may deactivate this customer instead."
            ];
        }

        try {
            Database::execute("UPDATE customers SET deleted_at = NOW(), status = 'inactive' WHERE id = :id", [':id' => $id]);
            Audit::log('CUSTOMER_DELETED', 'customers', (string)$id, $customer, null);
            return ['success' => true, 'message' => "Customer '{$customer['name']}' deleted successfully."];
        } catch (Throwable $e) {
            Logger::error("Failed to delete customer #{$id}: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Database error while deleting customer: ' . $e->getMessage()];
        }
    }

    /**
     * Soft Deactivate / Reactivate Customer
     */
    public static function toggleStatus(int $id): array
    {
        $customer = self::find($id);
        if (!$customer) {
            return ['success' => false, 'message' => 'Customer not found.'];
        }

        if (!empty($customer['is_walkin']) || $id === 1) {
            return ['success' => false, 'message' => 'Walk-in Customer cannot be deactivated.'];
        }

        $newStatus = ($customer['status'] === 'active') ? 'inactive' : 'active';
        try {
            Database::execute("UPDATE customers SET status = :status, updated_at = NOW() WHERE id = :id", [
                ':status' => $newStatus,
                ':id'     => $id
            ]);

            Audit::log('CUSTOMER_STATUS_TOGGLED', 'customers', (string)$id, ['status' => $customer['status']], ['status' => $newStatus]);
            return ['success' => true, 'message' => "Customer status updated to '$newStatus'."];
        } catch (Throwable $e) {
            Logger::error("Failed to toggle customer status #$id: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Database error while updating customer status.'];
        }
    }

    /**
     * Customer Credit Foundation Summary & Ledger History
     */
    public static function getCreditHistory(int $customerId): array
    {
        $customer = self::find($customerId);
        if (!$customer) {
            return ['credit_limit' => 0.00, 'credit_balance' => 0.00, 'available_credit' => 0.00, 'ledger' => []];
        }

        try {
            $ledger = Database::fetchAll("
                SELECT ccl.*, b.name AS branch_name, u.username 
                FROM customer_credit_ledger ccl
                LEFT JOIN branches b ON ccl.branch_id = b.id
                LEFT JOIN users u ON ccl.user_id = u.id
                WHERE ccl.customer_id = :customer_id
                ORDER BY ccl.id DESC
            ", [':customer_id' => $customerId]);

            $limit = (float)$customer['credit_limit'];
            $balance = (float)$customer['credit_balance'];
            $available = max(0.00, $limit - $balance);

            return [
                'credit_limit'     => $limit,
                'credit_balance'   => $balance,
                'available_credit' => $available,
                'ledger'           => $ledger
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to fetch customer credit history for #$customerId: " . $e->getMessage(), $e);
            return ['credit_limit' => 0.00, 'credit_balance' => 0.00, 'available_credit' => 0.00, 'ledger' => []];
        }
    }

    /**
     * Customer Real Purchase History & Invoices List
     */
    public static function getPurchaseHistory(int $customerId): array
    {
        $customer = self::find($customerId);
        if (!$customer) {
            return ['total_invoices' => 0, 'total_spent' => 0.00, 'invoices' => []];
        }

        try {
            $invoices = Database::fetchAll("
                SELECT 
                    s.id,
                    s.invoice_no,
                    s.created_at AS date,
                    s.total_amount AS amount,
                    s.status,
                    b.name AS branch_name,
                    (SELECT COUNT(*) FROM sale_items si WHERE si.sale_id = s.id) AS total_items
                FROM sales s
                JOIN branches b ON s.branch_id = b.id
                WHERE s.customer_id = :customer_id AND s.status = 'completed'
                ORDER BY s.id DESC
            ", [':customer_id' => $customerId]);

            $totalSpent = 0.00;
            foreach ($invoices as $inv) {
                $totalSpent += (float)$inv['amount'];
            }

            return [
                'total_invoices' => count($invoices),
                'total_spent'    => round($totalSpent, 2),
                'invoices'       => $invoices
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to fetch customer purchase history for #{$customerId}: " . $e->getMessage(), $e);
            return ['total_invoices' => 0, 'total_spent' => 0.00, 'invoices' => []];
        }
    }

    /**
     * Get Consolidated Customer Profile
     */
    public static function getProfile(int $customerId): ?array
    {
        $customer = self::find($customerId);
        if (!$customer) {
            return null;
        }

        return [
            'master'             => $customer,
            'current_outstanding'=> (float)$customer['credit_balance'],
            'credit_limit'      => (float)$customer['credit_limit'],
            'available_credit'  => max(0.00, (float)$customer['credit_limit'] - (float)$customer['credit_balance']),
            'purchase_history'  => self::getPurchaseHistory($customerId),
            'credit_history'    => self::getCreditHistory($customerId),
            'activity_timeline' => self::getActivityTimeline($customerId)
        ];
    }

    /**
     * Consolidated Customer Activity History Timeline
     */
    public static function getActivityTimeline(int $customerId): array
    {
        $timeline = [];
        try {
            // 1. Invoices / Credit Sales
            $sales = Database::fetchAll("
                SELECT s.id, s.invoice_no AS ref_no, 'Invoice' AS event_type, s.total_amount AS amount, s.created_at, b.name AS branch_name
                FROM sales s
                LEFT JOIN branches b ON s.branch_id = b.id
                WHERE s.customer_id = :cid AND s.status = 'completed'
            ", [':cid' => $customerId]);
            foreach ($sales as $s) {
                $timeline[] = [
                    'date'          => $s['created_at'],
                    'event_type'    => 'Invoice',
                    'reference_no'  => $s['ref_no'],
                    'amount'        => (float)$s['amount'],
                    'balance_after' => null,
                    'notes'         => "Completed Sales Invoice #{$s['ref_no']}",
                    'branch_name'   => $s['branch_name'] ?? 'Main'
                ];
            }

            // 2. Customer Credit Ledger Movements
            $ledger = Database::fetchAll("
                SELECT ccl.*, b.name AS branch_name
                FROM customer_credit_ledger ccl
                LEFT JOIN branches b ON ccl.branch_id = b.id
                WHERE ccl.customer_id = :cid
            ", [':cid' => $customerId]);
            foreach ($ledger as $l) {
                $eventType = 'Credit Adjustment';
                if ($l['transaction_type'] === 'credit_sale') $eventType = 'Credit Sale';
                elseif ($l['transaction_type'] === 'payment') $eventType = 'Customer Payment';
                elseif ($l['transaction_type'] === 'opening_balance') $eventType = 'Opening Balance';

                $timeline[] = [
                    'date'          => $l['created_at'],
                    'event_type'    => $eventType,
                    'reference_no'  => $l['reference_no'] ?? ('CCL-' . $l['id']),
                    'amount'        => (float)$l['amount_paid'] > 0 ? (float)$l['amount_paid'] : (float)$l['invoice_total'],
                    'balance_after' => (float)$l['balance_after'],
                    'notes'         => $l['notes'] ?? '',
                    'branch_name'   => $l['branch_name'] ?? 'Main'
                ];
            }

            // 3. Sales Returns
            $returns = Database::fetchAll("
                SELECT sr.id, sr.return_no AS ref_no, sr.total_return_amount AS amount, sr.created_at, b.name AS branch_name, sr.notes
                FROM sales_returns sr
                LEFT JOIN branches b ON sr.branch_id = b.id
                WHERE sr.customer_id = :cid AND (sr.status IS NULL OR sr.status = 'completed')
            ", [':cid' => $customerId]);
            foreach ($returns as $r) {
                $timeline[] = [
                    'date'          => $r['created_at'],
                    'event_type'    => 'Sales Return',
                    'reference_no'  => $r['ref_no'],
                    'amount'        => (float)$r['amount'],
                    'balance_after' => null,
                    'notes'         => $r['notes'] ?: "Sales Return #{$r['ref_no']}",
                    'branch_name'   => $r['branch_name'] ?? 'Main'
                ];
            }

            // Sort timeline chronologically descending
            usort($timeline, function($a, $b) {
                return strcmp($b['date'], $a['date']);
            });

            return $timeline;
        } catch (Throwable $e) {
            Logger::error("Failed to compile activity timeline for customer #{$customerId}: " . $e->getMessage(), $e);
            return [];
        }
    }

    /**
     * Reconcile Customer Outstanding vs Credit Ledger vs GL AR Account 1040
     */
    public static function reconcileBalances(): array
    {
        try {
            $custRow = Database::fetchOne("SELECT COALESCE(SUM(credit_balance), 0.00) AS total_bal FROM customers WHERE deleted_at IS NULL");
            $custSum = round((float)($custRow['total_bal'] ?? 0.00), 2);

            $ledgerRow = Database::fetchOne("
                SELECT COALESCE(SUM(ccl.amount_due), 0.00) AS net_ledger 
                FROM customer_credit_ledger ccl
                JOIN customers c ON ccl.customer_id = c.id
                WHERE c.deleted_at IS NULL
            ");
            $ledgerSum = round((float)($ledgerRow['net_ledger'] ?? 0.00), 2);

            require_once __DIR__ . '/Accounting.php';
            $glArRow = Database::fetchOne("
                SELECT COALESCE(SUM(jl.debit - jl.credit), 0.00) AS net_ar
                FROM journal_lines jl
                JOIN journal_entries je ON jl.journal_entry_id = je.id
                JOIN chart_of_accounts coa ON jl.account_id = coa.id
                WHERE je.posting_status = 'posted' AND coa.account_code = '1040'
            ");
            $glArSum = round((float)($glArRow['net_ar'] ?? 0.00), 2);

            $ledgerVariance = round(abs($custSum - $ledgerSum), 2);
            $status = ($ledgerVariance < 1.00) ? 'PASS' : 'WARN';

            return [
                'status'               => $status,
                'customer_credit_sum'  => $custSum,
                'credit_ledger_sum'    => $ledgerSum,
                'gl_ar_balance'        => $glArSum,
                'ledger_variance'      => $ledgerVariance
            ];
        } catch (Throwable $e) {
            Logger::error("Failed customer AR balance reconciliation: " . $e->getMessage(), $e);
            return ['status' => 'ERROR', 'message' => $e->getMessage()];
        }
    }

    /**
     * Get Overall Summary Statistics for Customer Center Dashboard
     */
    public static function getSummaryStats(): array
    {
        try {
            $row = Database::fetchOne("
                SELECT 
                    COUNT(*) AS total_customers,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_customers,
                    SUM(CASE WHEN card_barcode IS NOT NULL AND card_barcode != '' THEN 1 ELSE 0 END) AS card_holders,
                    COALESCE(SUM(credit_balance), 0.00) AS total_outstanding,
                    COALESCE(SUM(credit_limit), 0.00) AS total_credit_limit
                FROM customers
                WHERE deleted_at IS NULL
            ");
            return $row ?: [
                'total_customers' => 0,
                'active_customers' => 0,
                'card_holders' => 0,
                'total_outstanding' => 0.00,
                'total_credit_limit' => 0.00
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to fetch customer summary stats: " . $e->getMessage(), $e);
            return [
                'total_customers' => 0,
                'active_customers' => 0,
                'card_holders' => 0,
                'total_outstanding' => 0.00,
                'total_credit_limit' => 0.00
            ];
        }
    }
}
