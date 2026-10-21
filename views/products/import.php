<?php
/**
 * Curtiss POS - Enhanced Product Master Import View (Section 1)
 * High-performance UI for uploading, previewing, validating, fixing, and committing 10,000+ product catalogs.
 * Theme: Curtiss Floating Glassmorphism
 */
?>

<!-- Include Searchable Select Assets -->
<link rel="stylesheet" href="assets/css/curtiss_select.css">
<script src="assets/js/curtiss_select.js"></script>

<style>
/* ── Product Import — Curtiss Floating Theme ────────────────────────── */

.imp-header {
    margin-bottom: 1.25rem;
}

.imp-title {
    font-size: 1.35rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.025em;
    line-height: 1.2;
    margin: 0;
}

.imp-card {
    background: rgba(255, 255, 255, 0.94);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(226, 232, 240, 0.85);
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 20px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
}

.imp-step-title {
    font-size: 14px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.01em;
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.imp-step-num {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #0f172a;
    color: #ffffff;
    font-size: 11px;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.imp-form-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 16px;
    margin-bottom: 16px;
}
@media (max-width: 768px) {
    .imp-form-grid { grid-template-columns: 1fr; }
}

.imp-label {
    display: block;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #94a3b8;
    margin-bottom: 6px;
}

.imp-file-input,
.imp-select {
    width: 100%;
    height: 40px;
    padding: 6px 12px;
    font-size: 12.5px;
    font-family: inherit;
    font-weight: 500;
    color: #0f172a;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    outline: none;
    transition: all 0.15s ease;
    box-sizing: border-box;
}

.imp-file-input:focus,
.imp-select:focus {
    background: #ffffff;
    border-color: #0f172a;
    box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.08);
}

.imp-opts-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    margin-bottom: 20px;
}
@media (max-width: 768px) {
    .imp-opts-grid { grid-template-columns: 1fr; }
}

.imp-opt-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 14px 16px;
    transition: background 0.15s ease, border-color 0.15s ease;
}
.imp-opt-box:hover {
    background: #ffffff;
    border-color: #cbd5e1;
}

.imp-opt-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12.5px;
    font-weight: 700;
    color: #0f172a;
    cursor: pointer;
}

.imp-opt-desc {
    display: block;
    font-size: 11px;
    color: #64748b;
    margin-top: 4px;
    padding-left: 23px;
    font-weight: 500;
    line-height: 1.4;
}

/* Action Buttons */
.imp-btn-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    height: 40px;
    padding: 0 24px;
    border-radius: 999px;
    background: #0f172a;
    color: #ffffff;
    font-size: 12.5px;
    font-weight: 700;
    border: none;
    cursor: pointer;
    transition: all 0.15s ease;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.2);
    font-family: inherit;
}
.imp-btn-primary:hover {
    background: #1e293b;
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.3);
}

.imp-btn-success {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    height: 42px;
    padding: 0 28px;
    border-radius: 999px;
    background: #16a34a;
    color: #ffffff;
    font-size: 13px;
    font-weight: 700;
    border: none;
    cursor: pointer;
    transition: all 0.15s ease;
    box-shadow: 0 4px 14px rgba(22, 163, 74, 0.25);
    font-family: inherit;
}
.imp-btn-success:hover {
    background: #15803d;
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(22, 163, 74, 0.35);
}

.imp-btn-secondary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    height: 34px;
    padding: 0 16px;
    border-radius: 999px;
    background: #ffffff;
    color: #334155;
    font-size: 12px;
    font-weight: 700;
    border: 1px solid #cbd5e1;
    cursor: pointer;
    transition: all 0.15s ease;
    font-family: inherit;
}
.imp-btn-secondary:hover {
    background: #f8fafc;
    border-color: #94a3b8;
    color: #0f172a;
}

/* Stat Cards in Preview */
.imp-stat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
    gap: 12px;
    margin-bottom: 20px;
}
.imp-stat-card {
    padding: 14px;
    border-radius: 12px;
    text-align: center;
    border: 1px solid transparent;
}
.imp-stat-val {
    font-size: 22px;
    font-weight: 900;
    letter-spacing: -0.02em;
    margin-top: 2px;
}
.imp-stat-lbl {
    font-size: 9.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}

