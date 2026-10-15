<?php
/**
 * Curtiss POS - Branch Management View
 * Multi-branch registry, contact info for receipts & prints, and status control
 */
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Branch Management</h1>
        <p class="page-subtitle">Configure branch location contacts, receipt phone numbers, addresses, and operational status</p>
    </div>
    <div>
        <button class="btn btn-primary" onclick="openCreateBranchModal()">
            <i class="fa-solid fa-plus me-1"></i> + Add New Branch
        </button>
    </div>
</div>

<?php if (!empty($successMsg)): ?>
    <div class="alert alert-success" style="margin-bottom: 1.25rem;">
        <span><?= View::escape($successMsg) ?></span>
    </div>
<?php endif; ?>

<?php if (!empty($errorMsg)): ?>
    <div class="alert alert-danger" style="background: #fee2e2; border: 1px solid #fca5a5; color: #b91c1c; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1.25rem;">
        <span><?= View::escape($errorMsg) ?></span>
    </div>
<?php endif; ?>

<div class="card">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 70px;">ID</th>
                    <th>Branch Name</th>
                    <th>Branch Code</th>
                    <th>Phone (Receipts)</th>
                    <th>Email</th>
                    <th>Address (Receipts)</th>
                    <th style="text-align: center;">Status</th>
                    <th style="text-align: right; width: 100px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($branches)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 2rem; color: #64748b;">
                            No branches found. Click <strong>+ Add New Branch</strong> to create one.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($branches as $branch): ?>
                        <tr>
                            <td><strong>#<?= (int)$branch['id'] ?></strong></td>
                            <td>
                                <strong style="color: var(--color-primary); font-size: 0.95rem;"><?= View::escape($branch['name']) ?></strong>
                            </td>
                            <td><code style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-weight: 700; color: #0284c7;"><?= View::escape($branch['code']) ?></code></td>
                            <td>
                                <?php if (!empty($branch['phone'])): ?>
                                    <span style="font-weight: 600; color: #0f172a;"><?= View::escape($branch['phone']) ?></span>
                                <?php else: ?>
                                    <span style="color: #94a3b8; font-style: italic;">Not set</span>
                                <?php endif; ?>
                            </td>
                            <td><?= View::escape($branch['email'] ?? '—') ?></td>
                            <td style="max-width: 220px; white-space: normal; line-height: 1.35; font-size: 0.85rem;">
                                <?= View::escape($branch['address'] ?? '—') ?>
                            </td>
                            <td style="text-align: center;">
                                <form method="POST" action="branches.php?action=toggle_status" style="display: inline;" onsubmit="return confirm('Toggle status for <?= View::escape(addslashes($branch['name'])) ?>?');">
                                    <input type="hidden" name="id" value="<?= (int)$branch['id'] ?>">
                                    <?php if ($branch['status'] === 'active'): ?>
                                        <button type="submit" style="background: none; border: none; padding: 0; cursor: pointer;">
                                            <span class="badge badge-success" title="Click to deactivate" style="cursor: pointer;">Active</span>
                                        </button>
                                    <?php else: ?>
                                        <button type="submit" style="background: none; border: none; padding: 0; cursor: pointer;">
                                            <span class="badge badge-danger" title="Click to activate" style="cursor: pointer;">Inactive</span>
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </td>
                            <td style="text-align: right;">
                                <button type="button" class="btn btn-secondary btn-sm" style="padding: 4px 10px; font-size: 0.8rem;" onclick='openEditBranchModal(<?= json_encode($branch) ?>)'>
                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add / Edit Branch Modal -->
