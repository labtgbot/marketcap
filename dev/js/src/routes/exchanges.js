(function (window, CoinGecko, GeckoClient) {
    'use strict';

    const setTitle = GeckoClient.setTitle;

    const exchangesOptions = GeckoClient.getOptions('exchanges');
    const tableHeaders = exchangesOptions.tableHeaders.filter(header => header.show);
    const perPage = Math.min(250, exchangesOptions.perPage) || 100;

    GeckoClient.router.addRoute({
        name: 'exchanges',
        path: GeckoClient.routesConfig.exchanges.path,
        component: {
            template: '#route-exchanges',
            data: function () {
                return {
                    tableHeaders: tableHeaders,
                    exchanges: [],
                    page: 0,
                    perPage: perPage,
                    loading: false,
                    loadMore: true,
                    loadMoreLoading: false
                };
            },
            created: function () {
                this.loading = true;
                this.fetchExchanges().finally(() => this.loading = false);
                // update title meta tags
                setTitle(exchangesOptions.title);
            },
            methods: {
                fetchExchanges: function () {
                    const params = {
                        per_page: this.per_page,
                        page: ++this.page
                    };
                    return CoinGecko.exchanges(params)
                        .then(exchanges => {
                            exchanges.forEach(exchange => this.exchanges.push(this.extendExchange(exchange)));
                            this.loadMore = exchanges.length === this.perPage;
                            return exchanges;
                        })
                        .catch(err => this.loadMore = false);
                },
                fetchMoreExchanges: function () {
                    this.loadMoreLoading = true;
                    return this.fetchExchanges().finally(() => this.loadMoreLoading = false);
                },
                extendExchange: function (exchange) {
                    // extend with properties for table usage
                    const $root = this.$root;

                    // trust details
                    const score = exchange.trust_score;
                    exchange.trustScore = $root.coinGeckoTrustScoreInteger(score);
                    exchange.trustColor = $root.coinGeckoTrustScoreColor(score);
                    exchange.trustTextColor = $root.coinGeckoTrustScoreTextColor(score);
                    exchange.trustText = $root.coinGeckoTrustScoreText(score);

                    exchange.volume24hFormatted = $root.volumeBTCFormat(exchange.trade_volume_24h_btc);
                    exchange.volume24hNormalizedFormatted = $root.volumeBTCFormat(exchange.trade_volume_24h_btc_normalized);

                    exchange.route = {name: 'exchange', params: {id: exchange.id}}

                    return exchange;
                },
                toExchange: function (exchange) {
                    this.$router.push(exchange.route);
                }
            }
        }
    });

})(window, CoinGecko, GeckoClient);
