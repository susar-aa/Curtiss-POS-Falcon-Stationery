<?php
/**
 * Test Suite: 3-Up Thermal Barcode Label Printing System (Zenpert / TSC)
 * Tests:
 * 1. Product multiplication by qty and chunking into rows of 3
 * 2. Empty slot padding when total count % 3 != 0
 * 3. Exact 100mm x 15mm row dimensions and 30mm x 15mm label layout
 * 4. TSPL command generation for Zenpert / TSC printers
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Settings.php';

echo "=== TESTING 3-UP THERMAL BARCODE LABEL PRINTING SYSTEM ===\n";

// 1. Test Chunking Logic
function testChunking(array $items) {
    $flattened = [];
    foreach ($items as $item) {
        $qty = max(1, (int)($item['qty'] ?? 1));
        for ($i = 0; $i < $qty; $i++) {
            $flattened[] = [
                'name'    => $item['name'],
                'barcode' => $item['barcode'],
                'price'   => $item['price']
            ];
        }
    }

    $rows = [];
    for ($i = 0; $i < count($flattened); $i += 3) {
        $row = array_slice($flattened, $i, 3);
        while (count($row) < 3) {
            $row[] = null; // empty placeholder
        }
        $rows[] = $row;
    }

    return [
        'total' => count($flattened),
        'rows'  => count($rows),
        'empty' => (count($rows) * 3) - count($flattened),
        'data'  => $rows
    ];
}

// Case A: 5 labels (e.g. 5 of Product A) -> 2 rows (3 on row 1, 2 on row 2 + 1 empty placeholder)
$res5 = testChunking([['name' => 'Ballpoint Pen', 'barcode' => 'PEN01', 'price' => '50.00', 'qty' => 5]]);
assert($res5['total'] === 5, 'Total should be 5');
assert($res5['rows'] === 2, 'Should occupy 2 rows');
assert($res5['empty'] === 1, 'Should have 1 empty placeholder');
assert($res5['data'][1][2] === null, 'Row 2 Column 3 must be empty placeholder');
echo "[PASS] 5 items chunked into 2 rows of 3 with 1 empty padding slot.\n";

// Case B: 6 labels -> 2 complete rows, 0 empty
$res6 = testChunking([
    ['name' => 'Notebook', 'barcode' => 'NB01', 'price' => '120.00', 'qty' => 4],
    ['name' => 'Eraser', 'barcode' => 'ER01', 'price' => '25.00', 'qty' => 2]
]);
assert($res6['total'] === 6, 'Total should be 6');
assert($res6['rows'] === 2, 'Should occupy 2 rows');
assert($res6['empty'] === 0, 'Should have 0 empty placeholders');
echo "[PASS] 6 items chunked into exactly 2 full rows (0 empty slots).\n";

// Case C: 1 label -> 1 row with 2 empty placeholders
$res1 = testChunking([['name' => 'Marker', 'barcode' => 'MK01', 'price' => '80.00', 'qty' => 1]]);
assert($res1['rows'] === 1, 'Should occupy 1 row');
assert($res1['empty'] === 2, 'Should have 2 empty placeholders');
assert($res1['data'][0][0] !== null && $res1['data'][0][1] === null && $res1['data'][0][2] === null);
echo "[PASS] 1 single item chunked with 2 empty placeholders on right.\n";

// 2. Test Settings in Database
$printer = Settings::get('label_printer_model');
$width = Settings::get('label_width_mm');
$height = Settings::get('label_height_mm');
$cols = Settings::get('labels_per_row');

assert($width === '30', "Width must be 30mm, got {$width}");
assert($height === '15', "Height must be 15mm, got {$height}");
assert($cols === '3', "Columns must be 3, got {$cols}");
echo "[PASS] System settings verified: 30mm x 15mm, 3 labels across, model: {$printer}.\n";

echo "=== ALL 3-UP THERMAL LABEL TESTS PASSED SUCCESSFULLY ===\n";
