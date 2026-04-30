(function (window, _, Vue) {
    'use strict';

    const supportedSymbols = {
        ada: 'cardano',
        bnb: 'binance-coin',
        btc: 'bitcoin',
        busd: 'binance-usd',
        doge: 'dogecoin',
        dot: 'polkadot',
        eth: 'ethereum',
        usdc: 'usd-coin',
        usdt: 'tether',
        xrp: 'ripple'
    };

    const defaultSymbols = ['btc', 'eth'];


    Vue.component('gc-stats-bar', {
        props: {
            dominance: {
                default: () => defaultSymbols
            }
        },
        template: '#component-stats-bar',
        computed: {
            dominanceEntries: function () {
                let entries = [];
                let symbols = [];

                if (_.isArray(this.dominance)) {
                    symbols = this.dominance;
                } else if (_.isString(this.dominance)) {
                    symbols = this.dominance.split(',');
                }

                symbols.forEach(symbol => {
                    symbol = _.toLower(_.trim(symbol));
                    const id = _.get(supportedSymbols, symbol, false);
                    if (id) {
                        entries.push({
                            symbol: symbol,
                            id: id,
                            route: {name: 'currency', params: {id: id}}
                        })
                    }
                })

                return entries;
            }
        }
    });

})(window, _, Vue);
