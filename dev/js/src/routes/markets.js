(function (window, _, axios, CoinGecko, GeckoClient) {
    'use strict';

    const setTitle = GeckoClient.setTitle;

    const marketsRoute = GeckoClient.routesConfig.markets;
    const marketsOptions = GeckoClient.getOptions('markets');
    const tableHeaders = marketsOptions.tableHeaders.filter(header => header.show);
    const perPage = Math.min(250, marketsOptions.perPage) || 100;
    const percentChange = currency => parseFloat(currency.price_change_percentage_24h_in_currency);

    const normalizeTag = value => {
        value = _.toString(value || '').toLowerCase().trim().replace(/[^a-z0-9._-]+/g, '_').replace(/^[._-]+|[._-]+$/g, '');
        return value === 'ton' ? 'ton_ecosystem' : value;
    };

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
                    watchlistUnsubscribe: null,
                    tonAssets: [],
                    tonCategories: {},
                    tonLists: {},
                    loadingTonFilters: false,
                    tonFilterError: ''
                };
            },
            created: function () {
                this.initWatchlist();
                this.fetchTonCuration().finally(() => this.fetchFirstCurrencies());
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
                },
                '$route.query.tag': function () {
                    this.fetchFirstCurrencies();
                }
            },
            computed: {
                activeTonTag: function () {
                    return normalizeTag(_.get(this.$route, 'query.tag', ''));
                },
                tableHeaders: function () {
                    if (this.$vuetify.breakpoint.xs) {
                        // hide rank column in smartphones
                        return _.reject(tableHeaders, ['value', 'market_cap_rank']);
                    }
                    return tableHeaders;
                },
                tonFilterChips: function () {
                    const configured = marketsOptions.tonFilters || [];
                    if (configured.length) {
                        return configured.map(filter => Object.assign({}, filter, {tag: normalizeTag(filter.tag)}));
                    }

                    return _.sortBy(_.map(this.tonCategories, category => ({
                        tag: category.tag || category.id,
                        label: category.title,
                        icon: category.icon || 'mdi-tag-outline'
                    })), 'label');
                },
                activeTonAssets: function () {
                    if (!this.activeTonTag) return [];
                    return this.tonAssets.filter(asset => this.tonAssetMatchesTag(asset, this.activeTonTag));
                },
                activeTonCoinIds: function () {
                    return _.uniq(this.activeTonAssets.map(asset => asset.coin_id).filter(Boolean));
                },
                activeTonFilterLabel: function () {
                    if (!this.activeTonTag) return '';
                    const chip = _.find(this.tonFilterChips, ['tag', this.activeTonTag]);
                    return chip ? chip.label : _.startCase(this.activeTonTag);
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
                tonEndpoint: function () {
                    return marketsOptions.tonApiBaseUrl || '/api/ton/assets';
                },
                fetchTonCuration: function () {
                    if (!axios) return Promise.resolve();

                    this.loadingTonFilters = true;
                    this.tonFilterError = '';

                    return axios.get(this.tonEndpoint())
                        .then(response => {
                            const payload = response.data && response.data.ok === true ? response.data.data : response.data;
                            this.tonAssets = _.get(payload, 'assets', []);
                            this.tonCategories = _.get(payload, 'categories', {});
                            this.tonLists = _.get(payload, 'lists', {});
                        })
                        .catch(() => {
                            this.tonAssets = [];
                            this.tonCategories = {};
                            this.tonLists = {};
                            this.tonFilterError = 'TON filters unavailable';
                        })
                        .finally(() => this.loadingTonFilters = false);
                },
                fetchCurrencies: function () {
                    if (this.activeTonTag && !this.activeTonCoinIds.length) {
                        this.loadMore = false;
                        return Promise.resolve([]);
                    }

                    const params = {
                        per_page: this.activeTonTag ? Math.min(250, this.activeTonCoinIds.length) : this.perPage,
                        page: ++this.page,
                        order: this.order,
                        vs_currency: this.$root.vsCurrencyId,
                        price_change_percentage: this.priceChanges.join(','),
                        sparkline: true
                    };

                    if (this.activeTonTag) {
                        params.ids = this.activeTonCoinIds.join(',');
                        params.page = 1;
                    }

                    return CoinGecko.coinsMarkets(params)
                        .then(currencies => {
                            _.each(currencies, currency => {
                                currency.route = {name: 'currency', params: {id: currency.id}};
                                currency.tonAsset = this.tonAssetForCurrency(currency);
                                this.currencies.push(currency);
                            })
                            this.loadMore = this.activeTonTag ? false : currencies.length === this.perPage;
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
                    return this.fetchCurrencies()
                        .then(currencies => {
                            this.trackMarketAchievements(currencies);
                            return currencies;
                        })
                        .finally(() => this.loading = false);
                },
                fetchMoreCurrencies: function () {
                    this.loadMoreLoading = true;
                    return this.fetchCurrencies().finally(() => this.loadMoreLoading = false);
                },
                trackMarketAchievements: function (currencies) {
                    if (!GeckoClient.achievements || !currencies || !currencies.length) return;

                    GeckoClient.achievements.track('market_check', {source_route: 'markets'});
                    const strongest = _.maxBy(currencies.filter(currency => _.isFinite(percentChange(currency))), currency => Math.abs(percentChange(currency)));
                    if (strongest) {
                        GeckoClient.achievements.trackMarketMovement(strongest, 'markets');
                    }
                },
                tonAssetMatchesTag: function (asset, tag) {
                    tag = normalizeTag(tag);
                    const tags = (asset.tags || []).map(normalizeTag);
                    const lists = (asset.list_ids || []).map(normalizeTag);
                    return _.includes(tags, tag)
                        || _.includes(lists, tag)
                        || normalizeTag(asset.category) === tag
                        || normalizeTag(asset.verification_state) === tag;
                },
                tonAssetForCurrency: function (currency) {
                    const activeIds = this.activeTonTag ? this.activeTonAssets : this.tonAssets;
                    return _.find(activeIds, asset => asset.coin_id === currency.id) || null;
                },
                tonFilterRoute: function (tag) {
                    tag = normalizeTag(tag);
                    return {name: 'markets', query: tag ? {tag: tag} : {}};
                },
                clearTonFilterRoute: function () {
                    return {name: 'markets'};
                },
                tonStateColor: function (asset) {
                    if (!asset) return 'primary';
                    if (asset.verification_state === 'verified') return 'success';
                    if (asset.verification_state === 'curated') return 'primary';
                    return 'warning';
                },
                tonStateLabel: function (asset) {
                    return _.startCase(asset && asset.verification_state ? asset.verification_state : 'TON');
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

})(window, _, axios, CoinGecko, GeckoClient);
