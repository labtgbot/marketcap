(function (window, _, axios, CoinGecko, GeckoClient) {
    'use strict';

    const route = GeckoClient.routesConfig['ton-asset'];
    if (!route) return;

    const options = GeckoClient.getOptions('ton-asset', {
        title: 'TON Ecosystem Asset',
        apiBaseUrl: '/api/ton/assets'
    });
    const adminTokenStorageKey = 'TONBANKCARD:adminToken';
    const adminApiBaseUrl = '/api/admin';
    const tonCategoryOptions = ['native', 'stablecoin', 'jetton', 'defi', 'wallet', 'infrastructure', 'community'];
    const tonVerificationStateOptions = ['verified', 'curated', 'unverified'];

    const normalizeTag = value => {
        value = _.toString(value || '').toLowerCase().trim().replace(/[^a-z0-9._-]+/g, '_').replace(/^[._-]+|[._-]+$/g, '');
        return value === 'ton' ? 'ton_ecosystem' : value;
    };

    GeckoClient.router.addRoute({
        name: 'ton-asset',
        path: route.path,
        component: {
            template: '#route-ton-asset',
            data: function () {
                return {
                    asset: null,
                    tonCategoriesMap: {},
                    loadingCuration: false,
                    loadError: '',
                    adminToken: localStorage.getItem(adminTokenStorageKey) || '',
                    adminActor: null,
                    adminSaving: false,
                    adminNotice: '',
                    adminNoticeType: 'info',
                    editorOpen: false,
                    editorAsset: null,
                    tonCategoryOptions: tonCategoryOptions.slice(),
                    tonVerificationStateOptions: tonVerificationStateOptions.slice()
                };
            },
            created: function () {
                GeckoClient.setTitle(options.title || 'TON Ecosystem Asset');
                this.fetchAsset();
                if (this.adminToken) this.refreshAdminSession();
            },
            watch: {
                '$route.params.id': function () {
                    this.fetchAsset();
                }
            },
            computed: {
                isAdminAuthenticated: function () {
                    return !!this.adminToken && !!_.get(this.adminActor, 'role');
                },
                canEditCuration: function () {
                    return this.isAdminAuthenticated && _.get(this.adminActor, 'permissions.write') === true;
                },
                tonAssetInsightContext: function () {
                    if (!this.asset || !GeckoClient.ai) return null;

                    const market = this.asset.market || null;
                    return {
                        insight_type: 'ton_ecosystem_pulse',
                        subject: (this.asset.name || this.asset.id) + ' TON asset',
                        market_data_age_seconds: GeckoClient.ai.marketDataAgeSeconds(market ? {last_updated_at: _.get(market, 'last_updated')} : null),
                        market_data_updated_at: GeckoClient.ai.marketDataUpdatedAt(market ? {last_updated_at: _.get(market, 'last_updated')} : null),
                        market_data: {
                            vs_currency: this.$root.vsCurrencyId,
                            assets: [{
                                id: this.asset.id,
                                coin_id: this.asset.coin_id || null,
                                name: this.asset.name,
                                symbol: this.asset.symbol,
                                category: this.asset.category,
                                verification_state: this.asset.verification_state,
                                description: this.asset.description || null,
                                current_price: market ? (market.current_price || null) : null,
                                price_change_percentage_24h: market ? (market.price_change_percentage_24h_in_currency || null) : null,
                                market_cap: market ? (market.market_cap || null) : null
                            }],
                            coverage: ['TON asset detail', 'Verification state', 'Category context']
                        }
                    };
                }
            },
            methods: {
                tonEndpoint: function () {
                    return options.apiBaseUrl || '/api/ton/assets';
                },
                assetId: function () {
                    return _.toString(_.get(this.$route, 'params.id') || '').toLowerCase();
                },
                fetchAsset: function () {
                    const id = this.assetId();
                    if (!id) {
                        this.asset = null;
                        return Promise.resolve(null);
                    }

                    this.loadingCuration = true;
                    this.loadError = '';

                    return axios.get(this.tonEndpoint())
                        .then(response => {
                            const payload = response.data && response.data.ok === true ? response.data.data : response.data;
                            this.tonCategoriesMap = _.get(payload, 'categories', {});
                            const assets = _.get(payload, 'assets', []);
                            const found = _.find(assets, asset => asset && asset.id === id);
                            this.asset = found || null;
                            if (found && found.coin_id) {
                                return this.fetchMarket(found);
                            }
                            return found;
                        })
                        .catch(() => {
                            this.asset = null;
                            this.loadError = 'TON asset details unavailable.';
                        })
                        .finally(() => this.loadingCuration = false);
                },
                fetchMarket: function (asset) {
                    if (!asset || !asset.coin_id) return Promise.resolve(asset);
                    return CoinGecko.coinsMarkets({
                        ids: asset.coin_id,
                        per_page: 1,
                        page: 1,
                        order: 'market_cap_desc',
                        vs_currency: this.$root.vsCurrencyId,
                        price_change_percentage: '24h,7d,30d',
                        sparkline: false
                    }).then(currencies => {
                        const market = (currencies || [])[0] || null;
                        if (this.asset) this.asset = _.assign({}, this.asset, {market: market});
                        return this.asset;
                    }).catch(() => asset);
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
                categoryTitle: function (id) {
                    return _.get(this.tonCategoriesMap, [id, 'title'], _.startCase(id || 'TON'));
                },
                backRoute: function () {
                    return {name: 'ton'};
                },
                catalogRoute: function (field, value) {
                    const query = {};
                    value = normalizeTag(value || '');
                    if (value) query[field] = value;
                    return {name: 'ton', query: query};
                },
                categoryRoute: function (category) {
                    return {name: 'ton', query: {category: category || ''}};
                },
                coinRoute: function (asset) {
                    if (asset && asset.coin_id) return {name: 'currency', params: {id: asset.coin_id}};
                    return null;
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
                        });
                },
                fetchAdminContent: function () {
                    return this.adminClient().get('/config')
                        .then(response => {
                            const data = _.get(response, 'data.data') || {};
                            this.adminActor = data.actor || this.adminActor;
                            return _.get(data, 'content') || {ton_assets: []};
                        });
                },
                openEditor: function () {
                    if (!this.canEditCuration || !this.asset) return;
                    const asset = this.asset;
                    this.editorAsset = {
                        id: asset.id,
                        coin_id: asset.coin_id || '',
                        name: asset.name || '',
                        symbol: asset.symbol || '',
                        category: asset.category || 'jetton',
                        verification_state: asset.verification_state || 'curated',
                        description: asset.description || '',
                        featured: !!asset.featured,
                        tags: Array.isArray(asset.tags) ? asset.tags.slice() : ['ton_ecosystem'],
                        list_ids: Array.isArray(asset.list_ids) ? asset.list_ids.slice() : []
                    };
                    this.editorOpen = true;
                },
                closeEditor: function () {
                    this.editorOpen = false;
                    this.editorAsset = null;
                },
                saveEditor: function () {
                    if (!this.canEditCuration || !this.editorAsset) return;
                    const draft = this.editorAsset;
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
                            const payload = {
                                id: draft.id,
                                coin_id: draft.coin_id || '',
                                name: draft.name,
                                symbol: draft.symbol || '',
                                category: draft.category || 'jetton',
                                verification_state: draft.verification_state || 'curated',
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
                            return this.fetchAsset();
                        })
                        .catch(error => {
                            this.adminNotice = _.get(error, 'response.data.error.message') || 'Saving the asset failed.';
                            this.adminNoticeType = 'error';
                        })
                        .finally(() => this.adminSaving = false);
                },
                deleteAsset: function () {
                    if (!this.canEditCuration || !this.asset) return;
                    const asset = this.asset;
                    if (typeof window.confirm === 'function' && !window.confirm('Remove ' + (asset.name || asset.id) + ' from the TON catalog?')) return;
                    this.adminSaving = true;
                    this.adminNotice = '';
                    return this.fetchAdminContent()
                        .then(content => {
                            const assets = (Array.isArray(content.ton_assets) ? content.ton_assets : [])
                                .filter(entry => entry && entry.id !== asset.id);
                            return this.adminClient().put('/content', {
                                content: _.assign({}, content, {ton_assets: assets})
                            });
                        })
                        .then(() => {
                            this.adminNotice = 'TON asset removed.';
                            this.adminNoticeType = 'success';
                            this.$router.push({name: 'ton'});
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
