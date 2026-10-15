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
    margin-bottom: 1.5rem;
}
.prod-table-scroll { overflow-x: auto; }
.prod-table {
    width: 100%;
    border-collapse: collapse;
}
.prod-table thead {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}
.prod-table thead th {
    padding: 10px 14px;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #64748b;
    white-space: nowrap;
}
.prod-table tbody tr {
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.12s ease;
}
.prod-table tbody tr:hover {
    background: #f8fafc;
}
.prod-table td {
    padding: 12px 14px;
    font-size: 12.5px;
    color: #1e293b;
    vertical-align: middle;
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

<div style="width: 100%; max-width: 100%; padding-bottom: 24px;">
    <!-- Page Header -->
    <div class="inv-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
                <h1 class="inv-title">Cash Drawer & Day Closing</h1>
            </div>
            <p class="inv-subtitle">Auto-initialized business day (LKR 20,000.00 default cash float), append-only drawer movements & reconciliation</p>
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
        <!-- Active Drawer Reconciliation (Full Width Clean Layout) -->
        <?php $sum = $activeSummary['summary'] ?? []; ?>
        <div class="inv-card" style="margin-bottom: 1.75rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 1.25rem; padding-bottom: 10px; border-bottom: 1px solid rgba(226, 232, 240, 0.8);">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: #dbeafe; color: #2563eb; display: flex; align-items: center; justify-content: center;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 3v18M3 12h18"/></svg>
                    </div>
                    <div>
                        <h3 style="font-size: 1.1rem; font-weight: 800; color: #0f172a; margin: 0;">Active Drawer Reconciliation</h3>
                        <p style="font-size: 11.5px; color: #64748b; margin: 2px 0 0 0;">Live cash drawer movements breakdown for current active business day</p>
                    </div>
                </div>
                <!-- Active Day Meta Info Chips -->
                <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap; background: #f8fafc; border: 1px solid #e2e8f0; padding: 6px 14px; border-radius: 999px; font-size: 12px;">
                    <div><span style="color: #64748b;">Opened By:</span> <strong style="color: #0f172a;"><?= htmlspecialchars($activeDay['opener_name']) ?></strong></div>
                    <div style="color: #cbd5e1;">|</div>
                    <div><span style="color: #64748b;">Opened At:</span> <strong style="color: #0f172a;"><?= htmlspecialchars($activeDay['opened_at']) ?></strong></div>
                    <div style="color: #cbd5e1;">|</div>
                    <div><span style="color: #64748b;">Opening Float:</span> <strong style="color: #0f172a;">LKR <?= number_format((float)$activeDay['opening_cash'], 2) ?></strong></div>
                </div>
            </div>

            <div class="reconcile-row">
                <span style="color: #334155; font-weight: 500;">Initial Opening Cash</span>
                <span style="font-weight: 700; color: #0f172a;">LKR <?= number_format($sum['opening_cash'] ?? 0, 2) ?></span>
            </div>
            <div class="reconcile-row">
                <span style="color: #16a34a; font-weight: 600;">+ POS Cash Sales</span>
                <span style="font-weight: 700; color: #16a34a;">LKR <?= number_format($sum['pos_cash_sales'] ?? 0, 2) ?></span>
            </div>
            <div class="reconcile-row">
                <span style="color: #16a34a; font-weight: 600;">+ Customer Cash Collections</span>
                <span style="font-weight: 700; color: #16a34a;">LKR <?= number_format($sum['customer_cash_collections'] ?? 0, 2) ?></span>
            </div>
            <?php if (($sum['supplier_cash_refunds'] ?? 0) > 0): ?>
            <div class="reconcile-row">
                <span style="color: #16a34a; font-weight: 600;">+ Supplier Cash Refunds</span>
                <span style="font-weight: 700; color: #16a34a;">LKR <?= number_format($sum['supplier_cash_refunds'] ?? 0, 2) ?></span>
            </div>
            <?php endif; ?>
            <div class="reconcile-row">
                <span style="color: #16a34a; font-weight: 600;">+ Manual Cash In</span>
                <span style="font-weight: 700; color: #16a34a;">LKR <?= number_format($sum['manual_cash_in'] ?? 0, 2) ?></span>
            </div>
            <?php if (($sum['overage_adjustments'] ?? 0) > 0): ?>
            <div class="reconcile-row">
                <span style="color: #16a34a; font-weight: 600;">+ Overage Adjustments</span>
                <span style="font-weight: 700; color: #16a34a;">LKR <?= number_format($sum['overage_adjustments'] ?? 0, 2) ?></span>
            </div>
            <?php endif; ?>
            <div class="reconcile-row subtotal-row">
                <span>Total Cash In</span>
                <span>LKR <?= number_format($sum['total_cash_in'] ?? 0, 2) ?></span>
            </div>

            <div class="reconcile-row">
                <span style="color: #dc2626; font-weight: 600;">- Cash Refunds</span>
                <span style="font-weight: 700; color: #dc2626;">LKR <?= number_format($sum['cash_refunds'] ?? 0, 2) ?></span>
            </div>
            <div class="reconcile-row">
                <span style="color: #dc2626; font-weight: 600;">- Supplier Cash Payments</span>
                <span style="font-weight: 700; color: #dc2626;">LKR <?= number_format($sum['supplier_cash_payments'] ?? 0, 2) ?></span>
            </div>
            <div class="reconcile-row">
                <span style="color: #dc2626; font-weight: 600;">- Employee Advance Payments</span>
                <span style="font-weight: 700; color: #dc2626;">LKR <?= number_format($sum['employee_advances'] ?? 0, 2) ?></span>
            </div>
            <?php if (($sum['salary_payments'] ?? 0) > 0): ?>
            <div class="reconcile-row">
                <span style="color: #dc2626; font-weight: 600;">- Payroll Salary Disbursements</span>
                <span style="font-weight: 700; color: #dc2626;">LKR <?= number_format($sum['salary_payments'] ?? 0, 2) ?></span>
            </div>
            <?php endif; ?>
            <?php if (($sum['petty_cash_reimbursements'] ?? 0) > 0): ?>
            <div class="reconcile-row">
                <span style="color: #dc2626; font-weight: 600;">- Petty Cash Reimbursements</span>
                <span style="font-weight: 700; color: #dc2626;">LKR <?= number_format($sum['petty_cash_reimbursements'] ?? 0, 2) ?></span>
            </div>
            <?php endif; ?>
            <div class="reconcile-row">
                <span style="color: #dc2626; font-weight: 600;">- Manual Cash Out</span>
                <span style="font-weight: 700; color: #dc2626;">LKR <?= number_format($sum['manual_cash_out'] ?? 0, 2) ?></span>
            </div>
            <?php if (($sum['shortage_adjustments'] ?? 0) > 0): ?>
            <div class="reconcile-row">
                <span style="color: #dc2626; font-weight: 600;">- Shortage Adjustments</span>
                <span style="font-weight: 700; color: #dc2626;">LKR <?= number_format($sum['shortage_adjustments'] ?? 0, 2) ?></span>
            </div>
            <?php endif; ?>
            <div class="reconcile-row subtotal-row">
                <span>Total Cash Out</span>
                <span>LKR <?= number_format($sum['total_cash_out'] ?? 0, 2) ?></span>
            </div>

            <div class="reconcile-row total-row">
                <span>Expected Cash in Drawer</span>
                <span style="color: #2563eb;">LKR <?= number_format($sum['expected_cash'] ?? 0, 2) ?></span>
            </div>
            <div class="reconcile-row total-row" style="margin-top: 6px; background: linear-gradient(135deg, rgba(240, 253, 244, 0.95) 0%, rgba(255, 255, 255, 0.95) 100%); border-color: #bbf7d0;">
                <span style="color: #15803d;">System Expected Card Sales</span>
                <span style="color: #15803d;">LKR <?= number_format($sum['expected_card_sales'] ?? 0, 2) ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- History Tab Navigation -->
    <div class="cd-tabs-nav">
        <button type="button" class="cd-tab-btn active" id="tabBtnMovements" onclick="switchCdTab('movements')">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            Today's Movements
            <?php if (!empty($movements)): ?>
                <span class="cd-tab-badge"><?= count($movements) ?></span>
            <?php endif; ?>
        </button>
        <button type="button" class="cd-tab-btn" id="tabBtnClosed" onclick="switchCdTab('closed')">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            Closed Business Days Log
            <?php if (!empty($closedDays['totalItems'])): ?>
                <span class="cd-tab-badge"><?= (int)$closedDays['totalItems'] ?></span>
            <?php endif; ?>
        </button>
    </div>

    <!-- Tab 1: Today's Movements -->
    <div id="tabContentMovements" class="cd-tab-pane">
        <div class="prod-table-panel">
            <div style="padding: 14px 18px; border-bottom: 1px solid #e2e8f0; background: rgba(248, 250, 252, 0.8); display: flex; align-items: center; justify-content: space-between;">
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
                            <th style="width: 120px;">Direction</th>
                            <th style="width: 140px;">Amount (LKR)</th>
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
                                <td>
                                    <?php if ($m['direction'] === 'in'): ?>
                                        <span class="prod-badge prod-badge-active"><span class="dot"></span>IN ▲</span>
                                    <?php else: ?>
                                        <span class="prod-badge prod-badge-inactive"><span class="dot"></span>OUT ▼</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-weight: 800; color: #0f172a;">LKR <?= number_format((float)$m['amount'], 2) ?></td>
                                <td style="color: #475569; font-size: 12px;"><?= htmlspecialchars($m['reason'] ?? '-') ?></td>
                                <td style="font-weight: 600; color: #334155;"><?= htmlspecialchars($m['user_name']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- Movements Table Pagination Footer -->
            <div class="prod-table-footer">
                <div style="font-size: 12px; font-weight: 600; color: #64748b;" id="movPagInfo">
                    Showing <span id="movPagStart">1</span> to <span id="movPagEnd">10</span> of <span id="movPagTotal">0</span> entries
                </div>
                <div style="display: flex; align-items: center; gap: 14px;">
                    <div style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: #64748b; font-weight: 600;">
                        <span>Rows:</span>
                        <select id="movPagSize" onchange="changeMovPageSize()" style="height: 30px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 8px; font-size: 12px; color: #0f172a; background: #fff; outline: none; font-weight: 600; cursor: pointer;">
                            <option value="10" selected>10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                    <div style="display: flex; align-items: center; gap: 4px;" id="movPagNavBtns"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab 2: Closed Business Days Log -->
    <div id="tabContentClosed" class="cd-tab-pane" style="display: none;">
        <div class="prod-table-panel">
            <div style="padding: 14px 18px; border-bottom: 1px solid #e2e8f0; background: rgba(248, 250, 252, 0.8); display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#0f172a" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <h3 style="font-size: 0.95rem; font-weight: 800; color: #0f172a; margin: 0;">Closed Business Days Log</h3>
                </div>
            </div>
            <div class="prod-table-scroll">
                <table class="prod-table" id="closedDaysTable">
                    <thead>
                        <tr>
                            <th style="width: 70px;">ID</th>
                            <th>Business Date</th>
                            <th>Opening Cash</th>
                            <th>Expected Cash</th>
                            <th>Actual Counted</th>
                            <th>Variance (Shortage/Overage)</th>
                            <th>Retained Float</th>
                            <th>Main Cash Account</th>
                            <th>Opener / Closer</th>
                            <th>Closed At</th>
                            <th style="width: 110px; text-align: center;">Receipt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($closedDays['items'])): ?>
                            <tr class="prod-empty"><td colspan="11" style="text-align: center; color: #94a3b8; padding: 32px 16px;">No closed business days recorded.</td></tr>
                        <?php else: ?>
                            <?php foreach ($closedDays['items'] as $cd): ?>
                            <?php 
                                $shortage = (float)$cd['shortage_amount'];
                                $overage = (float)$cd['overage_amount'];
                            ?>
                            <tr>
                                <td><strong style="color: #64748b; font-size: 11.5px;">#<?= (int)$cd['id'] ?></strong></td>
                                <td style="font-weight: 800; color: #0f172a;"><?= htmlspecialchars($cd['business_date']) ?></td>
                                <td style="color: #475569;">LKR <?= number_format((float)$cd['opening_cash'], 2) ?></td>
                                <td style="color: #475569;">LKR <?= number_format((float)$cd['expected_cash'], 2) ?></td>
                                <td style="font-weight: 800; color: #0f172a;">LKR <?= number_format((float)$cd['actual_cash'], 2) ?></td>
                                <td>
                                    <?php if ($shortage > 0): ?>
                                        <span class="prod-badge prod-badge-inactive"><span class="dot"></span>Shortage: LKR <?= number_format($shortage, 2) ?></span>
                                    <?php elseif ($overage > 0): ?>
                                        <span class="prod-badge prod-badge-active"><span class="dot"></span>Overage: LKR <?= number_format($overage, 2) ?></span>
                                    <?php else: ?>
                                        <span style="color: #64748b; font-weight: 600; font-size: 12px;">Exact (0.00)</span>
                                    <?php endif; ?>
                                </td>
                                <td style="color: #475569;">LKR <?= number_format((float)$cd['retained_cash'], 2) ?></td>
                                <td style="font-weight: 800; color: #2563eb;">LKR <?= number_format((float)$cd['cash_to_deposit'], 2) ?></td>
                                <td style="color: #334155; font-size: 12px;"><?= htmlspecialchars($cd['opener_name']) ?> / <?= htmlspecialchars($cd['closer_name'] ?? 'N/A') ?></td>
                                <td style="color: #64748b; font-size: 12px; font-weight: 500;"><?= htmlspecialchars($cd['closed_at']) ?></td>
                                <td style="text-align: center;">
                                    <button type="button" onclick="printDayClosingReceipt(<?= (int)$cd['id'] ?>)" class="prod-act-btn prod-act-primary" style="background: #0f172a; color: #fff; border-color: #0f172a;" title="Print 88mm Day Closing Receipt">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                                        Print
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- Closed Days Log Pagination Footer -->
            <div class="prod-table-footer">
                <div style="font-size: 12px; font-weight: 600; color: #64748b;" id="closedPagInfo">
                    Showing <span id="closedPagStart">1</span> to <span id="closedPagEnd">10</span> of <span id="closedPagTotal">0</span> entries
                </div>
                <div style="display: flex; align-items: center; gap: 14px;">
                    <div style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: #64748b; font-weight: 600;">
                        <span>Rows per page:</span>
                        <select id="closedPagSize" onchange="changeClosedPageSize()" style="height: 30px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 8px; font-size: 12px; color: #0f172a; background: #fff; outline: none; font-weight: 600; cursor: pointer;">
                            <option value="10" selected>10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                    <div style="display: flex; align-items: center; gap: 4px;" id="closedPagNavBtns"></div>
                </div>
            </div>
        </div>
    </div>
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
                            <small style="color: #64748b; font-size: 11.5px; display: block; margin-top: 4px;">Default retained cash float is LKR 20,000.00</small>
                        </div>

                        <!-- Main Cash Account Box -->
                        <div style="background: linear-gradient(135deg, rgba(239, 246, 255, 0.95) 0%, rgba(255, 255, 255, 0.95) 100%); border: 1px solid #bfdbfe; border-radius: 10px; padding: 0.75rem 1rem; margin-bottom: 1rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.95rem;">
                                <span style="color: #1e40af; font-weight: 600;">Cash Transfer to Main Cash Account:</span>
                                <strong style="color: #1e40af; font-size: 1.15rem; font-weight: 800;">LKR <span id="calcDepositCash">0.00</span></strong>
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
            }
        } catch (err) {
            alert('Connection error: ' + err.message);
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
            }
        } catch (err) {
            alert('Connection error: ' + err.message);
        }
    }

    let hasPendingOfflineSales = false;
    let hasConflictOfflineSales = false;

    function openCloseDayModal() {
        document.getElementById('closeActualCash').value = '';
        document.getElementById('varianceBox').style.display = 'none';
        document.getElementById('calcDepositCash').innerText = '0.00';

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
            if (chk && !chk.checked) {
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
        const btnMov = document.getElementById('tabBtnMovements');
        const btnClosed = document.getElementById('tabBtnClosed');
        const paneMov = document.getElementById('tabContentMovements');
        const paneClosed = document.getElementById('tabContentClosed');

        if (tab === 'movements') {
            if (btnMov) btnMov.classList.add('active');
            if (btnClosed) btnClosed.classList.remove('active');
            if (paneMov) paneMov.style.display = 'block';
            if (paneClosed) paneClosed.style.display = 'none';
            updateMovPagination();
        } else {
            if (btnClosed) btnClosed.classList.add('active');
            if (btnMov) btnMov.classList.remove('active');
            if (paneClosed) paneClosed.style.display = 'block';
            if (paneMov) paneMov.style.display = 'none';
            updateClosedPagination();
        }
    }

    function printDayClosingReceipt(bdayId) {
        if (!bdayId) return;
        const url = 'day_closing_receipt.php?id=' + bdayId + '&autoprint=1';
        window.open(url, '_blank', 'width=420,height=650');
    }

    async function handleCloseDay(e) {
        e.preventDefault();
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

        if (!confirm('Are you sure you want to close this Business Day? Once closed, no further transactions can be posted into today.')) {
            return;
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
                    cash_denominations: denominations
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
            }
        } catch (err) {
            alert('Connection error: ' + err.message);
        }
    }

    /* ── Dynamic Client-Side Table Pagination ── */
    let movPage = 1, movPageSize = 10;
    let closedPage = 1, closedPageSize = 10;

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

        document.getElementById('movPagStart').textContent = total > 0 ? startIdx + 1 : 0;
        document.getElementById('movPagEnd').textContent = endIdx;
        document.getElementById('movPagTotal').textContent = total;

        renderPaginationNav('movPagNavBtns', movPage, totalPages, (p) => { movPage = p; updateMovPagination(); });
    }

    function changeMovPageSize() {
        movPageSize = parseInt(document.getElementById('movPagSize').value) || 10;
        movPage = 1;
        updateMovPagination();
    }

    function updateClosedPagination() {
        const table = document.getElementById('closedDaysTable');
        if (!table) return;
        const allRows = Array.from(table.querySelectorAll('tbody tr')).filter(tr => !tr.classList.contains('prod-empty'));
        const visibleRows = allRows.filter(tr => tr.dataset.searchMatched !== 'false');
        const total = visibleRows.length;
        const totalPages = Math.max(1, Math.ceil(total / closedPageSize));
        if (closedPage > totalPages) closedPage = totalPages;
        if (closedPage < 1) closedPage = 1;

        const startIdx = total > 0 ? (closedPage - 1) * closedPageSize : 0;
        const endIdx = Math.min(startIdx + closedPageSize, total);

        allRows.forEach(tr => tr.style.display = 'none');
        for (let i = startIdx; i < endIdx; i++) {
            if (visibleRows[i]) visibleRows[i].style.display = '';
        }

        document.getElementById('closedPagStart').textContent = total > 0 ? startIdx + 1 : 0;
        document.getElementById('closedPagEnd').textContent = endIdx;
        document.getElementById('closedPagTotal').textContent = total;

        renderPaginationNav('closedPagNavBtns', closedPage, totalPages, (p) => { closedPage = p; updateClosedPagination(); });
    }

    function changeClosedPageSize() {
        closedPageSize = parseInt(document.getElementById('closedPagSize').value) || 10;
        closedPage = 1;
        updateClosedPagination();
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
        updateClosedPagination();
    });

    /* ── Realtime Search in Floating Bottom Nav Bar ── */
    function registerBottomSearch() {
        if (window.CurtissNav && window.CurtissNav.enablePageSearch) {
            window.CurtissNav.enablePageSearch({
                placeholder: 'Search movements & closed days...',
                onSearch: function(query) {
                    filterCashDrawerRealtime(query);
                }
            });
        } else {
            setTimeout(registerBottomSearch, 40);
        }
    }
    registerBottomSearch();

    function filterCashDrawerRealtime(query) {
        const q = query.toLowerCase().trim();
        const movRows = document.querySelectorAll('#movementsTable tbody tr');
        movRows.forEach(tr => {
            if (tr.classList.contains('prod-empty')) return;
            const text = tr.textContent.toLowerCase();
            tr.dataset.searchMatched = (!q || text.includes(q)) ? 'true' : 'false';
        });

        const closedRows = document.querySelectorAll('#closedDaysTable tbody tr');
        closedRows.forEach(tr => {
            if (tr.classList.contains('prod-empty')) return;
            const text = tr.textContent.toLowerCase();
            tr.dataset.searchMatched = (!q || text.includes(q)) ? 'true' : 'false';
        });

        movPage = 1;
        updateMovPagination();
        closedPage = 1;
        updateClosedPagination();
    }
</script>
