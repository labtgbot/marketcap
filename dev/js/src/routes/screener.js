(function (window, _, axios, GeckoClient) {
    'use strict';

    const route = GeckoClient.routesConfig.screener;
    const options = GeckoClient.getOptions('screener');
    if (!route) return;

    const normalizeTag = value => {
        value = _.toString(value || '').toLowerCase().trim().replace(/[^a-z0-9._-]+/g, '_').replace(/^[._-]+|[._-]+$/g, '');
        return value === 'ton' ? 'ton_ecosystem' : value;
    };

    GeckoClient.router.addRoute({
        name: 'screener',
        path: route.path,
        component: {
            template: '#route-screener',
            data: function () {
                return {
                    tonAssets: [],
                    tonCategories: {},
                    loadingTonFilters: false,
                    tonFilterError: '',
                    filters: {
                        tag: '',
                        category: '',
                        state: ''
                    }
                };
            },
            created: function () {
                GeckoClient.setTitle(options.title);
                this.syncFiltersFromRoute();
                this.fetchTonCuration();
            },
            watch: {
                '$route.query': function () {
                    this.syncFiltersFromRoute();
                }
            },
            computed: {
                categoryFilters: function () {
                    return _.sortBy(_.map(this.tonCategories), 'title');
                },
                tagFilters: function () {
                    const hidden = ['ton_ecosystem', 'ton_asset'];
                    return _.take(_.sortBy(_.uniq(_.flatMap(this.tonAssets, asset => asset.tags || [])
                        .map(normalizeTag)
                        .filter(tag => tag && hidden.indexOf(tag) === -1))), 14);
                },
                filteredAssets: function () {
                    return this.tonAssets.filter(asset => this.assetMatchesFilters(asset));
                },
                hasActiveFilters: function () {
                    return !!(this.filters.tag || this.filters.category || this.filters.state);
                },
                verifiedCount: function () {
                    return this.tonAssets.filter(asset => asset.verification_state === 'verified').length;
                },
                curatedCount: function () {
                    return this.tonAssets.filter(asset => asset.verification_state === 'curated').length;
                },
                unverifiedCount: function () {
                    return this.tonAssets.filter(asset => asset.verification_state === 'unverified').length;
                }
            },
            methods: {
                tonEndpoint: function () {
                    return options.tonApiBaseUrl || '/api/ton/assets';
                },
                syncFiltersFromRoute: function () {
                    const query = this.$route.query || {};
                    this.filters = {
                        tag: normalizeTag(query.tag || ''),
                        category: normalizeTag(query.category || ''),
                        state: normalizeTag(query.state || '')
                    };
                },
                fetchTonCuration: function () {
                    this.loadingTonFilters = true;
                    this.tonFilterError = '';

                    return axios.get(this.tonEndpoint())
                        .then(response => {
                            const payload = response.data && response.data.ok === true ? response.data.data : response.data;
                            this.tonAssets = _.get(payload, 'assets', []);
                            this.tonCategories = _.get(payload, 'categories', {});
                        })
                        .catch(() => {
                            this.tonAssets = [];
                            this.tonCategories = {};
                            this.tonFilterError = 'TON screener filters unavailable';
                        })
                        .finally(() => this.loadingTonFilters = false);
                },
                assetMatchesFilters: function (asset) {
                    if (this.filters.category && this.filters.category !== asset.category) return false;
                    if (this.filters.state && this.filters.state !== asset.verification_state) return false;
                    if (this.filters.tag) {
                        const tags = (asset.tags || []).map(normalizeTag);
                        const lists = (asset.list_ids || []).map(normalizeTag);
                        if (
                            !_.includes(tags, this.filters.tag)
                            && !_.includes(lists, this.filters.tag)
                            && normalizeTag(asset.category) !== this.filters.tag
                            && normalizeTag(asset.verification_state) !== this.filters.tag
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
                    return {name: 'screener', query: query};
                },
                clearFiltersRoute: function () {
                    return {name: 'screener'};
                },
                marketRoute: function (asset) {
                    const tag = this.filters.tag || asset.category || 'ton_ecosystem';
                    return {name: 'markets', query: {tag: normalizeTag(tag)}};
                },
                searchRoute: function (asset) {
                    return {name: 'ton', query: {category: asset.category}};
                },
                categoryTitle: function (id) {
                    return _.get(this.tonCategories, [id, 'title'], _.startCase(id || 'TON'));
                },
                stateLabel: function (state) {
                    return _.startCase(state || 'unverified');
                },
                stateColor: function (state) {
                    if (state === 'verified') return 'success';
                    if (state === 'curated') return 'primary';
                    return 'warning';
                },
                stateIcon: function (state) {
                    if (state === 'verified') return 'mdi-shield-check';
                    if (state === 'curated') return 'mdi-bookmark-check-outline';
                    return 'mdi-alert-circle-outline';
                }
            }
        }
    });

})(window, _, axios, GeckoClient);
