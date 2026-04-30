(function (window, GeckoClient) {
    'use strict';

    // redirects to root (/)
    // needs to be added to router in last, because it matches any path (*)

    GeckoClient.router.addRoute({
        name: 'not-found',
        path: '*',
        redirect: '/'
    });

})(window, GeckoClient);
