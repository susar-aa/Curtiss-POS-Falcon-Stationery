<?php
/**
 * Curtiss POS - Chart of Accounts View (Stage 10 Redesign)
 * Full Glassmorphic ERP Interface for Chart of Accounts (COA) Structure & Account Management.
 */
?>
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
    .prod-badge-active   { background: #dcfce7; color: #15803d; border: 1px solid rgba(22,163,74,0.2); }
    .prod-badge-system   { background: #dbeafe; color: #1e40af; border: 1px solid rgba(37,99,235,0.2); }
    .prod-badge .dot {
        width: 5px; height: 5px; border-radius: 50%; display: inline-block;
    }
    .prod-badge-active   .dot { background: #16a34a; }
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
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.12s ease;
    }
    .prod-table tbody tr:nth-child(even) {
        background: rgba(248, 250, 252, 0.75);
    }
    .prod-table tbody tr:nth-child(odd) {
        background: #ffffff;
    }
    .prod-table tbody tr:hover {
        background: #f1f5f9 !important;
    }
    .prod-table td {
        padding: 8px 12px;
        font-size: 12.5px;
        color: #1e293b;
        vertical-align: middle;
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
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        border: 1px solid transparent;
        cursor: pointer;
        transition: all 0.15s ease;
        font-family: inherit;
        text-decoration: none;
        white-space: nowrap;
    }
    .prod-act-edit {
        background: #f1f5f9;
        border-color: #cbd5e1;
        color: #334155;
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

<div style="width: 100%; max-width: 100%; height: calc(100vh - 110px); display: flex; flex-direction: column; box-sizing: border-box; overflow: hidden; padding-bottom: 8px;">
    <!-- Page Header -->
    <div class="inv-header" style="flex-shrink: 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 0.85rem;">
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
        <div style="flex-shrink: 0; padding: 12px 18px; border-bottom: 1px solid #e2e8f0; background: rgba(248, 250, 252, 0.8); display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0f172a" stroke-width="2.5"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                <h3 style="font-size: 0.95rem; font-weight: 800; color: #0f172a; margin: 0;">Chart of Accounts Master List</h3>
            </div>
        </div>
        <div class="prod-table-scroll">
            <table class="prod-table" id="coaTable">
                <thead>
                    <tr>
                        <th style="width: 110px; text-align: left;">Code</th>
                        <th style="text-align: left;">Account Name</th>
                        <th style="width: 120px; text-align: left;">Type</th>
                        <th style="width: 160px; text-align: right;" class="col-right">Running Balance (LKR)</th>
                        <th style="width: 140px; text-align: left;">System / Custom</th>
                        <th style="width: 100px; text-align: center;" class="col-center">Status</th>
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
                Showing <span id="coaPagStart">1</span> to <span id="coaPagEnd">10</span> of <span id="coaPagTotal">0</span> entries
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
                <button type="submit" class="inv-btn inv-btn-primary">Save Account</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Account Transaction History -->
<div class="curtiss-modal-backdrop" id="accountHistoryModal">
    <div class="curtiss-modal-card" style="max-width: 920px; width: 95%;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 10px; height: 10px; border-radius: 50%; background: #2563eb;"></div>
                <div>
                    <h3 id="histAccountTitle" style="font-size: 1.15rem; font-weight: 800; color: #0f172a; margin: 0;">Account Transaction History</h3>
                    <div style="display: flex; align-items: center; gap: 8px; margin-top: 4px;">
                        <span id="histAccountType" style="background: #f1f5f9; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 700; text-transform: capitalize; color: #475569;"></span>
                        <span id="histAccountBranch" style="font-size: 11px; color: #64748b; font-weight: 600;"></span>
                    </div>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 14px;">
                <div style="text-align: right; background: #f8fafc; padding: 6px 14px; border-radius: 12px; border: 1px solid #e2e8f0;">
                    <div style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">Current Balance</div>
                    <div id="histAccountBalance" style="font-size: 1.05rem; font-weight: 800; font-family: monospace; color: #0f172a;">LKR 0.00</div>
                </div>
                <button type="button" onclick="closeModal('accountHistoryModal')" style="background: none; border: none; color: #94a3b8; cursor: pointer; padding: 4px; display: flex; align-items: center; justify-content: center;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
        </div>

        <!-- History Table Panel -->
        <div class="prod-table-panel" style="max-height: 480px; display: flex; flex-direction: column;">
            <div class="prod-table-scroll" style="max-height: 480px; overflow-y: auto;">
                <table class="prod-table">
                    <thead>
                        <tr>
                            <th style="width: 100px; text-align: left;">Date</th>
                            <th style="width: 140px; text-align: left;">Journal #</th>
                            <th style="width: 110px; text-align: left;">Source</th>
                            <th style="text-align: left;">Description / Memo</th>
                            <th style="width: 110px; text-align: right;" class="col-right">Debit (LKR)</th>
                            <th style="width: 110px; text-align: right;" class="col-right">Credit (LKR)</th>
                            <th style="width: 140px; text-align: right;" class="col-right">Running Balance (LKR)</th>
                        </tr>
                    </thead>
                    <tbody id="accountHistoryBody">
                        <tr><td colspan="7" style="text-align: center; color: #94a3b8; padding: 32px 16px;">Loading transaction history...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div style="display: flex; justify-content: flex-end; margin-top: 16px; padding-top: 12px; border-top: 1px solid #f1f5f9;">
            <button type="button" class="inv-btn inv-btn-ghost" onclick="closeModal('accountHistoryModal')">Close</button>
        </div>
    </div>
</div>

<script>
    let coaPage = 1, coaPageSize = 25;
    let allCoaItems = [];

    function closeModal(id) {
        const m = document.getElementById(id);
        if (m) m.classList.remove('active');
    }

    function openModal(id) {
        const m = document.getElementById(id);
        if (m) m.classList.add('active');
    }

    function getCoaSearchValue() {
        const globalVal = (document.getElementById('searchInput')?.value || '').trim();
        return globalVal;
    }

    function updateFilterActiveDot() {
        const type = document.getElementById('filterCoaType')?.value || '';
        const dot = document.getElementById('filterActiveDot');
        if (dot) {
            dot.style.display = type ? 'block' : 'none';
        }
    }

    function applyCoaFilters() {
        updateFilterActiveDot();
        loadCOA();
    }

    function resetCoaFilters() {
        const type = document.getElementById('filterCoaType');
        if (type) type.value = '';
        updateFilterActiveDot();
        loadCOA();
    }

    async function loadCOA() {
        const search = getCoaSearchValue();
        const type = document.getElementById('filterCoaType')?.value || '';

        try {
            const res = await fetch(`accounting_api.php?action=get_coa&search=${encodeURIComponent(search)}&type=${type}`);
            const data = await res.json();

            const tbody = document.getElementById('coaTableBody');
            if (!data.success || !data.data || data.data.length === 0) {
                tbody.innerHTML = '<tr class="prod-empty"><td colspan="5" style="text-align:center; padding:32px 16px; color:#94a3b8;">No accounts found.</td></tr>';
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

                return `
                <tr onclick="viewAccountHistory(${a.id})" style="cursor: pointer;" title="Click to view transaction history">
                    <td><strong style="font-family: monospace; font-size: 12px; color: #2563eb;">${escapeHtml(a.account_code)}</strong></td>
                    <td style="font-weight: 700; color: #0f172a;">${escapeHtml(a.account_name)}</td>
                    <td><span style="background: #f1f5f9; padding: 3px 8px; border-radius: 999px; font-size: 11px; font-weight: 700; text-transform: capitalize; color: #475569;">${escapeHtml(a.account_type)}</span></td>
                    <td class="col-right" style="text-align: right; font-weight: 800; font-family: monospace; color: ${balColor};">${formattedBal}</td>
                    <td>${a.is_system ? '<span class="prod-badge prod-badge-system"><span class="dot"></span>System</span>' : '<span style="color:#64748b; font-size:12px;">Custom</span>'}</td>
                    <td class="col-center" style="text-align: center;"><span class="prod-badge prod-badge-active" onclick="event.stopPropagation();"><span class="dot"></span>${escapeHtml(a.status.toUpperCase())}</span></td>
                </tr>
                `;
            }).join('');
        }

        document.getElementById('coaPagStart').textContent = total > 0 ? startIdx + 1 : 0;
        document.getElementById('coaPagEnd').textContent = endIdx;
        document.getElementById('coaPagTotal').textContent = total;

        renderCoaPaginationNav(coaPage, totalPages);
    }

    async function viewAccountHistory(accountId) {
        openModal('accountHistoryModal');
        const tbody = document.getElementById('accountHistoryBody');
        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: #94a3b8; padding: 32px 16px;">Loading transaction history...</td></tr>';

        try {
            const res = await fetch(`accounting_api.php?action=get_account_history&account_id=${accountId}`);
            const data = await res.json();

            if (!data.success || !data.account) {
                tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: #dc2626; padding: 24px;">${escapeHtml(data.message || 'Error loading history.')}</td></tr>`;
                return;
            }

            const acc = data.account;
            document.getElementById('histAccountTitle').textContent = `${acc.account_code} - ${acc.account_name}`;
            document.getElementById('histAccountType').textContent = acc.account_type;
            document.getElementById('histAccountBranch').textContent = acc.branch_name ? `Branch: ${acc.branch_name}` : 'Global System Account';

            const totalBal = parseFloat(acc.current_balance || 0);
            const formattedTotalBal = (totalBal < 0 ? '-' : '') + Math.abs(totalBal).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById('histAccountBalance').textContent = `LKR ${formattedTotalBal}`;

            const txs = data.transactions || [];
            if (txs.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: #94a3b8; padding: 32px 16px;">No posted transactions found for this account.</td></tr>';
                return;
            }

            tbody.innerHTML = txs.map(t => {
                const debit = parseFloat(t.debit || 0);
                const credit = parseFloat(t.credit || 0);
                const rBal = parseFloat(t.running_balance || 0);
                const isNeg = rBal < 0;
                const rBalStr = (isNeg ? '-' : '') + Math.abs(rBal).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                const desc = t.memo ? `${escapeHtml(t.description)} (${escapeHtml(t.memo)})` : escapeHtml(t.description);

                return `
                    <tr>
                        <td style="font-weight: 600; font-size: 11.5px; color: #475569;">${escapeHtml(t.entry_date)}</td>
                        <td><strong style="font-family: monospace; font-size: 11.5px; color: #2563eb;">${escapeHtml(t.entry_number)}</strong></td>
                        <td><span style="background: #e2e8f0; padding: 2px 7px; border-radius: 999px; font-size: 10.5px; font-weight: 700; color: #334155; text-transform: uppercase;">${escapeHtml(t.source_type)}</span></td>
                        <td style="font-size: 12px; color: #1e293b;">${desc}</td>
                        <td class="col-right" style="text-align: right; font-family: monospace; font-size: 12px; font-weight: 600;">${debit > 0 ? debit.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '-'}</td>
                        <td class="col-right" style="text-align: right; font-family: monospace; font-size: 12px; font-weight: 600;">${credit > 0 ? credit.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '-'}</td>
                        <td class="col-right" style="text-align: right; font-family: monospace; font-size: 12px; font-weight: 800; color: ${isNeg ? '#dc2626' : '#0f172a'};">${rBalStr}</td>
                    </tr>
                `;
            }).join('');
        } catch (err) {
            tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: #dc2626; padding: 24px;">Connection error: ${escapeHtml(err.message)}</td></tr>`;
        }
    }

    function renderCoaPaginationNav(currentPage, totalPages) {
        const container = document.getElementById('coaPagNavBtns');
        if (!container) return;
        container.innerHTML = '';

        const prevBtn = document.createElement('button');
        prevBtn.className = 'prod-act-btn prod-act-edit';
        prevBtn.style.padding = '4px 10px';
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
            pBtn.style.padding = '4px 10px';
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
        nextBtn.style.padding = '4px 10px';
        nextBtn.disabled = currentPage === totalPages;
        nextBtn.innerHTML = `Next →`;
        nextBtn.onclick = () => { if (currentPage < totalPages) { coaPage = currentPage + 1; updateCoaPagination(); } };
        container.appendChild(nextBtn);
    }

    function changeCoaPageSize() {
        coaPageSize = parseInt(document.getElementById('coaPagSize').value) || 15;
        coaPage = 1;
        updateCoaPagination();
    }

    function openAccountModal() {
        document.getElementById('accountForm').reset();
        openModal('accountModal');
    }

    async function submitAccount(e) {
        e.preventDefault();
        const code = document.getElementById('accCode').value;
        const name = document.getElementById('accName').value;
        const type = document.getElementById('accType').value;

        try {
            const res = await fetch('accounting_api.php?action=create_account', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ account_code: code, account_name: name, account_type: type })
            });

            const data = await res.json();
            if (data.success) {
                alert(data.message);
                closeModal('accountModal');
                loadCOA();
            } else {
                alert('Error: ' + data.message);
            }
        } catch (err) {
            alert('Connection error: ' + err.message);
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const coaTypeEl = document.getElementById('filterCoaType');
        if (coaTypeEl && window.CurtissSearchableSelect) {
            CurtissSearchableSelect.attach(coaTypeEl, { searchPlaceholder: 'Type to search...' });
        }
        loadCOA();
    });

    /* ── Realtime Search in Floating Bottom Nav Bar ── */
    function registerBottomSearch() {
        if (window.CurtissNav && window.CurtissNav.enablePageSearch) {
            window.CurtissNav.enablePageSearch({
                placeholder: 'Search COA accounts (code, name, type...)...',
                onSearch: function(query) {
                    loadCOA();
                }
            });
        } else {
            setTimeout(registerBottomSearch, 40);
        }
    }
    registerBottomSearch();
</script>
