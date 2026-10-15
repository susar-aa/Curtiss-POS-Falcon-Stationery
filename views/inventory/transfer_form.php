<?php
/**
 * Curtiss POS - Branch Stock Transfer Creation & Editing Panel
 * Fixed Viewport Layout with Internally Scrollable Cart Table & Full Keyboard Navigation Loop
 */

$isEdit = !empty($transferData);
$transferNo = $isEdit ? ($transferData['transfer_number'] ?? '') : ($nextTransferNumber ?? '');
?>
<link rel="stylesheet" href="assets/css/curtiss_select.css?v=<?= file_exists(__DIR__ . '/../../assets/css/curtiss_select.css') ? filemtime(__DIR__ . '/../../assets/css/curtiss_select.css') : time() ?>">

<!-- Floating Top-Right Action Header -->
<div id="curtiss-floating-header-right" style="position: fixed; top: 16px; right: 20px; z-index: 1050; display: flex; align-items: center; gap: 8px;">
    <a href="stock_transfers" class="po-top-btn po-btn-ghost">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Cancel
    </a>
    <button type="button" class="po-top-btn po-btn-primary" onclick="submitTransferForm()" id="trfSaveBtn">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
        <span id="saveBtnText"><?= $isEdit ? 'Update Transfer' : 'Send Transfer (In-Transit)' ?></span>
        <kbd class="po-kbd">Ctrl+S</kbd>
    </button>
</div>

