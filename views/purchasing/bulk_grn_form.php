<?php
/**
 * Curtiss POS - Bulk Goods Received Note (Bulk GRN) View
 * Complete Supplier-based Bulk GRN entry system with instant loading,
 * inline price lot selection, real-time search & filters, hide/restore rows,
 * on-the-fly new product creation, and accounting integration.
 */
?>
<link rel="stylesheet" href="assets/css/curtiss_select.css">

<!-- Floating Top-Right Action Header -->
<div id="curtiss-floating-header-right" style="position: fixed; top: 16px; right: 20px; z-index: 1050; display: flex; align-items: center; gap: 8px;">
    <a href="grns" class="po-top-btn po-btn-ghost">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Cancel
    </a>
    <button type="button" class="po-top-btn po-btn-ghost" onclick="focusInlineAddRow()" style="border-color: #3b82f6; color: #1d4ed8; background: #eff6ff;" id="btnAddNewProduct">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
        + Add New Product
    </button>
    <button type="button" class="po-top-btn po-btn-primary" onclick="submitBulkGrn(false)" id="btnSaveDraft">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
        Save GRN (Draft)
        <kbd class="po-kbd">Ctrl+S</kbd>
    </button>
    <?php if (Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)): ?>
    <button type="button" class="po-top-btn po-btn-success" onclick="submitBulkGrn(true)" id="btnSaveApprove" style="background: #16a34a; color: #ffffff; border-color: #15803d; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.25);">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        Save & Post to Stock
    </button>
    <?php endif; ?>
</div>

<style>
/* ── Hide bottom floating dock on full-screen procurement entry panels ── */
#curtiss-dock {
    display: none !important;
}

/* ── Fixed Screen Viewport & Body Reset (Screen-Fit Guaranteed) ── */
body, html {
    overflow: hidden !important;
}
.app-container, .app-main {
    height: 100vh !important;
    max-height: 100vh !important;
    overflow: hidden !important;
}
.app-content {
    padding: 6px 14px 0 14px !important;
    height: calc(100vh - var(--header-height, 64px)) !important;
    max-height: calc(100vh - var(--header-height, 64px)) !important;
    overflow: hidden !important;
    display: flex !important;
    flex-direction: column !important;
    box-sizing: border-box !important;
}

.bulk-panel-root {
    width: 100%;
    max-width: 100%;
    height: 100% !important;
    max-height: 100% !important;
    flex: 1 !important;
    min-height: 0 !important;
    display: flex !important;
    flex-direction: column !important;
    box-sizing: border-box !important;
    overflow: hidden !important;
    padding-bottom: 0 !important;
    font-family: inherit;
}

/* ── Top Floating Action Buttons ── */
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
    background: rgba(255, 255, 255, 0.95);
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
.po-btn-success:hover {
    background: #15803d;
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

/* ── Cards & Grids ── */
.po-upper-grid {
    display: grid;
    grid-template-columns: 320px 1fr;
    gap: 10px;
    margin-bottom: 6px;
    flex-shrink: 0;
}
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
    padding: 6px 12px;
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
    padding: 8px 12px;
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    background: #ffffff;
}

/* ── Full Card Length Invoice Grid (Requirement 3) ── */
.po-invoice-grid {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 10px;
    align-items: center;
    width: 100%;
    box-sizing: border-box;
}
.po-field-label {
    display: block;
    font-size: 0.68rem;
    font-weight: 800;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 3px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.po-input {
    width: 100%;
    height: 32px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    padding: 0 8px;
    font-size: 0.78rem;
    color: #0f172a;
    background: #ffffff;
    outline: none;
    box-sizing: border-box;
    transition: all 0.15s ease;
}
.po-input:focus {
    border-color: #0f172a;
    box-shadow: 0 0 0 2px rgba(15, 23, 42, 0.08);
}

/* ── Filter & Search Toolbar ── */
.bulk-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding: 5px 10px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    margin-bottom: 6px;
    flex-shrink: 0;
    flex-wrap: wrap;
}
.bulk-search-box {
    position: relative;
    flex: 1;
    min-width: 240px;
    max-width: 380px;
}
.bulk-search-box svg {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
}
.bulk-search-input {
    width: 100%;
    height: 32px;
    padding: 0 10px 0 32px;
    border: 1px solid #cbd5e1;
    border-radius: 999px;
    font-size: 12px;
    outline: none;
    background: #f8fafc;
    transition: all 0.15s ease;
}
.bulk-search-input:focus {
    background: #ffffff;
    border-color: #0f172a;
    box-shadow: 0 0 0 2px rgba(15, 23, 42, 0.08);
}
.bulk-filter-select {
    height: 32px;
    min-height: 32px;
    padding: 0 12px;
    border-radius: 999px;
    border: 1px solid #cbd5e1;
    font-size: 12px;
    font-weight: 700;
    color: #0f172a;
    background: #f8fafc;
    outline: none;
    cursor: pointer;
}
.bulk-filter-select:focus {
    border-color: #0f172a;
    background: #ffffff;
}

