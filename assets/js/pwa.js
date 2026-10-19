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

    function showInstallButton() {
        if (isStandalone) return;
        
        let btn = document.getElementById('curtiss-pwa-install-btn');
        if (!btn) {
            btn = document.createElement('button');
            btn.id = 'curtiss-pwa-install-btn';
            btn.type = 'button';
            btn.className = 'curtiss-pwa-btn';
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

            // Try mounting into floating header right or append to body
            const headerRight = document.getElementById('curtiss-floating-header-right');
            if (headerRight) {
                headerRight.prepend(btn);
            } else {
                // Fixed top banner style if not on a view with floating right
                btn.classList.add('curtiss-pwa-floating');
                document.body.appendChild(btn);
            }
        }
        btn.style.display = 'inline-flex';
    }

    function hideInstallButton() {
        const btn = document.getElementById('curtiss-pwa-install-btn');
        if (btn) btn.style.display = 'none';
    }

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
