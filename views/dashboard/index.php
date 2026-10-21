<?php
/**
 * Curtiss POS — Premium Real-Time Dashboard View
 * Hero fullscreen analytics chart + scroll-over frosted-glass panels
 */

// Inject dashboard stylesheet & set body class
$GLOBALS['__dashboardPage'] = true;
?>
<link rel="stylesheet" href="assets/css/dashboard.css">
<script>document.body.classList.add('dashboard-page');</script>

<?php
// ── Resolve data from controller ─────────────────────────────────────────
$branchIdCtx     = (int)($_SESSION['branch_id'] ?? $_SESSION['active_branch_id'] ?? 1);
$isAll           = !empty($isAllBranches);
$currencySymbol  = 'LKR';

// Pull pre-loaded analytics bundles from dashboard.php controller
$sales    = $analyticsBundle['sales_summary'] ?? [];
$growth   = $analyticsBundle['growth'] ?? [];
$timeSeries = $analyticsBundle['time_series'] ?? [];
$payments = $analyticsBundle['payment_methods'] ?? [];
$topProds = $analyticsBundle['top_products'] ?? [];
$slowProds = $analyticsBundle['slow_movers'] ?? [];
$cats     = $analyticsBundle['category_breakdown'] ?? [];
$brands   = $analyticsBundle['brand_breakdown'] ?? [];
$cashiers = $analyticsBundle['cashier_performance'] ?? [];
$branches = $analyticsBundle['branch_performance'] ?? [];
$custAna  = $analyticsBundle['customer_analytics'] ?? [];
$returns  = $analyticsBundle['returns'] ?? [];
$discounts = $analyticsBundle['discounts'] ?? [];
$hourly   = $analyticsBundle['hourly_trend'] ?? [];
$ops      = $operationsBundle ?? [];
$inventory= $ops['inventory'] ?? [];
$financial= $ops['financial'] ?? [];
$aging    = $inventory['aging'] ?? $inventory['stock_aging'] ?? [];
$lowStock = $inventory['low_stock_count'] ?? 0;
$outStock = $inventory['out_of_stock_count'] ?? 0;
$invValue = $inventory['total_inventory_value'] ?? 0;
$recentSales = $ops['recent_sales'] ?? [];

// ── Formatters ───────────────────────────────────────────────────────────
if (!function_exists('dbFmt')) {
    function dbFmt(float $v, int $dp = 2): string {
        return number_format($v, $dp);
    }
}
if (!function_exists('dbMoney')) {
    function dbMoney(float $v): string {
        return 'LKR ' . number_format($v, 2);
    }
}
if (!function_exists('dbGrowthClass')) {
    function dbGrowthClass(float $v): string {
        return $v > 0 ? 'up' : ($v < 0 ? 'down' : 'flat');
    }
}
if (!function_exists('dbGrowthIcon')) {
    function dbGrowthIcon(float $v): string {
        return $v > 0 ? '▲' : ($v < 0 ? '▼' : '—');
    }
}
if (!function_exists('dbGrowthText')) {
    function dbGrowthText(float $v): string {
        return ($v > 0 ? '+' : '') . number_format($v, 1) . '%';
    }
}

// ── Core KPIs ────────────────────────────────────────────────────────────
$grossSales  = (float)($sales['gross_sales'] ?? 0);
$netSales    = (float)($sales['net_sales'] ?? $sales['revenue'] ?? 0);
$cogs        = (float)($sales['cogs'] ?? 0);
$grossProfit = (float)($sales['gross_profit'] ?? 0);
$grossMargin = (float)($sales['gross_margin_pct'] ?? 0);
$opExpenses  = (float)($sales['operating_expenses'] ?? 0);
$netProfit   = (float)($sales['net_profit'] ?? 0);
$totalDisc   = (float)($sales['total_discounts'] ?? 0);
$totalReturns= (float)($sales['total_returns'] ?? $sales['returns_amount'] ?? 0);
$invoiceCount= (int)($sales['invoice_count'] ?? 0);
$itemsSold   = (int)($sales['items_sold'] ?? $sales['units_sold'] ?? 0);

// ── Growth deltas ─────────────────────────────────────────────────────────
$resolveGrowth = function($key, $growth) {
    if (isset($growth[$key . '_growth'])) return (float)$growth[$key . '_growth'];
    if (isset($growth[$key]['percentage'])) return (float)$growth[$key]['percentage'];
    if (isset($growth[$key]) && is_numeric($growth[$key])) return (float)$growth[$key];
    return 0.0;
};
$revenueGrowth  = $resolveGrowth('net_sales', $growth);
$profitGrowth   = $resolveGrowth('gross_profit', $growth);
$cogsGrowth     = $resolveGrowth('cogs', $growth);
$expenseGrowth  = $resolveGrowth('expenses', $growth) ?: $resolveGrowth('operating_expenses', $growth);

// ── Time-series for charts ────────────────────────────────────────────────
if (isset($timeSeries['labels']) && isset($timeSeries['series'])) {
    $tsLabels = $timeSeries['labels'];
    $tsSales  = $timeSeries['series']['sales'] ?? [];
    $tsCogs   = $timeSeries['series']['cogs'] ?? [];
    $tsProfit = $timeSeries['series']['gross_profit'] ?? [];
    $tsExpenses = $timeSeries['series']['expenses'] ?? [];
    $tsInvoices = $timeSeries['series']['invoices'] ?? [];
} else {
    $tsLabels = array_column($timeSeries, 'label');
    $tsSales  = array_column($timeSeries, 'net_sales');
    $tsCogs   = array_column($timeSeries, 'cogs');
    $tsProfit = array_column($timeSeries, 'gross_profit');
    $tsExpenses = array_column($timeSeries, 'expenses');
    $tsInvoices = array_column($timeSeries, 'invoice_count');
}

// ── Hourly trend ─────────────────────────────────────────────────────────
$hrLabels = [];
$hrSales  = [];
foreach ($hourly as $h) {
    $hrLabels[] = sprintf('%02d:00', (int)($h['hour'] ?? 0));
    $hrSales[]  = (float)($h['net_sales'] ?? $h['sales'] ?? 0);
}

// ── Payment method colours ────────────────────────────────────────────────
$pmColors = ['#0f172a','#3b82f6','#10b981','#f59e0b','#8b5cf6','#ef4444','#06b6d4','#f97316'];

// ── Category colours ─────────────────────────────────────────────────────
$catColors = ['#2563eb','#0d9488','#d97706','#dc2626','#7c3aed','#0891b2','#16a34a','#db2777'];
?>

<!-- ======================================================================
     HERO PANEL — Fullscreen sales analytics chart
     ====================================================================== -->
<div class="db-hero" id="dbHero">

    <!-- Controls bar -->
    <div class="db-hero-controls">
        <div class="db-period-bar" id="dbPeriodBar">
            <button class="db-period-btn active" data-preset="today">Today</button>
            <button class="db-period-btn" data-preset="week">Week</button>
            <button class="db-period-btn" data-preset="month">Month</button>
            <button class="db-period-btn" data-preset="quarter">Quarter</button>
            <button class="db-period-btn" data-preset="year">Year</button>
        </div>
        <div style="display:flex;align-items:center;gap:8px;">
            <span class="db-live-dot" id="dbLiveDot">Live</span>
            <span style="font-size:10.5px;color:rgba(148,163,184,0.85);font-weight:500;" id="dbLastUpdate">—</span>
        </div>
    </div>

    <!-- KPI amount -->
    <div class="db-hero-kpi">
        <div class="db-kpi-label" id="dbMainKpiLabel">Net Revenue</div>
        <div class="db-kpi-amount" id="dbMainKpiAmount">
            LKR <?= number_format($netSales, 2) ?>
        </div>
        <div class="db-kpi-meta">
            <span class="db-kpi-badge <?= dbGrowthClass($revenueGrowth) ?>" id="dbMainGrowth">
                <?= dbGrowthIcon($revenueGrowth) ?> <?= dbGrowthText($revenueGrowth) ?>
            </span>
            <span class="db-kpi-sub" id="dbMainKpiSub">
                <?= $invoiceCount ?> invoices · <?= number_format($itemsSold) ?> items
            </span>
        </div>
    </div>

    <!-- Metric selectors -->
    <div class="db-metric-pills" id="dbMetricPills">
        <button class="db-metric-pill active" data-metric="net_sales">
            <span class="pill-dot" style="background:#0f172a;"></span> Revenue
        </button>
        <button class="db-metric-pill" data-metric="gross_profit">
            <span class="pill-dot" style="background:#334155;"></span> Net Profit
        </button>
        <button class="db-metric-pill" data-metric="cogs">
            <span class="pill-dot" style="background:#64748b;"></span> COGS
        </button>
        <button class="db-metric-pill" data-metric="expenses">
            <span class="pill-dot" style="background:#94a3b8;"></span> Expenses
        </button>
    </div>

    <!-- Main chart -->
    <div class="db-hero-chart-area">
        <div class="db-hero-chart-wrap">
            <canvas id="dbMainChart"></canvas>
        </div>
    </div>

    <!-- Scroll hint -->
    <div class="db-scroll-hint" onclick="document.getElementById('dbPanels').scrollIntoView({behavior:'smooth'})">
        <span>Analytics</span>
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <polyline points="6 9 12 15 18 9"></polyline>
        </svg>
    </div>
</div>

<!-- ======================================================================
     ANALYTICS PANELS — glassmorphism bento grid
     ====================================================================== -->
<div class="db-panels" id="dbPanels">
<div class="db-bento">

<?php
// ── EXPIRY ALERT BANNER ────────────────────────────────────────────────────
$hasExpired   = !empty($expirySummary['expired_count'])    && $expirySummary['expired_count']    > 0;
$hasNearExpiry= !empty($expirySummary['near_expiry_count']) && $expirySummary['near_expiry_count'] > 0;
if ($hasExpired || $hasNearExpiry):
?>
<div class="db-bento-row">
    <div class="db-glass" style="border-color:<?= $hasExpired ? 'rgba(252,165,165,0.6)' : 'rgba(253,211,77,0.5)' ?>;">
        <div class="db-expiry-alert" style="background:<?= $hasExpired ? 'linear-gradient(135deg,rgba(255,241,242,0.9),rgba(254,242,242,0.9))' : 'linear-gradient(135deg,rgba(255,251,235,0.9),rgba(254,243,199,0.9))' ?>;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:38px;height:38px;border-radius:12px;background:<?= $hasExpired ? 'rgba(254,226,226,0.8)' : 'rgba(254,243,199,0.8)' ?>;color:<?= $hasExpired ? '#dc2626' : '#d97706' ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </div>
                <div>
                    <div style="font-size:13px;font-weight:800;color:<?= $hasExpired ? '#991b1b' : '#92400e' ?>;margin-bottom:2px;">
                        <?= $hasExpired ? 'Expired Inventory Detected' : 'Near-Expiry Inventory Alert' ?>
                    </div>
                    <div style="font-size:11.5px;color:#475569;display:flex;gap:14px;flex-wrap:wrap;">
                        <?php if ($hasExpired): ?>
                            <span><strong><?= (int)$expirySummary['expired_count'] ?></strong> batch<?= $expirySummary['expired_count'] > 1 ? 'es' : '' ?> (<?= number_format($expirySummary['expired_quantity'],0) ?> units) — Loss: <strong style="color:#dc2626;">LKR <?= number_format($expirySummary['expired_loss_value'],2) ?></strong></span>
                        <?php endif; ?>
                        <?php if ($hasNearExpiry): ?>
                            <span>⚠ <strong><?= (int)$expirySummary['near_expiry_count'] ?></strong> batch<?= $expirySummary['near_expiry_count'] > 1 ? 'es' : '' ?> expiring within <?= (int)$expirySummary['alert_days_soon'] ?> days (LKR <?= number_format($expirySummary['near_expiry_value'],2) ?> at risk)</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div style="display:flex;gap:8px;">
                <a href="expired_stock.php" style="display:inline-flex;align-items:center;gap:5px;padding:7px 16px;background:<?= $hasExpired ? '#dc2626' : '#d97706' ?>;color:#fff;font-size:12px;font-weight:700;border-radius:999px;text-decoration:none;">Manage →</a>
                <a href="reports.php/expired_stock" style="display:inline-flex;align-items:center;gap:5px;padding:7px 16px;background:rgba(255,255,255,0.6);color:#475569;font-size:12px;font-weight:700;border-radius:999px;text-decoration:none;border:1px solid rgba(255,255,255,0.85);">Reports</a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ── ROW 1: Four accent KPI cards ─────────────────────────────────────── -->
