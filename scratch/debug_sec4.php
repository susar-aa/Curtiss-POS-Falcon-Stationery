<?php
$suites = [
    'verify_stage15.php',
    'verify_section2_enhancements.php',
    'verify_section3_enhancements.php'
];

foreach ($suites as $s) {
    echo "=== Running $s ===\n";
    $output = [];
    $rc = 0;
    exec("C:\\xampp\\php\\php.exe tests/$s 2>&1", $output, $rc);
    echo "RC: $rc, Lines: " . count($output) . "\n";
    echo implode("\n", array_slice($output, 0, 10)) . "\n\n";
}
