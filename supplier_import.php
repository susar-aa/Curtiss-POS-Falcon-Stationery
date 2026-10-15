<?php
/**
 * Curtiss POS - Supplier Import Controller
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/SupplierImportEngine.php';

Auth::requireAuth();

if (!Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)) {
    View::renderBlank('errors/403');
    exit();
}

$errorMsg = null;
$successMsg = null;
$previewData = null;

$tempDir = __DIR__ . '/storage/tmp';
if (!is_dir($tempDir)) {
    mkdir($tempDir, 0777, true);
}

if (($_GET['action'] ?? '') === 'template') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="supplier_import_template.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Supplier Code', 'Supplier Name', 'Phone', 'WhatsApp', 'Address', 'Status']);
    fputcsv($out, ['SUP-0001', 'Atlas Axillia Co Ltd', '+94 11 269 1111', '+94 77 123 4567', 'Peliyagoda', 'active']);
    fclose($out);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $mode = $_POST['mode'] ?? SupplierImportEngine::MODE_CREATE_AND_UPDATE;
    $ignoreBlank = isset($_POST['ignore_blank']) ? (bool)$_POST['ignore_blank'] : true;

    if ($action === 'preview') {
        if (!empty($_FILES['csv_file']['tmp_name'])) {
            $tmpFile = $tempDir . '/sup_import_' . time() . '_' . rand(1000, 9999) . '.csv';
            if (move_uploaded_file($_FILES['csv_file']['tmp_name'], $tmpFile)) {
                $res = SupplierImportEngine::preview($tmpFile, $mode, $ignoreBlank);
                if ($res['success']) {
                    $previewData = $res;
                    $previewData['temp_file'] = basename($tmpFile);
                } else {
                    $errorMsg = $res['message'];
                }
            } else {
                $errorMsg = 'Failed to upload CSV file.';
            }
        } else {
            $errorMsg = 'Please select a CSV file to import.';
        }
    } elseif ($action === 'commit') {
        $tempFileName = basename($_POST['temp_file'] ?? '');
        $tempFile = $tempDir . '/' . $tempFileName;

        if (file_exists($tempFile)) {
            $res = SupplierImportEngine::import($tempFile, $mode, $ignoreBlank);
            if ($res['success']) {
                $successMsg = $res['message'];
                @unlink($tempFile);
            } else {
                $errorMsg = $res['message'];
            }
        } else {
            $errorMsg = 'Import session expired. Please re-upload the CSV file.';
        }
    }
}

View::render('suppliers/import', [
    'errorMsg'    => $errorMsg,
    'successMsg'  => $successMsg,
    'previewData' => $previewData
], 'Supplier Import - Curtiss POS');
