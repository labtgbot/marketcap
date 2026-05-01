(function (window, document, _, axios, Vue, GeckoClient) {
    'use strict';

    const __ = GeckoClient.__;
    const resultGroups = {
        recent: __( 'Recent searches' ),
        action: __( 'Quick actions' ),
        coin: __( 'Coins' ),
        ton_asset: __( 'TON assets' ),
        exchange: __( 'Exchanges' ),
        category: __( 'Categories' )
    };
    const recentLimit = 5;

    Vue.component('gc-search-bar', {
        template: '#component-search-bar',
        data: function () {
            return {
                model: null,
                search: null,
                loading: false,
                hasError: false,
                items: [],
                recent: [],
                requestCounter: 0,
                searchOpened: false,
                searchTimer: null,
                dialog: false,
                menuProps: {
                    contentClass: 'gc-search-menu',
                    maxHeight: 420,
                    offsetY: true,
                    closeOnClick: false
                },
                dialogMenuProps: {
                    contentClass: 'gc-search-menu gc-search-dialog-menu',
                    maxHeight: 560,
                    offsetY: true,
                    closeOnClick: false
                }
            };
        },
        computed: {
            compactSearch: function () {
                return _.get(GeckoClient, 'telegram.active') || _.get(this.$vuetify, 'breakpoint.xs', window.innerWidth < 600);
            },
            noDataText: function () {
                if (this.loading) return __( 'Searching...' );
                if (this.hasError) return __( 'Search is unavailable. Try again.' );
                if (this.getQueryText() === '') return __( 'Start typing or choose a quick action.' );
                return __( 'No results found.' );
            }
        },
        watch: {
            model: function (selected) {
                this.selectResult(selected);
            }
        },
        mounted: function () {
            this.loadRecentSearches();
            window.addEventListener('keydown', this.onGlobalKeydown);
        },
        beforeDestroy: function () {
            window.removeEventListener('keydown', this.onGlobalKeydown);
            if (this.searchTimer) window.clearTimeout(this.searchTimer);
        },
        methods: {
            avatarChar: function (name) {
                return _.toUpper(_.first(name || '')) || '?';
            },
            getQueryText: function () {
                return _.toLower(_.trim(this.search || ''));
            },
            searchEndpoint: function () {
                return _.get(GeckoClient, 'search.apiBaseUrl', '/api/search');
            },
            searchSurface: function () {
                return _.get(GeckoClient, 'analytics.surface', () => 'public_web')();
            },
            recentStorageKey: function () {
                const prefix = _.get(GeckoClient, 'preferences.prefix', 'GeckoClient:');
                return prefix + 'recent_searches';
            },
            resultKey: function (item) {
                if (!item) return '';
                return item.originalSearchId || item.searchId || ((item.type || 'result') + ':' + item.id);
            },
            isResultItem: function (item) {
                return !!(item && !item.header && item.route && item.id && this.resultKey(item));
            },
            normalizeResult: function (item) {
                if (!item || item.header) return item;

                const result = Object.assign({}, item);
                result.type = result.type || 'action';
                result.name = result.name || result.title || result.id || '';
                result.title = result.title || result.name;
                result.subtitle = result.subtitle || result.symbol || this.resultTypeLabel(result);
                result.searchId = result.searchId || (result.type + ':' + result.id);

                return result;
            },
            decoratedRecentSearch: function (item) {
                const result = this.normalizeResult(_.cloneDeep(item));
                const key = this.resultKey(result);
                result.originalSearchId = key;
                result.searchId = 'recent:' + key;
                result.recent = true;
                return result;
            },
            loadRecentSearches: function () {
                let parsed = [];
                try {
                    parsed = JSON.parse(localStorage.getItem(this.recentStorageKey()) || '[]');
                } catch (err) {
                    parsed = [];
                }

                this.recent = (Array.isArray(parsed) ? parsed : [])
                    .map(item => this.normalizeResult(item))
                    .filter(item => this.isResultItem(item))
                    .slice(0, recentLimit);
            },
            saveRecentSearches: function () {
                try {
                    localStorage.setItem(this.recentStorageKey(), JSON.stringify(this.recent.slice(0, recentLimit)));
                } catch (err) {
                    // Storage can be unavailable in private or constrained webviews.
                }
            },
            rememberSearchResult: function (selected) {
                if (!this.isResultItem(selected)) return;

                const result = this.normalizeResult(selected);
                const key = this.resultKey(result);
                const stored = _.cloneDeep(
                    _.pick(result, [
                        'searchId',
                        'type',
                        'id',
                        'coin_id',
                        'exchange_id',
                        'category_id',
                        'title',
                        'name',
                        'subtitle',
                        'symbol',
                        'rank',
                        'large',
                        'image',
                        'tags',
                        'contract_addresses',
                        'route',
                        'links',
                        'analytics'
                    ])
                );
                stored.searchId = key;

                this.recent = [stored]
                    .concat(this.recent.filter(item => this.resultKey(item) !== key))
                    .slice(0, recentLimit);
                this.saveRecentSearches();
            },
            setItems: function (results) {
                const query = this.getQueryText();
                const items = [];
                const seenTypes = {};
                const seenResults = {};

                if (query === '' && this.recent.length) {
                    items.push({header: resultGroups.recent});
                    this.recent.forEach(item => {
                        const recent = this.decoratedRecentSearch(item);
                        seenResults[this.resultKey(recent)] = true;
                        items.push(recent);
                    });
                }

                (results || []).map(item => this.normalizeResult(item)).forEach(item => {
                    if (!this.isResultItem(item)) return;

                    const key = this.resultKey(item);
                    if (seenResults[key]) return;
                    seenResults[key] = true;

                    const type = item.type || 'action';
                    if (!seenTypes[type]) {
                        seenTypes[type] = true;
                        items.push({header: resultGroups[type] || type});
                    }

                    items.push(item);
                });

                this.items = items;
                this.openSearchMenu();
            },
            searchParams: function () {
                return {
                    q: this.getQueryText(),
                    limit: _.get(GeckoClient, 'search.defaultLimit', 12),
                    surface: this.searchSurface()
                };
            },
            fetchData: function (requestId) {
                return axios.get(this.searchEndpoint(), {params: this.searchParams()})
                    .then(response => {
                        if (requestId !== this.requestCounter) return;

                        const payload = response.data || {};
                        const data = payload.ok === true ? payload.data : payload;
                        this.hasError = false;
                        this.setItems(_.get(data, 'results', []));
                    })
                    .catch(() => {
                        if (requestId === this.requestCounter) {
                            this.hasError = true;
                            this.setItems([]);
                        }
                    })
                    .finally(() => {
                        if (requestId === this.requestCounter) {
                            this.loading = false;
                        }
                    });
            },
            searchItems: function () {
                const requestId = ++this.requestCounter;
                this.loading = true;
                this.hasError = false;

                if (this.getQueryText() === '') {
                    this.setItems([]);
                }

                return this.fetchData(requestId);
            },
            queueSearchItems: function (value) {
                if (value !== undefined) {
                    this.search = value;
                }

                if (this.searchTimer) window.clearTimeout(this.searchTimer);
                this.searchTimer = window.setTimeout(() => this.searchItems(), this.getQueryText() === '' ? 0 : 160);
            },
            clearSearch: function () {
                this.search = null;
                this.queueSearchItems('');
            },
            openSearch: function (trigger) {
                this.trackSearchOpened(trigger || 'button');

                if (this.compactSearch) {
                    this.dialog = true;
                    this.$nextTick(() => {
                        this.focusSearchRef('dialogSearch');
                        this.searchItems();
                    });
                    return;
                }

                this.$nextTick(() => {
                    this.focusSearchRef('inlineSearch');
                    this.searchItems();
                });
            },
            closeSearch: function () {
                this.dialog = false;
                this.searchOpened = false;
            },
            focusSearchRef: function (refName) {
                const ref = this.$refs[refName];
                const root = ref && ref.$el ? ref.$el : ref;
                const input = root && root.querySelector ? root.querySelector('input') : null;
                if (!input) return;

                input.focus();
                input.select();
                this.openSearchMenu(refName);
            },
            openSearchMenu: function (refName) {
                this.$nextTick(() => {
                    const name = refName || (this.dialog ? 'dialogSearch' : 'inlineSearch');
                    const ref = this.$refs[name];
                    const root = ref && ref.$el ? ref.$el : ref;
                    const input = root && root.querySelector ? root.querySelector('input') : null;
                    if (ref && (this.dialog || !input || document.activeElement === input)) ref.isMenuActive = true;
                });
            },
            editableTarget: function (target) {
                const tag = target && target.tagName ? target.tagName.toLowerCase() : '';
                return ['input', 'textarea', 'select'].indexOf(tag) !== -1 || !!(target && target.isContentEditable);
            },
            onGlobalKeydown: function (event) {
                const key = _.toLower(event.key || '');
                const commandShortcut = (event.ctrlKey || event.metaKey) && key === 'k';
                const slashShortcut = key === '/' && !event.ctrlKey && !event.metaKey && !event.altKey && !this.editableTarget(event.target);

                if (!commandShortcut && !slashShortcut) return;

                event.preventDefault();
                this.openSearch(commandShortcut ? 'shortcut' : 'slash');
            },
            selectResult: function (selected) {
                if (!this.isResultItem(selected)) return;

                this.trackSearchSelection(selected);
                this.rememberSearchResult(selected);

                const route = selected.route || {};
                let next = null;
                if (route.name) {
                    next = {
                        name: route.name,
                        params: route.params || {},
                        query: route.query || {}
                    };
                } else if (route.path) {
                    next = route.path;
                }
                if (!next) return;

                const resolved = this.$router.resolve(next).route;
                this.model = null;
                this.closeSearch();
                this.search = null;
                this.setItems([]);

                if (resolved.fullPath === this.$route.fullPath) return;
                this.$router.push(next).catch(() => {});
            },
            trackSearchOpened: function (trigger) {
                if (!GeckoClient.analytics || this.searchOpened) return;

                this.searchOpened = true;
                GeckoClient.analytics.emit('search_opened', {
                    trigger: trigger || 'focus',
                    query_present: this.getQueryText() !== '',
                    surface: this.searchSurface()
                });
            },
            trackSearchSelection: function (selected) {
                if (!GeckoClient.analytics) return;

                const analytics = Object.assign(
                    {},
                    selected.analytics || {},
                    {
                        event_name: 'search_result_selected',
                        result_type: selected.type,
                        coin_id: selected.coin_id || (selected.type === 'coin' ? selected.id : null),
                        exchange_id: selected.exchange_id || (selected.type === 'exchange' ? selected.id : null),
                        category_id: selected.category_id || (selected.type === 'category' ? selected.id : null),
                        rank: selected.rank,
                        query_length_bucket: _.get(selected, 'analytics.query_length_bucket') || GeckoClient.analytics.queryLengthBucket(this.search),
                        surface: this.searchSurface()
                    }
                );

                GeckoClient.analytics.emit(analytics.event_name, analytics);
            },
            onFocus: function () {
                this.trackSearchOpened('focus');
                this.searchItems();
            },
            resultTypeLabel: function (item) {
                if (!item) return '';
                if (item.recent) return __( 'Recent' );
                if (item.type === 'coin') return __( 'Coin' );
                if (item.type === 'ton_asset') return __( 'TON asset' );
                if (item.type === 'exchange') return __( 'Exchange' );
                if (item.type === 'category') return __( 'Category' );
                return __( 'Action' );
            },
            resultIcon: function (item) {
                if (!item) return 'mdi-magnify';
                if (item.recent) return 'mdi-history';
                if (_.get(item, 'route.name') === 'crypto-exchange') return 'mdi-swap-horizontal-circle-outline';
                if (item.type === 'ton_asset') return 'mdi-diamond-stone';
                if (item.type === 'exchange') return 'mdi-bank-outline';
                if (item.type === 'category') return 'mdi-tag-outline';
                if (item.type === 'action') return 'mdi-lightning-bolt-outline';
                return 'mdi-currency-btc';
            },
            itemImage: function (item) {
                return item && (item.large || item.image);
            }
        }
    });

})(window, document, _, axios, Vue, GeckoClient);
