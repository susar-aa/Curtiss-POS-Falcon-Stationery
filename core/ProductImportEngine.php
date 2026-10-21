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
            ['FAL-PEN-001', '893450001001', 'Atlas', 'Stationery', 'Atlas Ball Pen Blue 0.5mm', 'Atlas Suppliers', '20.00', '30.00', '100.00', '10.00', 'active'],
            ['FAL-SCI-002', '893450001002', 'Maped', 'Stationery', 'Maped Scissors 17cm', 'Maped Sri Lanka', '120.00', '180.00', '50.00', '5.00', 'active']
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
            if ($clean === 'sku' || $clean === 'item_code' || $clean === 'product_code') {
                $mapping['sku'] = $idx;
            } elseif ($clean === 'barcode' || $clean === 'ean' || $clean === 'upc' || $clean === 'code') {
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

        // If barcode was not mapped by header label, check if SKU column can serve as barcode
        if ($mapping['barcode'] === -1 && $mapping['sku'] !== -1) {
            $mapping['barcode'] = $mapping['sku'];
        } elseif ($mapping['barcode'] === -1 && count($headers) > 0) {
            $mapping['barcode'] = 0;
        }

        // If SKU was not mapped by header label, default to barcode column
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

        // Pre-fetch existing products by Barcode as unique identifier
        $existingBarcodeMap = [];
        $stmtBarcodes = $db->query("SELECT id, barcode, sku, name, cost, selling_price, brand_id, category_id, supplier_id, status FROM products WHERE deleted_at IS NULL");
        while ($r = $stmtBarcodes->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($r['barcode'])) {
                $existingBarcodeMap[strtolower(trim($r['barcode']))] = $r;
            }
        }

        // Pre-fetch Master Lookups (Brand, Category, Supplier)
        $masterBrandNames = [];
        $brandMap = [];
        foreach (Brand::all(true) as $b) {
            $masterBrandNames[] = $b['name'];
            $brandMap[strtolower(trim($b['name']))] = (int)$b['id'];
        }

        $masterCategoryNames = [];
        $categoryMap = [];
        foreach (Category::all(true) as $c) {
            $masterCategoryNames[] = $c['name'];
            $categoryMap[strtolower(trim($c['name']))] = (int)$c['id'];
        }

        $masterSupplierNames = [];
        $supplierMap = [];
        foreach (Supplier::all(true) as $s) {
            $masterSupplierNames[] = $s['name'];
            $supplierMap[strtolower(trim($s['name']))] = (int)$s['id'];
        }

        $totalRows = count($rawRows);
        $newCount = 0;
        $updateCount = 0;
        $lotCount = 0;
        $skippedCount = 0;

        $validItems = [];
        $issueItems = [];

        // Track barcodes seen in this CSV file to distinguish the primary product row from subsequent price lots
        $seenCsvBarcodes = [];

        foreach ($rawRows as $rowIdx => $row) {
            $lineNo = $rowIdx + 2; // +1 for 0-index, +1 for header row

            // Extract mapped fields as string text (preserve leading zeros for Barcode and SKU)
            $rawBarcode = isset($mapping['barcode']) && isset($row[$mapping['barcode']]) ? trim((string)$row[$mapping['barcode']]) : '';
            $rawSku     = isset($mapping['sku']) && isset($row[$mapping['sku']]) ? trim((string)$row[$mapping['sku']]) : '';
            if ($rawSku === '') {
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

            $rowIssues = [];

            // 1. Barcode presence check
            if ($rawBarcode === '') {
                $rowIssues[] = [
                    'field'   => 'barcode',
                    'type'    => 'empty',
                    'message' => "Missing required Barcode."
                ];
                $issueItems[] = [
                    'row_idx'             => $rowIdx,
                    'line'                => $lineNo,
                    'barcode'             => '',
                    'sku'                 => '',
                    'brand'               => $rawBrand,
                    'category'            => $rawCategory,
                    'name'                => $rawName,
                    'supplier'            => $rawSupplier,
                    'cost'                => $rawCost,
                    'selling_price'       => $rawSelling,
                    'opening_stock'       => $rawStock,
                    'low_stock_threshold' => $rawThreshold,
                    'status'              => $rawStatus,
                    'issues'              => $rowIssues,
                    'error_message'       => "Missing required Barcode.",
                    'action'              => 'issue'
                ];
                continue;
            }

            $barcodeLower = strtolower($rawBarcode);
            $isLotOccurrence = isset($seenCsvBarcodes[$barcodeLower]);
            $existingProduct = $existingBarcodeMap[$barcodeLower] ?? null;

            // ── CASE A: SUBSEQUENT OCCURRENCE IN CSV = PRICE LOT ──
            if ($isLotOccurrence) {
                // Numeric validations
                if ($rawCost !== '' && (!is_numeric($rawCost) || (float)$rawCost < 0)) {
                    $rowIssues[] = [
                        'field'   => 'cost',
                        'type'    => 'invalid_number',
                        'message' => "Invalid Cost '{$rawCost}'. Must be a non-negative number."
                    ];
                }
                if ($rawSelling !== '' && (!is_numeric($rawSelling) || (float)$rawSelling < 0)) {
                    $rowIssues[] = [
                        'field'   => 'selling_price',
                        'type'    => 'invalid_number',
                        'message' => "Invalid Selling Price '{$rawSelling}'. Must be a non-negative number."
                    ];
                }
                if ($rawStock !== '' && (!is_numeric($rawStock) || (float)$rawStock < 0)) {
                    $rowIssues[] = [
                        'field'   => 'opening_stock',
                        'type'    => 'invalid_number',
                        'message' => "Invalid Opening Stock '{$rawStock}'. Must be a non-negative number."
                    ];
                }
                if (is_numeric($rawCost) && is_numeric($rawSelling) && (float)$rawCost > 0 && (float)$rawSelling > 0 && (float)$rawSelling < (float)$rawCost) {
                    $rowIssues[] = [
                        'field'   => 'selling_price',
                        'type'    => 'warning',
                        'message' => "Selling Price (LKR " . number_format((float)$rawSelling, 2) . ") is lower than Cost (LKR " . number_format((float)$rawCost, 2) . "). Negative profit margin."
                    ];
                }

                if (!empty($rowIssues)) {
                    $issueItems[] = [
                        'row_idx'             => $rowIdx,
                        'line'                => $lineNo,
                        'barcode'             => $rawBarcode,
                        'sku'                 => $rawBarcode,
                        'brand'               => $rawBrand ?: ($seenCsvBarcodes[$barcodeLower]['brand'] ?? ($existingProduct['brand_name'] ?? '')),
                        'category'            => $rawCategory ?: ($seenCsvBarcodes[$barcodeLower]['category'] ?? ($existingProduct['category_name'] ?? '')),
                        'name'                => $rawName ?: ($seenCsvBarcodes[$barcodeLower]['name'] ?? ($existingProduct['name'] ?? 'Price Lot')),
                        'supplier'            => $rawSupplier ?: ($seenCsvBarcodes[$barcodeLower]['supplier'] ?? ($existingProduct['supplier_name'] ?? '')),
                        'cost'                => $rawCost,
                        'selling_price'       => $rawSelling,
                        'opening_stock'       => $rawStock,
                        'low_stock_threshold' => $rawThreshold,
                        'status'              => $rawStatus,
                        'issues'              => $rowIssues,
                        'error_message'       => implode(' | ', array_column($rowIssues, 'message')),
                        'action'              => 'issue'
                    ];
                } else {
                    $costFmt = is_numeric($rawCost) ? number_format((float)$rawCost, 2) : '-';
                    $priceFmt = is_numeric($rawSelling) ? number_format((float)$rawSelling, 2) : '-';
                    $stockFmt = is_numeric($rawStock) ? number_format((float)$rawStock, 2) : '0.00';
                    $lotCount++;

                    $validItems[] = [
                        'row_idx'             => $rowIdx,
                        'line'                => $lineNo,
                        'barcode'             => $rawBarcode,
                        'sku'                 => $rawBarcode,
                        'brand'               => $rawBrand ?: ($seenCsvBarcodes[$barcodeLower]['brand'] ?? ($existingProduct['brand_name'] ?? '-')),
                        'category'            => $rawCategory ?: ($seenCsvBarcodes[$barcodeLower]['category'] ?? ($existingProduct['category_name'] ?? '-')),
                        'name'                => $rawName ?: ($seenCsvBarcodes[$barcodeLower]['name'] ?? ($existingProduct['name'] ?? 'Price Lot')),
                        'supplier'            => $rawSupplier ?: ($seenCsvBarcodes[$barcodeLower]['supplier'] ?? ($existingProduct['supplier_name'] ?? '-')),
                        'cost'                => $rawCost,
                        'selling_price'       => $rawSelling,
                        'opening_stock'       => $rawStock,
                        'low_stock_threshold' => $rawThreshold,
                        'status'              => $rawStatus,
                        'action'              => 'price_lot',
                        'product_id'          => $existingProduct['id'] ?? null,
                        'stock_note'          => "Price Lot: Cost LKR {$costFmt}, Price LKR {$priceFmt}, Stock {$stockFmt}"
                    ];
                }
                continue;
            }

            // Record first occurrence
            $seenCsvBarcodes[$barcodeLower] = [
                'line'     => $lineNo,
                'name'     => $rawName,
                'brand'    => $rawBrand,
                'category' => $rawCategory,
                'supplier' => $rawSupplier
            ];

            // ── CASE B: PRIMARY PRODUCT OCCURRENCE ──
            // Mode constraint: Create New Only
            if ($importMode === 'create_new_only' && $existingProduct) {
                $rowIssues[] = [
                    'field'   => 'barcode',
                    'type'    => 'exists',
                    'message' => "Product already exists in catalog (Barcode: {$existingProduct['barcode']}). Skipped under 'Create New Barcodes Only' mode."
                ];
            }

            // Product Name check
            if ($rawName === '' && !$existingProduct) {
                $rowIssues[] = [
                    'field'   => 'name',
                    'type'    => 'empty',
                    'message' => "Missing required Product Name for new product."
                ];
            }

            // Numeric validations
            if ($rawCost !== '' && (!is_numeric($rawCost) || (float)$rawCost < 0)) {
                $rowIssues[] = [
                    'field'   => 'cost',
                    'type'    => 'invalid_number',
                    'message' => "Invalid Cost '{$rawCost}'. Must be a non-negative number."
                ];
            }
            if ($rawSelling !== '' && (!is_numeric($rawSelling) || (float)$rawSelling < 0)) {
                $rowIssues[] = [
                    'field'   => 'selling_price',
                    'type'    => 'invalid_number',
                    'message' => "Invalid Selling Price '{$rawSelling}'. Must be a non-negative number."
                ];
            }
            if ($rawStock !== '' && (!is_numeric($rawStock) || (float)$rawStock < 0)) {
                $rowIssues[] = [
                    'field'   => 'opening_stock',
                    'type'    => 'invalid_number',
                    'message' => "Invalid Opening Stock '{$rawStock}'. Must be a non-negative number."
                ];
            }

            // Selling Price Lower than Cost Warning / Issue
            if (is_numeric($rawCost) && is_numeric($rawSelling) && (float)$rawCost > 0 && (float)$rawSelling > 0 && (float)$rawSelling < (float)$rawCost) {
                $rowIssues[] = [
                    'field'   => 'selling_price',
                    'type'    => 'warning',
                    'message' => "Selling Price (LKR " . number_format((float)$rawSelling, 2) . ") is lower than Cost (LKR " . number_format((float)$rawCost, 2) . "). Negative profit margin."
                ];
            }

            // Master lookups validation when auto_create_masters is disabled
            if (!empty($rawBrand) && !isset($brandMap[strtolower($rawBrand)]) && !$autoCreateMasters) {
                $rowIssues[] = [
                    'field'   => 'brand',
                    'type'    => 'unknown_master',
                    'message' => "Unknown Brand '{$rawBrand}'. Enable auto-create or select an existing brand."
                ];
            }
            if (!empty($rawCategory) && !isset($categoryMap[strtolower($rawCategory)]) && !$autoCreateMasters) {
                $rowIssues[] = [
                    'field'   => 'category',
                    'type'    => 'unknown_master',
                    'message' => "Unknown Category '{$rawCategory}'. Enable auto-create or select an existing category."
                ];
            }
            if (!empty($rawSupplier) && !isset($supplierMap[strtolower($rawSupplier)]) && !$autoCreateMasters) {
                $rowIssues[] = [
                    'field'   => 'supplier',
                    'type'    => 'unknown_master',
                    'message' => "Unknown Supplier '{$rawSupplier}'. Enable auto-create or select an existing supplier."
                ];
            }

            // Segregate: If any errors/issues exist, place in issueItems
            if (!empty($rowIssues)) {
                $issueItems[] = [
                    'row_idx'             => $rowIdx,
                    'line'                => $lineNo,
                    'barcode'             => $rawBarcode,
                    'sku'                 => $rawBarcode,
                    'brand'               => $rawBrand,
                    'category'            => $rawCategory,
                    'name'                => $rawName ?: ($existingProduct['name'] ?? ''),
                    'supplier'            => $rawSupplier,
                    'cost'                => $rawCost,
                    'selling_price'       => $rawSelling,
                    'opening_stock'       => $rawStock,
                    'low_stock_threshold' => $rawThreshold,
                    'status'              => $rawStatus,
                    'issues'              => $rowIssues,
                    'error_message'       => implode(' | ', array_column($rowIssues, 'message')),
                    'action'              => 'issue'
                ];
                continue;
            }

            // Otherwise, determine valid action: NEW, UPDATE, or SKIPPED
            if ($existingProduct) {
                if ($importMode === 'create_new_only') {
                    $skippedCount++;
                    $validItems[] = [
                        'row_idx'             => $rowIdx,
                        'line'                => $lineNo,
                        'barcode'             => $rawBarcode,
                        'sku'                 => $rawBarcode,
                        'brand'               => $rawBrand,
                        'category'            => $rawCategory,
                        'name'                => $rawName ?: $existingProduct['name'],
                        'supplier'            => $rawSupplier,
                        'cost'                => $rawCost,
                        'selling_price'       => $rawSelling,
                        'opening_stock'       => $rawStock,
                        'low_stock_threshold' => $rawThreshold,
                        'status'              => $rawStatus,
                        'action'              => 'skipped',
                        'product_id'          => $existingProduct['id'],
                        'stock_note'          => 'Existing Barcode skipped in Create New Only mode'
                    ];
                } else {
                    $updateCount++;
                    $validItems[] = [
                        'row_idx'             => $rowIdx,
                        'line'                => $lineNo,
                        'barcode'             => $rawBarcode,
                        'sku'                 => $rawBarcode,
                        'brand'               => $rawBrand,
                        'category'            => $rawCategory,
                        'name'                => $rawName ?: $existingProduct['name'],
                        'supplier'            => $rawSupplier,
                        'cost'                => $rawCost,
                        'selling_price'       => $rawSelling,
                        'opening_stock'       => $rawStock,
                        'low_stock_threshold' => $rawThreshold,
                        'status'              => $rawStatus,
                        'action'              => 'update',
                        'product_id'          => $existingProduct['id'],
                        'stock_note'          => 'Existing product master updated; live inventory stock preserved'
                    ];
                }
            } else {
                if ($importMode === 'update_existing_only') {
                    $skippedCount++;
                    $validItems[] = [
                        'row_idx'             => $rowIdx,
                        'line'                => $lineNo,
                        'barcode'             => $rawBarcode,
                        'sku'                 => $rawSku,
                        'brand'               => $rawBrand,
                        'category'            => $rawCategory,
                        'name'                => $rawName,
                        'supplier'            => $rawSupplier,
                        'cost'                => $rawCost,
                        'selling_price'       => $rawSelling,
                        'opening_stock'       => $rawStock,
                        'low_stock_threshold' => $rawThreshold,
                        'status'              => $rawStatus,
                        'action'              => 'skipped',
                        'stock_note'          => 'Unknown Barcode skipped in Update Existing Only mode'
                    ];
                } else {
                    $newCount++;
                    $validItems[] = [
                        'row_idx'             => $rowIdx,
                        'line'                => $lineNo,
                        'barcode'             => $rawBarcode,
                        'sku'                 => $rawSku,
                        'brand'               => $rawBrand,
                        'category'            => $rawCategory,
                        'name'                => $rawName,
                        'supplier'            => $rawSupplier,
                        'cost'                => $rawCost,
                        'selling_price'       => $rawSelling,
                        'opening_stock'       => $rawStock,
                        'low_stock_threshold' => $rawThreshold,
                        'status'              => $rawStatus,
                        'action'              => 'create',
                        'stock_note'          => $rawStock !== '' ? "Initial opening stock set to {$rawStock}" : "Default opening stock = 0"
                    ];
                }
            }
        }

        $allErrors = array_map(function($item) {
            return [
                'line'    => $item['line'],
                'barcode' => $item['barcode'] ?: 'N/A',
                'name'    => $item['name'] ?: 'N/A',
                'message' => $item['error_message']
            ];
        }, $issueItems);

        return [
            'success'           => true,
            'import_mode'       => $importMode,
            'summary'           => [
                'total_rows'    => $totalRows,
                'valid_count'   => count($validItems),
                'issue_count'   => count($issueItems),
                'new_count'     => $newCount,
                'update_count'  => $updateCount,
                'lot_count'     => $lotCount,
                'skipped_count' => $skippedCount,
                'invalid_count' => count($issueItems)
            ],
            'issue_items'       => $issueItems,
            'valid_items'       => $validItems,
            'items'             => array_slice(array_merge($validItems, $issueItems), 0, 100),
            'errors'            => $allErrors,
            'available_masters' => [
                'brands'     => $masterBrandNames,
                'categories' => $masterCategoryNames,
                'suppliers'  => $masterSupplierNames
            ]
        ];
    }

    /**
     * Execute Final Batch Import with Full Transaction, Stock Safety, and Row Fix Overrides
     */
    public static function commitImport(array $parsedData, array $mapping, string $importMode = 'create_and_update', array $options = [], int $userId = 1): array
    {
        $fileName = $options['file_name'] ?? 'product_import.csv';
        $ignoreBlank = $options['ignore_blank_cells'] ?? true;
        $autoCreateMasters = $options['auto_create_masters'] ?? true;
        $branchId = (int)($options['branch_id'] ?? ($_SESSION['active_branch_id'] ?? 1));

        $rowOverrides = $options['row_overrides'] ?? [];
        if (!is_array($rowOverrides)) $rowOverrides = [];

        $excludedIndices = $options['excluded_indices'] ?? [];
        if (!is_array($excludedIndices)) $excludedIndices = [];
        $excludedMap = array_flip(array_map('intval', $excludedIndices));

        $rawRows = $parsedData['rows'] ?? [];

        $db = Database::getInstance()->getConnection();
        $isNested = $db->inTransaction();
        if (!$isNested) {
            $db->beginTransaction();
        }

        try {
            // Generate Import Batch Number
            $batchNo = 'IMP-' . date('Ymd-His') . '-' . rand(100, 999);
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

            $createdCount     = 0;
            $updatedCount     = 0;
            $createdLotsCount = 0;
            $skippedCount     = 0;
            $failedCount      = 0;

            // Track barcodes encountered in this commit batch to route duplicate barcodes to price lots
            $seenBarcodesInBatch = [];

            foreach ($rawRows as $rowIdx => $row) {
                // Check if user excluded this row from import
                if (isset($excludedMap[$rowIdx])) {
                    $skippedCount++;
                    continue;
                }

                // Check if user edited/fixed this row
                if (isset($rowOverrides[$rowIdx]) && is_array($rowOverrides[$rowIdx])) {
                    $ov = $rowOverrides[$rowIdx];
                    $rawBarcode    = isset($ov['barcode']) ? trim((string)$ov['barcode']) : '';
                    $rawSku        = isset($ov['sku']) ? trim((string)$ov['sku']) : '';
                    if ($rawSku === '') $rawSku = $rawBarcode;
                    if ($rawBarcode === '' && $rawSku !== '') $rawBarcode = $rawSku;
                    $rawBrand      = isset($ov['brand']) ? trim((string)$ov['brand']) : '';
                    $rawCategory   = isset($ov['category']) ? trim((string)$ov['category']) : '';
                    $rawName       = isset($ov['name']) ? trim((string)$ov['name']) : '';
                    $rawSupplier   = isset($ov['supplier']) ? trim((string)$ov['supplier']) : '';
                    $rawCost       = isset($ov['cost']) ? trim((string)$ov['cost']) : '';
                    $rawSelling    = isset($ov['selling_price']) ? trim((string)$ov['selling_price']) : '';
                    $rawStock      = isset($ov['opening_stock']) ? trim((string)$ov['opening_stock']) : '';
                    $rawThreshold  = isset($ov['low_stock_threshold']) ? trim((string)$ov['low_stock_threshold']) : '';
                    $rawStatus     = isset($ov['status']) ? strtolower(trim((string)$ov['status'])) : 'active';
                } else {
                    $rawBarcode = isset($mapping['barcode']) && isset($row[$mapping['barcode']]) ? trim((string)$row[$mapping['barcode']]) : '';
                    $rawSku     = isset($mapping['sku']) && isset($row[$mapping['sku']]) ? trim((string)$row[$mapping['sku']]) : '';
                    if ($rawSku === '') $rawSku = $rawBarcode;
                    if ($rawBarcode === '' && $rawSku !== '') $rawBarcode = $rawSku;

                    $rawBrand      = isset($mapping['brand']) && isset($row[$mapping['brand']]) ? trim((string)$row[$mapping['brand']]) : '';
                    $rawCategory   = isset($mapping['category']) && isset($row[$mapping['category']]) ? trim((string)$row[$mapping['category']]) : '';
                    $rawName       = isset($mapping['name']) && isset($row[$mapping['name']]) ? trim((string)$row[$mapping['name']]) : '';
                    $rawSupplier   = isset($mapping['supplier']) && isset($row[$mapping['supplier']]) ? trim((string)$row[$mapping['supplier']]) : '';
                    $rawCost       = isset($mapping['cost']) && isset($row[$mapping['cost']]) ? trim((string)$row[$mapping['cost']]) : '';
                    $rawSelling    = isset($mapping['selling_price']) && isset($row[$mapping['selling_price']]) ? trim((string)$row[$mapping['selling_price']]) : '';
                    $rawStock      = isset($mapping['opening_stock']) && isset($row[$mapping['opening_stock']]) ? trim((string)$row[$mapping['opening_stock']]) : '';
                    $rawThreshold  = isset($mapping['low_stock_threshold']) && isset($row[$mapping['low_stock_threshold']]) ? trim((string)$row[$mapping['low_stock_threshold']]) : '';
                    $rawStatus     = isset($mapping['status']) && isset($row[$mapping['status']]) ? strtolower(trim((string)$row[$mapping['status']])) : 'active';
                }

                if ($rawBarcode === '') {
                    $failedCount++;
                    continue;
                }

                $barcodeLower = strtolower($rawBarcode);
                $isFirstInBatch = !isset($seenBarcodesInBatch[$barcodeLower]);

                // ── CASE 1: PRIMARY PRODUCT OCCURRENCE ──
                if ($isFirstInBatch) {
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

                    // Find existing product by Barcode
                    $existingProduct = Product::findByBarcode($rawBarcode);

                    if ($existingProduct) {
                        // UPDATE Existing Product
                        if ($importMode === 'create_new_only') {
                            $skippedCount++;
                            $seenBarcodesInBatch[$barcodeLower] = ['status' => 'skipped', 'product_id' => (int)$existingProduct['id']];
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

                        $upData = [
                            'barcode'             => $upBarcode,
                            'sku'                 => !empty($rawSku) ? $rawSku : ($existingProduct['sku'] ?: $upBarcode),
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
                            $seenBarcodesInBatch[$barcodeLower] = ['status' => 'active', 'product_id' => (int)$existingProduct['id']];
                        } else {
                            $failedCount++;
                            $seenBarcodesInBatch[$barcodeLower] = ['status' => 'failed', 'product_id' => (int)$existingProduct['id']];
                        }
                    } else {
                        // CREATE New Product
                        if ($importMode === 'update_existing_only') {
                            $skippedCount++;
                            $seenBarcodesInBatch[$barcodeLower] = ['status' => 'skipped', 'product_id' => null];
                            continue;
                        }

                        $newCost    = is_numeric($rawCost) ? (float)$rawCost : 0.00;
                        $newSelling = is_numeric($rawSelling) ? (float)$rawSelling : 0.00;
                        $newStock   = is_numeric($rawStock) ? (float)$rawStock : 0.00;
                        $newThresh  = is_numeric($rawThreshold) ? (float)$rawThreshold : 10.00;

                        $newData = [
                            'barcode'             => $rawBarcode,
                            'sku'                 => !empty($rawSku) ? $rawSku : $rawBarcode,
                            'brand_id'            => $brandId,
                            'category_id'         => $categoryId,
                            'name'                => $rawName ?: ('Product ' . $rawBarcode),
                            'supplier_id'         => $supplierId,
                            'cost'                => $newCost,
                            'selling_price'       => $newSelling,
                            'opening_stock'       => $newStock, // Product::create initializes opening stock & creates the first lot
                            'low_stock_threshold' => $newThresh,
                            'status'              => $rawStatus === 'inactive' ? 'inactive' : 'active',
                            'branch_id'           => $branchId
                        ];

                        $resCreate = Product::create($newData);
                        if ($resCreate['success']) {
                            $newId = (int)$resCreate['id'];
                            $createdCount++;
                            $seenBarcodesInBatch[$barcodeLower] = ['status' => 'active', 'product_id' => $newId];
                        } else {
                            $failedCount++;
                            $seenBarcodesInBatch[$barcodeLower] = ['status' => 'failed', 'product_id' => null];
                        }
                    }
                } else {
                    // ── CASE 2: ADDITIONAL PRICE LOT FOR THIS PRODUCT ──
                    $batchInfo = $seenBarcodesInBatch[$barcodeLower] ?? null;
                    if ($batchInfo && $batchInfo['status'] === 'skipped') {
                        $skippedCount++;
                        continue;
                    }

                    $productId = $batchInfo['product_id'] ?? null;
                    if (!$productId) {
                        $p = Product::findByBarcode($rawBarcode);
                        $productId = $p ? (int)$p['id'] : null;
                    }

                    if ($productId > 0) {
                        $lotCost    = is_numeric($rawCost) ? (float)$rawCost : 0.00;
                        $lotSelling = is_numeric($rawSelling) ? (float)$rawSelling : 0.00;
                        $lotStock   = is_numeric($rawStock) ? (float)$rawStock : 0.00;

                        $lotRes = Inventory::addStockLot(
                            $branchId,
                            $productId,
                            $lotCost,
                            $lotSelling,
                            $lotStock,
                            null, // auto-generated lot number
                            'INIT-STOCK-BR' . $branchId,
                            'Opening stock price lot from import' . (!empty($rawName) ? " ({$rawName})" : ''),
                            $userId,
                            'opening_stock',
                            null,
                            null,
                            'opening_stock',
                            $productId
                        );

                        // Double-Entry COA Posting for Opening Stock Lot (Rule 23)
                        if ($lotStock > 0 && $lotCost > 0) {
                            $lotOpeningVal = $lotStock * $lotCost;
                            try {
                                require_once __DIR__ . '/Accounting.php';
                                $invAccId = Accounting::getAccountIdByCode(Accounting::CODE_INVENTORY_ASSET);
                                $equityAccId = Accounting::getAccountIdByCode(Accounting::CODE_EQUITY);
                                $prodName = $rawName ?: ('Product ' . $rawBarcode);

                                $jeHeader = [
                                    'branch_id'   => $branchId,
                                    'entry_date'  => date('Y-m-d'),
                                    'source_type' => 'opening_balance',
                                    'source_id'   => (int)($lotRes['lot_id'] ?? $productId),
                                    'description' => "Opening Stock Price Lot for Product: {$prodName} ({$rawBarcode}) - Qty: {$lotStock} @ Cost: LKR " . number_format($lotCost, 2) . " [Val: LKR " . number_format($lotOpeningVal, 2) . "]",
                                    'created_by'  => $userId
                                ];
                                $jeLines = [
                                    [
                                        'account_id' => $invAccId,
                                        'debit'      => $lotOpeningVal,
                                        'credit'     => 0.00,
                                        'memo'       => "Inventory Asset: {$prodName} Lot"
                                    ],
                                    [
                                        'account_id' => $equityAccId,
                                        'debit'      => 0.00,
                                        'credit'     => $lotOpeningVal,
                                        'memo'       => "Opening Balance Equity: {$prodName} Lot"
                                    ]
                                ];
                                Accounting::postJournalEntry($jeHeader, $jeLines);
                            } catch (Throwable $e) {
                                Logger::error("Failed to post opening stock lot journal: " . $e->getMessage(), $e);
                            }
                        }

                        Audit::log('create', 'inventory_lot', $productId, null, [
                            'barcode' => $rawBarcode, 'cost' => $lotCost, 'selling_price' => $lotSelling, 'quantity' => $lotStock
                        ]);

                        $createdLotsCount++;
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
                ':notes'         => "Import executed in $importMode mode ($createdLotsCount price lots added)."
            ]);

            if (!$isNested) {
                $db->commit();
            }

            Audit::log('PRODUCT_IMPORT_COMMITTED', 'products', null, null, [
                'batch_no' => $batchNo, 'mode' => $importMode, 'created' => $createdCount, 'lots_added' => $createdLotsCount, 'updated' => $updatedCount, 'skipped' => $skippedCount, 'failed' => $failedCount
            ]);

            return [
                'success'       => true,
                'batch_no'      => $batchNo,
                'summary'       => [
                    'total_rows'         => $totalRows,
                    'created_count'      => $createdCount,
                    'created_lots_count' => $createdLotsCount,
                    'updated_count'      => $updatedCount,
                    'skipped_count'      => $skippedCount,
                    'failed_count'       => $failedCount
                ],
                'message'       => "Product catalog import completed successfully! Created: {$createdCount} products, Added: {$createdLotsCount} price lots, Updated: {$updatedCount}, Skipped: {$skippedCount}."
            ];

        } catch (Throwable $e) {
            if (!$isNested && $db->inTransaction()) {
                $db->rollBack();
            }
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
