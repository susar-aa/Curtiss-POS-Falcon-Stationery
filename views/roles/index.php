<?php
/**
 * Curtiss POS - Roles & Permission Matrix Management View
 * Fixed Screen Viewport, 48px Zebra Rows, Universal Real-Time Floating Search,
 * Floating Top Header Right Action Button, and Module Permission Matrix Modal.
 */
?>
<meta name="csrf-token" content="<?= htmlspecialchars(Auth::generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">

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
.inv-btn-success {
    background: #10b981;
    color: #ffffff;
}
.inv-btn-success:hover {
    background: #059669;
    color: #ffffff;
}
.inv-btn-danger {
    color: #dc2626;
    background: #fef2f2;
    border-color: #fca5a5;
}
.inv-btn-danger:hover {
    background: #fee2e2;
    border-color: #f87171;
}

/* Badges */
.role-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    height: 26px;
    line-height: 24px;
    padding: 0 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
}
.role-badge-super {
    background: #fef3c7;
    color: #b45309;
    border: 1px solid #fde68a;
}
.role-badge-primary {
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #bfdbfe;
}
.role-badge-muted {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
}

/* Permission Matrix Modal Styling */
.matrix-module-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    margin-bottom: 14px;
    overflow: hidden;
    transition: border-color 0.2s ease;
}
.matrix-module-card:hover {
    border-color: #cbd5e1;
}
.matrix-module-header {
    background: #f8fafc;
    padding: 10px 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #e2e8f0;
}
.matrix-module-title {
    font-size: 12.5px;
    font-weight: 800;
    color: #0f172a;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    display: flex;
    align-items: center;
    gap: 8px;
}
.matrix-module-body {
    padding: 12px 16px;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 8px;
}
.matrix-perm-item {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    background: #f8fafc;
    border: 1px solid #f1f5f9;
    padding: 8px 10px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.15s ease;
}
.matrix-perm-item:hover {
    background: #f1f5f9;
    border-color: #e2e8f0;
}
.matrix-perm-item.checked {
    background: #eff6ff;
    border-color: #bfdbfe;
}

/* Modals */
.curtiss-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 2000;
    padding: 1.5rem;
}
.curtiss-modal.active {
    display: flex;
}
.curtiss-modal-content {
    background: #ffffff;
    border-radius: 18px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    width: 100%;
    max-width: 600px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: modalScale 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}
.curtiss-modal-content-large {
    max-width: 960px;
}
@keyframes modalScale {
    from { opacity: 0; transform: scale(0.96); }
    to   { opacity: 1; transform: scale(1); }
}
.curtiss-modal-header {
    padding: 16px 20px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #ffffff;
}
.curtiss-modal-body {
    padding: 18px 20px;
    overflow-y: auto;
    flex: 1;
}
.curtiss-modal-footer {
    padding: 14px 20px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    background: #f8fafc;
}
</style>

<!-- Floating Header Right Action Button (Rule 19) -->
<div id="curtiss-floating-header-right" class="no-print" style="position: fixed; top: 16px; right: 20px; z-index: 1050; display: flex; align-items: center; gap: 8px;">
    <a href="users.php" class="inv-btn inv-btn-ghost" style="height: 34px; padding: 0 14px; font-weight: 700;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        <span>← User Accounts</span>
    </a>
    <button type="button" class="inv-btn inv-btn-primary" onclick="openCreateRoleModal()" style="height: 34px; padding: 0 16px; font-weight: 800; box-shadow: 0 2px 5px rgba(0,0,0,0.12);">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        <span>+ Add Custom Role</span>
    </button>
</div>

