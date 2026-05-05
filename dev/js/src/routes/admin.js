(function (window, _, axios, GeckoClient) {
    'use strict';

    const route = GeckoClient.routesConfig.admin;
    if (!route) return;

    const options = GeckoClient.getOptions('admin', {title: 'Admin Panel', apiBaseUrl: '/api/admin'});
    const tokenStorageKey = 'TONBANKCARD:adminToken';
    const adminRouteNames = [
        'admin',
        'admin-providers',
        'admin-feature-flags',
        'admin-ton-assets',
        'admin-legal-copy',
        'admin-alerts',
        'admin-cache',
        'admin-audit-log'
    ];

    function clone(value) {
        return JSON.parse(JSON.stringify(value || {}));
    }

    function defaultProviders() {
        return {
            coingecko: {api_plan: 'demo', api_key: {configured: false}},
            groq: {model_id: 'llama-3.3-70b-versatile', api_key: {configured: false}},
            upstash: {status: 'not_configured', rest_url_configured: false, rest_token: {configured: false}},
            telegram: {bot_username: '', bot_token: {configured: false}},
            changenow: {link_id: ''}
        };
    }

    function defaultContent() {
        return {
            legal_copy: {
                market_data_disclaimer: '',
                ai_disclaimer: '',
                alerts_disclaimer: '',
                widget_disclaimer: ''
            },
            ton_assets: []
        };
    }

    function defaultOperations() {
        return {
            cache: {
                market_stale_mode: 'serve_stale',
                purge_status: 'idle',
                last_purge_scope: null,
                last_purge_at: null
            },
            alert_thresholds: {
                max_alerts_per_user: 20,
                default_frequency_cap_seconds: 3600,
                default_max_deliveries_per_day: 8,
                evaluation_interval_seconds: 300
            },
            achievements: {
                enabled: false,
                daily_streak_points: 10,
                share_points: 5,
                alert_points: 10
            }
        };
    }

    function component() {
        return {
            template: '#route-admin',
            data: function () {
                return {
                    token: localStorage.getItem(tokenStorageKey) || '',
                    loginToken: '',
                    actor: {},
                    meta: {},
                    featureFlags: {},
                    providers: defaultProviders(),
                    providerSecrets: {
                        coingecko: {api_key: ''},
                        groq: {api_key: ''},
                        upstash: {rest_token: ''},
                        telegram: {bot_token: ''}
                    },
                    content: defaultContent(),
                    operations: defaultOperations(),
                    auditLog: [],
                    loading: false,
                    saving: false,
                    notice: '',
                    noticeType: 'info',
                    cacheScope: 'all'
                };
            },
            created: function () {
                GeckoClient.setTitle(options.title || 'Admin Panel');
                if (this.token) this.loadConfig();
            },
            computed: {
                authenticated: function () {
                    return !!this.actor.role && !!this.token;
                },
                canWrite: function () {
                    return _.get(this.actor, 'permissions.write') === true;
                },
                activeSection: function () {
                    const name = _.get(this.$route, 'name') || 'admin';
                    const map = {
                        'admin': 'overview',
                        'admin-providers': 'providers',
                        'admin-feature-flags': 'flags',
                        'admin-ton-assets': 'ton-assets',
                        'admin-legal-copy': 'legal-copy',
                        'admin-alerts': 'operations',
                        'admin-cache': 'overview',
                        'admin-audit-log': 'audit-log'
                    };

                    return map[name] || 'overview';
                },
                storeLabel: function () {
                    if (_.get(this.meta, 'store_configured')) return _.get(this.meta, 'store_loaded') ? 'store loaded' : 'store ready';
                    return 'store missing';
                },
                flagDefinitions: function () {
                    return [
                        {key: 'ai', label: 'AI'},
                        {key: 'alerts', label: 'Alerts'},
                        {key: 'widget', label: 'Widget'},
                        {key: 'ton_connect', label: 'TON Connect'},
                        {key: 'gamification', label: 'Gamification'},
                        {key: 'referrals', label: 'Referrals'},
                        {key: 'premium', label: 'Premium'}
                    ];
                },
                legalCopyDefinitions: function () {
                    return [
                        {key: 'market_data_disclaimer', label: 'Market data disclaimer'},
                        {key: 'ai_disclaimer', label: 'AI disclaimer'},
                        {key: 'alerts_disclaimer', label: 'Alerts disclaimer'},
                        {key: 'widget_disclaimer', label: 'Widget disclaimer'}
                    ];
                },
                coingeckoPlans: function () {
                    return ['demo', 'pro'];
                },
                providerStatusOptions: function () {
                    return ['not_configured', 'configured', 'enabled', 'degraded', 'disabled'];
                },
                cacheModeOptions: function () {
                    return ['serve_stale', 'strict', 'bypass'];
                },
                tonCategories: function () {
                    return ['native', 'stablecoin', 'jetton', 'defi', 'wallet', 'infrastructure', 'community'];
                },
                tonVerificationStates: function () {
                    return ['verified', 'curated', 'unverified'];
                },
                overviewMetrics: function () {
                    const flags = this.flagDefinitions.filter(flag => this.featureFlags[flag.key] === true).length;
                    const providers = [
                        _.get(this.providers, 'groq.api_key.configured'),
                        _.get(this.providers, 'coingecko.api_key.configured'),
                        _.get(this.providers, 'upstash.rest_token.configured'),
                        _.get(this.providers, 'telegram.bot_token.configured')
                    ].filter(Boolean).length;

                    return [
                        {label: 'Enabled flags', value: String(flags)},
                        {label: 'Configured secrets', value: String(providers)},
                        {label: 'TON assets', value: String((this.content.ton_assets || []).length)},
                        {label: 'Audit entries', value: String((this.auditLog || []).length)}
                    ];
                },
                cacheStatusLabel: function () {
                    const cache = this.operations.cache || {};
                    if (!cache.last_purge_at) return 'Purge status: ' + (cache.purge_status || 'idle');
                    return 'Purge status: ' + cache.purge_status + ' at ' + this.formatDate(cache.last_purge_at);
                },
                auditHeaders: function () {
                    return [
                        {text: 'Time', value: 'created_at'},
                        {text: 'Actor', value: 'actor'},
                        {text: 'Action', value: 'action'},
                        {text: 'Subject', value: 'subject_type'},
                        {text: 'Request', value: 'request_id'}
                    ];
                }
            },
            methods: {
                client: function () {
                    return axios.create({
                        baseURL: options.apiBaseUrl || '/api/admin',
                        headers: this.token ? {Authorization: 'Bearer ' + this.token} : {}
                    });
                },
                login: function () {
                    if (!this.loginToken || this.loading) return;

                    this.loading = true;
                    this.notice = '';
                    axios.post((options.apiBaseUrl || '/api/admin') + '/session', {token: this.loginToken})
                        .then(response => {
                            this.token = this.loginToken;
                            localStorage.setItem(tokenStorageKey, this.token);
                            this.actor = _.get(response, 'data.data.actor') || {};
                            this.loginToken = '';
                            return this.loadConfig();
                        })
                        .catch(error => this.handleError(error, 'Admin access failed.'))
                        .finally(() => this.loading = false);
                },
                logout: function () {
                    localStorage.removeItem(tokenStorageKey);
                    this.token = '';
                    this.loginToken = '';
                    this.actor = {};
                    this.meta = {};
                    this.auditLog = [];
                    this.notice = '';
                },
                loadConfig: function () {
                    if (!this.token) return Promise.resolve();

                    this.loading = true;
                    return this.client().get('/config')
                        .then(response => {
                            this.applyConfig(_.get(response, 'data.data') || {});
                            this.showNotice('Admin configuration loaded.', 'success');
                        })
                        .catch(error => {
                            if (_.get(error, 'response.status') === 401) this.logout();
                            this.handleError(error, 'Admin configuration could not be loaded.');
                        })
                        .finally(() => this.loading = false);
                },
                applyConfig: function (data) {
                    const providers = _.merge(defaultProviders(), clone(data.providers));
                    const content = _.merge(defaultContent(), clone(data.content));
                    const operations = _.merge(defaultOperations(), clone(data.operations));

                    content.ton_assets = (content.ton_assets || []).map((asset, index) => {
                        asset.local_id = asset.local_id || ('ton-asset-' + index + '-' + Date.now());
                        asset.tags = Array.isArray(asset.tags) ? asset.tags : [];
                        return asset;
                    });

                    this.actor = clone(data.actor || this.actor);
                    this.meta = clone(data.meta || {});
                    this.featureFlags = clone(data.feature_flags || {});
                    this.providers = providers;
                    this.content = content;
                    this.operations = operations;
                    this.auditLog = clone(data.audit_log || []);
                    this.clearProviderSecrets();
                },
                clearProviderSecrets: function () {
                    this.providerSecrets = {
                        coingecko: {api_key: ''},
                        groq: {api_key: ''},
                        upstash: {rest_token: ''},
                        telegram: {bot_token: ''}
                    };
                },
                saveFeatureFlags: function () {
                    if (!this.canWrite) return;
                    this.write('/feature-flags', {feature_flags: this.featureFlags}, 'Feature flags saved.')
                        .then(data => {
                            if (data.feature_flags) this.featureFlags = clone(data.feature_flags);
                        });
                },
                saveProviders: function () {
                    if (!this.canWrite) return;

                    const payload = {
                        coingecko: {
                            api_plan: _.get(this.providers, 'coingecko.api_plan')
                        },
                        groq: {
                            model_id: _.get(this.providers, 'groq.model_id')
                        },
                        upstash: {
                            status: _.get(this.providers, 'upstash.status')
                        },
                        telegram: {
                            bot_username: _.get(this.providers, 'telegram.bot_username')
                        },
                        changenow: {
                            link_id: _.get(this.providers, 'changenow.link_id')
                        }
                    };

                    if (this.providerSecrets.coingecko.api_key) payload.coingecko.api_key = this.providerSecrets.coingecko.api_key;
                    if (this.providerSecrets.groq.api_key) payload.groq.api_key = this.providerSecrets.groq.api_key;
                    if (this.providerSecrets.upstash.rest_token) payload.upstash.rest_token = this.providerSecrets.upstash.rest_token;
                    if (this.providerSecrets.telegram.bot_token) payload.telegram.bot_token = this.providerSecrets.telegram.bot_token;

                    this.write('/providers', {providers: payload}, 'Providers saved.')
                        .then(data => {
                            if (data.providers) this.providers = _.merge(defaultProviders(), clone(data.providers));
                            this.clearProviderSecrets();
                        });
                },
                saveContent: function () {
                    if (!this.canWrite) return;
                    const content = clone(this.content);
                    content.ton_assets = (content.ton_assets || []).map(asset => {
                        delete asset.local_id;
                        return asset;
                    });

                    this.write('/content', {content: content}, 'Content saved.')
                        .then(data => {
                            if (data.content) {
                                this.content = _.merge(defaultContent(), clone(data.content));
                                this.content.ton_assets = (this.content.ton_assets || []).map((asset, index) => {
                                    asset.local_id = asset.local_id || ('ton-asset-' + index + '-' + Date.now());
                                    return asset;
                                });
                            }
                        });
                },
                saveOperations: function () {
                    if (!this.canWrite) return;
                    this.write('/operations', {operations: this.operations}, 'Operations saved.')
                        .then(data => {
                            if (data.operations) this.operations = _.merge(defaultOperations(), clone(data.operations));
                        });
                },
                purgeCache: function () {
                    if (!this.canWrite) return;
                    this.saving = true;
                    this.client().post('/cache/purge', {scope: this.cacheScope || 'all'})
                        .then(response => {
                            const data = _.get(response, 'data.data') || {};
                            if (data.cache) this.operations.cache = clone(data.cache);
                            this.loadAuditLog();
                            this.showNotice('Cache purge requested.', 'success');
                        })
                        .catch(error => this.handleError(error, 'Cache purge could not be requested.'))
                        .finally(() => this.saving = false);
                },
                write: function (path, payload, message) {
                    this.saving = true;
                    this.notice = '';

                    return this.client().put(path, payload)
                        .then(response => {
                            const data = _.get(response, 'data.data') || {};
                            this.loadAuditLog();
                            this.showNotice(message, 'success');
                            return data;
                        })
                        .catch(error => {
                            this.handleError(error, 'Admin change could not be saved.');
                            return {};
                        })
                        .finally(() => this.saving = false);
                },
                loadAuditLog: function () {
                    if (!this.token) return;
                    this.client().get('/audit-log')
                        .then(response => {
                            this.auditLog = clone(_.get(response, 'data.data.audit_log') || []);
                        })
                        .catch(() => {});
                },
                addTonAsset: function () {
                    if (!this.canWrite) return;
                    this.content.ton_assets.push({
                        local_id: 'ton-asset-' + Date.now(),
                        id: '',
                        name: '',
                        symbol: '',
                        category: 'jetton',
                        verification_state: 'curated',
                        tags: ['ton_ecosystem']
                    });
                },
                removeTonAsset: function (index) {
                    if (!this.canWrite) return;
                    this.content.ton_assets.splice(index, 1);
                },
                secretStatus: function (metadata) {
                    if (_.get(metadata, 'configured')) return 'configured';
                    return 'not configured';
                },
                secretPlaceholder: function (metadata) {
                    if (_.get(metadata, 'configured')) return '[redacted]';
                    return '';
                },
                formatDate: function (value) {
                    const date = new Date(value || 0);
                    if (!GeckoClient.utils.isValidDate(date)) return 'N/A';
                    return date.toLocaleString();
                },
                showNotice: function (message, type) {
                    this.notice = message;
                    this.noticeType = type || 'info';
                },
                handleError: function (error, fallback) {
                    const message = _.get(error, 'response.data.error.message') || fallback;
                    this.showNotice(message, 'error');
                }
            }
        };
    }

    adminRouteNames.forEach(routeName => {
        const config = GeckoClient.routesConfig[routeName];
        if (!config) return;

        GeckoClient.router.addRoute({
            name: routeName,
            path: config.path,
            component: component()
        });
    });

})(window, _, axios, GeckoClient);
