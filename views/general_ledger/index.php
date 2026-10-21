<?php
/**
 * Curtiss POS - General Ledger View (Stage 10 Optimization)
 * Full Glassmorphic ERP Interface for General Ledger (GL) Statements, Account Ledgers & Transaction History.
 * Adheres strictly to CURTISS_MASTER_RULES & AGENTS.md design, responsiveness, performance, and security standards.
 */
?>
<meta name="csrf-token" content="<?php echo htmlspecialchars(Auth::generateCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
<link rel="stylesheet" href="assets/css/curtiss_select.css">
<script src="assets/js/curtiss_select.js"></script>

<!-- Floating Top-Right Action Header Buttons -->
<div id="curtiss-floating-header-right" class="no-print" style="position: fixed; top: 16px; right: 20px; z-index: 1050; display: flex; align-items: center; gap: 8px;">
    <button type="button" class="inv-btn inv-btn-ghost" onclick="resetGlFilters()" title="Reset all active filters">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
        Reset Filters
    </button>
    <button type="button" class="inv-btn inv-btn-primary" onclick="window.print()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
        Print Statement
    </button>
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

    /* Status Badges - 28px standard height matching action buttons */
    .prod-badge {
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
        white-space: nowrap;
        box-sizing: border-box;
        text-decoration: none;
        cursor: default;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .prod-badge-manual   { background: #f1f5f9; color: #334155; border: 1px solid rgba(203, 213, 225, 0.8); }
    .prod-badge-pos      { background: #e0f2fe; color: #0369a1; border: 1px solid rgba(14, 165, 233, 0.25); }
    .prod-badge-return   { background: #fef3c7; color: #b45309; border: 1px solid rgba(245, 158, 11, 0.25); }
    .prod-badge-grn      { background: #ede9fe; color: #6d28d9; border: 1px solid rgba(139, 92, 246, 0.25); }
    .prod-badge-payment  { background: #dcfce7; color: #15803d; border: 1px solid rgba(22, 163, 74, 0.25); }
    .prod-badge-reversal { background: #fee2e2; color: #b91c1c; border: 1px solid rgba(220, 38, 38, 0.25); }
    .prod-badge-system   { background: #dbeafe; color: #1e40af; border: 1px solid rgba(37, 99, 235, 0.25); }

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
    .col-right {
        text-align: right;
    }
    .col-center {
        text-align: center;
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
        max-width: 780px;
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
</style>

<div style="width: 100%; max-width: 100%; height: calc(100vh - 110px); display: flex; flex-direction: column; box-sizing: border-box; overflow: hidden; padding-bottom: 8px;">
    <!-- Page Header (Headline Only) -->
    <div class="inv-header" style="flex-shrink: 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
                <h1 class="inv-title">General Ledger</h1>
            </div>
        </div>
        <!-- Inline Filters Above Table (Height: 34px) -->
        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Branch:</span>
                <select id="glBranchFilter" onchange="applyGlFilters()" style="height: 34px; min-height: 34px; padding: 0 14px; border-radius: 999px; font-size: 12px; font-weight: 700; color: #334155; background: rgba(255,255,255,0.95); border: 1px solid rgba(203,213,225,0.9); box-shadow: 0 2px 8px rgba(0,0,0,0.04); min-width: 150px;">
                    <option value="all">All Branches</option>
                    <?php if (!empty($branches)): foreach ($branches as $br): ?>
                    <option value="<?= (int)$br['id'] ?>" <?= (int)$br['id'] === (int)$branchId ? 'selected' : '' ?>><?= htmlspecialchars($br['name']) ?></option>
                    <?php endforeach; endif; ?>
                </select>
            </div>
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Account:</span>
                <select id="glAccountFilter" onchange="applyGlFilters()" style="height: 34px; min-height: 34px; padding: 0 14px; border-radius: 999px; font-size: 12px; font-weight: 700; color: #334155; background: rgba(255,255,255,0.95); border: 1px solid rgba(203,213,225,0.9); box-shadow: 0 2px 8px rgba(0,0,0,0.04); min-width: 180px;">
                    <option value="">All Accounts</option>
                    <?php foreach ($coaAccounts as $ca): ?>
                    <option value="<?= (int)$ca['id'] ?>"><?= htmlspecialchars($ca['account_code'] . ' - ' . $ca['account_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Source:</span>
                <select id="glSourceFilter" onchange="applyGlFilters()" style="height: 34px; min-height: 34px; padding: 0 14px; border-radius: 999px; font-size: 12px; font-weight: 700; color: #334155; background: rgba(255,255,255,0.95); border: 1px solid rgba(203,213,225,0.9); box-shadow: 0 2px 8px rgba(0,0,0,0.04); min-width: 130px;">
                    <option value="">All Sources</option>
                    <option value="manual">Manual Entry</option>
                    <option value="sale">POS Sale</option>
                    <option value="sales_return">Sale Return</option>
                    <option value="grn">GRN Receipt</option>
                    <option value="customer_collection">Customer Collection</option>
                    <option value="gift_voucher_issuance">Gift Voucher</option>
                    <option value="payroll">Payroll Entry</option>
                    <option value="reversal">Reversal Entry</option>
                    <option value="opening_balance">Opening Balance</option>
                </select>
            </div>
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">From:</span>
                <input type="date" id="glStartDate" onchange="applyGlFilters()" style="height: 34px; min-height: 34px; padding: 0 12px; border-radius: 999px; font-size: 12px; font-weight: 700; color: #334155; background: rgba(255,255,255,0.95); border: 1px solid rgba(203,213,225,0.9); box-shadow: 0 2px 8px rgba(0,0,0,0.04); outline: none;">
            </div>
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">To:</span>
                <input type="date" id="glEndDate" onchange="applyGlFilters()" style="height: 34px; min-height: 34px; padding: 0 12px; border-radius: 999px; font-size: 12px; font-weight: 700; color: #334155; background: rgba(255,255,255,0.95); border: 1px solid rgba(203,213,225,0.9); box-shadow: 0 2px 8px rgba(0,0,0,0.04); outline: none;">
            </div>
        </div>
    </div>

    <!-- General Ledger Table Panel -->
    <div class="prod-table-panel" style="flex: 1; min-height: 0; display: flex; flex-direction: column;">
        <div style="flex-shrink: 0; padding: 10px 18px; border-bottom: 1px solid #e2e8f0; background: rgba(248, 250, 252, 0.8); display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#0f172a" stroke-width="2.5"><rect x="3" y="4" width="18" height="16" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <h3 style="font-size: 0.9rem; font-weight: 800; color: #0f172a; margin: 0; text-transform: uppercase; letter-spacing: 0.05em;">General Ledger Audit Trail</h3>
            </div>
        </div>
        <div class="prod-table-scroll">
            <table class="prod-table" id="glTable">
                <thead>
                    <tr>
                        <th style="width: 100px; text-align: left;">Date</th>
                        <th style="width: 140px; text-align: left;">Entry #</th>
                        <th style="width: 220px; text-align: left;">Account</th>
                        <th style="width: 130px; text-align: left;">Source</th>
                        <th style="text-align: left;">Description / Line Memo</th>
                        <th style="width: 145px; text-align: right;" class="col-right">Debit (LKR)</th>
                        <th style="width: 145px; text-align: right;" class="col-right">Credit (LKR)</th>
                    </tr>
                </thead>
                <tbody id="glTableBody">
                    <tr class="prod-empty"><td colspan="7" style="text-align: center; color: #94a3b8; padding: 32px 16px;">Loading general ledger entries...</td></tr>
                </tbody>
            </table>
        </div>
        <!-- Table Pagination Footer -->
        <div class="prod-table-footer">
            <div style="font-size: 12px; font-weight: 600; color: #64748b;" id="glPagInfo">
                Showing <span id="glPagStart">0</span> to <span id="glPagEnd">0</span> of <span id="glPagTotal">0</span> entries
            </div>
            <div style="display: flex; align-items: center; gap: 14px;">
                <div style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: #64748b; font-weight: 600;">
                    <span>Rows:</span>
                    <select id="glPagSize" onchange="changeGlPageSize()" style="height: 30px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 8px; font-size: 12px; color: #0f172a; background: #fff; outline: none; font-weight: 600; cursor: pointer;">
                        <option value="25" selected>25</option>
                        <option value="50">50</option>
                        <option value="75">75</option>
                        <option value="100">100</option>
                        <option value="500">500</option>
                    </select>
                </div>
                <div style="display: flex; align-items: center; gap: 4px;" id="glPagNavBtns"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: View Journal Details -->
<div class="curtiss-modal-backdrop" id="journalDetailsModal">
    <div class="curtiss-modal-card">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid #f1f5f9;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #2563eb;"></div>
                <h3 style="font-size: 1.1rem; font-weight: 800; color: #0f172a; margin: 0;" id="jdTitle">Journal Details</h3>
            </div>
            <button type="button" onclick="closeModal('journalDetailsModal')" style="background: none; border: none; color: #94a3b8; cursor: pointer; padding: 4px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div id="jdBody" style="overflow-y: auto; max-height: 70vh;">Loading details...</div>
    </div>
</div>

<script>
    let glPage = 1, glLimit = 25;
    let currentGlSearch = '';
    let searchDebounceTimer = null;

    function closeModal(id) {
        const m = document.getElementById(id);
        if (m) m.classList.remove('active');
    }

    function openModal(id) {
        const m = document.getElementById(id);
        if (m) m.classList.add('active');
    }

    function applyGlFilters() {
        glPage = 1;
        loadGeneralLedger(1);
    }

    function resetGlFilters() {
        const brId  = document.getElementById('glBranchFilter');
        const accId = document.getElementById('glAccountFilter');
        const srcId = document.getElementById('glSourceFilter');
        const start = document.getElementById('glStartDate');
        const end   = document.getElementById('glEndDate');
        if (brId)  brId.value = '<?= (int)$branchId ?>';
        if (accId) accId.value = '';
        if (srcId) srcId.value = '';
        if (start) start.value = '';
        if (end)   end.value = '';
        currentGlSearch = '';
        glPage = 1;
        loadGeneralLedger(1);
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

    function getSourceBadgeClass(src) {
        switch ((src || '').toLowerCase()) {
            case 'manual': return 'prod-badge-manual';
            case 'sale': case 'pos_sale': return 'prod-badge-pos';
            case 'sales_return': return 'prod-badge-return';
            case 'grn': return 'prod-badge-grn';
            case 'customer_collection': case 'payment': return 'prod-badge-payment';
            case 'reversal': return 'prod-badge-reversal';
            default: return 'prod-badge-system';
        }
    }

    async function loadGeneralLedger(page = 1) {
        glPage = page;
        const search = currentGlSearch;
        const branch = document.getElementById('glBranchFilter')?.value || '';
        const accId = document.getElementById('glAccountFilter')?.value || '';
        const source = document.getElementById('glSourceFilter')?.value || '';
        const start = document.getElementById('glStartDate')?.value || '';
        const end   = document.getElementById('glEndDate')?.value || '';

        try {
            const res = await fetch(`accounting_api.php?action=get_general_ledger&page=${page}&limit=${glLimit}&branch_id=${encodeURIComponent(branch)}&account_id=${accId}&source_type=${encodeURIComponent(source)}&start_date=${start}&end_date=${end}&search=${encodeURIComponent(search)}`);
            const data = await res.json();

            const tbody = document.getElementById('glTableBody');
            if (!data.success || !data.data.items || data.data.items.length === 0) {
                tbody.innerHTML = '<tr class="prod-empty"><td colspan="7" style="text-align:center; padding:32px 16px; color:#94a3b8;">No general ledger records found matching filter criteria.</td></tr>';
                renderGlPagination(1, 1, 0);
                return;
            }

            tbody.innerHTML = data.data.items.map(l => {
                const debit = parseFloat(l.debit || 0);
                const credit = parseFloat(l.credit || 0);
                const debitStr = debit > 0 ? debit.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '-';
                const creditStr = credit > 0 ? credit.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '-';
                const srcBadge = getSourceBadgeClass(l.source_type);

                return `
                <tr onclick="viewJournalDetails(${l.journal_id})" style="cursor: pointer;" title="Click row to view complete double-entry journal details">
                    <td style="color:#475569; font-weight:600; font-size:11.5px;">${escapeHtml(l.entry_date)}</td>
                    <td><strong style="font-family: monospace; font-size:11.5px; color:#2563eb;">${escapeHtml(l.entry_number)}</strong></td>
                    <td><strong style="color:#0f172a; font-family: monospace;">${escapeHtml(l.account_code)}</strong> <span style="color:#334155; font-weight:600;">- ${escapeHtml(l.account_name)}</span></td>
                    <td><span class="prod-badge ${srcBadge}">${escapeHtml(l.source_type)}</span></td>
                    <td style="color:#1e293b; font-size:12px;">${escapeHtml(l.description)} ${l.memo ? '<br><small style="color:#64748b;">(' + escapeHtml(l.memo) + ')</small>' : ''}</td>
                    <td class="col-right" style="text-align:right; font-weight:800; font-family: monospace; color:#16a34a;">${debitStr}</td>
                    <td class="col-right" style="text-align:right; font-weight:800; font-family: monospace; color:#dc2626;">${creditStr}</td>
                </tr>
                `;
            }).join('');

            renderGlPagination(data.data.page, data.data.totalPages, data.data.totalItems);
        } catch (err) {
            console.error('Failed to load GL', err);
        }
    }

    function renderGlPagination(currentPage, totalPages, totalItems) {
        document.getElementById('glPagStart').textContent = totalItems > 0 ? (currentPage - 1) * glLimit + 1 : 0;
        document.getElementById('glPagEnd').textContent = Math.min(currentPage * glLimit, totalItems);
        document.getElementById('glPagTotal').textContent = totalItems;

        const container = document.getElementById('glPagNavBtns');
        if (!container) return;
        container.innerHTML = '';

        const prevBtn = document.createElement('button');
        prevBtn.className = 'prod-act-btn prod-act-edit';
        prevBtn.disabled = currentPage === 1;
        prevBtn.innerHTML = `← Prev`;
        prevBtn.onclick = () => { if (currentPage > 1) loadGeneralLedger(currentPage - 1); };
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
            pBtn.onclick = () => loadGeneralLedger(p);
            container.appendChild(pBtn);
        }

        const nextBtn = document.createElement('button');
        nextBtn.className = 'prod-act-btn prod-act-edit';
        nextBtn.disabled = currentPage === totalPages;
        nextBtn.innerHTML = `Next →`;
        nextBtn.onclick = () => { if (currentPage < totalPages) loadGeneralLedger(currentPage + 1); };
        container.appendChild(nextBtn);
    }

    function changeGlPageSize() {
        glLimit = parseInt(document.getElementById('glPagSize').value) || 25;
        loadGeneralLedger(1);
    }

    async function viewJournalDetails(id) {
        openModal('journalDetailsModal');
        const res = await fetch(`accounting_api.php?action=get_journal_details&id=${id}`);
        const data = await res.json();

        if (!data.success || !data.data) {
            document.getElementById('jdBody').innerHTML = '<p style="color:#dc2626; font-weight:700;">Failed to load journal entry details.</p>';
            return;
        }

        const j = data.data;
        document.getElementById('jdTitle').innerText = `Journal Entry ${escapeHtml(j.entry_number)}`;
        document.getElementById('jdBody').innerHTML = `
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:12px 16px; margin-bottom:14px; font-size:12.5px;">
                <p style="margin:0 0 6px 0;"><strong>Date:</strong> ${escapeHtml(j.entry_date)} | <strong>Branch:</strong> ${escapeHtml(j.branch_name || 'Global')} | <strong>Posted By:</strong> ${escapeHtml(j.creator_name || 'System')}</p>
                <p style="margin:0 0 6px 0;"><strong>Source:</strong> <span class="prod-badge ${getSourceBadgeClass(j.source_type)}">${escapeHtml(j.source_type)}</span> ${j.source_id ? '(ID: ' + escapeHtml(j.source_id) + ')' : ''} | <strong>Status:</strong> ${escapeHtml(j.posting_status)}</p>
                <p style="margin:0;"><strong>Description:</strong> ${escapeHtml(j.description)}</p>
            </div>
            <table class="prod-table" style="margin-bottom:14px;">
                <thead>
                    <tr>
                        <th style="text-align: left;">Account Code & Name</th>
                        <th style="text-align: right;" class="col-right">Debit (LKR)</th>
                        <th style="text-align: right;" class="col-right">Credit (LKR)</th>
                        <th style="text-align: left;">Line Memo</th>
                    </tr>
                </thead>
                <tbody>
                    ${j.lines.map(l => `
                        <tr>
                            <td><strong style="color:#2563eb; font-family: monospace;">${escapeHtml(l.account_code)}</strong> - ${escapeHtml(l.account_name)}</td>
                            <td class="col-right" style="text-align:right; font-weight:800; font-family: monospace; color:#16a34a;">${parseFloat(l.debit) > 0 ? parseFloat(l.debit).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) : '-'}</td>
                            <td class="col-right" style="text-align:right; font-weight:800; font-family: monospace; color:#dc2626;">${parseFloat(l.credit) > 0 ? parseFloat(l.credit).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) : '-'}</td>
                            <td style="color:#64748b; font-size:12px;">${escapeHtml(l.memo || '')}</td>
                        </tr>
                    `).join('')}
                </tbody>
                <tfoot>
                    <tr style="font-weight:800; background:#f8fafc; border-top:2px solid #e2e8f0;">
                        <td>TOTAL</td>
                        <td class="col-right" style="text-align:right; color:#16a34a; font-family: monospace;">${parseFloat(j.total_debit).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
                        <td class="col-right" style="text-align:right; color:#dc2626; font-family: monospace;">${parseFloat(j.total_credit).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
                        <td style="color:#16a34a; font-weight:700;">${Math.abs(parseFloat(j.total_debit) - parseFloat(j.total_credit)) < 0.001 ? '✓ Balanced' : '⚠ Unbalanced'}</td>
                    </tr>
                </tfoot>
            </table>
        `;
    }

    document.addEventListener('DOMContentLoaded', () => {
        const glBranchEl = document.getElementById('glBranchFilter');
        if (glBranchEl && window.CurtissSearchableSelect) {
            CurtissSearchableSelect.attach(glBranchEl, { searchPlaceholder: 'Type to search branch...' });
        }
        const glAccEl = document.getElementById('glAccountFilter');
        if (glAccEl && window.CurtissSearchableSelect) {
            CurtissSearchableSelect.attach(glAccEl, { searchPlaceholder: 'Type to search account...' });
        }
        const glSrcEl = document.getElementById('glSourceFilter');
        if (glSrcEl && window.CurtissSearchableSelect) {
            CurtissSearchableSelect.attach(glSrcEl, { searchPlaceholder: 'Type to search source...' });
        }
        loadGeneralLedger(1);
    });

    /* ── Realtime Search in Floating Bottom Nav Bar ── */
    function registerBottomSearch() {
        if (window.CurtissNav && window.CurtissNav.enablePageSearch) {
            window.CurtissNav.enablePageSearch({
                placeholder: 'Search general ledger (JE#, account, memo, amounts...)...',
                onSearch: function(query) {
                    currentGlSearch = (query || '').trim();
                    if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
                    searchDebounceTimer = setTimeout(() => {
                        glPage = 1;
                        loadGeneralLedger(1);
                    }, 300);
                }
            });
        } else {
            setTimeout(registerBottomSearch, 40);
        }
    }
    registerBottomSearch();
</script>
