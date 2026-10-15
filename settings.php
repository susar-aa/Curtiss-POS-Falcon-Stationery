<?php
/**
 * Curtiss POS - System Settings Controller (Stage 17)
 * Enforces Super Admin restriction for Special User masking percentage updates.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Settings.php';
require_once __DIR__ . '/core/Audit.php';
require_once __DIR__ . '/core/View.php';

Auth::requireAuth();

if (!Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_ACCOUNTANT)) {
    View::renderBlank('errors/403');
    exit();
}

$successMsg = null;
$errorMsg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['settings'])) {
    $user = Auth::user();

    // 1. Handle Company Logo Upload
    if (!empty($_POST['remove_logo'])) {
        $oldLogo = Settings::get('company_logo');
        if (!empty($oldLogo) && file_exists(__DIR__ . '/' . $oldLogo)) {
            @unlink(__DIR__ . '/' . $oldLogo);
        }
        Settings::set('company_logo', '', $user['id']);
    } elseif (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['company_logo'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExts = ['png', 'jpg', 'jpeg', 'webp', 'svg'];

        if (!in_array($ext, $allowedExts)) {
            $errorMsg = 'Invalid logo format. Please upload PNG, JPG, WEBP, or SVG.';
        } elseif ($file['size'] > 4 * 1024 * 1024) {
            $errorMsg = 'Logo file size exceeds 4MB limit.';
        } else {
            $uploadDir = __DIR__ . '/uploads';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }

            $fileName = 'company_logo_' . time() . '.' . $ext;
            $targetPath = $uploadDir . '/' . $fileName;

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $oldLogo = Settings::get('company_logo');
                if (!empty($oldLogo) && file_exists(__DIR__ . '/' . $oldLogo)) {
                    @unlink(__DIR__ . '/' . $oldLogo);
                }
                Settings::set('company_logo', 'uploads/' . $fileName, $user['id']);
            } else {
                $errorMsg = 'Failed to save uploaded logo file.';
            }
        }
    }

    // 2. Save text settings
    foreach ($_POST['settings'] as $key => $val) {
        $valTrimmed = trim($val);

        if ($key === 'special_user_display_percentage' || $key === 'special_user_visibility_pct') {
            if (!Auth::hasRole(ROLE_SUPER_ADMIN)) {
                continue; // Strictly restrict percentage change to Super Admin
            }

            $oldVal = Settings::get('special_user_display_percentage', '60.00');
            $newVal = number_format((float)$valTrimmed, 2, '.', '');

            if ($oldVal !== $newVal) {
                Audit::log('SPECIAL_USER_PERCENTAGE_UPDATE', 'settings', null, [
                    'old_percentage' => $oldVal
                ], [
                    'new_percentage' => $newVal,
                    'user_id'        => $user['id'],
                    'timestamp'      => date('Y-m-d H:i:s')
                ]);
            }

            Settings::set('special_user_display_percentage', $newVal, $user['id']);
            Settings::set('special_user_visibility_pct', $newVal, $user['id']);
        } else {
            Settings::set($key, $valTrimmed, $user['id']);
        }
    }

    Audit::log('SETTINGS_UPDATED', 'settings', null, null, $_POST['settings']);
    if (!$errorMsg) {
        $successMsg = 'System settings updated successfully!';
    }
}

View::render('settings/index', [
    'successMsg' => $successMsg,
    'errorMsg'   => $errorMsg
], 'System Configuration - Curtiss POS');
