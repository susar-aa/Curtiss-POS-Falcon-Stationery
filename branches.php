<?php
/**
 * Curtiss POS - Branch Management Controller
 * Handles Multi-Branch Registry, Status Control, Trash Bin Soft Deletion & Restoration,
 * Realtime Search, and Operational Statistics.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Audit.php';
require_once __DIR__ . '/core/BusinessDay.php';
require_once __DIR__ . '/core/Logger.php';
require_once __DIR__ . '/core/View.php';

Auth::requireAuth();

if (!Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)) {
    View::renderBlank('errors/403');
    exit();
}

$user = Auth::user();
$userId = (int)($user['id'] ?? 1);
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
        $branch = Database::fetchOne("SELECT * FROM branches WHERE id = :id", [':id' => $id]);
        if (!$branch) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Branch not found.']);
            exit;
        }

        // Fetch live branch statistics
        $staffCount = (int)Database::fetchValue("SELECT COUNT(*) FROM employees WHERE branch_id = :id AND deleted_at IS NULL", [':id' => $id]);
        $userCount = (int)Database::fetchValue("SELECT COUNT(DISTINCT user_id) FROM user_branches WHERE branch_id = :id", [':id' => $id]);
        $salesRow = Database::fetchOne("
            SELECT COUNT(id) AS sales_count, COALESCE(SUM(total_amount), 0.00) AS total_sales 
            FROM sales 
            WHERE branch_id = :id AND status != 'cancelled'
        ", [':id' => $id]);
        $inventoryCount = (int)Database::fetchValue("SELECT COUNT(id) FROM branch_inventory WHERE branch_id = :id AND quantity > 0", [':id' => $id]);
        $activeDay = BusinessDay::getActiveDay($id);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'branch'  => $branch,
            'stats'   => [
                'staff_count'     => $staffCount,
                'user_count'      => $userCount,
                'sales_count'     => (int)($salesRow['sales_count'] ?? 0),
                'total_sales'     => (float)($salesRow['total_sales'] ?? 0.00),
                'inventory_skus'  => $inventoryCount,
                'has_active_day'  => !empty($activeDay),
                'active_day_info' => $activeDay ? [
                    'id'            => $activeDay['id'],
                    'business_date' => $activeDay['business_date'] ?? date('Y-m-d'),
                    'opened_by'     => $activeDay['opener_name'] ?? 'System'
                ] : null
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
            $name = trim($_POST['name'] ?? '');
            $code = strtoupper(trim($_POST['code'] ?? ''));
            $phone = trim($_POST['phone'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $address = trim($_POST['address'] ?? '');

            if (empty($name) || empty($code)) {
                $err = "Branch Name and Branch Code are mandatory.";
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $err]);
                    exit;
                }
                $errorMsg = $err;
            } else {
                // Check unique code case-insensitively across non-deleted branches
                $existing = Database::fetchOne("
                    SELECT id FROM branches 
                    WHERE LOWER(code) = LOWER(:code) 
                    AND id != :id 
                    AND deleted_at IS NULL
                ", [
                    ':code' => $code,
                    ':id'   => $id ?? 0
                ]);

                if ($existing) {
                    $err = "A branch with the code '{$code}' already exists.";
                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => $err]);
                        exit;
                    }
                    $errorMsg = $err;
                } else {
                    if ($id) {
                        $old = Database::fetchOne("SELECT * FROM branches WHERE id = :id", [':id' => $id]);
                        if (!$old) {
                            $err = "Branch record not found.";
                            if ($isAjax) {
                                header('Content-Type: application/json');
                                echo json_encode(['success' => false, 'message' => $err]);
                                exit;
                            }
                            $errorMsg = $err;
                        } else {
                            Database::execute("
                                UPDATE branches 
                                SET name = :name, code = :code, phone = :phone, email = :email, address = :address, updated_at = NOW()
                                WHERE id = :id
                            ", [
                                ':name'    => $name,
                                ':code'    => $code,
                                ':phone'   => $phone,
                                ':email'   => $email,
                                ':address' => $address,
                                ':id'      => $id
                            ]);

                            Audit::log('BRANCH_UPDATED', 'branches', (string)$id, $old, [
                                'name'    => $name,
                                'code'    => $code,
                                'phone'   => $phone,
                                'email'   => $email,
                                'address' => $address
                            ]);
                            $msg = "Branch '{$name}' updated successfully.";
                            if ($isAjax) {
                                header('Content-Type: application/json');
                                echo json_encode(['success' => true, 'message' => $msg]);
                                exit;
                            }
                            $successMsg = $msg;
                        }
                    } else {
                        // New records default to 'active' automatically
                        Database::execute("
                            INSERT INTO branches (name, code, phone, email, address, status, created_at, updated_at)
                            VALUES (:name, :code, :phone, :email, :address, 'active', NOW(), NOW())
                        ", [
                            ':name'    => $name,
                            ':code'    => $code,
                            ':phone'   => $phone,
                            ':email'   => $email,
                            ':address' => $address
                        ]);
                        $newId = Database::lastInsertId();

                        // Automatically associate creating admin to user_branches
                        Database::execute("
                            INSERT IGNORE INTO user_branches (user_id, branch_id, is_primary) 
                            VALUES (:uid, :bid, 0)
                        ", [':uid' => $userId, ':bid' => $newId]);

                        Audit::log('BRANCH_CREATED', 'branches', (string)$newId, null, [
                            'name'    => $name,
                            'code'    => $code,
                            'phone'   => $phone,
                            'email'   => $email,
                            'address' => $address,
                            'status'  => 'active'
                        ]);
                        $msg = "Branch '{$name}' created successfully.";
                        if ($isAjax) {
                            header('Content-Type: application/json');
                            echo json_encode(['success' => true, 'message' => $msg, 'branch_id' => $newId]);
                            exit;
                        }
                        $successMsg = $msg;
                    }
                }
            }
        } elseif ($action === 'toggle_status') {
            $id = (int)($_POST['id'] ?? 0);
            $branch = Database::fetchOne("SELECT * FROM branches WHERE id = :id AND deleted_at IS NULL", [':id' => $id]);
            if (!$branch) {
                $err = "Active branch not found.";
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $err]);
                    exit;
                }
                $errorMsg = $err;
            } else {
                $newStatus = ($branch['status'] === 'active') ? 'inactive' : 'active';

                // Safety: Prevent deactivating the last active branch or branch with open business day
                if ($newStatus === 'inactive') {
                    $activeCount = (int)Database::fetchValue("SELECT COUNT(*) FROM branches WHERE status = 'active' AND deleted_at IS NULL");
                    if ($activeCount <= 1) {
                        $err = "Cannot deactivate the only active branch in the system.";
                        if ($isAjax) {
                            header('Content-Type: application/json');
                            echo json_encode(['success' => false, 'message' => $err]);
                            exit;
                        }
                        $errorMsg = $err;
                    } else {
                        $activeDay = BusinessDay::getActiveDay($id);
                        if ($activeDay) {
                            $err = "Cannot deactivate branch while an active business day is open.";
                            if ($isAjax) {
                                header('Content-Type: application/json');
                                echo json_encode(['success' => false, 'message' => $err]);
                                exit;
                            }
                            $errorMsg = $err;
                        }
                    }
                }

                if (empty($errorMsg)) {
                    Database::execute("UPDATE branches SET status = :status, updated_at = NOW() WHERE id = :id", [
                        ':status' => $newStatus,
                        ':id'     => $id
                    ]);
                    Audit::log('BRANCH_STATUS_TOGGLED', 'branches', (string)$id, ['status' => $branch['status']], ['status' => $newStatus]);

                    $msg = "Branch '{$branch['name']}' status updated to " . ucfirst($newStatus) . ".";
                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true, 'message' => $msg, 'status' => $newStatus]);
                        exit;
                    }
                    $successMsg = $msg;
                }
            }
        } elseif ($action === 'delete') {
            // Soft delete into Trash Bin
            $id = (int)($_POST['id'] ?? 0);
            if ($id === 1) {
                $err = "The primary Main Branch (ID #1) is required by system core operations and cannot be deleted.";
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $err]);
                    exit;
                }
                $errorMsg = $err;
            } else {
                $branch = Database::fetchOne("SELECT * FROM branches WHERE id = :id AND deleted_at IS NULL", [':id' => $id]);
                if (!$branch) {
                    $err = "Branch not found or already in Trash Bin.";
                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => $err]);
                        exit;
                    }
                    $errorMsg = $err;
                } else {
                    $activeDay = BusinessDay::getActiveDay($id);
                    if ($activeDay) {
                        $err = "Cannot delete branch with an active business day in progress. Please close the day first.";
                        if ($isAjax) {
                            header('Content-Type: application/json');
                            echo json_encode(['success' => false, 'message' => $err]);
                            exit;
                        }
                        $errorMsg = $err;
                    } else {
                        Database::execute("
                            UPDATE branches 
                            SET deleted_at = NOW(), status = 'inactive', updated_at = NOW() 
                            WHERE id = :id
                        ", [':id' => $id]);

                        Audit::log('BRANCH_DELETED', 'branches', (string)$id, $branch, [
                            'deleted_at' => date('Y-m-d H:i:s'),
                            'status'     => 'inactive'
                        ]);

                        $msg = "Branch '{$branch['name']}' moved to Trash Bin.";
                        if ($isAjax) {
                            header('Content-Type: application/json');
                            echo json_encode(['success' => true, 'message' => $msg]);
                            exit;
                        }
                        $successMsg = $msg;
                    }
                }
            }
        } elseif ($action === 'restore') {
            // Restore from Trash Bin
            $id = (int)($_POST['id'] ?? 0);
            $branch = Database::fetchOne("SELECT * FROM branches WHERE id = :id AND deleted_at IS NOT NULL", [':id' => $id]);
            if (!$branch) {
                $err = "Deleted branch not found in Trash Bin.";
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $err]);
                    exit;
                }
                $errorMsg = $err;
            } else {
                // Check if active branch already uses this code
                $conflict = Database::fetchOne("
                    SELECT id FROM branches 
                    WHERE LOWER(code) = LOWER(:code) 
                    AND deleted_at IS NULL 
                    AND id != :id
                ", [':code' => $branch['code'], ':id' => $id]);

                if ($conflict) {
                    $err = "Cannot restore branch: Another active branch is already using code '{$branch['code']}'.";
                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => $err]);
                        exit;
                    }
                    $errorMsg = $err;
                } else {
                    Database::execute("
                        UPDATE branches 
                        SET deleted_at = NULL, status = 'active', updated_at = NOW() 
                        WHERE id = :id
                    ", [':id' => $id]);

                    Audit::log('BRANCH_RESTORED', 'branches', (string)$id, ['deleted_at' => $branch['deleted_at']], [
                        'deleted_at' => null,
                        'status'     => 'active'
                    ]);

                    $msg = "Branch '{$branch['name']}' restored successfully.";
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

// Determine view mode (active branches vs trash bin)
$view = trim($_GET['view'] ?? 'active');
if (!in_array($view, ['active', 'deleted'], true)) {
    $view = 'active';
}

// Counts for navigation pills
$activeCount = (int)Database::fetchValue("SELECT COUNT(*) FROM branches WHERE deleted_at IS NULL");
$deletedCount = (int)Database::fetchValue("SELECT COUNT(*) FROM branches WHERE deleted_at IS NOT NULL");

// Fetch branches with employee count for the table
if ($view === 'deleted') {
    $branches = Database::fetchAll("
        SELECT 
            b.*,
            (SELECT COUNT(*) FROM employees e WHERE e.branch_id = b.id AND e.deleted_at IS NULL) AS staff_count
        FROM branches b
        WHERE b.deleted_at IS NOT NULL 
        ORDER BY b.deleted_at DESC, b.id DESC
    ");
} else {
    $branches = Database::fetchAll("
        SELECT 
            b.*,
            (SELECT COUNT(*) FROM employees e WHERE e.branch_id = b.id AND e.deleted_at IS NULL) AS staff_count
        FROM branches b
        WHERE b.deleted_at IS NULL 
        ORDER BY b.id ASC
    ");
}

View::render('branches/index', [
    'branches'     => $branches,
    'view'         => $view,
    'activeCount'  => $activeCount,
    'deletedCount' => $deletedCount,
    'successMsg'   => $successMsg,
    'errorMsg'     => $errorMsg
], 'Branch Management - Curtiss POS');
