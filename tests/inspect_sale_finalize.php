<?php
$lines = file(__DIR__ . '/../core/Sale.php');
foreach ($lines as $i => $l) {
    if (strpos($l, 'function holdInvoice') !== false || strpos($l, 'function getHeldInvoices') !== false) {
        echo ($i + 1) . ": " . $l;
        for ($j = $i; $j < min(count($lines), $i + 40); $j++) {
            echo ($j + 1) . ": " . $lines[$j];
        }
    }
}
