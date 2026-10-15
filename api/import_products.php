<?php
/**
 * Curtiss POS - Product Master Import API Endpoint
 * Handles import preview validation and committed batch execution.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/ProductImportEngine.php';
require_once __DIR__ . '/../core/Auth.php';

header('Content-Type: application/json; charset=utf-8');

try {
    Auth::requirePermission('products.import');

    $action = $_REQUEST['action'] ?? '';

    if ($action === 'template') {
        $csvContent = ProductImportEngine::generateCsvTemplate();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=curtiss_product_import_template.csv');
        echo "\xEF\xBB\xBF" . $csvContent;
        exit();
    }

    if ($action === 'preview') {
        if (empty($_FILES['import_file']['tmp_name'])) {
            echo json_encode(['success' => false, 'message' => 'Please upload a valid CSV file.']);
            exit();
        }

        $filePath = $_FILES['import_file']['tmp_name'];
        $parsed = ProductImportEngine::parseCsvFile($filePath);
        if (!$parsed['success']) {
            echo json_encode($parsed);
            exit();
        }

        $headers = $parsed['headers'];
        $mapping = ProductImportEngine::autoDetectMapping($headers);
        $importMode = $_POST['import_mode'] ?? 'create_and_update';
        $options = [
            'ignore_blank_cells'  => isset($_POST['ignore_blank_cells']) ? (bool)$_POST['ignore_blank_cells'] : true,
            'auto_create_masters' => isset($_POST['auto_create_masters']) ? (bool)$_POST['auto_create_masters'] : true
        ];

        // Store parsed rows in session for commit step
        $_SESSION['pending_import_parsed'] = $parsed;
        $_SESSION['pending_import_file_name'] = $_FILES['import_file']['name'];

        $previewResult = ProductImportEngine::preview($parsed, $mapping, $importMode, $options);
        $previewResult['mapping'] = $mapping;
        $previewResult['headers'] = $headers;

        echo json_encode($previewResult);
        exit();
    }

    if ($action === 'commit') {
        $parsed = $_SESSION['pending_import_parsed'] ?? null;
        if (!$parsed) {
            echo json_encode(['success' => false, 'message' => 'No pending import found. Please upload file again.']);
            exit();
        }

        $headers = $parsed['headers'];
        $mapping = ProductImportEngine::autoDetectMapping($headers);

        // Allow user override of mapping if provided
        if (!empty($_POST['mapping'])) {
            $userMap = json_decode($_POST['mapping'], true);
            if (is_array($userMap)) {
                $mapping = array_merge($mapping, $userMap);
            }
        }

        $importMode = $_POST['import_mode'] ?? 'create_and_update';
        $options = [
            'file_name'           => $_SESSION['pending_import_file_name'] ?? 'product_import.csv',
            'ignore_blank_cells'  => isset($_POST['ignore_blank_cells']) ? (bool)$_POST['ignore_blank_cells'] : true,
            'auto_create_masters' => isset($_POST['auto_create_masters']) ? (bool)$_POST['auto_create_masters'] : true,
            'branch_id'           => (int)($_SESSION['active_branch_id'] ?? 1)
        ];

        $userId = (int)($_SESSION['user_id'] ?? 1);

        $commitResult = ProductImportEngine::commitImport($parsed, $mapping, $importMode, $options, $userId);

        // Clear session preview cache
        unset($_SESSION['pending_import_parsed']);
        unset($_SESSION['pending_import_file_name']);

        echo json_encode($commitResult);
        exit();
    }

    echo json_encode(['success' => false, 'message' => 'Invalid action.']);

} catch (Throwable $e) {
    Logger::error("Product import API error: " . $e->getMessage(), $e);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
