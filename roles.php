<?php
/**
 * Curtiss POS - Roles & Permissions Controller
 * Professional Role & Permission Matrix Management
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
$db = Database::getInstance()->getConnection();

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
    || (isset($_GET['ajax']) && $_GET['ajax'] == '1');

// Handle GET AJAX endpoints
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    $action = trim($_GET['action']);

    if ($action === 'get_role_permissions') {
        $roleId = (int)($_GET['role_id'] ?? 0);
        $role = Database::fetchOne("SELECT * FROM roles WHERE id = :id", [':id' => $roleId]);

        if (!$role) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Role not found.']);
            exit;
        }

        $granted = Database::fetchAll("
            SELECT permission_id 
            FROM role_permissions 
            WHERE role_id = :id
        ", [':id' => $roleId]);
        $grantedIds = array_map('intval', array_column($granted, 'permission_id'));

        header('Content-Type: application/json');
        echo json_encode([
            'success'     => true,
            'role'        => $role,
            'permissions' => $grantedIds,
            'is_super_admin' => ($role['code'] === ROLE_SUPER_ADMIN || $role['code'] === 'superadmin')
        ]);
        exit;
    }
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim($_GET['action'] ?? $_POST['action'] ?? '');

    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? null;
    if (!Auth::validateCsrfToken($csrfToken)) {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Security token invalid or expired. Please refresh the page.']);
        exit;
    }

    if ($action === 'save_permissions') {
        $roleId = (int)($_POST['role_id'] ?? 0);
        $permissionIds = isset($_POST['permission_ids']) && is_array($_POST['permission_ids'])
            ? array_filter(array_map('intval', $_POST['permission_ids']))
            : [];

        $role = Database::fetchOne("SELECT * FROM roles WHERE id = :id", [':id' => $roleId]);
        if (!$role) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Role not found.']);
            exit;
        }

        if ($role['code'] === ROLE_SUPER_ADMIN || $role['code'] === 'superadmin') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Super Administrator automatically has all permissions and cannot be modified.']);
            exit;
        }

        $db->beginTransaction();
        try {
            // Fetch old permissions for audit
            $oldPerms = Database::fetchAll("SELECT permission_id FROM role_permissions WHERE role_id = :id", [':id' => $roleId]);
            $oldIds = array_map('intval', array_column($oldPerms, 'permission_id'));

            // Clear existing permissions
            Database::execute("DELETE FROM role_permissions WHERE role_id = :id", [':id' => $roleId]);

            // Insert new permissions
            $stmtInsert = $db->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (:rid, :pid)");
            foreach ($permissionIds as $pId) {
                $stmtInsert->execute([':rid' => $roleId, ':pid' => $pId]);
            }

            Audit::log('PERMISSIONS_UPDATED', 'roles', (string)$roleId, ['permission_ids' => $oldIds], [
                'role_name'      => $role['name'],
                'role_code'      => $role['code'],
                'permission_ids' => $permissionIds,
                'count'          => count($permissionIds)
            ]);

            $db->commit();
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'message' => "Successfully updated " . count($permissionIds) . " permissions for '{$role['name']}'."
            ]);
            exit;
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to update role permissions: " . $e->getMessage(), $e);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error saving permissions: ' . $e->getMessage()]);
            exit;
        }
    }

    if ($action === 'save_role') {
        $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
        $name = trim($_POST['name'] ?? '');
        $code = strtolower(trim($_POST['code'] ?? ''));
        $description = trim($_POST['description'] ?? '');

        if (empty($name)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Role name is required.']);
            exit;
        }

        if (empty($code)) {
            $code = preg_replace('/[^a-z0-9_]+/', '_', strtolower($name));
        }

        if (!preg_match('/^[a-z0-9_]+$/', $code)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Role code must contain only lowercase letters, numbers, and underscores.']);
            exit;
        }

        // Check code uniqueness
        $existing = Database::fetchOne("
            SELECT id FROM roles 
            WHERE (LOWER(code) = LOWER(:code) OR LOWER(name) = LOWER(:name)) 
              AND id != :id
        ", [':code' => $code, ':name' => $name, ':id' => $id ?? 0]);

        if ($existing) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => "A role with the name '{$name}' or code '{$code}' already exists."]);
            exit;
        }

        try {
            if ($id) {
                // If built-in role, prevent code change
                $oldRole = Database::fetchOne("SELECT * FROM roles WHERE id = :id", [':id' => $id]);
                if (!$oldRole) {
                    throw new Exception("Role not found.");
                }

                $isBuiltin = in_array((int)$id, [1, 2, 3, 4, 5, 6], true);
                if ($isBuiltin) {
                    $code = $oldRole['code']; // keep original code
                }

                Database::execute("
                    UPDATE roles 
                    SET name = :name, code = :code, description = :desc, updated_at = NOW()
                    WHERE id = :id
                ", [':name' => $name, ':code' => $code, ':desc' => $description ?: null, ':id' => $id]);

                Audit::log('ROLE_UPDATED', 'roles', (string)$id, $oldRole, [
                    'name' => $name,
                    'code' => $code,
                    'description' => $description
                ]);
                $msg = "Role '{$name}' updated successfully.";
            } else {
                Database::execute("
                    INSERT INTO roles (name, code, description, created_at, updated_at)
                    VALUES (:name, :code, :desc, NOW(), NOW())
                ", [':name' => $name, ':code' => $code, ':desc' => $description ?: null]);
                $id = (int)Database::lastInsertId();

                Audit::log('ROLE_CREATED', 'roles', (string)$id, null, [
                    'name' => $name,
                    'code' => $code,
                    'description' => $description
                ]);
                $msg = "Custom role '{$name}' created successfully.";
            }

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => $msg, 'role_id' => $id]);
            exit;
        } catch (Throwable $e) {
            Logger::error("Failed to save role: " . $e->getMessage(), $e);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Server error saving role: ' . $e->getMessage()]);
            exit;
        }
    }

    if ($action === 'delete_role') {
        $id = (int)($_POST['id'] ?? 0);

        if (in_array($id, [1, 2, 3, 4, 5, 6], true)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Built-in system roles cannot be deleted.']);
            exit;
        }

        $userCount = (int)Database::fetchValue("
            SELECT COUNT(*) FROM (
                SELECT user_id FROM user_roles WHERE role_id = :id
                UNION
                SELECT id FROM users WHERE role_id = :id AND deleted_at IS NULL
            ) t
        ", [':id' => $id]);

        if ($userCount > 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => "Cannot delete role: {$userCount} user accounts are currently assigned to this role."]);
            exit;
        }

        $role = Database::fetchOne("SELECT * FROM roles WHERE id = :id", [':id' => $id]);
        if (!$role) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Role not found.']);
            exit;
        }

        $db->beginTransaction();
        try {
            Database::execute("DELETE FROM role_permissions WHERE role_id = :id", [':id' => $id]);
            Database::execute("DELETE FROM roles WHERE id = :id", [':id' => $id]);

            Audit::log('ROLE_DELETED', 'roles', (string)$id, $role, null);
            $db->commit();

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => "Role '{$role['name']}' deleted successfully."]);
            exit;
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to delete role: " . $e->getMessage(), $e);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Server error deleting role: ' . $e->getMessage()]);
            exit;
        }
    }
}

// Fetch all roles with assigned user counts and granted permission counts
$roles = Database::fetchAll("
    SELECT 
        r.*,
        (
            SELECT COUNT(DISTINCT u.id) 
            FROM users u
            LEFT JOIN user_roles ur ON u.id = ur.user_id
            WHERE (u.role_id = r.id OR ur.role_id = r.id)
              AND u.deleted_at IS NULL
        ) AS user_count,
        (
            SELECT COUNT(*) 
            FROM role_permissions rp 
            WHERE rp.role_id = r.id
        ) AS permission_count
    FROM roles r
    ORDER BY r.id ASC
");

// Fetch all system permissions grouped by module
$rawPerms = Database::fetchAll("
    SELECT id, permission_code, module, description
    FROM permissions
    ORDER BY module ASC, id ASC
");

$totalPermissionsCount = count($rawPerms);
$permissionsByModule = [];
foreach ($rawPerms as $p) {
    $mod = $p['module'];
    if (!isset($permissionsByModule[$mod])) {
        $permissionsByModule[$mod] = [];
    }
    $permissionsByModule[$mod][] = $p;
}

$pageTitle = 'Roles & Permissions - Curtiss POS';

View::render('roles/index', [
    'roles'                 => $roles,
    'permissionsByModule'   => $permissionsByModule,
    'totalPermissionsCount' => $totalPermissionsCount,
    'currentUserId'         => $currentUserId
], $pageTitle);