<!-- Main Workspace Layout -->
<div style="width: 100%; max-width: 100%; height: calc(100vh - 110px); display: flex; flex-direction: column; box-sizing: border-box; overflow: hidden; padding-bottom: 8px;">
    <!-- Page Header (Headline Only) -->
    <div class="inv-header" style="flex-shrink: 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 0.85rem;">
        <div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #0f172a;"></div>
                <h1 class="inv-title">Roles & Permission Management</h1>
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 10px; font-size: 12px; color: #64748b; font-weight: 600;">
            <span>Configured Permissions: <strong><?= (int)$totalPermissionsCount ?></strong></span>
            <button type="button" class="inv-btn inv-btn-primary" onclick="openCreateRoleModal()" style="height: 34px; padding: 0 14px; font-weight: 800;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>+ Add Role</span>
            </button>
        </div>
    </div>

    <!-- Table Panel & Scroll -->
    <div class="prod-table-panel" style="flex: 1; min-height: 0; display: flex; flex-direction: column;">
        <div class="prod-table-scroll">
            <table class="prod-table" id="rolesTable">
                <thead>
                    <tr>
                        <th style="width: 50px;" class="col-center">#</th>
                        <th style="min-width: 160px; text-align: left;">Role Name</th>
                        <th style="min-width: 140px; text-align: left;">System Code</th>
                        <th style="min-width: 240px; text-align: left;">Description</th>
                        <th style="width: 130px; text-align: left;">Assigned Users</th>
                        <th style="width: 180px; text-align: left;">Granted Permissions</th>
                        <th style="width: 220px;" class="col-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="rolesTableBody">
                    <?php foreach ($roles as $idx => $r): ?>
                        <?php 
                            $isSuper = ($r['code'] === ROLE_SUPER_ADMIN || $r['code'] === 'superadmin'); 
                            $isBuiltin = in_array((int)$r['id'], [1, 2, 3, 4, 5, 6], true);
                        ?>
                        <tr onclick='openPermissionMatrix(<?= (int)$r['id'] ?>, <?= json_encode($r['name']) ?>)' style="cursor: pointer;">
                            <td class="col-center" style="font-weight: 700; color: #94a3b8;">
                                <?= $idx + 1 ?>
                            </td>
                            <td style="text-align: left; font-weight: 800; color: #0f172a;">
                                <?= htmlspecialchars($r['name']) ?>
                                <?php if ($isBuiltin): ?>
                                    <span style="font-size: 9.5px; font-weight: 800; background: #e2e8f0; color: #475569; padding: 2px 6px; border-radius: 4px; margin-left: 6px; text-transform: uppercase;">System</span>
                                <?php else: ?>
                                    <span style="font-size: 9.5px; font-weight: 800; background: #dbeafe; color: #1e40af; padding: 2px 6px; border-radius: 4px; margin-left: 6px; text-transform: uppercase;">Custom</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: left;">
                                <code style="font-size: 11.5px; background: #f1f5f9; padding: 3px 6px; border-radius: 4px; color: #0f172a; font-weight: 600;"><?= htmlspecialchars($r['code']) ?></code>
                            </td>
                            <td style="text-align: left; color: #64748b; font-size: 12px;">
                                <?= htmlspecialchars($r['description'] ?? 'No description provided') ?>
                            </td>
                            <td style="text-align: left;">
                                <span class="role-badge role-badge-muted">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="9" cy="7" r="4"></circle>
                                    </svg>
                                    <?= (int)$r['user_count'] ?> Users
                                </span>
                            </td>
                            <td style="text-align: left;">
                                <?php if ($isSuper): ?>
                                    <span class="role-badge role-badge-super">
                                        <span style="width:6px; height:6px; border-radius:50%; background:#d97706;"></span>
                                        <?= (int)$totalPermissionsCount ?> / <?= (int)$totalPermissionsCount ?> (Full Access)
                                    </span>
                                <?php else: ?>
                                    <span class="role-badge role-badge-primary">
                                        <span style="width:6px; height:6px; border-radius:50%; background:#2563eb;"></span>
                                        <?= (int)$r['permission_count'] ?> / <?= (int)$totalPermissionsCount ?> Granted
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="col-right" onclick="event.stopPropagation();">
                                <div style="display: flex; gap: 6px; justify-content: flex-end; align-items: center;">
                                    <button type="button" class="inv-btn inv-btn-ghost" style="height: 28px; min-height: 28px; line-height: 26px; padding: 0 10px; font-size: 11px;" onclick='openPermissionMatrix(<?= (int)$r['id'] ?>, <?= json_encode($r['name']) ?>)'>
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                        </svg>
                                        Permissions
                                    </button>
                                    <button type="button" class="inv-btn inv-btn-ghost" style="height: 28px; min-height: 28px; line-height: 26px; padding: 0 10px; font-size: 11px;" onclick='openEditRoleModal(<?= json_encode($r) ?>)'>
                                        Edit
                                    </button>
                                    <?php if (!$isBuiltin && (int)$r['user_count'] === 0): ?>
                                        <button type="button" class="inv-btn inv-btn-danger" style="height: 28px; min-height: 28px; line-height: 26px; padding: 0 10px; font-size: 11px;" onclick='confirmDeleteRole(<?= (int)$r['id'] ?>, <?= json_encode($r['name']) ?>)'>
                                            Delete
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Table Footer Info -->
        <div style="padding: 10px 16px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; font-size: 11.5px; color: #64748b;">
            <div>
                Showing <strong><?= count($roles) ?></strong> configured user roles.
            </div>
            <div>
                Click any role row to view and customize its module permission matrix.
            </div>
        </div>
    </div>
