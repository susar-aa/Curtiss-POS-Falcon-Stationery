/**
 * CurtissPOS - Progressive Web App (PWA) Integration
 * Registers Service Worker, handles install prompt and standalone detection.
 */

(function () {
    'use strict';

    let deferredPrompt = null;
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

    // Service Worker Registration
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('sw.js', { scope: './' })
                .then((registration) => {
                    // Check for SW updates
                    registration.onupdatefound = () => {
                        const installingWorker = registration.installing;
                        if (installingWorker) {
                            installingWorker.onstatechange = () => {
                                if (installingWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                    console.log('[CurtissPOS] New version available. Refresh to update.');
                                }
                            };
                        }
                    };
                })
                .catch((err) => {
                    console.warn('[CurtissPOS] Service Worker registration failed:', err);
                });
        });
    }

    // Capture install prompt
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        showInstallButton();
    });

    window.addEventListener('appinstalled', () => {
        deferredPrompt = null;
        hideInstallButton();
        console.log('[CurtissPOS] App was installed successfully!');
    });

    function isDashboardPage() {
        return Boolean(
            (document.body && document.body.classList.contains('dashboard-page')) ||
            /\/(dashboard|dashboard\.php)$/i.test(window.location.pathname.replace(/\/+$/, ''))
        );
    }

    function showInstallButton() {
        if (isStandalone || !isDashboardPage()) return;
        
        if (!document.body) {
            document.addEventListener('DOMContentLoaded', showInstallButton, { once: true });
            return;
        }

        let btn = document.getElementById('curtiss-pwa-install-btn');
        if (!btn) {
            btn = document.createElement('button');
            btn.id = 'curtiss-pwa-install-btn';
            btn.type = 'button';
            btn.className = 'curtiss-pwa-btn curtiss-pwa-floating';
            btn.innerHTML = `
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
                <span>Install App</span>
            `;
            btn.setAttribute('title', 'Install CurtissPOS Desktop / Mobile App');
            btn.onclick = installApp;
            document.body.appendChild(btn);
        }
        btn.style.display = 'inline-flex';
    }

    function hideInstallButton() {
        const btn = document.getElementById('curtiss-pwa-install-btn');
        if (btn) btn.style.display = 'none';
    }

    window.addEventListener('DOMContentLoaded', () => {
        if (deferredPrompt && isDashboardPage()) {
            showInstallButton();
        }
    });

    function installApp() {
        if (!deferredPrompt) {
            alert('To install CurtissPOS, click the install icon in your browser address bar (top right).');
            return;
        }
        deferredPrompt.prompt();
        deferredPrompt.userChoice.then((choiceResult) => {
            if (choiceResult.outcome === 'accepted') {
                console.log('[CurtissPOS] User accepted the install prompt');
            }
            deferredPrompt = null;
            hideInstallButton();
        });
    }

    // Expose global controller
    window.CurtissPWA = {
        install: installApp,
        isInstalled: () => isStandalone
    };
})();
