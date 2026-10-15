<?php
/**
 * Curtiss POS - Stock Movement Ledger View (Redesign Theme & Design Rules Enforced)
 * Glassmorphism layout, floating bottom nav search, top-right header searchable select filter & detail drawer.
 */
?>
<link rel="stylesheet" href="assets/css/curtiss_select.css">

<!-- Floating Top-Right Action Header -->
<div id="curtiss-floating-header-right" style="position: fixed; top: 16px; right: 20px; z-index: 1050; display: flex; align-items: center; gap: 8px;">
    <form method="GET" action="inventory_ledger.php" id="headerLedgerFilterForm" style="margin: 0; padding: 0;">
        <input type="hidden" name="branch_id" value="<?= $branchId ?>">
        <select name="movement_type" id="headerMovementTypeSelect" class="inv-select curtiss-select-enhance" style="height: 38px; padding: 0 16px; border-radius: 999px; font-size: 12px; font-weight: 700; background: rgba(255,255,255,0.96); border: 1px solid rgba(226,232,240,0.9); color: #0f172a; min-width: 190px;" onchange="this.form.submit()">
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
    </form>
    <a href="inventory.php" class="inv-btn inv-btn-ghost">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        Back to Branch Inventory
    </a>
</div>

<style>
/* ── Page Layout & Typography ── */
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

/* Glassmorphism Container */
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

/* Table Styling */
.inv-table-wrap {
    width: 100%;
    overflow-x: auto;
    border-radius: 12px;
}
.inv-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 0.83rem;
}
.inv-table th {
    background: #f8fafc;
    color: #64748b;
    font-size: 0.68rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 10px 14px;
    border-bottom: 1px solid #e2e8f0;
    text-align: left;
    white-space: nowrap;
}
.inv-table td {
    padding: 12px 14px;
    border-bottom: 1px solid #f1f5f9;
    color: #334155;
    vertical-align: middle;
    transition: background 0.15s ease;
}
.inv-row {
    cursor: pointer;
}
.inv-row:hover td {
    background: #f8fafc;
}
.inv-row:last-child td {
    border-bottom: none;
}

/* Code Pills */
.inv-code-pill {
    display: inline-block;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.72rem;
    font-weight: 700;
    background: #f1f5f9;
    color: #334155;
    padding: 2px 7px;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
}

/* Movement Type Badges */
.mvt-badge {
    display: inline-flex;
    align-items: center;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.02em;
    white-space: nowrap;
    text-transform: uppercase;
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

/* Quantity Colors */
.qty-plus { color: #166534; font-weight: 800; font-family: ui-monospace, monospace; }
.qty-minus { color: #dc2626; font-weight: 800; font-family: ui-monospace, monospace; }

/* Pagination */
.inv-pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1rem;
    padding-top: 0.75rem;
    border-top: 1px solid #f1f5f9;
}
.page-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 6px 14px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    color: #0f172a;
    text-decoration: none;
    transition: all 0.15s ease;
}
.page-pill:hover {
    background: #f8fafc;
    border-color: #94a3b8;
}

/* Slide-in Drawer */
.inv-drawer-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(15, 23, 42, 0.4);
    backdrop-filter: blur(4px);
    z-index: 2000;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.25s ease;
}
.inv-drawer-backdrop.active {
    opacity: 1;
    pointer-events: auto;
}
.inv-drawer {
    position: fixed;
    top: 0;
    right: -480px;
    width: 440px;
    max-width: 90vw;
    height: 100vh;
    background: #ffffff;
    box-shadow: -10px 0 40px rgba(0, 0, 0, 0.15);
    z-index: 2001;
    transition: right 0.28s cubic-bezier(0.16, 1, 0.3, 1);
    display: flex;
    flex-direction: column;
}
.inv-drawer-backdrop.active .inv-drawer {
    right: 0;
}
.inv-drawer-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8fafc;
}
.inv-drawer-body {
    padding: 1.5rem;
    overflow-y: auto;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}
