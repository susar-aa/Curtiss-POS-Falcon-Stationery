<?php
$lines = file(__DIR__ . '/../views/pos/pos_screen.php');
foreach ($lines as $i => $line) {
    if (strpos($line, 'id="modal-payment"') !== false) {
        echo "modal-payment found on line: " . ($i + 1) . "\n";
        for ($j = $i; $j < min(count($lines), $i + 150); $j++) {
            echo ($j + 1) . ": " . $lines[$j];
            if (strpos($lines[$j], 'modal-invoice-complete') !== false) break;
        }
        break;
    }
}
