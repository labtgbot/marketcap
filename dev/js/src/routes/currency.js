(function (window, _, CoinGecko, GeckoClient) {
    'use strict';

    const setTitle = GeckoClient.setTitle;

    const currencyRouteConfig = GeckoClient.routesConfig.currency;
    const coinsRouteConfig = GeckoClient.routesConfig.coins;

    const mainOptions = GeckoClient.getOptions('currency');

    const marketOptions = GeckoClient.getOptions('currency-market');

    const historicalOptions = GeckoClient.getOptions('currency-historical');
    // CoinGecko has auto granularity, min 120 day period to force 1-day interval
    const historicalPeriodDays  = Math.max(120, historicalOptions.periodDays) || 120;
    const historicalPeriodSecs  = historicalPeriodDays * 3600 * 24;
    const historicalToTimestamp = parseInt(new Date() / 1000);


    function currencyComponent() {
        return {
            template: '#route-currency',
            data: function () {
                return {
                    currencyId: this.$route.params.id,
                    currency: null,
                    tabsModel: null,
                    tab: null,
                    tabs: mainOptions.tabs,
                    loading: false,
                    actionNotice: '',
                    actionNoticeModel: false,

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
                    historicalLoadMoreLoading: false,

                    watchlistIds: [],
                    watchlistUnsubscribe: null
                };
            },
            created: function () {
                this.initWatchlist();
                this.fetchCurrency()
            },
            beforeDestroy: function () {
                if (this.watchlistUnsubscribe) this.watchlistUnsubscribe();
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
            computed: {
                isInWatchlist: function () {
                    return this.isWatched(this.currency);
                },
                watchlistButtonLabel: function () {
                    return this.watchlistLabel(this.currency);
                },
                alertButtonLabel: function () {
                    return this.currency ? 'Create alert for ' + this.currency.name : 'Create alert';
                },
                shareButtonLabel: function () {
                    return this.currency ? 'Share ' + this.currency.name : 'Share coin';
                },
                currencyShareCard: function () {
                    if (!this.currency) return null;

                    const symbol = _.toUpper(this.currency.symbol || '');
                    const price = this.currency.currentPrice ? this.$root.priceFormat(this.currency.currentPrice) : 'Price unavailable';
                    const change = _.isFinite(parseFloat(this.currency.change24hPercent))
                        ? this.$root.changeFormat(this.currency.change24hPercent)
                        : '24h unavailable';

                    return {
                        title: this.currency.name + ' price',
                        subtitle: symbol ? symbol + ' market card' : 'Coin market card',
                        body: price + ' with 24h move ' + change + ' on TONBANKCARD.',
                        route: '/currency/' + encodeURIComponent(this.currency.id),
                        campaign: 'coin-price',
                        context: 'coin_price',
                        freshness: this.currencyShareFreshnessLabel(),
                        metrics: [
                            {label: 'Price', value: price},
                            {label: '24h', value: change},
                            {label: 'Market cap', value: this.currency.marketCap ? this.$root.marketCapFormat(this.currency.marketCap) : 'N/A'},
                            {label: 'Rank', value: this.currency.market_cap_rank ? '#' + this.currency.market_cap_rank : 'N/A'}
                        ]
                    };
                },
                coinInsightContext: function () {
                    if (!this.currency || !GeckoClient.ai) return null;

                    return {
                        insight_type: 'coin_summary',
                        subject: this.currency.name + ' (' + _.toUpper(this.currency.symbol) + ')',
                        market_data_age_seconds: this.currencyMarketAgeSeconds(),
                        market_data_updated_at: this.currencyMarketUpdatedAt(),
                        market_data: this.currencyInsightMarketData()
                    };
                },
                alertInsightContext: function () {
                    if (!this.currency || !GeckoClient.ai) return null;

                    return {
                        insight_type: 'alert_explanation',
                        subject: this.currency.name + ' (' + _.toUpper(this.currency.symbol) + ') alert context',
                        market_data_age_seconds: this.currencyMarketAgeSeconds(),
                        market_data_updated_at: this.currencyMarketUpdatedAt(),
                        market_data: Object.assign(
                            this.currencyInsightMarketData(),
                            {
                                watchlisted: this.isWatched(this.currency),
                                alert_context: {
                                    current_price: this.currency.currentPrice,
                                    high_24h: this.currency.high24h,
                                    low_24h: this.currency.low24h,
                                    change_24h_percent: this.currency.change24hPercent,
                                    volume_market_cap_ratio: this.currency.volumePerMarketCap
                                }
                            }
                        )
                    };
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
                isWatched: function (currency) {
                    return currency && this.watchlistIds.indexOf(currency.id) >= 0;
                },
                watchlistIcon: function (currency) {
                    return this.isWatched(currency) ? 'mdi-star' : 'mdi-star-outline';
                },
                watchlistLabel: function (currency) {
                    if (!currency) return 'Watchlist';
                    return (this.isWatched(currency) ? 'Remove ' : 'Add ') + currency.name + ' ' + (this.isWatched(currency) ? 'from' : 'to') + ' Watchlist';
                },
                toggleWatchlist: function (currency) {
                    if (!currency || !GeckoClient.watchlist) return;

                    const wasWatched = this.isWatched(currency);
                    GeckoClient.watchlist.toggle(
                        {
                            id: currency.id,
                            symbol: currency.symbol,
                            name: currency.name,
                            image: _.get(currency, 'image.large') || _.get(currency, 'image.small') || _.get(currency, 'image.thumb')
                        },
                        {sourceRoute: 'coin_detail'}
                    ).then(() => {
                        this.syncWatchlistIds();
                        this.showActionNotice(currency.name + (wasWatched ? ' removed from watchlist.' : ' added to watchlist.'));
                    });
                },
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
                    currency.isTonAsset = this.isTonAsset(currency);

                    const marketCap = parseFloat(currency.marketCap);
                    const totalVolume = parseFloat(currency.totalVolume);
                    const volumePerMarketCap = totalVolume / marketCap;
                    currency.volumePerMarketCap = _.isFinite(volumePerMarketCap) ? volumePerMarketCap : null;

                    return currency;
                },
                vsConverted: function (priceObj) {
                    return _.get(priceObj, this.$root.vsCurrencyId, null);
                },
                currencyMarketUpdatedAt: function () {
                    return _.get(this.currency, 'last_updated') || _.get(this.currency, 'market_data.last_updated') || null;
                },
                currencyMarketAgeSeconds: function () {
                    return GeckoClient.ai ? GeckoClient.ai.marketDataAgeSeconds({last_updated_at: this.currencyMarketUpdatedAt()}) : 0;
                },
                currencyShareFreshnessLabel: function () {
                    const timestamp = this.currencyMarketUpdatedAt();
                    return timestamp ? 'Updated ' + this.relativeTime(timestamp) : 'Freshness unavailable';
                },
                currencyInsightMarketData: function () {
                    const currency = this.currency || {};
                    return {
                        vs_currency: this.$root.vsCurrencyId,
                        asset: {
                            id: currency.id,
                            symbol: currency.symbol,
                            name: currency.name,
                            rank: currency.market_cap_rank,
                            price: currency.currentPrice,
                            change_24h_percent: currency.change24hPercent,
                            market_cap: currency.marketCap,
                            market_cap_change_24h: currency.marketCapChange24h,
                            market_cap_change_24h_percent: currency.marketCapChange24hPercent,
                            volume_24h: currency.totalVolume,
                            circulating_supply: currency.circulatingSupply,
                            total_supply: currency.totalSupply,
                            volume_market_cap_ratio: currency.volumePerMarketCap,
                            is_ton_asset: currency.isTonAsset
                        },
                        community_score: _.get(currency, 'community_score', null),
                        developer_score: _.get(currency, 'developer_score', null),
                        liquidity_score: _.get(currency, 'liquidity_score', null),
                        coingecko_score: _.get(currency, 'coingecko_score', null)
                    };
                },
                prepareAlertDraft: function () {
                    if (!this.currency) return;

                    const draft = {
                        coin_id: this.currency.id,
                        symbol: this.currency.symbol,
                        name: this.currency.name,
                        vs_currency: this.$root.vsCurrencyId,
                        created_at: (new Date()).toISOString()
                    };

                    const alertDraftKey = _.get(GeckoClient, 'alertsConfig.draftStorageKey', 'TONBANKCARD:alertDraft');
                    window.localStorage.setItem(alertDraftKey, JSON.stringify(draft));
                    this.showActionNotice('Opening alert draft for ' + this.currency.name + '.');
                    this.$router.push({
                        name: 'alerts',
                        query: {
                            coin: this.currency.id,
                            symbol: this.currency.symbol
                        }
                    }).catch(() => {});
                },
                shareCurrency: function () {
                    if (!this.currency || !GeckoClient.share) return;

                    GeckoClient.share.share(this.currencyShareCard)
                        .then(shared => {
                            if (shared) this.showActionNotice('Share link ready for ' + this.currency.name + '.');
                        });
                },
                showActionNotice: function (message) {
                    this.actionNotice = message;
                    this.actionNoticeModel = true;
                },
                isTonAsset: function (currency) {
                    const id = _.toLower(_.get(currency, 'id', ''));
                    const symbol = _.toLower(_.get(currency, 'symbol', ''));
                    const platforms = _.keys(_.get(currency, 'platforms', {})).map(key => _.toLower(key));
                    const categories = (_.get(currency, 'categories', []) || []).map(category => _.toLower(category));

                    return id === 'toncoin'
                        || id === 'the-open-network'
                        || symbol === 'ton'
                        || platforms.indexOf('the-open-network') >= 0
                        || platforms.indexOf('ton') >= 0
                        || categories.some(category => category.indexOf('ton ecosystem') >= 0 || category.indexOf('the open network') >= 0);
                },
                relativeTime: function (timestamp) {
                    const date = new Date(timestamp);
                    if (!GeckoClient.utils.isValidDate(date)) return 'unknown';

                    const seconds = Math.max(0, Math.floor((Date.now() - date.getTime()) / 1000));
                    if (seconds < 60) return 'now';
                    if (seconds < 3600) return Math.floor(seconds / 60) + 'm ago';
                    if (seconds < 86400) return Math.floor(seconds / 3600) + 'h ago';
                    return Math.floor(seconds / 86400) + 'd ago';
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
        };
    }

    GeckoClient.router.addRoute({
        name: 'currency',
        path: currencyRouteConfig.path,
        component: currencyComponent()
    });

    if (coinsRouteConfig) {
        GeckoClient.router.addRoute({
            name: 'coins',
            path: coinsRouteConfig.path,
            component: currencyComponent()
        });
    }

})(window, _, CoinGecko, GeckoClient);