<div id="branchModal" style="display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: #fff; width: 100%; max-width: 520px; border-radius: 16px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2); overflow: hidden; animation: modalFadeIn 0.2s ease-out;">
        <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <h3 id="branchModalTitle" style="margin: 0; font-size: 1.15rem; font-weight: 800; color: #0f172a;">Branch Details</h3>
            <button type="button" onclick="closeBranchModal()" style="background: none; border: none; font-size: 1.4rem; line-height: 1; color: #64748b; cursor: pointer;">&times;</button>
        </div>

        <form method="POST" action="branches.php?action=save" onsubmit="handleFormSubmit(this)">
            <input type="hidden" name="id" id="branch_modal_id" value="">
            
            <div style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem;">
                <div class="form-group" style="margin: 0;">
                    <label class="form-label" style="font-weight: 700;">Branch Name <span style="color: #dc2626;">*</span></label>
                    <input type="text" name="name" id="branch_modal_name" class="form-control" placeholder="e.g. Main Branch - Kandy" required>
                </div>

                <div class="form-group" style="margin: 0;">
                    <label class="form-label" style="font-weight: 700;">Branch Code <span style="color: #dc2626;">*</span></label>
                    <input type="text" name="code" id="branch_modal_code" class="form-control" placeholder="e.g. BR-KDY-01" required style="text-transform: uppercase;">
                    <small style="color: #64748b; font-size: 0.76rem;">Unique identifier code for invoices, barcodes, and inventory.</small>
                </div>

                <div class="form-group" style="margin: 0;">
                    <label class="form-label" style="font-weight: 700;">Branch Phone Number (Printed on Receipts)</label>
                    <input type="text" name="phone" id="branch_modal_phone" class="form-control" placeholder="e.g. +94 81 222 3344">
                    <small style="color: #64748b; font-size: 0.76rem;">Used on customer sales receipts, day closing sheets, and purchase orders.</small>
                </div>

                <div class="form-group" style="margin: 0;">
                    <label class="form-label" style="font-weight: 700;">Branch Email Address</label>
                    <input type="email" name="email" id="branch_modal_email" class="form-control" placeholder="e.g. kandy@curtisspos.com">
                </div>

                <div class="form-group" style="margin: 0;">
                    <label class="form-label" style="font-weight: 700;">Branch Physical Address (Printed on Receipts)</label>
                    <textarea name="address" id="branch_modal_address" class="form-control" rows="2" placeholder="e.g. No 123, Peradeniya Road, Kandy"></textarea>
                </div>

                <div class="form-group" style="margin: 0;">
                    <label class="form-label" style="font-weight: 700;">Operational Status</label>
                    <select name="status" id="branch_modal_status" class="form-control">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <div style="padding: 1rem 1.5rem; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" class="btn btn-secondary" onclick="closeBranchModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="branchModalSubmitBtn" style="font-weight: 700;">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Save Branch
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreateBranchModal() {
    document.getElementById('branchModalTitle').innerText = 'Add New Branch';
    document.getElementById('branch_modal_id').value = '';
    document.getElementById('branch_modal_name').value = '';
    document.getElementById('branch_modal_code').value = '';
    document.getElementById('branch_modal_phone').value = '';
    document.getElementById('branch_modal_email').value = '';
    document.getElementById('branch_modal_address').value = '';
    document.getElementById('branch_modal_status').value = 'active';

    const modal = document.getElementById('branchModal');
    modal.style.display = 'flex';
    setTimeout(() => document.getElementById('branch_modal_name').focus(), 50);
}

function openEditBranchModal(b) {
    document.getElementById('branchModalTitle').innerText = 'Edit Branch: ' + (b.name || '');
    document.getElementById('branch_modal_id').value = b.id || '';
    document.getElementById('branch_modal_name').value = b.name || '';
    document.getElementById('branch_modal_code').value = b.code || '';
    document.getElementById('branch_modal_phone').value = b.phone || '';
    document.getElementById('branch_modal_email').value = b.email || '';
    document.getElementById('branch_modal_address').value = b.address || '';
    document.getElementById('branch_modal_status').value = b.status || 'active';

    const modal = document.getElementById('branchModal');
    modal.style.display = 'flex';
    setTimeout(() => document.getElementById('branch_modal_name').focus(), 50);
}

function closeBranchModal() {
    document.getElementById('branchModal').style.display = 'none';
}

function handleFormSubmit(form) {
    const btn = form.querySelector('button[type="submit"]');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...';
    }
}

// Close on backdrop click
document.getElementById('branchModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeBranchModal();
    }
});
</script>
