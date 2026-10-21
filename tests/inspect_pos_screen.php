<?php
$content = file_get_contents(__DIR__ . '/../views/pos/pos_screen.php');

// Find all modals
preg_match_all('/id=["\']([^"\']*modal[^"\']*)["\']/i', $content, $mModals);
echo "MODALS FOUND (" . count($mModals[1]) . "):\n";
print_r(array_unique($mModals[1]));

// Find API calls or endpoints
preg_match_all('/(?:fetch|url|action|href)\s*[:=\(]\s*[\'"`]([^\'"`]+\.php[^\'"`]*)[\'"`]/i', $content, $mApis);
echo "\nAPIS / PHP ENDPOINTS REFERENCED (" . count($mApis[1]) . "):\n";
print_r(array_unique($mApis[1]));

// Find major section headings or buttons
preg_match_all('/<button[^>]+id=["\']([^"\']+)["\']/i', $content, $mBtns);
echo "\nBUTTON IDS (" . count($mBtns[1]) . "):\n";
print_r(array_unique($mBtns[1]));
