(function (window, _, CoinGecko, GeckoClient) {
    'use strict';

    const route = GeckoClient.routesConfig.watchlist;
    const options = GeckoClient.getOptions('watchlist');
    if (!route) return;

    function dateValue(value) {
        const date = new Date(value || 0);
        return GeckoClient.utils.isValidDate(date) ? date.getTime() : 0;
    }

    GeckoClient.router.addRoute({
        name: 'watchlist',
        path: route.path,
        component: {
            template: '#route-watchlist',
            data: function () {
                return {
                    entries: [],
                    marketCurrencies: [],
                    loading: false,
                    marketError: false,
                    marketMeta: null,
                    marketConfig: null,
                    sortKey: 'added_at',
                    sortDirection: 'desc',
                    watchlistUnsubscribe: null
                };
            },
            created: function () {
                GeckoClient.setTitle(options.title);
                this.initWatchlist();
            },
            beforeDestroy: function () {
                if (this.watchlistUnsubscribe) this.watchlistUnsubscribe();
            },
            watch: {
                '$root.vsCurrencyId': function () {
                    this.fetchWatchlistMarketData();
                }
            },
            computed: {
                sortOptions: function () {
                    return [
                        {text: 'Added', value: 'added_at'},
                        {text: 'Name', value: 'name'},
                        {text: 'Rank', value: 'market_cap_rank'},
                        {text: 'Price', value: 'current_price'},
                        {text: '24h change', value: 'price_change_percentage_24h_in_currency'},
                        {text: 'Market cap', value: 'market_cap'}
                    ];
                },
                entryIds: function () {
                    return this.entries.map(entry => entry.coin_id);
                },
                isEmpty: function () {
                    return this.entries.length === 0;
                },
                freshnessStatus: function () {
                    return _.get(this.marketMeta, 'freshness.cache_status', null);
                },
                isStale: function () {
                    return ['stale', 'expired', 'fallback'].indexOf(this.freshnessStatus) >= 0;
                },
                freshnessLabel: function () {
                    const status = this.freshnessStatus || 'fresh';
                    const label = ['pass', 'hit', 'fresh'].indexOf(status) >= 0 ? 'Fresh' : _.startCase(status);
                    const timestamp = _.get(this.marketMeta, 'freshness.last_updated_at')
                        || _.get(this.marketMeta, 'freshness.fetched_at');

                    return timestamp ? label + ' ' + this.relativeTime(timestamp) : label;
                },
                storageModeLabel: function () {
                    const mode = GeckoClient.watchlist ? GeckoClient.watchlist.storageMode : 'local';
                    if (mode === 'telegram_cloud') return 'Telegram CloudStorage';
                    if (mode === 'memory') return 'Memory fallback';
                    return 'Local';
                },
                sortedCurrencies: function () {
                    const direction = this.sortDirection === 'asc' ? 1 : -1;
                    const sortKey = this.sortKey;

                    return this.marketCurrencies.slice().sort((a, b) => {
                        const aValue = this.sortValue(a, sortKey);
                        const bValue = this.sortValue(b, sortKey);

                        if (_.isString(aValue) || _.isString(bValue)) {
                            return direction * String(aValue || '').localeCompare(String(bValue || ''));
                        }

                        return direction * ((parseFloat(aValue) || 0) - (parseFloat(bValue) || 0));
                    });
                },
                watchlistInsightContext: function () {
                    if (this.isEmpty || !this.marketCurrencies.length || !GeckoClient.ai) return null;

                    return {
                        insight_type: 'watchlist_digest',
                        subject: 'Watchlist digest',
                        market_data_age_seconds: GeckoClient.ai.marketDataAgeSeconds(this.marketMeta),
                        market_data_updated_at: GeckoClient.ai.marketDataUpdatedAt(this.marketMeta),
                        market_data: {
                            vs_currency: this.$root.vsCurrencyId,
                            storage_mode: this.storageModeLabel,
                            freshness_status: this.freshnessStatus || 'fresh',
                            sort_key: this.sortKey,
                            sort_direction: this.sortDirection,
                            assets: this.sortedCurrencies.slice(0, 20).map(currency => this.aiCurrencySnapshot(currency))
                        }
                    };
                },
                watchlistShareCard: function () {
                    const strongest = _.first(this.sortedCurrencies.filter(currency => _.isFinite(parseFloat(currency.price_change_percentage_24h_in_currency))));

                    return {
                        title: 'Watchlist snapshot',
                        subtitle: this.entries.length + ' saved assets',
                        body: this.isEmpty
                            ? 'Saved watchlist view on TONBANKCARD.'
                            : 'Prices, 24h moves, ranks, and saved assets in one watchlist snapshot.',
                        route: '/watchlist',
                        campaign: 'watchlist-snapshot',
                        context: 'watchlist_snapshot',
                        freshness: !this.isEmpty && this.marketMeta ? this.freshnessLabel : 'Saved watchlist state',
                        metrics: [
                            {label: 'Assets', value: String(this.entries.length)},
                            {label: 'Storage', value: this.storageModeLabel},
                            {label: 'Sort', value: _.startCase(this.sortKey) + ' ' + this.sortDirection},
                            {label: 'Top 24h', value: strongest ? this.ruleAssetLabel(strongest) + ' ' + this.changeLabel(strongest.price_change_percentage_24h_in_currency) : 'N/A'}
                        ]
                    };
                }
            },
            methods: {
                initWatchlist: function () {
                    const watchlist = GeckoClient.watchlist;
                    if (!watchlist) return;

                    this.watchlistUnsubscribe = watchlist.onChange(() => {
                        this.syncEntries();
                        this.fetchWatchlistMarketData();
                    });

                    watchlist.init().then(() => {
                        this.syncEntries();
                        this.fetchWatchlistMarketData();
                    });
                },
                syncEntries: function () {
                    const snapshot = GeckoClient.watchlist ? GeckoClient.watchlist.snapshot() : {entries: []};
                    this.entries = snapshot.entries || [];
                },
                fetchWatchlistMarketData: function () {
                    if (!this.entries.length) {
                        this.marketCurrencies = [];
                        this.marketMeta = null;
                        this.marketError = false;
                        return Promise.resolve([]);
                    }

                    const params = {
                        ids: this.entryIds.join(','),
                        per_page: Math.min(250, this.entryIds.length),
                        page: 1,
                        order: 'market_cap_desc',
                        vs_currency: this.$root.vsCurrencyId,
                        price_change_percentage: '24h,7d,30d',
                        sparkline: true
                    };

                    this.marketConfig = {params: params};
                    this.loading = true;
                    this.marketError = false;

                    return CoinGecko.coinsMarkets(params)
                        .then(currencies => {
                            const byId = _.keyBy(currencies || [], 'id');
                            this.marketCurrencies = this.entries.map(entry => {
                                return this.extendCurrency(byId[entry.coin_id] || this.fallbackCurrency(entry), entry);
                            });
                            this.marketMeta = CoinGecko.metaGet('coins/markets', this.marketConfig) || null;
                        })
                        .catch(() => {
                            this.marketError = true;
                            this.marketCurrencies = this.entries.map(entry => this.extendCurrency(this.fallbackCurrency(entry), entry));
                        })
                        .finally(() => this.loading = false);
                },
                extendCurrency: function (currency, entry) {
                    currency.route = {name: 'currency', params: {id: currency.id}};
                    currency.added_at = entry.added_at;
                    currency.watchlist_symbol = entry.symbol || currency.symbol;
                    return currency;
                },
                fallbackCurrency: function (entry) {
                    return {
                        id: entry.coin_id,
                        symbol: entry.symbol,
                        name: entry.name || _.startCase(entry.coin_id),
                        image: entry.image,
                        market_cap_rank: null,
                        current_price: null,
                        price_change_percentage_24h_in_currency: null,
                        market_cap: null,
                        total_volume: null,
                        sparkline_in_7d: {price: []}
                    };
                },
                aiCurrencySnapshot: function (currency) {
                    return GeckoClient.ai ? GeckoClient.ai.marketCurrencySnapshot(currency) : {};
                },
                removeFromWatchlist: function (currency) {
                    if (!GeckoClient.watchlist) return;

                    GeckoClient.watchlist.remove(currency, {sourceRoute: 'watchlist'})
                        .then(() => {
                            this.syncEntries();
                            this.fetchWatchlistMarketData();
                        });
                },
                toggleSortDirection: function () {
                    this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
                },
                sortIcon: function () {
                    return this.sortDirection === 'asc' ? 'mdi-sort-ascending' : 'mdi-sort-descending';
                },
                sortValue: function (currency, key) {
                    if (key === 'added_at') return dateValue(currency.added_at);
                    if (key === 'name') return _.toLower(currency.name || currency.id);
                    return _.get(currency, key, 0);
                },
                priceLabel: function (value) {
                    return _.isFinite(parseFloat(value)) ? this.$root.priceFormat(value) : 'N/A';
                },
                marketCapLabel: function (value) {
                    return _.isFinite(parseFloat(value)) ? this.$root.marketCapFormat(value) : 'N/A';
                },
                changeLabel: function (value) {
                    return _.isFinite(parseFloat(value)) ? this.$root.changeFormat(value) : 'N/A';
                },
                ruleAssetLabel: function (currency) {
                    return _.toUpper(currency.watchlist_symbol || currency.symbol || currency.id || 'asset');
                },
                shareWatchlist: function () {
                    if (!GeckoClient.share) return;
                    GeckoClient.share.share(this.watchlistShareCard);
                },
                relativeTime: function (timestamp) {
                    const date = new Date(timestamp);
                    if (!GeckoClient.utils.isValidDate(date)) return '';

                    const seconds = Math.max(0, Math.floor((Date.now() - date.getTime()) / 1000));
                    if (seconds < 60) return 'now';
                    if (seconds < 3600) return Math.floor(seconds / 60) + 'm ago';
                    if (seconds < 86400) return Math.floor(seconds / 3600) + 'h ago';
                    return Math.floor(seconds / 86400) + 'd ago';
                }
            }
        }
    });

})(window, _, CoinGecko, GeckoClient);
