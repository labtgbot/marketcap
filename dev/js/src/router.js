(function (window, _, VueRouter, GeckoClient) {
    'use strict';

    const trackYandexMetricaPageView = GeckoClient.trackYandexMetricaPageView = function () {
        const metrica = window.tonbankcardYandexMetrica;
        if (!metrica || metrica.enabled !== true || !metrica.counterId || typeof window.ym !== 'function') {
            return false;
        }

        const currentUrl = window.location && window.location.href ? window.location.href : '';
        if (!currentUrl || metrica.lastTrackedUrl === currentUrl) {
            return false;
        }

        const documentRef = window.document || {};
        const referer = metrica.lastTrackedUrl || documentRef.referrer || '';
        window.ym(metrica.counterId, 'hit', currentUrl, {
            title: documentRef.title || '',
            referer: referer
        });
        metrica.lastTrackedUrl = currentUrl;

        return true;
    };

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
        if (GeckoClient.achievements && _.isFunction(GeckoClient.achievements.trackRoute)) {
            GeckoClient.achievements.trackRoute(to);
        }
        if (typeof window.setTimeout === 'function') {
            window.setTimeout(trackYandexMetricaPageView, 0);
        } else {
            trackYandexMetricaPageView();
        }
    })


})(window, _, VueRouter, GeckoClient);
