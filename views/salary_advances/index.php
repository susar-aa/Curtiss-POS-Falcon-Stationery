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

<meta name="csrf-token" content="<?php echo htmlspecialchars(Auth::generateCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
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
            <?php if (Auth::hasRole(ROLE_SUPER_ADMIN)): ?>
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Branch:</span>
                <select id="filterAdvBranch" onchange="loadAdvances()" style="height: 34px; min-height: 34px; padding: 0 14px; border-radius: 999px; font-size: 12px; font-weight: 700; border: 1px solid #cbd5e1; outline: none;">
                    <option value="">All Branches</option>
                    <?php foreach ($branches as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= ($b['id'] == $currentBranchId) ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

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
                    <option value="25" selected>25 per page</option>
                    <option value="50">50 per page</option>
                    <option value="75">75 per page</option>
                    <option value="100">100 per page</option>
                    <option value="500">500 per page</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Summary KPI Ribbon -->
    <div style="flex-shrink: 0; background: #f8fafc; border: 1px solid rgba(226, 232, 240, 0.9); border-radius: 12px; padding: 8px 18px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; font-size: 12px;">
        <div style="display: flex; gap: 24px; align-items: center; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 10px;">Total Disbursed:</span>
                <strong id="kpiTotalDisbursed" style="color: #0f172a; font-size: 13px;">LKR 0.00</strong>
            </div>
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 10px;">Total Recovered:</span>
                <strong id="kpiTotalRecovered" style="color: #16a34a; font-size: 13px;">LKR 0.00</strong>
            </div>
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 10px;">Outstanding Balance:</span>
                <strong id="kpiTotalOutstanding" style="color: #b45309; font-size: 13px;">LKR 0.00</strong>
            </div>
        </div>
        <div style="display: flex; gap: 14px; align-items: center;">
            <span style="font-size: 11.5px; color: #475569; font-weight: 700;">
                Active Advances: <strong id="kpiActiveCount" style="color: #0f172a; background: #e2e8f0; padding: 2px 8px; border-radius: 999px;">0</strong>
            </span>
        </div>
    </div>

    <!-- Salary Advance Ledger Table Panel -->
    <div id="paneRegister" class="prod-table-panel" style="flex: 1; min-height: 0; display: flex; flex-direction: column;">
        <div class="prod-table-scroll">
            <table class="prod-table" id="advancesTable">
                <thead>
                    <tr>
                        <th style="width: 120px; text-align: left;">Advance #</th>
                        <th style="width: 95px; text-align: left;">Date</th>
                        <th style="width: 220px; text-align: left;">Employee</th>
                        <th style="width: 150px; text-align: left;">Payment Source</th>
                        <th class="col-right" style="width: 110px; text-align: right;">Amount (LKR)</th>
                        <th class="col-right" style="width: 110px; text-align: right;">Recovered (LKR)</th>
                        <th class="col-right" style="width: 110px; text-align: right;">Remaining (LKR)</th>
                        <th style="width: 150px; text-align: left;">Scheduled Period</th>
                        <th class="col-center" style="width: 110px; text-align: center;">Status</th>
                        <th class="col-right" style="width: 90px; text-align: right;">Actions</th>
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

<!-- Modal: Advance Details View -->
<div class="curtiss-modal" id="modalAdvanceDetails">
    <div class="curtiss-modal-content" style="max-width: 620px;">
        <div class="curtiss-modal-header">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span id="detAdvNo" style="font-weight: 800; font-size: 1.15rem; color: #0f172a;">Advance Details</span>
                <span id="detStatusBadge"></span>
            </div>
            <button type="button" onclick="closeModal('modalAdvanceDetails')" style="background:none; border:none; font-size:1.5rem; color:#64748b; cursor:pointer;">&times;</button>
        </div>
        <div class="curtiss-modal-body" id="detModalBody" style="padding: 1.5rem;">
            <div style="text-align: center; color: #64748b; padding: 2rem;">Loading advance details...</div>
        </div>
        <div class="curtiss-modal-footer">
            <div id="detActionFooter" style="margin-right: auto;"></div>
            <button type="button" class="inv-btn inv-btn-ghost" onclick="closeModal('modalAdvanceDetails')">Close</button>
        </div>
    </div>
</div>

<script>
let currentAdvPage = 1;
let currentAdvLimit = 25;
let currentSearchTerm = '';
let currentMainTab = 'register';
const currentBranchId = <?= (int)$currentBranchId ?>;

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

let advSearchDebounceTimer = null;

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
    const recSelect = document.getElementById('advRecoveryPeriodSelect');
    if (recSelect && window.CurtissSearchableSelect) {
        CurtissSearchableSelect.attach(recSelect);
    }
    const statusSelect = document.getElementById('filterAdvStatus');
    if (statusSelect && window.CurtissSearchableSelect) {
        CurtissSearchableSelect.attach(statusSelect);
    }
    const branchSelect = document.getElementById('filterAdvBranch');
    if (branchSelect && window.CurtissSearchableSelect) {
        CurtissSearchableSelect.attach(branchSelect);
    }

    loadAdvances();

    // Attach CurtissNav page search if available with 300ms debounce
    if (window.CurtissNav && typeof CurtissNav.enablePageSearch === 'function') {
        CurtissNav.enablePageSearch((term) => {
            clearTimeout(advSearchDebounceTimer);
            advSearchDebounceTimer = setTimeout(() => {
                currentSearchTerm = term;
                currentAdvPage = 1;
                loadAdvances();
            }, 300);
        });
    }
});

