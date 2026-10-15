<?php
/**
 * Falcon POS - User Management View
 */
?>
<style>
/* Modal and Form Styling */
.modal-overlay {
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(15, 23, 42, 0.65);
    backdrop-filter: blur(8px);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 1200;
}
.modal-overlay.active { display: flex; }
.modal-box {
    background: #ffffff;
    border-radius: 16px;
    width: 92%;
    max-width: 620px;
    max-height: 90vh;
    overflow-y: auto;
    padding: 1.75rem;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    border: 1px solid rgba(226, 232, 240, 0.9);
}
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.25rem;
    padding-bottom: 0.85rem;
    border-bottom: 1px solid #e2e8f0;
}
.modal-title { font-size: 1.2rem; font-weight: 800; color: #0f172a; }
.modal-close {
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    width: 32px; height: 32px;
    border-radius: 50%;
    font-size: 1.25rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #475569;
}
.modal-close:hover { background: #e2e8f0; color: #0f172a; }

.form-grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}
.form-group {
    margin-bottom: 1rem;
}
.form-label {
    display: block;
    font-size: 0.82rem;
    font-weight: 700;
    color: #334155;
    margin-bottom: 0.35rem;
}
.form-input, .form-select {
    width: 100%;
    height: 40px;
    border: 1.5px solid #cbd5e1;
    border-radius: 8px;
    padding: 0 12px;
    font-size: 0.9rem;
    font-family: inherit;
    color: #0f172a;
    background: #ffffff;
    box-sizing: border-box;
    transition: border-color 0.15s ease;
}
.form-input:focus, .form-select:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}
.branch-pill-box {
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    padding: 0.75rem 1rem;
    background: #f8fafc;
    max-height: 140px;
    overflow-y: auto;
}
.branch-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.4rem 0;
    border-bottom: 1px solid #e2e8f0;
}
.branch-item:last-child { border-bottom: none; }
.branch-item label {
    font-size: 0.85rem;
    font-weight: 600;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}
.primary-radio {
    font-size: 0.75rem;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 4px;
}
.btn-action-sm {
    padding: 4px 10px;
    font-size: 0.75rem;
    font-weight: 700;
    border-radius: 6px;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    cursor: pointer;
    transition: all 0.15s ease;
}
.btn-action-sm:hover {
    background: #f1f5f9;
    border-color: #94a3b8;
}
</style>

<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <div>
        <h1 class="page-title" style="margin: 0; font-size: 1.5rem; font-weight: 800; color: #0f172a;">User Accounts & Access Control</h1>
        <p class="page-subtitle" style="margin: 0.25rem 0 0 0; color: #64748b; font-size: 0.875rem;">Manage user credentials, operational roles, and branch visibility</p>
    </div>
    <div>
        <button type="button" class="btn btn-primary" onclick="openCreateUserModal()" style="display: inline-flex; align-items: center; gap: 6px; padding: 0.5rem 1.25rem; font-weight: 700; border-radius: 8px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Create New User
        </button>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 0.85rem 1.25rem; border-radius: 8px; margin-bottom: 1.25rem; font-size: 0.9rem; font-weight: 600;">
        ✓ <?= View::escape($success) ?>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 0.85rem 1.25rem; border-radius: 8px; margin-bottom: 1.25rem; font-size: 0.9rem; font-weight: 600;">
        ✕ <?= View::escape($error) ?>
    </div>
<?php endif; ?>

