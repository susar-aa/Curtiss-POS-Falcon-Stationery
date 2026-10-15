<?php
/**
 * Curtiss POS - Create Supplier Purchase Return Panel
 * Redesigned with Exact GRN & PO Panel Theme, Fixed Viewport Layout,
 * Internally Scrollable Cart Table, and Continuous Keyboard Navigation Loop.
 */
?>
<link rel="stylesheet" href="assets/css/curtiss_select.css">

<!-- Floating Top-Right Action Header -->
<div id="curtiss-floating-header-right" style="position: fixed; top: 16px; right: 20px; z-index: 1050; display: flex; align-items: center; gap: 8px;">
    <a href="supplier_returns" class="po-top-btn po-btn-ghost">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Cancel
    </a>
    <button type="button" class="po-top-btn po-btn-primary" onclick="submitReturnForm()" id="returnSaveBtn">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        Process Supplier Return
        <kbd class="po-kbd">Ctrl+S</kbd>
    </button>
</div>

<style>
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
.po-ac-item.active .po-ac-meta { color: #94a3b8 !important; }
.po-ac-item.active .po-ac-cost { color: #38bdf8 !important; }
.po-ac-item.active .po-ac-stock { color: #86efac !important; }

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
    height: 48px;
    min-height: 48px;
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.12s ease;
}
.po-cart-table tbody tr:nth-child(even) {
    background: rgba(248, 250, 252, 0.57);
}
.po-cart-table tbody tr:nth-child(odd) {
    background: #ffffff;
}
.po-cart-table tbody tr:hover {
    background: #f1f5f9 !important;
}
.po-cart-table td {
    height: 48px;
    padding: 0 10px;
    vertical-align: middle;
    box-sizing: border-box;
}

@media (max-width: 992px) {
    .po-panel-root { height: auto; min-height: calc(100vh - 110px); overflow-y: auto; }
    div[style*="grid-template-columns: 36% 64%"] { grid-template-columns: 1fr !important; }
    .po-bottom-panel { grid-template-columns: 1fr !important; }
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
    grid-template-columns: 1fr 400px;
    gap: 12px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 8px 12px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
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
            <h1 class="prod-title" style="margin: 0; font-size: 1.25rem; font-weight: 800; color: #0f172a;">
                Create Supplier Purchase Return
            </h1>
            <span style="font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; background: #fee2e2; color: #dc2626; padding: 3px 8px; border-radius: 999px;">
                PURCHASE RETURN PANEL
            </span>
        </div>
        <div style="font-size: 0.75rem; color: #64748b; font-weight: 600;">
            Returning Branch: <strong style="color: #0f172a;"><?= View::escape($activeBranchName ?? 'Main Branch') ?></strong>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (!empty($errorMsg)): ?>
        <div style="flex-shrink: 0; padding: 8px 12px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; color: #991b1b; font-size: 0.8rem; font-weight: 600; margin-bottom: 6px;">
            ✕ <?= View::escape($errorMsg) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="supplier_return_create" id="supplierReturnForm" style="display: flex; flex-direction: column; flex: 1; min-height: 0;" onsubmit="handleFormSubmit(this)">
        <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrfToken() ?>">
        <!-- Upper Grid: Supplier Card & Return Schedule Card -->
        <div style="display: grid; grid-template-columns: 36% 64%; gap: 12px; flex-shrink: 0;">
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
                        <select name="supplier_id" id="supplierSelect" class="curtiss-select-enhance" data-create-type="supplier" onchange="onSupplierChange(this.value)" required>
                            <option value="">-- Select Supplier --</option>
                            <?php foreach ($suppliers as $s): ?>
                                <option value="<?= $s['id'] ?>">
                                    <?= View::escape($s['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Dynamic Supplier Info Box -->
                    <div id="suppInfoBox" style="margin-top: 6px;">
                        <div id="suppEmptyPlaceholder" style="padding: 6px; text-align: center; color: #94a3b8; font-size: 0.74rem;">
                            Select a supplier to load profile & returnable products
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

            <!-- Return Schedule & Reference Details Card -->
            <div class="po-card">
                <div class="po-card-strip">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <span>RETURN SCHEDULE & REFERENCE</span>
                    </div>
                    <span style="font-size: 10px; font-weight: 700; color: #94a3b8;">STAGE 15 PURCHASE RETURN</span>
                </div>
                <div class="po-card-body" style="padding: 10px 14px;">
                    <div style="display: grid; grid-template-columns: 1fr 1.1fr 2fr; gap: 12px; align-items: center;">
                        <div>
                            <label class="po-field-label">RETURN NUMBER</label>
                            <div style="font-size: 0.84rem; font-weight: 800; color: #2563eb; background: #eff6ff; padding: 6px 10px; border-radius: 8px; border: 1px solid #bfdbfe; font-family: monospace; display: flex; align-items: center; gap: 5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                <span><?= View::escape($nextReturnNumber ?? 'SRET-0001') ?></span>
                            </div>
                        </div>

                        <div>
                            <label class="po-field-label">RETURN DATE *</label>
                            <input type="date" name="return_date" id="returnDateInput" class="po-input" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div>
                            <label class="po-field-label">NOTES / REFERENCE</label>
                            <input type="text" name="notes" id="returnNotesInput" class="po-input" placeholder="e.g. Defective batch returned to vendor">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Catalog Product Search Bar (Filter ONLY products purchased from selected supplier) -->
        <div class="po-search-wrap">
            <svg class="po-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="catalogSearchInput" class="po-search-input" placeholder="Select a supplier first to search purchased returnable items... (Press '/' or 'F2' to focus, ↑ / ↓ to navigate, Enter to add)" disabled autocomplete="off">
            <!-- Autocomplete Dropdown Menu -->
            <div id="catalogSearchDropdown" class="po-ac-menu"></div>
        </div>

        <!-- Cart Table Panel (Only Table Rows Scroll) -->
        <div class="po-cart-panel">
            <div class="po-cart-scroll">
                <table class="po-cart-table" id="returnCartTable">
                    <thead>
                        <tr>
                            <th style="width: 36px; text-align: center;">#</th>
                            <th style="text-align: left;">PRODUCT & LOT DETAILS</th>
                            <th style="width: 120px; text-align: center;">PURCHASED QTY</th>
                            <th style="width: 130px; text-align: center;">MAX RETURNABLE</th>
                            <th style="width: 130px; text-align: right;">UNIT COST (LKR)</th>
                            <th style="width: 120px; text-align: center;">RETURN QTY *</th>
                            <th style="width: 140px; text-align: right;">LINE TOTAL (LKR)</th>
                            <th style="width: 160px; text-align: left;">REASON</th>
                            <th style="width: 38px; text-align: center;">ACTION</th>
                        </tr>
                    </thead>
                    <tbody id="returnCartTableBody">
                        <!-- Empty State Row -->
                        <tr id="emptyCartRow">
                            <td colspan="9" style="text-align: center; padding: 45px 12px; color: #94a3b8;">
                                <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.8"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                                    <div style="font-size: 0.88rem; font-weight: 700; color: #64748b;">No products added to this return yet.</div>
                                    <div style="font-size: 0.75rem; color: #94a3b8;">Select a supplier and search purchased products above (press '/' or 'F2' to focus).</div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Bottom Panel: Terms & Totals Summary -->
        <div class="po-bottom-panel">
            <div style="display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <label class="po-field-label">RETURN NOTES & INVENTORY DISPOSITION</label>
                    <textarea name="bottom_notes" id="bottomNotesTextarea" class="po-notes-textarea" placeholder="Detailed return notes, reason for return, carrier tracking, or disposal authorization..."></textarea>
                </div>
                <div style="font-size: 0.73rem; color: #64748b; margin-top: 4px;">
                    💡 <strong>Tip:</strong> Press <kbd style="background: #f1f5f9; padding: 1px 4px; border-radius: 4px; font-weight: 700;">Enter</kbd> to advance between fields. Press <kbd style="background: #f1f5f9; padding: 1px 4px; border-radius: 4px; font-weight: 700;">Ctrl+S</kbd> anytime to process.
                </div>
            </div>

            <!-- Financial Breakdown Box -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 12px; display: flex; flex-direction: column; justify-content: space-between;">
                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem; color: #64748b;">
                    <span>Items: <strong id="cartItemCount" style="color: #0f172a;">0</strong> (<span id="cartTotalUnits">0</span> units)</span>
                    <span style="font-weight: 600;">Outstanding Due:</span>
                    <span id="footerOutstandingVal" style="font-family: monospace; font-weight: 700; color: #475569;">LKR 0.00</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem; color: #64748b; margin-top: 2px;">
                    <span>Est. AP Offset: <strong id="estApOffsetVal" style="color: #16a34a; font-family: monospace;">LKR 0.00</strong></span>
                    <span>Est. Supplier Credit: <strong id="estCreditVal" style="color: #2563eb; font-family: monospace;">LKR 0.00</strong></span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 4px; border-top: 1px dashed #cbd5e1; margin-top: 2px;">
                    <span style="font-size: 0.82rem; font-weight: 800; color: #0f172a;">Total Return Value:</span>
                    <span id="grandTotalVal" style="font-size: 1.05rem; font-weight: 900; color: #0f172a; font-family: monospace;">LKR 0.00</span>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="assets/js/curtiss_select.js"></script>
<script>
// Master Data
const suppliersList = <?= json_encode($suppliers) ?>;
const suppliersMap = {};
suppliersList.forEach(s => { suppliersMap[s.id] = s; });

let selectedSupplierPurchased = [];
let cartItems = []; // [{ key, product_id, product_name, barcode, lot_id, lot_number, total_purchased, max_returnable, unit_cost, quantity, reason }]

// Supplier Selection Handler
async function onSupplierChange(suppId) {
    const s = suppliersMap[suppId];
    const emptyBox = document.getElementById('suppEmptyPlaceholder');
    const detailsBox = document.getElementById('suppDetailsContent');
    const linkedBadge = document.getElementById('suppLinkedBadge');
    const searchInput = document.getElementById('catalogSearchInput');
    const searchMenu = document.getElementById('catalogSearchDropdown');

    searchMenu.classList.remove('show');
    searchMenu.innerHTML = '';
    searchInput.value = '';
    cartItems = [];
    renderCartTable();

    if (!s) {
        emptyBox.style.display = 'block';
        detailsBox.style.display = 'none';
        linkedBadge.innerText = '0 LINKED PRODUCTS';
        searchInput.disabled = true;
        searchInput.placeholder = 'Select a supplier first to search purchased returnable items...';
        document.getElementById('footerOutstandingVal').innerText = 'LKR 0.00';
        return;
    }

    emptyBox.style.display = 'none';
    detailsBox.style.display = 'block';
    linkedBadge.innerText = `${s.linked_products || 0} LINKED PRODUCTS`;

    document.getElementById('suppNameTitle').innerText = s.name;
    document.getElementById('suppCodeBadge').innerText = s.supplier_code ? s.supplier_code : `ID #${s.id}`;
    
    let contactInfo = [];
    if (s.phone) contactInfo.push(s.phone);
    if (s.whatsapp) contactInfo.push('WA: ' + s.whatsapp);
    document.getElementById('suppContactLine').innerText = contactInfo.length ? contactInfo.join(' • ') : 'No phone recorded';
    document.getElementById('suppAddressLine').innerText = s.address ? s.address : 'No physical address recorded';

    // Outstanding Payables Alert
    const outAlert = document.getElementById('suppOutstandingAlert');
    const outAmt = parseFloat(s.outstanding_payable || 0);
    if (outAmt > 0) {
        outAlert.style.display = 'flex';
        document.getElementById('suppOutstandingVal').innerText = 'LKR ' + outAmt.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('footerOutstandingVal').innerText = 'LKR ' + outAmt.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    } else {
        outAlert.style.display = 'none';
        document.getElementById('footerOutstandingVal').innerText = 'LKR 0.00';
    }

    // Enable Catalog Search Bar
    searchInput.disabled = false;
    searchInput.placeholder = `Search products purchased from ${s.name} (Press '/' or 'F2' to focus, ↑ / ↓ to navigate, Enter to add)...`;

    // Fetch purchased products from API
    try {
        const res = await fetch(`supplier_return_api.php?action=get_supplier_products&supplier_id=${suppId}`);
        const data = await res.json();
        if (data.success) {
            selectedSupplierPurchased = data.products || [];
            linkedBadge.innerText = `${selectedSupplierPurchased.length} RETURNABLE LOT ITEMS`;
        } else {
            selectedSupplierPurchased = [];
        }
    } catch (e) {
        console.error(e);
        selectedSupplierPurchased = [];
    }

    // Auto-advance focus to Return Date
    setTimeout(() => {
        const rDate = document.getElementById('returnDateInput');
        if (rDate) {
            rDate.focus();
        }
    }, 60);
}

// Keyboard Navigation for Upper Form Fields
document.getElementById('returnDateInput').addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
        e.preventDefault();
        const notes = document.getElementById('returnNotesInput');
        if (notes) {
            notes.focus();
            notes.select();
        }
    }
});

document.getElementById('returnNotesInput').addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
        e.preventDefault();
        const search = document.getElementById('catalogSearchInput');
        if (search && !search.disabled) {
            search.focus();
            search.select();
        }
    }
});

