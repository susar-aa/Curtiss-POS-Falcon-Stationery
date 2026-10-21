<?php
/**
 * Curtiss POS - Barcode Label Printing View
 * Optimized for TSC TE244 Thermal Label Printer (25mm x 40mm, 2 labels per row)
 * Features: Fixed Viewport, Zebra Strips, 48px Rows, Keyboard-Driven Queue, Vector SVG Barcodes.
 */

$canConfigure = Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN);
?>

<link rel="stylesheet" href="assets/css/curtiss_select.css">
<script src="assets/js/curtiss_select.js"></script>
<script src="assets/js/code128.js"></script>

<!-- Floating Top-Right Action Controls -->
<div id="curtiss-floating-header-right" class="no-print" style="position: fixed; top: 16px; right: 20px; z-index: 1050; display: flex; align-items: center; gap: 8px;">
    <button type="button" class="inv-btn inv-btn-ghost" onclick="openSettingsModal()" title="Printer & Label Settings">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2v-.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
        Printer Config
    </button>

    <button type="button" class="inv-btn inv-btn-ghost" onclick="openTsplModal()" title="Export TSPL-II Raw Commands">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
        Export TSPL
    </button>

    <button type="button" class="inv-btn inv-btn-ghost" onclick="openPreviewModal()" title="Preview Label Sheet">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        Preview
    </button>

    <button type="button" class="inv-btn inv-btn-primary" onclick="triggerDirectPrint()" id="btnHeaderPrint" style="background: #0f172a;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
        Print Labels (TSC TE244)
    </button>
</div>

<style>
/* ── Theme Shared Standards ── */
.inv-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    height: 34px;
    min-height: 34px;
    padding: 0 14px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    border: 1px solid transparent;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.18s ease;
    white-space: nowrap;
    box-sizing: border-box;
}
.inv-btn-primary {
    background: #0f172a;
    color: #ffffff;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.2);
}
.inv-btn-primary:hover {
    background: #1e293b;
    transform: translateY(-1px);
}
.inv-btn-ghost {
    background: #ffffff;
    color: #0f172a;
    border-color: #e2e8f0;
}
.inv-btn-ghost:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
}

/* ── Typography & Header ── */
.inv-title {
    font-size: 1.35rem;
    font-weight: 900;
    color: #0f172a;
    letter-spacing: -0.02em;
    margin: 0;
}

/* ── Data Table Panel & 48px Rows with Zebra Striping ── */
.prod-table-panel {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
}
.prod-table-scroll {
    overflow-y: auto;
    flex: 1;
}
.prod-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 12.5px;
    color: #1e293b;
}
.prod-table thead th {
    height: 40px;
    padding: 8px 14px;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #64748b;
    background: #f8fafc;
    position: sticky;
    top: 0;
    z-index: 10;
    border-bottom: 1.5px solid #e2e8f0;
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
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
    box-sizing: border-box;
}

.col-right  { text-align: right; }
.col-center { text-align: center; }

/* ── In-Cell Action Buttons ── */
.prod-act-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    height: 28px;
    min-height: 28px;
    line-height: 26px;
    padding: 0 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    color: #0f172a;
    cursor: pointer;
    transition: all 0.15s ease;
    white-space: nowrap;
    box-sizing: border-box;
    text-decoration: none;
}
.prod-act-btn:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
}
.prod-act-delete {
    color: #dc2626;
    background: #fef2f2;
    border-color: #fca5a5;
}
.prod-act-delete:hover {
    background: #fee2e2;
    border-color: #f87171;
}

/* ── Form Controls & Autocomplete ── */
.form-label {
    display: block;
    font-size: 10.5px;
    font-weight: 800;
    text-transform: uppercase;
    color: #475569;
    letter-spacing: 0.05em;
    margin-bottom: 5px;
}
.form-control {
    width: 100%;
    height: 36px;
    padding: 0 12px;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    font-size: 12.5px;
    color: #0f172a;
    box-sizing: border-box;
    background: #ffffff;
    transition: border 0.15s ease, box-shadow 0.15s ease;
}
.form-control:focus {
    outline: none;
    border-color: #0f172a;
    box-shadow: 0 0 0 2px rgba(15, 23, 42, 0.1);
}

/* ── Autocomplete Dropdown List ── */
.autocomplete-container {
    position: relative;
    width: 100%;
}
.autocomplete-list {
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    right: 0;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 12px;
    box-shadow: 0 12px 32px rgba(15, 23, 42, 0.15);
    z-index: 1000;
    max-height: 280px;
    overflow-y: auto;
    display: none;
}
.autocomplete-list.active {
    display: block;
}
.autocomplete-item {
    padding: 10px 14px;
    border-bottom: 1px solid #f1f5f9;
    cursor: pointer;
    transition: background 0.12s ease;
}
.autocomplete-item:last-child {
    border-bottom: none;
}
.autocomplete-item:hover, .autocomplete-item.selected {
    background: #f8fafc;
}

/* ── Modals ── */
.curtiss-modal {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(15, 23, 42, 0.5);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2000;
    padding: 16px;
    box-sizing: border-box;
    opacity: 0;
    pointer-events: none;
    transition: all 0.2s ease;
}
.curtiss-modal.active {
    opacity: 1;
    pointer-events: auto;
}
.curtiss-modal-content {
    background: #ffffff;
    border-radius: 18px;
    width: 680px;
    max-width: 95vw;
    max-height: calc(100vh - 40px);
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 48px -10px rgba(15, 23, 42, 0.3);
    overflow: hidden;
    position: relative;
    box-sizing: border-box;
}
.curtiss-modal-content form {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-height: 0;
    overflow: hidden;
    margin: 0;
}
.curtiss-modal-header {
    padding: 1rem 1.4rem;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 800;
    font-size: 1.15rem;
    color: #0f172a;
    flex-shrink: 0;
}
.curtiss-modal-body {
    padding: 1.25rem 1.4rem;
    overflow-y: auto;
    flex: 1;
    min-height: 0;
    box-sizing: border-box;
}
.curtiss-modal-footer {
    padding: 0.85rem 1.4rem;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    background: #f8fafc;
    flex-shrink: 0;
    box-sizing: border-box;
}

