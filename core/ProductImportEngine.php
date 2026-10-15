<?php
/**
 * Curtiss POS - Scalable Product Import & Master File Engine (Section 1)
 * High-performance batch processor for 10,000+ product catalogs:
 *  - CSV / TSV file parsing with UTF-8 BOM removal & leading-zero SKU string preservation
 *  - Preview Validation (Zero DB mutations during preview)
 *  - 3 Import Modes: 'create_and_update', 'update_existing_only', 'create_new_only'
 *  - Field Mapping Interface with standard Curtiss template auto-matching
 *  - Blank Cell Protection ('ignore_blank_cells' default = true)
 *  - Stock Safety Rule: Opening Stock ONLY initializes NEW products; existing live stock is NEVER overwritten
 *  - Auto Master Creation: Optional auto-creation of missing Brand/Category/Supplier
 *  - Import History Batch Audit (`product_import_batches`)
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Audit.php';
require_once __DIR__ . '/Product.php';
require_once __DIR__ . '/Brand.php';
require_once __DIR__ . '/Category.php';
require_once __DIR__ . '/Supplier.php';

class ProductImportEngine
{
    /**
     * Standard Curtiss Product Import CSV Header Template
     */
    public static function getStandardTemplateHeaders(): array
    {
        return [
            'SKU',
            'Barcode',
            'Brand',
            'Category',
            'Product Name',
            'Supplier',
            'Cost',
            'Selling Price',
            'Opening Stock',
            'Low Stock Threshold',
            'Status'
        ];
    }

    /**
     * Generate downloadable CSV template
     */
    public static function generateCsvTemplate(): string
    {
        $headers = self::getStandardTemplateHeaders();
        $sampleRows = [
            ['SKU001', '893450001001', 'Atlas', 'Stationery', 'Atlas Ball Pen Blue 0.5mm', 'Atlas Suppliers', '20.00', '30.00', '100.00', '10.00', 'active'],
            ['SKU002', '893450001002', 'Maped', 'Stationery', 'Maped Scissors 17cm', 'Maped Sri Lanka', '120.00', '180.00', '50.00', '5.00', 'active']
        ];

        $output = implode(',', $headers) . "\r\n";
        foreach ($sampleRows as $row) {
            $escaped = array_map(function($val) {
                return '"' . str_replace('"', '""', $val) . '"';
            }, $row);
            $output .= implode(',', $escaped) . "\r\n";
        }
        return $output;
    }

    /**
     * Parse CSV file into raw array of rows
     */
    public static function parseCsvFile(string $filePath): array
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return ['success' => false, 'message' => 'File not found or unreadable.'];
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return ['success' => false, 'message' => 'Failed to read file content.'];
        }

        // Remove UTF-8 BOM if present
        $bom = pack('H*', 'EFBBBF');
        $content = preg_replace("/^$bom/", '', $content);

        // Standardize line endings
        $lines = preg_split('/\r\n|\r|\n/', $content);
        $rows = [];

        foreach ($lines as $line) {
            if (trim($line) === '') continue;
            // Parse CSV row
            $rows[] = str_getcsv($line);
        }

        if (empty($rows)) {
            return ['success' => false, 'message' => 'Import file is empty.'];
        }

        $headers = array_map(function($h) {
            return trim((string)$h);
        }, array_shift($rows));

        return [
            'success' => true,
            'headers' => $headers,
            'rows'    => $rows
        ];
    }

    /**
     * Auto-detect column index mapping from headers
     */
    public static function autoDetectMapping(array $headers): array
    {
        $mapping = [
            'sku'                 => -1,
            'barcode'             => -1,
            'brand'               => -1,
            'category'            => -1,
            'name'                => -1,
            'supplier'            => -1,
            'cost'                => -1,
            'selling_price'       => -1,
            'opening_stock'       => -1,
            'low_stock_threshold' => -1,
            'status'              => -1
        ];

        foreach ($headers as $idx => $h) {
            $clean = strtolower(trim($h));
            if ($clean === 'sku' || $clean === 'item_code' || $clean === 'code') {
                $mapping['sku'] = $idx;
            } elseif ($clean === 'barcode' || $clean === 'ean' || $clean === 'upc') {
                $mapping['barcode'] = $idx;
            } elseif ($clean === 'brand' || $clean === 'brand_name') {
                $mapping['brand'] = $idx;
            } elseif ($clean === 'category' || $clean === 'category_name') {
                $mapping['category'] = $idx;
            } elseif ($clean === 'product name' || $clean === 'name' || $clean === 'product_name' || $clean === 'item_name') {
                $mapping['name'] = $idx;
            } elseif ($clean === 'supplier' || $clean === 'supplier_name') {
                $mapping['supplier'] = $idx;
            } elseif ($clean === 'cost' || $clean === 'unit_cost' || $clean === 'purchase_price') {
                $mapping['cost'] = $idx;
            } elseif ($clean === 'selling price' || $clean === 'selling_price' || $clean === 'price' || $clean === 'retail_price') {
                $mapping['selling_price'] = $idx;
            } elseif ($clean === 'opening stock' || $clean === 'opening_stock' || $clean === 'initial_stock' || $clean === 'qty') {
                $mapping['opening_stock'] = $idx;
            } elseif ($clean === 'low stock threshold' || $clean === 'low_stock_threshold' || $clean === 'reorder_level') {
                $mapping['low_stock_threshold'] = $idx;
            } elseif ($clean === 'status') {
                $mapping['status'] = $idx;
            }
        }

        // Fallback: If SKU not mapped, try barcode index or first column
        if ($mapping['sku'] === -1 && $mapping['barcode'] !== -1) {
            $mapping['sku'] = $mapping['barcode'];
        }

        return $mapping;
    }

    /**
     * Preview and Validate Import File without mutating Database
     */
    public static function preview(array $parsedData, array $mapping, string $importMode = 'create_and_update', array $options = []): array
    {
        $headers = $parsedData['headers'] ?? [];
        $rawRows = $parsedData['rows'] ?? [];

        $ignoreBlank = $options['ignore_blank_cells'] ?? true;
        $autoCreateMasters = $options['auto_create_masters'] ?? true;

        $db = Database::getInstance()->getConnection();

        // Pre-fetch existing products by SKU and Barcode
        $existingSkuMap = [];
        $stmtSkus = $db->query("SELECT id, sku, barcode, name, cost, selling_price, brand_id, category_id, supplier_id, status FROM products WHERE deleted_at IS NULL");
        while ($r = $stmtSkus->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($r['sku'])) {
                $existingSkuMap[strtolower(trim($r['sku']))] = $r;
            }
            if (!empty($r['barcode'])) {
                $existingSkuMap[strtolower(trim($r['barcode']))] = $r;
            }
        }

        // Pre-fetch Master Lookups (Brand, Category, Supplier)
        $brandMap = [];
        foreach (Brand::all(true) as $b) {
            $brandMap[strtolower(trim($b['name']))] = (int)$b['id'];
        }

        $categoryMap = [];
        foreach (Category::all(true) as $c) {
            $categoryMap[strtolower(trim($c['name']))] = (int)$c['id'];
        }

        $supplierMap = [];
        foreach (Supplier::all(true) as $s) {
            $supplierMap[strtolower(trim($s['name']))] = (int)$s['id'];
        }

        $seenSkusInFile = [];
        $seenBarcodesInFile = [];

        $totalRows = count($rawRows);
        $newCount = 0;
        $updateCount = 0;
        $skippedCount = 0;
        $invalidCount = 0;

        $items = [];
        $errors = [];

        foreach ($rawRows as $rowIdx => $row) {
            $lineNo = $rowIdx + 2; // +1 for 0-index, +1 for header row

            // Extract mapped fields as string text (preserve leading zeros for SKU & Barcode)
            $rawSku     = isset($mapping['sku']) && isset($row[$mapping['sku']]) ? trim((string)$row[$mapping['sku']]) : '';
            $rawBarcode = isset($mapping['barcode']) && isset($row[$mapping['barcode']]) ? trim((string)$row[$mapping['barcode']]) : '';
            
            if ($rawSku === '' && $rawBarcode !== '') {
                $rawSku = $rawBarcode;
            }
            if ($rawBarcode === '' && $rawSku !== '') {
                $rawBarcode = $rawSku;
            }

            $rawBrand      = isset($mapping['brand']) && isset($row[$mapping['brand']]) ? trim((string)$row[$mapping['brand']]) : '';
            $rawCategory   = isset($mapping['category']) && isset($row[$mapping['category']]) ? trim((string)$row[$mapping['category']]) : '';
            $rawName       = isset($mapping['name']) && isset($row[$mapping['name']]) ? trim((string)$row[$mapping['name']]) : '';
            $rawSupplier   = isset($mapping['supplier']) && isset($row[$mapping['supplier']]) ? trim((string)$row[$mapping['supplier']]) : '';
            $rawCost       = isset($mapping['cost']) && isset($row[$mapping['cost']]) ? trim((string)$row[$mapping['cost']]) : '';
            $rawSelling    = isset($mapping['selling_price']) && isset($row[$mapping['selling_price']]) ? trim((string)$row[$mapping['selling_price']]) : '';
            $rawStock      = isset($mapping['opening_stock']) && isset($row[$mapping['opening_stock']]) ? trim((string)$row[$mapping['opening_stock']]) : '';
            $rawThreshold  = isset($mapping['low_stock_threshold']) && isset($row[$mapping['low_stock_threshold']]) ? trim((string)$row[$mapping['low_stock_threshold']]) : '';
            $rawStatus     = isset($mapping['status']) && isset($row[$mapping['status']]) ? strtolower(trim((string)$row[$mapping['status']])) : 'active';

            $rowErrors = [];

            // Validation 1: SKU Required
            if ($rawSku === '') {
                $rowErrors[] = "Row #{$lineNo}: Missing required SKU/Product Code.";
            } else {
                $lowerSku = strtolower($rawSku);
                if (isset($seenSkusInFile[$lowerSku])) {
                    $rowErrors[] = "Row #{$lineNo}: Duplicate SKU '{$rawSku}' found inside imported file.";
                }
                $seenSkusInFile[$lowerSku] = true;
            }

            // Validation 2: Barcode Required
            if ($rawBarcode === '') {
                $rowErrors[] = "Row #{$lineNo}: Missing required Barcode.";
            }

            // Validation 3: Product Name Required
            if ($rawName === '' && !isset($existingSkuMap[strtolower($rawSku)])) {
                $rowErrors[] = "Row #{$lineNo}: Missing required Product Name for new SKU.";
            }

            // Validation 4: Price & Cost Numeric
            if ($rawCost !== '' && (!is_numeric($rawCost) || (float)$rawCost < 0)) {
                $rowErrors[] = "Row #{$lineNo}: Invalid Cost value '{$rawCost}'. Must be non-negative number.";
            }
            if ($rawSelling !== '' && (!is_numeric($rawSelling) || (float)$rawSelling < 0)) {
                $rowErrors[] = "Row #{$lineNo}: Invalid Selling Price value '{$rawSelling}'. Must be non-negative number.";
            }
            if ($rawStock !== '' && (!is_numeric($rawStock) || (float)$rawStock < 0)) {
                $rowErrors[] = "Row #{$lineNo}: Invalid Opening Stock value '{$rawStock}'. Must be non-negative number.";
            }

            // Master lookups validation
            if (!empty($rawBrand) && !isset($brandMap[strtolower($rawBrand)]) && !$autoCreateMasters) {
                $rowErrors[] = "Row #{$lineNo}: Unknown Brand '{$rawBrand}'. Enable auto-create or create brand first.";
            }
            if (!empty($rawCategory) && !isset($categoryMap[strtolower($rawCategory)]) && !$autoCreateMasters) {
                $rowErrors[] = "Row #{$lineNo}: Unknown Category '{$rawCategory}'. Enable auto-create or create category first.";
            }
            if (!empty($rawSupplier) && !isset($supplierMap[strtolower($rawSupplier)]) && !$autoCreateMasters) {
                $rowErrors[] = "Row #{$lineNo}: Unknown Supplier '{$rawSupplier}'. Enable auto-create or create supplier first.";
            }

            if (!empty($rowErrors)) {
                $invalidCount++;
                $errors[] = [
                    'line'    => $lineNo,
                    'sku'     => $rawSku ?: 'N/A',
                    'name'    => $rawName ?: 'N/A',
                    'message' => implode(' | ', $rowErrors)
                ];
                $items[] = [
                    'line'         => $lineNo,
                    'sku'          => $rawSku,
                    'barcode'      => $rawBarcode,
                    'name'         => $rawName,
                    'action'       => 'invalid',
                    'error'        => implode(' | ', $rowErrors)
                ];
                continue;
            }

            // Determine if row is NEW or UPDATE
            $lowerSku = strtolower($rawSku);
            $existingProduct = $existingSkuMap[$lowerSku] ?? null;

            if ($existingProduct) {
                if ($importMode === 'create_new_only') {
                    $skippedCount++;
                    $items[] = [
                        'line' => $lineNo, 'sku' => $rawSku, 'name' => $rawName, 'action' => 'skipped', 'note' => 'Existing SKU skipped in Create New Only mode'
                    ];
                } else {
                    $updateCount++;
                    $items[] = [
                        'line' => $lineNo, 'sku' => $rawSku, 'barcode' => $rawBarcode, 'name' => $rawName ?: $existingProduct['name'],
                        'action' => 'update', 'product_id' => $existingProduct['id'],
                        'stock_note' => 'Existing product master updated; live inventory stock preserved'
                    ];
                }
            } else {
                if ($importMode === 'update_existing_only') {
                    $skippedCount++;
                    $items[] = [
                        'line' => $lineNo, 'sku' => $rawSku, 'name' => $rawName, 'action' => 'skipped', 'note' => 'Unknown SKU skipped in Update Existing Only mode'
                    ];
                } else {
                    $newCount++;
                    $items[] = [
                        'line' => $lineNo, 'sku' => $rawSku, 'barcode' => $rawBarcode, 'name' => $rawName,
                        'action' => 'create',
                        'stock_note' => $rawStock !== '' ? "Initial opening stock set to {$rawStock}" : "Default opening stock = 0"
                    ];
                }
            }
        }

        return [
            'success'       => true,
            'import_mode'   => $importMode,
            'summary'       => [
                'total_rows'  => $totalRows,
                'new_count'   => $newCount,
                'update_count'=> $updateCount,
                'skipped_count'=> $skippedCount,
                'invalid_count'=> $invalidCount
            ],
            'items'         => array_slice($items, 0, 100), // Sample items for preview UI
            'errors'        => $errors
        ];
    }

    /**
     * Execute Final Batch Import with Full Transaction & Stock Safety
     */
    public static function commitImport(array $parsedData, array $mapping, string $importMode = 'create_and_update', array $options = [], int $userId = 1): array
    {
        $fileName = $options['file_name'] ?? 'product_import.csv';
        $ignoreBlank = $options['ignore_blank_cells'] ?? true;
        $autoCreateMasters = $options['auto_create_masters'] ?? true;
        $branchId = (int)($options['branch_id'] ?? ($_SESSION['active_branch_id'] ?? 1));

        $rawRows = $parsedData['rows'] ?? [];

        $db = Database::getInstance()->getConnection();
        $db->beginTransaction();

        try {
            // Generate Import Batch Number
            $batchNo = 'IMP-' . date('YMD-His') . '-' . rand(100, 999);
            $batchNo = strtoupper(str_replace(' ', '', $batchNo));

            // Load Lookups
            $brandMap = [];
            foreach (Brand::all(true) as $b) {
                $brandMap[strtolower(trim($b['name']))] = (int)$b['id'];
            }

            $categoryMap = [];
            foreach (Category::all(true) as $c) {
                $categoryMap[strtolower(trim($c['name']))] = (int)$c['id'];
            }

            $supplierMap = [];
            foreach (Supplier::all(true) as $s) {
                $supplierMap[strtolower(trim($s['name']))] = (int)$s['id'];
            }

            $createdCount = 0;
            $updatedCount = 0;
            $skippedCount = 0;
            $failedCount = 0;

            foreach ($rawRows as $rowIdx => $row) {
                $rawSku     = isset($mapping['sku']) && isset($row[$mapping['sku']]) ? trim((string)$row[$mapping['sku']]) : '';
                $rawBarcode = isset($mapping['barcode']) && isset($row[$mapping['barcode']]) ? trim((string)$row[$mapping['barcode']]) : '';
                
                if ($rawSku === '' && $rawBarcode !== '') {
                    $rawSku = $rawBarcode;
                }
                if ($rawBarcode === '' && $rawSku !== '') {
                    $rawBarcode = $rawSku;
                }

                if ($rawSku === '') {
                    $failedCount++;
                    continue;
                }

                $rawBrand      = isset($mapping['brand']) && isset($row[$mapping['brand']]) ? trim((string)$row[$mapping['brand']]) : '';
                $rawCategory   = isset($mapping['category']) && isset($row[$mapping['category']]) ? trim((string)$row[$mapping['category']]) : '';
                $rawName       = isset($mapping['name']) && isset($row[$mapping['name']]) ? trim((string)$row[$mapping['name']]) : '';
                $rawSupplier   = isset($mapping['supplier']) && isset($row[$mapping['supplier']]) ? trim((string)$row[$mapping['supplier']]) : '';
                $rawCost       = isset($mapping['cost']) && isset($row[$mapping['cost']]) ? trim((string)$row[$mapping['cost']]) : '';
                $rawSelling    = isset($mapping['selling_price']) && isset($row[$mapping['selling_price']]) ? trim((string)$row[$mapping['selling_price']]) : '';
                $rawStock      = isset($mapping['opening_stock']) && isset($row[$mapping['opening_stock']]) ? trim((string)$row[$mapping['opening_stock']]) : '';
                $rawThreshold  = isset($mapping['low_stock_threshold']) && isset($row[$mapping['low_stock_threshold']]) ? trim((string)$row[$mapping['low_stock_threshold']]) : '';
                $rawStatus     = isset($mapping['status']) && isset($row[$mapping['status']]) ? strtolower(trim((string)$row[$mapping['status']])) : 'active';

                // Resolve Brand ID
                $brandId = 0;
                if (!empty($rawBrand)) {
                    $lowerB = strtolower($rawBrand);
                    if (isset($brandMap[$lowerB])) {
                        $brandId = $brandMap[$lowerB];
                    } elseif ($autoCreateMasters) {
                        $bRes = Brand::create($rawBrand, 'active');
                        if ($bRes['success']) {
                            $brandId = (int)$bRes['id'];
                            $brandMap[$lowerB] = $brandId;
                        }
                    }
                }
                if ($brandId <= 0) {
                    // Fallback to first brand
                    $firstB = Brand::all(true);
                    $brandId = !empty($firstB) ? (int)$firstB[0]['id'] : 1;
                }

                // Resolve Category ID
                $categoryId = 0;
                if (!empty($rawCategory)) {
                    $lowerC = strtolower($rawCategory);
                    if (isset($categoryMap[$lowerC])) {
                        $categoryId = $categoryMap[$lowerC];
                    } elseif ($autoCreateMasters) {
                        $cRes = Category::create($rawCategory, 'active');
                        if ($cRes['success']) {
                            $categoryId = (int)$cRes['id'];
                            $categoryMap[$lowerC] = $categoryId;
                        }
                    }
                }
                if ($categoryId <= 0) {
                    $firstC = Category::all(true);
                    $categoryId = !empty($firstC) ? (int)$firstC[0]['id'] : 1;
                }

                // Resolve Supplier ID
                $supplierId = 0;
                if (!empty($rawSupplier)) {
                    $lowerS = strtolower($rawSupplier);
                    if (isset($supplierMap[$lowerS])) {
                        $supplierId = $supplierMap[$lowerS];
                    } elseif ($autoCreateMasters) {
                        $sRes = Supplier::create($rawSupplier, null, null, null, 'active');
                        if ($sRes['success']) {
                            $supplierId = (int)$sRes['id'];
                            $supplierMap[$lowerS] = $supplierId;
                        }
                    }
                }
                if ($supplierId <= 0) {
                    $firstS = Supplier::all(true);
                    $supplierId = !empty($firstS) ? (int)$firstS[0]['id'] : 1;
                }

                // Find existing product by SKU or Barcode
                $existingProduct = Product::findBySku($rawSku);
                if (!$existingProduct) {
                    $existingProduct = Product::findByBarcode($rawBarcode);
                }

                if ($existingProduct) {
                    // UPDATE Existing Product
                    if ($importMode === 'create_new_only') {
                        $skippedCount++;
                        continue;
                    }

                    $upName       = ($ignoreBlank && $rawName === '') ? $existingProduct['name'] : $rawName;
                    $upBarcode    = ($ignoreBlank && $rawBarcode === '') ? $existingProduct['barcode'] : $rawBarcode;
                    $upCost       = ($ignoreBlank && $rawCost === '') ? (float)$existingProduct['cost'] : (float)$rawCost;
                    $upSelling    = ($ignoreBlank && $rawSelling === '') ? (float)$existingProduct['selling_price'] : (float)$rawSelling;
                    $upThreshold  = ($ignoreBlank && $rawThreshold === '') ? (float)$existingProduct['low_stock_threshold'] : (float)$rawThreshold;
                    $upBrandId    = ($ignoreBlank && empty($rawBrand)) ? (int)$existingProduct['brand_id'] : $brandId;
                    $upCategoryId = ($ignoreBlank && empty($rawCategory)) ? (int)$existingProduct['category_id'] : $categoryId;
                    $upSupplierId = ($ignoreBlank && empty($rawSupplier)) ? (int)$existingProduct['supplier_id'] : $supplierId;
                    $upStatus     = ($ignoreBlank && empty($rawStatus)) ? $existingProduct['status'] : ($rawStatus === 'inactive' ? 'inactive' : 'active');

                    // STOCK SAFETY RULE: Opening Stock is IGNORED during update to protect live inventory!
                    $upData = [
                        'sku'                 => $rawSku,
                        'barcode'             => $upBarcode,
                        'brand_id'            => $upBrandId,
                        'category_id'         => $upCategoryId,
                        'name'                => $upName,
                        'supplier_id'         => $upSupplierId,
                        'cost'                => $upCost,
                        'selling_price'       => $upSelling,
                        'opening_stock'       => (float)$existingProduct['opening_stock'], // Preserve stored opening stock field
                        'low_stock_threshold' => $upThreshold,
                        'status'              => $upStatus
                    ];

                    $resUp = Product::update((int)$existingProduct['id'], $upData);
                    if ($resUp['success']) {
                        $updatedCount++;
                    } else {
                        $failedCount++;
                    }
                } else {
                    // CREATE New Product
                    if ($importMode === 'update_existing_only') {
                        $skippedCount++;
                        continue;
                    }

                    $newCost    = is_numeric($rawCost) ? (float)$rawCost : 0.00;
                    $newSelling = is_numeric($rawSelling) ? (float)$rawSelling : 0.00;
                    $newStock   = is_numeric($rawStock) ? (float)$rawStock : 0.00;
                    $newThresh  = is_numeric($rawThreshold) ? (float)$rawThreshold : 10.00;

                    $newData = [
                        'sku'                 => $rawSku,
                        'barcode'             => $rawBarcode,
                        'brand_id'            => $brandId,
                        'category_id'         => $categoryId,
                        'name'                => $rawName ?: ('Product ' . $rawSku),
                        'supplier_id'         => $supplierId,
                        'cost'                => $newCost,
                        'selling_price'       => $newSelling,
                        'opening_stock'       => $newStock, // Stock safety: Only NEW product initializes opening stock
                        'low_stock_threshold' => $newThresh,
                        'status'              => $rawStatus === 'inactive' ? 'inactive' : 'active',
                        'branch_id'           => $branchId
                    ];

                    $resCreate = Product::create($newData);
                    if ($resCreate['success']) {
                        $createdCount++;
                    } else {
                        $failedCount++;
                    }
                }
            }

            // Insert Batch History Record
            $totalRows = count($rawRows);
            $stmtBatch = $db->prepare("
                INSERT INTO product_import_batches (batch_no, file_name, user_id, import_date, import_mode, total_rows, created_count, updated_count, skipped_count, failed_count, notes)
                VALUES (:batch_no, :file_name, :user_id, NOW(), :import_mode, :total_rows, :created_count, :updated_count, :skipped_count, :failed_count, :notes)
            ");
            $stmtBatch->execute([
                ':batch_no'      => $batchNo,
                ':file_name'     => $fileName,
                ':user_id'       => $userId,
                ':import_mode'   => $importMode,
                ':total_rows'    => $totalRows,
                ':created_count' => $createdCount,
                ':updated_count' => $updatedCount,
                ':skipped_count' => $skippedCount,
                ':failed_count'  => $failedCount,
                ':notes'         => "Import executed in $importMode mode."
            ]);

            $db->commit();

            Audit::log('PRODUCT_IMPORT_COMMITTED', 'products', null, null, [
                'batch_no' => $batchNo, 'mode' => $importMode, 'created' => $createdCount, 'updated' => $updatedCount, 'skipped' => $skippedCount, 'failed' => $failedCount
            ]);

            return [
                'success'       => true,
                'batch_no'      => $batchNo,
                'summary'       => [
                    'total_rows'   => $totalRows,
                    'created_count' => $createdCount,
                    'updated_count' => $updatedCount,
                    'skipped_count' => $skippedCount,
                    'failed_count'  => $failedCount
                ],
                'message'       => "Product master import completed successfully! Created: {$createdCount}, Updated: {$updatedCount}, Skipped: {$skippedCount}, Failed: {$failedCount}."
            ];

        } catch (Throwable $e) {
            $db->rollBack();
            Logger::error("Failed to commit product import batch: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Database error during import execution: ' . $e->getMessage()];
        }
    }

    /**
     * Get Import Batches History
     */
    public static function getImportHistory(int $limit = 20): array
    {
        try {
            return Database::fetchAll("
                SELECT b.*, u.full_name AS user_name
                FROM product_import_batches b
                JOIN users u ON b.user_id = u.id
                ORDER BY b.id DESC
                LIMIT $limit
            ");
        } catch (Throwable $e) {
            Logger::error("Failed to fetch import history: " . $e->getMessage(), $e);
            return [];
        }
    }
}