<div class="db-bento-row cols-4">

    <div class="db-glass db-kpi-accent db-accent-purple">
        <div class="db-kpi-accent-label">Net Revenue</div>
        <div class="db-kpi-accent-value" id="dkNetSales"><?= dbFmt($netSales) ?></div>
        <div class="db-kpi-accent-badge" id="dkNetSalesGrowth">
            <?= dbGrowthIcon($revenueGrowth) ?> <?= dbGrowthText($revenueGrowth) ?> vs prev
        </div>
    </div>

    <div class="db-glass db-kpi-accent db-accent-teal">
        <div class="db-kpi-accent-label">Gross Profit</div>
        <div class="db-kpi-accent-value" id="dkGrossProfit"><?= dbFmt($grossProfit) ?></div>
        <div class="db-kpi-accent-badge" id="dkGrossProfitGrowth">
            <?= dbGrowthIcon($profitGrowth) ?> <?= dbGrowthText($profitGrowth) ?> vs prev
        </div>
    </div>

    <div class="db-glass db-kpi-accent db-accent-blue">
        <div class="db-kpi-accent-label">Gross Sales</div>
        <div class="db-kpi-accent-value" id="dkGrossSales"><?= dbFmt($grossSales) ?></div>
        <div class="db-kpi-accent-sub">Before discounts &amp; returns</div>
    </div>

    <div class="db-glass db-kpi-accent db-accent-indigo">
        <div class="db-kpi-accent-label">Net Profit</div>
        <div class="db-kpi-accent-value <?= $netProfit < 0 ? '' : '' ?>" id="dkNetProfit"><?= dbFmt($netProfit) ?></div>
        <div class="db-kpi-accent-sub" id="dkNetProfitMargin"><?php $npm = $netSales > 0 ? round($netProfit / $netSales * 100, 1) : 0; ?>Margin: <?= $npm ?>%</div>
    </div>

</div>

<!-- ── ROW 2: Secondary metrics row ─────────────────────────────────────── -->
<div class="db-bento-row cols-4">

    <div class="db-glass db-kpi-card">
        <div class="db-kpi-card-label">COGS</div>
        <div class="db-kpi-card-value" id="dkCogs"><?= dbFmt($cogs) ?></div>
        <div class="db-kpi-card-growth <?= dbGrowthClass($cogsGrowth) ?>" id="dkCogsGrowth">
            <?= dbGrowthIcon($cogsGrowth) ?> <?= dbGrowthText($cogsGrowth) ?> vs prev
        </div>
    </div>

    <div class="db-glass db-kpi-card">
        <div class="db-kpi-card-label">Op. Expenses</div>
        <div class="db-kpi-card-value" id="dkOpEx"><?= dbFmt($opExpenses) ?></div>
        <div class="db-kpi-card-growth <?= dbGrowthClass($expenseGrowth) ?>" id="dkOpExGrowth">
            <?= dbGrowthIcon($expenseGrowth) ?> <?= dbGrowthText($expenseGrowth) ?> vs prev
        </div>
    </div>

    <div class="db-glass db-kpi-card">
        <div class="db-kpi-card-label">Gross Margin</div>
        <div class="db-kpi-card-value <?= $grossMargin < 20 ? 'is-negative' : '' ?>" id="dkGrossMargin"><?= dbFmt($grossMargin) ?>%</div>
        <div class="db-kpi-card-growth flat">Target ≥ 30%</div>
    </div>

    <div class="db-glass db-kpi-card">
        <div class="db-kpi-card-label">Discounts Given</div>
        <div class="db-kpi-card-value" id="dkDisc"><?= dbFmt($totalDisc) ?></div>
        <div class="db-kpi-card-growth flat">Total applied</div>
    </div>

</div>

<!-- ── ROW 3: Sales count metrics ────────────────────────────────────────── -->
<div class="db-bento-row cols-4">

    <div class="db-glass db-kpi-card">
        <div class="db-kpi-card-label">Invoices</div>
        <div class="db-kpi-card-value" id="dkInv"><?= $invoiceCount ?></div>
        <div class="db-kpi-card-growth flat">Bills raised</div>
    </div>

    <div class="db-glass db-kpi-card">
        <div class="db-kpi-card-label">Items Sold</div>
        <div class="db-kpi-card-value" id="dkItems"><?= number_format($itemsSold) ?></div>
        <div class="db-kpi-card-growth flat">Qty dispensed</div>
    </div>

    <div class="db-glass db-kpi-card">
        <div class="db-kpi-card-label">Avg. Bill Value</div>
        <div class="db-kpi-card-value" id="dkAvgBill"><?= $invoiceCount > 0 ? dbFmt($netSales / $invoiceCount) : '0.00' ?></div>
        <div class="db-kpi-card-growth flat">Per invoice</div>
    </div>

    <div class="db-glass db-kpi-card">
        <div class="db-kpi-card-label">Returns</div>
        <div class="db-kpi-card-value <?= $totalReturns > 0 ? 'is-negative' : '' ?>" id="dkRet"><?= dbFmt($totalReturns) ?></div>
        <div class="db-kpi-card-growth flat">Value refunded</div>
    </div>

</div>

<!-- ── ROW 4: Hourly Chart + Payment Methods ─────────────────────────────── -->
<div class="db-bento-row cols-3-2">

    <!-- Hourly trend chart -->
    <div class="db-glass db-chart-card">
        <div class="db-card-hd">
            <div class="db-card-title">
                <div class="db-card-icon" style="background:rgba(219,234,254,0.75);color:#2563eb;">
                    <svg viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
                </div>
                Hourly Sales Trend
            </div>
            <span class="db-card-meta db-section-meta" id="dbSectionPeriodLabel">Today</span>
        </div>
        <div style="position:relative;height:200px;">
            <canvas id="dbHourlyChart"></canvas>
        </div>
    </div>

    <!-- Payment methods donut -->
    <div class="db-glass db-chart-card">
        <div class="db-card-hd">
            <div class="db-card-title">
                <div class="db-card-icon" style="background:rgba(237,233,254,0.75);color:#7c3aed;">
                    <svg viewBox="0 0 24 24"><rect width="20" height="14" x="2" y="5" rx="2"/><path d="M2 10h20"/></svg>
                </div>
                Payment Methods
            </div>
        </div>
        <div class="db-donut-wrap" id="dbPaymentDonutWrap">
            <div class="db-donut-canvas-wrap">
                <canvas id="dbPaymentDonut" width="130" height="130"></canvas>
                <div class="db-donut-center">
                    <div class="db-donut-center-val" id="dbPayTotalAmt">
                        <?php
                        $payTotal = array_sum(array_column($payments, 'total_amount'));
                        echo number_format($payTotal, 0);
                        ?>
                    </div>
                    <div class="db-donut-center-lbl">Collected</div>
                </div>
            </div>
            <div class="db-legend" id="dbPayLegend">
                <?php foreach (array_values(array_slice(array_values($payments), 0, 8)) as $pi => $pm): ?>
                    <?php $amt = (float)($pm['total_amount'] ?? 0); $pct = $payTotal > 0 ? round($amt / $payTotal * 100, 1) : 0; ?>
                    <div class="db-legend-row">
                        <span class="db-legend-dot" style="background:<?= $pmColors[$pi % count($pmColors)] ?>;"></span>
                        <span class="db-legend-name"><?= htmlspecialchars($pm['payment_method'] ?? 'Other', ENT_QUOTES) ?></span>
                        <span class="db-legend-val"><?= number_format($amt, 0) ?></span>
                        <span class="db-legend-pct"><?= $pct ?>%</span>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($payments)): ?>
                    <div class="db-empty" style="padding:12px 0;">No payment data</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<?php
// ── Group Label: Products & Performance ────────────────────────────────────
$branchStockTotal = $inventory['stock_quantity'] ?? $inventory['branch_stock_total'] ?? 0;
$activeLotsCount  = $inventory['active_lots_count'] ?? 0;
$financial        = $ops['financial'] ?? [];
?>
<div class="db-group-label">Products &amp; Performance</div>

<!-- ── ROW 5: Top Products + Slow Movers ─────────────────────────────────── -->
<div class="db-bento-row cols-2">

    <!-- Top sellers -->
    <div class="db-glass db-chart-card">
        <div class="db-card-hd">
            <div class="db-card-title">
                <div class="db-card-icon" style="background:rgba(254,249,195,0.75);color:#d97706;">
                    <svg viewBox="0 0 24 24"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/></svg>
                </div>
                Top Selling Products
            </div>
        </div>
        <div id="dbTopProductsList">
            <?php if (empty($topProds)): ?>
                <div class="db-empty">No sales data yet</div>
            <?php else:
                $maxRev = max(array_column($topProds, 'total_revenue') ?: array_column($topProds, 'revenue') ?: [1]);
                foreach (array_slice($topProds, 0, 8) as $ri => $p):
                    $rev = (float)($p['total_revenue'] ?? $p['revenue'] ?? 0);
                    $qty = (float)($p['total_quantity'] ?? $p['units_sold'] ?? 0);
                    $pName = $p['product_name'] ?? $p['name'] ?? '';
                    $barW = $maxRev > 0 ? round($rev / $maxRev * 100) : 0;
            ?>
                <div class="db-list-row">
                    <span class="db-list-rank"><?= $ri + 1 ?></span>
                    <div style="flex:1;overflow:hidden;">
                        <div class="db-list-label"><?= htmlspecialchars($pName, ENT_QUOTES) ?></div>
                        <div class="db-list-sub"><?= number_format($qty) ?> units</div>
                    </div>
                    <div class="db-list-bar-wrap"><div class="db-list-bar" style="width:<?= $barW ?>%;"></div></div>
                    <span class="db-list-value"><?= number_format($rev, 0) ?></span>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- Slow movers -->
    <div class="db-glass db-chart-card">
        <div class="db-card-hd">
            <div class="db-card-title">
                <div class="db-card-icon" style="background:rgba(254,243,199,0.75);color:#d97706;">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                Slow Movers / Dead Stock
            </div>
        </div>
        <div id="dbSlowMoversList">
            <?php if (empty($slowProds)): ?>
                <div class="db-empty">No slow movers detected</div>
            <?php else:
                foreach (array_slice($slowProds, 0, 8) as $si => $s):
                    $sName = $s['product_name'] ?? $s['name'] ?? '';
                    $sStock = (float)($s['current_stock'] ?? $s['stock_on_hand'] ?? 0);
                    $daysSinceSale = !empty($s['last_sold_at']) ? (int)round((time() - strtotime($s['last_sold_at'])) / 86400) : null;
            ?>
                <div class="db-list-row">
                    <span class="db-list-rank"><?= $si + 1 ?></span>
                    <div style="flex:1;overflow:hidden;">
                        <div class="db-list-label"><?= htmlspecialchars($sName, ENT_QUOTES) ?></div>
                        <div class="db-list-sub">
                            Stock: <?= number_format($sStock) ?>
                            <?= $daysSinceSale !== null ? ' · Last sold ' . $daysSinceSale . 'd ago' : (!empty($s['units_sold']) ? ' · Sold: ' . (int)$s['units_sold'] : ' · 0 sold') ?>
                        </div>
                    </div>
                    <span class="db-badge db-badge-yellow">Slow</span>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

