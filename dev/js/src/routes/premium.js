(function (window, _, GeckoClient) {
    'use strict';

    const route = GeckoClient.routesConfig.premium;
    const appRoute = GeckoClient.routesConfig['app-premium'];
    if (!route && !appRoute) return;

    const options = GeckoClient.getOptions('premium', {title: 'Premium'});

    function createComponent(templateId) {
        return {
            template: templateId,
            data: function () {
                return {
                    plans: GeckoClient.premium ? GeckoClient.premium.plans : [],
                    settings: GeckoClient.premium ? GeckoClient.premium.settings : {},
                    entitlement: GeckoClient.premium ? GeckoClient.premium.entitlement() : {},
                    loading: false,
                    canceling: false,
                    checkoutPlanCode: '',
                    notice: '',
                    noticeType: 'info'
                };
            },
            created: function () {
                GeckoClient.setTitle(options.title || 'Premium');
                this.initPremium();
            },
            computed: {
                checkoutEnabled: function () {
                    return this.settings.checkout_enabled === true;
                },
                entitlementLabel: function () {
                    if (_.get(this.entitlement, 'entitled') === true) return 'Premium active';
                    return 'Free limits';
                },
                currentPlanCode: function () {
                    return _.get(this.entitlement, 'plan_code') || 'free';
                },
                entitlementExpiresLabel: function () {
                    const expiresAt = _.get(this.entitlement, 'entitlement.expires_at');
                    if (!expiresAt) return 'No expiry';
                    const date = new Date(expiresAt);
                    return GeckoClient.utils.isValidDate(date) ? date.toISOString().slice(0, 10) : expiresAt;
                },
                priorityRefreshLabel: function () {
                    return _.get(this.entitlement, 'limits.priority_refresh') === true ? 'Enabled' : 'Standard';
                },
                canCancel: function () {
                    return _.get(this.entitlement, 'entitled') === true && _.get(this.entitlement, 'entitlement.cancel_at_period_end') !== true;
                }
            },
            methods: {
                initPremium: function () {
                    if (!GeckoClient.premium) return;
                    this.loading = true;
                    GeckoClient.premium.init()
                        .then(() => GeckoClient.premium.fetchPlans())
                        .then(result => {
                            this.settings = result.settings || this.settings;
                            this.plans = result.plans || this.plans;
                            this.entitlement = GeckoClient.premium.entitlement();
                        })
                        .catch(() => {
                            this.showNotice('Premium status could not be loaded.', 'warning');
                        })
                        .finally(() => this.loading = false);
                },
                refreshEntitlement: function () {
                    if (!GeckoClient.premium) return;
                    this.loading = true;
                    GeckoClient.premium.fetchEntitlement()
                        .then(entitlement => {
                            this.entitlement = entitlement;
                            this.showNotice('Premium status refreshed.', 'success');
                        })
                        .catch(() => this.showNotice('Premium status could not be refreshed.', 'warning'))
                        .finally(() => this.loading = false);
                },
                startCheckout: function (plan) {
                    if (!GeckoClient.premium || !plan || this.checkoutPlanCode) return;
                    this.checkoutPlanCode = plan.code;
                    GeckoClient.premium.checkout(plan.code)
                        .then(data => {
                            if (data.invoice_link) {
                                GeckoClient.premium.openInvoice(data.invoice_link);
                                this.showNotice('Telegram Stars invoice opened.', 'success');
                            } else {
                                this.showNotice('Invoice link was not returned.', 'warning');
                            }
                        })
                        .catch(() => this.showNotice('Stars checkout could not be started. Check your Telegram session.', 'warning'))
                        .finally(() => this.checkoutPlanCode = '');
                },
                cancelEntitlement: function () {
                    if (!GeckoClient.premium || !this.canCancel || this.canceling) return;
                    this.canceling = true;
                    GeckoClient.premium.cancel()
                        .then(entitlement => {
                            this.entitlement = entitlement;
                            this.showNotice('Premium renewal cancellation recorded.', 'success');
                        })
                        .catch(() => this.showNotice('Premium renewal could not be canceled.', 'warning'))
                        .finally(() => this.canceling = false);
                },
                showNotice: function (message, type) {
                    this.notice = message;
                    this.noticeType = type || 'info';
                },
                isCurrentPlan: function (plan) {
                    return plan && plan.code === this.currentPlanCode;
                },
                priceLabel: function (plan) {
                    if (!plan || !plan.price_stars) return 'Free';
                    return plan.price_stars + ' Stars';
                },
                rangeLabel: function (ranges) {
                    return (ranges || []).map(range => String(range).toUpperCase()).join(', ');
                },
                secondsLabel: function (seconds) {
                    seconds = parseInt(seconds, 10) || 0;
                    if (seconds <= 0) return 'Standard';
                    if (seconds < 60) return seconds + 's';
                    if (seconds < 3600) return Math.round(seconds / 60) + 'm';
                    return Math.round(seconds / 3600) + 'h';
                },
                limitRows: function (plan) {
                    const limits = _.get(plan, 'limits', {});
                    return [
                        {key: 'alerts', icon: 'mdi-bell-outline', label: 'Alerts', value: (limits.alerts_per_user || 0) + ' rules'},
                        {key: 'watchlist', icon: 'mdi-star-outline', label: 'Watchlist', value: (limits.watchlist_entries || 0) + ' assets'},
                        {key: 'ranges', icon: 'mdi-chart-timeline-variant', label: 'Ranges', value: this.rangeLabel(limits.advanced_ranges)},
                        {key: 'digest', icon: 'mdi-brain', label: 'AI digest', value: (limits.ai_digest_per_day || 0) + '/day'},
                        {key: 'refresh', icon: 'mdi-refresh', label: 'Refresh', value: limits.priority_refresh ? this.secondsLabel(limits.market_refresh_seconds) : 'Standard'}
                    ];
                }
            }
        };
    }

    if (route) {
        GeckoClient.router.addRoute({
            name: 'premium',
            path: route.path,
            component: createComponent('#route-premium')
        });
    }

    if (appRoute) {
        GeckoClient.router.addRoute({
            name: 'app-premium',
            path: appRoute.path,
            component: createComponent('#route-app-premium')
        });
    }

})(window, _, GeckoClient);
