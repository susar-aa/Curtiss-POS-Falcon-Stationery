<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

$tables = ['bank_deposits'];
foreach ($tables as $tbl) {
    echo "=== $tbl ===\n";
    $cols = Database::fetchAll("DESCRIBE $tbl");
    foreach ($cols as $c) {
        echo " - " . $c['Field'] . " (" . $c['Type'] . ")\n";
    }
}