</div>

<div class="db-group-label">Category &amp; Brand Analytics</div>

<!-- ── ROW 6: Category + Brand breakdown ─────────────────────────────────── -->
<div class="db-bento-row cols-2">

    <!-- Category chart -->
    <div class="db-glass db-chart-card">
        <div class="db-card-hd">
            <div class="db-card-title">
                <div class="db-card-icon" style="background:rgba(219,234,254,0.75);color:#2563eb;">
                    <svg viewBox="0 0 24 24"><path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/></svg>
                </div>
                Revenue by Category
            </div>
        </div>
        <div id="dbCategoryList">
            <?php if (empty($cats)): ?>
                <div class="db-empty">No category data</div>
            <?php else:
                $catTotal = max(array_sum(array_column($cats, 'revenue') ?: array_column($cats, 'total_sales')), 1);
                foreach (array_slice($cats, 0, 8) as $ci => $cat):
                    $cv = (float)($cat['revenue'] ?? $cat['total_sales'] ?? 0);
                    $cName = $cat['category_name'] ?? $cat['name'] ?? 'Uncategorised';
                    $cp = round($cv / $catTotal * 100, 1);
            ?>
                <div class="db-progress-row">
                    <div class="db-progress-header">
                        <span><?= htmlspecialchars($cName, ENT_QUOTES) ?></span>
                        <span style="color:rgba(148,163,184,0.9);"><?= $cp ?>% · <?= number_format($cv, 0) ?></span>
                    </div>
                    <div class="db-progress-bar-track">
                        <div class="db-progress-bar-fill" style="width:<?= $cp ?>%;background:<?= $catColors[$ci % count($catColors)] ?>;"></div>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- Brand chart -->
    <div class="db-glass db-chart-card">
        <div class="db-card-hd">
            <div class="db-card-title">
                <div class="db-card-icon" style="background:rgba(237,233,254,0.75);color:#7c3aed;">
                    <svg viewBox="0 0 24 24"><path d="M12 2H2v10l9.29 9.29c.94.94 2.48.94 3.42 0l6.58-6.58c.94-.94.94-2.48 0-3.42L12 2Z"/><path d="M7 7h.01"/></svg>
                </div>
                Revenue by Brand
            </div>
        </div>
        <div id="dbBrandList">
            <?php if (empty($brands)): ?>
                <div class="db-empty">No brand data</div>
            <?php else:
                $brandTotal = max(array_sum(array_column($brands, 'revenue') ?: array_column($brands, 'total_sales')), 1);
                foreach (array_slice($brands, 0, 8) as $bi => $br):
                    $bv = (float)($br['revenue'] ?? $br['total_sales'] ?? 0);
                    $bName = $br['brand_name'] ?? $br['name'] ?? 'No Brand';
                    $bp = round($bv / $brandTotal * 100, 1);
            ?>
                <div class="db-progress-row">
                    <div class="db-progress-header">
                        <span><?= htmlspecialchars($bName, ENT_QUOTES) ?></span>
                        <span style="color:rgba(148,163,184,0.9);"><?= $bp ?>% · <?= number_format($bv, 0) ?></span>
                    </div>
                    <div class="db-progress-bar-track">
                        <div class="db-progress-bar-fill" style="width:<?= $bp ?>%;background:<?= $pmColors[$bi % count($pmColors)] ?>;"></div>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

</div>

<!-- ── SECTION 5: Cashier Performance ────────────────────────────────────── -->
<?php if (!empty($cashiers)): ?>
<div class="db-group-label">Cashier Performance</div>
<div class="db-bento-row" id="dbSectionCashiers">
    <div class="db-glass db-chart-card">
        <table class="db-mini-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Cashier</th>
                    <th class="col-right">Invoices</th>
                    <th class="col-right">Items</th>
                    <th class="col-right">Revenue (LKR)</th>
                    <th class="col-right">Avg Bill</th>
                    <th class="col-right">Discounts</th>
                </tr>
            </thead>
            <tbody id="dbCashierTableBody">
                <?php foreach (array_slice($cashiers, 0, 10) as $ci => $c): ?>
                    <tr>
                        <td><?= $ci + 1 ?></td>
                        <td class="fw-bold"><?= htmlspecialchars($c['cashier_name'] ?? $c['user_name'] ?? 'Unknown', ENT_QUOTES) ?></td>
                        <td class="col-right"><?= (int)($c['invoice_count'] ?? $c['total_invoices'] ?? 0) ?></td>
                        <td class="col-right"><?= number_format((int)($c['items_sold'] ?? $c['units_sold'] ?? 0)) ?></td>
                        <td class="col-right fw-bold"><?= number_format((float)($c['net_sales'] ?? $c['total_sales'] ?? 0), 2) ?></td>
                        <td class="col-right"><?= number_format((float)($c['avg_bill'] ?? $c['avg_sale_value'] ?? 0), 2) ?></td>
                        <td class="col-right"><?= number_format((float)($c['total_discounts'] ?? 0), 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- ── SECTION 6: Branch Performance (superadmin) ────────────────────────── -->
<?php if ($isAll): ?>
<div class="db-group-label">Branch Performance</div>
<div class="db-bento-row" id="dbSectionBranches">
    <div class="db-glass db-chart-card">
        <div id="dbBranchList">
            <?php
            $maxBranchRev = max(array_column($branches, 'net_sales') ?: [1]);
            foreach ($branches as $bi => $br):
                $brs = (float)($br['net_sales'] ?? 0);
                $brw = $maxBranchRev > 0 ? round($brs / $maxBranchRev * 100) : 0;
            ?>
                <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid rgba(226,232,240,0.4);">
                    <div style="width:32px;height:32px;border-radius:10px;background:<?= $catColors[$bi % count($catColors)] ?>20;color:<?= $catColors[$bi % count($catColors)] ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;border:1px solid <?= $catColors[$bi % count($catColors)] ?>30;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/></svg>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:12.5px;font-weight:700;color:#1e293b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($br['branch_name'] ?? '', ENT_QUOTES) ?></div>
                        <div style="margin-top:4px;height:4px;background:rgba(226,232,240,0.5);border-radius:999px;overflow:hidden;"><div style="height:100%;width:<?= $brw ?>%;background:<?= $catColors[$bi % count($catColors)] ?>;border-radius:999px;"></div></div>
                    </div>
                    <div style="text-align:right;flex-shrink:0;">
                        <div style="font-size:13px;font-weight:800;color:#1e1b4b;"><?= number_format($brs, 0) ?></div>
                        <div style="font-size:10.5px;color:rgba(148,163,184,0.9);"><?= (int)($br['invoice_count'] ?? 0) ?> inv</div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($branches)): ?>
                <div class="db-empty" style="padding:12px 0;">No branch comparison data</div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="db-group-label">Customer Analytics</div>

<!-- ── SECTION 7: Customer Analytics ─────────────────────────────────────── -->
<div class="db-bento-row cols-4">

    <div class="db-glass db-kpi-card">
        <div class="db-kpi-card-label">Total Customers</div>
        <div class="db-kpi-card-value" id="dkTotalCustomers"><?= number_format((int)($custAna['total_customers'] ?? 0)) ?></div>
    </div>

    <div class="db-glass db-kpi-card">
        <div class="db-kpi-card-label">Active Customers</div>
        <div class="db-kpi-card-value is-positive" id="dkActiveCustomers"><?= number_format((int)($custAna['active_customers'] ?? 0)) ?></div>
    </div>

    <div class="db-glass db-kpi-card">
        <div class="db-kpi-card-label">New Customers</div>
        <div class="db-kpi-card-value" id="dkNewCustomers"><?= number_format((int)($custAna['new_customers'] ?? 0)) ?></div>
        <div class="db-kpi-card-growth flat">This period</div>
    </div>

    <div class="db-glass db-kpi-card">
        <div class="db-kpi-card-label">Returning</div>
        <div class="db-kpi-card-value" id="dkReturningCustomers"><?= number_format((int)($custAna['returning_customers'] ?? 0)) ?></div>
    </div>

</div>

<!-- ── ROW: Customer financials + Top Customers ───────────────────────────── -->
<div class="db-bento-row cols-2-1-1">

    <!-- Top Customers -->
    <div class="db-glass db-chart-card">
        <div class="db-card-hd">
            <div class="db-card-title">
                <div class="db-card-icon" style="background:rgba(237,233,254,0.75);color:#7c3aed;">
                    <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                Top Customers by Revenue
            </div>
        </div>
        <div id="dbTopCustomersList">
            <?php if (empty($custAna['top_customers'])): ?>
                <div class="db-empty" style="padding:10px 0;">No customer sales data</div>
            <?php else:
                $maxCustRev = max(array_column($custAna['top_customers'], 'total_spent') ?: [1]);
                foreach (array_slice($custAna['top_customers'], 0, 6) as $ci => $tc):
                    $cv = (float)($tc['total_spent'] ?? 0);
                    $cw = $maxCustRev > 0 ? round($cv / $maxCustRev * 100) : 0;
            ?>
                <div class="db-list-row">
                    <span class="db-list-rank"><?= $ci + 1 ?></span>
                    <div style="flex:1;overflow:hidden;">
                        <div class="db-list-label"><?= htmlspecialchars($tc['customer_name'] ?? $tc['name'] ?? 'Walk-in', ENT_QUOTES) ?></div>
                        <div class="db-list-sub"><?= (int)($tc['invoice_count'] ?? $tc['order_count'] ?? 0) ?> invoices</div>
                    </div>
                    <div class="db-list-bar-wrap"><div class="db-list-bar" style="width:<?= $cw ?>%;"></div></div>
                    <span class="db-list-value"><?= number_format($cv, 0) ?></span>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <div class="db-glass db-ops-card">
        <div class="db-ops-card-header">
            <div class="db-ops-icon" style="background:rgba(220,252,231,0.75);color:#16a34a;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <div class="db-ops-label">Collections Today</div>
        </div>
        <div class="db-ops-value is-positive" id="dkCollectionsToday"><?= number_format((float)($financial['customer_collections'] ?? 0), 0) ?></div>
        <div class="db-ops-sub">Customer payments received</div>
    </div>

    <div class="db-glass db-ops-card">
        <div class="db-ops-card-header">
            <div class="db-ops-icon" style="background:rgba(254,242,242,0.75);color:#dc2626;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect width="8" height="4" x="8" y="2" rx="1"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/></svg>
            </div>
            <div class="db-ops-label">Credit Outstanding</div>
        </div>
        <div class="db-ops-value is-negative" id="dkCreditOutstanding"><?= number_format((float)($financial['outstanding_credit'] ?? 0), 0) ?></div>
        <div class="db-ops-sub">Uncollected receivables</div>
    </div>