// Catalog Product Search & Autocomplete
const searchInput = document.getElementById('catalogSearchInput');
const searchMenu = document.getElementById('catalogSearchDropdown');
let filteredPurchased = [];
let highlightedIdx = -1;

searchInput.addEventListener('input', () => {
    const q = searchInput.value.toLowerCase().trim();
    if (!q) {
        searchMenu.classList.remove('show');
        searchMenu.innerHTML = '';
        filteredPurchased = [];
        highlightedIdx = -1;
        return;
    }

    filteredPurchased = selectedSupplierPurchased.filter(p => {
        return (p.product_name && p.product_name.toLowerCase().includes(q)) ||
               (p.barcode && p.barcode.toLowerCase().includes(q)) ||
               (p.lot_number && p.lot_number.toLowerCase().includes(q));
    }).slice(0, 15);

    renderSearchDropdown();
});

searchInput.addEventListener('keydown', (e) => {
    if (!searchMenu.classList.contains('show') || !filteredPurchased.length) return;

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        highlightedIdx = (highlightedIdx + 1) % filteredPurchased.length;
        renderSearchDropdown();
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        highlightedIdx = (highlightedIdx - 1 + filteredPurchased.length) % filteredPurchased.length;
        renderSearchDropdown();
    } else if (e.key === 'Enter') {
        e.preventDefault();
        if (highlightedIdx >= 0 && highlightedIdx < filteredPurchased.length) {
            addProductToReturn(filteredPurchased[highlightedIdx]);
        } else if (filteredPurchased.length > 0) {
            addProductToReturn(filteredPurchased[0]);
        }
    } else if (e.key === 'Escape') {
        searchMenu.classList.remove('show');
    }
});

