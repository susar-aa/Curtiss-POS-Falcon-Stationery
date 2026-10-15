/**
 * Curtiss POS - Offline Billing & IndexedDB Synchronization Engine (Stage 18)
 * Local-First persistent storage using IndexedDB, Atomic Server Sync, Idempotency, 
 * Stock Reservation, Conflict Handling, and Temp Offline Receipts.
 */

const CurtissOfflinePOS = (function() {
    const DB_NAME = 'CurtissPOSDB';
    const DB_VERSION = 1;
    let db = null;
    let isOnlineStatus = navigator.onLine;

    // Helper: Generate RFC4122 v4 UUID
    function generateUUID() {
        if (typeof crypto !== 'undefined' && crypto.randomUUID) {
            return crypto.randomUUID();
        }
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    // Helper: Generate temporary offline reference e.g. OFF-A1B2C3D4
    function generateTempRef(uuid) {
        const clean = uuid.replace(/-/g, '').toUpperCase();
        return 'OFF-' + clean.substring(0, 8);
    }

    // Initialize IndexedDB
    function initDB() {
        return new Promise((resolve, reject) => {
            if (db) {
                resolve(db);
                return;
            }
            const request = indexedDB.open(DB_NAME, DB_VERSION);

            request.onupgradeneeded = function(e) {
                const dbInstance = e.target.result;

                // Billing Cache Store
                if (!dbInstance.objectStoreNames.contains('billing_cache')) {
                    dbInstance.createObjectStore('billing_cache', { keyPath: 'key' });
                }

                // Offline Sales Store
                if (!dbInstance.objectStoreNames.contains('offline_sales')) {
                    const salesStore = dbInstance.createObjectStore('offline_sales', { keyPath: 'offline_uuid' });
                    salesStore.createIndex('sync_status', 'sync_status', { unique: false });
                    salesStore.createIndex('created_at', 'created_at', { unique: false });
                }
            };

            request.onsuccess = function(e) {
                db = e.target.result;
                resolve(db);
            };

            request.onerror = function(e) {
                console.error("IndexedDB Open Error:", e.target.error);
                reject(e.target.error);
            };
        });
    }

    // Refresh billing cache from server when online
    async function refreshBillingCache() {
        if (!navigator.onLine) return false;
        try {
            const res = await fetch('pos_api.php?action=get_billing_cache');
            const data = await res.json();
            if (data.success) {
                const dbInstance = await initDB();
                const tx = dbInstance.transaction(['billing_cache'], 'readwrite');
                const store = tx.objectStore('billing_cache');

                store.put({ key: 'products', value: Array.isArray(data.products) ? data.products : [], timestamp: data.timestamp });
                store.put({ key: 'lots', value: Array.isArray(data.lots) ? data.lots : [], timestamp: data.timestamp });
                store.put({ key: 'customers', value: Array.isArray(data.customers) ? data.customers : [], timestamp: data.timestamp });
                store.put({ key: 'sales_reps', value: Array.isArray(data.sales_reps) ? data.sales_reps : [], timestamp: data.timestamp });
                store.put({ key: 'bank_accounts', value: Array.isArray(data.bank_accounts) ? data.bank_accounts : [], timestamp: data.timestamp });
                store.put({ key: 'branch', value: data.branch || null, timestamp: data.timestamp });

                return true;
            }
        } catch (err) {
            console.warn("Failed to refresh billing cache:", err);
        }
        return false;
    }

    // Clear all billing cache in IndexedDB
    async function clearAllBillingCache() {
        try {
            const dbInstance = await initDB();
            return new Promise((resolve) => {
                const tx = dbInstance.transaction(['billing_cache'], 'readwrite');
                const store = tx.objectStore('billing_cache');
                const req = store.clear();
                req.onsuccess = () => resolve(true);
                req.onerror = () => resolve(false);
            });
        } catch (e) {
            console.warn("Failed to clear billing cache:", e);
            return false;
        }
    }

    // Get item from cache
    async function getCachedData(key) {
        const dbInstance = await initDB();
        return new Promise((resolve) => {
            const tx = dbInstance.transaction(['billing_cache'], 'readonly');
            const store = tx.objectStore('billing_cache');
            const req = store.get(key);
            req.onsuccess = () => resolve(req.result ? req.result.value : null);
            req.onerror = () => resolve(null);
        });
    }

    // Save offline sale into IndexedDB and reserve local stock
    async function saveOfflineSale(payload) {
        const dbInstance = await initDB();
        const uuid = generateUUID();
        const tempRef = generateTempRef(uuid);

        const record = {
            offline_uuid: uuid,
            temp_ref: tempRef,
            branch_id: payload.branch_id,
            user_id: payload.user_id,
            customer_id: payload.customer_id,
            customer_name: payload.customer_name || 'Customer',
            sales_rep_id: payload.sales_rep_id || null,
            sales_rep_name: payload.sales_rep_name || null,
            items: payload.items,
            discount_str: payload.discount_str || '',
            customer_facing_discount: payload.customer_facing_discount || '',
            subtotal: payload.subtotal,
            total_discount: payload.total_discount || 0,
            total_amount: payload.total_amount,
            payments: payload.payments,
            created_at: new Date().toISOString().replace('T', ' ').substring(0, 19),
            sync_status: 'Pending Sync',
            server_sale_id: null,
            server_invoice_no: null,
            conflict_details: null,
            error_message: null
        };

        // 1. Save Sale in IndexedDB
        await new Promise((resolve, reject) => {
            const tx = dbInstance.transaction(['offline_sales'], 'readwrite');
            const store = tx.objectStore('offline_sales');
            const req = store.add(record);
            req.onsuccess = () => resolve();
            req.onerror = (e) => reject(e.target.error);
        });

        // 2. Local Stock Reservation: Deduct cached lots
        try {
            const cachedLots = await getCachedData('lots') || [];
            payload.items.forEach(item => {
                const lot = cachedLots.find(l => parseInt(l.id) === parseInt(item.lot_id));
                if (lot) {
                    lot.remaining_quantity = Math.max(0, parseFloat(lot.remaining_quantity) - parseFloat(item.quantity));
                }
            });
            const tx = dbInstance.transaction(['billing_cache'], 'readwrite');
            tx.objectStore('billing_cache').put({ key: 'lots', value: cachedLots, timestamp: Date.now() });
        } catch (e) {
            console.warn("Stock reservation update failed:", e);
        }

        return record;
    }

    // Fetch all offline sales
    async function getAllOfflineSales() {
        const dbInstance = await initDB();
        return new Promise((resolve) => {
            const tx = dbInstance.transaction(['offline_sales'], 'readonly');
            const store = tx.objectStore('offline_sales');
            const req = store.getAll();
            req.onsuccess = () => resolve(req.result || []);
            req.onerror = () => resolve([]);
        });
    }

    // Fetch offline sales scoped to specific user and branch
    async function getOfflineSalesForUser(userId, branchId) {
        const sales = await getAllOfflineSales();
        return sales.filter(s => 
            (!userId || parseInt(s.user_id) === parseInt(userId)) && 
            (!branchId || parseInt(s.branch_id) === parseInt(branchId))
        );
    }

    // Sync all pending offline sales to server
    async function syncAllPendingSales(progressCallback) {
        if (!navigator.onLine) return { success: false, message: 'Device is offline.' };

        const sales = await getAllOfflineSales();
        const pending = sales.filter(s => s.sync_status === 'Pending Sync' || s.sync_status === 'Failed');

        if (pending.length === 0) {
            return { success: true, syncedCount: 0, conflictCount: 0, message: 'No pending offline sales to sync.' };
        }

        let syncedCount = 0;
        let conflictCount = 0;
        let failedCount = 0;

        for (const sale of pending) {
            // Update status to Syncing
            await updateSaleStatus(sale.offline_uuid, 'Syncing');

            try {
                const res = await fetch('pos_api.php?action=sync_offline_sale', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(sale)
                });
                const result = await res.json();

                if (result.success) {
                    syncedCount++;
                    await updateSaleStatus(sale.offline_uuid, 'Synced', {
                        server_sale_id: result.sale_id,
                        server_invoice_no: result.invoice_no
                    });
                } else if (result.conflict) {
                    conflictCount++;
                    await updateSaleStatus(sale.offline_uuid, 'Conflict', {
                        conflict_details: result.conflict_details,
                        error_message: result.message
                    });
                } else {
                    failedCount++;
                    await updateSaleStatus(sale.offline_uuid, 'Failed', {
                        error_message: result.message
                    });
                }
            } catch (err) {
                failedCount++;
                await updateSaleStatus(sale.offline_uuid, 'Failed', {
                    error_message: err.message
                });
            }

            if (progressCallback) {
                progressCallback({ syncedCount, conflictCount, failedCount, total: pending.length });
            }
        }

        // Refresh billing cache after sync
        await refreshBillingCache();

        return {
            success: true,
            syncedCount,
            conflictCount,
            failedCount,
            total: pending.length
        };
    }

    // Update sale status in IndexedDB
    async function updateSaleStatus(uuid, status, extraData = {}) {
        const dbInstance = await initDB();
        return new Promise((resolve) => {
            const tx = dbInstance.transaction(['offline_sales'], 'readwrite');
            const store = tx.objectStore('offline_sales');
            const req = store.get(uuid);

            req.onsuccess = () => {
                const record = req.result;
                if (record) {
                    record.sync_status = status;
                    if (extraData.server_sale_id) record.server_sale_id = extraData.server_sale_id;
                    if (extraData.server_invoice_no) record.server_invoice_no = extraData.server_invoice_no;
                    if (extraData.conflict_details) record.conflict_details = extraData.conflict_details;
                    if (extraData.error_message) record.error_message = extraData.error_message;

                    store.put(record);
                }
                resolve();
            };
            req.onerror = () => resolve();
        });
    }

    return {
        initDB,
        refreshBillingCache,
        clearAllBillingCache,
        getCachedData,
        saveOfflineSale,
        getAllOfflineSales,
        getOfflineSalesForUser,
        syncAllPendingSales,
        generateUUID,
        generateTempRef
    };
})();
