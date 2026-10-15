<?php
/**
 * Curtiss POS - Customer Import Endpoint / Controller (Section 5)
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/CustomerImportEngine.php';

Auth::requireLogin();

if (!Auth::hasPermission('import_customers') && !Auth::hasPermission('manage_customers')) {
    http_response_code(403);
    die('Unauthorized: You do not have permission to import customers.');
}

$action = $_POST['action'] ?? $_GET['action'] ?? 'view';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $options = [
        'mode'          => $_POST['mode'] ?? 'create_update',
        'ignore_blanks' => isset($_POST['ignore_blanks']) ? ($_POST['ignore_blanks'] === '1' || $_POST['ignore_blanks'] === 'true') : true
    ];

    if ($action === 'preview') {
        if (empty($_FILES['csv_file']['tmp_name'])) {
            echo json_encode(['success' => false, 'message' => 'Please upload a CSV file.']);
            exit;
        }
        $res = CustomerImportEngine::previewImport($_FILES['csv_file']['tmp_name'], $options);
        echo json_encode($res);
        exit;
    }

    if ($action === 'commit') {
        if (empty($_FILES['csv_file']['tmp_name'])) {
            echo json_encode(['success' => false, 'message' => 'Please upload a CSV file.']);
            exit;
        }
        $userId = Auth::user()['id'] ?? 1;
        $res = CustomerImportEngine::commitImport($_FILES['csv_file']['tmp_name'], $options, $userId);
        echo json_encode($res);
        exit;
    }
}