.inv-drawer-close {
    background: transparent;
    border: none;
    color: #64748b;
    cursor: pointer;
    padding: 4px;
    border-radius: 6px;
}
.inv-drawer-close:hover {
    background: #e2e8f0;
    color: #0f172a;
}
.detail-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}
.detail-box {
    background: #f8fafc;
    border: 1px solid #f1f5f9;
    border-radius: 10px;
    padding: 10px 12px;
}
.detail-label {
    font-size: 0.65rem;
    font-weight: 800;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.detail-val {
    font-size: 0.88rem;
    font-weight: 700;
    color: #0f172a;
    margin-top: 2px;
}
</style>

<!-- Header Title -->
<div class="inv-header">
    <h1 class="inv-title">Stock Movement Ledger</h1>
    <p class="inv-subtitle">Immutable append-only stock transaction history for <strong><?= View::escape($activeBranchName) ?></strong></p>
</div>

<!-- Main Table Card -->
<div class="inv-card">
    <div class="inv-table-wrap">
        <table class="inv-table" id="ledgerTable">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Movement Type</th>
                    <th>Product</th>
                    <th>Lot Number</th>
                    <th style="text-align: right;">Qty Changed</th>
                    <th style="text-align: right;">Balance After</th>
                    <th>Ref No</th>
                    <th>User</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($ledger)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; color: #94a3b8; padding: 2.5rem 1rem; font-weight: 500;">
                            No stock ledger movements recorded for the selected filter.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($ledger as $row): ?>
                        <?php 
                        $qty = (float)$row['quantity']; 
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
                        $searchData = strtolower(($row['product_name'] ?? '') . ' ' . ($row['barcode'] ?? '') . ' ' . ($row['lot_number'] ?? '') . ' ' . ($row['reference_no'] ?? '') . ' ' . ($row['notes'] ?? ''));
                        ?>
                        <tr class="inv-row" data-search="<?= View::escape($searchData) ?>" onclick="openLedgerDetailDrawer(<?= htmlspecialchars(json_encode($row, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>)">
                            <td style="white-space: nowrap; color: #64748b; font-size: 0.78rem;">
                                <strong><?= View::formatDate($row['created_at']) ?></strong>
                            </td>
                            <td>
                                <span class="mvt-badge <?= $mvtClass ?>"><?= View::escape($mvtLabel) ?></span>
                            </td>
                            <td>
                                <div style="font-weight: 700; color: #0f172a;"><?= View::escape($row['product_name']) ?></div>
                                <span class="inv-code-pill" style="margin-top: 2px; font-size: 0.68rem;"><?= View::escape($row['barcode']) ?></span>
                            </td>
                            <td>
                                <?php if (!empty($row['lot_number'])): ?>
                                    <span class="inv-code-pill"><?= View::escape($row['lot_number']) ?></span>
                                <?php else: ?>
                                    <span style="color: #cbd5e1;">—</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;" class="<?= $isAddition ? 'qty-plus' : 'qty-minus' ?>">
                                <?= $isAddition ? '+' : '' ?><?= number_format($qty, 2) ?>
                            </td>
                            <td style="text-align: right; font-weight: 800; font-family: ui-monospace, monospace; color: #0f172a;">
                                <?= number_format((float)$row['balance_after'], 2) ?>
                            </td>
                            <td>
                                <?php if (!empty($row['reference_no'])): ?>
                                    <span class="inv-code-pill" style="background: #f8fafc; border-color: #cbd5e1;"><?= View::escape($row['reference_no']) ?></span>
                                <?php else: ?>
                                    <span style="color: #cbd5e1;">—</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-weight: 600; color: #475569; font-size: 0.78rem;">
                                <?= View::escape($row['username'] ?? 'System') ?>
                            </td>
                            <td style="color: #64748b; font-size: 0.75rem; max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                <?= View::escape($row['notes'] ?? '—') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="inv-pagination">
            <div style="font-size: 0.78rem; color: #64748b; font-weight: 500;">
                Showing page <strong style="color: #0f172a;"><?= $page ?></strong> of <strong style="color: #0f172a;"><?= $totalPages ?></strong> (Total <?= $totalItems ?> transactions)
            </div>
            <div style="display: flex; gap: 8px;">
                <?php if ($page > 1): ?>
                    <a href="inventory_ledger.php?<?= http_build_query(array_merge($filters, ['branch_id' => $branchId, 'page' => $page - 1])) ?>" class="page-pill">
                        ‹ Previous
                    </a>
                <?php endif; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="inventory_ledger.php?<?= http_build_query(array_merge($filters, ['branch_id' => $branchId, 'page' => $page + 1])) ?>" class="page-pill">
                        Next ›
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Ledger Detail Slide-in Drawer -->
<div class="inv-drawer-backdrop" id="ledgerDrawerBackdrop" onclick="closeLedgerDetailDrawer()">
    <div class="inv-drawer" onclick="event.stopPropagation()">
        <div class="inv-drawer-header">
            <div>
                <h3 style="margin:0; font-size: 1.1rem; font-weight: 800; color: #0f172a;">Ledger Transaction Detail</h3>
                <span id="drawerMvtTypeBadge" style="margin-top: 4px; display: inline-block;"></span>
            </div>
            <button class="inv-drawer-close" onclick="closeLedgerDetailDrawer()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="inv-drawer-body">
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
                <div style="font-size: 0.68rem; font-weight: 800; color: #64748b; text-transform: uppercase;">PRODUCT NAME</div>
                <div id="drawerProductName" style="font-size: 1rem; font-weight: 800; color: #0f172a; margin-top: 2px;"></div>
                <div id="drawerBarcode" class="inv-code-pill" style="margin-top: 6px;"></div>
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
                    <div id="drawerTimestamp" class="detail-val" style="font-size: 0.8rem;"></div>
                </div>
                <div class="detail-box">
                    <div class="detail-label">PERFORMED BY USER</div>
                    <div id="drawerUser" class="detail-val" style="font-size: 0.8rem;"></div>
                </div>
            </div>

            <div class="detail-box" style="grid-column: span 2;">
                <div class="detail-label">TRANSACTION NOTES / REASON</div>
                <div id="drawerNotes" style="font-size: 0.85rem; color: #334155; margin-top: 4px; line-height: 1.4; white-space: pre-wrap;"></div>
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
                placeholder: 'Search Barcode, Product, Lot, Ref No...',
                onSearch: function(query) {
                    filterLedgerTableRealtime(query);
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

function filterLedgerTableRealtime(query) {
    const q = query.toLowerCase().trim();
    const rows = document.querySelectorAll('#ledgerTable tbody tr.inv-row');

    rows.forEach(tr => {
        const searchAttr = tr.getAttribute('data-search') || '';
        if (!q || searchAttr.includes(q)) {
            tr.style.display = '';
        } else {
            tr.style.display = 'none';
        }
    });
}

function openLedgerDetailDrawer(row) {
    if (!row) return;

    document.getElementById('drawerProductName').textContent = row.product_name || 'N/A';
    document.getElementById('drawerBarcode').textContent = row.barcode || 'N/A';

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
