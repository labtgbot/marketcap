(function (window, GeckoClient) {
    'use strict';

    const route = GeckoClient.routesConfig.watchlist;
    if (!route) return;

    GeckoClient.router.addRoute({
        name: 'watchlist',
        path: route.path,
        component: {
            template: '#route-watchlist',
            created: function () {
                GeckoClient.setTitle(GeckoClient.getOptions('watchlist').title);
            }
        }
    });

})(window, GeckoClient);
