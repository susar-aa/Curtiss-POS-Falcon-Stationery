<?php
/**
 * Curtiss POS - Purchase Order (PO) Creation Panel
 * Fixed Viewport Layout with Internally Scrollable Cart Table
 */
?>
<link rel="stylesheet" href="assets/css/curtiss_select.css">

<!-- Floating Top-Right Action Header -->
<div id="curtiss-floating-header-right" style="position: fixed; top: 16px; right: 20px; z-index: 1050; display: flex; align-items: center; gap: 8px;">
    <a href="purchase_orders" class="po-top-btn po-btn-ghost">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Back to POs
    </a>
    <button type="button" class="po-top-btn po-btn-primary" onclick="submitPoForm()" id="poSaveBtn">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
        Save Purchase Order
        <kbd class="po-kbd">Ctrl+S</kbd>
    </button>
</div>

<style>
/* Hide bottom floating dock on full-screen procurement entry panels */
#curtiss-dock {
    display: none !important;
}

/* ── Reset & Fixed Viewport ── */
.po-panel-root {
    width: 100%;
    max-width: 100%;
    height: calc(100vh - 110px);
    display: flex;
    flex-direction: column;
    box-sizing: border-box;
    overflow: hidden;
    padding-bottom: 4px;
    font-family: inherit;
}

/* ── Top Header Buttons ── */
.po-top-btn {
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
.po-btn-ghost {
    background: rgba(255, 255, 255, 0.92);
    border-color: rgba(226, 232, 240, 0.9);
    color: #0f172a;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}
.po-btn-ghost:hover {
    background: #ffffff;
    border-color: #cbd5e1;
    transform: translateY(-1px);
}
.po-btn-primary {
    background: #0f172a;
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.22);
}
.po-btn-primary:hover {
    background: #1e293b;
    transform: translateY(-1px);
}
.po-kbd {
    display: inline-block;
    padding: 1px 5px;
    font-size: 10px;
    font-weight: 800;
    line-height: 1;
    color: #94a3b8;
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.25);
    border-radius: 4px;
    margin-left: 4px;
}

/* ── Section Cards ── */
.po-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
    display: flex;
    flex-direction: column;
}
.po-card-strip {
    background: #0f172a;
    color: #ffffff;
    padding: 7px 12px;
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
}
.po-card-body {
    padding: 10px 14px;
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    background: #ffffff;
}

/* ── Field Styles ── */
.po-field-label {
    font-size: 0.7rem;
    font-weight: 800;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 4px;
    display: block;
}
.po-input, .po-select {
    width: 100%;
    height: 34px;
    padding: 0 10px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 0.82rem;
    font-weight: 600;
    color: #0f172a;
    background: #ffffff;
    outline: none;
    box-sizing: border-box;
    transition: all 0.15s ease;
}
.po-input:focus, .po-select:focus {
    border-color: #0f172a;
    box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.08);
}

/* ── Catalog Search Bar ── */
.po-search-wrap {
    position: relative;
    width: 100%;
    margin-top: 8px;
    flex-shrink: 0;
}
.po-search-input {
    width: 100%;
    height: 38px;
    padding: 0 14px 0 38px;
    border: 1px solid #cbd5e1;
    border-radius: 9px;
    font-size: 0.82rem;
    font-weight: 500;
    color: #0f172a;
    background: #ffffff;
    outline: none;
    box-sizing: border-box;
    transition: all 0.15s ease;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
}
.po-search-input:focus {
    border-color: #0f172a;
    box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.08);
}
.po-search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    pointer-events: none;
}

