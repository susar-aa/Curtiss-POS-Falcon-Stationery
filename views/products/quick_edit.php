<?php
/**
 * Falcon POS - Quick Product & Stock Editor View
 * Enhanced with 3-Up Thermal Barcode Label Printing (30mm x 15mm / 3 stickers in row)
 */
?>
<script src="assets/js/code128.js"></script>

<style>
/* CSS Variables for 3-Up Label Calibration (30mm x 15mm on 99mm / 3.9in Roll) */
:root {
    --lbl-width: 30mm;
    --lbl-height: 15mm;
    --lbl-row-width: 99mm;
    --lbl-gap-x: 3mm;
    --lbl-gap-y: 0mm;
}

/* Quick Editor Specific Styles */
.qe-container {
    width: 100%;
    margin: 0 auto;
}
.qe-header-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 1.25rem 1.5rem;
    margin-bottom: 1.25rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.qe-search-box {
    position: relative;
    flex: 1;
    min-width: 280px;
}
.qe-search-input {
    width: 100%;
    height: 44px;
    padding: 0 42px 0 42px;
    border: 1.5px solid #cbd5e1;
    border-radius: 10px;
    font-size: 0.92rem;
    color: #0f172a;
    background: #f8fafc;
    transition: all 0.15s ease;
    box-sizing: border-box;
}
.qe-search-input:focus {
    background: #ffffff;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    outline: none;
}
.qe-search-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    pointer-events: none;
}
.qe-clear-icon {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    cursor: pointer;
    display: none;
}
.qe-clear-icon:hover { color: #0f172a; }

.qe-table-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    overflow: hidden;
}
.qe-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.88rem;
    text-align: left;
}
.qe-table thead th {
    background: #f8fafc;
    color: #475569;
    font-size: 0.76rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 0.85rem 0.75rem;
    border-bottom: 1.5px solid #e2e8f0;
    white-space: nowrap;
    position: sticky;
    top: 0;
    z-index: 10;
}
.qe-table tbody tr {
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.15s ease;
}
.qe-table tbody tr:hover {
    background: #f8fafc;
}
.qe-table tbody tr.row-dirty {
    background: #fffbeb !important;
}
.qe-table tbody tr.row-saved {
    background: #f0fdf4 !important;
    animation: flashGreen 1.5s ease;
}
.qe-table td {
    padding: 0.65rem 0.75rem;
    vertical-align: middle;
}

/* Inline Inputs */
.qe-input {
    width: 100%;
    height: 36px;
    padding: 0 10px;
    border: 1.5px solid #cbd5e1;
    border-radius: 7px;
    font-size: 0.88rem;
    font-family: inherit;
    color: #0f172a;
    background: #ffffff;
    box-sizing: border-box;
    transition: all 0.15s ease;
}
.qe-input:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 2.5px rgba(37, 99, 235, 0.15);
    outline: none;
    background: #ffffff;
}
.qe-input-num {
    text-align: right;
    font-variant-numeric: tabular-nums;
}
.qe-input-stock {
    font-weight: 800;
    color: #0f172a;
}
.qe-input.dirty {
    border-color: #f59e0b;
    background: #fffdf5;
}

/* Action Buttons */
.qe-btn-update {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    height: 36px;
    padding: 0 14px;
    border-radius: 7px;
    background: #0f172a;
    color: #ffffff;
    font-size: 0.82rem;
    font-weight: 700;
    border: none;
    cursor: pointer;
    transition: all 0.15s ease;
    white-space: nowrap;
}
.qe-btn-update:hover {
    background: #1e293b;
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(15,23,42,0.2);
}
.qe-btn-update:active {
    transform: translateY(0);
}
.qe-btn-update.btn-saved {
    background: #16a34a !important;
}
.qe-btn-update.btn-error {
    background: #dc2626 !important;
}

/* Label Print Button & Quantity Field */
.qe-btn-print {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    height: 36px;
    padding: 0 12px;
    border-radius: 7px;
    background: #0284c7;
    color: #ffffff;
    font-size: 0.82rem;
    font-weight: 700;
    border: none;
    cursor: pointer;
    transition: all 0.15s ease;
    white-space: nowrap;
}
.qe-btn-print:hover {
    background: #0369a1;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(2, 132, 199, 0.25);
}
.qe-btn-print:active {
    transform: translateY(0);
}
.qe-btn-price {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    height: 36px;
    padding: 0 10px;
    border-radius: 7px;
    background: #059669;
    color: #ffffff;
    font-size: 0.80rem;
    font-weight: 700;
    border: none;
    cursor: pointer;
    transition: all 0.15s ease;
    white-space: nowrap;
}
.qe-btn-price:hover {
    background: #047857;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(5, 150, 105, 0.25);
}
.qe-btn-price:active {
    transform: translateY(0);
}
.field-label-qty {
    width: 48px !important;
    height: 36px !important;
    padding: 0 3px !important;
    text-align: center !important;
    font-weight: 800 !important;
    color: #0f172a !important;
    border-radius: 7px !important;
}

/* Delete Product Button */
.qe-btn-delete {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 36px;
    border-radius: 7px;
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
    cursor: pointer;
    transition: all 0.15s ease;
    padding: 0;
    flex-shrink: 0;
}
.qe-btn-delete:hover {
    background: #dc2626;
    color: #ffffff;
    border-color: #dc2626;
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(220, 38, 38, 0.25);
}
.qe-btn-delete:active {
    transform: translateY(0);
}

@keyframes flashGreen {
    0% { background: #dcfce7; }
    100% { background: #ffffff; }
}

/* Modal Overlay & Styling */
.qe-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(15, 23, 42, 0.55);
    backdrop-filter: blur(4px);
    z-index: 10000;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 16px;
    box-sizing: border-box;
}
.qe-modal-overlay.active {
    display: flex;
}
.qe-modal-box {
    background: #ffffff;
    border-radius: 16px;
    width: 720px;
    max-width: 96vw;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 48px -10px rgba(15, 23, 42, 0.35);
    overflow: hidden;
    animation: modalPop 0.2s ease-out;
}
@keyframes modalPop {
    from { transform: scale(0.96); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}
.qe-modal-header {
    padding: 1.1rem 1.4rem;
    border-bottom: 1.5px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #ffffff;
}
.qe-modal-body {
    padding: 1.25rem 1.4rem;
    overflow-y: auto;
    flex: 1;
    background: #f8fafc;
}
.qe-modal-footer {
    padding: 0.9rem 1.4rem;
    border-top: 1.5px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #ffffff;
}

/* ══════════════════════════════════════════════════════════════
   3-UP THERMAL LABEL PRINTING ENGINE (3 STICKERS PER ROW)
   Roll width: ~100mm, each sticker ~31.5mm x 16mm
   ══════════════════════════════════════════════════════════════ */
#printLabelContainer {
    display: none;
}

@media print {
    body * {
        visibility: hidden !important;
    }
    .no-print, header, nav, #curtiss-nav, #curtiss-floating-header-right, .qe-container, .qe-modal-overlay, .qe-toast {
        display: none !important;
    }

    @page {
        size: 99mm 15mm;
        margin: 0mm !important;
    }

    html, body {
        width: 99mm !important;
        height: auto !important;
        min-height: 15mm !important;
        margin: 0 !important;
        padding: 0 !important;
        background: #ffffff !important;
        overflow: hidden !important;
    }

    #printLabelContainer {
        visibility: visible !important;
        display: block !important;
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        width: 99mm !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    #printLabelContainer * {
        visibility: visible !important;
    }

    .label-row-3 {
        display: flex !important;
        flex-direction: row !important;
        width: 99mm !important;
        height: 15mm !important;
        max-height: 15mm !important;
        margin: 0 !important;
        padding: 0 1.5mm !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        page-break-after: always !important;
        break-after: page !important;
        box-sizing: border-box !important;
        justify-content: space-between !important;
        overflow: hidden !important;
    }

    .label-row-3:last-child {
        page-break-after: avoid !important;
        break-after: avoid !important;
    }

    .label-item-3 {
        width: 30mm !important;
        max-width: 30mm !important;
        height: 14.8mm !important;
        max-height: 14.8mm !important;
        padding: 0.3mm 0.5mm !important;
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

    .label-item-price-only {
        justify-content: center !important;
        gap: 1mm !important;
        padding: 0.5mm 0.5mm !important;
    }

    .label-blank-3 {
        width: 30mm !important;
        height: 14.8mm !important;
        visibility: hidden !important;
    }

    .lbl-company {
        font-family: Arial, Helvetica, sans-serif !important;
        font-size: 4.8pt !important;
        font-weight: 900 !important;
        letter-spacing: 0px !important;
        text-transform: uppercase !important;
        line-height: 1 !important;
        margin: 0 !important;
        color: #000000 !important;
        width: 100% !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
    }

    .lbl-name {
        font-family: Arial, Helvetica, sans-serif !important;
        font-size: 7.2pt !important;
        font-weight: 800 !important;
        line-height: 1.1 !important;
        margin: 0 !important;
        max-height: 3.2mm !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        white-space: nowrap !important;
        width: 100% !important;
        color: #000000 !important;
    }

    .lbl-barcode {
        width: 96% !important;
        height: 6.2mm !important;
        max-height: 6.2mm !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        margin: 0.2mm auto !important;
        overflow: hidden !important;
    }

    .lbl-barcode svg {
        width: 100% !important;
        height: 100% !important;
        max-height: 6.2mm !important;
    }

    .lbl-sku {
        font-family: 'Courier New', Courier, monospace !important;
        font-size: 7.5pt !important;
        font-weight: 900 !important;
        color: #000000 !important;
        width: 100% !important;
        text-align: center !important;
        line-height: 1 !important;
        margin: 0.2mm 0 0 0 !important;
        letter-spacing: 0.3px !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
    }

    .lbl-bottom {
        width: 100% !important;
        display: flex !important;
        justify-content: center !important;
        align-items: center !important;
        line-height: 1 !important;
        margin: 0 !important;
        text-align: center !important;
    }

    .lbl-price {
        font-family: Arial, Helvetica, sans-serif !important;
        font-size: 8.5pt !important;
        font-weight: 900 !important;
        color: #000000 !important;
        white-space: nowrap !important;
        text-align: center !important;
    }

    .lbl-price-large {
        font-family: Arial, Helvetica, sans-serif !important;
        font-size: 13pt !important;
        font-weight: 900 !important;
        color: #000000 !important;
        line-height: 1.1 !important;
        text-align: center !important;
        white-space: nowrap !important;
        letter-spacing: -0.2px !important;
    }
}

