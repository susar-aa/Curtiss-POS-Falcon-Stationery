<?php
/**
 * Curtiss POS - Central Reporting Engine (Stage 16 & 17)
 * Single source of truth for all Sales, Purchasing, Inventory, Customer,
 * Supplier, Cash & Banking, Accounting GL Financial Statements (P&L, Balance Sheet, Trial Balance),
 * and KPI Performance dashboards with server-side Special User presentation masking.
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Accounting.php';
require_once __DIR__ . '/Banking.php';
require_once __DIR__ . '/MaskingHelper.php';

class ReportsEngine
{
    /**
     * Build WHERE SQL clause and parameters from common filter inputs
     */
    public static function buildFilterWhere(array $filters, string $tableAlias = ''): array
    {
        $prefix = !empty($tableAlias) ? $tableAlias . '.' : '';
        $where = [];
        $params = [];

        // Branch filter
        if (isset($filters['branch_id']) && $filters['branch_id'] !== '' && $filters['branch_id'] !== 'all') {
            $where[] = "{$prefix}branch_id = :branch_id";
            $params[':branch_id'] = (int)$filters['branch_id'];
        }

        // Date range filter
        if (!empty($filters['date_from'])) {
            $dateCol = !empty($filters['date_column']) ? $filters['date_column'] : "{$prefix}created_at";
            $where[] = "{$dateCol} >= :date_from";
            $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $dateCol = !empty($filters['date_column']) ? $filters['date_column'] : "{$prefix}created_at";
            $where[] = "{$dateCol} <= :date_to";
            $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        return ['where' => $where, 'params' => $params];
    }

    // =========================================================================
    // 1. SALES REPORTS
    // =========================================================================

    public static function getSalesSummary(array $filters): array
    {
        try {
            $where = ["s.status = 'completed'"];
            $params = [];

            if (isset($filters['branch_id']) && $filters['branch_id'] !== '' && $filters['branch_id'] !== 'all') {
                $where[] = "s.branch_id = :branch_id";
                $params[':branch_id'] = (int)$filters['branch_id'];
            }

            if (!empty($filters['date_from'])) {
                $where[] = "s.created_at >= :date_from";
                $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
            }

            if (!empty($filters['date_to'])) {
                $where[] = "s.created_at <= :date_to";
                $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
            }

            $whereSql = implode(' AND ', $where);

            $salesRow = Database::fetchOne("
                SELECT 
                    COUNT(s.id) AS invoice_count,
                    COALESCE(SUM(s.subtotal), 0.00) AS gross_sales,
                    COALESCE(SUM(s.discount_amount), 0.00) AS total_discounts,
                    COALESCE(SUM(s.total_amount), 0.00) AS net_sales
                FROM sales s
                WHERE $whereSql
            ", $params);

            // Calculate cost of goods sold based ONLY on exact sale_items.unit_cost / sold lot cost
            $cogsRow = Database::fetchOne("
                SELECT 
                    COALESCE(SUM(si.quantity * si.unit_cost), 0.00) AS total_cogs,
                    COALESCE(SUM(si.quantity), 0.00) AS total_units_sold
                FROM sale_items si
                JOIN sales s ON si.sale_id = s.id
                WHERE $whereSql
            ", $params);

            $invCount          = (int)($salesRow['invoice_count'] ?? 0);
            $rawGross          = round((float)($salesRow['gross_sales'] ?? 0), 2);
            $discounts         = round((float)($salesRow['total_discounts'] ?? 0), 2);
            $rawNet            = round((float)($salesRow['net_sales'] ?? 0), 2);
            $cogs              = round((float)($cogsRow['total_cogs'] ?? 0), 2);
            $unitsSold         = round((float)($cogsRow['total_units_sold'] ?? 0), 2);

            $grossSales   = $rawGross;
            $netSales     = $rawNet;

            $grossProfit  = round($netSales - $cogs, 2);
            $marginPct    = $netSales > 0 ? round(($grossProfit / $netSales) * 100, 2) : 0.00;
            $avgInvoice   = $invCount > 0 ? round($netSales / $invCount, 2) : 0.00;

            $isSpecial = !empty($filters['special_user']) || MaskingHelper::isSpecialUser();
            if ($isSpecial) {
                $grossSales   = MaskingHelper::maskValue($grossSales, true);
                $discounts    = MaskingHelper::maskValue($discounts, true);
                $netSales     = MaskingHelper::maskValue($netSales, true);
                $cogs         = MaskingHelper::maskValue($cogs, true);
                $unitsSold    = MaskingHelper::maskQuantity($unitsSold, true);
                $grossProfit  = MaskingHelper::maskValue($grossProfit, true);
                $avgInvoice   = MaskingHelper::maskValue($avgInvoice, true);
            }

            return [
                'invoice_count'      => $invCount,
                'gross_sales'        => $grossSales,
                'total_discounts'    => $discounts,
                'net_sales'          => $netSales,
                'cogs'               => $cogs,
                'units_sold'         => $unitsSold,
                'gross_profit'       => $grossProfit,
                'gross_margin_pct'   => $marginPct,
                'profit_margin'      => $marginPct,
                'avg_invoice_value'  => $avgInvoice,
                'total_collected'    => $netSales
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to fetch sales summary report: " . $e->getMessage(), $e);
            return [];
        }
    }

    public static function getSalesByProduct(array $filters): array
    {
        try {
            $where = [
                "s.status = 'completed'", 
                "p.deleted_at IS NULL"
            ];
            $params = [];

            if (isset($filters['branch_id']) && $filters['branch_id'] !== '' && $filters['branch_id'] !== 'all') {
                $where[] = "s.branch_id = :branch_id";
                $params[':branch_id'] = (int)$filters['branch_id'];
            }

            if (!empty($filters['date_from'])) {
                $where[] = "s.created_at >= :date_from";
                $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
            }

            if (!empty($filters['date_to'])) {
                $where[] = "s.created_at <= :date_to";
                $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
            }

            if (!empty($filters['category_id'])) {
                $where[] = "p.category_id = :category_id";
                $params[':category_id'] = (int)$filters['category_id'];
            }

            if (!empty($filters['brand_id'])) {
                $where[] = "p.brand_id = :brand_id";
                $params[':brand_id'] = (int)$filters['brand_id'];
            }

            $whereSql = implode(' AND ', $where);

            $items = Database::fetchAll("
                SELECT 
                    p.id AS product_id,
                    p.name AS product_name,
                    COALESCE(p.sku, p.barcode) AS sku,
                    p.barcode,
                    COALESCE(cat.name, 'Uncategorized') AS category_name,
                    COALESCE(brd.name, 'No Brand') AS brand_name,
                    SUM(si.quantity) AS total_units,
                    SUM(si.line_total) AS total_sales,
                    SUM(si.line_cost) AS total_cost
                FROM sale_items si
                JOIN sales s ON si.sale_id = s.id
                JOIN products p ON si.product_id = p.id
                LEFT JOIN categories cat ON p.category_id = cat.id
                LEFT JOIN brands brd ON p.brand_id = brd.id
                WHERE $whereSql
                GROUP BY p.id
                ORDER BY total_sales DESC
            ", $params);

            foreach ($items as &$it) {
                $rev  = (float)$it['total_sales'];
                $cost = (float)$it['total_cost'];
                $prof = round($rev - $cost, 2);
                $units= (float)$it['total_units'];

                $isSpecial = !empty($filters['special_user']) || MaskingHelper::isSpecialUser();
                if ($isSpecial) {
                    $rev   = MaskingHelper::maskValue($rev, true);
                    $cost  = MaskingHelper::maskValue($cost, true);
                    $prof  = MaskingHelper::maskValue($prof, true);
                    $units = MaskingHelper::maskQuantity($units, true);
                }

                $it['total_units']   = $units;
                $it['total_sales']   = $rev;
                $it['total_cost']    = $cost;
                $it['gross_profit']  = $prof;
                $it['profit_margin'] = $rev > 0 ? round(($prof / $rev) * 100, 2) : 0.00;
            }

            return $items;
        } catch (Throwable $e) {
            Logger::error("Failed to fetch sales by product report: " . $e->getMessage(), $e);
            return [];
        }
    }

    public static function getSalesByCategory(array $filters): array
    {
        try {
            $where = [
                "s.status = 'completed'", 
                "p.deleted_at IS NULL"
            ];
            $params = [];

            if (isset($filters['branch_id']) && $filters['branch_id'] !== '' && $filters['branch_id'] !== 'all') {
                $where[] = "s.branch_id = :branch_id";
                $params[':branch_id'] = (int)$filters['branch_id'];
            }

            if (!empty($filters['date_from'])) {
                $where[] = "s.created_at >= :date_from";
                $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
            }

            if (!empty($filters['date_to'])) {
                $where[] = "s.created_at <= :date_to";
                $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
            }

            $whereSql = implode(' AND ', $where);

            $items = Database::fetchAll("
                SELECT 
                    COALESCE(cat.id, 0) AS category_id,
                    COALESCE(cat.name, 'Uncategorized') AS category_name,
                    SUM(si.quantity) AS total_units,
                    SUM(si.line_total) AS total_sales,
                    SUM(si.line_cost) AS total_cost
                FROM sale_items si
                JOIN sales s ON si.sale_id = s.id
                JOIN products p ON si.product_id = p.id
                LEFT JOIN categories cat ON p.category_id = cat.id
                WHERE $whereSql
                GROUP BY cat.id
                ORDER BY total_sales DESC
            ", $params);

            foreach ($items as &$it) {
                $rev  = (float)$it['total_sales'];
                $cost = (float)$it['total_cost'];
                $prof = round($rev - $cost, 2);
                $units= (float)$it['total_units'];

                $isSpecial = !empty($filters['special_user']) || MaskingHelper::isSpecialUser();
                if ($isSpecial) {
                    $rev   = MaskingHelper::maskValue($rev, true);
                    $cost  = MaskingHelper::maskValue($cost, true);
                    $prof  = MaskingHelper::maskValue($prof, true);
                    $units = MaskingHelper::maskQuantity($units, true);
                }

                $it['total_units']   = $units;
                $it['total_sales']   = $rev;
                $it['total_cost']    = $cost;
                $it['gross_profit']  = $prof;
                $it['profit_margin'] = $rev > 0 ? round(($prof / $rev) * 100, 2) : 0.00;
            }

            return $items;
        } catch (Throwable $e) {
            Logger::error("Failed to fetch sales by category report: " . $e->getMessage(), $e);
            return [];
        }
    }

    public static function getSalesByPaymentMethod(array $filters): array
    {
        try {
            $where = ["s.status = 'completed'"];
            $params = [];

            if (isset($filters['branch_id']) && $filters['branch_id'] !== '' && $filters['branch_id'] !== 'all') {
                $where[] = "s.branch_id = :branch_id";
                $params[':branch_id'] = (int)$filters['branch_id'];
            }

            if (!empty($filters['date_from'])) {
                $where[] = "s.created_at >= :date_from";
                $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
            }

            if (!empty($filters['date_to'])) {
                $where[] = "s.created_at <= :date_to";
                $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
            }

            $whereSql = implode(' AND ', $where);

            $rows = Database::fetchAll("
                SELECT 
                    p.payment_method,
                    COUNT(p.id) AS transaction_count,
                    SUM(p.amount) AS total_amount
                FROM payments p
                JOIN sales s ON p.sale_id = s.id
                WHERE $whereSql
                GROUP BY p.payment_method
                ORDER BY total_amount DESC
            ", $params);

            foreach ($rows as &$r) {
                $amt = (float)$r['total_amount'];
                if (MaskingHelper::isSpecialUser()) {
                    $amt = MaskingHelper::maskValue($amt);
                }
                $r['total_amount'] = $amt;
            }

            return $rows;
        } catch (Throwable $e) {
            Logger::error("Failed to fetch sales by payment method report: " . $e->getMessage(), $e);
            return [];
        }
    }

    public static function getSalesHistoryReport(array $filters): array
    {
        try {
            $where = ["s.status IN ('completed', 'refunded', 'cancelled')"];
            $params = [];

            if (isset($filters['branch_id']) && $filters['branch_id'] !== '' && $filters['branch_id'] !== 'all') {
                $where[] = "s.branch_id = :branch_id";
                $params[':branch_id'] = (int)$filters['branch_id'];
            }

            if (!empty($filters['date_from'])) {
                $where[] = "s.created_at >= :date_from";
                $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
            }

            if (!empty($filters['date_to'])) {
                $where[] = "s.created_at <= :date_to";
                $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
            }

            $whereSql = implode(' AND ', $where);

            $sales = Database::fetchAll("
                SELECT 
                    s.id AS sale_id,
                    s.invoice_no,
                    s.created_at AS sale_date,
                    b.name AS branch_name,
                    COALESCE(c.name, 'Walk-in Customer') AS customer_name,
                    COALESCE(NULLIF(u.full_name, ''), u.username, 'System') AS cashier_name,
                    s.subtotal,
                    s.discount_amount,
                    s.total_amount,
                    s.status,
                    (SELECT GROUP_CONCAT(DISTINCT p.payment_method SEPARATOR ', ') FROM payments p WHERE p.sale_id = s.id) AS payment_method
                FROM sales s
                JOIN branches b ON s.branch_id = b.id
                LEFT JOIN customers c ON s.customer_id = c.id
                LEFT JOIN users u ON s.user_id = u.id
                WHERE $whereSql
                ORDER BY s.created_at DESC, s.id DESC
                LIMIT 500
            ", $params);

            $isSpecial = !empty($filters['special_user']) || MaskingHelper::isSpecialUser();

            foreach ($sales as &$s) {
                $sub  = (float)$s['subtotal'];
                $disc = (float)$s['discount_amount'];
                $tot  = (float)$s['total_amount'];

                if ($isSpecial) {
                    $sub  = MaskingHelper::maskValue($sub, true);
                    $disc = MaskingHelper::maskValue($disc, true);
                    $tot  = MaskingHelper::maskValue($tot, true);
                }

                $s['subtotal']        = round($sub, 2);
                $s['discount_amount'] = round($disc, 2);
                $s['total_amount']    = round($tot, 2);
                $s['payment_method']  = strtoupper($s['payment_method'] ?: 'CASH');
                $s['invoice_type']    = 'MERCHANDISE';
            }

            return $sales;
        } catch (Throwable $e) {
            Logger::error("Failed to fetch sales history report: " . $e->getMessage(), $e);
            return [];
        }
    }

    // =========================================================================
    // 2. PURCHASING & SUPPLIER REPORTS
    // =========================================================================

    public static function getPurchaseSummary(array $filters): array
    {
        try {
            $where = ["g.status = 'Approved'"];
            $params = [];

            if (isset($filters['branch_id']) && $filters['branch_id'] !== '' && $filters['branch_id'] !== 'all') {
                $where[] = "g.branch_id = :branch_id";
                $params[':branch_id'] = (int)$filters['branch_id'];
            }

            if (!empty($filters['date_from'])) {
                $where[] = "g.received_date >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }

            if (!empty($filters['date_to'])) {
                $where[] = "g.received_date <= :date_to";
                $params[':date_to'] = $filters['date_to'];
            }

            if (!empty($filters['supplier_id'])) {
                $where[] = "g.supplier_id = :supplier_id";
                $params[':supplier_id'] = (int)$filters['supplier_id'];
            }

            $whereSql = implode(' AND ', $where);

            $grnRow = Database::fetchOne("
                SELECT 
                    COUNT(g.id) AS grn_count,
                    COALESCE(SUM(g.actual_grn_total), 0.00) AS total_grn_amount,
                    COALESCE(SUM(g.paid_amount), 0.00) AS total_settled_amount
                FROM grns g
                WHERE $whereSql
            ", $params);

            $sretWhere = [];
            $sretParams = [];
            if (isset($filters['branch_id']) && $filters['branch_id'] !== '' && $filters['branch_id'] !== 'all') {
                $sretWhere[] = "sr.branch_id = :sr_branch_id";
                $sretParams[':sr_branch_id'] = (int)$filters['branch_id'];
            }
            if (!empty($filters['date_from'])) {
                $sretWhere[] = "sr.return_date >= :sr_date_from";
                $sretParams[':sr_date_from'] = $filters['date_from'];
            }
            if (!empty($filters['date_to'])) {
                $sretWhere[] = "sr.return_date <= :sr_date_to";
                $sretParams[':sr_date_to'] = $filters['date_to'];
            }
            if (!empty($filters['supplier_id'])) {
                $sretWhere[] = "sr.supplier_id = :sr_supplier_id";
                $sretParams[':sr_supplier_id'] = (int)$filters['supplier_id'];
            }
            $sretWhereSql = !empty($sretWhere) ? "WHERE " . implode(' AND ', $sretWhere) : "";

            $sretRow = Database::fetchOne("
                SELECT 
                    COALESCE(SUM(sr.total_return_amount), 0.00) AS total_returns,
                    COALESCE(SUM(sr.applied_to_payable), 0.00) AS total_applied_to_payable
                FROM supplier_returns sr
                $sretWhereSql
            ", $sretParams);

            $actualTotal  = round((float)($grnRow['total_grn_amount'] ?? 0), 2);
            $settledTotal = round((float)($grnRow['total_settled_amount'] ?? 0), 2);
            $returnsTot   = round((float)($sretRow['total_returns'] ?? 0), 2);
            $returnOffset = round((float)($sretRow['total_applied_to_payable'] ?? 0), 2);

            // Separate actual monetary payments paid from supplier return offsets
            $paidTotal    = max(0.00, round($settledTotal - $returnOffset, 2));
            $dueTotal     = max(0.00, round($actualTotal - $settledTotal, 2));

            $isSpecial = !empty($filters['special_user']) || MaskingHelper::isSpecialUser();
            if ($isSpecial) {
                $actualTotal = MaskingHelper::maskValue($actualTotal, true);
                $paidTotal   = MaskingHelper::maskValue($paidTotal, true);
                $dueTotal    = MaskingHelper::maskValue($dueTotal, true);
                $returnsTot  = MaskingHelper::maskValue($returnsTot, true);
            }

            return [
                'grn_count'           => (int)($grnRow['grn_count'] ?? 0),
                'total_grn_amount'    => $actualTotal,
                'total_payments_paid' => $paidTotal,
                'outstanding_due'     => $dueTotal,
                'total_returns'       => $returnsTot
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to fetch purchase summary report: " . $e->getMessage(), $e);
            return [];
        }
    }

    public static function getSupplierOutstandingReport(array $filters): array
    {
        try {
            $asOfDate = !empty($filters['date_to']) ? $filters['date_to'] : date('Y-m-d');

            $grnWhere = ["g.status = 'Approved'", "(g.actual_grn_total - g.paid_amount) > 0.001"];
            $obWhere  = ["ob.status = 'posted'", "ob.balance_type = 'owe_supplier'", "(ob.amount - ob.paid_amount) > 0.001"];
            $params   = [];

            if (isset($filters['branch_id']) && $filters['branch_id'] !== '' && $filters['branch_id'] !== 'all') {
                $grnWhere[] = "g.branch_id = :b_grn";
                $obWhere[]  = "ob.branch_id = :b_ob";
                $params[':b_grn'] = (int)$filters['branch_id'];
                $params[':b_ob']  = (int)$filters['branch_id'];
            }

            if (!empty($filters['supplier_id'])) {
                $grnWhere[] = "g.supplier_id = :s_grn";
                $obWhere[]  = "ob.supplier_id = :s_ob";
                $params[':s_grn'] = (int)$filters['supplier_id'];
                $params[':s_ob']  = (int)$filters['supplier_id'];
            }

            $params[':as_of_1'] = $asOfDate;
            $params[':as_of_2'] = $asOfDate;
            $params[':as_of_3'] = $asOfDate;
            $params[':as_of_4'] = $asOfDate;
            $params[':as_of_5'] = $asOfDate;
            $params[':as_of_6'] = $asOfDate;
            $params[':as_of_7'] = $asOfDate;
            $params[':as_of_8'] = $asOfDate;

            $grnWhereSql = implode(' AND ', $grnWhere);
            $obWhereSql  = implode(' AND ', $obWhere);

            $rows = Database::fetchAll("
                SELECT 
                    s.id AS supplier_id,
                    s.supplier_code,
                    s.name AS supplier_name,
                    s.phone,
                    COALESCE(SUM(sub.due_amount), 0.00) AS total_payable,
                    COALESCE(SUM(sub.current_amount), 0.00) AS current_amount,
                    COALESCE(SUM(sub.days_1_30), 0.00) AS days_1_30,
                    COALESCE(SUM(sub.days_31_60), 0.00) AS days_31_60,
                    COALESCE(SUM(sub.days_60_plus), 0.00) AS days_60_plus,
                    COALESCE(SUM(sub.purchases), 0.00) AS total_grn_amount,
                    COALESCE(SUM(sub.paid), 0.00) AS total_paid
                FROM (
                    SELECT 
                        g.supplier_id,
                        (g.actual_grn_total - g.paid_amount) AS due_amount,
                        g.actual_grn_total AS purchases,
                        g.paid_amount AS paid,
                        CASE WHEN DATEDIFF(:as_of_1, g.received_date) <= 0 THEN (g.actual_grn_total - g.paid_amount) ELSE 0.00 END AS current_amount,
                        CASE WHEN DATEDIFF(:as_of_2, g.received_date) BETWEEN 1 AND 30 THEN (g.actual_grn_total - g.paid_amount) ELSE 0.00 END AS days_1_30,
                        CASE WHEN DATEDIFF(:as_of_3, g.received_date) BETWEEN 31 AND 60 THEN (g.actual_grn_total - g.paid_amount) ELSE 0.00 END AS days_31_60,
                        CASE WHEN DATEDIFF(:as_of_4, g.received_date) > 60 THEN (g.actual_grn_total - g.paid_amount) ELSE 0.00 END AS days_60_plus
                    FROM grns g
                    WHERE $grnWhereSql

                    UNION ALL

                    SELECT 
                        ob.supplier_id,
                        (ob.amount - ob.paid_amount) AS due_amount,
                        ob.amount AS purchases,
                        ob.paid_amount AS paid,
                        CASE WHEN DATEDIFF(:as_of_5, COALESCE(ob.entry_date, DATE(ob.created_at))) <= 0 THEN (ob.amount - ob.paid_amount) ELSE 0.00 END AS current_amount,
                        CASE WHEN DATEDIFF(:as_of_6, COALESCE(ob.entry_date, DATE(ob.created_at))) BETWEEN 1 AND 30 THEN (ob.amount - ob.paid_amount) ELSE 0.00 END AS days_1_30,
                        CASE WHEN DATEDIFF(:as_of_7, COALESCE(ob.entry_date, DATE(ob.created_at))) BETWEEN 31 AND 60 THEN (ob.amount - ob.paid_amount) ELSE 0.00 END AS days_31_60,
                        CASE WHEN DATEDIFF(:as_of_8, COALESCE(ob.entry_date, DATE(ob.created_at))) > 60 THEN (ob.amount - ob.paid_amount) ELSE 0.00 END AS days_60_plus
                    FROM supplier_opening_balances ob
                    WHERE $obWhereSql
                ) sub
                JOIN suppliers s ON sub.supplier_id = s.id
                WHERE s.deleted_at IS NULL
                GROUP BY s.id, s.name, s.supplier_code, s.phone
                HAVING SUM(sub.due_amount) > 0.001
                ORDER BY SUM(sub.due_amount) DESC
            ", $params);

            // Check unallocated supplier credit notes
            $crWhere = "status = 'active' AND remaining_credit_amount > 0.001";
            $crParams = [];
            if (isset($filters['branch_id']) && $filters['branch_id'] !== '' && $filters['branch_id'] !== 'all') {
                $crWhere .= " AND branch_id = :b_cr";
                $crParams[':b_cr'] = (int)$filters['branch_id'];
            }
            $credits = Database::fetchAll("
                SELECT supplier_id, SUM(remaining_credit_amount) AS total_credit
                FROM supplier_credits
                WHERE {$crWhere}
                GROUP BY supplier_id
            ", $crParams);
            $creditMap = [];
            foreach ($credits as $cr) {
                $creditMap[(int)$cr['supplier_id']] = (float)$cr['total_credit'];
            }

            foreach ($rows as &$r) {
                $supId = (int)$r['supplier_id'];
                $cur = (float)$r['current_amount'];
                $d1  = (float)$r['days_1_30'];
                $d2  = (float)$r['days_31_60'];
                $d3  = (float)$r['days_60_plus'];
                $tot = (float)$r['total_payable'];

                if (!empty($creditMap[$supId])) {
                    $cRem = $creditMap[$supId];
                    if ($d3 > 0) {
                        $deduct = min($d3, $cRem);
                        $d3 -= $deduct;
                        $cRem -= $deduct;
                    }
                    if ($cRem > 0 && $d2 > 0) {
                        $deduct = min($d2, $cRem);
                        $d2 -= $deduct;
                        $cRem -= $deduct;
                    }
                    if ($cRem > 0 && $d1 > 0) {
                        $deduct = min($d1, $cRem);
                        $d1 -= $deduct;
                        $cRem -= $deduct;
                    }
                    if ($cRem > 0 && $cur > 0) {
                        $deduct = min($cur, $cRem);
                        $cur -= $deduct;
                        $cRem -= $deduct;
                    }
                    $tot = max(0.00, $cur + $d1 + $d2 + $d3);
                }

                $isSpecial = !empty($filters['special_user']) || MaskingHelper::isSpecialUser();
                if ($isSpecial) {
                    $cur = MaskingHelper::maskValue($cur, true);
                    $d1  = MaskingHelper::maskValue($d1, true);
                    $d2  = MaskingHelper::maskValue($d2, true);
                    $d3  = MaskingHelper::maskValue($d3, true);
                    $tot = MaskingHelper::maskValue($tot, true);
                }

                $r['current_amount']       = round($cur, 2);
                $r['days_1_30']            = round($d1, 2);
                $r['days_31_60']           = round($d2, 2);
                $r['days_60_plus']         = round($d3, 2);
                $r['total_payable']        = round($tot, 2);
                $r['outstanding_balance']  = round($tot, 2);
            }
            unset($r);

            return $rows;
        } catch (Throwable $e) {
            Logger::error("Failed to fetch supplier outstanding report: " . $e->getMessage(), $e);
            return [];
        }
    }

    // =========================================================================
    // 3. INVENTORY REPORTS
    // =========================================================================

    public static function getStockValuationReport(array $filters): array
    {
        try {
            $where = ["pl.remaining_quantity > 0 AND pl.status = 'active' AND p.deleted_at IS NULL"];
            $params = [];

            if (isset($filters['branch_id']) && $filters['branch_id'] !== '' && $filters['branch_id'] !== 'all') {
                $where[] = "pl.branch_id = :branch_id";
                $params[':branch_id'] = (int)$filters['branch_id'];
            }

            if (!empty($filters['category_id'])) {
                $where[] = "p.category_id = :category_id";
                $params[':category_id'] = (int)$filters['category_id'];
            }

            if (!empty($filters['brand_id'])) {
                $where[] = "p.brand_id = :brand_id";
                $params[':brand_id'] = (int)$filters['brand_id'];
            }

            $whereSql = implode(' AND ', $where);

            // Fetch itemized remaining product lots valuation with exact lot cost
            $items = Database::fetchAll("
                SELECT 
                    pl.id AS lot_id,
                    COALESCE(NULLIF(pl.lot_number, ''), CONCAT('LOT-', pl.id)) AS lot_number,
                    p.id AS product_id,
                    p.name,
                    COALESCE(p.sku, p.barcode) AS sku,
                    p.barcode,
                    COALESCE(cat.name, 'Uncategorized') AS category_name,
                    COALESCE(brd.name, 'No Brand') AS brand_name,
                    pl.remaining_quantity AS remaining_qty,
                    pl.cost AS unit_cost,
                    (pl.remaining_quantity * pl.cost) AS total_lot_valuation
                FROM product_lots pl
                JOIN products p ON pl.product_id = p.id
                LEFT JOIN categories cat ON p.category_id = cat.id
                LEFT JOIN brands brd ON p.brand_id = brd.id
                WHERE $whereSql
                ORDER BY p.name ASC, pl.id ASC
            ", $params);

            $totQty = 0.00;
            $totVal = 0.00;

            foreach ($items as &$it) {
                $qty  = (float)$it['remaining_qty'];
                $cost = (float)$it['unit_cost'];
                $val  = (float)$it['total_lot_valuation'];

                $isSpecial = !empty($filters['special_user']) || MaskingHelper::isSpecialUser();
                if ($isSpecial) {
                    $qty  = MaskingHelper::maskQuantity($qty, true);
                    $cost = MaskingHelper::maskValue($cost, true);
                    $val  = MaskingHelper::maskValue($val, true);
                }

                $it['remaining_qty']      = $qty;
                $it['unit_cost']          = round($cost, 2);
                $it['total_lot_valuation']= round($val, 2);

                $totQty += $qty;
                $totVal += $val;
            }

            return [
                'items'           => $items,
                'total_qty'       => round($totQty, 2),
                'total_valuation' => round($totVal, 2)
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to fetch stock valuation report: " . $e->getMessage(), $e);
            return ['items' => [], 'total_qty' => 0, 'total_valuation' => 0.00];
        }
    }

    public static function getLowStockReport(array $filters): array
    {
        try {
            $branchId = isset($filters['branch_id']) && $filters['branch_id'] !== '' && $filters['branch_id'] !== 'all'
                ? (int)$filters['branch_id'] : 1;

            $rows = Database::fetchAll("
                SELECT 
                    p.id AS product_id,
                    p.name,
                    COALESCE(p.sku, p.barcode) AS sku,
                    p.barcode,
                    COALESCE(c.name, 'Uncategorized') AS category_name,
                    COALESCE(SUM(pl.remaining_quantity), 0.00) AS total_stock,
                    p.low_stock_threshold AS reorder_level
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN product_lots pl ON p.id = pl.product_id AND pl.branch_id = :branch_id AND pl.remaining_quantity > 0 AND pl.status = 'active'
                WHERE p.deleted_at IS NULL AND p.status = 'active'
                GROUP BY p.id
                HAVING total_stock <= p.low_stock_threshold
                ORDER BY total_stock ASC
            ", [':branch_id' => $branchId]);

            foreach ($rows as &$r) {
                $stock = (float)$r['total_stock'];
                if (MaskingHelper::isSpecialUser()) {
                    $stock = MaskingHelper::maskQuantity($stock);
                }
                $r['total_stock'] = $stock;
            }

            return $rows;
        } catch (Throwable $e) {
            Logger::error("Failed to fetch low stock report: " . $e->getMessage(), $e);
            return [];
        }
    }

    // =========================================================================
    // 4. ACCOUNTING FINANCIAL STATEMENTS (Directly from Journal Entries/Lines)
    // =========================================================================

    public static function getTrialBalance(array $filters): array
    {
        try {
            $where = ["je.posting_status IN ('posted', 'reversed')"];
            $params = [];

            if (isset($filters['branch_id']) && $filters['branch_id'] !== '' && $filters['branch_id'] !== 'all') {
                $where[] = "je.branch_id = :branch_id";
                $params[':branch_id'] = (int)$filters['branch_id'];
            }

            if (!empty($filters['date_to'])) {
                $where[] = "je.entry_date <= :date_to";
                $params[':date_to'] = $filters['date_to'];
            }

            $whereSql = implode(' AND ', $where);

            $rows = Database::fetchAll("
                SELECT 
                    coa.id AS account_id,
                    coa.account_code,
                    coa.account_name,
                    coa.account_type,
                    SUM(jl.debit) AS total_debit,
                    SUM(jl.credit) AS total_credit
                FROM journal_lines jl
                JOIN journal_entries je ON jl.journal_entry_id = je.id
                JOIN chart_of_accounts coa ON jl.account_id = coa.id
                WHERE $whereSql
                GROUP BY coa.id
                ORDER BY coa.account_code ASC
            ", $params);

            $sumDebit = 0.00;
            $sumCredit = 0.00;
            $accounts = [];

            foreach ($rows as $r) {
                $d = round((float)$r['total_debit'], 2);
                $c = round((float)$r['total_credit'], 2);

                $isSpecial = !empty($filters['special_user']) || MaskingHelper::isSpecialUser();
                if ($isSpecial) {
                    $d = MaskingHelper::maskValue($d, true);
                    $c = MaskingHelper::maskValue($c, true);
                }

                $net = round($d - $c, 2);
                $balDebit = ($net > 0.001) ? $net : 0.00;
                $balCredit = ($net < -0.001) ? abs($net) : 0.00;

                $accounts[] = [
                    'account_id'   => $r['account_id'],
                    'account_code' => $r['account_code'],
                    'account_name' => $r['account_name'],
                    'account_type' => $r['account_type'],
                    'debit'        => $balDebit,
                    'credit'       => $balCredit,
                    'net_balance'  => $net
                ];

                $sumDebit += $balDebit;
                $sumCredit += $balCredit;
            }

            return [
                'accounts'     => $accounts,
                'total_debit'  => round($sumDebit, 2),
                'total_credit' => round($sumCredit, 2),
                'is_balanced'  => abs($sumDebit - $sumCredit) < 0.01
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to generate Trial Balance: " . $e->getMessage(), $e);
            return ['accounts' => [], 'total_debit' => 0, 'total_credit' => 0, 'is_balanced' => true];
        }
    }

    public static function getProfitAndLoss(array $filters): array
    {
        try {
            $where = ["je.posting_status IN ('posted', 'reversed')"];
            $params = [];

            if (isset($filters['branch_id']) && $filters['branch_id'] !== '' && $filters['branch_id'] !== 'all') {
                $where[] = "je.branch_id = :branch_id";
                $params[':branch_id'] = (int)$filters['branch_id'];
            }

            if (!empty($filters['date_from'])) {
                $where[] = "je.entry_date >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }

            if (!empty($filters['date_to'])) {
                $where[] = "je.entry_date <= :date_to";
                $params[':date_to'] = $filters['date_to'];
            }

            $whereSql = implode(' AND ', $where);

            // Fetch income and expense account movement totals
            $rows = Database::fetchAll("
                SELECT 
                    coa.account_code,
                    coa.account_name,
                    coa.account_type,
                    SUM(jl.debit) AS total_debit,
                    SUM(jl.credit) AS total_credit
                FROM journal_lines jl
                JOIN journal_entries je ON jl.journal_entry_id = je.id
                JOIN chart_of_accounts coa ON jl.account_id = coa.id
                WHERE $whereSql AND coa.account_type IN ('income', 'expense')
                GROUP BY coa.id
                ORDER BY coa.account_code ASC
            ", $params);

            $salesRevenue = 0.00;
            $otherIncome  = 0.00;
            $salesReturns = 0.00;
            $cogs         = 0.00;
            $expenses     = [];
            $totalExpenses = 0.00;

            $isSpecial = !empty($filters['special_user']) || MaskingHelper::isSpecialUser();

            foreach ($rows as $r) {
                $code = $r['account_code'];
                $type = $r['account_type'];
                $debit = (float)$r['total_debit'];
                $credit = (float)$r['total_credit'];

                if ($type === 'income') {
                    if ($code === '4010') {
                        $salesRevenue += ($credit - $debit);
                    } elseif ($code === '4020') { // Sales returns contra-revenue
                        $salesReturns += ($debit - $credit);
                    } else {
                        $otherIncome += ($credit - $debit);
                    }
                } elseif ($type === 'expense') {
                    $netExp = ($debit - $credit);
                    if ($code === '5010') {
                        $cogs += $netExp;
                    } else {
                        $totalExpenses += $netExp;
                        $expenses[] = [
                            'account_code' => $code,
                            'account_name' => $r['account_name'],
                            'amount'       => round(MaskingHelper::maskValue($netExp, $isSpecial), 2)
                        ];
                    }
                }
            }

            $salesRevenue = round($salesRevenue, 2);
            $otherIncome  = round($otherIncome, 2);
            $salesReturns = round($salesReturns, 2);
            $netRevenue   = round($salesRevenue - $salesReturns, 2);
            $cogs         = round($cogs, 2);
            $grossProfit  = round($netRevenue - $cogs, 2);
            $totalExpenses= round($totalExpenses, 2);
            $netProfit    = round($grossProfit + $otherIncome - $totalExpenses, 2);

            if ($isSpecial) {
                $salesRevenue = MaskingHelper::maskValue($salesRevenue, true);
                $otherIncome  = MaskingHelper::maskValue($otherIncome, true);
                $salesReturns = MaskingHelper::maskValue($salesReturns, true);
                $netRevenue   = MaskingHelper::maskValue($netRevenue, true);
                $cogs         = MaskingHelper::maskValue($cogs, true);
                $grossProfit  = MaskingHelper::maskValue($grossProfit, true);
                $totalExpenses= MaskingHelper::maskValue($totalExpenses, true);
                $netProfit    = MaskingHelper::maskValue($netProfit, true);
            }

            return [
                'gross_sales'     => $salesRevenue,
                'other_income'    => $otherIncome,
                'sales_returns'   => $salesReturns,
                'net_revenue'     => $netRevenue,
                'net_sales'       => $netRevenue,
                'cogs'            => $cogs,
                'gross_profit'    => $grossProfit,
                'operating_expenses' => $expenses,
                'total_expenses'  => $totalExpenses,
                'net_profit'      => $netProfit
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to generate P&L: " . $e->getMessage(), $e);
            return [];
        }
    }

    public static function getBalanceSheet(array $filters): array
    {
        try {
            $where = ["je.posting_status IN ('posted', 'reversed')"];
            $params = [];

            if (isset($filters['branch_id']) && $filters['branch_id'] !== '' && $filters['branch_id'] !== 'all') {
                $where[] = "je.branch_id = :branch_id";
                $params[':branch_id'] = (int)$filters['branch_id'];
            }

            if (!empty($filters['date_to'])) {
                $where[] = "je.entry_date <= :date_to";
                $params[':date_to'] = $filters['date_to'];
            }

            $whereSql = implode(' AND ', $where);

            $rows = Database::fetchAll("
                SELECT 
                    coa.account_code,
                    coa.account_name,
                    coa.account_type,
                    SUM(jl.debit) AS total_debit,
                    SUM(jl.credit) AS total_credit
                FROM journal_lines jl
                JOIN journal_entries je ON jl.journal_entry_id = je.id
                JOIN chart_of_accounts coa ON jl.account_id = coa.id
                WHERE $whereSql AND coa.account_type IN ('asset', 'liability', 'equity')
                GROUP BY coa.id
                ORDER BY coa.account_code ASC
            ", $params);

            $assets = [];
            $liabilities = [];
            $equity = [];

            $totalAssets = 0.00;
            $totalLiabilities = 0.00;
            $totalEquity = 0.00;

            foreach ($rows as $r) {
                $code  = $r['account_code'];
                $type  = $r['account_type'];
                $debit = (float)$r['total_debit'];
                $credit = (float)$r['total_credit'];

                // Operational bank accounts (codes starting with 102 except structural parent 1020) stay REAL
                $isBankAccount = ($code !== '1020' && (strpos($code, '102') === 0 || (stripos($r['account_name'], 'bank') !== false && strpos($code, '10') === 0)));

                $isSpecial = !empty($filters['special_user']) || MaskingHelper::isSpecialUser();
                if ($type === 'asset') {
                    $net = round($debit - $credit, 2);
                    if ($isSpecial && !$isBankAccount) {
                        $net = MaskingHelper::maskValue($net);
                    }
                    $totalAssets += (is_numeric($net) ? $net : 0);
                    $assets[] = ['account_code' => $code, 'account_name' => $r['account_name'], 'balance' => $net];
                } elseif ($type === 'liability') {
                    $net = round($credit - $debit, 2);
                    if ($isSpecial) {
                        $net = MaskingHelper::maskValue($net);
                    }
                    $totalLiabilities += (is_numeric($net) ? $net : 0);
                    $liabilities[] = ['account_code' => $code, 'account_name' => $r['account_name'], 'balance' => $net];
                } elseif ($type === 'equity') {
                    $net = round($credit - $debit, 2);
                    if ($isSpecial) {
                        $net = MaskingHelper::maskValue($net);
                    }
                    $totalEquity += (is_numeric($net) ? $net : 0);
                    $equity[] = ['account_code' => $code, 'account_name' => $r['account_name'], 'balance' => $net];
                }
            }

            // Calculate Net Profit from P&L to include in Equity (Retained Earnings)
            $pnl = self::getProfitAndLoss($filters);
            $netProfit = (float)($pnl['net_profit'] ?? 0.00);

            $totalAssets                = round($totalAssets, 2);
            $totalLiabilities            = round($totalLiabilities, 2);
            $totalEquity                = round($totalEquity + $netProfit, 2);
            $totalLiabEquity            = round($totalLiabilities + $totalEquity, 2);

            return [
                'assets'                      => ['accounts' => $assets, 'total' => $totalAssets],
                'liabilities'                 => ['accounts' => $liabilities, 'total' => $totalLiabilities],
                'equity'                      => ['accounts' => $equity, 'total' => $totalEquity],
                'retained_earnings'          => round($netProfit, 2),
                'total_liabilities_and_equity'=> $totalLiabEquity,
                'is_balanced'                 => abs($totalAssets - $totalLiabEquity) < 0.01
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to generate Balance Sheet: " . $e->getMessage(), $e);
            return [];
        }
    }

    // =========================================================================
    // 5. KPI DASHBOARD
    // =========================================================================

    public static function getKPIPerformance(array $filters): array
    {
        try {
            $salesSummary  = self::getSalesSummary($filters);
            $purchSummary  = self::getPurchaseSummary($filters);
            $stockVal      = self::getStockValuationReport($filters);
            $lowStock      = self::getLowStockReport($filters);
            $supOut        = self::getSupplierOutstandingReport($filters);
            $pnl           = self::getProfitAndLoss($filters);
            $bs            = self::getBalanceSheet($filters);
            $tb            = self::getTrialBalance($filters);

            // Compute total supplier outstanding
            $totalSupDue = 0.00;
            foreach ($supOut as $so) {
                $totalSupDue += (float)($so['outstanding_balance'] ?? 0);
            }

            return [
                'sales' => [
                    'gross_sales'     => $salesSummary['gross_sales'] ?? 0.00,
                    'net_sales'       => $salesSummary['net_sales'] ?? 0.00,
                    'total_discounts' => $salesSummary['total_discounts'] ?? 0.00,
                    'cogs'            => $salesSummary['cogs'] ?? 0.00,
                    'gross_profit'    => $salesSummary['gross_profit'] ?? 0.00,
                    'profit_margin'   => $salesSummary['profit_margin'] ?? 0.00,
                    'invoice_count'   => $salesSummary['invoice_count'] ?? 0,
                ],
                'inventory' => [
                    'total_valuation' => $stockVal['total_valuation'] ?? 0.00,
                    'total_qty'       => $stockVal['total_qty'] ?? 0,
                    'low_stock_count' => count($lowStock)
                ],
                'purchases' => [
                    'total_grn'                  => $purchSummary['total_grn_amount'] ?? 0.00,
                    'total_supplier_outstanding' => $totalSupDue,
                    'supplier_count'             => count($supOut)
                ],
                'financials' => [
                    'net_profit'             => $pnl['net_profit'] ?? 0.00,
                    'trial_balance_balanced' => $tb['is_balanced'] ?? true,
                    'balance_sheet_balanced' => $bs['is_balanced'] ?? true
                ]
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to generate KPI performance dashboard: " . $e->getMessage(), $e);
            return [];
        }
    }

    public static function getCustomerOutstandingReport(array $filters): array
    {
        return ['customers' => [], 'total_outstanding' => 0.00];
    }


    public static function getDamagedStockReport(array $filters): array
    {
        try {
            $where = ["ds.status = 'active'", "p.deleted_at IS NULL"];
            $params = [];

            if (isset($filters['branch_id']) && $filters['branch_id'] !== '' && $filters['branch_id'] !== 'all') {
                $where[] = "ds.branch_id = :branch_id";
                $params[':branch_id'] = (int)$filters['branch_id'];
            }

            $whereSql = implode(' AND ', $where);

            $rows = Database::fetchAll("
                SELECT 
                    ds.id,
                    b.name AS branch_name,
                    p.name AS product_name,
                    COALESCE(p.sku, p.barcode) AS sku,
                    p.barcode,
                    ds.stock_type,
                    ds.quantity,
                    ds.cost,
                    (ds.quantity * ds.cost) AS total_cost_loss,
                    ds.reason,
                    ds.created_at AS logged_date
                FROM damaged_stock ds
                JOIN products p ON ds.product_id = p.id
                JOIN branches b ON ds.branch_id = b.id
                WHERE $whereSql
                ORDER BY ds.created_at DESC, ds.id DESC
            ", $params);

            $totalQty = 0.00;
            $totalLoss = 0.00;

            foreach ($rows as &$r) {
                $q = (float)$r['quantity'];
                $c = (float)$r['cost'];
                $l = (float)$r['total_cost_loss'];

                if (MaskingHelper::isSpecialUser()) {
                    $q = MaskingHelper::maskQuantity($q);
                    $c = MaskingHelper::maskValue($c);
                    $l = MaskingHelper::maskValue($l);
                }

                $r['quantity']        = round($q, 2);
                $r['cost']            = round($c, 2);
                $r['total_cost_loss'] = round($l, 2);

                $totalQty += $q;
                $totalLoss += $l;
            }

            return [
                'items'      => $rows,
                'total_qty'  => round($totalQty, 2),
                'total_loss' => round($totalLoss, 2)
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to fetch damaged stock report: " . $e->getMessage(), $e);
            return ['items' => [], 'total_qty' => 0, 'total_loss' => 0.00];
        }
    }

    public static function getInTransitStockReport(array $filters): array
    {
        try {
            $rows = Database::fetchAll("
                SELECT 
                    st.id AS transfer_id,
                    st.transfer_number,
                    fb.name AS from_branch,
                    tb.name AS to_branch,
                    p.name AS product_name,
                    COALESCE(p.sku, p.barcode) AS sku,
                    p.barcode,
                    sti.quantity,
                    sti.cost AS unit_cost,
                    (sti.quantity * sti.cost) AS transfer_value,
                    st.sent_at
                FROM stock_transfers st
                JOIN stock_transfer_items sti ON st.id = sti.transfer_id
                JOIN products p ON sti.product_id = p.id
                JOIN branches fb ON st.from_branch_id = fb.id
                JOIN branches tb ON st.to_branch_id = tb.id
                WHERE st.status = 'sent' AND p.deleted_at IS NULL
                ORDER BY st.sent_at DESC, st.id DESC
            ");

            $totalVal = 0.00;
            foreach ($rows as &$r) {
                $v = (float)$r['transfer_value'];
                if (MaskingHelper::isSpecialUser()) {
                    $v = MaskingHelper::maskValue($v);
                }
                $r['transfer_value'] = round($v, 2);
                $totalVal += $v;
            }

            return [
                'items'       => $rows,
                'total_value' => round($totalVal, 2)
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to fetch in-transit stock report: " . $e->getMessage(), $e);
            return ['items' => [], 'total_value' => 0.00];
        }
    }

    public static function getPettyCashRegisterReport(array $filters): array
    {
        try {
            $pcAccId = Accounting::getAccountIdByCode(Accounting::CODE_PETTY_CASH);
            if (!$pcAccId) {
                return ['items' => []];
            }

            $where = [
                "jl.account_id = :acc_id",
                "je.posting_status IN ('posted', 'reversed')"
            ];
            $params = [':acc_id' => $pcAccId];

            if (isset($filters['branch_id']) && $filters['branch_id'] !== '' && $filters['branch_id'] !== 'all') {
                $where[] = "je.branch_id = :branch_id";
                $params[':branch_id'] = (int)$filters['branch_id'];
            }

            if (!empty($filters['date_from'])) {
                $where[] = "je.entry_date >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }

            if (!empty($filters['date_to'])) {
                $where[] = "je.entry_date <= :date_to";
                $params[':date_to'] = $filters['date_to'];
            }

            if (!empty($filters['search'])) {
                $searchTerm = '%' . trim($filters['search']) . '%';
                $where[] = "(je.entry_number LIKE :s1 OR je.description LIKE :s2 OR jl.memo LIKE :s3 OR b.name LIKE :s4)";
                $params[':s1'] = $searchTerm;
                $params[':s2'] = $searchTerm;
                $params[':s3'] = $searchTerm;
                $params[':s4'] = $searchTerm;
            }

            $whereSql = implode(' AND ', $where);

            $rows = Database::fetchAll("
                SELECT 
                    je.entry_number AS id,
                    b.name AS branch_name,
                    CASE WHEN jl.debit > 0 THEN 'deposit' ELSE 'expense' END AS movement_type,
                    GREATEST(jl.debit, jl.credit) AS amount,
                    CONCAT(je.description, CASE WHEN jl.memo IS NOT NULL AND jl.memo != '' THEN CONCAT(' (', jl.memo, ')') ELSE '' END) AS reason,
                    je.created_at
                FROM journal_lines jl
                JOIN journal_entries je ON jl.journal_entry_id = je.id
                JOIN branches b ON je.branch_id = b.id
                WHERE $whereSql
                ORDER BY je.entry_date DESC, je.id DESC
                LIMIT 200
            ", $params);

            $isSpecial = !empty($filters['special_user']) || MaskingHelper::isSpecialUser();
            if ($isSpecial) {
                foreach ($rows as &$r) {
                    $r['amount'] = MaskingHelper::maskValue((float)$r['amount'], true);
                }
            }

            return ['items' => $rows];
        } catch (Throwable $e) {
            Logger::error("Failed to fetch petty cash register report: " . $e->getMessage(), $e);
            return ['items' => []];
        }
    }

    public static function getBankRegisterReport(array $filters): array
    {
        try {
            require_once __DIR__ . '/Banking.php';

            $where = [
                "ba.status = 'active'",
                "je.posting_status IN ('posted', 'reversed')"
            ];
            $params = [];

            $bankAccountId = !empty($filters['bank_account_id']) && $filters['bank_account_id'] !== 'all' ? (int)$filters['bank_account_id'] : 0;
            if ($bankAccountId > 0) {
                $where[] = "ba.id = :bank_acc_id";
                $params[':bank_acc_id'] = $bankAccountId;
            }

            if (!empty($filters['branch_id']) && $filters['branch_id'] !== 'all') {
                $where[] = "(je.branch_id = :branch_id OR je.branch_id IS NULL)";
                $params[':branch_id'] = (int)$filters['branch_id'];
            }

            if (!empty($filters['date_from'])) {
                $where[] = "je.entry_date >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }

            if (!empty($filters['date_to'])) {
                $where[] = "je.entry_date <= :date_to";
                $params[':date_to'] = $filters['date_to'];
            }

            if (!empty($filters['search'])) {
                $searchTerm = '%' . trim($filters['search']) . '%';
                $where[] = "(je.entry_number LIKE :s1 OR je.description LIKE :s2 OR ba.bank_name LIKE :s3 OR ba.account_number LIKE :s4)";
                $params[':s1'] = $searchTerm;
                $params[':s2'] = $searchTerm;
                $params[':s3'] = $searchTerm;
                $params[':s4'] = $searchTerm;
            }

            $whereSql = implode(' AND ', $where);

            $branchFilter = !empty($filters['branch_id']) && $filters['branch_id'] !== 'all' ? (int)$filters['branch_id'] : null;
            $bankAccounts = Banking::getBankAccounts($branchFilter, true);

            $selectedBankAccount = null;
            $openingBalance = 0.00;

            if ($bankAccountId > 0) {
                $selectedBankAccount = Banking::getBankAccount($bankAccountId);
                if ($selectedBankAccount) {
                    $openingBalance = (float)$selectedBankAccount['opening_balance'];
                    if (!empty($filters['date_from'])) {
                        $priorParams = [':chart_acc_id' => (int)$selectedBankAccount['account_id'], ':date_from' => $filters['date_from']];
                        $priorBranchSql = "";
                        if (!empty($filters['branch_id']) && $filters['branch_id'] !== 'all') {
                            $priorBranchSql = "AND (je.branch_id = :branch_id OR je.branch_id IS NULL)";
                            $priorParams[':branch_id'] = (int)$filters['branch_id'];
                        }
                        $priorRow = Database::fetchOne("
                            SELECT SUM(jl.debit) - SUM(jl.credit) AS prior_net
                            FROM journal_lines jl
                            INNER JOIN journal_entries je ON jl.journal_entry_id = je.id
                            WHERE jl.account_id = :chart_acc_id
                              AND je.posting_status = 'posted'
                              AND je.entry_date < :date_from
                              $priorBranchSql
                        ", $priorParams);
                        $openingBalance += (float)($priorRow['prior_net'] ?? 0.00);
                    }
                }
            } else {
                foreach ($bankAccounts as $bAcc) {
                    $openingBalance += (float)$bAcc['opening_balance'];
                }
                if (!empty($filters['date_from'])) {
                    $chartIds = array_column($bankAccounts, 'account_id');
                    if (!empty($chartIds)) {
                        $inPlaceholders = implode(',', array_map('intval', $chartIds));
                        $priorParams = [':date_from' => $filters['date_from']];
                        $priorBranchSql = "";
                        if (!empty($filters['branch_id']) && $filters['branch_id'] !== 'all') {
                            $priorBranchSql = "AND (je.branch_id = :branch_id OR je.branch_id IS NULL)";
                            $priorParams[':branch_id'] = (int)$filters['branch_id'];
                        }
                        $priorRow = Database::fetchOne("
                            SELECT SUM(jl.debit) - SUM(jl.credit) AS prior_net
                            FROM journal_lines jl
                            INNER JOIN journal_entries je ON jl.journal_entry_id = je.id
                            WHERE jl.account_id IN ($inPlaceholders)
                              AND je.posting_status = 'posted'
                              AND je.entry_date < :date_from
                              $priorBranchSql
                        ", $priorParams);
                        $openingBalance += (float)($priorRow['prior_net'] ?? 0.00);
                    }
                }
            }

            $rows = Database::fetchAll("
                SELECT 
                    jl.id,
                    je.id AS journal_entry_id,
                    je.entry_date,
                    je.entry_number,
                    je.description,
                    jl.debit,
                    jl.credit,
                    (jl.debit - jl.credit) AS net_change,
                    ba.id AS bank_account_id,
                    ba.bank_name,
                    ba.account_name,
                    ba.account_number
                FROM journal_lines jl
                INNER JOIN journal_entries je ON jl.journal_entry_id = je.id
                INNER JOIN bank_accounts ba ON jl.account_id = ba.account_id
                WHERE $whereSql
                ORDER BY je.entry_date ASC, je.id ASC
            ", $params);

            $runningBalance = $openingBalance;
            $totalDebit = 0.00;
            $totalCredit = 0.00;

            foreach ($rows as &$r) {
                $net = (float)$r['net_change'];
                $totalDebit += (float)$r['debit'];
                $totalCredit += (float)$r['credit'];
                $runningBalance += $net;
                $r['running_balance'] = round($runningBalance, 2);
            }
            unset($r);

            // Re-order rows so the latest records appear on top (DESC)
            $rows = array_values(array_reverse($rows));

            return [
                'bank_accounts'    => $bankAccounts,
                'selected_bank_id' => $bankAccountId > 0 ? (string)$bankAccountId : 'all',
                'bank_account'     => $selectedBankAccount,
                'opening_balance'  => round($openingBalance, 2),
                'total_debit'      => round($totalDebit, 2),
                'total_credit'     => round($totalCredit, 2),
                'items'            => $rows,
                'current_balance'  => round($runningBalance, 2)
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to fetch bank register report: " . $e->getMessage(), $e);
            return ['bank_accounts' => [], 'bank_account' => null, 'items' => []];
        }
    }

    public static function getCustomerChequeReport(array $filters): array
    {
        return ['items' => []];
    }


    public static function getSupplierChequeReport(array $filters): array
    {
        try {
            $where = ["1=1"];
            $params = [];

            if (isset($filters['branch_id']) && $filters['branch_id'] !== '' && $filters['branch_id'] !== 'all') {
                $where[] = "sc.branch_id = :branch_id";
                $params[':branch_id'] = (int)$filters['branch_id'];
            }

            if (!empty($filters['status'])) {
                $where[] = "sc.status = :status";
                $params[':status'] = $filters['status'];
            }

            $whereSql = implode(' AND ', $where);

            $rows = Database::fetchAll("
                SELECT 
                    sc.id,
                    sc.cheque_number,
                    sc.cheque_date,
                    sc.amount,
                    sc.status,
                    s.name AS supplier_name,
                    b.name AS branch_name
                FROM supplier_cheques sc
                JOIN suppliers s ON sc.supplier_id = s.id
                JOIN branches b ON sc.branch_id = b.id
                WHERE $whereSql
                ORDER BY sc.cheque_date DESC, sc.id DESC
            ", $params);

            $isSpecial = !empty($filters['special_user']) || MaskingHelper::isSpecialUser();
            if ($isSpecial) {
                foreach ($rows as &$r) {
                    $r['amount'] = MaskingHelper::maskValue((float)$r['amount'], true);
                }
            }

            return ['items' => $rows];
        } catch (Throwable $e) {
            Logger::error("Failed to fetch supplier cheque report: " . $e->getMessage(), $e);
            return ['items' => []];
        }
    }

    // =========================================================================
    // 7. CARD SETTLEMENT & RECONCILIATION REPORTS (SECTION 17)
    // =========================================================================

    /**
     * Unsettled Card Payment Report
     */
    public static function getUnsettledCardReport(array $filters): array
    {
        try {
            require_once __DIR__ . '/CardSettlement.php';

            $items = CardSettlement::getUnsettledCardPayments($filters);
            $totalUnsettled = 0.00;

            foreach ($items as $item) {
                $totalUnsettled += (float)$item['unsettled_amount'];
            }

            $totalUnsettled = round($totalUnsettled, 2);

            $isSpecial = !empty($filters['special_user']) || MaskingHelper::isSpecialUser();
            if ($isSpecial) {
                foreach ($items as &$r) {
                    $r = MaskingHelper::maskCardSettlement($r, true);
                }
                $totalUnsettled = MaskingHelper::maskValue($totalUnsettled, true);
            }

            return [
                'items'           => $items,
                'total_count'     => count($items),
                'total_unsettled' => $totalUnsettled
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to fetch unsettled card report: " . $e->getMessage(), $e);
            return ['items' => [], 'total_count' => 0, 'total_unsettled' => 0.00];
        }
    }

    /**
     * Card Receivable Aging Report (0-1, 2-3, 4-7, 8+ days)
     */
    public static function getCardAgingReport(array $filters): array
    {
        try {
            require_once __DIR__ . '/CardSettlement.php';

            $items = CardSettlement::getUnsettledCardPayments($filters);

            $buckets = [
                'days_0_1'   => ['label' => '0-1 Days',   'count' => 0, 'total_amount' => 0.00, 'items' => []],
                'days_2_3'   => ['label' => '2-3 Days',   'count' => 0, 'total_amount' => 0.00, 'items' => []],
                'days_4_7'   => ['label' => '4-7 Days',   'count' => 0, 'total_amount' => 0.00, 'items' => []],
                'days_8_plus'=> ['label' => '8+ Days',    'count' => 0, 'total_amount' => 0.00, 'items' => []]
            ];

            $grandTotal = 0.00;
            $today = new DateTime(date('Y-m-d'));

            foreach ($items as $item) {
                $pmtDate = new DateTime(date('Y-m-d', strtotime($item['payment_date'])));
                $ageDays = (int)$today->diff($pmtDate)->format('%a');
                $unsettled = (float)$item['unsettled_amount'];

                if ($ageDays <= 1) {
                    $key = 'days_0_1';
                } elseif ($ageDays <= 3) {
                    $key = 'days_2_3';
                } elseif ($ageDays <= 7) {
                    $key = 'days_4_7';
                } else {
                    $key = 'days_8_plus';
                }

                $item['age_days'] = $ageDays;
                $buckets[$key]['count']++;
                $buckets[$key]['total_amount'] += $unsettled;
                $buckets[$key]['items'][] = $item;
                $grandTotal += $unsettled;
            }

            foreach ($buckets as &$b) {
                $b['total_amount'] = round($b['total_amount'], 2);
            }
            $grandTotal = round($grandTotal, 2);

            $isSpecial = !empty($filters['special_user']) || MaskingHelper::isSpecialUser();
            if ($isSpecial) {
                foreach ($buckets as &$b) {
                    $b['total_amount'] = MaskingHelper::maskValue($b['total_amount'], true);
                    foreach ($b['items'] as &$it) {
                        $it = MaskingHelper::maskCardSettlement($it, true);
                    }
                }
                $grandTotal = MaskingHelper::maskValue($grandTotal, true);
            }

            return [
                'buckets'         => $buckets,
                'total_count'     => count($items),
                'total_unsettled' => $grandTotal
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to fetch card aging report: " . $e->getMessage(), $e);
            return ['buckets' => [], 'total_count' => 0, 'total_unsettled' => 0.00];
        }
    }

    /**
     * Card Settlement Register Report
     */
    public static function getCardSettlementRegister(array $filters, int $page = 1, int $limit = 20): array
    {
        try {
            require_once __DIR__ . '/CardSettlement.php';

            $result = CardSettlement::getSettlements($filters, $page, $limit);
            $items  = $result['items'] ?? [];

            $totalGross = 0.00;
            $totalFee   = 0.00;
            $totalNet   = 0.00;

            foreach ($items as $cs) {
                if ($cs['status'] === 'posted') {
                    $totalGross += (float)$cs['gross_amount'];
                    $totalFee   += (float)$cs['processing_fee'];
                    $totalNet   += (float)$cs['net_bank_amount'];
                }
            }

            $totalGross = round($totalGross, 2);
            $totalFee   = round($totalFee, 2);
            $totalNet   = round($totalNet, 2);

            $isSpecial = !empty($filters['special_user']) || MaskingHelper::isSpecialUser();
            if ($isSpecial) {
                foreach ($items as &$cs) {
                    $cs = MaskingHelper::maskCardSettlement($cs, true);
                }
                $totalGross = MaskingHelper::maskValue($totalGross, true);
                $totalFee   = MaskingHelper::maskValue($totalFee, true);
                $totalNet   = MaskingHelper::maskValue($totalNet, true);
            }

            $result['items']       = $items;
            $result['total_gross'] = $totalGross;
            $result['total_fee']   = $totalFee;
            $result['total_net']   = $totalNet;

            return $result;
        } catch (Throwable $e) {
            Logger::error("Failed to fetch card settlement register report: " . $e->getMessage(), $e);
            return ['items' => [], 'totalItems' => 0, 'page' => 1, 'limit' => $limit, 'totalPages' => 1, 'total_gross' => 0, 'total_fee' => 0, 'total_net' => 0];
        }
    }

    public static function getCashierPerformanceReport(array $filters): array
    {
        try {
            $where = ["1=1"];
            $params = [];

            if (isset($filters['branch_id']) && $filters['branch_id'] !== '' && $filters['branch_id'] !== 'all') {
                $where[] = "bd.branch_id = :branch_id";
                $params[':branch_id'] = (int)$filters['branch_id'];
            }

            if (!empty($filters['date_from'])) {
                $where[] = "bd.opened_at >= :date_from";
                $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
            }

            if (!empty($filters['date_to'])) {
                $where[] = "bd.opened_at <= :date_to";
                $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
            }

            $whereSql = implode(' AND ', $where);

            $rows = Database::fetchAll("
                SELECT 
                    bd.id AS day_id,
                    b.name AS branch_name,
                    COALESCE(NULLIF(u.full_name, ''), u.username, 'System User') AS opened_by,
                    bd.opened_at,
                    bd.closed_at,
                    bd.opening_cash,
                    COALESCE(bd.actual_cash, bd.expected_cash, 0.00) AS closing_cash,
                    bd.status,
                    COALESCE((SELECT SUM(cdm.amount) FROM cash_drawer_movements cdm WHERE cdm.business_day_id = bd.id AND (cdm.source_type = 'sales' OR cdm.movement_type = 'sale')), 0.00) AS total_sales,
                    COALESCE((SELECT SUM(cdm.amount) FROM cash_drawer_movements cdm WHERE cdm.business_day_id = bd.id AND cdm.movement_type IN ('deposit', 'manual_in')), 0.00) AS total_deposits
                FROM business_days bd
                JOIN branches b ON bd.branch_id = b.id
                LEFT JOIN users u ON bd.opened_by = u.id
                WHERE $whereSql
                ORDER BY bd.opened_at DESC, bd.id DESC
                LIMIT 200
            ", $params);

            $isSpecial = !empty($filters['special_user']) || MaskingHelper::isSpecialUser();

            foreach ($rows as &$r) {
                $op  = (float)$r['opening_cash'];
                $cl  = (float)$r['closing_cash'];
                $sal = (float)$r['total_sales'];
                $dep = (float)$r['total_deposits'];

                if ($isSpecial) {
                    $op  = MaskingHelper::maskValue($op, true);
                    $cl  = MaskingHelper::maskValue($cl, true);
                    $sal = MaskingHelper::maskValue($sal, true);
                    $dep = MaskingHelper::maskValue($dep, true);
                }

                $r['opening_cash']   = round($op, 2);
                $r['closing_cash']   = round($cl, 2);
                $r['total_sales']    = round($sal, 2);
                $r['total_deposits'] = round($dep, 2);
            }

            return ['items' => $rows];
        } catch (Throwable $e) {
            Logger::error("Failed to fetch cashier performance report: " . $e->getMessage(), $e);
            return ['items' => []];
        }
    }

    public static function getSupplierReturnsReport(array $filters): array
    {
        try {
            $where = ["1=1"];
            $params = [];

            if (isset($filters['branch_id']) && $filters['branch_id'] !== '' && $filters['branch_id'] !== 'all') {
                $where[] = "sr.branch_id = :branch_id";
                $params[':branch_id'] = (int)$filters['branch_id'];
            }

            if (!empty($filters['date_from'])) {
                $where[] = "sr.return_date >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }

            if (!empty($filters['date_to'])) {
                $where[] = "sr.return_date <= :date_to";
                $params[':date_to'] = $filters['date_to'];
            }

            if (!empty($filters['supplier_id'])) {
                $where[] = "sr.supplier_id = :supplier_id";
                $params[':supplier_id'] = (int)$filters['supplier_id'];
            }

            $whereSql = implode(' AND ', $where);

            $rows = Database::fetchAll("
                SELECT 
                    sr.id,
                    sr.return_number,
                    sr.return_date,
                    b.name AS branch_name,
                    COALESCE(sup.name, 'Supplier') AS supplier_name,
                    COALESCE(sup.supplier_code, '') AS supplier_code,
                    COALESCE(sup.name, 'Supplier') AS party_name,
                    'Supplier Return' AS return_type,
                    COALESCE(g.grn_number, '-') AS grn_number,
                    sr.total_return_amount,
                    sr.total_return_amount AS amount,
                    sr.applied_to_payable,
                    sr.credit_amount,
                    COALESCE(sr.notes, '') AS notes,
                    COALESCE(u.full_name, 'System') AS created_by_name,
                    'Completed' AS status
                FROM supplier_returns sr
                JOIN branches b ON sr.branch_id = b.id
                LEFT JOIN suppliers sup ON sr.supplier_id = sup.id
                LEFT JOIN grns g ON sr.grn_id = g.id
                LEFT JOIN users u ON sr.created_by = u.id
                WHERE $whereSql
                ORDER BY sr.return_date DESC, sr.id DESC
                LIMIT 500
            ", $params);

            $isSpecial = !empty($filters['special_user']) || MaskingHelper::isSpecialUser();
            if ($isSpecial) {
                foreach ($rows as &$r) {
                    $r['total_return_amount'] = MaskingHelper::maskValue((float)$r['total_return_amount'], true);
                    $r['amount']              = MaskingHelper::maskValue((float)$r['amount'], true);
                    $r['applied_to_payable']  = MaskingHelper::maskValue((float)$r['applied_to_payable'], true);
                    $r['credit_amount']       = MaskingHelper::maskValue((float)$r['credit_amount'], true);
                }
            }

            $totalReturnAmount = 0.00;
            $totalPayableOffset = 0.00;
            $totalCreditAmount  = 0.00;
            foreach ($rows as $r) {
                $totalReturnAmount += (float)$r['total_return_amount'];
                $totalPayableOffset += (float)$r['applied_to_payable'];
                $totalCreditAmount  += (float)$r['credit_amount'];
            }

            return [
                'items' => $rows,
                'summary' => [
                    'count'                => count($rows),
                    'total_return_amount'  => round($totalReturnAmount, 2),
                    'total_payable_offset' => round($totalPayableOffset, 2),
                    'total_credit_amount'  => round($totalCreditAmount, 2),
                ]
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to fetch supplier returns report: " . $e->getMessage(), $e);
            return ['items' => [], 'summary' => []];
        }
    }

    public static function getStockAdjustmentsReport(array $filters): array
    {
        try {
            $where = ["p.deleted_at IS NULL"];
            $params = [];

            if (isset($filters['branch_id']) && $filters['branch_id'] !== '' && $filters['branch_id'] !== 'all') {
                $where[] = "sa.branch_id = :branch_id";
                $params[':branch_id'] = (int)$filters['branch_id'];
            }

            if (!empty($filters['date_from'])) {
                $where[] = "DATE(sa.created_at) >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }

            if (!empty($filters['date_to'])) {
                $where[] = "DATE(sa.created_at) <= :date_to";
                $params[':date_to'] = $filters['date_to'];
            }

            if (!empty($filters['product_id'])) {
                $where[] = "sa.product_id = :product_id";
                $params[':product_id'] = (int)$filters['product_id'];
            }

            if (!empty($filters['adjustment_type']) && in_array($filters['adjustment_type'], ['increase', 'decrease'])) {
                $where[] = "sa.adjustment_type = :adj_type";
                $params[':adj_type'] = $filters['adjustment_type'];
            }

            $whereSql = implode(' AND ', $where);

            $rows = Database::fetchAll("
                SELECT 
                    sa.id,
                    sa.adjustment_number,
                    DATE(sa.created_at) AS adjustment_date,
                    b.name AS branch_name,
                    p.name AS product_name,
                    p.sku,
                    COALESCE(pl.lot_number, '-') AS lot_number,
                    sa.adjustment_type,
                    sa.quantity,
                    sa.cost AS unit_cost,
                    ROUND(sa.quantity * sa.cost, 2) AS total_value,
                    sa.reason,
                    COALESCE(sa.notes, '') AS notes,
                    COALESCE(u.full_name, 'System') AS adjusted_by
                FROM stock_adjustments sa
                JOIN branches b ON sa.branch_id = b.id
                JOIN products p ON sa.product_id = p.id
                LEFT JOIN product_lots pl ON sa.lot_id = pl.id
                LEFT JOIN users u ON sa.created_by = u.id
                WHERE $whereSql
                ORDER BY sa.created_at DESC, sa.id DESC
                LIMIT 500
            ", $params);

            $isSpecial = !empty($filters['special_user']) || MaskingHelper::isSpecialUser();
            if ($isSpecial) {
                foreach ($rows as &$r) {
                    $r['unit_cost']   = MaskingHelper::maskValue((float)$r['unit_cost'], true);
                    $r['total_value'] = MaskingHelper::maskValue((float)$r['total_value'], true);
                }
            }

            $totalIncreasesQty   = 0.00;
            $totalDecreasesQty   = 0.00;
            $totalNetQtyChange   = 0.00;
            $totalNetValueChange = 0.00;

            foreach ($rows as $r) {
                $q = (float)$r['quantity'];
                $v = (float)$r['total_value'];
                if ($r['adjustment_type'] === 'increase') {
                    $totalIncreasesQty += $q;
                    $totalNetQtyChange += $q;
                    $totalNetValueChange += $v;
                } else {
                    $totalDecreasesQty += $q;
                    $totalNetQtyChange -= $q;
                    $totalNetValueChange -= $v;
                }
            }

            return [
                'items' => $rows,
                'summary' => [
                    'count'                  => count($rows),
                    'total_increases_qty'    => round($totalIncreasesQty, 2),
                    'total_decreases_qty'    => round($totalDecreasesQty, 2),
                    'total_net_qty_change'   => round($totalNetQtyChange, 2),
                    'total_net_value_change' => round($totalNetValueChange, 2),
                ]
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to fetch stock adjustments report: " . $e->getMessage(), $e);
            return ['items' => [], 'summary' => []];
        }
    }

    public static function getReturnsAdjustmentsReport(array $filters): array
    {
        return self::getSupplierReturnsReport($filters);
    }

    /**
     * Centralized Expense Register Report
     */
    public static function getExpenseRegisterReport(array $filters): array
    {
        try {
            $where = ["e.deleted_at IS NULL", "e.status != 'reversed'"];
            $params = [];

            if (isset($filters['branch_id']) && $filters['branch_id'] !== '' && $filters['branch_id'] !== 'all') {
                $where[] = "e.branch_id = :branch_id";
                $params[':branch_id'] = (int)$filters['branch_id'];
            }
            if (!empty($filters['date_from'])) {
                $where[] = "e.expense_date >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }
            if (!empty($filters['date_to'])) {
                $where[] = "e.expense_date <= :date_to";
                $params[':date_to'] = $filters['date_to'];
            }
            if (!empty($filters['category_id'])) {
                $where[] = "e.category_id = :category_id";
                $params[':category_id'] = (int)$filters['category_id'];
            }
            if (!empty($filters['payment_method'])) {
                $where[] = "e.payment_method = :payment_method";
                $params[':payment_method'] = $filters['payment_method'];
            }

            $whereSql = implode(' AND ', $where);

            $rows = Database::fetchAll("
                SELECT 
                    e.id,
                    e.expense_no,
                    e.expense_date,
                    b.name AS branch_name,
                    COALESCE(ec.name, 'General Expense') AS category_name,
                    coa.account_code,
                    coa.account_name,
                    e.payee,
                    e.description,
                    e.payment_method,
                    ba.bank_name,
                    e.amount,
                    e.paid_amount,
                    (e.amount - e.paid_amount) AS outstanding_amount,
                    e.status,
                    COALESCE(u.full_name, 'System') AS created_by_name,
                    je.entry_number AS journal_entry_number
                FROM expenses e
                LEFT JOIN expense_categories ec ON e.category_id = ec.id
                LEFT JOIN chart_of_accounts coa ON e.account_id = coa.id
                LEFT JOIN bank_accounts ba ON e.bank_account_id = ba.id
                LEFT JOIN branches b ON e.branch_id = b.id
                LEFT JOIN users u ON e.created_by = u.id
                LEFT JOIN journal_entries je ON e.journal_entry_id = je.id
                WHERE $whereSql
                ORDER BY e.expense_date DESC, e.id DESC
            ", $params);

            $isSpecial = MaskingHelper::isSpecialUser();
            $totAmount = 0.00;
            $totDrawer = 0.00;
            $totBank   = 0.00;
            $totSafe   = 0.00;
            $totPetty  = 0.00;
            $totUnpaid = 0.00;

            foreach ($rows as &$r) {
                $amt = (float)$r['amount'];
                $paid = (float)$r['paid_amount'];
                $out = (float)$r['outstanding_amount'];

                $totAmount += $amt;
                if ($r['payment_method'] === 'cash_drawer') {
                    $totDrawer += $amt;
                } elseif ($r['payment_method'] === 'bank') {
                    $totBank += $amt;
                } elseif ($r['payment_method'] === 'cash_in_hand') {
                    $totSafe += $amt;
                } elseif ($r['payment_method'] === 'petty_cash') {
                    $totPetty += $amt;
                } elseif ($r['payment_method'] === 'unpaid') {
                    $totUnpaid += $out;
                }

                if ($isSpecial) {
                    $amt  = MaskingHelper::maskValue($amt, true);
                    $paid = MaskingHelper::maskValue($paid, true);
                    $out  = MaskingHelper::maskValue($out, true);
                }

                $r['amount']             = round($amt, 2);
                $r['paid_amount']        = round($paid, 2);
                $r['outstanding_amount'] = round($out, 2);
            }

            return [
                'items' => $rows,
                'summary' => [
                    'count'        => count($rows),
                    'total_amount' => round($isSpecial ? MaskingHelper::maskValue($totAmount, true) : $totAmount, 2),
                    'cash_drawer'  => round($isSpecial ? MaskingHelper::maskValue($totDrawer, true) : $totDrawer, 2),
                    'bank'         => round($isSpecial ? MaskingHelper::maskValue($totBank, true) : $totBank, 2),
                    'cash_in_hand' => round($isSpecial ? MaskingHelper::maskValue($totSafe, true) : $totSafe, 2),
                    'petty_cash'   => round($isSpecial ? MaskingHelper::maskValue($totPetty, true) : $totPetty, 2),
                    'unpaid'       => round($isSpecial ? MaskingHelper::maskValue($totUnpaid, true) : $totUnpaid, 2)
                ]
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to fetch expense register report: " . $e->getMessage(), $e);
            return ['items' => [], 'summary' => []];
        }
    }

    /**
     * Expense Summary By Category Report
     */
    public static function getExpensesByCategoryReport(array $filters): array
    {
        try {
            $where = ["e.deleted_at IS NULL", "e.status != 'reversed'"];
            $params = [];

            if (isset($filters['branch_id']) && $filters['branch_id'] !== '' && $filters['branch_id'] !== 'all') {
                $where[] = "e.branch_id = :branch_id";
                $params[':branch_id'] = (int)$filters['branch_id'];
            }
            if (!empty($filters['date_from'])) {
                $where[] = "e.expense_date >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }
            if (!empty($filters['date_to'])) {
                $where[] = "e.expense_date <= :date_to";
                $params[':date_to'] = $filters['date_to'];
            }

            $whereSql = implode(' AND ', $where);

            $rows = Database::fetchAll("
                SELECT 
                    ec.id AS category_id,
                    COALESCE(ec.name, 'General Expense') AS category_name,
                    coa.account_code,
                    coa.account_name,
                    COUNT(e.id) AS transaction_count,
                    COALESCE(SUM(e.amount), 0.00) AS total_amount,
                    COALESCE(SUM(CASE WHEN e.payment_method = 'cash_drawer' THEN e.amount ELSE 0 END), 0.00) AS cash_drawer_amount,
                    COALESCE(SUM(CASE WHEN e.payment_method = 'bank' THEN e.amount ELSE 0 END), 0.00) AS bank_amount,
                    COALESCE(SUM(CASE WHEN e.payment_method = 'cash_in_hand' THEN e.amount ELSE 0 END), 0.00) AS cash_in_hand_amount,
                    COALESCE(SUM(CASE WHEN e.payment_method = 'petty_cash' THEN e.amount ELSE 0 END), 0.00) AS petty_cash_amount,
                    COALESCE(SUM(CASE WHEN e.payment_method = 'unpaid' THEN (e.amount - e.paid_amount) ELSE 0 END), 0.00) AS unpaid_amount
                FROM expenses e
                LEFT JOIN expense_categories ec ON e.category_id = ec.id
                LEFT JOIN chart_of_accounts coa ON e.account_id = coa.id
                WHERE $whereSql
                GROUP BY ec.id, ec.name, coa.account_code, coa.account_name
                ORDER BY total_amount DESC
            ", $params);

            $isSpecial = MaskingHelper::isSpecialUser();
            $grandTotal = 0.00;
            foreach ($rows as $r) {
                $grandTotal += (float)$r['total_amount'];
            }

            foreach ($rows as &$r) {
                $amt = (float)$r['total_amount'];
                $r['percentage'] = $grandTotal > 0 ? round(($amt / $grandTotal) * 100, 2) : 0.00;

                if ($isSpecial) {
                    $amt = MaskingHelper::maskValue($amt, true);
                    $r['cash_drawer_amount']  = MaskingHelper::maskValue((float)$r['cash_drawer_amount'], true);
                    $r['bank_amount']         = MaskingHelper::maskValue((float)$r['bank_amount'], true);
                    $r['cash_in_hand_amount'] = MaskingHelper::maskValue((float)$r['cash_in_hand_amount'], true);
                    $r['petty_cash_amount']   = MaskingHelper::maskValue((float)$r['petty_cash_amount'], true);
                    $r['unpaid_amount']       = MaskingHelper::maskValue((float)$r['unpaid_amount'], true);
                }

                $r['total_amount'] = round($amt, 2);
            }

            return [
                'items' => $rows,
                'summary' => [
                    'category_count' => count($rows),
                    'grand_total'    => round($isSpecial ? MaskingHelper::maskValue($grandTotal, true) : $grandTotal, 2)
                ]
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to fetch expenses by category report: " . $e->getMessage(), $e);
            return ['items' => [], 'summary' => []];
        }
    }

    /**
     * Discount Usage Report (Section 18.1)
     */
    public static function getDiscountUsageReport(array $filters, int $page = 1, int $limit = 25): array
    {
        try {
            $page = max(1, $page);
            $limit = in_array($limit, [25, 50, 75, 100, 500]) ? $limit : 25;
            $offset = ($page - 1) * $limit;

            $where = ["s.status = 'completed'"];
            $params = [];

            if (!empty($filters['branch_id']) && $filters['branch_id'] !== 'all') {
                $where[] = "s.branch_id = :branch_id";
                $params[':branch_id'] = (int)$filters['branch_id'];
            }

            if (!empty($filters['date_from'])) {
                $where[] = "s.created_at >= :date_from";
                $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
            }

            if (!empty($filters['date_to'])) {
                $where[] = "s.created_at <= :date_to";
                $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
            }

            if (!empty($filters['search'])) {
                $st = '%' . trim($filters['search']) . '%';
                $where[] = "(
                    s.invoice_no LIKE :s1 OR
                    c.name LIKE :s2 OR
                    sd.discount_name LIKE :s3 OR
                    sd.coupon_code LIKE :s4 OR
                    u.full_name LIKE :s5 OR
                    b.name LIKE :s6 OR
                    CAST(sd.discount_amount AS CHAR) LIKE :s7 OR
                    CONCAT('LKR ', FORMAT(sd.discount_amount, 2)) LIKE :s8 OR
                    CONCAT('Rs. ', FORMAT(sd.discount_amount, 2)) LIKE :s9
                )";
                for ($i = 1; $i <= 9; $i++) {
                    $params[":s{$i}"] = $st;
                }
            }

            $whereSql = "WHERE " . implode(' AND ', $where);

            $countSql = "
                SELECT COUNT(*) 
                FROM sale_discounts sd
                JOIN sales s ON sd.sale_id = s.id
                LEFT JOIN customers c ON s.customer_id = c.id
                LEFT JOIN users u ON s.user_id = u.id
                LEFT JOIN branches b ON s.branch_id = b.id
                {$whereSql}
            ";
            $totalCount = (int)Database::fetchColumn($countSql, $params);

            $sql = "
                SELECT 
                    sd.id,
                    sd.sale_id,
                    s.invoice_no,
                    s.created_at AS sale_date,
                    b.name AS branch_name,
                    COALESCE(c.name, 'Walk-in Customer') AS customer_name,
                    sd.discount_name,
                    sd.rule_type,
                    sd.discount_mode,
                    sd.discount_rate,
                    sd.discount_amount,
                    sd.coupon_code,
                    u.full_name AS cashier_name,
                    p.name AS product_name
                FROM sale_discounts sd
                JOIN sales s ON sd.sale_id = s.id
                LEFT JOIN customers c ON s.customer_id = c.id
                LEFT JOIN users u ON s.user_id = u.id
                LEFT JOIN branches b ON s.branch_id = b.id
                LEFT JOIN sale_items si ON sd.sale_item_id = si.id
                LEFT JOIN products p ON si.product_id = p.id
                {$whereSql}
                ORDER BY s.created_at DESC, sd.id DESC
                LIMIT :limit OFFSET :offset
            ";

            $stmt = Database::getInstance()->getConnection()->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $totalSavings = Database::fetchColumn("
                SELECT COALESCE(SUM(sd.discount_amount), 0.00)
                FROM sale_discounts sd
                JOIN sales s ON sd.sale_id = s.id
                LEFT JOIN customers c ON s.customer_id = c.id
                LEFT JOIN users u ON s.user_id = u.id
                LEFT JOIN branches b ON s.branch_id = b.id
                {$whereSql}
            ", $params);

            return [
                'items'       => $items,
                'total_count' => $totalCount,
                'page'        => $page,
                'limit'       => $limit,
                'total_pages' => ceil($totalCount / $limit) ?: 1,
                'summary'     => [
                    'total_discounts_granted' => round((float)$totalSavings, 2),
                    'total_records'           => $totalCount
                ]
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to fetch discount usage report: " . $e->getMessage(), $e);
            return ['items' => [], 'total_count' => 0, 'page' => 1, 'limit' => 25, 'total_pages' => 1, 'summary' => []];
        }
    }

    /**
     * Promotion Campaign Performance Report (Section 18.2)
     */
    public static function getPromotionPerformanceReport(array $filters): array
    {
        try {
            $where = ["s.status = 'completed'"];
            $params = [];

            if (!empty($filters['branch_id']) && $filters['branch_id'] !== 'all') {
                $where[] = "s.branch_id = :branch_id";
                $params[':branch_id'] = (int)$filters['branch_id'];
            }
            if (!empty($filters['date_from'])) {
                $where[] = "s.created_at >= :date_from";
                $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
            }
            if (!empty($filters['date_to'])) {
                $where[] = "s.created_at <= :date_to";
                $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
            }

            $whereSql = "WHERE " . implode(' AND ', $where);

            $sql = "
                SELECT 
                    dr.id AS rule_id,
                    dr.rule_code,
                    dr.name AS rule_name,
                    dr.rule_type,
                    dr.discount_mode,
                    dr.discount_value,
                    COUNT(DISTINCT sd.id) AS redemptions_count,
                    COUNT(DISTINCT sd.sale_id) AS distinct_invoices_count,
                    COALESCE(SUM(sd.discount_amount), 0.00) AS total_discount_given,
                    COALESCE(SUM(s.total_amount), 0.00) AS sales_revenue_generated
                FROM discount_rules dr
                JOIN sale_discounts sd ON dr.id = sd.rule_id
                JOIN sales s ON sd.sale_id = s.id
                {$whereSql}
                GROUP BY dr.id, dr.rule_code, dr.name, dr.rule_type, dr.discount_mode, dr.discount_value
                ORDER BY total_discount_given DESC
            ";

            $rows = Database::fetchAll($sql, $params);
            $totalSavings = array_sum(array_column($rows, 'total_discount_given'));
            $totalRevenue = array_sum(array_column($rows, 'sales_revenue_generated'));
            $totalRedemptions = array_sum(array_column($rows, 'redemptions_count'));

            return [
                'items'   => $rows,
                'summary' => [
                    'active_rules_used' => count($rows),
                    'total_redemptions' => $totalRedemptions,
                    'total_savings'     => round($totalSavings, 2),
                    'total_revenue'     => round($totalRevenue, 2)
                ]
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to fetch promotion performance report: " . $e->getMessage(), $e);
            return ['items' => [], 'summary' => []];
        }
    }
}

