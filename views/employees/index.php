<?php
/**
 * Curtiss POS - Employee Directory View
 * Features: Fixed Viewport, Zebra Strips, 48px Rows, 34px Filter Selects,
 * Clickable Status Pill, Right Slide-Over Details Panel, and Trash Bin Restore.
 */

$isDeletedView = ($viewMode === 'deleted');
$deletedCount = (int)($deletedCount ?? 0);
$canManage = Auth::hasPermission('employees.manage') || Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER);
$canViewSalary = Auth::hasPermission('payroll.view') || Auth::hasPermission('employees.manage') || Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_ACCOUNTANT);
?>

<meta name="csrf-token" content="<?= htmlspecialchars(class_exists('Csrf') ? Csrf::token() : (class_exists('Auth') ? Auth::generateCsrfToken() : '')) ?>">
<link rel="stylesheet" href="assets/css/curtiss_select.css">
<script src="assets/js/curtiss_select.js"></script>

<!-- Floating Top-Right Action Controls -->
<div id="curtiss-floating-header-right" class="no-print" style="position: fixed; top: 16px; right: 20px; z-index: 1050; display: flex; align-items: center; gap: 8px;">
    <?php if ($isDeletedView): ?>
        <a href="employees" class="inv-btn inv-btn-ghost">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            Active Directory
        </a>
    <?php else: ?>
        <a href="employees?view=deleted" class="inv-btn" style="color: #dc2626; background: #fef2f2; border-color: #fca5a5;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
            Trash Bin (<?= $deletedCount ?>)
        </a>
        <?php if ($canManage): ?>
        <button type="button" class="inv-btn inv-btn-primary" onclick="openCreateEmployeeModal()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
            + Add New Employee
        </button>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
/* ── Curtiss Theme Shared Standards ── */
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

/* ── Fixed Table Panel & Sticky Header ── */
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

/* ── Theme Badges ── */
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
    box-sizing: border-box;
    white-space: nowrap;
}
.prod-badge .dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
}
.prod-badge-active {
    background: #dcfce7;
    color: #15803d;
}
.prod-badge-active .dot { background: #16a34a; }
.prod-badge-inactive {
    background: #fee2e2;
    color: #b91c1c;
}
.prod-badge-inactive .dot { background: #dc2626; }

.prod-badge-role {
    background: #f1f5f9;
    color: #334155;
    font-size: 10.5px;
    padding: 0 10px;
    height: 26px;
    line-height: 24px;
}
.prod-badge-rep {
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #bfdbfe;
    font-size: 10.5px;
    padding: 0 10px;
    height: 26px;
    line-height: 24px;
}

/* ── In-Row Action Buttons ── */
.prod-act-btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    height: 28px;
    min-height: 28px;
    line-height: 26px;
    padding: 0 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    color: #0f172a;
    cursor: pointer;
    transition: all 0.15s ease;
}
.prod-act-btn:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
}
.prod-act-delete {
    color: #dc2626;
    background: #fef2f2;
    border-color: #fca5a5;
}
.prod-act-delete:hover {
    background: #fee2e2;
}

/* ── Right Slide-Over Details Panel ── */
.slide-drawer-backdrop {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(15, 23, 42, 0.4);
    backdrop-filter: blur(4px);
    z-index: 1100;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.25s ease;
}
.slide-drawer-backdrop.active {
    opacity: 1;
    pointer-events: auto;
}
.slide-drawer {
    position: fixed;
    top: 0; right: -580px; bottom: 0;
    width: 580px;
    max-width: 90vw;
    background: #ffffff;
    box-shadow: -8px 0 32px rgba(15, 23, 42, 0.15);
    z-index: 1101;
    display: flex;
    flex-direction: column;
    transition: right 0.28s cubic-bezier(0.16, 1, 0.3, 1);
}
.slide-drawer.active {
    right: 0;
}
.drawer-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.drawer-body {
    padding: 1.5rem;
    overflow-y: auto;
    flex: 1;
}
.drawer-tabs {
    display: flex;
    gap: 8px;
    border-bottom: 1px solid #e2e8f0;
    padding: 0 1.5rem;
    background: #f8fafc;
}
.drawer-tab-btn {
    padding: 10px 14px;
    font-size: 12px;
    font-weight: 700;
    color: #64748b;
    background: none;
    border: none;
    border-bottom: 2px solid transparent;
    cursor: pointer;
    transition: all 0.15s ease;
}
.drawer-tab-btn.active {
    color: #0f172a;
    border-bottom-color: #0f172a;
}

/* ── Modal Dialogs ── */
.curtiss-modal {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1200;
    opacity: 0;
    pointer-events: none;
    transition: all 0.2s ease;
}
.curtiss-modal.active {
    opacity: 1;
    pointer-events: auto;
}
.curtiss-modal-content {
    background: #ffffff;
    border-radius: 20px;
    width: 620px;
    max-width: 92vw;
    max-height: 88vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 48px -10px rgba(15, 23, 42, 0.25);
    overflow: hidden;
}
.curtiss-modal-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 800;
    font-size: 1.15rem;
    color: #0f172a;
}
.curtiss-modal-body {
    padding: 1.5rem;
    overflow-y: auto;
    flex: 1;
}
.curtiss-modal-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    background: #f8fafc;
}
.form-group {
    margin-bottom: 1rem;
}
.form-label {
    display: block;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    color: #475569;
    letter-spacing: 0.05em;
    margin-bottom: 6px;
}
.form-control {
    width: 100%;
    height: 38px;
    padding: 0 12px;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    font-size: 13px;
    color: #0f172a;
    box-sizing: border-box;
    transition: border-color 0.15s ease;
}
.form-control:focus {
    outline: none;
    border-color: #0f172a;
}
</style>

