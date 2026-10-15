<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>500 Internal Server Error - Curtiss POS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <style>
        body {
            background-color: var(--color-bg, #f8fafc);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: var(--color-text-main, #1e293b);
            padding: 2rem 1rem;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            box-sizing: border-box;
        }
        .error-card {
            background: #ffffff;
            width: 100%;
            max-width: 960px;
            border-radius: var(--radius-lg, 12px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            border: 1px solid #e2e8f0;
        }
        .error-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            border-bottom: 2px solid #fee2e2;
            padding-bottom: 1.25rem;
            margin-bottom: 1.5rem;
        }
        .error-badge {
            background: #ef4444;
            color: #ffffff;
            font-size: 1.8rem;
            font-weight: 800;
            padding: 0.5rem 1.2rem;
            border-radius: var(--radius-md, 8px);
        }
        .error-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #991b1b;
            margin: 0;
        }
        .error-section-title {
            font-size: 1rem;
            font-weight: 700;
            color: #334155;
            margin-top: 1.5rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .code-box {
            background: #0f172a;
            color: #f8fafc;
            padding: 1.25rem;
            border-radius: var(--radius-md, 8px);
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 0.85rem;
            line-height: 1.5;
            overflow-x: auto;
            max-height: 350px;
            white-space: pre-wrap;
            word-break: break-all;
            border: 1px solid #334155;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
        }
        .meta-table th, .meta-table td {
            padding: 0.6rem 0.8rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.9rem;
        }
        .meta-table th {
            width: 160px;
            background: #f1f5f9;
            color: #475569;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-header">
            <div class="error-badge">500</div>
            <div>
                <h1 class="error-title">System Error Occurred</h1>
                <p style="margin: 0.25rem 0 0 0; color: #64748b; font-size: 0.9rem;">An unhandled system exception or runtime error occurred. Detailed error logs are presented below for developer diagnostics.</p>
            </div>
        </div>

        <?php
        $exception = $GLOBALS['last_exception'] ?? null;
        $lastError = error_get_last();
        $logPath = defined('LOG_PATH') ? LOG_PATH : __DIR__ . '/../../storage/logs/app.log';
        ?>

        <?php if ($exception): ?>
            <div class="error-section-title">🔴 Unhandled Exception Details</div>
            <table class="meta-table">
                <tr>
                    <th>Exception Class</th>
                    <td><code><?= htmlspecialchars(get_class($exception)) ?></code></td>
                </tr>
                <tr>
                    <th>Message</th>
                    <td><strong style="color: #dc2626;"><?= htmlspecialchars($exception->getMessage()) ?></strong></td>
                </tr>
                <tr>
                    <th>File</th>
                    <td><code><?= htmlspecialchars($exception->getFile()) ?>:<?= $exception->getLine() ?></code></td>
                </tr>
            </table>

            <div class="error-section-title">📌 Stack Trace</div>
            <div class="code-box"><?= htmlspecialchars($exception->getTraceAsString()) ?></div>
        <?php elseif ($lastError): ?>
            <div class="error-section-title">⚠️ Last Fatal Error</div>
            <table class="meta-table">
                <tr>
                    <th>Message</th>
                    <td><strong style="color: #dc2626;"><?= htmlspecialchars($lastError['message']) ?></strong></td>
                </tr>
                <tr>
                    <th>File & Line</th>
                    <td><code><?= htmlspecialchars($lastError['file']) ?>:<?= $lastError['line'] ?></code></td>
                </tr>
                <tr>
                    <th>Type</th>
                    <td><code>Type <?= $lastError['type'] ?></code></td>
                </tr>
            </table>
        <?php endif; ?>

        <!-- Recent Log File Snippet -->
        <div class="error-section-title">📄 Recent System Log Snippet (<?= htmlspecialchars(basename($logPath)) ?>)</div>
        <div class="code-box"><?php
            $logSnippet = "";
            if (file_exists($logPath) && is_readable($logPath)) {
                $lines = file($logPath);
                $slice = array_slice($lines, -40);
                $logSnippet = implode("", $slice);
                echo htmlspecialchars($logSnippet);
            } else {
                $logSnippet = "Log file not accessible or empty: " . $logPath;
                echo htmlspecialchars($logSnippet);
            }
        ?></div>

        <?php
        // Prepare plain text copy payload
        $copyPayload = "";
        if ($exception) {
            $copyPayload .= "Unhandled Exception Details\n";
            $copyPayload .= "Exception Class\t" . get_class($exception) . "\n";
            $copyPayload .= "Message\t" . $exception->getMessage() . "\n";
            $copyPayload .= "File\t" . $exception->getFile() . ":" . $exception->getLine() . "\n\n";
            $copyPayload .= "📌 Stack Trace\n" . $exception->getTraceAsString() . "\n\n";
        } elseif ($lastError) {
            $copyPayload .= "Last Fatal Error\n";
            $copyPayload .= "Message\t" . $lastError['message'] . "\n";
            $copyPayload .= "File & Line\t" . $lastError['file'] . ":" . $lastError['line'] . "\n";
            $copyPayload .= "Type\tType " . $lastError['type'] . "\n\n";
        }
        $copyPayload .= "📄 Recent System Log Snippet (" . basename($logPath) . ")\n" . $logSnippet;
        ?>

        <textarea id="rawCopyText" style="display: none;"><?= htmlspecialchars($copyPayload) ?></textarea>

        <div style="margin-top: 1.75rem; display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
            <a href="dashboard.php" class="btn btn-primary">Return to System Dashboard</a>
            <button onclick="window.location.reload()" class="btn btn-secondary">Retry Request ↻</button>
            <button id="btnCopyError" onclick="copyErrorDetails()" class="btn btn-secondary" style="border-color: #cbd5e1; font-weight: 600;">
                📋 Copy Error Details
            </button>
        </div>
    </div>

    <script>
    function copyErrorDetails() {
        const textarea = document.getElementById('rawCopyText');
        const text = textarea.value;
        const btn = document.getElementById('btnCopyError');

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(() => {
                showCopySuccess(btn);
            }).catch(err => {
                fallbackCopyText(textarea, btn);
            });
        } else {
            fallbackCopyText(textarea, btn);
        }
    }

    function fallbackCopyText(textarea, btn) {
        textarea.style.display = 'block';
        textarea.select();
        try {
            document.execCommand('copy');
            showCopySuccess(btn);
        } catch (err) {
            alert('Failed to copy error details automatically.');
        }
        textarea.style.display = 'none';
    }

    function showCopySuccess(btn) {
        const origText = btn.innerHTML;
        btn.innerHTML = '✔ Copied to Clipboard!';
        btn.style.background = '#10b981';
        btn.style.color = '#ffffff';
        setTimeout(() => {
            btn.innerHTML = origText;
            btn.style.background = '';
            btn.style.color = '';
        }, 2500);
    }
    </script>
</body>
</html>