</div>

<!-- Modal: Permission Matrix -->
<div class="curtiss-modal" id="permissionMatrixModal">
    <div class="curtiss-modal-content curtiss-modal-content-large">
        <div class="curtiss-modal-header">
            <div>
                <span style="font-weight: 800; font-size: 1.15rem; color: #0f172a;" id="permModalRoleTitle">Role Permissions</span>
                <span id="permSuperAdminNotice" style="display: none; margin-left: 10px; font-size: 11px; font-weight: 800; background: #fef3c7; color: #b45309; padding: 2px 8px; border-radius: 999px;">Permanent Full Access</span>
            </div>
            <button type="button" onclick="closeModal('permissionMatrixModal')" style="background: none; border: none; font-size: 1.5rem; color: #64748b; cursor: pointer;">&times;</button>
        </div>

        <!-- Sticky Controls Bar Inside Modal -->
        <div style="padding: 12px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <input type="text" id="permSearchInput" placeholder="Filter permissions..." onkeyup="filterPermissionsList(this.value)" style="height: 32px; padding: 0 10px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 12px; width: 220px;">
                <button type="button" class="inv-btn inv-btn-ghost" style="height: 32px; padding: 0 10px; font-size: 11.5px;" onclick="selectAllPermissions(true)">Select All</button>
                <button type="button" class="inv-btn inv-btn-ghost" style="height: 32px; padding: 0 10px; font-size: 11.5px;" onclick="selectAllPermissions(false)">Deselect All</button>
            </div>
            <div style="font-size: 12px; font-weight: 700; color: #0f172a;" id="permSelectedCounter">
                0 of <?= (int)$totalPermissionsCount ?> selected
            </div>
        </div>

        <form id="permMatrixForm" onsubmit="saveRolePermissions(event)">
            <input type="hidden" name="role_id" id="perm_modal_role_id" value="">

            <div class="curtiss-modal-body" style="padding-top: 14px;">
                <?php foreach ($permissionsByModule as $moduleName => $pList): ?>
                    <div class="matrix-module-card" data-module="<?= htmlspecialchars($moduleName) ?>">
                        <div class="matrix-module-header">
                            <div class="matrix-module-title">
                                <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #2563eb;"></span>
                                <?= htmlspecialchars(ucwords(str_replace('_', ' ', $moduleName))) ?>
                                <span style="font-size: 11px; font-weight: 600; color: #64748b; text-transform: none;">(<?= count($pList) ?> actions)</span>
                            </div>
                            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 11.5px; font-weight: 700; color: #475569;">
                                <input type="checkbox" onchange="toggleModulePermissions('<?= htmlspecialchars($moduleName) ?>', this.checked)" class="module-master-chk" data-module="<?= htmlspecialchars($moduleName) ?>">
                                <span>Select Module</span>
                            </label>
                        </div>
                        <div class="matrix-module-body">
                            <?php foreach ($pList as $p): ?>
                                <label class="matrix-perm-item" id="perm_item_<?= (int)$p['id'] ?>" data-search="<?= strtolower(htmlspecialchars($p['permission_code'] . ' ' . $p['description'])) ?>">
                                    <input type="checkbox" name="permission_ids[]" value="<?= (int)$p['id'] ?>" class="perm-chk" data-module="<?= htmlspecialchars($moduleName) ?>" onchange="updatePermissionCounter()">
                                    <div style="font-size: 12px; line-height: 1.3;">
                                        <div style="font-weight: 800; font-family: monospace; color: #0f172a;"><?= htmlspecialchars($p['permission_code']) ?></div>
                                        <div style="color: #64748b; font-size: 11px; margin-top: 2px;"><?= htmlspecialchars($p['description']) ?></div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="curtiss-modal-footer">
                <button type="button" class="inv-btn inv-btn-ghost" onclick="closeModal('permissionMatrixModal')">Close</button>
                <button type="submit" class="inv-btn inv-btn-primary" id="btnSavePermissions">Save Permissions</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Add / Edit Role -->