<div class="card" style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden;">
    <div class="table-responsive">
        <table class="data-table" style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 1.5px solid #e2e8f0; color: #475569; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.05em;">
                    <th style="padding: 0.75rem 1rem;">ID</th>
                    <th style="padding: 0.75rem 1rem;">Full Name</th>
                    <th style="padding: 0.75rem 1rem;">Username</th>
                    <th style="padding: 0.75rem 1rem;">Role</th>
                    <th style="padding: 0.75rem 1rem;">Assigned Branches</th>
                    <th style="padding: 0.75rem 1rem;">Status</th>
                    <th style="padding: 0.75rem 1rem;">Last Login</th>
                    <th style="padding: 0.75rem 1rem; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="8" style="padding: 2.5rem; text-align: center; color: #64748b;">No user accounts found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9; font-size: 0.88rem;">
                            <td style="padding: 0.75rem 1rem;"><strong>#<?= (int)$u['id'] ?></strong></td>
                            <td style="padding: 0.75rem 1rem;">
                                <div style="font-weight: 700; color: #0f172a;"><?= View::escape($u['full_name']) ?></div>
                                <div style="font-size: 0.75rem; color: #64748b;"><?= View::escape($u['email']) ?><?= !empty($u['phone']) ? ' • ' . View::escape($u['phone']) : '' ?></div>
                            </td>
                            <td style="padding: 0.75rem 1rem;">
                                <code style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-weight: 600; color: #0f172a;"><?= View::escape($u['username']) ?></code>
                            </td>
                            <td style="padding: 0.75rem 1rem;">
                                <span class="badge" style="background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; font-weight: 700; font-size: 0.75rem; padding: 3px 8px; border-radius: 6px;">
                                    <?= View::escape($u['role_name']) ?>
                                </span>
                            </td>
                            <td style="padding: 0.75rem 1rem;">
                                <?php 
                                if (!empty($u['assigned_branches'])) {
                                    $bNames = explode(',', $u['assigned_branches']);
                                    foreach ($bNames as $bn) {
                                        echo '<span class="badge" style="background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; font-size: 0.72rem; padding: 2px 6px; border-radius: 4px; margin-right: 4px; display: inline-block; margin-bottom: 2px;">' . View::escape(trim($bn)) . '</span>';
                                    }
                                } else {
                                    echo '<span style="color: #94a3b8; font-size: 0.8rem;">All Active Branches</span>';
                                }
                                ?>
                            </td>
                            <td style="padding: 0.75rem 1rem;">
                                <?php if ($u['status'] === 'active'): ?>
                                    <span class="badge" style="background: #dcfce7; color: #15803d; font-weight: 700; font-size: 0.75rem; padding: 3px 8px; border-radius: 6px;">Active</span>
                                <?php else: ?>
                                    <span class="badge" style="background: #fee2e2; color: #b91c1c; font-weight: 700; font-size: 0.75rem; padding: 3px 8px; border-radius: 6px;">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 0.75rem 1rem; color: #64748b; font-size: 0.82rem;">
                                <?= !empty($u['last_login_at']) ? View::formatDate($u['last_login_at']) : '<span style="color: #cbd5e1;">Never</span>' ?>
                            </td>
                            <td style="padding: 0.75rem 1rem; text-align: right;">
                                <div style="display: inline-flex; gap: 6px;">
                                    <!-- Reset Password Button -->
                                    <button type="button" class="btn-action-sm" onclick="openResetPasswordModal(<?= (int)$u['id'] ?>, '<?= View::escape(addslashes($u['username'])) ?>')">
                                        Reset Pwd
                                    </button>

                                    <!-- Status Toggle Form -->
                                    <?php if ((int)$u['id'] !== (int)($_SESSION['user_id'] ?? 0)): ?>
                                        <form method="POST" action="users" style="display: inline;" onsubmit="return confirm('Toggle status for user <?= View::escape(addslashes($u['username'])) ?>?');">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                            <button type="submit" class="btn-action-sm" style="color: <?= $u['status'] === 'active' ? '#dc2626' : '#16a34a' ?>;">
                                                <?= $u['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: CREATE NEW USER                                                    -->
<!-- ========================================================================= -->
<div class="modal-overlay" id="createUserModal">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">+ Provision New User Account</div>
            <button type="button" class="modal-close" onclick="closeModal('createUserModal')">&times;</button>
        </div>
        <form method="POST" action="users">
            <input type="hidden" name="action" value="create_user">

            <div class="form-grid-2">
                <div>
                    <label class="form-label">Full Name *</label>
                    <input type="text" class="form-input" name="full_name" required placeholder="e.g. John Doe">
                </div>
                <div>
                    <label class="form-label">Username (Sign In ID) *</label>
                    <input type="text" class="form-input" name="username" required placeholder="e.g. jdoe">
                </div>
            </div>

            <div class="form-grid-2">
                <div>
                    <label class="form-label">Email Address *</label>
                    <input type="email" class="form-input" name="email" required placeholder="e.g. jdoe@falconpos.lk">
                </div>
                <div>
                    <label class="form-label">Phone Number</label>
                    <input type="text" class="form-input" name="phone" placeholder="e.g. +94 77 123 4567">
                </div>
            </div>

            <div class="form-grid-2">
                <div>
                    <label class="form-label">Initial Password * (min. 6 characters)</label>
                    <input type="password" class="form-input" name="password" id="create_user_pwd" required minlength="6" placeholder="••••••••">
                </div>
                <div>
                    <label class="form-label">System Role *</label>
                    <select class="form-select" name="role_id" required>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= (int)$r['id'] ?>" <?= $r['code'] === ROLE_CASHIER ? 'selected' : '' ?>>
                                <?= View::escape($r['name']) ?> (<?= View::escape($r['code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Account Status</label>
                <select class="form-select" name="status">
                    <option value="active" selected>Active (Can Sign In)</option>
                    <option value="inactive">Inactive (Access Suspended)</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Assigned Operating Branches & Primary Branch</label>
                <div class="branch-pill-box">
                    <?php if (empty($branches)): ?>
                        <div style="font-size: 0.8rem; color: #94a3b8;">No branches configured. User will default to system head.</div>
                    <?php else: ?>
                        <?php foreach ($branches as $idx => $b): ?>
                            <div class="branch-item">
                                <label>
                                    <input type="checkbox" name="branch_ids[]" value="<?= (int)$b['id'] ?>" checked>
                                    <?= View::escape($b['name']) ?> <span style="font-size: 0.75rem; color: #64748b;">(<?= View::escape($b['code']) ?>)</span>
                                </label>
                                <label class="primary-radio" title="Mark as initial branch upon login">
                                    <input type="radio" name="primary_branch_id" value="<?= (int)$b['id'] ?>" <?= $idx === 0 ? 'checked' : '' ?>>
                                    Primary
                                </label>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e2e8f0;">
                <button type="button" class="btn" onclick="closeModal('createUserModal')" style="padding: 0.5rem 1.25rem; border: 1px solid #cbd5e1; background: #ffffff; border-radius: 8px; font-weight: 600; cursor: pointer;">
                    Cancel
                </button>
                <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1.5rem; background: #0f172a; color: #ffffff; border: none; border-radius: 8px; font-weight: 700; cursor: pointer;">
                    Create User Account
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: RESET PASSWORD                                                    -->
<!-- ========================================================================= -->
<div class="modal-overlay" id="resetPasswordModal">
    <div class="modal-box" style="max-width: 440px;">
        <div class="modal-header">
            <div class="modal-title">Reset User Password</div>
            <button type="button" class="modal-close" onclick="closeModal('resetPasswordModal')">&times;</button>
        </div>
        <form method="POST" action="users">
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="user_id" id="reset_user_id" value="">

            <p style="font-size: 0.88rem; color: #475569; margin-top: 0;">
                Resetting password for user: <strong id="reset_username_display" style="color: #0f172a;"></strong>
            </p>

            <div class="form-group">
                <label class="form-label">New Password * (min. 6 characters)</label>
                <input type="password" class="form-input" name="new_password" required minlength="6" placeholder="Enter new password">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 1.25rem;">
                <button type="button" class="btn" onclick="closeModal('resetPasswordModal')" style="padding: 0.5rem 1.25rem; border: 1px solid #cbd5e1; background: #ffffff; border-radius: 8px; font-weight: 600; cursor: pointer;">
                    Cancel
                </button>
                <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1.5rem; background: #0f172a; color: #ffffff; border: none; border-radius: 8px; font-weight: 700; cursor: pointer;">
                    Set New Password
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreateUserModal() {
    document.getElementById('createUserModal').classList.add('active');
}

function openResetPasswordModal(userId, username) {
    document.getElementById('reset_user_id').value = userId;
    document.getElementById('reset_username_display').textContent = username;
    document.getElementById('resetPasswordModal').classList.add('active');
}

function closeModal(modalId) {
    const el = document.getElementById(modalId);
    if (el) el.classList.remove('active');
}

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.active').forEach(m => m.classList.remove('active'));
    }
});

// Close modal when clicking outside box
document.querySelectorAll('.modal-overlay').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.classList.remove('active');
        }
    });
});
</script>
