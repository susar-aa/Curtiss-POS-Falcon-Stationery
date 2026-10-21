<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($pageTitle ?? 'Login - CurtissPOS', ENT_QUOTES, 'UTF-8') ?></title>
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
    <script src="assets/js/pwa.js" defer></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            -webkit-tap-highlight-color: transparent;
        }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #090d16;
            background-image: 
                radial-gradient(at 0% 0%, rgba(37, 99, 235, 0.18) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(16, 185, 129, 0.12) 0px, transparent 50%),
                radial-gradient(at 50% 50%, rgba(15, 23, 42, 0.95) 0px, transparent 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            min-height: 100dvh;
            padding: 1.25rem;
            color: #f8fafc;
        }
        .login-card {
            width: 100%;
            max-width: 440px;
            background: rgba(15, 23, 42, 0.88);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border-radius: 28px;
            padding: 2.25rem 2.25rem;
            box-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.08);
            position: relative;
            overflow: hidden;
            animation: cardFadeUp 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @keyframes cardFadeUp {
            from { opacity: 0; transform: translateY(16px) scale(0.98); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }
        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #2563eb 0%, #38bdf8 50%, #10b981 100%);
        }

        /* ── Brand Header ── */
        .login-brand {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .login-brand-icon {
            width: 54px;
            height: 54px;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #38bdf8;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            font-weight: 900;
            font-family: 'Outfit', sans-serif;
            margin-bottom: 0.65rem;
            box-shadow: 0 10px 25px -5px rgba(37, 99, 235, 0.25);
            transition: transform 0.2s ease;
        }
        .login-brand-title {
            font-family: 'Outfit', sans-serif;
            font-size: 1.6rem;
            font-weight: 900;
            color: #ffffff;
            letter-spacing: -0.025em;
            line-height: 1.2;
        }
        .login-brand-subtitle {
            font-size: 0.78rem;
            font-weight: 700;
            color: #94a3b8;
            margin-top: 0.35rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        /* ── Mode Switcher Segmented Control ── */
        .mode-switcher {
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: rgba(30, 41, 59, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 999px;
            padding: 4px;
            margin-bottom: 1.5rem;
            gap: 4px;
        }
        .mode-btn {
            background: transparent;
            border: none;
            color: #94a3b8;
            padding: 8px 14px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .mode-btn.active {
            background: #ffffff;
            color: #0f172a;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        /* ── Alerts ── */
        .alert-error {
            background: rgba(220, 38, 38, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.35);
            color: #fca5a5;
            padding: 10px 14px;
            border-radius: 12px;
            font-size: 12.5px;
            font-weight: 600;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 8px;
            animation: alertShake 0.35s ease;
        }
        @keyframes alertShake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-6px); }
            40%, 80% { transform: translateX(6px); }
        }

        /* ── PIN Display & Dots ── */
        .pin-display-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .pin-dots-container {
            display: flex;
            gap: 14px;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
        }
        .pin-dot {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            background: rgba(30, 41, 59, 0.75);
            border: 2px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            font-weight: 800;
            color: #38bdf8;
            transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .pin-dot.filled {
            background: rgba(56, 189, 248, 0.15);
            border-color: #38bdf8;
            box-shadow: 0 0 16px rgba(56, 189, 248, 0.25);
            transform: scale(1.05);
        }
        .pin-dot.filled.masked::after {
            content: '';
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #38bdf8;
        }
        .pin-controls-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 11.5px;
            color: #94a3b8;
        }
        .pin-toggle-btn {
            background: none;
            border: none;
            color: #94a3b8;
            font-size: 11.5px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            border-radius: 6px;
            transition: color 0.15s ease;
        }
        .pin-toggle-btn:hover {
            color: #f8fafc;
        }

        /* ── Touchscreen Numeric Keypad ── */
        .pin-keypad {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            max-width: 320px;
            margin: 0 auto;
        }
        .pin-key {
            height: 56px;
            min-height: 56px;
            background: rgba(30, 41, 59, 0.65);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            color: #ffffff;
            font-size: 1.35rem;
            font-weight: 700;
            font-family: 'Outfit', sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.12s cubic-bezier(0.16, 1, 0.3, 1);
            user-select: none;
        }
        .pin-key:hover {
            background: rgba(51, 65, 85, 0.85);
            border-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
        }
        .pin-key:active {
            background: #2563eb;
            border-color: #3b82f6;
            color: #ffffff;
            transform: scale(0.95);
        }
        .pin-key-action {
            background: rgba(15, 23, 42, 0.6);
            color: #94a3b8;
            font-size: 1rem;
            font-weight: 800;
            font-family: 'Inter', sans-serif;
        }
        .pin-key-action:hover {
            color: #f8fafc;
            background: rgba(30, 41, 59, 0.8);
        }
        .pin-key-clear:active {
            background: #dc2626 !important;
            color: #ffffff !important;
        }

        /* ── Username & Password Form ── */
        .form-group {
            margin-bottom: 1.15rem;
            text-align: left;
        }
        .form-label {
            display: block;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: #94a3b8;
            margin-bottom: 6px;
        }
        .form-input {
            width: 100%;
            height: 44px;
            background: rgba(30, 41, 59, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 0 14px;
            color: #ffffff;
            font-size: 13.5px;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .form-input:focus {
            border-color: #38bdf8;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2);
            background: rgba(30, 41, 59, 0.95);
        }
        .btn-submit {
            width: 100%;
            height: 46px;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            border: none;
            border-radius: 12px;
            color: #ffffff;
            font-size: 13.5px;
            font-weight: 800;
            letter-spacing: 0.02em;
            cursor: pointer;
            box-shadow: 0 10px 20px -5px rgba(37, 99, 235, 0.4);
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 1.25rem;
        }
        .btn-submit:hover {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            transform: translateY(-1px);
            box-shadow: 0 12px 24px -5px rgba(37, 99, 235, 0.5);
        }
        .btn-submit:active {
            transform: translateY(0);
        }
        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        /* ── Spinner ── */
        .spinner {
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* ── Footer Info ── */
        .login-footer {
            margin-top: 1.5rem;
            text-align: center;
            font-size: 11px;
            color: #64748b;
            font-weight: 500;
        }

        /* Hidden Keyboard Input */
        #hiddenPinInput {
            position: absolute;
            opacity: 0;
            pointer-events: none;
            width: 1px;
            height: 1px;
            top: -100px;
            left: -100px;
        }

        /* ── Mobile Device Restriction System ── */
        .mobile-block-view {
            display: none;
            text-align: center;
            padding: 0.5rem 0.25rem;
            animation: cardFadeUp 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        /* Screen width <= 768px triggers mobile block */
        @media screen and (max-width: 768px) {
            .login-desktop-content {
                display: none !important;
            }
            .mobile-block-view {
                display: block !important;
            }
            .login-card {
                padding: 2rem 1.5rem;
            }
        }

        /* Server-side or JS flagged mobile device */
        body.is-mobile-device .login-desktop-content {
            display: none !important;
        }
        body.is-mobile-device .mobile-block-view {
            display: block !important;
        }

        .mobile-block-icon-wrap {
            position: relative;
            width: 82px;
            height: 82px;
            margin: 0 auto 1.25rem auto;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.8) 0%, rgba(15, 23, 42, 0.95) 100%);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 24px;
            box-shadow: 0 15px 30px -10px rgba(0, 0, 0, 0.5);
        }
        .mobile-block-glow {
            position: absolute;
            inset: -6px;
            border-radius: 28px;
            background: radial-gradient(circle, rgba(239, 68, 68, 0.22) 0%, transparent 70%);
            z-index: 0;
            pointer-events: none;
        }
        .mobile-monitor-icon {
            position: relative;
            z-index: 1;
            color: #94a3b8;
        }
        .mobile-block-badge {
            position: absolute;
            bottom: -3px;
            right: -3px;
            width: 26px;
            height: 26px;
            background: #0f172a;
            border: 2px solid #ef4444;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2;
            box-shadow: 0 4px 10px rgba(239, 68, 68, 0.4);
        }
        .mobile-pill {
            display: inline-block;
            padding: 3px 12px;
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.35);
            color: #f87171;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 0.75rem;
        }
        .mobile-title {
            font-family: 'Outfit', sans-serif;
            font-size: 1.45rem;
            font-weight: 900;
            color: #ffffff;
            line-height: 1.2;
            margin-bottom: 0.65rem;
        }
        .mobile-desc {
            font-size: 13px;
            color: #94a3b8;
            line-height: 1.55;
            margin-bottom: 1.4rem;
            max-width: 360px;
            margin-left: auto;
            margin-right: auto;
        }
        .mobile-checklist {
            background: rgba(30, 41, 59, 0.55);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 1rem 1.15rem;
            text-align: left;
            margin-bottom: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .mobile-check-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        .mobile-check-item svg {
            flex-shrink: 0;
            margin-top: 2px;
        }
        .mobile-check-text strong {
            display: block;
            font-size: 12.5px;
            color: #f8fafc;
            font-weight: 700;
        }
        .mobile-check-text span {
            display: block;
            font-size: 11px;
            color: #64748b;
            margin-top: 1px;
        }
        .mobile-footer-credits {
            font-size: 11.5px;
            color: #64748b;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .mobile-footer-credits a {
            color: #38bdf8;
            text-decoration: none;
            font-weight: 700;
        }
        .mobile-footer-credits a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 1.75rem 1.25rem;
                border-radius: 20px;
            }
            .pin-dot {
                width: 38px;
                height: 38px;
                border-radius: 10px;
            }
            .pin-key {
                height: 50px;
                min-height: 50px;
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body class="<?= !empty($isMobileDevice) ? 'is-mobile-device' : '' ?>">

<div class="login-card">
    <!-- Desktop Login Content -->
    <div class="login-desktop-content" id="loginDesktopContent">
    <!-- Brand Header -->
    <div class="login-brand">
        <div class="login-brand-icon" style="background: transparent; border: none; box-shadow: none; width: auto; height: auto; margin-bottom: 0.65rem;">
            <img src="assets/icons/icon-192x192.png" alt="CurtissPOS" style="width: 62px; height: 62px; border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4); object-fit: contain;">
        </div>
        <h1 class="login-brand-title">CurtissPOS</h1>
        <p class="login-brand-subtitle">Retail Management & Terminal Access</p>
    </div>

    <!-- Error Message Display -->
    <div id="errorAlert" class="alert-error" style="<?= empty($error) ? 'display: none;' : '' ?>">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <span id="errorText"><?= htmlspecialchars($error ?? '', ENT_QUOTES, 'UTF-8') ?></span>
    </div>

    <!-- Dual Mode Switcher -->
    <div class="mode-switcher">
        <button type="button" class="mode-btn active" id="tabPinBtn" onclick="switchLoginMode('pin')">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                <circle cx="8" cy="8" r="1.5"></circle>
                <circle cx="12" cy="8" r="1.5"></circle>
                <circle cx="16" cy="8" r="1.5"></circle>
                <circle cx="8" cy="12" r="1.5"></circle>
                <circle cx="12" cy="12" r="1.5"></circle>
                <circle cx="16" cy="12" r="1.5"></circle>
                <circle cx="8" cy="16" r="1.5"></circle>
                <circle cx="12" cy="16" r="1.5"></circle>
                <circle cx="16" cy="16" r="1.5"></circle>
            </svg>
            PIN Login
        </button>
        <button type="button" class="mode-btn" id="tabPwdBtn" onclick="switchLoginMode('password')">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
            Password
        </button>
    </div>

    <!-- ── PIN Login Mode ── -->
    <div id="pinLoginSection">
        <input type="tel" id="hiddenPinInput" maxlength="5" autocomplete="off" inputmode="numeric" pattern="[0-9]*">
        
        <div class="pin-display-wrapper">
            <div class="pin-dots-container" id="pinDotsContainer">
                <div class="pin-dot" id="dot0"></div>
                <div class="pin-dot" id="dot1"></div>
                <div class="pin-dot" id="dot2"></div>
                <div class="pin-dot" id="dot3"></div>
                <div class="pin-dot" id="dot4"></div>
            </div>
            
            <div class="pin-controls-bar">
                <span>Enter 5-digit security PIN</span>
                <button type="button" class="pin-toggle-btn" id="togglePinMaskBtn" onclick="togglePinVisibility()">
                    <span id="pinVisibilityIcon">👁</span>
                    <span id="pinVisibilityLabel">Show</span>
                </button>
            </div>
        </div>

        <!-- Touchscreen Numeric Keypad -->
        <div class="pin-keypad">
            <button type="button" class="pin-key" onclick="enterPinDigit('1')">1</button>
            <button type="button" class="pin-key" onclick="enterPinDigit('2')">2</button>
            <button type="button" class="pin-key" onclick="enterPinDigit('3')">3</button>
            <button type="button" class="pin-key" onclick="enterPinDigit('4')">4</button>
            <button type="button" class="pin-key" onclick="enterPinDigit('5')">5</button>
            <button type="button" class="pin-key" onclick="enterPinDigit('6')">6</button>
            <button type="button" class="pin-key" onclick="enterPinDigit('7')">7</button>
            <button type="button" class="pin-key" onclick="enterPinDigit('8')">8</button>
            <button type="button" class="pin-key" onclick="enterPinDigit('9')">9</button>
            <button type="button" class="pin-key pin-key-action pin-key-clear" onclick="clearPin()" title="Clear PIN">CLR</button>
            <button type="button" class="pin-key" onclick="enterPinDigit('0')">0</button>
            <button type="button" class="pin-key pin-key-action" onclick="backspacePin()" title="Backspace">⌫</button>
        </div>
    </div>

    <!-- ── Username & Password Login Mode ── -->
    <div id="pwdLoginSection" style="display: none;">
        <form id="pwdLoginForm" method="POST" action="login.php" onsubmit="handlePasswordSubmit(event)">
            <input type="hidden" name="login_type" value="password">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="return_url" value="<?= htmlspecialchars($returnUrl, ENT_QUOTES, 'UTF-8') ?>">

            <div class="form-group">
                <label class="form-label" for="usernameInput">Username or Email</label>
                <input type="text" name="username" id="usernameInput" class="form-input" placeholder="e.g. admin or cashier1" autocomplete="username" required>
            </div>

            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                    <label class="form-label" for="passwordInput" style="margin-bottom: 0;">Password</label>
                    <button type="button" onclick="togglePasswordVisibility()" style="background:none; border:none; color:#38bdf8; font-size:11px; cursor:pointer; font-weight:700;">Show</button>
                </div>
                <input type="password" name="password" id="passwordInput" class="form-input" placeholder="Enter your password..." autocomplete="current-password" required>
            </div>

            <button type="submit" class="btn-submit" id="pwdSubmitBtn">
                <span id="pwdSubmitText">Sign In</span>
                <span class="spinner" id="pwdSpinner" style="display: none;"></span>
            </button>
        </form>
    </div>

        <!-- Footer Copyright -->
        <div class="login-footer">
            Curtiss POS &copy; <?= date('Y') ?> &bull; Enterprise Point of Sale
        </div>
    </div>
    <!-- /login-desktop-content -->

    <!-- Mobile Restricted Access View -->
    <div class="mobile-block-view" id="mobileBlockView">
        <div class="mobile-block-icon-wrap">
            <div class="mobile-block-glow"></div>
            <svg class="mobile-monitor-icon" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                <line x1="8" y1="21" x2="16" y2="21"></line>
                <line x1="12" y1="17" x2="12" y2="21"></line>
            </svg>
            <div class="mobile-block-badge">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line>
                </svg>
            </div>
        </div>

        <span class="mobile-pill">Desktop Access Only</span>
        <h2 class="mobile-title">Please Use Desktop</h2>
        <p class="mobile-desc">
            Curtiss POS retail terminals and management systems require a desktop computer or laptop workstation. Mobile device access is currently restricted.
        </p>

        <div class="mobile-checklist">
            <div class="mobile-check-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#38bdf8" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="3" width="20" height="14" rx="2"/>
                    <line x1="8" y1="21" x2="16" y2="21"/>
                    <line x1="12" y1="17" x2="12" y2="21"/>
                </svg>
                <div class="mobile-check-text">
                    <strong>Desktop PC or Laptop Required</strong>
                    <span>Optimized for screens 1024×768 and wider</span>
                </div>
            </div>
            <div class="mobile-check-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#38bdf8" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                <div class="mobile-check-text">
                    <strong>Hardware Peripherals Support</strong>
                    <span>Requires physical keyboard, barcode scanner & receipt printer</span>
                </div>
            </div>
        </div>

        <div class="mobile-footer-credits">
            <span>Powered by Curtiss POS</span>
            <span>•</span>
            <a href="https://www.trycurtiss.com" target="_blank" rel="noopener">www.trycurtiss.com</a>
        </div>
    </div>
</div>

<script>
    let currentPin = '';
    let isMasked = true;
    let isSubmitting = false;
    const CSRF_TOKEN = <?= json_encode($csrfToken) ?>;
    const RETURN_URL = <?= json_encode($returnUrl) ?>;

    // Switch between PIN and Password Login
    function switchLoginMode(mode) {
        const pinSection = document.getElementById('pinLoginSection');
        const pwdSection = document.getElementById('pwdLoginSection');
        const tabPinBtn = document.getElementById('tabPinBtn');
        const tabPwdBtn = document.getElementById('tabPwdBtn');
        const errorAlert = document.getElementById('errorAlert');

        errorAlert.style.display = 'none';

        if (mode === 'pin') {
            pinSection.style.display = 'block';
            pwdSection.style.display = 'none';
            tabPinBtn.classList.add('active');
            tabPwdBtn.classList.remove('active');
            focusHiddenInput();
        } else {
            pinSection.style.display = 'none';
            pwdSection.style.display = 'block';
            tabPinBtn.classList.remove('active');
            tabPwdBtn.classList.add('active');
            const userIn = document.getElementById('usernameInput');
            if (userIn) userIn.focus();
        }
    }

    // PIN Display update
    function updatePinDisplay() {
        for (let i = 0; i < 5; i++) {
            const dot = document.getElementById('dot' + i);
            if (i < currentPin.length) {
                dot.classList.add('filled');
                if (isMasked) {
                    dot.classList.add('masked');
                    dot.textContent = '';
                } else {
                    dot.classList.remove('masked');
                    dot.textContent = currentPin[i];
                }
            } else {
                dot.classList.remove('filled', 'masked');
                dot.textContent = '';
            }
        }
    }

    // Toggle Show/Hide PIN
    function togglePinVisibility() {
        isMasked = !isMasked;
        document.getElementById('pinVisibilityLabel').textContent = isMasked ? 'Show' : 'Hide';
        updatePinDisplay();
    }

    // Keypad digit entry
    function enterPinDigit(digit) {
        if (isSubmitting || currentPin.length >= 5 || document.body.classList.contains('is-mobile-device')) return;
        currentPin += digit;
        updatePinDisplay();

        if (currentPin.length === 5) {
            submitPinAuth();
        }
    }

    // Backspace
    function backspacePin() {
        if (isSubmitting || currentPin.length === 0) return;
        currentPin = currentPin.slice(0, -1);
        updatePinDisplay();
    }

    // Clear PIN
    function clearPin() {
        if (isSubmitting) return;
        currentPin = '';
        updatePinDisplay();
        hideError();
    }

    // Submit PIN Authentication via AJAX
    function submitPinAuth() {
        if (isSubmitting || currentPin.length !== 5) return;
        isSubmitting = true;
        hideError();

        // Animated dots glow
        const dots = document.querySelectorAll('.pin-dot');
        dots.forEach(d => d.style.borderColor = '#38bdf8');

        fetch('login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({
                login_type: 'pin',
                pin: currentPin,
                csrf_token: CSRF_TOKEN,
                return_url: RETURN_URL,
                ajax: '1'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Success animation
                dots.forEach(d => {
                    d.style.borderColor = '#10b981';
                    d.style.background = 'rgba(16, 185, 129, 0.2)';
                });
                window.location.href = data.redirect || 'dashboard';
            } else {
                showError(data.message || 'Invalid PIN. Please try again.');
                shakePinDots();
                currentPin = '';
                updatePinDisplay();
                isSubmitting = false;
                focusHiddenInput();
            }
        })
        .catch(err => {
            showError('Authentication failed. Please check network and try again.');
            shakePinDots();
            currentPin = '';
            updatePinDisplay();
            isSubmitting = false;
            focusHiddenInput();
        });
    }

    // Handle Password Form Submission via AJAX
    function handlePasswordSubmit(e) {
        e.preventDefault();
        if (document.body.classList.contains('is-mobile-device')) return;

        const form = document.getElementById('pwdLoginForm');
        const submitBtn = document.getElementById('pwdSubmitBtn');
        const submitText = document.getElementById('pwdSubmitText');
        const spinner = document.getElementById('pwdSpinner');

        submitBtn.disabled = true;
        submitText.style.display = 'none';
        spinner.style.display = 'inline-block';
        hideError();

        const formData = new FormData(form);
        formData.append('ajax', '1');

        fetch('login.php', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.location.href = data.redirect || 'dashboard';
            } else {
                showError(data.message || 'Invalid credentials.');
                submitBtn.disabled = false;
                submitText.style.display = 'inline-block';
                spinner.style.display = 'none';
            }
        })
        .catch(err => {
            showError('Connection error. Please try again.');
            submitBtn.disabled = false;
            submitText.style.display = 'inline-block';
            spinner.style.display = 'none';
        });
    }

    function togglePasswordVisibility() {
        const pwdInput = document.getElementById('passwordInput');
        pwdInput.type = (pwdInput.type === 'password') ? 'text' : 'password';
    }

    function showError(msg) {
        const errorAlert = document.getElementById('errorAlert');
        const errorText = document.getElementById('errorText');
        errorText.textContent = msg;
        errorAlert.style.display = 'flex';
    }

    function hideError() {
        document.getElementById('errorAlert').style.display = 'none';
    }

    function shakePinDots() {
        const container = document.getElementById('pinDotsContainer');
        container.style.animation = 'alertShake 0.35s ease';
        setTimeout(() => container.style.animation = '', 400);
    }

    function focusHiddenInput() {
        if (document.body.classList.contains('is-mobile-device')) return;
        const hiddenIn = document.getElementById('hiddenPinInput');
        if (hiddenIn) {
            hiddenIn.focus();
        }
    }

    // Physical Keyboard Support
    document.addEventListener('keydown', function(e) {
        if (document.body.classList.contains('is-mobile-device')) return;
        const pinSection = document.getElementById('pinLoginSection');
        if (pinSection.style.display === 'none') return;

        if (e.key >= '0' && e.key <= '9') {
            enterPinDigit(e.key);
        } else if (e.key === 'Backspace') {
            backspacePin();
        } else if (e.key === 'Escape' || e.key === 'Delete') {
            clearPin();
        }
    });

    // Mobile Device Detection & Hard Block
    function isMobileEnvironment() {
        const ua = navigator.userAgent || navigator.vendor || window.opera || '';
        const isMobileUA = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini|Mobile/i.test(ua);
        const isSmallWidth = window.innerWidth <= 768;
        const isTouchHandheld = (('ontouchstart' in window) || navigator.maxTouchPoints > 1) && window.innerWidth <= 850;
        return isMobileUA || isSmallWidth || isTouchHandheld;
    }

    function enforceMobileRestriction() {
        const isMobile = isMobileEnvironment() || <?= !empty($isMobileDevice) ? 'true' : 'false' ?>;
        if (isMobile) {
            document.body.classList.add('is-mobile-device');
            const hiddenIn = document.getElementById('hiddenPinInput');
            if (hiddenIn) hiddenIn.disabled = true;
            const pwdInput = document.getElementById('passwordInput');
            if (pwdInput) pwdInput.disabled = true;
            const userInput = document.getElementById('usernameInput');
            if (userInput) userInput.disabled = true;
        } else {
            <?php if (empty($isMobileDevice)): ?>
            document.body.classList.remove('is-mobile-device');
            const hiddenIn = document.getElementById('hiddenPinInput');
            if (hiddenIn) hiddenIn.disabled = false;
            const pwdInput = document.getElementById('passwordInput');
            if (pwdInput) pwdInput.disabled = false;
            const userInput = document.getElementById('usernameInput');
            if (userInput) userInput.disabled = false;
            <?php endif; ?>
        }
    }

    window.addEventListener('resize', enforceMobileRestriction);
    window.addEventListener('orientationchange', enforceMobileRestriction);
    enforceMobileRestriction();

    // Auto-focus on load (Desktop only)
    window.addEventListener('DOMContentLoaded', () => {
        enforceMobileRestriction();
        if (!document.body.classList.contains('is-mobile-device')) {
            focusHiddenInput();
        }
        document.addEventListener('click', (e) => {
            if (document.body.classList.contains('is-mobile-device')) return;
            const pinSection = document.getElementById('pinLoginSection');
            if (pinSection.style.display !== 'none' && !e.target.closest('button') && !e.target.closest('input')) {
                focusHiddenInput();
            }
        });
    });
</script>

</body>
</html>
