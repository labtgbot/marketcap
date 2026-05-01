(function (window, _, GeckoClient) {
    'use strict';

    const route = GeckoClient.routesConfig.alerts;
    if (!route) return;

    const options = GeckoClient.getOptions('alerts', {title: 'Alerts'});

    const component = {
        template: '#route-alerts',
        data: function () {
            return {
                rules: [],
                form: GeckoClient.alerts ? GeckoClient.alerts.emptyRule() : {},
                editingId: null,
                saving: false,
                testingId: null,
                notice: '',
                noticeModel: false,
                testResult: null,
                alertsUnsubscribe: null
            };
        },
        created: function () {
            GeckoClient.setTitle(options.title || 'Alerts');
            this.initAlerts();
        },
        beforeDestroy: function () {
            if (this.alertsUnsubscribe) this.alertsUnsubscribe();
        },
        watch: {
            '$route': function () {
                this.applyRouteContext();
            },
            'form.trigger_type': function (type) {
                if (this.form.threshold !== null && this.form.threshold !== '') return;
                this.form.threshold = this.defaultThreshold(type);
            }
        },
        computed: {
            featureEnabled: function () {
                return _.get(GeckoClient, 'runtime.features.alerts') === true;
            },
            storageModeLabel: function () {
                const mode = GeckoClient.alerts ? GeckoClient.alerts.storageMode : 'local';
                if (mode === 'server') return 'Telegram session';
                if (mode === 'memory') return 'Memory fallback';
                return 'Local draft';
            },
            activeCount: function () {
                return this.rules.filter(rule => rule.status === 'active').length;
            },
            pausedCount: function () {
                return this.rules.filter(rule => rule.status === 'paused').length;
            },
            sortedRules: function () {
                return this.rules.slice().sort((a, b) => {
                    const aTime = new Date(a.updated_at || a.created_at || 0).getTime() || 0;
                    const bTime = new Date(b.updated_at || b.created_at || 0).getTime() || 0;
                    return bTime - aTime;
                });
            },
            detailRule: function () {
                const id = _.get(this.$route, 'params.id');
                if (!id) return null;
                return _.find(this.rules, rule => String(rule.id) === String(id)) || null;
            },
            triggerTypes: function () {
                return [
                    {text: 'Price cross', value: 'price_cross'},
                    {text: 'Percent move', value: 'percent_move'},
                    {text: 'Volume spike', value: 'volume_spike'},
                    {text: 'Rank change', value: 'rank_change'},
                    {text: 'Sentiment change', value: 'sentiment_change'},
                    {text: 'Market cap cross', value: 'market_cap_cross'},
                    {text: 'TON ecosystem', value: 'ton_ecosystem'}
                ];
            },
            operatorOptions: function () {
                return [
                    {text: '>=', value: 'gte'},
                    {text: '>', value: 'gt'},
                    {text: '<=', value: 'lte'},
                    {text: '<', value: 'lt'}
                ];
            },
            thresholdLabel: function () {
                const labels = {
                    price_cross: 'Price threshold',
                    percent_move: '24h move percent',
                    volume_spike: '24h volume threshold',
                    rank_change: 'Rank threshold',
                    sentiment_change: 'Sentiment score',
                    market_cap_cross: 'Market cap threshold',
                    ton_ecosystem: 'TON move percent'
                };
                return labels[this.form.trigger_type] || 'Threshold';
            },
            saveLabel: function () {
                return this.editingId ? 'Update alert' : 'Create alert';
            },
            alertsShareCard: function () {
                return {
                    title: 'Alert wins',
                    subtitle: this.activeCount + ' active alerts',
                    body: 'Smart alert rules with Telegram delivery, test links, and coin context.',
                    route: '/alerts',
                    campaign: 'alert-wins',
                    context: 'alert_win',
                    freshness: 'Updated in browser session',
                    metrics: [
                        {label: 'Active', value: String(this.activeCount)},
                        {label: 'Paused', value: String(this.pausedCount)},
                        {label: 'Storage', value: this.storageModeLabel},
                        {label: 'Delivery', value: this.featureEnabled ? 'Enabled' : 'Flag off'}
                    ]
                };
            },
            testResultShareCard: function () {
                const route = _.get(this.testResult, 'links.mini_app_path') || '/alerts';

                return {
                    title: 'Alert win',
                    subtitle: 'Test delivery ready',
                    body: _.get(this.testResult, 'text') || 'A TONBANKCARD alert delivery is ready to open.',
                    route: route,
                    campaign: 'alert-wins',
                    context: 'alert_win',
                    freshness: 'Generated now',
                    metrics: [
                        {label: 'Route', value: route},
                        {label: 'Channel', value: 'Telegram bot'},
                        {label: 'Mode', value: 'Test delivery'},
                        {label: 'Storage', value: this.storageModeLabel}
                    ]
                };
            }
        },
        methods: {
            initAlerts: function () {
                if (!GeckoClient.alerts) return;

                this.alertsUnsubscribe = GeckoClient.alerts.onChange(snapshot => {
                    this.rules = snapshot.rules || [];
                });

                GeckoClient.alerts.init().then(() => {
                    this.rules = GeckoClient.alerts.list();
                    this.applyRouteContext();
                });
            },
            applyRouteContext: function () {
                const draft = GeckoClient.alerts ? GeckoClient.alerts.readDraft() : null;
                const queryCoin = _.get(this.$route, 'query.coin');

                if (draft) {
                    this.form = this.ruleToForm(Object.assign({}, draft, {
                        trigger_type: draft.trigger_type || 'price_cross',
                        threshold: draft.threshold || null,
                        context_path: draft.context_path || (draft.coin_id ? '/currency/' + draft.coin_id : null)
                    }));
                    if (GeckoClient.alerts) GeckoClient.alerts.clearDraft();
                    return;
                }

                if (queryCoin && !this.editingId) {
                    this.form.coin_id = queryCoin;
                    this.form.symbol = _.toUpper(_.get(this.$route, 'query.symbol') || this.form.symbol || '');
                    this.form.context_path = '/currency/' + queryCoin;
                }

                if (this.detailRule) {
                    this.startEdit(this.detailRule);
                }
            },
            ruleToForm: function (rule) {
                const normalized = GeckoClient.alerts.normalizeRule(rule) || GeckoClient.alerts.emptyRule();
                return Object.assign({}, normalized);
            },
            resetForm: function () {
                this.editingId = null;
                this.form = GeckoClient.alerts.emptyRule();
                this.testResult = null;
            },
            saveRule: function () {
                if (!GeckoClient.alerts || this.saving) return;

                this.saving = true;
                const payload = Object.assign({}, this.form, {
                    id: this.editingId || this.form.id,
                    context_path: this.form.context_path || '/currency/' + this.form.coin_id
                });

                GeckoClient.alerts.save(payload, {sourceRoute: 'alerts'})
                    .then(rule => {
                        this.rules = GeckoClient.alerts.list();
                        this.editingId = rule.id;
                        this.form = this.ruleToForm(rule);
                        this.showNotice((payload.id ? 'Updated' : 'Created') + ' alert for ' + this.ruleSymbol(rule) + '.');
                    })
                    .catch(() => {
                        this.showNotice('Alert could not be saved. Check the rule fields and Telegram session.');
                    })
                    .finally(() => this.saving = false);
            },
            startEdit: function (rule) {
                this.editingId = rule.id || rule.local_id;
                this.form = this.ruleToForm(rule);
                this.testResult = null;
            },
            pauseRule: function (rule) {
                if (!GeckoClient.alerts) return;
                GeckoClient.alerts.pause(rule, {sourceRoute: 'alerts'}).then(saved => {
                    this.rules = GeckoClient.alerts.list();
                    this.showNotice('Paused alert for ' + this.ruleSymbol(saved) + '.');
                });
            },
            resumeRule: function (rule) {
                if (!GeckoClient.alerts) return;
                GeckoClient.alerts.resume(rule, {sourceRoute: 'alerts'}).then(saved => {
                    this.rules = GeckoClient.alerts.list();
                    this.showNotice('Resumed alert for ' + this.ruleSymbol(saved) + '.');
                });
            },
            deleteRule: function (rule) {
                if (!GeckoClient.alerts) return;
                GeckoClient.alerts.remove(rule, {sourceRoute: 'alerts'}).then(() => {
                    this.rules = GeckoClient.alerts.list();
                    if (String(this.editingId) === String(rule.id || rule.local_id)) this.resetForm();
                    this.showNotice('Deleted alert for ' + this.ruleSymbol(rule) + '.');
                });
            },
            testRule: function (rule) {
                if (!GeckoClient.alerts) return;
                this.testingId = rule.id || rule.local_id || 'form';
                GeckoClient.alerts.test(rule, {sourceRoute: 'alerts'})
                    .then(delivery => {
                        this.testResult = delivery;
                        this.showNotice('Test alert prepared for ' + this.ruleSymbol(rule) + '.');
                    })
                    .catch(() => {
                        this.showNotice('Test alert could not be prepared.');
                    })
                    .finally(() => this.testingId = null);
            },
            testCurrentForm: function () {
                const rule = GeckoClient.alerts.normalizeRule(this.form);
                if (rule) this.testRule(rule);
            },
            defaultThreshold: function (type) {
                const defaults = {
                    price_cross: 1,
                    percent_move: 5,
                    volume_spike: 1000000,
                    rank_change: 10,
                    sentiment_change: 50,
                    market_cap_cross: 1000000000,
                    ton_ecosystem: 5
                };
                return defaults[type] || 1;
            },
            ruleSymbol: function (rule) {
                return rule.symbol || rule.display_name || _.startCase(rule.coin_id || 'asset');
            },
            triggerTypeLabel: function (type) {
                const item = _.find(this.triggerTypes, ['value', type]);
                return item ? item.text : _.startCase(type || '');
            },
            operatorLabel: function (operator) {
                const item = _.find(this.operatorOptions, ['value', operator]);
                return item ? item.text : operator;
            },
            capLabel: function (seconds) {
                seconds = parseInt(seconds || 0, 10);
                if (seconds >= 3600) return Math.round(seconds / 3600) + 'h cap';
                return Math.round(seconds / 60) + 'm cap';
            },
            deliveryPath: function (rule) {
                return _.get(rule, 'links.mini_app_path') || '/app/alerts?coin=' + encodeURIComponent(rule.coin_id);
            },
            alertShareCard: function (rule) {
                if (!rule) return this.alertsShareCard;

                const symbol = this.ruleSymbol(rule);
                const route = rule.id ? '/app/alert/' + encodeURIComponent(rule.id) : this.deliveryPath(rule);

                return {
                    title: symbol + ' alert win',
                    subtitle: this.triggerTypeLabel(rule.trigger_type),
                    body: symbol + ' alert ' + this.operatorLabel(rule.operator) + ' ' + rule.threshold + ' on TONBANKCARD.',
                    route: route,
                    campaign: 'alert-wins',
                    context: 'alert_win',
                    freshness: rule.updated_at ? 'Updated ' + this.relativeTime(rule.updated_at) : 'Saved alert rule',
                    metrics: [
                        {label: 'Coin', value: rule.coin_id || symbol},
                        {label: 'Trigger', value: this.triggerTypeLabel(rule.trigger_type)},
                        {label: 'Status', value: rule.status || 'active'},
                        {label: 'Cap', value: this.capLabel(rule.frequency_cap_seconds)}
                    ]
                };
            },
            shareAlert: function (ruleOrCard) {
                if (!GeckoClient.share) return;

                const card = ruleOrCard && ruleOrCard.context ? ruleOrCard : this.alertShareCard(ruleOrCard);
                GeckoClient.share.share(card);
            },
            openCoinRoute: function (rule) {
                return {name: 'currency', params: {id: rule.coin_id}};
            },
            relativeTime: function (timestamp) {
                const date = new Date(timestamp);
                if (!GeckoClient.utils.isValidDate(date)) return 'unknown';

                const seconds = Math.max(0, Math.floor((Date.now() - date.getTime()) / 1000));
                if (seconds < 60) return 'now';
                if (seconds < 3600) return Math.floor(seconds / 60) + 'm ago';
                if (seconds < 86400) return Math.floor(seconds / 3600) + 'h ago';
                return Math.floor(seconds / 86400) + 'd ago';
            },
            showNotice: function (message) {
                this.notice = message;
                this.noticeModel = true;
            }
        }
    };

    GeckoClient.router.addRoute({
        name: 'alerts',
        path: route.path,
        component: component
    });

    if (GeckoClient.routesConfig['app-alerts']) {
        GeckoClient.router.addRoute({
            name: 'app-alerts',
            path: GeckoClient.routesConfig['app-alerts'].path,
            component: component
        });
    }

    if (GeckoClient.routesConfig['app-alert-detail']) {
        GeckoClient.router.addRoute({
            name: 'app-alert-detail',
            path: GeckoClient.routesConfig['app-alert-detail'].path,
            component: component
        });
    }

})(window, _, GeckoClient);