/* ── Autocomplete Dropdown Menu ── */
.po-ac-menu {
    display: none;
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    right: 0;
    max-height: 280px;
    overflow-y: auto;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    box-shadow: 0 15px 35px rgba(15, 23, 42, 0.18);
    z-index: 1080;
}
.po-ac-menu.show { display: block; }
.po-ac-item {
    padding: 8px 12px;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.12s ease;
}
.po-ac-item:last-child { border-bottom: none; }
.po-ac-item:hover, .po-ac-item.active {
    background: #f1f5f9;
}
.po-ac-item.active {
    background: #0f172a !important;
    color: #ffffff !important;
}
.po-ac-item.active .po-ac-sku { color: #94a3b8 !important; }
.po-ac-item.active .po-ac-cost { color: #38bdf8 !important; }

/* ── Cart Table & Internal Scroll ── */
.po-cart-panel {
    flex: 1;
    min-height: 0;
    display: flex;
    flex-direction: column;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
    margin-top: 8px;
    margin-bottom: 8px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
}
.po-cart-scroll {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
}
.po-cart-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.82rem;
}
.po-cart-table thead th {
    position: sticky;
    top: 0;
    z-index: 10;
    background: #0f172a;
    color: #ffffff;
    padding: 8px 10px;
    font-size: 0.73rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    border: none;
}
.po-cart-table tbody tr {
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.12s ease;
}
.po-cart-table tbody tr:nth-child(even) {
    background: rgba(248, 250, 252, 0.57);
}
.po-cart-table tbody tr:hover {
    background: #f1f5f9 !important;
}
.po-cart-table td {
    padding: 6px 10px;
    vertical-align: middle;
}
.del-row-btn {
    width: 26px;
    height: 26px;
    border-radius: 6px;
    border: 1px solid #fecaca;
    background: #fef2f2;
    color: #dc2626;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.12s ease;
    font-size: 0.8rem;
    font-weight: 800;
}
.del-row-btn:hover {
    background: #fee2e2;
    border-color: #f87171;
}

/* ── Bottom Terms & Summary Bar ── */
.po-bottom-panel {
    flex-shrink: 0;
    display: grid;
    grid-template-columns: 1fr 360px;
    gap: 12px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 8px 12px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
}
.po-upper-grid {
    display: grid;
    grid-template-columns: 38% 62%;
    gap: 12px;
    flex-shrink: 0;
}
.po-schedule-grid {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 12px;
    align-items: center;
}
@media (max-width: 900px) {
    .po-panel-root {
        height: auto !important;
        min-height: calc(100vh - 110px);
        overflow-y: auto;
    }
    .po-upper-grid {
        grid-template-columns: 1fr !important;
    }
    .po-schedule-grid {
        grid-template-columns: 1fr !important;
    }
    .po-bottom-panel {
        grid-template-columns: 1fr !important;
    }
}
.po-notes-textarea {
    width: 100%;
    height: 48px;
    padding: 6px 10px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 0.78rem;
    font-family: inherit;
    color: #0f172a;
    resize: none;
    outline: none;
    box-sizing: border-box;
}
.po-notes-textarea:focus {
    border-color: #0f172a;
}
</style>

<div class="po-panel-root">
    <!-- Header Title (Headline Only) -->
    <div style="flex-shrink: 0; display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
        <div style="display: flex; align-items: center; gap: 8px;">
            <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
            <h1 class="prod-title" style="margin: 0; font-size: 1.25rem; font-weight: 800; color: #0f172a;">Purchase Order (PO)</h1>
            <span style="font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; background: #e0f2fe; color: #0369a1; padding: 3px 8px; border-radius: 999px;">NEW PURCHASE ORDER</span>
        </div>
        <div style="font-size: 0.75rem; color: #64748b; font-weight: 600;">
            Branch: <strong style="color: #0f172a;"><?= View::escape($activeBranchName) ?></strong>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (!empty($errorMsg)): ?>
        <div style="flex-shrink: 0; padding: 8px 12px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; color: #991b1b; font-size: 0.8rem; font-weight: 600; margin-bottom: 6px;">
            ✕ <?= View::escape($errorMsg) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="po_create" id="poForm" style="display: flex; flex-direction: column; flex: 1; min-height: 0;">
        <!-- Upper Grid: Supplier Card & Order Schedule Card -->
        <div class="po-upper-grid">
            <!-- Supplier / Vendor Card -->
            <div class="po-card">
                <div class="po-card-strip">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <span>SUPPLIER / VENDOR *</span>
                    </div>
                    <span id="suppLinkedBadge" style="font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase;">0 LINKED PRODUCTS</span>
                </div>
                <div class="po-card-body" style="padding: 8px 10px;">
                    <div>
                        <select name="supplier_id" id="poSupplierSelect" class="curtiss-select-enhance" data-create-type="supplier" onchange="onSupplierChange(this.value)" required>
                            <option value="">-- Select Supplier --</option>
                            <?php foreach ($suppliers as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= View::escape($s['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Dynamic Supplier Info Box -->
                    <div id="suppInfoBox" style="margin-top: 6px;">
                        <div id="suppEmptyPlaceholder" style="padding: 6px; text-align: center; color: #94a3b8; font-size: 0.74rem;">
                            Select a supplier to load profile & payables balance
                        </div>

                        <!-- Dynamic Content when Selected -->
                        <div id="suppDetailsContent" style="display: none;">
                            <div id="suppOutstandingAlert" style="display: none; background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; padding: 4px 8px; margin-bottom: 6px; font-size: 0.74rem; font-weight: 700; color: #b91c1c; justify-content: space-between; align-items: center;">
                                <span>⚠️ Outstanding Payable:</span>
                                <span id="suppOutstandingVal" style="font-family: monospace;">LKR 0.00</span>
                            </div>

                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div>
                                    <div id="suppNameTitle" style="font-size: 0.86rem; font-weight: 800; color: #0f172a; line-height: 1.2;">—</div>
                                    <div id="suppContactLine" style="font-size: 0.72rem; color: #64748b; margin-top: 2px;">—</div>
                                    <div id="suppAddressLine" style="font-size: 0.70rem; color: #94a3b8; margin-top: 1px;">—</div>
                                </div>
                                <span id="suppCodeBadge" style="font-size: 10px; font-weight: 800; background: #f1f5f9; color: #0f172a; padding: 2px 6px; border-radius: 4px; font-family: monospace;">ID #—</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order & Delivery Schedule Card -->
            <div class="po-card">
                <div class="po-card-strip">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <span>ORDER & DELIVERY SCHEDULE</span>
                    </div>
                    <span style="font-size: 10px; font-weight: 700; color: #94a3b8;">PROCUREMENT SCHEDULE</span>
                </div>
                <div class="po-card-body" style="padding: 10px 14px;">
                    <div class="po-schedule-grid">
                        <div>
                            <label class="po-field-label">PO NUMBER *</label>
                            <div style="font-size: 0.88rem; font-weight: 800; color: #0284c7; background: #f0f9ff; padding: 6px 10px; border-radius: 8px; border: 1px solid #bae6fd; font-family: monospace; display: flex; align-items: center; gap: 6px;">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                <span><?= View::escape($nextPoNumber) ?></span>
                            </div>
                        </div>

                        <div>
                            <label class="po-field-label">ORDER DATE *</label>
                            <input type="date" name="order_date" id="orderDateInput" class="po-input" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div>
                            <label class="po-field-label">EXPECTED DELIVERY *</label>
                            <input type="date" name="expected_date" id="expectedDateInput" class="po-input" value="<?= date('Y-m-d', strtotime('+7 days')) ?>" required>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Catalog Product Search Bar -->
        <div class="po-search-wrap">
            <svg class="po-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="catalogSearchInput" class="po-search-input" placeholder="Search catalog by product name, SKU, item code, or variation... (Press '/' or 'F2' to focus, ↑ / ↓ to navigate, Enter to add)" autocomplete="off">
            <!-- Autocomplete Dropdown Menu -->
            <div id="catalogSearchDropdown" class="po-ac-menu"></div>
        </div>

        <!-- Cart Table Panel (Only Table Rows Scroll) -->
        <div class="po-cart-panel">
            <div class="po-cart-scroll">
                <table class="po-cart-table" id="poCartTable">
                    <thead>
                        <tr>
                            <th style="width: 38px; text-align: center;">#</th>
                            <th style="width: 40%; text-align: left;">PRODUCT / VARIATION</th>
                            <th style="width: 14%; text-align: center;">QTY</th>
                            <th style="width: 18%; text-align: right;">UNIT COST (LKR)</th>
                            <th style="width: 22%; text-align: right;">TOTAL (LKR)</th>
                            <th style="width: 42px; text-align: center;">ACTION</th>
                        </tr>
                    </thead>
                    <tbody id="poCartTableBody">
                        <!-- Empty State Row -->
                        <tr id="emptyCartRow">
                            <td colspan="6" style="text-align: center; padding: 45px 12px; color: #94a3b8;">
                                <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.8"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                                    <div style="font-size: 0.88rem; font-weight: 700; color: #64748b;">No items added to this Purchase Order yet.</div>
                                    <div style="font-size: 0.75rem; color: #94a3b8;">Search catalog products above (press '/' or 'F2' to focus) and press Enter to add.</div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Bottom Panel: Terms & Totals Summary -->
        <div class="po-bottom-panel">
            <div>
                <label class="po-field-label">DELIVERY TERMS & PURCHASING NOTES</label>
                <textarea name="notes" class="po-notes-textarea" placeholder="Enter terms of delivery, payment conditions, or specific instructions for the supplier..."></textarea>
            </div>

            <div style="display: flex; flex-direction: column; justify-content: space-between;">
                <div style="display: flex; justify-content: space-between; font-size: 0.74rem; color: #64748b;">
                    <span>Total Items Count:</span>
                    <strong id="totalLinesCount" style="color: #0f172a;">0 lines (0 units)</strong>
                </div>

                <div style="display: flex; justify-content: space-between; font-size: 0.76rem; color: #64748b;">
                    <span>Items Subtotal:</span>
                    <strong id="subtotalVal" style="font-family: monospace; color: #0f172a;">LKR 0.00</strong>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.74rem; color: #64748b;">
                    <span>Discount (LKR):</span>
                    <input type="number" name="discount_amount" id="discountInput" value="0.00" min="0" step="0.01" style="width: 90px; height: 24px; text-align: right; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 6px; font-weight: 700; font-family: monospace; font-size: 0.76rem;" oninput="recalcCartTotals()">
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 4px; border-top: 1px dashed #cbd5e1; margin-top: 2px;">
                    <span style="font-size: 0.95rem; font-weight: 800; color: #0f172a;">Grand Total:</span>
                    <span id="grandTotalVal" style="font-size: 1.15rem; font-weight: 900; color: #15803d; font-family: monospace;">LKR 0.00</span>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="assets/js/curtiss_select.js"></script>
<script>
// Master Data
const suppliersList = <?= json_encode($suppliers) ?>;
const productsList = <?= json_encode($products) ?>;
const suppliersMap = {};
suppliersList.forEach(s => { suppliersMap[s.id] = s; });

// Cart State
let cartItems = []; // [{ id, product_id, name, sku, quantity, unit_cost, selling_price }]

// Supplier Selection Handler
function onSupplierChange(suppId) {
    const s = suppliersMap[suppId];
    const emptyBox = document.getElementById('suppEmptyPlaceholder');
    const detailsBox = document.getElementById('suppDetailsContent');
    const linkedBadge = document.getElementById('suppLinkedBadge');

    if (!s) {
        emptyBox.style.display = 'block';
        detailsBox.style.display = 'none';
        linkedBadge.innerText = '0 LINKED PRODUCTS';
        return;
    }

    emptyBox.style.display = 'none';
    detailsBox.style.display = 'block';
    linkedBadge.innerText = `${s.linked_products || 0} LINKED PRODUCTS`;

    document.getElementById('suppNameTitle').innerText = s.name;
    document.getElementById('suppCodeBadge').innerText = s.supplier_code ? s.supplier_code : `ID #${s.id}`;
    
    let contactInfo = [];
    if (s.phone) contactInfo.push(s.phone);
    if (s.email) contactInfo.push(s.email);
    document.getElementById('suppContactLine').innerText = contactInfo.length ? contactInfo.join(' • ') : 'No phone/email recorded';
    document.getElementById('suppAddressLine').innerText = s.address ? s.address : 'No physical address recorded';

    // Outstanding Payables Alert
    const outAlert = document.getElementById('suppOutstandingAlert');
    const outAmt = parseFloat(s.outstanding_payable || 0);
    if (outAmt > 0) {
        outAlert.style.display = 'flex';
        document.getElementById('suppOutstandingVal').innerText = 'LKR ' + outAmt.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    } else {
        outAlert.style.display = 'none';
    }

    // Auto-advance focus to Order Date field
    setTimeout(() => {
        const orderDateInput = document.getElementById('orderDateInput');
        if (orderDateInput) {
            orderDateInput.focus();
        }
    }, 60);
}

// Catalog Search & Autocomplete
const searchInput = document.getElementById('catalogSearchInput');
const searchMenu = document.getElementById('catalogSearchDropdown');
let filteredProducts = [];
let highlightedIdx = -1;

searchInput.addEventListener('input', () => {
    const q = searchInput.value.toLowerCase().trim();
    if (!q) {
        searchMenu.classList.remove('show');
        searchMenu.innerHTML = '';
        filteredProducts = [];
        highlightedIdx = -1;
        return;
    }

    filteredProducts = productsList.filter(p => {
        return (p.name && p.name.toLowerCase().includes(q)) ||
               (p.sku && p.sku.toLowerCase().includes(q)) ||
               (p.barcode && p.barcode.toLowerCase().includes(q));
    }).slice(0, 15);

    renderSearchDropdown();
});

searchInput.addEventListener('keydown', (e) => {
    if (!searchMenu.classList.contains('show') || !filteredProducts.length) return;

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        highlightedIdx = (highlightedIdx + 1) % filteredProducts.length;
        renderSearchDropdown();
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        highlightedIdx = (highlightedIdx - 1 + filteredProducts.length) % filteredProducts.length;
        renderSearchDropdown();
    } else if (e.key === 'Enter') {
        e.preventDefault();
        if (highlightedIdx >= 0 && highlightedIdx < filteredProducts.length) {
            addProductToCart(filteredProducts[highlightedIdx]);
        } else if (filteredProducts.length > 0) {
            addProductToCart(filteredProducts[0]);
        }
    } else if (e.key === 'Escape') {
        searchMenu.classList.remove('show');
    }
});

function renderSearchDropdown() {
    if (!filteredProducts.length) {
        searchMenu.innerHTML = '<div style="padding: 10px 14px; color: #94a3b8; font-size: 0.78rem;">No products found matching query</div>';
        searchMenu.classList.add('show');
        return;
    }

    let html = '';
    filteredProducts.forEach((p, idx) => {
        const isAct = idx === highlightedIdx ? 'active' : '';
        const cost = parseFloat(p.cost || 0).toFixed(2);
        const sell = parseFloat(p.selling_price || 0).toFixed(2);
        html += `
            <div class="po-ac-item ${isAct}" onclick="selectProductByIndex(${idx})">
                <div>
                    <div style="font-weight: 700; font-size: 0.82rem;">${p.name}</div>
                    <div class="po-ac-sku" style="font-size: 0.70rem; color: #64748b;">SKU: ${p.sku || '—'} ${p.barcode ? '• Barcode: ' + p.barcode : ''}</div>
                </div>
                <div style="text-align: right;">
                    <div class="po-ac-cost" style="font-family: monospace; font-weight: 800; font-size: 0.8rem; color: #0284c7;">Cost: LKR ${cost}</div>
                    <div style="font-size: 0.68rem; color: #64748b;">Selling: LKR ${sell}</div>
                </div>
            </div>
        `;
    });

    searchMenu.innerHTML = html;
    searchMenu.classList.add('show');
}

function selectProductByIndex(idx) {
    if (filteredProducts[idx]) {
        addProductToCart(filteredProducts[idx]);
    }
}

// Add Product to Cart Table
function addProductToCart(prod) {
    searchMenu.classList.remove('show');
    searchInput.value = '';
    highlightedIdx = -1;

    // If item already exists in cart, increment quantity
    const existing = cartItems.find(it => it.product_id === prod.id);
    if (existing) {
        existing.quantity += 1;
        renderCartTable();
        focusRowQty(existing.product_id);
        return;
    }

    cartItems.push({
        product_id: prod.id,
        name: prod.name,
        sku: prod.sku || '',
        quantity: 1,
        unit_cost: parseFloat(prod.cost || 0),
        selling_price: parseFloat(prod.selling_price || 0)
    });

    renderCartTable();
    focusRowQty(prod.id);
}

// Focus and highlight QTY input field so typing immediately enters new value
function focusRowQty(prodId) {
    setTimeout(() => {
        const input = document.getElementById(`qty_input_${prodId}`);
        if (input) {
            input.focus();
            input.select();
        }
    }, 40);
}

// QTY Enter advances to Unit Cost (highlighted)
function handleQtyKeydown(e, prodId) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const costInput = document.getElementById(`cost_input_${prodId}`);
        if (costInput) {
            costInput.focus();
            costInput.select();
        }
    }
}

// Unit Cost Enter loops back to Catalog Search Bar (ready for next product)
function handleCostKeydown(e, prodId) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const search = document.getElementById('catalogSearchInput');
        if (search) {
            search.value = '';
            search.focus();
            search.select();
        }
    }
}

