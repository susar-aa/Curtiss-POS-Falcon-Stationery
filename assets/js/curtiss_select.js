/**
 * Curtiss POS - Searchable Select Component
 * Reusable real-time search combobox dropdown with Arrow Key & Enter Navigation
 * Global Portal Menu (z-index 9999999) attached directly to document.body
 */

window.CurtissSearchableSelect = class CurtissSearchableSelect {
    constructor(selectEl, options = {}) {
        this.selectEl = selectEl;
        this.options = options;
        this.highlightedIndex = -1;
        this._scrollHandler = null;
        this.init();
    }

    init() {
        if (!this.selectEl) return;
        this.selectEl.style.display = 'none';

        this.wrapper = document.createElement('div');
        this.wrapper.className = 'curtiss-searchable-select';

        this.trigger = document.createElement('div');
        this.trigger.className = 'c-select-trigger';
        this.trigger.tabIndex = 0;

        this.selectedText = document.createElement('span');
        this.selectedText.className = 'c-select-text';

        const arrowIcon = document.createElement('span');
        arrowIcon.className = 'c-select-arrow';
        arrowIcon.innerHTML = `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>`;

        // Copy inline styles & classes if present on selectEl (e.g., pill buttons in top-right header)
        if (this.selectEl.style.borderRadius) this.trigger.style.borderRadius = this.selectEl.style.borderRadius;
        if (this.selectEl.style.height) {
            this.trigger.style.height = this.selectEl.style.height;
            this.trigger.style.minHeight = this.selectEl.style.height;
        }
        if (this.selectEl.style.fontWeight) this.trigger.style.fontWeight = this.selectEl.style.fontWeight;
        if (this.selectEl.style.fontSize) this.trigger.style.fontSize = this.selectEl.style.fontSize;
        if (this.selectEl.style.background) this.trigger.style.background = this.selectEl.style.background;
        if (this.selectEl.style.minWidth) this.wrapper.style.minWidth = this.selectEl.style.minWidth;
        if (this.selectEl.style.border) this.trigger.style.border = this.selectEl.style.border;

        this.trigger.appendChild(this.selectedText);
        this.trigger.appendChild(arrowIcon);

        this.menu = document.createElement('div');
        this.menu.className = 'c-select-menu';

        this.searchContainer = document.createElement('div');
        this.searchContainer.className = 'c-select-search-box';

        this.searchInput = document.createElement('input');
        this.searchInput.type = 'text';
        this.searchInput.className = 'c-select-search-input';
        this.searchInput.placeholder = this.options.searchPlaceholder || 'Type to search...';

        const searchIcon = document.createElement('span');
        searchIcon.className = 'c-select-search-icon';
        searchIcon.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>`;

        this.searchContainer.appendChild(searchIcon);
        this.searchContainer.appendChild(this.searchInput);

        this.optionsList = document.createElement('div');
        this.optionsList.className = 'c-select-options-list';

        this.menu.appendChild(this.searchContainer);
        this.menu.appendChild(this.optionsList);

        this.wrapper.appendChild(this.trigger);

        if (this.selectEl.nextSibling) {
            this.selectEl.parentNode.insertBefore(this.wrapper, this.selectEl.nextSibling);
        } else {
            this.selectEl.parentNode.appendChild(this.wrapper);
        }

        // Attach menu to body level so it is NEVER clipped by card stacking contexts
        document.body.appendChild(this.menu);

        // Trigger Click
        this.trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleMenu();
        });

        // Realtime Filter Input
        this.searchInput.addEventListener('input', () => {
            this.filterOptions(this.searchInput.value);
        });

        this.searchInput.addEventListener('click', (e) => {
            e.stopPropagation();
        });

        // Keydown Handler for Keyboard Navigation (Arrow Keys, Enter, Escape)
        const handleKeydown = (e) => {
            if (!this.menu.classList.contains('show')) {
                if (e.key === 'ArrowDown' || e.key === 'ArrowUp' || e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.toggleMenu();
                }
                return;
            }

            const visibleItems = Array.from(this.optionsList.querySelectorAll('.c-select-option:not(.disabled)')).filter(el => el.style.display !== 'none');

            if (visibleItems.length === 0) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (this.highlightedIndex < 0) {
                    this.highlightedIndex = 0;
                } else {
                    this.highlightedIndex = (this.highlightedIndex + 1) % visibleItems.length;
                }
                this.updateHighlight(visibleItems);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (this.highlightedIndex < 0) {
                    this.highlightedIndex = visibleItems.length - 1;
                } else {
                    this.highlightedIndex = (this.highlightedIndex - 1 + visibleItems.length) % visibleItems.length;
                }
                this.updateHighlight(visibleItems);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (visibleItems.length > 0) {
                    const targetItem = (this.highlightedIndex >= 0 && this.highlightedIndex < visibleItems.length) 
                        ? visibleItems[this.highlightedIndex] 
                        : visibleItems[0];

                    if (targetItem.classList.contains('c-select-create-option')) {
                        const createType = this.getCreateType();
                        const createName = targetItem.getAttribute('data-create-name');
                        this.handleCreateItem(createType, createName, targetItem);
                    } else {
                        const val = targetItem.getAttribute('data-value');
                        this.selectOption(val, true);
                    }
                }
            } else if (e.key === 'Escape') {
                e.preventDefault();
                this.closeMenu();
                this.trigger.focus();
            }
        };

        this.searchInput.addEventListener('keydown', handleKeydown);
        this.trigger.addEventListener('keydown', handleKeydown);

        // Global Click Outside
        this._docClickHandler = (e) => {
            if (!this.wrapper || !this.wrapper.isConnected) return;
            if (!this.wrapper.contains(e.target) && !this.menu.contains(e.target)) {
                this.closeMenu();
            }
        };
        document.addEventListener('click', this._docClickHandler);

        this.refreshOptions();

        this.selectEl.addEventListener('change', () => {
            this.updateSelectedDisplay();
        });
    }

    getCreateType() {
        return this.options.createType || (this.selectEl ? (this.selectEl.dataset.createType || this.selectEl.getAttribute('data-create-type')) : null) || null;
    }

    escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    positionMenu() {
        if (!this.trigger || !this.menu) return;
        const rect = this.trigger.getBoundingClientRect();
        const menuWidth = Math.max(260, rect.width);
        let leftPos = rect.left;
        if (leftPos + menuWidth > window.innerWidth - 12) {
            leftPos = Math.max(12, rect.right - menuWidth);
        }
        this.menu.style.position = 'fixed';
        this.menu.style.top = (rect.bottom + 4) + 'px';
        this.menu.style.left = leftPos + 'px';
        this.menu.style.width = menuWidth + 'px';
        this.menu.style.zIndex = '9999999';
    }

    refreshOptions() {
        this.optionsList.innerHTML = '';
        const opts = Array.from(this.selectEl.options);

        if (opts.length === 0) {
            this.optionsList.innerHTML = `<div class="c-select-empty">No options available</div>`;
            return;
        }

        opts.forEach((opt) => {
            const item = document.createElement('div');
            item.className = 'c-select-option';
            item.setAttribute('data-value', opt.value);
            if (opt.selected) item.classList.add('selected');
            if (opt.disabled) {
                item.classList.add('disabled');
                item.style.opacity = '0.45';
                item.style.pointerEvents = 'none';
                item.style.cursor = 'not-allowed';
            }
            if (!opt.value && opt.value !== 0 && opt.value !== '0') item.classList.add('placeholder-opt');

            item.innerHTML = `
                <span>${opt.text}</span>
                ${opt.selected ? `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>` : ''}
            `;

            if (!opt.disabled) {
                item.addEventListener('mouseenter', () => {
                    const visibleItems = Array.from(this.optionsList.querySelectorAll('.c-select-option:not(.disabled)')).filter(el => el.style.display !== 'none');
                    this.highlightedIndex = visibleItems.indexOf(item);
                    this.updateHighlight(visibleItems);
                });

                item.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.selectOption(opt.value, true);
                });
            }

            this.optionsList.appendChild(item);
        });

        this.updateSelectedDisplay();
    }

    selectOption(val, advanceFocus = false) {
        this.selectEl.value = val;
        this.selectEl.dispatchEvent(new Event('change', { bubbles: true }));
        this.refreshOptions();
        this.closeMenu();
        if (advanceFocus) {
            this.focusNextField();
        }
    }

    focusNextField() {
        const form = this.selectEl.closest('form');
        if (!form) return;

        const allElements = Array.from(form.querySelectorAll(
            'input, textarea, select, button[type="submit"]'
        ));

        const focusables = allElements.filter(el => {
            if (el.type === 'hidden' || el.disabled || el.readOnly) return false;
            if (el.closest('.c-select-menu') || el.classList.contains('c-select-search-input')) return false;
            if (el.tagName === 'SELECT' && el._curtissSelect) {
                return true;
            }
            const style = window.getComputedStyle(el);
            if (style.display === 'none' || style.visibility === 'hidden') return false;
            return true;
        });

        const currentIndex = focusables.indexOf(this.selectEl);
        if (currentIndex >= 0 && currentIndex < focusables.length - 1) {
            const nextEl = focusables[currentIndex + 1];

            if (nextEl._curtissSelect) {
                setTimeout(() => {
                    nextEl._curtissSelect.toggleMenu();
                }, 60);
            } else {
                setTimeout(() => {
                    nextEl.focus();
                    if (nextEl.id === 'name' || nextEl.getAttribute('data-cursor-end') === 'true') {
                        if (nextEl.value && !nextEl.value.endsWith(' ')) {
                            nextEl.value = nextEl.value.trim() + ' ';
                        }
                        const len = nextEl.value ? nextEl.value.length : 0;
                        if (typeof nextEl.setSelectionRange === 'function') {
                            nextEl.setSelectionRange(len, len);
                        }
                    } else if (typeof nextEl.select === 'function' && nextEl.type !== 'button' && nextEl.type !== 'submit') {
                        nextEl.select();
                    }
                }, 60);
            }
        }
    }

    updateSelectedDisplay() {
        const selectedOpt = this.selectEl.options[this.selectEl.selectedIndex];
        if (selectedOpt && selectedOpt.value) {
            this.selectedText.textContent = selectedOpt.text;
            this.selectedText.classList.remove('is-placeholder');
        } else {
            this.selectedText.textContent = (selectedOpt ? selectedOpt.text : '') || this.options.placeholder || '-- Select --';
            this.selectedText.classList.add('is-placeholder');
        }

        if (this.selectEl.disabled) {
            this.trigger.classList.add('disabled');
            this.trigger.style.pointerEvents = 'none';
            this.trigger.style.opacity = '0.6';
            this.trigger.style.background = '#f8fafc';
            this.trigger.style.cursor = 'not-allowed';
        } else {
            this.trigger.classList.remove('disabled');
            this.trigger.style.pointerEvents = '';
            this.trigger.style.opacity = '';
            this.trigger.style.background = '';
            this.trigger.style.cursor = '';
        }
    }

    filterOptions(query) {
        const q = (query || '').trim();
        const qLower = q.toLowerCase();
        const items = this.optionsList.querySelectorAll('.c-select-option:not(.c-select-create-option)');
        let count = 0;
        let exactMatch = false;

        items.forEach(item => {
            const text = item.textContent.toLowerCase();
            if (text === qLower) {
                exactMatch = true;
            }
            if (!qLower || text.includes(qLower)) {
                item.style.display = '';
                count++;
            } else {
                item.style.display = 'none';
            }
        });

        const createType = this.getCreateType();

        let emptyMsg = this.optionsList.querySelector('.c-select-no-result');
        if (count === 0 && !createType) {
            if (!emptyMsg) {
                emptyMsg = document.createElement('div');
                emptyMsg.className = 'c-select-no-result';
                emptyMsg.textContent = 'No matching results found';
                this.optionsList.appendChild(emptyMsg);
            }
            emptyMsg.style.display = 'block';
        } else if (emptyMsg) {
            emptyMsg.style.display = 'none';
        }

        // Handle inline "Create New" option
        let createOpt = this.optionsList.querySelector('.c-select-create-option');

        if (createType && q.length > 0 && !exactMatch) {
            if (!createOpt) {
                createOpt = document.createElement('div');
                createOpt.className = 'c-select-option c-select-create-option';
                this.optionsList.appendChild(createOpt);
            }
            createOpt.style.display = '';
            createOpt.setAttribute('data-create-name', q);
            createOpt.innerHTML = `
                <span style="display: flex; align-items: center; gap: 6px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Create "${this.escapeHtml(q)}"
                </span>
            `;

            createOpt.onclick = (e) => {
                e.stopPropagation();
                this.handleCreateItem(createType, q, createOpt);
            };

            createOpt.onmouseenter = () => {
                const visibleItems = Array.from(this.optionsList.querySelectorAll('.c-select-option')).filter(el => el.style.display !== 'none');
                this.highlightedIndex = visibleItems.indexOf(createOpt);
                this.updateHighlight(visibleItems);
            };
        } else if (createOpt) {
            createOpt.style.display = 'none';
        }

        const visibleItems = Array.from(this.optionsList.querySelectorAll('.c-select-option')).filter(el => el.style.display !== 'none');
        this.highlightedIndex = visibleItems.length > 0 ? 0 : -1;
        this.updateHighlight(visibleItems);
    }

    handleCreateItem(type, name, itemEl) {
        if (!name || !type || this._isCreating) return;
        this._isCreating = true;

        if (itemEl) {
            itemEl.innerHTML = `<span>Creating "${this.escapeHtml(name)}"...</span>`;
        }

        fetch('api/quick_create.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ type: type, name: name })
        })
        .then(r => r.json())
        .then(data => {
            this._isCreating = false;
            if (data.success) {
                this.addItem(data.id, data.name, true);
            } else {
                alert(data.message || ('Error creating ' + type));
                this.filterOptions(this.searchInput.value);
            }
        })
        .catch(err => {
            this._isCreating = false;
            alert('Network error while creating ' + type);
            this.filterOptions(this.searchInput.value);
        });
    }

    updateHighlight(visibleItems) {
        this.optionsList.querySelectorAll('.c-select-option').forEach(el => el.classList.remove('highlighted'));
        if (this.highlightedIndex >= 0 && this.highlightedIndex < visibleItems.length) {
            const activeEl = visibleItems[this.highlightedIndex];
            activeEl.classList.add('highlighted');
            activeEl.scrollIntoView({ block: 'nearest' });
        }
    }

    toggleMenu() {
        if (this.selectEl && this.selectEl.disabled) return;
        if (this.menu.classList.contains('show')) {
            this.closeMenu();
        } else {
            document.querySelectorAll('.c-select-menu.show').forEach(m => m.classList.remove('show'));
            this.positionMenu();
            this.menu.classList.add('show');
            this.trigger.classList.add('active');
            this.searchInput.value = '';
            this.filterOptions('');

            if (!this._scrollHandler) {
                this._scrollHandler = () => {
                    if (this.menu.classList.contains('show')) {
                        this.positionMenu();
                    }
                };
                window.addEventListener('scroll', this._scrollHandler, true);
                window.addEventListener('resize', this._scrollHandler);
            }

            setTimeout(() => this.searchInput.focus(), 50);
        }
    }

    closeMenu() {
        this.menu.classList.remove('show');
        this.trigger.classList.remove('active');
        this.highlightedIndex = -1;
        this.optionsList.querySelectorAll('.c-select-option').forEach(el => el.classList.remove('highlighted'));

        if (this._scrollHandler) {
            window.removeEventListener('scroll', this._scrollHandler, true);
            window.removeEventListener('resize', this._scrollHandler);
            this._scrollHandler = null;
        }
    }

    addItem(val, name, selectIt = true) {
        const opt = document.createElement('option');
        opt.value = val;
        opt.text = name;
        opt.setAttribute('data-name', name);
        this.selectEl.appendChild(opt);
        this.refreshOptions();
        if (selectIt) {
            this.selectOption(val, true);
        }
    }

    destroy() {
        this.closeMenu();
        if (this._docClickHandler) {
            document.removeEventListener('click', this._docClickHandler);
            this._docClickHandler = null;
        }
        if (this.menu && this.menu.parentNode) {
            this.menu.parentNode.removeChild(this.menu);
        }
        if (this.wrapper && this.wrapper.parentNode) {
            this.wrapper.parentNode.removeChild(this.wrapper);
        }
        if (this.selectEl) {
            this.selectEl.style.display = '';
            delete this.selectEl._curtissSelect;
        }
    }

    static attach(selectEl, options = {}) {
        if (!selectEl) return null;
        if (selectEl._curtissSelect) return selectEl._curtissSelect;
        const instance = new CurtissSearchableSelect(selectEl, options);
        selectEl._curtissSelect = instance;
        return instance;
    }
};

// Global Form Keyboard Navigator (Rule 15 & Rule 22)
document.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.shiftKey && !e.defaultPrevented) {
        const target = e.target;
        if (!target) return;

        if (target.tagName === 'TEXTAREA' || target.type === 'submit' || target.type === 'button') return;
        if (target.classList.contains('c-select-search-input') || target.classList.contains('c-select-trigger')) return;

        const form = target.closest('form');
        if (!form) return;

        const allElements = Array.from(form.querySelectorAll(
            'input, textarea, select, button[type="submit"]'
        ));

        const focusables = allElements.filter(el => {
            if (el.type === 'hidden' || el.disabled || el.readOnly) return false;
            if (el.closest('.c-select-menu') || el.classList.contains('c-select-search-input')) return false;
            if (el.tagName === 'SELECT' && el._curtissSelect) {
                return true;
            }
            const style = window.getComputedStyle(el);
            if (style.display === 'none' || style.visibility === 'hidden') return false;
            return true;
        });

        const currentIndex = focusables.indexOf(target);
        if (currentIndex >= 0 && currentIndex < focusables.length - 1) {
            e.preventDefault();
            const nextEl = focusables[currentIndex + 1];

            if (nextEl._curtissSelect) {
                setTimeout(() => nextEl._curtissSelect.toggleMenu(), 60);
            } else {
                setTimeout(() => {
                    nextEl.focus();
                    if (typeof nextEl.select === 'function' && nextEl.type !== 'button' && nextEl.type !== 'submit') {
                        nextEl.select();
                    }
                }, 60);
            }
        }
    }
});