function renderSearchDropdown() {
    if (!filteredPurchased.length) {
        searchMenu.innerHTML = '<div style="padding: 10px 14px; color: #94a3b8; font-size: 0.78rem;">No purchased returnable products found matching query</div>';
        searchMenu.classList.add('show');
        return;
    }

    let html = '';
    filteredPurchased.forEach((p, idx) => {
        const isAct = idx === highlightedIdx ? 'active' : '';
        const cost = parseFloat(p.unit_cost || 0).toFixed(2);
        html += `
            <div class="po-ac-item ${isAct}" onclick="selectProductByIndex(${idx})">
                <div>
                    <div style="font-weight: 700; font-size: 0.82rem;">${p.product_name}</div>
                    <div class="po-ac-meta" style="font-size: 0.70rem; color: #64748b;">SKU: ${p.barcode || '—'} • Lot: ${p.lot_number || 'N/A'}</div>
                </div>
                <div style="text-align: right;">
                    <div class="po-ac-cost" style="font-family: monospace; font-weight: 800; font-size: 0.8rem; color: #0284c7;">Cost: LKR ${cost}</div>
                    <div class="po-ac-stock" style="font-size: 0.72rem; color: #166534; font-weight: 700;">Purchased: ${p.total_purchased_quantity} | Max Return: ${p.max_returnable}</div>
                </div>
            </div>
        `;
    });

    searchMenu.innerHTML = html;
    searchMenu.classList.add('show');
}

