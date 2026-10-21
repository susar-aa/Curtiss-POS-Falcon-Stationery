<?php
/**
 * Curtiss POS - Product Expiry Date & Batch Management Engine
 * Handles FEFO tracking, batch expiry auditing, write-offs, and multi-branch expiry valuations.
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Audit.php';
require_once __DIR__ . '/AdvancedInventory.php';

class ExpiryManager
{
    /**
     * Get Expired & Near Expiry Batches List
     */
    public static function getExpiringLots(int $branchId, array $filters = [], int $page = 1, int $limit = 25): array
    {
        try {
            $params = [':branch_id' => $branchId];
            $where = [
                "pl.branch_id = :branch_id",
                "pl.remaining_quantity > 0",
                "pl.status = 'active'",
                "pl.expiry_date IS NOT NULL",
                "p.deleted_at IS NULL"
            ];

            $filterType = $filters['filter_type'] ?? 'all';
            if ($filterType === 'expired') {
                $where[] = "pl.expiry_date < CURRENT_DATE()";
            } elseif ($filterType === 'near_30' || $filterType === 'red') {
                $where[] = "pl.expiry_date <= DATE_ADD(CURRENT_DATE(), INTERVAL 30 DAY)";
            } elseif ($filterType === 'near_60' || $filterType === 'yellow') {
                $where[] = "pl.expiry_date > DATE_ADD(CURRENT_DATE(), INTERVAL 30 DAY) AND pl.expiry_date <= DATE_ADD(CURRENT_DATE(), INTERVAL 60 DAY)";
            } elseif ($filterType === 'near_90') {
                $where[] = "pl.expiry_date > DATE_ADD(CURRENT_DATE(), INTERVAL 60 DAY) AND pl.expiry_date <= DATE_ADD(CURRENT_DATE(), INTERVAL 90 DAY)";
            } elseif ($filterType === 'safe' || $filterType === 'green' || $filterType === 'others') {
                $where[] = "pl.expiry_date > DATE_ADD(CURRENT_DATE(), INTERVAL 60 DAY)";
            }

            if (!empty($filters['brand_id']) && (int)$filters['brand_id'] > 0) {
                $where[] = "p.brand_id = :brand_id";
                $params[':brand_id'] = (int)$filters['brand_id'];
            }

            if (!empty($filters['category_id']) && (int)$filters['category_id'] > 0) {
                $where[] = "p.category_id = :category_id";
                $params[':category_id'] = (int)$filters['category_id'];
            }

            if (!empty($filters['supplier_id']) && (int)$filters['supplier_id'] > 0) {
                $where[] = "p.supplier_id = :supplier_id";
                $params[':supplier_id'] = (int)$filters['supplier_id'];
            }

            if (!empty($filters['search'])) {
                $rawSearch = trim($filters['search']);
                $searchTerm = '%' . $rawSearch . '%';
                $where[] = "(
                    p.name LIKE :s1 OR p.sku LIKE :s2 OR p.barcode LIKE :s3 OR
                    pl.lot_number LIKE :s4 OR pl.batch_number LIKE :s5 OR
                    b.name LIKE :s6 OR c.name LIKE :s7 OR s.name LIKE :s8
                )";
                for ($i = 1; $i <= 8; $i++) {
                    $params[":s{$i}"] = $searchTerm;
                }
            }

            $whereSql = implode(' AND ', $where);

            // Total count
            $countRow = Database::fetchOne("
                SELECT COUNT(*) AS total 
                FROM product_lots pl
                JOIN products p ON pl.product_id = p.id
                LEFT JOIN brands b ON p.brand_id = b.id
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN suppliers s ON p.supplier_id = s.id
                WHERE $whereSql
            ", $params);

            $totalItems = (int)($countRow['total'] ?? 0);
            $limit = max(1, min(500, $limit));
            $offset = ($page - 1) * $limit;

            $items = Database::fetchAll("
                SELECT 
                    pl.id AS lot_id,
                    pl.lot_number,
                    pl.batch_number,
                    pl.product_id,
                    pl.cost,
                    pl.selling_price,
                    pl.remaining_quantity,
                    pl.initial_quantity,
                    pl.expiry_date,
                    pl.source_type,
                    pl.created_at,
                    DATEDIFF(pl.expiry_date, CURRENT_DATE()) AS days_remaining,
                    CASE 
                        WHEN pl.expiry_date <= DATE_ADD(CURRENT_DATE(), INTERVAL 30 DAY) THEN 'red'
                        WHEN pl.expiry_date <= DATE_ADD(CURRENT_DATE(), INTERVAL 60 DAY) THEN 'yellow'
                        ELSE 'green'
                    END AS color_tier,
                    CASE 
                        WHEN pl.expiry_date < CURRENT_DATE() THEN 'expired'
                        WHEN pl.expiry_date <= DATE_ADD(CURRENT_DATE(), INTERVAL 30 DAY) THEN 'near_30'
                        WHEN pl.expiry_date <= DATE_ADD(CURRENT_DATE(), INTERVAL 60 DAY) THEN 'near_60'
                        ELSE 'safe'
                    END AS expiry_stage,
                    ROUND(pl.remaining_quantity * pl.cost, 2) AS total_cost_value,
                    ROUND(pl.remaining_quantity * pl.selling_price, 2) AS total_retail_value,
                    p.name AS product_name,
                    p.sku,
                    p.barcode,
                    b.name AS brand_name,
                    c.name AS category_name,
                    s.name AS supplier_name,
                    COALESCE(br.name, 'Main Branch') AS branch_name
                FROM product_lots pl
                JOIN products p ON pl.product_id = p.id
                LEFT JOIN brands b ON p.brand_id = b.id
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN suppliers s ON p.supplier_id = s.id
                LEFT JOIN branches br ON pl.branch_id = br.id
                WHERE $whereSql
                ORDER BY pl.expiry_date ASC, pl.id ASC
                LIMIT $limit OFFSET $offset
            ", $params);

            $totQty = 0.0;
            $totLoss = 0.0;
            $countRed = 0;
            $countYellow = 0;
            $countGreen = 0;
            $valRed = 0.0;
            $valYellow = 0.0;
            $valGreen = 0.0;

            foreach ($items as &$it) {
                $rem = (float)$it['remaining_quantity'];
                $c = (float)$it['cost'];
                $daysRemaining = (int)$it['days_remaining'];
                $lineVal = round($rem * $c, 2);

                $it['current_quantity'] = $rem;
                $it['unit_cost'] = $c;
                $it['loss_value'] = $lineVal;
                $it['total_cost'] = $lineVal;
                $it['total_valuation'] = $lineVal;
                $it['days_expired'] = max(0, -$daysRemaining);
                $it['days_until_expiry'] = $daysRemaining;

                // 3-Color Expiry Categorization:
                // 0–30 days (and already expired) => RED (#dc2626)
                // 30–60 days => YELLOW (#d97706)
                // Others (>60 days) => GREEN (#16a34a)
                if ($daysRemaining <= 30) {
                    $it['color_tag']     = 'red';
                    $it['color_hex']     = '#dc2626';
                    $it['color_bg']      = '#fee2e2';
                    $it['color_text']    = '#b91c1c';
                    $it['color_border']  = '#fca5a5';
                    $it['urgency_level'] = 'danger'; // Red
                    $it['range_label']   = ($daysRemaining < 0) ? "Expired (" . abs($daysRemaining) . "d ago)" : "0–30 Days ({$daysRemaining}d left)";
                    $countRed++;
                    $valRed += $lineVal;
                } elseif ($daysRemaining <= 60) {
                    $it['color_tag']     = 'yellow';
                    $it['color_hex']     = '#d97706';
                    $it['color_bg']      = '#fef3c7';
                    $it['color_text']    = '#b45309';
                    $it['color_border']  = '#fde68a';
                    $it['urgency_level'] = 'warning'; // Yellow
                    $it['range_label']   = "30–60 Days ({$daysRemaining}d left)";
                    $countYellow++;
                    $valYellow += $lineVal;
                } else {
                    $it['color_tag']     = 'green';
                    $it['color_hex']     = '#16a34a';
                    $it['color_bg']      = '#dcfce7';
                    $it['color_text']    = '#15803d';
                    $it['color_border']  = '#86efac';
                    $it['urgency_level'] = 'safe'; // Green
                    $it['range_label']   = "60+ Days ({$daysRemaining}d left)";
                    $countGreen++;
                    $valGreen += $lineVal;
                }

                $totQty += $rem;
                $totLoss += $lineVal;
            }
            unset($it);

            $summary = [
                'total_items'         => count($items),
                'total_qty'           => $totQty,
                'total_loss_value'    => $totLoss,
                'total_at_risk_value' => $totLoss,
                'count_red'           => $countRed,
                'val_red'             => round($valRed, 2),
                'count_yellow'        => $countYellow,
                'val_yellow'          => round($valYellow, 2),
                'count_green'         => $countGreen,
                'val_green'           => round($valGreen, 2),
                'window_days'         => 30
            ];

            return [
                'items'      => $items,
                'summary'    => $summary,
                'totalItems' => $totalItems,
                'page'       => $page,
                'limit'      => $limit,
                'totalPages' => max(1, ceil($totalItems / $limit))
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to fetch expiring lots: " . $e->getMessage(), $e);
            return [
                'items'      => [],
                'summary'    => ['total_items' => 0, 'total_qty' => 0, 'total_loss_value' => 0, 'total_at_risk_value' => 0, 'window_days' => 30],
                'totalItems' => 0,
                'page'       => 1,
                'limit'      => $limit,
                'totalPages' => 1
            ];
        }
    }

    /**
     * Get Expiry Dashboard Summary KPI Metrics
     */
    public static function getExpirySummary(?int $branchId = null): array
    {
        try {
            $where = [
                "pl.remaining_quantity > 0",
                "pl.status = 'active'",
                "pl.expiry_date IS NOT NULL",
                "p.deleted_at IS NULL"
            ];
            $params = [];
            if ($branchId !== null && $branchId > 0) {
                $where[] = "pl.branch_id = :branch_id";
                $params[':branch_id'] = $branchId;
            }
            $whereSql = implode(' AND ', $where);

            $summary = Database::fetchOne("
                SELECT 
                    COUNT(CASE WHEN pl.expiry_date < CURRENT_DATE() THEN 1 END) AS expired_lots_count,
                    COALESCE(SUM(CASE WHEN pl.expiry_date < CURRENT_DATE() THEN pl.remaining_quantity ELSE 0 END), 0) AS expired_units_count,
                    COALESCE(SUM(CASE WHEN pl.expiry_date < CURRENT_DATE() THEN pl.remaining_quantity * pl.cost ELSE 0 END), 0) AS expired_cost_valuation,
                    COUNT(CASE WHEN pl.expiry_date >= CURRENT_DATE() AND pl.expiry_date <= DATE_ADD(CURRENT_DATE(), INTERVAL 30 DAY) THEN 1 END) AS near_30_lots_count,
                    COALESCE(SUM(CASE WHEN pl.expiry_date >= CURRENT_DATE() AND pl.expiry_date <= DATE_ADD(CURRENT_DATE(), INTERVAL 30 DAY) THEN pl.remaining_quantity ELSE 0 END), 0) AS near_30_units_count,
                    COALESCE(SUM(CASE WHEN pl.expiry_date >= CURRENT_DATE() AND pl.expiry_date <= DATE_ADD(CURRENT_DATE(), INTERVAL 30 DAY) THEN pl.remaining_quantity * pl.cost ELSE 0 END), 0) AS near_30_cost_valuation,
                    -- Red Tier: 0–30 Days & Expired
                    COUNT(CASE WHEN pl.expiry_date <= DATE_ADD(CURRENT_DATE(), INTERVAL 30 DAY) THEN 1 END) AS red_lots_count,
                    COALESCE(SUM(CASE WHEN pl.expiry_date <= DATE_ADD(CURRENT_DATE(), INTERVAL 30 DAY) THEN pl.remaining_quantity ELSE 0 END), 0) AS red_units_count,
                    COALESCE(SUM(CASE WHEN pl.expiry_date <= DATE_ADD(CURRENT_DATE(), INTERVAL 30 DAY) THEN pl.remaining_quantity * pl.cost ELSE 0 END), 0) AS red_cost_valuation,
                    -- Yellow Tier: 30–60 Days
                    COUNT(CASE WHEN pl.expiry_date > DATE_ADD(CURRENT_DATE(), INTERVAL 30 DAY) AND pl.expiry_date <= DATE_ADD(CURRENT_DATE(), INTERVAL 60 DAY) THEN 1 END) AS near_60_lots_count,
                    COALESCE(SUM(CASE WHEN pl.expiry_date > DATE_ADD(CURRENT_DATE(), INTERVAL 30 DAY) AND pl.expiry_date <= DATE_ADD(CURRENT_DATE(), INTERVAL 60 DAY) THEN pl.remaining_quantity ELSE 0 END), 0) AS near_60_units_count,
                    COALESCE(SUM(CASE WHEN pl.expiry_date > DATE_ADD(CURRENT_DATE(), INTERVAL 30 DAY) AND pl.expiry_date <= DATE_ADD(CURRENT_DATE(), INTERVAL 60 DAY) THEN pl.remaining_quantity * pl.cost ELSE 0 END), 0) AS near_60_cost_valuation,
                    -- Green Tier: Others (> 60 Days)
                    COUNT(CASE WHEN pl.expiry_date > DATE_ADD(CURRENT_DATE(), INTERVAL 60 DAY) THEN 1 END) AS green_lots_count,
                    COALESCE(SUM(CASE WHEN pl.expiry_date > DATE_ADD(CURRENT_DATE(), INTERVAL 60 DAY) THEN pl.remaining_quantity ELSE 0 END), 0) AS green_units_count,
                    COALESCE(SUM(CASE WHEN pl.expiry_date > DATE_ADD(CURRENT_DATE(), INTERVAL 60 DAY) THEN pl.remaining_quantity * pl.cost ELSE 0 END), 0) AS green_cost_valuation,
                    COUNT(CASE WHEN pl.expiry_date > DATE_ADD(CURRENT_DATE(), INTERVAL 60 DAY) AND pl.expiry_date <= DATE_ADD(CURRENT_DATE(), INTERVAL 90 DAY) THEN 1 END) AS near_90_lots_count
                FROM product_lots pl
                JOIN products p ON pl.product_id = p.id
                WHERE $whereSql
            ", $params);

            return [
                'expired_lots_count'      => (int)($summary['expired_lots_count'] ?? 0),
                'expired_count'           => (int)($summary['expired_lots_count'] ?? 0),
                'expired_units_count'     => (float)($summary['expired_units_count'] ?? 0),
                'expired_quantity'        => (float)($summary['expired_units_count'] ?? 0),
                'expired_cost_valuation'  => (float)($summary['expired_cost_valuation'] ?? 0),
                'expired_loss_value'      => (float)($summary['expired_cost_valuation'] ?? 0),
                'near_30_lots_count'      => (int)($summary['near_30_lots_count'] ?? 0),
                'near_expiry_count'       => (int)($summary['near_30_lots_count'] ?? 0),
                'near_30_units_count'     => (float)($summary['near_30_units_count'] ?? 0),
                'near_expiry_quantity'    => (float)($summary['near_30_units_count'] ?? 0),
                'near_30_cost_valuation'  => (float)($summary['near_30_cost_valuation'] ?? 0),
                'near_expiry_value'       => (float)($summary['near_30_cost_valuation'] ?? 0),
                'red_lots_count'          => (int)($summary['red_lots_count'] ?? 0),
                'red_units_count'         => (float)($summary['red_units_count'] ?? 0),
                'red_cost_valuation'      => (float)($summary['red_cost_valuation'] ?? 0),
                'near_60_lots_count'      => (int)($summary['near_60_lots_count'] ?? 0),
                'near_60_units_count'     => (float)($summary['near_60_units_count'] ?? 0),
                'near_60_cost_valuation'  => (float)($summary['near_60_cost_valuation'] ?? 0),
                'yellow_lots_count'       => (int)($summary['near_60_lots_count'] ?? 0),
                'yellow_units_count'      => (float)($summary['near_60_units_count'] ?? 0),
                'yellow_cost_valuation'   => (float)($summary['near_60_cost_valuation'] ?? 0),
                'green_lots_count'        => (int)($summary['green_lots_count'] ?? 0),
                'green_units_count'       => (float)($summary['green_units_count'] ?? 0),
                'green_cost_valuation'    => (float)($summary['green_cost_valuation'] ?? 0),
                'near_90_lots_count'      => (int)($summary['near_90_lots_count'] ?? 0),
                'alert_days_soon'         => 30
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to fetch expiry summary: " . $e->getMessage(), $e);
            return [
                'expired_lots_count'      => 0,
                'expired_count'           => 0,
                'expired_units_count'     => 0,
                'expired_quantity'        => 0,
                'expired_cost_valuation'  => 0,
                'expired_loss_value'      => 0,
                'near_30_lots_count'      => 0,
                'near_expiry_count'       => 0,
                'near_30_units_count'     => 0,
                'near_expiry_quantity'    => 0,
                'near_30_cost_valuation'  => 0,
                'near_expiry_value'       => 0,
                'near_60_lots_count'      => 0,
                'near_90_lots_count'      => 0,
                'alert_days_soon'         => 30
            ];
        }
    }

    /**
     * Write-off an Expired Lot (Atomic Stock Removal, Damaged Log & Accounting Journal Dr 1250 Cr 1200)
     */
    public static function writeOffLot(int $lotId, int $branchId, float $quantity, string $reason, int $userId): array
    {
        $lot = Database::fetchOne("
            SELECT pl.*, p.name AS product_name 
            FROM product_lots pl
            JOIN products p ON pl.product_id = p.id
            WHERE pl.id = :id AND pl.branch_id = :branch_id
        ", [':id' => $lotId, ':branch_id' => $branchId]);

        if (!$lot) {
            return ['success' => false, 'message' => 'Stock lot not found in this branch.'];
        }

        if ((float)$lot['remaining_quantity'] < $quantity || $quantity <= 0) {
            return ['success' => false, 'message' => "Invalid write-off quantity. Available in lot: {$lot['remaining_quantity']}."];
        }

        $formattedReason = "Expired Stock Write-Off: " . ($reason ?: "Batch {$lot['batch_number']} expired on {$lot['expiry_date']}");

        $recordData = [
            'branch_id'  => $branchId,
            'product_id' => (int)$lot['product_id'],
            'lot_id'     => $lotId,
            'quantity'   => $quantity,
            'stock_type' => 'expired',
            'reason'     => $formattedReason
        ];

        return AdvancedInventory::recordDamagedStock($recordData, $userId);
    }

    /**
     * Batch Write-off Multiple Expired Stock Lots
     */
    public static function batchWriteOff(int $branchId, array $lotIds, string $reason, int $userId): array
    {
        if (empty($lotIds)) {
            return ['success' => false, 'message' => 'No batches selected for write-off.'];
        }

        $successCount = 0;
        $errors = [];

        foreach ($lotIds as $id) {
            $lotId = (int)$id;
            if ($lotId <= 0) continue;

            $lot = Database::fetchOne("SELECT remaining_quantity, expiry_date FROM product_lots WHERE id = :id AND branch_id = :b", [':id' => $lotId, ':b' => $branchId]);
            if (!$lot || (float)$lot['remaining_quantity'] <= 0) {
                continue;
            }

            $remQty = (float)$lot['remaining_quantity'];
            $res = self::writeOffLot($lotId, $branchId, $remQty, $reason, $userId);
            if ($res['success']) {
                $successCount++;
            } else {
                $errors[] = "Lot #$lotId: " . ($res['message'] ?? 'Error');
            }
        }

        return [
            'success'      => $successCount > 0,
            'successCount' => $successCount,
            'errors'       => $errors,
            'message'      => "Successfully written off $successCount expired lot(s)." . (!empty($errors) ? " " . implode("; ", $errors) : "")
        ];
    }

    /**
     * 1. Expired Stock Report (Batches already past expiry date with remaining stock)
     */
    public static function getExpiredStockReport(int $branchId, array $filters = [], int $page = 1, int $limit = 25): array
    {
        $filters['filter_type'] = 'expired';
        return self::getExpiringLots($branchId, $filters, $page, $limit);
    }

    /**
     * 2. Near Expiry Report (Batches expiring within N days: 30, 60, or 90)
     */
    public static function getNearExpiryReport(int $branchId, int $days = 0, array $filters = [], int $page = 1, int $limit = 25): array
    {
        if (!empty($filters['filter_type'])) {
            // caller explicitly set filter_type
        } elseif ($days === 30) {
            $filters['filter_type'] = 'near_30';
        } elseif ($days === 60) {
            $filters['filter_type'] = 'near_60';
        } elseif ($days === 90) {
            $filters['filter_type'] = 'near_90';
        } else {
            $filters['filter_type'] = 'all';
        }
        return self::getExpiringLots($branchId, $filters, $page, $limit);
    }

    /**
     * 3. Batch Tracking / Lot Expiry Audit Report (All batches with expiry status)
     */
    public static function getBatchExpiryAuditReport(int $branchId, array $filters = [], int $page = 1, int $limit = 25): array
    {
        try {
            $params = [];
            $where = [
                "pl.expiry_date IS NOT NULL",
                "p.deleted_at IS NULL"
            ];

            if ($branchId > 0) {
                $where[] = "pl.branch_id = :branch_id";
                $params[':branch_id'] = $branchId;
            }

            if (!empty($filters['search'])) {
                $searchTerm = '%' . trim($filters['search']) . '%';
                $where[] = "(p.name LIKE :s1 OR p.sku LIKE :s2 OR pl.lot_number LIKE :s3 OR pl.batch_number LIKE :s4)";
                for ($i = 1; $i <= 4; $i++) {
                    $params[":s{$i}"] = $searchTerm;
                }
            }

            $whereSql = implode(' AND ', $where);

            $countRow = Database::fetchOne("
                SELECT COUNT(*) AS total 
                FROM product_lots pl
                JOIN products p ON pl.product_id = p.id
                WHERE $whereSql
            ", $params);

            $totalItems = (int)($countRow['total'] ?? 0);
            $limit = max(1, min(500, $limit));
            $offset = ($page - 1) * $limit;

            $items = Database::fetchAll("
                SELECT 
                    pl.id AS lot_id,
                    pl.lot_number,
                    pl.batch_number,
                    pl.product_id,
                    pl.cost,
                    pl.selling_price,
                    pl.initial_quantity,
                    pl.remaining_quantity,
                    pl.expiry_date,
                    pl.status AS lot_status,
                    pl.source_type,
                    pl.created_at,
                    DATEDIFF(pl.expiry_date, CURRENT_DATE()) AS days_remaining,
                    CASE 
                        WHEN pl.remaining_quantity <= 0 THEN 'Depleted'
                        WHEN pl.expiry_date < CURRENT_DATE() THEN 'Expired'
                        WHEN pl.expiry_date <= DATE_ADD(CURRENT_DATE(), INTERVAL 30 DAY) THEN 'Expiring Soon (30d)'
                        ELSE 'Healthy'
                    END AS audit_status,
                    p.name AS product_name,
                    p.sku,
                    p.barcode,
                    b.name AS brand_name,
                    c.name AS category_name,
                    COALESCE(br.name, 'Main Branch') AS branch_name
                FROM product_lots pl
                JOIN products p ON pl.product_id = p.id
                LEFT JOIN brands b ON p.brand_id = b.id
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN branches br ON pl.branch_id = br.id
                WHERE $whereSql
                ORDER BY pl.expiry_date ASC, pl.id ASC
                LIMIT $limit OFFSET $offset
            ", $params);

            $totQty = 0.0;
            $totVal = 0.0;
            foreach ($items as &$it) {
                $rem = (float)$it['remaining_quantity'];
                $c = (float)$it['cost'];
                $daysRemaining = (int)$it['days_remaining'];
                $lineVal = round($rem * $c, 2);

                $it['current_quantity'] = $rem;
                $it['unit_cost'] = $c;
                $it['total_valuation'] = $lineVal;

                // 3-Color Expiry Categorization
                if ($rem <= 0) {
                    $it['color_tag']    = 'gray';
                    $it['color_hex']    = '#64748b';
                    $it['color_bg']     = '#f1f5f9';
                    $it['color_text']   = '#475569';
                    $it['color_border'] = '#cbd5e1';
                    $it['status_class'] = 'closed';
                    $it['status_label'] = 'Depleted';
                } elseif ($daysRemaining <= 30) {
                    $it['color_tag']    = 'red';
                    $it['color_hex']    = '#dc2626';
                    $it['color_bg']     = '#fee2e2';
                    $it['color_text']   = '#b91c1c';
                    $it['color_border'] = '#fca5a5';
                    $it['status_class'] = 'danger';
                    $it['status_label'] = ($daysRemaining < 0) ? 'Expired (' . abs($daysRemaining) . 'd ago)' : '0–30 Days (' . $daysRemaining . 'd left)';
                } elseif ($daysRemaining <= 60) {
                    $it['color_tag']    = 'yellow';
                    $it['color_hex']    = '#d97706';
                    $it['color_bg']     = '#fef3c7';
                    $it['color_text']   = '#b45309';
                    $it['color_border'] = '#fde68a';
                    $it['status_class'] = 'warning';
                    $it['status_label'] = '30–60 Days (' . $daysRemaining . 'd left)';
                } else {
                    $it['color_tag']    = 'green';
                    $it['color_hex']    = '#16a34a';
                    $it['color_bg']     = '#dcfce7';
                    $it['color_text']   = '#15803d';
                    $it['color_border'] = '#86efac';
                    $it['status_class'] = 'safe';
                    $it['status_label'] = '60+ Days (' . $daysRemaining . 'd left)';
                }

                $totQty += $rem;
                $totVal += $lineVal;
            }
            unset($it);

            $summary = [
                'total_lots'      => count($items),
                'total_qty'       => $totQty,
                'total_valuation' => $totVal
            ];

            return [
                'items'      => $items,
                'summary'    => $summary,
                'totalItems' => $totalItems,
                'page'       => $page,
                'limit'      => $limit,
                'totalPages' => max(1, ceil($totalItems / $limit))
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to fetch batch audit report: " . $e->getMessage(), $e);
            return [
                'items'      => [],
                'summary'    => ['total_lots' => 0, 'total_qty' => 0, 'total_valuation' => 0],
                'totalItems' => 0,
                'page'       => 1,
                'limit'      => $limit,
                'totalPages' => 1
            ];
        }
    }

    /**
     * 4. Expired Stock Write-off History Report (Historical write-offs with loss amounts)
     */
    public static function getExpiredWriteOffHistoryReport(int $branchId, array $filters = [], int $page = 1, int $limit = 25): array
    {
        try {
            $params = [':branch_id' => $branchId];
            $where = [
                "ds.branch_id = :branch_id",
                "ds.stock_type = 'expired'"
            ];

            if (!empty($filters['date_from'])) {
                $where[] = "DATE(ds.created_at) >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }
            if (!empty($filters['date_to'])) {
                $where[] = "DATE(ds.created_at) <= :date_to";
                $params[':date_to'] = $filters['date_to'];
            }

            if (!empty($filters['search'])) {
                $searchTerm = '%' . trim($filters['search']) . '%';
                $where[] = "(p.name LIKE :s1 OR p.sku LIKE :s2 OR ds.reason LIKE :s3 OR pl.lot_number LIKE :s4 OR pl.batch_number LIKE :s5)";
                for ($i = 1; $i <= 5; $i++) {
                    $params[":s{$i}"] = $searchTerm;
                }
            }

            $whereSql = implode(' AND ', $where);

            $countRow = Database::fetchOne("
                SELECT COUNT(*) AS total 
                FROM damaged_stock ds
                JOIN products p ON ds.product_id = p.id
                LEFT JOIN product_lots pl ON ds.lot_id = pl.id
                WHERE $whereSql
            ", $params);

            $totalItems = (int)($countRow['total'] ?? 0);
            $limit = max(1, min(500, $limit));
            $offset = ($page - 1) * $limit;

            $items = Database::fetchAll("
                SELECT 
                    ds.id AS writeoff_id,
                    ds.product_id,
                    ds.lot_id,
                    ds.quantity,
                    ds.cost,
                    ROUND(ds.quantity * ds.cost, 2) AS total_loss_amount,
                    ds.reason,
                    ds.created_at AS writeoff_date,
                    p.name AS product_name,
                    p.sku,
                    pl.lot_number,
                    pl.batch_number,
                    pl.expiry_date,
                    u.full_name AS logged_by_name,
                    je.entry_number AS journal_number
                FROM damaged_stock ds
                JOIN products p ON ds.product_id = p.id
                LEFT JOIN product_lots pl ON ds.lot_id = pl.id
                LEFT JOIN users u ON ds.logged_by = u.id
                LEFT JOIN journal_entries je ON ds.journal_entry_id = je.id
                WHERE $whereSql
                ORDER BY ds.created_at DESC, ds.id DESC
                LIMIT $limit OFFSET $offset
            ", $params);

            $totQty = 0.0;
            $totLoss = 0.0;
            foreach ($items as &$it) {
                $q = (float)$it['quantity'];
                $c = (float)$it['cost'];
                $it['unit_cost'] = $c;
                $it['total_loss'] = round($q * $c, 2);
                $it['user_name'] = $it['logged_by_name'];
                $it['branch_name'] = 'Main Branch - Kandy';
                $totQty += $q;
                $totLoss += round($q * $c, 2);
            }
            unset($it);

            $summary = [
                'total_records'    => count($items),
                'total_qty'        => $totQty,
                'total_loss_value' => $totLoss
            ];

            return [
                'items'      => $items,
                'summary'    => $summary,
                'totalItems' => $totalItems,
                'page'       => $page,
                'limit'      => $limit,
                'totalPages' => max(1, ceil($totalItems / $limit))
            ];
        } catch (Throwable $e) {
            Logger::error("Failed to fetch write-off history: " . $e->getMessage(), $e);
            return [
                'items'      => [],
                'summary'    => ['total_records' => 0, 'total_qty' => 0, 'total_loss_value' => 0],
                'totalItems' => 0,
                'page'       => 1,
                'limit'      => $limit,
                'totalPages' => 1
            ];
        }
    }

    /**
     * 5. Supplier Expiry Performance Report (Suppliers with highest expired stock rate)
     */
    public static function getSupplierExpiryPerformanceReport(int $branchId): array
    {
        try {
            return Database::fetchAll("
                SELECT 
                    s.id AS supplier_id,
                    s.name AS supplier_name,
                    s.supplier_code,
                    COUNT(DISTINCT pl.id) AS total_tracked_lots,
                    COUNT(DISTINCT CASE WHEN pl.expiry_date < CURRENT_DATE() AND pl.remaining_quantity > 0 THEN pl.id END) AS currently_expired_lots,
                    COALESCE(SUM(CASE WHEN pl.expiry_date < CURRENT_DATE() AND pl.remaining_quantity > 0 THEN pl.remaining_quantity ELSE 0 END), 0) AS currently_expired_units,
                    COALESCE(SUM(CASE WHEN pl.expiry_date < CURRENT_DATE() AND pl.remaining_quantity > 0 THEN pl.remaining_quantity * pl.cost ELSE 0 END), 0) AS active_expired_loss,
                    COALESCE(SUM(ds.quantity), 0) AS total_historical_writeoff_units,
                    COALESCE(SUM(ds.quantity * ds.cost), 0) AS total_historical_writeoff_loss
                FROM suppliers s
                JOIN products p ON s.id = p.supplier_id AND p.deleted_at IS NULL
                LEFT JOIN product_lots pl ON p.id = pl.product_id AND pl.branch_id = :branch_id AND pl.expiry_date IS NOT NULL
                LEFT JOIN damaged_stock ds ON p.id = ds.product_id AND ds.branch_id = :branch_id2 AND ds.stock_type = 'expired'
                WHERE s.deleted_at IS NULL
                GROUP BY s.id, s.name, s.supplier_code
                HAVING currently_expired_lots > 0 OR total_historical_writeoff_units > 0
                ORDER BY (active_expired_loss + total_historical_writeoff_loss) DESC
            ", [':branch_id' => $branchId, ':branch_id2' => $branchId]);

            foreach ($rows as &$r) {
                $r['total_lots_received'] = (int)$r['total_tracked_lots'];
                $r['total_qty_received'] = (float)$r['currently_expired_units'] + (float)$r['total_historical_writeoff_units'];
                $r['active_qty'] = 0;
                $r['expired_lots_count'] = (int)$r['currently_expired_lots'];
                $r['expired_qty'] = (float)$r['currently_expired_units'] + (float)$r['total_historical_writeoff_units'];
                $r['total_loss_value'] = (float)$r['active_expired_loss'] + (float)$r['total_historical_writeoff_loss'];
                $r['expiry_rate_pct'] = ($r['total_qty_received'] > 0) ? round(($r['expired_qty'] / $r['total_qty_received']) * 100, 2) : 0.0;
                $r['phone'] = '';
            }
            unset($r);

            return $rows;
        } catch (Throwable $e) {
            Logger::error("Failed to fetch supplier expiry performance: " . $e->getMessage(), $e);
            return [];
        }
    }
}
