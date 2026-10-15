<?php
/**
 * Curtiss POS - Suppliers View (with Right Slide-In Profile & Activity Ledger Tabs + Opening Balance System)
 */
$isDeletedView = ($viewMode === 'deleted');
$deletedCount = (int)($deletedCount ?? 0);
?>

<!-- Floating Top-Right Action Pill -->
<div id="curtiss-floating-header-right" class="no-print" style="position: fixed; top: 16px; right: 20px; z-index: 1050; display: flex; align-items: center; gap: 8px;">
    <?php if ($isDeletedView): ?>
        <a href="suppliers" class="prod-btn prod-btn-ghost">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            Active Suppliers
        </a>
    <?php else: ?>
        <a href="suppliers?view=deleted" class="prod-btn" style="color: #dc2626; background: #fef2f2; border-color: #fca5a5;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
            Trash Bin (<?= $deletedCount ?>)
        </a>
    <a href="supplier_import" class="prod-btn prod-btn-ghost">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Import CSV
    </a>
    <button type="button" class="prod-btn prod-btn-ghost" onclick="startExportCSV(event, 'supplier_export')">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
        Export CSV
    </button>
    <button type="button" class="prod-btn prod-btn-primary" onclick="openCreateSupplierModal()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
        Add New Supplier
    </button>
    <?php endif; ?>
</div>

<style>
/* ── Inventory Shared Theme Styles ── */
.prod-header {
    margin-bottom: 1.25rem;
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
    margin-bottom: 14px;
    animation: slideDown 0.2s ease;
}
@keyframes slideDown {
    from { opacity: 0; transform: translateY(-6px); }
    to   { opacity: 1; transform: translateY(0); }
}
.prod-alert-success { background: rgba(240,253,244,0.95); border: 1px solid rgba(22,163,74,0.25); color: #16a34a; }
.prod-alert-danger  { background: rgba(254,242,242,0.95); border: 1px solid rgba(220,38,38,0.25); color: #dc2626; }

/* Main Inventory Table Panel */
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
}
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
}
.prod-table thead th.col-right { text-align: right; }

.prod-table tbody tr {
    height: 48px;
    min-height: 48px;
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.12s ease;
    cursor: pointer;
}
.prod-table tbody tr:nth-child(even) { background: rgba(248, 250, 252, 0.57); }
.prod-table tbody tr:nth-child(odd)  { background: #ffffff; }
.prod-table tbody tr:hover            { background: #f1f5f9 !important; }

.prod-table td,
.prod-table tbody td {
    height: 48px;
    padding: 0 14px;
    font-size: 12.5px;
    color: #1e293b;
    vertical-align: middle;
    box-sizing: border-box;
}
.sup-name-cell {
    font-weight: 700;
    color: #0f172a;
    font-size: 13px;
}
.sup-code-badge {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 2px 7px;
    font-family: monospace;
    font-size: 11.5px;
    font-weight: 600;
}

/* Status Badges */
.prod-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    height: 28px;
    min-height: 28px;
    line-height: 26px;
    padding: 0 12px;
    border-radius: 999px;
    box-sizing: border-box;
    font-size: 11px;
    font-weight: 700;
}
.prod-badge-active   { background: #dcfce7; color: #15803d; border: 1px solid rgba(22,163,74,0.2); }
.prod-badge-inactive { background: #fee2e2; color: #b91c1c; border: 1px solid rgba(220,38,38,0.2); }
.prod-badge .dot {
    width: 5px; height: 5px; border-radius: 50%; display: inline-block;
}
.prod-badge-active   .dot { background: #16a34a; }
.prod-badge-inactive .dot { background: #dc2626; }

/* Action Buttons */
.prod-act-btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    height: 28px;
    min-height: 28px;
    line-height: 26px;
    padding: 0 12px;
    border-radius: 999px;
    box-sizing: border-box;
    font-size: 11px;
    font-weight: 700;
    border: 1px solid transparent;
    cursor: pointer;
    transition: all 0.15s ease;
    font-family: inherit;
    text-decoration: none;
}
.prod-act-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}
.prod-act-edit {
    background: #f1f5f9;
    border-color: #cbd5e1;
    color: #334155;
}
.prod-act-edit:hover:not(:disabled) {
    background: #e2e8f0;
    color: #0f172a;
}
.prod-act-toggle-deactivate {
    background: #fef2f2;
    border-color: #fca5a5;
    color: #dc2626;
}
.prod-act-toggle-deactivate:hover:not(:disabled) {
    background: #fee2e2;
}
.prod-act-toggle-activate {
    background: #f0fdf4;
    border-color: #86efac;
    color: #16a34a;
}
.prod-act-toggle-activate:hover:not(:disabled) {
    background: #dcfce7;
}
.prod-act-delete {
    background: #fef2f2;
    border-color: #fca5a5;
    color: #dc2626;
}
.prod-act-delete:hover:not(:disabled) {
    background: #fee2e2;
}
.prod-act-restore {
    background: #f0fdf4;
    border-color: #86efac;
    color: #16a34a;
}
.prod-act-restore:hover:not(:disabled) {
    background: #dcfce7;
}

/* Glassmorphism Modal */
.curtiss-modal-backdrop {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.4);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    z-index: 9600;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}
.curtiss-modal-backdrop.active {
    display: flex;
}
.curtiss-modal-card {
    background: rgba(255, 255, 255, 0.98);
    width: 100%;
    max-width: 520px;
    max-height: 90vh;
    overflow-y: auto;
    box-sizing: border-box;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.15);
    border: 1px solid rgba(226, 232, 240, 0.9);
    animation: modalSlideUp 0.22s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
@keyframes modalSlideUp {
    from { opacity: 0; transform: translateY(12px) scale(0.98); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}

@media (max-width: 768px) {
    .curtiss-modal-card {
        padding: 16px;
        max-width: 95vw;
    }
}

/* ── Supplier Details Panel (Right Slide-In) ── */
.sdp-backdrop {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    z-index: 9500;
}
.sdp-backdrop.active {
    display: block;
}

.sdp-panel {
    position: fixed;
    top: 0;
    right: 0;
    bottom: 0;
    width: 100%;
    max-width: 760px;
    background: #ffffff;
    border-left: 1px solid #e2e8f0;
    box-shadow: -10px 0 30px rgba(0, 0, 0, 0.12);
    z-index: 9501;
    display: flex;
    flex-direction: column;
    transform: translateX(100%);
    transition: transform 0.28s cubic-bezier(0.16, 1, 0.3, 1);
}
.sdp-panel.active {
    transform: translateX(0);
}

@media (max-width: 768px) {
    .sdp-panel {
        max-width: 100vw;
    }
}

/* ── Ledger Table Styling (Design Matching Reference Image) ── */
.ledger-table-panel {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
}
.ledger-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
}
.ledger-table thead {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}
.ledger-table th {
    position: sticky;
    top: 0;
    z-index: 10;
    background: #f8fafc;
    padding: 10px 12px;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #64748b;
    white-space: nowrap;
    border-bottom: 1px solid #e2e8f0;
}
.ledger-table td {
    padding: 10px 12px;
    border-bottom: 1px solid #f1f5f9;
    color: #1e293b;
    vertical-align: middle;
}
.ledger-table tbody tr:nth-child(even) {
    background: rgba(248, 250, 252, 0.57);
}
.ledger-table tbody tr:nth-child(odd) {
    background: #ffffff;
}
.ledger-table tbody tr:hover {
    background: #f1f5f9 !important;
}
.ledger-table tr:last-child td {
    border-bottom: none;
}
.ledger-col-date {
    color: #64748b;
    font-size: 11.5px;
    font-weight: 600;
    white-space: nowrap;
}
.ledger-col-desc-title {
    font-weight: 800;
    color: #0f172a;
}
.ledger-col-desc-sub {
    font-size: 11.5px;
    color: #64748b;
    margin-left: 4px;
}
.ledger-col-debit {
    font-weight: 700;
    font-family: monospace;
    font-size: 12px;
    color: #0f172a;
    text-align: right;
    white-space: nowrap;
}
.ledger-col-credit {
    font-weight: 700;
    font-family: monospace;
    font-size: 12px;
    color: #16a34a;
    text-align: right;
    white-space: nowrap;
}
.ledger-col-running {
    font-weight: 800;
    font-family: monospace;
    font-size: 12px;
    color: #dc2626;
    text-align: right;
    white-space: nowrap;
}
.ledger-col-empty {
    color: #cbd5e1;
    text-align: right;
    font-family: monospace;
}
.ledger-filter-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    cursor: pointer;
    transition: all 0.15s ease;
    user-select: none;
    font-family: inherit;
}
.ledger-filter-pill:hover {
    background: #f1f5f9;
    color: #0f172a;
    border-color: #94a3b8;
}
.ledger-filter-pill.active {
    background: #0f172a;
    color: #ffffff;
    border-color: #0f172a;
}

.sdp-topbar {
    padding: 16px 22px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #f8fafc;
}
.sdp-topbar-title {
    font-size: 1.05rem;
    font-weight: 800;
    color: #0f172a;
}
.sdp-close-btn {
    background: none;
    border: none;
    font-size: 1.1rem;
    color: #64748b;
    cursor: pointer;
    padding: 4px;
    border-radius: 8px;
}
.sdp-close-btn:hover {
    background: #e2e8f0;
    color: #0f172a;
}

/* SDP Tab Navigation Bar */
.sdp-nav-tabs {
    display: flex;
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
    padding: 0 16px;
    gap: 4px;
}
.sdp-tab-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 11px 16px;
    font-size: 0.8rem;
    font-weight: 700;
    color: #64748b;
    border: none;
    border-bottom: 2px solid transparent;
    background: none;
    cursor: pointer;
    transition: all 0.15s ease;
    font-family: inherit;
}
.sdp-tab-btn:hover {
    color: #0f172a;
}
.sdp-tab-btn.active {
    color: #0f172a;
    border-bottom-color: #0f172a;
    background: #ffffff;
}

