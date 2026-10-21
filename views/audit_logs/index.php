<?php
/**
 * Curtiss POS - Audit Logs View (Full Compliance & Security Trail)
 * Standardized to Curtiss Master Rules:
 * - Fixed screen viewport & internal scrolling (calc(100vh - 110px))
 * - Sticky uppercase table headers
 * - Standard 48px row heights & Curtiss zebra striping (rgba(248, 250, 252, 0.57))
 * - Floating top-right header action bar (#curtiss-floating-header-right) with CSV Export
 * - 34px inline filter controls with CurtissSearchableSelect comboboxes
 * - Superadmin branch selector integration
 * - Theme-matched 28px audit action badges & buttons
 * - Full-row click for audit payload inspection modal
 * - Realtime floating search via CurtissNav.enablePageSearch excluding action columns
 * - Standard pagination limits (25, 50, 75, 100, 500)
 */

$isSuperAdmin = Auth::hasRole(ROLE_SUPER_ADMIN);
$exportQuery = http_build_query(array_merge($filters, ['export' => 'csv']));
?>
<link rel="stylesheet" href="assets/css/curtiss_select.css">
<script src="assets/js/curtiss_select.js"></script>

<!-- Floating Top-Right Action Controls -->
<div id="curtiss-floating-header-right" class="no-print" style="position: fixed; top: 16px; right: 20px; z-index: 1050; display: flex; align-items: center; gap: 8px;">
    <a href="audit_logs?<?= $exportQuery ?>" class="inv-btn inv-btn-ghost" title="Export filtered audit logs to CSV format">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Export CSV
    </a>
</div>

<style>
/* ── Master Theme & Typography ── */
.inv-title {
    font-size: 1.35rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.025em;
    line-height: 1.2;
    margin: 0;
}

/* ── Standard 34px Buttons ── */
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
    font-family: inherit;
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
.inv-btn-danger-ghost {
    background: #fef2f2;
    color: #dc2626;
    border-color: #fca5a5;
}
.inv-btn-danger-ghost:hover {
    background: #fee2e2;
    border-color: #f87171;
}

