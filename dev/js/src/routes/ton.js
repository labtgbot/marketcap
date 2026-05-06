(function (window, _, axios, CoinGecko, GeckoClient) {
    'use strict';

    const route = GeckoClient.routesConfig.ton;
    const options = GeckoClient.getOptions('ton');
    if (!route) return;

    const adminTokenStorageKey = 'TONBANKCARD:adminToken';
    const adminApiBaseUrl = '/api/admin';
    const tonCategoryOptions = ['native', 'stablecoin', 'jetton', 'defi', 'wallet', 'infrastructure', 'community'];
    const tonVerificationStateOptions = ['verified', 'curated', 'unverified'];
    const tonLinkTypeOptions = [
        {value: 'currency', text: 'Cryptocurrency page'},
        {value: 'project', text: 'Project catalog page'}
    ];
    const tonProjectCategoryOptions = ['defi', 'wallet', 'infrastructure', 'community', 'jetton', 'stablecoin', 'native'];

    const normalizeTag = value => {
        value = _.toString(value || '').toLowerCase().trim().replace(/[^a-z0-9._-]+/g, '_').replace(/^[._-]+|[._-]+$/g, '');
        return value === 'ton' ? 'ton_ecosystem' : value;
    };

    const slugId = value => {
        return _.toString(value || '').toLowerCase().trim().replace(/[^a-z0-9._-]+/g, '-').replace(/^-+|-+$/g, '');
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
                    },
                    adminToken: localStorage.getItem(adminTokenStorageKey) || '',
                    adminActor: null,
                    adminLoading: false,
                    adminSaving: false,
                    adminNotice: '',
                    adminNoticeType: 'info',
                    editorOpen: false,
                    editorAsset: null,
                    editorIndex: -1,
                    editorIsNew: false,
                    tonCategoryOptions: tonCategoryOptions.slice(),
                    tonVerificationStateOptions: tonVerificationStateOptions.slice(),
                    tonLinkTypeOptions: tonLinkTypeOptions.slice(),
                    tonProjectCategoryOptions: tonProjectCategoryOptions.slice()
                };
            },
            created: function () {
                GeckoClient.setTitle(options.title);
                this.syncFiltersFromRoute();
                this.trackTonAchievement();
                this.fetchTonEcosystem();
                if (this.adminToken) this.refreshAdminSession();
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
                isAdminAuthenticated: function () {
                    return !!this.adminToken && !!_.get(this.adminActor, 'role');
                },
                canEditCuration: function () {
                    return this.isAdminAuthenticated && _.get(this.adminActor, 'permissions.write') === true;
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
                    // Always request the-open-network so Toncoin keeps market data even
                    // when its curated entry is missing coin_id (admin override regression).
                    const ids = _.uniq(
                        this.tonAssets.map(asset => asset.coin_id).filter(Boolean).concat(['the-open-network'])
                    );
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
                        market: this.marketForAsset(asset),
                        categoryLabel: this.categoryTitle(asset.category)
                    }));
                },
                marketForAsset: function (asset) {
                    if (!asset) return null;
                    if (asset.coin_id) {
                        const direct = this.tonMarketMap[asset.coin_id];
                        if (direct) return direct;
                    }
                    if (asset.id === 'toncoin' && this.tonMarketMap['the-open-network']) {
                        return this.tonMarketMap['the-open-network'];
                    }
                    const symbol = _.toLower(asset.symbol || '');
                    if (!symbol) return null;
                    return _.find(this.tonMarketMap, currency => _.toLower(currency.symbol) === symbol) || null;
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
                    if (!asset) return {name: 'ton'};
                    if (asset.link_type === 'currency' && asset.coin_id) return {name: 'currency', params: {id: asset.coin_id}};
                    if (asset.link_type === 'project' && asset.id) return {name: 'ton-asset', params: {id: asset.id}};
                    if (asset.id) return {name: 'ton-asset', params: {id: asset.id}};
                    if (asset.route) return asset.route;
                    if (asset.coin_id) return {name: 'currency', params: {id: asset.coin_id}};
                    return {name: 'ton', query: {category: asset.category || ''}};
                },
                coinRoute: function (asset) {
                    if (asset && asset.coin_id) return {name: 'currency', params: {id: asset.coin_id}};
                    return null;
                },
                marketRoute: function (tag) {
                    return {name: 'markets', query: {tag: normalizeTag(tag)}};
                },
                shareTonMovers: function () {
                    if (!GeckoClient.share) return;
                    GeckoClient.share.share(this.tonShareCard);
                },
                trackTonAchievement: function () {
                    if (!GeckoClient.achievements) return;
                    GeckoClient.achievements.track('ton_viewed', {source_route: 'ton'});
                },
                adminClient: function () {
                    return axios.create({
                        baseURL: adminApiBaseUrl,
                        headers: this.adminToken ? {Authorization: 'Bearer ' + this.adminToken} : {}
                    });
                },
                refreshAdminSession: function () {
                    if (!this.adminToken) {
                        this.adminActor = null;
                        return Promise.resolve(null);
                    }
                    this.adminLoading = true;
                    return this.adminClient().get('/config')
                        .then(response => {
                            this.adminActor = _.get(response, 'data.data.actor') || null;
                            return this.adminActor;
                        })
                        .catch(error => {
                            if (_.get(error, 'response.status') === 401) {
                                localStorage.removeItem(adminTokenStorageKey);
                                this.adminToken = '';
                            }
                            this.adminActor = null;
                            return null;
                        })
                        .finally(() => this.adminLoading = false);
                },
                openEditor: function (asset, index) {
                    if (!this.canEditCuration) return;
                    const source = asset || {};
                    const link_type = source.link_type || (source.coin_id ? 'currency' : 'project');
                    this.editorAsset = {
                        id: source.id || '',
                        coin_id: source.coin_id || '',
                        name: source.name || '',
                        symbol: source.symbol || '',
                        category: source.category || 'jetton',
                        verification_state: source.verification_state || 'curated',
                        link_type: link_type,
                        project_category: source.project_category || (link_type === 'project' ? source.category || '' : ''),
                        description: source.description || '',
                        featured: !!source.featured,
                        tags: Array.isArray(source.tags) ? source.tags.slice() : ['ton_ecosystem'],
                        list_ids: Array.isArray(source.list_ids) ? source.list_ids.slice() : []
                    };
                    this.editorIndex = (typeof index === 'number') ? index : -1;
                    this.editorIsNew = !source.id;
                    this.editorOpen = true;
                },
                openCreator: function () {
                    if (!this.canEditCuration) return;
                    this.openEditor(null, -1);
                    this.editorIsNew = true;
                },
                closeEditor: function () {
                    this.editorOpen = false;
                    this.editorAsset = null;
                    this.editorIndex = -1;
                    this.editorIsNew = false;
                },
                fetchAdminContent: function () {
                    return this.adminClient().get('/config')
                        .then(response => {
                            const data = _.get(response, 'data.data') || {};
                            this.adminActor = data.actor || this.adminActor;
                            return _.get(data, 'content') || {ton_assets: []};
                        });
                },
                saveEditor: function () {
                    if (!this.canEditCuration || !this.editorAsset) return;
                    const draft = this.editorAsset;
                    if (!draft.id) draft.id = slugId(draft.name || draft.symbol);
                    if (!draft.id || !draft.name) {
                        this.adminNotice = 'Asset name and id are required.';
                        this.adminNoticeType = 'error';
                        return;
                    }
                    this.adminSaving = true;
                    this.adminNotice = '';
                    return this.fetchAdminContent()
                        .then(content => {
                            const assets = Array.isArray(content.ton_assets) ? content.ton_assets.slice() : [];
                            const matchIndex = _.findIndex(assets, entry => entry && entry.id === draft.id);
                            const link_type = draft.link_type === 'currency' ? 'currency' : 'project';
                            const payload = {
                                id: draft.id,
                                coin_id: draft.coin_id || '',
                                name: draft.name,
                                symbol: draft.symbol || '',
                                category: draft.category || 'jetton',
                                verification_state: draft.verification_state || 'curated',
                                link_type: link_type,
                                project_category: link_type === 'project' ? (draft.project_category || draft.category || '') : '',
                                description: draft.description || '',
                                featured: !!draft.featured,
                                tags: Array.isArray(draft.tags) ? draft.tags : [],
                                list_ids: Array.isArray(draft.list_ids) ? draft.list_ids : []
                            };
                            if (matchIndex >= 0) {
                                assets[matchIndex] = _.assign({}, assets[matchIndex], payload);
                            } else {
                                assets.push(payload);
                            }
                            return this.adminClient().put('/content', {
                                content: _.assign({}, content, {ton_assets: assets})
                            });
                        })
                        .then(() => {
                            this.adminNotice = 'TON asset saved.';
                            this.adminNoticeType = 'success';
                            this.closeEditor();
                            return this.fetchTonEcosystem();
                        })
                        .catch(error => {
                            this.adminNotice = _.get(error, 'response.data.error.message') || 'Saving the asset failed.';
                            this.adminNoticeType = 'error';
                        })
                        .finally(() => this.adminSaving = false);
                },
                deleteAsset: function (asset) {
                    if (!this.canEditCuration || !asset || !asset.id) return;
                    if (typeof window.confirm === 'function' && !window.confirm('Remove ' + (asset.name || asset.id) + ' from the TON catalog?')) return;
                    this.adminSaving = true;
                    this.adminNotice = '';
                    return this.fetchAdminContent()
                        .then(content => {
                            const assets = (Array.isArray(content.ton_assets) ? content.ton_assets : [])
                                .filter(entry => entry && entry.id !== asset.id);
                            const excluded = _.uniq((Array.isArray(content.ton_excluded_asset_ids) ? content.ton_excluded_asset_ids : []).concat([asset.id]));
                            return this.adminClient().put('/content', {
                                content: _.assign({}, content, {ton_assets: assets, ton_excluded_asset_ids: excluded})
                            });
                        })
                        .then(() => {
                            this.adminNotice = 'TON asset removed.';
                            this.adminNoticeType = 'success';
                            return this.fetchTonEcosystem();
                        })
                        .catch(error => {
                            this.adminNotice = _.get(error, 'response.data.error.message') || 'Removing the asset failed.';
                            this.adminNoticeType = 'error';
                        })
                        .finally(() => this.adminSaving = false);
                }
            }
        }
    });

})(window, _, axios, CoinGecko, GeckoClient);