/* ══════════════════════════════════════════════════════════════
   TSC TE244 THERMAL LABEL PRINTING STYLES (2 LABELS PER ROW)
   Label Size: 25mm Width x 40mm Length (or rotated depending on media)
   ══════════════════════════════════════════════════════════════ */
#printLabelContainer {
    display: none;
}

@media print {
    /* Hide everything on screen */
    body * {
        visibility: hidden !important;
    }
    .no-print, header, nav, #curtiss-nav, #curtiss-floating-header-right, .curtiss-modal {
        display: none !important;
    }

    /* Page Setup: Calibrated for 2-column continuous thermal label web */
    @page {
        size: auto;
        margin: 0mm !important;
    }

    html, body {
        width: 100% !important;
        height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
        background: #ffffff !important;
        overflow: visible !important;
    }

    #printLabelContainer {
        visibility: visible !important;
        display: block !important;
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        width: 52mm !important; /* 25mm + 2mm gap + 25mm */
        margin: 0 !important;
        padding: 0 !important;
    }

    #printLabelContainer * {
        visibility: visible !important;
    }

    /* 2 Labels Per Row Grid Layout */
    .label-row {
        display: flex !important;
        flex-direction: row !important;
        width: 52mm !important;
        height: 40mm !important;
        margin-bottom: 2mm !important; /* Row gap */
        page-break-inside: avoid !important;
        page-break-after: always !important;
        box-sizing: border-box !important;
        justify-content: space-between !important;
    }

    .label-item {
        width: 25mm !important;
        height: 40mm !important;
        padding: 1.5mm 1mm !important;
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: space-between !important;
        text-align: center !important;
        box-sizing: border-box !important;
        overflow: hidden !important;
        background: #ffffff !important;
        color: #000000 !important;
        border: none !important;
    }

    .label-blank {
        width: 25mm !important;
        height: 40mm !important;
        visibility: hidden !important;
    }

    .label-shop-name {
        font-family: Arial, Helvetica, sans-serif !important;
        font-size: 7pt !important;
        font-weight: 900 !important;
        letter-spacing: 0.5px !important;
        text-transform: uppercase !important;
        line-height: 1.1 !important;
        margin: 0 !important;
        color: #000000 !important;
    }

    .label-product-name {
        font-family: Arial, Helvetica, sans-serif !important;
        font-size: 6.5pt !important;
        font-weight: 700 !important;
        line-height: 1.15 !important;
        margin: 0.5mm 0 !important;
        max-height: 6mm !important;
        overflow: hidden !important;
        word-break: break-word !important;
        color: #000000 !important;
    }

    .label-product-code {
        font-family: 'Courier New', Courier, monospace !important;
        font-size: 6pt !important;
        font-weight: 800 !important;
        line-height: 1 !important;
        margin: 0.2mm 0 !important;
        color: #000000 !important;
    }

    .label-barcode-wrapper {
        width: 100% !important;
        max-height: 16mm !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        margin: 0.5mm 0 !important;
    }

    .label-barcode-wrapper svg {
        width: 100% !important;
        height: 14mm !important;
    }

    .label-price {
        font-family: Arial, Helvetica, sans-serif !important;
        font-size: 9.5pt !important;
        font-weight: 900 !important;
        line-height: 1.1 !important;
        margin-top: 0.5mm !important;
        color: #000000 !important;
    }
}
</style>

