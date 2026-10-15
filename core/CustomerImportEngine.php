<?php
/**
 * Curtiss POS - Customer Import Engine (Section 5)
 * Safe CSV Import Engine for Customer Master Data with row-level validation,
 * duplicate code/barcode protection, and financial balance isolation.
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Audit.php';
require_once __DIR__ . '/Customer.php';

class CustomerImportEngine
{
    /**
     * Preview CSV Import file without modifying database
     */
    public static function previewImport(string $filePath, array $options = []): array
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return ['success' => false, 'message' => 'Import file not found or unreadable.'];
        }

        $mode = $options['mode'] ?? 'create_update'; // 'create_update', 'update_only', 'create_only'
        $ignoreBlanks = isset($options['ignore_blanks']) ? (bool)$options['ignore_blanks'] : true;

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return ['success' => false, 'message' => 'Failed to open CSV file.'];
        }

        // Read header row
        $rawHeaders = fgetcsv($handle);
        if (!$rawHeaders) {
            fclose($handle);
            return ['success' => false, 'message' => 'CSV file is empty or missing headers.'];
        }

        // Remove UTF-8 BOM from header if present
        if (isset($rawHeaders[0])) {
            $rawHeaders[0] = preg_replace('/\x{EF}\x{BB}\x{BF}/', '', $rawHeaders[0]);
        }

        // Header mapping
        $headerMap = [];
        $ignoredFinancialCols = [];

        foreach ($rawHeaders as $idx => $hName) {
            $norm = strtolower(trim($hName));
            if (in_array($norm, ['code', 'customer code', 'customer_code', 'cus_code', 'id_code'])) {
                $headerMap['customer_code'] = $idx;
            } elseif (in_array($norm, ['name', 'customer name', 'customer_name', 'full name'])) {
                $headerMap['name'] = $idx;
            } elseif (in_array($norm, ['phone', 'telephone', 'phone_number', 'mobile_phone'])) {
                $headerMap['phone'] = $idx;
            } elseif (in_array($norm, ['whatsapp', 'whatsapp_number', 'wa'])) {
                $headerMap['whatsapp'] = $idx;
            } elseif (in_array($norm, ['address', 'street_address', 'location'])) {
                $headerMap['address'] = $idx;
            } elseif (in_array($norm, ['card_barcode', 'card barcode', 'barcode', 'customer_card'])) {
                $headerMap['card_barcode'] = $idx;
            } elseif (in_array($norm, ['credit_limit', 'credit limit', 'limit'])) {
                $headerMap['credit_limit'] = $idx;
            } elseif (in_array($norm, ['status', 'state'])) {
                $headerMap['status'] = $idx;
            } elseif (in_array($norm, ['outstanding', 'credit_balance', 'balance', 'ledger', 'due_amount', 'paid_amount'])) {
                $ignoredFinancialCols[] = $hName;
            }
        }

        if (!isset($headerMap['name']) && !isset($headerMap['customer_code'])) {
            fclose($handle);
            return ['success' => false, 'message' => 'CSV must contain at least a "Name" or "Customer Code" column.'];
        }

        // Tracking data
        $fileCodes = [];
        $fileBarcodes = [];

        $totalRows = 0;
        $newCount = 0;
        $updateCount = 0;
        $unchangedCount = 0;
        $invalidCount = 0;
        $dupCodesCount = 0;
        $dupBarcodesCount = 0;
        $skippedCount = 0;

        $rowDetails = [];
        $rowNum = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;

            // Skip empty rows
            if (empty(array_filter($row, function($v) { return trim($v) !== ''; }))) {
                continue;
            }

            $totalRows++;
            $errors = [];

            $code = isset($headerMap['customer_code']) ? trim($row[$headerMap['customer_code']] ?? '') : '';
            $name = isset($headerMap['name']) ? trim($row[$headerMap['name']] ?? '') : '';
            $phone = isset($headerMap['phone']) ? trim($row[$headerMap['phone']] ?? '') : '';
            $whatsapp = isset($headerMap['whatsapp']) ? trim($row[$headerMap['whatsapp']] ?? '') : '';
            $address = isset($headerMap['address']) ? trim($row[$headerMap['address']] ?? '') : '';
            $cardBarcode = isset($headerMap['card_barcode']) ? trim($row[$headerMap['card_barcode']] ?? '') : '';
            $creditLimitStr = isset($headerMap['credit_limit']) ? trim($row[$headerMap['credit_limit']] ?? '') : '';
            $statusStr = isset($headerMap['status']) ? strtolower(trim($row[$headerMap['status']] ?? '')) : '';

            $status = ($statusStr === 'inactive') ? 'inactive' : 'active';
            $creditLimit = ($creditLimitStr !== '') ? (float)$creditLimitStr : null;

            // Walk-in protection
            if (strtoupper($code) === 'CUST-WALKIN' || strtoupper($code) === 'CUS-WALKIN') {
                $skippedCount++;
                $rowDetails[] = [
                    'row_number' => $rowNum,
                    'action'     => 'skip',
                    'reason'     => 'Walk-in Customer code is protected and cannot be imported/modified.',
                    'data'       => ['code' => $code, 'name' => $name]
                ];
                continue;
            }

            // Check existing customer in DB by code
            $existingCustomer = null;
            if ($code !== '') {
                $existingCustomer = Database::fetchOne("SELECT * FROM customers WHERE customer_code = :code AND deleted_at IS NULL", [':code' => $code]);
            }

            // Code duplication check in CSV file
            if ($code !== '') {
                $codeKey = strtolower($code);
                if (isset($fileCodes[$codeKey])) {
                    $errors[] = "Duplicate Customer Code '{$code}' found in CSV row #{$fileCodes[$codeKey]} and row #{$rowNum}.";
                    $dupCodesCount++;
                } else {
                    $fileCodes[$codeKey] = $rowNum;
                }
            }

            // Barcode duplication check in CSV file and DB
            if ($cardBarcode !== '') {
                $barcodeKey = strtolower($cardBarcode);
                if (isset($fileBarcodes[$barcodeKey])) {
                    $errors[] = "Duplicate Card Barcode '{$cardBarcode}' found in CSV row #{$fileBarcodes[$barcodeKey]} and row #{$rowNum}.";
                    $dupBarcodesCount++;
                } else {
                    $fileBarcodes[$barcodeKey] = $rowNum;

                    // Check DB for duplicate barcode on OTHER customers
                    $excludeId = $existingCustomer ? (int)$existingCustomer['id'] : 0;
                    $dupDbBarcode = Database::fetchOne("
                        SELECT id, name FROM customers 
                        WHERE card_barcode = :bc AND id != :ex_id AND deleted_at IS NULL
                    ", [':bc' => $cardBarcode, ':ex_id' => $excludeId]);

                    if ($dupDbBarcode) {
                        $errors[] = "Card Barcode '{$cardBarcode}' is already assigned to customer '{$dupDbBarcode['name']}' in system.";
                        $dupBarcodesCount++;
                    }
                }
            }

            // Action determination
            $action = 'skip';
            $reason = '';

            if ($existingCustomer) {
                if ($mode === 'create_only') {
                    $action = 'skip';
                    $reason = "Existing customer code '{$code}' skipped in Create Only mode.";
                    $skippedCount++;
                } else {
                    $action = 'update';
                    $updateCount++;
                }
            } else {
                if ($mode === 'update_only') {
                    $action = 'skip';
                    $reason = "Unknown customer code '{$code}' skipped in Update Only mode.";
                    $skippedCount++;
                } else {
                    if (empty($name)) {
                        $errors[] = "Customer name is required for creating new customer.";
                    }
                    $action = 'create';
                    $newCount++;
                }
            }

            if (!empty($errors)) {
                $action = 'invalid';
                $invalidCount++;
                if ($action === 'create') $newCount--;
                if ($action === 'update') $updateCount--;
            }

            $rowDetails[] = [
                'row_number' => $rowNum,
                'action'     => $action,
                'reason'     => $reason,
                'errors'     => $errors,
                'data'       => [
                    'customer_code' => $code,
                    'name'          => $name,
                    'phone'         => $phone,
                    'whatsapp'      => $whatsapp,
                    'address'       => $address,
                    'card_barcode'  => $cardBarcode,
                    'credit_limit'  => $creditLimit,
                    'status'        => $status
                ]
            ];
        }

        fclose($handle);

        return [
            'success'                  => true,
            'total_rows'               => $totalRows,
            'new_count'                => $newCount,
            'update_count'             => $updateCount,
            'unchanged_count'          => $unchangedCount,
            'invalid_count'            => $invalidCount,
            'duplicate_codes_count'    => $dupCodesCount,
            'duplicate_barcodes_count' => $dupBarcodesCount,
            'skipped_count'            => $skippedCount,
            'ignored_financial_cols'   => array_unique($ignoredFinancialCols),
            'rows'                     => $rowDetails
        ];
    }

    /**
     * Commit CSV Import inside a database transaction
     */
    public static function commitImport(string $filePath, array $options = [], int $userId = 1): array
    {
        $preview = self::previewImport($filePath, $options);
        if (!$preview['success']) {
            return $preview;
        }

        $ignoreBlanks = isset($options['ignore_blanks']) ? (bool)$options['ignore_blanks'] : true;

        $db = Database::getInstance()->getConnection();
        try {
            $db->beginTransaction();

            $createdCount = 0;
            $updatedCount = 0;
            $skippedCount = $preview['skipped_count'];

            foreach ($preview['rows'] as $r) {
                if ($r['action'] === 'invalid' || $r['action'] === 'skip') {
                    continue;
                }

                $d = $r['data'];
                $code = trim($d['customer_code'] ?? '');
                $name = trim($d['name'] ?? '');
                $phone = trim($d['phone'] ?? '');
                $whatsapp = trim($d['whatsapp'] ?? '');
                $address = trim($d['address'] ?? '');
                $cardBarcode = trim($d['card_barcode'] ?? '');
                $creditLimit = $d['credit_limit'];
                $status = $d['status'];

                if ($r['action'] === 'create') {
                    if (empty($code)) {
                        $code = Customer::generateCustomerCode();
                    }

                    $db->prepare("
                        INSERT INTO customers (customer_code, name, phone, whatsapp, address, card_barcode, credit_limit, credit_balance, is_walkin, status)
                        VALUES (:code, :name, :phone, :whatsapp, :address, :card_barcode, :credit_limit, 0.00, 0, :status)
                    ")->execute([
                        ':code'         => $code,
                        ':name'         => $name,
                        ':phone'        => $phone !== '' ? $phone : null,
                        ':whatsapp'     => $whatsapp !== '' ? $whatsapp : null,
                        ':address'      => $address !== '' ? $address : null,
                        ':card_barcode' => $cardBarcode !== '' ? $cardBarcode : null,
                        ':credit_limit' => $creditLimit !== null ? $creditLimit : 0.00,
                        ':status'       => $status
                    ]);
                    $createdCount++;
                } elseif ($r['action'] === 'update') {
                    $existing = Database::fetchOne("SELECT * FROM customers WHERE customer_code = :code AND deleted_at IS NULL", [':code' => $code]);
                    if ($existing) {
                        $finalName = ($ignoreBlanks && $name === '') ? $existing['name'] : $name;
                        $finalPhone = ($ignoreBlanks && $phone === '') ? $existing['phone'] : ($phone !== '' ? $phone : null);
                        $finalWhatsapp = ($ignoreBlanks && $whatsapp === '') ? $existing['whatsapp'] : ($whatsapp !== '' ? $whatsapp : null);
                        $finalAddress = ($ignoreBlanks && $address === '') ? $existing['address'] : ($address !== '' ? $address : null);
                        $finalBarcode = ($ignoreBlanks && $cardBarcode === '') ? $existing['card_barcode'] : ($cardBarcode !== '' ? $cardBarcode : null);
                        $finalLimit = ($ignoreBlanks && $creditLimit === null) ? (float)$existing['credit_limit'] : ($creditLimit !== null ? $creditLimit : 0.00);

                        $db->prepare("
                            UPDATE customers SET 
                                name = :name, phone = :phone, whatsapp = :whatsapp, address = :address, 
                                card_barcode = :card_barcode, credit_limit = :credit_limit, status = :status, updated_at = NOW()
                            WHERE id = :id
                        ")->execute([
                            ':name'         => $finalName,
                            ':phone'        => $finalPhone,
                            ':whatsapp'     => $finalWhatsapp,
                            ':address'      => $finalAddress,
                            ':card_barcode' => $finalBarcode,
                            ':credit_limit' => $finalLimit,
                            ':status'       => $status,
                            ':id'           => $existing['id']
                        ]);
                        $updatedCount++;
                    }
                }
            }

            $db->commit();

            Audit::log('CUSTOMER_IMPORT_COMMITTED', 'customers', '0', null, [
                'total_rows'    => $preview['total_rows'],
                'created_count' => $createdCount,
                'updated_count' => $updatedCount,
                'user_id'       => $userId
            ]);

            return [
                'success'       => true,
                'message'       => "Customer CSV Import completed successfully: {$createdCount} created, {$updatedCount} updated, {$skippedCount} skipped.",
                'total_rows'    => $preview['total_rows'],
                'created_count' => $createdCount,
                'updated_count' => $updatedCount,
                'skipped_count' => $skippedCount,
                'invalid_count' => $preview['invalid_count']
            ];
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to commit customer CSV import: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Database error committing customer CSV import: ' . $e->getMessage()];
        }
    }
}
