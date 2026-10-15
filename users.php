<?php
/**
 * Curtiss POS - Users Controller
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Audit.php';
require_once __DIR__ . '/core/View.php';

Auth::requireAuth();

if (!Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN)) {
    View::renderBlank('errors/403');
    exit();
}

$error = null;
$success = null;

if (isset($_GET['created'])) {
    $success = 'User account created successfully!';
} elseif (isset($_GET['toggled'])) {
    $success = 'User status updated successfully!';
} elseif (isset($_GET['reset'])) {
    $success = 'User password reset successfully!';
}

// ─── Handle Form Actions ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Create New User
    if ($action === 'create_user') {
        $username = trim($_POST['username'] ?? '');
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $roleId = (int)($_POST['role_id'] ?? 0);
        $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
        $branchIds = $_POST['branch_ids'] ?? [];
        $primaryBranchId = (int)($_POST['primary_branch_id'] ?? 0);

        if (empty($username) || empty($fullName) || empty($email) || empty($password) || $roleId <= 0) {
            $error = 'Full Name, Username, Email, Password, and Role are all required.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } else {
            // Check for uniqueness
            $existing = Database::fetchOne("
                SELECT id, username, email FROM users 
                WHERE (username = :u OR email = :e) AND deleted_at IS NULL
            ", [':u' => $username, ':e' => $email]);

            if ($existing) {
                if (strtolower($existing['username']) === strtolower($username)) {
                    $error = "The username '$username' is already taken.";
                } else {
                    $error = "The email '$email' is already registered to another user.";
                }
            } else {
                try {
                    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
                    Database::execute("
                        INSERT INTO users (username, email, password_hash, full_name, phone, role_id, status)
                        VALUES (:username, :email, :password_hash, :full_name, :phone, :role_id, :status)
                    ", [
                        ':username'      => $username,
                        ':email'         => $email,
                        ':password_hash' => $passwordHash,
                        ':full_name'     => $fullName,
                        ':phone'         => $phone ?: null,
                        ':role_id'       => $roleId,
                        ':status'        => $status
                    ]);

                    $newUserId = (int)Database::lastInsertId();

                    // If no branch was explicitly selected, assign first available active branch
                    if (empty($branchIds)) {
                        $firstB = Database::fetchOne("SELECT id FROM branches WHERE status = 'active' AND deleted_at IS NULL LIMIT 1");
                        if ($firstB) {
                            $branchIds = [(int)$firstB['id']];
                            $primaryBranchId = (int)$firstB['id'];
                        }
                    }

                    if (empty($primaryBranchId) && !empty($branchIds)) {
                        $primaryBranchId = (int)$branchIds[0];
                    }

                    // Assign user branches
                    foreach ($branchIds as $bId) {
                        $bId = (int)$bId;
                        if ($bId > 0) {
                            $isPrimary = ($bId === $primaryBranchId) ? 1 : 0;
                            Database::execute("
                                INSERT IGNORE INTO user_branches (user_id, branch_id, is_primary)
                                VALUES (:uid, :bid, :is_primary)
                            ", [
                                ':uid'        => $newUserId,
                                ':bid'        => $bId,
                                ':is_primary' => $isPrimary
                            ]);
                        }
                    }

                    Audit::log('USER_CREATED', 'users', (string)$newUserId, null, [
                        'username' => $username,
                        'full_name' => $fullName,
                        'role_id' => $roleId
                    ]);

                    header('Location: users?created=1');
                    exit();
                } catch (Throwable $e) {
                    $error = 'Database error creating user: ' . $e->getMessage();
                }
            }
        }
    }

    // 2. Toggle Status (Active / Inactive)
    if ($action === 'toggle_status') {
        $targetUserId = (int)($_POST['user_id'] ?? 0);
        $currUserId = (int)($_SESSION['user_id'] ?? 0);

        if ($targetUserId === $currUserId) {
            $error = 'You cannot deactivate your own logged-in user account.';
        } else {
            $u = Database::fetchOne("SELECT id, status FROM users WHERE id = :id AND deleted_at IS NULL", [':id' => $targetUserId]);
            if ($u) {
                $newStatus = ($u['status'] === 'active') ? 'inactive' : 'active';
                Database::execute("UPDATE users SET status = :s WHERE id = :id", [
                    ':s'  => $newStatus,
                    ':id' => $targetUserId
                ]);
                Audit::log('USER_STATUS_TOGGLED', 'users', (string)$targetUserId, ['status' => $u['status']], ['status' => $newStatus]);
                header('Location: users?toggled=1');
                exit();
            }
        }
    }

    // 3. Reset Password
    if ($action === 'reset_password') {
        $targetUserId = (int)($_POST['user_id'] ?? 0);
        $newPassword = trim($_POST['new_password'] ?? '');

        if ($targetUserId <= 0 || empty($newPassword)) {
            $error = 'User ID and New Password are required.';
        } elseif (strlen($newPassword) < 6) {
            $error = 'Password must be at least 6 characters.';
        } else {
            $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
            Database::execute("UPDATE users SET password_hash = :hash WHERE id = :id", [
                ':hash' => $newHash,
                ':id'   => $targetUserId
            ]);
            Audit::log('USER_PASSWORD_RESET', 'users', (string)$targetUserId);
            header('Location: users?reset=1');
            exit();
        }
    }
}

// ─── Fetch Master Records for UI ─────────────────────────────────────────────
$roles = Database::fetchAll("SELECT id, name, code, description FROM roles ORDER BY id ASC");
$branches = Database::fetchAll("SELECT id, name, code FROM branches WHERE status = 'active' AND deleted_at IS NULL ORDER BY name ASC");

$users = Database::fetchAll("
    SELECT u.*, r.name AS role_name, r.code AS role_code,
           GROUP_CONCAT(b.name SEPARATOR ', ') AS assigned_branches
    FROM users u
    JOIN roles r ON u.role_id = r.id
    LEFT JOIN user_branches ub ON u.id = ub.user_id
    LEFT JOIN branches b ON ub.branch_id = b.id AND b.status = 'active'
    WHERE u.deleted_at IS NULL
    GROUP BY u.id
    ORDER BY u.id ASC
");

View::render('users/index', [
    'users'    => $users,
    'roles'    => $roles,
    'branches' => $branches,
    'error'    => $error,
    'success'  => $success
], 'User Accounts - Falcon POS');
