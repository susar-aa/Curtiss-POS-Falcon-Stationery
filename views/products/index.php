<?php
/**
 * Curtiss POS - Products View (Section 1)
 * Field Order: 1. SKU & Barcode, 2. Brand, 3. Category, 4. Name, 5. Supplier, 6. Cost, 7. Selling Price, 8. Opening Stock
 */

$queryString = http_build_query(array_filter($filters));
?>
<link rel="stylesheet" href="assets/css/curtiss_select.css">

<style>
/* ── Products Page — Curtiss Theme ───────────────────────────────────── */

.prod-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.prod-title {
    font-size: 1.35rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.025em;
    line-height: 1.2;
}

.prod-subtitle {
    font-size: 0.8rem;
    color: #94a3b8;
    margin-top: 4px;
    font-weight: 500;
}

.prod-header-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    align-items: center;
}

.prod-btn {
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

.prod-btn-ghost {
    background: rgba(255,255,255,0.9);
    border-color: rgba(226,232,240,0.9);
    color: #0f172a;
    backdrop-filter: blur(12px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}
.prod-btn-ghost:hover {
    background: #ffffff;
    box-shadow: 0 4px 14px rgba(0,0,0,0.1);
    transform: translateY(-1px);
}

.prod-btn-primary {
    background: #0f172a;
    color: #ffffff;
    box-shadow: 0 2px 12px rgba(15,23,42,0.2);
}
.prod-btn-primary:hover {
    background: #1e293b;
    box-shadow: 0 4px 16px rgba(15,23,42,0.3);
    transform: translateY(-1px);
}

/* Alert Notices */
.prod-alert {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    border-radius: 12px;
    font-size: 12.5px;
    font-weight: 600;
    margin-bottom: 12px;
    animation: slideDown 0.2s ease;
}
@keyframes slideDown {
    from { opacity: 0; transform: translateY(-6px); }
    to   { opacity: 1; transform: translateY(0); }
}
.prod-alert-success { background: rgba(240,253,244,0.95); border: 1px solid rgba(22,163,74,0.25); color: #16a34a; }
.prod-alert-danger  { background: rgba(254,242,242,0.95); border: 1px solid rgba(220,38,38,0.25); color: #dc2626; }

/* Filter Panel */
.prod-filter-panel {
    background: rgba(255,255,255,0.92);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(226,232,240,0.85);
    border-radius: 16px;
    padding: 16px 20px;
    margin-bottom: 16px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.04);
}
.prod-filter-grid {
    display: grid;
    grid-template-columns: 2fr repeat(4, 1fr) auto;
    gap: 12px;
    align-items: end;
}
@media (max-width: 960px) {
    .prod-filter-grid { grid-template-columns: repeat(2, 1fr); }
    .prod-filter-btn-col { grid-column: span 2; }
}
.prod-filter-label {
    display: block;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #94a3b8;
    margin-bottom: 6px;
}
.prod-filter-input,
.prod-filter-select {
    width: 100%;
    height: 36px;
    padding: 0 12px;
    font-size: 12.5px;
    font-family: inherit;
    font-weight: 500;
    color: #0f172a;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    outline: none;
    transition: border-color 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
    -webkit-appearance: none;
    appearance: none;
}
.prod-filter-input:focus,
.prod-filter-select:focus {
    background: #ffffff;
    border-color: #0f172a;
    box-shadow: 0 0 0 3px rgba(15,23,42,0.08);
}
.prod-filter-select {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 10px center;
    padding-right: 32px;
}
.prod-filter-btn {
    height: 36px;
    padding: 0 20px;
    border-radius: 10px;
    background: #0f172a;
    color: #ffffff;
    font-size: 12px;
    font-weight: 700;
    border: none;
    cursor: pointer;
    font-family: inherit;
    transition: background 0.15s ease, box-shadow 0.15s ease;
    white-space: nowrap;
    width: 100%;
}
.prod-filter-btn:hover { background: #1e293b; box-shadow: 0 2px 8px rgba(15,23,42,0.2); }

/* Bulk Toolbar */
.prod-bulk-toolbar {
    display: none;
    background: #0f172a;
    border-radius: 12px;
    padding: 10px 16px;
    margin-bottom: 12px;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    animation: slideDown 0.2s ease;
}
.prod-bulk-toolbar.visible { display: flex; }
.prod-bulk-count { font-size: 12px; font-weight: 700; color: #ffffff; white-space: nowrap; }
.prod-bulk-count span {
    display: inline-flex; align-items: center; justify-content: center;
    background: rgba(255,255,255,0.15); border-radius: 6px;
    padding: 1px 7px; margin-right: 4px; font-size: 12px;
}
.prod-bulk-actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
.prod-bulk-btn {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 6px 13px; border-radius: 8px; font-size: 11px;
    font-weight: 700; border: none; cursor: pointer;
    transition: all 0.15s ease; font-family: inherit;
}
.prod-bulk-btn-activate  { background: rgba(22,163,74,0.15); color: #4ade80; }
.prod-bulk-btn-activate:hover { background: rgba(22,163,74,0.25); }
.prod-bulk-btn-deactivate { background: rgba(234,179,8,0.15); color: #fbbf24; }
.prod-bulk-btn-deactivate:hover { background: rgba(234,179,8,0.25); }
.prod-bulk-btn-neutral { background: rgba(255,255,255,0.1); color: rgba(255,255,255,0.85); }
.prod-bulk-btn-neutral:hover { background: rgba(255,255,255,0.18); }
.prod-bulk-select {
    height: 30px; padding: 0 10px; border-radius: 8px;
    background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.12);
    color: rgba(255,255,255,0.85); font-size: 11px; font-family: inherit; outline: none;
}
.prod-bulk-select option { color: #0f172a; background: #fff; }

/* Main Table Panel */
.prod-table-panel {
    background: rgba(255,255,255,0.92);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(226,232,240,0.85);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 16px rgba(0,0,0,0.04);
}
.prod-table-scroll { overflow-x: auto; }
.prod-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 560px;
}
.prod-table thead { background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
.prod-table thead th {
    position: sticky;
    top: 0;
    z-index: 10;
    background: #f8fafc;
    padding: 10px 14px;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #64748b;
    white-space: nowrap;
    text-align: left;
}
.prod-table thead th.col-check { width: 40px; text-align: center; }
.prod-table thead th.col-right { text-align: right; }
.prod-table thead th.col-view  { width: 40px; text-align: center; }

.prod-table tbody tr {
    height: 48px;
    min-height: 48px;
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.12s ease;
    cursor: pointer;
}
.prod-table tbody tr:nth-child(even) { background: rgba(248, 250, 252, 0.57); }
.prod-table tbody tr:nth-child(odd)  { background: #ffffff; }
.prod-table tbody tr:last-child { border-bottom: none; }
.prod-table tbody tr:hover { background: #f1f5f9 !important; }

.prod-table td,
.prod-table tbody td {
    height: 48px;
    padding: 0 14px;
    font-size: 12.5px;
    color: #1e293b;
    vertical-align: middle;
    box-sizing: border-box;
}
.prod-table tbody td.col-check { text-align: center; }
.prod-table tbody td.col-right { text-align: right; }
.prod-table tbody td.col-view  { text-align: center; }

/* SKU/Barcode combined cell */
.prod-sku-line {
    display: flex;
    align-items: center;
    gap: 5px;
    flex-wrap: wrap;
}
.prod-code {
    display: inline-block;
    font-family: 'SF Mono', 'Fira Code', 'Courier New', monospace;
    font-size: 10.5px;
    font-weight: 600;
    color: #475569;
    background: #f1f5f9;
    border-radius: 5px;
    padding: 1px 6px;
    letter-spacing: 0.02em;
}
.prod-code-sep {
    font-size: 10px;
    color: #cbd5e1;
    font-weight: 400;
}

.prod-name-cell {
    font-weight: 700;
    color: #0f172a;
    font-size: 12.5px;
}

.prod-price  { font-weight: 800; color: #0f172a; font-size: 12.5px; }
.prod-cost   { font-weight: 500; color: #64748b; font-size: 11.5px; }

/* Status badge — theme-matched 28px interactive trigger (Rule 12 & Rule 27) */
.prod-badge-active,
.prod-badge-inactive {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    height: 28px;
    min-height: 28px;
    line-height: 26px;
    padding: 0 12px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
    box-sizing: border-box;
    cursor: pointer;
    transition: all 0.15s ease;
}
.prod-badge-active {
    background: #dcfce7;
    color: #15803d;
    border: 1px solid rgba(22,163,74,0.25);
}
.prod-badge-active:hover {
    background: #bbf7d0;
}
.prod-badge-inactive {
    background: #fee2e2;
    color: #b91c1c;
    border: 1px solid rgba(220,38,38,0.25);
}
.prod-badge-inactive:hover {
    background: #fecaca;
}

/* View icon button */
.prod-view-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 8px;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    color: #64748b;
    cursor: pointer;
    transition: all 0.14s ease;
    flex-shrink: 0;
}
.prod-view-btn:hover {
    background: #0f172a;
    border-color: #0f172a;
    color: #ffffff;
}

/* Empty state */
.prod-empty { text-align: center; padding: 48px 20px; }
.prod-empty-icon {
    width: 52px; height: 52px;
    background: #f1f5f9; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 12px;
}
.prod-empty-title { font-size: 13px; font-weight: 700; color: #0f172a; margin-bottom: 4px; }
.prod-empty-text  { font-size: 12px; color: #94a3b8; }

/* Pagination */
.prod-pagination {
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 16px; border-top: 1px solid #f1f5f9; flex-wrap: wrap; gap: 10px;
}
.prod-pagination-info { font-size: 12px; color: #64748b; font-weight: 500; }
.prod-pagination-info strong { color: #0f172a; font-weight: 700; }
.prod-pagination-controls { display: flex; gap: 6px; }
.prod-page-btn {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 6px 14px; border-radius: 9px; font-size: 12px; font-weight: 600;
    background: #f8fafc; border: 1px solid #e2e8f0; color: #0f172a;
    text-decoration: none; transition: all 0.14s ease;
}
.prod-page-btn:hover { background: #0f172a; color: #ffffff; border-color: #0f172a; }

/* Checkbox */
/* Action Buttons */
.prod-act-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    height: 28px;
    min-height: 28px;
    line-height: 26px;
    padding: 0 12px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    border: 1px solid transparent;
    cursor: pointer;
    transition: all 0.15s ease;
    font-family: inherit;
    text-decoration: none;
    white-space: nowrap;
    box-sizing: border-box;
}
.prod-act-edit {
    background: #f1f5f9;
    border-color: #cbd5e1;
    color: #334155;
}
.prod-act-edit:hover {
    background: #e2e8f0;
    color: #0f172a;
}
.prod-act-delete {
    background: #fef2f2;
    border-color: #fca5a5;
    color: #dc2626;
}
.prod-act-delete:hover {
    background: #fee2e2;
    color: #991b1b;
}
.prod-act-restore {
    background: #f0fdf4;
    border-color: #86efac;
    color: #16a34a;
}
.prod-act-restore:hover {
    background: #dcfce7;
    color: #15803d;
}

/* ════════════════════════════════════════════════════════
   PRODUCT DETAILS SLIDE-IN PANEL
   ════════════════════════════════════════════════════════ */

/* Backdrop */
.pdp-backdrop {
    position: fixed;
    inset: 0;
    z-index: 9100;
    background: rgba(15,23,42,0.32);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.25s ease;
}
.pdp-backdrop.active {
    opacity: 1;
    pointer-events: auto;
}

/* Panel */
.pdp-panel {
    position: fixed;
    top: 0;
    right: 0;
    bottom: 0;
    width: 420px;
    max-width: 100vw;
    z-index: 9200;
    background: #ffffff;
    border-left: 1px solid #e2e8f0;
    box-shadow: -8px 0 40px rgba(15,23,42,0.12);
    display: flex;
    flex-direction: column;
    transform: translateX(100%);
    transition: transform 0.28s cubic-bezier(0.22, 1, 0.36, 1);
    overflow: hidden;
}
.pdp-panel.active {
    transform: translateX(0);
}

/* Panel Top Bar */
.pdp-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 20px;
    border-bottom: 1px solid #f1f5f9;
    flex-shrink: 0;
    background: #f8fafc;
}
.pdp-topbar-title {
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #94a3b8;
}
.pdp-close-btn {
    display: flex; align-items: center; justify-content: center;
    width: 28px; height: 28px; border-radius: 8px;
    background: #f1f5f9; border: 1px solid #e2e8f0;
    color: #64748b; cursor: pointer;
    transition: all 0.14s ease; font-size: 0;
}
.pdp-close-btn:hover { background: #0f172a; color: #ffffff; border-color: #0f172a; }

/* Scrollable body */
.pdp-body {
    flex: 1;
    overflow-y: auto;
    padding: 0 0 24px;
}

/* Product hero area */
.pdp-hero {
    padding: 24px 20px 20px;
    border-bottom: 1px solid #f1f5f9;
}

.pdp-image-box {
    width: 72px;
    height: 72px;
    border-radius: 14px;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    overflow: hidden;
    margin-bottom: 14px;
}
.pdp-image-box img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.pdp-product-name {
    font-size: 16px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.02em;
    line-height: 1.3;
    margin-bottom: 8px;
}

.pdp-status-row {
    display: flex;
    align-items: center;
    gap: 8px;
}

.pdp-stock-highlight {
    display: flex;
    align-items: baseline;
    gap: 4px;
    margin-top: 14px;
    padding: 10px 14px;
    background: #0f172a;
    border-radius: 12px;
}
.pdp-stock-label {
    font-size: 11px;
    font-weight: 600;
    color: rgba(255,255,255,0.6);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.pdp-stock-value {
    font-size: 22px;
    font-weight: 900;
    color: #ffffff;
    letter-spacing: -0.02em;
}
.pdp-stock-unit {
    font-size: 11px;
    color: rgba(255,255,255,0.5);
    margin-left: auto;
    font-weight: 600;
}

/* Detail fields section */
.pdp-section {
    padding: 16px 20px;
    border-bottom: 1px solid #f8fafc;
}
.pdp-section-title {
    font-size: 9px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: #cbd5e1;
    margin-bottom: 12px;
}
.pdp-field-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px 16px;
}
.pdp-field-full { grid-column: span 2; }
.pdp-field-label {
    font-size: 10px;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin-bottom: 2px;
}
.pdp-field-value {
    font-size: 13px;
    font-weight: 600;
    color: #0f172a;
    line-height: 1.35;
}
.pdp-field-value.mono {
    font-family: 'SF Mono', 'Fira Code', 'Courier New', monospace;
    font-size: 12px;
    background: #f1f5f9;
    display: inline-block;
    padding: 2px 7px;
    border-radius: 5px;
}
.pdp-field-value.price {
    font-size: 15px;
    font-weight: 900;
    letter-spacing: -0.02em;
}
.pdp-field-value.cost {
    font-size: 13px;
    font-weight: 600;
    color: #475569;
}
.pdp-field-value.muted { color: #64748b; font-weight: 500; }

/* Actions footer */
.pdp-actions {
    padding: 16px 20px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    border-top: 1px solid #f1f5f9;
    flex-shrink: 0;
}
.pdp-actions-title {
    font-size: 9px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: #cbd5e1;
    margin-bottom: 4px;
}
.pdp-action-row {
    display: flex;
    gap: 8px;
}
.pdp-act-btn {
    flex: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 9px 14px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 700;
    border: 1px solid transparent;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.15s ease;
    font-family: inherit;
}
.pdp-act-edit {
    background: #0f172a;
    color: #ffffff;
}
.pdp-act-edit:hover { background: #1e293b; }

.pdp-act-toggle-deactivate {
    background: #fffbeb;
    border-color: rgba(217,119,6,0.25);
    color: #d97706;
}
.pdp-act-toggle-deactivate:hover { background: #fef3c7; }

.pdp-act-toggle-activate {
    background: #f0fdf4;
    border-color: rgba(22,163,74,0.25);
    color: #16a34a;
}
.pdp-act-toggle-activate:hover { background: #dcfce7; }

.pdp-act-delete {
    background: #fef2f2;
    border-color: rgba(220,38,38,0.25);
    color: #dc2626;
}
.pdp-act-delete:hover { background: #fee2e2; }

/* ── Export Progress Modal ── */
.exp-backdrop {
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
.exp-backdrop.active {
    opacity: 1;
    pointer-events: auto;
}
.exp-modal {
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
.exp-modal.active {
    opacity: 1;
    pointer-events: auto;
    transform: translate(-50%, -50%) scale(1);
}
.exp-modal-header {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 16px;
}
.exp-icon-box {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.exp-spinner {
    animation: expSpin 1s linear infinite;
}
@keyframes expSpin {
    to { transform: rotate(360deg); }
}
.exp-title {
    font-size: 14px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.01em;
}
.exp-subtitle {
    font-size: 11px;
    font-weight: 500;
    color: #64748b;
    margin-top: 2px;
}
.exp-progress-wrapper {
    height: 8px;
    background: #e2e8f0;
    border-radius: 999px;
    overflow: hidden;
    margin-bottom: 12px;
    position: relative;
}
.exp-progress-bar {
    height: 100%;
    background: #0f172a;
    border-radius: 999px;
    transition: width 0.25s ease;
}
.exp-modal-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.exp-status-text {
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
}
.exp-close-btn {
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

<?php
$isDeletedView = ($viewMode === 'deleted');
$hasActiveFilters = !empty($filters['brand_id']) || !empty($filters['category_id']) || !empty($filters['supplier_id']) || !empty($filters['status']);
$deletedCount = count($deletedProducts ?? []);
?>
<div style="width: 100%; max-width: 100%; height: calc(100vh - 110px); display: flex; flex-direction: column; box-sizing: border-box; overflow: hidden; padding-bottom: 8px;">

<!-- Top Right Fixed Floating Header Bubbles (Actions & Trash Bin) -->
<div id="curtiss-floating-header-right" class="no-print" style="position: fixed; top: 16px; right: 20px; z-index: 1050; display: flex; align-items: center; gap: 8px;">
    <?php if ($isDeletedView): ?>
        <a href="products" class="top-bubble-pill top-action-btn">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            Active Products
        </a>
    <?php else: ?>
        <a href="products?view=deleted" class="top-bubble-pill top-action-btn" style="color: #dc2626; background: #fef2f2; border-color: #fca5a5;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
            Trash Bin (<?= $deletedCount ?>)
        </a>

        <?php if (Auth::hasPermission('products.import')): ?>
        <a href="product_import" class="top-bubble-pill top-action-btn">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            Import CSV
        </a>
        <?php endif; ?>

        <?php if (Auth::hasPermission('products.export')): ?>
        <a href="product_export?<?= $queryString ?>" class="top-bubble-pill top-action-btn" title="Export Product Catalog to CSV" onclick="startExportCSV(event, this.href)">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Export CSV
        </a>
        <?php endif; ?>

        <?php if (Auth::hasPermission('products.create')): ?>
        <a href="product_create" class="top-bubble-pill top-action-btn top-btn-primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Product
        </a>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Page Header Title & Inline Filter Controls (Rule 3, Rule 5 & Rule 10) -->
<div class="prod-header" style="flex-shrink: 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 0.85rem;">
    <div>
        <div style="display: flex; align-items: center; gap: 8px;">
            <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
            <h1 class="prod-title" style="margin: 0; font-size: 1.35rem; font-weight: 800; color: #0f172a;"><?= $isDeletedView ? 'Products (Trash Bin)' : 'Products' ?></h1>
        </div>
    </div>

    <?php if (!$isDeletedView): ?>
    <!-- Inline Filter Dropdowns Above Table Top Right Corner (Rule 3) -->
    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 6px;">
            <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Brand:</span>
            <select id="filterBrand" name="brand_id" onchange="applyProductFilters()" class="curtiss-select-enhance" style="height: 34px; min-height: 34px; padding: 0 14px; border-radius: 999px; font-size: 12px; font-weight: 700; color: #334155; background: rgba(255,255,255,0.95); border: 1px solid rgba(203,213,225,0.9); box-shadow: 0 2px 8px rgba(0,0,0,0.04); min-width: 150px;">
                <option value="">All Brands</option>
                <?php foreach ($brands as $b): ?>
                    <option value="<?= $b['id'] ?>" <?= ((int)$filters['brand_id'] === (int)$b['id']) ? 'selected' : '' ?>><?= View::escape($b['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="display: flex; align-items: center; gap: 6px;">
            <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Category:</span>
            <select id="filterCategory" name="category_id" onchange="applyProductFilters()" class="curtiss-select-enhance" style="height: 34px; min-height: 34px; padding: 0 14px; border-radius: 999px; font-size: 12px; font-weight: 700; color: #334155; background: rgba(255,255,255,0.95); border: 1px solid rgba(203,213,225,0.9); box-shadow: 0 2px 8px rgba(0,0,0,0.04); min-width: 150px;">
                <option value="">All Categories</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= ((int)$filters['category_id'] === (int)$c['id']) ? 'selected' : '' ?>><?= View::escape($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="display: flex; align-items: center; gap: 6px;">
            <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Supplier:</span>
            <select id="filterSupplier" name="supplier_id" onchange="applyProductFilters()" class="curtiss-select-enhance" style="height: 34px; min-height: 34px; padding: 0 14px; border-radius: 999px; font-size: 12px; font-weight: 700; color: #334155; background: rgba(255,255,255,0.95); border: 1px solid rgba(203,213,225,0.9); box-shadow: 0 2px 8px rgba(0,0,0,0.04); min-width: 160px;">
                <option value="">All Suppliers</option>
                <?php foreach ($suppliers as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= ((int)$filters['supplier_id'] === (int)$s['id']) ? 'selected' : '' ?>><?= View::escape($s['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="display: flex; align-items: center; gap: 6px;">
            <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Status:</span>
            <select id="filterStatus" name="status" onchange="applyProductFilters()" class="curtiss-select-enhance" style="height: 34px; min-height: 34px; padding: 0 14px; border-radius: 999px; font-size: 12px; font-weight: 700; color: #334155; background: rgba(255,255,255,0.95); border: 1px solid rgba(203,213,225,0.9); box-shadow: 0 2px 8px rgba(0,0,0,0.04); min-width: 130px;">
                <option value="">All Statuses</option>
                <option value="active" <?= ($filters['status'] === 'active') ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= ($filters['status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>

        <?php if ($hasActiveFilters): ?>
            <a href="products" class="prod-act-btn prod-act-edit" style="height: 34px; min-height: 34px; padding: 0 14px; border-radius: 999px; font-size: 11px; text-decoration: none; color: #ef4444; background: #fef2f2; border-color: #fca5a5;">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                Reset
            </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Alert Messages -->
<?php if (!empty($message)): ?>
    <div class="prod-alert prod-alert-success">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        <?= View::escape($message) ?>
    </div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="prod-alert prod-alert-danger">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        <?= View::escape($error) ?>
    </div>
<?php endif; ?>
<?php if (isset($_GET['created'])): ?><div class="prod-alert prod-alert-success"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Product created successfully.</div><?php endif; ?>
<?php if (isset($_GET['updated'])): ?><div class="prod-alert prod-alert-success"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Product updated successfully.</div><?php endif; ?>
<?php if (isset($_GET['toggled'])): ?><div class="prod-alert prod-alert-success"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Product status updated successfully.</div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="prod-alert prod-alert-success"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Product moved to Trash Bin successfully.</div><?php endif; ?>
<?php if (isset($_GET['restored'])): ?><div class="prod-alert prod-alert-success"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Product restored successfully from Trash Bin.</div><?php endif; ?>

<!-- Bulk Actions Form + Table -->
<form id="bulkForm" method="POST" action="products.php" style="flex: 1; min-height: 0; display: flex; flex-direction: column;">
    <input type="hidden" name="action" value="bulk_action">
    <input type="hidden" name="bulk_type" id="bulk_type_input" value="">

    <!-- Bulk Toolbar -->
    <div class="prod-bulk-toolbar" id="bulkToolbar">
        <div class="prod-bulk-count"><span id="selectedCount">0</span> selected</div>
        <div class="prod-bulk-actions">
            <button type="button" onclick="executeBulkAction('activate')" class="prod-bulk-btn prod-bulk-btn-activate">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Activate
            </button>
            <button type="button" onclick="executeBulkAction('deactivate')" class="prod-bulk-btn prod-bulk-btn-deactivate">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="8" y1="12" x2="16" y2="12"/></svg>Deactivate
            </button>
            <select id="bulk_category_id" name="bulk_category_id" class="prod-bulk-select">
                <option value="">— Category —</option>
                <?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>"><?= View::escape($c['name']) ?></option><?php endforeach; ?>
            </select>
            <button type="button" onclick="executeBulkAction('update_category')" class="prod-bulk-btn prod-bulk-btn-neutral">Apply Cat</button>
            <select id="bulk_brand_id" name="bulk_brand_id" class="prod-bulk-select">
                <option value="">— Brand —</option>
                <?php foreach ($brands as $b): ?><option value="<?= $b['id'] ?>"><?= View::escape($b['name']) ?></option><?php endforeach; ?>
            </select>
            <button type="button" onclick="executeBulkAction('update_brand')" class="prod-bulk-btn prod-bulk-btn-neutral">Apply Brand</button>
            <button type="button" onclick="exportSelected()" class="prod-bulk-btn prod-bulk-btn-neutral">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>Export
            </button>
        </div>
    </div>

    <!-- Product Table -->
    <div class="prod-table-panel" style="flex: 1; min-height: 0; display: flex; flex-direction: column; overflow: hidden;">
        <div class="prod-table-scroll" style="overflow-y: auto; flex: 1;">
            <table class="prod-table">
                <thead>
                    <tr>
                        <th class="col-check" style="text-align: center;"><input type="checkbox" class="prod-checkbox" id="selectAllCheckbox" onclick="toggleSelectAll(this)"></th>
                        <th style="text-align: left;">SKU</th>
                        <th style="text-align: left;">Barcode</th>
                        <th style="text-align: left;">Product Name</th>
                        <th class="col-right" style="text-align: right;">Cost (LKR)</th>
                        <th class="col-right" style="text-align: right;">Selling Price (LKR)</th>
                        <th style="text-align: left;">Status</th>
                        <th class="col-right" style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody id="productTableBody">
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="8">
                                <div class="prod-empty">
                                    <div class="prod-empty-icon">
                                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
                                    </div>
                                    <div class="prod-empty-title">No products found</div>
                                    <div class="prod-empty-text">No products match the selected criteria. Try adjusting your filters.</div>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $p):
                            $isActive = ($p['status'] === 'active');
                            $canManage = Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER);
                            // JSON-encode product for JS panel
                            $pj = json_encode([
                                'id'            => (int)$p['id'],
                                'name'          => $p['name'] ?? '',
                                'sku'           => $p['sku'] ?? '',
                                'barcode'       => $p['barcode'] ?? '',
                                'brand'         => $p['brand_name'] ?? '',
                                'category'      => $p['category_name'] ?? '',
                                'supplier'      => $p['supplier_name'] ?? '',
                                'cost'          => number_format((float)$p['cost'], 2),
                                'price'         => number_format((float)$p['selling_price'], 2),
                                'stock'         => number_format((float)$p['opening_stock'], 2),
                                'status'        => $p['status'] ?? 'inactive',
                                'has_expiry'    => (int)($p['has_expiry'] ?? 0),
                                'image'         => $p['image_url'] ?? '',
                                'can_manage'    => $canManage,
                            ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
                        ?>
                        <tr onclick="openProductPanel(<?= htmlspecialchars($pj, ENT_QUOTES) ?>)" data-product-id="<?= $p['id'] ?>">
                            <td class="col-check" onclick="event.stopPropagation()">
                                <input type="checkbox" name="product_ids[]" value="<?= $p['id'] ?>" class="prod-checkbox product-checkbox" onclick="updateBulkToolbar()">
                            </td>
                            <td>
                                <?php if (!empty($p['sku'])): ?>
                                    <span class="prod-code" style="background:#e0f2fe;color:#0369a1;border:1px solid #bae6fd;"><?= View::escape($p['sku']) ?></span>
                                <?php else: ?>
                                    <span style="color:#cbd5e1;font-size:11px;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($p['barcode'])): ?>
                                    <span class="prod-code"><?= View::escape($p['barcode']) ?></span>
                                <?php else: ?>
                                    <span style="color:#cbd5e1;font-size:11px;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="prod-name-cell"><?= View::escape($p['name']) ?></span>
                                <?php if (!empty($p['has_expiry'])): ?>
                                    <span style="display:inline-flex;align-items:center;gap:3px;font-size:10px;font-weight:700;padding:1px 6px;border-radius:6px;background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe;margin-left:6px;" title="Expiry & Batch Tracking Enabled">
                                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                        Exp
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="col-right"><span class="prod-cost"><?= number_format((float)$p['cost'], 2) ?></span></td>
                            <td class="col-right"><span class="prod-price"><?= number_format((float)$p['selling_price'], 2) ?></span></td>
                            <td>
                                <?php if ($isActive): ?>
                                    <span class="prod-badge-active" onclick="event.stopPropagation(); submitToggle(<?= $p['id'] ?>);" style="cursor: pointer;" title="Click to toggle status"><svg width="6" height="6" viewBox="0 0 8 8" fill="#16a34a"><circle cx="4" cy="4" r="4"/></svg>Active</span>
                                <?php else: ?>
                                    <span class="prod-badge-inactive" onclick="event.stopPropagation(); submitToggle(<?= $p['id'] ?>);" style="cursor: pointer;" title="Click to toggle status"><svg width="6" height="6" viewBox="0 0 8 8" fill="#dc2626"><circle cx="4" cy="4" r="4"/></svg>Deactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="col-right" onclick="event.stopPropagation()" style="text-align: right; white-space: nowrap;">
                                <?php if ($isDeletedView): ?>
                                    <?php if ($canManage): ?>
                                        <button type="button" class="prod-act-btn prod-act-restore" onclick="submitRestore(<?= $p['id'] ?>)">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><polyline points="3 3 3 8 8 8"/></svg>
                                            Restore
                                        </button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if ($canManage): ?>
                                        <div style="display: inline-flex; align-items: center; gap: 6px;">
                                            <a href="product_edit?id=<?= $p['id'] ?>" class="prod-act-btn prod-act-edit" title="Edit Product">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                                Edit
                                            </a>
                                            <button type="button" class="prod-act-btn prod-act-delete" onclick="submitDelete(<?= $p['id'] ?>)" title="Delete Product">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                                                Delete
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination & Row Limit Controls (Rule 6) -->
        <div class="prod-pagination">
            <div class="prod-pagination-info">
                Showing <strong><?= $totalItems > 0 ? (($page - 1) * $limit + 1) : 0 ?></strong> to <strong><?= min($page * $limit, $totalItems) ?></strong> of <strong><?= number_format($totalItems) ?></strong> entries
            </div>
            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 6px;">
                    <span style="font-size: 11px; font-weight: 700; color: #64748b;">Per page:</span>
                    <select id="pageSizeSelect" onchange="changeProductLimit(this.value)" style="height: 30px; padding: 0 8px; font-size: 11px; font-weight: 700; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: #0f172a; outline: none; cursor: pointer;">
                        <?php foreach (($allowedLimits ?? [25, 50, 75, 100, 500]) as $al): ?>
                            <option value="<?= $al ?>" <?= ($limit === $al) ? 'selected' : '' ?>><?= $al ?> rows</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($totalPages > 1): ?>
                    <div class="prod-pagination-controls">
                        <?php if ($page > 1): ?>
                            <a href="products?<?= http_build_query(array_merge($filters, ['page' => $page - 1, 'limit' => $limit])) ?>" class="prod-page-btn">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>Previous
                            </a>
                        <?php endif; ?>
                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $startPage + 4);
                        if ($endPage - $startPage < 4) $startPage = max(1, $endPage - 4);
                        for ($p = $startPage; $p <= $endPage; $p++):
                        ?>
                            <a href="products?<?= http_build_query(array_merge($filters, ['page' => $p, 'limit' => $limit])) ?>" class="prod-page-btn" style="<?= ($p === $page) ? 'background:#0f172a; color:#ffffff; border-color:#0f172a;' : '' ?>">
                                <?= $p ?>
                            </a>
                        <?php endfor; ?>
                        <?php if ($page < $totalPages): ?>
                            <a href="products?<?= http_build_query(array_merge($filters, ['page' => $page + 1, 'limit' => $limit])) ?>" class="prod-page-btn">
                                Next<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</form>
</div>

<!-- ═══════════════════════════════════════════════════
     PRODUCT DETAILS PANEL (Right Slide-In)
     ═══════════════════════════════════════════════════ -->
<div class="pdp-backdrop" id="pdpBackdrop" onclick="closeProductPanel()"></div>

<div class="pdp-panel" id="pdpPanel" role="dialog" aria-modal="true" aria-label="Product Details">
    <!-- Top bar -->
    <div class="pdp-topbar">
        <span class="pdp-topbar-title">Product Details</span>
        <button class="pdp-close-btn" onclick="closeProductPanel()" aria-label="Close">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    </div>

    <!-- Body -->
    <div class="pdp-body" id="pdpBody">
        <!-- Filled by JS -->
    </div>

    <!-- Actions footer — forms injected by JS so PHP permissions stay server-side -->
    <div class="pdp-actions" id="pdpActions" style="display:none;">
        <div class="pdp-actions-title">Actions</div>
        <div class="pdp-action-row" id="pdpActionRow"></div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════
     EXPORT PROGRESS MODAL
     ═══════════════════════════════════════════════════ -->
<div class="exp-backdrop" id="expBackdrop" onclick="closeExportModal()"></div>
<div class="exp-modal" id="expModal" role="dialog" aria-modal="true" aria-label="Export Progress">
    <div class="exp-modal-header">
        <div class="exp-icon-box" id="expIconBox">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0f172a" stroke-width="2.5" class="exp-spinner">
                <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
            </svg>
        </div>
        <div>
            <div class="exp-title" id="expTitle">Exporting Catalog</div>
            <div class="exp-subtitle" id="expSubtitle">Preparing product records...</div>
        </div>
    </div>
    <div class="exp-progress-wrapper">
        <div class="exp-progress-bar" id="expProgressBar" style="width: 0%;"></div>
    </div>
    <div class="exp-modal-footer">
        <span class="exp-status-text" id="expStatusText">0%</span>
        <button type="button" class="exp-close-btn" id="expCloseBtn" onclick="closeExportModal()" style="display:none;">Close</button>
    </div>
</div>

<?php
/* ── Inline permission flags for JS ── */
$_canManage = Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER) ? 'true' : 'false';
$_canEdit   = Auth::hasPermission('products.edit') || Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER) ? 'true' : 'false';
$_canDelete = Auth::hasPermission('products.delete') || Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER) ? 'true' : 'false';
?>
<script>
const _canManage = <?= $_canManage ?>;
const _canEdit   = <?= $_canEdit ?>;
const _canDelete = <?= $_canDelete ?>;

/* ── Bulk actions ── */
function toggleSelectAll(master) {
    document.querySelectorAll('.product-checkbox').forEach(cb => cb.checked = master.checked);
    updateBulkToolbar();
}
function updateBulkToolbar() {
    const checked = document.querySelectorAll('.product-checkbox:checked');
    const toolbar = document.getElementById('bulkToolbar');
    document.getElementById('selectedCount').textContent = checked.length;
    toolbar.classList.toggle('visible', checked.length > 0);
}
function executeBulkAction(actionType) {
    const checked = document.querySelectorAll('.product-checkbox:checked');
    if (!checked.length) { alert('Please select at least one product.'); return; }
    if (confirm('Apply bulk action "' + actionType + '" to ' + checked.length + ' product(s)?')) {
        document.getElementById('bulk_type_input').value = actionType;
        document.getElementById('bulkForm').submit();
    }
}
function exportSelected() {
    const checked = document.querySelectorAll('.product-checkbox:checked');
    if (!checked.length) { alert('Please select at least one product to export.'); return; }
    const url = 'product_export?ids=' + Array.from(checked).map(cb => cb.value).join(',');
    startExportCSV(null, url);
}

/* ── Product Details Panel ── */
let _currentProduct = null;

function openProductPanel(product) {
    _currentProduct = product;
    renderPanel(product);
    document.getElementById('pdpBackdrop').classList.add('active');
    document.getElementById('pdpPanel').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeProductPanel() {
    document.getElementById('pdpBackdrop').classList.remove('active');
    document.getElementById('pdpPanel').classList.remove('active');
    document.body.style.overflow = '';
    _currentProduct = null;
}

function esc(str) {
    if (!str) return '—';
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

function renderPanel(p) {
    const isActive = p.status === 'active';
    const statusBadge = isActive
        ? `<span class="prod-badge-active"><svg width="6" height="6" viewBox="0 0 8 8" fill="#16a34a"><circle cx="4" cy="4" r="4"/></svg>Active</span>`
        : `<span class="prod-badge-inactive"><svg width="6" height="6" viewBox="0 0 8 8" fill="#dc2626"><circle cx="4" cy="4" r="4"/></svg>Inactive</span>`;

    // SKU & Barcode separate fields
    const skuVal = p.sku ? `<span class="pdp-field-value mono">${esc(p.sku)}</span>` : `<span class="pdp-field-value muted">—</span>`;
    const barcodeVal = p.barcode ? `<span class="pdp-field-value mono">${esc(p.barcode)}</span>` : `<span class="pdp-field-value muted">—</span>`;

    document.getElementById('pdpBody').innerHTML = `
        <div class="pdp-hero">
            <div class="pdp-product-name">${esc(p.name)}</div>
            <div class="pdp-status-row">${statusBadge}</div>
            <div class="pdp-stock-highlight">
                <div>
                    <div class="pdp-stock-label">Available Stock</div>
                    <div class="pdp-stock-value">${esc(p.stock)}</div>
                </div>
                <div class="pdp-stock-unit">units</div>
            </div>
        </div>

        <div class="pdp-section">
            <div class="pdp-section-title">Identification</div>
            <div class="pdp-field-grid">
                <div>
                    <div class="pdp-field-label">SKU</div>
                    <div>${skuVal}</div>
                </div>
                <div>
                    <div class="pdp-field-label">Barcode</div>
                    <div>${barcodeVal}</div>
                </div>
            </div>
        </div>

        <div class="pdp-section">
            <div class="pdp-section-title">Classification</div>
            <div class="pdp-field-grid">
                <div>
                    <div class="pdp-field-label">Brand</div>
                    <div class="pdp-field-value">${esc(p.brand)}</div>
                </div>
                <div>
                    <div class="pdp-field-label">Category</div>
                    <div class="pdp-field-value">${esc(p.category)}</div>
                </div>
                <div class="pdp-field-full">
                    <div class="pdp-field-label">Supplier</div>
                    <div class="pdp-field-value">${esc(p.supplier)}</div>
                </div>
            </div>
        </div>

        <div class="pdp-section">
            <div class="pdp-section-title">Pricing & Expiry</div>
            <div class="pdp-field-grid">
                <div>
                    <div class="pdp-field-label">Cost Price</div>
                    <div class="pdp-field-value cost">${esc(p.cost)}</div>
                </div>
                <div>
                    <div class="pdp-field-label">Selling Price</div>
                    <div class="pdp-field-value price">${esc(p.price)}</div>
                </div>
                <div class="pdp-field-full">
                    <div class="pdp-field-label">Expiry Tracking</div>
                    <div class="pdp-field-value">${p.has_expiry ? '<span style="color:#2563eb;font-weight:700;display:inline-flex;align-items:center;gap:4px;"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>Enabled (FEFO Priority)</span>' : '<span style="color:#64748b;">Standard Stock (No Expiry)</span>'}</div>
                </div>
            </div>
        </div>
    `;

    // Action buttons (only if user can manage)
    const actionsEl  = document.getElementById('pdpActions');
    const actionRow  = document.getElementById('pdpActionRow');

    if (_canManage) {
        actionsEl.style.display = 'block';
        const toggleClass = isActive ? 'pdp-act-toggle-deactivate' : 'pdp-act-toggle-activate';
        const toggleLabel = isActive ? 'Deactivate' : 'Activate';
        const toggleIcon  = isActive
            ? `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="8" y1="12" x2="16" y2="12"/></svg>`
            : `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>`;

        actionRow.innerHTML = `
            <a href="product_edit?id=${p.id}" class="pdp-act-btn pdp-act-edit">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                Edit
            </a>
            <button type="button" class="pdp-act-btn ${toggleClass}" onclick="submitToggle(${p.id})">
                ${toggleIcon} ${toggleLabel}
            </button>
            <button type="button" class="pdp-act-btn pdp-act-delete" onclick="submitDelete(${p.id})">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                Delete
            </button>
        `;
    } else {
        actionsEl.style.display = 'none';
    }
}

let _isSubmitting = false;

function submitToggle(productId) {
    if (_isSubmitting) return;
    _isSubmitting = true;
    const f = document.createElement('form');
    f.method = 'POST';
    f.action = 'products.php';
    f.innerHTML = `<input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="${productId}">`;
    document.body.appendChild(f);
    f.submit();
}

function submitDelete(productId) {
    if (_isSubmitting) return;
    if (!confirm('Are you sure you want to delete this product? If historical transactions exist, deletion will be blocked and deactivation offered.')) return;
    _isSubmitting = true;
    const f = document.createElement('form');
    f.method = 'POST';
    f.action = 'products.php';
    f.innerHTML = `<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="${productId}">`;
    document.body.appendChild(f);
    f.submit();
}

/* ── Inline Filter Application (Rule 3) ── */
function applyProductFilters() {
    const brandId = document.getElementById('filterBrand')?.value || '';
    const categoryId = document.getElementById('filterCategory')?.value || '';
    const supplierId = document.getElementById('filterSupplier')?.value || '';
    const status = document.getElementById('filterStatus')?.value || '';
    const limit = document.getElementById('pageSizeSelect')?.value || '25';

    const params = new URLSearchParams(window.location.search);
    if (brandId) params.set('brand_id', brandId); else params.delete('brand_id');
    if (categoryId) params.set('category_id', categoryId); else params.delete('category_id');
    if (supplierId) params.set('supplier_id', supplierId); else params.delete('supplier_id');
    if (status) params.set('status', status); else params.delete('status');
    params.set('limit', limit);
    params.set('page', '1');

    window.location.href = 'products?' + params.toString();
}

function changeProductLimit(newLimit) {
    const params = new URLSearchParams(window.location.search);
    params.set('limit', newLimit);
    params.set('page', '1');
    window.location.href = 'products?' + params.toString();
}

/* ── Realtime Search in Floating Bottom Nav Bar (Rule 4) ── */
const _currentSearch = <?= json_encode($filters['search'] ?? '') ?>;

function registerBottomSearch() {
    if (window.CurtissNav && window.CurtissNav.enablePageSearch) {
        window.CurtissNav.enablePageSearch({
            placeholder: 'Search products, SKU, barcode, price, cost...',
            onSearch: function(query) {
                filterTableRealtime(query);
            }
        });

        const searchInput = document.getElementById('dock-search-input');
        if (searchInput && _currentSearch) {
            searchInput.value = _currentSearch;
        }

        // Enter key initiates server-side search across entire dataset
        if (searchInput && !searchInput.dataset.enterBound) {
            searchInput.dataset.enterBound = 'true';
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const q = searchInput.value.trim();
                    const params = new URLSearchParams(window.location.search);
                    if (q) {
                        params.set('search', q);
                    } else {
                        params.delete('search');
                    }
                    params.set('page', '1');
                    window.location.href = 'products?' + params.toString();
                }
            });
        }
    } else {
        setTimeout(registerBottomSearch, 40);
    }
}
registerBottomSearch();

function filterTableRealtime(query) {
    const tbody = document.getElementById('productTableBody');
    const existingEmpty = document.getElementById('clientSearchEmptyRow');
    if (existingEmpty) existingEmpty.remove();

    if (!query) {
        document.querySelectorAll('#productTableBody tr').forEach(tr => tr.style.display = '');
        return;
    }
    const q = query.toLowerCase().trim();
    const qClean = q.replace(/^(rs\.?|lkr)\s*/i, '').replace(/,/g, '').trim();

    const rows = document.querySelectorAll('#productTableBody tr');
    let visibleCount = 0;

    rows.forEach(tr => {
        if (tr.querySelector('.prod-empty')) return;
        const dataTexts = [];
        tr.querySelectorAll('td:not(.col-check):not(.col-right:last-child)').forEach(td => {
            const clone = td.cloneNode(true);
            clone.querySelectorAll('.prod-act-btn, button, a').forEach(el => el.remove());
            const raw = clone.textContent.trim();
            if (raw) {
                dataTexts.push(raw);
                const noComma = raw.replace(/,/g, '');
                dataTexts.push(noComma);
                const noCurrencyNoComma = noComma.replace(/^(rs\.?|lkr)\s*/gi, '').trim();
                dataTexts.push(noCurrencyNoComma);
                if (noCurrencyNoComma.includes('.')) {
                    dataTexts.push(noCurrencyNoComma.split('.')[0]);
                }
            }
        });
        const fullText = dataTexts.join(' ').toLowerCase();
        if (fullText.includes(q) || (qClean && fullText.includes(qClean))) {
            tr.style.display = '';
            visibleCount++;
        } else {
            tr.style.display = 'none';
        }
    });

    if (visibleCount === 0 && tbody) {
        const noMatchTr = document.createElement('tr');
        noMatchTr.id = 'clientSearchEmptyRow';
        noMatchTr.innerHTML = `
            <td colspan="8" style="text-align: center; padding: 36px 16px; color: #64748b;">
                <div style="font-size: 13px; font-weight: 700; color: #0f172a; margin-bottom: 6px;">No products matching "${esc(query)}" on this page</div>
                <div style="font-size: 12px; margin-bottom: 12px;">Press <kbd style="background:#f1f5f9; padding:2px 6px; border-radius:4px; border:1px solid #cbd5e1; font-weight:700;">Enter</kbd> to search the entire catalog, or try different keywords.</div>
                <button type="button" onclick="executeServerSearch('${encodeURIComponent(query)}')" class="prod-act-btn prod-act-primary" style="background:#0f172a; color:#fff; border-color:#0f172a;">Search All Pages</button>
            </td>
        `;
        tbody.appendChild(noMatchTr);
    }
}

function executeServerSearch(encodedQuery) {
    const params = new URLSearchParams(window.location.search);
    if (encodedQuery) {
        params.set('search', decodeURIComponent(encodedQuery));
    } else {
        params.delete('search');
    }
    params.set('page', '1');
    window.location.href = 'products?' + params.toString();
}

function submitRestore(productId) {
    if (_isSubmitting) return;
    _isSubmitting = true;
    const f = document.createElement('form');
    f.method = 'POST';
    f.action = 'products.php';
    f.innerHTML = `<input type="hidden" name="action" value="restore"><input type="hidden" name="id" value="${productId}">`;
    document.body.appendChild(f);
    f.submit();
}

/* ── CSV Export Progress Modal Handler ── */
function startExportCSV(event, exportUrl) {
    if (event) event.preventDefault();

    const backdrop = document.getElementById('expBackdrop');
    const modal = document.getElementById('expModal');
    const bar = document.getElementById('expProgressBar');
    const statusText = document.getElementById('expStatusText');
    const title = document.getElementById('expTitle');
    const subtitle = document.getElementById('expSubtitle');
    const iconBox = document.getElementById('expIconBox');
    const closeBtn = document.getElementById('expCloseBtn');

    title.textContent = 'Exporting Catalog';
    subtitle.textContent = 'Preparing product records...';
    statusText.textContent = '0%';
    bar.style.width = '0%';
    closeBtn.style.display = 'none';
    iconBox.innerHTML = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0f172a" stroke-width="2.5" class="exp-spinner"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>`;

    backdrop.classList.add('active');
    modal.classList.add('active');

    let currentProgress = 0;
    const progressInterval = setInterval(() => {
        if (currentProgress < 85) {
            currentProgress += Math.floor(Math.random() * 12) + 6;
            if (currentProgress > 85) currentProgress = 85;
            bar.style.width = currentProgress + '%';
            statusText.textContent = currentProgress + '%';
            if (currentProgress > 30) subtitle.textContent = 'Generating CSV file data...';
        }
    }, 180);

    const xhr = new XMLHttpRequest();
    xhr.open('GET', exportUrl, true);
    xhr.responseType = 'blob';

    xhr.onprogress = function(e) {
        if (e.lengthComputable) {
            const pct = Math.round((e.loaded / e.total) * 100);
            const actualPct = Math.max(currentProgress, pct);
            bar.style.width = actualPct + '%';
            statusText.textContent = actualPct + '%';
        }
    };

    xhr.onload = function() {
        clearInterval(progressInterval);
        if (xhr.status === 200) {
            bar.style.width = '100%';
            statusText.textContent = '100%';
            title.textContent = 'Export Complete';
            subtitle.textContent = 'Downloading CSV file...';
            iconBox.innerHTML = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>`;

            let filename = 'curtiss_products_master.csv';
            const disposition = xhr.getResponseHeader('Content-Disposition');
            if (disposition && disposition.indexOf('filename=') !== -1) {
                const matches = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/.exec(disposition);
                if (matches != null && matches[1]) {
                    filename = matches[1].replace(/['"]/g, '');
                }
            }

            const blob = xhr.response;
            const link = document.createElement('a');
            link.href = window.URL.createObjectURL(blob);
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            setTimeout(() => {
                closeExportModal();
            }, 1100);
        } else {
            title.textContent = 'Export Failed';
            subtitle.textContent = 'Could not generate CSV file.';
            iconBox.innerHTML = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>`;
            closeBtn.style.display = 'block';
        }
    };

    xhr.onerror = function() {
        clearInterval(progressInterval);
        title.textContent = 'Export Failed';
        subtitle.textContent = 'Network or server error.';
        closeBtn.style.display = 'block';
    };

    xhr.send();
}

function closeExportModal() {
    document.getElementById('expBackdrop').classList.remove('active');
    document.getElementById('expModal').classList.remove('active');
}

// Close panel/modal on Escape
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        if (_currentProduct) closeProductPanel();
        closeExportModal();
    }
});
</script>

<script src="assets/js/curtiss_select.js"></script>
<script>
    function initSearchableSelects() {
        document.querySelectorAll('.curtiss-select-enhance').forEach(el => {
            if (window.CurtissSearchableSelect && !el.dataset.selectEnhanced) {
                CurtissSearchableSelect.attach(el);
                el.dataset.selectEnhanced = 'true';
            }
        });
    }

    document.addEventListener('DOMContentLoaded', initSearchableSelects);
</script>
