<?php
/**
 * Curtiss POS - Petty Cash & Expense Management Master View (Stage 12 Redesign & Optimization)
 * Glassmorphic ERP Interface for branch petty cash fund tracking, expense vouchers creation,
 * COA-mapped expense categories, bank/drawer reimbursements, adjustments, trash bin & voucher reversals.
 */
?>
<link rel="stylesheet" href="assets/css/curtiss_select.css">
<script src="assets/js/curtiss_select.js"></script>

<!-- Floating Top-Right Action Header Buttons -->
<div id="curtiss-floating-header-right" class="no-print" style="position: fixed; top: 16px; right: 20px; z-index: 1050; display: flex; align-items: center; gap: 8px;">
    <?php if (Auth::hasPermission('create_expense')): ?>
    <button type="button" class="inv-btn inv-btn-primary" onclick="openNewVoucherModal()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        New Expense Voucher
    </button>
    <?php endif; ?>

    <?php if (Auth::hasPermission('reimburse_petty_cash')): ?>
    <button type="button" class="inv-btn inv-btn-ghost" onclick="openReimburseModal()" style="color: #6d28d9; border-color: rgba(139,92,246,0.3); background: rgba(245,243,255,0.95);">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
        Reimburse / Top-Up
    </button>
    <?php endif; ?>

    <?php if (Auth::hasPermission('manage_expense_categories')): ?>
    <button type="button" class="inv-btn inv-btn-ghost" onclick="openCategoriesModal()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
        Categories
    </button>
    <?php endif; ?>

    <?php if (Auth::hasPermission('adjust_petty_cash')): ?>
    <button type="button" class="inv-btn inv-btn-ghost" onclick="openAdjustmentModal()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 3v18M3 12h18"/></svg>
        Adjustment
    </button>
    <?php endif; ?>
</div>

