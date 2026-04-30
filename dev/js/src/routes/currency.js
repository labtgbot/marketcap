(function (window, CoinGecko, GeckoClient) {
    'use strict';

    const setTitle = GeckoClient.setTitle;

    const mainOptions = GeckoClient.getOptions('currency');

    const marketOptions = GeckoClient.getOptions('currency-market');

    const historicalOptions = GeckoClient.getOptions('currency-historical');
    // CoinGecko has auto granularity, min 120 day period to force 1-day interval
    const historicalPeriodDays  = Math.max(120, historicalOptions.periodDays) || 120;
    const historicalPeriodSecs  = historicalPeriodDays * 3600 * 24;
    const historicalToTimestamp = parseInt(new Date() / 1000);


    GeckoClient.router.addRoute({
        name: 'currency',
        path: GeckoClient.routesConfig.currency.path,
        component: {
            template: '#route-currency',
            data: function () {
                return {
                    currencyId: this.$route.params.id,
                    currency: null,
                    tabsModel: null,
                    tab: null,
                    tabs: mainOptions.tabs,
                    loading: false,

                    marketLoading: false,
                    marketTableHeaders: marketOptions.tableHeaders,
                    marketTickers: [],
                    marketPage: 0,
                    marketOrder: 'volume_desc',
                    marketPerPage: 100, // must be 100
                    marketLoadMore: true,
                    marketLoadingMore: false,

                    historicalLoading: false,
                    historicalTableHeaders: historicalOptions.tableHeaders,
                    historicalData: [],
                    historicalToTimestamp: historicalToTimestamp,
                    historicalPeriodDays: historicalPeriodDays,
                    historicalPeriodSecs: historicalPeriodSecs,
                    historicalLoadMore: true,
                    historicalLoadMoreLoading: false
                };
            },
            created: function () {
                this.fetchCurrency()
            },
            beforeRouteUpdate: function (to, from, next) {
                // reset and fetch new currency in currency to currency route transition
                this.resetData();
                this.currencyId = to.params.id;
                this.fetchCurrency()
                    .then(() => next())
                    .then(() => this.tabChanged(this.tabsModel)); // open the same tab
            },
            watch: {
                '$root.vsCurrencyId': function () {
                    this.resetData();
                    // fetch currency with new vs currency values
                    this.fetchCurrency().then(() => this.tabChanged(this.tabsModel)); // open the same tab
                },
                tabsModel: function (index) {
                    this.tabChanged(index)
                }
            },
            methods: {
                resetData: function () {
                    this.marketTickers = [];
                    this.marketLoading = false;
                    this.marketPage = 0;
                    this.marketLoadMore = true;
                    this.marketLoadingMore = false;

                    this.historicalData = [];
                    this.historicalLoading = false;
                    this.historicalToTimestamp = historicalToTimestamp;
                    this.historicalLoadMore = true;
                    this.historicalLoadMoreLoading = false;
                },
                fetchCurrency: function () {
                    this.loading = true;

                    const params = {
                        market_data: true,
                        localization: false,
                        tickers: false,
                        sparkline: false
                    };
                    return CoinGecko.coin(this.currencyId, params)
                        .then(currency => {
                            // avoid crossing requests
                            if (currency.id === this.currencyId) {
                                this.currency = this.extendCurrency(currency);
                                // update title meta tags
                                setTitle(currency.name + ' (' + _.toUpper(currency.symbol) + ')');
                            }
                            return currency;
                        })
                        .catch(err => this.$router.push({name: 'currencies'})) // redirect to table if fails
                        .finally(() => this.loading = false);
                },
                extendCurrency: function (currency) {
                    // extend with converted and calculated market data properties
                    const md = currency.market_data = currency.market_data || {};
                    currency.currentPrice = this.vsConverted(md.current_price);
                    currency.change24hPercent = this.vsConverted(md.price_change_percentage_24h_in_currency);
                    currency.high24h = this.vsConverted(md.high_24h);
                    currency.low24h = this.vsConverted(md.low_24h);
                    currency.marketCap = this.vsConverted(md.market_cap);
                    currency.marketCapChange24h = this.vsConverted(md.market_cap_change_24h_in_currency);
                    currency.marketCapChange24hPercent = this.vsConverted(md.market_cap_change_percentage_24h_in_currency);
                    currency.fullyDilutedValuation = this.vsConverted(md.fully_diluted_valuation);
                    currency.totalVolume = this.vsConverted(md.total_volume);
                    currency.circulatingSupply = md.circulating_supply || null;
                    currency.totalSupply = md.total_supply || null;

                    const marketCap = parseFloat(currency.marketCap);
                    const totalVolume = parseFloat(currency.totalVolume);
                    const volumePerMarketCap = totalVolume / marketCap;
                    currency.volumePerMarketCap = _.isFinite(volumePerMarketCap) ? volumePerMarketCap : null;

                    return currency;
                },
                vsConverted: function (priceObj) {
                    return _.get(priceObj, this.$root.vsCurrencyId, null);
                },
                tabChanged: function (index) {
                    this.tab = this.tabs[index];
                    switch (this.tab) {
                        case 'market': return this.showMarket();
                        case 'historical': return this.showHistoricalData();
                    }
                },
                fetchTickers: function () {
                    const params = {
                        include_exchange_logo: true,
                        per_page: this.marketPerPage,
                        page: ++this.marketPage,
                        order: this.marketOrder
                    }
                    return CoinGecko.coinTickers(this.currencyId, params)
                        .then(tickers => {
                            tickers.forEach(ticker => this.marketTickers.push(this.extendTicker(ticker)));
                            this.marketLoadMore = tickers.length === this.marketPerPage;
                            return tickers;
                        })
                        .catch(err => this.marketLoadMore = false);
                },
                showMarket: function () {
                    // if has tickers, do nothing
                    if (this.marketTickers.length || this.marketLoading) return;
                    // fetch first tickers
                    this.marketLoading = true;
                    this.fetchTickers().finally(() => this.marketLoading = false);
                },
                fetchMoreTickers: function () {
                    this.marketLoadingMore = true;
                    return this.fetchTickers().finally(() => this.marketLoadingMore = false);
                },
                extendTicker: function (ticker) {
                    // extend with properties for table usage
                    const $root = this.$root;

                    ticker.pair = ticker.base + '/' + ticker.target;
                    ticker.pairDisplay = $root.pairDisplay(ticker.base, ticker.target);

                    ticker.exchangeName = ticker.market.name;
                    ticker.exchangeLogo = ticker.market.logo;
                    ticker.exchangeRoute = {
                        name: 'exchange',
                        params: {id: ticker.market.identifier}
                    };

                    // avoid addresses as symbols
                    const target = _.toLower(ticker.target).indexOf('0x') === 0 ? false : ticker.target;
                    const base   = _.toLower(ticker.base).indexOf('0x') === 0 ? false : ticker.base;

                    ticker.converted_last = ticker.converted_last || {};
                    ticker.lastUSD = parseFloat(ticker.converted_last.usd) || 0;
                    ticker.lastFormatted = $root.priceTargetFormat(ticker.last, target);
                    ticker.volumeFormatted = $root.volumeTargetFormat(ticker.volume, base);
                    ticker.spreadFormatted = $root.spreadFormat(ticker.bid_ask_spread_percentage);

                    // trust details
                    ticker.trustColor = $root.coinGeckoTrustScoreColor(ticker.trust_score);
                    ticker.trustTextColor = $root.coinGeckoTrustScoreTextColor(ticker.trust_score);
                    ticker.trustScore = $root.coinGeckoTrustScoreInteger(ticker.trust_score);
                    ticker.trustText = $root.coinGeckoTrustScoreText(ticker.trust_score);

                    return ticker;
                },
                fetchHistoricalData: function () {
                    // calculate "from" subtracting a full period to current upper limit
                    const from = this.historicalToTimestamp - this.historicalPeriodSecs;
                    const params = {
                        vs_currency: this.$root.vsCurrencyId,
                        from: from,
                        to: this.historicalToTimestamp
                    };
                    return CoinGecko.coinMarketChartRange(this.currencyId, params)
                        .then(data => {
                            this.historicalLoadMore = data.prices.length === this.historicalPeriodDays;
                            // need to be added in reverse order
                            return _.eachRight(data.prices, (p, index) => {
                                this.historicalData.push({
                                    timestamp: data.prices[index][0],
                                    price: data.prices[index][1],
                                    marketCap: data.market_caps[index][1],
                                    volume: data.total_volumes[index][1]
                                });
                            });
                        })
                        .catch(err => this.historicalLoadMore = false)
                        .finally(() => this.historicalToTimestamp = from - 1);
                },
                fetchMoreHistoricalData: function () {
                    this.historicalLoadMoreLoading = true;
                    return this.fetchHistoricalData().finally(() => this.historicalLoadMoreLoading = false);
                },
                showHistoricalData: function () {
                    // if has data, do nothing
                    if (this.historicalData.length || this.historicalLoading) return;
                    // fetch first entries
                    this.historicalLoading = true;
                    this.fetchHistoricalData().finally(() => this.historicalLoading = false);
                }
            }
        }
    });

})(window, CoinGecko, GeckoClient);
