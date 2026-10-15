<?php
/**
 * Curtiss POS - Central Reports & Financial Statements View (Redesign Theme)
 * Modular Reports Center Dashboard & Dedicated Report Views.
 */
?>
<link rel="stylesheet" href="assets/css/curtiss_select.css">
<script src="assets/js/curtiss_select.js"></script>

<!-- Floating Top-Right Action Header -->
<div id="curtiss-floating-header-right" style="position: fixed; top: 16px; right: 20px; z-index: 1050; display: flex; align-items: center; gap: 8px;">
    <button type="button" class="inv-btn inv-btn-ghost" onclick="handleReportBack()" id="btnBackToHub" style="display: none;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        Reports List
    </button>

    <button type="button" class="inv-btn inv-btn-ghost" onclick="openFilterModal()" id="btnFilterTrigger" style="position: relative;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
        Filter Scope
        <span id="filterActiveDot" style="display: none; position: absolute; top: 6px; right: 8px; width: 7px; height: 7px; background: #2563eb; border-radius: 50%; border: 2px solid #fff;"></span>
    <button type="button" class="inv-btn inv-btn-ghost" onclick="openColumnCustomizerModal()" id="btnColumnTrigger" style="display: none; position: relative;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 3h7a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-7m0-18H5a2 2 0 0 0-2 2v14a2 2 0 0 1 2 2h7m0-18v18"/></svg>
        Customize Columns
        <span id="colCustomActiveDot" style="display: none; position: absolute; top: 6px; right: 8px; width: 7px; height: 7px; background: #9333ea; border-radius: 50%; border: 2px solid #fff;"></span>
    </button>

    <?php if ($canExport): ?>
    <button type="button" class="inv-btn inv-btn-ghost" onclick="openExportModal()" id="btnExportTrigger">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Export Options
    </button>
    <?php endif; ?>

    <button type="button" class="inv-btn inv-btn-primary" onclick="triggerPaperPrint()" id="btnPrintTrigger">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
        Print Statement
    </button>
</div>

<style>
/* ── Global Typography & Web Layout ── */
body, button, input, select, textarea {
    font-family: 'SF Pro Display', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
}

.inv-header {
    margin-bottom: 1.25rem;
}
.inv-title {
    font-size: 1.35rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.025em;
    line-height: 1.2;
}
.inv-subtitle {
    font-size: 0.8rem;
    color: #94a3b8;
    margin-top: 4px;
    font-weight: 500;
}

.inv-btn {
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
.inv-btn-ghost {
    background: rgba(255,255,255,0.92);
    border-color: rgba(226,232,240,0.9);
    color: #0f172a;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    backdrop-filter: blur(8px);
}
.inv-btn-ghost:hover {
    background: #ffffff;
    border-color: #cbd5e1;
    transform: translateY(-1px);
}
.inv-btn-primary {
    background: #0f172a;
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(15,23,42,0.25);
}
.inv-btn-primary:hover {
    background: #1e293b;
    transform: translateY(-1px);
}

/* ── Reports Center 2-Column Hub Layout Styles ── */
.reports-hub-layout {
    display: flex;
    height: 100%;
    width: 100%;
    background: rgba(255, 255, 255, 0.94);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-radius: 18px;
    border: 1px solid rgba(226, 232, 240, 0.85);
    box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.04);
    overflow: hidden;
}

.reports-category-sidebar {
    width: 250px;
    min-width: 250px;
    flex-shrink: 0;
    background: rgba(248, 250, 252, 0.7);
    border-right: 1px solid rgba(226, 232, 240, 0.85);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.rep-sidebar-header {
    padding: 14px 18px 10px;
    border-bottom: 1px solid rgba(226, 232, 240, 0.65);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.rep-sidebar-title {
    font-size: 10.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #64748b;
}

.rep-sidebar-nav {
    flex: 1;
    overflow-y: auto;
    padding: 10px 10px 20px;
    display: flex;
    flex-direction: column;
    gap: 4px;
    scrollbar-width: thin;
}

.rep-cat-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    padding: 8px 12px;
    border-radius: 10px;
    border: 1px solid transparent;
    background: transparent;
    color: #334155;
    font-size: 12.5px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.16s ease;
    text-align: left;
}

.rep-cat-btn:hover {
    background: rgba(241, 245, 249, 0.95);
    color: #0f172a;
}

.rep-cat-btn.active {
    background: #0f172a;
    color: #ffffff;
    font-weight: 700;
    box-shadow: 0 3px 10px rgba(15, 23, 42, 0.18);
}

.rep-cat-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    flex-shrink: 0;
}

.rep-cat-name {
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.rep-cat-count {
    font-size: 11px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 999px;
    background: rgba(226, 232, 240, 0.7);
    color: #475569;
    transition: all 0.16s ease;
}

.rep-cat-btn.active .rep-cat-count {
    background: rgba(255, 255, 255, 0.22);
    color: #ffffff;
}

.reports-list-panel {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    background: #ffffff;
    overflow: hidden;
}

.rep-list-header {
    padding: 12px 20px;
    border-bottom: 1px solid rgba(226, 232, 240, 0.8);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    background: rgba(255, 255, 255, 0.95);
    flex-shrink: 0;
}

.rep-list-header-left {
    display: flex;
    align-items: center;
    gap: 10px;
}

.rep-active-cat-title {
    font-size: 15px;
    font-weight: 800;
    color: #0f172a;
    margin: 0;
    letter-spacing: -0.01em;
}

.rep-count-badge {
    font-size: 11px;
    font-weight: 700;
    color: #2563eb;
    background: rgba(37, 99, 235, 0.08);
    padding: 3px 9px;
    border-radius: 999px;
}

.rep-list-search-box {
    position: relative;
    width: 260px;
    max-width: 100%;
}

.rep-list-search-box input {
    width: 100%;
    height: 34px;
    padding: 0 12px 0 34px;
    border-radius: 999px;
    border: 1px solid rgba(203, 213, 225, 0.8);
    font-size: 12px;
    font-weight: 600;
    outline: none;
    background: #f8fafc;
    box-sizing: border-box;
    transition: all 0.18s ease;
}

.rep-list-search-box input:focus {
    background: #ffffff;
    border-color: #0f172a;
    box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.08);
}

.reports-list-scroll {
    flex: 1;
    overflow-y: auto;
    padding: 12px 18px 24px;
    min-height: 0;
    scrollbar-width: thin;
}

.reports-items-container {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.report-row-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 10px 14px;
    border-radius: 12px;
    border: 1px solid rgba(226, 232, 240, 0.7);
    cursor: pointer;
    transition: all 0.16s ease;
    text-decoration: none;
    outline: none;
    box-sizing: border-box;
}

.report-row-item:nth-child(even) { background: rgba(248, 250, 252, 0.57); }
.report-row-item:nth-child(odd)  { background: #ffffff; }

.report-row-item:hover {
    background: #f1f5f9 !important;
    border-color: #cbd5e1;
    transform: translateX(3px);
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
}

.report-row-item:focus {
    background: #f1f5f9;
    border-color: #0f172a;
    box-shadow: 0 0 0 2px rgba(15, 23, 42, 0.15);
}

.report-row-icon {
    width: 36px;
    height: 36px;
    min-width: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(241, 245, 249, 0.95);
    color: #0f172a;
    border: 1px solid rgba(226, 232, 240, 0.8);
    transition: all 0.16s ease;
}

.report-row-item:hover .report-row-icon {
    background: #0f172a;
    color: #ffffff;
    border-color: #0f172a;
}

.report-row-info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.report-row-title {
    font-size: 13.5px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.25;
}

.report-row-desc {
    font-size: 12px;
    color: #64748b;
    line-height: 1.35;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.report-row-meta {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
}

.report-cat-badge {
    font-size: 11px;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 999px;
    background: rgba(241, 245, 249, 0.9);
    color: #475569;
    border: 1px solid rgba(226, 232, 240, 0.8);
}

.report-row-arrow {
    color: #94a3b8;
    transition: transform 0.16s ease, color 0.16s ease;
}

.report-row-item:hover .report-row-arrow {
    color: #0f172a;
    transform: translateX(2px);
}

.rep-empty-state {
    padding: 48px 20px;
    text-align: center;
    color: #64748b;
}

@media (max-width: 860px) {
    .reports-hub-layout {
        flex-direction: column;
        height: auto;
    }
    .reports-category-sidebar {
        width: 100%;
        min-width: 100%;
        border-right: none;
        border-bottom: 1px solid rgba(226, 232, 240, 0.85);
    }
    .rep-sidebar-nav {
        flex-direction: row;
        overflow-x: auto;
        padding: 8px 12px;
    }
    .rep-cat-btn {
        flex-shrink: 0;
        width: auto;
    }
    .report-row-desc {
        white-space: normal;
    }
}

/* ── Reports Main Grid Layout ── */
.reports-layout {
    display: block;
    width: 100%;
}

.reports-menu-panel {
    display: none !important;
}

.reports-menu-group {
    font-size: 0.68rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #64748b;
    margin: 1.1rem 0.6rem 0.4rem;
}
.reports-menu-group:first-child {
    margin-top: 0.2rem;
}

.reports-menu-item {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    padding: 0.625rem 0.75rem;
    font-size: 0.83rem;
    font-weight: 600;
    color: #475569;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.18s ease;
    user-select: none;
    margin-bottom: 2px;
}
.reports-menu-item svg {
    flex-shrink: 0;
    transition: transform 0.18s ease;
}
.reports-menu-item:hover {
    background: rgba(241, 245, 249, 0.8);
    color: #0f172a;
    transform: translateX(2px);
}
.reports-menu-item.active {
    background: #0f172a;
    color: #ffffff;
    font-weight: 700;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.22);
}
.reports-menu-item.active svg {
    stroke: #ffffff;
}

.report-view-container {
    background: rgba(255, 255, 255, 0.96);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(226, 232, 240, 0.9);
    border-radius: 18px;
    padding: 1.25rem;
    box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.05);
    height: calc(100vh - 110px);
    display: flex;
    flex-direction: column;
    box-sizing: border-box;
    overflow: hidden;
}

.report-header-action {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: 0.75rem;
    margin-bottom: 1rem;
    flex-shrink: 0;
}
.report-title-text {
    font-size: 1.25rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.015em;
}
.report-subtitle-text {
    display: none !important;
}

#reportContentArea {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 0;
    overflow: hidden;
}

.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
    flex-shrink: 0;
}
.kpi-card {
    background: rgba(255, 255, 255, 0.85);
    border: 1px solid rgba(226, 232, 240, 0.95);
    border-radius: 16px;
    padding: 1.2rem;
    box-shadow: 0 4px 12px -2px rgba(0, 0, 0, 0.03);
    transition: all 0.2s ease;
}
.kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 24px -4px rgba(0, 0, 0, 0.06);
    border-color: #cbd5e1;
}
.kpi-title {
    font-size: 0.7rem;
    font-weight: 800;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin-bottom: 0.4rem;
}
.kpi-value {
    font-size: 1.55rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.02em;
}
.kpi-sub {
    font-size: 0.75rem;
    font-weight: 600;
    color: #94a3b8;
    margin-top: 0.3rem;
}

.inv-table-panel {
    flex: 1;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    overflow-y: auto;
    overflow-x: auto;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
    min-height: 0;
    position: relative;
    width: 100%;
    box-sizing: border-box;
}
.financial-table {
    width: 100% !important;
    min-width: 100%;
    border-collapse: collapse !important;
    border-spacing: 0;
    font-size: 0.83rem;
    table-layout: auto;
}
.financial-table th {
    position: sticky;
    top: 0;
    z-index: 10;
    background: #f8fafc;
    color: #64748b;
    font-weight: 800;
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    text-align: left;
    padding: 0.65rem 0.85rem;
    border-bottom: 2px solid #cbd5e1;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    white-space: nowrap;
}
.financial-table td {
    padding: 0.65rem 0.85rem;
    border-bottom: 1px solid #f1f5f9;
    color: #1e293b;
    font-weight: 500;
    vertical-align: middle;
}
.financial-table .col-fit,
.financial-table th.col-fit,
.financial-table td.col-fit {
    white-space: nowrap;
}
.financial-table .col-text-nowrap,
.financial-table td.col-text-nowrap {
    white-space: nowrap;
}
.financial-table tbody tr:nth-child(even) {
    background-color: rgba(248, 250, 252, 0.75);
}
.financial-table tbody tr:nth-child(odd) {
    background-color: #ffffff;
}
.financial-table tbody tr:hover {
    background-color: #f1f5f9 !important;
}

.financial-table tr.section-header td {
    background: #f1f5f9;
    font-weight: 800;
    color: #0f172a;
    font-size: 0.8rem;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}
.financial-table tfoot td, .financial-table tr.total-row td {
    position: sticky;
    bottom: 0;
    z-index: 9;
    background: #f8fafc;
    border-top: 2px solid #cbd5e1;
    border-bottom: none;
    box-shadow: 0 -2px 4px rgba(0,0,0,0.03);
    font-weight: 800;
    color: #0f172a;
}

