<?php
$lines = file(__DIR__ . '/../views/pos/pos_screen.php');
foreach ($lines as $i => $l) {
    if (strpos($l, 'btn-finalize-sale') !== false) {
        echo ($i + 1) . ": " . $l;
    }
}
