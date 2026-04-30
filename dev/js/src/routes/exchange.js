(function (window, CoinGecko, GeckoClient) {
    'use strict';

    const setTitle = GeckoClient.setTitle;

    const exchangeOptions = GeckoClient.getOptions('exchange');

    GeckoClient.router.addRoute({
        name: 'exchange',
        path: GeckoClient.routesConfig.exchange.path,
        component: {
            template: '#route-exchange',
            data: function () {
                return {
                    exchangeId: this.$route.params.id,
                    exchange: null,
                    tableHeaders: exchangeOptions.tableHeaders,
                    loading: false,
                    tickers: [],
                    tickersPage: 1,
                    tickersPerPage: 100, // must be 100
                    tickersLoadMore: true,
                    tickersLoading: false
                };
            },
            created: function () {
                this.fetchExchange();
            },
            beforeRouteUpdate: function (to, from, next) {
                // reset and fetch new exchange in exchange to exchange route transition
                this.resetData();
                this.exchangeId = to.params.id;
                this.fetchExchange().then(() => next());
            },
            methods: {
                resetData: function () {
                    this.loading = false;
                    this.tickers = [];
                    this.tickersPage = 1;
                    this.tickersLoadMore = true;
                    this.tickersLoading = false;
                },
                fetchExchange: function () {
                    this.loading = true;
                    return CoinGecko.exchange(this.exchangeId, null)
                        .then(exchange => {
                            this.exchange = exchange;
                            this.tickers = _.each(exchange.tickers, ticker => this.extendTicker(ticker));
                            this.tickersLoadMore = exchange.tickers.length === this.tickersPerPage;

                            // update title meta tags
                            setTitle(exchange.name);

                            return exchange;
                        })
                        .catch(err => this.$router.push({name: 'exchanges'})) // redirect to table if fails
                        .finally(() => this.loading = false);
                },
                extendTicker: function (ticker) {
                    // extend with properties for table usage

                    ticker.pair = ticker.base + '/' + ticker.target;
                    ticker.pairDisplay = this.$root.pairDisplay(ticker.base, ticker.target);

                    // avoid addresses as symbols
                    const target = _.toLower(ticker.target).indexOf('0x') === 0 ? false : ticker.target;
                    const base   = _.toLower(ticker.base).indexOf('0x') === 0 ? false : ticker.base;

                    ticker.lastUSD = parseFloat(ticker.converted_last.usd) || 0;
                    ticker.lastFormatted = this.$root.priceTargetFormat(ticker.last, target);
                    ticker.volumeUSD = parseFloat(ticker.converted_volume.usd) || 0;
                    ticker.volumeFormatted = this.$root.volumeTargetFormat(ticker.volume, base);
                    ticker.spreadFormatted = this.$root.spreadFormat(ticker.bid_ask_spread_percentage);

                    // trust details
                    ticker.trustColor = this.$root.coinGeckoTrustScoreColor(ticker.trust_score);
                    ticker.trustTextColor = this.$root.coinGeckoTrustScoreTextColor(ticker.trust_score);
                    ticker.trustScore = this.$root.coinGeckoTrustScoreInteger(ticker.trust_score);
                    ticker.trustText = this.$root.coinGeckoTrustScoreText(ticker.trust_score);

                    return ticker;
                },
                fetchTickers: function () {
                    const params = {
                        page: ++this.tickersPage,
                        per_page: this.tickersPerPage
                    };
                    return CoinGecko.exchangeTickers(this.exchangeId, params)
                        .then(tickers => {
                            tickers.forEach(ticker => this.tickers.push(this.extendTicker(ticker)));
                            this.tickersLoadMore = tickers.length === this.tickersPerPage;
                            return tickers;
                        });
                },
                fetchMoreTickers: function () {
                    this.tickersLoading = true;
                    return this.fetchTickers().finally(() => this.tickersLoading = false);
                },
            }
        }
    });

})(window, CoinGecko, GeckoClient);
