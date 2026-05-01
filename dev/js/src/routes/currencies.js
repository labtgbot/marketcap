(function (window, _, CoinGecko, GeckoClient) {
    'use strict';

    const setTitle = GeckoClient.setTitle;
    const options = GeckoClient.getOptions('currencies');
    const route = GeckoClient.routesConfig.currencies;
    const perPage = Math.min(100, options.perPage) || 50;

    function percentChange(currency) {
        return parseFloat(currency.price_change_percentage_24h_in_currency);
    }

    function hasFiniteChange(currency) {
        return _.isFinite(percentChange(currency));
    }

    GeckoClient.router.addRoute({
        name: 'currencies',
        path: route.path,
        component: {
            template: '#route-currencies',
            data: function () {
                return {
                    global: null,
                    marketCurrencies: [],
                    trendingCoins: [],
                    watchlistIds: [],
                    loadingGlobal: false,
                    loadingMarkets: false,
                    loadingTrending: false,
                    globalError: false,
                    marketError: false,
                    trendingError: false,
                    globalMeta: null,
                    marketMeta: null,
                    marketConfig: null,
                    watchlistUnsubscribe: null
                };
            },
            created: function () {
                this.initWatchlist();
                this.fetchPulse();
                setTitle(options.title);
            },
            beforeDestroy: function () {
                if (this.watchlistUnsubscribe) this.watchlistUnsubscribe();
            },
            watch: {
                '$root.vsCurrencyId': function () {
                    this.fetchPulse();
                }
            },
            computed: {
                loading: function () {
                    return this.loadingGlobal || this.loadingMarkets || this.loadingTrending;
                },
                upstreamError: function () {
                    return this.marketError && !this.marketCurrencies.length;
                },
                partialError: function () {
                    return !this.upstreamError && (this.globalError || this.marketError || this.trendingError);
                },
                freshnessMeta: function () {
                    return this.marketMeta || this.globalMeta || null;
                },
                freshnessStatus: function () {
                    return _.get(this.freshnessMeta, 'freshness.cache_status', null);
                },
                freshnessLabel: function () {
                    const status = this.freshnessStatus || 'fresh';
                    const label = ['pass', 'hit', 'fresh'].indexOf(status) >= 0 ? 'Fresh' : _.startCase(status);
                    const timestamp = _.get(this.freshnessMeta, 'freshness.last_updated_at')
                        || _.get(this.freshnessMeta, 'freshness.fetched_at');

                    return timestamp ? label + ' ' + this.relativeTime(timestamp) : label;
                },
                freshnessColor: function () {
                    return this.isStale ? 'warning' : 'success';
                },
                isStale: function () {
                    return ['stale', 'expired', 'fallback'].indexOf(this.freshnessStatus) >= 0;
                },
                globalStats: function () {
                    return [
                        {
                            label: 'Market cap',
                            icon: 'mdi-finance',
                            value: this.$root.marketCapFormat(_.get(this.global, ['total_market_cap', this.$root.vsCurrencyId], null))
                        },
                        {
                            label: '24h volume',
                            icon: 'mdi-chart-bar',
                            value: this.$root.volumeFormat(_.get(this.global, ['total_volume', this.$root.vsCurrencyId], null))
                        },
                        {
                            label: 'Assets',
                            icon: 'mdi-database',
                            value: this.$root.bigNumberFormat(_.get(this.global, 'active_cryptocurrencies', null))
                        },
                        {
                            label: 'BTC dominance',
                            icon: 'mdi-bitcoin',
                            value: this.$root.dominanceFormat(_.get(this.global, ['market_cap_percentage', 'btc'], null))
                        }
                    ];
                },
                tonCurrencies: function () {
                    const tonCoinIds = options.tonCoinIds || ['toncoin'];
                    return this.marketCurrencies.filter(currency => {
                        const id = _.toLower(currency.id);
                        const symbol = _.toLower(currency.symbol);
                        const name = _.toLower(currency.name);
                        return tonCoinIds.indexOf(id) >= 0 || symbol === 'ton' || name.indexOf('ton') >= 0;
                    }).slice(0, 4);
                },
                topGainers: function () {
                    return this.marketCurrencies
                        .filter(currency => hasFiniteChange(currency) && percentChange(currency) > 0)
                        .slice()
                        .sort((a, b) => percentChange(b) - percentChange(a))
                        .slice(0, 4);
                },
                topLosers: function () {
                    return this.marketCurrencies
                        .filter(currency => hasFiniteChange(currency) && percentChange(currency) < 0)
                        .slice()
                        .sort((a, b) => percentChange(a) - percentChange(b))
                        .slice(0, 4);
                },
                watchlistCurrencies: function () {
                    if (!this.watchlistIds.length) return [];

                    return this.marketCurrencies.filter(currency => {
                        return this.watchlistIds.indexOf(currency.id) >= 0 || this.watchlistIds.indexOf(currency.symbol) >= 0;
                    }).slice(0, 4);
                },
                marketInsightContext: function () {
                    if (!this.marketCurrencies.length || !GeckoClient.ai) return null;

                    return {
                        insight_type: 'market_summary',
                        subject: 'Market pulse for ' + _.toUpper(this.$root.vsCurrencyId),
                        market_data_age_seconds: GeckoClient.ai.marketDataAgeSeconds(this.freshnessMeta),
                        market_data_updated_at: GeckoClient.ai.marketDataUpdatedAt(this.freshnessMeta),
                        market_data: {
                            vs_currency: this.$root.vsCurrencyId,
                            freshness_status: this.freshnessStatus || 'fresh',
                            global: {
                                market_cap: _.get(this.global, ['total_market_cap', this.$root.vsCurrencyId], null),
                                volume_24h: _.get(this.global, ['total_volume', this.$root.vsCurrencyId], null),
                                active_cryptocurrencies: _.get(this.global, 'active_cryptocurrencies', null),
                                btc_dominance: _.get(this.global, ['market_cap_percentage', 'btc'], null)
                            },
                            top_gainers: this.topGainers.map(currency => this.aiCurrencySnapshot(currency)),
                            top_losers: this.topLosers.map(currency => this.aiCurrencySnapshot(currency)),
                            ton_assets: this.tonCurrencies.map(currency => this.aiCurrencySnapshot(currency)),
                            watchlist_preview: this.watchlistCurrencies.map(currency => this.aiCurrencySnapshot(currency))
                        }
                    };
                }
            },
            methods: {
                fetchPulse: function () {
                    this.fetchGlobal();
                    this.fetchMarketCurrencies();
                    this.fetchTrendingCoins();
                },
                initWatchlist: function () {
                    const watchlist = GeckoClient.watchlist;
                    if (!watchlist) return;

                    this.watchlistUnsubscribe = watchlist.onChange(() => this.syncWatchlistIds());
                    watchlist.init().then(() => this.syncWatchlistIds());
                },
                syncWatchlistIds: function () {
                    this.watchlistIds = GeckoClient.watchlist ? GeckoClient.watchlist.ids() : [];
                },
                fetchGlobal: function () {
                    this.loadingGlobal = true;
                    this.globalError = false;

                    return CoinGecko.global()
                        .then(global => {
                            this.global = global;
                            this.globalMeta = CoinGecko.metaGet('global', undefined) || null;
                        })
                        .catch(() => this.globalError = true)
                        .finally(() => this.loadingGlobal = false);
                },
                fetchMarketCurrencies: function () {
                    const params = {
                        per_page: perPage,
                        page: 1,
                        order: options.order,
                        vs_currency: this.$root.vsCurrencyId,
                        price_change_percentage: options.priceChanges.join(','),
                        sparkline: true
                    };

                    this.marketConfig = {params: params};
                    this.loadingMarkets = true;
                    this.marketError = false;

                    return CoinGecko.coinsMarkets(params)
                        .then(currencies => {
                            this.marketCurrencies = currencies.map(currency => this.extendCurrency(currency));
                            this.marketMeta = CoinGecko.metaGet('coins/markets', this.marketConfig) || null;
                        })
                        .catch(() => {
                            this.marketError = true;
                            this.marketCurrencies = [];
                        })
                        .finally(() => this.loadingMarkets = false);
                },
                fetchTrendingCoins: function () {
                    this.loadingTrending = true;
                    this.trendingError = false;

                    return CoinGecko.searchTrending()
                        .then(trending => {
                            this.trendingCoins = (trending.coins || [])
                                .slice(0, 6)
                                .map(coin => this.extendCurrency(coin));
                        })
                        .catch(() => this.trendingError = true)
                        .finally(() => this.loadingTrending = false);
                },
                extendCurrency: function (currency) {
                    currency.route = {name: 'currency', params: {id: currency.id}};
                    return currency;
                },
                aiCurrencySnapshot: function (currency) {
                    return GeckoClient.ai ? GeckoClient.ai.marketCurrencySnapshot(currency) : {};
                },
                isWatched: function (currency) {
                    return currency && this.watchlistIds.indexOf(currency.id) >= 0;
                },
                watchlistIcon: function (currency) {
                    return this.isWatched(currency) ? 'mdi-star' : 'mdi-star-outline';
                },
                watchlistLabel: function (currency) {
                    return (this.isWatched(currency) ? 'Remove ' : 'Add ') + currency.name + ' ' + (this.isWatched(currency) ? 'from' : 'to') + ' Watchlist';
                },
                toggleWatchlist: function (currency) {
                    if (!currency || !GeckoClient.watchlist) return;

                    GeckoClient.watchlist.toggle(currency, {sourceRoute: 'market_pulse'})
                        .then(() => this.syncWatchlistIds());
                },
                relativeTime: function (timestamp) {
                    const date = new Date(timestamp);
                    if (!GeckoClient.utils.isValidDate(date)) return '';

                    const seconds = Math.max(0, Math.floor((Date.now() - date.getTime()) / 1000));
                    if (seconds < 60) return 'now';
                    if (seconds < 3600) return Math.floor(seconds / 60) + 'm ago';
                    if (seconds < 86400) return Math.floor(seconds / 3600) + 'h ago';
                    return Math.floor(seconds / 86400) + 'd ago';
                },
                focusSearch: function () {
                    const input = document.querySelector('.gc-search-bar input[type="text"]');
                    if (input) {
                        input.focus();
                        input.click();
                    }
                }
            }
        }
    });

})(window, _, CoinGecko, GeckoClient);