<div class="curtiss-modal" id="roleModal">
    <div class="curtiss-modal-content">
        <div class="curtiss-modal-header">
            <span id="roleModalTitle" style="font-weight: 800; font-size: 1.15rem; color: #0f172a;">Custom Role</span>
            <button type="button" onclick="closeModal('roleModal')" style="background: none; border: none; font-size: 1.5rem; color: #64748b; cursor: pointer;">&times;</button>
        </div>

        <form id="roleForm" onsubmit="handleRoleSubmit(event)">
            <input type="hidden" name="id" id="role_modal_id" value="">

            <div class="curtiss-modal-body">
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <div>
                        <label class="form-label" style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #475569; display: block; margin-bottom: 4px;">
                            Role Name <span style="color: #dc2626;">*</span>
                        </label>
                        <input type="text" name="name" id="role_modal_name" required placeholder="e.g. Shift Supervisor" onkeyup="autoGenerateRoleCode(this.value)" style="width: 100%; height: 38px; padding: 0 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 13px;">
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #475569; display: block; margin-bottom: 4px;">
                            System Code <span style="color: #dc2626;">*</span>
                        </label>
                        <input type="text" name="code" id="role_modal_code" required placeholder="e.g. shift_supervisor" style="width: 100%; height: 38px; padding: 0 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 13px; font-family: monospace;">
                        <small style="color: #64748b; font-size: 11px; margin-top: 2px; display: block;">Unique identifier using lowercase letters, numbers, and underscores.</small>
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #475569; display: block; margin-bottom: 4px;">
                            Description
                        </label>
                        <textarea name="description" id="role_modal_desc" rows="3" placeholder="Describe the responsibilities and access scope of this role..." style="width: 100%; padding: 8px 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 13px; font-family: inherit; resize: vertical;"></textarea>
                    </div>
                </div>
            </div>

            <div class="curtiss-modal-footer">
                <button type="button" class="inv-btn inv-btn-ghost" onclick="closeModal('roleModal')">Cancel</button>
                <button type="submit" class="inv-btn inv-btn-primary" id="roleModalSubmitBtn">Save Role</button>
            </div>
        </form>
    </div>
</div>

<script>
function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

// Mount action button to floating top header right
window.addEventListener('DOMContentLoaded', () => {
    const src = document.getElementById('roles-floating-header-source');
    const dest = document.getElementById('curtiss-floating-header-right');
    if (src && dest) {
        dest.innerHTML = src.innerHTML;
    }

    if (window.CurtissNav && typeof window.CurtissNav.enablePageSearch === 'function') {
        window.CurtissNav.enablePageSearch('#rolesTable', {
            columns: 'td:not(.col-right)',
            excludeCols: ['.col-right']
        });
    }
});