/* ── Table Panel & Scroll ── */
.bulk-table-panel {
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(16px);
    border: 1px solid rgba(226, 232, 240, 0.9);
    border-radius: 10px;
    box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.05);
    overflow: hidden;
    flex: 1;
    min-height: 0;
    display: flex;
    flex-direction: column;
}
.prod-table-scroll {
    overflow-y: auto;
    overflow-x: auto;
    flex: 1;
    min-height: 0;
}
.bulk-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.82rem;
    font-family: inherit;
}
.bulk-table thead th {
    background: #f8fafc;
    color: #64748b;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    padding: 10px 10px;
    position: sticky;
    top: 0;
    z-index: 10;
    border-bottom: 1px solid #e2e8f0;
    white-space: nowrap;
}
.bulk-table tbody tr {
    height: 48px;
    min-height: 48px;
    transition: background 0.12s ease;
    border-bottom: 1px solid #f1f5f9;
}
.bulk-table tbody tr:nth-child(even) { background: rgba(248, 250, 252, 0.57); }
.bulk-table tbody tr:nth-child(odd)  { background: #ffffff; }
.bulk-table tbody tr:hover            { background: #f1f5f9 !important; }

/* Highlight row when QTY is entered */
.bulk-table tbody tr.row-entered {
    background: #eff6ff !important;
}
.bulk-table tbody tr.row-new {
    background: #fffbeb !important;
}

.bulk-table td {
    height: 48px;
    padding: 0 10px;
    font-size: 12.5px;
    color: #1e293b;
    vertical-align: middle;
    box-sizing: border-box;
    white-space: nowrap;
}

/* Explicit Alignments */
.col-left { text-align: left; }
.col-center { text-align: center; }
.col-right { text-align: right; }

/* In-Cell Editable Inputs */
.cell-input {
    width: 100%;
    height: 30px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    padding: 0 6px;
    font-size: 12px;
    font-family: inherit;
    color: #0f172a;
    background: #ffffff;
    box-sizing: border-box;
}
.cell-input:focus {
    border-color: #0f172a;
    background: #ffffff;
    outline: none;
    box-shadow: 0 0 0 2px rgba(15, 23, 42, 0.1);
}
.cell-input-qty {
    text-align: center;
    font-weight: 800;
    font-family: monospace;
    font-size: 13px;
    color: #0f172a;
    background: #fdfdfd;
}
.cell-input-qty:focus {
    background: #ffffff;
    border-color: #2563eb;
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
}
.cell-input-num {
    text-align: right;
    font-family: monospace;
    font-weight: 700;
}

/* Available Lots Select Picker */
.lot-picker-select {
    width: 100%;
    max-width: 210px;
    height: 28px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    padding: 0 4px;
    font-size: 11px;
    font-weight: 600;
    color: #334155;
    background: #f8fafc;
    cursor: pointer;
    outline: none;
}
.lot-picker-select:focus {
    border-color: #0f172a;
    background: #ffffff;
}

/* Badges & Action Buttons */
.stock-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 2px 8px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 800;
    font-family: monospace;
}
.stock-in { background: #dcfce7; color: #15803d; }
.stock-low { background: #fef3c7; color: #b45309; }
.stock-out { background: #fee2e2; color: #b91c1c; }

.act-hide-btn {
    height: 28px;
    min-height: 28px;
    line-height: 26px;
    padding: 0 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    color: #dc2626;
    background: #fef2f2;
    border: 1px solid #fca5a5;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: all 0.15s ease;
}
.act-hide-btn:hover {
    background: #fee2e2;
    border-color: #f87171;
    transform: translateY(-1px);
}
.act-restore-btn {
    height: 28px;
    min-height: 28px;
    line-height: 26px;
    padding: 0 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    color: #15803d;
    background: #dcfce7;
    border: 1px solid #86efac;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

/* ── Fixed Bottom Summary Bar (Permanently Pinned to Screen Bottom) ── */
.bulk-bottom-panel {
    position: sticky;
    bottom: 0;
    z-index: 50;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-bottom: none;
    border-radius: 10px 10px 0 0;
    padding: 6px 14px;
    margin-top: 4px;
    margin-bottom: 0 !important;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    box-shadow: 0 -3px 12px rgba(0, 0, 0, 0.06);
}
.bulk-summary-chip {
    display: flex;
    flex-direction: column;
}
.bulk-summary-label {
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    color: #64748b;
    letter-spacing: 0.05em;
}
.bulk-summary-val {
    font-size: 13.5px;
    font-weight: 800;
    font-family: monospace;
    color: #0f172a;
}
</style>

<div class="bulk-panel-root">
    <!-- Header Headline -->
    <div style="flex-shrink: 0; display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
        <div style="display: flex; align-items: center; gap: 8px;">
            <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
            <h1 class="inv-title" style="font-size: 1.25rem; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.02em;">Bulk Goods Received Note (Bulk GRN)</h1>
            <span style="background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 800;">Fast Supplier Entry</span>
        </div>
        <div style="font-size: 0.74rem; color: #64748b; font-weight: 600;">
            Receiving Branch: <strong style="color: #0f172a;"><?= View::escape($activeBranchName) ?></strong>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (!empty($errorMsg)): ?>
        <div style="flex-shrink: 0; padding: 8px 12px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; color: #991b1b; font-size: 0.8rem; font-weight: 600; margin-bottom: 6px;" id="serverErrorAlert">
            ✕ <?= View::escape($errorMsg) ?>
        </div>
    <?php endif; ?>

    <form id="bulkGrnForm" onsubmit="event.preventDefault(); submitBulkGrn(false);" style="display: flex; flex-direction: column; flex: 1; min-height: 0;">
        <!-- Upper Grid: Supplier Card & Invoice Details Card -->
        <div class="po-upper-grid">
            <!-- Supplier Card -->
            <div class="po-card">
                <div class="po-card-strip">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <span>SUPPLIER / VENDOR *</span>
                    </div>
                    <span id="suppLinkedBadge" style="font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase;">0 PRODUCTS</span>
                </div>
                <div class="po-card-body">
                    <div>
                        <select name="supplier_id" id="bulkSupplierSelect" class="curtiss-select-enhance" data-create-type="supplier" onchange="onSupplierChange(this.value)" required>
                            <option value="">-- Select Supplier --</option>
                            <?php foreach ($suppliers as $s): ?>
                                <option value="<?= $s['id'] ?>">
                                    <?= View::escape($s['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Dynamic Supplier Info -->
                    <div id="suppInfoBox" style="margin-top: 4px;">
                        <div id="suppEmptyPlaceholder" style="padding: 4px; text-align: center; color: #94a3b8; font-size: 0.72rem;">
                            Select a supplier to load all catalog products automatically
                        </div>

                        <div id="suppDetailsContent" style="display: none;">
                            <div id="suppOutstandingAlert" style="display: none; background: #fef2f2; border: 1px solid #fecaca; border-radius: 4px; padding: 2px 6px; margin-top: 4px; font-size: 0.70rem; font-weight: 700; color: #b91c1c; justify-content: space-between; align-items: center;">
                                <span>⚠️ Payables:</span>
                                <span id="suppOutstandingVal" style="font-family: monospace;">LKR 0.00</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 3px;">
                                <div id="suppContactLine" style="font-size: 0.70rem; color: #64748b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">—</div>
                                <span id="suppCodeBadge" style="font-size: 9.5px; font-weight: 800; background: #f1f5f9; color: #0f172a; padding: 1px 4px; border-radius: 3px; font-family: monospace;">ID #—</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Invoice Details Card: Full Card Length Grid -->
            <div class="po-card">
                <div class="po-card-strip">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <span>INVOICE & RECEIVING SCHEDULE</span>
                    </div>
                    <span style="font-size: 10px; font-weight: 700; color: #94a3b8;">PROCUREMENT DETAILS</span>
                </div>
                <div class="po-card-body">
                    <div class="po-invoice-grid">
                        <div>
                            <label class="po-field-label">GRN NUMBER</label>
                            <div style="font-size: 0.82rem; font-weight: 800; color: #0284c7; background: #f0f9ff; padding: 5px 8px; border-radius: 6px; border: 1px solid #bae6fd; font-family: monospace; display: flex; align-items: center; gap: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                <span><?= View::escape($nextGrnNumber) ?></span>
                            </div>
                        </div>
                        <div>
                            <label class="po-field-label">SUPPLIER INVOICE NO *</label>
                            <input type="text" name="supplier_invoice_no" id="supplierInvoiceNoInput" class="po-input" placeholder="e.g. INV-2026-001" required>
                        </div>
                        <div>
                            <label class="po-field-label">INVOICE DATE</label>
                            <input type="date" name="supplier_invoice_date" id="supplierInvoiceDateInput" class="po-input" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div>
                            <label class="po-field-label">PAYMENT DUE DATE</label>
                            <input type="date" name="payment_due_date" id="paymentDueDateInput" class="po-input">
                        </div>
                        <div>
                            <label class="po-field-label">RECEIVED DATE *</label>
                            <input type="date" name="received_date" id="receivedDateInput" class="po-input" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div>
                            <label class="po-field-label">BILLED INVOICE (LKR) *</label>
                            <input type="number" step="0.01" min="0" name="supplier_invoice_amount" id="supplierInvoiceAmountInput" class="po-input" value="0.00" style="font-family: monospace; font-weight: 800; text-align: right;" oninput="updateSummaryCalculations()" required>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="bulk-toolbar">
            <!-- Realtime Search Box (Requirement 2) -->
            <div class="bulk-search-box">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" id="bulkTableSearch" class="bulk-search-input" placeholder="Real-time search products, barcodes, SKU, brand, category..." autocomplete="off">
            </div>

            <!-- Filter Controls -->
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <!-- Brand Filter -->
                <select id="filterBrand" class="bulk-filter-select" onchange="applyTableFilters()">
                    <option value="">All Brands</option>
                    <?php foreach ($brands as $b): ?>
                        <option value="<?= View::escape($b['name']) ?>"><?= View::escape($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <!-- Category Filter -->
                <select id="filterCategory" class="bulk-filter-select" onchange="applyTableFilters()">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= View::escape($c['name']) ?>"><?= View::escape($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <!-- Stock Filter -->
                <select id="filterStock" class="bulk-filter-select" onchange="applyTableFilters()">
                    <option value="all">All Stock Status</option>
                    <option value="in_stock">In Stock (> 0)</option>
                    <option value="low_stock">Low Stock (≤ 10)</option>
                    <option value="out_of_stock">Out of Stock (= 0)</option>
                </select>

                <!-- Entry Status Filter -->
                <select id="filterStatus" class="bulk-filter-select" onchange="applyTableFilters()">
                    <option value="all">All Products</option>
                    <option value="with_qty">Products with QTY Entered</option>
                    <option value="without_qty">Products without QTY</option>
                    <option value="new_products">New Products Added</option>
                    <option value="existing_products">Existing Products</option>
                    <option value="has_expiry">Expiry-Enabled Products</option>
                    <option value="hidden">Hidden Products</option>
                </select>

                <!-- Reset Filters Button -->
                <button type="button" class="po-top-btn po-btn-ghost" onclick="resetTableFilters()" style="height: 32px; padding: 0 12px; font-size: 11px;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
                    Clear Filters
                </button>

                <!-- Restore All Hidden Button -->
                <button type="button" id="btnRestoreAll" class="act-restore-btn" onclick="restoreAllHidden()" style="display: none;">
                    Restore All Hidden (<span id="hiddenCountBadge">0</span>)
                </button>
            </div>
        </div>

        <!-- Product Table Panel -->
        <div class="bulk-table-panel">
            <div class="prod-table-scroll">
                <table class="bulk-table" id="bulkTable">
                    <thead>
                        <tr>
                            <th style="width: 38px;" class="col-center">#</th>
                            <th style="width: 120px;" class="col-left">BARCODE</th>
                            <th style="width: 110px;" class="col-left">BRAND</th>
                            <th style="width: 120px;" class="col-left">CATEGORY</th>
                            <th style="width: 220px;" class="col-left">PRODUCT NAME</th>
                            <th style="width: 90px;" class="col-center">STOCK</th>
                            <th style="width: 120px;" class="col-right">COST PRICE (LKR)</th>
                            <th style="width: 95px;" class="col-right">MARGIN %</th>
                            <th style="width: 120px;" class="col-right">SELLING PRICE (LKR)</th>
                            <th style="width: 220px;" class="col-left">AVAILABLE LOTS (CLICK TO REUSE)</th>
                            <th style="width: 130px;" class="col-center">EXPIRY DATE</th>
                            <th style="width: 110px;" class="col-center">GRN QTY</th>
                            <th style="width: 120px;" class="col-right">LINE TOTAL (LKR)</th>
                            <th style="width: 70px;" class="col-center">ACTION</th>
                        </tr>
                    </thead>
                    <!-- Dedicated Inline Add Product Row (Empty line on top of the list with search & select, quick create) -->
                    <tbody id="bulkNewProductTbody" style="display: none; background: #f0f9ff; border-bottom: 2px solid #bae6fd;">
                        <tr id="inlineNewProductRow" style="height: 52px; background: #f0f9ff;">
                            <td class="col-center" style="vertical-align: middle;">
                                <span style="background: #0284c7; color: #ffffff; padding: 3px 6px; border-radius: 999px; font-size: 9.5px; font-weight: 800; white-space: nowrap; letter-spacing: 0.04em;">
                                    + NEW
                                </span>
                            </td>
                            <td class="col-left" style="vertical-align: middle;">
                                <div style="display: flex; gap: 4px; align-items: center;">
                                    <input type="text" id="inline_barcode" class="cell-input" placeholder="Barcode" style="font-family: monospace; font-weight: 700; width: 85px;" onkeydown="handleInlineKeydown(event, 'barcode')">
                                    <button type="button" onclick="generateInlineBarcode()" style="height: 28px; width: 24px; flex-shrink: 0; padding: 0; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; cursor: pointer; color: #0284c7; font-size: 11px; display: inline-flex; align-items: center; justify-content: center;" title="Auto-Generate Barcode" tabindex="-1">⚡</button>
                                </div>
                            </td>
                            <td class="col-left" style="vertical-align: middle;">
                                <div style="min-width: 105px;">
                                    <select id="inline_brand_id" class="curtiss-select-enhance" data-create-type="brand" style="width: 100%; height: 28px; font-size: 11px;">
                                        <option value="">-- Brand --</option>
                                        <?php foreach ($brands as $b): ?>
                                            <option value="<?= $b['id'] ?>" data-name="<?= View::escape($b['name']) ?>"><?= View::escape($b['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </td>
                            <td class="col-left" style="vertical-align: middle;">
                                <div style="min-width: 105px;">
                                    <select id="inline_category_id" class="curtiss-select-enhance" data-create-type="category" style="width: 100%; height: 28px; font-size: 11px;">
                                        <option value="">-- Category --</option>
                                        <?php foreach ($categories as $c): ?>
                                            <option value="<?= $c['id'] ?>" data-name="<?= View::escape($c['name']) ?>"><?= View::escape($c['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </td>
                            <td class="col-left" style="vertical-align: middle;">
                                <div style="display: flex; flex-direction: column; gap: 2px; min-width: 180px; max-width: 230px;">
                                    <input type="text" id="inline_description" class="cell-input" placeholder="Description (e.g. 500ml / Red)..." oninput="updateInlineNameAutoPrefix()" onkeydown="handleInlineKeydown(event, 'description')" style="height: 26px; font-size: 11px;">
                                    <div id="inline_name_preview" style="font-size: 10px; font-weight: 700; color: #0284c7; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="Auto-Generated Product Name">
                                        <span style="color: #94a3b8; font-style: italic; font-weight: 500;">(Brand + Category + Description)</span>
                                    </div>
                                </div>
                            </td>
                            <td class="col-center" style="vertical-align: middle;">
                                <span class="stock-pill" style="background: #e0f2fe; color: #0369a1; font-weight: 800; font-size: 9.5px;">NEW</span>
                            </td>
                            <td class="col-right" style="vertical-align: middle;">
                                <input type="number" step="0.01" min="0" id="inline_cost" class="cell-input cell-input-num" placeholder="0.00" oninput="calcInlinePriceFromCost()" onkeydown="handleInlineKeydown(event, 'cost')" style="width: 90px;">
                            </td>
                            <td class="col-right" style="vertical-align: middle;">
                                <input type="number" step="0.01" id="inline_margin" class="cell-input cell-input-num" placeholder="0.00" oninput="calcInlinePriceFromMargin()" onkeydown="handleInlineKeydown(event, 'margin')" style="width: 70px;">
                            </td>
                            <td class="col-right" style="vertical-align: middle;">
                                <input type="number" step="0.01" min="0" id="inline_price" class="cell-input cell-input-num" placeholder="0.00" oninput="calcInlineMarginFromPrice()" onkeydown="handleInlineKeydown(event, 'price')" style="width: 90px;">
                            </td>
                            <td class="col-left" style="vertical-align: middle;">
                                <span style="font-size: 11px; color: #94a3b8; font-style: italic;">New Price Lot</span>
                            </td>
                            <td class="col-center" style="vertical-align: middle;">
                                <input type="date" id="inline_expiry" class="cell-input" style="width: 115px; font-size: 11px;" onkeydown="handleInlineKeydown(event, 'expiry')">
                            </td>
                            <td class="col-center" style="vertical-align: middle;">
                                <input type="number" step="1" min="1" id="inline_qty" class="cell-input cell-input-qty" placeholder="Qty" oninput="calcInlineLineTotal()" onkeydown="handleInlineKeydown(event, 'qty')" style="width: 75px; color: #0284c7;">
                            </td>
                            <td class="col-right" style="vertical-align: middle; font-family: monospace; font-weight: 800; color: #0f172a;">
                                <span id="inline_line_total">0.00</span>
                            </td>
                            <td class="col-center" style="vertical-align: middle;">
                                <button type="button" class="po-top-btn po-btn-primary" onclick="commitInlineNewProduct()" style="height: 28px; line-height: 26px; padding: 0 10px; font-size: 11px; font-weight: 800; border-radius: 999px; background: #0284c7; border-color: #0369a1; box-shadow: 0 2px 6px rgba(2, 132, 199, 0.25); white-space: nowrap;" title="Add product to GRN">
                                    + Add
                                </button>
                            </td>
                        </tr>
                    </tbody>
                    <tbody id="bulkTableBody">
                        <!-- Initial Prompt State -->
                        <tr id="emptySupplierPromptRow">
                            <td colspan="14" style="text-align: center; padding: 60px 12px; color: #94a3b8;">
                                <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.8"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                    <div style="font-size: 0.92rem; font-weight: 700; color: #64748b;">Please select a supplier from the dropdown above</div>
                                    <div style="font-size: 0.76rem; color: #94a3b8;">All active products assigned to the supplier will automatically load here. You can also add new items directly on the top row anytime.</div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Fixed Bottom Panel (Pinned to Screen Bottom as required in Point 6) -->
        <div class="bulk-bottom-panel">
            <div style="flex: 1; max-width: 440px;">
                <label class="po-field-label" style="margin-bottom: 2px;">REMARKS & RECEIVING NOTES</label>
                <textarea name="notes" id="bulkGrnNotes" class="po-input" placeholder="Goods condition, delivery vehicle, notes..." style="height: 38px; padding: 4px 8px; resize: none; font-size: 0.76rem;"></textarea>
            </div>

            <!-- Summary Chips -->
            <div style="display: flex; align-items: center; gap: 20px; flex-wrap: wrap;">
                <div class="bulk-summary-chip">
                    <span class="bulk-summary-label">Total Products</span>
                    <span class="bulk-summary-val" id="summaryTotalProds">0</span>
                </div>
                <div class="bulk-summary-chip">
                    <span class="bulk-summary-label">Selected (Qty > 0)</span>
                    <span class="bulk-summary-val" id="summarySelectedProds" style="color: #2563eb;">0</span>
                </div>
                <div class="bulk-summary-chip">
                    <span class="bulk-summary-label">Total Received Qty</span>
                    <span class="bulk-summary-val" id="summaryTotalQty">0</span>
                </div>
                <div class="bulk-summary-chip">
                    <span class="bulk-summary-label">Actual GRN Total</span>
                    <span class="bulk-summary-val" id="summaryActualTotal" style="color: #0f172a;">LKR 0.00</span>
                </div>
                <div class="bulk-summary-chip">
                    <span class="bulk-summary-label">Billed Invoice</span>
                    <span class="bulk-summary-val" id="summaryBilledTotal">LKR 0.00</span>
                </div>
                <div class="bulk-summary-chip">
                    <span class="bulk-summary-label">Variance</span>
                    <span class="bulk-summary-val" id="summaryVariance" style="color: #15803d;">+0.00</span>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="assets/js/curtiss_select.js"></script>
<script>
// State Management
let loadedProducts = [];       // Array of supplier's existing product objects
let newProductsList = [];      // Array of locally added new products
let hiddenProductIds = new Set();
let nextNewRowIndex = 1;
let suppInstance = null;

document.addEventListener('DOMContentLoaded', () => {
    // 1. Attach searchable combobox to Supplier dropdown
    const suppSelect = document.getElementById('bulkSupplierSelect');
    if (suppSelect && window.CurtissSearchableSelect) {
        suppInstance = CurtissSearchableSelect.attach(suppSelect);
    }

    // Auto-open supplier search combobox on page load (same as other GRN panel)
    setTimeout(() => {
        if (suppInstance && !suppSelect.value) {
            suppInstance.toggleMenu();
            if (suppInstance.searchInput) {
                suppInstance.searchInput.focus();
            }
        }
    }, 100);

    // 2. Real-time Search Input Listener (Requirement 2)
    const searchInput = document.getElementById('bulkTableSearch');
    if (searchInput) {
        ['input', 'keyup', 'search'].forEach(evt => {
            searchInput.addEventListener(evt, () => {
                applyTableFilters();
            });
        });
    }

    // 3. Sequential Header Keyboard Navigation (Requirement 5)
    setupHeaderKeyboardFlow();

    // 4. Inline New Product Row Keyboard & Searchable Select Setup
    setupInlineKeyboardFlow();

    // 5. Global Keyboard Shortcuts (Ctrl+S)
    window.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) {
            e.preventDefault();
            submitBulkGrn(false);
        }
    });
});

// Sequential Header Keyboard Navigation (same as other GRN panel)
function setupHeaderKeyboardFlow() {
    const invNoEl = document.getElementById('supplierInvoiceNoInput');
    const invDateEl = document.getElementById('supplierInvoiceDateInput');
    const dueDateEl = document.getElementById('paymentDueDateInput');
    const recvDateEl = document.getElementById('receivedDateInput');
    const invAmtEl = document.getElementById('supplierInvoiceAmountInput');

    if (invNoEl) {
        invNoEl.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (invDateEl) invDateEl.focus();
            }
        });
    }
    if (invDateEl) {
        invDateEl.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (dueDateEl) dueDateEl.focus();
            }
        });
    }
    if (dueDateEl) {
        dueDateEl.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (recvDateEl) recvDateEl.focus();
            }
        });
    }
    if (recvDateEl) {
        recvDateEl.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (invAmtEl) { invAmtEl.focus(); invAmtEl.select(); }
            }
        });
    }
    if (invAmtEl) {
        invAmtEl.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                // Move directly down to first visible row in the table
                focusFirstVisibleRow();
            }
        });
    }
}

