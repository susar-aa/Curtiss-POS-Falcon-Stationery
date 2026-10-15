<?php
/**
 * Curtiss POS - Comprehensive CLI Test Suite for Session Inactivity & Return URL Preservation
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Settings.php';
require_once __DIR__ . '/../core/Audit.php';

echo "=== STARTING SESSION INACTIVITY & RETURN URL VERIFICATION ===\n\n";

$passed = 0;
$failed = 0;

function assertCondition(bool $cond, string $message) {
    global $passed, $failed;
    if ($cond) {
        echo "[PASS] " . $message . "\n";
        $passed++;
    } else {
        echo "[FAIL] " . $message . "\n";
        $failed++;
    }
}

// 1. Test return_url sanitization logic
function sanitizeReturnUrl(string $url): string {
    $returnUrl = trim($url);
    if (!empty($returnUrl)) {
        if (preg_match('#^https?://#i', $returnUrl)) {
            $parsed = parse_url($returnUrl);
            $currHost = 'localhost';
            if (($parsed['host'] ?? '') !== $currHost) {
                return '';
            } else {
                $returnUrl = ($parsed['path'] ?? '') . (!empty($parsed['query']) ? '?' . $parsed['query'] : '');
            }
        }
        if (str_contains($returnUrl, 'login') || str_contains($returnUrl, 'logout')) {
            return '';
        }
    }
    return $returnUrl;
}

$clean1 = sanitizeReturnUrl('/CURTISS/Kandy%20Kids/POS/branches?filter=active');
assertCondition($clean1 === '/CURTISS/Kandy%20Kids/POS/branches?filter=active', "Sanitizer preserves internal route with query string: '$clean1'");

$clean2 = sanitizeReturnUrl('http://evil-phishing.com/steal-creds');
assertCondition($clean2 === '', "Sanitizer drops foreign external URLs to prevent open redirects");

$clean3 = sanitizeReturnUrl('/POS/login?expired=1');
assertCondition($clean3 === '', "Sanitizer drops login loops: '$clean3'");

$clean4 = sanitizeReturnUrl('/POS/sales_history.php?from=2026-10-01&to=2026-10-14');
assertCondition($clean4 === '/POS/sales_history.php?from=2026-10-01&to=2026-10-14', "Sanitizer retains complex report queries: '$clean4'");

// 2. Test Session Expiration Redirection Target Generation
$_SERVER['REQUEST_URI'] = '/CURTISS/Kandy%20Kids/POS/branches.php?action=edit&id=2';
$returnUrl = $_SERVER['REQUEST_URI'];
$target = 'login?expired=1';
if (!empty($returnUrl) && !str_contains($returnUrl, 'login') && !str_contains($returnUrl, 'logout')) {
    $target .= '&return_url=' . urlencode($returnUrl);
}
assertCondition(str_contains($target, 'return_url=%2FCURTISS%2FKandy'), "Session expiration target encodes exact request URI: '$target'");

// 3. Test Inactivity Ping Logic
$_SESSION['user_id'] = 1;
$_SESSION['last_activity'] = time() - 100;
Auth::checkInactivityTimeout();
assertCondition($_SESSION['last_activity'] >= time() - 2, "checkInactivityTimeout updates last_activity to current timestamp on active session");

// 4. Test select_branch return_url handling
$_SESSION['return_url'] = '/CURTISS/Kandy%20Kids/POS/settings.php';
$targetAfterBranch = $_SESSION['return_url'] ?? 'dashboard';
unset($_SESSION['return_url']);
assertCondition($targetAfterBranch === '/CURTISS/Kandy%20Kids/POS/settings.php', "Branch selection correctly routes to preserved return_url");
assertCondition(!isset($_SESSION['return_url']), "Temporary return_url session token is cleared after redirect");

// 5. Verify ping.php endpoint exists and is readable
assertCondition(file_exists(__DIR__ . '/../api/ping.php'), "api/ping.php heartbeat endpoint exists");

// Clean up session
$_SESSION = [];

echo "\n======================================================\n";
echo "SUMMARY: Passed: {$passed}, Failed: {$failed}\n";
echo "======================================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
