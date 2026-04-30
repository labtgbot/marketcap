(function (window, Vue, CoinGecko) {
    'use strict';

    Vue.component('gc-trending-coins', {
        template: '#component-trending-coins',
        data: function () {
            return {coins: []};
        },
        created: function () {
            CoinGecko.searchTrending().then(trending => {
                this.coins = _.each(trending.coins, coin => {
                    coin.route = {name: 'currency', params: {id: coin.id}}
                });
            });
        }
    });

})(window, Vue, CoinGecko);