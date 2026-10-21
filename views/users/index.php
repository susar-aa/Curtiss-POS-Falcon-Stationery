<?php
/**
 * Curtiss POS - User Accounts & Branch Assignments View
 * Fixed Screen Viewport, 48px Zebra Rows, Universal Real-Time Floating Search,
 * Interactive Status Badges, Full-Row Click Details Modal, and Trash Bin System.
 */
?>
<meta name="csrf-token" content="<?= htmlspecialchars(Auth::generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">

<!-- Floating Top-Right Action Controls (Rule 19) -->
<div id="curtiss-floating-header-right" class="no-print" style="position: fixed; top: 16px; right: 20px; z-index: 1050; display: flex; align-items: center; gap: 8px;">
    <?php if ($view === 'deleted'): ?>
        <a href="users.php?view=active" class="inv-btn inv-btn-ghost" style="height: 34px; padding: 0 14px; font-weight: 700;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            <span>← Active Users</span>
        </a>
    <?php else: ?>
        <button type="button" class="inv-btn inv-btn-primary" onclick="openCreateUserModal()" style="height: 34px; padding: 0 16px; font-weight: 800; box-shadow: 0 2px 5px rgba(0,0,0,0.12);">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            <span>+ Add New User</span>
        </button>
    <?php endif; ?>
</div>