/* Toast alert */
.qe-toast {
    position: fixed;
    bottom: 24px;
    right: 24px;
    background: #0f172a;
    color: #ffffff;
    padding: 12px 20px;
    border-radius: 10px;
    font-size: 0.88rem;
    font-weight: 600;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    z-index: 9999;
    display: none;
    align-items: center;
    gap: 10px;
    animation: slideUp 0.25s ease;
}
@keyframes slideUp {
    from { transform: translateY(20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
</style>

<div class="qe-container">
    <!-- Header Title & Active Operating Branch -->
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 1.25rem;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <h1 style="margin: 0; font-size: 1.55rem; font-weight: 800; color: #0f172a;">Quick Product & Stock Editor</h1>
                <span class="badge" style="background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; font-size: 0.78rem; font-weight: 700; padding: 4px 10px; border-radius: 6px;">
                    Branch: <?= View::escape($activeBranchName) ?>
                </span>
            </div>
            <p style="margin: 0.35rem 0 0 0; color: #64748b; font-size: 0.88rem;">
                Edit product names, barcodes, prices, and update <strong>live branch inventory</strong> directly in real time.
            </p>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <button type="button" onclick="openAddProductModal()" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 6px; padding: 0.5rem 1.15rem; font-size: 0.85rem; font-weight: 800; border-radius: 8px; background: #0f172a; color: #ffffff; border: none; cursor: pointer; box-shadow: 0 2px 8px rgba(15,23,42,0.25);">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                + Add Product
            </button>
            <button type="button" onclick="openLabelConfigModal()" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 6px; padding: 0.5rem 1rem; font-size: 0.85rem; font-weight: 700; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; color: #334155; cursor: pointer;" title="Configure Label Calibration (3-up roll sizing)">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2v-.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                Label Setup (3-Up)
            </button>
            <a href="products.php" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 6px; text-decoration: none; padding: 0.5rem 1rem; font-size: 0.85rem; font-weight: 700; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; color: #334155;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                Product Master
            </a>
            <a href="product_import.php" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 6px; text-decoration: none; padding: 0.5rem 1rem; font-size: 0.85rem; font-weight: 700; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; color: #334155;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                Import CSV
            </a>
        </div>
    </div>

    <!-- Search & Controls Bar -->
    <div class="qe-header-card">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <!-- Realtime Search Bar -->
            <div class="qe-search-box">
                <svg class="qe-search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" id="qeSearchInput" class="qe-search-input" placeholder="Type to search by Product Name, Barcode, or SKU in real-time..." autofocus>
                <span id="qeClearBtn" class="qe-clear-icon" onclick="clearSearch()">&times;</span>
            </div>

            <!-- Stats & Quick Actions -->
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="font-size: 0.85rem; color: #64748b; font-weight: 600;">
                    Showing <strong id="qeRowCount" style="color: #0f172a;"><?= count($products) ?></strong> of <strong id="qeTotalCount" style="color: #0f172a;"><?= $totalCount ?></strong> products
                </div>
                <button type="button" id="qeSaveAllBtn" class="btn" onclick="saveAllModified()" style="display: none; background: #f59e0b; color: #ffffff; border: none; padding: 0.5rem 1rem; border-radius: 8px; font-weight: 700; font-size: 0.85rem; cursor: pointer;">
                    ⚡ Save All Modified (<span id="dirtyCount">0</span>)
                </button>
            </div>
        </div>
    </div>

    <!-- Editable Products Table -->
    <div class="qe-table-card">
        <div class="table-responsive" style="max-height: 72vh; overflow-y: auto;">
            <table class="qe-table" id="qeProductTable">
                <thead>
                    <tr>
                        <th style="width: 60px;"># ID</th>
                        <th style="min-width: 200px;">Product Name *</th>
                        <th style="width: 130px;">SKU</th>
                        <th style="width: 140px;">Barcode *</th>
                        <th style="width: 110px; text-align: right;">Cost (LKR)</th>
                        <th style="width: 120px; text-align: right;">Selling Price (LKR)</th>
                        <th style="width: 100px; text-align: right;">Stock (Live)</th>
                        <th style="width: 275px; text-align: center;">Actions &amp; Print</th>
                    </tr>
                </thead>
                <tbody id="qeTableBody">
                    <?php if (empty($products)): ?>
                        <tr id="noRowsNotice">
                            <td colspan="8" style="padding: 3rem; text-align: center; color: #64748b;">
                                <div style="font-size: 1.1rem; font-weight: 700; color: #1e293b; margin-bottom: 0.25rem;">No products found</div>
                                <div style="font-size: 0.85rem;">Import products or add new items to get started.</div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $p): ?>
                            <tr id="row_<?= (int)$p['id'] ?>" data-id="<?= (int)$p['id'] ?>" data-sku="<?= View::escape($p['sku'] ?? '') ?>">
                                <!-- ID -->
                                <td>
                                    <span style="font-weight: 800; color: #475569;">#<?= (int)$p['id'] ?></span>
                                </td>

                                <!-- Product Name -->
                                <td>
                                    <input type="text" class="qe-input field-name" value="<?= View::escape($p['name']) ?>" 
                                           data-original="<?= View::escape($p['name']) ?>" oninput="markDirty(<?= (int)$p['id'] ?>)" onkeydown="checkEnter(event, <?= (int)$p['id'] ?>)">
                                </td>

                                <!-- SKU -->
                                <td>
                                    <input type="text" class="qe-input field-sku" style="font-family: monospace;" value="<?= View::escape($p['sku'] ?? '') ?>" 
                                           data-original="<?= View::escape($p['sku'] ?? '') ?>" placeholder="SKU" oninput="markDirty(<?= (int)$p['id'] ?>)" onkeydown="checkEnter(event, <?= (int)$p['id'] ?>)">
                                </td>

                                <!-- Barcode -->
                                <td>
                                    <input type="text" class="qe-input field-barcode" style="font-family: monospace;" value="<?= View::escape($p['barcode']) ?>" 
                                           data-original="<?= View::escape($p['barcode']) ?>" oninput="markDirty(<?= (int)$p['id'] ?>)" onkeydown="checkEnter(event, <?= (int)$p['id'] ?>)">
                                </td>

                                <!-- Cost Price -->
                                <td>
                                    <input type="number" step="0.01" min="0" class="qe-input qe-input-num field-cost" value="<?= number_format((float)$p['cost'], 2, '.', '') ?>" 
                                           data-original="<?= number_format((float)$p['cost'], 2, '.', '') ?>" oninput="markDirty(<?= (int)$p['id'] ?>)" onkeydown="checkEnter(event, <?= (int)$p['id'] ?>)">
                                </td>

                                <!-- Selling Price -->
                                <td>
                                    <input type="number" step="0.01" min="0" class="qe-input qe-input-num field-price" value="<?= number_format((float)$p['selling_price'], 2, '.', '') ?>" 
                                           data-original="<?= number_format((float)$p['selling_price'], 2, '.', '') ?>" oninput="markDirty(<?= (int)$p['id'] ?>)" onkeydown="checkEnter(event, <?= (int)$p['id'] ?>)">
                                </td>

                                <!-- Live Branch Inventory Stock -->
                                <td>
                                    <input type="number" step="1" class="qe-input qe-input-num qe-input-stock field-stock" value="<?= (float)$p['stock'] ?>" 
                                           data-original="<?= (float)$p['stock'] ?>" oninput="markDirty(<?= (int)$p['id'] ?>)" onkeydown="checkEnter(event, <?= (int)$p['id'] ?>)">
                                </td>

                                <!-- Action & Label Print Buttons -->
                                <td style="text-align: center;">
                                    <div style="display: flex; align-items: center; justify-content: center; gap: 5px;">
                                        <button type="button" class="qe-btn-update" id="btn_<?= (int)$p['id'] ?>" onclick="saveProduct(<?= (int)$p['id'] ?>)">
                                            Update
                                        </button>
                                        <div style="display: flex; align-items: center; gap: 3px; border-left: 1px solid #e2e8f0; padding-left: 5px;">
                                            <input type="number" step="1" min="1" max="999" class="qe-input field-label-qty" value="3" 
                                                   title="Label Quantity to print (3 per row)" onkeydown="if(event.key==='Enter') printRowLabel(<?= (int)$p['id'] ?>)">
                                            <button type="button" class="qe-btn-print" onclick="printRowLabel(<?= (int)$p['id'] ?>)" title="Print Full Barcode Label (3 stickers in row)">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                                                Print
                                            </button>
                                            <button type="button" class="qe-btn-price" onclick="printRowPriceOnly(<?= (int)$p['id'] ?>)" title="Print Price Only Labels (Company & Price)">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                                                Price
                                            </button>
                                        </div>
                                        <button type="button" class="qe-btn-delete" onclick="deleteProduct(<?= (int)$p['id'] ?>, '<?= View::escape(addslashes($p['name'])) ?>')" title="Delete Product (Move to Trash Bin)">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Toast Alert Box -->
<div id="qeToast" class="qe-toast">
    <span id="qeToastIcon">✓</span>
    <span id="qeToastMsg">Update saved successfully!</span>
</div>

<script>
let searchTimeout = null;
const modifiedRows = new Set();

function markDirty(id) {
    const row = document.getElementById('row_' + id);
    if (!row) return;

    const name = row.querySelector('.field-name');
    const sku = row.querySelector('.field-sku');
    const barcode = row.querySelector('.field-barcode');
    const cost = row.querySelector('.field-cost');
    const price = row.querySelector('.field-price');
    const stock = row.querySelector('.field-stock');

    const isChanged = (
        name.value !== name.dataset.original ||
        (sku && sku.value !== sku.dataset.original) ||
        barcode.value !== barcode.dataset.original ||
        parseFloat(cost.value || 0) !== parseFloat(cost.dataset.original || 0) ||
        parseFloat(price.value || 0) !== parseFloat(price.dataset.original || 0) ||
        parseFloat(stock.value || 0) !== parseFloat(stock.dataset.original || 0)
    );

    if (isChanged) {
        row.classList.add('row-dirty');
        modifiedRows.add(id);
    } else {
        row.classList.remove('row-dirty');
        modifiedRows.delete(id);
    }

    updateSaveAllBadge();
}

function updateSaveAllBadge() {
    const saveAllBtn = document.getElementById('qeSaveAllBtn');
    const countSpan = document.getElementById('dirtyCount');
    if (modifiedRows.size > 0) {
        saveAllBtn.style.display = 'inline-flex';
        countSpan.textContent = modifiedRows.size;
    } else {
        saveAllBtn.style.display = 'none';
    }
}

function checkEnter(event, id) {
    if (event.key === 'Enter') {
        event.preventDefault();
        saveProduct(id);
    }
}

function saveProduct(id) {
    const row = document.getElementById('row_' + id);
    if (!row) return;

    const btn = document.getElementById('btn_' + id);
    const nameInput = row.querySelector('.field-name');
    const skuInput = row.querySelector('.field-sku');
    const barcodeInput = row.querySelector('.field-barcode');
    const costInput = row.querySelector('.field-cost');
    const priceInput = row.querySelector('.field-price');
    const stockInput = row.querySelector('.field-stock');

    const name = nameInput.value.trim();
    const sku = skuInput ? skuInput.value.trim() : '';
    const barcode = barcodeInput.value.trim();
    const cost = parseFloat(costInput.value) || 0;
    const price = parseFloat(priceInput.value) || 0;
    const stock = parseFloat(stockInput.value) || 0;

    if (!name) {
        showToast('Product name cannot be empty', true);
        nameInput.focus();
        return;
    }
    if (!barcode) {
        showToast('Barcode cannot be empty', true);
        barcodeInput.focus();
        return;
    }

    // Button loading state
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="animation: spin 0.8s linear infinite;"><circle cx="12" cy="12" r="10" stroke-opacity="0.25"/><path d="M12 2a10 10 0 0 1 10 10"/></svg> Saving...';

    const formData = new FormData();
    formData.append('action', 'update_product');
    formData.append('id', id);
    formData.append('name', name);
    formData.append('sku', sku);
    formData.append('barcode', barcode);
    formData.append('cost', cost);
    formData.append('selling_price', price);
    formData.append('stock', stock);

    fetch('quick_products.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        if (res.success) {
            btn.classList.add('btn-saved');
            btn.innerHTML = '✓ Saved!';

            // Update originals
            nameInput.dataset.original = name;
            if (skuInput) skuInput.dataset.original = sku;
            barcodeInput.dataset.original = barcode;
            costInput.dataset.original = cost.toFixed(2);
            priceInput.dataset.original = price.toFixed(2);
            stockInput.dataset.original = stock;
            row.dataset.sku = sku;

            row.classList.remove('row-dirty');
            row.classList.add('row-saved');
            modifiedRows.delete(id);
            updateSaveAllBadge();

            showToast(res.message || 'Product & stock updated successfully!');

            setTimeout(() => {
                btn.classList.remove('btn-saved');
                btn.innerHTML = 'Update';
                row.classList.remove('row-saved');
            }, 1800);
        } else {
            btn.classList.add('btn-error');
            btn.innerHTML = '✕ Error';
            showToast(res.message || 'Failed to update product', true);

            setTimeout(() => {
                btn.classList.remove('btn-error');
                btn.innerHTML = 'Update';
            }, 2500);
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = 'Update';
        showToast('Network error while saving product.', true);
        console.error(err);
    });
}

function saveAllModified() {
    if (modifiedRows.size === 0) return;
    const ids = Array.from(modifiedRows);
    ids.forEach(id => {
        saveProduct(id);
    });
}

// ─── Real-Time Search Engine ──────────────────────────────────────────────────
const searchInput = document.getElementById('qeSearchInput');
const clearBtn = document.getElementById('qeClearBtn');

searchInput.addEventListener('input', function() {
    const q = this.value.trim();
    if (q.length > 0) {
        clearBtn.style.display = 'block';
    } else {
        clearBtn.style.display = 'none';
    }

    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        performRealtimeSearch(q);
    }, 180);
});

