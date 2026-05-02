(function (window, _, axios, GeckoClient) {
    'use strict';

    const config = GeckoClient.premiumConfig || {};
    let entitlement = emptyEntitlement();
    let initialized = false;
    let initializing = null;
    let sessionAttempted = false;

    const service = GeckoClient.premium = {
        settings: normalizeSettings(config.settings || {}),
        plans: normalizePlans(config.plans || []),
        init: init,
        entitlement: snapshot,
        fetchPlans: fetchPlans,
        fetchEntitlement: fetchEntitlement,
        checkout: checkout,
        cancel: cancel,
        openInvoice: openInvoice
    };

    function apiBaseUrl() {
        return _.trimEnd(config.apiBaseUrl || '/api/premium', '/');
    }

    function endpoint(suffix) {
        return suffix ? apiBaseUrl() + '/' + suffix : apiBaseUrl();
    }

    function normalizeSettings(source) {
        return {
            enabled: source.enabled === true,
            checkout_enabled: source.checkout_enabled === true,
            provider: _.toString(source.provider || 'telegram_stars'),
            currency: _.toString(source.currency || 'XTR'),
            premium_plan_code: _.toString(source.premium_plan_code || 'premium_monthly'),
            subscription_period_seconds: parseInt(source.subscription_period_seconds, 10) || 2592000,
            telegram_bot_configured: source.telegram_bot_configured === true,
            bot_username: _.toString(source.bot_username || '')
        };
    }

    function normalizePlans(items) {
        return (items || []).map(plan => {
            const limits = plan.limits || {};
            return {
                code: _.toString(plan.code || ''),
                name: _.toString(plan.name || ''),
                description: _.toString(plan.description || ''),
                price_stars: parseInt(plan.price_stars, 10) || 0,
                currency: _.toString(plan.currency || 'XTR'),
                recurring: plan.recurring === true,
                subscription_period_seconds: plan.subscription_period_seconds || null,
                duration_seconds: plan.duration_seconds || null,
                limits: {
                    alerts_per_user: parseInt(limits.alerts_per_user || limits.alerts, 10) || 0,
                    watchlist_entries: parseInt(limits.watchlist_entries || limits.watchlist, 10) || 0,
                    advanced_ranges: _.isArray(limits.advanced_ranges) ? limits.advanced_ranges : [],
                    ai_digest_per_day: parseInt(limits.ai_digest_per_day, 10) || 0,
                    priority_refresh: limits.priority_refresh === true,
                    market_refresh_seconds: parseInt(limits.market_refresh_seconds, 10) || 0
                }
            };
        }).filter(plan => plan.code);
    }

    function emptyEntitlement() {
        return {
            plan_code: 'free',
            entitled: false,
            status: 'free',
            source: null,
            reason: 'initial',
            limits: {},
            entitlement: null,
            upgrade_plan_code: 'premium_monthly'
        };
    }

    function normalizeEntitlement(source) {
        return Object.assign(emptyEntitlement(), _.isPlainObject(source) ? source : {});
    }

    function telegramInitData() {
        return _.get(GeckoClient, 'telegram.webApp.initData') || _.get(window, 'Telegram.WebApp.initData') || '';
    }

    function bootstrapSession() {
        if (sessionAttempted) return Promise.resolve();
        sessionAttempted = true;

        const initData = telegramInitData();
        if (!initData || !axios) return Promise.resolve();

        return axios.post('/api/telegram/session', {initData: initData}, {withCredentials: true}).catch(() => {});
    }

    function init() {
        if (initialized) return Promise.resolve(service);
        if (initializing) return initializing;

        initializing = bootstrapSession()
            .then(fetchEntitlement)
            .catch(() => {})
            .then(() => {
                initialized = true;
                return service;
            });

        return initializing;
    }

    function snapshot() {
        return _.cloneDeep(entitlement);
    }

    function fetchPlans() {
        if (!axios) return Promise.resolve({settings: service.settings, plans: service.plans});

        return axios.get(endpoint('plans'), {withCredentials: true}).then(response => {
            const data = _.get(response, 'data.data', {});
            service.settings = normalizeSettings(data.settings || service.settings);
            service.plans = normalizePlans(data.plans || service.plans);
            return {settings: service.settings, plans: service.plans};
        });
    }

    function fetchEntitlement() {
        if (!axios) return Promise.resolve(snapshot());

        return axios.get(endpoint('entitlement'), {withCredentials: true}).then(response => {
            entitlement = normalizeEntitlement(_.get(response, 'data.data.entitlement', {}));
            return snapshot();
        });
    }

    function checkout(planCode) {
        if (!axios) return Promise.reject(new Error('Checkout API is unavailable.'));

        return bootstrapSession()
            .then(() => axios.post(endpoint('checkout'), {plan_code: planCode}, {withCredentials: true}))
            .then(response => _.get(response, 'data.data', {}));
    }

    function cancel() {
        if (!axios) return Promise.reject(new Error('Premium API is unavailable.'));

        return axios.post(endpoint('entitlement/cancel'), {}, {withCredentials: true}).then(response => {
            entitlement = normalizeEntitlement(_.get(response, 'data.data.entitlement', entitlement));
            return snapshot();
        });
    }

    function openInvoice(invoiceLink) {
        if (!invoiceLink) return;

        const webApp = _.get(GeckoClient, 'telegram.webApp') || _.get(window, 'Telegram.WebApp');
        if (_.isFunction(_.get(webApp, 'openInvoice'))) {
            webApp.openInvoice(invoiceLink, function () {
                fetchEntitlement().catch(() => {});
            });
            return;
        }
        if (_.isFunction(_.get(webApp, 'openTelegramLink'))) {
            webApp.openTelegramLink(invoiceLink);
            return;
        }

        window.location.href = invoiceLink;
    }

})(window, _, axios, GeckoClient);
