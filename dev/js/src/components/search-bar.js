(function (window, _, axios, Vue, GeckoClient, CoinGecko) {
    'use strict';

    const __ = GeckoClient.__;
    const resultGroups = {
        coin: __( 'Currencies' ),
        ton_asset: __( 'TON assets' ),
        exchange: __( 'Exchanges' ),
        category: __( 'Categories' ),
        action: __( 'Actions' )
    };

    Vue.component('gc-search-bar', {
        template: '#component-search-bar',
        data: function () {
            return {
                model: null,
                search: null,
                loading: false,
                items: [],
                requestCounter: 0,
                searchOpened: false
            };
        },
        watch: {
            model: function (selected) {
                if (!selected || !selected.route || !selected.id) return;

                this.trackSearchSelection(selected);

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

                const resolved = _.isString(next) ? this.$router.resolve(next).route : this.$router.resolve(next).route;
                if (resolved.fullPath === this.$route.fullPath) return;
                this.$router.push(next).catch(() => {});
            }
        },
        methods: {
            avatarChar: function (name) {
                return _.toUpper(_.first(name)) || '?';
            },
            getQueryText: function () {
                return _.toLower(_.trim(this.search));
            },
            searchEndpoint: function () {
                return _.get(GeckoClient, 'search.apiBaseUrl', '/api/search');
            },
            searchSurface: function () {
                return _.get(GeckoClient, 'analytics.surface', () => 'public_web')();
            },
            setItems: function (results) {
                const items = [];
                const seenTypes = {};

                (results || []).forEach(item => {
                    const type = item.type || 'action';
                    if (!seenTypes[type]) {
                        seenTypes[type] = true;
                        items.push({header: resultGroups[type] || type});
                    }

                    items.push(item);
                });

                this.items = items;
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
                        this.setItems(_.get(data, 'results', []));
                    })
                    .catch(() => {
                        if (requestId === this.requestCounter) this.items = [];
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
                return this.fetchData(requestId);
            },
            trackSearchOpened: function () {
                if (!GeckoClient.analytics || this.searchOpened) return;

                this.searchOpened = true;
                GeckoClient.analytics.emit('search_opened', {
                    trigger: 'focus',
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
                this.trackSearchOpened();
                this.searchItems();
            }
        }
    });

})(window, _, axios, Vue, GeckoClient, CoinGecko);
