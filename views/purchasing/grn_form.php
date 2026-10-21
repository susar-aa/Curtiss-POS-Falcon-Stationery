<?php
/**
 * Curtiss POS - Goods Received Note (GRN) Creation & Editing Panel
 * Fixed Viewport Layout with Internally Scrollable Cart Table & Full Keyboard Navigation Loop
 */

$isEdit = !empty($grnData);
$isFromPo = !empty($poData) || (!empty($grnData['po_id']));
$nextGrnNumber = $nextGrnNumber ?? ($grnData['grn_number'] ?? '');
?>
<link rel="stylesheet" href="assets/css/curtiss_select.css">

<!-- Floating Top-Right Action Header -->
<div id="curtiss-floating-header-right" style="position: fixed; top: 16px; right: 20px; z-index: 1050; display: flex; align-items: center; gap: 8px;">
    <a href="grns" class="po-top-btn po-btn-ghost">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Cancel
    </a>
    <button type="button" class="po-top-btn po-btn-primary" onclick="submitGrnForm()" id="grnSaveBtn">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
        <?= $isEdit ? 'Update GRN (Draft)' : 'Save GRN (Draft)' ?>
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
.po-ac-item.active .po-ac-sell { color: #86efac !important; }

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
    grid-template-columns: 1fr 400px;
    gap: 12px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 8px 12px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
}
.po-upper-grid {
    display: grid;
    grid-template-columns: 36% 64%;
    gap: 12px;
    flex-shrink: 0;
}
.po-invoice-grid {
    display: grid;
    grid-template-columns: 1fr 1.15fr 0.9fr 0.95fr 0.9fr 1.1fr;
    gap: 8px;
    align-items: center;
}
@media (max-width: 1200px) {
    .po-invoice-grid {
        grid-template-columns: 1fr 1fr 1fr !important;
    }
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
    .po-invoice-grid {
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
            <h1 class="prod-title" style="margin: 0; font-size: 1.25rem; font-weight: 800; color: #0f172a;">
                <?= $isEdit ? 'Edit Goods Received Note (GRN)' : 'Goods Received Note (GRN)' ?>
            </h1>
            <?php if ($isEdit): ?>
                <span style="font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; background: #fef3c7; color: #92400e; padding: 3px 8px; border-radius: 999px;">
                    EDITING DRAFT: <?= View::escape($grnData['grn_number']) ?>
                </span>
            <?php else: ?>
                <span style="font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; background: #dcfce7; color: #15803d; padding: 3px 8px; border-radius: 999px;">
                    STOCK RECEIVING PANEL
                </span>
            <?php endif; ?>
            <?php if ($isFromPo): ?>
                <span style="font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; background: #e0f2fe; color: #0369a1; padding: 3px 8px; border-radius: 999px;">
                    LINKED PO: <?= View::escape($poData['po_number'] ?? $grnData['po_number'] ?? '') ?>
                </span>
            <?php endif; ?>
        </div>
        <div style="font-size: 0.75rem; color: #64748b; font-weight: 600;">
            Receiving Branch: <strong style="color: #0f172a;"><?= View::escape($activeBranchName) ?></strong>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (!empty($errorMsg)): ?>
        <div style="flex-shrink: 0; padding: 8px 12px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; color: #991b1b; font-size: 0.8rem; font-weight: 600; margin-bottom: 6px;">
            ✕ <?= View::escape($errorMsg) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= $isEdit ? 'grn_edit?id=' . (int)$grnData['id'] : 'grn_create' ?>" id="grnForm" style="display: flex; flex-direction: column; flex: 1; min-height: 0;">
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int)$grnData['id'] ?>">
        <?php endif; ?>
        <?php if ($isFromPo && !empty($poData)): ?>
            <input type="hidden" name="po_id" value="<?= (int)$poData['id'] ?>">
        <?php elseif ($isEdit && !empty($grnData['po_id'])): ?>
            <input type="hidden" name="po_id" value="<?= (int)$grnData['po_id'] ?>">
        <?php endif; ?>

        <!-- Upper Grid: Supplier Card & Invoice/Receiving Schedule Card -->
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
                        <?php 
                        $selectedSuppId = $isEdit ? (int)$grnData['supplier_id'] : ($isFromPo ? (int)($poData['supplier_id'] ?? 0) : 0);
                        ?>
                        <select name="supplier_id" id="grnSupplierSelect" class="curtiss-select-enhance" data-create-type="supplier" onchange="onSupplierChange(this.value)" required <?= ($isFromPo && !$isEdit) ? 'style="pointer-events: none; opacity: 0.9;"' : '' ?>>
                            <option value="">-- Select Supplier --</option>
                            <?php foreach ($suppliers as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= ($selectedSuppId === (int)$s['id']) ? 'selected' : '' ?>>
                                    <?= View::escape($s['name']) ?>
                                </option>
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

            <!-- Invoice & Receiving Schedule Card -->
            <div class="po-card">
                <div class="po-card-strip">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <span>INVOICE & RECEIVING DETAILS</span>
                    </div>
                    <span style="font-size: 10px; font-weight: 700; color: #94a3b8;">PROCUREMENT INVOICE</span>
                </div>
                <div class="po-card-body" style="padding: 10px 14px;">
                    <div class="po-invoice-grid">
                        <div>
                            <label class="po-field-label">GRN NUMBER</label>
                            <div style="font-size: 0.84rem; font-weight: 800; color: #0284c7; background: #f0f9ff; padding: 6px 8px; border-radius: 8px; border: 1px solid #bae6fd; font-family: monospace; display: flex; align-items: center; gap: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                <span><?= View::escape($isEdit ? $grnData['grn_number'] : $nextGrnNumber) ?></span>
                            </div>
                        </div>

                        <div>
                            <label class="po-field-label">SUPPLIER INVOICE NO *</label>
                            <input type="text" name="supplier_invoice_no" id="supplierInvoiceNoInput" class="po-input" placeholder="e.g. INV-10029" value="<?= View::escape($isEdit ? $grnData['supplier_invoice_no'] : '') ?>" required autofocus>
                        </div>

                        <div>
                            <label class="po-field-label">INVOICE DATE</label>
                            <input type="date" name="supplier_invoice_date" id="supplierInvoiceDateInput" class="po-input" value="<?= View::escape($isEdit ? ($grnData['supplier_invoice_date'] ?? date('Y-m-d')) : date('Y-m-d')) ?>">
                        </div>

                        <div>
                            <label class="po-field-label">PAYMENT DUE DATE</label>
                            <input type="date" name="payment_due_date" id="paymentDueDateInput" class="po-input" value="<?= View::escape($isEdit ? ($grnData['payment_due_date'] ?? '') : '') ?>" title="Supplier payment due date (Optional)">
                        </div>

                        <div>
                            <label class="po-field-label">RECEIVED DATE *</label>
                            <input type="date" name="received_date" id="receivedDateInput" class="po-input" value="<?= View::escape($isEdit ? $grnData['received_date'] : date('Y-m-d')) ?>" required>
                        </div>

                        <div>
                            <label class="po-field-label">BILLED INVOICE (LKR) *</label>
                            <input type="number" step="0.01" min="0" name="supplier_invoice_amount" id="supplierInvoiceAmountInput" class="po-input" value="<?= $isEdit ? number_format((float)$grnData['supplier_invoice_amount'], 2, '.', '') : ($isFromPo ? number_format((float)$poData['net_amount'], 2, '.', '') : '0.00') ?>" style="font-family: monospace; font-weight: 700;" required oninput="recalcCartTotals()">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Catalog Product Search Bar -->
        <div class="po-search-wrap">
            <svg class="po-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="catalogSearchInput" class="po-search-input" placeholder="Search catalog by product name, SKU, item code, or barcode... (Press '/' or 'F2' to focus, ↑ / ↓ to navigate, Enter to add)" autocomplete="off">
            <!-- Autocomplete Dropdown Menu -->
            <div id="catalogSearchDropdown" class="po-ac-menu"></div>
        </div>

        <!-- Cart Table Panel (Only Table Rows Scroll) -->
        <div class="po-cart-panel">
            <div class="po-cart-scroll">
                <table class="po-cart-table" id="grnCartTable">
                    <thead>
                        <tr>
                            <th style="width: 36px; text-align: center;">#</th>
                            <th style="width: 28%; text-align: left;">PRODUCT / VARIATION</th>
                            <th style="width: 14%; text-align: left;">EXPIRY & BATCH</th>
                            <th style="width: 10%; text-align: center;">RECEIVED QTY</th>
                            <th style="width: 14%; text-align: right;">UNIT COST (LKR)</th>
                            <th style="width: 14%; text-align: right;">SELLING PRICE (LKR)</th>
                            <th style="width: 16%; text-align: right;">LINE TOTAL (LKR)</th>
                            <th style="width: 38px; text-align: center;">ACTION</th>
                        </tr>
                    </thead>
                    <tbody id="grnCartTableBody">
                        <!-- Empty State Row -->
                        <tr id="emptyCartRow">
                            <td colspan="7" style="text-align: center; padding: 45px 12px; color: #94a3b8;">
                                <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.8"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                                    <div style="font-size: 0.88rem; font-weight: 700; color: #64748b;">No items added to this GRN yet.</div>
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
            <div style="display: flex; gap: 12px; align-items: flex-start;">
                <div style="flex: 1;">
                    <label class="po-field-label">GRN REMARKS & RECEIVING NOTES</label>
                    <textarea name="notes" id="grnNotesTextarea" class="po-notes-textarea" placeholder="Condition of received goods, delivery note number, internal remarks..."><?= View::escape($isEdit ? ($grnData['notes'] ?? '') : ($isFromPo ? ($poData['notes'] ?? '') : '')) ?></textarea>
                </div>
                <div style="width: 180px;">
                    <label class="po-field-label">VARIANCE REASON</label>
                    <?php 
                    $currDiffReason = $isEdit ? ($grnData['difference_reason'] ?? 'None') : 'None';
                    $diffOptions = [
                        'None' => 'None (Balanced)',
                        'Price Variance' => 'Price Variance',
                        'Quantity Discrepancy' => 'Quantity Discrepancy',
                        'Tax / Freight Adjustment' => 'Tax / Freight Adjustment',
                        'Damaged / Returned Goods' => 'Damaged Goods'
                    ];
                    ?>
                    <select name="difference_reason" id="diffReasonSelect" class="po-select" style="height: 48px; font-size: 0.78rem;">
                        <?php foreach ($diffOptions as $dVal => $dLabel): ?>
                            <option value="<?= $dVal ?>" <?= ($currDiffReason === $dVal) ? 'selected' : '' ?>><?= $dLabel ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Financial Variance Breakdown Box -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 12px; display: flex; flex-direction: column; justify-content: space-between;">
                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem; color: #64748b;">
                    <span>Items: <strong id="cartItemCount" style="color: #0f172a;">0</strong> (<span id="cartTotalUnits">0</span> units)</span>
                    <span style="font-weight: 600;">Actual Received Total:</span>
                    <span id="actualTotalVal" style="font-family: monospace; font-weight: 800; color: #0f172a;">LKR 0.00</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem; color: #64748b; margin-top: 2px;">
                    <span>Supplier Stated Invoice:</span>
                    <span id="invoiceTotalVal" style="font-family: monospace; font-weight: 700; color: #475569;">LKR 0.00</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 4px; border-top: 1px dashed #cbd5e1; margin-top: 2px;">
                    <span style="font-size: 0.82rem; font-weight: 800; color: #0f172a;">Variance:</span>
                    <span id="varianceVal" style="font-size: 0.95rem; font-weight: 900; color: #15803d; font-family: monospace;">LKR 0.00</span>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Expiry Date & Batch Entry Modal for Expiry Products -->
<div id="grnExpiryModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); z-index: 2500; align-items: center; justify-content: center;">
    <div style="background: #ffffff; border-radius: 14px; width: 100%; max-width: 440px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 8px 10px -6px rgba(0, 0, 0, 0.3); overflow: hidden; border: 1px solid #e2e8f0;">
        <!-- Modal Header -->
        <div style="background: #0f172a; color: #ffffff; padding: 12px 18px; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="2.2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <span style="font-size: 0.88rem; font-weight: 800; letter-spacing: 0.02em;">PRODUCT EXPIRY & BATCH</span>
            </div>
            <button type="button" onclick="closeExpiryModal()" style="background: transparent; border: none; color: #94a3b8; font-size: 20px; line-height: 1; cursor: pointer; padding: 2px 6px; border-radius: 4px;" title="Close (Esc)">✕</button>
        </div>

        <!-- Modal Body -->
        <div style="padding: 16px 20px;">
            <!-- Product Info Box -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 12px; margin-bottom: 14px;">
                <div id="modalExpiryProdName" style="font-weight: 800; font-size: 0.90rem; color: #0f172a; margin-bottom: 3px;">Product Name</div>
                <div style="display: flex; gap: 10px; font-size: 0.72rem; color: #64748b; flex-wrap: wrap;">
                    <span id="modalExpiryProdSku">SKU: —</span>
                    <span id="modalExpiryProdPrice">Cost: LKR 0.00</span>
                    <span style="background: #eff6ff; color: #2563eb; font-weight: 800; padding: 1px 6px; border-radius: 4px; border: 1px solid #bfdbfe;">EXPIRY TRACKED</span>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 12px;">
                <div>
                    <label class="po-field-label" style="display: flex; justify-content: space-between; align-items: center;">
                        <span>EXPIRY DATE <strong style="color: #ef4444;">*</strong></span>
                        <span style="font-size: 0.68rem; color: #94a3b8; font-weight: 600;">(Required)</span>
                    </label>
                    <input type="date" id="modalExpiryDateInput" class="po-input" min="<?= date('Y-m-d') ?>" required style="height: 38px; font-size: 0.88rem; font-weight: 700; border: 1.5px solid #3b82f6; background: #f0fdf4; color: #0f172a;" onkeydown="if(event.key === 'Enter'){ event.preventDefault(); event.stopPropagation(); submitExpiryModal(); }">
                    <div id="modalExpiryError" style="display: none; color: #dc2626; font-size: 0.72rem; font-weight: 700; margin-top: 4px;">Please enter a valid expiry date.</div>
                </div>

                <div>
                    <label class="po-field-label" style="display: flex; justify-content: space-between; align-items: center;">
                        <span>BATCH / LOT NUMBER</span>
                        <span style="font-size: 0.68rem; color: #94a3b8; font-weight: 600;">(Optional)</span>
                    </label>
                    <input type="text" id="modalBatchNoInput" class="po-input" placeholder="e.g. BATCH-2026-01" style="height: 36px; font-size: 0.82rem;" onkeydown="if(event.key === 'Enter'){ event.preventDefault(); event.stopPropagation(); submitExpiryModal(); }">
                </div>
            </div>

            <div style="margin-top: 14px; padding: 8px 10px; background: #eff6ff; border-radius: 6px; border: 1px solid #bfdbfe; display: flex; align-items: center; gap: 8px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                <span style="font-size: 0.73rem; color: #1e40af; font-weight: 600;">Press <kbd style="background: #ffffff; border: 1px solid #93c5fd; border-radius: 3px; padding: 1px 4px; font-family: monospace; font-size: 0.70rem;">Enter</kbd> to add to GRN list & proceed to QTY, Cost, and Selling Price.</span>
            </div>
        </div>

        <!-- Modal Footer -->
        <div style="padding: 10px 20px 14px 20px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 8px;">
            <button type="button" onclick="closeExpiryModal()" class="po-btn" style="background: #ffffff; border: 1px solid #cbd5e1; color: #64748b; font-size: 0.78rem; font-weight: 700; height: 34px; padding: 0 14px; border-radius: 6px; cursor: pointer;">Cancel (Esc)</button>
            <button type="button" onclick="submitExpiryModal()" class="po-btn-primary" style="height: 34px; padding: 0 18px; font-size: 0.80rem; font-weight: 800; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer;">
                <span>Add to GRN</span>
                <kbd style="background: rgba(255,255,255,0.25); border-radius: 3px; padding: 1px 4px; font-size: 0.68rem; font-family: monospace;">Enter ↵</kbd>
            </button>
        </div>
    </div>
</div>

<script src="assets/js/curtiss_select.js"></script>
<script>
// Master Data
const suppliersList = <?= json_encode($suppliers) ?>;
const productsList = <?= json_encode($products) ?>;
const poItems = <?= json_encode($poData['items'] ?? []) ?>;
const editGrnItems = <?= json_encode($grnData['items'] ?? []) ?>;
const isEditMode = <?= $isEdit ? 'true' : 'false' ?>;
const isFromPo = <?= $isFromPo ? 'true' : 'false' ?>;
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
    if (s.whatsapp) contactInfo.push('WA: ' + s.whatsapp);
    document.getElementById('suppContactLine').innerText = contactInfo.length ? contactInfo.join(' • ') : 'No phone recorded';
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

    // Auto-advance focus to Supplier Invoice No field
    setTimeout(() => {
        const invNo = document.getElementById('supplierInvoiceNoInput');
        if (invNo) {
            invNo.focus();
            invNo.select();
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
        const isActive = !p.status || p.status.toLowerCase() === 'active';
        if (!isActive) return false;
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
        } else {
            // Check direct exact barcode or SKU match if dropdown not actively populated
            const q = searchInput.value.trim().toLowerCase();
            if (q) {
                const directMatch = productsList.find(p => {
                    const isActive = !p.status || p.status.toLowerCase() === 'active';
                    if (!isActive) return false;
                    return (p.barcode && p.barcode.toLowerCase() === q) ||
                           (p.sku && p.sku.toLowerCase() === q);
                });
                if (directMatch) {
                    addProductToCart(directMatch);
                }
            }
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
                    <div class="po-ac-sell" style="font-size: 0.72rem; color: #16a34a; font-weight: 600;">Selling: LKR ${sell}</div>
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

let currentExpiryProduct = null;

// Helper to reliably test expiry tracking across string, int, boolean representations
function isProductExpiryTracked(prod) {
    if (!prod) return false;
    const exp = prod.has_expiry;
    return exp === 1 || exp === '1' || exp === true || exp === 'true' || parseInt(exp, 10) === 1;
}

// Add Product to Cart Table
function addProductToCart(prod) {
    if (!prod) return;
    searchMenu.classList.remove('show');
    searchInput.value = '';
    highlightedIdx = -1;

    // If product has expiry enabled, open modal to capture expiry date
    if (isProductExpiryTracked(prod)) {
        openExpiryModal(prod);
        return;
    }

    // If non-expiry item already exists in cart, increment quantity
    const existing = cartItems.find(it => it.product_id === prod.id && !it.has_expiry);
    if (existing) {
        existing.quantity += 1;
        renderCartTable();
        focusRowQty(existing.uid || existing.product_id);
        return;
    }

    const itemUid = Date.now() + '_' + Math.random().toString(36).substr(2, 5);
    cartItems.push({
        uid: itemUid,
        product_id: prod.id,
        name: prod.name,
        sku: prod.sku || '',
        has_expiry: 0,
        expiry_date: '',
        batch_number: '',
        quantity: 1,
        unit_cost: parseFloat(prod.cost || 0),
        selling_price: parseFloat(prod.selling_price || 0)
    });

    renderCartTable();
    focusRowQty(itemUid);
}

// Open Expiry & Batch Modal
function openExpiryModal(prod) {
    currentExpiryProduct = prod;
    document.getElementById('modalExpiryProdName').textContent = prod.name;
    document.getElementById('modalExpiryProdSku').textContent = 'SKU: ' + (prod.sku || '—');
    document.getElementById('modalExpiryProdPrice').textContent = 'Cost: LKR ' + parseFloat(prod.cost || 0).toFixed(2);
    
    const dateInput = document.getElementById('modalExpiryDateInput');
    const batchInput = document.getElementById('modalBatchNoInput');
    const errBox = document.getElementById('modalExpiryError');
    
    dateInput.value = '';
    batchInput.value = '';
    errBox.style.display = 'none';

    const modal = document.getElementById('grnExpiryModal');
    modal.style.display = 'flex';
    
    setTimeout(() => {
        dateInput.focus();
    }, 60);
}

// Close Expiry Modal
function closeExpiryModal() {
    const modal = document.getElementById('grnExpiryModal');
    modal.style.display = 'none';
    currentExpiryProduct = null;
    setTimeout(() => {
        if (searchInput) {
            searchInput.focus();
            searchInput.select();
        }
    }, 60);
}

// Submit Expiry Modal (Enter or Button Click)
function submitExpiryModal() {
    if (!currentExpiryProduct) return;
    
    const dateInput = document.getElementById('modalExpiryDateInput');
    const batchInput = document.getElementById('modalBatchNoInput');
    const errBox = document.getElementById('modalExpiryError');
    const expDate = dateInput.value.trim();
    const batchNo = batchInput.value.trim();

    if (!expDate) {
        errBox.textContent = 'Please enter a valid expiry date.';
        errBox.style.display = 'block';
        dateInput.focus();
        return;
    }

    errBox.style.display = 'none';

    // If item already exists in cart with same product_id and expiry_date, increment quantity
    let targetUid = '';
    const existing = cartItems.find(it => it.product_id === currentExpiryProduct.id && it.expiry_date === expDate);
    if (existing) {
        existing.quantity += 1;
        if (batchNo && !existing.batch_number) existing.batch_number = batchNo;
        targetUid = existing.uid || existing.product_id;
    } else {
        const itemUid = Date.now() + '_' + Math.random().toString(36).substr(2, 5);
        cartItems.push({
            uid: itemUid,
            product_id: currentExpiryProduct.id,
            name: currentExpiryProduct.name,
            sku: currentExpiryProduct.sku || '',
            has_expiry: 1,
            expiry_date: expDate,
            batch_number: batchNo,
            quantity: 1,
            unit_cost: parseFloat(currentExpiryProduct.cost || 0),
            selling_price: parseFloat(currentExpiryProduct.selling_price || 0)
        });
        targetUid = itemUid;
    }

    const modal = document.getElementById('grnExpiryModal');
    modal.style.display = 'none';
    currentExpiryProduct = null;

    if (searchInput) {
        searchInput.value = '';
    }

    renderCartTable();

    // Advance focus directly to that product's QTY (highlighted for fast overwrite)
    focusRowQty(targetUid);
}

// Keyboard listener for modal inputs
document.addEventListener('keydown', function(e) {
    const modal = document.getElementById('grnExpiryModal');
    if (!modal || modal.style.display === 'none') return;

    if (e.key === 'Escape') {
        e.preventDefault();
        closeExpiryModal();
    } else if (e.key === 'Enter') {
        e.preventDefault();
        submitExpiryModal();
    }
});

// Focus and highlight QTY input field so typing immediately enters new value
function focusRowQty(rowKey) {
    setTimeout(() => {
        const input = document.getElementById(`qty_input_${rowKey}`);
        if (input) {
            input.focus();
            input.select();
        }
    }, 60);
}

// QTY Enter advances to Unit Cost (highlighted)
function handleQtyKeydown(e, rowKey) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const costInput = document.getElementById(`cost_input_${rowKey}`);
        if (costInput) {
            costInput.focus();
            costInput.select();
        }
    }
}

// Unit Cost Enter advances to Selling Price (highlighted)
function handleCostKeydown(e, rowKey) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const priceInput = document.getElementById(`price_input_${rowKey}`);
        if (priceInput) {
            priceInput.focus();
            priceInput.select();
        }
    }
}

// Selling Price Enter loops back to Catalog Search Bar (ready for next product)
function handlePriceKeydown(e, rowKey) {
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
    const tbody = document.getElementById('grnCartTableBody');
    if (!cartItems.length) {
        tbody.innerHTML = `
            <tr id="emptyCartRow">
                <td colspan="8" style="text-align: center; padding: 45px 12px; color: #94a3b8;">
                    <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.8"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                        <div style="font-size: 0.88rem; font-weight: 700; color: #64748b;">No items added to this GRN yet.</div>
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
        if (!it.uid) it.uid = it.product_id + '_' + idx;
        const lineTotal = (it.quantity * it.unit_cost).toFixed(2);
        rowsHtml += `
            <tr id="cartRow_${it.uid}">
                <td style="text-align: center; color: #64748b; font-size: 0.76rem; font-weight: 700;">${idx + 1}</td>
                <td>
                    <div style="font-weight: 700; color: #0f172a; font-size: 0.83rem; display: flex; align-items: center; gap: 6px;">
                        <span>${it.name}</span>
                        ${it.has_expiry ? '<span style="font-size: 9px; font-weight: 800; background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; border-radius: 4px; padding: 1px 4px;">EXP</span>' : ''}
                    </div>
                    <div style="font-size: 0.70rem; color: #64748b; font-family: monospace;">SKU: ${it.sku || '—'}</div>
                    <input type="hidden" name="items[${idx}][product_id]" value="${it.product_id}">
                </td>
                <td>
                    ${it.has_expiry ? `
                        <div style="display: flex; flex-direction: column; gap: 3px;">
                            <input type="date" name="items[${idx}][expiry_date]" id="exp_input_${it.uid}" value="${it.expiry_date || ''}" required min="<?= date('Y-m-d') ?>" style="width: 110px; height: 26px; font-size: 0.72rem; border: 1px solid #93c5fd; border-radius: 5px; padding: 0 4px; background: #eff6ff; color: #1e40af; font-weight: 700;" onchange="updateItemExpiry('${it.uid}', this.value)" onkeydown="if(event.key === 'Enter'){ event.preventDefault(); focusRowQty('${it.uid}'); }" title="Expiry Date (Required)">
                            <input type="text" name="items[${idx}][batch_number]" id="batch_input_${it.uid}" placeholder="Batch #" value="${it.batch_number || ''}" style="width: 110px; height: 24px; font-size: 0.70rem; border: 1px solid #cbd5e1; border-radius: 5px; padding: 0 6px;" onchange="updateItemBatch('${it.uid}', this.value)" onkeydown="if(event.key === 'Enter'){ event.preventDefault(); focusRowQty('${it.uid}'); }">
                        </div>
                    ` : `
                        <span style="display: inline-block; padding: 2px 6px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; font-size: 0.70rem; color: #94a3b8;">No Expiry</span>
                        <input type="hidden" name="items[${idx}][expiry_date]" value="">
                        <input type="hidden" name="items[${idx}][batch_number]" value="">
                    `}
                </td>
                <td style="text-align: center;">
                    <input type="number" name="items[${idx}][quantity]" id="qty_input_${it.uid}" min="0.01" step="any" value="${it.quantity}" style="width: 75px; height: 30px; text-align: center; border: 1px solid #cbd5e1; border-radius: 6px; font-weight: 700; font-family: monospace; font-size: 0.82rem;" oninput="updateItemQty('${it.uid}', this.value)" onkeydown="handleQtyKeydown(event, '${it.uid}')">
                </td>
                <td style="text-align: right;">
                    <input type="number" name="items[${idx}][unit_cost]" id="cost_input_${it.uid}" min="0" step="0.01" value="${it.unit_cost.toFixed(2)}" style="width: 100px; height: 30px; text-align: right; border: 1px solid #cbd5e1; border-radius: 6px; font-weight: 700; font-family: monospace; font-size: 0.82rem; padding: 0 6px;" oninput="updateItemCost('${it.uid}', this.value)" onkeydown="handleCostKeydown(event, '${it.uid}')">
                </td>
                <td style="text-align: right;">
                    <input type="number" name="items[${idx}][selling_price]" id="price_input_${it.uid}" min="0" step="0.01" value="${it.selling_price.toFixed(2)}" style="width: 100px; height: 30px; text-align: right; border: 1px solid #cbd5e1; border-radius: 6px; font-weight: 700; font-family: monospace; font-size: 0.82rem; padding: 0 6px;" oninput="updateItemPrice('${it.uid}', this.value)" onkeydown="handlePriceKeydown(event, '${it.uid}')">
                </td>
                <td style="text-align: right; font-weight: 800; font-family: monospace; color: #0f172a; font-size: 0.85rem;" id="lineTotal_${it.uid}">
                    ${lineTotal}
                </td>
                <td style="text-align: center;">
                    <button type="button" class="del-row-btn" onclick="removeCartItem('${it.uid}')" title="Remove Item">✕</button>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = rowsHtml;
    recalcCartTotals();
}

function updateItemQty(rowKey, val) {
    const it = cartItems.find(i => i.uid === rowKey || i.product_id == rowKey);
    if (it) {
        it.quantity = Math.max(0, parseFloat(val) || 0);
        const lineTotalSpan = document.getElementById(`lineTotal_${it.uid}`);
        if (lineTotalSpan) {
            lineTotalSpan.innerText = (it.quantity * it.unit_cost).toFixed(2);
        }
        recalcCartTotals();
    }
}

function updateItemCost(rowKey, val) {
    const it = cartItems.find(i => i.uid === rowKey || i.product_id == rowKey);
    if (it) {
        it.unit_cost = Math.max(0, parseFloat(val) || 0);
        const lineTotalSpan = document.getElementById(`lineTotal_${it.uid}`);
        if (lineTotalSpan) {
            lineTotalSpan.innerText = (it.quantity * it.unit_cost).toFixed(2);
        }
        recalcCartTotals();
    }
}

function updateItemPrice(rowKey, val) {
    const it = cartItems.find(i => i.uid === rowKey || i.product_id == rowKey);
    if (it) {
        it.selling_price = Math.max(0, parseFloat(val) || 0);
    }
}

function updateItemExpiry(rowKey, val) {
    const it = cartItems.find(i => i.uid === rowKey || i.product_id == rowKey);
    if (it) {
        it.expiry_date = val;
    }
}

function updateItemBatch(rowKey, val) {
    const it = cartItems.find(i => i.uid === rowKey || i.product_id == rowKey);
    if (it) {
        it.batch_number = val;
    }
}

function removeCartItem(rowKey) {
    cartItems = cartItems.filter(i => i.uid !== rowKey && i.product_id != rowKey);
    renderCartTable();
}

// Calculate Summary Totals & Invoice Variance
function recalcCartTotals() {
    let actualTotal = 0;
    let totalUnits = 0;

    cartItems.forEach(it => {
        actualTotal += (it.quantity * it.unit_cost);
        totalUnits += it.quantity;
    });

    const invoiceInput = document.getElementById('supplierInvoiceAmountInput');
    const invoiceAmount = parseFloat(invoiceInput ? invoiceInput.value : 0) || 0;
    const variance = invoiceAmount - actualTotal;

    document.getElementById('cartItemCount').innerText = cartItems.length;
    document.getElementById('cartTotalUnits').innerText = Math.round(totalUnits);
    document.getElementById('actualTotalVal').innerText = 'LKR ' + actualTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('invoiceTotalVal').innerText = 'LKR ' + invoiceAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

    const varianceEl = document.getElementById('varianceVal');
    const diffReasonEl = document.getElementById('diffReasonSelect');

    if (Math.abs(variance) < 0.005) {
        varianceEl.style.color = '#15803d';
        varianceEl.innerText = '✓ LKR 0.00 (Balanced)';
        if (diffReasonEl && diffReasonEl.value === 'Price Variance') {
            diffReasonEl.value = 'None';
        }
    } else {
        varianceEl.style.color = '#dc2626';
        varianceEl.innerText = (variance > 0 ? '+' : '') + 'LKR ' + variance.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        if (diffReasonEl && diffReasonEl.value === 'None') {
            diffReasonEl.value = 'Price Variance';
        }
    }
}

// Form Submission with Double Submit Protection
let isSubmitting = false;
function submitGrnForm() {
    if (isSubmitting) return;

    const suppSelect = document.getElementById('grnSupplierSelect');
    if (!suppSelect.value) {
        alert('Please select a supplier.');
        suppSelect.focus();
        return;
    }

    const invNo = document.getElementById('supplierInvoiceNoInput');
    if (!invNo.value.trim()) {
        alert('Please enter the Supplier Invoice Number.');
        invNo.focus();
        return;
    }

    if (!cartItems.length) {
        alert('Please add at least one product item to the Goods Received Note.');
        searchInput.focus();
        return;
    }

    // Validate expiry dates for items that require expiry
    for (let it of cartItems) {
        if (it.has_expiry && !it.expiry_date) {
            alert(`Expiry Date is required for expiry-tracked product: "${it.name}"`);
            const expInput = document.getElementById(`exp_input_${it.uid}`) || document.getElementById(`exp_input_${it.product_id}`);
            if (expInput) expInput.focus();
            return;
        }
    }

    isSubmitting = true;
    const saveBtn = document.getElementById('grnSaveBtn');
    if (saveBtn) {
        saveBtn.disabled = true;
        saveBtn.innerText = 'Saving GRN...';
    }

    const form = document.getElementById('grnForm');
    form.submit();
}

// Global Keyboard Shortcuts
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
        submitGrnForm();
    }
    // Close dropdown on click outside
    if (e.key === 'Escape') {
        searchMenu.classList.remove('show');
    }
});

