(function (window, CoinGecko, GeckoClient) {
    'use strict';

    const setTitle = GeckoClient.setTitle;

    const marketsRoute = GeckoClient.routesConfig.markets;
    const marketsOptions = GeckoClient.getOptions('markets');
    const tableHeaders = marketsOptions.tableHeaders.filter(header => header.show);
    const perPage = Math.min(250, marketsOptions.perPage) || 100;

    if (!marketsRoute) return;

    GeckoClient.router.addRoute({
        name: 'markets',
        path: marketsRoute.path,
        component: {
            template: '#route-markets',
            data: function () {
                return {
                    order: marketsOptions.order,
                    priceChanges: marketsOptions.priceChanges,
                    currencies: [],
                    page: 0,
                    perPage: perPage,
                    loading: false,
                    loadMore: true,
                    loadMoreLoading: false,
                    watchlistIds: [],
                    watchlistUnsubscribe: null
                };
            },
            created: function () {
                this.initWatchlist();
                this.fetchFirstCurrencies();
                // update title meta tags
                setTitle(marketsOptions.title);
            },
            beforeDestroy: function () {
                if (this.watchlistUnsubscribe) this.watchlistUnsubscribe();
            },
            watch: {
                '$root.vsCurrencyId': function () {
                    // refresh values with new vs currency
                    this.fetchFirstCurrencies();
                }
            },
            computed: {
                tableHeaders: function () {
                    if (this.$vuetify.breakpoint.xs) {
                        // hide rank column in smartphones
                        return _.reject(tableHeaders, ['value', 'market_cap_rank']);
                    }
                    return tableHeaders;
                }
            },
            methods: {
                initWatchlist: function () {
                    const watchlist = GeckoClient.watchlist;
                    if (!watchlist) return;

                    this.watchlistUnsubscribe = watchlist.onChange(() => this.syncWatchlistIds());
                    watchlist.init().then(() => this.syncWatchlistIds());
                },
                syncWatchlistIds: function () {
                    this.watchlistIds = GeckoClient.watchlist ? GeckoClient.watchlist.ids() : [];
                },
                fetchCurrencies: function () {
                    const params = {
                        per_page: this.perPage,
                        page: ++this.page,
                        order: this.order,
                        vs_currency: this.$root.vsCurrencyId,
                        price_change_percentage: this.priceChanges.join(','),
                        sparkline: true
                    };
                    return CoinGecko.coinsMarkets(params)
                        .then(currencies => {
                            _.each(currencies, currency => {
                                currency.route = {name: 'currency', params: {id: currency.id}};
                                this.currencies.push(currency);
                            })
                            this.loadMore = currencies.length === this.perPage;
                            return currencies;
                        })
                        .catch(err => this.loadMore = false);
                },
                fetchFirstCurrencies: function () {
                    // reset
                    this.currencies = [];
                    this.page = 0;
                    this.loadMore = true;
                    this.loadMoreLoading = false;

                    this.loading = true;
                    return this.fetchCurrencies().finally(() => this.loading = false);
                },
                fetchMoreCurrencies: function () {
                    this.loadMoreLoading = true;
                    return this.fetchCurrencies().finally(() => this.loadMoreLoading = false);
                },
                isWatched: function (currency) {
                    return this.watchlistIds.indexOf(currency.id) >= 0;
                },
                watchlistIcon: function (currency) {
                    return this.isWatched(currency) ? 'mdi-star' : 'mdi-star-outline';
                },
                watchlistLabel: function (currency) {
                    return (this.isWatched(currency) ? 'Remove ' : 'Add ') + currency.name + ' ' + (this.isWatched(currency) ? 'from' : 'to') + ' Watchlist';
                },
                toggleWatchlist: function (currency) {
                    if (!GeckoClient.watchlist) return;

                    GeckoClient.watchlist.toggle(currency, {sourceRoute: 'markets'})
                        .then(() => this.syncWatchlistIds());
                },
                toCurrency: function (currency) {
                    this.$router.push(currency.route);
                }
            }
        }
    });

})(window, CoinGecko, GeckoClient);