// Permission Matrix Modal Logic
let currentActiveRoleId = 0;
let isCurrentRoleSuperAdmin = false;

async function openPermissionMatrix(roleId, roleName) {
    currentActiveRoleId = roleId;
    document.getElementById('perm_modal_role_id').value = roleId;
    document.getElementById('permModalRoleTitle').innerText = 'Permissions for ' + roleName;
    document.getElementById('permSearchInput').value = '';
    filterPermissionsList('');

    const modal = document.getElementById('permissionMatrixModal');
    modal.classList.add('active');

    // Reset checkboxes
    document.querySelectorAll('.perm-chk').forEach(c => {
        c.checked = false;
        c.closest('.matrix-perm-item')?.classList.remove('checked');
    });

    try {
        const res = await fetch(`roles.php?action=get_role_permissions&role_id=${roleId}&ajax=1`);
        const json = await res.json();

        if (json.success) {
            isCurrentRoleSuperAdmin = json.is_super_admin;
            const grantedIds = (json.permissions || []).map(String);
            
            document.querySelectorAll('.perm-chk').forEach(c => {
                const isChecked = isCurrentRoleSuperAdmin || grantedIds.includes(c.value);
                c.checked = isChecked;
                c.disabled = isCurrentRoleSuperAdmin;
                if (isChecked) {
                    c.closest('.matrix-perm-item')?.classList.add('checked');
                }
            });

            document.querySelectorAll('.module-master-chk').forEach(mc => {
                mc.disabled = isCurrentRoleSuperAdmin;
            });

            document.getElementById('btnSavePermissions').style.display = isCurrentRoleSuperAdmin ? 'none' : 'inline-flex';
            document.getElementById('permSuperAdminNotice').style.display = isCurrentRoleSuperAdmin ? 'inline-block' : 'none';

            updatePermissionCounter();
        } else {
            alert(json.message || "Failed to load role permissions.");
        }
    } catch (err) {
        alert("Server communication error loading permissions.");
    }
}

function updatePermissionCounter() {
    const total = document.querySelectorAll('.perm-chk').length;
    const selected = document.querySelectorAll('.perm-chk:checked').length;
    document.getElementById('permSelectedCounter').innerText = `${selected} of ${total} selected`;

    // Update item styles
    document.querySelectorAll('.perm-chk').forEach(c => {
        if (c.checked) {
            c.closest('.matrix-perm-item')?.classList.add('checked');
        } else {
            c.closest('.matrix-perm-item')?.classList.remove('checked');
        }
    });

    // Update module master checkboxes
    document.querySelectorAll('.module-master-chk').forEach(mc => {
        const mod = mc.getAttribute('data-module');
        const modChks = document.querySelectorAll(`.perm-chk[data-module="${mod}"]`);
        const checkedModChks = document.querySelectorAll(`.perm-chk[data-module="${mod}"]:checked`);
        mc.checked = (modChks.length > 0 && modChks.length === checkedModChks.length);
    });
}

function selectAllPermissions(status) {
    if (isCurrentRoleSuperAdmin) return;
    document.querySelectorAll('.perm-chk').forEach(c => c.checked = status);
    updatePermissionCounter();
}

function toggleModulePermissions(moduleName, status) {
    if (isCurrentRoleSuperAdmin) return;
    document.querySelectorAll(`.perm-chk[data-module="${moduleName}"]`).forEach(c => c.checked = status);
    updatePermissionCounter();
}

function filterPermissionsList(query) {
    query = (query || '').toLowerCase().trim();
    document.querySelectorAll('.matrix-perm-item').forEach(item => {
        const text = item.getAttribute('data-search') || '';
        item.style.display = (!query || text.includes(query)) ? 'flex' : 'none';
    });

    // Hide module card if all items hidden
    document.querySelectorAll('.matrix-module-card').forEach(card => {
        const visibleItems = card.querySelectorAll('.matrix-perm-item[style*="display: flex"]');
        const anyVisible = !query || visibleItems.length > 0;
        card.style.display = anyVisible ? 'block' : 'none';
    });
}