function selectProductByIndex(idx) {
    if (filteredPurchased[idx]) {
        addProductToReturn(filteredPurchased[idx]);
    }
}

// Add Product to Return Cart Table
function addProductToReturn(p) {
    searchMenu.classList.remove('show');
    searchInput.value = '';
    highlightedIdx = -1;

    const key = `${p.product_id}_${p.lot_id || 0}_${p.unit_cost}`;
    const existing = cartItems.find(it => it.key === key);

    if (existing) {
        if (existing.quantity < existing.max_returnable) {
            existing.quantity += 1;
        }
        renderCartTable();
        focusRowQty(key);
        return;
    }

    cartItems.push({
        key: key,
        product_id: p.product_id,
        product_name: p.product_name,
        barcode: p.barcode || '-',
        lot_id: p.lot_id,
        lot_number: p.lot_number || 'N/A',
        total_purchased: p.total_purchased_quantity,
        max_returnable: p.max_returnable,
        unit_cost: parseFloat(p.unit_cost || 0),
        quantity: 1,
        reason: 'Defective / Supplier Return'
    });

    renderCartTable();
    focusRowQty(key);
}

// Focus and highlight QTY input field so typing immediately enters new value
function focusRowQty(key) {
    setTimeout(() => {
        const input = document.getElementById(`qty_input_${key}`);
        if (input) {
            input.focus();
            input.select();
        }
    }, 40);
}

