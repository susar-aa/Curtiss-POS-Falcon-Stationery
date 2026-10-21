<?php
$isEmbedded = !empty($_GET['embedded']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= View::escape($pageTitle ?? 'Curtiss POS') ?></title>
    <?php
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $posDir = preg_replace('/\/[^\/]+\.php$/', '', $scriptName);
    if (!str_ends_with($posDir, '/')) {
        $posDir .= '/';
    }
    $baseUrl = $protocol . $host . $posDir;
    ?>
    <base href="<?= htmlspecialchars($baseUrl) ?>">
    <!-- CurtissPOS Progressive Web App (PWA) -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#0f172a">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="CurtissPOS">
    <meta name="application-name" content="CurtissPOS">
    <link rel="apple-touch-icon" href="assets/icons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/icons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/icons/favicon-16x16.png">
    <link rel="shortcut icon" href="assets/icons/favicon-32x32.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/nav.css">
    <link rel="stylesheet" href="assets/css/curtiss_select.css">
    <?php if (!empty($GLOBALS['__dashboardPage']) || basename($_SERVER['SCRIPT_NAME'] ?? '') === 'dashboard.php'): ?>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <?php endif; ?>
    <script src="assets/js/pwa.js" defer></script>
    <?php if (!$isEmbedded) include APP_ROOT . '/includes/nav_shell.php'; ?>
</head>
<body class="<?= (!empty($GLOBALS['__dashboardPage']) || basename($_SERVER['SCRIPT_NAME'] ?? '') === 'dashboard.php') ? 'dashboard-page' : '' ?>" <?= $isEmbedded ? 'style="margin:0; padding:0; background:#f8fafc; overflow:hidden;"' : '' ?>>
    <div class="app-container" <?= $isEmbedded ? 'style="display:flex; flex-direction:column; width:100%; height:100vh; overflow:hidden; margin:0; padding:0;"' : '' ?>>
        <?php if (!$isEmbedded): ?>
            <?php include APP_ROOT . '/includes/sidebar.php'; ?>
        <?php endif; ?>

        <div class="app-main" <?= $isEmbedded ? 'style="display:flex; flex-direction:column; flex:1; width:100%; height:100vh; overflow:hidden; margin:0; padding:0;"' : '' ?>>
            <?php if (!$isEmbedded): ?>
                <?php include APP_ROOT . '/includes/header.php'; ?>
            <?php endif; ?>

            <main class="app-content" <?= $isEmbedded ? 'style="padding:10px 16px; flex:1; min-height:0; overflow-y:auto;"' : '' ?>>
                <?= $content ?? '' ?>
            </main>
        </div>
    </div>

    <?php if (!$isEmbedded): ?>
        <script src="assets/js/nav.js"></script>
        <?php include APP_ROOT . '/includes/footer.php'; ?>
    <?php endif; ?>
