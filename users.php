<?php
/**
 * Curtiss POS - Users Controller
 */

require_once __DIR__ . '/config/config.php';

Auth::requireAuth();

if (!Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN)) {
    View::renderBlank('errors/403');
    exit();
}

$users = Database::fetchAll("
    SELECT u.*, r.name AS role_name,
           GROUP_CONCAT(b.name SEPARATOR ', ') AS assigned_branches
    FROM users u
    JOIN roles r ON u.role_id = r.id
    LEFT JOIN user_branches ub ON u.id = ub.user_id
    LEFT JOIN branches b ON ub.branch_id = b.id AND b.status = 'active'
    WHERE u.deleted_at IS NULL
    GROUP BY u.id
    ORDER BY u.full_name ASC
");

View::render('users/index', [
    'users' => $users
], 'User Accounts - Curtiss POS');
