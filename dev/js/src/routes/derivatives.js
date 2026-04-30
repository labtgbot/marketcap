(function (window, CoinGecko, GeckoClient) {
    'use strict';

    const routeConfig = GeckoClient.routesConfig.derivatives;
    if (!routeConfig) return;

    const __ = GeckoClient.__;
    const setTitle = GeckoClient.setTitle;

    const derivativesOptions = GeckoClient.getOptions('derivatives');
    const tableHeaders = derivativesOptions.tableHeaders.filter(header => header.show);


    GeckoClient.router.addRoute({
        name: 'derivatives',
        path: routeConfig.path,
        component: {
            template: '#route-derivatives',
            data: function () {
                return {
                    tableFooterProps: derivativesOptions.tableFooterProps,
                    derivatives: [],
                    markets: [],
                    loading: false,
                    search: null,
                    selectedType: derivativesOptions.defaultType,
                    types: derivativesOptions.types,
                    selectedMarket: 'all',
                };
            },
            computed: {
                tableHeaders: function () {
                    // filter specific type headers
                    return tableHeaders.filter(header => !header.type || header.type === this.selectedType);
                },
                isFiltered: function () {
                    return this.search || this.selectedMarket !=='all' || this.selectedType !== 'all';
                },
                items: function () {
                    let items = this.derivatives;
                    // filter if specific type is selected
                    if (this.selectedType !== 'all') items = items.filter(d => d.contract_type === this.selectedType);
                    // filter if specific market is selected
                    if (this.selectedMarket !== 'all') items = items.filter(d => d.market === this.selectedMarket);
                    return items;
                }
            },
            created: function () {
                this.fetchDerivatives();
                // update title meta tags
                setTitle(derivativesOptions.title);
            },
            methods: {
                fetchDerivatives() {
                    this.loading = true;
                    return CoinGecko.derivatives()
                        .then(derivatives => {
                            this.markets = [{value: 'all', text: __('All')}];

                            // sort markets for easier look up on the select element
                            const marketsMap = new Map();
                            derivatives.forEach(derivative => {
                                this.derivatives.push(derivative);
                                if (derivative.market) marketsMap.set(derivative.market.toLowerCase(), derivative.market);
                            });
                            Array.from(marketsMap.entries())
                                .sort()
                                .forEach(entry => this.markets.push({value: entry[1], text: entry[1]}));

                            return derivatives;
                        })
                        .finally(() => this.loading = false);
                },
                clearFilters: function () {
                    // sets filters defaults
                    this.search = null;
                    this.selectedMarket = 'all';
                    this.selectedType = 'all';
                },
                getMarketUrl: function (market) {
                    return GeckoClient.getCustomLink('derivativesMarkets', market);
                }
            }
        },
    });

})(window, CoinGecko, GeckoClient);