(function (window, _, Vue, GeckoClient, CoinGecko) {
    'use strict';

    const __ = GeckoClient.__;

    Vue.component('gc-search-bar', {
        template: '#component-search-bar',
        data: function () {
            return {
                model: null,
                search: null,
                loading: false,
                items: [],
                coins: null,
                exchanges: null
            };
        },
        watch: {
            model: function (selected) {
                if (!selected || !selected.route || !selected.id) return;
                const next = {
                    name: selected.route,
                    params: {id: selected.id}
                }
                const from = this.$route;
                if (next.name !== from.name || next.params.id !== from.params.id) {
                    this.$router.push(next);
                }
            }
        },
        methods: {
            avatarChar: function (name) {
                // result avatar char
                return _.toUpper(_.first(name)) || '?';
            },
            getQueryText: function () {
                // strips surrounding whitespaces and converts to lowercase
                return _.toLower(_.trim(this.search));
            },
            filterItem: function (item, queryText) {
                // look for queryText in currency name, symbol and id
                const itemString = _.toLower(item.name) + ' ' + _.toLower(item.symbol)  + ' ' + _.toLower(item.id);
                return _.includes(itemString, queryText);
            },
            filterList: function (list, queryText, size) {
                // filter list with 'filterItem' and restrict size
                const filtered = queryText === '' ? list : list.filter(item => this.filterItem(item, queryText));
                return _.slice(filtered, 0, size);
            },
            setItems: function () {
                const queryText = this.getQueryText();

                const categories = [
                    {
                        list: 'coins',
                        header: __( 'Currencies' ),
                        route: 'currency',
                        size: 10
                    },
                    {
                        list: 'exchanges',
                        header: __( 'Exchanges' ),
                        route: 'exchange',
                        size: 10
                    }
                ];

                const items = [];
                // get results separated by category
                _.each(categories, category => {
                    const listItems = this.filterList(this[category.list], queryText, category.size);
                    if (listItems.length) {
                        items.push({header: category.header});
                        listItems.forEach(item => {
                            item.route = category.route;
                            items.push(item);
                        });
                    }
                })
                this.items = items;
            },
            fetchData: function () {
                this.loading = true;

                return CoinGecko.search()
                    .then(search => {
                        this.coins = search.coins;
                        this.exchanges = search.exchanges;
                        return search;
                    })
                    .finally(() => this.loading = false);
            },
            searchItems: function () {
                // avoid multiple 'fetchData' calls
                if (this.loading) return;

                // search immediately
                if (this.coins && this.exchanges) this.setItems();

                // fetch before searching
                this.fetchData().then(() => this.setItems())
            },
            onFocus: function () {
                // forces download on bar click
                if (!this.coins || !this.exchanges) this.searchItems();
            }
        }
    });

})(window, _, Vue, GeckoClient, CoinGecko);
