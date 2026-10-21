<?php
/**
 * Curtiss POS - Branch Inventory Master View
 * Adheres strictly to Curtiss POS Design Standards:
 * Fixed Viewport, Zebra Table, Inline 34px Filter Selects, Sticky Headers,
 * Floating Bottom Nav Realtime Search, Pagination (25, 50, 75, 100, 500),
 * Dual Slide-in Lot Drawer & Enter-Key Form Navigation.
 */
?>
<link rel="stylesheet" href="assets/css/curtiss_select.css">
<script src="assets/js/curtiss_select.js"></script>

<!-- Floating Top-Right Action Header (Action Buttons Only) -->
<div id="curtiss-floating-header-right" style="position: fixed; top: 16px; right: 20px; z-index: 1050; display: flex; align-items: center; gap: 8px;">
    <?php if (!Auth::isSpecialUser()): ?>
    <a href="inventory_lots" class="inv-btn inv-btn-ghost">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
        Price & Cost Lots
    </a>
    <a href="inventory_ledger" class="inv-btn inv-btn-ghost">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg>
        Stock Movement Ledger
    </a>
    <?php endif; ?>

    <button type="button" class="inv-btn inv-btn-ghost" onclick="exportInventoryCsv()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Export CSV
    </button>

    <?php if (Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER) && !Auth::isSpecialUser()): ?>
    <button type="button" class="inv-btn inv-btn-primary" onclick="openAddLotModal()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Stock Lot
    </button>
    <?php endif; ?>
</div>

<style>
/* ── Typography & Standard Buttons ── */
.inv-title {
    font-size: 1.35rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.025em;
    line-height: 1.2;
    margin: 0;
}
.inv-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 0 16px;
    height: 34px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    border: 1px solid transparent;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.18s ease;
    white-space: nowrap;
    font-family: inherit;
    box-sizing: border-box;
}
.inv-btn-ghost {
    background: rgba(255,255,255,0.94);
    border-color: rgba(226,232,240,0.9);
    color: #0f172a;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    backdrop-filter: blur(8px);
}
.inv-btn-ghost:hover {
    background: #ffffff;
    border-color: #cbd5e1;
    transform: translateY(-1px);
}
.inv-btn-primary {
    background: #0f172a;
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(15,23,42,0.25);
}
.inv-btn-primary:hover {
    background: #1e293b;
    transform: translateY(-1px);
}