async function loadAdvances() {
    const status = document.getElementById('filterAdvStatus').value;
    const limit = parseInt(document.getElementById('filterAdvLimit').value) || 25;
    const branchVal = document.getElementById('filterAdvBranch') ? document.getElementById('filterAdvBranch').value : currentBranchId;
    currentAdvLimit = limit;

    const tbody = document.getElementById('advancesTableBody');
    tbody.innerHTML = '<tr><td colspan="10" style="text-align: center; color: #94a3b8; padding: 32px;">Loading advances...</td></tr>';

    try {
        let url = `payroll_api.php?action=list_advances&status=${encodeURIComponent(status)}&search=${encodeURIComponent(currentSearchTerm)}&page=${currentAdvPage}&limit=${currentAdvLimit}`;
        if (branchVal) {
            url += `&branch_id=${encodeURIComponent(branchVal)}`;
        }

        const res = await fetch(url);
        const json = await res.json();

        const items = json.items || (json.data && json.data.items) || [];
        const total = (json.total !== undefined) ? json.total : ((json.data && json.data.total) !== undefined ? json.data.total : items.length);
        const totalPages = json.total_pages || (json.data && json.data.total_pages) || 1;
        const page = json.page || (json.data && json.data.page) || 1;

        // Recompute KPI Summary Ribbon
        let totalDisb = 0;
        let totalRec = 0;
        let totalRem = 0;
        let activeCount = 0;
        items.forEach(adv => {
            if (adv.status !== 'cancelled') {
                totalDisb += parseFloat(adv.amount) || 0;
                totalRec += parseFloat(adv.recovered_amount) || 0;
                totalRem += parseFloat(adv.remaining_amount) || 0;
                if (adv.status === 'active' && (parseFloat(adv.remaining_amount) || 0) > 0) {
                    activeCount++;
                }
            }
        });
        const dEl = document.getElementById('kpiTotalDisbursed');
        const rEl = document.getElementById('kpiTotalRecovered');
        const oEl = document.getElementById('kpiTotalOutstanding');
        const cEl = document.getElementById('kpiActiveCount');
        if (dEl) dEl.innerText = `LKR ${totalDisb.toLocaleString('en-US', {minimumFractionDigits: 2})}`;
        if (rEl) rEl.innerText = `LKR ${totalRec.toLocaleString('en-US', {minimumFractionDigits: 2})}`;
        if (oEl) oEl.innerText = `LKR ${totalRem.toLocaleString('en-US', {minimumFractionDigits: 2})}`;
        if (cEl) cEl.innerText = activeCount;

        if (items.length === 0) {
            tbody.innerHTML = '<tr><td colspan="10" style="text-align: center; color: #94a3b8; padding: 32px; font-weight: 600;">No salary advances found.</td></tr>';
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
            <tr onclick="viewAdvanceDetails(${adv.id})" style="cursor: pointer;">
                <td style="font-weight: 800; font-family: monospace; text-align: left;">${escapeHtml(adv.advance_no)}</td>
                <td style="color: #64748b; text-align: left;">${escapeHtml(adv.disbursement_date)}</td>
                <td style="text-align: left;">
                    <div style="font-weight: 800; color: #0f172a;">${escapeHtml(adv.employee_name)}</div>
                    <div style="font-size: 11px; color: #64748b;">${escapeHtml(adv.employee_code)}</div>
                </td>
                <td style="text-align: left;">
                    <div style="font-weight: 700; color: #334155;">${escapeHtml(adv.payment_method)}</div>
                    <div style="font-size: 10.5px; color: #64748b;">${escapeHtml(adv.source_account_name || '')}</div>
                </td>
                <td class="col-right" style="font-weight: 800;">${amt.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td class="col-right" style="color: #16a34a; font-weight: 700;">${rec.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td class="col-right" style="font-weight: 800; color: ${rem > 0 ? '#b45309' : '#64748b'};">${rem.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td style="font-size: 11.5px; color: #475569; text-align: left;">${escapeHtml(adv.recovery_period_name || 'Next Regular')}</td>
                <td class="col-center">
                    <span class="prod-badge ${badgeClass}"><span class="dot"></span>${badgeLabel}</span>
                </td>
                <td class="col-right" onclick="event.stopPropagation();">${cancelBtn}</td>
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
        formData.append('csrf_token', getCsrfToken());

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
        formData.append('csrf_token', getCsrfToken());

        const res = await fetch('payroll_api.php?action=cancel_advance', {
            method: 'POST',
            body: formData
        });
        const json = await res.json();

        if (json.success) {
            alert(json.message || "Salary advance cancelled and reversed.");
            loadAdvances();
            if (typeof loadAnalytics === 'function') {
                loadAnalytics();
            }
        } else {
            alert(json.message || "Failed to cancel salary advance.");
        }
    } catch (err) {
        alert("Server error cancelling salary advance.");
    }
}

async function viewAdvanceDetails(advId) {
    const modal = document.getElementById('modalAdvanceDetails');
    const body = document.getElementById('detModalBody');
    const title = document.getElementById('detAdvNo');
    const badge = document.getElementById('detStatusBadge');
    const footer = document.getElementById('detActionFooter');

    title.innerText = 'Advance Details';
    badge.innerHTML = '';
    footer.innerHTML = '';
    body.innerHTML = `
        <div style="text-align: center; color: #64748b; padding: 2.5rem;">
            <div style="display: inline-block; width: 24px; height: 24px; border: 3px solid #e2e8f0; border-top-color: #0f172a; border-radius: 50%; animation: spin 0.8s linear infinite;"></div>
            <div style="margin-top: 10px; font-size: 13px; font-weight: 600;">Loading advance details...</div>
        </div>
    `;
    modal.classList.add('active');

    try {
        const res = await fetch(`payroll_api.php?action=get_advance_details&id=${advId}`);
        const json = await res.json();

        if (!json.success || !json.advance) {
            body.innerHTML = `<div style="text-align: center; color: #ef4444; padding: 2rem; font-weight: 700;">${json.message || 'Advance record not found.'}</div>`;
            return;
        }

        const adv = json.advance;
        title.innerText = `Advance #${adv.advance_number}`;

        let badgeClass = 'prod-badge-active';
        let badgeLabel = 'Active';
        if (adv.status === 'Fully Recovered') {
            badgeClass = 'prod-badge-confirmed';
            badgeLabel = 'Fully Recovered';
        } else if (adv.status === 'Cancelled') {
            badgeClass = 'prod-badge-danger';
            badgeLabel = 'Cancelled';
        } else if (adv.status === 'Partially Recovered') {
            badgeClass = 'prod-badge-warning';
            badgeLabel = 'Partially Recovered';
        }
        badge.innerHTML = `<span class="prod-badge ${badgeClass}"><span class="dot"></span>${badgeLabel}</span>`;

        const amount = parseFloat(adv.amount || 0);
        const rec = parseFloat(adv.recovered_amount || 0);
        const rem = parseFloat(adv.remaining_amount || 0);
        const pct = amount > 0 ? Math.min(100, Math.round((rec / amount) * 100)) : 0;

        let bankInfo = '';
        if (adv.bank_name) {
            bankInfo = `<div style="font-size: 11px; color: #64748b; margin-top: 2px;">Bank: ${escapeHtml(adv.bank_name)} (${escapeHtml(adv.account_number || '')})</div>`;
        }

        let refInfo = '';
        if (adv.reference_no) {
            refInfo = `<div style="font-size: 11px; color: #64748b; margin-top: 2px;">Ref / Cheque: ${escapeHtml(adv.reference_no)}</div>`;
        }

        let recoveryPeriod = adv.recovery_period_name 
            ? `${escapeHtml(adv.recovery_period_name)} (${escapeHtml(adv.recovery_period_code || '')})`
            : 'Next Regular Payroll Period';

        body.innerHTML = `
            <!-- Staff Header -->
            <div style="display: flex; justify-content: space-between; align-items: flex-start; padding: 14px 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 1.25rem;">
                <div>
                    <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b;">Staff Member</div>
                    <div style="font-size: 15px; font-weight: 800; color: #0f172a; margin-top: 2px;">${escapeHtml(adv.employee_name)}</div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                        <span>Code: <strong>${escapeHtml(adv.employee_code)}</strong></span>
                        ${adv.job_role ? ` • <span>${escapeHtml(adv.job_role)}</span>` : ''}
                        ${adv.employee_phone ? ` • <span>${escapeHtml(adv.employee_phone)}</span>` : ''}
                    </div>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b;">Branch</div>
                    <div style="font-size: 13.5px; font-weight: 700; color: #0f172a; margin-top: 2px;">${escapeHtml(adv.branch_name || 'Main')}</div>
                    ${adv.basic_salary ? `<div style="font-size: 11px; color: #64748b; margin-top: 2px;">Basic: LKR ${parseFloat(adv.basic_salary).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>` : ''}
                </div>
            </div>

            <!-- Financial Breakdown & Progress -->
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 1.25rem;">
                <div style="padding: 12px 14px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px;">
                    <span style="font-size: 10.5px; font-weight: 800; text-transform: uppercase; color: #64748b;">Disbursed Amount</span>
                    <div style="font-size: 16px; font-weight: 900; color: #0f172a; margin-top: 4px;">LKR ${amount.toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                </div>
                <div style="padding: 12px 14px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px;">
                    <span style="font-size: 10.5px; font-weight: 800; text-transform: uppercase; color: #16a34a;">Recovered Amount</span>
                    <div style="font-size: 16px; font-weight: 900; color: #16a34a; margin-top: 4px;">LKR ${rec.toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                </div>
                <div style="padding: 12px 14px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px;">
                    <span style="font-size: 10.5px; font-weight: 800; text-transform: uppercase; color: ${rem > 0 ? '#b45309' : '#64748b'};">Remaining Balance</span>
                    <div style="font-size: 16px; font-weight: 900; color: ${rem > 0 ? '#b45309' : '#64748b'}; margin-top: 4px;">LKR ${rem.toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                </div>
            </div>

            <!-- Recovery Progress Bar -->
            <div style="margin-bottom: 1.25rem;">
                <div style="display: flex; justify-content: space-between; font-size: 11.5px; font-weight: 700; color: #475569; margin-bottom: 6px;">
                    <span>Recovery Progress</span>
                    <span>${pct}% (${rec.toLocaleString('en-US', {minimumFractionDigits: 2})} / ${amount.toLocaleString('en-US', {minimumFractionDigits: 2})})</span>
                </div>
                <div style="width: 100%; height: 8px; background: #e2e8f0; border-radius: 999px; overflow: hidden;">
                    <div style="width: ${pct}%; height: 100%; background: ${pct >= 100 ? '#16a34a' : '#0284c7'}; transition: width 0.3s ease;"></div>
                </div>
            </div>

            <!-- Disbursement & Recovery Details -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; font-size: 12px; margin-bottom: 1.25rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px;">
                <div>
                    <span style="font-size: 10.5px; font-weight: 800; text-transform: uppercase; color: #64748b;">Disbursement Method</span>
                    <div style="font-weight: 800; color: #0f172a; margin-top: 2px;">${escapeHtml(adv.payment_method)}</div>
                    <div style="font-size: 11.5px; color: #475569; margin-top: 2px;">Source Account: <strong>${escapeHtml(adv.source_account_name || 'N/A')}</strong></div>
                    ${bankInfo}
                    ${refInfo}
                </div>
                <div>
                    <span style="font-size: 10.5px; font-weight: 800; text-transform: uppercase; color: #64748b;">Schedule & Timing</span>
                    <div style="font-weight: 700; color: #0f172a; margin-top: 2px;">Disbursed: <strong>${escapeHtml(adv.disbursement_date)}</strong></div>
                    <div style="font-size: 11.5px; color: #475569; margin-top: 2px;">Recovery Target: <strong>${recoveryPeriod}</strong></div>
                    <div style="font-size: 11px; color: #64748b; margin-top: 2px;">Disbursed By: ${escapeHtml(adv.created_by_name || 'System')}</div>
                </div>
            </div>

            ${adv.notes ? `
                <div style="padding: 10px 14px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 12px; margin-bottom: 1rem;">
                    <span style="font-weight: 800; color: #64748b; font-size: 10.5px; text-transform: uppercase;">Remarks / Reason:</span>
                    <div style="color: #334155; margin-top: 2px;">${escapeHtml(adv.notes)}</div>
                </div>
            ` : ''}

            ${adv.status === 'Cancelled' ? `
                <div style="padding: 10px 14px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; font-size: 12px; color: #991b1b;">
                    <span style="font-weight: 800; font-size: 10.5px; text-transform: uppercase;">Cancelled Record:</span>
                    <div style="margin-top: 2px;">Reason: <strong>${escapeHtml(adv.cancel_reason || 'N/A')}</strong></div>
                    <div style="font-size: 11px; margin-top: 2px;">Cancelled by: ${escapeHtml(adv.cancelled_by_name || 'N/A')} on ${escapeHtml(adv.cancelled_at || '')}</div>
                </div>
            ` : ''}
        `;

        if (adv.status === 'Active' || adv.status === 'Partially Recovered') {
            footer.innerHTML = `
                <button type="button" class="inv-btn inv-btn-danger" style="color: #dc2626; background: #fef2f2; border-color: #fca5a5;" onclick="closeModal('modalAdvanceDetails'); promptCancelAdvance(${adv.id}, '${escapeHtml(adv.advance_number)}', '${escapeHtml(adv.employee_name)}');">
                    Cancel & Reverse Advance
                </button>
            `;
        }
    } catch (err) {
        body.innerHTML = `<div style="text-align: center; color: #ef4444; padding: 2rem; font-weight: 700;">Error loading advance details.</div>`;
    }
}

// Full keyboard Enter key navigation for form
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('disburseAdvanceForm');
    if (form) {
        const inputs = Array.from(form.querySelectorAll('input:not([type="hidden"]), select, textarea'));
        inputs.forEach((input, idx) => {
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
                    e.preventDefault();
                    if (idx < inputs.length - 1) {
                        inputs[idx + 1].focus();
                        if (inputs[idx + 1].select) inputs[idx + 1].select();
                    } else {
                        form.requestSubmit();
                    }
                }
            });
        });
    }
});

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
</script>