<!-- Main Fixed Viewport Workarea -->
<div style="width: 100%; max-width: 100%; height: calc(100vh - 110px); display: flex; flex-direction: column; box-sizing: border-box; overflow: hidden; padding-bottom: 8px;">
    
    <!-- Page Header (Headline Only - No Subtitle) -->
    <div class="inv-header" style="flex-shrink: 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 0.85rem;">
        <div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
                <h1 class="inv-title"><?= $isDeletedView ? 'Trash Bin — Deleted Employees' : 'Employee Directory' ?></h1>
            </div>
        </div>

        <!-- Inline Filter Dropdowns Above Table Right Corner (Height: 34px) -->
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <?php if (Auth::hasRole(ROLE_SUPER_ADMIN)): ?>
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Branch:</span>
                <select id="filterEmpBranch" onchange="applyEmpFilters()" style="height: 34px; min-height: 34px; padding: 0 14px; border-radius: 999px; font-size: 12px; font-weight: 700;">
                    <option value="">All Branches</option>
                    <?php foreach ($branches as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= $b['id'] == $currentBranchId ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Role:</span>
                <select id="filterEmpRole" onchange="applyEmpFilters()" style="height: 34px; min-height: 34px; padding: 0 14px; border-radius: 999px; font-size: 12px; font-weight: 700;">
                    <option value="">All Roles</option>
                    <option value="Sales Rep">Sales Rep</option>
                    <option value="Cashier">Cashier</option>
                    <option value="Store Keeper">Store Keeper</option>
                    <option value="Accountant">Accountant</option>
                    <option value="Branch Manager">Branch Manager</option>
                    <option value="Driver / Delivery">Driver / Delivery</option>
                </select>
            </div>

            <?php if (!$isDeletedView): ?>
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Status:</span>
                <select id="filterEmpStatus" onchange="applyEmpFilters()" style="height: 34px; min-height: 34px; padding: 0 14px; border-radius: 999px; font-size: 12px; font-weight: 700;">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Table Panel & Scroll Container -->
    <div class="prod-table-panel" style="flex: 1; min-height: 0; display: flex; flex-direction: column;">
        <div class="prod-table-scroll">
            <table class="prod-table" id="employeesTable">
                <thead>
                    <tr>
                        <th style="width: 10%;">Emp ID</th>
                        <th style="width: 22%;">Full Name</th>
                        <th style="width: 14%;">Job Role</th>
                        <th style="width: 14%;">Assigned Branch</th>
                        <th style="width: 13%;">Phone / WhatsApp</th>
                        <?php if ($canViewSalary): ?>
                        <th class="col-right" style="width: 12%;">Basic Salary (LKR)</th>
                        <?php endif; ?>
                        <th class="col-center" style="width: 10%;">Status</th>
                        <th class="col-right" style="width: 11%;">Actions</th>
                    </tr>
                </thead>
                <tbody id="employeesTableBody">
                    <tr>
                        <td colspan="<?= $canViewSalary ? 8 : 7 ?>" style="text-align: center; color: #94a3b8; padding: 2rem;">Loading employees...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination Controls Bar -->
        <div style="padding: 8px 16px; border-top: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: #ffffff; flex-shrink: 0;">
            <div style="font-size: 12px; color: #64748b; font-weight: 600;">
                Showing <span id="pagStart" style="font-weight: 800; color: #0f172a;">0</span> to <span id="pagEnd" style="font-weight: 800; color: #0f172a;">0</span> of <span id="pagTotal" style="font-weight: 800; color: #0f172a;">0</span> records
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <select id="empLimit" onchange="changeLimit(this.value)" style="height: 30px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 11.5px; font-weight: 700;">
                    <option value="25" selected>25 per page</option>
                    <option value="50">50 per page</option>
                    <option value="75">75 per page</option>
                    <option value="100">100 per page</option>
                    <option value="500">500 per page</option>
                </select>
                <button type="button" class="prod-act-btn" id="btnPrevPage" onclick="prevPage()" disabled>&larr; Prev</button>
                <span id="pagCurrentPage" style="font-size: 12px; font-weight: 800; color: #0f172a; padding: 0 4px;">1 / 1</span>
                <button type="button" class="prod-act-btn" id="btnNextPage" onclick="nextPage()" disabled>Next &rarr;</button>
            </div>
        </div>
    </div>
</div>

<!-- Slide-Over Employee Details Drawer -->
<div class="slide-drawer-backdrop" id="empDrawerBackdrop" onclick="closeEmployeeDrawer()"></div>
<div class="slide-drawer" id="empDrawer">
    <div class="drawer-header">
        <div>
            <div style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;" id="drawerEmpCode">EMP-0000</div>
            <div style="font-size: 1.25rem; font-weight: 800; color: #0f172a;" id="drawerEmpName">Employee Details</div>
        </div>
        <button type="button" onclick="closeEmployeeDrawer()" style="background: none; border: none; font-size: 1.5rem; color: #64748b; cursor: pointer;">&times;</button>
    </div>

    <!-- Drawer Navigation Tabs -->
    <div class="drawer-tabs">
        <button type="button" class="drawer-tab-btn active" onclick="switchDrawerTab('overview', this)">Overview</button>
        <?php if ($canViewSalary): ?>
        <button type="button" class="drawer-tab-btn" onclick="switchDrawerTab('salary', this)">Salary & Statutory</button>
        <button type="button" class="drawer-tab-btn" onclick="switchDrawerTab('advances', this)">Salary Advances</button>
        <button type="button" class="drawer-tab-btn" onclick="switchDrawerTab('ledger', this)">Payroll Ledger</button>
        <?php endif; ?>
        <button type="button" class="drawer-tab-btn" onclick="switchDrawerTab('sales', this)">Sales Rep Stats</button>
    </div>

    <div class="drawer-body" id="drawerBody">
        <!-- Tab 1: Overview -->
        <div id="tab-overview" class="drawer-tab-pane">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 1.5rem;">
                <div>
                    <div class="form-label">Job Role</div>
                    <div id="d-job-role" style="font-weight: 700; color: #0f172a;">-</div>
                </div>
                <div>
                    <div class="form-label">Sales Rep Designation</div>
                    <div id="d-is-rep" style="font-weight: 700; color: #0f172a;">-</div>
                </div>
                <div>
                    <div class="form-label">Assigned Branch</div>
                    <div id="d-branch" style="font-weight: 700; color: #0f172a;">-</div>
                </div>
                <div>
                    <div class="form-label">Joining Date</div>
                    <div id="d-joining-date" style="font-weight: 700; color: #0f172a;">-</div>
                </div>
                <div>
                    <div class="form-label">Phone</div>
                    <div id="d-phone" style="font-weight: 700; color: #0f172a;">-</div>
                </div>
                <div>
                    <div class="form-label">WhatsApp</div>
                    <div id="d-whatsapp" style="font-weight: 700; color: #0f172a;">-</div>
                </div>
                <div>
                    <div class="form-label">NIC Number</div>
                    <div id="d-nic" style="font-weight: 700; color: #0f172a;">-</div>
                </div>
                <div>
                    <div class="form-label">Current Status</div>
                    <div id="d-status" style="font-weight: 700;">-</div>
                </div>
            </div>
            <div style="margin-bottom: 1.5rem;">
                <div class="form-label">Residential Address</div>
                <div id="d-address" style="color: #334155; line-height: 1.5;">-</div>
            </div>
            <div>
                <div class="form-label">Internal Notes</div>
                <div id="d-notes" style="color: #64748b; font-style: italic;">None</div>
            </div>
        </div>

        <?php if ($canViewSalary): ?>
        <!-- Tab 2: Salary & Statutory -->
        <div id="tab-salary" class="drawer-tab-pane" style="display: none;">
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.25rem; margin-bottom: 1.25rem;">
                <div style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Basic Monthly Salary</div>
                <div id="d-basic-salary" style="font-size: 1.6rem; font-weight: 900; color: #0f172a; margin: 4px 0;">LKR 0.00</div>
                <div style="font-size: 11px; color: #64748b;">Subject to Sri Lankan statutory EPF/ETF contributions</div>
            </div>

            <div style="border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.25rem; margin-bottom: 1.25rem;">
                <div style="font-weight: 800; font-size: 13px; color: #0f172a; margin-bottom: 8px;">Statutory Contribution Estimates (Monthly)</div>
                <table style="width: 100%; font-size: 12px; border-collapse: collapse;">
                    <tr style="border-bottom: 1px solid #f1f5f9; height: 32px;">
                        <td>Employee EPF (8%):</td>
                        <td class="col-right" style="font-weight: 700; color: #b91c1c;" id="d-emp-epf">LKR 0.00</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #f1f5f9; height: 32px;">
                        <td>Employer EPF (12%):</td>
                        <td class="col-right" style="font-weight: 700; color: #0f172a;" id="d-empr-epf">LKR 0.00</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #f1f5f9; height: 32px;">
                        <td>Employer ETF (3%):</td>
                        <td class="col-right" style="font-weight: 700; color: #0f172a;" id="d-empr-etf">LKR 0.00</td>
                    </tr>
                    <tr style="height: 36px; font-weight: 800;">
                        <td>Total EPF/ETF Remittance:</td>
                        <td class="col-right" style="color: #15803d;" id="d-total-statutory">LKR 0.00</td>
                    </tr>
                </table>
            </div>

            <div style="border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.25rem;">
                <div style="font-weight: 800; font-size: 13px; color: #0f172a; margin-bottom: 8px;">Disbursement Bank Account</div>
                <div style="font-size: 12px; line-height: 1.6;">
                    <div>Bank: <strong id="d-bank-name">-</strong></div>
                    <div>Account Name: <strong id="d-bank-acc-name">-</strong></div>
                    <div>Account No: <strong id="d-bank-acc-no">-</strong></div>
                </div>
            </div>
        </div>

        <!-- Tab 3: Payroll Ledger -->
        <div id="tab-ledger" class="drawer-tab-pane" style="display: none;">
            <div id="d-ledger-list">Loading payroll ledger...</div>
        </div>

        <!-- Tab: Salary Advances -->
        <div id="tab-advances" class="drawer-tab-pane" style="display: none;">
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.25rem; margin-bottom: 1.25rem; display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; text-align: center;">
                <div>
                    <div style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">Total Advanced</div>
                    <div id="d-adv-total" style="font-size: 1.15rem; font-weight: 900; color: #0f172a;">LKR 0.00</div>
                </div>
                <div>
                    <div style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">Total Recovered</div>
                    <div id="d-adv-recovered" style="font-size: 1.15rem; font-weight: 900; color: #16a34a;">LKR 0.00</div>
                </div>
                <div>
                    <div style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">Remaining Due</div>
                    <div id="d-adv-remaining" style="font-size: 1.15rem; font-weight: 900; color: #b45309;">LKR 0.00</div>
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem;">
                <span style="font-size: 12px; font-weight: 800; color: #0f172a; text-transform: uppercase;">Advance History</span>
                <button type="button" class="inv-btn inv-btn-primary" onclick="openDisburseAdvanceModal(activeEmpRecord ? activeEmpRecord.id : null)" style="height: 30px; min-height: 30px; font-size: 11px;">
                    + Disburse Advance
                </button>
            </div>
            <div id="d-advances-list">Loading advances...</div>
        </div>
        <?php endif; ?>

        <!-- Tab 4: Sales Performance -->
        <div id="tab-sales" class="drawer-tab-pane" style="display: none;">
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.25rem; margin-bottom: 1.25rem; display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; text-align: center;">
                <div>
                    <div style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">Total Sales</div>
                    <div id="d-sales-total" style="font-size: 1.25rem; font-weight: 900; color: #0f172a;">LKR 0.00</div>
                </div>
                <div>
                    <div style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">Invoices</div>
                    <div id="d-sales-count" style="font-size: 1.25rem; font-weight: 900; color: #0f172a;">0</div>
                </div>
                <div>
                    <div style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">Avg Bill</div>
                    <div id="d-sales-avg" style="font-size: 1.25rem; font-weight: 900; color: #0f172a;">LKR 0.00</div>
                </div>
            </div>
            <div id="d-recent-sales">Loading sales history...</div>
        </div>
    </div>
</div>

<!-- Modal: Disburse Employee Salary Advance (From Employee Directory) -->
<div class="curtiss-modal" id="modalEmpAdvanceForm">
    <div class="curtiss-modal-content" style="width: 600px; max-width: 95vw;">
        <div class="curtiss-modal-header">
            <span>Disburse Salary Advance</span>
            <button type="button" onclick="closeModal('modalEmpAdvanceForm')" style="background:none; border:none; font-size:1.5rem; color:#64748b; cursor:pointer;">&times;</button>
        </div>
        <form id="empAdvanceForm" onsubmit="handleEmployeeAdvanceSubmit(event)">
            <input type="hidden" name="employee_id" id="empAdvEmployeeId" value="">
            <div class="curtiss-modal-body">
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 16px; margin-bottom: 1rem;">
                    <div style="font-weight: 800; font-size: 1rem; color: #0f172a;" id="empAdvTargetName">-</div>
                    <div style="display: flex; justify-content: space-between; font-size: 12px; margin-top: 4px;">
                        <span>Basic Salary: <strong id="empAdvTargetBasic">LKR 0.00</strong></span>
                        <span>Current Due: <strong id="empAdvTargetDue" style="color: #b45309;">LKR 0.00</strong></span>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label class="form-label">Advance Amount (LKR) *</label>
                        <input type="number" step="0.01" min="0.01" class="form-control" name="amount" id="empAdvAmount" required placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Payment Source *</label>
                        <select class="form-control" id="empAdvAccountSelect" onchange="onEmpAdvAccountSelected(this)" required>
                            <option value="">-- Select Source Account --</option>
                            <?php if (!empty($paymentAccounts)): foreach ($paymentAccounts as $pa): ?>
                                <option value="<?= htmlspecialchars(json_encode($pa)) ?>" <?= ($pa['account_code'] === '1012') ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($pa['formatted_label']) ?>
                                </option>
                            <?php endforeach; endif; ?>
                        </select>
                        <input type="hidden" name="payment_method" id="empAdvMethod" value="Cash Drawer">
                        <input type="hidden" name="source_account_id" id="empAdvSourceAccId" value="49">
                        <input type="hidden" name="bank_account_id" id="empAdvBankAccId" value="">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label class="form-label">Disbursement Date *</label>
                        <input type="date" class="form-control" name="disbursement_date" required value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Reference / Cheque No</label>
                        <input type="text" class="form-control" name="reference_no" placeholder="Optional reference...">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Reason / Remarks</label>
                    <input type="text" class="form-control" name="notes" placeholder="e.g. Festival advance, emergency...">
                </div>
            </div>
            <div class="curtiss-modal-footer">
                <button type="button" class="inv-btn inv-btn-ghost" onclick="closeModal('modalEmpAdvanceForm')">Cancel</button>
                <button type="submit" class="inv-btn inv-btn-primary" id="btnSubmitEmpAdvance">Disburse Advance</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Create / Edit Employee -->
<div class="curtiss-modal" id="modalEmployeeForm">
    <div class="curtiss-modal-content">
        <div class="curtiss-modal-header">
            <span id="modalEmpTitle">Add New Employee</span>
            <button type="button" onclick="closeEmployeeModal()" style="background:none; border:none; font-size:1.5rem; color:#64748b; cursor:pointer;">&times;</button>
        </div>
        <form id="empForm" onsubmit="handleEmployeeSubmit(event)">
            <input type="hidden" id="formEmpId" name="id" value="">
            <div class="curtiss-modal-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label class="form-label">Employee Code *</label>
                        <input type="text" class="form-control" id="formEmpCode" name="employee_code" placeholder="Auto-generated if blank" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Full Name *</label>
                        <input type="text" class="form-control" id="formFullName" name="full_name" required placeholder="e.g. Kasun Perera" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone Number *</label>
                        <input type="text" class="form-control" id="formPhone" name="phone" placeholder="07X XXXXXXX" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label class="form-label">WhatsApp Number</label>
                        <input type="text" class="form-control" id="formWhatsapp" name="whatsapp" placeholder="07X XXXXXXX" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label class="form-label">NIC Number</label>
                        <input type="text" class="form-control" id="formNic" name="nic" placeholder="XXXXXXXXXV or 12 digits" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Job Role *</label>
                        <select class="form-control" id="formJobRole" name="job_role" required>
                            <option value="Sales Rep">Sales Rep</option>
                            <option value="Cashier">Cashier</option>
                            <option value="Store Keeper">Store Keeper</option>
                            <option value="Accountant">Accountant</option>
                            <option value="Branch Manager">Branch Manager</option>
                            <option value="Driver / Delivery">Driver / Delivery</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Assigned Branch *</label>
                        <select class="form-control" id="formBranchId" name="branch_id" required>
                            <?php foreach ($branches as $b): ?>
                                <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Joining Date</label>
                        <input type="date" class="form-control" id="formJoiningDate" name="joining_date" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; font-weight: 700; color: #0f172a; margin-top: 4px;">
                        <input type="checkbox" id="formIsSalesRep" name="is_sales_rep" value="1" checked style="width: 16px; height: 16px;">
                        Attribute POS Sales (Show in POS Sales Rep Selector)
                    </label>
                </div>

                <?php if ($canViewSalary): ?>
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; margin: 12px 0;">
                    <div class="form-group" style="margin-bottom: 8px;">
                        <label class="form-label">Basic Monthly Salary (LKR) *</label>
                        <input type="number" step="0.01" class="form-control" id="formBasicSalary" name="basic_salary" placeholder="0.00" value="0.00">
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div>
                            <label class="form-label">Bank Name</label>
                            <input type="text" class="form-control" id="formBankName" name="bank_name" placeholder="e.g. Commercial Bank">
                        </div>
                        <div>
                            <label class="form-label">Account No</label>
                            <input type="text" class="form-control" id="formBankAccNo" name="bank_account_no" placeholder="Bank Account Number">
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="form-group">
                    <label class="form-label">Address</label>
                    <textarea class="form-control" id="formAddress" name="address" style="height: 60px; padding: 8px 12px;" placeholder="Residential Address..."></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Notes</label>
                    <input type="text" class="form-control" id="formNotes" name="notes" placeholder="Optional internal notes...">
                </div>
            </div>
            <div class="curtiss-modal-footer">
                <button type="button" class="inv-btn inv-btn-ghost" onclick="closeEmployeeModal()">Cancel</button>
                <button type="submit" class="inv-btn inv-btn-primary" id="btnSaveEmployee">Save Employee</button>
            </div>
        </form>
    </div>
</div>

<script>
let currentEmpPage = 1;
let currentEmpLimit = 25;
let currentEmpTotalPages = 1;
let isDeletedMode = <?= $isDeletedView ? 'true' : 'false' ?>;
let activeEmpRecord = null;
let currentSearchTerm = '';

document.addEventListener('DOMContentLoaded', () => {
    // Enhance filter selects with CurtissSearchableSelect
    if (typeof CurtissSearchableSelect !== 'undefined') {
        const branchSel = document.getElementById('filterEmpBranch');
        if (branchSel) CurtissSearchableSelect.attach(branchSel);

        const roleSel = document.getElementById('filterEmpRole');
        if (roleSel) CurtissSearchableSelect.attach(roleSel);

        const statusSel = document.getElementById('filterEmpStatus');
        if (statusSel) CurtissSearchableSelect.attach(statusSel);
    }

    function getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }
    window.getCsrfToken = getCsrfToken;

    // Connect CurtissNav bottom floating search
    let searchDebounceTimer = null;
    function registerEmpSearch() {
        if (typeof CurtissNav !== 'undefined' && CurtissNav.enablePageSearch) {
            CurtissNav.enablePageSearch({
                placeholder: 'Search employees by code, name, phone, WA, NIC, role, branch, salary...',
                onSearch: (term) => {
                    clearTimeout(searchDebounceTimer);
                    searchDebounceTimer = setTimeout(() => {
                        currentSearchTerm = term;
                        currentEmpPage = 1;
                        loadEmployees();
                    }, 300);
                }
            });
        } else {
            setTimeout(registerEmpSearch, 50);
        }
    }
    registerEmpSearch();

    // Setup Enter key auto-advancement in modal form
    const form = document.getElementById('empForm');
    if (form) {
        const inputs = Array.from(form.querySelectorAll('input:not([type="hidden"]):not([type="checkbox"]), select, textarea'));
        inputs.forEach((input, idx) => {
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
                    e.preventDefault();
                    if (idx < inputs.length - 1) {
                        inputs[idx + 1].focus();
                    } else {
                        form.requestSubmit();
                    }
                }
            });
        });
    }

    loadEmployees();
});

