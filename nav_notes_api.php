<?php
/**
 * Curtiss POS - Personal Notes API
 * 
 * One note per user. Ownership enforced server-side from SESSION.
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

$method = $_SERVER['REQUEST_METHOD'];
$user   = Auth::user();
$userId = (int)$user['id'];
$db     = Database::getInstance();
$pdo    = $db->getConnection();
$action = $_GET['action'] ?? '';

function csrf_notes_verify() {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['_csrf'] ?? '');
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        echo json_encode(['error' => 'CSRF token mismatch']);
        exit;
    }
}

try {
    switch ($action) {

        case 'get':
            $stmt = $pdo->prepare("SELECT content, updated_at FROM user_notes WHERE user_id = :uid");
            $stmt->execute([':uid' => $userId]);
            $row  = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['ok' => true, 'content' => $row['content'] ?? '', 'updated_at' => $row['updated_at'] ?? null]);
            break;

        case 'save':
            if ($method !== 'POST') { http_response_code(405); echo json_encode(['error'=>'POST required']); exit; }
            csrf_notes_verify();
            $input   = json_decode(file_get_contents('php://input'), true) ?? [];
            $content = $input['content'] ?? '';

            // UPSERT - one note per user
            $stmt = $pdo->prepare(
                "INSERT INTO user_notes (user_id, content)
                 VALUES (:uid, :content)
                 ON DUPLICATE KEY UPDATE content = VALUES(content)"
            );
            $stmt->execute([':uid' => $userId, ':content' => $content]);
            echo json_encode(['ok' => true]);
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
