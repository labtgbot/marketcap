(function (window, _, CoinGecko, GeckoClient) {
    'use strict';

    const routeConfig = GeckoClient.routesConfig['finance-products'];
    if (!routeConfig) return;

    const setTitle = GeckoClient.setTitle;

    const financeProductsOptions = GeckoClient.getOptions('finance-products');

    GeckoClient.router.addRoute({
        name: 'finance-products',
        path: routeConfig.path,
        component: {
            template: '#route-finance-products',
            data: function () {
                return {
                    tableHeaders: financeProductsOptions.tableHeaders.filter(header => header.show),
                    products: [],
                    page: 0,
                    perPage: financeProductsOptions.perPage,
                    loading: false,
                    loadMore: true,
                    loadMoreLoading: false,
                    platformsMap: new Map()
                };
            },
            created: function () {
                this.loading = true;
                Promise.all([this.fetchPlatforms(), this.fetchProducts()]).finally(() => this.loading = false);
                // update title meta tags
                setTitle(financeProductsOptions.title);
            },
            methods: {
                fetchPlatforms: function () {
                    return CoinGecko.financePlatforms({per_page: 250})
                        .then(platforms => {
                            platforms.forEach(platform => this.platformsMap.set(platform.name, platform));
                            return platforms;
                        });
                },
                fetchProducts: function () {
                    const params = {
                        per_page: this.perPage,
                        page: ++this.page
                    }
                    return CoinGecko.financeProducts(params)
                        .then(products => {
                            products.forEach(product => this.products.push(product));
                            this.loadMore = products.length === this.perPage;
                            return products;
                        })
                },
                fetchMoreProducts: function () {
                    this.loadMoreLoading = true;
                    return this.fetchProducts().finally(() => this.loadMoreLoading = false);
                },
                platformUrl: function (name) {
                    const platform = this.platformsMap.get(name) || {};
                    return platform.url;
                },
                platformColor: function (name) {
                    const platform = this.platformsMap.get(name);
                    if (platform) return platform.color;
                    return 'grey';
                }
            }
        }
    });

})(window, _, CoinGecko, GeckoClient);
