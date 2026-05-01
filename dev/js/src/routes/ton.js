(function (window, _, axios, CoinGecko, GeckoClient) {
    'use strict';

    const route = GeckoClient.routesConfig.ton;
    const options = GeckoClient.getOptions('ton');
    if (!route) return;

    const normalizeTag = value => {
        value = _.toString(value || '').toLowerCase().trim().replace(/[^a-z0-9._-]+/g, '_').replace(/^[._-]+|[._-]+$/g, '');
        return value === 'ton' ? 'ton_ecosystem' : value;
    };

    GeckoClient.router.addRoute({
        name: 'ton',
        path: route.path,
        component: {
            template: '#route-ton',
            data: function () {
                return {
                    tonAssets: [],
                    tonCategories: {},
                    tonLists: {},
                    tonMeta: null,
                    marketMeta: null,
                    tonMarketMap: {},
                    loadingCuration: false,
                    loadingTonMarkets: false,
                    curationError: '',
                    marketError: '',
                    tonFilters: {
                        tag: '',
                        category: '',
                        list: '',
                        state: ''
                    }
                };
            },
            created: function () {
                GeckoClient.setTitle(options.title);
                this.syncFiltersFromRoute();
                this.fetchTonEcosystem();
            },
            watch: {
                '$root.vsCurrencyId': function () {
                    this.fetchTonMarkets();
                },
                '$route.query': function () {
                    this.syncFiltersFromRoute();
                }
            },
            computed: {
                categoryList: function () {
                    return _.sortBy(_.map(this.tonCategories), 'title');
                },
                ecosystemLists: function () {
                    return _.sortBy(_.map(this.tonLists), 'title');
                },
                visibleTags: function () {
                    const hidden = ['ton_ecosystem', 'ton_asset'];
                    const tags = _.uniq(_.flatMap(this.tonAssets, asset => asset.tags || []))
                        .map(normalizeTag)
                        .filter(tag => tag && hidden.indexOf(tag) === -1);
                    return _.take(_.sortBy(tags), 12);
                },
                featuredAssets: function () {
                    return this.decorateAssets(this.tonAssets.filter(asset => asset.featured || _.includes(asset.list_ids || [], 'featured')));
                },
                filteredAssets: function () {
                    return this.decorateAssets(this.tonAssets.filter(asset => this.assetMatchesFilters(asset)));
                },
                hasActiveFilters: function () {
                    return !!(this.tonFilters.tag || this.tonFilters.category || this.tonFilters.list || this.tonFilters.state);
                },
                verifiedCount: function () {
                    return this.tonAssets.filter(asset => asset.verification_state === 'verified').length;
                },
                unverifiedCount: function () {
                    return this.tonAssets.filter(asset => asset.verification_state === 'unverified').length;
                },
                curationUpdatedLabel: function () {
                    const updatedAt = _.get(this.tonMeta, 'ton_ecosystem.updated_at') || _.get(this.tonMeta, 'ton_ecosystem.index_built_at');
                    return updatedAt || (this.tonAssets.length ? 'Built-in defaults' : 'Pending');
                },
                tonInsightContext: function () {
                    if (!GeckoClient.ai || !this.tonAssets.length) return null;

                    return {
                        insight_type: 'ton_ecosystem_pulse',
                        subject: 'TON ecosystem pulse',
                        market_data_age_seconds: GeckoClient.ai.marketDataAgeSeconds(this.marketMeta || this.tonMeta),
                        market_data_updated_at: GeckoClient.ai.marketDataUpdatedAt(this.marketMeta || this.tonMeta),
                        market_data: {
                            vs_currency: this.$root.vsCurrencyId,
                            assets: this.filteredAssets.map(asset => {
                                const market = asset.market || {};
                                return {
                                    id: asset.id,
                                    coin_id: asset.coin_id || null,
                                    name: asset.name,
                                    symbol: asset.symbol,
                                    category: asset.category,
                                    verification_state: asset.verification_state,
                                    current_price: market.current_price || null,
                                    price_change_percentage_24h: market.price_change_percentage_24h_in_currency || null,
                                    market_cap: market.market_cap || null
                                };
                            }),
                            coverage: [
                                'Curated TON categories',
                                'Verified and unverified asset states',
                                'TON-tagged market filters'
                            ]
                        }
                    };
                },
                tonShareCard: function () {
                    const mover = _.first(this.filteredAssets.filter(asset => {
                        return _.isFinite(parseFloat(_.get(asset, 'market.price_change_percentage_24h_in_currency')));
                    }));

                    return {
                        title: 'TON ecosystem movers',
                        subtitle: this.filteredAssets.length + ' visible assets',
                        body: 'Curated TON assets with verification state, categories, and 24h market movement.',
                        route: _.get(this.$route, 'fullPath') || '/ton',
                        campaign: 'ton-movers',
                        context: 'ton_movers',
                        freshness: 'Updated ' + this.curationUpdatedLabel,
                        metrics: [
                            {label: 'Assets', value: String(this.tonAssets.length)},
                            {label: 'Verified', value: String(this.verifiedCount)},
                            {label: 'Visible', value: String(this.filteredAssets.length)},
                            {label: 'Top mover', value: mover ? _.toUpper(mover.symbol || mover.id) + ' ' + this.$root.changeFormat(_.get(mover, 'market.price_change_percentage_24h_in_currency')) : 'N/A'}
                        ]
                    };
                }
            },
            methods: {
                tonEndpoint: function () {
                    return options.apiBaseUrl || '/api/ton/assets';
                },
                syncFiltersFromRoute: function () {
                    const query = this.$route.query || {};
                    this.tonFilters = {
                        tag: normalizeTag(query.tag || ''),
                        category: normalizeTag(query.category || ''),
                        list: normalizeTag(query.list || ''),
                        state: normalizeTag(query.state || '')
                    };
                },
                fetchTonEcosystem: function () {
                    this.loadingCuration = true;
                    this.curationError = '';

                    return axios.get(this.tonEndpoint())
                        .then(response => {
                            const payload = response.data && response.data.ok === true ? response.data.data : response.data;
                            this.tonAssets = _.get(payload, 'assets', []);
                            this.tonCategories = _.get(payload, 'categories', {});
                            this.tonLists = _.get(payload, 'lists', {});
                            this.tonMeta = response.data && response.data.meta ? response.data.meta : null;
                            return this.fetchTonMarkets();
                        })
                        .catch(() => {
                            this.tonAssets = [];
                            this.tonCategories = {};
                            this.tonLists = {};
                            this.tonMeta = null;
                            this.curationError = 'TON curation feed unavailable';
                        })
                        .finally(() => this.loadingCuration = false);
                },
                fetchTonMarkets: function () {
                    const ids = _.uniq(this.tonAssets.map(asset => asset.coin_id).filter(Boolean));
                    if (!ids.length) {
                        this.tonMarketMap = {};
                        this.marketMeta = null;
                        return Promise.resolve([]);
                    }

                    const params = {
                        ids: ids.join(','),
                        per_page: Math.min(250, ids.length),
                        page: 1,
                        order: 'market_cap_desc',
                        vs_currency: this.$root.vsCurrencyId,
                        price_change_percentage: '24h,7d,30d',
                        sparkline: false
                    };
                    const config = {params: params};

                    this.loadingTonMarkets = true;
                    this.marketError = '';

                    return CoinGecko.coinsMarkets(params)
                        .then(currencies => {
                            this.tonMarketMap = _.keyBy(currencies || [], 'id');
                            this.marketMeta = CoinGecko.metaGet('coins/markets', config) || null;
                            return currencies;
                        })
                        .catch(() => {
                            this.tonMarketMap = {};
                            this.marketMeta = null;
                            this.marketError = 'TON market snapshot unavailable';
                            return [];
                        })
                        .finally(() => this.loadingTonMarkets = false);
                },
                decorateAssets: function (assets) {
                    return assets.map(asset => Object.assign({}, asset, {
                        market: asset.coin_id ? (this.tonMarketMap[asset.coin_id] || null) : null,
                        categoryLabel: this.categoryTitle(asset.category)
                    }));
                },
                assetMatchesFilters: function (asset) {
                    if (this.tonFilters.category && this.tonFilters.category !== asset.category) return false;
                    if (this.tonFilters.state && this.tonFilters.state !== asset.verification_state) return false;
                    if (this.tonFilters.list && !_.includes(asset.list_ids || [], this.tonFilters.list)) return false;

                    if (this.tonFilters.tag) {
                        const tags = (asset.tags || []).map(normalizeTag);
                        const lists = (asset.list_ids || []).map(normalizeTag);
                        if (
                            !_.includes(tags, this.tonFilters.tag)
                            && !_.includes(lists, this.tonFilters.tag)
                            && normalizeTag(asset.category) !== this.tonFilters.tag
                            && normalizeTag(asset.verification_state) !== this.tonFilters.tag
                        ) {
                            return false;
                        }
                    }

                    return true;
                },
                filterRoute: function (field, value) {
                    const query = Object.assign({}, this.$route.query || {});
                    value = normalizeTag(value || '');
                    if (value) query[field] = value;
                    else delete query[field];
                    return {name: 'ton', query: query};
                },
                clearFiltersRoute: function () {
                    return {name: 'ton'};
                },
                categoryTitle: function (id) {
                    return _.get(this.tonCategories, [id, 'title'], _.startCase(id || 'TON'));
                },
                listTitle: function (id) {
                    return _.get(this.tonLists, [id, 'title'], _.startCase(id || 'TON list'));
                },
                stateLabel: function (state) {
                    return _.startCase(state || 'unverified');
                },
                stateIcon: function (state) {
                    if (state === 'verified') return 'mdi-shield-check';
                    if (state === 'curated') return 'mdi-bookmark-check-outline';
                    return 'mdi-alert-circle-outline';
                },
                stateColor: function (state) {
                    if (state === 'verified') return 'success';
                    if (state === 'curated') return 'primary';
                    return 'warning';
                },
                assetRoute: function (asset) {
                    if (asset.route) return asset.route;
                    if (asset.coin_id) return {name: 'currency', params: {id: asset.coin_id}};
                    return {name: 'ton', query: {category: asset.category}};
                },
                marketRoute: function (tag) {
                    return {name: 'markets', query: {tag: normalizeTag(tag)}};
                },
                shareTonMovers: function () {
                    if (!GeckoClient.share) return;
                    GeckoClient.share.share(this.tonShareCard);
                }
            }
        }
    });

})(window, _, axios, CoinGecko, GeckoClient);
