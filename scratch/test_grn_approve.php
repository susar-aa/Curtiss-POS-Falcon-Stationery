<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/GRN.php';

$res = GRN::approve(1010, 1);
echo "GRN::approve(1010) Result: " . json_encode($res, JSON_PRETTY_PRINT) . "\n";
