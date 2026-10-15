/**
 * Curtiss POS - Inactivity Timeout Engine
 * Keeps session synchronized with server and preserves exact return URL on expiration.
 */

(function () {
    // Configurable timeout in seconds (from System Configuration)
    const timeoutSeconds = window.CURTISS_POS_TIMEOUT || 1800;
    const warningBufferSeconds = 60; // Show warning 60 seconds before session expires
    let lastActivityTime = Date.now();
    let lastPingTime = Date.now();
    let warningShown = false;

    // Background heartbeat to keep PHP session in sync with active user interaction
    function pingServer() {
        lastPingTime = Date.now();
        fetch('api/ping.php', { credentials: 'same-origin' })
            .then(res => res.json())
            .then(data => {
                if (data && data.success) {
                    lastActivityTime = Date.now();
                } else if (data && !data.success) {
                    expireSession();
                }
            })
            .catch(() => {});
    }

    function resetTimer() {
        lastActivityTime = Date.now();
        if (warningShown) {
            hideWarningToast();
            warningShown = false;
        }

        // Throttle server heartbeats to at most once per 60 seconds of user activity
        if (Date.now() - lastPingTime > 60000) {
            pingServer();
        }
    }

    function expireSession() {
        const path = window.location.pathname;
        const search = window.location.search;
        const currentUrl = encodeURIComponent(path + search);
        window.location.href = 'login.php?expired=1&return_url=' + currentUrl;
    }

    // Register User Activity Listeners (typing, mouse, click, scroll, touch, form focus)
    ['mousemove', 'keydown', 'click', 'touchstart', 'scroll', 'input', 'focus'].forEach(evt => {
        window.addEventListener(evt, resetTimer, { passive: true });
    });

    // Check Inactivity Loop every 5 seconds
    setInterval(() => {
        const elapsedSeconds = Math.floor((Date.now() - lastActivityTime) / 1000);
        const remainingSeconds = timeoutSeconds - elapsedSeconds;

        if (remainingSeconds <= 0) {
            expireSession();
        } else if (remainingSeconds <= warningBufferSeconds && !warningShown) {
            showWarningToast(remainingSeconds);
            warningShown = true;
        }
    }, 5000);

    function showWarningToast(secondsLeft) {
        let toast = document.getElementById('inactivityToast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'inactivityToast';
            toast.style.cssText = `
                position: fixed;
                bottom: 24px;
                right: 24px;
                background: #0f172a;
                color: #ffffff;
                padding: 1rem 1.25rem;
                border-radius: 12px;
                box-shadow: 0 14px 28px rgba(0,0,0,0.35);
                z-index: 99999;
                font-family: inherit;
                font-size: 0.88rem;
                border: 1px solid #f59e0b;
                display: flex;
                align-items: center;
                gap: 12px;
                max-width: 420px;
                animation: toastSlideUp 0.3s ease-out;
            `;
            document.body.appendChild(toast);
        }
        toast.innerHTML = `
            <div style="font-size: 1.4rem;">⏱️</div>
            <div style="flex: 1;">
                <div style="font-weight: 700; color: #fef08a; margin-bottom: 2px;">Session Expiring Soon</div>
                <div style="font-size: 0.8rem; color: #cbd5e1; line-height: 1.35;">You will be safely redirected to login if inactive.</div>
            </div>
            <button type="button" id="stayLoggedInBtn" style="background: #0284c7; color: #fff; border: none; padding: 6px 14px; border-radius: 8px; font-weight: 700; font-size: 0.82rem; cursor: pointer; white-space: nowrap;">
                Stay Active
            </button>
        `;

        const btn = document.getElementById('stayLoggedInBtn');
        if (btn) {
            btn.onclick = function() {
                pingServer();
                resetTimer();
            };
        }
    }

    function hideWarningToast() {
        const toast = document.getElementById('inactivityToast');
        if (toast) toast.remove();
    }
})();
