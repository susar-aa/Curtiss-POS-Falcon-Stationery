<?php
/**
 * Curtiss POS - Categories Controller (Stage 2 Product Master)
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Category.php';

Auth::requireAuth();

if (!Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER)) {
    View::renderBlank('errors/403');
    exit();
}

$errorMsg = null;
$successMsg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = $_POST['name'] ?? '';
        $status = $_POST['status'] ?? 'active';
        $res = Category::create($name, $status);
        if ($res['success']) {
            $successMsg = $res['message'];
        } else {
            $errorMsg = $res['message'];
        }
    } elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $name = $_POST['name'] ?? '';
        $status = $_POST['status'] ?? 'active';
        $res = Category::update($id, $name, $status);
        if ($res['success']) {
            $successMsg = $res['message'];
        } else {
            $errorMsg = $res['message'];
        }
    } elseif ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        $res = Category::toggleStatus($id);
        if ($res['success']) {
            $successMsg = $res['message'];
        } else {
            $errorMsg = $res['message'];
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $res = Category::delete($id);
        if ($res['success']) {
            $successMsg = $res['message'];
        } else {
            $errorMsg = $res['message'];
        }
    } elseif ($action === 'restore') {
        $id = (int)($_POST['id'] ?? 0);
        $res = Category::restore($id);
        if ($res['success']) {
            $successMsg = $res['message'];
        } else {
            $errorMsg = $res['message'];
        }
    }
}

$viewMode = $_GET['view'] ?? 'active';
$categories = Category::all(false);
$deletedCategories = Category::deleted();

View::render('categories/index', [
    'categories'        => $categories,
    'deletedCategories' => $deletedCategories,
    'viewMode'          => $viewMode,
    'errorMsg'          => $errorMsg,
    'successMsg'        => $successMsg
], 'Categories - Curtiss POS');
