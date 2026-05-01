(function (window, VueRouter, GeckoClient) {
    'use strict';

    const router = GeckoClient.router = new VueRouter({
        mode: GeckoClient.routerMode,
        base: GeckoClient.routerBase,
        scrollBehavior: function () {
            document.getElementById('app').scrollIntoView();
        }
    });

    router.afterEach((to, from) => {
        GeckoClient.setCanonicalUrl();
        if (GeckoClient.telegram) {
            GeckoClient.telegram.updateBackButton(to, from);
        }
    })


})(window, VueRouter, GeckoClient);