// QTY Enter advances to Reason input (highlighted)
function handleQtyKeydown(e, key) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const reasonInput = document.getElementById(`reason_input_${key}`);
        if (reasonInput) {
            reasonInput.focus();
            reasonInput.select();
        }
    }
}

// Reason Enter loops back to Catalog Search Bar (ready for next product)
function handleReasonKeydown(e, key) {
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

function updateCartQty(key, val) {
    const item = cartItems.find(it => it.key === key);
    if (!item) return;

    let qty = parseFloat(val) || 0;
    if (qty > item.max_returnable) {
        alert(`Return quantity cannot exceed max returnable limit (${item.max_returnable}).`);
        qty = item.max_returnable;
    }
    item.quantity = qty;

    const lineTotalEl = document.getElementById(`line_total_${key}`);
    if (lineTotalEl) {
        lineTotalEl.innerText = (item.quantity * item.unit_cost).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    recalcCartTotals();
}

function updateCartReason(key, val) {
    const item = cartItems.find(it => it.key === key);
    if (item) {
        item.reason = val;
    }
}

function removeCartItem(key) {
    cartItems = cartItems.filter(it => it.key !== key);
    renderCartTable();
}

// Render Cart Table Rows
function renderCartTable() {
    const tbody = document.getElementById('returnCartTableBody');
    if (!cartItems.length) {
        tbody.innerHTML = `
            <tr id="emptyCartRow">
                <td colspan="9" style="text-align: center; padding: 45px 12px; color: #94a3b8;">
                    <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.8"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                        <div style="font-size: 0.88rem; font-weight: 700; color: #64748b;">No products added to this return yet.</div>
                        <div style="font-size: 0.75rem; color: #94a3b8;">Select a supplier and search purchased products above (press '/' or 'F2' to focus).</div>
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
            <tr id="cartRow_${it.key}">
                <td style="text-align: center; color: #64748b; font-size: 0.76rem; font-weight: 700;">${idx + 1}</td>
                <td>
                    <div style="font-weight: 700; color: #0f172a; font-size: 0.83rem;">${it.product_name}</div>
                    <div style="font-size: 0.70rem; color: #64748b; font-family: monospace;">SKU: ${it.barcode} • Lot: ${it.lot_number}</div>
                    <input type="hidden" name="items[${idx}][product_id]" value="${it.product_id}">
                    <input type="hidden" name="items[${idx}][lot_id]" value="${it.lot_id || ''}">
                    <input type="hidden" name="items[${idx}][unit_cost]" value="${it.unit_cost}">
                </td>
                <td style="text-align: center; font-weight: 600; color: #475569;">${it.total_purchased}</td>
                <td style="text-align: center; font-weight: 800; color: #166534;">${it.max_returnable}</td>
                <td style="text-align: right; font-family: monospace; font-weight: 700; color: #0f172a;">${it.unit_cost.toFixed(2)}</td>
                <td style="text-align: center;">
                    <input type="number" step="0.01" min="0.01" max="${it.max_returnable}"
                           name="items[${idx}][quantity]"
                           id="qty_input_${it.key}"
                           value="${it.quantity}"
                           class="po-input"
                           style="height: 30px; text-align: center; font-family: monospace; font-weight: 800; width: 85px;"
                           oninput="updateCartQty('${it.key}', this.value)"
                           onkeydown="handleQtyKeydown(event, '${it.key}')"
                           required>
                </td>
                <td style="text-align: right; font-family: monospace; font-weight: 800; color: #0f172a;" id="line_total_${it.key}">
                    ${lineTotal}
                </td>
                <td>
                    <input type="text"
                           name="items[${idx}][reason]"
                           id="reason_input_${it.key}"
                           value="${it.reason}"
                           class="po-input"
                           style="height: 30px; font-size: 0.78rem;"
                           oninput="updateCartReason('${it.key}', this.value)"
                           onkeydown="handleReasonKeydown(event, '${it.key}')">
                </td>
                <td style="text-align: center;">
                    <button type="button" class="del-row-btn" onclick="removeCartItem('${it.key}')" title="Remove Item">✕</button>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = rowsHtml;
    recalcCartTotals();
}

// Recalculate Totals & Breakdown
function recalcCartTotals() {
    let grandTotal = 0;
    let totalUnits = 0;

    cartItems.forEach(it => {
        grandTotal += (it.quantity * it.unit_cost);
        totalUnits += it.quantity;
    });

    document.getElementById('cartItemCount').innerText = cartItems.length;
    document.getElementById('cartTotalUnits').innerText = totalUnits.toFixed(2);
    document.getElementById('grandTotalVal').innerText = 'LKR ' + grandTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

    const suppId = document.getElementById('supplierSelect').value;
    const s = suppliersMap[suppId];
    const outAmt = s ? parseFloat(s.outstanding_payable || 0) : 0;

    const estApOffset = Math.min(grandTotal, outAmt);
    const estCredit = Math.max(0, grandTotal - estApOffset);

    document.getElementById('estApOffsetVal').innerText = 'LKR ' + estApOffset.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('estCreditVal').innerText = 'LKR ' + estCredit.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

// Global Keyboard Shortcuts
window.addEventListener('keydown', (e) => {
    // '/' or 'F2' -> Focus catalog search
    if ((e.key === '/' || e.key === 'F2') && !['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName)) {
        e.preventDefault();
        const search = document.getElementById('catalogSearchInput');
        if (search && !search.disabled) {
            search.focus();
            search.select();
        }
    }

    // Ctrl + S -> Save Return
    if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) {
        e.preventDefault();
        submitReturnForm();
    }
});

// Submit Form Handler with Double-Submit Protection
let isSubmitting = false;

function submitReturnForm() {
    if (isSubmitting) return;

    const suppId = document.getElementById('supplierSelect').value;
    if (!suppId) {
        alert('Please select a supplier.');
        return;
    }

    if (!cartItems.length) {
        alert('Please add at least one product item to process this return.');
        const search = document.getElementById('catalogSearchInput');
        if (search && !search.disabled) search.focus();
        return;
    }

    for (let it of cartItems) {
        if (!it.quantity || it.quantity <= 0) {
            alert(`Please specify a valid return quantity for ${it.product_name}.`);
            focusRowQty(it.key);
            return;
        }
        if (it.quantity > it.max_returnable) {
            alert(`Return quantity for ${it.product_name} exceeds max returnable limit (${it.max_returnable}).`);
            focusRowQty(it.key);
            return;
        }
    }

    // Sync bottom notes with form
    const bottomNotes = document.getElementById('bottomNotesTextarea').value.trim();
    if (bottomNotes) {
        const topNotes = document.getElementById('returnNotesInput');
        if (topNotes.value.trim() && topNotes.value !== bottomNotes) {
            topNotes.value += ' | ' + bottomNotes;
        } else {
            topNotes.value = bottomNotes;
        }
    }

    const form = document.getElementById('supplierReturnForm');
    const saveBtn = document.getElementById('returnSaveBtn');
    isSubmitting = true;
    if (saveBtn) {
        saveBtn.disabled = true;
        saveBtn.innerHTML = `
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="po-spinner"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
            Processing...
        `;
    }

    form.submit();
}

function handleFormSubmit(form) {
    if (isSubmitting) return false;
    isSubmitting = true;
    const saveBtn = document.getElementById('returnSaveBtn');
    if (saveBtn) {
        saveBtn.disabled = true;
        saveBtn.innerHTML = `
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="po-spinner"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
            Processing...
        `;
    }
}

// Initialize Searchable Comboboxes on DOM Ready
document.addEventListener('DOMContentLoaded', () => {
    const suppSelect = document.getElementById('supplierSelect');
    let suppInstance = null;

    if (suppSelect && window.CurtissSearchableSelect) {
        suppInstance = CurtissSearchableSelect.attach(suppSelect);
    }

    // Auto-open supplier combobox and focus its search input immediately on page load
    setTimeout(() => {
        if (suppInstance) {
            suppInstance.toggleMenu();
            if (suppInstance.searchInput) {
                suppInstance.searchInput.focus();
            }
        }
    }, 80);

    // Trigger onSupplierChange if supplier is already selected
    if (suppSelect && suppSelect.value) {
        onSupplierChange(suppSelect.value);
    }
});
</script>
