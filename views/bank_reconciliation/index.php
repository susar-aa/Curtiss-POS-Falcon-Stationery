<?php
/**
 * Curtiss POS - Bank Reconciliation Master View (Stage 13 Redesign)
 * Full Glassmorphic ERP Interface for bank reconciliation sessions, statement matching,
 * live difference calculation, bank charges/interest adjustments, and zero-difference completion locking.
 */
?>
<link rel="stylesheet" href="assets/css/curtiss_select.css">

<!-- Floating Top-Right Action Header Buttons -->
<div id="curtiss-floating-header-right" class="no-print" style="position: fixed; top: 16px; right: 20px; z-index: 1050; display: flex; align-items: center; gap: 8px;">
    <?php if ($activeSession): ?>
        <?php if (Auth::hasPermission('post_bank_adjustment')): ?>
        <button type="button" class="inv-btn inv-btn-ghost" onclick="openAdjustmentModal()" style="color: #d97706; border-color: rgba(245,158,11,0.3); background: rgba(254,243,199,0.95);">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            + Post Bank Charge / Interest
        </button>
        <?php endif; ?>
        <button type="button" class="inv-btn inv-btn-success" onclick="handleCompleteReconciliation(<?= (int)$activeSession['id'] ?>)" style="background: #16a34a; color: #fff; box-shadow: 0 4px 12px rgba(22,163,74,0.25);">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            Complete & Lock Session
        </button>
    <?php endif; ?>

    <!-- Bank Account Switcher Pill -->
    <div style="background: rgba(255, 255, 255, 0.92); backdrop-filter: blur(8px); border: 1px solid rgba(226, 232, 240, 0.9); border-radius: 999px; padding: 2px 6px; display: flex; align-items: center; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2.5" style="margin-left: 8px;"><path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11M8 14v3M12 14v3M16 14v3"/></svg>
        <select onchange="window.location.href='bank_reconciliation.php?bank_account_id=' + this.value" style="font-weight: 700; border: none; background: transparent; font-size: 12px; color: #0f172a; padding: 6px 10px; cursor: pointer; outline: none; font-family: inherit;">
            <?php foreach ($bankAccounts as $ba): ?>
            <option value="<?= (int)$ba['id'] ?>" <?= (int)$ba['id'] === $selectedBankId ? 'selected' : '' ?>>
                <?= htmlspecialchars($ba['bank_name'] . ' - ' . $ba['account_name'] . ' (' . $ba['account_number'] . ')') ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
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
    .inv-btn-success {
        background: #16a34a;
        color: #ffffff;
    }
    .inv-btn-success:hover {
        background: #15803d;
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

    /* KPI Header Cards Grid */
    .rec-kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    .rec-kpi-card {
        background: rgba(255, 255, 255, 0.92);
        border-radius: 14px;
        padding: 1rem 1.15rem;
        border: 1px solid rgba(226, 232, 240, 0.9);
        box-shadow: 0 4px 12px -2px rgba(0, 0, 0, 0.03);
        position: relative;
        overflow: hidden;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .rec-kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px -4px rgba(0, 0, 0, 0.06);
    }
    .rec-kpi-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; width: 4px; height: 100%;
        background: #3b82f6;
    }
    .rec-kpi-card.stmt::before { background: #0f172a; }
    .rec-kpi-card.book::before { background: #2563eb; }
    .rec-kpi-card.cleared::before { background: #059669; }
    .rec-kpi-card.deposits::before { background: #16a34a; }
    .rec-kpi-card.payments::before { background: #dc2626; }
    .rec-kpi-card.diff-zero::before { background: #16a34a; }
    .rec-kpi-card.diff-warn::before { background: #dc2626; }

    .rec-kpi-card.diff-zero {
        background: linear-gradient(135deg, rgba(240, 253, 244, 0.95) 0%, rgba(255, 255, 255, 0.95) 100%);
        border-color: #bbf7d0;
    }
    .rec-kpi-card.diff-warn {
        background: linear-gradient(135deg, rgba(254, 242, 242, 0.95) 0%, rgba(255, 255, 255, 0.95) 100%);
        border-color: #fecaca;
    }

    .rec-kpi-title {
        font-size: 0.7rem;
        font-weight: 800;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        margin-bottom: 0.35rem;
    }
    .rec-kpi-value {
        font-size: 1.4rem;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.02em;
    }

    /* Status Badges */
    .prod-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        line-height: 1.2;
        white-space: nowrap;
    }
    .prod-badge-active   { background: #dcfce7; color: #15803d; border: 1px solid rgba(22,163,74,0.2); }
    .prod-badge-inactive { background: #fee2e2; color: #b91c1c; border: 1px solid rgba(220,38,38,0.2); }
    .prod-badge-draft    { background: #dbeafe; color: #1e40af; border: 1px solid rgba(37,99,235,0.2); }
    .prod-badge .dot {
        width: 5px; height: 5px; border-radius: 50%; display: inline-block;
    }
    .prod-badge-active   .dot { background: #16a34a; }
    .prod-badge-inactive .dot { background: #dc2626; }
    .prod-badge-draft    .dot { background: #2563eb; }

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
        padding: 11px 14px;
        font-size: 12.5px;
        color: #1e293b;
        vertical-align: middle;
    }

    /* Modal Backdrop & Card */
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
        max-width: 560px;
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

<div style="width: 100%; max-width: 100%; padding-bottom: 24px;">
    <!-- Page Header -->
    <div class="inv-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
                <h1 class="inv-title">Bank Reconciliation</h1>
            </div>
            <p class="inv-subtitle">Match bank statements against system records & lock zero-difference reconciliation periods</p>
        </div>
        <div>
            <?php if ($activeSession): ?>
                <span class="prod-badge prod-badge-draft" style="padding: 6px 14px; font-size: 12px;">
                    <span class="dot"></span> DRAFT SESSION (#<?= htmlspecialchars($activeSession['reconciliation_no']) ?>)
                </span>
            <?php else: ?>
                <span class="prod-badge prod-badge-inactive" style="padding: 6px 14px; font-size: 12px;">
                    <span class="dot"></span> NO ACTIVE DRAFT SESSION
                </span>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!$activeSession): ?>
    <!-- Hero Glass Card: Start New Reconciliation Session -->
    <div class="inv-card" style="background: linear-gradient(135deg, rgba(239, 246, 255, 0.95) 0%, rgba(255, 255, 255, 0.95) 100%); border-color: #bfdbfe; max-width: 720px; margin: 0 auto 2rem auto;">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 1.25rem;">
            <div style="width: 42px; height: 42px; border-radius: 12px; background: #dbeafe; color: #1e40af; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11M8 14v3M12 14v3M16 14v3"/></svg>
            </div>
            <div>
                <h2 style="color: #1e40af; font-weight: 800; font-size: 1.25rem; margin: 0;">Start Bank Reconciliation Session</h2>
                <p style="color: #475569; font-size: 0.85rem; margin-top: 2px;">Enter statement period details for <strong><?= htmlspecialchars($selectedBankAccount['bank_name'] ?? 'Bank') ?></strong> to begin statement matching.</p>
            </div>
        </div>

        <form id="startSessionForm" onsubmit="handleStartSession(event)">
            <input type="hidden" name="bank_account_id" value="<?= $selectedBankId ?>">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label class="form-label">Statement Start Date <span style="color: #ef4444;">*</span></label>
                    <input type="date" class="form-input" name="statement_start_date" value="<?= date('Y-m-01') ?>" required>
                </div>
                <div>
                    <label class="form-label">Statement End Date <span style="color: #ef4444;">*</span></label>
                    <input type="date" class="form-input" name="statement_end_date" value="<?= date('Y-m-t') ?>" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label class="form-label">Statement Opening Balance (LKR) <span style="color: #ef4444;">*</span></label>
                    <input type="number" step="0.01" class="form-input" name="statement_opening_balance" required placeholder="0.00" style="font-size: 1.1rem; font-weight: 800;">
                </div>
                <div>
                    <label class="form-label">Statement Closing Balance (LKR) <span style="color: #ef4444;">*</span></label>
                    <input type="number" step="0.01" class="form-input" name="statement_closing_balance" required placeholder="0.00" style="font-size: 1.1rem; font-weight: 800; color: #2563eb;">
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <label class="form-label">Notes / Statement Reference (Optional)</label>
                <input type="text" class="form-input" name="notes" placeholder="e.g. Monthly Bank Statement #2026-09">
            </div>

            <button type="submit" class="inv-btn inv-btn-primary" id="startBtn" style="width: 100%; padding: 12px 20px; font-size: 13.5px;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                Start Reconciliation Session
            </button>
        </form>
    </div>

    <?php else: ?>
    <!-- Active Draft Session Workspace Header Banner -->
    <div class="inv-card" style="background: linear-gradient(135deg, rgba(239, 246, 255, 0.95) 0%, rgba(255, 255, 255, 0.95) 100%); border-color: #bfdbfe; margin-bottom: 1.5rem;">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 12px; background: #dbeafe; color: #2563eb; display: flex; align-items: center; justify-content: center;">
                    <svg width="20" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M9 15l2 2 4-4"/></svg>
                </div>
                <div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <h3 style="font-size: 1.15rem; font-weight: 800; color: #1e40af; margin: 0;">Session: <?= htmlspecialchars($activeSession['reconciliation_no']) ?></h3>
                        <span style="font-size: 11px; font-weight: 800; background: #dbeafe; color: #1e40af; padding: 2px 8px; border-radius: 999px;">DRAFT WORKSPACE</span>
                    </div>
                    <p style="font-size: 12px; color: #475569; margin: 3px 0 0 0;">
                        Statement Period: <strong><?= htmlspecialchars($activeSession['statement_start_date']) ?></strong> to <strong><?= htmlspecialchars($activeSession['statement_end_date']) ?></strong>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Live Header Balance Calculation Cards -->
    <div class="rec-kpi-grid">
        <div class="rec-kpi-card stmt">
            <div class="rec-kpi-title">Statement Closing Bal</div>
            <div class="rec-kpi-value">LKR <?= number_format((float)$activeSession['statement_closing_balance'], 2) ?></div>
        </div>
        <div class="rec-kpi-card book">
            <div class="rec-kpi-title">Book Balance (GL)</div>
            <div class="rec-kpi-value">LKR <?= number_format((float)$activeSession['book_balance'], 2) ?></div>
        </div>
        <div class="rec-kpi-card cleared">
            <div class="rec-kpi-title">Cleared Balance</div>
            <div class="rec-kpi-value" id="val_cleared_balance" style="color: #059669;">LKR <?= number_format((float)$activeSession['cleared_balance'], 2) ?></div>
        </div>
        <div class="rec-kpi-card deposits">
            <div class="rec-kpi-title">Outstanding Deposits (+)</div>
            <div class="rec-kpi-value" id="val_out_deposits" style="color: #16a34a;">LKR <?= number_format((float)$activeSession['outstanding_deposits'], 2) ?></div>
        </div>
        <div class="rec-kpi-card payments">
            <div class="rec-kpi-title">Outstanding Payments (-)</div>
            <div class="rec-kpi-value" id="val_out_payments" style="color: #dc2626;">LKR <?= number_format((float)$activeSession['outstanding_payments'], 2) ?></div>
        </div>
        <?php $diffClass = abs((float)$activeSession['reconciliation_difference']) < 0.001 ? 'diff-zero' : 'diff-warn'; ?>
        <div class="rec-kpi-card <?= $diffClass ?>" id="card_diff">
            <div class="rec-kpi-title">Difference</div>
            <div class="rec-kpi-value" id="val_difference" style="font-weight: 800;">LKR <?= number_format((float)$activeSession['reconciliation_difference'], 2) ?></div>
        </div>
    </div>

    <!-- Cleared Transactions Table Panel -->
    <div class="prod-table-panel">
        <div style="padding: 14px 18px; border-bottom: 1px solid #e2e8f0; background: rgba(248, 250, 252, 0.8); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0f172a" stroke-width="2.5"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                <h3 style="font-size: 0.95rem; font-weight: 800; color: #0f172a; margin: 0;">
                    Unreconciled System Transactions (<?= count($sessionItems) ?>)
                </h3>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <button type="button" class="inv-btn inv-btn-ghost" onclick="toggleAllItems(true)" style="padding: 5px 12px; font-size: 11px;">Select All</button>
                <button type="button" class="inv-btn inv-btn-ghost" onclick="toggleAllItems(false)" style="padding: 5px 12px; font-size: 11px;">Deselect All</button>
            </div>
        </div>

        <form id="clearedItemsForm">
            <input type="hidden" id="rec_session_id" value="<?= (int)$activeSession['id'] ?>">
            <div class="prod-table-scroll">
                <table class="prod-table" id="itemsTable">
                    <thead>
                        <tr>
                            <th style="width: 60px; text-align: center;">Cleared</th>
                            <th style="width: 120px;">Date</th>
                            <th style="width: 130px;">Ref / Entry #</th>
                            <th style="width: 120px;">Type</th>
                            <th>Description / Memo</th>
                            <th style="width: 140px; text-align: right;">Deposit (+)</th>
                            <th style="width: 140px; text-align: right;">Payment (-)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sessionItems)): ?>
                            <tr class="prod-empty"><td colspan="7" style="text-align: center; color: #94a3b8; padding: 32px 16px;">No unreconciled transactions available for this period.</td></tr>
                        <?php else: ?>
                            <?php foreach ($sessionItems as $item): ?>
                            <tr>
                                <td style="text-align: center;">
                                    <input type="checkbox" class="item-checkbox" value="<?= (int)$item['journal_line_id'] ?>" <?= !empty($item['is_cleared']) ? 'checked' : '' ?> onchange="handleItemCheckChange()" style="width: 16px; height: 16px; cursor: pointer; accent-color: #2563eb;">
                                </td>
                                <td style="color: #64748b; font-size: 12px; font-weight: 500;"><?= htmlspecialchars($item['entry_date']) ?></td>
                                <td><strong style="font-family: monospace; font-size: 12px; color: #2563eb;"><?= htmlspecialchars($item['entry_number']) ?></strong></td>
                                <td><span style="background: #f1f5f9; padding: 3px 8px; border-radius: 999px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #475569;"><?= htmlspecialchars($item['source_type']) ?></span></td>
                                <td>
                                    <strong style="color: #0f172a;"><?= htmlspecialchars($item['description']) ?></strong>
                                    <?php if (!empty($item['memo'])): ?>
                                        <br><small style="color: #64748b; font-size: 11.5px;"><?= htmlspecialchars($item['memo']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right; font-weight: 800; color: #16a34a;">
                                    <?= (float)$item['debit'] > 0 ? 'LKR ' . number_format((float)$item['debit'], 2) : '-' ?>
                                </td>
                                <td style="text-align: right; font-weight: 800; color: #dc2626;">
                                    <?= (float)$item['credit'] > 0 ? 'LKR ' . number_format((float)$item['credit'], 2) : '-' ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- History Panel: Locked Completed Reconciliations -->
    <div class="prod-table-panel">
        <div style="padding: 14px 18px; border-bottom: 1px solid #e2e8f0; background: rgba(248, 250, 252, 0.8); display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0f172a" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <h3 style="font-size: 0.95rem; font-weight: 800; color: #0f172a; margin: 0;">Locked Completed Reconciliations History</h3>
            </div>
        </div>
        <div class="prod-table-scroll">
            <table class="prod-table">
                <thead>
                    <tr>
                        <th style="width: 140px;">Reconciliation #</th>
                        <th>Statement Period</th>
                        <th>Closing Bal (LKR)</th>
                        <th>Cleared Bal (LKR)</th>
                        <th>Reconciled At</th>
                        <th>Reconciled By</th>
                        <th style="width: 100px; text-align: center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($completedSessions)): ?>
                        <tr class="prod-empty"><td colspan="7" style="text-align: center; color: #94a3b8; padding: 32px 16px;">No completed reconciliation sessions recorded for this bank account yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($completedSessions as $cs): ?>
                        <tr>
                            <td><strong style="font-family: monospace; color: #1e40af; font-size: 12px;"><?= htmlspecialchars($cs['reconciliation_no']) ?></strong></td>
                            <td style="font-weight: 600; color: #334155;"><?= htmlspecialchars($cs['statement_start_date']) ?> to <?= htmlspecialchars($cs['statement_end_date']) ?></td>
                            <td style="font-weight: 700; color: #0f172a;">LKR <?= number_format((float)$cs['statement_closing_balance'], 2) ?></td>
                            <td style="font-weight: 700; color: #16a34a;">LKR <?= number_format((float)$cs['cleared_balance'], 2) ?></td>
                            <td style="color: #64748b; font-size: 12px;"><?= htmlspecialchars($cs['reconciled_at']) ?></td>
                            <td style="font-weight: 600; color: #334155;"><?= htmlspecialchars($cs['reconciler_name'] ?? 'System') ?></td>
                            <td style="text-align: center;">
                                <span class="prod-badge prod-badge-active"><span class="dot"></span>LOCKED</span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Post Bank Adjustment / Charge / Interest -->
<div class="curtiss-modal-backdrop" id="adjustmentModal">
    <div class="curtiss-modal-card">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; padding-bottom: 10px; border-bottom: 1px solid #f1f5f9;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #d97706;"></div>
                <h3 style="font-size: 1.1rem; font-weight: 800; color: #0f172a; margin: 0;">Post Direct Bank Adjustment</h3>
            </div>
            <button type="button" onclick="closeModal('adjustmentModal')" style="background: none; border: none; color: #94a3b8; cursor: pointer; padding: 4px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <form id="adjustmentForm" onsubmit="handlePostAdjustment(event)">
            <input type="hidden" name="bank_account_id" value="<?= $selectedBankId ?>">

            <div style="margin-bottom: 16px;">
                <label class="form-label">Adjustment Type <span style="color: #ef4444;">*</span></label>
                <select class="form-input" name="type" id="adj_type" required style="font-weight: 700;">
                    <option value="bank_charge">Bank Charge / Service Fee (Expense)</option>
                    <option value="bank_interest">Bank Interest Received (Income)</option>
                    <option value="adjustment">Direct Journal Adjustment</option>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label class="form-label">Amount (LKR) <span style="color: #ef4444;">*</span></label>
                    <input type="number" step="0.01" min="0.01" class="form-input" name="amount" required placeholder="0.00" style="font-size: 1.15rem; font-weight: 800;">
                </div>
                <div>
                    <label class="form-label">Direction (for Direct Adj)</label>
                    <select class="form-input" name="direction" style="font-weight: 600;">
                        <option value="out">Bank Deduction (Out)</option>
                        <option value="in">Bank Addition (In)</option>
                    </select>
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <label class="form-label">Description / Memo <span style="color: #ef4444;">*</span></label>
                <input type="text" class="form-input" name="description" required placeholder="e.g. Monthly Account Maintenance Fee">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 14px; border-top: 1px solid #f1f5f9;">
                <button type="button" class="inv-btn inv-btn-ghost" onclick="closeModal('adjustmentModal')">Cancel</button>
                <button type="submit" class="inv-btn inv-btn-primary" id="postAdjBtn">Post Adjustment</button>
            </div>
        </form>
    </div>
</div>

<script>
    function closeModal(id) {
        const m = document.getElementById(id);
        if (m) m.classList.remove('active');
    }

    function openAdjustmentModal() {
        document.getElementById('adjustmentForm').reset();
        const m = document.getElementById('adjustmentModal');
        if (m) m.classList.add('active');
    }

    async function handleStartSession(e) {
        e.preventDefault();
        const btn = document.getElementById('startBtn');
        btn.disabled = true;
        btn.innerText = 'Starting Session...';

        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());

        try {
            const res = await fetch('banking_api.php?action=start_reconciliation', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await res.json();
            if (result.success) {
                alert(result.message);
                location.reload();
            } else {
                alert('Error: ' + result.message);
            }
        } catch (err) {
            alert('Server connection error: ' + err.message);
        } finally {
            btn.disabled = false;
            btn.innerText = 'Start Reconciliation Session';
        }
    }

    async function handleItemCheckChange() {
        const recIdEl = document.getElementById('rec_session_id');
        if (!recIdEl) return;
        const recId = recIdEl.value;
        const checkboxes = document.querySelectorAll('.item-checkbox:checked');
        const clearedIds = Array.from(checkboxes).map(cb => parseInt(cb.value));

        try {
            const res = await fetch('banking_api.php?action=update_cleared_items', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ reconciliation_id: recId, cleared_line_ids: clearedIds })
            });
            const result = await res.json();
            if (result.success) {
                document.getElementById('val_cleared_balance').innerText = 'LKR ' + parseFloat(result.cleared_balance).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
                document.getElementById('val_out_deposits').innerText = 'LKR ' + parseFloat(result.outstanding_deposits).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
                document.getElementById('val_out_payments').innerText = 'LKR ' + parseFloat(result.outstanding_payments).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
                
                const diffVal = parseFloat(result.reconciliation_difference);
                const cardDiff = document.getElementById('card_diff');
                const valDiff = document.getElementById('val_difference');
                valDiff.innerText = 'LKR ' + diffVal.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});

                if (Math.abs(diffVal) < 0.001) {
                    cardDiff.className = 'rec-kpi-card diff-zero';
                } else {
                    cardDiff.className = 'rec-kpi-card diff-warn';
                }
            }
        } catch (err) {
            console.error('Failed to update cleared items', err);
        }
    }

    function toggleAllItems(state) {
        const checkboxes = document.querySelectorAll('.item-checkbox');
        checkboxes.forEach(cb => cb.checked = state);
        handleItemCheckChange();
    }

    async function handlePostAdjustment(e) {
        e.preventDefault();
        const btn = document.getElementById('postAdjBtn');
        btn.disabled = true;
        btn.innerText = 'Posting...';

        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());

        try {
            const res = await fetch('banking_api.php?action=post_adjustment', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await res.json();
            if (result.success) {
                alert(result.message);
                location.reload();
            } else {
                alert('Error: ' + result.message);
            }
        } catch (err) {
            alert('Server connection error: ' + err.message);
        } finally {
            btn.disabled = false;
            btn.innerText = 'Post Adjustment';
        }
    }

    async function handleCompleteReconciliation(recId) {
        if (!confirm('Are you sure you want to complete and lock this reconciliation session? Once completed, it cannot be modified.')) {
            return;
        }

        try {
            const res = await fetch('banking_api.php?action=complete_reconciliation', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ reconciliation_id: recId })
            });
            const result = await res.json();
            if (result.success) {
                alert(result.message);
                location.reload();
            } else {
                alert('Cannot Complete: ' + result.message);
            }
        } catch (err) {
            alert('Server connection error: ' + err.message);
        }
    }
</script>
