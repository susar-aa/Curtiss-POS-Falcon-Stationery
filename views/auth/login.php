<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Curtiss POS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <style>
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1.5rem;
        }
        .login-card {
            width: 100%;
            max-width: 440px;
            background: #ffffff;
            border-radius: var(--radius-lg);
            padding: 2.5rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--color-border);
        }
        .login-brand {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-brand-icon {
            width: 56px;
            height: 56px;
            background: var(--color-primary);
            color: #ffffff;
            border-radius: var(--radius-lg);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.4);
        }
        .login-brand-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--color-text-main);
            letter-spacing: -0.02em;
        }
        .login-brand-subtitle {
            font-size: var(--font-size-sm);
            color: var(--color-text-secondary);
            margin-top: 0.25rem;
        }
        .demo-credentials {
            margin-top: 2rem;
            padding: 1rem;
            background-color: #f8fafc;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
            font-size: 0.8rem;
            color: var(--color-text-secondary);
        }
        .demo-credentials summary {
            font-weight: 600;
            cursor: pointer;
            color: var(--color-primary);
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-brand">
            <div class="login-brand-icon">C</div>
            <h1 class="login-brand-title">Curtiss POS</h1>
            <p class="login-brand-subtitle">Multi-Branch Retail Management System</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <span><?= View::escape($error) ?></span>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['expired'])): ?>
            <div class="alert alert-warning" style="background: #fffbeb; border: 1px solid #fde68a; color: #b45309; padding: 0.85rem 1rem; border-radius: 8px; margin-bottom: 1.25rem; font-size: 0.88rem; display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 1.2rem;">⏱️</span>
                <span>Your session timed out due to inactivity. Sign in to continue exactly where you left off.</span>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" onsubmit="const b=this.querySelector('button[type=submit]'); if(b){b.disabled=true; b.innerText='Signing In...';}">
            <?php if (!empty($returnUrl)): ?>
                <input type="hidden" name="return_url" value="<?= View::escape($returnUrl) ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="username" class="form-label">Username or Email</label>
                <input type="text" id="username" name="username" class="form-control" placeholder="e.g. superadmin" required autofocus>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem; font-weight: 700;">
                Sign In to Curtiss POS
            </button>
        </form>

        <details class="demo-credentials">
            <summary>🔑 View Demo Login Credentials</summary>
            <div style="margin-top: 0.5rem; line-height: 1.6;">
                <strong>Password for all accounts:</strong> <code>Password@123</code><br><br>
                • <code>superadmin</code> (Super Admin - All Branches)<br>
                • <code>admin</code> (Shop Owner - Multi-Branch)<br>
                • <code>manager1</code> (Kandy Branch Manager)<br>
                • <code>cashier1</code> (Kandy Cashier)<br>
                • <code>accountant</code> (Lead Accountant)<br>
                • <code>specialuser</code> (Auditor / Restricted View)
            </div>
        </details>
    </div>
</body>
</html>
