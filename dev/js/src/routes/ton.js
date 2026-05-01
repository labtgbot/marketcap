(function (window, _, CoinGecko, GeckoClient) {
    'use strict';

    const route = GeckoClient.routesConfig.ton;
    const options = GeckoClient.getOptions('ton');
    if (!route) return;

    GeckoClient.router.addRoute({
        name: 'ton',
        path: route.path,
        component: {
            template: '#route-ton',
            data: function () {
                return {
                    tonCurrencies: [],
                    tonMeta: null,
                    tonConfig: null,
                    loadingTonMarkets: false
                };
            },
            created: function () {
                GeckoClient.setTitle(options.title);
                this.fetchTonMarkets();
            },
            watch: {
                '$root.vsCurrencyId': function () {
                    this.fetchTonMarkets();
                }
            },
            computed: {
                tonInsightContext: function () {
                    if (!GeckoClient.ai || !this.tonCurrencies.length) return null;

                    return {
                        insight_type: 'ton_ecosystem_pulse',
                        subject: 'TON ecosystem pulse',
                        market_data_age_seconds: GeckoClient.ai.marketDataAgeSeconds(this.tonMeta),
                        market_data_updated_at: GeckoClient.ai.marketDataUpdatedAt(this.tonMeta),
                        market_data: {
                            vs_currency: this.$root.vsCurrencyId,
                            assets: this.tonCurrencies.map(currency => GeckoClient.ai.marketCurrencySnapshot(currency)),
                            coverage: [
                                'Toncoin and core assets',
                                'Telegram-native discovery',
                                'Risk-aware market context'
                            ]
                        }
                    };
                }
            },
            methods: {
                fetchTonMarkets: function () {
                    const ids = options.tonCoinIds || ['toncoin'];
                    const params = {
                        ids: ids.join(','),
                        per_page: Math.min(50, ids.length),
                        page: 1,
                        order: 'market_cap_desc',
                        vs_currency: this.$root.vsCurrencyId,
                        price_change_percentage: '24h,7d,30d',
                        sparkline: false
                    };

                    this.tonConfig = {params: params};
                    this.loadingTonMarkets = true;

                    return CoinGecko.coinsMarkets(params)
                        .then(currencies => {
                            this.tonCurrencies = currencies || [];
                            this.tonMeta = CoinGecko.metaGet('coins/markets', this.tonConfig) || null;
                        })
                        .catch(() => {
                            this.tonCurrencies = [];
                            this.tonMeta = null;
                        })
                        .finally(() => this.loadingTonMarkets = false);
                }
            }
        }
    });

})(window, _, CoinGecko, GeckoClient);
