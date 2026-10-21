<?php
/**
 * Curtiss POS - Product Price & Cost Lots Master View
 * Adheres strictly to Curtiss Master Rules:
 * Fixed Viewport, Zebra Striping (48px rows), Sticky Headers,
 * Inline 34px Filter Selects, Floating Bottom Nav Realtime Search,
 * Server-Side Pagination (25, 50, 75, 100, 500) & Slide-in Lot Drawer.
 */
?>
<link rel="stylesheet" href="assets/css/curtiss_select.css">
<script src="assets/js/curtiss_select.js"></script>

<!-- Floating Top-Right Action Header (Action Controls Only - Rule 19) -->
<div id="curtiss-floating-header-right" style="position: fixed; top: 16px; right: 20px; z-index: 1050; display: flex; align-items: center; gap: 8px;">
    <button type="button" class="inv-btn inv-btn-ghost" onclick="exportLotsCsv()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Export CSV
    </button>
    <a href="barcode_labels.php" class="inv-btn inv-btn-ghost">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M6 9v6M9 9v6M13 9v6M16 9v6M18 9v6"/></svg>
        Print Barcode Labels
    </a>
    <a href="inventory.php" class="inv-btn inv-btn-ghost">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        Back to Branch Inventory
    </a>
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

/* In-Cell Badges & Action Buttons */
.inv-code-pill {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 11px;
    background: #f1f5f9;
    color: #334155;
    padding: 2px 6px;
    border-radius: 6px;
    font-weight: 600;
}
.inv-badge-brand {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #dbeafe;
}
.inv-badge-cat {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    background: #f0fdf4;
    color: #15803d;
    border: 1px solid #dcfce7;
}

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
.stock-badge-out { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

/* ── Slide-in Drawer ── */
.inv-drawer-backdrop {
    position: fixed; inset: 0; background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);
    z-index: 9990; opacity: 0; visibility: hidden; transition: all 0.25s ease;
}
.inv-drawer-backdrop.active { opacity: 1; visibility: visible; }
.inv-drawer {
    position: fixed; top: 0; right: 0; bottom: 0; width: 520px; max-width: 95vw;
    background: rgba(255, 255, 255, 0.98); backdrop-filter: blur(20px);
    border-left: 1px solid rgba(226, 232, 240, 0.9); box-shadow: -10px 0 40px rgba(0,0,0,0.15);
    z-index: 9991; transform: translateX(100%); transition: transform 0.28s cubic-bezier(0.16, 1, 0.3, 1);
    display: flex; flex-direction: column;
}
.inv-drawer-backdrop.active .inv-drawer { transform: translateX(0); }
.inv-drawer-header {
    padding: 16px 20px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; background: #ffffff;
}
.inv-drawer-body { padding: 18px; overflow-y: auto; flex: 1; display: flex; flex-direction: column; gap: 14px; }
</style>