// Supplier Change: Fetch all products for selected supplier
async function onSupplierChange(supplierId) {
    const tableBody = document.getElementById('bulkTableBody');
    const newTbody = document.getElementById('bulkNewProductTbody');
    const emptyBox = document.getElementById('suppEmptyPlaceholder');
    const detailsBox = document.getElementById('suppDetailsContent');
    const linkedBadge = document.getElementById('suppLinkedBadge');

    if (!supplierId) {
        if (newTbody) newTbody.style.display = 'none';
        emptyBox.style.display = 'block';
        detailsBox.style.display = 'none';
        linkedBadge.innerText = '0 PRODUCTS';
        loadedProducts = [];
        newProductsList = [];
        renderTableRows();
        return;
    }

    if (newTbody) {
        newTbody.style.display = 'table-row-group';
        if (!document.getElementById('inline_barcode')?.value) {
            generateInlineBarcode();
        }
    }

    // Show loading indicator in table
    tableBody.innerHTML = `
        <tr>
            <td colspan="14" style="text-align: center; padding: 60px 12px; color: #64748b;">
                <div style="display: flex; flex-direction: column; align-items: center; gap: 10px;">
                    <div style="width: 28px; height: 28px; border: 3px solid #cbd5e1; border-top-color: #0f172a; border-radius: 50%; animation: spin 0.8s linear infinite;"></div>
                    <div style="font-size: 0.90rem; font-weight: 700;">Loading supplier products & price lots...</div>
                </div>
            </td>
        </tr>
    `;

    try {
        const res = await fetch(`bulk_grn_api.php?action=get_supplier_products&supplier_id=${supplierId}`);
        const data = await res.json();

        if (!data.success) {
            alert('Failed to load supplier products: ' + data.message);
            tableBody.innerHTML = `<tr><td colspan="14" style="text-align: center; padding: 40px; color: #dc2626;">${data.message}</td></tr>`;
            return;
        }

        // Update supplier card
        const s = data.supplier;
        emptyBox.style.display = 'none';
        detailsBox.style.display = 'block';
        linkedBadge.innerText = `${data.count} PRODUCTS`;
        document.getElementById('suppCodeBadge').innerText = s.code ? s.code : `ID #${s.id}`;
        
        let contact = [];
        if (s.phone) contact.push(s.phone);
        if (s.whatsapp) contact.push('WA: ' + s.whatsapp);
        document.getElementById('suppContactLine').innerText = contact.length ? contact.join(' • ') : 'No phone recorded';

        const outAlert = document.getElementById('suppOutstandingAlert');
        const outAmt = parseFloat(s.outstanding_payable || 0);
        if (outAmt > 0) {
            outAlert.style.display = 'flex';
            document.getElementById('suppOutstandingVal').innerText = 'LKR ' + outAmt.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        } else {
            outAlert.style.display = 'none';
        }

        // Initialize loaded products with row state
        loadedProducts = (data.products || []).map(p => ({
            id: parseInt(p.id),
            barcode: p.barcode || '',
            sku: p.sku || '',
            name: p.name || '',
            brand_id: parseInt(p.brand_id) || 0,
            brand_name: p.brand_name || 'Unassigned',
            category_id: parseInt(p.category_id) || 0,
            category_name: p.category_name || 'Uncategorized',
            current_stock: parseFloat(p.current_stock || 0),
            cost: parseFloat(p.cost || 0),
            selling_price: parseFloat(p.selling_price || 0),
            has_expiry: parseInt(p.has_expiry) === 1,
            expiry_date: p.latest_expiry_date || '',
            batch_number: p.latest_batch_number || '',
            quantity: '', // initially empty as per specification!
            lots: p.lots || [],
            is_new: false,
            is_hidden: false
        }));

        hiddenProductIds.clear();
        renderTableRows();

        // Auto-advance focus to Supplier Invoice No input
        setTimeout(() => {
            const invNo = document.getElementById('supplierInvoiceNoInput');
            if (invNo) { invNo.focus(); invNo.select(); }
        }, 100);

    } catch (err) {
        console.error(err);
        tableBody.innerHTML = `<tr><td colspan="14" style="text-align: center; padding: 40px; color: #dc2626;">Network error while fetching supplier products.</td></tr>`;
    }
}

