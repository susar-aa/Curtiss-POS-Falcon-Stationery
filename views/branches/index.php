<?php
/**
 * Curtiss POS - Branch Management View
 * Fixed Screen Viewport, 48px Zebra Rows, Universal Real-Time Floating Search,
 * Interactive Status Badges, Full-Row Click Details Modal, and Trash Bin System.
 */
?>
<meta name="csrf-token" content="<?= htmlspecialchars(Auth::generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">

<style>
/* Table Panel & Standard Sizing */
.prod-table-panel {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    overflow: hidden;
}
.prod-table-scroll {
    overflow-y: auto;
    flex: 1;
    min-height: 0;
}
.prod-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}
.prod-table thead th {
    height: 40px;
    padding: 10px 14px;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #64748b;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    position: sticky;
    top: 0;
    z-index: 10;
    white-space: nowrap;
    text-align: left;
    box-sizing: border-box;
}
.prod-table thead th.col-right,
.prod-table tbody td.col-right {
    text-align: right;
}
.prod-table thead th.col-center,
.prod-table tbody td.col-center {
    text-align: center;
}
.prod-table tbody tr {
    height: 48px;
    min-height: 48px;
    transition: background 0.15s ease;
}
.prod-table tbody tr:nth-child(even) { 
    background: rgba(248, 250, 252, 0.57); 
}
.prod-table tbody tr:nth-child(odd)  { 
    background: #ffffff; 
}
.prod-table tbody tr:hover { 
    background: #f1f5f9 !important; 
}
.prod-table tbody td {
    height: 48px;
    padding: 0 14px;
    font-size: 12.5px;
    color: #1e293b;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
    box-sizing: border-box;
}

/* Action & Filter Buttons */
.inv-btn {
    height: 34px;
    min-height: 34px;
    padding: 0 14px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    cursor: pointer;
    border: 1px solid transparent;
    transition: all 0.2s ease;
    text-decoration: none;
    box-sizing: border-box;
}
.inv-btn-primary {
    background: #0f172a;
    color: #ffffff;
}
.inv-btn-primary:hover {
    background: #1e293b;
    color: #ffffff;
}
.inv-btn-ghost {
    background: #ffffff;
    color: #334155;
    border-color: #cbd5e1;
}
.inv-btn-ghost:hover {
    background: #f8fafc;
    border-color: #94a3b8;
}
.inv-btn-danger {
    color: #dc2626;
    background: #fef2f2;
    border-color: #fca5a5;
}
.inv-btn-danger:hover {
    background: #fee2e2;
    color: #b91c1c;
}
.inv-btn-success {
    color: #16a34a;
    background: #f0fdf4;
    border-color: #86efac;
}
.inv-btn-success:hover {
    background: #dcfce7;
    color: #15803d;
}

/* In-Cell Badges & Buttons */
.prod-badge {
    height: 28px;
    min-height: 28px;
    line-height: 26px;
    padding: 0 12px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.03em;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    box-sizing: border-box;
    white-space: nowrap;
    border: 1px solid transparent;
}
.prod-badge .dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    display: inline-block;
}
.prod-badge-active {
    background: #dcfce7;
    color: #15803d;
    border-color: #bbf7d0;
}
.prod-badge-active .dot {
    background: #16a34a;
}
.prod-badge-inactive {
    background: #fee2e2;
    color: #b91c1c;
    border-color: #fecaca;
}
.prod-badge-inactive .dot {
    background: #dc2626;
}
.prod-badge-code {
    background: #f1f5f9;
    color: #0369a1;
    font-family: monospace;
    font-size: 11.5px;
    font-weight: 700;
    border-radius: 6px;
    padding: 2px 8px;
}

/* Modal Styling */
.curtiss-modal {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    align-items: center;
    justify-content: center;
    padding: 1rem;
}
.curtiss-modal.active {
    display: flex;
}
.curtiss-modal-content {
    background: #ffffff;
    width: 100%;
    max-width: 520px;
    border-radius: 16px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
    overflow: hidden;
    animation: modalFadeIn 0.2s ease-out;
    display: flex;
    flex-direction: column;
    max-height: 90vh;
}
.curtiss-modal-header {
    padding: 1.15rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8fafc;
    flex-shrink: 0;
}
.curtiss-modal-body {
    padding: 1.5rem;
    overflow-y: auto;
    flex: 1;
}
.curtiss-modal-footer {
    padding: 1rem 1.5rem;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 0.75rem;
    flex-shrink: 0;
}

