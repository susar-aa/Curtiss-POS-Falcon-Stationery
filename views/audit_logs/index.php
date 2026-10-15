<?php
/**
 * Curtiss POS - Audit Logs View (Full Compliance & Security Trail)
 */
?>
<link rel="stylesheet" href="assets/css/curtiss_select.css">
<script src="assets/js/curtiss_select.js"></script>

<style>
/* Module-Specific Theme Styling for Audit Logs */
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
}
.prod-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-family: inherit;
}
.prod-table thead th {
    position: sticky;
    top: 0;
    z-index: 10;
    background: #f8fafc;
    color: #475569;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    padding: 10px 14px;
    border-bottom: 1px solid #e2e8f0;
    text-align: left;
    white-space: nowrap;
}
.prod-table thead th.col-right,
.prod-table td.col-right { text-align: right; }
.prod-table thead th.col-center,
.prod-table td.col-center { text-align: center; }

.prod-table tbody tr {
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.12s ease;
    cursor: pointer;
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
    padding: 8px 14px;
    font-size: 12.5px;
    color: #1e293b;
    vertical-align: middle;
}

/* Theme Action Badges */
.audit-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 9px;
    border-radius: 999px;
    font-size: 10.5px;
    font-weight: 700;
    line-height: 1.2;
    letter-spacing: 0.02em;
    white-space: nowrap;
}
.audit-badge-create  { background: #dcfce7; color: #15803d; border: 1px solid rgba(22,163,74,0.3); }
.audit-badge-update  { background: #e0f2fe; color: #0369a1; border: 1px solid rgba(2,132,199,0.3); }
.audit-badge-delete  { background: #fee2e2; color: #b91c1c; border: 1px solid rgba(220,38,38,0.3); }
.audit-badge-toggle  { background: #fef3c7; color: #b45309; border: 1px solid rgba(217,119,6,0.3); }
.audit-badge-auth    { background: #f3e8ff; color: #6b21a8; border: 1px solid rgba(147,51,234,0.3); }
.audit-badge-generic { background: #f1f5f9; color: #475569; border: 1px solid rgba(100,116,139,0.3); }

.audit-badge .dot {
    width: 6px; height: 6px; border-radius: 50%; display: inline-block;
}
.audit-badge-create  .dot { background: #16a34a; }
.audit-badge-update  .dot { background: #0284c7; }
.audit-badge-delete  .dot { background: #dc2626; }
.audit-badge-toggle  .dot { background: #d97706; }
.audit-badge-auth    .dot { background: #9333ea; }
.audit-badge-generic .dot { background: #64748b; }

/* Buttons */
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
.prod-act-edit:hover {
    background: #e2e8f0;
    color: #0f172a;
}

/* Glassmorphism Modal */
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
                <h1 class="prod-title">System Audit Logs</h1>
            </div>
        </div>

        <!-- Inline Filter Controls Above Table Right Corner (34px height) -->
        <form method="GET" action="audit_logs" id="auditFilterForm" style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin: 0;">
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

            <?php if (!empty($filters['module']) || !empty($filters['action']) || !empty($filters['date_from']) || !empty($filters['date_to']) || !empty($filters['search'])): ?>
                <a href="audit_logs" class="prod-act-btn prod-act-edit" style="height: 34px; box-sizing: border-box; display: inline-flex; align-items: center;">Clear Filters</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Main Table Panel -->
    <div class="prod-table-panel" style="flex: 1; min-height: 0; display: flex; flex-direction: column;">
        <div class="prod-table-scroll">
            <table class="prod-table" id="auditTable">
                <thead>
                    <tr>
                        <th style="width: 70px;"># ID</th>
                        <th style="width: 150px;">Timestamp</th>
                        <th style="width: 140px;">User</th>
                        <th style="width: 120px;">Branch</th>
                        <th style="width: 110px;">Module</th>
                        <th style="width: 190px;">Action Event</th>
                        <th style="width: 90px;">Record ID</th>
                        <th style="width: 110px;">IP Address</th>
                        <th style="text-align: right; width: 100px;">Details</th>
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
                                if (str_contains($actionName, 'CREATED') || str_contains($actionName, 'ADDED') || str_contains($actionName, 'POSTED') || str_contains($actionName, 'RESTORED')) {
                                    $badgeClass = 'audit-badge-create';
                                } elseif (str_contains($actionName, 'UPDATED') || str_contains($actionName, 'MODIFIED')) {
                                    $badgeClass = 'audit-badge-update';
                                } elseif (str_contains($actionName, 'DELETED') || str_contains($actionName, 'CANCELLED') || str_contains($actionName, 'REVERSED')) {
                                    $badgeClass = 'audit-badge-delete';
                                } elseif (str_contains($actionName, 'TOGGLED') || str_contains($actionName, 'STATUS')) {
                                    $badgeClass = 'audit-badge-toggle';
                                } elseif (str_contains($actionName, 'LOGIN') || str_contains($actionName, 'LOGOUT') || str_contains($actionName, 'SESSION')) {
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
                                    <button type="button" class="prod-act-btn prod-act-edit" onclick='viewAuditDetails(<?= json_encode($log, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
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
<div id="auditModal" class="curtiss-modal-backdrop">
    <div class="curtiss-modal-card">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; flex-shrink: 0;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
                <h3 id="modalTitle" style="font-size: 1.1rem; font-weight: 800; color: #0f172a; margin: 0;">Audit Record Details</h3>
            </div>
            <button type="button" onclick="closeAuditModal()" style="background: none; border: none; color: #94a3b8; cursor: pointer; padding: 4px; display: flex; align-items: center; justify-content: center; transition: color 0.15s ease;">
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
            <button type="button" class="prod-act-btn prod-act-edit" onclick="closeAuditModal()">Close Panel</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Attach searchable comboboxes to filters
    if (typeof CurtissSearchableSelect !== 'undefined') {
        const modEl = document.getElementById('filterModule');
        const actEl = document.getElementById('filterAction');
        if (modEl) CurtissSearchableSelect.attach(modEl);
        if (actEl) CurtissSearchableSelect.attach(actEl);
    }
});

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

    document.getElementById('pagStart').textContent = totalItems > 0 ? startIdx + 1 : 0;
    document.getElementById('pagEnd').textContent = endIdx;
    document.getElementById('pagTotal').textContent = totalItems;

    const navContainer = document.getElementById('pagNavBtns');
    navContainer.innerHTML = '';

    const prevBtn = document.createElement('button');
    prevBtn.className = 'prod-act-btn prod-act-edit';
    prevBtn.style.padding = '4px 10px';
    prevBtn.disabled = currentPage === 1;
    prevBtn.innerHTML = `← Prev`;
    prevBtn.onclick = () => { if (currentPage > 1) { currentPage--; updatePagination(); } };
    navContainer.appendChild(prevBtn);

    let startPage = Math.max(1, currentPage - 2);
    let endPage = Math.min(totalPages, startPage + 4);
    if (endPage - startPage < 4) startPage = Math.max(1, endPage - 4);

    for (let p = startPage; p <= endPage; p++) {
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
        pBtn.onclick = () => { currentPage = p; updatePagination(); };
        navContainer.appendChild(pBtn);
    }

    const nextBtn = document.createElement('button');
    nextBtn.className = 'prod-act-btn prod-act-edit';
    nextBtn.style.padding = '4px 10px';
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

/* ── Realtime Search in Floating Bottom Nav Bar ── */
function registerBottomSearch() {
    if (window.CurtissNav && window.CurtissNav.enablePageSearch) {
        window.CurtissNav.enablePageSearch({
            placeholder: 'Search audit logs...',
            onSearch: function(query) {
                filterAuditRealtime(query);
            }
        });
    } else {
        setTimeout(registerBottomSearch, 40);
    }
}
registerBottomSearch();

function filterAuditRealtime(query) {
    const q = query.toLowerCase().trim();
    const rows = document.querySelectorAll('#auditTable tbody tr');
    rows.forEach(tr => {
        if (tr.classList.contains('prod-empty')) return;
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