<!-- Fixed Screen Viewport Container -->
<div style="width: 100%; max-width: 100%; height: calc(100vh - 110px); display: flex; flex-direction: column; box-sizing: border-box; overflow: hidden; padding-bottom: 8px;">

    <!-- Page Header (Headline Only + Inline 34px Filter Select - Rule 3 & 5) -->
    <div class="inv-header" style="flex-shrink: 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 0.85rem;">
        <div style="display: flex; align-items: center; gap: 8px;">
            <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
            <h1 class="inv-title">Product Price & Cost Lots</h1>
        </div>

        <!-- Inline Filter Controls Above Table Right Corner (Height: 34px) -->
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-left: auto;">
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Lot Status:</span>
                <select id="filterActiveOnly" class="curtiss-select-enhance" onchange="applyLotsFilter(this.value)" style="height: 34px; min-height: 34px; padding: 0 14px; border-radius: 999px; font-size: 12px; font-weight: 700; color: #334155; background: rgba(255,255,255,0.95); border: 1px solid rgba(203,213,225,0.9); box-shadow: 0 2px 8px rgba(0,0,0,0.04); min-width: 220px;">
                    <option value="0" <?= !$activeOnly ? 'selected' : '' ?>>All Lots (Active & Depleted)</option>
                    <option value="1" <?= $activeOnly ? 'selected' : '' ?>>Active Available Lots Only</option>
                </select>
            </div>
            <?php if ($activeOnly || !empty($search)): ?>
                <a href="inventory_lots.php" class="inv-btn" style="height: 34px; min-height: 34px; padding: 0 14px; border-radius: 999px; font-size: 11px; font-weight: 700; text-decoration: none; color: #dc2626; background: #fef2f2; border: 1px solid #fca5a5;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    Reset
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Products Data Table Panel -->
    <div class="prod-table-panel" style="flex: 1; min-height: 0; display: flex; flex-direction: column;">
        <div class="prod-table-scroll" style="overflow-y: auto; flex: 1;">
            <table class="prod-table" id="lotsTable">
                <thead>
                    <tr>
                        <th class="col-left" style="width: 140px;">BARCODE</th>
                        <th class="col-left">PRODUCT NAME</th>
                        <th class="col-right" style="width: 150px;">ACTIVE LOTS COUNT</th>
                        <th class="col-right" style="width: 150px;">TOTAL LOT STOCK</th>
                        <th class="col-right" style="width: 150px;">DEFAULT COST (LKR)</th>
                        <th class="col-right" style="width: 170px;">DEFAULT SELLING PRICE (LKR)</th>
                        <th class="col-right" style="width: 130px;">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($productsWithLots)): ?>
                        <tr id="noRecordsRow">
                            <td colspan="7" style="text-align: center; color: #94a3b8; padding: 48px 16px;">
                                No products found matching your filters in <strong><?= View::escape($activeBranchName) ?></strong>.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($productsWithLots as $p): ?>
                            <?php
                            $cost = (float)$p['default_cost'];
                            $price = (float)$p['default_price'];
                            $stock = (float)$p['total_lot_stock'];
                            $lotsCount = (int)$p['active_lots_count'];
                            $searchBlob = implode(' ', [
                                $p['product_name'],
                                $p['barcode'] ?? '',
                                $p['brand_name'] ?? '',
                                $p['category_name'] ?? '',
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
                                number_format($stock, 2),
                                (string)$stock,
                                $lotsCount . ' active lots'
                            ]);
                            ?>
                            <tr class="lots-prod-row" data-search="<?= htmlspecialchars(strtolower($searchBlob), ENT_QUOTES, 'UTF-8') ?>" onclick='openProductLotsDrawer(<?= htmlspecialchars(json_encode($p), ENT_QUOTES, "UTF-8") ?>)' style="cursor: pointer;">
                                <td class="col-left"><span class="inv-code-pill"><?= View::escape($p['barcode'] ?: '-') ?></span></td>
                                <td class="col-left"><strong style="color: #0f172a; font-size: 13px;"><?= View::escape($p['product_name']) ?></strong></td>
                                <td class="col-right" style="font-weight: 700; color: #0f172a;">
                                    <?= number_format($lotsCount) ?> Active Lots
                                </td>
                                <td class="col-right" style="font-size: 13.5px; font-weight: 800; font-family: ui-monospace, monospace; color: <?= $stock > 0 ? '#166534' : '#dc2626' ?>;">
                                    <?= number_format($stock, 2) ?>
                                </td>
                                <td class="col-right" style="font-family: ui-monospace, monospace; color: #64748b; font-weight: 600;">
                                    <?= number_format($cost, 2) ?>
                                </td>
                                <td class="col-right" style="font-family: ui-monospace, monospace; font-weight: 700; color: #0f172a;">
                                    <?= number_format($price, 2) ?>
                                </td>
                                <td class="col-right" onclick="event.stopPropagation();">
                                    <button type="button" class="lots-act-link" onclick='openProductLotsDrawer(<?= htmlspecialchars(json_encode($p), ENT_QUOTES, "UTF-8") ?>)'>
                                        View Lots (<?= count($p['lots']) ?>) →
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
                    <select id="tableLimitSelect" onchange="changeLotsLimit(this.value)" style="height: 28px; padding: 0 8px; border-radius: 6px; font-size: 12px; font-weight: 700; border: 1px solid #cbd5e1; background: #fff;">
                        <?php foreach ([25, 50, 75, 100, 500] as $lim): ?>
                            <option value="<?= $lim ?>" <?= ($limit === $lim) ? 'selected' : '' ?>><?= $lim ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display: flex; gap: 4px;">
                    <button type="button" class="inv-btn inv-btn-ghost" style="height: 28px; padding: 0 10px; font-size: 11px;" <?= ($page <= 1) ? 'disabled style="opacity:0.4; cursor:not-allowed;"' : 'onclick="goToLotsPage(' . ($page - 1) . ')"' ?>>← Prev</button>
                    <span style="font-size: 12px; font-weight: 700; padding: 4px 8px; color: #0f172a;"><?= $page ?> / <?= max(1, $totalPages) ?></span>
                    <button type="button" class="inv-btn inv-btn-ghost" style="height: 28px; padding: 0 10px; font-size: 11px;" <?= ($page >= $totalPages) ? 'disabled style="opacity:0.4; cursor:not-allowed;"' : 'onclick="goToLotsPage(' . ($page + 1) . ')"' ?>>Next →</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Right Slide-In Side Pane: Product Lots Cards Drawer -->
