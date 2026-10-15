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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/nav.css">
    <link rel="stylesheet" href="assets/css/curtiss_select.css">
    <?php include APP_ROOT . '/includes/nav_shell.php'; ?>
</head>
<body>
    <div class="app-container">
        <?php include APP_ROOT . '/includes/sidebar.php'; ?>

        <div class="app-main">
            <?php include APP_ROOT . '/includes/header.php'; ?>

            <main class="app-content">
                <?= $content ?? '' ?>
            </main>
        </div>
    </div>

    <script src="assets/js/nav.js"></script>
    <?php include APP_ROOT . '/includes/footer.php'; ?>