<link rel="stylesheet" href="assets/css/curtiss_select.css">
<script src="assets/js/curtiss_select.js"></script>

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
.prod-badge-role {
    background: #f1f5f9;
    color: #0f172a;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 3px 8px;
    font-size: 11px;
    font-weight: 800;
    display: inline-block;
}
.prod-badge-branch {
    background: #f0fdf4;
    color: #166534;
    border: 1px solid #bbf7d0;
    border-radius: 6px;
    padding: 2px 7px;
    font-size: 11px;
    font-weight: 700;
    display: inline-block;
    margin-right: 4px;
    margin-bottom: 2px;
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
    box-sizing: border-box;
}
.curtiss-modal.active {
    display: flex;
}
.curtiss-modal-content {
    background: #ffffff;
    width: 100%;
    max-width: 640px;
    border-radius: 16px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.25);
    overflow: hidden;
    animation: modalFadeIn 0.2s ease-out;
    display: flex;
    flex-direction: column;
    max-height: 88vh;
    box-sizing: border-box;
}
#userForm {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-height: 0;
    overflow: hidden;
    margin: 0;
}
.curtiss-modal-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8fafc;
    flex-shrink: 0;
}
.curtiss-modal-body {
    padding: 1.25rem 1.5rem;
    overflow-y: auto;
    flex: 1;
    min-height: 0;
    box-sizing: border-box;
}
.curtiss-modal-body::-webkit-scrollbar {
    width: 6px;
}
.curtiss-modal-body::-webkit-scrollbar-track {
    background: #f1f5f9;
}
.curtiss-modal-body::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}
.curtiss-modal-body::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
.curtiss-modal-footer {
    padding: 0.85rem 1.5rem;
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

    <!-- Page Header (Headline Only & Inline Filter Controls) -->
    <div class="inv-header" style="flex-shrink: 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 0.85rem;">
        <div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
                <h1 class="inv-title">User Accounts & Branch Assignments</h1>
            </div>
        </div>

        <!-- Inline Navigation Filters (Active vs Trash Bin + Role Selector + Add User) -->
        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            <a href="users.php?view=active" class="inv-btn <?= ($view === 'active') ? 'inv-btn-primary' : 'inv-btn-ghost' ?>" style="font-size: 11.5px;">
                Active Users (<?= (int)$activeCount ?>)
            </a>
            <a href="users.php?view=deleted" class="inv-btn <?= ($view === 'deleted') ? 'inv-btn-danger' : 'inv-btn-ghost' ?>" style="<?= ($view !== 'deleted' && $deletedCount > 0) ? 'color: #dc2626; border-color: #fca5a5; background: #fef2f2;' : '' ?> font-size: 11.5px;">
                Trash Bin (<?= (int)$deletedCount ?>)
            </a>

            <!-- Role Filter Combobox -->
            <div style="display: flex; align-items: center; gap: 6px; margin-left: 4px;">
                <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Role:</span>
                <select id="filterUserRole" onchange="filterUserTableByRole(this.value)" style="height: 34px; min-height: 34px; padding: 0 12px; border-radius: 999px; font-size: 12px; font-weight: 700; border: 1px solid #cbd5e1; background: #ffffff;">
                    <option value="">All Roles</option>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= htmlspecialchars($r['name']) ?>"><?= htmlspecialchars($r['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($view !== 'deleted'): ?>
                <button type="button" class="inv-btn inv-btn-primary" onclick="openCreateUserModal()" style="height: 34px; padding: 0 14px; font-weight: 800; margin-left: 2px;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>+ Add User</span>
                </button>
            <?php endif; ?>
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
            <table class="prod-table" id="usersTable">
                <thead>
                    <tr>
                        <th style="width: 70px; text-align: left;">ID</th>
                        <th style="text-align: left;">Full Name</th>
                        <th style="width: 130px; text-align: left;">Username</th>
                        <th style="text-align: left;">Email / Phone</th>
                        <th style="width: 130px; text-align: left;">Role</th>
                        <th style="text-align: left;">Assigned Branches</th>
                        <th class="col-center" style="width: 110px;">Status</th>
                        <th style="width: 140px; text-align: left;">Last Login</th>
                        <th class="col-right" style="width: 130px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="usersTableBody">
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 2.5rem; color: #64748b; font-size: 13px;">
                                <?= ($view === 'deleted') ? 'Trash Bin is empty. No soft-deleted user accounts.' : 'No users found. Click <strong>+ Create New User</strong> to provision one.' ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                            <tr onclick='openUserDetails(<?= json_encode($u, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' style="cursor: pointer;" id="user-row-<?= (int)$u['id'] ?>" data-role="<?= htmlspecialchars($u['role_name']) ?>">
                                <td style="text-align: left; font-weight: 800; color: #475569;">
                                    #<?= (int)$u['id'] ?>
                                </td>
                                <td style="text-align: left;">
                                    <div style="font-weight: 800; color: #0f172a; font-size: 13.5px;"><?= View::escape($u['full_name']) ?></div>
                                    <?php if ((int)$u['id'] === (int)$currentUserId): ?>
                                        <span style="font-size: 10px; font-weight: 800; text-transform: uppercase; color: #0284c7; background: #e0f2fe; padding: 1px 6px; border-radius: 4px;">You</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: left;">
                                    <code style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-weight: 700; color: #0369a1;"><?= View::escape($u['username']) ?></code>
                                </td>
                                <td style="text-align: left;">
                                    <div style="color: #334155; font-weight: 600;"><?= View::escape($u['email'] ?? '—') ?></div>
                                    <?php if (!empty($u['phone'])): ?>
                                        <div style="font-size: 11px; color: #64748b;"><?= View::escape($u['phone']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: left;">
                                    <span class="prod-badge-role"><?= View::escape($u['role_name']) ?></span>
                                </td>
                                <td style="text-align: left;">
                                    <?php 
                                    if (!empty($u['assigned_branches'])) {
                                        $bList = explode(',', $u['assigned_branches']);
                                        foreach ($bList as $bn) {
                                            echo '<span class="prod-badge-branch">' . View::escape(trim($bn)) . '</span>';
                                        }
                                    } else {
                                        echo '<span style="color: #94a3b8; font-style: italic;">All (Unrestricted)</span>';
                                    }
                                    ?>
                                </td>
                                <td class="col-center" onclick="event.stopPropagation();">
                                    <?php if ($view === 'deleted'): ?>
                                        <span class="prod-badge prod-badge-inactive" title="Soft-deleted in Trash Bin">
                                            <span class="dot"></span>Deleted
                                        </span>
                                    <?php else: ?>
                                        <?php if ($u['status'] === 'active'): ?>
                                            <span class="prod-badge prod-badge-active" onclick="toggleUserStatus(<?= (int)$u['id'] ?>, 'active', '<?= View::escape(addslashes($u['full_name'])) ?>')" style="cursor: pointer;" title="Click to toggle status">
                                                <span class="dot"></span>Active
                                            </span>
                                        <?php else: ?>
                                            <span class="prod-badge prod-badge-inactive" onclick="toggleUserStatus(<?= (int)$u['id'] ?>, 'inactive', '<?= View::escape(addslashes($u['full_name'])) ?>')" style="cursor: pointer;" title="Click to toggle status">
                                                <span class="dot"></span>Inactive
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: left; font-size: 11.5px; color: #64748b;">
                                    <?= !empty($u['last_login_at']) ? View::escape(substr($u['last_login_at'], 0, 16)) : 'Never' ?>
                                </td>
                                <td class="col-right" onclick="event.stopPropagation();">
                                    <div style="display: flex; gap: 6px; justify-content: flex-end; align-items: center;">
                                        <?php if ($view === 'deleted'): ?>
                                            <button type="button" class="inv-btn inv-btn-success" style="height: 28px; min-height: 28px; line-height: 26px; padding: 0 10px; font-size: 11px;" onclick='confirmRestoreUser(<?= (int)$u['id'] ?>, "<?= View::escape(addslashes($u['full_name'])) ?>")'>
                                                Restore
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="inv-btn inv-btn-ghost" style="height: 28px; min-height: 28px; line-height: 26px; padding: 0 10px; font-size: 11px;" onclick='openEditUserModal(<?= json_encode($u, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                                Edit
                                            </button>
                                            <?php if ((int)$u['id'] !== 1 && (int)$u['id'] !== (int)$currentUserId): ?>
                                                <button type="button" class="inv-btn inv-btn-danger" style="height: 28px; min-height: 28px; line-height: 26px; padding: 0 10px; font-size: 11px;" onclick='confirmDeleteUser(<?= (int)$u['id'] ?>, "<?= View::escape(addslashes($u['full_name'])) ?>")'>
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
            <div id="usersTableCounter">
                Showing <strong><?= count($users) ?></strong> <?= ($view === 'deleted') ? 'deleted accounts' : 'active user accounts' ?>
            </div>
            <div>
                Click any row for comprehensive user profile & branch assignments.
            </div>
        </div>
    </div>
</div>

<!-- Modal: Add / Edit User Account -->
<div class="curtiss-modal" id="userModal">
    <div class="curtiss-modal-content">
        <div class="curtiss-modal-header">
            <span id="userModalTitle" style="font-weight: 800; font-size: 1.15rem; color: #0f172a;">User Account</span>
            <button type="button" onclick="closeUserModal()" style="background: none; border: none; font-size: 1.5rem; color: #64748b; cursor: pointer; line-height: 1;">&times;</button>
        </div>

        <form id="userForm" onsubmit="handleUserSubmit(event)">
            <input type="hidden" name="id" id="user_modal_id" value="">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Auth::generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">

            <div class="curtiss-modal-body">
                <div style="display: flex; flex-direction: column; gap: 0.9rem;">
                    <!-- Full Name & Username -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div>
                            <label class="form-label" style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #475569; display: block; margin-bottom: 4px;">
                                Full Name <span style="color: #dc2626;">*</span>
                            </label>
                            <input type="text" name="full_name" id="user_modal_full_name" class="form-control" placeholder="e.g. Ruwan Perera" required style="width: 100%; height: 38px; padding: 0 12px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 13px; font-weight: 600; box-sizing: border-box;">
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #475569; display: block; margin-bottom: 4px;">
                                Username <span style="color: #dc2626;">*</span>
                            </label>
                            <input type="text" name="username" id="user_modal_username" class="form-control" placeholder="e.g. ruwan.p" required style="width: 100%; height: 38px; padding: 0 12px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 13px; font-weight: 600; box-sizing: border-box;">
                        </div>
                    </div>

                    <!-- Email & Phone -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div>
                            <label class="form-label" style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #475569; display: block; margin-bottom: 4px;">
                                Email Address
                            </label>
                            <input type="email" name="email" id="user_modal_email" class="form-control" placeholder="e.g. ruwan@curtisspos.com" style="width: 100%; height: 38px; padding: 0 12px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 13px; box-sizing: border-box;">
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #475569; display: block; margin-bottom: 4px;">
                                Phone Number
                            </label>
                            <input type="text" name="phone" id="user_modal_phone" class="form-control" placeholder="e.g. +94 77 123 4567" style="width: 100%; height: 38px; padding: 0 12px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 13px; box-sizing: border-box;">
                        </div>
                    </div>

                    <!-- Role Selection (Compact Pill Checkboxes) -->
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                            <label class="form-label" style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #475569; margin-bottom: 0;">
                                Assigned System Roles <span style="color: #dc2626;">*</span>
                            </label>
                            <span style="font-size: 10.5px; color: #64748b; font-weight: 600;">Select one or multiple</span>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 8px;" id="roleCheckboxContainer">
                            <?php foreach ($roles as $r): ?>
                                <label style="display: flex; align-items: center; gap: 7px; cursor: pointer; background: #f8fafc; padding: 7px 10px; border-radius: 8px; border: 1.5px solid #e2e8f0; font-size: 12px; transition: all 0.15s ease; user-select: none;">
                                    <input type="checkbox" name="role_ids[]" value="<?= (int)$r['id'] ?>" class="user-role-chk" id="ur_chk_<?= (int)$r['id'] ?>" style="width: 15px; height: 15px; accent-color: #0f172a; cursor: pointer;">
                                    <span style="font-weight: 700; color: #1e293b;"><?= htmlspecialchars($r['name']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Credentials Card: 5-Digit Security PIN & Password Side-by-Side -->
                    <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 12px 14px;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                            <!-- 5-Digit Security PIN -->
                            <div>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                    <label class="form-label" style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #475569; margin-bottom: 0;">
                                        5-Digit PIN <span id="pinRequiredStar" style="color: #dc2626;">*</span>
                                    </label>
                                    <button type="button" onclick="toggleModalPinVisibility()" id="togglePinBtn" style="background:none; border:none; color:#2563eb; font-size:11px; cursor:pointer; font-weight:700; padding:0;">Show</button>
                                </div>
                                <input type="password" name="pin" id="user_modal_pin" class="form-control" placeholder="12345 (5 digits)" maxlength="5" pattern="[0-9]{5}" inputmode="numeric" style="width: 100%; height: 38px; padding: 0 12px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 13px; font-family: monospace; letter-spacing: 2px; font-weight: 700; box-sizing: border-box; background: #ffffff;">
                                <small id="pinHelpText" style="color: #64748b; font-size: 10.5px; margin-top: 3px; display: block; line-height: 1.3;">5-digit PIN for touchscreen & fast login.</small>
                            </div>

                            <!-- Password -->
                            <div>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                    <label class="form-label" style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #475569; margin-bottom: 0;">
                                        Password <span id="pwdRequiredStar" style="color: #dc2626;">*</span>
                                    </label>
                                    <button type="button" onclick="toggleModalPasswordVisibility()" id="togglePwdBtn" style="background:none; border:none; color:#2563eb; font-size:11px; cursor:pointer; font-weight:700; padding:0;">Show</button>
                                </div>
                                <input type="password" name="password" id="user_modal_password" class="form-control" placeholder="Min 6 characters..." style="width: 100%; height: 38px; padding: 0 12px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 13px; box-sizing: border-box; background: #ffffff;">
                                <small id="pwdHelpText" style="color: #64748b; font-size: 10.5px; margin-top: 3px; display: block; line-height: 1.3;">Leave blank to keep unchanged.</small>
                            </div>
                        </div>
                    </div>

                    <!-- Multi-Branch Assignment Card -->
                    <div style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 12px 14px;">
                        <label class="form-label" style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #475569; display: block; margin-bottom: 6px;">
                            Assigned Operating Branches
                        </label>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 8px; font-size: 12px;" id="branchCheckboxContainer">
                            <?php foreach ($allBranches as $ab): ?>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; background: #f8fafc; padding: 6px 10px; border-radius: 6px; border: 1px solid #e2e8f0; transition: background 0.15s ease;">
                                    <input type="checkbox" name="branch_ids[]" value="<?= (int)$ab['id'] ?>" class="user-branch-chk" id="ub_chk_<?= (int)$ab['id'] ?>" onchange="updatePrimaryBranchOptions()" style="width: 15px; height: 15px; accent-color: #0f172a; cursor: pointer;">
                                    <span style="font-weight: 600; color: #1e293b;"><?= htmlspecialchars($ab['name']) ?> <span style="color: #64748b; font-size: 11px;">(<?= htmlspecialchars($ab['code']) ?>)</span></span>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <!-- Primary Operating Branch -->
                        <div style="margin-top: 10px; padding-top: 8px; border-top: 1px dashed #e2e8f0; display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                            <label class="form-label" style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 0; white-space: nowrap;">
                                Primary Branch (Default):
                            </label>
                            <select name="primary_branch_id" id="user_modal_primary_branch" style="flex: 1; min-width: 180px; height: 34px; padding: 0 10px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 12px; font-weight: 700; background: #ffffff;">
                                <option value="">-- Choose Primary Branch --</option>
                                <?php foreach ($allBranches as $ab): ?>
                                    <option value="<?= (int)$ab['id'] ?>"><?= htmlspecialchars($ab['name']) ?> (<?= htmlspecialchars($ab['code']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="curtiss-modal-footer">
                <button type="button" class="inv-btn inv-btn-ghost" onclick="closeUserModal()">Cancel</button>
                <button type="submit" class="inv-btn inv-btn-primary" id="userModalSubmitBtn">
                    Save User Account
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Comprehensive User Details View -->
<div class="curtiss-modal" id="userDetailsModal">
    <div class="curtiss-modal-content" style="max-width: 620px;">
        <div class="curtiss-modal-header">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span id="detUserFullName" style="font-weight: 800; font-size: 1.15rem; color: #0f172a;">User Profile</span>
                <span id="detUserStatusBadge"></span>
            </div>
            <button type="button" onclick="closeModal('userDetailsModal')" style="background: none; border: none; font-size: 1.5rem; color: #64748b; cursor: pointer;">&times;</button>
        </div>
        <div class="curtiss-modal-body" id="detUserBody" style="padding: 1.5rem;">
            <div style="text-align: center; color: #64748b; padding: 2rem;">Loading user details...</div>
        </div>
        <div class="curtiss-modal-footer">
            <div id="detUserActionFooter" style="margin-right: auto; display: flex; gap: 8px;"></div>
            <button type="button" class="inv-btn inv-btn-ghost" onclick="closeModal('userDetailsModal')">Close</button>
        </div>
    </div>
</div>

<script>
function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function toggleModalPinVisibility() {
    const p = document.getElementById('user_modal_pin');
    const btn = document.getElementById('togglePinBtn');
    if (p.type === 'password') {
        p.type = 'text';
        if (btn) btn.innerText = 'Hide';
    } else {
        p.type = 'password';
        if (btn) btn.innerText = 'Show';
    }
}

function toggleModalPasswordVisibility() {
    const p = document.getElementById('user_modal_password');
    const btn = document.getElementById('togglePwdBtn');
    if (p.type === 'password') {
        p.type = 'text';
        if (btn) btn.innerText = 'Hide';
    } else {
        p.type = 'password';
        if (btn) btn.innerText = 'Show';
    }
}

function openCreateUserModal() {
    document.getElementById('userModalTitle').innerText = 'Create New User Account';
    document.getElementById('user_modal_id').value = '';
    document.getElementById('user_modal_full_name').value = '';
    document.getElementById('user_modal_username').value = '';
    document.getElementById('user_modal_email').value = '';
    document.getElementById('user_modal_phone').value = '';
    
    // PIN Setup
    const pinInput = document.getElementById('user_modal_pin');
    pinInput.value = '';
    pinInput.type = 'password';
    pinInput.required = true;
    document.getElementById('pinRequiredStar').style.display = 'inline';
    document.getElementById('pinHelpText').innerText = 'Required 5-digit PIN for new user (e.g. 12345).';
    const togglePinBtn = document.getElementById('togglePinBtn');
    if (togglePinBtn) togglePinBtn.innerText = 'Show';

    // Password Setup
    const pwdInput = document.getElementById('user_modal_password');
    pwdInput.value = '';
    pwdInput.type = 'password';
    pwdInput.required = true;
    document.getElementById('pwdRequiredStar').style.display = 'inline';
    document.getElementById('pwdHelpText').innerText = 'Required for new user (minimum 6 characters).';
    const togglePwdBtn = document.getElementById('togglePwdBtn');
    if (togglePwdBtn) togglePwdBtn.innerText = 'Show';

    // Reset role checkboxes: default Cashier (ID 4) checked
    document.querySelectorAll('.user-role-chk').forEach(chk => {
        chk.checked = (chk.value === '4');
    });

    // Reset branch checkboxes: default Main Branch #1 checked
    document.querySelectorAll('.user-branch-chk').forEach(chk => {
        chk.checked = (chk.value === '1');
    });
    document.getElementById('user_modal_primary_branch').value = '1';

    const modal = document.getElementById('userModal');
    modal.classList.add('active');
    setTimeout(() => document.getElementById('user_modal_full_name').focus(), 60);
}

function openEditUserModal(u) {
    document.getElementById('userModalTitle').innerText = 'Edit User: ' + (u.full_name || '');
    document.getElementById('user_modal_id').value = u.id || '';
    document.getElementById('user_modal_full_name').value = u.full_name || '';
    document.getElementById('user_modal_username').value = u.username || '';
    document.getElementById('user_modal_email').value = u.email || '';
    document.getElementById('user_modal_phone').value = u.phone || '';
    
    // PIN Setup
    const pinInput = document.getElementById('user_modal_pin');
    pinInput.value = '';
    pinInput.type = 'password';
    pinInput.required = false;
    document.getElementById('pinRequiredStar').style.display = 'none';
    document.getElementById('pinHelpText').innerText = 'Leave blank to keep current 5-digit PIN, or enter 5 digits to reset.';
    const togglePinBtn = document.getElementById('togglePinBtn');
    if (togglePinBtn) togglePinBtn.innerText = 'Show';

    // Password Setup
    const pwdInput = document.getElementById('user_modal_password');
    pwdInput.value = '';
    pwdInput.type = 'password';
    pwdInput.required = false;
    document.getElementById('pwdRequiredStar').style.display = 'none';
    document.getElementById('pwdHelpText').innerText = 'Leave blank to keep existing password unchanged.';
    const togglePwdBtn = document.getElementById('togglePwdBtn');
    if (togglePwdBtn) togglePwdBtn.innerText = 'Show';

    // Check assigned roles
    const assignedRoleIds = u.assigned_role_ids || (u.role_id ? [parseInt(u.role_id)] : []);
    document.querySelectorAll('.user-role-chk').forEach(chk => {
        chk.checked = assignedRoleIds.map(String).includes(chk.value) || (String(u.role_id) === chk.value);
    });

    // Check assigned branches
    const assignedIds = (u.assigned_branch_ids || '').split(',').map(s => s.trim());
    document.querySelectorAll('.user-branch-chk').forEach(chk => {
        chk.checked = assignedIds.includes(chk.value);
    });
    if (assignedIds.length > 0 && assignedIds[0]) {
        document.getElementById('user_modal_primary_branch').value = assignedIds[0];
    }

    const modal = document.getElementById('userModal');
    modal.classList.add('active');
    setTimeout(() => document.getElementById('user_modal_full_name').focus(), 60);
}

function updatePrimaryBranchOptions() {
    const checked = Array.from(document.querySelectorAll('.user-branch-chk:checked')).map(c => c.value);
    const sel = document.getElementById('user_modal_primary_branch');
    if (checked.length > 0 && !checked.includes(sel.value)) {
        sel.value = checked[0];
    }
}

function closeUserModal() {
    document.getElementById('userModal').classList.remove('active');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

async function handleUserSubmit(e) {
    e.preventDefault();
    const btn = document.getElementById('userModalSubmitBtn');

    // Validate at least one role is checked
    const checkedRoles = document.querySelectorAll('.user-role-chk:checked');
    if (checkedRoles.length === 0) {
        alert("Please select at least one User System Role.");
        return;
    }

    // Validate 5-digit PIN if entered or required
    const pinVal = document.getElementById('user_modal_pin').value.trim();
    const userId = document.getElementById('user_modal_id').value;
    if (!userId && (!pinVal || !/^\d{5}$/.test(pinVal))) {
        alert("A valid 5-digit numeric PIN is required for new user creation.");
        document.getElementById('user_modal_pin').focus();
        return;
    }
    if (pinVal && !/^\d{5}$/.test(pinVal)) {
        alert("Security PIN must be exactly 5 numeric digits (0-9).");
        document.getElementById('user_modal_pin').focus();
        return;
    }

    btn.disabled = true;
    btn.innerText = 'Saving...';

    try {
        const form = document.getElementById('userForm');
        const formData = new FormData(form);
        formData.append('csrf_token', getCsrfToken());

        const res = await fetch('users.php?action=save', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const json = await res.json();

        if (json.success) {
            alert(json.message || "User account saved successfully!");
            window.location.reload();
        } else {
            alert(json.message || "Failed to save user account.");
            btn.disabled = false;
            btn.innerText = 'Save User Account';
        }
    } catch (err) {
        alert("Server error processing user request.");
        btn.disabled = false;
        btn.innerText = 'Save User Account';
    }
}

async function toggleUserStatus(id, currentStatus, userName) {
    const nextStatus = (currentStatus === 'active') ? 'inactive' : 'active';
    if (!confirm(`Are you sure you want to change account status for "${userName}" to ${nextStatus.toUpperCase()}?`)) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('csrf_token', getCsrfToken());

        const res = await fetch('users.php?action=toggle_status', {
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
            alert(json.message || "Failed to update user status.");
        }
    } catch (err) {
        alert("Server communication error updating status.");
    }
}

async function confirmDeleteUser(id, userName) {
    if (!confirm(`Move user account "${userName}" to the Trash Bin?\n\nHistorical transactions and audit logs will remain intact.`)) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('csrf_token', getCsrfToken());

        const res = await fetch('users.php?action=delete', {
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
            alert(json.message || "Failed to delete user account.");
        }
    } catch (err) {
        alert("Server communication error moving user to Trash Bin.");
    }
}

async function confirmRestoreUser(id, userName) {
    if (!confirm(`Restore user account "${userName}" from the Trash Bin back to active status?`)) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('csrf_token', getCsrfToken());

        const res = await fetch('users.php?action=restore', {
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
            alert(json.message || "Failed to restore user account.");
        }
    } catch (err) {
        alert("Server communication error restoring user.");
    }
}

async function openUserDetails(u) {
    const modal = document.getElementById('userDetailsModal');
    const title = document.getElementById('detUserFullName');
    const badge = document.getElementById('detUserStatusBadge');
    const body = document.getElementById('detUserBody');
    const footer = document.getElementById('detUserActionFooter');

    title.innerText = u.full_name || 'User Profile';
    badge.innerHTML = (u.status === 'active')
        ? '<span class="prod-badge prod-badge-active"><span class="dot"></span>Active</span>'
        : '<span class="prod-badge prod-badge-inactive"><span class="dot"></span>Inactive</span>';

    body.innerHTML = `
        <div style="text-align: center; color: #64748b; padding: 2rem;">
            <div style="display: inline-block; width: 22px; height: 22px; border: 3px solid #e2e8f0; border-top-color: #0f172a; border-radius: 50%; animation: spin 0.8s linear infinite;"></div>
            <div style="margin-top: 8px; font-size: 12.5px;">Loading user profile...</div>
        </div>
    `;
    footer.innerHTML = '';
    modal.classList.add('active');

    try {
        const res = await fetch(`users.php?action=get_details&id=${u.id}`);
        const json = await res.json();

        if (!json.success || !json.user) {
            body.innerHTML = `<div style="text-align: center; color: #ef4444; padding: 2rem;">${json.message || 'User details not available.'}</div>`;
            return;
        }

        const user = json.user;
        const branches = json.branches || [];
        const metrics = json.metrics || {};

        let branchBadges = '';
        if (branches.length > 0) {
            branches.forEach(b => {
                const primaryTag = b.is_primary == 1 ? ' (Primary)' : '';
                branchBadges += `<span class="prod-badge-branch" style="${b.is_primary == 1 ? 'font-weight: 800; background: #e0f2fe; color: #0369a1; border-color: #bae6fd;' : ''}">${escapeHtml(b.name)}${primaryTag}</span>`;
            });
        } else {
            branchBadges = '<span style="color: #64748b; font-style: italic;">All Branches (Unrestricted Access)</span>';
        }

        body.innerHTML = `
            <!-- Profile Banner -->
            <div style="padding: 14px 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 1.25rem; display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b;">System Account</div>
                    <div style="font-size: 16px; font-weight: 900; color: #0f172a; margin-top: 2px;">${escapeHtml(user.full_name)}</div>
                    <div style="display: flex; align-items: center; gap: 8px; margin-top: 4px;">
                        <code style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-weight: 700; color: #0369a1;">${escapeHtml(user.username)}</code>
                        <span class="prod-badge-role">${escapeHtml(user.role_name)}</span>
                        <span style="font-size: 12px; color: #64748b;">ID: <strong>#${user.id}</strong></span>
                    </div>
                </div>
                <div style="text-align: right; font-size: 11.5px; color: #64748b;">
                    <div>Registered: <strong>${escapeHtml(user.created_at || '').substring(0, 10)}</strong></div>
                    <div style="margin-top: 2px;">Last Login: <strong>${user.last_login_at ? escapeHtml(user.last_login_at).substring(0, 16) : 'Never'}</strong></div>
                </div>
            </div>

            <!-- Credentials & Contact Card -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 1.25rem;">
                <div style="padding: 12px 14px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px;">
                    <div style="font-size: 10.5px; font-weight: 800; text-transform: uppercase; color: #64748b;">Email Address</div>
                    <div style="font-size: 13.5px; font-weight: 700; color: #0f172a; margin-top: 3px;">${escapeHtml(user.email || '—')}</div>
                </div>
                <div style="padding: 12px 14px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px;">
                    <div style="font-size: 10.5px; font-weight: 800; text-transform: uppercase; color: #64748b;">Phone Number</div>
                    <div style="font-size: 13.5px; font-weight: 700; color: #0f172a; margin-top: 3px;">${escapeHtml(user.phone || '—')}</div>
                </div>
            </div>

            <!-- Branch Assignments -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px; margin-bottom: 1.25rem; font-size: 12px;">
                <div style="font-size: 10.5px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; margin-bottom: 6px;">Branch Visibility & Access</div>
                <div>${branchBadges}</div>
            </div>

            <!-- User Activity Metrics -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div style="padding: 12px 14px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px;">
                    <div style="font-size: 10.5px; font-weight: 800; text-transform: uppercase; color: #64748b;">Sales Processed</div>
                    <div style="font-size: 18px; font-weight: 900; color: #0f172a; margin-top: 2px;">${metrics.sales_count || 0}</div>
                    <div style="font-size: 11px; color: #16a34a; margin-top: 2px;">Receipts issued as cashier</div>
                </div>
                <div style="padding: 12px 14px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px;">
                    <div style="font-size: 10.5px; font-weight: 800; text-transform: uppercase; color: #64748b;">Audit Activity Log</div>
                    <div style="font-size: 18px; font-weight: 900; color: #0f172a; margin-top: 2px;">${metrics.audit_count || 0}</div>
                    <div style="font-size: 11px; color: #64748b; margin-top: 2px;">Tracked actions logged</div>
                </div>
            </div>
        `;

        footer.innerHTML = `
            <button type="button" class="inv-btn inv-btn-ghost" onclick='closeModal("userDetailsModal"); openEditUserModal(${JSON.stringify(u)})'>
                Edit User
            </button>
            <button type="button" class="inv-btn inv-btn-ghost" onclick='closeModal("userDetailsModal"); toggleUserStatus(${user.id}, "${user.status}", "${escapeHtml(addslashes(user.full_name))}")'>
                Toggle Status
            </button>
        `;
    } catch (err) {
        body.innerHTML = `<div style="text-align: center; color: #ef4444; padding: 2rem;">Error fetching user details.</div>`;
    }
}

function filterUserTableByRole(roleName) {
    const term = (roleName || '').toLowerCase().trim();
    const rows = document.querySelectorAll('#usersTableBody tr');
    let visibleCount = 0;

    rows.forEach(row => {
        if (row.cells.length < 8) return;
        const rowRole = (row.getAttribute('data-role') || '').toLowerCase().trim();
        if (!term || rowRole === term) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    const counter = document.getElementById('usersTableCounter');
    if (counter) {
        counter.innerHTML = `Showing <strong>${visibleCount}</strong> of <strong>${rows.length}</strong> user accounts`;
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

// Enter Key Navigation for Modal Form
document.addEventListener('DOMContentLoaded', () => {
    // Attach CurtissSearchableSelect to role dropdown
    if (window.CurtissSearchableSelect) {
        const roleSel = document.getElementById('filterUserRole');
        if (roleSel) CurtissSearchableSelect.attach(roleSel);
    }

    const form = document.getElementById('userForm');
    if (form) {
        const inputs = Array.from(form.querySelectorAll('input:not([type="hidden"]), select'));
        inputs.forEach((input, idx) => {
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
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
                <a href="users.php?view=active" class="inv-btn inv-btn-ghost" style="height: 34px; padding: 0 14px; font-weight: 700;">
                    ← Active Users
                </a>
            `;
        <?php else: ?>
            btnHtml = `
                <button type="button" class="inv-btn inv-btn-primary" onclick="openCreateUserModal()" style="height: 34px; padding: 0 16px; font-weight: 800;">
                    + Create New User
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
                const rows = document.querySelectorAll('#usersTableBody tr');
                let visibleCount = 0;

                rows.forEach(row => {
                    if (row.cells.length < 8) return;
                    const dataCells = Array.from(row.querySelectorAll('td:not(.col-right)'));
                    const rowText = dataCells.map(td => td.textContent.toLowerCase()).join(' ');

                    if (!term || rowText.includes(term)) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                const counter = document.getElementById('usersTableCounter');
                if (counter) {
                    counter.innerHTML = `Showing <strong>${visibleCount}</strong> of <strong>${rows.length}</strong> user accounts`;
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
