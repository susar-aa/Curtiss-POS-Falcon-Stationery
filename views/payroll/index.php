<?php
/**
 * Curtiss POS - Payroll Management View
 * Features: Fixed Viewport, Zebra Strips, 48px Rows, 34px Filter Selects,
 * Period Workflow (Draft -> Approved -> Paid), Double-Entry Accounting Posting,
 * Salary Advances, EPF/ETF Statutory Remittances, and Printable Payslips.
 */

$canCreate = Auth::hasPermission('payroll.create') || Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_ACCOUNTANT);
$canApprove = Auth::hasPermission('payroll.approve') || Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_ACCOUNTANT);
$canPay = Auth::hasPermission('payroll.pay') || Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_ACCOUNTANT);
$canRemit = Auth::hasPermission('payroll.remit') || Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_ACCOUNTANT);
?>

<link rel="stylesheet" href="assets/css/curtiss_select.css">
<script src="assets/js/curtiss_select.js"></script>

<!-- Floating Top-Right Action Controls -->
<div id="curtiss-floating-header-right" class="no-print" style="position: fixed; top: 16px; right: 20px; z-index: 1050; display: flex; align-items: center; gap: 8px;">
    <a href="salary_advances.php" class="inv-btn inv-btn-ghost">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        Salary Advances
    </a>

    <?php if ($canCreate): ?>
    <button type="button" class="inv-btn inv-btn-primary" onclick="openCreatePeriodModal()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
        + New Payroll Period
    </button>
    <?php endif; ?>
</div>

<style>
/* ── Theme Shared Standards ── */
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

/* ── Fixed Table Panel ── */
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

/* ── Status Pills ── */
.status-pill {
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
}
.status-pill .dot { width: 6px; height: 6px; border-radius: 50%; }

