(function (window, _, axios, GeckoClient) {
    'use strict';

    const route = GeckoClient.routesConfig.screener;
    const options = GeckoClient.getOptions('screener');
    if (!route) return;

    const numericFilterKeys = [
        'market_cap_min',
        'market_cap_max',
        'volume_min',
        'volume_max',
        'rank_min',
        'rank_max',
        'change_24h_min',
        'change_24h_max',
        'change_7d_min',
        'change_7d_max',
        'change_30d_min',
        'change_30d_max',
        'sentiment_min',
        'sentiment_max'
    ];
    const textFilterKeys = ['category', 'exchange', 'ton_tag', 'watchlist'];
    const sortAliases = {
        change_24h: 'price_change_percentage_24h_in_currency',
        change_7d: 'price_change_percentage_7d_in_currency',
        change_30d: 'price_change_percentage_30d_in_currency',
        sentiment: 'sentiment_score'
    };
    const allowedSortKeys = [
        'market_cap_rank',
        'name',
        'current_price',
        'market_cap',
        'total_volume',
        'price_change_percentage_24h_in_currency',
        'price_change_percentage_7d_in_currency',
        'price_change_percentage_30d_in_currency',
        'sentiment_score',
        'exchange_available'
    ];

    function defaultFilters() {
        const filters = {
            category: '',
            exchange: '',
            ton_tag: '',
            watchlist: 'all'
        };
        numericFilterKeys.forEach(key => filters[key] = '');
        return filters;
    }

    function normalizeSlug(value) {
        value = _.toString(value || '').toLowerCase().trim().replace(/[^a-z0-9._-]+/g, '_').replace(/^[._-]+|[._-]+$/g, '');
        return value === 'ton' ? 'ton_ecosystem' : value;
    }

    function normalizeWatchlist(value) {
        value = normalizeSlug(value || 'all');
        return _.includes(['all', 'watched', 'unwatched'], value) ? value : 'all';
    }

    function normalizeSort(value) {
        value = _.isArray(value) ? _.first(value) : value;
        value = _.toString(value || 'market_cap_rank');
        value = sortAliases[value] || value;
        return _.includes(allowedSortKeys, value) ? value : 'market_cap_rank';
    }

    function normalizeDirection(value) {
        if (_.isArray(value)) value = _.first(value);
        return value === true || value === 'desc' ? 'desc' : 'asc';
    }

    function cleanNumber(value) {
        value = _.toString(value === null || value === undefined ? '' : value).trim();
        if (!value) return '';
        const number = parseFloat(value);
        return _.isFinite(number) ? String(number) : '';
    }

    function responseData(response) {
        const payload = response && response.data ? response.data : {};
        return payload.ok === true ? payload.data || {} : payload;
    }

    function telegramInitData() {
        return _.get(GeckoClient, 'telegram.webApp.initData') || _.get(window, 'Telegram.WebApp.initData') || '';
    }

    function asArray(value) {
        return _.isArray(value) ? value : [];
    }

    GeckoClient.router.addRoute({
        name: 'screener',
        path: route.path,
        component: {
            template: '#route-screener',
            data: function () {
                return {
                    items: [],
                    summary: {},
                    filterOptions: {},
                    filters: defaultFilters(),
                    sortBy: 'market_cap_rank',
                    sortDesc: false,
                    loading: false,
                    errorMessage: '',
                    filterDrawer: false,
                    watchlistIds: [],
                    watchlistUnsubscribe: null,
                    presets: [],
                    selectedPresetId: null,
                    presetName: '',
                    presetSyncEnabled: false,
                    presetSyncChecked: false,
                    presetSyncError: '',
                    loadingPresets: false,
                    savingPreset: false,
                    deletingPreset: false
                };
            },
            created: function () {
                GeckoClient.setTitle(options.title);
                this.syncFiltersFromRoute();
                this.initWatchlist();
                this.bootstrapPresetSync();
                this.fetchScreenerResults();
            },
            beforeDestroy: function () {
                if (this.watchlistUnsubscribe) this.watchlistUnsubscribe();
            },
            watch: {
                '$route.query': function () {
                    this.syncFiltersFromRoute();
                    this.fetchScreenerResults();
                },
                '$root.vsCurrencyId': function () {
                    this.fetchScreenerResults();
                }
            },
            computed: {
                tableHeaders: function () {
                    const headers = asArray(options.tableHeaders).filter(header => header.show !== false);
                    if (this.$vuetify.breakpoint.xs) {
                        return _.reject(headers, header => _.includes(['market_cap_rank', 'exchange_available'], header.value));
                    }
                    return headers;
                },
                categoryOptions: function () {
                    const configured = asArray(options.categoryOptions);
                    const responseCategories = asArray(this.filterOptions.categories).map(category => ({
                        text: category.title || _.startCase(category.id || category.slug || category.name || ''),
                        value: category.id || category.slug || category.name || ''
                    })).filter(category => category.value);
                    return configured.length ? configured : [{text: 'All categories', value: ''}].concat(responseCategories);
                },
                exchangeOptions: function () {
                    const configured = asArray(options.exchangeOptions);
                    const responseExchanges = asArray(this.filterOptions.exchanges).map(exchange => ({
                        text: exchange.name || _.startCase(exchange.id || ''),
                        value: exchange.id || ''
                    })).filter(exchange => exchange.value);
                    return configured.length ? configured : [{text: 'Any exchange', value: ''}].concat(responseExchanges);
                },
                tonTagOptions: function () {
                    const configured = asArray(options.tonTagOptions);
                    const responseTags = asArray(this.filterOptions.ton_tags).map(tag => ({
                        text: _.startCase(tag),
                        value: tag
                    }));
                    return configured.length ? configured : [{text: 'All assets', value: ''}].concat(responseTags);
                },
                watchlistFilterOptions: function () {
                    return [
                        {text: 'All assets', value: 'all'},
                        {text: 'Watched only', value: 'watched'},
                        {text: 'Unwatched only', value: 'unwatched'}
                    ];
                },
                hasActiveFilters: function () {
                    return this.activeFilterChips.length > 0;
                },
                activeFilterChips: function () {
                    const labels = {
                        category: 'Category',
                        exchange: 'Exchange',
                        ton_tag: 'TON',
                        watchlist: 'Watchlist',
                        market_cap_min: 'MCap min',
                        market_cap_max: 'MCap max',
                        volume_min: 'Volume min',
                        volume_max: 'Volume max',
                        rank_min: 'Rank min',
                        rank_max: 'Rank max',
                        change_24h_min: '24h min',
                        change_24h_max: '24h max',
                        change_7d_min: '7d min',
                        change_7d_max: '7d max',
                        change_30d_min: '30d min',
                        change_30d_max: '30d max',
                        sentiment_min: 'Sentiment min',
                        sentiment_max: 'Sentiment max'
                    };
                    const chips = [];
                    Object.keys(labels).forEach(key => {
                        const value = this.filters[key];
                        if (!value || (key === 'watchlist' && value === 'all')) return;
                        chips.push({
                            key: key,
                            icon: key === 'watchlist' ? 'mdi-star-outline' : (key === 'ton_tag' ? 'mdi-diamond-stone' : 'mdi-filter-outline'),
                            label: labels[key] + ': ' + this.displayFilterValue(key, value)
                        });
                    });
                    return chips;
                },
                emptyFilterSummary: function () {
                    if (this.hasActiveFilters) {
                        return 'No assets match the active screener filters: ' + this.activeFilterChips.map(chip => chip.label).join(', ') + '.';
                    }
                    return 'No market rows are available from the backend dataset right now.';
                },
                resultSummaryText: function () {
                    const sourceCount = parseInt(this.summary.source_count || 0, 10);
                    const resultCount = parseInt(this.summary.result_count || this.items.length || 0, 10);
                    return resultCount + ' matches from ' + sourceCount + ' backend rows';
                },
                sentimentSummary: function () {
                    if (this.summary.average_sentiment === null || this.summary.average_sentiment === undefined) return 'n/a';
                    const score = parseInt(this.summary.average_sentiment, 10);
                    return (_.isFinite(score) && score > 0 ? '+' : '') + score;
                },
                presetOptions: function () {
                    return this.presets.map(preset => ({
                        text: preset.name,
                        value: preset.id
                    }));
                },
                presetStatusText: function () {
                    if (this.loadingPresets) return 'Loading presets';
                    if (this.presetSyncEnabled) return this.presets.length ? 'Trusted preset sync active' : 'Trusted preset sync active; no presets saved';
                    if (this.presetSyncError) return this.presetSyncError;
                    return this.presetSyncChecked ? 'Preset sync requires a trusted Telegram session' : 'Checking preset sync';
                }
            },
            methods: {
                endpoint: function () {
                    return options.apiBaseUrl || '/api/screener/markets';
                },
                presetsEndpoint: function () {
                    return options.presetsApiBaseUrl || '/api/screener/presets';
                },
                syncFiltersFromRoute: function () {
                    const query = this.$route.query || {};
                    const next = defaultFilters();

                    textFilterKeys.forEach(key => {
                        if (key === 'watchlist') next[key] = normalizeWatchlist(query[key] || 'all');
                        else next[key] = normalizeSlug(query[key] || '');
                    });
                    numericFilterKeys.forEach(key => {
                        next[key] = cleanNumber(query[key]);
                    });

                    this.filters = next;
                    this.sortBy = normalizeSort(query.sort);
                    this.sortDesc = normalizeDirection(query.direction) === 'desc';
                },
                buildQuery: function () {
                    const query = {};
                    textFilterKeys.forEach(key => {
                        const value = key === 'watchlist' ? normalizeWatchlist(this.filters[key]) : normalizeSlug(this.filters[key]);
                        if (value && !(key === 'watchlist' && value === 'all')) query[key] = value;
                    });
                    numericFilterKeys.forEach(key => {
                        const value = cleanNumber(this.filters[key]);
                        if (value) query[key] = value;
                    });

                    const sort = normalizeSort(this.sortBy);
                    const direction = this.sortDesc ? 'desc' : 'asc';
                    if (sort !== 'market_cap_rank') query.sort = sort;
                    if (direction !== 'asc') query.direction = direction;
                    return query;
                },
                requestParams: function () {
                    const params = Object.assign({}, this.buildQuery(), {
                        vs_currency: this.$root.vsCurrencyId,
                        per_page: options.perPage || 100,
                        sort: normalizeSort(this.sortBy),
                        direction: this.sortDesc ? 'desc' : 'asc'
                    });
                    if (this.watchlistIds.length) {
                        params.watchlist_ids = this.watchlistIds.join(',');
                    }
                    return params;
                },
                updateRouteFromState: function () {
                    const query = this.buildQuery();
                    const current = this.$route.query || {};
                    if (_.isEqual(query, current)) {
                        this.fetchScreenerResults();
                        return;
                    }
                    this.$router.replace({name: 'screener', query: query}).catch(() => {});
                },
                applyFilters: function () {
                    this.filterDrawer = false;
                    this.updateRouteFromState();
                },
                resetFilters: function () {
                    this.filters = defaultFilters();
                    this.sortBy = 'market_cap_rank';
                    this.sortDesc = false;
                    this.applyFilters();
                },
                clearFilter: function (key) {
                    if (key === 'watchlist') this.filters[key] = 'all';
                    else if (_.has(this.filters, key)) this.filters[key] = '';
                    this.applyFilters();
                },
                updateSort: function (value) {
                    const next = normalizeSort(value);
                    if (next === this.sortBy) return;
                    this.sortBy = next;
                    this.applyFilters();
                },
                updateSortDirection: function (value) {
                    const next = normalizeDirection(value) === 'desc';
                    if (next === this.sortDesc) return;
                    this.sortDesc = next;
                    this.applyFilters();
                },
                fetchScreenerResults: function () {
                    if (!axios) return Promise.resolve();

                    this.loading = true;
                    this.errorMessage = '';

                    return axios.get(this.endpoint(), {
                        params: this.requestParams(),
                        withCredentials: true
                    }).then(response => {
                        const payload = responseData(response);
                        this.items = asArray(payload.items).map(item => Object.assign({}, item, {
                            route: this.currencyRoute(item)
                        }));
                        this.summary = payload.summary || {};
                        this.filterOptions = payload.filter_options || {};
                    }).catch(() => {
                        this.items = [];
                        this.summary = {};
                        this.errorMessage = 'Screener data is temporarily unavailable.';
                    }).finally(() => {
                        this.loading = false;
                    });
                },
                initWatchlist: function () {
                    const watchlist = GeckoClient.watchlist;
                    if (!watchlist) return;

                    this.watchlistUnsubscribe = watchlist.onChange(() => {
                        this.syncWatchlistIds();
                        this.fetchScreenerResults();
                    });
                    watchlist.init().then(() => {
                        this.syncWatchlistIds();
                        this.fetchScreenerResults();
                    });
                },
                syncWatchlistIds: function () {
                    this.watchlistIds = GeckoClient.watchlist ? GeckoClient.watchlist.ids() : [];
                },
                isWatched: function (item) {
                    const id = item && (item.id || item.coin_id);
                    return this.watchlistIds.indexOf(id) >= 0 || _.get(item, 'watchlist.watched') === true;
                },
                watchlistIcon: function (item) {
                    return this.isWatched(item) ? 'mdi-star' : 'mdi-star-outline';
                },
                watchlistLabel: function (item) {
                    const name = _.get(item, 'name', 'asset');
                    return (this.isWatched(item) ? 'Remove ' : 'Add ') + name + ' ' + (this.isWatched(item) ? 'from' : 'to') + ' Watchlist';
                },
                toggleWatchlist: function (item) {
                    if (!GeckoClient.watchlist) return;

                    GeckoClient.watchlist.toggle(item, {sourceRoute: 'screener'})
                        .then(() => {
                            this.syncWatchlistIds();
                            this.fetchScreenerResults();
                        });
                },
                bootstrapPresetSync: function () {
                    const initData = telegramInitData();
                    if (!initData || !axios) {
                        this.presetSyncChecked = true;
                        this.presetSyncError = 'Preset sync requires a trusted Telegram session';
                        return Promise.resolve(false);
                    }

                    return axios.post('/api/telegram/session', {initData: initData}, {withCredentials: true})
                        .then(response => {
                            const payload = response.data || {};
                            this.presetSyncEnabled = payload.ok === true && _.get(payload, 'data.session.state') === 'telegram_validated';
                            this.presetSyncChecked = true;
                            if (this.presetSyncEnabled) {
                                return this.fetchPresets();
                            }
                            this.presetSyncError = 'Preset sync requires a trusted Telegram session';
                            return false;
                        })
                        .catch(() => {
                            this.presetSyncChecked = true;
                            this.presetSyncEnabled = false;
                            this.presetSyncError = 'Preset sync is unavailable';
                            return false;
                        });
                },
                fetchPresets: function () {
                    if (!this.presetSyncEnabled || !axios) return Promise.resolve();

                    this.loadingPresets = true;
                    return axios.get(this.presetsEndpoint(), {withCredentials: true})
                        .then(response => {
                            const payload = responseData(response);
                            this.presets = asArray(payload.presets);
                        })
                        .catch(() => {
                            this.presets = [];
                            this.presetSyncError = 'Preset sync is unavailable';
                        })
                        .finally(() => this.loadingPresets = false);
                },
                savePreset: function () {
                    if (!this.presetSyncEnabled || !this.presetName || !axios) return;

                    this.savingPreset = true;
                    return axios.post(
                        this.presetsEndpoint(),
                        {
                            name: this.presetName,
                            filters: this.requestParams(),
                            sort: {
                                key: normalizeSort(this.sortBy),
                                direction: this.sortDesc ? 'desc' : 'asc'
                            }
                        },
                        {withCredentials: true}
                    ).then(response => {
                        const payload = responseData(response);
                        this.presets = asArray(payload.presets);
                        if (payload.preset && payload.preset.id) {
                            this.selectedPresetId = payload.preset.id;
                        }
                    }).catch(() => {
                        this.presetSyncError = 'Preset could not be saved';
                    }).finally(() => this.savingPreset = false);
                },
                deletePreset: function () {
                    if (!this.presetSyncEnabled || !this.selectedPresetId || !axios) return;

                    this.deletingPreset = true;
                    return axios.delete(this.presetsEndpoint() + '/' + encodeURIComponent(this.selectedPresetId), {withCredentials: true})
                        .then(response => {
                            const payload = responseData(response);
                            this.presets = asArray(payload.presets);
                            this.selectedPresetId = null;
                            this.presetName = '';
                        })
                        .catch(() => {
                            this.presetSyncError = 'Preset could not be deleted';
                        })
                        .finally(() => this.deletingPreset = false);
                },
                applyPreset: function (id) {
                    const preset = _.find(this.presets, preset => String(preset.id) === String(id));
                    if (!preset) return;

                    const filters = defaultFilters();
                    const presetFilters = preset.filters || {};
                    textFilterKeys.forEach(key => {
                        filters[key] = key === 'watchlist' ? normalizeWatchlist(presetFilters[key]) : normalizeSlug(presetFilters[key]);
                    });
                    numericFilterKeys.forEach(key => {
                        filters[key] = cleanNumber(presetFilters[key]);
                    });
                    this.filters = filters;
                    this.sortBy = normalizeSort(_.get(preset, 'sort.key'));
                    this.sortDesc = normalizeDirection(_.get(preset, 'sort.direction')) === 'desc';
                    this.presetName = preset.name;
                    this.applyFilters();
                },
                displayFilterValue: function (key, value) {
                    const optionSets = {
                        category: this.categoryOptions,
                        exchange: this.exchangeOptions,
                        ton_tag: this.tonTagOptions,
                        watchlist: this.watchlistFilterOptions
                    };
                    const option = _.find(optionSets[key] || [], ['value', value]);
                    return option ? option.text : _.startCase(_.toString(value));
                },
                currencyRoute: function (item) {
                    return {name: 'currency', params: {id: item.id}};
                },
                openCurrency: function (item) {
                    this.$router.push(this.currencyRoute(item));
                },
                sentimentColor: function (score) {
                    score = parseFloat(score);
                    if (!_.isFinite(score)) return 'grey';
                    if (score >= 35) return 'success';
                    if (score <= -35) return 'low';
                    return 'primary';
                },
                sentimentLabel: function (item) {
                    const score = parseInt(_.get(item, 'sentiment.score', item.sentiment_score), 10);
                    const prefix = _.isFinite(score) && score > 0 ? '+' : '';
                    return (_.get(item, 'sentiment.label') || 'score') + ' ' + prefix + (score || 0);
                },
                exchangeIcon: function (item) {
                    if (!this.filters.exchange) return 'mdi-minus';
                    return _.get(item, 'exchange_availability.matched') ? 'mdi-check-circle' : 'mdi-close-circle-outline';
                },
                exchangeIconColor: function (item) {
                    if (!this.filters.exchange) return 'grey';
                    return _.get(item, 'exchange_availability.matched') ? 'success' : 'grey';
                },
                exchangeLabel: function (item) {
                    if (!this.filters.exchange) return 'No exchange filter active';
                    return _.get(item, 'exchange_availability.matched') ? 'Available on selected exchange' : 'Not found on selected exchange';
                },
                tonStateColor: function (asset) {
                    const state = _.get(asset, 'verification_state');
                    if (state === 'verified') return 'success';
                    if (state === 'curated') return 'primary';
                    return 'warning';
                },
                tonStateIcon: function (asset) {
                    const state = _.get(asset, 'verification_state');
                    if (state === 'verified') return 'mdi-shield-check';
                    if (state === 'curated') return 'mdi-bookmark-check-outline';
                    return 'mdi-alert-circle-outline';
                },
                tonStateLabel: function (asset) {
                    return _.startCase(_.get(asset, 'verification_state', 'TON'));
                }
            }
        }
    });

})(window, _, axios, GeckoClient);