function applyEmpFilters() {
    currentEmpPage = 1;
    loadEmployees();
}

function changeLimit(lim) {
    currentEmpLimit = parseInt(lim);
    currentEmpPage = 1;
    loadEmployees();
}

function prevPage() {
    if (currentEmpPage > 1) {
        currentEmpPage--;
        loadEmployees();
    }
}

function nextPage() {
    if (currentEmpPage < currentEmpTotalPages) {
        currentEmpPage++;
        loadEmployees();
    }
}

async function loadEmployees() {
    const tbody = document.getElementById('employeesTableBody');
    const branchVal = document.getElementById('filterEmpBranch') ? document.getElementById('filterEmpBranch').value : '';
    const roleVal = document.getElementById('filterEmpRole') ? document.getElementById('filterEmpRole').value : '';
    const statusVal = document.getElementById('filterEmpStatus') ? document.getElementById('filterEmpStatus').value : '';

    let url = `employee_api.php?action=list&page=${currentEmpPage}&limit=${currentEmpLimit}`;
    if (isDeletedMode) url += `&view=deleted`;
    if (branchVal) url += `&branch_id=${encodeURIComponent(branchVal)}`;
    if (roleVal) url += `&job_role=${encodeURIComponent(roleVal)}`;
    if (statusVal) url += `&status=${encodeURIComponent(statusVal)}`;
    if (currentSearchTerm) url += `&search=${encodeURIComponent(currentSearchTerm)}`;

    try {
        const res = await fetch(url);
        const json = await res.json();

        if (!json.success || !json.data) {
            tbody.innerHTML = `<tr><td colspan="8" style="text-align:center; color:#ef4444; padding:2rem;">Failed to load employees.</td></tr>`;
            return;
        }

        const data = json.data;
        currentEmpTotalPages = data.total_pages || 1;

        // Update pagination UI
        const start = data.total === 0 ? 0 : (data.page - 1) * data.limit + 1;
        const end = Math.min(data.page * data.limit, data.total);
        document.getElementById('pagStart').innerText = start;
        document.getElementById('pagEnd').innerText = end;
        document.getElementById('pagTotal').innerText = data.total;
        document.getElementById('pagCurrentPage').innerText = `${data.page} / ${currentEmpTotalPages}`;
        document.getElementById('btnPrevPage').disabled = (data.page <= 1);
        document.getElementById('btnNextPage').disabled = (data.page >= currentEmpTotalPages);

        if (!data.items || data.items.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8" style="text-align:center; color:#94a3b8; padding:3rem; font-weight:600;">No employees found matching criteria.</td></tr>`;
            return;
        }

        const canSalary = <?= $canViewSalary ? 'true' : 'false' ?>;
        const canManage = <?= $canManage ? 'true' : 'false' ?>;

        let html = '';
        data.items.forEach(emp => {
            const isActive = emp.status === 'active';
            const statusBadge = isActive 
                ? `<span class="prod-badge prod-badge-active" onclick="event.stopPropagation(); toggleEmployeeStatus(${emp.id});" style="cursor: pointer;" title="Click to toggle status"><span class="dot"></span>Active</span>`
                : `<span class="prod-badge prod-badge-inactive" onclick="event.stopPropagation(); toggleEmployeeStatus(${emp.id});" style="cursor: pointer;" title="Click to toggle status"><span class="dot"></span>Inactive</span>`;

            const repBadge = emp.is_sales_rep == 1 
                ? `<span class="prod-badge prod-badge-rep"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Rep</span>`
                : '';

            const salaryCell = canSalary 
                ? `<td class="col-right" style="font-weight: 700;">${emp.basic_salary !== null ? parseFloat(emp.basic_salary).toLocaleString('en-US', {minimumFractionDigits: 2}) : '***'}</td>`
                : '';

            let actButtons = '';
            if (isDeletedMode) {
                actButtons = `
                    <button type="button" class="prod-act-btn" onclick="event.stopPropagation(); restoreEmployee(${emp.id});" title="Restore Employee">
                        Restore
                    </button>
                `;
            } else if (canManage) {
                actButtons = `
                    <button type="button" class="prod-act-btn" onclick="event.stopPropagation(); editEmployee(${emp.id});" title="Edit Employee">
                        Edit
                    </button>
                    <button type="button" class="prod-act-btn prod-act-delete" onclick="event.stopPropagation(); deleteEmployee(${emp.id});" title="Move to Trash">
                        Delete
                    </button>
                `;
            }

            html += `
                <tr onclick="openEmployeeDrawer(${emp.id})" style="cursor: pointer;">
                    <td style="font-weight: 800; color: #0f172a;">${escapeHtml(emp.employee_code)}</td>
                    <td>
                        <div style="font-weight: 800; color: #0f172a;">${escapeHtml(emp.full_name)}</div>
                        ${emp.nic ? `<div style="font-size: 11px; color: #64748b;">NIC: ${escapeHtml(emp.nic)}</div>` : ''}
                    </td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <span class="prod-badge prod-badge-role">${escapeHtml(emp.job_role)}</span>
                            ${repBadge}
                        </div>
                    </td>
                    <td>${escapeHtml(emp.branch_name)}</td>
                    <td>
                        <div>${escapeHtml(emp.phone || '-')}</div>
                        ${emp.whatsapp ? `<div style="font-size: 11px; color: #16a34a;">WA: ${escapeHtml(emp.whatsapp)}</div>` : ''}
                    </td>
                    ${salaryCell}
                    <td class="col-center">${statusBadge}</td>
                    <td class="col-right" onclick="event.stopPropagation();">
                        <div style="display: inline-flex; gap: 4px;">
                            ${actButtons}
                        </div>
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
    } catch (err) {
        console.error("Employee listing error:", err);
        tbody.innerHTML = `<tr><td colspan="8" style="text-align:center; color:#ef4444; padding:2rem;">Network error fetching employee data.</td></tr>`;
    }
}

async function toggleEmployeeStatus(id) {
    try {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('csrf_token', getCsrfToken());
        const res = await fetch('employee_api.php?action=toggle_status', {
            method: 'POST',
            body: formData
        });
        const json = await res.json();
        if (json.success) {
            loadEmployees();
        } else {
            alert(json.message || "Failed to toggle status.");
        }
    } catch (err) {
        alert("Server error toggling status.");
    }
}

async function deleteEmployee(id) {
    if (!confirm("Move this employee to the Trash Bin? Their history and records will be preserved safely.")) {
        return;
    }
    try {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('csrf_token', getCsrfToken());
        const res = await fetch('employee_api.php?action=delete', {
            method: 'POST',
            body: formData
        });
        const json = await res.json();
        if (json.success) {
            loadEmployees();
        } else {
            alert(json.message || "Failed to delete employee.");
        }
    } catch (err) {
        alert("Server error deleting employee.");
    }
}

async function restoreEmployee(id) {
    try {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('csrf_token', getCsrfToken());
        const res = await fetch('employee_api.php?action=restore', {
            method: 'POST',
            body: formData
        });
        const json = await res.json();
        if (json.success) {
            loadEmployees();
        } else {
            alert(json.message || "Failed to restore employee.");
        }
    } catch (err) {
        alert("Server error restoring employee.");
    }
}

function openCreateEmployeeModal() {
    document.getElementById('modalEmpTitle').innerText = 'Add New Employee';
    document.getElementById('formEmpId').value = '';
    document.getElementById('empForm').reset();
    document.getElementById('formJoiningDate').value = new Date().toISOString().substring(0, 10);
    document.getElementById('modalEmployeeForm').classList.add('active');
    setTimeout(() => document.getElementById('formFullName').focus(), 80);
}

async function editEmployee(id) {
    try {
        const res = await fetch(`employee_api.php?action=get&id=${id}`);
        const json = await res.json();
        if (!json.success || !json.employee) {
            alert("Could not load employee details.");
            return;
        }

        const e = json.employee;
        document.getElementById('modalEmpTitle').innerText = `Edit Employee (${e.employee_code})`;
        document.getElementById('formEmpId').value = e.id;
        document.getElementById('formEmpCode').value = e.employee_code;
        document.getElementById('formFullName').value = e.full_name;
        document.getElementById('formPhone').value = e.phone || '';
        document.getElementById('formWhatsapp').value = e.whatsapp || '';
        document.getElementById('formNic').value = e.nic || '';
        document.getElementById('formJobRole').value = e.job_role;
        document.getElementById('formBranchId').value = e.branch_id;
        document.getElementById('formJoiningDate').value = e.joining_date || '';
        document.getElementById('formIsSalesRep').checked = (e.is_sales_rep == 1);
        if (document.getElementById('formBasicSalary')) {
            document.getElementById('formBasicSalary').value = e.basic_salary || '0.00';
            document.getElementById('formBankName').value = e.bank_name || '';
            document.getElementById('formBankAccNo').value = e.bank_account_no || '';
        }
        document.getElementById('formAddress').value = e.address || '';
        document.getElementById('formNotes').value = e.notes || '';

        document.getElementById('modalEmployeeForm').classList.add('active');
        setTimeout(() => document.getElementById('formFullName').focus(), 80);
    } catch (err) {
        alert("Error loading employee for editing.");
    }
}

function closeEmployeeModal() {
    document.getElementById('modalEmployeeForm').classList.remove('active');
}

async function handleEmployeeSubmit(event) {
    event.preventDefault();
    const btn = document.getElementById('btnSaveEmployee');
    btn.disabled = true;

    try {
        const form = document.getElementById('empForm');
        const formData = new FormData(form);
        formData.append('csrf_token', getCsrfToken());
        const empId = document.getElementById('formEmpId').value;
        const action = empId ? 'update' : 'create';

        const res = await fetch(`employee_api.php?action=${action}`, {
            method: 'POST',
            body: formData
        });
        const json = await res.json();

        if (json.success) {
            closeEmployeeModal();
            loadEmployees();
        } else {
            alert(json.message || "Failed to save employee.");
        }
    } catch (err) {
        alert("Server error saving employee.");
    } finally {
        btn.disabled = false;
    }
}

/* ── Slide-Over Details Panel Logic ── */
async function openEmployeeDrawer(id) {
    try {
        const res = await fetch(`employee_api.php?action=get&id=${id}`);
        const json = await res.json();
        if (!json.success || !json.employee) return;

        const e = json.employee;
        activeEmpRecord = e;

        document.getElementById('drawerEmpCode').innerText = e.employee_code;
        document.getElementById('drawerEmpName').innerText = e.full_name;

        document.getElementById('d-job-role').innerText = e.job_role;
        document.getElementById('d-is-rep').innerText = e.is_sales_rep == 1 ? 'Yes (Active Sales Rep)' : 'No';
        document.getElementById('d-branch').innerText = e.branch_name;
        document.getElementById('d-joining-date').innerText = e.joining_date || 'N/A';
        document.getElementById('d-phone').innerText = e.phone || 'N/A';
        document.getElementById('d-whatsapp').innerText = e.whatsapp || 'N/A';
        document.getElementById('d-nic').innerText = e.nic || 'N/A';
        document.getElementById('d-status').innerHTML = e.status === 'active' 
            ? `<span style="color: #16a34a; font-weight:800;">Active</span>` 
            : `<span style="color: #dc2626; font-weight:800;">Inactive</span>`;
        document.getElementById('d-address').innerText = e.address || 'No address provided.';
        document.getElementById('d-notes').innerText = e.notes || 'None';

        // Salary Tab
        const basic = parseFloat(e.basic_salary || 0);
        if (document.getElementById('d-basic-salary')) {
            document.getElementById('d-basic-salary').innerText = `LKR ${basic.toLocaleString('en-US', {minimumFractionDigits: 2})}`;
            const empEpf = basic * 0.08;
            const emprEpf = basic * 0.12;
            const emprEtf = basic * 0.03;
            document.getElementById('d-emp-epf').innerText = `LKR ${empEpf.toFixed(2)}`;
            document.getElementById('d-empr-epf').innerText = `LKR ${emprEpf.toFixed(2)}`;
            document.getElementById('d-empr-etf').innerText = `LKR ${emprEtf.toFixed(2)}`;
            document.getElementById('d-total-statutory').innerText = `LKR ${(empEpf + emprEpf + emprEtf).toFixed(2)}`;

            document.getElementById('d-bank-name').innerText = e.bank_name || 'N/A';
            document.getElementById('d-bank-acc-name').innerText = e.bank_account_name || 'N/A';
            document.getElementById('d-bank-acc-no').innerText = e.bank_account_no || 'N/A';
        }

        // Reset to first tab
        switchDrawerTab('overview', document.querySelector('.drawer-tab-btn'));

        document.getElementById('empDrawerBackdrop').classList.add('active');
        document.getElementById('empDrawer').classList.add('active');
    } catch (err) {
        console.error("Error opening employee drawer:", err);
    }
}

function closeEmployeeDrawer() {
    document.getElementById('empDrawerBackdrop').classList.remove('active');
    document.getElementById('empDrawer').classList.remove('active');
}

function switchDrawerTab(tabName, btnElem) {
    document.querySelectorAll('.drawer-tab-btn').forEach(b => b.classList.remove('active'));
    if (btnElem) btnElem.classList.add('active');

    document.querySelectorAll('.drawer-tab-pane').forEach(p => p.style.display = 'none');
    const pane = document.getElementById(`tab-${tabName}`);
    if (pane) pane.style.display = 'block';

    if (tabName === 'sales' && activeEmpRecord) {
        loadDrawerSalesPerformance(activeEmpRecord.id);
    } else if (tabName === 'ledger' && activeEmpRecord) {
        loadDrawerPayrollLedger(activeEmpRecord.id);
    } else if (tabName === 'advances' && activeEmpRecord) {
        loadDrawerAdvances(activeEmpRecord.id);
    }
}

async function loadDrawerAdvances(empId) {
    const listElem = document.getElementById('d-advances-list');
    listElem.innerHTML = 'Loading salary advances...';

    try {
        const res = await fetch(`payroll_api.php?action=get_employee_advance_summary&employee_id=${empId}`);
        const json = await res.json();
        if (json.success) {
            document.getElementById('d-adv-total').innerText = `LKR ${parseFloat(json.total_advanced).toLocaleString('en-US', {minimumFractionDigits: 2})}`;
            document.getElementById('d-adv-recovered').innerText = `LKR ${parseFloat(json.total_recovered).toLocaleString('en-US', {minimumFractionDigits: 2})}`;
            document.getElementById('d-adv-remaining').innerText = `LKR ${parseFloat(json.remaining_balance).toLocaleString('en-US', {minimumFractionDigits: 2})}`;

            if (!json.advances || json.advances.length === 0) {
                listElem.innerHTML = '<div style="color: #94a3b8; font-style: italic; padding: 12px 0;">No salary advances recorded for this employee.</div>';
                return;
            }

            let html = `
                <table class="prod-table" style="font-size: 11.5px;">
                    <thead>
                        <tr>
                            <th>Adv #</th>
                            <th>Date</th>
                            <th>Method</th>
                            <th class="col-right">Amount</th>
                            <th class="col-right">Recovered</th>
                            <th class="col-right">Remaining</th>
                            <th class="col-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            json.advances.forEach(a => {
                let badge = (a.status === 'active')
                    ? '<span class="prod-badge prod-badge-active"><span class="dot"></span>Active</span>'
                    : (a.status === 'fully_recovered'
                        ? '<span class="prod-badge" style="background:#e0f2fe; color:#0369a1;"><span class="dot" style="background:#0284c7;"></span>Recovered</span>'
                        : '<span class="prod-badge prod-badge-deactive"><span class="dot"></span>Cancelled</span>');

                html += `
                    <tr>
                        <td style="font-weight: 800;">${escapeHtml(a.advance_no)}</td>
                        <td>${escapeHtml(a.disbursement_date)}</td>
                        <td>${escapeHtml(a.payment_method)}</td>
                        <td class="col-right" style="font-weight: 700;">${parseFloat(a.amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                        <td class="col-right" style="color: #16a34a;">${parseFloat(a.recovered_amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                        <td class="col-right" style="font-weight: 800; color: ${parseFloat(a.remaining_amount) > 0 ? '#b45309' : '#64748b'};">${parseFloat(a.remaining_amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                        <td class="col-center">${badge}</td>
                    </tr>
                `;
            });

            html += '</tbody></table>';
            listElem.innerHTML = html;
        } else {
            listElem.innerHTML = `<div style="color:#ef4444;">${json.message || 'Failed to load advances.'}</div>`;
        }
    } catch (e) {
        listElem.innerHTML = '<div style="color:#ef4444;">Server error loading advances.</div>';
    }
}

