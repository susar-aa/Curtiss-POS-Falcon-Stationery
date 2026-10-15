<?php
/**
 * Curtiss POS - Branch Selector Controller
 */

require_once __DIR__ . '/config/config.php';

Auth::requireAuth();

$user = Auth::user();
$branches = $_SESSION['user_branches'] ?? [];

// If only 1 branch is available, auto-assign and go to target
if (count($branches) <= 1 && !empty($branches)) {
    Auth::switchBranch((int)$branches[0]['id']);
    $_SESSION['branch_selected'] = true;
    $target = $_SESSION['return_url'] ?? 'dashboard';
    unset($_SESSION['return_url']);
    header('Location: ' . $target);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $refererPath = parse_url($referer, PHP_URL_PATH) ?? '';
    $refererScript = basename($refererPath);

    if (empty($referer) || in_array($refererScript, ['login.php', 'login', 'select_branch.php', 'select_branch'], true)) {
        $target = $_SESSION['return_url'] ?? 'dashboard';
    } else {
        $target = $referer;
    }
    unset($_SESSION['return_url']);

    $branchIdRaw = trim((string)($_POST['branch_id'] ?? ''));
    if ($branchIdRaw === 'all') {
        $_SESSION['all_branches_view'] = true;
        $_SESSION['branch_selected'] = true;
        Audit::log('BRANCH_SWITCH', 'auth', 'all', ['from' => $_SESSION['active_branch_name'] ?? 'None'], ['to' => 'All Branches']);
        header('Location: ' . $target);
        exit();
    }

    $branchId = (int)$branchIdRaw;
    if ($branchId > 0 && Auth::switchBranch($branchId)) {
        $_SESSION['all_branches_view'] = false;
        $_SESSION['branch_selected'] = true;
        header('Location: ' . $target);
        exit();
    }
}

View::renderBlank('auth/select_branch', [
    'user' => $user,
    'branches' => $branches,
    'currentBranchId' => (int)($_SESSION['active_branch_id'] ?? 0)
], 'Select Operating Branch - Curtiss POS');
