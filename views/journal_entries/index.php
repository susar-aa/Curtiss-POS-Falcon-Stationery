<?php
/**
 * Curtiss POS - Journal Entries View (Stage 10 Redesign)
 * Full Glassmorphic ERP Interface for Journal Entries, Double-Entry Validation, Manual Postings & Reversals.
 */
?>
<meta name="csrf-token" content="<?= htmlspecialchars(Auth::generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
<link rel="stylesheet" href="assets/css/curtiss_select.css">
<script src="assets/js/curtiss_select.js"></script>

<!-- Floating Top-Right Action Header Buttons -->
<div id="curtiss-floating-header-right" class="no-print" style="position: fixed; top: 16px; right: 20px; z-index: 1050; display: flex; align-items: center; gap: 8px;">
    <?php if (Auth::hasPermission('accounting.post_manual')): ?>
    <button type="button" class="inv-btn inv-btn-primary" onclick="openManualJournalModal()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        New Manual Journal Entry
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

    /* Glassmorphism Cards & Filters */
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
    .prod-badge-posted   { background: #dcfce7; color: #15803d; border: 1px solid rgba(22,163,74,0.25); }
    .prod-badge-reversed { background: #fee2e2; color: #b91c1c; border: 1px solid rgba(220,38,38,0.25); }
    .prod-badge .dot {
        width: 6px; height: 6px; border-radius: 50%; display: inline-block;
    }
    .prod-badge-posted   .dot { background: #16a34a; }
    .prod-badge-reversed .dot { background: #dc2626; }

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
        position: sticky;
        top: 0;
        z-index: 10;
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
        text-align: right !important;
    }
    .col-center {
        text-align: center !important;
    }
    .col-left {
        text-align: left !important;
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
        border-color: #f87171;
        color: #b91c1c;
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
        max-height: calc(100vh - 32px);
        display: flex;
        flex-direction: column;
        border-radius: 18px;
        padding: 22px 26px;
        box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.15);
        border: 1px solid rgba(226, 232, 240, 0.9);
        animation: modalSlideUp 0.22s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        overflow: hidden;
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

    .journal-line-row {
        display: grid;
        grid-template-columns: 2.2fr 1fr 1fr 2fr 36px;
        gap: 8px;
        margin-bottom: 8px;
        align-items: center;
    }

    .balance-indicator {
        font-weight: 700;
        padding: 10px 14px;
        border-radius: 12px;
        text-align: center;
        font-size: 12.5px;
    }
    .balance-matched { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    .balance-unmatched { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }
</style>

<div style="width: 100%; max-width: 100%; height: calc(100vh - 110px); display: flex; flex-direction: column; box-sizing: border-box; overflow: hidden; padding-bottom: 8px;">
    <!-- Page Header -->
    <div class="inv-header" style="flex-shrink: 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 0.85rem;">
        <div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
                <h1 class="inv-title">Journal Entries</h1>
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Branch:</span>
                <select id="filterJournalBranch" onchange="applyJournalFilters()" style="height: 34px; min-height: 34px; padding: 0 14px; border-radius: 999px; font-size: 12px; font-weight: 700; color: #334155; background: rgba(255,255,255,0.95); border: 1px solid rgba(203,213,225,0.9); box-shadow: 0 2px 8px rgba(0,0,0,0.04); min-width: 170px;">
                    <option value="all">All Branches</option>
                    <?php if (!empty($branches)): foreach ($branches as $br): ?>
                    <option value="<?= (int)$br['id'] ?>" <?= (int)$br['id'] === (int)$branchId ? 'selected' : '' ?>><?= htmlspecialchars($br['name']) ?></option>
                    <?php endforeach; endif; ?>
                </select>
            </div>
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Source:</span>
                <select id="filterJournalSource" onchange="applyJournalFilters()" style="height: 34px; min-height: 34px; padding: 0 14px; border-radius: 999px; font-size: 12px; font-weight: 700; color: #334155; background: rgba(255,255,255,0.95); border: 1px solid rgba(203,213,225,0.9); box-shadow: 0 2px 8px rgba(0,0,0,0.04); min-width: 170px;">
                    <option value="">All Sources</option>
                    <option value="manual">Manual Journal</option>
                    <option value="pos_sale">POS Sale</option>
                    <option value="grn">Goods Received Note</option>
                    <option value="supplier_payment">Supplier Payment</option>
                    <option value="customer_collection">Customer Collection</option>
                    <option value="sales_return">Sales Return</option>
                </select>
            </div>
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Status:</span>
                <select id="filterJournalStatus" onchange="applyJournalFilters()" style="height: 34px; min-height: 34px; padding: 0 14px; border-radius: 999px; font-size: 12px; font-weight: 700; color: #334155; background: rgba(255,255,255,0.95); border: 1px solid rgba(203,213,225,0.9); box-shadow: 0 2px 8px rgba(0,0,0,0.04); min-width: 140px;">
                    <option value="">All Statuses</option>
                    <option value="posted">Posted</option>
                    <option value="reversed">Reversed</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Journal Entries Table Panel -->
    <div class="prod-table-panel" style="flex: 1; min-height: 0; display: flex; flex-direction: column;">
        <div style="flex-shrink: 0; padding: 12px 18px; border-bottom: 1px solid #e2e8f0; background: rgba(248, 250, 252, 0.8); display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0f172a" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                <h3 style="font-size: 0.95rem; font-weight: 800; color: #0f172a; margin: 0;">Double-Entry Journal Postings Log</h3>
            </div>
        </div>
        <div class="prod-table-scroll">
            <table class="prod-table" id="journalTable">
                <thead>
                    <tr>
                        <th style="width: 135px; text-align: left;">Entry #</th>
                        <th style="width: 95px; text-align: left;">Date</th>
                        <th style="width: 115px; text-align: left;">Source</th>
                        <th style="max-width: 320px; text-align: left;">Description / Narrative</th>
                        <th style="width: 125px; text-align: right;" class="col-right">Debit (LKR)</th>
                        <th style="width: 125px; text-align: right;" class="col-right">Credit (LKR)</th>
                        <th style="width: 100px; text-align: center;" class="col-center">Status</th>
                        <th style="width: 120px; text-align: center;" class="col-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="journalTableBody">
                    <tr class="prod-empty"><td colspan="8" style="text-align: center; color: #94a3b8; padding: 32px 16px;">Loading journal entries...</td></tr>
                </tbody>
            </table>
        </div>
        <!-- Table Pagination Footer -->
        <div class="prod-table-footer">
            <div style="font-size: 12px; font-weight: 600; color: #64748b;" id="journalPagInfo">
                Showing <span id="journalPagStart">0</span> to <span id="journalPagEnd">0</span> of <span id="journalPagTotal">0</span> entries
            </div>
            <div style="display: flex; align-items: center; gap: 14px;">
                <div style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: #64748b; font-weight: 600;">
                    <span>Rows:</span>
                    <select id="journalPagSize" onchange="changeJournalPageSize()" style="height: 30px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 8px; font-size: 12px; color: #0f172a; background: #fff; outline: none; font-weight: 600; cursor: pointer;">
                        <option value="25" selected>25</option>
                        <option value="50">50</option>
                        <option value="75">75</option>
                        <option value="100">100</option>
                        <option value="500">500</option>
                    </select>
                </div>
                <div style="display: flex; align-items: center; gap: 4px;" id="journalPagNavBtns"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal 1: New Manual Journal Entry -->
<div class="curtiss-modal-backdrop" id="manualJournalModal">
    <div class="curtiss-modal-card">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid #f1f5f9; flex-shrink: 0;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
                <h3 style="font-size: 1.1rem; font-weight: 800; color: #0f172a; margin: 0;">New Manual Double-Entry Journal Posting</h3>
            </div>
            <button type="button" onclick="closeModal('manualJournalModal')" style="background: none; border: none; color: #94a3b8; cursor: pointer; padding: 4px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <form id="manualJournalForm" onsubmit="submitManualJournal(event)" style="display: flex; flex-direction: column; flex: 1; min-height: 0; overflow: hidden;">
            <div style="flex: 1; overflow-y: auto; padding-right: 4px;">
                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 14px; margin-bottom: 14px;">
                    <div>
                        <label class="form-label">Posting Date <span style="color: #ef4444;">*</span></label>
                        <input type="date" id="mjEntryDate" class="form-input" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div>
                        <label class="form-label">Description / Narrative <span style="color: #ef4444;">*</span></label>
                        <input type="text" id="mjDescription" class="form-input" placeholder="e.g. Month-end depreciation adjustment" required>
                    </div>
                </div>

                <div style="margin-bottom: 14px;">
                    <label class="form-label" style="display: flex; justify-content: space-between; align-items: center;">
                        <span>Journal Lines (Min. 2 lines required) <span style="color: #ef4444;">*</span></span>
                        <button type="button" class="prod-act-btn prod-act-edit" onclick="addJournalLineRow()" style="padding: 3px 10px; font-size: 11px;">+ Add Line</button>
                    </label>
                    <div id="journalLinesContainer" style="margin-top: 8px;">
                        <!-- Dynamic Lines inserted here -->
                    </div>
                </div>

                <!-- Live Double Entry Balance Verification Bar -->
                <div id="balanceIndicator" class="balance-indicator balance-unmatched" style="margin-bottom: 14px;">
                    Total Debit: LKR <span id="lblTotalDebit">0.00</span> | Total Credit: LKR <span id="lblTotalCredit">0.00</span> (Diff: LKR <span id="lblBalanceDiff">0.00</span>)
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 14px; border-top: 1px solid #f1f5f9; flex-shrink: 0; background: #fff;">
                <button type="button" class="inv-btn inv-btn-ghost" onclick="closeModal('manualJournalModal')">Cancel</button>
                <button type="submit" class="inv-btn inv-btn-primary" id="btnSubmitJournal" disabled>Post Journal Entry</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: View Journal Details -->
<div class="curtiss-modal-backdrop" id="journalDetailsModal">
    <div class="curtiss-modal-card">
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

<!-- Modal 3: Reversal Reason -->
<div class="curtiss-modal-backdrop" id="reversalModal">
    <div class="curtiss-modal-card" style="max-width: 500px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid #f1f5f9;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #dc2626;"></div>
                <h3 style="font-size: 1.1rem; font-weight: 800; color: #dc2626; margin: 0;">Reverse Journal Entry</h3>
            </div>
            <button type="button" onclick="closeModal('reversalModal')" style="background: none; border: none; color: #94a3b8; cursor: pointer; padding: 4px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <input type="hidden" id="revJournalId">
        <div style="margin-bottom: 18px;">
            <label class="form-label">Reason for Reversal <span style="color: #ef4444;">*</span></label>
            <textarea id="revReason" class="form-input" rows="3" placeholder="Provide audit reason for reversing this journal..." required></textarea>
        </div>
        <div style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 14px; border-top: 1px solid #f1f5f9;">
            <button type="button" class="inv-btn inv-btn-ghost" onclick="closeModal('reversalModal')">Cancel</button>
            <button type="button" class="inv-btn" id="btnConfirmReversal" style="background: #dc2626; color: #fff; font-weight: 700;" onclick="confirmReversal()">Confirm Reversal</button>
        </div>
    </div>
</div>



<script>
    const coaList = <?= json_encode($coaAccounts) ?>;
    let currentJournalPage = 1, journalLimit = 25;
    let currentJournalSearch = '';
    let searchDebounceTimer = null;

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function closeModal(id) {
        const m = document.getElementById(id);
        if (m) m.classList.remove('active');
    }

    function openModal(id) {
        const m = document.getElementById(id);
        if (m) m.classList.add('active');
    }

    function getJournalSearchValue() {
        return (currentJournalSearch || document.getElementById('searchInput')?.value || '').trim();
    }

    function updateFilterActiveDot() {
        const source = document.getElementById('filterJournalSource')?.value || '';
        const status = document.getElementById('filterJournalStatus')?.value || '';
        const dot = document.getElementById('filterActiveDot');
        if (dot) {
            dot.style.display = (source || status) ? 'block' : 'none';
        }
    }

    function applyJournalFilters() {
        updateFilterActiveDot();
        closeModal('filterModal');
        loadJournalEntries(1);
    }

    function resetJournalFilters() {
        const branch = document.getElementById('filterJournalBranch');
        const source = document.getElementById('filterJournalSource');
        const status = document.getElementById('filterJournalStatus');
        if (branch) branch.value = '<?= (int)$branchId ?>';
        if (source) source.value = '';
        if (status) status.value = '';
        updateFilterActiveDot();
        closeModal('filterModal');
        loadJournalEntries(1);
    }

    async function loadJournalEntries(page = 1) {
        currentJournalPage = page;
        const search = getJournalSearchValue();
        const branch = document.getElementById('filterJournalBranch')?.value || '';
        const source = document.getElementById('filterJournalSource')?.value || '';
        const status = document.getElementById('filterJournalStatus')?.value || '';

        try {
            const res = await fetch(`accounting_api.php?action=get_journals&page=${page}&limit=${journalLimit}&branch_id=${encodeURIComponent(branch)}&search=${encodeURIComponent(search)}&source_type=${encodeURIComponent(source)}&posting_status=${encodeURIComponent(status)}`);
            const data = await res.json();

            const tbody = document.getElementById('journalTableBody');
            if (!data.success || !data.data.items || data.data.items.length === 0) {
                tbody.innerHTML = '<tr class="prod-empty"><td colspan="8" style="text-align:center; padding:32px 16px; color:#94a3b8;">No journal entries found.</td></tr>';
                renderJournalPagination(1, 1, 0);
                return;
            }

            tbody.innerHTML = data.data.items.map(j => `
                <tr onclick="viewJournalDetails(${j.id})" style="cursor: pointer;">
                    <td><strong style="font-family: monospace; font-size:12px; color:#2563eb;">${escapeHtml(j.entry_number)}</strong></td>
                    <td style="color:#64748b; font-size:12px; font-weight:500;">${escapeHtml(j.entry_date)}</td>
                    <td><span style="text-transform:uppercase; font-size:10.5px; font-weight:700; background:#f1f5f9; padding:2px 7px; border-radius:999px; color:#475569;">${escapeHtml(j.source_type)}</span></td>
                    <td style="font-weight:600; color:#0f172a; max-width:320px; word-break:break-word;">${escapeHtml(j.description)}</td>
                    <td class="col-right" style="text-align:right; font-weight:800; color:#16a34a;">${parseFloat(j.total_debit).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
                    <td class="col-right" style="text-align:right; font-weight:800; color:#dc2626;">${parseFloat(j.total_credit).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
                    <td class="col-center" style="text-align:center;">
                        <span class="prod-badge ${j.posting_status === 'posted' ? 'prod-badge-posted' : 'prod-badge-reversed'}">
                            <span class="dot"></span>${escapeHtml(j.posting_status.toUpperCase())}
                        </span>
                    </td>
                    <td class="col-center" style="text-align:center;" onclick="event.stopPropagation();">
                        ${j.posting_status === 'posted' ? `<button type="button" class="prod-act-btn prod-act-danger" onclick="event.stopPropagation(); openReversalModal(${j.id})">Reverse</button>` : '-'}
                    </td>
                </tr>
            `).join('');

            renderJournalPagination(data.data.page, data.data.totalPages, data.data.totalItems);
        } catch (err) {
            console.error('Failed to load journals', err);
        }
    }

    function renderJournalPagination(currentPage, totalPages, totalItems) {
        document.getElementById('journalPagStart').textContent = totalItems > 0 ? (currentPage - 1) * journalLimit + 1 : 0;
        document.getElementById('journalPagEnd').textContent = Math.min(currentPage * journalLimit, totalItems);
        document.getElementById('journalPagTotal').textContent = totalItems;

        const container = document.getElementById('journalPagNavBtns');
        if (!container) return;
        container.innerHTML = '';

        const prevBtn = document.createElement('button');
        prevBtn.className = 'prod-act-btn prod-act-edit';
        prevBtn.style.padding = '4px 10px';
        prevBtn.disabled = currentPage === 1;
        prevBtn.innerHTML = `← Prev`;
        prevBtn.onclick = () => { if (currentPage > 1) loadJournalEntries(currentPage - 1); };
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
            pBtn.onclick = () => loadJournalEntries(p);
            container.appendChild(pBtn);
        }

        const nextBtn = document.createElement('button');
        nextBtn.className = 'prod-act-btn prod-act-edit';
        nextBtn.style.padding = '4px 10px';
        nextBtn.disabled = currentPage === totalPages;
        nextBtn.innerHTML = `Next →`;
        nextBtn.onclick = () => { if (currentPage < totalPages) loadJournalEntries(currentPage + 1); };
        container.appendChild(nextBtn);
    }

    function changeJournalPageSize() {
        journalLimit = parseInt(document.getElementById('journalPagSize').value) || 25;
        loadJournalEntries(1);
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
                <p style="margin:0 0 6px 0;"><strong>Date:</strong> ${escapeHtml(j.entry_date)} | <strong>Branch:</strong> ${escapeHtml(j.branch_name)} | <strong>Posted By:</strong> ${escapeHtml(j.creator_name)}</p>
                <p style="margin:0 0 6px 0;"><strong>Source:</strong> ${escapeHtml(j.source_type)} (ID: ${escapeHtml(j.source_id || 'N/A')})</p>
                <p style="margin:0;"><strong>Description:</strong> ${escapeHtml(j.description)}</p>
            </div>
            <table class="prod-table" style="margin-bottom:14px;">
                <thead>
                    <tr>
                        <th style="text-align:left;">Account Code & Name</th>
                        <th style="text-align:right;" class="col-right">Debit (LKR)</th>
                        <th style="text-align:right;" class="col-right">Credit (LKR)</th>
                        <th style="text-align:left;">Memo</th>
                    </tr>
                </thead>
                <tbody>
                    ${j.lines.map(l => `
                        <tr>
                            <td><strong style="color:#2563eb;">${escapeHtml(l.account_code)}</strong> - ${escapeHtml(l.account_name)}</td>
                            <td class="col-right" style="text-align:right; font-weight:800; color:#16a34a;">${parseFloat(l.debit) > 0 ? parseFloat(l.debit).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) : '-'}</td>
                            <td class="col-right" style="text-align:right; font-weight:800; color:#dc2626;">${parseFloat(l.credit) > 0 ? parseFloat(l.credit).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) : '-'}</td>
                            <td style="color:#64748b; font-size:12px;">${escapeHtml(l.memo || '')}</td>
                        </tr>
                    `).join('')}
                </tbody>
                <tfoot>
                    <tr style="font-weight:800; background:#f8fafc; border-top:2px solid #e2e8f0;">
                        <td>TOTAL</td>
                        <td class="col-right" style="text-align:right; color:#16a34a;">${parseFloat(j.total_debit).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
                        <td class="col-right" style="text-align:right; color:#dc2626;">${parseFloat(j.total_credit).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        `;
    }

    function openReversalModal(id) {
        document.getElementById('revJournalId').value = id;
        document.getElementById('revReason').value = '';
        openModal('reversalModal');
    }

    async function confirmReversal() {
        const id = document.getElementById('revJournalId').value;
        const reason = document.getElementById('revReason').value;
        const confirmBtn = document.getElementById('btnConfirmReversal');

        if (!reason.trim()) {
            alert('Please enter a reversal reason.');
            return;
        }

        if (confirmBtn && confirmBtn.disabled) return;
        if (confirmBtn) {
            confirmBtn.disabled = true;
            confirmBtn.textContent = 'Reversing...';
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        try {
            const res = await fetch('accounting_api.php?action=reverse_journal', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken},
                body: JSON.stringify({ journal_id: id, reason: reason, csrf_token: csrfToken })
            });

            const data = await res.json();
            if (data.success) {
                alert(data.message || 'Journal entry reversed successfully.');
                closeModal('reversalModal');
                loadJournalEntries(1);
            } else {
                alert('Error: ' + (data.message || 'Failed to reverse entry.'));
            }
        } catch (err) {
            alert('Connection error: ' + err.message);
        } finally {
            if (confirmBtn) {
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'Confirm Reversal';
            }
        }
    }

    function openManualJournalModal() {
        document.getElementById('manualJournalForm').reset();
        const container = document.getElementById('journalLinesContainer');
        container.innerHTML = '';
        addJournalLineRow();
        addJournalLineRow();
        recalculateJournalBalance();
        openModal('manualJournalModal');
    }

    function addJournalLineRow() {
        const container = document.getElementById('journalLinesContainer');
        const rowDiv = document.createElement('div');
        rowDiv.className = 'journal-line-row';
        rowDiv.innerHTML = `
            <select class="form-input jl-account" onchange="recalculateJournalBalance()" required style="font-weight:600;">
                <option value="">Select Account...</option>
                ${coaList.map(a => `<option value="${a.id}">${escapeHtml(a.account_code)} - ${escapeHtml(a.account_name)}</option>`).join('')}
            </select>
            <input type="number" step="0.01" min="0" class="form-input jl-debit" placeholder="Debit" oninput="onDebitInput(this)" value="0.00" style="font-weight:700;">
            <input type="number" step="0.01" min="0" class="form-input jl-credit" placeholder="Credit" oninput="onCreditInput(this)" value="0.00" style="font-weight:700;">
            <input type="text" class="form-input jl-memo" placeholder="Memo / Line note">
            <button type="button" onclick="removeJournalLineRow(this)" style="color:#ef4444; background:none; border:none; cursor:pointer; font-size:18px; font-weight:700;">&times;</button>
        `;
        container.appendChild(rowDiv);
    }

    function removeJournalLineRow(btn) {
        const rows = document.querySelectorAll('.journal-line-row');
        if (rows.length <= 2) {
            alert('Journal entry must have at least 2 lines.');
            return;
        }
        btn.parentElement.remove();
        recalculateJournalBalance();
    }

    function onDebitInput(input) {
        if (parseFloat(input.value) > 0) {
            input.parentElement.querySelector('.jl-credit').value = '0.00';
        }
        recalculateJournalBalance();
    }

    function onCreditInput(input) {
        if (parseFloat(input.value) > 0) {
            input.parentElement.querySelector('.jl-debit').value = '0.00';
        }
        recalculateJournalBalance();
    }

    function recalculateJournalBalance() {
        let totDebit = 0.00;
        let totCredit = 0.00;

        document.querySelectorAll('.journal-line-row').forEach(row => {
            const d = parseFloat(row.querySelector('.jl-debit').value) || 0;
            const c = parseFloat(row.querySelector('.jl-credit').value) || 0;
            totDebit += d;
            totCredit += c;
        });

        document.getElementById('lblTotalDebit').innerText = totDebit.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
        document.getElementById('lblTotalCredit').innerText = totCredit.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});

        const diff = Math.abs(totDebit - totCredit);
        document.getElementById('lblBalanceDiff').innerText = diff.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});

        const ind = document.getElementById('balanceIndicator');
        const submitBtn = document.getElementById('btnSubmitJournal');

        if (diff < 0.001 && totDebit > 0) {
            ind.className = 'balance-indicator balance-matched';
            submitBtn.disabled = false;
        } else {
            ind.className = 'balance-indicator balance-unmatched';
            submitBtn.disabled = true;
        }
    }

    async function submitManualJournal(e) {
        e.preventDefault();
        const submitBtn = document.getElementById('btnSubmitJournal');
        if (submitBtn.disabled) return;
        submitBtn.disabled = true;
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Posting Entry...';

        const date = document.getElementById('mjEntryDate').value;
        const desc = document.getElementById('mjDescription').value;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        const lines = [];
        document.querySelectorAll('.journal-line-row').forEach(row => {
            const accId = row.querySelector('.jl-account').value;
            const debit = parseFloat(row.querySelector('.jl-debit').value) || 0;
            const credit = parseFloat(row.querySelector('.jl-credit').value) || 0;
            const memo = row.querySelector('.jl-memo').value;

            if (accId && (debit > 0 || credit > 0)) {
                lines.push({ account_id: accId, debit: debit, credit: credit, memo: memo });
            }
        });

        try {
            const res = await fetch('accounting_api.php?action=post_manual_journal', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken},
                body: JSON.stringify({ entry_date: date, description: desc, lines: lines, csrf_token: csrfToken })
            });

            const data = await res.json();
            if (data.success) {
                alert(data.message || 'Journal entry posted successfully.');
                closeModal('manualJournalModal');
                loadJournalEntries(1);
            } else {
                alert('Error: ' + (data.message || 'Failed to post manual journal.'));
            }
        } catch (err) {
            alert('Connection error: ' + err.message);
        } finally {
            submitBtn.textContent = originalText;
            recalculateJournalBalance();
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const branchEl = document.getElementById('filterJournalBranch');
        if (branchEl && window.CurtissSearchableSelect) {
            CurtissSearchableSelect.attach(branchEl, { searchPlaceholder: 'Type to search branch...' });
        }
        const sourceEl = document.getElementById('filterJournalSource');
        const statusEl = document.getElementById('filterJournalStatus');
        if (sourceEl && window.CurtissSearchableSelect) {
            CurtissSearchableSelect.attach(sourceEl, { searchPlaceholder: 'Type to search source...' });
        }
        if (statusEl && window.CurtissSearchableSelect) {
            CurtissSearchableSelect.attach(statusEl, { searchPlaceholder: 'Type to search status...' });
        }

        // Full keyboard navigation in modal forms
        document.getElementById('manualJournalForm')?.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                const target = e.target;
                if (target.tagName === 'TEXTAREA' || target.tagName === 'BUTTON') return;
                e.preventDefault();
                const inputs = Array.from(this.querySelectorAll('input:not([type="hidden"]):not([disabled]), select:not([disabled])'));
                const idx = inputs.indexOf(target);
                if (idx > -1 && idx < inputs.length - 1) {
                    inputs[idx + 1].focus();
                    if (inputs[idx + 1].select) inputs[idx + 1].select();
                } else if (idx === inputs.length - 1) {
                    const submitBtn = document.getElementById('btnSubmitJournal');
                    if (submitBtn && !submitBtn.disabled) {
                        submitBtn.click();
                    } else {
                        addJournalLineRow();
                    }
                }
            }
        });

        loadJournalEntries(1);
    });

    /* ── Realtime Search in Floating Bottom Nav Bar ── */
    function registerBottomSearch() {
        if (window.CurtissNav && window.CurtissNav.enablePageSearch) {
            window.CurtissNav.enablePageSearch({
                placeholder: 'Search journal entries (JE#, amount, description, status...)...',
                onSearch: function(query) {
                    currentJournalSearch = (query || '').trim();
                    clearTimeout(searchDebounceTimer);
                    searchDebounceTimer = setTimeout(() => {
                        loadJournalEntries(1);
                    }, 300);
                }
            });
        } else {
            setTimeout(registerBottomSearch, 40);
        }
    }
    registerBottomSearch();
</script>
