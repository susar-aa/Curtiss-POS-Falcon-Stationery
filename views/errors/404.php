<?php
/**
 * Curtiss POS - 404 Page Not Found Error View
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 Page Not Found - Curtiss POS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .error-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 2.5rem;
            max-width: 480px;
            width: 90%;
            text-align: center;
            box-shadow: 0 10px 30px -5px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
        }
        .error-code {
            font-size: 4rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1;
            margin-bottom: 0.5rem;
        }
        .error-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #334155;
            margin-bottom: 0.75rem;
        }
        .error-desc {
            font-size: 0.88rem;
            color: #64748b;
            margin-bottom: 1.5rem;
        }
        .btn-home {
            display: inline-block;
            background: #0f172a;
            color: #ffffff;
            padding: 10px 24px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 0.85rem;
            text-decoration: none;
            transition: background 0.15s ease;
        }
        .btn-home:hover {
            background: #1e293b;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-code">404</div>
        <div class="error-title">Page Not Found</div>
        <div class="error-desc">The page or resource you are looking for does not exist or has been moved.</div>
        <a href="dashboard" class="btn-home">Return to Dashboard</a>
    </div>
</body>
</html>
