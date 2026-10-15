<?php
/**
 * Curtiss POS - Product Master Import View (Section 1)
 * High-performance UI for uploading, previewing, validating, and committing 10,000+ product catalogs.
 * Theme: Curtiss Floating Glassmorphism
 */
?>

<style>
/* ── Product Import — Curtiss Floating Theme ────────────────────────── */

.imp-header {
    margin-bottom: 1.5rem;
}

.imp-title {
    font-size: 1.35rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.025em;
    line-height: 1.2;
}

.imp-subtitle {
    font-size: 0.8rem;
    color: #94a3b8;
    margin-top: 4px;
    font-weight: 500;
}

.imp-card {
    background: rgba(255,255,255,0.92);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(226,232,240,0.85);
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 20px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.04);
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
    box-shadow: 0 0 0 3px rgba(15,23,42,0.08);
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
    box-shadow: 0 4px 14px rgba(15,23,42,0.2);
    font-family: inherit;
}
.imp-btn-primary:hover {
    background: #1e293b;
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(15,23,42,0.3);
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
    box-shadow: 0 4px 14px rgba(22,163,74,0.25);
    font-family: inherit;
}
.imp-btn-success:hover {
    background: #15803d;
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(22,163,74,0.35);
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
.stat-new     { background: #f0fdf4; border-color: rgba(22,163,74,0.2); color: #166534; }
.stat-new .imp-stat-val { color: #16a34a; }
.stat-update  { background: #eff6ff; border-color: rgba(37,99,235,0.2); color: #1e40af; }
.stat-update .imp-stat-val { color: #2563eb; }
.stat-skipped { background: #fffbeb; border-color: rgba(217,119,6,0.2); color: #92400e; }
.stat-skipped .imp-stat-val { color: #d97706; }
.stat-invalid { background: #fef2f2; border-color: rgba(220,38,38,0.2); color: #991b1b; }
.stat-invalid .imp-stat-val { color: #dc2626; }

/* Table overrides */
.imp-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
}
.imp-table thead {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}
.imp-table thead th {
    padding: 9px 12px;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #64748b;
    text-align: left;
    white-space: nowrap;
}
.imp-table tbody tr {
    border-bottom: 1px solid #f1f5f9;
}
.imp-table tbody tr:last-child { border-bottom: none; }
.imp-table tbody td {
    padding: 9px 12px;
    color: #0f172a;
    vertical-align: middle;
}

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
.badge-create  { background: #f0fdf4; color: #16a34a; border: 1px solid rgba(22,163,74,0.2); }
.badge-update  { background: #eff6ff; color: #2563eb; border: 1px solid rgba(37,99,235,0.2); }
.badge-skipped { background: #fffbeb; color: #d97706; border: 1px solid rgba(217,119,6,0.2); }
.badge-invalid { background: #fef2f2; color: #dc2626; border: 1px solid rgba(220,38,38,0.2); }

.imp-code {
    font-family: 'SF Mono', 'Fira Code', 'Courier New', monospace;
    font-size: 11px;
    font-weight: 600;
    color: #475569;
    background: #f1f5f9;
    padding: 2px 6px;
    border-radius: 5px;
}

/* ── Import Progress Modal ── */
.imp-backdrop {
    position: fixed;
    inset: 0;
    z-index: 9500;
    background: rgba(15,23,42,0.35);
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
    width: 360px;
    max-width: calc(100vw - 32px);
    background: rgba(255,255,255,0.98);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    border: 1px solid rgba(226,232,240,0.9);
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(15,23,42,0.18), 0 4px 16px rgba(0,0,0,0.06);
    padding: 20px 22px;
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
    padding: 5px 14px;
    border-radius: 8px;
    background: #0f172a;
    color: #fff;
    border: none;
    font-size: 11px;
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

<!-- Page Title Header -->
<div class="imp-header">
    <h1 class="imp-title">Import Products</h1>
    <p class="imp-subtitle">Batch create or update products via CSV master file with SKU preservation and zero live-stock overwrites</p>
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
                <small style="display:block;color:#94a3b8;font-size:11px;margin-top:4px;font-weight:500;">Supported format: CSV (UTF-8). Preserves SKU leading zeros (e.g. 001, 00001).</small>
            </div>

            <div>
                <label class="imp-label" for="import_mode">Import Mode *</label>
                <select name="import_mode" id="import_mode" class="imp-select" required>
                    <option value="create_and_update" selected>Create New & Update Existing</option>
                    <option value="update_existing_only">Update Existing SKUs Only</option>
                    <option value="create_new_only">Create New SKUs Only</option>
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
                    If an imported Brand or Category does not exist, automatically register it instead of failing the row.
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

<!-- Step 2: Validation & Preview Result Box (Hidden initially) -->
<div id="previewCard" class="imp-card" style="display: none;">
    <div class="imp-step-title">
        <span class="imp-step-num">2</span>
        <span>Import Preview & Validation Summary</span>
    </div>

    <!-- Summary Metrics Cards -->
    <div class="imp-stat-grid">
        <div class="imp-stat-card stat-total">
            <div class="imp-stat-lbl">Total Rows</div>
            <div id="statTotal" class="imp-stat-val">0</div>
        </div>
        <div class="imp-stat-card stat-new">
            <div class="imp-stat-lbl">New Products</div>
            <div id="statNew" class="imp-stat-val">0</div>
        </div>
        <div class="imp-stat-card stat-update">
            <div class="imp-stat-lbl">To Update</div>
            <div id="statUpdate" class="imp-stat-val">0</div>
        </div>
        <div class="imp-stat-card stat-skipped">
            <div class="imp-stat-lbl">Skipped</div>
            <div id="statSkipped" class="imp-stat-val">0</div>
        </div>
        <div class="imp-stat-card stat-invalid">
            <div class="imp-stat-lbl">Invalid Rows</div>
            <div id="statInvalid" class="imp-stat-val">0</div>
        </div>
    </div>

    <!-- Error Diagnostics Container -->
    <div id="errorReportBox" style="display: none; margin-bottom: 20px; background: #fef2f2; border: 1px solid rgba(220,38,38,0.25); border-radius: 12px; padding: 16px;">
        <h4 style="color: #dc2626; font-size: 13px; margin-bottom: 6px; font-weight: 800; display:flex; align-items:center; gap:6px;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            Validation Errors Detected
        </h4>
        <p style="font-size: 11.5px; color: #991b1b; margin-bottom: 12px; font-weight: 500;">Fix the following row issues in your CSV file before committing the import:</p>
        
        <div style="max-height: 220px; overflow-y: auto;">
            <table class="imp-table">
                <thead>
                    <tr>
                        <th style="width: 70px;">Row #</th>
                        <th style="width: 120px;">SKU</th>
                        <th>Product Name</th>
                        <th>Validation Error & Correction Guidance</th>
                    </tr>
                </thead>
                <tbody id="errorReportBody">
                </tbody>
            </table>
        </div>
    </div>

    <!-- Preview Items Sample Table -->
    <h4 style="font-size: 11px; font-weight: 800; text-transform:uppercase; letter-spacing:0.07em; color: #94a3b8; margin-bottom: 10px;">Parsed Sample Items (First 100 rows)</h4>
    <div style="max-height: 320px; overflow-y: auto; margin-bottom: 20px; border: 1px solid #e2e8f0; border-radius: 12px;">
        <table class="imp-table">
            <thead>
                <tr>
                    <th>Row #</th>
                    <th>Action</th>
                    <th>SKU</th>
                    <th>Barcode</th>
                    <th>Product Name</th>
                    <th>Stock / Import Note</th>
                </tr>
            </thead>
            <tbody id="previewBody">
            </tbody>
        </table>
    </div>

    <!-- Final Action Button -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div id="commitNotice" style="font-size: 11.5px; color: #64748b; font-weight: 500;">
            Stock Safety Enforced: Live stock for existing products will NOT be overwritten.
        </div>
        <button type="button" id="btnCommit" onclick="commitImportBatch()" class="imp-btn-success">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
            Commit Final Import Batch
        </button>
    </div>
</div>

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

    showImportProgressModal('Parsing & Validating CSV', 'Analyzing rows, SKUs, and category master records...');

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
    const summary = data.summary;

    document.getElementById('statTotal').textContent = summary.total_rows || 0;
    document.getElementById('statNew').textContent = summary.new_count || 0;
    document.getElementById('statUpdate').textContent = summary.update_count || 0;
    document.getElementById('statSkipped').textContent = summary.skipped_count || 0;
    document.getElementById('statInvalid').textContent = summary.invalid_count || 0;

    // Error report list
    const errBox = document.getElementById('errorReportBox');
    const errBody = document.getElementById('errorReportBody');
    errBody.innerHTML = '';

    if (data.errors && data.errors.length > 0) {
        errBox.style.display = 'block';
        data.errors.forEach(e => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>#${e.line}</td>
                <td><span class="imp-code">${escapeHtml(e.sku)}</span></td>
                <td>${escapeHtml(e.name)}</td>
                <td style="color: #dc2626; font-weight: 600;">${escapeHtml(e.message)}</td>
            `;
            errBody.appendChild(tr);
        });
    } else {
        errBox.style.display = 'none';
    }

    // Sample items list
    const prevBody = document.getElementById('previewBody');
    prevBody.innerHTML = '';

    if (data.items && data.items.length > 0) {
        data.items.forEach(item => {
            const tr = document.createElement('tr');
            let badgeHtml = '';
            if (item.action === 'create') {
                badgeHtml = '<span class="imp-badge badge-create">Create</span>';
            } else if (item.action === 'update') {
                badgeHtml = '<span class="imp-badge badge-update">Update</span>';
            } else if (item.action === 'skipped') {
                badgeHtml = '<span class="imp-badge badge-skipped">Skipped</span>';
            } else {
                badgeHtml = '<span class="imp-badge badge-invalid">Invalid</span>';
            }

            tr.innerHTML = `
                <td>#${item.line}</td>
                <td>${badgeHtml}</td>
                <td><span class="imp-code">${escapeHtml(item.sku || '')}</span></td>
                <td><span class="imp-code">${escapeHtml(item.barcode || '')}</span></td>
                <td style="font-weight:700;">${escapeHtml(item.name || '')}</td>
                <td style="font-size: 11px; color: #64748b;">${escapeHtml(item.stock_note || item.error || item.note || '')}</td>
            `;
            prevBody.appendChild(tr);
        });
    }

    document.getElementById('previewCard').style.display = 'block';
    document.getElementById('previewCard').scrollIntoView({ behavior: 'smooth' });
}

function commitImportBatch() {
    if (!confirm('Are you sure you want to commit this import batch into the Products List?')) {
        return;
    }

    showImportProgressModal('Executing Batch Import', 'Creating & updating product master records in catalog...');

    const formData = new FormData();
    formData.append('import_mode', document.getElementById('import_mode').value);
    if (document.getElementById('ignore_blank_cells').checked) formData.append('ignore_blank_cells', '1');
    if (document.getElementById('auto_create_masters').checked) formData.append('auto_create_masters', '1');

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

