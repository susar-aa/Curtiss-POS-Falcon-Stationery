/**
 * Curtiss POS - Real-Time Dashboard Client Controller
 * Manages live real-time fast polling (2.5s), signature verification,
 * reactive state subscriptions, and asynchronous bundle synchronization.
 */

(function (window) {
    'use strict';

    const CurtissDashboard = {
        config: {
            apiEndpoint: 'dashboard_api.php',
            pollIntervalMs: 2500, // 2.5s live polling
            useSSE: false,        // Fast polling is 100% non-blocking & reliable on XAMPP/Apache
            activeBranchId: 'all',
            preset: 'today'
        },

        state: {
            filters: {
                preset: 'today',
                branch_id: 'all',
                from: '',
                to: '',
                category_id: '',
                brand_id: '',
                supplier_id: '',
                user_id: ''
            },
            signature: null,
            analytics: null,
            timeSeries: null,
            operations: null,
            products: null,
            lastPulseTimestamp: null,
            liveCounters: {
                today_invoices: 0,
                today_sales: 0.00
            },
            isConnected: false
        },

        _subscribers: {},
        _eventSource: null,
        _pollTimer: null,
        _isPolling: false,

        /**
         * Initialize dashboard client
         */
        init: function (options) {
            this.config = Object.assign({}, this.config, options || {});
            if (options) {
                if (options.filters) {
                    this.state.filters = Object.assign({}, this.state.filters, options.filters);
                }
                if (options.activeBranchId !== undefined) {
                    this.state.filters.branch_id = options.activeBranchId;
                }
                if (options.preset !== undefined) {
                    this.state.filters.preset = options.preset;
                }
            }

            // Expose globally
            window.CurtissDashboard = this;

            // Start live stream / polling
            this.startRealtime();

            // Refresh immediately when returning to tab
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) {
                    this._pollPulse();
                }
            });

            return this;
        },

        /**
         * Change active preset filter (today, week, month, quarter, year)
         */
        setPreset: function (preset) {
            this.state.filters.preset = preset;
            return this.loadBundle();
        },

        /**
         * Change active branch filter
         */
        setBranch: function (branchId) {
            this.state.filters.branch_id = branchId;
            return this.loadBundle();
        },

        /**
         * Subscribe to events ('update', 'pulse', 'error', 'connected')
         */
        on: function (event, callback) {
            if (!this._subscribers[event]) {
                this._subscribers[event] = [];
            }
            this._subscribers[event].push(callback);
            return this;
        },

        /**
         * Trigger event
         */
        emit: function (event, data) {
            const listeners = this._subscribers[event] || [];
            listeners.forEach(cb => {
                try {
                    cb(data);
                } catch (err) {
                    console.error('[CurtissDashboard] Listener error:', err);
                }
            });

            // Dispatch on BOTH window and document so any listener catches it reliably
            const evtName = 'curtiss:dashboard:' + event;
            const ce = new CustomEvent(evtName, { detail: data, bubbles: true });
            try { window.dispatchEvent(ce); } catch (e) {}
            try { document.dispatchEvent(ce); } catch (e) {}
        },

        /**
         * Build query string from active filters
         */
        _buildQueryString: function (extra) {
            const params = new URLSearchParams();
            const merged = Object.assign({}, this.state.filters, extra || {});

            for (const key in merged) {
                if (merged[key] !== null && merged[key] !== undefined && merged[key] !== '') {
                    params.append(key, merged[key]);
                }
            }
            return params.toString();
        },

        /**
         * Fetch complete dashboard bundle
         */
        loadBundle: async function (extraFilters) {
            if (extraFilters) {
                this.state.filters = Object.assign({}, this.state.filters, extraFilters);
            }

            const qs = this._buildQueryString({ action: 'bundle', _t: Date.now() });
            try {
                const response = await fetch(`${this.config.apiEndpoint}?${qs}`, {
                    cache: 'no-store',
                    headers: { 'Pragma': 'no-cache', 'Cache-Control': 'no-cache' }
                });
                if (!response.ok) throw new Error('HTTP ' + response.status);
                const result = await response.json();

                if (result.success && result.data) {
                    const d = result.data;
                    this.state.analytics   = d.analytics;
                    this.state.timeSeries  = d.time_series;
                    this.state.operations  = d.operations;
                    this.state.products    = d.products;
                    this.state.lastPulseTimestamp = d.timestamp;

                    this.emit('update', { type: 'bundle', data: d });
                    return d;
                }
            } catch (err) {
                console.error('[CurtissDashboard] Failed to load dashboard bundle:', err);
                this.emit('error', err);
            }
            return null;
        },

        /**
         * Fetch sales KPIs only
         */
        loadAnalytics: async function (extraFilters) {
            const qs = this._buildQueryString(Object.assign({ action: 'analytics' }, extraFilters || {}));
            try {
                const response = await fetch(`${this.config.apiEndpoint}?${qs}`);
                const result = await response.json();
                if (result.success) {
                    this.state.analytics = result.data;
                    this.emit('update', { type: 'analytics', data: result.data });
                    return result.data;
                }
            } catch (err) {
                this.emit('error', err);
            }
            return null;
        },

        /**
         * Fetch time-series chart data only
         */
        loadCharts: async function (extraFilters) {
            const qs = this._buildQueryString(Object.assign({ action: 'charts' }, extraFilters || {}));
            try {
                const response = await fetch(`${this.config.apiEndpoint}?${qs}`);
                const result = await response.json();
                if (result.success) {
                    this.state.timeSeries = result.data;
                    this.emit('update', { type: 'charts', data: result.data });
                    return result.data;
                }
            } catch (err) {
                this.emit('error', err);
            }
            return null;
        },

        /**
         * Start real-time connection
         */
        startRealtime: function () {
            this.stopRealtime();
            this._initPolling();
        },

        /**
         * Stop real-time connection
         */
        stopRealtime: function () {
            if (this._pollTimer) {
                clearInterval(this._pollTimer);
                this._pollTimer = null;
            }
            this.state.isConnected = false;
        },

        /**
         * Periodic lightweight polling
         */
        _initPolling: function () {
            this._pollPulse();
            this._pollTimer = setInterval(() => {
                this._pollPulse();
            }, this.config.pollIntervalMs);
        },

        /**
         * Single fast pulse query (< 10ms execution on server)
         */
        _pollPulse: async function () {
            if (this._isPolling) return;
            this._isPolling = true;

            const branch = this.state.filters.branch_id !== undefined ? this.state.filters.branch_id : 'all';
            const qs = new URLSearchParams({
                action: 'pulse',
                branch_id: branch,
                last_sig: this.state.signature || '',
                last_check: this.state.lastPulseTimestamp || '',
                _t: Date.now()
            }).toString();

            try {
                const res = await fetch(`${this.config.apiEndpoint}?${qs}`, {
                    cache: 'no-store',
                    headers: { 'Pragma': 'no-cache', 'Cache-Control': 'no-cache' }
                });
                if (!res.ok) throw new Error('Pulse HTTP ' + res.status);
                const result = await res.json();

                if (result.success && result.data) {
                    const d = result.data;
                    this.state.isConnected = true;

                    if (d.has_updates) {
                        this.state.signature = d.signature;
                        this.state.lastPulseTimestamp = d.latest_activity;
                        this.state.liveCounters = d.live_counters;
                        this.emit('pulse', d);
                        await this.loadBundle();
                    } else {
                        this.emit('pulse', d);
                    }
                }
            } catch (err) {
                this.state.isConnected = false;
                this.emit('error', err);
            } finally {
                this._isPolling = false;
            }
        }
    };

    window.CurtissDashboard = CurtissDashboard;

})(window);
