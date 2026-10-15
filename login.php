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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            if (!empty($result['needs_branch_selection'])) {
                header('Location: select_branch');
            } else {
                $target = !empty($returnUrl) ? $returnUrl : 'dashboard';
                unset($_SESSION['return_url']);
                header('Location: ' . $target);
            }
            exit();
        } else {
            $error = $result['message'];
        }
    }
}

View::renderBlank('auth/login', [
    'error' => $error,
    'returnUrl' => $returnUrl
], 'Login - Curtiss POS');