<!-- Main Fixed Viewport Workarea -->
<div style="width: 100%; max-width: 100%; height: calc(100vh - 110px); display: flex; flex-direction: column; box-sizing: border-box; overflow: hidden; padding-bottom: 8px;">

    <!-- Page Header (Headline Only - No Subtitle) -->
    <div class="inv-header" style="flex-shrink: 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 0.85rem;">
        <div style="display: flex; align-items: center; gap: 8px;">
            <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
            <h1 class="inv-title">Barcode Label Printing</h1>
            <span style="font-size: 11px; font-weight: 800; color: #64748b; background: #f1f5f9; padding: 2px 8px; border-radius: 999px;">TSC TE244 (25&times;40mm &bull; 2 per row)</span>
        </div>

        <div style="display: flex; align-items: center; gap: 10px;">
            <span id="queueCountBadge" style="font-size: 12px; font-weight: 800; color: #0284c7; background: #e0f2fe; padding: 4px 12px; border-radius: 999px;">
                Queue: 0 labels (0 rows)
            </span>
            <a href="settings.php#barcode_printer_settings" class="inv-btn inv-btn-ghost" style="height: 32px; display: inline-flex; align-items: center; gap: 5px; text-decoration: none; font-size: 11.5px; font-weight: 700;" title="Configure TSC TE244 Printer in System Settings">
                ⚙️ Printer Settings
            </a>
            <button type="button" class="inv-btn prod-act-delete" style="height: 32px;" onclick="clearPrintQueue()" title="Clear all queued labels">
                Clear Queue
            </button>
        </div>
    </div>

    <!-- Product Selection & Fast Input Strip -->
    <div style="flex-shrink: 0; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 14px 18px; margin-bottom: 0.85rem; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
        <form id="addQueueForm" onsubmit="handleAddQueueSubmit(event)" style="margin: 0;">
            <div style="display: grid; grid-template-columns: 2.2fr 1.6fr 1.1fr 0.9fr auto; gap: 12px; align-items: flex-end;">
                
                <!-- 1. Product Search Autocomplete -->
                <div class="autocomplete-container">
                    <label class="form-label">Search Product (Name / SKU / Barcode) *</label>
                    <div style="position: relative;">
                        <input type="text" id="inputProductSearch" class="form-control" placeholder="Type to search product or scan barcode..." autocomplete="off" autofocus>
                        <span id="searchSpinner" style="position: absolute; right: 10px; top: 9px; font-size: 11px; color: #94a3b8; display: none;">Searching...</span>
                    </div>
                    <div id="productSuggestions" class="autocomplete-list"></div>
                    <input type="hidden" id="selectedProductId">
                </div>

                <!-- 2. Price Lot Dropdown -->
                <div>
                    <label class="form-label">Select Price Lot *</label>
                    <select id="selectPriceLot" class="form-control" onchange="onPriceLotChanged()" disabled style="height: 36px; min-height: 36px; border-radius: 10px;">
                        <option value="">-- Choose Product First --</option>
                    </select>
                </div>

                <!-- 3. Selling Price (Auto / Custom Override) -->
                <div>
                    <label class="form-label">Selling Price (LKR)</label>
                    <input type="number" step="0.01" min="0" id="inputSellingPrice" class="form-control" placeholder="0.00" onfocus="this.select()">
                </div>

                <!-- 4. Label Quantity -->
                <div>
                    <label class="form-label">Quantity *</label>
                    <input type="number" step="1" min="1" max="5000" id="inputLabelQty" class="form-control" value="1" onfocus="this.select()" required>
                </div>

                <!-- 5. Action Buttons: Print Now (Primary) & Add to Queue (Secondary) -->
                <div style="display: flex; gap: 8px; align-items: flex-end;">
                    <button type="button" class="inv-btn inv-btn-primary" id="btnDirectPrintNow" onclick="handleDirectPrintNow()" style="height: 36px; padding: 0 16px; font-weight: 800; background: #0f172a; color: #ffffff; white-space: nowrap; display: inline-flex; align-items: center; gap: 6px;" title="Print barcode labels directly without adding to queue">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                        Print Now
                    </button>
                    <button type="submit" class="inv-btn inv-btn-ghost" id="btnAddQueue" style="height: 36px; padding: 0 14px; font-weight: 700; white-space: nowrap; display: inline-flex; align-items: center; gap: 6px;" title="Add selected item to print queue table">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Add to Queue
                    </button>
                </div>

            </div>
        </form>
    </div>

    <!-- Main Print Queue Table Panel -->
    <div class="prod-table-panel" style="flex: 1; min-height: 0; display: flex; flex-direction: column;">
        <div class="prod-table-scroll">
            <table class="prod-table" id="queueTable">
                <thead>
                    <tr>
                        <th style="min-width: 100px; width: 110px; text-align: left;">Product Code</th>
                        <th style="min-width: 180px; text-align: left;">Product Name</th>
                        <th style="min-width: 140px; text-align: left;">Price Lot</th>
                        <th class="col-center" style="min-width: 100px; width: 110px; text-align: center;">Available Stock</th>
                        <th class="col-right" style="min-width: 110px; text-align: right;">Unit Price (LKR)</th>
                        <th class="col-center" style="min-width: 110px; width: 120px; text-align: center;">Labels Qty</th>
                        <th class="col-center" style="min-width: 90px; width: 100px; text-align: center;">Rows (Pair)</th>
                        <th class="col-right" style="min-width: 80px; width: 90px; text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody id="queueTableBody">
                    <tr>
                        <td colspan="8" style="text-align: center; color: #94a3b8; padding: 3rem; font-weight: 600;">
                            Your print queue is empty. Search products above and click "+ Add to Queue".
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Sticky Queue Summary Footer -->
        <div style="padding: 10px 18px; border-top: 1.5px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc; flex-shrink: 0;">
            <div style="font-size: 12px; color: #64748b; font-weight: 700;">
                Total Queued Items: <span id="footItemCount" style="color: #0f172a; font-weight: 900;">0</span> &bull; 
                Total Labels: <span id="footLabelCount" style="color: #16a34a; font-weight: 900;">0</span> &bull; 
                Total Sheet Rows: <span id="footRowCount" style="color: #0284c7; font-weight: 900;">0</span> (at 2 labels/row)
            </div>
            <div style="display: flex; gap: 8px;">
                <button type="button" class="inv-btn inv-btn-ghost" onclick="openPreviewModal()">
                    👁️ Preview Sheet
                </button>
                <button type="button" class="inv-btn inv-btn-primary" onclick="triggerDirectPrint()">
                    🖨️ Print Labels Now
                </button>
            </div>
        </div>
    </div>

</div>

<!-- ══════════════════════════════════════════════════════════════
     MODAL: LIVE PRINT PREVIEW
     ══════════════════════════════════════════════════════════════ -->
<div class="curtiss-modal" id="modalPreview">
    <div class="curtiss-modal-content" style="width: 620px;">
        <div class="curtiss-modal-header">
            <span>Label Sheet Live Preview (TSC TE244)</span>
            <button type="button" onclick="closeModal('modalPreview')" style="background:none; border:none; font-size:1.5rem; color:#64748b; cursor:pointer;">&times;</button>
        </div>
        <div class="curtiss-modal-body" style="background: #f1f5f9; padding: 20px;">
            <div style="text-align: center; margin-bottom: 12px; font-size: 11.5px; color: #64748b; font-weight: 700;">
                Physical Layout: 2 labels per row (25mm &times; 40mm each) &bull; Continuous Thermal Web
            </div>
            <div id="previewSheetWrapper" style="display: flex; flex-direction: column; align-items: center; gap: 8px; max-height: 440px; overflow-y: auto; padding: 10px;">
                <!-- Generated preview rows -->
            </div>
        </div>
        <div class="curtiss-modal-footer">
            <button type="button" class="inv-btn inv-btn-ghost" onclick="closeModal('modalPreview')">Close</button>
            <button type="button" class="inv-btn inv-btn-primary" onclick="triggerDirectPrint()">Print Now</button>
        </div>
    </div>
</div>



<!-- ══════════════════════════════════════════════════════════════
     MODAL: EXPORT TSPL-II RAW COMMANDS
     ══════════════════════════════════════════════════════════════ -->
