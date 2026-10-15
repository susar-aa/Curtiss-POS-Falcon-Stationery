<?php
/**
 * Curtiss POS - Dedicated Salary Advances Management View
 * Features:
 * - Fixed screen viewport & internal scrolling
 * - 48px table rows & zebra striping
 * - Sticky table headers
 * - Disburse Advance Modal with live employee stats & universal payment account routing
 * - Direct interactive status pill & cancellation
 * - Aging & Ledger Analytics Tab
 */

$canPay = Auth::hasPermission('payroll.pay') || Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_ACCOUNTANT);
$branchName = $currentBranch['name'] ?? 'Main Branch';
?>

<link rel="stylesheet" href="assets/css/curtiss_select.css">
<script src="assets/js/curtiss_select.js"></script>

<!-- Floating Top-Right Action Controls -->
<div id="curtiss-floating-header-right" class="no-print" style="position: fixed; top: 16px; right: 20px; z-index: 1050; display: flex; align-items: center; gap: 8px;">
    <a href="payroll.php" class="inv-btn inv-btn-ghost">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="4" width="20" height="16" rx="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
        Payroll Sheet
    </a>

    <?php if ($canPay): ?>
    <button type="button" class="inv-btn inv-btn-primary" onclick="openDisburseModal()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
        + Disburse Advance
    </button>
    <?php endif; ?>
</div>

<style>
.inv-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    height: 34px;
    min-height: 34px;
    padding: 0 14px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    border: 1px solid transparent;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.18s ease;
    white-space: nowrap;
    box-sizing: border-box;
}
.inv-btn-primary {
    background: #0f172a;
    color: #ffffff;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.18);
}
.inv-btn-primary:hover {
    background: #1e293b;
    transform: translateY(-1px);
}
.inv-btn-ghost {
    background: rgba(255, 255, 255, 0.95);
    border-color: rgba(226, 232, 240, 0.9);
    color: #0f172a;
    backdrop-filter: blur(12px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}
.inv-btn-ghost:hover {
    background: #ffffff;
    border-color: #cbd5e1;
    transform: translateY(-1px);
}

.inv-title {
    font-size: 1.35rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.025em;
    line-height: 1.2;
    margin: 0;
}

.prod-table-panel {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid rgba(226, 232, 240, 0.8);
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.prod-table-scroll {
    overflow-y: auto;
    flex: 1;
}
.prod-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12.5px;
    table-layout: fixed;
}
.prod-table thead th {
    height: 40px;
    padding: 10px 14px;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #64748b;
    position: sticky;
    top: 0;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    z-index: 10;
    text-align: left;
    box-sizing: border-box;
}
.prod-table tbody tr {
    height: 48px;
    min-height: 48px;
    transition: background 0.12s ease;
}
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
    border-bottom: 1px solid #f1f5f9;
}