function clearSearch() {
    searchInput.value = '';
    clearBtn.style.display = 'none';
    performRealtimeSearch('');
    searchInput.focus();
}

function performRealtimeSearch(query) {
    const qLower = query.toLowerCase();

    fetch('quick_products.php?action=search&q=' + encodeURIComponent(query))
    .then(r => r.json())
    .then(res => {
        if (!res.success) return;
        renderSearchResults(res.products, query);
    })
    .catch(err => {
        // Fallback: Client-side row hide/show if server fetch fails
        filterClientRows(qLower);
    });
}

function filterClientRows(qLower) {
    const rows = document.querySelectorAll('#qeTableBody tr[data-id]');
    let visibleCount = 0;
    rows.forEach(row => {
        const name = row.querySelector('.field-name').value.toLowerCase();
        const barcode = row.querySelector('.field-barcode').value.toLowerCase();
        const skuEl = row.querySelector('.field-sku');
        const sku = skuEl ? skuEl.value.toLowerCase() : '';
        if (name.includes(qLower) || barcode.includes(qLower) || sku.includes(qLower)) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    document.getElementById('qeRowCount').textContent = visibleCount;
}

function renderSearchResults(products, query) {
    const tbody = document.getElementById('qeTableBody');
    document.getElementById('qeRowCount').textContent = products.length;

    if (products.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" style="padding: 3rem; text-align: center; color: #64748b;">
                    <div style="font-size: 1.05rem; font-weight: 700; color: #1e293b;">No matching products found</div>
                    <div style="font-size: 0.82rem; margin-top: 4px;">No products match "${escapeHtml(query)}"</div>
                </td>
            </tr>
        `;
        return;
    }

    let html = '';
    products.forEach(p => {
        const isDirty = modifiedRows.has(parseInt(p.id));
        html += `
            <tr id="row_${p.id}" data-id="${p.id}" data-sku="${escapeHtml(p.sku || '')}" class="${isDirty ? 'row-dirty' : ''}">
                <td>
                    <span style="font-weight: 800; color: #475569;">#${p.id}</span>
                </td>
                <td>
                    <input type="text" class="qe-input field-name" value="${escapeHtml(p.name)}" 
                           data-original="${escapeHtml(p.name)}" oninput="markDirty(${p.id})" onkeydown="checkEnter(event, ${p.id})">
                </td>
                <td>
                    <input type="text" class="qe-input field-sku" style="font-family: monospace;" value="${escapeHtml(p.sku || '')}" 
                           data-original="${escapeHtml(p.sku || '')}" placeholder="SKU" oninput="markDirty(${p.id})" onkeydown="checkEnter(event, ${p.id})">
                </td>
                <td>
                    <input type="text" class="qe-input field-barcode" style="font-family: monospace;" value="${escapeHtml(p.barcode)}" 
                           data-original="${escapeHtml(p.barcode)}" oninput="markDirty(${p.id})" onkeydown="checkEnter(event, ${p.id})">
                </td>
                <td>
                    <input type="number" step="0.01" min="0" class="qe-input qe-input-num field-cost" value="${parseFloat(p.cost || 0).toFixed(2)}" 
                           data-original="${parseFloat(p.cost || 0).toFixed(2)}" oninput="markDirty(${p.id})" onkeydown="checkEnter(event, ${p.id})">
                </td>
                <td>
                    <input type="number" step="0.01" min="0" class="qe-input qe-input-num field-price" value="${parseFloat(p.selling_price || 0).toFixed(2)}" 
                           data-original="${parseFloat(p.selling_price || 0).toFixed(2)}" oninput="markDirty(${p.id})" onkeydown="checkEnter(event, ${p.id})">
                </td>
                <td>
                    <input type="number" step="1" class="qe-input qe-input-num qe-input-stock field-stock" value="${parseFloat(p.stock || 0)}" 
                           data-original="${parseFloat(p.stock || 0)}" oninput="markDirty(${p.id})" onkeydown="checkEnter(event, ${p.id})">
                </td>
                <td style="text-align: center;">
                    <div style="display: flex; align-items: center; justify-content: center; gap: 5px;">
                        <button type="button" class="qe-btn-update" id="btn_${p.id}" onclick="saveProduct(${p.id})">
                            Update
                        </button>
                        <div style="display: flex; align-items: center; gap: 3px; border-left: 1px solid #e2e8f0; padding-left: 5px;">
                            <input type="number" step="1" min="1" max="999" class="qe-input field-label-qty" value="3" 
                                   title="Label Quantity to print (3 per row)" onkeydown="if(event.key==='Enter') printRowLabel(${p.id})">
                            <button type="button" class="qe-btn-print" onclick="printRowLabel(${p.id})" title="Print Full Barcode Label (3 stickers in row)">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                                Print
                            </button>
                            <button type="button" class="qe-btn-price" onclick="printRowPriceOnly(${p.id})" title="Print Price Only Labels (Company & Price)">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                                Price
                            </button>
                        </div>
                        <button type="button" class="qe-btn-delete" onclick="deleteProduct(${p.id}, '${escapeHtml(p.name)}')" title="Delete Product (Move to Trash Bin)">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

function showToast(msg, isError = false) {
    const toast = document.getElementById('qeToast');
    const icon = document.getElementById('qeToastIcon');
    const text = document.getElementById('qeToastMsg');

    text.textContent = msg;
    if (isError) {
        toast.style.background = '#dc2626';
        icon.textContent = '✕';
    } else {
        toast.style.background = '#0f172a';
        icon.textContent = '✓';
    }

    toast.style.display = 'flex';
    setTimeout(() => {
        toast.style.display = 'none';
    }, 2800);
}

function escapeHtml(text) {
    if (!text) return '';
    return text.toString()
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// ════════════════════════════════════════════════════════════════════════════
// 3-UP BARCODE LABEL PRINTING LOGIC (FALCON STATIONERY)
// ════════════════════════════════════════════════════════════════════════════

const DEFAULT_LABEL_CONFIG = {
    company_name: 'Falcon Stationery',
    show_company: true,
    show_sku: true,
    price_prefix: 'Rs.',
    label_width_mm: 30.0,
    label_height_mm: 15.0,
    row_width_mm: 99.0,
    gap_x_mm: 3.0,
    gap_y_mm: 0.0,
    direct_print: false
};

function getLabelConfig() {
    try {
        const stored = localStorage.getItem('falcon_3up_label_cfg');
        if (stored) {
            return Object.assign({}, DEFAULT_LABEL_CONFIG, JSON.parse(stored));
        }
    } catch (e) {}
    return Object.assign({}, DEFAULT_LABEL_CONFIG);
}

function applyLabelConfigCss() {
    const cfg = getLabelConfig();
    const root = document.documentElement;
    root.style.setProperty('--lbl-width', cfg.label_width_mm + 'mm');
    root.style.setProperty('--lbl-height', cfg.label_height_mm + 'mm');
    root.style.setProperty('--lbl-row-width', cfg.row_width_mm + 'mm');
    root.style.setProperty('--lbl-gap-x', cfg.gap_x_mm + 'mm');
    root.style.setProperty('--lbl-gap-y', cfg.gap_y_mm + 'mm');
}

// Apply on initial load
applyLabelConfigCss();

let currentPrintPayload = null;

function printRowLabel(id) {
    initiateLabelPrint(id, 'barcode');
}

function printRowPriceOnly(id) {
    initiateLabelPrint(id, 'price_only');
}

function initiateLabelPrint(id, mode = 'barcode') {
    const row = document.getElementById('row_' + id);
    if (!row) return;

    const name = row.querySelector('.field-name').value.trim();
    const barcode = row.querySelector('.field-barcode').value.trim();
    const skuEl = row.querySelector('.field-sku');
    const sku = (skuEl && skuEl.value.trim()) ? skuEl.value.trim() : (row.dataset.sku || barcode || String(id).padStart(5, '0'));
    const price = parseFloat(row.querySelector('.field-price').value) || 0;
    const qtyInput = row.querySelector('.field-label-qty');
    const qty = Math.max(1, parseInt(qtyInput ? qtyInput.value : 3) || 3);

    if (mode === 'barcode' && !barcode) {
        showToast('Cannot print barcode label: Product has no barcode!', true);
        return;
    }

    currentPrintPayload = {
        id: id,
        name: name,
        barcode: barcode,
        price: price,
        code: sku,
        quantity: qty,
        mode: mode
    };

    const cfg = getLabelConfig();
    if (cfg.direct_print) {
        executeThermalPrint(currentPrintPayload);
    } else {
        openLabelPreviewModal(currentPrintPayload);
    }
}

function executeThermalPrint(payload) {
    const cfg = getLabelConfig();
    const totalQty = payload.quantity;
    const rowsNeeded = Math.ceil(totalQty / 3);
    const mode = payload.mode || 'barcode';

    let labelsCreated = 0;
    let rowsHtml = '';

    for (let r = 0; r < rowsNeeded; r++) {
        let itemsInRowHtml = '';
        for (let col = 0; col < 3; col++) {
            if (labelsCreated < totalQty) {
                if (mode === 'price_only') {
                    itemsInRowHtml += createPriceOnlyLabelHtml(payload, cfg);
                } else {
                    itemsInRowHtml += createSingleBarcodeLabelHtml(payload, cfg);
                }
                labelsCreated++;
            } else {
                // Blank spacer preserving 3-column physical thermal alignment
                itemsInRowHtml += '<div class="label-blank-3"></div>';
            }
        }
        rowsHtml += `<div class="label-row-3">${itemsInRowHtml}</div>`;
    }

    // Print through isolated iframe to eliminate host application bleed and extra blank sheets
    triggerThermalIframePrint(rowsHtml, cfg);
}

function createPriceOnlyLabelHtml(item, cfg) {
    const company = cfg.show_company ? escapeHtml(cfg.company_name) : '';
    const priceFormatted = `${cfg.price_prefix} ${item.price.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;

    return `
        <div class="label-item-3 label-item-price-only">
            ${company ? `<div class="lbl-company">${company}</div>` : ''}
            <div class="lbl-price-large">${priceFormatted}</div>
        </div>
    `;
}

function createSingleBarcodeLabelHtml(item, cfg) {
    // 1. Product Name (Bigger font, no company header above it)
    const nameHtml = `<div class="lbl-name" title="${escapeHtml(item.name)}">${escapeHtml(item.name)}</div>`;

    // 2. SVG Barcode (Bigger height & optimized density for 1D laser scanners)
    let barcodeSvg = '';
    if (window.CurtissCode128 && typeof CurtissCode128.generateSVG === 'function') {
        barcodeSvg = CurtissCode128.generateSVG(item.barcode, {
            width: 1.1,
            height: 36,
            quietZone: 8,
            preserveAspectRatio: 'none',
            showText: false
        });
    } else {
        barcodeSvg = `<div style="font-family:monospace; font-weight:800; font-size:8pt;">*${escapeHtml(item.barcode)}*</div>`;
    }

    // 3. Product SKU / Code (Bigger font under barcode)
    const skuHtml = (cfg.show_sku !== false)
        ? `<div class="lbl-sku">${escapeHtml(item.code)}</div>` 
        : '';

    // 4. Price at bottom
    const priceFormatted = `${cfg.price_prefix} ${item.price.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;

    return `
        <div class="label-item-3">
            ${nameHtml}
            <div class="lbl-barcode">${barcodeSvg}</div>
            ${skuHtml}
            <div class="lbl-bottom">
                <span class="lbl-price">${priceFormatted}</span>
            </div>
        </div>
    `;
}

function triggerThermalIframePrint(rowsHtml, cfg) {
    let iframe = document.getElementById('thermalPrintFrame');
    if (!iframe) {
        iframe = document.createElement('iframe');
        iframe.id = 'thermalPrintFrame';
        iframe.style.position = 'fixed';
        iframe.style.top = '-9999px';
        iframe.style.left = '-9999px';
        iframe.style.width = '0';
        iframe.style.height = '0';
        iframe.style.border = 'none';
        document.body.appendChild(iframe);
    }

    const doc = iframe.contentWindow.document;
    doc.open();
    doc.write(`<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Falcon Thermal Print</title>
    <style>
        @page {
            size: ${cfg.row_width_mm}mm ${cfg.label_height_mm}mm;
            margin: 0mm !important;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        html, body {
            width: ${cfg.row_width_mm}mm !important;
            margin: 0 !important;
            padding: 0 !important;
            background: #ffffff !important;
            overflow: hidden !important;
        }
        .label-row-3 {
            display: flex !important;
            flex-direction: row !important;
            width: ${cfg.row_width_mm}mm !important;
            height: ${cfg.label_height_mm}mm !important;
            max-height: ${cfg.label_height_mm}mm !important;
            margin: 0 !important;
            padding: 0 1.5mm !important;
            box-sizing: border-box !important;
            justify-content: space-between !important;
            overflow: hidden !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
            page-break-after: always !important;
            break-after: page !important;
        }
        .label-row-3:last-child {
            page-break-after: avoid !important;
            break-after: avoid !important;
        }
        .label-item-3 {
            width: ${cfg.label_width_mm}mm !important;
            max-width: ${cfg.label_width_mm}mm !important;
            height: 14.8mm !important;
            max-height: 14.8mm !important;
            padding: 0.3mm 0.5mm !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: space-between !important;
            text-align: center !important;
            box-sizing: border-box !important;
            overflow: hidden !important;
            background: #ffffff !important;
            color: #000000 !important;
        }
        .label-item-price-only {
            justify-content: center !important;
            gap: 1mm !important;
            padding: 0.5mm 0.5mm !important;
        }
        .label-blank-3 {
            width: ${cfg.label_width_mm}mm !important;
            height: 14.8mm !important;
            visibility: hidden !important;
        }
        .lbl-company {
            font-family: Arial, Helvetica, sans-serif !important;
            font-size: 4.8pt !important;
            font-weight: 900 !important;
            letter-spacing: 0px !important;
            text-transform: uppercase !important;
            line-height: 1 !important;
            margin: 0 !important;
            color: #000000 !important;
            width: 100% !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
        }
        .lbl-name {
            font-family: Arial, Helvetica, sans-serif !important;
            font-size: 7.2pt !important;
            font-weight: 800 !important;
            line-height: 1.1 !important;
            margin: 0 !important;
            max-height: 3.2mm !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            white-space: nowrap !important;
            width: 100% !important;
            color: #000000 !important;
        }
        .lbl-barcode {
            width: 96% !important;
            height: 6.2mm !important;
            max-height: 6.2mm !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            margin: 0.2mm auto !important;
            overflow: hidden !important;
        }
        .lbl-barcode svg {
            width: 100% !important;
            height: 100% !important;
            max-height: 6.2mm !important;
        }
        .lbl-sku {
            font-family: 'Courier New', Courier, monospace !important;
            font-size: 7.5pt !important;
            font-weight: 900 !important;
            color: #000000 !important;
            width: 100% !important;
            text-align: center !important;
            line-height: 1 !important;
            margin: 0.2mm 0 0 0 !important;
            letter-spacing: 0.3px !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
        }
        .lbl-bottom {
            width: 100% !important;
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            line-height: 1 !important;
            margin: 0 !important;
            text-align: center !important;
        }
        .lbl-price {
            font-family: Arial, Helvetica, sans-serif !important;
            font-size: 8.5pt !important;
            font-weight: 900 !important;
            color: #000000 !important;
            white-space: nowrap !important;
            text-align: center !important;
        }
        .lbl-price-large {
            font-family: Arial, Helvetica, sans-serif !important;
            font-size: 13pt !important;
            font-weight: 900 !important;
            color: #000000 !important;
            line-height: 1.1 !important;
            text-align: center !important;
            white-space: nowrap !important;
            letter-spacing: -0.2px !important;
        }
    </style>
</head>
<body>
    ${rowsHtml}
</body>
</html>`);
    doc.close();

    setTimeout(() => {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
    }, 160);
}

// ── Preview Modal Handler ─────────────────────────────────────────────────────
function openLabelPreviewModal(payload) {
    const modal = document.getElementById('modalLabelPreview');
    const cfg = getLabelConfig();

    const titleEl = document.getElementById('prevModalTitle');
    if (titleEl) {
        titleEl.textContent = (payload.mode === 'price_only')
            ? '3-Up Price Label Preview (Price & Company)'
            : '3-Up Barcode Label Preview';
    }

    document.getElementById('prevProdName').textContent = payload.name;
    document.getElementById('prevProdBarcode').textContent = payload.barcode || 'N/A';
    document.getElementById('prevModalQty').value = payload.quantity;

    renderPreviewSheet(payload, cfg);
    modal.classList.add('active');
}

function renderPreviewSheet(payload, cfg) {
    const container = document.getElementById('previewSheetWrapper');
    container.innerHTML = '';

    const totalQty = parseInt(document.getElementById('prevModalQty').value) || payload.quantity || 3;
    payload.quantity = totalQty;

    const rowsNeeded = Math.ceil(totalQty / 3);
    const modeLabel = (payload.mode === 'price_only') ? 'Price Tag (Company & Price)' : 'Barcode Label';
    document.getElementById('prevStatsText').innerHTML = 
        `Mode: <strong>${modeLabel}</strong> &bull; Total Labels: <strong>${totalQty}</strong> &bull; Total Rows: <strong>${rowsNeeded}</strong>`;

    let labelsRendered = 0;
    for (let r = 0; r < rowsNeeded; r++) {
        const rowDiv = document.createElement('div');
        rowDiv.style.display = 'flex';
        rowDiv.style.gap = '8px';
        rowDiv.style.justifyContent = 'center';
        rowDiv.style.marginBottom = '8px';

        for (let col = 0; col < 3; col++) {
            if (labelsRendered < totalQty) {
                rowDiv.appendChild(createPreviewCardElement(payload, cfg));
                labelsRendered++;
            } else {
                const blankCard = document.createElement('div');
                blankCard.style.width = '160px';
                blankCard.style.height = '85px';
                blankCard.style.border = '1.5px dashed #cbd5e1';
                blankCard.style.borderRadius = '6px';
                blankCard.style.display = 'flex';
                blankCard.style.alignItems = 'center';
                blankCard.style.justifyContent = 'center';
                blankCard.style.color = '#94a3b8';
                blankCard.style.fontSize = '10px';
                blankCard.style.fontWeight = '700';
                blankCard.textContent = '[Blank Spacer]';
                rowDiv.appendChild(blankCard);
            }
        }
        container.appendChild(rowDiv);
    }
}

function createPreviewCardElement(item, cfg) {
    const card = document.createElement('div');
    card.style.width = '160px';
    card.style.height = '85px';
    card.style.background = '#ffffff';
    card.style.border = '1px solid #0f172a';
    card.style.borderRadius = '6px';
    card.style.padding = '5px 6px';
    card.style.display = 'flex';
    card.style.flexDirection = 'column';
    card.style.alignItems = 'center';
    card.style.textAlign = 'center';
    card.style.boxSizing = 'border-box';
    card.style.boxShadow = '0 1px 4px rgba(0,0,0,0.06)';

    const priceFormatted = `${cfg.price_prefix} ${item.price.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;

    // Price-Only Card Preview
    if (item.mode === 'price_only') {
        card.style.justifyContent = 'center';
        card.style.gap = '6px';
        card.innerHTML = `
            <div style="width: 100%; font-size: 9px; font-weight: 900; letter-spacing: 0.3px; text-transform: uppercase; color: #475569; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1;">
                ${escapeHtml(cfg.company_name)}
            </div>
            <div style="font-family: Arial, Helvetica, sans-serif; font-weight: 900; color: #0f172a; font-size: 20px; line-height: 1;">
                ${priceFormatted}
            </div>
        `;
        return card;
    }

    // Barcode Card Preview
    card.style.justifyContent = 'space-between';
    let barcodeSvg = '';
    if (window.CurtissCode128 && typeof CurtissCode128.generateSVG === 'function') {
        barcodeSvg = CurtissCode128.generateSVG(item.barcode, {
            width: 1.1,
            height: 28,
            quietZone: 8,
            preserveAspectRatio: 'none',
            showText: false
        });
    }

    const skuHtml = (cfg.show_sku !== false)
        ? `<div style="width: 100%; font-family: 'Courier New', Courier, monospace; font-size: 10px; font-weight: 900; color: #000000; letter-spacing: 0.5px; line-height: 1; text-align: center;">${escapeHtml(item.code)}</div>`
        : '';

    card.innerHTML = `
        <div style="width: 100%; font-size: 10px; font-weight: 800; margin: 0 0 1px 0; max-height: 20px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #0f172a;">
            ${escapeHtml(item.name)}
        </div>
        <div style="width: 96%; height: 28px; display: flex; align-items: center; justify-content: center; overflow: hidden; margin: 0 auto;">
            ${barcodeSvg}
        </div>
        ${skuHtml}
        <div style="width: 100%; text-align: center; line-height: 1;">
            <span style="font-weight: 900; color: #0f172a; font-size: 12px;">${priceFormatted}</span>
        </div>
    `;

    return card;
}

function updateModalPreviewQty() {
    if (!currentPrintPayload) return;
    const cfg = getLabelConfig();
    const qty = parseInt(document.getElementById('prevModalQty').value) || 1;
    currentPrintPayload.quantity = Math.max(1, qty);
    renderPreviewSheet(currentPrintPayload, cfg);
}

function adjustModalQty(delta) {
    const input = document.getElementById('prevModalQty');
    let q = parseInt(input.value) || 3;
    q = Math.max(1, q + delta);
    input.value = q;
    updateModalPreviewQty();
}

function executeModalPrint() {
    if (!currentPrintPayload) return;
    const qty = parseInt(document.getElementById('prevModalQty').value) || currentPrintPayload.quantity || 3;
    currentPrintPayload.quantity = qty;

    // Check direct print checkbox
    const directChk = document.getElementById('chkSkipPreview');
    if (directChk && directChk.checked) {
        const cfg = getLabelConfig();
        cfg.direct_print = true;
        localStorage.setItem('falcon_3up_label_cfg', JSON.stringify(cfg));
    }

    closeQeModal('modalLabelPreview');
    executeThermalPrint(currentPrintPayload);
}

// ── Configuration Modal ───────────────────────────────────────────────────────
function openLabelConfigModal() {
    const cfg = getLabelConfig();
    document.getElementById('cfgCompanyName').value = cfg.company_name;
    document.getElementById('cfgShowCompany').checked = cfg.show_company;
    document.getElementById('cfgShowSku').checked = (cfg.show_sku !== false);
    document.getElementById('cfgPricePrefix').value = cfg.price_prefix;
    document.getElementById('cfgLabelWidth').value = cfg.label_width_mm;
    document.getElementById('cfgLabelHeight').value = cfg.label_height_mm;
    document.getElementById('cfgRowWidth').value = cfg.row_width_mm;
    document.getElementById('cfgGapX').value = cfg.gap_x_mm;
    document.getElementById('cfgGapY').value = cfg.gap_y_mm;
    document.getElementById('cfgDirectPrint').checked = cfg.direct_print;

    document.getElementById('modalLabelConfig').classList.add('active');
}

function saveLabelConfigFromModal() {
    const cfg = {
        company_name: document.getElementById('cfgCompanyName').value.trim() || 'Falcon Stationery (PVT) LTD',
        show_company: document.getElementById('cfgShowCompany').checked,
        show_sku: document.getElementById('cfgShowSku').checked,
        price_prefix: document.getElementById('cfgPricePrefix').value.trim() || 'Rs.',
        label_width_mm: parseFloat(document.getElementById('cfgLabelWidth').value) || 31.5,
        label_height_mm: parseFloat(document.getElementById('cfgLabelHeight').value) || 16.0,
        row_width_mm: parseFloat(document.getElementById('cfgRowWidth').value) || 100.0,
        gap_x_mm: parseFloat(document.getElementById('cfgGapX').value) || 2.0,
        gap_y_mm: parseFloat(document.getElementById('cfgGapY').value) || 2.0,
        direct_print: document.getElementById('cfgDirectPrint').checked
    };

    localStorage.setItem('falcon_3up_label_cfg', JSON.stringify(cfg));
    applyLabelConfigCss();
    closeQeModal('modalLabelConfig');
    showToast('Label setup calibrated successfully!');
}

function resetLabelConfigDefaults() {
    if (confirm('Reset label dimensions and settings back to factory defaults (30x15mm, 3 per row)?')) {
        localStorage.removeItem('falcon_3up_label_cfg');
        applyLabelConfigCss();
        openLabelConfigModal();
        showToast('Reset to 3-Up thermal defaults!');
    }
}

// ─── Delete Product (Move to Trash Bin) ──────────────────────────────────────
function deleteProduct(id, name) {
    if (!confirm(`Are you sure you want to delete "${name}"?\nThis will move the product to the Trash Bin.`)) {
        return;
    }

    const row = document.getElementById('row_' + id);
    const formData = new FormData();
    formData.append('action', 'delete_product');
    formData.append('id', id);

    fetch('quick_products.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            if (row) {
                row.style.transition = 'all 0.28s ease';
                row.style.opacity = '0';
                row.style.transform = 'translateX(25px)';
                setTimeout(() => {
                    row.remove();
                    const rowCount = document.getElementById('qeRowCount');
                    const totalCount = document.getElementById('qeTotalCount');
                    if (rowCount) rowCount.textContent = Math.max(0, parseInt(rowCount.textContent) - 1);
                    if (totalCount) totalCount.textContent = Math.max(0, parseInt(totalCount.textContent) - 1);
                }, 280);
            }
            showToast(res.message || 'Product moved to Trash Bin');
        } else {
            showToast(res.message || 'Failed to delete product', true);
        }
    })
    .catch(err => {
        showToast('Network error while deleting product.', true);
        console.error(err);
    });
}

// ─── Quick Add Product Modal & Submission ─────────────────────────────────────
function openAddProductModal() {
    document.getElementById('add_name').value = '';
    document.getElementById('add_barcode').value = '';
    document.getElementById('add_cost').value = '0.00';
    document.getElementById('add_selling_price').value = '0.00';
    document.getElementById('add_stock').value = '0';

    document.getElementById('modalAddProduct').classList.add('active');
    setTimeout(() => {
        document.getElementById('add_name').focus();
    }, 100);
}

function generateAutoBarcode() {
    // Generate a clean 12-digit EAN/Code-128 numeric barcode
    const ts = Date.now().toString().slice(-7);
    const rnd = Math.floor(10000 + Math.random() * 90000).toString();
    const barcode = ts + rnd;
    document.getElementById('add_barcode').value = barcode;
}

function submitAddProduct(keepOpen = false) {
    const nameInput = document.getElementById('add_name');
    const skuInput = document.getElementById('add_sku');
    const barcodeInput = document.getElementById('add_barcode');
    const costInput = document.getElementById('add_cost');
    const priceInput = document.getElementById('add_selling_price');
    const stockInput = document.getElementById('add_stock');

    const name = nameInput.value.trim();
    const sku = skuInput ? skuInput.value.trim() : '';
    const barcode = barcodeInput.value.trim();
    const cost = parseFloat(costInput.value) || 0;
    const price = parseFloat(priceInput.value) || 0;
    const stock = parseFloat(stockInput.value) || 0;

    if (!name) {
        showToast('Product Name is required!', true);
        nameInput.focus();
        return;
    }

    if (!barcode) {
        showToast('Barcode is required!', true);
        barcodeInput.focus();
        return;
    }

    const btn = keepOpen ? document.getElementById('btnSubmitAddAndAnother') : document.getElementById('btnSubmitAdd');
    const origText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = 'Saving...';

    const formData = new FormData();
    formData.append('action', 'add_product');
    formData.append('name', name);
    formData.append('sku', sku);
    formData.append('barcode', barcode);
    formData.append('cost', cost);
    formData.append('selling_price', price);
    formData.append('stock', stock);

    fetch('quick_products.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        btn.innerHTML = origText;

        if (res.success && res.product) {
            const p = res.product;
            prependProductRow(p);

            showToast(res.message || `Product '${p.name}' created successfully!`);

            if (keepOpen) {
                nameInput.value = '';
                if (skuInput) skuInput.value = '';
                barcodeInput.value = '';
                costInput.value = '0.00';
                priceInput.value = '0.00';
                stockInput.value = '0';
                nameInput.focus();
            } else {
                closeQeModal('modalAddProduct');
            }
        } else {
            showToast(res.message || 'Failed to add product', true);
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = origText;
        showToast('Network error while creating product.', true);
        console.error(err);
    });
}

function prependProductRow(p) {
    const tbody = document.getElementById('qeTableBody');
    const noRows = document.getElementById('noRowsNotice');
    if (noRows) noRows.remove();

    const rowHtml = `
        <tr id="row_${p.id}" data-id="${p.id}" data-sku="${escapeHtml(p.sku || p.barcode)}" class="row-saved">
            <td>
                <span style="font-weight: 800; color: #475569;">#${p.id}</span>
            </td>
            <td>
                <input type="text" class="qe-input field-name" value="${escapeHtml(p.name)}" 
                       data-original="${escapeHtml(p.name)}" oninput="markDirty(${p.id})" onkeydown="checkEnter(event, ${p.id})">
            </td>
            <td>
                <input type="text" class="qe-input field-sku" style="font-family: monospace;" value="${escapeHtml(p.sku || '')}" 
                       data-original="${escapeHtml(p.sku || '')}" placeholder="SKU" oninput="markDirty(${p.id})" onkeydown="checkEnter(event, ${p.id})">
            </td>
            <td>
                <input type="text" class="qe-input field-barcode" style="font-family: monospace;" value="${escapeHtml(p.barcode)}" 
                       data-original="${escapeHtml(p.barcode)}" oninput="markDirty(${p.id})" onkeydown="checkEnter(event, ${p.id})">
            </td>
            <td>
                <input type="number" step="0.01" min="0" class="qe-input qe-input-num field-cost" value="${parseFloat(p.cost || 0).toFixed(2)}" 
                       data-original="${parseFloat(p.cost || 0).toFixed(2)}" oninput="markDirty(${p.id})" onkeydown="checkEnter(event, ${p.id})">
            </td>
            <td>
                <input type="number" step="0.01" min="0" class="qe-input qe-input-num field-price" value="${parseFloat(p.selling_price || 0).toFixed(2)}" 
                       data-original="${parseFloat(p.selling_price || 0).toFixed(2)}" oninput="markDirty(${p.id})" onkeydown="checkEnter(event, ${p.id})">
            </td>
            <td>
                <input type="number" step="1" class="qe-input qe-input-num qe-input-stock field-stock" value="${parseFloat(p.stock || 0)}" 
                       data-original="${parseFloat(p.stock || 0)}" oninput="markDirty(${p.id})" onkeydown="checkEnter(event, ${p.id})">
            </td>
            <td style="text-align: center;">
                <div style="display: flex; align-items: center; justify-content: center; gap: 5px;">
                    <button type="button" class="qe-btn-update" id="btn_${p.id}" onclick="saveProduct(${p.id})">
                        Update
                    </button>
                    <div style="display: flex; align-items: center; gap: 3px; border-left: 1px solid #e2e8f0; padding-left: 5px;">
                        <input type="number" step="1" min="1" max="999" class="qe-input field-label-qty" value="3" 
                               title="Label Quantity to print (3 per row)" onkeydown="if(event.key==='Enter') printRowLabel(${p.id})">
                        <button type="button" class="qe-btn-print" onclick="printRowLabel(${p.id})" title="Print Full Barcode Label (3 stickers in row)">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                            Print
                        </button>
                        <button type="button" class="qe-btn-price" onclick="printRowPriceOnly(${p.id})" title="Print Price Only Labels (Company & Price)">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                            Price
                        </button>
                    </div>
                    <button type="button" class="qe-btn-delete" onclick="deleteProduct(${p.id}, '${escapeHtml(p.name)}')" title="Delete Product (Move to Trash Bin)">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    </button>
                </div>
            </td>
        </tr>
    `;

    tbody.insertAdjacentHTML('afterbegin', rowHtml);

    const rowCount = document.getElementById('qeRowCount');
    const totalCount = document.getElementById('qeTotalCount');
    if (rowCount) rowCount.textContent = parseInt(rowCount.textContent || 0) + 1;
    if (totalCount) totalCount.textContent = parseInt(totalCount.textContent || 0) + 1;

    setTimeout(() => {
        const addedRow = document.getElementById('row_' + p.id);
        if (addedRow) addedRow.classList.remove('row-saved');
    }, 2500);
}

function closeQeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.remove('active');
}

// Close modals on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeQeModal('modalAddProduct');
        closeQeModal('modalLabelPreview');
        closeQeModal('modalLabelConfig');
    }
});
</script>

<!-- Hidden Container for Printing Thermal 3-Up Labels -->
<div id="printLabelContainer"></div>

<!-- ══════════════════════════════════════════════════════════════
     MODAL: QUICK ADD PRODUCT
     ══════════════════════════════════════════════════════════════ -->
<div class="qe-modal-overlay" id="modalAddProduct">
    <div class="qe-modal-box" style="width: 580px;">
        <div class="qe-modal-header">
            <div>
                <div style="font-weight: 800; font-size: 1.15rem; color: #0f172a;">+ Add New Product</div>
                <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;">Fast inline catalog creation & initial stock entry</div>
            </div>
            <button type="button" onclick="closeQeModal('modalAddProduct')" style="background:none; border:none; font-size:1.5rem; color:#64748b; cursor:pointer; line-height:1;">&times;</button>
        </div>

        <div class="qe-modal-body" style="background:#ffffff;">
            <form onsubmit="event.preventDefault(); submitAddProduct(false);">
                <!-- Product Name -->
                <div style="margin-bottom: 14px;">
                    <label style="display:block; font-size: 0.82rem; font-weight: 700; color: #334155; margin-bottom: 4px;">Product Name *</label>
                    <input type="text" id="add_name" class="qe-input" placeholder="e.g. Atlas CR Exercise Book 120 Pages" required autofocus>
                </div>

                <!-- SKU & Barcode in grid -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                    <div>
                        <label style="display:block; font-size: 0.82rem; font-weight: 700; color: #334155; margin-bottom: 4px;">SKU <span style="font-size: 0.72rem; color: #64748b; font-weight: 400;">(Optional)</span></label>
                        <input type="text" id="add_sku" class="qe-input" placeholder="e.g. PEN-BLU-001 (auto if blank)" style="font-family: monospace;">
                    </div>
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                            <label style="font-size: 0.82rem; font-weight: 700; color: #334155;">Barcode *</label>
                            <button type="button" onclick="generateAutoBarcode()" style="background: none; border: none; font-size: 0.72rem; color: #2563eb; cursor: pointer; font-weight: 700; padding: 0;" title="Auto-generate numeric barcode">⚡ Generate</button>
                        </div>
                        <input type="text" id="add_barcode" class="qe-input" placeholder="Scan or enter barcode" style="font-family: monospace;" required>
                    </div>
                </div>

                <!-- Cost, Price, Stock -->
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 8px;">
                    <div>
                        <label style="display:block; font-size: 0.8rem; font-weight: 700; color: #334155; margin-bottom: 4px;">Cost (LKR)</label>
                        <input type="number" step="0.01" min="0" id="add_cost" class="qe-input qe-input-num" value="0.00" placeholder="0.00" onkeydown="if(event.key==='Enter') submitAddProduct(false)">
                    </div>
                    <div>
                        <label style="display:block; font-size: 0.8rem; font-weight: 700; color: #334155; margin-bottom: 4px;">Selling Price (LKR) *</label>
                        <input type="number" step="0.01" min="0" id="add_selling_price" class="qe-input qe-input-num" value="0.00" placeholder="0.00" required onkeydown="if(event.key==='Enter') submitAddProduct(false)">
                    </div>
                    <div>
                        <label style="display:block; font-size: 0.8rem; font-weight: 700; color: #334155; margin-bottom: 4px;">Stock (Live) *</label>
                        <input type="number" step="1" min="0" id="add_stock" class="qe-input qe-input-num qe-input-stock" value="0" placeholder="0" required onkeydown="if(event.key==='Enter') submitAddProduct(false)">
                    </div>
                </div>
            </form>
        </div>

        <div class="qe-modal-footer">
            <span style="font-size: 0.76rem; color: #94a3b8;">Enter submits form automatically</span>
            <div style="display: flex; gap: 8px;">
                <button type="button" class="btn btn-secondary" onclick="closeQeModal('modalAddProduct')" style="padding: 0.5rem 1rem; border-radius: 8px; font-weight: 700; border: 1px solid #cbd5e1; background: #ffffff; cursor: pointer;">
                    Cancel
                </button>
                <button type="button" class="btn btn-primary" id="btnSubmitAddAndAnother" onclick="submitAddProduct(true)" style="padding: 0.5rem 1.1rem; border-radius: 8px; font-weight: 700; background: #1e293b; color: #ffffff; border: none; cursor: pointer;">
                    Save &amp; Add Another
                </button>
                <button type="button" class="btn btn-primary" id="btnSubmitAdd" onclick="submitAddProduct(false)" style="padding: 0.5rem 1.3rem; border-radius: 8px; font-weight: 800; background: #0f172a; color: #ffffff; border: none; cursor: pointer;">
                    Save Product
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     MODAL: LIVE PRINT PREVIEW (3 STICKERS PER ROW)
     ══════════════════════════════════════════════════════════════ -->
<div class="qe-modal-overlay" id="modalLabelPreview">
    <div class="qe-modal-box">
        <div class="qe-modal-header">
            <div>
                <div id="prevModalTitle" style="font-weight: 800; font-size: 1.1rem; color: #0f172a;">3-Up Barcode Label Preview</div>
                <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;">
                    <span id="prevProdName" style="font-weight: 700; color: #0f172a;"></span> &bull; BC: <code id="prevProdBarcode" style="color:#0284c7;"></code>
                </div>
            </div>
            <button type="button" onclick="closeQeModal('modalLabelPreview')" style="background:none; border:none; font-size:1.5rem; color:#64748b; cursor:pointer; line-height:1;">&times;</button>
        </div>

        <div class="qe-modal-body">
            <!-- Quantity Toolbar -->
            <div style="display: flex; justify-content: space-between; align-items: center; background: #ffffff; padding: 10px 14px; border-radius: 10px; border: 1px solid #e2e8f0; margin-bottom: 14px;">
                <div id="prevStatsText" style="font-size: 0.85rem; color: #475569;">
                    Layout: <strong>3 stickers in row</strong>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <label style="font-size: 0.82rem; font-weight: 700; color: #334155;">Labels QTY:</label>
                    <div style="display: flex; align-items: center; border: 1.5px solid #cbd5e1; border-radius: 8px; overflow: hidden; background: #ffffff;">
                        <button type="button" onclick="adjustModalQty(-3)" style="border:none; background:#f1f5f9; padding: 4px 10px; font-weight:800; cursor:pointer;" title="Minus 1 Row (-3)">-3</button>
                        <input type="number" id="prevModalQty" min="1" max="999" value="3" onchange="updateModalPreviewQty()" style="width: 50px; border:none; text-align:center; font-weight:800; font-size:0.9rem; outline:none;">
                        <button type="button" onclick="adjustModalQty(3)" style="border:none; background:#f1f5f9; padding: 4px 10px; font-weight:800; cursor:pointer;" title="Plus 1 Row (+3)">+3</button>
                    </div>
                </div>
            </div>

            <!-- Visual Preview Sheet of 3-across stickers -->
            <div id="previewSheetWrapper" style="max-height: 400px; overflow-y: auto; padding: 6px;"></div>
        </div>

        <div class="qe-modal-footer">
            <label style="display: flex; align-items: center; gap: 6px; font-size: 0.82rem; color: #64748b; cursor: pointer;">
                <input type="checkbox" id="chkSkipPreview"> Skip preview and print directly on table button click
            </label>
            <div style="display: flex; gap: 8px;">
                <button type="button" class="btn btn-secondary" onclick="closeQeModal('modalLabelPreview')" style="padding: 0.5rem 1rem; border-radius: 8px; font-weight: 700; border: 1px solid #cbd5e1; background: #ffffff; cursor: pointer;">
                    Cancel
                </button>
                <button type="button" class="btn btn-primary" onclick="executeModalPrint()" style="display: inline-flex; align-items: center; gap: 6px; padding: 0.5rem 1.3rem; border-radius: 8px; font-weight: 800; background: #0f172a; color: #ffffff; border: none; cursor: pointer;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                    Print Labels Now
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     MODAL: LABEL CALIBRATION & SETUP (3-UP)
     ══════════════════════════════════════════════════════════════ -->
<div class="qe-modal-overlay" id="modalLabelConfig">
    <div class="qe-modal-box" style="width: 580px;">
        <div class="qe-modal-header">
            <div>
                <div style="font-weight: 800; font-size: 1.1rem; color: #0f172a;">3-Up Barcode Label Setup</div>
                <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;">Calibrate sticker roll sizing & header text</div>
            </div>
            <button type="button" onclick="closeQeModal('modalLabelConfig')" style="background:none; border:none; font-size:1.5rem; color:#64748b; cursor:pointer; line-height:1;">&times;</button>
        </div>

        <div class="qe-modal-body" style="background:#ffffff;">
            <!-- Company Name -->
            <div style="margin-bottom: 14px;">
                <label style="display:block; font-size: 0.82rem; font-weight: 700; color: #334155; margin-bottom: 4px;">Company Name Header</label>
                <input type="text" id="cfgCompanyName" class="qe-input" value="Falcon Stationery" placeholder="e.g. Falcon Stationery">
            </div>

            <!-- Options -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                <label style="display: flex; align-items: center; gap: 8px; font-size: 0.82rem; color: #334155; cursor: pointer;">
                    <input type="checkbox" id="cfgShowCompany" checked> Print Company Header
                </label>
                <label style="display: flex; align-items: center; gap: 8px; font-size: 0.82rem; color: #334155; cursor: pointer;">
                    <input type="checkbox" id="cfgShowSku" checked> Print Product SKU Under Barcode
                </label>
            </div>

            <!-- Price Prefix -->
            <div style="margin-bottom: 14px;">
                <label style="display:block; font-size: 0.82rem; font-weight: 700; color: #334155; margin-bottom: 4px;">Price Prefix</label>
                <input type="text" id="cfgPricePrefix" class="qe-input" value="Rs." style="width: 120px;">
            </div>

            <!-- Sizing Controls (Millimeters) -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px; margin-bottom: 14px;">
                <div style="font-weight: 800; font-size: 0.85rem; color: #0f172a; margin-bottom: 10px;">
                    Millimeter Calibration (Default 3-Up: 30mm x 15mm on 99mm / 3.90in Web)
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label style="display:block; font-size: 0.78rem; font-weight: 700; color: #475569; margin-bottom: 4px;">Sticker Width (mm)</label>
                        <input type="number" step="0.5" id="cfgLabelWidth" class="qe-input" value="30.0">
                    </div>
                    <div>
                        <label style="display:block; font-size: 0.78rem; font-weight: 700; color: #475569; margin-bottom: 4px;">Sticker Length / Height (mm)</label>
                        <input type="number" step="0.5" id="cfgLabelHeight" class="qe-input" value="15.0">
                    </div>
                    <div>
                        <label style="display:block; font-size: 0.78rem; font-weight: 700; color: #475569; margin-bottom: 4px;">Total Roll Web Width (mm)</label>
                        <input type="number" step="0.5" id="cfgRowWidth" class="qe-input" value="99.0">
                    </div>
                    <div>
                        <label style="display:block; font-size: 0.78rem; font-weight: 700; color: #475569; margin-bottom: 4px;">Horizontal Gap (mm)</label>
                        <input type="number" step="0.5" id="cfgGapX" class="qe-input" value="3.0">
                    </div>
                </div>
                <div style="margin-top: 10px;">
                    <label style="display:block; font-size: 0.78rem; font-weight: 700; color: #475569; margin-bottom: 4px;">Vertical Pitch / Row Gap (mm)</label>
                    <input type="number" step="0.5" id="cfgGapY" class="qe-input" value="0.0" style="width: 120px;">
                </div>
            </div>

            <!-- Direct print option -->
            <div>
                <label style="display: flex; align-items: center; gap: 8px; font-size: 0.84rem; color: #0f172a; font-weight: 600; cursor: pointer;">
                    <input type="checkbox" id="cfgDirectPrint"> Direct 1-Click Print (Bypass Preview Modal)
                </label>
            </div>
        </div>

        <div class="qe-modal-footer">
            <button type="button" onclick="resetLabelConfigDefaults()" style="background:none; border:none; color:#dc2626; font-size:0.82rem; font-weight:700; cursor:pointer;">
                Reset Factory Defaults
            </button>
            <div style="display: flex; gap: 8px;">
                <button type="button" class="btn btn-secondary" onclick="closeQeModal('modalLabelConfig')" style="padding: 0.5rem 1rem; border-radius: 8px; font-weight: 700; border: 1px solid #cbd5e1; background: #ffffff; cursor: pointer;">
                    Cancel
                </button>
                <button type="button" class="btn btn-primary" onclick="saveLabelConfigFromModal()" style="padding: 0.5rem 1.25rem; border-radius: 8px; font-weight: 800; background: #0f172a; color: #ffffff; border: none; cursor: pointer;">
                    Save Settings
                </button>
            </div>
        </div>
    </div>
</div>

