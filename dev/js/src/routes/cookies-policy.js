(function (window, GeckoClient) {
    'use strict';

    const routeConfig = GeckoClient.routesConfig['cookies-policy'];
    if (!routeConfig) return;

    const setTitle = GeckoClient.setTitle;

    const cookiesPolicyOptions = GeckoClient.getOptions('cookies-policy');

    GeckoClient.router.addRoute({
        name: 'cookies-policy',
        path: routeConfig.path,
        component: {
            template: '#route-cookies-policy',
            created: function () {
                // update title meta tags
                setTitle(cookiesPolicyOptions.title)
            }
        }
    });

})(window, GeckoClient);