/* ── Table Panel & Layout ── */
.prod-table-panel {
    background: #ffffff;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}
.prod-table-scroll {
    overflow-y: auto;
    flex: 1;
    min-height: 0;
}
.prod-table {
    width: 100%;
    border-collapse: collapse;
    border-spacing: 0;
    font-family: inherit;
    font-size: 12.5px;
}
.prod-table thead th {
    position: sticky;
    top: 0;
    z-index: 10;
    height: 40px;
    background: #f8fafc;
    color: #64748b;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    padding: 10px 14px;
    border-bottom: 1px solid #e2e8f0;
    text-align: left;
    white-space: nowrap;
    box-sizing: border-box;
}
.prod-table thead th.col-right,
.prod-table td.col-right { text-align: right !important; }
.prod-table thead th.col-center,
.prod-table td.col-center { text-align: center !important; }

/* ── 48px Row Height & Curtiss Zebra Striping ── */
.prod-table tbody tr {
    height: 48px;
    min-height: 48px;
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.12s ease;
    cursor: pointer;
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

/* ── Theme 28px Badges & Action Buttons ── */
.audit-badge {
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
    letter-spacing: 0.03em;
    white-space: nowrap;
    box-sizing: border-box;
    user-select: none;
}
.audit-badge-create  { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
.audit-badge-update  { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
.audit-badge-delete  { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
.audit-badge-toggle  { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
.audit-badge-auth    { background: #f3e8ff; color: #6b21a8; border: 1px solid #e9d5ff; }
.audit-badge-generic { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }

.audit-badge .dot {
    width: 6px; height: 6px; border-radius: 50%; display: inline-block;
}
.audit-badge-create  .dot { background: #16a34a; }
.audit-badge-update  .dot { background: #0284c7; }
.audit-badge-delete  .dot { background: #dc2626; }
.audit-badge-toggle  .dot { background: #d97706; }
.audit-badge-auth    .dot { background: #9333ea; }
.audit-badge-generic .dot { background: #64748b; }

.prod-act-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
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
    box-sizing: border-box;
    white-space: nowrap;
}
.prod-act-edit {
    background: #f1f5f9;
    border-color: #cbd5e1;
    color: #334155;
}
.prod-act-edit:hover {
    background: #e2e8f0;
    color: #0f172a;
}
.prod-act-primary {
    background: #0f172a;
    color: #ffffff;
    border-color: #0f172a;
}

/* ── Modal & JSON Viewer ── */
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
.curtiss-modal-card {
    background: rgba(255, 255, 255, 0.98);
    width: 100%;
    max-width: 680px;
    max-height: 90vh;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.2);
    border: 1px solid rgba(226, 232, 240, 0.9);
    animation: modalSlideUp 0.22s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    display: flex;
    flex-direction: column;
}
@keyframes modalSlideUp {
    from { opacity: 0; transform: translateY(12px) scale(0.98); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}
.audit-json-box {
    background: #0f172a;
    color: #e2e8f0;
    font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
    font-size: 11.5px;
    padding: 12px;
    border-radius: 10px;
    overflow-x: auto;
    max-height: 220px;
    white-space: pre-wrap;
    word-break: break-word;
}
</style>

<div style="width: 100%; max-width: 100%; height: calc(100vh - 110px); display: flex; flex-direction: column; box-sizing: border-box; overflow: hidden; padding-bottom: 8px;">
    <!-- Page Header (Headline Only) -->
    <div class="prod-header" style="flex-shrink: 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 0.85rem;">
        <div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
                <h1 class="inv-title">System Audit Logs</h1>
            </div>
        </div>

        <!-- Inline Filter Controls Above Table Right Corner (34px height) -->
        <form method="GET" action="audit_logs" id="auditFilterForm" style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin: 0;">
            <?php if ($isSuperAdmin && !empty($branches)): ?>
            <div style="display: flex; align-items: center; gap: 5px;">
                <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Branch:</span>
                <select name="branch_id" id="filterBranch" onchange="this.form.submit()" style="height: 34px; min-height: 34px; padding: 0 12px; border-radius: 999px; font-size: 12px; font-weight: 700; background: #fff; border: 1px solid #cbd5e1; color: #0f172a;">
                    <option value="">All Branches</option>
                    <?php foreach ($branches as $b): ?>
                        <option value="<?= (int)$b['id'] ?>" <?= ($filters['branch_id'] == $b['id']) ? 'selected' : '' ?>><?= View::escape($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div style="display: flex; align-items: center; gap: 5px;">
                <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Module:</span>
                <select name="module" id="filterModule" onchange="this.form.submit()" style="height: 34px; min-height: 34px; padding: 0 12px; border-radius: 999px; font-size: 12px; font-weight: 700; background: #fff; border: 1px solid #cbd5e1; color: #0f172a;">
                    <option value="">All Modules</option>
                    <?php foreach ($modules as $m): ?>
                        <option value="<?= View::escape($m) ?>" <?= ($filters['module'] === $m) ? 'selected' : '' ?>><?= View::escape(ucfirst($m)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: flex; align-items: center; gap: 5px;">
                <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Action:</span>
                <select name="action" id="filterAction" onchange="this.form.submit()" style="height: 34px; min-height: 34px; padding: 0 12px; border-radius: 999px; font-size: 12px; font-weight: 700; background: #fff; border: 1px solid #cbd5e1; color: #0f172a;">
                    <option value="">All Actions</option>
                    <?php foreach ($actions as $a): ?>
                        <option value="<?= View::escape($a) ?>" <?= ($filters['action'] === $a) ? 'selected' : '' ?>><?= View::escape($a) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: flex; align-items: center; gap: 5px;">
                <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">From:</span>
                <input type="date" name="date_from" value="<?= View::escape($filters['date_from']) ?>" onchange="this.form.submit()" style="height: 34px; min-height: 34px; padding: 0 10px; border-radius: 999px; font-size: 12px; font-weight: 700; background: #fff; border: 1px solid #cbd5e1; color: #0f172a;">
            </div>

            <div style="display: flex; align-items: center; gap: 5px;">
                <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">To:</span>
                <input type="date" name="date_to" value="<?= View::escape($filters['date_to']) ?>" onchange="this.form.submit()" style="height: 34px; min-height: 34px; padding: 0 10px; border-radius: 999px; font-size: 12px; font-weight: 700; background: #fff; border: 1px solid #cbd5e1; color: #0f172a;">
            </div>

            <?php if (!empty($filters['module']) || !empty($filters['action']) || !empty($filters['branch_id']) || !empty($filters['date_from']) || !empty($filters['date_to']) || !empty($filters['search'])): ?>
                <a href="audit_logs" class="inv-btn inv-btn-danger-ghost" title="Clear all active filters">Clear Filters</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Main Table Panel -->
    <div class="prod-table-panel" style="flex: 1; min-height: 0; display: flex; flex-direction: column;">
        <div class="prod-table-scroll">
            <table class="prod-table" id="auditTable">
                <thead>
                    <tr>
                        <th style="width: 75px;"># ID</th>
                        <th style="width: 150px;">Timestamp</th>
                        <th style="width: 140px;">User</th>
                        <th style="width: 130px;">Branch</th>
                        <th style="width: 110px;">Module</th>
                        <th style="width: 190px;">Action Event</th>
                        <th style="width: 90px;">Record ID</th>
                        <th style="width: 110px;">IP Address</th>
                        <th class="col-right" style="width: 120px;">Details</th>
                    </tr>
                </thead>
                <tbody id="auditTableBody">
                    <?php if (empty($auditLogs)): ?>
                        <tr class="prod-empty">
                            <td colspan="9" style="text-align: center; padding: 40px 20px; color: #94a3b8; font-weight: 600;">
                                No audit log records found matching the current criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($auditLogs as $log): ?>
                            <?php
                                $actionName = strtoupper($log['action']);
                                $badgeClass = 'audit-badge-generic';
                                if (str_contains($actionName, 'CREATED') || str_contains($actionName, 'ADDED') || str_contains($actionName, 'POSTED') || str_contains($actionName, 'RESTORED') || str_contains($actionName, 'SUCCESS')) {
                                    $badgeClass = 'audit-badge-create';
                                } elseif (str_contains($actionName, 'UPDATED') || str_contains($actionName, 'MODIFIED')) {
                                    $badgeClass = 'audit-badge-update';
                                } elseif (str_contains($actionName, 'DELETED') || str_contains($actionName, 'CANCELLED') || str_contains($actionName, 'REVERSED') || str_contains($actionName, 'FAILED')) {
                                    $badgeClass = 'audit-badge-delete';
                                } elseif (str_contains($actionName, 'TOGGLED') || str_contains($actionName, 'STATUS')) {
                                    $badgeClass = 'audit-badge-toggle';
                                } elseif (str_contains($actionName, 'LOGIN') || str_contains($actionName, 'LOGOUT') || str_contains($actionName, 'SESSION') || str_contains($actionName, 'AUTH')) {
                                    $badgeClass = 'audit-badge-auth';
                                }
                            ?>
                            <tr onclick='viewAuditDetails(<?= json_encode($log, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                <td style="font-weight: 800; color: #64748b;">#<?= (int)$log['id'] ?></td>
                                <td style="font-weight: 600; color: #475569; font-size: 12px;"><?= View::escape($log['created_at']) ?></td>
                                <td style="font-weight: 700; color: #0f172a;"><?= View::escape($log['user_name']) ?></td>
                                <td style="font-weight: 600; color: #475569;"><?= View::escape($log['branch_name']) ?></td>
                                <td>
                                    <span style="font-weight: 700; color: #334155; font-size: 11px; text-transform: uppercase; background: #f1f5f9; padding: 2px 8px; border-radius: 6px;">
                                        <?= View::escape($log['module']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="audit-badge <?= $badgeClass ?>">
                                        <span class="dot"></span>
                                        <?= View::escape($log['action']) ?>
                                    </span>
                                </td>
                                <td style="font-weight: 700; color: #64748b;"><?= View::escape($log['record_id'] ?? '—') ?></td>
                                <td style="font-size: 11.5px; color: #64748b; font-family: monospace;"><?= View::escape($log['ip_address'] ?? 'CLI') ?></td>
                                <td class="col-right" onclick="event.stopPropagation();">
                                    <button type="button" class="prod-act-btn prod-act-edit" onclick='event.stopPropagation(); viewAuditDetails(<?= json_encode($log, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                        View Payload
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Table Pagination Footer -->
        <div class="prod-table-footer" style="padding: 10px 16px; border-top: 1px solid #e2e8f0; background: #ffffff; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; flex-shrink: 0;">
            <div style="font-size: 12px; font-weight: 600; color: #64748b;" id="paginationInfo">
                Showing <span id="pagStart">1</span> to <span id="pagEnd">25</span> of <span id="pagTotal">0</span> entries
            </div>

            <div style="display: flex; align-items: center; gap: 14px;">
                <div style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: #64748b; font-weight: 600;">
                    <span>Rows:</span>
                    <select id="pagPageSize" onchange="changePageSize()" style="height: 30px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 8px; font-size: 12px; color: #0f172a; background: #fff; outline: none; font-weight: 600; cursor: pointer;">
                        <option value="25" selected>25</option>
                        <option value="50">50</option>
                        <option value="75">75</option>
                        <option value="100">100</option>
                        <option value="500">500</option>
                    </select>
                </div>

                <div style="display: flex; align-items: center; gap: 4px;" id="pagNavBtns">
                    <!-- Dynamic page buttons -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Glassmorphism Modal for Audit Payload Details -->
<div id="auditModal" class="curtiss-modal-backdrop" onclick="handleBackdropClick(event)">
    <div class="curtiss-modal-card">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; flex-shrink: 0;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
                <h3 id="modalTitle" style="font-size: 1.1rem; font-weight: 800; color: #0f172a; margin: 0;">Audit Record Details</h3>
            </div>
            <button type="button" onclick="closeAuditModal()" style="background: none; border: none; color: #94a3b8; cursor: pointer; padding: 4px; display: flex; align-items: center; justify-content: center; transition: color 0.15s ease;" title="Close Modal (Esc)">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <div style="overflow-y: auto; flex: 1; padding-right: 4px;">
            <!-- Metadata Summary -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 10px; margin-bottom: 18px; background: #f8fafc; padding: 12px; border-radius: 12px; border: 1px solid #e2e8f0;">
                <div>
                    <span style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">Event Action</span>
                    <div id="modalAction" style="font-size: 12px; font-weight: 800; color: #0f172a;"></div>
                </div>
                <div>
                    <span style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">Module</span>
                    <div id="modalModule" style="font-size: 12px; font-weight: 700; color: #334155;"></div>
                </div>
                <div>
                    <span style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">Record ID</span>
                    <div id="modalRecordId" style="font-size: 12px; font-weight: 700; color: #334155;"></div>
                </div>
                <div>
                    <span style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">User</span>
                    <div id="modalUser" style="font-size: 12px; font-weight: 700; color: #0f172a;"></div>
                </div>
                <div>
                    <span style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">Branch</span>
                    <div id="modalBranch" style="font-size: 12px; font-weight: 700; color: #334155;"></div>
                </div>
                <div>
                    <span style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">Timestamp</span>
                    <div id="modalTime" style="font-size: 12px; font-weight: 700; color: #475569;"></div>
                </div>
                <div>
                    <span style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">IP Address</span>
                    <div id="modalIp" style="font-size: 12px; font-weight: 700; color: #475569; font-family: monospace;"></div>
                </div>
            </div>

            <!-- User Agent -->
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 4px;">User Agent / Client</label>
                <div id="modalUserAgent" style="font-size: 11.5px; color: #475569; background: #f1f5f9; padding: 8px 12px; border-radius: 8px; font-family: monospace; word-break: break-all;"></div>
            </div>

            <!-- Old Values Payload -->
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 11px; font-weight: 800; color: #ef4444; text-transform: uppercase; margin-bottom: 4px;">Previous State (Old Values)</label>
                <pre id="modalOldValues" class="audit-json-box">None</pre>
            </div>

            <!-- New Values Payload -->
            <div style="margin-bottom: 10px;">
                <label style="display: block; font-size: 11px; font-weight: 800; color: #16a34a; text-transform: uppercase; margin-bottom: 4px;">New State (New Values)</label>
                <pre id="modalNewValues" class="audit-json-box">None</pre>
            </div>
        </div>

        <div style="display: flex; justify-content: flex-end; padding-top: 14px; border-top: 1px solid #f1f5f9; margin-top: 12px; flex-shrink: 0;">
            <button type="button" class="inv-btn inv-btn-ghost" onclick="closeAuditModal()">Close Panel</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Attach searchable comboboxes to filters
    if (typeof CurtissSearchableSelect !== 'undefined') {
        const modEl = document.getElementById('filterModule');
        const actEl = document.getElementById('filterAction');
        const brEl  = document.getElementById('filterBranch');
        if (modEl) CurtissSearchableSelect.attach(modEl);
        if (actEl) CurtissSearchableSelect.attach(actEl);
        if (brEl)  CurtissSearchableSelect.attach(brEl);
    }
});

// ESC Key listener to close modal
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAuditModal();
    }
});

function handleBackdropClick(e) {
    if (e.target.id === 'auditModal') {
        closeAuditModal();
    }
}

function viewAuditDetails(log) {
    document.getElementById('modalTitle').innerText = 'Audit Record #' + log.id;
    document.getElementById('modalAction').innerText = log.action || 'N/A';
    document.getElementById('modalModule').innerText = log.module ? log.module.toUpperCase() : 'N/A';
    document.getElementById('modalRecordId').innerText = log.record_id || '—';
    document.getElementById('modalUser').innerText = log.user_name || 'System';
    document.getElementById('modalBranch').innerText = log.branch_name || 'Main Branch';
    document.getElementById('modalTime').innerText = log.created_at || '—';
    document.getElementById('modalIp').innerText = log.ip_address || 'CLI';
    document.getElementById('modalUserAgent').innerText = log.user_agent || 'CLI Agent';

    let oldVal = 'None';
    if (log.old_values) {
        try {
            const parsed = typeof log.old_values === 'string' ? JSON.parse(log.old_values) : log.old_values;
            oldVal = JSON.stringify(parsed, null, 2);
        } catch (e) {
            oldVal = log.old_values;
        }
    }
    document.getElementById('modalOldValues').textContent = oldVal;

    let newVal = 'None';
    if (log.new_values) {
        try {
            const parsed = typeof log.new_values === 'string' ? JSON.parse(log.new_values) : log.new_values;
            newVal = JSON.stringify(parsed, null, 2);
        } catch (e) {
            newVal = log.new_values;
        }
    }
    document.getElementById('modalNewValues').textContent = newVal;

    document.getElementById('auditModal').style.display = 'flex';
}

function closeAuditModal() {
    document.getElementById('auditModal').style.display = 'none';
}

/* ── Client-Side Dynamic Table Pagination Controller ── */
let currentPage = 1;
let pageSize = 25;

function updatePagination() {
    const table = document.getElementById('auditTable');
    if (!table) return;

    const allRows = Array.from(table.querySelectorAll('tbody tr')).filter(tr => !tr.classList.contains('prod-empty'));
    const visibleRows = allRows.filter(tr => tr.dataset.searchMatched !== 'false');

    const totalItems = visibleRows.length;
    const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));

    if (currentPage > totalPages) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;

    const startIdx = totalItems > 0 ? (currentPage - 1) * pageSize : 0;
    const endIdx = Math.min(startIdx + pageSize, totalItems);

    allRows.forEach(tr => tr.style.display = 'none');
    for (let i = startIdx; i < endIdx; i++) {
        if (visibleRows[i]) {
            visibleRows[i].style.display = '';
        }
    }

    const startEl = document.getElementById('pagStart');
    const endEl   = document.getElementById('pagEnd');
    const totalEl = document.getElementById('pagTotal');
    if (startEl) startEl.textContent = totalItems > 0 ? startIdx + 1 : 0;
    if (endEl)   endEl.textContent = endIdx;
    if (totalEl) totalEl.textContent = totalItems;

    const navContainer = document.getElementById('pagNavBtns');
    if (!navContainer) return;
    navContainer.innerHTML = '';

    const prevBtn = document.createElement('button');
    prevBtn.type = 'button';
    prevBtn.className = 'prod-act-btn prod-act-edit';
    prevBtn.style.padding = '0 12px';
    prevBtn.disabled = currentPage === 1;
    prevBtn.innerHTML = `← Prev`;
    prevBtn.onclick = () => { if (currentPage > 1) { currentPage--; updatePagination(); } };
    navContainer.appendChild(prevBtn);

    let startPage = Math.max(1, currentPage - 2);
    let endPage = Math.min(totalPages, startPage + 4);
    if (endPage - startPage < 4) startPage = Math.max(1, endPage - 4);

    for (let p = startPage; p <= endPage; p++) {
        const pBtn = document.createElement('button');
        pBtn.type = 'button';
        pBtn.className = 'prod-act-btn ' + (p === currentPage ? 'prod-act-primary' : 'prod-act-edit');
        pBtn.style.padding = '0 10px';
        pBtn.style.minWidth = '28px';
        pBtn.style.justifyContent = 'center';
        if (p === currentPage) {
            pBtn.style.background = '#0f172a';
            pBtn.style.color = '#ffffff';
            pBtn.style.borderColor = '#0f172a';
        }
        pBtn.textContent = p;
        pBtn.onclick = () => { currentPage = p; updatePagination(); };
        navContainer.appendChild(pBtn);
    }

    const nextBtn = document.createElement('button');
    nextBtn.type = 'button';
    nextBtn.className = 'prod-act-btn prod-act-edit';
    nextBtn.style.padding = '0 12px';
    nextBtn.disabled = currentPage === totalPages;
    nextBtn.innerHTML = `Next →`;
    nextBtn.onclick = () => { if (currentPage < totalPages) { currentPage++; updatePagination(); } };
    navContainer.appendChild(nextBtn);
}

function changePageSize() {
    pageSize = parseInt(document.getElementById('pagPageSize').value) || 25;
    currentPage = 1;
    updatePagination();
}

document.addEventListener('DOMContentLoaded', updatePagination);

/* ── Realtime Search in Floating Bottom Nav Bar (Debounced 300ms) ── */
let searchDebounceTimer = null;
function registerBottomSearch() {
    if (window.CurtissNav && window.CurtissNav.enablePageSearch) {
        window.CurtissNav.enablePageSearch({
            placeholder: 'Search audit logs (ID, User, Branch, Action, IP)...',
            onSearch: function(query) {
                clearTimeout(searchDebounceTimer);
                searchDebounceTimer = setTimeout(() => {
                    filterAuditRealtime(query);
                }, 300);
            }
        });
    } else {
        setTimeout(registerBottomSearch, 40);
    }
}
registerBottomSearch();

function filterAuditRealtime(query) {
    const q = (query || '').toLowerCase().trim();
    const rows = document.querySelectorAll('#auditTable tbody tr');
    rows.forEach(tr => {
        if (tr.classList.contains('prod-empty')) return;
        // Search all columns except the col-right actions column
        const searchableCells = Array.from(tr.querySelectorAll('td:not(.col-right)'));
        const text = searchableCells.map(td => td.textContent).join(' ').toLowerCase();
        if (!q || text.includes(q)) {
            tr.dataset.searchMatched = 'true';
        } else {
            tr.dataset.searchMatched = 'false';
        }
    });
    currentPage = 1;
    updatePagination();
}
</script>