.stat-total   { background: #f8fafc; border-color: #e2e8f0; color: #475569; }
.stat-total .imp-stat-val { color: #0f172a; }
.stat-valid   { background: #f0fdf4; border-color: rgba(22, 163, 74, 0.25); color: #166534; }
.stat-valid .imp-stat-val { color: #16a34a; }
.stat-issues  { background: #fef2f2; border-color: rgba(220, 38, 38, 0.25); color: #991b1b; }
.stat-issues .imp-stat-val { color: #dc2626; }
.stat-new     { background: #eff6ff; border-color: rgba(37, 99, 235, 0.2); color: #1e40af; }
.stat-new .imp-stat-val { color: #2563eb; }
.stat-update  { background: #faf5ff; border-color: rgba(147, 51, 234, 0.2); color: #6b21a8; }
.stat-update .imp-stat-val { color: #9333ea; }
.stat-skipped { background: #fffbeb; border-color: rgba(217, 119, 6, 0.2); color: #92400e; }
.stat-skipped .imp-stat-val { color: #d97706; }

/* ── Tab Switcher ── */
.imp-tabs-bar {
    display: flex;
    align-items: center;
    gap: 8px;
    border-bottom: 2px solid #e2e8f0;
    margin-bottom: 16px;
    padding-bottom: 2px;
}
.imp-tab-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    border-radius: 10px 10px 0 0;
    font-size: 13px;
    font-weight: 700;
    background: transparent;
    border: none;
    cursor: pointer;
    color: #64748b;
    transition: all 0.15s ease;
    border-bottom: 3px solid transparent;
    margin-bottom: -4px;
}
.imp-tab-btn:hover {
    color: #0f172a;
    background: rgba(241, 245, 249, 0.6);
}
.imp-tab-btn.active {
    color: #0f172a;
    border-bottom-color: #0f172a;
    background: #f8fafc;
}
.imp-tab-badge {
    padding: 2px 8px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 800;
}
.badge-tab-issues {
    background: #fee2e2;
    color: #dc2626;
}
.badge-tab-valid {
    background: #dcfce7;
    color: #16a34a;
}

/* ── Table & Zebra Striping ── */
.prod-table-scroll {
    overflow-y: auto;
    flex: 1;
    min-height: 0;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
}

.imp-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
}
.imp-table thead {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    position: sticky;
    top: 0;
    z-index: 10;
}
.imp-table thead th {
    padding: 10px 12px;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #64748b;
    text-align: left;
    white-space: nowrap;
    background: #f8fafc;
}
.imp-table tbody tr {
    height: 48px;
    min-height: 48px;
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.15s ease;
}
.imp-table tbody tr:nth-child(even) { background: rgba(248, 250, 252, 0.6); }
.imp-table tbody tr:nth-child(odd)  { background: #ffffff; }
.imp-table tbody tr:hover            { background: #f1f5f9 !important; }
.imp-table tbody td {
    padding: 6px 10px;
    color: #0f172a;
    vertical-align: middle;
    box-sizing: border-box;
}

/* Inline Edit Controls in Issues Table */
.imp-cell-input {
    width: 100%;
    height: 32px;
    padding: 4px 8px;
    font-size: 12px;
    font-family: inherit;
    font-weight: 600;
    color: #0f172a;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    outline: none;
    transition: all 0.15s ease;
    box-sizing: border-box;
}
.imp-cell-input:focus {
    border-color: #0f172a;
    box-shadow: 0 0 0 2px rgba(15, 23, 42, 0.1);
}
.imp-cell-input.input-has-error {
    border-color: #dc2626;
    background: #fffafa;
}

.imp-barcode-group {
    display: flex;
    align-items: center;
    gap: 4px;
}
.imp-gen-btn {
    height: 32px;
    width: 32px;
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    color: #0f172a;
    cursor: pointer;
    transition: all 0.15s ease;
}
.imp-gen-btn:hover {
    background: #e2e8f0;
    color: #2563eb;
    border-color: #94a3b8;
}

/* Badges */
.imp-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    border-radius: 999px;
    font-size: 10.5px;
    font-weight: 700;
    white-space: nowrap;
}
.badge-create  { background: #f0fdf4; color: #16a34a; border: 1px solid rgba(22, 163, 74, 0.2); }
.badge-update  { background: #eff6ff; color: #2563eb; border: 1px solid rgba(37, 99, 235, 0.2); }
.badge-skipped { background: #fffbeb; color: #d97706; border: 1px solid rgba(217, 119, 6, 0.2); }
.badge-invalid { background: #fef2f2; color: #dc2626; border: 1px solid rgba(220, 38, 38, 0.2); }

.issue-pill {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    padding: 2px 6px;
    border-radius: 6px;
    font-size: 10px;
    font-weight: 700;
    margin: 1px 2px;
}
.issue-pill-danger  { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }
.issue-pill-warning { background: #fef3c7; color: #b45309; border: 1px solid #fcd34d; }

.imp-code {
    font-family: 'SF Mono', 'Fira Code', 'Courier New', monospace;
    font-size: 11px;
    font-weight: 600;
    color: #475569;
    background: #f1f5f9;
    padding: 2px 6px;
    border-radius: 5px;
}

/* Row Action Buttons */
.imp-act-btn {
    height: 28px;
    min-height: 28px;
    line-height: 26px;
    padding: 0 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    cursor: pointer;
    border: none;
    transition: all 0.15s ease;
    font-family: inherit;
}
.imp-btn-fix {
    background: #dcfce7;
    color: #15803d;
    border: 1px solid #86efac;
}
.imp-btn-fix:hover {
    background: #bbf7d0;
}
.imp-btn-del {
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fca5a5;
}
.imp-btn-del:hover {
    background: #fee2e2;
}

/* ── Import Progress Modal ── */
.imp-backdrop {
    position: fixed;
    inset: 0;
    z-index: 9500;
    background: rgba(15, 23, 42, 0.35);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.2s ease;
}
.imp-backdrop.active {
    opacity: 1;
    pointer-events: auto;
}
.imp-modal {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(0.95);
    z-index: 9600;
    width: 380px;
    max-width: calc(100vw - 32px);
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    border: 1px solid rgba(226, 232, 240, 0.9);
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(15, 23, 42, 0.18), 0 4px 16px rgba(0, 0, 0, 0.06);
    padding: 22px 24px;
    opacity: 0;
    pointer-events: none;
    transition: all 0.22s cubic-bezier(0.22, 1, 0.36, 1);
}
.imp-modal.active {
    opacity: 1;
    pointer-events: auto;
    transform: translate(-50%, -50%) scale(1);
}
.imp-modal-header {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 16px;
}
.imp-icon-box {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.imp-spinner {
    animation: impSpin 1s linear infinite;
}
@keyframes impSpin {
    to { transform: rotate(360deg); }
}
.imp-modal-title {
    font-size: 14px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.01em;
}
.imp-modal-subtitle {
    font-size: 11px;
    font-weight: 500;
    color: #64748b;
    margin-top: 2px;
}
.imp-progress-wrapper {
    height: 8px;
    background: #e2e8f0;
    border-radius: 999px;
    overflow: hidden;
    margin-bottom: 12px;
    position: relative;
}
.imp-progress-bar {
    height: 100%;
    background: #0f172a;
    border-radius: 999px;
    transition: width 0.25s ease;
}
.imp-modal-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.imp-status-text {
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
}
.imp-close-btn {
    padding: 6px 16px;
    border-radius: 999px;
    background: #0f172a;
    color: #fff;
    border: none;
    font-size: 11.5px;
    font-weight: 700;
    cursor: pointer;
}
</style>

<!-- Top Right Fixed Floating Header Bubbles -->
<div id="curtiss-floating-header-right">
    <a href="api/import_products.php?action=template" class="top-bubble-pill top-action-btn" title="Download CSV Master Template">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Download CSV Template
    </a>
    <a href="products" class="top-bubble-pill top-action-btn top-btn-primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        Back to Product Master
    </a>
</div>

<!-- Page Title Header (Headline only per Curtiss rules) -->
<div class="imp-header">
    <h1 class="imp-title">Import Products</h1>
</div>

<!-- Step 1: Upload Master File & Options -->
<div class="imp-card">
    <div class="imp-step-title">
        <span class="imp-step-num">1</span>
        <span>Upload Master File & Options</span>
    </div>
    
    <form id="uploadForm" enctype="multipart/form-data">
        <div class="imp-form-grid">
            <div>
                <label class="imp-label" for="import_file">Select Product Master CSV File *</label>
                <input type="file" name="import_file" id="import_file" class="imp-file-input" accept=".csv, text/csv" required>
                <small style="display:block;color:#94a3b8;font-size:11px;margin-top:4px;font-weight:500;">Supported format: CSV (UTF-8). Preserves Barcode leading zeros (e.g. 001, 00001).</small>
            </div>

            <div>
                <label class="imp-label" for="import_mode">Import Mode *</label>
                <select name="import_mode" id="import_mode" class="imp-select" required>
                    <option value="create_and_update" selected>Create New & Update Existing</option>
                    <option value="update_existing_only">Update Existing Barcodes Only</option>
                    <option value="create_new_only">Create New Barcodes Only</option>
                </select>
            </div>
        </div>

        <div class="imp-opts-grid">
            <div class="imp-opt-box">
                <label class="imp-opt-label">
                    <input type="checkbox" name="ignore_blank_cells" id="ignore_blank_cells" value="1" checked style="accent-color:#0f172a;width:15px;height:15px;">
                    Ignore blank cells when updating existing products
                </label>
                <span class="imp-opt-desc">
                    Default ON. Prevents erasing existing Supplier, Brand, or Cost values if optional spreadsheet cell is blank.
                </span>
            </div>

            <div class="imp-opt-box">
                <label class="imp-opt-label">
                    <input type="checkbox" name="auto_create_masters" id="auto_create_masters" value="1" checked style="accent-color:#0f172a;width:15px;height:15px;">
                    Automatically create missing Brands, Categories & Suppliers
                </label>
                <span class="imp-opt-desc">
                    If an imported Brand, Category or Supplier does not exist, automatically register it instead of failing the row.
                </span>
            </div>
        </div>

        <div style="display: flex; justify-content: flex-end;">
            <button type="button" id="btnPreview" onclick="runImportPreview()" class="imp-btn-primary">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                Preview & Validate Import File
            </button>
        </div>
    </form>
</div>

<!-- Step 2: Validation & Dual-Tab Preview Box (Hidden initially) -->
<div id="previewCard" class="imp-card" style="display: none;">
    <div class="imp-step-title">
        <span class="imp-step-num">2</span>
        <span>Import Preview & Resolution Hub</span>
    </div>

    <!-- Summary Metrics Cards -->
    <div class="imp-stat-grid">
        <div class="imp-stat-card stat-total">
            <div class="imp-stat-lbl">Total Rows</div>
            <div id="statTotal" class="imp-stat-val">0</div>
        </div>
        <div class="imp-stat-card stat-valid">
            <div class="imp-stat-lbl">Valid & Ready</div>
            <div id="statValid" class="imp-stat-val">0</div>
        </div>
        <div class="imp-stat-card stat-issues">
            <div class="imp-stat-lbl">Issues Detected</div>
            <div id="statIssues" class="imp-stat-val">0</div>
        </div>
        <div class="imp-stat-card stat-new">
            <div class="imp-stat-lbl">New Products</div>
            <div id="statNew" class="imp-stat-val">0</div>
        </div>
        <div class="imp-stat-card stat-update">
            <div class="imp-stat-lbl">To Update</div>
            <div id="statUpdate" class="imp-stat-val">0</div>
        </div>
        <div class="imp-stat-card stat-lots" style="border-top: 3px solid #0284c7;">
            <div class="imp-stat-lbl">Price Lots</div>
            <div id="statLots" class="imp-stat-val">0</div>
        </div>
        <div class="imp-stat-card stat-skipped">
            <div class="imp-stat-lbl">Skipped</div>
            <div id="statSkipped" class="imp-stat-val">0</div>
        </div>
    </div>

    <!-- Tab Buttons -->
    <div class="imp-tabs-bar">
        <button type="button" id="tabBtnIssues" onclick="switchPreviewTab('issues')" class="imp-tab-btn active">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            Issues Detected
            <span id="badgeIssuesCount" class="imp-tab-badge badge-tab-issues">0</span>
        </button>
        <button type="button" id="tabBtnValid" onclick="switchPreviewTab('valid')" class="imp-tab-btn">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            Valid Products (Ready)
            <span id="badgeValidCount" class="imp-tab-badge badge-tab-valid">0</span>
        </button>
    </div>

    <!-- TAB 1: ISSUES TO FIX (Interactive Inline Edit) -->
    <div id="tabContentIssues">
        <!-- Quick Fix Action Toolbar -->
        <div id="issuesToolbar" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 12px; padding: 10px 14px; background: #fff8f8; border: 1px solid #fee2e2; border-radius: 10px;">
            <div style="font-size: 12px; font-weight: 700; color: #991b1b; display: flex; align-items: center; gap: 6px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                Fix issues inline below or use one-click helpers:
            </div>
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <button type="button" onclick="autoFixAllBarcodes()" class="imp-btn-secondary" title="Generate unique 13-digit retail barcodes for all rows with empty or duplicate barcodes">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.5"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                    ⚡ Auto-Fix All Barcodes
                </button>
                <button type="button" onclick="revalidateAllIssues()" class="imp-btn-secondary" title="Re-check all rows and move fixed items to Valid list">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    ✓ Re-Validate All
                </button>
                <button type="button" onclick="excludeAllIssues()" class="imp-btn-secondary" style="color: #dc2626; border-color: #fca5a5;" title="Skip all problematic rows and proceed only with valid products">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    ✕ Exclude All Issues
                </button>
            </div>
        </div>

        <!-- Issues Table Scroll Container -->
        <div class="prod-table-scroll" style="max-height: 480px; margin-bottom: 20px;">
            <table class="imp-table" id="issuesTable">
                <thead>
                    <tr>
                        <th style="width: 50px;">Row #</th>
                        <th style="width: 170px;">Detected Issues</th>
                        <th style="width: 120px;">SKU</th>
                        <th style="width: 150px;">Barcode *</th>
                        <th style="min-width: 170px;">Product Name *</th>
                        <th style="width: 120px;">Brand</th>
                        <th style="width: 120px;">Category</th>
                        <th style="width: 95px;">Cost (LKR)</th>
                        <th style="width: 105px;">Selling (LKR)</th>
                        <th style="width: 130px;">Supplier</th>
                        <th style="width: 130px; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody id="issuesBody">
                </tbody>
            </table>
        </div>

        <!-- Empty Issues State (when all issues fixed) -->
        <div id="noIssuesBanner" style="display: none; padding: 28px; text-align: center; background: #f0fdf4; border: 1px dashed #86efac; border-radius: 12px; margin-bottom: 20px;">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" style="margin-bottom: 6px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            <div style="font-size: 14px; font-weight: 800; color: #166534;">All Issues Resolved!</div>
            <div style="font-size: 12px; color: #15803d; margin-top: 2px;">Zero problematic rows remaining. All products are verified and ready to commit.</div>
        </div>
    </div>

    <!-- TAB 2: VALID PRODUCTS (Ready to Import) -->
    <div id="tabContentValid" style="display: none;">
        <div class="prod-table-scroll" style="max-height: 480px; margin-bottom: 20px;">
            <table class="imp-table" id="validTable">
                <thead>
                    <tr>
                        <th style="width: 55px;">Row #</th>
                        <th style="width: 85px;">Action</th>
                        <th style="width: 120px;">SKU</th>
                        <th style="width: 130px;">Barcode</th>
                        <th>Product Name</th>
                        <th style="width: 120px;">Brand</th>
                        <th style="width: 120px;">Category</th>
                        <th style="width: 95px;">Cost (LKR)</th>
                        <th style="width: 105px;">Selling (LKR)</th>
                        <th>Stock Note</th>
                        <th style="width: 75px; text-align: center;">Exclude</th>
                    </tr>
                </thead>
                <tbody id="validBody">
                </tbody>
            </table>
        </div>
    </div>

    <!-- Final Commit Action Bar -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; padding-top: 10px; border-top: 1px solid #e2e8f0;">
        <div id="commitNotice" style="font-size: 12px; color: #475569; font-weight: 600;">
            Stock Safety Enforced: Live stock for existing products will NOT be overwritten.
        </div>
        <button type="button" id="btnCommit" onclick="commitImportBatch()" class="imp-btn-success">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
            Commit Final Import Batch (<span id="btnCommitCount">0</span>)
        </button>
    </div>
</div>

<!-- Datalists for Master Autocomplete -->
<datalist id="brandDatalist"></datalist>
<datalist id="categoryDatalist"></datalist>
<datalist id="supplierDatalist"></datalist>

<!-- Import Progress Modal -->
<div class="imp-backdrop" id="impBackdrop" onclick="closeImportProgressModal()"></div>
<div class="imp-modal" id="impModal" role="dialog" aria-modal="true" aria-label="Import Progress">
    <div class="imp-modal-header">
        <div class="imp-icon-box" id="impIconBox">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0f172a" stroke-width="2.5" class="imp-spinner">
                <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
            </svg>
        </div>
        <div>
            <div class="imp-modal-title" id="impModalTitle">Parsing Import File</div>
            <div class="imp-modal-subtitle" id="impModalSubtitle">Reading and validating catalog rows...</div>
        </div>
    </div>
    <div class="imp-progress-wrapper">
        <div class="imp-progress-bar" id="impProgressBar" style="width: 0%;"></div>
    </div>
    <div class="imp-modal-footer">
        <span class="imp-status-text" id="impStatusText">0%</span>
        <button type="button" class="imp-close-btn" id="impCloseBtn" onclick="closeImportProgressModal()" style="display:none;">Close</button>
    </div>
</div>

<script>
let currentPreviewData = null;
let issueItems = [];
let validItems = [];
let rowOverrides = {};
let excludedIndices = [];
let availableMasters = { brands: [], categories: [], suppliers: [] };
let progressTimer = null;

function showImportProgressModal(title, subtitle) {
    const backdrop   = document.getElementById('impBackdrop');
    const modal      = document.getElementById('impModal');
    const bar        = document.getElementById('impProgressBar');
    const statusText = document.getElementById('impStatusText');
    const modalTitle = document.getElementById('impModalTitle');
    const modalSub   = document.getElementById('impModalSubtitle');
    const iconBox    = document.getElementById('impIconBox');
    const closeBtn   = document.getElementById('impCloseBtn');

    modalTitle.textContent = title || 'Processing Import';
    modalSub.textContent   = subtitle || 'Please wait...';
    statusText.textContent = '0%';
    bar.style.width        = '0%';
    closeBtn.style.display = 'none';
    iconBox.innerHTML      = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0f172a" stroke-width="2.5" class="imp-spinner"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>`;

    backdrop.classList.add('active');
    modal.classList.add('active');

    let currentProgress = 0;
    if (progressTimer) clearInterval(progressTimer);
    progressTimer = setInterval(() => {
        if (currentProgress < 85) {
            currentProgress += Math.floor(Math.random() * 10) + 5;
            if (currentProgress > 85) currentProgress = 85;
            bar.style.width = currentProgress + '%';
            statusText.textContent = currentProgress + '%';
        }
    }, 180);
}

function completeImportProgressModal(successMessage, isError = false) {
    if (progressTimer) clearInterval(progressTimer);
    const bar        = document.getElementById('impProgressBar');
    const statusText = document.getElementById('impStatusText');
    const modalTitle = document.getElementById('impModalTitle');
    const modalSub   = document.getElementById('impModalSubtitle');
    const iconBox    = document.getElementById('impIconBox');
    const closeBtn   = document.getElementById('impCloseBtn');

    if (!isError) {
        bar.style.width = '100%';
        statusText.textContent = '100%';
        modalTitle.textContent = 'Completed';
        modalSub.textContent   = successMessage || 'Operation finished successfully.';
        iconBox.innerHTML      = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>`;

        setTimeout(() => {
            closeImportProgressModal();
        }, 1100);
    } else {
        modalTitle.textContent = 'Import Error';
        modalSub.textContent   = successMessage || 'Validation or server error occurred.';
        iconBox.innerHTML      = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>`;
        closeBtn.style.display = 'block';
    }
}

function closeImportProgressModal() {
    if (progressTimer) clearInterval(progressTimer);
    document.getElementById('impBackdrop').classList.remove('active');
    document.getElementById('impModal').classList.remove('active');
}

function runImportPreview() {
    const fileInput = document.getElementById('import_file');
    if (!fileInput.files || fileInput.files.length === 0) {
        alert('Please select a CSV file to import.');
        return;
    }

    showImportProgressModal('Parsing & Validating CSV', 'Analyzing rows, barcodes, and category master records...');

    const formData = new FormData();
    formData.append('import_file', fileInput.files[0]);
    formData.append('import_mode', document.getElementById('import_mode').value);
    if (document.getElementById('ignore_blank_cells').checked) formData.append('ignore_blank_cells', '1');
    if (document.getElementById('auto_create_masters').checked) formData.append('auto_create_masters', '1');

    fetch('api/import_products.php?action=preview', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) {
            completeImportProgressModal('Preview Error: ' + res.message, true);
            return;
        }

        completeImportProgressModal('Validation complete! Loading summary...');
        renderPreviewResult(res);
    })
    .catch(err => {
        completeImportProgressModal('Server error during CSV parsing/validation.', true);
        console.error(err);
    });
}

function renderPreviewResult(data) {
    currentPreviewData = data;
    issueItems = data.issue_items ? JSON.parse(JSON.stringify(data.issue_items)) : [];
    validItems = data.valid_items ? JSON.parse(JSON.stringify(data.valid_items)) : [];
    rowOverrides = {};
    excludedIndices = [];

    // Populate Master Datalists
    availableMasters = data.available_masters || { brands: [], categories: [], suppliers: [] };
    populateDatalist('brandDatalist', availableMasters.brands || []);
    populateDatalist('categoryDatalist', availableMasters.categories || []);
    populateDatalist('supplierDatalist', availableMasters.suppliers || []);

    refreshDashboardStats();
    renderIssuesTable();
    renderValidTable();

    // Default to issues tab if issues exist, otherwise valid tab
    if (issueItems.length > 0) {
        switchPreviewTab('issues');
    } else {
        switchPreviewTab('valid');
    }

    document.getElementById('previewCard').style.display = 'block';
    document.getElementById('previewCard').scrollIntoView({ behavior: 'smooth' });
}

function populateDatalist(elementId, items) {
    const dl = document.getElementById(elementId);
    if (!dl) return;
    dl.innerHTML = '';
    items.forEach(val => {
        if (!val) return;
        const opt = document.createElement('option');
        opt.value = val;
        dl.appendChild(opt);
    });
}

function refreshDashboardStats() {
    const total = (issueItems.length + validItems.length + excludedIndices.length);
    document.getElementById('statTotal').textContent = total;
    document.getElementById('statValid').textContent = validItems.length;
    document.getElementById('statIssues').textContent = issueItems.length;

    let newCount = 0;
    let updateCount = 0;
    let lotCount = 0;
    let skippedCount = excludedIndices.length;

    validItems.forEach(item => {
        if (item.action === 'create') newCount++;
        else if (item.action === 'update') updateCount++;
        else if (item.action === 'price_lot' || item.action === 'lot') lotCount++;
        else if (item.action === 'skipped') skippedCount++;
    });

    document.getElementById('statNew').textContent = newCount;
    document.getElementById('statUpdate').textContent = updateCount;
    const statLotsEl = document.getElementById('statLots');
    if (statLotsEl) statLotsEl.textContent = lotCount;
    document.getElementById('statSkipped').textContent = skippedCount;

    document.getElementById('badgeIssuesCount').textContent = issueItems.length;
    document.getElementById('badgeValidCount').textContent = validItems.length;
    document.getElementById('btnCommitCount').textContent = validItems.length;

    const noIssuesBanner = document.getElementById('noIssuesBanner');
    const issuesToolbar = document.getElementById('issuesToolbar');
    const issuesTable = document.getElementById('issuesTable');

    if (issueItems.length === 0) {
        if (noIssuesBanner) noIssuesBanner.style.display = 'block';
        if (issuesToolbar) issuesToolbar.style.display = 'none';
        if (issuesTable) issuesTable.style.display = 'none';
    } else {
        if (noIssuesBanner) noIssuesBanner.style.display = 'none';
        if (issuesToolbar) issuesToolbar.style.display = 'flex';
        if (issuesTable) issuesTable.style.display = 'table';
    }
}

function switchPreviewTab(tab) {
    const tabBtnIssues = document.getElementById('tabBtnIssues');
    const tabBtnValid  = document.getElementById('tabBtnValid');
    const tabContentIssues = document.getElementById('tabContentIssues');
    const tabContentValid  = document.getElementById('tabContentValid');

    if (tab === 'issues') {
        tabBtnIssues.classList.add('active');
        tabBtnValid.classList.remove('active');
        tabContentIssues.style.display = 'block';
        tabContentValid.style.display = 'none';
    } else {
        tabBtnValid.classList.add('active');
        tabBtnIssues.classList.remove('active');
        tabContentValid.style.display = 'block';
        tabContentIssues.style.display = 'none';
    }
}

/* ── Render Issues Table with Inline Editable Inputs ── */
function renderIssuesTable() {
    const tbody = document.getElementById('issuesBody');
    tbody.innerHTML = '';

    issueItems.forEach((item, idx) => {
        const tr = document.createElement('tr');
        tr.id = `issue-row-${item.row_idx}`;

        // Issue Badges
        let issuePills = '';
        if (item.issues && item.issues.length > 0) {
            item.issues.forEach(iss => {
                const cls = (iss.type === 'warning') ? 'issue-pill-warning' : 'issue-pill-danger';
                issuePills += `<span class="issue-pill ${cls}" title="${escapeHtml(iss.message)}">${escapeHtml(iss.message)}</span>`;
            });
        } else {
            issuePills = `<span class="issue-pill issue-pill-danger">${escapeHtml(item.error_message || 'Issue detected')}</span>`;
        }

        const hasBarcodeIssue = item.issues && item.issues.some(i => i.field === 'barcode');
        const hasNameIssue    = item.issues && item.issues.some(i => i.field === 'name');
        const hasCostIssue    = item.issues && item.issues.some(i => i.field === 'cost');
        const hasPriceIssue   = item.issues && item.issues.some(i => i.field === 'selling_price');

        tr.innerHTML = `
            <td style="font-weight:700; color:#64748b;">#${item.line}</td>
            <td><div style="display:flex; flex-direction:column; gap:2px;">${issuePills}</div></td>
            <td>
                <input type="text" id="iss-sku-${item.row_idx}" class="imp-cell-input" value="${escapeHtml(item.sku || '')}" placeholder="SKU">
            </td>
            <td>
                <div class="imp-barcode-group">
                    <input type="text" id="iss-barcode-${item.row_idx}" class="imp-cell-input ${hasBarcodeIssue ? 'input-has-error' : ''}" value="${escapeHtml(item.barcode || '')}" placeholder="Barcode" oninput="clearInputError(this)">
                    <button type="button" class="imp-gen-btn" onclick="generateRowBarcode(${item.row_idx})" title="Generate unique retail barcode">⚡</button>
                </div>
            </td>
            <td>
                <input type="text" id="iss-name-${item.row_idx}" class="imp-cell-input ${hasNameIssue ? 'input-has-error' : ''}" value="${escapeHtml(item.name || '')}" placeholder="Product Name" oninput="clearInputError(this)">
            </td>
            <td>
                <input type="text" id="iss-brand-${item.row_idx}" list="brandDatalist" class="imp-cell-input" value="${escapeHtml(item.brand || '')}" placeholder="Brand">
            </td>
            <td>
                <input type="text" id="iss-category-${item.row_idx}" list="categoryDatalist" class="imp-cell-input" value="${escapeHtml(item.category || '')}" placeholder="Category">
            </td>
            <td>
                <input type="number" step="0.01" min="0" id="iss-cost-${item.row_idx}" class="imp-cell-input ${hasCostIssue ? 'input-has-error' : ''}" value="${escapeHtml(item.cost || '')}" placeholder="0.00" oninput="clearInputError(this)">
            </td>
            <td>
                <input type="number" step="0.01" min="0" id="iss-price-${item.row_idx}" class="imp-cell-input ${hasPriceIssue ? 'input-has-error' : ''}" value="${escapeHtml(item.selling_price || '')}" placeholder="0.00" oninput="clearInputError(this)">
            </td>
            <td>
                <input type="text" id="iss-supplier-${item.row_idx}" list="supplierDatalist" class="imp-cell-input" value="${escapeHtml(item.supplier || '')}" placeholder="Supplier">
            </td>
            <td style="text-align: center; white-space: nowrap;">
                <div style="display: inline-flex; align-items: center; gap: 4px;">
                    <button type="button" onclick="fixSingleIssueRow(${item.row_idx})" class="imp-act-btn imp-btn-fix" title="Validate and move to Valid list">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                        Fix
                    </button>
                    <button type="button" onclick="excludeSingleIssueRow(${item.row_idx})" class="imp-act-btn imp-btn-del" title="Exclude this row from import">
                        ✕
                    </button>
                </div>
            </td>
        `;

        tbody.appendChild(tr);
    });
}

/* ── Render Valid Products Table ── */
function renderValidTable() {
    const tbody = document.getElementById('validBody');
    tbody.innerHTML = '';

    validItems.forEach((item, idx) => {
        const tr = document.createElement('tr');
        let badgeHtml = '';
        if (item.action === 'create') {
            badgeHtml = '<span class="imp-badge badge-create">Create</span>';
        } else if (item.action === 'update') {
            badgeHtml = '<span class="imp-badge badge-update">Update</span>';
        } else if (item.action === 'price_lot' || item.action === 'lot') {
            badgeHtml = '<span class="imp-badge" style="background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd;">Price Lot</span>';
        } else {
            badgeHtml = '<span class="imp-badge badge-skipped">Skipped</span>';
        }

        const costFmt = isNaN(parseFloat(item.cost)) ? '-' : parseFloat(item.cost).toFixed(2);
        const priceFmt = isNaN(parseFloat(item.selling_price)) ? '-' : parseFloat(item.selling_price).toFixed(2);

        tr.innerHTML = `
            <td style="font-weight:700; color:#64748b;">#${item.line}</td>
            <td>${badgeHtml}</td>
            <td><span class="imp-code" style="background:#e0f2fe;color:#0369a1;border-color:#bae6fd;">${escapeHtml(item.sku || item.barcode || '')}</span></td>
            <td><span class="imp-code">${escapeHtml(item.barcode || '')}</span></td>
            <td style="font-weight:700;">${escapeHtml(item.name || '')}</td>
            <td>${escapeHtml(item.brand || '-')}</td>
            <td>${escapeHtml(item.category || '-')}</td>
            <td style="font-weight:600;">${costFmt}</td>
            <td style="font-weight:700; color:#16a34a;">${priceFmt}</td>
            <td style="font-size:11px; color:#64748b;">${escapeHtml(item.stock_note || '')}</td>
            <td style="text-align: center;">
                <button type="button" onclick="excludeValidRow(${item.row_idx})" class="imp-act-btn imp-btn-del" title="Exclude row">
                    ✕
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function clearInputError(input) {
    if (input) input.classList.remove('input-has-error');
}

/* ── Barcode Generator (Standard EAN-13 algorithm) ── */
function generateEanBarcode() {
    // Prefix 893 (Sri Lanka retail standard) + 9 random digits
    let base = '893';
    for (let i = 0; i < 9; i++) {
        base += Math.floor(Math.random() * 10).toString();
    }
    // Calculate EAN-13 Check Digit
    let sum = 0;
    for (let i = 0; i < 12; i++) {
        const digit = parseInt(base.charAt(i), 10);
        sum += (i % 2 === 0) ? digit : digit * 3;
    }
    const checkDigit = (10 - (sum % 10)) % 10;
    return base + checkDigit.toString();
}

function generateRowBarcode(rowIdx) {
    const inputBarcode = document.getElementById(`iss-barcode-${rowIdx}`);
    if (!inputBarcode) return;

    let newBarcode = generateEanBarcode();
    inputBarcode.value = newBarcode;
    inputBarcode.classList.remove('input-has-error');

    // Flash green border briefly
    inputBarcode.style.borderColor = '#16a34a';
    setTimeout(() => { inputBarcode.style.borderColor = ''; }, 700);
}

/* ── Fix & Validate Single Row ── */
function fixSingleIssueRow(rowIdx) {
    const rowEl = document.getElementById(`issue-row-${rowIdx}`);
    if (!rowEl) return;

    const bInput = document.getElementById(`iss-barcode-${rowIdx}`);
    const sInput = document.getElementById(`iss-sku-${rowIdx}`);
    const nInput = document.getElementById(`iss-name-${rowIdx}`);
    const brInput = document.getElementById(`iss-brand-${rowIdx}`);
    const catInput = document.getElementById(`iss-category-${rowIdx}`);
    const cInput = document.getElementById(`iss-cost-${rowIdx}`);
    const pInput = document.getElementById(`iss-price-${rowIdx}`);
    const supInput = document.getElementById(`iss-supplier-${rowIdx}`);

    const rawBarcode = bInput ? bInput.value.trim() : '';
    const rawSku     = (sInput && sInput.value.trim()) ? sInput.value.trim() : rawBarcode;
    const rawName    = nInput ? nInput.value.trim() : '';
    const rawBrand   = brInput ? brInput.value.trim() : '';
    const rawCategory= catInput ? catInput.value.trim() : '';
    const rawCost    = cInput ? cInput.value.trim() : '';
    const rawPrice   = pInput ? pInput.value.trim() : '';
    const rawSupplier= supInput ? supInput.value.trim() : '';

    // Validation checks
    let hasErr = false;
    if (!rawBarcode) {
        if (bInput) bInput.classList.add('input-has-error');
        hasErr = true;
    }
    if (!rawName) {
        if (nInput) nInput.classList.add('input-has-error');
        hasErr = true;
    }
    if (rawCost !== '' && (isNaN(parseFloat(rawCost)) || parseFloat(rawCost) < 0)) {
        if (cInput) cInput.classList.add('input-has-error');
        hasErr = true;
    }
    if (rawPrice !== '' && (isNaN(parseFloat(rawPrice)) || parseFloat(rawPrice) < 0)) {
        if (pInput) pInput.classList.add('input-has-error');
        hasErr = true;
    }

    // Check if barcode already exists in valid list -> price lot
    const isLotRow = validItems.some(v => v.barcode && v.barcode.toLowerCase() === rawBarcode.toLowerCase());

    if (hasErr) {
        alert('Please resolve highlighted required/numeric fields before validating row.');
        return;
    }

    // Record client override
    rowOverrides[rowIdx] = {
        sku: rawSku,
        barcode: rawBarcode,
        name: rawName,
        brand: rawBrand,
        category: rawCategory,
        cost: rawCost,
        selling_price: rawPrice,
        supplier: rawSupplier,
        status: 'active'
    };

    // Find original item in issueItems
    const issueIndex = issueItems.findIndex(i => i.row_idx === rowIdx);
    if (issueIndex === -1) return;
    const oldItem = issueItems[issueIndex];

    // Remove from issueItems
    issueItems.splice(issueIndex, 1);

    // Add to validItems
    validItems.push({
        row_idx: rowIdx,
        line: oldItem.line,
        sku: rawSku,
        barcode: rawBarcode,
        name: rawName,
        brand: rawBrand,
        category: rawCategory,
        supplier: rawSupplier,
        cost: rawCost,
        selling_price: rawPrice,
        opening_stock: oldItem.opening_stock || '',
        action: isLotRow ? 'price_lot' : 'create',
        stock_note: isLotRow ? 'Price lot resolved & verified' : 'Resolved & verified by user'
    });

    // Refresh UI
    refreshDashboardStats();
    renderIssuesTable();
    renderValidTable();

    // If no issues left, automatically switch to Valid tab
    if (issueItems.length === 0) {
        switchPreviewTab('valid');
    }
}

/* ── Exclude Single Issue Row ── */
function excludeSingleIssueRow(rowIdx) {
    const issueIndex = issueItems.findIndex(i => i.row_idx === rowIdx);
    if (issueIndex === -1) return;

    excludedIndices.push(rowIdx);
    issueItems.splice(issueIndex, 1);

    refreshDashboardStats();
    renderIssuesTable();

    if (issueItems.length === 0) {
        switchPreviewTab('valid');
    }
}

/* ── Exclude Single Valid Row ── */
function excludeValidRow(rowIdx) {
    const validIndex = validItems.findIndex(i => i.row_idx === rowIdx);
    if (validIndex === -1) return;

    excludedIndices.push(rowIdx);
    validItems.splice(validIndex, 1);

    refreshDashboardStats();
    renderValidTable();
}

/* ── Bulk Helper: Auto-Fix All Barcodes ── */
function autoFixAllBarcodes() {
    let fixedCount = 0;
    issueItems.forEach(item => {
        const bInput = document.getElementById(`iss-barcode-${item.row_idx}`);
        if (!bInput) return;

        // Check if row has empty or duplicate barcode issue
        const needsBarcode = !item.barcode || (item.issues && item.issues.some(iss => iss.field === 'barcode'));
        if (needsBarcode) {
            const newBarcode = generateEanBarcode();
            bInput.value = newBarcode;
            bInput.classList.remove('input-has-error');
            fixedCount++;
        }
    });

    if (fixedCount > 0) {
        alert(`Generated ${fixedCount} unique retail barcodes! Click "✓ Re-Validate All" or "Fix" on each row to verify.`);
    } else {
        alert('All issue rows already have barcodes.');
    }
}

/* ── Bulk Helper: Re-Validate All Issue Rows ── */
function revalidateAllIssues() {
    if (issueItems.length === 0) return;

    // Snapshot rows to iterate
    const rowsToTest = [...issueItems];
    let resolvedCount = 0;

    rowsToTest.forEach(item => {
        const bInput = document.getElementById(`iss-barcode-${item.row_idx}`);
        const sInput = document.getElementById(`iss-sku-${item.row_idx}`);
        const nInput = document.getElementById(`iss-name-${item.row_idx}`);
        const brInput = document.getElementById(`iss-brand-${item.row_idx}`);
        const catInput = document.getElementById(`iss-category-${item.row_idx}`);
        const cInput = document.getElementById(`iss-cost-${item.row_idx}`);
        const pInput = document.getElementById(`iss-price-${item.row_idx}`);
        const supInput = document.getElementById(`iss-supplier-${item.row_idx}`);

        const rawBarcode = bInput ? bInput.value.trim() : '';
        const rawSku     = (sInput && sInput.value.trim()) ? sInput.value.trim() : rawBarcode;
        const rawName    = nInput ? nInput.value.trim() : '';
        const rawBrand   = brInput ? brInput.value.trim() : '';
        const rawCategory= catInput ? catInput.value.trim() : '';
        const rawCost    = cInput ? cInput.value.trim() : '';
        const rawPrice   = pInput ? pInput.value.trim() : '';
        const rawSupplier= supInput ? supInput.value.trim() : '';

        // Validation
        if (!rawBarcode || !rawName) return;
        if (rawCost !== '' && (isNaN(parseFloat(rawCost)) || parseFloat(rawCost) < 0)) return;
        if (rawPrice !== '' && (isNaN(parseFloat(rawPrice)) || parseFloat(rawPrice) < 0)) return;

        // Check if barcode already exists in valid list -> price lot
        const isLotRow = validItems.some(v => v.barcode && v.barcode.toLowerCase() === rawBarcode.toLowerCase());

        // Valid!
        rowOverrides[item.row_idx] = {
            sku: rawSku,
            barcode: rawBarcode,
            name: rawName,
            brand: rawBrand,
            category: rawCategory,
            cost: rawCost,
            selling_price: rawPrice,
            supplier: rawSupplier,
            status: 'active'
        };

        const idxInList = issueItems.findIndex(i => i.row_idx === item.row_idx);
        if (idxInList !== -1) {
            issueItems.splice(idxInList, 1);
        }

        validItems.push({
            row_idx: item.row_idx,
            line: item.line,
            sku: rawSku,
            barcode: rawBarcode,
            name: rawName,
            brand: rawBrand,
            category: rawCategory,
            supplier: rawSupplier,
            cost: rawCost,
            selling_price: rawPrice,
            opening_stock: item.opening_stock || '',
            action: isLotRow ? 'price_lot' : 'create',
            stock_note: isLotRow ? 'Price lot resolved & verified' : 'Resolved & verified by user'
        });

        resolvedCount++;
    });

    refreshDashboardStats();
    renderIssuesTable();
    renderValidTable();

    if (resolvedCount > 0) {
        alert(`Successfully verified and moved ${resolvedCount} rows to the Valid list!`);
    } else {
        alert('No additional rows could be automatically verified. Please fill in required missing fields (Barcode, Name, etc.) on remaining items.');
    }

    if (issueItems.length === 0) {
        switchPreviewTab('valid');
    }
}

/* ── Bulk Helper: Exclude All Remaining Issues ── */
function excludeAllIssues() {
    if (issueItems.length === 0) return;
    if (!confirm(`Are you sure you want to exclude all ${issueItems.length} remaining issue rows from this import?`)) {
        return;
    }

    issueItems.forEach(item => {
        excludedIndices.push(item.row_idx);
    });
    issueItems = [];

    refreshDashboardStats();
    renderIssuesTable();
    switchPreviewTab('valid');
}

/* ── Commit Final Batch ── */
function commitImportBatch() {
    if (validItems.length === 0) {
        alert('There are no valid products to import. Please resolve at least one issue or upload a valid CSV file.');
        return;
    }

    if (issueItems.length > 0) {
        const ok = confirm(`Notice: You have ${issueItems.length} unresolved issue rows remaining. These will be automatically excluded.\n\nDo you want to proceed with importing the ${validItems.length} verified valid products?`);
        if (!ok) return;
    } else {
        if (!confirm(`Are you sure you want to commit these ${validItems.length} products into the catalog?`)) {
            return;
        }
    }

    showImportProgressModal('Executing Batch Import', 'Creating & updating product master records in catalog...');

    // Any remaining issue items are automatically excluded
    const finalExcluded = [...excludedIndices];
    issueItems.forEach(item => {
        if (!finalExcluded.includes(item.row_idx)) {
            finalExcluded.push(item.row_idx);
        }
    });

    const formData = new FormData();
    formData.append('import_mode', document.getElementById('import_mode').value);
    if (document.getElementById('ignore_blank_cells').checked) formData.append('ignore_blank_cells', '1');
    if (document.getElementById('auto_create_masters').checked) formData.append('auto_create_masters', '1');

    formData.append('row_overrides', JSON.stringify(rowOverrides));
    formData.append('excluded_indices', JSON.stringify(finalExcluded));

    fetch('api/import_products.php?action=commit', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) {
            completeImportProgressModal('Import Execution Error: ' + res.message, true);
            return;
        }

        completeImportProgressModal('Batch import successful! Redirecting...');
        setTimeout(() => {
            window.location.href = 'products';
        }, 1200);
    })
    .catch(err => {
        completeImportProgressModal('Server error during import commit.', true);
        console.error(err);
    });
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
</script>