@keyframes modalFadeIn {
    from { opacity: 0; transform: scale(0.97); }
    to { opacity: 1; transform: scale(1); }
}
</style>

<div style="width: 100%; max-width: 100%; height: calc(100vh - 110px); display: flex; flex-direction: column; box-sizing: border-box; overflow: hidden; padding-bottom: 8px;">

    <!-- Page Header (Headline Only & Top Action Pills) -->
    <div class="inv-header" style="flex-shrink: 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 0.85rem;">
        <div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
                <h1 class="inv-title">Branch Management</h1>
            </div>
        </div>

        <!-- Inline Navigation Filters (Active vs Trash Bin) -->
        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            <a href="branches.php?view=active" class="inv-btn <?= ($view === 'active') ? 'inv-btn-primary' : 'inv-btn-ghost' ?>" style="font-size: 11.5px;">
                Active Branches (<?= (int)$activeCount ?>)
            </a>
            <a href="branches.php?view=deleted" class="inv-btn <?= ($view === 'deleted') ? 'inv-btn-danger' : 'inv-btn-ghost' ?>" style="<?= ($view !== 'deleted' && $deletedCount > 0) ? 'color: #dc2626; border-color: #fca5a5; background: #fef2f2;' : '' ?> font-size: 11.5px;">
                Trash Bin (<?= (int)$deletedCount ?>)
            </a>
        </div>
    </div>

    <?php if (!empty($successMsg)): ?>
        <div class="alert alert-success" style="margin-bottom: 0.75rem; padding: 10px 14px; border-radius: 10px; font-size: 12.5px; background: #dcfce7; border: 1px solid #86efac; color: #166534; display: flex; justify-content: space-between; align-items: center;">
            <span><?= View::escape($successMsg) ?></span>
            <button type="button" onclick="this.parentElement.style.display='none'" style="background: none; border: none; font-size: 1.1rem; color: #166534; cursor: pointer;">&times;</button>
        </div>
    <?php endif; ?>

    <?php if (!empty($errorMsg)): ?>
        <div class="alert alert-danger" style="margin-bottom: 0.75rem; padding: 10px 14px; border-radius: 10px; font-size: 12.5px; background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; display: flex; justify-content: space-between; align-items: center;">
            <span><?= View::escape($errorMsg) ?></span>
            <button type="button" onclick="this.parentElement.style.display='none'" style="background: none; border: none; font-size: 1.1rem; color: #991b1b; cursor: pointer;">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Table Panel & Scroll Container -->
    <div class="prod-table-panel" style="flex: 1; min-height: 0; display: flex; flex-direction: column;">
        <div class="prod-table-scroll">
            <table class="prod-table" id="branchTable">
                <thead>
                    <tr>
                        <th style="width: 70px; text-align: left;">ID</th>
                        <th style="text-align: left;">Branch Name</th>
                        <th style="width: 140px; text-align: left;">Branch Code</th>
                        <th style="width: 160px; text-align: left;">Phone (Receipts)</th>
                        <th style="text-align: left;">Email</th>
                        <th style="text-align: left;">Physical Address</th>
                        <th class="col-right" style="width: 80px;">Staff</th>
                        <th class="col-center" style="width: 120px;">Status</th>
                        <th class="col-right" style="width: 140px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="branchTableBody">
                    <?php if (empty($branches)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 2.5rem; color: #64748b; font-size: 13px;">
                                <?= ($view === 'deleted') ? 'Trash Bin is empty. No soft-deleted branches.' : 'No branches registered yet. Click <strong>+ Add New Branch</strong> to create one.' ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($branches as $branch): ?>
                            <tr onclick='openBranchDetails(<?= json_encode($branch, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' style="cursor: pointer;" id="branch-row-<?= (int)$branch['id'] ?>">
                                <td style="text-align: left; font-weight: 800; color: #475569;">
                                    #<?= (int)$branch['id'] ?>
                                </td>
                                <td style="text-align: left;">
                                    <div style="font-weight: 800; color: #0f172a; font-size: 13.5px;"><?= View::escape($branch['name']) ?></div>
                                    <?php if ((int)$branch['id'] === 1): ?>
                                        <span style="font-size: 10px; font-weight: 800; text-transform: uppercase; color: #0284c7; background: #e0f2fe; padding: 1px 6px; border-radius: 4px;">Primary Main</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: left;">
                                    <span class="prod-badge-code"><?= View::escape($branch['code']) ?></span>
                                </td>
                                <td style="text-align: left;">
                                    <?php if (!empty($branch['phone'])): ?>
                                        <span style="font-weight: 700; color: #0f172a;"><?= View::escape($branch['phone']) ?></span>
                                    <?php else: ?>
                                        <span style="color: #94a3b8; font-style: italic;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: left; color: #475569;">
                                    <?= View::escape($branch['email'] ?? '—') ?>
                                </td>
                                <td style="text-align: left; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #475569;">
                                    <?= View::escape($branch['address'] ?? '—') ?>
                                </td>
                                <td class="col-right" style="font-weight: 800; color: #0f172a;">
                                    <?= (int)($branch['staff_count'] ?? 0) ?>
                                </td>
                                <td class="col-center" onclick="event.stopPropagation();">
                                    <?php if ($view === 'deleted'): ?>
                                        <span class="prod-badge prod-badge-inactive" title="Soft-deleted in Trash Bin">
                                            <span class="dot"></span>Deleted
                                        </span>
                                    <?php else: ?>
                                        <?php if ($branch['status'] === 'active'): ?>
                                            <span class="prod-badge prod-badge-active" onclick="toggleBranchStatus(<?= (int)$branch['id'] ?>, 'active', '<?= View::escape(addslashes($branch['name'])) ?>')" style="cursor: pointer;" title="Click to toggle status">
                                                <span class="dot"></span>Active
                                            </span>
                                        <?php else: ?>
                                            <span class="prod-badge prod-badge-inactive" onclick="toggleBranchStatus(<?= (int)$branch['id'] ?>, 'inactive', '<?= View::escape(addslashes($branch['name'])) ?>')" style="cursor: pointer;" title="Click to toggle status">
                                                <span class="dot"></span>Inactive
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td class="col-right" onclick="event.stopPropagation();">
                                    <div style="display: flex; gap: 6px; justify-content: flex-end; align-items: center;">
                                        <?php if ($view === 'deleted'): ?>
                                            <button type="button" class="inv-btn inv-btn-success" style="height: 28px; min-height: 28px; line-height: 26px; padding: 0 10px; font-size: 11px;" onclick='confirmRestoreBranch(<?= (int)$branch['id'] ?>, "<?= View::escape(addslashes($branch['name'])) ?>")'>
                                                Restore
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="inv-btn inv-btn-ghost" style="height: 28px; min-height: 28px; line-height: 26px; padding: 0 10px; font-size: 11px;" onclick='openEditBranchModal(<?= json_encode($branch, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                                Edit
                                            </button>
                                            <?php if ((int)$branch['id'] !== 1): ?>
                                                <button type="button" class="inv-btn inv-btn-danger" style="height: 28px; min-height: 28px; line-height: 26px; padding: 0 10px; font-size: 11px;" onclick='confirmDeleteBranch(<?= (int)$branch['id'] ?>, "<?= View::escape(addslashes($branch['name'])) ?>")'>
                                                    Trash
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Table Footer Count Info -->
        <div style="padding: 10px 16px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; font-size: 11.5px; color: #64748b;">
            <div id="branchTableCounter">
                Showing <strong><?= count($branches) ?></strong> <?= ($view === 'deleted') ? 'deleted branches' : 'active branches' ?>
            </div>
            <div>
                Click any row for comprehensive branch statistics & metrics.
            </div>
        </div>
    </div>
</div>

<!-- Add / Edit Branch Modal -->
<div class="curtiss-modal" id="branchModal">
    <div class="curtiss-modal-content">
        <div class="curtiss-modal-header">
            <span id="branchModalTitle" style="font-weight: 800; font-size: 1.15rem; color: #0f172a;">Branch Details</span>
            <button type="button" onclick="closeBranchModal()" style="background: none; border: none; font-size: 1.5rem; color: #64748b; cursor: pointer;">&times;</button>
        </div>

        <form id="branchForm" onsubmit="handleBranchSubmit(event)">
            <input type="hidden" name="id" id="branch_modal_id" value="">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Auth::generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            
            <div class="curtiss-modal-body">
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <div>
                        <label class="form-label" style="font-size: 11.5px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #475569; display: block; margin-bottom: 4px;">
                            Branch Name <span style="color: #dc2626;">*</span>
                        </label>
                        <input type="text" name="name" id="branch_modal_name" class="form-control" placeholder="e.g. Main Branch - Kandy" required style="width: 100%; height: 38px; padding: 0 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 13px;">
                    </div>

                    <div>
                        <label class="form-label" style="font-size: 11.5px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #475569; display: block; margin-bottom: 4px;">
                            Branch Code <span style="color: #dc2626;">*</span>
                        </label>
                        <input type="text" name="code" id="branch_modal_code" class="form-control" placeholder="e.g. BR-KDY-01" required style="width: 100%; height: 38px; padding: 0 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 13px; text-transform: uppercase;">
                        <small style="color: #64748b; font-size: 11px; margin-top: 2px; display: block;">Unique identifier code for invoices, barcodes, and inventory.</small>
                    </div>

                    <div>
                        <label class="form-label" style="font-size: 11.5px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #475569; display: block; margin-bottom: 4px;">
                            Branch Phone (Printed on Receipts)
                        </label>
                        <input type="text" name="phone" id="branch_modal_phone" class="form-control" placeholder="e.g. +94 81 222 3344" style="width: 100%; height: 38px; padding: 0 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 13px;">
                        <small style="color: #64748b; font-size: 11px; margin-top: 2px; display: block;">Printed on customer receipts, day closing sheets, and purchase orders.</small>
                    </div>

                    <div>
                        <label class="form-label" style="font-size: 11.5px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #475569; display: block; margin-bottom: 4px;">
                            Branch Email Address
                        </label>
                        <input type="email" name="email" id="branch_modal_email" class="form-control" placeholder="e.g. kandy@curtisspos.com" style="width: 100%; height: 38px; padding: 0 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 13px;">
                    </div>

                    <div>
                        <label class="form-label" style="font-size: 11.5px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #475569; display: block; margin-bottom: 4px;">
                            Branch Physical Address (Printed on Receipts)
                        </label>
                        <textarea name="address" id="branch_modal_address" class="form-control" rows="2" placeholder="e.g. No 123, Peradeniya Road, Kandy" style="width: 100%; padding: 8px 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 13px; font-family: inherit; resize: vertical;"></textarea>
                    </div>
                </div>
            </div>

            <div class="curtiss-modal-footer">
                <button type="button" class="inv-btn inv-btn-ghost" onclick="closeBranchModal()">Cancel</button>
                <button type="submit" class="inv-btn inv-btn-primary" id="branchModalSubmitBtn">
                    Save Branch
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Comprehensive Branch Details View -->
<div class="curtiss-modal" id="branchDetailsModal">
    <div class="curtiss-modal-content" style="max-width: 620px;">
        <div class="curtiss-modal-header">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span id="detBranchName" style="font-weight: 800; font-size: 1.15rem; color: #0f172a;">Branch Details</span>
                <span id="detBranchStatusBadge"></span>
            </div>
            <button type="button" onclick="closeModal('branchDetailsModal')" style="background: none; border: none; font-size: 1.5rem; color: #64748b; cursor: pointer;">&times;</button>
        </div>
        <div class="curtiss-modal-body" id="detBranchBody" style="padding: 1.5rem;">
            <div style="text-align: center; color: #64748b; padding: 2rem;">Loading branch statistics...</div>
        </div>
        <div class="curtiss-modal-footer">
            <div id="detBranchActionFooter" style="margin-right: auto; display: flex; gap: 8px;"></div>
            <button type="button" class="inv-btn inv-btn-ghost" onclick="closeModal('branchDetailsModal')">Close</button>
        </div>
    </div>
</div>

<script>
function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function openCreateBranchModal() {
    document.getElementById('branchModalTitle').innerText = 'Add New Branch';
    document.getElementById('branch_modal_id').value = '';
    document.getElementById('branch_modal_name').value = '';
    document.getElementById('branch_modal_code').value = '';
    document.getElementById('branch_modal_phone').value = '';
    document.getElementById('branch_modal_email').value = '';
    document.getElementById('branch_modal_address').value = '';

    const modal = document.getElementById('branchModal');
    modal.classList.add('active');
    setTimeout(() => document.getElementById('branch_modal_name').focus(), 60);
}

function openEditBranchModal(b) {
    document.getElementById('branchModalTitle').innerText = 'Edit Branch: ' + (b.name || '');
    document.getElementById('branch_modal_id').value = b.id || '';
    document.getElementById('branch_modal_name').value = b.name || '';
    document.getElementById('branch_modal_code').value = b.code || '';
    document.getElementById('branch_modal_phone').value = b.phone || '';
    document.getElementById('branch_modal_email').value = b.email || '';
    document.getElementById('branch_modal_address').value = b.address || '';

    const modal = document.getElementById('branchModal');
    modal.classList.add('active');
    setTimeout(() => document.getElementById('branch_modal_name').focus(), 60);
}

function closeBranchModal() {
    document.getElementById('branchModal').classList.remove('active');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

async function handleBranchSubmit(e) {
    e.preventDefault();
    const btn = document.getElementById('branchModalSubmitBtn');
    btn.disabled = true;
    btn.innerText = 'Saving...';

    try {
        const form = document.getElementById('branchForm');
        const formData = new FormData(form);
        formData.append('csrf_token', getCsrfToken());

        const res = await fetch('branches.php?action=save', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const json = await res.json();

        if (json.success) {
            alert(json.message || "Branch saved successfully!");
            window.location.reload();
        } else {
            alert(json.message || "Failed to save branch.");
            btn.disabled = false;
            btn.innerText = 'Save Branch';
        }
    } catch (err) {
        alert("Server error processing branch request.");
        btn.disabled = false;
        btn.innerText = 'Save Branch';
    }
}

async function toggleBranchStatus(id, currentStatus, branchName) {
    const nextStatus = (currentStatus === 'active') ? 'inactive' : 'active';
    if (!confirm(`Are you sure you want to change operational status for "${branchName}" to ${nextStatus.toUpperCase()}?`)) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('csrf_token', getCsrfToken());

        const res = await fetch('branches.php?action=toggle_status', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const json = await res.json();

        if (json.success) {
            window.location.reload();
        } else {
            alert(json.message || "Failed to update branch status.");
        }
    } catch (err) {
        alert("Server communication error updating status.");
    }
}

async function confirmDeleteBranch(id, branchName) {
    if (!confirm(`Move branch "${branchName}" to the Trash Bin?\n\nHistorical transactions, stock ledgers, and audit logs will remain intact.`)) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('csrf_token', getCsrfToken());

        const res = await fetch('branches.php?action=delete', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const json = await res.json();

        if (json.success) {
            window.location.reload();
        } else {
            alert(json.message || "Failed to delete branch.");
        }
    } catch (err) {
        alert("Server communication error moving branch to Trash Bin.");
    }
}

async function confirmRestoreBranch(id, branchName) {
    if (!confirm(`Restore branch "${branchName}" from the Trash Bin back to active operations?`)) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('csrf_token', getCsrfToken());

        const res = await fetch('branches.php?action=restore', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const json = await res.json();

        if (json.success) {
            window.location.reload();
        } else {
            alert(json.message || "Failed to restore branch.");
        }
    } catch (err) {
        alert("Server communication error restoring branch.");
    }
}

async function openBranchDetails(branch) {
    const modal = document.getElementById('branchDetailsModal');
    const title = document.getElementById('detBranchName');
    const badge = document.getElementById('detBranchStatusBadge');
    const body = document.getElementById('detBranchBody');
    const footer = document.getElementById('detBranchActionFooter');

    title.innerText = branch.name || 'Branch Details';
    badge.innerHTML = (branch.status === 'active')
        ? '<span class="prod-badge prod-badge-active"><span class="dot"></span>Active</span>'
        : '<span class="prod-badge prod-badge-inactive"><span class="dot"></span>Inactive</span>';

    body.innerHTML = `
        <div style="text-align: center; color: #64748b; padding: 2rem;">
            <div style="display: inline-block; width: 22px; height: 22px; border: 3px solid #e2e8f0; border-top-color: #0f172a; border-radius: 50%; animation: spin 0.8s linear infinite;"></div>
            <div style="margin-top: 8px; font-size: 12.5px;">Loading metrics...</div>
        </div>
    `;
    footer.innerHTML = '';
    modal.classList.add('active');

    try {
        const res = await fetch(`branches.php?action=get_details&id=${branch.id}`);
        const json = await res.json();

        if (!json.success || !json.branch) {
            body.innerHTML = `<div style="text-align: center; color: #ef4444; padding: 2rem;">${json.message || 'Branch details not available.'}</div>`;
            return;
        }

        const b = json.branch;
        const stats = json.stats || {};

        let dayBadge = '<span style="color: #64748b; font-weight: 700;">No Open Day</span>';
        if (stats.has_active_day && stats.active_day_info) {
            dayBadge = `<span style="color: #16a34a; font-weight: 800;">Open (#${stats.active_day_info.id}) by ${escapeHtml(stats.active_day_info.opened_by)}</span>`;
        }

        body.innerHTML = `
            <!-- Profile Banner -->
            <div style="padding: 14px 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 1.25rem; display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b;">Branch Identification</div>
                    <div style="font-size: 16px; font-weight: 900; color: #0f172a; margin-top: 2px;">${escapeHtml(b.name)}</div>
                    <div style="display: flex; align-items: center; gap: 8px; margin-top: 4px;">
                        <span class="prod-badge-code">${escapeHtml(b.code)}</span>
                        <span style="font-size: 12px; color: #64748b;">Branch ID: <strong>#${b.id}</strong></span>
                    </div>
                </div>
                <div style="text-align: right; font-size: 11.5px; color: #64748b;">
                    <div>Registered: <strong>${escapeHtml(b.created_at || '').substring(0, 10)}</strong></div>
                    <div style="margin-top: 2px;">Business Day: ${dayBadge}</div>
                </div>
            </div>

            <!-- Operational Metrics Grid -->
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 1.25rem;">
                <div style="padding: 12px 14px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px;">
                    <div style="font-size: 10.5px; font-weight: 800; text-transform: uppercase; color: #64748b;">Active Staff</div>
                    <div style="font-size: 18px; font-weight: 900; color: #0f172a; margin-top: 2px;">${stats.staff_count || 0}</div>
                    <div style="font-size: 11px; color: #64748b; margin-top: 2px;">Employees assigned</div>
                </div>
                <div style="padding: 12px 14px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px;">
                    <div style="font-size: 10.5px; font-weight: 800; text-transform: uppercase; color: #64748b;">Total Sales Count</div>
                    <div style="font-size: 18px; font-weight: 900; color: #0f172a; margin-top: 2px;">${stats.sales_count || 0}</div>
                    <div style="font-size: 11px; color: #16a34a; margin-top: 2px;">Invoices processed</div>
                </div>
                <div style="padding: 12px 14px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px;">
                    <div style="font-size: 10.5px; font-weight: 800; text-transform: uppercase; color: #64748b;">Inventory SKUs</div>
                    <div style="font-size: 18px; font-weight: 900; color: #0f172a; margin-top: 2px;">${stats.inventory_skus || 0}</div>
                    <div style="font-size: 11px; color: #64748b; margin-top: 2px;">Active in stock</div>
                </div>
            </div>

            <!-- Receipt & Contact Settings -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px; font-size: 12px;">
                <div style="font-size: 10.5px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; margin-bottom: 8px;">Customer Receipt & Contact Configuration</div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <span style="color: #64748b;">Receipt Phone:</span>
                        <div style="font-weight: 700; color: #0f172a; margin-top: 2px;">${escapeHtml(b.phone || 'Not configured')}</div>
                    </div>
                    <div>
                        <span style="color: #64748b;">Email Address:</span>
                        <div style="font-weight: 700; color: #0f172a; margin-top: 2px;">${escapeHtml(b.email || '—')}</div>
                    </div>
                </div>
                <div style="margin-top: 10px; padding-top: 8px; border-top: 1px solid #e2e8f0;">
                    <span style="color: #64748b;">Printed Physical Address:</span>
                    <div style="font-weight: 700; color: #0f172a; margin-top: 2px; white-space: normal; line-height: 1.4;">${escapeHtml(b.address || '—')}</div>
                </div>
            </div>
        `;

        footer.innerHTML = `
            <button type="button" class="inv-btn inv-btn-ghost" onclick='closeModal("branchDetailsModal"); openEditBranchModal(${JSON.stringify(b)})'>
                Edit Branch
            </button>
            <button type="button" class="inv-btn inv-btn-ghost" onclick='closeModal("branchDetailsModal"); toggleBranchStatus(${b.id}, "${b.status}", "${escapeHtml(addslashes(b.name))}")'>
                Toggle Status
            </button>
        `;
    } catch (err) {
        body.innerHTML = `<div style="text-align: center; color: #ef4444; padding: 2rem;">Error fetching branch details.</div>`;
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function addslashes(str) {
    if (!str) return '';
    return String(str).replace(/[\\"']/g, '\\$&').replace(/\u0000/g, '\\0');
}

// Enter Key Navigation for Add / Edit Modal Form
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('branchForm');
    if (form) {
        const inputs = Array.from(form.querySelectorAll('input:not([type="hidden"]), textarea'));
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

    // Mount Top-Right Floating Actions into #curtiss-floating-header-right
    const floatingRight = document.getElementById('curtiss-floating-header-right');
    if (floatingRight) {
        let btnHtml = '';
        <?php if ($view === 'deleted'): ?>
            btnHtml = `
                <a href="branches.php?view=active" class="inv-btn inv-btn-ghost" style="height: 34px; padding: 0 14px; font-weight: 700;">
                    ← Active Branches
                </a>
            `;
        <?php else: ?>
            btnHtml = `
                <button type="button" class="inv-btn inv-btn-primary" onclick="openCreateBranchModal()" style="height: 34px; padding: 0 16px; font-weight: 800;">
                    + Add New Branch
                </button>
            `;
        <?php endif; ?>
        floatingRight.innerHTML = btnHtml;
    }

    // Universal Floating Search via CurtissNav across EVERY data column
    if (window.CurtissNav && typeof CurtissNav.enablePageSearch === 'function') {
        let debounceTimer = null;
        CurtissNav.enablePageSearch((rawTerm) => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                const term = (rawTerm || '').toLowerCase().trim();
                const rows = document.querySelectorAll('#branchTableBody tr');
                let visibleCount = 0;

                rows.forEach(row => {
                    if (row.cells.length < 8) return; // Skip empty row
                    // Select all cells except the rightmost action cell
                    const dataCells = Array.from(row.querySelectorAll('td:not(.col-right)'));
                    const rowText = dataCells.map(td => td.textContent.toLowerCase()).join(' ');

                    if (!term || rowText.includes(term)) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                const counter = document.getElementById('branchTableCounter');
                if (counter) {
                    counter.innerHTML = `Showing <strong>${visibleCount}</strong> of <strong>${rows.length}</strong> branches`;
                }
            }, 300);
        });
    }
});

// Close modals on backdrop click
document.querySelectorAll('.curtiss-modal').forEach(m => {
    m.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('active');
        }
    });
});
</script>
