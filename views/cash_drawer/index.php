<?php
/**
 * Curtiss POS - Cash Drawer & Business Day View
 * Inventory & Purchasing Master Theme Alignment
 */
?>
<link rel="stylesheet" href="assets/css/curtiss_select.css">

<!-- Floating Top-Right Action Pill -->
<div id="curtiss-floating-header-right" style="position: fixed; top: 16px; right: 20px; z-index: 1050; display: flex; align-items: center; gap: 8px;">
    <?php if ($activeDay): ?>
        <?php if (Auth::hasPermission('manual_cash_movement')): ?>
            <button type="button" class="inv-btn inv-btn-ghost" onclick="openManualCashModal('cash_in')" style="color: #15803d; border-color: rgba(34,197,94,0.3); background: rgba(240,253,244,0.92);">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                + Cash In
            </button>
            <button type="button" class="inv-btn inv-btn-ghost" onclick="openManualCashModal('cash_out')" style="color: #b91c1c; border-color: rgba(239,68,68,0.3); background: rgba(254,242,242,0.92);">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                - Cash Out
            </button>
        <?php endif; ?>
        <?php if (Auth::hasPermission('close_business_day')): ?>
            <button type="button" class="inv-btn inv-btn-danger" onclick="openCloseDayModal()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Close Business Day
            </button>
        <?php endif; ?>
    <?php else: ?>
        <?php if (Auth::hasPermission('start_business_day')): ?>
            <button type="button" class="inv-btn inv-btn-primary" onclick="focusStartDayForm()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                Start Business Day
            </button>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
/* ── Inventory Shared Theme Styles ── */
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

.inv-btn {
    display: inline-flex;
    align-items: center;
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
    background: rgba(255,255,255,0.9);
    border-color: rgba(226,232,240,0.9);
    color: #0f172a;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
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
    box-shadow: 0 4px 12px rgba(15,23,42,0.25);
}
.inv-btn-primary:hover {
    background: #1e293b;
    transform: translateY(-1px);
}
.inv-btn-danger {
    background: #dc2626;
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(220,38,38,0.25);
}
.inv-btn-danger:hover {
    background: #b91c1c;
    transform: translateY(-1px);
}

/* Glassmorphism Cards */
.inv-card {
    background: rgba(255, 255, 255, 0.94);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(226, 232, 240, 0.9);
    border-radius: 16px;
    padding: 1.25rem 1.5rem;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.03);
    margin-bottom: 1.5rem;
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