// Click outside search menu closes it
document.addEventListener('click', (e) => {
    if (!e.target.closest('.po-search-wrap')) {
        searchMenu.classList.remove('show');
    }
});

// Initialize Searchable Select & Continuous Keyboard Navigation Loop
document.addEventListener('DOMContentLoaded', () => {
    const suppSelect = document.getElementById('grnSupplierSelect');
    let suppInstance = null;

    if (suppSelect && window.CurtissSearchableSelect) {
        suppInstance = CurtissSearchableSelect.attach(suppSelect);
    }

    // Pre-populate if in Edit mode
    if (isEditMode && editGrnItems && editGrnItems.length > 0) {
        editGrnItems.forEach((item, idx) => {
            const prod = productsList.find(p => parseInt(p.id) === parseInt(item.product_id));
            const itemUid = Date.now() + '_' + idx + '_' + Math.random().toString(36).substr(2, 5);
            cartItems.push({
                uid: itemUid,
                product_id: parseInt(item.product_id),
                name: item.product_name || (prod ? prod.name : 'Product #' + item.product_id),
                sku: item.sku || (prod ? prod.sku : ''),
                has_expiry: parseInt(item.has_expiry || (prod ? prod.has_expiry : 0) || 0),
                expiry_date: item.expiry_date || '',
                batch_number: item.batch_number || '',
                quantity: parseFloat(item.quantity || 1),
                unit_cost: parseFloat(item.unit_cost || 0),
                selling_price: parseFloat(item.selling_price || (prod ? prod.selling_price : 0) || 0)
            });
        });
        renderCartTable();

        // Focus directly on Supplier Invoice No
        setTimeout(() => {
            const invNo = document.getElementById('supplierInvoiceNoInput');
            if (invNo) {
                invNo.focus();
                invNo.select();
            }
        }, 100);
    } else if (isFromPo && poItems && poItems.length > 0) {
        // Pre-populate if transferred directly from Purchase Order
        poItems.forEach((item, idx) => {
            const qty = parseFloat(item.quantity) || 0;
            const rec = parseFloat(item.received_quantity) || 0;
            const remaining = (qty - rec) > 0 ? (qty - rec) : qty;
            if (remaining > 0) {
                const prod = productsList.find(p => parseInt(p.id) === parseInt(item.product_id));
                const itemUid = Date.now() + '_' + idx + '_' + Math.random().toString(36).substr(2, 5);
                cartItems.push({
                    uid: itemUid,
                    product_id: parseInt(item.product_id),
                    name: item.product_name || (prod ? prod.name : 'Product #' + item.product_id),
                    sku: item.sku || (prod ? prod.sku : ''),
                    has_expiry: parseInt(item.has_expiry || (prod ? prod.has_expiry : 0) || 0),
                    expiry_date: item.expiry_date || '',
                    batch_number: item.batch_number || '',
                    quantity: remaining,
                    unit_cost: parseFloat(item.unit_cost || (prod ? prod.cost : 0) || 0),
                    selling_price: parseFloat(item.selling_price || (prod ? prod.selling_price : 0) || 0)
                });
            }
        });
        renderCartTable();

        // Focus directly on Supplier Invoice No for fast entry
        setTimeout(() => {
            const invNo = document.getElementById('supplierInvoiceNoInput');
            if (invNo) {
                invNo.focus();
                invNo.select();
            }
        }, 100);
    } else {
        // Direct GRN: Auto-open supplier combobox and focus its search input immediately on page load
        setTimeout(() => {
            if (suppInstance) {
                suppInstance.toggleMenu();
                if (suppInstance.searchInput) {
                    suppInstance.searchInput.focus();
                }
            }
        }, 80);
    }

    // Trigger onSupplierChange if supplier is already selected (e.g. from PO or Edit)
    if (suppSelect && suppSelect.value) {
        onSupplierChange(suppSelect.value);
    }

    // Supplier Invoice No Enter Key advances to Invoice Date
    const invNoEl = document.getElementById('supplierInvoiceNoInput');
    if (invNoEl) {
        invNoEl.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const invDate = document.getElementById('supplierInvoiceDateInput');
                if (invDate) invDate.focus();
            }
        });
    }

    // Invoice Date Enter Key advances to Payment Due Date
    const invDateEl = document.getElementById('supplierInvoiceDateInput');
    if (invDateEl) {
        invDateEl.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const dueDate = document.getElementById('paymentDueDateInput');
                if (dueDate) dueDate.focus();
            }
        });
    }

    // Payment Due Date Enter Key advances to Received Date
    const dueDateEl = document.getElementById('paymentDueDateInput');
    if (dueDateEl) {
        dueDateEl.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const recvDate = document.getElementById('receivedDateInput');
                if (recvDate) recvDate.focus();
            }
        });
    }

    // Received Date Enter Key advances to Stated Invoice Amount
    const recvDateEl = document.getElementById('receivedDateInput');
    if (recvDateEl) {
        recvDateEl.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const invAmt = document.getElementById('supplierInvoiceAmountInput');
                if (invAmt) {
                    invAmt.focus();
                    invAmt.select();
                }
            }
        });
    }

    // Stated Invoice Amount Enter Key advances directly to Catalog Search Bar
    const invAmtEl = document.getElementById('supplierInvoiceAmountInput');
    if (invAmtEl) {
        invAmtEl.addEventListener('keydown', (e) => {
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