function openDisburseAdvanceModal(empId) {
    if (!empId && activeEmpRecord) empId = activeEmpRecord.id;
    if (!empId) return;

    document.getElementById('empAdvEmployeeId').value = empId;
    document.getElementById('empAdvTargetName').innerText = `${activeEmpRecord.full_name} (${activeEmpRecord.employee_code})`;
    document.getElementById('empAdvTargetBasic').innerText = `LKR ${parseFloat(activeEmpRecord.basic_salary || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}`;
    document.getElementById('empAdvTargetDue').innerText = document.getElementById('d-adv-remaining') ? document.getElementById('d-adv-remaining').innerText : 'LKR 0.00';
    document.getElementById('empAdvAmount').value = '';

    document.getElementById('modalEmpAdvanceForm').classList.add('active');
}

function onEmpAdvAccountSelected(select) {
    try {
        if (!select.value) return;
        const pa = JSON.parse(select.value);
        document.getElementById('empAdvMethod').value = pa.payment_method;
        document.getElementById('empAdvSourceAccId').value = pa.account_id || '';
        document.getElementById('empAdvBankAccId').value = pa.bank_account_id || '';
    } catch (e) {}
}

async function handleEmployeeAdvanceSubmit(event) {
    event.preventDefault();
    const btn = document.getElementById('btnSubmitEmpAdvance');
    btn.disabled = true;

    try {
        const form = document.getElementById('empAdvanceForm');
        const formData = new FormData(form);

        const res = await fetch('payroll_api.php?action=disburse_advance', {
            method: 'POST',
            body: formData
        });
        const json = await res.json();
        alert(json.message);
        if (json.success) {
            closeModal('modalEmpAdvanceForm');
            form.reset();
            if (activeEmpRecord) {
                loadDrawerAdvances(activeEmpRecord.id);
            }
        }
    } catch (e) {
        alert("Server error disbursing advance.");
    } finally {
        btn.disabled = false;
    }
}

