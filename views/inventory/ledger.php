<?php
/**
 * Curtiss POS - Stock Movement Ledger Master View
 * Adheres strictly to Curtiss Master Rules:
 * Fixed Viewport, Zebra Striping (48px rows), Sticky Headers,
 * Inline 34px Filter Selects, Floating Bottom Nav Realtime Search,
 * Server-Side Pagination (25, 50, 75, 100, 500) & Slide-in Detail Drawer.
 */
?>
<link rel="stylesheet" href="assets/css/curtiss_select.css">
<script src="assets/js/curtiss_select.js"></script>

<!-- Floating Top-Right Action Header (Action Controls Only - Rule 19) -->
<div id="curtiss-floating-header-right" style="position: fixed; top: 16px; right: 20px; z-index: 1050; display: flex; align-items: center; gap: 8px;">
    <button type="button" class="inv-btn inv-btn-ghost" onclick="exportLedgerCsv()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Export CSV
    </button>
    <a href="inventory_lots.php" class="inv-btn inv-btn-ghost">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
        Price & Cost Lots
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
    cursor: pointer;
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
    white-space: nowrap;
}
.mvt-badge {
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
    font-weight: 800;
    letter-spacing: 0.02em;
    white-space: nowrap;
    text-transform: uppercase;
    box-sizing: border-box;
    vertical-align: middle;
}
.mvt-grn { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
.mvt-opening { background: #ccfbf1; color: #0f766e; border: 1px solid #99f6e4; }
.mvt-sale { background: #ffedd5; color: #c2410c; border: 1px solid #fed7aa; }
.mvt-cust-return { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
.mvt-supp-return { background: #f3e8ff; color: #7e22ce; border: 1px solid #e9d5ff; }
.mvt-transfer-in { background: #cff4fc; color: #087990; border: 1px solid #b6effb; }
.mvt-transfer-out { background: #e0e7ff; color: #4338ca; border: 1px solid #c7d2fe; }
.mvt-adjustment { background: #fef9c3; color: #a16207; border: 1px solid #fef08a; }
.mvt-damage { background: #ffe4e6; color: #be123c; border: 1px solid #fecdd3; }
.mvt-default { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }

.qty-plus { color: #166534; font-weight: 800; font-family: ui-monospace, monospace; }
.qty-minus { color: #dc2626; font-weight: 800; font-family: ui-monospace, monospace; }

/* ── Slide-in Detail Drawer ── */
.inv-drawer-backdrop {
    position: fixed; inset: 0; background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);
    z-index: 9990; opacity: 0; visibility: hidden; transition: all 0.25s ease;
}
.inv-drawer-backdrop.active { opacity: 1; visibility: visible; }
.inv-drawer {
    position: fixed; top: 0; right: 0; bottom: 0; width: 480px; max-width: 95vw;
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
.detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.detail-box { background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px; }
.detail-label { font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; }
.detail-val { font-size: 13px; font-weight: 700; color: #0f172a; margin-top: 2px; }
</style>

<!-- Fixed Screen Viewport Container -->
<div style="width: 100%; max-width: 100%; height: calc(100vh - 110px); display: flex; flex-direction: column; box-sizing: border-box; overflow: hidden; padding-bottom: 8px;">

    <!-- Page Header (Headline Only + Inline 34px Filter Select - Rule 3 & 5) -->
    <div class="inv-header" style="flex-shrink: 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 0.85rem;">
        <div style="display: flex; align-items: center; gap: 8px;">
            <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
            <h1 class="inv-title">Stock Movement Ledger</h1>
        </div>

        <!-- Inline Filter Controls Above Table Right Corner (Height: 34px) -->
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-left: auto;">
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Movement Type:</span>
                <select id="filterMovementType" class="curtiss-select-enhance" onchange="applyLedgerFilter()" style="height: 34px; min-height: 34px; padding: 0 14px; border-radius: 999px; font-size: 12px; font-weight: 700; color: #334155; background: rgba(255,255,255,0.95); border: 1px solid rgba(203,213,225,0.9); box-shadow: 0 2px 8px rgba(0,0,0,0.04); min-width: 180px;">
                    <option value="">All Movement Types</option>
                    <option value="opening_stock" <?= ($filters['movement_type'] === 'opening_stock') ? 'selected' : '' ?>>Opening Stock</option>
                    <option value="purchase_grn" <?= ($filters['movement_type'] === 'purchase_grn') ? 'selected' : '' ?>>Purchase / GRN</option>
                    <option value="sale" <?= ($filters['movement_type'] === 'sale') ? 'selected' : '' ?>>Sale</option>
                    <option value="customer_return" <?= ($filters['movement_type'] === 'customer_return') ? 'selected' : '' ?>>Customer Return</option>
                    <option value="supplier_return" <?= ($filters['movement_type'] === 'supplier_return') ? 'selected' : '' ?>>Supplier Return</option>
                    <option value="transfer_in" <?= ($filters['movement_type'] === 'transfer_in') ? 'selected' : '' ?>>Transfer In</option>
                    <option value="transfer_out" <?= ($filters['movement_type'] === 'transfer_out') ? 'selected' : '' ?>>Transfer Out</option>
                    <option value="adjustment" <?= ($filters['movement_type'] === 'adjustment') ? 'selected' : '' ?>>Adjustment</option>
                    <option value="damage_wastage" <?= ($filters['movement_type'] === 'damage_wastage') ? 'selected' : '' ?>>Damage / Wastage</option>
                </select>
            </div>
            <?php if (!empty($filters['movement_type']) || !empty($filters['search']) || !empty($filters['product_id'])): ?>
                <a href="inventory_ledger.php" class="inv-btn" style="height: 34px; min-height: 34px; padding: 0 14px; border-radius: 999px; font-size: 11px; font-weight: 700; text-decoration: none; color: #dc2626; background: #fef2f2; border: 1px solid #fca5a5;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    Reset
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Ledger Data Table Panel -->
    <div class="prod-table-panel" style="flex: 1; min-height: 0; display: flex; flex-direction: column;">
        <div class="prod-table-scroll" style="overflow-y: auto; flex: 1;">
            <table class="prod-table" id="ledgerTable">
                <thead>
                    <tr>
                        <th class="col-left" style="width: 140px;">TIMESTAMP</th>
                        <th class="col-left" style="width: 150px;">MOVEMENT TYPE</th>
                        <th class="col-left">PRODUCT</th>
                        <th class="col-left" style="width: 130px;">LOT NUMBER</th>
                        <th class="col-right" style="width: 120px;">QTY CHANGED</th>
                        <th class="col-right" style="width: 120px;">BALANCE AFTER</th>
                        <th class="col-left" style="width: 140px;">REF NO</th>
                        <th class="col-left" style="width: 110px;">USER</th>
                        <th class="col-left" style="width: 180px;">NOTES</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ledger)): ?>
                        <tr id="noRecordsRow">
                            <td colspan="9" style="text-align: center; color: #94a3b8; padding: 48px 16px;">
                                No stock ledger movements recorded matching your filters in <strong><?= View::escape($activeBranchName) ?></strong>.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($ledger as $row): ?>
                            <?php 
                            $qty = (float)$row['quantity']; 
                            $bal = (float)$row['balance_after'];
                            $isAddition = $qty >= 0;
                            $mvtClass = match($row['movement_type']) {
                                'purchase_grn'     => 'mvt-grn',
                                'opening_stock'    => 'mvt-opening',
                                'sale'             => 'mvt-sale',
                                'customer_return'  => 'mvt-cust-return',
                                'supplier_return'  => 'mvt-supp-return',
                                'transfer_in'      => 'mvt-transfer-in',
                                'transfer_out'     => 'mvt-transfer-out',
                                'adjustment'       => 'mvt-adjustment',
                                'damage_wastage'   => 'mvt-damage',
                                default            => 'mvt-default'
                            };
                            $mvtLabel = str_replace('_', ' ', strtoupper($row['movement_type']));
                            $searchBlob = implode(' ', [
                                $row['created_at'],
                                $mvtLabel,
                                $row['movement_type'],
                                $row['product_name'] ?? '',
                                $row['barcode'] ?? '',
                                $row['sku'] ?? '',
                                $row['lot_number'] ?? '',
                                $row['reference_no'] ?? '',
                                $row['username'] ?? '',
                                $row['notes'] ?? '',
                                number_format($qty, 2),
                                (string)$qty,
                                number_format($bal, 2),
                                (string)$bal
                            ]);
                            ?>
                            <tr class="inv-row" data-search="<?= htmlspecialchars(strtolower($searchBlob), ENT_QUOTES, 'UTF-8') ?>" onclick='openLedgerDetailDrawer(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, "UTF-8") ?>)'>
                                <td class="col-left" style="white-space: nowrap; color: #64748b; font-size: 11.5px; font-family: ui-monospace, monospace;">
                                    <strong><?= View::formatDate($row['created_at']) ?></strong>
                                </td>
                                <td class="col-left">
                                    <span class="mvt-badge <?= $mvtClass ?>"><?= View::escape($mvtLabel) ?></span>
                                </td>
                                <td class="col-left">
                                    <div style="font-weight: 700; color: #0f172a;"><?= View::escape($row['product_name']) ?></div>
                                    <span class="inv-code-pill" style="margin-top: 2px; font-size: 10px;"><?= View::escape($row['barcode']) ?></span>
                                </td>
                                <td class="col-left">
                                    <?php if (!empty($row['lot_number'])): ?>
                                        <span class="inv-code-pill"><?= View::escape($row['lot_number']) ?></span>
                                    <?php else: ?>
                                        <span style="color: #cbd5e1;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="col-right <?= $isAddition ? 'qty-plus' : 'qty-minus' ?>">
                                    <?= $isAddition ? '+' : '' ?><?= number_format($qty, 2) ?>
                                </td>
                                <td class="col-right" style="font-weight: 800; font-family: ui-monospace, monospace; color: #0f172a;">
                                    <?= number_format($bal, 2) ?>
                                </td>
                                <td class="col-left">
                                    <?php if (!empty($row['reference_no'])): ?>
                                        <span class="inv-code-pill" style="background: #f8fafc; border: 1px solid #cbd5e1;"><?= View::escape($row['reference_no']) ?></span>
                                    <?php else: ?>
                                        <span style="color: #cbd5e1;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="col-left" style="font-weight: 600; color: #475569; font-size: 12px;">
                                    <?= View::escape($row['username'] ?? 'System') ?>
                                </td>
                                <td class="col-left" style="color: #64748b; font-size: 11.5px; max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?= View::escape($row['notes'] ?: '—') ?>
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
                    <select id="tableLimitSelect" onchange="changeLedgerLimit(this.value)" style="height: 28px; padding: 0 8px; border-radius: 6px; font-size: 12px; font-weight: 700; border: 1px solid #cbd5e1; background: #fff;">
                        <?php foreach ([25, 50, 75, 100, 500] as $lim): ?>
                            <option value="<?= $lim ?>" <?= ($limit === $lim) ? 'selected' : '' ?>><?= $lim ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display: flex; gap: 4px;">
                    <button type="button" class="inv-btn inv-btn-ghost" style="height: 28px; padding: 0 10px; font-size: 11px;" <?= ($page <= 1) ? 'disabled style="opacity:0.4; cursor:not-allowed;"' : 'onclick="goToLedgerPage(' . ($page - 1) . ')"' ?>>← Prev</button>
                    <span style="font-size: 12px; font-weight: 700; padding: 4px 8px; color: #0f172a;"><?= $page ?> / <?= max(1, $totalPages) ?></span>
                    <button type="button" class="inv-btn inv-btn-ghost" style="height: 28px; padding: 0 10px; font-size: 11px;" <?= ($page >= $totalPages) ? 'disabled style="opacity:0.4; cursor:not-allowed;"' : 'onclick="goToLedgerPage(' . ($page + 1) . ')"' ?>>Next →</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Ledger Detail Slide-in Drawer -->
<div class="inv-drawer-backdrop" id="ledgerDrawerBackdrop" onclick="closeLedgerDetailDrawer()">
    <div class="inv-drawer" onclick="event.stopPropagation()">
        <div class="inv-drawer-header">
            <div>
                <h3 style="margin:0; font-size: 1.05rem; font-weight: 800; color: #0f172a;">Ledger Transaction Detail</h3>
                <span id="drawerMvtTypeBadge" style="margin-top: 4px; display: inline-block;"></span>
            </div>
            <button type="button" onclick="closeLedgerDetailDrawer()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: #64748b;">✕</button>
        </div>
        <div class="inv-drawer-body">
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
                <div style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">PRODUCT NAME</div>
                <div id="drawerProductName" style="font-size: 1rem; font-weight: 800; color: #0f172a; margin-top: 2px;"></div>
                <div style="display: flex; gap: 6px; margin-top: 6px;">
                    <span id="drawerSku" class="inv-code-pill"></span>
                    <span id="drawerBarcode" class="inv-code-pill"></span>
                </div>
            </div>

            <div class="detail-grid">
                <div class="detail-box">
                    <div class="detail-label">QUANTITY CHANGED</div>
                    <div id="drawerQtyChanged" class="detail-val" style="font-size: 1.1rem;"></div>
                </div>
                <div class="detail-box">
                    <div class="detail-label">BALANCE AFTER</div>
                    <div id="drawerBalanceAfter" class="detail-val" style="font-size: 1.1rem; font-family: ui-monospace, monospace;"></div>
                </div>
            </div>

            <div class="detail-grid">
                <div class="detail-box">
                    <div class="detail-label">LOT NUMBER</div>
                    <div id="drawerLotNumber" class="detail-val" style="font-family: ui-monospace, monospace;"></div>
                </div>
                <div class="detail-box">
                    <div class="detail-label">REFERENCE NO</div>
                    <div id="drawerRefNo" class="detail-val" style="font-family: ui-monospace, monospace;"></div>
                </div>
            </div>

            <div class="detail-grid">
                <div class="detail-box">
                    <div class="detail-label">DATE & TIME</div>
                    <div id="drawerTimestamp" class="detail-val" style="font-size: 12px;"></div>
                </div>
                <div class="detail-box">
                    <div class="detail-label">PERFORMED BY USER</div>
                    <div id="drawerUser" class="detail-val" style="font-size: 12px;"></div>
                </div>
            </div>

            <div class="detail-box" style="grid-column: span 2;">
                <div class="detail-label">TRANSACTION NOTES / REASON</div>
                <div id="drawerNotes" style="font-size: 12.5px; color: #334155; margin-top: 4px; line-height: 1.4; white-space: pre-wrap;"></div>
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
                placeholder: 'Search SKU, barcode, product, ref no, user...',
                initialValue: <?= json_encode($filters['search'] ?? '') ?>,
                onSearch: function(query) {
                    filterLedgerTableRealtime(query);
                },
                onEnter: function(query) {
                    const params = new URLSearchParams(window.location.search);
                    if (query && query.trim()) {
                        params.set('search', query.trim());
                    } else {
                        params.delete('search');
                    }
                    params.set('page', '1');
                    window.location.href = 'inventory_ledger.php?' + params.toString();
                }
            });
        } else {
            setTimeout(registerBottomSearch, 40);
        }
    }
    registerBottomSearch();
});

function applyLedgerFilter() {
    const mvt = document.getElementById('filterMovementType').value;
    const params = new URLSearchParams(window.location.search);
    if (mvt) {
        params.set('movement_type', mvt);
    } else {
        params.delete('movement_type');
    }
    params.set('page', '1');
    window.location.href = 'inventory_ledger.php?' + params.toString();
}

function changeLedgerLimit(limitVal) {
    const params = new URLSearchParams(window.location.search);
    params.set('limit', limitVal);
    params.set('page', '1');
    window.location.href = 'inventory_ledger.php?' + params.toString();
}

function goToLedgerPage(targetPage) {
    const params = new URLSearchParams(window.location.search);
    params.set('page', targetPage);
    window.location.href = 'inventory_ledger.php?' + params.toString();
}

function exportLedgerCsv() {
    const params = new URLSearchParams(window.location.search);
    window.location.href = 'inventory_ledger_export.php?' + params.toString();
}

function filterLedgerTableRealtime(query) {
    const q = (query || '').toLowerCase().trim();
    const rows = document.querySelectorAll('#ledgerTable tbody tr.inv-row');
    let visibleCount = 0;

    rows.forEach(tr => {
        const searchAttr = (tr.getAttribute('data-search') || '').toLowerCase();
        const rowText = tr.textContent.toLowerCase();

        const match = !q || searchAttr.includes(q) || rowText.includes(q);

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

function openLedgerDetailDrawer(row) {
    if (!row) return;

    document.getElementById('drawerProductName').textContent = row.product_name || 'N/A';
    document.getElementById('drawerBarcode').textContent = 'Barcode: ' + (row.barcode || 'N/A');
    document.getElementById('drawerSku').textContent = 'SKU: ' + (row.sku || 'N/A');

    const qty = parseFloat(row.quantity || 0);
    const isAddition = qty >= 0;
    const qtyEl = document.getElementById('drawerQtyChanged');
    qtyEl.textContent = (isAddition ? '+' : '') + qty.toFixed(2);
    qtyEl.style.color = isAddition ? '#166534' : '#dc2626';

    document.getElementById('drawerBalanceAfter').textContent = parseFloat(row.balance_after || 0).toFixed(2);
    document.getElementById('drawerLotNumber').textContent = row.lot_number || 'N/A';
    document.getElementById('drawerRefNo').textContent = row.reference_no || 'N/A';
    document.getElementById('drawerTimestamp').textContent = row.created_at || 'N/A';
    document.getElementById('drawerUser').textContent = row.username || 'System';
    document.getElementById('drawerNotes').textContent = row.notes || 'No notes provided.';

    const mvtClass = {
        'purchase_grn': 'mvt-grn',
        'opening_stock': 'mvt-opening',
        'sale': 'mvt-sale',
        'customer_return': 'mvt-cust-return',
        'supplier_return': 'mvt-supp-return',
        'transfer_in': 'mvt-transfer-in',
        'transfer_out': 'mvt-transfer-out',
        'adjustment': 'mvt-adjustment',
        'damage_wastage': 'mvt-damage'
    }[row.movement_type] || 'mvt-default';

    const mvtLabel = (row.movement_type || '').replace(/_/g, ' ').toUpperCase();
    document.getElementById('drawerMvtTypeBadge').className = 'mvt-badge ' + mvtClass;
    document.getElementById('drawerMvtTypeBadge').textContent = mvtLabel;

    document.getElementById('ledgerDrawerBackdrop').classList.add('active');
}

function closeLedgerDetailDrawer() {
    document.getElementById('ledgerDrawerBackdrop').classList.remove('active');
}
</script>