<div class="curtiss-modal" id="modalTspl">
    <div class="curtiss-modal-content" style="width: 640px;">
        <div class="curtiss-modal-header">
            <span>TSC TSPL-II Raw Printer Code</span>
            <button type="button" onclick="closeModal('modalTspl')" style="background:none; border:none; font-size:1.5rem; color:#64748b; cursor:pointer;">&times;</button>
        </div>
        <div class="curtiss-modal-body" style="padding: 16px 20px;">
            <div style="font-size: 11.5px; color: #64748b; font-weight: 600; margin-bottom: 8px;">
                You can copy or download these native TSPL commands for direct raw printing over USB / Network to your TSC TE244 printer:
            </div>
            <textarea id="tsplCodeArea" class="form-control" style="height: 240px; font-family: monospace; font-size: 11.5px; background: #0f172a; color: #38bdf8; resize: none;" readonly></textarea>
        </div>
        <div class="curtiss-modal-footer">
            <button type="button" class="inv-btn inv-btn-ghost" onclick="closeModal('modalTspl')">Close</button>
            <button type="button" class="inv-btn inv-btn-ghost" onclick="copyTsplCode()">📋 Copy to Clipboard</button>
            <button type="button" class="inv-btn inv-btn-primary" onclick="downloadTsplFile()">⬇️ Download .prn</button>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     HIDDEN PRINT CONTAINER FOR BROWSER DIRECT THERMAL PRINT
     ══════════════════════════════════════════════════════════════ -->
<div id="printLabelContainer"></div>

<script>
// ── Global Queue & Configuration State ──
let printQueue = [];
let activeSettings = {
    printer_model: 'TSC TE244',
    label_width_mm: 25,
    label_height_mm: 40,
    labels_per_row: 2,
    column_gap_mm: 2,
    row_gap_mm: 2,
    shop_name: '<?= addslashes($shopName) ?>',
    price_prefix: '<?= addslashes($currency) ?>',
    show_shop_name: 1,
    show_product_code: 1,
    show_price: 1,
    barcode_height_mm: 13,
    rotation_deg: 0
};

let currentSearchResults = [];
let currentSelectedProduct = null;
let currentSelectedLot = null;
let searchDebounceTimer = null;

document.addEventListener('DOMContentLoaded', () => {
    // Attach CurtissSearchableSelect to Price Lot dropdown
    const lotSel = document.getElementById('selectPriceLot');
    if (lotSel && window.CurtissSearchableSelect) {
        CurtissSearchableSelect.attach(lotSel, {
            placeholder: '-- Choose Product First --',
            searchPlaceholder: 'Type to search lot...'
        });
    }

    loadPrinterSettings();
    initKeyboardNavigation();

    // Check if initial product ID was provided via URL query
    const initProd = <?= json_encode($initialProduct) ?>;
    if (initProd) {
        selectProduct(initProd);
    }
});

// ── 1. Settings Management ──
async function loadPrinterSettings() {
    try {
        const res = await fetch('barcode_labels_api.php?action=get_settings');
        const json = await res.json();
        if (json.success && json.settings) {
            activeSettings = Object.assign(activeSettings, json.settings);
            applySettingsToModal();
        }
    } catch (e) {
        console.error("Failed to load settings:", e);
    }
}

// ── 2. Product Search & Autocomplete ──
const searchInput = document.getElementById('inputProductSearch');
const suggestionsBox = document.getElementById('productSuggestions');
const searchSpinner = document.getElementById('searchSpinner');

searchInput.addEventListener('input', function() {
    const q = this.value.trim();
    clearTimeout(searchDebounceTimer);

    if (q.length < 1) {
        suggestionsBox.classList.remove('active');
        suggestionsBox.innerHTML = '';
        return;
    }

    searchSpinner.style.display = 'block';
    searchDebounceTimer = setTimeout(() => executeProductSearch(q), 180);
});

async function executeProductSearch(query) {
    try {
        const res = await fetch(`barcode_labels_api.php?action=search_products&q=${encodeURIComponent(query)}`);
        const json = await res.json();
        searchSpinner.style.display = 'none';

        if (json.success && json.products) {
            currentSearchResults = json.products;
            renderSuggestions(json.products);
        } else {
            suggestionsBox.classList.remove('active');
        }
    } catch (e) {
        searchSpinner.style.display = 'none';
        console.error("Search error:", e);
    }
}

function renderSuggestions(products) {
    if (products.length === 0) {
        suggestionsBox.innerHTML = '<div style="padding: 12px; text-align: center; color: #94a3b8; font-size: 12px;">No matching products found.</div>';
        suggestionsBox.classList.add('active');
        return;
    }

    let html = '';
    products.forEach((p, idx) => {
        const lotCount = p.lots ? p.lots.length : 0;
        const bc = p.barcode ? `Barcode: <strong>${escapeHtml(p.barcode)}</strong>` : 'No Barcode';
        const sku = p.sku ? `SKU: ${escapeHtml(p.sku)}` : '';
        html += `
            <div class="autocomplete-item ${idx === 0 ? 'selected' : ''}" onclick="selectProductByIndex(${idx})">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="font-weight: 800; color: #0f172a; font-size: 13px;">${escapeHtml(p.name)}</div>
                    <div style="font-weight: 800; color: #16a34a; font-size: 13px;">LKR ${p.default_price.toFixed(2)}</div>
                </div>
                <div style="display: flex; gap: 12px; font-size: 11px; color: #64748b; margin-top: 3px;">
                    <span>${sku}</span>
                    <span>${bc}</span>
                    <span style="color: #0284c7; font-weight: 700;">Stock: ${p.total_branch_stock} (${lotCount} lot${lotCount === 1 ? '' : 's'})</span>
                </div>
            </div>
        `;
    });

    suggestionsBox.innerHTML = html;
    suggestionsBox.classList.add('active');
}

function selectProductByIndex(index) {
    if (currentSearchResults[index]) {
        selectProduct(currentSearchResults[index]);
    }
}