async function loadDrawerSalesPerformance(empId) {
    const listElem = document.getElementById('d-recent-sales');
    listElem.innerHTML = 'Loading sales statistics...';

    try {
        const res = await fetch(`employee_api.php?action=sales_performance&id=${empId}`);
        const json = await res.json();
        if (json.success && json.performance) {
            const p = json.performance;
            const totalSales = parseFloat(p.total_sales ?? p.total_sales_volume ?? 0) || 0;
            const invoices = parseInt(p.total_invoices ?? 0, 10) || 0;
            const avgBill = parseFloat(p.average_invoice_value ?? (invoices > 0 ? totalSales / invoices : 0)) || 0;

            document.getElementById('d-sales-total').innerText = `LKR ${totalSales.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
            document.getElementById('d-sales-count').innerText = invoices;
            document.getElementById('d-sales-avg').innerText = `LKR ${avgBill.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;

            if (!p.recent_sales || p.recent_sales.length === 0) {
                listElem.innerHTML = '<div style="text-align:center; padding:1.5rem; color:#94a3b8;">No recorded sales for this employee yet.</div>';
                return;
            }

            let html = '<div style="font-weight:800; font-size:12px; margin-bottom:8px; color:#0f172a;">Recent Completed Sales</div>';
            html += '<table style="width:100%; font-size:12px; border-collapse:collapse;">';
            html += '<thead><tr style="border-bottom:1px solid #e2e8f0; height:28px; color:#64748b; font-size:10px; text-transform:uppercase;"><th style="text-align:left;">Invoice</th><th style="text-align:left;">Date</th><th style="text-align:right;">Amount (LKR)</th></tr></thead><tbody>';
            p.recent_sales.forEach(s => {
                html += `
                    <tr style="border-bottom:1px solid #f1f5f9; height:34px;">
                        <td style="font-weight:700;">#${escapeHtml(s.invoice_no)}</td>
                        <td style="font-size:11px; color:#64748b;">${s.created_at}</td>
                        <td style="text-align:right; font-weight:800;">${parseFloat(s.total_amount).toFixed(2)}</td>
                    </tr>
                `;
            });
            html += '</tbody></table>';
            listElem.innerHTML = html;
        }
    } catch (err) {
        listElem.innerHTML = '<div style="color:#ef4444;">Failed to load sales stats.</div>';
    }
}

async function loadDrawerPayrollLedger(empId) {
    const listElem = document.getElementById('d-ledger-list');
    listElem.innerHTML = 'Loading payroll history...';

    try {
        const res = await fetch(`employee_api.php?action=payroll_ledger&id=${empId}`);
        const json = await res.json();
        if (json.success && json.ledger) {
            if (json.ledger.length === 0) {
                listElem.innerHTML = '<div style="text-align:center; padding:1.5rem; color:#94a3b8;">No payroll records for this employee yet.</div>';
                return;
            }

            let html = '<table style="width:100%; font-size:12px; border-collapse:collapse;">';
            html += '<thead><tr style="border-bottom:1px solid #e2e8f0; height:28px; color:#64748b; font-size:10px; text-transform:uppercase;"><th style="text-align:left;">Period</th><th style="text-align:right;">Gross</th><th style="text-align:right;">Deductions</th><th style="text-align:right;">Net Salary</th><th style="text-align:center;">Status</th></tr></thead><tbody>';
            json.ledger.forEach(l => {
                html += `
                    <tr style="border-bottom:1px solid #f1f5f9; height:36px;">
                        <td style="font-weight:800;">${escapeHtml(l.period_name)}</td>
                        <td style="text-align:right;">${parseFloat(l.gross_salary).toFixed(2)}</td>
                        <td style="text-align:right; color:#dc2626;">-${parseFloat(l.total_deductions).toFixed(2)}</td>
                        <td style="text-align:right; font-weight:800; color:#15803d;">${parseFloat(l.net_salary).toFixed(2)}</td>
                        <td style="text-align:center;"><span class="prod-badge prod-badge-role" style="font-size:10px;">${l.status}</span></td>
                    </tr>
                `;
            });
            html += '</tbody></table>';
            listElem.innerHTML = html;
        }
    } catch (err) {
        listElem.innerHTML = '<div style="color:#ef4444;">Failed to load payroll ledger.</div>';
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
</script>