<style>
/* ── Reset & Fixed Viewport Layout ── */
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
    transition: all 0.12s ease;
}
.po-ac-item:last-child { border-bottom: none; }
.po-ac-item:hover {
    background: #f1f5f9;
}
.po-ac-item.active {
    background: #0f172a !important;
}
.po-ac-title {
    font-weight: 700;
    font-size: 0.85rem;
    color: #0f172a;
    transition: color 0.12s ease;
}
.po-ac-item:hover .po-ac-title {
    color: #0284c7;
}
.po-ac-item.active .po-ac-title {
    color: #ffffff !important;
}
.po-ac-item.active .po-ac-sub {
    color: #94a3b8 !important;
}
.po-ac-item.active .po-ac-stock {
    color: #38bdf8 !important;
}
.po-ac-item.active .po-ac-avail {
    color: #cbd5e1 !important;
}

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
    font-size: 0.72rem;
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
.po-cart-table tbody tr:nth-child(odd) {
    background: #ffffff;
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
    grid-template-columns: 1fr 380px;
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
                <?= $isEdit ? 'Edit Branch Stock Transfer' : 'Branch Stock Transfer' ?>
            </h1>
            <span style="font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; background: #dcfce7; color: #15803d; padding: 3px 8px; border-radius: 999px;">
                STOCK DISPATCH PANEL
            </span>
            <?php if ($isEdit): ?>
                <span style="font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; background: #fef3c7; color: #92400e; padding: 3px 8px; border-radius: 999px;">
                    DRAFT: <?= View::escape($transferData['transfer_number']) ?>
                </span>
            <?php endif; ?>
        </div>
        <div style="font-size: 0.75rem; color: #64748b; font-weight: 600;">
            Source Branch: <strong style="color: #0f172a;"><?= View::escape($currentBranch['name']) ?> (<?= View::escape($currentBranch['code']) ?>)</strong>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (!empty($errorMsg)): ?>
        <div style="flex-shrink: 0; padding: 8px 12px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; color: #991b1b; font-size: 0.8rem; font-weight: 600; margin-bottom: 6px;">
            ✕ <?= View::escape($errorMsg) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="stock_transfer_create<?= $isEdit ? '?id=' . (int)$transferData['id'] : '' ?>" id="transferForm" style="display: flex; flex-direction: column; flex: 1; min-height: 0;">
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int)$transferData['id'] ?>">
        <?php endif; ?>
        <input type="hidden" name="from_branch_id" value="<?= (int)$currentBranch['id'] ?>">

        <!-- Upper Grid: Destination Branch Card & Transfer Details Card -->
        <div style="display: grid; grid-template-columns: 36% 64%; gap: 12px; flex-shrink: 0;">
            
            <!-- Destination Branch Card -->
            <div class="po-card">
                <div class="po-card-strip">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        <span>DESTINATION BRANCH *</span>
                    </div>
                    <span id="destBranchCodeBadge" style="font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase;">SELECT BRANCH</span>
                </div>
                <div class="po-card-body" style="padding: 8px 10px;">
                    <div>
                        <?php 
                        $selectedDestId = $isEdit ? (int)$transferData['to_branch_id'] : 0;
                        ?>
                        <select name="to_branch_id" id="trfDestBranchSelect" class="curtiss-select-enhance" onchange="onDestBranchChange(this.value)" required>
                            <option value="">-- Select Destination Branch --</option>
                            <?php foreach ($branches as $b): ?>
                                <option value="<?= (int)$b['id'] ?>" <?= ($selectedDestId === (int)$b['id']) ? 'selected' : '' ?>>
                                    <?= View::escape($b['name']) ?> (<?= View::escape($b['code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Dynamic Destination Branch Details Box -->
                    <div id="destBranchInfoBox" style="margin-top: 6px;">
                        <div id="destEmptyPlaceholder" style="padding: 6px; text-align: center; color: #94a3b8; font-size: 0.74rem;">
                            Select a destination branch to load receiving branch details
                        </div>

                        <div id="destDetailsContent" style="display: none;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div>
                                    <div id="destBranchTitle" style="font-size: 0.86rem; font-weight: 800; color: #0f172a; line-height: 1.2;">—</div>
                                    <div id="destAddressLine" style="font-size: 0.72rem; color: #64748b; margin-top: 2px;">—</div>
                                    <div id="destPhoneLine" style="font-size: 0.70rem; color: #94a3b8; margin-top: 1px;">—</div>
                                </div>
                                <span id="destCodePill" style="font-size: 10px; font-weight: 800; background: #f1f5f9; color: #0f172a; padding: 2px 6px; border-radius: 4px; font-family: monospace;">BR-—</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transfer & Dispatch Details Card -->
            <div class="po-card">
                <div class="po-card-strip">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                        <span>TRANSFER & DISPATCH DETAILS</span>
                    </div>
                    <span style="font-size: 10px; font-weight: 700; color: #94a3b8;">INVENTORY DISPATCH</span>
                </div>
                <div class="po-card-body" style="padding: 10px 14px;">
                    <div style="display: grid; grid-template-columns: 1.2fr 1fr 1.2fr 1.2fr; gap: 10px; align-items: center;">
                        <div>
                            <label class="po-field-label">TRANSFER NUMBER</label>
                            <div style="font-size: 0.84rem; font-weight: 800; color: #0284c7; background: #f0f9ff; padding: 6px 8px; border-radius: 8px; border: 1px solid #bae6fd; font-family: monospace; display: flex; align-items: center; gap: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                <span><?= View::escape($transferNo) ?></span>
                            </div>
                        </div>

                        <div>
                            <label class="po-field-label">TRANSFER DATE *</label>
                            <input type="date" name="transfer_date" id="transferDateInput" class="po-input" value="<?= View::escape($isEdit ? ($transferData['transfer_date'] ?? date('Y-m-d')) : date('Y-m-d')) ?>" required>
                        </div>

                        <div>
                            <label class="po-field-label">DISPATCH ACTION *</label>
                            <select name="send_now" id="sendNowSelect" class="po-select" onchange="onDispatchActionChange(this.value)">
                                <option value="1" selected>Send Immediately (In-Transit)</option>
                                <option value="0">Save as Draft (Prepare Only)</option>
                            </select>
                        </div>

                        <div>
                            <label class="po-field-label">REFERENCE / VEHICLE NO</label>
                            <input type="text" name="reference_no" id="referenceNoInput" class="po-input" placeholder="e.g. WP-CA-8899 / Courier" value="">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Catalog Product Search Bar -->
        <div class="po-search-wrap">
            <svg class="po-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="catalogSearchInput" class="po-search-input" placeholder="Search catalog by product name, SKU, item code, or barcode... (Press '/' or 'F2' to focus, ↑ / ↓ to navigate, Enter to add)" autocomplete="off">
            <div id="catalogSearchDropdown" class="po-ac-menu"></div>
        </div>

        <!-- Transfer Items Cart Table Panel -->
        <div class="po-cart-panel">
            <div class="po-cart-scroll">
                <table class="po-cart-table" id="trfCartTable">
                    <thead>
                        <tr>
                            <th style="width: 36px; text-align: center;">#</th>
                            <th style="width: 25%; text-align: left;">PRODUCT / VARIATION</th>
                            <th style="width: 24%; text-align: left;">SOURCE LOT / BATCH</th>
                            <th style="width: 10%; text-align: center;">AVAILABLE</th>
                            <th style="width: 11%; text-align: center;">TRANSFER QTY</th>
                            <th style="width: 12%; text-align: right;">UNIT COST (LKR)</th>
                            <th style="width: 12%; text-align: right;">SELLING (LKR)</th>
                            <th style="width: 14%; text-align: right;">TOTAL COST (LKR)</th>
                            <th style="width: 38px; text-align: center;">ACTION</th>
                        </tr>
                    </thead>
                    <tbody id="trfCartTableBody">
                        <tr id="emptyCartRow">
                            <td colspan="9" style="text-align: center; padding: 45px 12px; color: #94a3b8;">
                                <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.8"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                                    <div style="font-size: 0.88rem; font-weight: 700; color: #64748b;">No items added to this Transfer yet.</div>
                                    <div style="font-size: 0.75rem; color: #94a3b8;">Search catalog products above (press '/' or 'F2' to focus) and press Enter to add.</div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Bottom Panel: Remarks & Financial Breakdown -->
        <div class="po-bottom-panel">
            <div>
                <label class="po-field-label">TRANSFER REMARKS & DISPATCH NOTES</label>
                <textarea name="notes" id="trfNotesTextarea" class="po-notes-textarea" placeholder="Special handling notes, driver / vehicle information, dispatch instructions..."><?= View::escape($isEdit ? ($transferData['notes'] ?? '') : '') ?></textarea>
            </div>

            <!-- Financial Summary Box -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 12px; display: flex; flex-direction: column; justify-content: space-between;">
                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem; color: #64748b;">
                    <span>Items: <strong id="cartItemCount" style="color: #0f172a;">0</strong> (<span id="cartTotalUnits">0</span> units)</span>
                    <span style="font-weight: 600;">Transfer Cost Valuation:</span>
                </div>
                <div style="display: flex; justify-content: flex-end; align-items: center; margin-top: 2px;">
                    <span id="transferTotalCostVal" style="font-family: monospace; font-size: 1.05rem; font-weight: 800; color: #0f172a;">LKR 0.00</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 4px; border-top: 1px dashed #cbd5e1; margin-top: 2px; font-size: 0.75rem; color: #64748b;">
                    <span style="font-weight: 700; color: #475569;">Retail Selling Valuation:</span>
                    <span id="transferTotalRetailVal" style="font-size: 0.85rem; font-weight: 800; color: #166534; font-family: monospace;">LKR 0.00</span>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="assets/js/curtiss_select.js?v=<?= file_exists(__DIR__ . '/../../assets/js/curtiss_select.js') ? filemtime(__DIR__ . '/../../assets/js/curtiss_select.js') : time() ?>"></script>
<script>
const catalogProducts = <?= json_encode($catalogProducts, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const branchesList = <?= json_encode($branches, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const existingItemsData = <?= json_encode($existingItems, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

let cartItems = [];
let acSelectedIndex = -1;

function esc(str) {
    if (!str) return '';
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

function fmt(num) {
    return (parseFloat(num) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

document.addEventListener('DOMContentLoaded', () => {
    // Attach searchable comboboxes
    document.querySelectorAll('.curtiss-select-enhance').forEach(el => {
        if (window.CurtissSearchableSelect) {
            CurtissSearchableSelect.attach(el);
        }
    });

    // Populate destination branch info if pre-selected
    const destSelect = document.getElementById('trfDestBranchSelect');
    if (destSelect && destSelect.value) {
        onDestBranchChange(destSelect.value);
    }

    // Initialize Catalog Search input events
    const searchInput = document.getElementById('catalogSearchInput');
    const dropdown = document.getElementById('catalogSearchDropdown');

    searchInput.addEventListener('input', (e) => {
        handleCatalogSearch(e.target.value);
    });

    searchInput.addEventListener('keydown', (e) => {
        const items = dropdown.querySelectorAll('.po-ac-item');
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (items.length === 0) return;
            acSelectedIndex = (acSelectedIndex + 1) % items.length;
            updateAcHighlight(items);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (items.length === 0) return;
            acSelectedIndex = (acSelectedIndex - 1 + items.length) % items.length;
            updateAcHighlight(items);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (acSelectedIndex >= 0 && items[acSelectedIndex]) {
                items[acSelectedIndex].click();
            } else if (items.length > 0) {
                items[0].click();
            }
        } else if (e.key === 'Escape') {
            dropdown.classList.remove('show');
            acSelectedIndex = -1;
        }
    });

    document.addEventListener('click', (e) => {
        if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.remove('show');
        }
    });

    // Global Keyboard Shortcuts (Ctrl+S to save, / or F2 to focus search)
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) {
            e.preventDefault();
            submitTransferForm();
        } else if (e.key === '/' || e.key === 'F2') {
            if (document.activeElement !== searchInput && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
                e.preventDefault();
                searchInput.focus();
                searchInput.select();
            }
        }
    });

    // Load existing items if editing draft transfer
    if (existingItemsData && existingItemsData.length > 0) {
        existingItemsData.forEach(item => {
            const product = catalogProducts.find(p => p.product_id === parseInt(item.product_id));
            if (product) {
                const targetLotId = parseInt(item.source_lot_id);
                const lot = product.lots.find(l => l.lot_id === targetLotId);
                addCartRow(product, lot || product.lots[0], parseFloat(item.quantity), false);
            }
        });
    }

    // Set initial focus
    setTimeout(() => {
        if (!destSelect.value) {
            if (destSelect._curtissSelect) {
                destSelect._curtissSelect.toggleMenu();
                destSelect._curtissSelect.searchInput.focus();
            }
        } else {
            searchInput.focus();
        }
    }, 100);
});

function onDestBranchChange(branchId) {
    const bId = parseInt(branchId || 0);
    const branch = branchesList.find(b => parseInt(b.id) === bId);

    const emptyBox = document.getElementById('destEmptyPlaceholder');
    const contentBox = document.getElementById('destDetailsContent');
    const codeBadge = document.getElementById('destBranchCodeBadge');

    if (!branch) {
        emptyBox.style.display = 'block';
        contentBox.style.display = 'none';
        codeBadge.textContent = 'SELECT BRANCH';
        return;
    }

    emptyBox.style.display = 'none';
    contentBox.style.display = 'block';
    codeBadge.textContent = branch.code || 'BR-DEST';

    document.getElementById('destBranchTitle').textContent = branch.name || '';
    document.getElementById('destCodePill').textContent = branch.code || 'BR-00';
    document.getElementById('destAddressLine').textContent = branch.address || 'Address: N/A';
    document.getElementById('destPhoneLine').textContent = 'Phone: ' + (branch.phone || 'N/A');

    // Auto-advance focus to catalog search
    const searchInput = document.getElementById('catalogSearchInput');
    if (searchInput) searchInput.focus();
}

function onDispatchActionChange(val) {
    const btnText = document.getElementById('saveBtnText');
    if (val === '1') {
        btnText.textContent = 'Send Transfer (In-Transit)';
    } else {
        btnText.textContent = 'Save Transfer (Draft)';
    }
}

function handleCatalogSearch(query) {
    const q = (query || '').toLowerCase().trim();
    const dropdown = document.getElementById('catalogSearchDropdown');
    acSelectedIndex = -1;

    if (!q) {
        dropdown.classList.remove('show');
        dropdown.innerHTML = '';
        return;
    }

    const matches = catalogProducts.filter(p => {
        const text = (p.product_name + ' ' + p.barcode + ' ' + p.sku + ' ' + p.category_name).toLowerCase();
        return text.includes(q);
    }).slice(0, 15);

    if (matches.length === 0) {
        dropdown.innerHTML = `
            <div style="padding: 12px; text-align: center; color: #94a3b8; font-size: 0.78rem;">
                No products with available stock at this branch match "<strong>${esc(q)}</strong>".
            </div>
        `;
        dropdown.classList.add('show');
        return;
    }

    let html = '';
    matches.forEach(p => {
        const lotsCount = p.lots ? p.lots.length : 0;
        const totalStock = parseFloat(p.total_stock || 0).toFixed(2);
        const sub = [p.barcode, p.sku, p.category_name].filter(Boolean).join(' • ');

        html += `
            <div class="po-ac-item" onclick="onCatalogProductSelect(${p.product_id})">
                <div>
                    <div class="po-ac-title">${esc(p.product_name)}</div>
                    <div class="po-ac-sub" style="font-size: 0.72rem; color: #64748b; margin-top: 1px;">${esc(sub)}</div>
                </div>
                <div style="text-align: right;">
                    <div class="po-ac-stock" style="font-weight: 800; font-size: 0.82rem; color: #166534; font-family: monospace;">${totalStock} units</div>
                    <div class="po-ac-avail" style="font-size: 0.68rem; color: #94a3b8;">${lotsCount} Lot${lotsCount === 1 ? '' : 's'} Available</div>
                </div>
            </div>
        `;
    });

    dropdown.innerHTML = html;
    dropdown.classList.add('show');
}

function updateAcHighlight(items) {
    items.forEach((item, idx) => {
        if (idx === acSelectedIndex) {
            item.classList.add('active');
            item.scrollIntoView({ block: 'nearest' });
        } else {
            item.classList.remove('active');
        }
    });
}

function onCatalogProductSelect(productId) {
    const product = catalogProducts.find(p => p.product_id === productId);
    const dropdown = document.getElementById('catalogSearchDropdown');
    const searchInput = document.getElementById('catalogSearchInput');

    dropdown.classList.remove('show');
    searchInput.value = '';

    if (!product || !product.lots || product.lots.length === 0) {
        alert('No available lots found for this product.');
        return;
    }

    // Find lots that are NOT yet in the cart for this product
    const usedLotIds = cartItems
        .filter(i => i.product_id === productId)
        .map(i => parseInt(i.lot_id));

    const availableLots = product.lots.filter(l => !usedLotIds.includes(parseInt(l.lot_id)));

    if (availableLots.length === 0) {
        alert(`All available lots (${product.lots.length}) for "${product.product_name}" have already been added to the transfer.`);
        const lastItem = [...cartItems].reverse().find(i => i.product_id === productId);
        if (lastItem) {
            const inputEl = document.getElementById(`qty_${lastItem.row_id}`);
            if (inputEl) {
                inputEl.focus();
                inputEl.select();
            }
        }
        return;
    }

    // Pick the first available unused lot and open lot selection
    const lotToAdd = availableLots[0];
    addCartRow(product, lotToAdd, 1.00, true);
}

function generateLotOptionsHtml(cartItem) {
    let optionsHtml = '';
    const otherUsedLotIds = cartItems
        .filter(i => i.row_id !== cartItem.row_id && i.product_id === cartItem.product_id)
        .map(i => parseInt(i.lot_id));

    cartItem.lots.forEach(l => {
        const lotId = parseInt(l.lot_id);
        const expText = l.expiry_date ? `Exp: ${l.expiry_date}` : 'No Exp';
        const isSelected = (lotId === parseInt(cartItem.lot_id));
        const isUsedElsewhere = otherUsedLotIds.includes(lotId);

        if (isUsedElsewhere) {
            optionsHtml += `<option value="${lotId}" disabled>Lot #${esc(l.lot_number)} (${expText}) — [In another row]</option>`;
        } else {
            optionsHtml += `<option value="${lotId}" ${isSelected ? 'selected' : ''}>Lot #${esc(l.lot_number)} (${expText}) — Avail: ${parseFloat(l.remaining_quantity).toFixed(2)}</option>`;
        }
    });

    return optionsHtml;
}

function refreshAllLotDropdowns(productId) {
    cartItems.filter(i => i.product_id === productId).forEach(item => {
        const selectEl = document.getElementById(`lot_select_${item.row_id}`);
        if (!selectEl) return;
        selectEl.innerHTML = generateLotOptionsHtml(item);
        if (selectEl._curtissSelect) {
            selectEl._curtissSelect.refreshOptions();
        }
    });
}

function addCartRow(product, lot, initialQty = 1.00, autoOpenLot = false) {
    const tbody = document.getElementById('trfCartTableBody');
    const emptyRow = document.getElementById('emptyCartRow');
    if (emptyRow) emptyRow.remove();

    const rowId = 'trf_row_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
    const cartItem = {
        row_id: rowId,
        product_id: product.product_id,
        product_name: product.product_name,
        barcode: product.barcode,
        sku: product.sku,
        category: product.category_name,
        lot_id: lot.lot_id,
        lot_number: lot.lot_number,
        cost: parseFloat(lot.cost) || 0,
        selling_price: parseFloat(lot.selling_price) || 0,
        max_quantity: parseFloat(lot.remaining_quantity) || 0,
        quantity: Math.min(initialQty, parseFloat(lot.remaining_quantity) || 0),
        lots: product.lots
    };

    cartItems.push(cartItem);

    const sub = [cartItem.barcode, cartItem.sku].filter(Boolean).join(' • ');
    const lineTotal = cartItem.quantity * cartItem.cost;
    const lotOptions = generateLotOptionsHtml(cartItem);

    const tr = document.createElement('tr');
    tr.id = rowId;
    tr.className = 'trf-item-row';
    tr.innerHTML = `
        <td class="row-num-cell" style="text-align: center; font-weight: 700; color: #64748b; font-size: 0.75rem;"></td>
        <td>
            <div style="font-weight: 700; color: #0f172a; font-size: 0.84rem;">${esc(cartItem.product_name)}</div>
            <div style="font-size: 0.70rem; color: #64748b; display: flex; align-items: center; gap: 4px; margin-top: 2px;">
                <span style="font-family: monospace;">${esc(sub)}</span>
                <span style="background: #f1f5f9; padding: 1px 5px; border-radius: 4px; font-size: 0.65rem;">${esc(cartItem.category)}</span>
            </div>
            <input type="hidden" class="row-product-id" value="${cartItem.product_id}">
        </td>
        <td style="min-width: 220px;">
            <select id="lot_select_${rowId}" class="row-lot-id po-select" style="height: 32px; font-size: 0.76rem; font-weight: 600; border-radius: 8px;" onchange="onLotDropdownChange('${rowId}', this.value)">
                ${lotOptions}
            </select>
        </td>
        <td style="text-align: center;">
            <span id="avail_${rowId}" style="display: inline-block; padding: 2px 8px; border-radius: 999px; background: #e0f2fe; color: #0369a1; font-weight: 800; font-family: monospace; font-size: 0.78rem;">
                ${cartItem.max_quantity.toFixed(2)}
            </span>
        </td>
        <td style="text-align: center;">
            <input type="number" step="0.01" min="0.01" max="${cartItem.max_quantity}" 
                   class="po-input row-qty-input" 
                   id="qty_${rowId}"
                   data-row-id="${rowId}"
                   value="${cartItem.quantity.toFixed(2)}"
                   style="width: 85px; height: 32px; text-align: center; font-weight: 800; font-family: monospace; margin: 0 auto;"
                   oninput="onQuantityInput('${rowId}', this.value)"
                   onkeydown="onQuantityKeyDown(event, '${rowId}')"
                   required>
        </td>
        <td style="text-align: right; font-family: monospace; font-weight: 700; color: #0f172a;" id="cost_${rowId}">
            ${fmt(cartItem.cost)}
        </td>
        <td style="text-align: right; font-family: monospace; font-weight: 600; color: #475569;" id="price_${rowId}">
            ${fmt(cartItem.selling_price)}
        </td>
        <td style="text-align: right; font-family: monospace; font-weight: 800; color: #0284c7;" id="lineTotal_${rowId}">
            ${fmt(lineTotal)}
        </td>
        <td style="text-align: center;">
            <button type="button" class="del-row-btn" onclick="removeCartRow('${rowId}')" title="Remove Item">✕</button>
        </td>
    `;

    tbody.appendChild(tr);

    const lotSelectEl = document.getElementById(`lot_select_${rowId}`);
    if (window.CurtissSearchableSelect && lotSelectEl) {
        CurtissSearchableSelect.attach(lotSelectEl, {
            searchPlaceholder: 'Search lot, expiry, cost...'
        });
    }

    updateRowIndices();
    recalcTotals();
    refreshAllLotDropdowns(cartItem.product_id);

    if (autoOpenLot && lotSelectEl && lotSelectEl._curtissSelect) {
        setTimeout(() => {
            lotSelectEl._curtissSelect.toggleMenu();
        }, 50);
    }
}

function removeCartRow(rowId) {
    const item = cartItems.find(i => i.row_id === rowId);
    const prodId = item ? item.product_id : null;

    const selectEl = document.getElementById(`lot_select_${rowId}`);
    if (selectEl && selectEl._curtissSelect) {
        try {
            if (typeof selectEl._curtissSelect.destroy === 'function') {
                selectEl._curtissSelect.destroy();
            } else {
                if (selectEl._curtissSelect.menu && selectEl._curtissSelect.menu.remove) {
                    selectEl._curtissSelect.menu.remove();
                }
                if (selectEl._curtissSelect.wrapper && selectEl._curtissSelect.wrapper.remove) {
                    selectEl._curtissSelect.wrapper.remove();
                }
                delete selectEl._curtissSelect;
            }
        } catch (err) {
            console.warn('Error destroying select portal:', err);
        }
    }

    const tr = document.getElementById(rowId);
    if (tr) tr.remove();

    cartItems = cartItems.filter(i => i.row_id !== rowId);

    if (cartItems.length === 0) {
        const tbody = document.getElementById('trfCartTableBody');
        tbody.innerHTML = `
            <tr id="emptyCartRow">
                <td colspan="9" style="text-align: center; padding: 45px 12px; color: #94a3b8;">
                    <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.8"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                        <div style="font-size: 0.88rem; font-weight: 700; color: #64748b;">No items added to this Transfer yet.</div>
                        <div style="font-size: 0.75rem; color: #94a3b8;">Search catalog products above (press '/' or 'F2' to focus) and press Enter to add.</div>
                    </div>
                </td>
            </tr>
        `;
    }

    updateRowIndices();
    recalcTotals();
    if (prodId) refreshAllLotDropdowns(prodId);
}

function onLotDropdownChange(rowId, selectedLotId) {
    const lotId = parseInt(selectedLotId || 0);
    const item = cartItems.find(i => i.row_id === rowId);
    if (!item) return;

    const duplicate = cartItems.find(i => i.row_id !== rowId && i.product_id === item.product_id && parseInt(i.lot_id) === lotId);
    if (duplicate) {
        alert('This lot is already selected in another row for this product.');
        refreshAllLotDropdowns(item.product_id);
        return;
    }

    const newLot = item.lots.find(l => parseInt(l.lot_id) === lotId);
    if (!newLot) return;

    item.lot_id = newLot.lot_id;
    item.lot_number = newLot.lot_number;
    item.cost = parseFloat(newLot.cost) || 0;
    item.selling_price = parseFloat(newLot.selling_price) || 0;
    item.max_quantity = parseFloat(newLot.remaining_quantity) || 0;

    if (item.quantity > item.max_quantity) {
        item.quantity = item.max_quantity;
    }

    const availEl = document.getElementById(`avail_${rowId}`);
    if (availEl) availEl.textContent = item.max_quantity.toFixed(2);

    const qtyInput = document.getElementById(`qty_${rowId}`);
    if (qtyInput) {
        qtyInput.max = item.max_quantity;
        qtyInput.value = item.quantity.toFixed(2);
    }

    const costEl = document.getElementById(`cost_${rowId}`);
    if (costEl) costEl.textContent = fmt(item.cost);

    const priceEl = document.getElementById(`price_${rowId}`);
    if (priceEl) priceEl.textContent = fmt(item.selling_price);

    const lineTotalEl = document.getElementById(`lineTotal_${rowId}`);
    if (lineTotalEl) lineTotalEl.textContent = fmt(item.quantity * item.cost);

    recalcTotals();
    refreshAllLotDropdowns(item.product_id);

    setTimeout(() => {
        if (qtyInput) {
            qtyInput.focus();
            qtyInput.select();
        }
    }, 40);
}

function onQuantityInput(rowId, val) {
    const item = cartItems.find(i => i.row_id === rowId);
    if (!item) return;

    let qty = parseFloat(val);
    if (isNaN(qty) || qty <= 0) {
        qty = 0;
    }

    if (qty > item.max_quantity) {
        qty = item.max_quantity;
        const inputEl = document.getElementById(`qty_${rowId}`);
        if (inputEl) inputEl.value = qty.toFixed(2);
    }

    item.quantity = qty;
    
    const lineTotalEl = document.getElementById(`lineTotal_${rowId}`);
    if (lineTotalEl) {
        lineTotalEl.textContent = fmt(qty * item.cost);
    }

    recalcTotals();
}

function onQuantityKeyDown(e, rowId) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const searchInput = document.getElementById('catalogSearchInput');
        if (searchInput) {
            searchInput.focus();
            searchInput.select();
        }
    }
}

function updateRowIndices() {
    const rows = document.querySelectorAll('#trfCartTableBody tr.trf-item-row');
    rows.forEach((tr, index) => {
        const numCell = tr.querySelector('.row-num-cell');
        if (numCell) numCell.textContent = index + 1;

        const prodInput = tr.querySelector('.row-product-id');
        if (prodInput) prodInput.name = `items[${index}][product_id]`;

        const lotInput = tr.querySelector('.row-lot-id');
        if (lotInput) lotInput.name = `items[${index}][lot_id]`;

        const qtyInput = tr.querySelector('.row-qty-input');
        if (qtyInput) qtyInput.name = `items[${index}][quantity]`;
    });
}

function recalcTotals() {
    let totalItems = cartItems.length;
    let totalUnits = 0;
    let totalCost = 0;
    let totalRetail = 0;

    cartItems.forEach(item => {
        const qty = parseFloat(item.quantity) || 0;
        const cost = parseFloat(item.cost) || 0;
        const sell = parseFloat(item.selling_price) || 0;

        totalUnits += qty;
        totalCost += (qty * cost);
        totalRetail += (qty * sell);
    });

    document.getElementById('cartItemCount').textContent = totalItems;
    document.getElementById('cartTotalUnits').textContent = totalUnits.toFixed(2);
    document.getElementById('transferTotalCostVal').textContent = 'LKR ' + fmt(totalCost);
    document.getElementById('transferTotalRetailVal').textContent = 'LKR ' + fmt(totalRetail);
}

function submitTransferForm() {
    const destSelect = document.getElementById('trfDestBranchSelect');
    if (!destSelect.value) {
        alert('Please select a destination branch.');
        if (destSelect._curtissSelect) {
            destSelect._curtissSelect.toggleMenu();
            destSelect._curtissSelect.searchInput.focus();
        } else {
            destSelect.focus();
        }
        return;
    }

    if (cartItems.length === 0) {
        alert('Please add at least one product to the transfer cart.');
        document.getElementById('catalogSearchInput').focus();
        return;
    }

    for (let item of cartItems) {
        if (!item.quantity || item.quantity <= 0) {
            alert(`Please enter a valid transfer quantity for ${item.product_name}.`);
            const inputEl = document.getElementById(`qty_${item.row_id}`);
            if (inputEl) { inputEl.focus(); inputEl.select(); }
            return;
        }
        if (item.quantity > item.max_quantity) {
            alert(`Transfer quantity for ${item.product_name} exceeds available stock in Lot #${item.lot_number}. Max available: ${item.max_quantity}.`);
            const inputEl = document.getElementById(`qty_${item.row_id}`);
            if (inputEl) { inputEl.focus(); inputEl.select(); }
            return;
        }
    }

    updateRowIndices();

    const saveBtn = document.getElementById('trfSaveBtn');
    saveBtn.disabled = true;
    saveBtn.innerHTML = 'Saving Transfer...';

    document.getElementById('transferForm').submit();
}
</script>