</div>

<div class="db-group-label">Returns &amp; Discounts</div>

<!-- ── SECTION 8: Returns + Discount Analytics ───────────────────────────── -->
<div class="db-bento-row cols-2">

    <div class="db-glass db-chart-card">
        <div class="db-card-hd">
            <div class="db-card-title">
                <div class="db-card-icon" style="background:rgba(254,242,242,0.75);color:#dc2626;">
                    <svg viewBox="0 0 24 24"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                </div>
                Returns Summary
            </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
            <div>
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:rgba(148,163,184,0.9);margin-bottom:4px;">Return Count</div>
                <div style="font-size:1.4rem;font-weight:800;color:#dc2626;" id="dkReturnCount"><?= (int)($returns['return_count'] ?? 0) ?></div>
            </div>
            <div>
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:rgba(148,163,184,0.9);margin-bottom:4px;">Return Value</div>
                <div style="font-size:1.4rem;font-weight:800;color:#dc2626;" id="dkReturnValue"><?= number_format((float)($returns['total_return_value'] ?? 0), 0) ?></div>
            </div>
        </div>
        <div id="dbTopReturnedList">
            <?php if (!empty($returns['top_returned'])): ?>
                <?php foreach (array_slice($returns['top_returned'], 0, 4) as $r): ?>
                    <div class="db-list-row">
                        <div style="flex:1;overflow:hidden;">
                            <div class="db-list-label"><?= htmlspecialchars($r['product_name'] ?? '', ENT_QUOTES) ?></div>
                            <div class="db-list-sub"><?= (int)($r['return_qty'] ?? 0) ?> units returned</div>
                        </div>
                        <span class="db-badge db-badge-red"><?= number_format((float)($r['return_value'] ?? 0), 0) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="db-empty" style="padding:10px 0;">No returns recorded</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="db-glass db-chart-card">
        <div class="db-card-hd">
            <div class="db-card-title">
                <div class="db-card-icon" style="background:rgba(254,243,199,0.75);color:#d97706;">
                    <svg viewBox="0 0 24 24"><path d="M12 2H2v10l9.29 9.29c.94.94 2.48.94 3.42 0l6.58-6.58c.94-.94.94-2.48 0-3.42L12 2Z"/><path d="M7 7h.01"/></svg>
                </div>
                Discount Analytics
            </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
            <div>
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:rgba(148,163,184,0.9);margin-bottom:4px;">Total Discounted</div>
                <div style="font-size:1.4rem;font-weight:800;color:#d97706;" id="dkTotalDiscounted"><?= number_format((float)($discounts['total_discount_amount'] ?? 0), 0) ?></div>
            </div>
            <div>
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:rgba(148,163,184,0.9);margin-bottom:4px;">Invoices w/ Discount</div>
                <div style="font-size:1.4rem;font-weight:800;color:#d97706;" id="dkInvoicesDiscounted"><?= (int)($discounts['discounted_invoice_count'] ?? 0) ?></div>
            </div>
        </div>
        <div id="dbTopPromosList">
            <?php if (!empty($discounts['top_discount_promos'])): ?>
                <?php foreach (array_slice($discounts['top_discount_promos'], 0, 4) as $dp): ?>
                    <div class="db-list-row">
                        <div style="flex:1;overflow:hidden;">
                            <div class="db-list-label"><?= htmlspecialchars($dp['discount_name'] ?? 'Manual', ENT_QUOTES) ?></div>
                            <div class="db-list-sub"><?= (int)($dp['usage_count'] ?? 0) ?> times applied</div>
                        </div>
                        <span class="db-badge db-badge-yellow"><?= number_format((float)($dp['total_amount'] ?? 0), 0) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="db-empty" style="padding:10px 0;">No discount data</div>
            <?php endif; ?>
        </div>
    </div>

</div>

<div class="db-group-label">Inventory &amp; Stock Health</div>

<!-- ── SECTION 9: Inventory KPIs ─────────────────────────────────────────── -->
<div class="db-bento-row cols-4">

    <div class="db-glass db-kpi-accent db-accent-teal">
        <div class="db-kpi-accent-label">Inventory Value</div>
        <div class="db-kpi-accent-value" id="dkInventoryValue"><?= number_format((float)$invValue, 0) ?></div>
        <div class="db-kpi-accent-sub">At cost</div>
    </div>

    <div class="db-glass db-kpi-card">
        <div class="db-kpi-card-label">Low Stock Items</div>
        <div class="db-kpi-card-value <?= $lowStock > 0 ? 'is-negative' : '' ?>" id="dkLowStockCount"><?= (int)$lowStock ?></div>
        <div class="db-kpi-card-growth <?= $lowStock > 0 ? 'down' : 'flat' ?>" id="dkLowStockGrowth"><?= $lowStock > 0 ? '⚠ Action needed' : 'All good' ?></div>
    </div>

    <div class="db-glass db-kpi-card">
        <div class="db-kpi-card-label">Out of Stock</div>
        <div class="db-kpi-card-value <?= $outStock > 0 ? 'is-negative' : '' ?>" id="dkOutStockCount"><?= (int)$outStock ?></div>
        <div class="db-kpi-card-growth <?= $outStock > 0 ? 'down' : 'flat' ?>" id="dkOutStockGrowth"><?= $outStock > 0 ? '🔴 Replenish' : 'None' ?></div>
    </div>

    <div class="db-glass db-kpi-card">
        <div class="db-kpi-card-label">Branch Stock Qty</div>
        <div class="db-kpi-card-value" id="dkBranchStockQty"><?= number_format((float)($branchStockTotal ?? 0), 0) ?></div>
        <div class="db-kpi-card-growth flat">Units on hand</div>
    </div>

</div>

<!-- ── ROW: Stock Aging ───────────────────────────────────────────────────── -->
<div class="db-bento-row cols-2-1">

    <div class="db-glass db-chart-card">
        <div class="db-card-hd">
            <div class="db-card-title">
                <div class="db-card-icon" style="background:rgba(240,253,250,0.75);color:#0d9488;">
                    <svg viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/></svg>
                </div>
                Stock Aging Analysis
            </div>
            <span style="font-size:10.5px;color:rgba(148,163,184,0.9);"><?= (int)($activeLotsCount ?? 0) ?> active lots</span>
        </div>
        <div id="dbStockAgingList">
            <?php
            $agingTiers = [
                'fresh'    => ['label' => '< 30 days (Fresh)',       'color' => '#16a34a'],
                'normal'   => ['label' => '30–90 days (Normal)',      'color' => '#2563eb'],
                'aging'    => ['label' => '90–180 days (Aging)',      'color' => '#d97706'],
                'old'      => ['label' => '180–365 days (Old)',       'color' => '#ef4444'],
                'critical' => ['label' => '365+ days (Critical)',     'color' => '#9f1239'],
            ];
            $totalUnits = array_sum(array_column($aging, 'quantity') ?: array_values($aging));
            foreach ($agingTiers as $key => $tier):
                $qty = (float)($aging[$key]['quantity'] ?? $aging[$key] ?? 0);
                $pct = $totalUnits > 0 ? round($qty / $totalUnits * 100, 1) : 0;
            ?>
                <div class="db-aging-row">
                    <div class="db-aging-label"><?= $tier['label'] ?></div>
                    <div class="db-aging-bar-track"><div class="db-aging-bar-fill" style="width:<?= $pct ?>%;background:<?= $tier['color'] ?>;"></div></div>
                    <div class="db-aging-val"><?= number_format($qty, 0) ?> <span style="color:rgba(148,163,184,0.85);font-weight:400;">(<?= $pct ?>%)</span></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="db-glass db-kpi-card" style="display:flex;flex-direction:column;justify-content:center;gap:14px;">
        <div>
            <div class="db-kpi-card-label">Price Lots</div>
            <div class="db-kpi-card-value" id="dkActiveLotsCount"><?= (int)($activeLotsCount ?? 0) ?></div>
            <div class="db-kpi-card-growth flat">Active lots</div>
        </div>
    </div>

</div>

<div class="db-group-label">Financial Position</div>

<!-- ── SECTION 10: Financial Position ────────────────────────────────────── -->
<div class="db-bento-row cols-4">

    <div class="db-glass db-ops-card">
        <div class="db-ops-card-header">
            <div class="db-ops-icon" style="background:rgba(220,252,231,0.75);color:#16a34a;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="12" x="2" y="6" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
            </div>
            <div class="db-ops-label">Cash Drawer</div>
        </div>
        <div class="db-ops-value" id="dkCashDrawer"><?= number_format((float)($financial['cash_drawer_balance'] ?? 0), 0) ?></div>
        <div class="db-ops-sub">POS register balance</div>
    </div>

    <div class="db-glass db-ops-card">
        <div class="db-ops-card-header">
            <div class="db-ops-icon" style="background:rgba(219,234,254,0.75);color:#2563eb;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="12" x="2" y="6" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
            </div>
            <div class="db-ops-label">Cash in Hand</div>
        </div>
        <div class="db-ops-value" id="dkCashInHand"><?= number_format((float)($financial['cash_in_hand'] ?? 0), 0) ?></div>
        <div class="db-ops-sub">Main safe balance</div>
    </div>

    <div class="db-glass db-ops-card">
        <div class="db-ops-card-header">
            <div class="db-ops-icon" style="background:rgba(237,233,254,0.75);color:#7c3aed;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M3 10h18"/><path d="M5 6l7-3 7 3"/><path d="M4 10v11"/><path d="M20 10v11"/><path d="M8 14v3"/><path d="M12 14v3"/><path d="M16 14v3"/></svg>
            </div>
            <div class="db-ops-label">Bank Balances</div>
        </div>
        <div class="db-ops-value" id="dkBankBalances"><?= number_format((float)($financial['bank_total'] ?? 0), 0) ?></div>
        <div class="db-ops-sub">All accounts combined</div>
    </div>

    <div class="db-glass db-ops-card">
        <div class="db-ops-card-header">
            <div class="db-ops-icon" style="background:rgba(254,242,242,0.75);color:#dc2626;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5"/><path d="m5 12 7-7 7 7"/></svg>
            </div>
            <div class="db-ops-label">Supplier Payables</div>
        </div>
        <div class="db-ops-value is-negative" id="dkSupplierPayables"><?= number_format((float)($financial['supplier_payables'] ?? 0), 0) ?></div>
        <div class="db-ops-sub">Outstanding payable</div>
    </div>

</div>