/* ── Standard Fixed Viewport & Zebra Striped Table ── */
.prod-table-panel {
    background: rgba(255, 255, 255, 0.94);
    backdrop-filter: blur(16px);
    border: 1px solid rgba(226, 232, 240, 0.9);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.05);
}
.prod-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12.5px;
    table-layout: auto;
}
.prod-table thead th {
    position: sticky;
    top: 0;
    z-index: 10;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    padding: 10px 14px;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #64748b;
    white-space: nowrap;
}
.prod-table tbody tr {
    height: 48px;
    min-height: 48px;
    transition: background 0.12s ease;
}
.prod-table tbody tr:nth-child(even) { background: rgba(248, 250, 252, 0.57); }
.prod-table tbody tr:nth-child(odd)  { background: #ffffff; }
.prod-table tbody tr:hover            { background: #f1f5f9 !important; }
.prod-table td {
    height: 48px;
    padding: 0 14px;
    color: #1e293b;
    vertical-align: middle;
    border-bottom: 1px solid rgba(241, 245, 249, 0.8);
    box-sizing: border-box;
}

/* Explicit Alignment Rules */
.prod-table th.col-left, .prod-table td.col-left   { text-align: left; }
.prod-table th.col-right, .prod-table td.col-right { text-align: right; }
.prod-table th.col-center, .prod-table td.col-center { text-align: center; }

/* In-Cell Badges & Pills */
.inv-code-pill {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 11px;
    background: #f1f5f9;
    color: #334155;
    padding: 2px 6px;
    border-radius: 6px;
    font-weight: 600;
}
.stock-badge {
    height: 28px;
    min-height: 28px;
    line-height: 26px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    padding: 0 12px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    box-sizing: border-box;
    white-space: nowrap;
    vertical-align: middle;
}
.stock-badge-in  { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
.stock-badge-low { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
.stock-badge-out { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

.lots-act-link {
    height: 28px;
    min-height: 28px;
    line-height: 26px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    padding: 0 12px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    background: #f8fafc;
    color: #0f172a;
    border: 1px solid #cbd5e1;
    cursor: pointer;
    transition: all 0.15s ease;
    font-family: inherit;
    box-sizing: border-box;
    white-space: nowrap;
    vertical-align: middle;
}
.lots-act-link:hover {
    background: #0f172a;
    color: #ffffff;
    border-color: #0f172a;
}

/* ── Slide-in Drawer ── */
.inv-drawer-backdrop {
    position: fixed; inset: 0; background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);
    z-index: 9990; opacity: 0; visibility: hidden; transition: all 0.25s ease;
}
.inv-drawer-backdrop.active { opacity: 1; visibility: visible; }
.inv-dual-drawer {
    position: fixed; top: 0; right: 0; bottom: 0; width: 860px; max-width: 95vw;
    background: rgba(255, 255, 255, 0.98); backdrop-filter: blur(20px);
    border-left: 1px solid rgba(226, 232, 240, 0.9); box-shadow: -10px 0 40px rgba(0,0,0,0.15);
    z-index: 9991; transform: translateX(100%); transition: transform 0.28s cubic-bezier(0.16, 1, 0.3, 1);
    display: flex; flex-direction: column;
}
.inv-drawer-backdrop.active .inv-dual-drawer { transform: translateX(0); }
.inv-drawer-header {
    padding: 16px 20px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; background: #ffffff;
}
.inv-drawer-body { padding: 18px; overflow-y: auto; flex: 1; display: flex; flex-direction: column; gap: 14px; }
.inv-dual-grid {
    display: grid;
    grid-template-columns: 350px 1fr;
    flex: 1;
    overflow: hidden;
}
@media (max-width: 768px) {
    .inv-dual-grid { grid-template-columns: 1fr; overflow-y: auto; }
}

/* ── Modal & Fields ── */
.curtiss-modal-backdrop {
    position: fixed; inset: 0; background: rgba(15, 23, 42, 0.45); backdrop-filter: blur(8px);
    z-index: 10000; display: flex; align-items: center; justify-content: center; padding: 20px;
    opacity: 0; visibility: hidden; transition: all 0.2s ease;
}
.curtiss-modal-backdrop.active { opacity: 1; visibility: visible; }
.curtiss-modal {
    background: rgba(255, 255, 255, 0.98); backdrop-filter: blur(20px);
    border: 1px solid rgba(226, 232, 240, 0.9); border-radius: 20px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); width: 100%; max-width: 520px; padding: 24px;
    max-height: calc(100vh - 40px); overflow-y: auto;
    transform: scale(0.95); transition: transform 0.2s ease;
}
.curtiss-modal-backdrop.active .curtiss-modal { transform: scale(1); }
.inv-field { display: flex; flex-direction: column; gap: 4px; }
.inv-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; }
.inv-input, .inv-select {
    width: 100%; height: 38px; padding: 0 12px; border: 1px solid #cbd5e1; border-radius: 10px;
    font-size: 13px; font-family: inherit; color: #0f172a; background: #ffffff; box-sizing: border-box;
}
.inv-input:focus, .inv-select:focus {
    outline: none; border-color: #0f172a; box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.08);
}
.inv-alert {
    display: flex; align-items: center; gap: 10px; padding: 10px 14px;
    border-radius: 10px; font-size: 12px; font-weight: 600; margin-bottom: 10px;
}
.inv-alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
.inv-alert-danger  { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
</style>

<!-- Fixed Screen Viewport Container -->
<div style="width: 100%; max-width: 100%; height: calc(100vh - 110px); display: flex; flex-direction: column; box-sizing: border-box; overflow: hidden; padding-bottom: 8px;">

    <!-- Page Header (Headline Only + Inline Filter Dropdowns Above Table Right Corner - Rule 3 & 5) -->
    <div class="inv-header" style="flex-shrink: 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 0.85rem;">
        <div style="display: flex; align-items: center; gap: 8px;">
            <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
            <h1 class="inv-title">Branch Inventory</h1>
        </div>

        <!-- Inline Filter Controls Above Table Right Corner (Height: 34px) -->
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-left: auto;">
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Status:</span>
                <select id="filterStockStatus" class="curtiss-select-enhance" onchange="applyInventoryFilter()" style="height: 34px; min-height: 34px; padding: 0 14px; border-radius: 999px; font-size: 12px; font-weight: 700; color: #334155; background: rgba(255,255,255,0.95); border: 1px solid rgba(203,213,225,0.9); box-shadow: 0 2px 8px rgba(0,0,0,0.04); min-width: 140px;">
                    <option value="">All Stock Statuses</option>
                    <option value="in_stock" <?= ($filters['stock_status'] === 'in_stock') ? 'selected' : '' ?>>In Stock</option>
                    <option value="low_stock" <?= ($filters['stock_status'] === 'low_stock') ? 'selected' : '' ?>>Low Stock</option>
                    <option value="out_of_stock" <?= ($filters['stock_status'] === 'out_of_stock') ? 'selected' : '' ?>>Out of Stock</option>
                </select>
            </div>
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Brand:</span>
                <select id="filterBrand" class="curtiss-select-enhance" onchange="applyInventoryFilter()" style="height: 34px; min-height: 34px; padding: 0 14px; border-radius: 999px; font-size: 12px; font-weight: 700; color: #334155; background: rgba(255,255,255,0.95); border: 1px solid rgba(203,213,225,0.9); box-shadow: 0 2px 8px rgba(0,0,0,0.04); min-width: 150px;">
                    <option value="">All Brands</option>
                    <?php foreach ($brands as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= ((int)$filters['brand_id'] === (int)$b['id']) ? 'selected' : '' ?>><?= View::escape($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Category:</span>
                <select id="filterCategory" class="curtiss-select-enhance" onchange="applyInventoryFilter()" style="height: 34px; min-height: 34px; padding: 0 14px; border-radius: 999px; font-size: 12px; font-weight: 700; color: #334155; background: rgba(255,255,255,0.95); border: 1px solid rgba(203,213,225,0.9); box-shadow: 0 2px 8px rgba(0,0,0,0.04); min-width: 150px;">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ((int)$filters['category_id'] === (int)$c['id']) ? 'selected' : '' ?>><?= View::escape($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php $hasActiveFilters = !empty($filters['stock_status']) || !empty($filters['brand_id']) || !empty($filters['category_id']); ?>
            <?php if ($hasActiveFilters): ?>
                <a href="inventory.php" class="inv-btn" style="height: 34px; min-height: 34px; padding: 0 14px; border-radius: 999px; font-size: 11px; font-weight: 700; text-decoration: none; color: #dc2626; background: #fef2f2; border: 1px solid #fca5a5;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    Reset
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Alert Notices -->
    <?php if (!empty($successMsg)): ?>
        <div class="inv-alert inv-alert-success" style="flex-shrink: 0;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            <?= View::escape($successMsg) ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($errorMsg)): ?>
        <div class="inv-alert inv-alert-danger" style="flex-shrink: 0;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <?= View::escape($errorMsg) ?>
        </div>
    <?php endif; ?>

    <!-- Table Panel & Scroll Container (Internal Scrolling) -->
    <div class="prod-table-panel" style="flex: 1; min-height: 0; display: flex; flex-direction: column;">
        <div class="prod-table-scroll" style="overflow-y: auto; flex: 1;">
            <table class="prod-table" id="inventoryTable">
                <thead>
                    <tr>
                        <th class="col-left" style="width: 120px;">SKU</th>
                        <th class="col-left" style="width: 130px;">BARCODE</th>
                        <th class="col-left">PRODUCT NAME</th>
                        <th class="col-right" style="width: 140px;">COST (LKR)</th>
                        <th class="col-right" style="width: 160px;">SELLING PRICE (LKR)</th>
                        <th class="col-right" style="width: 140px;">AVAILABLE STOCK</th>
                        <th class="col-center" style="width: 120px;">STOCK ALERT</th>
                        <th class="col-right" style="width: 130px;">PRICE LOTS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($inventory)): ?>
                        <tr id="noRecordsRow">
                            <td colspan="8" style="text-align: center; color: #94a3b8; padding: 48px 16px;">
                                No stock records found matching your filters for <strong><?= View::escape($activeBranchName) ?></strong>.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($inventory as $item): ?>
                            <?php 
                            $qty = (float)$item['available_quantity']; 
                            $cost = (float)$item['default_cost'];
                            $price = (float)$item['default_price'];
                            $threshold = (float)$item['low_stock_threshold'];
                            $isLow = $qty <= $threshold;
                            $stockStatusText = ($qty <= 0) ? 'out of stock' : ($isLow ? 'low stock' : 'in stock');
                            $lotsCountText = ((int)$item['active_lots_count']) . ' active lots';
                            $searchBlob = implode(' ', [
                                $item['product_name'],
                                $item['sku'] ?? '',
                                $item['barcode'] ?? '',
                                $item['brand_name'] ?? '',
                                $item['category_name'] ?? '',
                                number_format($cost, 2),
                                (string)$cost,
                                'lkr ' . number_format($cost, 2),
                                'rs. ' . number_format($cost, 2),
                                'rs ' . number_format($cost, 2),
                                number_format($price, 2),
                                (string)$price,
                                'lkr ' . number_format($price, 2),
                                'rs. ' . number_format($price, 2),
                                'rs ' . number_format($price, 2),
                                number_format($qty, 2),
                                (string)$qty,
                                $stockStatusText,
                                $lotsCountText
                            ]);
                            ?>
                            <tr class="inv-row" data-search="<?= htmlspecialchars(strtolower($searchBlob), ENT_QUOTES, 'UTF-8') ?>" onclick='openItemDualDrawer(<?= htmlspecialchars(json_encode($item), ENT_QUOTES, "UTF-8") ?>)' style="cursor: pointer;">
                                <td class="col-left"><span class="inv-code-pill"><?= View::escape($item['sku'] ?? 'N/A') ?></span></td>
                                <td class="col-left"><span class="inv-code-pill"><?= View::escape($item['barcode']) ?></span></td>
                                <td class="col-left"><strong style="color: #0f172a; font-size: 13px;"><?= View::escape($item['product_name']) ?></strong></td>
                                <td class="col-right" style="font-family: ui-monospace, monospace; color: #64748b; font-weight: 600;">
                                    <?= number_format((float)$item['default_cost'], 2) ?>
                                </td>
                                <td class="col-right" style="font-family: ui-monospace, monospace; font-weight: 700; color: #0f172a;">
                                    <?= number_format((float)$item['default_price'], 2) ?>
                                </td>
                                <td class="col-right" style="font-size: 13.5px; font-weight: 800; font-family: ui-monospace, monospace; color: <?= $qty > 0 ? '#0f172a' : '#dc2626' ?>;">
                                    <?= is_numeric($qty) ? number_format($qty, 2) : View::escape($qty) ?>
                                </td>
                                <td class="col-center">
                                    <?php if (is_numeric($qty) && $qty <= 0): ?>
                                        <span class="stock-badge stock-badge-out">Out of Stock</span>
                                    <?php elseif (is_numeric($qty) && $isLow): ?>
                                        <span class="stock-badge stock-badge-low">Low Stock</span>
                                    <?php else: ?>
                                        <span class="stock-badge stock-badge-in">In Stock</span>
                                    <?php endif; ?>
                                </td>
                                <td class="col-right" onclick="event.stopPropagation();">
                                    <button type="button" class="lots-act-link" onclick='openItemDualDrawer(<?= htmlspecialchars(json_encode($item), ENT_QUOTES, "UTF-8") ?>)'>
                                        <?= (int)$item['active_lots_count'] ?> Active Lots →
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Standard Table Pagination Footer (Limits: 25, 50, 75, 100, 500) -->
        <div class="prod-table-footer" style="flex-shrink: 0; display: flex; justify-content: space-between; align-items: center; padding: 10px 16px; background: #ffffff; border-top: 1px solid #e2e8f0;">
            <div style="font-size: 12px; color: #64748b; font-weight: 600;" id="tableEntriesInfo">
                Showing <?= $totalItems > 0 ? (($page - 1) * $limit + 1) : 0 ?> to <?= min($page * $limit, $totalItems) ?> of <?= $totalItems ?> entries
            </div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="display: flex; align-items: center; gap: 6px;">
                    <span style="font-size: 11px; font-weight: 700; color: #64748b;">Per Page:</span>
                    <select id="tableLimitSelect" onchange="changeInventoryLimit(this.value)" style="height: 28px; padding: 0 8px; border-radius: 6px; font-size: 12px; font-weight: 700; border: 1px solid #cbd5e1; background: #fff;">
                        <?php foreach ([25, 50, 75, 100, 500] as $lim): ?>
                            <option value="<?= $lim ?>" <?= ($limit === $lim) ? 'selected' : '' ?>><?= $lim ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display: flex; gap: 4px;">
                    <button type="button" class="inv-btn inv-btn-ghost" style="height: 28px; padding: 0 10px; font-size: 11px;" <?= ($page <= 1) ? 'disabled style="opacity:0.4; cursor:not-allowed;"' : 'onclick="goToPage(' . ($page - 1) . ')"' ?>>← Prev</button>
                    <span style="font-size: 12px; font-weight: 700; padding: 4px 8px; color: #0f172a;"><?= $page ?> / <?= max(1, $totalPages) ?></span>
                    <button type="button" class="inv-btn inv-btn-ghost" style="height: 28px; padding: 0 10px; font-size: 11px;" <?= ($page >= $totalPages) ? 'disabled style="opacity:0.4; cursor:not-allowed;"' : 'onclick="goToPage(' . ($page + 1) . ')"' ?>>Next →</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Dual Side-by-Side Slide-In Container (Product Details + Price & Cost Lots Cards) -->
<div id="invDrawerBackdrop" class="inv-drawer-backdrop" onclick="closeItemDualDrawer()">
    <div class="inv-dual-drawer" onclick="event.stopPropagation()">
        <div class="inv-drawer-header">
            <div>
                <h3 id="drawerItemTitle" style="font-size: 1.1rem; font-weight: 800; color: #0f172a; margin: 0;">Product Details & Lots</h3>
                <span id="drawerItemSku" style="font-size: 0.75rem; color: #64748b; font-weight: 600;">SKU: -</span>
            </div>
            <button type="button" onclick="closeItemDualDrawer()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: #64748b;">✕</button>
        </div>

        <div class="inv-dual-grid">
            <!-- PANE 1: Left Pane (Product Summary & Metadata) -->
            <div class="inv-drawer-body" style="border-right: 1px solid #e2e8f0; background: #fafafa;">
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px;">
                    <span style="font-size: 0.68rem; font-weight: 800; text-transform: uppercase; color: #64748b; letter-spacing: 0.05em;">Branch Available Stock</span>
                    <div id="drawerStockVal" style="font-size: 1.6rem; font-weight: 800; color: #0f172a; font-family: ui-monospace, monospace; margin-top: 4px;">0.00</div>
                    <div id="drawerStatusBadge" style="margin-top: 6px;"></div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px;">
                        <span style="font-size: 0.65rem; font-weight: 700; color: #64748b;">DEFAULT COST</span>
                        <div id="drawerCostVal" style="font-size: 0.9rem; font-weight: 800; color: #0f172a; font-family: monospace; margin-top: 2px;">LKR 0.00</div>
                    </div>
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px;">
                        <span style="font-size: 0.65rem; font-weight: 700; color: #64748b;">DEFAULT SELLING PRICE</span>
                        <div id="drawerPriceVal" style="font-size: 0.9rem; font-weight: 800; color: #0f172a; font-family: monospace; margin-top: 2px;">LKR 0.00</div>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 8px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px;">
                    <div style="display: flex; justify-content: space-between; font-size: 0.8rem; padding: 6px 0; border-bottom: 1px solid #f1f5f9;">
                        <span style="color: #64748b;">Barcode:</span>
                        <strong id="drawerBarcode" style="color: #0f172a; font-family: monospace;">-</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.8rem; padding: 6px 0; border-bottom: 1px solid #f1f5f9;">
                        <span style="color: #64748b;">Brand:</span>
                        <span id="drawerBrand" class="inv-code-pill">-</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.8rem; padding: 6px 0; border-bottom: 1px solid #f1f5f9;">
                        <span style="color: #64748b;">Category:</span>
                        <span id="drawerCategory" class="inv-code-pill">-</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.8rem; padding: 6px 0;">
                        <span style="color: #64748b;">Active Cost Lots:</span>
                        <strong id="drawerLotsCount" style="color: #0f172a;">0 Lots</strong>
                    </div>
                </div>
            </div>

            <!-- PANE 2: Right Pane (Side-by-side Price & Cost Lots Cards) -->
            <div class="inv-drawer-body" style="background: #ffffff;">
                <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                    <span style="font-size: 0.78rem; font-weight: 800; text-transform: uppercase; color: #0f172a; letter-spacing: 0.05em;">Price & Cost Lots Cards</span>
                    <span style="font-size: 0.72rem; color: #64748b; font-weight: 600;"><?= View::escape($activeBranchName) ?></span>
                </div>
                <div id="lotsPaneContainer" style="display: flex; flex-direction: column; gap: 12px;">
                    <!-- Dynamic Lot Cards inserted via JS -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Add Stock Lot / Opening Stock -->
<div id="addLotModalBackdrop" class="curtiss-modal-backdrop" onclick="closeAddLotModal()">
    <div class="curtiss-modal" onclick="event.stopPropagation()">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
            <h3 style="font-size: 1.05rem; font-weight: 800; color: #0f172a; margin: 0;">Add Stock Lot / Opening Stock</h3>
            <button type="button" onclick="closeAddLotModal()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: #64748b;">✕</button>
        </div>
        <form method="POST" action="inventory.php" id="addLotForm" onsubmit="return handleFormSubmit(this)">
            <input type="hidden" name="action" value="add_lot">
            <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrfToken() ?>">

            <div style="display: flex; flex-direction: column; gap: 12px;">
                <div class="inv-field">
                    <label class="inv-label">Select Product *</label>
                    <select name="product_id" id="lot_product_id" class="curtiss-select-enhance" required onchange="autoFillLotPrices()">
                        <option value="">-- Choose Product --</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?= $p['id'] ?>" data-cost="<?= $p['cost'] ?>" data-price="<?= $p['selling_price'] ?>">
                                <?= View::escape($p['name']) ?> (<?= View::escape($p['barcode']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="inv-field">
                        <label class="inv-label">Cost Price (LKR) *</label>
                        <input type="number" step="0.01" min="0" name="cost" id="lot_cost" class="inv-input" required placeholder="0.00">
                    </div>
                    <div class="inv-field">
                        <label class="inv-label">Selling Price (LKR) *</label>
                        <input type="number" step="0.01" min="0" name="selling_price" id="lot_selling_price" class="inv-input" required placeholder="0.00">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="inv-field">
                        <label class="inv-label">Quantity *</label>
                        <input type="number" step="0.01" min="0.01" name="quantity" id="lot_quantity" class="inv-input" placeholder="e.g. 50" required>
                    </div>
                    <div class="inv-field">
                        <label class="inv-label">Lot Ref / Number</label>
                        <input type="text" name="lot_number" id="lot_number" class="inv-input" placeholder="Auto-generated if empty">
                    </div>
                </div>

                <div class="inv-field">
                    <label class="inv-label">Notes / Reference</label>
                    <input type="text" name="notes" id="lot_notes" class="inv-input" placeholder="e.g. Opening stock audit addition">
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 20px;">
                <button type="button" class="inv-btn inv-btn-ghost" onclick="closeAddLotModal()">Cancel</button>
                <button type="submit" class="inv-btn inv-btn-primary" id="btnSubmitLot">Save Stock Lot</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/curtiss_select.js"></script>
<script>
function esc(str) {
    if (!str) return '';
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

document.addEventListener('DOMContentLoaded', () => {
    // Enhance filter selects and modal selects with CurtissSearchableSelect
    document.querySelectorAll('.curtiss-select-enhance').forEach(el => {
        if (window.CurtissSearchableSelect) {
            CurtissSearchableSelect.attach(el);
        }
    });

    // Register Real-time Search Hook on Floating Bottom Nav Bar
    function registerBottomSearch() {
        if (window.CurtissNav && window.CurtissNav.enablePageSearch) {
            window.CurtissNav.enablePageSearch({
                placeholder: 'Search SKU, barcode, product, amount (LKR)...',
                initialValue: <?= json_encode($filters['search'] ?? '') ?>,
                onSearch: function(query) {
                    filterInventoryTableRealtime(query);
                },
                onEnter: function(query) {
                    const params = new URLSearchParams(window.location.search);
                    if (query && query.trim()) {
                        params.set('search', query.trim());
                    } else {
                        params.delete('search');
                    }
                    params.set('page', '1');
                    window.location.href = 'inventory.php?' + params.toString();
                }
            });
        } else {
            setTimeout(registerBottomSearch, 40);
        }
    }
    registerBottomSearch();

    // Setup Enter Key Auto-Advancement inside Add Lot Modal Form
    setupModalKeyboardNavigation();
});

function applyInventoryFilter() {
    const status = document.getElementById('filterStockStatus').value;
    const brand = document.getElementById('filterBrand').value;
    const cat = document.getElementById('filterCategory').value;
    const limit = document.getElementById('tableLimitSelect').value;

    const params = new URLSearchParams(window.location.search);
    if (status) params.set('stock_status', status); else params.delete('stock_status');
    if (brand) params.set('brand_id', brand); else params.delete('brand_id');
    if (cat) params.set('category_id', cat); else params.delete('category_id');
    if (limit) params.set('limit', limit); else params.delete('limit');
    params.set('page', '1');

    window.location.href = 'inventory.php?' + params.toString();
}

function changeInventoryLimit(limitVal) {
    const params = new URLSearchParams(window.location.search);
    params.set('limit', limitVal);
    params.set('page', '1');
    window.location.href = 'inventory.php?' + params.toString();
}

function goToPage(targetPage) {
    const params = new URLSearchParams(window.location.search);
    params.set('page', targetPage);
    window.location.href = 'inventory.php?' + params.toString();
}

function filterInventoryTableRealtime(query) {
    const q = (query || '').toLowerCase().trim();
    const cleanQ = q.replace(/^lkr\s*/i, '').replace(/^rs\.?\s*/i, '').replace(/,/g, '').trim();
    const rows = document.querySelectorAll('#inventoryTable tbody tr.inv-row');
    let visibleCount = 0;

    rows.forEach(tr => {
        const searchAttr = (tr.getAttribute('data-search') || '').toLowerCase();
        // Exclude action column button/link text
        const cells = Array.from(tr.querySelectorAll('td:not(.col-right:last-child)'));
        const rowText = cells.map(td => td.textContent.toLowerCase()).join(' ');

        const match = !q || 
                      searchAttr.includes(q) || 
                      (cleanQ && searchAttr.includes(cleanQ)) ||
                      rowText.includes(q) ||
                      (cleanQ && rowText.includes(cleanQ));

        if (match) {
            tr.style.display = '';
            visibleCount++;
        } else {
            tr.style.display = 'none';
        }
    });

    const noRow = document.getElementById('noRecordsRow');
    if (noRow) {
        noRow.style.display = (visibleCount === 0 && rows.length > 0) ? '' : 'none';
    }
    const info = document.getElementById('tableEntriesInfo');
    if (info) {
        if (q) {
            info.textContent = `Showing ${visibleCount} filtered results for "${query}"`;
        } else {
            info.textContent = `Showing <?= $totalItems > 0 ? (($page - 1) * $limit + 1) : 0 ?> to <?= min($page * $limit, $totalItems) ?> of <?= $totalItems ?> entries`;
        }
    }
}

function exportInventoryCsv() {
    const params = new URLSearchParams(window.location.search);
    window.location.href = 'inventory_export.php?' + params.toString();
}

function openAddLotModal() {
    document.getElementById('addLotModalBackdrop').classList.add('active');
    setTimeout(() => {
        const prodSel = document.getElementById('lot_product_id');
        if (prodSel && prodSel._curtissSelect) {
            prodSel._curtissSelect.focus();
        }
    }, 150);
}

function closeAddLotModal() {
    document.getElementById('addLotModalBackdrop').classList.remove('active');
}

function autoFillLotPrices() {
    const sel = document.getElementById('lot_product_id');
    const opt = sel.options[sel.selectedIndex];
    if (opt && opt.value) {
        document.getElementById('lot_cost').value = parseFloat(opt.getAttribute('data-cost') || 0).toFixed(2);
        document.getElementById('lot_selling_price').value = parseFloat(opt.getAttribute('data-price') || 0).toFixed(2);
        document.getElementById('lot_quantity').focus();
    }
}

let isLotSubmitting = false;
function handleFormSubmit(form) {
    if (isLotSubmitting) return false;
    isLotSubmitting = true;
    const btn = form.querySelector('#btnSubmitLot');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span style="display:inline-block; width:12px; height:12px; border:2px solid #fff; border-top-color:transparent; border-radius:50%; animation:spin 0.6s linear infinite; margin-right:6px;"></span>Saving...';
    }
    return true;
}

function setupModalKeyboardNavigation() {
    const form = document.getElementById('addLotForm');
    if (!form) return;

    const fields = [
        document.getElementById('lot_cost'),
        document.getElementById('lot_selling_price'),
        document.getElementById('lot_quantity'),
        document.getElementById('lot_number'),
        document.getElementById('lot_notes')
    ].filter(f => f !== null);

    fields.forEach((field, index) => {
        field.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (index < fields.length - 1) {
                    fields[index + 1].focus();
                    if (fields[index + 1].select) fields[index + 1].select();
                } else {
                    form.requestSubmit();
                }
            }
        });
    });

    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
            const modal = document.getElementById('addLotModalBackdrop');
            if (modal && modal.classList.contains('active')) {
                e.preventDefault();
                form.requestSubmit();
            }
        }
    });
}

async function openItemDualDrawer(item) {
    document.getElementById('drawerItemTitle').textContent = item.product_name;
    document.getElementById('drawerItemSku').textContent = 'SKU: ' + (item.sku || 'N/A');
    
    const qty = parseFloat(item.available_quantity || 0);
    document.getElementById('drawerStockVal').textContent = qty.toFixed(2);

    const badgeBox = document.getElementById('drawerStatusBadge');
    if (qty <= 0) {
        badgeBox.innerHTML = '<span class="stock-badge stock-badge-out">Out of Stock</span>';
    } else if (qty <= parseFloat(item.low_stock_threshold || 0)) {
        badgeBox.innerHTML = '<span class="stock-badge stock-badge-low">Low Stock</span>';
    } else {
        badgeBox.innerHTML = '<span class="stock-badge stock-badge-in">In Stock</span>';
    }

    document.getElementById('drawerCostVal').textContent = 'LKR ' + parseFloat(item.default_cost || 0).toLocaleString('en-US', {minimumFractionDigits: 2});
    document.getElementById('drawerPriceVal').textContent = 'LKR ' + parseFloat(item.default_price || 0).toLocaleString('en-US', {minimumFractionDigits: 2});
    document.getElementById('drawerBarcode').textContent = item.barcode || '-';
    document.getElementById('drawerBrand').textContent = item.brand_name || 'Unassigned';
    document.getElementById('drawerCategory').textContent = item.category_name || 'Uncategorized';
    document.getElementById('drawerLotsCount').textContent = (item.active_lots_count || 0) + ' Active Lots';

    const lotsContainer = document.getElementById('lotsPaneContainer');
    lotsContainer.innerHTML = `
        <div style="text-align: center; color: #64748b; padding: 48px 0;">
            <div style="font-size: 0.85rem; font-weight: 600;">Loading lot cards...</div>
        </div>
    `;

    document.getElementById('invDrawerBackdrop').classList.add('active');

    try {
        const res = await fetch(`inventory_api.php?action=get_product_lots&product_id=${item.product_id}`);
        const data = await res.json();
        
        if (!data.success || !data.lots || data.lots.length === 0) {
            lotsContainer.innerHTML = `
                <div style="text-align: center; color: #94a3b8; padding: 60px 16px;">
                    <svg width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" style="margin-bottom: 8px;"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                    <div style="font-weight: 700; color: #475569; font-size: 0.9rem;">No Price Lots Found</div>
                    <div style="font-size: 0.78rem; color: #94a3b8; margin-top: 4px;">No price or cost lots recorded for this product.</div>
                </div>
            `;
            return;
        }

        const groupedLots = [];
        data.lots.forEach(lot => {
            const costKey = parseFloat(lot.cost || 0).toFixed(2);
            const priceKey = parseFloat(lot.selling_price || 0).toFixed(2);
            const statusKey = (lot.status || 'active').toLowerCase();
            const key = `${costKey}_${priceKey}_${statusKey}`;

            let existing = groupedLots.find(g => g.key === key);
            if (!existing) {
                existing = {
                    key: key,
                    lot_number: lot.lot_number || ('LOT-#' + lot.id),
                    lot_numbers: [lot.lot_number || ('LOT-#' + lot.id)],
                    created_at: lot.created_at,
                    cost: parseFloat(lot.cost || 0),
                    selling_price: parseFloat(lot.selling_price || 0),
                    remaining_quantity: 0,
                    initial_quantity: 0,
                    status: lot.status,
                    lot_count: 0
                };
                groupedLots.push(existing);
            } else {
                existing.lot_numbers.push(lot.lot_number || ('LOT-#' + lot.id));
            }

            existing.remaining_quantity += parseFloat(lot.remaining_quantity || 0);
            existing.initial_quantity += parseFloat(lot.initial_quantity || 0);
            existing.lot_count++;
        });

        let html = '';
        groupedLots.forEach(lot => {
            const remQty = lot.remaining_quantity;
            const initQty = lot.initial_quantity;
            const cost = lot.cost;
            const price = lot.selling_price;
            const status = (lot.status || 'active').toLowerCase();

            const isDepleted = status === 'depleted' || remQty <= 0;
            const statusBadge = isDepleted 
                ? '<span class="stock-badge stock-badge-out">Depleted</span>' 
                : '<span class="stock-badge stock-badge-in">Active Lot</span>';

            const lotPillText = lot.lot_count > 1 
                ? `${esc(lot.lot_number)} (+${lot.lot_count - 1} lots)` 
                : esc(lot.lot_number);

            html += `
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 14px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); display: flex; flex-direction: column; gap: 8px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 6px;">
                        <div>
                            <span class="inv-code-pill" style="font-size: 0.75rem; font-weight: 800; color: #0f172a;">${lotPillText}</span>
                            <span style="font-size: 0.7rem; color: #94a3b8; margin-left: 6px;">${lot.created_at ? esc(lot.created_at.substring(0, 10)) : ''}</span>
                        </div>
                        ${statusBadge}
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                        <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 8px; padding: 6px 8px;">
                            <span style="font-size: 0.62rem; font-weight: 700; color: #64748b; text-transform: uppercase;">COST PRICE</span>
                            <div style="font-size: 0.88rem; font-weight: 800; font-family: ui-monospace, monospace; color: #0f172a; margin-top: 2px;">LKR ${cost.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}</div>
                        </div>
                        <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 8px; padding: 8px 10px;">
                            <span style="font-size: 0.62rem; font-weight: 700; color: #64748b; text-transform: uppercase;">SELLING PRICE</span>
                            <div style="font-size: 0.88rem; font-weight: 800; font-family: ui-monospace, monospace; color: #0f172a; margin-top: 2px;">LKR ${price.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}</div>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem; background: #fafafa; padding: 6px 10px; border-radius: 6px; border: 1px solid #f1f5f9;">
                        <span style="color: #64748b; font-weight: 600;">Stock Remaining / Initial:</span>
                        <div style="font-family: ui-monospace, monospace;">
                            <strong style="font-size: 0.88rem; color: ${remQty > 0 ? '#166534' : '#dc2626'};">${remQty.toFixed(2)}</strong>
                            <span style="color: #94a3b8;">/ ${initQty.toFixed(2)}</span>
                        </div>
                    </div>
                </div>
            `;
        });

        lotsContainer.innerHTML = html;
    } catch (e) {
        console.error(e);
        lotsContainer.innerHTML = '<div style="color: #dc2626; text-align: center; padding: 20px; font-weight: 600;">Failed to load lot cards.</div>';
    }
}

function closeItemDualDrawer() {
    document.getElementById('invDrawerBackdrop').classList.remove('active');
}
</script>