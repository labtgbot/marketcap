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
                };
            },
            created: function () {
                this.fetchFirstCurrencies();
                // update title meta tags
                setTitle(marketsOptions.title);
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
                toCurrency: function (currency) {
                    this.$router.push(currency.route);
                }
            }
        }
    });

})(window, CoinGecko, GeckoClient);
