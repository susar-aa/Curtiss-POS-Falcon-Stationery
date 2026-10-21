<?php
/**
 * Curtiss POS - Dual Mode Login Controller (PIN Keypad & Username/Password)
 */

require_once __DIR__ . '/config/config.php';

$returnUrl = trim($_POST['return_url'] ?? $_GET['return_url'] ?? '');

// Sanitize returnUrl: ensure it doesn't lead to foreign hosts or auth loops
if (!empty($returnUrl)) {
    if (preg_match('#^https?://#i', $returnUrl)) {
        $parsed = parse_url($returnUrl);
        $currHost = $_SERVER['HTTP_HOST'] ?? '';
        if (($parsed['host'] ?? '') !== $currHost) {
            $returnUrl = '';
        } else {
            $returnUrl = ($parsed['path'] ?? '') . (!empty($parsed['query']) ? '?' . $parsed['query'] : '');
        }
    }
    if (str_contains($returnUrl, 'login') || str_contains($returnUrl, 'logout')) {
        $returnUrl = '';
    }
}

if (Auth::isLoggedIn()) {
    $target = !empty($returnUrl) ? $returnUrl : 'dashboard';
    header('Location: ' . $target);
    exit();
}

$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$isMobileDevice = (bool)preg_match('/(android|iphone|ipad|ipod|blackberry|windows phone|mobile)/i', $userAgent);

$error = null;
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
    || (isset($_POST['ajax']) && $_POST['ajax'] == '1');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Block mobile access
    if ($isMobileDevice) {
        $error = 'Mobile access is restricted. Please use a desktop computer or workstation.';
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $error]);
            exit();
        }
    }

    // CSRF token validation if session token exists
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!empty($_SESSION['csrf_token']) && !empty($csrfToken) && empty($error)) {
        if (!Auth::validateCsrfToken($csrfToken)) {
            $error = 'Security session validation expired. Please refresh the page and try again.';
        }
    }

    if (empty($error)) {
        $loginType = trim($_POST['login_type'] ?? 'pin');

        if ($loginType === 'pin') {
            $pin = trim($_POST['pin'] ?? '');
            if (empty($pin)) {
                $error = 'Please enter your 5-digit PIN.';
            } elseif (!preg_match('/^\d{5}$/', $pin)) {
                $error = 'PIN must be exactly 5 numeric digits.';
            } else {
                $result = Auth::attemptPin($pin);
                if ($result['success']) {
                    if (!empty($returnUrl)) {
                        $_SESSION['return_url'] = $returnUrl;
                    }
                    $redirect = !empty($result['needs_branch_selection']) ? 'select_branch' : (!empty($returnUrl) ? $returnUrl : 'dashboard');
                    if (!empty($result['needs_branch_selection']) === false) {
                        unset($_SESSION['return_url']);
                    }

                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true, 'redirect' => $redirect]);
                        exit();
                    }
                    header('Location: ' . $redirect);
                    exit();
                } else {
                    $error = $result['message'];
                }
            }
        } else {
            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');

            if (empty($username) || empty($password)) {
                $error = 'Please enter both username and password.';
            } else {
                $result = Auth::attempt($username, $password);
                if ($result['success']) {
                    if (!empty($returnUrl)) {
                        $_SESSION['return_url'] = $returnUrl;
                    }
                    $redirect = !empty($result['needs_branch_selection']) ? 'select_branch' : (!empty($returnUrl) ? $returnUrl : 'dashboard');
                    if (!empty($result['needs_branch_selection']) === false) {
                        unset($_SESSION['return_url']);
                    }

                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true, 'redirect' => $redirect]);
                        exit();
                    }
                    header('Location: ' . $redirect);
                    exit();
                } else {
                    $error = $result['message'];
                }
            }
        }
    }

    if ($isAjax && !empty($error)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $error]);
        exit();
    }
}

$csrfToken = Auth::generateCsrfToken();

View::renderBlank('auth/login', [
    'error'          => $error,
    'returnUrl'      => $returnUrl,
    'csrfToken'      => $csrfToken,
    'isMobileDevice' => $isMobileDevice
], 'Login - Curtiss POS');
