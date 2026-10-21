<?php
require_once 'c:/xampp/htdocs/CURTISS/FalconStationery/POS/config/config.php';
require_once 'c:/xampp/htdocs/CURTISS/FalconStationery/POS/core/Database.php';
require_once 'c:/xampp/htdocs/CURTISS/FalconStationery/POS/core/ProductImportEngine.php';
require_once 'c:/xampp/htdocs/CURTISS/FalconStationery/POS/core/Product.php';
require_once 'c:/xampp/htdocs/CURTISS/FalconStationery/POS/core/Brand.php';
require_once 'c:/xampp/htdocs/CURTISS/FalconStationery/POS/core/Category.php';
require_once 'c:/xampp/htdocs/CURTISS/FalconStationery/POS/core/Supplier.php';

$csvFile = 'c:/xampp/htdocs/CURTISS/FalconStationery/POS/scratch/import_data.csv';
$parsed = ProductImportEngine::parseCsvFile($csvFile);

if (!$parsed['success']) {
    die("Error parsing CSV: " . $parsed['message']);
}

echo "Found " . count($parsed['rows']) . " rows in CSV.\n";

// Sanitize rows - clean any tabs or unusual characters
$cleanedRows = [];
foreach ($parsed['rows'] as $r) {
    $cleaned = array_map(function($val) {
        $v = trim((string)$val);
        // Replace interior tabs with spaces
        $v = preg_replace('/\t+/', ' ', $v);
        return $v;
    }, $r);
    $cleanedRows[] = $cleaned;
}
$parsed['rows'] = $cleanedRows;

$mapping = ProductImportEngine::autoDetectMapping($parsed['headers']);
echo "Detected mapping:\n";
print_r($mapping);

$options = [
    'import_mode'         => 'create_and_update',
    'auto_create_masters' => true,
    'branch_id'           => 1,
    'user_id'             => 1,
    'ignore_blank_cells'  => true
];

$res = ProductImportEngine::commitImport($parsed, $mapping, 'create_and_update', $options, 1);
echo "Commit Result:\n";
print_r($res);