<div class="db-bento-row cols-2-1-1">

    <!-- Bank accounts table -->
    <div class="db-glass db-chart-card" id="dbBankAccountsCard">
        <div class="db-card-hd">
            <div class="db-card-title">
                <div class="db-card-icon" style="background:rgba(219,234,254,0.75);color:#2563eb;">
                    <svg viewBox="0 0 24 24"><path d="M3 21h18"/><path d="M3 10h18"/><path d="M5 6l7-3 7 3"/><path d="M4 10v11"/><path d="M20 10v11"/><path d="M8 14v3"/><path d="M12 14v3"/><path d="M16 14v3"/></svg>
                </div>
                Bank Accounts
            </div>
        </div>
        <table class="db-mini-table">
            <thead>
                <tr>
                    <th>Bank / Account</th>
                    <th>Account No.</th>
                    <th class="col-right">Balance (LKR)</th>
                </tr>
            </thead>
            <tbody id="dbBankAccountsTableBody">
                <?php foreach ($financial['bank_accounts'] ?? [] as $ba): ?>
                    <tr>
                        <td class="fw-bold"><?= htmlspecialchars($ba['bank_name'] ?? '', ENT_QUOTES) ?></td>
                        <td><?= htmlspecialchars($ba['account_number'] ?? '—', ENT_QUOTES) ?></td>
                        <td class="col-right fw-bold <?= (float)($ba['balance'] ?? 0) < 0 ? 'is-negative' : '' ?>"><?= number_format((float)($ba['balance'] ?? 0), 2) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($financial['bank_accounts'])): ?>
                    <tr><td colspan="3" class="db-empty" style="padding:14px 0;">No active bank accounts</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="db-glass db-ops-card">
        <div class="db-ops-card-header">
            <div class="db-ops-icon" style="background:rgba(240,253,250,0.75);color:#0d9488;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 18V6"/></svg>
            </div>
            <div class="db-ops-label">Petty Cash</div>
        </div>
        <div class="db-ops-value" id="dkPettyCash"><?= number_format((float)($financial['petty_cash_balance'] ?? 0), 0) ?></div>
        <div class="db-ops-sub">Petty cash on hand</div>
    </div>

</div>

<div class="db-group-label">Recent Activity</div>

<!-- ── SECTION 11: Recent Sales + Quick Nav ───────────────────────────────── -->
<div class="db-bento-row cols-3-2">

    <!-- Recent sales -->
    <div class="db-glass db-chart-card">
        <div class="db-card-hd">
            <div class="db-card-title">
                <div class="db-card-icon" style="background:rgba(248,250,252,0.8);color:#475569;">
                    <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/><path d="M10 9H8"/></svg>
                </div>
                Recent Sales Activity
            </div>
        </div>
        <div id="dbRecentSalesList">
            <?php if (empty($recentSales)): ?>
                <div class="db-empty">No sales recorded yet</div>
            <?php else: ?>
                <?php foreach (array_slice($recentSales, 0, 8) as $rs): ?>
                    <div class="db-activity-row">
                        <div class="db-activity-icon">
                            <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/><path d="M10 9H8"/></svg>
                        </div>
                        <div class="db-activity-main">
                            <div class="db-activity-title"><?= htmlspecialchars($rs['invoice_number'] ?? 'INV', ENT_QUOTES) ?> — <?= htmlspecialchars($rs['customer_name'] ?? 'Walk-in', ENT_QUOTES) ?></div>
                            <div class="db-activity-sub"><?= htmlspecialchars($rs['cashier_name'] ?? '', ENT_QUOTES) ?> · <?= !empty($rs['created_at']) ? date('H:i', strtotime($rs['created_at'])) : '' ?></div>
                        </div>
                        <div style="text-align:right;">
                            <div class="db-activity-amount">LKR <?= number_format((float)($rs['total_amount'] ?? 0), 2) ?></div>
                            <div class="db-activity-time"><span class="db-badge db-badge-green"><?= htmlspecialchars($rs['payment_status'] ?? 'paid', ENT_QUOTES) ?></span></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Audit trail -->
    <div class="db-glass db-chart-card">
        <div class="db-card-hd">
            <div class="db-card-title">
                <div class="db-card-icon" style="background:rgba(219,234,254,0.75);color:#2563eb;">
                    <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                </div>
                Recent Audit Trail
            </div>
        </div>
        <?php foreach (array_slice($recentAudits ?? [], 0, 8) as $log): ?>
            <div style="display:flex;align-items:center;gap:8px;padding:7px 0;border-bottom:1px solid rgba(226,232,240,0.4);">
                <span class="db-badge db-badge-blue"><?= htmlspecialchars($log['action'], ENT_QUOTES) ?></span>
                <span style="flex:1;font-size:11.5px;color:#475569;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($log['module'], ENT_QUOTES) ?></span>
                <span style="font-size:10.5px;color:rgba(203,213,225,0.9);white-space:nowrap;"><?= date('H:i', strtotime($log['created_at'])) ?></span>
            </div>
        <?php endforeach; ?>
        <?php if (empty($recentAudits)): ?>
            <div class="db-empty" style="padding:10px 0;">No recent activity</div>
        <?php endif; ?>
    </div>

</div>

<!-- ── Developer Credits Footer ─────────────────────────────────────────── -->
<div class="db-footer-credits">
    <a href="https://www.trycurtiss.com" target="_blank" rel="noopener noreferrer" class="db-credits-pill" title="Visit Curtiss POS Website">
        <span class="db-credits-logo">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                <polyline points="2 17 12 22 22 17"></polyline>
                <polyline points="2 12 12 17 22 12"></polyline>
            </svg>
        </span>
        <span class="db-credits-label">Designed &amp; Developed with</span>
        <span class="db-credits-brand">CURTISS</span>
        <span class="db-credits-sep">·</span>
        <span class="db-credits-url">www.trycurtiss.com</span>
        <svg class="db-credits-arrow" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="7" y1="17" x2="17" y2="7"></line>
            <polyline points="7 7 17 7 17 17"></polyline>
        </svg>
    </a>
</div>

</div><!-- /db-bento -->
</div><!-- /db-panels -->
<!-- ======================================================================
     CHART.JS CDN + DASHBOARD INIT SCRIPT
     ====================================================================== -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script src="assets/js/curtiss_dashboard.js?v=<?= file_exists(APP_ROOT . '/assets/js/curtiss_dashboard.js') ? filemtime(APP_ROOT . '/assets/js/curtiss_dashboard.js') : time() ?>"></script>
