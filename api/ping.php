<?php
/**
 * Curtiss POS - Session Heartbeat / Ping Endpoint
 * Refreshes session inactivity timestamp on active user interaction
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Auth.php';

header('Content-Type: application/json');

if (!Auth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthenticated session'
    ]);
    exit();
}

// Verify and refresh activity timestamp
Auth::checkInactivityTimeout();

echo json_encode([
    'success' => true,
    'user_id' => Auth::getUserId(),
    'timestamp' => time()
]);
