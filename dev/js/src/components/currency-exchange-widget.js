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

        if (asset) {
            return _.isString(asset) ? {from: asset} : _.cloneDeep(asset);
        }

        if (widgetOptions.symbolFallback && symbol) {
            return {from: symbol, _pendingCheck: true};
        }

        return null;
    }

    // Shared cache for the ChangeNOW ticker list so we only fetch once per page load.
    let tickerListPromise = null;

    function fetchChangeNowTickers() {
        if (tickerListPromise) {
            return tickerListPromise;
        }

        const apiUrl = (widgetOptions.checkAvailabilityUrl || '/api/changenow/currencies');

        tickerListPromise = fetch(apiUrl)
            .then(function (response) {
                if (!response.ok) {
                    return Promise.reject(new Error('HTTP ' + response.status));
                }
                return response.json();
            })
            .then(function (json) {
                const tickers = _.get(json, 'data.tickers', null);
                if (!Array.isArray(tickers)) {
                    return Promise.reject(new Error('unexpected response shape'));
                }
                return tickers;
            })
            .catch(function () {
                // On any error reset so the next call can retry, and signal failure.
                tickerListPromise = null;
                return null;
            });

        return tickerListPromise;
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
        data: function () {
            return {
                checkedSymbol: null,    // symbol that was checked
                checkedResult: null,    // true = listed, false = not listed, null = not yet checked
            };
        },
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
            _rawSupportedAsset: function () {
                return getSupportedAsset(this.currency);
            },
            supportedAsset: function () {
                const raw = this._rawSupportedAsset;
                if (!raw) return null;
                if (!raw._pendingCheck) return raw;
                // Waiting for async check or result is false.
                if (this.checkedResult === false) return null;
                if (this.checkedResult === true) {
                    return _.omit(raw, '_pendingCheck');
                }
                return null; // still checking
            },
            widgetStatus: function () {
                if (!this.isEnabled) return 'disabled';
                if (!this.currency) return 'loading';
                const raw = this._rawSupportedAsset;
                if (!raw) return 'unsupported';
                if (raw._pendingCheck) {
                    if (this.checkedResult === null) return 'checking';
                    if (this.checkedResult === false) return 'unsupported';
                    // checkedResult === true falls through to 'ready'
                }
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
        watch: {
            currency: {
                immediate: true,
                handler: function (newCurrency) {
                    if (!newCurrency) return;
                    const raw = getSupportedAsset(newCurrency);
                    if (!raw || !raw._pendingCheck) return;

                    const symbol = raw.from;
                    if (this.checkedSymbol === symbol) return; // already checked/checking

                    this.checkedSymbol = symbol;
                    this.checkedResult = null;

                    const self = this;
                    fetchChangeNowTickers().then(function (tickers) {
                        if (self.checkedSymbol !== symbol) return; // currency changed while fetching
                        if (tickers === null) {
                            // API unavailable — show widget optimistically (old behavior).
                            self.checkedResult = true;
                        } else {
                            self.checkedResult = tickers.indexOf(symbol) !== -1;
                        }
                    });
                }
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