.financial-table th.amount-num,
.financial-table td.amount-num,
.amount-num {
    text-align: right !important;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.status-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.03em;
}
.status-balanced { background: #dcfce7; color: #15803d; }
.status-unbalanced { background: #fee2e2; color: #b91c1c; }

/* Filter & Export Popup Modals */
.inv-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    z-index: 1200;
    display: flex;
    align-items: center;
    justify-content: center;
}
.inv-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(6px);
}
.inv-modal-content {
    position: relative;
    z-index: 2;
    background: rgba(255, 255, 255, 0.96);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(226, 232, 240, 0.9);
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    width: 90%;
    overflow: hidden;
    animation: modalPop 0.22s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes modalPop {
    from { opacity: 0; transform: scale(0.95) translateY(10px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
.inv-modal-header {
    padding: 1.1rem 1.25rem;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: rgba(248, 250, 252, 0.8);
}
.inv-modal-close {
    background: transparent;
    border: none;
    font-size: 1.4rem;
    color: #64748b;
    cursor: pointer;
    line-height: 1;
    padding: 4px 8px;
    border-radius: 6px;
}
.inv-modal-close:hover {
    color: #0f172a;
    background: #e2e8f0;
}

.inv-field {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.inv-label {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #64748b;
}
.inv-input, .inv-select {
    width: 100%;
    height: 40px;
    padding: 0 12px;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    font-size: 0.83rem;
    font-family: inherit;
    color: #0f172a;
    background: #ffffff;
    transition: all 0.15s ease;
    box-sizing: border-box;
}
.inv-input:focus, .inv-select:focus {
    outline: none;
    border-color: #0f172a;
    box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.08);
}

.export-opt-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.18s ease;
    background: #ffffff;
}
.export-opt-card:hover {
    border-color: #0f172a;
    background: rgba(248, 250, 252, 0.9);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.print-header { display: none; }

/* ── STRICT PAPER-SAVING COMPACT PRINT ENGINE (@media print) ── */
@media print {
    #curtiss-dock,
    #curtiss-dock *,
    #curtiss-menu,
    #curtiss-menu *,
    #curtiss-notif-panel,
    #curtiss-notif-panel *,
    #curtiss-menu-overlay,
    #dock-logo-bubble,
    #dock-search-bubble,
    #dock-bar,
    .dock-item,
    .dock-circle-bubble,
    .dock-search-wrapper,
    .dock-search-input,
    #curtiss-floating-header,
    #curtiss-floating-header *,
    #curtiss-floating-header-right,
    #curtiss-floating-header-right *,
    #top-back-btn,
    #top-page-pill,
    #top-branch-wrapper,
    .top-bubble-btn,
    .top-bubble-pill,
    .top-branch-pill,
    .top-branch-dropdown,
    .app-sidebar,
    .app-header,
    .reports-menu-panel,
    .btn-no-print,
    .inv-header,
    .curtiss-select-container,
    .nav-shell,
    #app-bottom-nav,
    .inv-modal,
    .inv-modal-overlay,
    .inv-modal-content,
    nav, header, footer {
        display: none !important;
    }

    @page {
        size: A4 portrait;
        margin: 6mm 6mm 6mm 6mm;
    }
    @page landscape-page {
        size: A4 landscape;
        margin: 6mm 6mm 6mm 6mm;
    }
    body.print-landscape {
        page: landscape-page;
    }

    html, body {
        background: #ffffff !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        color: #000000 !important;
        font-family: 'SF Pro Display', -apple-system, BlinkMacSystemFont, sans-serif !important;
        font-size: 7.5pt !important;
        line-height: 1.2 !important;
    }

    .app-container, .app-main, .app-content, .reports-layout {
        display: block !important;
        grid-template-columns: 1fr !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
    }

    .report-view-container {
        border: none !important;
        box-shadow: none !important;
        background: transparent !important;
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
        padding: 0 !important;
        margin: 0 !important;
        width: 100% !important;
        height: auto !important;
        overflow: visible !important;
    }

    #reportContentArea {
        height: auto !important;
        overflow: visible !important;
        display: block !important;
    }

    .print-header {
        display: block !important;
        margin-bottom: 10px !important;
        padding-bottom: 6px !important;
        border-bottom: 2pt solid #0f172a !important;
    }

    .inv-table-panel {
        border: 0.5pt solid #cbd5e1 !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        margin-top: 4px !important;
        overflow: visible !important;
        height: auto !important;
        max-height: none !important;
    }

    table.financial-table {
        width: 100% !important;
        border-collapse: collapse !important;
        font-size: 7.5pt !important;
        table-layout: auto !important;
    }

    table.financial-table thead {
        display: table-header-group !important;
    }

    table.financial-table tfoot {
        display: table-footer-group !important;
    }

    table.financial-table th {
        background: #f1f5f9 !important;
        color: #0f172a !important;
        font-size: 7pt !important;
        font-weight: 800 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.02em !important;
        padding: 4px 5px !important;
        border-bottom: 1.5pt solid #0f172a !important;
        border-top: 0.5pt solid #cbd5e1 !important;
        white-space: nowrap !important;
    }

    table.financial-table td {
        padding: 4px 5px !important;
        border-bottom: 0.5pt solid #e2e8f0 !important;
        color: #0f172a !important;
        font-size: 7.5pt !important;
        word-break: normal !important;
        vertical-align: middle !important;
    }

    table.financial-table .col-fit,
    table.financial-table th.col-fit,
    table.financial-table td.col-fit {
        width: 1% !important;
        white-space: nowrap !important;
    }

    table.financial-table .col-text-nowrap,
    table.financial-table td.col-text-nowrap {
        white-space: nowrap !important;
    }

    table.financial-table th.amount-num,
    table.financial-table td.amount-num {
        text-align: right !important;
        white-space: nowrap !important;
        width: 1% !important;
    }

    table.financial-table tbody tr:nth-child(even) td {
        background: #f8fafc !important;
    }

    table.financial-table tr {
        page-break-inside: avoid !important;
    }

    table.financial-table tr.section-header td {
        background: #f1f5f9 !important;
        font-weight: 800 !important;
        color: #0f172a !important;
        font-size: 8.5pt !important;
        padding: 5px 6px !important;
    }

    table.financial-table tr.total-row td {
        border-top: 1.5pt solid #0f172a !important;
        border-bottom: 1.5pt solid #0f172a !important;
        font-weight: 800 !important;
        background: #f8fafc !important;
        padding: 5px 6px !important;
    }

    .kpi-grid {
        grid-template-columns: repeat(4, 1fr) !important;
        gap: 6px !important;
        margin-bottom: 10px !important;
    }

    .kpi-card {
        border: 0.5pt solid #cbd5e1 !important;
        border-radius: 4px !important;
        padding: 6px 8px !important;
        box-shadow: none !important;
        background: #ffffff !important;
        page-break-inside: avoid !important;
    }

    .kpi-title {
        font-size: 6.5pt !important;
        margin-bottom: 2px !important;
        color: #475569 !important;
    }

    .kpi-value {
        font-size: 11pt !important;
        font-weight: 800 !important;
        color: #0f172a !important;
    }

    .kpi-sub {
        font-size: 6.5pt !important;
        margin-top: 2px !important;
    }

    .status-pill {
        padding: 2px 6px !important;
        font-size: 7.5pt !important;
        border: 0.5pt solid #cbd5e1 !important;
    }

    .col-hidden-print, th.col-hidden-print, td.col-hidden-print {
        display: none !important;
    }
}
</style>

<!-- Main Page Title Header -->
<div class="inv-header btn-no-print" id="invHeaderTitleBlock" style="display: none;">
    <div style="display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <div class="inv-title" id="pageHeaderTitle">Central Reports & Financial Statements</div>
            <div class="inv-subtitle" id="pageHeaderSubtitle">Single Source of Truth Business Intelligence & General Ledger Financial Statements</div>
        </div>
        <div id="hubSearchWrapper" style="display: flex; align-items: center; gap: 8px;">
            <div style="position: relative; width: 280px;">
                <input type="text" id="reportsHubSearchInput" class="inv-input" placeholder="Search report name or keyword..." oninput="filterReportsHub(this.value)" style="padding-left: 36px; border-radius: 9999px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" style="position: absolute; left: 12px; top: 12px;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 1. REPORTS CENTER DASHBOARD HUB (Professional 2-Column Interface) -->
<!-- ========================================================================= -->
<div id="reportsCenterContainer" style="display: none; height: calc(100vh - 120px); max-height: calc(100vh - 120px); flex: 1; min-height: 0; box-sizing: border-box; overflow: hidden;">
    <div class="reports-hub-layout">
        
        <!-- Left Sidebar: Category Navigation -->
        <aside class="reports-category-sidebar btn-no-print">
            <div class="rep-sidebar-header">
                <span class="rep-sidebar-title">Categories</span>
            </div>
            <div class="rep-sidebar-nav" id="repCategoriesNav">
                <button type="button" class="rep-cat-btn active" data-cat="all" onclick="selectReportCategory('all', this)">
                    <span class="rep-cat-icon">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    </span>
                    <span class="rep-cat-name">All Reports</span>
                    <span class="rep-cat-count" id="count-all">28</span>
                </button>
                <button type="button" class="rep-cat-btn" data-cat="overview" onclick="selectReportCategory('overview', this)">
                    <span class="rep-cat-icon">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                    </span>
                    <span class="rep-cat-name">Overview</span>
                    <span class="rep-cat-count" id="count-overview">1</span>
                </button>
                <button type="button" class="rep-cat-btn" data-cat="sales" onclick="selectReportCategory('sales', this)">
                    <span class="rep-cat-icon">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </span>
                    <span class="rep-cat-name">Sales & Revenue</span>
                    <span class="rep-cat-count" id="count-sales">5</span>
                </button>
                <button type="button" class="rep-cat-btn" data-cat="inventory" onclick="selectReportCategory('inventory', this)">
                    <span class="rep-cat-icon">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                    </span>
                    <span class="rep-cat-name">Inventory & Stock</span>
                    <span class="rep-cat-count" id="count-inventory">5</span>
                </button>
                <button type="button" class="rep-cat-btn" data-cat="purchases" onclick="selectReportCategory('purchases', this)">
                    <span class="rep-cat-icon">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                    </span>
                    <span class="rep-cat-name">Purchases & GRN</span>
                    <span class="rep-cat-count" id="count-purchases">2</span>
                </button>
                <button type="button" class="rep-cat-btn" data-cat="suppliers" onclick="selectReportCategory('suppliers', this)">
                    <span class="rep-cat-icon">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </span>
                    <span class="rep-cat-name">Suppliers & Payables</span>
                    <span class="rep-cat-count" id="count-suppliers">3</span>
                </button>
                <button type="button" class="rep-cat-btn" data-cat="customers" onclick="selectReportCategory('customers', this)">
                    <span class="rep-cat-icon">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><polyline points="17 11 19 13 23 9"/></svg>
                    </span>
                    <span class="rep-cat-name">Customers & Receivables</span>
                    <span class="rep-cat-count" id="count-customers">1</span>
                </button>
                <button type="button" class="rep-cat-btn" data-cat="payments" onclick="selectReportCategory('payments', this)">
                    <span class="rep-cat-icon">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                    </span>
                    <span class="rep-cat-name">Payments & Collections</span>
                    <span class="rep-cat-count" id="count-payments">3</span>
                </button>
                <button type="button" class="rep-cat-btn" data-cat="banking" onclick="selectReportCategory('banking', this)">
                    <span class="rep-cat-icon">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="2" y1="20" x2="22" y2="20"/><path d="M4 10h16v10H4z"/><path d="M12 2L2 7h20L12 2z"/></svg>
                    </span>
                    <span class="rep-cat-name">Banking & Cash</span>
                    <span class="rep-cat-count" id="count-banking">2</span>
                </button>
                <?php if ($canViewFinancials): ?>
                <button type="button" class="rep-cat-btn" data-cat="financials" onclick="selectReportCategory('financials', this)">
                    <span class="rep-cat-icon">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                    </span>
                    <span class="rep-cat-name">Accounting & Financial</span>
                    <span class="rep-cat-count" id="count-financials">3</span>
                </button>
                <?php endif; ?>
                <button type="button" class="rep-cat-btn" data-cat="cashier" onclick="selectReportCategory('cashier', this)">
                    <span class="rep-cat-icon">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/></svg>
                    </span>
                    <span class="rep-cat-name">Cashier Performance</span>
                    <span class="rep-cat-count" id="count-cashier">1</span>
                </button>
                <button type="button" class="rep-cat-btn" data-cat="expiry" onclick="selectReportCategory('expiry', this)">
                    <span class="rep-cat-icon">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><line x1="10" y1="14" x2="14" y2="18"/><line x1="14" y1="14" x2="10" y2="18"/></svg>
                    </span>
                    <span class="rep-cat-name">Expiry & Batches</span>
                    <span class="rep-cat-count" id="count-expiry">5</span>
                </button>
            </div>
        </aside>

        <!-- Right Panel: Report Rows List -->
        <main class="reports-list-panel">
            <!-- Header bar with category name, count, and instant search -->
            <div class="rep-list-header btn-no-print">
                <div class="rep-list-header-left">
                    <h2 class="rep-active-cat-title" id="repActiveCatTitle">All Reports</h2>
                    <span class="rep-count-badge" id="repActiveCountBadge">28 Reports</span>
                </div>
                <div class="rep-list-search-box">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position: absolute; left: 11px; top: 10px;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" id="reportsListSearchInput" placeholder="Quick search reports..." oninput="filterReportsList(this.value)">
                </div>
            </div>

            <!-- Scrollable Report List Body -->
            <div class="reports-list-scroll btn-no-print" id="reportsListScroll">
                <div class="reports-items-container" id="reportsItemsContainer">

                    <!-- 1. Executive KPI Dashboard -->
                    <div class="report-row-item" tabindex="0" role="button" data-category="overview" data-search="kpi executive performance dashboard high-level metrics sales profit stock gl balance cogs" onclick="openReport('kpi_performance', event)">
                        <div class="report-row-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                        </div>
                        <div class="report-row-info">
                            <div class="report-row-title">Executive KPI Dashboard</div>
                            <div class="report-row-desc">High-level executive metrics across sales, COGS, profitability, inventory valuation, and GL balance status.</div>
                        </div>
                        <div class="report-row-meta">
                            <span class="report-cat-badge">Overview</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="report-row-arrow"><polyline points="9 18 15 12 9 6"/></svg>
                        </div>
                    </div>

                    <!-- 2. Sales Summary -->
                    <div class="report-row-item" tabindex="0" role="button" data-category="sales" data-search="sales summary revenue gross net discounts cogs margin invoice count" onclick="openReport('sales_summary', event)">
                        <div class="report-row-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                        </div>
                        <div class="report-row-info">
                            <div class="report-row-title">Sales Summary</div>
                            <div class="report-row-desc">Total invoice counts, gross revenue, discounts, net sales, COGS, gross profit & profit margin percentages.</div>
                        </div>
                        <div class="report-row-meta">
                            <span class="report-cat-badge">Sales & Revenue</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="report-row-arrow"><polyline points="9 18 15 12 9 6"/></svg>
                        </div>
                    </div>

                    <!-- 3. Sales by Product -->
                    <div class="report-row-item" tabindex="0" role="button" data-category="sales" data-search="sales by product itemized sold units revenue exact lot costs margins sku volume" onclick="openReport('sales_by_product', event)">
                        <div class="report-row-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                        </div>
                        <div class="report-row-info">
                            <div class="report-row-title">Sales by Product</div>
                            <div class="report-row-desc">Itemized report of sold units, gross revenue, exact lot costs, gross profit margins, and SKU performance.</div>
                        </div>
                        <div class="report-row-meta">
                            <span class="report-cat-badge">Sales & Revenue</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="report-row-arrow"><polyline points="9 18 15 12 9 6"/></svg>
                        </div>
                    </div>

                    <!-- 4. Sales by Category -->
                    <div class="report-row-item" tabindex="0" role="button" data-category="sales" data-search="sales by category product groups department volume distribution gross profit margins" onclick="openReport('sales_by_category', event)">
                        <div class="report-row-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                        </div>
                        <div class="report-row-info">
                            <div class="report-row-title">Sales by Category</div>
                            <div class="report-row-desc">High-level volume and margin distribution across product categories and sub-departments.</div>
                        </div>
                        <div class="report-row-meta">
                            <span class="report-cat-badge">Sales & Revenue</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="report-row-arrow"><polyline points="9 18 15 12 9 6"/></svg>
                        </div>
                    </div>

                    <!-- 5. Sales by Payment Method -->
                    <div class="report-row-item" tabindex="0" role="button" data-category="sales payments" data-search="sales by payment method cash drawer petty bank card cheque reconciliation terminal register" onclick="openReport('sales_by_payment', event)">
                        <div class="report-row-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                        </div>
                        <div class="report-row-info">
                            <div class="report-row-title">Sales by Payment Method</div>
                            <div class="report-row-desc">Payment channel breakdown reconciling Cash Drawer, Petty Cash, Bank Accounts, Cards & Cheques.</div>
                        </div>
                        <div class="report-row-meta">
                            <span class="report-cat-badge">Sales / Payments</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="report-row-arrow"><polyline points="9 18 15 12 9 6"/></svg>
                        </div>
                    </div>

                    <!-- 6. Sales History Log -->
                    <div class="report-row-item" tabindex="0" role="button" data-category="sales" data-search="sales history log invoices transactions customer audit discounts cashier receipts" onclick="openReport('sales_history', event)">
                        <div class="report-row-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        </div>
                        <div class="report-row-info">
                            <div class="report-row-title">Sales History Log</div>
                            <div class="report-row-desc">Complete audit trail of sales invoices with customer details, payment methods, discount reasons, and cashier info.</div>
                        </div>
                        <div class="report-row-meta">
                            <span class="report-cat-badge">Sales & Revenue</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="report-row-arrow"><polyline points="9 18 15 12 9 6"/></svg>
                        </div>
                    </div>

                    <!-- 7. Stock Valuation (Lot Cost) -->
                    <div class="report-row-item" tabindex="0" role="button" data-category="inventory" data-search="stock valuation inventory lot cost on hand fifo purchase cost retail potential profit" onclick="openReport('stock_valuation', event)">
                        <div class="report-row-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
                        </div>
                        <div class="report-row-info">
                            <div class="report-row-title">Stock Valuation (Lot Cost)</div>
                            <div class="report-row-desc">Current on-hand inventory valuation using exact FIFO lot purchase costs vs retail value and potential profit.</div>
                        </div>
                        <div class="report-row-meta">
                            <span class="report-cat-badge">Inventory & Stock</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="report-row-arrow"><polyline points="9 18 15 12 9 6"/></svg>
                        </div>
                    </div>

                    <!-- 8. Low Stock Alert -->
                    <div class="report-row-item" tabindex="0" role="button" data-category="inventory" data-search="low stock alert minimum threshold reorder out of stock lead time supplier" onclick="openReport('low_stock', event)">
                        <div class="report-row-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        </div>
                        <div class="report-row-info">
                            <div class="report-row-title">Low Stock Alert</div>
                            <div class="report-row-desc">Fast re-order list for items reaching or dropping below minimum threshold with supplier lead-time recommendations.</div>
                        </div>
                        <div class="report-row-meta">
                            <span class="report-cat-badge">Inventory & Stock</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="report-row-arrow"><polyline points="9 18 15 12 9 6"/></svg>
                        </div>
                    </div>

                    <!-- 9. Damaged Stock Report -->
                    <div class="report-row-item" tabindex="0" role="button" data-category="inventory" data-search="damaged stock broken leaking loss write off quarantine gl cost impact" onclick="openReport('damaged_stock', event)">
                        <div class="report-row-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        </div>
                        <div class="report-row-info">
                            <div class="report-row-title">Damaged Stock Report</div>
                            <div class="report-row-desc">Comprehensive loss audit of broken, leaking, or damaged items quarantined or written off with financial cost impact.</div>
                        </div>
                        <div class="report-row-meta">
                            <span class="report-cat-badge">Inventory & Stock</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="report-row-arrow"><polyline points="9 18 15 12 9 6"/></svg>
                        </div>
                    </div>

                    <!-- 10. In-Transit Stock Transfers -->
                    <div class="report-row-item" tabindex="0" role="button" data-category="inventory" data-search="in-transit stock transfers inter-branch dispatch destination pending delivery" onclick="openReport('in_transit_stock', event)">
                        <div class="report-row-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                        </div>
                        <div class="report-row-info">
                            <div class="report-row-title">In-Transit Stock Transfers</div>
                            <div class="report-row-desc">Real-time tracking of inter-branch inventory transfers currently en route between business locations.</div>
                        </div>
                        <div class="report-row-meta">
                            <span class="report-cat-badge">Inventory & Stock</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="report-row-arrow"><polyline points="9 18 15 12 9 6"/></svg>
                        </div>
                    </div>

                    <!-- 11. Stock Adjustments Log -->
                    <div class="report-row-item" tabindex="0" role="button" data-category="inventory" data-search="stock adjustments manual count corrections write-offs physical audit discrepancy reasons" onclick="openReport('stock_adjustments', event)">
                        <div class="report-row-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></svg>
                        </div>
                        <div class="report-row-info">
                            <div class="report-row-title">Stock Adjustments Log</div>
                            <div class="report-row-desc">Audit trail of all manual quantity adjustments, physical count corrections, write-offs, and discrepancy reasons.</div>
                        </div>
                        <div class="report-row-meta">
                            <span class="report-cat-badge">Inventory & Stock</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="report-row-arrow"><polyline points="9 18 15 12 9 6"/></svg>
                        </div>
                    </div>

                    <!-- 12. Purchase & GRN Summary -->
                    <div class="report-row-item" tabindex="0" role="button" data-category="purchases" data-search="purchase summary grn goods received supplier invoices billed amounts shipping costs procurement" onclick="openReport('purchase_summary', event)">
                        <div class="report-row-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><polyline points="9 15 11 17 15 13"/></svg>
                        </div>
                        <div class="report-row-info">
                            <div class="report-row-title">Purchase & GRN Summary</div>
                            <div class="report-row-desc">Total supplier goods received notes, billed amounts, shipping costs, and supplier invoice reconciliation.</div>
                        </div>
                        <div class="report-row-meta">
                            <span class="report-cat-badge">Purchases & GRN</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="report-row-arrow"><polyline points="9 18 15 12 9 6"/></svg>
                        </div>
                    </div>

                    <!-- 13. Supplier Purchase Returns -->
                    <div class="report-row-item" tabindex="0" role="button" data-category="purchases suppliers" data-search="supplier purchase returns rtv vendor debit notes refunds replacement returned quantities" onclick="openReport('supplier_returns', event)">
                        <div class="report-row-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
                        </div>
                        <div class="report-row-info">
                            <div class="report-row-title">Supplier Purchase Returns</div>
                            <div class="report-row-desc">Returns to vendor (RTV) audit tracking returned quantities, debit notes, replacement items, and supplier refunds.</div>
                        </div>
                        <div class="report-row-meta">
                            <span class="report-cat-badge">Purchases / Suppliers</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="report-row-arrow"><polyline points="9 18 15 12 9 6"/></svg>
                        </div>
                    </div>

                    <!-- 14. Supplier Payables Aging -->
                    <div class="report-row-item" tabindex="0" role="button" data-category="suppliers" data-search="supplier outstanding payables aging vendor invoices unpaid credit terms 30 60 90 days" onclick="openReport('supplier_outstanding', event)">
                        <div class="report-row-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        </div>
                        <div class="report-row-info">
                            <div class="report-row-title">Supplier Payables Aging</div>
                            <div class="report-row-desc">Accounts payable aging schedule (Current, 30, 60, 90+ days), credit limits, and unpaid vendor invoices.</div>
                        </div>
                        <div class="report-row-meta">
                            <span class="report-cat-badge">Suppliers & Payables</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="report-row-arrow"><polyline points="9 18 15 12 9 6"/></svg>
                        </div>
                    </div>

                    <!-- 15. Supplier Expiry Performance -->
                    <div class="report-row-item" tabindex="0" role="button" data-category="suppliers expiry" data-search="supplier expiry performance vendor quality return rate expired goods liability batch reliability" onclick="openReport('supplier_expiry_performance', event)">
                        <div class="report-row-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/></svg>
                        </div>
                        <div class="report-row-info">
                            <div class="report-row-title">Supplier Expiry Performance</div>
                            <div class="report-row-desc">Vendor quality analysis tracking expired items by supplier, received volume vs expired quantities, and financial loss.</div>
                        </div>
                        <div class="report-row-meta">
                            <span class="report-cat-badge">Suppliers / Expiry</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="report-row-arrow"><polyline points="9 18 15 12 9 6"/></svg>
                        </div>
                    </div>

                    <!-- 16. Customer AR Receivables -->
                    <div class="report-row-item" tabindex="0" role="button" data-category="customers" data-search="customer outstanding ar receivables aging credit limits overdue balances collections" onclick="openReport('customer_outstanding', event)">
                        <div class="report-row-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><polyline points="17 11 19 13 23 9"/></svg>
                        </div>
                        <div class="report-row-info">
                            <div class="report-row-title">Customer AR Receivables</div>
                            <div class="report-row-desc">Accounts receivable aging ledger, credit limits, overdue accounts, and outstanding customer balances.</div>
                        </div>
                        <div class="report-row-meta">
                            <span class="report-cat-badge">Customers & Receivables</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="report-row-arrow"><polyline points="9 18 15 12 9 6"/></svg>
                        </div>
                    </div>

                    <!-- 17. Customer Cheques Register -->
                    <div class="report-row-item" tabindex="0" role="button" data-category="payments" data-search="customer cheques register in-hand banked cleared returned dishonoured deposits" onclick="openReport('customer_cheques', event)">
                        <div class="report-row-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                        </div>
                        <div class="report-row-info">
                            <div class="report-row-title">Customer Cheques Register</div>
                            <div class="report-row-desc">Cheque clearing status tracking in-hand, banked, cleared, and returned/dishonoured cheques from customers.</div>
                        </div>
                        <div class="report-row-meta">
                            <span class="report-cat-badge">Payments & Collections</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="report-row-arrow"><polyline points="9 18 15 12 9 6"/></svg>
                        </div>
                    </div>

                    <!-- 18. Supplier Cheques Register -->
                    <div class="report-row-item" tabindex="0" role="button" data-category="payments" data-search="supplier cheques register issued post-dated cleared voided vendor payment obligations" onclick="openReport('supplier_cheques', event)">
                        <div class="report-row-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                        </div>
                        <div class="report-row-info">
                            <div class="report-row-title">Supplier Cheques Register</div>
                            <div class="report-row-desc">Outgoing vendor payment cheques: issued, post-dated, cleared, and voided records for cash forecasting.</div>
                        </div>
                        <div class="report-row-meta">
                            <span class="report-cat-badge">Payments & Collections</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="report-row-arrow"><polyline points="9 18 15 12 9 6"/></svg>
                        </div>
                    </div>

                    <!-- 19. Petty Cash Register -->
                    <div class="report-row-item" tabindex="0" role="button" data-category="banking" data-search="petty cash register disbursements daily expenses custodian balance receipts vouchers" onclick="openReport('petty_cash_register', event)">
                        <div class="report-row-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        </div>
                        <div class="report-row-info">
                            <div class="report-row-title">Petty Cash Register</div>
                            <div class="report-row-desc">Imprest petty cash ledger tracking receipts, daily small expense disbursements, and custodian balances.</div>
                        </div>
                        <div class="report-row-meta">
                            <span class="report-cat-badge">Banking & Cash</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="report-row-arrow"><polyline points="9 18 15 12 9 6"/></svg>
                        </div>
                    </div>

                    <!-- 20. Bank Register Ledger -->
                    <div class="report-row-item" tabindex="0" role="button" data-category="banking" data-search="bank register ledger account transactions deposits transfers bank charges reconciliation" onclick="openReport('bank_register', event)">
                        <div class="report-row-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="2" y1="20" x2="22" y2="20"/><path d="M4 10h16v10H4z"/><path d="M12 2L2 7h20L12 2z"/></svg>
                        </div>
                        <div class="report-row-info">
                            <div class="report-row-title">Bank Register Ledger</div>
                            <div class="report-row-desc">Detailed bank account transactional register: deposits, bank transfers, charges, and running balances.</div>
                        </div>
                        <div class="report-row-meta">
                            <span class="report-cat-badge">Banking & Cash</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="report-row-arrow"><polyline points="9 18 15 12 9 6"/></svg>
                        </div>
                    </div>

                    <?php if ($canViewFinancials): ?>
                    <!-- 21. Profit & Loss Statement (P&L) -->
                    <div class="report-row-item" tabindex="0" role="button" data-category="financials" data-search="profit and loss statement p&l income revenue cogs gross margin operating expenses net profit" onclick="openReport('profit_and_loss', event)">
                        <div class="report-row-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                        </div>
                        <div class="report-row-info">
                            <div class="report-row-title">Profit & Loss Statement (P&L)</div>
                            <div class="report-row-desc">Accrual-based income statement: Revenue, Cost of Goods Sold, Gross Margin, Operating Expenses, and Net Profit.</div>
                        </div>
                        <div class="report-row-meta">
                            <span class="report-cat-badge">Accounting & Financial</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="report-row-arrow"><polyline points="9 18 15 12 9 6"/></svg>
                        </div>
                    </div>

                    <!-- 22. Balance Sheet Statement -->
                    <div class="report-row-item" tabindex="0" role="button" data-category="financials" data-search="balance sheet financial position assets liabilities equity accounting equation period end" onclick="openReport('balance_sheet', event)">
                        <div class="report-row-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 16l3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1z"/><path d="M2 16l3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1z"/><path d="M7 21h10"/><path d="M12 3v18"/><path d="M3 7h18"/></svg>
                        </div>
                        <div class="report-row-info">
                            <div class="report-row-title">Balance Sheet Statement</div>
                            <div class="report-row-desc">Financial position statement: Total Assets = Total Liabilities + Equity with period-end balances.</div>
                        </div>
                        <div class="report-row-meta">
                            <span class="report-cat-badge">Accounting & Financial</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="report-row-arrow"><polyline points="9 18 15 12 9 6"/></svg>
                        </div>
                    </div>

                    <!-- 23. Trial Balance (General Ledger) -->
                    <div class="report-row-item" tabindex="0" role="button" data-category="financials" data-search="trial balance general ledger debits credits chart of accounts double entry audit verification" onclick="openReport('trial_balance', event)">
                        <div class="report-row-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path d="m9 12 2 2 4-4"/></svg>
                        </div>
                        <div class="report-row-info">
                            <div class="report-row-title">Trial Balance (General Ledger)</div>
                            <div class="report-row-desc">Standard accounting trial balance verifying total Debits equal total Credits across all active GL accounts.</div>
                        </div>
                        <div class="report-row-meta">
                            <span class="report-cat-badge">Accounting & Financial</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="report-row-arrow"><polyline points="9 18 15 12 9 6"/></svg>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- 24. Business Day & Cashier Log -->
                    <div class="report-row-item" tabindex="0" role="button" data-category="cashier" data-search="cashier performance business day register open close float over short discrepancy sales shifts" onclick="openReport('cashier_performance', event)">
                        <div class="report-row-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </div>
                        <div class="report-row-info">
                            <div class="report-row-title">Business Day & Cashier Log</div>
                            <div class="report-row-desc">Shift-level accountability: register open/close floats, cash over/short discrepancies, and sales per cashier.</div>
                        </div>
                        <div class="report-row-meta">
                            <span class="report-cat-badge">Cashier Performance</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="report-row-arrow"><polyline points="9 18 15 12 9 6"/></svg>
                        </div>
                    </div>

                    <!-- 25. Expired Stock Report -->
                    <div class="report-row-item" tabindex="0" role="button" data-category="expiry" data-search="expired stock quarantined non-sellable lot batch expiration date financial loss" onclick="openReport('expired_stock', event)">
                        <div class="report-row-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><line x1="10" y1="14" x2="14" y2="18"/><line x1="14" y1="14" x2="10" y2="18"/></svg>
                        </div>
                        <div class="report-row-info">
                            <div class="report-row-title">Expired Stock Report</div>
                            <div class="report-row-desc">Quarantined non-sellable stock items past their expiration date with batch details and financial write-off impact.</div>
                        </div>
                        <div class="report-row-meta">
                            <span class="report-cat-badge">Expiry & Batches</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="report-row-arrow"><polyline points="9 18 15 12 9 6"/></svg>
                        </div>
                    </div>

                    <!-- 26. Near-Expiry Stock Report -->
                    <div class="report-row-item" tabindex="0" role="button" data-category="expiry" data-search="near-expiry stock proactive warning batches 30 60 90 days discount clearance" onclick="openReport('near_expiry', event)">
                        <div class="report-row-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        </div>
                        <div class="report-row-info">
                            <div class="report-row-title">Near-Expiry Stock Report</div>
                            <div class="report-row-desc">Proactive warning report for batches approaching expiry within 30, 60, or 90 days for prioritized markdown clearance.</div>
                        </div>
                        <div class="report-row-meta">
                            <span class="report-cat-badge">Expiry & Batches</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="report-row-arrow"><polyline points="9 18 15 12 9 6"/></svg>
                        </div>
                    </div>

                    <!-- 27. Batch & Expiry Master Audit -->
                    <div class="report-row-item" tabindex="0" role="button" data-category="expiry" data-search="batch expiry master audit inventory lot registry manufacturing expiry active lot numbers" onclick="openReport('batch_expiry_audit', event)">
                        <div class="report-row-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M8 13h8"/><path d="M8 17h8"/><path d="M8 9h2"/></svg>
                        </div>
                        <div class="report-row-info">
                            <div class="report-row-title">Batch & Expiry Master Audit</div>
                            <div class="report-row-desc">Complete inventory lot registry showing active lot numbers, manufacturing & expiry dates, initial vs remaining quantities.</div>
                        </div>
                        <div class="report-row-meta">
                            <span class="report-cat-badge">Expiry & Batches</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="report-row-arrow"><polyline points="9 18 15 12 9 6"/></svg>
                        </div>
                    </div>

                    <!-- 28. Expired Stock Write-Off History -->
                    <div class="report-row-item" tabindex="0" role="button" data-category="expiry" data-search="expired stock write-off history audit damaged stock gl loss dr 1250 cr 1200 authorizing users" onclick="openReport('expired_writeoff_history', event)">
                        <div class="report-row-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                        </div>
                        <div class="report-row-info">
                            <div class="report-row-title">Expired Stock Write-Off History</div>
                            <div class="report-row-desc">Audited historical log of expired batches written off to damaged stock and General Ledger Loss (Dr 1250 / Cr 1200).</div>
                        </div>
                        <div class="report-row-meta">
                            <span class="report-cat-badge">Expiry & Batches</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="report-row-arrow"><polyline points="9 18 15 12 9 6"/></svg>
                        </div>
                    </div>

                </div>

                <!-- Empty State Message when search has 0 matches -->
                <div class="rep-empty-state" id="repEmptyState" style="display: none;">
                    <div style="font-size: 28px; margin-bottom: 8px;">🔍</div>
                    <div style="font-size: 14px; font-weight: 700; color: #1e293b; margin-bottom: 4px;">No matching reports found</div>
                    <div style="font-size: 12px; color: #64748b;">Try searching for different keywords or select "All Reports" from the categories.</div>
                </div>

            </div>
        </main>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 2. DEDICATED REPORT VIEW CONTAINER -->
<!-- ========================================================================= -->
<div class="reports-layout" id="dedicatedReportContainer">
    <!-- Main Report Viewer Workspace -->
    <div>
        <!-- Printable Paper Letterhead Header -->
        <div class="print-header">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <h1 id="print_main_headline" style="font-size: 14pt; font-weight: 800; color: #0f172a; margin: 0; text-transform: uppercase;">CURTISS POS - EXECUTIVE KPI PERFORMANCE DASHBOARD</h1>
                    <div id="print_report_subtitle" style="font-size: 9pt; font-weight: 700; color: #2563eb; margin-top: 2px;">High-level business metrics across sales, profitability, stock, and GL</div>
                </div>
                <div style="text-align: right; font-size: 8pt; color: #475569; font-weight: 600;">
                    <div><strong>Branch:</strong> <span id="print_branch_name">Consolidated</span></div>
                    <div><strong>Period:</strong> <span id="print_period_name">Current Month</span></div>
                    <div><strong>Generated:</strong> <span id="print_generated_time"></span></div>
                </div>
            </div>
        </div>

        <div class="report-view-container">
            <div class="report-header-action btn-no-print">
                <div>
                    <div class="report-title-text" id="reportTitleText">Executive KPI Dashboard</div>
                    <div id="reportSubtitleText" style="display: none;"></div>
                </div>

                <div style="display: flex; align-items: center; gap: 8px;">
                    <span id="activeFilterBadge" style="display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 9999px; background: rgba(15,23,42,0.05); color: #475569; font-size: 0.75rem; font-weight: 700;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <span id="badgePeriodText">This Month</span>
                    </span>
                </div>
            </div>

            <div id="reportContentArea">
                <!-- Dynamic Content Rendered Here -->
            </div>
        </div>
    </div>
</div>

<!-- Top-Right Glass Filter Modal -->
<div id="filterModal" class="inv-modal" style="display: none;">
    <div class="inv-modal-overlay" onclick="closeFilterModal()"></div>
    <div class="inv-modal-content" style="max-width: 520px; border-radius: 20px;">
        <div class="inv-modal-header">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 38px; height: 38px; border-radius: 12px; background: rgba(15,23,42,0.06); display: flex; align-items: center; justify-content: center; color: #0f172a;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 1.1rem; font-weight: 800; color: #0f172a;">Filter Business Reports</h3>
                    <p style="margin: 2px 0 0 0; font-size: 0.78rem; color: #64748b;">Set branch and date scope for reports</p>
                </div>
            </div>
            <button type="button" class="inv-modal-close" onclick="closeFilterModal()">&times;</button>
        </div>
        <div class="inv-modal-body" style="padding: 1.25rem;">
            <form id="filterForm" onsubmit="event.preventDefault(); applyFiltersFromModal();">
                <div style="display: grid; grid-template-columns: 1fr; gap: 1rem;">
                    <div class="inv-field">
                        <label class="inv-label">Branch Scope</label>
                        <select id="filter_branch" class="inv-select curtiss-select-enhance">
                            <?php 
                            $curBranchScope = $selectedBranch ?? 'all';
                            if ($canViewAllBranches): ?>
                                <option value="all" <?= ($curBranchScope === 'all') ? 'selected' : '' ?>>All Branches (Consolidated)</option>
                            <?php endif; ?>
                            <?php foreach ($branches as $b): ?>
                                <option value="<?= $b['id'] ?>" <?= ((string)$b['id'] === (string)$curBranchScope) ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?> (<?= htmlspecialchars($b['code']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;" id="filter_date_group">
                        <div class="inv-field">
                            <label class="inv-label">Date From</label>
                            <input type="date" id="filter_date_from" class="inv-input" value="<?= $defaultDateFrom ?>">
                        </div>
                        <div class="inv-field">
                            <label class="inv-label">Date To</label>
                            <input type="date" id="filter_date_to" class="inv-input" value="<?= $defaultDateTo ?>">
                        </div>
                    </div>

                    <div class="inv-field" id="filter_quick_range_group">
                        <label class="inv-label">Quick Range Shortcut</label>
                        <select id="quick_range_select" class="inv-select" onchange="setQuickDateRange(this.value)">
                            <option value="custom">Custom Range</option>
                            <option value="today">Today</option>
                            <option value="this_week">This Week</option>
                            <option value="this_month" selected>This Month</option>
                            <option value="this_year">This Year</option>
                        </select>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1.5rem;">
                    <button type="button" class="inv-btn inv-btn-ghost" onclick="resetFilters()">Reset Defaults</button>
                    <button type="submit" class="inv-btn inv-btn-primary">Apply Filters</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Multi-Format Export Options Modal -->
<div id="exportChoiceModal" class="inv-modal" style="display: none;">
    <div class="inv-modal-overlay" onclick="closeExportModal()"></div>
    <div class="inv-modal-content" style="max-width: 500px; border-radius: 20px;">
        <div class="inv-modal-header">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 38px; height: 38px; border-radius: 12px; background: rgba(15,23,42,0.06); display: flex; align-items: center; justify-content: center; color: #0f172a;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 1.1rem; font-weight: 800; color: #0f172a;">Export Report Data</h3>
                    <p style="margin: 2px 0 0 0; font-size: 0.78rem; color: #64748b;">Select file export format for current report view</p>
                </div>
            </div>
            <button type="button" class="inv-modal-close" onclick="closeExportModal()">&times;</button>
        </div>
        <div class="inv-modal-body" style="padding: 1.25rem;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.85rem;">
                <div class="export-opt-card" onclick="executeExportFormat('pdf')">
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 11px;">PDF</div>
                    <div>
                        <div style="font-size: 0.85rem; font-weight: 800; color: #0f172a;">PDF Document</div>
                        <div style="font-size: 0.72rem; color: #64748b;">Vector print layout</div>
                    </div>
                </div>

                <div class="export-opt-card" onclick="executeExportFormat('excel')">
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: #dcfce7; color: #16a34a; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 11px;">XLSX</div>
                    <div>
                        <div style="font-size: 0.85rem; font-weight: 800; color: #0f172a;">Excel Sheet</div>
                        <div style="font-size: 0.72rem; color: #64748b;">Structured CSV/XLSX</div>
                    </div>
                </div>

                <div class="export-opt-card" onclick="executeExportFormat('csv')">
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 11px;">CSV</div>
                    <div>
                        <div style="font-size: 0.85rem; font-weight: 800; color: #0f172a;">CSV Text</div>
                        <div style="font-size: 0.72rem; color: #64748b;">Raw tabular data</div>
                    </div>
                </div>

                <div class="export-opt-card" onclick="executeExportFormat('json')">
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: #f3e8ff; color: #9333ea; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 11px;">JSON</div>
                    <div>
                        <div style="font-size: 0.85rem; font-weight: 800; color: #0f172a;">JSON Payload</div>
                        <div style="font-size: 0.72rem; color: #64748b;">Raw API dataset</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Column Customizer Dropdown / Modal Popup -->
<div id="columnCustomizerModal" class="inv-modal" style="display: none;">
    <div class="inv-modal-overlay" onclick="closeColumnCustomizerModal()"></div>
    <div class="inv-modal-content" style="max-width: 440px; border-radius: 20px;">
        <div class="inv-modal-header">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 38px; height: 38px; border-radius: 12px; background: rgba(147, 51, 234, 0.1); display: flex; align-items: center; justify-content: center; color: #9333ea;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 3h7a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-7m0-18H5a2 2 0 0 0-2 2v14a2 2 0 0 1 2 2h7m0-18v18"/></svg>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 1.1rem; font-weight: 800; color: #0f172a;">Customize Report Columns</h3>
                    <p style="margin: 2px 0 0 0; font-size: 0.78rem; color: #64748b;">Select which columns to display, print, and export</p>
                </div>
            </div>
            <button type="button" class="inv-modal-close" onclick="closeColumnCustomizerModal()">&times;</button>
        </div>
        <div class="inv-modal-body" style="padding: 1.25rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem; padding-bottom: 0.5rem; border-bottom: 1px solid #e2e8f0;">
                <span style="font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Available Columns</span>
                <div style="display: flex; gap: 8px;">
                    <button type="button" class="inv-btn inv-btn-ghost" style="padding: 3px 10px; font-size: 11px;" onclick="setAllColumnsVisible(true)">Select All</button>
                    <button type="button" class="inv-btn inv-btn-ghost" style="padding: 3px 10px; font-size: 11px;" onclick="setAllColumnsVisible(false)">Deselect All</button>
                </div>
            </div>
            
            <div id="colCustomizerCheckboxList" style="display: flex; flex-direction: column; gap: 8px; max-height: 300px; overflow-y: auto; padding-right: 4px;">
                <!-- Dynamically populated per active report -->
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1.25rem; padding-top: 0.75rem; border-top: 1px solid #e2e8f0;">
                <button type="button" class="inv-btn inv-btn-ghost" onclick="resetDefaultColumns()">Reset Default</button>
                <button type="button" class="inv-btn inv-btn-primary" onclick="closeColumnCustomizerModal()">Done</button>
            </div>
        </div>
    </div>
</div>

<!-- Progress Loader Modal -->
<div id="expModal" class="inv-modal" style="display: none;">
    <div class="inv-modal-overlay"></div>
    <div class="inv-modal-content" style="max-width: 380px; padding: 1.5rem; text-align: center; border-radius: 20px;">
        <div style="display: flex; justify-content: center; margin-bottom: 1rem;">
            <div style="width: 44px; height: 44px; border-radius: 14px; background: rgba(15,23,42,0.06); display: flex; align-items: center; justify-content: center; color: #0f172a;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            </div>
        </div>
        <h4 style="margin: 0; font-size: 1rem; font-weight: 800; color: #0f172a;" id="expModalTitle">Preparing Export File</h4>
        <p style="margin: 4px 0 1rem 0; font-size: 0.78rem; color: #64748b;" id="expModalMsg">Processing report data payload...</p>
        
        <div style="width: 100%; height: 6px; background: #e2e8f0; border-radius: 999px; overflow: hidden;">
            <div id="expProgressBar" style="width: 15%; height: 100%; background: #0f172a; transition: width 0.3s ease;"></div>
        </div>
    </div>
</div>

<script>
    let currentReportType = '<?= $activeReportType ?? 'center' ?>';
    let currentReportRawData = null;

    function getReportsBaseUrl() {
        let path = window.location.pathname;
        if (path.includes('/reports.php')) {
            return path.replace(/\/reports\.php.*$/, '/reports');
        }
        return path.replace(/\/reports(\/[^\/]+)?\/?$/, '/reports');
    }

    function getReportUrl(reportType) {
        const base = getReportsBaseUrl();
        if (reportType === 'center' || !reportType) {
            return base;
        }
        return base + '/' + reportType;
    }

    function openReport(reportType, event) {
        if (event && (event.ctrlKey || event.metaKey || event.button === 1)) {
            return;
        }
        if (event) event.preventDefault();
        const targetUrl = getReportUrl(reportType);
        window.open(targetUrl, '_blank');
    }

    function handleReportBack() {
        if (currentReportType !== 'center') {
            showReportsCenterHub(true);
        } else {
            if (window.history.length > 1 && document.referrer) {
                window.history.back();
            } else {
                window.location.href = 'dashboard';
            }
        }
    }

    function filterReportTableRealtime(query) {
        const q = String(query || '').toLowerCase().trim();
        const rows = document.querySelectorAll('#reportContentArea table.financial-table tbody tr');
        rows.forEach(tr => {
            if (tr.classList.contains('section-header') || tr.classList.contains('total-row')) return;
            const text = tr.textContent.toLowerCase();
            if (!q || text.includes(q)) {
                tr.style.display = '';
            } else {
                tr.style.display = 'none';
            }
        });
    }

    function registerBottomSearch() {
        if (window.CurtissNav && window.CurtissNav.enablePageSearch) {
            window.CurtissNav.enablePageSearch({
                placeholder: 'Search reports...',
                onSearch: function(query) {
                    if (currentReportType === 'center') {
                        filterReportsHub(query);
                    } else {
                        filterReportTableRealtime(query);
                    }
                }
            });
        } else {
            setTimeout(registerBottomSearch, 40);
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (typeof CurtissSearchableSelect !== 'undefined') {
            CurtissSearchableSelect.attach(document.getElementById('filter_branch'));
        }

        const topBackBtn = document.getElementById('top-back-btn');
        if (topBackBtn) {
            topBackBtn.onclick = function(e) {
                e.preventDefault();
                e.stopPropagation();
                handleReportBack();
            };
        }

        if (currentReportType === 'center' || !currentReportType) {
            showReportsCenterHub(false);
        } else {
            switchReport(currentReportType, null, false);
        }

        registerBottomSearch();
    });

    window.addEventListener('popstate', (e) => {
        let reportType = 'center';
        if (e.state && e.state.type) {
            reportType = e.state.type;
        } else {
            const match = window.location.pathname.match(/\/reports\/([a-zA-Z0-9_-]+)\/?$/);
            if (match && match[1]) {
                reportType = match[1];
            }
        }
        if (reportType === 'center') {
            showReportsCenterHub(false);
        } else {
            switchReport(reportType, null, false);
        }
    });

    function openFilterModal() {
        document.getElementById('filterModal').style.display = 'flex';
    }

    function closeFilterModal() {
        document.getElementById('filterModal').style.display = 'none';
    }

    function openExportModal() {
        document.getElementById('exportChoiceModal').style.display = 'flex';
    }

    function closeExportModal() {
        document.getElementById('exportChoiceModal').style.display = 'none';
    }

    function setElementDisplay(id, val) {
        const el = document.getElementById(id);
        if (el) el.style.display = val;
    }

    function setElementText(id, val) {
        const el = document.getElementById(id);
        if (el) el.innerText = val;
    }

    let currentSelectedCategory = 'all';

    function initCategoryCounters() {
        const categories = ['all', 'overview', 'sales', 'inventory', 'purchases', 'suppliers', 'customers', 'payments', 'banking', 'financials', 'cashier', 'expiry'];
        const rows = document.querySelectorAll('.report-row-item');
        
        categories.forEach(cat => {
            let count = 0;
            rows.forEach(row => {
                const rowCats = (row.getAttribute('data-category') || '').split(' ');
                if (cat === 'all' || rowCats.includes(cat)) {
                    count++;
                }
            });
            const countEl = document.getElementById(`count-${cat}`);
            if (countEl) countEl.innerText = count;
        });
    }

    function selectReportCategory(catKey, btn) {
        currentSelectedCategory = catKey || 'all';
        document.querySelectorAll('.rep-cat-btn').forEach(b => b.classList.remove('active'));
        if (btn) {
            btn.classList.add('active');
        } else {
            const targetBtn = document.querySelector(`.rep-cat-btn[data-cat="${currentSelectedCategory}"]`);
            if (targetBtn) targetBtn.classList.add('active');
        }

        const catTitles = {
            'all': 'All Reports',
            'overview': 'Overview',
            'sales': 'Sales & Revenue',
            'inventory': 'Inventory & Stock',
            'purchases': 'Purchases & GRN',
            'suppliers': 'Suppliers & Payables',
            'customers': 'Customers & Receivables',
            'payments': 'Payments & Collections',
            'banking': 'Banking & Cash',
            'financials': 'Accounting & Financial',
            'cashier': 'Cashier Performance',
            'expiry': 'Expiry & Batches'
        };
        const titleEl = document.getElementById('repActiveCatTitle');
        if (titleEl) titleEl.innerText = catTitles[currentSelectedCategory] || 'Reports';

        applyReportsFilter();
    }

    function filterReportsList(query) {
        const hubInput = document.getElementById('reportsHubSearchInput');
        if (hubInput && hubInput.value !== query) {
            hubInput.value = query;
        }
        applyReportsFilter();
    }

    function filterReportsHub(query) {
        const listInput = document.getElementById('reportsListSearchInput');
        if (listInput && listInput.value !== query) {
            listInput.value = query;
        }
        applyReportsFilter();
    }

    function filterCategoryHub(catKey, btn) {
        selectReportCategory(catKey, btn);
    }

    function applyReportsFilter() {
        const searchInput = document.getElementById('reportsListSearchInput') || document.getElementById('reportsHubSearchInput');
        const q = searchInput ? String(searchInput.value || '').toLowerCase().trim() : '';
        const rows = document.querySelectorAll('.report-row-item');
        let visibleCount = 0;

        rows.forEach(row => {
            const cat = row.getAttribute('data-category') || '';
            const searchData = (row.getAttribute('data-search') + ' ' + row.innerText).toLowerCase();
            
            const matchesCategory = (currentSelectedCategory === 'all' || cat.split(' ').includes(currentSelectedCategory));
            const matchesSearch = (!q || searchData.includes(q));

            if (matchesCategory && matchesSearch) {
                row.style.display = 'flex';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        const badge = document.getElementById('repActiveCountBadge');
        if (badge) badge.innerText = `${visibleCount} Report${visibleCount === 1 ? '' : 's'}`;

        const emptyState = document.getElementById('repEmptyState');
        if (emptyState) {
            emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
        }
    }

    function showReportsCenterHub(updateState = true) {
        currentReportType = 'center';
        setElementDisplay('reportsCenterContainer', 'block');
        setElementDisplay('dedicatedReportContainer', 'none');
        setElementDisplay('hubSearchWrapper', 'none');
        setElementDisplay('btnBackToHub', 'none');
        setElementDisplay('btnFilterTrigger', 'none');
        setElementDisplay('btnColumnTrigger', 'none');
        setElementDisplay('btnExportTrigger', 'none');
        setElementDisplay('btnPrintTrigger', 'none');
        setElementText('pageHeaderTitle', 'Central Reports & Financial Statements');
        setElementText('pageHeaderSubtitle', 'Single Source of Truth Business Intelligence & General Ledger Financial Statements');

        initCategoryCounters();
        applyReportsFilter();

        if (updateState && window.history && window.history.pushState) {
            const newUrl = getReportUrl('center');
            if (window.location.pathname !== newUrl) {
                window.history.pushState({ type: 'center' }, '', newUrl);
            }
        }
    }

    function switchReport(reportType, element, updateState = true) {
        if (reportType === 'center') {
            showReportsCenterHub(updateState);
            return;
        }

        currentReportType = reportType;
        setElementDisplay('reportsCenterContainer', 'none');
        setElementDisplay('dedicatedReportContainer', 'block');
        setElementDisplay('hubSearchWrapper', 'none');
        setElementDisplay('btnBackToHub', 'inline-flex');
        setElementDisplay('btnFilterTrigger', 'inline-flex');
        setElementDisplay('btnColumnTrigger', 'inline-flex');
        setElementDisplay('btnExportTrigger', 'inline-flex');
        setElementDisplay('btnPrintTrigger', 'inline-flex');
        setElementText('pageHeaderTitle', 'Central Reports & Financial Statements');
        setElementText('pageHeaderSubtitle', 'Single Source of Truth Business Intelligence & General Ledger Financial Statements');

        document.querySelectorAll('.reports-menu-item').forEach(el => el.classList.remove('active'));
        if (element) {
            element.classList.add('active');
        } else {
            const target = document.querySelector(`.reports-menu-item[data-report="${reportType}"]`);
            if (target) target.classList.add('active');
        }

        if (updateState && window.history && window.history.pushState) {
            const newUrl = getReportUrl(reportType);
            if (window.location.pathname !== newUrl) {
                window.history.pushState({ type: reportType }, '', newUrl);
            }
        }

        loadActiveReport();
    }

    // Keyboard arrow and enter navigation inside report list
    document.addEventListener('keydown', (e) => {
        if (currentReportType !== 'center') return;
        const activeEl = document.activeElement;
        if (activeEl && activeEl.classList.contains('report-row-item')) {
            const visibleRows = Array.from(document.querySelectorAll('.report-row-item')).filter(r => r.style.display !== 'none');
            const currentIndex = visibleRows.indexOf(activeEl);
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                const next = visibleRows[currentIndex + 1] || visibleRows[0];
                if (next) next.focus();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                const prev = visibleRows[currentIndex - 1] || visibleRows[visibleRows.length - 1];
                if (prev) prev.focus();
            } else if (e.key === 'Enter') {
                e.preventDefault();
                activeEl.click();
            }
        }
    });

    function updatePrintMetadata() {
        const branchSelect = document.getElementById('filter_branch');
        const branchText = (branchSelect && branchSelect.options[branchSelect.selectedIndex]) ? branchSelect.options[branchSelect.selectedIndex].text : 'Consolidated';
        const titleEl = document.getElementById('reportTitleText');
        const subtitleEl = document.getElementById('reportSubtitleText');
        const dateFromEl = document.getElementById('filter_date_from');
        const dateToEl = document.getElementById('filter_date_to');

        const titleText = titleEl ? titleEl.innerText : 'Report';
        const subtitleText = subtitleEl ? subtitleEl.innerText : '';
        const dateFrom = dateFromEl ? dateFromEl.value : '';
        const dateTo = dateToEl ? dateToEl.value : '';

        setElementText('print_main_headline', `CURTISS POS - ${titleText.toUpperCase()}`);
        setElementText('print_report_subtitle', subtitleText);
        setElementText('print_branch_name', branchText);
        setElementText('print_period_name', `${dateFrom} to ${dateTo}`);
        setElementText('print_generated_time', new Date().toLocaleString());
    }

    function triggerPaperPrint() {
        updatePrintMetadata();
        
        // Automatic Orientation Engine:
        // Automatically selects A4 Landscape when report has > 5 columns or is a wide dataset,
        // otherwise selects A4 Portrait.
        const thCount = document.querySelectorAll('#reportContentArea table.financial-table thead th').length;
        const wideReportTypes = [
            'sales_by_product', 'sales_history', 'stock_valuation', 
            'supplier_outstanding', 'trial_balance', 'sales_by_category', 
            'cashier_performance', 'damaged_stock', 'in_transit_stock', 
            'customer_cheques', 'returns_adjustments', 'supplier_returns', 'stock_adjustments', 'sales_by_branch', 
            'sales_by_brand', 'sales_by_customer', 'grn_register',
            'expired_stock', 'near_expiry', 'batch_expiry_audit', 'expired_writeoff_history', 'supplier_expiry_performance'
        ];
        
        const isWide = thCount > 5 || wideReportTypes.includes(currentReportType);
        
        if (isWide) {
            document.body.classList.add('print-landscape');
        } else {
            document.body.classList.remove('print-landscape');
        }
        
        window.print();
    }

    /* Direct Vector PDF File Download using Native Crisp Print-to-PDF Engine */
    function downloadDirectPDF() {
        closeExportModal();
        triggerPaperPrint();
    }

    function applyFiltersFromModal() {
        closeFilterModal();
        updateFilterActiveBadge();
        loadActiveReport();
    }

    function updateFilterActiveBadge() {
        const branchVal = document.getElementById('filter_branch').value;
        const dateFrom = document.getElementById('filter_date_from').value;
        const dateTo = document.getElementById('filter_date_to').value;
        const defaultFrom = '<?= $defaultDateFrom ?>';
        const defaultTo = '<?= $defaultDateTo ?>';

        const isFiltered = (branchVal !== 'all' && branchVal !== '') || dateFrom !== defaultFrom || dateTo !== defaultTo;
        document.getElementById('filterActiveDot').style.display = isFiltered ? 'block' : 'none';

        const periodLabel = document.getElementById('quick_range_select').options[document.getElementById('quick_range_select').selectedIndex].text;
        document.getElementById('badgePeriodText').innerText = periodLabel;
    }

    function setQuickDateRange(type) {
        const today = new Date();
        const dateFrom = document.getElementById('filter_date_from');
        const dateTo = document.getElementById('filter_date_to');

        const formatDate = (d) => d.toISOString().split('T')[0];

        if (type === 'today') {
            dateFrom.value = formatDate(today);
            dateTo.value = formatDate(today);
        } else if (type === 'this_week') {
            const day = today.getDay();
            const diffToMon = today.getDate() - day + (day === 0 ? -6 : 1);
            const mon = new Date(today.setDate(diffToMon));
            dateFrom.value = formatDate(mon);
            dateTo.value = formatDate(new Date());
        } else if (type === 'this_month') {
            const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            dateFrom.value = formatDate(firstDay);
            dateTo.value = formatDate(new Date());
        } else if (type === 'this_year') {
            const firstDay = new Date(today.getFullYear(), 0, 1);
            dateFrom.value = formatDate(firstDay);
            dateTo.value = formatDate(new Date());
        }
    }

    let currentBankFilter = 'all';

    function handleBankFilterChange(val) {
        currentBankFilter = val;
        loadActiveReport();
    }

    function resetFilters() {
        const defaultBranchScope = '<?= $selectedBranch ?? 'all' ?>';
        document.getElementById('filter_branch').value = defaultBranchScope;
        if (document.getElementById('filter_branch')._curtissSelect) {
            document.getElementById('filter_branch')._curtissSelect.setValue(defaultBranchScope);
        }
        document.getElementById('filter_date_from').value = '<?= $defaultDateFrom ?>';
        document.getElementById('filter_date_to').value = '<?= $defaultDateTo ?>';
        document.getElementById('quick_range_select').value = 'this_month';
        currentBankFilter = 'all';
        updateFilterActiveBadge();
        closeFilterModal();
        loadActiveReport();
    }

    function getFilterParams() {
        const branch = document.getElementById('filter_branch').value;
        const dateFrom = document.getElementById('filter_date_from').value;
        const dateTo = document.getElementById('filter_date_to').value;

        let urlParams = `branch_id=${encodeURIComponent(branch)}&date_from=${encodeURIComponent(dateFrom)}&date_to=${encodeURIComponent(dateTo)}`;

        if (currentReportType === 'bank_register' && currentBankFilter && currentBankFilter !== 'all') {
            urlParams += `&bank_account_id=${encodeURIComponent(currentBankFilter)}`;
        }

        const visibleCols = getVisibleColumnIndices();
        if (visibleCols.length > 0) {
            urlParams += `&visible_cols=${encodeURIComponent(visibleCols.join(','))}`;
        }
        return urlParams;
    }

    // ── Column Customizer Engine ──
    let reportColumnStates = {};

    function populateColumnCustomizer() {
        const container = document.getElementById('colCustomizerCheckboxList');
        if (!container) return;
        container.innerHTML = '';

        const thList = document.querySelectorAll('#reportContentArea table.financial-table thead th');
        if (!thList.length) {
            container.innerHTML = '<div style="font-size:0.8rem; color:#94a3b8; padding:1.25rem; text-align:center;">No customizable table columns found for this report.</div>';
            applyColumnVisibility();
            return;
        }

        if (!reportColumnStates[currentReportType]) {
            reportColumnStates[currentReportType] = Array.from(thList).map(() => true);
        } else if (reportColumnStates[currentReportType].length !== thList.length) {
            const oldState = reportColumnStates[currentReportType];
            reportColumnStates[currentReportType] = Array.from(thList).map((_, i) => oldState[i] !== undefined ? oldState[i] : true);
        }

        const state = reportColumnStates[currentReportType];

        thList.forEach((th, idx) => {
            const colName = th.innerText.trim() || `Column ${idx + 1}`;
            const isChecked = state[idx] !== false;

            const row = document.createElement('label');
            row.style.cssText = 'display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 10px; cursor: pointer; background: #ffffff; transition: all 0.15s ease; user-select: none;';
            row.innerHTML = `
                <span style="font-size: 0.83rem; font-weight: 600; color: #0f172a;">${escapeHtml(colName)}</span>
                <input type="checkbox" ${isChecked ? 'checked' : ''} onchange="toggleColumnState(${idx}, this.checked)" style="width: 16px; height: 16px; accent-color: #9333ea; cursor: pointer;">
            `;
            container.appendChild(row);
        });

        applyColumnVisibility();
    }

    function toggleColumnState(idx, isChecked) {
        if (!reportColumnStates[currentReportType]) return;
        reportColumnStates[currentReportType][idx] = isChecked;
        applyColumnVisibility();
    }

    function setAllColumnsVisible(status) {
        if (!reportColumnStates[currentReportType]) return;
        reportColumnStates[currentReportType] = reportColumnStates[currentReportType].map(() => status);
        populateColumnCustomizer();
    }

    function resetDefaultColumns() {
        if (!reportColumnStates[currentReportType]) return;
        reportColumnStates[currentReportType] = reportColumnStates[currentReportType].map(() => true);
        populateColumnCustomizer();
    }

    function applyColumnVisibility() {
        const state = reportColumnStates[currentReportType];
        const table = document.querySelector('#reportContentArea table.financial-table');
        const badge = document.getElementById('colCustomActiveDot');

        if (!state || !table) {
            if (badge) badge.style.display = 'none';
            return;
        }

        let hasHidden = false;
        let visibleCount = 0;

        // Apply visibility strictly to thead th and tbody td (by cell index in each row)
        const rows = table.querySelectorAll('thead tr, tbody tr');
        rows.forEach(tr => {
            if (tr.classList.contains('section-header')) return;
            const cells = tr.children;
            state.forEach((isVisible, idx) => {
                if (cells[idx]) {
                    if (isVisible) {
                        cells[idx].style.display = '';
                        cells[idx].classList.remove('col-hidden-print');
                    } else {
                        cells[idx].style.display = 'none';
                        cells[idx].classList.add('col-hidden-print');
                    }
                }
            });
        });

        // Count visible headers
        const thList = table.querySelectorAll('thead tr th');
        thList.forEach((th, idx) => {
            if (state[idx] !== false) visibleCount++;
            else hasHidden = true;
        });

        // Dynamically recalculate tfoot colspan so footer total row matches visible headers count
        const tfootTr = table.querySelector('tfoot tr');
        if (tfootTr) {
            const tfootCells = Array.from(tfootTr.children);
            if (tfootCells.length > 0) {
                const firstTd = tfootCells[0];
                const trailingTdsCount = tfootCells.length - 1;
                const newColspan = Math.max(1, visibleCount - trailingTdsCount);
                firstTd.setAttribute('colspan', newColspan);
            }
        }

        if (badge) badge.style.display = hasHidden ? 'block' : 'none';
    }

    function getVisibleColumnIndices() {
        const state = reportColumnStates[currentReportType];
        if (!state) return [];
        return state.map((v, i) => v ? i : null).filter(v => v !== null);
    }

    function openColumnCustomizerModal() {
        populateColumnCustomizer();
        document.getElementById('columnCustomizerModal').style.display = 'flex';
    }

    function closeColumnCustomizerModal() {
        document.getElementById('columnCustomizerModal').style.display = 'none';
    }

    function getApiUrl(action, params) {
        let path = window.location.pathname;
        let posBase = path.replace(/\/reports(\/.*)?$/, '');
        posBase = posBase.replace(/\/reports\.php$/, '');
        if (!posBase.endsWith('/')) posBase += '/';
        return `${posBase}reports_api.php?action=${action}&${params}`;
    }

    function loadActiveReport() {
        if (currentReportType === 'center') return;

        const contentArea = document.getElementById('reportContentArea');
        contentArea.innerHTML = '<div style="text-align: center; padding: 4rem 1rem; color: #64748b; font-weight: 600;"><div style="display:inline-block; width:32px; height:32px; border:3px solid rgba(15,23,42,0.15); border-top-color:#0f172a; border-radius:50%; animation:spin 0.8s linear infinite; margin-bottom:1rem;"></div><div>Loading report dataset...</div></div><style>@keyframes spin { to { transform: rotate(360deg); } }</style>';

        const params = getFilterParams();
        const url = getApiUrl(currentReportType, params);

        fetch(url)
            .then(async res => {
                if (res.status === 401) {
                    window.location.href = 'login?expired=1';
                    return null;
                }
                const contentType = res.headers.get('content-type') || '';
                if (contentType.includes('application/json')) {
                    return res.json();
                }
                const text = await res.text();
                throw new Error(`Server returned non-JSON response (${res.status}): ${text.substring(0, 150)}`);
            })
            .then(res => {
                if (!res) return;
                if (!res.success) {
                    if (res.message && res.message.toLowerCase().includes('session')) {
                        window.location.href = 'login?expired=1';
                        return;
                    }
                    contentArea.innerHTML = `<div style="padding:1.25rem; background:#fef2f2; border:1px solid #fecaca; color:#b91c1c; border-radius:12px; font-weight:600;">${escapeHtml(res.message || 'Failed to load report.')}</div>`;
                    return;
                }
                currentReportRawData = res.data;
                renderReportView(currentReportType, res.data);
                populateColumnCustomizer();
                updatePrintMetadata();
            })
            .catch(err => {
                contentArea.innerHTML = `<div style="padding:1.25rem; background:#fef2f2; border:1px solid #fecaca; color:#b91c1c; border-radius:12px; font-weight:600;">Network or server error: ${escapeHtml(err.message)}</div>`;
            });
    }

    function formatCurrency(amt) {
        return new Intl.NumberFormat('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amt || 0);
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function renderReportView(type, data) {
        const titleEl = document.getElementById('reportTitleText');
        const subTitleEl = document.getElementById('reportSubtitleText');
        const contentArea = document.getElementById('reportContentArea');

        const setTitle = (t, s) => {
            if (titleEl) titleEl.innerText = t;
            if (subTitleEl) subTitleEl.innerText = s;
        };

        if (type === 'kpi_performance') {
            setTitle('Executive KPI Dashboard', 'High-level business metrics across sales, profitability, stock, and GL');

            contentArea.innerHTML = `
                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-title">Gross Sales Revenue</div>
                        <div class="kpi-value" style="color: #2563eb;">${formatCurrency(data.sales.gross_sales)}</div>
                        <div class="kpi-sub">${data.sales.invoice_count} completed invoices</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-title">Net Sales (After Discount)</div>
                        <div class="kpi-value">${formatCurrency(data.sales.net_sales)}</div>
                        <div class="kpi-sub">Discounts: ${formatCurrency(data.sales.total_discounts)}</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-title">Cost of Goods Sold (COGS)</div>
                        <div class="kpi-value" style="color: #dc2626;">${formatCurrency(data.sales.cogs)}</div>
                        <div class="kpi-sub">Strict Lot Unit Costs</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-title">Gross Profit</div>
                        <div class="kpi-value" style="color: #16a34a;">${formatCurrency(data.sales.gross_profit)}</div>
                        <div class="kpi-sub">Margin: ${data.sales.profit_margin.toFixed(2)}%</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-title">Stock Valuation</div>
                        <div class="kpi-value">${formatCurrency(data.inventory.total_valuation)}</div>
                        <div class="kpi-sub">${data.inventory.total_qty} units in active lots</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-title">Supplier Payables</div>
                        <div class="kpi-value" style="color: #d97706;">${formatCurrency(data.purchases.total_supplier_outstanding)}</div>
                        <div class="kpi-sub">${data.purchases.supplier_count} suppliers with balance</div>
                    </div>
                    ${data.financials ? `
                    <div class="kpi-card">
                        <div class="kpi-title">Net Operating Profit</div>
                        <div class="kpi-value" style="color: ${data.financials.net_profit >= 0 ? '#16a34a' : '#dc2626'};">${formatCurrency(data.financials.net_profit)}</div>
                        <div class="kpi-sub">Net Profit / (Loss)</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-title">Trial Balance Status</div>
                        <div class="kpi-value">
                            <span class="status-pill ${data.financials.trial_balance_balanced ? 'status-balanced' : 'status-unbalanced'}">
                                ${data.financials.trial_balance_balanced ? 'BALANCED' : 'UNBALANCED'}
                            </span>
                        </div>
                        <div class="kpi-sub">GL Ledger Debit = Credit</div>
                    </div>
                    ` : ''}
                </div>
            `;
        } else if (type === 'sales_summary') {
            setTitle('Sales Summary Report', 'Total invoice count, gross revenue, discounts, net revenue, COGS, gross profit & margin');

            contentArea.innerHTML = `
                <div class="kpi-grid">
                    <div class="kpi-card"><div class="kpi-title">Invoices</div><div class="kpi-value">${data.invoice_count}</div></div>
                    <div class="kpi-card"><div class="kpi-title">Gross Sales</div><div class="kpi-value">${formatCurrency(data.gross_sales)}</div></div>
                    <div class="kpi-card"><div class="kpi-title">Total Discounts</div><div class="kpi-value">${formatCurrency(data.total_discounts)}</div></div>
                    <div class="kpi-card"><div class="kpi-title">Net Sales</div><div class="kpi-value">${formatCurrency(data.net_sales)}</div></div>
                    <div class="kpi-card"><div class="kpi-title">Total COGS</div><div class="kpi-value">${formatCurrency(data.cogs)}</div></div>
                    <div class="kpi-card"><div class="kpi-title">Gross Profit</div><div class="kpi-value" style="color: #16a34a;">${formatCurrency(data.gross_profit)}</div></div>
                    <div class="kpi-card"><div class="kpi-title">Profit Margin</div><div class="kpi-value">${data.profit_margin.toFixed(2)}%</div></div>
                </div>
            `;
        } else if (type === 'sales_by_product') {
            setTitle('Sales by Product Report', 'Detailed itemized sales breakdown with exact sold unit costs & profit margins');

            let rowsHtml = data.map(r => `
                <tr>
                    <td class="col-fit"><strong>${escapeHtml(r.sku)}</strong></td>
                    <td>${escapeHtml(r.product_name)}</td>
                    <td class="col-fit">${escapeHtml(r.category_name)}</td>
                    <td class="col-fit">${escapeHtml(r.brand_name)}</td>
                    <td class="amount-num">${r.total_units}</td>
                    <td class="amount-num">${formatCurrency(r.total_sales)}</td>
                    <td class="amount-num">${formatCurrency(r.total_cost)}</td>
                    <td class="amount-num" style="color: ${r.gross_profit >= 0 ? '#16a34a' : '#dc2626'};">${formatCurrency(r.gross_profit)}</td>
                    <td class="amount-num">${r.profit_margin.toFixed(2)}%</td>
                </tr>
            `).join('');

            const totSales = data.reduce((sum, r) => sum + r.total_sales, 0);
            const totCost  = data.reduce((sum, r) => sum + r.total_cost, 0);
            const totProfit= totSales - totCost;

            contentArea.innerHTML = `
                <div class="inv-table-panel">
                    <table class="financial-table">
                        <thead>
                            <tr>
                                <th class="col-fit">SKU</th>
                                <th>Product Name</th>
                                <th class="col-fit">Category</th>
                                <th class="col-fit">Brand</th>
                                <th class="amount-num">Units Sold</th>
                                <th class="amount-num">Total Sales</th>
                                <th class="amount-num">Total Cost</th>
                                <th class="amount-num">Gross Profit</th>
                                <th class="amount-num">Margin (%)</th>
                            </tr>
                        </thead>
                        <tbody>${rowsHtml || '<tr><td colspan="9" style="text-align:center; padding: 2rem; color: #94a3b8;">No sales data found.</td></tr>'}</tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td colspan="4">TOTALS</td>
                                <td class="amount-num"></td>
                                <td class="amount-num">${formatCurrency(totSales)}</td>
                                <td class="amount-num">${formatCurrency(totCost)}</td>
                                <td class="amount-num">${formatCurrency(totProfit)}</td>
                                <td class="amount-num">${totSales > 0 ? (totProfit/totSales*100).toFixed(2) : 0}%</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            `;
        } else if (type === 'sales_by_category') {
            setTitle('Sales by Category', 'Category performance summary');

            let rowsHtml = data.map(r => `
                <tr>
                    <td><strong>${escapeHtml(r.category_name)}</strong></td>
                    <td class="amount-num">${r.total_units}</td>
                    <td class="amount-num">${formatCurrency(r.total_sales)}</td>
                    <td class="amount-num">${formatCurrency(r.total_cost)}</td>
                    <td class="amount-num">${formatCurrency(r.gross_profit)}</td>
                </tr>
            `).join('');

            contentArea.innerHTML = `
                <div class="inv-table-panel">
                    <table class="financial-table">
                        <thead>
                            <tr>
                                <th>Category Name</th>
                                <th class="amount-num">Units Sold</th>
                                <th class="amount-num">Total Sales</th>
                                <th class="amount-num">Total Cost</th>
                                <th class="amount-num">Gross Profit</th>
                            </tr>
                        </thead>
                        <tbody>${rowsHtml || '<tr><td colspan="5" style="text-align:center; padding: 2rem; color: #94a3b8;">No category sales data found.</td></tr>'}</tbody>
                    </table>
                </div>
            `;
        } else if (type === 'sales_by_payment') {
            setTitle('Sales by Payment Method', 'Breakdown of completed sales payments by channel');

            let rowsHtml = data.map(r => `
                <tr>
                    <td class="col-fit"><strong>${escapeHtml(r.payment_method).toUpperCase()}</strong></td>
                    <td class="amount-num">${r.transaction_count}</td>
                    <td class="amount-num">${formatCurrency(r.total_amount)}</td>
                </tr>
            `).join('');

            contentArea.innerHTML = `
                <div class="inv-table-panel">
                    <table class="financial-table">
                        <thead>
                            <tr>
                                <th class="col-fit">Payment Method</th>
                                <th class="amount-num">Transaction Count</th>
                                <th class="amount-num">Total Collected</th>
                            </tr>
                        </thead>
                        <tbody>${rowsHtml || '<tr><td colspan="3" style="text-align:center; padding: 2rem; color: #94a3b8;">No payment records found.</td></tr>'}</tbody>
                    </table>
                </div>
            `;
        } else if (type === 'sales_history') {
            setTitle('Sales History Log Report', 'Chronological invoice audit log with customer, payment, cashier & discount details');

            let rowsHtml = data.map(r => `
                <tr>
                    <td class="col-fit"><strong>${escapeHtml(r.invoice_no)}</strong></td>
                    <td class="col-fit">${escapeHtml(r.sale_date)}</td>
                    <td class="col-text-nowrap">${escapeHtml(r.branch_name)}</td>
                    <td>${escapeHtml(r.customer_name)}</td>
                    <td class="col-fit"><span style="font-weight: 700; color: #2563eb;">${escapeHtml(r.payment_method)}</span></td>
                    <td class="col-text-nowrap">${escapeHtml(r.cashier_name)}</td>
                    <td class="amount-num">${formatCurrency(r.subtotal)}</td>
                    <td class="amount-num" style="color: #dc2626;">${formatCurrency(r.discount_amount)}</td>
                    <td class="amount-num">${formatCurrency(r.total_amount)}</td>
                    <td class="col-fit">
                        <span class="status-pill ${r.status === 'completed' ? 'status-balanced' : 'status-unbalanced'}">
                            ${escapeHtml(r.status).toUpperCase()}
                        </span>
                    </td>
                </tr>
            `).join('');

            const totNet = data.reduce((sum, r) => sum + r.total_amount, 0);

            contentArea.innerHTML = `
                <div class="inv-table-panel">
                    <table class="financial-table">
                        <thead>
                            <tr>
                                <th class="col-fit">Invoice #</th>
                                <th class="col-fit">Date & Time</th>
                                <th>Branch</th>
                                <th>Customer</th>
                                <th class="col-fit">Payment Method</th>
                                <th>Cashier</th>
                                <th class="amount-num">Subtotal</th>
                                <th class="amount-num">Discount</th>
                                <th class="amount-num">Net Total</th>
                                <th class="col-fit">Status</th>
                            </tr>
                        </thead>
                        <tbody>${rowsHtml || '<tr><td colspan="10" style="text-align:center; padding: 2rem; color: #94a3b8;">No sales history transactions found.</td></tr>'}</tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td colspan="8">TOTAL SALES HISTORY NET REVENUE</td>
                                <td class="amount-num">${formatCurrency(totNet)}</td>
                                <td class="col-fit"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            `;
        } else if (type === 'purchase_summary') {
            setTitle('Purchasing & GRN Summary', 'Total GRN invoices, received amounts, and supplier payments');

            contentArea.innerHTML = `
                <div class="kpi-grid">
                    <div class="kpi-card"><div class="kpi-title">Total GRN Count</div><div class="kpi-value">${data.grn_count}</div></div>
                    <div class="kpi-card"><div class="kpi-title">Total GRN Amount</div><div class="kpi-value">${formatCurrency(data.total_grn_amount)}</div></div>
                    <div class="kpi-card"><div class="kpi-title">Total Payments Paid</div><div class="kpi-value">${formatCurrency(data.total_payments_paid)}</div></div>
                </div>
            `;
        } else if (type === 'supplier_outstanding') {
            setTitle('Supplier Outstanding Payables Aging', 'Active supplier credit balances & accounts payable aging');

            let rowsHtml = data.map(r => `
                <tr>
                    <td><strong>${escapeHtml(r.supplier_name)}</strong></td>
                    <td class="col-fit">${escapeHtml(r.phone) || '-'}</td>
                    <td class="amount-num">${formatCurrency(r.total_grn_amount)}</td>
                    <td class="amount-num">${formatCurrency(r.total_paid)}</td>
                    <td class="amount-num" style="color: #dc2626;">${formatCurrency(r.outstanding_balance)}</td>
                </tr>
            `).join('');

            const totBal = data.reduce((sum, r) => sum + r.outstanding_balance, 0);

            contentArea.innerHTML = `
                <div class="inv-table-panel">
                    <table class="financial-table">
                        <thead>
                            <tr>
                                <th>Supplier Name</th>
                                <th class="col-fit">Phone</th>
                                <th class="amount-num">Total Purchases</th>
                                <th class="amount-num">Total Paid</th>
                                <th class="amount-num">Outstanding Balance</th>
                            </tr>
                        </thead>
                        <tbody>${rowsHtml || '<tr><td colspan="5" style="text-align:center; padding: 2rem; color: #94a3b8;">No supplier balances found.</td></tr>'}</tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td colspan="4">TOTAL OUTSTANDING PAYABLES</td>
                                <td class="amount-num" style="color: #dc2626;">${formatCurrency(totBal)}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            `;
        } else if (type === 'stock_valuation') {
            setTitle('Stock Valuation Report (Lot-based Cost)', 'Total inventory asset valuation based strictly on exact remaining product lot costs');

            let rowsHtml = data.items.map(r => `
                <tr>
                    <td class="col-fit"><strong>${escapeHtml(r.lot_number)}</strong></td>
                    <td class="col-fit">${escapeHtml(r.sku)}</td>
                    <td>${escapeHtml(r.name)}</td>
                    <td class="col-fit">${escapeHtml(r.category_name)}</td>
                    <td class="col-fit">${escapeHtml(r.brand_name)}</td>
                    <td class="amount-num">${r.remaining_qty}</td>
                    <td class="amount-num">${formatCurrency(r.unit_cost)}</td>
                    <td class="amount-num">${formatCurrency(r.total_lot_valuation)}</td>
                </tr>
            `).join('');

            contentArea.innerHTML = `
                <div class="inv-table-panel">
                    <table class="financial-table">
                        <thead>
                            <tr>
                                <th class="col-fit">Lot #</th>
                                <th class="col-fit">SKU</th>
                                <th>Product Name</th>
                                <th class="col-fit">Category</th>
                                <th class="col-fit">Brand</th>
                                <th class="amount-num">Remaining Qty</th>
                                <th class="amount-num">Unit Cost</th>
                                <th class="amount-num">Stock Valuation</th>
                            </tr>
                        </thead>
                        <tbody>${rowsHtml || '<tr><td colspan="8" style="text-align:center; padding: 2rem; color: #94a3b8;">No active stock lots found.</td></tr>'}</tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td colspan="5">TOTAL INVENTORY VALUATION</td>
                                <td class="amount-num">${data.total_qty}</td>
                                <td class="amount-num"></td>
                                <td class="amount-num">${formatCurrency(data.total_valuation)}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            `;
        } else if (type === 'low_stock') {
            setTitle('Low Stock Alert Report', 'Products below minimum reorder alert levels');

            let rowsHtml = data.map(r => `
                <tr>
                    <td class="col-fit"><strong>${escapeHtml(r.sku)}</strong></td>
                    <td>${escapeHtml(r.name)}</td>
                    <td class="col-fit">${escapeHtml(r.category_name)}</td>
                    <td class="amount-num" style="color: #dc2626; font-weight: 700;">${r.total_stock}</td>
                    <td class="amount-num">${r.reorder_level}</td>
                </tr>
            `).join('');

            contentArea.innerHTML = `
                <div class="inv-table-panel">
                    <table class="financial-table">
                        <thead>
                            <tr>
                                <th class="col-fit">SKU</th>
                                <th>Product Name</th>
                                <th class="col-fit">Category</th>
                                <th class="amount-num">Current Stock</th>
                                <th class="amount-num">Reorder Threshold</th>
                            </tr>
                        </thead>
                        <tbody>${rowsHtml || '<tr><td colspan="5" style="text-align:center; padding: 2rem; color: #94a3b8;">No products currently low in stock.</td></tr>'}</tbody>
                    </table>
                </div>
            `;
        } else if (type === 'cashier_performance') {
            setTitle('Business Day & Cashier Log', 'Business day opening float, total sales collected, drawer cash deposits, closing cash & over/short variances');

            let rowsHtml = (data.items || []).map(r => `
                <tr>
                    <td class="col-fit"><strong>#${r.day_id}</strong></td>
                    <td class="col-text-nowrap">${escapeHtml(r.branch_name)}</td>
                    <td class="col-text-nowrap">${escapeHtml(r.opened_by)}</td>
                    <td class="col-fit">${escapeHtml(r.opened_at)}</td>
                    <td class="col-fit">${escapeHtml(r.closed_at || 'Active Open Day')}</td>
                    <td class="amount-num">${formatCurrency(r.opening_cash)}</td>
                    <td class="amount-num">${formatCurrency(r.total_sales)}</td>
                    <td class="amount-num">${formatCurrency(r.total_deposits)}</td>
                    <td class="amount-num">${formatCurrency(r.closing_cash)}</td>
                    <td class="col-fit"><span class="status-pill ${r.status === 'open' ? 'status-balanced' : 'status-unbalanced'}">${escapeHtml(r.status).toUpperCase()}</span></td>
                </tr>
            `).join('');

            contentArea.innerHTML = `
                <div class="inv-table-panel">
                    <table class="financial-table">
                        <thead>
                            <tr>
                                <th class="col-fit">Day #</th>
                                <th>Branch</th>
                                <th>Opened By</th>
                                <th class="col-fit">Opened At</th>
                                <th class="col-fit">Closed At</th>
                                <th class="amount-num">Opening Float</th>
                                <th class="amount-num">Total Sales</th>
                                <th class="amount-num">Total Deposits</th>
                                <th class="amount-num">Closing Cash</th>
                                <th class="col-fit">Status</th>
                            </tr>
                        </thead>
                        <tbody>${rowsHtml || '<tr><td colspan="10" style="text-align:center; padding: 2rem; color: #94a3b8;">No business day records found.</td></tr>'}</tbody>
                    </table>
                </div>
            `;
        } else if (type === 'supplier_returns' || type === 'returns_adjustments') {
            setTitle('Supplier Purchase Returns Log', 'Audit breakdown of inventory returned to suppliers against Goods Received Notes (GRN), payable offsets & credits');

            const summary = data.summary || {};
            const summaryHtml = `
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 14px;">
                    <div class="kpi-card-metric" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 16px;">
                        <div style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Total Returns</div>
                        <div style="font-size: 18px; font-weight: 800; color: #0f172a; margin-top: 4px; font-family: monospace;">${summary.count || 0} Return(s)</div>
                    </div>
                    <div class="kpi-card-metric" style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; padding: 12px 16px;">
                        <div style="font-size: 11px; font-weight: 800; color: #1e40af; text-transform: uppercase;">Total Return Value</div>
                        <div style="font-size: 18px; font-weight: 800; color: #2563eb; margin-top: 4px; font-family: monospace;">LKR ${formatCurrency(summary.total_return_amount || 0)}</div>
                    </div>
                    <div class="kpi-card-metric" style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 12px 16px;">
                        <div style="font-size: 11px; font-weight: 800; color: #166534; text-transform: uppercase;">AP Payable Offset</div>
                        <div style="font-size: 18px; font-weight: 800; color: #15803d; margin-top: 4px; font-family: monospace;">LKR ${formatCurrency(summary.total_payable_offset || 0)}</div>
                    </div>
                    <div class="kpi-card-metric" style="background: #faf5ff; border: 1px solid #e9d5ff; border-radius: 10px; padding: 12px 16px;">
                        <div style="font-size: 11px; font-weight: 800; color: #6b21a8; text-transform: uppercase;">Supplier Credit Balance</div>
                        <div style="font-size: 18px; font-weight: 800; color: #7e22ce; margin-top: 4px; font-family: monospace;">LKR ${formatCurrency(summary.total_credit_amount || 0)}</div>
                    </div>
                </div>
            `;

            let rowsHtml = (data.items || []).map(r => `
                <tr>
                    <td class="col-fit"><strong>${escapeHtml(r.return_number)}</strong></td>
                    <td class="col-fit">${escapeHtml(r.return_date)}</td>
                    <td class="col-text-nowrap">${escapeHtml(r.branch_name)}</td>
                    <td><strong>${escapeHtml(r.supplier_name || r.party_name)}</strong> ${r.supplier_code ? `<span style="font-size:10px; color:#64748b; font-family:monospace;">(${escapeHtml(r.supplier_code)})</span>` : ''}</td>
                    <td class="col-fit"><span style="font-family:monospace; font-weight:700; color:#475569;">${escapeHtml(r.grn_number || '-')}</span></td>
                    <td class="amount-num" style="font-weight:700; color:#0f172a;">${formatCurrency(r.total_return_amount || r.amount)}</td>
                    <td class="amount-num" style="color:#15803d;">${formatCurrency(r.applied_to_payable || 0)}</td>
                    <td class="amount-num" style="color:#7e22ce;">${formatCurrency(r.credit_amount || 0)}</td>
                    <td style="font-size:11.5px; color:#64748b;">${escapeHtml(r.notes || '—')}</td>
                    <td class="col-fit"><span class="status-pill status-balanced">${escapeHtml(r.status || 'COMPLETED').toUpperCase()}</span></td>
                </tr>
            `).join('');

            contentArea.innerHTML = `
                ${summaryHtml}
                <div class="inv-table-panel">
                    <table class="financial-table">
                        <thead>
                            <tr>
                                <th class="col-fit">Return #</th>
                                <th class="col-fit">Return Date</th>
                                <th>Branch</th>
                                <th>Supplier</th>
                                <th class="col-fit">GRN Ref</th>
                                <th class="amount-num">Return Total (LKR)</th>
                                <th class="amount-num">AP Offset (LKR)</th>
                                <th class="amount-num">Credit (LKR)</th>
                                <th>Notes</th>
                                <th class="col-fit">Status</th>
                            </tr>
                        </thead>
                        <tbody>${rowsHtml || '<tr><td colspan="10" style="text-align:center; padding: 2rem; color: #94a3b8;">No supplier return records found for selected period and branch.</td></tr>'}</tbody>
                    </table>
                </div>
            `;
        } else if (type === 'stock_adjustments') {
            setTitle('Stock Adjustments Log', 'Audit breakdown of manual inventory increases, decreases, lot batch allocations & adjustment reasons');

            const summary = data.summary || {};
            const summaryHtml = `
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 14px;">
                    <div class="kpi-card-metric" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 16px;">
                        <div style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Total Adjustments</div>
                        <div style="font-size: 18px; font-weight: 800; color: #0f172a; margin-top: 4px; font-family: monospace;">${summary.count || 0} Record(s)</div>
                    </div>
                    <div class="kpi-card-metric" style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 12px 16px;">
                        <div style="font-size: 11px; font-weight: 800; color: #166534; text-transform: uppercase;">Total Increases</div>
                        <div style="font-size: 18px; font-weight: 800; color: #15803d; margin-top: 4px; font-family: monospace;">+${parseFloat(summary.total_increases_qty || 0).toLocaleString()} Qty</div>
                    </div>
                    <div class="kpi-card-metric" style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: 12px 16px;">
                        <div style="font-size: 11px; font-weight: 800; color: #991b1b; text-transform: uppercase;">Total Decreases</div>
                        <div style="font-size: 18px; font-weight: 800; color: #dc2626; margin-top: 4px; font-family: monospace;">-${parseFloat(summary.total_decreases_qty || 0).toLocaleString()} Qty</div>
                    </div>
                    <div class="kpi-card-metric" style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; padding: 12px 16px;">
                        <div style="font-size: 11px; font-weight: 800; color: #1e40af; text-transform: uppercase;">Net Qty Change</div>
                        <div style="font-size: 18px; font-weight: 800; color: ${(summary.total_net_qty_change || 0) >= 0 ? '#15803d' : '#dc2626'}; margin-top: 4px; font-family: monospace;">${(summary.total_net_qty_change || 0) >= 0 ? '+' : ''}${parseFloat(summary.total_net_qty_change || 0).toLocaleString()}</div>
                    </div>
                </div>
            `;

            let rowsHtml = (data.items || []).map(r => {
                const isIncrease = r.adjustment_type === 'increase';
                const typeBadge = isIncrease
                    ? `<span class="status-pill status-balanced" style="background:#dcfce7; color:#15803d; border-color:#86efac;">+ INCREASE</span>`
                    : `<span class="status-pill" style="background:#fee2e2; color:#b91c1c; border-color:#fca5a5;">- DECREASE</span>`;

                return `
                    <tr>
                        <td class="col-fit"><strong>${escapeHtml(r.adjustment_number)}</strong></td>
                        <td class="col-fit">${escapeHtml(r.adjustment_date)}</td>
                        <td class="col-text-nowrap">${escapeHtml(r.branch_name)}</td>
                        <td class="col-fit"><code style="font-size:11px; font-weight:700;">${escapeHtml(r.sku)}</code></td>
                        <td><strong>${escapeHtml(r.product_name)}</strong></td>
                        <td class="col-fit"><span style="font-family:monospace; font-size:11px;">${escapeHtml(r.lot_number)}</span></td>
                        <td class="col-fit">${typeBadge}</td>
                        <td class="amount-num" style="font-weight:700; color:${isIncrease ? '#15803d' : '#dc2626'};">${isIncrease ? '+' : '-'}${parseFloat(r.quantity).toLocaleString()}</td>
                        <td class="amount-num">${formatCurrency(r.unit_cost)}</td>
                        <td class="amount-num" style="font-weight:700;">${formatCurrency(r.total_value)}</td>
                        <td>${escapeHtml(r.reason)}</td>
                        <td class="col-fit" style="font-size:11px; color:#64748b;">${escapeHtml(r.adjusted_by)}</td>
                    </tr>
                `;
            }).join('');

            contentArea.innerHTML = `
                ${summaryHtml}
                <div class="inv-table-panel">
                    <table class="financial-table">
                        <thead>
                            <tr>
                                <th class="col-fit">Adjustment #</th>
                                <th class="col-fit">Date</th>
                                <th>Branch</th>
                                <th class="col-fit">SKU</th>
                                <th>Product Name</th>
                                <th class="col-fit">Lot Batch</th>
                                <th class="col-fit">Type</th>
                                <th class="amount-num">Qty</th>
                                <th class="amount-num">Unit Cost (LKR)</th>
                                <th class="amount-num">Total Value (LKR)</th>
                                <th>Reason</th>
                                <th class="col-fit">Adjusted By</th>
                            </tr>
                        </thead>
                        <tbody>${rowsHtml || '<tr><td colspan="12" style="text-align:center; padding: 2rem; color: #94a3b8;">No stock adjustment records found for selected period and branch.</td></tr>'}</tbody>
                    </table>
                </div>
            `;
        } else if (type === 'customer_outstanding') {
            setTitle('Customer Accounts Receivable Aging', 'Registered customer credit balances & outstanding accounts receivable');

            let rowsHtml = (data.customers || []).map(r => `
                <tr>
                    <td class="col-fit"><strong>${escapeHtml(r.customer_code)}</strong></td>
                    <td>${escapeHtml(r.customer_name)}</td>
                    <td class="col-fit">${escapeHtml(r.phone || '-')}</td>
                    <td class="amount-num" style="color: #dc2626;">${formatCurrency(r.outstanding_balance)}</td>
                </tr>
            `).join('');

            contentArea.innerHTML = `
                <div class="inv-table-panel">
                    <table class="financial-table">
                        <thead>
                            <tr>
                                <th class="col-fit">Customer Code</th>
                                <th>Customer Name</th>
                                <th class="col-fit">Phone</th>
                                <th class="amount-num">Outstanding AR Balance</th>
                            </tr>
                        </thead>
                        <tbody>${rowsHtml || '<tr><td colspan="4" style="text-align:center; padding: 2rem; color: #94a3b8;">No customer credit balances found.</td></tr>'}</tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td colspan="3">TOTAL OUTSTANDING RECEIVABLES</td>
                                <td class="amount-num" style="color: #dc2626;">${formatCurrency(data.total_outstanding)}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            `;
        } else if (type === 'damaged_stock') {
            setTitle('Damaged & Written-off Stock Report', 'Logged damaged items, quantities, cost losses, and reasons');

            let rowsHtml = (data.items || []).map(r => `
                <tr>
                    <td class="col-fit"><strong>${escapeHtml(r.sku)}</strong></td>
                    <td>${escapeHtml(r.product_name)}</td>
                    <td class="col-text-nowrap">${escapeHtml(r.branch_name)}</td>
                    <td class="col-fit">${escapeHtml(r.stock_type)}</td>
                    <td class="amount-num">${r.quantity}</td>
                    <td class="amount-num">${formatCurrency(r.cost)}</td>
                    <td class="amount-num" style="color:#dc2626;">${formatCurrency(r.total_cost_loss)}</td>
                    <td>${escapeHtml(r.reason)}</td>
                    <td class="col-fit">${escapeHtml(r.logged_date)}</td>
                </tr>
            `).join('');

            contentArea.innerHTML = `
                <div class="inv-table-panel">
                    <table class="financial-table">
                        <thead>
                            <tr>
                                <th class="col-fit">SKU</th>
                                <th>Product Name</th>
                                <th>Branch</th>
                                <th class="col-fit">Stock Type</th>
                                <th class="amount-num">Quantity</th>
                                <th class="amount-num">Cost Price</th>
                                <th class="amount-num">Total Cost Loss</th>
                                <th>Reason</th>
                                <th class="col-fit">Logged Date</th>
                            </tr>
                        </thead>
                        <tbody>${rowsHtml || '<tr><td colspan="9" style="text-align:center; padding: 2rem; color: #94a3b8;">No damaged stock records found.</td></tr>'}</tbody>
                    </table>
                </div>
            `;
        } else if (type === 'in_transit_stock') {
            setTitle('In-Transit Stock Transfers Report', 'Active stock transfers currently in transit between branches');

            let rowsHtml = (data.items || []).map(r => `
                <tr>
                    <td class="col-fit"><strong>${escapeHtml(r.transfer_number)}</strong></td>
                    <td class="col-text-nowrap">${escapeHtml(r.from_branch)}</td>
                    <td class="col-text-nowrap">${escapeHtml(r.to_branch)}</td>
                    <td class="col-fit">${escapeHtml(r.sku)}</td>
                    <td>${escapeHtml(r.product_name)}</td>
                    <td class="amount-num">${r.quantity}</td>
                    <td class="amount-num">${formatCurrency(r.unit_cost)}</td>
                    <td class="amount-num">${formatCurrency(r.transfer_value)}</td>
                    <td class="col-fit">${escapeHtml(r.sent_at)}</td>
                </tr>
            `).join('');

            contentArea.innerHTML = `
                <div class="inv-table-panel">
                    <table class="financial-table">
                        <thead>
                            <tr>
                                <th class="col-fit">Transfer #</th>
                                <th>From Branch</th>
                                <th>To Branch</th>
                                <th class="col-fit">SKU</th>
                                <th>Product Name</th>
                                <th class="amount-num">Quantity</th>
                                <th class="amount-num">Unit Cost</th>
                                <th class="amount-num">Transfer Value</th>
                                <th class="col-fit">Sent Date</th>
                            </tr>
                        </thead>
                        <tbody>${rowsHtml || '<tr><td colspan="9" style="text-align:center; padding: 2rem; color: #94a3b8;">No in-transit transfers found.</td></tr>'}</tbody>
                    </table>
                </div>
            `;
        } else if (type === 'petty_cash_register') {
            setTitle('Petty Cash Register Report', 'Petty cash deposits, expenses, and movement reasons');

            let rowsHtml = (data.items || []).map(r => `
                <tr>
                    <td class="col-fit"><strong>#${r.id}</strong></td>
                    <td class="col-text-nowrap">${escapeHtml(r.branch_name)}</td>
                    <td class="col-fit"><span style="font-weight:700; color:${r.movement_type === 'deposit' ? '#16a34a' : '#dc2626'};">${escapeHtml(r.movement_type).toUpperCase()}</span></td>
                    <td>${escapeHtml(r.reason)}</td>
                    <td class="amount-num">${formatCurrency(r.amount)}</td>
                    <td class="col-fit">${escapeHtml(r.created_at)}</td>
                </tr>
            `).join('');

            contentArea.innerHTML = `
                <div class="inv-table-panel">
                    <table class="financial-table">
                        <thead>
                            <tr>
                                <th class="col-fit">ID</th>
                                <th>Branch</th>
                                <th class="col-fit">Movement Type</th>
                                <th>Reason</th>
                                <th class="amount-num">Amount</th>
                                <th class="col-fit">Date & Time</th>
                            </tr>
                        </thead>
                        <tbody>${rowsHtml || '<tr><td colspan="6" style="text-align:center; padding: 2rem; color: #94a3b8;">No petty cash movements found.</td></tr>'}</tbody>
                    </table>
                </div>
            `;
        } else if (type === 'bank_register') {
            setTitle('Bank Register Ledger Report', 'Journal movements impacting operational bank account running balances');

            const bankAccounts = data.bank_accounts || [];
            const selectedBankId = String(data.selected_bank_id || currentBankFilter || 'all');

            let bankOptsHtml = `<option value="all" ${selectedBankId === 'all' ? 'selected' : ''}>All Bank Accounts (Consolidated)</option>`;
            bankAccounts.forEach(b => {
                const isSel = String(b.id) === selectedBankId ? 'selected' : '';
                bankOptsHtml += `<option value="${b.id}" ${isSel}>${escapeHtml(b.bank_name)} - ${escapeHtml(b.account_name)} (${escapeHtml(b.account_number)})</option>`;
            });

            const openingBal = parseFloat(data.opening_balance || 0);
            const totalDebit = parseFloat(data.total_debit || 0);
            const totalCredit = parseFloat(data.total_credit || 0);
            const currentBal = parseFloat(data.current_balance || 0);

            const summaryHtml = `
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-bottom: 1rem;">
                    <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 16px;">
                        <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #64748b; letter-spacing: 0.05em;">Opening Balance</div>
                        <div style="font-family: 'Outfit', sans-serif; font-size: 1.3rem; font-weight: 900; color: #0f172a; margin-top: 4px;">LKR ${formatCurrency(openingBal)}</div>
                    </div>
                    <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 16px;">
                        <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #16a34a; letter-spacing: 0.05em;">Total Deposits (Debit)</div>
                        <div style="font-family: 'Outfit', sans-serif; font-size: 1.3rem; font-weight: 900; color: #16a34a; margin-top: 4px;">+ LKR ${formatCurrency(totalDebit)}</div>
                    </div>
                    <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 16px;">
                        <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #dc2626; letter-spacing: 0.05em;">Total Withdrawals (Credit)</div>
                        <div style="font-family: 'Outfit', sans-serif; font-size: 1.3rem; font-weight: 900; color: #dc2626; margin-top: 4px;">- LKR ${formatCurrency(totalCredit)}</div>
                    </div>
                    <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 12px; padding: 12px 16px;">
                        <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #0f172a; letter-spacing: 0.05em;">Ending Ledger Balance</div>
                        <div style="font-family: 'Outfit', sans-serif; font-size: 1.3rem; font-weight: 900; color: #0f172a; margin-top: 4px;">LKR ${formatCurrency(currentBal)}</div>
                    </div>
                </div>
            `;

            const filterBarHtml = `
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 10px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 12px; font-weight: 700; color: #64748b;">${(data.items || []).length} Ledger Transactions</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #64748b; letter-spacing: 0.05em;">Bank Account:</span>
                        <select id="inline_filter_bank_account" onchange="handleBankFilterChange(this.value)" style="height: 34px; min-height: 34px; padding: 0 14px; border-radius: 999px; font-size: 12px; font-weight: 700; border: 1px solid #e2e8f0; background: #fff; cursor: pointer;">
                            ${bankOptsHtml}
                        </select>
                    </div>
                </div>
            `;

            let rowsHtml = (data.items || []).map(r => `
                <tr>
                    <td class="col-fit"><strong>${escapeHtml(r.entry_number)}</strong></td>
                    <td class="col-fit">${escapeHtml(r.entry_date)}</td>
                    <td>
                        <div style="font-weight: 700; color: #0f172a;">${escapeHtml(r.bank_name || '-')}</div>
                        <div style="font-size: 11px; color: #64748b;">${escapeHtml(r.account_name || '')} (${escapeHtml(r.account_number || '')})</div>
                    </td>
                    <td>${escapeHtml(r.description)}</td>
                    <td class="amount-num" style="color: #16a34a; font-weight: 700;">${r.debit > 0 ? '+ ' + formatCurrency(r.debit) : '-'}</td>
                    <td class="amount-num" style="color: #dc2626; font-weight: 700;">${r.credit > 0 ? '- ' + formatCurrency(r.credit) : '-'}</td>
                    <td class="amount-num" style="font-weight:800;">${formatCurrency(r.running_balance)}</td>
                </tr>
            `).join('');

            contentArea.innerHTML = `
                ${summaryHtml}
                ${filterBarHtml}
                <div class="inv-table-panel">
                    <table class="financial-table">
                        <thead>
                            <tr>
                                <th class="col-fit">Entry #</th>
                                <th class="col-fit">Date</th>
                                <th>Bank Account</th>
                                <th>Description</th>
                                <th class="amount-num">Debit (LKR)</th>
                                <th class="amount-num">Credit (LKR)</th>
                                <th class="amount-num">Running Balance (LKR)</th>
                            </tr>
                        </thead>
                        <tbody>${rowsHtml || '<tr><td colspan="7" style="text-align:center; padding: 2.5rem; color: #94a3b8;">No bank ledger entries found for selected filter scope.</td></tr>'}</tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td colspan="4">TOTAL MOVEMENTS & LEDGER BALANCE</td>
                                <td class="amount-num" style="color: #16a34a;">+ ${formatCurrency(totalDebit)}</td>
                                <td class="amount-num" style="color: #dc2626;">- ${formatCurrency(totalCredit)}</td>
                                <td class="amount-num" style="font-weight: 900; color: #0f172a;">${formatCurrency(currentBal)}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            `;

            const bSelect = document.getElementById('inline_filter_bank_account');
            if (bSelect && typeof CurtissSearchableSelect !== 'undefined') {
                CurtissSearchableSelect.attach(bSelect);
            }
        } else if (type === 'customer_cheques') {
            setTitle('Customer Cheques Register', 'Received customer cheques, issuing banks, due dates, and status');

            let rowsHtml = (data.items || []).map(r => `
                <tr>
                    <td class="col-fit"><strong>${escapeHtml(r.cheque_number)}</strong></td>
                    <td>${escapeHtml(r.customer_name)}</td>
                    <td class="col-fit">${escapeHtml(r.bank_name)}</td>
                    <td class="col-fit">${escapeHtml(r.cheque_date)}</td>
                    <td class="amount-num">${formatCurrency(r.amount)}</td>
                    <td class="col-text-nowrap">${escapeHtml(r.branch_name)}</td>
                    <td class="col-fit"><span class="status-pill status-balanced">${escapeHtml(r.status).toUpperCase()}</span></td>
                </tr>
            `).join('');

            contentArea.innerHTML = `
                <div class="inv-table-panel">
                    <table class="financial-table">
                        <thead>
                            <tr>
                                <th class="col-fit">Cheque #</th>
                                <th>Customer Name</th>
                                <th class="col-fit">Bank</th>
                                <th class="col-fit">Cheque Date</th>
                                <th class="amount-num">Amount</th>
                                <th>Branch</th>
                                <th class="col-fit">Status</th>
                            </tr>
                        </thead>
                        <tbody>${rowsHtml || '<tr><td colspan="7" style="text-align:center; padding: 2rem; color: #94a3b8;">No customer cheques found.</td></tr>'}</tbody>
                    </table>
                </div>
            `;
        } else if (type === 'supplier_cheques') {
            setTitle('Supplier Cheques Register', 'Issued supplier payment cheques, due dates, and bank clearance status');

            let rowsHtml = (data.items || []).map(r => `
                <tr>
                    <td class="col-fit"><strong>${escapeHtml(r.cheque_number)}</strong></td>
                    <td>${escapeHtml(r.supplier_name)}</td>
                    <td class="col-fit">${escapeHtml(r.cheque_date)}</td>
                    <td class="amount-num">${formatCurrency(r.amount)}</td>
                    <td class="col-text-nowrap">${escapeHtml(r.branch_name)}</td>
                    <td class="col-fit"><span class="status-pill status-balanced">${escapeHtml(r.status).toUpperCase()}</span></td>
                </tr>
            `).join('');

            contentArea.innerHTML = `
                <div class="inv-table-panel">
                    <table class="financial-table">
                        <thead>
                            <tr>
                                <th class="col-fit">Cheque #</th>
                                <th>Supplier Name</th>
                                <th class="col-fit">Cheque Date</th>
                                <th class="amount-num">Amount</th>
                                <th>Branch</th>
                                <th class="col-fit">Status</th>
                            </tr>
                        </thead>
                        <tbody>${rowsHtml || '<tr><td colspan="6" style="text-align:center; padding: 2rem; color: #94a3b8;">No supplier cheques found.</td></tr>'}</tbody>
                    </table>
                </div>
            `;
        } else if (type === 'profit_and_loss') {
            setTitle('Profit & Loss Statement (P&L)', 'Income Statement - Dynamic calculation from GL ledger entries & sales transactions');

            contentArea.innerHTML = `
                <div class="inv-table-panel">
                    <table class="financial-table">
                        <thead>
                            <tr>
                                <th>Revenue & Cost Category</th>
                                <th class="amount-num">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="section-header"><td colspan="2">REVENUE</td></tr>
                            <tr><td>Gross Sales Revenue (Account 4010)</td><td class="amount-num">${formatCurrency(data.gross_sales)}</td></tr>
                            <tr><td>Sales Returns & Allowances (Account 4020)</td><td class="amount-num" style="color: #dc2626;">(${formatCurrency(data.sales_returns)})</td></tr>
                            <tr style="font-weight: 700; background: #f8fafc;"><td>NET SALES REVENUE</td><td class="amount-num">${formatCurrency(data.net_revenue)}</td></tr>

                            <tr class="section-header"><td colspan="2">COST OF GOODS SOLD</td></tr>
                            <tr><td>Cost of Goods Sold (Account 5010)</td><td class="amount-num">${formatCurrency(data.cogs)}</td></tr>
                            <tr class="total-row"><td>GROSS PROFIT</td><td class="amount-num" style="color: ${data.gross_profit >= 0 ? '#16a34a' : '#dc2626'};">${formatCurrency(data.gross_profit)}</td></tr>

                            <tr class="section-header"><td colspan="2">OTHER INCOME & OPERATING EXPENSES</td></tr>
                            <tr><td>Bank Interest & Other Income (Account 4050)</td><td class="amount-num">${formatCurrency(data.other_income)}</td></tr>
                            <tr><td>Operating Expenses (Account 5020 - 5090)</td><td class="amount-num" style="color: #dc2626;">(${formatCurrency(data.total_expenses)})</td></tr>

                            <tr class="total-row" style="font-size: 1rem;">
                                <td>NET PROFIT / (LOSS) FOR THE PERIOD</td>
                                <td class="amount-num" style="color: ${data.net_profit >= 0 ? '#16a34a' : '#dc2626'};">${formatCurrency(data.net_profit)}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            `;
        } else if (type === 'balance_sheet') {
            setTitle('Balance Sheet Statement', 'Statement of Financial Position: Assets = Liabilities + Equity');

            let assetRows = data.assets.accounts.map(r => `
                <tr><td>${escapeHtml(r.account_code)} - ${escapeHtml(r.account_name)}</td><td class="amount-num">${formatCurrency(r.balance)}</td></tr>
            `).join('');

            let liabRows = data.liabilities.accounts.map(r => `
                <tr><td>${escapeHtml(r.account_code)} - ${escapeHtml(r.account_name)}</td><td class="amount-num">${formatCurrency(r.balance)}</td></tr>
            `).join('');

            let eqRows = data.equity.accounts.map(r => `
                <tr><td>${escapeHtml(r.account_code)} - ${escapeHtml(r.account_name)}</td><td class="amount-num">${formatCurrency(r.balance)}</td></tr>
            `).join('');

            contentArea.innerHTML = `
                <div class="inv-table-panel">
                    <table class="financial-table">
                        <thead>
                            <tr>
                                <th>Account Classification</th>
                                <th class="amount-num">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="section-header"><td colspan="2">ASSETS</td></tr>
                            ${assetRows}
                            <tr class="total-row"><td>TOTAL ASSETS</td><td class="amount-num">${formatCurrency(data.assets.total)}</td></tr>

                            <tr class="section-header"><td colspan="2">LIABILITIES</td></tr>
                            ${liabRows}
                            <tr class="total-row"><td>TOTAL LIABILITIES</td><td class="amount-num">${formatCurrency(data.liabilities.total)}</td></tr>

                            <tr class="section-header"><td colspan="2">EQUITY</td></tr>
                            ${eqRows}
                            <tr><td>Current Period Retained Earnings (Net Profit)</td><td class="amount-num">${formatCurrency(data.retained_earnings)}</td></tr>
                            <tr class="total-row"><td>TOTAL EQUITY</td><td class="amount-num">${formatCurrency(data.equity.total)}</td></tr>

                            <tr class="total-row" style="font-size: 1rem; background: #eff6ff;">
                                <td>TOTAL LIABILITIES & EQUITY</td>
                                <td class="amount-num">${formatCurrency(data.total_liabilities_and_equity)}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div style="margin-top: 1.5rem; text-align: center;">
                    <span class="status-pill ${data.is_balanced ? 'status-balanced' : 'status-unbalanced'}" style="font-size: 0.85rem; padding: 0.5rem 1.25rem;">
                        ACCOUNTING EQUATION: ${data.is_balanced ? 'BALANCED (Assets == Liabilities + Equity)' : 'UNBALANCED DISCREPANCY'}
                    </span>
                </div>
            `;
        } else if (type === 'trial_balance') {
            setTitle('General Ledger Trial Balance', 'Verifies SUM(Debit) == SUM(Credit) across all active accounts');

            let rowsHtml = data.accounts.map(r => `
                <tr>
                    <td><strong>${escapeHtml(r.account_code)}</strong></td>
                    <td>${escapeHtml(r.account_name)}</td>
                    <td><span style="font-size: 0.72rem; font-weight: 800; text-transform: uppercase; color: #64748b;">${escapeHtml(r.account_type)}</span></td>
                    <td class="amount-num">${formatCurrency(r.debit)}</td>
                    <td class="amount-num">${formatCurrency(r.credit)}</td>
                </tr>
            `).join('');

            contentArea.innerHTML = `
                <div class="inv-table-panel">
                    <table class="financial-table">
                        <thead>
                            <tr>
                                <th>Account Code</th>
                                <th>Account Name</th>
                                <th>Type</th>
                                <th class="amount-num">Debit</th>
                                <th class="amount-num">Credit</th>
                            </tr>
                        </thead>
                        <tbody>${rowsHtml || '<tr><td colspan="5" style="text-align:center; padding: 2rem; color: #94a3b8;">No active accounts found.</td></tr>'}</tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td colspan="3">TOTAL GL TRIAL BALANCE</td>
                                <td class="amount-num">${formatCurrency(data.total_debit)}</td>
                                <td class="amount-num">${formatCurrency(data.total_credit)}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div style="margin-top: 1.5rem; text-align: center;">
                    <span class="status-pill ${data.is_balanced ? 'status-balanced' : 'status-unbalanced'}" style="font-size: 0.85rem; padding: 0.5rem 1.25rem;">
                        TRIAL BALANCE EQUALITY: ${data.is_balanced ? 'BALANCED (SUM Debit == SUM Credit)' : 'UNBALANCED'}
                    </span>
                </div>
            `;
        } else if (type === 'expired_stock') {
            setTitle('Expired Stock & Loss Valuation Report', 'Active inventory batches past expiration date with exact unit cost valuation losses');

            let rowsHtml = (data.items || []).map(r => `
                <tr>
                    <td class="col-fit"><strong>${escapeHtml(r.lot_number)}</strong></td>
                    <td class="col-fit">${escapeHtml(r.sku)}</td>
                    <td>${escapeHtml(r.product_name)}</td>
                    <td class="col-fit">${escapeHtml(r.batch_number) || '-'}</td>
                    <td class="col-fit" style="color: #dc2626; font-weight: 700;">${escapeHtml(r.expiry_date)}</td>
                    <td class="amount-num" style="color: #dc2626; font-weight: 800;">+${r.days_expired}d</td>
                    <td class="amount-num">${r.current_quantity}</td>
                    <td class="amount-num">${formatCurrency(r.unit_cost)}</td>
                    <td class="amount-num" style="color: #dc2626; font-weight: 800;">${formatCurrency(r.loss_value)}</td>
                    <td class="col-fit">${escapeHtml(r.branch_name)}</td>
                </tr>
            `).join('');

            contentArea.innerHTML = `
                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-title">Expired Lots Count</div>
                        <div class="kpi-value" style="color: #dc2626;">${data.summary ? data.summary.total_items : 0}</div>
                        <div class="kpi-sub">Batches Past Expiry</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-title">Expired Quantity</div>
                        <div class="kpi-value">${data.summary ? data.summary.total_qty : 0}</div>
                        <div class="kpi-sub">Stock Units Expired</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-title">Total Loss Valuation</div>
                        <div class="kpi-value" style="color: #dc2626;">${formatCurrency(data.summary ? data.summary.total_loss_value : 0)}</div>
                        <div class="kpi-sub">Exact Lot Cost Basis</div>
                    </div>
                </div>

                <div class="inv-table-panel">
                    <table class="financial-table">
                        <thead>
                            <tr>
                                <th class="col-fit">Lot #</th>
                                <th class="col-fit">SKU</th>
                                <th>Product Name</th>
                                <th class="col-fit">Batch #</th>
                                <th class="col-fit">Expiry Date</th>
                                <th class="amount-num">Expired By</th>
                                <th class="amount-num">Remaining Qty</th>
                                <th class="amount-num">Unit Cost</th>
                                <th class="amount-num">Loss Valuation</th>
                                <th class="col-fit">Branch</th>
                            </tr>
                        </thead>
                        <tbody>${rowsHtml || '<tr><td colspan="10" style="text-align:center; padding: 2rem; color: #16a34a; font-weight: 600;">✓ Excellent! No expired stock batches found in active inventory.</td></tr>'}</tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td colspan="6">TOTAL EXPIRED INVENTORY VALUATION LOSS</td>
                                <td class="amount-num">${data.summary ? data.summary.total_qty : 0}</td>
                                <td class="amount-num"></td>
                                <td class="amount-num" style="color: #dc2626;">${formatCurrency(data.summary ? data.summary.total_loss_value : 0)}</td>
                                <td class="col-fit"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            `;
        } else if (type === 'near_expiry') {
            setTitle('Near-Expiry Stock Alert Report', 'Batches expiring within threshold window requiring markdown promotions or supplier return');

            let rowsHtml = (data.items || []).map(r => {
                let badgeStyle = r.urgency_level === 'danger'
                    ? 'background: #fee2e2; color: #b91c1c;'
                    : (r.urgency_level === 'warning' ? 'background: #fef3c7; color: #b45309;' : 'background: #e0f2fe; color: #0369a1;');
                return `
                    <tr>
                        <td class="col-fit"><strong>${escapeHtml(r.lot_number)}</strong></td>
                        <td class="col-fit">${escapeHtml(r.sku)}</td>
                        <td>${escapeHtml(r.product_name)}</td>
                        <td class="col-fit">${escapeHtml(r.batch_number) || '-'}</td>
                        <td class="col-fit" style="font-weight: 700;">${escapeHtml(r.expiry_date)}</td>
                        <td class="amount-num" style="font-weight: 800;">${r.days_until_expiry}d left</td>
                        <td class="col-fit"><span class="status-pill" style="${badgeStyle}; font-size: 10px; text-transform: uppercase;">${escapeHtml(r.urgency_level)}</span></td>
                        <td class="amount-num">${r.current_quantity}</td>
                        <td class="amount-num">${formatCurrency(r.unit_cost)}</td>
                        <td class="amount-num" style="color: #d97706; font-weight: 800;">${formatCurrency(r.total_cost)}</td>
                        <td class="col-fit">${escapeHtml(r.branch_name)}</td>
                    </tr>
                `;
            }).join('');

            contentArea.innerHTML = `
                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-title">Expiring Batches</div>
                        <div class="kpi-value" style="color: #ea580c;">${data.summary ? data.summary.total_items : 0}</div>
                        <div class="kpi-sub">Within ${data.summary ? data.summary.window_days : 30} Days</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-title">At-Risk Quantity</div>
                        <div class="kpi-value">${data.summary ? data.summary.total_qty : 0}</div>
                        <div class="kpi-sub">Stock Units Pending FEFO Sale</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-title">At-Risk Valuation</div>
                        <div class="kpi-value" style="color: #ea580c;">${formatCurrency(data.summary ? data.summary.total_at_risk_value : 0)}</div>
                        <div class="kpi-sub">Potential Financial Exposure</div>
                    </div>
                </div>

                <div class="inv-table-panel">
                    <table class="financial-table">
                        <thead>
                            <tr>
                                <th class="col-fit">Lot #</th>
                                <th class="col-fit">SKU</th>
                                <th>Product Name</th>
                                <th class="col-fit">Batch #</th>
                                <th class="col-fit">Expiry Date</th>
                                <th class="amount-num">Time Left</th>
                                <th class="col-fit">Urgency</th>
                                <th class="amount-num">Qty</th>
                                <th class="amount-num">Unit Cost</th>
                                <th class="amount-num">At-Risk Valuation</th>
                                <th class="col-fit">Branch</th>
                            </tr>
                        </thead>
                        <tbody>${rowsHtml || '<tr><td colspan="11" style="text-align:center; padding: 2rem; color: #16a34a; font-weight: 600;">✓ No products currently within the near-expiry window.</td></tr>'}</tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td colspan="7">TOTAL AT-RISK INVENTORY VALUATION</td>
                                <td class="amount-num">${data.summary ? data.summary.total_qty : 0}</td>
                                <td class="amount-num"></td>
                                <td class="amount-num" style="color: #ea580c;">${formatCurrency(data.summary ? data.summary.total_at_risk_value : 0)}</td>
                                <td class="col-fit"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            `;
        } else if (type === 'batch_expiry_audit') {
            setTitle('Batch & Expiry Master Audit Trail', 'Complete lifecycle tracking across all inventory lots, batches, and sources');

            let rowsHtml = (data.items || []).map(r => `
                <tr>
                    <td class="col-fit"><strong>${escapeHtml(r.lot_number)}</strong></td>
                    <td class="col-fit">${escapeHtml(r.sku)}</td>
                    <td>${escapeHtml(r.product_name)}</td>
                    <td class="col-fit">${escapeHtml(r.batch_number) || '-'}</td>
                    <td class="col-fit">${escapeHtml(r.expiry_date) || '<span style="color:#94a3b8;">None</span>'}</td>
                    <td class="col-fit"><span class="status-pill status-${r.status_class}">${escapeHtml(r.status_label)}</span></td>
                    <td class="amount-num">${r.current_quantity}</td>
                    <td class="amount-num">${formatCurrency(r.unit_cost)}</td>
                    <td class="amount-num">${formatCurrency(r.total_valuation)}</td>
                    <td class="col-fit"><span style="text-transform: capitalize; font-weight: 600;">${escapeHtml(r.source_type || '-')}</span></td>
                    <td class="col-fit">${escapeHtml((r.created_at || '').substring(0, 10))}</td>
                    <td class="col-fit">${escapeHtml(r.branch_name)}</td>
                </tr>
            `).join('');

            contentArea.innerHTML = `
                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-title">Total Active Batches</div>
                        <div class="kpi-value" style="color: #7c3aed;">${data.summary ? data.summary.total_lots : 0}</div>
                        <div class="kpi-sub">Tracked Stock Lots</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-title">Audited Stock Units</div>
                        <div class="kpi-value">${data.summary ? data.summary.total_qty : 0}</div>
                        <div class="kpi-sub">Units Across All Batches</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-title">Audited Valuation</div>
                        <div class="kpi-value">${formatCurrency(data.summary ? data.summary.total_valuation : 0)}</div>
                        <div class="kpi-sub">Total Inventory Asset</div>
                    </div>
                </div>

                <div class="inv-table-panel">
                    <table class="financial-table">
                        <thead>
                            <tr>
                                <th class="col-fit">Lot #</th>
                                <th class="col-fit">SKU</th>
                                <th>Product Name</th>
                                <th class="col-fit">Batch #</th>
                                <th class="col-fit">Expiry Date</th>
                                <th class="col-fit">Status</th>
                                <th class="amount-num">Qty</th>
                                <th class="amount-num">Unit Cost</th>
                                <th class="amount-num">Valuation</th>
                                <th class="col-fit">Source</th>
                                <th class="col-fit">Received Date</th>
                                <th class="col-fit">Branch</th>
                            </tr>
                        </thead>
                        <tbody>${rowsHtml || '<tr><td colspan="12" style="text-align:center; padding: 2rem; color: #94a3b8;">No inventory lots found.</td></tr>'}</tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td colspan="6">TOTAL INVENTORY BATCH VALUATION</td>
                                <td class="amount-num">${data.summary ? data.summary.total_qty : 0}</td>
                                <td class="amount-num"></td>
                                <td class="amount-num">${formatCurrency(data.summary ? data.summary.total_valuation : 0)}</td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            `;
        } else if (type === 'expired_writeoff_history') {
            setTitle('Expired Stock Write-Off History', 'Audit trail of disposed expired inventory written off to General Ledger loss (COA 1250)');

            let rowsHtml = (data.items || []).map(r => `
                <tr>
                    <td class="col-fit">${escapeHtml((r.created_at || '').substring(0, 16))}</td>
                    <td class="col-fit">${escapeHtml(r.sku)}</td>
                    <td>${escapeHtml(r.product_name)}</td>
                    <td class="col-fit">${escapeHtml(r.batch_number) || '-'}</td>
                    <td class="col-fit" style="color: #dc2626;">${escapeHtml(r.expiry_date) || '-'}</td>
                    <td class="amount-num" style="font-weight: 700;">${r.quantity}</td>
                    <td class="amount-num">${formatCurrency(r.unit_cost)}</td>
                    <td class="amount-num" style="color: #dc2626; font-weight: 800;">${formatCurrency(r.total_loss)}</td>
                    <td class="col-fit">${escapeHtml(r.user_name || 'System')}</td>
                    <td>${escapeHtml(r.reason || '')} ${r.notes ? '<span style="color:#64748b;">(' + escapeHtml(r.notes) + ')</span>' : ''}</td>
                    <td class="col-fit">${escapeHtml(r.branch_name)}</td>
                </tr>
            `).join('');

            contentArea.innerHTML = `
                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-title">Disposal Events</div>
                        <div class="kpi-value">${data.summary ? data.summary.total_records : 0}</div>
                        <div class="kpi-sub">Write-Off Transactions</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-title">Total Disposed Units</div>
                        <div class="kpi-value">${data.summary ? data.summary.total_qty : 0}</div>
                        <div class="kpi-sub">Removed From Stock</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-title">Accumulated Loss Recognized</div>
                        <div class="kpi-value" style="color: #dc2626;">${formatCurrency(data.summary ? data.summary.total_loss_value : 0)}</div>
                        <div class="kpi-sub">Debited to COA 1250 Loss</div>
                    </div>
                </div>

                <div class="inv-table-panel">
                    <table class="financial-table">
                        <thead>
                            <tr>
                                <th class="col-fit">Write-Off Date</th>
                                <th class="col-fit">SKU</th>
                                <th>Product Name</th>
                                <th class="col-fit">Batch #</th>
                                <th class="col-fit">Expiry Date</th>
                                <th class="amount-num">Qty Disposed</th>
                                <th class="amount-num">Unit Cost</th>
                                <th class="amount-num">Total Loss</th>
                                <th class="col-fit">Authorized By</th>
                                <th>Reason & Notes</th>
                                <th class="col-fit">Branch</th>
                            </tr>
                        </thead>
                        <tbody>${rowsHtml || '<tr><td colspan="11" style="text-align:center; padding: 2rem; color: #94a3b8;">No expired stock write-off records found.</td></tr>'}</tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td colspan="5">TOTAL ACCUMULATED WRITE-OFF LOSS</td>
                                <td class="amount-num">${data.summary ? data.summary.total_qty : 0}</td>
                                <td class="amount-num"></td>
                                <td class="amount-num" style="color: #dc2626;">${formatCurrency(data.summary ? data.summary.total_loss_value : 0)}</td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            `;
        } else if (type === 'supplier_expiry_performance') {
            setTitle('Supplier Expiry Performance Analysis', 'Evaluation of received vendor goods vs expired quantities and return liabilities');

            let items = data.items || [];
            let totRec = items.reduce((s, r) => s + Number(r.total_qty_received || 0), 0);
            let totExp = items.reduce((s, r) => s + Number(r.expired_qty || 0), 0);
            let totLoss = items.reduce((s, r) => s + Number(r.total_loss_value || 0), 0);

            let rowsHtml = items.map(r => `
                <tr>
                    <td><strong>${escapeHtml(r.supplier_name)}</strong></td>
                    <td class="col-fit">${escapeHtml(r.phone || '-')}</td>
                    <td class="amount-num">${r.total_lots_received}</td>
                    <td class="amount-num">${r.total_qty_received}</td>
                    <td class="amount-num">${r.active_qty}</td>
                    <td class="amount-num" style="color: #dc2626; font-weight: 700;">${r.expired_lots_count}</td>
                    <td class="amount-num" style="color: #dc2626; font-weight: 700;">${r.expired_qty}</td>
                    <td class="amount-num" style="color: #dc2626; font-weight: 800;">${formatCurrency(r.total_loss_value)}</td>
                    <td class="amount-num" style="font-weight: 800; color: ${r.expiry_rate_pct > 5 ? '#dc2626' : (r.expiry_rate_pct > 0 ? '#d97706' : '#16a34a')};">${r.expiry_rate_pct.toFixed(2)}%</td>
                </tr>
            `).join('');

            contentArea.innerHTML = `
                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-title">Active Suppliers Audited</div>
                        <div class="kpi-value">${items.length}</div>
                        <div class="kpi-sub">Suppliers with Deliveries</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-title">Expired Supplier Units</div>
                        <div class="kpi-value" style="color: #dc2626;">${totExp}</div>
                        <div class="kpi-sub">Out of ${totRec} Received Units</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-title">Vendor Expiry Financial Loss</div>
                        <div class="kpi-value" style="color: #dc2626;">${formatCurrency(totLoss)}</div>
                        <div class="kpi-sub">Eligible for Supplier Recovery / Credit</div>
                    </div>
                </div>

                <div class="inv-table-panel">
                    <table class="financial-table">
                        <thead>
                            <tr>
                                <th>Supplier Name</th>
                                <th class="col-fit">Phone</th>
                                <th class="amount-num">Lots Recv</th>
                                <th class="amount-num">Total Qty Recv</th>
                                <th class="amount-num">Active Qty</th>
                                <th class="amount-num">Expired Lots</th>
                                <th class="amount-num">Expired Qty</th>
                                <th class="amount-num">Expired Loss</th>
                                <th class="amount-num">Expiry Rate</th>
                            </tr>
                        </thead>
                        <tbody>${rowsHtml || '<tr><td colspan="9" style="text-align:center; padding: 2rem; color: #16a34a; font-weight: 600;">✓ Excellent! Zero expired items recorded across all active suppliers.</td></tr>'}</tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td colspan="3">TOTAL SUPPLIER PERFORMANCE SUMMARY</td>
                                <td class="amount-num">${totRec}</td>
                                <td class="amount-num"></td>
                                <td class="amount-num"></td>
                                <td class="amount-num" style="color: #dc2626;">${totExp}</td>
                                <td class="amount-num" style="color: #dc2626;">${formatCurrency(totLoss)}</td>
                                <td class="amount-num">${totRec > 0 ? (totExp / totRec * 100).toFixed(2) + '%' : '0.00%'}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            `;
        }
    }

    function executeExportFormat(fmt) {
        closeExportModal();

        if (fmt === 'pdf') {
            downloadDirectPDF();
            return;
        }

        const modal = document.getElementById('expModal');
        const progressBar = document.getElementById('expProgressBar');
        const msg = document.getElementById('expModalMsg');
        
        modal.style.display = 'flex';
        progressBar.style.width = '30%';
        msg.innerText = `Generating ${fmt.toUpperCase()} export file...`;

        setTimeout(() => { progressBar.style.width = '75%'; }, 200);

        const params = getFilterParams();
        const url = `reports_api.php?action=export&report_type=${currentReportType}&format=${fmt}&${params}`;

        setTimeout(() => {
            progressBar.style.width = '100%';
            setTimeout(() => {
                modal.style.display = 'none';
                window.location.href = url;
            }, 300);
        }, 500);
    }

</script>
