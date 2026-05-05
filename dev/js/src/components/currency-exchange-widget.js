(function (window, document, _, Vue, GeckoClient) {
    'use strict';

    const currencyOptions = GeckoClient.getOptions('currency', {});
    const widgetOptions = currencyOptions.exchangeWidget || {};
    const defaultWidgetUrl = 'https://changenow.io/embeds/exchange-widget/v2/widget.html';
    const defaultConnectorScriptUrl = 'https://changenow.io/embeds/exchange-widget/v2/stepper-connector.js';

    function normalizeKey(value) {
        return _.toLower(_.trim(value || ''));
    }

    function normalizeColor(value, fallback) {
        value = _.trim(value || fallback || '').replace(/^#/, '');
        return /^[0-9a-f]{6}$/i.test(value) ? _.toLower(value) : fallback;
    }

    function getSupportedAsset(currency) {
        const supportedAssets = widgetOptions.supportedAssets || {};
        const ids = supportedAssets.ids || {};
        const symbols = supportedAssets.symbols || {};
        const id = normalizeKey(_.get(currency, 'id'));
        const symbol = normalizeKey(_.get(currency, 'symbol'));
        const asset = ids[id] || symbols[symbol] || null;

        if (!asset) return null;
        if (_.isString(asset)) return {from: asset};

        return _.cloneDeep(asset);
    }

    function buildWidgetUrl(baseUrl, params) {
        const url = new URL(baseUrl || defaultWidgetUrl, window.location.href);

        _.forOwn(params, (value, key) => {
            url.searchParams.set(key, value === null || value === undefined ? '' : String(value));
        });

        return url.toString();
    }

    Vue.component('gc-currency-exchange-widget', {
        props: {
            currency: {}
        },
        template: '#component-currency-exchange-widget',
        computed: {
            providerName: function () {
                return widgetOptions.provider || 'ChangeNOW';
            },
            isEnabled: function () {
                return widgetOptions.enabled === true && !!this.linkId;
            },
            linkId: function () {
                return _.trim(widgetOptions.linkId || '');
            },
            supportedAsset: function () {
                return getSupportedAsset(this.currency);
            },
            widgetStatus: function () {
                if (!this.isEnabled) return 'disabled';
                if (!this.currency) return 'loading';
                return this.supportedAsset ? 'ready' : 'unsupported';
            },
            isReady: function () {
                return this.widgetStatus === 'ready';
            },
            listingUrl: function () {
                return _.trim(widgetOptions.listingUrl || '');
            },
            assetLabel: function () {
                return this.supportedAsset && this.supportedAsset.label
                    ? this.supportedAsset.label
                    : _.get(this.currency, 'name', this.providerName);
            },
            targetLabel: function () {
                return this.supportedAsset && this.supportedAsset.toLabel
                    ? this.supportedAsset.toLabel
                    : 'USDT on TON';
            },
            iframeTitle: function () {
                return this.providerName + ' exchange widget for ' + this.assetLabel;
            },
            iframeSrc: function () {
                if (!this.isReady) return null;

                const defaults = widgetOptions.defaults || {};
                const target = this.supportedAsset.to || defaults.to || 'usdtton';
                const params = Object.assign({}, defaults, {
                    from: this.supportedAsset.from,
                    to: target,
                    link_id: this.linkId,
                    primaryColor: normalizeColor(defaults.primaryColor, '1bb2da'),
                    backgroundColor: normalizeColor(defaults.backgroundColor, 'f6fafd')
                });

                return buildWidgetUrl(widgetOptions.widgetUrl, params);
            }
        },
        mounted: function () {
            if (this.isReady) {
                this.ensureConnectorScript();
            }
        },
        updated: function () {
            if (this.isReady) {
                this.ensureConnectorScript();
            }
        },
        methods: {
            ensureConnectorScript: function () {
                const scriptUrl = widgetOptions.connectorScriptUrl || defaultConnectorScriptUrl;
                if (!scriptUrl || document.querySelector('script[data-tbc-changenow-connector="true"]')) {
                    return;
                }

                const script = document.createElement('script');
                script.src = scriptUrl;
                script.defer = true;
                script.setAttribute('data-tbc-changenow-connector', 'true');
                document.body.appendChild(script);
            }
        }
    });

})(window, document, _, Vue, GeckoClient);
