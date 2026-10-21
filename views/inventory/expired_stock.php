<?php
/**
 * Curtiss POS - Expired Stock & Batch Management View
 * Fixed Viewport Layout with Internally Scrollable Zebra Striped Table
 */
?>
<link rel="stylesheet" href="assets/css/curtiss_select.css">
<script src="assets/js/curtiss_select.js"></script>

<!-- Floating Top Header Bar Top Right Action Controls -->
<div id="curtiss-floating-header-right" style="position: fixed; top: 16px; right: 20px; z-index: 1050; display: flex; align-items: center; gap: 8px;">
    <button type="button" onclick="openBulkWriteOffModal()" class="inv-btn" id="bulkWriteOffBtn" style="display: none; height: 34px; padding: 0 16px; border-radius: 999px; font-size: 12px; font-weight: 700; color: #dc2626; background: #fef2f2; border: 1px solid #fca5a5; cursor: pointer; transition: all 0.2s ease;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
        Write-off Selected (<span id="selectedCountSpan">0</span>)
    </button>
    <a href="reports" class="inv-btn" style="height: 34px; padding: 0 16px; border-radius: 999px; font-size: 12px; font-weight: 700; color: #0f172a; background: #ffffff; border: 1px solid #e2e8f0; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
        Expiry Reports
    </a>
</div>

<style>
.exp-root {
    width: 100%;
    max-width: 100%;
    height: calc(100vh - 110px);
    display: flex;
    flex-direction: column;
    box-sizing: border-box;
    overflow: hidden;
    padding-bottom: 8px;
    font-family: inherit;
}

/* KPI Summary Cards */
.exp-kpi-bar {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-bottom: 12px;
    flex-shrink: 0;
}
.exp-kpi-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(226, 232, 240, 0.9);
    border-radius: 14px;
    padding: 12px 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.02);
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.exp-kpi-val {
    font-size: 1.25rem;
    font-weight: 900;
    line-height: 1.2;
}
.exp-kpi-lbl {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #64748b;
    margin-top: 2px;
}

