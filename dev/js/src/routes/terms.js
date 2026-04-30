(function (window, GeckoClient) {
    'use strict';

    const routeConfig = GeckoClient.routesConfig.terms;
    if (!routeConfig) return;

    const setTitle = GeckoClient.setTitle;

    const termsOptions = GeckoClient.getOptions('terms');

    GeckoClient.router.addRoute({
        name: 'terms',
        path: routeConfig.path,
        component: {
            template: '#route-terms',
            created: function () {
                // update title meta tags
                setTitle(termsOptions.title)
            }
        }
    });

})(window, GeckoClient);