<div id="productLotsDrawerBackdrop" class="inv-drawer-backdrop" onclick="closeProductLotsDrawer()">
    <div class="inv-drawer" onclick="event.stopPropagation()">
        <div class="inv-drawer-header">
            <div>
                <h3 id="drawerProdTitle" style="font-size: 1.05rem; font-weight: 800; color: #0f172a; margin: 0;">Product Lots</h3>
                <span id="drawerProdSku" style="font-size: 0.75rem; color: #64748b; font-weight: 600;">SKU: -</span>
            </div>
            <button type="button" onclick="closeProductLotsDrawer()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: #64748b;">✕</button>
        </div>
        <div class="inv-drawer-body">
            <!-- Product Meta Box -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 14px; display: flex; flex-direction: column; gap: 6px;">
                <div style="display: flex; justify-content: space-between; font-size: 0.8rem;">
                    <span style="color: #64748b;">Total Available Lot Stock:</span>
                    <strong id="drawerTotalStock" style="color: #0f172a; font-family: ui-monospace, monospace; font-size: 0.95rem;">0.00</strong>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.8rem;">
                    <span style="color: #64748b;">Brand / Category:</span>
                    <div>
                        <span id="drawerBrand" class="inv-badge-brand">-</span>
                        <span id="drawerCategory" class="inv-badge-cat" style="margin-left: 4px;">-</span>
                    </div>
                </div>
            </div>

            <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 6px; margin-top: 4px;">
                <span style="font-size: 0.78rem; font-weight: 800; text-transform: uppercase; color: #0f172a; letter-spacing: 0.05em;">Price & Cost Lot Cards</span>
                <span style="font-size: 0.72rem; color: #64748b; font-weight: 600;"><?= View::escape($activeBranchName) ?></span>
            </div>

            <!-- Lots Cards Container -->
            <div id="drawerLotsContainer" style="display: flex; flex-direction: column; gap: 12px;">
                <!-- Rendered dynamically via JS -->
            </div>
        </div>
    </div>
</div>

<script>
function esc(str) {
    if (!str) return '';
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

document.addEventListener('DOMContentLoaded', () => {
    // Enhance filter select with CurtissSearchableSelect
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
                initialValue: <?= json_encode($search) ?>,
                onSearch: function(query) {
                    filterLotsTableRealtime(query);
                },
                onEnter: function(query) {
                    const params = new URLSearchParams(window.location.search);
                    if (query && query.trim()) {
                        params.set('search', query.trim());
                    } else {
                        params.delete('search');
                    }
                    params.set('page', '1');
                    window.location.href = 'inventory_lots.php?' + params.toString();
                }
            });
        } else {
            setTimeout(registerBottomSearch, 40);
        }
    }
    registerBottomSearch();
});

