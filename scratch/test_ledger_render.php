<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Supplier.php';

$suppliers = Supplier::all(false);
foreach ($suppliers as $s) {
    $ledger = Supplier::getActivityLedger((int)$s['id']);
    if (!empty($ledger)) {
        echo "Supplier #{$s['id']} ({$s['name']}) has " . count($ledger) . " activity item(s):\n";
        foreach (array_slice($ledger, 0, 3) as $item) {
            echo "  - [{$item['type']}] {$item['title']} | Date: {$item['date']} | LKR {$item['amount']}\n";
        }
    }
}
