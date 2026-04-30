(function (window, GeckoClient) {
    'use strict';

    const route = GeckoClient.routesConfig.ton;
    if (!route) return;

    GeckoClient.router.addRoute({
        name: 'ton',
        path: route.path,
        component: {
            template: '#route-ton',
            created: function () {
                GeckoClient.setTitle(GeckoClient.getOptions('ton').title);
            }
        }
    });

})(window, GeckoClient);
