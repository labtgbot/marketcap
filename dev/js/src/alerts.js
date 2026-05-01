(function (window, _, axios, GeckoClient) {
    'use strict';

    const storageKey = 'TONBANKCARD:alerts:v1';
    const storageTestKey = storageKey + ':test';
    const changedEventName = 'tonbankcard:alerts-changed';
    const maxLocalRules = 50;

    let state = emptyState();
    let initialized = false;
    let initializing = null;
    let listeners = [];
    let localStorageAvailable = null;
    let serverSyncEnabled = false;
    let serverSyncAttempted = false;

    const service = GeckoClient.alerts = {
        storageKey: storageKey,
        storageMode: 'local',
        init: init,
        snapshot: snapshot,
        list: list,
        save: save,
        remove: remove,
        pause: pause,
        resume: resume,
        test: testDelivery,
        readDraft: readDraft,
        clearDraft: clearDraft,
        normalizeRule: normalizeRule,
        emptyRule: emptyRule,
        onChange: onChange
    };

    function nowIso() {
        return new Date().toISOString();
    }

    function emptyState() {
        return {
            version: 1,
            updated_at: null,
            rules: []
        };
    }

    function emptyRule(seed) {
        seed = seed || {};
        return {
            id: seed.id || null,
            coin_id: normalizeCoinId(seed.coin_id || 'toncoin') || 'toncoin',
            symbol: normalizeSymbol(seed.symbol || 'TON') || 'TON',
            display_name: _.trim(seed.display_name || seed.name || '') || null,
            trigger_type: seed.trigger_type || 'price_cross',
            operator: seed.operator || seed.comparison_operator || 'gte',
            threshold: _.isFinite(parseFloat(seed.threshold || seed.threshold_value)) ? parseFloat(seed.threshold || seed.threshold_value) : null,
            delivery_channel: 'telegram_bot',
            status: seed.status || 'active',
            quiet_start: seed.quiet_start || seed.quiet_hours_start || null,
            quiet_end: seed.quiet_end || seed.quiet_hours_end || null,
            timezone: seed.timezone || Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC',
            frequency_cap_seconds: parseInt(seed.frequency_cap_seconds || _.get(GeckoClient, 'alertsDefaults.frequencyCapSeconds') || 3600, 10),
            max_deliveries_per_day: parseInt(seed.max_deliveries_per_day || 8, 10),
            context_path: seed.context_path || (seed.coin_id ? '/currency/' + seed.coin_id : '/currency/toncoin'),
            created_at: seed.created_at || nowIso(),
            updated_at: seed.updated_at || nowIso()
        };
    }

    function normalizeCoinId(value) {
        value = _.toLower(_.trim(value || ''));
        return /^[a-z0-9._-]{1,96}$/.test(value) ? value : null;
    }

    function normalizeSymbol(value) {
        value = _.toUpper(_.trim(value || ''));
        value = value.replace(/[^A-Z0-9._-]/g, '');
        return value ? value.slice(0, 32) : null;
    }

    function normalizeTime(value) {
        value = _.trim(value || '');
        if (!value) return null;
        const match = value.match(/^([01][0-9]|2[0-3]):([0-5][0-9])(?::([0-5][0-9]))?$/);
        return match ? match[1] + ':' + match[2] : null;
    }

    function normalizeTriggerType(value) {
        value = _.toLower(_.trim(value || 'price_cross'));
        return [
            'price_cross',
            'percent_move',
            'volume_spike',
            'market_cap_cross',
            'rank_change',
            'sentiment_change',
            'ton_ecosystem'
        ].indexOf(value) >= 0 ? value : 'price_cross';
    }

    function normalizeOperator(value) {
        value = _.toLower(_.trim(value || 'gte'));
        return ['gt', 'gte', 'lt', 'lte'].indexOf(value) >= 0 ? value : 'gte';
    }

    function normalizeRule(input) {
        if (!_.isObject(input)) return null;

        const seed = emptyRule(input);
        const coinId = normalizeCoinId(input.coin_id || seed.coin_id);
        if (!coinId) return null;

        const threshold = parseFloat(input.threshold !== undefined ? input.threshold : input.threshold_value);
        const rule = Object.assign(seed, {
            id: input.id || input.local_id || seed.id,
            local_id: input.local_id || (_.isString(input.id) && input.id.indexOf('local_') === 0 ? input.id : null),
            coin_id: coinId,
            symbol: normalizeSymbol(input.symbol) || seed.symbol,
            display_name: _.trim(input.display_name || input.name || seed.display_name || '') || null,
            trigger_type: normalizeTriggerType(input.trigger_type),
            operator: normalizeOperator(input.operator || input.comparison_operator),
            threshold: _.isFinite(threshold) ? threshold : seed.threshold,
            delivery_channel: 'telegram_bot',
            status: ['active', 'paused'].indexOf(input.status) >= 0 ? input.status : seed.status,
            quiet_start: normalizeTime(input.quiet_start || input.quiet_hours_start),
            quiet_end: normalizeTime(input.quiet_end || input.quiet_hours_end),
            timezone: _.trim(input.timezone || seed.timezone) || 'UTC',
            frequency_cap_seconds: clampInt(input.frequency_cap_seconds, 300, 86400, seed.frequency_cap_seconds),
            max_deliveries_per_day: clampInt(input.max_deliveries_per_day, 1, 100, seed.max_deliveries_per_day),
            context_path: safeContextPath(input.context_path, coinId),
            updated_at: input.updated_at || nowIso(),
            links: input.links || null,
            last_evaluated_at: input.last_evaluated_at || null,
            last_triggered_at: input.last_triggered_at || null,
            next_evaluation_at: input.next_evaluation_at || null
        });

        if (rule.threshold === null && rule.trigger_type !== 'ton_ecosystem') {
            rule.threshold = 0;
        }
        if (rule.trigger_type === 'ton_ecosystem' && rule.threshold === null) {
            rule.threshold = 0;
        }

        return rule;
    }

    function clampInt(value, min, max, fallback) {
        value = parseInt(value, 10);
        if (!_.isFinite(value)) return fallback;
        return Math.max(min, Math.min(max, value));
    }

    function safeContextPath(value, coinId) {
        value = _.trim(value || '');
        if (!value || value.charAt(0) !== '/' || value.indexOf('//') === 0) {
            return '/currency/' + coinId;
        }
        return value.slice(0, 190);
    }

    function normalizePayload(payload) {
        if (_.isString(payload)) {
            try {
                payload = JSON.parse(payload);
            } catch (err) {
                return emptyState();
            }
        }

        let rules = [];
        if (_.isArray(payload)) {
            rules = payload;
        } else if (_.isArray(_.get(payload, 'rules'))) {
            rules = payload.rules;
        }

        const seen = {};
        const normalized = [];
        rules.forEach(item => {
            const rule = normalizeRule(item);
            if (!rule) return;
            const key = String(rule.id || rule.local_id || rule.coin_id + ':' + rule.trigger_type);
            if (seen[key]) return;
            seen[key] = true;
            normalized.push(rule);
        });

        return {
            version: 1,
            updated_at: _.get(payload, 'updated_at') || nowIso(),
            rules: normalized.slice(0, maxLocalRules)
        };
    }

    function clone(value) {
        return _.cloneDeep(value);
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

    function readLocalPayload() {
        if (!canUseLocalStorage()) {
            service.storageMode = 'memory';
            return emptyState();
        }

        try {
            return normalizePayload(window.localStorage.getItem(storageKey));
        } catch (err) {
            localStorageAvailable = false;
            service.storageMode = 'memory';
            return emptyState();
        }
    }

    function writeLocalPayload(payload) {
        if (!canUseLocalStorage()) return false;
        try {
            window.localStorage.setItem(storageKey, JSON.stringify(payload));
            return true;
        } catch (err) {
            localStorageAvailable = false;
            service.storageMode = 'memory';
            return false;
        }
    }

    function persistState() {
        state.version = 1;
        state.updated_at = nowIso();
        writeLocalPayload(snapshot());
        notify();
        return Promise.resolve(snapshot());
    }

    function endpoint(id, suffix) {
        let base = _.get(GeckoClient, 'alertsConfig.apiBaseUrl', '/api/alerts').replace(/\/+$/, '');
        if (id) base += '/' + encodeURIComponent(id);
        if (suffix) base += '/' + suffix;
        return base;
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
                service.storageMode = serverSyncEnabled ? 'server' : service.storageMode;
                if (!serverSyncEnabled) return;
                return pullServerRules();
            })
            .catch(() => {
                serverSyncEnabled = false;
            });
    }

    function pullServerRules() {
        if (!serverSyncEnabled || !axios) return Promise.resolve();

        return axios.get(endpoint(), {withCredentials: true})
            .then(response => {
                const payload = response.data || {};
                if (payload.ok !== true) return;
                state = normalizePayload(_.get(payload, 'data', {}));
                writeLocalPayload(snapshot());
                notify();
            })
            .catch(() => {});
    }

    function serverRuleId(rule) {
        const id = _.get(rule, 'id');
        return /^[0-9]+$/.test(String(id || '')) ? String(id) : null;
    }

    function init() {
        if (initialized) return Promise.resolve(service);
        if (initializing) return initializing;

        initializing = Promise.resolve(readLocalPayload())
            .then(payload => {
                state = normalizePayload(payload);
                initialized = true;
                notify();
                return bootstrapServerSync();
            })
            .then(() => service);

        return initializing;
    }

    function snapshot() {
        return clone(state);
    }

    function list() {
        return snapshot().rules || [];
    }

    function upsertLocal(rule) {
        const key = String(rule.id || rule.local_id);
        state.rules = state.rules.filter(current => String(current.id || current.local_id) !== key);
        state.rules.unshift(rule);
        state.rules = state.rules.slice(0, maxLocalRules);
        return persistState();
    }

    function save(rule, options) {
        rule = normalizeRule(rule);
        if (!rule) return Promise.reject(new Error('invalid_alert_rule'));

        return init().then(() => {
            const existingId = serverRuleId(rule);
            if (serverSyncEnabled && axios) {
                const payload = rulePayload(rule);
                const request = existingId
                    ? axios.put(endpoint(existingId), payload, {withCredentials: true})
                    : axios.post(endpoint(), payload, {withCredentials: true});

                return request.then(response => {
                    const body = response.data || {};
                    const saved = normalizeRule(_.get(body, 'data.rule') || rule);
                    emitAnalytics(existingId ? 'alert_updated' : 'alert_created', saved, options);
                    return upsertLocal(saved).then(() => saved);
                });
            }

            const isNewLocal = !rule.id;
            if (isNewLocal) {
                rule.id = 'local_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2, 8);
                rule.local_id = rule.id;
                rule.created_at = nowIso();
            }
            rule.updated_at = nowIso();
            emitAnalytics(isNewLocal ? 'alert_created' : 'alert_updated', rule, options);
            return upsertLocal(rule).then(() => rule);
        });
    }

    function rulePayload(rule) {
        return {
            coin_id: rule.coin_id,
            symbol: rule.symbol,
            display_name: rule.display_name,
            trigger_type: rule.trigger_type,
            operator: rule.operator,
            threshold: rule.threshold,
            delivery_channel: 'telegram_bot',
            status: rule.status,
            quiet_start: rule.quiet_start,
            quiet_end: rule.quiet_end,
            timezone: rule.timezone,
            frequency_cap_seconds: rule.frequency_cap_seconds,
            max_deliveries_per_day: rule.max_deliveries_per_day,
            context_path: rule.context_path
        };
    }

    function remove(rule, options) {
        return init().then(() => {
            const id = serverRuleId(rule);
            const localKey = String(_.get(rule, 'id') || _.get(rule, 'local_id'));
            const finish = () => {
                state.rules = state.rules.filter(current => String(current.id || current.local_id) !== localKey);
                emitAnalytics('alert_deleted', rule, options);
                return persistState().then(() => true);
            };

            if (serverSyncEnabled && axios && id) {
                return axios.delete(endpoint(id), {withCredentials: true}).then(finish);
            }

            return finish();
        });
    }

    function pause(rule, options) {
        rule = Object.assign({}, rule, {status: 'paused'});
        return save(rule, options).then(saved => {
            emitAnalytics('alert_paused', saved, options);
            return saved;
        });
    }

    function resume(rule, options) {
        return save(Object.assign({}, rule, {status: 'active'}), options);
    }

    function testDelivery(rule, options) {
        return init().then(() => {
            const id = serverRuleId(rule);
            if (serverSyncEnabled && axios && id) {
                return axios.post(endpoint(id, 'test'), {}, {withCredentials: true})
                    .then(response => {
                        emitAnalytics('alert_tested', rule, options);
                        return _.get(response, 'data.data.delivery') || null;
                    });
            }

            emitAnalytics('alert_tested', rule, options);
            return {
                text: 'Test alert: ' + (rule.symbol || rule.coin_id) + ' opens inside TONBANKCARD.',
                startapp: 'alert_' + (rule.id || rule.local_id || rule.coin_id),
                links: {
                    mini_app_path: '/app/alerts?coin=' + encodeURIComponent(rule.coin_id),
                    coin_path: rule.context_path || '/currency/' + rule.coin_id,
                    telegram_deep_link: '/app/alerts?coin=' + encodeURIComponent(rule.coin_id)
                }
            };
        });
    }

    function readDraft() {
        const key = _.get(GeckoClient, 'alertsConfig.draftStorageKey', 'TONBANKCARD:alertDraft');
        if (!canUseLocalStorage()) return null;
        try {
            const raw = window.localStorage.getItem(key);
            return raw ? JSON.parse(raw) : null;
        } catch (err) {
            return null;
        }
    }

    function clearDraft() {
        const key = _.get(GeckoClient, 'alertsConfig.draftStorageKey', 'TONBANKCARD:alertDraft');
        if (!canUseLocalStorage()) return;
        try {
            window.localStorage.removeItem(key);
        } catch (err) {}
    }

    function emitAnalytics(eventName, rule, options) {
        const properties = {
            alert_id: String(rule.id || rule.local_id || ''),
            coin_id: rule.coin_id,
            symbol: rule.symbol,
            trigger_type: rule.trigger_type,
            delivery_channel: 'telegram_bot',
            threshold_bucket: thresholdBucket(rule.threshold),
            quiet_hours_enabled: !!(rule.quiet_start && rule.quiet_end),
            status: rule.status,
            source_route: _.get(options, 'sourceRoute') || _.get(options, 'source_route') || null,
            storage_mode: service.storageMode
        };

        if (GeckoClient.analytics) {
            GeckoClient.analytics.emit(eventName, properties);
        }
        if (eventName === 'alert_created' && GeckoClient.achievements) {
            GeckoClient.achievements.track('alert_created', properties);
        }
    }

    function thresholdBucket(value) {
        value = Math.abs(parseFloat(value) || 0);
        if (value === 0) return 'zero';
        if (value < 1) return 'sub_1';
        if (value < 10) return '1_10';
        if (value < 100) return '10_100';
        return '100_plus';
    }

    function notify() {
        const detail = snapshot();
        listeners.slice().forEach(callback => callback(detail));

        if (typeof window.CustomEvent === 'function') {
            window.dispatchEvent(new CustomEvent(changedEventName, {detail: detail}));
        }
    }

    function onChange(callback) {
        if (!_.isFunction(callback)) return function () {};
        listeners.push(callback);
        return function unsubscribe() {
            listeners = listeners.filter(listener => listener !== callback);
        };
    }

})(window, _, axios, GeckoClient);
