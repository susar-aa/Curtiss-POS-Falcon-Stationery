<?php
/**
 * Curtiss POS - Product Price & Cost Lots View (Redesign Theme & Design Rules Enforced)
 * Shows products with active price lots and slides in a side pane with lot cards upon selection.
 */
?>
<link rel="stylesheet" href="assets/css/curtiss_select.css">

<!-- Floating Top-Right Action Header (Contains Searchable Select Filter & Actions directly in header) -->
<div id="curtiss-floating-header-right" style="position: fixed; top: 16px; right: 20px; z-index: 1050; display: flex; align-items: center; gap: 8px;">
    <form method="GET" action="inventory_lots.php" id="headerLotsFilterForm" style="margin: 0; padding: 0;">
        <input type="hidden" name="branch_id" value="<?= $branchId ?>">
        <select name="active_only" id="headerActiveOnlySelect" class="inv-select curtiss-select-enhance" style="height: 36px; padding: 0 14px; border-radius: 999px; font-size: 12px; font-weight: 700; background: rgba(255,255,255,0.96); border: 1px solid rgba(226,232,240,0.9); color: #0f172a; min-width: 220px;" onchange="this.form.submit()">
            <option value="0" <?= !$activeOnly ? 'selected' : '' ?>>All Lots (Active & Depleted)</option>
            <option value="1" <?= $activeOnly ? 'selected' : '' ?>>Active Available Lots Only</option>
        </select>
    </form>
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
/* ── Page Header & Theme Layout ── */
.inv-header {
    margin-bottom: 1.25rem;
}
.inv-title {
    font-size: 1.35rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.025em;
    line-height: 1.2;
}
.inv-subtitle {
    font-size: 0.8rem;
    color: #94a3b8;
    margin-top: 4px;
    font-weight: 500;
}

.inv-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    border: 1px solid transparent;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.18s ease;
    white-space: nowrap;
    font-family: inherit;
}
.inv-btn-ghost {
    background: rgba(255,255,255,0.9);
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

/* Glassmorphism Cards */
.inv-card {
    background: rgba(255, 255, 255, 0.94);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(226, 232, 240, 0.9);
    border-radius: 16px;
    box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.05), 0 4px 12px -2px rgba(0, 0, 0, 0.03);
    margin-bottom: 1.25rem;
    padding: 1.25rem;
    width: 100%;
    box-sizing: border-box;
}

/* Form Fields */
.inv-field {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.inv-label {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #64748b;
}
.inv-input, .inv-select {
    width: 100%;
    height: 38px;
    padding: 0 12px;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    font-size: 0.82rem;
    font-family: inherit;
    color: #0f172a;
    background: #ffffff;
    transition: all 0.15s ease;
    box-sizing: border-box;
}
.inv-input:focus, .inv-select:focus {
    outline: none;
    border-color: #0f172a;
    box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.08);
}

/* Main Table Panel */
.inv-table-panel {
    background: rgba(255, 255, 255, 0.94);
    backdrop-filter: blur(16px);
    border: 1px solid rgba(226, 232, 240, 0.9);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.05);
}
.inv-table-scroll {
    overflow-x: auto;
}
.inv-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.83rem;
}
.inv-table thead {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}
.inv-table th {
    padding: 11px 14px;
    font-size: 0.68rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #475569;
    white-space: nowrap;
}
.inv-table tbody tr {
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.12s ease;
    cursor: pointer;
}
.inv-table tbody tr:hover {
    background: #f8fafc;
}
.inv-table td {
    padding: 12px 14px;
    color: #1e293b;
    vertical-align: middle;
}

/* Badges & Pills */
.inv-code-pill {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.74rem;
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
    font-size: 0.72rem;
    font-weight: 700;
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #dbeafe;
}
.inv-badge-cat {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 700;
    background: #f0fdf4;
    color: #15803d;
    border: 1px solid #dcfce7;
}

