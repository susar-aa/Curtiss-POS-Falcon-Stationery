<?php
/**
 * Curtiss POS - Supplier Import Engine
 * Handles CSV import of suppliers with preview, stable supplier_code matching, mode filtering, and blank cell protection.
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Supplier.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Audit.php';

class SupplierImportEngine
{
    public const MODE_CREATE_AND_UPDATE = 'create_and_update';
    public const MODE_UPDATE_EXISTING   = 'update_existing_only';
    public const MODE_CREATE_NEW        = 'create_new_only';

    public static function parseCsv(string $filePath): array
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return ['success' => false, 'message' => 'CSV file not found or unreadable.'];
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return ['success' => false, 'message' => 'Unable to open CSV file.'];
        }

        // Detect header
        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return ['success' => false, 'message' => 'CSV file is empty.'];
        }

        // Clean UTF-8 BOM if present
        if (isset($header[0])) {
            $header[0] = preg_replace('/\x{EF}\x{BB}\x{BF}/', '', $header[0]);
        }

        $normalizedHeaders = array_map(function ($h) {
            $h = strtolower(trim($h));
            $h = str_replace([' ', '-', '_'], '', $h);
            return $h;
        }, $header);

        $map = [
            'suppliercode' => null,
            'code'         => null,
            'name'         => null,
            'suppliername' => null,
            'phone'        => null,
            'whatsapp'     => null,
            'address'      => null,
            'status'       => null
        ];

        foreach ($normalizedHeaders as $idx => $nh) {
            if ($nh === 'suppliercode' || $nh === 'code') $map['supplier_code'] = $idx;
            elseif ($nh === 'name' || $nh === 'suppliername') $map['name'] = $idx;
            elseif ($nh === 'phone' || $nh === 'telephone' || $nh === 'contact') $map['phone'] = $idx;
            elseif ($nh === 'whatsapp' || $nh === 'wa') $map['whatsapp'] = $idx;
            elseif ($nh === 'address') $map['address'] = $idx;
            elseif ($nh === 'status') $map['status'] = $idx;
        }

        if (!isset($map['name'])) {
            fclose($handle);
            return ['success' => false, 'message' => 'CSV must include a "Supplier Name" or "Name" column.'];
        }

        $rows = [];
        $rowNum = 1;
        while (($data = fgetcsv($handle)) !== false) {
            $rowNum++;
            if (empty(array_filter($data))) continue; // Skip blank lines

            $val = function ($key) use ($data, $map) {
                if (isset($map[$key]) && isset($data[$map[$key]])) {
                    return trim($data[$map[$key]]);
                }
                return '';
            };

            $rows[] = [
                'row_index'     => $rowNum,
                'supplier_code' => $val('supplier_code'),
                'name'          => $val('name'),
                'phone'         => $val('phone'),
                'whatsapp'      => $val('whatsapp'),
                'address'       => $val('address'),
                'status'        => strtolower($val('status')) === 'inactive' ? 'inactive' : 'active'
            ];
        }

        fclose($handle);
        return ['success' => true, 'rows' => $rows];
    }

    public static function preview(string $filePath, string $mode = self::MODE_CREATE_AND_UPDATE, bool $ignoreBlankCells = true): array
    {
        $parse = self::parseCsv($filePath);
        if (!$parse['success']) {
            return $parse;
        }

        $rows = $parse['rows'];
        $previewRows = [];
        $summary = [
            'total'          => count($rows),
            'will_create'    => 0,
            'will_update'    => 0,
            'skipped'        => 0,
            'invalid'        => 0
        ];

        foreach ($rows as $row) {
            $name = $row['name'];
            $code = $row['supplier_code'];
            $status = 'valid';
            $action = 'none';
            $message = '';

            if (empty($name)) {
                $status = 'invalid';
                $message = 'Supplier Name is missing.';
                $summary['invalid']++;
            } else {
                $existing = !empty($code) ? Supplier::findByCode($code) : null;

                if ($existing) {
                    if ($mode === self::MODE_CREATE_NEW) {
                        $status = 'skipped';
                        $action = 'skip';
                        $message = "Supplier Code '{$code}' already exists (Skipped in Create New Only mode).";
                        $summary['skipped']++;
                    } else {
                        $action = 'update';
                        $message = "Matches existing supplier #{$existing['id']} ({$existing['name']}). Will update.";
                        $summary['will_update']++;
                    }
                } else {
                    if ($mode === self::MODE_UPDATE_EXISTING) {
                        $status = 'skipped';
                        $action = 'skip';
                        $message = !empty($code) ? "Supplier Code '{$code}' not found (Skipped in Update Existing Only mode)." : "No Supplier Code provided (Skipped in Update Existing Only mode).";
                        $summary['skipped']++;
                    } else {
                        $action = 'create';
                        $genCode = !empty($code) ? $code : '(Auto-generated)';
                        $message = "New supplier will be created with code {$genCode}.";
                        $summary['will_create']++;
                    }
                }
            }

            $previewRows[] = array_merge($row, [
                'preview_status' => $status,
                'preview_action' => $action,
                'message'        => $message
            ]);
        }

        return [
            'success'            => true,
            'mode'               => $mode,
            'ignore_blank_cells' => $ignoreBlankCells,
            'summary'            => $summary,
            'rows'               => $previewRows
        ];
    }

    public static function import(string $filePath, string $mode = self::MODE_CREATE_AND_UPDATE, bool $ignoreBlankCells = true): array
    {
        $previewRes = self::preview($filePath, $mode, $ignoreBlankCells);
        if (!$previewRes['success']) {
            return $previewRes;
        }

        $db = Database::getInstance()->getConnection();

        try {
            $db->beginTransaction();

            $createdCount = 0;
            $updatedCount = 0;
            $skippedCount = 0;
            $failedCount  = 0;

            foreach ($previewRes['rows'] as $row) {
                if ($row['preview_status'] === 'invalid' || $row['preview_action'] === 'skip') {
                    $skippedCount++;
                    continue;
                }

                $code = $row['supplier_code'];
                $name = $row['name'];
                $phone = $row['phone'];
                $whatsapp = $row['whatsapp'];
                $address = $row['address'];
                $status = $row['status'];

                if ($row['preview_action'] === 'update') {
                    $existing = Supplier::findByCode($code);
                    if ($existing) {
                        $upName     = ($ignoreBlankCells && empty($name)) ? $existing['name'] : $name;
                        $upPhone    = ($ignoreBlankCells && $phone === '') ? $existing['phone'] : $phone;
                        $upWhatsapp = ($ignoreBlankCells && $whatsapp === '') ? $existing['whatsapp'] : $whatsapp;
                        $upAddress  = ($ignoreBlankCells && $address === '') ? $existing['address'] : $address;
                        $upStatus   = ($ignoreBlankCells && empty($status)) ? $existing['status'] : $status;

                        $res = Supplier::update((int)$existing['id'], $upName, $upPhone, $upWhatsapp, $upAddress, $upStatus, $code);
                        if ($res['success']) {
                            $updatedCount++;
                        } else {
                            $failedCount++;
                        }
                    } else {
                        $failedCount++;
                    }
                } elseif ($row['preview_action'] === 'create') {
                    $res = Supplier::create($name, $phone, $whatsapp, $address, $status, $code);
                    if ($res['success']) {
                        $createdCount++;
                    } else {
                        $failedCount++;
                    }
                }
            }

            $db->commit();

            Audit::log('SUPPLIER_CSV_IMPORT', 'suppliers', '0', null, [
                'mode'               => $mode,
                'ignore_blank_cells' => $ignoreBlankCells,
                'created'            => $createdCount,
                'updated'            => $updatedCount,
                'skipped'            => $skippedCount,
                'failed'             => $failedCount
            ]);

            return [
                'success' => true,
                'message' => "Import completed: {$createdCount} created, {$updatedCount} updated, {$skippedCount} skipped, {$failedCount} failed.",
                'summary' => [
                    'created' => $createdCount,
                    'updated' => $updatedCount,
                    'skipped' => $skippedCount,
                    'failed'  => $failedCount
                ]
            ];

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Supplier CSV Import failed: " . $e->getMessage(), $e);
            return ['success' => false, 'message' => 'Import failed due to database error: ' . $e->getMessage()];
        }
    }
}