/* Reconciliation Rows */
.reconcile-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.65rem 0.75rem;
    border-bottom: 1px dashed rgba(226, 232, 240, 0.9);
    font-size: 0.9rem;
    border-radius: 8px;
    margin-bottom: 2px;
    transition: background 0.12s ease;
}
.reconcile-row:hover {
    background: rgba(248, 250, 252, 0.8);
}
.reconcile-row.subtotal-row {
    background: #f8fafc;
    font-weight: 700;
    border-bottom: 1px solid #e2e8f0;
    margin-top: 4px;
    margin-bottom: 8px;
}
.reconcile-row.total-row {
    background: linear-gradient(135deg, rgba(239, 246, 255, 0.95) 0%, rgba(255, 255, 255, 0.95) 100%);
    border: 1.5px solid #bfdbfe;
    font-weight: 800;
    font-size: 1.05rem;
    padding: 0.85rem 1rem;
    margin-top: 0.75rem;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.06);
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
.prod-badge-inactive { background: #fee2e2; color: #b91c1c; border: 1px solid rgba(220,38,38,0.2); }
.prod-badge .dot {
    width: 5px; height: 5px; border-radius: 50%; display: inline-block;
}
.prod-badge-active   .dot { background: #16a34a; }
.prod-badge-inactive .dot { background: #dc2626; }

/* Table Panels */
.prod-table-panel {
    background: rgba(255, 255, 255, 0.92);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(226, 232, 240, 0.85);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
    margin-bottom: 0.5rem;
    display: flex;
    flex-direction: column;
    flex: 1;
    min-height: 0;
}
.prod-table-scroll {
    overflow-x: auto;
    overflow-y: auto;
    flex: 1;
    min-height: 0;
}
.prod-table {
    width: 100%;
    border-collapse: collapse;
}
.prod-table thead {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}
.prod-table thead th {
    height: 48px;
    padding: 10px 14px;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #64748b;
    white-space: nowrap;
    position: sticky;
    top: 0;
    z-index: 10;
    background: #f8fafc;
    text-align: left;
    box-sizing: border-box;
}
.prod-table tbody tr {
    height: 48px;
    min-height: 48px;
    border-bottom: 1px solid #f1f5f9;
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
    text-align: left;
}
.prod-table th.col-right, .prod-table td.col-right {
    text-align: right !important;
}
.prod-table th.col-center, .prod-table td.col-center {
    text-align: center !important;
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
.prod-act-btn:disabled {
    opacity: 0.45;
    cursor: not-allowed;
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
.curtiss-modal-backdrop.active {
    display: flex;
}
.curtiss-modal-card {
    background: rgba(255, 255, 255, 0.98);
    width: 100%;
    max-width: 520px;
    max-height: calc(100vh - 32px);
    display: flex;
    flex-direction: column;
    border-radius: 18px;
    padding: 20px 24px;
    box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.15);
    border: 1px solid rgba(226, 232, 240, 0.9);
    animation: modalSlideUp 0.22s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    overflow: hidden;
}
@keyframes modalSlideUp {
    from { opacity: 0; transform: translateY(12px) scale(0.98); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}

.curtiss-modal-header {
    flex-shrink: 0;
    margin-bottom: 10px;
    padding-bottom: 10px;
    border-bottom: 1px solid #f1f5f9;
}
.curtiss-modal-body {
    flex: 1;
    overflow-y: auto;
    padding-right: 4px;
    min-height: 0;
}
.curtiss-modal-footer {
    flex-shrink: 0;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    padding-top: 14px;
    border-top: 1px solid #f1f5f9;
    background: #ffffff;
    margin-top: 8px;
}

.cd-close-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    align-items: start;
}
@media (max-width: 860px) {
    .cd-close-grid {
        grid-template-columns: 1fr !important;
        gap: 14px;
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

<div style="width: 100%; max-width: 100%; height: calc(100vh - 110px); display: flex; flex-direction: column; overflow: hidden; box-sizing: border-box; padding-bottom: 8px;">
    <!-- Page Header (Headline Only - Rule 5) -->
    <div class="inv-header" style="flex-shrink: 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 0.75rem;">
        <div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
                <h1 class="inv-title">Cash Drawer & Day Closing</h1>
            </div>
        </div>
        <div>
            <?php if ($activeDay): ?>
                <span class="prod-badge prod-badge-active" style="padding: 6px 14px; font-size: 12px;">
                    <span class="dot"></span> OPEN (Date: <?= htmlspecialchars($activeDay['business_date']) ?>)
                </span>
            <?php else: ?>
                <span class="prod-badge prod-badge-inactive" style="padding: 6px 14px; font-size: 12px;">
                    <span class="dot"></span> SYSTEM ERROR (No Day Active)
                </span>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!$activeDay): ?>
        <!-- Start Business Day Glassmorphic Card -->
        <div id="startDayContainer" class="inv-card" style="background: linear-gradient(135deg, rgba(239, 246, 255, 0.95) 0%, rgba(255, 255, 255, 0.95) 100%); border-color: #bfdbfe; margin-bottom: 2rem;">
            <div style="max-width: 640px;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 0.5rem;">
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: #dbeafe; color: #1e40af; display: flex; align-items: center; justify-content: center; font-size: 16px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
                    </div>
                    <div>
                        <h2 style="color: #1e40af; font-weight: 800; font-size: 1.25rem; margin: 0;">Start New Business Day</h2>
                        <p style="color: #475569; font-size: 0.85rem; margin-top: 2px;">No active business day is currently open for this branch. Please confirm opening float before posting transactions.</p>
                    </div>
                </div>

                <?php if (Auth::hasPermission('start_business_day')): ?>
                <form id="startDayForm" onsubmit="handleStartDay(event)" style="margin-top: 1.25rem;">
                    <div style="margin-bottom: 1rem;">
                        <label class="form-label">Opening Cash Float (LKR) <span style="color: #ef4444;">*</span></label>
                        <input type="number" step="0.01" min="0" id="startOpeningCash" class="form-input" value="20000.00" required style="font-size: 1.25rem; font-weight: 800; color: #0f172a; padding: 10px 14px;">
                        <small style="color: #64748b; font-size: 11.5px; display: block; margin-top: 4px;">Default retained cash float is LKR 20,000.00</small>
                    </div>
                    <div style="margin-bottom: 1.25rem;">
                        <label class="form-label">Opening Notes (Optional)</label>
                        <input type="text" id="startNotes" class="form-input" placeholder="e.g. Morning float verified by cashier">
                    </div>
                    <button type="submit" class="inv-btn inv-btn-primary" style="padding: 10px 24px; font-size: 13px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                        Start Business Day
                    </button>
                </form>
                <?php else: ?>
                    <p style="color: #dc2626; font-weight: 600; margin-top: 1rem;">You do not have permission to start the business day. Contact manager.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <?php
            $cSummary       = $comprehensiveSummary ?? [];
            $salesSummary   = $cSummary['sales_summary'] ?? [];
            $custColls      = $cSummary['customer_collections'] ?? [];
            $suppPayments   = $cSummary['supplier_payments'] ?? [];
            $empAdvances    = $cSummary['employee_advances'] ?? [];
            $pettyExpenses  = $cSummary['petty_cash_expenses'] ?? [];
            $payrollPays    = $cSummary['payroll_payments'] ?? [];
            $refundsData    = $cSummary['refunds'] ?? [];
            $manualMovs     = $cSummary['manual_movements'] ?? [];
            $sum            = $cSummary['drawer_summary'] ?? ($activeSummary['summary'] ?? []);

            $totalOrders    = (int)($salesSummary['total_sales_count'] ?? 0);
            $totalItemsSold = (float)($salesSummary['total_items_sold'] ?? 0);
            $grossSales     = (float)($salesSummary['gross_sales'] ?? 0);
            $totalDisc      = (float)($salesSummary['total_discount'] ?? 0);
            $netSales       = (float)($salesSummary['net_sales'] ?? 0);
            $totalCogs      = (float)($salesSummary['total_cogs'] ?? 0);
            $grossProfit    = (float)($salesSummary['gross_profit'] ?? 0);
            $grossMarginPct = (float)($salesSummary['gross_margin_pct'] ?? 0);

            $cashSales      = (float)($salesSummary['cash_sales'] ?? 0);
            $cardSales      = (float)($salesSummary['card_sales'] ?? 0);
            $creditSales    = (float)($salesSummary['credit_sales'] ?? 0);
            $bankSales      = (float)($salesSummary['bank_sales'] ?? 0);

            $totCollections = (float)($custColls['total'] ?? 0);
            $collItems      = $custColls['items'] ?? [];

            $totSupp        = (float)($suppPayments['total'] ?? 0);
            $cashSupp       = (float)($suppPayments['cash_amount'] ?? 0);
            $bankSupp       = (float)($suppPayments['bank_amount'] ?? 0);
            $suppItems      = $suppPayments['items'] ?? [];

            $totEmpAdv      = (float)($empAdvances['total'] ?? 0);
            $cashEmpAdv     = (float)($empAdvances['cash_amount'] ?? 0);
            $bankEmpAdv     = (float)($empAdvances['bank_amount'] ?? 0);
            $empItems       = $empAdvances['items'] ?? [];

            $totPetty       = (float)($pettyExpenses['total'] ?? 0);
            $pettyItems     = $pettyExpenses['items'] ?? [];

            $totPayroll     = (float)($payrollPays['total'] ?? 0);
            $cashPayroll    = (float)($payrollPays['cash_amount'] ?? 0);
            $bankPayroll    = (float)($payrollPays['bank_amount'] ?? 0);
            $payrollItems   = $payrollPays['items'] ?? [];

            $totRefunds     = (float)($refundsData['total'] ?? 0);
            $cashRefunds    = (float)($refundsData['cash_amount'] ?? 0);
            $refundItems    = $refundsData['items'] ?? [];

            $totManualIn    = (float)($manualMovs['total_in'] ?? 0);
            $totManualOut   = (float)($manualMovs['total_out'] ?? 0);
        ?>

        <!-- Tab Navigation System (2 Tabs: Live Preview vs Today's Movements) -->
        <div class="cd-tabs-nav" style="flex-shrink: 0; margin-bottom: 0.65rem;">
            <button type="button" class="cd-tab-btn active" id="tabBtnPreview" onclick="switchCdTab('preview')">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
                Current Cash Drawer & Day End Live Preview
                <span class="prod-badge prod-badge-active" style="padding: 1px 8px; font-size: 10px; margin-left: 6px;"><span class="dot"></span>Live Shift</span>
            </button>
            <button type="button" class="cd-tab-btn" id="tabBtnMovements" onclick="switchCdTab('movements')">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                Today's Cash Drawer Movement History
                <span class="cd-tab-badge" id="movCountBadge"><?= count($movements) ?></span>
            </button>
        </div>

        <!-- TAB 1: Live Day End Report Preview -->
        <div id="tabContentPreview" class="cd-tab-pane" style="flex: 1; min-height: 0; display: flex; flex-direction: column; overflow-y: auto; padding-right: 4px; gap: 12px;">
            <!-- Top Live Session Banner -->
            <div class="inv-card" style="flex-shrink: 0; padding: 12px 18px; margin-bottom: 0; border-radius: 14px;">
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 32px; height: 32px; border-radius: 9px; background: #0f172a; color: #fff; display: flex; align-items: center; justify-content: center;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        </div>
                        <div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <h3 style="font-size: 1rem; font-weight: 800; color: #0f172a; margin: 0;">Live Day End Financial &amp; Reconciliation Summary</h3>
                                <span class="prod-badge prod-badge-active" style="font-size: 10.5px;"><span class="dot"></span>ACTIVE LIVE SHIFT</span>
                            </div>
                            <div style="font-size: 11.5px; color: #64748b; margin-top: 2px;">
                                Day Record: <strong style="color: #0f172a;">#<?= (int)$activeDay['id'] ?></strong> &bull; Date: <strong style="color: #0f172a;"><?= htmlspecialchars($activeDay['business_date']) ?></strong> &bull; Opener: <strong style="color: #0f172a;"><?= htmlspecialchars($activeDay['opener_name']) ?></strong> (<?= htmlspecialchars($activeDay['opened_at']) ?>) &bull; Float: <strong style="color: #0f172a;">LKR <?= number_format((float)$activeDay['opening_cash'], 2) ?></strong>
                            </div>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <button type="button" onclick="printDayClosingReceipt(<?= (int)$activeDay['id'] ?>)" class="inv-btn inv-btn-ghost" style="color: #0f172a; border-color: #cbd5e1; background: #ffffff; box-shadow: 0 1px 4px rgba(0,0,0,0.05);" title="Open print-ready 88mm thermal day end receipt">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                            🖨️ Print 88mm Live Receipt
                        </button>
                    </div>
                </div>
            </div>

            <!-- 4 Top Financial KPI Cards -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 12px; flex-shrink: 0;">
                <!-- Net Sales -->
                <div style="background: linear-gradient(135deg, rgba(255, 255, 255, 0.96) 0%, rgba(248, 250, 252, 0.96) 100%); border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 12px 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.02);">
                    <div style="font-size: 10.5px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.06em;">Net Sales Turnover</div>
                    <div style="font-size: 19px; font-weight: 800; color: #0f172a; margin-top: 4px; font-family: monospace;">LKR <?= number_format($netSales, 2) ?></div>
                    <div style="font-size: 11px; color: #64748b; margin-top: 3px;"><strong><?= $totalOrders ?></strong> Completed Sales &bull; <strong><?= number_format($totalItemsSold, 2) ?></strong> Units</div>
                </div>
                <!-- Gross Profit & COGS -->
                <div style="background: linear-gradient(135deg, rgba(240, 253, 244, 0.8) 0%, rgba(255, 255, 255, 0.96) 100%); border: 1.5px solid #bbf7d0; border-radius: 12px; padding: 12px 16px; box-shadow: 0 2px 8px rgba(22,163,74,0.04);">
                    <div style="font-size: 10.5px; font-weight: 800; color: #166534; text-transform: uppercase; letter-spacing: 0.06em;">Gross Profit (COGS)</div>
                    <div style="font-size: 19px; font-weight: 800; color: #15803d; margin-top: 4px; font-family: monospace;">LKR <?= number_format($grossProfit, 2) ?></div>
                    <div style="font-size: 11px; color: #166534; margin-top: 3px;"><strong><?= number_format($grossMarginPct, 2) ?>%</strong> Margin &bull; COGS: LKR <?= number_format($totalCogs, 2) ?></div>
                </div>
                <!-- Expected Cash in Drawer -->
                <div style="background: linear-gradient(135deg, rgba(239, 246, 255, 0.85) 0%, rgba(255, 255, 255, 0.96) 100%); border: 1.5px solid #bfdbfe; border-radius: 12px; padding: 12px 16px; box-shadow: 0 2px 8px rgba(37,99,235,0.04);">
                    <div style="font-size: 10.5px; font-weight: 800; color: #1e40af; text-transform: uppercase; letter-spacing: 0.06em;">Expected Cash in Drawer</div>
                    <div style="font-size: 19px; font-weight: 800; color: #1d4ed8; margin-top: 4px; font-family: monospace;">LKR <?= number_format($sum['expected_cash'] ?? 0, 2) ?></div>
                    <div style="font-size: 11px; color: #1e40af; margin-top: 3px;">Safe Transfer: <strong>LKR <?= number_format(max(0, ($sum['expected_cash'] ?? 0) - (float)($activeDay['opening_cash'] ?? 20000)), 2) ?></strong></div>
                </div>
                <!-- Expected Card Sales -->
                <div style="background: linear-gradient(135deg, rgba(254, 249, 195, 0.8) 0%, rgba(255, 255, 255, 0.96) 100%); border: 1.5px solid #fde047; border-radius: 12px; padding: 12px 16px; box-shadow: 0 2px 8px rgba(202,138,4,0.04);">
                    <div style="font-size: 10.5px; font-weight: 800; color: #854d0e; text-transform: uppercase; letter-spacing: 0.06em;">System Expected Card</div>
                    <div style="font-size: 19px; font-weight: 800; color: #a16207; margin-top: 4px; font-family: monospace;">LKR <?= number_format($sum['expected_card_sales'] ?? $cardSales, 2) ?></div>
                    <div style="font-size: 11px; color: #854d0e; margin-top: 3px;">Card Terminal Settlement Target</div>
                </div>
            </div>

            <!-- 2-Column Detailed Report Content Grid -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                <!-- LEFT COLUMN: Sales Revenue, Profitability, Collections & Refunds -->
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <!-- 1. Daily Sales & Profitability -->
                    <div class="inv-card" style="padding: 14px 18px; margin-bottom: 0; border-radius: 14px;">
                        <div style="font-size: 11.5px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid #f1f5f9; padding-bottom: 6px; margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between;">
                            <span>1. Daily Sales &amp; Profitability</span>
                            <span style="font-size: 11px; color: #64748b; font-weight: 600;"><?= $totalOrders ?> Invoices</span>
                        </div>
                        <div class="reconcile-row" style="padding: 4px 6px; font-size: 12px;">
                            <span>Gross Sales:</span>
                            <strong style="color: #0f172a;">LKR <?= number_format($grossSales, 2) ?></strong>
                        </div>
                        <?php if ($totalDisc > 0): ?>
                        <div class="reconcile-row" style="padding: 4px 6px; font-size: 12px; color: #dc2626;">
                            <span>- Discounts Allowed:</span>
                            <strong>LKR <?= number_format($totalDisc, 2) ?></strong>
                        </div>
                        <?php endif; ?>
                        <div class="reconcile-row" style="padding: 5px 6px; font-size: 12.5px; font-weight: 800; background: #f8fafc; border-top: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; margin: 3px 0;">
                            <span>NET SALES TURNOVER:</span>
                            <strong style="color: #0f172a;">LKR <?= number_format($netSales, 2) ?></strong>
                        </div>
                        <div class="reconcile-row" style="padding: 4px 6px; font-size: 12px;">
                            <span>Cost of Goods Sold (COGS):</span>
                            <strong style="color: #475569;">LKR <?= number_format($totalCogs, 2) ?></strong>
                        </div>
                        <div class="reconcile-row" style="padding: 4px 6px; font-size: 12px;">
                            <span style="color: #15803d; font-weight: 700;">Gross Profit:</span>
                            <strong style="color: #15803d; font-weight: 800;"><?= $grossProfit < 0 ? '-' : '' ?>LKR <?= number_format(abs($grossProfit), 2) ?></strong>
                        </div>
                        <div class="reconcile-row" style="padding: 4px 6px; font-size: 12px;">
                            <span>Gross Profit Margin:</span>
                            <strong style="color: #15803d;"><?= number_format($grossMarginPct, 2) ?>%</strong>
                        </div>
                    </div>

                    <!-- 2. Sales Revenue by Payment Channel -->
                    <div class="inv-card" style="padding: 14px 18px; margin-bottom: 0; border-radius: 14px;">
                        <div style="font-size: 11.5px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid #f1f5f9; padding-bottom: 6px; margin-bottom: 8px;">
                            2. Sales Revenue by Method
                        </div>
                        <div class="reconcile-row" style="padding: 4px 6px; font-size: 12px;">
                            <span>Cash Sales (POS Register):</span>
                            <strong style="color: #15803d;">LKR <?= number_format($cashSales, 2) ?></strong>
                        </div>
                        <div class="reconcile-row" style="padding: 4px 6px; font-size: 12px;">
                            <span>Card Sales (Terminal):</span>
                            <strong style="color: #854d0e;">LKR <?= number_format($cardSales, 2) ?></strong>
                        </div>
                        <div class="reconcile-row" style="padding: 4px 6px; font-size: 12px;">
                            <span>Customer Credit (On Account):</span>
                            <strong style="color: #2563eb;">LKR <?= number_format($creditSales, 2) ?></strong>
                        </div>
                        <div class="reconcile-row" style="padding: 4px 6px; font-size: 12px;">
                            <span>Bank / Direct Transfer Sales:</span>
                            <strong style="color: #6b21a8;">LKR <?= number_format($bankSales, 2) ?></strong>
                        </div>
                    </div>

                    <!-- 3. Customer Credit Collections (Inflows) -->
                    <div class="inv-card" style="padding: 14px 18px; margin-bottom: 0; border-radius: 14px;">
                        <div style="font-size: 11.5px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid #f1f5f9; padding-bottom: 6px; margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between;">
                            <span>3. Customer Credit Collections</span>
                            <strong style="color: #15803d;">+ LKR <?= number_format($totCollections, 2) ?></strong>
                        </div>
                        <?php if (empty($collItems)): ?>
                            <div style="font-size: 11.5px; color: #94a3b8; font-style: italic; padding: 4px 6px;">No customer credit collections recorded today.</div>
                        <?php else: ?>
                            <div style="max-height: 160px; overflow-y: auto;">
                                <?php foreach ($collItems as $ci): ?>
                                <div class="reconcile-row" style="padding: 4px 6px; font-size: 11.5px;">
                                    <span>&bull; <?= htmlspecialchars($ci['customer_name'] ?? 'Customer') ?> <small style="color: #64748b;">(<?= htmlspecialchars($ci['reference_no'] ?? 'PMT') ?>)</small></span>
                                    <strong style="color: #15803d;">LKR <?= number_format((float)$ci['amount_paid'], 2) ?></strong>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- 4. Customer Refunds & Sales Returns (Outflows) -->
                    <div class="inv-card" style="padding: 14px 18px; margin-bottom: 0; border-radius: 14px;">
                        <div style="font-size: 11.5px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid #f1f5f9; padding-bottom: 6px; margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between;">
                            <span>4. Sales Returns &amp; Customer Refunds</span>
                            <strong style="color: #dc2626;">- LKR <?= number_format($totRefunds, 2) ?></strong>
                        </div>
                        <?php if (empty($refundItems)): ?>
                            <div style="font-size: 11.5px; color: #94a3b8; font-style: italic; padding: 4px 6px;">No customer refunds processed today.</div>
                        <?php else: ?>
                            <div style="max-height: 140px; overflow-y: auto;">
                                <?php foreach ($refundItems as $ri): ?>
                                <div class="reconcile-row" style="padding: 4px 6px; font-size: 11.5px;">
                                    <span>&bull; <?= htmlspecialchars($ri['customer_name'] ?? 'Customer') ?> <small style="color: #64748b;">(<?= htmlspecialchars($ri['refund_method'] ?? 'Cash') ?>)</small></span>
                                    <strong style="color: #dc2626;">LKR <?= number_format((float)$ri['amount'], 2) ?></strong>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- RIGHT COLUMN: Cash Drawer Reconciliation & Operational Disbursements -->
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <!-- 5. Active Cash Drawer Reconciliation -->
                    <div class="inv-card" style="padding: 14px 18px; margin-bottom: 0; border-radius: 14px;">
                        <div style="font-size: 11.5px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid #f1f5f9; padding-bottom: 6px; margin-bottom: 8px;">
                            5. Active Cash Drawer Reconciliation
                        </div>
                        <!-- 2 Sub-Columns: Inflow vs Outflow -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 10px;">
                            <!-- Inflows -->
                            <div style="background: rgba(240, 253, 244, 0.5); border: 1px solid rgba(187, 247, 208, 0.8); border-radius: 10px; padding: 8px 10px;">
                                <div style="font-size: 10px; font-weight: 800; color: #15803d; text-transform: uppercase; margin-bottom: 4px; display: flex; justify-content: space-between;">
                                    <span>Inflows (+)</span>
                                    <span>Amount (LKR)</span>
                                </div>
                                <div class="reconcile-row" style="padding: 2px 0; font-size: 11.5px;">
                                    <span style="color: #334155;">Initial Opening Float</span>
                                    <strong><?= number_format($sum['opening_cash'] ?? 0, 2) ?></strong>
                                </div>
                                <div class="reconcile-row" style="padding: 2px 0; font-size: 11.5px;">
                                    <span style="color: #16a34a;">+ POS Cash Sales</span>
                                    <strong style="color: #16a34a;"><?= number_format($sum['pos_cash_sales'] ?? 0, 2) ?></strong>
                                </div>
                                <div class="reconcile-row" style="padding: 2px 0; font-size: 11.5px;">
                                    <span style="color: #16a34a;">+ Credit Collections</span>
                                    <strong style="color: #16a34a;"><?= number_format($sum['customer_cash_collections'] ?? 0, 2) ?></strong>
                                </div>
                                <div class="reconcile-row" style="padding: 2px 0; font-size: 11.5px;">
                                    <span style="color: #16a34a;">+ Manual Cash In</span>
                                    <strong style="color: #16a34a;"><?= number_format($sum['manual_cash_in'] ?? 0, 2) ?></strong>
                                </div>
                                <div class="reconcile-row subtotal-row" style="padding: 4px 6px; font-size: 12px; margin-top: 4px; margin-bottom: 0;">
                                    <span>Total Cash In</span>
                                    <span style="color: #15803d; font-weight: 800;"><?= number_format($sum['total_cash_in'] ?? 0, 2) ?></span>
                                </div>
                            </div>

                            <!-- Outflows -->
                            <div style="background: rgba(254, 242, 242, 0.5); border: 1px solid rgba(254, 202, 202, 0.8); border-radius: 10px; padding: 8px 10px;">
                                <div style="font-size: 10px; font-weight: 800; color: #b91c1c; text-transform: uppercase; margin-bottom: 4px; display: flex; justify-content: space-between;">
                                    <span>Outflows (-)</span>
                                    <span>Amount (LKR)</span>
                                </div>
                                <div class="reconcile-row" style="padding: 2px 0; font-size: 11.5px;">
                                    <span style="color: #dc2626;">- Cash Refunds</span>
                                    <strong style="color: #dc2626;"><?= number_format($sum['cash_refunds'] ?? 0, 2) ?></strong>
                                </div>
                                <div class="reconcile-row" style="padding: 2px 0; font-size: 11.5px;">
                                    <span style="color: #dc2626;">- Supplier Payments</span>
                                    <strong style="color: #dc2626;"><?= number_format($sum['supplier_cash_payments'] ?? 0, 2) ?></strong>
                                </div>
                                <div class="reconcile-row" style="padding: 2px 0; font-size: 11.5px;">
                                    <span style="color: #dc2626;">- Advances &amp; Salaries</span>
                                    <strong style="color: #dc2626;"><?= number_format(($sum['employee_advances'] ?? 0) + ($sum['salary_payments'] ?? 0), 2) ?></strong>
                                </div>
                                <div class="reconcile-row" style="padding: 2px 0; font-size: 11.5px;">
                                    <span style="color: #dc2626;">- Petty Expenses</span>
                                    <strong style="color: #dc2626;"><?= number_format($sum['petty_cash_reimbursements'] ?? 0, 2) ?></strong>
                                </div>
                                <div class="reconcile-row" style="padding: 2px 0; font-size: 11.5px;">
                                    <span style="color: #dc2626;">- Manual Cash Out</span>
                                    <strong style="color: #dc2626;"><?= number_format($sum['manual_cash_out'] ?? 0, 2) ?></strong>
                                </div>
                                <div class="reconcile-row subtotal-row" style="padding: 4px 6px; font-size: 12px; margin-top: 4px; margin-bottom: 0;">
                                    <span>Total Cash Out</span>
                                    <span style="color: #b91c1c; font-weight: 800;"><?= number_format($sum['total_cash_out'] ?? 0, 2) ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Expected Cash & Safe Transfer -->
                        <div style="background: linear-gradient(135deg, rgba(239, 246, 255, 0.95) 0%, rgba(255, 255, 255, 0.95) 100%); border: 1.5px solid #bfdbfe; border-radius: 10px; padding: 8px 14px; display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 12px; font-weight: 700; color: #1e40af;">Live Expected Cash in Drawer:</span>
                            <strong style="font-size: 15px; font-weight: 800; color: #1d4ed8; font-family: monospace;">LKR <?= number_format($sum['expected_cash'] ?? 0, 2) ?></strong>
                        </div>
                    </div>

                    <!-- 6. Supplier Payments & Advance Payments -->
                    <div class="inv-card" style="padding: 14px 18px; margin-bottom: 0; border-radius: 14px;">
                        <div style="font-size: 11.5px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid #f1f5f9; padding-bottom: 6px; margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between;">
                            <span>6. Supplier Payments &amp; Advances</span>
                            <strong style="color: #dc2626;">- LKR <?= number_format($totSupp, 2) ?></strong>
                        </div>
                        <?php if (empty($suppItems)): ?>
                            <div style="font-size: 11.5px; color: #94a3b8; font-style: italic; padding: 4px 6px;">No supplier payments made today.</div>
                        <?php else: ?>
                            <div style="max-height: 150px; overflow-y: auto;">
                                <?php foreach ($suppItems as $si): ?>
                                <div class="reconcile-row" style="padding: 4px 6px; font-size: 11.5px;">
                                    <span>&bull; <?= htmlspecialchars($si['supplier_name'] ?? 'Supplier') ?> <small style="color: #64748b;">(<?= htmlspecialchars($si['payment_method']) ?> &bull; <?= htmlspecialchars($si['payment_no'] ?? '') ?>)</small></span>
                                    <strong style="color: #dc2626;">LKR <?= number_format((float)$si['total_amount'], 2) ?></strong>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- 7. Petty Cash Operating Expenses -->
                    <div class="inv-card" style="padding: 14px 18px; margin-bottom: 0; border-radius: 14px;">
                        <div style="font-size: 11.5px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid #f1f5f9; padding-bottom: 6px; margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between;">
                            <span>7. Petty Cash / Operating Expenses</span>
                            <strong style="color: #dc2626;">- LKR <?= number_format($totPetty, 2) ?></strong>
                        </div>
                        <?php if (empty($pettyItems)): ?>
                            <div style="font-size: 11.5px; color: #94a3b8; font-style: italic; padding: 4px 6px;">No petty cash expenses posted today.</div>
                        <?php else: ?>
                            <div style="max-height: 150px; overflow-y: auto;">
                                <?php foreach ($pettyItems as $pi): ?>
                                <div class="reconcile-row" style="padding: 4px 6px; font-size: 11.5px;">
                                    <span>&bull; <?= htmlspecialchars($pi['category_name']) ?><?= !empty($pi['payee']) ? ' (' . htmlspecialchars($pi['payee']) . ')' : '' ?> <small style="color: #64748b;">[<?= htmlspecialchars($pi['voucher_no']) ?>]</small></span>
                                    <strong style="color: #dc2626;">LKR <?= number_format((float)$pi['amount'], 2) ?></strong>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- 8. Employee Advances & Payroll Salaries -->
                    <div class="inv-card" style="padding: 14px 18px; margin-bottom: 0; border-radius: 14px;">
                        <div style="font-size: 11.5px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid #f1f5f9; padding-bottom: 6px; margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between;">
                            <span>8. Employee Advances &amp; Payroll</span>
                            <strong style="color: #dc2626;">- LKR <?= number_format($totEmpAdv + $totPayroll, 2) ?></strong>
                        </div>
                        <?php if (empty($empItems) && empty($payrollItems)): ?>
                            <div style="font-size: 11.5px; color: #94a3b8; font-style: italic; padding: 4px 6px;">No employee advances or salary disbursements recorded today.</div>
                        <?php else: ?>
                            <div style="max-height: 140px; overflow-y: auto;">
                                <?php foreach ($empItems as $ei): ?>
                                <div class="reconcile-row" style="padding: 4px 6px; font-size: 11.5px;">
                                    <span>&bull; [Advance] <?= htmlspecialchars($ei['employee_name'] ?? 'Employee') ?> <small style="color: #64748b;">(<?= htmlspecialchars($ei['payment_method']) ?>)</small></span>
                                    <strong style="color: #dc2626;">LKR <?= number_format((float)$ei['amount'], 2) ?></strong>
                                </div>
                                <?php endforeach; ?>
                                <?php foreach ($payrollItems as $ppi): ?>
                                <div class="reconcile-row" style="padding: 4px 6px; font-size: 11.5px;">
                                    <span>&bull; [Salary] <?= htmlspecialchars($ppi['employee_name'] ?? 'Employee') ?> <small style="color: #64748b;">(<?= htmlspecialchars($ppi['payment_method']) ?>)</small></span>
                                    <strong style="color: #dc2626;">LKR <?= number_format((float)$ppi['amount'], 2) ?></strong>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: Today's Movements -->
        <div id="tabContentMovements" class="cd-tab-pane" style="flex: 1; min-height: 0; display: none; flex-direction: column;">
            <div class="prod-table-panel">
                <div style="padding: 10px 18px; border-bottom: 1px solid #e2e8f0; background: rgba(248, 250, 252, 0.8); display: flex; align-items: center; justify-content: space-between; flex-shrink: 0;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#0f172a" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <h3 style="font-size: 0.95rem; font-weight: 800; color: #0f172a; margin: 0;">Today's Cash Drawer Movement History</h3>
                    </div>
                </div>
                <div class="prod-table-scroll">
                    <table class="prod-table" id="movementsTable">
                        <thead>
                            <tr>
                                <th style="width: 70px;">ID</th>
                                <th style="width: 160px;">Timestamp</th>
                                <th>Movement Type</th>
                                <th style="width: 120px;" class="col-center">Direction</th>
                                <th style="width: 140px;" class="col-right">Amount (LKR)</th>
                                <th>Reason / Reference</th>
                                <th style="width: 140px;">User</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($movements)): ?>
                                <tr class="prod-empty"><td colspan="7" style="text-align: center; color: #94a3b8; padding: 32px 16px;">No drawer movements recorded yet today.</td></tr>
                            <?php else: ?>
                                <?php foreach ($movements as $m): ?>
                                <tr>
                                    <td><strong style="color: #64748b; font-size: 11.5px;">#<?= (int)$m['id'] ?></strong></td>
                                    <td style="color: #64748b; font-size: 12px; font-weight: 500;"><?= htmlspecialchars($m['created_at']) ?></td>
                                    <td><strong style="text-transform: capitalize; color: #0f172a;"><?= str_replace('_', ' ', htmlspecialchars($m['movement_type'])) ?></strong></td>
                                    <td class="col-center">
                                        <?php if ($m['direction'] === 'in'): ?>
                                            <span class="prod-badge prod-badge-active"><span class="dot"></span>IN ▲</span>
                                        <?php else: ?>
                                            <span class="prod-badge prod-badge-inactive"><span class="dot"></span>OUT ▼</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="col-right" style="font-weight: 800; color: #0f172a;"><?= number_format((float)$m['amount'], 2) ?></td>
                                    <td style="color: #475569; font-size: 12px;"><?= htmlspecialchars($m['reason'] ?? '-') ?></td>
                                    <td style="font-weight: 600; color: #334155;"><?= htmlspecialchars($m['user_name']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Movements Table Pagination Footer -->
                <div class="prod-table-footer" style="flex-shrink: 0;">
                    <div style="font-size: 12px; font-weight: 600; color: #64748b;" id="movPagInfo">
                        Showing <span id="movPagStart">1</span> to <span id="movPagEnd">25</span> of <span id="movPagTotal">0</span> entries
                    </div>
                    <div style="display: flex; align-items: center; gap: 14px;">
                        <div style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: #64748b; font-weight: 600;">
                            <span>Rows:</span>
                            <select id="movPagSize" onchange="changeMovPageSize()" style="height: 30px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 8px; font-size: 12px; color: #0f172a; background: #fff; outline: none; font-weight: 600; cursor: pointer;">
                                <option value="25" selected>25</option>
                                <option value="50">50</option>
                                <option value="75">75</option>
                                <option value="100">100</option>
                                <option value="500">500</option>
                            </select>
                        </div>
                        <div style="display: flex; align-items: center; gap: 4px;" id="movPagNavBtns"></div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>


<!-- Manual Cash In / Cash Out Modal -->
<div class="curtiss-modal-backdrop" id="manualCashModal">
    <div class="curtiss-modal-card">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; padding-bottom: 10px; border-bottom: 1px solid #f1f5f9;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
                <h3 id="manualModalTitle" style="font-size: 1.1rem; font-weight: 800; color: #0f172a; margin: 0;">Manual Cash Movement</h3>
            </div>
            <button type="button" onclick="closeModal('manualCashModal')" style="background: none; border: none; color: #94a3b8; cursor: pointer; padding: 4px; display: flex; align-items: center; justify-content: center;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form id="manualCashForm" onsubmit="handleManualCash(event)">
            <input type="hidden" id="manualType" value="cash_in">
            <div style="margin-bottom: 16px;">
                <label class="form-label">Amount (LKR) <span style="color: #ef4444;">*</span></label>
                <input type="number" step="0.01" min="0.01" id="manualAmount" class="form-input" required style="font-size: 1.2rem; font-weight: 800; padding: 10px 12px;">
            </div>
            <div style="margin-bottom: 16px;">
                <label class="form-label">Reason <span style="color: #ef4444;">*</span></label>
                <input type="text" id="manualReason" class="form-input" required placeholder="e.g. Bank cash float replenishment">
            </div>
            <div style="margin-bottom: 20px;">
                <label class="form-label">Notes / Reference (Optional)</label>
                <textarea id="manualNotes" class="form-input" rows="2" placeholder="Optional notes..."></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 14px; border-top: 1px solid #f1f5f9;">
                <button type="button" class="inv-btn inv-btn-ghost" onclick="closeModal('manualCashModal')">Cancel</button>
                <button type="submit" class="inv-btn inv-btn-primary">Save Cash Movement</button>
            </div>
        </form>
    </div>
</div>

<!-- Day Closing Modal -->
<?php if ($activeDay): ?>
<div class="curtiss-modal-backdrop" id="closeDayModal">
    <div class="curtiss-modal-card" style="max-width: 940px; width: 95%;">
        <!-- Header (Fixed) -->
        <div class="curtiss-modal-header">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div style="width: 8px; height: 8px; border-radius: 50%; background: #dc2626;"></div>
                    <h3 style="font-size: 1.15rem; font-weight: 800; color: #0f172a; margin: 0;">Close Business Day</h3>
                </div>
                <button type="button" onclick="closeModal('closeDayModal')" style="background: none; border: none; color: #94a3b8; cursor: pointer; padding: 4px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <p style="color: #64748b; font-size: 0.85rem; margin: 4px 0 0 0;">Verify physical cash drawer count and perform end-of-day reconciliation.</p>
        </div>
        
        <form id="closeDayForm" onsubmit="handleCloseDay(event)" style="display: flex; flex-direction: column; flex: 1; min-height: 0; overflow: hidden;">
            <input type="hidden" id="closeBDayId" value="<?= (int)$activeDay['id'] ?>">
            <input type="hidden" id="rawExpectedCash" value="<?= (float)($sum['expected_cash'] ?? 0) ?>">

            <!-- Body (Scrollable) -->
            <div class="curtiss-modal-body">
                <div class="cd-close-grid" style="padding-top: 4px;">
                    <!-- LEFT COLUMN: Cash Denomination Counter -->
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 14px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid #cbd5e1;">
                            <div>
                                <label class="form-label" style="margin: 0; color: #0f172a; font-size: 12px;">CASH DENOMINATION COUNTER</label>
                                <span style="font-size: 11px; color: #64748b; font-weight: 500;">Counts auto-sum physical cash</span>
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr; gap: 8px;">
                            <!-- 5000 -->
                            <div style="display: flex; align-items: center; justify-content: space-between; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 10px;">
                                <span style="font-size: 12px; font-weight: 700; color: #0f172a; width: 75px;">5,000 x</span>
                                <input type="number" min="0" id="denom_5000" class="form-input" placeholder="0" oninput="calcDenoms()" style="width: 75px; text-align: center; padding: 4px 6px; font-weight: 700;">
                                <span style="font-size: 11.5px; font-weight: 700; color: #2563eb; width: 85px; text-align: right;" id="sub_5000">0.00</span>
                            </div>
                            <!-- 2000 -->
                            <div style="display: flex; align-items: center; justify-content: space-between; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 10px;">
                                <span style="font-size: 12px; font-weight: 700; color: #0f172a; width: 75px;">2,000 x</span>
                                <input type="number" min="0" id="denom_2000" class="form-input" placeholder="0" oninput="calcDenoms()" style="width: 75px; text-align: center; padding: 4px 6px; font-weight: 700;">
                                <span style="font-size: 11.5px; font-weight: 700; color: #2563eb; width: 85px; text-align: right;" id="sub_2000">0.00</span>
                            </div>
                            <!-- 1000 -->
                            <div style="display: flex; align-items: center; justify-content: space-between; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 10px;">
                                <span style="font-size: 12px; font-weight: 700; color: #0f172a; width: 75px;">1,000 x</span>
                                <input type="number" min="0" id="denom_1000" class="form-input" placeholder="0" oninput="calcDenoms()" style="width: 75px; text-align: center; padding: 4px 6px; font-weight: 700;">
                                <span style="font-size: 11.5px; font-weight: 700; color: #2563eb; width: 85px; text-align: right;" id="sub_1000">0.00</span>
                            </div>
                            <!-- 500 -->
                            <div style="display: flex; align-items: center; justify-content: space-between; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 10px;">
                                <span style="font-size: 12px; font-weight: 700; color: #0f172a; width: 75px;">500 x</span>
                                <input type="number" min="0" id="denom_500" class="form-input" placeholder="0" oninput="calcDenoms()" style="width: 75px; text-align: center; padding: 4px 6px; font-weight: 700;">
                                <span style="font-size: 11.5px; font-weight: 700; color: #2563eb; width: 85px; text-align: right;" id="sub_500">0.00</span>
                            </div>
                            <!-- 100 -->
                            <div style="display: flex; align-items: center; justify-content: space-between; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 10px;">
                                <span style="font-size: 12px; font-weight: 700; color: #0f172a; width: 75px;">100 x</span>
                                <input type="number" min="0" id="denom_100" class="form-input" placeholder="0" oninput="calcDenoms()" style="width: 75px; text-align: center; padding: 4px 6px; font-weight: 700;">
                                <span style="font-size: 11.5px; font-weight: 700; color: #2563eb; width: 85px; text-align: right;" id="sub_100">0.00</span>
                            </div>
                            <!-- 50 -->
                            <div style="display: flex; align-items: center; justify-content: space-between; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 10px;">
                                <span style="font-size: 12px; font-weight: 700; color: #0f172a; width: 75px;">50 x</span>
                                <input type="number" min="0" id="denom_50" class="form-input" placeholder="0" oninput="calcDenoms()" style="width: 75px; text-align: center; padding: 4px 6px; font-weight: 700;">
                                <span style="font-size: 11.5px; font-weight: 700; color: #2563eb; width: 85px; text-align: right;" id="sub_50">0.00</span>
                            </div>
                            <!-- 20 -->
                            <div style="display: flex; align-items: center; justify-content: space-between; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 10px;">
                                <span style="font-size: 12px; font-weight: 700; color: #0f172a; width: 75px;">20 x</span>
                                <input type="number" min="0" id="denom_20" class="form-input" placeholder="0" oninput="calcDenoms()" style="width: 75px; text-align: center; padding: 4px 6px; font-weight: 700;">
                                <span style="font-size: 11.5px; font-weight: 700; color: #2563eb; width: 85px; text-align: right;" id="sub_20">0.00</span>
                            </div>
                            <!-- Coins -->
                            <div style="display: flex; align-items: center; justify-content: space-between; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 10px;">
                                <span style="font-size: 12px; font-weight: 700; color: #0f172a; width: 75px;">Coins:</span>
                                <input type="number" step="0.01" min="0" id="denom_coins" class="form-input" placeholder="0.00" oninput="calcDenoms()" style="width: 75px; text-align: center; padding: 4px 6px; font-weight: 700;">
                                <span style="font-size: 11.5px; font-weight: 700; color: #2563eb; width: 85px; text-align: right;" id="sub_coins">0.00</span>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN: Expected Cash & Day Closing Fields -->
                    <div>
                        <!-- System Expected Cash & Card Card -->
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 0.85rem 1rem; margin-bottom: 1rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.95rem; margin-bottom: 6px;">
                                <span style="color: #475569; font-weight: 500;">System Expected Cash:</span>
                                <strong style="color: #2563eb; font-size: 1.1rem; font-weight: 800;">LKR <span id="modalExpectedCash"><?= number_format($sum['expected_cash'] ?? 0, 2) ?></span></strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.95rem; border-top: 1px dashed #cbd5e1; padding-top: 6px;">
                                <span style="color: #475569; font-weight: 500;">System Expected Card:</span>
                                <strong style="color: #16a34a; font-size: 1.1rem; font-weight: 800;">LKR <span id="modalExpectedCard"><?= number_format($sum['expected_card_sales'] ?? 0, 2) ?></span></strong>
                            </div>
                        </div>

                        <!-- Actual Physical Counted Cash -->
                        <div style="margin-bottom: 1rem;">
                            <label class="form-label">Actual Physical Counted Cash (LKR) <span style="color: #ef4444;">*</span></label>
                            <input type="number" step="0.01" min="0" id="closeActualCash" class="form-input" required oninput="calculateClosingDiff()" style="font-size: 1.2rem; font-weight: 800; padding: 10px 12px;">
                        </div>

                        <!-- Card Payment Settlement Amount -->
                        <div style="margin-bottom: 1rem;">
                            <label class="form-label">Card Payment Settlement Amount (LKR)</label>
                            <input type="number" step="0.01" min="0" id="closeCardSettlement" class="form-input" placeholder="0.00" style="font-size: 1.1rem; font-weight: 700; padding: 8px 12px;">
                            <small style="color: #16a34a; font-size: 11px; font-weight: 700; display: block; margin-top: 3px;">System Expected Card Sales: LKR <?= number_format($sum['expected_card_sales'] ?? 0, 2) ?></small>
                        </div>

                        <!-- Variance Display -->
                        <div id="varianceBox" style="padding: 0.65rem 0.85rem; border-radius: 10px; margin-bottom: 1rem; display: none;">
                            <div style="font-weight: 800; font-size: 0.9rem;" id="varianceText"></div>
                        </div>

                        <!-- Retained Float -->
                        <div style="margin-bottom: 1rem;">
                            <label class="form-label">Retained Float for Next Opening (LKR) <span style="color: #ef4444;">*</span></label>
                            <input type="number" step="0.01" min="0" id="closeRetainedCash" class="form-input" value="20000.00" required oninput="calculateClosingDiff()" style="font-size: 1.1rem; font-weight: 800; padding: 8px 12px;">
                            <small style="color: #64748b; font-size: 11.5px; display: block; margin-top: 4px;">Default retained cash float is LKR 20,000.00 (kept in drawer for next opening)</small>
                        </div>

                        <!-- Surplus Cash & Transfer Destination Allocation -->
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem; margin-bottom: 1rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; padding-bottom: 0.5rem; border-bottom: 1px solid #e2e8f0;">
                                <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #475569;">Total Surplus Cash to Transfer</span>
                                <strong style="color: #0f172a; font-size: 1.15rem; font-weight: 800;">LKR <span id="calcDepositCash">0.00</span></strong>
                            </div>

                            <!-- Allocation Option 1: Cash / Safe Account -->
                            <div style="margin-bottom: 0.75rem;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                    <label class="form-label" style="margin-bottom: 0; font-size: 11.5px; font-weight: 700; color: #1e40af;">
                                        💼 Transfer to Safe / Cash Account
                                    </label>
                                </div>
                                <div style="display: grid; grid-template-columns: <?= (!empty($cashAccounts) && count($cashAccounts) > 1) ? '1.2fr 1fr' : '1fr' ?>; gap: 8px;">
                                    <?php if (!empty($cashAccounts) && count($cashAccounts) > 1): ?>
                                    <select id="splitSafeAccountId" class="form-input" style="font-size: 11.5px; font-weight: 600; padding: 6px 8px;">
                                        <?php foreach ($cashAccounts as $ca): ?>
                                            <option value="<?= (int)$ca['id'] ?>" <?= $ca['account_code'] === '1010' ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($ca['account_name'] . ' (' . $ca['account_code'] . ')') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php else: ?>
                                    <input type="hidden" id="splitSafeAccountId" value="<?= (int)($cashAccounts[0]['id'] ?? 1) ?>">
                                    <?php endif; ?>
                                    <input type="number" step="0.01" min="0" id="splitTransferSafe" class="form-input" placeholder="0.00" oninput="handleSplitManualChange()" style="font-size: 1rem; font-weight: 700; padding: 6px 10px;">
                                </div>
                            </div>

                            <!-- Allocation Option 2: Direct Bank Deposit (Optional) -->
                            <div style="margin-bottom: 0.75rem;">
                                <label class="form-label" style="font-size: 11.5px; font-weight: 700; color: #0284c7; margin-bottom: 4px;">
                                    🏦 Direct Bank Deposit (Optional)
                                </label>
                                <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 8px;">
                                    <select id="splitBankAccountId" class="form-input" onchange="handleSplitManualChange()" style="font-size: 11.5px; font-weight: 600; padding: 6px 8px;">
                                        <option value="">-- No Direct Bank Deposit --</option>
                                        <?php if (!empty($bankAccounts)): ?>
                                            <?php foreach ($bankAccounts as $ba): ?>
                                                <option value="<?= (int)$ba['id'] ?>">
                                                    <?= htmlspecialchars($ba['bank_name'] . ' - ' . $ba['account_name'] . ' (' . $ba['account_number'] . ')') ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                    <input type="number" step="0.01" min="0" id="splitTransferBank" class="form-input" placeholder="Deposit LKR (0.00)" oninput="handleSplitManualChange()" style="font-size: 1rem; font-weight: 700; padding: 6px 10px;">
                                </div>
                            </div>

                            <!-- Allocation Option 3: Petty Cash Account (Optional) -->
                            <div style="margin-bottom: 0.5rem;">
                                <label class="form-label" style="font-size: 11.5px; font-weight: 700; color: #64748b; margin-bottom: 4px;">
                                    🪙 Transfer to Petty Cash Account (1015) (Optional)
                                </label>
                                <input type="number" step="0.01" min="0" id="splitTransferPetty" class="form-input" placeholder="0.00" oninput="handleSplitManualChange()" style="font-size: 0.95rem; font-weight: 700; padding: 6px 10px;">
                            </div>

                            <!-- Split Allocation Status Validation -->
                            <div id="splitStatusNotice" style="display: flex; justify-content: space-between; align-items: center; font-size: 11px; font-weight: 700; padding-top: 6px; border-top: 1px dashed #cbd5e1; margin-top: 6px; color: #15803d;">
                                <span>Allocated: LKR <span id="lblSplitAllocated">0.00</span></span>
                                <span id="lblSplitBalanceText">✓ 100% Balanced</span>
                            </div>
                        </div>

                        <!-- Offline Banners -->
                        <div id="offlinePendingBlocker" style="display:none; background:#fee2e2; border:1px solid #fca5a5; padding:0.75rem; border-radius:10px; margin-bottom:1rem; color:#b91c1c; font-weight:700; font-size:0.8rem;">
                            DAY CLOSING BLOCKED: <span id="offlinePendingBlockCount">0</span> pending offline sale(s). Synchronize all offline sales first.
                        </div>

                        <div id="offlineConflictWarning" style="display:none; background:#fffbe6; border:1px solid #ffe58f; padding:0.75rem; border-radius:10px; margin-bottom:1rem; color:#d46b08;">
                            <div style="font-weight:700; margin-bottom:0.3rem; font-size:0.8rem;">
                                Conflict Warning: <span id="offlineConflictCount">0</span> offline sale(s) failed sync.
                            </div>
                            <label style="font-size:0.8rem; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:0.4rem;">
                                <input type="checkbox" id="chkAcknowledgeConflicts" onchange="toggleCloseButtonState()">
                                I acknowledge offline conflicts and approve closing.
                            </label>
                        </div>

                        <div style="background:#f8fafc; border:1px solid #cbd5e1; padding:0.5rem 0.75rem; border-radius:8px; margin-bottom:1rem; font-size:0.75rem; color:#475569;">
                            Notice: Other disconnected POS devices may contain unsynced sales that this server cannot detect while offline.
                        </div>

                        <!-- Manager Closing Notes -->
                        <div style="margin-bottom: 0.5rem;">
                            <label class="form-label">Manager Closing Notes (Optional)</label>
                            <textarea id="closeNotes" class="form-input" rows="2" placeholder="e.g. Verified by Branch Manager"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Action Buttons (Fixed Footer) -->
            <div class="curtiss-modal-footer">
                <button type="button" onclick="closeModal('closeDayModal')" class="inv-btn inv-btn-ghost">Cancel</button>
                <button type="submit" id="btnConfirmCloseDay" class="inv-btn inv-btn-danger">Confirm Final Day Closing</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script src="assets/js/offline_pos.js"></script>
<script>
    function openModal(id) {
        const m = document.getElementById(id);
        if (m) m.classList.add('active');
    }

    function closeModal(id) {
        const m = document.getElementById(id);
        if (m) m.classList.remove('active');
    }

    function focusStartDayForm() {
        const el = document.getElementById('startDayContainer');
        if (el) {
            el.scrollIntoView({ behavior: 'smooth' });
            document.getElementById('startOpeningCash')?.focus();
        }
    }

    async function handleStartDay(e) {
        e.preventDefault();
        const submitBtn = e.target.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.dataset.originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = 'Starting Day...';
        }

        const openingCash = parseFloat(document.getElementById('startOpeningCash').value) || 0;
        const notes = document.getElementById('startNotes').value;

        try {
            const res = await fetch('cash_drawer_api.php?action=start_day', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ opening_cash: openingCash, notes: notes })
            });
            const data = await res.json();
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = submitBtn.dataset.originalText;
                }
            }
        } catch (err) {
            alert('Connection error: ' + err.message);
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = submitBtn.dataset.originalText;
            }
        }
    }

    function openManualCashModal(type) {
        document.getElementById('manualType').value = type;
        document.getElementById('manualModalTitle').innerText = type === 'cash_in' ? 'Manual Cash In (+)' : 'Manual Cash Out (-)';
        document.getElementById('manualAmount').value = '';
        document.getElementById('manualReason').value = '';
        document.getElementById('manualNotes').value = '';
        openModal('manualCashModal');
    }

    async function handleManualCash(e) {
        e.preventDefault();
        const submitBtn = e.target.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.dataset.originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = 'Saving...';
        }

        const type = document.getElementById('manualType').value;
        const amount = parseFloat(document.getElementById('manualAmount').value) || 0;
        const reason = document.getElementById('manualReason').value;
        const notes = document.getElementById('manualNotes').value;

        try {
            const res = await fetch('cash_drawer_api.php?action=manual_cash_movement', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ type, amount, reason, notes })
            });
            const data = await res.json();
            if (data.success) {
                alert(data.message);
                closeModal('manualCashModal');
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = submitBtn.dataset.originalText;
                }
            }
        } catch (err) {
            alert('Connection error: ' + err.message);
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = submitBtn.dataset.originalText;
            }
        }
    }

    let hasPendingOfflineSales = false;
    let hasConflictOfflineSales = false;

    function openCloseDayModal() {
        document.getElementById('closeActualCash').value = '';
        document.getElementById('varianceBox').style.display = 'none';
        document.getElementById('calcDepositCash').innerText = '0.00';

        const safeInput = document.getElementById('splitTransferSafe');
        if (safeInput) {
            safeInput.dataset.manual = 'false';
            safeInput.value = '';
        }
        const bankInput = document.getElementById('splitTransferBank');
        if (bankInput) bankInput.value = '';
        const bankSelect = document.getElementById('splitBankAccountId');
        if (bankSelect) bankSelect.value = '';
        const pettyInput = document.getElementById('splitTransferPetty');
        if (pettyInput) pettyInput.value = '';

        const expected = parseFloat(document.getElementById('rawExpectedCash').value) || 0;
        const retainedInput = document.getElementById('closeRetainedCash');
        if (retainedInput) {
            retainedInput.value = Math.min(expected, 20000).toFixed(2);
        }

        if (typeof CurtissOfflinePOS !== 'undefined') {
            CurtissOfflinePOS.getAllOfflineSales().then(sales => {
                const pending = sales.filter(s => s.sync_status === 'Pending Sync' || s.sync_status === 'Syncing');
                const conflicts = sales.filter(s => s.sync_status === 'Conflict' || s.sync_status === 'Failed');

                hasPendingOfflineSales = pending.length > 0;
                hasConflictOfflineSales = conflicts.length > 0;

                if (hasPendingOfflineSales) {
                    document.getElementById('offlinePendingBlockCount').innerText = pending.length;
                    document.getElementById('offlinePendingBlocker').style.display = 'block';
                } else {
                    document.getElementById('offlinePendingBlocker').style.display = 'none';
                }

                if (hasConflictOfflineSales) {
                    document.getElementById('offlineConflictCount').innerText = conflicts.length;
                    document.getElementById('offlineConflictWarning').style.display = 'block';
                } else {
                    document.getElementById('offlineConflictWarning').style.display = 'none';
                }

                toggleCloseButtonState();
            });
        }
        openModal('closeDayModal');
    }

    function toggleCloseButtonState() {
        const btn = document.getElementById('btnConfirmCloseDay');
        if (!btn) return;

        if (hasPendingOfflineSales) {
            btn.disabled = true;
            btn.style.opacity = '0.45';
            btn.style.cursor = 'not-allowed';
            return;
        }

        if (hasConflictOfflineSales) {
            const chk = document.getElementById('chkAcknowledgeConflicts');
            if (!chk || !chk.checked) {
                btn.disabled = true;
                btn.style.opacity = '0.45';
                btn.style.cursor = 'not-allowed';
                return;
            }
        }

        btn.disabled = false;
        btn.style.opacity = '1';
        btn.style.cursor = 'pointer';
    }

    function calculateClosingDiff() {
        const expected = parseFloat(document.getElementById('rawExpectedCash').value) || 0;
        const actual = parseFloat(document.getElementById('closeActualCash').value) || 0;
        const retainedInput = document.getElementById('closeRetainedCash');
        let retained = parseFloat(retainedInput?.value) || 0;

        if (actual > 0 && retained > actual) {
            retained = actual;
            if (retainedInput) retainedInput.value = actual.toFixed(2);
        }

        const diff = actual - expected;
        const varianceBox = document.getElementById('varianceBox');
        const varianceText = document.getElementById('varianceText');

        if (actual > 0 || document.getElementById('closeActualCash').value !== '') {
            varianceBox.style.display = 'block';
            if (diff < -0.001) {
                varianceBox.style.background = '#fee2e2';
                varianceBox.style.border = '1px solid #fca5a5';
                varianceText.style.color = '#b91c1c';
                varianceText.innerText = '⚠️ Cash Shortage: LKR ' + Math.abs(diff).toFixed(2);
            } else if (diff > 0.001) {
                varianceBox.style.background = '#dcfce7';
                varianceBox.style.border = '1px solid #86efac';
                varianceText.style.color = '#15803d';
                varianceText.innerText = '✨ Cash Overage: LKR ' + diff.toFixed(2);
            } else {
                varianceBox.style.background = '#f1f5f9';
                varianceBox.style.border = '1px solid #cbd5e1';
                varianceText.style.color = '#475569';
                varianceText.innerText = '✓ Exact Cash Match (No Shortage/Overage)';
            }
        } else {
            varianceBox.style.display = 'none';
        }

        const deposit = Math.max(0, actual - retained);
        document.getElementById('calcDepositCash').innerText = deposit.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        const safeInput = document.getElementById('splitTransferSafe');
        const bankAmt = parseFloat(document.getElementById('splitTransferBank')?.value) || 0;
        const pettyAmt = parseFloat(document.getElementById('splitTransferPetty')?.value) || 0;
        if (safeInput && (!safeInput.dataset.manual || safeInput.dataset.manual === 'false')) {
            const safeRem = Math.max(0, deposit - bankAmt - pettyAmt);
            safeInput.value = safeRem > 0 ? safeRem.toFixed(2) : (deposit > 0 ? deposit.toFixed(2) : '');
        }
        updateSplitStatus();
    }

    function handleSplitManualChange() {
        const safeInput = document.getElementById('splitTransferSafe');
        if (safeInput) safeInput.dataset.manual = 'true';
        updateSplitStatus();
    }

    function updateSplitStatus() {
        const actual = parseFloat(document.getElementById('closeActualCash')?.value) || 0;
        const retained = parseFloat(document.getElementById('closeRetainedCash')?.value) || 0;
        const deposit = Math.max(0, actual - retained);

        const safeAmt = parseFloat(document.getElementById('splitTransferSafe')?.value) || 0;
        const bankAmt = parseFloat(document.getElementById('splitTransferBank')?.value) || 0;
        const pettyAmt = parseFloat(document.getElementById('splitTransferPetty')?.value) || 0;
        const totalAlloc = Math.round((safeAmt + bankAmt + pettyAmt) * 100) / 100;

        const lblAlloc = document.getElementById('lblSplitAllocated');
        const lblBal = document.getElementById('lblSplitBalanceText');
        const notice = document.getElementById('splitStatusNotice');

        if (lblAlloc) lblAlloc.textContent = totalAlloc.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        const diff = Math.round((deposit - totalAlloc) * 100) / 100;

        if (Math.abs(diff) <= 0.01) {
            if (notice) notice.style.color = '#15803d';
            if (lblBal) lblBal.innerHTML = '✓ 100% Balanced';
        } else if (diff > 0) {
            if (notice) notice.style.color = '#d97706';
            if (lblBal) lblBal.innerHTML = '⚠️ Remaining: LKR ' + diff.toFixed(2);
        } else {
            if (notice) notice.style.color = '#dc2626';
            if (lblBal) lblBal.innerHTML = '⚠️ Overallocated by: LKR ' + Math.abs(diff).toFixed(2);
        }
    }

    function calcDenoms() {
        const notes = [5000, 2000, 1000, 500, 100, 50, 20];
        let grandSum = 0;
        notes.forEach(note => {
            const cnt = parseInt(document.getElementById('denom_' + note)?.value) || 0;
            const sub = note * cnt;
            grandSum += sub;
            const subEl = document.getElementById('sub_' + note);
            if (subEl) subEl.textContent = sub.toFixed(2);
        });

        const coins = parseFloat(document.getElementById('denom_coins')?.value) || 0;
        grandSum += coins;
        const subCoinsEl = document.getElementById('sub_coins');
        if (subCoinsEl) subCoinsEl.textContent = coins.toFixed(2);

        const actualInput = document.getElementById('closeActualCash');
        if (actualInput) {
            actualInput.value = grandSum.toFixed(2);
            calculateClosingDiff();
        }
    }

    function switchCdTab(tab) {
        const btnPreview = document.getElementById('tabBtnPreview');
        const btnMov = document.getElementById('tabBtnMovements');
        const panePreview = document.getElementById('tabContentPreview');
        const paneMov = document.getElementById('tabContentMovements');

        if (tab === 'preview') {
            if (btnPreview) btnPreview.classList.add('active');
            if (btnMov) btnMov.classList.remove('active');
            if (panePreview) panePreview.style.display = 'flex';
            if (paneMov) paneMov.style.display = 'none';
        } else {
            if (btnMov) btnMov.classList.add('active');
            if (btnPreview) btnPreview.classList.remove('active');
            if (paneMov) paneMov.style.display = 'flex';
            if (panePreview) panePreview.style.display = 'none';
            updateMovPagination();
        }
    }

    function printDayClosingReceipt(bdayId) {
        if (!bdayId) return;
        const url = 'day_closing_receipt.php?id=' + bdayId + '&autoprint=1';
        window.open(url, '_blank', 'width=420,height=650');
    }

    async function handleCloseDay(e) {
        e.preventDefault();
        const submitBtn = document.getElementById('btnConfirmCloseDay');

        if (hasPendingOfflineSales) {
            alert("Day closing is BLOCKED because there are pending offline sales on this device. Please synchronize all offline sales before closing.");
            return;
        }

        if (hasConflictOfflineSales) {
            const chk = document.getElementById('chkAcknowledgeConflicts');
            if (!chk || !chk.checked) {
                alert("Please acknowledge the offline sales conflicts/failures before closing the business day.");
                return;
            }
        }

        const bdayId = parseInt(document.getElementById('closeBDayId').value);
        const actual = parseFloat(document.getElementById('closeActualCash').value) || 0;
        const cardSettlement = parseFloat(document.getElementById('closeCardSettlement')?.value) || 0;
        const retained = parseFloat(document.getElementById('closeRetainedCash').value) || 0;
        const notes = document.getElementById('closeNotes').value;

        const denominations = {
            '5000':  parseInt(document.getElementById('denom_5000')?.value) || 0,
            '2000':  parseInt(document.getElementById('denom_2000')?.value) || 0,
            '1000':  parseInt(document.getElementById('denom_1000')?.value) || 0,
            '500':   parseInt(document.getElementById('denom_500')?.value) || 0,
            '100':   parseInt(document.getElementById('denom_100')?.value) || 0,
            '50':    parseInt(document.getElementById('denom_50')?.value) || 0,
            '20':    parseInt(document.getElementById('denom_20')?.value) || 0,
            'coins': parseFloat(document.getElementById('denom_coins')?.value) || 0
        };

        // Split transfers validation
        const splits = [];
        const safeAmt = parseFloat(document.getElementById('splitTransferSafe')?.value) || 0;
        const bankAmt = parseFloat(document.getElementById('splitTransferBank')?.value) || 0;
        const bankAccId = parseInt(document.getElementById('splitBankAccountId')?.value) || 0;
        const pettyAmt = parseFloat(document.getElementById('splitTransferPetty')?.value) || 0;

        if (bankAmt > 0 && bankAccId <= 0) {
            alert('Please select a destination Bank Account for the direct bank deposit.');
            return;
        }

        const depositExpected = Math.max(0, actual - retained);
        if (depositExpected > 0) {
            const totalAlloc = Math.round((safeAmt + bankAmt + pettyAmt) * 100) / 100;
            if (Math.abs(totalAlloc - depositExpected) > 0.01) {
                alert(`Surplus cash transfer allocation total (LKR ${totalAlloc.toFixed(2)}) must equal surplus cash to transfer (LKR ${depositExpected.toFixed(2)}).`);
                return;
            }
            const safeAccId = parseInt(document.getElementById('splitSafeAccountId')?.value) || 0;
            if (safeAmt > 0) {
                splits.push({ type: 'safe', amount: safeAmt, account_id: safeAccId, notes: 'Day End Transfer to Safe/Cash Account' });
            }
            if (bankAmt > 0 && bankAccId > 0) {
                splits.push({ type: 'bank', amount: bankAmt, bank_account_id: bankAccId, notes: 'Day End Direct Bank Deposit' });
            }
            if (pettyAmt > 0) {
                splits.push({ type: 'petty_cash', amount: pettyAmt, notes: 'Day End Transfer to Petty Cash' });
            }
        }

        if (!confirm('Are you sure you want to close this Business Day? Once closed, no further transactions can be posted into today.')) {
            return;
        }

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.dataset.originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = 'Closing Day...';
        }

        try {
            const res = await fetch('cash_drawer_api.php?action=close_day', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    business_day_id: bdayId,
                    actual_cash: actual,
                    card_settlement_amount: cardSettlement,
                    retained_cash: retained,
                    notes: notes,
                    cash_denominations: denominations,
                    split_transfers: splits
                })
            });
            const data = await res.json();
            if (data.success) {
                printDayClosingReceipt(bdayId);
                alert(data.message);
                closeModal('closeDayModal');
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = submitBtn.dataset.originalText;
                }
            }
        } catch (err) {
            alert('Connection error: ' + err.message);
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = submitBtn.dataset.originalText;
            }
        }
    }

    /* ── Dynamic Client-Side Table Pagination for Movements (Default limit: 25) ── */
    let movPage = 1, movPageSize = 25;

    function updateMovPagination() {
        const table = document.getElementById('movementsTable');
        if (!table) return;
        const allRows = Array.from(table.querySelectorAll('tbody tr')).filter(tr => !tr.classList.contains('prod-empty'));
        const visibleRows = allRows.filter(tr => tr.dataset.searchMatched !== 'false');
        const total = visibleRows.length;
        const totalPages = Math.max(1, Math.ceil(total / movPageSize));
        if (movPage > totalPages) movPage = totalPages;
        if (movPage < 1) movPage = 1;

        const startIdx = total > 0 ? (movPage - 1) * movPageSize : 0;
        const endIdx = Math.min(startIdx + movPageSize, total);

        allRows.forEach(tr => tr.style.display = 'none');
        for (let i = startIdx; i < endIdx; i++) {
            if (visibleRows[i]) visibleRows[i].style.display = '';
        }

        const pStart = document.getElementById('movPagStart');
        const pEnd = document.getElementById('movPagEnd');
        const pTot = document.getElementById('movPagTotal');
        if (pStart) pStart.textContent = total > 0 ? startIdx + 1 : 0;
        if (pEnd) pEnd.textContent = endIdx;
        if (pTot) pTot.textContent = total;

        renderPaginationNav('movPagNavBtns', movPage, totalPages, (p) => { movPage = p; updateMovPagination(); });
    }

    function changeMovPageSize() {
        movPageSize = parseInt(document.getElementById('movPagSize').value) || 25;
        movPage = 1;
        updateMovPagination();
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
        updateMovPagination();

        // Keyboard support on modal forms (Rule 15)
        document.querySelectorAll('#manualCashModal input, #closeDayModal input').forEach(input => {
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
                    const form = e.target.closest('form');
                    if (form) {
                        const inputs = Array.from(form.querySelectorAll('input:not([type="hidden"]), select, textarea, button[type="submit"]'));
                        const idx = inputs.indexOf(e.target);
                        if (idx >= 0 && idx < inputs.length - 1) {
                            e.preventDefault();
                            inputs[idx + 1].focus();
                        }
                    }
                }
            });
        });
    });

    /* ── Universal Multi-Column Realtime Search with Floating Bottom Nav ── */
    function registerBottomSearch() {
        if (window.CurtissNav && window.CurtissNav.enablePageSearch) {
            const urlParams = new URLSearchParams(window.location.search);
            const initialSearch = urlParams.get('search') || '';

            window.CurtissNav.enablePageSearch({
                placeholder: 'Search movements, dates, amounts, users...',
                initialValue: initialSearch,
                onSearch: function(query) {
                    filterCashDrawerRealtime(query);
                },
                onEnter: function(query) {
                    const url = new URL(window.location.href);
                    if (query && query.trim()) {
                        url.searchParams.set('search', query.trim());
                    } else {
                        url.searchParams.delete('search');
                    }
                    url.searchParams.set('page', '1');
                    window.location.href = url.toString();
                }
            });

            if (initialSearch) {
                filterCashDrawerRealtime(initialSearch);
            }
        } else {
            setTimeout(registerBottomSearch, 40);
        }
    }
    registerBottomSearch();

    function filterCashDrawerRealtime(query) {
        const q = (query || '').toLowerCase().trim();
        const cleanDigits = q.replace(/[^0-9.]/g, '');

        if (q && document.getElementById('tabContentMovements')?.style.display === 'none') {
            switchCdTab('movements');
        }

        const movRows = document.querySelectorAll('#movementsTable tbody tr');
        movRows.forEach(tr => {
            if (tr.classList.contains('prod-empty')) return;
            let matched = false;
            if (!q) {
                matched = true;
            } else {
                const dataCells = Array.from(tr.querySelectorAll('td'));
                for (const td of dataCells) {
                    const text = td.textContent.toLowerCase();
                    const textNoComma = text.replace(/,/g, '');
                    if (text.includes(q) || (cleanDigits && textNoComma.includes(cleanDigits))) {
                        matched = true;
                        break;
                    }
                }
            }
            tr.dataset.searchMatched = matched ? 'true' : 'false';
        });

        movPage = 1;
        updateMovPagination();
    }
</script>