// ── Inline Add Product Row System (Top Row Entry with Searchable Comboboxes & Quick-Create) ──
let inlineKeyboardSetupDone = false;

function capitalizeWords(str) {
    return (str || '').replace(/(^|[\s\-\/\(])([a-z])/g, function(match, prefix, char) {
        return prefix + char.toUpperCase();
    });
}

function updateInlineNameAutoPrefix() {
    const brandSel = document.getElementById('inline_brand_id');
    const catSel = document.getElementById('inline_category_id');
    const descInput = document.getElementById('inline_description');
    const preview = document.getElementById('inline_name_preview');
    if (!brandSel || !catSel || !descInput || !preview) return;

    const brandOpt = brandSel.options[brandSel.selectedIndex];
    const catOpt = catSel.options[catSel.selectedIndex];
    const brandName = brandOpt && brandOpt.value ? (brandOpt.getAttribute('data-name') || brandOpt.text || '').trim() : '';
    const catName = catOpt && catOpt.value ? (catOpt.getAttribute('data-name') || catOpt.text || '').trim() : '';
    const desc = descInput.value.trim();

    const parts = [];
    if (brandName) parts.push(brandName);
    if (catName) parts.push(catName);
    if (desc) parts.push(desc);

    const fullName = capitalizeWords(parts.join(' '));
    if (fullName) {
        preview.innerHTML = escapeHtml(fullName);
        preview.title = fullName;
    } else {
        preview.innerHTML = '<span style="color: #94a3b8; font-style: italic; font-weight: 500;">(Brand + Category + Description)</span>';
        preview.title = 'Auto-Generated Product Name';
    }
}

function setupInlineKeyboardFlow() {
    if (inlineKeyboardSetupDone) return;
    inlineKeyboardSetupDone = true;

    const brandEl = document.getElementById('inline_brand_id');
    const catEl = document.getElementById('inline_category_id');
    const descEl = document.getElementById('inline_description');

    // Attach CurtissSearchableSelect with quick create capability
    if (brandEl && !brandEl._curtissSelect && window.CurtissSearchableSelect) {
        CurtissSearchableSelect.attach(brandEl);
        brandEl.addEventListener('change', updateInlineNameAutoPrefix);
    }
    if (catEl && !catEl._curtissSelect && window.CurtissSearchableSelect) {
        CurtissSearchableSelect.attach(catEl);
        catEl.addEventListener('change', updateInlineNameAutoPrefix);
    }

    descEl?.addEventListener('input', function() {
        const selStart = this.selectionStart;
        const selEnd = this.selectionEnd;
        const oldVal = this.value;
        const newVal = capitalizeWords(oldVal);
        if (oldVal !== newVal) {
            this.value = newVal;
            if (typeof this.setSelectionRange === 'function') {
                this.setSelectionRange(selStart, selEnd);
            }
        }
        updateInlineNameAutoPrefix();
    });
}

function focusInlineAddRow() {
    const suppSelect = document.getElementById('bulkSupplierSelect');
    const supplierId = parseInt(suppSelect ? suppSelect.value : 0);
    if (!supplierId) {
        alert('Please select a Supplier first before adding new products.');
        if (suppInstance) suppInstance.toggleMenu();
        return;
    }

    const newTbody = document.getElementById('bulkNewProductTbody');
    if (newTbody) newTbody.style.display = 'table-row-group';

    const barcodeIn = document.getElementById('inline_barcode');
    if (barcodeIn) {
        if (!barcodeIn.value) generateInlineBarcode();
        barcodeIn.focus();
        barcodeIn.select();
    }
}

async function generateInlineBarcode() {
    try {
        const res = await fetch('bulk_grn_api.php?action=generate_barcode');
        const data = await res.json();
        if (data.success) {
            const el = document.getElementById('inline_barcode');
            if (el) el.value = data.barcode;
        }
    } catch (e) {
        const b = 'B' + Date.now().toString().slice(-8);
        const el = document.getElementById('inline_barcode');
        if (el) el.value = b;
    }
}

// Inline Bi-Directional Margin & Selling Price Calculations
function calcInlinePriceFromMargin() {
    const cost = parseFloat(document.getElementById('inline_cost')?.value || 0);
    const marginVal = document.getElementById('inline_margin')?.value;
    if (cost > 0 && marginVal !== '') {
        const margin = parseFloat(marginVal || 0);
        const price = Math.round((cost * (1 + (margin / 100))) * 100) / 100;
        const priceEl = document.getElementById('inline_price');
        if (priceEl) priceEl.value = price.toFixed(2);
    }
    calcInlineLineTotal();
}

function calcInlineMarginFromPrice() {
    const cost = parseFloat(document.getElementById('inline_cost')?.value || 0);
    const price = parseFloat(document.getElementById('inline_price')?.value || 0);
    const marginEl = document.getElementById('inline_margin');
    if (cost > 0 && price > 0) {
        const margin = (((price - cost) / cost) * 100).toFixed(2);
        if (marginEl) marginEl.value = margin;
    } else if (marginEl && !price) {
        marginEl.value = '';
    }
    calcInlineLineTotal();
}

function calcInlinePriceFromCost() {
    const cost = parseFloat(document.getElementById('inline_cost')?.value || 0);
    const marginVal = document.getElementById('inline_margin')?.value;
    const priceVal = document.getElementById('inline_price')?.value;
    if (marginVal !== '' && cost > 0) {
        calcInlinePriceFromMargin();
    } else if (cost > 0 && priceVal) {
        calcInlineMarginFromPrice();
    }
    calcInlineLineTotal();
}

function calcInlineLineTotal() {
    const qty = parseFloat(document.getElementById('inline_qty')?.value || 0);
    const cost = parseFloat(document.getElementById('inline_cost')?.value || 0);
    const totalSpan = document.getElementById('inline_line_total');
    if (totalSpan) {
        const lineTotal = (qty > 0 && cost > 0) ? (qty * cost) : 0;
        totalSpan.innerText = lineTotal > 0 ? lineTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '0.00';
    }
}

function handleInlineKeydown(e, field) {
    if (e.key === 'Enter') {
        e.preventDefault();
        if (field === 'barcode') {
            const brandEl = document.getElementById('inline_brand_id');
            if (brandEl && brandEl._curtissSelect) {
                brandEl._curtissSelect.toggleMenu();
            } else {
                document.getElementById('inline_description')?.focus();
            }
        } else if (field === 'description') {
            const costEl = document.getElementById('inline_cost');
            if (costEl) { costEl.focus(); costEl.select(); }
        } else if (field === 'cost') {
            const marginEl = document.getElementById('inline_margin');
            if (marginEl) { marginEl.focus(); marginEl.select(); }
        } else if (field === 'margin') {
            const priceEl = document.getElementById('inline_price');
            if (priceEl) { priceEl.focus(); priceEl.select(); }
        } else if (field === 'price') {
            const qtyEl = document.getElementById('inline_qty');
            if (qtyEl) { qtyEl.focus(); qtyEl.select(); }
        } else if (field === 'expiry') {
            const qtyEl = document.getElementById('inline_qty');
            if (qtyEl) { qtyEl.focus(); qtyEl.select(); }
        } else if (field === 'qty') {
            commitInlineNewProduct();
        }
    }
}

function commitInlineNewProduct() {
    const suppSelect = document.getElementById('bulkSupplierSelect');
    const supplierId = parseInt(suppSelect ? suppSelect.value : 0);
    if (!supplierId) {
        alert('Please select a Supplier first.');
        if (suppInstance) suppInstance.toggleMenu();
        return;
    }

    let barcode = (document.getElementById('inline_barcode')?.value || '').trim();
    if (!barcode) {
        barcode = 'B' + Date.now().toString().slice(-8);
        document.getElementById('inline_barcode').value = barcode;
    }

    const brandSelect = document.getElementById('inline_brand_id');
    const brandId = parseInt(brandSelect?.value || 0);
    const brandOpt = brandSelect?.options[brandSelect.selectedIndex];
    const brandName = brandOpt && brandOpt.value ? (brandOpt.getAttribute('data-name') || brandOpt.text || '').trim() : '';

    const catSelect = document.getElementById('inline_category_id');
    const catId = parseInt(catSelect?.value || 0);
    const catOpt = catSelect?.options[catSelect.selectedIndex];
    const catName = catOpt && catOpt.value ? (catOpt.getAttribute('data-name') || catOpt.text || '').trim() : '';

    const description = (document.getElementById('inline_description')?.value || '').trim();

    const parts = [];
    if (brandName) parts.push(brandName);
    if (catName) parts.push(catName);
    if (description) parts.push(description);

    let fullName = capitalizeWords(parts.join(' '));
    if (!fullName) {
        alert('Please select Brand/Category or enter Description to create the product name.');
        document.getElementById('inline_description')?.focus();
        return;
    }

    const cost = parseFloat(document.getElementById('inline_cost')?.value || 0);
    const margin = document.getElementById('inline_margin')?.value !== '' ? parseFloat(document.getElementById('inline_margin')?.value) : '';
    let price = parseFloat(document.getElementById('inline_price')?.value || 0);
    const expiryDate = (document.getElementById('inline_expiry')?.value || '').trim();
    const qty = parseFloat(document.getElementById('inline_qty')?.value || 0);

    if (qty <= 0) {
        alert('Please enter Received QTY greater than 0.');
        const qtyIn = document.getElementById('inline_qty');
        if (qtyIn) { qtyIn.focus(); qtyIn.select(); }
        return;
    }
    if (cost <= 0) {
        alert('Please enter a valid Cost Price.');
        const costIn = document.getElementById('inline_cost');
        if (costIn) { costIn.focus(); costIn.select(); }
        return;
    }
    if (price <= 0) {
        if (margin !== '' && cost > 0) {
            price = Math.round((cost * (1 + (margin / 100))) * 100) / 100;
        } else {
            alert('Please enter a valid Selling Price.');
            const priceIn = document.getElementById('inline_price');
            if (priceIn) { priceIn.focus(); priceIn.select(); }
            return;
        }
    }

    const tempId = 'new_' + nextNewRowIndex++;
    const newProd = {
        temp_id: tempId,
        barcode: barcode,
        sku: barcode,
        name: fullName,
        description: description,
        brand_id: brandId || 1,
        brand_name: brandName || 'General',
        category_id: catId || 1,
        category_name: catName || 'General',
        current_stock: 0,
        cost: cost,
        margin: margin,
        selling_price: price,
        has_expiry: Boolean(expiryDate),
        expiry_date: expiryDate,
        batch_number: '',
        quantity: qty.toString(),
        lots: [],
        is_new: true,
        is_hidden: false
    };

    newProductsList.unshift(newProd);
    renderTableRows();
    resetInlineNewRow();
}

function resetInlineNewRow() {
    const descIn = document.getElementById('inline_description');
    if (descIn) descIn.value = '';
    const preview = document.getElementById('inline_name_preview');
    if (preview) {
        preview.innerHTML = '<span style="color: #94a3b8; font-style: italic; font-weight: 500;">(Brand + Category + Description)</span>';
        preview.title = 'Auto-Generated Product Name';
    }
    const costIn = document.getElementById('inline_cost');
    if (costIn) costIn.value = '';
    const marginIn = document.getElementById('inline_margin');
    if (marginIn) marginIn.value = '';
    const priceIn = document.getElementById('inline_price');
    if (priceIn) priceIn.value = '';
    const expIn = document.getElementById('inline_expiry');
    if (expIn) expIn.value = '';
    const qtyIn = document.getElementById('inline_qty');
    if (qtyIn) qtyIn.value = '';
    const totalSpan = document.getElementById('inline_line_total');
    if (totalSpan) totalSpan.innerText = '0.00';

    generateInlineBarcode();

    setTimeout(() => {
        const barcodeIn = document.getElementById('inline_barcode');
        if (barcodeIn) {
            barcodeIn.focus();
            barcodeIn.select();
        }
    }, 60);
}

// Render Table Rows based on current loaded products, new products, and filters
function renderTableRows() {
    const tableBody = document.getElementById('bulkTableBody');
    const allItems = [...newProductsList, ...loadedProducts];

    if (allItems.length === 0) {
        tableBody.innerHTML = `
            <tr id="emptySupplierPromptRow">
                <td colspan="14" style="text-align: center; padding: 60px 12px; color: #64748b;">
                    <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.8"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <div style="font-size: 0.92rem; font-weight: 700; color: #64748b;">No active products assigned to this supplier yet.</div>
                        <div style="font-size: 0.76rem; color: #94a3b8;">Use the "+ NEW" row above to add products to this supplier and GRN immediately.</div>
                    </div>
                </td>
            </tr>
        `;
        updateSummaryCalculations();
        return;
    }

    let rowsHtml = '';
    let visibleIndex = 1;

    allItems.forEach((p) => {
        const rowId = p.is_new ? p.temp_id : p.id;
        const isHidden = p.is_new ? p.is_hidden : hiddenProductIds.has(p.id);
        const hasQty = parseFloat(p.quantity || 0) > 0;
        const lineTotal = hasQty ? (parseFloat(p.quantity) * parseFloat(p.cost || 0)) : 0;

        let stockBadge = '';
        if (p.is_new) {
            stockBadge = `<span class="stock-pill stock-out">NEW</span>`;
        } else if (p.current_stock > 10) {
            stockBadge = `<span class="stock-pill stock-in">${p.current_stock.toFixed(0)}</span>`;
        } else if (p.current_stock > 0) {
            stockBadge = `<span class="stock-pill stock-low">${p.current_stock.toFixed(0)}</span>`;
        } else {
            stockBadge = `<span class="stock-pill stock-out">0</span>`;
        }

        let lotsDropdown = '';
        if (p.is_new) {
            lotsDropdown = `<span style="font-size: 11px; color: #94a3b8; font-style: italic;">New Price Lot</span>`;
        } else if (p.lots && p.lots.length > 0) {
            lotsDropdown = `<select class="lot-picker-select" onchange="onSelectLot('${rowId}', this.value)">
                <option value="">-- Available Lots (${p.lots.length}) --</option>
                ${p.lots.map(l => `
                    <option value="${l.id}">
                        ${l.display}
                    </option>
                `).join('')}
            </select>`;
        } else {
            lotsDropdown = `<span style="font-size: 11px; color: #94a3b8; font-style: italic;">No active lots (will create)</span>`;
        }

        const trClass = p.is_new ? 'row-new' : (hasQty ? 'row-entered' : '');
        const hideStyle = isHidden ? 'display: none;' : '';

        let marginVal = '';
        if (p.cost > 0 && p.selling_price > 0) {
            marginVal = (((p.selling_price - p.cost) / p.cost) * 100).toFixed(2);
        } else if (p.margin !== undefined && p.margin !== null && p.margin !== '') {
            marginVal = parseFloat(p.margin).toFixed(2);
        }

        rowsHtml += `
            <tr id="row_${rowId}" class="${trClass}" style="${hideStyle}" data-row-id="${rowId}" data-is-new="${p.is_new ? '1' : '0'}" data-is-hidden="${isHidden ? '1' : '0'}">
                <td class="col-center" style="font-weight: 800; font-size: 11px; color: #64748b;">
                    ${p.is_new ? '<span style="background: #f59e0b; color: #fff; padding: 1px 5px; border-radius: 4px; font-size: 9px;">NEW</span>' : visibleIndex++}
                </td>
                <td class="col-left">
                    <span style="font-family: monospace; font-weight: 700; color: #334155;">${escapeHtml(p.barcode || p.sku)}</span>
                </td>
                <td class="col-left">
                    <span style="color: #475569; font-size: 12px;">${escapeHtml(p.brand_name)}</span>
                </td>
                <td class="col-left">
                    <span style="color: #475569; font-size: 12px;">${escapeHtml(p.category_name)}</span>
                </td>
                <td class="col-left" style="font-weight: 700;">
                    <span style="color: #0f172a;">${escapeHtml(p.name)}</span>
                </td>
                <td class="col-center">
                    ${stockBadge}
                </td>
                <td class="col-right">
                    <input type="number" step="0.01" min="0" id="cost_${rowId}" value="${p.cost > 0 ? p.cost.toFixed(2) : '0.00'}" class="cell-input cell-input-num" oninput="updateRowCost('${rowId}', this.value)" onkeydown="handleCostKeydown(event, '${rowId}')">
                </td>
                <td class="col-right">
                    <input type="number" step="0.01" id="margin_${rowId}" value="${marginVal}" class="cell-input cell-input-num" placeholder="0.00" oninput="updateRowMargin('${rowId}', this.value)" onkeydown="handleMarginKeydown(event, '${rowId}')">
                </td>
                <td class="col-right">
                    <input type="number" step="0.01" min="0" id="price_${rowId}" value="${p.selling_price > 0 ? p.selling_price.toFixed(2) : '0.00'}" class="cell-input cell-input-num" oninput="updateRowPrice('${rowId}', this.value)" onkeydown="handlePriceKeydown(event, '${rowId}')">
                </td>
                <td class="col-left">
                    ${lotsDropdown}
                </td>
                <td class="col-center">
                    ${p.has_expiry ? `
                        <input type="date" id="exp_${rowId}" value="${p.expiry_date || ''}" class="cell-input" style="width: 110px;" oninput="updateRowField('${rowId}', 'expiry_date', this.value)" onkeydown="handleExpiryKeydown(event, '${rowId}')">
                    ` : `
                        <span style="color: #cbd5e1; font-weight: 700;">—</span>
                    `}
                </td>
                <td class="col-center">
                    <input type="number" step="1" min="0" id="qty_${rowId}" value="${p.quantity}" class="cell-input cell-input-qty" placeholder="0" oninput="updateRowQty('${rowId}', this.value)" onkeydown="handleQtyKeydown(event, '${rowId}')">
                </td>
                <td class="col-right" style="font-family: monospace; font-weight: 800; color: #0f172a;">
                    <span id="line_total_${rowId}">${lineTotal > 0 ? lineTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '0.00'}</span>
                </td>
                <td class="col-center">
                    ${p.is_new ? `
                        <button type="button" class="act-hide-btn" onclick="removeNewProductRow('${rowId}')" title="Delete new product row">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            Remove
                        </button>
                    ` : (isHidden ? `
                        <button type="button" class="act-restore-btn" onclick="restoreRow('${rowId}')" title="Restore hidden product row">
                            Restore
                        </button>
                    ` : `
                        <button type="button" class="act-hide-btn" onclick="hideRow('${rowId}')" title="Hide product from current GRN">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                            Hide
                        </button>
                    `)}
                </td>
            </tr>
        `;
    });

    tableBody.innerHTML = rowsHtml;
    applyTableFilters();
    updateSummaryCalculations();
}

// Update Row Quantity
function updateRowQty(rowId, val) {
    const item = findItemById(rowId);
    if (!item) return;

    item.quantity = val.trim();
    const qty = parseFloat(item.quantity || 0);
    const lineTotal = qty > 0 ? (qty * parseFloat(item.cost || 0)) : 0;

    const lineTotalSpan = document.getElementById(`line_total_${rowId}`);
    if (lineTotalSpan) {
        lineTotalSpan.innerText = lineTotal > 0 ? lineTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '0.00';
    }

    const tr = document.getElementById(`row_${rowId}`);
    if (tr) {
        if (qty > 0) {
            tr.classList.add('row-entered');
        } else {
            tr.classList.remove('row-entered');
        }
    }

    updateSummaryCalculations();
}

// Update Row Margin (%) & Automatically set Selling Price
function updateRowMargin(rowId, val) {
    const item = findItemById(rowId);
    if (!item) return;
    item.margin = val !== '' ? parseFloat(val) : '';
    const cost = parseFloat(item.cost || 0);
    if (cost > 0 && val !== '') {
        const margin = parseFloat(val || 0);
        const newPrice = Math.round((cost * (1 + (margin / 100))) * 100) / 100;
        item.selling_price = newPrice;
        const priceInput = document.getElementById(`price_${rowId}`);
        if (priceInput) priceInput.value = newPrice.toFixed(2);
    }
}

// Update Row Cost Price: recalculates selling price if margin set, or recalculates margin if price exists
function updateRowCost(rowId, val) {
    const item = findItemById(rowId);
    if (!item) return;

    item.cost = parseFloat(val || 0);
    const cost = item.cost;
    const marginInput = document.getElementById(`margin_${rowId}`);
    const priceInput = document.getElementById(`price_${rowId}`);

    if (marginInput && marginInput.value !== '' && cost > 0) {
        const margin = parseFloat(marginInput.value || 0);
        const newPrice = Math.round((cost * (1 + (margin / 100))) * 100) / 100;
        item.selling_price = newPrice;
        if (priceInput) priceInput.value = newPrice.toFixed(2);
    } else if (cost > 0 && item.selling_price > 0) {
        const margin = (((item.selling_price - cost) / cost) * 100).toFixed(2);
        item.margin = margin;
        if (marginInput) marginInput.value = margin;
    }

    const qty = parseFloat(item.quantity || 0);
    const lineTotal = qty > 0 ? (qty * item.cost) : 0;

    const lineTotalSpan = document.getElementById(`line_total_${rowId}`);
    if (lineTotalSpan) {
        lineTotalSpan.innerText = lineTotal > 0 ? lineTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '0.00';
    }
    updateSummaryCalculations();
}

// Update Row Selling Price: auto recalculates margin %
function updateRowPrice(rowId, val) {
    const item = findItemById(rowId);
    if (!item) return;
    item.selling_price = parseFloat(val || 0);

    const cost = parseFloat(item.cost || 0);
    const marginInput = document.getElementById(`margin_${rowId}`);
    if (cost > 0 && item.selling_price > 0) {
        const margin = (((item.selling_price - cost) / cost) * 100).toFixed(2);
        item.margin = margin;
        if (marginInput) marginInput.value = margin;
    } else if (marginInput && !val) {
        marginInput.value = '';
        item.margin = '';
    }
}

// Generic row field update
function updateRowField(rowId, field, val) {
    const item = findItemById(rowId);
    if (!item) return;
    item[field] = val;
}

// Existing price lot selection handler
function onSelectLot(rowId, lotId) {
    if (!lotId) return;
    const item = findItemById(rowId);
    if (!item || !item.lots) return;

    const selectedLot = item.lots.find(l => l.id == lotId);
    if (!selectedLot) return;

    item.cost = parseFloat(selectedLot.cost);
    item.selling_price = parseFloat(selectedLot.selling_price);

    const costInput = document.getElementById(`cost_${rowId}`);
    const marginInput = document.getElementById(`margin_${rowId}`);
    const priceInput = document.getElementById(`price_${rowId}`);

    if (costInput) costInput.value = item.cost.toFixed(2);
    if (priceInput) priceInput.value = item.selling_price.toFixed(2);
    if (marginInput) {
        if (item.cost > 0 && item.selling_price > 0) {
            marginInput.value = (((item.selling_price - item.cost) / item.cost) * 100).toFixed(2);
        } else {
            marginInput.value = '';
        }
    }

    if (item.has_expiry && selectedLot.expiry_date) {
        item.expiry_date = selectedLot.expiry_date;
        const expInput = document.getElementById(`exp_${rowId}`);
        if (expInput) expInput.value = item.expiry_date;
    }

    const qty = parseFloat(item.quantity || 0);
    const lineTotal = qty > 0 ? (qty * item.cost) : 0;
    const lineTotalSpan = document.getElementById(`line_total_${rowId}`);
    if (lineTotalSpan) {
        lineTotalSpan.innerText = lineTotal > 0 ? lineTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '0.00';
    }

    updateSummaryCalculations();
}

// Hide product row
function hideRow(rowId) {
    const numericId = parseInt(rowId);
    if (numericId > 0) {
        hiddenProductIds.add(numericId);
        const item = loadedProducts.find(p => p.id === numericId);
        if (item) item.is_hidden = true;
    }
    applyTableFilters();
    updateSummaryCalculations();
}

// Restore product row
function restoreRow(rowId) {
    const numericId = parseInt(rowId);
    if (numericId > 0) {
        hiddenProductIds.delete(numericId);
        const item = loadedProducts.find(p => p.id === numericId);
        if (item) item.is_hidden = false;
    }
    applyTableFilters();
    updateSummaryCalculations();
}

// Restore all hidden products
function restoreAllHidden() {
    hiddenProductIds.clear();
    loadedProducts.forEach(p => p.is_hidden = false);
    applyTableFilters();
    updateSummaryCalculations();
}

// Remove locally added new product row
function removeNewProductRow(tempId) {
    newProductsList = newProductsList.filter(p => p.temp_id !== tempId);
    renderTableRows();
}

// Find item helper
function findItemById(rowId) {
    if (String(rowId).startsWith('new_')) {
        return newProductsList.find(p => p.temp_id === rowId);
    }
    const numId = parseInt(rowId);
    return loadedProducts.find(p => p.id === numId);
}

// Realtime Search & Filtering Engine (Requirement 2)
function applyTableFilters() {
    const q = (document.getElementById('bulkTableSearch')?.value || '').toLowerCase().trim();
    const filterBrand = document.getElementById('filterBrand')?.value || '';
    const filterCat = document.getElementById('filterCategory')?.value || '';
    const filterStock = document.getElementById('filterStock')?.value || 'all';
    const filterStatus = document.getElementById('filterStatus')?.value || 'all';

    const allItems = [...newProductsList, ...loadedProducts];
    let hiddenCount = 0;

    allItems.forEach(p => {
        const rowId = p.is_new ? p.temp_id : p.id;
        const tr = document.getElementById(`row_${rowId}`);
        if (!tr) return;

        const isHidden = p.is_new ? p.is_hidden : hiddenProductIds.has(p.id);
        if (isHidden) hiddenCount++;

        let matchesSearch = true;
        if (q) {
            const haystack = `${p.name} ${p.barcode} ${p.sku} ${p.brand_name} ${p.category_name} ${p.cost} ${p.selling_price}`.toLowerCase();
            matchesSearch = haystack.includes(q);
        }

        let matchesBrand = !filterBrand || p.brand_name === filterBrand;
        let matchesCat = !filterCat || p.category_name === filterCat;

        let matchesStock = true;
        if (filterStock === 'in_stock') matchesStock = p.current_stock > 0;
        else if (filterStock === 'low_stock') matchesStock = p.current_stock > 0 && p.current_stock <= 10;
        else if (filterStock === 'out_of_stock') matchesStock = p.current_stock <= 0;

        let matchesStatus = true;
        const hasQty = parseFloat(p.quantity || 0) > 0;
        if (filterStatus === 'with_qty') matchesStatus = hasQty && !isHidden;
        else if (filterStatus === 'without_qty') matchesStatus = !hasQty && !isHidden;
        else if (filterStatus === 'new_products') matchesStatus = p.is_new && !isHidden;
        else if (filterStatus === 'existing_products') matchesStatus = !p.is_new && !isHidden;
        else if (filterStatus === 'has_expiry') matchesStatus = p.has_expiry && !isHidden;
        else if (filterStatus === 'hidden') matchesStatus = isHidden;
        else {
            matchesStatus = !isHidden;
        }

        if (matchesSearch && matchesBrand && matchesCat && matchesStock && matchesStatus) {
            tr.style.display = '';
        } else {
            tr.style.display = 'none';
        }
    });

    const restoreBtn = document.getElementById('btnRestoreAll');
    const hiddenCountBadge = document.getElementById('hiddenCountBadge');
    if (restoreBtn && hiddenCountBadge) {
        hiddenCountBadge.innerText = hiddenCount;
        restoreBtn.style.display = hiddenCount > 0 ? 'inline-flex' : 'none';
    }
}

// Reset all filters
function resetTableFilters() {
    document.getElementById('bulkTableSearch').value = '';
    document.getElementById('filterBrand').value = '';
    document.getElementById('filterCategory').value = '';
    document.getElementById('filterStock').value = 'all';
    document.getElementById('filterStatus').value = 'all';
    applyTableFilters();
}

// Live Summary Calculations
function updateSummaryCalculations() {
    const allItems = [...newProductsList, ...loadedProducts];
    let totalProds = 0;
    let selectedCount = 0;
    let totalQty = 0;
    let actualGrnTotal = 0.00;

    allItems.forEach(p => {
        const isHidden = p.is_new ? p.is_hidden : hiddenProductIds.has(p.id);
        if (isHidden) return;

        totalProds++;
        const qty = parseFloat(p.quantity || 0);
        if (qty > 0) {
            selectedCount++;
            totalQty += qty;
            actualGrnTotal += (qty * parseFloat(p.cost || 0));
        }
    });

    const billedAmt = parseFloat(document.getElementById('supplierInvoiceAmountInput')?.value || 0);
    const variance = billedAmt - actualGrnTotal;

    document.getElementById('summaryTotalProds').innerText = totalProds;
    document.getElementById('summarySelectedProds').innerText = selectedCount;
    document.getElementById('summaryTotalQty').innerText = totalQty;
    document.getElementById('summaryActualTotal').innerText = 'LKR ' + actualGrnTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('summaryBilledTotal').innerText = 'LKR ' + billedAmt.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

    const varSpan = document.getElementById('summaryVariance');
    varSpan.innerText = (variance >= 0 ? '+' : '') + variance.toFixed(2);
    if (Math.abs(variance) < 0.01) {
        varSpan.style.color = '#15803d';
    } else {
        varSpan.style.color = '#dc2626';
    }
}

// Focus First Visible Row
function focusFirstVisibleRow() {
    const trs = Array.from(document.querySelectorAll('#bulkTableBody tr')).filter(tr => tr.style.display !== 'none');
    if (trs.length > 0) {
        const firstQty = trs[0].querySelector('.cell-input-qty');
        if (firstQty) {
            firstQty.focus();
            firstQty.select();
        }
    }
}

// ── KEYBOARD NAVIGATION RULES (Requirement 6) ──
// Enter: Move DOWN to next row in the same column
// Tab: Move RIGHT to next column in the same row

function handleCostKeydown(e, rowId) {
    if (e.key === 'Enter') {
        e.preventDefault();
        // Move DOWN to next visible row's COST input
        moveVertical('cost', rowId, 1);
    } else if (e.key === 'Tab' && !e.shiftKey) {
        e.preventDefault();
        // Move RIGHT to Margin (%) input of the same row
        const marginInput = document.getElementById(`margin_${rowId}`);
        if (marginInput) {
            marginInput.focus();
            marginInput.select();
        }
    }
}

function handleMarginKeydown(e, rowId) {
    if (e.key === 'Enter') {
        e.preventDefault();
        // Move DOWN to next visible row's MARGIN input
        moveVertical('margin', rowId, 1);
    } else if (e.key === 'Tab' && !e.shiftKey) {
        e.preventDefault();
        // Move RIGHT to Selling Price input of the same row
        const priceInput = document.getElementById(`price_${rowId}`);
        if (priceInput) {
            priceInput.focus();
            priceInput.select();
        }
    } else if (e.key === 'Tab' && e.shiftKey) {
        e.preventDefault();
        // Move LEFT to Cost Price input of the same row
        const costInput = document.getElementById(`cost_${rowId}`);
        if (costInput) {
            costInput.focus();
            costInput.select();
        }
    }
}

function handlePriceKeydown(e, rowId) {
    if (e.key === 'Enter') {
        e.preventDefault();
        // Move DOWN to next visible row's SELLING PRICE input
        moveVertical('price', rowId, 1);
    } else if (e.key === 'Tab' && !e.shiftKey) {
        e.preventDefault();
        // Move RIGHT to QTY (or Expiry Date if expiry-enabled)
        const expInput = document.getElementById(`exp_${rowId}`);
        if (expInput && expInput.offsetParent !== null) {
            expInput.focus();
        } else {
            const qtyInput = document.getElementById(`qty_${rowId}`);
            if (qtyInput) {
                qtyInput.focus();
                qtyInput.select();
            }
        }
    } else if (e.key === 'Tab' && e.shiftKey) {
        e.preventDefault();
        // Move LEFT to Margin (%) input of the same row
        const marginInput = document.getElementById(`margin_${rowId}`);
        if (marginInput) {
            marginInput.focus();
            marginInput.select();
        }
    }
}

function handleExpiryKeydown(e, rowId) {
    if (e.key === 'Enter') {
        e.preventDefault();
        // Move DOWN to next visible row's EXPIRY input
        moveVertical('exp', rowId, 1);
    } else if (e.key === 'Tab' && !e.shiftKey) {
        e.preventDefault();
        // Move RIGHT to QTY of same row
        const qtyInput = document.getElementById(`qty_${rowId}`);
        if (qtyInput) {
            qtyInput.focus();
            qtyInput.select();
        }
    }
}

function handleQtyKeydown(e, rowId) {
    if (e.key === 'Enter') {
        e.preventDefault();
        // Move DOWN to next visible row's QTY input
        moveVertical('qty', rowId, 1);
    }
}

// Helper: Move Focus Vertically (Down/Up) across visible table rows
function moveVertical(fieldPrefix, currentRowId, direction = 1) {
    const visibleTrs = Array.from(document.querySelectorAll('#bulkTableBody tr')).filter(tr => tr.style.display !== 'none');
    const currentIndex = visibleTrs.findIndex(tr => tr.id === `row_${currentRowId}`);

    if (currentIndex !== -1) {
        const targetIndex = currentIndex + direction;
        if (targetIndex >= 0 && targetIndex < visibleTrs.length) {
            const nextRow = visibleTrs[targetIndex];
            const nextInput = nextRow.querySelector(`[id^="${fieldPrefix}_"]`);
            if (nextInput) {
                nextInput.focus();
                if (typeof nextInput.select === 'function' && nextInput.type !== 'date') {
                    nextInput.select();
                }
            }
        }
    }
}

// Submit Bulk GRN (AJAX / Atomic)
async function submitBulkGrn(approveImmediately = false) {
    const suppSelect = document.getElementById('bulkSupplierSelect');
    const supplierId = parseInt(suppSelect ? suppSelect.value : 0);
    const invoiceNo = (document.getElementById('supplierInvoiceNoInput')?.value || '').trim();
    const invoiceDate = document.getElementById('supplierInvoiceDateInput')?.value || '';
    const paymentDueDate = document.getElementById('paymentDueDateInput')?.value || '';
    const receivedDate = document.getElementById('receivedDateInput')?.value || '';
    const invoiceAmt = parseFloat(document.getElementById('supplierInvoiceAmountInput')?.value || 0);
    const notes = (document.getElementById('bulkGrnNotes')?.value || '').trim();

    if (!supplierId) {
        alert('Please select a Supplier.');
        if (suppInstance) suppInstance.toggleMenu();
        return;
    }

    if (!invoiceNo) {
        alert('Supplier Invoice Number is required.');
        const invNo = document.getElementById('supplierInvoiceNoInput');
        if (invNo) { invNo.focus(); invNo.select(); }
        return;
    }

    // Collect valid existing products with qty > 0
    const existingItems = [];
    for (const p of loadedProducts) {
        if (hiddenProductIds.has(p.id)) continue;
        const qty = parseFloat(p.quantity || 0);
        if (qty > 0) {
            if (p.has_expiry && !p.expiry_date) {
                alert(`Expiry Date is required for expiry-tracked product '${p.name}'.`);
                const expInput = document.getElementById(`exp_${p.id}`);
                if (expInput) expInput.focus();
                return;
            }
            existingItems.push({
                product_id: p.id,
                quantity: qty,
                unit_cost: parseFloat(p.cost || 0),
                selling_price: parseFloat(p.selling_price || 0),
                expiry_date: p.expiry_date || null,
                batch_number: p.batch_number || null
            });
        }
    }

    // Collect valid new products with qty > 0
    const newProducts = [];
    for (const np of newProductsList) {
        const qty = parseFloat(np.quantity || 0);
        if (qty > 0) {
            if (!np.name.trim()) {
                alert('Please enter a Product Name for all new product rows with quantities.');
                return;
            }
            if (np.has_expiry && !np.expiry_date) {
                alert(`Expiry Date is required for expiry-tracked new product '${np.name}'.`);
                return;
            }
            newProducts.push({
                name: np.name.trim(),
                barcode: np.barcode.trim(),
                brand_id: parseInt(np.brand_id) || 1,
                category_id: parseInt(np.category_id) || 1,
                quantity: qty,
                unit_cost: parseFloat(np.cost || 0),
                selling_price: parseFloat(np.selling_price || 0),
                has_expiry: np.has_expiry ? 1 : 0,
                expiry_date: np.expiry_date || null,
                batch_number: np.batch_number || null
            });
        }
    }

    if (existingItems.length === 0 && newProducts.length === 0) {
        alert('Please enter a Received Quantity greater than 0 for at least one product before saving.');
        return;
    }

    // Double-submit protection: disable buttons
    const btnDraft = document.getElementById('btnSaveDraft');
    const btnApprove = document.getElementById('btnSaveApprove');
    if (btnDraft) { btnDraft.disabled = true; btnDraft.style.opacity = '0.6'; }
    if (btnApprove) { btnApprove.disabled = true; btnApprove.style.opacity = '0.6'; }

    const payload = {
        action: 'save_bulk_grn',
        supplier_id: supplierId,
        supplier_invoice_no: invoiceNo,
        supplier_invoice_date: invoiceDate,
        payment_due_date: paymentDueDate,
        received_date: receivedDate,
        supplier_invoice_amount: invoiceAmt,
        notes: notes,
        existing_items: existingItems,
        new_products: newProducts,
        approve_immediately: approveImmediately
    };

    try {
        const res = await fetch('bulk_grn_api.php?action=save_bulk_grn', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const result = await res.json();

        if (result.success) {
            window.location.href = 'grns?created=1';
        } else {
            alert('Failed to save Bulk GRN: ' + result.message);
            if (btnDraft) { btnDraft.disabled = false; btnDraft.style.opacity = '1'; }
            if (btnApprove) { btnApprove.disabled = false; btnApprove.style.opacity = '1'; }
        }
    } catch (err) {
        console.error(err);
        alert('An unexpected network error occurred while saving the Bulk GRN.');
        if (btnDraft) { btnDraft.disabled = false; btnDraft.style.opacity = '1'; }
        if (btnApprove) { btnApprove.disabled = false; btnApprove.style.opacity = '1'; }
    }
}

// Utility: HTML Escaper
function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"']/g, function(m) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
    });
}
</script>
