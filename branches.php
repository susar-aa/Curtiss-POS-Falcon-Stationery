<?php
/**
 * Curtiss POS - Branch Management Controller
 */

require_once __DIR__ . '/config/config.php';

Auth::requireAuth();

if (!Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)) {
    View::renderBlank('errors/403');
    exit();
}

$successMsg = null;
$errorMsg = null;

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_GET['action'] ?? $_POST['action'] ?? 'save';
    $user = Auth::user();

    if ($action === 'save') {
        $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
        $name = trim($_POST['name'] ?? '');
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

        if (empty($name) || empty($code)) {
            $errorMsg = "Branch Name and Branch Code are mandatory.";
        } else {
            // Check unique code
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
                $errorMsg = "A branch with the code '{$code}' already exists.";
            } else {
                if ($id) {
                    $old = Database::fetchOne("SELECT * FROM branches WHERE id = :id", [':id' => $id]);
                    Database::execute("
                        UPDATE branches 
                        SET name = :name, code = :code, phone = :phone, email = :email, address = :address, status = :status, updated_at = NOW()
                        WHERE id = :id
                    ", [
                        ':name'    => $name,
                        ':code'    => $code,
                        ':phone'   => $phone,
                        ':email'   => $email,
                        ':address' => $address,
                        ':status'  => $status,
                        ':id'      => $id
                    ]);

                    Audit::log('BRANCH_UPDATED', 'branches', (string)$id, $old, [
                        'name'    => $name,
                        'code'    => $code,
                        'phone'   => $phone,
                        'email'   => $email,
                        'address' => $address,
                        'status'  => $status
                    ]);
                    $successMsg = "Branch '{$name}' updated successfully.";
                } else {
                    Database::execute("
                        INSERT INTO branches (name, code, phone, email, address, status, created_at, updated_at)
                        VALUES (:name, :code, :phone, :email, :address, :status, NOW(), NOW())
                    ", [
                        ':name'    => $name,
                        ':code'    => $code,
                        ':phone'   => $phone,
                        ':email'   => $email,
                        ':address' => $address,
                        ':status'  => $status
                    ]);
                    $newId = Database::lastInsertId();

                    Audit::log('BRANCH_CREATED', 'branches', (string)$newId, null, [
                        'name'    => $name,
                        'code'    => $code,
                        'phone'   => $phone,
                        'email'   => $email,
                        'address' => $address,
                        'status'  => $status
                    ]);
                    $successMsg = "Branch '{$name}' created successfully.";
                }
            }
        }
    } elseif ($action === 'toggle_status') {
        $id = (int)($_POST['id'] ?? 0);
        $branch = Database::fetchOne("SELECT * FROM branches WHERE id = :id", [':id' => $id]);
        if ($branch) {
            $newStatus = ($branch['status'] === 'active') ? 'inactive' : 'active';
            Database::execute("UPDATE branches SET status = :status, updated_at = NOW() WHERE id = :id", [
                ':status' => $newStatus,
                ':id'     => $id
            ]);
            Audit::log('BRANCH_STATUS_TOGGLED', 'branches', (string)$id, ['status' => $branch['status']], ['status' => $newStatus]);
            
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'status' => $newStatus]);
                exit;
            }
            $successMsg = "Branch status updated to " . ucfirst($newStatus) . ".";
        }
    }
}

$branches = Database::fetchAll("
    SELECT * FROM branches 
    WHERE deleted_at IS NULL 
    ORDER BY id ASC
");

View::render('branches/index', [
    'branches'   => $branches,
    'successMsg' => $successMsg,
    'errorMsg'   => $errorMsg
], 'Branch Management - Curtiss POS');