function selectProduct(product) {
    currentSelectedProduct = product;
    document.getElementById('selectedProductId').value = product.id;
    searchInput.value = `${product.name} (${product.sku || product.barcode || ''})`;
    suggestionsBox.classList.remove('active');

    // Populate Price Lots
    populatePriceLots(product.lots || []);

    // Advance focus to lot selector or custom price
    setTimeout(() => {
        const lotSelect = document.getElementById('selectPriceLot');
        if (lotSelect && !lotSelect.disabled && lotSelect._curtissSelect) {
            lotSelect._curtissSelect.trigger.focus();
        } else {
            document.getElementById('inputSellingPrice').focus();
        }
    }, 60);
}

function populatePriceLots(lots) {
    const sel = document.getElementById('selectPriceLot');
    sel.innerHTML = '';

    if (!lots || lots.length === 0) {
        sel.innerHTML = '<option value="">Default Product Price</option>';
        sel.disabled = true;
        currentSelectedLot = null;
        if (sel._curtissSelect) {
            sel._curtissSelect.refreshOptions();
        }
        document.getElementById('inputSellingPrice').value = currentSelectedProduct ? currentSelectedProduct.default_price.toFixed(2) : '0.00';
        return;
    }

    sel.disabled = false;
    lots.forEach((lot, i) => {
        const lotNum = lot.lot_number || `Lot #${lot.lot_id}`;
        const price = parseFloat(lot.selling_price) || 0;
        const stock = parseFloat(lot.remaining_quantity) || 0;
        const exp = lot.expiry_date ? ` (Exp: ${lot.expiry_date})` : '';

        const opt = document.createElement('option');
        opt.value = i;
        opt.text = `${lotNum} — Price: LKR ${price.toFixed(2)} | Stock: ${stock}${exp}`;
        opt.dataset.price = price;
        opt.dataset.stock = stock;
        sel.appendChild(opt);
    });

    // Select first lot by default
    sel.selectedIndex = 0;
    if (sel._curtissSelect) {
        sel._curtissSelect.refreshOptions();
    }
    onPriceLotChanged();
}

function onPriceLotChanged() {
    const sel = document.getElementById('selectPriceLot');
    if (!currentSelectedProduct || !currentSelectedProduct.lots || currentSelectedProduct.lots.length === 0) {
        return;
    }

    const idx = parseInt(sel.value) || 0;
    currentSelectedLot = currentSelectedProduct.lots[idx] || null;

    if (currentSelectedLot) {
        const price = parseFloat(currentSelectedLot.selling_price) || currentSelectedProduct.default_price || 0;
        document.getElementById('inputSellingPrice').value = price.toFixed(2);
        
        // Auto suggest qty to remaining stock or 1
        const rem = parseInt(currentSelectedLot.remaining_quantity) || 1;
        if (rem > 0) {
            document.getElementById('inputLabelQty').value = rem;
        }
    }
}

// ── 3. Queue Management & Add to Queue ──
function handleAddQueueSubmit(event) {
    event.preventDefault();

    if (!currentSelectedProduct) {
        alert("Please search and select a product first.");
        searchInput.focus();
        return;
    }

    const qty = parseInt(document.getElementById('inputLabelQty').value) || 1;
    if (qty < 1) {
        alert("Please enter a valid quantity of labels (minimum 1).");
        document.getElementById('inputLabelQty').focus();
        return;
    }

    const customPriceVal = parseFloat(document.getElementById('inputSellingPrice').value);
    const lotPrice = currentSelectedLot ? (parseFloat(currentSelectedLot.selling_price) || 0) : currentSelectedProduct.default_price;
    const finalPrice = !isNaN(customPriceVal) ? customPriceVal : lotPrice;
    const isCustomPrice = Math.abs(finalPrice - lotPrice) > 0.01;

    // Barcode: prioritize product barcode, fallback to SKU or generated code
    const barcodeVal = currentSelectedProduct.barcode || currentSelectedProduct.sku || String(currentSelectedProduct.id).padStart(8, '0');

    // Add item to print queue
    printQueue.push({
        queue_id: Date.now() + Math.random(),
        product_id: currentSelectedProduct.id,
        product_name: currentSelectedProduct.name,
        product_code: currentSelectedProduct.sku || String(currentSelectedProduct.id).padStart(5, '0'),
        barcode: barcodeVal,
        lot_id: currentSelectedLot ? currentSelectedLot.lot_id : 0,
        lot_number: currentSelectedLot ? currentSelectedLot.lot_number : 'MASTER',
        available_stock: currentSelectedLot ? currentSelectedLot.remaining_quantity : currentSelectedProduct.total_branch_stock,
        lot_price: lotPrice,
        selling_price: finalPrice,
        is_custom_price: isCustomPrice,
        quantity: qty
    });

    renderQueueTable();

    // Reset inputs for fast continuous keyboard loop back to search
    resetFastInputStrip();
    searchInput.focus();
}

function resetFastInputStrip() {
    searchInput.value = '';
    currentSelectedProduct = null;
    currentSelectedLot = null;
    document.getElementById('selectedProductId').value = '';
    const lotSel = document.getElementById('selectPriceLot');
    lotSel.innerHTML = '<option value="">-- Choose Product First --</option>';
    lotSel.disabled = true;
    if (lotSel._curtissSelect) {
        lotSel._curtissSelect.refreshOptions();
    }
    document.getElementById('inputSellingPrice').value = '';
    document.getElementById('inputLabelQty').value = '1';
}

