<?php
/**
 * Curtiss POS - Login Controller
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

$error = null;
$csrfToken = Auth::generateCsrfToken();
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
          (isset($_POST['ajax']) && $_POST['ajax'] === '1');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginType = trim($_POST['login_type'] ?? 'password');

    if ($loginType === 'pin') {
        $pin = trim($_POST['pin'] ?? '');
        if (empty($pin)) {
            $error = 'Please enter your 5-digit PIN.';
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => $error]);
                exit();
            }
        } else {
            $result = Auth::attemptPin($pin);
            if ($result['success']) {
                if (!empty($returnUrl)) {
                    $_SESSION['return_url'] = $returnUrl;
                }
                $target = (!empty($result['needs_branch_selection'])) ? 'select_branch' : (!empty($returnUrl) ? $returnUrl : 'dashboard');
                if (empty($result['needs_branch_selection'])) {
                    unset($_SESSION['return_url']);
                }
                if ($isAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => true, 'redirect' => $target]);
                    exit();
                }
                header('Location: ' . $target);
                exit();
            } else {
                $error = $result['message'];
                if ($isAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => false, 'message' => $error]);
                    exit();
                }
            }
        }
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($username) || empty($password)) {
            $error = 'Please enter both username and password.';
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => $error]);
                exit();
            }
        } else {
            $result = Auth::attempt($username, $password);
            if ($result['success']) {
                if (!empty($returnUrl)) {
                    $_SESSION['return_url'] = $returnUrl;
                }
                $target = (!empty($result['needs_branch_selection'])) ? 'select_branch' : (!empty($returnUrl) ? $returnUrl : 'dashboard');
                if (empty($result['needs_branch_selection'])) {
                    unset($_SESSION['return_url']);
                }
                if ($isAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => true, 'redirect' => $target]);
                    exit();
                }
                header('Location: ' . $target);
                exit();
            } else {
                $error = $result['message'];
                if ($isAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => false, 'message' => $error]);
                    exit();
                }
            }
        }
    }
}

View::renderBlank('auth/login', [
    'error' => $error,
    'returnUrl' => $returnUrl,
    'csrfToken' => $csrfToken
], 'Login - Curtiss POS');
