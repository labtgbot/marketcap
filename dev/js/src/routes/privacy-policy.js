(function (window, GeckoClient) {
    'use strict';

    const routeConfig = GeckoClient.routesConfig['privacy-policy'];
    if (!routeConfig) return;

    const setTitle = GeckoClient.setTitle;

    const privacyPolicyOptions = GeckoClient.getOptions('privacy-policy');

    GeckoClient.router.addRoute({
        name: 'privacy-policy',
        path: routeConfig.path,
        component: {
            template: '#route-privacy-policy',
            created: function () {
                // update title meta tags
                setTitle(privacyPolicyOptions.title)
            }
        }
    });

})(window, GeckoClient);
