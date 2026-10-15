<?php
/**
 * Curtiss POS - Product Master Import Controller (Section 1)
 * High-performance controller for uploading, previewing, validating, and committing 10,000+ product catalogs.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/ProductImportEngine.php';
require_once __DIR__ . '/core/Brand.php';
require_once __DIR__ . '/core/Category.php';
require_once __DIR__ . '/core/Supplier.php';

Auth::requireAuth();
Auth::requirePermission('products.import');

$importHistory = ProductImportEngine::getImportHistory(20);

View::render('products/import', [
    'importHistory' => $importHistory
], 'Import Products - Curtiss POS');
