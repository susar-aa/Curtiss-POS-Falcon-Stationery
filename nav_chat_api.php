<?php
/**
 * Curtiss POS - Employee Branch Chat API
 * 
 * Security:
 * - sender_id always resolved from SESSION, never from request body
 * - branch_id resolved from session active branch; client-provided branch rejected
 * - Messages escaped on output (htmlspecialchars in JSON)
 * - Prepared statements only
 * - CSRF on POST
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Logger.php';

header('Content-Type: application/json');

if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthenticated']);
    exit;
}

$method    = $_SERVER['REQUEST_METHOD'];
$user      = Auth::user();
$senderId  = (int)$user['id'];
// Branch is ALWAYS taken from the authenticated session, never the client
$branchId  = (int)($user['active_branch_id'] ?? 0);

if ($branchId < 1) {
    http_response_code(403);
    echo json_encode(['error' => 'No active branch in session']);
    exit;
}

$db     = Database::getInstance();
$pdo    = $db->getConnection();
$action = $_GET['action'] ?? '';

function csrf_chat_verify() {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['_csrf'] ?? '');
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        echo json_encode(['error' => 'CSRF token mismatch']);
        exit;
    }
}

try {
    switch ($action) {

        // ── FETCH RECENT MESSAGES ─────────────────────────────────────────────
        case 'messages':
            $since   = $_GET['since_id'] ?? 0;
            $sinceId = (int)$since;

            // Only messages from this branch
            $stmt = $pdo->prepare(
                "SELECT c.id, c.sender_id, c.message, c.sent_at,
                        u.full_name AS sender_name,
                        u.username  AS sender_username
                 FROM employee_chat c
                 JOIN users u ON u.id = c.sender_id
                 WHERE c.branch_id = :branch_id
                   AND c.id > :since_id
                 ORDER BY c.sent_at ASC
                 LIMIT 80"
            );
            $stmt->execute([':branch_id' => $branchId, ':since_id' => $sinceId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // XSS-escape on output
            foreach ($rows as &$row) {
                $row['message']         = htmlspecialchars($row['message'], ENT_QUOTES, 'UTF-8');
                $row['sender_name']     = htmlspecialchars($row['sender_name'], ENT_QUOTES, 'UTF-8');
                $row['sender_username'] = htmlspecialchars($row['sender_username'], ENT_QUOTES, 'UTF-8');
            }
            unset($row);

            // Return max id for polling
            $maxId = !empty($rows) ? max(array_column($rows, 'id')) : $sinceId;
            echo json_encode(['ok' => true, 'messages' => $rows, 'max_id' => $maxId, 'my_sender_id' => $senderId]);
            break;

        // ── SEND MESSAGE ──────────────────────────────────────────────────────
        case 'send':
            if ($method !== 'POST') { http_response_code(405); echo json_encode(['error'=>'POST required']); exit; }
            csrf_chat_verify();

            $input   = json_decode(file_get_contents('php://input'), true) ?? [];
            $message = trim($input['message'] ?? '');

            if ($message === '') {
                http_response_code(422);
                echo json_encode(['error' => 'Message cannot be empty']);
                exit;
            }

            if (strlen($message) > 2000) {
                http_response_code(422);
                echo json_encode(['error' => 'Message too long (max 2000 chars)']);
                exit;
            }

            // sender_id and branch_id come from SESSION only
            $stmt = $pdo->prepare(
                "INSERT INTO employee_chat (branch_id, sender_id, message) VALUES (:branch_id, :sender_id, :message)"
            );
            $stmt->execute([
                ':branch_id' => $branchId,
                ':sender_id' => $senderId,
                ':message'   => $message,
            ]);
            echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId()]);
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