.status-draft { background: #fef3c7; color: #b45309; }
.status-draft .dot { background: #f59e0b; }

.status-approved { background: #dbeafe; color: #1e40af; }
.status-approved .dot { background: #2563eb; }

.status-paid, .status-completed { background: #dcfce7; color: #15803d; }
.status-paid .dot, .status-completed .dot { background: #16a34a; }

.status-partially_paid { background: #fef08a; color: #854d0e; }
.status-partially_paid .dot { background: #ca8a04; }

/* ── Action Buttons ── */
.prod-act-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
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
    white-space: nowrap;
    box-sizing: border-box;
    text-decoration: none;
}
.prod-act-btn:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
}

/* ── Drawer & Modals ── */
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
.slide-drawer-backdrop.active { opacity: 1; pointer-events: auto; }

.slide-drawer {
    position: fixed;
    top: 0; right: -750px; bottom: 0;
    width: 750px;
    max-width: 95vw;
    background: #ffffff;
    box-shadow: -8px 0 32px rgba(15, 23, 42, 0.15);
    z-index: 1101;
    display: flex;
    flex-direction: column;
    transition: right 0.28s cubic-bezier(0.16, 1, 0.3, 1);
}
.slide-drawer.active { right: 0; }

/* ── Fullscreen Payroll Sheet & Commission Inputs ── */
.fullscreen-payroll-sheet {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: #ffffff;
    z-index: 99999;
    flex-direction: column;
    overflow: hidden;
}
.fullscreen-payroll-sheet.active {
    display: flex;
}
.fullscreen-payroll-table tbody tr {
    height: 48px;
    min-height: 48px;
}
.fullscreen-payroll-table tbody tr:nth-child(even) {
    background: rgba(248, 250, 252, 0.65);
}
.fullscreen-payroll-table tbody tr:nth-child(odd) {
    background: #ffffff;
}
.fullscreen-payroll-table tbody tr:hover {
    background: #f1f5f9 !important;
}
.fullscreen-payroll-table td {
    padding: 0 12px;
    vertical-align: middle;
    font-size: 12.5px;
    box-sizing: border-box;
}
.comm-cell-input {
    width: 95px;
    height: 28px;
    border: 1.5px solid #86efac;
    background: #f0fdf4;
    border-radius: 6px;
    text-align: right;
    font-size: 12px;
    font-weight: 700;
    color: #15803d;
    padding: 0 8px;
    transition: all 0.15s ease;
    box-sizing: border-box;
}
.comm-cell-input:focus {
    border-color: #16a34a;
    background: #ffffff;
    box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.25);
    outline: none;
}
.other-cell-input {
    width: 95px;
    height: 28px;
    border: 1.5px solid #fca5a5;
    background: #fef2f2;
    border-radius: 6px;
    text-align: right;
    font-size: 12px;
    font-weight: 700;
    color: #dc2626;
    padding: 0 8px;
    transition: all 0.15s ease;
    box-sizing: border-box;
}
.other-cell-input:focus {
    border-color: #dc2626;
    background: #ffffff;
    box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.25);
    outline: none;
}

.curtiss-modal {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(15, 23, 42, 0.5);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2000;
    padding: 16px;
    box-sizing: border-box;
    opacity: 0;
    pointer-events: none;
    transition: all 0.2s ease;
}
.curtiss-modal.active { opacity: 1; pointer-events: auto; }

.adv-tab-pill {
    background: transparent;
    border: none;
    padding: 4px 12px;
    border-radius: 999px;
    font-size: 11.5px;
    font-weight: 700;
    color: #64748b;
    cursor: pointer;
    transition: all 0.15s ease;
}
.adv-tab-pill.active {
    background: #ffffff;
    color: #0f172a;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}
.curtiss-modal-content {
    background: #ffffff;
    border-radius: 18px;
    width: 680px;
    max-width: 95vw;
    max-height: calc(100vh - 40px);
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 48px -10px rgba(15, 23, 42, 0.3);
    overflow: hidden;
    position: relative;
    box-sizing: border-box;
}
.curtiss-modal-content form {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-height: 0;
    overflow: hidden;
    margin: 0;
}
.curtiss-modal-header {
    padding: 1rem 1.4rem;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 800;
    font-size: 1.15rem;
    color: #0f172a;
    flex-shrink: 0;
}
.curtiss-modal-body {
    padding: 1.25rem 1.4rem;
    overflow-y: auto;
    flex: 1;
    min-height: 0;
    box-sizing: border-box;
}
.curtiss-modal-footer {
    padding: 0.85rem 1.4rem;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    background: #f8fafc;
    flex-shrink: 0;
    box-sizing: border-box;
}
@media (max-height: 820px) {
    .curtiss-modal { padding: 8px; }
    .curtiss-modal-content { max-height: calc(100vh - 20px); border-radius: 14px; }
    .curtiss-modal-header { padding: 0.75rem 1.25rem; font-size: 1rem; }
    .curtiss-modal-body { padding: 0.85rem 1.25rem; }
    .curtiss-modal-footer { padding: 0.65rem 1.25rem; }
    .form-group { margin-bottom: 0.65rem; }
}
.form-group { margin-bottom: 1rem; }
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
}
.form-control:focus { outline: none; border-color: #0f172a; }
</style>

<!-- Main Fixed Viewport Workarea -->
<div style="width: 100%; max-width: 100%; height: calc(100vh - 110px); display: flex; flex-direction: column; box-sizing: border-box; overflow: hidden; padding-bottom: 8px;">

    <!-- Page Header (Headline Only - No Subtitle) -->
    <div class="inv-header" style="flex-shrink: 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 0.85rem;">
        <div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
                <h1 class="inv-title">Payroll Management</h1>
            </div>
        </div>

        <!-- Inline Filter Selects (Height: 34px) -->
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <?php if (Auth::hasRole(ROLE_SUPER_ADMIN)): ?>
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Branch:</span>
                <select id="filterPayrollBranch" onchange="applyPayrollFilters()" style="height: 34px; min-height: 34px; padding: 0 14px; border-radius: 999px; font-size: 12px; font-weight: 700;">
                    <option value="">All Branches</option>
                    <?php foreach ($branches as $b): ?>
                        <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Status:</span>
                <select id="filterPayrollStatus" onchange="applyPayrollFilters()" style="height: 34px; min-height: 34px; padding: 0 14px; border-radius: 999px; font-size: 12px; font-weight: 700;">
                    <option value="">All Status</option>
                    <option value="draft">Draft</option>
                    <option value="approved">Approved</option>
                    <option value="completed">Completed / Paid</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Table Panel & Scroll Container -->
    <div class="prod-table-panel" style="flex: 1; min-height: 0; display: flex; flex-direction: column;">
        <div class="prod-table-scroll">
            <table class="prod-table" id="payrollTable" style="table-layout: auto; width: 100%;">
                <thead>
                    <tr>
                        <th style="min-width: 95px; width: 100px; text-align: left;">Period Code</th>
                        <th style="min-width: 120px; text-align: left;">Period Name</th>
                        <th style="min-width: 145px; text-align: left;">Period Dates</th>
                        <th class="col-center" style="min-width: 85px; width: 90px; text-align: center;">Employees</th>
                        <th class="col-right" style="min-width: 105px; text-align: right;">Gross (LKR)</th>
                        <th class="col-right" style="min-width: 115px; text-align: right;">Net Salary (LKR)</th>
                        <th class="col-right" style="min-width: 105px; text-align: right;">Paid (LKR)</th>
                        <th class="col-center" style="min-width: 100px; width: 105px; text-align: center;">Status</th>
                        <th class="col-right" style="min-width: 320px; width: 330px; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody id="payrollTableBody">
                    <tr>
                        <td colspan="9" style="text-align: center; color: #94a3b8; padding: 2rem;">Loading payroll periods...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination Controls -->
        <div style="padding: 8px 16px; border-top: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: #ffffff; flex-shrink: 0;">
            <div style="font-size: 12px; color: #64748b; font-weight: 600;">
                Showing <span id="pagStart" style="font-weight: 800; color: #0f172a;">0</span> to <span id="pagEnd" style="font-weight: 800; color: #0f172a;">0</span> of <span id="pagTotal" style="font-weight: 800; color: #0f172a;">0</span> periods
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <select id="payrollLimit" onchange="changePayrollLimit(this.value)" style="height: 30px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 11.5px; font-weight: 700;">
                    <option value="25" selected>25 per page</option>
                    <option value="50">50 per page</option>
                    <option value="75">75 per page</option>
                    <option value="100">100 per page</option>
                    <option value="500">500 per page</option>
                </select>
                <button type="button" class="prod-act-btn" id="btnPrevPage" onclick="prevPayrollPage()" disabled>&larr; Prev</button>
                <span id="pagCurrentPage" style="font-size: 12px; font-weight: 800; color: #0f172a; padding: 0 4px;">1 / 1</span>
                <button type="button" class="prod-act-btn" id="btnNextPage" onclick="nextPayrollPage()" disabled>Next &rarr;</button>
            </div>
        </div>
    </div>
</div>

<!-- Fullscreen Payroll Calculation Sheet -->
<div class="fullscreen-payroll-sheet" id="fullscreenPayrollSheet">
    <!-- Top Bar: Fixed Header matching image -->
    <div style="flex-shrink: 0; padding: 12px 24px; background: #ffffff; border-bottom: 1.5px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; gap: 16px;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <button type="button" class="inv-btn inv-btn-ghost" onclick="closeFullscreenSheet()" title="Back to Periods" style="height: 34px; padding: 0 12px; display: inline-flex; align-items: center; gap: 6px; font-weight: 700;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
                <span>Back</span>
            </button>
            <div style="display: flex; align-items: center; gap: 10px;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.5"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                <h2 style="font-size: 1.25rem; font-weight: 900; color: #0f172a; margin: 0; letter-spacing: -0.01em;">Employee Payslips Calculation</h2>
            </div>
            <div style="display: flex; align-items: center; gap: 8px; margin-left: 6px;">
                <span id="fullPeriodBadge" style="font-size: 12px; font-weight: 800; color: #1e293b; background: #f1f5f9; padding: 4px 12px; border-radius: 999px;">Period</span>
                <span id="fullPeriodStatusPill"></span>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="position: relative;">
                <input type="text" id="fullSheetSearch" placeholder="Filter employee..." oninput="filterFullscreenSheet(this.value)" style="height: 34px; width: 200px; padding: 0 12px 0 32px; border: 1px solid #cbd5e1; border-radius: 999px; font-size: 12px; outline: none;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2.5" style="position: absolute; left: 11px; top: 10px;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </div>
            <div id="fullSheetHeaderActions" style="display: flex; gap: 8px;"></div>
            <div style="font-size: 12px; font-weight: 800; color: #64748b; background: #f8fafc; border: 1px solid #e2e8f0; padding: 6px 14px; border-radius: 999px;" id="fullEmployeesCount">
                0 Employees Included
            </div>
            <button type="button" onclick="closeFullscreenSheet()" style="background: none; border: none; font-size: 1.6rem; color: #64748b; cursor: pointer; line-height: 1; padding: 0 4px;" title="Close Fullscreen (Esc)">&times;</button>
        </div>
    </div>

    <!-- Summary Metrics Header Ribbon -->
    <div style="flex-shrink: 0; background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 8px 24px; display: flex; justify-content: space-between; align-items: center; font-size: 12px;">
        <div style="display: flex; gap: 24px; color: #475569;">
            <span>Branch: <strong id="fullBranchText" style="color: #0f172a;">-</strong></span>
            <span>Date Range: <strong id="fullDateRangeText" style="color: #0f172a;">-</strong></span>
            <span id="fullDraftEditNotice" style="display: inline-flex; align-items: center; gap: 6px; color: #15803d; font-weight: 700;">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                Draft Mode: Edit Commission & Deductions directly. Press Enter or ↓ to move to next staff.
            </span>
        </div>
        <div style="display: flex; gap: 18px; font-weight: 800;">
            <span style="color: #64748b;">Covered Sales: <strong id="ribbonSales" style="color: #0284c7;">0.00</strong></span>
            <span style="color: #64748b;">Gross: <strong id="ribbonGross" style="color: #0f172a;">0.00</strong></span>
            <span style="color: #64748b;">Advances Ded: <strong id="ribbonAdv" style="color: #dc2626;">0.00</strong></span>
            <span style="color: #64748b;">Net Payable: <strong id="ribbonNet" style="color: #16a34a;">0.00</strong></span>
            <span style="color: #64748b;">Paid: <strong id="ribbonPaid" style="color: #0f172a;">0.00</strong></span>
        </div>
    </div>

    <!-- Table Container with Sticky Headers & Scroll -->
    <div style="flex: 1; min-height: 0; overflow-y: auto; background: #ffffff; padding: 0 16px;">
        <table class="prod-table fullscreen-payroll-table" id="fullscreenPayrollTable" style="width: 100%; border-collapse: separate; border-spacing: 0;">
            <thead>
                <tr style="height: 44px;">
                    <th style="position: sticky; top: 0; z-index: 10; background: #f8fafc; min-width: 190px; font-size: 10px; font-weight: 800; letter-spacing: 0.07em; color: #64748b; text-transform: uppercase; text-align: left; padding: 10px 12px;">EMPLOYEE</th>
                    <th class="col-right" style="position: sticky; top: 0; z-index: 10; background: #f8fafc; min-width: 120px; font-size: 10px; font-weight: 800; letter-spacing: 0.07em; color: #64748b; text-transform: uppercase; padding: 10px 12px;">BASE SALARY (LKR)</th>
                    <th class="col-right" style="position: sticky; top: 0; z-index: 10; background: #f8fafc; min-width: 135px; font-size: 10px; font-weight: 800; letter-spacing: 0.07em; color: #0284c7; text-transform: uppercase; padding: 10px 12px;">PERIOD SALES (LKR)</th>
                    <th class="col-right" style="position: sticky; top: 0; z-index: 10; background: #f8fafc; min-width: 125px; font-size: 10px; font-weight: 800; letter-spacing: 0.07em; color: #16a34a; text-transform: uppercase; padding: 10px 12px;">+ COMMS / OT (LKR)</th>
                    <th class="col-right" style="position: sticky; top: 0; z-index: 10; background: #f8fafc; min-width: 120px; font-size: 10px; font-weight: 800; letter-spacing: 0.07em; color: #64748b; text-transform: uppercase; padding: 10px 12px;">GROSS (LKR)</th>
                    <th class="col-right" style="position: sticky; top: 0; z-index: 10; background: #f8fafc; min-width: 105px; font-size: 10px; font-weight: 800; letter-spacing: 0.07em; color: #dc2626; text-transform: uppercase; padding: 10px 12px;">- EPF 8% (LKR)</th>
                    <th class="col-right" style="position: sticky; top: 0; z-index: 10; background: #f8fafc; min-width: 115px; font-size: 10px; font-weight: 800; letter-spacing: 0.07em; color: #dc2626; text-transform: uppercase; padding: 10px 12px;">- ADVANCES (LKR)</th>
                    <th class="col-right" style="position: sticky; top: 0; z-index: 10; background: #f8fafc; min-width: 120px; font-size: 10px; font-weight: 800; letter-spacing: 0.07em; color: #dc2626; text-transform: uppercase; padding: 10px 12px;">- OTHER DED. (LKR)</th>
                    <th class="col-right" style="position: sticky; top: 0; z-index: 10; background: #f8fafc; min-width: 130px; font-size: 10px; font-weight: 800; letter-spacing: 0.07em; color: #16a34a; text-transform: uppercase; padding: 10px 12px;">NET PAY (LKR)</th>
                    <th class="col-right" style="position: sticky; top: 0; z-index: 10; background: #f8fafc; min-width: 110px; font-size: 10px; font-weight: 800; letter-spacing: 0.07em; color: #d97706; text-transform: uppercase; padding: 10px 12px;">EPF 12% (LKR)</th>
                    <th class="col-right" style="position: sticky; top: 0; z-index: 10; background: #f8fafc; min-width: 105px; font-size: 10px; font-weight: 800; letter-spacing: 0.07em; color: #9333ea; text-transform: uppercase; padding: 10px 12px;">ETF 3% (LKR)</th>
                </tr>
            </thead>
            <tbody id="fullscreenPayrollBody">
                <!-- Dynamically generated rows -->
            </tbody>
            <tfoot>
                <tr style="position: sticky; bottom: 0; z-index: 10; background: #f1f5f9; border-top: 2px solid #cbd5e1; height: 48px; font-weight: 900; font-size: 12px;">
                    <td style="color: #0f172a; padding: 0 12px; text-align: left;">GRAND TOTALS</td>
                    <td class="col-right" id="footBase" style="color: #0f172a; padding: 0 12px;">0.00</td>
                    <td class="col-right" id="footSales" style="color: #0284c7; padding: 0 12px;">0.00</td>
                    <td class="col-right" id="footComms" style="color: #16a34a; padding: 0 12px;">0.00</td>
                    <td class="col-right" id="footGross" style="color: #0f172a; padding: 0 12px;">0.00</td>
                    <td class="col-right" id="footEpf8" style="color: #dc2626; padding: 0 12px;">0.00</td>
                    <td class="col-right" id="footAdv" style="color: #dc2626; padding: 0 12px;">0.00</td>
                    <td class="col-right" id="footOther" style="color: #dc2626; padding: 0 12px;">0.00</td>
                    <td class="col-right" id="footNet" style="color: #16a34a; font-size: 13.5px; padding: 0 12px;">0.00</td>
                    <td class="col-right" id="footEpf12" style="color: #d97706; padding: 0 12px;">0.00</td>
                    <td class="col-right" id="footEtf3" style="color: #9333ea; padding: 0 12px;">0.00</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Modal: Create Payroll Period -->
<div class="curtiss-modal" id="modalPeriodForm">
    <div class="curtiss-modal-content">
        <div class="curtiss-modal-header">
            <span>Generate Monthly Payroll Period</span>
            <button type="button" onclick="closeModal('modalPeriodForm')" style="background:none; border:none; font-size:1.5rem; color:#64748b; cursor:pointer;">&times;</button>
        </div>
        <form id="createPeriodForm" onsubmit="handleCreatePeriod(event)">
            <div class="curtiss-modal-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label class="form-label">Period Code *</label>
                        <input type="text" class="form-control" name="period_code" id="periodCodeInput" required placeholder="YYYY-MM e.g. <?= date('Y-m') ?>" value="<?= date('Y-m') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Period Name *</label>
                        <input type="text" class="form-control" name="period_name" id="periodNameInput" required placeholder="e.g. <?= date('F Y') ?>" value="<?= date('F Y') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Start Date *</label>
                        <input type="date" class="form-control" name="start_date" required value="<?= date('Y-m-01') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">End Date *</label>
                        <input type="date" class="form-control" name="end_date" required value="<?= date('Y-m-t') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Branch Scope</label>
                    <div style="font-weight: 800; font-size: 13px; color: #0f172a; background: #f8fafc; border: 1px solid #e2e8f0; padding: 9px 14px; border-radius: 10px; display: flex; align-items: center; gap: 8px;">
                        <span style="width: 8px; height: 8px; border-radius: 50%; background: #16a34a;"></span>
                        <span><?= htmlspecialchars($currentBranch['name'] ?? 'Main Branch') ?> (Current Opened Branch)</span>
                    </div>
                    <input type="hidden" name="branch_id" value="<?= (int)$currentBranchId ?>">
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Notes</label>
                    <input type="text" class="form-control" name="notes" placeholder="Optional notes for this payroll run...">
                </div>
            </div>
            <div class="curtiss-modal-footer">
                <button type="button" class="inv-btn inv-btn-ghost" onclick="closeModal('modalPeriodForm')">Cancel</button>
                <button type="submit" class="inv-btn inv-btn-primary" id="btnSubmitPeriod">Generate Period</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Record Salary Payment (Split Payment Capable) -->
<div class="curtiss-modal" id="modalPaymentForm">
    <div class="curtiss-modal-content" style="width: 760px; max-width: 95vw;">
        <div class="curtiss-modal-header">
            <span style="font-weight: 800; font-size: 15px; color: #0f172a;">Disburse Salary Payment (Split Payment)</span>
            <button type="button" onclick="closeModal('modalPaymentForm')" style="background:none; border:none; font-size:1.5rem; color:#64748b; cursor:pointer;">&times;</button>
        </div>
        <form id="recordPaymentForm" onsubmit="handleRecordPayment(event)">
            <input type="hidden" name="payroll_period_id" id="payPeriodId">
            <input type="hidden" name="payroll_entry_id" id="payEntryId">
            <input type="hidden" name="splits" id="paySplitsJson">
            <div class="curtiss-modal-body" style="padding: 20px 24px;">
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 18px; margin-bottom: 1.2rem;">
                    <div style="font-weight: 800; font-size: 1.05rem; color: #0f172a;" id="payEmployeeName">-</div>
                    <div style="display: flex; justify-content: space-between; font-size: 12.5px; margin-top: 6px;">
                        <span>Net Salary: <strong id="payNetSalary">LKR 0.00</strong></span>
                        <span>Already Paid: <strong id="payAlreadyPaid">LKR 0.00</strong></span>
                        <span style="color: #16a34a;">Outstanding: <strong id="payOutstanding">LKR 0.00</strong></span>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 1rem;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Payment Date *</label>
                        <input type="date" class="form-control" name="payment_date" id="payPaymentDate" required value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Total Amount to Pay (LKR) *</label>
                        <input type="number" step="0.01" min="0.01" class="form-control" name="amount" id="payAmountInput" oninput="onTotalPayAmountChanged(this.value)" required style="font-size: 15px; font-weight: 800; color: #0f172a;">
                    </div>
                </div>

                <!-- Split Payments Section -->
                <div style="margin-top: 1rem; margin-bottom: 1rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #475569;">
                            Payment Account Allocation (Multi-Account / Splits)
                        </span>
                        <button type="button" class="inv-btn" style="height: 28px; padding: 0 10px; font-size: 11px; font-weight: 700; color: #0284c7; background: #e0f2fe; border-color: #bae6fd;" onclick="addPaymentSplitRow()">
                            + Add Account Split
                        </button>
                    </div>

                    <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 12px; overflow: hidden;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 12px;" id="splitPaymentsTable">
                            <thead>
                                <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; height: 36px; color: #64748b; font-size: 10.5px; font-weight: 800; text-transform: uppercase;">
                                    <th style="padding: 0 10px; text-align: left;">Disbursement Account</th>
                                    <th style="padding: 0 10px; text-align: right; width: 130px;">Amount (LKR)</th>
                                    <th style="padding: 0 10px; text-align: left; width: 130px;">Cheque / Ref #</th>
                                    <th style="padding: 0 10px; text-align: left; width: 125px;">Cheque Date</th>
                                    <th style="padding: 0 8px; text-align: center; width: 36px;"></th>
                                </tr>
                            </thead>
                            <tbody id="splitPaymentsBody">
                                <!-- Dynamic rows generated by JS -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Allocation Balance Bar -->
                    <div id="splitAllocationSummary" style="display: flex; justify-content: space-between; align-items: center; padding: 8px 14px; background: #f1f5f9; border-radius: 8px; margin-top: 8px; font-size: 12px; font-weight: 700;">
                        <span id="splitTotalAllocatedText">Allocated: LKR 0.00</span>
                        <span id="splitDifferenceBadge" class="prod-badge prod-badge-active" style="background: #dcfce7; color: #15803d;">Balanced</span>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Payment Notes</label>
                    <input type="text" class="form-control" name="notes" placeholder="Optional notes for this disbursement...">
                </div>
            </div>
            <div class="curtiss-modal-footer">
                <button type="button" class="inv-btn inv-btn-ghost" onclick="closeModal('modalPaymentForm')">Cancel</button>
                <button type="submit" class="inv-btn inv-btn-primary" id="btnSubmitPayment">Disburse Payment</button>
            </div>
        </form>
    </div>
</div>


<!-- Modal: Remit EPF / ETF -->
<div class="curtiss-modal" id="modalRemitForm">
    <div class="curtiss-modal-content" style="width: 720px; max-width: 95vw;">
        <div class="curtiss-modal-header">
            <span style="font-weight: 800; font-size: 15px; color: #0f172a;">Statutory EPF & ETF Remittance</span>
            <button type="button" onclick="closeModal('modalRemitForm')" style="background:none; border:none; font-size:1.5rem; color:#64748b; cursor:pointer;">&times;</button>
        </div>
        <form id="remitForm" onsubmit="handleRemitEpfEtf(event)">
            <input type="hidden" name="payroll_period_id" id="remitPeriodIdHidden">
            <input type="hidden" name="remittance_type" id="remitTypeInput" value="Combined">
            <div class="curtiss-modal-body" style="padding: 16px 20px;">
                <!-- Period Overview Banner -->
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 16px; margin-bottom: 12px;">
                    <div style="font-weight: 800; font-size: 1.05rem; color: #0f172a;" id="remitPeriodTitle">-</div>
                    <div style="font-size: 11.5px; color: #64748b; margin-top: 3px;" id="remitPeriodDates">-</div>
                </div>

                <!-- Remittance Type Switcher -->
                <div style="display: flex; gap: 8px; margin-bottom: 12px; background: #f1f5f9; padding: 4px; border-radius: 10px;">
                    <button type="button" class="inv-btn" id="btnRemitTypeCombined" onclick="setRemitType('Combined')" style="flex: 1; height: 32px; font-weight: 800; font-size: 11.5px; background: #0f172a; color: #ffffff;">Combined (EPF & ETF)</button>
                    <button type="button" class="inv-btn" id="btnRemitTypeEPF" onclick="setRemitType('EPF')" style="flex: 1; height: 32px; font-weight: 800; font-size: 11.5px; background: transparent; color: #64748b; border: none;">EPF Only (20%)</button>
                    <button type="button" class="inv-btn" id="btnRemitTypeETF" onclick="setRemitType('ETF')" style="flex: 1; height: 32px; font-weight: 800; font-size: 11.5px; background: transparent; color: #64748b; border: none;">ETF Only (3%)</button>
                </div>

                <!-- EPF Box -->
                <div id="remitEpfSection" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; margin-bottom: 12px;">
                    <div style="font-weight: 800; font-size: 12px; color: #b45309; text-transform: uppercase; margin-bottom: 8px; display: flex; justify-content: space-between;">
                        <span>Central Bank EPF Remittance (20% = EE 8% + ER 12%)</span>
                        <span id="epfDueBadge" style="font-size: 11px; font-weight: 700; color: #64748b;">Due: LKR 0.00</span>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 8px;">
                        <div>
                            <label class="form-label" style="font-size: 10px;">EPF Amount (LKR) *</label>
                            <input type="number" step="0.01" class="form-control" name="epf_amount" id="remitEpfInput" oninput="recalcRemitGrandTotal()" required>
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 10px;">EPF Payment Account *</label>
                            <select class="form-control" name="epf_bank_account_id" id="remitEpfAccountSelect" required>
                                <?php foreach ($bankAccounts as $ba): ?>
                                    <option value="<?= $ba['id'] ?>"><?= htmlspecialchars($ba['bank_name']) ?> - <?= htmlspecialchars($ba['account_name']) ?> (<?= htmlspecialchars($ba['account_number']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div>
                            <label class="form-label" style="font-size: 10px;">EPF Cheque No / Ref No</label>
                            <input type="text" class="form-control" name="epf_cheque_no" id="remitEpfChequeInput" placeholder="e.g. Cheque # / Ref #">
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 10px;">EPF Cheque Date</label>
                            <input type="date" class="form-control" name="epf_cheque_date" id="remitEpfChequeDate" value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                </div>

                <!-- ETF Box -->
                <div id="remitEtfSection" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; margin-bottom: 12px;">
                    <div style="font-weight: 800; font-size: 12px; color: #7e22ce; text-transform: uppercase; margin-bottom: 8px; display: flex; justify-content: space-between;">
                        <span>ETF Board Remittance (3% Employer Contribution)</span>
                        <span id="etfDueBadge" style="font-size: 11px; font-weight: 700; color: #64748b;">Due: LKR 0.00</span>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 8px;">
                        <div>
                            <label class="form-label" style="font-size: 10px;">ETF Amount (LKR) *</label>
                            <input type="number" step="0.01" class="form-control" name="etf_amount" id="remitEtfInput" oninput="recalcRemitGrandTotal()" required>
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 10px;">ETF Payment Account *</label>
                            <select class="form-control" name="etf_bank_account_id" id="remitEtfAccountSelect" required>
                                <?php foreach ($bankAccounts as $ba): ?>
                                    <option value="<?= $ba['id'] ?>"><?= htmlspecialchars($ba['bank_name']) ?> - <?= htmlspecialchars($ba['account_name']) ?> (<?= htmlspecialchars($ba['account_number']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div>
                            <label class="form-label" style="font-size: 10px;">ETF Cheque No / Ref No</label>
                            <input type="text" class="form-control" name="etf_cheque_no" id="remitEtfChequeInput" placeholder="e.g. Cheque # / Ref #">
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 10px;">ETF Cheque Date</label>
                            <input type="date" class="form-control" name="etf_cheque_date" id="remitEtfChequeDate" value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                    <div>
                        <label class="form-label">Payment Date *</label>
                        <input type="date" class="form-control" name="payment_date" required value="<?= date('Y-m-d') ?>">
                    </div>
                    <div>
                        <label class="form-label">Notes</label>
                        <input type="text" class="form-control" name="notes" placeholder="Optional remittance note...">
                    </div>
                </div>

                <div style="padding: 10px 14px; background: #e0f2fe; border-radius: 8px; font-size: 13px; font-weight: 800; color: #0369a1; display: flex; justify-content: space-between;">
                    <span>Total Payment Disbursed:</span>
                    <span id="remitTotalSumText">LKR 0.00</span>
                </div>
            </div>
            <div class="curtiss-modal-footer">
                <button type="button" class="inv-btn inv-btn-ghost" onclick="closeModal('modalRemitForm')">Cancel</button>
                <button type="submit" class="inv-btn inv-btn-primary" id="btnSubmitRemit">Post Statutory Remittance</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Select Thermal Payslip to Print (88mm) -->
<div class="curtiss-modal" id="modalPrintPayslipSelect">
    <div class="curtiss-modal-content" style="max-width: 520px;">
        <div class="curtiss-modal-header">
            <span style="font-weight: 800; font-size: 15px;">🖨️ Print Thermal Payslips (88mm)</span>
            <button type="button" onclick="closeModal('modalPrintPayslipSelect')" style="background:none; border:none; font-size:1.5rem; color:#64748b; cursor:pointer;">&times;</button>
        </div>
        <div class="curtiss-modal-body" style="padding: 1.5rem;">
            <div id="printPayslipPeriodInfo" style="margin-bottom: 1.25rem; padding: 12px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                <!-- Period details populated by JS -->
            </div>

            <div class="form-group" style="margin-bottom: 1.25rem;">
                <label class="form-label" style="font-weight: 800; font-size: 12.5px; color: #0f172a; margin-bottom: 8px; display: block;">Whose payslip would you like to print?</label>
                <select id="selectPayslipTarget" class="form-control" style="width: 100%; height: 38px; font-weight: 600; font-size: 12.5px;">
                    <option value="all">🖨️ Print All Payslips</option>
                </select>
                <div style="font-size: 11px; color: #64748b; margin-top: 6px; line-height: 1.4;">
                    Receipt prints directly to 88mm thermal POS printer with full details: basic salary, sales commissions, overtime, EPF/ETF, advance deductions, and payment settlement.
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 1.25rem;">
                <div style="display: flex; gap: 8px;">
                    <button type="button" class="inv-btn" style="flex: 1; height: 38px; background: #16a34a; color: #ffffff; font-weight: 800; border: none;" onclick="quickPrintAllAutoCut()" title="Sends each payslip as a separate print job so thermal printer cuts after each employee">
                        ✂️ Print All (Auto-Cut Each Slip)
                    </button>
                    <button type="button" class="inv-btn" style="flex: 1; height: 38px; background: #f1f5f9; color: #0f172a; font-weight: 800; border: 1px solid #cbd5e1;" onclick="quickPrintAllPayslips()" title="Opens all slips in a single continuous document with page breaks">
                        📄 Print All (Single Document)
                    </button>
                </div>
                <button type="button" class="inv-btn inv-btn-primary" style="width: 100%; height: 38px; font-weight: 800;" onclick="submitPrintPayslips()">
                    🖨️ Print Selected Employee
                </button>
            </div>
            <div style="font-size: 11px; color: #64748b; margin-top: 8px; line-height: 1.4;">
                💡 <strong>Auto-Cut Note:</strong> <strong>"Auto-Cut Each Slip"</strong> prints each employee slip as an individual print job so your POS printer's auto-cutter cuts the paper after each slip.
            </div>
        </div>
        <div class="curtiss-modal-footer">
            <button type="button" class="inv-btn inv-btn-ghost" onclick="closeModal('modalPrintPayslipSelect')">Cancel</button>
        </div>
    </div>
</div>


<script>
let currentPeriodPage = 1;
let currentPeriodLimit = 25;
let currentPeriodTotalPages = 1;
let currentPeriodsData = [];
let activePeriodId = null;
let activePeriodObj = null;

document.addEventListener('DOMContentLoaded', () => {
    if (typeof CurtissSearchableSelect !== 'undefined') {
        const branchSel = document.getElementById('filterPayrollBranch');
        if (branchSel) CurtissSearchableSelect.attach(branchSel);

        const statusSel = document.getElementById('filterPayrollStatus');
        if (statusSel) CurtissSearchableSelect.attach(statusSel);
    }

    if (typeof CurtissNav !== 'undefined' && CurtissNav.enablePageSearch) {
        CurtissNav.enablePageSearch((term) => {
            currentPeriodPage = 1;
            loadPayrollPeriods(term);
        });
    }

    loadPayrollPeriods();
});

function applyPayrollFilters() {
    currentPeriodPage = 1;
    loadPayrollPeriods();
}

function changePayrollLimit(lim) {
    currentPeriodLimit = parseInt(lim);
    currentPeriodPage = 1;
    loadPayrollPeriods();
}

function prevPayrollPage() {
    if (currentPeriodPage > 1) {
        currentPeriodPage--;
        loadPayrollPeriods();
    }
}

function nextPayrollPage() {
    if (currentPeriodPage < currentPeriodTotalPages) {
        currentPeriodPage++;
        loadPayrollPeriods();
    }
}

async function loadPayrollPeriods(searchTerm = '') {
    const tbody = document.getElementById('payrollTableBody');
    const branchVal = document.getElementById('filterPayrollBranch') ? document.getElementById('filterPayrollBranch').value : '';
    const statusVal = document.getElementById('filterPayrollStatus') ? document.getElementById('filterPayrollStatus').value : '';

    let url = `payroll_api.php?action=list_periods&page=${currentPeriodPage}&limit=${currentPeriodLimit}`;
    if (branchVal) url += `&branch_id=${encodeURIComponent(branchVal)}`;
    if (statusVal) url += `&status=${encodeURIComponent(statusVal)}`;
    if (searchTerm) url += `&search=${encodeURIComponent(searchTerm)}`;

    try {
        const res = await fetch(url);
        const json = await res.json();

        if (!json.success || !json.data) {
            tbody.innerHTML = `<tr><td colspan="9" style="text-align:center; color:#ef4444; padding:2rem;">Failed to load payroll periods.</td></tr>`;
            return;
        }

        const data = json.data;
        currentPeriodsData = data.items || [];
        currentPeriodTotalPages = data.total_pages || 1;

        // Pagination UI
        const start = data.total === 0 ? 0 : (data.page - 1) * data.limit + 1;
        const end = Math.min(data.page * data.limit, data.total);
        document.getElementById('pagStart').innerText = start;
        document.getElementById('pagEnd').innerText = end;
        document.getElementById('pagTotal').innerText = data.total;
        document.getElementById('pagCurrentPage').innerText = `${data.page} / ${currentPeriodTotalPages}`;
        document.getElementById('btnPrevPage').disabled = (data.page <= 1);
        document.getElementById('btnNextPage').disabled = (data.page >= currentPeriodTotalPages);

        if (currentPeriodsData.length === 0) {
            tbody.innerHTML = `<tr><td colspan="9" style="text-align:center; color:#94a3b8; padding:3rem; font-weight:600;">No payroll periods found. Click "+ New Payroll Period" to generate one.</td></tr>`;
            return;
        }

        let html = '';
        currentPeriodsData.forEach(p => {
            let statusPill = `<span class="status-pill status-${p.status}"><span class="dot"></span>${p.status.toUpperCase()}</span>`;

            const netTotal = parseFloat(p.total_net_salary) || 0;
            const paidTotal = parseFloat(p.total_paid) || 0;
            const remTotal = Math.max(0, netTotal - paidTotal);

            // 1. Salary Payment Action Button
            let payBtnHtml = '';
            if (p.status !== 'draft' && p.status !== 'cancelled') {
                if (remTotal > 0.01) {
                    payBtnHtml = `
                        <button type="button" class="prod-act-btn" style="background: #15803d; color: #ffffff; border-color: #15803d; font-weight: 800; white-space: nowrap;" onclick="openPeriodPaymentModal(${p.id}, '${escapeHtml(p.period_name)} (${escapeHtml(p.period_code)})', ${netTotal}, ${paidTotal}, ${remTotal})" title="Disburse Period Salary Payments">
                            Pay (${remTotal.toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0})})
                        </button>
                    `;
                } else {
                    payBtnHtml = `<span style="font-size: 11px; color: #16a34a; font-weight: 800; padding: 0 4px; white-space: nowrap;">✓ Paid</span>`;
                }
            }

            // 2. Statutory EPF & ETF Remittance Action Button
            const totalEpfEtf = (parseFloat(p.total_employee_epf) || 0) + (parseFloat(p.total_employer_epf) || 0) + (parseFloat(p.total_employer_etf) || 0);
            const remittedEpfEtf = parseFloat(p.remitted_epf_etf) || 0;
            const remEpfEtf = Math.max(0, totalEpfEtf - remittedEpfEtf);

            let epfRemitBtnHtml = '';
            if (p.status !== 'draft' && p.status !== 'cancelled' && totalEpfEtf > 0) {
                if (remEpfEtf > 0.01) {
                    epfRemitBtnHtml = `
                        <button type="button" class="prod-act-btn" style="background: #7e22ce; color: #ffffff; border-color: #7e22ce; font-weight: 800; white-space: nowrap;" onclick="openPeriodRemittanceModal(${p.id})" title="Remit Statutory EPF & ETF (Due: LKR ${remEpfEtf.toLocaleString('en-US', {minimumFractionDigits: 2})})">
                            Remit EPF/ETF (${remEpfEtf.toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0})})
                        </button>
                    `;
                } else {
                    epfRemitBtnHtml = `<span style="font-size: 11px; color: #7e22ce; font-weight: 800; padding: 0 4px; white-space: nowrap;" title="Statutory EPF & ETF Remitted">✓ Remitted</span>`;
                }
            }

            // 3. 88mm Thermal Payslips Action
            const payslipBtnHtml = `
                <button type="button" class="prod-act-btn" style="white-space: nowrap;" onclick="openPeriodPayslipSelector(${p.id})" title="Print 88mm Thermal Payslips">
                    🖨️ Payslip
                </button>
            `;

            html += `
                <tr onclick="openFullscreenSheet(${p.id})" style="cursor: pointer;">
                    <td style="font-weight: 800; color: #0f172a; text-align: left;">${escapeHtml(p.period_code)}</td>
                    <td style="font-weight: 800; color: #0f172a; text-align: left;">${escapeHtml(p.period_name)}</td>
                    <td style="font-size: 11.5px; color: #64748b; text-align: left;">${p.start_date} ~ ${p.end_date}</td>
                    <td class="col-center"><span class="status-pill status-draft" style="font-size: 11px;">${p.total_employees} staff</span></td>
                    <td class="col-right" style="font-weight: 700;">${parseFloat(p.total_gross).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                    <td class="col-right" style="font-weight: 800; color: #15803d;">${parseFloat(p.total_net_salary).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                    <td class="col-right" style="font-weight: 800; color: #0f172a;">${parseFloat(p.total_paid).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                    <td class="col-center">${statusPill}</td>
                    <td class="col-right" onclick="event.stopPropagation();">
                        <div style="display: inline-flex; align-items: center; justify-content: flex-end; gap: 6px; flex-wrap: nowrap; white-space: nowrap;">
                            ${payBtnHtml}
                            ${epfRemitBtnHtml}
                            ${payslipBtnHtml}
                        </div>
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
    } catch (err) {
        console.error("Error loading payroll periods:", err);
        tbody.innerHTML = `<tr><td colspan="9" style="text-align:center; color:#ef4444; padding:2rem;">Network error fetching payroll periods.</td></tr>`;
    }
}

let activePeriodEntries = [];

function formatCur(num) {
    const n = parseFloat(num) || 0;
    return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

async function openFullscreenSheet(id) {
    activePeriodId = id;
    try {
        const res = await fetch(`payroll_api.php?action=get_period&id=${id}`);
        const json = await res.json();
        if (!json.success || !json.period) {
            alert("Could not load payroll period details.");
            return;
        }

        const p = json.period;
        const entries = json.entries || [];
        activePeriodObj = p;
        activePeriodEntries = entries;

        // Populate Fullscreen Header
        document.getElementById('fullPeriodBadge').innerText = `${p.period_name} (${p.period_code})`;
        document.getElementById('fullPeriodStatusPill').innerHTML = `<span class="status-pill status-${p.status}"><span class="dot"></span>${p.status.toUpperCase()}</span>`;
        document.getElementById('fullEmployeesCount').innerText = `${entries.length} Employees Included`;
        document.getElementById('fullBranchText').innerText = p.branch_name || 'All Branches';
        document.getElementById('fullDateRangeText').innerText = `${p.start_date} ~ ${p.end_date}`;

        const isDraft = (p.status === 'draft');
        const isApproved = (p.status === 'approved');
        const isCompleted = (p.status === 'completed');

        document.getElementById('fullDraftEditNotice').style.display = isDraft ? 'inline-flex' : 'none';

        // Header Action buttons
        let actionsHtml = '';
        if (isDraft) {
            actionsHtml += `
                <button type="button" class="inv-btn inv-btn-primary" onclick="approvePayrollPeriod(${p.id})">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    Approve & Post Journals
                </button>
            `;
        } else if (isApproved && parseFloat(p.total_paid) === 0) {
            actionsHtml += `
                <button type="button" class="inv-btn" style="color: #dc2626; background: #fef2f2; border-color: #fca5a5;" onclick="promptCancelPeriod(${p.id})">
                    Cancel & Reverse Period
                </button>
            `;
        }
        document.getElementById('fullSheetHeaderActions').innerHTML = actionsHtml;

        // Clear search
        const sInput = document.getElementById('fullSheetSearch');
        if (sInput) sInput.value = '';

        // Render table
        renderFullscreenEntries(entries, isDraft);
        recalculateFullscreenTotals();

        document.getElementById('fullscreenPayrollSheet').classList.add('active');
        document.body.style.overflow = 'hidden';
    } catch (err) {
        console.error("Error opening fullscreen payroll sheet:", err);
        alert("Network error opening payroll sheet.");
    }
}

function openPeriodDrawer(id) {
    openFullscreenSheet(id);
}

function closeFullscreenSheet() {
    document.getElementById('fullscreenPayrollSheet').classList.remove('active');
    document.body.style.overflow = '';
    loadPayrollPeriods();
}

function closePeriodDrawer() {
    closeFullscreenSheet();
}

// Global Escape key to close fullscreen
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        const sheet = document.getElementById('fullscreenPayrollSheet');
        if (sheet && sheet.classList.contains('active')) {
            closeFullscreenSheet();
        }
    }
});

function renderFullscreenEntries(entries, isDraft) {
    const tbody = document.getElementById('fullscreenPayrollBody');
    if (!entries || entries.length === 0) {
        tbody.innerHTML = `<tr><td colspan="11" style="text-align: center; color: #94a3b8; padding: 40px; font-weight: 600;">No employee records found in this payroll period.</td></tr>`;
        return;
    }

    let html = '';
    entries.forEach(e => {
        const basic = parseFloat(e.basic_salary) || 0;
        const periodSales = parseFloat(e.period_sales) || 0;
        const salesCount = parseInt(e.sales_count) || 0;
        const comms = parseFloat(e.commissions) || 0;
        const gross = parseFloat(e.gross_salary) || 0;
        const epf8 = parseFloat(e.employee_epf_amount) || 0;
        const adv = parseFloat(e.salary_advances_deducted) || 0;
        const other = parseFloat(e.other_deductions) || 0;
        const net = parseFloat(e.net_salary) || 0;
        const epf12 = parseFloat(e.employer_epf_amount) || 0;
        const etf3 = parseFloat(e.employer_etf_amount) || 0;

        const isSalesRep = (parseInt(e.is_sales_rep) === 1 || periodSales > 0 || comms > 0);
        const subtagHtml = isSalesRep
            ? `<div style="font-size: 10px; font-weight: 700; color: #0284c7; display: inline-flex; align-items: center; gap: 4px; margin-top: 2px;">
                 <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                 Sales Rep
               </div>`
            : `<div style="font-size: 10px; font-weight: 600; color: #64748b; margin-top: 2px;">${escapeHtml(e.job_role || 'Staff')}</div>`;

        const periodSalesHtml = periodSales > 0
            ? `<div style="display: flex; flex-direction: column; align-items: flex-end; justify-content: center;">
                 <span style="font-weight: 800; color: #0284c7; font-size: 13px;">${formatCur(periodSales)}</span>
                 <span style="font-size: 10px; color: #64748b; font-weight: 600;">${salesCount} sale${salesCount === 1 ? '' : 's'}</span>
               </div>`
            : `<span style="color: #94a3b8; font-weight: 500;">0.00</span>`;

        let commColHtml = '';
        if (isDraft) {
            commColHtml = `
                <div style="display: flex; justify-content: flex-end; align-items: center;">
                    <input type="number" step="0.01" min="0" value="${comms.toFixed(2)}"
                        class="comm-cell-input" id="commInput_${e.id}" data-id="${e.id}"
                        placeholder="0.00"
                        onfocus="this.select()"
                        oninput="onLiveRecalculateRow(${e.id})"
                        onchange="saveEntryCommission(${e.id}, this.value)"
                        onkeydown="handleCommissionKeynav(event, ${e.id})" />
                </div>
            `;
        } else {
            commColHtml = `<span style="font-weight: 700; color: #16a34a;">${formatCur(comms)}</span>`;
        }

        let otherColHtml = '';
        if (isDraft) {
            otherColHtml = `
                <div style="display: flex; justify-content: flex-end; align-items: center;">
                    <input type="number" step="0.01" min="0" value="${other.toFixed(2)}"
                        class="other-cell-input" id="otherInput_${e.id}" data-id="${e.id}"
                        placeholder="0.00"
                        onfocus="this.select()"
                        oninput="onLiveRecalculateRow(${e.id})"
                        onchange="saveEntryOtherDeduction(${e.id}, this.value)"
                        onkeydown="handleOtherKeynav(event, ${e.id})" />
                </div>
            `;
        } else {
            otherColHtml = `<span style="font-weight: 700; color: #dc2626;">${formatCur(other)}</span>`;
        }

        html += `
            <tr id="payrollRow_${e.id}">
                <td style="padding: 0 12px; vertical-align: middle;">
                    <div style="font-weight: 800; color: #0f172a; font-size: 13px;">${escapeHtml(e.employee_name)}</div>
                    <div style="font-size: 10.5px; color: #64748b; font-weight: 600;">Code: ${escapeHtml(e.employee_code || '')}</div>
                    ${subtagHtml}
                </td>
                <td class="col-right" style="font-weight: 700; color: #0f172a; padding: 0 12px;">
                    ${formatCur(basic)}
                </td>
                <td class="col-right" style="padding: 0 12px;">
                    ${periodSalesHtml}
                </td>
                <td class="col-right" style="padding: 0 12px;" id="commCell_${e.id}">
                    ${commColHtml}
                </td>
                <td class="col-right" style="font-weight: 700; color: #0f172a; padding: 0 12px;" id="grossCell_${e.id}">
                    ${formatCur(gross)}
                </td>
                <td class="col-right" style="font-weight: 700; color: #dc2626; padding: 0 12px;" id="epf8Cell_${e.id}">
                    ${formatCur(epf8)}
                </td>
                <td class="col-right" style="font-weight: 700; color: #dc2626; padding: 0 12px;">
                    ${formatCur(adv)}
                </td>
                <td class="col-right" style="padding: 0 12px;" id="otherCell_${e.id}">
                    ${otherColHtml}
                </td>
                <td class="col-right" style="font-weight: 800; color: #16a34a; font-size: 13.5px; padding: 0 12px;" id="netCell_${e.id}">
                    ${formatCur(net)}
                </td>
                <td class="col-right" style="font-weight: 700; color: #d97706; padding: 0 12px;" id="epf12Cell_${e.id}">
                    ${formatCur(epf12)}
                </td>
                <td class="col-right" style="font-weight: 700; color: #9333ea; padding: 0 12px;" id="etf3Cell_${e.id}">
                    ${formatCur(etf3)}
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

function round2(val) {
    return Math.round((parseFloat(val) + Number.EPSILON) * 100) / 100;
}

function onLiveRecalculateRow(entryId) {
    const entry = activePeriodEntries.find(x => x.id === entryId);
    if (!entry) return;

    const commInput = document.getElementById(`commInput_${entryId}`);
    const otherInput = document.getElementById(`otherInput_${entryId}`);

    const comm = commInput ? Math.max(0, parseFloat(commInput.value) || 0) : (parseFloat(entry.commissions) || 0);
    const other = otherInput ? Math.max(0, parseFloat(otherInput.value) || 0) : (parseFloat(entry.other_deductions) || 0);

    entry.commissions = comm;
    entry.other_deductions = other;

    const basic = parseFloat(entry.basic_salary) || 0;
    const allowances = parseFloat(entry.allowances) || 0;
    const bonuses = parseFloat(entry.bonuses) || 0;
    const overtime = parseFloat(entry.overtime_amount) || 0;
    const adv = parseFloat(entry.salary_advances_deducted) || 0;

    // Gross Salary = Basic + Allowances + Bonuses + Commissions + Overtime
    const gross = round2(basic + allowances + bonuses + comm + overtime);
    entry.gross_salary = gross;

    // Sri Lankan Labor Law (Employees' Provident Fund Act):
    // Total monthly earnings for EPF/ETF explicitly include Basic Salary, Commissions, Allowances & Bonuses
    const epfEligibleBase = round2(basic + comm + allowances + bonuses);
    const epf8 = round2(epfEligibleBase * 0.08);   // Employee EPF 8%
    const epf12 = round2(epfEligibleBase * 0.12);  // Employer EPF 12%
    const etf3 = round2(epfEligibleBase * 0.03);   // Employer ETF 3%

    entry.employee_epf_amount = epf8;
    entry.employer_epf_amount = epf12;
    entry.employer_etf_amount = etf3;

    // Total deductions = Employee EPF (8%) + Salary Advances Recovered + Other Deductions
    const totalDeduct = round2(epf8 + adv + other);
    entry.total_deductions = totalDeduct;

    // Net Salary = Gross - Total Deductions
    const net = Math.max(0, round2(gross - totalDeduct));
    entry.net_salary = net;

    // Update Row DOM cells
    const grossEl = document.getElementById(`grossCell_${entryId}`);
    if (grossEl) grossEl.innerText = formatCur(gross);

    const epf8El = document.getElementById(`epf8Cell_${entryId}`);
    if (epf8El) epf8El.innerText = formatCur(epf8);

    const epf12El = document.getElementById(`epf12Cell_${entryId}`);
    if (epf12El) epf12El.innerText = formatCur(epf12);

    const etf3El = document.getElementById(`etf3Cell_${entryId}`);
    if (etf3El) etf3El.innerText = formatCur(etf3);

    const netEl = document.getElementById(`netCell_${entryId}`);
    if (netEl) netEl.innerText = formatCur(net);

    // Recompute sticky footer and ribbon
    recalculateFullscreenTotals();
}

async function saveEntryCommission(entryId, newCommVal) {
    const inp = document.getElementById(`commInput_${entryId}`);
    const entry = activePeriodEntries.find(x => x.id === entryId);
    const commVal = (parseFloat(newCommVal) || 0).toFixed(2);
    const otherVal = entry ? (parseFloat(entry.other_deductions) || 0).toFixed(2) : '0.00';

    try {
        const formData = new FormData();
        formData.append('entry_id', entryId);
        formData.append('commissions', commVal);
        formData.append('other_deductions', otherVal);

        const res = await fetch('payroll_api.php?action=update_entry', {
            method: 'POST',
            body: formData
        });
        const json = await res.json();
        if (json.success && inp) {
            inp.style.borderColor = '#16a34a';
            inp.style.boxShadow = '0 0 0 2px rgba(22, 163, 74, 0.25)';
            setTimeout(() => {
                if (inp) {
                    inp.style.borderColor = '';
                    inp.style.boxShadow = '';
                }
            }, 1200);
        }
    } catch (e) {
        console.error("Save commission error:", e);
    }
}

async function saveEntryOtherDeduction(entryId, newOtherVal) {
    const inp = document.getElementById(`otherInput_${entryId}`);
    const entry = activePeriodEntries.find(x => x.id === entryId);
    const commVal = entry ? (parseFloat(entry.commissions) || 0).toFixed(2) : '0.00';
    const otherVal = (parseFloat(newOtherVal) || 0).toFixed(2);

    try {
        const formData = new FormData();
        formData.append('entry_id', entryId);
        formData.append('commissions', commVal);
        formData.append('other_deductions', otherVal);

        const res = await fetch('payroll_api.php?action=update_entry', {
            method: 'POST',
            body: formData
        });
        const json = await res.json();
        if (json.success && inp) {
            inp.style.borderColor = '#dc2626';
            inp.style.boxShadow = '0 0 0 2px rgba(220, 38, 38, 0.25)';
            setTimeout(() => {
                if (inp) {
                    inp.style.borderColor = '';
                    inp.style.boxShadow = '';
                }
            }, 1200);
        }
    } catch (e) {
        console.error("Save other deduction error:", e);
    }
}

function handleCommissionKeynav(event, entryId) {
    if (event.key === 'Enter' || event.key === 'ArrowDown') {
        event.preventDefault();
        saveEntryCommission(entryId, event.target.value);
        const inputs = Array.from(document.querySelectorAll('.comm-cell-input:not([disabled])'));
        const currentIndex = inputs.indexOf(event.target);
        if (currentIndex !== -1 && currentIndex < inputs.length - 1) {
            inputs[currentIndex + 1].focus();
            inputs[currentIndex + 1].select();
        }
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        saveEntryCommission(entryId, event.target.value);
        const inputs = Array.from(document.querySelectorAll('.comm-cell-input:not([disabled])'));
        const currentIndex = inputs.indexOf(event.target);
        if (currentIndex > 0) {
            inputs[currentIndex - 1].focus();
            inputs[currentIndex - 1].select();
        }
    } else if (event.key === 'ArrowRight') {
        const otherInp = document.getElementById(`otherInput_${entryId}`);
        if (otherInp) {
            otherInp.focus();
            otherInp.select();
        }
    }
}

function handleOtherKeynav(event, entryId) {
    if (event.key === 'Enter' || event.key === 'ArrowDown') {
        event.preventDefault();
        saveEntryOtherDeduction(entryId, event.target.value);
        const inputs = Array.from(document.querySelectorAll('.other-cell-input:not([disabled])'));
        const currentIndex = inputs.indexOf(event.target);
        if (currentIndex !== -1 && currentIndex < inputs.length - 1) {
            inputs[currentIndex + 1].focus();
            inputs[currentIndex + 1].select();
        }
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        saveEntryOtherDeduction(entryId, event.target.value);
        const inputs = Array.from(document.querySelectorAll('.other-cell-input:not([disabled])'));
        const currentIndex = inputs.indexOf(event.target);
        if (currentIndex > 0) {
            inputs[currentIndex - 1].focus();
            inputs[currentIndex - 1].select();
        }
    } else if (event.key === 'ArrowLeft') {
        const commInp = document.getElementById(`commInput_${entryId}`);
        if (commInp) {
            commInp.focus();
            commInp.select();
        }
    }
}

function recalculateFullscreenTotals() {
    let totBase = 0;
    let totSales = 0;
    let totComms = 0;
    let totGross = 0;
    let totEpf8 = 0;
    let totAdv = 0;
    let totOther = 0;
    let totNet = 0;
    let totEpf12 = 0;
    let totEtf3 = 0;
    let totPaid = 0;

    activePeriodEntries.forEach(e => {
        totBase += parseFloat(e.basic_salary) || 0;
        totSales += parseFloat(e.period_sales) || 0;
        totComms += parseFloat(e.commissions) || 0;
        totGross += parseFloat(e.gross_salary) || 0;
        totEpf8 += parseFloat(e.employee_epf_amount) || 0;
        totAdv += parseFloat(e.salary_advances_deducted) || 0;
        totOther += parseFloat(e.other_deductions) || 0;
        totNet += parseFloat(e.net_salary) || 0;
        totEpf12 += parseFloat(e.employer_epf_amount) || 0;
        totEtf3 += parseFloat(e.employer_etf_amount) || 0;
        totPaid += parseFloat(e.paid_amount) || 0;
    });

    // Update Ribbon
    const rSales = document.getElementById('ribbonSales');
    if (rSales) rSales.innerText = formatCur(totSales);
    const rGross = document.getElementById('ribbonGross');
    if (rGross) rGross.innerText = formatCur(totGross);
    const rAdv = document.getElementById('ribbonAdv');
    if (rAdv) rAdv.innerText = formatCur(totAdv);
    const rNet = document.getElementById('ribbonNet');
    if (rNet) rNet.innerText = formatCur(totNet);
    const rPaid = document.getElementById('ribbonPaid');
    if (rPaid) rPaid.innerText = formatCur(totPaid);

    // Update Footer
    const fBase = document.getElementById('footBase');
    if (fBase) fBase.innerText = formatCur(totBase);
    const fSales = document.getElementById('footSales');
    if (fSales) fSales.innerText = formatCur(totSales);
    const fComms = document.getElementById('footComms');
    if (fComms) fComms.innerText = formatCur(totComms);
    const fGross = document.getElementById('footGross');
    if (fGross) fGross.innerText = formatCur(totGross);
    const fEpf8 = document.getElementById('footEpf8');
    if (fEpf8) fEpf8.innerText = formatCur(totEpf8);
    const fAdv = document.getElementById('footAdv');
    if (fAdv) fAdv.innerText = formatCur(totAdv);
    const fOther = document.getElementById('footOther');
    if (fOther) fOther.innerText = formatCur(totOther);
    const fNet = document.getElementById('footNet');
    if (fNet) fNet.innerText = formatCur(totNet);
    const fEpf12 = document.getElementById('footEpf12');
    if (fEpf12) fEpf12.innerText = formatCur(totEpf12);
    const fEtf3 = document.getElementById('footEtf3');
    if (fEtf3) fEtf3.innerText = formatCur(totEtf3);
}

function filterFullscreenSheet(term) {
    const q = (term || '').trim().toLowerCase();
    activePeriodEntries.forEach(e => {
        const row = document.getElementById(`payrollRow_${e.id}`);
        if (!row) return;
        if (!q) {
            row.style.display = '';
        } else {
            const rowData = row.getAttribute('data-emp') || '';
            row.style.display = rowData.includes(q) ? '' : 'none';
        }
    });
}

async function approvePayrollPeriod(periodId) {
    if (!confirm("Approve this payroll period and post double-entry journals to Chart of Accounts? This will lock employee basic amounts and record salary & statutory liabilities.")) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('period_id', periodId);

        const res = await fetch('payroll_api.php?action=approve_period', {
            method: 'POST',
            body: formData
        });
        const json = await res.json();

        if (json.success) {
            alert("Payroll approved and accounting journals posted successfully!");
            openFullscreenSheet(periodId);
            loadPayrollPeriods();
        } else {
            alert(json.message || "Failed to approve payroll period.");
        }
    } catch (err) {
        alert("Server error approving payroll period.");
    }
}

function openCreatePeriodModal() {
    document.getElementById('modalPeriodForm').classList.add('active');
}

async function handleCreatePeriod(event) {
    event.preventDefault();
    const btn = document.getElementById('btnSubmitPeriod');
    btn.disabled = true;

    try {
        const form = document.getElementById('createPeriodForm');
        const formData = new FormData(form);

        const res = await fetch('payroll_api.php?action=create_period', {
            method: 'POST',
            body: formData
        });
        const json = await res.json();

        if (json.success) {
            closeModal('modalPeriodForm');
            loadPayrollPeriods();
            if (json.period_id) {
                openFullscreenSheet(json.period_id);
            }
        } else {
            alert(json.message || "Failed to create payroll period.");
        }
    } catch (err) {
        alert("Server error creating payroll period.");
    } finally {
        btn.disabled = false;
    }
}

window.availablePaymentAccounts = <?= json_encode($paymentAccounts) ?>;
window.availableBankAccounts = <?= json_encode($bankAccounts) ?>;

function openPeriodPaymentModal(periodId, periodTitle, net, paid, remaining) {
    document.getElementById('payPeriodId').value = periodId;
    document.getElementById('payEntryId').value = '';
    document.getElementById('payEmployeeName').innerText = periodTitle;
    document.getElementById('payNetSalary').innerText = `LKR ${parseFloat(net).toLocaleString('en-US', {minimumFractionDigits: 2})}`;
    document.getElementById('payAlreadyPaid').innerText = `LKR ${parseFloat(paid).toLocaleString('en-US', {minimumFractionDigits: 2})}`;
    document.getElementById('payOutstanding').innerText = `LKR ${parseFloat(remaining).toLocaleString('en-US', {minimumFractionDigits: 2})}`;
    document.getElementById('payAmountInput').value = remaining.toFixed(2);
    document.getElementById('payAmountInput').max = remaining.toFixed(2);

    let defKey = '';
    if (window.availablePaymentAccounts && window.availablePaymentAccounts.length > 0) {
        const acc = window.availablePaymentAccounts[0];
        defKey = `acc_${acc.account_id}_${acc.payment_method}_${acc.bank_account_id || ''}`;
    }

    paymentSplitRows = [
        {
            account_key: defKey,
            amount: remaining,
            ref: '',
            cheque_date: '<?= date('Y-m-d') ?>'
        }
    ];

    renderPaymentSplitRows();
    document.getElementById('modalPaymentForm').classList.add('active');
    setTimeout(() => document.getElementById('payAmountInput').focus(), 80);
}

function openPaymentModal(entryId, empName, net, paid, remaining) {
    document.getElementById('payPeriodId').value = '';
    document.getElementById('payEntryId').value = entryId;
    document.getElementById('payEmployeeName').innerText = empName;
    document.getElementById('payNetSalary').innerText = `LKR ${parseFloat(net).toLocaleString('en-US', {minimumFractionDigits: 2})}`;
    document.getElementById('payAlreadyPaid').innerText = `LKR ${parseFloat(paid).toLocaleString('en-US', {minimumFractionDigits: 2})}`;
    document.getElementById('payOutstanding').innerText = `LKR ${parseFloat(remaining).toLocaleString('en-US', {minimumFractionDigits: 2})}`;
    document.getElementById('payAmountInput').value = remaining.toFixed(2);
    document.getElementById('payAmountInput').max = remaining.toFixed(2);

    let defKey = '';
    if (window.availablePaymentAccounts && window.availablePaymentAccounts.length > 0) {
        const acc = window.availablePaymentAccounts[0];
        defKey = `acc_${acc.account_id}_${acc.payment_method}_${acc.bank_account_id || ''}`;
    }

    paymentSplitRows = [
        {
            account_key: defKey,
            amount: remaining,
            ref: '',
            cheque_date: '<?= date('Y-m-d') ?>'
        }
    ];

    renderPaymentSplitRows();
    document.getElementById('modalPaymentForm').classList.add('active');
    setTimeout(() => document.getElementById('payAmountInput').focus(), 80);
}

function renderPaymentSplitRows() {
    const tbody = document.getElementById('splitPaymentsBody');
    if (!tbody) return;

    let html = '';
    paymentSplitRows.forEach((row, idx) => {
        let optionsHtml = '';
        if (window.availablePaymentAccounts) {
            window.availablePaymentAccounts.forEach(acc => {
                const key = `acc_${acc.account_id}_${acc.payment_method}_${acc.bank_account_id || ''}`;
                const sel = (row.account_key === key || (!row.account_key && idx === 0)) ? 'selected' : '';
                optionsHtml += `<option value="${key}" data-acc-id="${acc.account_id}" data-method="${escapeHtml(acc.payment_method)}" data-bank-id="${acc.bank_account_id || ''}" ${sel}>${escapeHtml(acc.formatted_label || acc.account_name)}</option>`;
            });
        }
        if (window.availableBankAccounts) {
            window.availableBankAccounts.forEach(ba => {
                const key = `chq_${ba.id}`;
                const sel = (row.account_key === key) ? 'selected' : '';
                optionsHtml += `<option value="${key}" data-method="Cheque" data-bank-id="${ba.id}" ${sel}>Cheque: ${escapeHtml(ba.bank_name)} - ${escapeHtml(ba.account_name)} (${escapeHtml(ba.account_number)})</option>`;
            });
        }

        const isCheque = row.account_key && row.account_key.startsWith('chq_');

        html += `
            <tr style="height: 42px; border-bottom: 1px solid #f1f5f9;">
                <td style="padding: 4px 8px;">
                    <select class="form-control" style="height: 32px; padding: 0 8px; font-size: 11.5px; font-weight: 600;" onchange="updateSplitAccount(${idx}, this)">
                        ${optionsHtml}
                    </select>
                </td>
                <td style="padding: 4px 8px;">
                    <input type="number" step="0.01" min="0.01" class="form-control" style="height: 32px; padding: 0 8px; font-size: 12px; font-weight: 800; text-align: right;" value="${parseFloat(row.amount || 0).toFixed(2)}" oninput="updateSplitAmount(${idx}, this.value)">
                </td>
                <td style="padding: 4px 8px;">
                    <input type="text" class="form-control" style="height: 32px; padding: 0 8px; font-size: 11.5px;" placeholder="${isCheque ? 'Cheque No' : 'Ref / Slip #'}" value="${escapeHtml(row.ref || '')}" oninput="updateSplitRef(${idx}, this.value)">
                </td>
                <td style="padding: 4px 8px;">
                    <input type="date" class="form-control" style="height: 32px; padding: 0 6px; font-size: 11.5px;" value="${row.cheque_date || '<?= date('Y-m-d') ?>'}" oninput="updateSplitChequeDate(${idx}, this.value)">
                </td>
                <td style="padding: 4px 6px; text-align: center;">
                    ${paymentSplitRows.length > 1 ? `
                        <button type="button" class="inv-btn" style="height: 26px; width: 26px; padding: 0; min-height: 26px; border-radius: 50%; color: #dc2626; background: #fee2e2; border: none; font-size: 14px; font-weight: 800;" onclick="removePaymentSplitRow(${idx})" title="Remove Split">&times;</button>
                    ` : ''}
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
    recalcPaymentSplits();
}

function addPaymentSplitRow() {
    const totalToPay = parseFloat(document.getElementById('payAmountInput').value) || 0;
    const currentAlloc = paymentSplitRows.reduce((sum, r) => sum + (parseFloat(r.amount) || 0), 0);
    const rem = Math.max(0, round2(totalToPay - currentAlloc));

    let defKey = '';
    if (window.availablePaymentAccounts && window.availablePaymentAccounts.length > 1) {
        const acc = window.availablePaymentAccounts[1];
        defKey = `acc_${acc.account_id}_${acc.payment_method}_${acc.bank_account_id || ''}`;
    }

    paymentSplitRows.push({
        account_key: defKey,
        amount: rem,
        ref: '',
        cheque_date: '<?= date('Y-m-d') ?>'
    });
    renderPaymentSplitRows();
}

function removePaymentSplitRow(idx) {
    if (paymentSplitRows.length <= 1) return;
    paymentSplitRows.splice(idx, 1);
    renderPaymentSplitRows();
}

function updateSplitAccount(idx, selectEl) {
    paymentSplitRows[idx].account_key = selectEl.value;
    const opt = selectEl.options[selectEl.selectedIndex];
    paymentSplitRows[idx].account_id = opt.dataset.accId || null;
    paymentSplitRows[idx].payment_method = opt.dataset.method || 'Cash in Hand';
    paymentSplitRows[idx].bank_account_id = opt.dataset.bankId || null;
    renderPaymentSplitRows();
}

function updateSplitAmount(idx, val) {
    paymentSplitRows[idx].amount = Math.max(0, parseFloat(val) || 0);
    recalcPaymentSplits();
}

function updateSplitRef(idx, val) {
    paymentSplitRows[idx].ref = val;
}

function updateSplitChequeDate(idx, val) {
    paymentSplitRows[idx].cheque_date = val;
}

function onTotalPayAmountChanged(val) {
    const tot = Math.max(0, parseFloat(val) || 0);
    if (paymentSplitRows.length === 1) {
        paymentSplitRows[0].amount = tot;
        renderPaymentSplitRows();
    } else {
        recalcPaymentSplits();
    }
}

function recalcPaymentSplits() {
    const targetTot = round2(parseFloat(document.getElementById('payAmountInput').value) || 0);
    const allocTot = round2(paymentSplitRows.reduce((sum, r) => sum + (parseFloat(r.amount) || 0), 0));
    const diff = round2(targetTot - allocTot);

    const allocText = document.getElementById('splitTotalAllocatedText');
    if (allocText) allocText.innerText = `Allocated: LKR ${allocTot.toLocaleString('en-US', {minimumFractionDigits: 2})} / Total: LKR ${targetTot.toLocaleString('en-US', {minimumFractionDigits: 2})}`;

    const badge = document.getElementById('splitDifferenceBadge');
    const submitBtn = document.getElementById('btnSubmitPayment');

    if (Math.abs(diff) < 0.01 && targetTot > 0) {
        if (badge) {
            badge.className = 'prod-badge prod-badge-active';
            badge.style.background = '#dcfce7';
            badge.style.color = '#15803d';
            badge.innerText = '✓ Balanced (100%)';
        }
        if (submitBtn) submitBtn.disabled = false;
    } else {
        if (badge) {
            badge.className = 'prod-badge';
            badge.style.background = '#fee2e2';
            badge.style.color = '#b91c1c';
            badge.innerText = (diff > 0) ? `Remaining to allocate: LKR ${diff.toFixed(2)}` : `Over-allocated by: LKR ${Math.abs(diff).toFixed(2)}`;
        }
        if (submitBtn) submitBtn.disabled = true;
    }
}

async function handleRecordPayment(event) {
    event.preventDefault();
    const btn = document.getElementById('btnSubmitPayment');
    btn.disabled = true;

    try {
        const form = document.getElementById('recordPaymentForm');
        const formData = new FormData(form);

        // Serialize splits array
        const splitsPayload = paymentSplitRows.map((r) => {
            let m = r.payment_method || 'Cash in Hand';
            let accId = r.account_id || null;
            let bankId = r.bank_account_id || null;

            if (r.account_key) {
                if (r.account_key.startsWith('chq_')) {
                    m = 'Cheque';
                    bankId = r.account_key.replace('chq_', '');
                } else if (r.account_key.startsWith('acc_')) {
                    const parts = r.account_key.split('_');
                    accId = parts[1] || null;
                    m = parts[2] || m;
                    bankId = parts[3] || bankId;
                }
            }

            return {
                amount: parseFloat(r.amount) || 0,
                payment_method: m,
                account_id: accId,
                bank_account_id: bankId,
                cheque_number: (m === 'Cheque') ? (r.ref || '') : '',
                cheque_date: (m === 'Cheque') ? (r.cheque_date || '') : null,
                reference_no: (m !== 'Cheque') ? (r.ref || '') : '',
                notes: ''
            };
        });

        formData.append('splits', JSON.stringify(splitsPayload));

        const res = await fetch('payroll_api.php?action=record_payment', {
            method: 'POST',
            body: formData
        });
        const json = await res.json();

        if (json.success) {
            alert(json.message || "Salary payment recorded successfully!");
            closeModal('modalPaymentForm');
            if (activePeriodId) openFullscreenSheet(activePeriodId);
            loadPayrollPeriods();
        } else {
            alert(json.message || "Failed to record payment.");
        }
    } catch (err) {
        alert("Server error recording salary payment.");
    } finally {
        btn.disabled = false;
    }
}

async function promptCancelPeriod(periodId) {
    const reason = prompt("Are you sure you want to cancel and reverse this payroll period?\nAny recovered salary advances will be safely restored.\nEnter cancellation reason:");
    if (!reason || !reason.trim()) return;

    try {
        const formData = new FormData();
        formData.append('period_id', periodId);
        formData.append('reason', reason.trim());

        const res = await fetch('payroll_api.php?action=cancel_period', {
            method: 'POST',
            body: formData
        });
        const json = await res.json();
        alert(json.message);
        if (json.success) {
            closeFullscreenSheet();
            loadPayrollPeriods();
        }
    } catch (e) {
        alert("Server error cancelling payroll period.");
    }
}

function setRemitType(type) {
    document.getElementById('remitTypeInput').value = type;

    // Toggle button style
    ['Combined', 'EPF', 'ETF'].forEach(t => {
        const btn = document.getElementById(`btnRemitType${t}`);
        if (!btn) return;
        if (t === type) {
            btn.style.background = '#0f172a';
            btn.style.color = '#ffffff';
        } else {
            btn.style.background = 'transparent';
            btn.style.color = '#64748b';
        }
    });

    const epfSec = document.getElementById('remitEpfSection');
    const etfSec = document.getElementById('remitEtfSection');

    if (type === 'Combined') {
        if (epfSec) epfSec.style.display = 'block';
        if (etfSec) etfSec.style.display = 'block';
    } else if (type === 'EPF') {
        if (epfSec) epfSec.style.display = 'block';
        if (etfSec) etfSec.style.display = 'none';
        document.getElementById('remitEtfInput').value = '0.00';
    } else if (type === 'ETF') {
        if (epfSec) epfSec.style.display = 'none';
        if (etfSec) etfSec.style.display = 'block';
        document.getElementById('remitEpfInput').value = '0.00';
    }

    const pid = document.getElementById('remitPeriodIdHidden').value;
    const p = currentPeriodsData.find(x => x.id == pid);
    if (p) {
        const epfTotal = (parseFloat(p.total_employee_epf) + parseFloat(p.total_employer_epf)) || 0;
        const etfTotal = (parseFloat(p.total_employer_etf)) || 0;

        if (type !== 'ETF') document.getElementById('remitEpfInput').value = epfTotal.toFixed(2);
        if (type !== 'EPF') document.getElementById('remitEtfInput').value = etfTotal.toFixed(2);
    }

    recalcRemitGrandTotal();
}

function openPeriodRemittanceModal(periodId) {
    const p = currentPeriodsData.find(x => x.id == periodId);
    if (!p) {
        alert("Payroll period not found.");
        return;
    }

    document.getElementById('remitPeriodIdHidden').value = p.id;
    document.getElementById('remitPeriodTitle').innerText = `${p.period_name} (${p.period_code})`;
    document.getElementById('remitPeriodDates').innerText = `${p.start_date} ~ ${p.end_date} • ${p.total_employees || 0} Staff • Status: ${p.status.toUpperCase()}`;

    const epfTotal = (parseFloat(p.total_employee_epf) + parseFloat(p.total_employer_epf)) || 0;
    const etfTotal = (parseFloat(p.total_employer_etf)) || 0;

    document.getElementById('epfDueBadge').innerText = `Due: LKR ${epfTotal.toLocaleString('en-US', {minimumFractionDigits: 2})}`;
    document.getElementById('etfDueBadge').innerText = `Due: LKR ${etfTotal.toLocaleString('en-US', {minimumFractionDigits: 2})}`;

    setRemitType('Combined');
    document.getElementById('modalRemitForm').classList.add('active');
}

function openRemittanceModal(periodId) {
    if (periodId) {
        openPeriodRemittanceModal(periodId);
    } else if (currentPeriodsData.length > 0) {
        openPeriodRemittanceModal(currentPeriodsData[0].id);
    }
}

function recalcRemitGrandTotal() {
    const type = document.getElementById('remitTypeInput').value;
    const epf = (type !== 'ETF') ? (parseFloat(document.getElementById('remitEpfInput').value) || 0) : 0;
    const etf = (type !== 'EPF') ? (parseFloat(document.getElementById('remitEtfInput').value) || 0) : 0;
    const tot = round2(epf + etf);
    document.getElementById('remitTotalSumText').innerText = `LKR ${tot.toLocaleString('en-US', {minimumFractionDigits: 2})}`;
}

async function handleRemitEpfEtf(event) {
    event.preventDefault();
    const btn = document.getElementById('btnSubmitRemit');
    btn.disabled = true;

    try {
        const form = document.getElementById('remitForm');
        const formData = new FormData(form);

        const res = await fetch('payroll_api.php?action=remit_epf_etf', {
            method: 'POST',
            body: formData
        });
        const json = await res.json();

        if (json.success) {
            alert(json.message || "Statutory EPF & ETF remittance posted successfully!");
            closeModal('modalRemitForm');
            form.reset();
            loadPayrollPeriods();
        } else {
            alert(json.message || "Failed to post remittance.");
        }
    } catch (err) {
        alert("Server error posting remittance.");
    } finally {
        btn.disabled = false;
    }
}

let currentPayslipPeriodId = null;

async function openPeriodPayslipSelector(periodId) {
    currentPayslipPeriodId = periodId;
    const modal = document.getElementById('modalPrintPayslipSelect');
    const infoBox = document.getElementById('printPayslipPeriodInfo');
    const select = document.getElementById('selectPayslipTarget');

    infoBox.innerHTML = '<div style="color: #64748b; font-size: 12px; font-weight: 600;">Loading period staff list...</div>';
    select.innerHTML = '<option value="all">Loading...</option>';
    modal.classList.add('active');

    try {
        const res = await fetch(`payroll_api.php?action=get_period&id=${periodId}`);
        const json = await res.json();
        if (!json.success || !json.period) {
            infoBox.innerHTML = '<div style="color: #ef4444; font-size: 12px;">Failed to load payroll period data.</div>';
            return;
        }

        const period = json.period;
        const entries = json.entries || [];

        infoBox.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                <span style="font-weight: 800; font-size: 13.5px; color: #0f172a;">${escapeHtml(period.period_code)} &bull; ${escapeHtml(period.period_name)}</span>
                <span class="status-pill status-${period.status}" style="font-size: 10px;"><span class="dot"></span>${period.status.toUpperCase()}</span>
            </div>
            <div style="font-size: 11.5px; color: #64748b;">
                Date Range: <strong>${period.start_date}</strong> to <strong>${period.end_date}</strong> &bull; <strong>${entries.length}</strong> Staff Included
            </div>
        `;

        let optionsHtml = `<option value="all">🖨️ Print All Payslips (${entries.length} Employees)</option>`;
        entries.forEach(e => {
            const netFormatted = parseFloat(e.net_salary || 0).toLocaleString('en-US', { minimumFractionDigits: 2 });
            optionsHtml += `<option value="${e.id}">[${escapeHtml(e.employee_code || '')}] ${escapeHtml(e.employee_name)} (Net: LKR ${netFormatted})</option>`;
        });

        select.innerHTML = optionsHtml;
        select.value = 'all';

        if (typeof CurtissSearchableSelect !== 'undefined') {
            CurtissSearchableSelect.attach(select);
        }
    } catch (err) {
        console.error("Error loading period payslips:", err);
        infoBox.innerHTML = '<div style="color: #ef4444; font-size: 12px;">Network error loading period employees.</div>';
    }
}

function submitPrintPayslips() {
    if (!currentPayslipPeriodId) return;
    const select = document.getElementById('selectPayslipTarget');
    const val = select ? select.value : 'all';

    let printUrl = '';
    if (!val || val === 'all') {
        printUrl = `payslip_receipt.php?period_id=${currentPayslipPeriodId}&all=1`;
    } else {
        printUrl = `payslip_receipt.php?period_id=${currentPayslipPeriodId}&entry_id=${encodeURIComponent(val)}`;
    }

    window.open(printUrl, '_blank', 'width=460,height=750,scrollbars=yes');
    closeModal('modalPrintPayslipSelect');
}

function quickPrintAllPayslips() {
    if (!currentPayslipPeriodId) return;
    const printUrl = `payslip_receipt.php?period_id=${currentPayslipPeriodId}&all=1`;
    window.open(printUrl, '_blank', 'width=460,height=750,scrollbars=yes');
    closeModal('modalPrintPayslipSelect');
}

function quickPrintAllAutoCut() {
    if (!currentPayslipPeriodId) return;
    const printUrl = `payslip_receipt.php?period_id=${currentPayslipPeriodId}&all=1&autocut=1`;
    window.open(printUrl, '_blank', 'width=460,height=750,scrollbars=yes');
    closeModal('modalPrintPayslipSelect');
}

function openPayslipModal(entryId) {
    window.open(`payslip_receipt.php?entry_id=${entryId}`, '_blank', 'width=460,height=750,scrollbars=yes');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
</script>
