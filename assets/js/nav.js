/**
 * Curtiss POS - Navigation Redesign JavaScript
 * Floating Dock + Windows 11-inspired Curtiss Menu
 *
 * Features:
 * - Floating bottom dock (Home, POS, Search, Notifications)
 * - Curtiss logo bubble → opens full menu panel
 * - Two-panel category/module navigation (permission-aware via PHP)
 * - Unsplash wallpaper (1-hour cache via PHP API)
 * - Widgets: Profile, Clock, Business Day, Quick Actions, Recent, Calculator
 * - Alt+X toggle, ESC close
 * - CSRF protection on all POST requests
 */

(function () {
    'use strict';

    // ─── Helpers ─────────────────────────────────────────────────────────────

    const CSRF = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

    async function api(url, opts = {}) {
        const headers = { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF() };
        const res = await fetch(url, { ...opts, headers: { ...headers, ...(opts.headers || {}) } });
        return res.json();
    }

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function el(id) { return document.getElementById(id); }

    // ─── State ───────────────────────────────────────────────────────────────

    let menuOpen   = false;
    let notifOpen  = false;
    let activeCategory = 0;
    let notifPoller = null;
    let calcBuffer = '';
    let calcPrevOp = null;
    let calcPrevVal = null;
    let calcReset  = false;

    // Populated from PHP via window.CURTISS_NAV (injected in nav_shell.php)
    const NAV = window.CURTISS_NAV || { categories: [], currentScript: '', user: {} };

    // ─── Build the DOM ───────────────────────────────────────────────────────

    function buildShell() {
        // ── Overlay
        const overlay = document.createElement('div');
        overlay.id = 'curtiss-menu-overlay';
        overlay.setAttribute('aria-hidden', 'true');
        overlay.addEventListener('click', closeMenu);
        document.body.appendChild(overlay);

        // ── Main Menu Panel
        const menu = document.createElement('div');
        menu.id = 'curtiss-menu';
        menu.setAttribute('role', 'dialog');
        menu.setAttribute('aria-modal', 'true');
        menu.setAttribute('aria-label', 'Curtiss Navigation Menu');
        menu.innerHTML = buildMenuHTML();
        document.body.appendChild(menu);

        // ── Notifications Panel
        const notifPanel = document.createElement('div');
        notifPanel.id = 'curtiss-notif-panel';
        notifPanel.innerHTML = buildNotifHTML();
        document.body.appendChild(notifPanel);

        // ── Dock
        const dock = document.createElement('div');
        dock.id = 'curtiss-dock';
        dock.setAttribute('role', 'navigation');
        dock.setAttribute('aria-label', 'Main Navigation Dock');
        dock.innerHTML = buildDockHTML();
        document.body.appendChild(dock);

        bindEvents();
        initClock();
        loadActiveState();
        loadWallpaper();
        loadRecent();
        renderWidgetProfile();
        setupMenuSearch();
        startNotifPoller();

        if (pendingPageSearchConfig) {
            enablePageSearch(pendingPageSearchConfig);
        }
    }

    // ─── HTML Builders ────────────────────────────────────────────────────────

    function buildDockHTML() {
        return `
        <button id="dock-logo-bubble" aria-label="Open Curtiss Menu" aria-expanded="false" aria-haspopup="dialog" data-tooltip="Curtiss Menu (Alt+X)">
            <img src="assets/Curtiss-bg-removed.png" alt="Curtiss" loading="lazy" onerror="this.style.display='none';this.parentElement.textContent='C';">
        </button>
        <div class="dock-separator"></div>

        <!-- Page-Specific Search Bubble Icon -->
        <button id="dock-search-bubble" class="dock-circle-bubble" aria-label="Toggle Page Search" data-tooltip="Search" style="display:none;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        </button>

        <div id="dock-bar" role="toolbar">
            <div id="dock-default-items" class="dock-items-wrapper">
                <a href="dashboard" class="dock-item ${['dashboard','dashboard.php','index','index.php'].includes(NAV.currentScript) ? 'active' : ''}" data-tooltip="Home" aria-label="Home">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    <span class="dock-item-label">Home</span>
                </a>
                ${NAV.hasPOS ? `<a href="pos" class="dock-item ${['pos','pos.php'].includes(NAV.currentScript) ? 'active' : ''}" data-tooltip="POS Terminal" aria-label="POS">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M6 8h12M6 12h8M6 16h4"/></svg>
                    <span class="dock-item-label">POS</span>
                </a>` : ''}
                <div class="dock-divider"></div>
                <button class="dock-item" id="dock-notif-btn" aria-label="Notifications" data-tooltip="Notifications">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    <span class="dock-item-label">Alerts</span>
                    <span class="dock-badge" id="dock-notif-badge" style="display:none">0</span>
                </button>
            </div>

            <!-- Realtime Page Search Input Container -->
            <div id="dock-search-container" class="dock-search-wrapper" style="display:none;">
                <svg class="dock-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" id="dock-search-input" class="dock-search-input" placeholder="Search..." autocomplete="off">
                <button id="dock-search-close" class="dock-search-close-btn" type="button" aria-label="Close search">&times;</button>
            </div>
        </div>
        `;
    }

    function buildMenuHTML() {
        return `
        <div id="menu-wallpaper"></div>
        <div id="menu-wallpaper-overlay"></div>
        <div id="menu-content">
            <div id="menu-topbar">
                <div id="menu-topbar-left">
                    <img src="assets/Curtiss-bg-removed.png" alt="" aria-hidden="true" onerror="this.style.display='none'">
                    <span id="menu-topbar-title">Curtiss</span>
                </div>
                <div id="menu-topbar-right">
                    <span id="menu-shortcut-hint" style="font-size:10px;color:#9ca3af;font-weight:500;">Alt+X</span>
                    <button class="menu-close-btn" id="menu-close-btn" aria-label="Close menu">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
            </div>
            <div id="menu-body">
                <nav id="menu-categories" aria-label="ERP Categories" role="list"></nav>
                <div id="menu-modules" role="region" aria-live="polite">
                    <div id="menu-search-wrapper">
                        <svg class="menu-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                        <input type="text" id="menu-search-input" placeholder="Search modules & pages..." aria-label="Search modules" autocomplete="off">
                        <button id="menu-search-clear" aria-label="Clear search" style="display:none">&times;</button>
                    </div>
                    <div id="menu-modules-title"></div>
                    <div id="menu-modules-grid"></div>
                </div>
                <aside id="menu-widgets" aria-label="Widgets">
                    ${buildWidgetsHTML()}
                </aside>
            </div>
            <!-- Shortcut Picker Modal -->
            <div id="shortcut-picker-modal" role="dialog" aria-modal="true" aria-label="Customize Home Shortcuts">
                <div class="shortcut-picker-header">
                    <div>
                        <div class="shortcut-picker-title">Customize Home Shortcuts</div>
                        <div class="shortcut-picker-subtitle">Select options to feature on your Home tab</div>
                    </div>
                    <button class="menu-close-btn" id="shortcut-picker-close-btn" aria-label="Close modal">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <div class="shortcut-picker-body" id="shortcut-picker-list"></div>
            </div>
        </div>
        `;
    }

    function buildNotifHTML() {
        return `
        <div class="notif-panel-header">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #2563eb;"></div>
                <span class="notif-panel-title">System Alerts</span>
            </div>
            <div style="display: flex; align-items: center; gap: 6px;">
                <button class="notif-refresh-btn" id="notif-refresh-btn" title="Refresh alerts" aria-label="Refresh alerts">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
                </button>
                <button class="menu-close-btn" id="notif-close-btn" aria-label="Close notifications">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
        </div>
        <div class="notif-list" id="notif-list" role="list">
            <p class="notif-empty">Loading notifications...</p>
        </div>
        `;
    }

    function buildWidgetsHTML() {
        const user = NAV.user;
        const initials = (user.full_name || user.username || 'U').split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase();

        return `
        <!-- Profile & Logout -->
        <div class="widget-card widget-card-profile">
            <div id="widget-profile">
                <div class="profile-avatar">${escHtml(initials)}</div>
                <div class="profile-info">
                    <div class="profile-name">${escHtml(user.full_name || user.username || 'User')}</div>
                    <div class="profile-meta">${escHtml(user.role_name || '')}</div>
                    <div class="profile-branch-tag" id="profile-branch-tag">
                        <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-3"/></svg>
                        ${escHtml(user.active_branch_name || 'Branch')}
                    </div>
                </div>
            </div>
            <a href="logout" class="widget-logout-btn" data-tooltip="Sign Out" aria-label="Sign Out">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                <span>Logout</span>
            </a>
        </div>

        <!-- Clock + Business Day -->
        <div class="widget-card">
            <div id="widget-clock">
                <div class="clock-time" id="clock-time">00:00:00</div>
                <div class="clock-date" id="clock-date">Loading...</div>
            </div>
        </div>



        <!-- Quick Actions -->
        ${NAV.quickActions && NAV.quickActions.length > 0 ? `
        <div class="widget-card">
            <div class="widget-title">Quick Actions</div>
            <div id="widget-quick-actions-grid">
                ${NAV.quickActions.map(qa => `
                <a href="${escHtml(qa.url)}" class="quick-action-btn" aria-label="${escHtml(qa.label)}">
                    ${qa.icon}
                    <span>${escHtml(qa.label)}</span>
                </a>`).join('')}
            </div>
        </div>
        ` : ''}

        <!-- Recently Opened -->
        <div class="widget-card">
            <div class="widget-title">
                Recent
                <button class="widget-title-action" id="clear-recent-btn" aria-label="Clear recent">Clear</button>
            </div>
            <div id="recent-list"></div>
        </div>



        <!-- Calculator -->
        <div class="widget-card">
            <div class="widget-title">
                Calculator
                <button class="widget-title-action" id="calc-toggle-btn">Show</button>
            </div>
            <div id="widget-calc">
                <div class="calc-display" id="calc-display">0</div>
                <div class="calc-grid" id="calc-grid">
                    ${buildCalcButtons()}
                </div>
            </div>
        </div>
        `;
    }

    function buildCalcButtons() {
        const btns = [
            ['C','clear','clear'], ['±','sign','op'], ['%','percent','op'], ['÷','div','op'],
            ['7','7','num'], ['8','8','num'], ['9','9','num'], ['×','mul','op'],
            ['4','4','num'], ['5','5','num'], ['6','6','num'], ['−','sub','op'],
            ['1','1','num'], ['2','2','num'], ['3','3','num'], ['+','add','op'],
            ['0','0','num span2'], ['.','dot','num'], ['=','eq','equal'],
        ];
        return btns.map(([lbl, val, cls]) =>
            `<button class="calc-btn ${cls.replace('num ', '').replace('span2','span2')}" data-calc="${val}" ${cls.includes('span2') ? 'style="grid-column:span 2"' : ''}>${escHtml(lbl)}</button>`
        ).join('');
    }

    // ─── Category / Module rendering ─────────────────────────────────────────

    function renderCategories() {
        const container = el('menu-categories');
        if (!container) return;
        container.innerHTML = '';

        NAV.categories.forEach((cat, idx) => {
            const btn = document.createElement('button');
            btn.className = 'menu-category-item' + (idx === activeCategory ? ' active' : '');
            btn.setAttribute('role', 'listitem');
            btn.setAttribute('aria-selected', idx === activeCategory ? 'true' : 'false');
            btn.setAttribute('data-category-idx', idx);
            btn.innerHTML = `${cat.icon}<span>${escHtml(cat.label)}</span>`;
            btn.addEventListener('click', () => {
                activeCategory = idx;
                renderCategories();
                renderModules();
                sessionStorage.setItem('curtiss_cat', idx);
            });
            btn.addEventListener('keydown', e => {
                if (e.key === 'ArrowDown') { e.preventDefault(); container.children[Math.min(idx + 1, container.children.length - 1)]?.focus(); }
                if (e.key === 'ArrowUp')   { e.preventDefault(); container.children[Math.max(idx - 1, 0)]?.focus(); }
            });
            container.appendChild(btn);
        });
    }

    // ─── Custom Shortcuts State & Helpers ─────────────────────────────────────

    const SHORTCUTS_KEY = `curtiss_shortcuts_v2_${NAV.user.id || 0}`;

    function getAllAvailableModules() {
        const list = [];
        const seen = new Set();
        (NAV.categories || []).forEach(cat => {
            (cat.modules || []).forEach(mod => {
                if (!seen.has(mod.url)) {
                    seen.add(mod.url);
                    list.push({ ...mod, categoryLabel: cat.label });
                }
            });
        });
        return list;
    }

    function getUserShortcuts() {
        try {
            const saved = localStorage.getItem(SHORTCUTS_KEY);
            if (saved !== null) {
                return JSON.parse(saved);
            }
        } catch (e) {}

        const defaults = ['pos', 'inventory', 'products', 'customer_create', 'purchase_orders', 'customer_payments', 'cash_drawer', 'reports'];
        const all = getAllAvailableModules();
        const availableUrls = new Set(all.map(m => m.url));
        const initial = defaults.filter(url => availableUrls.has(url));
        localStorage.setItem(SHORTCUTS_KEY, JSON.stringify(initial));
        return initial;
    }

    function setUserShortcuts(arr) {
        try {
            localStorage.setItem(SHORTCUTS_KEY, JSON.stringify(arr));
        } catch (e) {}
    }

    function isShortcut(url) {
        return getUserShortcuts().includes(url);
    }

    function toggleShortcut(url, e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        let current = getUserShortcuts();
        if (current.includes(url)) {
            current = current.filter(u => u !== url);
        } else {
            current.push(url);
        }
        setUserShortcuts(current);

        if (el('shortcut-picker-modal')?.classList.contains('visible')) {
            renderShortcutPicker();
        } else {
            const searchInput = el('menu-search-input');
            if (searchInput && searchInput.value.trim()) {
                performRealtimeSearch(searchInput.value.trim());
            } else {
                renderModules();
            }
        }
    }

    function openShortcutPicker() {
        const picker = el('shortcut-picker-modal');
        if (!picker) return;
        renderShortcutPicker();
        picker.classList.add('visible');
    }

    function closeShortcutPicker() {
        const picker = el('shortcut-picker-modal');
        if (picker) picker.classList.remove('visible');
        renderModules();
    }

    function renderShortcutPicker() {
        const container = el('shortcut-picker-list');
        if (!container) return;
        container.innerHTML = '';

        const shortcuts = getUserShortcuts();

        NAV.categories.forEach(cat => {
            if (!cat.modules || cat.modules.length === 0) return;
            const section = document.createElement('div');
            section.className = 'shortcut-picker-section';
            section.innerHTML = `<div class="shortcut-picker-cat-title">${escHtml(cat.label)}</div>`;

            const grid = document.createElement('div');
            grid.className = 'shortcut-picker-grid';

            cat.modules.forEach(mod => {
                const pinned = shortcuts.includes(mod.url);
                const item = document.createElement('div');
                item.className = 'shortcut-picker-item' + (pinned ? ' pinned' : '');
                item.innerHTML = `
                    <div class="shortcut-picker-icon">${mod.icon}</div>
                    <div class="shortcut-picker-info">
                        <div class="shortcut-picker-name">${escHtml(mod.label)}</div>
                        ${mod.desc ? `<div class="shortcut-picker-desc">${escHtml(mod.desc)}</div>` : ''}
                    </div>
                    <button class="shortcut-pin-toggle" type="button" aria-label="${pinned ? 'Unpin' : 'Pin'} ${escHtml(mod.label)}">
                        ${pinned ? '✓ Pinned' : '+ Add'}
                    </button>
                `;
                item.addEventListener('click', (e) => {
                    toggleShortcut(mod.url, e);
                });
                grid.appendChild(item);
            });

            section.appendChild(grid);
            container.appendChild(section);
        });
    }

    function createModuleCard(mod, extraInfo = '') {
        const isActive = mod.scripts && mod.scripts.includes(NAV.currentScript);
        const card = document.createElement('a');
        card.href = mod.url;
        card.className = 'menu-module-card' + (isActive ? ' active-page' : '');
        card.setAttribute('aria-label', mod.label);

        card.innerHTML = `
            <div class="menu-module-icon">${mod.icon}</div>
            <div>
                <div class="menu-module-name">${escHtml(mod.label)}</div>
                ${mod.desc ? `<div class="menu-module-desc">${escHtml(mod.desc)}</div>` : ''}
                ${extraInfo ? `<div class="menu-module-category-tag">${escHtml(extraInfo)}</div>` : ''}
            </div>
        `;

        return card;
    }

    function renderModules() {
        const titleEl = el('menu-modules-title');
        const gridEl  = el('menu-modules-grid');
        if (!titleEl || !gridEl) return;

        const searchInput = el('menu-search-input');
        if (searchInput && searchInput.value.trim()) {
            performRealtimeSearch(searchInput.value.trim());
            return;
        }

        gridEl.innerHTML = '';

        if (activeCategory === 0) {
            titleEl.textContent = 'Home & Shortcuts';

            const shortcuts = getUserShortcuts();
            const allModules = getAllAvailableModules();
            const moduleMap = new Map(allModules.map(m => [m.url, m]));
            const renderedUrls = new Set();

            shortcuts.forEach(url => {
                const mod = moduleMap.get(url);
                if (mod) {
                    renderedUrls.add(url);
                    gridEl.appendChild(createModuleCard(mod));
                }
            });

            if (!renderedUrls.has('dashboard') && !renderedUrls.has('dashboard.php') && (moduleMap.has('dashboard') || moduleMap.has('dashboard.php'))) {
                const dashMod = moduleMap.get('dashboard') || moduleMap.get('dashboard.php');
                gridEl.insertBefore(createModuleCard(dashMod), gridEl.firstChild);
            }

            const addCard = document.createElement('div');
            addCard.className = 'menu-module-card add-shortcut-card';
            addCard.setAttribute('role', 'button');
            addCard.setAttribute('aria-label', 'Add Custom Shortcut');
            addCard.innerHTML = `
                <div class="menu-module-icon add-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </div>
                <div>
                    <div class="menu-module-name">+ Add Shortcut</div>
                    <div class="menu-module-desc">Customize Home options</div>
                </div>
            `;
            addCard.addEventListener('click', openShortcutPicker);
            gridEl.appendChild(addCard);

            return;
        }

        const cat = NAV.categories[activeCategory];
        if (!cat) return;

        titleEl.textContent = cat.label;

        if (!cat.modules || cat.modules.length === 0) {
            gridEl.innerHTML = '<p style="color:#9ca3af;font-size:12px;padding:8px 4px;">No modules available.</p>';
            return;
        }

        cat.modules.forEach(mod => {
            gridEl.appendChild(createModuleCard(mod));
        });
    }

    // ─── Menu open/close ─────────────────────────────────────────────────────

    function openMenu() {
        menuOpen = true;
        el('curtiss-menu')?.classList.add('visible');
        el('curtiss-menu-overlay')?.classList.add('visible');
        el('dock-logo-bubble')?.classList.add('menu-open');
        el('dock-logo-bubble')?.setAttribute('aria-expanded', 'true');
        closeNotif();
        renderCategories();
        renderModules();
        loadWallpaper();
        // Focus first category
        setTimeout(() => el('menu-categories')?.querySelector('.menu-category-item')?.focus(), 100);
    }

    function closeMenu() {
        menuOpen = false;
        el('curtiss-menu')?.classList.remove('visible');
        el('curtiss-menu-overlay')?.classList.remove('visible');
        el('dock-logo-bubble')?.classList.remove('menu-open');
        el('dock-logo-bubble')?.setAttribute('aria-expanded', 'false');
        el('dock-logo-bubble')?.focus();
    }

    function toggleMenu() {
        menuOpen ? closeMenu() : openMenu();
    }

    function openNotif() {
        notifOpen = true;
        el('curtiss-notif-panel')?.classList.add('visible');
        loadNotifications();
        closeMenu();
    }

    function closeNotif() {
        notifOpen = false;
        el('curtiss-notif-panel')?.classList.remove('visible');
    }

    function toggleNotif() {
        notifOpen ? closeNotif() : openNotif();
    }

    // ─── Active state detection ───────────────────────────────────────────────

    function loadActiveState() {
        // Restore last selected category or auto-detect from current script
        const saved = sessionStorage.getItem('curtiss_cat');
        if (saved !== null) {
            activeCategory = parseInt(saved, 10) || 0;
            return;
        }
        // Auto-detect: find which category contains the current script
        NAV.categories.forEach((cat, idx) => {
            cat.modules?.forEach(mod => {
                if (mod.scripts?.includes(NAV.currentScript)) {
                    activeCategory = idx;
                }
            });
        });
    }

    // ─── Wallpaper ────────────────────────────────────────────────────────────

    let wallpaperLoaded = false;

    async function loadWallpaper() {
        if (wallpaperLoaded) return;
        try {
            const data = await fetch('nav_wallpaper_api.php').then(r => r.json());
            if (data.ok && data.image_url) {
                const wallEl = el('menu-wallpaper');
                if (!wallEl) return;
                const img = new Image();
                img.onload = () => {
                    wallEl.style.backgroundImage = `url('${data.image_url}')`;
                    wallEl.classList.add('loaded');
                    wallpaperLoaded = true;
                };
                img.src = data.image_url;
            }
        } catch (e) {
            // Graceful fallback - menu renders fine without wallpaper
        }
    }

    // ─── Clock ────────────────────────────────────────────────────────────────

    function initClock() {
        function tick() {
            const now  = new Date();
            const timeEl = el('clock-time');
            const dateEl = el('clock-date');
            if (!timeEl || !dateEl) return;
            const hh = String(now.getHours()).padStart(2, '0');
            const mm = String(now.getMinutes()).padStart(2, '0');
            const ss = String(now.getSeconds()).padStart(2, '0');
            timeEl.textContent = `${hh}:${mm}:${ss}`;
            const days   = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
            const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            dateEl.textContent = `${days[now.getDay()]}, ${months[now.getMonth()]} ${now.getDate()}, ${now.getFullYear()}`;
        }
        tick();
        setInterval(tick, 1000);
    }



    // ─── Profile Widget ───────────────────────────────────────────────────────

    function renderWidgetProfile() {
        // Already rendered inline from NAV.user in buildWidgetsHTML
        // Nothing dynamic to update here
    }

    // ─── Recently Opened ──────────────────────────────────────────────────────

    const RECENT_KEY = 'curtiss_recent_v1';
    const MAX_RECENT = 6;

    function trackCurrentPage() {
        if (!NAV.currentPageLabel || !NAV.currentScript) return;
        const recent = getRecent();
        const entry  = { label: NAV.currentPageLabel, url: NAV.currentScript, icon: NAV.currentPageIcon || '' };
        const filtered = recent.filter(r => r.url !== entry.url);
        filtered.unshift(entry);
        sessionStorage.setItem(RECENT_KEY, JSON.stringify(filtered.slice(0, MAX_RECENT)));
    }

    function getRecent() {
        try { return JSON.parse(sessionStorage.getItem(RECENT_KEY) || '[]'); } catch { return []; }
    }

    function loadRecent() {
        const listEl = el('recent-list');
        if (!listEl) return;
        trackCurrentPage();
        const recent = getRecent();

        if (recent.length === 0) {
            listEl.innerHTML = '<p style="color:#9ca3af;font-size:11px;">No recent pages.</p>';
            return;
        }

        listEl.innerHTML = recent.map(r => `
            <a href="${escHtml(r.url)}" class="recent-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <span>${escHtml(r.label)}</span>
            </a>
        `).join('');
    }



    // ─── Notifications ────────────────────────────────────────────────────────

    async function loadNotifications() {
        const listEl = el('notif-list');
        if (!listEl) return;

        try {
            const data = await fetch('nav_notifications_api.php').then(r => r.json());
            if (!data.ok || !data.notifications || data.notifications.length === 0) {
                listEl.innerHTML = `
                    <div class="notif-empty-state">
                        <div class="notif-empty-icon">✓</div>
                        <div class="notif-empty-title">All Caught Up</div>
                        <p class="notif-empty-text">No active alerts or urgent items for this branch.</p>
                    </div>
                `;
                updateNotifBadge(0);
                return;
            }
            updateNotifBadge(data.unread_count || 0);
            listEl.innerHTML = data.notifications.map(n => `
                <a href="${escHtml(n.url || '#')}" class="notif-item notif-${escHtml(n.type || 'info')} ${n.unread ? 'unread' : ''}" role="listitem">
                    <div class="notif-icon-bubble notif-bubble-${escHtml(n.type || 'info')}">
                        <span>${escHtml(n.icon || '🔔')}</span>
                    </div>
                    <div class="notif-body">
                        <div class="notif-top-row">
                            <span class="notif-title">${escHtml(n.title || 'System Alert')}</span>
                            ${n.badge ? `<span class="notif-badge-pill notif-badge-${escHtml(n.type || 'info')}">${escHtml(n.badge)}</span>` : ''}
                        </div>
                        <div class="notif-text">${escHtml(n.text || '')}</div>
                        <div class="notif-meta-row">
                            <span class="notif-time">${escHtml(n.time_label || '')}</span>
                            <span class="notif-action-hint">View report &rarr;</span>
                        </div>
                    </div>
                </a>
            `).join('');

            // Close notification panel when user clicks an alert card to navigate
            listEl.querySelectorAll('.notif-item').forEach(item => {
                item.addEventListener('click', () => {
                    closeNotif();
                });
            });
        } catch (e) {
            listEl.innerHTML = '<p class="notif-empty">Could not load notifications.</p>';
        }
    }

    function startNotifPoller() {
        loadNotifications();
        notifPoller = setInterval(loadNotifications, 60000);
    }

    function updateNotifBadge(count) {
        const badge = el('dock-notif-badge');
        if (!badge) return;
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    }

    // ─── Realtime Menu Search ──────────────────────────────────────────────────

    function setupMenuSearch() {
        const input = el('menu-search-input');
        const clearBtn = el('menu-search-clear');
        if (!input) return;

        input.addEventListener('input', () => {
            const query = input.value.trim();
            if (clearBtn) clearBtn.style.display = query ? 'block' : 'none';
            if (query) {
                performRealtimeSearch(query);
            } else {
                renderModules();
            }
        });

        clearBtn?.addEventListener('click', () => {
            input.value = '';
            clearBtn.style.display = 'none';
            renderModules();
            input.focus();
        });

        input.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                input.value = '';
                clearBtn.style.display = 'none';
                renderModules();
            }
        });
    }

    function performRealtimeSearch(query) {
        const titleEl = el('menu-modules-title');
        const gridEl  = el('menu-modules-grid');
        if (!titleEl || !gridEl) return;

        const q = query.toLowerCase();
        const all = getAllAvailableModules();
        const matches = all.filter(m =>
            m.label.toLowerCase().includes(q) ||
            (m.desc && m.desc.toLowerCase().includes(q)) ||
            (m.categoryLabel && m.categoryLabel.toLowerCase().includes(q))
        );

        titleEl.textContent = `Search Results (${matches.length})`;
        gridEl.innerHTML = '';

        if (matches.length === 0) {
            gridEl.innerHTML = `<div class="menu-search-no-results">No modules matching "<strong>${escHtml(query)}</strong>"</div>`;
            return;
        }

        matches.forEach(mod => {
            gridEl.appendChild(createModuleCard(mod, mod.categoryLabel));
        });
    }



    // ─── Calculator ───────────────────────────────────────────────────────────

    function initCalculator() {
        const display = el('calc-display');
        const grid    = el('calc-grid');
        const toggle  = el('calc-toggle-btn');
        const widget  = el('widget-calc');

        if (!grid || !display) return;

        toggle?.addEventListener('click', () => {
            const showing = widget.classList.toggle('show');
            toggle.textContent = showing ? 'Hide' : 'Show';
        });

        grid.addEventListener('click', e => {
            const btn = e.target.closest('.calc-btn');
            if (!btn) return;
            const val = btn.dataset.calc;

            if (val === 'clear') { calcBuffer = ''; calcPrevOp = null; calcPrevVal = null; calcReset = false; display.textContent = '0'; return; }
            if (val === 'sign')  { calcBuffer = calcBuffer.startsWith('-') ? calcBuffer.slice(1) : '-' + calcBuffer; display.textContent = calcBuffer || '0'; return; }
            if (val === 'percent') { const n = parseFloat(calcBuffer || '0'); calcBuffer = String(n / 100); display.textContent = calcBuffer; return; }
            if (['add','sub','mul','div'].includes(val)) {
                if (calcBuffer !== '') {
                    if (calcPrevOp && calcPrevVal !== null) {
                        const res = doCalc(parseFloat(calcPrevVal), parseFloat(calcBuffer), calcPrevOp);
                        calcBuffer = String(res);
                        display.textContent = calcBuffer;
                    }
                    calcPrevVal = calcBuffer;
                    calcBuffer = '';
                }
                calcPrevOp = val;
                calcReset  = false;
                return;
            }
            if (val === 'eq') {
                if (calcPrevOp && calcPrevVal !== null && calcBuffer !== '') {
                    const res = doCalc(parseFloat(calcPrevVal), parseFloat(calcBuffer), calcPrevOp);
                    display.textContent = parseFloat(res.toFixed(8));
                    calcBuffer = String(res);
                    calcPrevOp = null;
                    calcPrevVal = null;
                }
                return;
            }
            if (val === 'dot') {
                if (!calcBuffer.includes('.')) calcBuffer += calcBuffer ? '.' : '0.';
                display.textContent = calcBuffer;
                return;
            }
            if (calcBuffer.length < 15) {
                calcBuffer += val;
                display.textContent = calcBuffer;
            }
        });
    }

    function doCalc(a, b, op) {
        switch (op) {
            case 'add': return a + b;
            case 'sub': return a - b;
            case 'mul': return a * b;
            case 'div': return b !== 0 ? a / b : 0;
        }
        return b;
    }

    // ─── Realtime Page Search Dock Module ────────────────────────────────────

    let pageSearchCallback      = null;
    let pageSearchActive        = false;
    let pendingPageSearchConfig = null;

    function enablePageSearch(options = {}) {
        if (typeof options === 'function') {
            options = { onSearch: options };
        }
        pageSearchCallback = options.onSearch || null;
        pendingPageSearchConfig = options;

        const bubble = el('dock-search-bubble');
        const searchInput = el('dock-search-input');
        if (!bubble) return;

        if (options.placeholder && searchInput) {
            searchInput.placeholder = options.placeholder;
        }

        bubble.style.display = 'flex';
    }

    function disablePageSearch() {
        const bubble = el('dock-search-bubble');
        if (!bubble) return;
        bubble.style.display = 'none';
        if (pageSearchActive) {
            toggleDockSearch(false);
        }
        pageSearchCallback = null;
    }

    function toggleDockSearch(forceState) {
        const bubble          = el('dock-search-bubble');
        const searchContainer = el('dock-search-container');
        const defaultItems    = el('dock-default-items');
        const searchInput     = el('dock-search-input');

        if (!bubble || !searchContainer || !defaultItems) return;

        pageSearchActive = (typeof forceState === 'boolean') ? forceState : !pageSearchActive;

        if (pageSearchActive) {
            bubble.classList.add('active');
            defaultItems.style.display = 'none';
            searchContainer.style.display = 'flex';
            if (searchInput) {
                searchInput.focus();
                if (pageSearchCallback) {
                    pageSearchCallback(searchInput.value.trim());
                }
            }
        } else {
            bubble.classList.remove('active');
            searchContainer.style.display = 'none';
            defaultItems.style.display = 'flex';
            if (searchInput) {
                searchInput.value = '';
            }
            if (pageSearchCallback) {
                pageSearchCallback('');
            }
        }
    }

    // ─── Event Bindings ───────────────────────────────────────────────────────

    function bindEvents() {
        // Logo bubble
        el('dock-logo-bubble')?.addEventListener('click', toggleMenu);

        // Page search bubble
        el('dock-search-bubble')?.addEventListener('click', () => toggleDockSearch());

        // Search input realtime callback
        const searchInput = el('dock-search-input');
        searchInput?.addEventListener('input', () => {
            if (pageSearchCallback) {
                pageSearchCallback(searchInput.value.trim());
            }
        });

        // Search close button inside bottom dock
        el('dock-search-close')?.addEventListener('click', () => {
            toggleDockSearch(false);
        });

        // Close buttons
        el('menu-close-btn')?.addEventListener('click', closeMenu);
        el('notif-close-btn')?.addEventListener('click', closeNotif);
        el('notif-refresh-btn')?.addEventListener('click', (e) => {
            e.stopPropagation();
            loadNotifications();
        });
        el('shortcut-picker-close-btn')?.addEventListener('click', closeShortcutPicker);

        // Notifications button
        el('dock-notif-btn')?.addEventListener('click', toggleNotif);

        // Keyboard: Alt+X, ESC
        document.addEventListener('keydown', e => {
            if (e.altKey && !e.ctrlKey && !e.shiftKey && e.key === 'x') {
                // Don't trigger if in a text input/textarea
                const tag = document.activeElement?.tagName?.toLowerCase();
                if (!['input','textarea','select'].includes(tag)) {
                    e.preventDefault();
                    toggleMenu();
                }
            }
            if (e.key === 'Escape') {
                if (menuOpen) closeMenu();
                if (notifOpen) closeNotif();
                if (pageSearchActive) toggleDockSearch(false);
            }
        });

        // Calculator
        initCalculator();

        // Clear recent
        el('clear-recent-btn')?.addEventListener('click', () => {
            sessionStorage.removeItem(RECENT_KEY);
            loadRecent();
        });

        // Branch Dropdown in Top Header
        initBranchDropdown();

        // Close menu when clicking on a module (navigation happens naturally)
        el('menu-modules-grid')?.addEventListener('click', () => {
            setTimeout(closeMenu, 80);
        });
    }

    function initBranchDropdown() {
        const trigger = el('top-branch-trigger');
        const dropdown = el('top-branch-dropdown');
        const wrapper = el('top-branch-wrapper');
        if (!trigger || !dropdown) return;

        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            const isShown = dropdown.classList.contains('show');
            if (isShown) {
                dropdown.classList.remove('show');
                trigger.classList.remove('active');
            } else {
                dropdown.classList.add('show');
                trigger.classList.add('active');
            }
        });

        document.addEventListener('click', (e) => {
            if (wrapper && !wrapper.contains(e.target)) {
                dropdown.classList.remove('show');
                trigger.classList.remove('active');
            }
        });

        dropdown.querySelectorAll('.branch-dropdown-item').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const branchId = btn.getAttribute('data-branch-id');
                if (!branchId) return;

                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'select_branch.php';

                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'branch_id';
                input.value = branchId;

                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            });
        });
    }

    // ─── Init ────────────────────────────────────────────────────────────────

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', buildShell);
    } else {
        buildShell();
    }

    // Expose CurtissNav API
    window.CurtissNav = { 
        setupContextualSearch: () => {}, 
        openMenu, 
        closeMenu, 
        enablePageSearch, 
        disablePageSearch, 
        togglePageSearch: toggleDockSearch 
    };

})();