function renderQueueTable() {
    const tbody = document.getElementById('queueTableBody');

    if (printQueue.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" style="text-align: center; color: #94a3b8; padding: 3rem; font-weight: 600;">
                    Your print queue is empty. Search products above and click "+ Add to Queue".
                </td>
            </tr>
        `;
        updateQueueTotals();
        return;
    }

    let html = '';
    printQueue.forEach((item, idx) => {
        const rowsNeeded = Math.ceil(item.quantity / 2);
        const priceBadge = item.is_custom_price 
            ? `<span style="font-size: 10px; color: #d97706; background: #fef3c7; padding: 1px 6px; border-radius: 999px; margin-left: 4px; font-weight: 800;">Custom</span>` 
            : '';

        html += `
            <tr>
                <td style="font-weight: 800; color: #0f172a;">${escapeHtml(item.product_code)}</td>
                <td style="font-weight: 700; color: #0f172a;">
                    <div>${escapeHtml(item.product_name)}</div>
                    <div style="font-size: 10px; color: #64748b; font-weight: 600;">BC: ${escapeHtml(item.barcode)}</div>
                </td>
                <td style="font-size: 11.5px; color: #475569; font-weight: 600;">
                    ${escapeHtml(item.lot_number)}
                </td>
                <td class="col-center" style="font-weight: 700; color: #0284c7;">
                    ${item.available_stock}
                </td>
                <td class="col-right" style="font-weight: 800; color: #16a34a; font-size: 13px;">
                    ${item.selling_price.toLocaleString('en-US', {minimumFractionDigits: 2})} ${priceBadge}
                </td>
                <td class="col-center">
                    <input type="number" min="1" max="5000" class="form-control" style="width: 75px; height: 30px; text-align: center; font-weight: 800; display: inline-block; padding: 0 4px;" 
                        value="${item.quantity}" onchange="updateQueueItemQty(${idx}, this.value)">
                </td>
                <td class="col-center" style="font-weight: 800; color: #64748b;">
                    ${rowsNeeded}
                </td>
                <td class="col-right">
                    <button type="button" class="prod-act-btn prod-act-delete" onclick="removeQueueItem(${idx})" title="Remove item">
                        Remove
                    </button>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
    updateQueueTotals();
}

function updateQueueItemQty(idx, val) {
    const q = Math.max(1, parseInt(val) || 1);
    if (printQueue[idx]) {
        printQueue[idx].quantity = q;
        renderQueueTable();
    }
}

function removeQueueItem(idx) {
    printQueue.splice(idx, 1);
    renderQueueTable();
}

function clearPrintQueue() {
    if (printQueue.length === 0) return;
    if (confirm("Are you sure you want to clear all items in the print queue?")) {
        printQueue = [];
        renderQueueTable();
    }
}

function updateQueueTotals() {
    const totalItems = printQueue.length;
    const totalLabels = printQueue.reduce((sum, item) => sum + (parseInt(item.quantity) || 0), 0);
    const totalRows = Math.ceil(totalLabels / 2);

    document.getElementById('queueCountBadge').innerText = `Queue: ${totalLabels} labels (${totalRows} rows)`;
    document.getElementById('footItemCount').innerText = totalItems;
    document.getElementById('footLabelCount').innerText = totalLabels;
    document.getElementById('footRowCount').innerText = totalRows;
}

// ── 4. TSC TE244 Direct Printing (2 per row with blank filling for odd quantities) ──
function handleDirectPrintNow() {
    if (!currentSelectedProduct) {
        alert("Please search and select a product first.");
        searchInput.focus();
        return;
    }

    const qty = parseInt(document.getElementById('inputLabelQty').value) || 1;
    if (qty < 1) {
        alert("Please enter a valid quantity of labels (minimum 1).");
        document.getElementById('inputLabelQty').focus();
        return;
    }

    const customPriceVal = parseFloat(document.getElementById('inputSellingPrice').value);
    const lotPrice = currentSelectedLot ? (parseFloat(currentSelectedLot.selling_price) || 0) : currentSelectedProduct.default_price;
    const finalPrice = !isNaN(customPriceVal) ? customPriceVal : lotPrice;

    // Barcode: prioritize product barcode, fallback to SKU or generated code
    const barcodeVal = currentSelectedProduct.barcode || currentSelectedProduct.sku || String(currentSelectedProduct.id).padStart(8, '0');

    const directItem = {
        queue_id: 'direct_' + Date.now(),
        product_id: currentSelectedProduct.id,
        product_name: currentSelectedProduct.name,
        product_code: currentSelectedProduct.sku || String(currentSelectedProduct.id).padStart(5, '0'),
        barcode: barcodeVal,
        lot_id: currentSelectedLot ? currentSelectedLot.lot_id : 0,
        lot_number: currentSelectedLot ? currentSelectedLot.lot_number : 'MASTER',
        available_stock: currentSelectedLot ? currentSelectedLot.remaining_quantity : currentSelectedProduct.total_branch_stock,
        lot_price: lotPrice,
        selling_price: finalPrice,
        quantity: qty
    };

    // Build the 2-column print DOM with this direct item
    buildPrintDom([directItem]);

    // Log audit activity
    logPrintActivity(qty, [directItem]);

    // Trigger browser thermal print
    setTimeout(() => {
        window.print();
    }, 150);

    // Reset fast input strip so user can immediately scan / search next product
    resetFastInputStrip();

    // Refocus on products search bar
    setTimeout(() => {
        if (searchInput) {
            searchInput.focus();
        }
    }, 200);
}

function triggerDirectPrint() {
    if (printQueue.length === 0) {
        alert("The print queue is empty! Add products to the queue first.");
        searchInput.focus();
        return;
    }

    // Build the 2-column print DOM from queued items
    buildPrintDom();

    // Log audit activity
    const totalLabels = printQueue.reduce((sum, item) => sum + (parseInt(item.quantity) || 0), 0);
    logPrintActivity(totalLabels);

    // Trigger browser thermal print
    setTimeout(() => {
        window.print();
    }, 150);
}

function buildPrintDom(customList = null) {
    const container = document.getElementById('printLabelContainer');
    container.innerHTML = '';

    const sourceList = customList || printQueue;
    if (!sourceList || sourceList.length === 0) return;

    // Flatten items into individual label tokens
    const individualLabels = [];
    sourceList.forEach(item => {
        for (let i = 0; i < item.quantity; i++) {
            individualLabels.push(item);
        }
    });

    if (individualLabels.length === 0) return;

    // Pair labels 2 per row
    for (let i = 0; i < individualLabels.length; i += 2) {
        const itemLeft = individualLabels[i];
        const itemRight = individualLabels[i + 1] || null; // null if odd count

        const rowDiv = document.createElement('div');
        rowDiv.className = 'label-row';

        // Left label
        rowDiv.appendChild(createSingleLabelElement(itemLeft));

        // Right label (or blank spacer)
        if (itemRight) {
            rowDiv.appendChild(createSingleLabelElement(itemRight));
        } else {
            const blankDiv = document.createElement('div');
            blankDiv.className = 'label-blank';
            rowDiv.appendChild(blankDiv);
        }

        container.appendChild(rowDiv);
    }
}

function createSingleLabelElement(item) {
    const el = document.createElement('div');
    el.className = 'label-item';

    const shopNameHtml = activeSettings.show_shop_name 
        ? `<div class="label-shop-name">${escapeHtml(activeSettings.shop_name || 'FALCON STATIONERY')}</div>` 
        : '';

    const codeHtml = activeSettings.show_product_code 
        ? `<div class="label-product-code">Code: ${escapeHtml(item.product_code)}</div>` 
        : '';

    const pricePrefix = activeSettings.price_prefix || 'Rs.';
    const priceFormatted = `${pricePrefix} ${item.selling_price.toLocaleString('en-US', {minimumFractionDigits: 2})}`;
    const priceHtml = activeSettings.show_price 
        ? `<div class="label-price">${priceFormatted}</div>` 
        : '';

    // Generate razor-sharp Code 128 SVG barcode
    const barcodeSvg = CurtissCode128.generateSVG(item.barcode, {
        width: 1.25,
        height: 38,
        fontSize: 9,
        showText: true
    });

    el.innerHTML = `
        <div style="width: 100%;">
            ${shopNameHtml}
            <div class="label-product-name">${escapeHtml(item.product_name)}</div>
            ${codeHtml}
        </div>
        <div class="label-barcode-wrapper">
            ${barcodeSvg}
        </div>
        <div style="width: 100%;">
            ${priceHtml}
        </div>
    `;

    return el;
}

// ── 5. Live Preview Modal ──
function openPreviewModal() {
    if (printQueue.length === 0) {
        alert("The print queue is empty! Add products to the queue first.");
        searchInput.focus();
        return;
    }

    const wrapper = document.getElementById('previewSheetWrapper');
    wrapper.innerHTML = '';

    const individualLabels = [];
    printQueue.forEach(item => {
        for (let i = 0; i < item.quantity; i++) {
            individualLabels.push(item);
        }
    });

    for (let i = 0; i < individualLabels.length; i += 2) {
        const itemLeft = individualLabels[i];
        const itemRight = individualLabels[i + 1] || null;

        const rowDiv = document.createElement('div');
        rowDiv.style.display = 'flex';
        rowDiv.style.gap = '8px';
        rowDiv.style.background = '#ffffff';
        rowDiv.style.border = '1px dashed #cbd5e1';
        rowDiv.style.borderRadius = '8px';
        rowDiv.style.padding = '8px';
        rowDiv.style.boxShadow = '0 2px 6px rgba(0,0,0,0.04)';

        // Left label preview card
        rowDiv.appendChild(createPreviewCard(itemLeft));

        // Right label preview card
        if (itemRight) {
            rowDiv.appendChild(createPreviewCard(itemRight));
        } else {
            const blankCard = document.createElement('div');
            blankCard.style.width = '180px';
            blankCard.style.height = '240px';
            blankCard.style.border = '1px dashed #e2e8f0';
            blankCard.style.borderRadius = '6px';
            blankCard.style.display = 'flex';
            blankCard.style.alignItems = 'center';
            blankCard.style.justifyContent = 'center';
            blankCard.style.color = '#cbd5e1';
            blankCard.style.fontSize = '11px';
            blankCard.style.fontWeight = '700';
            blankCard.innerText = '[Blank Feed]';
            rowDiv.appendChild(blankCard);
        }

        wrapper.appendChild(rowDiv);
    }

    document.getElementById('modalPreview').classList.add('active');
}

function createPreviewCard(item) {
    const card = document.createElement('div');
    card.style.width = '180px';
    card.style.height = '240px';
    card.style.background = '#ffffff';
    card.style.border = '1px solid #0f172a';
    card.style.borderRadius = '6px';
    card.style.padding = '10px 8px';
    card.style.display = 'flex';
    card.style.flexDirection = 'column';
    card.style.justifyContent = 'space-between';
    card.style.alignItems = 'center';
    card.style.textAlign = 'center';
    card.style.boxSizing = 'border-box';

    const barcodeSvg = CurtissCode128.generateSVG(item.barcode, {
        width: 1.25,
        height: 38,
        fontSize: 9,
        showText: true
    });

    const pricePrefix = activeSettings.price_prefix || 'Rs.';
    card.innerHTML = `
        <div style="width: 100%;">
            <div style="font-weight: 900; font-size: 11px; text-transform: uppercase;">${escapeHtml(activeSettings.shop_name || 'FALCON STATIONERY')}</div>
            <div style="font-weight: 700; font-size: 10px; margin: 3px 0; max-height: 28px; overflow: hidden;">${escapeHtml(item.product_name)}</div>
            <div style="font-size: 9.5px; font-weight: 800; color: #475569;">Code: ${escapeHtml(item.product_code)}</div>
        </div>
        <div style="width: 100%; margin: 6px 0;">
            ${barcodeSvg}
        </div>
        <div style="width: 100%; font-weight: 900; font-size: 13px; color: #0f172a;">
            ${pricePrefix} ${item.selling_price.toLocaleString('en-US', {minimumFractionDigits: 2})}
        </div>
    `;

    return card;
}

// ── 6. TSPL-II Command Generator (TSC TE244) ──
function openTsplModal() {
    if (printQueue.length === 0) {
        alert("The print queue is empty! Add products to the queue first.");
        searchInput.focus();
        return;
    }

    const tspl = generateTsplCommands();
    document.getElementById('tsplCodeArea').value = tspl;
    document.getElementById('modalTspl').classList.add('active');
}

function generateTsplCommands() {
    let lines = [];
    lines.push('SIZE 52 mm, 40 mm');
    lines.push('GAP 2 mm, 0 mm');
    lines.push('DIRECTION 1,0');
    lines.push('REFERENCE 0,0');
    lines.push('OFFSET 0 mm');
    lines.push('SET PEEL OFF');
    lines.push('SET CUTTER OFF');
    lines.push('SET TEAR ON');

    const individualLabels = [];
    printQueue.forEach(item => {
        for (let i = 0; i < item.quantity; i++) {
            individualLabels.push(item);
        }
    });

    // 2 columns per row in TSPL
    for (let i = 0; i < individualLabels.length; i += 2) {
        lines.push('CLS');

        const leftItem = individualLabels[i];
        const rightItem = individualLabels[i + 1] || null;

        // Column 1 (Left label at X=15 dots ~ 2mm)
        appendTsplLabelBlock(lines, leftItem, 15);

        // Column 2 (Right label at X=220 dots ~ 27.5mm)
        if (rightItem) {
            appendTsplLabelBlock(lines, rightItem, 220);
        }

        lines.push('PRINT 1,1');
    }

    return lines.join('\r\n');
}

function appendTsplLabelBlock(lines, item, startX) {
    const shop = activeSettings.shop_name || 'FALCON STATIONERY';
    const pricePrefix = activeSettings.price_prefix || 'Rs.';
    const priceStr = `${pricePrefix} ${item.selling_price.toFixed(2)}`;

    // Shop Header
    lines.push(`TEXT ${startX + 5},15,"3",0,1,1,"${escapeTspl(shop)}"`);
    // Product Name
    lines.push(`TEXT ${startX + 5},45,"2",0,1,1,"${escapeTspl(item.product_name.substr(0, 20))}"`);
    // Product Code
    lines.push(`TEXT ${startX + 5},72,"1",0,1,1,"Code: ${escapeTspl(item.product_code)}"`);
    // Code 128 Barcode: BARCODE X,Y,"128",height,human_readable,rotation,narrow,wide,"content"
    lines.push(`BARCODE ${startX + 10},95,"128",90,1,0,2,2,"${escapeTspl(item.barcode)}"`);
    // Price
    lines.push(`TEXT ${startX + 15},220,"3",0,1,1,"${escapeTspl(priceStr)}"`);
}

function escapeTspl(str) {
    return String(str).replace(/"/g, '""');
}

function copyTsplCode() {
    const ta = document.getElementById('tsplCodeArea');
    ta.select();
    navigator.clipboard.writeText(ta.value).then(() => {
        alert("TSPL commands copied to clipboard!");
    });
}

function downloadTsplFile() {
    const code = document.getElementById('tsplCodeArea').value;
    const blob = new Blob([code], { type: 'text/plain;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `curtiss_barcode_labels_${Date.now()}.prn`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

// ── 7. Audit Logging ──
async function logPrintActivity(totalLabels, customItems = null) {
    try {
        const formData = new FormData();
        formData.append('total_labels', totalLabels);
        formData.append('items_json', JSON.stringify(customItems || printQueue));
        await fetch('barcode_labels_api.php?action=log_print', {
            method: 'POST',
            body: formData
        });
    } catch (e) {
        console.error("Audit log error:", e);
    }
}

// ── 8. Keyboard Navigation Loop ──
function initKeyboardNavigation() {
    // Arrow Navigation on Product Autocomplete
    searchInput.addEventListener('keydown', function(e) {
        if (!suggestionsBox.classList.contains('active')) return;
        const items = suggestionsBox.querySelectorAll('.autocomplete-item');
        if (items.length === 0) return;

        let activeIdx = -1;
        items.forEach((item, idx) => {
            if (item.classList.contains('selected')) activeIdx = idx;
        });

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIdx = (activeIdx + 1) % items.length;
            highlightSuggestion(items, activeIdx);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIdx = (activeIdx - 1 + items.length) % items.length;
            highlightSuggestion(items, activeIdx);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (activeIdx >= 0 && currentSearchResults[activeIdx]) {
                selectProduct(currentSearchResults[activeIdx]);
            }
        } else if (e.key === 'Escape') {
            suggestionsBox.classList.remove('active');
        }
    });

    // Enter inside Custom Price advances to Qty
    document.getElementById('inputSellingPrice').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('inputLabelQty').focus();
            document.getElementById('inputLabelQty').select();
        }
    });

    // Enter inside QTY advances focus directly to Print Now button
    document.getElementById('inputLabelQty').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const btn = document.getElementById('btnDirectPrintNow');
            if (btn) {
                btn.focus();
            }
        }
    });

    // Enter inside Print Now button triggers direct print
    const directPrintBtn = document.getElementById('btnDirectPrintNow');
    if (directPrintBtn) {
        directPrintBtn.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                handleDirectPrintNow();
            }
        });
    }

    // Refocus on products search bar after browser print dialog closes
    window.addEventListener('afterprint', function() {
        setTimeout(() => {
            if (searchInput) {
                searchInput.focus();
            }
        }, 60);
    });

    // Global Ctrl+P hotkey triggers print
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && (e.key === 'p' || e.key === 'P')) {
            e.preventDefault();
            triggerDirectPrint();
        }
    });

    // Close autocomplete on click outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.autocomplete-container')) {
            suggestionsBox.classList.remove('active');
        }
    });
}

function highlightSuggestion(items, index) {
    items.forEach((item, idx) => {
        if (idx === index) {
            item.classList.add('selected');
            item.scrollIntoView({ block: 'nearest' });
        } else {
            item.classList.remove('selected');
        }
    });
}

function closeModal(modalId) {
    const el = document.getElementById(modalId);
    if (el) el.classList.remove('active');
}

function escapeHtml(str) {
    return String(str || '')
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
</script>
