(function (window, _, CoinGecko, GeckoClient) {
    'use strict';

    const routeConfig = GeckoClient.routesConfig['finance-platforms'];
    if (!routeConfig) return;

    const setTitle = GeckoClient.setTitle;

    const financePlatformsOptions = GeckoClient.getOptions('finance-platforms');

    GeckoClient.router.addRoute({
        name: 'finance-platforms',
        path: routeConfig.path,
        component: {
            template: '#route-finance-platforms',
            data: function () {
                return {
                    types: financePlatformsOptions.types,
                    selectedType: 'all',
                    platforms: [],
                    loading: false,
                    perPage: 250,
                };
            },
            created: function () {
                this.loading = true;
                this.fetchPlatforms().finally(() => this.loading = false);
                // update title meta tags
                setTitle(financePlatformsOptions.title);
            },
            computed: {
                filteredPlatforms: function () {
                    // restricted
                    if (this.selectedType !== 'all') {
                        const centralized = this.selectedType === 'cefi';
                        return _.filter(this.platforms, ['centralized', centralized]);
                    }
                    return this.platforms; // all
                }
            },
            methods: {
                fetchPlatforms: function () {
                    return CoinGecko.financePlatforms({per_page: this.perPage})
                        .then(platforms => {
                            return this.platforms = financePlatformsOptions.sort ? this.sortPlatforms(platforms) : platforms;
                        });
                },
                sortPlatforms: function (platforms) {
                    return _.sortBy(platforms, platform => _.deburr(_.toLower(platform.name)))
                }
            }
        }
    });

})(window, _, CoinGecko, GeckoClient);