async function saveRolePermissions(e) {
    e.preventDefault();
    if (isCurrentRoleSuperAdmin) return;

    const btn = document.getElementById('btnSavePermissions');
    btn.disabled = true;
    btn.innerText = 'Saving...';

    try {
        const form = document.getElementById('permMatrixForm');
        const formData = new FormData(form);
        formData.append('csrf_token', getCsrfToken());

        const res = await fetch('roles.php?action=save_permissions', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const json = await res.json();

        if (json.success) {
            alert(json.message || "Permissions updated successfully!");
            window.location.reload();
        } else {
            alert(json.message || "Failed to update permissions.");
            btn.disabled = false;
            btn.innerText = 'Save Permissions';
        }
    } catch (err) {
        alert("Server communication error saving permissions.");
        btn.disabled = false;
        btn.innerText = 'Save Permissions';
    }
}

// Role Edit / Create Modal
function openCreateRoleModal() {
    document.getElementById('roleModalTitle').innerText = 'Create Custom Role';
    document.getElementById('role_modal_id').value = '';
    document.getElementById('role_modal_name').value = '';
    document.getElementById('role_modal_code').value = '';
    document.getElementById('role_modal_code').readOnly = false;
    document.getElementById('role_modal_desc').value = '';
    document.getElementById('roleModal').classList.add('active');
    setTimeout(() => document.getElementById('role_modal_name').focus(), 60);
}

function openEditRoleModal(r) {
    document.getElementById('roleModalTitle').innerText = 'Edit Role: ' + (r.name || '');
    document.getElementById('role_modal_id').value = r.id || '';
    document.getElementById('role_modal_name').value = r.name || '';
    document.getElementById('role_modal_code').value = r.code || '';
    
    // Builtin roles (ID 1-6) code cannot be altered
    const isBuiltin = [1, 2, 3, 4, 5, 6].includes(parseInt(r.id));
    document.getElementById('role_modal_code').readOnly = isBuiltin;
    document.getElementById('role_modal_desc').value = r.description || '';

    document.getElementById('roleModal').classList.add('active');
    setTimeout(() => document.getElementById('role_modal_name').focus(), 60);
}

function autoGenerateRoleCode(name) {
    const id = document.getElementById('role_modal_id').value;
    if (!id) {
        const slug = name.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
        document.getElementById('role_modal_code').value = slug;
    }
}

async function handleRoleSubmit(e) {
    e.preventDefault();
    const btn = document.getElementById('roleModalSubmitBtn');
    btn.disabled = true;
    btn.innerText = 'Saving...';

    try {
        const form = document.getElementById('roleForm');
        const formData = new FormData(form);
        formData.append('csrf_token', getCsrfToken());

        const res = await fetch('roles.php?action=save_role', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const json = await res.json();

        if (json.success) {
            alert(json.message || "Role saved successfully!");
            window.location.reload();
        } else {
            alert(json.message || "Failed to save role.");
            btn.disabled = false;
            btn.innerText = 'Save Role';
        }
    } catch (err) {
        alert("Server communication error saving role.");
        btn.disabled = false;
        btn.innerText = 'Save Role';
    }
}

async function confirmDeleteRole(id, roleName) {
    if (!confirm(`Are you sure you want to permanently delete custom role "${roleName}"?`)) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('csrf_token', getCsrfToken());

        const res = await fetch('roles.php?action=delete_role', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const json = await res.json();

        if (json.success) {
            alert(json.message || "Role deleted successfully.");
            window.location.reload();
        } else {
            alert(json.message || "Failed to delete role.");
        }
    } catch (err) {
        alert("Server communication error deleting role.");
    }
}
</script>