<script>
(function () {
    'use strict';

    // ── PHP data bridge ──────────────────────────────────────────────────
    const PHP = {
        activeBranchId: <?= json_encode($isAll ? 'all' : $branchIdCtx) ?>,
        preset: 'today',
        tsLabels: <?= json_encode(array_values($tsLabels)) ?>,
        tsNet:    <?= json_encode(array_map('floatval', array_values($tsSales))) ?>,
        tsCogs:   <?= json_encode(array_map('floatval', array_values($tsCogs))) ?>,
        tsProfit: <?= json_encode(array_map('floatval', array_values($tsProfit))) ?>,
        tsExp:    <?= json_encode(array_map('floatval', array_values($tsExpenses))) ?>,
        hrLabels: <?= json_encode($hrLabels) ?>,
        hrSales:  <?= json_encode(array_map('floatval', $hrSales)) ?>,
        pmLabels: <?= json_encode(array_values(array_column($payments, 'payment_method'))) ?>,
        pmData:   <?= json_encode(array_values(array_map('floatval', array_column($payments, 'total_amount')))) ?>,
        pmColors: <?= json_encode($pmColors) ?>,
        netSales: <?= json_encode(round($netSales, 2)) ?>,
        grossProfit: <?= json_encode(round($grossProfit, 2)) ?>,
        cogs: <?= json_encode(round($cogs, 2)) ?>,
        opExpenses: <?= json_encode(round($opExpenses, 2)) ?>,
        invoiceCount: <?= json_encode($invoiceCount) ?>,
        itemsSold: <?= json_encode($itemsSold) ?>,
        revenueGrowth: <?= json_encode(round($revenueGrowth, 2)) ?>,
        profitGrowth: <?= json_encode(round($profitGrowth, 2)) ?>,
        cogsGrowth: <?= json_encode(round($cogsGrowth, 2)) ?>,
        expenseGrowth: <?= json_encode(round($expenseGrowth, 2)) ?>,
    };

    // ── Chart.js global defaults ─────────────────────────────────────────
    Chart.defaults.font.family = "'SF Pro Display', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif";
    Chart.defaults.color = '#94a3b8';

    // ── Active metric state ──────────────────────────────────────────────
    let activeMetric  = 'net_sales';
    let activePreset  = 'today';
    let mainChart     = null;
    let hourlyChart   = null;
    let paymentDonut  = null;

    // ── Metric config ────────────────────────────────────────────────────
    const METRICS = {
        net_sales:    { label:'Net Revenue',  data: () => PHP.tsNet,    color:'#0f172a', growth: () => PHP.revenueGrowth, kpiVal: () => PHP.netSales, kpiSub: () => PHP.invoiceCount + ' invoices · ' + PHP.itemsSold.toLocaleString() + ' items' },
        gross_profit: { label:'Net Profit',   data: () => PHP.tsProfit, color:'#1e293b', growth: () => PHP.profitGrowth,  kpiVal: () => PHP.grossProfit, kpiSub: () => '' },
        cogs:         { label:'COGS',         data: () => PHP.tsCogs,   color:'#334155', growth: () => PHP.cogsGrowth,    kpiVal: () => PHP.cogs, kpiSub: () => '' },
        expenses:     { label:'Op. Expenses', data: () => PHP.tsExp,    color:'#475569', growth: () => PHP.expenseGrowth, kpiVal: () => PHP.opExpenses, kpiSub: () => '' },
    };

    // ── Hero main chart ──────────────────────────────────────────────────
    function buildMainChart(metric) {
        const cfg = METRICS[metric];
        const ctx = document.getElementById('dbMainChart').getContext('2d');
        const labels = PHP.tsLabels.length ? PHP.tsLabels : ['—'];
        const data   = cfg.data().length   ? cfg.data()   : [0];
        const col    = '#0f172a'; // Black & White theme
        const grad   = ctx.createLinearGradient(0, 0, 0, 280);
        grad.addColorStop(0, 'rgba(15, 23, 42, 0.16)');
        grad.addColorStop(1, 'rgba(255, 255, 255, 0.00)');

        if (mainChart) mainChart.destroy();
        mainChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    {
                        type: 'bar',
                        label: cfg.label,
                        data,
                        backgroundColor: 'rgba(15, 23, 42, 0.08)',
                        borderColor: 'rgba(15, 23, 42, 0.22)',
                        borderWidth: 1,
                        borderRadius: 4,
                        order: 2,
                    },
                    {
                        type: 'line',
                        label: cfg.label + ' (trend)',
                        data,
                        borderColor: '#0f172a',
                        borderWidth: 2.5,
                        backgroundColor: grad,
                        pointBackgroundColor: '#0f172a',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: labels.length > 20 ? 0 : 4,
                        pointHoverRadius: 6,
                        tension: 0.4,
                        fill: true,
                        order: 1,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                animation: { duration: 600, easing: 'easeOutQuart' },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleColor: '#94a3b8',
                        bodyColor: '#f8fafc',
                        borderColor: '#1e293b',
                        borderWidth: 1,
                        cornerRadius: 10,
                        padding: 12,
                        callbacks: {
                            label: ctx => ' LKR ' + Number(ctx.parsed.y || 0).toLocaleString('en-LK', {minimumFractionDigits:2, maximumFractionDigits:2}),
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { color: '#f1f5f9', lineWidth: 1 },
                        border: { display: false },
                        ticks: { maxTicksLimit: 12, font: { size: 10, weight: '600' } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9', lineWidth: 1 },
                        border: { display: false },
                        ticks: {
                            font: { size: 10, weight: '600' },
                            callback: v => v >= 1000000 ? (v/1000000).toFixed(1)+'M' : v >= 1000 ? (v/1000).toFixed(0)+'K' : v
                        }
                    }
                }
            }
        });
    }

    // ── Hourly chart ─────────────────────────────────────────────────────
    function buildHourlyChart(customLabels, customData) {
        const ctx = document.getElementById('dbHourlyChart');
        if (!ctx) return;
        const c2 = ctx.getContext('2d');
        const labels = customLabels || (PHP.hrLabels.length ? PHP.hrLabels : Array.from({length:24},(_,i)=>i+':00'));
        const data   = customData   || (PHP.hrSales.length  ? PHP.hrSales  : Array(24).fill(0));
        const grad   = c2.createLinearGradient(0,0,0,200);
        grad.addColorStop(0,'#2563eb22');
        grad.addColorStop(1,'#2563eb00');
        if (hourlyChart) hourlyChart.destroy();
        hourlyChart = new Chart(c2, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: 'Hourly Sales',
                    data,
                    borderColor: '#2563eb',
                    borderWidth: 2,
                    backgroundColor: grad,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    pointBackgroundColor: '#2563eb',
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                interaction: { mode:'index', intersect: false },
                animation: { duration: 500 },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor:'#0f172a', titleColor:'#94a3b8', bodyColor:'#f8fafc',
                        cornerRadius:8, padding:10,
                        callbacks: { label: ctx => ' LKR ' + Number(ctx.parsed.y||0).toLocaleString('en-LK',{minimumFractionDigits:2,maximumFractionDigits:2}) }
                    }
                },
                scales: {
                    x: { grid:{color:'#f1f5f9'}, border:{display:false}, ticks:{font:{size:10,weight:'600'},maxTicksLimit:12} },
                    y: { grid:{color:'#f1f5f9'}, border:{display:false}, ticks:{font:{size:10,weight:'600'}, callback: v=>v>=1000?(v/1000).toFixed(0)+'K':v} }
                }
            }
        });
    }

    // ── Payment donut ─────────────────────────────────────────────────────
    function buildPaymentDonut(customLabels, customData, customColors) {
        const ctx = document.getElementById('dbPaymentDonut');
        if (!ctx) return;
        if (paymentDonut) paymentDonut.destroy();
        const labels = customLabels || PHP.pmLabels || [];
        const data   = customData   || PHP.pmData   || [];
        const colors = customColors || PHP.pmColors || ['#2563eb','#16a34a','#d97706','#7c3aed','#06b6d4','#ef4444','#64748b','#8b5cf6'];
        if (!data.length) return;
        paymentDonut = new Chart(ctx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{ data: data, backgroundColor: colors, borderWidth: 2, borderColor: '#ffffff', hoverOffset: 4 }]
            },
            options: {
                responsive: false, maintainAspectRatio: false,
                cutout: '72%',
                animation: { duration: 600, easing: 'easeOutQuart' },
                plugins: { legend: { display: false }, tooltip: {
                    backgroundColor:'#0f172a', bodyColor:'#f8fafc', cornerRadius:8, padding:10,
                    callbacks: { label: ctx => ' LKR ' + Number(ctx.parsed||0).toLocaleString('en-LK',{minimumFractionDigits:2,maximumFractionDigits:2}) }
                }}
            }
        });
    }

    // ── Safe HTML & Number Helpers ────────────────────────────────────────
    function esc(s) {
        if (!s && s !== 0) return '';
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
    }
    function fmtNum(n, d = 0) {
        return Number(n || 0).toLocaleString('en-LK', { minimumFractionDigits: d, maximumFractionDigits: d });
    }
    const PALETTE = ['#2563eb','#16a34a','#d97706','#7c3aed','#06b6d4','#ef4444','#64748b','#8b5cf6'];

    // ── KPI update (from live data) ───────────────────────────────────────
    function updateKpiDisplay(metric, data) {
        const cfg = METRICS[metric];
        if (!cfg) return;
        const val = cfg.kpiVal();
        const growth = cfg.growth();
        const el = document.getElementById('dbMainKpiAmount');
        if (el) {
            el.classList.add('updating');
            setTimeout(() => {
                el.textContent = 'LKR ' + Number(val).toLocaleString('en-LK',{minimumFractionDigits:2,maximumFractionDigits:2});
                el.classList.remove('updating');
            }, 150);
        }
        const lbl = document.getElementById('dbMainKpiLabel');
        if (lbl) lbl.textContent = cfg.label;
        const growthEl = document.getElementById('dbMainGrowth');
        if (growthEl) {
            growthEl.className = 'db-kpi-badge ' + (growth > 0 ? 'up' : growth < 0 ? 'down' : 'flat');
            growthEl.textContent = (growth > 0 ? '▲ +' : growth < 0 ? '▼ ' : '— ') + Math.abs(growth).toFixed(1) + '%';
        }
        const sub = document.getElementById('dbMainKpiSub');
        if (sub) sub.textContent = cfg.kpiSub();
    }

    // ── Metric pill click ─────────────────────────────────────────────────
    document.querySelectorAll('.db-metric-pill').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.db-metric-pill').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            activeMetric = btn.dataset.metric;
            buildMainChart(activeMetric);
            updateKpiDisplay(activeMetric);
        });
    });

    // ── Period selector ───────────────────────────────────────────────────
    document.querySelectorAll('.db-period-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.db-period-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            activePreset = btn.dataset.preset;
            const label = { today:'Today', week:'This Week', month:'This Month', quarter:'This Quarter', year:'This Year' };
            document.querySelectorAll('.db-section-meta').forEach(el => el.textContent = label[activePreset] || activePreset);
            if (typeof CurtissDashboard !== 'undefined') {
                CurtissDashboard.setPreset(activePreset);
            }
        });
    });

    // ── Comprehensive Live Hydration on Update ────────────────────────────
    function handleDashboardUpdate(e) {
        const payload = (e && e.detail ? (e.detail.data || e.detail) : (e && e.data ? e.data : e)) || {};
        const s = payload.sales_summary || payload.analytics?.current || (payload.current ? payload.current : null);
        const g = payload.growth || payload.analytics?.growth;

        // 1. Update mirror state if sales data exists
        if (s) {
            PHP.netSales    = parseFloat(s.net_sales    || s.revenue || 0);
            PHP.grossProfit = parseFloat(s.gross_profit || 0);
            PHP.cogs        = parseFloat(s.cogs         || 0);
            PHP.opExpenses  = parseFloat(s.operating_expenses || 0);
            PHP.invoiceCount= parseInt(s.invoice_count  || 0);
            PHP.itemsSold   = parseInt(s.items_sold     || s.units_sold || 0);
            if (g) {
                PHP.revenueGrowth  = parseFloat(g.net_sales_growth || g.net_sales?.percentage || 0);
                PHP.profitGrowth   = parseFloat(g.gross_profit_growth || g.gross_profit?.percentage || 0);
                PHP.cogsGrowth     = parseFloat(g.cogs_growth || g.cogs?.percentage || 0);
                PHP.expenseGrowth  = parseFloat(g.expenses_growth || g.operating_expenses?.percentage || 0);
            }
            updateKpiDisplay(activeMetric);

            // 3. Section 1 KPIs
            const avgBill = PHP.invoiceCount > 0 ? (PHP.netSales / PHP.invoiceCount) : 0;
            const kpis = {
                dkNetSales: fmtNum(s.net_sales, 2),
                dkGrossSales: fmtNum(s.gross_sales, 2),
                dkGrossProfit: fmtNum(s.gross_profit, 2),
                dkGrossMargin: (parseFloat(s.gross_margin_pct || 0)).toFixed(1) + '%',
                dkCogs: fmtNum(s.cogs, 2),
                dkOpEx: fmtNum(s.operating_expenses, 2),
                dkNetProfit: fmtNum(s.net_profit, 2),
                dkDisc: fmtNum(s.total_discounts, 2),
                dkRet: fmtNum(s.total_returns, 2),
                dkInv: fmtNum(PHP.invoiceCount, 0),
                dkItems: fmtNum(PHP.itemsSold, 0),
                dkAvgBill: fmtNum(avgBill, 2),
            };
            Object.entries(kpis).forEach(([id, val]) => {
                const el = document.getElementById(id);
                if (el && val !== undefined) el.textContent = val;
            });
        }

        // 2. Main Time Series Chart
        const ts = payload.time_series;
        if (ts) {
            if (ts.labels && ts.series) {
                PHP.tsLabels = ts.labels;
                PHP.tsNet    = (ts.series.sales || []).map(Number);
                PHP.tsCogs   = (ts.series.cogs || []).map(Number);
                PHP.tsProfit = (ts.series.gross_profit || []).map(Number);
                PHP.tsExp    = (ts.series.expenses || []).map(Number);
                buildMainChart(activeMetric);
            } else if (Array.isArray(ts) && ts.length) {
                PHP.tsLabels = ts.map(r => r.label);
                PHP.tsNet    = ts.map(r => parseFloat(r.net_sales   || r.sales || 0));
                PHP.tsCogs   = ts.map(r => parseFloat(r.cogs        || 0));
                PHP.tsProfit = ts.map(r => parseFloat(r.gross_profit|| 0));
                PHP.tsExp    = ts.map(r => parseFloat(r.expenses    || 0));
                buildMainChart(activeMetric);
            }
        }

        // 4. Section 2: Hourly Chart & Payment Donut
        if (payload.hourly_trend && Array.isArray(payload.hourly_trend) && payload.hourly_trend.length) {
            const hLabels = payload.hourly_trend.map(h => h.label);
            const hData   = payload.hourly_trend.map(h => parseFloat(h.net_sales || 0));
            buildHourlyChart(hLabels, hData);
        } else if (PHP.hrLabels.length) {
            buildHourlyChart();
        }

        const pmList = payload.payments?.methods || payload.payments?.breakdown || (Array.isArray(payload.payments) ? payload.payments : []);
        if (pmList.length) {
            const pmLabels = pmList.map(p => p.payment_method || p.method_label || p.method || 'Other');
            const pmData   = pmList.map(p => parseFloat(p.total_amount || 0));
            const pmColors = pmList.map((_, i) => PALETTE[i % PALETTE.length]);
            buildPaymentDonut(pmLabels, pmData, pmColors);

            const payTotal = pmData.reduce((a, b) => a + b, 0);
            const totEl = document.getElementById('dbPayTotalAmt');
            if (totEl) totEl.textContent = fmtNum(payTotal, 0);

            const legEl = document.getElementById('dbPayLegend');
            if (legEl) {
                legEl.innerHTML = pmList.slice(0, 8).map((pm, i) => {
                    const amt = parseFloat(pm.total_amount || 0);
                    const pct = payTotal > 0 ? Math.round(amt / payTotal * 1000) / 10 : 0;
                    const name = pm.payment_method || pm.method_label || pm.method || 'Other';
                    const color = PALETTE[i % PALETTE.length];
                    return `<div class="db-legend-row">
                        <span class="db-legend-dot" style="background:${color};"></span>
                        <span class="db-legend-name">${esc(name)}</span>
                        <span class="db-legend-val">${fmtNum(amt, 0)}</span>
                        <span class="db-legend-pct">${pct}%</span>
                    </div>`;
                }).join('');
            }
        }

        // 5. Section 3: Top Products & Slow Movers
        const topProds = payload.products?.top_selling || (Array.isArray(payload.products) ? payload.products : []);
        const tpEl = document.getElementById('dbTopProductsList');
        if (tpEl) {
            if (!topProds.length) {
                tpEl.innerHTML = '<div class="db-empty">No sales data yet</div>';
            } else {
                const maxRev = Math.max(...topProds.map(p => parseFloat(p.total_revenue || p.revenue || 0)), 1);
                tpEl.innerHTML = topProds.slice(0, 8).map((p, i) => {
                    const rev = parseFloat(p.total_revenue || p.revenue || 0);
                    const qty = parseFloat(p.total_quantity || p.units_sold || p.quantity_sold || 0);
                    const name = p.product_name || p.name || 'Product';
                    const barW = maxRev > 0 ? Math.round(rev / maxRev * 100) : 0;
                    return `<div class="db-list-row">
                        <span class="db-list-rank">${i + 1}</span>
                        <div style="flex:1;overflow:hidden;">
                            <div class="db-list-label">${esc(name)}</div>
                            <div class="db-list-sub">${fmtNum(qty, 0)} units</div>
                        </div>
                        <div class="db-list-bar-wrap"><div class="db-list-bar" style="width:${barW}%;"></div></div>
                        <span class="db-list-value">${fmtNum(rev, 0)}</span>
                    </div>`;
                }).join('');
            }
        }

        const slowProds = payload.products?.slow_moving || [];
        const smEl = document.getElementById('dbSlowMoversList');
        if (smEl) {
            if (!slowProds.length) {
                smEl.innerHTML = '<div class="db-empty">No slow movers detected</div>';
            } else {
                smEl.innerHTML = slowProds.slice(0, 8).map((s, i) => {
                    const name = s.product_name || s.name || 'Product';
                    const stock = parseFloat(s.current_stock || s.stock_on_hand || 0);
                    const days = s.days_since_last_sale !== undefined && s.days_since_last_sale !== null
                        ? ` · Last sold ${s.days_since_last_sale}d ago`
                        : (s.units_sold ? ` · Sold: ${s.units_sold}` : ' · 0 sold');
                    return `<div class="db-list-row">
                        <span class="db-list-rank">${i + 1}</span>
                        <div style="flex:1;overflow:hidden;">
                            <div class="db-list-label">${esc(name)}</div>
                            <div class="db-list-sub">Stock: ${fmtNum(stock, 0)}${days}</div>
                        </div>
                        <span class="db-badge db-badge-yellow">Slow</span>
                    </div>`;
                }).join('');
            }
        }

        // 6. Section 4: Categories & Brands
        const cats = payload.categories || [];
        const catEl = document.getElementById('dbCategoryList');
        if (catEl) {
            if (!cats.length) {
                catEl.innerHTML = '<div class="db-empty">No category data</div>';
            } else {
                const catTotal = Math.max(cats.reduce((a, c) => a + parseFloat(c.revenue || c.total_sales || 0), 0), 1);
                catEl.innerHTML = cats.slice(0, 8).map((c, i) => {
                    const cv = parseFloat(c.revenue || c.total_sales || 0);
                    const cp = Math.round(cv / catTotal * 1000) / 10;
                    const name = c.category_name || c.name || 'Uncategorised';
                    const color = PALETTE[i % PALETTE.length];
                    return `<div class="db-progress-row">
                        <div class="db-progress-header">
                            <span>${esc(name)}</span>
                            <span style="color:#94a3b8;">${cp}% · ${fmtNum(cv, 0)}</span>
                        </div>
                        <div class="db-progress-bar-track">
                            <div class="db-progress-bar-fill" style="width:${cp}%;background:${color};"></div>
                        </div>
                    </div>`;
                }).join('');
            }
        }

        const brands = payload.brands || [];
        const brEl = document.getElementById('dbBrandList');
        if (brEl) {
            if (!brands.length) {
                brEl.innerHTML = '<div class="db-empty">No brand data</div>';
            } else {
                const brTotal = Math.max(brands.reduce((a, b) => a + parseFloat(b.revenue || b.total_sales || 0), 0), 1);
                brEl.innerHTML = brands.slice(0, 8).map((b, i) => {
                    const bv = parseFloat(b.revenue || b.total_sales || 0);
                    const bp = Math.round(bv / brTotal * 1000) / 10;
                    const name = b.brand_name || b.name || 'No Brand';
                    const color = PALETTE[i % PALETTE.length];
                    return `<div class="db-progress-row">
                        <div class="db-progress-header">
                            <span>${esc(name)}</span>
                            <span style="color:#94a3b8;">${bp}% · ${fmtNum(bv, 0)}</span>
                        </div>
                        <div class="db-progress-bar-track">
                            <div class="db-progress-bar-fill" style="width:${bp}%;background:${color};"></div>
                        </div>
                    </div>`;
                }).join('');
            }
        }

        // 7. Section 5: Cashiers
        const cashiers = payload.cashiers || [];
        const cTableBody = document.getElementById('dbCashierTableBody');
        const cSection   = document.getElementById('dbSectionCashiers');
        if (cTableBody) {
            if (!cashiers.length) {
                if (cSection) cSection.style.display = 'none';
                cTableBody.innerHTML = '<tr><td colspan="7" class="db-empty" style="padding:14px 0;">No cashier activity</td></tr>';
            } else {
                if (cSection) cSection.style.display = 'block';
                cTableBody.innerHTML = cashiers.slice(0, 10).map((c, i) => {
                    const name = c.cashier_name || c.user_name || 'Unknown';
                    const invs = parseInt(c.invoice_count || c.total_invoices || 0);
                    const items = parseInt(c.items_sold || c.units_sold || 0);
                    const rev = parseFloat(c.net_sales || c.total_sales || 0);
                    const avg = parseFloat(c.avg_bill || c.avg_sale_value || 0);
                    const disc = parseFloat(c.total_discounts || 0);
                    return `<tr>
                        <td>${i + 1}</td>
                        <td class="fw-bold">${esc(name)}</td>
                        <td class="col-right">${invs}</td>
                        <td class="col-right">${fmtNum(items, 0)}</td>
                        <td class="col-right fw-bold">${fmtNum(rev, 2)}</td>
                        <td class="col-right">${fmtNum(avg, 2)}</td>
                        <td class="col-right">${fmtNum(disc, 2)}</td>
                    </tr>`;
                }).join('');
            }
        }

        // 8. Section 6: Branches (superadmin)
        const branches = payload.branches || [];
        const brContainer = document.getElementById('dbBranchList');
        if (brContainer && branches.length) {
            const maxBranchRev = Math.max(...branches.map(b => parseFloat(b.net_sales || 0)), 1);
            brContainer.innerHTML = branches.map((br, i) => {
                const brs = parseFloat(br.net_sales || 0);
                const brw = maxBranchRev > 0 ? Math.round(brs / maxBranchRev * 100) : 0;
                const color = PALETTE[i % PALETTE.length];
                const name = br.branch_name || 'Branch';
                const invs = parseInt(br.invoice_count || 0);
                return `<div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid #f8fafc;">
                    <div style="width:28px;height:28px;border-radius:8px;background:${color}18;color:${color};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/></svg>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:12.5px;font-weight:700;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${esc(name)}</div>
                        <div style="margin-top:4px;height:4px;background:#f1f5f9;border-radius:999px;overflow:hidden;"><div style="height:100%;width:${brw}%;background:${color};border-radius:999px;"></div></div>
                    </div>
                    <div style="text-align:right;flex-shrink:0;">
                        <div style="font-size:13px;font-weight:800;color:#0f172a;">${fmtNum(brs, 0)}</div>
                        <div style="font-size:10.5px;color:#94a3b8;">${invs} inv</div>
                    </div>
                </div>`;
            }).join('');
        }

        // 9. Section 7: Customers
        const cust = payload.customers || {};
        const custKpis = cust.kpis || {};
        const custKpiMap = {
            dkTotalCustomers: fmtNum(custKpis.total_customers || cust.total_customers || 0, 0),
            dkActiveCustomers: fmtNum(custKpis.active_in_period || cust.active_customers || 0, 0),
            dkNewCustomers: fmtNum(custKpis.new_customers || 0, 0),
            dkReturningCustomers: fmtNum(custKpis.returning_customers || 0, 0),
        };
        Object.entries(custKpiMap).forEach(([id, val]) => {
            const el = document.getElementById(id);
            if (el) el.textContent = val;
        });

        const topCust = cust.top_customers || [];
        const tcEl = document.getElementById('dbTopCustomersList');
        if (tcEl) {
            if (!topCust.length) {
                tcEl.innerHTML = '<div class="db-empty" style="padding:10px 0;">No customer sales data</div>';
            } else {
                const maxCustRev = Math.max(...topCust.map(c => parseFloat(c.total_spent || 0)), 1);
                tcEl.innerHTML = topCust.slice(0, 6).map((c, i) => {
                    const cv = parseFloat(c.total_spent || 0);
                    const cw = maxCustRev > 0 ? Math.round(cv / maxCustRev * 100) : 0;
                    const name = c.customer_name || c.name || 'Walk-in';
                    const invs = parseInt(c.invoice_count ?? c.order_count ?? 0);
                    return `<div class="db-list-row">
                        <span class="db-list-rank">${i + 1}</span>
                        <div style="flex:1;overflow:hidden;">
                            <div class="db-list-label">${esc(name)}</div>
                            <div class="db-list-sub">${invs} invoices</div>
                        </div>
                        <div class="db-list-bar-wrap"><div class="db-list-bar" style="width:${cw}%;background:#7c3aed;"></div></div>
                        <span class="db-list-value">${fmtNum(cv, 0)}</span>
                    </div>`;
                }).join('');
            }
        }

        // 10. Section 8: Returns & Discounts
        const rets = payload.returns || {};
        const retSum = rets.summary || {};
        const rCount = parseInt(retSum.total_returns_count || retSum.return_count || rets.return_count || 0);
        const rVal   = parseFloat(retSum.total_returned_amount || retSum.total_return_value || rets.total_return_value || 0);
        const rCountEl = document.getElementById('dkReturnCount');
        const rValEl   = document.getElementById('dkReturnValue');
        if (rCountEl) rCountEl.textContent = fmtNum(rCount, 0);
        if (rValEl)   rValEl.textContent   = fmtNum(rVal, 0);

        const topRet = rets.top_returned_products || rets.top_returned || [];
        const trEl   = document.getElementById('dbTopReturnedList');
        if (trEl) {
            if (!topRet.length) {
                trEl.innerHTML = '<div class="db-empty" style="padding:10px 0;">No returns recorded</div>';
            } else {
                trEl.innerHTML = topRet.slice(0, 4).map(r => {
                    const name = r.product_name || r.name || 'Product';
                    const qty  = parseInt(r.return_qty || r.return_count || 0);
                    const val  = parseFloat(r.return_value || r.return_amount || 0);
                    return `<div class="db-list-row">
                        <div style="flex:1;overflow:hidden;">
                            <div class="db-list-label">${esc(name)}</div>
                            <div class="db-list-sub">${qty} units returned</div>
                        </div>
                        <span class="db-badge db-badge-red">${fmtNum(val, 0)}</span>
                    </div>`;
                }).join('');
            }
        }

        const disc = payload.discounts || {};
        const discSum = disc.summary || {};
        const dAmt = parseFloat(discSum.total_discounts || discSum.total_discount_amount || disc.total_discount_amount || 0);
        const dInvs = parseInt(discSum.discounted_invoices_count || discSum.discounted_invoice_count || disc.discounted_invoice_count || 0);
        const dAmtEl  = document.getElementById('dkTotalDiscounted');
        const dInvsEl = document.getElementById('dkInvoicesDiscounted');
        if (dAmtEl)  dAmtEl.textContent  = fmtNum(dAmt, 0);
        if (dInvsEl) dInvsEl.textContent = fmtNum(dInvs, 0);

        const promos = disc.promotions || disc.top_discount_promos || [];
        const prEl   = document.getElementById('dbTopPromosList');
        if (prEl) {
            if (!promos.length) {
                prEl.innerHTML = '<div class="db-empty" style="padding:10px 0;">No discount data</div>';
            } else {
                prEl.innerHTML = promos.slice(0, 4).map(dp => {
                    const name = dp.discount_name || 'Manual';
                    const usage = parseInt(dp.usage_count || 0);
                    const amt  = parseFloat(dp.total_amount || 0);
                    return `<div class="db-list-row">
                        <div style="flex:1;overflow:hidden;">
                            <div class="db-list-label">${esc(name)}</div>
                            <div class="db-list-sub">${usage} times applied</div>
                        </div>
                        <span class="db-badge db-badge-yellow">${fmtNum(amt, 0)}</span>
                    </div>`;
                }).join('');
            }
        }

        // 11. Section 9: Inventory Health & Stock Aging
        const ops = payload.operations || {};
        const invH = ops.inventory || ops.inventory_health || {};
        const invVal   = parseFloat(invH.cost_valuation ?? (invH.total_inventory_value || 0));
        const lowS     = parseInt(invH.low_stock_count || 0);
        const outS     = parseInt(invH.out_of_stock_count || 0);
        const brStock  = parseFloat(invH.branch_stock_total ?? (invH.stock_quantity ?? (invH.branch_stock_qty || 0)));
        const actLots  = parseInt(invH.active_lots_count || 0);

        const ivEl = document.getElementById('dkInventoryValue');
        const lsEl = document.getElementById('dkLowStockCount');
        const lsgEl = document.getElementById('dkLowStockGrowth');
        const osEl = document.getElementById('dkOutStockCount');
        const osgEl = document.getElementById('dkOutStockGrowth');
        const bsqEl = document.getElementById('dkBranchStockQty');
        const alcEl = document.getElementById('dkActiveLotsCount');

        if (ivEl)  ivEl.textContent  = fmtNum(invVal, 0);
        if (lsEl) {
            lsEl.textContent = fmtNum(lowS, 0);
            lsEl.className = 'db-kpi-card-value ' + (lowS > 0 ? 'is-negative' : '');
        }
        if (lsgEl) {
            lsgEl.className = 'db-kpi-card-growth ' + (lowS > 0 ? 'down' : 'flat');
            lsgEl.textContent = lowS > 0 ? '⚠ Action needed' : 'All good';
        }
        if (osEl) {
            osEl.textContent = fmtNum(outS, 0);
            osEl.className = 'db-kpi-card-value ' + (outS > 0 ? 'is-negative' : '');
        }
        if (osgEl) {
            osgEl.className = 'db-kpi-card-growth ' + (outS > 0 ? 'down' : 'flat');
            osgEl.textContent = outS > 0 ? '🔴 Replenish' : 'None';
        }
        if (bsqEl) bsqEl.textContent = fmtNum(brStock, 0);
        if (alcEl) alcEl.textContent = fmtNum(actLots, 0);

        const aging = invH.aging || invH.stock_aging || ops.stock_aging || {};
        const agingEl = document.getElementById('dbStockAgingList');
        if (agingEl) {
            const agingTiers = [
                { key: 'fresh',    label: '< 30 days (Fresh)',   color: '#16a34a' },
                { key: 'normal',   label: '30–90 days (Normal)',  color: '#2563eb' },
                { key: 'aging',    label: '90–180 days (Aging)',  color: '#d97706' },
                { key: 'old',      label: '180–365 days (Old)',   color: '#ef4444' },
                { key: 'critical', label: '365+ days (Critical)', color: '#9f1239' }
            ];
            let totUnits = 0;
            agingTiers.forEach((t, i) => {
                const item = aging[t.key] || (Array.isArray(aging) ? aging[i] : null);
                const q = typeof item === 'object' && item !== null ? (item.quantity ?? 0) : (item || 0);
                totUnits += parseFloat(q);
            });
            agingEl.innerHTML = agingTiers.map((t, i) => {
                const item = aging[t.key] || (Array.isArray(aging) ? aging[i] : null);
                const qty = typeof item === 'object' && item !== null ? (item.quantity ?? 0) : (item || 0);
                const qNum = parseFloat(qty);
                const pct = totUnits > 0 ? Math.round(qNum / totUnits * 1000) / 10 : 0;
                return `<div class="db-aging-row">
                    <div class="db-aging-label">${t.label}</div>
                    <div class="db-aging-bar-track"><div class="db-aging-bar-fill" style="width:${pct}%;background:${t.color};"></div></div>
                    <div class="db-aging-val">${fmtNum(qNum, 0)} <span style="color:#94a3b8;font-weight:400;">(${pct}%)</span></div>
                </div>`;
            }).join('');
        }

        // 12. Section 10: Financial Position
        const cdBal = parseFloat(ops.cash_drawer?.balance ?? (ops.cash_drawer_balance || 0));
        const ciBal = parseFloat(ops.cash_in_hand || 0);
        const bkBal = parseFloat(ops.banking?.total_bank_balance ?? (ops.bank_balances?.total ?? (ops.bank_total || 0)));
        const spBal = parseFloat(ops.payables?.total_payables ?? (ops.supplier_payables ?? (ops.accounts_payable || 0)));
        const pcBal = parseFloat(ops.petty_cash_balance || 0);
        const crOut = parseFloat(custKpis.total_credit_outstanding ?? (ops.receivables?.total_receivable ?? (ops.outstanding_credit || 0)));
        const crCol = parseFloat(custKpis.total_collections_today ?? (ops.receivables?.period_collections ?? (ops.customer_collections || 0)));

        const finMap = {
            dkCashDrawer: fmtNum(cdBal, 0),
            dkCashInHand: fmtNum(ciBal, 0),
            dkBankBalances: fmtNum(bkBal, 0),
            dkSupplierPayables: fmtNum(spBal, 0),
            dkPettyCash: fmtNum(pcBal, 0),
            dkCreditOutstanding: fmtNum(crOut, 0),
            dkCollectionsToday: fmtNum(crCol, 0),
        };
        Object.entries(finMap).forEach(([id, val]) => {
            const el = document.getElementById(id);
            if (el) el.textContent = val;
        });

        const bankList = ops.banking?.accounts || ops.bank_balances?.accounts || ops.bank_accounts || [];
        const bankTbody = document.getElementById('dbBankAccountsTableBody');
        if (bankTbody) {
            if (!bankList.length) {
                bankTbody.innerHTML = '<tr><td colspan="3" class="db-empty" style="padding:14px 0;">No active bank accounts</td></tr>';
            } else {
                bankTbody.innerHTML = bankList.map(ba => {
                    const bName = ba.bank_name || 'Bank';
                    const aNum  = ba.account_number || '—';
                    const bal   = parseFloat(ba.balance || 0);
                    return `<tr>
                        <td class="fw-bold">${esc(bName)}</td>
                        <td>${esc(aNum)}</td>
                        <td class="col-right fw-bold ${bal < 0 ? 'is-negative' : ''}">${fmtNum(bal, 2)}</td>
                    </tr>`;
                }).join('');
            }
        }

        // 13. Section 11: Recent Sales Activity
        const recent = payload.recent_activity || payload.recentSales || [];
        const rsEl   = document.getElementById('dbRecentSalesList');
        if (rsEl) {
            if (!recent.length) {
                rsEl.innerHTML = '<div class="db-empty">No sales recorded yet</div>';
            } else {
                rsEl.innerHTML = recent.slice(0, 8).map(r => {
                    const inv   = r.invoice_no || r.invoice_number || 'INV';
                    const cName = r.customer_name || 'Walk-in';
                    const cash  = r.cashier_name || '';
                    const time  = r.created_at ? r.created_at.substring(11, 16) : '';
                    const amt   = parseFloat(r.total_amount ?? r.amount ?? 0);
                    const st    = r.payment_status || r.status || 'paid';
                    return `<div class="db-activity-row">
                        <div class="db-activity-icon">
                            <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/><path d="M10 9H8"/></svg>
                        </div>
                        <div class="db-activity-main">
                            <div class="db-activity-title">${esc(inv)} — ${esc(cName)}</div>
                            <div class="db-activity-sub">${esc(cash)} · ${time}</div>
                        </div>
                        <div style="text-align:right;">
                            <div class="db-activity-amount">LKR ${fmtNum(amt, 2)}</div>
                            <div class="db-activity-time"><span class="db-badge db-badge-green">${esc(st)}</span></div>
                        </div>
                    </div>`;
                }).join('');
            }
        }

        // 14. Last updated timestamp
        const ts2 = document.getElementById('dbLastUpdate');
        if (ts2) ts2.textContent = 'Live · ' + new Date().toLocaleTimeString('en-LK', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }

    function handleDashboardPulse(e) {
        const dot = document.getElementById('dbLiveDot');
        if (dot) {
            dot.classList.remove('disconnected');
            dot.textContent = 'Live';
            dot.classList.add('syncing');
            setTimeout(() => dot.classList.remove('syncing'), 450);
        }
    }

    function handleDashboardError(e) {
        const dot = document.getElementById('dbLiveDot');
        if (dot) {
            dot.classList.add('disconnected');
            dot.textContent = 'Syncing...';
        }
    }

    // Attach listeners on both window and document
    window.addEventListener('curtiss:dashboard:update', handleDashboardUpdate);
    document.addEventListener('curtiss:dashboard:update', handleDashboardUpdate);
    window.addEventListener('curtiss:dashboard:pulse', handleDashboardPulse);
    document.addEventListener('curtiss:dashboard:pulse', handleDashboardPulse);
    window.addEventListener('curtiss:dashboard:error', handleDashboardError);
    document.addEventListener('curtiss:dashboard:error', handleDashboardError);

    // ── Init ──────────────────────────────────────────────────────────────
    function init() {
        buildMainChart(activeMetric);
        buildHourlyChart();
        buildPaymentDonut();
        if (typeof CurtissDashboard !== 'undefined') {
            CurtissDashboard.on('update', handleDashboardUpdate);
            CurtissDashboard.on('pulse', handleDashboardPulse);
            CurtissDashboard.on('error', handleDashboardError);
            CurtissDashboard.init({
                apiEndpoint: 'dashboard_api.php',
                activeBranchId: PHP.activeBranchId,
                preset: PHP.preset,
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>