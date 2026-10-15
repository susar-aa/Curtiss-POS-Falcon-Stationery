<?php
/**
 * Curtiss POS - Personal To-Do API
 * 
 * All ownership is resolved from SESSION, never from client payload.
 * IDOR: user_id is never accepted from the request body/query string.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Logger.php';

header('Content-Type: application/json');

// Must be authenticated
if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthenticated']);
    exit;
}

// CSRF check for mutating operations
$method = $_SERVER['REQUEST_METHOD'];
$user   = Auth::user();
$userId = (int)$user['id'];
$db     = Database::getInstance();
$pdo    = $db->getConnection();

function csrf_verify() {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['_csrf'] ?? '');
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        echo json_encode(['error' => 'CSRF token mismatch']);
        exit;
    }
}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {

        // ── LIST ─────────────────────────────────────────────────────────────
        case 'list':
            $stmt = $pdo->prepare(
                "SELECT id, title, notes, priority, due_date, completed, completed_at, created_at
                 FROM user_todos
                 WHERE user_id = :uid
                 ORDER BY completed ASC, priority DESC, due_date ASC, created_at DESC
                 LIMIT 50"
            );
            $stmt->execute([':uid' => $userId]);
            echo json_encode(['ok' => true, 'todos' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        // ── CREATE ───────────────────────────────────────────────────────────
        case 'create':
            if ($method !== 'POST') { http_response_code(405); echo json_encode(['error'=>'POST required']); exit; }
            csrf_verify();
            $input    = json_decode(file_get_contents('php://input'), true) ?? [];
            $title    = trim($input['title'] ?? '');
            $notes    = trim($input['notes'] ?? '');
            $priority = in_array($input['priority'] ?? '', ['low','normal','high']) ? $input['priority'] : 'normal';
            $dueDate  = !empty($input['due_date']) ? $input['due_date'] : null;

            if ($title === '') {
                http_response_code(422);
                echo json_encode(['error' => 'Title is required']);
                exit;
            }

            $stmt = $pdo->prepare(
                "INSERT INTO user_todos (user_id, title, notes, priority, due_date)
                 VALUES (:uid, :title, :notes, :priority, :due_date)"
            );
            $stmt->execute([
                ':uid'      => $userId,
                ':title'    => $title,
                ':notes'    => $notes ?: null,
                ':priority' => $priority,
                ':due_date' => $dueDate,
            ]);
            echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId()]);
            break;

        // ── TOGGLE COMPLETE ───────────────────────────────────────────────────
        case 'toggle':
            if ($method !== 'POST') { http_response_code(405); echo json_encode(['error'=>'POST required']); exit; }
            csrf_verify();
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $id    = (int)($input['id'] ?? 0);
            if ($id < 1) { http_response_code(422); echo json_encode(['error'=>'Invalid id']); exit; }

            // Ownership check via WHERE user_id
            $stmt = $pdo->prepare(
                "UPDATE user_todos
                 SET completed = NOT completed,
                     completed_at = CASE WHEN completed = 0 THEN NOW() ELSE NULL END
                 WHERE id = :id AND user_id = :uid"
            );
            $stmt->execute([':id' => $id, ':uid' => $userId]);
            echo json_encode(['ok' => true, 'affected' => $stmt->rowCount()]);
            break;

        // ── DELETE ────────────────────────────────────────────────────────────
        case 'delete':
            if ($method !== 'POST') { http_response_code(405); echo json_encode(['error'=>'POST required']); exit; }
            csrf_verify();
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $id    = (int)($input['id'] ?? 0);
            if ($id < 1) { http_response_code(422); echo json_encode(['error'=>'Invalid id']); exit; }

            // Ownership check via WHERE user_id
            $stmt = $pdo->prepare("DELETE FROM user_todos WHERE id = :id AND user_id = :uid");
            $stmt->execute([':id' => $id, ':uid' => $userId]);
            echo json_encode(['ok' => true, 'affected' => $stmt->rowCount()]);
            break;

        // ── UPDATE ────────────────────────────────────────────────────────────
        case 'update':
            if ($method !== 'POST') { http_response_code(405); echo json_encode(['error'=>'POST required']); exit; }
            csrf_verify();
            $input    = json_decode(file_get_contents('php://input'), true) ?? [];
            $id       = (int)($input['id'] ?? 0);
            $title    = trim($input['title'] ?? '');
            $notes    = trim($input['notes'] ?? '');
            $priority = in_array($input['priority'] ?? '', ['low','normal','high']) ? $input['priority'] : 'normal';
            $dueDate  = !empty($input['due_date']) ? $input['due_date'] : null;

            if ($id < 1 || $title === '') {
                http_response_code(422); echo json_encode(['error' => 'Invalid input']); exit;
            }

            $stmt = $pdo->prepare(
                "UPDATE user_todos
                 SET title = :title, notes = :notes, priority = :priority, due_date = :due_date
                 WHERE id = :id AND user_id = :uid"
            );
            $stmt->execute([
                ':id'       => $id,
                ':uid'      => $userId,
                ':title'    => $title,
                ':notes'    => $notes ?: null,
                ':priority' => $priority,
                ':due_date' => $dueDate,
            ]);
            echo json_encode(['ok' => true, 'affected' => $stmt->rowCount()]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Unknown action']);
    }

} catch (Throwable $e) {
    Logger::exception($e);
    http_response_code(500);
    echo json_encode(['error' => 'Server error', 'detail' => $e->getMessage()]);
}
