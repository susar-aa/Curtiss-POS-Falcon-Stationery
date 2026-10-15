<?php
/**
 * Curtiss POS - General Ledger View (Stage 10 Redesign)
 * Full Glassmorphic ERP Interface for General Ledger (GL) Statements, Account Ledgers & Transaction History.
 */
?>
<link rel="stylesheet" href="assets/css/curtiss_select.css">
<script src="assets/js/curtiss_select.js"></script>

<!-- Floating Top-Right Action Header Buttons -->
<div id="curtiss-floating-header-right" class="no-print" style="position: fixed; top: 16px; right: 20px; z-index: 1050; display: flex; align-items: center; gap: 8px;">

    <button type="button" class="inv-btn inv-btn-primary" onclick="window.print()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
        Print Ledger Statement
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

    /* Table Panels */
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
        max-width: 680px;
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
                <h1 class="inv-title">General Ledger</h1>
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Branch:</span>
                <select id="glBranchFilter" onchange="applyGlFilters()" style="height: 34px; min-height: 34px; padding: 0 14px; border-radius: 999px; font-size: 12px; font-weight: 700; color: #334155; background: rgba(255,255,255,0.95); border: 1px solid rgba(203,213,225,0.9); box-shadow: 0 2px 8px rgba(0,0,0,0.04); min-width: 170px;">
                    <option value="all">All Branches</option>
                    <?php if (!empty($branches)): foreach ($branches as $br): ?>
                    <option value="<?= (int)$br['id'] ?>" <?= (int)$br['id'] === (int)$branchId ? 'selected' : '' ?>><?= htmlspecialchars($br['name']) ?></option>
                    <?php endforeach; endif; ?>
                </select>
            </div>
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Account:</span>
                <select id="glAccountFilter" onchange="applyGlFilters()" style="height: 34px; min-height: 34px; padding: 0 14px; border-radius: 999px; font-size: 12px; font-weight: 700; color: #334155; background: rgba(255,255,255,0.95); border: 1px solid rgba(203,213,225,0.9); box-shadow: 0 2px 8px rgba(0,0,0,0.04); min-width: 190px;">
                    <option value="">All Accounts</option>
                    <?php foreach ($coaAccounts as $ca): ?>
                    <option value="<?= (int)$ca['id'] ?>"><?= htmlspecialchars($ca['account_code'] . ' - ' . $ca['account_name']) ?></option>
                    <?php endforeach; ?>
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
        <div style="flex-shrink: 0; padding: 12px 18px; border-bottom: 1px solid #e2e8f0; background: rgba(248, 250, 252, 0.8); display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0f172a" stroke-width="2.5"><rect x="3" y="4" width="18" height="16" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <h3 style="font-size: 0.95rem; font-weight: 800; color: #0f172a; margin: 0;">General Ledger Transactions Audit Trail</h3>
            </div>
        </div>
        <div class="prod-table-scroll">
            <table class="prod-table" id="glTable">
                <thead>
                    <tr>
                        <th style="width: 110px;">Date</th>
                        <th style="width: 150px;">Entry #</th>
                        <th>Account Code & Name</th>
                        <th>Description / Memo</th>
                        <th style="width: 140px; text-align: right;">Debit (LKR)</th>
                        <th style="width: 140px; text-align: right;">Credit (LKR)</th>
                    </tr>
                </thead>
                <tbody id="glTableBody">
                    <tr class="prod-empty"><td colspan="6" style="text-align: center; color: #94a3b8; padding: 32px 16px;">Select filters to view general ledger records...</td></tr>
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

    function closeModal(id) {
        const m = document.getElementById(id);
        if (m) m.classList.remove('active');
    }

    function openModal(id) {
        const m = document.getElementById(id);
        if (m) m.classList.add('active');
    }

    function getGlSearchValue() {
        const globalVal = (document.getElementById('searchInput')?.value || '').trim();
        return globalVal;
    }

    function updateFilterActiveDot() {
        const accId = document.getElementById('glAccountFilter')?.value || '';
        const start = document.getElementById('glStartDate')?.value || '';
        const end   = document.getElementById('glEndDate')?.value || '';
        const dot   = document.getElementById('filterActiveDot');
        if (dot) {
            dot.style.display = (accId || start || end) ? 'block' : 'none';
        }
    }

    function applyGlFilters() {
        updateFilterActiveDot();
        closeModal('filterModal');
        loadGeneralLedger(1);
    }

    function resetGlFilters() {
        const brId  = document.getElementById('glBranchFilter');
        const accId = document.getElementById('glAccountFilter');
        const start = document.getElementById('glStartDate');
        const end   = document.getElementById('glEndDate');
        if (brId)  brId.value = '<?= (int)$branchId ?>';
        if (accId) accId.value = '';
        if (start) start.value = '';
        if (end)   end.value = '';
        updateFilterActiveDot();
        closeModal('filterModal');
        loadGeneralLedger(1);
    }

    async function loadGeneralLedger(page = 1) {
        glPage = page;
        const search = getGlSearchValue();
        const branch = document.getElementById('glBranchFilter')?.value || '';
        const accId = document.getElementById('glAccountFilter')?.value || '';
        const start = document.getElementById('glStartDate')?.value || '';
        const end   = document.getElementById('glEndDate')?.value || '';

        try {
            const res = await fetch(`accounting_api.php?action=get_general_ledger&page=${page}&limit=${glLimit}&branch_id=${encodeURIComponent(branch)}&account_id=${accId}&start_date=${start}&end_date=${end}&search=${encodeURIComponent(search)}`);
            const data = await res.json();

            const tbody = document.getElementById('glTableBody');
            if (!data.success || !data.data.items || data.data.items.length === 0) {
                tbody.innerHTML = '<tr class="prod-empty"><td colspan="6" style="text-align:center; padding:32px 16px; color:#94a3b8;">No general ledger records found.</td></tr>';
                renderGlPagination(1, 1, 0);
                return;
            }

            tbody.innerHTML = data.data.items.map(l => `
                <tr onclick="viewJournalDetails(${l.journal_id})" style="cursor: pointer;">
                    <td style="color:#64748b; font-size:12px; font-weight:500;">${l.entry_date}</td>
                    <td><strong style="font-family: monospace; font-size:12px; color:#2563eb;">${l.entry_number}</strong></td>
                    <td><strong style="color:#0f172a;">${l.account_code}</strong> - ${l.account_name}</td>
                    <td style="color:#475569; font-size:12px;">${l.description} ${l.memo ? '<br><small style="color:#94a3b8;">' + l.memo + '</small>' : ''}</td>
                    <td style="text-align:right; font-weight:800; color:#16a34a;">${parseFloat(l.debit) > 0 ? parseFloat(l.debit).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) : '-'}</td>
                    <td style="text-align:right; font-weight:800; color:#dc2626;">${parseFloat(l.credit) > 0 ? parseFloat(l.credit).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) : '-'}</td>
                </tr>
            `).join('');

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
        prevBtn.style.padding = '4px 10px';
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
            pBtn.style.padding = '4px 10px';
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
        nextBtn.style.padding = '4px 10px';
        nextBtn.disabled = currentPage === totalPages;
        nextBtn.innerHTML = `Next →`;
        nextBtn.onclick = () => { if (currentPage < totalPages) loadGeneralLedger(currentPage + 1); };
        container.appendChild(nextBtn);
    }

    function changeGlPageSize() {
        glLimit = parseInt(document.getElementById('glPagSize').value) || 20;
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
        document.getElementById('jdTitle').innerText = `Journal Entry ${j.entry_number}`;
        document.getElementById('jdBody').innerHTML = `
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:12px 16px; margin-bottom:14px; font-size:12.5px;">
                <p style="margin:0 0 6px 0;"><strong>Date:</strong> ${j.entry_date} | <strong>Branch:</strong> ${j.branch_name} | <strong>Posted By:</strong> ${j.creator_name}</p>
                <p style="margin:0 0 6px 0;"><strong>Source:</strong> ${j.source_type} (ID: ${j.source_id || 'N/A'})</p>
                <p style="margin:0;"><strong>Description:</strong> ${j.description}</p>
            </div>
            <table class="prod-table" style="margin-bottom:14px;">
                <thead>
                    <tr>
                        <th>Account Code & Name</th>
                        <th style="text-align:right;">Debit (LKR)</th>
                        <th style="text-align:right;">Credit (LKR)</th>
                        <th>Memo</th>
                    </tr>
                </thead>
                <tbody>
                    ${j.lines.map(l => `
                        <tr>
                            <td><strong style="color:#2563eb;">${l.account_code}</strong> - ${l.account_name}</td>
                            <td style="text-align:right; font-weight:800; color:#16a34a;">${parseFloat(l.debit) > 0 ? parseFloat(l.debit).toLocaleString('en-US', {minimumFractionDigits:2}) : '-'}</td>
                            <td style="text-align:right; font-weight:800; color:#dc2626;">${parseFloat(l.credit) > 0 ? parseFloat(l.credit).toLocaleString('en-US', {minimumFractionDigits:2}) : '-'}</td>
                            <td style="color:#64748b; font-size:12px;">${l.memo || ''}</td>
                        </tr>
                    `).join('')}
                </tbody>
                <tfoot>
                    <tr style="font-weight:800; background:#f8fafc; border-top:2px solid #e2e8f0;">
                        <td>TOTAL</td>
                        <td style="text-align:right; color:#16a34a;">${parseFloat(j.total_debit).toLocaleString('en-US', {minimumFractionDigits:2})}</td>
                        <td style="text-align:right; color:#dc2626;">${parseFloat(j.total_credit).toLocaleString('en-US', {minimumFractionDigits:2})}</td>
                        <td></td>
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
        loadGeneralLedger(1);
    });

    /* ── Realtime Search in Floating Bottom Nav Bar ── */
    function registerBottomSearch() {
        if (window.CurtissNav && window.CurtissNav.enablePageSearch) {
            window.CurtissNav.enablePageSearch({
                placeholder: 'Search general ledger (JE#, memo, account code, amounts...)...',
                onSearch: function(query) {
                    loadGeneralLedger(1);
                }
            });
        } else {
            setTimeout(registerBottomSearch, 40);
        }
    }
    registerBottomSearch();
</script>
