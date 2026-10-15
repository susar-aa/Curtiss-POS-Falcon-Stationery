<?php
/**
 * Curtiss POS - User Management View (Stage 1 Foundation)
 */
?>
<div class="page-header">
    <div>
        <h1 class="page-title">User Accounts & Branch Assignments</h1>
        <p class="page-subtitle">Manage user roles, access credentials and branch visibility</p>
    </div>
    <div>
        <button class="btn btn-primary" onclick="alert('User Provisioning Modal placeholder for Stage 1. Schema ready!');">
            + Create New User
        </button>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Full Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Assigned Branches</th>
                    <th>Status</th>
                    <th>Last Login</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><strong>#<?= (int)$u['id'] ?></strong></td>
                        <td><strong><?= View::escape($u['full_name']) ?></strong></td>
                        <td><code><?= View::escape($u['username']) ?></code></td>
                        <td><?= View::escape($u['email']) ?></td>
                        <td>
                            <span class="badge badge-info"><?= View::escape($u['role_name']) ?></span>
                        </td>
                        <td>
                            <?php 
                            if (!empty($u['assigned_branches'])) {
                                $bNames = explode(',', $u['assigned_branches']);
                                foreach ($bNames as $bn) {
                                    echo '<span class="badge badge-teal" style="margin-right: 0.25rem;">' . View::escape(trim($bn)) . '</span>';
                                }
                            } else {
                                echo '<span style="color: var(--color-text-muted);">None</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <?php if ($u['status'] === 'active'): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td><?= View::formatDate($u['last_login_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