// Render Cart Table Rows
function renderCartTable() {
    const tbody = document.getElementById('poCartTableBody');
    if (!cartItems.length) {
        tbody.innerHTML = `
            <tr id="emptyCartRow">
                <td colspan="6" style="text-align: center; padding: 45px 12px; color: #94a3b8;">
                    <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.8"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                        <div style="font-size: 0.88rem; font-weight: 700; color: #64748b;">No items added to this Purchase Order yet.</div>
                        <div style="font-size: 0.75rem; color: #94a3b8;">Search catalog products above (press '/' or 'F2' to focus) and press Enter to add.</div>
                    </div>
                </td>
            </tr>
        `;
        recalcCartTotals();
        return;
    }

    let rowsHtml = '';
    cartItems.forEach((it, idx) => {
        const lineTotal = (it.quantity * it.unit_cost).toFixed(2);
        rowsHtml += `
            <tr id="cartRow_${it.product_id}">
                <td style="text-align: center; color: #64748b; font-size: 0.76rem; font-weight: 700;">${idx + 1}</td>
                <td>
                    <div style="font-weight: 700; color: #0f172a; font-size: 0.83rem;">${it.name}</div>
                    <div style="font-size: 0.70rem; color: #64748b; font-family: monospace;">SKU: ${it.sku || '—'}</div>
                    <input type="hidden" name="items[${idx}][product_id]" value="${it.product_id}">
                    <input type="hidden" name="items[${idx}][selling_price]" value="${it.selling_price}">
                </td>
                <td style="text-align: center;">
                    <input type="number" name="items[${idx}][quantity]" id="qty_input_${it.product_id}" min="1" step="any" value="${it.quantity}" style="width: 75px; height: 30px; text-align: center; border: 1px solid #cbd5e1; border-radius: 6px; font-weight: 700; font-family: monospace; font-size: 0.82rem;" oninput="updateItemQty(${it.product_id}, this.value)" onkeydown="handleQtyKeydown(event, ${it.product_id})">
                </td>
                <td style="text-align: right;">
                    <input type="number" name="items[${idx}][unit_cost]" id="cost_input_${it.product_id}" min="0" step="0.01" value="${it.unit_cost.toFixed(2)}" style="width: 100px; height: 30px; text-align: right; border: 1px solid #cbd5e1; border-radius: 6px; font-weight: 700; font-family: monospace; font-size: 0.82rem; padding: 0 6px;" oninput="updateItemCost(${it.product_id}, this.value)" onkeydown="handleCostKeydown(event, ${it.product_id})">
                </td>
                <td style="text-align: right; font-weight: 800; font-family: monospace; color: #0f172a; font-size: 0.85rem;" id="lineTotal_${it.product_id}">
                    ${lineTotal}
                </td>
                <td style="text-align: center;">
                    <button type="button" class="del-row-btn" onclick="removeCartItem(${it.product_id})" title="Remove Item">✕</button>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = rowsHtml;
    recalcCartTotals();
}

function updateItemQty(prodId, val) {
    const it = cartItems.find(i => i.product_id === prodId);
    if (it) {
        it.quantity = Math.max(0, parseFloat(val) || 0);
        const lineTotalSpan = document.getElementById(`lineTotal_${prodId}`);
        if (lineTotalSpan) {
            lineTotalSpan.innerText = (it.quantity * it.unit_cost).toFixed(2);
        }
        recalcCartTotals();
    }
}

function updateItemCost(prodId, val) {
    const it = cartItems.find(i => i.product_id === prodId);
    if (it) {
        it.unit_cost = Math.max(0, parseFloat(val) || 0);
        const lineTotalSpan = document.getElementById(`lineTotal_${prodId}`);
        if (lineTotalSpan) {
            lineTotalSpan.innerText = (it.quantity * it.unit_cost).toFixed(2);
        }
        recalcCartTotals();
    }
}

function removeCartItem(prodId) {
    cartItems = cartItems.filter(i => i.product_id !== prodId);
    renderCartTable();
}

// Calculate Summary Totals
function recalcCartTotals() {
    let subtotal = 0;
    let totalUnits = 0;

    cartItems.forEach(it => {
        subtotal += (it.quantity * it.unit_cost);
        totalUnits += it.quantity;
    });

    const discount = Math.max(0, parseFloat(document.getElementById('discountInput').value) || 0);
    const grandTotal = Math.max(0, subtotal - discount);

    document.getElementById('totalLinesCount').innerText = `${cartItems.length} lines (${totalUnits} units)`;
    document.getElementById('subtotalVal').innerText = 'LKR ' + subtotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('grandTotalVal').innerText = 'LKR ' + grandTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

// Form Submission with Validation & Double Submit Prevention
let isSubmitting = false;
function submitPoForm() {
    if (isSubmitting) return;

    const suppSelect = document.getElementById('poSupplierSelect');
    if (!suppSelect.value) {
        alert('Please select a Supplier before saving the Purchase Order.');
        suppSelect.focus();
        return;
    }

    if (!cartItems.length) {
        alert('Please add at least one product item to the Purchase Order.');
        searchInput.focus();
        return;
    }

    isSubmitting = true;
    const saveBtn = document.getElementById('poSaveBtn');
    if (saveBtn) {
        saveBtn.disabled = true;
        saveBtn.innerText = 'Saving PO...';
    }

    const form = document.getElementById('poForm');
    form.submit();
}

// Keyboard Shortcuts
document.addEventListener('keydown', (e) => {
    // Focus search on '/' or 'F2'
    if (e.key === '/' || e.key === 'F2') {
        if (document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
            e.preventDefault();
            searchInput.focus();
            searchInput.select();
        }
    }
    // Save on Ctrl + S
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
        e.preventDefault();
        submitPoForm();
    }
    // Close dropdown on click outside
    if (e.key === 'Escape') {
        searchMenu.classList.remove('show');
    }
});

// Click Outside search menu closes it
document.addEventListener('click', (e) => {
    if (!e.target.closest('.po-search-wrap')) {
        searchMenu.classList.remove('show');
    }
});

// Initialize Searchable Select & Keyboard Navigation Sequence
document.addEventListener('DOMContentLoaded', () => {
    const suppSelect = document.getElementById('poSupplierSelect');
    if (suppSelect && window.CurtissSearchableSelect) {
        const instance = CurtissSearchableSelect.attach(suppSelect);
        // Automatically open supplier combobox and focus its search input immediately on page load
        setTimeout(() => {
            if (instance) {
                instance.toggleMenu();
                if (instance.searchInput) {
                    instance.searchInput.focus();
                }
            }
        }, 80);
    }

    // Order Date Enter Key advances to Expected Delivery Date
    const orderDateEl = document.getElementById('orderDateInput');
    if (orderDateEl) {
        orderDateEl.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const expEl = document.getElementById('expectedDateInput');
                if (expEl) expEl.focus();
            }
        });
    }

    // Expected Delivery Date Enter Key advances to Catalog Search Bar
    const expDateEl = document.getElementById('expectedDateInput');
    if (expDateEl) {
        expDateEl.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const search = document.getElementById('catalogSearchInput');
                if (search) {
                    search.focus();
                    search.select();
                }
            }
        });
    }
});
</script>
