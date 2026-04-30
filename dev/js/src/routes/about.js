(function (window, GeckoClient) {
    'use strict';

    const routeConfig = GeckoClient.routesConfig.about;
    if (!routeConfig) return;

    const setTitle = GeckoClient.setTitle;

    const aboutOptions = GeckoClient.getOptions('about');

    GeckoClient.router.addRoute({
        name: 'about',
        path: routeConfig.path,
        component: {
            template: '#route-about',
            created: function () {
                // update title meta tags
                setTitle(aboutOptions.title)
            }
        }
    });

})(window, GeckoClient);
