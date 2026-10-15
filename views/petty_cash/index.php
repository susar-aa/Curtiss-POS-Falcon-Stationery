<?php
/**
 * Curtiss POS - Petty Cash & Expense Management Master View (Stage 12 Redesign)
 * Glassmorphic ERP Interface for branch petty cash fund tracking, expense vouchers creation,
 * COA-mapped expense categories, bank/drawer reimbursements, adjustments & voucher reversals.
 */
?>
<link rel="stylesheet" href="assets/css/curtiss_select.css">
<script src="assets/js/curtiss_select.js"></script>

<!-- Floating Top-Right Action Header Buttons -->
<div id="curtiss-floating-header-right" class="no-print" style="position: fixed; top: 16px; right: 20px; z-index: 1050; display: flex; align-items: center; gap: 8px;">
    <?php if (Auth::hasPermission('create_expense')): ?>
    <button type="button" class="inv-btn inv-btn-primary" onclick="openNewVoucherModal()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        + New Expense Voucher
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

    /* Button System */
    .inv-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
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
        gap: 1.25rem;
        margin-bottom: 1.5rem;
    }
    .pc-kpi-card {
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(12px);
        border-radius: 14px;
        padding: 1.25rem;
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
        margin-bottom: 0.35rem;
    }
    .kpi-val {
        font-size: 1.6rem;
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
        margin-bottom: 1.25rem;
    }
    .cd-tab-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        border: none;
        background: transparent;
        font-size: 13px;
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

    /* Status Badges */
    .prod-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 9px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        line-height: 1.2;
        white-space: nowrap;
    }
    .prod-badge-posted   { background: #dcfce7; color: #15803d; border: 1px solid rgba(22,163,74,0.2); }
    .prod-badge-reversed { background: #fee2e2; color: #b91c1c; border: 1px solid rgba(220,38,38,0.2); }
    .prod-badge .dot {
        width: 5px; height: 5px; border-radius: 50%; display: inline-block;
    }
    .prod-badge-posted   .dot { background: #16a34a; }
    .prod-badge-reversed .dot { background: #dc2626; }

    /* Table Panels */
    .prod-table-panel {
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid rgba(226, 232, 240, 0.85);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
        margin-bottom: 1.5rem;
    }
    .prod-table-scroll { overflow-x: auto; }
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
    }
    .prod-table tbody tr {
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.12s ease;
    }
    .prod-table tbody tr:nth-child(even) { background: rgba(248, 250, 252, 0.57); }
    .prod-table tbody tr:nth-child(odd)  { background: #ffffff; }
    .prod-table tbody tr:hover            { background: #f1f5f9 !important; }
    .prod-table td {
        padding: 12px 14px;
        font-size: 12.5px;
        color: #1e293b;
        vertical-align: middle;
    }

    /* Table Footer Pagination */
    .prod-table-footer {
        padding: 12px 18px;
        border-top: 1px solid #e2e8f0;
        background: #ffffff;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
    }
    .prod-act-btn {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 5px 12px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        border: 1px solid transparent;
        cursor: pointer;
        transition: all 0.15s ease;
        font-family: inherit;
        text-decoration: none;
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
        background: #fee2e2;
        border-color: #fca5a5;
        color: #b91c1c;
    }
    .prod-act-danger:hover {
        background: #fca5a5;
        color: #7f1d1d;
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

<div style="width: 100%; max-width: 100%; padding-bottom: 24px;">
    <!-- Page Header (Headline Only per Rule 5 with Inline Filter per Rule 3) -->
    <div class="inv-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 1.25rem;">
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
    <div class="pc-kpi-grid">
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
    <div class="cd-tabs-nav">
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
    <div id="tabContentAccountTransactions" class="pc-tab-pane">
        <div class="prod-table-panel">
            <div style="padding: 14px 18px; border-bottom: 1px solid #e2e8f0; background: rgba(248, 250, 252, 0.8); display: flex; align-items: center; justify-content: space-between;">
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
                            <th style="width: 105px; text-align: left;">Date</th>
                            <th style="width: 135px; text-align: left;">Entry #</th>
                            <th style="width: 140px; text-align: left;">Branch</th>
                            <th style="width: 120px; text-align: left;">Source</th>
                            <th style="text-align: left;">Description / Memo</th>
                            <th class="col-right" style="width: 125px; text-align: right;">Inflow / Dr (LKR)</th>
                            <th class="col-right" style="width: 125px; text-align: right;">Outflow / Cr (LKR)</th>
                            <th class="col-right" style="width: 125px; text-align: right;">Balance (LKR)</th>
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
    <div id="tabContentVouchers" class="pc-tab-pane" style="display: none;">
        <div class="prod-table-panel">
            <div style="padding: 14px 18px; border-bottom: 1px solid #e2e8f0; background: rgba(248, 250, 252, 0.8); display: flex; align-items: center; justify-content: space-between;">
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
                            <th style="width: 130px;">Amount (LKR)</th>
                            <th style="width: 110px; text-align: center;">Status</th>
                            <th style="width: 130px;">Created By</th>
                            <th style="width: 90px; text-align: center;">Action</th>
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
                                <td style="font-weight: 800; color: #0f172a;">LKR <?= number_format((float)$v['amount'], 2) ?></td>
                                <td style="text-align: center;">
                                    <?php if ($v['status'] === 'posted'): ?>
                                        <span class="prod-badge prod-badge-posted"><span class="dot"></span>POSTED</span>
                                    <?php else: ?>
                                        <span class="prod-badge prod-badge-reversed"><span class="dot"></span>REVERSED</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-weight: 600; color: #334155; font-size: 12px;"><?= htmlspecialchars($v['creator_name']) ?></td>
                                <td style="text-align: center;">
                                    <?php if ($v['status'] === 'posted' && Auth::hasPermission('reverse_petty_cash_voucher')): ?>
                                        <button type="button" onclick="openReverseModal(<?= (int)$v['id'] ?>, '<?= htmlspecialchars($v['voucher_no']) ?>')" class="prod-act-btn prod-act-danger" title="Reverse Voucher">
                                            Reverse
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
            <!-- Pagination Footer -->
            <div class="prod-table-footer">
                <div style="font-size: 12px; font-weight: 600; color: #64748b;" id="vouchPagInfo">
                    Showing <span id="vouchPagStart">1</span> to <span id="vouchPagEnd">10</span> of <span id="vouchPagTotal">0</span> entries
                </div>
                <div style="display: flex; align-items: center; gap: 14px;">
                    <div style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: #64748b; font-weight: 600;">
                        <span>Rows:</span>
                        <select id="vouchPagSize" onchange="changeVouchPageSize()" style="height: 30px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 8px; font-size: 12px; color: #0f172a; background: #fff; outline: none; font-weight: 600; cursor: pointer;">
                            <option value="10" selected>10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                    <div style="display: flex; align-items: center; gap: 4px;" id="vouchPagNavBtns"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab 2: Reimbursements Log -->
    <div id="tabContentReimbursements" class="pc-tab-pane" style="display: none;">
        <div class="prod-table-panel">
            <div style="padding: 14px 18px; border-bottom: 1px solid #e2e8f0; background: rgba(248, 250, 252, 0.8); display: flex; align-items: center; justify-content: space-between;">
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
                            <th style="width: 130px;">Amount (LKR)</th>
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
                                        <span class="prod-badge" style="background: #fef3c7; color: #92400e; border: 1px solid rgba(217,119,6,0.2);"><span class="dot" style="background: #d97706;"></span>Cash Drawer</span>
                                    <?php else: ?>
                                        <span class="prod-badge" style="background: #dbeafe; color: #1e40af; border: 1px solid rgba(37,99,235,0.2);"><span class="dot" style="background: #2563eb;"></span>Bank Account</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-weight: 800; color: #16a34a;">LKR <?= number_format((float)$r['amount'], 2) ?></td>
                                <td style="color: #475569; font-size: 12px;"><?= htmlspecialchars($r['reference_no'] ?? '-') ?></td>
                                <td style="color: #475569; font-size: 12px;"><?= htmlspecialchars($r['notes'] ?? '-') ?></td>
                                <td style="font-weight: 600; color: #334155; font-size: 12px;"><?= htmlspecialchars($r['creator_name']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- Pagination Footer -->
            <div class="prod-table-footer">
                <div style="font-size: 12px; font-weight: 600; color: #64748b;" id="reimbPagInfo">
                    Showing <span id="reimbPagStart">1</span> to <span id="reimbPagEnd">10</span> of <span id="reimbPagTotal">0</span> entries
                </div>
                <div style="display: flex; align-items: center; gap: 14px;">
                    <div style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: #64748b; font-weight: 600;">
                        <span>Rows:</span>
                        <select id="reimbPagSize" onchange="changeReimbPageSize()" style="height: 30px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 8px; font-size: 12px; color: #0f172a; background: #fff; outline: none; font-weight: 600; cursor: pointer;">
                            <option value="10" selected>10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                    <div style="display: flex; align-items: center; gap: 4px;" id="reimbPagNavBtns"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab 3: Adjustments Log -->
    <div id="tabContentAdjustments" class="pc-tab-pane" style="display: none;">
        <div class="prod-table-panel">
            <div style="padding: 14px 18px; border-bottom: 1px solid #e2e8f0; background: rgba(248, 250, 252, 0.8); display: flex; align-items: center; justify-content: space-between;">
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
                            <th style="width: 130px;">Amount (LKR)</th>
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
                                <td style="font-weight: 800; color: #0f172a;">LKR <?= number_format((float)$a['amount'], 2) ?></td>
                                <td style="color: #475569; font-size: 12px;"><?= htmlspecialchars($a['reason']) ?></td>
                                <td style="font-weight: 600; color: #334155; font-size: 12px;"><?= htmlspecialchars($a['creator_name']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- Pagination Footer -->
            <div class="prod-table-footer">
                <div style="font-size: 12px; font-weight: 600; color: #64748b;" id="adjPagInfo">
                    Showing <span id="adjPagStart">1</span> to <span id="adjPagEnd">10</span> of <span id="adjPagTotal">0</span> entries
                </div>
                <div style="display: flex; align-items: center; gap: 14px;">
                    <div style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: #64748b; font-weight: 600;">
                        <span>Rows:</span>
                        <select id="adjPagSize" onchange="changeAdjPageSize()" style="height: 30px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 8px; font-size: 12px; color: #0f172a; background: #fff; outline: none; font-weight: 600; cursor: pointer;">
                            <option value="10" selected>10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
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
                <select id="vCategory" class="form-input" required style="font-weight: 700;">
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
                <button type="submit" class="inv-btn inv-btn-primary">Post Expense Voucher</button>
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
                    <option value="bank">Bank Account Withdrawal (Does NOT affect Cash Drawer)</option>
                    <option value="cash_in_hand">Cash in Hand Drawer (Requires Open Business Day)</option>
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
                <button type="submit" class="inv-btn inv-btn-primary" style="background: #6d28d9; border-color: #6d28d9;">Confirm Replenishment</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 3: Manage Expense Categories -->
<div class="curtiss-modal-backdrop" id="categoriesModal">
    <div class="curtiss-modal-card" style="max-width: 680px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; padding-bottom: 10px; border-bottom: 1px solid #f1f5f9;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
                <h3 style="font-size: 1.1rem; font-weight: 800; color: #0f172a; margin: 0;">Expense Categories & COA Mapping</h3>
            </div>
            <button type="button" onclick="closeModal('categoriesModal')" style="background: none; border: none; color: #94a3b8; cursor: pointer; padding: 4px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <form id="categoryForm" onsubmit="handleSaveCategory(event)" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; margin-bottom: 16px;">
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
                <button type="submit" class="prod-act-btn prod-act-primary" style="background: #0f172a; color: #fff;">Save Category</button>
            </div>
        </form>

        <div style="max-height: 240px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 10px;">
            <table class="prod-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Mapped COA Account</th>
                        <th style="width: 80px; text-align: center;">Status</th>
                        <th style="width: 70px; text-align: center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allCategories as $c): ?>
                    <tr>
                        <td><strong style="font-family: monospace; color: #2563eb; font-size: 11.5px;"><?= htmlspecialchars($c['code']) ?></strong></td>
                        <td style="font-weight: 700; color: #0f172a;"><?= htmlspecialchars($c['name']) ?></td>
                        <td style="color: #475569; font-size: 12px;"><?= htmlspecialchars($c['account_code']) ?> - <?= htmlspecialchars($c['account_name']) ?></td>
                        <td style="text-align: center;">
                            <span class="prod-badge prod-badge-posted"><span class="dot"></span><?= htmlspecialchars($c['status']) ?></span>
                        </td>
                        <td style="text-align: center;">
                            <button type="button" onclick="editCategory(<?= htmlspecialchars(json_encode($c)) ?>)" class="prod-act-btn prod-act-edit" style="padding: 3px 8px; font-size: 10.5px;">Edit</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
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
                <button type="submit" class="inv-btn inv-btn-primary">Process Adjustment</button>
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
                <button type="submit" class="inv-btn" style="background: #dc2626; color: #fff; font-weight: 700;">Confirm Reversal</button>
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
            if (paneAcc) paneAcc.style.display = 'block';
            renderAccountTransactionsTable();
        } else if (tab === 'vouchers') {
            if (btnVouch) btnVouch.classList.add('active');
            if (paneVouch) paneVouch.style.display = 'block';
            updateVouchPagination();
        } else if (tab === 'reimbursements') {
            if (btnReimb) btnReimb.classList.add('active');
            if (paneReimb) paneReimb.style.display = 'block';
            updateReimbPagination();
        } else {
            if (btnAdj) btnAdj.classList.add('active');
            if (paneAdj) paneAdj.style.display = 'block';
            updateAdjPagination();
        }
    }

    function openNewVoucherModal() {
        document.getElementById('vCategory').value = '';
        document.getElementById('vAmount').value = '';
        document.getElementById('vPayee').value = '';
        document.getElementById('vDescription').value = '';
        document.getElementById('vRefNo').value = '';
        document.getElementById('vReceiptRef').value = '';
        openModal('voucherModal');
    }

    async function handleCreateVoucher(e) {
        e.preventDefault();
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
            }
        } catch (err) {
            alert('Connection error: ' + err.message);
        }
    }

    function openReimburseModal() {
        document.getElementById('rSource').value = 'bank';
        document.getElementById('rAmount').value = '';
        document.getElementById('rRefNo').value = '';
        document.getElementById('rNotes').value = '';
        openModal('reimburseModal');
    }

    async function handleReimburse(e) {
        e.preventDefault();
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
            }
        } catch (err) {
            alert('Connection error: ' + err.message);
        }
    }

    function openCategoriesModal() {
        resetCategoryForm();
        openModal('categoriesModal');
    }

    function resetCategoryForm() {
        document.getElementById('catId').value = '';
        document.getElementById('catName').value = '';
        document.getElementById('catCode').value = '';
        document.getElementById('catAccountId').value = '';
    }

    function editCategory(cat) {
        document.getElementById('catId').value = cat.id;
        document.getElementById('catName').value = cat.name;
        document.getElementById('catCode').value = cat.code;
        document.getElementById('catAccountId').value = cat.account_id;
    }

    async function handleSaveCategory(e) {
        e.preventDefault();
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
            }
        } catch (err) {
            alert('Connection error: ' + err.message);
        }
    }

    function openAdjustmentModal() {
        document.getElementById('adjType').value = 'positive';
        document.getElementById('adjAmount').value = '';
        document.getElementById('adjReason').value = '';
        openModal('adjustmentModal');
    }

    async function handleAdjustment(e) {
        e.preventDefault();
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
            }
        } catch (err) {
            alert('Connection error: ' + err.message);
        }
    }

    function openReverseModal(id, voucherNo) {
        document.getElementById('revVoucherId').value = id;
        document.getElementById('revVoucherNoText').innerText = voucherNo;
        document.getElementById('revReason').value = '';
        openModal('reverseModal');
    }

    async function handleReverseVoucher(e) {
        e.preventDefault();
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
            }
        } catch (err) {
            alert('Connection error: ' + err.message);
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
                return dateStr.includes(q) || entryStr.includes(q) || branchStr.includes(q) ||
                       srcStr.includes(q) || descStr.includes(q) || memoStr.includes(q) ||
                       drStr.includes(q) || crStr.includes(q) || balStr.includes(q);
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
                    <td class="col-right" style="font-weight: 800; color: #16a34a; text-align: right;">${debit > 0 ? debit.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '-'}</td>
                    <td class="col-right" style="font-weight: 800; color: #dc2626; text-align: right;">${credit > 0 ? credit.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '-'}</td>
                    <td class="col-right" style="font-weight: 800; color: ${balColor}; text-align: right;">${bal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
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
                            <th style="text-align: left;">Account Code & Name</th>
                            <th class="col-right" style="text-align:right;">Debit (LKR)</th>
                            <th class="col-right" style="text-align:right;">Credit (LKR)</th>
                            <th style="text-align: left;">Memo</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${j.lines.map(l => `
                            <tr>
                                <td><strong style="color:#2563eb;">${escapeHtml(l.account_code)}</strong> - ${escapeHtml(l.account_name)}</td>
                                <td class="col-right" style="text-align:right; font-weight:800; color:#16a34a;">${parseFloat(l.debit) > 0 ? parseFloat(l.debit).toLocaleString('en-US', {minimumFractionDigits:2}) : '-'}</td>
                                <td class="col-right" style="text-align:right; font-weight:800; color:#dc2626;">${parseFloat(l.credit) > 0 ? parseFloat(l.credit).toLocaleString('en-US', {minimumFractionDigits:2}) : '-'}</td>
                                <td style="color:#64748b; font-size:12px;">${escapeHtml(l.memo || '')}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                    <tfoot>
                        <tr style="font-weight:800; background:#f8fafc; border-top:2px solid #e2e8f0;">
                            <td>TOTAL</td>
                            <td class="col-right" style="text-align:right; color:#16a34a;">${parseFloat(j.total_debit).toLocaleString('en-US', {minimumFractionDigits:2})}</td>
                            <td class="col-right" style="text-align:right; color:#dc2626;">${parseFloat(j.total_credit).toLocaleString('en-US', {minimumFractionDigits:2})}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            `;
        } catch (e) {
            if (jdBody) jdBody.innerHTML = '<p style="color:#dc2626; font-weight:700;">Error fetching details: ' + escapeHtml(e.message) + '</p>';
        }
    }

    /* ── Client-Side Dynamic Table Pagination ── */
    let vouchPage = 1, vouchPageSize = 10;
    let reimbPage = 1, reimbPageSize = 10;
    let adjPage   = 1, adjPageSize   = 10;

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

        document.getElementById('vouchPagStart').textContent = total > 0 ? startIdx + 1 : 0;
        document.getElementById('vouchPagEnd').textContent = endIdx;
        document.getElementById('vouchPagTotal').textContent = total;

        renderPaginationNav('vouchPagNavBtns', vouchPage, totalPages, (p) => { vouchPage = p; updateVouchPagination(); });
    }

    function changeVouchPageSize() {
        vouchPageSize = parseInt(document.getElementById('vouchPagSize').value) || 10;
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

        document.getElementById('reimbPagStart').textContent = total > 0 ? startIdx + 1 : 0;
        document.getElementById('reimbPagEnd').textContent = endIdx;
        document.getElementById('reimbPagTotal').textContent = total;

        renderPaginationNav('reimbPagNavBtns', reimbPage, totalPages, (p) => { reimbPage = p; updateReimbPagination(); });
    }

    function changeReimbPageSize() {
        reimbPageSize = parseInt(document.getElementById('reimbPagSize').value) || 10;
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

        document.getElementById('adjPagStart').textContent = total > 0 ? startIdx + 1 : 0;
        document.getElementById('adjPagEnd').textContent = endIdx;
        document.getElementById('adjPagTotal').textContent = total;

        renderPaginationNav('adjPagNavBtns', adjPage, totalPages, (p) => { adjPage = p; updateAdjPagination(); });
    }

    function changeAdjPageSize() {
        adjPageSize = parseInt(document.getElementById('adjPagSize').value) || 10;
        adjPage = 1;
        updateAdjPagination();
    }

    function renderPaginationNav(containerId, currentPage, totalPages, onPageClick) {
        const container = document.getElementById(containerId);
        if (!container) return;
        container.innerHTML = '';

        const prevBtn = document.createElement('button');
        prevBtn.className = 'prod-act-btn prod-act-edit';
        prevBtn.style.padding = '4px 10px';
        prevBtn.disabled = currentPage === 1;
        prevBtn.innerHTML = `← Prev`;
        prevBtn.onclick = () => { if (currentPage > 1) onPageClick(currentPage - 1); };
        container.appendChild(prevBtn);

        let startP = Math.max(1, currentPage - 2);
        let endP = Math.min(totalPages, startP + 4);
        if (endP - startP < 4) startP = Math.max(1, endP - 4);

        for (let p = startP; p <= endP; p++) {
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
            pBtn.onclick = () => onPageClick(p);
            container.appendChild(pBtn);
        }

        const nextBtn = document.createElement('button');
        nextBtn.className = 'prod-act-btn prod-act-edit';
        nextBtn.style.padding = '4px 10px';
        nextBtn.disabled = currentPage === totalPages;
        nextBtn.innerHTML = `Next →`;
        nextBtn.onclick = () => { if (currentPage < totalPages) onPageClick(currentPage + 1); };
        container.appendChild(nextBtn);
    }

    document.addEventListener('DOMContentLoaded', () => {
        const branchSel = document.getElementById('pcBranchFilter');
        if (branchSel && window.CurtissSearchableSelect) {
            CurtissSearchableSelect.attach(branchSel);
        }
        renderAccountTransactionsTable();
        updateVouchPagination();
        updateReimbPagination();
        updateAdjPagination();
    });

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
                const text = tr.textContent.toLowerCase();
                tr.dataset.searchMatched = (!q || text.includes(q)) ? 'true' : 'false';
            });
        });

        vouchPage = 1; updateVouchPagination();
        reimbPage = 1; updateReimbPagination();
        adjPage   = 1; updateAdjPagination();
    }
</script>
