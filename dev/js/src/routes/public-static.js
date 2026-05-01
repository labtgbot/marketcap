(function (window, GeckoClient) {
    'use strict';

    const setTitle = GeckoClient.setTitle;

    ['support'].forEach(routeName => {
        const routeConfig = GeckoClient.routesConfig[routeName];
        if (!routeConfig) return;

        const routeOptions = GeckoClient.getOptions(routeName, {});

        GeckoClient.router.addRoute({
            name: routeName,
            path: routeConfig.path,
            component: {
                template: '#route-' + routeName,
                created: function () {
                    setTitle(routeOptions.title);
                }
            }
        });
    });

})(window, GeckoClient);