.col-right { text-align: right !important; }
.col-center { text-align: center !important; }

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
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    user-select: none;
    box-sizing: border-box;
}
.prod-badge .dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
}
.prod-badge-active {
    background: #dcfce7;
    color: #15803d;
    border: 1px solid #bbf7d0;
}
.prod-badge-active .dot { background: #16a34a; }
.prod-badge-recovered {
    background: #e0f2fe;
    color: #0369a1;
    border: 1px solid #bae6fd;
}
.prod-badge-recovered .dot { background: #0284c7; }
.prod-badge-cancelled {
    background: #fee2e2;
    color: #b91c1c;
    border: 1px solid #fecaca;
}
.prod-badge-cancelled .dot { background: #dc2626; }

.adv-tab-pill {
    background: none;
    border: none;
    font-size: 12px;
    font-weight: 700;
    color: #64748b;
    padding: 6px 14px;
    border-radius: 999px;
    cursor: pointer;
    transition: all 0.15s ease;
}
.adv-tab-pill.active {
    background: #ffffff;
    color: #0f172a;
    box-shadow: 0 2px 6px rgba(0,0,0,0.06);
}

.curtiss-modal {
    display: none;
    position: fixed;
    top: 0; left: 0; width: 100vw; height: 100vh;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(4px);
    z-index: 1100;
    align-items: center;
    justify-content: center;
}
.curtiss-modal.active { display: flex; }
.curtiss-modal-content {
    background: #ffffff;
    border-radius: 20px;
    width: 680px;
    max-width: 95vw;
    box-shadow: 0 20px 40px rgba(15, 23, 42, 0.18);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: modalPop 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes modalPop {
    from { opacity: 0; transform: scale(0.96) translateY(8px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
.curtiss-modal-header {
    padding: 18px 24px;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.curtiss-modal-body {
    padding: 24px;
    overflow-y: auto;
    max-height: 80vh;
}
.curtiss-modal-footer {
    padding: 16px 24px;
    background: #f8fafc;
    border-top: 1px solid #f1f5f9;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}
.form-group { margin-bottom: 16px; }
.form-label {
    display: block;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #475569;
    margin-bottom: 6px;
}
.form-control {
    width: 100%;
    height: 38px;
    padding: 0 12px;
    border-radius: 10px;
    border: 1px solid #cbd5e1;
    font-size: 13px;
    font-weight: 600;
    color: #0f172a;
    box-sizing: border-box;
    outline: none;
    transition: border-color 0.15s ease;
}
.form-control:focus {
    border-color: #0f172a;
    box-shadow: 0 0 0 2px rgba(15, 23, 42, 0.08);
}

.kpi-card {
    background: #ffffff;
    border: 1px solid rgba(226, 232, 240, 0.8);
    border-radius: 14px;
    padding: 16px 20px;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
}
.kpi-title {
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #64748b;
}
.kpi-val {
    font-size: 1.45rem;
    font-weight: 900;
    color: #0f172a;
    margin-top: 4px;
    letter-spacing: -0.02em;
}
</style>

<!-- Main Fixed Viewport Container -->
<div style="width: 100%; max-width: 100%; height: calc(100vh - 110px); display: flex; flex-direction: column; box-sizing: border-box; overflow: hidden; padding-bottom: 8px;">
    
    <!-- Page Header (Headline Only) & Inline Filter Bar -->
    <div class="inv-header" style="flex-shrink: 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 0.85rem;">
        <div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
                <h1 class="inv-title">Salary Advance Ledger</h1>
                <span style="font-size: 12px; font-weight: 700; color: #64748b; background: #f1f5f9; padding: 2px 8px; border-radius: 6px;">
                    <?= htmlspecialchars($branchName) ?>
                </span>
            </div>
        </div>

        <!-- Inline Filters -->
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <!-- Status Filter Dropdown -->
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Status:</span>
                <select id="filterAdvStatus" onchange="loadAdvances()" style="height: 34px; min-height: 34px; padding: 0 14px; border-radius: 999px; font-size: 12px; font-weight: 700; border: 1px solid #cbd5e1; outline: none;">
                    <option value="">All Statuses</option>
                    <option value="active">Active (Due)</option>
                    <option value="fully_recovered">Fully Recovered</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>

            <!-- Page Size -->
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Show:</span>
                <select id="filterAdvLimit" onchange="loadAdvances()" style="height: 34px; min-height: 34px; padding: 0 12px; border-radius: 999px; font-size: 12px; font-weight: 700; border: 1px solid #cbd5e1; outline: none;">
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="75">75</option>
                    <option value="100">100</option>
                    <option value="500">500</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Salary Advance Ledger Table Panel -->
    <div id="paneRegister" class="prod-table-panel" style="flex: 1; min-height: 0; display: flex; flex-direction: column;">
        <div class="prod-table-scroll">
            <table class="prod-table" id="advancesTable">
                <thead>
                    <tr>
                        <th style="width: 120px;">Advance #</th>
                        <th style="width: 95px;">Date</th>
                        <th style="width: 220px;">Employee</th>
                        <th style="width: 150px;">Payment Source</th>
                        <th class="col-right" style="width: 110px;">Amount</th>
                        <th class="col-right" style="width: 110px;">Recovered</th>
                        <th class="col-right" style="width: 110px;">Remaining</th>
                        <th style="width: 150px;">Scheduled Period</th>
                        <th class="col-center" style="width: 110px;">Status</th>
                        <th class="col-right" style="width: 90px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="advancesTableBody">
                    <tr><td colspan="10" style="text-align: center; color: #94a3b8; padding: 32px;">Loading salary advances...</td></tr>
                </tbody>
            </table>
        </div>
        
        <!-- Table Pagination Footer -->
        <div style="padding: 10px 18px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: #64748b; flex-shrink: 0;">
            <div id="advPaginationInfo">Showing 0 of 0 advances</div>
            <div style="display: flex; gap: 6px;" id="advPaginationBtns"></div>
        </div>
    </div>
</div>

<!-- Modal: Disburse Advance -->
<div class="curtiss-modal" id="modalDisburseAdvance">
    <div class="curtiss-modal-content">
        <div class="curtiss-modal-header">
            <span style="font-weight: 800; font-size: 1.15rem; color: #0f172a;">Disburse Salary Advance</span>
            <button type="button" onclick="closeModal('modalDisburseAdvance')" style="background:none; border:none; font-size:1.5rem; color:#64748b; cursor:pointer;">&times;</button>
        </div>
        <form id="disburseAdvanceForm" onsubmit="handleDisburseAdvance(event)">
            <div class="curtiss-modal-body">
                <!-- Select Employee (Staff of current branch only) -->
                <div class="form-group">
                    <label class="form-label">Select Staff Member *</label>
                    <select class="form-control" name="employee_id" id="advEmployeeSelect" onchange="onEmployeeSelected(this.value)" required>
                        <option value="">-- Choose Employee (<?= htmlspecialchars($branchName) ?>) --</option>
                        <?php foreach ($activeEmployees as $ae): ?>
                            <option value="<?= $ae['id'] ?>" data-basic="<?= (float)$ae['basic_salary'] ?>" data-code="<?= htmlspecialchars($ae['employee_code']) ?>">
                                <?= htmlspecialchars($ae['employee_code']) ?> - <?= htmlspecialchars($ae['full_name']) ?> (Basic: LKR <?= number_format($ae['basic_salary'], 2) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Dynamic Employee Stats Banner -->
                <div id="advEmpStatsBanner" style="display: none; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 16px; margin-bottom: 1rem;">
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; font-size: 12px;">
                        <div>
                            <span style="color: #64748b; font-size: 11px; text-transform: uppercase; font-weight: 700;">Basic Salary:</span>
                            <div id="bannerEmpBasic" style="font-weight: 800; font-size: 13.5px; color: #0f172a; margin-top: 2px;">LKR 0.00</div>
                        </div>
                        <div>
                            <span style="color: #64748b; font-size: 11px; text-transform: uppercase; font-weight: 700;">Outstanding Advance:</span>
                            <div id="bannerEmpOutstanding" style="font-weight: 800; font-size: 13.5px; color: #b45309; margin-top: 2px;">LKR 0.00</div>
                        </div>
                        <div>
                            <span style="color: #64748b; font-size: 11px; text-transform: uppercase; font-weight: 700;">Active Advances:</span>
                            <div id="bannerEmpCount" style="font-weight: 800; font-size: 13.5px; color: #0f172a; margin-top: 2px;">0</div>
                        </div>
                    </div>
                </div>

                <!-- Advance Amount & Payment Source Account -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label class="form-label">Advance Amount (LKR) *</label>
                        <input type="number" step="0.01" min="0.01" class="form-control" name="amount" id="advAmountInput" required placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Disbursement Source Account *</label>
                        <select class="form-control" id="advAccountSelect" onchange="onAccountSelected(this)" required>
                            <option value="">-- Select Source Account --</option>
                            <?php foreach ($paymentAccounts as $pa): ?>
                                <option value="<?= htmlspecialchars(json_encode($pa)) ?>" <?= ($pa['account_code'] === '1012') ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($pa['formatted_label']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="payment_method" id="advPaymentMethod" value="Cash Drawer">
                        <input type="hidden" name="source_account_id" id="advSourceAccountId" value="49">
                        <input type="hidden" name="bank_account_id" id="advBankAccountId" value="">
                    </div>
                </div>

                <!-- Disbursement Date & Recovery Period -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label class="form-label">Disbursement Date *</label>
                        <input type="date" class="form-control" name="disbursement_date" id="advDateInput" required value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Scheduled Recovery Period</label>
                        <select class="form-control" name="recovery_period_id" id="advRecoveryPeriodSelect">
                            <option value="">-- Next Regular Payroll Period --</option>
                            <?php foreach ($availablePeriods as $ap): ?>
                                <option value="<?= $ap['id'] ?>"><?= htmlspecialchars($ap['period_name']) ?> (<?= htmlspecialchars($ap['period_code']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Reference & Notes -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label class="form-label">Reference No / Cheque No</label>
                        <input type="text" class="form-control" name="reference_no" placeholder="Optional reference...">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Reason / Remarks</label>
                        <input type="text" class="form-control" name="notes" placeholder="e.g. Festival advance, emergency...">
                    </div>
                </div>
            </div>
            <div class="curtiss-modal-footer">
                <button type="button" class="inv-btn inv-btn-ghost" onclick="closeModal('modalDisburseAdvance')">Cancel</button>
                <button type="submit" class="inv-btn inv-btn-primary" id="btnSubmitAdvance">Disburse Advance</button>
            </div>
        </form>
    </div>
</div>

<script>
let currentAdvPage = 1;
let currentAdvLimit = 25;
let currentSearchTerm = '';
let currentMainTab = 'register';
const currentBranchId = <?= (int)$currentBranchId ?>;

document.addEventListener('DOMContentLoaded', () => {
    // Enhance searchable selects
    const empSelect = document.getElementById('advEmployeeSelect');
    if (empSelect && window.CurtissSearchableSelect) {
        CurtissSearchableSelect.attach(empSelect);
    }
    const accSelect = document.getElementById('advAccountSelect');
    if (accSelect && window.CurtissSearchableSelect) {
        CurtissSearchableSelect.attach(accSelect);
    }

    loadAdvances();

    // Attach CurtissNav page search if available
    if (window.CurtissNav && typeof CurtissNav.enablePageSearch === 'function') {
        CurtissNav.enablePageSearch((term) => {
            currentSearchTerm = term;
            currentAdvPage = 1;
            loadAdvances();
        });
    }
});

async function loadAdvances() {
    const status = document.getElementById('filterAdvStatus').value;
    const limit = parseInt(document.getElementById('filterAdvLimit').value) || 25;
    currentAdvLimit = limit;

    const tbody = document.getElementById('advancesTableBody');
    tbody.innerHTML = '<tr><td colspan="10" style="text-align: center; color: #94a3b8; padding: 32px;">Loading advances...</td></tr>';

    try {
        const url = `payroll_api.php?action=list_advances&branch_id=${currentBranchId}&status=${encodeURIComponent(status)}&search=${encodeURIComponent(currentSearchTerm)}&page=${currentAdvPage}&limit=${currentAdvLimit}`;
        const res = await fetch(url);
        const json = await res.json();

        const items = json.items || (json.data && json.data.items) || [];
        const total = (json.total !== undefined) ? json.total : ((json.data && json.data.total) !== undefined ? json.data.total : items.length);
        const totalPages = json.total_pages || (json.data && json.data.total_pages) || 1;
        const page = json.page || (json.data && json.data.page) || 1;

        if (items.length === 0) {
            tbody.innerHTML = '<tr><td colspan="10" style="text-align: center; color: #94a3b8; padding: 32px;">No salary advances found.</td></tr>';
            renderPagination(0, 0, 1);
            return;
        }

        renderAdvancesList(items);
        renderPagination(total, totalPages, page);
    } catch (err) {
        console.error("Error loading advances:", err);
        tbody.innerHTML = '<tr><td colspan="10" style="text-align: center; color: #ef4444; padding: 32px;">Error loading advances.</td></tr>';
    }
}

function renderAdvancesList(items) {
    const tbody = document.getElementById('advancesTableBody');
    let html = '';

    items.forEach(adv => {
        const amt = parseFloat(adv.amount);
        const rec = parseFloat(adv.recovered_amount);
        const rem = parseFloat(adv.remaining_amount);

        let badgeClass = 'prod-badge-active';
        let badgeLabel = 'Active (Due)';
        if (adv.status === 'fully_recovered') {
            badgeClass = 'prod-badge-recovered';
            badgeLabel = 'Recovered';
        } else if (adv.status === 'cancelled') {
            badgeClass = 'prod-badge-cancelled';
            badgeLabel = 'Cancelled';
        }

        let cancelBtn = '';
        if (adv.status === 'active' && rec === 0) {
            cancelBtn = `
                <button type="button" class="inv-btn" style="height: 28px; min-height: 28px; line-height: 26px; padding: 0 10px; font-size: 11px; color: #dc2626; background: #fef2f2; border-color: #fca5a5;" onclick="promptCancelAdvance(${adv.id}, '${escapeHtml(adv.advance_no)}', '${escapeHtml(adv.employee_name)}')">
                    Cancel
                </button>
            `;
        }

        html += `
            <tr>
                <td style="font-weight: 800; font-family: monospace;">${escapeHtml(adv.advance_no)}</td>
                <td style="color: #64748b;">${escapeHtml(adv.disbursement_date)}</td>
                <td>
                    <div style="font-weight: 800; color: #0f172a;">${escapeHtml(adv.employee_name)}</div>
                    <div style="font-size: 11px; color: #64748b;">${escapeHtml(adv.employee_code)}</div>
                </td>
                <td>
                    <div style="font-weight: 700; color: #334155;">${escapeHtml(adv.payment_method)}</div>
                    <div style="font-size: 10.5px; color: #64748b;">${escapeHtml(adv.source_account_name || '')}</div>
                </td>
                <td class="col-right" style="font-weight: 800;">${amt.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td class="col-right" style="color: #16a34a; font-weight: 700;">${rec.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td class="col-right" style="font-weight: 800; color: ${rem > 0 ? '#b45309' : '#64748b'};">${rem.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td style="font-size: 11.5px; color: #475569;">${escapeHtml(adv.recovery_period_name || 'Next Regular')}</td>
                <td class="col-center">
                    <span class="prod-badge ${badgeClass}"><span class="dot"></span>${badgeLabel}</span>
                </td>
                <td class="col-right">${cancelBtn}</td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

function renderPagination(total, totalPages, curPage) {
    document.getElementById('advPaginationInfo').innerText = `Showing page ${curPage} of ${totalPages} (${total} total advances)`;
    const btnContainer = document.getElementById('advPaginationBtns');
    if (totalPages <= 1) {
        btnContainer.innerHTML = '';
        return;
    }

    let btns = '';
    if (curPage > 1) {
        btns += `<button class="inv-btn inv-btn-ghost" style="height: 28px; padding: 0 10px;" onclick="goToPage(${curPage - 1})">← Prev</button>`;
    }
    if (curPage < totalPages) {
        btns += `<button class="inv-btn inv-btn-ghost" style="height: 28px; padding: 0 10px;" onclick="goToPage(${curPage + 1})">Next →</button>`;
    }
    btnContainer.innerHTML = btns;
}

function goToPage(p) {
    currentAdvPage = p;
    loadAdvances();
}

function openDisburseModal() {
    document.getElementById('disburseAdvanceForm').reset();
    document.getElementById('advEmpStatsBanner').style.display = 'none';
    document.getElementById('modalDisburseAdvance').classList.add('active');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

async function onEmployeeSelected(empId) {
    if (!empId) {
        document.getElementById('advEmpStatsBanner').style.display = 'none';
        return;
    }

    try {
        const res = await fetch(`payroll_api.php?action=get_employee_advance_summary&employee_id=${empId}`);
        const json = await res.json();
        if (json.success) {
            document.getElementById('bannerEmpBasic').innerText = `LKR ${parseFloat(json.basic_salary).toLocaleString('en-US', {minimumFractionDigits: 2})}`;
            document.getElementById('bannerEmpOutstanding').innerText = `LKR ${parseFloat(json.remaining_balance).toLocaleString('en-US', {minimumFractionDigits: 2})}`;
            document.getElementById('bannerEmpCount').innerText = `${json.active_count} active`;
            document.getElementById('advEmpStatsBanner').style.display = 'block';
        }
    } catch (err) {
        console.error("Error loading employee advance summary:", err);
    }
}

function onAccountSelected(sel) {
    if (!sel.value) return;
    try {
        const acc = JSON.parse(sel.value);
        document.getElementById('advPaymentMethod').value = acc.payment_method;
        document.getElementById('advSourceAccountId').value = acc.account_id;
        document.getElementById('advBankAccountId').value = acc.bank_account_id || '';
    } catch (e) {
        console.error("Error parsing account JSON:", e);
    }
}

async function handleDisburseAdvance(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSubmitAdvance');
    btn.disabled = true;
    btn.innerText = 'Disbursing...';

    try {
        const form = document.getElementById('disburseAdvanceForm');
        const formData = new FormData(form);

        const res = await fetch('payroll_api.php?action=disburse_advance', {
            method: 'POST',
            body: formData
        });
        const json = await res.json();

        if (json.success) {
            alert(json.message || "Advance disbursed successfully!");
            closeModal('modalDisburseAdvance');
            loadAdvances();
        } else {
            alert(json.message || "Failed to disburse advance.");
        }
    } catch (err) {
        alert("Server error disbursing salary advance.");
    } finally {
        btn.disabled = false;
        btn.innerText = 'Disburse Advance';
    }
}

async function promptCancelAdvance(advId, advNo, empName) {
    const reason = prompt(`Cancel and reverse Salary Advance #${advNo} for ${empName}?\n\nEnter reason for cancellation:`);
    if (!reason || !reason.trim()) return;

    try {
        const formData = new FormData();
        formData.append('advance_id', advId);
        formData.append('reason', reason.trim());

        const res = await fetch('payroll_api.php?action=cancel_advance', {
            method: 'POST',
            body: formData
        });
        const json = await res.json();

        if (json.success) {
            alert(json.message || "Salary advance cancelled and reversed.");
            loadAdvances();
            loadAnalytics();
        } else {
            alert(json.message || "Failed to cancel salary advance.");
        }
    } catch (err) {
        alert("Server error cancelling salary advance.");
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
</script>
