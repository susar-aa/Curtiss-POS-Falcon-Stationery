<?php
/**
 * Curtiss POS - Users & Branch Assignments Controller
 * Manages user accounts, credentials, role assignments, multi-branch visibility,
 * status controls, and soft-delete Trash Bin recovery.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Audit.php';
require_once __DIR__ . '/core/Logger.php';
require_once __DIR__ . '/core/View.php';

Auth::requireAuth();

if (!Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN)) {
    View::renderBlank('errors/403');
    exit();
}

$currentUser = Auth::user();
$currentUserId = (int)($currentUser['id'] ?? 1);
$successMsg = null;
$errorMsg = null;

// Helper to detect AJAX request
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
    || (isset($_GET['ajax']) && $_GET['ajax'] == '1');

// Handle GET AJAX endpoints (e.g. get_details)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    $action = trim($_GET['action']);
    if ($action === 'get_details') {
        $id = (int)($_GET['id'] ?? 0);
        $user = Database::fetchOne("
            SELECT u.*, r.name AS role_name, r.code AS role_code 
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE u.id = :id
        ", [':id' => $id]);

        if (!$user) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'User account not found.']);
            exit;
        }

        // Fetch assigned roles (multi-role support)
        $assignedRoles = Database::fetchAll("
            SELECT ur.role_id, r.name, r.code
            FROM user_roles ur
            JOIN roles r ON ur.role_id = r.id
            WHERE ur.user_id = :id
            ORDER BY r.id ASC
        ", [':id' => $id]);

        $user['assigned_role_ids'] = array_map('intval', array_column($assignedRoles, 'role_id'));
        if (empty($user['assigned_role_ids']) && !empty($user['role_id'])) {
            $user['assigned_role_ids'] = [(int)$user['role_id']];
        }
        $user['has_pin'] = !empty($user['pin_hash']);
        unset($user['password_hash'], $user['pin_hash']);

        // Fetch assigned branches
        $assignedBranches = Database::fetchAll("
            SELECT ub.branch_id, ub.is_primary, b.name, b.code
            FROM user_branches ub
            JOIN branches b ON ub.branch_id = b.id
            WHERE ub.user_id = :id AND b.deleted_at IS NULL
            ORDER BY ub.is_primary DESC, b.name ASC
        ", [':id' => $id]);

        // Fetch user activity metrics
        $salesCount = (int)Database::fetchValue("SELECT COUNT(id) FROM sales WHERE user_id = :id", [':id' => $id]);
        $auditCount = (int)Database::fetchValue("SELECT COUNT(id) FROM audit_logs WHERE user_id = :id", [':id' => $id]);

        header('Content-Type: application/json');
        echo json_encode([
            'success'  => true,
            'user'     => $user,
            'roles'    => $assignedRoles,
            'branches' => $assignedBranches,
            'metrics'  => [
                'sales_count' => $salesCount,
                'audit_count' => $auditCount
            ]
        ]);
        exit;
    }
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim($_GET['action'] ?? $_POST['action'] ?? 'save');

    // CSRF Token Validation
    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? null;
    if (!Auth::validateCsrfToken($csrfToken)) {
        if ($isAjax) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Security token invalid or expired. Please refresh the page.']);
            exit;
        }
        $errorMsg = 'Security token invalid or expired. Please try again.';
    } else {
        if ($action === 'save') {
            $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
            $fullName = trim($_POST['full_name'] ?? '');
            $username = strtolower(trim($_POST['username'] ?? ''));
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $pin = trim($_POST['pin'] ?? '');

            // Support multi-role selection
            $roleIds = isset($_POST['role_ids']) && is_array($_POST['role_ids']) 
                ? array_filter(array_map('intval', $_POST['role_ids'])) 
                : [];
            if (empty($roleIds) && !empty($_POST['role_id'])) {
                $roleIds = [(int)$_POST['role_id']];
            }
            $roleId = !empty($roleIds) ? $roleIds[0] : 0;

            $password = trim($_POST['password'] ?? '');
            $branchIds = isset($_POST['branch_ids']) && is_array($_POST['branch_ids']) 
                ? array_map('intval', $_POST['branch_ids']) 
                : [];
            $primaryBranchId = !empty($_POST['primary_branch_id']) ? (int)$_POST['primary_branch_id'] : 0;

            if (empty($fullName) || empty($username) || empty($roleId)) {
                $err = "Full Name, Username, and at least one User Role are mandatory.";
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $err]);
                    exit;
                }
                $errorMsg = $err;
            } elseif (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $username)) {
                $err = "Username can only contain alphanumeric characters, underscores, dashes, and periods.";
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $err]);
                    exit;
                }
                $errorMsg = $err;
            } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $err = "Please enter a valid email address.";
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $err]);
                    exit;
                }
                $errorMsg = $err;
            } elseif (!$id && (empty($password) || strlen($password) < 6)) {
                $err = "Password is required for new users and must be at least 6 characters.";
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $err]);
                    exit;
                }
                $errorMsg = $err;
            } elseif ($id && !empty($password) && strlen($password) < 6) {
                $err = "New password must be at least 6 characters.";
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $err]);
                    exit;
                }
                $errorMsg = $err;
            } elseif (!$id && empty($pin)) {
                $err = "A 5-digit Security PIN is required when creating a user account.";
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $err]);
                    exit;
                }
                $errorMsg = $err;
            } elseif (!empty($pin) && !preg_match('/^\d{5}$/', $pin)) {
                $err = "Security PIN must be exactly 5 numeric digits (0-9).";
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $err]);
                    exit;
                }
                $errorMsg = $err;
            } else {
                // Check PIN uniqueness across active users
                $pinConflict = false;
                if (!empty($pin)) {
                    $activeWithPin = Database::fetchAll("
                        SELECT id, pin_hash FROM users 
                        WHERE status = 'active' AND deleted_at IS NULL AND pin_hash IS NOT NULL AND id != :id
                    ", [':id' => $id ?? 0]);
                    foreach ($activeWithPin as $awp) {
                        if (password_verify($pin, $awp['pin_hash'])) {
                            $pinConflict = true;
                            break;
                        }
                    }
                }

                if ($pinConflict) {
                    $err = "This 5-digit PIN is already assigned to another user account. Please choose a unique PIN.";
                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => $err]);
                        exit;
                    }
                    $errorMsg = $err;
                } else {
                    // Check unique username
                    $existingUsername = Database::fetchOne("
                        SELECT id FROM users 
                        WHERE LOWER(username) = LOWER(:u) 
                        AND id != :id 
                        AND deleted_at IS NULL
                    ", [':u' => $username, ':id' => $id ?? 0]);

                    if ($existingUsername) {
                        $err = "The username '{$username}' is already in use by another account.";
                        if ($isAjax) {
                            header('Content-Type: application/json');
                            echo json_encode(['success' => false, 'message' => $err]);
                            exit;
                        }
                        $errorMsg = $err;
                    } else {
                        // Check unique email if provided
                        $existingEmail = null;
                        if (!empty($email)) {
                            $existingEmail = Database::fetchOne("
                                SELECT id FROM users 
                                WHERE LOWER(email) = LOWER(:e) 
                                AND id != :id 
                                AND deleted_at IS NULL
                            ", [':e' => $email, ':id' => $id ?? 0]);
                        }

                        if ($existingEmail) {
                            $err = "The email '{$email}' is already registered to another account.";
                            if ($isAjax) {
                                header('Content-Type: application/json');
                                echo json_encode(['success' => false, 'message' => $err]);
                                exit;
                            }
                            $errorMsg = $err;
                        } else {
                            $db = Database::getInstance()->getConnection();
                            $db->beginTransaction();

                            try {
                                $pinHash = !empty($pin) ? password_hash($pin, PASSWORD_BCRYPT) : null;

                                if ($id) {
                                    // Update existing user
                                    $old = Database::fetchOne("SELECT * FROM users WHERE id = :id", [':id' => $id]);
                                    if (!$old) {
                                        throw new Exception("User account not found.");
                                    }

                                    $updates = [
                                        'full_name = :fn',
                                        'username = :un',
                                        'email = :em',
                                        'phone = :ph',
                                        'role_id = :rid',
                                        'updated_at = NOW()'
                                    ];
                                    $params = [
                                        ':fn'  => $fullName,
                                        ':un'  => $username,
                                        ':em'  => $email ?: null,
                                        ':ph'  => $phone ?: null,
                                        ':rid' => $roleId,
                                        ':id'  => $id
                                    ];

                                    if (!empty($password)) {
                                        $updates[] = 'password_hash = :pwd';
                                        $params[':pwd'] = password_hash($password, PASSWORD_DEFAULT);
                                    }

                                    if ($pinHash !== null) {
                                        $updates[] = 'pin_hash = :pin_hash';
                                        $params[':pin_hash'] = $pinHash;
                                    }

                                    $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = :id";
                                    Database::execute($sql, $params);

                                    Audit::log('USER_UPDATED', 'users', (string)$id, $old, [
                                        'full_name'      => $fullName,
                                        'username'       => $username,
                                        'email'          => $email,
                                        'phone'          => $phone,
                                        'role_id'        => $roleId,
                                        'roles'          => $roleIds,
                                        'password_reset' => !empty($password),
                                        'pin_reset'      => ($pinHash !== null)
                                    ]);
                                    $targetUserId = $id;
                                    $msg = "User account '{$fullName}' updated successfully.";
                                } else {
                                    // Create new user
                                    $pwdHash = password_hash($password, PASSWORD_DEFAULT);
                                    Database::execute("
                                        INSERT INTO users (full_name, username, email, phone, role_id, password_hash, pin_hash, status, created_at, updated_at)
                                        VALUES (:fn, :un, :em, :ph, :rid, :pwd, :pin_hash, 'active', NOW(), NOW())
                                    ", [
                                        ':fn'       => $fullName,
                                        ':un'       => $username,
                                        ':em'       => $email ?: null,
                                        ':ph'       => $phone ?: null,
                                        ':rid'      => $roleId,
                                        ':pwd'      => $pwdHash,
                                        ':pin_hash' => $pinHash
                                    ]);
                                    $targetUserId = (int)Database::lastInsertId();

                                    Audit::log('USER_CREATED', 'users', (string)$targetUserId, null, [
                                        'full_name'      => $fullName,
                                        'username'       => $username,
                                        'email'          => $email,
                                        'phone'          => $phone,
                                        'role_id'        => $roleId,
                                        'roles'          => $roleIds,
                                        'pin_configured' => true,
                                        'status'         => 'active'
                                    ]);
                                    $msg = "User account '{$fullName}' created successfully with 5-digit PIN.";
                                }

                                // Sync Multi-Role Assignments (user_roles)
                                Database::execute("DELETE FROM user_roles WHERE user_id = :uid", [':uid' => $targetUserId]);
                                foreach ($roleIds as $rId) {
                                    Database::execute("
                                        INSERT IGNORE INTO user_roles (user_id, role_id, assigned_at)
                                        VALUES (:uid, :rid, NOW())
                                    ", [
                                        ':uid' => $targetUserId,
                                        ':rid' => $rId
                                    ]);
                                }

                                // Update Branch Assignments
                                Database::execute("DELETE FROM user_branches WHERE user_id = :uid", [':uid' => $targetUserId]);

                                if (empty($branchIds)) {
                                    $branchIds = [1];
                                }
                                if (!$primaryBranchId || !in_array($primaryBranchId, $branchIds, true)) {
                                    $primaryBranchId = $branchIds[0];
                                }

                                foreach ($branchIds as $bId) {
                                    $isPrimary = ($bId === $primaryBranchId) ? 1 : 0;
                                    Database::execute("
                                        INSERT INTO user_branches (user_id, branch_id, is_primary, assigned_at)
                                        VALUES (:uid, :bid, :ip, NOW())
                                    ", [
                                        ':uid' => $targetUserId,
                                        ':bid' => $bId,
                                        ':ip'  => $isPrimary
                                    ]);
                                }

                                Audit::log('USER_BRANCHES_UPDATED', 'users', (string)$targetUserId, null, [
                                    'branch_ids'        => $branchIds,
                                    'primary_branch_id' => $primaryBranchId
                                ]);

                                $db->commit();

                                if ($isAjax) {
                                    header('Content-Type: application/json');
                                    echo json_encode(['success' => true, 'message' => $msg, 'user_id' => $targetUserId]);
                                    exit;
                                }
                                $successMsg = $msg;
                        } catch (Throwable $e) {
                            if ($db->inTransaction()) {
                                $db->rollBack();
                            }
                            Logger::error("Failed to save user account: " . $e->getMessage(), $e);
                            $err = "Server error saving user account: " . $e->getMessage();
                            if ($isAjax) {
                                header('Content-Type: application/json');
                                echo json_encode(['success' => false, 'message' => $err]);
                                exit;
                            }
                            $errorMsg = $err;
                        }
                    }
                }
            }
        }
    } elseif ($action === 'toggle_status') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id === 1) {
                $err = "The primary Super Administrator (ID #1) cannot be deactivated.";
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $err]);
                    exit;
                }
                $errorMsg = $err;
            } elseif ($id === $currentUserId) {
                $err = "You cannot deactivate your own currently active session account.";
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $err]);
                    exit;
                }
                $errorMsg = $err;
            } else {
                $u = Database::fetchOne("SELECT * FROM users WHERE id = :id AND deleted_at IS NULL", [':id' => $id]);
                if (!$u) {
                    $err = "Active user account not found.";
                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => $err]);
                        exit;
                    }
                    $errorMsg = $err;
                } else {
                    $newStatus = ($u['status'] === 'active') ? 'inactive' : 'active';
                    Database::execute("UPDATE users SET status = :st, updated_at = NOW() WHERE id = :id", [
                        ':st' => $newStatus,
                        ':id' => $id
                    ]);

                    Audit::log('USER_STATUS_TOGGLED', 'users', (string)$id, ['status' => $u['status']], ['status' => $newStatus]);

                    $msg = "User '{$u['full_name']}' status updated to " . ucfirst($newStatus) . ".";
                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true, 'message' => $msg, 'status' => $newStatus]);
                        exit;
                    }
                    $successMsg = $msg;
                }
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id === 1) {
                $err = "The primary Super Administrator (ID #1) is protected and cannot be deleted.";
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $err]);
                    exit;
                }
                $errorMsg = $err;
            } elseif ($id === $currentUserId) {
                $err = "You cannot delete your own active session account.";
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $err]);
                    exit;
                }
                $errorMsg = $err;
            } else {
                $u = Database::fetchOne("SELECT * FROM users WHERE id = :id AND deleted_at IS NULL", [':id' => $id]);
                if (!$u) {
                    $err = "User account not found or already in Trash Bin.";
                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => $err]);
                        exit;
                    }
                    $errorMsg = $err;
                } else {
                    Database::execute("
                        UPDATE users 
                        SET deleted_at = NOW(), status = 'inactive', updated_at = NOW() 
                        WHERE id = :id
                    ", [':id' => $id]);

                    Audit::log('USER_DELETED', 'users', (string)$id, $u, [
                        'deleted_at' => date('Y-m-d H:i:s'),
                        'status'     => 'inactive'
                    ]);

                    $msg = "User '{$u['full_name']}' moved to Trash Bin.";
                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true, 'message' => $msg]);
                        exit;
                    }
                    $successMsg = $msg;
                }
            }
        } elseif ($action === 'restore') {
            $id = (int)($_POST['id'] ?? 0);
            $u = Database::fetchOne("SELECT * FROM users WHERE id = :id AND deleted_at IS NOT NULL", [':id' => $id]);
            if (!$u) {
                $err = "User account not found in Trash Bin.";
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $err]);
                    exit;
                }
                $errorMsg = $err;
            } else {
                // Check if username collision occurred while in trash
                $conflict = Database::fetchOne("
                    SELECT id FROM users 
                    WHERE LOWER(username) = LOWER(:un) 
                    AND deleted_at IS NULL 
                    AND id != :id
                ", [':un' => $u['username'], ':id' => $id]);

                if ($conflict) {
                    $err = "Cannot restore user: Another active account is already using username '{$u['username']}'.";
                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => $err]);
                        exit;
                    }
                    $errorMsg = $err;
                } else {
                    Database::execute("
                        UPDATE users 
                        SET deleted_at = NULL, status = 'active', updated_at = NOW() 
                        WHERE id = :id
                    ", [':id' => $id]);

                    Audit::log('USER_RESTORED', 'users', (string)$id, ['deleted_at' => $u['deleted_at']], [
                        'deleted_at' => null,
                        'status'     => 'active'
                    ]);

                    $msg = "User '{$u['full_name']}' restored successfully.";
                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true, 'message' => $msg]);
                        exit;
                    }
                    $successMsg = $msg;
                }
            }
        }
    }
}

// Determine view mode (active vs trash bin)
$view = trim($_GET['view'] ?? 'active');
if (!in_array($view, ['active', 'deleted'], true)) {
    $view = 'active';
}

// Counts for navigation pills
$activeCount = (int)Database::fetchValue("SELECT COUNT(*) FROM users WHERE deleted_at IS NULL");
$deletedCount = (int)Database::fetchValue("SELECT COUNT(*) FROM users WHERE deleted_at IS NOT NULL");

// Roles and active branches for forms and filters
$roles = Database::fetchAll("SELECT * FROM roles ORDER BY id ASC");
$allBranches = Database::fetchAll("SELECT id, name, code FROM branches WHERE status = 'active' AND deleted_at IS NULL ORDER BY name ASC");

// Fetch users listing with role name and assigned branches
if ($view === 'deleted') {
    $users = Database::fetchAll("
        SELECT u.*, r.name AS role_name, r.code AS role_code,
               GROUP_CONCAT(b.name SEPARATOR ', ') AS assigned_branches,
               GROUP_CONCAT(b.id SEPARATOR ',') AS assigned_branch_ids
        FROM users u
        JOIN roles r ON u.role_id = r.id
        LEFT JOIN user_branches ub ON u.id = ub.user_id
        LEFT JOIN branches b ON ub.branch_id = b.id AND b.deleted_at IS NULL
        WHERE u.deleted_at IS NOT NULL
        GROUP BY u.id
        ORDER BY u.deleted_at DESC, u.full_name ASC
    ");
} else {
    $users = Database::fetchAll("
        SELECT u.*, r.name AS role_name, r.code AS role_code,
               GROUP_CONCAT(b.name SEPARATOR ', ') AS assigned_branches,
               GROUP_CONCAT(b.id SEPARATOR ',') AS assigned_branch_ids
        FROM users u
        JOIN roles r ON u.role_id = r.id
        LEFT JOIN user_branches ub ON u.id = ub.user_id
        LEFT JOIN branches b ON ub.branch_id = b.id AND b.deleted_at IS NULL
        WHERE u.deleted_at IS NULL
        GROUP BY u.id
        ORDER BY u.full_name ASC
    ");
}

View::render('users/index', [
    'users'         => $users,
    'roles'         => $roles,
    'allBranches'   => $allBranches,
    'view'          => $view,
    'activeCount'   => $activeCount,
    'deletedCount'  => $deletedCount,
    'currentUserId' => $currentUserId,
    'successMsg'    => $successMsg,
    'errorMsg'      => $errorMsg
], 'User Accounts & Branch Assignments - Curtiss POS');
