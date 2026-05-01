(function (window, _, axios, GeckoClient) {
    'use strict';

    const storageKey = 'TONBANKCARD:watchlist:v1';
    const storageTestKey = storageKey + ':test';
    const legacyStorageKeys = ['TONBANKCARD:watchlist', 'GeckoClient:watchlist'];
    const maxEntries = 200;
    const changedEventName = 'tonbankcard:watchlist-changed';

    let state = emptyState();
    let initialized = false;
    let initializing = null;
    let localStorageAvailable = null;
    let listeners = [];
    let serverSyncEnabled = false;
    let serverSyncAttempted = false;

    const service = GeckoClient.watchlist = {
        storageKey: storageKey,
        storageMode: 'local',
        init: init,
        ids: ids,
        snapshot: snapshot,
        has: has,
        add: add,
        remove: remove,
        toggle: toggle,
        onChange: onChange,
        normalizeEntry: normalizeEntry
    };

    function nowIso() {
        return new Date().toISOString();
    }

    function emptyState() {
        return {
            version: 1,
            updated_at: null,
            entries: [],
            removed: {}
        };
    }

    function clone(value) {
        return _.cloneDeep(value);
    }

    function parseDate(value) {
        const date = new Date(value || 0);
        return GeckoClient.utils.isValidDate(date) ? date.getTime() : 0;
    }

    function normalizeCoinId(value) {
        value = _.toLower(_.trim(value || ''));
        return /^[a-z0-9._-]{1,96}$/.test(value) ? value : null;
    }

    function normalizeSymbol(value) {
        value = _.toLower(_.trim(value || ''));
        return /^[a-z0-9._-]{1,32}$/.test(value) ? value : null;
    }

    function normalizeImage(value) {
        if (_.isString(value)) return value;
        if (_.isObject(value)) return value.large || value.small || value.thumb || null;
        return null;
    }

    function normalizeEntry(item) {
        if (_.isString(item)) {
            const id = normalizeCoinId(item);
            return id ? {
                coin_id: id,
                id: id,
                symbol: null,
                name: _.startCase(id),
                image: null,
                source: 'coingecko',
                added_at: nowIso(),
                updated_at: nowIso()
            } : null;
        }

        if (!_.isObject(item)) return null;

        const id = normalizeCoinId(item.coin_id || item.id);
        if (!id) return null;

        const symbol = normalizeSymbol(item.symbol);
        const addedAt = item.added_at || item.addedAt || nowIso();
        const updatedAt = item.updated_at || item.updatedAt || addedAt;

        return {
            coin_id: id,
            id: id,
            symbol: symbol,
            name: _.trim(item.name || item.title || '') || _.startCase(id),
            image: normalizeImage(item.image || item.large || item.small || item.thumb),
            source: _.trim(item.source || 'coingecko') || 'coingecko',
            added_at: addedAt,
            updated_at: updatedAt
        };
    }

    function normalizePayload(payload) {
        if (_.isString(payload)) {
            try {
                payload = JSON.parse(payload);
            } catch (err) {
                return emptyState();
            }
        }

        let entries = [];
        let removed = {};

        if (_.isArray(payload)) {
            entries = payload;
        } else if (_.isObject(payload)) {
            if (_.isArray(payload.entries)) {
                entries = payload.entries;
            } else {
                entries = _.keys(payload).map(key => {
                    const value = payload[key];
                    return _.isObject(value) ? Object.assign({}, value, {coin_id: key}) : key;
                });
            }

            if (_.isObject(payload.removed)) {
                removed = _.mapValues(payload.removed, value => _.isString(value) ? value : nowIso());
            }
        }

        const seen = {};
        const normalizedEntries = [];
        entries.forEach(item => {
            const entry = normalizeEntry(item);
            if (!entry || seen[entry.coin_id]) return;
            seen[entry.coin_id] = true;
            normalizedEntries.push(entry);
        });

        const normalizedRemoved = {};
        _.forOwn(removed, (removedAt, coinId) => {
            const id = normalizeCoinId(coinId);
            if (id) normalizedRemoved[id] = removedAt || nowIso();
        });

        return {
            version: 1,
            updated_at: _.get(payload, 'updated_at') || null,
            entries: normalizedEntries.slice(0, maxEntries),
            removed: normalizedRemoved
        };
    }

    function mergeState(base, incoming) {
        base = normalizePayload(base);
        incoming = normalizePayload(incoming);

        const entriesById = {};
        const removed = Object.assign({}, base.removed || {}, incoming.removed || {});

        function mergeEntry(entry) {
            const removedAt = parseDate(removed[entry.coin_id]);
            const entryUpdatedAt = parseDate(entry.updated_at || entry.added_at);
            if (removedAt > entryUpdatedAt) return;

            const existing = entriesById[entry.coin_id];
            if (!existing || parseDate(existing.updated_at || existing.added_at) <= entryUpdatedAt) {
                entriesById[entry.coin_id] = entry;
            }
        }

        base.entries.forEach(mergeEntry);
        incoming.entries.forEach(mergeEntry);

        const entries = _.values(entriesById)
            .filter(entry => parseDate(removed[entry.coin_id]) <= parseDate(entry.updated_at || entry.added_at))
            .sort((a, b) => parseDate(a.added_at) - parseDate(b.added_at))
            .slice(0, maxEntries);

        return {
            version: 1,
            updated_at: base.updated_at || incoming.updated_at || nowIso(),
            entries: entries,
            removed: removed
        };
    }

    function canUseLocalStorage() {
        if (localStorageAvailable !== null) return localStorageAvailable;

        try {
            window.localStorage.setItem(storageTestKey, '1');
            window.localStorage.removeItem(storageTestKey);
            localStorageAvailable = true;
        } catch (err) {
            localStorageAvailable = false;
        }

        return localStorageAvailable;
    }

    function readLocalStorage(key) {
        if (!canUseLocalStorage()) return null;

        try {
            return window.localStorage.getItem(key);
        } catch (err) {
            localStorageAvailable = false;
            return null;
        }
    }

    function writeLocalStorage(key, value) {
        if (!canUseLocalStorage()) return false;

        try {
            window.localStorage.setItem(key, value);
            return true;
        } catch (err) {
            localStorageAvailable = false;
            return false;
        }
    }

    function readLocalPayload() {
        const raw = readLocalStorage(storageKey);
        if (raw) return normalizePayload(raw);

        for (let i = 0; i < legacyStorageKeys.length; i++) {
            const legacyRaw = readLocalStorage(legacyStorageKeys[i]);
            if (legacyRaw) return normalizePayload(legacyRaw);
        }

        return emptyState();
    }

    function writeLocalPayload(payload) {
        return writeLocalStorage(storageKey, JSON.stringify(payload));
    }

    function getCloudStorage() {
        const cloudStorage = _.get(GeckoClient, 'telegram.webApp.CloudStorage') || _.get(window, 'Telegram.WebApp.CloudStorage');
        if (!cloudStorage || !_.isFunction(cloudStorage.getItem) || !_.isFunction(cloudStorage.setItem)) {
            return null;
        }

        return cloudStorage;
    }

    function cloudGet(cloudStorage) {
        return new Promise(resolve => {
            try {
                cloudStorage.getItem(storageKey, function (error, value) {
                    resolve(error ? null : value);
                });
            } catch (err) {
                resolve(null);
            }
        });
    }

    function cloudSet(cloudStorage, payload) {
        return new Promise(resolve => {
            try {
                cloudStorage.setItem(storageKey, JSON.stringify(payload), function (error) {
                    resolve(!error);
                });
            } catch (err) {
                resolve(false);
            }
        });
    }

    function loadStoredState() {
        const cloudStorage = getCloudStorage();
        if (cloudStorage) {
            service.storageMode = 'telegram_cloud';
            return cloudGet(cloudStorage).then(raw => {
                if (raw) return normalizePayload(raw);
                return readLocalPayload();
            });
        }

        if (!canUseLocalStorage()) {
            service.storageMode = 'memory';
            return Promise.resolve(state);
        }

        service.storageMode = 'local';
        return Promise.resolve(readLocalPayload());
    }

    function persistDeviceSnapshot(payload) {
        let stored;
        if (service.storageMode === 'telegram_cloud') {
            const cloudStorage = getCloudStorage();
            stored = cloudStorage ? cloudSet(cloudStorage, payload) : Promise.resolve(false);
        } else if (service.storageMode === 'memory') {
            stored = Promise.resolve(true);
        } else {
            stored = Promise.resolve(writeLocalPayload(payload));
        }

        return stored.then(ok => {
            if (!ok && service.storageMode !== 'memory') {
                service.storageMode = writeLocalPayload(payload) ? 'local' : 'memory';
            }

            return payload;
        });
    }

    function persistState() {
        state.version = 1;
        state.updated_at = nowIso();
        const payload = snapshot();

        return persistDeviceSnapshot(payload)
            .then(() => pushServerSnapshot())
            .then(() => payload);
    }

    function emitAnalytics(eventName, entry, options) {
        if (!GeckoClient.analytics) return;

        GeckoClient.analytics.emit(eventName, {
            coin_id: entry.coin_id,
            symbol: entry.symbol,
            source_route: _.get(options, 'sourceRoute') || _.get(options, 'source_route') || null,
            storage_mode: service.storageMode
        });
    }

    function notify() {
        const detail = snapshot();
        listeners.slice().forEach(callback => callback(detail));

        if (typeof window.CustomEvent === 'function') {
            window.dispatchEvent(new CustomEvent(changedEventName, {detail: detail}));
        }
    }

    function endpoint() {
        return _.get(GeckoClient, 'watchlistConfig.apiBaseUrl', '/api/watchlist');
    }

    function telegramInitData() {
        return _.get(GeckoClient, 'telegram.webApp.initData') || _.get(window, 'Telegram.WebApp.initData') || '';
    }

    function bootstrapServerSync() {
        if (serverSyncAttempted) return Promise.resolve();
        serverSyncAttempted = true;

        const initData = telegramInitData();
        if (!initData || !axios) return Promise.resolve();

        return axios.post('/api/telegram/session', {initData: initData}, {withCredentials: true})
            .then(response => {
                const payload = response.data || {};
                const trustState = _.get(payload, 'data.session.state');
                serverSyncEnabled = payload.ok === true && trustState === 'telegram_validated';
                if (!serverSyncEnabled) return;

                return pullServerSnapshot();
            })
            .catch(() => {
                serverSyncEnabled = false;
            });
    }

    function pullServerSnapshot() {
        if (!serverSyncEnabled || !axios) return Promise.resolve();

        return axios.get(endpoint(), {withCredentials: true})
            .then(response => {
                const payload = response.data || {};
                if (payload.ok !== true) return;

                state = mergeState(state, _.get(payload, 'data', {}));
                notify();
                return persistState();
            })
            .catch(() => {});
    }

    function pushServerSnapshot() {
        if (!serverSyncEnabled || !axios) return Promise.resolve();

        return axios.post(
            endpoint(),
            {
                entries: state.entries.map(entry => _.pick(entry, ['coin_id', 'symbol', 'source', 'added_at', 'updated_at'])),
                removed: state.removed,
                updated_at: state.updated_at
            },
            {withCredentials: true}
        ).then(response => {
            const payload = response.data || {};
            if (payload.ok === true && _.get(payload, 'data.entries')) {
                state = mergeState(state, payload.data);
                notify();
                return persistDeviceSnapshot(snapshot());
            }
        }).catch(() => {});
    }

    function init() {
        if (initialized) return Promise.resolve(service);
        if (initializing) return initializing;

        initializing = loadStoredState()
            .then(storedState => {
                state = mergeState(state, storedState);
                initialized = true;
                notify();
                return bootstrapServerSync();
            })
            .then(() => service);

        return initializing;
    }

    function ids() {
        return state.entries.map(entry => entry.coin_id);
    }

    function snapshot() {
        return clone({
            version: 1,
            updated_at: state.updated_at,
            entries: state.entries,
            removed: state.removed
        });
    }

    function has(item) {
        const id = normalizeCoinId(_.isString(item) ? item : _.get(item, 'coin_id') || _.get(item, 'id'));
        return !!id && ids().indexOf(id) >= 0;
    }

    function add(item, options) {
        const entry = normalizeEntry(item);
        if (!entry) return Promise.resolve(false);

        return init().then(() => {
            const existing = _.find(state.entries, ['coin_id', entry.coin_id]);
            const timestamp = nowIso();
            entry.added_at = existing ? existing.added_at : timestamp;
            entry.updated_at = timestamp;

            state.entries = state.entries.filter(current => current.coin_id !== entry.coin_id);
            state.entries.push(entry);
            state.entries = state.entries.slice(-maxEntries);
            delete state.removed[entry.coin_id];

            notify();
            emitAnalytics('watchlist_added', entry, options);
            return persistState().then(() => true);
        });
    }

    function remove(item, options) {
        const id = normalizeCoinId(_.isString(item) ? item : _.get(item, 'coin_id') || _.get(item, 'id'));
        if (!id) return Promise.resolve(false);

        return init().then(() => {
            const entry = _.find(state.entries, ['coin_id', id]) || normalizeEntry(id);
            const beforeCount = state.entries.length;
            state.entries = state.entries.filter(current => current.coin_id !== id);
            state.removed[id] = nowIso();

            notify();
            if (beforeCount !== state.entries.length) {
                emitAnalytics('watchlist_removed', entry, options);
            }

            return persistState().then(() => true);
        });
    }

    function toggle(item, options) {
        return init().then(() => has(item) ? remove(item, options) : add(item, options));
    }

    function onChange(callback) {
        if (!_.isFunction(callback)) return function () {};

        listeners.push(callback);
        return function unsubscribe() {
            listeners = listeners.filter(listener => listener !== callback);
        };
    }

})(window, _, axios, GeckoClient);