/* Stock Badges */
.stock-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 9px;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 700;
}
.stock-badge-in { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
.stock-badge-out { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

.lots-act-link {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 12px;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 700;
    background: #f8fafc;
    color: #0f172a;
    border: 1px solid #cbd5e1;
    cursor: pointer;
    transition: all 0.15s ease;
    font-family: inherit;
}
.lots-act-link:hover {
    background: #0f172a;
    color: #ffffff;
    border-color: #0f172a;
}

/* Slide-in Product Lots Drawer Container */
.inv-drawer-backdrop {
    position: fixed; inset: 0; background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);
    z-index: 9990; opacity: 0; visibility: hidden; transition: all 0.25s ease;
}
.inv-drawer-backdrop.active { opacity: 1; visibility: visible; }
.inv-drawer {
    position: fixed; top: 0; right: 0; bottom: 0; width: 480px; max-width: 92vw;
    background: rgba(255, 255, 255, 0.98); backdrop-filter: blur(20px);
    border-left: 1px solid rgba(226, 232, 240, 0.9); box-shadow: -10px 0 40px rgba(0,0,0,0.15);
    z-index: 9991; transform: translateX(100%); transition: transform 0.28s cubic-bezier(0.16, 1, 0.3, 1);
    display: flex; flex-direction: column;
}
.inv-drawer-backdrop.active .inv-drawer { transform: translateX(0); }
.inv-drawer-header {
    padding: 18px 20px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; background: #ffffff;
}
.inv-drawer-body { padding: 20px; overflow-y: auto; flex: 1; display: flex; flex-direction: column; gap: 14px; }
</style>

<div style="width: 100%; max-width: 100%; padding-bottom: 40px;">
    <!-- Page Header -->
    <div class="inv-header">
        <h1 class="inv-title">Product Price & Cost Lots</h1>
        <p class="inv-subtitle">Products with active price & cost lots in <strong><?= View::escape($activeBranchName) ?></strong></p>
    </div>

    <!-- Main Products Data Table Panel (Shows ONLY Products having price lots) -->
    <div class="inv-table-panel">
        <div class="inv-table-scroll">
            <table class="inv-table" id="lotsTable">
                <thead>
                    <tr>
                        <th style="width: 120px;">SKU</th>
                        <th style="width: 130px;">BARCODE</th>
                        <th>PRODUCT NAME</th>
                        <th style="text-align: right; width: 140px;">ACTIVE LOTS COUNT</th>
                        <th style="text-align: right; width: 150px;">TOTAL LOT STOCK</th>
                        <th style="text-align: right; width: 140px;">DEFAULT COST</th>
                        <th style="text-align: right; width: 160px;">DEFAULT SELLING PRICE</th>
                        <th style="text-align: right; width: 120px;">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($productsWithLots)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; color: #94a3b8; padding: 48px 16px;">
                                No products found matching your filters in <strong><?= View::escape($activeBranchName) ?></strong>.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($productsWithLots as $p): ?>
                            <tr class="lots-prod-row" data-search="<?= htmlspecialchars(strtolower($p['product_name'] . ' ' . $p['sku'] . ' ' . $p['barcode']), ENT_QUOTES, 'UTF-8') ?>" onclick='openProductLotsDrawer(<?= htmlspecialchars(json_encode($p), ENT_QUOTES, "UTF-8") ?>)'>
                                <td><span class="inv-code-pill"><?= View::escape($p['sku'] ?? 'N/A') ?></span></td>
                                <td><span class="inv-code-pill"><?= View::escape($p['barcode'] ?: '-') ?></span></td>
                                <td><strong style="color: #0f172a; font-size: 0.85rem;"><?= View::escape($p['product_name']) ?></strong></td>
                                <td style="text-align: right; font-weight: 700; color: #0f172a;">
                                    <?= number_format($p['active_lots_count']) ?> Active Lots
                                </td>
                                <td style="text-align: right; font-size: 1.02rem; font-weight: 800; font-family: ui-monospace, monospace; color: <?= $p['total_lot_stock'] > 0 ? '#166534' : '#dc2626' ?>;">
                                    <?= number_format($p['total_lot_stock'], 2) ?>
                                </td>
                                <td style="text-align: right; font-family: ui-monospace, monospace; color: #64748b; font-weight: 600;">
                                    <?= View::formatMoney($p['default_cost']) ?>
                                </td>
                                <td style="text-align: right; font-family: ui-monospace, monospace; font-weight: 700; color: #0f172a;">
                                    <?= View::formatMoney($p['default_price']) ?>
                                </td>
                                <td style="text-align: right;" onclick="event.stopPropagation();">
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

<script src="assets/js/curtiss_select.js"></script>
<script>
function esc(str) {
    if (!str) return '';
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

document.addEventListener('DOMContentLoaded', () => {
    // Register Real-time Search Hook on Floating Bottom Nav Bar
    function registerBottomSearch() {
        if (window.CurtissNav && window.CurtissNav.enablePageSearch) {
            window.CurtissNav.enablePageSearch({
                placeholder: 'Search Product Name, SKU or Barcode...',
                onSearch: function(query) {
                    filterLotsTableRealtime(query);
                }
            });
        } else {
            setTimeout(registerBottomSearch, 50);
        }
    }
    registerBottomSearch();

    document.querySelectorAll('.curtiss-select-enhance').forEach(el => {
        if (window.CurtissSearchableSelect) {
            CurtissSearchableSelect.attach(el);
        }
    });
});

function filterLotsTableRealtime(query) {
    const q = query.toLowerCase().trim();
    const rows = document.querySelectorAll('#lotsTable tbody tr.lots-prod-row');

    rows.forEach(tr => {
        const searchAttr = tr.getAttribute('data-search') || '';
        if (!q || searchAttr.includes(q)) {
            tr.style.display = '';
        } else {
            tr.style.display = 'none';
        }
    });
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