<style>
    :root {
        --inv-primary: #0f172a;
        --inv-accent: #2563eb;
        --inv-success: #16a34a;
        --inv-warning: #d97706;
        --inv-danger: #dc2626;
        --inv-bg-glass: rgba(255, 255, 255, 0.94);
        --inv-border: rgba(226, 232, 240, 0.9);
        --inv-radius: 16px;
        --inv-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.05), 0 4px 12px -2px rgba(0, 0, 0, 0.03);
    }

    /* Page Header */
    .inv-header {
        margin-bottom: 0.85rem;
    }
    .inv-title {
        font-size: 1.35rem;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.025em;
        line-height: 1.2;
    }

    /* Button System */
    .inv-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 0 16px;
        height: 34px;
        min-height: 34px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        border: 1px solid transparent;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.18s ease;
        white-space: nowrap;
        font-family: inherit;
        box-sizing: border-box;
    }
    .inv-btn-ghost {
        background: rgba(255, 255, 255, 0.9);
        border-color: rgba(226, 232, 240, 0.9);
        color: #0f172a;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
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
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.25);
    }
    .inv-btn-primary:hover {
        background: #1e293b;
        transform: translateY(-1px);
    }

    /* Glassmorphism KPI Summary Cards Grid */
    .pc-kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1rem;
        margin-bottom: 1rem;
    }
    .pc-kpi-card {
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(12px);
        border-radius: 14px;
        padding: 1rem 1.25rem;
        border: 1px solid rgba(226, 232, 240, 0.9);
        box-shadow: 0 4px 12px -2px rgba(0, 0, 0, 0.03);
        position: relative;
        overflow: hidden;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .pc-kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px -4px rgba(0, 0, 0, 0.06);
    }
    .pc-kpi-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; width: 4px; height: 100%;
        background: #3b82f6;
    }
    .pc-kpi-card.balance::before { background: #059669; }
    .pc-kpi-card.expenses::before { background: #dc2626; }
    .pc-kpi-card.reimb::before { background: #7c3aed; }
    .pc-kpi-card.adj::before { background: #2563eb; }

    .kpi-lbl {
        font-size: 0.72rem;
        font-weight: 800;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        margin-bottom: 0.25rem;
    }
    .kpi-val {
        font-size: 1.45rem;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.02em;
    }

    /* Tab Navigation System */
    .cd-tabs-nav {
        display: flex;
        align-items: center;
        gap: 8px;
        border-bottom: 2px solid #e2e8f0;
        margin-bottom: 0.85rem;
    }
    .cd-tab-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 18px;
        border: none;
        background: transparent;
        font-size: 12.5px;
        font-weight: 700;
        color: #64748b;
        border-bottom: 3px solid transparent;
        cursor: pointer;
        margin-bottom: -2px;
        transition: all 0.18s ease;
        font-family: inherit;
        text-decoration: none;
    }
    .cd-tab-btn:hover {
        color: #0f172a;
    }
    .cd-tab-btn.active {
        color: #2563eb;
        border-bottom-color: #2563eb;
    }
    .cd-tab-badge {
        background: #f1f5f9;
        color: #475569;
        padding: 2px 8px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 800;
    }
    .cd-tab-btn.active .cd-tab-badge {
        background: #dbeafe;
        color: #1e40af;
    }

    /* In-Cell Badges & Height Standardization (Rule 24 & Rule 27) */
    .prod-badge {
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
    }
    .prod-badge-posted   { background: #dcfce7; color: #15803d; border: 1px solid rgba(22,163,74,0.2); }
    .prod-badge-reversed { background: #fee2e2; color: #b91c1c; border: 1px solid rgba(220,38,38,0.2); }
    .prod-badge-draft    { background: #fef3c7; color: #92400e; border: 1px solid rgba(217,119,6,0.2); }
    .prod-badge .dot {
        width: 6px; height: 6px; border-radius: 50%; display: inline-block;
    }
    .prod-badge-posted   .dot { background: #16a34a; }
    .prod-badge-reversed .dot { background: #dc2626; }
    .prod-badge-draft    .dot { background: #d97706; }

    /* Table Panels & Fixed Viewport Standard */
    .prod-table-panel {
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid rgba(226, 232, 240, 0.85);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
        display: flex;
        flex-direction: column;
    }
    .prod-table-scroll {
        overflow-y: auto;
        overflow-x: auto;
        flex: 1;
        min-height: 0;
    }
    .prod-table {
        width: 100%;
        border-collapse: collapse;
    }
    .prod-table thead {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    .prod-table thead th {
        padding: 10px 14px;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        color: #64748b;
        white-space: nowrap;
        position: sticky;
        top: 0;
        z-index: 10;
        background: #f8fafc;
        text-align: left;
    }
    .prod-table tbody tr {
        height: 48px;
        min-height: 48px;
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.12s ease;
    }
    /* Alternating Zebra Striping */
    .prod-table tbody tr:nth-child(even) { background: rgba(248, 250, 252, 0.57); }
    .prod-table tbody tr:nth-child(odd)  { background: #ffffff; }
    .prod-table tbody tr:hover            { background: #f1f5f9 !important; }
    .prod-table td {
        height: 48px;
        padding: 0 14px;
        font-size: 12.5px;
        color: #1e293b;
        vertical-align: middle;
        box-sizing: border-box;
        text-align: left;
    }
    .prod-table th.col-right, .prod-table td.col-right {
        text-align: right !important;
    }
    .prod-table th.col-center, .prod-table td.col-center {
        text-align: center !important;
    }

    /* Table Footer Pagination */
    .prod-table-footer {
        padding: 10px 18px;
        border-top: 1px solid #e2e8f0;
        background: #ffffff;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        flex-shrink: 0;
    }
    .prod-act-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
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
        box-sizing: border-box;
        white-space: nowrap;
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
    .prod-act-danger {
        background: #fef2f2;
        border-color: #fca5a5;
        color: #dc2626;
    }
    .prod-act-danger:hover {
        background: #fee2e2;
        color: #b91c1c;
    }
    .prod-act-success {
        background: #dcfce7;
        border-color: #86efac;
        color: #15803d;
    }
    .prod-act-success:hover {
        background: #bbf7d0;
        color: #166534;
    }

    /* Glassmorphism Modals */
    .curtiss-modal-backdrop {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.45);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        z-index: 2000;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }
    .curtiss-modal-backdrop.active { display: flex; }
    .curtiss-modal-card {
        background: rgba(255, 255, 255, 0.98);
        width: 100%;
        max-width: 560px;
        border-radius: 18px;
        padding: 22px 26px;
        box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.15);
        border: 1px solid rgba(226, 232, 240, 0.9);
        animation: modalSlideUp 0.22s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
    @keyframes modalSlideUp {
        from { opacity: 0; transform: translateY(12px) scale(0.98); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }

    .form-input {
        width: 100%;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        padding: 8px 12px;
        font-size: 13px;
        color: #0f172a;
        outline: none;
        box-sizing: border-box;
        transition: all 0.15s ease;
        font-family: inherit;
    }
    .form-input:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }
    .form-label {
        display: block;
        font-size: 11.5px;
        font-weight: 700;
        color: #334155;
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
</style>

<!-- Main Fixed Viewport Workarea Container (Rule 1) -->
<div style="width: 100%; max-width: 100%; height: calc(100vh - 110px); display: flex; flex-direction: column; box-sizing: border-box; overflow: hidden; padding-bottom: 8px;">

    <!-- Page Header (Headline Only per Rule 5 with Inline Filter per Rule 3) -->
    <div class="inv-header" style="flex-shrink: 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
                <h1 class="inv-title">Petty Cash & Expense Management</h1>
            </div>
        </div>
        <!-- Inline Filter Controls Above Table Right Corner (Height: 34px) -->
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Branch:</span>
                <select id="pcBranchFilter" onchange="applyPcBranchFilter()" style="height: 34px; min-height: 34px; padding: 0 14px; border-radius: 999px; font-size: 12px; font-weight: 700; color: #334155; background: rgba(255,255,255,0.95); border: 1px solid rgba(203,213,225,0.9); box-shadow: 0 2px 8px rgba(0,0,0,0.04); min-width: 170px;">
                    <option value="all">All Branches</option>
                    <?php if (!empty($branches)): foreach ($branches as $br): ?>
                    <option value="<?= (int)$br['id'] ?>" <?= (int)$br['id'] === (int)$branchId ? 'selected' : '' ?>><?= htmlspecialchars($br['name']) ?></option>
                    <?php endforeach; endif; ?>
                </select>
            </div>
        </div>
    </div>

    <!-- KPI Summary Cards Grid (Linked to COA 1015 Petty Cash Account) -->
    <div class="pc-kpi-grid" style="flex-shrink: 0;">
        <div class="pc-kpi-card balance">
            <div class="kpi-lbl">Petty Cash COA Balance (Account 1015)</div>
            <div class="kpi-val" id="kpiCurrentBalance" style="color: <?= ((float)($accountTransactions['summary']['current_balance'] ?? 0) >= 0) ? '#059669' : '#dc2626' ?>;">LKR <?= number_format((float)($accountTransactions['summary']['current_balance'] ?? 0), 2) ?></div>
        </div>
        <div class="pc-kpi-card expenses">
            <div class="kpi-lbl">Total Outflow / Spent</div>
            <div class="kpi-val" id="kpiTotalOutflow" style="color: #dc2626;">LKR <?= number_format((float)($accountTransactions['summary']['total_outflow'] ?? 0), 2) ?></div>
            <small style="color: #64748b; font-weight: 600; display: block; margin-top: 4px;" id="kpiTxCount"><?= (int)($accountTransactions['summary']['total_count'] ?? 0) ?> Total Transactions</small>
        </div>
        <div class="pc-kpi-card reimb">
            <div class="kpi-lbl">Total Inflow / Replenished</div>
            <div class="kpi-val" id="kpiTotalInflow" style="color: #16a34a;">LKR <?= number_format((float)($accountTransactions['summary']['total_inflow'] ?? 0), 2) ?></div>
        </div>
        <div class="pc-kpi-card adj">
            <div class="kpi-lbl">Net Adjustments</div>
            <div class="kpi-val" style="color: #2563eb;">LKR <?= number_format((float)$summary['net_adjustments'], 2) ?></div>
        </div>
    </div>

    <!-- Tab Navigation System -->
    <div class="cd-tabs-nav" style="flex-shrink: 0;">
        <button type="button" class="cd-tab-btn active" id="tabBtnAccountTransactions" onclick="switchPcTab('account_transactions')">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
            Account Transactions (COA 1015)
            <span class="cd-tab-badge" id="pcAccountTxCount"><?= (int)($accountTransactions['summary']['total_count'] ?? 0) ?></span>
        </button>
        <button type="button" class="cd-tab-btn" id="tabBtnVouchers" onclick="switchPcTab('vouchers')">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M16 13H8M16 17H8M10 9H8"/></svg>
            Expense Vouchers
            <?php if (!empty($vouchersRes['items'])): ?>
                <span class="cd-tab-badge"><?= count($vouchersRes['items']) ?></span>
            <?php endif; ?>
        </button>
        <button type="button" class="cd-tab-btn" id="tabBtnReimbursements" onclick="switchPcTab('reimbursements')">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
            Reimbursements Log
            <?php if (!empty($reimbursements)): ?>
                <span class="cd-tab-badge"><?= count($reimbursements) ?></span>
            <?php endif; ?>
        </button>
        <button type="button" class="cd-tab-btn" id="tabBtnAdjustments" onclick="switchPcTab('adjustments')">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 3v18M3 12h18"/></svg>
            Adjustments Log
            <?php if (!empty($adjustments)): ?>
                <span class="cd-tab-badge"><?= count($adjustments) ?></span>
            <?php endif; ?>
        </button>
    </div>

    <!-- Tab 1: Real Petty Cash Account Transactions (COA 1015 Ledger) -->
    <div id="tabContentAccountTransactions" class="pc-tab-pane" style="flex: 1; min-height: 0; display: flex; flex-direction: column;">
        <div class="prod-table-panel" style="flex: 1; min-height: 0; display: flex; flex-direction: column;">
            <div style="padding: 12px 18px; border-bottom: 1px solid #e2e8f0; background: rgba(248, 250, 252, 0.8); display: flex; align-items: center; justify-content: space-between; flex-shrink: 0;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0f172a" stroke-width="2.5"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                    <h3 style="font-size: 0.95rem; font-weight: 800; color: #0f172a; margin: 0;">Account 1015 (Petty Cash) - General Ledger Transactions</h3>
                </div>
                <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #64748b; letter-spacing: 0.05em;">
                    <span style="color: #16a34a;">Dr: Inflow</span> &bull; <span style="color: #dc2626;">Cr: Outflow</span>
                </div>
            </div>
            <div class="prod-table-scroll">
                <table class="prod-table" id="accountTransactionsTable">
                    <thead>
                        <tr>
                            <th style="width: 105px;">Date</th>
                            <th style="width: 135px;">Entry #</th>
                            <th style="width: 140px;">Branch</th>
                            <th style="width: 120px;">Source</th>
                            <th>Description / Memo</th>
                            <th class="col-right" style="width: 130px;">Inflow / Dr (LKR)</th>
                            <th class="col-right" style="width: 130px;">Outflow / Cr (LKR)</th>
                            <th class="col-right" style="width: 130px;">Balance (LKR)</th>
                        </tr>
                    </thead>
                    <tbody id="accountTransactionsBody">
                        <tr class="prod-empty"><td colspan="8" style="text-align: center; color: #94a3b8; padding: 36px 16px;">Loading transactions...</td></tr>
                    </tbody>
                </table>
            </div>
            <!-- Pagination Footer (Options: 25, 50, 75, 100, 500 per Rule 6) -->
            <div class="prod-table-footer">
                <div style="font-size: 12px; font-weight: 600; color: #64748b;">
                    Showing <span id="accPagStart">1</span> to <span id="accPagEnd">25</span> of <span id="accPagTotal">0</span> entries
                </div>
                <div style="display: flex; align-items: center; gap: 14px;">
                    <div style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: #64748b; font-weight: 600;">
                        <span>Rows:</span>
                        <select id="accPagSize" onchange="changeAccPageSize()" style="height: 30px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 8px; font-size: 12px; color: #0f172a; background: #fff; outline: none; font-weight: 600; cursor: pointer;">
                            <option value="25" selected>25</option>
                            <option value="50">50</option>
                            <option value="75">75</option>
                            <option value="100">100</option>
                            <option value="500">500</option>
                        </select>
                    </div>
                    <div style="display: flex; align-items: center; gap: 4px;" id="accPagNavBtns"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab 2: Expense Vouchers -->
    <div id="tabContentVouchers" class="pc-tab-pane" style="flex: 1; min-height: 0; display: none; flex-direction: column;">
        <div class="prod-table-panel" style="flex: 1; min-height: 0; display: flex; flex-direction: column;">
            <div style="padding: 12px 18px; border-bottom: 1px solid #e2e8f0; background: rgba(248, 250, 252, 0.8); display: flex; align-items: center; justify-content: space-between; flex-shrink: 0;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0f172a" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    <h3 style="font-size: 0.95rem; font-weight: 800; color: #0f172a; margin: 0;">Expense Vouchers Log</h3>
                </div>
            </div>
            <div class="prod-table-scroll">
                <table class="prod-table" id="vouchersTable">
                    <thead>
                        <tr>
                            <th style="width: 120px;">Voucher #</th>
                            <th style="width: 110px;">Date</th>
                            <th>Category</th>
                            <th style="width: 140px;">Payee</th>
                            <th>Description</th>
                            <th class="col-right" style="width: 130px;">Amount (LKR)</th>
                            <th class="col-center" style="width: 110px;">Status</th>
                            <th style="width: 130px;">Created By</th>
                            <th class="col-center col-action" style="width: 100px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($vouchersRes['items'])): ?>
                            <tr class="prod-empty"><td colspan="9" style="text-align: center; color: #94a3b8; padding: 32px 16px;">No expense vouchers created yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($vouchersRes['items'] as $v): ?>
                            <tr>
                                <td><strong style="font-family: monospace; color: #2563eb; font-size: 12px;"><?= htmlspecialchars($v['voucher_no']) ?></strong></td>
                                <td style="color: #64748b; font-size: 12px; font-weight: 500;"><?= htmlspecialchars($v['voucher_date']) ?></td>
                                <td>
                                    <strong style="color: #0f172a;"><?= htmlspecialchars($v['category_name']) ?></strong>
                                    <br><small style="color: #64748b; font-size: 11px; font-family: monospace;"><?= htmlspecialchars($v['category_code']) ?></small>
                                </td>
                                <td style="font-weight: 600; color: #334155;"><?= htmlspecialchars($v['payee']) ?></td>
                                <td style="color: #475569; font-size: 12px;"><?= htmlspecialchars($v['description']) ?></td>
                                <td class="col-right" style="font-weight: 800; color: #0f172a;"><?= number_format((float)$v['amount'], 2) ?></td>
                                <td class="col-center">
                                    <?php if ($v['status'] === 'posted'): ?>
                                        <span class="prod-badge prod-badge-posted"><span class="dot"></span>POSTED</span>
                                    <?php elseif ($v['status'] === 'draft'): ?>
                                        <span class="prod-badge prod-badge-draft"><span class="dot"></span>DRAFT</span>
                                    <?php else: ?>
                                        <span class="prod-badge prod-badge-reversed"><span class="dot"></span>REVERSED</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-weight: 600; color: #334155; font-size: 12px;"><?= htmlspecialchars($v['creator_name']) ?></td>
                                <td class="col-center col-action">
                                    <?php if ($v['status'] === 'posted' && Auth::hasPermission('reverse_petty_cash_voucher')): ?>
                                        <button type="button" onclick="event.stopPropagation(); openReverseModal(<?= (int)$v['id'] ?>, '<?= htmlspecialchars($v['voucher_no']) ?>')" class="prod-act-btn prod-act-danger" title="Reverse Voucher">
                                            Reverse
                                        </button>
                                    <?php elseif ($v['status'] === 'draft'): ?>
                                        <button type="button" onclick="event.stopPropagation(); handleCancelDraftVoucher(<?= (int)$v['id'] ?>, '<?= htmlspecialchars($v['voucher_no']) ?>')" class="prod-act-btn prod-act-danger" title="Discard Draft">
                                            Discard
                                        </button>
                                    <?php else: ?>
                                        <span style="color: #94a3b8; font-size: 12px;">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- Pagination Footer (Options: 25, 50, 75, 100, 500 per Rule 6) -->
            <div class="prod-table-footer">
                <div style="font-size: 12px; font-weight: 600; color: #64748b;" id="vouchPagInfo">
                    Showing <span id="vouchPagStart">1</span> to <span id="vouchPagEnd">25</span> of <span id="vouchPagTotal">0</span> entries
                </div>
                <div style="display: flex; align-items: center; gap: 14px;">
                    <div style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: #64748b; font-weight: 600;">
                        <span>Rows:</span>
                        <select id="vouchPagSize" onchange="changeVouchPageSize()" style="height: 30px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 8px; font-size: 12px; color: #0f172a; background: #fff; outline: none; font-weight: 600; cursor: pointer;">
                            <option value="25" selected>25</option>
                            <option value="50">50</option>
                            <option value="75">75</option>
                            <option value="100">100</option>
                            <option value="500">500</option>
                        </select>
                    </div>
                    <div style="display: flex; align-items: center; gap: 4px;" id="vouchPagNavBtns"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab 3: Reimbursements Log -->
    <div id="tabContentReimbursements" class="pc-tab-pane" style="flex: 1; min-height: 0; display: none; flex-direction: column;">
        <div class="prod-table-panel" style="flex: 1; min-height: 0; display: flex; flex-direction: column;">
            <div style="padding: 12px 18px; border-bottom: 1px solid #e2e8f0; background: rgba(248, 250, 252, 0.8); display: flex; align-items: center; justify-content: space-between; flex-shrink: 0;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0f172a" stroke-width="2.5"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/></svg>
                    <h3 style="font-size: 0.95rem; font-weight: 800; color: #0f172a; margin: 0;">Fund Reimbursements Log</h3>
                </div>
            </div>
            <div class="prod-table-scroll">
                <table class="prod-table" id="reimbTable">
                    <thead>
                        <tr>
                            <th style="width: 140px;">Reimbursement #</th>
                            <th style="width: 120px;">Date</th>
                            <th style="width: 160px;">Funding Source</th>
                            <th class="col-right" style="width: 130px;">Amount (LKR)</th>
                            <th>Reference No</th>
                            <th>Notes</th>
                            <th style="width: 130px;">User</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reimbursements)): ?>
                            <tr class="prod-empty"><td colspan="7" style="text-align: center; color: #94a3b8; padding: 32px 16px;">No fund reimbursements recorded.</td></tr>
                        <?php else: ?>
                            <?php foreach ($reimbursements as $r): ?>
                            <tr>
                                <td><strong style="font-family: monospace; color: #6d28d9; font-size: 12px;"><?= htmlspecialchars($r['reimbursement_no']) ?></strong></td>
                                <td style="color: #64748b; font-size: 12px; font-weight: 500;"><?= htmlspecialchars($r['reimbursement_date']) ?></td>
                                <td>
                                    <?php if ($r['source_type'] === 'cash_in_hand'): ?>
                                        <span class="prod-badge" style="background: #fef3c7; color: #92400e; border: 1px solid rgba(217,119,6,0.2);"><span class="dot" style="background: #d97706;"></span>Cash in Hand (Safe)</span>
                                    <?php elseif ($r['source_type'] === 'cash_drawer'): ?>
                                        <span class="prod-badge" style="background: #fef3c7; color: #92400e; border: 1px solid rgba(217,119,6,0.2);"><span class="dot" style="background: #d97706;"></span>Cash Drawer</span>
                                    <?php elseif ($r['source_type'] === 'opening_balance'): ?>
                                        <span class="prod-badge" style="background: #f3e8ff; color: #7e22ce; border: 1px solid rgba(147,51,234,0.2);"><span class="dot" style="background: #9333ea;"></span>Opening Equity</span>
                                    <?php else: ?>
                                        <span class="prod-badge" style="background: #dbeafe; color: #1e40af; border: 1px solid rgba(37,99,235,0.2);"><span class="dot" style="background: #2563eb;"></span><?= !empty($r['bank_name']) ? htmlspecialchars($r['bank_name']) : 'Bank Transfer' ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="col-right" style="font-weight: 800; color: #16a34a;"><?= number_format((float)$r['amount'], 2) ?></td>
                                <td style="color: #475569; font-size: 12px;"><?= htmlspecialchars($r['reference_no'] ?? '-') ?></td>
                                <td style="color: #475569; font-size: 12px;"><?= htmlspecialchars($r['notes'] ?? '-') ?></td>
                                <td style="font-weight: 600; color: #334155; font-size: 12px;"><?= htmlspecialchars($r['creator_name']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- Pagination Footer (Options: 25, 50, 75, 100, 500 per Rule 6) -->
            <div class="prod-table-footer">
                <div style="font-size: 12px; font-weight: 600; color: #64748b;" id="reimbPagInfo">
                    Showing <span id="reimbPagStart">1</span> to <span id="reimbPagEnd">25</span> of <span id="reimbPagTotal">0</span> entries
                </div>
                <div style="display: flex; align-items: center; gap: 14px;">
                    <div style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: #64748b; font-weight: 600;">
                        <span>Rows:</span>
                        <select id="reimbPagSize" onchange="changeReimbPageSize()" style="height: 30px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 8px; font-size: 12px; color: #0f172a; background: #fff; outline: none; font-weight: 600; cursor: pointer;">
                            <option value="25" selected>25</option>
                            <option value="50">50</option>
                            <option value="75">75</option>
                            <option value="100">100</option>
                            <option value="500">500</option>
                        </select>
                    </div>
                    <div style="display: flex; align-items: center; gap: 4px;" id="reimbPagNavBtns"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab 4: Adjustments Log -->
    <div id="tabContentAdjustments" class="pc-tab-pane" style="flex: 1; min-height: 0; display: none; flex-direction: column;">
        <div class="prod-table-panel" style="flex: 1; min-height: 0; display: flex; flex-direction: column;">
            <div style="padding: 12px 18px; border-bottom: 1px solid #e2e8f0; background: rgba(248, 250, 252, 0.8); display: flex; align-items: center; justify-content: space-between; flex-shrink: 0;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0f172a" stroke-width="2.5"><path d="M12 3v18M3 12h18"/></svg>
                    <h3 style="font-size: 0.95rem; font-weight: 800; color: #0f172a; margin: 0;">Manual Adjustments Log</h3>
                </div>
            </div>
            <div class="prod-table-scroll">
                <table class="prod-table" id="adjTable">
                    <thead>
                        <tr>
                            <th style="width: 140px;">Adjustment #</th>
                            <th style="width: 140px;">Timestamp</th>
                            <th style="width: 120px;">Type</th>
                            <th class="col-right" style="width: 130px;">Amount (LKR)</th>
                            <th>Reason</th>
                            <th style="width: 130px;">User</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($adjustments)): ?>
                            <tr class="prod-empty"><td colspan="6" style="text-align: center; color: #94a3b8; padding: 32px 16px;">No manual adjustments recorded.</td></tr>
                        <?php else: ?>
                            <?php foreach ($adjustments as $a): ?>
                            <tr>
                                <td><strong style="font-family: monospace; color: #2563eb; font-size: 12px;"><?= htmlspecialchars($a['adjustment_no']) ?></strong></td>
                                <td style="color: #64748b; font-size: 12px; font-weight: 500;"><?= htmlspecialchars($a['created_at']) ?></td>
                                <td>
                                    <?php if ($a['adjustment_type'] === 'positive'): ?>
                                        <span class="prod-badge prod-badge-posted"><span class="dot"></span>+ POSITIVE</span>
                                    <?php else: ?>
                                        <span class="prod-badge prod-badge-reversed"><span class="dot"></span>- NEGATIVE</span>
                                    <?php endif; ?>
                                </td>
                                <td class="col-right" style="font-weight: 800; color: #0f172a;"><?= number_format((float)$a['amount'], 2) ?></td>
                                <td style="color: #475569; font-size: 12px;"><?= htmlspecialchars($a['reason']) ?></td>
                                <td style="font-weight: 600; color: #334155; font-size: 12px;"><?= htmlspecialchars($a['creator_name']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- Pagination Footer (Options: 25, 50, 75, 100, 500 per Rule 6) -->
            <div class="prod-table-footer">
                <div style="font-size: 12px; font-weight: 600; color: #64748b;" id="adjPagInfo">
                    Showing <span id="adjPagStart">1</span> to <span id="adjPagEnd">25</span> of <span id="adjPagTotal">0</span> entries
                </div>
                <div style="display: flex; align-items: center; gap: 14px;">
                    <div style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: #64748b; font-weight: 600;">
                        <span>Rows:</span>
                        <select id="adjPagSize" onchange="changeAdjPageSize()" style="height: 30px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 8px; font-size: 12px; color: #0f172a; background: #fff; outline: none; font-weight: 600; cursor: pointer;">
                            <option value="25" selected>25</option>
                            <option value="50">50</option>
                            <option value="75">75</option>
                            <option value="100">100</option>
                            <option value="500">500</option>
                        </select>
                    </div>
                    <div style="display: flex; align-items: center; gap: 4px;" id="adjPagNavBtns"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal 1: New Expense Voucher -->
<div class="curtiss-modal-backdrop" id="voucherModal">
    <div class="curtiss-modal-card">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; padding-bottom: 10px; border-bottom: 1px solid #f1f5f9;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
                <h3 style="font-size: 1.1rem; font-weight: 800; color: #0f172a; margin: 0;">New Petty Cash Expense Voucher</h3>
            </div>
            <button type="button" onclick="closeModal('voucherModal')" style="background: none; border: none; color: #94a3b8; cursor: pointer; padding: 4px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <form id="voucherForm" onsubmit="handleCreateVoucher(event)">
            <div style="margin-bottom: 16px;">
                <label class="form-label">Expense Category <span style="color: #ef4444;">*</span></label>
                <select id="vCategory" class="form-input" data-create-type="expense_category" required style="font-weight: 700;">
                    <option value="">-- Select Category --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int)$cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?> (<?= htmlspecialchars($cat['account_code']) ?> - <?= htmlspecialchars($cat['account_name']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="margin-bottom: 16px;">
                <label class="form-label">Amount (LKR) <span style="color: #ef4444;">*</span></label>
                <input type="number" step="0.01" min="0.01" id="vAmount" class="form-input" required placeholder="0.00" style="font-size: 1.2rem; font-weight: 800;">
            </div>
            <div style="margin-bottom: 16px;">
                <label class="form-label">Payee / Recipient <span style="color: #ef4444;">*</span></label>
                <input type="text" id="vPayee" class="form-input" placeholder="e.g. Ceypetco Station / Cleaning Worker" required>
            </div>
            <div style="margin-bottom: 16px;">
                <label class="form-label">Description <span style="color: #ef4444;">*</span></label>
                <textarea id="vDescription" class="form-input" rows="2" placeholder="e.g. Generator diesel 10 Litres" required></textarea>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                <div>
                    <label class="form-label">Reference No</label>
                    <input type="text" id="vRefNo" class="form-input" placeholder="e.g. Bill #1234">
                </div>
                <div>
                    <label class="form-label">Receipt Ref</label>
                    <input type="text" id="vReceiptRef" class="form-input" placeholder="e.g. Rec-098">
                </div>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 14px; border-top: 1px solid #f1f5f9;">
                <button type="button" class="inv-btn inv-btn-ghost" onclick="closeModal('voucherModal')">Cancel</button>
                <button type="submit" class="inv-btn inv-btn-primary" id="btnSubmitVoucher">Post Expense Voucher</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: Fund Reimbursement / Top-Up -->
<div class="curtiss-modal-backdrop" id="reimburseModal">
    <div class="curtiss-modal-card">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; padding-bottom: 10px; border-bottom: 1px solid #f1f5f9;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #6d28d9;"></div>
                <h3 style="font-size: 1.1rem; font-weight: 800; color: #0f172a; margin: 0;">Reimburse / Top-Up Petty Cash</h3>
            </div>
            <button type="button" onclick="closeModal('reimburseModal')" style="background: none; border: none; color: #94a3b8; cursor: pointer; padding: 4px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <form id="reimburseForm" onsubmit="handleReimburse(event)">
            <div style="margin-bottom: 16px;">
                <label class="form-label">Funding Source <span style="color: #ef4444;">*</span></label>
                <select id="rSource" class="form-input" required style="font-weight: 700;">
                    <?php if (!empty($bankAccounts)): ?>
                    <optgroup label="Bank Accounts (Direct Bank Transfer)">
                        <?php foreach ($bankAccounts as $ba): ?>
                        <option value="bank_<?= (int)$ba['id'] ?>">
                            Bank Transfer: <?= htmlspecialchars($ba['bank_name']) ?> - <?= htmlspecialchars($ba['account_name']) ?> (<?= htmlspecialchars($ba['account_number']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </optgroup>
                    <?php endif; ?>
                    <optgroup label="Cash Accounts">
                        <option value="cash_in_hand">Cash in Hand (Main Safe) - Account 1010</option>
                        <?php if (!empty($cashAccounts)): ?>
                            <?php foreach ($cashAccounts as $ca): ?>
                                <?php if ($ca['account_code'] !== '1010'): ?>
                                <option value="cash_<?= (int)$ca['id'] ?>">
                                    <?= htmlspecialchars($ca['account_name']) ?> - Account <?= htmlspecialchars($ca['account_code']) ?>
                                </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <option value="cash_drawer">Cash Drawer (POS Register - Requires Open Business Day) - Account 1012</option>
                    </optgroup>
                    <optgroup label="Initial Setup / Migration">
                        <option value="opening_balance">Opening Balance Equity (Initial Setup / Migration) - Account 3010</option>
                    </optgroup>
                </select>
            </div>
            <div style="margin-bottom: 16px;">
                <label class="form-label">Reimbursement Amount (LKR) <span style="color: #ef4444;">*</span></label>
                <input type="number" step="0.01" min="0.01" id="rAmount" class="form-input" required placeholder="0.00" style="font-size: 1.2rem; font-weight: 800;">
            </div>
            <div style="margin-bottom: 16px;">
                <label class="form-label">Cheque / Bank Reference No</label>
                <input type="text" id="rRefNo" class="form-input" placeholder="e.g. CHQ-889900">
            </div>
            <div style="margin-bottom: 20px;">
                <label class="form-label">Notes</label>
                <textarea id="rNotes" class="form-input" rows="2" placeholder="e.g. Weekly petty cash replenishment"></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 14px; border-top: 1px solid #f1f5f9;">
                <button type="button" class="inv-btn inv-btn-ghost" onclick="closeModal('reimburseModal')">Cancel</button>
                <button type="submit" class="inv-btn inv-btn-primary" id="btnSubmitReimburse" style="background: #6d28d9; border-color: #6d28d9;">Confirm Replenishment</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 3: Manage Expense Categories (With Soft-Delete & Trash Bin per Rules 17 & 18) -->
<div class="curtiss-modal-backdrop" id="categoriesModal">
    <div class="curtiss-modal-card" style="max-width: 720px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid #f1f5f9;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
                <h3 style="font-size: 1.1rem; font-weight: 800; color: #0f172a; margin: 0;">Expense Categories & COA Mapping</h3>
            </div>
            <button type="button" onclick="closeModal('categoriesModal')" style="background: none; border: none; color: #94a3b8; cursor: pointer; padding: 4px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <form id="categoryForm" onsubmit="handleSaveCategory(event)" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; margin-bottom: 14px;">
            <input type="hidden" id="catId" value="">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                <div>
                    <label class="form-label">Category Name <span style="color: #ef4444;">*</span></label>
                    <input type="text" id="catName" class="form-input" required placeholder="e.g. Fuel & Travelling">
                </div>
                <div>
                    <label class="form-label">Category Code <span style="color: #ef4444;">*</span></label>
                    <input type="text" id="catCode" class="form-input" required placeholder="e.g. EXP-FUEL">
                </div>
            </div>
            <div style="margin-bottom: 12px;">
                <label class="form-label">Mapped COA Expense Account <span style="color: #ef4444;">*</span></label>
                <select id="catAccountId" class="form-input" required style="font-weight: 600;">
                    <option value="">-- Select Expense COA Account --</option>
                    <?php foreach ($coaExpenseAccounts as $acc): ?>
                        <option value="<?= (int)$acc['id'] ?>">[<?= htmlspecialchars($acc['account_code']) ?>] <?= htmlspecialchars($acc['account_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                <button type="button" onclick="resetCategoryForm()" class="prod-act-btn prod-act-edit">Reset</button>
                <button type="submit" class="prod-act-btn prod-act-primary" id="btnSubmitCategory" style="background: #0f172a; color: #fff;">Save Category</button>
            </div>
        </form>

        <!-- Category View Switcher (Active vs Trash Bin) -->
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
            <div style="display: flex; gap: 6px;">
                <button type="button" id="btnCatViewActive" onclick="switchCategoryView('active')" class="prod-act-btn prod-act-edit" style="background: #0f172a; color: #fff; border-color: #0f172a;">
                    Active Categories (<span id="catActiveCount"><?= count($allCategories) ?></span>)
                </button>
                <button type="button" id="btnCatViewTrash" onclick="switchCategoryView('trash')" class="prod-act-btn prod-act-danger">
                    Trash Bin (<span id="catTrashCount"><?= count($deletedCategories) ?></span>)
                </button>
            </div>
        </div>

        <!-- Active Categories Table -->
        <div id="catActivePanel" style="max-height: 240px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 10px;">
            <table class="prod-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Mapped COA Account</th>
                        <th class="col-center" style="width: 80px;">Status</th>
                        <th class="col-center" style="width: 130px;">Action</th>
                    </tr>
                </thead>
                <tbody id="catActiveTbody">
                    <?php if (empty($allCategories)): ?>
                        <tr class="prod-empty"><td colspan="5" style="text-align: center; color: #94a3b8; padding: 20px;">No categories configured.</td></tr>
                    <?php else: foreach ($allCategories as $c): ?>
                    <tr id="cat-row-<?= (int)$c['id'] ?>">
                        <td><strong style="font-family: monospace; color: #2563eb; font-size: 11.5px;"><?= htmlspecialchars($c['code']) ?></strong></td>
                        <td style="font-weight: 700; color: #0f172a;"><?= htmlspecialchars($c['name']) ?></td>
                        <td style="color: #475569; font-size: 12px;"><?= htmlspecialchars($c['account_code']) ?> - <?= htmlspecialchars($c['account_name']) ?></td>
                        <td class="col-center">
                            <span class="prod-badge prod-badge-posted"><span class="dot"></span><?= htmlspecialchars($c['status']) ?></span>
                        </td>
                        <td class="col-center" style="white-space: nowrap;">
                            <button type="button" onclick="editCategory(<?= htmlspecialchars(json_encode($c)) ?>)" class="prod-act-btn prod-act-edit" style="padding: 2px 8px; font-size: 10.5px; margin-right: 4px;">Edit</button>
                            <button type="button" onclick="handleDeleteCategory(<?= (int)$c['id'] ?>, '<?= htmlspecialchars(addslashes($c['name'])) ?>')" class="prod-act-btn prod-act-danger" style="padding: 2px 8px; font-size: 10.5px;" title="Move to Trash Bin">Delete</button>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Trash Bin Categories Table -->
        <div id="catTrashPanel" style="display: none; max-height: 240px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 10px;">
            <table class="prod-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Deleted At</th>
                        <th class="col-center" style="width: 100px;">Action</th>
                    </tr>
                </thead>
                <tbody id="catTrashTbody">
                    <?php if (empty($deletedCategories)): ?>
                        <tr class="prod-empty"><td colspan="4" style="text-align: center; color: #94a3b8; padding: 20px;">Trash bin is empty.</td></tr>
                    <?php else: foreach ($deletedCategories as $dc): ?>
                    <tr id="cat-trash-row-<?= (int)$dc['id'] ?>">
                        <td><strong style="font-family: monospace; color: #dc2626; font-size: 11.5px;"><?= htmlspecialchars($dc['code']) ?></strong></td>
                        <td style="font-weight: 700; color: #0f172a;"><?= htmlspecialchars($dc['name']) ?></td>
                        <td style="color: #64748b; font-size: 11.5px;"><?= htmlspecialchars($dc['deleted_at']) ?></td>
                        <td class="col-center">
                            <button type="button" onclick="handleRestoreCategory(<?= (int)$dc['id'] ?>, '<?= htmlspecialchars(addslashes($dc['name'])) ?>')" class="prod-act-btn prod-act-success" style="padding: 2px 10px; font-size: 10.5px;" title="Restore Category">Restore</button>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <div style="display: flex; justify-content: flex-end; padding-top: 14px; margin-top: 12px; border-top: 1px solid #f1f5f9;">
            <button type="button" onclick="closeModal('categoriesModal')" class="inv-btn inv-btn-ghost">Close</button>
        </div>
    </div>
</div>

<!-- Modal 4: Manual Adjustment -->
<div class="curtiss-modal-backdrop" id="adjustmentModal">
    <div class="curtiss-modal-card">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; padding-bottom: 10px; border-bottom: 1px solid #f1f5f9;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #2563eb;"></div>
                <h3 style="font-size: 1.1rem; font-weight: 800; color: #0f172a; margin: 0;">Manual Petty Cash Adjustment</h3>
            </div>
            <button type="button" onclick="closeModal('adjustmentModal')" style="background: none; border: none; color: #94a3b8; cursor: pointer; padding: 4px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <form id="adjustmentForm" onsubmit="handleAdjustment(event)">
            <div style="margin-bottom: 16px;">
                <label class="form-label">Adjustment Type <span style="color: #ef4444;">*</span></label>
                <select id="adjType" class="form-input" required style="font-weight: 700;">
                    <option value="positive">+ Positive Adjustment (Increases Petty Cash Balance)</option>
                    <option value="negative">- Negative Adjustment (Decreases Petty Cash Balance)</option>
                </select>
            </div>
            <div style="margin-bottom: 16px;">
                <label class="form-label">Amount (LKR) <span style="color: #ef4444;">*</span></label>
                <input type="number" step="0.01" min="0.01" id="adjAmount" class="form-input" required placeholder="0.00" style="font-size: 1.2rem; font-weight: 800;">
            </div>
            <div style="margin-bottom: 20px;">
                <label class="form-label">Mandatory Reason <span style="color: #ef4444;">*</span></label>
                <textarea id="adjReason" class="form-input" rows="2" required placeholder="e.g. Audit physical count correction"></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 14px; border-top: 1px solid #f1f5f9;">
                <button type="button" class="inv-btn inv-btn-ghost" onclick="closeModal('adjustmentModal')">Cancel</button>
                <button type="submit" class="inv-btn inv-btn-primary" id="btnSubmitAdjustment">Process Adjustment</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 5: Voucher Reversal -->
<div class="curtiss-modal-backdrop" id="reverseModal">
    <div class="curtiss-modal-card">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid #f1f5f9;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #dc2626;"></div>
                <h3 style="font-size: 1.1rem; font-weight: 800; color: #dc2626; margin: 0;">Reverse Expense Voucher</h3>
            </div>
            <button type="button" onclick="closeModal('reverseModal')" style="background: none; border: none; color: #94a3b8; cursor: pointer; padding: 4px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <p style="color: #64748b; font-size: 0.85rem; margin-bottom: 14px;">Voucher No: <strong id="revVoucherNoText" style="color: #0f172a;"></strong></p>
        
        <form id="reverseForm" onsubmit="handleReverseVoucher(event)">
            <input type="hidden" id="revVoucherId" value="">
            <div style="margin-bottom: 20px;">
                <label class="form-label">Reversal Reason <span style="color: #ef4444;">*</span></label>
                <textarea id="revReason" class="form-input" rows="3" required placeholder="e.g. Entered incorrect payee and amount"></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 14px; border-top: 1px solid #f1f5f9;">
                <button type="button" class="inv-btn inv-btn-ghost" onclick="closeModal('reverseModal')">Cancel</button>
                <button type="submit" class="inv-btn" id="btnSubmitReverse" style="background: #dc2626; color: #fff; font-weight: 700;">Confirm Reversal</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 6: View Journal Details (Double-Entry Verification) -->
<div class="curtiss-modal-backdrop" id="journalDetailsModal">
    <div class="curtiss-modal-card" style="max-width: 800px; max-height: 85vh; display: flex; flex-direction: column;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid #f1f5f9; flex-shrink: 0;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #2563eb;"></div>
                <h3 style="font-size: 1.1rem; font-weight: 800; color: #0f172a; margin: 0;" id="jdTitle">Journal Details</h3>
            </div>
            <button type="button" onclick="closeModal('journalDetailsModal')" style="background: none; border: none; color: #94a3b8; cursor: pointer; padding: 4px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div id="jdBody" style="flex: 1; overflow-y: auto;">Loading details...</div>
    </div>
</div>

<script>
    function closeModal(id) {
        const m = document.getElementById(id);
        if (m) m.classList.remove('active');
    }

    function openModal(id) {
        const m = document.getElementById(id);
        if (m) m.classList.add('active');
    }

    function switchPcTab(tab) {
        const btnAcc   = document.getElementById('tabBtnAccountTransactions');
        const btnVouch = document.getElementById('tabBtnVouchers');
        const btnReimb = document.getElementById('tabBtnReimbursements');
        const btnAdj   = document.getElementById('tabBtnAdjustments');

        const paneAcc   = document.getElementById('tabContentAccountTransactions');
        const paneVouch = document.getElementById('tabContentVouchers');
        const paneReimb = document.getElementById('tabContentReimbursements');
        const paneAdj   = document.getElementById('tabContentAdjustments');

        [btnAcc, btnVouch, btnReimb, btnAdj].forEach(b => { if (b) b.classList.remove('active'); });
        [paneAcc, paneVouch, paneReimb, paneAdj].forEach(p => { if (p) p.style.display = 'none'; });

        if (tab === 'account_transactions') {
            if (btnAcc) btnAcc.classList.add('active');
            if (paneAcc) paneAcc.style.display = 'flex';
            renderAccountTransactionsTable();
        } else if (tab === 'vouchers') {
            if (btnVouch) btnVouch.classList.add('active');
            if (paneVouch) paneVouch.style.display = 'flex';
            updateVouchPagination();
        } else if (tab === 'reimbursements') {
            if (btnReimb) btnReimb.classList.add('active');
            if (paneReimb) paneReimb.style.display = 'flex';
            updateReimbPagination();
        } else {
            if (btnAdj) btnAdj.classList.add('active');
            if (paneAdj) paneAdj.style.display = 'flex';
            updateAdjPagination();
        }
    }

    function setFormSubmitting(btnId, isSubmitting, defaultText = 'Submit') {
        const btn = document.getElementById(btnId);
        if (!btn) return;
        btn.disabled = isSubmitting;
        if (isSubmitting) {
            btn.dataset.originalText = btn.textContent;
            btn.textContent = 'Processing...';
        } else {
            btn.textContent = btn.dataset.originalText || defaultText;
        }
    }

    function openNewVoucherModal() {
        const vCatSel = document.getElementById('vCategory');
        if (vCatSel) {
            vCatSel.value = '';
            if (vCatSel._curtissSelect && typeof vCatSel._curtissSelect.refreshOptions === 'function') {
                vCatSel._curtissSelect.refreshOptions();
            }
        }
        document.getElementById('vAmount').value = '';
        document.getElementById('vPayee').value = '';
        document.getElementById('vDescription').value = '';
        document.getElementById('vRefNo').value = '';
        document.getElementById('vReceiptRef').value = '';
        setFormSubmitting('btnSubmitVoucher', false, 'Post Expense Voucher');
        openModal('voucherModal');
    }

    async function handleCreateVoucher(e) {
        e.preventDefault();
        setFormSubmitting('btnSubmitVoucher', true);
        const category_id = parseInt(document.getElementById('vCategory').value);
        const amount = parseFloat(document.getElementById('vAmount').value);
        const payee = document.getElementById('vPayee').value;
        const description = document.getElementById('vDescription').value;
        const reference_no = document.getElementById('vRefNo').value;
        const receipt_ref = document.getElementById('vReceiptRef').value;

        try {
            const res = await fetch('petty_cash_api.php?action=create_voucher', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ category_id, amount, payee, description, reference_no, receipt_ref })
            });
            const data = await res.json();
            if (data.success) {
                alert(data.message);
                closeModal('voucherModal');
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
                setFormSubmitting('btnSubmitVoucher', false);
            }
        } catch (err) {
            alert('Connection error: ' + err.message);
            setFormSubmitting('btnSubmitVoucher', false);
        }
    }

    function openReimburseModal() {
        const rSourceSel = document.getElementById('rSource');
        if (rSourceSel && rSourceSel.options.length > 0) {
            rSourceSel.selectedIndex = 0;
            if (rSourceSel._curtissSelect && typeof rSourceSel._curtissSelect.refreshOptions === 'function') {
                rSourceSel._curtissSelect.refreshOptions();
            } else if (window.CurtissSearchableSelect && typeof CurtissSearchableSelect.sync === 'function') {
                CurtissSearchableSelect.sync(rSourceSel);
            }
        }
        document.getElementById('rAmount').value = '';
        document.getElementById('rRefNo').value = '';
        document.getElementById('rNotes').value = '';
        setFormSubmitting('btnSubmitReimburse', false, 'Confirm Replenishment');
        openModal('reimburseModal');
    }

    async function handleReimburse(e) {
        e.preventDefault();
        setFormSubmitting('btnSubmitReimburse', true);
        const source_type = document.getElementById('rSource').value;
        const amount = parseFloat(document.getElementById('rAmount').value);
        const reference_no = document.getElementById('rRefNo').value;
        const notes = document.getElementById('rNotes').value;

        try {
            const res = await fetch('petty_cash_api.php?action=reimburse_fund', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ source_type, amount, reference_no, notes })
            });
            const data = await res.json();
            if (data.success) {
                alert(data.message);
                closeModal('reimburseModal');
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
                setFormSubmitting('btnSubmitReimburse', false);
            }
        } catch (err) {
            alert('Connection error: ' + err.message);
            setFormSubmitting('btnSubmitReimburse', false);
        }
    }

    function openCategoriesModal() {
        resetCategoryForm();
        switchCategoryView('active');
        openModal('categoriesModal');
    }

    function switchCategoryView(view) {
        const btnActive = document.getElementById('btnCatViewActive');
        const btnTrash = document.getElementById('btnCatViewTrash');
        const panelActive = document.getElementById('catActivePanel');
        const panelTrash = document.getElementById('catTrashPanel');

        if (view === 'active') {
            btnActive.style.background = '#0f172a';
            btnActive.style.color = '#fff';
            btnActive.style.borderColor = '#0f172a';
            btnTrash.style.background = '#fef2f2';
            btnTrash.style.color = '#dc2626';
            btnTrash.style.borderColor = '#fca5a5';
            panelActive.style.display = 'block';
            panelTrash.style.display = 'none';
        } else {
            btnTrash.style.background = '#dc2626';
            btnTrash.style.color = '#fff';
            btnTrash.style.borderColor = '#dc2626';
            btnActive.style.background = '#f1f5f9';
            btnActive.style.color = '#334155';
            btnActive.style.borderColor = '#cbd5e1';
            panelActive.style.display = 'none';
            panelTrash.style.display = 'block';
        }
    }

    function resetCategoryForm() {
        document.getElementById('catId').value = '';
        document.getElementById('catName').value = '';
        document.getElementById('catCode').value = '';
        document.getElementById('catAccountId').value = '';
        setFormSubmitting('btnSubmitCategory', false, 'Save Category');
    }

    function editCategory(cat) {
        document.getElementById('catId').value = cat.id;
        document.getElementById('catName').value = cat.name;
        document.getElementById('catCode').value = cat.code;
        document.getElementById('catAccountId').value = cat.account_id;
        document.getElementById('catName').focus();
    }

    async function handleSaveCategory(e) {
        e.preventDefault();
        setFormSubmitting('btnSubmitCategory', true);
        const id = document.getElementById('catId').value;
        const name = document.getElementById('catName').value;
        const code = document.getElementById('catCode').value;
        const account_id = parseInt(document.getElementById('catAccountId').value);

        try {
            const res = await fetch('petty_cash_api.php?action=save_category', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, name, code, account_id })
            });
            const data = await res.json();
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
                setFormSubmitting('btnSubmitCategory', false);
            }
        } catch (err) {
            alert('Connection error: ' + err.message);
            setFormSubmitting('btnSubmitCategory', false);
        }
    }

    async function handleDeleteCategory(catId, catName) {
        if (!confirm(`Are you sure you want to move category "${catName}" to Trash Bin?`)) return;
        try {
            const res = await fetch('petty_cash_api.php?action=delete_category', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: catId })
            });
            const data = await res.json();
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        } catch (e) {
            alert('Connection error: ' + e.message);
        }
    }

    async function handleRestoreCategory(catId, catName) {
        if (!confirm(`Restore category "${catName}" from Trash Bin?`)) return;
        try {
            const res = await fetch('petty_cash_api.php?action=restore_category', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: catId })
            });
            const data = await res.json();
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        } catch (e) {
            alert('Connection error: ' + e.message);
        }
    }

    function openAdjustmentModal() {
        document.getElementById('adjType').value = 'positive';
        document.getElementById('adjAmount').value = '';
        document.getElementById('adjReason').value = '';
        setFormSubmitting('btnSubmitAdjustment', false, 'Process Adjustment');
        openModal('adjustmentModal');
    }

    async function handleAdjustment(e) {
        e.preventDefault();
        setFormSubmitting('btnSubmitAdjustment', true);
        const adjustment_type = document.getElementById('adjType').value;
        const amount = parseFloat(document.getElementById('adjAmount').value);
        const reason = document.getElementById('adjReason').value;

        try {
            const res = await fetch('petty_cash_api.php?action=adjust_fund', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ adjustment_type, amount, reason })
            });
            const data = await res.json();
            if (data.success) {
                alert(data.message);
                closeModal('adjustmentModal');
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
                setFormSubmitting('btnSubmitAdjustment', false);
            }
        } catch (err) {
            alert('Connection error: ' + err.message);
            setFormSubmitting('btnSubmitAdjustment', false);
        }
    }

    function openReverseModal(id, voucherNo) {
        document.getElementById('revVoucherId').value = id;
        document.getElementById('revVoucherNoText').innerText = voucherNo;
        document.getElementById('revReason').value = '';
        setFormSubmitting('btnSubmitReverse', false, 'Confirm Reversal');
        openModal('reverseModal');
    }

    async function handleReverseVoucher(e) {
        e.preventDefault();
        setFormSubmitting('btnSubmitReverse', true);
        const voucher_id = parseInt(document.getElementById('revVoucherId').value);
        const reason = document.getElementById('revReason').value;

        try {
            const res = await fetch('petty_cash_api.php?action=reverse_voucher', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ voucher_id, reason })
            });
            const data = await res.json();
            if (data.success) {
                alert(data.message);
                closeModal('reverseModal');
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
                setFormSubmitting('btnSubmitReverse', false);
            }
        } catch (err) {
            alert('Connection error: ' + err.message);
            setFormSubmitting('btnSubmitReverse', false);
        }
    }

    async function handleCancelDraftVoucher(id, voucherNo) {
        if (!confirm(`Are you sure you want to discard draft voucher "${voucherNo}"?`)) return;
        try {
            const res = await fetch('petty_cash_api.php?action=cancel_voucher', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ voucher_id: id })
            });
            const data = await res.json();
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        } catch (e) {
            alert('Connection error: ' + e.message);
        }
    }

    /* ── Real COA Petty Cash Account (1015) Transactions State & Logic ── */
    let accTransactions = <?= json_encode($accountTransactions['transactions'] ?? []) ?>;
    let accPage = 1, accPageSize = 25;
    let currentSearchQuery = '';

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderSourceBadge(sourceType) {
        switch (sourceType) {
            case 'supplier_payment':
                return '<span class="prod-badge" style="background:#fef3c7; color:#92400e; border:1px solid rgba(217,119,6,0.2);"><span class="dot" style="background:#d97706;"></span>Supplier Pay</span>';
            case 'petty_cash_voucher':
                return '<span class="prod-badge" style="background:#fee2e2; color:#b91c1c; border:1px solid rgba(239,68,68,0.2);"><span class="dot" style="background:#dc2626;"></span>Voucher</span>';
            case 'petty_cash_reimbursement':
                return '<span class="prod-badge" style="background:#dcfce7; color:#15803d; border:1px solid rgba(22,163,74,0.2);"><span class="dot" style="background:#16a34a;"></span>Top-Up</span>';
            case 'pos_sale':
                return '<span class="prod-badge" style="background:#e0e7ff; color:#3730a3; border:1px solid rgba(99,102,241,0.2);"><span class="dot" style="background:#6366f1;"></span>Sale</span>';
            default:
                return '<span class="prod-badge" style="background:#f1f5f9; color:#475569; border:1px solid rgba(148,163,184,0.3);"><span class="dot" style="background:#64748b;"></span>' + escapeHtml(sourceType || 'Journal') + '</span>';
        }
    }

    function renderAccountTransactionsTable() {
        const tbody = document.getElementById('accountTransactionsBody');
        if (!tbody) return;

        let filtered = accTransactions;
        if (currentSearchQuery) {
            const q = currentSearchQuery.toLowerCase().trim();
            filtered = accTransactions.filter(tx => {
                const dateStr = (tx.entry_date || '').toLowerCase();
                const entryStr = (tx.entry_number || '').toLowerCase();
                const branchStr = (tx.branch_name || '').toLowerCase();
                const srcStr = (tx.source_type || '').toLowerCase();
                const descStr = (tx.description || '').toLowerCase();
                const memoStr = (tx.memo || '').toLowerCase();
                const drStr = (tx.debit || '').toString();
                const crStr = (tx.credit || '').toString();
                const balStr = (tx.running_balance || '').toString();
                const drFmt = (parseFloat(tx.debit) || 0).toLocaleString('en-US', {minimumFractionDigits: 2});
                const crFmt = (parseFloat(tx.credit) || 0).toLocaleString('en-US', {minimumFractionDigits: 2});
                const balFmt = (parseFloat(tx.running_balance) || 0).toLocaleString('en-US', {minimumFractionDigits: 2});

                return dateStr.includes(q) || entryStr.includes(q) || branchStr.includes(q) ||
                       srcStr.includes(q) || descStr.includes(q) || memoStr.includes(q) ||
                       drStr.includes(q) || crStr.includes(q) || balStr.includes(q) ||
                       drFmt.includes(q) || crFmt.includes(q) || balFmt.includes(q);
            });
        }

        const total = filtered.length;
        const totalPages = Math.max(1, Math.ceil(total / accPageSize));
        if (accPage > totalPages) accPage = totalPages;
        if (accPage < 1) accPage = 1;

        const startIdx = total > 0 ? (accPage - 1) * accPageSize : 0;
        const endIdx = Math.min(startIdx + accPageSize, total);
        const pageRows = filtered.slice(startIdx, endIdx);

        if (total === 0) {
            tbody.innerHTML = `<tr class="prod-empty"><td colspan="8" style="text-align: center; color: #94a3b8; padding: 36px 16px;">No transactions found for Petty Cash account (COA 1015).</td></tr>`;
        } else {
            tbody.innerHTML = pageRows.map(tx => {
                const debit = parseFloat(tx.debit) || 0;
                const credit = parseFloat(tx.credit) || 0;
                const bal = parseFloat(tx.running_balance) || 0;
                const balColor = bal >= 0 ? '#059669' : '#dc2626';

                return `
                <tr onclick="viewJournalDetails(${tx.journal_id})" style="cursor: pointer;" title="Click to view journal double-entry details">
                    <td style="color: #64748b; font-size: 12px; font-weight: 500;">${escapeHtml(tx.entry_date)}</td>
                    <td><strong style="font-family: monospace; color: #2563eb; font-size: 12px;">${escapeHtml(tx.entry_number)}</strong></td>
                    <td><span style="font-weight: 600; color: #334155; font-size: 12px;">${escapeHtml(tx.branch_name || '-')}</span></td>
                    <td>${renderSourceBadge(tx.source_type)}</td>
                    <td>
                        <div style="font-weight: 600; color: #0f172a; font-size: 12px;">${escapeHtml(tx.description || '-')}</div>
                        ${tx.memo ? `<small style="color: #64748b; font-size: 11px;">${escapeHtml(tx.memo)}</small>` : ''}
                    </td>
                    <td class="col-right" style="font-weight: 800; color: #16a34a;">${debit > 0 ? debit.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '-'}</td>
                    <td class="col-right" style="font-weight: 800; color: #dc2626;">${credit > 0 ? credit.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '-'}</td>
                    <td class="col-right" style="font-weight: 800; color: ${balColor};">${bal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                </tr>
                `;
            }).join('');
        }

        const elStart = document.getElementById('accPagStart');
        const elEnd = document.getElementById('accPagEnd');
        const elTotal = document.getElementById('accPagTotal');
        if (elStart) elStart.textContent = total > 0 ? startIdx + 1 : 0;
        if (elEnd) elEnd.textContent = endIdx;
        if (elTotal) elTotal.textContent = total;

        renderPaginationNav('accPagNavBtns', accPage, totalPages, (p) => {
            accPage = p;
            renderAccountTransactionsTable();
        });
    }

    function changeAccPageSize() {
        accPageSize = parseInt(document.getElementById('accPagSize').value) || 25;
        accPage = 1;
        renderAccountTransactionsTable();
    }

    async function applyPcBranchFilter() {
        const branchVal = document.getElementById('pcBranchFilter').value;
        await loadAccountTransactions(1, branchVal);
    }

    async function loadAccountTransactions(page = 1, branchVal = null) {
        if (branchVal === null) {
            const sel = document.getElementById('pcBranchFilter');
            branchVal = sel ? sel.value : 'all';
        }
        const tbody = document.getElementById('accountTransactionsBody');
        if (tbody) {
            tbody.innerHTML = `<tr class="prod-empty"><td colspan="8" style="text-align: center; color: #94a3b8; padding: 36px 16px;">Loading transactions...</td></tr>`;
        }
        try {
            const url = `petty_cash_api.php?action=get_account_transactions&branch_id=${encodeURIComponent(branchVal)}&search=${encodeURIComponent(currentSearchQuery)}`;
            const res = await fetch(url);
            const data = await res.json();
            if (data.success && data.data) {
                accTransactions = data.data.transactions || [];
                const sum = data.data.summary || {};
                const curBal = parseFloat(sum.current_balance) || 0;
                const elBal = document.getElementById('kpiCurrentBalance');
                if (elBal) {
                    elBal.textContent = 'LKR ' + curBal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    elBal.style.color = curBal >= 0 ? '#059669' : '#dc2626';
                }
                const elOut = document.getElementById('kpiTotalOutflow');
                if (elOut) {
                    const totalOut = parseFloat(sum.total_outflow) || 0;
                    elOut.textContent = 'LKR ' + totalOut.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                }
                const elIn = document.getElementById('kpiTotalInflow');
                if (elIn) {
                    const totalIn = parseFloat(sum.total_inflow) || 0;
                    elIn.textContent = 'LKR ' + totalIn.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                }
                const elCount = document.getElementById('kpiTxCount');
                if (elCount) {
                    elCount.textContent = (sum.total_count || 0) + ' Total Transactions';
                }
                const elTabBadge = document.getElementById('pcAccountTxCount');
                if (elTabBadge) {
                    elTabBadge.textContent = sum.total_count || 0;
                }
                accPage = page;
                renderAccountTransactionsTable();
            }
        } catch (e) {
            console.error('Failed to load petty cash transactions', e);
        }
    }

    async function viewJournalDetails(id) {
        openModal('journalDetailsModal');
        const jdBody = document.getElementById('jdBody');
        if (jdBody) jdBody.innerHTML = '<p style="color: #64748b; padding: 20px 0;">Loading journal entry details...</p>';
        try {
            const res = await fetch(`accounting_api.php?action=get_journal_details&id=${id}`);
            const data = await res.json();
            if (!data.success || !data.data) {
                if (jdBody) jdBody.innerHTML = '<p style="color:#dc2626; font-weight:700;">Failed to load journal entry details.</p>';
                return;
            }
            const j = data.data;
            document.getElementById('jdTitle').innerText = `Journal Entry ${j.entry_number}`;
            jdBody.innerHTML = `
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:12px 16px; margin-bottom:14px; font-size:12.5px;">
                    <p style="margin:0 0 6px 0;"><strong>Date:</strong> ${escapeHtml(j.entry_date)} | <strong>Branch:</strong> ${escapeHtml(j.branch_name)} | <strong>Posted By:</strong> ${escapeHtml(j.creator_name)}</p>
                    <p style="margin:0 0 6px 0;"><strong>Source:</strong> ${escapeHtml(j.source_type)} (ID: ${escapeHtml(j.source_id || 'N/A')})</p>
                    <p style="margin:0;"><strong>Description:</strong> ${escapeHtml(j.description)}</p>
                </div>
                <table class="prod-table" style="margin-bottom:14px;">
                    <thead>
                        <tr>
                            <th>Account Code & Name</th>
                            <th class="col-right">Debit (LKR)</th>
                            <th class="col-right">Credit (LKR)</th>
                            <th>Memo</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${j.lines.map(l => `
                            <tr>
                                <td><strong style="color:#2563eb;">${escapeHtml(l.account_code)}</strong> - ${escapeHtml(l.account_name)}</td>
                                <td class="col-right" style="font-weight:800; color:#16a34a;">${parseFloat(l.debit) > 0 ? parseFloat(l.debit).toLocaleString('en-US', {minimumFractionDigits:2}) : '-'}</td>
                                <td class="col-right" style="font-weight:800; color:#dc2626;">${parseFloat(l.credit) > 0 ? parseFloat(l.credit).toLocaleString('en-US', {minimumFractionDigits:2}) : '-'}</td>
                                <td style="color:#64748b; font-size:12px;">${escapeHtml(l.memo || '')}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                    <tfoot>
                        <tr style="font-weight:800; background:#f8fafc; border-top:2px solid #e2e8f0;">
                            <td>TOTAL</td>
                            <td class="col-right" style="color:#16a34a;">${parseFloat(j.total_debit).toLocaleString('en-US', {minimumFractionDigits:2})}</td>
                            <td class="col-right" style="color:#dc2626;">${parseFloat(j.total_credit).toLocaleString('en-US', {minimumFractionDigits:2})}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            `;
        } catch (e) {
            if (jdBody) jdBody.innerHTML = '<p style="color:#dc2626; font-weight:700;">Error fetching details: ' + escapeHtml(e.message) + '</p>';
        }
    }

    /* ── Client-Side Dynamic Table Pagination (Standard 25, 50, 75, 100, 500) ── */
    let vouchPage = 1, vouchPageSize = 25;
    let reimbPage = 1, reimbPageSize = 25;
    let adjPage   = 1, adjPageSize   = 25;

    function updateVouchPagination() {
        const table = document.getElementById('vouchersTable');
        if (!table) return;
        const allRows = Array.from(table.querySelectorAll('tbody tr')).filter(tr => !tr.classList.contains('prod-empty'));
        const visibleRows = allRows.filter(tr => tr.dataset.searchMatched !== 'false');
        const total = visibleRows.length;
        const totalPages = Math.max(1, Math.ceil(total / vouchPageSize));
        if (vouchPage > totalPages) vouchPage = totalPages;
        if (vouchPage < 1) vouchPage = 1;

        const startIdx = total > 0 ? (vouchPage - 1) * vouchPageSize : 0;
        const endIdx = Math.min(startIdx + vouchPageSize, total);

        allRows.forEach(tr => tr.style.display = 'none');
        for (let i = startIdx; i < endIdx; i++) {
            if (visibleRows[i]) visibleRows[i].style.display = '';
        }

        const elStart = document.getElementById('vouchPagStart');
        const elEnd = document.getElementById('vouchPagEnd');
        const elTotal = document.getElementById('vouchPagTotal');
        if (elStart) elStart.textContent = total > 0 ? startIdx + 1 : 0;
        if (elEnd) elEnd.textContent = endIdx;
        if (elTotal) elTotal.textContent = total;

        renderPaginationNav('vouchPagNavBtns', vouchPage, totalPages, (p) => { vouchPage = p; updateVouchPagination(); });
    }

    function changeVouchPageSize() {
        vouchPageSize = parseInt(document.getElementById('vouchPagSize').value) || 25;
        vouchPage = 1;
        updateVouchPagination();
    }

    function updateReimbPagination() {
        const table = document.getElementById('reimbTable');
        if (!table) return;
        const allRows = Array.from(table.querySelectorAll('tbody tr')).filter(tr => !tr.classList.contains('prod-empty'));
        const visibleRows = allRows.filter(tr => tr.dataset.searchMatched !== 'false');
        const total = visibleRows.length;
        const totalPages = Math.max(1, Math.ceil(total / reimbPageSize));
        if (reimbPage > totalPages) reimbPage = totalPages;
        if (reimbPage < 1) reimbPage = 1;

        const startIdx = total > 0 ? (reimbPage - 1) * reimbPageSize : 0;
        const endIdx = Math.min(startIdx + reimbPageSize, total);

        allRows.forEach(tr => tr.style.display = 'none');
        for (let i = startIdx; i < endIdx; i++) {
            if (visibleRows[i]) visibleRows[i].style.display = '';
        }

        const elStart = document.getElementById('reimbPagStart');
        const elEnd = document.getElementById('reimbPagEnd');
        const elTotal = document.getElementById('reimbPagTotal');
        if (elStart) elStart.textContent = total > 0 ? startIdx + 1 : 0;
        if (elEnd) elEnd.textContent = endIdx;
        if (elTotal) elTotal.textContent = total;

        renderPaginationNav('reimbPagNavBtns', reimbPage, totalPages, (p) => { reimbPage = p; updateReimbPagination(); });
    }

    function changeReimbPageSize() {
        reimbPageSize = parseInt(document.getElementById('reimbPagSize').value) || 25;
        reimbPage = 1;
        updateReimbPagination();
    }

    function updateAdjPagination() {
        const table = document.getElementById('adjTable');
        if (!table) return;
        const allRows = Array.from(table.querySelectorAll('tbody tr')).filter(tr => !tr.classList.contains('prod-empty'));
        const visibleRows = allRows.filter(tr => tr.dataset.searchMatched !== 'false');
        const total = visibleRows.length;
        const totalPages = Math.max(1, Math.ceil(total / adjPageSize));
        if (adjPage > totalPages) adjPage = totalPages;
        if (adjPage < 1) adjPage = 1;

        const startIdx = total > 0 ? (adjPage - 1) * adjPageSize : 0;
        const endIdx = Math.min(startIdx + adjPageSize, total);

        allRows.forEach(tr => tr.style.display = 'none');
        for (let i = startIdx; i < endIdx; i++) {
            if (visibleRows[i]) visibleRows[i].style.display = '';
        }

        const elStart = document.getElementById('adjPagStart');
        const elEnd = document.getElementById('adjPagEnd');
        const elTotal = document.getElementById('adjPagTotal');
        if (elStart) elStart.textContent = total > 0 ? startIdx + 1 : 0;
        if (elEnd) elEnd.textContent = endIdx;
        if (elTotal) elTotal.textContent = total;

        renderPaginationNav('adjPagNavBtns', adjPage, totalPages, (p) => { adjPage = p; updateAdjPagination(); });
    }

    function changeAdjPageSize() {
        adjPageSize = parseInt(document.getElementById('adjPagSize').value) || 25;
        adjPage = 1;
        updateAdjPagination();
    }

    function renderPaginationNav(containerId, currentPage, totalPages, onPageClick) {
        const container = document.getElementById(containerId);
        if (!container) return;
        container.innerHTML = '';

        const prevBtn = document.createElement('button');
        prevBtn.className = 'prod-act-btn prod-act-edit';
        prevBtn.style.padding = '0 10px';
        prevBtn.disabled = currentPage === 1;
        prevBtn.innerHTML = `&larr; Prev`;
        prevBtn.onclick = () => { if (currentPage > 1) onPageClick(currentPage - 1); };
        container.appendChild(prevBtn);

        let startP = Math.max(1, currentPage - 2);
        let endP = Math.min(totalPages, startP + 4);
        if (endP - startP < 4) startP = Math.max(1, endP - 4);

        for (let p = startP; p <= endP; p++) {
            const pBtn = document.createElement('button');
            pBtn.className = 'prod-act-btn ' + (p === currentPage ? 'prod-act-primary' : 'prod-act-edit');
            pBtn.style.padding = '0 10px';
            pBtn.style.minWidth = '28px';
            pBtn.style.justifyContent = 'center';
            if (p === currentPage) {
                pBtn.style.background = '#0f172a';
                pBtn.style.color = '#ffffff';
                pBtn.style.borderColor = '#0f172a';
            }
            pBtn.textContent = p;
            pBtn.onclick = () => onPageClick(p);
            container.appendChild(pBtn);
        }

        const nextBtn = document.createElement('button');
        nextBtn.className = 'prod-act-btn prod-act-edit';
        nextBtn.style.padding = '0 10px';
        nextBtn.disabled = currentPage === totalPages;
        nextBtn.innerHTML = `Next &rarr;`;
        nextBtn.onclick = () => { if (currentPage < totalPages) onPageClick(currentPage + 1); };
        container.appendChild(nextBtn);
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Attach CurtissSearchableSelect to filter dropdowns
        const branchSel = document.getElementById('pcBranchFilter');
        if (branchSel && window.CurtissSearchableSelect) {
            CurtissSearchableSelect.attach(branchSel);
        }
        const vCatSel = document.getElementById('vCategory');
        if (vCatSel && window.CurtissSearchableSelect) {
            CurtissSearchableSelect.attach(vCatSel);
        }
        const catAccSel = document.getElementById('catAccountId');
        if (catAccSel && window.CurtissSearchableSelect) {
            CurtissSearchableSelect.attach(catAccSel);
        }
        const rSourceSel = document.getElementById('rSource');
        if (rSourceSel && window.CurtissSearchableSelect) {
            CurtissSearchableSelect.attach(rSourceSel);
        }
        const adjTypeSel = document.getElementById('adjType');
        if (adjTypeSel && window.CurtissSearchableSelect) {
            CurtissSearchableSelect.attach(adjTypeSel);
        }

        // Setup keyboard navigation on forms
        ['voucherForm', 'reimburseForm', 'categoryForm', 'adjustmentForm', 'reverseForm'].forEach(setupKeyboardNavigation);

        renderAccountTransactionsTable();
        updateVouchPagination();
        updateReimbPagination();
        updateAdjPagination();
    });

    function setupKeyboardNavigation(formId) {
        const form = document.getElementById(formId);
        if (!form) return;
        const inputs = Array.from(form.querySelectorAll('input:not([type="hidden"]), select, textarea, button[type="submit"]'))
                            .filter(el => !el.disabled && el.offsetParent !== null);
        inputs.forEach((input, idx) => {
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
                    e.preventDefault();
                    const next = inputs[idx + 1];
                    if (next) {
                        if (next.type === 'submit') {
                            next.click();
                        } else {
                            next.focus();
                            if (next.select) next.select();
                        }
                    } else {
                        form.requestSubmit();
                    }
                }
            });
        });
    }

    /* ── Realtime Search Hook into Floating Bottom Nav Bar ── */
    function registerBottomSearch() {
        if (window.CurtissNav && window.CurtissNav.enablePageSearch) {
            window.CurtissNav.enablePageSearch({
                placeholder: 'Search petty cash transactions, vouchers & entries...',
                onSearch: function(query) {
                    filterPettyCashRealtime(query);
                }
            });
        } else {
            setTimeout(registerBottomSearch, 40);
        }
    }
    registerBottomSearch();

    function filterPettyCashRealtime(query) {
        currentSearchQuery = query;
        renderAccountTransactionsTable();

        const q = query.toLowerCase().trim();
        ['#vouchersTable', '#reimbTable', '#adjTable'].forEach(tableId => {
            const rows = document.querySelectorAll(tableId + ' tbody tr');
            rows.forEach(tr => {
                if (tr.classList.contains('prod-empty')) return;
                // Exclude action column buttons/text from search matching
                const searchableCells = Array.from(tr.querySelectorAll('td:not(.col-action)'));
                const text = searchableCells.map(c => c.textContent).join(' ').toLowerCase();
                tr.dataset.searchMatched = (!q || text.includes(q)) ? 'true' : 'false';
            });
        });

        vouchPage = 1; updateVouchPagination();
        reimbPage = 1; updateReimbPagination();
        adjPage   = 1; updateAdjPagination();
    }
</script>
