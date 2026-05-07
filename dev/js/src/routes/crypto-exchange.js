(function (window, document, _, GeckoClient) {
    'use strict';

    const routeConfig = GeckoClient.routesConfig['crypto-exchange'];
    if (!routeConfig) return;

    const setTitle = GeckoClient.setTitle;
    const options = GeckoClient.getOptions('crypto-exchange', {});
    const defaultFrom = sanitizeCurrency(options.defaultFrom, 'ton');
    const defaultTo = sanitizeCurrency(options.defaultTo, 'usdtton');
    const widgetBaseUrl = options.widgetBaseUrl || 'https://changenow.io/embeds/exchange-widget/v2/widget.html';
    const stepperScriptUrl = options.stepperScriptUrl || 'https://changenow.io/embeds/exchange-widget/v2/stepper-connector.js';
    const widgetDefaults = options.defaults || {};

    function sanitizeCurrency(value, fallback) {
        const sanitized = _.toLower(_.trim(value || '')).replace(/[^a-z0-9]/g, '');
        return sanitized || fallback || '';
    }

    function sanitizeAssetId(value) {
        return _.toLower(_.trim(value || '')).replace(/[^a-z0-9-]/g, '');
    }

    function sameQuery(left, right) {
        return ['from', 'to', 'asset'].every(key => (left[key] || '') === (right[key] || ''));
    }

    GeckoClient.router.addRoute({
        name: 'crypto-exchange',
        path: routeConfig.path,
        component: {
            template: '#route-crypto-exchange',
            data: function () {
                return {
                    fromInput: defaultFrom,
                    toInput: defaultTo,
                    assetId: '',
                    presets: options.presets || []
                };
            },
            computed: {
                normalizedFrom: function () {
                    return sanitizeCurrency(this.fromInput, defaultFrom);
                },
                normalizedTo: function () {
                    return sanitizeCurrency(this.toInput, defaultTo);
                },
                pairLabel: function () {
                    return this.assetLabel(this.normalizedFrom) + ' to ' + this.assetLabel(this.normalizedTo);
                },
                widgetSrc: function () {
                    return this.buildWidgetSrc(this.normalizedFrom, this.normalizedTo);
                }
            },
            created: function () {
                setTitle(options.title || 'Crypto Exchange');
                this.applyRouteQuery(this.$route.query || {});
            },
            mounted: function () {
                this.loadStepperConnector();
            },
            beforeRouteUpdate: function (to, from, next) {
                this.applyRouteQuery(to.query || {});
                next();
            },
            methods: {
                applyRouteQuery: function (query) {
                    this.fromInput = sanitizeCurrency(query.from, defaultFrom);
                    this.toInput = sanitizeCurrency(query.to, defaultTo);
                    this.assetId = sanitizeAssetId(query.asset || '');
                },
                routeQuery: function () {
                    const query = {
                        from: this.normalizedFrom,
                        to: this.normalizedTo
                    };

                    if (this.assetId) {
                        query.asset = this.assetId;
                    }

                    return query;
                },
                syncRoute: function () {
                    const query = this.routeQuery();
                    if (sameQuery(this.$route.query || {}, query)) return;

                    this.$router.replace({name: 'crypto-exchange', query}).catch(() => {});
                },
                swapPair: function () {
                    const from = this.normalizedFrom;
                    const to = this.normalizedTo;

                    this.fromInput = to;
                    this.toInput = from;
                    this.assetId = '';
                    this.syncRoute();
                },
                applyPreset: function (preset) {
                    this.fromInput = sanitizeCurrency(preset.from, defaultFrom);
                    this.toInput = sanitizeCurrency(preset.to, defaultTo);
                    this.assetId = sanitizeAssetId(preset.asset || '');
                    this.syncRoute();
                },
                assetLabel: function (value) {
                    const id = sanitizeCurrency(value, '');
                    return _.get(options.assetLabels, id) || _.toUpper(id);
                },
                buildWidgetSrc: function (from, to) {
                    const url = new URL(widgetBaseUrl);
                    const params = Object.assign({}, widgetDefaults, {
                        darkMode: this.$root && this.$root.darkTheme ? 'true' : 'false',
                        from: from,
                        link_id: options.linkId || '',
                        to: to
                    });

                    url.search = '';
                    Object.keys(params).forEach(key => url.searchParams.set(key, params[key]));

                    return url.toString();
                },
                loadStepperConnector: function () {
                    if (document.querySelector('script[data-changenow-stepper="true"]')) return;

                    const script = document.createElement('script');
                    script.defer = true;
                    script.type = 'text/javascript';
                    script.src = stepperScriptUrl;
                    script.setAttribute('data-changenow-stepper', 'true');
                    document.body.appendChild(script);
                }
            }
        }
    });

})(window, document, _, GeckoClient);