.sdp-body {
    padding: 22px;
    overflow-y: auto;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.sdp-tab-pane {
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.sdp-actions {
    padding: 16px 22px;
    border-top: 1px solid #e2e8f0;
    background: #f8fafc;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.sdp-actions-title {
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
    color: #64748b;
    letter-spacing: 0.05em;
}
.sdp-action-row {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.sdp-section-title {
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #64748b;
    margin-bottom: 8px;
}
.sdp-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 14px;
}

/* Activity Ledger Item Styling */
.act-item-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 12px 14px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
    transition: transform 0.12s ease;
}
.act-item-card:hover {
    transform: translateY(-1px);
}
.act-badge {
    display: inline-flex;
    align-items: center;
    padding: 2px 7px;
    border-radius: 999px;
    font-size: 10px;
    font-weight: 800;
    font-family: monospace;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}
.act-badge-po { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
.act-badge-grn { background: #f5f3ff; color: #6d28d9; border: 1px solid #ddd6fe; }
.act-badge-payment { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
.act-badge-opening_balance { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
.act-badge-return { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }

/* ── Export Progress Modal ── */
.exp-backdrop {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.5);
    backdrop-filter: blur(8px);
    z-index: 9600;
    transition: opacity 0.2s ease;
}
.exp-backdrop.active { display: block; }
.exp-modal {
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(0.96);
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(16px);
    border: 1px solid rgba(226, 232, 240, 0.9);
    box-shadow: 0 20px 50px -10px rgba(0,0,0,0.15);
    border-radius: 20px;
    width: 90%;
    max-width: 420px;
    padding: 28px;
    z-index: 9601;
    transition: all 0.2s ease;
}
.exp-modal.active { display: block; transform: translate(-50%, -50%) scale(1); }
.exp-header { display: flex; align-items: center; gap: 14px; margin-bottom: 20px; }
.exp-icon-box {
    width: 44px; height: 44px; border-radius: 14px;
    background: #f1f5f9; display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.exp-title { font-size: 1.1rem; font-weight: 800; color: #0f172a; margin: 0; }
.exp-subtitle { font-size: 0.8rem; color: #64748b; margin-top: 2px; }
.exp-progress-wrapper { margin-bottom: 12px; }
.exp-progress-bar-bg { width: 100%; height: 8px; background: #e2e8f0; border-radius: 999px; overflow: hidden; }
.exp-progress-bar-fill { width: 0%; height: 100%; background: #0f172a; border-radius: 999px; transition: width 0.18s ease; }
.exp-status-row { display: flex; justify-content: space-between; font-size: 0.78rem; font-weight: 700; color: #475569; margin-top: 6px; }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
.exp-spinner { animation: spin 1s linear infinite; }
</style>

<div style="width: 100%; max-width: 100%; height: calc(100vh - 110px); display: flex; flex-direction: column; box-sizing: border-box; overflow: hidden; padding-bottom: 8px;">
    <!-- Page Header -->
    <div class="prod-header" style="flex-shrink: 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 0.85rem;">
        <div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
                <h1 class="prod-title"><?= $isDeletedView ? 'Suppliers (Trash Bin)' : 'Supplier Master' ?></h1>
            </div>
        </div>
    </div>

    <!-- Alert Notices -->
    <?php if (!empty($incompleteCount) && $incompleteCount > 0): ?>
        <div class="prod-alert" style="background: #fffbeb; border: 1px solid #fde68a; color: #b45309;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            <span><strong><?= (int)$incompleteCount ?> Quick-Created Supplier(s) Need Profile Review:</strong> Missing contact details (phone / address). Review and complete their profiles below.</span>
        </div>
    <?php endif; ?>

    <?php if (!empty($successMsg)): ?>
        <div class="prod-alert prod-alert-success">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            <?= View::escape($successMsg) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errorMsg)): ?>
        <div class="prod-alert prod-alert-danger">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <?= View::escape($errorMsg) ?>
        </div>
    <?php endif; ?>

    <!-- Main Inventory-Themed Table Panel -->
    <div class="prod-table-panel" style="flex: 1; min-height: 0; display: flex; flex-direction: column; overflow: hidden;">
        <div class="prod-table-scroll" style="overflow-y: auto; flex: 1;">
            <table class="prod-table" id="supplierTable">
                <thead>
                    <tr>
                        <th style="width: 110px; text-align: left;">Code</th>
                        <th style="text-align: left;">Supplier Name</th>
                        <th style="width: 130px; text-align: left;">Phone</th>
                        <th style="width: 130px; text-align: left;">WhatsApp</th>
                        <th class="col-right" style="text-align: right; width: 140px;">Payable Due (LKR)</th>
                        <th style="width: 130px; text-align: left;">Status</th>
                        <th class="col-right" style="text-align: right; width: 180px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($suppliers)): ?>
                        <tr class="prod-empty"><td colspan="7" style="text-align: center; color: #94a3b8; padding: 32px 16px;">No suppliers found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($suppliers as $s): 
                            $rawOut = (float)($s['total_outstanding'] ?? 0);
                            $fmtOut = number_format($rawOut, 2);
                            $searchData = strtolower(implode(' ', array_filter([
                                $s['supplier_code'] ?? 'SUP-'.sprintf('%04d', $s['id']),
                                $s['name'] ?? '',
                                $s['phone'] ?? '',
                                $s['whatsapp'] ?? '',
                                $s['address'] ?? '',
                                $s['bank_name'] ?? '',
                                $s['bank_account_no'] ?? '',
                                $s['bank_account_name'] ?? '',
                                $s['bank_branch'] ?? '',
                                $s['status'] ?? '',
                                !empty($s['needs_review']) ? 'needs review quick-created' : '',
                                (string)$rawOut,
                                (string)round($rawOut),
                                $fmtOut,
                                'lkr ' . $fmtOut,
                                'rs. ' . $fmtOut,
                                'rs ' . $fmtOut,
                                'lkr ' . (string)$rawOut,
                                'rs. ' . (string)$rawOut
                            ])));
                        ?>
                            <tr onclick='openSupplierPanel(<?= json_encode($s, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' data-search="<?= View::escape($searchData) ?>">
                                <td style="text-align: left;"><span class="sup-code-badge"><?= View::escape($s['supplier_code'] ?? 'SUP-'.sprintf('%04d', $s['id'])) ?></span></td>
                                <td class="sup-name-cell" style="text-align: left;"><?= View::escape($s['name']) ?></td>
                                <td style="text-align: left; color: #475569; font-size: 12px; font-weight: 500;"><?= View::escape($s['phone'] ?? 'N/A') ?></td>
                                <td style="text-align: left; color: #475569; font-size: 12px; font-weight: 500;"><?= View::escape($s['whatsapp'] ?? 'N/A') ?></td>
                                <td class="col-right" style="text-align: right; font-weight: 700; font-family: monospace; font-size: 12.5px; color: <?= ($rawOut > 0) ? '#dc2626' : '#16a34a' ?>;">
                                    <?= $fmtOut ?>
                                </td>
                                <td style="text-align: left;">
                                    <?php if (!empty($s['needs_review'])): ?>
                                        <span class="prod-badge" style="background:#fffbeb; color:#b45309; border:1px solid #fde68a;" title="Quick-created: missing contact details"><span class="dot" style="background:#b45309;"></span>Needs Review</span>
                                    <?php elseif ($s['status'] === 'active'): ?>
                                        <span class="prod-badge prod-badge-active" onclick="event.stopPropagation(); submitToggle(<?= $s['id'] ?>);" style="cursor: pointer;" title="Click to toggle status"><span class="dot"></span>Active</span>
                                    <?php else: ?>
                                        <span class="prod-badge prod-badge-inactive" onclick="event.stopPropagation(); submitToggle(<?= $s['id'] ?>);" style="cursor: pointer;" title="Click to toggle status"><span class="dot"></span>Deactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="col-right" onclick="event.stopPropagation()" style="text-align: right; white-space: nowrap;">
                                    <?php if ($isDeletedView): ?>
                                        <button type="button" class="prod-act-btn prod-act-restore" onclick="submitRestore(<?= $s['id'] ?>)">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><polyline points="3 3 3 8 8 8"/></svg>
                                            Restore
                                        </button>
                                    <?php else: ?>
                                        <div style="display: inline-flex; align-items: center; gap: 5px;">
                                            <?php if ($rawOut > 0.001): ?>
                                                <button type="button" class="prod-act-btn" style="background: #f0fdf4; color: #166534; border-color: #bbf7d0; font-weight: 700;" onclick='event.stopPropagation(); openPaySupplierModal(<?= json_encode($s, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' title="Make Payment to Supplier">
                                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                                                    Pay
                                                </button>
                                            <?php endif; ?>
                                            <button type="button" class="prod-act-btn prod-act-edit" onclick='openEditSupplierModal(<?= json_encode($s, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' title="Edit Supplier">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                                Edit
                                            </button>
                                            <button type="button" class="prod-act-btn prod-act-delete" onclick="submitDelete(<?= $s['id'] ?>)" title="Delete Supplier">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                                                Delete
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Table Pagination Footer -->
        <div class="prod-table-footer" style="padding: 12px 18px; border-top: 1px solid #e2e8f0; background: #ffffff; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
            <div style="font-size: 12px; font-weight: 600; color: #64748b;" id="paginationInfo">
                Showing <span id="pagStart">1</span> to <span id="pagEnd">10</span> of <span id="pagTotal">0</span> entries
            </div>

            <div style="display: flex; align-items: center; gap: 14px;">
                <div style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: #64748b; font-weight: 600;">
                    <span>Rows per page:</span>
                    <select id="pagPageSize" onchange="changePageSize()" style="height: 30px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 8px; font-size: 12px; color: #0f172a; background: #fff; outline: none; font-weight: 600; cursor: pointer;">
                        <option value="25" selected>25</option>
                        <option value="50">50</option>
                        <option value="75">75</option>
                        <option value="100">100</option>
                        <option value="500">500</option>
                    </select>
                </div>

                <div style="display: flex; align-items: center; gap: 4px;" id="pagNavBtns">
                    <!-- Dynamic page buttons -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════
     SUPPLIER DETAILS PANEL (Right Slide-In Drawer with 2 Tabs)
     ═══════════════════════════════════════════════════ -->
<div class="sdp-backdrop" id="sdpBackdrop" onclick="closeSupplierPanel()"></div>

<div class="sdp-panel" id="sdpPanel" role="dialog" aria-modal="true" aria-label="Supplier Profile & Activity Ledger">
    <div class="sdp-topbar">
        <span class="sdp-topbar-title">Supplier Profile</span>
        <button class="sdp-close-btn" onclick="closeSupplierPanel()" aria-label="Close">✕</button>
    </div>

    <!-- Two Tabs Bar -->
    <div class="sdp-nav-tabs">
        <button type="button" id="tabBtnProfile" onclick="switchSdpTab('profile')" class="sdp-tab-btn active">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Supplier Profile
        </button>
        <button type="button" id="tabBtnActivity" onclick="switchSdpTab('activity')" class="sdp-tab-btn">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            Activity Ledger <span id="sdpActivityCountBadge" style="background: #f1f5f9; color: #475569; padding: 2px 6px; border-radius: 999px; font-size: 10px; font-weight: 800;">0</span>
        </button>
    </div>

    <!-- Tab 1: Profile Pane -->
    <div class="sdp-body sdp-tab-pane" id="sdpTabProfile">
        <!-- Content injected dynamically via JS -->
    </div>

    <!-- Tab 2: Activity Ledger Pane -->
    <div class="sdp-body sdp-tab-pane" id="sdpTabActivity" style="display: none;">
        <!-- Activity items injected dynamically via JS -->
    </div>

    <div class="sdp-actions" id="sdpActions">
        <div class="sdp-actions-title">Supplier Actions</div>
        <div class="sdp-action-row" id="sdpActionRow">
            <!-- Buttons injected dynamically via JS -->
        </div>
    </div>
</div>

<!-- Modal for Create/Edit Supplier -->
<div id="supModal" class="curtiss-modal-backdrop">
    <div class="curtiss-modal-card">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; padding-bottom: 10px; border-bottom: 1px solid #f1f5f9;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
                <h3 id="supModalTitle" style="font-size: 1.1rem; font-weight: 800; color: #0f172a; margin: 0;">Add New Supplier</h3>
            </div>
            <button type="button" onclick="closeSupplierModal()" style="background: none; border: none; color: #94a3b8; cursor: pointer; padding: 4px;">✕</button>
        </div>

        <form method="POST" action="suppliers" onsubmit="handleFormSubmit(this)">
            <input type="hidden" name="action" id="supAction" value="create">
            <input type="hidden" name="id" id="supId" value="">

            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.05em;">Supplier Name <span style="color: #ef4444;">*</span></label>
                <input type="text" name="name" id="supName" style="width: 100%; height: 38px; border: 1px solid #cbd5e1; border-radius: 10px; padding: 0 12px; font-size: 13px; color: #0f172a; outline: none; box-sizing: border-box;" placeholder="e.g. Atlas Axillia Co Ltd" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.05em;">Phone</label>
                    <input type="text" name="phone" id="supPhone" style="width: 100%; height: 38px; border: 1px solid #cbd5e1; border-radius: 10px; padding: 0 12px; font-size: 13px; color: #0f172a; outline: none; box-sizing: border-box;" placeholder="+94 11 269 1111">
                </div>
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.05em;">WhatsApp</label>
                    <input type="text" name="whatsapp" id="supWhatsapp" style="width: 100%; height: 38px; border: 1px solid #cbd5e1; border-radius: 10px; padding: 0 12px; font-size: 13px; color: #0f172a; outline: none; box-sizing: border-box;" placeholder="+94 77 123 4567">
                </div>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.05em;">Address</label>
                <textarea name="address" id="supAddress" rows="2" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 10px; padding: 8px 12px; font-size: 13px; color: #0f172a; outline: none; box-sizing: border-box; font-family: inherit;" placeholder="Full address"></textarea>
            </div>

            <!-- Bank Account Details Section -->
            <div style="margin-bottom: 20px; padding: 12px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;">
                <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 10px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#475569" stroke-width="2.5"><rect x="2" y="4" width="20" height="16" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                    <span style="font-size: 11px; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.06em;">Bank Account Details</span>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                    <div>
                        <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 4px; text-transform: uppercase;">Account Name</label>
                        <input type="text" name="bank_account_name" id="supBankAccountName" style="width: 100%; height: 36px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 10px; font-size: 12.5px; color: #0f172a; outline: none; box-sizing: border-box; background: #ffffff;" placeholder="e.g. Atlas Axillia PLC">
                    </div>
                    <div>
                        <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 4px; text-transform: uppercase;">Bank Name</label>
                        <input type="text" name="bank_name" id="supBankName" style="width: 100%; height: 36px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 10px; font-size: 12.5px; color: #0f172a; outline: none; box-sizing: border-box; background: #ffffff;" placeholder="e.g. Commercial Bank">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div>
                        <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 4px; text-transform: uppercase;">Account Number</label>
                        <input type="text" name="bank_account_no" id="supBankAccountNo" style="width: 100%; height: 36px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 10px; font-size: 12.5px; font-family: monospace; font-weight: 700; color: #0f172a; outline: none; box-sizing: border-box; background: #ffffff;" placeholder="e.g. 1000234567">
                    </div>
                    <div>
                        <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 4px; text-transform: uppercase;">Branch</label>
                        <input type="text" name="bank_branch" id="supBankBranch" style="width: 100%; height: 36px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 10px; font-size: 12.5px; color: #0f172a; outline: none; box-sizing: border-box; background: #ffffff;" placeholder="e.g. Kollupitiya">
                    </div>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 14px; border-top: 1px solid #f1f5f9;">
                <button type="button" class="prod-btn prod-btn-ghost" onclick="closeSupplierModal()">Cancel</button>
                <button type="submit" class="prod-btn prod-btn-primary">Save Supplier</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal for Setting Supplier Opening Balance -->
<div id="obModal" class="curtiss-modal-backdrop">
    <div class="curtiss-modal-card">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; padding-bottom: 10px; border-bottom: 1px solid #f1f5f9;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
                <h3 style="font-size: 1.1rem; font-weight: 800; color: #0f172a; margin: 0;">Set Supplier Opening Balance</h3>
            </div>
            <button type="button" onclick="closeObModal()" style="background: none; border: none; color: #94a3b8; cursor: pointer; padding: 4px;">✕</button>
        </div>

        <form method="POST" action="suppliers" onsubmit="handleFormSubmit(this)">
            <input type="hidden" name="action" value="post_opening_balance">
            <input type="hidden" name="supplier_id" id="obSupplierId" value="">

            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; margin-bottom: 14px;">
                <div style="font-size: 0.72rem; font-weight: 800; color: #64748b; text-transform: uppercase;">Target Supplier</div>
                <div id="obSupplierName" style="font-size: 0.95rem; font-weight: 800; color: #0f172a; margin-top: 2px;"></div>
            </div>

            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.05em;">Opening Balance Amount (LKR) <span style="color: #ef4444;">*</span></label>
                <input type="number" step="0.01" min="0.01" name="amount" id="obAmount" style="width: 100%; height: 38px; border: 1px solid #cbd5e1; border-radius: 10px; padding: 0 12px; font-size: 14px; font-weight: 700; color: #0f172a; outline: none; box-sizing: border-box;" placeholder="e.g. 15000.00" required>
            </div>

            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.05em;">Balance Type <span style="color: #ef4444;">*</span></label>
                <select name="balance_type" id="obBalanceType" style="width: 100%; height: 38px; border: 1px solid #cbd5e1; border-radius: 10px; padding: 0 12px; font-size: 13px; font-weight: 700; color: #0f172a; outline: none; box-sizing: border-box;" required>
                    <option value="owe_supplier">We Owe Supplier (Payable — Dr 3010 Equity / Cr 2020 Payables)</option>
                    <option value="supplier_owes_us">Supplier Owes Us / Credit (Receivable — Dr 1045 Credit / Cr 3010 Equity)</option>
                </select>
            </div>

            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.05em;">Opening Balance Date</label>
                <input type="date" name="entry_date" id="obDate" value="<?= date('Y-m-d') ?>" style="width: 100%; height: 38px; border: 1px solid #cbd5e1; border-radius: 10px; padding: 0 12px; font-size: 13px; color: #0f172a; outline: none; box-sizing: border-box;">
            </div>

            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.05em;">Reference (Optional)</label>
                <input type="text" name="reference_no" id="obRef" style="width: 100%; height: 38px; border: 1px solid #cbd5e1; border-radius: 10px; padding: 0 12px; font-size: 13px; color: #0f172a; outline: none; box-sizing: border-box;" placeholder="e.g. Legacy migration ref #001">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.05em;">Notes / Remarks</label>
                <textarea name="notes" id="obNotes" rows="2" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 10px; padding: 8px 12px; font-size: 13px; color: #0f172a; outline: none; box-sizing: border-box; font-family: inherit;" placeholder="Notes..."></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 14px; border-top: 1px solid #f1f5f9;">
                <button type="button" class="prod-btn prod-btn-ghost" onclick="closeObModal()">Cancel</button>
                <button type="submit" class="prod-btn prod-btn-primary">Post Opening Balance</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal for Reversing Supplier Opening Balance -->
<div id="reverseObModal" class="curtiss-modal-backdrop">
    <div class="curtiss-modal-card">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; padding-bottom: 10px; border-bottom: 1px solid #f1f5f9;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #dc2626;"></div>
                <h3 style="font-size: 1.1rem; font-weight: 800; color: #dc2626; margin: 0;">Reverse Opening Balance</h3>
            </div>
            <button type="button" onclick="closeReverseObModal()" style="background: none; border: none; color: #94a3b8; cursor: pointer; padding: 4px;">✕</button>
        </div>

        <form method="POST" action="suppliers" onsubmit="handleFormSubmit(this)">
            <input type="hidden" name="action" value="reverse_opening_balance">
            <input type="hidden" name="opening_balance_id" id="revObId" value="">

            <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: 12px; margin-bottom: 14px;">
                <div style="font-size: 0.72rem; font-weight: 800; color: #991b1b; text-transform: uppercase;">Active Opening Balance</div>
                <div id="revObSummaryText" style="font-size: 0.88rem; font-weight: 700; color: #7f1d1d; margin-top: 2px;"></div>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.05em;">Reversal Reason / Audit Note <span style="color: #ef4444;">*</span></label>
                <textarea name="reversal_reason" id="revReason" rows="3" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 10px; padding: 8px 12px; font-size: 13px; color: #0f172a; outline: none; box-sizing: border-box; font-family: inherit;" placeholder="Reason for correcting or reversing this posted balance..." required></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 14px; border-top: 1px solid #f1f5f9;">
                <button type="button" class="prod-btn prod-btn-ghost" onclick="closeReverseObModal()">Cancel</button>
                <button type="submit" class="prod-btn" style="background: #dc2626; color: #ffffff; border: none; padding: 8px 16px; font-size: 12px; font-weight: 700; border-radius: 999px; cursor: pointer;">Confirm Reversal</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal for Supplier Payment Collection -->
<div id="paySupplierModal" class="curtiss-modal-backdrop">
    <div class="curtiss-modal-card" style="max-width: 650px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; padding-bottom: 10px; border-bottom: 1px solid #f1f5f9;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #16a34a;"></div>
                <h3 style="font-size: 1.1rem; font-weight: 800; color: #0f172a; margin: 0;">Make Supplier Payment</h3>
            </div>
            <button type="button" onclick="closePaySupplierModal()" style="background: none; border: none; color: #94a3b8; cursor: pointer; padding: 4px;">✕</button>
        </div>

        <form method="POST" action="suppliers" onsubmit="handleFormSubmit(this)" id="paySupplierForm">
            <input type="hidden" name="action" value="process_supplier_payment">
            <input type="hidden" name="supplier_id" id="paySupplierId" value="">

            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 0.72rem; font-weight: 800; color: #64748b; text-transform: uppercase;">Target Supplier</div>
                    <div id="paySupplierName" style="font-size: 1.05rem; font-weight: 800; color: #0f172a; margin-top: 2px;"></div>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 0.72rem; font-weight: 800; color: #991b1b; text-transform: uppercase;">Total Outstanding</div>
                    <div id="paySupplierOutstanding" style="font-size: 1.15rem; font-weight: 800; font-family: monospace; color: #dc2626;">LKR 0.00</div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.05em;">Payment Method <span style="color: #ef4444;">*</span></label>
                    <select name="payment_method" id="payMethodSelect" onchange="toggleSupplierPayFields()" style="width: 100%; height: 38px; border: 1px solid #cbd5e1; border-radius: 10px; padding: 0 12px; font-size: 13px; font-weight: 700; color: #0f172a; outline: none; box-sizing: border-box;" required>
                        <option value="Cash Drawer">Cash Drawer (POS Register)</option>
                        <option value="Cash in Hand" selected>Cash in Hand (Main Safe)</option>
                        <option value="Petty Cash">Petty Cash Account</option>
                        <?php if (!empty($bankAccounts)): ?>
                            <?php foreach ($bankAccounts as $ba): ?>
                                <option value="Bank:<?= $ba['id'] ?>">Bank Transfer: <?= View::escape($ba['bank_name']) ?> - <?= View::escape($ba['account_name']) ?> (<?= View::escape($ba['account_number']) ?>)</option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <option value="Cheque">Cheque Issued</option>
                    </select>
                </div>

                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.05em;">Payment Amount (LKR) <span style="color: #ef4444;">*</span></label>
                    <input type="number" step="0.01" min="0.01" name="amount" id="payAmountInput" style="width: 100%; height: 38px; border: 1px solid #cbd5e1; border-radius: 10px; padding: 0 12px; font-size: 14px; font-weight: 800; font-family: monospace; color: #166534; outline: none; box-sizing: border-box;" placeholder="e.g. 25000.00" required>
                </div>
            </div>

            <!-- Dynamic Bank Account Group -->
            <div id="payBankGroup" style="margin-bottom: 14px; display: none;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.05em;">Source Bank Account <span style="color: #ef4444;">*</span></label>
                <select name="bank_account_id" id="payBankAccountSelect" style="width: 100%; height: 38px; border: 1px solid #cbd5e1; border-radius: 10px; padding: 0 12px; font-size: 13px; font-weight: 700; color: #0f172a; outline: none; box-sizing: border-box;">
                    <?php if (!empty($bankAccounts)): ?>
                        <?php foreach ($bankAccounts as $ba): ?>
                            <option value="<?= $ba['id'] ?>"><?= View::escape($ba['bank_name']) ?> - <?= View::escape($ba['account_name']) ?> (<?= View::escape($ba['account_number']) ?>)</option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="">No Active Bank Accounts Found</option>
                    <?php endif; ?>
                </select>
            </div>

            <!-- Dynamic Cheque Details Group -->
            <div id="payChequeGroup" style="display: none; background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; padding: 12px; margin-bottom: 14px;">
                <div style="font-size: 0.75rem; font-weight: 800; color: #b45309; text-transform: uppercase; margin-bottom: 8px;">Cheque Issued Details</div>
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
                    <div>
                        <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 4px;">Cheque No *</label>
                        <input type="text" name="cheque[cheque_number]" id="payChequeNo" style="width: 100%; height: 34px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 10px; font-size: 12px; color: #0f172a; outline: none; box-sizing: border-box;" placeholder="e.g. CHQ-99102">
                    </div>
                    <div>
                        <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 4px;">Cheque Date *</label>
                        <input type="date" name="cheque[cheque_date]" id="payChequeDate" value="<?= date('Y-m-d') ?>" style="width: 100%; height: 34px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 10px; font-size: 12px; color: #0f172a; outline: none; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 4px;">Payer Bank *</label>
                        <input type="text" name="cheque[bank_name]" id="payChequeBank" style="width: 100%; height: 34px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 10px; font-size: 12px; color: #0f172a; outline: none; box-sizing: border-box;" placeholder="e.g. Commercial Bank">
                    </div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.05em;">Payment Date</label>
                    <input type="date" name="payment_date" id="payDateInput" value="<?= date('Y-m-d') ?>" style="width: 100%; height: 38px; border: 1px solid #cbd5e1; border-radius: 10px; padding: 0 12px; font-size: 13px; color: #0f172a; outline: none; box-sizing: border-box;">
                </div>

                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.05em;">Ref / Transaction No</label>
                    <input type="text" name="reference_no" id="payRefInput" style="width: 100%; height: 38px; border: 1px solid #cbd5e1; border-radius: 10px; padding: 0 12px; font-size: 13px; color: #0f172a; outline: none; box-sizing: border-box;" placeholder="e.g. TRX-889102">
                </div>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.05em;">Notes / Remarks</label>
                <textarea name="notes" id="payNotesInput" rows="2" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 10px; padding: 8px 12px; font-size: 13px; color: #0f172a; outline: none; box-sizing: border-box; font-family: inherit;" placeholder="Optional payment notes..."></textarea>
            </div>

            <!-- Approved Unpaid GRNs Summary List -->
            <div style="margin-bottom: 18px;">
                <label style="display: block; font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 6px;">Unpaid Approved GRNs (FIFO Allocation)</label>
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; max-height: 140px; overflow-y: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.78rem;">
                        <thead>
                            <tr style="border-bottom: 1px solid #cbd5e1; text-align: left; color: #64748b; font-size: 0.68rem; text-transform: uppercase;">
                                <th style="padding: 6px 10px;">GRN No</th>
                                <th style="padding: 6px 10px;">Invoice No</th>
                                <th style="padding: 6px 10px;">Date</th>
                                <th style="padding: 6px 10px; text-align: right;">Due Amount</th>
                            </tr>
                        </thead>
                        <tbody id="payModalGrnsTbody">
                            <tr>
                                <td colspan="4" style="text-align: center; color: #94a3b8; padding: 1.5rem;">Loading approved GRNs...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 14px; border-top: 1px solid #f1f5f9;">
                <button type="button" class="prod-btn prod-btn-ghost" onclick="closePaySupplierModal()">Cancel</button>
                <button type="submit" class="prod-btn" style="background: #166534; color: #ffffff; border: none; padding: 8px 18px; font-size: 12.5px; font-weight: 700; border-radius: 999px; cursor: pointer;">
                    💳 Process Payment
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Export Progress Modal Popup -->
<div id="expBackdrop" class="exp-backdrop"></div>
<div id="expModal" class="exp-modal">
    <div class="exp-header">
        <div id="expIconBox" class="exp-icon-box">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0f172a" stroke-width="2.5" class="exp-spinner"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
        </div>
        <div>
            <h3 id="expTitle" class="exp-title">Exporting Suppliers</h3>
            <div id="expSubtitle" class="exp-subtitle">Preparing supplier records...</div>
        </div>
    </div>
    <div class="exp-progress-wrapper">
        <div class="exp-progress-bar-bg">
            <div id="expProgressBar" class="exp-progress-bar-fill"></div>
        </div>
        <div class="exp-status-row">
            <span>Progress</span>
            <span id="expStatusText">0%</span>
        </div>
    </div>
    <div style="display: flex; justify-content: flex-end; margin-top: 14px;">
        <button type="button" id="expCloseBtn" class="prod-btn prod-btn-ghost" style="display: none;" onclick="closeExportModal()">Close</button>
    </div>
</div>

<script>
let currentSupplier = null;

function esc(str) {
    if (!str) return '—';
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

function switchSdpTab(tabName) {
    const profileBtn = document.getElementById('tabBtnProfile');
    const activityBtn = document.getElementById('tabBtnActivity');
    const profilePane = document.getElementById('sdpTabProfile');
    const activityPane = document.getElementById('sdpTabActivity');

    if (tabName === 'profile') {
        profileBtn.classList.add('active');
        activityBtn.classList.remove('active');
        profilePane.style.display = 'flex';
        activityPane.style.display = 'none';
    } else {
        activityBtn.classList.add('active');
        profileBtn.classList.remove('active');
        activityPane.style.display = 'flex';
        profilePane.style.display = 'none';
    }
}

let currentLedgerFilter = 'all';
let currentLedgerSearch = '';

function openSupplierPanel(s) {
    currentSupplier = s;
    currentLedgerFilter = 'all';
    currentLedgerSearch = '';
    switchSdpTab('profile');
    renderSupplierPanel(s);
    loadSupplierLedger(s.id);
    document.getElementById('sdpBackdrop').classList.add('active');
    document.getElementById('sdpPanel').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeSupplierPanel() {
    document.getElementById('sdpBackdrop').classList.remove('active');
    document.getElementById('sdpPanel').classList.remove('active');
    document.body.style.overflow = '';
    currentSupplier = null;
}

function renderSupplierPanel(s) {
    const isActive = s.status === 'active';
    const statusBadge = isActive
        ? `<span class="prod-badge prod-badge-active"><span class="dot"></span>Active</span>`
        : `<span class="prod-badge prod-badge-inactive"><span class="dot"></span>Inactive</span>`;

    const supCodeStr = s.supplier_code || ('SUP-' + String(s.id).padStart(4, '0'));
    const totalOut = parseFloat(s.total_outstanding || 0);
    const creditBal = parseFloat(s.total_credit_balance || 0);

    const activeOb = s.active_opening_balance;
    let obHtml = '';

    if (activeOb) {
        const obTypeLabel = (activeOb.balance_type === 'owe_supplier') ? 'We Owe Supplier (Payable)' : 'Supplier Owes Us / Credit (Receivable)';
        const obTypeColor = (activeOb.balance_type === 'owe_supplier') ? '#dc2626' : '#16a34a';
        obHtml = `
            <div class="sdp-card" style="background: #f8fafc; border-color: #cbd5e1;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                    <span style="font-size: 0.72rem; font-weight: 800; text-transform: uppercase; color: #64748b;">Active Opening Balance</span>
                    <span style="font-size: 0.72rem; font-weight: 800; color: ${obTypeColor}; background: #ffffff; padding: 2px 8px; border-radius: 999px; border: 1px solid #e2e8f0;">${obTypeLabel}</span>
                </div>
                <div style="font-size: 1.3rem; font-weight: 800; font-family: monospace; color: #0f172a;">
                    LKR ${parseFloat(activeOb.amount).toLocaleString('en-US', {minimumFractionDigits: 2})}
                </div>
                <div style="font-size: 0.78rem; color: #475569; margin-top: 6px; display: flex; flex-direction: column; gap: 2px;">
                    <div><strong>Date:</strong> ${esc(activeOb.entry_date)}</div>
                    <div><strong>Journal #:</strong> <code style="font-size: 0.78rem; font-weight: 700; color: #2563eb;">${esc(activeOb.journal_number || 'N/A')}</code></div>
                    <div><strong>Posted By:</strong> ${esc(activeOb.created_by_name || 'System')} (${esc(activeOb.created_at)})</div>
                    ${activeOb.reference_no ? `<div><strong>Ref:</strong> ${esc(activeOb.reference_no)}</div>` : ''}
                    ${activeOb.notes ? `<div><strong>Note:</strong> ${esc(activeOb.notes)}</div>` : ''}
                </div>
                <div style="margin-top: 10px; padding-top: 8px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end;">
                    <button type="button" class="prod-act-btn prod-act-delete" onclick='openReverseObModal(${JSON.stringify(activeOb)})'>
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                        Reverse Opening Balance
                    </button>
                </div>
            </div>
        `;
    } else {
        obHtml = `
            <div class="sdp-card" style="background: #ffffff; border-style: dashed;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <div style="font-size: 0.8rem; font-weight: 800; color: #0f172a;">No Active Opening Balance</div>
                        <div style="font-size: 0.75rem; color: #64748b;">Set initial migration balance</div>
                    </div>
                    <button type="button" class="prod-btn prod-btn-primary" style="padding: 5px 12px; font-size: 11px;" onclick='openObModal(${JSON.stringify(s)})'>
                        + Set Opening Balance
                    </button>
                </div>
            </div>
        `;
    }

    // ── Render Tab 1: Profile Pane ──
    document.getElementById('sdpTabProfile').innerHTML = `
        <div style="display: flex; flex-direction: column; gap: 4px;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <span class="sup-code-badge" style="font-size: 0.82rem;">${esc(supCodeStr)}</span>
                ${statusBadge}
            </div>
            <div style="font-size: 1.25rem; font-weight: 800; color: #0f172a; margin-top: 4px;">${esc(s.name)}</div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
            <div class="sdp-card" style="background: #fef2f2; border-color: #fecaca;">
                <div class="sdp-stat-label" style="color: #991b1b;">Current Outstanding</div>
                <div class="sdp-stat-value" style="color: #dc2626;">LKR ${totalOut.toLocaleString('en-US', {minimumFractionDigits:2})}</div>
                <div style="font-size: 0.7rem; color: #7f1d1d; margin-top: 2px;">Total Payable Due</div>
            </div>

            <div class="sdp-card" style="background: #f0fdf4; border-color: #bbf7d0;">
                <div class="sdp-stat-label" style="color: #166534;">Supplier Credit</div>
                <div class="sdp-stat-value" style="color: #16a34a;">LKR ${creditBal.toLocaleString('en-US', {minimumFractionDigits:2})}</div>
                <div style="font-size: 0.7rem; color: #14532d; margin-top: 2px;">Receivable / Advances</div>
            </div>
        </div>

        <div>
            <div class="sdp-section-title">Opening Balance System</div>
            ${obHtml}
        </div>

        <div>
            <div class="sdp-section-title">Contact & Location</div>
            <div class="sdp-card" style="display: flex; flex-direction: column; gap: 8px; font-size: 0.82rem;">
                <div><strong style="color: #64748b;">Phone:</strong> <span style="color: #0f172a; font-weight: 600;">${esc(s.phone || 'N/A')}</span></div>
                <div><strong style="color: #64748b;">WhatsApp:</strong> <span style="color: #0f172a; font-weight: 600;">${esc(s.whatsapp || 'N/A')}</span></div>
                <div><strong style="color: #64748b;">Address:</strong> <span style="color: #0f172a;">${esc(s.address || 'N/A')}</span></div>
            </div>
        </div>

        <div>
            <div class="sdp-section-title">Bank Account Details</div>
            <div class="sdp-card" style="display: flex; flex-direction: column; gap: 8px; font-size: 0.82rem; background: #f8fafc; border-color: #e2e8f0;">
                <div><strong style="color: #64748b;">Account Name:</strong> <span style="color: #0f172a; font-weight: 600;">${esc(s.bank_account_name || 'N/A')}</span></div>
                <div><strong style="color: #64748b;">Bank:</strong> <span style="color: #0f172a; font-weight: 600;">${esc(s.bank_name || 'N/A')}</span></div>
                <div><strong style="color: #64748b;">Account Number:</strong> <span style="color: #0f172a; font-family: monospace; font-weight: 700;">${esc(s.bank_account_no || 'N/A')}</span></div>
                <div><strong style="color: #64748b;">Branch:</strong> <span style="color: #0f172a;">${esc(s.bank_branch || 'N/A')}</span></div>
            </div>
        </div>

        <div>
            <div class="sdp-section-title">Ledger Summary</div>
            <div class="sdp-card" style="display: flex; justify-content: space-around; text-align: center;">
                <div>
                    <div style="font-size: 1.1rem; font-weight: 800; color: #0f172a;">${s.approved_grns_count || 0}</div>
                    <div style="font-size: 0.72rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Approved GRNs</div>
                </div>
                <div style="border-right: 1px solid #e2e8f0;"></div>
                <div>
                    <div style="font-size: 1.1rem; font-weight: 800; color: #0f172a;">${s.payment_history_count || 0}</div>
                    <div style="font-size: 0.72rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Payments Logged</div>
                </div>
            </div>
        </div>
    `;

    // ── Render Tab 2: Activity Ledger Table ──
    renderActivityLedger(s.activity_ledger || [], currentLedgerFilter, currentLedgerSearch);

    // Inject Action Row Buttons
    const actionRow = document.getElementById('sdpActionRow');
    const payBtnHtml = (totalOut > 0.001)
        ? `<button type="button" class="prod-act-btn" style="background: #f0fdf4; color: #166534; border-color: #bbf7d0; font-weight: 700;" onclick='openPaySupplierModal(${JSON.stringify(s)})'>
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                Make Payment
           </button>`
        : '';

    actionRow.innerHTML = `
        ${payBtnHtml}
        <button type="button" class="prod-act-btn prod-act-edit" onclick='openEditSupplierModal(${JSON.stringify(s)})'>
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Edit Supplier
        </button>
        <button type="button" class="prod-act-btn prod-act-edit" onclick='openObModal(${JSON.stringify(s)})'>
            Set / Adjust OB
        </button>
        <button type="button" class="prod-act-btn ${isActive ? 'prod-act-toggle-deactivate' : 'prod-act-toggle-activate'}" onclick="submitToggle(${s.id})">
            ${isActive ? 'Deactivate' : 'Activate'}
        </button>
        <button type="button" class="prod-act-btn prod-act-delete" onclick="submitDelete(${s.id})">
            Delete
        </button>
    `;
}

function setLedgerFilter(type) {
    currentLedgerFilter = type;
    if (currentSupplier) {
        renderActivityLedger(currentSupplier.activity_ledger || [], currentLedgerFilter, currentLedgerSearch);
    }
}

function onLedgerSearchInput(val) {
    currentLedgerSearch = val;
    if (currentSupplier) {
        renderActivityLedger(currentSupplier.activity_ledger || [], currentLedgerFilter, currentLedgerSearch);
        const searchInput = document.getElementById('ledgerSearchInput');
        if (searchInput) {
            searchInput.focus();
            const len = searchInput.value.length;
            searchInput.setSelectionRange(len, len);
        }
    }
}

function loadSupplierLedger(supplierId) {
    if (!supplierId) return;
    fetch(`supplier_payments_api.php?action=get_supplier_activity_ledger&supplier_id=${supplierId}`)
        .then(res => res.json())
        .then(data => {
            if (data && data.success && Array.isArray(data.activity_ledger)) {
                if (currentSupplier && currentSupplier.id == supplierId) {
                    currentSupplier.activity_ledger = data.activity_ledger;
                    renderActivityLedger(data.activity_ledger, currentLedgerFilter, currentLedgerSearch);
                }
            }
        })
        .catch(err => console.error('Error fetching fresh activity ledger:', err));
}

function renderActivityLedger(ledger, filterType = 'all', searchQuery = '') {
    const totalCount = ledger.length;
    const grnCount   = ledger.filter(x => x.type === 'GRN').length;
    const payCount   = ledger.filter(x => x.type === 'Payment').length;
    const obCount    = ledger.filter(x => x.type === 'Opening Bal').length;
    const retCount   = ledger.filter(x => x.type === 'Return' || x.type === 'Settlement').length;
    const poCount    = ledger.filter(x => x.type === 'PO').length;

    const badgeEl = document.getElementById('sdpActivityCountBadge');
    if (badgeEl) badgeEl.textContent = totalCount;

    if (!ledger.length) {
        document.getElementById('sdpTabActivity').innerHTML = `
            <div style="text-align: center; padding: 48px 16px; color: #94a3b8;">
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-bottom: 8px; opacity: 0.5;"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                <div style="font-size: 0.92rem; font-weight: 700; color: #475569;">No Activity Logged</div>
                <div style="font-size: 0.78rem; margin-top: 3px; color: #64748b;">No GRNs, payments, invoices, or transactions recorded yet with this supplier.</div>
            </div>
        `;
        return;
    }

    let totalPurchases = 0;
    let totalPaymentsPaid = 0;
    let totalReturnsVal = 0;
    let totalOpeningBal = 0;
    let totalDebits = 0;
    let totalCredits = 0;
    ledger.forEach(item => {
        const d = parseFloat(item.debit || 0);
        const c = parseFloat(item.credit || 0);
        totalDebits += d;
        totalCredits += c;

        if (item.type === 'GRN') {
            totalPurchases += d;
        } else if (item.type === 'Payment') {
            totalPaymentsPaid += c;
        } else if (item.type === 'Return' || item.type === 'Settlement') {
            totalReturnsVal += c;
        } else if (item.type === 'Opening Bal') {
            totalOpeningBal += (d - c);
        }
    });
    const currentRunningBal = ledger.length ? parseFloat(ledger[0].running_balance || 0) : 0;

    let filtered = ledger.slice();
    if (filterType !== 'all') {
        if (filterType === 'grn') {
            filtered = filtered.filter(x => x.type === 'GRN');
        } else if (filterType === 'payment') {
            filtered = filtered.filter(x => x.type === 'Payment');
        } else if (filterType === 'ob') {
            filtered = filtered.filter(x => x.type === 'Opening Bal');
        } else if (filterType === 'return') {
            filtered = filtered.filter(x => x.type === 'Return' || x.type === 'Settlement');
        } else if (filterType === 'po') {
            filtered = filtered.filter(x => x.type === 'PO');
        }
    }

    if (searchQuery && searchQuery.trim() !== '') {
        const q = searchQuery.toLowerCase().trim();
        filtered = filtered.filter(x => {
            return (x.ref_no && x.ref_no.toLowerCase().includes(q)) ||
                   (x.invoice_no && x.invoice_no.toLowerCase().includes(q)) ||
                   (x.title && x.title.toLowerCase().includes(q)) ||
                   (x.type && x.type.toLowerCase().includes(q)) ||
                   (x.details && x.details.toLowerCase().includes(q)) ||
                   (x.status && x.status.toLowerCase().includes(q)) ||
                   (x.date && x.date.toLowerCase().includes(q)) ||
                   (x.amount && String(x.amount).includes(q));
        });
    }

    let rowsHtml = '';
    if (!filtered.length) {
        rowsHtml = `
            <tr>
                <td colspan="6" style="text-align: center; padding: 32px 16px; color: #94a3b8;">
                    No transactions match the selected filter.
                </td>
            </tr>
        `;
    } else {
        filtered.forEach(item => {
            const dateStr = item.date_fmt || esc(item.date);
            const debitVal = parseFloat(item.debit || 0);
            const creditVal = parseFloat(item.credit || 0);
            const runningVal = parseFloat(item.running_balance || 0);

            const debitCell = (debitVal > 0)
                ? `<td class="ledger-col-debit">${debitVal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>`
                : `<td class="ledger-col-empty">-</td>`;

            const creditCell = (creditVal > 0)
                ? `<td class="ledger-col-credit">${creditVal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>`
                : `<td class="ledger-col-empty">-</td>`;

            const runningColor = (runningVal > 0) ? '#dc2626' : ((runningVal < 0) ? '#16a34a' : '#64748b');
            const runningCell = `<td class="ledger-col-running" style="color: ${runningColor};">${runningVal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>`;

            let typeBadge = '';
            if (item.type === 'GRN') {
                typeBadge = '<span class="act-badge act-badge-grn">GRN</span>';
            } else if (item.type === 'Payment') {
                typeBadge = '<span class="act-badge act-badge-payment">Payment</span>';
            } else if (item.type === 'Opening Bal') {
                typeBadge = '<span class="act-badge act-badge-opening_balance">Opening Bal</span>';
            } else if (item.type === 'Return') {
                typeBadge = '<span class="act-badge act-badge-return">Return</span>';
            } else if (item.type === 'Settlement') {
                typeBadge = '<span class="act-badge act-badge-payment">Settlement</span>';
            } else if (item.type === 'PO') {
                typeBadge = '<span class="act-badge act-badge-po">PO Order</span>';
            }

            let invBadge = '';
            if (item.invoice_no) {
                invBadge = `<span style="background: #f1f5f9; border: 1px solid #cbd5e1; color: #1e293b; font-size: 10px; font-weight: 800; padding: 1px 6px; border-radius: 4px;" title="Supplier Invoice Number">Inv #${esc(item.invoice_no)}</span>`;
            }

            let statusBadge = '';
            if (item.status === 'Approved') {
                statusBadge = '<span class="prod-badge prod-badge-active" style="font-size: 9.5px; padding: 1px 6px;"><span class="dot"></span>Approved</span>';
            } else if (item.status === 'Draft') {
                statusBadge = '<span class="prod-badge" style="background: #fef3c7; color: #92400e; border: 1px solid #fde68a; font-size: 9.5px; padding: 1px 6px;"><span class="dot" style="background:#b45309;"></span>Draft</span>';
            } else if (item.status === 'Cancelled') {
                statusBadge = '<span class="prod-badge prod-badge-inactive" style="font-size: 9.5px; padding: 1px 6px;">Cancelled</span>';
            }

            let payBadge = '';
            if (item.payment_status === 'paid') {
                payBadge = '<span class="prod-badge prod-badge-active" style="font-size: 9.5px; padding: 1px 6px;">Paid</span>';
            } else if (item.payment_status === 'partially_paid') {
                payBadge = '<span class="prod-badge" style="background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-size: 9.5px; padding: 1px 6px;">Partial</span>';
            } else if (item.payment_status === 'unpaid' && item.type === 'GRN') {
                payBadge = '<span class="prod-badge prod-badge-inactive" style="font-size: 9.5px; padding: 1px 6px;">Unpaid</span>';
            }

            let refHtml = `<strong style="font-family: monospace; font-size: 12px; color: #0f172a;">${esc(item.ref_no)}</strong>`;
            if (item.action_url) {
                refHtml = `<a href="${item.action_url}" target="_blank" style="font-family: monospace; font-size: 12px; font-weight: 800; color: #0f172a; text-decoration: none;" title="Open document">${esc(item.ref_no)} ↗</a>`;
            }

            const detailsHtml = item.details ? `<div style="font-size: 11px; color: #64748b; line-height: 1.35; margin-top: 2px;">${esc(item.details)}</div>` : '';

            const actionCell = item.action_url
                ? `<a href="${item.action_url}" target="_blank" class="prod-act-btn prod-act-edit" style="font-size: 10px; padding: 3px 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 3px;" title="View Document">View ↗</a>`
                : '<span style="color: #cbd5e1;">—</span>';

            rowsHtml += `
                <tr>
                    <td class="ledger-col-date" style="vertical-align: top; padding-top: 11px;">${dateStr}</td>
                    <td style="vertical-align: top; padding-top: 10px;">
                        <div style="display: flex; flex-direction: column; gap: 3px;">
                            <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                ${typeBadge}
                                ${refHtml}
                                ${invBadge}
                                ${statusBadge}
                                ${payBadge}
                            </div>
                            ${detailsHtml}
                        </div>
                    </td>
                    ${debitCell}
                    ${creditCell}
                    ${runningCell}
                    <td class="col-right" style="vertical-align: top; padding-top: 10px; text-align: right;">
                        ${actionCell}
                    </td>
                </tr>
            `;
        });
    }

    const returnCardHtml = retCount > 0 ? `
        <div class="sdp-card" style="background: #eff6ff; border-color: #bfdbfe; padding: 10px 14px;">
            <div style="font-size: 0.68rem; font-weight: 800; text-transform: uppercase; color: #1e40af; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Purchase Returns</div>
            <div style="font-size: 1.1rem; font-weight: 800; font-family: monospace; color: #2563eb; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">LKR ${totalReturnsVal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
            <div style="font-size: 0.72rem; color: #1d4ed8; margin-top: 2px;">${retCount} Return(s) Logged</div>
        </div>
    ` : '';

    const html = `
        <div style="display: grid; grid-template-columns: repeat(${retCount > 0 ? 4 : 3}, minmax(0, 1fr)); gap: 10px; margin-bottom: 12px;">
            <div class="sdp-card" style="background: #f8fafc; border-color: #e2e8f0; padding: 10px 14px;">
                <div style="font-size: 0.68rem; font-weight: 800; text-transform: uppercase; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Total Purchases / GRNs</div>
                <div style="font-size: 1.1rem; font-weight: 800; font-family: monospace; color: #0f172a; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">LKR ${totalPurchases.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                <div style="font-size: 0.72rem; color: #64748b; margin-top: 2px;">${grnCount} GRN(s) Logged</div>
            </div>
            <div class="sdp-card" style="background: #f0fdf4; border-color: #bbf7d0; padding: 10px 14px;">
                <div style="font-size: 0.68rem; font-weight: 800; text-transform: uppercase; color: #166534; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Total Payments Paid</div>
                <div style="font-size: 1.1rem; font-weight: 800; font-family: monospace; color: #15803d; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">LKR ${totalPaymentsPaid.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                <div style="font-size: 0.72rem; color: #15803d; margin-top: 2px;">${payCount} Payment(s) Settled</div>
            </div>
            ${returnCardHtml}
            <div class="sdp-card" style="background: ${currentRunningBal > 0 ? '#fef2f2' : '#f0fdf4'}; border-color: ${currentRunningBal > 0 ? '#fecaca' : '#bbf7d0'}; padding: 10px 14px;">
                <div style="font-size: 0.68rem; font-weight: 800; text-transform: uppercase; color: ${currentRunningBal > 0 ? '#991b1b' : '#166534'}; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Current Balance Due</div>
                <div style="font-size: 1.1rem; font-weight: 800; font-family: monospace; color: ${currentRunningBal > 0 ? '#dc2626' : '#16a34a'}; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">LKR ${Math.abs(currentRunningBal).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                <div style="font-size: 0.72rem; color: ${currentRunningBal > 0 ? '#b91c1c' : '#15803d'}; margin-top: 2px;">${currentRunningBal > 0 ? 'Payable (We Owe)' : (currentRunningBal < 0 ? 'Advance Credit' : 'Fully Settled')}</div>
            </div>
        </div>

        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; margin-bottom: 12px;">
            <div style="display: flex; align-items: center; gap: 5px; flex-wrap: wrap;">
                <button type="button" class="ledger-filter-pill ${filterType === 'all' ? 'active' : ''}" onclick="setLedgerFilter('all')">All (${totalCount})</button>
                <button type="button" class="ledger-filter-pill ${filterType === 'grn' ? 'active' : ''}" onclick="setLedgerFilter('grn')">GRNs & Invoices (${grnCount})</button>
                <button type="button" class="ledger-filter-pill ${filterType === 'payment' ? 'active' : ''}" onclick="setLedgerFilter('payment')">Payments (${payCount})</button>
                <button type="button" class="ledger-filter-pill ${filterType === 'ob' ? 'active' : ''}" onclick="setLedgerFilter('ob')">Opening Bal (${obCount})</button>
                <button type="button" class="ledger-filter-pill ${filterType === 'return' ? 'active' : ''}" onclick="setLedgerFilter('return')">Returns (${retCount})</button>
                <button type="button" class="ledger-filter-pill ${filterType === 'po' ? 'active' : ''}" onclick="setLedgerFilter('po')">Orders (${poCount})</button>
            </div>
            <div style="position: relative; min-width: 180px; flex: 1; max-width: 250px;">
                <input type="text" id="ledgerSearchInput" placeholder="Filter ledger..." value="${esc(searchQuery)}" oninput="onLedgerSearchInput(this.value)" style="width: 100%; height: 30px; font-size: 11.5px; border-radius: 999px; border: 1px solid #cbd5e1; padding: 0 12px; outline: none;">
            </div>
        </div>

        <div class="ledger-table-panel">
            <div style="overflow-x: auto; max-height: 480px; overflow-y: auto;">
                <table class="ledger-table">
                    <thead>
                        <tr>
                            <th style="width: 105px; text-align: left;">DATE</th>
                            <th style="text-align: left;">TRANSACTION & DETAILS</th>
                            <th class="col-right" style="text-align: right; width: 110px;">DEBIT (LKR)</th>
                            <th class="col-right" style="text-align: right; width: 110px;">CREDIT (LKR)</th>
                            <th class="col-right" style="text-align: right; width: 135px;">RUNNING BAL (LKR)</th>
                            <th class="col-right" style="text-align: right; width: 65px;">ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rowsHtml}
                    </tbody>
                </table>
            </div>
        </div>
    `;

    document.getElementById('sdpTabActivity').innerHTML = html;
}

function submitToggle(supplierId) {
    const f = document.createElement('form');
    f.method = 'POST';
    f.action = 'suppliers';
    f.innerHTML = `<input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="${supplierId}">`;
    document.body.appendChild(f);
    f.submit();
}

function submitDelete(supplierId) {
    if (!confirm('Are you sure you want to delete this supplier? If historical transactions exist, deletion will be blocked and deactivation offered.')) return;
    const f = document.createElement('form');
    f.method = 'POST';
    f.action = 'suppliers';
    f.innerHTML = `<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="${supplierId}">`;
    document.body.appendChild(f);
    f.submit();
}

function submitRestore(supplierId) {
    const f = document.createElement('form');
    f.method = 'POST';
    f.action = 'suppliers';
    f.innerHTML = `<input type="hidden" name="action" value="restore"><input type="hidden" name="id" value="${supplierId}">`;
    document.body.appendChild(f);
    f.submit();
}

function handleFormSubmit(form) {
    const btn = form.querySelector('button[type="submit"]');
    if (btn) {
        btn.disabled = true;
        btn.style.opacity = '0.6';
        btn.style.cursor = 'not-allowed';
    }
    return true;
}

function openCreateSupplierModal() {
    document.getElementById('supModalTitle').innerText = 'Add New Supplier';
    document.getElementById('supAction').value = 'create';
    document.getElementById('supId').value = '';
    document.getElementById('supName').value = '';
    document.getElementById('supPhone').value = '';
    document.getElementById('supWhatsapp').value = '';
    document.getElementById('supAddress').value = '';
    document.getElementById('supBankAccountName').value = '';
    document.getElementById('supBankName').value = '';
    document.getElementById('supBankAccountNo').value = '';
    document.getElementById('supBankBranch').value = '';
    document.getElementById('supModal').classList.add('active');
    setTimeout(() => {
        const inp = document.getElementById('supName');
        if (inp) { inp.focus(); inp.select(); }
    }, 50);
}

function openEditSupplierModal(s) {
    document.getElementById('supModalTitle').innerText = 'Edit Supplier';
    document.getElementById('supAction').value = 'update';
    document.getElementById('supId').value = s.id;
    document.getElementById('supName').value = s.name;
    document.getElementById('supPhone').value = s.phone || '';
    document.getElementById('supWhatsapp').value = s.whatsapp || '';
    document.getElementById('supAddress').value = s.address || '';
    document.getElementById('supBankAccountName').value = s.bank_account_name || '';
    document.getElementById('supBankName').value = s.bank_name || '';
    document.getElementById('supBankAccountNo').value = s.bank_account_no || '';
    document.getElementById('supBankBranch').value = s.bank_branch || '';
    document.getElementById('supModal').classList.add('active');
    setTimeout(() => {
        const inp = document.getElementById('supName');
        if (inp) { inp.focus(); inp.select(); }
    }, 50);
}

function closeSupplierModal() {
    document.getElementById('supModal').classList.remove('active');
}

function openObModal(s) {
    document.getElementById('obSupplierId').value = s.id;
    document.getElementById('obSupplierName').innerText = `${s.name} (${s.supplier_code || ('SUP-' + s.id)})`;
    document.getElementById('obAmount').value = '';
    document.getElementById('obRef').value = '';
    document.getElementById('obNotes').value = '';
    document.getElementById('obModal').classList.add('active');
    setTimeout(() => {
        const inp = document.getElementById('obAmount');
        if (inp) { inp.focus(); inp.select(); }
    }, 50);
}

function closeObModal() {
    document.getElementById('obModal').classList.remove('active');
}

function openReverseObModal(ob) {
    document.getElementById('revObId').value = ob.id;
    const typeText = (ob.balance_type === 'owe_supplier') ? 'We Owe Supplier (Payable)' : 'Supplier Owes Us (Credit)';
    document.getElementById('revObSummaryText').innerText = `LKR ${parseFloat(ob.amount).toFixed(2)} [${typeText}] - Journal #${ob.journal_number || 'N/A'}`;
    document.getElementById('revReason').value = '';
    document.getElementById('reverseObModal').classList.add('active');
    setTimeout(() => {
        const inp = document.getElementById('revReason');
        if (inp) { inp.focus(); inp.select(); }
    }, 50);
}

function closeReverseObModal() {
    document.getElementById('reverseObModal').classList.remove('active');
}

async function openPaySupplierModal(s) {
    document.getElementById('paySupplierId').value = s.id;
    document.getElementById('paySupplierName').innerText = `${s.name} (${s.supplier_code || ('SUP-' + s.id)})`;
    
    const totalOut = parseFloat(s.total_outstanding || 0);
    document.getElementById('paySupplierOutstanding').innerText = `LKR ${totalOut.toLocaleString('en-US', {minimumFractionDigits: 2})}`;
    
    const amtInput = document.getElementById('payAmountInput');
    amtInput.value = totalOut > 0 ? totalOut.toFixed(2) : '';
    
    document.getElementById('payMethodSelect').value = 'Cash in Hand';
    toggleSupplierPayFields();
    
    const tbody = document.getElementById('payModalGrnsTbody');
    tbody.innerHTML = `<tr><td colspan="4" style="text-align: center; color: #94a3b8; padding: 1rem;">Loading unpaid obligations...</td></tr>`;
    
    document.getElementById('paySupplierModal').classList.add('active');
    setTimeout(() => { amtInput.focus(); amtInput.select(); }, 50);

    try {
        const res = await fetch(`supplier_payments_api.php?action=get_supplier_outstanding&supplier_id=${s.id}`);
        const data = await res.json();
        if (data.success) {
            let html = '';
            if (data.active_opening_balance && parseFloat(data.ob_outstanding || 0) > 0) {
                const obAmt = parseFloat(data.ob_outstanding);
                html += `
                    <tr style="border-bottom: 1px solid #f1f5f9; background: #fffbeb;">
                        <td style="padding: 6px 10px; font-weight: 800; color: #b45309;">Opening Balance (Payable)</td>
                        <td style="padding: 6px 10px; color: #78350f;">${esc(data.active_opening_balance.reference_no || 'OB Initial')}</td>
                        <td style="padding: 6px 10px; color: #78350f;">${esc(data.active_opening_balance.entry_date)}</td>
                        <td style="padding: 6px 10px; text-align: right; font-weight: 800; font-family: monospace; color: #dc2626;">LKR ${obAmt.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                    </tr>
                `;
            }
            if (data.approved_grns && data.approved_grns.length) {
                data.approved_grns.forEach(g => {
                    const due = parseFloat(g.due_amount || 0);
                    if (due > 0) {
                        html += `
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 6px 10px; font-weight: 700; color: #0f172a;">${esc(g.grn_number)}</td>
                                <td style="padding: 6px 10px; color: #475569;">${esc(g.supplier_invoice_no || 'N/A')}</td>
                                <td style="padding: 6px 10px; color: #64748b;">${esc(g.received_date)}</td>
                                <td style="padding: 6px 10px; text-align: right; font-weight: 800; font-family: monospace; color: #dc2626;">LKR ${due.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                            </tr>
                        `;
                    }
                });
            }
            tbody.innerHTML = html || `<tr><td colspan="4" style="text-align: center; color: #16a34a; padding: 1rem; font-weight: 700;">All supplier obligations fully settled.</td></tr>`;
        }
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="4" style="text-align: center; color: #ef4444; padding: 1rem;">Failed to load GRN list.</td></tr>`;
    }
}

function closePaySupplierModal() {
    document.getElementById('paySupplierModal').classList.remove('active');
}

function toggleSupplierPayFields() {
    const method = document.getElementById('payMethodSelect').value;
    const bankGroup = document.getElementById('payBankGroup');
    const chequeGroup = document.getElementById('payChequeGroup');

    if (method.startsWith('Bank')) {
        bankGroup.style.display = 'none';
        chequeGroup.style.display = 'none';
    } else if (method === 'Cheque') {
        bankGroup.style.display = 'block';
        chequeGroup.style.display = 'block';
    } else {
        bankGroup.style.display = 'none';
        chequeGroup.style.display = 'none';
    }
}

// Close panels/modals on Escape key press
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeSupplierPanel();
        closeSupplierModal();
        closeObModal();
        closeReverseObModal();
        closePaySupplierModal();
    }
});

/* ── Keyboard Enter Navigation & Form Auto-Submit System ── */
document.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey && e.target.matches('input:not([type="hidden"]), select, textarea')) {
        const form = e.target.form;
        if (!form) return;

        const visibleControls = Array.from(form.querySelectorAll('input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled])'))
            .filter(el => el.offsetWidth > 0 || el.offsetHeight > 0 || (el.getClientRects && el.getClientRects().length > 0));

        const index = visibleControls.indexOf(e.target);
        if (index > -1) {
            if (index < visibleControls.length - 1) {
                e.preventDefault();
                const nextEl = visibleControls[index + 1];
                nextEl.focus();
                if (typeof nextEl.select === 'function' && nextEl.tagName !== 'TEXTAREA') {
                    nextEl.select();
                }
            } else {
                // Final field: submit/save form directly
                e.preventDefault();
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                } else {
                    const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
                    if (submitBtn) {
                        submitBtn.click();
                    } else {
                        form.submit();
                    }
                }
            }
        }
    }
});

/* ── Client-Side Dynamic Table Pagination Controller ── */
let currentPage = 1;
let pageSize = 25;

function updatePagination() {
    const table = document.getElementById('supplierTable');
    if (!table) return;

    const allRows = Array.from(table.querySelectorAll('tbody tr')).filter(tr => !tr.classList.contains('prod-empty'));
    const visibleRows = allRows.filter(tr => tr.dataset.searchMatched !== 'false');

    const totalItems = visibleRows.length;
    const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));

    if (currentPage > totalPages) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;

    const startIdx = totalItems > 0 ? (currentPage - 1) * pageSize : 0;
    const endIdx = Math.min(startIdx + pageSize, totalItems);

    allRows.forEach(tr => tr.style.display = 'none');
    for (let i = startIdx; i < endIdx; i++) {
        if (visibleRows[i]) {
            visibleRows[i].style.display = '';
        }
    }

    document.getElementById('pagStart').textContent = totalItems > 0 ? startIdx + 1 : 0;
    document.getElementById('pagEnd').textContent = endIdx;
    document.getElementById('pagTotal').textContent = totalItems;

    const navContainer = document.getElementById('pagNavBtns');
    navContainer.innerHTML = '';

    // Prev Button
    const prevBtn = document.createElement('button');
    prevBtn.className = 'prod-act-btn prod-act-edit';
    prevBtn.style.padding = '4px 10px';
    prevBtn.disabled = currentPage === 1;
    prevBtn.innerHTML = `← Prev`;
    prevBtn.onclick = () => { if (currentPage > 1) { currentPage--; updatePagination(); } };
    navContainer.appendChild(prevBtn);

    // Page Numbers
    let startPage = Math.max(1, currentPage - 2);
    let endPage = Math.min(totalPages, startPage + 4);
    if (endPage - startPage < 4) startPage = Math.max(1, endPage - 4);

    for (let p = startPage; p <= endPage; p++) {
        const pBtn = document.createElement('button');
        pBtn.className = 'prod-act-btn ' + (p === currentPage ? 'prod-act-primary' : 'prod-act-edit');
        pBtn.style.padding = '4px 10px';
        pBtn.style.minWidth = '28px';
        pBtn.style.justifyContent = 'center';
        if (p === currentPage) {
            pBtn.style.background = '#0f172a';
            pBtn.style.color = '#ffffff';
            pBtn.style.borderColor = '#0f172a';
        }
        pBtn.textContent = p;
        pBtn.onclick = () => { currentPage = p; updatePagination(); };
        navContainer.appendChild(pBtn);
    }

    // Next Button
    const nextBtn = document.createElement('button');
    nextBtn.className = 'prod-act-btn prod-act-edit';
    nextBtn.style.padding = '4px 10px';
    nextBtn.disabled = currentPage === totalPages;
    nextBtn.innerHTML = `Next →`;
    nextBtn.onclick = () => { if (currentPage < totalPages) { currentPage++; updatePagination(); } };
    navContainer.appendChild(nextBtn);
}

function changePageSize() {
    pageSize = parseInt(document.getElementById('pagPageSize').value) || 25;
    currentPage = 1;
    updatePagination();
}

document.addEventListener('DOMContentLoaded', updatePagination);

/* ── Realtime Search in Floating Bottom Nav Bar ── */
function registerBottomSearch() {
    if (window.CurtissNav && window.CurtissNav.enablePageSearch) {
        const urlParams = new URLSearchParams(window.location.search);
        const currentSearch = urlParams.get('search') || '';
        window.CurtissNav.enablePageSearch({
            placeholder: 'Search suppliers by code, name, phone, payable due...',
            initialValue: currentSearch,
            onSearch: function(query) {
                filterSuppliersRealtime(query);
            }
        });
        const navInput = document.querySelector('.cnav-search-input');
        if (navInput) {
            navInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const q = this.value.trim();
                    const url = new URL(window.location.href);
                    if (q) {
                        url.searchParams.set('search', q);
                    } else {
                        url.searchParams.delete('search');
                    }
                    window.location.href = url.toString();
                }
            });
        }
    } else {
        setTimeout(registerBottomSearch, 40);
    }
}
registerBottomSearch();

function filterSuppliersRealtime(query) {
    const q = (query || '').toLowerCase().trim();
    const rows = document.querySelectorAll('#supplierTable tbody tr:not(.prod-empty)');

    if (!q) {
        rows.forEach(tr => { tr.dataset.searchMatched = 'true'; });
        currentPage = 1;
        updatePagination();
        return;
    }

    const qClean = q.replace(/,/g, '').replace(/lkr|rs\.?/gi, '').trim();

    rows.forEach(tr => {
        const searchAttr = tr.getAttribute('data-search') || '';
        const isMatch = searchAttr.includes(q) || (qClean && searchAttr.includes(qClean));
        tr.dataset.searchMatched = isMatch ? 'true' : 'false';
    });
    currentPage = 1;
    updatePagination();
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

    title.textContent = 'Exporting Suppliers';
    subtitle.textContent = 'Preparing supplier records...';
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

            let filename = 'suppliers_export.csv';
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
</script>
