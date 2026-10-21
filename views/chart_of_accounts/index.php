<?php
/**
 * Curtiss POS - Chart of Accounts View (Stage 10 Optimization)
 * Full Glassmorphic ERP Interface for Chart of Accounts (COA) Structure & Account Management.
 * Follows CURTISS_MASTER_RULES & AGENTS.md design, responsiveness, performance, and security standards.
 */
?>
<meta name="csrf-token" content="<?php echo htmlspecialchars(Auth::generateCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
<link rel="stylesheet" href="assets/css/curtiss_select.css">
<script src="assets/js/curtiss_select.js"></script>

<!-- Floating Top-Right Action Header Buttons -->
<div id="curtiss-floating-header-right" class="no-print" style="position: fixed; top: 16px; right: 20px; z-index: 1050; display: flex; align-items: center; gap: 8px;">
    <?php if (Auth::hasPermission('accounts.manage')): ?>
    <button type="button" class="inv-btn inv-btn-primary" onclick="openAccountModal()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        + Create New Account
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
        margin: 0;
    }

    /* Button System */
    .inv-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        height: 34px;
        min-height: 34px;
        padding: 0 16px;
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
    .inv-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none !important;
    }

    /* Glassmorphism Cards */
    .inv-card {
        background: var(--inv-bg-glass);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid var(--inv-border);
        border-radius: var(--inv-radius);
        box-shadow: var(--inv-shadow);
        margin-bottom: 1.5rem;
        padding: 1.25rem 1.5rem;
        width: 100%;
        box-sizing: border-box;
    }

    /* Status Badges - 28px standard height matching action buttons */
    .prod-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
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
        text-decoration: none;
        cursor: default;
        transition: all 0.15s ease;
    }
    .prod-badge-active   { background: #dcfce7; color: #15803d; border: 1px solid rgba(22, 163, 74, 0.2); }
    .prod-badge-deactive { background: #fee2e2; color: #b91c1c; border: 1px solid rgba(220, 38, 38, 0.2); }
    .prod-badge-system   { background: #dbeafe; color: #1e40af; border: 1px solid rgba(37, 99, 235, 0.2); }
    .prod-badge .dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        display: inline-block;
    }
    .prod-badge-active   .dot { background: #16a34a; }
    .prod-badge-deactive .dot { background: #dc2626; }
    .prod-badge-system   .dot { background: #2563eb; }

    /* Table Panels & Fixed Viewport System */
    .prod-table-panel {
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid rgba(226, 232, 240, 0.85);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
        margin-bottom: 0;
    }
    .prod-table-scroll { 
        overflow-x: auto; 
        overflow-y: auto;
        flex: 1;
        min-height: 0;
        position: relative;
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
        background: #f8fafc;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
    }
    .prod-table tbody tr {
        height: 48px;
        min-height: 48px;
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.12s ease;
    }
    .prod-table tbody tr:nth-child(even) {
        background: rgba(248, 250, 252, 0.57);
    }
    .prod-table tbody tr:nth-child(odd) {
        background: #ffffff;
    }
    .prod-table tbody tr:hover {
        background: #f1f5f9 !important;
    }
    .prod-table td {
        height: 48px;
        padding: 0 14px;
        font-size: 12.5px;
        color: #1e293b;
        vertical-align: middle;
        box-sizing: border-box;
    }

    /* Table Footer Pagination */
    .prod-table-footer {
        padding: 10px 16px;
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
    }
    .prod-act-primary {
        background: #0f172a;
        color: #ffffff;
        border-color: #0f172a;
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
        max-width: 520px;
        max-height: 90vh;
        overflow-y: auto;
        border-radius: 18px;
        padding: 22px 26px;
        box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.15);
        border: 1px solid rgba(226, 232, 240, 0.9);
        animation: modalSlideUp 0.22s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        box-sizing: border-box;
    }
    @keyframes modalSlideUp {
        from { opacity: 0; transform: translateY(12px) scale(0.98); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }

    /* ── Fullscreen Account Ledger Report Modal ── */
    .coa-report-modal-backdrop {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.65);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        z-index: 2000;
        align-items: center;
        justify-content: center;
        padding: 12px;
        box-sizing: border-box;
    }
    .coa-report-modal-backdrop.active {
        display: flex;
    }
    .coa-report-modal-card {
        background: #ffffff;
        width: 100%;
        height: 100%;
        max-width: 100%;
        max-height: 100%;
        border-radius: 16px;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.35);
        border: 1px solid rgba(226, 232, 240, 0.9);
        animation: modalSlideUp 0.22s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    /* KPI Summary Cards Bar */
    .coa-kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        gap: 12px;
        padding: 12px 20px;
        background: rgba(248, 250, 252, 0.85);
        border-bottom: 1px solid #e2e8f0;
        flex-shrink: 0;
    }
    .coa-kpi-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 10px 14px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    .coa-kpi-title {
        font-size: 10.5px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #64748b;
        margin-bottom: 2px;
    }
    .coa-kpi-value {
        font-size: 1.15rem;
        font-weight: 800;
        font-family: monospace;
        color: #0f172a;
        line-height: 1.2;
    }
    .coa-kpi-sub {
        font-size: 11px;
        color: #94a3b8;
        font-weight: 600;
        margin-top: 2px;
    }

    /* Filter Toolbar inside Modal */
    .coa-modal-toolbar {
        padding: 10px 20px;
        background: #ffffff;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
        flex-shrink: 0;
    }

    /* Print Styles */
    @media print {
        body * {
            visibility: hidden;
        }
        #accountHistoryModal, #accountHistoryModal * {
            visibility: visible;
        }
        #accountHistoryModal {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: auto;
            background: #fff;
            padding: 0;
        }
        .coa-report-modal-card {
            border: none;
            box-shadow: none;
            height: auto;
            max-height: none;
        }
        .no-print-btn, .coa-modal-toolbar {
            display: none !important;
        }
        .prod-table-scroll {
            overflow: visible !important;
            max-height: none !important;
        }
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

<div style="width: 100%; max-width: 100%; height: calc(100vh - 110px); display: flex; flex-direction: column; box-sizing: border-box; overflow: hidden; padding-bottom: 8px;">
    <!-- Page Header (Headline Only) -->
    <div class="inv-header" style="flex-shrink: 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
                <h1 class="inv-title">Chart of Accounts</h1>
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Account Type:</span>
                <select id="filterCoaType" onchange="applyCoaFilters()" style="height: 34px; min-height: 34px; padding: 0 14px; border-radius: 999px; font-size: 12px; font-weight: 700; color: #334155; background: rgba(255,255,255,0.95); border: 1px solid rgba(203,213,225,0.9); box-shadow: 0 2px 8px rgba(0,0,0,0.04); min-width: 170px;">
                    <option value="">All Account Types</option>
                    <option value="asset">Asset</option>
                    <option value="liability">Liability</option>
                    <option value="equity">Equity</option>
                    <option value="income">Income</option>
                    <option value="expense">Expense</option>
                </select>
            </div>
        </div>
    </div>

    <!-- COA Table Panel -->
    <div class="prod-table-panel" style="flex: 1; min-height: 0; display: flex; flex-direction: column;">
        <div style="flex-shrink: 0; padding: 10px 18px; border-bottom: 1px solid #e2e8f0; background: rgba(248, 250, 252, 0.8); display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#0f172a" stroke-width="2.5"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                <h3 style="font-size: 0.9rem; font-weight: 800; color: #0f172a; margin: 0; text-transform: uppercase; letter-spacing: 0.05em;">Chart of Accounts Master List</h3>
            </div>
        </div>
        <div class="prod-table-scroll">
            <table class="prod-table" id="coaTable">
                <thead>
                    <tr>
                        <th style="width: 110px; text-align: left;">Code</th>
                        <th style="text-align: left;">Account Name</th>
                        <th style="width: 130px; text-align: left;">Type</th>
                        <th style="width: 170px; text-align: right;" class="col-right">Running Balance (LKR)</th>
                        <th style="width: 140px; text-align: left;">System / Custom</th>
                        <th style="width: 120px; text-align: center;" class="col-center">Status</th>
                    </tr>
                </thead>
                <tbody id="coaTableBody">
                    <tr class="prod-empty"><td colspan="6" style="text-align: center; color: #94a3b8; padding: 32px 16px;">Loading Chart of Accounts...</td></tr>
                </tbody>
            </table>
        </div>
        <!-- Table Pagination Footer -->
        <div class="prod-table-footer">
            <div style="font-size: 12px; font-weight: 600; color: #64748b;" id="coaPagInfo">
                Showing <span id="coaPagStart">1</span> to <span id="coaPagEnd">25</span> of <span id="coaPagTotal">0</span> entries
            </div>
            <div style="display: flex; align-items: center; gap: 14px;">
                <div style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: #64748b; font-weight: 600;">
                    <span>Rows:</span>
                    <select id="coaPagSize" onchange="changeCoaPageSize()" style="height: 30px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 8px; font-size: 12px; color: #0f172a; background: #fff; outline: none; font-weight: 600; cursor: pointer;">
                        <option value="25" selected>25</option>
                        <option value="50">50</option>
                        <option value="75">75</option>
                        <option value="100">100</option>
                        <option value="500">500</option>
                    </select>
                </div>
                <div style="display: flex; align-items: center; gap: 4px;" id="coaPagNavBtns"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Account Create/Edit -->
<div class="curtiss-modal-backdrop" id="accountModal">
    <div class="curtiss-modal-card">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; padding-bottom: 10px; border-bottom: 1px solid #f1f5f9;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
                <h3 style="font-size: 1.1rem; font-weight: 800; color: #0f172a; margin: 0;">Create Chart of Account</h3>
            </div>
            <button type="button" onclick="closeModal('accountModal')" style="background: none; border: none; color: #94a3b8; cursor: pointer; padding: 4px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <form id="accountForm" onsubmit="submitAccount(event)">
            <div style="margin-bottom: 16px;">
                <label class="form-label">Account Code <span style="color: #ef4444;">*</span></label>
                <input type="text" id="accCode" class="form-input" placeholder="e.g. 5200" required style="font-weight: 800; font-family: monospace;">
            </div>
            <div style="margin-bottom: 16px;">
                <label class="form-label">Account Name <span style="color: #ef4444;">*</span></label>
                <input type="text" id="accName" class="form-input" placeholder="e.g. Electricity Expense" required>
            </div>
            <div style="margin-bottom: 20px;">
                <label class="form-label">Account Type <span style="color: #ef4444;">*</span></label>
                <select id="accType" class="form-input" required style="font-weight: 700;">
                    <option value="asset">Asset</option>
                    <option value="liability">Liability</option>
                    <option value="equity">Equity</option>
                    <option value="income">Income</option>
                    <option value="expense">Expense</option>
                </select>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 14px; border-top: 1px solid #f1f5f9;">
                <button type="button" class="inv-btn inv-btn-ghost" onclick="closeModal('accountModal')">Cancel</button>
                <button type="submit" id="accountSubmitBtn" class="inv-btn inv-btn-primary">Save Account</button>
            </div>
        </form>
    </div>
</div>

<!-- Full-Screen Modal: Account Transaction History Ledger Report -->
<div class="curtiss-modal-backdrop coa-report-modal-backdrop" id="accountHistoryModal">
    <div class="coa-report-modal-card">
        <!-- 1. Report Top Header Bar -->
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 14px 22px; border-bottom: 1px solid #e2e8f0; background: #ffffff; flex-shrink: 0;">
            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <button type="button" class="inv-btn inv-btn-ghost no-print-btn" onclick="closeModal('accountHistoryModal')" title="Back to Chart of Accounts" style="padding: 0 12px; height: 32px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
                    Back
                </button>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span id="histAccountCode" style="font-family: monospace; font-size: 1.15rem; font-weight: 800; color: #2563eb; background: #eff6ff; padding: 3px 10px; border-radius: 8px; border: 1px solid #bfdbfe;"></span>
                    <h2 id="histAccountTitle" style="font-size: 1.25rem; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.02em;">Account Ledger Statement</h2>
                </div>
                <span id="histAccountType" class="prod-badge" style="text-transform: uppercase;"></span>
                <span id="histAccountBranch" style="background: #f1f5f9; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; color: #475569;"></span>
                <span id="histAccountStatus"></span>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <button type="button" class="inv-btn inv-btn-ghost no-print-btn" onclick="printAccountStatement()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                    Print Statement
                </button>
                <button type="button" class="inv-btn inv-btn-ghost no-print-btn" onclick="exportAccountCsv()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Export CSV
                </button>
                <button type="button" class="inv-btn inv-btn-primary no-print-btn" onclick="closeModal('accountHistoryModal')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    Close (Esc)
                </button>
            </div>
        </div>

        <!-- 2. KPI Summary Cards Bar (Exact Report Format) -->
        <div class="coa-kpi-grid">
            <div class="coa-kpi-card">
                <div class="coa-kpi-title">Transactions</div>
                <div class="coa-kpi-value" id="kpiHistCount">0</div>
                <div class="coa-kpi-sub" id="kpiHistPeriod">All Recorded Activity</div>
            </div>
            <div class="coa-kpi-card">
                <div class="coa-kpi-title">Total Debits</div>
                <div class="coa-kpi-value" style="color: #16a34a;" id="kpiHistDebit">0.00</div>
                <div class="coa-kpi-sub">Total Dr Postings</div>
            </div>
            <div class="coa-kpi-card">
                <div class="coa-kpi-title">Total Credits</div>
                <div class="coa-kpi-value" style="color: #dc2626;" id="kpiHistCredit">0.00</div>
                <div class="coa-kpi-sub">Total Cr Postings</div>
            </div>
            <div class="coa-kpi-card">
                <div class="coa-kpi-title">Net Movement</div>
                <div class="coa-kpi-value" id="kpiHistNet">0.00</div>
                <div class="coa-kpi-sub">Filtered Net Change</div>
            </div>
            <div class="coa-kpi-card" style="background: rgba(37, 99, 235, 0.04); border-color: rgba(37, 99, 235, 0.25);">
                <div class="coa-kpi-title" style="color: #2563eb;">Current Balance (LKR)</div>
                <div class="coa-kpi-value" id="kpiHistBalance" style="color: #0f172a;">0.00</div>
                <div class="coa-kpi-sub" id="kpiHistDrCr" style="font-weight: 700; color: #2563eb;">Dr Normal Balance</div>
            </div>
        </div>

        <!-- 3. Inline Filter Toolbar -->
        <div class="coa-modal-toolbar no-print">
            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; flex: 1;">
                <!-- Real-time In-Modal Search Input -->
                <div style="position: relative; min-width: 250px; max-width: 320px;">
                    <svg style="position: absolute; left: 12px; top: 10px; pointer-events: none;" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" id="histSearchInput" placeholder="Filter transactions (JE #, memo, amount)..." oninput="onHistFilterChange()" style="width: 100%; height: 34px; padding: 0 14px 0 34px; border-radius: 999px; border: 1px solid #cbd5e1; font-size: 12px; outline: none; background: #fff; box-sizing: border-box; font-weight: 600;">
                </div>

                <!-- Source Type Filter -->
                <div style="display: flex; align-items: center; gap: 6px;">
                    <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Source:</span>
                    <select id="histFilterSource" onchange="onHistFilterChange()" style="height: 34px; min-height: 34px; padding: 0 14px; border-radius: 999px; font-size: 12px; font-weight: 700; border: 1px solid #cbd5e1; background: #fff; color: #334155;">
                        <option value="">All Sources</option>
                        <option value="opening_balance">Opening Balance</option>
                        <option value="sale">Sales</option>
                        <option value="grn">GRN Purchases</option>
                        <option value="payment">Payments</option>
                        <option value="payroll">Payroll</option>
                        <option value="expense">Expenses</option>
                        <option value="manual">Manual Adjustments</option>
                        <option value="reversal">Reversals</option>
                        <option value="bank_deposit">Bank Deposits</option>
                    </select>
                </div>

                <!-- Date Range Inputs -->
                <div style="display: flex; align-items: center; gap: 6px;">
                    <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">From:</span>
                    <input type="date" id="histDateFrom" onchange="onHistFilterChange()" style="height: 34px; padding: 0 10px; border-radius: 999px; border: 1px solid #cbd5e1; font-size: 12px; color: #334155; font-weight: 600;">
                </div>
                <div style="display: flex; align-items: center; gap: 6px;">
                    <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">To:</span>
                    <input type="date" id="histDateTo" onchange="onHistFilterChange()" style="height: 34px; padding: 0 10px; border-radius: 999px; border: 1px solid #cbd5e1; font-size: 12px; color: #334155; font-weight: 600;">
                </div>

                <!-- Quick Date Range Pills -->
                <div style="display: flex; align-items: center; gap: 4px;">
                    <button type="button" class="inv-btn inv-btn-ghost" onclick="setHistDateRange('all')" style="height: 32px; padding: 0 12px; font-size: 11px;">All Time</button>
                    <button type="button" class="inv-btn inv-btn-ghost" onclick="setHistDateRange('today')" style="height: 32px; padding: 0 12px; font-size: 11px;">Today</button>
                    <button type="button" class="inv-btn inv-btn-ghost" onclick="setHistDateRange('month')" style="height: 32px; padding: 0 12px; font-size: 11px;">This Month</button>
                    <button type="button" class="inv-btn inv-btn-ghost" onclick="setHistDateRange('year')" style="height: 32px; padding: 0 12px; font-size: 11px;">This Year</button>
                    <button type="button" class="inv-btn inv-btn-ghost" onclick="resetHistFilters()" title="Reset Filters" style="height: 32px; padding: 0 10px;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                    </button>
                </div>
            </div>
            <div style="font-size: 11.5px; font-weight: 700; color: #64748b;" id="histFilterCountSummary">
                Showing 0 entries
            </div>
        </div>

        <!-- 4. Financial Report Table Panel (Exact report layout!) -->
        <div class="prod-table-panel" style="flex: 1; min-height: 0; display: flex; flex-direction: column; border-radius: 0; border: none; box-shadow: none;">
            <div class="prod-table-scroll" style="flex: 1; min-height: 0; overflow-y: auto;">
                <table class="prod-table" id="accountHistoryTable">
                    <thead>
                        <tr>
                            <th style="width: 45px; text-align: center;" class="col-center">#</th>
                            <th style="width: 105px; text-align: left;">Date</th>
                            <th style="width: 155px; text-align: left;">Journal #</th>
                            <th style="width: 125px; text-align: left;">Source</th>
                            <th style="text-align: left;">Description &amp; Line Memo</th>
                            <th style="width: 125px; text-align: left;">Branch</th>
                            <th style="width: 115px; text-align: left;">Posted By</th>
                            <th style="width: 135px; text-align: right;" class="col-right">Debit (LKR)</th>
                            <th style="width: 135px; text-align: right;" class="col-right">Credit (LKR)</th>
                            <th style="width: 165px; text-align: right;" class="col-right">Running Balance (LKR)</th>
                        </tr>
                    </thead>
                    <tbody id="accountHistoryBody">
                        <tr><td colspan="10" style="text-align: center; color: #94a3b8; padding: 48px 16px;">Loading transaction history...</td></tr>
                    </tbody>
                    <tfoot>
                        <tr style="height: 48px; background: #f8fafc; font-weight: 800; border-top: 2px solid #cbd5e1; position: sticky; bottom: 0; z-index: 10;">
                            <td colspan="7" style="text-align: right; text-transform: uppercase; font-size: 11px; letter-spacing: 0.05em; color: #475569;">
                                TOTALS (Filtered Records):
                            </td>
                            <td class="col-right" style="text-align: right; color: #16a34a; font-family: monospace; font-size: 13px;" id="histFootDebit">0.00</td>
                            <td class="col-right" style="text-align: right; color: #dc2626; font-family: monospace; font-size: 13px;" id="histFootCredit">0.00</td>
                            <td class="col-right" style="text-align: right; font-family: monospace; font-size: 13.5px; color: #0f172a;" id="histFootBalance">0.00</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: View Journal Details (Double-Entry Drilldown) -->
<div class="curtiss-modal-backdrop" id="journalDetailsModal" style="z-index: 2200;">
    <div class="curtiss-modal-card" style="max-width: 760px; width: 95%;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid #f1f5f9; flex-shrink: 0;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #2563eb;"></div>
                <h3 style="font-size: 1.1rem; font-weight: 800; color: #0f172a; margin: 0;" id="jdTitle">Journal Entry Details</h3>
            </div>
            <button type="button" onclick="closeModal('journalDetailsModal')" style="background: none; border: none; color: #94a3b8; cursor: pointer; padding: 4px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div id="jdBody" style="flex: 1; overflow-y: auto;">Loading details...</div>
        <div style="display: flex; justify-content: flex-end; padding-top: 14px; border-top: 1px solid #f1f5f9; margin-top: 12px;">
            <button type="button" class="inv-btn inv-btn-ghost" onclick="closeModal('journalDetailsModal')">Close</button>
        </div>
    </div>
</div>

<script>
    let coaPage = 1, coaPageSize = 25;
    let allCoaItems = [];
    let currentCoaSearch = '';
    let searchDebounceTimer = null;

    function getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    function closeModal(id) {
        const m = document.getElementById(id);
        if (m) m.classList.remove('active');
    }

    function openModal(id) {
        const m = document.getElementById(id);
        if (m) m.classList.add('active');
    }

    function applyCoaFilters() {
        coaPage = 1;
        loadCOA();
    }

    async function loadCOA() {
        const search = currentCoaSearch;
        const type = document.getElementById('filterCoaType')?.value || '';

        try {
            const res = await fetch(`accounting_api.php?action=get_coa&search=${encodeURIComponent(search)}&type=${encodeURIComponent(type)}`);
            const data = await res.json();

            const tbody = document.getElementById('coaTableBody');
            if (!data.success || !data.data || data.data.length === 0) {
                tbody.innerHTML = '<tr class="prod-empty"><td colspan="6" style="text-align:center; padding:32px 16px; color:#94a3b8;">No accounts found matching filter criteria.</td></tr>';
                allCoaItems = [];
                updateCoaPagination();
                return;
            }

            allCoaItems = data.data;
            updateCoaPagination();
        } catch (err) {
            console.error('Failed to load COA', err);
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function updateCoaPagination() {
        const tbody = document.getElementById('coaTableBody');
        if (!tbody) return;

        const total = allCoaItems.length;
        const totalPages = Math.max(1, Math.ceil(total / coaPageSize));
        if (coaPage > totalPages) coaPage = totalPages;
        if (coaPage < 1) coaPage = 1;

        const startIdx = total > 0 ? (coaPage - 1) * coaPageSize : 0;
        const endIdx = Math.min(startIdx + coaPageSize, total);

        if (total === 0) {
            tbody.innerHTML = '<tr class="prod-empty"><td colspan="6" style="text-align:center; padding:32px 16px; color:#94a3b8;">No accounts found.</td></tr>';
        } else {
            const pageItems = allCoaItems.slice(startIdx, endIdx);
            tbody.innerHTML = pageItems.map(a => {
                const bal = parseFloat(a.current_balance || 0);
                const isNeg = bal < 0;
                const formattedBal = (isNeg ? '-' : '') + Math.abs(bal).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                const balColor = isNeg ? '#dc2626' : (bal > 0 ? '#15803d' : '#475569');
                const isSystem = parseInt(a.is_system) === 1;
                const isActive = a.status === 'active';

                return `
                <tr onclick="viewAccountHistory(${a.id})" style="cursor: pointer;" title="Click row to view complete transaction history">
                    <td><strong style="font-family: monospace; font-size: 12px; color: #2563eb;">${escapeHtml(a.account_code)}</strong></td>
                    <td style="font-weight: 700; color: #0f172a;">${escapeHtml(a.account_name)}</td>
                    <td><span style="background: #f1f5f9; padding: 3px 8px; border-radius: 999px; font-size: 11px; font-weight: 700; text-transform: capitalize; color: #475569;">${escapeHtml(a.account_type)}</span></td>
                    <td class="col-right" style="text-align: right; font-weight: 800; font-family: monospace; color: ${balColor};">${formattedBal}</td>
                    <td>${isSystem ? '<span class="prod-badge prod-badge-system"><span class="dot"></span>System</span>' : '<span style="color:#64748b; font-size:12px; font-weight: 600;">Custom</span>'}</td>
                    <td class="col-center" style="text-align: center;">
                        <span class="prod-badge ${isActive ? 'prod-badge-active' : 'prod-badge-deactive'}" 
                              onclick="event.stopPropagation(); toggleAccountStatus(${a.id}, '${a.status}', ${isSystem ? 1 : 0});" 
                              style="cursor: ${isSystem ? 'default' : 'pointer'};" 
                              title="${isSystem ? 'System core accounts cannot be deactivated' : 'Click to toggle status'}">
                            <span class="dot"></span>${isActive ? 'Active' : 'Deactive'}
                        </span>
                    </td>
                </tr>
                `;
            }).join('');
        }

        document.getElementById('coaPagStart').textContent = total > 0 ? startIdx + 1 : 0;
        document.getElementById('coaPagEnd').textContent = endIdx;
        document.getElementById('coaPagTotal').textContent = total;

        renderCoaPaginationNav(coaPage, totalPages);
    }

    async function toggleAccountStatus(accountId, currentStatus, isSystem) {
        if (isSystem) {
            alert('System core accounts cannot be deactivated because they are required for automated journal postings.');
            return;
        }

        const newTarget = currentStatus === 'active' ? 'Deactive' : 'Active';
        if (!confirm(`Are you sure you want to set this account status to ${newTarget}?`)) {
            return;
        }

        try {
            const res = await fetch('accounting_api.php?action=toggle_account_status', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                body: JSON.stringify({
                    id: accountId,
                    csrf_token: getCsrfToken()
                })
            });
            const data = await res.json();
            if (data.success) {
                loadCOA();
            } else {
                alert('Error: ' + data.message);
            }
        } catch (err) {
            alert('Connection error: ' + err.message);
        }
    }

    let currentHistAccount = null;
    let allHistTransactions = [];
    let filteredHistTransactions = [];

    function formatAmount(val) {
        const num = parseFloat(val || 0);
        return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    async function viewAccountHistory(accountId) {
        openModal('accountHistoryModal');
        
        // Reset filter inputs
        const searchInput = document.getElementById('histSearchInput');
        if (searchInput) searchInput.value = '';
        const sourceSelect = document.getElementById('histFilterSource');
        if (sourceSelect) sourceSelect.value = '';
        const dateFromInput = document.getElementById('histDateFrom');
        if (dateFromInput) dateFromInput.value = '';
        const dateToInput = document.getElementById('histDateTo');
        if (dateToInput) dateToInput.value = '';

        const tbody = document.getElementById('accountHistoryBody');
        tbody.innerHTML = '<tr><td colspan="10" style="text-align: center; color: #94a3b8; padding: 48px 16px;">Loading account transactions...</td></tr>';

        try {
            const res = await fetch(`accounting_api.php?action=get_account_history&account_id=${accountId}`);
            const data = await res.json();

            if (!data.success || !data.account) {
                tbody.innerHTML = `<tr><td colspan="10" style="text-align: center; color: #dc2626; padding: 36px 16px;">${escapeHtml(data.message || 'Error loading history.')}</td></tr>`;
                return;
            }

            currentHistAccount = data.account;
            allHistTransactions = data.transactions || [];

            // Populate Account Details in Modal Header
            document.getElementById('histAccountCode').textContent = currentHistAccount.account_code;
            document.getElementById('histAccountTitle').textContent = currentHistAccount.account_name;
            
            const typeBadge = document.getElementById('histAccountType');
            typeBadge.textContent = currentHistAccount.account_type;
            typeBadge.className = 'prod-badge';
            if (['asset', 'expense'].includes(currentHistAccount.account_type.toLowerCase())) {
                typeBadge.style.background = '#eff6ff';
                typeBadge.style.color = '#1d4ed8';
                typeBadge.style.borderColor = '#bfdbfe';
            } else {
                typeBadge.style.background = '#fef3c7';
                typeBadge.style.color = '#b45309';
                typeBadge.style.borderColor = '#fde68a';
            }

            document.getElementById('histAccountBranch').textContent = currentHistAccount.branch_name ? `Branch: ${currentHistAccount.branch_name}` : 'All Branches (Consolidated)';
            
            const statusBadge = document.getElementById('histAccountStatus');
            const isActive = currentHistAccount.status === 'active';
            statusBadge.innerHTML = `<span class="prod-badge ${isActive ? 'prod-badge-active' : 'prod-badge-deactive'}"><span class="dot"></span>${isActive ? 'Active' : 'Deactive'}</span>`;

            // Initial render with all transactions
            onHistFilterChange();

        } catch (err) {
            tbody.innerHTML = `<tr><td colspan="10" style="text-align: center; color: #dc2626; padding: 36px 16px;">Connection error: ${escapeHtml(err.message)}</td></tr>`;
        }
    }

    function onHistFilterChange() {
        if (!currentHistAccount) return;

        const query = (document.getElementById('histSearchInput')?.value || '').trim().toLowerCase();
        const source = document.getElementById('histFilterSource')?.value || '';
        const dateFrom = document.getElementById('histDateFrom')?.value || '';
        const dateTo = document.getElementById('histDateTo')?.value || '';

        filteredHistTransactions = allHistTransactions.filter(t => {
            if (source && (t.source_type || '').toLowerCase() !== source.toLowerCase()) {
                return false;
            }
            if (dateFrom && t.entry_date < dateFrom) {
                return false;
            }
            if (dateTo && t.entry_date > dateTo) {
                return false;
            }
            if (query) {
                const debitStr = formatAmount(t.debit);
                const creditStr = formatAmount(t.credit);
                const rbalStr = formatAmount(t.running_balance);
                const match = (t.entry_number || '').toLowerCase().includes(query) ||
                              (t.source_type || '').toLowerCase().includes(query) ||
                              (t.description || '').toLowerCase().includes(query) ||
                              (t.memo || '').toLowerCase().includes(query) ||
                              (t.branch_name || '').toLowerCase().includes(query) ||
                              (t.created_by_name || '').toLowerCase().includes(query) ||
                              String(t.debit).includes(query) ||
                              String(t.credit).includes(query) ||
                              debitStr.includes(query) ||
                              creditStr.includes(query) ||
                              rbalStr.includes(query);
                if (!match) return false;
            }
            return true;
        });

        renderHistTableAndStats();
    }

    function renderHistTableAndStats() {
        const tbody = document.getElementById('accountHistoryBody');
        if (!tbody) return;

        const isAssetOrExpense = ['asset', 'expense'].includes(currentHistAccount.account_type.toLowerCase());

        let totDebit = 0;
        let totCredit = 0;

        filteredHistTransactions.forEach(t => {
            totDebit += parseFloat(t.debit || 0);
            totCredit += parseFloat(t.credit || 0);
        });

        const netChange = isAssetOrExpense ? (totDebit - totCredit) : (totCredit - totDebit);
        
        // Latest balance from filtered list or full account
        let currentBal = 0;
        if (filteredHistTransactions.length > 0) {
            currentBal = parseFloat(filteredHistTransactions[0].running_balance || 0);
        } else {
            currentBal = parseFloat(currentHistAccount.current_balance || 0);
        }

        const isBalNeg = currentBal < 0;
        const absBal = Math.abs(currentBal);
        const drCrLabel = isAssetOrExpense 
            ? (currentBal >= 0 ? 'Dr Normal Balance' : 'Cr Overdraft Balance')
            : (currentBal >= 0 ? 'Cr Normal Balance' : 'Dr Contra Balance');
        const drCrTag = isAssetOrExpense 
            ? (currentBal >= 0 ? 'Dr' : 'Cr')
            : (currentBal >= 0 ? 'Cr' : 'Dr');

        // Update KPI summary cards
        document.getElementById('kpiHistCount').textContent = filteredHistTransactions.length;
        document.getElementById('kpiHistPeriod').textContent = (document.getElementById('histDateFrom')?.value || document.getElementById('histDateTo')?.value) ? 'Filtered Date Scope' : 'All Activity Lifetime';
        document.getElementById('kpiHistDebit').textContent = formatAmount(totDebit);
        document.getElementById('kpiHistCredit').textContent = formatAmount(totCredit);
        
        const netEl = document.getElementById('kpiHistNet');
        netEl.textContent = (netChange >= 0 ? '+' : '-') + ' ' + formatAmount(Math.abs(netChange));
        netEl.style.color = netChange >= 0 ? '#16a34a' : '#dc2626';

        const balEl = document.getElementById('kpiHistBalance');
        balEl.textContent = (isBalNeg ? '-' : '') + formatAmount(absBal) + ' ' + drCrTag;
        balEl.style.color = isBalNeg ? '#dc2626' : '#0f172a';

        document.getElementById('kpiHistDrCr').textContent = drCrLabel;
        document.getElementById('kpiHistDrCr').style.color = isBalNeg ? '#dc2626' : '#2563eb';

        // Update footer totals
        document.getElementById('histFootDebit').textContent = formatAmount(totDebit);
        document.getElementById('histFootCredit').textContent = formatAmount(totCredit);
        document.getElementById('histFootBalance').textContent = (isBalNeg ? '-' : '') + formatAmount(absBal) + ' ' + drCrTag;
        document.getElementById('histFootBalance').style.color = isBalNeg ? '#dc2626' : '#0f172a';

        // Update summary counter
        document.getElementById('histFilterCountSummary').textContent = `Showing ${filteredHistTransactions.length} of ${allHistTransactions.length} entries`;

        if (filteredHistTransactions.length === 0) {
            tbody.innerHTML = '<tr><td colspan="10" style="text-align: center; color: #94a3b8; padding: 48px 16px;">No transactions found matching filter criteria.</td></tr>';
            return;
        }

        tbody.innerHTML = filteredHistTransactions.map((t, idx) => {
            const debit = parseFloat(t.debit || 0);
            const credit = parseFloat(t.credit || 0);
            const rBal = parseFloat(t.running_balance || 0);
            const isNeg = rBal < 0;
            const rBalStr = (isNeg ? '-' : '') + formatAmount(Math.abs(rBal));
            const rowDrCr = isAssetOrExpense ? (rBal >= 0 ? 'Dr' : 'Cr') : (rBal >= 0 ? 'Cr' : 'Dr');
            const balColor = isNeg ? '#dc2626' : (rBal > 0 ? '#0f172a' : '#64748b');

            const sourceBadgeStyle = getSourceBadgeStyle(t.source_type);

            return `
                <tr>
                    <td class="col-center" style="text-align: center; color: #94a3b8; font-size: 11px; font-weight: 700;">${idx + 1}</td>
                    <td style="font-weight: 600; font-size: 12px; color: #334155; white-space: nowrap;">${escapeHtml(t.entry_date)}</td>
                    <td>
                        <a href="javascript:void(0)" onclick="openJournalDetailsFromHist(${t.journal_id})" style="font-family: monospace; font-size: 12px; font-weight: 800; color: #2563eb; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;" title="Click to view complete double-entry journal">
                            ${escapeHtml(t.entry_number)}
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                        </a>
                    </td>
                    <td>
                        <span style="${sourceBadgeStyle}; padding: 3px 9px; border-radius: 999px; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.04em;">
                            ${escapeHtml(t.source_type || 'MANUAL')}
                        </span>
                    </td>
                    <td>
                        <div style="font-size: 12.5px; font-weight: 600; color: #0f172a;">${escapeHtml(t.description)}</div>
                        ${t.memo ? `<div style="font-size: 11px; color: #64748b; margin-top: 2px; font-style: italic;">Memo: ${escapeHtml(t.memo)}</div>` : ''}
                    </td>
                    <td style="font-size: 12px; color: #475569; font-weight: 600;">${escapeHtml(t.branch_name || 'Global')}</td>
                    <td style="font-size: 11.5px; color: #64748b;">${escapeHtml(t.created_by_name || 'System')}</td>
                    <td class="col-right" style="text-align: right; font-family: monospace; font-size: 12.5px; font-weight: 700; color: ${debit > 0 ? '#16a34a' : '#94a3b8'};">
                        ${debit > 0 ? formatAmount(debit) : '-'}
                    </td>
                    <td class="col-right" style="text-align: right; font-family: monospace; font-size: 12.5px; font-weight: 700; color: ${credit > 0 ? '#dc2626' : '#94a3b8'};">
                        ${credit > 0 ? formatAmount(credit) : '-'}
                    </td>
                    <td class="col-right" style="text-align: right; font-family: monospace; font-size: 12.5px; font-weight: 800; color: ${balColor}; white-space: nowrap;">
                        ${rBalStr} <span style="font-size: 10px; font-weight: 800; color: #64748b;">${rowDrCr}</span>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function getSourceBadgeStyle(source) {
        switch ((source || '').toLowerCase()) {
            case 'sale':
                return 'background: #dcfce7; color: #15803d; border: 1px solid rgba(22, 163, 74, 0.2)';
            case 'grn':
                return 'background: #eff6ff; color: #1d4ed8; border: 1px solid rgba(37, 99, 235, 0.2)';
            case 'opening_balance':
                return 'background: #f3e8ff; color: #7e22ce; border: 1px solid rgba(147, 51, 234, 0.2)';
            case 'payment':
            case 'bank_deposit':
                return 'background: #e0f2fe; color: #0369a1; border: 1px solid rgba(2, 132, 199, 0.2)';
            case 'payroll':
                return 'background: #fef3c7; color: #b45309; border: 1px solid rgba(217, 119, 6, 0.2)';
            case 'expense':
                return 'background: #fee2e2; color: #b91c1c; border: 1px solid rgba(220, 38, 38, 0.2)';
            case 'reversal':
                return 'background: #ffe4e6; color: #e11d48; border: 1px solid rgba(225, 29, 72, 0.2)';
            default:
                return 'background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1';
        }
    }

    function setHistDateRange(type) {
        const today = new Date();
        const y = today.getFullYear();
        const m = String(today.getMonth() + 1).padStart(2, '0');
        const d = String(today.getDate()).padStart(2, '0');
        const todayStr = `${y}-${m}-${d}`;

        const fromInput = document.getElementById('histDateFrom');
        const toInput = document.getElementById('histDateTo');

        if (type === 'today') {
            fromInput.value = todayStr;
            toInput.value = todayStr;
        } else if (type === 'month') {
            fromInput.value = `${y}-${m}-01`;
            toInput.value = todayStr;
        } else if (type === 'year') {
            fromInput.value = `${y}-01-01`;
            toInput.value = todayStr;
        } else if (type === 'all') {
            fromInput.value = '';
            toInput.value = '';
        }
        onHistFilterChange();
    }

    function resetHistFilters() {
        const s = document.getElementById('histSearchInput');
        if (s) s.value = '';
        const src = document.getElementById('histFilterSource');
        if (src) src.value = '';
        const f = document.getElementById('histDateFrom');
        if (f) f.value = '';
        const t = document.getElementById('histDateTo');
        if (t) t.value = '';
        onHistFilterChange();
    }

    function printAccountStatement() {
        if (!currentHistAccount) return;
        window.print();
    }

    function exportAccountCsv() {
        if (!currentHistAccount || filteredHistTransactions.length === 0) {
            alert('No transactions to export.');
            return;
        }

        const headers = ['#', 'Date', 'Journal #', 'Source', 'Description', 'Memo', 'Branch', 'Posted By', 'Debit (LKR)', 'Credit (LKR)', 'Running Balance (LKR)'];
        const csvRows = [];
        csvRows.push(headers.join(','));

        filteredHistTransactions.forEach((t, idx) => {
            const row = [
                idx + 1,
                `"${t.entry_date || ''}"`,
                `"${t.entry_number || ''}"`,
                `"${t.source_type || ''}"`,
                `"${(t.description || '').replace(/"/g, '""')}"`,
                `"${(t.memo || '').replace(/"/g, '""')}"`,
                `"${(t.branch_name || '').replace(/"/g, '""')}"`,
                `"${(t.created_by_name || '').replace(/"/g, '""')}"`,
                parseFloat(t.debit || 0).toFixed(2),
                parseFloat(t.credit || 0).toFixed(2),
                parseFloat(t.running_balance || 0).toFixed(2)
            ];
            csvRows.push(row.join(','));
        });

        const csvString = csvRows.join('\r\n');
        const blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        const fileName = `${currentHistAccount.account_code}_${currentHistAccount.account_name.replace(/[^a-zA-Z0-9_-]/g, '_')}_Statement.csv`;

        link.setAttribute('href', url);
        link.setAttribute('download', fileName);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    async function openJournalDetailsFromHist(journalId) {
        openModal('journalDetailsModal');
        const jdBody = document.getElementById('jdBody');
        jdBody.innerHTML = '<div style="text-align: center; color: #94a3b8; padding: 24px;">Loading journal entry details...</div>';

        try {
            const res = await fetch(`accounting_api.php?action=get_journal_entry_details&id=${journalId}`);
            const data = await res.json();

            if (!data.success || !data.data) {
                jdBody.innerHTML = `<div style="text-align: center; color: #dc2626; padding: 20px;">${escapeHtml(data.message || 'Error loading journal entry.')}</div>`;
                return;
            }

            const j = data.data;
            document.getElementById('jdTitle').textContent = `Journal Entry ${j.entry_number}`;

            jdBody.innerHTML = `
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 16px; margin-bottom: 14px; font-size: 12.5px;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 8px;">
                        <div><strong>Posting Date:</strong> ${escapeHtml(j.entry_date)}</div>
                        <div><strong>Source Type:</strong> <span style="background: #e2e8f0; padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: 700; text-transform: uppercase;">${escapeHtml(j.source_type)}</span></div>
                        <div><strong>Branch:</strong> ${escapeHtml(j.branch_name || 'Consolidated')}</div>
                        <div><strong>Created By:</strong> ${escapeHtml(j.creator_name || 'System')}</div>
                    </div>
                    <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #e2e8f0;">
                        <strong>Description:</strong> ${escapeHtml(j.description)}
                    </div>
                </div>

                <table class="prod-table" style="margin-bottom: 10px; border: 1px solid #e2e8f0;">
                    <thead>
                        <tr style="background: #f1f5f9;">
                            <th style="text-align: left; padding: 8px 12px; font-size: 10.5px; font-weight: 800; text-transform: uppercase;">Account Code & Name</th>
                            <th style="text-align: right; padding: 8px 12px; font-size: 10.5px; font-weight: 800; text-transform: uppercase;" class="col-right">Debit (LKR)</th>
                            <th style="text-align: right; padding: 8px 12px; font-size: 10.5px; font-weight: 800; text-transform: uppercase;" class="col-right">Credit (LKR)</th>
                            <th style="text-align: left; padding: 8px 12px; font-size: 10.5px; font-weight: 800; text-transform: uppercase;">Memo</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${j.lines.map(l => `
                            <tr style="height: 38px;">
                                <td style="padding: 6px 12px;">
                                    <strong style="color: #2563eb; font-family: monospace;">${escapeHtml(l.account_code)}</strong> - ${escapeHtml(l.account_name)}
                                </td>
                                <td class="col-right" style="text-align: right; padding: 6px 12px; font-family: monospace; font-weight: 700; color: ${parseFloat(l.debit) > 0 ? '#16a34a' : '#94a3b8'};">
                                    ${parseFloat(l.debit) > 0 ? parseFloat(l.debit).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '-'}
                                </td>
                                <td class="col-right" style="text-align: right; padding: 6px 12px; font-family: monospace; font-weight: 700; color: ${parseFloat(l.credit) > 0 ? '#dc2626' : '#94a3b8'};">
                                    ${parseFloat(l.credit) > 0 ? parseFloat(l.credit).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '-'}
                                </td>
                                <td style="padding: 6px 12px; color: #64748b; font-size: 11.5px;">${escapeHtml(l.memo || '')}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                    <tfoot>
                        <tr style="height: 40px; font-weight: 800; background: #f8fafc; border-top: 2px solid #cbd5e1;">
                            <td style="padding: 8px 12px; text-transform: uppercase; font-size: 11px;">TOTAL DOUBLE ENTRY</td>
                            <td class="col-right" style="text-align: right; padding: 8px 12px; color: #16a34a; font-family: monospace;">
                                ${parseFloat(j.total_debit).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                            </td>
                            <td class="col-right" style="text-align: right; padding: 8px 12px; color: #dc2626; font-family: monospace;">
                                ${parseFloat(j.total_credit).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            `;
        } catch (err) {
            jdBody.innerHTML = `<div style="text-align: center; color: #dc2626; padding: 20px;">Connection error: ${escapeHtml(err.message)}</div>`;
        }
    }

    // Global ESC key listener to close active modals
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const jd = document.getElementById('journalDetailsModal');
            if (jd && jd.classList.contains('active')) {
                closeModal('journalDetailsModal');
                return;
            }
            const ah = document.getElementById('accountHistoryModal');
            if (ah && ah.classList.contains('active')) {
                closeModal('accountHistoryModal');
                return;
            }
            const accModal = document.getElementById('accountModal');
            if (accModal && accModal.classList.contains('active')) {
                closeModal('accountModal');
            }
        }
    });

    function renderCoaPaginationNav(currentPage, totalPages) {
        const container = document.getElementById('coaPagNavBtns');
        if (!container) return;
        container.innerHTML = '';

        const prevBtn = document.createElement('button');
        prevBtn.className = 'prod-act-btn prod-act-edit';
        prevBtn.disabled = currentPage === 1;
        prevBtn.innerHTML = `← Prev`;
        prevBtn.onclick = () => { if (currentPage > 1) { coaPage = currentPage - 1; updateCoaPagination(); } };
        container.appendChild(prevBtn);

        let startP = Math.max(1, currentPage - 2);
        let endP = Math.min(totalPages, startP + 4);
        if (endP - startP < 4) startP = Math.max(1, endP - 4);

        for (let p = startP; p <= endP; p++) {
            const pBtn = document.createElement('button');
            pBtn.className = 'prod-act-btn ' + (p === currentPage ? 'prod-act-primary' : 'prod-act-edit');
            pBtn.style.minWidth = '28px';
            pBtn.style.justifyContent = 'center';
            if (p === currentPage) {
                pBtn.style.background = '#0f172a';
                pBtn.style.color = '#ffffff';
                pBtn.style.borderColor = '#0f172a';
            }
            pBtn.textContent = p;
            pBtn.onclick = () => { coaPage = p; updateCoaPagination(); };
            container.appendChild(pBtn);
        }

        const nextBtn = document.createElement('button');
        nextBtn.className = 'prod-act-btn prod-act-edit';
        nextBtn.disabled = currentPage === totalPages;
        nextBtn.innerHTML = `Next →`;
        nextBtn.onclick = () => { if (currentPage < totalPages) { coaPage = currentPage + 1; updateCoaPagination(); } };
        container.appendChild(nextBtn);
    }

    function changeCoaPageSize() {
        coaPageSize = parseInt(document.getElementById('coaPagSize').value) || 25;
        coaPage = 1;
        updateCoaPagination();
    }

    function openAccountModal() {
        const form = document.getElementById('accountForm');
        form.reset();
        const submitBtn = document.getElementById('accountSubmitBtn');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Save Account';
        }
        openModal('accountModal');
        setTimeout(() => {
            document.getElementById('accCode')?.focus();
        }, 60);
    }

    async function submitAccount(e) {
        e.preventDefault();
        const submitBtn = document.getElementById('accountSubmitBtn');
        if (submitBtn && submitBtn.disabled) return;

        const code = document.getElementById('accCode').value.trim();
        const name = document.getElementById('accName').value.trim();
        const type = document.getElementById('accType').value;

        if (!code || !name || !type) {
            alert('Please fill out all required fields.');
            return;
        }

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Saving...';
        }

        try {
            const res = await fetch('accounting_api.php?action=create_account', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                body: JSON.stringify({
                    account_code: code,
                    account_name: name,
                    account_type: type,
                    csrf_token: getCsrfToken()
                })
            });

            const data = await res.json();
            if (data.success) {
                closeModal('accountModal');
                loadCOA();
            } else {
                alert('Error: ' + data.message);
            }
        } catch (err) {
            alert('Connection error: ' + err.message);
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Save Account';
            }
        }
    }

    // Keyboard navigation inside account modal form
    document.addEventListener('DOMContentLoaded', () => {
        const coaTypeEl = document.getElementById('filterCoaType');
        if (coaTypeEl && window.CurtissSearchableSelect) {
            CurtissSearchableSelect.attach(coaTypeEl, { searchPlaceholder: 'Type to search...' });
        }

        const accCode = document.getElementById('accCode');
        const accName = document.getElementById('accName');
        const accType = document.getElementById('accType');

        if (accCode) {
            accCode.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    accName?.focus();
                }
            });
        }
        if (accName) {
            accName.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    accType?.focus();
                }
            });
        }

        loadCOA();
    });

    /* ── Realtime Search in Floating Bottom Nav Bar ── */
    function registerBottomSearch() {
        if (window.CurtissNav && window.CurtissNav.enablePageSearch) {
            window.CurtissNav.enablePageSearch({
                placeholder: 'Search COA accounts (code, name, type, balance...)...',
                onSearch: function(query) {
                    currentCoaSearch = (query || '').trim();
                    if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
                    searchDebounceTimer = setTimeout(() => {
                        coaPage = 1;
                        loadCOA();
                    }, 300);
                }
            });
        } else {
            setTimeout(registerBottomSearch, 40);
        }
    }
    registerBottomSearch();
</script>