function applyLotsFilter(activeOnlyVal) {
    const params = new URLSearchParams(window.location.search);
    if (activeOnlyVal === '1') {
        params.set('active_only', '1');
    } else {
        params.delete('active_only');
    }
    params.set('page', '1');
    window.location.href = 'inventory_lots.php?' + params.toString();
}

function changeLotsLimit(limitVal) {
    const params = new URLSearchParams(window.location.search);
    params.set('limit', limitVal);
    params.set('page', '1');
    window.location.href = 'inventory_lots.php?' + params.toString();
}

function goToLotsPage(targetPage) {
    const params = new URLSearchParams(window.location.search);
    params.set('page', targetPage);
    window.location.href = 'inventory_lots.php?' + params.toString();
}

function exportLotsCsv() {
    const params = new URLSearchParams(window.location.search);
    window.location.href = 'inventory_lots_export.php?' + params.toString();
}

function filterLotsTableRealtime(query) {
    const q = (query || '').toLowerCase().trim();
    const cleanQ = q.replace(/^lkr\s*/i, '').replace(/^rs\.?\s*/i, '').replace(/,/g, '').trim();
    const rows = document.querySelectorAll('#lotsTable tbody tr.lots-prod-row');
    let visibleCount = 0;

    rows.forEach(tr => {
        const searchAttr = (tr.getAttribute('data-search') || '').toLowerCase();
        // Exclude action column buttons/links
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

function openProductLotsDrawer(product) {
    document.getElementById('drawerProdTitle').textContent = product.product_name;
    document.getElementById('drawerProdSku').textContent = 'SKU: ' + (product.sku || 'N/A') + ' | Barcode: ' + (product.barcode || '-');
    document.getElementById('drawerTotalStock').textContent = parseFloat(product.total_lot_stock || 0).toFixed(2);
    document.getElementById('drawerBrand').textContent = product.brand_name || 'Unassigned';
    document.getElementById('drawerCategory').textContent = product.category_name || 'Uncategorized';

    const lotsContainer = document.getElementById('drawerLotsContainer');
    lotsContainer.innerHTML = '';

    const rawLots = product.lots || [];
    if (!rawLots.length) {
        lotsContainer.innerHTML = `
            <div style="text-align: center; color: #94a3b8; padding: 48px 16px;">
                <div style="font-weight: 700; color: #475569; font-size: 0.9rem;">No Active Lots</div>
                <div style="font-size: 0.78rem; color: #94a3b8; margin-top: 4px;">No price or cost lots recorded for this product.</div>
            </div>
        `;
    } else {
        // Group lots with identical Cost Price + Selling Price + Status into combined price-tier cards
        const groupedLots = [];
        rawLots.forEach(lot => {
            const costKey = parseFloat(lot.cost || 0).toFixed(2);
            const priceKey = parseFloat(lot.selling_price || 0).toFixed(2);
            const statusKey = (lot.status || 'active').toLowerCase();
            const key = `${costKey}_${priceKey}_${statusKey}`;

            let existing = groupedLots.find(g => g.key === key);
            if (!existing) {
                existing = {
                    key: key,
                    lot_number: lot.lot_number || ('LOT-#' + lot.id),
                    created_at: lot.created_at,
                    cost: parseFloat(lot.cost || 0),
                    selling_price: parseFloat(lot.selling_price || 0),
                    remaining_quantity: 0,
                    initial_quantity: 0,
                    status: lot.status,
                    lot_count: 0
                };
                groupedLots.push(existing);
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
    }

    document.getElementById('productLotsDrawerBackdrop').classList.add('active');
}

function closeProductLotsDrawer() {
    document.getElementById('productLotsDrawerBackdrop').classList.remove('active');
}
</script>