/* Table styling with sticky header & zebra striping */
.prod-table-panel {
    background: #ffffff;
    border: 1px solid rgba(226, 232, 240, 0.9);
    border-radius: 14px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.03);
    overflow: hidden;
}
.prod-table-scroll {
    overflow-y: auto;
    flex: 1;
}
.prod-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12.5px;
}
.prod-table thead th {
    height: 40px;
    padding: 0 14px;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #64748b;
    position: sticky;
    top: 0;
    z-index: 10;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    text-align: left;
}
.prod-table tbody tr {
    height: 48px;
    min-height: 48px;
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.15s ease;
}
.prod-table tbody tr:nth-child(even) { background: rgba(248, 250, 252, 0.57); }
.prod-table tbody tr:nth-child(odd)  { background: #ffffff; }
.prod-table tbody tr:hover            { background: #f1f5f9 !important; }

.prod-table td {
    height: 48px;
    padding: 0 14px;
    vertical-align: middle;
    color: #1e293b;
    box-sizing: border-box;
}
.col-right { text-align: right !important; }
.col-center { text-align: center !important; }

/* Status Badges - 3 Tier Expiry Visualization (Red: 0-30d, Yellow: 30-60d, Green: >60d) */
.badge-expired {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 999px;
    background: #fee2e2;
    color: #b91c1c;
    border: 1px solid #fca5a5;
    font-weight: 800;
    font-size: 11px;
}
.badge-near-30 {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 999px;
    background: #fee2e2;
    color: #b91c1c;
    border: 1px solid #fca5a5;
    font-weight: 800;
    font-size: 11px;
}
.badge-near-60 {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 999px;
    background: #fef3c7;
    color: #b45309;
    border: 1px solid #fde68a;
    font-weight: 800;
    font-size: 11px;
}
.badge-safe {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 999px;
    background: #dcfce7;
    color: #15803d;
    border: 1px solid #86efac;
    font-weight: 800;
    font-size: 11px;
}

.act-btn {
    height: 28px;
    min-height: 28px;
    line-height: 26px;
    padding: 0 12px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: all 0.15s ease;
}
.act-btn-danger {
    color: #dc2626;
    background: #fef2f2;
    border-color: #fca5a5;
}
.act-btn-danger:hover {
    background: #fee2e2;
}

/* Modal */
.exp-modal-backdrop {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(4px);
    z-index: 2000;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}
.exp-modal-card {
    background: #ffffff;
    border-radius: 18px;
    width: 100%;
    max-width: 440px;
    padding: 24px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    border: 1px solid #e2e8f0;
}
</style>

<div class="exp-root">
    <!-- Header Title & Inline Filter Controls (Rule 3, Rule 5) -->
    <div style="flex-shrink: 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 0.85rem;">
        <div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #dc2626;"></div>
                <h1 style="margin: 0; font-size: 1.35rem; font-weight: 800; color: #0f172a;">Expired Stock & Batch Management</h1>
            </div>
        </div>

        <!-- Inline Filter Selects (Height: 34px) -->
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Filter:</span>
                <select id="filterTypeSelect" onchange="applyFilters()" style="height: 34px; min-height: 34px; padding: 0 14px; border-radius: 999px; font-size: 12px; font-weight: 700; color: #334155; background: #ffffff; border: 1px solid #cbd5e1;">
                    <option value="all" <?= ($filters['filter_type'] === 'all') ? 'selected' : '' ?>>All Expiring Batches (Nearest First)</option>
                    <option value="near_30" <?= in_array($filters['filter_type'], ['near_30', 'red'], true) ? 'selected' : '' ?>>🔴 0–30 Days (Critical & Expired)</option>
                    <option value="near_60" <?= in_array($filters['filter_type'], ['near_60', 'yellow'], true) ? 'selected' : '' ?>>🟡 30–60 Days (Warning)</option>
                    <option value="near_90" <?= in_array($filters['filter_type'], ['near_90', 'safe', 'green', 'others'], true) ? 'selected' : '' ?>>🟢 Others (>60 Days Safe)</option>
                    <option value="expired" <?= ($filters['filter_type'] === 'expired') ? 'selected' : '' ?>>⚠️ Expired Only</option>
                </select>
            </div>

            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Brand:</span>
                <select id="filterBrandSelect" class="curtiss-select-enhance" onchange="applyFilters()" style="height: 34px; min-height: 34px; min-width: 140px;">
                    <option value="">All Brands</option>
                    <?php foreach ($brands as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= ($filters['brand_id'] === (int)$b['id']) ? 'selected' : '' ?>><?= View::escape($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Category:</span>
                <select id="filterCategorySelect" class="curtiss-select-enhance" onchange="applyFilters()" style="height: 34px; min-height: 34px; min-width: 140px;">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($filters['category_id'] === (int)$c['id']) ? 'selected' : '' ?>><?= View::escape($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <!-- KPI Summary Bar (3 Color Tiers: Red 0-30d, Yellow 30-60d, Green >60d) -->
    <div class="exp-kpi-bar">
        <div class="exp-kpi-card" style="border-left: 4px solid #dc2626;">
            <div>
                <div class="exp-kpi-val" style="color: #dc2626;"><?= number_format($summary['red_lots_count'] ?? ($summary['expired_lots_count'] + $summary['near_30_lots_count'])) ?> Batches</div>
                <div class="exp-kpi-lbl" style="color: #b91c1c; font-weight: 700;">🔴 0–30 Days & Expired</div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 0.88rem; font-weight: 800; color: #991b1b;"><?= number_format($summary['red_units_count'] ?? ($summary['expired_units_count'] + $summary['near_30_units_count']), 2) ?> units</div>
                <div style="font-size: 0.72rem; color: #64748b;">LKR <?= number_format($summary['red_cost_valuation'] ?? ($summary['expired_cost_valuation'] + $summary['near_30_cost_valuation']), 2) ?></div>
            </div>
        </div>

        <div class="exp-kpi-card" style="border-left: 4px solid #d97706;">
            <div>
                <div class="exp-kpi-val" style="color: #d97706;"><?= number_format($summary['yellow_lots_count'] ?? $summary['near_60_lots_count']) ?> Batches</div>
                <div class="exp-kpi-lbl" style="color: #b45309; font-weight: 700;">🟡 30–60 Days (Warning)</div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 0.88rem; font-weight: 800; color: #92400e;"><?= number_format($summary['yellow_units_count'] ?? ($summary['near_60_units_count'] ?? 0), 2) ?> units</div>
                <div style="font-size: 0.72rem; color: #64748b;">LKR <?= number_format($summary['yellow_cost_valuation'] ?? ($summary['near_60_cost_valuation'] ?? 0), 2) ?></div>
            </div>
        </div>

        <div class="exp-kpi-card" style="border-left: 4px solid #16a34a;">
            <div>
                <div class="exp-kpi-val" style="color: #16a34a;"><?= number_format($summary['green_lots_count'] ?? $summary['near_90_lots_count']) ?> Batches</div>
                <div class="exp-kpi-lbl" style="color: #15803d; font-weight: 700;">🟢 Others (> 60 Days Safe)</div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 0.88rem; font-weight: 800; color: #166534;"><?= number_format($summary['green_units_count'] ?? 0, 2) ?> units</div>
                <div style="font-size: 0.72rem; color: #64748b;">LKR <?= number_format($summary['green_cost_valuation'] ?? 0, 2) ?></div>
            </div>
        </div>

        <div class="exp-kpi-card" style="border-left: 4px solid #475569;">
            <div>
                <div class="exp-kpi-val" style="color: #0f172a;"><?= number_format($summary['expired_lots_count']) ?> Batches</div>
                <div class="exp-kpi-lbl">⚠️ Expired (Write-Off Needed)</div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 0.88rem; font-weight: 800; color: #dc2626;"><?= number_format($summary['expired_units_count'], 2) ?> units</div>
                <div style="font-size: 0.72rem; color: #64748b;">LKR <?= number_format($summary['expired_cost_valuation'], 2) ?></div>
            </div>
        </div>
    </div>

    <!-- Alerts -->
    <?php if (!empty($successMsg)): ?>
        <div style="flex-shrink: 0; padding: 10px 16px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; color: #16a34a; font-weight: 700; font-size: 12.5px; margin-bottom: 10px;">
            ✓ <?= View::escape($successMsg) ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($errorMsg)): ?>
        <div style="flex-shrink: 0; padding: 10px 16px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; color: #dc2626; font-weight: 700; font-size: 12.5px; margin-bottom: 10px;">
            ✕ <?= View::escape($errorMsg) ?>
        </div>
    <?php endif; ?>

    <!-- Table Panel & Scroll Container -->
    <div class="prod-table-panel" style="flex: 1; min-height: 0; display: flex; flex-direction: column;">
        <div class="prod-table-scroll">
            <table class="prod-table" id="expiredLotsTable">
                <thead>
                    <tr>
                        <th class="col-center" style="width: 40px;">
                            <input type="checkbox" id="selectAllLots" onclick="toggleSelectAll(this)" style="cursor: pointer;">
                        </th>
                        <th style="text-align: left;">Product Name</th>
                        <th style="text-align: left;">Batch / Lot No</th>
                        <th style="text-align: left;">Expiry Date</th>
                        <th style="text-align: left;">Shelf Life Status</th>
                        <th class="col-right" style="text-align: right;">Remaining Qty</th>
                        <th class="col-right" style="text-align: right;">Unit Cost (LKR)</th>
                        <th class="col-right" style="text-align: right;">Estimated Loss (LKR)</th>
                        <th class="col-right" style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 45px 14px; color: #94a3b8;">
                                <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                                    <div style="font-size: 0.9rem; font-weight: 700; color: #64748b;">No expiring or expired batches found matching criteria.</div>
                                    <div style="font-size: 0.75rem; color: #94a3b8;">All inventory stock lots are healthy or outside the warning threshold.</div>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $it): 
                            $isExpired = ($it['days_remaining'] < 0);
                            $days = abs($it['days_remaining']);
                            $daysRemaining = (int)$it['days_remaining'];

                            // 3-Color mapping:
                            // 0-30 days & expired: Red (#dc2626)
                            // 30-60 days: Yellow (#d97706)
                            // >60 days: Green (#16a34a)
                            if ($daysRemaining <= 30) {
                                $tierColor = '#dc2626';
                                $borderStyle = 'border-left: 4px solid #dc2626;';
                            } elseif ($daysRemaining <= 60) {
                                $tierColor = '#d97706';
                                $borderStyle = 'border-left: 4px solid #d97706;';
                            } else {
                                $tierColor = '#16a34a';
                                $borderStyle = 'border-left: 4px solid #16a34a;';
                            }
                        ?>
                            <tr style="<?= $borderStyle ?>">
                                <td class="col-center" onclick="event.stopPropagation();">
                                    <input type="checkbox" class="lot-check" value="<?= $it['lot_id'] ?>" onchange="updateSelectedCount()" style="cursor: pointer;">
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: #0f172a; font-size: 13px;"><?= View::escape($it['product_name']) ?></div>
                                    <div style="font-size: 11px; color: #64748b; font-family: monospace;">SKU: <?= View::escape($it['sku'] ?: '—') ?></div>
                                </td>
                                <td>
                                    <div style="font-weight: 700; font-family: monospace; color: #334155; font-size: 12px;"><?= View::escape($it['batch_number'] ?: $it['lot_number']) ?></div>
                                    <?php if ($it['batch_number'] && $it['batch_number'] !== $it['lot_number']): ?>
                                        <div style="font-size: 10px; color: #94a3b8; font-family: monospace;"><?= View::escape($it['lot_number']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="font-weight: 800; font-family: monospace; color: <?= $tierColor ?>; font-size: 12px;">
                                        <?= View::escape($it['expiry_date']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($isExpired): ?>
                                        <span class="badge-expired">
                                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                            Expired (<?= $days ?>d ago)
                                        </span>
                                    <?php elseif ($daysRemaining <= 30): ?>
                                        <span class="badge-near-30">
                                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                            0–30 Days (<?= $days ?>d left)
                                        </span>
                                    <?php elseif ($daysRemaining <= 60): ?>
                                        <span class="badge-near-60">
                                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                            30–60 Days (<?= $days ?>d left)
                                        </span>
                                    <?php else: ?>
                                        <span class="badge-safe">
                                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                            60+ Days (<?= $days ?>d left)
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="col-right" style="font-weight: 800; font-family: monospace;">
                                    <?= number_format((float)$it['remaining_quantity'], 2) ?>
                                </td>
                                <td class="col-right" style="font-family: monospace;">
                                    <?= number_format((float)$it['cost'], 2) ?>
                                </td>
                                <td class="col-right" style="font-weight: 800; font-family: monospace; color: <?= $tierColor ?>;">
                                    <?= number_format((float)$it['total_cost_value'], 2) ?>
                                </td>
                                <td class="col-right" onclick="event.stopPropagation();">
                                    <button type="button" class="act-btn act-btn-danger" onclick="openWriteOffModal(<?= $it['lot_id'] ?>, '<?= htmlspecialchars(addslashes($it['product_name']), ENT_QUOTES) ?>', <?= (float)$it['remaining_quantity'] ?>, '<?= $it['expiry_date'] ?>', '<?= htmlspecialchars(addslashes($it['batch_number'] ?: $it['lot_number']), ENT_QUOTES) ?>')">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                        Write-Off
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Single Lot Write-Off Modal -->
<div id="writeOffModal" class="exp-modal-backdrop">
    <div class="exp-modal-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 10px; border-bottom: 1px solid #f1f5f9;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #dc2626;"></div>
                <h3 style="margin: 0; font-size: 1.1rem; font-weight: 800; color: #0f172a;">Write-Off Stock Batch</h3>
            </div>
            <button type="button" onclick="closeWriteOffModal()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: #94a3b8;">✕</button>
        </div>

        <form method="POST" action="expired_stock" id="singleWriteOffForm" onsubmit="handleFormSubmit(this)">
            <input type="hidden" name="action" value="write_off_single">
            <input type="hidden" name="lot_id" id="modalLotId">

            <div style="margin-bottom: 12px;">
                <div style="font-size: 0.8rem; color: #64748b; font-weight: 600;">Product:</div>
                <div id="modalProductName" style="font-size: 0.92rem; font-weight: 800; color: #0f172a;">—</div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 14px;">
                <div>
                    <div style="font-size: 0.75rem; color: #64748b; font-weight: 600;">Batch Reference:</div>
                    <div id="modalBatchRef" style="font-size: 0.85rem; font-weight: 700; font-family: monospace;">—</div>
                </div>
                <div>
                    <div style="font-size: 0.75rem; color: #64748b; font-weight: 600;">Expiry Date:</div>
                    <div id="modalExpiryDate" style="font-size: 0.85rem; font-weight: 800; color: #dc2626; font-family: monospace;">—</div>
                </div>
            </div>

            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #0f172a; margin-bottom: 4px;">Write-Off Quantity *</label>
                <input type="number" step="any" min="0.01" name="quantity" id="modalQuantity" class="f-input" required style="width: 100%; height: 38px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 10px; font-weight: 700; font-family: monospace;">
                <span id="modalMaxQtyText" style="font-size: 0.72rem; color: #64748b; display: block; margin-top: 2px;"></span>
            </div>

            <div style="margin-bottom: 18px;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #0f172a; margin-bottom: 4px;">Reason / Write-off Note *</label>
                <input type="text" name="reason" id="modalReason" class="f-input" value="Expired batch written off from inventory" required style="width: 100%; height: 38px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 10px; font-size: 0.85rem;">
                <span style="font-size: 0.72rem; color: #94a3b8; display: block; margin-top: 2px;">Posts Dr 1250 (Damaged Loss) & Cr 1200 (Inventory Asset)</span>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeWriteOffModal()" class="act-btn" style="height: 36px; padding: 0 16px;">Cancel</button>
                <button type="submit" class="act-btn act-btn-danger" style="height: 36px; padding: 0 20px;">Confirm Write-Off</button>
            </div>
        </form>
    </div>
</div>

<!-- Bulk Write-Off Modal -->
<div id="bulkWriteOffModal" class="exp-modal-backdrop">
    <div class="exp-modal-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 10px; border-bottom: 1px solid #f1f5f9;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #dc2626;"></div>
                <h3 style="margin: 0; font-size: 1.1rem; font-weight: 800; color: #0f172a;">Bulk Write-Off Expired Batches</h3>
            </div>
            <button type="button" onclick="closeBulkWriteOffModal()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: #94a3b8;">✕</button>
        </div>

        <form method="POST" action="expired_stock" id="bulkWriteOffForm" onsubmit="handleFormSubmit(this)">
            <input type="hidden" name="action" value="write_off_batch">
            <div id="bulkLotIdsContainer"></div>

            <div style="margin-bottom: 14px; padding: 12px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; color: #991b1b; font-size: 0.85rem;">
                ⚠️ You are about to write off <strong><span id="bulkModalCount">0</span> selected batches</strong> in their entirety. Their full remaining quantities will be moved to Damaged Stock and written off to Loss (COA 1250).
            </div>

            <div style="margin-bottom: 18px;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #0f172a; margin-bottom: 4px;">Reason / Disposal Authorization *</label>
                <input type="text" name="batch_reason" class="f-input" value="Bulk write-off of expired stock batches" required style="width: 100%; height: 38px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 10px; font-size: 0.85rem;">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeBulkWriteOffModal()" class="act-btn" style="height: 36px; padding: 0 16px;">Cancel</button>
                <button type="submit" class="act-btn act-btn-danger" style="height: 36px; padding: 0 20px;">Execute Bulk Write-Off</button>
            </div>
        </form>
    </div>
</div>

<script>
function applyFilters() {
    const fType = document.getElementById('filterTypeSelect')?.value || 'all';
    const brand = document.getElementById('filterBrandSelect')?.value || '';
    const cat = document.getElementById('filterCategorySelect')?.value || '';
    
    let url = 'expired_stock?filter_type=' + encodeURIComponent(fType);
    if (brand) url += '&brand_id=' + encodeURIComponent(brand);
    if (cat) url += '&category_id=' + encodeURIComponent(cat);
    window.location.href = url;
}

function toggleSelectAll(master) {
    document.querySelectorAll('.lot-check').forEach(cb => {
        cb.checked = master.checked;
    });
    updateSelectedCount();
}

function updateSelectedCount() {
    const checked = document.querySelectorAll('.lot-check:checked');
    const count = checked.length;
    const btn = document.getElementById('bulkWriteOffBtn');
    const span = document.getElementById('selectedCountSpan');
    if (span) span.innerText = count;
    if (btn) btn.style.display = count > 0 ? 'inline-flex' : 'none';
}

function openWriteOffModal(lotId, prodName, remQty, expDate, batchRef) {
    document.getElementById('modalLotId').value = lotId;
    document.getElementById('modalProductName').innerText = prodName;
    document.getElementById('modalBatchRef').innerText = batchRef;
    document.getElementById('modalExpiryDate').innerText = expDate;
    document.getElementById('modalQuantity').value = remQty;
    document.getElementById('modalQuantity').max = remQty;
    document.getElementById('modalMaxQtyText').innerText = `Maximum available in lot: ${remQty} units`;
    document.getElementById('writeOffModal').style.display = 'flex';
}

function closeWriteOffModal() {
    document.getElementById('writeOffModal').style.display = 'none';
}

function openBulkWriteOffModal() {
    const checked = document.querySelectorAll('.lot-check:checked');
    if (!checked.length) return;

    const container = document.getElementById('bulkLotIdsContainer');
    container.innerHTML = '';
    checked.forEach(cb => {
        const inp = document.createElement('input');
        inp.type = 'hidden';
        inp.name = 'lot_ids[]';
        inp.value = cb.value;
        container.appendChild(inp);
    });

    document.getElementById('bulkModalCount').innerText = checked.length;
    document.getElementById('bulkWriteOffModal').style.display = 'flex';
}

function closeBulkWriteOffModal() {
    document.getElementById('bulkWriteOffModal').style.display = 'none';
}

function handleFormSubmit(form) {
    const btn = form.querySelector('button[type="submit"]');
    if (btn) {
        btn.disabled = true;
        btn.innerText = 'Processing...';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.curtiss-select-enhance').forEach(el => {
        if (window.CurtissSearchableSelect) {
            CurtissSearchableSelect.attach(el);
        }
    });

    if (window.CurtissNav && typeof CurtissNav.enablePageSearch === 'function') {
        CurtissNav.enablePageSearch({
            tableId: 'expiredLotsTable',
            excludeSelector: 'td:last-child, td:first-child'
        });
    }
});
</script>
